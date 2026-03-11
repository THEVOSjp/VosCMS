<?php
/**
 * RezlyX Default Theme - Header Component
 *
 * DB 메뉴 기반 반응형 네비게이션 (다국어 지원, 드롭다운)
 */
$appName = $config['app_name'] ?? 'RezlyX';
$baseUrl = $baseUrl ?? $config['app_url'] ?? '';
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);

// 메뉴 데이터 로드
include_once BASE_PATH . '/resources/views/components/menu-loader.php';
$mainMenu = $siteMenus['Main Menu'] ?? [];

// 로그인 상태 확인
$isUserLoggedIn = false;
if (function_exists('auth')) {
    try { $isUserLoggedIn = auth()->check(); } catch (\Throwable $e) {
        if (session_status() === PHP_SESSION_ACTIVE) $isUserLoggedIn = !empty($_SESSION['user_id']);
    }
} elseif (session_status() === PHP_SESSION_ACTIVE) {
    $isUserLoggedIn = !empty($_SESSION['user_id']);
}

if (!isset($siteSettings)) $siteSettings = [];
?>
<header class="bg-white dark:bg-zinc-800 shadow-sm sticky top-0 z-50 transition-colors duration-200" id="rzxHeader">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Logo -->
            <div class="flex items-center">
                <a href="<?= $baseUrl ?>/" class="flex items-center space-x-2">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="text-xl font-bold text-blue-600 dark:text-blue-400"><?= htmlspecialchars($appName) ?></span>
                </a>
            </div>

            <!-- Desktop Navigation -->
            <nav class="hidden md:flex items-center space-x-1">
                <?php foreach ($mainMenu as $item):
                    $href = rzxMenuUrl($item, $baseUrl);
                    $isActive = rzxIsActive($item, $currentPath, $baseUrl);
                    $hasChildren = !empty($item['children']);
                    $activeClass = $isActive
                        ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30'
                        : 'text-zinc-600 dark:text-zinc-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-zinc-100 dark:hover:bg-zinc-700';
                ?>
                <?php if ($hasChildren): ?>
                <div class="relative group">
                    <a href="<?= htmlspecialchars($href) ?>"
                       class="px-4 py-2 text-sm font-medium rounded-lg transition-colors inline-flex items-center gap-1 <?= $activeClass ?>">
                        <?= htmlspecialchars($item['title']) ?>
                        <svg class="w-3.5 h-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                    </a>
                    <div class="absolute left-0 top-full pt-1 hidden group-hover:block z-50">
                        <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg py-1 min-w-[180px]">
                            <?php foreach ($item['children'] as $child):
                                $childHref = rzxMenuUrl($child, $baseUrl);
                                $childActive = rzxIsActive($child, $currentPath, $baseUrl);
                            ?>
                            <a href="<?= htmlspecialchars($childHref) ?>"
                               class="block px-4 py-2 text-sm transition-colors <?= $childActive ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30' : 'text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700' ?>">
                                <?= htmlspecialchars($child['title']) ?>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <?php else: ?>
                <a href="<?= htmlspecialchars($href) ?>"
                   class="px-4 py-2 text-sm font-medium rounded-lg transition-colors <?= $activeClass ?>">
                    <?= htmlspecialchars($item['title']) ?>
                </a>
                <?php endif; ?>
                <?php endforeach; ?>
            </nav>

            <!-- Right side controls -->
            <div class="flex items-center space-x-2">
                <!-- Language Selector -->
                <?php include BASE_PATH . '/resources/views/components/language-selector.php'; ?>

                <!-- Dark Mode Toggle -->
                <button type="button" id="darkModeBtn"
                        class="p-2 text-zinc-600 dark:text-zinc-300 hover:text-blue-600 dark:hover:text-blue-400 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                    <svg id="sunIcon" class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <svg id="moonIcon" class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                </button>

                <!-- Auth -->
                <?php if ($isUserLoggedIn): ?>
                    <a href="<?= $baseUrl ?>/my" class="p-2 text-zinc-600 dark:text-zinc-300 hover:text-blue-600 dark:hover:text-blue-400 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </a>
                <?php else: ?>
                    <a href="<?= $baseUrl ?>/login"
                       class="hidden sm:inline-flex items-center px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors shadow-sm">
                        <?= __('common.buttons.login') ?>
                    </a>
                <?php endif; ?>

                <!-- Mobile menu button -->
                <button type="button" id="mobileMenuBtn"
                        class="md:hidden p-2 text-zinc-600 dark:text-zinc-300 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                    <svg id="menuOpenIcon" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg id="menuCloseIcon" class="w-6 h-6 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation -->
    <div id="mobileMenu" class="hidden md:hidden bg-white dark:bg-zinc-800 border-t border-zinc-200 dark:border-zinc-700">
        <div class="px-4 py-3 space-y-1">
            <?php foreach ($mainMenu as $item):
                $href = rzxMenuUrl($item, $baseUrl);
                $hasChildren = !empty($item['children']);
            ?>
            <a href="<?= htmlspecialchars($href) ?>"
               class="block px-4 py-3 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition-colors">
                <?= htmlspecialchars($item['title']) ?>
            </a>
            <?php if ($hasChildren): ?>
                <?php foreach ($item['children'] as $child):
                    $childHref = rzxMenuUrl($child, $baseUrl);
                ?>
                <a href="<?= htmlspecialchars($childHref) ?>"
                   class="block px-4 py-3 pl-8 text-sm text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition-colors">
                    <?= htmlspecialchars($child['title']) ?>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php endforeach; ?>

            <?php if (!$isUserLoggedIn): ?>
                <div class="pt-2 border-t border-zinc-200 dark:border-zinc-700">
                    <a href="<?= $baseUrl ?>/login"
                       class="block px-4 py-3 text-center text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors font-medium">
                        <?= __('common.buttons.login') ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>

<script>
(function() {
    var darkModeBtn = document.getElementById('darkModeBtn');
    var mobileMenuBtn = document.getElementById('mobileMenuBtn');
    var mobileMenu = document.getElementById('mobileMenu');
    var menuOpenIcon = document.getElementById('menuOpenIcon');
    var menuCloseIcon = document.getElementById('menuCloseIcon');

    if (darkModeBtn) {
        darkModeBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            var isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('darkMode', isDark ? 'true' : 'false');
            console.log('[Header] Dark mode:', isDark);
        });
    }

    if (mobileMenuBtn && mobileMenu) {
        mobileMenuBtn.addEventListener('click', function() {
            var isHidden = mobileMenu.classList.toggle('hidden');
            if (menuOpenIcon) menuOpenIcon.classList.toggle('hidden', !isHidden);
            if (menuCloseIcon) menuCloseIcon.classList.toggle('hidden', isHidden);
            console.log('[Header] Mobile menu toggled');
        });
    }

    window.toggleDarkMode = window.toggleDarkMode || function() {
        var isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('darkMode', isDark ? 'true' : 'false');
    };
})();
</script>
