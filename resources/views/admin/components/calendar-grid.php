<?php
/**
 * 재사용 가능 캘린더 그리드 컴포넌트
 *
 * 필수 변수 ($cal 배열):
 *   year, month, daysInMonth, startWeekday, totalWeeks
 *   byDate       — 날짜별 예약 배열 ['2026-03-15' => [...]]
 *   reservations — 전체 예약 목록 (건수 표시용)
 *   adminUrl, csrfToken
 *   currencySymbol, currencyPosition
 *   services     — 빠른 예약 추가용 서비스 목록
 *   prevUrl, nextUrl — 이전/다음 월 URL
 *   backUrl      — 뒤로가기 URL (null이면 표시 안함)
 *   mode         — 'fullscreen' | 'embedded'
 */

$isFullscreen = ($cal['mode'] ?? 'embedded') === 'fullscreen';
$today = date('Y-m-d');
$uid = 'cal' . $cal['year'] . $cal['month']; // 고유 접두사
?>

<?php if ($isFullscreen): ?>
<style>
    .rzx-cal-wrap { display: flex; flex-direction: column; height: calc(100vh - 130px); }
    .rzx-cal-grid { flex: 1; display: grid; grid-template-columns: repeat(7, 1fr); grid-template-rows: auto repeat(<?= $cal['totalWeeks'] ?>, 1fr); overflow: hidden; }
</style>
<?php else: ?>
<style>
    .rzx-cal-grid { display: grid; grid-template-columns: repeat(7, 1fr); grid-template-rows: auto repeat(<?= $cal['totalWeeks'] ?>, minmax(90px, 1fr)); overflow: hidden; }
</style>
<?php endif; ?>

<style>
    .rzx-cal-cell { overflow-y: auto; scrollbar-width: thin; scrollbar-color: rgba(155,155,155,0.3) transparent; }
    .rzx-cal-cell::-webkit-scrollbar { width: 3px; }
    .rzx-cal-cell::-webkit-scrollbar-thumb { background: rgba(155,155,155,0.3); border-radius: 3px; }
    .rzx-cal-item { cursor: pointer; transition: opacity 0.15s; }
    .rzx-cal-item:hover { opacity: 0.8; }
</style>

<?php if (!$isFullscreen): ?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden mt-8">
<?php endif; ?>

<!-- 상단 바: 네비게이션 + 범례 -->
<div class="flex items-center justify-between <?= $isFullscreen ? 'mb-3' : 'p-4 border-b border-zinc-200 dark:border-zinc-700' ?>">
    <div class="flex items-center gap-3">
        <?php if (!empty($cal['backUrl'])): ?>
        <a href="<?= $cal['backUrl'] ?>" class="p-2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <?php endif; ?>
        <a href="<?= $cal['prevUrl'] ?>" class="p-1.5 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-lg transition">
            <svg class="w-4 h-4 text-zinc-600 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h2 class="<?= $isFullscreen ? 'text-xl' : 'text-lg' ?> font-bold text-zinc-900 dark:text-white">
            <?php if (!$isFullscreen): ?>
            <svg class="w-5 h-5 mr-1.5 text-blue-600 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <?php endif; ?>
            <?= sprintf('%04d년 %02d월', $cal['year'], $cal['month']) ?><?= $isFullscreen ? '' : ' 예약 현황' ?>
        </h2>
        <a href="<?= $cal['nextUrl'] ?>" class="p-1.5 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-lg transition">
            <svg class="w-4 h-4 text-zinc-600 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
        <span class="text-sm text-zinc-500 dark:text-zinc-400 ml-2"><?= count($cal['reservations']) ?>건</span>
    </div>
    <div class="flex items-center gap-3">
        <div class="flex items-center gap-2 text-[11px] text-zinc-500 dark:text-zinc-400">
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-yellow-400"></span>대기</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-blue-400"></span>확정</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-green-400"></span>완료</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-red-400"></span>취소</span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-zinc-400"></span>노쇼</span>
        </div>
        <a href="<?= $cal['adminUrl'] ?>/reservations/create" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm transition">+ 예약 등록</a>
    </div>
</div>

<!-- 캘린더 그리드 -->
<div class="<?= $isFullscreen ? 'rzx-cal-wrap bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden' : '' ?>">
    <div class="rzx-cal-grid">
        <!-- 요일 헤더 -->
        <?php
        $weekdays = ['일', '월', '화', '수', '목', '금', '토'];
        foreach ($weekdays as $i => $wd):
            $hColor = $i === 0 ? 'text-red-500' : ($i === 6 ? 'text-blue-500' : 'text-zinc-600 dark:text-zinc-400');
        ?>
        <div class="py-2 text-center text-xs font-semibold <?= $hColor ?> border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/50"><?= $wd ?></div>
        <?php endforeach; ?>

        <!-- 빈 셀 (이전 달) -->
        <?php for ($i = 0; $i < $cal['startWeekday']; $i++): ?>
        <div class="border-b border-r border-zinc-100 dark:border-zinc-700/50 bg-zinc-50/30 dark:bg-zinc-900/20"></div>
        <?php endfor; ?>

        <!-- 날짜 셀 -->
        <?php for ($day = 1; $day <= $cal['daysInMonth']; $day++):
            $dateStr = sprintf('%04d-%02d-%02d', $cal['year'], $cal['month'], $day);
            $weekday = ($cal['startWeekday'] + $day - 1) % 7;
            $isToday = ($dateStr === $today);
            $dayList = $cal['byDate'][$dateStr] ?? [];
            $dayColor = $weekday === 0 ? 'text-red-500' : ($weekday === 6 ? 'text-blue-500' : 'text-zinc-700 dark:text-zinc-300');
            $todayBg = $isToday ? 'bg-blue-50/60 dark:bg-blue-900/10' : '';
        ?>
        <div class="rzx-cal-cell border-b border-r border-zinc-100 dark:border-zinc-700/50 p-1 <?= $todayBg ?> cursor-pointer" onclick="rzxCalQuickAdd('<?= $dateStr ?>', event)">
            <div class="flex items-center justify-between mb-0.5 px-0.5">
                <?php if ($isToday): ?>
                <span class="text-[11px] font-bold bg-blue-600 text-white w-5 h-5 rounded-full flex items-center justify-center"><?= $day ?></span>
                <?php else: ?>
                <span class="text-[11px] font-semibold <?= $dayColor ?>"><?= $day ?></span>
                <?php endif; ?>
                <?php if (!empty($dayList)): ?>
                <span class="text-[9px] text-zinc-400 dark:text-zinc-500"><?= count($dayList) ?></span>
                <?php endif; ?>
            </div>
            <?php foreach ($dayList as $rv):
                $sc = match($rv['status']) {
                    'pending'   => 'border-l-yellow-400 bg-yellow-50 dark:bg-yellow-900/20',
                    'confirmed' => 'border-l-blue-400 bg-blue-50 dark:bg-blue-900/20',
                    'completed' => 'border-l-green-400 bg-green-50 dark:bg-green-900/20',
                    'cancelled' => 'border-l-red-400 bg-red-50 dark:bg-red-900/20',
                    'no_show'   => 'border-l-zinc-400 bg-zinc-50 dark:bg-zinc-700/30',
                    default     => 'border-l-zinc-300 bg-zinc-50 dark:bg-zinc-700/30',
                };
            ?>
            <div class="rzx-cal-item border-l-2 rounded-r px-1 py-0.5 mb-0.5 <?= $sc ?>"
                 onclick="event.stopPropagation(); rzxCalShowDetail(<?= htmlspecialchars(json_encode($rv, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>)" title="<?= htmlspecialchars($rv['customer_name']) ?>">
                <div class="flex items-center gap-1 text-[10px] leading-tight">
                    <span class="font-medium text-zinc-800 dark:text-zinc-200 whitespace-nowrap"><?= substr($rv['start_time'], 0, 5) ?></span>
                    <span class="text-zinc-600 dark:text-zinc-400 truncate"><?= htmlspecialchars(mb_substr($rv['customer_name'], 0, 6)) ?></span>
                </div>
                <div class="text-[9px] text-zinc-500 dark:text-zinc-500 truncate"><?= htmlspecialchars(mb_substr($rv['service_name'] ?? '', 0, 10)) ?></div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endfor; ?>

        <!-- 빈 셀 (다음 달) -->
        <?php
        $remainingCells = (7 - ($cal['startWeekday'] + $cal['daysInMonth']) % 7) % 7;
        for ($i = 0; $i < $remainingCells; $i++):
        ?>
        <div class="border-b border-r border-zinc-100 dark:border-zinc-700/50 bg-zinc-50/30 dark:bg-zinc-900/20"></div>
        <?php endfor; ?>
    </div>
</div>

<?php if (!$isFullscreen): ?>
</div>
<?php endif; ?>
