<?php
/**
 * 키오스크 관리 메인 페이지
 */
include __DIR__ . '/_init.php';

$pageTitle = __('reservations.kiosk') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$pageHeaderTitle = __('reservations.kiosk');
?>
<?php include __DIR__ . '/../reservations/_head.php'; ?>

        <div class="flex items-center justify-between mb-6">
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= __('reservations.kiosk') ?></h1>
            <div class="flex gap-3">
                <a href="<?= $adminUrl ?>/kiosk/settings"
                   class="inline-flex items-center gap-2 px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg text-sm font-medium hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                    <?= __('reservations.kiosk_settings') ?>
                </a>
                <a href="<?= $adminUrl ?>/kiosk/run" target="_blank"
                   class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-medium transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    <?= __('reservations.kiosk_preview') ?>
                </a>
            </div>
        </div>

        <!-- 키오스크 상태 카드 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-8 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-2xl bg-blue-100 dark:bg-blue-900/30 mb-4">
                <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                </svg>
            </div>
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2"><?= __('reservations.kiosk') ?></h2>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6">키오스크 URL: <code class="px-2 py-1 bg-zinc-100 dark:bg-zinc-800 rounded text-xs"><?= htmlspecialchars($baseUrl) ?>/kiosk</code></p>
            <div class="flex justify-center gap-3">
                <a href="<?= $adminUrl ?>/kiosk/settings" class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg text-sm hover:bg-zinc-50 dark:hover:bg-zinc-800 transition"><?= __('reservations.kiosk_settings') ?></a>
                <a href="<?= $adminUrl ?>/kiosk/run" target="_blank" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm transition"><?= __('reservations.kiosk_preview') ?></a>
            </div>
        </div>

    </div>
    </main>
</div>

<script>
console.log('[Kiosk] Admin kiosk page loaded');
</script>

</body>
</html>
