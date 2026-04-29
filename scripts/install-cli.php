<?php
/**
 * VosCMS 자동 설치 CLI 도구 (호스팅 자동화 백필 / 디버깅용).
 *
 * 호스팅 프로비저닝이 이미 끝난 상태에서 install-core.php 를 headless 로 실행.
 * 일반 결제 흐름은 service-order.php → _autoProvisionHosting → installVoscms 로
 * 자동 호출되므로, 이 스크립트는 수동 백필 / 재시도용.
 *
 * 사용:
 *   sudo -u www-data php scripts/install-cli.php --order=SVC2604280CB6E6
 *
 *   metadata.install_info (admin_id, admin_email, admin_pw, site_title) 자동 조회.
 *
 *   추가 옵션 (install_info 덮어쓰기):
 *     --admin-email=...
 *     --admin-pw=...
 *     --admin-id=...
 *     --site-title=...
 *     --locale=ko (default)
 *
 * voscms / voscms-com 양쪽에서 실행 가능. 실행 사용자는 www-data 권장
 * (sudo 권한 + DB 접근 + 설치 디렉토리 권한).
 */

if (PHP_SAPI !== 'cli') {
    fwrite(STDERR, "CLI only.\n");
    exit(1);
}

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));

if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

// .env 로드 (메인 voscms / voscms-com)
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

$opts = getopt('', [
    'order:', 'domain::',
    'admin-email::', 'admin-pw::', 'admin-id::', 'site-title::',
    'locale::', 'timezone::', 'admin-path::',
]);

if (empty($opts['order'])) {
    fwrite(STDERR, "Usage: php install-cli.php --order=<ORDER> [--admin-email=... --admin-pw=...]\n");
    exit(1);
}

$orderNumber = strtoupper($opts['order']);

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$pdo = new PDO(
    "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
    $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

// 주문 조회
$st = $pdo->prepare("SELECT id, domain FROM {$prefix}orders WHERE order_number = ? LIMIT 1");
$st->execute([$orderNumber]);
$order = $st->fetch(PDO::FETCH_ASSOC);
if (!$order) {
    fwrite(STDERR, "주문 없음: {$orderNumber}\n");
    exit(1);
}
$orderId = (int)$order['id'];
$domain = $opts['domain'] ?? $order['domain'];

// DB 정보: 우선 subscription metadata.server.db, 없으면 customer docroot 의 .env 에서 추출
$hSt = $pdo->prepare("SELECT id, metadata FROM {$prefix}subscriptions WHERE order_id = ? AND type = 'hosting' LIMIT 1");
$hSt->execute([$orderId]);
$hSub = $hSt->fetch(PDO::FETCH_ASSOC);
if (!$hSub) {
    fwrite(STDERR, "hosting subscription 없음.\n");
    exit(1);
}
$hMeta = json_decode($hSub['metadata'] ?? '{}', true) ?: [];
$dbInfo = $hMeta['server']['db'] ?? null;

if (!$dbInfo || empty($dbInfo['db_user']) || empty($dbInfo['db_pass'])) {
    // 폴백: customer docroot 의 .env 에서 DB 정보 추출
    $custEnv = "/var/www/customers/{$orderNumber}/public_html/.env";
    if (!file_exists($custEnv)) {
        fwrite(STDERR, "DB 정보 없음 — metadata + {$custEnv} 모두 비어있음.\n");
        fwrite(STDERR, "호스팅을 먼저 프로비저닝하세요: php scripts/provision-hosting.php --order <ORDER>\n");
        exit(1);
    }
    $envVars = [];
    foreach (file($custEnv, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || !str_contains($line, '=')) continue;
        [$k, $v] = explode('=', $line, 2);
        $envVars[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
    }
    $dbInfo = [
        'db_host' => $envVars['DB_HOST'] ?? '127.0.0.1',
        'db_port' => $envVars['DB_PORT'] ?? '3306',
        'db_name' => $envVars['DB_DATABASE'] ?? '',
        'db_user' => $envVars['DB_USERNAME'] ?? '',
        'db_pass' => $envVars['DB_PASSWORD'] ?? '',
        'db_prefix' => $envVars['DB_PREFIX'] ?? 'rzx_',
    ];
    if (empty($dbInfo['db_user']) || empty($dbInfo['db_pass'])) {
        fwrite(STDERR, "{$custEnv} 에 DB 자격 정보 부족.\n");
        exit(1);
    }
    echo "[*] DB 정보 customer .env 에서 폴백 로드.\n";
}

// addon 의 install_info 자동 조회 + CLI 옵션 병합
$aSt = $pdo->prepare("SELECT id, metadata FROM {$prefix}subscriptions WHERE order_id = ? AND type = 'addon'");
$aSt->execute([$orderId]);
$installInfo = null;
$addonId = null;
while ($aRow = $aSt->fetch(PDO::FETCH_ASSOC)) {
    $aMeta = json_decode($aRow['metadata'] ?? '{}', true) ?: [];
    if (!empty($aMeta['install_info'])) {
        $installInfo = $aMeta['install_info'];
        $addonId = (int)$aRow['id'];
        break;
    }
}
$installInfo = $installInfo ?: [];
foreach (['admin-email' => 'admin_email', 'admin-pw' => 'admin_pw', 'admin-id' => 'admin_id',
          'site-title' => 'site_title', 'locale' => 'locale', 'timezone' => 'timezone',
          'admin-path' => 'admin_path'] as $cli => $key) {
    if (!empty($opts[$cli])) $installInfo[$key] = $opts[$cli];
}

if (empty($installInfo['admin_email']) || empty($installInfo['admin_pw'])) {
    fwrite(STDERR, "install_info.admin_email + admin_pw 필요 (addon metadata 또는 --admin-email/--admin-pw).\n");
    exit(1);
}

echo "[*] install\n";
echo "    order:   {$orderNumber}\n";
echo "    domain:  {$domain}\n";
echo "    admin:   {$installInfo['admin_email']}\n";
echo "\n";

$prov = new \RzxLib\Core\Hosting\HostingProvisioner($pdo, $prefix);
$result = $prov->installVoscms($orderNumber, $domain, $dbInfo, $installInfo);

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";

// 성공 시 addon metadata 에 install_completed_at 기록
if (!empty($result['success']) && $addonId) {
    $aMeta['install_completed_at'] = $result['installed_at'] ?? date('c');
    $aMeta['install_admin_url'] = $result['admin_url'] ?? null;
    $upd = $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?");
    $upd->execute([json_encode($aMeta, JSON_UNESCAPED_UNICODE), $addonId]);
    echo "✅ install_completed_at 기록됨 (subscription #{$addonId})\n";

    $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type) VALUES (?, 'voscms_installed', ?, 'system')")
        ->execute([$orderId, json_encode($result, JSON_UNESCAPED_UNICODE)]);
}

exit(!empty($result['success']) ? 0 : 1);
