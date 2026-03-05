<?php
/**
 * Dynamic manifest.json generator for Frontend PWA
 * Reads settings from database and generates manifest dynamically
 */

// Set JSON content type
header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=3600');

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
    $stmt = $pdo->query("SELECT `key`, `value` FROM rzx_settings WHERE `key` LIKE 'pwa_%' OR `key` IN ('site_name', 'site_tagline')");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['key']] = $row['value'];
    }
} catch (Exception $e) {
    error_log('PWA Manifest Error: ' . $e->getMessage());
}

// Default values
$appName = $settings['pwa_name'] ?? $settings['site_name'] ?? 'RezlyX';
$shortName = $settings['pwa_short_name'] ?? 'RezlyX';
$description = $settings['pwa_description'] ?? $settings['site_tagline'] ?? 'Online Reservation System';
$themeColor = $settings['pwa_theme_color'] ?? '#3b82f6';
$bgColor = $settings['pwa_bg_color'] ?? '#ffffff';

// Build manifest with dynamic basePath
$manifest = [
    'name' => $appName,
    'short_name' => $shortName,
    'description' => $description,
    'start_url' => $basePath . '/',
    'display' => 'standalone',
    'orientation' => 'portrait-primary',
    'theme_color' => $themeColor,
    'background_color' => $bgColor,
    'scope' => $basePath . '/',
    'icons' => [
        [
            'src' => $basePath . '/assets/icons/icon-72x72.png',
            'sizes' => '72x72',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => $basePath . '/assets/icons/icon-96x96.png',
            'sizes' => '96x96',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => $basePath . '/assets/icons/icon-128x128.png',
            'sizes' => '128x128',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => $basePath . '/assets/icons/icon-144x144.png',
            'sizes' => '144x144',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => $basePath . '/assets/icons/icon-152x152.png',
            'sizes' => '152x152',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => $basePath . '/assets/icons/icon-192x192.png',
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => $basePath . '/assets/icons/icon-384x384.png',
            'sizes' => '384x384',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => $basePath . '/assets/icons/icon-512x512.png',
            'sizes' => '512x512',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ]
    ],
    'shortcuts' => [
        [
            'name' => 'Book Now',
            'short_name' => 'Book',
            'description' => 'Make a reservation',
            'url' => $basePath . '/booking',
            'icons' => [['src' => $basePath . '/assets/icons/shortcut-booking.png', 'sizes' => '96x96']]
        ],
        [
            'name' => 'My Reservations',
            'short_name' => 'MyPage',
            'description' => 'View my reservations',
            'url' => $basePath . '/mypage',
            'icons' => [['src' => $basePath . '/assets/icons/shortcut-mypage.png', 'sizes' => '96x96']]
        ]
    ],
    'categories' => ['business', 'lifestyle'],
    'lang' => 'ko',
    'dir' => 'ltr'
];

echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
