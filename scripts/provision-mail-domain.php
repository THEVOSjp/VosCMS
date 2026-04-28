<?php
/**
 * 메일 도메인 수동/배치 프로비저닝.
 *
 * 사용:
 *   php provision-mail-domain.php <order_id|order_number>     # 단일 주문 처리
 *   php provision-mail-domain.php --pending                    # 미발급 active 주문 일괄 처리
 *   php provision-mail-domain.php --pending --dry              # 미발급 목록만 (실제 처리 X)
 */

require __DIR__ . '/../vendor/autoload.php';

// .env 로드
$envFile = __DIR__ . '/../.env';
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') continue;
    if (strpos($line, '=') !== false) {
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
    }
}

$arg1 = $argv[1] ?? null;
$dryRun = in_array('--dry', $argv, true);

if (!$arg1) {
    fwrite(STDERR, "Usage: php provision-mail-domain.php <order_id|order_number> [--dry]\n");
    fwrite(STDERR, "       php provision-mail-domain.php --pending [--dry]\n");
    exit(1);
}

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$pdo = new PDO(
    "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
    $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$provisioner = new \RzxLib\Core\Mail\MailDomainProvisioner($pdo);

if ($arg1 === '--pending') {
    // 미발급 active 주문 일괄 처리
    $stmt = $pdo->query("
        SELECT o.id, o.order_number, o.status, o.domain, s.metadata
        FROM {$prefix}orders o
        JOIN {$prefix}subscriptions s ON s.order_id = o.id AND s.type = 'hosting'
        WHERE o.status IN ('active', 'paid')
          AND (
            JSON_EXTRACT(s.metadata, '$.mail_provision') IS NULL
            OR JSON_EXTRACT(s.metadata, '$.mail_provision.provisioned_at') IS NULL
          )
        ORDER BY o.id
    ");
    $orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo "=== 미발급 active 주문: " . count($orders) . "건 ===\n";

    if ($dryRun) {
        foreach ($orders as $o) {
            echo "  #{$o['id']} {$o['order_number']} {$o['status']} domain={$o['domain']}\n";
        }
        echo "\n(dry-run — 실제 처리 안함)\n";
        exit(0);
    }

    $ok = 0; $fail = 0;
    foreach ($orders as $o) {
        try {
            $r = $provisioner->provisionForOrder((int)$o['id']);
            $sub = $r['temp_subdomain'] ?? '?';
            echo "  ✓ #{$o['id']} {$o['order_number']} → $sub\n";
            $ok++;
        } catch (\Throwable $e) {
            echo "  ✗ #{$o['id']} {$o['order_number']}: " . $e->getMessage() . "\n";
            $fail++;
        }
    }
    echo "\n결과: 성공 $ok, 실패 $fail\n";
    exit($fail > 0 ? 1 : 0);
}

// 단일 주문 처리 — 숫자면 id, 아니면 order_number 로 조회
$orderId = 0;
if (ctype_digit($arg1)) {
    $orderId = (int)$arg1;
} else {
    $s = $pdo->prepare("SELECT id FROM {$prefix}orders WHERE order_number = ?");
    $s->execute([$arg1]);
    $orderId = (int)$s->fetchColumn();
}
if (!$orderId) {
    fwrite(STDERR, "Order not found: $arg1\n");
    exit(1);
}

if ($dryRun) {
    $info = $provisioner->getProvisionInfo($orderId);
    echo "Order #$orderId provision info:\n";
    echo $info ? json_encode($info, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n" : "  (not provisioned)\n";
    exit(0);
}

try {
    $result = $provisioner->provisionForOrder($orderId);
    echo "✓ Order #$orderId provisioned\n";
    echo json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE) . "\n";
} catch (\Throwable $e) {
    fwrite(STDERR, "✗ Failed: " . $e->getMessage() . "\n");
    exit(1);
}
