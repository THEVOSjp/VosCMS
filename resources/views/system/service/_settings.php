<?php
/**
 * 서비스 신청 페이지 — 관리자 설정 (pages-settings.php 탭 콘텐츠)
 *
 * pages-settings.php에서 include되며, 다음 변수를 사용:
 *   $pdo, $prefix, $baseUrl, $adminUrl, $pageSlug, $serviceSettings, $config
 */
$_inp = 'w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500';
$_sel = 'px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500';
$_curSymbol = ['KRW'=>'₩','USD'=>'$','JPY'=>'¥','CNY'=>'¥','EUR'=>'€'];
$_dispCur = $serviceSettings['service_currency'] ?? 'KRW';
$_dispSym = $_curSymbol[$_dispCur] ?? $_dispCur;
?>

<!-- ① 기본 설정 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700 overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-700">
        <h3 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            기본 설정
        </h3>
    </div>
    <div class="divide-y divide-zinc-100 dark:divide-zinc-700">
        <!-- 표시 통화 -->
        <div class="flex items-center px-6 py-4">
            <label class="w-44 shrink-0 text-sm font-medium text-zinc-700 dark:text-zinc-300">표시 통화</label>
            <div class="flex-1">
                <select id="svcCurrency" class="<?= $_sel ?>">
                    <?php
                    $currencies = ['KRW' => '🇰🇷 KRW (원)', 'USD' => '🇺🇸 USD ($)', 'JPY' => '🇯🇵 JPY (¥)', 'CNY' => '🇨🇳 CNY (¥)', 'EUR' => '🇪🇺 EUR (€)'];
                    $curCurrency = $serviceSettings['service_currency'] ?? 'KRW';
                    foreach ($currencies as $ck => $cl): ?>
                    <option value="<?= $ck ?>" <?= $curCurrency === $ck ? 'selected' : '' ?>><?= $cl ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="text-xs text-zinc-400 mt-1">사이트 기본 통화 설정. 사용자가 프론트에서 전환 가능.</p>
            </div>
        </div>
    </div>
</div>

<!-- ② 환율 설정 -->
<?php
// DB 저장값 우선, 없으면 캐시 폴백
$_ratesCache = BASE_PATH . '/storage/cache/exchange_rates.json';
$_cachedRates = file_exists($_ratesCache) ? json_decode(file_get_contents($_ratesCache), true) : null;
$_rateDate = $_cachedRates['date'] ?? '';

$_rateKRW = $serviceSettings['service_exchange_rate'] ?? $_cachedRates['rates']['KRW'] ?? '1380';
$_rateJPY = $serviceSettings['service_rate_jpy'] ?? $_cachedRates['rates']['JPY'] ?? '';
$_rateCNY = $serviceSettings['service_rate_cny'] ?? $_cachedRates['rates']['CNY'] ?? '';
$_rateEUR = $serviceSettings['service_rate_eur'] ?? $_cachedRates['rates']['EUR'] ?? '';
?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700 overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-700 flex items-center justify-between">
        <h3 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            환율 설정
        </h3>
        <button type="button" onclick="fetchExchangeRates()" id="btnFetchRates" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 hover:bg-blue-100 dark:hover:bg-blue-900/50 rounded-lg transition">
            <svg class="w-3.5 h-3.5" id="fetchRatesIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            실시간 환율 가져오기
        </button>
    </div>
    <div class="divide-y divide-zinc-100 dark:divide-zinc-700">
        <!-- 현재 환율 (4통화 한눈에) -->
        <div class="px-6 py-4">
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3" id="ratesGrid">
                <div class="p-3 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg text-center">
                    <p class="text-[10px] text-zinc-400 mb-1">USD → KRW</p>
                    <input type="number" id="svcRateKRW" value="<?= htmlspecialchars($_rateKRW) ?>" class="w-full text-center text-sm font-bold text-zinc-900 dark:text-white bg-transparent border-0 focus:ring-0 p-0" step="0.01">
                </div>
                <div class="p-3 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg text-center">
                    <p class="text-[10px] text-zinc-400 mb-1">USD → JPY</p>
                    <input type="number" id="svcRateJPY" value="<?= htmlspecialchars($_rateJPY) ?>" class="w-full text-center text-sm font-bold text-zinc-900 dark:text-white bg-transparent border-0 focus:ring-0 p-0" step="0.01">
                </div>
                <div class="p-3 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg text-center">
                    <p class="text-[10px] text-zinc-400 mb-1">USD → CNY</p>
                    <input type="number" id="svcRateCNY" value="<?= htmlspecialchars($_rateCNY) ?>" class="w-full text-center text-sm font-bold text-zinc-900 dark:text-white bg-transparent border-0 focus:ring-0 p-0" step="0.0001">
                </div>
                <div class="p-3 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg text-center">
                    <p class="text-[10px] text-zinc-400 mb-1">USD → EUR</p>
                    <input type="number" id="svcRateEUR" value="<?= htmlspecialchars($_rateEUR) ?>" class="w-full text-center text-sm font-bold text-zinc-900 dark:text-white bg-transparent border-0 focus:ring-0 p-0" step="0.0001">
                </div>
            </div>
            <p class="text-xs text-zinc-400 mt-2" id="rateStatus">
                <?php if ($_rateDate): ?>
                    기준일: <?= htmlspecialchars($_rateDate) ?> · <a href="https://frankfurter.dev" target="_blank" class="text-blue-500 hover:underline">Frankfurter API</a> (무료, 키 불필요)
                <?php else: ?>
                    환율 데이터 없음 — "실시간 환율 가져오기" 버튼을 클릭하세요.
                <?php endif; ?>
            </p>
        </div>
        <!-- 자동 업데이트 -->
        <div class="flex items-center px-6 py-4">
            <label class="w-44 shrink-0 text-sm font-medium text-zinc-700 dark:text-zinc-300">자동 업데이트</label>
            <div class="flex-1">
                <select id="svcExchangeAuto" class="<?= $_sel ?>">
                    <?php
                    $autoOptions = ['manual' => '수동 설정', 'daily' => '매일 자동', 'weekly' => '매주 자동'];
                    $curAuto = $serviceSettings['service_exchange_auto'] ?? 'manual';
                    foreach ($autoOptions as $ak => $al): ?>
                    <option value="<?= $ak ?>" <?= $curAuto === $ak ? 'selected' : '' ?>><?= $al ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="text-xs text-zinc-400 mt-1">자동 설정 시 페이지 로드 때 캐시 만료 확인 후 자동 갱신됩니다.</p>
            </div>
        </div>
    </div>
</div>

<!-- ③ 반올림 단위 (통화별) -->
<?php
$_roundDefaults = ['KRW'=>'1000','USD'=>'1','JPY'=>'100','CNY'=>'10','EUR'=>'1'];
$_savedRounds = json_decode($serviceSettings['service_rounding'] ?? '', true) ?: $_roundDefaults;
$_roundCurrencies = [
    'KRW' => ['symbol'=>'₩', 'options'=>['100'=>'100원','500'=>'500원','1000'=>'1,000원','5000'=>'5,000원','10000'=>'10,000원']],
    'JPY' => ['symbol'=>'¥', 'options'=>['1'=>'1円','10'=>'10円','50'=>'50円','100'=>'100円','500'=>'500円','1000'=>'1,000円']],
    'USD' => ['symbol'=>'$', 'options'=>['0.01'=>'$0.01','0.1'=>'$0.10','0.5'=>'$0.50','1'=>'$1.00','5'=>'$5.00']],
    'CNY' => ['symbol'=>'¥', 'options'=>['1'=>'1元','5'=>'5元','10'=>'10元','50'=>'50元','100'=>'100元']],
    'EUR' => ['symbol'=>'€', 'options'=>['0.01'=>'€0.01','0.1'=>'€0.10','0.5'=>'€0.50','1'=>'€1.00','5'=>'€5.00']],
];
?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700 overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-700">
        <h3 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
            <svg class="w-4 h-4 text-teal-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/></svg>
            반올림 단위
        </h3>
    </div>
    <div class="px-6 py-4">
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-5 gap-3">
            <?php foreach ($_roundCurrencies as $curCode => $curInfo):
                $curVal = $_savedRounds[$curCode] ?? $_roundDefaults[$curCode];
            ?>
            <div class="p-3 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg">
                <p class="text-[10px] text-zinc-400 mb-1.5 font-medium"><?= $curCode ?> (<?= $curInfo['symbol'] ?>)</p>
                <select id="svcRound_<?= $curCode ?>" class="svc-round w-full px-2 py-1.5 text-xs border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white" data-currency="<?= $curCode ?>">
                    <?php foreach ($curInfo['options'] as $optVal => $optLabel): ?>
                    <option value="<?= $optVal ?>" <?= $curVal == $optVal ? 'selected' : '' ?>><?= $optLabel ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endforeach; ?>
        </div>
        <p class="text-xs text-zinc-400 mt-2">배율 계산 시 각 통화 단위에 맞게 반올림됩니다.</p>
    </div>
</div>

<!-- ④ 도메인 검색 TLD -->
<?php
$_defSearchTLDs = ['.com','.net','.org','.jp','.co.jp','.kr','.co.kr'];
$_searchTLDs = json_decode($serviceSettings['service_search_tlds'] ?? '', true) ?: $_defSearchTLDs;
?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700 overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-700">
        <h3 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
            <svg class="w-4 h-4 text-cyan-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            도메인 검색 대상
        </h3>
    </div>
    <div class="px-6 py-4">
        <div class="flex flex-wrap gap-2" id="searchTLDList">
            <?php foreach ($_searchTLDs as $st): ?>
            <span class="search-tld-tag inline-flex items-center gap-1 px-2.5 py-1 bg-cyan-50 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300 text-xs font-medium rounded-lg">
                <?= htmlspecialchars($st) ?>
                <button type="button" onclick="this.closest('.search-tld-tag').remove()" class="text-cyan-400 hover:text-red-500 ml-0.5">&times;</button>
            </span>
            <?php endforeach; ?>
        </div>
        <div class="flex items-center gap-2 mt-3">
            <input type="text" id="newSearchTLD" placeholder=".example" class="<?= $_inp ?> text-xs max-w-[120px] font-mono" onkeydown="if(event.key==='Enter'){event.preventDefault();addSearchTLD();}">
            <button type="button" onclick="addSearchTLD()" class="text-xs text-cyan-600 dark:text-cyan-400 hover:underline">+ 추가</button>
        </div>
        <p class="text-xs text-zinc-400 mt-2">도메인 검색 시 여기에 등록된 TLD만 WHOIS 조회합니다. 너무 많으면 검색이 느려집니다 (권장: 15개 이하).</p>
    </div>
</div>

<!-- ⑤ 도메인 가격 -->
<?php
$_defDomains = [
    ['tld'=>'.com','vip_price'=>15000,'price'=>20000,'discount'=>0],
    ['tld'=>'.net','vip_price'=>17000,'price'=>22000,'discount'=>0],
    ['tld'=>'.org','vip_price'=>14000,'price'=>18000,'discount'=>0],
    ['tld'=>'.io','vip_price'=>45000,'price'=>55000,'discount'=>0],
    ['tld'=>'.co','vip_price'=>16000,'price'=>20000,'discount'=>0],
    ['tld'=>'.dev','vip_price'=>18000,'price'=>22000,'discount'=>0],
    ['tld'=>'.shop','vip_price'=>8000,'price'=>10000,'discount'=>0],
    ['tld'=>'.store','vip_price'=>9000,'price'=>12000,'discount'=>0],
    ['tld'=>'.site','vip_price'=>8000,'price'=>10000,'discount'=>0],
    ['tld'=>'.online','vip_price'=>8000,'price'=>10000,'discount'=>0],
    ['tld'=>'.biz','vip_price'=>12000,'price'=>15000,'discount'=>0],
    ['tld'=>'.info','vip_price'=>12000,'price'=>15000,'discount'=>0],
    ['tld'=>'.xyz','vip_price'=>6000,'price'=>8000,'discount'=>0],
    ['tld'=>'.app','vip_price'=>20000,'price'=>25000,'discount'=>0],
];
$_domains = json_decode($serviceSettings['service_domain_pricing'] ?? '', true) ?: $_defDomains;
$_savedKRW = floatval($serviceSettings['service_exchange_rate'] ?? $_rateKRW ?? 1380);
// 표시 통화 환율 (USD → 표시통화)
$_dispRateMap = [
    'KRW' => $_savedKRW,
    'JPY' => floatval($serviceSettings['service_rate_jpy'] ?? $_rateJPY ?? 150),
    'CNY' => floatval($serviceSettings['service_rate_cny'] ?? $_rateCNY ?? 7),
    'EUR' => floatval($serviceSettings['service_rate_eur'] ?? $_rateEUR ?? 0.85),
    'USD' => 1,
];
$_dispRate = $_dispRateMap[$_dispCur] ?? $_savedKRW;
// NameSilo 캐시 로드
$_nsCache = BASE_PATH . '/storage/cache/namesilo_prices.json';
$_nsData = file_exists($_nsCache) ? json_decode(file_get_contents($_nsCache), true) : null;
$_nsMap = [];
if ($_nsData && !empty($_nsData['prices'])) {
    foreach ($_nsData['prices'] as $_np) { $_nsMap[$_np['tld']] = $_np; }
}
// xdomain 크롤링 캐시 로드
$_xdomainCache = BASE_PATH . '/storage/cache/xdomain_prices.json';
$_xdomainData = file_exists($_xdomainCache) ? json_decode(file_get_contents($_xdomainCache), true) : null;
$_xdomainMap = [];
if ($_xdomainData && !empty($_xdomainData['prices'])) {
    foreach ($_xdomainData['prices'] as $_xp) {
        $_xdomainMap[$_xp['tld']] = $_xp;
    }
}
?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700 overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-700 flex items-center justify-between">
        <h3 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
            <svg class="w-4 h-4 text-purple-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
            도메인 가격 설정
        </h3>
        <div class="flex items-center gap-3">
            <span class="text-[10px] text-zinc-400">적용 환율: 1 USD = <span id="domainExRate"><?= number_format($_dispRate, 2) ?></span> <?= $_dispCur ?></span>
            <button type="button" onclick="fetchNamesiloPrices()" id="btnFetchNS" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-orange-600 dark:text-orange-400 bg-orange-50 dark:bg-orange-900/30 hover:bg-orange-100 dark:hover:bg-orange-900/50 rounded-lg transition">
                <svg class="w-3.5 h-3.5" id="fetchNSIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                NameSilo 가져오기
            </button>
            <button type="button" onclick="fetchXdomainPrices()" id="btnFetchDomainPrices" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-purple-600 dark:text-purple-400 bg-purple-50 dark:bg-purple-900/30 hover:bg-purple-100 dark:hover:bg-purple-900/50 rounded-lg transition">
                <svg class="w-3.5 h-3.5" id="fetchDomainIcon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                xdomain 원가 가져오기
            </button>
            <button type="button" onclick="importAllXdomainTLDs()" id="btnImportAllTLD" class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-900/30 hover:bg-indigo-100 dark:hover:bg-indigo-900/50 rounded-lg transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                전체 TLD 가져오기
            </button>
        </div>
    </div>
    <div class="p-6 space-y-4">
        <div class="overflow-x-auto">
            <table class="w-full text-sm" id="tblDomains">
                <thead>
                    <tr class="text-[11px] text-zinc-400 dark:text-zinc-500 border-b border-zinc-200 dark:border-zinc-600">
                        <th class="pb-2 px-1 w-8" rowspan="2"><input type="checkbox" checked onchange="document.querySelectorAll('.dom-active').forEach(c=>c.checked=this.checked)" class="rounded text-blue-600 cursor-pointer" title="전체 선택/해제"></th>
                        <th class="pb-2 pr-1 text-left w-16" rowspan="2">TLD</th>
                        <th class="pb-2 px-1 text-center" colspan="3">NameSilo ($)</th>
                        <th class="pb-2 px-1 text-center" colspan="3">xdomain.ne.jp (¥)</th>
                        <th class="pb-2 px-1 text-center w-24">회원<br>판매가 (<?= $_dispCur ?>)</th>
                        <th class="pb-2 px-1 text-center w-24">일반<br>판매가 (<?= $_dispCur ?>)</th>
                        <th class="pb-2 px-1 text-center w-24" rowspan="2">할인율</th>
                        <th class="pb-2 w-8" rowspan="2"></th>
                    </tr>
                    <tr class="text-[10px] text-zinc-300 dark:text-zinc-600 border-b border-zinc-100 dark:border-zinc-700">
                        <th class="pb-1 px-1 text-center font-normal">Reg</th>
                        <th class="pb-1 px-1 text-center font-normal">Renew</th>
                        <th class="pb-1 px-1 text-center font-normal"><?= $_dispCur ?></th>
                        <th class="pb-1 px-1 text-center font-normal">取得</th>
                        <th class="pb-1 px-1 text-center font-normal">更新</th>
                        <th class="pb-1 px-1 text-center font-normal">移管</th>
                        <th class="pb-1 px-1 text-center"><input type="number" id="vipMultiplier" placeholder="×배율" step="0.1" min="0" class="w-full px-1 py-0.5 text-[10px] text-center border border-blue-300 dark:border-blue-700 rounded bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-bold" oninput="applyMultiplier('vip', this.value)" title="更新가격 × 배율 = 회원 판매가"></th>
                        <th class="pb-1 px-1 text-center"><input type="number" id="normalMultiplier" placeholder="×배율" step="0.1" min="0" class="w-full px-1 py-0.5 text-[10px] text-center border border-blue-300 dark:border-blue-700 rounded bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 font-bold" oninput="applyMultiplier('normal', this.value)" title="更新가격 × 배율 = 일반 판매가"></th>
                    </tr>
                </thead>
                <tbody id="domainRows">
                    <?php foreach ($_domains as $d):
                        $_xp = $_xdomainMap[$d['tld']] ?? null;
                        $_ns = $_nsMap[$d['tld']] ?? null;
                        $_nsRenewDisp = $_ns ? round($_ns['renew'] * $_dispRate) : null;
                    ?>
                    <tr class="border-b border-zinc-50 dark:border-zinc-700/50 domain-row">
                        <td class="py-1.5 px-1 text-center"><input type="checkbox" class="dom-active rounded text-blue-600 cursor-pointer" <?= ($d['active'] ?? true) ? 'checked' : '' ?>></td>
                        <td class="py-1.5 pr-1"><input type="text" value="<?= htmlspecialchars($d['tld']) ?>" class="dom-tld <?= $_inp ?> text-xs font-mono text-center" style="max-width:70px"></td>
                        <td class="py-1.5 px-1 text-center"><span class="text-xs text-orange-400 dom-nsreg"><?= $_ns ? '$' . number_format($_ns['registration'], 2) : '-' ?></span></td>
                        <td class="py-1.5 px-1 text-center"><span class="text-xs text-orange-500 font-medium dom-nsrenew"><?= $_ns ? '$' . number_format($_ns['renew'], 2) : '-' ?></span></td>
                        <td class="py-1.5 px-1 text-center"><span class="dom-nsdisp text-xs text-zinc-400"><?= $_nsRenewDisp !== null ? number_format($_nsRenewDisp) : '-' ?></span></td>
                        <td class="py-1.5 px-1 text-center"><span class="text-xs text-purple-400 dom-xget"><?= $_xp && $_xp['registration'] !== null ? '¥' . number_format($_xp['registration']) : '-' ?></span></td>
                        <td class="py-1.5 px-1 text-center"><span class="text-xs text-purple-500 font-medium dom-xrenew"><?= $_xp && $_xp['renewal'] !== null ? '¥' . number_format($_xp['renewal']) : '-' ?></span></td>
                        <td class="py-1.5 px-1 text-center"><span class="text-xs text-purple-400 dom-xmove"><?= $_xp && $_xp['transfer'] !== null ? '¥' . number_format($_xp['transfer']) : '-' ?></span></td>
                        <td class="py-1.5 px-1"><input type="number" value="<?= (int)($d['vip_price'] ?? 0) ?>" class="dom-vip <?= $_inp ?> text-xs text-center" min="0" style="max-width:100px"></td>
                        <td class="py-1.5 px-1"><input type="number" value="<?= (int)($d['price'] ?? 0) ?>" class="dom-price <?= $_inp ?> text-xs text-center" min="0" style="max-width:100px"></td>
                        <td class="py-1.5 px-1"><div class="flex items-center gap-0.5"><input type="number" value="<?= (int)($d['discount'] ?? 0) ?>" class="dom-disc <?= $_inp ?> text-xs text-center" min="0" max="100" <span class="text-[10px] text-zinc-400">%</span></div></td>
                        <td class="py-1.5 text-center"><button type="button" onclick="this.closest('tr').remove()" class="text-red-400 hover:text-red-600 p-0.5"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <button type="button" onclick="addDomainRow()" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">+ 도메인 TLD 추가</button>

    </div>
</div>

<!-- ④ NameSilo API -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700 overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-700">
        <h3 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
            <svg class="w-4 h-4 text-amber-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/></svg>
            NameSilo API
        </h3>
    </div>
    <div class="divide-y divide-zinc-100 dark:divide-zinc-700">
        <div class="flex items-center px-6 py-4">
            <label class="w-44 shrink-0 text-sm font-medium text-zinc-700 dark:text-zinc-300">API Key</label>
            <div class="flex-1">
                <input type="password" id="svcNamesiloKey" value="<?= htmlspecialchars($serviceSettings['service_namesilo_key'] ?? ($_ENV['NAMESILO_API_KEY'] ?? '')) ?>" class="<?= $_inp ?> font-mono" autocomplete="off">
                <p class="text-xs text-zinc-400 mt-1">NameSilo 계정의 API Key. <a href="https://www.namesilo.com/account/api-manager" target="_blank" class="text-blue-500 hover:underline">API Manager →</a></p>
            </div>
        </div>
        <div class="flex items-center px-6 py-4">
            <label class="w-44 shrink-0 text-sm font-medium text-zinc-700 dark:text-zinc-300">샌드박스 모드</label>
            <div class="flex-1">
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="svcNamesiloSandbox" class="sr-only peer" <?= ($serviceSettings['service_namesilo_sandbox'] ?? '0') === '1' ? 'checked' : '' ?>>
                    <div class="w-11 h-6 bg-zinc-200 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                </label>
                <p class="text-xs text-zinc-400 mt-1">테스트 환경에서 실제 등록 없이 API를 테스트합니다.</p>
            </div>
        </div>
    </div>
</div>

<!-- ⑤ 웹 호스팅 -->
<?php
$_defPlans = [
    ['label'=>'무료','capacity'=>'50MB','price'=>0,'features'=>'광고 포함,1개월','locked'=>true],
    ['label'=>'입문','capacity'=>'500MB','price'=>3000,'features'=>''],
    ['label'=>'추천','capacity'=>'1GB','price'=>5000,'features'=>''],
    ['label'=>'비즈니스','capacity'=>'3GB','price'=>10000,'features'=>''],
    ['label'=>'프로','capacity'=>'5GB','price'=>18000,'features'=>''],
    ['label'=>'엔터프라이즈','capacity'=>'10GB','price'=>30000,'features'=>''],
    ['label'=>'대용량','capacity'=>'15GB','price'=>45000,'features'=>''],
    ['label'=>'프리미엄','capacity'=>'20GB','price'=>55000,'features'=>''],
    ['label'=>'맥스','capacity'=>'30GB','price'=>80000,'features'=>''],
];
$_defPeriods = [
    ['months'=>1,'discount'=>0],['months'=>6,'discount'=>5],['months'=>12,'discount'=>10],
    ['months'=>24,'discount'=>15],['months'=>36,'discount'=>20],['months'=>60,'discount'=>30],
];
$_defStorage = [
    ['capacity'=>'1GB','price'=>2000],['capacity'=>'3GB','price'=>5000],['capacity'=>'5GB','price'=>8000],
    ['capacity'=>'10GB','price'=>14000],['capacity'=>'20GB','price'=>25000],['capacity'=>'50GB','price'=>50000],
];
$_defFeatures = ['SSL 인증서 무료','일일 백업','PHP 8.3','기본 메일 5개'];

$_plans = json_decode($serviceSettings['service_hosting_plans'] ?? '', true) ?: $_defPlans;
$_periods = json_decode($serviceSettings['service_hosting_periods'] ?? '', true) ?: $_defPeriods;
$_storage = json_decode($serviceSettings['service_hosting_storage'] ?? '', true) ?: $_defStorage;
$_features = json_decode($serviceSettings['service_hosting_features'] ?? '', true) ?: $_defFeatures;
?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700 overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-700">
        <h3 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
            <svg class="w-4 h-4 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/></svg>
            웹 호스팅
        </h3>
    </div>
    <div class="p-6 space-y-8">

        <!-- 호스팅 플랜 -->
        <div>
            <h4 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3">용량 / 플랜</h4>
            <div class="overflow-x-auto">
                <table class="w-full text-sm" id="tblPlans">
                    <thead>
                        <tr class="text-left text-xs text-zinc-400 dark:text-zinc-500 border-b border-zinc-100 dark:border-zinc-700">
                            <th class="pb-2 pr-2 w-24">플랜명</th>
                            <th class="pb-2 pr-2 w-20">용량</th>
                            <th class="pb-2 pr-2 w-28">월 가격 (<?= $_dispCur ?>)</th>
                            <th class="pb-2 pr-2">서비스 내용 (콤마 구분)</th>
                            <th class="pb-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody id="planRows">
                        <?php foreach ($_plans as $i => $p): $locked = !empty($p['locked']); ?>
                        <tr class="border-b border-zinc-50 dark:border-zinc-700/50 plan-row" data-locked="<?= $locked ? '1' : '0' ?>">
                            <td class="py-2 pr-2"><input type="text" value="<?= htmlspecialchars($p['label']) ?>" class="plan-label <?= $_inp ?> text-xs" <?= $locked ? '' : '' ?>></td>
                            <td class="py-2 pr-2"><input type="text" value="<?= htmlspecialchars($p['capacity']) ?>" class="plan-cap <?= $_inp ?> text-xs"></td>
                            <td class="py-2 pr-2"><input type="number" value="<?= (int)$p['price'] ?>" class="plan-price <?= $_inp ?> text-xs" min="0"></td>
                            <td class="py-2 pr-2"><input type="text" value="<?= htmlspecialchars($p['features'] ?? '') ?>" class="plan-feat <?= $_inp ?> text-xs" placeholder="광고 포함, 1개월"></td>
                            <td class="py-2 text-center"><?php if (!$locked): ?><button type="button" onclick="this.closest('tr').remove()" class="text-red-400 hover:text-red-600 p-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button><?php else: ?><span class="text-zinc-300 dark:text-zinc-600 text-[10px]">기본</span><?php endif; ?></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="button" onclick="addPlanRow()" class="mt-2 text-xs text-blue-600 dark:text-blue-400 hover:underline">+ 호스팅 항목 추가</button>
        </div>

        <!-- 계약 기간 -->
        <div>
            <h4 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3">계약 기간</h4>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-6 gap-2" id="periodRows">
                <?php foreach ($_periods as $pd): ?>
                <div class="period-item flex items-center gap-1.5 p-2.5 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg relative group">
                    <input type="number" value="<?= (int)$pd['months'] ?>" class="period-months w-12 text-center text-xs font-bold bg-transparent border-0 focus:ring-0 p-0 text-zinc-900 dark:text-white" min="1">
                    <span class="text-[10px] text-zinc-400">개월</span>
                    <input type="number" value="<?= (int)$pd['discount'] ?>" class="period-disc w-10 text-center text-xs bg-transparent border-0 focus:ring-0 p-0 text-blue-600 dark:text-blue-400" min="0" max="100">
                    <span class="text-[10px] text-zinc-400">%</span>
                    <button type="button" onclick="this.closest('.period-item').remove()" class="absolute -top-1.5 -right-1.5 hidden group-hover:flex w-4 h-4 bg-red-500 text-white rounded-full items-center justify-center text-[10px] leading-none">&times;</button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" onclick="addPeriodRow()" class="mt-2 text-xs text-blue-600 dark:text-blue-400 hover:underline">+ 계약기간 추가</button>
        </div>

        <!-- 추가 용량 -->
        <div>
            <h4 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3">추가 용량</h4>
            <div class="overflow-x-auto">
                <table class="w-full text-sm" id="tblStorage">
                    <thead>
                        <tr class="text-left text-xs text-zinc-400 dark:text-zinc-500 border-b border-zinc-100 dark:border-zinc-700">
                            <th class="pb-2 pr-2 w-24">용량</th>
                            <th class="pb-2 pr-2">월 가격 (<?= $_dispCur ?>)</th>
                            <th class="pb-2 w-10"></th>
                        </tr>
                    </thead>
                    <tbody id="storageRows">
                        <?php foreach ($_storage as $st): ?>
                        <tr class="border-b border-zinc-50 dark:border-zinc-700/50 storage-row">
                            <td class="py-2 pr-2"><input type="text" value="<?= htmlspecialchars($st['capacity']) ?>" class="stor-cap <?= $_inp ?> text-xs"></td>
                            <td class="py-2 pr-2"><input type="number" value="<?= (int)$st['price'] ?>" class="stor-price <?= $_inp ?> text-xs" min="0"></td>
                            <td class="py-2 text-center"><button type="button" onclick="this.closest('tr').remove()" class="text-red-400 hover:text-red-600 p-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="button" onclick="addStorageRow()" class="mt-2 text-xs text-blue-600 dark:text-blue-400 hover:underline">+ 추가용량 추가</button>
        </div>

        <!-- 공통 서비스 -->
        <div>
            <h4 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3">공통 서비스</h4>
            <div class="space-y-1.5" id="featureRows">
                <?php foreach ($_features as $ft): ?>
                <div class="feat-item flex items-center gap-2">
                    <svg class="w-3.5 h-3.5 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>
                    <input type="text" value="<?= htmlspecialchars($ft) ?>" class="feat-text <?= $_inp ?> text-xs flex-1">
                    <button type="button" onclick="this.closest('.feat-item').remove()" class="text-red-400 hover:text-red-600 p-1 shrink-0"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
                </div>
                <?php endforeach; ?>
            </div>
            <button type="button" onclick="addFeatureRow()" class="mt-2 text-xs text-blue-600 dark:text-blue-400 hover:underline">+ 공통 서비스 추가</button>
        </div>

    </div>
</div>

<!-- ⑥ 부가 서비스 -->
<?php
$_defAddons = [
    ['key'=>'install','label'=>'설치 지원','desc'=>'VosCMS 설치 및 초기 설정을 대행합니다. 도메인 연결, SSL 설정, 기본 환경 구성 포함.','price'=>0,'unit'=>'','checked'=>true,'type'=>'checkbox'],
    ['key'=>'support','label'=>'기술 지원 (1년)','desc'=>'이메일/채팅 기술 지원, 버그 수정, 보안 업데이트 적용, 장애 대응 (영업일 기준 24시간 이내 응답).','price'=>120000,'unit'=>'/년','type'=>'checkbox'],
    ['key'=>'custom','label'=>'커스터마이징 개발','desc'=>'맞춤 디자인, 전용 플러그인 개발, 외부 시스템 연동, 데이터 마이그레이션 등.','price'=>0,'unit'=>'별도 견적','type'=>'checkbox'],
    ['key'=>'bizmail','label'=>'비즈니스 메일','desc'=>'대용량 첨부파일 전송 (최대 10GB), 계정당 10GB 저장공간, 광고 없는 웹메일, 스팸 필터.','price'=>5000,'unit'=>'/계정/월','type'=>'checkbox'],
];
$_defMaintenance = [
    ['label'=>'Basic','price'=>10000,'desc'=>'보안 업데이트 적용, 월 1회 백업 확인'],
    ['label'=>'Standard','price'=>20000,'desc'=>'보안 업데이트, 플러그인/코어 업데이트, 주 1회 백업, 이메일 기술지원'],
    ['label'=>'Pro','price'=>30000,'desc'=>'Standard + 성능 모니터링, 장애 대응 (24h 이내), 일일 백업, 월 1회 리포트'],
    ['label'=>'Enterprise','price'=>50000,'desc'=>'Pro + 전담 매니저, 긴급 장애 대응 (4h 이내), 커스텀 기능 월 2건, 트래픽 분석','badge'=>'포털 · 쇼핑몰'],
];
$_addons = json_decode($serviceSettings['service_addons'] ?? '', true) ?: $_defAddons;
$_maintenance = json_decode($serviceSettings['service_maintenance'] ?? '', true) ?: $_defMaintenance;
?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700 overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-700">
        <h3 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
            <svg class="w-4 h-4 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
            부가 서비스
        </h3>
    </div>
    <div class="p-6 space-y-6">
        <!-- 단일 서비스 -->
        <div>
            <h4 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3">서비스 항목</h4>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-[11px] text-zinc-400 dark:text-zinc-500 border-b border-zinc-200 dark:border-zinc-600">
                            <th class="pb-2 pr-2 text-left">서비스명</th>
                            <th class="pb-2 px-2 text-left">설명</th>
                            <th class="pb-2 px-2 text-center w-28">가격 (<?= $_dispCur ?>)</th>
                            <th class="pb-2 px-2 text-center w-24">단위</th>
                            <th class="pb-2 px-2 text-center w-14">기본체크</th>
                            <th class="pb-2 w-8"></th>
                        </tr>
                    </thead>
                    <tbody id="addonRows">
                        <?php foreach ($_addons as $addon): ?>
                        <tr class="border-b border-zinc-50 dark:border-zinc-700/50 addon-row">
                            <td class="py-1.5 pr-2"><input type="text" value="<?= htmlspecialchars($addon['label'] ?? '') ?>" class="addon-label <?= $_inp ?> text-xs"></td>
                            <td class="py-1.5 px-2"><input type="text" value="<?= htmlspecialchars($addon['desc'] ?? '') ?>" class="addon-desc <?= $_inp ?> text-xs"></td>
                            <td class="py-1.5 px-2"><input type="number" value="<?= (int)($addon['price'] ?? 0) ?>" class="addon-price <?= $_inp ?> text-xs text-center" min="0"></td>
                            <td class="py-1.5 px-2"><input type="text" value="<?= htmlspecialchars($addon['unit'] ?? '') ?>" class="addon-unit <?= $_inp ?> text-xs text-center" placeholder="/년, /월, 별도 견적"></td>
                            <td class="py-1.5 px-2 text-center"><input type="checkbox" class="addon-checked rounded text-blue-600" <?= !empty($addon['checked']) ? 'checked' : '' ?>></td>
                            <td class="py-1.5 text-center"><button type="button" onclick="this.closest('tr').remove()" class="text-red-400 hover:text-red-600 p-0.5"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="button" onclick="addAddonRow()" class="mt-2 text-xs text-blue-600 dark:text-blue-400 hover:underline">+ 서비스 항목 추가</button>
        </div>

        <!-- 유지보수 등급 -->
        <div>
            <h4 class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3">정기 유지보수 등급</h4>
            <div class="overflow-x-auto">
                <table class="w-full text-sm">
                    <thead>
                        <tr class="text-[11px] text-zinc-400 dark:text-zinc-500 border-b border-zinc-200 dark:border-zinc-600">
                            <th class="pb-2 pr-2 text-left w-28">등급명</th>
                            <th class="pb-2 px-2 text-center w-28">월 가격 (<?= $_dispCur ?>)</th>
                            <th class="pb-2 px-2 text-left">설명</th>
                            <th class="pb-2 px-2 text-left w-24">뱃지</th>
                            <th class="pb-2 w-8"></th>
                        </tr>
                    </thead>
                    <tbody id="maintRows">
                        <?php foreach ($_maintenance as $mt): ?>
                        <tr class="border-b border-zinc-50 dark:border-zinc-700/50 maint-row">
                            <td class="py-1.5 pr-2"><input type="text" value="<?= htmlspecialchars($mt['label'] ?? '') ?>" class="maint-label <?= $_inp ?> text-xs"></td>
                            <td class="py-1.5 px-2"><input type="number" value="<?= (int)($mt['price'] ?? 0) ?>" class="maint-price <?= $_inp ?> text-xs text-center" min="0"></td>
                            <td class="py-1.5 px-2"><input type="text" value="<?= htmlspecialchars($mt['desc'] ?? '') ?>" class="maint-desc <?= $_inp ?> text-xs"></td>
                            <td class="py-1.5 px-2"><input type="text" value="<?= htmlspecialchars($mt['badge'] ?? '') ?>" class="maint-badge <?= $_inp ?> text-xs" placeholder="선택"></td>
                            <td class="py-1.5 text-center"><button type="button" onclick="this.closest('tr').remove()" class="text-red-400 hover:text-red-600 p-0.5"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <button type="button" onclick="addMaintRow()" class="mt-2 text-xs text-blue-600 dark:text-blue-400 hover:underline">+ 유지보수 등급 추가</button>
        </div>
    </div>
</div>

<!-- 저장 버튼 -->
<div class="flex justify-end">
    <button onclick="saveServiceSettings()" class="inline-flex items-center gap-2 px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        서비스 설정 저장
    </button>
</div>

<script>
var _inp = '<?= $_inp ?> text-xs';

function addPlanRow() {
    var tr = document.createElement('tr');
    tr.className = 'border-b border-zinc-50 dark:border-zinc-700/50 plan-row';
    tr.dataset.locked = '0';
    tr.innerHTML = '<td class="py-2 pr-2"><input type="text" class="plan-label ' + _inp + '" placeholder="플랜명"></td>'
        + '<td class="py-2 pr-2"><input type="text" class="plan-cap ' + _inp + '" placeholder="5GB"></td>'
        + '<td class="py-2 pr-2"><input type="number" class="plan-price ' + _inp + '" min="0" value="0"></td>'
        + '<td class="py-2 pr-2"><input type="text" class="plan-feat ' + _inp + '" placeholder=""></td>'
        + '<td class="py-2 text-center"><button type="button" onclick="this.closest(\'tr\').remove()" class="text-red-400 hover:text-red-600 p-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></td>';
    document.getElementById('planRows').appendChild(tr);
}

function addPeriodRow() {
    var div = document.createElement('div');
    div.className = 'period-item flex items-center gap-1.5 p-2.5 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg relative group';
    div.innerHTML = '<input type="number" class="period-months w-12 text-center text-xs font-bold bg-transparent border-0 focus:ring-0 p-0 text-zinc-900 dark:text-white" min="1" value="1">'
        + '<span class="text-[10px] text-zinc-400">개월</span>'
        + '<input type="number" class="period-disc w-10 text-center text-xs bg-transparent border-0 focus:ring-0 p-0 text-blue-600 dark:text-blue-400" min="0" max="100" value="0">'
        + '<span class="text-[10px] text-zinc-400">%</span>'
        + '<button type="button" onclick="this.closest(\'.period-item\').remove()" class="absolute -top-1.5 -right-1.5 hidden group-hover:flex w-4 h-4 bg-red-500 text-white rounded-full items-center justify-center text-[10px] leading-none">&times;</button>';
    document.getElementById('periodRows').appendChild(div);
}

function addStorageRow() {
    var tr = document.createElement('tr');
    tr.className = 'border-b border-zinc-50 dark:border-zinc-700/50 storage-row';
    tr.innerHTML = '<td class="py-2 pr-2"><input type="text" class="stor-cap ' + _inp + '" placeholder="5GB"></td>'
        + '<td class="py-2 pr-2"><input type="number" class="stor-price ' + _inp + '" min="0" value="0"></td>'
        + '<td class="py-2 text-center"><button type="button" onclick="this.closest(\'tr\').remove()" class="text-red-400 hover:text-red-600 p-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></td>';
    document.getElementById('storageRows').appendChild(tr);
}

var _domainExRate = <?= $_dispRate ?>;

function fetchNamesiloPrices() {
    var btn = document.getElementById('btnFetchNS');
    var icon = document.getElementById('fetchNSIcon');
    btn.disabled = true;
    icon.classList.add('animate-spin');

    fetch('<?= $baseUrl ?>/api/namesilo-prices.php?refresh=1')
        .then(r => r.json())
        .then(data => {
            if (!data.success) { showResultModal(false, data.message || 'NameSilo API 실패'); return; }
            var ns = {};
            data.prices.forEach(function(p) { ns[p.tld] = p; });

            var updated = 0;
            document.querySelectorAll('.domain-row').forEach(function(tr) {
                var tld = tr.querySelector('.dom-tld').value.trim();
                if (!ns[tld]) return;
                var regEl = tr.querySelector('.dom-nsreg');
                var renewEl = tr.querySelector('.dom-nsrenew');
                var dispEl = tr.querySelector('.dom-nsdisp');
                if (regEl) regEl.textContent = '$' + ns[tld].registration.toFixed(2);
                if (renewEl) renewEl.textContent = '$' + ns[tld].renew.toFixed(2);
                if (dispEl) dispEl.textContent = Math.round(ns[tld].renew * _domainExRate).toLocaleString();
                updated++;
            });
            showResultModal(true, 'NameSilo ' + data.count + '개 TLD 가격 로드 (' + updated + '개 업데이트)');
        })
        .catch(function(e) { showResultModal(false, 'NameSilo 요청 실패: ' + e.message); })
        .finally(function() { btn.disabled = false; icon.classList.remove('animate-spin'); });
}

function fetchXdomainPrices() {
    var btn = document.getElementById('btnFetchDomainPrices');
    var icon = document.getElementById('fetchDomainIcon');
    btn.disabled = true;
    icon.classList.add('animate-spin');

    fetch('<?= $baseUrl ?>/api/domain-prices-crawl.php?refresh=1')
        .then(r => r.json())
        .then(data => {
            if (!data.success) { showResultModal(false, data.message || '크롤링 실패'); return; }
            // xdomain 가격을 TLD 키로 매핑
            var xp = {};
            data.prices.forEach(function(p) { xp[p.tld] = p; });

            // 기존 행의 xdomain 가격 업데이트 (取得/更新/移管)
            var updated = 0;
            document.querySelectorAll('.domain-row').forEach(function(tr) {
                var tld = tr.querySelector('.dom-tld').value.trim();
                if (xp[tld]) {
                    var p = xp[tld];
                    var getEl = tr.querySelector('.dom-xget');
                    var renewEl = tr.querySelector('.dom-xrenew');
                    var moveEl = tr.querySelector('.dom-xmove');
                    if (getEl) getEl.textContent = p.registration ? ('¥' + p.registration.toLocaleString()) : '-';
                    if (renewEl) renewEl.textContent = p.renewal ? ('¥' + p.renewal.toLocaleString()) : '-';
                    if (moveEl) moveEl.textContent = p.transfer ? ('¥' + p.transfer.toLocaleString()) : '-';
                    updated++;
                }
            });
            showResultModal(true, 'xdomain.ne.jp에서 ' + data.count + '개 TLD 가격을 가져왔습니다. (' + updated + '개 TLD 원가 업데이트)');
        })
        .catch(function(e) { showResultModal(false, '크롤링 요청 실패: ' + e.message); })
        .finally(function() {
            btn.disabled = false;
            icon.classList.remove('animate-spin');
        });
}

function importAllXdomainTLDs() {
    var btn = document.getElementById('btnImportAllTLD');
    btn.disabled = true;

    fetch('<?= $baseUrl ?>/api/domain-prices-crawl.php')
        .then(r => r.json())
        .then(data => {
            if (!data.success) { showResultModal(false, data.message || '크롤링 데이터 로드 실패'); return; }

            // 기존 TLD 수집
            var existing = {};
            document.querySelectorAll('.domain-row .dom-tld').forEach(function(inp) {
                existing[inp.value.trim()] = true;
            });

            var added = 0;
            data.prices.forEach(function(p) {
                if (existing[p.tld]) {
                    // 이미 있는 TLD → xdomain 가격만 업데이트
                    document.querySelectorAll('.domain-row').forEach(function(tr) {
                        if (tr.querySelector('.dom-tld').value.trim() === p.tld) {
                            var getEl = tr.querySelector('.dom-xget');
                            var renewEl = tr.querySelector('.dom-xrenew');
                            var moveEl = tr.querySelector('.dom-xmove');
                            if (getEl) getEl.textContent = p.registration ? ('¥' + p.registration.toLocaleString()) : '-';
                            if (renewEl) renewEl.textContent = p.renewal ? ('¥' + p.renewal.toLocaleString()) : '-';
                            if (moveEl) moveEl.textContent = p.transfer ? ('¥' + p.transfer.toLocaleString()) : '-';
                        }
                    });
                    return;
                }

                // 새 TLD 추가
                var tr = document.createElement('tr');
                tr.className = 'border-b border-zinc-50 dark:border-zinc-700/50 domain-row';
                var xget = p.registration ? ('¥' + p.registration.toLocaleString()) : '-';
                var xrenew = p.renewal ? ('¥' + p.renewal.toLocaleString()) : '-';
                var xmove = p.transfer ? ('¥' + p.transfer.toLocaleString()) : '-';
                tr.innerHTML = '<td class="py-1.5 px-1 text-center"><input type="checkbox" class="dom-active rounded text-blue-600 cursor-pointer" checked></td>'
                    + '<td class="py-1.5 pr-1"><input type="text" value="' + p.tld + '" class="dom-tld ' + _inp + ' text-xs font-mono text-center" style="max-width:70px"></td>'
                    + '<td class="py-1.5 px-1 text-center"><span class="text-xs text-orange-400 dom-nsreg">-</span></td>'
                    + '<td class="py-1.5 px-1 text-center"><span class="text-xs text-orange-500 font-medium dom-nsrenew">-</span></td>'
                    + '<td class="py-1.5 px-1 text-center"><span class="dom-nsdisp text-xs text-zinc-400">-</span></td>'
                    + '<td class="py-1.5 px-1 text-center"><span class="text-xs text-purple-400 dom-xget">' + xget + '</span></td>'
                    + '<td class="py-1.5 px-1 text-center"><span class="text-xs text-purple-500 font-medium dom-xrenew">' + xrenew + '</span></td>'
                    + '<td class="py-1.5 px-1 text-center"><span class="text-xs text-purple-400 dom-xmove">' + xmove + '</span></td>'
                    + '<td class="py-1.5 px-1"><input type="number" value="0" class="dom-vip ' + _inp + ' text-xs text-center" min="0" style="max-width:100px"></td>'
                    + '<td class="py-1.5 px-1"><input type="number" value="0" class="dom-price ' + _inp + ' text-xs text-center" min="0" style="max-width:100px"></td>'
                    + '<td class="py-1.5 px-1"><div class="flex items-center gap-0.5"><input type="number" value="0" class="dom-disc ' + _inp + ' text-xs text-center" min="0" max="100" <span class="text-[10px] text-zinc-400">%</span></div></td>'
                    + '<td class="py-1.5 text-center"><button type="button" onclick="this.closest(\'tr\').remove()" class="text-red-400 hover:text-red-600 p-0.5"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></td>';
                document.getElementById('domainRows').appendChild(tr);
                added++;
                existing[p.tld] = true;
            });

            showResultModal(true, 'xdomain ' + data.count + '개 TLD 중 ' + added + '개 신규 추가 (기존 TLD 가격 업데이트 완료)');
        })
        .catch(function(e) { showResultModal(false, '요청 실패: ' + e.message); })
        .finally(function() { btn.disabled = false; });
}

function addDomainRow() {
    var tr = document.createElement('tr');
    tr.className = 'border-b border-zinc-50 dark:border-zinc-700/50 domain-row';
    tr.innerHTML = '<td class="py-1.5 px-1 text-center"><input type="checkbox" class="dom-active rounded text-blue-600 cursor-pointer" checked></td>'
        + '<td class="py-1.5 pr-1"><input type="text" class="dom-tld ' + _inp + ' text-xs font-mono text-center" placeholder=".com" style="max-width:70px"></td>'
        + '<td class="py-1.5 px-1 text-center"><span class="text-xs text-orange-400 dom-nsreg">-</span></td>'
        + '<td class="py-1.5 px-1 text-center"><span class="text-xs text-orange-500 font-medium dom-nsrenew">-</span></td>'
        + '<td class="py-1.5 px-1 text-center"><span class="dom-nsdisp text-xs text-zinc-400">-</span></td>'
        + '<td class="py-1.5 px-1 text-center"><span class="text-xs text-purple-400 dom-xget">-</span></td>'
        + '<td class="py-1.5 px-1 text-center"><span class="text-xs text-purple-500 font-medium dom-xrenew">-</span></td>'
        + '<td class="py-1.5 px-1 text-center"><span class="text-xs text-purple-400 dom-xmove">-</span></td>'
        + '<td class="py-1.5 px-1"><input type="number" class="dom-vip ' + _inp + ' text-xs text-center" min="0" value="0" style="max-width:100px"></td>'
        + '<td class="py-1.5 px-1"><input type="number" class="dom-price ' + _inp + ' text-xs text-center" min="0" value="0" style="max-width:100px"></td>'
        + '<td class="py-1.5 px-1"><div class="flex items-center gap-0.5"><input type="number" class="dom-disc ' + _inp + ' text-xs text-center" min="0" max="100" value="0" <span class="text-[10px] text-zinc-400">%</span></div></td>'
        + '<td class="py-1.5 text-center"><button type="button" onclick="this.closest(\'tr\').remove()" class="text-red-400 hover:text-red-600 p-0.5"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></td>';
    document.getElementById('domainRows').appendChild(tr);
}

function getRoundingUnit() {
    var curCode = '<?= $_dispCur ?>';
    var sel = document.getElementById('svcRound_' + curCode);
    return sel ? parseFloat(sel.value) || 1 : 1;
}

function roundPrice(value) {
    var unit = getRoundingUnit();
    return Math.round(value / unit) * unit;
}

function applyMultiplier(type, value) {
    var mul = parseFloat(value);
    if (!mul || mul <= 0) return;
    var priceField = type === 'vip' ? '.dom-vip' : '.dom-price';

    document.querySelectorAll('.domain-row').forEach(function(tr) {
        // 1순위: xdomain 更新 가격
        var renewEl = tr.querySelector('.dom-xrenew');
        var renewText = renewEl ? renewEl.textContent.replace(/[¥,\s]/g, '') : '';
        var basePrice = parseFloat(renewText) || 0;

        // 2순위: NameSilo Renew ($) × 표시통화 환율
        if (basePrice <= 0) {
            var nsEl = tr.querySelector('.dom-nsrenew');
            var nsText = nsEl ? nsEl.textContent.replace(/[$,\s]/g, '') : '';
            var nsUsd = parseFloat(nsText) || 0;
            if (nsUsd > 0) {
                basePrice = nsUsd * _domainExRate;
            }
        }

        if (basePrice > 0) {
            var calculated = roundPrice(basePrice * mul);
            tr.querySelector(priceField).value = calculated;
        }
    });
}

function addAddonRow() {
    var tr = document.createElement('tr');
    tr.className = 'border-b border-zinc-50 dark:border-zinc-700/50 addon-row';
    tr.innerHTML = '<td class="py-1.5 pr-2"><input type="text" class="addon-label ' + _inp + ' text-xs" placeholder="서비스명"></td>'
        + '<td class="py-1.5 px-2"><input type="text" class="addon-desc ' + _inp + ' text-xs" placeholder="설명"></td>'
        + '<td class="py-1.5 px-2"><input type="number" class="addon-price ' + _inp + ' text-xs text-center" min="0" value="0"></td>'
        + '<td class="py-1.5 px-2"><input type="text" class="addon-unit ' + _inp + ' text-xs text-center" placeholder="/년, /월"></td>'
        + '<td class="py-1.5 px-2 text-center"><input type="checkbox" class="addon-checked rounded text-blue-600"></td>'
        + '<td class="py-1.5 text-center"><button type="button" onclick="this.closest(\'tr\').remove()" class="text-red-400 hover:text-red-600 p-0.5"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></td>';
    document.getElementById('addonRows').appendChild(tr);
}

function addMaintRow() {
    var tr = document.createElement('tr');
    tr.className = 'border-b border-zinc-50 dark:border-zinc-700/50 maint-row';
    tr.innerHTML = '<td class="py-1.5 pr-2"><input type="text" class="maint-label ' + _inp + ' text-xs" placeholder="등급명"></td>'
        + '<td class="py-1.5 px-2"><input type="number" class="maint-price ' + _inp + ' text-xs text-center" min="0" value="0"></td>'
        + '<td class="py-1.5 px-2"><input type="text" class="maint-desc ' + _inp + ' text-xs" placeholder="설명"></td>'
        + '<td class="py-1.5 px-2"><input type="text" class="maint-badge ' + _inp + ' text-xs" placeholder="선택"></td>'
        + '<td class="py-1.5 text-center"><button type="button" onclick="this.closest(\'tr\').remove()" class="text-red-400 hover:text-red-600 p-0.5"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></td>';
    document.getElementById('maintRows').appendChild(tr);
}

function collectAddonsData() {
    var addons = [], maintenance = [];
    document.querySelectorAll('.addon-row').forEach(function(tr) {
        var label = tr.querySelector('.addon-label').value.trim();
        if (!label) return;
        addons.push({
            label: label,
            desc: tr.querySelector('.addon-desc').value,
            price: parseInt(tr.querySelector('.addon-price').value) || 0,
            unit: tr.querySelector('.addon-unit').value,
            checked: tr.querySelector('.addon-checked').checked,
            type: 'checkbox'
        });
    });
    document.querySelectorAll('.maint-row').forEach(function(tr) {
        var label = tr.querySelector('.maint-label').value.trim();
        if (!label) return;
        maintenance.push({
            label: label,
            price: parseInt(tr.querySelector('.maint-price').value) || 0,
            desc: tr.querySelector('.maint-desc').value,
            badge: tr.querySelector('.maint-badge').value
        });
    });
    return { addons: addons, maintenance: maintenance };
}

function addSearchTLD() {
    var input = document.getElementById('newSearchTLD');
    var tld = input.value.trim().toLowerCase();
    if (!tld) return;
    if (tld[0] !== '.') tld = '.' + tld;
    // 중복 체크
    var exists = false;
    document.querySelectorAll('.search-tld-tag').forEach(function(el) {
        if (el.textContent.trim().replace('×','').trim() === tld) exists = true;
    });
    if (exists) { input.value = ''; return; }
    var span = document.createElement('span');
    span.className = 'search-tld-tag inline-flex items-center gap-1 px-2.5 py-1 bg-cyan-50 dark:bg-cyan-900/30 text-cyan-700 dark:text-cyan-300 text-xs font-medium rounded-lg';
    span.innerHTML = tld + ' <button type="button" onclick="this.closest(\'.search-tld-tag\').remove()" class="text-cyan-400 hover:text-red-500 ml-0.5">&times;</button>';
    document.getElementById('searchTLDList').appendChild(span);
    input.value = '';
}

function collectSearchTLDs() {
    var tlds = [];
    document.querySelectorAll('.search-tld-tag').forEach(function(el) {
        var t = el.textContent.trim().replace('×','').trim();
        if (t) tlds.push(t);
    });
    return tlds;
}

function collectDomainData() {
    var domains = [];
    document.querySelectorAll('.domain-row').forEach(function(tr) {
        var tld = tr.querySelector('.dom-tld').value.trim();
        if (!tld) return;
        domains.push({
            tld: tld,
            active: tr.querySelector('.dom-active').checked,
            vip_price: parseInt(tr.querySelector('.dom-vip').value) || 0,
            price: parseInt(tr.querySelector('.dom-price').value) || 0,
            discount: parseInt(tr.querySelector('.dom-disc').value) || 0
        });
    });
    return domains;
}

function addFeatureRow() {
    var div = document.createElement('div');
    div.className = 'feat-item flex items-center gap-2';
    div.innerHTML = '<svg class="w-3.5 h-3.5 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>'
        + '<input type="text" class="feat-text ' + _inp + ' flex-1" placeholder="서비스 항목">'
        + '<button type="button" onclick="this.closest(\'.feat-item\').remove()" class="text-red-400 hover:text-red-600 p-1 shrink-0"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>';
    document.getElementById('featureRows').appendChild(div);
}

function collectHostingData() {
    var plans = [], periods = [], storage = [], features = [];
    document.querySelectorAll('.plan-row').forEach(function(tr) {
        plans.push({
            label: tr.querySelector('.plan-label').value,
            capacity: tr.querySelector('.plan-cap').value,
            price: parseInt(tr.querySelector('.plan-price').value) || 0,
            features: tr.querySelector('.plan-feat').value,
            locked: tr.dataset.locked === '1'
        });
    });
    document.querySelectorAll('.period-item').forEach(function(el) {
        periods.push({
            months: parseInt(el.querySelector('.period-months').value) || 1,
            discount: parseInt(el.querySelector('.period-disc').value) || 0
        });
    });
    document.querySelectorAll('.storage-row').forEach(function(tr) {
        storage.push({
            capacity: tr.querySelector('.stor-cap').value,
            price: parseInt(tr.querySelector('.stor-price').value) || 0
        });
    });
    document.querySelectorAll('.feat-text').forEach(function(inp) {
        if (inp.value.trim()) features.push(inp.value.trim());
    });
    return { plans: plans, periods: periods, storage: storage, features: features };
}

function saveServiceSettings(successMsg) {
    var url = <?= !empty($embedMode) ? "'{$baseUrl}/" . htmlspecialchars($pageSlug) . "/settings?tab=skin'" : "'{$adminUrl}/site/pages/settings?slug=" . urlencode($pageSlug) . "&tab=skin'" ?>;
    var hosting = collectHostingData();
    var formData = new FormData();
    formData.append('action', 'save_service_settings');
    formData.append('service_currency', document.getElementById('svcCurrency').value);
    formData.append('service_exchange_rate', document.getElementById('svcRateKRW').value);
    formData.append('service_rate_jpy', document.getElementById('svcRateJPY').value);
    formData.append('service_rate_cny', document.getElementById('svcRateCNY').value);
    formData.append('service_rate_eur', document.getElementById('svcRateEUR').value);
    formData.append('service_exchange_auto', document.getElementById('svcExchangeAuto').value);
    var rounding = {};
    document.querySelectorAll('.svc-round').forEach(function(sel) { rounding[sel.dataset.currency] = sel.value; });
    formData.append('service_rounding', JSON.stringify(rounding));
    var addonsData = collectAddonsData();
    formData.append('service_addons', JSON.stringify(addonsData.addons));
    formData.append('service_maintenance', JSON.stringify(addonsData.maintenance));
    formData.append('service_search_tlds', JSON.stringify(collectSearchTLDs()));
    formData.append('service_domain_pricing', JSON.stringify(collectDomainData()));
    formData.append('service_namesilo_key', document.getElementById('svcNamesiloKey').value);
    if (document.getElementById('svcNamesiloSandbox').checked) {
        formData.append('service_namesilo_sandbox', '1');
    }
    formData.append('service_hosting_plans', JSON.stringify(hosting.plans));
    formData.append('service_hosting_periods', JSON.stringify(hosting.periods));
    formData.append('service_hosting_storage', JSON.stringify(hosting.storage));
    formData.append('service_hosting_features', JSON.stringify(hosting.features));

    fetch(url, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            showResultModal(data.success, data.success ? (successMsg || '') : (data.message || '저장 실패'));
        })
        .catch(function() {
            showResultModal(false, '오류가 발생했습니다.');
        });
}

function fetchExchangeRates() {
    var btn = document.getElementById('btnFetchRates');
    var icon = document.getElementById('fetchRatesIcon');
    btn.disabled = true;
    icon.classList.add('animate-spin');

    fetch('<?= $baseUrl ?>/api/exchange-rates.php?refresh=1')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                document.getElementById('svcRateKRW').value = data.rates.KRW || '';
                document.getElementById('svcRateJPY').value = data.rates.JPY || '';
                document.getElementById('svcRateCNY').value = data.rates.CNY || '';
                document.getElementById('svcRateEUR').value = data.rates.EUR || '';
                document.getElementById('rateStatus').innerHTML =
                    '기준일: ' + data.date + ' · <a href="https://frankfurter.dev" target="_blank" class="text-blue-500 hover:underline">Frankfurter API</a> · <span class="text-green-600">방금 업데이트됨</span>';
                // 도메인 환율 적용 표시 업데이트
                var _curCode = '<?= $_dispCur ?>';
                _domainExRate = data.rates[_curCode] || _domainExRate;
                document.getElementById('domainExRate').textContent = parseFloat(_domainExRate).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
                // NameSilo Renew 환율 적용 표시 갱신
                document.querySelectorAll('.domain-row').forEach(function(tr) {
                    var renewEl = tr.querySelector('.dom-nsrenew');
                    var dispEl = tr.querySelector('.dom-nsdisp');
                    if (renewEl && dispEl && renewEl.textContent !== '-') {
                        var usd = parseFloat(renewEl.textContent.replace('$','')) || 0;
                        dispEl.textContent = Math.round(usd * _domainExRate).toLocaleString();
                    }
                });
                // 환율 즉시 DB 저장
                saveExchangeRates();
            } else {
                showResultModal(false, data.message || '환율 가져오기 실패');
            }
        })
        .catch(function(e) { showResultModal(false, '환율 API 요청 실패: ' + e.message); })
        .finally(function() {
            btn.disabled = false;
            icon.classList.remove('animate-spin');
        });
}

function saveExchangeRates() {
    // 현재 input 값으로 전체 서비스 설정 저장 (환율 포함)
    saveServiceSettings('환율이 업데이트되었습니다.');
}
</script>
