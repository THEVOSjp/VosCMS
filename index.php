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
        $_locale = $config['locale'] ?? 'ko';
        // 서비스/번들/카테고리 번역 캐시 로드
        $_trMap = [];
        try {
            $_lcChain = array_unique([$_locale, 'en']);
            $_lcPh = implode(',', array_fill(0, count($_lcChain), '?'));
            $_trSt = $pdo->prepare("SELECT lang_key, locale, content FROM {$prefix}translations WHERE locale IN ({$_lcPh}) AND (lang_key LIKE 'service.%.name' OR lang_key LIKE 'bundle.%.name' OR lang_key LIKE 'category.%.name')");
            $_trSt->execute(array_values($_lcChain));
            while ($_t = $_trSt->fetch(PDO::FETCH_ASSOC)) $_trMap[$_t['lang_key']][$_t['locale']] = $_t['content'];
        } catch (\Throwable $e) {}
        $_trGet = function($type, $id, $fallback) use ($_trMap, $_locale) {
            $key = "{$type}.{$id}.name";
            if (isset($_trMap[$key])) {
                if (!empty($_trMap[$key][$_locale])) return $_trMap[$key][$_locale];
                if (!empty($_trMap[$key]['en'])) return $_trMap[$key]['en'];
            }
            return $fallback;
        };

        $stmt = $pdo->query("SELECT s.id, s.name, s.price, s.duration, s.image, s.category_id, c.name as category_name FROM {$prefix}services s LEFT JOIN {$prefix}service_categories c ON s.category_id = c.id WHERE s.is_active = 1 ORDER BY s.sort_order ASC, s.name ASC");
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        foreach ($services as &$sv) {
            $sv['name'] = $_trGet('service', $sv['id'], $sv['name']);
            if ($sv['category_name']) $sv['category_name'] = $_trGet('category', $sv['category_id'], $sv['category_name']);
            $sv['price_formatted'] = number_format((float)$sv['price']);
        }
        // 번들 목록
        $bundles = [];
        try {
            $bStmt = $pdo->query("SELECT b.id, b.name, b.bundle_price, b.image, b.description, COUNT(bi.service_id) as service_count, GROUP_CONCAT(bi.service_id) as service_ids FROM {$prefix}service_bundles b LEFT JOIN {$prefix}service_bundle_items bi ON b.id = bi.bundle_id WHERE b.is_active = 1 GROUP BY b.id ORDER BY b.display_order");
            while ($b = $bStmt->fetch(PDO::FETCH_ASSOC)) {
                $b['name'] = $_trGet('bundle', $b['id'], $b['name']);
                $b['price_formatted'] = number_format((float)$b['bundle_price']);
                $b['service_ids'] = $b['service_ids'] ? explode(',', $b['service_ids']) : [];
                $bundles[] = $b;
            }
        } catch (\Throwable $e) {}
        echo json_encode(['services' => $services, 'bundles' => $bundles]);
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
    } elseif ($adminRoute === 'reservations/remove-bundle' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json');
        $resId = trim($_POST['reservation_id'] ?? '');
        if ($resId) {
            try {
                $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
                // 번들 포함 서비스 삭제
                $pdo->prepare("DELETE FROM {$prefix}reservation_services WHERE reservation_id = ? AND bundle_id IS NOT NULL")->execute([$resId]);
                // reservations에서 번들 정보 제거
                $pdo->prepare("UPDATE {$prefix}reservations SET bundle_id = NULL, bundle_price = NULL WHERE id = ?")->execute([$resId]);
                // 남은 서비스 기준 금액 재계산
                $recalc = $pdo->prepare("SELECT COALESCE(SUM(price),0) as total, COALESCE(SUM(duration),0) as dur FROM {$prefix}reservation_services WHERE reservation_id = ?");
                $recalc->execute([$resId]);
                $sums = $recalc->fetch(PDO::FETCH_ASSOC);
                $pdo->prepare("UPDATE {$prefix}reservations SET total_amount = ?, final_amount = ?, updated_at = NOW() WHERE id = ?")
                    ->execute([(float)$sums['total'], (float)$sums['total'], $resId]);
                echo json_encode(['success' => true]);
            } catch (\Throwable $e) {
                echo json_encode(['error' => true, 'message' => $e->getMessage()]);
            }
        } else {
            echo json_encode(['error' => true, 'message' => 'Reservation ID required']);
        }
        exit;
    } elseif ($adminRoute === 'reservations/remove-service' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $apiAction = 'remove-service'; $apiId = null;
        include BASE_PATH . '/resources/views/admin/reservations/_api.php';
    } elseif ($adminRoute === 'reservations/save-memo' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $apiAction = 'save-memo'; $apiId = null;
        include BASE_PATH . '/resources/views/admin/reservations/_api.php';
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
        if ($__boardLayout !== '' && $__boardLayout !== 'inherit') {
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
        if ($__pageLayout !== '' && $__pageLayout !== 'inherit') {
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
        // 항상 사이트 레이아웃 적용 (페이지별 "사용 안함"이어도 사이트 레이아웃 사용)
        $__siteLayout = $siteSettings['site_layout'] ?? 'default';
        if ($__layout === false || $__layout === 'none') {
            $__layout = $__siteLayout;
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
