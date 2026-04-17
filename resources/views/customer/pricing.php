<?php
/**
 * Pricing 페이지 — 호스팅 플랜 비교표
 * DB service_hosting_plans 설정에서 자동 로드
 */
$pageTitle = 'Pricing - ' . ($config['app_name'] ?? 'VosCMS');
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$_locale = $currentLocale ?? 'ko';

// 호스팅 플랜 로드
$plans = json_decode($siteSettings['service_hosting_plans'] ?? '[]', true) ?: [];
$periods = json_decode($siteSettings['service_hosting_periods'] ?? '[]', true) ?: [];
$features = json_decode($siteSettings['service_hosting_features'] ?? '[]', true) ?: [];

// 다국어
$L = [
    'ko' => ['title'=>'요금제','subtitle'=>'비즈니스 규모에 맞는 플랜을 선택하세요','monthly'=>'월','free'=>'무료','per_month'=>'/월','popular'=>'인기','start'=>'시작하기','features_title'=>'모든 플랜에 포함','contact'=>'문의하기','custom'=>'맞춤형 플랜이 필요하신가요?','custom_desc'=>'기업 규모에 맞는 맞춤 견적을 제공합니다.'],
    'en' => ['title'=>'Pricing','subtitle'=>'Choose the plan that fits your business','monthly'=>'mo','free'=>'Free','per_month'=>'/mo','popular'=>'Popular','start'=>'Get Started','features_title'=>'Included in all plans','contact'=>'Contact Us','custom'=>'Need a custom plan?','custom_desc'=>'We offer custom quotes tailored to your business.'],
    'ja' => ['title'=>'料金プラン','subtitle'=>'ビジネスに合ったプランをお選びください','monthly'=>'月','free'=>'無料','per_month'=>'/月','popular'=>'人気','start'=>'始める','features_title'=>'全プラン共通','contact'=>'お問い合わせ','custom'=>'カスタムプランが必要ですか？','custom_desc'=>'お見積りをご用意いたします。'],
];
$t = $L[$_locale] ?? $L['en'];

// 통화 포맷
$currency = $siteSettings['service_currency'] ?? 'JPY';
$symbol = match ($currency) { 'KRW' => '₩', 'JPY' => '¥', 'EUR' => '€', default => '$' };
$decimals = in_array($currency, ['KRW', 'JPY']) ? 0 : 2;
?>

<div class="max-w-6xl mx-auto px-4 py-12">
    <!-- 헤더 -->
    <div class="text-center mb-12">
        <div class="text-sm font-semibold text-indigo-600 dark:text-indigo-400 tracking-widest uppercase mb-2">Pricing</div>
        <h1 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-white mb-3"><?= $t['title'] ?></h1>
        <div class="text-zinc-500 dark:text-zinc-400"><?= $t['subtitle'] ?></div>
    </div>

    <!-- 플랜 카드 -->
    <?php if (!empty($plans)): ?>
    <div class="grid grid-cols-1 md:grid-cols-<?= min(count($plans), 4) ?> gap-6 mb-12">
        <?php foreach ($plans as $i => $plan):
            $price = (float)($plan['price'] ?? 0);
            $isFree = $price <= 0;
            $isPopular = $i === 1; // 두 번째 플랜을 인기로
            $planFeatures = isset($plan['features']) ? (is_array($plan['features']) ? $plan['features'] : explode(',', $plan['features'])) : [];
        ?>
        <div class="relative flex flex-col bg-white dark:bg-zinc-800 rounded-2xl border-2 <?= $isPopular ? 'border-indigo-500 shadow-xl shadow-indigo-500/10' : 'border-zinc-200 dark:border-zinc-700' ?> p-6 transition hover:shadow-lg">
            <?php if ($isPopular): ?>
            <div class="absolute -top-3 left-1/2 -translate-x-1/2 px-4 py-1 bg-indigo-600 text-white text-xs font-bold rounded-full"><?= $t['popular'] ?></div>
            <?php endif; ?>

            <h3 class="text-lg font-bold text-zinc-900 dark:text-white mb-1"><?= htmlspecialchars($plan['label'] ?? 'Plan ' . ($i+1)) ?></h3>
            <div class="text-xs text-zinc-400 mb-4"><?= htmlspecialchars($plan['capacity'] ?? '') ?></div>

            <div class="mb-6">
                <?php if ($isFree): ?>
                <span class="text-3xl font-extrabold text-green-600 dark:text-green-400"><?= $t['free'] ?></span>
                <?php else: ?>
                <span class="text-3xl font-extrabold text-zinc-900 dark:text-white"><?= $symbol ?><?= number_format($price, $decimals) ?></span>
                <span class="text-sm text-zinc-400"><?= $t['per_month'] ?></span>
                <?php endif; ?>
            </div>

            <ul class="space-y-2 mb-6 flex-1">
                <?php foreach ($planFeatures as $f): $f = trim($f); if (!$f) continue; ?>
                <li class="flex items-start gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                    <svg class="w-4 h-4 text-green-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                    <?= htmlspecialchars($f) ?>
                </li>
                <?php endforeach; ?>
            </ul>

            <a href="<?= $baseUrl ?>/service/order" class="block text-center py-2.5 rounded-lg text-sm font-bold transition <?= $isPopular ? 'bg-indigo-600 hover:bg-indigo-700 text-white shadow-lg shadow-indigo-600/25' : 'border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700' ?>" style="text-decoration:none !important;<?= $isPopular ? 'color:#fff !important' : 'color:#374151 !important' ?>">
                <?= $t['start'] ?>
            </a>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- 공통 기능 -->
    <?php if (!empty($features)): ?>
    <div class="bg-zinc-50 dark:bg-zinc-800/50 rounded-2xl p-8 mb-12">
        <h2 class="text-lg font-bold text-zinc-900 dark:text-white text-center mb-6"><?= $t['features_title'] ?></h2>
        <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-3">
            <?php foreach ($features as $f): ?>
            <div class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                <svg class="w-4 h-4 text-indigo-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <?= htmlspecialchars($f) ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- CTA -->
    <div class="text-center bg-gradient-to-r from-indigo-50 to-purple-50 dark:from-indigo-900/20 dark:to-purple-900/20 rounded-2xl p-8">
        <h3 class="text-xl font-bold text-zinc-900 dark:text-white mb-2"><?= $t['custom'] ?></h3>
        <div class="text-sm text-zinc-500 dark:text-zinc-400 mb-4"><?= $t['custom_desc'] ?></div>
        <a href="<?= $baseUrl ?>/contact" class="inline-flex items-center gap-2 px-6 py-3 bg-zinc-900 dark:bg-white text-white dark:text-zinc-900 text-sm font-bold rounded-lg hover:bg-zinc-800 dark:hover:bg-zinc-100 transition" style="color:#fff !important;text-decoration:none !important">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
            <?= $t['contact'] ?>
        </a>
    </div>
</div>
