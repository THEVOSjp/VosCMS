<?php
/**
 * 부가서비스 결제 모달 — 가격 있는 recurring 부가서비스 신청 시 (예: 기술 지원 1년)
 * 호출 변수: $_hostSub, $_payPubKey, $_paymentCustomerId, $_curr, $_curSym
 */
?>
<div id="addonPayModal" class="hidden fixed inset-0 z-[100] items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="closeAddonPayModal()"></div>
    <div class="relative bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl w-full max-w-lg max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-700 flex items-center justify-between sticky top-0 bg-white dark:bg-zinc-800 z-10">
            <h3 id="addonPayTitle" class="text-base font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars(__('services.detail.addon_pay_title')) ?></h3>
            <button type="button" onclick="closeAddonPayModal()" class="text-zinc-400 hover:text-zinc-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-6 space-y-4">
            <div class="bg-gray-50 dark:bg-zinc-700/50 rounded-lg p-3">
                <p id="addonPayLabel" class="text-sm font-medium text-zinc-900 dark:text-white"></p>
                <p id="addonPayUnit" class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5"></p>
            </div>
            <!-- 합계 -->
            <div class="bg-blue-50 dark:bg-blue-900/20 rounded-lg p-3 space-y-1.5 border border-blue-200 dark:border-blue-800">
                <div class="flex items-center justify-between text-xs">
                    <span class="text-zinc-600 dark:text-zinc-300"><?= htmlspecialchars(__('services.order.summary.subtotal')) ?></span>
                    <span id="addonPaySubtotal" class="text-zinc-700 dark:text-zinc-200"><?= htmlspecialchars($_curSym) ?>0</span>
                </div>
                <div class="flex items-center justify-between text-xs">
                    <span class="text-zinc-600 dark:text-zinc-300"><?= htmlspecialchars(__('services.order.summary.vat')) ?></span>
                    <span id="addonPayTax" class="text-zinc-700 dark:text-zinc-200"><?= htmlspecialchars($_curSym) ?>0</span>
                </div>
                <div class="flex items-center justify-between pt-2 border-t border-blue-200 dark:border-blue-800">
                    <span class="text-xs font-medium"><?= htmlspecialchars(__('services.detail.dm_modal_total')) ?></span>
                    <span id="addonPayTotal" class="text-base font-bold text-blue-700 dark:text-blue-300"><?= htmlspecialchars($_curSym) ?>0</span>
                </div>
            </div>
            <!-- 결제 정보 -->
            <div>
                <p class="text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-2"><?= htmlspecialchars(__('services.detail.dm_modal_payment')) ?></p>
                <?php if ($_paymentCustomerId): ?>
                <div id="addonPayCardOnFile" class="p-3 bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800 rounded-lg flex items-center justify-between">
                    <span class="text-xs text-emerald-700 dark:text-emerald-400">✓ <?= htmlspecialchars(__('services.mypage.addon_pay_with_card_on_file')) ?></span>
                    <button type="button" onclick="addonPayToggleNewCard(true)" class="text-xs text-blue-600 hover:underline whitespace-nowrap ml-2"><?= htmlspecialchars(__('services.mypage.addon_use_new_card')) ?></button>
                </div>
                <?php endif; ?>
                <div id="addonPayCardForm" class="<?= $_paymentCustomerId ? 'hidden' : '' ?> space-y-2 mt-2">
                    <div class="flex items-center justify-between">
                        <p class="text-[10px] text-zinc-500 dark:text-zinc-400"><?= htmlspecialchars(__('services.mypage.addon_step_card_info')) ?></p>
                        <?php if ($_paymentCustomerId): ?>
                        <button type="button" onclick="addonPayToggleNewCard(false)" class="text-[10px] text-zinc-500 underline hover:text-zinc-700"><?= htmlspecialchars(__('services.mypage.addon_use_saved_card')) ?></button>
                        <?php endif; ?>
                    </div>
                    <div id="addonPayErrorBanner" class="hidden p-2 bg-red-50 border border-red-200 text-red-700 text-[11px] rounded"></div>
                    <div>
                        <label class="block text-[11px] text-zinc-500 mb-1"><?= htmlspecialchars(__('services.order.payment.card_number')) ?></label>
                        <div id="addonPay-payjp-number" class="px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 rounded-lg"></div>
                    </div>
                    <div class="grid grid-cols-2 gap-2">
                        <div>
                            <label class="block text-[11px] text-zinc-500 mb-1"><?= htmlspecialchars(__('services.order.payment.card_expiry')) ?></label>
                            <div id="addonPay-payjp-expiry" class="px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 rounded-lg"></div>
                        </div>
                        <div>
                            <label class="block text-[11px] text-zinc-500 mb-1"><?= htmlspecialchars(__('services.order.payment.card_cvc')) ?></label>
                            <div id="addonPay-payjp-cvc" class="px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 rounded-lg"></div>
                        </div>
                    </div>
                    <div>
                        <label class="block text-[11px] text-zinc-500 mb-1"><?= htmlspecialchars(__('services.order.payment.card_holder')) ?></label>
                        <input type="text" id="addonPay-card-holder" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg" placeholder="HONG GILDONG">
                    </div>
                </div>
            </div>
        </div>
        <div class="px-6 py-4 border-t border-gray-200 dark:border-zinc-700 flex items-center justify-end gap-2 sticky bottom-0 bg-white dark:bg-zinc-800">
            <button type="button" onclick="closeAddonPayModal()" class="px-4 py-2 text-xs font-medium text-zinc-600 dark:text-zinc-300 bg-gray-100 dark:bg-zinc-700 hover:bg-gray-200 rounded-lg"><?= htmlspecialchars(__('services.order.checkout.btn_cancel')) ?></button>
            <button type="button" id="addonPayBtn2" onclick="addonPaySubmit()" class="px-5 py-2 text-xs font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg disabled:opacity-50">
                <span id="addonPayBtnLabel2"><?= htmlspecialchars(__('services.detail.dm_modal_btn_apply')) ?></span>
            </button>
        </div>
    </div>
</div>

<script>
(function(){
    var apHostingSubId = <?= (int)($_hostSub['id'] ?? 0) ?>;
    var apHasCardOnFile = <?= $_paymentCustomerId ? 'true' : 'false' ?>;
    var apPubKey = <?= json_encode($_payPubKey) ?>;
    var apCurSym = <?= json_encode($_curSym) ?>;
    var apCurrent = null;  // {addonId, label, unitPrice}
    var apUseNewCard = !apHasCardOnFile;
    var apPayjpInst = null, apElNum = null, apElExp = null, apElCvc = null;

    function el(id) { return document.getElementById(id); }
    function fmt(n) { return apCurSym + Number(n||0).toLocaleString(); }

    function initPayjp() {
        if (!apPubKey || typeof Payjp === 'undefined' || apPayjpInst) return;
        apPayjpInst = Payjp(apPubKey);
        var els = apPayjpInst.elements();
        var style = { base: { fontSize: '14px' } };
        apElNum = els.create('cardNumber', { style: style });
        apElExp = els.create('cardExpiry', { style: style });
        apElCvc = els.create('cardCvc', { style: style });
        apElNum.mount('#addonPay-payjp-number');
        apElExp.mount('#addonPay-payjp-expiry');
        apElCvc.mount('#addonPay-payjp-cvc');
    }

    window.openAddonPayModal = function(addonId, label, unitPrice, unitText) {
        apCurrent = { addonId: addonId, label: label, unitPrice: parseInt(unitPrice, 10) || 0 };
        el('addonPayLabel').textContent = label;
        el('addonPayUnit').textContent = unitText || '';
        var sub = apCurrent.unitPrice;
        var tax = Math.round(sub * 0.1);
        var grand = sub + tax;
        el('addonPaySubtotal').textContent = fmt(sub);
        el('addonPayTax').textContent = fmt(tax);
        el('addonPayTotal').textContent = fmt(grand);

        var m = el('addonPayModal');
        if (m.parentElement !== document.body) document.body.appendChild(m);
        m.classList.remove('hidden'); m.classList.add('flex');
        document.body.style.overflow = 'hidden';
        if (apUseNewCard) initPayjp();
    };
    window.closeAddonPayModal = function() {
        var m = el('addonPayModal');
        m.classList.add('hidden'); m.classList.remove('flex');
        document.body.style.overflow = '';
    };
    window.addonPayToggleNewCard = function(useNew) {
        apUseNewCard = useNew;
        var f = el('addonPayCardForm'), c = el('addonPayCardOnFile');
        if (useNew) { f.classList.remove('hidden'); if (c) c.classList.add('hidden'); initPayjp(); }
        else { f.classList.add('hidden'); if (c) c.classList.remove('hidden'); }
    };
    function showErr(msg) { var b = el('addonPayErrorBanner'); if (b) { b.textContent = msg; b.classList.remove('hidden'); } }
    function clearErr() { var b = el('addonPayErrorBanner'); if (b) { b.classList.add('hidden'); b.textContent = ''; } }

    window.addonPaySubmit = function() {
        clearErr();
        if (!apCurrent || !apHostingSubId) return;
        var btn = el('addonPayBtn2');
        var lbl = el('addonPayBtnLabel2');
        var origLbl = lbl.textContent;
        btn.disabled = true; lbl.textContent = '...';

        var payload = {
            host_subscription_id: apHostingSubId,
            addon_id: apCurrent.addonId,
            label: apCurrent.label,
            unit_price: apCurrent.unitPrice,
        };

        function send() {
            return serviceAction('pay_addon_recurring', payload).then(function(d) {
                if (d.success) {
                    alert(<?= json_encode(__('services.detail.addon_pay_done'), JSON_UNESCAPED_UNICODE) ?>);
                    closeAddonPayModal();
                    location.reload();
                    return;
                }
                btn.disabled = false; lbl.textContent = origLbl;
                if (d.card_error) {
                    if (apHasCardOnFile && !apUseNewCard) addonPayToggleNewCard(true);
                    showErr(d.message || 'card error');
                } else {
                    alert(d.message || <?= json_encode(__('services.detail.alert_failed'), JSON_UNESCAPED_UNICODE) ?>);
                }
            }).catch(function(e) {
                btn.disabled = false; lbl.textContent = origLbl;
                alert(e && e.message ? e.message : 'error');
            });
        }

        if (apUseNewCard) {
            if (!apPayjpInst) {
                alert(<?= json_encode(__('services.order.payment.not_configured'), JSON_UNESCAPED_UNICODE) ?>);
                btn.disabled = false; lbl.textContent = origLbl; return;
            }
            var holder = (el('addonPay-card-holder').value || '').trim().toUpperCase();
            if (!holder) {
                alert(<?= json_encode(__('services.order.payment.card_holder_required'), JSON_UNESCAPED_UNICODE) ?>);
                btn.disabled = false; lbl.textContent = origLbl; return;
            }
            apPayjpInst.createToken(apElNum).then(function(r) {
                if (r.error) throw new Error(r.error.message);
                if (!r.id) throw new Error(<?= json_encode(__('services.order.payment.token_failed'), JSON_UNESCAPED_UNICODE) ?>);
                payload.card_token = r.id;
                payload.card_holder = holder;
                return send();
            }).catch(function(e) {
                btn.disabled = false; lbl.textContent = origLbl;
                alert(e && e.message ? e.message : 'error');
            });
        } else {
            send();
        }
    };
})();
</script>
