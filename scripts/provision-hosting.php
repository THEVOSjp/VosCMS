<?php
/**
 * 호스팅 자동 셋업 CLI 도구
 *
 * 사용:
 *   php provision-hosting.php SVC260428AE7476 hotel.21ces.com 1GB
 *   php provision-hosting.php --deprovision SVC260428AE7476 hotel.21ces.com
 *   php provision-hosting.php --order SVC260428AE7476  # DB 에서 도메인/용량 자동 조회
 *
 * voscms-com (prod) 또는 voscms (dev) 위치에서 실행 가능. 실행 사용자는 www-data 권장.
 */

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));

if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

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

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$pdo = new PDO(
    "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
    $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$args = array_slice($argv, 1);
$mode = 'provision';
if (in_array('--deprovision', $args, true)) {
    $mode = 'deprovision';
    $args = array_values(array_filter($args, fn($a) => $a !== '--deprovision'));
}
$autoLookup = false;
if (in_array('--order', $args, true)) {
    $autoLookup = true;
    $args = array_values(array_filter($args, fn($a) => $a !== '--order'));
}
$shouldInstallFlag = false;
if (in_array('--install-voscms', $args, true)) {
    $shouldInstallFlag = true;
    $args = array_values(array_filter($args, fn($a) => $a !== '--install-voscms'));
}

if (empty($args[0])) {
    fwrite(STDERR, "Usage:\n");
    fwrite(STDERR, "  provision:   php provision-hosting.php <ORDER> <DOMAIN> <CAPACITY>\n");
    fwrite(STDERR, "  auto-lookup: php provision-hosting.php --order <ORDER>\n");
    fwrite(STDERR, "  deprovision: php provision-hosting.php --deprovision <ORDER> <DOMAIN>\n");
    exit(1);
}

$orderNumber = strtoupper($args[0]);

if ($autoLookup) {
    $stmt = $pdo->prepare("SELECT domain, hosting_capacity FROM {$prefix}orders WHERE order_number = ?");
    $stmt->execute([$orderNumber]);
    $row = $stmt->fetch(PDO::FETCH_ASSOC);
    if (!$row) { fwrite(STDERR, "주문 없음: {$orderNumber}\n"); exit(1); }
    $domain = $row['domain'];
    $capacity = $row['hosting_capacity'];
} else {
    $domain = $args[1] ?? null;
    $capacity = $args[2] ?? '1GB';
    if (!$domain) { fwrite(STDERR, "도메인 필요\n"); exit(1); }
}

echo "[*] {$mode}\n";
echo "    order:    {$orderNumber}\n";
echo "    domain:   {$domain}\n";
if ($mode === 'provision') echo "    capacity: {$capacity}\n";
echo "\n";

$prov = new \RzxLib\Core\Hosting\HostingProvisioner($pdo, $prefix);

if ($mode === 'provision') {
    $result = $prov->provision($orderNumber, $domain, $capacity);

    // --install-voscms 또는 자동 lookup 시 install_info 가 metadata 에 있으면 voscms 설치
    $shouldInstall = $shouldInstallFlag;
    $installInfo = null;
    if ($autoLookup && $result['success']) {
        // metadata.install_info 자동 조회
        $stmt = $pdo->prepare("SELECT metadata FROM {$prefix}subscriptions WHERE order_id = (SELECT id FROM {$prefix}orders WHERE order_number = ?) AND type = 'addon'");
        $stmt->execute([$orderNumber]);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $m = json_decode($row['metadata'] ?? '{}', true) ?: [];
            if (!empty($m['install_info'])) {
                $installInfo = $m['install_info'];
                $shouldInstall = true;
                break;
            }
        }
    }
    if ($shouldInstall && $result['success'] && !empty($result['db']['success'])) {
        $installInfo = $installInfo ?: [
            'admin_id' => 'admin',
            'admin_email' => 'admin@' . $domain,
            'admin_pw' => bin2hex(random_bytes(8)),
            'site_title' => $domain,
        ];
        echo "\n[*] VosCMS 자동 설치 시작...\n";
        $installResult = $prov->installVoscms($orderNumber, $domain, $result['db'], $installInfo);
        $result['install'] = $installResult;
    }
} else {
    $result = $prov->deprovision($orderNumber, $domain);
}

echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT) . "\n";
exit($result['success'] ? 0 : 1);
