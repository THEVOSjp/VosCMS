<?php
/**
 * 메일 계정 비밀번호 마이그레이션
 * 기존 encrypt() (enc:...) → SHA512-CRYPT 해시 ({SHA512-CRYPT}$6$...)
 *
 * 사용법:
 *   php migrate-mail-passwords.php voscms_dev dry-run    # 시뮬레이션
 *   php migrate-mail-passwords.php voscms_dev apply      # 실제 적용
 *   php migrate-mail-passwords.php voscms_prod apply
 */

require __DIR__ . '/../vendor/autoload.php';
require_once __DIR__ . '/../rzxlib/Core/Helpers/Encryption.php';
require_once __DIR__ . '/../rzxlib/Core/Helpers/functions.php';

$db   = $argv[1] ?? null;
$mode = $argv[2] ?? 'dry-run';

if (!in_array($db, ['voscms_dev', 'voscms_prod', 'voscms_test'], true)) {
    fwrite(STDERR, "Usage: php migrate-mail-passwords.php voscms_dev|voscms_prod|voscms_test [dry-run|apply]\n");
    exit(1);
}
if (!in_array($mode, ['dry-run', 'apply'], true)) {
    fwrite(STDERR, "Mode must be 'dry-run' or 'apply'\n");
    exit(1);
}

// .env 로드 (단순 파서 — parse_ini_file 은 일부 특수문자 깨짐)
$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') !== false) {
            [$k, $v] = explode('=', $line, 2);
            $k = trim($k);
            $v = trim($v, " \t\n\r\0\x0B\"'");
            $_ENV[$k] = $v;
            putenv("$k=$v");
        }
    }
}
$dbUser = $_ENV['DB_USERNAME'] ?? null;
$dbPass = $_ENV['DB_PASSWORD'] ?? null;
$appKey = $_ENV['APP_KEY'] ?? null;
if (!$dbUser || !$dbPass) {
    fwrite(STDERR, "DB credentials missing in .env\n");
    exit(1);
}
if (!$appKey) {
    fwrite(STDERR, "APP_KEY missing in .env (encrypt/decrypt 동작 안 함)\n");
    exit(1);
}

try {
    $pdo = new PDO("mysql:host=127.0.0.1;dbname=$db;charset=utf8mb4", $dbUser, $dbPass, [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);
} catch (PDOException $e) {
    fwrite(STDERR, "DB connection failed: " . $e->getMessage() . "\n");
    exit(1);
}

echo "=== 메일 비밀번호 마이그레이션 (DB: $db, mode: $mode) ===\n\n";

$stmt = $pdo->query("
    SELECT id, type, label, metadata
    FROM rzx_subscriptions
    WHERE JSON_EXTRACT(metadata, '$.mail_accounts') IS NOT NULL
    ORDER BY id
");

$totalSubs = 0; $totalAccounts = 0; $migrated = 0; $skipped = 0; $failed = 0;

while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $totalSubs++;
    $meta = json_decode($row['metadata'], true);
    if (!is_array($meta) || !isset($meta['mail_accounts'])) continue;

    $accounts = $meta['mail_accounts'];
    if (!is_array($accounts) || count($accounts) === 0) continue;

    $changed = false;
    foreach ($accounts as $i => $acc) {
        $totalAccounts++;
        $addr = $acc['address'] ?? '?';
        $pw   = $acc['password'] ?? '';

        if ($pw === '') {
            echo "  [SKIP-empty] sub:{$row['id']} type:{$row['type']} <$addr>\n";
            $skipped++;
            continue;
        }
        if (str_starts_with($pw, '{SHA512-CRYPT}')) {
            echo "  [SKIP-hash]  sub:{$row['id']} type:{$row['type']} <$addr> (이미 hash)\n";
            $skipped++;
            continue;
        }
        if (!str_starts_with($pw, 'enc:')) {
            echo "  [WARN-unknown] sub:{$row['id']} <$addr> prefix='" . substr($pw, 0, 10) . "...'\n";
            $skipped++;
            continue;
        }

        // enc: → 복호화
        $plain = decrypt($pw);
        if ($plain === null || $plain === '' || $plain === false) {
            echo "  [FAIL-decrypt] sub:{$row['id']} <$addr>\n";
            $failed++;
            continue;
        }

        $newHash = mail_password_hash($plain);
        $accounts[$i]['password'] = $newHash;
        $changed = true;
        $migrated++;
        echo "  [OK] sub:{$row['id']} <$addr> enc → SHA512-CRYPT (len=" . strlen($plain) . ")\n";
    }

    if ($changed && $mode === 'apply') {
        $meta['mail_accounts'] = $accounts;
        $upd = $pdo->prepare("UPDATE rzx_subscriptions SET metadata = ? WHERE id = ?");
        $upd->execute([json_encode($meta, JSON_UNESCAPED_UNICODE), $row['id']]);
    }
}

echo "\n=== 요약 ===\n";
echo sprintf("  subscriptions:  %d\n", $totalSubs);
echo sprintf("  accounts total: %d\n", $totalAccounts);
echo sprintf("  migrated:       %d\n", $migrated);
echo sprintf("  skipped:        %d\n", $skipped);
echo sprintf("  failed:         %d\n", $failed);
if ($mode === 'dry-run') {
    echo "\n  (dry-run — 실제 DB 변경 없음. 'apply' 로 적용)\n";
}
