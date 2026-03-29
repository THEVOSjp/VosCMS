<?php
/**
 * 예약 상세 - 공통 데이터 가공 모듈
 *
 * 필수 변수: $reservation, $pdo, $prefix, $currentLocale, $baseUrl, $config, $siteSettings
 * 출력 변수: $services, $staff, $bundle, $bundleServices, $displayServices,
 *           $bundlePrice, $backgroundImage, $statusClass, $statusLabel,
 *           $paymentLabel, $currencySymbol, $fmtPrice, $isCancellable, $pageTitle
 */

// ===== 헬퍼 함수 (중복 정의 방지) =====
if (!function_exists('_rdTr')) {
    /** translations 테이블 기반 번역 (캐시) */
    function _rdTr($pdo, $prefix, $langKey, $default, $locale) {
        static $cache = [];
        if (isset($cache[$langKey])) {
            $c = $cache[$langKey];
        } else {
            $stmt = $pdo->prepare("SELECT locale, content FROM {$prefix}translations WHERE lang_key = ? AND locale IN (?, 'en') LIMIT 10");
            $stmt->execute([$langKey, $locale]);
            $c = [];
            while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) $c[$r['locale']] = $r['content'];
            $cache[$langKey] = $c;
        }
        return $c[$locale] ?? $c['en'] ?? $default;
    }
}

if (!function_exists('_rdFmtDate')) {
    /** 다국어 날짜 포맷 (13개 언어) */
    function _rdFmtDate($dateStr, $locale = 'ko') {
        $ts = strtotime($dateStr);
        if (!$ts) return '';
        $y = date('Y', $ts); $m = date('m', $ts); $d = date('d', $ts); $w = date('w', $ts);
        $dMap = [
            'ko'=>['일','월','화','수','목','금','토'], 'en'=>['Sun','Mon','Tue','Wed','Thu','Fri','Sat'],
            'ja'=>['日','月','火','水','木','金','土'], 'zh_CN'=>['日','一','二','三','四','五','六'],
            'zh_TW'=>['日','一','二','三','四','五','六'], 'de'=>['So','Mo','Di','Mi','Do','Fr','Sa'],
            'es'=>['Dom','Lun','Mar','Mié','Jue','Vie','Sab'], 'fr'=>['Dim','Lun','Mar','Mer','Jeu','Ven','Sam'],
            'id'=>['Minggu','Senin','Selasa','Rabu','Kamis','Jumat','Sabtu'],
            'mn'=>['Ням','Дав','Мяг','Лха','Пүр','Баа','Ням'],
            'ru'=>['Вс','Пн','Вт','Ср','Чт','Пт','Сб'], 'tr'=>['Paz','Pzt','Sal','Çar','Per','Cum','Cmt'],
            'vi'=>['CN','T2','T3','T4','T5','T6','T7'],
        ];
        $dn = $dMap[$locale] ?? $dMap['en'];
        $mEN = ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec'];
        switch ($locale) {
            case 'ko': return $y.'년 '.(int)$m.'월 '.(int)$d.'일 ('.$dn[$w].')';
            case 'ja': case 'zh_CN': case 'zh_TW': return $y.'年'.(int)$m.'月'.(int)$d.'日 ('.$dn[$w].')';
            case 'en': return $mEN[(int)$m-1].' '.(int)$d.', '.$y.' ('.$dn[$w].')';
            case 'de': $mDE=['Jan','Feb','Mär','Apr','Mai','Jun','Jul','Aug','Sep','Okt','Nov','Dez']; return (int)$d.'. '.$mDE[(int)$m-1].' '.$y.' ('.$dn[$w].')';
            case 'es': $mES=['Ene','Feb','Mar','Abr','May','Jun','Jul','Ago','Sep','Oct','Nov','Dic']; return (int)$d.' '.$mES[(int)$m-1].' '.$y.' ('.$dn[$w].')';
            case 'fr': $mFR=['Jan','Fév','Mar','Avr','Mai','Jun','Jul','Aoû','Sep','Oct','Nov','Déc']; return (int)$d.' '.$mFR[(int)$m-1].' '.$y.' ('.$dn[$w].')';
            case 'id': $mID=['Jan','Feb','Mar','Apr','Mei','Jun','Jul','Agu','Sep','Okt','Nov','Des']; return (int)$d.' '.$mID[(int)$m-1].' '.$y.' ('.$dn[$w].')';
            case 'ru': $mRU=['Янв','Фев','Мар','Апр','Май','Июн','Июл','Авг','Сеп','Окт','Ноя','Дек']; return (int)$d.' '.$mRU[(int)$m-1].' '.$y.' ('.$dn[$w].')';
            case 'tr': $mTR=['Oca','Şub','Mar','Nis','May','Haz','Tem','Ağu','Eyl','Eki','Kas','Ara']; return (int)$d.' '.$mTR[(int)$m-1].' '.$y.' ('.$dn[$w].')';
            case 'vi': return 'Ngày '.(int)$d.' tháng '.(int)$m.' năm '.$y.' ('.$dn[$w].')';
            case 'mn': return $y.' оны '.(int)$m.' сарын '.(int)$d.' ('.$dn[$w].')';
            default: return "$y-$m-$d";
        }
    }
}

if (!function_exists('_rdFmtPhone')) {
    /** 국제전화 포맷 */
    function _rdFmtPhone($phone) {
        if (empty($phone)) return '';
        $d = preg_replace('/\D/', '', $phone);
        if (str_starts_with($d, '0')) {
            $l = substr($d, 1);
            if (preg_match('/^(10|11|16|17|18|19)(\d{4})(\d{4})$/', $l, $m)) return '+82 '.$m[1].'-'.$m[2].'-'.$m[3];
            if (preg_match('/^(2)(\d{3,4})(\d{4})$/', $l, $m)) return '+82 '.$m[1].'-'.$m[2].'-'.$m[3];
            if (preg_match('/^(\d{2})(\d{3,4})(\d{4})$/', $l, $m)) return '+82 '.$m[1].'-'.$m[2].'-'.$m[3];
            return '+82 '.$l;
        }
        if (str_starts_with($d, '82')) {
            $l = substr($d, 2);
            if (str_starts_with($l, '0')) $l = substr($l, 1); // 82 뒤의 0 제거
            if (preg_match('/^(10|11|16|17|18|19)(\d{4})(\d{4})$/', $l, $m)) return '+82 '.$m[1].'-'.$m[2].'-'.$m[3];
            if (preg_match('/^(2)(\d{3,4})(\d{4})$/', $l, $m)) return '+82 '.$m[1].'-'.$m[2].'-'.$m[3];
            if (preg_match('/^(\d{2})(\d{3,4})(\d{4})$/', $l, $m)) return '+82 '.$m[1].'-'.$m[2].'-'.$m[3];
            return '+82 '.$l;
        }
        if (str_starts_with($d, '81')) {
            $l = substr($d, 2);
            if (str_starts_with($l, '0')) $l = substr($l, 1); // 81 뒤의 0 제거
            if (preg_match('/^(\d{2,3})(\d{4})(\d{4})$/', $l, $m)) return '+81 '.$m[1].'-'.$m[2].'-'.$m[3];
            return '+81 '.$l;
        }
        return '+'.$d;
    }
}

// ===== 데이터 조회/가공 =====
$pageTitle = __('booking.detail.title') . ' - ' . $reservation['reservation_number'];

// 서비스 목록 (이미지/설명 포함)
$_rdSvcStmt = $pdo->prepare("SELECT rs.*, s.image, s.description FROM {$prefix}reservation_services rs LEFT JOIN {$prefix}services s ON rs.service_id = s.id WHERE rs.reservation_id = ? ORDER BY rs.sort_order");
$_rdSvcStmt->execute([$reservation['id']]);
$services = $_rdSvcStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($services as &$_s) {
    $_s['name'] = _rdTr($pdo, $prefix, 'service.'.$_s['service_id'].'.name', $_s['service_name'] ?? '', $currentLocale);
    $_s['description'] = _rdTr($pdo, $prefix, 'service.'.$_s['service_id'].'.description', $_s['description'] ?? '', $currentLocale);
}
unset($_s);

// 스태프
$staff = null;
if ($reservation['staff_id']) {
    $_rdStStmt = $pdo->prepare("SELECT id, name, avatar, greeting_before, greeting_after, designation_fee FROM {$prefix}staff WHERE id = ?");
    $_rdStStmt->execute([$reservation['staff_id']]);
    $staff = $_rdStStmt->fetch(PDO::FETCH_ASSOC);
    if ($staff) $staff['name'] = _rdTr($pdo, $prefix, 'staff.'.$staff['id'].'.name', $staff['name'], $currentLocale);
}

// 번들
$bundle = null;
$bundleServices = [];
$_rdBid = $reservation['bundle_id'] ?? null;
if (!$_rdBid && !empty($services)) $_rdBid = $services[0]['bundle_id'] ?? null;
if ($_rdBid) {
    try {
        $_rdBStmt = $pdo->prepare("SELECT id, name, bundle_price as price, description, image FROM {$prefix}service_bundles WHERE id = ?");
        $_rdBStmt->execute([$_rdBid]);
        $bundle = $_rdBStmt->fetch(PDO::FETCH_ASSOC);
        if ($bundle) {
            $_rdBSvcStmt = $pdo->prepare("SELECT sbi.service_id, s.id, s.name, s.description, s.duration, s.price, s.image FROM {$prefix}service_bundle_items sbi LEFT JOIN {$prefix}services s ON sbi.service_id = s.id WHERE sbi.bundle_id = ? ORDER BY sbi.sort_order");
            $_rdBSvcStmt->execute([$_rdBid]);
            $bundleServices = $_rdBSvcStmt->fetchAll(PDO::FETCH_ASSOC);
            $bundle['name'] = _rdTr($pdo, $prefix, 'bundle.'.$bundle['id'].'.name', $bundle['name'], $currentLocale);
            $bundle['description'] = _rdTr($pdo, $prefix, 'bundle.'.$bundle['id'].'.description', $bundle['description'] ?? '', $currentLocale);
            foreach ($bundleServices as &$_s) {
                $_s['name'] = _rdTr($pdo, $prefix, 'service.'.$_s['service_id'].'.name', $_s['name'] ?? '', $currentLocale);
                $_s['description'] = _rdTr($pdo, $prefix, 'service.'.$_s['service_id'].'.description', $_s['description'] ?? '', $currentLocale);
            }
            unset($_s);
        }
    } catch (PDOException $e) {}
}

// 공통 변수
$displayServices = ($bundle && !empty($bundleServices)) ? $bundleServices : $services;
$bundlePrice = $reservation['bundle_price'] ?? ($bundle['price'] ?? null);
$backgroundImage = null;
if ($bundle && $bundle['image']) $backgroundImage = $baseUrl.'/'.$bundle['image'];
elseif (!empty($services) && ($services[0]['image'] ?? '')) $backgroundImage = $baseUrl.'/'.$services[0]['image'];

$statusColors = [
    'pending'=>'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    'confirmed'=>'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    'completed'=>'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    'cancelled'=>'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    'no_show'=>'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-400',
];
$statusClass = $statusColors[$reservation['status']] ?? $statusColors['pending'];
$statusLabel = __('common.status.'.$reservation['status']);
$paymentLabel = __('booking.payment.'.($reservation['payment_status'] ?? 'unpaid'));

$currency = $siteSettings['service_currency'] ?? $config['currency'] ?? 'KRW';
$currencySymbol = ['KRW'=>'₩','JPY'=>'¥','USD'=>'$','EUR'=>'€','CNY'=>'¥'][$currency] ?? $currency;
$fmtPrice = function($a) use ($currencySymbol) { return $currencySymbol . number_format((float)$a); };

$isCancellable = in_array($reservation['status'], ['pending', 'confirmed']);

// 번들 표시명 다국어
$bundleDisplayName = $siteSettings['bundle_display_name'] ?? (__('booking.detail.bundle') ?? '쿠폰');
$_bdnTr = _rdTr($pdo, $prefix, 'bundle_display_name', '', $currentLocale);
if ($_bdnTr) $bundleDisplayName = $_bdnTr;

// 인쇄용 CSS
$_printCss = '<style>@media print { nav, footer, header, .no-print, button, a[href] { display: none !important; } body { background: white !important; } .dark\:bg-zinc-800, .dark\:bg-zinc-700 { background: white !important; } .dark\:text-white, .dark\:text-zinc-100, .dark\:text-zinc-200, .dark\:text-zinc-300 { color: #1f2937 !important; } .shadow-sm, .shadow-lg { box-shadow: none !important; } .border { border-color: #e5e7eb !important; } @page { margin: 15mm; } }</style>';
echo $_printCss;
