<?php
/**
 * PWA Settings - Sub Tab Navigation
 * Common tab menu for PWA settings sub-pages
 *
 * Required variables:
 * - $currentPwaTab: Current active tab (general, webpush, subscribers)
 * - $baseUrl: Base URL
 * - $adminPath: Admin path (from config)
 */

// Get admin path (should be set from _init.php)
$adminPath = $adminPath ?? $config['admin_path'] ?? $_ENV['ADMIN_PATH'] ?? 'admin';

$pwaTabs = [
    'general' => [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>',
        'label' => __('admin.settings.pwa.tabs.general'),
    ],
    'webpush' => [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>',
        'label' => __('admin.settings.pwa.tabs.webpush'),
    ],
    'subscribers' => [
        'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>',
        'label' => __('admin.settings.pwa.tabs.subscribers'),
    ],
];
?>

<!-- PWA Sub Tabs -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm mb-6 transition-colors">
    <div class="border-b border-zinc-200 dark:border-zinc-700">
        <nav class="flex -mb-px" aria-label="Tabs">
            <?php foreach ($pwaTabs as $tabKey => $tab): ?>
            <a href="<?= $baseUrl ?>/<?= $adminPath ?>/settings/pwa/<?= $tabKey ?>"
               class="px-6 py-4 text-sm font-medium border-b-2 <?= $currentPwaTab === $tabKey ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-300 hover:border-zinc-300 dark:hover:border-zinc-600'; ?>">
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
