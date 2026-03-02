<?php
/**
 * Dynamic manifest.json generator for Admin PWA
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
    $stmt = $db->query("SELECT setting_key, setting_value FROM rzx_settings WHERE setting_key LIKE 'pwa_admin_%' OR setting_key IN ('site_name', 'app_url', 'admin_path')");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['setting_key']] = $row['setting_value'];
    }
} catch (Exception $e) {
    // Use default values if DB error
    error_log('Admin PWA Manifest Error: ' . $e->getMessage());
}

// Default values
$appUrl = $settings['app_url'] ?? '';
$adminPath = $settings['admin_path'] ?? 'admin';
$siteName = $settings['site_name'] ?? 'RezlyX';
$appName = $settings['pwa_admin_name'] ?? $siteName . ' Admin';
$shortName = $settings['pwa_admin_short_name'] ?? 'Admin';
$themeColor = $settings['pwa_admin_theme_color'] ?? '#18181b';
$bgColor = $settings['pwa_admin_bg_color'] ?? '#18181b';
$icon = $settings['pwa_admin_icon'] ?? '/assets/icons/admin-icon-192x192.svg';

// Build manifest
$manifest = [
    'name' => $appName,
    'short_name' => $shortName,
    'description' => 'Administration Dashboard',
    'start_url' => '/' . $adminPath,
    'display' => 'standalone',
    'orientation' => 'any',
    'theme_color' => $themeColor,
    'background_color' => $bgColor,
    'scope' => '/' . $adminPath,
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
            'name' => 'Dashboard',
            'short_name' => 'Dashboard',
            'description' => 'View dashboard',
            'url' => '/' . $adminPath . '/dashboard',
            'icons' => [
                [
                    'src' => '/assets/icons/shortcut-dashboard.svg',
                    'sizes' => '96x96'
                ]
            ]
        ],
        [
            'name' => 'Reservations',
            'short_name' => 'Reservations',
            'description' => 'Manage reservations',
            'url' => '/' . $adminPath . '/reservations',
            'icons' => [
                [
                    'src' => '/assets/icons/shortcut-reservations.svg',
                    'sizes' => '96x96'
                ]
            ]
        ],
        [
            'name' => 'Settings',
            'short_name' => 'Settings',
            'description' => 'System settings',
            'url' => '/' . $adminPath . '/settings',
            'icons' => [
                [
                    'src' => '/assets/icons/shortcut-settings.svg',
                    'sizes' => '96x96'
                ]
            ]
        ]
    ],
    'categories' => ['business', 'utilities'],
    'lang' => 'ko',
    'dir' => 'ltr'
];

// Output JSON
echo json_encode($manifest, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
