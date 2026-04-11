<?php
/**
 * Booking CTA Widget - render.php
 * 그라데이션 배경의 예약 유도 배너
 */
$_t = function_exists('__') ? '__' : function($k) { return $k; };
$wTitle = $config['title'] ?? $_t('staff_page.cta_title');
$wSubtitle = $config['subtitle'] ?? $_t('staff_page.cta_description');
$wBtnText = $config['btn_text'] ?? $_t('common.nav.booking');
$wBtnUrl = $config['btn_url'] ?? '/booking';
$wFrom = $config['gradient_from'] ?? '#2563EB';
$wTo = $config['gradient_to'] ?? '#9333EA';
$wRounded = ($config['rounded'] ?? true) !== false && ($config['rounded'] ?? '1') !== '0';
$baseUrl = $baseUrl ?? '';

if (!str_starts_with($wBtnUrl, 'http')) {
    $wBtnUrl = $baseUrl . $wBtnUrl;
}

ob_start();
?>
<section class="py-12">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center p-8 md:p-12 <?= $wRounded ? 'rounded-2xl' : '' ?>" style="background:linear-gradient(135deg, <?= htmlspecialchars($wFrom) ?>, <?= htmlspecialchars($wTo) ?>)">
            <h2 class="text-2xl md:text-3xl font-bold text-white mb-4"><?= htmlspecialchars($wTitle) ?></h2>
            <?php if ($wSubtitle): ?>
            <p class="text-white/80 mb-6 max-w-2xl mx-auto"><?= htmlspecialchars($wSubtitle) ?></p>
            <?php endif; ?>
            <a href="<?= htmlspecialchars($wBtnUrl) ?>"
               class="inline-flex items-center px-8 py-3 bg-white font-semibold rounded-lg hover:bg-gray-100 transition shadow-lg" style="color:<?= htmlspecialchars($wFrom) ?>">
                <?= htmlspecialchars($wBtnText) ?>
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
        </div>
    </div>
</section>
<?php return ob_get_clean();
