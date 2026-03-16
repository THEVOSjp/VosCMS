<?php
/**
 * Dynamic manifest.json generator for Frontend PWA
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
    $stmt = $pdo->query("SELECT `key`, `value` FROM rzx_settings WHERE `key` LIKE 'pwa_front_%' OR `key` IN ('site_name', 'site_tagline')");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['key']] = $row['value'];
    }
} catch (Exception $e) {
    error_log('PWA Manifest Error: ' . $e->getMessage());
}

$appName = $settings['pwa_front_name'] ?? $settings['site_name'] ?? 'RezlyX';
$shortName = $settings['pwa_front_short_name'] ?? 'RezlyX';
$description = $settings['pwa_front_description'] ?? $settings['site_tagline'] ?? 'Online Reservation System';
$themeColor = $settings['pwa_front_theme_color'] ?? '#3b82f6';
$bgColor = $settings['pwa_front_bg_color'] ?? '#ffffff';
$display = $settings['pwa_front_display'] ?? 'standalone';

// Build icon list from uploaded icon
$icons = [];
$iconPath = $settings['pwa_front_icon'] ?? '';
if ($iconPath) {
    $iconUrl = $basePath . $iconPath;
    // Single source icon referenced at standard PWA sizes
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
    'description' => $description,
    'start_url' => $basePath . '/',
    'display' => $display,
    'orientation' => 'portrait-primary',
    'theme_color' => $themeColor,
    'background_color' => $bgColor,
    'scope' => $basePath . '/',
    'icons' => $icons,
    'categories' => ['business', 'lifestyle'],
    'lang' => 'ko',
    'dir' => 'ltr'
];

echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
