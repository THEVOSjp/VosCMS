<?php
/**
 * 결제 성공 콜백 - Stripe Checkout 완료 후 리다이렉트
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

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$sessionId = $_GET['session_id'] ?? '';

if (empty($sessionId)) {
    echo '<div class="max-w-2xl mx-auto px-4 py-12 text-center"><p class="text-zinc-500">결제 정보가 없습니다.</p></div>';
    return;
}

$manager = new PaymentManager($pdo, $prefix);
$paymentService = new PaymentService($manager, $pdo, $prefix);

try {
    $result = $paymentService->confirm($sessionId);

    if ($result->isSuccessful()) {
        // 예약번호 조회
        $stmt = $pdo->prepare("SELECT r.reservation_number FROM {$prefix}payments p JOIN {$prefix}reservations r ON p.reservation_id = r.id WHERE p.payment_key = ? LIMIT 1");
        $stmt->execute([$sessionId]);
        $resNum = $stmt->fetchColumn();

        $pageTitle = __('booking.success') ?? '결제 완료';
        $currency = $siteSettings['service_currency'] ?? $config['currency'] ?? 'JPY';
        $sym = ['KRW'=>'₩','JPY'=>'¥','USD'=>'$','EUR'=>'€'][$currency] ?? $currency;
        ?>
        <div class="max-w-lg mx-auto px-4 py-16 text-center">
            <svg class="w-20 h-20 text-green-500 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <h1 class="text-2xl font-bold text-zinc-800 dark:text-zinc-100 mb-2"><?= __('booking.success') ?? '결제가 완료되었습니다' ?></h1>
            <p class="text-zinc-500 dark:text-zinc-400 mb-6"><?= __('booking.success_desc') ?? '예약이 확정되었습니다.' ?></p>

            <?php if ($resNum): ?>
            <div class="bg-zinc-50 dark:bg-zinc-800 rounded-xl p-4 mb-6">
                <p class="text-xs text-zinc-500 mb-1"><?= __('booking.reservation_number') ?? '예약번호' ?></p>
                <p class="text-lg font-mono font-bold text-blue-600 dark:text-blue-400"><?= htmlspecialchars($resNum) ?></p>
            </div>
            <?php endif; ?>

            <div class="bg-green-50 dark:bg-green-900/20 rounded-xl p-4 mb-6">
                <p class="text-sm text-green-700 dark:text-green-300">
                    <?= $sym ?><?= number_format($result->amount) ?> <?= __('booking.payment.paid') ?? '결제 완료' ?>
                    <?php if ($result->method === 'card' && $result->methodDetail): ?>
                    (<?= $result->methodDetail['brand'] ?? '' ?> ****<?= $result->methodDetail['last4'] ?? '' ?>)
                    <?php endif; ?>
                </p>
            </div>

            <div class="flex gap-3 justify-center">
                <a href="<?= $baseUrl ?>/" class="px-5 py-2.5 text-sm font-medium text-zinc-600 dark:text-zinc-300 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 transition">
                    <?= __('common.nav.home') ?? '홈' ?>
                </a>
                <?php if ($resNum): ?>
                <a href="<?= $baseUrl ?>/booking/detail/<?= urlencode($resNum) ?>" class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">
                    <?= __('booking.detail.title') ?? '예약 상세' ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
        <?php
    } else {
        ?>
        <div class="max-w-lg mx-auto px-4 py-16 text-center">
            <svg class="w-20 h-20 text-red-500 mx-auto mb-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
            </svg>
            <h1 class="text-2xl font-bold text-zinc-800 dark:text-zinc-100 mb-2"><?= __('booking.error.payment_failed') ?? '결제 실패' ?></h1>
            <p class="text-zinc-500 dark:text-zinc-400 mb-6"><?= htmlspecialchars($result->failureMessage ?? '결제 처리 중 문제가 발생했습니다.') ?></p>
            <a href="<?= $baseUrl ?>/booking" class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">
                <?= __('common.nav.booking') ?? '예약하기' ?>
            </a>
        </div>
        <?php
    }
} catch (\Throwable $e) {
    error_log('Payment success callback error: ' . $e->getMessage());
    echo '<div class="max-w-2xl mx-auto px-4 py-12 text-center"><p class="text-red-500">결제 확인 중 오류: ' . htmlspecialchars($e->getMessage()) . '</p></div>';
}
