<?php
/**
 * Admin PWA Head Tags
 * Include this in all admin pages' <head> section
 */
$baseUrl = $config['app_url'] ?? '';
$_pwaSettings = $siteSettings ?? $settings ?? [];
$pwaAdminIcon = $_pwaSettings['pwa_admin_icon'] ?? '';
$pwaAdminTheme = $_pwaSettings['pwa_admin_theme_color'] ?? '#18181b';
$pwaAdminName = $_pwaSettings['pwa_admin_name'] ?? 'RezlyX Admin';
$iconHref = $pwaAdminIcon ? $baseUrl . $pwaAdminIcon : '';
$_faviconPath = $_pwaSettings['favicon'] ?? '';
?>
<!-- Favicon -->
<link rel="icon" href="<?php echo $_faviconPath ? $baseUrl . htmlspecialchars($_faviconPath) : $baseUrl . '/assets/images/favicon.ico'; ?>">
<!-- PWA Admin -->
<link rel="manifest" href="<?php echo $baseUrl; ?>/admin-manifest.json">
<meta name="theme-color" content="<?php echo htmlspecialchars($pwaAdminTheme); ?>">
<meta name="apple-mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
<meta name="apple-mobile-web-app-title" content="<?php echo htmlspecialchars($pwaAdminName); ?>">
<meta name="mobile-web-app-capable" content="yes">
<meta name="application-name" content="<?php echo htmlspecialchars($pwaAdminName); ?>">
<?php if ($iconHref): ?>
<link rel="apple-touch-icon" href="<?php echo htmlspecialchars($iconHref); ?>">
<?php endif; ?>
