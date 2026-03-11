<?php
/**
 * Installation Process Handler
 */

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'database':
        processDatabaseStep();
        break;

    case 'admin':
        processAdminStep();
        break;

    default:
        header('Location: ?step=welcome');
        exit;
}

/**
 * Process database configuration step
 */
function processDatabaseStep(): void
{
    $data = [
        'db_host' => trim($_POST['db_host'] ?? '127.0.0.1'),
        'db_port' => trim($_POST['db_port'] ?? '3306'),
        'db_name' => trim($_POST['db_name'] ?? ''),
        'db_user' => trim($_POST['db_user'] ?? ''),
        'db_pass' => $_POST['db_pass'] ?? '',
        'db_prefix' => trim($_POST['db_prefix'] ?? 'rzx_'),
    ];

    $_SESSION['install_db'] = $data;

    // Validate required fields
    if (empty($data['db_host']) || empty($data['db_name']) || empty($data['db_user'])) {
        $_SESSION['install_error'] = '필수 항목을 모두 입력해주세요.';
        header('Location: ?step=database');
        exit;
    }

    // Test database connection
    try {
        $dsn = "mysql:host={$data['db_host']};port={$data['db_port']};charset=utf8mb4";
        $pdo = new PDO($dsn, $data['db_user'], $data['db_pass'], [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        ]);

        // Check if database exists, create if not
        $pdo->exec("CREATE DATABASE IF NOT EXISTS `{$data['db_name']}` CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

        $_SESSION['install_db_success'] = true;
        header('Location: ?step=admin');
        exit;

    } catch (PDOException $e) {
        $_SESSION['install_error'] = '데이터베이스 연결 실패: ' . $e->getMessage();
        header('Location: ?step=database');
        exit;
    }
}

/**
 * Process admin account step
 */
function processAdminStep(): void
{
    $data = [
        'site_name' => trim($_POST['site_name'] ?? 'RezlyX'),
        'site_url' => rtrim(trim($_POST['site_url'] ?? ''), '/'),
        'admin_path' => trim($_POST['admin_path'] ?? 'admin'),
        'admin_email' => trim($_POST['admin_email'] ?? ''),
        'admin_name' => trim($_POST['admin_name'] ?? ''),
        'admin_password' => $_POST['admin_password'] ?? '',
        'admin_password_confirm' => $_POST['admin_password_confirm'] ?? '',
    ];

    $_SESSION['install_admin'] = $data;

    // Validate required fields
    if (empty($data['site_name']) || empty($data['site_url']) ||
        empty($data['admin_email']) || empty($data['admin_name']) || empty($data['admin_password'])) {
        $_SESSION['install_error'] = '필수 항목을 모두 입력해주세요.';
        header('Location: ?step=admin');
        exit;
    }

    // Validate email
    if (!filter_var($data['admin_email'], FILTER_VALIDATE_EMAIL)) {
        $_SESSION['install_error'] = '유효한 이메일 주소를 입력해주세요.';
        header('Location: ?step=admin');
        exit;
    }

    // Validate password
    if (strlen($data['admin_password']) < 8) {
        $_SESSION['install_error'] = '비밀번호는 8자 이상이어야 합니다.';
        header('Location: ?step=admin');
        exit;
    }

    if ($data['admin_password'] !== $data['admin_password_confirm']) {
        $_SESSION['install_error'] = '비밀번호가 일치하지 않습니다.';
        header('Location: ?step=admin');
        exit;
    }

    // Proceed with installation
    try {
        runInstallation($data);

        $_SESSION['admin_path'] = $data['admin_path'];
        $_SESSION['site_url'] = $data['site_url'];

        header('Location: ?step=complete');
        exit;

    } catch (Exception $e) {
        $_SESSION['install_error'] = '설치 중 오류 발생: ' . $e->getMessage();
        header('Location: ?step=admin');
        exit;
    }
}

/**
 * Run the actual installation
 */
function runInstallation(array $adminData): void
{
    $dbData = $_SESSION['install_db'] ?? [];

    // Generate secure keys
    $appKey = 'base64:' . base64_encode(random_bytes(32));
    $jwtSecret = bin2hex(random_bytes(32));

    // Create .env file
    $envContent = <<<ENV
# RezlyX Environment Configuration
# Generated: {$adminData['site_name']} - Installation

APP_NAME="{$adminData['site_name']}"
APP_ENV=production
APP_DEBUG=false
APP_URL={$adminData['site_url']}
APP_TIMEZONE=Asia/Seoul
APP_LOCALE=ko
APP_KEY={$appKey}

ADMIN_PATH={$adminData['admin_path']}

DB_CONNECTION=mysql
DB_HOST={$dbData['db_host']}
DB_PORT={$dbData['db_port']}
DB_DATABASE={$dbData['db_name']}
DB_USERNAME={$dbData['db_user']}
DB_PASSWORD={$dbData['db_pass']}
DB_CHARSET=utf8mb4
DB_COLLATION=utf8mb4_unicode_ci
DB_PREFIX={$dbData['db_prefix']}

SESSION_DRIVER=file
SESSION_LIFETIME=120

JWT_SECRET={$jwtSecret}
JWT_TTL=60
JWT_REFRESH_TTL=20160

CACHE_DRIVER=file
CACHE_PREFIX=rzx_

MAIL_DRIVER=smtp
MAIL_HOST=smtp.mailtrap.io
MAIL_PORT=2525
MAIL_USERNAME=null
MAIL_PASSWORD=null
MAIL_FROM_ADDRESS=noreply@rezlyx.com
MAIL_FROM_NAME="\${APP_NAME}"

LOG_CHANNEL=daily
LOG_LEVEL=warning
LOG_DAYS=14

SUPPORTED_LOCALES=ko,en,ja
DEFAULT_LOCALE=ko
FALLBACK_LOCALE=en
ENV;

    file_put_contents(BASE_PATH . '/.env', $envContent);

    // Connect to database and run migrations
    $dsn = "mysql:host={$dbData['db_host']};port={$dbData['db_port']};dbname={$dbData['db_name']};charset=utf8mb4";
    $pdo = new PDO($dsn, $dbData['db_user'], $dbData['db_pass'], [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    ]);

    $prefix = $dbData['db_prefix'];

    // Create admins table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `{$prefix}admins` (
            `id` CHAR(36) NOT NULL,
            `email` VARCHAR(255) NOT NULL,
            `password` VARCHAR(255) NOT NULL,
            `name` VARCHAR(100) NOT NULL,
            `role` ENUM('master', 'manager', 'staff') DEFAULT 'manager',
            `status` ENUM('active', 'inactive') DEFAULT 'active',
            `last_login_at` TIMESTAMP NULL,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`id`),
            UNIQUE KEY `uk_email` (`email`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Create settings table
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `{$prefix}settings` (
            `key` VARCHAR(100) NOT NULL,
            `value` TEXT,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (`key`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    // Insert admin user
    $adminId = generateUUID();
    $hashedPassword = password_hash($adminData['admin_password'], PASSWORD_DEFAULT);

    $stmt = $pdo->prepare("
        INSERT INTO `{$prefix}admins` (`id`, `email`, `password`, `name`, `role`, `status`)
        VALUES (?, ?, ?, ?, 'master', 'active')
    ");
    $stmt->execute([$adminId, $adminData['admin_email'], $hashedPassword, $adminData['admin_name']]);

    // Insert default settings
    $settings = [
        'site_name' => $adminData['site_name'],
        'site_url' => $adminData['site_url'],
        'admin_path' => $adminData['admin_path'],
        'timezone' => 'Asia/Seoul',
        'locale' => 'ko',
        'installed_at' => date('Y-m-d H:i:s'),
        'version' => '1.0.0',
    ];

    $stmt = $pdo->prepare("INSERT INTO `{$prefix}settings` (`key`, `value`) VALUES (?, ?)");
    foreach ($settings as $key => $value) {
        $stmt->execute([$key, $value]);
    }

    // Seed default services & categories (beauty salon)
    seedDefaultServices($pdo, $prefix);

    // Create installation lock file
    file_put_contents(INSTALL_PATH . '/installed.lock', date('Y-m-d H:i:s'));
}

/**
 * Generate UUID v4
 */
function generateUUID(): string
{
    $data = random_bytes(16);
    $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
    $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
    return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
}

/**
 * Seed default beauty salon services & categories with 13 language translations.
 * Executes database/seed_services.php which reads .env for DB connection.
 */
function seedDefaultServices(PDO $pdo, string $prefix): void
{
    $seedFile = BASE_PATH . '/database/seed_services.php';
    if (!file_exists($seedFile)) return;

    try {
        // .env가 이미 생성된 상태이므로 seed 스크립트 직접 실행
        $phpBin = PHP_BINARY ?: 'php';
        $output = [];
        $exitCode = 0;
        exec("{$phpBin} " . escapeshellarg($seedFile) . " 2>&1", $output, $exitCode);

        if ($exitCode !== 0) {
            error_log('[RezlyX Install] Service seed error (exit=' . $exitCode . '): ' . implode("\n", $output));
        }
    } catch (\Exception $e) {
        // 시드 실패는 설치를 중단하지 않음
        error_log('[RezlyX Install] Service seed error: ' . $e->getMessage());
    }
}
