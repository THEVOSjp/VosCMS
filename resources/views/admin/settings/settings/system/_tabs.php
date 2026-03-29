<?php
/**
 * System Settings - Sub Tab Navigation
 * Common tab menu for system settings sub-pages
 *
 * Required variables:
 * - $currentSystemTab: Current active tab (info, cache, mode, logs)
 * - $baseUrl: Base URL
 * - $adminPath: Admin path (from config)
 */

// Get admin path (should be set from _init.php)
$adminPath = $adminPath ?? $config['admin_path'] ?? $_ENV['ADMIN_PATH'] ?? 'admin';

$systemTabs = [
    'info' => [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
        'label' => __('system.tabs.info'),
    ],
    'cache' => [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>',
        'label' => __('system.tabs.cache'),
    ],
    'mode' => [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4"/>',
        'label' => __('system.tabs.mode'),
    ],
    'logs' => [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>',
        'label' => __('system.tabs.logs'),
    ],
    'updates' => [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>',
        'label' => __('system.tabs.updates'),
    ],
];
?>

<!-- System Sub Tabs -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm mb-6 transition-colors">
    <div class="border-b border-zinc-200 dark:border-zinc-700">
        <nav class="flex -mb-px" aria-label="Tabs">
            <?php foreach ($systemTabs as $tabKey => $tab): ?>
            <a href="<?= $baseUrl ?>/<?= $adminPath ?>/settings/system/<?= $tabKey ?>"
               class="px-6 py-4 text-sm font-medium border-b-2 <?= $currentSystemTab === $tabKey ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300 hover:border-zinc-300 dark:hover:border-zinc-600'; ?>">
                <span class="flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <?= $tab['icon'] ?>
                    </svg>
                    <?= $tab['label'] ?>
                </span>
            </a>
            <?php endforeach; ?>
        </nav>
    </div>
</div>
