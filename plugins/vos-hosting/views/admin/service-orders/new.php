<?php
/**
 * 관리자 — 호스팅 신청서 작성 (대리 등록)
 * 카운터/오프라인 영업으로 받은 주문을 시스템에 등록.
 * 결제 방식: 현금 / 카드 / 무료
 */
if (!function_exists('__')) require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';

$_svcLangFile = BASE_PATH . '/plugins/vos-hosting/lang/' . \RzxLib\Core\I18n\Translator::getLocale() . '/services.php';
if (!file_exists($_svcLangFile)) $_svcLangFile = BASE_PATH . '/plugins/vos-hosting/lang/en/services.php';
if (file_exists($_svcLangFile)) \RzxLib\Core\I18n\Translator::merge('services', require $_svcLangFile);

$pageTitle = __('services.admin_neworder.page_title') . ' - ' . ($config['app_name'] ?? 'VosCMS') . ' Admin';
$pageHeaderTitle = __('services.admin_neworder.header_title');
$pageSubTitle = __('services.admin_neworder.sub_title');
$pageSubDesc = __('services.admin_neworder.sub_desc');

$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$pdo = \RzxLib\Core\Database\Connection::getInstance()->getPdo();

// 서비스 settings 로드
$_settings = [];
$sSt = $pdo->prepare("SELECT `key`, `value` FROM {$prefix}settings WHERE `key` IN ('service_hosting_plans','service_addons','service_maintenance','payment_config')");
$sSt->execute();
while ($r = $sSt->fetch(PDO::FETCH_ASSOC)) $_settings[$r['key']] = $r['value'];

$hostingPlans = json_decode($_settings['service_hosting_plans'] ?? '[]', true) ?: [];
$addons = json_decode($_settings['service_addons'] ?? '[]', true) ?: [];
$maintenances = json_decode($_settings['service_maintenance'] ?? '[]', true) ?: [];
$_payCfg = json_decode($_settings['payment_config'] ?? '{}', true) ?: [];
$_payPubKey = (string)($_payCfg['gateways'][$_payCfg['gateway'] ?? 'payjp']['public_key'] ?? '');

include BASE_PATH . '/resources/views/admin/reservations/_head.php';
?>

<style>
/* 다크모드 input/select/textarea 강제 색상 (user-agent stylesheet 우회) */
.dark #adminNewOrderPage input:not([type="radio"]):not([type="checkbox"]):not([type="file"]),
.dark #adminNewOrderPage select,
.dark #adminNewOrderPage textarea {
    color: #ffffff !important;
    background-color: #3f3f46 !important;
    color-scheme: dark;
}
.dark #adminNewOrderPage input::placeholder,
.dark #adminNewOrderPage textarea::placeholder {
    color: #a1a1aa !important;
    opacity: 1 !important;
}
.dark #adminNewOrderPage select option {
    color: #ffffff !important;
    background-color: #3f3f46 !important;
}
/* 페이지 fallback — 색상 미지정 텍스트도 다크모드 흰색 톤 */
.dark #adminNewOrderPage {
    color: #f4f4f5;
}
</style>

<div id="adminNewOrderPage" class="px-4 sm:px-6 lg:px-8 py-6 max-w-4xl mx-auto">
    <div class="mb-4">
        <a href="<?= $adminUrl ?>/service-orders" class="text-xs text-zinc-500 hover:text-zinc-800 dark:hover:text-zinc-200">
            ← <?= htmlspecialchars(__('services.admin_neworder.back_to_list')) ?>
        </a>
    </div>

    <!-- ① 고객 정보 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 mb-4">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700">
            <p class="text-sm font-bold text-zinc-900 dark:text-white">① <?= htmlspecialchars(__('services.admin_neworder.section_customer')) ?></p>
        </div>
        <div class="p-5 space-y-3">
            <div>
                <label class="block text-[11px] font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= htmlspecialchars(__('services.admin_neworder.cust_mode')) ?></label>
                <div class="flex items-center gap-4 text-sm text-zinc-900 dark:text-white">
                    <label class="flex items-center gap-1.5 cursor-pointer"><input type="radio" name="cust_mode" value="existing" checked onchange="onCustomerModeChange()"> <?= htmlspecialchars(__('services.admin_neworder.cust_existing')) ?></label>
                    <label class="flex items-center gap-1.5 cursor-pointer"><input type="radio" name="cust_mode" value="new" onchange="onCustomerModeChange()"> <?= htmlspecialchars(__('services.admin_neworder.cust_new')) ?></label>
                </div>
            </div>

            <div id="custExistingBox">
                <label class="block text-[11px] font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= htmlspecialchars(__('services.admin_neworder.cust_search')) ?></label>
                <div class="flex items-center gap-2">
                    <input type="text" id="custSearchEmail" class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 rounded-lg" placeholder="<?= htmlspecialchars(__('services.admin_neworder.cust_search_ph')) ?>">
                    <button type="button" onclick="searchCustomer()" class="px-4 py-2 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg whitespace-nowrap"><?= htmlspecialchars(__('services.admin_neworder.btn_search')) ?></button>
                </div>
                <div id="custSearchResult" class="mt-2"></div>
                <input type="hidden" id="custUserId" value="">
            </div>

            <div id="custNewBox" class="hidden grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= htmlspecialchars(__('services.admin_neworder.cust_email')) ?> <span class="text-red-500">*</span></label>
                    <input type="email" id="custEmail" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 rounded-lg" placeholder="customer@example.com">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= htmlspecialchars(__('services.admin_neworder.cust_name')) ?> <span class="text-red-500">*</span></label>
                    <input type="text" id="custName" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white rounded-lg">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= htmlspecialchars(__('services.admin_neworder.cust_phone')) ?></label>
                    <input type="text" id="custPhone" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white rounded-lg">
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= htmlspecialchars(__('services.admin_neworder.cust_company')) ?></label>
                    <input type="text" id="custCompany" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white rounded-lg">
                </div>
                <p class="col-span-full text-[10px] text-amber-600 dark:text-amber-400">⚠ <?= htmlspecialchars(__('services.admin_neworder.cust_new_hint')) ?></p>
            </div>
        </div>
    </div>

    <!-- ② 호스팅 플랜 + 도메인 + 계약기간 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 mb-4">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700">
            <p class="text-sm font-bold text-zinc-900 dark:text-white">② <?= htmlspecialchars(__('services.admin_neworder.section_service')) ?></p>
        </div>
        <div class="p-5 space-y-3">
            <div>
                <label class="block text-[11px] font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= htmlspecialchars(__('services.admin_neworder.hosting_plan')) ?> <span class="text-red-500">*</span></label>
                <select id="hostingPlan" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white rounded-lg" onchange="recalc()">
                    <option value="">-</option>
                    <?php foreach ($hostingPlans as $p): ?>
                    <option value="<?= htmlspecialchars($p['_id']) ?>" data-price="<?= (int)($p['price'] ?? 0) ?>" data-capacity="<?= htmlspecialchars($p['capacity'] ?? '') ?>" data-label="<?= htmlspecialchars($p['label'] ?? '') ?>">
                        <?= htmlspecialchars($p['label'] ?? '') ?> <?= htmlspecialchars($p['capacity'] ?? '') ?> — ¥<?= number_format((int)($p['price'] ?? 0)) ?>/<?= htmlspecialchars(__('services.order.hosting.unit_month')) ?>
                    </option>
                    <?php endforeach; ?>
                </select>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                <div>
                    <label class="block text-[11px] font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= htmlspecialchars(__('services.admin_neworder.contract_months')) ?></label>
                    <select id="contractMonths" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white rounded-lg" onchange="recalc()">
                        <option value="1">1<?= htmlspecialchars(__('services.order.hosting.unit_month')) ?></option>
                        <option value="3">3<?= htmlspecialchars(__('services.order.hosting.unit_month')) ?></option>
                        <option value="6">6<?= htmlspecialchars(__('services.order.hosting.unit_month')) ?></option>
                        <option value="12" selected>12<?= htmlspecialchars(__('services.order.hosting.unit_month')) ?></option>
                        <option value="24">24<?= htmlspecialchars(__('services.order.hosting.unit_month')) ?></option>
                        <option value="36">36<?= htmlspecialchars(__('services.order.hosting.unit_month')) ?></option>
                    </select>
                </div>
                <div>
                    <label class="block text-[11px] font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= htmlspecialchars(__('services.admin_neworder.domain_option')) ?></label>
                    <select id="domainOption" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white rounded-lg" onchange="onDomainOptChange()">
                        <option value="free"><?= htmlspecialchars(__('services.custom.dom_free')) ?></option>
                        <option value="new"><?= htmlspecialchars(__('services.custom.dom_new')) ?></option>
                        <option value="existing"><?= htmlspecialchars(__('services.custom.dom_existing')) ?></option>
                    </select>
                </div>
            </div>

            <div id="domainNameBox" class="hidden">
                <label class="block text-[11px] font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= htmlspecialchars(__('services.admin_neworder.domain_name')) ?></label>
                <div class="flex items-center gap-2">
                    <input type="text" id="domainName" class="flex-1 px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 rounded-lg" placeholder="example.com" oninput="onDomainInputChange()">
                    <button type="button" id="domainSearchBtn" onclick="searchDomain()" class="hidden px-4 py-2 text-xs font-medium text-white bg-emerald-600 hover:bg-emerald-700 rounded-lg whitespace-nowrap">
                        🔍 <?= htmlspecialchars(__('services.admin_neworder.btn_domain_search')) ?>
                    </button>
                </div>
                <div id="domainSearchResult" class="mt-2"></div>
            </div>
        </div>
    </div>

    <!-- ③ 부가서비스 -->
    <?php if (!empty($addons)): ?>
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 mb-4">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700">
            <p class="text-sm font-bold text-zinc-900 dark:text-white">③ <?= htmlspecialchars(__('services.admin_neworder.section_addons')) ?></p>
        </div>
        <div class="p-5 space-y-2">
            <?php foreach ($addons as $a):
                $aPrice = (int)($a['price'] ?? 0);
                $aOneTime = !empty($a['one_time']);
                $aIsQuote = $aPrice <= 0 && (stripos($a['unit'] ?? '', '견적') !== false || stripos($a['unit'] ?? '', 'quote') !== false || stripos($a['unit'] ?? '', '見積') !== false);
                if ($aIsQuote) continue; // 견적 항목은 제작 프로젝트로
            ?>
            <label class="flex items-center justify-between gap-3 px-3 py-2 border border-gray-200 dark:border-zinc-700 rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-700/30 cursor-pointer">
                <div class="flex items-center gap-2 flex-1 min-w-0">
                    <input type="checkbox" class="addon-check" data-id="<?= htmlspecialchars($a['_id']) ?>" data-label="<?= htmlspecialchars($a['label']) ?>" data-price="<?= $aPrice ?>" data-onetime="<?= $aOneTime ? 1 : 0 ?>" onchange="recalc()">
                    <div class="min-w-0">
                        <p class="text-sm text-zinc-900 dark:text-white"><?= htmlspecialchars($a['label']) ?></p>
                        <?php if (!empty($a['desc'])): ?>
                        <p class="text-[10px] text-zinc-500 dark:text-zinc-400"><?= htmlspecialchars($a['desc']) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <span class="text-xs text-zinc-700 dark:text-zinc-300 whitespace-nowrap">
                    <?= $aPrice > 0 ? '¥' . number_format($aPrice) . htmlspecialchars($a['unit'] ?? '') : __('services.order.summary.free') ?>
                    <?php if ($aOneTime): ?> · <?= htmlspecialchars(__('services.detail.b_one_time')) ?><?php endif; ?>
                </span>
            </label>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- ④ 합계 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 mb-4">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700">
            <p class="text-sm font-bold text-zinc-900 dark:text-white">④ <?= htmlspecialchars(__('services.admin_neworder.section_total')) ?></p>
        </div>
        <div class="p-5 space-y-2 text-sm">
            <div class="flex justify-between">
                <span class="text-zinc-500 dark:text-zinc-400"><?= htmlspecialchars(__('services.order.summary.subtotal')) ?></span>
                <span id="sumSubtotal" class="tabular-nums text-zinc-900 dark:text-white">¥0</span>
            </div>
            <div class="flex justify-between">
                <span class="text-zinc-500 dark:text-zinc-400"><?= htmlspecialchars(__('services.order.summary.vat')) ?> (10%)</span>
                <span id="sumTax" class="tabular-nums text-zinc-900 dark:text-white">¥0</span>
            </div>
            <div class="flex justify-between pt-2 border-t border-gray-200 dark:border-zinc-600 font-bold text-zinc-900 dark:text-white">
                <span><?= htmlspecialchars(__('services.order.summary.final_amount')) ?></span>
                <span id="sumTotal" class="tabular-nums text-base">¥0</span>
            </div>
            <div>
                <label class="block text-[11px] font-medium text-zinc-700 dark:text-zinc-300 mt-2 mb-1"><?= htmlspecialchars(__('services.admin_neworder.amount_override')) ?></label>
                <input type="number" id="amountOverride" min="0" step="1" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 rounded-lg" placeholder="<?= htmlspecialchars(__('services.admin_neworder.amount_override_ph')) ?>">
                <p class="text-[10px] text-zinc-400 mt-1"><?= htmlspecialchars(__('services.admin_neworder.amount_override_hint')) ?></p>
            </div>
        </div>
    </div>

    <!-- ⑤ 결제 방식 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 mb-4">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700">
            <p class="text-sm font-bold text-zinc-900 dark:text-white">⑤ <?= htmlspecialchars(__('services.admin_neworder.section_payment')) ?></p>
        </div>
        <div class="p-5 space-y-3">
            <div class="grid grid-cols-3 gap-2">
                <label class="flex flex-col items-center gap-1 p-3 border-2 border-gray-200 dark:border-zinc-700 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-zinc-700/30 [&:has(input:checked)]:border-blue-500 [&:has(input:checked)]:bg-blue-50 dark:[&:has(input:checked)]:bg-blue-900/20">
                    <input type="radio" name="payMethod" value="cash" checked onchange="onPayMethodChange()" class="sr-only">
                    <span class="text-2xl">💴</span>
                    <span class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars(__('services.admin_neworder.pm_cash')) ?></span>
                </label>
                <label class="flex flex-col items-center gap-1 p-3 border-2 border-gray-200 dark:border-zinc-700 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-zinc-700/30 [&:has(input:checked)]:border-blue-500 [&:has(input:checked)]:bg-blue-50 dark:[&:has(input:checked)]:bg-blue-900/20">
                    <input type="radio" name="payMethod" value="card" onchange="onPayMethodChange()" class="sr-only">
                    <span class="text-2xl">💳</span>
                    <span class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars(__('services.admin_neworder.pm_card')) ?></span>
                </label>
                <label class="flex flex-col items-center gap-1 p-3 border-2 border-gray-200 dark:border-zinc-700 rounded-lg cursor-pointer hover:bg-gray-50 dark:hover:bg-zinc-700/30 [&:has(input:checked)]:border-blue-500 [&:has(input:checked)]:bg-blue-50 dark:[&:has(input:checked)]:bg-blue-900/20">
                    <input type="radio" name="payMethod" value="free" onchange="onPayMethodChange()" class="sr-only">
                    <span class="text-2xl">🎁</span>
                    <span class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars(__('services.admin_neworder.pm_free')) ?></span>
                </label>
            </div>

            <!-- 현금 -->
            <div id="payCashBox" class="space-y-2">
                <label class="block text-[11px] font-medium text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars(__('services.admin_neworder.cash_received')) ?> <span class="text-red-500">*</span></label>
                <input type="number" id="cashReceived" min="0" step="1" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white rounded-lg" placeholder="<?= htmlspecialchars(__('services.admin_neworder.cash_received_ph')) ?>">
                <p class="text-[10px] text-zinc-400"><?= htmlspecialchars(__('services.admin_neworder.cash_hint')) ?></p>
            </div>

            <!-- 카드 (PAY.JP Elements) -->
            <div id="payCardBox" class="hidden space-y-2">
                <p class="text-[11px] text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars(__('services.admin_neworder.card_hint')) ?></p>
                <div id="payCardError" class="hidden p-2 bg-red-50 border border-red-200 text-red-700 text-[11px] rounded"></div>
                <div>
                    <label class="block text-[11px] text-zinc-500 mb-1"><?= htmlspecialchars(__('services.order.payment.card_number')) ?></label>
                    <div id="adm_pj_number" class="px-3 py-2 border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 rounded-lg"></div>
                </div>
                <div class="grid grid-cols-2 gap-2">
                    <div>
                        <label class="block text-[11px] text-zinc-500 mb-1"><?= htmlspecialchars(__('services.order.payment.card_expiry')) ?></label>
                        <div id="adm_pj_expiry" class="px-3 py-2 border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 rounded-lg"></div>
                    </div>
                    <div>
                        <label class="block text-[11px] text-zinc-500 mb-1"><?= htmlspecialchars(__('services.order.payment.card_cvc')) ?></label>
                        <div id="adm_pj_cvc" class="px-3 py-2 border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 rounded-lg"></div>
                    </div>
                </div>
            </div>

            <!-- 무료 -->
            <div id="payFreeBox" class="hidden space-y-2">
                <label class="block text-[11px] font-medium text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars(__('services.admin_neworder.free_reason')) ?> <span class="text-red-500">*</span></label>
                <input type="text" id="freeReason" maxlength="255" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white placeholder-zinc-400 dark:placeholder-zinc-500 rounded-lg" placeholder="<?= htmlspecialchars(__('services.admin_neworder.free_reason_ph')) ?>">
                <p class="text-[10px] text-zinc-400"><?= htmlspecialchars(__('services.admin_neworder.free_hint')) ?></p>
            </div>
        </div>
    </div>

    <!-- 등록 버튼 -->
    <div class="flex items-center justify-end gap-2 mb-6">
        <a href="<?= $adminUrl ?>/service-orders" class="px-4 py-2 text-xs font-medium text-zinc-600 dark:text-zinc-300 bg-gray-100 dark:bg-zinc-700 rounded-lg"><?= htmlspecialchars(__('services.order.checkout.btn_cancel')) ?></a>
        <button type="button" id="submitBtn" onclick="submitOrder()" class="px-6 py-2 text-sm font-bold text-white bg-blue-600 hover:bg-blue-700 rounded-lg disabled:opacity-50">
            <?= htmlspecialchars(__('services.admin_neworder.btn_submit')) ?>
        </button>
    </div>
</div>

<?php if ($_payPubKey): ?>
<script src="https://js.pay.jp/v2/pay.js"></script>
<?php endif; ?>

<script>
var siteBaseUrl = <?= json_encode($baseUrl) ?>;
var adminUrl = <?= json_encode($adminUrl) ?>;
var TAX_RATE = 10;

function fmt(n) { return '¥' + Math.round(n).toLocaleString(); }

function onCustomerModeChange() {
    var mode = document.querySelector('input[name="cust_mode"]:checked').value;
    document.getElementById('custExistingBox').classList.toggle('hidden', mode !== 'existing');
    document.getElementById('custNewBox').classList.toggle('hidden', mode !== 'new');
}

function onDomainOptChange() {
    var v = document.getElementById('domainOption').value;
    document.getElementById('domainNameBox').classList.toggle('hidden', v === 'free');
    // 검색 버튼은 신규 등록 시에만
    document.getElementById('domainSearchBtn').classList.toggle('hidden', v !== 'new');
    document.getElementById('domainSearchResult').innerHTML = '';
}

function onDomainInputChange() {
    // 입력 변경 시 결과 초기화
    document.getElementById('domainSearchResult').innerHTML = '';
}

function searchDomain() {
    var input = document.getElementById('domainName').value.trim().toLowerCase();
    if (!input) return;
    // TLD 분리 — 가장 단순: 첫 점 이후를 TLD로
    var dotIdx = input.indexOf('.');
    if (dotIdx < 0) {
        document.getElementById('domainSearchResult').innerHTML =
            '<p class="text-xs text-amber-600 dark:text-amber-400">⚠ <?= htmlspecialchars(__('services.admin_neworder.dom_search_invalid')) ?></p>';
        return;
    }
    var name = input.substring(0, dotIdx);
    var tld = input.substring(dotIdx); // ".com", ".co.kr" 등 (선두 점 포함)
    if (!/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/.test(name)) {
        document.getElementById('domainSearchResult').innerHTML =
            '<p class="text-xs text-amber-600 dark:text-amber-400">⚠ <?= htmlspecialchars(__('services.admin_neworder.dom_search_invalid')) ?></p>';
        return;
    }
    document.getElementById('domainSearchResult').innerHTML =
        '<p class="text-xs text-zinc-500 dark:text-zinc-400 p-2"><?= htmlspecialchars(__('services.admin_neworder.dom_searching')) ?></p>';

    fetch(siteBaseUrl + '/plugins/vos-hosting/api/domain-check.php?domain=' + encodeURIComponent(name), {
        credentials: 'same-origin'
    }).then(function(r){ return r.json(); }).then(function(d) {
        if (!d.success) {
            document.getElementById('domainSearchResult').innerHTML =
                '<p class="text-xs text-red-600 p-2">' + (d.message || 'error') + '</p>';
            return;
        }
        // 입력 TLD 와 일치하는 결과만 표시 (없으면 전체 표시)
        var match = (d.results || []).find(function(r){ return r.tld === tld; });
        var html;
        if (match) {
            html = renderDomainResult(match);
        } else if ((d.results || []).length > 0) {
            // TLD 매칭 안 되면 사용 가능한 다른 TLD 추천
            html = '<p class="text-[11px] text-zinc-500 dark:text-zinc-400 p-2">' +
                   '<?= htmlspecialchars(__('services.admin_neworder.dom_tld_unsupported')) ?>: <strong>' + tld + '</strong>. ' +
                   '<?= htmlspecialchars(__('services.admin_neworder.dom_alt_tlds')) ?>:</p>' +
                   '<div class="space-y-1">' +
                   d.results.slice(0, 5).map(renderDomainResult).join('') +
                   '</div>';
        } else {
            html = '<p class="text-xs text-amber-600 dark:text-amber-400 p-2"><?= htmlspecialchars(__('services.admin_neworder.dom_no_results')) ?></p>';
        }
        document.getElementById('domainSearchResult').innerHTML = html;
    }).catch(function(e) {
        document.getElementById('domainSearchResult').innerHTML =
            '<p class="text-xs text-red-600 p-2">' + (e && e.message || 'error') + '</p>';
    });
}

function renderDomainResult(r) {
    var available = !!r.available;
    var price = r.price ? '¥' + parseInt(r.price, 10).toLocaleString() : '-';
    var cls = available
        ? 'border border-emerald-300 dark:border-emerald-700 text-emerald-800 dark:text-emerald-200'
        : 'border border-red-300 dark:border-red-700 text-red-700 dark:text-red-300';
    var icon = available ? '✓' : '✗';
    var label = available ? '<?= htmlspecialchars(__('services.admin_neworder.dom_available')) ?>' : '<?= htmlspecialchars(__('services.admin_neworder.dom_taken')) ?>';
    var pickBtn = available
        ? ' <button type="button" onclick="pickDomain(\'' + r.fqdn + '\')" class="ml-2 text-[10px] text-blue-600 hover:underline"><?= htmlspecialchars(__('services.admin_neworder.dom_pick')) ?></button>'
        : '';
    return '<div class="flex items-center justify-between gap-2 px-3 py-2 rounded-lg ' + cls + '">' +
           '<span class="flex items-center gap-2">' +
           '<span class="font-bold">' + icon + '</span>' +
           '<span class="font-mono text-sm">' + r.fqdn + '</span>' +
           '<span class="text-[10px]">' + label + '</span>' +
           pickBtn +
           '</span>' +
           '<span class="text-xs tabular-nums whitespace-nowrap">' + price + '</span>' +
           '</div>';
}

function pickDomain(fqdn) {
    document.getElementById('domainName').value = fqdn;
    document.getElementById('domainSearchResult').innerHTML =
        '<p class="text-xs text-emerald-700 dark:text-emerald-300 p-2 border border-emerald-300 dark:border-emerald-700 rounded">✓ ' + fqdn + ' <?= htmlspecialchars(__('services.admin_neworder.dom_selected')) ?></p>';
}

function searchCustomer() {
    var q = document.getElementById('custSearchEmail').value.trim();
    if (!q) return;
    fetch(siteBaseUrl + '/plugins/vos-hosting/api/service-manage.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({ action: 'admin_search_user', email: q }),
    }).then(function(r){ return r.json(); }).then(function(d) {
        var box = document.getElementById('custSearchResult');
        if (!d.success || !d.users || d.users.length === 0) {
            box.innerHTML = '<p class="text-xs text-amber-600 dark:text-amber-400 p-2"><?= htmlspecialchars(__('services.admin_neworder.search_no_result')) ?></p>';
            document.getElementById('custUserId').value = '';
            return;
        }
        var html = '';
        d.users.forEach(function(u) {
            html += '<button type="button" onclick="pickCustomer(\'' + u.id + '\', \'' + (u.email||'').replace(/\'/g,'\\\\\'') + '\', \'' + (u.name||'').replace(/\'/g,'\\\\\'') + '\')" class="block w-full text-left px-3 py-2 mb-1 border border-gray-200 dark:border-zinc-700 rounded hover:bg-blue-50 dark:hover:bg-zinc-700/30">'
                  + '<span class="text-sm text-zinc-900 dark:text-white">' + u.email + '</span>'
                  + (u.name ? ' <span class="text-xs text-zinc-500">(' + u.name + ')</span>' : '')
                  + '</button>';
        });
        box.innerHTML = html;
    });
}

function pickCustomer(id, email, name) {
    document.getElementById('custUserId').value = id;
    document.getElementById('custSearchResult').innerHTML = '<p class="text-xs text-emerald-700 dark:text-emerald-300 p-2 border border-emerald-300 dark:border-emerald-700 rounded">✓ ' + email + (name ? ' (' + name + ')' : '') + '</p>';
}

function recalc() {
    var sel = document.getElementById('hostingPlan');
    var planPrice = parseInt(sel.options[sel.selectedIndex]?.dataset.price || 0, 10) || 0;
    var months = parseInt(document.getElementById('contractMonths').value, 10) || 12;
    var subtotal = planPrice * months;

    document.querySelectorAll('.addon-check:checked').forEach(function(cb) {
        var p = parseInt(cb.dataset.price || 0, 10) || 0;
        var ot = cb.dataset.onetime === '1';
        subtotal += ot ? p : (p * months);
    });

    var override = parseInt(document.getElementById('amountOverride').value, 10);
    if (!isNaN(override) && override >= 0) subtotal = override;

    var tax = Math.round(subtotal * TAX_RATE / 100);
    var total = subtotal + tax;
    document.getElementById('sumSubtotal').textContent = fmt(subtotal);
    document.getElementById('sumTax').textContent = fmt(tax);
    document.getElementById('sumTotal').textContent = fmt(total);
}
document.getElementById('amountOverride').addEventListener('input', recalc);

function onPayMethodChange() {
    var v = document.querySelector('input[name="payMethod"]:checked').value;
    document.getElementById('payCashBox').classList.toggle('hidden', v !== 'cash');
    document.getElementById('payCardBox').classList.toggle('hidden', v !== 'card');
    document.getElementById('payFreeBox').classList.toggle('hidden', v !== 'free');
    if (v === 'card') ensureCardElements();
}

var payjpInst = null, pjN = null, pjE = null, pjC = null;
function ensureCardElements() {
    var pubKey = <?= json_encode($_payPubKey) ?>;
    if (!pubKey || typeof Payjp !== 'function') return;
    if (payjpInst && pjN) return;

    var isDark = document.documentElement.classList.contains('dark');
    var style = {
        base: { color: isDark ? '#fff' : '#18181b', fontSize: '14px', '::placeholder': { color: isDark ? '#71717a' : '#a1a1aa' } },
        invalid: { color: '#ef4444' },
    };
    payjpInst = Payjp(pubKey);
    var els = payjpInst.elements();
    pjN = els.create('cardNumber', { style: style });
    pjE = els.create('cardExpiry', { style: style });
    pjC = els.create('cardCvc', { style: style });
    pjN.mount('#adm_pj_number');
    pjE.mount('#adm_pj_expiry');
    pjC.mount('#adm_pj_cvc');
}

function submitOrder() {
    var mode = document.querySelector('input[name="cust_mode"]:checked').value;
    var customer = { mode: mode };
    if (mode === 'existing') {
        var uid = document.getElementById('custUserId').value;
        if (!uid) { alert('<?= htmlspecialchars(__('services.admin_neworder.err_pick_customer')) ?>'); return; }
        customer.user_id = uid;
    } else {
        var email = document.getElementById('custEmail').value.trim();
        var name = document.getElementById('custName').value.trim();
        if (!email || !name) { alert('<?= htmlspecialchars(__('services.admin_neworder.err_email_name')) ?>'); return; }
        customer.email = email; customer.name = name;
        customer.phone = document.getElementById('custPhone').value.trim();
        customer.company = document.getElementById('custCompany').value.trim();
    }

    var planSel = document.getElementById('hostingPlan');
    var planId = planSel.value;
    if (!planId) { alert('<?= htmlspecialchars(__('services.admin_neworder.err_plan')) ?>'); return; }

    var addons = [];
    document.querySelectorAll('.addon-check:checked').forEach(function(cb) {
        addons.push({ id: cb.dataset.id, label: cb.dataset.label, price: parseInt(cb.dataset.price, 10) || 0, one_time: cb.dataset.onetime === '1' });
    });

    var domainOption = document.getElementById('domainOption').value;
    var domainName = document.getElementById('domainName').value.trim();
    if ((domainOption === 'new' || domainOption === 'existing') && !domainName) {
        alert('<?= htmlspecialchars(__('services.admin_neworder.err_domain_name')) ?>'); return;
    }

    var payMethod = document.querySelector('input[name="payMethod"]:checked').value;
    var pay = { method: payMethod };

    if (payMethod === 'cash') {
        var cash = parseInt(document.getElementById('cashReceived').value, 10) || 0;
        if (cash <= 0) { alert('<?= htmlspecialchars(__('services.admin_neworder.err_cash_amount')) ?>'); return; }
        pay.received = cash;
        finalSubmit(customer, planId, addons, domainOption, domainName, pay);
    } else if (payMethod === 'free') {
        var reason = document.getElementById('freeReason').value.trim();
        if (!reason) { alert('<?= htmlspecialchars(__('services.admin_neworder.err_free_reason')) ?>'); return; }
        pay.reason = reason;
        finalSubmit(customer, planId, addons, domainOption, domainName, pay);
    } else if (payMethod === 'card') {
        if (!payjpInst || !pjN) { alert('PAY.JP not ready'); return; }
        document.getElementById('submitBtn').disabled = true;
        payjpInst.createToken(pjN).then(function(r) {
            if (r.error) {
                document.getElementById('submitBtn').disabled = false;
                document.getElementById('payCardError').textContent = r.error.message || 'Card error';
                document.getElementById('payCardError').classList.remove('hidden');
                return;
            }
            pay.card_token = r.id;
            finalSubmit(customer, planId, addons, domainOption, domainName, pay);
        });
    }
}

function finalSubmit(customer, planId, addons, domainOption, domainName, pay) {
    document.getElementById('submitBtn').disabled = true;
    fetch(siteBaseUrl + '/plugins/vos-hosting/api/service-manage.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        credentials: 'same-origin',
        body: JSON.stringify({
            action: 'admin_create_order',
            customer: customer,
            hosting_plan: planId,
            contract_months: parseInt(document.getElementById('contractMonths').value, 10) || 12,
            domain_option: domainOption,
            domain_name: domainName,
            addons: addons,
            amount_override: parseInt(document.getElementById('amountOverride').value, 10) || null,
            payment: pay,
        }),
    }).then(function(r){ return r.json(); }).then(function(d) {
        document.getElementById('submitBtn').disabled = false;
        if (d.success) {
            alert('<?= htmlspecialchars(__('services.admin_neworder.success')) ?>');
            location.href = adminUrl + '/service-orders/' + d.order_number;
        } else {
            alert(d.message || 'error');
        }
    }).catch(function(e) {
        document.getElementById('submitBtn').disabled = false;
        alert(e && e.message || 'error');
    });
}
</script>

<?php include BASE_PATH . '/resources/views/admin/reservations/_foot.php'; ?>
