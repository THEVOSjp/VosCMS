<?php
/**
 * Lookup Widget - render.php
 * 예약 조회 폼을 임베드하는 시스템 위젯
 */
$wTitle = $config['title'] ?? '';
$wSubtitle = $config['subtitle'] ?? '';
$baseUrl = $baseUrl ?? '';

ob_start();
?>
<section class="py-12">
    <div class="max-w-3xl mx-auto px-4">
        <?php if ($wTitle || $wSubtitle): ?>
        <div class="text-center mb-8">
            <?php if ($wTitle): ?><h2 class="text-3xl font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($wTitle) ?></h2><?php endif; ?>
            <?php if ($wSubtitle): ?><p class="text-gray-600 dark:text-zinc-400 mt-2"><?= htmlspecialchars($wSubtitle) ?></p><?php endif; ?>
        </div>
        <?php endif; ?>
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden" style="min-height:300px">
            <iframe src="<?= $baseUrl ?>/lookup?embed=1" class="w-full border-0" style="min-height:300px;height:60vh;max-height:600px" loading="lazy"></iframe>
        </div>
    </div>
</section>
<?php return ob_get_clean();
