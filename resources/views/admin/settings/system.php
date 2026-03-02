<?php
/**
 * RezlyX Admin Settings - System
 * Redirects to system/info subpage
 */

// Get base URL and admin path
$baseUrl = $_ENV['APP_URL'] ?? 'http://localhost';
$adminPath = $config['admin_path'] ?? $_ENV['ADMIN_PATH'] ?? 'admin';

// Redirect to info subpage
header('Location: ' . $baseUrl . '/' . $adminPath . '/settings/system/info');
exit;
