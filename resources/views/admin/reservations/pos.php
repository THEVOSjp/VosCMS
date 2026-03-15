<?php
/**
 * POS 페이지 - 당일 현장 관리 화면
 * 상단: 메뉴/버튼 영역
 * 하단 3:1 — 왼쪽: 이용중+대기 고객 카드 / 오른쪽: 탭(당일접수, 예약자리스트, 대기자명단)
 */
include __DIR__ . '/_init.php';

$services = getServices($pdo, $prefix);
$pageTitle = __('reservations.pos') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$today = date('Y-m-d');
$nowTime = date('H:i:s');

// 서비스 목록 (접수 컴포넌트용)
$calServices = $pdo->query("SELECT id, name, description, duration, price FROM {$prefix}services WHERE is_active = 1 ORDER BY sort_order ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);

// 오늘 전체 예약
$stmtToday = $pdo->prepare("
    SELECT r.*, s.name as service_name, s.duration as service_duration
    FROM {$prefix}reservations r
    LEFT JOIN {$prefix}services s ON r.service_id = s.id
    WHERE r.reservation_date = ?
    ORDER BY r.start_time ASC
");
$stmtToday->execute([$today]);
$todayAll = $stmtToday->fetchAll(PDO::FETCH_ASSOC);

// 분류
$inService = [];       // 이용중 (confirmed & 시간범위내)
$waitingCards = [];    // 대기 카드 (왼쪽: pending/confirmed & 아직 시작전)
$reservationList = []; // 사전 예약 건만 (source != walk_in)
$waitingList = [];     // 현장접수 대기 (source = walk_in)
$completedCount = 0;

foreach ($todayAll as $r) {
    $st = $r['status'] ?? 'pending';
    $src = $r['source'] ?? 'online';
    $isInSvc = ($st === 'confirmed' && ($r['start_time'] ?? '') <= $nowTime && (($r['end_time'] ?? '23:59:59') >= $nowTime));

    if ($isInSvc) {
        $inService[] = $r;
    } elseif ($st === 'pending' || ($st === 'confirmed' && !$isInSvc)) {
        $waitingCards[] = $r;
        if ($src === 'walk_in') {
            $waitingList[] = $r;
        }
    }
    // 예약자 리스트: 사전 예약 건만 (현장접수 제외)
    if ($src !== 'walk_in' && $st !== 'cancelled' && $st !== 'no_show') {
        $reservationList[] = $r;
    }
    if ($st === 'completed') $completedCount++;
}

$allCards = array_merge($inService, $waitingCards);
$counts = [
    'in_service' => count($inService),
    'waiting' => count($waitingCards),
    'reservations' => count($reservationList),
    'total' => count($todayAll),
];

include __DIR__ . '/_head.php';
?>

<!-- ═══ 상단: 메뉴 및 버튼 영역 ═══ -->
<div class="flex items-center justify-between mb-4">
    <div class="flex items-center gap-3">
        <h2 class="text-2xl font-bold text-zinc-900 dark:text-white flex items-center">
            <svg class="w-6 h-6 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
            </svg>
            <?= __('reservations.pos') ?>
        </h2>
        <span class="text-sm text-zinc-500 dark:text-zinc-400"><?= date('Y-m-d (D)') ?></span>
        <span id="posClock" class="text-sm font-mono text-blue-600 dark:text-blue-400"><?= date('H:i:s') ?></span>
    </div>
    <div class="flex items-center gap-2 text-sm">
        <span class="px-2.5 py-1 bg-emerald-100 text-emerald-800 dark:bg-emerald-900/30 dark:text-emerald-400 rounded-lg font-medium">
            <?= __('reservations.pos_in_service') ?> <?= $counts['in_service'] ?>
        </span>
        <span class="px-2.5 py-1 bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 rounded-lg font-medium">
            <?= __('reservations.pos_waiting') ?> <?= $counts['waiting'] ?>
        </span>
        <span class="px-2.5 py-1 bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400 rounded-lg font-medium">
            <?= __('reservations.pos_total_count') ?> <?= $counts['total'] ?>
        </span>
        <button onclick="location.reload()" class="ml-2 p-1.5 text-zinc-400 hover:text-zinc-600 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-lg transition" title="<?= __('reservations.pos_refresh') ?>">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        </button>
    </div>
</div>

<!-- ═══ 3:1 메인 레이아웃 ═══ -->
<div class="flex gap-4" style="height: calc(100vh - 180px);">

    <!-- ━━━ 왼쪽 3/4: 이용중 + 대기 고객 카드 ━━━ -->
    <div class="w-3/4 flex flex-col">
        <?php if (empty($allCards)): ?>
        <div class="flex-1 flex items-center justify-center bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700">
            <div class="text-center">
                <svg class="w-16 h-16 mx-auto text-zinc-200 dark:text-zinc-700 mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <p class="text-sm text-zinc-400 dark:text-zinc-500"><?= __('reservations.pos_no_in_service') ?></p>
            </div>
        </div>
        <?php else: ?>
        <div class="flex-1 overflow-y-auto pr-1">
            <div class="grid grid-cols-3 gap-3">
                <?php foreach ($allCards as $r):
                    $st = $r['status'] ?? 'pending';
                    $pSt = $r['payment_status'] ?? 'unpaid';
                    $isInSvc = ($st === 'confirmed' && ($r['start_time'] ?? '') <= $nowTime && (($r['end_time'] ?? '23:59:59') >= $nowTime));
                    $isWaiting = !$isInSvc;
                    $startT = substr($r['start_time'] ?? '', 0, 5);
                    $endT = substr($r['end_time'] ?? '', 0, 5);
                    $dur = (int)($r['service_duration'] ?? 0);
                    $finalAmt = (float)($r['final_amount'] ?? $r['total_amount'] ?? 0);
                    $rJson = htmlspecialchars(json_encode($r, JSON_UNESCAPED_UNICODE));

                    // 진행률 (이용중만)
                    $progress = 0; $remaining = 0; $isOvertime = false;
                    if ($isInSvc) {
                        $startMin = intval(substr($startT, 0, 2)) * 60 + intval(substr($startT, 3, 2));
                        $endMin = $endT ? intval(substr($endT, 0, 2)) * 60 + intval(substr($endT, 3, 2)) : $startMin + $dur;
                        $nowMin = intval(date('H')) * 60 + intval(date('i'));
                        $totalMin = max($endMin - $startMin, 1);
                        $elapsed = max(0, $nowMin - $startMin);
                        $progress = min(100, round($elapsed / $totalMin * 100));
                        $remaining = max(0, $endMin - $nowMin);
                        $isOvertime = $remaining <= 0;
                    }

                    // 카드 색상
                    if ($isWaiting) {
                        $borderCls = 'border-amber-300 dark:border-amber-600';
                        $barCls = 'bg-amber-400';
                    } elseif ($isOvertime) {
                        $borderCls = 'border-red-400 dark:border-red-500';
                        $barCls = 'bg-red-500';
                    } else {
                        $borderCls = 'border-emerald-300 dark:border-emerald-600';
                        $barCls = 'bg-emerald-500';
                    }
                ?>
                <div class="pos-card bg-white dark:bg-zinc-800 rounded-xl border-2 <?= $borderCls ?> shadow-sm relative overflow-hidden hover:shadow-md transition flex flex-col">
                    <?php if ($isInSvc): ?>
                    <div class="absolute bottom-0 left-0 h-1.5 <?= $barCls ?> transition-all z-10" style="width: <?= $progress ?>%"></div>
                    <?php endif; ?>

                    <!-- 카드 상단: 고객 정보 (클릭 → 상세) -->
                    <div class="p-4 pb-2 cursor-pointer" onclick="POS.showDetail(<?= $rJson ?>)">
                        <div class="flex items-start justify-between mb-2">
                            <div>
                                <p class="text-lg font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($r['customer_name'] ?? '') ?></p>
                                <p class="text-xs text-zinc-400 dark:text-zinc-500"><?= htmlspecialchars($r['customer_phone'] ?? '') ?></p>
                            </div>
                            <div class="flex flex-col items-end gap-1">
                                <?php if ($isWaiting): ?>
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-400"><?= __('reservations.pos_waiting') ?></span>
                                <?php elseif ($isOvertime): ?>
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-red-100 text-red-700 dark:bg-red-900/40 dark:text-red-400 animate-pulse"><?= __('reservations.pos_overtime') ?></span>
                                <?php else: ?>
                                    <span class="px-2 py-0.5 rounded-full text-xs font-bold bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-400"><?= __('reservations.pos_in_service') ?></span>
                                <?php endif; ?>
                                <?php if ($pSt === 'paid'): ?>
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-400"><?= __('reservations.pos_paid') ?></span>
                                <?php elseif ($pSt === 'partial'): ?>
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-400"><?= __('reservations.pos_partial_paid') ?></span>
                                <?php endif; ?>
                            </div>
                        </div>

                        <div class="mb-2">
                            <p class="text-sm font-semibold text-blue-600 dark:text-blue-400"><?= htmlspecialchars($r['service_name'] ?? '') ?></p>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5"><?= $startT ?><?= $endT ? ' ~ ' . $endT : '' ?></p>
                        </div>

                        <div class="flex items-center justify-between">
                            <?php if ($isInSvc): ?>
                            <div class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5 <?= $isOvertime ? 'text-red-500' : 'text-emerald-500' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <span class="text-xs font-bold <?= $isOvertime ? 'text-red-600' : 'text-emerald-600 dark:text-emerald-400' ?>">
                                    <?= $isOvertime ? '+' . abs($remaining) : $remaining ?><?= __('reservations.pos_min') ?> <?= $isOvertime ? '' : __('reservations.pos_remaining') ?>
                                </span>
                            </div>
                            <?php else: ?>
                            <span class="text-xs text-amber-600 dark:text-amber-400"><?= $startT ?> <?= __('reservations.pos_scheduled') ?></span>
                            <?php endif; ?>
                            <span class="text-sm font-bold text-zinc-900 dark:text-white"><?= formatPrice($finalAmt) ?></span>
                        </div>
                    </div>

                    <!-- 카드 하단: 액션 버튼 (터치 친화적 44px+) -->
                    <div class="px-3 pb-3 pt-1 flex gap-2 mt-auto">
                        <?php if ($st === 'pending' || ($isWaiting && $st === 'confirmed')): ?>
                            <!-- 대기(pending/confirmed 시작전): 진행 / 취소 -->
                            <button onclick="event.stopPropagation();POS.startService('<?= $r['id'] ?>')"
                                    class="flex-1 h-11 flex items-center justify-center gap-1.5 bg-blue-600 hover:bg-blue-700 active:bg-blue-800 text-white rounded-lg text-sm font-bold transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.752 11.168l-3.197-2.132A1 1 0 0010 9.87v4.263a1 1 0 001.555.832l3.197-2.132a1 1 0 000-1.664z"/></svg>
                                <?= __('reservations.pos_btn_start') ?>
                            </button>
                            <button onclick="event.stopPropagation();POS.changeStatus('<?= $r['id'] ?>','cancel')"
                                    class="h-11 px-3 flex items-center justify-center bg-zinc-200 hover:bg-zinc-300 active:bg-zinc-400 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-600 dark:text-zinc-300 rounded-lg text-sm transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        <?php else: ?>
                            <!-- 이용중 / 초과: 결제 + 완료 -->
                            <?php if ($pSt !== 'paid'): ?>
                            <button onclick="event.stopPropagation();POS.openPayment('<?= $r['id'] ?>', <?= $finalAmt ?>, <?= (float)($r['paid_amount'] ?? 0) ?>)"
                                    class="flex-1 h-11 flex items-center justify-center gap-1.5 bg-violet-600 hover:bg-violet-700 active:bg-violet-800 text-white rounded-lg text-sm font-bold transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                                <?= __('reservations.pos_btn_payment') ?>
                            </button>
                            <?php endif; ?>
                            <button onclick="event.stopPropagation();POS.changeStatus('<?= $r['id'] ?>','complete')"
                                    class="<?= $pSt === 'paid' ? 'flex-1' : '' ?> h-11 px-4 flex items-center justify-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 active:bg-emerald-800 text-white rounded-lg text-sm font-bold transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <?= __('reservations.pos_btn_complete') ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- ━━━ 오른쪽 1/4: 탭 패널 ━━━ -->
    <div class="w-1/4 flex flex-col bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="flex border-b border-zinc-200 dark:border-zinc-700">
            <button onclick="POS.switchTab('checkin')" class="pos-tab flex-1 py-2.5 text-xs font-bold text-center border-b-2 border-blue-500 text-blue-600 dark:text-blue-400" data-tab="checkin">
                <?= __('reservations.pos_tab_checkin') ?>
            </button>
            <button onclick="POS.switchTab('waiting')" class="pos-tab flex-1 py-2.5 text-xs font-bold text-center border-b-2 border-transparent text-zinc-400 hover:text-zinc-600" data-tab="waiting">
                <?= __('reservations.pos_tab_waiting') ?>
                <span class="ml-1 px-1 py-0.5 bg-amber-200 dark:bg-amber-800 text-amber-700 dark:text-amber-300 rounded text-[10px]"><?= $counts['waiting'] ?></span>
            </button>
            <button onclick="POS.switchTab('reservations')" class="pos-tab flex-1 py-2.5 text-xs font-bold text-center border-b-2 border-transparent text-zinc-400 hover:text-zinc-600" data-tab="reservations">
                <?= __('reservations.pos_tab_reservations') ?>
                <span class="ml-1 px-1 py-0.5 bg-zinc-200 dark:bg-zinc-600 text-zinc-600 dark:text-zinc-300 rounded text-[10px]"><?= $counts['reservations'] ?></span>
            </button>
        </div>

        <div class="flex-1 overflow-hidden relative">

            <!-- 탭1: 당일 접수 (예약 접수 모달 버튼) -->
            <div id="posTabCheckin" class="pos-tab-pane absolute inset-0 flex flex-col items-center justify-center p-4">
                <button onclick="POS.openCheckinModal()" class="w-full py-10 bg-blue-50 dark:bg-blue-900/20 border-2 border-dashed border-blue-300 dark:border-blue-600 rounded-xl hover:bg-blue-100 dark:hover:bg-blue-900/30 transition group">
                    <svg class="w-12 h-12 mx-auto text-blue-400 group-hover:text-blue-600 transition mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <p class="text-sm font-bold text-blue-600 dark:text-blue-400"><?= __('reservations.pos_tab_checkin') ?></p>
                    <p class="text-xs text-zinc-400 mt-1"><?= __('reservations.pos_walk_in') ?></p>
                </button>
                <!-- 오늘 요약 -->
                <div class="w-full mt-4 space-y-2 text-xs">
                    <div class="flex justify-between text-zinc-500"><span><?= __('reservations.pos_in_service') ?></span><span class="font-bold text-emerald-600"><?= $counts['in_service'] ?></span></div>
                    <div class="flex justify-between text-zinc-500"><span><?= __('reservations.pos_waiting') ?></span><span class="font-bold text-amber-600"><?= $counts['waiting'] ?></span></div>
                    <div class="flex justify-between text-zinc-500"><span><?= __('reservations.pos_done') ?></span><span class="font-bold text-zinc-600"><?= $completedCount ?></span></div>
                    <div class="flex justify-between text-zinc-500 pt-1 border-t border-zinc-200 dark:border-zinc-700"><span><?= __('reservations.pos_total_count') ?></span><span class="font-bold text-blue-600"><?= $counts['total'] ?></span></div>
                </div>
            </div>

            <!-- 탭2: 대기자 명단 -->
            <div id="posTabWaiting" class="pos-tab-pane absolute inset-0 hidden overflow-y-auto">
                <?php if (empty($waitingList)): ?>
                    <div class="flex items-center justify-center h-full">
                        <p class="text-xs text-zinc-400"><?= __('reservations.pos_no_waiting') ?></p>
                    </div>
                <?php else: ?>
                    <?php $wIdx = 1; foreach ($waitingList as $r): $st = $r['status'] ?? 'pending'; ?>
                    <div class="flex items-center justify-between px-3 py-2.5 border-b border-zinc-100 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 cursor-pointer transition"
                         onclick="POS.showDetail(<?= htmlspecialchars(json_encode($r, JSON_UNESCAPED_UNICODE)) ?>)">
                        <div class="flex items-center gap-2.5">
                            <span class="w-6 h-6 flex items-center justify-center rounded-full bg-amber-100 dark:bg-amber-900/30 text-amber-700 dark:text-amber-400 text-[10px] font-bold flex-shrink-0"><?= $wIdx ?></span>
                            <div>
                                <p class="text-xs font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($r['customer_name'] ?? '') ?></p>
                                <p class="text-[10px] text-zinc-400"><?= substr($r['start_time'] ?? '', 0, 5) ?> · <?= htmlspecialchars($r['service_name'] ?? '') ?></p>
                            </div>
                        </div>
                        <?= statusBadge($st) ?>
                    </div>
                    <?php $wIdx++; endforeach; ?>
                <?php endif; ?>
            </div>

            <!-- 탭3: 예약자 리스트 -->
            <div id="posTabReservations" class="pos-tab-pane absolute inset-0 hidden overflow-y-auto divide-y divide-zinc-100 dark:divide-zinc-700">
                <?php if (empty($reservationList)): ?>
                    <div class="flex items-center justify-center h-full">
                        <p class="text-xs text-zinc-400"><?= __('reservations.pos_no_reservations') ?></p>
                    </div>
                <?php else: ?>
                    <?php foreach ($reservationList as $r):
                        $st = $r['status'] ?? 'pending';
                        $isInSvc = ($st === 'confirmed' && ($r['start_time'] ?? '') <= $nowTime && (($r['end_time'] ?? '23:59:59') >= $nowTime));
                    ?>
                    <div class="flex items-center justify-between px-3 py-2 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 cursor-pointer transition"
                         onclick="POS.showDetail(<?= htmlspecialchars(json_encode($r, JSON_UNESCAPED_UNICODE)) ?>)">
                        <div class="flex items-center gap-2 min-w-0">
                            <span class="text-[11px] font-mono text-zinc-400 w-9 flex-shrink-0"><?= substr($r['start_time'] ?? '', 0, 5) ?></span>
                            <?php if ($isInSvc): ?><div class="w-1.5 h-1.5 bg-emerald-500 rounded-full animate-pulse flex-shrink-0"></div><?php endif; ?>
                            <div class="min-w-0">
                                <p class="text-xs font-medium text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($r['customer_name'] ?? '') ?></p>
                                <p class="text-[10px] text-zinc-400 truncate"><?= htmlspecialchars($r['service_name'] ?? '') ?></p>
                            </div>
                        </div>
                        <?= statusBadge($st) ?>
                    </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>

<!-- 상세/상태변경 모달 -->
<div id="posDetailModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4" onclick="POS.closeDetail(event)">
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-2xl w-full max-w-md overflow-hidden" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between p-4 border-b border-zinc-200 dark:border-zinc-700">
            <h3 id="posDetailTitle" class="text-base font-bold text-zinc-900 dark:text-white"></h3>
            <button onclick="POS.closeDetail()" class="p-1 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg">
                <svg class="w-5 h-5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div id="posDetailBody" class="p-4"></div>
        <div id="posDetailActions" class="px-4 pb-4 flex gap-2"></div>
    </div>
</div>

<!-- 당일 접수 모달 (예약 접수 컴포넌트 재사용) -->
<div id="posCheckinModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4" onclick="POS.closeCheckinModal(event)">
    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl w-full max-w-5xl max-h-[90vh] overflow-y-auto" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between p-5 border-b border-zinc-200 dark:border-zinc-700 sticky top-0 bg-white dark:bg-zinc-800 z-10 rounded-t-2xl">
            <h3 class="text-lg font-bold text-zinc-900 dark:text-white flex items-center">
                <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <?= __('reservations.pos_tab_checkin') ?>
            </h3>
            <button onclick="POS.closeCheckinModal()" class="p-1.5 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition">
                <svg class="w-5 h-5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-5">
            <?php
            $resForm = [
                'services'         => $calServices,
                'adminUrl'         => $adminUrl,
                'csrfToken'        => $csrfToken,
                'currencySymbol'   => $currencySymbol,
                'currencyPosition' => $currencyPosition,
                'formId'           => 'posCheckinForm',
                'mode'             => 'modal',
                'defaultDate'      => $today,
                'source'           => 'walk_in',
                'old'              => [],
            ];
            include BASE_PATH . '/resources/views/admin/components/reservation-form.php';
            ?>
        </div>
    </div>
</div>

<!-- 결제 모달 -->
<div id="posPaymentModal" class="fixed inset-0 bg-black/50 z-50 hidden flex items-center justify-center p-4" onclick="POS.closePayment(event)">
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-2xl w-full max-w-sm overflow-hidden" onclick="event.stopPropagation()">
        <div class="flex items-center justify-between p-4 border-b border-zinc-200 dark:border-zinc-700">
            <h3 class="text-base font-bold text-zinc-900 dark:text-white flex items-center">
                <svg class="w-5 h-5 mr-2 text-violet-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                <?= __('reservations.pos_btn_payment') ?>
            </h3>
            <button onclick="POS.closePayment()" class="p-1 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg">
                <svg class="w-5 h-5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="p-4 space-y-4">
            <input type="hidden" id="payReservationId">
            <!-- 금액 요약 -->
            <div class="space-y-2 text-sm">
                <div class="flex justify-between"><span class="text-zinc-500"><?= __('reservations.pos_pay_total') ?></span><span id="payTotalAmount" class="font-bold text-zinc-900 dark:text-white"></span></div>
                <div class="flex justify-between"><span class="text-zinc-500"><?= __('reservations.pos_pay_paid') ?></span><span id="payPaidAmount" class="font-medium text-emerald-600"></span></div>
                <div class="flex justify-between border-t border-zinc-200 dark:border-zinc-700 pt-2"><span class="font-bold text-zinc-900 dark:text-white"><?= __('reservations.pos_pay_remaining') ?></span><span id="payRemaining" class="font-bold text-lg text-violet-600"></span></div>
            </div>
            <!-- 결제 금액 -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('reservations.pos_pay_amount') ?></label>
                <input type="number" id="payAmount" min="0" step="1"
                       class="w-full h-12 px-4 text-lg font-bold border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-violet-500 focus:border-violet-500">
            </div>
            <!-- 결제 방법 -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('reservations.pos_pay_method') ?></label>
                <div class="grid grid-cols-3 gap-2">
                    <label class="cursor-pointer">
                        <input type="radio" name="pay_method_radio" value="card" checked class="sr-only peer" onchange="document.getElementById('payMethod').value='card'">
                        <div class="h-11 flex items-center justify-center rounded-lg border-2 border-zinc-200 dark:border-zinc-600 peer-checked:border-violet-500 peer-checked:bg-violet-50 dark:peer-checked:bg-violet-900/20 text-sm font-medium text-zinc-600 dark:text-zinc-300 peer-checked:text-violet-700 dark:peer-checked:text-violet-400 transition">
                            <?= __('reservations.pos_pay_card') ?>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="pay_method_radio" value="cash" class="sr-only peer" onchange="document.getElementById('payMethod').value='cash'">
                        <div class="h-11 flex items-center justify-center rounded-lg border-2 border-zinc-200 dark:border-zinc-600 peer-checked:border-violet-500 peer-checked:bg-violet-50 dark:peer-checked:bg-violet-900/20 text-sm font-medium text-zinc-600 dark:text-zinc-300 peer-checked:text-violet-700 dark:peer-checked:text-violet-400 transition">
                            <?= __('reservations.pos_pay_cash') ?>
                        </div>
                    </label>
                    <label class="cursor-pointer">
                        <input type="radio" name="pay_method_radio" value="transfer" class="sr-only peer" onchange="document.getElementById('payMethod').value='transfer'">
                        <div class="h-11 flex items-center justify-center rounded-lg border-2 border-zinc-200 dark:border-zinc-600 peer-checked:border-violet-500 peer-checked:bg-violet-50 dark:peer-checked:bg-violet-900/20 text-sm font-medium text-zinc-600 dark:text-zinc-300 peer-checked:text-violet-700 dark:peer-checked:text-violet-400 transition">
                            <?= __('reservations.pos_pay_transfer') ?>
                        </div>
                    </label>
                </div>
                <input type="hidden" id="payMethod" value="card">
            </div>
        </div>
        <!-- 결제 버튼 -->
        <div class="px-4 pb-4">
            <button onclick="POS.submitPayment()"
                    class="w-full h-12 bg-violet-600 hover:bg-violet-700 active:bg-violet-800 text-white rounded-lg text-base font-bold transition">
                <?= __('reservations.pos_pay_submit') ?>
            </button>
        </div>
    </div>
</div>

<?php include BASE_PATH . '/resources/views/admin/components/reservation-form-js.php'; ?>
<?php include __DIR__ . '/pos-js.php'; ?>
<?php include __DIR__ . '/_foot.php'; ?>
