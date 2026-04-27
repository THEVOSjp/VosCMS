<?php
$_mktLocale = $config['locale'] ?? ($_SESSION['locale'] ?? 'ko');

// 플러그인 전역 CSRF 토큰 초기화 (한 번만 생성, 세션 내 공유)
if (empty($_SESSION['_csrf'])) {
    $_SESSION['_csrf'] = bin2hex(random_bytes(32));
}

if (!function_exists('mkt_pdo')) {
    function mkt_pdo(): PDO {
        static $pdo = null;
        if ($pdo) return $pdo;
        $pdo = new PDO(
            "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
            $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
        );
        return $pdo;
    }
}

if (!function_exists('mkt_locale_val')) {
    function mkt_locale_val(?string $json, string $locale = 'ko'): string {
        if (!$json) return '';
        $d = json_decode($json, true);
        if (!is_array($d)) return $json;
        return $d[$locale] ?? $d['en'] ?? reset($d) ?: '';
    }
}

$_mktPrefix  = $_ENV['DB_PREFIX'] ?? 'rzx_';
$_mktBase    = $config['app_url'] ?? '';
$_mktAdmin   = $_mktBase . '/' . ($config['admin_path'] ?? 'theadmin');
$_mktSiteName = __('marketplace.marketplace') ?: 'VosCMS 마켓플레이스';
$pageTitle    = isset($pageHeaderTitle) ? $pageHeaderTitle . ' — ' . $_mktSiteName : $_mktSiteName;
?>
<!DOCTYPE html>
<html lang="<?= $_mktLocale ?>">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title><?= htmlspecialchars($pageTitle) ?></title>
<?php include BASE_PATH . '/resources/views/admin/partials/pwa-head.php'; ?>
<script src="https://cdn.tailwindcss.com"></script>
<script>tailwind.config = { darkMode: 'class' }</script>
<link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
<style>body { font-family: 'Pretendard', system-ui, sans-serif; } [x-cloak] { display: none !important; }</style>
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13/dist/cdn.min.js"></script>
<script>
if (localStorage.getItem('darkMode') === 'true' ||
    (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
    document.documentElement.classList.add('dark');
}
</script>
</head>
<body class="bg-zinc-100 dark:bg-zinc-900 min-h-screen">
<div class="flex">
<?php include BASE_PATH . '/resources/views/admin/partials/admin-sidebar.php'; ?>
<main class="flex-1 ml-64">
<?php
if (!isset($pageHeaderTitle)) $pageHeaderTitle = $_mktSiteName;
include BASE_PATH . '/resources/views/admin/partials/admin-topbar.php';
?>
<div class="p-6">
