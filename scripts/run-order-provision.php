<?php
/**
 * 주문 단위 통합 프로비저닝 CLI runner.
 *
 * service-order.php 의 _autoProvisionHosting() + _autoProvisionMailDomain() 을
 * CLI 컨텍스트에서 안전하게 실행 — FPM reload 영향 받지 않는 별도 프로세스.
 *
 * 호출 시점:
 *   1. /service/complete 페이지 렌더링 후 (fastcgi_finish_request → exec fork)
 *   2. /mypage/services/<order> 진입 시 (paid + not_provisioned 검출 시 fallback)
 *   3. cron 폴링 (매 1~5분, 누락 케이스 catch-up)
 *
 * Idempotent: 이미 hosting_provisioned=true 면 skip.
 *
 * 사용:
 *   sudo -u www-data php scripts/run-order-provision.php --order=<ORDER>
 *
 * Lock:
 *   /tmp/voscms-provision-<ORDER>.lock — 동시 실행 방지 (flock)
 *   완료 시 자동 해제. 실행 중 재호출 시 즉시 종료(중복 차단).
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));

if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

// .env 로드
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') !== false) {
            [$k, $v] = explode('=', $line, 2);
            $_ENV[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
        }
    }
}

$opts = getopt('', ['order:', 'force']);
if (empty($opts['order'])) {
    fwrite(STDERR, "Usage: php run-order-provision.php --order=<ORDER> [--force]\n");
    exit(1);
}

$orderNumber = strtoupper($opts['order']);
$force = isset($opts['force']);

// Lock 파일 — 동시 실행 차단
$lockFile = sys_get_temp_dir() . '/voscms-provision-' . preg_replace('/[^A-Za-z0-9_-]/', '', $orderNumber) . '.lock';
$lockFp = fopen($lockFile, 'c');
if (!$lockFp || !flock($lockFp, LOCK_EX | LOCK_NB)) {
    echo "[skip] {$orderNumber} 다른 프로세스가 처리 중\n";
    exit(0);
}

register_shutdown_function(function () use ($lockFp, $lockFile) {
    if ($lockFp) {
        @flock($lockFp, LOCK_UN);
        @fclose($lockFp);
        @unlink($lockFile);
    }
});

@set_time_limit(900);

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$pdo = new PDO(
    "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
    $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// 주문 조회
$st = $pdo->prepare("SELECT * FROM {$prefix}orders WHERE order_number = ? LIMIT 1");
$st->execute([$orderNumber]);
$order = $st->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    fwrite(STDERR, "주문 없음: {$orderNumber}\n");
    exit(1);
}
$orderId = (int)$order['id'];

if (!in_array($order['status'] ?? '', ['paid', 'active'], true)) {
    fwrite(STDERR, "주문 상태가 paid/active 가 아님 (status={$order['status']})\n");
    exit(1);
}

// 이미 프로비저닝 끝났는지 확인 (idempotent)
$hSt = $pdo->prepare("SELECT id, metadata FROM {$prefix}subscriptions WHERE order_id = ? AND type = 'hosting' LIMIT 1");
$hSt->execute([$orderId]);
$hSub = $hSt->fetch(PDO::FETCH_ASSOC);
if ($hSub) {
    $hMeta = json_decode($hSub['metadata'] ?? '{}', true) ?: [];
    if (!$force && !empty($hMeta['hosting_provisioned'])) {
        echo "[skip] {$orderNumber} 이미 프로비저닝 완료 ({$hMeta['hosting_provisioned_at']})\n";
        exit(0);
    }
}

echo "[*] run-order-provision: {$orderNumber} (domain={$order['domain']}, option={$order['domain_option']})\n";

// service-order.php 의 _autoProvisionHosting / _autoProvisionMailDomain 헬퍼 재사용
// service-order.php 가 헬퍼를 inline 으로 가지고 있어서 require 시 사이드이펙트 발생 →
// 헬퍼 로직 인라인 복제 (단순 호출 — HostingProvisioner / MailDomainProvisioner 가 핵심)

// ─── 1. 호스팅 프로비저닝 ───
try {
    $domain = $order['domain'] ?? '';
    $capacity = $order['hosting_capacity'] ?? '';
    $domainOption = $order['domain_option'] ?? 'free';

    if (empty($domain) || empty($capacity)) {
        $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type) VALUES (?, 'hosting_provision_skipped', ?, 'system')")
            ->execute([$orderId, json_encode(['reason' => 'no domain or capacity'])]);
        echo "[skip] no domain or capacity\n";
    } elseif ($domainOption !== 'free') {
        $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type) VALUES (?, 'hosting_provision_deferred', ?, 'system')")
            ->execute([$orderId, json_encode(['domain_option' => $domainOption, 'note' => '도메인 활성화 후 관리자가 수동 트리거'])]);
        echo "[defer] domain_option={$domainOption}\n";
    } else {
        $provisioner = new \RzxLib\Core\Hosting\HostingProvisioner($pdo);
        $result = $provisioner->provision($orderNumber, $domain, $capacity);

        // install addon 의 install_info 있으면 voscms 자동 설치
        if ($result['success'] && !empty($result['db']['success'])) {
            $aSt = $pdo->prepare("SELECT id, metadata FROM {$prefix}subscriptions WHERE order_id = ? AND type = 'addon'");
            $aSt->execute([$orderId]);
            while ($aRow = $aSt->fetch(PDO::FETCH_ASSOC)) {
                $aMeta = json_decode($aRow['metadata'] ?? '{}', true) ?: [];
                if (!empty($aMeta['install_info'])) {
                    $installResult = $provisioner->installVoscms($orderNumber, $domain, $result['db'], $aMeta['install_info']);
                    $result['install'] = $installResult;
                    if (!empty($installResult['success'])) {
                        $aMeta['install_completed_at'] = $installResult['installed_at'] ?? date('c');
                        $aMeta['install_admin_url'] = $installResult['admin_url'] ?? null;
                        $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ?, completed_at = NOW() WHERE id = ?")
                            ->execute([json_encode($aMeta, JSON_UNESCAPED_UNICODE), $aRow['id']]);
                    }
                    break;
                }
            }
        }

        $action = $result['success'] ? 'hosting_provisioned' : 'hosting_provision_failed';
        $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type) VALUES (?, ?, ?, 'system')")
            ->execute([$orderId, $action, json_encode($result, JSON_UNESCAPED_UNICODE)]);

        // hosting subscription metadata 갱신
        if ($result['success'] && $hSub) {
            $hMeta['hosting_provisioned'] = true;
            $hMeta['hosting_provisioned_at'] = date('c');
            $hMeta['server'] = array_merge($hMeta['server'] ?? [], [
                'db' => $result['db'],
                'env' => ['php' => '8.3', 'mysql' => '10.11'],
                'host' => ['name' => 'host.voscms.com', 'ip' => '27.81.39.11'],
                // 시스템 경로 (provision 결과 보존 — 운영자 SSH·디버깅용)
                'username' => $result['username'] ?? null,
                'home' => $result['home'] ?? null,
                'docroot' => $result['docroot'] ?? null,
                'vhost' => $result['vhost'] ?? null,
                'fpm_pool' => $result['fpm_pool'] ?? null,
            ]);
            $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
                ->execute([json_encode($hMeta, JSON_UNESCAPED_UNICODE), $hSub['id']]);
        }

        echo "[ok] hosting_provisioned (success=" . ($result['success'] ? 'Y' : 'N') . ")\n";
    }
} catch (\Throwable $e) {
    error_log("[run-order-provision] hosting failed: " . $e->getMessage());
    $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type) VALUES (?, 'hosting_provision_failed', ?, 'system')")
        ->execute([$orderId, json_encode(['error' => substr($e->getMessage(), 0, 500)], JSON_UNESCAPED_UNICODE)]);
    echo "[err] hosting: " . $e->getMessage() . "\n";
}

// ─── 2. 메일 도메인 프로비저닝 ───
try {
    $mailProvisioner = new \RzxLib\Core\Mail\MailDomainProvisioner($pdo);
    $result = $mailProvisioner->provisionForOrder($orderId);
    $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type) VALUES (?, 'mail_provisioned', ?, 'system')")
        ->execute([$orderId, json_encode(['result' => $result], JSON_UNESCAPED_UNICODE)]);
    echo "[ok] mail_provisioned (mode=" . ($result['mode'] ?? '?') . ")\n";
} catch (\Throwable $e) {
    error_log("[run-order-provision] mail failed: " . $e->getMessage());
    $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type) VALUES (?, 'mail_provision_failed', ?, 'system')")
        ->execute([$orderId, json_encode(['error' => substr($e->getMessage(), 0, 500)], JSON_UNESCAPED_UNICODE)]);
    echo "[err] mail: " . $e->getMessage() . "\n";
}

echo "[done] {$orderNumber}\n";
exit(0);
