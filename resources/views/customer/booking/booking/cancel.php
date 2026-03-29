<?php
/**
 * RezlyX - 예약 취소 페이지 (환불 정책 적용)
 */
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

if (empty($reservationNumber)) {
    http_response_code(404);
    echo '<div class="max-w-2xl mx-auto px-4 py-12 text-center"><p class="text-zinc-500">' . __('booking.lookup.not_found') . '</p></div>';
    return;
}

$stmt = $pdo->prepare("SELECT * FROM {$prefix}reservations WHERE reservation_number = ?");
$stmt->execute([$reservationNumber]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reservation || !in_array($reservation['status'], ['pending', 'confirmed'])) {
    echo '<div class="max-w-2xl mx-auto px-4 py-12 text-center"><p class="text-zinc-500">' . __('booking.cancel.cannot_cancel') . '</p></div>';
    return;
}

$pageTitle = __('booking.cancel.title') . ' - ' . $reservationNumber;
$currency = $siteSettings['service_currency'] ?? $config['currency'] ?? 'JPY';
$sym = ['KRW'=>'₩','JPY'=>'¥','USD'=>'$','EUR'=>'€'][$currency] ?? $currency;

// 환불 정책 설정 로드
$_rfStmt = $pdo->prepare("SELECT `key`, `value` FROM {$prefix}settings WHERE `key` LIKE 'refund_%'");
$_rfStmt->execute();
$_rf = [];
while ($r = $_rfStmt->fetch(PDO::FETCH_ASSOC)) $_rf[$r['key']] = $r['value'];

$refundEnabled = ($_rf['refund_enabled'] ?? '0') === '1';
$rfTimeUnit = $_rf['refund_time_unit'] ?? 'hours';
$rfFullPeriod = (int)($_rf['refund_full_period'] ?? 24);
$rfPartials = [];
for ($i = 1; $i <= 3; $i++) {
    if (($_rf["refund_partial{$i}_enabled"] ?? '0') === '1') {
        $rfPartials[] = [
            'period' => (int)($_rf["refund_partial{$i}_period"] ?? 0),
            'rate' => (int)($_rf["refund_partial{$i}_rate"] ?? 0),
        ];
    }
}
// period 내림차순 정렬
usort($rfPartials, fn($a, $b) => $b['period'] - $a['period']);

$paidAmount = (float)($reservation['paid_amount'] ?? 0);

// 환불율 계산
$refundRate = 0;
$refundAmount = 0;
$refundLabel = __('booking.cancel.no_refund') ?? '환불 불가';

if ($refundEnabled && $paidAmount > 0) {
    // 예약 시간까지 남은 시간 계산
    $reservationDt = new DateTime($reservation['reservation_date'] . ' ' . $reservation['start_time']);
    $now = new DateTime();
    $diff = $now->diff($reservationDt);
    $hoursLeft = ($diff->invert ? 0 : ($diff->days * 24 + $diff->h + ($diff->i / 60)));
    if ($rfTimeUnit === 'days') $hoursLeft = $hoursLeft / 24;

    // 전액 환불
    if ($hoursLeft >= $rfFullPeriod) {
        $refundRate = 100;
        $refundLabel = __('services.settings.general.refund_full_title') ?? '전액 환불';
    } else {
        // 부분 환불 단계 확인 (period 내림차순)
        foreach ($rfPartials as $p) {
            if ($hoursLeft >= $p['period']) {
                $refundRate = $p['rate'];
                $refundLabel = ($p['rate'] . '% ' . (__('services.settings.general.refund_word') ?? '환불'));
                break;
            }
        }
    }
    $refundAmount = (int)floor($paidAmount * $refundRate / 100);
} elseif ($paidAmount <= 0) {
    // 미결제 예약은 바로 취소
    $refundRate = -1; // 결제 없음 표시
    $refundLabel = __('booking.cancel.no_payment') ?? '결제 내역 없음';
}

$unitLabel = $rfTimeUnit === 'days' ? (__('services.settings.general.refund_unit_days') ?? '일') : (__('services.settings.general.refund_unit_hours') ?? '시간');

$success = false;
$error = '';

// 취소 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = trim($_POST['reason'] ?? '');

    try {
        $pdo->beginTransaction();

        // 예약 상태 변경
        $pdo->prepare("UPDATE {$prefix}reservations SET status = 'cancelled', cancel_reason = ?, cancelled_at = NOW() WHERE id = ?")
            ->execute([$reason, $reservation['id']]);

        // Stripe 환불 처리
        if ($refundAmount > 0 && $paidAmount > 0) {
            // 결제 레코드 조회
            $payStmt = $pdo->prepare("SELECT * FROM {$prefix}payments WHERE reservation_id = ? AND status = 'paid' ORDER BY created_at DESC LIMIT 1");
            $payStmt->execute([$reservation['id']]);
            $payment = $payStmt->fetch(PDO::FETCH_ASSOC);

            if ($payment && $payment['payment_key']) {
                // Stripe 환불 API 호출
                require_once BASE_PATH . '/rzxlib/Modules/Payment/PaymentManager.php';
                require_once BASE_PATH . '/rzxlib/Modules/Payment/Gateways/StripeGateway.php';
                require_once BASE_PATH . '/rzxlib/Modules/Payment/Contracts/PaymentGatewayInterface.php';
                require_once BASE_PATH . '/rzxlib/Modules/Payment/DTO/PaymentResult.php';
                require_once BASE_PATH . '/rzxlib/Modules/Payment/DTO/RefundResult.php';

                $manager = new \RzxLib\Modules\Payment\PaymentManager($pdo, $prefix);
                $gateway = $manager->gateway();
                $refundResult = $gateway->refund($payment['payment_key'], $refundAmount, $reason ?: 'Customer cancelled');

                // 환불 기록
                $pdo->prepare("INSERT INTO {$prefix}refunds (payment_id, refund_key, amount, reason, status, refunded_at, requested_by) VALUES (?, ?, ?, ?, ?, NOW(), ?)")
                    ->execute([
                        $payment['id'],
                        $refundResult->refundId,
                        $refundAmount,
                        $reason ?: 'Customer cancelled',
                        $refundResult->success ? 'completed' : 'failed',
                        $reservation['user_id'],
                    ]);

                // 결제 상태 업데이트
                if ($refundResult->success) {
                    $newPayStatus = ($refundAmount >= $paidAmount) ? 'refunded' : 'partial_cancelled';
                    $pdo->prepare("UPDATE {$prefix}payments SET status = ?, cancelled_at = NOW(), cancel_reason = ? WHERE id = ?")
                        ->execute([$newPayStatus, $reason, $payment['id']]);
                    $pdo->prepare("UPDATE {$prefix}reservations SET payment_status = ? WHERE id = ?")
                        ->execute([$newPayStatus, $reservation['id']]);
                }
            }
        }

        $pdo->commit();
        $success = true;
    } catch (\Throwable $e) {
        $pdo->rollBack();
        error_log('[Cancel] Error: ' . $e->getMessage());
        $error = $e->getMessage();
    }
}
?>

<div class="max-w-lg mx-auto px-4 py-12">
    <?php if ($success): ?>
    <!-- 취소 완료 -->
    <div class="text-center">
        <div class="w-16 h-16 mx-auto mb-4 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
            <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h1 class="text-2xl font-bold text-zinc-800 dark:text-zinc-100 mb-2"><?= __('booking.cancel.success') ?></h1>
        <p class="text-sm text-zinc-500 mb-4 font-mono"><?= htmlspecialchars($reservationNumber) ?></p>

        <?php if ($paidAmount > 0): ?>
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-5 mb-6 border border-zinc-200 dark:border-zinc-700 text-left">
            <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-3"><?= __('booking.cancel.refund_detail') ?? '환불 상세' ?></h3>
            <div class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <span class="text-zinc-500"><?= __('booking.cancel.paid_amount') ?? '결제 금액' ?></span>
                    <span class="text-zinc-800 dark:text-zinc-200"><?= $sym ?><?= number_format($paidAmount) ?></span>
                </div>
                <div class="flex justify-between">
                    <span class="text-zinc-500"><?= __('booking.cancel.refund_policy') ?? '적용 정책' ?></span>
                    <span class="font-medium <?= $refundRate > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' ?>"><?= $refundLabel ?></span>
                </div>
                <div class="flex justify-between pt-2 border-t dark:border-zinc-700 font-semibold">
                    <span class="text-zinc-800 dark:text-zinc-100"><?= __('booking.cancel.refund_amount') ?? '환불 금액' ?></span>
                    <span class="text-lg text-blue-600 dark:text-blue-400"><?= $sym ?><?= number_format($refundAmount) ?></span>
                </div>
            </div>

            <?php if ($refundAmount > 0): ?>
            <div class="mt-3 pt-3 border-t dark:border-zinc-700 flex items-center gap-2">
                <svg class="w-4 h-4 text-green-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <p class="text-xs text-green-600 dark:text-green-400"><?= __('booking.cancel.refund_processed') ?? '환불 처리 완료' ?> — <?= __('booking.cancel.refund_card_notice') ?? '카드사에 따라 3~5영업일 내 환불됩니다.' ?></p>
            </div>
            <?php elseif ($refundRate === 0): ?>
            <div class="mt-3 pt-3 border-t dark:border-zinc-700">
                <p class="text-xs text-red-600 dark:text-red-400"><?= __('booking.cancel.no_refund_notice') ?? '환불 정책에 따라 환불이 불가합니다.' ?></p>
            </div>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <a href="<?= $baseUrl ?>/lookup" class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition"><?= __('booking.detail.back_to_lookup') ?></a>
    </div>
    <?php else: ?>
    <!-- 취소 확인 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <h1 class="text-xl font-bold text-zinc-800 dark:text-zinc-100 mb-2"><?= __('booking.cancel.title') ?></h1>
        <p class="text-sm text-zinc-500 mb-1 font-mono"><?= htmlspecialchars($reservationNumber) ?></p>
        <p class="text-sm text-zinc-500 mb-6"><?= date('Y-m-d', strtotime($reservation['reservation_date'])) ?> <?= date('H:i', strtotime($reservation['start_time'])) ?></p>

        <?php if ($error): ?>
        <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg mb-4">
            <p class="text-sm text-red-700 dark:text-red-300"><?= htmlspecialchars($error) ?></p>
        </div>
        <?php endif; ?>

        <!-- 환불 정보 -->
        <?php if ($refundEnabled && $paidAmount > 0): ?>
        <div class="bg-zinc-50 dark:bg-zinc-700/30 rounded-lg p-4 mb-6 space-y-2">
            <div class="flex justify-between text-sm">
                <span class="text-zinc-600 dark:text-zinc-400"><?= __('booking.cancel.paid_amount') ?? '결제 금액' ?></span>
                <span class="text-zinc-800 dark:text-zinc-200 font-medium"><?= $sym ?><?= number_format($paidAmount) ?></span>
            </div>
            <div class="flex justify-between text-sm">
                <span class="text-zinc-600 dark:text-zinc-400"><?= __('booking.cancel.refund_policy') ?? '적용 정책' ?></span>
                <span class="<?= $refundRate > 0 ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400' ?> font-medium"><?= $refundLabel ?></span>
            </div>
            <div class="flex justify-between text-sm pt-2 border-t dark:border-zinc-600 font-semibold">
                <span class="text-zinc-800 dark:text-zinc-100"><?= __('booking.cancel.refund_amount') ?? '환불 예정 금액' ?></span>
                <span class="text-blue-600 dark:text-blue-400 text-lg"><?= $sym ?><?= number_format($refundAmount) ?></span>
            </div>
        </div>
        <?php endif; ?>

        <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg mb-6">
            <p class="text-sm text-red-700 dark:text-red-300"><?= __('booking.cancel.confirm') ?></p>
        </div>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('booking.cancel.reason') ?></label>
                <textarea name="reason" rows="3" class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200" placeholder="<?= __('booking.cancel.reason_placeholder') ?>"></textarea>
            </div>
            <div class="flex gap-3">
                <a href="<?= $baseUrl ?>/booking/detail/<?= urlencode($reservationNumber) ?>" class="flex-1 px-4 py-2.5 text-sm font-medium text-zinc-600 dark:text-zinc-300 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition text-center"><?= __('board.cancel') ?></a>
                <button type="submit" class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition"><?= __('booking.cancel.submit') ?></button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>
