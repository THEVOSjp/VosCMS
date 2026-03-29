<?php
/**
 * 예약 상세 - 결제 정보 사이드 패널
 * 필수 변수: reservation-detail-data.php에서 생성된 모든 변수
 */
$_svcList = $bundle && !empty($bundleServices) ? $bundleServices : $services;
$_serviceTotal = 0;
foreach ($_svcList as $_s) $_serviceTotal += (float)($_s['price'] ?? 0);
?>
<?php
// 영수증 URL 조회 (결제 완료된 경우)
$_receiptUrl = null;
if (in_array($reservation['payment_status'] ?? '', ['paid', 'partial'])) {
    $_rcptStmt = $pdo->prepare("SELECT receipt_url, payment_key FROM {$prefix}payments WHERE reservation_id = ? AND status = 'paid' ORDER BY created_at DESC LIMIT 1");
    $_rcptStmt->execute([$reservation['id']]);
    $_rcptRow = $_rcptStmt->fetch(PDO::FETCH_ASSOC);
    $_receiptUrl = $_rcptRow['receipt_url'] ?? null;

    // receipt_url이 없으면 Stripe에서 실시간 조회
    if (!$_receiptUrl && !empty($_rcptRow['payment_key'])) {
        try {
            require_once BASE_PATH . '/rzxlib/Modules/Payment/PaymentManager.php';
            require_once BASE_PATH . '/rzxlib/Modules/Payment/Gateways/StripeGateway.php';
            require_once BASE_PATH . '/rzxlib/Modules/Payment/Contracts/PaymentGatewayInterface.php';
            require_once BASE_PATH . '/rzxlib/Modules/Payment/DTO/PaymentResult.php';
            require_once BASE_PATH . '/rzxlib/Modules/Payment/DTO/RefundResult.php';
            $__mgr = new \RzxLib\Modules\Payment\PaymentManager($pdo, $prefix);
            if ($__mgr->isEnabled()) {
                $__gw = $__mgr->gateway();
                $__sess = $__gw->getTransaction($_rcptRow['payment_key']);
                $__piId = $__sess['payment_intent'] ?? null;
                if ($__piId) {
                    $__pi = $__gw->getTransaction($__piId);
                    $__chg = $__pi['latest_charge'] ?? null;
                    if (is_string($__chg)) $__chg = $__gw->getTransaction($__chg);
                    elseif (!is_array($__chg)) $__chg = $__pi['charges']['data'][0] ?? null;
                    $_receiptUrl = $__chg['receipt_url'] ?? null;
                    // DB에 저장 (캐시)
                    if ($_receiptUrl) {
                        $pdo->prepare("UPDATE {$prefix}payments SET receipt_url = ? WHERE payment_key = ?")->execute([$_receiptUrl, $_rcptRow['payment_key']]);
                    }
                }
            }
        } catch (\Throwable $e) {
            error_log('[Receipt] Error: ' . $e->getMessage());
        }
    }
}
?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6 sticky top-6">
    <div class="flex items-center justify-between mb-4">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100"><?= __('booking.detail.payment') ?></h3>
        <div class="flex items-center gap-1">
            <?php if ($_receiptUrl): ?>
            <a href="<?= htmlspecialchars($_receiptUrl) ?>" target="_blank" title="<?= __('booking.payment.receipt') ?? '영수증' ?>"
               class="p-1.5 text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </a>
            <?php endif; ?>
            <button onclick="window.print()" title="<?= __('booking.payment.print') ?? '인쇄' ?>"
                    class="p-1.5 text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
            </button>
        </div>
    </div>

    <!-- 포함 서비스 -->
    <div class="mb-4 pb-4 border-b border-zinc-200 dark:border-zinc-700">
        <h4 class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-2"><?= __('booking.detail.services') ?></h4>
        <div class="space-y-2">
            <?php foreach ($_svcList as $_s): ?>
            <div class="flex items-start justify-between gap-2 text-xs">
                <span class="text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($_s['name'] ?? $_s['service_name'] ?? '') ?></span>
                <span class="text-zinc-800 dark:text-zinc-200 font-medium shrink-0"><?= $fmtPrice($_s['price'] ?? 0) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 금액 -->
    <div class="space-y-2 text-sm mb-4 pb-4 border-b border-zinc-200 dark:border-zinc-700">
        <div class="flex justify-between font-semibold">
            <span class="text-zinc-600 dark:text-zinc-400"><?= __('booking.detail.total') ?></span>
            <span class="text-zinc-800 dark:text-zinc-200"><?= $fmtPrice($_serviceTotal) ?></span>
        </div>
        <?php if ($bundle && $bundlePrice > 0): ?>
        <div class="flex justify-between text-green-600 dark:text-green-400 font-semibold">
            <span><?= htmlspecialchars($bundleDisplayName) ?> <?= __('booking.payment.applied_price') ?? '적용가' ?></span>
            <span><?= $fmtPrice($bundlePrice) ?></span>
        </div>
        <?php endif; ?>
    </div>

    <!-- 지명비/할인/적립금 -->
    <div class="space-y-2 text-sm">
        <?php if (($reservation['designation_fee'] ?? 0) > 0): ?>
        <div class="flex justify-between text-amber-600 dark:text-amber-400">
            <span><?= __('booking.detail.designation_fee') ?></span>
            <span class="font-medium">+<?= $fmtPrice($reservation['designation_fee']) ?></span>
        </div>
        <?php endif; ?>
        <?php if (($reservation['discount_amount'] ?? 0) > 0): ?>
        <div class="flex justify-between text-green-600 dark:text-green-400">
            <span><?= __('booking.detail.discount') ?></span>
            <span class="font-medium">-<?= $fmtPrice($reservation['discount_amount']) ?></span>
        </div>
        <?php endif; ?>
        <?php if (($reservation['points_used'] ?? 0) > 0): ?>
        <div class="flex justify-between text-green-600 dark:text-green-400">
            <span><?= __('booking.detail.points_used') ?></span>
            <span class="font-medium">-<?= $fmtPrice($reservation['points_used']) ?></span>
        </div>
        <?php endif; ?>

        <div class="flex justify-between pt-2 border-t border-zinc-200 dark:border-zinc-700 font-semibold mt-3">
            <span class="text-zinc-800 dark:text-zinc-100"><?= __('booking.detail.final_amount') ?></span>
            <span class="text-blue-600 dark:text-blue-400 text-lg"><?= $fmtPrice($reservation['final_amount']) ?></span>
        </div>
        <div class="flex justify-between pt-2 text-sm">
            <span class="text-zinc-500 dark:text-zinc-400"><?= __('booking.detail.payment_status') ?></span>
            <?php if (($reservation['payment_status'] ?? '') === 'partial' && (float)($reservation['paid_amount'] ?? 0) > 0): ?>
            <span class="text-green-600 dark:text-green-400 font-medium"><?= __('booking.payment.deposit') ?? '예약금' ?> <?= $fmtPrice($reservation['paid_amount']) ?> <?= __('booking.payment.paid') ?? '결제완료' ?></span>
            <?php else: ?>
            <span class="text-zinc-700 dark:text-zinc-300"><?= $paymentLabel ?></span>
            <?php endif; ?>
        </div>
        <?php if (($reservation['payment_status'] ?? '') === 'partial' && (float)($reservation['paid_amount'] ?? 0) > 0): ?>
        <?php $_remainBalance = (float)$reservation['final_amount'] - (float)$reservation['paid_amount']; ?>
        <?php if ($_remainBalance > 0): ?>
        <div class="flex justify-between items-center pt-2 border-t border-zinc-200 dark:border-zinc-700 mt-2">
            <span class="text-sm font-semibold text-zinc-800 dark:text-zinc-100"><?= __('booking.payment.remaining_balance') ?></span>
            <span class="text-lg font-bold text-blue-600 dark:text-blue-400"><?= $fmtPrice($_remainBalance) ?></span>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>

    <?php
    // 환불 정책 안내 (취소 가능 상태일 때)
    if ($isCancellable):
        $_rpStmt = $pdo->prepare("SELECT `key`, `value` FROM {$prefix}settings WHERE `key` LIKE 'refund_%'");
        $_rpStmt->execute();
        $_rp = [];
        while ($_r = $_rpStmt->fetch(PDO::FETCH_ASSOC)) $_rp[$_r['key']] = $_r['value'];
        if (($_rp['refund_enabled'] ?? '0') === '1'):
            $_rpUnit = $_rp['refund_time_unit'] ?? 'hours';
            $_rpUL = $_rpUnit === 'days' ? (__('services.settings.general.refund_unit_days') ?? '일') : (__('services.settings.general.refund_unit_hours') ?? '시간');
            $_rpFull = (int)($_rp['refund_full_period'] ?? 24);
    ?>
    <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
        <h4 class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-2 flex items-center gap-1">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <?= __('booking.cancel.policy_title') ?? '취소 및 환불 안내' ?>
        </h4>
        <div class="text-[11px] space-y-0.5">
            <p class="text-green-600 dark:text-green-400">✓ <?= $_rpFull ?><?= $_rpUL ?> <?= __('services.settings.general.refund_preview_full') ?? '전 → 전액 환불 (100%)' ?></p>
            <?php for ($i = 1; $i <= 3; $i++):
                if (($_rp["refund_partial{$i}_enabled"] ?? '0') === '1'):
            ?>
            <p class="text-amber-600 dark:text-amber-400">△ <?= (int)($_rp["refund_partial{$i}_period"] ?? 0) ?><?= $_rpUL ?> <?= __('services.settings.general.refund_partial_suffix') ?? '전 취소 시' ?> <?= (int)($_rp["refund_partial{$i}_rate"] ?? 0) ?>%</p>
            <?php endif; endfor; ?>
            <p class="text-red-600 dark:text-red-400">✕ <?= __('services.settings.general.refund_preview_none') ?? '그 외 → 환불 불가' ?></p>
        </div>
    </div>
    <?php endif; endif; ?>
</div>
