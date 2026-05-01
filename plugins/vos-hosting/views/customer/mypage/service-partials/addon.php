<?php
/**
 * 마이페이지 서비스 관리 — 부가서비스 탭
 */

// 호스팅 구독
$_hostSub = $servicesByType['hosting'][0] ?? null;

// 추가 용량 옵션 (rzx_settings 의 service_hosting_storage) — 모든 호스팅에 표시
$_storageOptions = [];
$_payPubKey = '';
$_payGateway = '';
$_payEnabled = false;
if ($_hostSub) {
    try {
        $_pfx = $_ENV['DB_PREFIX'] ?? 'rzx_';
        $_stSt = $pdo->prepare("SELECT `value` FROM {$_pfx}settings WHERE `key` = 'service_hosting_storage' LIMIT 1");
        $_stSt->execute();
        $_storageOptions = json_decode($_stSt->fetchColumn() ?: '[]', true) ?: [];

        // 결제 설정 (카드 미등록 고객용 — 인라인 카드 폼)
        $_payCfgSt = $pdo->prepare("SELECT `value` FROM {$_pfx}settings WHERE `key` = 'payment_config' LIMIT 1");
        $_payCfgSt->execute();
        $_payConf = json_decode($_payCfgSt->fetchColumn() ?: '{}', true) ?: [];
        $_payGateway = $_payConf['gateway'] ?? '';
        $_payGwConf = ($_payConf['gateways'] ?? [])[$_payGateway] ?? [];
        $_payPubKey = $_payGwConf['public_key'] ?? $_payConf['public_key'] ?? '';
        $_payEnabled = !empty($_payConf['enabled']) && $_payPubKey !== '';
    } catch (\Throwable $e) { /* silent */ }
}
$_curr = $_hostSub['currency'] ?? 'JPY';
$_curSym = ['KRW'=>'₩','USD'=>'$','JPY'=>'¥','CNY'=>'¥','EUR'=>'€'][$_curr] ?? $_curr;

// 호스팅 사이트 URL 추출 (관리자/홈 링크용)
$_hostMeta = $_hostSub ? (json_decode($_hostSub['metadata'] ?? '{}', true) ?: []) : [];
$_primaryDomain = $_hostMeta['primary_domain'] ?? ($_hostMeta['domains'][0] ?? '');
if (!$_primaryDomain && !empty($order['domain'])) $_primaryDomain = $order['domain'];
$_siteUrl = $_primaryDomain ? ('https://' . $_primaryDomain) : '';

// Calendar 일할 계산: 첫 달 일할 + 정상 N개월 (호스팅 만료일까지)
$_nowTs = time();
$_daysInMonth = (int)date('t', $_nowTs);
$_dayOfMonth = (int)date('j', $_nowTs);
$_firstMonthDays = max(1, $_daysInMonth - $_dayOfMonth + 1);
$_normalMonths = 0;
if ($_hostSub && !empty($_hostSub['expires_at'])) {
    $_billingStartTs = strtotime('first day of next month', $_nowTs);
    $_expiresTs = strtotime($_hostSub['expires_at']);
    $_normalMonths = max(0,
        ((int)date('Y', $_expiresTs) - (int)date('Y', $_billingStartTs)) * 12
        + ((int)date('n', $_expiresTs) - (int)date('n', $_billingStartTs))
        + 1
    );
}
$_remainMonths = $_normalMonths + ($_firstMonthDays > 0 ? 1 : 0); // 표시용 총 개월 (첫 달 포함)
$_paymentCustomerId = $_hostSub['payment_customer_id'] ?? null;
$_needCard = !$_paymentCustomerId && $_payEnabled && $_payGateway === 'payjp';
?>
<div class="space-y-3">
    <!-- 웹 용량 추가 버튼 (시스템 도메인 외) -->
    <?php if (!empty($_storageOptions)): ?>
    <div class="flex items-center justify-between bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 px-5 py-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
            </div>
            <div>
                <p class="text-sm font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars(__('services.mypage.addon_storage_title')) ?></p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= htmlspecialchars(__('services.mypage.addon_storage_desc')) ?></p>
            </div>
        </div>
        <button type="button" onclick="openStorageAddonModal()"
                class="px-4 py-2 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition flex-shrink-0">
            <?= htmlspecialchars(__('services.mypage.btn_add_storage')) ?>
        </button>
    </div>

    <!-- 모달 -->
    <div id="storageAddonModal" class="fixed inset-0 z-[9999] hidden items-center justify-center bg-black/50 p-4" style="top:0;left:0;right:0;bottom:0;">
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl max-w-md w-full p-6 max-h-[95vh] overflow-y-auto">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-base font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars(__('services.mypage.addon_storage_title')) ?></h3>
                <button type="button" onclick="closeStorageAddonModal()" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3 leading-relaxed">
                <?= htmlspecialchars(__('services.mypage.addon_storage_modal_desc')) ?>
            </p>
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/50 rounded-lg px-3 py-2 mb-4 text-xs">
                <span class="text-blue-700 dark:text-blue-400"><?= htmlspecialchars(__('services.mypage.addon_remaining_months', ['months' => $_remainMonths])) ?></span>
                <?php if ($_hostSub && !empty($_hostSub['expires_at'])): ?>
                <span class="text-blue-600 dark:text-blue-500 ml-1">(~<?= date('Y-m-d', strtotime($_hostSub['expires_at'])) ?>)</span>
                <?php endif; ?>
            </div>

            <!-- 1단계: 용량 선택 -->
            <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-2"><?= htmlspecialchars(__('services.mypage.addon_step_select_capacity')) ?></p>
            <div class="space-y-2 mb-4">
                <?php foreach ($_storageOptions as $_so):
                    $_cap = $_so['capacity'] ?? '?';
                    $_unitPrice = (int)($_so['price'] ?? 0);
                    $_firstAmt = (int)round($_unitPrice * $_firstMonthDays / 30);
                    $_normalAmt = $_unitPrice * $_normalMonths;
                    $_totalPrice = $_firstAmt + $_normalAmt;
                ?>
                <label class="addon-cap-option block cursor-pointer">
                    <input type="radio" name="addonCapacity" value="<?= htmlspecialchars($_cap, ENT_QUOTES) ?>"
                           data-unit="<?= $_unitPrice ?>" data-total="<?= $_totalPrice ?>" class="sr-only peer">
                    <div class="flex items-center justify-between px-4 py-3 border border-zinc-200 dark:border-zinc-700 rounded-lg peer-checked:border-blue-500 peer-checked:bg-blue-50 dark:peer-checked:bg-blue-900/20 hover:border-blue-400 transition">
                        <div class="flex items-center gap-3">
                            <span class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                                <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                            </span>
                            <div>
                                <p class="text-base font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($_cap) ?></p>
                                <p class="text-[10px] text-zinc-400">
                                    <?= htmlspecialchars(__('services.mypage.addon_prorated_breakdown', [
                                        'first' => $_curSym . number_format($_firstAmt),
                                        'days' => $_firstMonthDays,
                                        'normal' => $_curSym . number_format($_normalAmt),
                                        'months' => $_normalMonths,
                                    ])) ?>
                                </p>
                            </div>
                        </div>
                        <span class="text-base font-bold text-blue-600 dark:text-blue-400"><?= $_curSym . number_format($_totalPrice) ?></span>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>

            <?php if (!$_payEnabled || $_payGateway !== 'payjp'): ?>
            <!-- 결제 미설정 -->
            <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/50 rounded-lg px-3 py-2 mb-4 text-xs">
                <span class="text-amber-700 dark:text-amber-400"><?= htmlspecialchars(__('services.order.payment.not_configured')) ?></span>
            </div>
            <?php else: ?>
                <?php if ($_paymentCustomerId): ?>
                <!-- 등록 카드 사용 안내 + 다른 카드로 결제 토글 -->
                <div id="addonCardOnFile" class="bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800/50 rounded-lg px-3 py-2 mb-3 text-xs flex items-center justify-between">
                    <span class="text-green-700 dark:text-green-400">✓ <?= htmlspecialchars(__('services.mypage.addon_pay_with_card_on_file')) ?></span>
                    <button type="button" onclick="toggleAddonNewCard(true)" class="text-blue-600 hover:underline whitespace-nowrap ml-2"><?= htmlspecialchars(__('services.mypage.addon_use_new_card')) ?></button>
                </div>
                <?php endif; ?>

                <!-- 카드 입력 폼 (등록 카드 있으면 hidden, card_error 시 자동 표시) -->
                <div id="addonCardForm" class="<?= $_paymentCustomerId ? 'hidden' : '' ?>">
                    <div class="flex items-center justify-between mb-2">
                        <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider"><?= htmlspecialchars(__('services.mypage.addon_step_card_info')) ?></p>
                        <?php if ($_paymentCustomerId): ?>
                        <button type="button" onclick="toggleAddonNewCard(false)" class="text-[10px] text-zinc-500 hover:text-zinc-700 underline"><?= htmlspecialchars(__('services.mypage.addon_use_saved_card')) ?></button>
                        <?php endif; ?>
                    </div>
                    <div id="addonCardErrorBanner" class="hidden mb-2 px-3 py-2 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800/50 rounded text-xs text-red-700 dark:text-red-400"></div>
                    <div class="space-y-3 mb-4 p-3 border border-zinc-200 dark:border-zinc-700 rounded-lg bg-zinc-50 dark:bg-zinc-900/40">
                        <div>
                            <label class="block text-[11px] font-medium text-zinc-600 dark:text-zinc-300 mb-1"><?= htmlspecialchars(__('services.order.payment.card_number')) ?></label>
                            <div id="addon-payjp-number" class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 min-h-[38px]"></div>
                        </div>
                        <div class="grid grid-cols-2 gap-3">
                            <div>
                                <label class="block text-[11px] font-medium text-zinc-600 dark:text-zinc-300 mb-1"><?= htmlspecialchars(__('services.order.payment.card_expiry')) ?></label>
                                <div id="addon-payjp-expiry" class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 min-h-[38px]"></div>
                            </div>
                            <div>
                                <label class="block text-[11px] font-medium text-zinc-600 dark:text-zinc-300 mb-1">CVC</label>
                                <div id="addon-payjp-cvc" class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 min-h-[38px]"></div>
                            </div>
                        </div>
                        <div>
                            <label class="block text-[11px] font-medium text-zinc-600 dark:text-zinc-300 mb-1"><?= htmlspecialchars(__('services.order.payment.card_holder')) ?></label>
                            <input type="text" id="addon-card-holder" placeholder="TARO YAMADA" autocomplete="cc-name"
                                   class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 dark:text-white text-sm uppercase">
                        </div>
                        <div id="addon-card-errors" class="text-xs text-red-600 hidden"></div>
                    </div>
                </div>
            <?php endif; ?>

            <!-- 결제 버튼 -->
            <button type="button" id="addonPayBtn" onclick="submitStorageAddon()" disabled
                    class="w-full px-4 py-3 bg-blue-600 text-white font-bold rounded-lg hover:bg-blue-700 transition disabled:opacity-50 disabled:cursor-not-allowed">
                <span id="addonPayBtnLabel"><?= htmlspecialchars(__('services.mypage.btn_pay_now')) ?></span>
            </button>
        </div>
    </div>

    <?php if ($_payEnabled && $_payGateway === 'payjp'): ?>
    <script src="https://js.pay.jp/v2/pay.js"></script>
    <?php endif; ?>
    <script>
    (function(){
        var hostingSubId = <?= (int)($_hostSub['id'] ?? 0) ?>;
        var months = <?= (int)$_remainMonths ?>;
        var curSym = <?= json_encode($_curSym, JSON_UNESCAPED_UNICODE) ?>;
        var hasCardOnFile = <?= $_paymentCustomerId ? 'true' : 'false' ?>;
        var payjpEnabled = <?= ($_payEnabled && $_payGateway === 'payjp') ? 'true' : 'false' ?>;
        var pubKey = <?= json_encode($_payPubKey, JSON_UNESCAPED_UNICODE) ?>;
        // 카드 입력 모드 — 등록 카드 없으면 항상 true, 등록되어있어도 사용자가 다른 카드 선택 시 true
        var useNewCard = !hasCardOnFile;
        var payjpInstance = null, addonElNumber = null, addonElExpiry = null, addonElCvc = null;
        var cardElementsReady = false;
        var cardReady = { number: false, expiry: false, cvc: false };

        function getSelected() {
            var r = document.querySelector('input[name="addonCapacity"]:checked');
            if (!r) return null;
            return {
                capacity: r.value,
                unit: parseInt(r.dataset.unit, 10),
                total: parseInt(r.dataset.total, 10)
            };
        }
        function updateBtn() {
            var s = getSelected();
            var btn = document.getElementById('addonPayBtn');
            var lbl = document.getElementById('addonPayBtnLabel');
            if (!s) {
                btn.disabled = true;
                lbl.textContent = <?= json_encode(__('services.mypage.btn_pay_now'), JSON_UNESCAPED_UNICODE) ?>;
                return;
            }
            lbl.textContent = <?= json_encode(__('services.mypage.btn_pay_amount'), JSON_UNESCAPED_UNICODE) ?>.replace(':amount', curSym + s.total.toLocaleString());
            if (!payjpEnabled) { btn.disabled = true; return; }
            if (useNewCard) {
                btn.disabled = !(cardReady.number && cardReady.expiry && cardReady.cvc);
            } else {
                btn.disabled = false; // 등록 카드 사용
            }
        }
        document.querySelectorAll('input[name="addonCapacity"]').forEach(function(r) {
            r.addEventListener('change', updateBtn);
        });

        function initPayjpElements() {
            if (cardElementsReady || !payjpEnabled || typeof Payjp === 'undefined') return;
            payjpInstance = Payjp(pubKey);
            var elements = payjpInstance.elements();
            var style = { base: { fontSize: '14px' } };
            addonElNumber = elements.create('cardNumber', { style: style });
            addonElExpiry = elements.create('cardExpiry', { style: style });
            addonElCvc = elements.create('cardCvc', { style: style });
            addonElNumber.mount('#addon-payjp-number');
            addonElExpiry.mount('#addon-payjp-expiry');
            addonElCvc.mount('#addon-payjp-cvc');
            addonElNumber.on('change', function(e){
                cardReady.number = !!e.complete;
                var err = document.getElementById('addon-card-errors');
                if (e.error) { err.textContent = e.error.message; err.classList.remove('hidden'); }
                else { err.classList.add('hidden'); }
                updateBtn();
            });
            addonElExpiry.on('change', function(e){ cardReady.expiry = !!e.complete; updateBtn(); });
            addonElCvc.on('change', function(e){ cardReady.cvc = !!e.complete; updateBtn(); });
            cardElementsReady = true;
        }

        window.toggleAddonNewCard = function(useNew) {
            useNewCard = useNew;
            var formEl = document.getElementById('addonCardForm');
            var onFileEl = document.getElementById('addonCardOnFile');
            if (useNew) {
                formEl.classList.remove('hidden');
                if (onFileEl) onFileEl.classList.add('hidden');
                initPayjpElements();
            } else {
                formEl.classList.add('hidden');
                if (onFileEl) onFileEl.classList.remove('hidden');
            }
            updateBtn();
        };

        window.openStorageAddonModal = function() {
            var m = document.getElementById('storageAddonModal');
            // Portal — transform 이 걸린 조상 요소를 벗어나기 위해 body 로 이동
            if (m.parentElement !== document.body) document.body.appendChild(m);
            m.classList.remove('hidden'); m.classList.add('flex');
            document.body.style.overflow = 'hidden';
            if (useNewCard) initPayjpElements();
            updateBtn();
        };
        window.closeStorageAddonModal = function() {
            var m = document.getElementById('storageAddonModal');
            m.classList.add('hidden'); m.classList.remove('flex');
            document.body.style.overflow = '';
        };

        function showCardErrorBanner(message) {
            var banner = document.getElementById('addonCardErrorBanner');
            if (banner) {
                banner.textContent = message;
                banner.classList.remove('hidden');
            }
        }

        function sendCharge(payload) {
            var btn = document.getElementById('addonPayBtn');
            var origLbl = document.getElementById('addonPayBtnLabel').textContent;
            btn.disabled = true;
            document.getElementById('addonPayBtnLabel').textContent = <?= json_encode(__('services.mypage.btn_processing'), JSON_UNESCAPED_UNICODE) ?>;
            return serviceAction('pay_storage_addon', payload).then(function(d) {
                if (d.success) {
                    alert(<?= json_encode(__('services.mypage.alert_addon_paid_active'), JSON_UNESCAPED_UNICODE) ?>);
                    closeStorageAddonModal();
                    location.reload();
                    return;
                }
                btn.disabled = false;
                document.getElementById('addonPayBtnLabel').textContent = origLbl;
                // 카드 자체 오류 → 새 카드 입력 폼으로 전환 + 안내 배너
                if (d.card_error) {
                    var msg = (d.message || '') + ' — ' + <?= json_encode(__('services.mypage.addon_card_error_retry'), JSON_UNESCAPED_UNICODE) ?>;
                    if (hasCardOnFile && !useNewCard) {
                        toggleAddonNewCard(true);
                    }
                    showCardErrorBanner(msg);
                } else {
                    alert(d.message || <?= json_encode(__('services.detail.alert_failed'), JSON_UNESCAPED_UNICODE) ?>);
                }
            }).catch(function(e) {
                alert(e && e.message ? e.message : <?= json_encode(__('services.detail.alert_failed'), JSON_UNESCAPED_UNICODE) ?>);
                btn.disabled = false;
                document.getElementById('addonPayBtnLabel').textContent = origLbl;
            });
        }

        window.submitStorageAddon = function() {
            var s = getSelected();
            if (!s) return;
            var msg = <?= json_encode(__('services.mypage.confirm_pay_storage_addon'), JSON_UNESCAPED_UNICODE) ?>
                .replace(':capacity', s.capacity)
                .replace(':months', months)
                .replace(':total', curSym + s.total.toLocaleString());
            if (!confirm(msg)) return;

            var basePayload = {
                subscription_id: hostingSubId,
                capacity: s.capacity,
                unit_price: s.unit,
                total_price: s.total,
                months: months
            };

            if (useNewCard) {
                // payjp.js 카드 토큰 생성 → 서버
                var btn = document.getElementById('addonPayBtn');
                btn.disabled = true;
                if (!payjpInstance) {
                    alert(<?= json_encode(__('services.order.payment.not_configured'), JSON_UNESCAPED_UNICODE) ?>);
                    btn.disabled = false; return;
                }
                var holderEl = document.getElementById('addon-card-holder');
                var holder = holderEl ? holderEl.value.trim().toUpperCase() : '';
                if (!holder) {
                    alert(<?= json_encode(__('services.order.payment.card_holder_required'), JSON_UNESCAPED_UNICODE) ?>);
                    btn.disabled = false;
                    if (holderEl) holderEl.focus();
                    return;
                }
                payjpInstance.createToken(addonElNumber).then(function(r) {
                    if (r.error) throw new Error(r.error.message);
                    if (!r.id) throw new Error(<?= json_encode(__('services.order.payment.token_failed'), JSON_UNESCAPED_UNICODE) ?>);
                    basePayload.card_token = r.id;
                    basePayload.card_holder = holder;
                    return sendCharge(basePayload);
                }).catch(function(e) {
                    alert(e && e.message ? e.message : <?= json_encode(__('services.detail.alert_failed'), JSON_UNESCAPED_UNICODE) ?>);
                    btn.disabled = false;
                });
            } else {
                // 등록 카드 사용
                sendCharge(basePayload);
            }
        };
    })();
    </script>
    <?php endif; ?>

    <!-- 기존 부가서비스 목록 -->
    <?php foreach ($subs as $sub):
        $st = $statusLabels[$sub['status']] ?? [__('services.mypage.status_unknown'), 'bg-gray-100 text-gray-500'];
        $meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
        $sc = $sub['service_class'] ?? 'recurring';
    ?>
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-5 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <p class="text-sm font-semibold text-zinc-900 dark:text-white"><?= htmlspecialchars($_localizeLabel($sub)) ?></p>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-medium <?= $st[1] ?>"><?= $st[0] ?></span>
                <?php if ($sc === 'one_time'): ?>
                <span class="text-[10px] px-2 py-0.5 bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400 rounded-full"><?= htmlspecialchars(__('services.detail.b_one_time')) ?></span>
                <?php elseif ($sc === 'free'): ?>
                <span class="text-[10px] px-2 py-0.5 bg-green-50 text-green-600 dark:bg-green-900/30 dark:text-green-400 rounded-full"><?= htmlspecialchars(__('services.order.summary.free')) ?></span>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-2">
                <?php if ($sc === 'one_time'): ?>
                    <?php
                    $currentOneTimeStatus = !empty($sub['completed_at']) ? 'completed' : $sub['status'];
                    $otColors = ['pending'=>'blue','active'=>'amber','suspended'=>'zinc','cancelled'=>'red','completed'=>'green'];
                    $otLabels = [
                        'pending'   => __('services.detail.ot_pending'),
                        'active'    => __('services.detail.ot_active'),
                        'suspended' => __('services.detail.ot_suspended'),
                        'cancelled' => __('services.detail.ot_cancelled'),
                        'completed' => __('services.detail.ot_completed'),
                    ];
                    $otColor = $otColors[$currentOneTimeStatus] ?? 'zinc';
                    $otLabel = $otLabels[$currentOneTimeStatus] ?? __('services.mypage.status_unknown');
                    ?>
                    <span class="text-xs px-2.5 py-1 bg-<?= $otColor ?>-50 text-<?= $otColor ?>-600 dark:bg-<?= $otColor ?>-900/20 dark:text-<?= $otColor ?>-400 rounded-lg"><?= htmlspecialchars($otLabel) ?></span>
                <?php elseif ($sc === 'recurring' && $sub['status'] === 'active'): ?>
                    <span class="text-xs text-zinc-400"><?= htmlspecialchars(__('services.detail.auto_renew')) ?></span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer" <?= $sub['auto_renew'] ? 'checked' : '' ?>
                               onchange="serviceAction('toggle_auto_renew',{subscription_id:<?= $sub['id'] ?>,auto_renew:this.checked})">
                        <div class="w-9 h-5 bg-zinc-200 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                <?php elseif ($sc === 'free' && $sub['status'] === 'active'): ?>
                    <button onclick="serviceAction('request_renewal',{subscription_id:<?= $sub['id'] ?>}).then(function(d){alert(d.message||<?= json_encode(__('services.detail.alert_request_done'), JSON_UNESCAPED_UNICODE) ?>)})"
                            class="text-xs px-3 py-1.5 bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400 rounded-lg hover:bg-blue-100 transition"><?= htmlspecialchars(__('services.detail.btn_renewal')) ?></button>
                <?php endif; ?>
            </div>
        </div>
        <div class="px-5 pb-3 flex items-center justify-between gap-3 text-xs text-zinc-400 flex-wrap">
            <div class="flex items-center gap-3 flex-wrap">
                <?php if ($sc !== 'one_time'): ?>
                <span><?= date('Y-m-d', strtotime($sub['started_at'])) ?> ~ <?= date('Y-m-d', strtotime($sub['expires_at'])) ?></span>
                <?php endif; ?>
                <span><?= (int)$sub['billing_amount'] > 0 ? $fmtPrice($sub['billing_amount'], $sub['currency']) : __('services.order.summary.free') ?></span>
                <?php if (!empty($meta['quote_required'])): ?>
                <span class="text-amber-500"><?= htmlspecialchars(__('services.detail.quote_required')) ?></span>
                <?php endif; ?>
                <?php if (!empty($sub['completed_at'])): ?>
                <span class="text-green-600"><?= htmlspecialchars(__('services.detail.f_completed')) ?>: <?= date('Y-m-d', strtotime($sub['completed_at'])) ?></span>
                <?php endif; ?>
            </div>
            <?php if (!empty($meta['install_info']) && $_siteUrl): ?>
            <div class="flex items-center gap-2">
                <a href="<?= htmlspecialchars($_siteUrl) ?>/admin" target="_blank"
                   class="inline-flex items-center gap-1 px-3 py-1 text-[11px] font-medium text-violet-700 dark:text-violet-300 bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-800 rounded hover:bg-violet-100 dark:hover:bg-violet-900/40 transition">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <?= htmlspecialchars(__('services.detail.btn_open_admin')) ?>
                </a>
                <a href="<?= htmlspecialchars($_siteUrl) ?>/" target="_blank"
                   class="inline-flex items-center gap-1 px-3 py-1 text-[11px] font-medium text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded hover:bg-blue-100 dark:hover:bg-blue-900/40 transition">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <?= htmlspecialchars(__('services.detail.btn_open_site')) ?>
                </a>
            </div>
            <?php endif; ?>
        </div>
        <?php if (!empty($meta['install_info'])):
            $info = $meta['install_info'];
        ?>
        <div class="mx-5 mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <p class="text-xs font-semibold text-blue-800 dark:text-blue-200 mb-2"><?= htmlspecialchars(__('services.order.addons.install_admin_label')) ?></p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-1.5 text-xs">
                <?php if (!empty($info['admin_id'])): ?>
                <div class="flex gap-2"><span class="text-zinc-500 dark:text-zinc-400 min-w-[7rem]"><?= htmlspecialchars(__('services.order.addons.install_admin_id')) ?>:</span><span class="font-mono text-zinc-800 dark:text-zinc-100"><?= htmlspecialchars($info['admin_id']) ?></span></div>
                <?php endif; ?>
                <?php if (!empty($info['admin_email'])): ?>
                <div class="flex gap-2"><span class="text-zinc-500 dark:text-zinc-400 min-w-[7rem]"><?= htmlspecialchars(__('services.order.addons.install_admin_email')) ?>:</span><span class="font-mono text-zinc-800 dark:text-zinc-100"><?= htmlspecialchars($info['admin_email']) ?></span></div>
                <?php endif; ?>
                <?php if (!empty($info['admin_pw'])): ?>
                <div class="flex items-center gap-2"><span class="text-zinc-500 dark:text-zinc-400 min-w-[7rem]"><?= htmlspecialchars(__('services.order.addons.install_admin_pw')) ?>:</span><span class="install-pw font-mono text-zinc-800 dark:text-zinc-100 select-all" data-real="<?= htmlspecialchars($info['admin_pw']) ?>">••••••••</span><button type="button" onclick="(function(b){var s=b.previousElementSibling;if(s.textContent==='••••••••'){s.textContent=s.dataset.real;b.textContent='🙈';}else{s.textContent='••••••••';b.textContent='👁';}})(this)" class="text-xs hover:opacity-70" title="show/hide">👁</button></div>
                <?php endif; ?>
                <?php if (!empty($info['site_title'])): ?>
                <div class="flex gap-2"><span class="text-zinc-500 dark:text-zinc-400 min-w-[7rem]"><?= htmlspecialchars(__('services.order.addons.install_site_title')) ?>:</span><span class="text-zinc-800 dark:text-zinc-100"><?= htmlspecialchars($info['site_title']) ?></span></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>

    <?php
    // 추가 신청 가능 부가서비스 — 비즈니스 메일 제외
    $_addonsAll = json_decode($_pdo_settings['service_addons'] ?? '[]', true) ?: [];
    if (empty($_addonsAll)) {
        try {
            $_pfx = $_ENV['DB_PREFIX'] ?? 'rzx_';
            $_aSt = $pdo->prepare("SELECT `value` FROM {$_pfx}settings WHERE `key` = 'service_addons' LIMIT 1");
            $_aSt->execute();
            $_addonsAll = json_decode($_aSt->fetchColumn() ?: '[]', true) ?: [];
        } catch (\Throwable $e) { $_addonsAll = []; }
    }
    // bizmail 제외 (label 또는 _id 기준)
    $_addonsAvail = array_filter($_addonsAll, function($a) {
        $id = strtolower($a['_id'] ?? '');
        $lbl = $a['label'] ?? '';
        if ($id === 'bizmail') return false;
        if (stripos($lbl, '비즈니스 메일') !== false || stripos($lbl, 'business mail') !== false || stripos($lbl, 'ビジネスメール') !== false) return false;
        return true;
    });
    // 이미 활성 sub 의 라벨 모음 (중복 신청 방지)
    $_activeAddonLabels = [];
    foreach ($subs as $_s) {
        $_activeAddonLabels[] = trim((string)($_s['label'] ?? ''));
    }
    ?>
    <?php if (!empty($_addonsAvail) && $_hostSub): ?>
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700">
            <p class="text-xs font-bold text-zinc-700 dark:text-zinc-200"><?= htmlspecialchars(__('services.detail.addon_request_section')) ?></p>
            <p class="text-[10px] text-zinc-400 mt-0.5"><?= htmlspecialchars(__('services.detail.addon_request_section_desc')) ?></p>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-zinc-700/50">
            <?php foreach ($_addonsAvail as $_a):
                $_aId = $_a['_id'] ?? '';
                $_aLabel = $_a['label'] ?? '';
                $_aPrice = (int)($_a['price'] ?? 0);
                $_aUnit = $_a['unit'] ?? '';
                $_aOneTime = !empty($_a['one_time']);
                $_aIsQuote = ($_aPrice <= 0 && stripos($_aUnit, '견적') !== false) || stripos($_aUnit, 'quote') !== false || stripos($_aUnit, '見積') !== false;
                $_aIsFree = $_aPrice <= 0 && !$_aIsQuote;
                // 이미 활성 sub 에 같은 label 이 있으면 비활성
                $_aAlreadyActive = in_array(trim($_aLabel), $_activeAddonLabels, true);
            ?>
            <div class="px-5 py-3 flex items-center justify-between gap-3 flex-wrap">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($_aLabel) ?></p>
                        <?php if ($_aOneTime): ?>
                        <span class="text-[10px] px-1.5 py-0.5 bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400 rounded"><?= htmlspecialchars(__('services.detail.b_one_time')) ?></span>
                        <?php endif; ?>
                        <?php if ($_aAlreadyActive): ?>
                        <span class="text-[10px] px-1.5 py-0.5 bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400 rounded"><?= htmlspecialchars(__('services.detail.addon_already_requested')) ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">
                        <?php if ($_aIsFree): ?>
                        <span class="text-green-600 dark:text-green-400"><?= htmlspecialchars(__('services.order.summary.free')) ?></span>
                        <?php elseif ($_aIsQuote): ?>
                        <span class="text-amber-600 dark:text-amber-400"><?= htmlspecialchars(__('services.detail.quote_required')) ?></span>
                        <?php else: ?>
                        <?= $fmtPrice($_aPrice, $_curr) ?><?= htmlspecialchars($_aUnit) ?>
                        <?php endif; ?>
                    </p>
                </div>
                <?php if ($_aAlreadyActive): ?>
                <button type="button" disabled
                    class="px-4 py-1.5 text-xs font-medium text-zinc-400 bg-zinc-100 dark:bg-zinc-700 dark:text-zinc-500 rounded-lg cursor-not-allowed whitespace-nowrap">
                    <?= htmlspecialchars(__('services.detail.btn_addon_already')) ?>
                </button>
                <?php elseif ($_aIsQuote): ?>
                <a href="<?= $baseUrl ?>/mypage/custom-projects/new?title=<?= rawurlencode($_aLabel) ?>&from=addon&host_sub=<?= (int)$_hostSub['id'] ?>"
                    class="inline-flex items-center gap-1 px-4 py-1.5 text-xs font-medium text-white bg-amber-600 hover:bg-amber-700 rounded-lg transition whitespace-nowrap">
                    <?= htmlspecialchars(__('services.detail.btn_addon_request_quote')) ?>
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/></svg>
                </a>
                <?php else: ?>
                <button type="button"
                    onclick="addonRequest(<?= (int)$_hostSub['id'] ?>, '<?= htmlspecialchars($_aId) ?>', '<?= htmlspecialchars(addslashes($_aLabel)) ?>', <?= (int)$_aPrice ?>, '<?= htmlspecialchars(addslashes($_aUnit)) ?>')"
                    class="px-4 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition whitespace-nowrap">
                    <?= htmlspecialchars(__('services.detail.btn_addon_request')) ?>
                </button>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <script>
    function addonRequest(hostSubId, addonId, label, unitPrice, unitText) {
        // 가격 있는 부가서비스 → 결제 모달이 확인 역할 (confirm 생략)
        if (parseInt(unitPrice, 10) > 0 && typeof openAddonPayModal === 'function') {
            openAddonPayModal(addonId, label, unitPrice, unitText);
            return;
        }
        // install 도 confirm 생략 (기존 정책 유지)
        if (addonId !== 'install') {
            var msg = <?= json_encode(__('services.detail.confirm_addon_request'), JSON_UNESCAPED_UNICODE) ?>.replace(':label', label);
            if (!confirm(msg)) return;
        }
        serviceAction('request_addon', { host_subscription_id: hostSubId, addon_id: addonId, label: label })
            .then(function(d) {
                if (d.success) {
                    alert(<?= json_encode(__('services.detail.alert_addon_requested'), JSON_UNESCAPED_UNICODE) ?>);
                    location.reload();
                } else {
                    alert(d.message || <?= json_encode(__('services.detail.alert_failed'), JSON_UNESCAPED_UNICODE) ?>);
                }
            }).catch(function(e) {
                alert(e.message);
            });
    }
    </script>
    <?php endif; ?>
</div>

<?php if ($_payPubKey && $_hostSub): ?>
<script src="https://js.pay.jp/v2/pay.js"></script>
<?php include __DIR__ . '/_addon-pay-modal.php'; ?>
<?php endif; ?>

