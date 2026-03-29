<?php
/**
 * 결제 취소/실패 - 예약 확인 화면으로 돌아가기
 * 예약은 pending 상태로 유지, 다시 결제 시도 가능
 */
$reservationId = $_GET['reservation_id'] ?? '';
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

// 예약 정보 조회
$reservation = null;
if ($reservationId) {
    try {
        $stmt = $pdo->prepare("SELECT * FROM {$prefix}reservations WHERE id = ? AND payment_status = 'unpaid' AND status = 'pending'");
        $stmt->execute([$reservationId]);
        $reservation = $stmt->fetch(PDO::FETCH_ASSOC);
    } catch (\Throwable $e) {}
}

// 서비스 목록
$svcList = [];
if ($reservation) {
    $svcStmt = $pdo->prepare("SELECT service_name, price, duration FROM {$prefix}reservation_services WHERE reservation_id = ? ORDER BY sort_order");
    $svcStmt->execute([$reservation['id']]);
    $svcList = $svcStmt->fetchAll(PDO::FETCH_ASSOC);
}

$currency = $siteSettings['service_currency'] ?? $config['currency'] ?? 'JPY';
$sym = ['KRW'=>'₩','JPY'=>'¥','USD'=>'$','EUR'=>'€'][$currency] ?? $currency;

$pageTitle = __('booking.error.payment_cancelled') ?? '결제 취소';
?>

<div class="max-w-lg mx-auto px-4 py-12">
    <!-- 결제 실패 안내 -->
    <div class="text-center mb-8">
        <svg class="w-16 h-16 text-amber-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/>
        </svg>
        <h1 class="text-2xl font-bold text-zinc-800 dark:text-zinc-100 mb-2"><?= __('booking.error.payment_cancelled') ?? '결제가 취소되었습니다' ?></h1>
        <p class="text-zinc-500 dark:text-zinc-400"><?= __('booking.error.payment_cancelled_retry') ?? '예약 정보를 확인하고 다시 결제를 진행해주세요.' ?></p>
    </div>

    <?php if ($reservation): ?>
    <!-- 예약 정보 확인 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6 mb-6">
        <h3 class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-4"><?= __('booking.confirm_info') ?? '예약 정보' ?></h3>

        <div class="space-y-3 text-sm">
            <!-- 예약번호 -->
            <div class="flex justify-between pb-3 border-b dark:border-zinc-700">
                <span class="text-zinc-500"><?= __('booking.reservation_number') ?? '예약번호' ?></span>
                <span class="font-mono font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($reservation['reservation_number']) ?></span>
            </div>

            <!-- 서비스 -->
            <?php foreach ($svcList as $s): ?>
            <div class="flex justify-between">
                <span class="text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($s['service_name']) ?></span>
                <span class="text-zinc-800 dark:text-zinc-200"><?= $sym ?><?= number_format((float)$s['price']) ?></span>
            </div>
            <?php endforeach; ?>

            <!-- 날짜/시간 -->
            <div class="flex justify-between pt-3 border-t dark:border-zinc-700">
                <span class="text-zinc-500"><?= __('booking.date_label') ?? '날짜' ?></span>
                <span class="text-zinc-800 dark:text-zinc-200"><?= $reservation['reservation_date'] ?> <?= substr($reservation['start_time'], 0, 5) ?></span>
            </div>

            <!-- 예약자 -->
            <div class="flex justify-between">
                <span class="text-zinc-500"><?= __('booking.customer') ?? '예약자' ?></span>
                <span class="text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($reservation['customer_name']) ?></span>
            </div>

            <!-- 결제 금액 -->
            <div class="flex justify-between pt-3 border-t dark:border-zinc-700 font-semibold">
                <span class="text-zinc-800 dark:text-zinc-100"><?= __('booking.payment.charge_amount') ?? '결제 금액' ?></span>
                <span class="text-blue-600 dark:text-blue-400 text-lg"><?= $sym ?><?= number_format((float)$reservation['final_amount']) ?></span>
            </div>
        </div>
    </div>

    <!-- 버튼 -->
    <div class="flex flex-col gap-3">
        <a href="<?= $baseUrl ?>/payment/checkout?reservation_id=<?= urlencode($reservationId) ?>"
           class="w-full px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition text-center flex items-center justify-center gap-2">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            <?= __('booking.payment.retry') ?? '다시 결제하기' ?>
        </a>
        <a href="<?= $baseUrl ?>/booking"
           class="w-full px-6 py-3 text-zinc-600 dark:text-zinc-300 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 font-medium rounded-lg hover:bg-zinc-50 transition text-center">
            <?= __('booking.payment.cancel_reservation') ?? '예약 취소하기' ?>
        </a>
    </div>

    <?php else: ?>
    <!-- 예약 정보 없음 -->
    <div class="text-center">
        <a href="<?= $baseUrl ?>/booking" class="px-6 py-3 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">
            <?= __('common.nav.booking') ?? '예약하기' ?>
        </a>
    </div>
    <?php endif; ?>
</div>
