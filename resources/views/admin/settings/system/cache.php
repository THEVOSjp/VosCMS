<?php
/**
 * RezlyX Admin Settings - Cache Management
 * Clear various cache types
 */

// Initialize database and settings
require_once __DIR__ . '/../_init.php';

$pageTitle = __('system.tabs.cache') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$currentSettingsPage = 'system';
$currentSystemTab = 'cache';

// Handle cache clear actions
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $action = $_POST['action'];
    $cleared = false;

    switch ($action) {
        case 'clear_view':
            $viewCachePath = BASE_PATH . '/storage/framework/views';
            if (is_dir($viewCachePath)) {
                array_map('unlink', glob($viewCachePath . '/*.php'));
                $cleared = true;
            }
            break;

        case 'clear_config':
            $configCachePath = BASE_PATH . '/storage/framework/cache/config.php';
            if (file_exists($configCachePath)) {
                unlink($configCachePath);
                $cleared = true;
            }
            break;

        case 'clear_route':
            $routeCachePath = BASE_PATH . '/storage/framework/cache/routes.php';
            if (file_exists($routeCachePath)) {
                unlink($routeCachePath);
                $cleared = true;
            }
            break;

        case 'clear_all':
            // Clear all cache types
            $cacheDir = BASE_PATH . '/storage/framework/cache';
            $viewDir = BASE_PATH . '/storage/framework/views';

            if (is_dir($cacheDir)) {
                array_map('unlink', glob($cacheDir . '/*'));
            }
            if (is_dir($viewDir)) {
                array_map('unlink', glob($viewDir . '/*.php'));
            }
            $cleared = true;
            break;
    }

    if ($cleared) {
        $message = __('system.cache.cleared');
        $messageType = 'success';
    }
}

// Get cache sizes
function getDirSize($dir) {
    $size = 0;
    if (is_dir($dir)) {
        foreach (glob($dir . '/*') as $file) {
            $size += is_file($file) ? filesize($file) : getDirSize($file);
        }
    }
    return $size;
}

function formatSize($bytes) {
    if ($bytes >= 1048576) {
        return number_format($bytes / 1048576, 2) . ' MB';
    } elseif ($bytes >= 1024) {
        return number_format($bytes / 1024, 2) . ' KB';
    }
    return $bytes . ' B';
}

$viewCacheSize = formatSize(getDirSize(BASE_PATH . '/storage/framework/views'));
$configCacheExists = file_exists(BASE_PATH . '/storage/framework/cache/config.php');
$routeCacheExists = file_exists(BASE_PATH . '/storage/framework/cache/routes.php');

// Start content buffering
ob_start();
?>

<?php include __DIR__ . '/_tabs.php'; ?>

<!-- Cache Management -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
    <?php
    $headerIcon = 'M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4';
    $headerTitle = __('system.cache.title');
    $headerDescription = __('system.cache.description');
    $headerActions = ''; $headerIconColor = 'text-blue-600';
    include __DIR__ . '/../../components/settings-header.php';
    ?>

    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
        <!-- View Cache -->
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= __('system.cache.view') ?></p>
                <span class="text-xs text-zinc-500 dark:text-zinc-400"><?= $viewCacheSize ?></span>
            </div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3"><?= __('system.cache.view_desc') ?></p>
            <form method="POST" class="inline">
                <input type="hidden" name="action" value="clear_view">
                <button type="submit" class="w-full px-3 py-2 text-sm bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition">
                    <?= __('system.cache.clear') ?>
                </button>
            </form>
        </div>

        <!-- Config Cache -->
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= __('system.cache.config') ?></p>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?= $configCacheExists ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400'; ?>">
                    <?= $configCacheExists ? __('system.cache.cached') : __('system.cache.not_cached') ?>
                </span>
            </div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3"><?= __('system.cache.config_desc') ?></p>
            <form method="POST" class="inline">
                <input type="hidden" name="action" value="clear_config">
                <button type="submit" class="w-full px-3 py-2 text-sm bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition" <?= !$configCacheExists ? 'disabled' : '' ?>>
                    <?= __('system.cache.clear') ?>
                </button>
            </form>
        </div>

        <!-- Route Cache -->
        <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
            <div class="flex items-center justify-between mb-2">
                <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= __('system.cache.route') ?></p>
                <span class="inline-flex items-center px-2 py-0.5 rounded text-xs font-medium <?= $routeCacheExists ? 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400' : 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400'; ?>">
                    <?= $routeCacheExists ? __('system.cache.cached') : __('system.cache.not_cached') ?>
                </span>
            </div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3"><?= __('system.cache.route_desc') ?></p>
            <form method="POST" class="inline">
                <input type="hidden" name="action" value="clear_route">
                <button type="submit" class="w-full px-3 py-2 text-sm bg-red-100 dark:bg-red-900/30 text-red-700 dark:text-red-400 rounded-lg hover:bg-red-200 dark:hover:bg-red-900/50 transition" <?= !$routeCacheExists ? 'disabled' : '' ?>>
                    <?= __('system.cache.clear') ?>
                </button>
            </form>
        </div>
    </div>

    <!-- Clear All Cache -->
    <div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700">
        <form method="POST" class="inline">
            <input type="hidden" name="action" value="clear_all">
            <button type="submit" onclick="return confirm('<?= __('system.cache.confirm_clear') ?>')" class="px-6 py-2.5 text-sm font-medium bg-red-600 text-white rounded-lg hover:bg-red-700 transition flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                <?= __('system.cache.clear_all') ?>
            </button>
        </form>
    </div>
</div>

<script>
    console.log('Cache management page loaded');
</script>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/../_layout.php';
