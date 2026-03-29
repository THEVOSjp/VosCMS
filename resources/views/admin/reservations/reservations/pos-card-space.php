<?php
/**
 * POS 공간(테이블/룸) 중심 카드 뷰
 * SpaceBasedAdapter에서 사용
 *
 * 사용 가능 변수 ($cd = $posAdapter->prepareCardData() 결과):
 *   $card, $gStatus, $pSt, $custCount, $firstR, $progress,
 *   $remaining, $isOvertime, $borderCls, $cardJson
 */

$spaceName = $card['space_name'] ?? '';
$spaceType = $card['space_type'] ?? 'table';
$capacity  = $card['capacity'] ?? 0;
$floor     = $card['floor'] ?? '';
$svcCount  = count($card['services']);

// 공간 타입 아이콘
$typeIcons = [
    'table' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/>',
    'room'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-4 0h4"/>',
    'seat'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>',
    'booth' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>',
    'zone'  => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/>',
];
$icon = $typeIcons[$spaceType] ?? $typeIcons['table'];
?>
<div class="pos-card bg-white dark:bg-zinc-800 rounded-xl border-2 <?= $borderCls ?> shadow-sm relative overflow-hidden hover:shadow-md transition flex flex-col">
    <?php if ($gStatus === 'occupied'): ?>
    <div class="absolute bottom-0 left-0 h-1.5 <?= $isOvertime ? 'bg-red-500' : 'bg-emerald-500' ?> transition-all z-10" style="width: <?= $progress ?>%"></div>
    <?php endif; ?>

    <!-- 카드 상단: 공간 정보 -->
    <div class="p-4 pb-2 cursor-pointer" onclick="<?= $gStatus !== 'available' ? "POS.showSpaceDetail({$cardJson})" : "POS.assignToSpace({$cardJson})" ?>">
        <div class="flex items-start justify-between mb-2">
            <div class="flex items-center gap-2">
                <div class="w-10 h-10 rounded-lg flex items-center justify-center <?= $gStatus === 'available' ? 'bg-zinc-100 dark:bg-zinc-700' : ($gStatus === 'occupied' ? 'bg-emerald-100 dark:bg-emerald-900/30' : 'bg-amber-100 dark:bg-amber-900/30') ?>">
                    <svg class="w-5 h-5 <?= $gStatus === 'available' ? 'text-zinc-400' : ($gStatus === 'occupied' ? 'text-emerald-600' : 'text-amber-600') ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><?= $icon ?></svg>
                </div>
                <div>
                    <p class="text-lg font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($spaceName) ?></p>
                    <p class="text-xs text-zinc-400 dark:text-zinc-500">
                        <?= $floor ? htmlspecialchars($floor) . ' · ' : '' ?>
                        <?= $capacity > 0 ? $capacity . __('reservations.pos_space_capacity_unit') : '' ?>
                    </p>
                </div>
            </div>
            <div class="flex flex-col items-end gap-1">
                <?php if ($gStatus === 'available'): ?>
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400"><?= __('reservations.pos_space_available') ?></span>
                <?php elseif ($gStatus === 'reserved'): ?>
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400"><?= __('reservations.pos_space_reserved') ?></span>
                <?php elseif ($isOvertime): ?>
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400 animate-pulse"><?= __('reservations.pos_overtime') ?></span>
                <?php else: ?>
                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400"><?= __('reservations.pos_space_occupied') ?></span>
                <?php endif; ?>
                <?php if ($pSt === 'paid' && $gStatus !== 'available'): ?>
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400"><?= __('reservations.pos_paid') ?></span>
                <?php elseif ($pSt === 'partial'): ?>
                    <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400"><?= __('reservations.pos_partial_paid') ?></span>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($gStatus !== 'available'): ?>
        <!-- 고객/서비스 정보 -->
        <div class="mb-2 space-y-0.5">
            <?php foreach ($card['customers'] as $cust): ?>
            <p class="text-sm font-medium text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($cust['name']) ?></p>
            <?php endforeach; ?>
            <?php if ($svcCount > 0): ?>
            <p class="text-xs text-blue-600 dark:text-blue-400"><?= $svcCount ?><?= __('reservations.pos_service_count') ?></p>
            <?php endif; ?>
        </div>

        <div class="flex items-center justify-between">
            <?php if ($gStatus === 'occupied'): ?>
            <div class="flex items-center gap-1">
                <svg class="w-3.5 h-3.5 <?= $isOvertime ? 'text-red-500' : 'text-emerald-500' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <span class="text-xs font-bold <?= $isOvertime ? 'text-red-600' : 'text-emerald-600 dark:text-emerald-400' ?>">
                    <?= $isOvertime ? '+' . abs($remaining) : $remaining ?><?= __('reservations.pos_min') ?> <?= $isOvertime ? '' : __('reservations.pos_remaining') ?>
                </span>
            </div>
            <?php else: ?>
            <span class="text-xs text-amber-600 dark:text-amber-400"><?= $card['earliest_start'] ?> <?= __('reservations.pos_scheduled') ?></span>
            <?php endif; ?>
            <span class="text-sm font-bold text-zinc-900 dark:text-white"><?= formatPrice($card['total_amount']) ?></span>
        </div>
        <?php endif; ?>
    </div>

    <!-- 카드 하단: 액션 버튼 -->
    <div class="px-3 pb-3 pt-1 flex gap-2 mt-auto">
        <?php if ($gStatus === 'available'): ?>
            <button onclick="event.stopPropagation();POS.assignToSpace(<?= $cardJson ?>)"
                    class="flex-1 h-11 flex items-center justify-center gap-1.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-lg text-sm font-bold transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <?= __('reservations.pos_space_assign') ?>
            </button>
        <?php elseif ($card['has_pending'] && !$card['is_occupied']): ?>
            <button onclick="event.stopPropagation();POS.startAllServices(<?= $cardJson ?>)"
                    class="flex-1 h-11 flex items-center justify-center gap-1.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-lg text-sm font-bold transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/></svg>
                <?= __('reservations.pos_btn_start') ?>
            </button>
            <button onclick="event.stopPropagation();POS.cancelAllServices(<?= $cardJson ?>)"
                    class="h-11 px-3 flex items-center justify-center bg-zinc-200 hover:bg-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-600 dark:text-zinc-300 rounded-lg text-sm transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        <?php else: ?>
            <?php if ($pSt !== 'paid'): ?>
            <button onclick="event.stopPropagation();POS.openGroupPayment(<?= $cardJson ?>, <?= $card['total_amount'] ?>, <?= $card['paid_amount'] ?>)"
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
