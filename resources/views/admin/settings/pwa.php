<?php
/**
 * RezlyX Admin Settings - PWA
 * Redirects to pwa/general subpage
 */

// Get base URL and admin path
require_once __DIR__ . '/_init.php';

$baseUrl = $_ENV['APP_URL'] ?? 'http://localhost';
$adminPath = $config['admin_path'] ?? $_ENV['ADMIN_PATH'] ?? 'admin';

// Redirect to general subpage
header('Location: ' . $baseUrl . '/' . $adminPath . '/settings/pwa/general');
exit;
