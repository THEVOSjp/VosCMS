<?php
/**
 * POS 고객 중심 카드 뷰
 * CustomerBasedAdapter에서 사용
 *
 * 사용 가능 변수 ($cd = $posAdapter->prepareCardData() 결과):
 *   $card, $gStatus, $pSt, $svcCount, $progress,
 *   $remaining, $isOvertime, $borderCls, $cardJson, $firstR
 */
$g = $card;
$svcImg = $g['service_image'] ?? '';
$profileImg = $g['user_profile_image'] ?? '';
$isMember = !empty($g['user_id']);
$hasServiceBg = !empty($svcImg) && ($posShowImage ?? true);
$_posShowPhone = $posShowPhone ?? true;
$_posShowPrice = $posShowPrice ?? true;
$_posRequireStaff = $posRequireStaff ?? true;
$_posImageOpacity = $posImageOpacity ?? 60;
$hasProfile = $isMember && !empty($profileImg);
$appUrl = $config['app_url'] ?? '';
$gradeName = $g['grade_name'] ?? '';
$gradeColor = $g['grade_color'] ?? '#6B7280';
// 설정에 따른 할인/적립 적용
$discountEnabled = ($siteSettings['service_discount_enabled'] ?? '0') === '1';
$pointsEnabled = ($siteSettings['service_points_enabled'] ?? '0') === '1';
$discountRate = $discountEnabled ? ($g['grade_discount_rate'] ?? 0) : 0;
$pointRate = $pointsEnabled ? ($g['grade_point_rate'] ?? 0) : 0;
$discountAmt = $discountEnabled ? ($g['discount_amount'] ?? 0) : 0;
$finalAmt = $discountEnabled ? ($g['final_amount'] ?? $g['total_amount']) : $g['total_amount'];
$expectedPts = $pointsEnabled ? ($g['expected_points'] ?? 0) : 0;
$pointsBalance = $pointsEnabled ? ($g['points_balance'] ?? 0) : 0;
?>
<div class="pos-card rounded-xl border-2 <?= $borderCls ?> shadow-sm relative overflow-hidden hover:shadow-md transition flex flex-col"
     style="width:<?= $posCardWidth ?? '260px' ?>;height:<?= $posCardHeight ?? '260px' ?>;<?php if ($hasServiceBg): ?>background-image:url('<?= htmlspecialchars($appUrl . '/' . $svcImg) ?>');background-size:cover;background-position:center<?php endif; ?>">

    <?php if ($hasServiceBg): ?>
    <?php
    // 투명도: 100% = 이미지 원본, 10% = 이미지 거의 안보임
    // 오버레이 opacity = 1 - (투명도/100)
    $overlayOp = 1 - ($_posImageOpacity / 100);
    $opFrom = min(0.9, $overlayOp + 0.2); // 하단 더 진하게 (텍스트 가독성)
    $opVia = $overlayOp;
    $opTo = max(0.05, $overlayOp - 0.15); // 상단 더 밝게
    ?>
    <div class="absolute inset-0 z-[1]" style="background:linear-gradient(to top, rgba(0,0,0,<?= $opFrom ?>), rgba(0,0,0,<?= $opVia ?>), rgba(0,0,0,<?= $opTo ?>))"></div>
    <?php else: ?>
    <div class="absolute inset-0 bg-white dark:bg-zinc-800 z-[1]"></div>
    <?php endif; ?>

    <?php if ($gStatus === 'in_service'): ?>
    <div class="absolute bottom-0 left-0 h-1.5 <?= $isOvertime ? 'bg-red-500' : 'bg-emerald-500' ?> transition-all z-20" style="width: <?= $progress ?>%"></div>
    <?php endif; ?>

    <!-- 카드 상단: 고객 + 서비스 목록 (클릭 → 서비스 상세) -->
    <div class="relative z-10 p-3 pb-2 cursor-pointer flex-1 flex flex-col overflow-hidden" onclick="POS.showServices(<?= $cardJson ?>)">
        <div class="flex items-start justify-between mb-2">
            <div class="flex items-center gap-2.5">
                <?php if ($isMember): ?>
                    <?php if ($hasProfile): ?>
                    <?php
                    // profile_image는 이미 /storage/ 포함, service image는 미포함 → 분기 처리
                    $profileSrc = str_starts_with($profileImg, 'http')
                        ? $profileImg
                        : $appUrl . '/' . ltrim($profileImg, '/');
                    ?>
                    <img src="<?= htmlspecialchars($profileSrc) ?>" alt=""
                         class="w-11 h-11 rounded-full object-cover border-2 border-blue-400/80 shadow-md flex-shrink-0">
                    <?php else: ?>
                    <div class="w-11 h-11 rounded-full bg-blue-500 flex items-center justify-center border-2 border-blue-400/50 shadow flex-shrink-0">
                        <svg class="w-5 h-5 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <?php endif; ?>
                <?php endif; ?>
                <div class="min-w-0">
                    <div class="flex items-center gap-1.5">
                        <p class="text-lg font-bold <?= $hasServiceBg ? 'text-white drop-shadow-sm' : 'text-zinc-900 dark:text-white' ?> truncate"><?= htmlspecialchars($g['customer_name']) ?></p>
                        <?php if ($isMember && $gradeName): ?>
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-bold text-white flex-shrink-0" style="background-color:<?= htmlspecialchars($gradeColor) ?>"><?= htmlspecialchars($gradeName) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($_posShowPhone): ?>
                    <p class="text-xs font-mono <?= $hasServiceBg ? 'text-white/70' : 'text-zinc-400 dark:text-zinc-500' ?>"><?= _admFmtPhone($g['customer_phone']) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <div class="flex flex-col items-end gap-1 flex-shrink-0">
                <?php if ($gStatus === 'waiting'): ?>
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100/90 text-amber-700 dark:bg-amber-900/60 dark:text-amber-400"><?= __('reservations.pos_waiting') ?></span>
                <?php elseif ($isOvertime): ?>
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-red-100/90 text-red-700 dark:bg-red-900/60 dark:text-red-400 animate-pulse"><?= __('reservations.pos_overtime') ?></span>
                <?php else: ?>
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100/90 text-emerald-700 dark:bg-emerald-900/60 dark:text-emerald-400"><?= __('reservations.pos_in_service') ?></span>
                <?php endif; ?>
                <?php if ($pSt === 'paid'): ?>
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-green-100/90 text-green-700 dark:bg-green-900/60 dark:text-green-400"><?= __('reservations.pos_paid') ?></span>
                <?php elseif ($pSt === 'partial'): ?>
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-100/90 text-blue-700 dark:bg-blue-900/60 dark:text-blue-400"><?= __('reservations.pos_partial_paid') ?></span>
                <?php endif; ?>
                <?php if (empty($g['staff_id'])): ?>
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-red-100/90 text-red-700 dark:bg-red-900/60 dark:text-red-400"><?= __('reservations.pos_unassigned') ?></span>
                <?php else: ?>
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-medium <?= $hasServiceBg ? 'bg-white/20 text-white/80' : 'bg-violet-100/90 text-violet-700 dark:bg-violet-900/60 dark:text-violet-400' ?>"><?= htmlspecialchars($g['staff_name'] ?? '') ?></span>
                <?php endif; ?>
            </div>
        </div>

        <!-- 서비스 목록 -->
        <div class="mb-1 space-y-0.5 flex-shrink overflow-hidden">
            <?php foreach (array_slice($g['services'], 0, 2) as $s): ?>
            <p class="text-sm font-semibold <?= $hasServiceBg ? 'text-blue-300 drop-shadow-sm' : 'text-blue-600 dark:text-blue-400' ?> truncate"><?= htmlspecialchars($s['service_name'] ?? '') ?></p>
            <?php endforeach; ?>
            <?php if ($svcCount > 2): ?>
            <p class="text-xs <?= $hasServiceBg ? 'text-white/60' : 'text-zinc-400' ?>">+<?= $svcCount - 2 ?><?= __('reservations.pos_service_count') ?></p>
            <?php endif; ?>
        </div>

        <!-- 할인/적립 정보 -->
        <?php if ($isMember && ($discountRate > 0 || $pointsBalance > 0)): ?>
        <div class="flex items-center gap-2 mb-1 flex-wrap">
            <?php if ($discountRate > 0): ?>
            <span class="text-[10px] <?= $hasServiceBg ? 'text-orange-300' : 'text-orange-500 dark:text-orange-400' ?>">
                <?= __('reservations.pos_discount') ?> <?= $discountRate ?>% (-<?= formatPrice($discountAmt) ?>)
            </span>
            <?php endif; ?>
            <?php if ($expectedPts > 0): ?>
            <span class="text-[10px] <?= $hasServiceBg ? 'text-yellow-300' : 'text-yellow-600 dark:text-yellow-400' ?>">
                <?= __('reservations.pos_points_earn') ?> +<?= number_format($expectedPts) ?>P
            </span>
            <?php endif; ?>
            <?php if ($pointsBalance > 0): ?>
            <span class="text-[10px] <?= $hasServiceBg ? 'text-cyan-300' : 'text-cyan-600 dark:text-cyan-400' ?>">
                <?= get_points_name() ?> <?= number_format($pointsBalance) ?>P
            </span>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="flex items-center justify-between mt-auto">
            <?php if ($gStatus === 'in_service'): ?>
            <div class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5 <?= $isOvertime ? 'text-red-400' : 'text-emerald-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-xs font-bold <?= $isOvertime ? 'text-red-400' : ($hasServiceBg ? 'text-emerald-300' : 'text-emerald-600 dark:text-emerald-400') ?>">
                    <?= $isOvertime ? '+' . abs($remaining) : $remaining ?><?= __('reservations.pos_min') ?> <?= $isOvertime ? '' : __('reservations.pos_remaining') ?>
                </span>
            </div>
            <?php else: ?>
            <span class="text-xs <?= $hasServiceBg ? 'text-amber-300' : 'text-amber-600 dark:text-amber-400' ?>"><?= $g['earliest_start'] ?> <?= __('reservations.pos_scheduled') ?></span>
            <?php endif; ?>
            <?php if ($_posShowPrice): ?>
            <div class="text-right">
                <?php if ($discountAmt > 0): ?>
                <span class="text-[10px] line-through <?= $hasServiceBg ? 'text-white/50' : 'text-zinc-400' ?>"><?= formatPrice($g['total_amount']) ?></span>
                <?php endif; ?>
                <span class="text-sm font-bold <?= $hasServiceBg ? 'text-white drop-shadow-sm' : 'text-zinc-900 dark:text-white' ?>"><?= formatPrice($finalAmt) ?></span>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 카드 하단: 액션 버튼 -->
    <div class="relative z-10 px-3 pb-2 pt-1 flex gap-2 flex-shrink-0 <?= $hasServiceBg ? 'bg-black/30 backdrop-blur-sm' : '' ?>">
        <?php if ($g['has_pending'] && !$g['has_in_service']): ?>
            <?php if (empty($g['staff_id']) && $_posRequireStaff): ?>
            <button disabled
                    class="flex-1 h-11 flex items-center justify-center gap-1.5 bg-zinc-300 dark:bg-zinc-600 text-zinc-500 dark:text-zinc-400 rounded-lg text-sm font-bold cursor-not-allowed">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <?= __('reservations.pos_assign_first') ?>
            </button>
            <?php else: ?>
            <button onclick="event.stopPropagation();POS.startAllServices(<?= $cardJson ?>)"
                    class="flex-1 h-11 flex items-center justify-center gap-1.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-lg text-sm font-bold transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/></svg>
                <?= __('reservations.pos_btn_start') ?>
            </button>
            <?php endif; ?>
            <button onclick="event.stopPropagation();POS.cancelAllServices(<?= $cardJson ?>)"
                    class="h-11 px-3 flex items-center justify-center bg-zinc-200/90 hover:bg-zinc-300 active:bg-zinc-400 dark:bg-zinc-700/90 dark:hover:bg-zinc-600 text-zinc-600 dark:text-zinc-300 rounded-lg text-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        <?php else: ?>
            <?php if ($pSt !== 'paid'): ?>
            <button onclick="event.stopPropagation();POS.openGroupPayment(<?= $cardJson ?>)"
                    class="flex-1 h-11 flex items-center justify-center gap-1.5 bg-violet-600 hover:bg-violet-700 active:bg-violet-800 text-white rounded-lg text-sm font-bold transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                <?= __('reservations.pos_btn_payment') ?>
            </button>
            <?php endif; ?>
            <button onclick="event.stopPropagation();POS.completeAllServices(<?= $cardJson ?>)"
                    class="<?= $pSt === 'paid' ? 'flex-1' : '' ?> h-11 px-4 flex items-center justify-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-lg text-sm font-bold transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <?= __('reservations.pos_btn_complete') ?>
            </button>
        <?php endif; ?>
    </div>
</div>
