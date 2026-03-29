<?php
/**
 * 결제 체크아웃 - Stripe Embedded Checkout (페이지 내 임베드)
 */
require_once BASE_PATH . '/rzxlib/Modules/Payment/PaymentManager.php';
require_once BASE_PATH . '/rzxlib/Modules/Payment/Services/PaymentService.php';
require_once BASE_PATH . '/rzxlib/Modules/Payment/DTO/PaymentRequest.php';
require_once BASE_PATH . '/rzxlib/Modules/Payment/Gateways/StripeGateway.php';
require_once BASE_PATH . '/rzxlib/Modules/Payment/Contracts/PaymentGatewayInterface.php';
require_once BASE_PATH . '/rzxlib/Modules/Payment/DTO/PaymentResult.php';
require_once BASE_PATH . '/rzxlib/Modules/Payment/DTO/RefundResult.php';

use RzxLib\Modules\Payment\PaymentManager;
use RzxLib\Modules\Payment\Services\PaymentService;
use RzxLib\Modules\Payment\DTO\PaymentRequest;

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$manager = new PaymentManager($pdo, $prefix);

if (!$manager->isEnabled()) {
    echo '<div class="max-w-2xl mx-auto px-4 py-12 text-center"><p class="text-zinc-500">' . (__('settings.payment_config.status_incomplete') ?? '결제 시스템이 설정되지 않았습니다.') . '</p></div>';
    return;
}

$reservationId = $_GET['reservation_id'] ?? '';
if (empty($reservationId)) {
    echo '<div class="max-w-2xl mx-auto px-4 py-12 text-center"><p class="text-zinc-500">예약 정보가 없습니다.</p></div>';
    return;
}

// 예약 조회
$stmt = $pdo->prepare("SELECT * FROM {$prefix}reservations WHERE id = ?");
$stmt->execute([$reservationId]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reservation) {
    echo '<div class="max-w-2xl mx-auto px-4 py-12 text-center"><p class="text-zinc-500">' . __('booking.lookup.not_found') . '</p></div>';
    return;
}

// 이미 결제 완료인 경우
if ($reservation['payment_status'] === 'paid') {
    header('Location: ' . $baseUrl . '/booking/detail/' . urlencode($reservation['reservation_number']));
    exit;
}

// 결제금액 계산: 예약금 설정 확인
$finalAmount = (float)$reservation['final_amount'];

// 예약금 설정 (siteSettings 또는 DB 직접 조회)
$_depSettings = [];
$_depStmt = $pdo->prepare("SELECT `key`, `value` FROM {$prefix}settings WHERE `key` LIKE 'service_deposit%'");
$_depStmt->execute();
while ($_dr = $_depStmt->fetch(PDO::FETCH_ASSOC)) $_depSettings[$_dr['key']] = $_dr['value'];

$depositEnabled = ($_depSettings['service_deposit_enabled'] ?? '0') === '1';
$chargeAmount = $finalAmount; // 기본: 전액
$isDeposit = false;

if ($depositEnabled) {
    $depositType = $_depSettings['service_deposit_type'] ?? 'fixed';
    if ($depositType === 'percent') {
        $depositPercent = (float)($_depSettings['service_deposit_percent'] ?? 0);
        $chargeAmount = ceil($finalAmount * $depositPercent / 100);
    } else {
        $chargeAmount = (float)($_depSettings['service_deposit_amount'] ?? 0);
        if ($chargeAmount > $finalAmount) $chargeAmount = $finalAmount;
    }
    if ($chargeAmount > 0 && $chargeAmount < $finalAmount) $isDeposit = true;
    if ($chargeAmount <= 0) $chargeAmount = $finalAmount;
}

// 번들 정보 조회
$_ckBundleId = $reservation['bundle_id'] ?? null;
$_ckBundlePrice = $reservation['bundle_price'] ?? null;
$_ckBundleName = null;
$_ckServiceTotal = (float)$reservation['total_amount']; // 서비스 원래 가격 합계
if ($_ckBundleId) {
    // 번들 표시명 (DB 설정값)
    $_ckBdnStmt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = 'bundle_display_name'");
    $_ckBdnStmt->execute();
    $_ckBundleName = $_ckBdnStmt->fetchColumn() ?: (__('booking.detail.bundle') ?? '쿠폰');
    // 다국어
    $currentLocale = $currentLocale ?? $config['locale'] ?? 'ko';
    if (function_exists('db_trans')) {
        $_tr = db_trans('bundle_display_name', $currentLocale);
        if ($_tr) $_ckBundleName = $_tr;
    } else {
        $_trStmt = $pdo->prepare("SELECT content FROM {$prefix}translations WHERE lang_key = 'bundle_display_name' AND locale = ?");
        $_trStmt->execute([$currentLocale]);
        $_trVal = $_trStmt->fetchColumn();
        if ($_trVal) $_ckBundleName = $_trVal;
    }
}

$currency = $siteSettings['service_currency'] ?? $config['currency'] ?? 'JPY';
$currencySymbol = ['KRW'=>'₩','JPY'=>'¥','USD'=>'$','EUR'=>'€'][$currency] ?? $currency;

$paymentService = new PaymentService($manager, $pdo, $prefix);

try {
    $description = __('booking.detail.title') . ' - ' . $reservation['reservation_number'];
    if ($isDeposit) $description .= ' (' . (__('booking.payment.deposit') ?? '예약금') . ')';

    $request = new PaymentRequest([
        'amount' => (int)$chargeAmount,
        'currency' => $currency,
        'description' => $description,
        'reservation_id' => $reservation['id'],
        'user_id' => $reservation['user_id'],
        'customer_email' => $reservation['customer_email'],
        'customer_name' => $reservation['customer_name'],
        'success_url' => $baseUrl . '/payment/success',
        'cancel_url' => $baseUrl . '/payment/cancel?reservation_id=' . urlencode($reservationId),
        'metadata' => [
            'reservation_number' => $reservation['reservation_number'],
            'is_deposit' => $isDeposit ? '1' : '0',
            'locale' => $currentLocale,
        ],
    ]);

    $session = $paymentService->prepare($request);
    $clientSecret = $session['client_secret'] ?? null;
    $checkoutUrl = $session['checkout_url'] ?? null;
    $publicKey = $manager->getPublicKey();

    error_log('[Payment] Session created: ' . ($session['session_id'] ?? '') . ', amount: ' . $chargeAmount);
} catch (\Throwable $e) {
    error_log('Payment checkout error: ' . $e->getMessage());
    echo '<div class="max-w-2xl mx-auto px-4 py-12 text-center"><p class="text-red-500">결제 처리 중 오류: ' . htmlspecialchars($e->getMessage()) . '</p></div>';
    return;
}

// 서비스 목록 (다국어 적용)
$currentLocale = $currentLocale ?? $config['locale'] ?? 'ko';
$svcStmt = $pdo->prepare("SELECT rs.service_id, rs.service_name, rs.price, rs.duration FROM {$prefix}reservation_services rs WHERE rs.reservation_id = ? ORDER BY rs.sort_order");
$svcStmt->execute([$reservation['id']]);
$svcList = $svcStmt->fetchAll(PDO::FETCH_ASSOC);
// 다국어 번역 일괄 조회
$_svcIds = array_filter(array_column($svcList, 'service_id'));
if (!empty($_svcIds)) {
    $_trKeys = array_map(fn($id) => "service.{$id}.name", $_svcIds);
    $_trPh = implode(',', array_fill(0, count($_trKeys), '?'));
    $_trStmt = $pdo->prepare("SELECT lang_key, content FROM {$prefix}translations WHERE lang_key IN ({$_trPh}) AND locale = ?");
    $_trStmt->execute(array_merge($_trKeys, [$currentLocale]));
    $_trMap = [];
    while ($_tr = $_trStmt->fetch(PDO::FETCH_ASSOC)) $_trMap[$_tr['lang_key']] = $_tr['content'];
    foreach ($svcList as &$_sv) {
        $key = 'service.' . $_sv['service_id'] . '.name';
        if (!empty($_trMap[$key])) $_sv['service_name'] = $_trMap[$key];
    }
    unset($_sv);
}

$pageTitle = __('booking.payment.pay_now') ?? '결제하기';
?>

<div class="max-w-4xl mx-auto px-4 sm:px-6 py-8">
    <div class="flex flex-col lg:flex-row gap-6">

        <!-- 좌측: 주문 요약 -->
        <div class="w-full lg:w-80 shrink-0 order-2 lg:order-1">
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6 sticky top-6">
                <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100 mb-4"><?= __('booking.detail.title') ?? '예약 상세' ?></h3>

                <div class="text-sm space-y-3 mb-4 pb-4 border-b dark:border-zinc-700">
                    <div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= __('booking.reservation_number') ?? '예약번호' ?></p>
                        <p class="font-mono font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($reservation['reservation_number']) ?></p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= __('booking.date_label') ?? '예약일' ?></p>
                        <p class="font-medium text-zinc-800 dark:text-zinc-200"><?= $reservation['reservation_date'] ?> <?= substr($reservation['start_time'], 0, 5) ?></p>
                    </div>
                </div>

                <!-- 서비스 내역 -->
                <div class="space-y-2 mb-4 pb-4 border-b dark:border-zinc-700">
                    <?php foreach ($svcList as $s): ?>
                    <div class="flex justify-between text-sm">
                        <span class="text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($s['service_name']) ?></span>
                        <span class="text-zinc-800 dark:text-zinc-200"><?= $currencySymbol ?><?= number_format((float)$s['price']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- 합계 -->
                <div class="space-y-2 text-sm">
                    <div class="flex justify-between font-semibold">
                        <span class="text-zinc-600 dark:text-zinc-400"><?= __('booking.detail.total') ?? '합계' ?></span>
                        <span class="text-zinc-800 dark:text-zinc-200"><?= $currencySymbol ?><?= number_format($_ckServiceTotal) ?></span>
                    </div>

                    <?php if ($_ckBundleId && $_ckBundlePrice !== null): ?>
                    <!-- 번들(쿠폰) 적용 -->
                    <div class="flex justify-between text-green-600 dark:text-green-400">
                        <span><?= htmlspecialchars($_ckBundleName) ?> <?= __('booking.payment.applied_price') ?? '적용가' ?></span>
                        <span class="font-medium"><?= $currencySymbol ?><?= number_format((float)$_ckBundlePrice) ?></span>
                    </div>
                    <?php endif; ?>

                    <?php $_ckDesignationFee = (float)($reservation['designation_fee'] ?? 0); ?>
                    <?php if ($_ckDesignationFee > 0): ?>
                    <!-- 지명료 -->
                    <div class="flex justify-between text-amber-600 dark:text-amber-400">
                        <span><?= __('booking.detail.designation_fee') ?? '지명료' ?></span>
                        <span class="font-medium">+<?= $currencySymbol ?><?= number_format($_ckDesignationFee) ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- 최종 결제금액 -->
                <div class="mt-3 pt-3 border-t dark:border-zinc-700 space-y-2">
                    <div class="flex justify-between items-center">
                        <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-100"><?= __('booking.detail.final_amount') ?? '최종 결제금액' ?></span>
                        <span class="text-lg font-bold text-zinc-800 dark:text-zinc-200"><?= $currencySymbol ?><?= number_format($finalAmount) ?></span>
                    </div>

                    <?php if ($isDeposit): ?>
                    <div class="flex justify-between text-blue-600 dark:text-blue-400 font-semibold text-sm">
                        <span><?= __('booking.payment.deposit') ?? '예약금' ?></span>
                        <span><?= $currencySymbol ?><?= number_format($chargeAmount) ?></span>
                    </div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= __('booking.payment.deposit_notice') ?? '잔액은 방문 시 현장에서 결제합니다.' ?></p>
                    <?php endif; ?>
                </div>

                <!-- 결제 금액 강조 -->
                <div class="mt-3 pt-3 border-t dark:border-zinc-700">
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-bold text-zinc-800 dark:text-zinc-100"><?= __('booking.payment.charge_amount') ?? '결제 금액' ?></span>
                        <span class="text-2xl font-bold text-blue-600 dark:text-blue-400"><?= $currencySymbol ?><?= number_format($chargeAmount) ?></span>
                    </div>
                </div>
            </div>
        </div>

        <!-- 우측: Stripe Embedded Checkout -->
        <div class="flex-1 order-1 lg:order-2">
            <h1 class="text-2xl font-bold text-zinc-800 dark:text-zinc-100 mb-6 flex items-center gap-3">
                <svg class="w-7 h-7 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                <?= __('booking.payment.pay_now') ?? '결제하기' ?>
            </h1>

            <!-- Stripe Embedded Checkout 영역 -->
            <div id="checkout-container" class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6 min-h-[400px] flex items-center justify-center">
                <div id="checkout-loading" class="text-center">
                    <svg class="animate-spin w-8 h-8 text-blue-600 mx-auto mb-3" fill="none" viewBox="0 0 24 24">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path>
                    </svg>
                    <p class="text-zinc-500 dark:text-zinc-400"><?= __('booking.payment.loading') ?? '결제 화면을 불러오는 중...' ?></p>
                </div>
                <div id="checkout-element" class="w-full hidden"></div>
            </div>

            <!-- 취소 버튼 -->
            <div class="mt-4 text-center">
                <a href="<?= $baseUrl ?>/booking/detail/<?= urlencode($reservation['reservation_number']) ?>"
                   class="text-sm text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300 transition">
                    &larr; <?= __('booking.payment.back_to_detail') ?? '예약 상세로 돌아가기' ?>
                </a>
            </div>
        </div>
    </div>
</div>

<script src="https://js.stripe.com/v3/"></script>
<script>
(function() {
    console.log('[Payment] Initializing Stripe Embedded Checkout');
    var stripe = Stripe('<?= htmlspecialchars($publicKey) ?>');

    <?php if ($clientSecret): ?>
    // Embedded Checkout 방식
    stripe.initEmbeddedCheckout({
        clientSecret: '<?= htmlspecialchars($clientSecret) ?>'
    }).then(function(checkout) {
        console.log('[Payment] Embedded checkout mounted');
        document.getElementById('checkout-loading').classList.add('hidden');
        var el = document.getElementById('checkout-element');
        el.classList.remove('hidden');
        checkout.mount('#checkout-element');
    }).catch(function(err) {
        console.error('[Payment] Embedded checkout error:', err);
        // Fallback: redirect 방식
        <?php if ($checkoutUrl): ?>
        window.location.href = '<?= htmlspecialchars($checkoutUrl) ?>';
        <?php else: ?>
        document.getElementById('checkout-loading').innerHTML = '<p class="text-red-500">결제 화면 로드 실패: ' + err.message + '</p>';
        <?php endif; ?>
    });
    <?php else: ?>
    // Redirect 방식 (Embedded 미지원 시 폴백)
    <?php if ($checkoutUrl): ?>
    window.location.href = '<?= htmlspecialchars($checkoutUrl) ?>';
    <?php else: ?>
    document.getElementById('checkout-loading').innerHTML = '<p class="text-red-500">결제 세션 생성 실패</p>';
    <?php endif; ?>
    <?php endif; ?>
})();
</script>
