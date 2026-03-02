<?php
/**
 * Admin PWA Head Tags
 * Include this in all admin pages' <head> section
 */
$baseUrl = $config['app_url'] ?? '';
?>
<!-- PWA Admin -->
<link rel="manifest" href="<?php echo $baseUrl; ?>/admin-manifest.json">
<meta name="theme-color" content="#18181b">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="RezlyX Admin">
<meta name="mobile-web-app-capable" content="yes">
<meta name="application-name" content="RezlyX Admin">
<link rel="apple-touch-icon" href="<?php echo $baseUrl; ?>/assets/icons/admin-icon-192x192.png">
