<?php
/**
 * Developer Dashboard - 공통 레이아웃 헤더
 * 다국어, 다크모드, 언어 변환기 포함
 */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$baseUrl = $_ENV['APP_URL'] ?? '';
$devLoggedIn = !empty($_SESSION['developer_id']);
$devName = $_SESSION['developer_name'] ?? '';
$devEmail = $_SESSION['developer_email'] ?? '';
$_devRoute = basename(parse_url($_SERVER['REQUEST_URI'] ?? 'dashboard', PHP_URL_PATH));

// 다국어 로드
$_mpLocale = $_SESSION['locale'] ?? ($_COOKIE['locale'] ?? ($_ENV['APP_LOCALE'] ?? 'ko'));
$_mpLangFile = BASE_PATH . '/resources/lang/' . $_mpLocale . '/marketplace.php';
if (!file_exists($_mpLangFile)) $_mpLangFile = BASE_PATH . '/resources/lang/en/marketplace.php';
$_mpLang = file_exists($_mpLangFile) ? require $_mpLangFile : [];

if (!function_exists('__mp')) {
    function __mp(string $key, string $default = ''): string {
        global $_mpLang;
        return $_mpLang[$key] ?? $default ?: $key;
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $_mpLocale ?>" class="">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? 'Developer') ?> - VosCMS Developer</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <style>body { font-family: 'Pretendard', -apple-system, sans-serif; }</style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13/dist/cdn.min.js"></script>
    <script>
        if (localStorage.getItem('darkMode') === 'true' || (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-zinc-50 dark:bg-zinc-900 min-h-screen transition-colors">
<!-- 네비게이션 -->
<nav class="bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
    <div class="max-w-6xl mx-auto px-6 h-14 flex items-center justify-between">
        <div class="flex items-center gap-6">
            <a href="<?= $baseUrl ?>/developer" class="font-bold text-lg text-zinc-800 dark:text-white">
                <span class="text-indigo-600">Vos</span>CMS <span class="text-sm font-normal text-zinc-400"><?= __mp('developer_portal') ?></span>
            </a>
            <?php if ($devLoggedIn): ?>
            <div class="hidden md:flex items-center gap-4 text-sm">
                <a href="<?= $baseUrl ?>/developer/dashboard" class="<?= $_devRoute === 'dashboard' ? 'text-indigo-600 font-medium' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' ?>"><?= __mp('dev_dashboard') ?></a>
                <a href="<?= $baseUrl ?>/developer/my-items" class="<?= $_devRoute === 'my-items' ? 'text-indigo-600 font-medium' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' ?>"><?= __mp('dev_my_items') ?></a>
                <a href="<?= $baseUrl ?>/developer/submit" class="<?= $_devRoute === 'submit' ? 'text-indigo-600 font-medium' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' ?>"><?= __mp('dev_submit') ?></a>
                <a href="<?= $baseUrl ?>/developer/earnings" class="<?= $_devRoute === 'earnings' ? 'text-indigo-600 font-medium' : 'text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300' ?>"><?= __mp('dev_earnings') ?></a>
            </div>
            <?php endif; ?>
        </div>
        <div class="flex items-center gap-2">
            <!-- 언어 변환기 -->
            <?php include BASE_PATH . '/resources/views/components/language-selector.php'; ?>

            <!-- 다크모드 토글 -->
            <button id="darkModeBtn" class="p-2 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition" title="Dark Mode">
                <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            </button>

            <a href="<?= $baseUrl ?>/marketplace" class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300"><?= __mp('marketplace') ?></a>
            <?php if ($devLoggedIn): ?>
            <span class="text-sm text-zinc-600 dark:text-zinc-400"><?= htmlspecialchars($devName) ?></span>
            <a href="<?= $baseUrl ?>/developer/logout" class="text-sm text-red-500 hover:text-red-600"><?= __mp('dev_logout') ?></a>
            <?php else: ?>
            <a href="<?= $baseUrl ?>/developer/login" class="text-sm px-4 py-1.5 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"><?= __mp('dev_login') ?></a>
            <?php endif; ?>
        </div>
    </div>
</nav>
<main class="max-w-6xl mx-auto px-6 py-8">
