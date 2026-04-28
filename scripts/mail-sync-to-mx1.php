<?php
/**
 * voscms_prod / voscms_dev 의 mail_accounts → mx1.voscms.com 의 mail_lookup DB 동기화.
 *
 * 흐름:
 *   1. 지정 DB 의 subscriptions 에서 type='mail' OR type='hosting' 의 mail_accounts JSON 추출
 *   2. SSH 로 mx1 에 접속 → mail_lookup DB 의 virtual_domains / virtual_users 갱신
 *   3. 평탄화 후 REPLACE INTO 사용 (멱등성 보장)
 *
 * 사용법:
 *   php mail-sync-to-mx1.php voscms_prod          # voscms_prod 동기화
 *   php mail-sync-to-mx1.php voscms_dev           # voscms_dev 동기화
 *   php mail-sync-to-mx1.php voscms_prod --dry    # 시뮬레이션 (mx1 변경 없음)
 *
 * cron 등록 예 (5분 간격):
 *   "5분마다 실행" /usr/bin/php8.3 /var/www/voscms/scripts/mail-sync-to-mx1.php voscms_prod
 */

$dbName = $argv[1] ?? null;
$dryRun = in_array('--dry', $argv, true);

if (!in_array($dbName, ['voscms_prod', 'voscms_dev'], true)) {
    fwrite(STDERR, "Usage: php mail-sync-to-mx1.php voscms_prod|voscms_dev [--dry]\n");
    exit(1);
}

// .env 로드
$envFile = __DIR__ . '/../.env';
$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    $line = trim($line);
    if ($line === '' || $line[0] === '#') continue;
    if (strpos($line, '=') !== false) {
        [$k, $v] = explode('=', $line, 2);
        $env[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
    }
}

$dbUser = $env['DB_USERNAME'] ?? null;
$dbPass = $env['DB_PASSWORD'] ?? null;
$mx1Host = $env['MAIL_SERVER_HOST'] ?? '133.117.72.149';
$mx1User = $env['MAIL_SERVER_USER'] ?? 'root';
$mx1SshKey = $env['MAIL_SERVER_SSH_KEY'] ?? '/home/thevos/.ssh/id_rsa';
$mx1MailsyncPass = $env['MAIL_SERVER_MAILSYNC_PASS'] ?? null;

if (!$mx1MailsyncPass) {
    fwrite(STDERR, "MAIL_SERVER_MAILSYNC_PASS 가 .env 에 설정되지 않음\n");
    exit(1);
}

[$ts] = [date('Y-m-d H:i:s')];
echo "[$ts] === mail-sync 시작 (DB: $dbName, dry=" . ($dryRun ? 'yes' : 'no') . ") ===\n";

// 1. voscms_* DB 조회
$pdo = new PDO("mysql:host=127.0.0.1;dbname=$dbName;charset=utf8mb4", $dbUser, $dbPass, [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
]);

$stmt = $pdo->query("
    SELECT s.id AS sub_id, s.order_id, s.type, s.label, s.metadata,
           o.domain AS order_domain, s.status
    FROM rzx_subscriptions s
    LEFT JOIN rzx_orders o ON o.id = s.order_id
    WHERE s.status = 'active'
      AND JSON_EXTRACT(s.metadata, '$.mail_accounts') IS NOT NULL
");

$domains = [];
$users = [];
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $meta = json_decode($row['metadata'], true);
    if (!isset($meta['mail_accounts']) || !is_array($meta['mail_accounts'])) continue;

    foreach ($meta['mail_accounts'] as $acc) {
        $email = strtolower(trim($acc['address'] ?? ''));
        $pw    = $acc['password'] ?? '';
        if ($email === '' || $pw === '') continue;
        if (strpos($email, '@') === false) continue;
        // SHA512-CRYPT hash 만 받음 (이전 enc: 는 마이그레이션 후 없어야 함)
        if (strpos($pw, '{SHA512-CRYPT}') !== 0) continue;

        $domain = substr(strrchr($email, '@'), 1);
        $domains[$domain] = true;
        $users[$email] = ['domain' => $domain, 'password' => $pw];
    }
}

echo "  소스 도메인: " . count($domains) . "\n";
echo "  소스 계정:   " . count($users) . "\n";

if ($dryRun) {
    echo "  (dry-run — mx1 변경 없음. 처음 5건 미리보기:)\n";
    foreach (array_slice($users, 0, 5, true) as $email => $u) {
        echo "    $email  ({$u['domain']})  pw=" . substr($u['password'], 0, 24) . "...\n";
    }
    exit(0);
}

// 2. mx1 으로 SQL 일괄 push (SSH 통해)
//    - REPLACE INTO 로 멱등성 (계정 변경 시 update, 신규 시 insert)
//    - 미사용 계정은 별도로 SELECT 해서 비교 후 DELETE
$sqlBuild = "USE mail_lookup;\n";
$sqlBuild .= "BEGIN;\n";

// 도메인 upsert
foreach (array_keys($domains) as $d) {
    $dEsc = addslashes($d);
    $sqlBuild .= "INSERT INTO virtual_domains (name) VALUES ('$dEsc') ON DUPLICATE KEY UPDATE active=1;\n";
}
// 계정 upsert
foreach ($users as $email => $u) {
    $eEsc = addslashes($email);
    $dEsc = addslashes($u['domain']);
    $pEsc = addslashes($u['password']);
    $sqlBuild .= "INSERT INTO virtual_users (domain_id, email, password)\n";
    $sqlBuild .= "  SELECT d.id, '$eEsc', '$pEsc' FROM virtual_domains d WHERE d.name='$dEsc'\n";
    $sqlBuild .= "  ON DUPLICATE KEY UPDATE password=VALUES(password), active=1;\n";
}

// 소스에 없는 계정 비활성 (즉시 삭제 대신 active=0 — 안전)
// 단, 보호 도메인 (PROTECTED_DOMAINS) 의 계정은 건드리지 않음 — mx1 자체 운영용
$protectedDomains = ['voscms.com'];
$emailList = empty($users) ? "''" : implode(',', array_map(fn($e) => "'" . addslashes($e) . "'", array_keys($users)));
$protectedList = implode(',', array_map(fn($d) => "'" . addslashes($d) . "'", $protectedDomains));
// active=1 → 0 으로 변하는 행만 UPDATE (updated_at 불필요한 갱신 방지 — 3일 카운트 정확)
$sqlBuild .= "UPDATE virtual_users SET active=0\n";
$sqlBuild .= "  WHERE active=1\n";
$sqlBuild .= "    AND email NOT IN ($emailList)\n";
$sqlBuild .= "    AND domain_id NOT IN (SELECT id FROM (SELECT id FROM virtual_domains WHERE name IN ($protectedList)) AS sub);\n";

$sqlBuild .= "COMMIT;\n";

// SSH 로 실행
$tmpFile = tempnam(sys_get_temp_dir(), 'mailsync_');
file_put_contents($tmpFile, $sqlBuild);

$cmd = sprintf(
    "ssh -o StrictHostKeyChecking=accept-new -o ConnectTimeout=10 -i %s %s@%s 'mariadb -u mailsync -p\"%s\"' < %s 2>&1",
    escapeshellarg($mx1SshKey),
    escapeshellarg($mx1User),
    escapeshellarg($mx1Host),
    addslashes($mx1MailsyncPass),
    escapeshellarg($tmpFile)
);

$output = shell_exec($cmd);
unlink($tmpFile);

if ($output && stripos($output, 'error') !== false) {
    echo "  ❌ mx1 적용 실패:\n$output\n";
    exit(1);
}

// 3. 검증 — mx1 의 현재 상태 조회
$verifyCmd = sprintf(
    "ssh -o StrictHostKeyChecking=accept-new -i %s %s@%s 'mariadb -u mailsync -p\"%s\" mail_lookup -sN -e \"SELECT COUNT(*) AS d FROM virtual_domains WHERE active=1; SELECT COUNT(*) AS u FROM virtual_users WHERE active=1;\"' 2>&1",
    escapeshellarg($mx1SshKey),
    escapeshellarg($mx1User),
    escapeshellarg($mx1Host),
    addslashes($mx1MailsyncPass)
);
$verifyOut = trim(shell_exec($verifyCmd));
echo "  ✓ mx1 적용 후: $verifyOut (도메인/계정 활성수)\n";

[$ts2] = [date('Y-m-d H:i:s')];
echo "[$ts2] === mail-sync 종료 ===\n\n";
