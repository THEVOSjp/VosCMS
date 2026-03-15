<?php
/**
 * 예약 캘린더 페이지 - 풀스크린 (공용 컴포넌트 사용)
 */
include __DIR__ . '/_init.php';

$year = (int)($_GET['year'] ?? date('Y'));
$month = (int)($_GET['month'] ?? date('m'));

if ($month < 1) { $month = 12; $year--; }
if ($month > 12) { $month = 1; $year++; }

$firstDay = mktime(0, 0, 0, $month, 1, $year);
$daysInMonth = (int)date('t', $firstDay);
$startWeekday = (int)date('w', $firstDay);
$totalWeeks = ceil(($startWeekday + $daysInMonth) / 7);

$prevYear = $month == 1 ? $year - 1 : $year;
$prevMonth = $month == 1 ? 12 : $month - 1;
$nextYear = $month == 12 ? $year + 1 : $year;
$nextMonth = $month == 12 ? 1 : $month + 1;

$dateFrom = sprintf('%04d-%02d-01', $year, $month);
$dateTo = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);

$stmt = $pdo->prepare("SELECT r.*, (SELECT GROUP_CONCAT(rs.service_name ORDER BY rs.sort_order SEPARATOR ', ') FROM {$prefix}reservation_services rs WHERE rs.reservation_id = r.id) as service_name FROM {$prefix}reservations r WHERE r.reservation_date BETWEEN ? AND ? ORDER BY r.start_time ASC");
$stmt->execute([$dateFrom, $dateTo]);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$byDate = [];
foreach ($reservations as $r) {
    $byDate[$r['reservation_date']][] = $r;
}

// 서비스 목록 (빠른 예약 추가용)
$calServices = $pdo->query("SELECT s.id, s.name, s.description, s.duration, s.price, s.image, s.category_id, c.name as category_name FROM {$prefix}services s LEFT JOIN {$prefix}service_categories c ON s.category_id = c.id WHERE s.is_active = 1 ORDER BY s.sort_order ASC, s.name ASC")->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = __('reservations.calendar') . ' - ' . sprintf('%04d-%02d', $year, $month);

include __DIR__ . '/_head.php';

// 공용 캘린더 컴포넌트 설정
$cal = [
    'year'             => $year,
    'month'            => $month,
    'daysInMonth'      => $daysInMonth,
    'startWeekday'     => $startWeekday,
    'totalWeeks'       => $totalWeeks,
    'byDate'           => $byDate,
    'reservations'     => $reservations,
    'adminUrl'         => $adminUrl,
    'csrfToken'        => $csrfToken,
    'currencySymbol'   => $currencySymbol,
    'currencyPosition' => $currencyPosition,
    'services'         => $calServices,
    'prevUrl'          => $adminUrl . '/reservations/calendar?year=' . $prevYear . '&month=' . $prevMonth,
    'nextUrl'          => $adminUrl . '/reservations/calendar?year=' . $nextYear . '&month=' . $nextMonth,
    'backUrl'          => $adminUrl . '/reservations',
    'mode'             => 'fullscreen',
];

include BASE_PATH . '/resources/views/admin/components/calendar-grid.php';
include BASE_PATH . '/resources/views/admin/components/calendar-modals.php';
include BASE_PATH . '/resources/views/admin/components/calendar-js.php';

include __DIR__ . '/_foot.php';
