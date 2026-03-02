<?php
/**
 * RezlyX Admin Members Settings - Redirect
 * Redirects to the general settings page
 */

// Build the redirect URL
$adminPath = $config['admin_path'] ?? 'admin';
$baseUrl = $config['app_url'] ?? '';
$redirectUrl = $baseUrl . '/' . $adminPath . '/members/settings/general';

header('Location: ' . $redirectUrl);
exit;
