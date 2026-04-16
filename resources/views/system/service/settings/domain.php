<!-- ③-2 무료 도메인 설정 -->
<?php
$_defFreeDomains = ['21ces.net'];
$_freeDomains = json_decode($serviceSettings['service_free_domains'] ?? '', true) ?: $_defFreeDomains;
?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700 overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-700">
        <h3 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
            <svg class="w-4 h-4 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/></svg>
            무료 도메인
        </h3>
    </div>
    <div class="px-6 py-4">
        <p class="text-xs text-zinc-400 mb-3">서비스 신청 시 무료로 제공할 서브도메인 목록입니다. 첫 번째 도메인이 기본 선택됩니다.</p>
        <div class="flex flex-wrap gap-2" id="freeDomainList">
            <?php foreach ($_freeDomains as $fd): ?>
            <span class="free-domain-tag inline-flex items-center gap-1 px-2.5 py-1 bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-xs font-mono font-medium rounded-lg">
                .<?= htmlspecialchars($fd) ?>
                <button type="button" onclick="this.closest('.free-domain-tag').remove()" class="text-green-400 hover:text-red-500 ml-0.5">&times;</button>
            </span>
            <?php endforeach; ?>
        </div>
        <div class="flex items-center gap-2 mt-3">
            <input type="text" id="newFreeDomain" placeholder="example.net" class="<?= $_inp ?> text-xs max-w-[180px] font-mono" onkeydown="if(event.key==='Enter'){event.preventDefault();addFreeDomain();}">
            <button type="button" onclick="addFreeDomain()" class="text-xs text-green-600 dark:text-green-400 hover:underline">+ 추가</button>
        </div>
    </div>
</div>

<!-- ③-3 등록 불가 서브도메인 -->
<?php
$_defBlockedSubs = ['www','mail','ftp','admin','test*','dev','staging','api','ns[n]','mx','smtp','pop','imap','localhost','cpanel','webmail'];
$_blockedSubs = json_decode($serviceSettings['service_blocked_subdomains'] ?? '', true) ?: $_defBlockedSubs;
?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700 overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-zinc-100 dark:border-zinc-700">
        <h3 class="text-sm font-bold text-zinc-900 dark:text-white flex items-center gap-2">
            <svg class="w-4 h-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
            등록 불가 서브도메인
        </h3>
    </div>
    <div class="px-6 py-4">
        <p class="text-xs text-zinc-400 mb-3">무료 도메인 신청 시 사용할 수 없는 서브도메인 목록입니다. 패턴: <code class="text-red-500">test*</code> 접두사 차단, <code class="text-red-500">*admin</code> 접미사 차단, <code class="text-red-500">ns[n]</code> 문자+숫자 차단.</p>
        <div class="flex flex-wrap gap-1.5" id="blockedSubList">
            <?php foreach ($_blockedSubs as $bs): ?>
            <span class="blocked-sub-tag inline-flex items-center gap-1 px-2 py-0.5 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-xs font-mono rounded">
                <?= htmlspecialchars($bs) ?>
                <button type="button" onclick="this.closest('.blocked-sub-tag').remove()" class="text-red-300 hover:text-red-600 ml-0.5">&times;</button>
            </span>
            <?php endforeach; ?>
        </div>
        <div class="flex items-center gap-2 mt-3">
            <input type="text" id="newBlockedSub" placeholder="subdomain" class="<?= $_inp ?> text-xs max-w-[150px] font-mono" onkeydown="if(event.key==='Enter'){event.preventDefault();addBlockedSub();}">
            <button type="button" onclick="addBlockedSub()" class="text-xs text-red-600 dark:text-red-400 hover:underline">+ 추가</button>
        </div>
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

