<?php
/**
 * RezlyX Admin Settings - System Information
 * Read-only system information display
 */

// Initialize database and settings
require_once __DIR__ . '/../_init.php';

$pageTitle = __('admin.settings.system.tabs.info') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$currentSettingsPage = 'system';
$currentSystemTab = 'info';

// Gather system information
$systemInfo = [
    'php_version' => PHP_VERSION,
    'php_sapi' => php_sapi_name(),
    'os' => PHP_OS,
    'os_family' => PHP_OS_FAMILY ?? (stripos(PHP_OS, 'WIN') === 0 ? 'Windows' : 'Unix'),
    'server_software' => $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown',
    'document_root' => $_SERVER['DOCUMENT_ROOT'] ?? '',
    'timezone' => date_default_timezone_get(),
    'current_time' => date('Y-m-d H:i:s'),
    'memory_limit' => ini_get('memory_limit'),
    'max_execution_time' => ini_get('max_execution_time') . 's',
    'upload_max_filesize' => ini_get('upload_max_filesize'),
    'post_max_size' => ini_get('post_max_size'),
    'max_input_vars' => ini_get('max_input_vars'),
    'display_errors' => ini_get('display_errors') ? 'On' : 'Off',
    'error_reporting' => error_reporting(),
];

// Database information
$dbInfo = [
    'driver' => 'MySQL/MariaDB',
    'host' => $_ENV['DB_HOST'] ?? 'localhost',
    'database' => $_ENV['DB_DATABASE'] ?? 'rezlyx',
];

try {
    $versionStmt = $pdo->query('SELECT VERSION() as version');
    $dbInfo['version'] = $versionStmt->fetchColumn();

    $charsetStmt = $pdo->query('SHOW VARIABLES LIKE "character_set_database"');
    $charset = $charsetStmt->fetch(PDO::FETCH_ASSOC);
    $dbInfo['charset'] = $charset['Value'] ?? 'Unknown';

    $collationStmt = $pdo->query('SHOW VARIABLES LIKE "collation_database"');
    $collation = $collationStmt->fetch(PDO::FETCH_ASSOC);
    $dbInfo['collation'] = $collation['Value'] ?? 'Unknown';
} catch (PDOException $e) {
    $dbInfo['version'] = 'Error: ' . $e->getMessage();
}

// PHP Extensions
$requiredExtensions = ['pdo', 'pdo_mysql', 'json', 'mbstring', 'openssl', 'curl', 'gd', 'fileinfo'];
$loadedExtensions = get_loaded_extensions();

// Debug mode status
$debugMode = ($config['debug'] ?? false) || (($_ENV['APP_DEBUG'] ?? 'false') === 'true');

// App information
$appInfo = [
    'name' => $config['app_name'] ?? 'RezlyX',
    'version' => $config['app_version'] ?? '1.0.0',
    'environment' => $config['environment'] ?? ($_ENV['APP_ENV'] ?? 'production'),
    'url' => $config['app_url'] ?? '',
    'locale' => $config['locale'] ?? 'ko',
];

// Start content buffering
ob_start();
?>

<?php include __DIR__ . '/_tabs.php'; ?>

<!-- Application Info -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <?= __('admin.settings.system.app.title') ?>
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.settings.system.app.name') ?></p>
            <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($appInfo['name']); ?></p>
        </div>
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.settings.system.app.version') ?></p>
            <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($appInfo['version']); ?></p>
        </div>
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.settings.system.app.environment') ?></p>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $appInfo['environment'] === 'production' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'; ?>">
                <?= htmlspecialchars(ucfirst($appInfo['environment'])); ?>
            </span>
        </div>
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.settings.system.app.debug_mode') ?></p>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $debugMode ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'; ?>">
                <?= $debugMode ? __('admin.settings.system.status.on') : __('admin.settings.system.status.off'); ?>
            </span>
            <?php if ($debugMode): ?>
            <p class="text-xs text-red-500 mt-1"><?= __('admin.settings.system.app.debug_warning') ?></p>
            <?php endif; ?>
        </div>
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.settings.system.app.url') ?></p>
            <p class="text-sm font-medium text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($appInfo['url']); ?></p>
        </div>
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.settings.system.app.locale') ?></p>
            <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars(strtoupper($appInfo['locale'])); ?></p>
        </div>
    </div>
</div>

<!-- Server Information -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-orange-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>
        </svg>
        <?= __('admin.settings.system.server.title') ?>
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.settings.system.server.os') ?></p>
            <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($systemInfo['os']); ?></p>
        </div>
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.settings.system.server.os_family') ?></p>
            <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($systemInfo['os_family']); ?></p>
        </div>
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.settings.system.server.software') ?></p>
            <p class="text-sm font-medium text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($systemInfo['server_software']); ?></p>
        </div>
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg md:col-span-2">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.settings.system.server.document_root') ?></p>
            <p class="text-sm font-medium text-zinc-900 dark:text-white break-all"><?= htmlspecialchars($systemInfo['document_root']); ?></p>
        </div>
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.settings.system.server.current_time') ?></p>
            <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($systemInfo['current_time']); ?></p>
        </div>
    </div>
</div>

<!-- PHP Information -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-purple-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 20l4-16m4 4l4 4-4 4M6 16l-4-4 4-4"/>
        </svg>
        <?= __('admin.settings.system.php.title') ?>
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 mb-6">
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.settings.system.php.version') ?></p>
            <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($systemInfo['php_version']); ?></p>
        </div>
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.settings.system.php.sapi') ?></p>
            <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($systemInfo['php_sapi']); ?></p>
        </div>
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.settings.system.php.timezone') ?></p>
            <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($systemInfo['timezone']); ?></p>
        </div>
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.settings.system.php.memory_limit') ?></p>
            <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($systemInfo['memory_limit']); ?></p>
        </div>
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.settings.system.php.max_execution_time') ?></p>
            <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($systemInfo['max_execution_time']); ?></p>
        </div>
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.settings.system.php.upload_max_filesize') ?></p>
            <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($systemInfo['upload_max_filesize']); ?></p>
        </div>
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.settings.system.php.post_max_size') ?></p>
            <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($systemInfo['post_max_size']); ?></p>
        </div>
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.settings.system.php.display_errors') ?></p>
            <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $systemInfo['display_errors'] === 'On' ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'; ?>">
                <?= $systemInfo['display_errors']; ?>
            </span>
        </div>
    </div>

    <!-- PHP Extensions -->
    <h3 class="text-md font-semibold text-zinc-900 dark:text-white mb-3"><?= __('admin.settings.system.php.extensions') ?></h3>
    <div class="flex flex-wrap gap-2">
        <?php foreach ($requiredExtensions as $ext): ?>
        <?php $isLoaded = in_array($ext, $loadedExtensions); ?>
        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?= $isLoaded ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400'; ?>">
            <?php if ($isLoaded): ?>
            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
            </svg>
            <?php else: ?>
            <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
            </svg>
            <?php endif; ?>
            <?= htmlspecialchars($ext); ?>
        </span>
        <?php endforeach; ?>
    </div>
</div>

<!-- Database Information -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 transition-colors">
    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4 flex items-center">
        <svg class="w-5 h-5 mr-2 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4m0 5c0 2.21-3.582 4-8 4s-8-1.79-8-4"/>
        </svg>
        <?= __('admin.settings.system.db.title') ?>
    </h2>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.settings.system.db.driver') ?></p>
            <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($dbInfo['driver']); ?></p>
        </div>
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.settings.system.db.version') ?></p>
            <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($dbInfo['version'] ?? 'Unknown'); ?></p>
        </div>
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.settings.system.db.host') ?></p>
            <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($dbInfo['host']); ?></p>
        </div>
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.settings.system.db.database') ?></p>
            <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($dbInfo['database']); ?></p>
        </div>
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.settings.system.db.charset') ?></p>
            <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($dbInfo['charset'] ?? 'Unknown'); ?></p>
        </div>
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.settings.system.db.collation') ?></p>
            <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($dbInfo['collation'] ?? 'Unknown'); ?></p>
        </div>
    </div>
</div>

<script>
    console.log('System info page loaded');
</script>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/../_layout.php';
