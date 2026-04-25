<?php
/**
 * VosCMS Marketplace - Admin Head
 */
$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
$pageTitle = __('autoinstall.title') . ' - ' . ($config['app_name'] ?? 'VosCMS') . ' Admin';
$locale = current_locale();
?>
<!DOCTYPE html>
<html lang="<?= $locale ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <?php include BASE_PATH . '/resources/views/admin/partials/pwa-head.php'; ?>
    <script src="https://cdn.tailwindcss.com?plugins=typography"></script>
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
        if (!isset($pageHeaderTitle)) $pageHeaderTitle = __('autoinstall.title');
        include BASE_PATH . '/resources/views/admin/partials/admin-topbar.php';
        ?>
        <div class="p-6">
