<?php
/**
 * localhost URL → 프로덕션 URL 일괄 변환
 * 사용 후 반드시 삭제!
 */
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (strpos($line, '=') !== false) {
            [$k, $v] = explode('=', $line, 2);
            $_ENV[trim($k)] = trim($v);
        }
    }
}

$pdo = new PDO(
    'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx'),
    $_ENV['DB_USERNAME'] ?? 'root', $_ENV['DB_PASSWORD'] ?? '',
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$oldUrl = 'http://localhost/rezlyx';
$newUrl = 'https://rezlyx.com';
$count = 0;

// staff avatar/banner
$stmt = $pdo->prepare("UPDATE {$prefix}staff SET avatar = REPLACE(avatar, ?, ?) WHERE avatar LIKE ?");
$stmt->execute([$oldUrl, $newUrl, $oldUrl . '%']);
$count += $stmt->rowCount();

try {
    $stmt = $pdo->prepare("UPDATE {$prefix}staff SET banner = REPLACE(banner, ?, ?) WHERE banner LIKE ?");
    $stmt->execute([$oldUrl, $newUrl, $oldUrl . '%']);
    $count += $stmt->rowCount();
} catch (Exception $e) { /* banner 컬럼 없을 수 있음 */ }

// users profile_image
$stmt = $pdo->prepare("UPDATE {$prefix}users SET profile_image = REPLACE(profile_image, ?, ?) WHERE profile_image LIKE ?");
$stmt->execute([$oldUrl, $newUrl, $oldUrl . '%']);
$count += $stmt->rowCount();

// settings
$stmt = $pdo->prepare("UPDATE {$prefix}settings SET `value` = REPLACE(`value`, ?, ?) WHERE `value` LIKE ?");
$stmt->execute([$oldUrl, $newUrl, '%' . $oldUrl . '%']);
$count += $stmt->rowCount();

// board_posts content
$stmt = $pdo->prepare("UPDATE {$prefix}board_posts SET content = REPLACE(content, ?, ?) WHERE content LIKE ?");
$stmt->execute([$oldUrl, $newUrl, '%' . $oldUrl . '%']);
$count += $stmt->rowCount();

echo "Updated {$count} rows. Old: {$oldUrl} → New: {$newUrl}";
echo "\n<br><b style='color:red'>이 파일을 반드시 삭제하세요!</b>";
