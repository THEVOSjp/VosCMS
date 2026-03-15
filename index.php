<?php
/**
 * RezlyX - Modern Reservation System
 *
 * @package RezlyX
 * @version 1.4.0
 */

define('REZLYX_START', microtime(true));
date_default_timezone_set('Asia/Seoul'); // 기본값, DB 설정 로드 후 재설정

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
define('BASE_PATH', __DIR__);

/*
|--------------------------------------------------------------------------
| Check Installation
|--------------------------------------------------------------------------
*/
if (!file_exists(BASE_PATH . '/install/installed.lock') && !file_exists(BASE_PATH . '/.env')) {
    $installPath = dirname($_SERVER['SCRIPT_NAME']) . '/install/';
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

$_appVersion = '1.0.0';
if (preg_match('/@version\s+(\d+\.\d+\.\d+)/', file_get_contents(__FILE__), $_vm)) {
    $_appVersion = $_vm[1];
}

$config = [
    'app_name' => $_ENV['APP_NAME'] ?? 'RezlyX',
    'app_url' => $_ENV['APP_URL'] ?? 'http://localhost',
    'app_version' => $_appVersion,
    'debug' => ($_ENV['APP_DEBUG'] ?? 'false') === 'true',
    'locale' => current_locale(),
    'admin_path' => $_ENV['ADMIN_PATH'] ?? 'admin',
];

/*
|--------------------------------------------------------------------------
| Load Settings from Database (if available)
|--------------------------------------------------------------------------
*/
$siteSettings = [];
try {
    $pdo = new PDO(
        'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx'),
        $_ENV['DB_USERNAME'] ?? 'root',
        $_ENV['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true]
    );

    // 모든 설정 불러오기
    $stmt = $pdo->query("SELECT `key`, `value` FROM rzx_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $siteSettings[$row['key']] = $row['value'];
    }

    // admin_path 설정
    if (!empty($siteSettings['admin_path'])) {
        $config['admin_path'] = $siteSettings['admin_path'];
    }

    // site_name 설정
    if (!empty($siteSettings['site_name'])) {
        $config['app_name'] = $siteSettings['site_name'];
    }

    // timezone 설정
    if (!empty($siteSettings['site_timezone'])) {
        date_default_timezone_set($siteSettings['site_timezone']);
    }
} catch (PDOException $e) {
    // Use default from .env if database is not available
    if ($config['debug']) {
        error_log('RezlyX DB Error: ' . $e->getMessage());
    }
}

// 자동 DB 마이그레이션 체크 (플래그 파일로 매 요청 방지)
if (isset($pdo)) {
    $migrationFlag = BASE_PATH . '/storage/.migration_checked';
    $codeVersion = null;
    if (preg_match('/@version\s+(\d+\.\d+\.\d+)/', file_get_contents(__FILE__), $_vm)) {
        $codeVersion = $_vm[1];
    }
    $flagValid = file_exists($migrationFlag) && (filemtime($migrationFlag) > time() - 3600)
        && trim(file_get_contents($migrationFlag)) === ($codeVersion ?? '');

    if (!$flagValid && $codeVersion) {
        try {
            require_once BASE_PATH . '/rzxlib/Core/Updater/DatabaseMigrator.php';
            $migrator = new \RzxLib\Core\Updater\DatabaseMigrator($pdo, BASE_PATH);
            if ($migrator->needsMigration($codeVersion)) {
                $migResult = $migrator->migrate($codeVersion);
                if (!$migResult['success']) {
                    error_log('RezlyX Auto-Migration failed: ' . implode(', ', $migResult['errors'] ?? []));
                }
            }
            @file_put_contents($migrationFlag, $codeVersion);
        } catch (\Exception $e) {
            error_log('RezlyX Migration check error: ' . $e->getMessage());
        }
    }
    unset($migrationFlag, $codeVersion, $flagValid, $migrator, $migResult, $_vm);
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

    // AdminAuth 초기화
    require_once BASE_PATH . '/rzxlib/Core/Auth/AdminAuth.php';
    \RzxLib\Core\Auth\AdminAuth::init($pdo);

    // 로그인 페이지는 인증 불필요
    if ($adminRoute === 'login') {
        include BASE_PATH . '/resources/views/admin/login.php';
        exit;
    }

    // 관리자 로그인 확인
    if (!\RzxLib\Core\Auth\AdminAuth::check()) {
        header('Location: ' . $basePath . '/' . $config['admin_path'] . '/login');
        exit;
    }

    // 권한 확인
    $requiredPerm = \RzxLib\Core\Auth\AdminAuth::getRequiredPermission($adminRoute);
    if ($requiredPerm && !\RzxLib\Core\Auth\AdminAuth::can($requiredPerm)) {
        http_response_code(403);
        include BASE_PATH . '/resources/views/admin/403.php';
        exit;
    }

    // 업데이트 확인 (캐시 기반, 1시간 TTL)
    $updateInfo = null;
    try {
        require_once BASE_PATH . '/rzxlib/Core/Updater/UpdateChecker.php';
        $updateInfo = \RzxLib\Core\Updater\UpdateChecker::check($pdo, BASE_PATH);
    } catch (\Throwable $e) {
        // 업데이트 확인 실패 시 무시
    }

    // 파일 기반 위젯 DB 동기화 (1시간에 1회)
    $syncFlag = BASE_PATH . '/storage/.widget_sync';
    if (!file_exists($syncFlag) || filemtime($syncFlag) < time() - 3600) {
        try {
            $widgetLoader = new \RzxLib\Core\Modules\WidgetLoader($pdo, BASE_PATH . '/widgets');
            $widgetLoader->syncToDatabase();
            @file_put_contents($syncFlag, date('c'));
        } catch (\Throwable $e) {
            error_log("Widget sync error: " . $e->getMessage());
        }
    }

    // 서비스 설정 서브페이지 처리 (services/settings/*)
    if (preg_match('#^services/settings(?:/(\w+))?$#', $adminRoute, $m)) {
        $settingsTab = $m[1] ?? 'general';
        if (!in_array($settingsTab, ['general', 'categories', 'holidays'])) {
            $settingsTab = 'general';
        }
        include BASE_PATH . '/resources/views/admin/services/settings.php';
    // 스태프 관리
    } elseif ($adminRoute === 'staff') {
        include BASE_PATH . '/resources/views/admin/staff/index.php';
    // 스태프 스케줄 관리
    } elseif ($adminRoute === 'staff/schedule') {
        include BASE_PATH . '/resources/views/admin/staff/schedule.php';
    // 관리자 권한 관리
    } elseif ($adminRoute === 'staff/admins') {
        include BASE_PATH . '/resources/views/admin/staff/admins.php';
    // 스태프 설정 서브페이지 처리 (staff/settings)
    } elseif ($adminRoute === 'staff/settings') {
        include BASE_PATH . '/resources/views/admin/staff/settings.php';
    } elseif ($adminRoute === 'staff/attendance') {
        include BASE_PATH . '/resources/views/admin/staff/attendance.php';
    } elseif ($adminRoute === 'staff/attendance/history') {
        include BASE_PATH . '/resources/views/admin/staff/attendance-history.php';
    } elseif ($adminRoute === 'staff/attendance/dashboard') {
        include BASE_PATH . '/resources/views/admin/staff/attendance-dashboard.php';
    } elseif ($adminRoute === 'staff/attendance/kiosk') {
        include BASE_PATH . '/resources/views/admin/staff/attendance-kiosk.php';
    } elseif ($adminRoute === 'staff/attendance/report') {
        include BASE_PATH . '/resources/views/admin/staff/attendance-report.php';
    } elseif (preg_match('#^staff/attendance/report/personal(?:/(\d+))?$#', $adminRoute, $m)) {
        $reportStaffId = $m[1] ?? null;
        include BASE_PATH . '/resources/views/admin/staff/attendance-report-personal.php';
    } elseif ($adminRoute === 'staff/attendance/report/stats') {
        include BASE_PATH . '/resources/views/admin/staff/attendance-report-stats.php';
    // 페이지 관리 - 데이터 관리 가이드 편집
    } elseif ($adminRoute === 'site/pages/compliance') {
        include BASE_PATH . '/resources/views/admin/site/pages-compliance.php';
    // 페이지 관리 - 범용 문서 페이지 에디터
    } elseif ($adminRoute === 'site/pages/edit') {
        include BASE_PATH . '/resources/views/admin/site/pages-document.php';
    // 페이지 관리 - 위젯 빌더 (홈 페이지)
    } elseif ($adminRoute === 'site/pages/widget-builder') {
        include BASE_PATH . '/resources/views/admin/site/pages-widget-builder.php';
    // 위젯 관리
    // 예약 관리 — POST API (상태변경, 생성, 수정)
    } elseif ($adminRoute === 'reservations' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $apiAction = 'store'; $apiId = null;
        include BASE_PATH . '/resources/views/admin/reservations/_api.php';
    } elseif ($adminRoute === 'reservations/customer-services' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $apiAction = 'customer-services'; $apiId = null;
        include BASE_PATH . '/resources/views/admin/reservations/_api.php';
    } elseif ($adminRoute === 'reservations/search-customers' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $apiAction = 'search-customers'; $apiId = null;
        include BASE_PATH . '/resources/views/admin/reservations/_api.php';
    } elseif ($adminRoute === 'reservations/add-service' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $apiAction = 'add-service'; $apiId = null;
        include BASE_PATH . '/resources/views/admin/reservations/_api.php';
    } elseif (preg_match('#^reservations/([\w-]+)/(confirm|cancel|complete|no-show|start-service|payment)$#', $adminRoute, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $apiId = $m[1]; $apiAction = $m[2];
        include BASE_PATH . '/resources/views/admin/reservations/_api.php';
    } elseif (preg_match('#^reservations/([\w-]+)$#', $adminRoute, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $apiId = $m[1]; $apiAction = 'update';
        include BASE_PATH . '/resources/views/admin/reservations/_api.php';
    // 예약 관리 — GET 페이지
    } elseif ($adminRoute === 'reservations/pos') {
        include BASE_PATH . '/resources/views/admin/reservations/pos.php';
    } elseif ($adminRoute === 'reservations') {
        include BASE_PATH . '/resources/views/admin/reservations/index.php';
    } elseif ($adminRoute === 'reservations/calendar') {
        include BASE_PATH . '/resources/views/admin/reservations/calendar.php';
    } elseif ($adminRoute === 'reservations/statistics') {
        include BASE_PATH . '/resources/views/admin/reservations/statistics.php';
    } elseif ($adminRoute === 'reservations/create') {
        include BASE_PATH . '/resources/views/admin/reservations/create.php';
    } elseif (preg_match('#^reservations/([\w-]+)/edit$#', $adminRoute, $m)) {
        $reservationId = $m[1];
        include BASE_PATH . '/resources/views/admin/reservations/edit.php';
    } elseif (preg_match('#^reservations/([\w-]+)$#', $adminRoute, $m)) {
        $reservationId = $m[1];
        include BASE_PATH . '/resources/views/admin/reservations/show.php';
    // 위젯 관리
    } elseif ($adminRoute === 'site/widgets') {
        include BASE_PATH . '/resources/views/admin/site/widgets.php';
    } elseif ($adminRoute === 'site/widgets/create') {
        include BASE_PATH . '/resources/views/admin/site/widgets-create.php';
    } elseif ($adminRoute === 'site/widgets/marketplace') {
        include BASE_PATH . '/resources/views/admin/site/widgets-marketplace.php';
    } else {
        $adminView = BASE_PATH . '/resources/views/admin/' . $adminRoute . '.php';
        if (file_exists($adminView)) {
            include $adminView;
        } else {
            include BASE_PATH . '/resources/views/admin/dashboard.php';
        }
    }
} else {
    // 하위 경로 처리 (예: booking/lookup)
    $pathParts = explode('/', $path);

    // 동적 라우트: staff/{id}
    if (preg_match('#^staff/(\d+)$#', $path, $m)) {
        $routeParams = ['id' => $m[1]];
        include BASE_PATH . '/resources/views/customer/staff-detail.php';
    } else {
        $customerView = BASE_PATH . '/resources/views/customer/' . $path . '.php';

        if (file_exists($customerView)) {
            include $customerView;
        } else {
            http_response_code(404);
            include BASE_PATH . '/resources/views/customer/404.php';
        }
    }
}
