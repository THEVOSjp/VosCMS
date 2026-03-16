<?php
/**
 * Dynamic manifest.json generator for Admin PWA
 * Reads settings from database and generates manifest dynamically
 */

header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=3600');

define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

$appUrl = $_ENV['APP_URL'] ?? 'http://localhost';
$parsedUrl = parse_url($appUrl);
$basePath = rtrim($parsedUrl['path'] ?? '', '/');

// Get settings from database
$settings = [];
try {
    $pdo = new PDO(
        'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx'),
        $_ENV['DB_USERNAME'] ?? 'root',
        $_ENV['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $stmt = $pdo->query("SELECT `key`, `value` FROM rzx_settings WHERE `key` LIKE 'pwa_admin_%' OR `key` IN ('site_name', 'admin_path')");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['key']] = $row['value'];
    }
} catch (Exception $e) {
    error_log('Admin PWA Manifest Error: ' . $e->getMessage());
}

$adminPath = $settings['admin_path'] ?? $_ENV['ADMIN_PATH'] ?? 'admin';
$siteName = $settings['site_name'] ?? $_ENV['APP_NAME'] ?? 'RezlyX';
$appName = $settings['pwa_admin_name'] ?? $siteName . ' Admin';
$shortName = $settings['pwa_admin_short_name'] ?? 'Admin';
$themeColor = $settings['pwa_admin_theme_color'] ?? '#18181b';
$bgColor = $settings['pwa_admin_bg_color'] ?? '#18181b';

// Build icon list from uploaded icon
$icons = [];
$iconPath = $settings['pwa_admin_icon'] ?? '';
if ($iconPath) {
    $iconUrl = $basePath . $iconPath;
    $sizes = ['72x72', '96x96', '128x128', '144x144', '152x152', '192x192', '384x384', '512x512'];
    foreach ($sizes as $size) {
        $icons[] = [
            'src' => $iconUrl,
            'sizes' => $size,
            'type' => 'image/png',
            'purpose' => 'any'
        ];
    }
}

$manifest = [
    'name' => $appName,
    'short_name' => $shortName,
    'description' => 'Administration Dashboard',
    'start_url' => $basePath . '/' . $adminPath,
    'display' => 'standalone',
    'orientation' => 'any',
    'theme_color' => $themeColor,
    'background_color' => $bgColor,
    'scope' => $basePath . '/' . $adminPath,
    'icons' => $icons,
    'categories' => ['business', 'utilities'],
    'lang' => 'ko',
    'dir' => 'ltr'
];

echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
