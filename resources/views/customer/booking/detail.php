<?php
/**
 * RezlyX - 예약 상세 페이지 (고객용)
 */
require_once BASE_PATH . '/rzxlib/Reservation/Models/Reservation.php';
require_once BASE_PATH . '/rzxlib/Core/Helpers/Encryption.php';
use RzxLib\Reservation\Models\Reservation;
use RzxLib\Core\Helpers\Encryption;

// 다국어 지원: Translator 초기화
require_once BASE_PATH . '/rzxlib/Core/I18n/Translator.php';
\RzxLib\Core\I18n\Translator::init(BASE_PATH . '/resources/lang');
if (!isset($currentLocale)) $currentLocale = current_locale();
if (!isset($baseUrl)) $baseUrl = rtrim($config['app_url'] ?? '', '/');
if (!isset($siteSettings)) $siteSettings = [];

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

// ===== 다국어 헬퍼 함수 =====
function getLocalizedVal($name, $nameI18n, $locale) {
    if (!empty($nameI18n)) {
        $i18n = is_string($nameI18n) ? json_decode($nameI18n, true) : $nameI18n;
        if (is_array($i18n) && !empty($i18n[$locale])) return $i18n[$locale];
    }
    return $name;
}

// translations 테이블 기반 번역 (캐시 포함)
$_trCache = [];
function _tr($pdo, $prefix, $langKey, $default, $locale) {
    global $_trCache;
    if (isset($_trCache[$langKey])) {
        $cached = $_trCache[$langKey];
    } else {
        $stmt = $pdo->prepare("SELECT locale, content FROM {$prefix}translations WHERE lang_key = ? AND locale IN (?, 'en') LIMIT 10");
        $stmt->execute([$langKey, $locale]);
        $cached = [];
        while ($r = $stmt->fetch(PDO::FETCH_ASSOC)) $cached[$r['locale']] = $r['content'];
        $_trCache[$langKey] = $cached;
    }
    return $cached[$locale] ?? $cached['en'] ?? $default;
}

// 다국어 날짜 포맷팅 함수
function formatReservationDate($dateString, $locale = 'ko') {
    $timestamp = strtotime($dateString);
    if (!$timestamp) return '';

    $year = date('Y', $timestamp);
    $month = date('m', $timestamp);
    $day = date('d', $timestamp);
    $dayOfWeek = date('w', $timestamp);

    $dayNamesMap = [
        'ko' => ['일', '월', '화', '수', '목', '금', '토'],
        'en' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
        'ja' => ['日', '月', '火', '水', '木', '金', '土'],
        'zh_CN' => ['日', '一', '二', '三', '四', '五', '六'],
        'zh_TW' => ['日', '一', '二', '三', '四', '五', '六'],
        'de' => ['So', 'Mo', 'Di', 'Mi', 'Do', 'Fr', 'Sa'],
        'es' => ['Dom', 'Lun', 'Mar', 'Mié', 'Jue', 'Vie', 'Sab'],
        'fr' => ['Dim', 'Lun', 'Mar', 'Mer', 'Jeu', 'Ven', 'Sam'],
        'id' => ['Minggu', 'Senin', 'Selasa', 'Rabu', 'Kamis', 'Jumat', 'Sabtu'],
        'mn' => ['Ням', 'Дав', 'Мяг', 'Лха', 'Пүр', 'Баа', 'Ням'],
        'ru' => ['Вс', 'Пн', 'Вт', 'Ср', 'Чт', 'Пт', 'Сб'],
        'tr' => ['Paz', 'Pzt', 'Sal', 'Çar', 'Per', 'Cum', 'Cmt'],
        'vi' => ['CN', 'T2', 'T3', 'T4', 'T5', 'T6', 'T7'],
    ];

    $dayNames = $dayNamesMap[$locale] ?? $dayNamesMap['en'];

    switch ($locale) {
        case 'ko':
            return $year . '년 ' . (int)$month . '월 ' . (int)$day . '일 (' . $dayNames[$dayOfWeek] . ')';
        case 'ja':
            return $year . '年' . (int)$month . '月' . (int)$day . '日 (' . $dayNames[$dayOfWeek] . ')';
        case 'en':
            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
            return $months[(int)$month - 1] . ' ' . (int)$day . ', ' . $year . ' (' . $dayNames[$dayOfWeek] . ')';
        case 'zh_CN':
        case 'zh_TW':
            return $year . '年' . (int)$month . '月' . (int)$day . '日 (' . $dayNames[$dayOfWeek] . ')';
        case 'de':
            $months = ['Jan', 'Feb', 'Mär', 'Apr', 'Mai', 'Jun', 'Jul', 'Aug', 'Sep', 'Okt', 'Nov', 'Dez'];
            return (int)$day . '. ' . $months[(int)$month - 1] . ' ' . $year . ' (' . $dayNames[$dayOfWeek] . ')';
        case 'es':
            $months = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
            return (int)$day . ' ' . $months[(int)$month - 1] . ' ' . $year . ' (' . $dayNames[$dayOfWeek] . ')';
        case 'fr':
            $months = ['Jan', 'Fév', 'Mar', 'Avr', 'Mai', 'Jun', 'Jul', 'Aoû', 'Sep', 'Oct', 'Nov', 'Déc'];
            return (int)$day . ' ' . $months[(int)$month - 1] . ' ' . $year . ' (' . $dayNames[$dayOfWeek] . ')';
        case 'id':
            $months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            return (int)$day . ' ' . $months[(int)$month - 1] . ' ' . $year . ' (' . $dayNames[$dayOfWeek] . ')';
        case 'ru':
            $months = ['Янв', 'Фев', 'Мар', 'Апр', 'Май', 'Июн', 'Июл', 'Авг', 'Сеп', 'Окт', 'Ноя', 'Дек'];
            return (int)$day . ' ' . $months[(int)$month - 1] . ' ' . $year . ' (' . $dayNames[$dayOfWeek] . ')';
        case 'tr':
            $months = ['Oca', 'Şub', 'Mar', 'Nis', 'May', 'Haz', 'Tem', 'Ağu', 'Eyl', 'Eki', 'Kas', 'Ara'];
            return (int)$day . ' ' . $months[(int)$month - 1] . ' ' . $year . ' (' . $dayNames[$dayOfWeek] . ')';
        case 'vi':
            return 'Ngày ' . (int)$day . ' tháng ' . (int)$month . ' năm ' . $year . ' (' . $dayNames[$dayOfWeek] . ')';
        case 'mn':
            $months = ['1', '2', '3', '4', '5', '6', '7', '8', '9', '10', '11', '12'];
            return $year . ' оны ' . (int)$month . ' сарын ' . (int)$day . ' (' . $dayNames[$dayOfWeek] . ')';
        default:
            return $year . '-' . $month . '-' . $day;
    }
}

// 예약 조회
if (empty($reservationNumber)) {
    http_response_code(404);
    echo '<div class="max-w-2xl mx-auto px-4 py-12 text-center"><p class="text-zinc-500">' . __('booking.lookup.not_found') . '</p></div>';
    return;
}

$stmt = $pdo->prepare("SELECT * FROM {$prefix}reservations WHERE reservation_number = ?");
$stmt->execute([$reservationNumber]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reservation) {
    http_response_code(404);
    echo '<div class="max-w-2xl mx-auto px-4 py-12 text-center"><p class="text-zinc-500">' . __('booking.lookup.not_found') . '</p></div>';
    return;
}

$pageTitle = __('booking.detail.title') . ' - ' . $reservationNumber;

// 서비스 목록 (상세 정보 포함)
$svcStmt = $pdo->prepare("
    SELECT rs.*, s.image, s.description
    FROM {$prefix}reservation_services rs
    LEFT JOIN {$prefix}services s ON rs.service_id = s.id
    WHERE rs.reservation_id = ?
    ORDER BY rs.sort_order
");
$svcStmt->execute([$reservation['id']]);
$services = $svcStmt->fetchAll(PDO::FETCH_ASSOC);

// 서비스 다국어 처리 (_tr() 사용)
foreach ($services as &$_svc) {
    $_svc['name'] = _tr($pdo, $prefix, 'service.' . $_svc['service_id'] . '.name', $_svc['name'] ?? '', $currentLocale);
    $_svc['description'] = _tr($pdo, $prefix, 'service.' . $_svc['service_id'] . '.description', $_svc['description'] ?? '', $currentLocale);
}
unset($_svc);

// 스태프 정보
$staff = null;
if ($reservation['staff_id']) {
    $staffStmt = $pdo->prepare("SELECT id, name, avatar, greeting_before, greeting_after, designation_fee FROM {$prefix}staff WHERE id = ?");
    $staffStmt->execute([$reservation['staff_id']]);
    $staff = $staffStmt->fetch(PDO::FETCH_ASSOC);

    // 스태프명 다국어 처리 (_tr() 사용)
    if ($staff) {
        $staff['name'] = _tr($pdo, $prefix, 'staff.' . $staff['id'] . '.name', $staff['name'], $currentLocale);
    }
}

// 번들 정보 (첫 번째 서비스의 bundle_id 사용)
$bundle = null;
$bundleServices = [];
if (!empty($services)) {
    $bundleId = $services[0]['bundle_id'] ?? null;
    if ($bundleId) {
        try {
            $bundleStmt = $pdo->prepare("SELECT id, name, bundle_price as price, description, image FROM {$prefix}service_bundles WHERE id = ?");
            $bundleStmt->execute([$bundleId]);
            $bundle = $bundleStmt->fetch(PDO::FETCH_ASSOC);

            // 번들에 포함된 서비스 조회 (상세 정보 포함)
            if ($bundle) {
                $bundleSvcStmt = $pdo->prepare("SELECT sbi.service_id, s.id, s.name, s.description, s.duration, s.price, s.image FROM {$prefix}service_bundle_items sbi LEFT JOIN {$prefix}services s ON sbi.service_id = s.id WHERE sbi.bundle_id = ? ORDER BY sbi.sort_order");
                $bundleSvcStmt->execute([$bundleId]);
                $bundleServices = $bundleSvcStmt->fetchAll(PDO::FETCH_ASSOC);

                // 번들 다국어 처리 (_tr() 사용)
                $bundle['name'] = _tr($pdo, $prefix, 'bundle.' . $bundle['id'] . '.name', $bundle['name'], $currentLocale);
                $bundle['description'] = _tr($pdo, $prefix, 'bundle.' . $bundle['id'] . '.description', $bundle['description'] ?? '', $currentLocale);

                // 번들 서비스 다국어 처리
                foreach ($bundleServices as &$_svc) {
                    $_svc['name'] = _tr($pdo, $prefix, 'service.' . $_svc['service_id'] . '.name', $_svc['name'] ?? '', $currentLocale);
                    $_svc['description'] = _tr($pdo, $prefix, 'service.' . $_svc['service_id'] . '.description', $_svc['description'] ?? '', $currentLocale);
                }
                unset($_svc);
            }
        } catch (PDOException $e) {
            // 번들 테이블이 없는 경우 무시
        }
    }
}

// 상태 배지
$statusColors = [
    'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    'confirmed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    'no_show' => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-400',
];
$statusClass = $statusColors[$reservation['status']] ?? $statusColors['pending'];
$statusLabel = __('common.status.' . $reservation['status']);

// 결제 상태 (다국어 처리)
$paymentLabel = __('booking.payment.' . ($reservation['payment_status'] ?? 'unpaid'));

// 통화 포맷
$currency = $siteSettings['service_currency'] ?? $config['currency'] ?? 'KRW';
$currencySymbol = ['KRW' => '₩', 'JPY' => '¥', 'USD' => '$'][$currency] ?? $currency;
function fmtPrice($amount, $symbol) { return $symbol . number_format((float)$amount); }

// 취소 가능 여부
$isCancellable = in_array($reservation['status'], ['pending', 'confirmed']);

// 고객 정보 (user_id가 있는 경우)
$customer = null;
if ($reservation['user_id']) {
    $userStmt = $pdo->prepare("SELECT id, name, avatar FROM {$prefix}users WHERE id = ?");
    $userStmt->execute([$reservation['user_id']]);
    $customer = $userStmt->fetch(PDO::FETCH_ASSOC);
}

// 배경 이미지 (번들 또는 첫 번째 서비스)
$backgroundImage = null;
if ($bundle && $bundle['image']) {
    $backgroundImage = $baseUrl . '/' . $bundle['image'];
} elseif (!empty($services) && $services[0]['image']) {
    $backgroundImage = $baseUrl . '/' . $services[0]['image'];
}

// 국제 전화번호 포맷팅
function formatPhoneNumber($phone) {
    if (empty($phone)) return '';
    // 숫자만 추출
    $digits = preg_replace('/\D/', '', $phone);
    // 한국 번호 (010, 02, 031 등)
    if (str_starts_with($digits, '0')) {
        $digits = '82' . substr($digits, 1);
    }
    // E.164 포맷: +{country_code}{number}
    return '+' . $digits;
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <div class="flex flex-col lg:flex-row gap-6 lg:gap-8">
        <!-- 좌측: 메인 콘텐츠 -->
        <div class="flex-1">

    <!-- 헤더 -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-zinc-800 dark:text-zinc-100"><?= __('booking.detail.title') ?></h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1 font-mono"><?= htmlspecialchars($reservationNumber) ?></p>
        </div>
        <span class="px-3 py-1 text-sm font-medium rounded-full <?= $statusClass ?>"><?= $statusLabel ?></span>
    </div>

    <!-- 고객 정보 (상단) - 배경 이미지 포함 -->
    <div class="relative rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden mb-6 bg-white dark:bg-zinc-800">
        <!-- 배경 이미지 -->
        <?php if ($backgroundImage): ?>
        <div class="absolute inset-0 opacity-15 dark:opacity-10 bg-cover bg-center" style="background-image: url('<?= htmlspecialchars($backgroundImage) ?>')"></div>
        <?php else: ?>
        <div class="absolute inset-0 opacity-10 bg-gradient-to-r from-blue-500 to-purple-500"></div>
        <?php endif; ?>

        <!-- 콘텐츠 -->
        <div class="relative p-6 z-10">
            <div class="flex gap-6 items-start">
                <!-- 아바타 (왼쪽) -->
                <div class="shrink-0">
                    <?php if ($customer && !empty($customer['avatar'])): ?>
                    <img src="<?= htmlspecialchars($baseUrl . '/' . ltrim($customer['avatar'], '/')) ?>" alt="<?= htmlspecialchars($customer['name']) ?>" class="w-20 h-20 rounded-full object-cover border-4 border-white dark:border-zinc-700 shadow-lg">
                    <?php else: ?>
                    <div class="w-20 h-20 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white font-bold text-2xl border-4 border-white dark:border-zinc-700 shadow-lg">
                        <?= mb_substr($reservation['customer_name'], 0, 1) ?>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- 정보 (오른쪽) -->
                <div class="flex-1">
                    <!-- 예약자명 (H1) -->
                    <h1 class="text-3xl font-bold text-zinc-900 dark:text-white mb-4"><?= htmlspecialchars($reservation['customer_name']) ?></h1>

                    <!-- 이메일과 연락처 (가로 배치) -->
                    <div class="flex flex-col sm:flex-row gap-4 text-sm">
                        <?php if ($reservation['customer_email']): ?>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-zinc-400 dark:text-zinc-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"></path></svg>
                            <span class="text-zinc-600 dark:text-zinc-300 break-all"><?= htmlspecialchars($reservation['customer_email']) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-zinc-400 dark:text-zinc-500 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"></path></svg>
                            <span class="text-zinc-600 dark:text-zinc-300 font-mono"><?= formatPhoneNumber($reservation['customer_phone']) ?></span>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- 예약 정보 카드 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden mb-6">

        <!-- 날짜/시간 + 스태프 -->
        <div class="p-6 border-b border-zinc-100 dark:border-zinc-700">
            <div class="flex items-start justify-between gap-4">
                <!-- 날짜/시간 -->
                <div class="flex items-center gap-4">
                    <div class="w-14 h-14 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex flex-col items-center justify-center shrink-0">
                        <span class="text-xs text-blue-600 dark:text-blue-400 font-medium"><?= date('M', strtotime($reservation['reservation_date'])) ?></span>
                        <span class="text-lg font-bold text-blue-600 dark:text-blue-400 -mt-1"><?= date('d', strtotime($reservation['reservation_date'])) ?></span>
                    </div>
                    <div>
                        <p class="text-lg font-semibold text-zinc-800 dark:text-zinc-100">
                            <?= formatReservationDate($reservation['reservation_date'], $currentLocale) ?>
                        </p>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400">
                            <?= date('H:i', strtotime($reservation['start_time'])) ?> ~ <?= date('H:i', strtotime($reservation['end_time'])) ?>
                        </p>
                    </div>
                </div>

                <!-- 스태프 프로필 -->
                <?php if ($staff): ?>
                <div class="flex items-start gap-4">
                    <!-- 스태프 아바타 (왼쪽) -->
                    <div class="shrink-0">
                        <?php if (!empty($staff['avatar'])): ?>
                        <img src="<?= htmlspecialchars($baseUrl . '/' . ltrim($staff['avatar'], '/')) ?>" alt="<?= htmlspecialchars($staff['name']) ?>" class="w-20 h-20 rounded-full object-cover border-4 border-white dark:border-zinc-700 shadow-lg">
                        <?php else: ?>
                        <div class="w-20 h-20 rounded-full bg-purple-500 dark:bg-purple-600 flex items-center justify-center text-white font-bold text-3xl border-4 border-white dark:border-zinc-700 shadow-lg"><?= mb_substr($staff['name'], 0, 1) ?></div>
                        <?php endif; ?>
                    </div>

                    <!-- 스태프 정보 (오른쪽) -->
                    <div class="flex-1">
                        <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1"><?= __('booking.detail.staff') ?></p>
                        <p class="text-lg font-semibold text-zinc-800 dark:text-zinc-100 mb-2"><?= htmlspecialchars(db_trans('staff.name.' . $staff['id'], $currentLocale, $staff['name'])) ?></p>
                        <?php if (($reservation['designation_fee'] ?? 0) > 0): ?>
                        <p class="text-sm font-medium text-amber-600 dark:text-amber-400"><?= __('booking.detail.designation_fee') ?> <?= fmtPrice($reservation['designation_fee'], $currencySymbol) ?></p>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 번들 정보 (있는 경우) -->
        <?php if ($bundle): ?>
        <div class="px-6 py-4 bg-amber-50 dark:bg-amber-900/20 border-b border-zinc-100 dark:border-zinc-700">
            <div class="flex items-start gap-4">
                <?php if ($bundle['image']): ?>
                <img src="<?= htmlspecialchars($baseUrl . '/' . $bundle['image']) ?>" alt="<?= htmlspecialchars($bundle['name']) ?>" class="w-20 h-20 rounded-lg object-cover shrink-0">
                <?php endif; ?>
                <div class="flex-1">
                    <p class="text-xs font-medium text-amber-600 dark:text-amber-400 mb-1"><?= htmlspecialchars($siteSettings['bundle_display_name'] ?? __('booking.detail.bundle')) ?></p>
                    <p class="font-semibold text-zinc-800 dark:text-zinc-100"><?= htmlspecialchars(db_trans('service_bundle.name.' . $bundle['id'], $currentLocale, $bundle['name'])) ?></p>
                    <?php if ($bundle['description']): ?>
                    <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1 line-clamp-2"><?= htmlspecialchars(db_trans('service_bundle.description.' . $bundle['id'], $currentLocale, $bundle['description'])) ?></p>
                    <?php endif; ?>
                    <?php if (isset($bundle['price']) && $bundle['price'] > 0): ?>
                    <p class="text-sm font-semibold text-amber-600 dark:text-amber-400 mt-2"><?= fmtPrice($bundle['price'], $currencySymbol) ?></p>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- 서비스 목록 -->
        <?php if ($bundle && !empty($bundleServices)): ?>
        <div class="px-6 py-4">
            <h3 class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-3"><?= __('booking.detail.services') ?></h3>
            <div class="space-y-3">
                <?php foreach ($bundleServices as $svc): ?>
                <div class="flex gap-4 p-3 rounded-lg bg-zinc-50 dark:bg-zinc-700/30 border border-zinc-200 dark:border-zinc-600">
                    <?php if ($svc['image']): ?>
                    <img src="<?= htmlspecialchars($baseUrl . '/' . $svc['image']) ?>" alt="<?= htmlspecialchars($svc['name']) ?>" class="w-16 h-16 rounded-lg object-cover shrink-0">
                    <?php else: ?>
                    <div class="w-16 h-16 rounded-lg bg-zinc-300 dark:bg-zinc-600 flex items-center justify-center shrink-0">
                        <svg class="w-8 h-8 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    </div>
                    <?php endif; ?>
                    <div class="flex-1">
                        <p class="font-medium text-zinc-800 dark:text-zinc-100"><?= htmlspecialchars(db_trans('service.name.' . $svc['id'], $currentLocale, $svc['name'] ?: '')) ?: __('booking.detail.service') ?></p>
                        <?php if ($svc['description']): ?>
                        <p class="text-xs text-zinc-600 dark:text-zinc-400 mt-1 line-clamp-2"><?= htmlspecialchars(db_trans('service.description.' . $svc['id'], $currentLocale, $svc['description'])) ?></p>
                        <?php endif; ?>
                        <div class="flex items-center justify-between mt-2 text-xs">
                            <span class="text-zinc-500 dark:text-zinc-400"><?= (int)($svc['duration'] ?? 0) ?><?= __('booking.detail.duration_unit') ?></span>
                            <span class="font-semibold text-zinc-700 dark:text-zinc-300"><?= fmtPrice($svc['price'] ?? 0, $currencySymbol) ?></span>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

        <!-- 취소 정보 -->
        <?php if ($reservation['status'] === 'cancelled'): ?>
        <div class="bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-800 p-6 mb-6">
            <h3 class="text-sm font-medium text-red-700 dark:text-red-300 mb-2"><?= __('booking.detail.cancel_info') ?></h3>
            <?php if ($reservation['cancelled_at']): ?>
            <p class="text-sm text-red-600 dark:text-red-400"><?= __('booking.detail.cancelled_at') ?>: <?= date('Y-m-d H:i', strtotime($reservation['cancelled_at'])) ?></p>
            <?php endif; ?>
            <?php if ($reservation['cancel_reason']): ?>
            <p class="text-sm text-red-600 dark:text-red-400 mt-1"><?= __('booking.detail.cancel_reason') ?>: <?= htmlspecialchars($reservation['cancel_reason']) ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <!-- 버튼 -->
        <div class="flex items-center gap-2 flex-wrap">
            <a href="<?= $baseUrl ?>/lookup" class="px-5 py-2.5 text-sm font-medium text-zinc-600 dark:text-zinc-300 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">
                <?= __('booking.detail.back_to_lookup') ?>
            </a>
            <?php if ($isCancellable): ?>
            <a href="<?= $baseUrl ?>/booking/cancel/<?= urlencode($reservationNumber) ?>" class="px-5 py-2.5 text-sm font-medium text-red-600 dark:text-red-400 bg-white dark:bg-zinc-800 border border-red-300 dark:border-red-600 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                <?= __('booking.cancel.title') ?>
            </a>
            <?php endif; ?>
        </div>

        </div>

        <!-- 우측: 결제 정보 사이드바 -->
        <div class="w-full lg:w-80 shrink-0">
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6 sticky top-6">
                <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-100 mb-4"><?= __('booking.detail.payment') ?></h3>

                <!-- 포함 서비스 목록 -->
                <div class="mb-4 pb-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h4 class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-2"><?= __('booking.detail.services') ?></h4>
                    <div class="space-y-2">
                        <?php
                        $servicesList = $bundle && !empty($bundleServices) ? $bundleServices : $services;
                        $serviceTotal = 0;
                        foreach ($servicesList as $svc):
                            $serviceTotal += (float)($svc['price'] ?? 0);
                        ?>
                        <div class="flex items-start justify-between gap-2 text-xs">
                            <span class="text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars(db_trans('service.name.' . $svc['id'], $currentLocale, $svc['name'] ?? $svc['service_name'] ?? '') ?: __('booking.detail.service')) ?></span>
                            <span class="text-zinc-800 dark:text-zinc-200 font-medium shrink-0"><?= fmtPrice($svc['price'], $currencySymbol) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- 금액 계산 -->
                <div class="space-y-2 text-sm mb-4 pb-4 border-b border-zinc-200 dark:border-zinc-700">
                    <div class="flex justify-between font-semibold">
                        <span class="text-zinc-600 dark:text-zinc-400"><?= __('booking.detail.total') ?></span>
                        <span class="text-zinc-800 dark:text-zinc-200"><?= fmtPrice($serviceTotal, $currencySymbol) ?></span>
                    </div>

                    <!-- 번들 정보 (번들이 있는 경우) -->
                    <?php if ($bundle): ?>
                    <div class="flex justify-between">
                        <span class="text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($siteSettings['bundle_display_name'] ?? __('booking.detail.bundle')) ?> - <?= htmlspecialchars(db_trans('service_bundle.name.' . $bundle['id'], $currentLocale, $bundle['name'])) ?></span>
                        <span class="text-zinc-800 dark:text-zinc-200 font-medium"><?= fmtPrice($bundle['price'] ?? 0, $currencySymbol) ?></span>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- 지명비 및 추가 항목 -->
                <div class="space-y-2 text-sm">
                    <?php if (($reservation['designation_fee'] ?? 0) > 0): ?>
                    <div class="flex justify-between text-amber-600 dark:text-amber-400">
                        <span><?= __('booking.detail.designation_fee') ?></span>
                        <span class="font-medium">+<?= fmtPrice($reservation['designation_fee'], $currencySymbol) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (($reservation['discount_amount'] ?? 0) > 0): ?>
                    <div class="flex justify-between text-green-600 dark:text-green-400">
                        <span><?= __('booking.detail.discount') ?></span>
                        <span class="font-medium">-<?= fmtPrice($reservation['discount_amount'], $currencySymbol) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (($reservation['points_used'] ?? 0) > 0): ?>
                    <div class="flex justify-between text-green-600 dark:text-green-400">
                        <span><?= __('booking.detail.points_used') ?></span>
                        <span class="font-medium">-<?= fmtPrice($reservation['points_used'], $currencySymbol) ?></span>
                    </div>
                    <?php endif; ?>

                    <!-- 최종 금액 -->
                    <div class="flex justify-between pt-2 border-t border-zinc-200 dark:border-zinc-700 font-semibold mt-3">
                        <span class="text-zinc-800 dark:text-zinc-100"><?= __('booking.detail.final_amount') ?></span>
                        <span class="text-blue-600 dark:text-blue-400 text-lg"><?= fmtPrice($reservation['final_amount'], $currencySymbol) ?></span>
                    </div>

                    <!-- 결제 상태 -->
                    <div class="flex justify-between pt-2 text-sm">
                        <span class="text-zinc-500 dark:text-zinc-400"><?= __('booking.detail.payment_status') ?></span>
                        <span class="text-zinc-700 dark:text-zinc-300"><?= $paymentLabel ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
