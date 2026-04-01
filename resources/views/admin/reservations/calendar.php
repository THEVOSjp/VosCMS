<?php
/**
 * 예약 캘린더 페이지 - 풀스크린 (공용 캘린더 로더 사용)
 * 뷰 모드: month (월별), week (주간), day (일별)
 */
include __DIR__ . '/_init.php';

// 뷰 모드 & 날짜 파라미터
$_calView  = $_GET['view'] ?? 'month';
if (!in_array($_calView, ['month', 'week', 'day'])) $_calView = 'month';
$_calYear  = (int)($_GET['year'] ?? date('Y'));
$_calMonth = (int)($_GET['month'] ?? date('m'));
$_calDay   = (int)($_GET['day'] ?? date('j'));

// 공통 캘린더 데이터 로드
include BASE_PATH . '/resources/views/admin/components/calendar-loader.php';

// 뷰별 URL 헬퍼
function calUrl(string $adminUrl, string $view, int $y, int $m, int $d = 1): string {
    return $adminUrl . '/reservations/calendar?view=' . $view . '&year=' . $y . '&month=' . $m . '&day=' . $d;
}

// 풀스크린 전용 URL 오버라이드
$cal['view']     = $_calView;
$cal['day']      = $_calDay;
$cal['prevUrl']  = calUrl($adminUrl, $_calView, $cal['prevYear'], $cal['prevMonth']);
$cal['nextUrl']  = calUrl($adminUrl, $_calView, $cal['nextYear'], $cal['nextMonth']);
$cal['backUrl']  = $adminUrl . '/reservations';
$cal['mode']     = 'fullscreen';

// 주간/일별 네비게이션 URL 계산
if ($_calView === 'week') {
    // 현재 주의 시작일(일요일) 기준
    $currentDate = mktime(0, 0, 0, $_calMonth, $_calDay, $_calYear);
    $weekStart = $currentDate - (date('w', $currentDate) * 86400);
    $prevWeekStart = $weekStart - (7 * 86400);
    $nextWeekStart = $weekStart + (7 * 86400);
    $cal['prevUrl'] = calUrl($adminUrl, 'week', (int)date('Y', $prevWeekStart), (int)date('n', $prevWeekStart), (int)date('j', $prevWeekStart));
    $cal['nextUrl'] = calUrl($adminUrl, 'week', (int)date('Y', $nextWeekStart), (int)date('n', $nextWeekStart), (int)date('j', $nextWeekStart));
    $cal['weekStart'] = $weekStart;
} elseif ($_calView === 'day') {
    $currentDate = mktime(0, 0, 0, $_calMonth, $_calDay, $_calYear);
    $prevDay = $currentDate - 86400;
    $nextDay = $currentDate + 86400;
    $cal['prevUrl'] = calUrl($adminUrl, 'day', (int)date('Y', $prevDay), (int)date('n', $prevDay), (int)date('j', $prevDay));
    $cal['nextUrl'] = calUrl($adminUrl, 'day', (int)date('Y', $nextDay), (int)date('n', $nextDay), (int)date('j', $nextDay));
}

// 뷰 전환 URL
$cal['monthUrl'] = calUrl($adminUrl, 'month', $_calYear, $_calMonth);
$cal['weekUrl']  = calUrl($adminUrl, 'week', $_calYear, $_calMonth, $_calDay ?: (int)date('j'));
$cal['dayUrl']   = calUrl($adminUrl, 'day', $_calYear, $_calMonth, $_calDay ?: (int)date('j'));

$pageTitle = __('reservations.calendar') . ' - ' . sprintf('%04d-%02d', $_calYear, $_calMonth);
$pageHeaderTitle = __('reservations.calendar') ?? '캘린더';

include __DIR__ . '/_head.php';

include BASE_PATH . '/resources/views/admin/components/calendar-grid.php';
include BASE_PATH . '/resources/views/admin/components/calendar-modals.php';
include BASE_PATH . '/resources/views/admin/components/calendar-js.php';

include __DIR__ . '/_foot.php';
