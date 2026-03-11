<?php
/**
 * RezlyX Customer Home Page (다국어 지원)
 */

// 인증 헬퍼 로드
require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
use RzxLib\Core\Auth\Auth;

// 현재 로케일 가져오기
$currentLocale = function_exists('current_locale') ? current_locale() : ($config['locale'] ?? 'ko');
$localeLabels = ['ko' => 'KO', 'en' => 'EN', 'ja' => 'JA'];
$currentLocaleLabel = $localeLabels[$currentLocale] ?? 'KO';

// 번역 함수 확인
if (!function_exists('__')) {
    function __($key, $replace = []) { return $key; }
}

// 헬퍼 함수 로드
$helpersPath = BASE_PATH . '/rzxlib/Core/Helpers/functions.php';
if (file_exists($helpersPath) && !function_exists('get_site_tagline')) {
    require_once $helpersPath;
}

// 사이트 이름과 로고 설정 (다국어 지원)
$siteName = function_exists('get_site_name') ? get_site_name() : ($siteSettings['site_name'] ?? ($config['app_name'] ?? 'RezlyX'));
$logoType = $siteSettings['logo_type'] ?? 'text';
$logoImage = $siteSettings['logo_image'] ?? '';

// 사이트 타이틀 (site_tagline이 있으면 사용)
$siteTagline = function_exists('get_site_tagline') ? get_site_tagline() : '';
if (!empty($siteTagline)) {
    $pageTitle = $siteName . ' - ' . $siteTagline;
} else {
    $pageTitle = $siteName . ' - ' . __('common.nav.home');
}

// baseUrl 경로만 추출
if (!empty($config['app_url'])) {
    $parsedUrl = parse_url($config['app_url']);
    $baseUrl = rtrim($parsedUrl['path'] ?? '', '/');
} else {
    $baseUrl = '';
}

// 로그인 상태 확인
$isLoggedIn = Auth::check();
$currentUser = $isLoggedIn ? Auth::user() : null;

// $siteSettings 보장 (공용 언어 선택기용)
if (!isset($siteSettings)) $siteSettings = [];
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($currentLocale); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
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
                    <?php
                    if (!isset($siteSettings)) $siteSettings = [];
                    include BASE_PATH . '/resources/views/components/language-selector.php';
                    ?>
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

    <!-- Hero Section -->
    <section class="relative bg-gradient-to-br from-blue-600 to-blue-800 dark:from-blue-800 dark:to-dark-950 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
            <div class="text-center">
                <h1 class="text-4xl md:text-5xl font-bold mb-6">
                    <?= __('home.hero.title_1') ?><br>
                    <span class="text-blue-200"><?= __('home.hero.title_2') ?></span>
                </h1>
                <p class="text-xl text-blue-100 dark:text-blue-200 mb-8 max-w-2xl mx-auto">
                    <?= __('home.hero.subtitle') ?>
                </p>
                <a href="<?php echo $baseUrl; ?>/booking" class="inline-flex items-center px-8 py-4 bg-white dark:bg-gray-100 text-blue-600 font-semibold rounded-xl hover:bg-blue-50 dark:hover:bg-gray-200 transition shadow-lg">
                    <?= __('home.hero.cta_booking') ?>
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                    </svg>
                </a>
            </div>
        </div>
        <div class="absolute bottom-0 left-0 right-0 h-16 bg-gradient-to-t from-gray-50 dark:from-zinc-900"></div>
    </section>

    <!-- Development Info -->
    <?php if ($config['debug'] ?? false): ?>
    <section class="bg-yellow-50 dark:bg-yellow-900/20 border-b border-yellow-200 dark:border-yellow-800">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex items-center justify-center space-x-4 text-sm">
                <span class="px-2 py-1 bg-yellow-200 dark:bg-yellow-800 text-yellow-800 dark:text-yellow-200 rounded font-medium">DEV MODE</span>
                <span class="text-yellow-700 dark:text-yellow-400">PHP <?php echo PHP_VERSION; ?></span>
                <span class="text-yellow-700 dark:text-yellow-500">|</span>
                <span class="text-yellow-700 dark:text-yellow-400">Locale: <?php echo $currentLocale; ?></span>
                <span class="text-yellow-700 dark:text-yellow-500">|</span>
                <a href="<?php echo $baseUrl; ?>/<?php echo $config['admin_path']; ?>" class="text-yellow-800 dark:text-yellow-300 hover:underline font-medium"><?= __('common.nav.admin') ?> →</a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <!-- Features Section -->
    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4"><?= __('home.features.title') ?></h2>
                <p class="text-gray-600 dark:text-zinc-400"><?= __('home.features.subtitle') ?></p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <!-- Feature 1 -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm dark:shadow-zinc-900/50 p-8 text-center hover:shadow-lg dark:hover:shadow-dark-900/70 transition">
                    <div class="w-16 h-16 bg-blue-100 dark:bg-blue-900/50 rounded-xl flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3"><?= __('home.features.mobile.title') ?></h3>
                    <p class="text-gray-600 dark:text-zinc-400"><?= __('home.features.mobile.desc') ?></p>
                </div>

                <!-- Feature 2 -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm dark:shadow-zinc-900/50 p-8 text-center hover:shadow-lg dark:hover:shadow-dark-900/70 transition">
                    <div class="w-16 h-16 bg-green-100 dark:bg-green-900/50 rounded-xl flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3"><?= __('home.features.realtime.title') ?></h3>
                    <p class="text-gray-600 dark:text-zinc-400"><?= __('home.features.realtime.desc') ?></p>
                </div>

                <!-- Feature 3 -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm dark:shadow-zinc-900/50 p-8 text-center hover:shadow-lg dark:hover:shadow-dark-900/70 transition">
                    <div class="w-16 h-16 bg-purple-100 dark:bg-purple-900/50 rounded-xl flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3"><?= __('home.features.easy_payment.title') ?></h3>
                    <p class="text-gray-600 dark:text-zinc-400"><?= __('home.features.easy_payment.desc') ?></p>
                </div>
            </div>
        </div>
    </section>

    <!-- Footer -->
    <footer class="bg-white dark:bg-zinc-800 border-t dark:border-zinc-700 transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col md:flex-row items-center justify-between">
                <p class="text-gray-500 dark:text-zinc-400 text-sm">
                    <?= __('common.footer.copyright', ['year' => date('Y')]) ?>
                </p>
                <div class="flex items-center space-x-6 mt-4 md:mt-0">
                    <a href="<?php echo $baseUrl; ?>/terms" class="text-sm text-gray-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400"><?= __('common.footer.terms') ?></a>
                    <a href="<?php echo $baseUrl; ?>/privacy" class="text-sm text-gray-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400"><?= __('common.footer.privacy') ?></a>
                </div>
            </div>
        </div>
    </footer>

    <?php if ($config['debug'] ?? false): ?>
    <!-- Debug Info -->
    <div class="fixed bottom-4 right-4 bg-gray-900 dark:bg-zinc-700 text-white text-xs p-3 rounded-lg shadow-lg">
        <p><?php echo number_format((microtime(true) - REZLYX_START) * 1000, 2); ?>ms</p>
    </div>
    <?php endif; ?>

    <script>
        // 사용자 메뉴 드롭다운 토글
        const userMenuBtn = document.getElementById('userMenuBtn');
        const userMenuDropdown = document.getElementById('userMenuDropdown');

        if (userMenuBtn && userMenuDropdown) {
            userMenuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                userMenuDropdown.classList.toggle('hidden');
                console.log('[Home] 사용자 메뉴 드롭다운 토글');
            });
        }

        // 외부 클릭 시 사용자 메뉴 닫기
        document.addEventListener('click', () => {
            if (userMenuDropdown) userMenuDropdown.classList.add('hidden');
        });

        // 다크 모드 토글
        const darkModeBtn = document.getElementById('darkModeBtn');

        darkModeBtn.addEventListener('click', () => {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('darkMode', isDark);
            console.log('[Home] 다크 모드 토글:', isDark);
        });
    </script>
</body>
</html>
