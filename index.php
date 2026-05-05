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

    // 전용 세션 디렉토리 (OS phpsessionclean cron의 24분 GC 회피)
    $_sessionDir = __DIR__ . '/storage/sessions';
    if (!is_dir($_sessionDir)) { @mkdir($_sessionDir, 0770, true); }
    ini_set('session.save_path', $_sessionDir);
    // VosCMS 자체 GC: 1/100 확률로 만료 세션 정리
    ini_set('session.gc_probability', '1');
    ini_set('session.gc_divisor', '100');

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

// 공용 메일 발송 헬퍼 (rzx_send_mail)
require_once BASE_PATH . '/rzxlib/Core/Helpers/mail.php';

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
if ($path === 'sitemap.xml') {
    include BASE_PATH . '/sitemap.php';
    exit;
} elseif ($path === 'manifest.json') {
    require_once BASE_PATH . '/rzxlib/Core/Helpers/pwa-manifest.php';
    pwa_serve_manifest('front');
    exit;
} elseif ($path === 'admin-manifest.json') {
    require_once BASE_PATH . '/rzxlib/Core/Helpers/pwa-manifest.php';
    pwa_serve_manifest('admin');
    exit;
} elseif ($path === 'robots.txt') {
    header('Content-Type: text/plain');
    $robotsCustom = $siteSettings['robots_txt'] ?? '';
    if ($robotsCustom) { echo $robotsCustom; }
    else {
        $appUrl = rtrim($config['app_url'] ?? '', '/');
        echo "User-agent: *\nAllow: /\n\nSitemap: {$appUrl}/sitemap.xml\n";
    }
    exit;
} elseif (empty($path) || $path === 'index.php' || $path === 'home') {
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
    // 관리자 라우팅 (AdminRouter.php로 분리)
    include BASE_PATH . '/rzxlib/Core/Router/AdminRouter.php';
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
    } elseif ($path === 'board/api/url-capture') {
        $__noLayout = true;
        include BASE_PATH . '/resources/views/customer/board/api-url-capture.php';
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
    // mypage/services 단순 라우트는 vos-hosting plugin.json routes.front 가 PluginManager 통해 자동 매칭
    // mypage/services/{order} 정규식 라우트는 코어가 plugin view 로 직접 dispatch (plugin 없으면 마이페이지로 redirect)
    } elseif (preg_match('#^mypage/services/([a-zA-Z0-9_-]+)$#', $path, $m)) {
        $serviceOrderNumber = $m[1];
        $_pf = BASE_PATH . '/plugins/vos-hosting/views/customer/mypage/service-detail.php';
        if (file_exists($_pf)) {
            $__pageFile = $_pf;
        } else {
            header('Location: ' . $basePath . '/mypage'); exit;
        }
    // mypage/custom-projects/new — 의뢰 폼
    } elseif ($path === 'mypage/custom-projects/new') {
        $_pf = BASE_PATH . '/plugins/vos-hosting/views/customer/mypage/custom-project-new.php';
        if (file_exists($_pf)) { $__pageFile = $_pf; }
        else { header('Location: ' . $basePath . '/mypage'); exit; }
    // mypage/custom-projects/{id} — 프로젝트 상세 + 견적 확인/승인
    } elseif (preg_match('#^mypage/custom-projects/(\d+)$#', $path, $m)) {
        $customProjectId = (int)$m[1];
        $_pf = BASE_PATH . '/plugins/vos-hosting/views/customer/mypage/custom-project-detail.php';
        if (file_exists($_pf)) { $__pageFile = $_pf; }
        else { header('Location: ' . $basePath . '/mypage'); exit; }
    // /service-request — 비회원도 접근 가능한 마케팅 랜딩 (호스팅 사업자용)
    } elseif ($path === 'service-request') {
        $_pf = BASE_PATH . '/plugins/vos-hosting/views/front/service-request.php';
        if (file_exists($_pf)) { $__pageFile = $_pf; }
        else { http_response_code(404); }
    } elseif ($path === 'mypage/password') {
        $__pageFile = BASE_PATH . '/resources/views/customer/mypage/password.php';
    } elseif ($path === 'mypage/settings') {
        $__pageFile = BASE_PATH . '/resources/views/customer/mypage/settings.php';
    } elseif ($path === 'mypage/withdraw') {
        $__pageFile = BASE_PATH . '/resources/views/customer/mypage/withdraw.php';
    } elseif ($path === 'mypage/messages') {
        // vos-community 플러그인이 활성이면 플러그인 view, 아니면 코어 (있을 경우)
        $_pluginPath = BASE_PATH . '/plugins/vos-community/views/customer/mypage/messages.php';
        $_corePath = BASE_PATH . '/resources/views/customer/mypage/messages.php';
        $__pageFile = file_exists($_pluginPath) ? $_pluginPath : $_corePath;
    // 공개 프로필 페이지 (vos-community 플러그인)
    } elseif (preg_match('#^profile/([a-f0-9-]{36})$#', $path, $m)) {
        $profileUserId = $m[1];
        $_pf = BASE_PATH . '/plugins/vos-community/views/customer/profile.php';
        if (file_exists($_pf)) { $__pageFile = $_pf; }
        else { http_response_code(404); }
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
                if (preg_match('#^(.+?)/(settings|edit)$#', $path, $_prm)) {
                    $_prSlug = $_prm[1];
                    $_prAction = $_prm[2];

                    // 1. DB 에서 먼저 페이지 타입 조회 (document/widget/external)
                    $_ptChk = $pdo->prepare("SELECT page_type FROM {$_pfx}page_contents WHERE page_slug = ? LIMIT 1");
                    $_ptChk->execute([$_prSlug]);
                    $_ptType = $_ptChk->fetchColumn() ?: '';
                    $_sysPageDef = null;

                    // 2. DB 에 없으면 config/system-pages.php 에서 시스템 페이지 검색 (type=system)
                    if (!$_ptType) {
                        $_spCheck = file_exists(BASE_PATH . '/config/system-pages.php')
                            ? include(BASE_PATH . '/config/system-pages.php') : [];
                        foreach ($_spCheck as $_sp) {
                            if (($_sp['slug'] ?? '') === $_prSlug) {
                                $_ptType = $_sp['type'] ?? 'system';
                                $_sysPageDef = $_sp;
                                break;
                            }
                        }
                    }

                    if ($_ptType) {
                        $pageSlug = $_prSlug;
                        if ($_prAction === 'settings') {
                            $__pageFile = BASE_PATH . '/resources/views/customer/page-settings.php';
                        } elseif ($_ptType === 'system' && $_sysPageDef && !empty($_sysPageDef['edit_view'])) {
                            // 시스템 페이지 편집: 전용 뷰 파일 직접 include (데이터 관리 UI)
                            // 뷰 파일이 자체적으로 layout header/footer include 하므로 여기서 바로 실행.
                            $_sysEditView = BASE_PATH . '/resources/views/' . $_sysPageDef['edit_view'];
                            if (file_exists($_sysEditView)) {
                                include $_sysEditView;
                                exit;
                            }
                            http_response_code(404);
                            echo 'Edit view not found: ' . htmlspecialchars($_sysPageDef['edit_view']);
                            exit;
                        } elseif ($_ptType === 'system' && $_sysPageDef && !empty($_sysPageDef['edit'])) {
                            // 레거시 fallback: edit 필드를 외부 URL 로 쓰는 경우 리다이렉트
                            $_sysEditUrl = str_replace('{admin}',
                                rtrim($config['app_url'] ?? '', '/') . '/' . ($config['admin_path'] ?? 'theadmin'),
                                $_sysPageDef['edit']);
                            if ($_sysEditUrl[0] === '/') {
                                $_sysEditUrl = rtrim($config['app_url'] ?? '', '/') . $_sysEditUrl;
                            }
                            header('Location: ' . $_sysEditUrl);
                            exit;
                        } elseif ($_ptType === 'widget' || $_ptType === 'system') {
                            $__pageFile = BASE_PATH . '/resources/views/customer/page-widget-builder.php';
                        } else {
                            $__pageFile = BASE_PATH . '/resources/views/customer/page-edit.php';
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
                            // plugin 지정된 경우 plugin view 우선, 코어 fallback
                            if (!empty($_sp['plugin'])) {
                                $_pf = BASE_PATH . '/plugins/' . $_sp['plugin'] . '/views/' . $_sp['view'];
                                if (file_exists($_pf)) { include $_pf; exit; }
                            }
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
