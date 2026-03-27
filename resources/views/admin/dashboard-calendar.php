<?php
/**
 * 대시보드 예약 캘린더 — 공통 캘린더 로더 사용
 * $cal은 dashboard.php에서 calendar-loader.php로 이미 생성됨
 */

// 대시보드 전용 URL 오버라이드
$cal['adminUrl'] = $adminUrl;
$cal['prevUrl']  = $adminUrl . '?cal_year=' . $cal['prevYear'] . '&cal_month=' . $cal['prevMonth'];
$cal['nextUrl']  = $adminUrl . '?cal_year=' . $cal['nextYear'] . '&cal_month=' . $cal['nextMonth'];
$cal['backUrl']  = null; // 대시보드에서는 뒤로가기 없음
$cal['mode']     = 'embedded';

include BASE_PATH . '/resources/views/admin/components/calendar-grid.php';
include BASE_PATH . '/resources/views/admin/components/calendar-modals.php';
include BASE_PATH . '/resources/views/admin/components/calendar-js.php';
