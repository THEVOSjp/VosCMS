<?php
/**
 * 예약 상세 - 공통 UI 모듈
 *
 * 필수 변수: reservation-detail-data.php에서 생성된 모든 변수
 * 선택 변수: $backUrl, $backLabel (뒤로가기 버튼)
 */
$_backUrl = $backUrl ?? ($baseUrl . '/lookup');
$_backLabel = $backLabel ?? __('booking.detail.back_to_lookup');

// 결제 활성화 여부 확인
$_paymentEnabled = false;
try {
    $_payConf = json_decode($siteSettings['payment_config'] ?? '{}', true) ?: [];
    $_paymentEnabled = ($_payConf['enabled'] ?? '0') === '1' && !empty($_payConf['public_key']) && !empty($_payConf['secret_key']);
} catch (\Throwable $e) {}
$_needsPayment = $_paymentEnabled && ($reservation['payment_status'] ?? 'unpaid') === 'unpaid';
?>

<!-- 헤더 -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-zinc-800 dark:text-zinc-100"><?= __('booking.detail.title') ?></h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1 font-mono"><?= htmlspecialchars($reservation['reservation_number']) ?></p>
    </div>
    <span class="px-3 py-1 text-sm font-medium rounded-full <?= $statusClass ?>"><?= $statusLabel ?></span>
</div>

<!-- 고객 정보 -->
<div class="relative rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden mb-6 bg-white dark:bg-zinc-800">
    <?php if ($backgroundImage): ?>
    <div class="absolute inset-0 opacity-15 dark:opacity-10 bg-cover bg-center" style="background-image: url('<?= htmlspecialchars($backgroundImage) ?>')"></div>
    <?php else: ?>
    <div class="absolute inset-0 opacity-10 bg-gradient-to-r from-blue-500 to-purple-500"></div>
    <?php endif; ?>
    <div class="relative p-6 z-10">
        <div class="flex gap-6 items-start">
            <div class="shrink-0">
                <div class="w-20 h-20 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white font-bold text-2xl border-4 border-white dark:border-zinc-700 shadow-lg">
                    <?= mb_substr($reservation['customer_name'], 0, 1) ?>
                </div>
            </div>
            <div class="flex-1">
                <h2 class="text-3xl font-bold text-zinc-900 dark:text-white mb-4"><?= htmlspecialchars($reservation['customer_name']) ?></h2>
                <div class="flex flex-col sm:flex-row gap-4 text-sm">
                    <?php if ($reservation['customer_email']): ?>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-zinc-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span class="text-zinc-600 dark:text-zinc-300 break-all"><?= htmlspecialchars($reservation['customer_email']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="flex items-center gap-2">
                        <svg class="w-4 h-4 text-zinc-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <span class="text-zinc-600 dark:text-zinc-300 font-mono"><?= _rdFmtPhone($reservation['customer_phone']) ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- 예약 정보 카드 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden mb-6">

    <!-- 날짜/시간 + 스태프 -->
    <div class="p-6 border-b border-zinc-100 dark:border-zinc-700">
        <div class="flex items-start justify-between gap-4">
            <div class="flex items-center gap-4">
                <div class="w-14 h-14 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex flex-col items-center justify-center shrink-0">
                    <span class="text-xs text-blue-600 dark:text-blue-400 font-medium"><?= date('M', strtotime($reservation['reservation_date'])) ?></span>
                    <span class="text-lg font-bold text-blue-600 dark:text-blue-400 -mt-1"><?= date('d', strtotime($reservation['reservation_date'])) ?></span>
                </div>
                <div>
                    <p class="text-lg font-semibold text-zinc-800 dark:text-zinc-100"><?= _rdFmtDate($reservation['reservation_date'], $currentLocale) ?></p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= date('H:i', strtotime($reservation['start_time'])) ?> ~ <?= date('H:i', strtotime($reservation['end_time'])) ?></p>
                </div>
            </div>

            <?php if ($staff): ?>
            <div class="flex items-start gap-4">
                <div class="shrink-0">
                    <?php if (!empty($staff['avatar'])): ?>
                    <img src="<?= htmlspecialchars($baseUrl . '/' . ltrim($staff['avatar'], '/')) ?>" alt="<?= htmlspecialchars($staff['name']) ?>" class="w-20 h-20 rounded-full object-cover border-4 border-white dark:border-zinc-700 shadow-lg">
                    <?php else: ?>
                    <div class="w-20 h-20 rounded-full bg-purple-500 dark:bg-purple-600 flex items-center justify-center text-white font-bold text-3xl border-4 border-white dark:border-zinc-700 shadow-lg"><?= mb_substr($staff['name'], 0, 1) ?></div>
                    <?php endif; ?>
                </div>
                <div class="flex-1">
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1"><?= __('booking.detail.staff') ?></p>
                    <p class="text-lg font-semibold text-zinc-800 dark:text-zinc-100 mb-2"><?= htmlspecialchars($staff['name']) ?></p>
                    <?php if (($reservation['designation_fee'] ?? 0) > 0): ?>
                    <p class="text-sm font-medium text-amber-600 dark:text-amber-400"><?= __('booking.detail.designation_fee') ?> <?= $fmtPrice($reservation['designation_fee']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php else: ?>
            <div class="flex items-center gap-3">
                <div class="w-14 h-14 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center shrink-0">
                    <svg class="w-7 h-7 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <div>
                    <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1"><?= __('booking.detail.staff') ?></p>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('booking.detail.staff_not_assigned') ?></p>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 번들 정보 -->
    <?php if ($bundle): ?>
    <div class="px-6 py-4 bg-amber-50 dark:bg-amber-900/20 border-b border-zinc-100 dark:border-zinc-700">
        <div class="flex items-start gap-4">
            <?php if ($bundle['image']): ?>
            <img src="<?= htmlspecialchars($baseUrl . '/' . $bundle['image']) ?>" alt="<?= htmlspecialchars($bundle['name']) ?>" class="w-20 h-20 rounded-lg object-cover shrink-0">
            <?php endif; ?>
            <div class="flex-1">
                <p class="text-xs font-medium text-amber-600 dark:text-amber-400 mb-1"><?= htmlspecialchars($bundleDisplayName) ?></p>
                <p class="font-semibold text-zinc-800 dark:text-zinc-100"><?= htmlspecialchars($bundle['name']) ?></p>
                <?php if ($bundle['description']): ?>
                <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1 line-clamp-2"><?= htmlspecialchars(strip_tags($bundle['description'])) ?></p>
                <?php endif; ?>
                <?php if ($bundlePrice > 0): ?>
                <p class="text-sm font-semibold text-amber-600 dark:text-amber-400 mt-2"><?= $fmtPrice($bundlePrice) ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <!-- 서비스 목록 -->
    <?php if (!empty($displayServices)): ?>
    <div class="px-6 py-4">
        <h3 class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-3"><?= __('booking.detail.services') ?></h3>
        <div class="space-y-3">
            <?php foreach ($displayServices as $svc): ?>
            <div class="flex gap-4 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-700/30 border border-zinc-200 dark:border-zinc-600">
                <?php if (!empty($svc['image'])): ?>
                <img src="<?= htmlspecialchars($baseUrl . '/' . $svc['image']) ?>" alt="<?= htmlspecialchars($svc['name'] ?? '') ?>" class="w-16 h-16 rounded-lg object-cover shrink-0">
                <?php else: ?>
                <div class="w-16 h-16 rounded-lg bg-zinc-300 dark:bg-zinc-600 flex items-center justify-center shrink-0">
                    <svg class="w-8 h-8 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </div>
                <?php endif; ?>
                <div class="flex-1">
                    <p class="font-medium text-zinc-800 dark:text-zinc-100"><?= htmlspecialchars($svc['name'] ?? $svc['service_name'] ?? '') ?: __('booking.detail.service') ?></p>
                    <?php if (!empty($svc['description'])): ?>
                    <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1 line-clamp-2"><?= htmlspecialchars($svc['description']) ?></p>
                    <?php endif; ?>
                    <div class="flex items-center justify-between mt-2 text-xs">
                        <span class="text-zinc-500 dark:text-zinc-400"><?= (int)($svc['duration'] ?? 0) ?><?= __('booking.detail.duration_unit') ?></span>
                        <span class="font-semibold text-zinc-700 dark:text-zinc-300"><?= $fmtPrice($svc['price'] ?? 0) ?></span>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- 취소 정보 -->
<?php if ($reservation['status'] === 'cancelled'): ?>
<div class="bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-800 p-6 mb-6">
    <h3 class="text-sm font-medium text-red-700 dark:text-red-300 mb-2"><?= __('booking.detail.cancel_info') ?></h3>
    <?php if ($reservation['cancelled_at']): ?>
    <p class="text-sm text-red-600 dark:text-red-400"><?= __('booking.detail.cancelled_at') ?>: <?= date('Y-m-d H:i', strtotime($reservation['cancelled_at'])) ?></p>
    <?php endif; ?>
    <?php if ($reservation['cancel_reason']): ?>
    <p class="text-sm text-red-600 dark:text-red-400 mt-1"><?= __('booking.detail.cancel_reason') ?>: <?= htmlspecialchars($reservation['cancel_reason']) ?></p>
    <?php endif; ?>
</div>
<?php endif; ?>

<!-- 요청사항 -->
<?php if (!empty($reservation['notes'])): ?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6 mb-6">
    <h3 class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-2"><?= __('booking.detail.notes') ?></h3>
    <p class="text-sm text-zinc-800 dark:text-zinc-200"><?= nl2br(htmlspecialchars($reservation['notes'])) ?></p>
</div>
<?php endif; ?>

<!-- 환불 정책 안내 (취소 가능 상태일 때만) -->
<?php if ($isCancellable):
    $_rfPolicyStmt = $pdo->prepare("SELECT `key`, `value` FROM {$prefix}settings WHERE `key` LIKE 'refund_%'");
    $_rfPolicyStmt->execute();
    $_rfP = [];
    while ($_rp = $_rfPolicyStmt->fetch(PDO::FETCH_ASSOC)) $_rfP[$_rp['key']] = $_rp['value'];
    if (($_rfP['refund_enabled'] ?? '0') === '1'):
        $_rfPUnit = $_rfP['refund_time_unit'] ?? 'hours';
        $_rfPUnitLabel = $_rfPUnit === 'days' ? (__('services.settings.general.refund_unit_days') ?? '일') : (__('services.settings.general.refund_unit_hours') ?? '시간');
        $_rfPFull = (int)($_rfP['refund_full_period'] ?? 24);
?>
<div class="bg-zinc-50 dark:bg-zinc-700/30 rounded-xl p-4 mb-6 border border-zinc-200 dark:border-zinc-700">
    <h4 class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-2 flex items-center gap-2">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <?= __('booking.cancel.policy_title') ?? '취소 및 환불 안내' ?>
    </h4>
    <div class="text-xs space-y-1">
        <p class="text-green-600 dark:text-green-400">✓ <?= $_rfPFull ?><?= $_rfPUnitLabel ?> <?= __('services.settings.general.refund_preview_full') ?? '전 → 전액 환불 (100%)' ?></p>
        <?php for ($i = 1; $i <= 3; $i++):
            if (($_rfP["refund_partial{$i}_enabled"] ?? '0') === '1'):
        ?>
        <p class="text-amber-600 dark:text-amber-400">△ <?= (int)($_rfP["refund_partial{$i}_period"] ?? 0) ?><?= $_rfPUnitLabel ?> <?= __('services.settings.general.refund_partial_suffix') ?? '전 취소 시' ?> <?= (int)($_rfP["refund_partial{$i}_rate"] ?? 0) ?>% <?= __('services.settings.general.refund_word') ?? '환불' ?></p>
        <?php endif; endfor; ?>
        <p class="text-red-600 dark:text-red-400">✕ <?= __('services.settings.general.refund_preview_none') ?? '그 외 → 환불 불가' ?></p>
    </div>
</div>
<?php endif; endif; ?>

<!-- 미결제 안내 + 결제 버튼 -->
<?php if ($_needsPayment): ?>
<div class="bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-200 dark:border-amber-800 p-6 mb-6">
    <div class="flex items-center justify-between gap-4">
        <div class="flex items-center gap-3">
            <svg class="w-6 h-6 text-amber-600 dark:text-amber-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            <div>
                <p class="text-sm font-medium text-amber-800 dark:text-amber-200"><?= __('booking.payment.needs_payment') ?? '결제가 필요합니다' ?></p>
                <p class="text-xs text-amber-600 dark:text-amber-400"><?= __('booking.payment.needs_payment_desc') ?? '온라인 결제를 완료하면 예약이 확정됩니다.' ?></p>
            </div>
        </div>
        <a href="<?= $baseUrl ?>/payment/checkout?reservation_id=<?= urlencode($reservation['id']) ?>"
           class="px-6 py-2.5 text-sm font-semibold text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition shrink-0 flex items-center gap-2">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
            <?= __('booking.payment.pay_now') ?? '결제하기' ?>
        </a>
    </div>
</div>
<?php endif; ?>

<!-- 버튼 -->
<div class="flex items-center gap-2 flex-wrap">
    <a href="<?= htmlspecialchars($_backUrl) ?>" class="px-5 py-2.5 text-sm font-medium text-zinc-600 dark:text-zinc-300 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">
        <?= $_backLabel ?>
    </a>
    <?php if ($_needsPayment): ?>
    <a href="<?= $baseUrl ?>/payment/checkout?reservation_id=<?= urlencode($reservation['id']) ?>" class="px-5 py-2.5 text-sm font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">
        <?= __('booking.payment.pay_now') ?? '결제하기' ?>
    </a>
    <?php endif; ?>
    <?php if ($isCancellable): ?>
    <a href="<?= $baseUrl ?>/booking/cancel/<?= urlencode($reservation['reservation_number']) ?>" class="px-5 py-2.5 text-sm font-medium text-red-600 dark:text-red-400 bg-white dark:bg-zinc-800 border border-red-300 dark:border-red-600 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition">
        <?= __('booking.cancel.title') ?>
    </a>
    <?php endif; ?>
</div>
