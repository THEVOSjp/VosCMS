<?php
/**
 * RezlyX - 캘린더 공통 데이터 로더
 *
 * 대시보드와 예약 캘린더에서 공통으로 사용.
 * 필요 변수: $pdo, $prefix, $config, $siteSettings, $adminUrl, $csrfToken, $currencySymbol, $currencyPosition
 * 선택 변수: $_calYear, $_calMonth (미지정 시 현재 연/월)
 *
 * 반환: $cal 배열 (calendar-grid/modals/js에서 사용)
 */

$_calYear  = $_calYear ?? (int)($_GET['year'] ?? date('Y'));
$_calMonth = $_calMonth ?? (int)($_GET['month'] ?? date('m'));

if ($_calMonth < 1)  { $_calMonth = 12; $_calYear--; }
if ($_calMonth > 12) { $_calMonth = 1;  $_calYear++; }

$_calFirstDay    = mktime(0, 0, 0, $_calMonth, 1, $_calYear);
$_calDaysInMonth = (int)date('t', $_calFirstDay);
$_calStartWD     = (int)date('w', $_calFirstDay);
$_calTotalWeeks  = ceil(($_calStartWD + $_calDaysInMonth) / 7);

$_calPrevYear  = $_calMonth == 1  ? $_calYear - 1  : $_calYear;
$_calPrevMonth = $_calMonth == 1  ? 12 : $_calMonth - 1;
$_calNextYear  = $_calMonth == 12 ? $_calYear + 1  : $_calYear;
$_calNextMonth = $_calMonth == 12 ? 1  : $_calMonth + 1;

$_calDateFrom = sprintf('%04d-%02d-01', $_calYear, $_calMonth);
$_calDateTo   = sprintf('%04d-%02d-%02d', $_calYear, $_calMonth, $_calDaysInMonth);

// ── 예약 데이터 조회 ──
$_calReservations = [];
try {
    $_calStmt = $pdo->prepare("SELECT r.*,
        (SELECT GROUP_CONCAT(rs.service_name ORDER BY rs.sort_order SEPARATOR ', ') FROM {$prefix}reservation_services rs WHERE rs.reservation_id = r.id) as service_name,
        (SELECT GROUP_CONCAT(rs.service_id ORDER BY rs.sort_order SEPARATOR ',') FROM {$prefix}reservation_services rs WHERE rs.reservation_id = r.id) as service_ids
        FROM {$prefix}reservations r WHERE r.reservation_date BETWEEN ? AND ? ORDER BY r.start_time ASC");
    $_calStmt->execute([$_calDateFrom, $_calDateTo]);
    $_calReservations = $_calStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    try {
        $_calStmt = $pdo->prepare("SELECT r.* FROM {$prefix}reservations r WHERE r.reservation_date BETWEEN ? AND ? ORDER BY r.start_time ASC");
        $_calStmt->execute([$_calDateFrom, $_calDateTo]);
        $_calReservations = $_calStmt->fetchAll(PDO::FETCH_ASSOC);
    } catch (PDOException $e2) {}
}

// ── 서비스명 다국어 처리 ──
$_calLocale    = $config['locale'] ?? 'ko';
$_calDefLocale = $siteSettings['default_language'] ?? 'ko';
$_calChain     = array_unique(array_filter([$_calLocale, 'en', $_calDefLocale]));
$_calSvcTr     = [];
try {
    $_calPH    = implode(',', array_fill(0, count($_calChain), '?'));
    $_calTrStmt = $pdo->prepare("SELECT lang_key, locale, content FROM {$prefix}translations WHERE locale IN ({$_calPH}) AND lang_key LIKE 'service.%.name'");
    $_calTrStmt->execute(array_values($_calChain));
    while ($_ct = $_calTrStmt->fetch(PDO::FETCH_ASSOC)) {
        $_calSvcTr[$_ct['lang_key']][$_ct['locale']] = $_ct['content'];
    }
} catch (PDOException $e) {}

foreach ($_calReservations as &$_crv) {
    if (!empty($_crv['service_ids'])) {
        $ids   = explode(',', $_crv['service_ids']);
        $names = explode(', ', $_crv['service_name'] ?? '');
        $tr    = [];
        foreach ($ids as $i => $sid) {
            $k = "service.{$sid}.name";
            $n = $names[$i] ?? '';
            if (isset($_calSvcTr[$k])) {
                foreach ($_calChain as $lc) { if (!empty($_calSvcTr[$k][$lc])) { $n = $_calSvcTr[$k][$lc]; break; } }
            }
            $tr[] = $n;
        }
        $_crv['service_name'] = implode(', ', $tr);
    }
}
unset($_crv);

// ── 날짜별 그룹핑 ──
$_calByDate = [];
foreach ($_calReservations as $_cr) {
    $_calByDate[$_cr['reservation_date']][] = $_cr;
}

// ── 서비스 목록 (예약 추가용) ──
$_calServices = [];
try {
    $_calServices = $pdo->query("SELECT s.id, s.name, s.description, s.duration, s.price, s.image, s.category_id, c.name as category_name FROM {$prefix}services s LEFT JOIN {$prefix}service_categories c ON s.category_id = c.id WHERE s.is_active = 1 ORDER BY s.sort_order ASC, s.name ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {}

// ── 번들 목록 (예약 추가용) ──
$_calBundles = [];
try {
    $_bStmt = $pdo->query("SELECT b.*, GROUP_CONCAT(bi.service_id) as svc_ids, COUNT(bi.service_id) as svc_count, SUM(sv.duration) as total_duration FROM {$prefix}service_bundles b LEFT JOIN {$prefix}service_bundle_items bi ON b.id = bi.bundle_id LEFT JOIN {$prefix}services sv ON bi.service_id = sv.id WHERE b.is_active = 1 GROUP BY b.id ORDER BY b.display_order");
    while ($_b = $_bStmt->fetch(PDO::FETCH_ASSOC)) {
        $_b['svc_id_list'] = $_b['svc_ids'] ? explode(',', $_b['svc_ids']) : [];
        $now = date('Y-m-d H:i:s');
        $_b['is_event'] = $_b['event_price'] && $_b['event_price'] > 0 && (!$_b['event_start'] || $_b['event_start'] <= $now) && (!$_b['event_end'] || $_b['event_end'] >= $now);
        $_b['display_price'] = $_b['is_event'] ? $_b['event_price'] : $_b['bundle_price'];
        if ($_b['image'] && !str_starts_with($_b['image'], 'http') && !str_starts_with($_b['image'], '/')) {
            $_b['image'] = '/' . ltrim($_b['image'], '/');
        }
        $_calBundles[] = $_b;
    }
} catch (PDOException $e) {}

// ── $cal 배열 구성 ──
$cal = [
    'year'             => $_calYear,
    'month'            => $_calMonth,
    'daysInMonth'      => $_calDaysInMonth,
    'startWeekday'     => $_calStartWD,
    'totalWeeks'       => $_calTotalWeeks,
    'byDate'           => $_calByDate,
    'reservations'     => $_calReservations,
    'adminUrl'         => $adminUrl ?? '',
    'csrfToken'        => $_SESSION['csrf_token'] ?? $csrfToken ?? '',
    'currencySymbol'   => $currencySymbol ?? '¥',
    'currencyPosition' => $currencyPosition ?? 'prefix',
    'services'         => $_calServices,
    'bundles'          => $_calBundles,
    'prevYear'         => $_calPrevYear,
    'prevMonth'        => $_calPrevMonth,
    'nextYear'         => $_calNextYear,
    'nextMonth'        => $_calNextMonth,
];
