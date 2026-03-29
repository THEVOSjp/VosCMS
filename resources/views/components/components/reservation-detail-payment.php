<?php
/**
 * 예약 상세 - 결제 정보 사이드 패널
 * 필수 변수: reservation-detail-data.php에서 생성된 모든 변수
 */
$_svcList = $bundle && !empty($bundleServices) ? $bundleServices : $services;
$_serviceTotal = 0;
foreach ($_svcList as $_s) $_serviceTotal += (float)($_s['price'] ?? 0);
?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6 sticky top-6">
    <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100 mb-4"><?= __('booking.detail.payment') ?></h3>

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
        <div class="flex justify-between">
            <span class="text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($bundleDisplayName) ?></span>
            <span class="text-zinc-800 dark:text-zinc-200 font-medium"><?= $fmtPrice($bundlePrice) ?></span>
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
            <span class="text-zinc-700 dark:text-zinc-300"><?= $paymentLabel ?></span>
        </div>
    </div>
</div>
