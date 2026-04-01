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
 *   prevUrl, nextUrl — 이전/다음 URL
 *   backUrl      — 뒤로가기 URL (null이면 표시 안함)
 *   mode         — 'fullscreen' | 'embedded'
 *   view         — 'month' | 'week' | 'day' (기본: month)
 */

$isFullscreen = ($cal['mode'] ?? 'embedded') === 'fullscreen';
$calView = $cal['view'] ?? 'month';
$today = date('Y-m-d');
$uid = 'cal' . $cal['year'] . $cal['month'];

// 요일 헤더 로드
$_calLocale = $config['locale'] ?? 'ko';
$_calLangFile = BASE_PATH . '/resources/lang/' . $_calLocale . '/reservations.php';
$_calLang = file_exists($_calLangFile) ? (include $_calLangFile) : [];
$weekdays = $_calLang['cal_weekdays'] ?? ['일','월','화','수','목','금','토'];

// 상태별 색상
function _calStatusClass(string $status): string {
    return match($status) {
        'pending'   => 'border-l-yellow-400 bg-yellow-50 dark:bg-yellow-900/20',
        'confirmed' => 'border-l-blue-400 bg-blue-50 dark:bg-blue-900/20',
        'completed' => 'border-l-green-400 bg-green-50 dark:bg-green-900/20',
        'cancelled' => 'border-l-red-400 bg-red-50 dark:bg-red-900/20',
        'no_show'   => 'border-l-zinc-400 bg-zinc-50 dark:bg-zinc-700/30',
        default     => 'border-l-zinc-300 bg-zinc-50 dark:bg-zinc-700/30',
    };
}
function _calStatusBg(string $status): string {
    return match($status) {
        'pending'   => 'bg-yellow-400',
        'confirmed' => 'bg-blue-400',
        'completed' => 'bg-green-400',
        'cancelled' => 'bg-red-400',
        'no_show'   => 'bg-zinc-400',
        default     => 'bg-zinc-300',
    };
}
?>

<?php if ($isFullscreen && $calView === 'month'): ?>
<style>
    .rzx-cal-wrap { display: flex; flex-direction: column; height: calc(100vh - 130px); }
    .rzx-cal-grid { flex: 1; display: grid; grid-template-columns: repeat(7, 1fr); grid-template-rows: auto repeat(<?= $cal['totalWeeks'] ?>, 1fr); overflow: hidden; }
</style>
<?php elseif ($calView === 'month'): ?>
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
    .rzx-view-btn { transition: all 0.15s; }
    .rzx-view-btn.active { background: #2563eb; color: #fff; }
</style>

<?php if ($isFullscreen): ?>
<div class="overflow-x-hidden">
<?php else: ?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden mt-8">
<?php endif; ?>

<!-- 상단 바: 네비게이션 + 뷰 전환 + 범례 -->
<div class="flex items-center justify-between <?= $isFullscreen ? 'mb-3 sticky top-0 z-30 bg-zinc-100 dark:bg-zinc-900 py-2' : 'p-4 border-b border-zinc-200 dark:border-zinc-700' ?>">
    <div class="flex items-center gap-3">
        <?php if (!empty($cal['backUrl'])): ?>
        <button type="button" onclick="history.back()" class="p-2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
        <?php endif; ?>
        <a href="<?= $cal['prevUrl'] ?>" class="p-1.5 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-lg transition">
            <svg class="w-4 h-4 text-zinc-600 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <h2 class="<?= $isFullscreen ? 'text-xl' : 'text-lg' ?> font-bold text-zinc-900 dark:text-white">
            <?php if (!$isFullscreen): ?>
            <svg class="w-5 h-5 mr-1.5 text-blue-600 inline-block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <?php endif; ?>
            <?php if ($calView === 'month'): ?>
                <?= str_replace([':year', ':month'], [sprintf('%04d', $cal['year']), sprintf('%02d', $cal['month'])], __('reservations.cal_year_month')) ?>
            <?php elseif ($calView === 'week'): ?>
                <?php
                $wStart = $cal['weekStart'] ?? mktime(0, 0, 0, $cal['month'], ($cal['day'] ?? 1) - date('w', mktime(0, 0, 0, $cal['month'], $cal['day'] ?? 1, $cal['year'])), $cal['year']);
                $wEnd = $wStart + 6 * 86400;
                echo date('m/d', $wStart) . ' ~ ' . date('m/d', $wEnd);
                ?>
            <?php else: ?>
                <?= sprintf('%04d-%02d-%02d', $cal['year'], $cal['month'], $cal['day'] ?? 1) ?>
                <?php
                $dayTs = mktime(0, 0, 0, $cal['month'], $cal['day'] ?? 1, $cal['year']);
                $dayWd = (int)date('w', $dayTs);
                echo ' (' . $weekdays[$dayWd] . ')';
                ?>
            <?php endif; ?>
        </h2>
        <a href="<?= $cal['nextUrl'] ?>" class="p-1.5 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-lg transition">
            <svg class="w-4 h-4 text-zinc-600 dark:text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
        <span class="text-sm text-zinc-500 dark:text-zinc-400 ml-2"><?= str_replace(':count', count($cal['reservations']), __('reservations.cal_count')) ?></span>
    </div>
    <div class="flex items-center gap-3">
        <!-- 뷰 전환 버튼 -->
        <?php if ($isFullscreen && !empty($cal['monthUrl'])): ?>
        <div class="flex items-center bg-zinc-100 dark:bg-zinc-700 rounded-lg p-0.5">
            <a href="<?= $cal['monthUrl'] ?>" class="rzx-view-btn <?= $calView === 'month' ? 'active' : 'text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600' ?> px-3 py-1 rounded-md text-xs font-medium">
                <?= __('reservations.cal_view_month') ?>
            </a>
            <a href="<?= $cal['weekUrl'] ?>" class="rzx-view-btn <?= $calView === 'week' ? 'active' : 'text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600' ?> px-3 py-1 rounded-md text-xs font-medium">
                <?= __('reservations.cal_view_week') ?>
            </a>
            <a href="<?= $cal['dayUrl'] ?>" class="rzx-view-btn <?= $calView === 'day' ? 'active' : 'text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600' ?> px-3 py-1 rounded-md text-xs font-medium">
                <?= __('reservations.cal_view_day') ?>
            </a>
        </div>
        <?php endif; ?>
        <div class="flex items-center gap-2 text-[11px] text-zinc-500 dark:text-zinc-400">
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-yellow-400"></span><?= __('reservations.cal_legend_pending') ?></span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-blue-400"></span><?= __('reservations.cal_legend_confirmed') ?></span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-green-400"></span><?= __('reservations.cal_legend_completed') ?></span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-red-400"></span><?= __('reservations.cal_legend_cancelled') ?></span>
            <span class="flex items-center gap-1"><span class="w-2.5 h-2.5 rounded-sm bg-zinc-400"></span><?= __('reservations.cal_legend_noshow') ?></span>
        </div>
        <a href="<?= $cal['adminUrl'] ?>/reservations/create" class="px-3 py-1.5 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm transition">+ <?= __('reservations.cal_add') ?></a>
    </div>
</div>

<?php if ($calView === 'month'): ?>
<!-- ═══════════════ 월별 뷰 ═══════════════ -->
<div class="<?= $isFullscreen ? 'rzx-cal-wrap bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden' : '' ?>">
    <div class="rzx-cal-grid">
        <!-- 요일 헤더 -->
        <?php foreach ($weekdays as $i => $wd):
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
            <?php foreach ($dayList as $rv): ?>
            <div class="rzx-cal-item border-l-2 rounded-r px-1 py-0.5 mb-0.5 <?= _calStatusClass($rv['status']) ?>"
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

<?php elseif ($calView === 'week'): ?>
<!-- ═══════════════ 주간 뷰 ═══════════════ -->
<?php
$weekStartTs = $cal['weekStart'] ?? mktime(0, 0, 0, $cal['month'], ($cal['day'] ?? 1) - date('w', mktime(0, 0, 0, $cal['month'], $cal['day'] ?? 1, $cal['year'])), $cal['year']);
$weekDates = [];
for ($d = 0; $d < 7; $d++) {
    $ts = $weekStartTs + ($d * 86400);
    $weekDates[] = [
        'date' => date('Y-m-d', $ts),
        'day'  => (int)date('j', $ts),
        'month'=> (int)date('n', $ts),
        'wd'   => $d,
        'ts'   => $ts,
    ];
}

// ── 영업시간 설정 읽기 ──
$_bhStart = (int)explode(':', $siteSettings['business_hour_start'] ?? '08:00')[0];
$_bhEnd   = (int)explode(':', $siteSettings['business_hour_end'] ?? '22:00')[0];
if ((int)(explode(':', $siteSettings['business_hour_end'] ?? '22:00')[1] ?? 0) > 0) $_bhEnd++;

// ── 시간 범위 계산 (영업시간 기준 + 예약 범위 확장) ──
$_wkMinHour = $_bhStart; $_wkMaxHour = $_bhEnd;
foreach ($weekDates as $wd) {
    $dayRvs = $cal['byDate'][$wd['date']] ?? [];
    foreach ($dayRvs as $rv) {
        $sh = (int)explode(':', $rv['start_time'])[0];
        $ep = explode(':', $rv['end_time'] ?? $rv['start_time']);
        $eh = (int)$ep[0];
        if ((int)($ep[1] ?? 0) > 0) $eh++;
        $_wkMinHour = min($_wkMinHour, $sh);
        $_wkMaxHour = max($_wkMaxHour, $eh);
    }
}
$_wkMinHour = max(0, $_wkMinHour - 1);  // 1시간 여유
$_wkMaxHour = min(24, $_wkMaxHour + 1);
$_wkHours = $_wkMaxHour - $_wkMinHour;

$_wkPxPerHour = 150; // 1시간 = 150px
$_wkPxPerMin = $_wkPxPerHour / 60;
$_wkTotalW = $_wkHours * $_wkPxPerHour;
$_wkLineH = 28;
$_wkLinePad = 2;
$_wkMinRowH = 36; // 최소 행 높이

// ── 레인 계산 ──
$_wkLanes = [];
foreach ($weekDates as $wd) {
    $cellDate = $wd['date'];
    $dayRvs = $cal['byDate'][$cellDate] ?? [];
    $lanes = [];
    $rvLanes = [];

    usort($dayRvs, function($a, $b) { return strcmp($a['start_time'], $b['start_time']); });

    foreach ($dayRvs as $ri => $rv) {
        $sp = explode(':', $rv['start_time']);
        $ep = explode(':', $rv['end_time'] ?? $rv['start_time']);
        $sMin = ((int)$sp[0] * 60) + (int)($sp[1] ?? 0);
        $eMin = ((int)$ep[0] * 60) + (int)($ep[1] ?? 0);
        if ($eMin <= $sMin) $eMin = $sMin + 30;

        $assigned = false;
        for ($li = 0; $li < count($lanes); $li++) {
            if ($sMin >= $lanes[$li]) {
                $lanes[$li] = $eMin;
                $rvLanes[$ri] = $li;
                $assigned = true;
                break;
            }
        }
        if (!$assigned) {
            $rvLanes[$ri] = count($lanes);
            $lanes[] = $eMin;
        }
    }

    $maxLanes = max(count($lanes), 1);
    $rowH = max($maxLanes * ($_wkLineH + $_wkLinePad) + $_wkLinePad, $_wkMinRowH);
    $_wkLanes[$cellDate] = [
        'reservations' => $dayRvs,
        'rvLanes' => $rvLanes,
        'maxLanes' => $maxLanes,
        'rowH' => $rowH,
    ];
}
$_wkOffsetMin = $_wkMinHour * 60; // left 좌표 계산시 빼줄 오프셋(분)
?>
<div class="<?= $isFullscreen ? 'bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden' : '' ?>" style="<?= $isFullscreen ? 'height: calc(100vh - 130px); display:flex; flex-direction:column;' : '' ?>">
    <div class="flex flex-1 overflow-hidden">
        <!-- 좌측: 요일 라벨 (고정) -->
        <div class="flex-shrink-0 border-r border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/50 overflow-y-auto" style="width: 64px;" id="wkSidebarScroll">
            <div class="h-8 border-b border-zinc-200 dark:border-zinc-700 flex-shrink-0"></div>
            <?php foreach ($weekDates as $wd):
                $hColor = $wd['wd'] === 0 ? 'text-red-500' : ($wd['wd'] === 6 ? 'text-blue-500' : 'text-zinc-600 dark:text-zinc-400');
                $isWdToday = ($wd['date'] === $today);
                $rowH = $_wkLanes[$wd['date']]['rowH'];
                $dayCount = count($cal['byDate'][$wd['date']] ?? []);
            ?>
            <div class="flex items-center justify-center border-b border-zinc-200 dark:border-zinc-700 <?= $isWdToday ? 'bg-blue-50 dark:bg-blue-900/20' : '' ?>" style="height: <?= $rowH ?>px; min-height: <?= $rowH ?>px;">
                <div class="text-center">
                    <div class="<?= $isWdToday ? 'bg-blue-600 text-white w-7 h-7 rounded-full flex items-center justify-center mx-auto text-sm font-bold' : 'text-sm font-bold text-zinc-700 dark:text-zinc-300' ?>">
                        <?= $wd['day'] ?>
                    </div>
                    <div class="text-[10px] font-medium <?= $hColor ?>"><?= $weekdays[$wd['wd']] ?></div>
                    <?php if ($dayCount > 0): ?>
                    <div class="text-[9px] text-zinc-400 dark:text-zinc-500"><?= $dayCount ?></div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <!-- 우측: 시간 그리드 -->
        <div class="flex-1 overflow-auto" id="wkGridScroll">
            <div style="width: <?= $_wkTotalW ?>px;">
                <!-- 시간 헤더 -->
                <div class="sticky top-0 z-20 relative h-8 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/50">
                    <?php for ($h = $_wkMinHour; $h < $_wkMaxHour; $h++): ?>
                    <div class="absolute top-0 bottom-0 flex items-center border-l border-zinc-200 dark:border-zinc-700 pl-1.5" style="left: <?= ($h - $_wkMinHour) * $_wkPxPerHour ?>px;">
                        <span class="text-[11px] text-zinc-500 dark:text-zinc-400 font-mono font-medium"><?= sprintf('%02d:00', $h) ?></span>
                    </div>
                    <?php endfor; ?>
                </div>
                <!-- 요일별 행 -->
                <?php foreach ($weekDates as $wd):
                    $cellDate = $wd['date'];
                    $isWdToday = ($cellDate === $today);
                    $rowBg = $isWdToday ? 'bg-blue-50/30 dark:bg-blue-900/5' : '';
                    $laneData = $_wkLanes[$cellDate];
                    $rowH = $laneData['rowH'];
                ?>
                <div class="relative border-b border-zinc-200 dark:border-zinc-700 <?= $rowBg ?> rzx-time-row" style="height: <?= $rowH ?>px;" data-px-per-hour="<?= $_wkPxPerHour ?>" data-offset-hour="<?= $_wkMinHour ?>" onclick="rzxCalQuickAdd('<?= $cellDate ?>', event)">
                    <?php for ($h = $_wkMinHour; $h < $_wkMaxHour; $h++): ?>
                    <div class="absolute top-0 bottom-0 border-l border-zinc-200 dark:border-zinc-700" style="left: <?= ($h - $_wkMinHour) * $_wkPxPerHour ?>px;"></div>
                    <div class="absolute top-0 bottom-0 border-l border-dashed border-zinc-100 dark:border-zinc-800" style="left: <?= ($h - $_wkMinHour) * $_wkPxPerHour + $_wkPxPerHour / 2 ?>px;"></div>
                    <?php endfor; ?>
                    <?php foreach ($laneData['reservations'] as $ri => $rv):
                        $sp = explode(':', $rv['start_time']);
                        $ep = explode(':', $rv['end_time'] ?? $rv['start_time']);
                        $sMin = ((int)$sp[0] * 60) + (int)($sp[1] ?? 0);
                        $eMin = ((int)$ep[0] * 60) + (int)($ep[1] ?? 0);
                        if ($eMin <= $sMin) $eMin = $sMin + 30;
                        $leftPx = round(($sMin - $_wkOffsetMin) * $_wkPxPerMin);
                        $wPx    = max(round(($eMin - $sMin) * $_wkPxPerMin), 60);
                        $lane   = $laneData['rvLanes'][$ri] ?? 0;
                        $topPx  = $_wkLinePad + $lane * ($_wkLineH + $_wkLinePad);
                    ?>
                    <div class="absolute z-10 rounded border-l-2 px-2 flex items-center gap-1.5 overflow-hidden whitespace-nowrap text-[11px] <?= _calStatusClass($rv['status']) ?> rzx-cal-item"
                         style="left: <?= $leftPx ?>px; width: <?= $wPx ?>px; top: <?= $topPx ?>px; height: <?= $_wkLineH ?>px;"
                         onclick="event.stopPropagation(); rzxCalShowDetail(<?= htmlspecialchars(json_encode($rv, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>)">
                        <span class="font-semibold text-zinc-800 dark:text-zinc-200 flex-shrink-0"><?= substr($rv['start_time'], 0, 5) ?>~<?= substr($rv['end_time'] ?? '', 0, 5) ?></span>
                        <span class="font-medium text-zinc-700 dark:text-zinc-300 truncate"><?= htmlspecialchars(mb_substr($rv['customer_name'], 0, 8)) ?></span>
                        <span class="text-zinc-500 dark:text-zinc-400 truncate"><?= htmlspecialchars(mb_substr($rv['service_name'] ?? '', 0, 14)) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<script>
(function(){
    const grid = document.getElementById('wkGridScroll');
    const side = document.getElementById('wkSidebarScroll');
    if (!grid || !side) return;
    let syncing = false;
    grid.addEventListener('scroll', function(){
        if (syncing) return; syncing = true;
        side.scrollTop = grid.scrollTop;
        syncing = false;
    });
    side.addEventListener('scroll', function(){
        if (syncing) return; syncing = true;
        grid.scrollTop = side.scrollTop;
        syncing = false;
    });
})();
</script>

<?php else: ?>
<!-- ═══════════════ 일별 뷰 ═══════════════ -->
<?php
$dayDateStr = sprintf('%04d-%02d-%02d', $cal['year'], $cal['month'], $cal['day'] ?? 1);
$dayReservations = $cal['byDate'][$dayDateStr] ?? [];

// ── 시간 범위 계산 (영업시간 기준 + 예약 범위 확장) ──
$_dayMinH = $_bhStart ?? (int)explode(':', $siteSettings['business_hour_start'] ?? '08:00')[0];
$_dayMaxH = $_bhEnd ?? (int)explode(':', $siteSettings['business_hour_end'] ?? '22:00')[0];
foreach ($dayReservations as $rv) {
    $sh = (int)explode(':', $rv['start_time'])[0];
    $ep = explode(':', $rv['end_time'] ?? $rv['start_time']);
    $eh = (int)$ep[0];
    if ((int)($ep[1] ?? 0) > 0) $eh++;
    $_dayMinH = min($_dayMinH, $sh);
    $_dayMaxH = max($_dayMaxH, $eh);
}
$_dayMaxH = min(24, $_dayMaxH + 1);
$_dayHours = $_dayMaxH - $_dayMinH;

$_dayPxPerHour = 150;
$_dayPxPerMin = $_dayPxPerHour / 60;
$_dayTotalW = $_dayHours * $_dayPxPerHour;
$_dayLineH = 32;
$_dayLinePad = 2;
$_dayOffsetMin = $_dayMinH * 60;

// 레인 계산
usort($dayReservations, function($a, $b) { return strcmp($a['start_time'], $b['start_time']); });
$_dayLanes = [];
$_dayRvLanes = [];
foreach ($dayReservations as $ri => $rv) {
    $sp = explode(':', $rv['start_time']);
    $ep = explode(':', $rv['end_time'] ?? $rv['start_time']);
    $sMin = ((int)$sp[0] * 60) + (int)($sp[1] ?? 0);
    $eMin = ((int)$ep[0] * 60) + (int)($ep[1] ?? 0);
    if ($eMin <= $sMin) $eMin = $sMin + 30;

    $assigned = false;
    for ($li = 0; $li < count($_dayLanes); $li++) {
        if ($sMin >= $_dayLanes[$li]) {
            $_dayLanes[$li] = $eMin;
            $_dayRvLanes[$ri] = $li;
            $assigned = true;
            break;
        }
    }
    if (!$assigned) {
        $_dayRvLanes[$ri] = count($_dayLanes);
        $_dayLanes[] = $eMin;
    }
}
$_dayMaxLanes = max(count($_dayLanes), 1);
$_dayGridH = $_dayMaxLanes * ($_dayLineH + $_dayLinePad) + $_dayLinePad;
?>
<div class="<?= $isFullscreen ? 'bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden' : '' ?>" style="<?= $isFullscreen ? 'height: calc(100vh - 130px); display:flex; flex-direction:column;' : '' ?>">
    <div class="flex-1 overflow-auto">
        <div style="width: <?= $_dayTotalW ?>px;">
            <!-- 시간 헤더 -->
            <div class="sticky top-0 z-20 relative h-8 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/50">
                <?php for ($h = $_dayMinH; $h < $_dayMaxH; $h++): ?>
                <div class="absolute top-0 bottom-0 flex items-center border-l border-zinc-200 dark:border-zinc-700 pl-1.5" style="left: <?= ($h - $_dayMinH) * $_dayPxPerHour ?>px;">
                    <span class="text-[11px] text-zinc-500 dark:text-zinc-400 font-mono font-medium"><?= sprintf('%02d:00', $h) ?></span>
                </div>
                <?php endfor; ?>
            </div>
            <!-- 예약 그리드 -->
            <div class="relative rzx-time-row" style="height: <?= max($_dayGridH, 200) ?>px;" data-px-per-hour="<?= $_dayPxPerHour ?>" data-offset-hour="<?= $_dayMinH ?>" onclick="rzxCalQuickAdd('<?= $dayDateStr ?>', event)">
                <?php for ($h = $_dayMinH; $h < $_dayMaxH; $h++): ?>
                <div class="absolute top-0 bottom-0 border-l border-zinc-200 dark:border-zinc-700" style="left: <?= ($h - $_dayMinH) * $_dayPxPerHour ?>px;"></div>
                <div class="absolute top-0 bottom-0 border-l border-dashed border-zinc-100 dark:border-zinc-800" style="left: <?= ($h - $_dayMinH) * $_dayPxPerHour + $_dayPxPerHour / 2 ?>px;"></div>
                <?php endfor; ?>
                <?php foreach ($dayReservations as $ri => $rv):
                    $sp = explode(':', $rv['start_time']);
                    $ep = explode(':', $rv['end_time'] ?? $rv['start_time']);
                    $sMin = ((int)$sp[0] * 60) + (int)($sp[1] ?? 0);
                    $eMin = ((int)$ep[0] * 60) + (int)($ep[1] ?? 0);
                    if ($eMin <= $sMin) $eMin = $sMin + 30;
                    $leftPx = round(($sMin - $_dayOffsetMin) * $_dayPxPerMin);
                    $wPx    = max(round(($eMin - $sMin) * $_dayPxPerMin), 80);
                    $lane   = $_dayRvLanes[$ri] ?? 0;
                    $topPx  = $_dayLinePad + $lane * ($_dayLineH + $_dayLinePad);
                ?>
                <div class="absolute z-10 rounded border-l-2 px-2.5 flex items-center gap-2 overflow-hidden whitespace-nowrap text-xs <?= _calStatusClass($rv['status']) ?> rzx-cal-item"
                     style="left: <?= $leftPx ?>px; width: <?= $wPx ?>px; top: <?= $topPx ?>px; height: <?= $_dayLineH ?>px;"
                     onclick="event.stopPropagation(); rzxCalShowDetail(<?= htmlspecialchars(json_encode($rv, JSON_UNESCAPED_UNICODE), ENT_QUOTES) ?>)">
                    <span class="font-bold text-zinc-800 dark:text-zinc-200 flex-shrink-0"><?= substr($rv['start_time'], 0, 5) ?>~<?= substr($rv['end_time'] ?? '', 0, 5) ?></span>
                    <span class="font-medium text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($rv['customer_name']) ?></span>
                    <span class="text-zinc-500 dark:text-zinc-400 truncate"><?= htmlspecialchars($rv['service_name'] ?? '') ?></span>
                    <?php if (!empty($rv['final_amount'])): ?>
                    <span class="text-zinc-400 dark:text-zinc-500 flex-shrink-0 ml-auto">
                        <?= ($cal['currencyPosition'] === 'suffix' ? number_format((float)$rv['final_amount']) . $cal['currencySymbol'] : $cal['currencySymbol'] . number_format((float)$rv['final_amount'])) ?>
                    </span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

</div><!-- /calendar outer wrapper -->
