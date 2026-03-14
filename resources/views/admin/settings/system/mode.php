<?php
/**
 * RezlyX Admin Settings - Mode Management
 * Debug mode, maintenance mode, environment settings
 */

// Initialize database and settings
require_once __DIR__ . '/../_init.php';

$pageTitle = __('system.tabs.mode') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$currentSettingsPage = 'system';
$currentSystemTab = 'mode';

// Debug mode status
// Priority: .env APP_DEBUG=true (always on) > DB debug_mode (toggleable)
$envDebug = ($_ENV['APP_DEBUG'] ?? 'false') === 'true';
$dbDebugMode = ($settings['debug_mode'] ?? '0') === '1';
$debugMode = $envDebug || $dbDebugMode;
$debugModeSource = $envDebug ? 'env' : 'db';  // Track source for UI

// Maintenance mode status
$maintenanceFile = BASE_PATH . '/storage/framework/down';
$maintenanceMode = file_exists($maintenanceFile);

// Environment
$environment = $config['environment'] ?? ($_ENV['APP_ENV'] ?? 'production');

// App information
$appInfo = [
    'name' => $config['app_name'] ?? 'RezlyX',
    'version' => $config['app_version'] ?? '1.0.0',
    'environment' => $environment,
    'locale' => $config['locale'] ?? 'ko',
];

// Handle mode toggle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];

    switch ($action) {
        case 'toggle_debug':
            // Only toggle if .env APP_DEBUG is not true
            if (!$envDebug) {
                $newDebugMode = $dbDebugMode ? '0' : '1';
                try {
                    // Check if setting exists
                    $stmt = $pdo->prepare("SELECT COUNT(*) FROM rzx_settings WHERE `key` = 'debug_mode'");
                    $stmt->execute();
                    $exists = $stmt->fetchColumn() > 0;

                    if ($exists) {
                        $stmt = $pdo->prepare("UPDATE rzx_settings SET `value` = ? WHERE `key` = 'debug_mode'");
                    } else {
                        $stmt = $pdo->prepare("INSERT INTO rzx_settings (`key`, `value`) VALUES ('debug_mode', ?)");
                    }
                    $stmt->execute([$newDebugMode]);

                    $dbDebugMode = $newDebugMode === '1';
                    $debugMode = $dbDebugMode;
                    $message = $dbDebugMode
                        ? __('system.mode.debug_enabled')
                        : __('system.mode.debug_disabled');
                    $messageType = 'success';
                } catch (PDOException $e) {
                    $message = __('system.mode.debug_error');
                    $messageType = 'error';
                }
            }
            break;

        case 'toggle_maintenance':
            if ($maintenanceMode) {
                // Disable maintenance mode
                if (file_exists($maintenanceFile)) {
                    unlink($maintenanceFile);
                    $maintenanceMode = false;
                    $message = __('system.mode.maintenance_disabled');
                    $messageType = 'success';
                }
            } else {
                // Enable maintenance mode
                $downContent = json_encode([
                    'time' => time(),
                    'message' => __('system.mode.maintenance_message'),
                    'retry' => 60,
                ]);
                if (!is_dir(dirname($maintenanceFile))) {
                    mkdir(dirname($maintenanceFile), 0755, true);
                }
                file_put_contents($maintenanceFile, $downContent);
                $maintenanceMode = true;
                $message = __('system.mode.maintenance_enabled');
                $messageType = 'success';
            }
            break;
    }
}

// Start content buffering
ob_start();
?>

<?php include __DIR__ . '/_tabs.php'; ?>

<!-- Mode Management -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
    <?php
    $headerIcon = 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4';
    $headerTitle = __('system.mode.title');
    $headerDescription = __('system.mode.description');
    $headerActions = ''; $headerIconColor = 'text-purple-600';
    include __DIR__ . '/../../components/settings-header.php';
    ?>

    <div class="space-y-4">
        <!-- Debug Mode -->
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= __('system.mode.debug') ?></p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= __('system.mode.debug_desc') ?></p>
                </div>
                <div class="flex items-center gap-2">
                    <?php if ($envDebug): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400">
                        .env
                    </span>
                    <?php endif; ?>
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $debugMode ? 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400' : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'; ?>">
                        <?= $debugMode ? __('system.status.on') : __('system.status.off'); ?>
                    </span>
                </div>
            </div>
            <?php if ($envDebug): ?>
            <p class="text-xs text-blue-600 dark:text-blue-400 mb-2">
                <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                </svg>
                <?= __('system.mode.debug_env_locked') ?>
            </p>
            <?php else: ?>
            <form method="POST">
                <input type="hidden" name="action" value="toggle_debug">
                <button type="submit" onclick="return confirm('<?= $debugMode ? __('system.mode.confirm_disable_debug') : __('system.mode.confirm_enable_debug') ?>')"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition <?= $debugMode ? 'bg-green-600 hover:bg-green-700 text-white' : 'bg-red-600 hover:bg-red-700 text-white'; ?>">
                    <?= $debugMode ? __('system.mode.disable_debug') : __('system.mode.enable_debug'); ?>
                </button>
            </form>
            <?php endif; ?>
        </div>

        <!-- Maintenance Mode -->
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <div class="flex items-center justify-between mb-4">
                <div>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= __('system.mode.maintenance') ?></p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= __('system.mode.maintenance_desc') ?></p>
                </div>
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $maintenanceMode ? 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400' : 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400'; ?>">
                    <?= $maintenanceMode ? __('system.status.on') : __('system.status.off'); ?>
                </span>
            </div>
            <form method="POST">
                <input type="hidden" name="action" value="toggle_maintenance">
                <button type="submit" onclick="return confirm('<?= $maintenanceMode ? __('system.mode.confirm_disable_maintenance') : __('system.mode.confirm_enable_maintenance') ?>')"
                        class="px-4 py-2 text-sm font-medium rounded-lg transition <?= $maintenanceMode ? 'bg-green-600 hover:bg-green-700 text-white' : 'bg-yellow-600 hover:bg-yellow-700 text-white'; ?>">
                    <?= $maintenanceMode ? __('system.mode.disable_maintenance') : __('system.mode.enable_maintenance'); ?>
                </button>
            </form>
        </div>

        <!-- Environment -->
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg flex items-center justify-between">
            <div>
                <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= __('system.mode.environment') ?></p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= __('system.mode.environment_desc') ?></p>
            </div>
            <div class="flex items-center">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium <?= $appInfo['environment'] === 'production' ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400'; ?>">
                    <?= htmlspecialchars(ucfirst($appInfo['environment'])); ?>
                </span>
            </div>
        </div>
    </div>

    <div class="mt-6 p-4 bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-lg">
        <p class="text-sm text-yellow-800 dark:text-yellow-300">
            <svg class="w-4 h-4 inline mr-1" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M8.257 3.099c.765-1.36 2.722-1.36 3.486 0l5.58 9.92c.75 1.334-.213 2.98-1.742 2.98H4.42c-1.53 0-2.493-1.646-1.743-2.98l5.58-9.92zM11 13a1 1 0 11-2 0 1 1 0 012 0zm-1-8a1 1 0 00-1 1v3a1 1 0 002 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <?= __('system.mode.env_notice') ?>
        </p>
    </div>
</div>

<script>
    console.log('Mode management page loaded');
</script>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/../_layout.php';
