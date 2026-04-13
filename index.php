<?php
/**
 * RezlyX - Modern Reservation System
 *
 * @package RezlyX
 * @see version.json for current version
 */

define('REZLYX_START', microtime(true));

// VosCMS 설치 체크 — .env가 없으면 설치 마법사로 리다이렉트
if (!file_exists(__DIR__ . '/.env') && file_exists(__DIR__ . '/install.php')) {
    header('Location: /install.php');
    exit;
}

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
    // 세션 수명: .env SESSION_LIFETIME (분) 또는 기본 7일
    $_sessionLifetime = ((int)($_ENV['SESSION_LIFETIME'] ?? 0) ?: 10080) * 60; // 분→초
    ini_set('session.gc_maxlifetime', (string)$_sessionLifetime);
    session_set_cookie_params([
        'lifetime' => $_sessionLifetime,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly'  => true,
        'samesite'  => 'Lax',
    ]);
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
if (!file_exists(BASE_PATH . '/install/installed.lock') && (!file_exists(BASE_PATH . '/.env') || filesize(BASE_PATH . '/.env') === 0)) {
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

// 세션/쿠키에서 로케일 로드 (초기화 전)
$_requestLocale = null;
if (!empty($_SESSION['locale'])) {
    $_requestLocale = $_SESSION['locale'];
} elseif (!empty($_COOKIE['locale'])) {
    $_requestLocale = $_COOKIE['locale'];
}

// URL 파라미터로 언어 변경 처리
if (isset($_GET['lang'])) {
    $_requestLocale = $_GET['lang'];
    Translator::setLocale($_requestLocale);

    // 쿠키에 로케일 저장 (리다이렉트 전!)
    setcookie('locale', $_requestLocale, [
        'expires' => time() + 31536000,  // 1년
        'path' => '/',
        'samesite' => 'Lax'
    ]);
    $_COOKIE['locale'] = $_requestLocale;
    $_SESSION['locale'] = $_requestLocale;

    // 현재 URL에서 lang 파라미터 제거 후 리다이렉트
    $currentUrl = strtok($_SERVER['REQUEST_URI'], '?');
    $params = $_GET;
    unset($params['lang']);
    $queryString = http_build_query($params);
    $redirectUrl = $currentUrl . ($queryString ? '?' . $queryString : '');

    header('Location: ' . $redirectUrl);
    exit;
} elseif ($_requestLocale) {
    // 세션/쿠키에서 로드한 로케일 적용
    Translator::setLocale($_requestLocale);
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
        'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost')
        . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx')
        . ';charset=' . ($_ENV['DB_CHARSET'] ?? 'utf8mb4'),
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

/*
|--------------------------------------------------------------------------
| Initialize Plugin System
|--------------------------------------------------------------------------
*/
require_once BASE_PATH . '/rzxlib/Core/Plugin/Hook.php';
require_once BASE_PATH . '/rzxlib/Core/Plugin/PluginManager.php';

$pluginManager = null;
if (isset($pdo)) {
    $pluginManager = \RzxLib\Core\Plugin\PluginManager::init($pdo, BASE_PATH . '/plugins', $_ENV['DB_PREFIX'] ?? 'rzx_');
    try {
        $pluginManager->loadAll();
    } catch (\Exception $e) {
        if ($config['debug']) error_log('Plugin load error: ' . $e->getMessage());
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

// Debug output (remove in production)
if ($config['debug']) {
    error_log("RezlyX Debug - Path: '$path', AdminPath: '{$config['admin_path']}'");
}

// 레이아웃 자동 적용 (고객 페이지)
// 페이지에서 $__layout = false 설정 시 레이아웃 미적용
$__pageFile = null;
$__noLayout = false; // API, 로그인 등 자체 레이아웃 페이지

// Route to appropriate handler
if (empty($path) || $path === 'index.php' || $path === 'home') {
    // home_page 설정에 지정된 위젯 페이지로 연결
    $pageSlug = $siteSettings['home_page'] ?? 'home';
    $__pageFile = BASE_PATH . '/resources/views/customer/page.php';
} elseif (preg_match('#^staff/([^/]+)$#', $path, $m) && !in_array($m[1], ['settings', 'edit'])) {
    $staffSlug = $m[1];
    $__pageFile = BASE_PATH . '/resources/views/customer/staff-detail.php';
} elseif (preg_match('#^booking/detail/([A-Za-z0-9-]+)$#', $path, $m)) {
    $reservationNumber = $m[1];
    $__pageFile = BASE_PATH . '/resources/views/customer/booking/detail.php';
} elseif (preg_match('#^booking/cancel/([A-Za-z0-9-]+)$#', $path, $m)) {
    $reservationNumber = $m[1];
    $__pageFile = BASE_PATH . '/resources/views/customer/booking/cancel.php';
} elseif ($path === 'payment/checkout') {
    $__pageFile = BASE_PATH . '/resources/views/customer/payment/checkout.php';
} elseif ($path === 'payment/success') {
    $__pageFile = BASE_PATH . '/resources/views/customer/payment/success.php';
} elseif ($path === 'payment/cancel') {
    $__pageFile = BASE_PATH . '/resources/views/customer/payment/cancel.php';
} elseif ($path === 'payment/webhook' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    include BASE_PATH . '/resources/views/customer/payment/webhook.php';
    exit;

// ─── Developer Pages ───
} elseif (str_starts_with($path, 'developer')) {
    $devRoute = trim(substr($path, strlen('developer')), '/') ?: 'dashboard';
    $devViewFile = BASE_PATH . '/resources/views/developer/' . basename($devRoute) . '.php';
    if (file_exists($devViewFile)) {
        include $devViewFile;
    } else {
        include BASE_PATH . '/resources/views/developer/dashboard.php';
    }
    exit;

// ─── Public Marketplace ───
} elseif (str_starts_with($path, 'marketplace')) {
    $mpRoute = trim(substr($path, strlen('marketplace')), '/') ?: 'index';
    $mpViewFile = BASE_PATH . '/resources/views/marketplace/' . basename($mpRoute) . '.php';
    if (file_exists($mpViewFile)) {
        include $mpViewFile;
    } else {
        include BASE_PATH . '/resources/views/marketplace/index.php';
    }
    exit;

// ─── Developer API ───
} elseif (str_starts_with($path, 'api/developer/')) {
    $devEndpoint = substr($path, strlen('api/developer/'));
    $devApiFile = BASE_PATH . '/api/developer/' . basename($devEndpoint) . '.php';
    if (file_exists($devApiFile)) {
        include $devApiFile;
    } else {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['error' => 'endpoint_not_found']);
    }
    exit;

// ─── Public API (공지사항 등) ───
} elseif ($path === 'api/notices' || $path === 'api/notices/') {
    include BASE_PATH . '/api/notices.php';
    exit;

// ─── License Server API ───
} elseif (str_starts_with($path, 'api/license/')) {
    $licenseEndpoint = substr($path, strlen('api/license/'));
    $licenseApiFile = BASE_PATH . '/api/license/' . basename($licenseEndpoint) . '.php';
    if (file_exists($licenseApiFile)) {
        include $licenseApiFile;
    } else {
        header('Content-Type: application/json');
        http_response_code(404);
        echo json_encode(['error' => 'endpoint_not_found']);
    }
    exit;

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
    // 키오스크 실행 (로그인 불필요 — 플러그인 라우트 우선)
    if (str_starts_with($adminRoute, 'kiosk/run') && isset($pluginManager)) {
        foreach ($pluginManager->getRoutes() as $_pr) {
            if ($_pr['type'] === 'admin' && $adminRoute === $_pr['path'] && file_exists($_pr['view_path'])) {
                include $_pr['view_path'];
                exit;
            }
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

    // ─── 라이선스 체크 ───
    $licenseInfo = null;
    try {
        require_once BASE_PATH . '/rzxlib/Core/License/LicenseClient.php';
        require_once BASE_PATH . '/rzxlib/Core/License/LicenseStatus.php';
        $licenseClient = new \RzxLib\Core\License\LicenseClient();

        // 라이선스 키 미등록 사이트 → 자동 Free 라이선스 등록
        if (empty($_ENV['LICENSE_KEY'])) {
            try {
                $result = $licenseClient->register(
                    $_ENV['APP_URL'] ?? $_SERVER['HTTP_HOST'] ?? 'unknown',
                    $_ENV['APP_VERSION'] ?? '2.1.0',
                    PHP_VERSION
                );
                if (!empty($result['success']) && !empty($result['key'])) {
                    // .env에 라이선스 정보 추가
                    $envFile = BASE_PATH . '/.env';
                    if (file_exists($envFile) && is_writable($envFile)) {
                        $envContent = file_get_contents($envFile);
                        if (!str_contains($envContent, 'LICENSE_KEY=')) {
                            $domain = $licenseClient::normalizeDomain($_ENV['APP_URL'] ?? $_SERVER['HTTP_HOST'] ?? '');
                            $envContent .= "\n\nLICENSE_KEY={$result['key']}\nLICENSE_DOMAIN={$domain}\nLICENSE_REGISTERED_AT=" . date('c') . "\nLICENSE_SERVER=" . ($_ENV['LICENSE_SERVER'] ?? 'https://vos.21ces.com/api') . "\n";
                            file_put_contents($envFile, $envContent);
                            $_ENV['LICENSE_KEY'] = $result['key'];
                        }
                    }
                }
            } catch (\Throwable $e) {
                if ($config['debug']) error_log('Auto license register error: ' . $e->getMessage());
            }
        }

        $licenseStatus = $licenseClient->check();
        $licenseInfo = $licenseStatus->toArray();
    } catch (\Throwable $e) {
        if ($config['debug']) error_log('License check error: ' . $e->getMessage());
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

    // 서비스 설정 (정규식 라우트 — 플러그인에서 직접 처리)
    if (preg_match('#^services/settings(?:/(\w+))?$#', $adminRoute, $m)) {
        $settingsTab = $m[1] ?? 'general';
        if (!in_array($settingsTab, ['general', 'categories', 'holidays'])) $settingsTab = 'general';
        $_sf = BASE_PATH . '/plugins/vos-salon/views/services/settings.php';
        if (file_exists($_sf)) { include $_sf; } else { include BASE_PATH . '/resources/views/admin/dashboard.php'; }
    // 근태 개인 리포트 (정규식 라우트)
    } elseif (preg_match('#^staff/attendance/report/personal(?:/(\d+))?$#', $adminRoute, $m)) {
        $reportStaffId = $m[1] ?? null;
        $_prFile = BASE_PATH . '/plugins/vos-attendance/views/attendance-report-personal.php';
        if (file_exists($_prFile)) { include $_prFile; } else { include BASE_PATH . '/resources/views/admin/dashboard.php'; }
    // 관리자 권한 관리 (코어)
    } elseif ($adminRoute === 'staff/admins') {
        include BASE_PATH . '/resources/views/admin/staff/admins.php';
    // 업소 관리 (vos-shop 플러그인 — 존재 시만)
    } elseif (file_exists(BASE_PATH . '/plugins/vos-shop/plugin.json') && $adminRoute === 'shops/consultations') {
        include BASE_PATH . '/plugins/vos-shop/views/admin/consultations.php';
    } elseif (file_exists(BASE_PATH . '/plugins/vos-shop/plugin.json') && ($adminRoute === 'shops' || str_starts_with($adminRoute, 'shops/'))) {
        include BASE_PATH . '/plugins/vos-shop/views/admin/shops.php';
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
    // 예약/서비스/스태프 관리: vos-salon 플러그인으로 이전됨
    // 예약 API 라우트 (POST, 정규식 라우트 — 플러그인 라우트 시스템으로 처리 불가하므로 직접 참조)
    } elseif ($adminRoute === 'reservations' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $apiAction = 'store'; $apiId = null;
        include BASE_PATH . '/plugins/vos-salon/views/reservations/_api.php';
    } elseif (preg_match('#^reservations/(customer-services|search-customers|available-staff)$#', $adminRoute, $m) && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $apiAction = $m[1]; $apiId = null;
        include BASE_PATH . '/plugins/vos-salon/views/reservations/_api.php';
    } elseif (preg_match('#^reservations/(add-service|append-service|assign-staff|remove-service|save-memo)$#', $adminRoute, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $apiAction = $m[1]; $apiId = null;
        include BASE_PATH . '/plugins/vos-salon/views/reservations/_api.php';
    } elseif (preg_match('#^reservations/([\w-]+)/update-contact$#', $adminRoute, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        $resId = $m[1];
        $phone = trim($_POST['phone'] ?? '');
        $email = trim($_POST['email'] ?? '');
        if ($phone) {
            try {
                $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
                $pdo->prepare("UPDATE {$prefix}reservations SET customer_phone = ?, customer_email = ? WHERE id = ?")
                    ->execute([$phone, $email ?: null, $resId]);
                echo json_encode(['success' => true]);
            } catch (\Throwable $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Phone required']);
        }
        exit;
    } elseif (preg_match('#^reservations/([\w-]+)/update-datetime$#', $adminRoute, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        $resId = $m[1];
        $date = $_POST['date'] ?? '';
        $time = $_POST['time'] ?? '';
        if ($date && $time) {
            try {
                $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
                // 기존 예약의 duration 계산
                $rStmt = $pdo->prepare("SELECT start_time, end_time FROM {$prefix}reservations WHERE id = ?");
                $rStmt->execute([$resId]);
                $rData = $rStmt->fetch(PDO::FETCH_ASSOC);
                if ($rData) {
                    $oldStart = new DateTime('2000-01-01 ' . $rData['start_time']);
                    $oldEnd = new DateTime('2000-01-01 ' . $rData['end_time']);
                    $duration = ($oldEnd->getTimestamp() - $oldStart->getTimestamp()) / 60;
                    $newStart = new DateTime("$date $time");
                    $newEnd = clone $newStart;
                    $newEnd->modify("+{$duration} minutes");
                    $pdo->prepare("UPDATE {$prefix}reservations SET reservation_date = ?, start_time = ?, end_time = ? WHERE id = ?")
                        ->execute([$date, $time . ':00', $newEnd->format('H:i:s'), $resId]);
                    echo json_encode(['success' => true]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Reservation not found']);
                }
            } catch (\Throwable $e) {
                echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Date and time required']);
        }
        exit;
    } elseif (preg_match('#^reservations/([\w-]+)/(confirm|cancel|complete|no-show|start-service|payment)$#', $adminRoute, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $apiId = $m[1]; $apiAction = $m[2];
        include BASE_PATH . '/resources/views/admin/reservations/_api.php';
    } elseif (preg_match('#^reservations/([\w-]+)$#', $adminRoute, $m) && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $apiId = $m[1]; $apiAction = 'update';
        include BASE_PATH . '/resources/views/admin/reservations/_api.php';
    // 예약 관리 — GET 페이지
    // 예약/번들: vos-salon 플러그인으로 이전
    } elseif (preg_match('#^reservations/([\w-]+)$#', $adminRoute, $m)) {
        // 플러그인 라우트 우선 (POS 등), 없으면 예약 상세
        $_pluginHit = false;
        if (isset($pluginManager)) {
            foreach ($pluginManager->getRoutes() as $_pr) {
                if ($_pr['type'] === 'admin' && $adminRoute === $_pr['path'] && file_exists($_pr['view_path'])) {
                    include $_pr['view_path'];
                    $_pluginHit = true;
                    break;
                }
            }
        }
        if (!$_pluginHit) {
            $reservationId = $m[1];
            $_sf = BASE_PATH . '/plugins/vos-salon/views/reservations/show.php';
            if (file_exists($_sf)) { include $_sf; } else { include BASE_PATH . '/resources/views/admin/dashboard.php'; }
        }
    } elseif (preg_match('#^bundles/([\w-]+)$#', $adminRoute, $m)) {
        $bundleId = $m[1];
        $_sf = BASE_PATH . '/plugins/vos-salon/views/bundles/edit.php';
        if (file_exists($_sf)) { include $_sf; } else { include BASE_PATH . '/resources/views/admin/dashboard.php'; }
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
    } elseif ($adminRoute === 'plugins') {
        include BASE_PATH . '/resources/views/admin/plugins.php';
    } elseif ($adminRoute === 'review-queue') {
        include BASE_PATH . '/resources/views/admin/review-queue.php';
    } elseif ($adminRoute === 'plugins/api') {
        $__noLayout = true;
        include BASE_PATH . '/resources/views/admin/plugins-api.php';
    } else {
        // 플러그인 라우트 매칭
        $_pluginRouteMatch = false;
        if (isset($pluginManager)) {
            foreach ($pluginManager->getRoutes() as $_pr) {
                if ($_pr['type'] === 'admin' && $adminRoute === $_pr['path']) {
                    if (file_exists($_pr['view_path'])) {
                        include $_pr['view_path'];
                        $_pluginRouteMatch = true;
                    }
                    break;
                }
            }
        }
        if (!$_pluginRouteMatch) {
            $adminView = BASE_PATH . '/resources/views/admin/' . $adminRoute . '.php';
            if (file_exists($adminView)) {
                include $adminView;
            } else {
                include BASE_PATH . '/resources/views/admin/dashboard.php';
            }
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
    // 키오스크: 플러그인 프론트 라우트로 이전됨 (vos-kiosk)
    // 마이페이지 라우트
    } elseif ($path === 'mypage') {
        $__pageFile = BASE_PATH . '/resources/views/customer/mypage/profile.php';
    } elseif ($path === 'mypage/reservations') {
        $__pageFile = BASE_PATH . '/resources/views/customer/mypage/reservations.php';
    } elseif (preg_match('#^mypage/reservations/([a-zA-Z0-9_-]+)$#', $path, $m)) {
        $reservationId = $m[1];
        $__pageFile = BASE_PATH . '/resources/views/customer/mypage/reservation-detail.php';
    } elseif ($path === 'mypage/password') {
        $__pageFile = BASE_PATH . '/resources/views/customer/mypage/password.php';
    } elseif ($path === 'mypage/settings') {
        $__pageFile = BASE_PATH . '/resources/views/customer/mypage/settings.php';
    } elseif ($path === 'mypage/withdraw') {
        $__pageFile = BASE_PATH . '/resources/views/customer/mypage/withdraw.php';
    } elseif ($path === 'mypage/messages') {
        $__pageFile = BASE_PATH . '/resources/views/customer/mypage/messages.php';
    // 업소 라우트 (vos-shop 플러그인 — 존재 시만)
    } elseif (file_exists(BASE_PATH . '/plugins/vos-shop/plugin.json') && $path === 'shop/my') {
        // 내 사업장으로 리다이렉트
        require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
        $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
        if (\RzxLib\Core\Auth\Auth::check()) {
            $_myUser = \RzxLib\Core\Auth\Auth::user();
            $_myShop = $pdo->prepare("SELECT slug FROM {$prefix}shops WHERE user_id = ? ORDER BY created_at DESC LIMIT 1");
            $_myShop->execute([$_myUser['id']]);
            $_mySlug = $_myShop->fetchColumn();
            if ($_mySlug) {
                header('Location: ' . ($config['app_url'] ?? '') . '/shop/' . $_mySlug . '/edit');
                exit;
            }
        }
        header('Location: ' . ($config['app_url'] ?? '') . '/shop/register');
        exit;
    } elseif (file_exists(BASE_PATH . '/plugins/vos-shop/plugin.json') && $path === 'shop/register') {
        $__pageFile = BASE_PATH . '/plugins/vos-shop/views/customer/shop/register.php';
    } elseif (file_exists(BASE_PATH . '/plugins/vos-shop/plugin.json') && preg_match('#^shop/([a-zA-Z0-9_-]+)/edit$#', $path, $m)) {
        $shopSlug = $m[1];
        $__pageFile = BASE_PATH . '/plugins/vos-shop/views/customer/shop/edit.php';
    } elseif (file_exists(BASE_PATH . '/plugins/vos-shop/plugin.json') && preg_match('#^shop/([a-zA-Z0-9_-]+)$#', $path, $m)) {
        $shopSlug = $m[1];
        $__pageFile = BASE_PATH . '/plugins/vos-shop/views/customer/shop/detail.php';
    } elseif (file_exists(BASE_PATH . '/plugins/vos-shop/plugin.json') && ($path === 'shops' || preg_match('#^shops/([a-zA-Z0-9_-]+)$#', $path, $m))) {
        $shopCategory = $m[1] ?? '';
        $__pageFile = BASE_PATH . '/plugins/vos-shop/views/customer/shop/list.php';
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
                if (preg_match('#^([a-zA-Z0-9_-]+)/(settings|edit)$#', $path, $_prm)) {
                    $_prSlug = $_prm[1];
                    $_prAction = $_prm[2];
                    $_prCheck = $pdo->prepare("SELECT page_slug FROM {$_pfx}page_contents WHERE page_slug = ? LIMIT 1");
                    $_prCheck->execute([$_prSlug]);
                    if ($_prCheck->fetchColumn()) {
                        $pageSlug = $_prSlug;
                        if ($_prAction === 'settings') {
                            $__pageFile = BASE_PATH . '/resources/views/customer/page-settings.php';
                        } else {
                            // 위젯/시스템 타입이면 위젯 빌더, 그 외 콘텐츠 편집
                            $_ptChk = $pdo->prepare("SELECT page_type FROM {$_pfx}page_contents WHERE page_slug = ? LIMIT 1");
                            $_ptChk->execute([$_prSlug]);
                            $_ptType = $_ptChk->fetchColumn();
                            if ($_ptType === 'widget' || $_ptType === 'system') {
                                $__pageFile = BASE_PATH . '/resources/views/customer/page-widget-builder.php';
                            } else {
                                $__pageFile = BASE_PATH . '/resources/views/customer/page-edit.php';
                            }
                        }
                        $_pageRouteMatch = true;
                    }
                }

                if (!$_pageRouteMatch) {
                    // 시스템 페이지 확인 (config/system-pages.php)
                    $_spConfig = file_exists(BASE_PATH . '/config/system-pages.php') ? include(BASE_PATH . '/config/system-pages.php') : [];
                    foreach ($_spConfig as $_sp) {
                        if (($_sp['slug'] ?? '') === $path && !empty($_sp['view'])) {
                            $__noLayout = true;
                            include BASE_PATH . '/resources/views/' . $_sp['view'];
                            exit;
                        }
                    }

                    // 동적 페이지 확인 (rzx_page_contents)
                    $_pageCheck = $pdo->prepare("SELECT page_slug FROM {$_pfx}page_contents WHERE page_slug = ? LIMIT 1");
                    $_pageCheck->execute([$path]);
                    if ($_pageCheck->fetchColumn()) {
                        $pageSlug = $path;
                        $__pageFile = BASE_PATH . '/resources/views/customer/page.php';
                    } else {
                        // 플러그인 프론트/API 라우트 매칭
                        $_pluginFrontMatch = false;
                        if (isset($pluginManager)) {
                            foreach ($pluginManager->getRoutes() as $_pr) {
                                if (in_array($_pr['type'], ['front', 'api']) && $path === $_pr['path']) {
                                    if (file_exists($_pr['view_path'])) {
                                        if ($_pr['type'] === 'api') $__noLayout = true;
                                        $__pageFile = $_pr['view_path'];
                                        $_pluginFrontMatch = true;
                                    }
                                    break;
                                }
                            }
                        }
                        if (!$_pluginFrontMatch) {
                            http_response_code(404);
                            $__noLayout = true;
                            include BASE_PATH . '/resources/views/customer/404.php';
                        }
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

    // 게시판 개별 레이아웃 조회 (board_config_{slug}.layout)
    // 개별 설정이 'inherit' 또는 미설정이면 전체 설정 유지, 명시적 지정 시에만 개별 적용
    if (!empty($boardSlug) && isset($pdo)) {
        $__bcKey = 'board_config_' . $boardSlug;
        if (isset($siteSettings[$__bcKey])) {
            $__boardConfig = json_decode($siteSettings[$__bcKey], true) ?: [];
        } else {
            $__pfx = $_ENV['DB_PREFIX'] ?? 'rzx_';
            $__bcStmt = $pdo->prepare("SELECT value FROM {$__pfx}settings WHERE `key` = ?");
            $__bcStmt->execute([$__bcKey]);
            $__boardConfig = json_decode($__bcStmt->fetchColumn() ?: '{}', true) ?: [];
        }
        $__boardLayout = $__boardConfig['layout'] ?? '';
        if ($__boardLayout && $__boardLayout !== 'inherit') {
            $__layout = $__boardLayout;
        }
    }

    // 페이지 개별 레이아웃 조회 (page_config_{slug}.layout)
    // 개별 설정이 'inherit' 또는 미설정이면 전체 설정 유지, 명시적 지정 시에만 개별 적용
    if (!empty($pageSlug) && isset($pdo)) {
        $__pcKey = 'page_config_' . $pageSlug;
        if (isset($siteSettings[$__pcKey])) {
            $__pageConfig = json_decode($siteSettings[$__pcKey], true) ?: [];
        } else {
            $__pcStmt = $pdo->prepare("SELECT value FROM " . ($_ENV['DB_PREFIX'] ?? 'rzx_') . "settings WHERE `key` = ?");
            $__pcStmt->execute([$__pcKey]);
            $__pageConfig = json_decode($__pcStmt->fetchColumn() ?: '{}', true) ?: [];
        }
        $__pageLayout = $__pageConfig['layout'] ?? '';
        if ($__pageLayout && $__pageLayout !== 'inherit') {
            $__layout = $__pageLayout;
        }
    }

    // none이면 레이아웃 미사용
    if ($__layout === 'none') $__layout = false;

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

    // no_layout=1: 외부 iframe / 미리보기용 → 콘텐츠만 출력 (CSS 포함)
    if (!empty($_GET['no_layout'])) {
        echo '<!DOCTYPE html><html lang="' . ($config['locale'] ?? 'ko') . '"><head><meta charset="UTF-8"><meta name="viewport" content="width=device-width,initial-scale=1">';
        echo '<link href="' . ($config['app_url'] ?? '') . '/assets/css/app.css" rel="stylesheet">';
        echo '<script src="https://cdn.tailwindcss.com"></script>';
        echo '</head><body class="bg-white dark:bg-zinc-900">';
        echo $__content;
        echo '</body></html>';
    } else {
        // 레이아웃 결정: 개별 설정 → 사이트 기본값 (none이면 콘텐츠만 출력)
        $__siteLayout = $siteSettings['site_layout'] ?? 'default';
        if ($__layout === false) {
            // none이 명시적으로 설정된 경우 → 레이아웃 없이 콘텐츠만 출력
            echo $__content;
            exit;
        }

        // 스킨 레이아웃 설정값 로드
        $__layoutConfig = [];
        $__lcKey = 'skin_detail_layout_' . $__layout;
        if (isset($siteSettings[$__lcKey])) {
            $__layoutConfig = json_decode($siteSettings[$__lcKey], true) ?: [];
        }
        $content = $__content;

        // 레이아웃 header/footer 사용
        $__layoutDir = BASE_PATH . '/skins/layouts/' . $__layout;
        $__headerFile = $__layoutDir . '/header.php';
        $__footerFile = $__layoutDir . '/footer.php';

        if (file_exists($__headerFile)) {
            include $__headerFile;
        }
        echo $content;
        if (file_exists($__footerFile)) {
            include $__footerFile;
        }
    }
}
