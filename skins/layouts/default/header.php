<?php
/**
 * RezlyX 기본 레이아웃 - 헤더
 *
 * 사용법: include BASE_PATH . '/resources/views/layouts/base-header.php';
 * 필요 변수: $config, $siteSettings (선택), $pageTitle (선택)
 * 제공 변수: $baseUrl, $siteName, $currentLocale, $isLoggedIn, $currentUser
 */

// 초기화
require_once BASE_PATH . '/rzxlib/Core/I18n/Translator.php';
use RzxLib\Core\I18n\Translator;
if (session_status() === PHP_SESSION_NONE) session_start();
Translator::init(BASE_PATH . '/resources/lang');
$currentLocale = current_locale();

$helpersPath = BASE_PATH . '/rzxlib/Core/Helpers/functions.php';
if (file_exists($helpersPath) && !function_exists('get_site_tagline')) require_once $helpersPath;

require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
$isLoggedIn = \RzxLib\Core\Auth\Auth::check();
if (!isset($currentUser)) $currentUser = $isLoggedIn ? \RzxLib\Core\Auth\Auth::user() : null;
if ($currentUser && !empty($currentUser['name']) && str_starts_with($currentUser['name'], 'enc:')) {
    require_once BASE_PATH . '/rzxlib/Core/Helpers/Encryption.php';
    $currentUser['name'] = \RzxLib\Core\Helpers\Encryption::decrypt($currentUser['name']) ?: $currentUser['email'] ?? '';
}

$siteName = function_exists('get_site_name') ? get_site_name() : ($config['app_name'] ?? 'RezlyX');
$baseUrl = rtrim($config['app_url'] ?? '', '/');
if (!isset($siteSettings)) $siteSettings = [];

$logoType = $siteSettings['logo_type'] ?? 'text';

// 레이아웃 설정 ($__layoutConfig: index.php에서 로드)
$_lc = $__layoutConfig ?? [];
$_headerStyle = $_lc['header_style'] ?? 'fixed';
$_contentWidth = $_lc['content_width'] ?? 'max-w-7xl';
$_primaryColor = $_lc['primary_color'] ?? '#3B82F6';
$_darkMode = $_lc['dark_mode'] ?? 'auto';
$_menuFixed = ($_lc['menu_fixed'] ?? '1') === '1';
$_showSearch = ($_lc['show_search'] ?? '1') === '1';
$_lcLogoImage = $_lc['logo_image'] ?? '';
$_headerScript = $_lc['header_script'] ?? '';
$_customCss = $_lc['custom_css'] ?? '';
// 로고 우선순위: 레이아웃 설정 > 사이트 설정(fallback). 레이아웃에 지정 없으면 사이트 로고 사용.
$logoImage = $_lcLogoImage ?: ($siteSettings['logo_image'] ?? '');
$pageTitle = $pageTitle ?? $siteName;

// SEO 헬퍼 로드
require_once BASE_PATH . '/rzxlib/Core/Helpers/seo.php';
$_seoCtx = $seoContext ?? [];
if (!isset($_seoCtx['type'])) {
    // 자동 타입 판별: pageTitle이 siteName과 같으면 main
    $_seoCtx['type'] = ($pageTitle === $siteName) ? 'main' : 'sub';
}
if (empty($_seoCtx['subpage_title']) && $pageTitle !== $siteName) {
    $_seoCtx['subpage_title'] = $pageTitle;
}
$_seo = rzx_seo_meta($siteSettings, $baseUrl, $siteName, $_seoCtx);
// 제목 패턴 적용 (seoContext가 있는 경우만, 없으면 기존 $pageTitle 유지)
if (isset($seoContext)) {
    $pageTitle = $_seo['title'];
}
// 메타 설명/키워드 fallback
if (empty($metaDescription) && $_seo['description']) {
    $metaDescription = $_seo['description'];
}
if (empty($metaKeywords)) {
    $metaKeywords = function_exists('db_trans') ? db_trans('settings.seo_keywords', null, '') : '';
    if (!$metaKeywords) $metaKeywords = $siteSettings['seo_keywords'] ?? '';
}
if ($_seo['keywords_extra'] && !empty($metaKeywords)) {
    $metaKeywords .= ', ' . $_seo['keywords_extra'];
} elseif ($_seo['keywords_extra']) {
    $metaKeywords = $_seo['keywords_extra'];
}

$_pwaS = $siteSettings;
$pwaFrontEnabled = ($_pwaS['pwa_front_enabled'] ?? '1') === '1';
$pwaFrontIcon = $_pwaS['pwa_front_icon'] ?? '';
$pwaFrontTheme = $_pwaS['pwa_front_theme_color'] ?? '#3b82f6';

// DB 메뉴 로드 (include_once가 아닌 include — 스킨 클로저에서 먼저 로드되었을 수 있음)
if (!isset($siteMenus) || empty($siteMenus)) {
    include BASE_PATH . '/resources/views/components/menu-loader.php';
}
$mainMenu = $siteMenus['Main Menu'] ?? [];
$currentPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH);
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($currentLocale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <?php if (!empty($metaKeywords)): ?><meta name="keywords" content="<?= htmlspecialchars($metaKeywords) ?>"><?php endif; ?>
    <?php if (!empty($metaDescription)): ?><meta name="description" content="<?= htmlspecialchars($metaDescription) ?>"><?php endif; ?>
    <?php if (!empty($metaRobots)): ?><meta name="robots" content="<?= $metaRobots ?>"><?php endif; ?>
    <meta name="base-url" content="<?= $baseUrl ?>">
    <link rel="icon" href="<?= !empty($siteSettings['favicon']) ? $baseUrl . htmlspecialchars($siteSettings['favicon']) : $baseUrl . '/assets/images/favicon.ico' ?>">
    <?php if ($pwaFrontEnabled): ?>
    <link rel="manifest" href="<?= $baseUrl ?>/manifest.json">
    <meta name="theme-color" content="<?= htmlspecialchars($pwaFrontTheme) ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <?php if ($pwaFrontIcon): ?><link rel="apple-touch-icon" href="<?= $baseUrl . htmlspecialchars($pwaFrontIcon) ?>"><?php endif; ?>
    <?php endif; ?>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <style>body { font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; margin: 0; padding: 0; }</style>
    <script>
    if (localStorage.getItem('darkMode') === 'true' || (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
        document.documentElement.classList.add('dark');
    }
    </script>
    <link rel="stylesheet" href="<?= $baseUrl ?>/resources/css/board-content.css?v=<?= @filemtime(__DIR__.'/../../../resources/css/board-content.css') ?: time() ?>">
    <script src="<?= $baseUrl ?>/resources/js/board-autolink.js" defer></script>
<?= $_seo['meta_tags'] ?>    <?php if (isset($headExtra)) echo $headExtra; ?>
</head>
<body class="bg-gray-50 dark:bg-zinc-900 min-h-screen flex flex-col transition-colors duration-200">
    <!-- Header -->
    <header class="bg-white dark:bg-zinc-800 shadow-sm dark:shadow-zinc-900/50 sticky top-0 z-50 transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="<?= $baseUrl ?>/" class="flex items-center text-xl font-bold text-blue-600 dark:text-blue-400">
                    <?php if ($logoType === 'image' && $logoImage): ?>
                        <img src="<?= $baseUrl . htmlspecialchars($logoImage) ?>" alt="<?= htmlspecialchars($siteName) ?>" class="h-10 object-contain">
                    <?php elseif ($logoType === 'image_text' && $logoImage): ?>
                        <img src="<?= $baseUrl . htmlspecialchars($logoImage) ?>" alt="" class="h-10 object-contain mr-2">
                        <span><?= htmlspecialchars($siteName) ?></span>
                    <?php else: ?>
                        <span><?= htmlspecialchars($siteName) ?></span>
                    <?php endif; ?>
                </a>
                <nav class="hidden lg:flex items-center space-x-1">
                    <?php foreach ($mainMenu as $__mi):
                        $__href = rzxMenuUrl($__mi, $baseUrl);
                        $__active = rzxIsActive($__mi, $currentPath, $baseUrl);
                        $__hasKids = !empty($__mi['children']);
                        $__cls = $__active ? 'text-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-zinc-300 hover:text-blue-600 dark:hover:text-blue-400';
                    ?>
                    <?php if ($__hasKids): ?>
                    <div class="relative group">
                        <a href="<?= htmlspecialchars($__href) ?>" class="px-3 py-2 font-medium inline-flex items-center gap-1 <?= $__cls ?>">
                            <?= htmlspecialchars($__mi['title']) ?>
                            <svg class="w-3.5 h-3.5 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </a>
                        <div class="absolute left-0 top-full pt-1 hidden group-hover:block z-50">
                            <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg py-1 min-w-[180px]">
                                <?php foreach ($__mi['children'] as $__ch): ?>
                                <a href="<?= htmlspecialchars(rzxMenuUrl($__ch, $baseUrl)) ?>" class="block px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700"><?= htmlspecialchars($__ch['title']) ?></a>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                    <?php else: ?>
                    <a href="<?= htmlspecialchars($__href) ?>" class="px-3 py-2 font-medium <?= $__cls ?>"><?= htmlspecialchars($__mi['title']) ?></a>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </nav>
                <div class="flex items-center space-x-3">
                    <button id="darkModeBtn" class="p-2 text-gray-600 dark:text-zinc-300 hover:text-blue-600 dark:hover:text-blue-400 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-700">
                        <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                        <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
                    </button>
                    <?php include BASE_PATH . '/resources/views/components/language-selector.php'; ?>
                    <?php if ($isLoggedIn): ?>
                    <div class="relative">
                        <button id="userMenuBtn" class="flex items-center space-x-2 px-3 py-2 text-sm font-medium text-gray-700 dark:text-zinc-300 hover:text-blue-600 dark:hover:text-blue-400 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-700">
                            <?php $_avatarUrl = $currentUser['profile_image'] ?? $currentUser['avatar'] ?? ''; if ($_avatarUrl && !str_starts_with($_avatarUrl, 'http')) $_avatarUrl = $baseUrl . $_avatarUrl; ?>
                            <?php if ($_avatarUrl): ?>
                            <img src="<?= htmlspecialchars($_avatarUrl) ?>" alt="" class="w-8 h-8 rounded-full object-cover border border-zinc-200 dark:border-zinc-600">
                            <?php else: ?>
                            <div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/50 rounded-full flex items-center justify-center">
                                <span class="text-blue-600 dark:text-blue-400 font-semibold"><?= mb_substr($currentUser['name'] ?? 'U', 0, 1) ?></span>
                            </div>
                            <?php endif; ?>
                            <span class="hidden sm:inline"><?= htmlspecialchars($currentUser['name'] ?? '') ?></span>
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                        </button>
                        <div id="userMenuDropdown" class="hidden absolute right-0 mt-2 w-48 bg-white dark:bg-zinc-800 rounded-lg shadow-lg border dark:border-zinc-700 py-1 z-50">
                            <div class="px-4 py-2 border-b dark:border-zinc-700">
                                <p class="text-sm font-medium text-gray-900 dark:text-white"><?= htmlspecialchars($currentUser['name'] ?? '') ?></p>
                                <p class="text-xs text-gray-500 dark:text-zinc-400"><?= htmlspecialchars($currentUser['email'] ?? '') ?></p>
                            </div>
<?php
                            $_udMenus = function_exists('load_menu') ? load_menu('user_dropdown') : [];
                            $_prevType = '';
                            foreach ($_udMenus as $_udi):
                                $_udLabel = is_string($_udi['label'] ?? '') ? $_udi['label'] : '';
                                $_udUrl = $_udi['url'] ?? '';
                                $_udType = $_udi['type'] ?? 'link';
                                $_udIcon = $_udi['icon'] ?? '';
                                if ($_udType === 'admin' && empty($_SESSION['admin_id'])) continue;
                                if ($_prevType && $_prevType !== $_udType): ?>
                            <div class="border-t dark:border-zinc-700"></div>
                            <?php endif; $_prevType = $_udType;
                                if ($_udType === 'danger'): ?>
                            <div class="border-t dark:border-zinc-700"></div>
                            <a href="<?= $baseUrl ?><?= $_udUrl ?>" class="block px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-gray-100 dark:hover:bg-zinc-700"><?= htmlspecialchars($_udLabel) ?></a>
                            <?php elseif ($_udType === 'admin'): ?>
                            <a href="<?= $baseUrl ?><?= $_udUrl ?>" class="flex items-center gap-2 px-4 py-2 text-sm text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20">
                                <?php if ($_udIcon): ?><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $_udIcon ?>"/></svg><?php endif; ?>
                                <?= htmlspecialchars($_udLabel) ?>
                            </a>
                            <?php else: ?>
                            <a href="<?= $baseUrl ?><?= $_udUrl ?>" class="<?= $_udIcon ? 'flex items-center gap-2' : 'block' ?> px-4 py-2 text-sm text-gray-700 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-700">
                                <?php if ($_udIcon): ?><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $_udIcon ?>"/></svg><?php endif; ?>
                                <?= htmlspecialchars($_udLabel) ?>
                            </a>
                            <?php endif;
                            endforeach; ?>
                        </div>
                    </div>
                    <?php else: ?>
                    <a href="<?= $baseUrl ?>/login" class="hidden lg:inline-flex px-4 py-2 text-sm font-medium text-gray-700 dark:text-zinc-300 hover:text-blue-600 dark:hover:text-blue-400"><?= __('common.buttons.login') ?></a>
                    <a href="<?= $baseUrl ?>/register" class="hidden lg:inline-flex px-4 py-2 text-sm font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600"><?= __('common.buttons.register') ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>

    <main class="flex-1">
