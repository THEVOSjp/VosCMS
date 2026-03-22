<?php
/**
 * RezlyX - Modern Reservation System
 *
 * @package RezlyX
 * @see version.json for current version
 */

define('REZLYX_START', microtime(true));
header('X-Frame-Options: SAMEORIGIN');
header("Content-Security-Policy: frame-ancestors 'self'");
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

// 다국어 UI 헬퍼 (rzx_multilang_btn, rzx_multilang_input 등)
require_once BASE_PATH . '/rzxlib/Core/Helpers/multilang.php';

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

// 버전은 version.json에서 단일 소스로 관리
$_versionFile = BASE_PATH . '/version.json';
$_versionData = file_exists($_versionFile) ? json_decode(file_get_contents($_versionFile), true) : [];
$_appVersion = $_versionData['version'] ?? '1.0.0';

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
    $codeVersion = $_appVersion;
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

// 레이아웃 자동 적용 (고객 페이지)
// 페이지에서 $__layout = false 설정 시 레이아웃 미적용
$__pageFile = null;
$__noLayout = false; // API, 로그인 등 자체 레이아웃 페이지

// Route to appropriate handler
if (empty($path) || $path === 'index.php') {
    $__pageFile = BASE_PATH . '/resources/views/customer/home.php';
} elseif ($path === 'staff') {
    $__pageFile = BASE_PATH . '/resources/views/customer/staff.php';
} elseif (preg_match('#^staff/([^/]+)$#', $path, $m)) {
    $staffSlug = $m[1];
    $__pageFile = BASE_PATH . '/resources/views/customer/staff-detail.php';
} elseif ($path === 'booking') {
    $__pageFile = BASE_PATH . '/resources/views/customer/booking.php';
} elseif ($path === 'lookup') {
    $__pageFile = BASE_PATH . '/resources/views/customer/booking/lookup.php';
} elseif (preg_match('#^booking/detail/([A-Za-z0-9]+)$#', $path, $m)) {
    $reservationNumber = $m[1];
    $__pageFile = BASE_PATH . '/resources/views/customer/booking/detail.php';
} elseif (preg_match('#^booking/cancel/([A-Za-z0-9]+)$#', $path, $m)) {
    $reservationNumber = $m[1];
    $__pageFile = BASE_PATH . '/resources/views/customer/booking/cancel.php';
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

    // 관리자 로그아웃
    if ($adminRoute === 'logout') {
        \RzxLib\Core\Auth\AdminAuth::logout();
        header('Location: ' . $basePath . '/' . $config['admin_path'] . '/login');
        exit;
    }

    // 키오스크 실행 (인증 불필요)
    if (str_starts_with($adminRoute, 'kiosk/run')) {
        $kioskPages = [
            'kiosk/run' => 'customer/kiosk/index.php',
            'kiosk/run/choose' => 'customer/kiosk/choose.php',
            'kiosk/run/staff' => 'customer/kiosk/staff.php',
            'kiosk/run/service' => 'customer/kiosk/service.php',
            'kiosk/run/confirm' => 'customer/kiosk/confirm.php',
            'kiosk/run/confirm-form' => 'customer/kiosk/confirm-form.php',
            'kiosk/run/confirm-done' => 'customer/kiosk/confirm-done.php',
        ];
        $kioskFile = $kioskPages[$adminRoute] ?? null;
        if ($kioskFile) {
            include BASE_PATH . '/resources/views/' . $kioskFile;
            exit;
        }
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
    // 페이지 관리 - 페이지 환경 설정
    } elseif ($adminRoute === 'site/pages/settings') {
        include BASE_PATH . '/resources/views/admin/site/pages-settings.php';
    // 페이지 관리 - 페이지 콘텐츠 편집
    } elseif ($adminRoute === 'site/pages/edit-content') {
        include BASE_PATH . '/resources/views/admin/site/pages-edit-content.php';
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
    } elseif ($adminRoute === 'services/list-json' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        // 활성 서비스 목록 JSON
        header('Content-Type: application/json; charset=utf-8');
        $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
        $stmt = $pdo->query("SELECT s.id, s.name, s.price, s.duration, c.name as category_name FROM {$prefix}services s LEFT JOIN {$prefix}service_categories c ON s.category_id = c.id WHERE s.is_active = 1 ORDER BY s.sort_order ASC, s.name ASC");
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($services as &$sv) { $sv['price_formatted'] = number_format((float)$sv['price']); }
        echo json_encode(['services' => $services]);
        exit;
    } elseif ($adminRoute === 'reservations/customer-services' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $apiAction = 'customer-services'; $apiId = null;
        include BASE_PATH . '/resources/views/admin/reservations/_api.php';
    } elseif ($adminRoute === 'reservations/search-customers' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $apiAction = 'search-customers'; $apiId = null;
        include BASE_PATH . '/resources/views/admin/reservations/_api.php';
    } elseif ($adminRoute === 'reservations/available-staff' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $apiAction = 'available-staff'; $apiId = null;
        include BASE_PATH . '/resources/views/admin/reservations/_api.php';
    } elseif ($adminRoute === 'reservations/add-service' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $apiAction = 'add-service'; $apiId = null;
        include BASE_PATH . '/resources/views/admin/reservations/_api.php';
    } elseif ($adminRoute === 'reservations/append-service' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $apiAction = 'append-service'; $apiId = null;
        include BASE_PATH . '/resources/views/admin/reservations/_api.php';
    } elseif ($adminRoute === 'reservations/assign-staff' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $apiAction = 'assign-staff'; $apiId = null;
        include BASE_PATH . '/resources/views/admin/reservations/_api.php';
    } elseif ($adminRoute === 'reservations/remove-service' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $apiAction = 'remove-service'; $apiId = null;
        include BASE_PATH . '/resources/views/admin/reservations/_api.php';
    } elseif ($adminRoute === 'reservations/save-memo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $apiAction = 'save-memo'; $apiId = null;
        include BASE_PATH . '/resources/views/admin/reservations/_api.php';
    } elseif (preg_match('#^reservations/([\w-]+)/(confirm|cancel|complete|no-show|start-service|payment)$#', $adminRoute, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $apiId = $m[1]; $apiAction = $m[2];
        include BASE_PATH . '/resources/views/admin/reservations/_api.php';
    } elseif (preg_match('#^reservations/([\w-]+)$#', $adminRoute, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $apiId = $m[1]; $apiAction = 'update';
        include BASE_PATH . '/resources/views/admin/reservations/_api.php';
    // 예약 관리 — GET 페이지
    // 키오스크 관리
    } elseif ($adminRoute === 'kiosk') {
        include BASE_PATH . '/resources/views/admin/reservations/kiosk.php';
    } elseif ($adminRoute === 'kiosk/settings') {
        include BASE_PATH . '/resources/views/admin/reservations/kiosk-settings.php';
    } elseif ($adminRoute === 'kiosk/run') {
        include BASE_PATH . '/resources/views/customer/kiosk/index.php';
    } elseif ($adminRoute === 'kiosk/run/choose') {
        include BASE_PATH . '/resources/views/customer/kiosk/choose.php';
    } elseif ($adminRoute === 'kiosk/run/staff') {
        include BASE_PATH . '/resources/views/customer/kiosk/staff.php';
    } elseif ($adminRoute === 'kiosk/run/service') {
        include BASE_PATH . '/resources/views/customer/kiosk/service.php';
    } elseif ($adminRoute === 'kiosk/run/confirm') {
        include BASE_PATH . '/resources/views/customer/kiosk/confirm.php';
    } elseif ($adminRoute === 'kiosk/upload' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        include BASE_PATH . '/resources/views/admin/reservations/kiosk-upload.php';
    } elseif ($adminRoute === 'reservations/pos') {
        include BASE_PATH . '/resources/views/admin/reservations/pos.php';
    } elseif ($adminRoute === 'pos/settings') {
        include BASE_PATH . '/resources/views/admin/reservations/pos-settings.php';
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
    // 묶음서비스(번들) 관리
    } elseif ($adminRoute === 'bundles') {
        include BASE_PATH . '/resources/views/admin/bundles/index.php';
    } elseif (preg_match('#^bundles/([\w-]+)$#', $adminRoute, $m)) {
        $bundleId = $m[1];
        include BASE_PATH . '/resources/views/admin/bundles/edit.php';
    // 게시판 관리
    } elseif ($adminRoute === 'site/boards') {
        include BASE_PATH . '/resources/views/admin/site/boards.php';
    } elseif ($adminRoute === 'site/boards/create') {
        include BASE_PATH . '/resources/views/admin/site/boards-create.php';
    } elseif ($adminRoute === 'site/boards/edit') {
        include BASE_PATH . '/resources/views/admin/site/boards-edit.php';
    } elseif ($adminRoute === 'site/boards/api') {
        include BASE_PATH . '/resources/views/admin/site/boards-api.php';
    } elseif ($adminRoute === 'site/boards/trash') {
        include BASE_PATH . '/resources/views/admin/site/boards-trash.php';
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

    // 동적 라우트: board/{slug}
    if (preg_match('#^board/([a-z0-9_-]+)$#', $path, $m)) {
        $boardSlug = $m[1];
        $__pageFile = BASE_PATH . '/resources/views/customer/board/list.php';
    } elseif (preg_match('#^board/([a-z0-9_-]+)/settings$#', $path, $m)) {
        $boardSlug = $m[1];
        $__pageFile = BASE_PATH . '/resources/views/customer/board/settings.php';
    } elseif (preg_match('#^board/([a-z0-9_-]+)/write$#', $path, $m)) {
        $boardSlug = $m[1];
        $__pageFile = BASE_PATH . '/resources/views/customer/board/write.php';
    } elseif (preg_match('#^board/([a-z0-9_-]+)/(\d+)$#', $path, $m)) {
        $boardSlug = $m[1];
        $postId = (int)$m[2];
        $__pageFile = BASE_PATH . '/resources/views/customer/board/read.php';
    } elseif (preg_match('#^board/([a-z0-9_-]+)/(\d+)/edit$#', $path, $m)) {
        $boardSlug = $m[1];
        $postId = (int)$m[2];
        $__pageFile = BASE_PATH . '/resources/views/customer/board/write.php';
    } elseif ($path === 'board/api/posts') {
        $__noLayout = true;
        include BASE_PATH . '/resources/views/customer/board/api-posts.php';
    } elseif ($path === 'board/api/comments') {
        $__noLayout = true;
        include BASE_PATH . '/resources/views/customer/board/api-comments.php';
    } elseif ($path === 'board/api/files') {
        $__noLayout = true;
        include BASE_PATH . '/resources/views/customer/board/api-files.php';
    } elseif ($path === 'board/api/og') {
        $__noLayout = true;
        include BASE_PATH . '/resources/views/customer/board/api-og.php';
    // 키오스크 고객용 라우트
    } elseif ($path === 'kiosk' || $path === 'kiosk/index') {
        $__noLayout = true;
        include BASE_PATH . '/resources/views/customer/kiosk/index.php';
    } elseif ($path === 'kiosk/choose') {
        $__noLayout = true;
        include BASE_PATH . '/resources/views/customer/kiosk/choose.php';
    } elseif ($path === 'kiosk/staff') {
        $__noLayout = true;
        include BASE_PATH . '/resources/views/customer/kiosk/staff.php';
    } elseif ($path === 'kiosk/service') {
        $__noLayout = true;
        include BASE_PATH . '/resources/views/customer/kiosk/service.php';
    } elseif ($path === 'kiosk/confirm') {
        $__noLayout = true;
        include BASE_PATH . '/resources/views/customer/kiosk/confirm.php';
    } elseif (preg_match('#^kiosk/confirm-form$#', $path)) {
        $__noLayout = true;
        include BASE_PATH . '/resources/views/customer/kiosk/confirm-form.php';
    } elseif (preg_match('#^kiosk/confirm-done$#', $path)) {
        $__noLayout = true;
        include BASE_PATH . '/resources/views/customer/kiosk/confirm-done.php';
    // 동적 라우트: mypage/reservations/{id}
    } elseif (preg_match('#^mypage/reservations/([a-zA-Z0-9_-]+)$#', $path, $m)) {
        $reservationId = $m[1];
        $__pageFile = BASE_PATH . '/resources/views/customer/mypage/reservation-detail.php';
    // 동적 라우트: staff/{id}
    } elseif (preg_match('#^staff/(\d+)$#', $path, $m)) {
        $routeParams = ['id' => $m[1]];
        $__pageFile = BASE_PATH . '/resources/views/customer/staff-detail.php';
    } else {
        $customerView = BASE_PATH . '/resources/views/customer/' . $path . '.php';

        if (file_exists($customerView)) {
            // 자체 레이아웃 페이지
            $_noLayoutPages = ['logout'];
            $_memberPages = ['login', 'register', 'forgot-password', 'reset-password'];
            if (in_array($path, $_noLayoutPages) || str_starts_with($path, 'kiosk/')) {
                $__noLayout = true;
                include $customerView;
            } elseif (in_array($path, $_memberPages)) {
                // 회원 페이지: 기본 레이아웃 적용
                $__pageFile = $customerView;
            } else {
                $__pageFile = $customerView;
            }
        } else {
            // 게시판 slug 확인 (/free → board/free, /notice/3 → board/notice/3)
            $_boardMatch = false;
            $_pathParts = explode('/', $path);
            $_slugCandidate = $_pathParts[0] ?? '';
            if ($_slugCandidate && preg_match('/^[a-z0-9_-]+$/', $_slugCandidate)) {
                $_bChk = $pdo->prepare("SELECT id FROM {$_ENV['DB_PREFIX']}boards WHERE slug = ? AND is_active = 1 LIMIT 1");
                $_bChk->execute([$_slugCandidate]);
                if ($_bChk->fetch()) {
                    $_boardMatch = true;
                    $boardSlug = $_slugCandidate;
                    if (count($_pathParts) === 1) {
                        // /free → 목록
                        $__pageFile = BASE_PATH . '/resources/views/customer/board/list.php';
                    } elseif ($_pathParts[1] === 'write') {
                        // /free/write → 글쓰기
                        $__pageFile = BASE_PATH . '/resources/views/customer/board/write.php';
                    } elseif (is_numeric($_pathParts[1]) && !isset($_pathParts[2])) {
                        // /free/3 → 글 읽기
                        $postId = (int)$_pathParts[1];
                        $__pageFile = BASE_PATH . '/resources/views/customer/board/read.php';
                    } elseif (is_numeric($_pathParts[1]) && ($_pathParts[2] ?? '') === 'edit') {
                        // /free/3/edit → 글 수정
                        $postId = (int)$_pathParts[1];
                        $__pageFile = BASE_PATH . '/resources/views/customer/board/write.php';
                    }
                }
            }
            if (!$_boardMatch) {
                $_pfx = $_ENV['DB_PREFIX'] ?? 'rzx_';

                // 페이지 설정/편집 프론트 라우트: {slug}/settings, {slug}/edit
                $_pageRouteMatch = false;
                if (preg_match('#^([a-z0-9_-]+)/(settings|edit)$#', $path, $_prm)) {
                    $_prSlug = $_prm[1];
                    $_prAction = $_prm[2];
                    $_prCheck = $pdo->prepare("SELECT page_slug FROM {$_pfx}page_contents WHERE page_slug = ? LIMIT 1");
                    $_prCheck->execute([$_prSlug]);
                    if ($_prCheck->fetchColumn()) {
                        $pageSlug = $_prSlug;
                        if ($_prAction === 'settings') {
                            $__pageFile = BASE_PATH . '/resources/views/customer/page-settings.php';
                        } else {
                            $__pageFile = BASE_PATH . '/resources/views/customer/page-edit.php';
                        }
                        $_pageRouteMatch = true;
                    }
                }

                if (!$_pageRouteMatch) {
                    // 동적 페이지 확인 (rzx_page_contents)
                    $_pageCheck = $pdo->prepare("SELECT page_slug FROM {$_pfx}page_contents WHERE page_slug = ? LIMIT 1");
                    $_pageCheck->execute([$path]);
                    if ($_pageCheck->fetchColumn()) {
                        $pageSlug = $path;
                        $__pageFile = BASE_PATH . '/resources/views/customer/page.php';
                    } else {
                        http_response_code(404);
                        $__noLayout = true;
                        include BASE_PATH . '/resources/views/customer/404.php';
                    }
                }
            }
        }
    }
}

// === 레이아웃 자동 적용 ===
if ($__pageFile && !$__noLayout) {
    // 전체 레이아웃/스킨 설정 (DB → 페이지 개별 설정 → 기본값)
    $__layout = $siteSettings['site_layout'] ?? 'default';
    $__sitePageSkin = $siteSettings['site_page_skin'] ?? 'default';
    $__siteBoardSkin = $siteSettings['site_board_skin'] ?? 'default';
    $__siteMemberSkin = $siteSettings['site_member_skin'] ?? 'default';

    // none이면 레이아웃 미사용
    if ($__layout === 'none') $__layout = false;

    // no_layout 파라미터: 레이아웃 없이 콘텐츠만 (레이아웃 관리 미리보기용)
    if (!empty($_GET['no_layout'])) $__layout = false;

    // 페이지에서 사용할 공통 변수 미리 설정
    require_once BASE_PATH . '/rzxlib/Core/I18n/Translator.php';
    \RzxLib\Core\I18n\Translator::init(BASE_PATH . '/resources/lang');
    if (!isset($currentLocale)) $currentLocale = current_locale();
    if (!isset($baseUrl)) $baseUrl = rtrim($config['app_url'] ?? '', '/');
    if (!isset($siteSettings)) $siteSettings = [];

    require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
    $isLoggedIn = \RzxLib\Core\Auth\Auth::check();
    if (!isset($currentUser)) $currentUser = $isLoggedIn ? \RzxLib\Core\Auth\Auth::user() : null;
    $isAdmin = !empty($_SESSION['admin_id']);

    ob_start();
    include $__pageFile;
    $__content = ob_get_clean();

    // 페이지에서 $__layout = false 설정 시 콘텐츠만 출력
    if ($__layout === false) {
        echo $__content;
    } else {
        // 스킨 레이아웃 파일 확인 (skins/layouts/{name}/main.php)
        $__layoutFile = BASE_PATH . '/skins/layouts/' . $__layout . '/main.php';
        if ($__layout !== 'default' && file_exists($__layoutFile)) {
            // 스킨 레이아웃의 설정값 로드
            $__layoutConfig = [];
            $__lcKey = 'skin_detail_layout_' . $__layout;
            if (isset($siteSettings[$__lcKey])) {
                $__layoutConfig = json_decode($siteSettings[$__lcKey], true) ?: [];
            }
            include $__layoutFile;
        } else {
            // 기본 레이아웃 (base-header + content + base-footer)
            include BASE_PATH . '/resources/views/layouts/base-header.php';
            echo $__content;
            include BASE_PATH . '/resources/views/layouts/base-footer.php';
        }
    }
}
