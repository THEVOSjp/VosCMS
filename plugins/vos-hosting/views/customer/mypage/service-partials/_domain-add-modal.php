<?php
/**
 * 도메인 추가 모달 — 도메인 옵션 선택 + PAY.JP 결제
 *
 * 호출 변수: $pdo, $prefix, $userId, $userData, $baseUrl, $servicesByType
 */

// 활성 호스팅 subscription (payment_customer_id 가져오기)
$_hostSub = null;
foreach ($servicesByType['hosting'] ?? [] as $_hs) {
    if (($_hs['status'] ?? '') === 'active') { $_hostSub = $_hs; break; }
}
$_paymentCustomerId = $_hostSub['payment_customer_id'] ?? '';
$_hostingOrderId = $_hostSub['order_id'] ?? 0;

// 도메인 settings 조회
$_dmSettings = [];
try {
    $_st = $pdo->prepare("SELECT `key`,`value` FROM {$prefix}settings WHERE `key` IN ('service_domain_pricing','service_free_domains','service_blocked_subdomains','service_currency')");
    $_st->execute();
    while ($_r = $_st->fetch(PDO::FETCH_ASSOC)) $_dmSettings[$_r['key']] = $_r['value'];
} catch (\Throwable $e) {}
$_domainPricing = json_decode($_dmSettings['service_domain_pricing'] ?? '[]', true) ?: [];
$_freeZones = json_decode($_dmSettings['service_free_domains'] ?? '[]', true) ?: [];
$_blockedSubs = json_decode($_dmSettings['service_blocked_subdomains'] ?? '[]', true) ?: [];
$_currency = $_dmSettings['service_currency'] ?? 'JPY';
$_curSym = ['JPY' => '¥', 'KRW' => '원', 'USD' => '$', 'CNY' => '¥', 'EUR' => '€'][$_currency] ?? '';

// PAY.JP 공개키
$_payjpPubKey = $_ENV['PAYJP_PUBLIC_KEY'] ?? '';
?>

<!-- 도메인 추가 모달 -->
<div id="domainAddModal" class="hidden fixed inset-0 z-[100] items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="closeDomainAddModal()"></div>
    <div class="relative bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl w-full max-w-2xl max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-700 flex items-center justify-between sticky top-0 bg-white dark:bg-zinc-800 z-10">
            <h3 class="text-base font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars(__('services.detail.dm_modal_title')) ?></h3>
            <button type="button" onclick="closeDomainAddModal()" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-6 space-y-5">
            <!-- 옵션 선택 -->
            <div>
                <p class="text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-2"><?= htmlspecialchars(__('services.detail.dm_modal_option_label')) ?></p>
                <div class="grid grid-cols-3 gap-2">
                    <label class="flex items-center justify-center gap-2 p-3 border border-blue-500 bg-blue-50 dark:bg-blue-900/20 rounded-lg cursor-pointer text-xs font-medium text-blue-700 dark:text-blue-300 dm-opt-label">
                        <input type="radio" name="dmOpt" value="free" class="sr-only" checked onchange="dmSwitchOpt('free')">
                        <?= htmlspecialchars(__('services.detail.dm_modal_opt_free')) ?>
                    </label>
                    <label class="flex items-center justify-center gap-2 p-3 border border-gray-200 dark:border-zinc-600 rounded-lg cursor-pointer text-xs font-medium text-zinc-600 dark:text-zinc-300 dm-opt-label">
                        <input type="radio" name="dmOpt" value="new" class="sr-only" onchange="dmSwitchOpt('new')">
                        <?= htmlspecialchars(__('services.detail.dm_modal_opt_new')) ?>
                    </label>
                    <label class="flex items-center justify-center gap-2 p-3 border border-gray-200 dark:border-zinc-600 rounded-lg cursor-pointer text-xs font-medium text-zinc-600 dark:text-zinc-300 dm-opt-label">
                        <input type="radio" name="dmOpt" value="existing" class="sr-only" onchange="dmSwitchOpt('existing')">
                        <?= htmlspecialchars(__('services.detail.dm_modal_opt_existing')) ?>
                    </label>
                </div>
            </div>

            <!-- 무료 서브도메인 -->
            <div id="dmOptFree" class="space-y-2">
                <div class="flex items-center gap-2">
                    <div class="flex-1 flex items-center border border-gray-300 dark:border-zinc-600 rounded-lg overflow-hidden">
                        <input type="text" id="dmFreeSub" placeholder="mysite" class="flex-1 px-3 py-2 text-sm bg-white dark:bg-zinc-700 dark:text-white border-0 focus:ring-0">
                        <select id="dmFreeZone" class="px-2 py-2 text-sm text-zinc-500 dark:text-zinc-300 bg-gray-50 dark:bg-zinc-600 border-l border-gray-300 dark:border-zinc-600 focus:ring-0 border-0 font-medium">
                            <?php foreach ($_freeZones as $_z): ?>
                            <option value="<?= htmlspecialchars($_z) ?>">.<?= htmlspecialchars($_z) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <button type="button" onclick="dmCheckFreeSub()" class="px-3 py-2 bg-emerald-600 hover:bg-emerald-700 text-white text-xs font-medium rounded-lg whitespace-nowrap"><?= htmlspecialchars(__('services.order.domain.btn_check')) ?></button>
                </div>
                <p id="dmFreeResult" class="text-[11px] hidden"></p>
            </div>

            <!-- 신규 도메인 등록 -->
            <div id="dmOptNew" class="space-y-2 hidden">
                <div class="flex items-center gap-2">
                    <input type="text" id="dmNewInput" placeholder="example" class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500" onkeydown="if(event.key==='Enter')dmSearchNew()">
                    <button type="button" onclick="dmSearchNew()" class="px-3 py-2 bg-blue-600 hover:bg-blue-700 text-white text-xs font-medium rounded-lg whitespace-nowrap"><?= htmlspecialchars(__('services.order.domain.btn_search')) ?></button>
                </div>
                <div id="dmNewResults" class="space-y-1.5 max-h-48 overflow-y-auto"></div>
                <p class="text-[10px] text-zinc-400"><?= htmlspecialchars(__('services.detail.dm_modal_new_notice')) ?></p>
            </div>

            <!-- 보유 도메인 -->
            <div id="dmOptExisting" class="space-y-2 hidden">
                <input type="text" id="dmExistingInput" placeholder="example.com" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                <p class="text-[10px] text-zinc-400"><?= htmlspecialchars(__('services.detail.dm_modal_existing_notice')) ?></p>
            </div>

            <!-- 합계 -->
            <div class="bg-gray-50 dark:bg-zinc-700/50 rounded-lg p-3 space-y-1.5">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-zinc-500 dark:text-zinc-400"><?= htmlspecialchars(__('services.order.summary.subtotal')) ?></span>
                    <span id="dmSubtotal" class="text-zinc-700 dark:text-zinc-200"><?= htmlspecialchars($_curSym) ?>0</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-zinc-500 dark:text-zinc-400"><?= htmlspecialchars(__('services.order.summary.vat')) ?></span>
                    <span id="dmTax" class="text-zinc-700 dark:text-zinc-200"><?= htmlspecialchars($_curSym) ?>0</span>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-zinc-200 dark:border-zinc-600">
                    <span class="text-xs font-medium text-zinc-700 dark:text-zinc-200"><?= htmlspecialchars(__('services.detail.dm_modal_total')) ?></span>
                    <span id="dmTotal" class="text-base font-bold text-blue-600 dark:text-blue-400"><?= htmlspecialchars($_curSym) ?>0</span>
                </div>
            </div>

            <!-- 결제 정보 -->
            <div id="dmPaymentSection" class="hidden">
                <p class="text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-2"><?= htmlspecialchars(__('services.detail.dm_modal_payment')) ?></p>
                <?php if ($_paymentCustomerId): ?>
                <div id="dmCardOnFile" class="p-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg flex items-center justify-between">
                    <span class="text-xs text-emerald-700 dark:text-emerald-400">✓ <?= htmlspecialchars(__('services.mypage.addon_pay_with_card_on_file')) ?></span>
                    <button type="button" onclick="dmToggleNewCard(true)" class="text-xs text-blue-600 hover:underline whitespace-nowrap ml-2"><?= htmlspecialchars(__('services.mypage.addon_use_new_card')) ?></button>
                </div>
                <?php endif; ?>
                <div id="dmCardForm" class="<?= $_paymentCustomerId ? 'hidden' : '' ?> space-y-2 mt-2">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] text-zinc-500 dark:text-zinc-400"><?= htmlspecialchars(__('services.mypage.addon_step_card_info')) ?></p>
                        <?php if ($_paymentCustomerId): ?>
                        <button type="button" onclick="dmToggleNewCard(false)" class="text-[10px] text-zinc-500 underline hover:text-zinc-700"><?= htmlspecialchars(__('services.mypage.addon_use_saved_card')) ?></button>
                        <?php endif; ?>
                    </div>
                    <div id="dmCardErrorBanner" class="hidden p-2 bg-red-50 border border-red-200 text-red-700 text-[11px] rounded"></div>
                    <div>
                        <label class="block text-[11px] text-zinc-500 mb-1"><?= htmlspecialchars(__('services.order.payment.card_number')) ?></label>
                        <div id="dm-payjp-number" class="px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 rounded-lg"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] text-zinc-500 mb-1"><?= htmlspecialchars(__('services.order.payment.card_expiry')) ?></label>
                            <div id="dm-payjp-expiry" class="px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 rounded-lg"></div>
                        </div>
                        <div>
                            <label class="block text-[11px] text-zinc-500 mb-1"><?= htmlspecialchars(__('services.order.payment.card_cvc')) ?></label>
                            <div id="dm-payjp-cvc" class="px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 rounded-lg"></div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] text-zinc-500 mb-1"><?= htmlspecialchars(__('services.order.payment.card_holder')) ?></label>
                        <input type="text" id="dm-card-holder" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg" placeholder="HONG GILDONG">
                    </div>
                    <p id="dm-card-errors" class="hidden text-[11px] text-red-600"></p>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-zinc-700 flex items-center justify-end gap-2 sticky bottom-0 bg-white dark:bg-zinc-800">
            <button type="button" onclick="closeDomainAddModal()" class="px-4 py-2 text-xs font-medium text-zinc-600 dark:text-zinc-300 bg-gray-100 dark:bg-zinc-700 hover:bg-gray-200 rounded-lg"><?= htmlspecialchars(__('services.order.checkout.btn_cancel') ?: '취소') ?></button>
            <button type="button" id="dmPayBtn" onclick="dmSubmit()" class="px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg disabled:opacity-50">
                <span id="dmPayBtnLabel"><?= htmlspecialchars(__('services.detail.dm_modal_btn_apply')) ?></span>
            </button>
        </div>
    </div>
</div>

<!-- 도메인 모달 전용 알림 모달 (모달 위에 표시되도록 z-index 더 높게) -->
<div id="dmAlertModal" class="hidden fixed inset-0 z-[110] items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/40" onclick="dmCloseAlert()"></div>
    <div class="relative bg-white dark:bg-zinc-800 rounded-xl shadow-2xl max-w-sm w-full p-5">
        <div class="flex items-start gap-3">
            <div id="dmAlertIcon" class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            </div>
            <p id="dmAlertMsg" class="text-sm text-zinc-800 dark:text-zinc-200 whitespace-pre-line flex-1"></p>
        </div>
        <div class="mt-4 flex justify-end">
            <button type="button" onclick="dmCloseAlert()" class="px-4 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg"><?= htmlspecialchars(__('services.detail.dm_modal_alert_ok') ?: '확인') ?></button>
        </div>
    </div>
</div>

<?php if ($_payjpPubKey): ?>
<script src="https://js.pay.jp/v2/pay.js"></script>
<?php endif; ?>
<script>
(function(){
    var dmPricing = <?= json_encode($_domainPricing, JSON_UNESCAPED_UNICODE) ?>;
    var dmFreeZones = <?= json_encode($_freeZones, JSON_UNESCAPED_UNICODE) ?>;
    var dmBlocked = <?= json_encode($_blockedSubs, JSON_UNESCAPED_UNICODE) ?>;
    var dmCurSym = <?= json_encode($_curSym) ?>;
    var dmHostingSubId = <?= (int)($_hostSub['id'] ?? 0) ?>;
    var dmHasCardOnFile = <?= $_paymentCustomerId ? 'true' : 'false' ?>;
    var dmPayjpPubKey = <?= json_encode($_payjpPubKey) ?>;
    var dmCurOpt = 'free';
    var dmSelectedDomain = '';
    var dmSelectedPrice = 0;
    var dmUseNewCard = !dmHasCardOnFile;
    var dmPayjpInst = null, dmElNum = null, dmElExp = null, dmElCvc = null, dmCardReady = false;

    function fmtPrice(n) { return dmCurSym + Number(n||0).toLocaleString(); }
    function el(id) { return document.getElementById(id); }
    function showErr(msg) { var b = el('dmCardErrorBanner'); if (b) { b.textContent = msg; b.classList.remove('hidden'); } }
    function clearErr() { var b = el('dmCardErrorBanner'); if (b) { b.classList.add('hidden'); b.textContent = ''; } }

    window.dmShowAlert = function(msg, type) {
        var box = el('dmAlertModal');
        if (!box) { alert(msg); return; }
        if (box.parentElement !== document.body) document.body.appendChild(box);
        el('dmAlertMsg').textContent = msg;
        var icon = el('dmAlertIcon');
        if (icon) {
            var cls = 'flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center ';
            if (type === 'success') cls += 'bg-emerald-100 dark:bg-emerald-900/30 text-emerald-600 dark:text-emerald-400';
            else if (type === 'error') cls += 'bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400';
            else cls += 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400';
            icon.className = cls;
        }
        box.classList.remove('hidden'); box.classList.add('flex');
    };
    window.dmCloseAlert = function() {
        var box = el('dmAlertModal');
        if (box) { box.classList.add('hidden'); box.classList.remove('flex'); }
    };

    window.openDomainAddModal = function() {
        var m = el('domainAddModal');
        if (m.parentElement !== document.body) document.body.appendChild(m);
        m.classList.remove('hidden'); m.classList.add('flex');
        document.body.style.overflow = 'hidden';
        if (dmUseNewCard && dmPayjpPubKey) initPayjp();
    };
    window.closeDomainAddModal = function() {
        var m = el('domainAddModal');
        m.classList.add('hidden'); m.classList.remove('flex');
        document.body.style.overflow = '';
    };

    window.dmSwitchOpt = function(opt) {
        dmCurOpt = opt;
        document.querySelectorAll('.dm-opt-label').forEach(function(lbl) {
            var inp = lbl.querySelector('input[name="dmOpt"]');
            if (inp && inp.value === opt) {
                lbl.className = 'flex items-center justify-center gap-2 p-3 border border-blue-500 bg-blue-50 dark:bg-blue-900/20 rounded-lg cursor-pointer text-xs font-medium text-blue-700 dark:text-blue-300 dm-opt-label';
            } else {
                lbl.className = 'flex items-center justify-center gap-2 p-3 border border-gray-200 dark:border-zinc-600 rounded-lg cursor-pointer text-xs font-medium text-zinc-600 dark:text-zinc-300 dm-opt-label';
            }
        });
        ['Free','New','Existing'].forEach(function(s){
            var box = el('dmOpt'+s);
            if (box) box.classList.toggle('hidden', s.toLowerCase() !== opt);
        });
        // 옵션 변경 시 선택 초기화
        dmSelectedDomain = ''; dmSelectedPrice = 0;
        updateTotal();
    };

    function updateTotal() {
        var sub = dmSelectedPrice;
        var tax = Math.round(sub * 0.1);
        var grand = sub + tax;
        if (el('dmSubtotal')) el('dmSubtotal').textContent = fmtPrice(sub);
        if (el('dmTax')) el('dmTax').textContent = fmtPrice(tax);
        el('dmTotal').textContent = fmtPrice(grand);
        var ps = el('dmPaymentSection');
        if (ps) ps.classList.toggle('hidden', grand <= 0);
    }

    window.dmCheckFreeSub = function() {
        var sub = (el('dmFreeSub').value || '').trim().toLowerCase();
        var zone = el('dmFreeZone').value;
        var res = el('dmFreeResult');
        res.classList.remove('hidden');
        if (!/^[a-z0-9]([a-z0-9-]{1,30}[a-z0-9])?$/.test(sub)) {
            res.className = 'text-[11px] text-red-600'; res.textContent = '✗ ' + <?= json_encode(__('services.detail.dm_modal_invalid_subdomain'), JSON_UNESCAPED_UNICODE) ?>;
            dmSelectedDomain = ''; dmSelectedPrice = 0; updateTotal(); return;
        }
        var blocked = dmBlocked.some(function(b){ return b.replace(/\*/g,'').toLowerCase() === sub; });
        if (blocked) {
            res.className = 'text-[11px] text-red-600'; res.textContent = '✗ ' + <?= json_encode(__('services.detail.dm_modal_blocked_subdomain'), JSON_UNESCAPED_UNICODE) ?>;
            dmSelectedDomain = ''; dmSelectedPrice = 0; updateTotal(); return;
        }
        var fqdn = sub + '.' + zone;
        // 서버 가용성 검사 (api/domain-check 의 sub 모드)
        res.className = 'text-[11px] text-zinc-400'; res.textContent = '⏳ ...';
        fetch(siteBaseUrl + '/plugins/vos-hosting/api/domain-check.php?subdomain=' + encodeURIComponent(sub) + '&zone=' + encodeURIComponent(zone))
            .then(function(r){return r.json();})
            .then(function(d){
                if (d && d.available === true) {
                    res.className = 'text-[11px] text-emerald-600'; res.textContent = '✓ ' + fqdn;
                    dmSelectedDomain = fqdn; dmSelectedPrice = 0; updateTotal();
                } else {
                    res.className = 'text-[11px] text-red-600'; res.textContent = '✗ ' + (d.message || <?= json_encode(__('services.detail.dm_modal_subdomain_taken'), JSON_UNESCAPED_UNICODE) ?>);
                    dmSelectedDomain = ''; dmSelectedPrice = 0; updateTotal();
                }
            })
            .catch(function(){
                // sub mode 미지원이면 일단 fqdn 그대로 신청 허용
                res.className = 'text-[11px] text-emerald-600'; res.textContent = '✓ ' + fqdn;
                dmSelectedDomain = fqdn; dmSelectedPrice = 0; updateTotal();
            });
    };

    window.dmSearchNew = function() {
        var input = (el('dmNewInput').value || '').trim().toLowerCase().replace(/\.[a-z.]+$/i, '');
        var box = el('dmNewResults'); box.innerHTML = '';
        if (!input || input.length < 2) { box.innerHTML = '<p class="text-[11px] text-red-600">' + <?= json_encode(__('services.order.domain.alert_min_2chars') ?: 'min 2 chars', JSON_UNESCAPED_UNICODE) ?> + '</p>'; return; }
        box.innerHTML = '<p class="text-[11px] text-zinc-400">⏳ ...</p>';
        fetch(siteBaseUrl + '/plugins/vos-hosting/api/domain-check.php?domain=' + encodeURIComponent(input))
            .then(function(r){return r.json();})
            .then(function(data){
                box.innerHTML = '';
                if (!data.success) { box.innerHTML = '<p class="text-[11px] text-red-600">' + (data.message || 'error') + '</p>'; return; }
                data.results.forEach(function(d){
                    var basePrice = d.vip_price || d.price || 0;
                    if (d.available === true && basePrice > 0) {
                        var div = document.createElement('label');
                        div.className = 'flex items-center justify-between p-2 border border-gray-200 dark:border-zinc-600 rounded cursor-pointer hover:border-blue-400';
                        div.innerHTML = '<div class="flex items-center gap-2"><input type="radio" name="dmNewSel" data-fqdn="' + d.fqdn + '" data-price="' + basePrice + '" class="w-3.5 h-3.5 text-blue-600"><div><p class="text-xs font-mono">' + d.fqdn + '</p><p class="text-[10px] text-emerald-600">' + <?= json_encode(__('services.order.domain.status_available'), JSON_UNESCAPED_UNICODE) ?> + '</p></div></div><p class="text-xs font-bold text-blue-600">' + fmtPrice(basePrice) + '<span class="text-[10px] font-normal text-zinc-400 ml-1">/y</span></p>';
                        var inp = div.querySelector('input');
                        inp.addEventListener('change', function(){
                            dmSelectedDomain = this.dataset.fqdn;
                            dmSelectedPrice = parseInt(this.dataset.price, 10) || 0;
                            updateTotal();
                        });
                        box.appendChild(div);
                    } else if (d.available === false) {
                        var x = document.createElement('div');
                        x.className = 'flex items-center justify-between p-2 bg-gray-50 dark:bg-zinc-700/30 rounded';
                        x.innerHTML = '<p class="text-xs font-mono text-zinc-400 line-through">' + d.fqdn + '</p><p class="text-[10px] text-red-400">' + <?= json_encode(__('services.order.domain.status_taken'), JSON_UNESCAPED_UNICODE) ?> + '</p>';
                        box.appendChild(x);
                    }
                });
            })
            .catch(function(e){ box.innerHTML = '<p class="text-[11px] text-red-600">' + (e && e.message || 'error') + '</p>'; });
    };

    // 보유 도메인 입력 즉시 반영
    document.addEventListener('input', function(e){
        if (e.target && e.target.id === 'dmExistingInput') {
            var v = (e.target.value || '').trim().toLowerCase();
            if (/^[a-z0-9]([a-z0-9.-]*[a-z0-9])?\.[a-z]{2,}$/i.test(v)) {
                dmSelectedDomain = v; dmSelectedPrice = 0; updateTotal();
            } else {
                dmSelectedDomain = ''; dmSelectedPrice = 0; updateTotal();
            }
        }
    });

    function initPayjp() {
        if (!dmPayjpPubKey || typeof Payjp === 'undefined') return;
        if (dmPayjpInst) return;
        dmPayjpInst = Payjp(dmPayjpPubKey);
        var els = dmPayjpInst.elements();
        var style = { base: { fontSize: '14px' } };
        dmElNum = els.create('cardNumber', { style: style });
        dmElExp = els.create('cardExpiry', { style: style });
        dmElCvc = els.create('cardCvc', { style: style });
        dmElNum.mount('#dm-payjp-number');
        dmElExp.mount('#dm-payjp-expiry');
        dmElCvc.mount('#dm-payjp-cvc');
    }

    window.dmToggleNewCard = function(useNew) {
        dmUseNewCard = useNew;
        var f = el('dmCardForm'), c = el('dmCardOnFile');
        if (useNew) { f.classList.remove('hidden'); if (c) c.classList.add('hidden'); initPayjp(); }
        else { f.classList.add('hidden'); if (c) c.classList.remove('hidden'); }
    };

    window.dmSubmit = function() {
        clearErr();
        if (!dmSelectedDomain) {
            dmShowAlert(<?= json_encode(__('services.detail.dm_modal_select_first'), JSON_UNESCAPED_UNICODE) ?>, 'warning');
            return;
        }
        if (!dmHostingSubId) {
            dmShowAlert(<?= json_encode(__('services.detail.dm_modal_no_hosting'), JSON_UNESCAPED_UNICODE) ?>, 'error');
            return;
        }
        var btn = el('dmPayBtn');
        var lbl = el('dmPayBtnLabel');
        var origLbl = lbl.textContent;
        btn.disabled = true; lbl.textContent = '...';

        var payload = {
            hosting_subscription_id: dmHostingSubId,
            domain_option: dmCurOpt,
            domain: dmSelectedDomain,
            unit_price: dmSelectedPrice
        };

        function send() {
            return serviceAction('pay_domain', payload).then(function(d){
                if (d.success) {
                    dmShowAlert(<?= json_encode(__('services.detail.dm_modal_alert_done'), JSON_UNESCAPED_UNICODE) ?>, 'success');
                    setTimeout(function(){ closeDomainAddModal(); location.reload(); }, 1500);
                    return;
                }
                btn.disabled = false; lbl.textContent = origLbl;
                if (d.card_error) {
                    if (dmHasCardOnFile && !dmUseNewCard) dmToggleNewCard(true);
                    showErr(d.message || 'card error');
                } else {
                    dmShowAlert(d.message || <?= json_encode(__('services.detail.alert_failed'), JSON_UNESCAPED_UNICODE) ?>, 'error');
                }
            }).catch(function(e){
                btn.disabled = false; lbl.textContent = origLbl;
                dmShowAlert(e && e.message ? e.message : <?= json_encode(__('services.detail.alert_failed'), JSON_UNESCAPED_UNICODE) ?>, 'error');
            });
        }

        if (dmSelectedPrice > 0 && dmUseNewCard) {
            if (!dmPayjpInst) {
                dmShowAlert(<?= json_encode(__('services.order.payment.not_configured'), JSON_UNESCAPED_UNICODE) ?>, 'error');
                btn.disabled = false; lbl.textContent = origLbl; return;
            }
            var holderEl = el('dm-card-holder');
            var holder = holderEl ? holderEl.value.trim().toUpperCase() : '';
            if (!holder) {
                dmShowAlert(<?= json_encode(__('services.order.payment.card_holder_required'), JSON_UNESCAPED_UNICODE) ?>, 'warning');
                btn.disabled = false; lbl.textContent = origLbl;
                if (holderEl) holderEl.focus();
                return;
            }
            dmPayjpInst.createToken(dmElNum).then(function(r){
                if (r.error) throw new Error(r.error.message);
                if (!r.id) throw new Error(<?= json_encode(__('services.order.payment.token_failed'), JSON_UNESCAPED_UNICODE) ?>);
                payload.card_token = r.id;
                payload.card_holder = holder;
                return send();
            }).catch(function(e){
                btn.disabled = false; lbl.textContent = origLbl;
                dmShowAlert(e && e.message ? e.message : <?= json_encode(__('services.detail.alert_failed'), JSON_UNESCAPED_UNICODE) ?>, 'error');
            });
        } else {
            send();
        }
    };
})();
</script>
