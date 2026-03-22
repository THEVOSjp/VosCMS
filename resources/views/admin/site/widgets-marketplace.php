<?php
/**
 * RezlyX Admin - 위젯 마켓플레이스
 */
$pageTitle = __('site.widgets.mp.title') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';

$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');


$pageHeaderTitle = __('site.widgets.marketplace');
$pageSubTitle = __('site.widgets.marketplace');
?>
<?php include __DIR__ . '/../reservations/_head.php'; ?>
                <!-- Back link -->
                <a href="<?= $adminUrl ?>/site/widgets" class="inline-flex items-center text-sm text-blue-600 dark:text-blue-400 hover:underline mb-4">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                    <?= __('site.widgets.title') ?>
                </a>

                <!-- Search -->
                <div class="mb-6">
                    <div class="relative max-w-md">
                        <svg class="w-5 h-5 absolute left-3 top-2.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <input type="text" placeholder="<?= __('site.widgets.mp.search_placeholder') ?>"
                               class="w-full pl-10 pr-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-800 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                    </div>
                </div>

                <!-- Category Tabs -->
                <div class="flex items-center gap-2 mb-6">
                    <button class="px-4 py-2 rounded-lg text-sm font-medium bg-blue-600 text-white"><?= __('site.widgets.categories.all') ?></button>
                    <button class="px-4 py-2 rounded-lg text-sm font-medium bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition"><?= __('site.widgets.mp.featured') ?></button>
                    <button class="px-4 py-2 rounded-lg text-sm font-medium bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition"><?= __('site.widgets.mp.popular') ?></button>
                    <button class="px-4 py-2 rounded-lg text-sm font-medium bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition"><?= __('site.widgets.mp.recent') ?></button>
                    <button class="px-4 py-2 rounded-lg text-sm font-medium bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition"><?= __('site.widgets.mp.free') ?></button>
                </div>

                <!-- Coming Soon -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-16 text-center">
                    <div class="w-20 h-20 bg-blue-100 dark:bg-blue-900/30 rounded-2xl flex items-center justify-center mx-auto mb-6">
                        <svg class="w-10 h-10 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                        </svg>
                    </div>
                    <h3 class="text-xl font-bold text-zinc-900 dark:text-white mb-2"><?= __('site.widgets.mp.coming_soon') ?></h3>
                    <p class="text-zinc-500 dark:text-zinc-400 mb-6"><?= __('site.widgets.mp.coming_soon_desc') ?></p>

                    <!-- Sample Widget Cards (placeholder) -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mt-8 opacity-50">
                        <?php
                        $sampleWidgets = [
                            ['name' => 'Image Slider', 'desc' => 'Responsive image carousel', 'price' => 0, 'downloads' => 1240, 'rating' => 4.8],
                            ['name' => 'Google Maps', 'desc' => 'Embed Google Maps', 'price' => 0, 'downloads' => 890, 'rating' => 4.5],
                            ['name' => 'Video Background', 'desc' => 'Hero with video background', 'price' => 9.99, 'downloads' => 420, 'rating' => 4.9],
                        ];
                        foreach ($sampleWidgets as $sw): ?>
                        <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-xl p-4 text-left border border-zinc-200 dark:border-zinc-600">
                            <div class="w-full h-32 bg-zinc-200 dark:bg-zinc-600 rounded-lg mb-3"></div>
                            <h4 class="font-semibold text-zinc-900 dark:text-white text-sm"><?= $sw['name'] ?></h4>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1"><?= $sw['desc'] ?></p>
                            <div class="flex items-center justify-between mt-3">
                                <span class="text-sm font-bold <?= $sw['price'] > 0 ? 'text-blue-600 dark:text-blue-400' : 'text-green-600 dark:text-green-400' ?>">
                                    <?= $sw['price'] > 0 ? '$' . number_format($sw['price'], 2) : __('site.widgets.mp.free') ?>
                                </span>
                                <span class="text-xs text-zinc-400"><?= number_format($sw['downloads']) ?> <?= __('site.widgets.mp.downloads') ?></span>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
