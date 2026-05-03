<?php
/**
 * E2E 주문 검증 — 관리자 신청서 → 자동 프로비저닝 → 자원 검증 → 디프로비저닝 → 정리 확인
 *
 * 사용:
 *   sudo -u www-data php /var/www/voscms-com/scripts/e2e-order-test.php \
 *     [--domain=test-e2e.21ces.com] [--plan=system] [--keep] [--no-install]
 *
 * 옵션:
 *   --domain=<sub.zone>  무료 서브도메인 (기본: e2e-{시각}.21ces.com)
 *   --plan=<_id>         hosting plan _id (기본: system)
 *   --keep               통과 후 자원 보존 (디프로비저닝 skip)
 *   --no-install         install addon 체크 안 함 (VosCMS 자동 설치 skip)
 *
 * 종료 코드: 0 = PASS / 1 = FAIL
 */

if (PHP_SAPI !== 'cli') { fwrite(STDERR, "CLI only\n"); exit(2); }
if (!defined('BASE_PATH')) define('BASE_PATH', '/var/www/voscms-com');

require_once BASE_PATH . '/vendor/autoload.php';
foreach (file(BASE_PATH . '/.env') as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') continue;
    if (strpos($line, '=') !== false) {
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
    }
}

$opts = getopt('', ['domain::','plan::','keep','no-install']);
$DOMAIN  = $opts['domain'] ?? ('e2e-' . date('His') . '.21ces.com');
$PLAN_ID = $opts['plan']   ?? 'system';
$KEEP    = isset($opts['keep']);
$NO_INST = isset($opts['no-install']);

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
    $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);

// ─── 출력 헬퍼 ────────────────────────────────────────────────
$pass = 0; $fail = 0; $warn = 0;
function step(string $msg) { echo "\n\033[1;36m▶ {$msg}\033[0m\n"; }
function ok(string $msg)   { global $pass; $pass++; echo "  \033[32m✓\033[0m {$msg}\n"; }
function bad(string $msg)  { global $fail; $fail++; echo "  \033[31m✗\033[0m {$msg}\n"; }
function warning(string $msg) { global $warn; $warn++; echo "  \033[33m⚠\033[0m {$msg}\n"; }
function info(string $msg) { echo "  \033[90m·\033[0m {$msg}\n"; }

step("E2E 주문 검증 시작 — domain={$DOMAIN}, plan={$PLAN_ID}, keep=" . ($KEEP ? 'Y' : 'N') . ", no-install=" . ($NO_INST ? 'Y' : 'N'));

// ─── 1. 사전 검증 ─────────────────────────────────────────────
step("1. 사전 검증");
$existSt = $pdo->prepare("SELECT id FROM {$prefix}orders WHERE domain = ?");
$existSt->execute([$DOMAIN]);
if ($existSt->fetchColumn()) { bad("동일 도메인 주문 이미 존재. 다른 도메인 사용하거나 기존 주문 정리 후 재실행."); exit(1); }
ok("도메인 충돌 없음");

$planSt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = 'service_hosting_plans' LIMIT 1");
$planSt->execute();
$plans = json_decode($planSt->fetchColumn() ?: '[]', true) ?: [];
$plan = null;
foreach ($plans as $p) { if (($p['_id'] ?? '') === $PLAN_ID) { $plan = $p; break; } }
if (!$plan) { bad("플랜 '{$PLAN_ID}' 없음"); exit(1); }
ok("플랜 발견: {$plan['label']} ({$plan['capacity']})");

// ─── 2. 테스트용 user 확보 ─────────────────────────────────────
step("2. 테스트용 user 확보");
$testEmail = 'e2e-test@thevos.jp';
$uSt = $pdo->prepare("SELECT id FROM {$prefix}users WHERE email = ? LIMIT 1");
$uSt->execute([$testEmail]);
$testUserId = $uSt->fetchColumn();
if (!$testUserId) {
    $testUserId = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
    $pdo->prepare("INSERT INTO {$prefix}users (id, email, name, password, role, created_at) VALUES (?, ?, 'E2E Test', ?, 'user', NOW())")
        ->execute([$testUserId, $testEmail, password_hash('e2e_dummy', PASSWORD_BCRYPT)]);
    ok("신규 user 생성 ({$testUserId})");
} else {
    ok("기존 user 사용 ({$testUserId})");
}

// ─── 3. 주문 생성 (admin_create_order 흐름 인라인 시뮬) ─────────
step("3. 주문 생성 (admin_create_order 시뮬레이션)");
$orderNumber = 'SVC' . date('ymd') . strtoupper(substr(md5(uniqid()), 0, 6));
$orderUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
);
$months = 12;
$startedAt = date('Y-m-d H:i:s');
$expiresAt = date('Y-m-d H:i:s', strtotime("+{$months} months"));
$currency = 'JPY';

$itemsJson = json_encode([
    'hosting' => ['plan_id' => $PLAN_ID, 'label' => $plan['label'], 'capacity' => $plan['capacity'], 'price' => 0, 'months' => $months],
    'addons' => $NO_INST ? [] : [['id' => 'install', 'label' => '설치 지원', 'price' => 0, 'one_time' => true]],
    'subtotal' => 0, 'tax' => 0, 'total' => 0,
], JSON_UNESCAPED_UNICODE);

$pdo->beginTransaction();
$pdo->prepare("INSERT INTO {$prefix}orders
    (uuid, order_number, user_id, status, hosting_capacity, domain, domain_option, contract_months, items, total, currency, payment_method, started_at, expires_at, created_at)
    VALUES (?, ?, ?, 'paid', ?, ?, 'free', ?, ?, 0, ?, 'free', ?, ?, NOW())")
    ->execute([$orderUuid, $orderNumber, $testUserId, $plan['capacity'], $DOMAIN, $months, $itemsJson, $currency, $startedAt, $expiresAt]);
$orderId = (int)$pdo->lastInsertId();

// hosting sub
$pdo->prepare("INSERT INTO {$prefix}subscriptions
    (order_id, user_id, type, service_class, label, unit_price, quantity, billing_amount, billing_cycle, billing_months,
     currency, started_at, billing_start, expires_at, status, metadata)
    VALUES (?, ?, 'hosting', 'recurring', ?, 0, 1, 0, 'monthly', ?, ?, ?, ?, ?, 'active', ?)")
    ->execute([
        $orderId, $testUserId,
        ($plan['label'] ?? '') . ' ' . ($plan['capacity'] ?? ''),
        $months, $currency, $startedAt, $startedAt, $expiresAt,
        json_encode(['capacity' => $plan['capacity'], 'admin_created' => 1, 'e2e' => 1], JSON_UNESCAPED_UNICODE),
    ]);

// mail sub (자동)
$pdo->prepare("INSERT INTO {$prefix}subscriptions
    (order_id, user_id, type, service_class, label, unit_price, quantity, billing_amount, billing_cycle, billing_months,
     currency, started_at, expires_at, status, metadata)
    VALUES (?, ?, 'mail', 'free', '기본 메일', 0, 0, 0, 'custom', ?, ?, ?, ?, 'active', ?)")
    ->execute([$orderId, $testUserId, $months, $currency, $startedAt, $expiresAt,
        json_encode(['accounts' => 0, 'mail_accounts' => [], 'admin_created' => 1, 'e2e' => 1], JSON_UNESCAPED_UNICODE)]);

// domain sub (자동, free)
$pdo->prepare("INSERT INTO {$prefix}subscriptions
    (order_id, user_id, type, service_class, label, unit_price, quantity, billing_amount, billing_cycle, billing_months,
     currency, started_at, expires_at, status, metadata)
    VALUES (?, ?, 'domain', 'free', '도메인 (무료)', 0, 1, 0, 'monthly', 1, ?, ?, ?, 'active', ?)")
    ->execute([$orderId, $testUserId, $currency, $startedAt, date('Y-m-d H:i:s', strtotime('+1 month')),
        json_encode(['domains' => [$DOMAIN], 'free_subdomain' => true, 'admin_created' => 1, 'e2e' => 1], JSON_UNESCAPED_UNICODE)]);

// install addon (옵션)
if (!$NO_INST) {
    $installInfo = ['admin_id' => $testEmail, 'admin_email' => $testEmail, 'admin_pw' => bin2hex(random_bytes(6)), 'site_title' => 'E2E Test ' . $DOMAIN];
    $pdo->prepare("INSERT INTO {$prefix}subscriptions
        (order_id, user_id, type, service_class, label, unit_price, quantity, billing_amount, billing_cycle, billing_months,
         currency, started_at, expires_at, status, metadata)
        VALUES (?, ?, 'addon', 'one_time', '설치 지원', 0, 1, 0, 'one_time', NULL, ?, ?, ?, 'active', ?)")
        ->execute([$orderId, $testUserId, $currency, $startedAt, $expiresAt,
            json_encode(['admin_created' => 1, 'addon_id' => 'install', 'install_info' => $installInfo, 'e2e' => 1], JSON_UNESCAPED_UNICODE)]);
}

$pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type) VALUES (?, 'admin_created', ?, 'system')")
    ->execute([$orderId, json_encode(['source' => 'e2e-order-test'], JSON_UNESCAPED_UNICODE)]);
$pdo->commit();
ok("주문 생성: order_number={$orderNumber}, order_id={$orderId}");

// ─── 4. 프로비저닝 실행 (run-order-provision.php) ──────────────
step("4. 프로비저닝 실행");
$runScript = BASE_PATH . '/scripts/run-order-provision.php';
if (!is_file($runScript)) { bad("runner 스크립트 없음"); cleanup(); exit(1); }
$cmd = sprintf('/usr/bin/php8.3 %s --order=%s 2>&1', escapeshellarg($runScript), escapeshellarg($orderNumber));
$output = []; $rc = 0;
exec($cmd, $output, $rc);
foreach ($output as $line) info($line);
if ($rc !== 0) { bad("runner 종료 코드 {$rc}"); }
else ok("runner 정상 종료");

// ─── 5. 자원 검증 ─────────────────────────────────────────────
step("5. 자원 검증");
$username = 'vos_' . preg_replace('/[^A-Za-z0-9]/', '', $orderNumber);
$home = '/var/www/customers/' . $orderNumber;

// nginx vhost
$vhost = '/etc/nginx/sites-enabled/' . $DOMAIN . '.conf';
file_exists($vhost) ? ok("nginx vhost 활성: {$vhost}") : bad("nginx vhost 없음: {$vhost}");

// Linux user
$idOut = @shell_exec('id ' . escapeshellarg($username) . ' 2>&1');
strpos((string)$idOut, 'uid=') === 0 ? ok("Linux user 존재: {$username}") : bad("Linux user 없음: {$username}");

// 디렉토리
is_dir($home) ? ok("홈 디렉토리: {$home}") : bad("홈 디렉토리 없음: {$home}");
is_dir($home . '/public_html') ? ok("docroot 존재") : bad("docroot 없음");

// MySQL DB
try {
    $admin = new PDO('mysql:host=127.0.0.1;charset=utf8mb4', $_ENV['HOSTING_DB_ADMIN_USER'], $_ENV['HOSTING_DB_ADMIN_PASS'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    $st = $admin->query("SHOW DATABASES LIKE '" . addslashes($username) . "'");
    $st->fetch() ? ok("MySQL DB 존재: {$username}") : bad("MySQL DB 없음");
} catch (\Throwable $e) { warning("MySQL 검증 skip: " . $e->getMessage()); }

// SSL 인증서
$sslOut = @shell_exec('sudo /usr/bin/certbot certificates --cert-name ' . escapeshellarg($DOMAIN) . ' 2>&1');
preg_match('/VALID:\s*(\d+)\s*days/', (string)$sslOut, $m) ? ok("SSL 발급됨 ({$m[1]}일 남음)") : bad("SSL 인증서 없음");

// Cloudflare DNS
$cfToken = $_ENV['CLOUDFLARE_API_TOKEN'] ?? '';
if ($cfToken && preg_match('/\.([\w.-]+)$/', $DOMAIN, $m)) {
    $zoneName = '21ces.com'; // 단순화 — 무료 서브도메인 zone
    $zoneInfo = @file_get_contents("https://api.cloudflare.com/client/v4/zones?name={$zoneName}", false, stream_context_create([
        'http' => ['header' => "Authorization: Bearer {$cfToken}"]
    ]));
    $zoneId = json_decode((string)$zoneInfo, true)['result'][0]['id'] ?? null;
    if ($zoneId) {
        $rec = @file_get_contents("https://api.cloudflare.com/client/v4/zones/{$zoneId}/dns_records?name={$DOMAIN}", false, stream_context_create([
            'http' => ['header' => "Authorization: Bearer {$cfToken}"]
        ]));
        $count = count(json_decode((string)$rec, true)['result'] ?? []);
        $count > 0 ? ok("Cloudflare DNS 레코드 {$count}개") : bad("Cloudflare DNS 없음");
    }
}

// 외부 접속
$httpOut = @shell_exec("curl -sI -m 10 https://{$DOMAIN}/ 2>&1 | head -1");
strpos((string)$httpOut, '200') !== false ? ok("HTTPS 응답 200") : warning("HTTPS 응답: " . trim((string)$httpOut));

// VosCMS 설치 검증 (option)
if (!$NO_INST) {
    $instSt = $pdo->prepare("SELECT JSON_EXTRACT(metadata, '$.install_completed_at') FROM {$prefix}subscriptions WHERE order_id = ? AND type = 'addon' AND JSON_EXTRACT(metadata, '$.addon_id') = '\"install\"' LIMIT 1");
    $instSt->execute([$orderId]);
    $instAt = $instSt->fetchColumn();
    $instAt && $instAt !== 'null' ? ok("VosCMS 자동 설치 완료") : warning("VosCMS 설치 미완료 (수동 트리거 필요할 수 있음)");
}

// metadata.server.* 체크
$hMeta = $pdo->query("SELECT metadata FROM {$prefix}subscriptions WHERE order_id={$orderId} AND type='hosting' LIMIT 1")->fetchColumn();
$hMetaArr = json_decode((string)$hMeta, true) ?: [];
$svr = $hMetaArr['server'] ?? [];
foreach (['db', 'env', 'host', 'home', 'docroot', 'vhost', 'fpm_pool', 'username'] as $key) {
    !empty($svr[$key]) ? ok("server.{$key} 자동 채움") : bad("server.{$key} 누락");
}

// ─── 6. 정리 (디프로비저닝 + DB 삭제) ────────────────────────────
if ($KEEP) {
    step("6. 정리 SKIP (--keep). 수동 정리 필요:");
    info("sudo -u www-data php8.3 -r 'require \"/var/www/voscms-com/vendor/autoload.php\"; \$pdo=new PDO(...); \\RzxLib\\Core\\Hosting\\HostingProvisioner(\$pdo)->deprovision(\"{$orderNumber}\",\"{$DOMAIN}\");'");
} else {
    step("6. 디프로비저닝 + DB 정리");
    cleanup();
    // 정리 후 재검증
    !file_exists($vhost) ? ok("nginx vhost 삭제됨") : bad("nginx vhost 잔재");
    @shell_exec('id ' . escapeshellarg($username) . ' 2>&1') === null || strpos((string)@shell_exec('id ' . escapeshellarg($username) . ' 2>&1'), 'no such user') !== false || strpos((string)@shell_exec('id ' . escapeshellarg($username) . ' 2>&1'), '없습니다') !== false
        ? ok("Linux user 삭제됨") : bad("Linux user 잔재");
    !is_dir($home) ? ok("홈 디렉토리 삭제됨") : bad("홈 디렉토리 잔재");
    $stExists = $pdo->prepare("SELECT id FROM {$prefix}orders WHERE order_number = ?");
    $stExists->execute([$orderNumber]);
    !$stExists->fetchColumn() ? ok("DB orders row 삭제됨") : bad("DB orders row 잔재");
}

// ─── 결과 ─────────────────────────────────────────────────────
echo "\n\033[1m=== 결과 ===\033[0m\n";
echo "  PASS: \033[32m{$pass}\033[0m\n";
echo "  FAIL: \033[31m{$fail}\033[0m\n";
echo "  WARN: \033[33m{$warn}\033[0m\n\n";
exit($fail > 0 ? 1 : 0);

// ─── helper: cleanup ──────────────────────────────────────────
function cleanup() {
    global $pdo, $prefix, $orderNumber, $orderId, $DOMAIN;
    try {
        $prov = new \RzxLib\Core\Hosting\HostingProvisioner($pdo);
        $prov->deprovision($orderNumber, $DOMAIN);
    } catch (\Throwable $e) { /* silent */ }
    try {
        $pdo->prepare("DELETE FROM {$prefix}payments WHERE order_id = ?")->execute([$orderNumber]);
        $pdo->prepare("DELETE FROM {$prefix}order_logs WHERE order_id = ?")->execute([$orderId]);
        $pdo->prepare("DELETE FROM {$prefix}subscriptions WHERE order_id = ?")->execute([$orderId]);
        $pdo->prepare("DELETE FROM {$prefix}orders WHERE id = ?")->execute([$orderId]);
    } catch (\Throwable $e) { /* silent */ }
}
