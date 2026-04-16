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
    ['label'=>'설치 지원','desc'=>'VosCMS 설치 및 초기 설정을 대행합니다.','price'=>0,'unit'=>'','checked'=>true],
    ['label'=>'기술 지원 (1년)','desc'=>'이메일/채팅 기술 지원, 버그 수정, 보안 업데이트 적용.','price'=>120000,'unit'=>'/년'],
    ['label'=>'커스터마이징 개발','desc'=>'맞춤 디자인, 전용 플러그인 개발, 외부 시스템 연동.','price'=>0,'unit'=>'별도 견적'],
    ['label'=>'비즈니스 메일','desc'=>'대용량 첨부파일 전송, 계정당 10GB, 광고 없는 웹메일.','price'=>5000,'unit'=>'/계정/월'],
];
$_maintenance = json_decode($serviceSettings['service_maintenance'] ?? '', true) ?: [
    ['label'=>'Basic','price'=>10000,'desc'=>'보안 업데이트 적용, 월 1회 백업 확인'],
    ['label'=>'Standard','price'=>20000,'desc'=>'보안 업데이트, 플러그인/코어 업데이트, 주 1회 백업'],
    ['label'=>'Pro','price'=>30000,'desc'=>'Standard + 성능 모니터링, 장애 대응, 일일 백업'],
    ['label'=>'Enterprise','price'=>50000,'desc'=>'Pro + 전담 매니저, 긴급 장애 대응, 커스텀 기능','badge'=>'포털 · 쇼핑몰'],
];

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

$pageTitle = __('site.pages.service_order') ?? 'VosCMS 서비스 신청';
include BASE_PATH . '/skins/layouts/' . ($siteSettings['site_layout'] ?? 'modern') . '/header.php';
?>

<!-- 헤더 -->
<div class="bg-gradient-to-r from-blue-600 to-blue-800 dark:from-blue-900 dark:to-zinc-900 text-white py-12">
    <div class="<?= $widthClass ?> mx-auto px-4 text-center relative">
        <h1 class="text-3xl font-bold mb-2"><?= $pageTitle ?></h1>
        <p class="text-blue-100 dark:text-blue-200">원스톱 서비스로 홈페이지를 시작하세요.</p>
        <?php if ($isAdmin): ?>
        <a href="<?= $baseUrl ?>/service/order/settings?tab=skin" class="absolute right-4 top-1/2 -translate-y-1/2 p-2 text-white/60 hover:text-white hover:bg-white/10 rounded-lg transition" title="환경 설정">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </a>
        <?php endif; ?>
    </div>
</div>

<div class="<?= $widthClass ?> mx-auto px-4 py-8 space-y-8">

    <!-- ① 도메인 -->
    <section class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-gray-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 dark:bg-zinc-800/50 border-b border-gray-200 dark:border-zinc-700">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-bold">1</span>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">도메인</h2>
                <span class="text-xs text-gray-400 dark:text-zinc-500 ml-1">선택사항</span>
            </div>
        </div>
        <div class="p-6">
            <div class="flex flex-wrap items-center gap-3 mb-4">
                <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="domain_option" value="free" class="text-blue-600" checked onchange="toggleDomainOption('free')"><span class="text-sm font-medium text-green-600">무료 도메인</span></label>
                <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="domain_option" value="new" class="text-blue-600" onchange="toggleDomainOption('new')"><span class="text-sm font-medium text-gray-700 dark:text-zinc-300">도메인 구입</span></label>
                <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="domain_option" value="existing" class="text-blue-600" onchange="toggleDomainOption('existing')"><span class="text-sm font-medium text-gray-700 dark:text-zinc-300">보유 도메인</span></label>
            </div>

            <!-- 무료 서브도메인 입력 -->
            <div id="domainFree">
                <div class="flex items-center gap-2">
                    <div class="flex-1 flex items-center border border-gray-300 dark:border-zinc-600 rounded-lg overflow-hidden">
                        <input type="text" id="freeSubdomain" placeholder="mysite" class="flex-1 px-4 py-3 text-sm bg-white dark:bg-zinc-700 dark:text-white border-0 focus:ring-0" title="영문 소문자, 숫자, 하이픈만 사용 가능">
                        <select id="freeDomainSelect" class="px-3 py-3 text-sm text-gray-500 dark:text-zinc-400 bg-gray-50 dark:bg-zinc-600 border-l border-gray-300 dark:border-zinc-600 focus:ring-0 border-0 font-medium">
                            <?php foreach ($_freeDomains as $fd): ?>
                            <option value="<?= htmlspecialchars($fd) ?>">.<?= htmlspecialchars($fd) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="button" onclick="checkSubdomain()" class="px-4 py-3 bg-green-600 text-white font-semibold rounded-lg hover:bg-green-700 transition text-sm whitespace-nowrap">확인</button>
                </div>
                <p class="text-xs text-gray-400 dark:text-zinc-500 mt-2">* 영문 소문자, 숫자, 하이픈(-) 사용 가능. 설정에서 다른 도메인으로 변경할 수 있습니다.</p>
                <div id="subdomainResult" class="hidden mt-2"></div>
            </div>

            <!-- 도메인 구입 검색 -->
            <div id="domainSearch" class="hidden">
                <div class="flex gap-2">
                    <input type="text" id="domainInput" placeholder="원하는 도메인명을 입력하세요 (예: mycompany)" class="flex-1 px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 text-sm" onkeydown="if(event.key==='Enter')searchDomain()">
                    <button onclick="searchDomain()" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition text-sm whitespace-nowrap">검색</button>
                </div>
                <p class="text-xs text-gray-400 dark:text-zinc-500 mt-2">* .com, .net 등 확장자 없이 도메인명만 입력하세요.</p>
                <div id="domainResults" class="hidden mt-4">
                    <div id="domainLoading" class="hidden text-center py-6">
                        <div class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-zinc-400">
                            <svg class="w-5 h-5 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                            도메인을 검색 중입니다...
                        </div>
                    </div>
                    <div id="domainList" class="space-y-2"></div>
                    <div id="domainConfirmWrap" class="hidden mt-4 flex items-center justify-between p-4 bg-blue-50 dark:bg-blue-900/30 rounded-xl border border-blue-200 dark:border-blue-800">
                        <p class="text-sm text-blue-800 dark:text-blue-200"><span id="domainSelectedCount" class="font-bold">0</span>개 도메인 선택됨 · 합계 <span id="domainSelectedTotal" class="font-bold">0</span> <span class="text-xs text-blue-600/60">(税別)</span></p>
                        <button onclick="confirmDomains()" class="px-6 py-2.5 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition text-sm">확인</button>
                    </div>
                    <div id="domainConfirmed" class="hidden mt-4 space-y-2"></div>
                </div>
            </div>

            <!-- 기존 도메인 입력 -->
            <div id="domainExisting" class="hidden">
                <input type="text" name="existing_domain" placeholder="보유한 도메인을 입력하세요 (예: mydomain.com)" class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 text-sm" onchange="if(this.value.trim()) updateMailDomain(this.value.trim())">
                <p class="text-xs text-gray-400 dark:text-zinc-500 mt-2">* 도메인의 네임서버를 저희 서버로 변경해야 합니다. 설정 안내는 가입 후 이메일로 발송됩니다.</p>
            </div>

        </div>
    </section>

    <!-- ② 웹 호스팅 -->
    <section class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-gray-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 dark:bg-zinc-800/50 border-b border-gray-200 dark:border-zinc-700">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-bold">2</span>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">웹 호스팅</h2>
                <span class="text-xs text-blue-600 font-medium ml-1">필수</span>
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
                ?>
                <label class="hosting-option cursor-pointer border-2 <?= $isDefault ? 'border-blue-500 bg-blue-50 dark:bg-blue-900/30 selected' : 'border-gray-200 dark:border-zinc-600' ?> rounded-xl p-4 text-center <?= !$isDefault ? ($isFree ? 'hover:border-green-400' : 'hover:border-blue-400') : '' ?> transition">
                    <input type="radio" name="hosting_plan" value="<?= htmlspecialchars($val) ?>" class="hidden" <?= $isDefault ? 'checked' : '' ?> data-price="<?= (int)$plan['price'] ?>">
                    <p class="text-xs <?= $isFree ? 'text-green-600' : ($isDefault ? 'text-blue-600' : 'text-gray-400 dark:text-zinc-500') ?> font-semibold mb-1"><?= htmlspecialchars($plan['label'] ?? '') ?></p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($plan['capacity'] ?? '') ?></p>
                    <?php if ($isFree): ?>
                    <p class="text-green-600 font-bold mt-1"><?= displayPrice(0) ?></p>
                    <?php if (!empty($plan['features'])): ?><p class="text-[10px] text-gray-400 dark:text-zinc-500 mt-0.5"><?= htmlspecialchars(str_replace(',', ' · ', $plan['features'])) ?></p><?php endif; ?>
                    <?php else: ?>
                    <p class="text-blue-600 font-bold mt-1"><?= displayPrice($plan['price']) ?><span class="text-xs font-normal text-gray-400">/월</span></p>
                    <?php endif; ?>
                </label>
                <?php endforeach; ?>
            </div>
            <?php if (!empty($morePlans)): ?>
            <details class="mb-6">
                <summary class="text-xs text-blue-600 cursor-pointer hover:underline">더 큰 플랜 보기</summary>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-3">
                    <?php foreach ($morePlans as $plan):
                        $val = strtolower(str_replace([' '], '', $plan['capacity'] ?? ''));
                    ?>
                    <label class="hosting-option cursor-pointer border-2 border-gray-200 dark:border-zinc-600 rounded-xl p-4 text-center hover:border-blue-400 transition">
                        <input type="radio" name="hosting_plan" value="<?= htmlspecialchars($val) ?>" class="hidden" data-price="<?= (int)$plan['price'] ?>">
                        <p class="text-xs text-gray-400 mb-1"><?= htmlspecialchars($plan['label'] ?? '') ?></p>
                        <p class="text-lg font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($plan['capacity'] ?? '') ?></p>
                        <p class="text-blue-600 font-bold mt-1"><?= displayPrice($plan['price']) ?><span class="text-xs font-normal text-gray-400">/월</span></p>
                    </label>
                    <?php endforeach; ?>
                </div>
            </details>
            <?php endif; ?>

            <!-- 무료 플랜 안내 (무료 선택 시만 표시) -->
            <div id="freePlanNotice" class="hidden p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl mb-4">
                <p class="text-sm text-green-800 dark:text-green-200 font-medium">무료 플랜은 <strong>1개월 단위</strong>로 연장 가능합니다.</p>
                <p class="text-xs text-green-600 dark:text-green-400 mt-1">서브도메인 제공 · 광고 포함 · 메일 미제공 · 마이페이지에서 연장 · 언제든 유료 전환 가능</p>
            </div>

            <!-- 계약 기간 (무료 선택 시 숨김) -->
            <div id="hostingPeriodWrap" class="flex flex-wrap items-center gap-3 p-4 bg-gray-50 dark:bg-zinc-700/50 rounded-xl mb-4">
                <span class="text-sm font-medium text-gray-600 dark:text-zinc-300">계약 기간:</span>
                <?php foreach ($_hostingPeriods as $j => $pd):
                    $months = (int)$pd['months'];
                    $disc = (int)$pd['discount'];
                    $periodLabel = $months >= 12 ? ($months / 12) . '년' : $months . '개월';
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
                <span class="text-sm font-medium text-gray-600 dark:text-zinc-300">추가 용량:</span>
                <select class="px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg text-sm">
                    <option value="0">추가 없음</option>
                    <?php foreach ($_hostingStorage as $st): ?>
                    <option value="<?= htmlspecialchars(strtolower($st['capacity'])) ?>" data-price="<?= (int)$st['price'] ?>" data-label="+<?= htmlspecialchars($st['capacity']) ?>">+<?= htmlspecialchars($st['capacity']) ?> (<?= displayPrice($st['price']) ?>/월)</option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <?php if (!empty($_hostingFeatures)): ?>
            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs text-gray-500 dark:text-zinc-400">
                <?php foreach ($_hostingFeatures as $feat): ?>
                <span class="flex items-center gap-1"><svg class="w-3.5 h-3.5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg><?= htmlspecialchars($feat) ?></span>
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

<!-- 관리자 설정 모달 -->

<!-- JS -->
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
</script>
<script src="<?= $baseUrl ?>/resources/views/system/service/order.js?v=<?= filemtime(BASE_PATH . '/resources/views/system/service/order.js') ?>"></script>

<?php
include BASE_PATH . '/skins/layouts/' . ($siteSettings['site_layout'] ?? 'modern') . '/footer.php';
?>
