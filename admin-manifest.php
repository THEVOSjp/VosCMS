<?php
/**
 * Dynamic manifest.json generator for Admin PWA
 * Reads settings from database and generates manifest dynamically
 */

// Set JSON content type
header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=3600'); // Cache for 1 hour

// Load environment
define('BASE_PATH', __DIR__);
require_once BASE_PATH . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

// Get base path from APP_URL
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
    // Use default values if DB error
    error_log('Admin PWA Manifest Error: ' . $e->getMessage());
}

// Default values
$adminPath = $settings['admin_path'] ?? $_ENV['ADMIN_PATH'] ?? 'admin';
$siteName = $settings['site_name'] ?? $_ENV['APP_NAME'] ?? 'RezlyX';
$appName = $settings['pwa_admin_name'] ?? $siteName . ' Admin';
$shortName = $settings['pwa_admin_short_name'] ?? 'Admin';
$themeColor = $settings['pwa_admin_theme_color'] ?? '#18181b';
$bgColor = $settings['pwa_admin_bg_color'] ?? '#18181b';

// Build manifest with dynamic basePath
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
    'icons' => [
        [
            'src' => $basePath . '/assets/icons/admin-icon-72x72.png',
            'sizes' => '72x72',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => $basePath . '/assets/icons/admin-icon-96x96.png',
            'sizes' => '96x96',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => $basePath . '/assets/icons/admin-icon-128x128.png',
            'sizes' => '128x128',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => $basePath . '/assets/icons/admin-icon-144x144.png',
            'sizes' => '144x144',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => $basePath . '/assets/icons/admin-icon-152x152.png',
            'sizes' => '152x152',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => $basePath . '/assets/icons/admin-icon-192x192.png',
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => $basePath . '/assets/icons/admin-icon-384x384.png',
            'sizes' => '384x384',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => $basePath . '/assets/icons/admin-icon-512x512.png',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ]
    ],
    'shortcuts' => [
        [
            'name' => 'Dashboard',
            'short_name' => 'Dashboard',
            'description' => 'View dashboard',
            'url' => $basePath . '/' . $adminPath . '/dashboard',
            'icons' => [['src' => $basePath . '/assets/icons/shortcut-dashboard.png', 'sizes' => '96x96']]
        ],
        [
            'name' => 'Reservations',
            'short_name' => 'Reservations',
            'description' => 'Manage reservations',
            'url' => $basePath . '/' . $adminPath . '/reservations',
            'icons' => [['src' => $basePath . '/assets/icons/shortcut-reservations.png', 'sizes' => '96x96']]
        ],
        [
            'name' => 'Settings',
            'short_name' => 'Settings',
            'description' => 'System settings',
            'url' => $basePath . '/' . $adminPath . '/settings',
            'icons' => [['src' => $basePath . '/assets/icons/shortcut-settings.png', 'sizes' => '96x96']]
        ]
    ],
    'categories' => ['business', 'utilities'],
    'lang' => 'ko',
    'dir' => 'ltr'
];

// Output JSON
echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
