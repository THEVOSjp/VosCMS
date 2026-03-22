<?php
/**
 * Booking Widget - render.php
 * 예약 폼을 임베드하는 시스템 위젯
 */
$wTitle = $config['title'] ?? '';
$baseUrl = $baseUrl ?? '';

ob_start();
?>
<section class="py-12">
    <div class="max-w-5xl mx-auto px-4">
        <?php if ($wTitle): ?>
        <div class="text-center mb-8">
            <h2 class="text-3xl font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($wTitle) ?></h2>
        </div>
        <?php endif; ?>
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden" style="min-height:500px">
            <iframe src="<?= $baseUrl ?>/booking?embed=1" class="w-full border-0" style="min-height:500px;height:100vh;max-height:900px" loading="lazy"></iframe>
        </div>
    </div>
</section>
<?php return ob_get_clean();
