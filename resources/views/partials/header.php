<?php
/**
 * RezlyX Common Header
 *
 * 필요한 변수:
 * - $config: 앱 설정 배열
 * - $pageTitle: 페이지 제목
 * - $baseUrl: 기본 URL
 * - $currentUser: 로그인한 사용자 정보 (optional)
 * - $isLoggedIn: 로그인 상태 (optional)
 */

// 변수 기본값 설정
if (!isset($baseUrl) || empty($baseUrl)) {
    // config에서 app_url의 경로 부분만 추출
    if (!empty($config['app_url'])) {
        $parsedUrl = parse_url($config['app_url']);
        $baseUrl = rtrim($parsedUrl['path'] ?? '', '/');
    } else {
        // 자동으로 baseUrl 감지
        $scriptDir = dirname($_SERVER['SCRIPT_NAME']);
        $baseUrl = ($scriptDir !== '/' && $scriptDir !== '\\') ? $scriptDir : '';
    }
}

// DB에서 로고 설정 불러오기
$logoSettings = [];
if (!isset($siteSettings)) {
    try {
        $pdo = new PDO(
            'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx'),
            $_ENV['DB_USERNAME'] ?? 'root',
            $_ENV['DB_PASSWORD'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $stmt = $pdo->query("SELECT `key`, `value` FROM rzx_settings WHERE `key` IN ('site_name', 'logo_type', 'logo_image')");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $logoSettings[$row['key']] = $row['value'];
        }
    } catch (PDOException $e) {
        // 오류 시 기본값 사용
    }
} else {
    $logoSettings = $siteSettings;
}

// 로고 설정값
$siteName = function_exists('get_site_name') ? get_site_name() : ($logoSettings['site_name'] ?? ($config['app_name'] ?? 'RezlyX'));
$logoType = $logoSettings['logo_type'] ?? 'text';
$logoImage = $logoSettings['logo_image'] ?? '';
$currentLocale = function_exists('current_locale') ? current_locale() : ($config['locale'] ?? 'ko');

// $siteSettings 보장 (공용 언어 선택기용)
if (!isset($siteSettings)) $siteSettings = [];

// 로그인 상태 확인
if (!isset($isLoggedIn)) {
    require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
    $isLoggedIn = \RzxLib\Core\Auth\Auth::check();
    $currentUser = $isLoggedIn ? \RzxLib\Core\Auth\Auth::user() : null;
}
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($currentLocale); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle ?? $siteName); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <style>
        body { font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; }
    </style>
    <script>
        // 다크 모드 초기화
        if (localStorage.getItem('darkMode') === 'true' ||
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-gray-50 dark:bg-zinc-900 min-h-screen transition-colors duration-200">
    <!-- Header -->
    <header class="bg-white dark:bg-zinc-800 shadow-sm dark:shadow-zinc-900/50 sticky top-0 z-50 transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="<?php echo $baseUrl; ?>/" class="flex items-center text-xl font-bold text-blue-600 dark:text-blue-400">
                    <?php if ($logoType === 'image' && $logoImage): ?>
                        <img src="<?php echo $baseUrl . htmlspecialchars($logoImage); ?>" alt="<?php echo htmlspecialchars($siteName); ?>" class="h-10 object-contain">
                    <?php elseif ($logoType === 'image_text' && $logoImage): ?>
                        <img src="<?php echo $baseUrl . htmlspecialchars($logoImage); ?>" alt="" class="h-10 object-contain mr-2">
                        <span><?php echo htmlspecialchars($siteName); ?></span>
                    <?php else: ?>
                        <span><?php echo htmlspecialchars($siteName); ?></span>
                    <?php endif; ?>
                </a>
                <?php
                // DB 메뉴 로드
                include_once BASE_PATH . '/resources/views/components/menu-loader.php';
                $mainMenu = $siteMenus['Main Menu'] ?? [];
                $currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
                ?>
                <nav class="hidden md:flex items-center space-x-1">
                    <?php foreach ($mainMenu as $__mi):
                        $__href = rzxMenuUrl($__mi, $baseUrl);
                        $__active = rzxIsActive($__mi, $currentPath, $baseUrl);
                        $__hasKids = !empty($__mi['children']);
                        $__cls = $__active
                            ? 'text-blue-600 dark:text-blue-400'
                            : 'text-gray-600 dark:text-zinc-300 hover:text-blue-600 dark:hover:text-blue-400';
                    ?>
                    <?php if ($__hasKids): ?>
                    <div class="relative group">
                        <a href="<?= htmlspecialchars($__href) ?>" class="px-3 py-2 font-medium inline-flex items-center gap-1 <?= $__cls ?>">
                            <?= htmlspecialchars($__mi['title']) ?>
                            <svg class="w-3.5 h-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </a>
                        <div class="absolute left-0 top-full pt-1 hidden group-hover:block z-50">
                            <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg py-1 min-w-[180px]">
                                <?php foreach ($__mi['children'] as $__ch):
                                    $__chHref = rzxMenuUrl($__ch, $baseUrl);
                                ?>
                                <a href="<?= htmlspecialchars($__chHref) ?>" class="block px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700">
                                    <?= htmlspecialchars($__ch['title']) ?>
                                </a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <a href="<?= htmlspecialchars($__href) ?>" class="px-3 py-2 font-medium <?= $__cls ?>">
                        <?= htmlspecialchars($__mi['title']) ?>
                    </a>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </nav>
                <div class="flex items-center space-x-3">
                    <!-- 공용 언어 선택기 -->
                    <?php include BASE_PATH . '/resources/views/components/language-selector.php'; ?>
                    <!-- 다크모드 토글 -->
                    <button id="darkModeBtn" class="p-2 text-gray-600 dark:text-zinc-300 hover:text-blue-600 dark:hover:text-blue-400 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-700">
                        <svg id="sunIcon" class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <svg id="moonIcon" class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </button>
                    <?php if ($isLoggedIn): ?>
                        <?php include BASE_PATH . '/resources/views/components/notif-bell.php'; ?>
                        <!-- 로그인 상태: 사용자 메뉴 -->
                        <div class="relative">
                            <button id="userMenuBtn" class="flex items-center space-x-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-zinc-300 hover:text-blue-600 dark:hover:text-blue-400 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-700">
                                <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center">
                                    <span class="text-blue-600 dark:text-blue-400 font-semibold"><?= mb_substr($currentUser['name'] ?? 'U', 0, 1) ?></span>
                                </div>
                                <span class="hidden sm:inline"><?= htmlspecialchars($currentUser['name'] ?? '') ?></span>
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                                </svg>
                            </button>
                            <div id="userMenuDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-zinc-800 rounded-lg shadow-lg border dark:border-zinc-700 py-1 z-50">
                                <div class="px-4 py-2 border-b dark:border-zinc-700">
                                    <p class="text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($currentUser['name'] ?? '') ?></p>
                                    <p class="text-xs text-gray-500 dark:text-zinc-400"><?= htmlspecialchars($currentUser['email'] ?? '') ?></p>
                                </div>
                                <a href="<?= $baseUrl ?>/mypage" class="block px-4 py-2 text-sm text-gray-700 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-700"><?= __('common.nav.mypage') ?></a>
                                <a href="<?= $baseUrl ?>/mypage/reservations" class="block px-4 py-2 text-sm text-gray-700 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-700"><?= __('auth.mypage.menu.reservations') ?></a>
                                <div class="border-t dark:border-zinc-700"></div>
                                <a href="<?= $baseUrl ?>/logout" class="block px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-zinc-700"><?= __('common.buttons.logout') ?></a>
                            </div>
                        </div>
                    <?php else: ?>
                        <!-- 비로그인 상태: 로그인/회원가입 버튼 -->
                        <a href="<?php echo $baseUrl; ?>/login" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-zinc-300 hover:text-blue-600 dark:hover:text-blue-400"><?= __('common.buttons.login') ?></a>
                        <a href="<?php echo $baseUrl; ?>/register" class="px-4 py-2 text-sm font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"><?= __('common.buttons.register') ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
