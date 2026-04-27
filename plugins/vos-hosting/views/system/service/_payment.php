<section class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-gray-200 dark:border-zinc-700 overflow-hidden">
    <div class="px-6 py-4 bg-gray-50 dark:bg-zinc-800/50 border-b border-gray-200 dark:border-zinc-700">
        <div class="flex items-center gap-2">
            <span class="w-7 h-7 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-bold">5</span>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white"><?= htmlspecialchars(__('services.order.payment.title')) ?></h2>
        </div>
    </div>
    <div class="p-6">
        <?php if (!$_payEnabled || !$_payPubKey): ?>
        <!-- 결제 미설정 -->
        <div class="p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl">
            <p class="text-sm text-amber-800 dark:text-amber-200"><?= htmlspecialchars(__('services.order.payment.not_configured')) ?></p>
        </div>
        <?php else: ?>
        <!-- 결제 방법 선택 -->
        <div class="grid grid-cols-2 gap-3 mb-4">
            <label class="payment-option selected cursor-pointer border-2 border-blue-500 rounded-xl p-4 text-center bg-blue-50 dark:bg-blue-900/30" onclick="selectPayment('card')">
                <input type="radio" name="payment" value="card" class="hidden" checked>
                <svg class="w-6 h-6 mx-auto mb-1 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                <p class="text-sm font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars(__('services.order.payment.method_card')) ?></p>
            </label>
            <label class="payment-option cursor-pointer border-2 border-gray-200 dark:border-zinc-600 rounded-xl p-4 text-center hover:border-blue-400 transition" onclick="selectPayment('bank')">
                <input type="radio" name="payment" value="bank" class="hidden">
                <svg class="w-6 h-6 mx-auto mb-1 text-gray-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                <p class="text-sm font-semibold text-gray-900 dark:text-white"><?= htmlspecialchars(__('services.order.payment.method_bank')) ?></p>
            </label>
        </div>

        <!-- 카드 결제 폼 -->
        <div id="paymentCardForm" class="mt-4">
            <?php if ($_payGateway === 'payjp'): ?>
            <!-- PAY.JP -->
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1"><?= htmlspecialchars(__('services.order.payment.card_number')) ?></label>
                    <div id="payjp-card-number" class="px-4 py-3 border border-gray-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 min-h-[44px]"></div>
                </div>
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1"><?= htmlspecialchars(__('services.order.payment.card_expiry')) ?></label>
                        <div id="payjp-card-expiry" class="px-4 py-3 border border-gray-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 min-h-[44px]"></div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">CVC</label>
                        <div id="payjp-card-cvc" class="px-4 py-3 border border-gray-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 min-h-[44px]"></div>
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1"><?= htmlspecialchars(__('services.order.payment.card_holder')) ?></label>
                    <input type="text" id="card-holder" placeholder="TARO YAMADA" class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 dark:text-white text-sm uppercase" autocomplete="cc-name">
                </div>
                <div id="card-errors" class="text-sm text-red-600 hidden"></div>
            </div>
            <script src="https://js.pay.jp/v2/pay.js"></script>
            <script>
            var payjp = Payjp('<?= htmlspecialchars($_payPubKey) ?>');
            var elements = payjp.elements();
            var style = { base: { fontSize: '16px', color: '<?= isset($_COOKIE['darkMode']) ? '#fff' : '#333' ?>' } };
            var cardNumber = elements.create('cardNumber', { style: style });
            var cardExpiry = elements.create('cardExpiry', { style: style });
            var cardCvc = elements.create('cardCvc', { style: style });
            cardNumber.mount('#payjp-card-number');
            cardExpiry.mount('#payjp-card-expiry');
            cardCvc.mount('#payjp-card-cvc');

            var _cardReady = { number: false, expiry: false, cvc: false };
            function updateSubmitButton() {
                var btn = document.getElementById('btnSubmitOrder');
                var agree = document.getElementById('agreeTerms');
                var payment = document.querySelector('input[name="payment"]:checked')?.value;
                var paymentSection = document.querySelector('input[name="payment"]')?.closest('section');
                var isFree = paymentSection && paymentSection.classList.contains('hidden');
                if (!btn) return;
                if (isFree) {
                    // 무료: 약관 동의만
                    btn.disabled = !(agree && agree.checked);
                } else if (payment === 'bank') {
                    btn.disabled = !(agree && agree.checked);
                } else {
                    btn.disabled = !(_cardReady.number && _cardReady.expiry && _cardReady.cvc && agree && agree.checked);
                }
            }
            cardNumber.on('change', function(e) {
                _cardReady.number = e.complete || false;
                var err = document.getElementById('card-errors');
                if (e.error) { err.textContent = e.error.message; err.classList.remove('hidden'); }
                else { err.classList.add('hidden'); }
                updateSubmitButton();
            });
            cardExpiry.on('change', function(e) { _cardReady.expiry = e.complete || false; updateSubmitButton(); });
            cardCvc.on('change', function(e) { _cardReady.cvc = e.complete || false; updateSubmitButton(); });

            function createPayjpToken() {
                if (!_cardReady.number || !_cardReady.expiry || !_cardReady.cvc) {
                    return Promise.reject(new Error(<?= json_encode(__('services.order.payment.card_error_incomplete'), JSON_UNESCAPED_UNICODE) ?>));
                }
                return payjp.createToken(cardNumber).then(function(r) {
                    if (r.error) throw new Error(r.error.message);
                    if (!r.id) throw new Error(<?= json_encode(__('services.order.payment.token_failed'), JSON_UNESCAPED_UNICODE) ?>);
                    return r.id;
                });
            }
            </script>

            <?php elseif ($_payGateway === 'stripe'): ?>
            <!-- Stripe -->
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1"><?= htmlspecialchars(__('services.order.payment.card_info')) ?></label>
                    <div id="stripe-card-element" class="px-4 py-3 border border-gray-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 min-h-[44px]"></div>
                </div>
                <div id="card-errors" class="text-sm text-red-600 hidden"></div>
            </div>
            <script src="https://js.stripe.com/v3/"></script>
            <script>
            var stripe = Stripe('<?= htmlspecialchars($_payPubKey) ?>');
            var stripeElements = stripe.elements();
            var cardElement = stripeElements.create('card', {
                style: { base: { fontSize: '16px', color: '<?= isset($_COOKIE['darkMode']) ? '#fff' : '#333' ?>' } }
            });
            cardElement.mount('#stripe-card-element');
            cardElement.on('change', function(e) {
                var err = document.getElementById('card-errors');
                if (e.error) { err.textContent = e.error.message; err.classList.remove('hidden'); }
                else { err.classList.add('hidden'); }
            });

            function createStripeToken() {
                return stripe.createToken(cardElement).then(function(r) {
                    if (r.error) throw new Error(r.error.message);
                    return r.token.id;
                });
            }
            </script>

            <?php endif; ?>
        </div>

        <!-- 계좌이체 안내 -->
        <div id="paymentBankForm" class="mt-4 hidden">
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl">
                <p class="text-sm font-medium text-blue-800 dark:text-blue-200 mb-2"><?= htmlspecialchars(__('services.order.payment.bank_title')) ?></p>
                <p class="text-xs text-blue-600 dark:text-blue-300"><?= htmlspecialchars(__('services.order.payment.bank_desc')) ?></p>
            </div>
        </div>

        <?php if ($_payTestMode): ?>
        <div class="mt-3 p-2 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
            <p class="text-[10px] text-amber-700 dark:text-amber-300 text-center"><?= htmlspecialchars(__('services.order.payment.test_mode')) ?></p>
        </div>
        <?php endif; ?>

        <?php endif; ?>
    </div>
</section>

<!-- 약관 동의 (결제 섹션과 독립 — 무료 주문에서도 표시) -->
<div class="mt-4 flex items-start gap-2 px-6">
    <input type="checkbox" id="agreeTerms" name="agree_terms" class="mt-1 text-blue-600 rounded" onchange="if(typeof updateSubmitButton==='function')updateSubmitButton()">
    <p class="text-xs text-gray-500 dark:text-zinc-400"><?php
        $_termsLink = '<a href="' . htmlspecialchars($baseUrl) . '/terms" class="text-blue-600 hover:underline">' . htmlspecialchars(__('services.order.payment.terms_link')) . '</a>';
        $_privacyLink = '<a href="' . htmlspecialchars($baseUrl) . '/privacy" class="text-blue-600 hover:underline">' . htmlspecialchars(__('services.order.payment.privacy_link')) . '</a>';
        echo str_replace([':terms', ':privacy'], [$_termsLink, $_privacyLink], htmlspecialchars(__('services.order.payment.terms_agree')));
    ?></p>
</div>

<script>
function selectPayment(type) {
    document.querySelectorAll('.payment-option').forEach(function(el) {
        el.classList.remove('selected', 'border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/30');
        el.classList.add('border-gray-200', 'dark:border-zinc-600');
    });
    var selected = document.querySelector('input[name="payment"][value="' + type + '"]');
    if (selected) {
        selected.checked = true;
        var label = selected.closest('label');
        label.classList.add('selected', 'border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/30');
        label.classList.remove('border-gray-200', 'dark:border-zinc-600');
    }
    var cardForm = document.getElementById('paymentCardForm');
    var bankForm = document.getElementById('paymentBankForm');
    if (cardForm) cardForm.classList.toggle('hidden', type !== 'card');
    if (bankForm) bankForm.classList.toggle('hidden', type !== 'bank');
    if (typeof updateSubmitButton === 'function') updateSubmitButton();
}
</script>
