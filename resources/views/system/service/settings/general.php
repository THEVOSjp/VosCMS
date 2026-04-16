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
