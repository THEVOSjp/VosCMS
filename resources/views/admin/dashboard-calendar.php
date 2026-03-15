<?php
/**
 * 대시보드 예약 캘린더 — 공용 컴포넌트 사용
 * 변수: $calYear, $calMonth, $calFirstDay, $calByDate, $calReservations,
 *       $adminUrl, $dashCurrencySymbol, $dashCurrencyPosition, $dashServices
 */

$daysInMonth = (int)date('t', strtotime($calFirstDay));
$startWeekday = (int)date('w', strtotime($calFirstDay));
$totalWeeks = ceil(($startWeekday + $daysInMonth) / 7);

$prevYear = $calMonth == 1 ? $calYear - 1 : $calYear;
$prevMonth = $calMonth == 1 ? 12 : $calMonth - 1;
$nextYear = $calMonth == 12 ? $calYear + 1 : $calYear;
$nextMonth = $calMonth == 12 ? 1 : $calMonth + 1;

// 공용 캘린더 컴포넌트에 전달할 설정
$cal = [
    'year'             => $calYear,
    'month'            => $calMonth,
    'daysInMonth'      => $daysInMonth,
    'startWeekday'     => $startWeekday,
    'totalWeeks'       => $totalWeeks,
    'byDate'           => $calByDate,
    'reservations'     => $calReservations,
    'adminUrl'         => $adminUrl,
    'csrfToken'        => $_SESSION['csrf_token'] ?? '',
    'currencySymbol'   => $dashCurrencySymbol,
    'currencyPosition' => $dashCurrencyPosition,
    'services'         => $dashServices ?? [],
    'prevUrl'          => $adminUrl . '?cal_year=' . $prevYear . '&cal_month=' . $prevMonth,
    'nextUrl'          => $adminUrl . '?cal_year=' . $nextYear . '&cal_month=' . $nextMonth,
    'backUrl'          => null, // 대시보드에서는 뒤로가기 없음
    'mode'             => 'embedded',
];

include BASE_PATH . '/resources/views/admin/components/calendar-grid.php';
include BASE_PATH . '/resources/views/admin/components/calendar-modals.php';
include BASE_PATH . '/resources/views/admin/components/calendar-js.php';
