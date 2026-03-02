<?php
/**
 * RezlyX - Modern Reservation System
 *
 * @package RezlyX
 * @version 1.0.0
 */

define('REZLYX_START', microtime(true));

/*
|--------------------------------------------------------------------------
| Check PHP Version
|--------------------------------------------------------------------------
*/
if (version_compare(PHP_VERSION, '8.0.0', '<')) {
    die('RezlyX requires PHP 8.0 or higher. Current version: ' . PHP_VERSION);
}

/*
|--------------------------------------------------------------------------
| Start Session
|--------------------------------------------------------------------------
*/
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

/*
|--------------------------------------------------------------------------
| Define Base Path
|--------------------------------------------------------------------------
*/
define('BASE_PATH', dirname(__DIR__));

/*
|--------------------------------------------------------------------------
| Check Installation
|--------------------------------------------------------------------------
*/
if (!file_exists(BASE_PATH . '/install/installed.lock') && !file_exists(BASE_PATH . '/.env')) {
    $installPath = dirname($_SERVER['SCRIPT_NAME']) . '/../install/';
    header('Location: ' . $installPath);
    exit;
}

/*
|--------------------------------------------------------------------------
| Register The Auto Loader
|--------------------------------------------------------------------------
*/
require BASE_PATH . '/vendor/autoload.php';

/*
|--------------------------------------------------------------------------
| Load Environment
|--------------------------------------------------------------------------
*/
$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

/*
|--------------------------------------------------------------------------
| Development Mode - Simple Routing
|--------------------------------------------------------------------------
*/
/*
|--------------------------------------------------------------------------
| Initialize Translator
|--------------------------------------------------------------------------
*/
require_once BASE_PATH . '/rzxlib/Core/I18n/Translator.php';
use RzxLib\Core\I18n\Translator;

$langPath = BASE_PATH . '/resources/lang';
Translator::init($langPath);

// URL 파라미터로 언어 변경 처리
if (isset($_GET['lang'])) {
    $newLang = $_GET['lang'];
    Translator::setLocale($newLang);

    // 현재 URL에서 lang 파라미터 제거 후 리다이렉트
    $currentUrl = strtok($_SERVER['REQUEST_URI'], '?');
    $params = $_GET;
    unset($params['lang']);
    $queryString = http_build_query($params);
    $redirectUrl = $currentUrl . ($queryString ? '?' . $queryString : '');

    header('Location: ' . $redirectUrl);
    exit;
}

$config = [
    'app_name' => $_ENV['APP_NAME'] ?? 'RezlyX',
    'app_url' => $_ENV['APP_URL'] ?? 'http://localhost',
    'debug' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
    'locale' => current_locale(),
    'admin_path' => $_ENV['ADMIN_PATH'] ?? 'admin',
];

/*
|--------------------------------------------------------------------------
| Load Admin Path from Database (if available)
|--------------------------------------------------------------------------
*/
try {
    $pdo = new PDO(
        'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx'),
        $_ENV['DB_USERNAME'] ?? 'root',
        $_ENV['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $stmt = $pdo->prepare("SELECT value FROM rzx_settings WHERE rzx_settings.key = ?");
    $stmt->execute(['admin_path']);
    $dbAdminPath = $stmt->fetchColumn();

    if ($dbAdminPath) {
        $config['admin_path'] = $dbAdminPath;
    }
} catch (PDOException $e) {
    // Use default from .env if database is not available
    if ($config['debug']) {
        error_log('RezlyX DB Error: ' . $e->getMessage());
    }
}

// Parse request
$requestUri = $_SERVER['REQUEST_URI'] ?? '/';

// Get base path from APP_URL
$appUrl = $_ENV['APP_URL'] ?? 'http://localhost';
$parsedAppUrl = parse_url($appUrl);
$basePath = rtrim($parsedAppUrl['path'] ?? '', '/');

// Remove base path and query string
$path = parse_url($requestUri, PHP_URL_PATH);
if (!empty($basePath) && str_starts_with($path, $basePath)) {
    $path = substr($path, strlen($basePath));
}
$path = trim($path, '/');

// Handle /public prefix (for backward compatibility)
if ($path === 'public' || str_starts_with($path, 'public/')) {
    $path = substr($path, strlen('public'));
    $path = trim($path, '/');
}

// Debug output (remove in production)
if ($config['debug']) {
    error_log("RezlyX Debug - Path: '$path', AdminPath: '{$config['admin_path']}'");
}

// Route to appropriate handler
if (empty($path) || $path === 'index.php') {
    include BASE_PATH . '/resources/views/customer/home.php';
} elseif ($path === $config['admin_path'] || str_starts_with($path, $config['admin_path'] . '/')) {
    $adminRoute = substr($path, strlen($config['admin_path']));
    $adminRoute = trim($adminRoute, '/') ?: 'dashboard';

    $adminView = BASE_PATH . '/resources/views/admin/' . $adminRoute . '.php';
    if (file_exists($adminView)) {
        include $adminView;
    } else {
        include BASE_PATH . '/resources/views/admin/dashboard.php';
    }
} else {
    $customerView = BASE_PATH . '/resources/views/customer/' . $path . '.php';
    if (file_exists($customerView)) {
        include $customerView;
    } else {
        http_response_code(404);
        include BASE_PATH . '/resources/views/customer/404.php';
    }
}
