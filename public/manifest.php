<?php
/**
 * Dynamic manifest.json generator for Frontend PWA
 * Reads settings from database and generates manifest dynamically
 */

// Set JSON content type
header('Content-Type: application/manifest+json; charset=utf-8');
header('Cache-Control: public, max-age=3600'); // Cache for 1 hour

// Load configuration
require_once __DIR__ . '/../config/config.php';
require_once __DIR__ . '/../rzxlib/Core/Database/Connection.php';

use RzxLib\Core\Database\Connection;

// Get settings from database
$settings = [];
try {
    $db = Connection::getInstance();
    $stmt = $db->query("SELECT setting_key, setting_value FROM rzx_settings WHERE setting_key LIKE 'pwa_front_%' OR setting_key IN ('site_name', 'site_tagline', 'app_url')");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    // Use default values if DB error
    error_log('PWA Manifest Error: ' . $e->getMessage());
}

// Default values
$appUrl = $settings['app_url'] ?? '';
$appName = $settings['pwa_front_name'] ?? $settings['site_name'] ?? 'RezlyX';
$shortName = $settings['pwa_front_short_name'] ?? 'RezlyX';
$description = $settings['pwa_front_description'] ?? $settings['site_tagline'] ?? 'Online Reservation System';
$themeColor = $settings['pwa_front_theme_color'] ?? '#3b82f6';
$bgColor = $settings['pwa_front_bg_color'] ?? '#ffffff';
$icon = $settings['pwa_front_icon'] ?? '/assets/icons/icon-192x192.svg';

// Build manifest
$manifest = [
    'name' => $appName,
    'short_name' => $shortName,
    'description' => $description,
    'start_url' => '/',
    'display' => 'standalone',
    'orientation' => 'portrait-primary',
    'theme_color' => $themeColor,
    'background_color' => $bgColor,
    'scope' => '/',
    'icons' => [
        [
            'src' => $icon,
            'sizes' => '192x192',
            'type' => 'image/png',
            'purpose' => 'any maskable'
        ],
        [
            'src' => str_replace('192x192', '512x512', $icon),
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
            'url' => '/booking',
            'icons' => [
                [
                    'src' => '/assets/icons/shortcut-booking.svg',
                    'sizes' => '96x96'
                ]
            ]
        ],
        [
            'name' => 'My Reservations',
            'short_name' => 'MyPage',
            'description' => 'View my reservations',
            'url' => '/mypage',
            'icons' => [
                [
                    'src' => '/assets/icons/shortcut-mypage.svg',
                    'sizes' => '96x96'
                ]
            ]
        ]
    ],
    'categories' => ['business', 'lifestyle'],
    'lang' => 'ko',
    'dir' => 'ltr'
];

// Output JSON
echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
