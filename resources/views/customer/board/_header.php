<?php
/**
 * RezlyX 게시판 - 공통 헤더
 * 제공 변수: $board, $pageTitle, $baseUrl, $config, $siteSettings, $currentLocale
 */
require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
use RzxLib\Core\Auth\Auth;

$currentLocale = function_exists('current_locale') ? current_locale() : ($config['locale'] ?? 'ko');
if (!function_exists('__')) { function __($key, $replace = []) { return $key; } }

$helpersPath = BASE_PATH . '/rzxlib/Core/Helpers/functions.php';
if (file_exists($helpersPath) && !function_exists('get_site_tagline')) require_once $helpersPath;

$siteName = function_exists('get_site_name') ? get_site_name() : ($siteSettings['site_name'] ?? ($config['app_name'] ?? 'RezlyX'));
$logoType = $siteSettings['logo_type'] ?? 'text';
$logoImage = $siteSettings['logo_image'] ?? '';

if (!empty($config['app_url'])) {
    $parsedUrl = parse_url($config['app_url']);
    $baseUrl = rtrim($parsedUrl['path'] ?? '', '/');
} else {
    $baseUrl = '';
}

$isLoggedIn = Auth::check();
if (!$currentUser) $currentUser = $isLoggedIn ? Auth::user() : null;
if (!isset($siteSettings)) $siteSettings = [];
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($currentLocale) ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle ?? $board['title'] ?? 'Board') ?></title>
    <?php if (!empty($board['seo_keywords'])): ?><meta name="keywords" content="<?= htmlspecialchars($board['seo_keywords']) ?>"><?php endif; ?>
    <?php if (!empty($board['seo_description'])): ?><meta name="description" content="<?= htmlspecialchars($board['seo_description']) ?>"><?php endif; ?>
    <?php if (($board['robots_tag'] ?? 'all') === 'noindex'): ?><meta name="robots" content="noindex,nofollow"><?php endif; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <style>body { font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; }</style>
    <script>
        if (localStorage.getItem('darkMode') === 'true' ||
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-gray-50 dark:bg-zinc-900 min-h-screen transition-colors">
    <!-- Header -->
    <header class="bg-white dark:bg-zinc-800 shadow-sm sticky top-0 z-50">
        <div class="max-w-5xl mx-auto px-4 sm:px-6">
            <div class="flex items-center justify-between h-14">
                <a href="<?= $baseUrl ?>/" class="flex items-center text-lg font-bold text-blue-600 dark:text-blue-400">
                    <?php if ($logoType === 'image' && $logoImage): ?>
                        <img src="<?= $baseUrl . htmlspecialchars($logoImage) ?>" alt="<?= htmlspecialchars($siteName) ?>" class="h-8 object-contain">
                    <?php else: ?>
                        <?= htmlspecialchars($siteName) ?>
                    <?php endif; ?>
                </a>
                <div class="flex items-center gap-4">
                    <?php if ($isLoggedIn): ?>
                    <span class="text-sm text-zinc-600 dark:text-zinc-400"><?= htmlspecialchars($currentUser['nick_name'] ?? $currentUser['name'] ?? '') ?></span>
                    <?php else: ?>
                    <a href="<?= $baseUrl ?>/login" class="text-sm text-zinc-600 dark:text-zinc-400 hover:text-blue-600"><?= __('common.nav.login') ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </header>
