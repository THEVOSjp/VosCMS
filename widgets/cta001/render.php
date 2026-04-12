<?php
/**
 * Booking CTA Widget (cta001) - render.php
 * 그라데이션 배경의 유도 배너 — 버튼 최대 3개 지원
 */

// 다국어 헬퍼
$loc = function($val) use ($locale) {
    if (!$val) return '';
    if (is_string($val)) return $val;
    if (is_array($val)) {
        $defaultLocale = $_ENV['DEFAULT_LOCALE'] ?? $_ENV['APP_LOCALE'] ?? 'ko';
        $chain = [$locale];
        if ($locale !== 'en') $chain[] = 'en';
        if ($locale !== $defaultLocale && $defaultLocale !== 'en') $chain[] = $defaultLocale;
        foreach ($chain as $l) { if (!empty($val[$l])) return $val[$l]; }
        foreach ($val as $v) { if (!empty($v)) return $v; }
    }
    return '';
};

$wTitle    = htmlspecialchars($loc($config['title'] ?? '') ?: (__('staff_page.cta_title') ?? 'Get Started Today'));
$wSubtitle = htmlspecialchars($loc($config['subtitle'] ?? '') ?: '');
$wFrom     = $config['gradient_from'] ?? '#2563EB';
$wTo       = $config['gradient_to'] ?? '#9333EA';
$wRounded  = ($config['rounded'] ?? true) !== false && ($config['rounded'] ?? '1') !== '0';
$baseUrl   = $baseUrl ?? '';

// 버튼 (최대 3개)
$buttons = [];
for ($i = 1; $i <= 3; $i++) {
    $suffix = $i === 1 ? '' : '_' . $i;
    $btnText = $loc($config['btn_text' . $suffix] ?? '');
    $btnUrl  = trim($config['btn_url' . $suffix] ?? '');
    if ($btnText && $btnUrl) {
        if (!str_starts_with($btnUrl, 'http')) $btnUrl = $baseUrl . $btnUrl;
        $btnStyle = $config['btn_style' . $suffix] ?? ($i === 1 ? 'primary' : 'outline');
        $buttons[] = ['text' => htmlspecialchars($btnText), 'url' => htmlspecialchars($btnUrl), 'style' => $btnStyle];
    }
}

// 레거시 호환 (버튼이 하나도 없으면 기본 1개)
if (empty($buttons)) {
    $btnText = $loc($config['btn_text'] ?? '') ?: (__('common.nav.booking') ?? 'Book Now');
    $btnUrl  = $config['btn_url'] ?? '/booking';
    if (!str_starts_with($btnUrl, 'http')) $btnUrl = $baseUrl . $btnUrl;
    $buttons[] = ['text' => htmlspecialchars($btnText), 'url' => htmlspecialchars($btnUrl), 'style' => 'primary'];
}

ob_start();
?>
<section class="py-12">
    <div class="max-w-7xl mx-auto px-4">
        <div class="text-center p-8 md:p-12 <?= $wRounded ? 'rounded-2xl' : '' ?>" style="background:linear-gradient(135deg, <?= htmlspecialchars($wFrom) ?>, <?= htmlspecialchars($wTo) ?>)">
            <h2 class="text-2xl md:text-3xl font-bold text-white mb-4"><?= $wTitle ?></h2>
            <?php if ($wSubtitle): ?>
            <p class="text-white/80 mb-6 max-w-2xl mx-auto"><?= nl2br($wSubtitle) ?></p>
            <?php endif; ?>
            <div class="flex flex-wrap items-center justify-center gap-3">
                <?php foreach ($buttons as $btn):
                    if ($btn['style'] === 'outline'): ?>
                    <a href="<?= $btn['url'] ?>" class="inline-flex items-center px-8 py-3 border-2 border-white text-white font-semibold rounded-lg hover:bg-white/10 transition">
                        <?= $btn['text'] ?>
                    </a>
                    <?php elseif ($btn['style'] === 'ghost'): ?>
                    <a href="<?= $btn['url'] ?>" class="inline-flex items-center px-6 py-3 text-white/80 hover:text-white font-semibold underline underline-offset-4 transition">
                        <?= $btn['text'] ?>
                    </a>
                    <?php else: ?>
                    <a href="<?= $btn['url'] ?>" class="inline-flex items-center px-8 py-3 bg-white font-semibold rounded-lg hover:bg-gray-100 transition shadow-lg" style="color:<?= htmlspecialchars($wFrom) ?>">
                        <?= $btn['text'] ?>
                        <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                        </svg>
                    </a>
                    <?php endif;
                endforeach; ?>
            </div>
        </div>
    </div>
</section>
<?php return ob_get_clean();
