<?php
/**
 * VosCMS 서비스 신청 — 시스템 페이지
 * 도메인 + 호스팅 + 부가서비스 원스톱 신청
 */
require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
$isLoggedIn = \RzxLib\Core\Auth\Auth::check();
$currentUser = $isLoggedIn ? \RzxLib\Core\Auth\Auth::user() : null;
$baseUrl = rtrim($config['app_url'] ?? '', '/');
$isAdmin = !empty($_SESSION['admin_id']);

// 서비스 설정 (DB에서 로드, 없으면 기본값)
$serviceSettings = [];
try {
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
    $sStmt = $pdo->prepare("SELECT `key`, `value` FROM {$prefix}settings WHERE `key` LIKE 'service_%'");
    $sStmt->execute();
    while ($r = $sStmt->fetch(PDO::FETCH_ASSOC)) $serviceSettings[$r['key']] = $r['value'];
} catch (\Throwable $e) {}

$pageWidth = $serviceSettings['service_page_width'] ?? '4xl';
$widthClass = match($pageWidth) {
    'lg' => 'max-w-lg', 'xl' => 'max-w-xl', '2xl' => 'max-w-2xl',
    '3xl' => 'max-w-3xl', '5xl' => 'max-w-5xl', '6xl' => 'max-w-6xl',
    '7xl' => 'max-w-7xl', 'full' => 'max-w-full px-8',
    default => 'max-w-4xl',
};

// 호스팅 설정 데이터 파싱
$_hostingPlans = json_decode($serviceSettings['service_hosting_plans'] ?? '', true) ?: [
    ['label'=>'무료','capacity'=>'50MB','price'=>0,'features'=>'광고 포함,1개월','locked'=>true],
    ['label'=>'추천','capacity'=>'1GB','price'=>5000,'features'=>''],
];
$_hostingPeriods = json_decode($serviceSettings['service_hosting_periods'] ?? '', true) ?: [
    ['months'=>1,'discount'=>0],['months'=>12,'discount'=>10],
];
$_hostingStorage = json_decode($serviceSettings['service_hosting_storage'] ?? '', true) ?: [];
$_hostingFeatures = json_decode($serviceSettings['service_hosting_features'] ?? '', true) ?: [];

// 무료 도메인
$_freeDomains = json_decode($serviceSettings['service_free_domains'] ?? '', true) ?: ['21ces.net'];
$_defaultFreeDomain = $_freeDomains[0] ?? '21ces.net';
$_blockedSubs = json_decode($serviceSettings['service_blocked_subdomains'] ?? '', true) ?: ['www','mail','ftp','admin','test*','dev','staging','api','ns[n]','mx','smtp','pop','imap','localhost','cpanel','webmail'];

// 부가 서비스 데이터
$_addons = json_decode($serviceSettings['service_addons'] ?? '', true) ?: [
    ['_id'=>'install', 'label'=>'설치 지원','desc'=>'VosCMS 설치 및 초기 설정을 대행합니다.','price'=>0,'unit'=>'','checked'=>true],
    ['_id'=>'support', 'label'=>'기술 지원 (1년)','desc'=>'이메일/채팅 기술 지원, 버그 수정, 보안 업데이트 적용.','price'=>120000,'unit'=>'/년'],
    ['_id'=>'custom',  'label'=>'커스터마이징 개발','desc'=>'맞춤 디자인, 전용 플러그인 개발, 외부 시스템 연동.','price'=>0,'unit'=>'별도 견적'],
    ['_id'=>'bizmail', 'label'=>'비즈니스 메일','desc'=>'대용량 첨부파일 전송, 계정당 10GB, 광고 없는 웹메일.','price'=>5000,'unit'=>'/계정/월'],
];
$_maintenance = json_decode($serviceSettings['service_maintenance'] ?? '', true) ?: [
    ['_id'=>'basic',      'label'=>'Basic','price'=>10000,'desc'=>'보안 업데이트 적용, 월 1회 백업 확인'],
    ['_id'=>'standard',   'label'=>'Standard','price'=>20000,'desc'=>'보안 업데이트, 플러그인/코어 업데이트, 주 1회 백업'],
    ['_id'=>'pro',        'label'=>'Pro','price'=>30000,'desc'=>'Standard + 성능 모니터링, 장애 대응, 일일 백업'],
    ['_id'=>'enterprise', 'label'=>'Enterprise','price'=>50000,'desc'=>'Pro + 전담 매니저, 긴급 장애 대응, 커스텀 기능','badge'=>'포털 · 쇼핑몰'],
];

// 다국어 매핑 안정화: _id 없는 항목에 자동 부여 (기존 'key' 마이그레이션 → 없으면 timestamp). 새 항목 표시용 fallback.
foreach ($_addons as $i => $a) {
    if (!empty($a['_id'])) continue;
    $_addons[$i]['_id'] = $a['key'] ?? ('a' . dechex(crc32(($a['label'] ?? '') . $i)));
}
foreach ($_maintenance as $i => $m) {
    if (!empty($m['_id'])) continue;
    $_maintenance[$i]['_id'] = 'm' . dechex(crc32(($m['label'] ?? '') . $i));
}

// 결제 설정 로드
$_payConf = json_decode($siteSettings['payment_config'] ?? '{}', true) ?: [];
$_payGateway = $_payConf['gateway'] ?? 'stripe';
$_payGateways = $_payConf['gateways'] ?? [];
$_payGwConf = $_payGateways[$_payGateway] ?? [];
$_payPubKey = $_payGwConf['public_key'] ?? $_payConf['public_key'] ?? '';
$_payEnabled = !empty($_payConf['enabled']);
$_payTestMode = !empty($_payGwConf['test_mode']) || !empty($_payConf['test_mode']);

// 표시 통화 헬퍼
$_dispCur = $serviceSettings['service_currency'] ?? 'KRW';
$_dispSymbols = ['KRW'=>'₩','USD'=>'$','JPY'=>'¥','CNY'=>'¥','EUR'=>'€'];
$_dispSym = $_dispSymbols[$_dispCur] ?? $_dispCur;
$_dispPrefix = in_array($_dispCur, ['USD','JPY','CNY','EUR']);
function displayPrice($amount) {
    global $_dispSym, $_dispPrefix;
    $formatted = number_format((int)$amount);
    return $_dispPrefix ? $_dispSym . $formatted : $formatted . $_dispSym;
}

// plugin lang (services) 로드
$_svcLangFile = BASE_PATH . '/plugins/vos-hosting/lang/' . \RzxLib\Core\I18n\Translator::getLocale() . '/services.php';
if (!file_exists($_svcLangFile)) {
    $_svcLangFile = BASE_PATH . '/plugins/vos-hosting/lang/en/services.php';
}
if (file_exists($_svcLangFile)) {
    \RzxLib\Core\I18n\Translator::merge('services', require $_svcLangFile);
}

$pageTitle = __('site.pages.service_order') ?? 'VosCMS 서비스 신청';

// ─── 페이지 스킨 설정 로드 (스킨 탭에서 입력한 hero 설정) ───
$_pageCfgRow = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = ?");
$_pageCfgRow->execute(['page_config_service/order']);
$_pageCfg = json_decode($_pageCfgRow->fetchColumn() ?: '{}', true) ?: [];
$_skin = $_pageCfg['skin_config'] ?? [];

// hero 표시 토글 (기본: 표시)
$_showTitle = !isset($_skin['show_title']) || in_array($_skin['show_title'], ['1', 1, true, 'true'], true);

// 다국어 제목/부제 (스킨 입력 우선, 없으면 기본 페이지 제목)
$_skinTitle = $_skin['page_title'] ? db_trans('skin_config.page_title', null, $_skin['page_title']) : '';
$_skinSubtitle = $_skin['page_subtitle'] ? db_trans('skin_config.page_subtitle', null, $_skin['page_subtitle']) : '';
$_heroTitle = $_skinTitle ?: $pageTitle;
$_heroSubtitle = $_skinSubtitle;

// 배경
$_bgImage = $_skin['title_bg_image'] ?? '';
$_bgHeight = (int)($_skin['title_bg_height'] ?? 0);
$_bgOverlay = (int)($_skin['title_bg_overlay'] ?? 0);
$_textColor = $_skin['title_text_color'] ?? 'white'; // 'white' | 'dark'
$_isDarkText = $_textColor === 'dark';

include BASE_PATH . '/skins/layouts/' . ($siteSettings['site_layout'] ?? 'modern') . '/header.php';
?>

<?php if ($_showTitle): ?>
<!-- 헤더 (hero) -->
<div class="relative <?= $_bgImage ? 'bg-cover bg-center' : 'bg-gradient-to-r from-blue-600 to-blue-800 dark:from-blue-900 dark:to-zinc-900' ?> <?= $_isDarkText ? 'text-zinc-900 dark:text-white' : 'text-white' ?>"
     style="<?= $_bgImage ? 'background-image:url(\''.htmlspecialchars($_bgImage).'\');' : '' ?> <?= $_bgHeight > 0 ? 'min-height:'.(int)$_bgHeight.'px;' : 'padding:3rem 0;' ?> <?= $_bgHeight > 0 ? 'display:flex;align-items:center;justify-content:center;' : '' ?>">
    <?php if ($_bgImage && $_bgOverlay > 0): ?>
    <div class="absolute inset-0 <?= $_isDarkText ? 'bg-white' : 'bg-black' ?>" style="opacity:<?= $_bgOverlay / 100 ?>"></div>
    <?php endif; ?>
    <div class="<?= $widthClass ?> mx-auto px-4 text-center relative z-10">
        <h1 class="text-3xl font-bold mb-2"><?= htmlspecialchars($_heroTitle) ?></h1>
        <?php if ($_heroSubtitle): ?>
        <p class="<?= $_isDarkText ? 'text-zinc-600 dark:text-zinc-300' : 'text-blue-100 dark:text-blue-200' ?>"><?= htmlspecialchars($_heroSubtitle) ?></p>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<div class="<?= $widthClass ?> mx-auto px-4 py-8 space-y-8">

    <?php if ($isAdmin): ?>
    <div class="flex justify-end -mb-4">
        <a href="<?= $baseUrl ?>/service/order/settings?tab=skin" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs text-gray-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-gray-100 dark:hover:bg-zinc-800 rounded-lg transition" title="<?= htmlspecialchars(__('site.pages.settings_title') ?: '페이지 설정') ?>">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <span><?= htmlspecialchars(__('site.pages.settings_title') ?: '페이지 설정') ?></span>
        </a>
    </div>
    <?php endif; ?>


    <!-- ① 도메인 -->
    <section class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-gray-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 dark:bg-zinc-800/50 border-b border-gray-200 dark:border-zinc-700">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-bold">1</span>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white"><?= htmlspecialchars(__('services.order.domain.title')) ?></h2>
                <span class="text-xs text-gray-400 dark:text-zinc-500 ml-1"><?= htmlspecialchars(__('services.order.domain.optional')) ?></span>
            </div>
        </div>
        <div class="p-6">
            <div class="flex flex-wrap items-center gap-3 mb-4">
                <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="domain_option" value="free" class="text-blue-600" checked onchange="toggleDomainOption('free')"><span class="text-sm font-medium text-green-600"><?= htmlspecialchars(__('services.order.domain.opt_free')) ?></span></label>
                <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="domain_option" value="new" class="text-blue-600" onchange="toggleDomainOption('new')"><span class="text-sm font-medium text-gray-700 dark:text-zinc-300"><?= htmlspecialchars(__('services.order.domain.opt_buy')) ?></span></label>
                <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="domain_option" value="existing" class="text-blue-600" onchange="toggleDomainOption('existing')"><span class="text-sm font-medium text-gray-700 dark:text-zinc-300"><?= htmlspecialchars(__('services.order.domain.opt_existing')) ?></span></label>
            </div>

            <!-- 무료 서브도메인 입력 -->
            <div id="domainFree">
                <div class="flex items-center gap-2">
                    <div class="flex-1 flex items-center border border-gray-300 dark:border-zinc-600 rounded-lg overflow-hidden">
                        <input type="text" id="freeSubdomain" placeholder="mysite" class="flex-1 px-4 py-3 text-sm bg-white dark:bg-zinc-700 dark:text-white border-0 focus:ring-0" title="<?= htmlspecialchars(__('services.order.domain.subdomain_title')) ?>">
                        <select id="freeDomainSelect" class="px-3 py-3 text-sm text-gray-500 dark:text-zinc-400 bg-gray-50 dark:bg-zinc-600 border-l border-gray-300 dark:border-zinc-600 focus:ring-0 border-0 font-medium">
                            <?php foreach ($_freeDomains as $fd): ?>
                            <option value="<?= htmlspecialchars($fd) ?>">.<?= htmlspecialchars($fd) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="button" onclick="checkSubdomain()" class="px-4 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition text-sm whitespace-nowrap"><?= htmlspecialchars(__('services.order.domain.btn_check')) ?></button>
                </div>
                <p class="text-xs text-gray-400 dark:text-zinc-500 mt-2"><?= htmlspecialchars(__('services.order.domain.help_subdomain')) ?></p>
                <div id="subdomainResult" class="hidden mt-2"></div>
            </div>

            <!-- 도메인 구입 검색 -->
            <div id="domainSearch" class="hidden">
                <div class="flex gap-2">
                    <input type="text" id="domainInput" placeholder="<?= htmlspecialchars(__('services.order.domain.search_placeholder')) ?>" class="flex-1 px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 text-sm" onkeydown="if(event.key==='Enter')searchDomain()">
                    <button onclick="searchDomain()" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition text-sm whitespace-nowrap"><?= htmlspecialchars(__('services.order.domain.btn_search')) ?></button>
                </div>
                <p class="text-xs text-gray-400 dark:text-zinc-500 mt-2"><?= htmlspecialchars(__('services.order.domain.help_search')) ?></p>
                <div id="domainResults" class="hidden mt-4">
                    <div id="domainLoading" class="hidden text-center py-6">
                        <div class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-zinc-400">
                            <svg class="w-5 h-5 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                            <?= htmlspecialchars(__('services.order.domain.searching')) ?>
                        </div>
                    </div>
                    <div id="domainList" class="space-y-2"></div>
                    <div id="domainConfirmWrap" class="hidden mt-4 flex items-center justify-between p-4 bg-blue-50 dark:bg-blue-900/30 rounded-xl border border-blue-200 dark:border-blue-800">
                        <p class="text-sm text-blue-800 dark:text-blue-200"><?= str_replace(':count', '<span id="domainSelectedCount" class="font-bold">0</span>', htmlspecialchars(__('services.order.domain.selected_summary'))) ?> <span id="domainSelectedTotal" class="font-bold">0</span> <span class="text-xs text-blue-600/60"><?= htmlspecialchars(__('services.order.domain.tax_excluded')) ?></span></p>
                        <button onclick="confirmDomains()" class="px-6 py-2.5 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition text-sm"><?= htmlspecialchars(__('services.order.domain.btn_confirm')) ?></button>
                    </div>
                    <div id="domainConfirmed" class="hidden mt-4 space-y-2"></div>
                </div>
            </div>

            <!-- 기존 도메인 입력 -->
            <div id="domainExisting" class="hidden">
                <input type="text" name="existing_domain" placeholder="<?= htmlspecialchars(__('services.order.domain.existing_placeholder')) ?>" class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 text-sm" onchange="if(this.value.trim()) updateMailDomain(this.value.trim())">
                <p class="text-xs text-gray-400 dark:text-zinc-500 mt-2"><?= htmlspecialchars(__('services.order.domain.help_existing')) ?></p>
            </div>

        </div>
    </section>

    <!-- ② 웹 호스팅 -->
    <section class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-gray-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 dark:bg-zinc-800/50 border-b border-gray-200 dark:border-zinc-700">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-bold">2</span>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white"><?= htmlspecialchars(__('services.order.hosting.title')) ?></h2>
                <span class="text-xs text-blue-600 font-medium ml-1"><?= htmlspecialchars(__('services.order.hosting.required')) ?></span>
            </div>
        </div>
        <div class="p-6">
            <?php
            // 메인 플랜 (최대 5개) + 나머지는 접기
            $mainPlans = array_slice($_hostingPlans, 0, 5);
            $morePlans = array_slice($_hostingPlans, 5);
            $defaultIdx = 0;
            foreach ($mainPlans as $i => $p) { if (($p['label'] ?? '') === '추천') $defaultIdx = $i; }
            ?>
            <div class="grid grid-cols-3 md:grid-cols-5 gap-3 mb-6">
                <?php foreach ($mainPlans as $i => $plan):
                    $isFree = (int)($plan['price'] ?? 0) === 0;
                    $isDefault = ($i === $defaultIdx);
                    $val = strtolower(str_replace([' '], '', $plan['capacity'] ?? 'plan'.$i));
                    $psid = $plan['_id'] ?? '';
                    $_planLabel = $psid ? db_trans("service.hosting.plan.{$psid}.label", null, $plan['label'] ?? '') : ($plan['label'] ?? '');
                    $_planFeatRaw = $psid ? db_trans("service.hosting.plan.{$psid}.features", null, $plan['features'] ?? '') : ($plan['features'] ?? '');
                ?>
                <label class="hosting-option cursor-pointer border-2 <?= $isDefault ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 selected' : 'border-gray-200 dark:border-zinc-600' ?> rounded-xl p-4 text-center <?= !$isDefault ? ($isFree ? 'hover:border-green-400' : 'hover:border-blue-400') : '' ?> transition">
                    <input type="radio" name="hosting_plan" value="<?= htmlspecialchars($val) ?>" class="hidden" <?= $isDefault ? 'checked' : '' ?> data-price="<?= (int)$plan['price'] ?>">
                    <p class="text-xs <?= $isFree ? 'text-green-600' : ($isDefault ? 'text-blue-600' : 'text-gray-400 dark:text-zinc-500') ?> font-semibold mb-1"><?= htmlspecialchars($_planLabel) ?></p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($plan['capacity'] ?? '') ?></p>
                    <?php if ($isFree): ?>
                    <p class="text-green-600 font-bold mt-1"><?= displayPrice(0) ?></p>
                    <?php if (!empty($_planFeatRaw)): ?><p class="text-[10px] text-gray-400 dark:text-zinc-500 mt-0.5"><?= htmlspecialchars(str_replace(',', ' · ', $_planFeatRaw)) ?></p><?php endif; ?>
                    <?php else: ?>
                    <p class="text-blue-600 font-bold mt-1"><?= displayPrice($plan['price']) ?><span class="text-xs font-normal text-gray-400"><?= htmlspecialchars(__('services.order.hosting.price_per_month')) ?></span></p>
                    <?php endif; ?>
                </label>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($morePlans)): ?>
            <details class="mb-6">
                <summary class="text-xs text-blue-600 cursor-pointer hover:underline"><?= htmlspecialchars(__('services.order.hosting.more_plans')) ?></summary>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-3">
                    <?php foreach ($morePlans as $plan):
                        $val = strtolower(str_replace([' '], '', $plan['capacity'] ?? ''));
                        $psid = $plan['_id'] ?? '';
                        $_planLabel = $psid ? db_trans("service.hosting.plan.{$psid}.label", null, $plan['label'] ?? '') : ($plan['label'] ?? '');
                    ?>
                    <label class="hosting-option cursor-pointer border-2 border-gray-200 dark:border-zinc-600 rounded-xl p-4 text-center hover:border-blue-400 transition">
                        <input type="radio" name="hosting_plan" value="<?= htmlspecialchars($val) ?>" class="hidden" data-price="<?= (int)$plan['price'] ?>">
                        <p class="text-xs text-gray-400 mb-1"><?= htmlspecialchars($_planLabel) ?></p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($plan['capacity'] ?? '') ?></p>
                        <p class="text-blue-600 font-bold mt-1"><?= displayPrice($plan['price']) ?><span class="text-xs font-normal text-gray-400"><?= htmlspecialchars(__('services.order.hosting.price_per_month')) ?></span></p>
                    </label>
                    <?php endforeach; ?>
                </div>
            </details>
            <?php endif; ?>

            <!-- 무료 플랜 안내 (무료 선택 시만 표시) -->
            <div id="freePlanNotice" class="hidden p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl mb-4">
                <p class="text-sm text-green-800 dark:text-green-200 font-medium"><?= __('services.order.hosting.free_plan_notice') ?></p>
                <p class="text-xs text-green-600 dark:text-green-400 mt-1"><?= htmlspecialchars(__('services.order.hosting.free_plan_features')) ?></p>
            </div>

            <!-- 계약 기간 (무료 선택 시 숨김) -->
            <div id="hostingPeriodWrap" class="flex flex-wrap items-center gap-3 p-4 bg-gray-50 dark:bg-zinc-700/50 rounded-xl mb-4">
                <span class="text-sm font-medium text-gray-600 dark:text-zinc-300"><?= htmlspecialchars(__('services.order.hosting.period_label')) ?></span>
                <?php foreach ($_hostingPeriods as $j => $pd):
                    $months = (int)$pd['months'];
                    $disc = (int)$pd['discount'];
                    $periodLabel = $months >= 12 ? ($months / 12) . __('services.order.hosting.unit_year') : $months . __('services.order.hosting.unit_month');
                    $isDefaultPeriod = ($months === 12);
                ?>
                <label class="flex items-center gap-1.5 cursor-pointer">
                    <input type="radio" name="hosting_period" value="<?= $months ?>" class="text-blue-600" <?= $isDefaultPeriod ? 'checked' : '' ?>>
                    <span class="text-sm text-gray-700 dark:text-zinc-300"><?= $periodLabel ?><?php if ($disc > 0): ?> <span class="<?= $disc >= 30 ? 'text-red-500' : 'text-blue-600' ?> text-xs font-semibold">-<?= $disc ?>%</span><?php endif; ?></span>
                </label>
                <?php endforeach; ?>
            </div>

            <!-- 추가 용량 (무료 선택 시 숨김) -->
            <?php if (!empty($_hostingStorage)): ?>
            <div id="hostingStorageWrap" class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-zinc-700/50 rounded-xl mb-4">
                <span class="text-sm font-medium text-gray-600 dark:text-zinc-300"><?= htmlspecialchars(__('services.order.hosting.storage_label')) ?></span>
                <select name="hosting_storage" class="px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg text-sm">
                    <option value="0"><?= htmlspecialchars(__('services.order.hosting.storage_none')) ?></option>
                    <?php foreach ($_hostingStorage as $st): ?>
                    <option value="<?= htmlspecialchars(strtolower($st['capacity'])) ?>" data-price="<?= (int)$st['price'] ?>" data-label="+<?= htmlspecialchars($st['capacity']) ?>">+<?= htmlspecialchars($st['capacity']) ?> (<?= displayPrice($st['price']) ?><?= htmlspecialchars(__('services.order.hosting.price_per_month')) ?>)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if (!empty($_hostingFeatures)): ?>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs text-gray-500 dark:text-zinc-400">
                <?php foreach ($_hostingFeatures as $feat):
                    // string (구버전) 또는 객체 ({_id, text}) 양쪽 호환
                    if (is_string($feat)) { $_fsid = ''; $_ftext = $feat; }
                    else { $_fsid = $feat['_id'] ?? ''; $_ftext = $feat['text'] ?? ''; }
                    $_featDisplay = $_fsid ? db_trans("service.hosting.feature.{$_fsid}.text", null, $_ftext) : $_ftext;
                ?>
                <span class="flex items-center gap-1"><svg class="w-3.5 h-3.5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg><?= htmlspecialchars($_featDisplay) ?></span>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </section>

    <!-- ③ 부가 서비스 -->
    <?php include __DIR__ . '/_addons.php'; ?>

    <!-- ④ 신청자 정보 -->
    <?php include __DIR__ . '/_applicant.php'; ?>

    <!-- ⑤ 결제 방법 -->
    <?php include __DIR__ . '/_payment.php'; ?>

    <!-- ⑥ 주문 요약 -->
    <?php include __DIR__ . '/_summary.php'; ?>

</div>

<!-- 주문 확인 모달 -->
<div id="orderConfirmModal" class="hidden fixed inset-0 z-50 bg-black/60 flex items-center justify-center p-4" onclick="if(event.target===this)closeOrderConfirmModal()">
    <div class="bg-white dark:bg-zinc-800 rounded-2xl max-w-2xl w-full max-h-[90vh] flex flex-col shadow-2xl">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-700 flex-shrink-0">
            <h3 class="text-lg font-bold text-gray-900 dark:text-white"><?= htmlspecialchars(__('services.order.confirm.title')) ?></h3>
            <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1"><?= htmlspecialchars(__('services.order.confirm.desc')) ?></p>
        </div>
        <div class="p-6 overflow-y-auto space-y-5 text-sm">
            <!-- 신청 서비스 -->
            <div>
                <h4 class="text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-2"><?= htmlspecialchars(__('services.order.confirm.section_services')) ?></h4>
                <div id="confirmServices" class="bg-gray-50 dark:bg-zinc-900 rounded-xl p-4 text-gray-700 dark:text-zinc-200"></div>
            </div>
            <!-- 신청자 정보 -->
            <div>
                <h4 class="text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-2"><?= htmlspecialchars(__('services.order.confirm.section_applicant')) ?></h4>
                <div id="confirmApplicant" class="bg-gray-50 dark:bg-zinc-900 rounded-xl p-4 space-y-1.5 text-gray-700 dark:text-zinc-200"></div>
            </div>
            <!-- 결제 정보 -->
            <div>
                <h4 class="text-xs font-bold text-gray-500 dark:text-zinc-400 uppercase tracking-wider mb-2"><?= htmlspecialchars(__('services.order.confirm.section_payment')) ?></h4>
                <div id="confirmPayment" class="bg-gray-50 dark:bg-zinc-900 rounded-xl p-4 text-gray-700 dark:text-zinc-200"></div>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-zinc-700 flex gap-3 flex-shrink-0">
            <button type="button" onclick="closeOrderConfirmModal()" class="flex-1 px-4 py-3 border border-gray-300 dark:border-zinc-600 text-gray-700 dark:text-zinc-300 font-semibold rounded-xl hover:bg-gray-50 dark:hover:bg-zinc-700 transition"><?= htmlspecialchars(__('services.order.confirm.btn_cancel')) ?></button>
            <button type="button" id="btnConfirmAndPay" onclick="confirmAndPay()" class="flex-1 px-4 py-3 bg-blue-600 hover:bg-blue-700 text-white font-bold rounded-xl transition shadow-md"><?= htmlspecialchars(__('services.order.confirm.btn_confirm')) ?></button>
        </div>
    </div>
</div>

<!-- JS -->
<?php
// JS 측 다국어 메시지 (영역별 점진 추가)
$_i18nJs = [
    'domain.alert_min_2chars'    => __('services.order.domain.alert_min_2chars'),
    'domain.search_failed'       => __('services.order.domain.search_failed'),
    'domain.search_error'        => __('services.order.domain.search_error'),
    'domain.status_available'    => __('services.order.domain.status_available'),
    'domain.status_taken'        => __('services.order.domain.status_taken'),
    'domain.status_unavailable'  => __('services.order.domain.status_unavailable'),
    'domain.status_unknown'      => __('services.order.domain.status_unknown'),
    'domain.selected_label'      => __('services.order.domain.selected_label'),
    'domain.search_again'        => __('services.order.domain.search_again'),
    'domain.price_per_year'      => __('services.order.domain.price_per_year'),
    'domain.tax_label'           => __('services.order.domain.tax_label'),
    'domain.select_domain'       => __('services.order.domain.select_domain'),
    'domain.subdomain_min_2chars'=> __('services.order.domain.subdomain_min_2chars'),
    'domain.subdomain_blocked'   => __('services.order.domain.subdomain_blocked'),
    'domain.subdomain_available' => __('services.order.domain.subdomain_available'),
    'hosting.price_per_month'    => __('services.order.hosting.price_per_month'),
    'addons.pw_placeholder'      => __('services.order.addons.pw_placeholder'),
    'addons.alert_max_mail'      => __('services.order.addons.alert_max_mail'),
    'addons.alert_max_bizmail'   => __('services.order.addons.alert_max_bizmail'),
    'hosting.unit_year'          => __('services.order.hosting.unit_year'),
    'hosting.unit_month'         => __('services.order.hosting.unit_month'),
    'summary.col_product'        => __('services.order.summary.col_product'),
    'summary.col_qty'            => __('services.order.summary.col_qty'),
    'summary.col_amount'         => __('services.order.summary.col_amount'),
    'summary.subtotal'           => __('services.order.summary.subtotal'),
    'summary.vat'                => __('services.order.summary.vat'),
    'summary.total'              => __('services.order.summary.total'),
    'summary.free'               => __('services.order.summary.free'),
    'summary.final_amount'       => __('services.order.summary.final_amount'),
    'summary.final_amount_note'  => __('services.order.summary.final_amount_note'),
    'summary.domain_label'       => __('services.order.summary.domain_label'),
    'summary.hosting_label_prefix' => __('services.order.summary.hosting_label_prefix'),
    'summary.maint_label_prefix' => __('services.order.summary.maint_label_prefix'),
    'summary.storage_label_prefix' => __('services.order.summary.storage_label_prefix'),
    'summary.domain_qty'         => __('services.order.summary.domain_qty'),
    'summary.bizmail_qty'        => __('services.order.summary.bizmail_qty'),
    'summary.discount_long'      => __('services.order.summary.discount_long'),
    'summary.discount'           => __('services.order.summary.discount'),
    'summary.price_quote_note'   => __('services.order.summary.price_quote_note'),
    'checkout.btn_pay'           => __('services.order.checkout.btn_pay'),
    'checkout.btn_submit'        => __('services.order.checkout.btn_submit'),
    'checkout.btn_processing'    => __('services.order.checkout.btn_processing'),
    'checkout.alert_terms'       => __('services.order.checkout.alert_terms'),
    'checkout.alert_no_payment_init' => __('services.order.checkout.alert_no_payment_init'),
    'checkout.alert_card_required' => __('services.order.checkout.alert_card_required'),
    'checkout.alert_card_error'  => __('services.order.checkout.alert_card_error'),
    'checkout.alert_order_failed' => __('services.order.checkout.alert_order_failed'),
    'checkout.alert_order_request_failed' => __('services.order.checkout.alert_order_request_failed'),
    'checkout.alert_subdomain_empty' => __('services.order.checkout.alert_subdomain_empty'),
    'checkout.alert_domain_empty' => __('services.order.checkout.alert_domain_empty'),
    'checkout.alert_applicant_required' => __('services.order.checkout.alert_applicant_required'),
    'checkout.alert_install_required' => __('services.order.checkout.alert_install_required'),
    'confirm.free_order'         => __('services.order.confirm.free_order'),
    'applicant.name_label'       => __('services.order.applicant.name_label'),
    'applicant.email_label'      => __('services.order.applicant.email_label'),
    'applicant.phone_label'      => __('services.order.applicant.phone_label'),
    'payment.title'              => __('services.order.payment.title'),
    'payment.method_card'        => __('services.order.payment.method_card'),
    'payment.method_bank'        => __('services.order.payment.method_bank'),
];
?>
<script>
var siteCurrency = '<?= $_dispCur ?>';
var siteBaseUrl = '<?= $baseUrl ?>';
var isLoggedIn = <?= $isLoggedIn ? 'true' : 'false' ?>;
var svcHostingPlans = <?= json_encode($_hostingPlans, JSON_UNESCAPED_UNICODE) ?>;
var svcHostingPeriods = <?= json_encode($_hostingPeriods, JSON_UNESCAPED_UNICODE) ?>;
var svcAddons = <?= json_encode($_addons, JSON_UNESCAPED_UNICODE) ?>;
var svcMaintenance = <?= json_encode($_maintenance, JSON_UNESCAPED_UNICODE) ?>;
var svcFreeDomains = <?= json_encode($_freeDomains, JSON_UNESCAPED_UNICODE) ?>;
var svcDefaultFreeDomain = '<?= addslashes($_defaultFreeDomain) ?>';
var svcBlockedSubs = <?= json_encode($_blockedSubs) ?>;
var svcRounding = <?= json_encode(json_decode($serviceSettings['service_rounding'] ?? '{}', true) ?: ['KRW'=>'1000','USD'=>'1','JPY'=>'100','CNY'=>'10','EUR'=>'1']) ?>;
var I18N = <?= json_encode($_i18nJs, JSON_UNESCAPED_UNICODE) ?>;
function t(key, replace) {
    var s = (typeof I18N !== 'undefined' && I18N[key]) ? I18N[key] : key;
    if (replace) for (var k in replace) s = s.split(':' + k).join(replace[k]);
    return s;
}
</script>
<script src="<?= $baseUrl ?>/plugins/vos-hosting/views/system/service/order.js?v=<?= filemtime(BASE_PATH . '/plugins/vos-hosting/views/system/service/order.js') ?>"></script>

<?php
include BASE_PATH . '/skins/layouts/' . ($siteSettings['site_layout'] ?? 'modern') . '/footer.php';
?>
