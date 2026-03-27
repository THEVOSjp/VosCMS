<?php
/**
 * 예약 캘린더 페이지 - 풀스크린 (공용 캘린더 로더 사용)
 */
include __DIR__ . '/_init.php';

// 공통 캘린더 데이터 로드
$_calYear  = (int)($_GET['year'] ?? date('Y'));
$_calMonth = (int)($_GET['month'] ?? date('m'));
include BASE_PATH . '/resources/views/admin/components/calendar-loader.php';

// 풀스크린 전용 URL 오버라이드
$cal['prevUrl']  = $adminUrl . '/reservations/calendar?year=' . $cal['prevYear'] . '&month=' . $cal['prevMonth'];
$cal['nextUrl']  = $adminUrl . '/reservations/calendar?year=' . $cal['nextYear'] . '&month=' . $cal['nextMonth'];
$cal['backUrl']  = $adminUrl . '/reservations';
$cal['mode']     = 'fullscreen';

$pageTitle = __('reservations.calendar') . ' - ' . sprintf('%04d-%02d', $_calYear, $_calMonth);
$pageHeaderTitle = __('reservations.calendar') ?? '캘린더';

include __DIR__ . '/_head.php';

include BASE_PATH . '/resources/views/admin/components/calendar-grid.php';
include BASE_PATH . '/resources/views/admin/components/calendar-modals.php';
include BASE_PATH . '/resources/views/admin/components/calendar-js.php';

include __DIR__ . '/_foot.php';
