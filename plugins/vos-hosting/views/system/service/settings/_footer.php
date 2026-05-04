<!-- 저장 버튼은 각 탭 view 에 자체 배치 (탭별 저장: general/domain/hosting/addons) -->

<script>
var _inp = '<?= $_inp ?> text-xs';

// ─── 탭별 저장 헬퍼 (공통) ───
function _saveServiceTabSubmit(formData, successMsg) {
    var url = <?= !empty($embedMode) ? "'{$baseUrl}/" . htmlspecialchars($pageSlug) . "/settings?tab=skin'" : "'{$adminUrl}/site/pages/settings?slug=" . urlencode($pageSlug) . "&tab=skin'" ?>;
    fetch(url, { method: 'POST', body: formData })
        .then(r => r.json())
        .then(data => {
            showResultModal(data.success, data.success ? (successMsg || '저장되었습니다.') : (data.message || '저장 실패'));
        })
        .catch(function() {
            showResultModal(false, '오류가 발생했습니다.');
        });
}

// ─── 기본 설정 탭 (general) ───
function saveGeneralSettings() {
    var fd = new FormData();
    fd.append('action', 'save_service_settings');
    var _v = function(id) { var el = document.getElementById(id); return el ? el.value : ''; };
    fd.append('service_currency', _v('svcCurrency'));
    fd.append('service_exchange_rate', _v('svcRateKRW'));
    fd.append('service_rate_jpy', _v('svcRateJPY'));
    fd.append('service_rate_cny', _v('svcRateCNY'));
    fd.append('service_rate_eur', _v('svcRateEUR'));
    fd.append('service_exchange_auto', _v('svcExchangeAuto'));
    var rounding = {};
    document.querySelectorAll('.svc-round').forEach(function(sel) { rounding[sel.dataset.currency] = sel.value; });
    fd.append('service_rounding', JSON.stringify(rounding));
    _saveServiceTabSubmit(fd, <?= json_encode(__('services.admin.general.save_success'), JSON_UNESCAPED_UNICODE) ?>);
}

// ─── 도메인 탭 (domain) ───
function saveDomainSettings() {
    var fd = new FormData();
    fd.append('action', 'save_service_settings');
    var _v = function(id) { var el = document.getElementById(id); return el ? el.value : ''; };
    fd.append('service_free_domains', JSON.stringify(collectFreeDomains()));
    fd.append('service_blocked_subdomains', JSON.stringify(collectBlockedSubs()));
    fd.append('service_search_tlds', JSON.stringify(collectSearchTLDs()));
    fd.append('service_domain_pricing', JSON.stringify(collectDomainData()));
    fd.append('service_namesilo_key', _v('svcNamesiloKey'));
    var sandboxEl = document.getElementById('svcNamesiloSandbox');
    fd.append('service_namesilo_sandbox', (sandboxEl && sandboxEl.checked) ? '1' : '0');
    _saveServiceTabSubmit(fd, <?= json_encode(__('services.admin.domain.save_success'), JSON_UNESCAPED_UNICODE) ?>);
}

// ─── 웹 호스팅 탭 (hosting) ───
function saveHostingSettings() {
    var fd = new FormData();
    fd.append('action', 'save_service_settings');
    var hosting = collectHostingData();
    fd.append('service_hosting_plans', JSON.stringify(hosting.plans));
    fd.append('service_hosting_periods', JSON.stringify(hosting.periods));
    fd.append('service_hosting_storage', JSON.stringify(hosting.storage));
    fd.append('service_hosting_features', JSON.stringify(hosting.features));
    fd.append('service_db_storage', JSON.stringify(hosting.dbStorage));
    fd.append('service_db_quota_mb', String(hosting.dbQuotaMb));
    _saveServiceTabSubmit(fd, <?= json_encode(__('services.admin.hosting.save_success'), JSON_UNESCAPED_UNICODE) ?>);
}

// ─── 부가서비스 탭 (addons) ───
function saveAddonsSettings() {
    var fd = new FormData();
    fd.append('action', 'save_service_settings');
    var addonsData = collectAddonsData();
    fd.append('service_addons', JSON.stringify(addonsData.addons));
    fd.append('service_maintenance', JSON.stringify(addonsData.maintenance));
    _saveServiceTabSubmit(fd, <?= json_encode(__('services.admin.addons.save_success'), JSON_UNESCAPED_UNICODE) ?>);
}

function addPlanRow() {
    var sid = _makeStableId();
    var tr = document.createElement('tr');
    tr.className = 'border-b border-zinc-50 dark:border-zinc-700/50 plan-row';
    tr.dataset.locked = '0';
    tr.setAttribute('data-stable-id', sid);
    tr.innerHTML = '<td class="py-2 pr-2">' + _multilangInputHtml('plan_label_' + sid, 'service.hosting.plan.' + sid + '.label', '', 'plan-label text-xs', <?= json_encode(__('services.admin.hosting.placeholder_plan_name'), JSON_UNESCAPED_UNICODE) ?>) + '</td>'
        + '<td class="py-2 pr-2"><input type="text" class="plan-cap ' + _inp + '" placeholder="<?= htmlspecialchars(__('services.admin.hosting.placeholder_capacity'), ENT_QUOTES) ?>"></td>'
        + '<td class="py-2 pr-2"><input type="number" class="plan-price ' + _inp + '" min="0" value="0"></td>'
        + '<td class="py-2 pr-2"><input type="number" class="plan-mail ' + _inp + '" min="0" max="50" value="5"></td>'
        + '<td class="py-2 pr-2">' + _multilangInputHtml('plan_feat_' + sid, 'service.hosting.plan.' + sid + '.features', '', 'plan-feat text-xs', <?= json_encode(__('services.admin.hosting.placeholder_features'), JSON_UNESCAPED_UNICODE) ?>) + '</td>'
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

function addDbStorageRow() {
    var tr = document.createElement('tr');
    tr.className = 'border-b border-zinc-50 dark:border-zinc-700/50 db-storage-row';
    tr.innerHTML = '<td class="py-2 pr-2"><input type="text" class="dbstor-cap ' + _inp + '" placeholder="100MB"></td>'
        + '<td class="py-2 pr-2"><input type="number" class="dbstor-price ' + _inp + '" min="0" value="0"></td>'
        + '<td class="py-2 text-center"><button type="button" onclick="this.closest(\'tr\').remove()" class="text-red-400 hover:text-red-600 p-1"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></td>';
    document.getElementById('dbStorageRows').appendChild(tr);
}

var _domainExRate = <?= $_dispRate ?? 1 ?>;

function fetchNamesiloPrices() {
    var btn = document.getElementById('btnFetchNS');
    var icon = document.getElementById('fetchNSIcon');
    btn.disabled = true;
    icon.classList.add('animate-spin');

    fetch('<?= $baseUrl ?>/plugins/vos-hosting/api/namesilo-prices.php?refresh=1')
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

    fetch('<?= $baseUrl ?>/plugins/vos-hosting/api/domain-prices-crawl.php?refresh=1')
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

    fetch('<?= $baseUrl ?>/plugins/vos-hosting/api/domain-prices-crawl.php')
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

// stable_id 생성 (timestamp + random hex). 새 row 의 lang_key 매핑용.
function _makeStableId() {
    return 'a' + Date.now().toString(16) + Math.floor(Math.random() * 0xFFFF + 0x1000).toString(16);
}

// rzx_multilang_input 와 동일한 구조의 HTML 생성 (input + 인라인 지구본 버튼)
function _multilangInputHtml(inputId, langKey, value, extraClass, placeholder) {
    var safeVal = (value || '').replace(/"/g, '&quot;');
    var ph = placeholder ? ' placeholder="' + placeholder + '"' : '';
    var baseClass = 'w-full text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200 placeholder-zinc-400';
    var globe = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>';
    return '<div class="relative">'
        + '<input type="text" name="' + inputId + '" id="' + inputId + '" value="' + safeVal + '"'
        + ph + ' class="' + baseClass + ' px-3 py-2 pr-8 ' + (extraClass || '') + '">'
        + '<button type="button" onclick="openMultilangModal(\'' + langKey + '\', \'' + inputId + '\')" '
        + 'class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition" title="다국어">'
        + globe + '</button></div>';
}

function addAddonRow() {
    var sid = _makeStableId();
    var tr = document.createElement('tr');
    tr.className = 'border-b border-zinc-50 dark:border-zinc-700/50 addon-row';
    tr.setAttribute('data-stable-id', sid);
    tr.innerHTML = '<td class="py-1.5 pr-2">' + _multilangInputHtml('addon_label_' + sid, 'service.addon.' + sid + '.label', '', 'addon-label text-xs', '서비스명') + '</td>'
        + '<td class="py-1.5 px-2">' + _multilangInputHtml('addon_desc_' + sid, 'service.addon.' + sid + '.desc', '', 'addon-desc text-xs', '설명') + '</td>'
        + '<td class="py-1.5 px-2"><input type="number" class="addon-price ' + _inp + ' text-xs text-center" min="0" value="0"></td>'
        + '<td class="py-1.5 px-2">' + _multilangInputHtml('addon_unit_' + sid, 'service.addon.' + sid + '.unit', '', 'addon-unit text-xs', '/년, /월') + '</td>'
        + '<td class="py-1.5 px-2 text-center"><input type="checkbox" class="addon-checked rounded text-blue-600"></td>'
        + '<td class="py-1.5 px-2 text-center"><input type="checkbox" class="addon-onetime rounded text-amber-600"></td>'
        + '<td class="py-1.5 text-center"><button type="button" onclick="this.closest(\'tr\').remove()" class="text-red-400 hover:text-red-600 p-0.5"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></td>';
    document.getElementById('addonRows').appendChild(tr);
}

function addMaintRow() {
    var sid = _makeStableId();
    var tr = document.createElement('tr');
    tr.className = 'border-b border-zinc-50 dark:border-zinc-700/50 maint-row';
    tr.setAttribute('data-stable-id', sid);
    tr.innerHTML = '<td class="py-1.5 pr-2">' + _multilangInputHtml('maint_label_' + sid, 'service.maintenance.' + sid + '.label', '', 'maint-label text-xs', '등급명') + '</td>'
        + '<td class="py-1.5 px-2"><input type="number" class="maint-price ' + _inp + ' text-xs text-center" min="0" value="0"></td>'
        + '<td class="py-1.5 px-2">' + _multilangInputHtml('maint_desc_' + sid, 'service.maintenance.' + sid + '.desc', '', 'maint-desc text-xs', '설명') + '</td>'
        + '<td class="py-1.5 px-2">' + _multilangInputHtml('maint_badge_' + sid, 'service.maintenance.' + sid + '.badge', '', 'maint-badge text-xs', '선택') + '</td>'
        + '<td class="py-1.5 text-center"><button type="button" onclick="this.closest(\'tr\').remove()" class="text-red-400 hover:text-red-600 p-0.5"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button></td>';
    document.getElementById('maintRows').appendChild(tr);
}

function collectAddonsData() {
    var addons = [], maintenance = [];
    document.querySelectorAll('.addon-row').forEach(function(tr) {
        var label = tr.querySelector('.addon-label').value.trim();
        if (!label) return;
        addons.push({
            _id: tr.dataset.stableId || '',
            label: label,
            desc: tr.querySelector('.addon-desc').value,
            price: parseInt(tr.querySelector('.addon-price').value) || 0,
            unit: tr.querySelector('.addon-unit').value,
            checked: tr.querySelector('.addon-checked').checked,
            one_time: tr.querySelector('.addon-onetime').checked,
            type: 'checkbox'
        });
    });
    document.querySelectorAll('.maint-row').forEach(function(tr) {
        var label = tr.querySelector('.maint-label').value.trim();
        if (!label) return;
        maintenance.push({
            _id: tr.dataset.stableId || '',
            label: label,
            price: parseInt(tr.querySelector('.maint-price').value) || 0,
            desc: tr.querySelector('.maint-desc').value,
            badge: tr.querySelector('.maint-badge').value
        });
    });
    return { addons: addons, maintenance: maintenance };
}

function addFreeDomain() {
    var input = document.getElementById('newFreeDomain');
    var domain = input.value.trim().toLowerCase().replace(/^\./, '');
    if (!domain) return;
    var exists = false;
    document.querySelectorAll('.free-domain-tag').forEach(function(el) {
        if (el.textContent.trim().replace('×','').trim().replace(/^\./, '') === domain) exists = true;
    });
    if (exists) { input.value = ''; return; }
    var span = document.createElement('span');
    span.className = 'free-domain-tag inline-flex items-center gap-1 px-2.5 py-1 bg-green-50 dark:bg-green-900/30 text-green-700 dark:text-green-300 text-xs font-mono font-medium rounded-lg';
    span.innerHTML = '.' + domain + ' <button type="button" onclick="this.closest(\'.free-domain-tag\').remove()" class="text-green-400 hover:text-red-500 ml-0.5">&times;</button>';
    document.getElementById('freeDomainList').appendChild(span);
    input.value = '';
}

function collectFreeDomains() {
    var domains = [];
    document.querySelectorAll('.free-domain-tag').forEach(function(el) {
        var d = el.textContent.trim().replace('×','').trim().replace(/^\./, '');
        if (d) domains.push(d);
    });
    return domains;
}

function addBlockedSub() {
    var input = document.getElementById('newBlockedSub');
    var val = input.value.trim().toLowerCase().replace(/[^a-z0-9\-\*\[\]]/g, '');
    if (!val) return;
    var exists = false;
    document.querySelectorAll('.blocked-sub-tag').forEach(function(el) {
        if (el.textContent.trim().replace('×','').trim() === val) exists = true;
    });
    if (exists) { input.value = ''; return; }
    var span = document.createElement('span');
    span.className = 'blocked-sub-tag inline-flex items-center gap-1 px-2 py-0.5 bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 text-xs font-mono rounded';
    span.innerHTML = val + ' <button type="button" onclick="this.closest(\'.blocked-sub-tag\').remove()" class="text-red-300 hover:text-red-600 ml-0.5">&times;</button>';
    document.getElementById('blockedSubList').appendChild(span);
    input.value = '';
}

function collectBlockedSubs() {
    var subs = [];
    document.querySelectorAll('.blocked-sub-tag').forEach(function(el) {
        var v = el.textContent.trim().replace('×','').trim();
        if (v) subs.push(v);
    });
    return subs;
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
    var sid = _makeStableId();
    var div = document.createElement('div');
    div.className = 'feat-item flex items-center gap-2';
    div.setAttribute('data-stable-id', sid);
    div.innerHTML = '<svg class="w-3.5 h-3.5 text-green-500 shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>'
        + '<div class="flex-1">' + _multilangInputHtml('feat_text_' + sid, 'service.hosting.feature.' + sid + '.text', '', 'feat-text text-xs', <?= json_encode(__('services.admin.hosting.placeholder_feature'), JSON_UNESCAPED_UNICODE) ?>) + '</div>'
        + '<button type="button" onclick="this.closest(\'.feat-item\').remove()" class="text-red-400 hover:text-red-600 p-1 shrink-0"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>';
    document.getElementById('featureRows').appendChild(div);
}

function collectHostingData() {
    var plans = [], periods = [], storage = [], features = [];
    document.querySelectorAll('.plan-row').forEach(function(tr) {
        var mailEl = tr.querySelector('.plan-mail');
        plans.push({
            _id: tr.dataset.stableId || '',
            label: tr.querySelector('.plan-label').value,
            capacity: tr.querySelector('.plan-cap').value,
            price: parseInt(tr.querySelector('.plan-price').value) || 0,
            free_mail_count: mailEl ? (parseInt(mailEl.value) || 0) : 5,
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
    // DB 추가 용량 옵션
    var dbStorage = [];
    document.querySelectorAll('.db-storage-row').forEach(function(tr) {
        dbStorage.push({
            capacity: tr.querySelector('.dbstor-cap').value,
            price: parseInt(tr.querySelector('.dbstor-price').value) || 0
        });
    });
    var dbQuotaEl = document.getElementById('dbQuotaMb');
    var dbQuotaMb = dbQuotaEl ? (parseInt(dbQuotaEl.value) || 100) : 100;
    // features: 객체 배열 ({_id, text}) 로 저장 (다국어 매핑용)
    document.querySelectorAll('.feat-item').forEach(function(el) {
        var inp = el.querySelector('.feat-text');
        var text = inp ? inp.value.trim() : '';
        if (!text) return;
        features.push({ _id: el.dataset.stableId || '', text: text });
    });
    return { plans: plans, periods: periods, storage: storage, dbStorage: dbStorage, dbQuotaMb: dbQuotaMb, features: features };
}

// 기존 saveServiceSettings() 는 saveGeneralSettings/saveDomainSettings/saveHostingSettings/saveAddonsSettings 4종으로 분리됨 (탭별 저장)

function fetchExchangeRates() {
    var btn = document.getElementById('btnFetchRates');
    var icon = document.getElementById('fetchRatesIcon');
    if (btn) btn.disabled = true;
    if (icon) icon.classList.add('animate-spin');

    fetch('<?= $baseUrl ?>/api/exchange-rates.php?refresh=1')
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                var _set = function(id, val) { var el = document.getElementById(id); if (el) el.value = val; };
                _set('svcRateKRW', data.rates.KRW || '');
                _set('svcRateJPY', data.rates.JPY || '');
                _set('svcRateCNY', data.rates.CNY || '');
                _set('svcRateEUR', data.rates.EUR || '');
                var rateStatusEl = document.getElementById('rateStatus');
                if (rateStatusEl) rateStatusEl.innerHTML =
                    '기준일: ' + data.date + ' · <a href="https://frankfurter.dev" target="_blank" class="text-blue-500 hover:underline">Frankfurter API</a> · <span class="text-green-600">방금 업데이트됨</span>';
                // 도메인 환율 적용 표시 업데이트
                var _curCode = '<?= $_dispCur ?>';
                _domainExRate = data.rates[_curCode] || _domainExRate;
                var exRateEl = document.getElementById('domainExRate');
                if (exRateEl) exRateEl.textContent = parseFloat(_domainExRate).toLocaleString(undefined, {minimumFractionDigits:2, maximumFractionDigits:2});
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
                showResultModal(false, data.message || <?= json_encode(__('services.admin.general.fetch_fail'), JSON_UNESCAPED_UNICODE) ?>);
            }
        })
        .catch(function(e) { showResultModal(false, <?= json_encode(__('services.admin.general.fetch_fail'), JSON_UNESCAPED_UNICODE) ?> + ': ' + e.message); })
        .finally(function() {
            if (btn) btn.disabled = false;
            if (icon) icon.classList.remove('animate-spin');
        });
}

function saveExchangeRates() {
    // 환율 변경 시 general 탭 데이터로 저장 (탭별 저장 분리 후)
    saveGeneralSettings();
}
</script>
