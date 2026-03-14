<?php
/**
 * RezlyX Data Import Script
 * Imports test data from local DB dump to production server.
 *
 * Usage: Upload this file + migrate_data.sql to /database/ on server,
 *        then access via browser: https://rezlyx.com/database/import_data.php
 *
 * IMPORTANT: Delete both files after import!
 */

// Security: simple token check
$token = $_GET['token'] ?? '';
if ($token !== 'rezlyx2026migrate') {
    die('Access denied. Use ?token=rezlyx2026migrate');
}

// Load .env for DB credentials
$envFile = dirname(__DIR__) . '/.env';
if (!file_exists($envFile)) {
    die('ERROR: .env file not found. Is RezlyX installed?');
}

$env = [];
foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with($line, '#')) continue;
    if (!str_contains($line, '=')) continue;
    [$key, $value] = explode('=', $line, 2);
    $env[trim($key)] = trim(trim($value), '"\'');
}

$host = $env['DB_HOST'] ?? '127.0.0.1';
$port = $env['DB_PORT'] ?? '3306';
$dbname = $env['DB_DATABASE'] ?? '';
$user = $env['DB_USERNAME'] ?? '';
$pass = $env['DB_PASSWORD'] ?? '';

if (!$dbname || !$user) {
    die('ERROR: DB credentials not found in .env');
}

// Connect
try {
    $pdo = new PDO(
        "mysql:host={$host};port={$port};dbname={$dbname};charset=utf8mb4",
        $user, $pass,
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('DB Connection failed: ' . $e->getMessage());
}

// Read SQL file
$sqlFile = __DIR__ . '/migrate_data.sql';
if (!file_exists($sqlFile)) {
    die('ERROR: migrate_data.sql not found');
}

$sql = file_get_contents($sqlFile);

echo "<pre>\n";
echo "=== RezlyX Data Import ===\n\n";
echo "Database: {$dbname}\n";
echo "SQL file: " . number_format(strlen($sql)) . " bytes\n\n";

// Preserve server-specific settings
$preserveKeys = ['site_url', 'site_name', 'admin_path', 'installed_at', 'version', 'locale', 'timezone'];
$preserved = [];
$stmt = $pdo->prepare("SELECT `key`, `value` FROM rzx_settings WHERE `key` IN (" . implode(',', array_fill(0, count($preserveKeys), '?')) . ")");
$stmt->execute($preserveKeys);
foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $row) {
    $preserved[$row['key']] = $row['value'];
}
echo "Preserved " . count($preserved) . " server settings\n";

// Add missing columns if needed (schema upgrade)
$schemaUpgrades = [
    "ALTER TABLE `rzx_users` ADD COLUMN `furigana` VARCHAR(100) NULL COMMENT '후리가나' AFTER `name`",
    "ALTER TABLE `rzx_users` ADD COLUMN `company` VARCHAR(200) NULL COMMENT '회사/소속' AFTER `gender`",
    "ALTER TABLE `rzx_users` ADD COLUMN `blog` VARCHAR(500) NULL COMMENT '블로그/웹사이트' AFTER `company`",
    "ALTER TABLE `rzx_users` ADD COLUMN `privacy_settings` JSON NULL COMMENT '개인정보 공개 설정' AFTER `blog`",
    "ALTER TABLE `rzx_users` ADD COLUMN `withdraw_reason` TEXT NULL COMMENT '탈퇴 사유' AFTER `privacy_settings`",
    "ALTER TABLE `rzx_users` ADD COLUMN `deleted_at` TIMESTAMP NULL COMMENT '소프트 삭제' AFTER `updated_at`",
    "ALTER TABLE `rzx_reservations` ADD COLUMN `staff_id` INT UNSIGNED NULL COMMENT '담당 스태프' AFTER `service_id`",
    "ALTER TABLE `rzx_reservations` ADD COLUMN `designation_fee` DECIMAL(12,2) DEFAULT 0 COMMENT '지명료' AFTER `points_used`",
];
foreach ($schemaUpgrades as $alter) {
    try { $pdo->exec($alter); } catch (PDOException $e) { /* column already exists */ }
}
echo "Schema upgrade checked\n";

// Execute SQL
$pdo->exec("SET FOREIGN_KEY_CHECKS = 0");

$statements = array_filter(array_map('trim', explode(";\n", $sql)));
$success = 0;
$errors = 0;

foreach ($statements as $stmt) {
    if (empty($stmt) || $stmt === ';') continue;
    try {
        $pdo->exec($stmt);
        $success++;
    } catch (PDOException $e) {
        $errors++;
        echo "ERROR: " . $e->getMessage() . "\n";
        echo "  SQL: " . substr($stmt, 0, 200) . "...\n\n";
    }
}

$pdo->exec("SET FOREIGN_KEY_CHECKS = 1");

// Restore server-specific settings
$restoreStmt = $pdo->prepare("REPLACE INTO rzx_settings (`key`, `value`) VALUES (?, ?)");
foreach ($preserved as $key => $value) {
    $restoreStmt->execute([$key, $value]);
}
echo "Restored " . count($preserved) . " server settings\n\n";

echo "=== Result ===\n";
echo "Success: {$success} statements\n";
echo "Errors: {$errors}\n\n";

if ($errors === 0) {
    echo "*** DATA IMPORT COMPLETE! ***\n\n";
    echo "IMPORTANT: Delete these files now:\n";
    echo "  - /database/import_data.php\n";
    echo "  - /database/migrate_data.sql\n";
} else {
    echo "*** COMPLETED WITH ERRORS ***\n";
}

echo "</pre>\n";
