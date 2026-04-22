<?php
/**
 * VosCMS Marketplace - Admin Head
 */
// 마켓플레이스 다국어 로드
$_mpLocale = $config['locale'] ?? ($_SESSION['locale'] ?? 'ko');
$_mpLangFile = __DIR__ . '/../../lang/' . $_mpLocale . '.php';
if (!file_exists($_mpLangFile)) $_mpLangFile = __DIR__ . '/../../lang/en.php';
$_mpLang = file_exists($_mpLangFile) ? require $_mpLangFile : [];

// 마켓플레이스 번역 헬퍼
if (!function_exists('__mp')) {
    function __mp(string $key, string $default = ''): string {
        global $_mpLang;
        return $_mpLang[$key] ?? $default ?: $key;
    }
}

$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
$pageTitle = __mp('title') . ' - ' . ($config['app_name'] ?? 'VosCMS') . ' Admin';
$locale = $_mpLocale;
?>
<!DOCTYPE html>
<html lang="<?= $locale ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <?php include BASE_PATH . '/resources/views/admin/partials/pwa-head.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <style>body { font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; }</style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13/dist/cdn.min.js"></script>
    <script>
        if (localStorage.getItem('darkMode') === 'true' ||
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-zinc-100 dark:bg-zinc-900 min-h-screen transition-colors">
<div class="flex">
    <?php include BASE_PATH . '/resources/views/admin/partials/admin-sidebar.php'; ?>
    <main class="flex-1 ml-64">
        <?php
        if (!isset($pageHeaderTitle)) $pageHeaderTitle = __mp('title');
        include BASE_PATH . '/resources/views/admin/partials/admin-topbar.php';
        ?>
        <div class="p-6">
