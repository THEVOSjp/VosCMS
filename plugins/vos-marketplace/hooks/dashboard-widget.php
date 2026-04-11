<?php
/**
 * 마켓플레이스 대시보드 위젯
 * admin.dashboard.render 훅에서 업데이트 알림 표시
 */
return function () {
    $pm = \RzxLib\Core\Plugin\PluginManager::getInstance();
    if (!$pm) return;

    $autoCheck = $pm->getSetting('vos-marketplace', 'auto_update_check', '1');
    if ($autoCheck !== '1') return;

    $baseUrl = $_ENV['APP_URL'] ?? '';
    $adminPath = $_ENV['ADMIN_PATH'] ?? 'admin';
    $adminUrl = $baseUrl . '/' . $adminPath;
    $locale = $_SESSION['locale'] ?? 'ko';

    $labels = [
        'ko' => ['title' => '마켓플레이스', 'browse' => '탐색하기'],
        'en' => ['title' => 'Marketplace', 'browse' => 'Browse'],
        'ja' => ['title' => 'マーケットプレイス', 'browse' => '検索する'],
    ];
    $l = $labels[$locale] ?? $labels['en'];
    ?>
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-5">
        <div class="flex items-center justify-between">
            <div class="flex items-center gap-3">
                <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
                    </svg>
                </div>
                <div>
                    <h3 class="font-semibold text-zinc-800 dark:text-zinc-200"><?= $l['title'] ?></h3>
                </div>
            </div>
            <a href="<?= $adminUrl ?>/marketplace"
               class="px-3 py-1.5 text-xs font-medium bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-lg hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition-colors">
                <?= $l['browse'] ?> &rarr;
            </a>
        </div>
    </div>
    <?php
};
