<?php
/**
 * RezlyX 키오스크 - 접수 확인 & 발행 화면
 * 서비스 확인 → 고객 정보 입력 → 접수 발행
 */
include __DIR__ . '/_init.php';

// 하단 문구
$kioskFooterText = $ks['kiosk_footer_text'] ?? '';
$footerFromDb = kioskTranslation($pdo, $prefix, 'kiosk.footer_text', $currentLocale);
$footerText = $footerFromDb ?: ($kioskFooterText ?: 'Powered by ' . $siteName);

// 파라미터
$lang = $_GET['lang'] ?? $currentLocale;
$type = $_GET['type'] ?? 'designation';
$staffId = $_GET['staff'] ?? '';
$serviceIdsRaw = $_GET['services'] ?? '';
$serviceIds = array_filter(explode(',', $serviceIdsRaw));

// 통화 설정
$serviceCurrency = $siteSettings['service_currency'] ?? 'KRW';
$currencySymbols = [
    'KRW' => '₩', 'USD' => '$', 'JPY' => '¥', 'EUR' => '€',
    'CNY' => '¥', 'GBP' => '£', 'THB' => '฿', 'VND' => '₫',
    'MNT' => '₮', 'RUB' => '₽', 'TRY' => '₺', 'IDR' => 'Rp',
];
$currencySymbol = $currencySymbols[$serviceCurrency] ?? $serviceCurrency;
$currencyPosition = $siteSettings['service_currency_position'] ?? 'prefix';

function kioskFmtPrice(float $amount, string $symbol, string $position): string {
    $formatted = number_format($amount);
    return $position === 'suffix' ? $formatted . $symbol : $symbol . $formatted;
}

// 서비스 조회
$services = [];
$totalAmount = 0;
$totalDuration = 0;
if (!empty($serviceIds)) {
    $ph = implode(',', array_fill(0, count($serviceIds), '?'));
    $st = $pdo->prepare("SELECT id, name, price, duration FROM {$prefix}services WHERE id IN ({$ph})");
    $st->execute(array_values($serviceIds));
    $services = $st->fetchAll(PDO::FETCH_ASSOC);
    foreach ($services as $s) {
        $totalAmount += (float)$s['price'];
        $totalDuration += (int)$s['duration'];
    }
}

// 다국어 서비스명
$translations = [];
if (!empty($serviceIds)) {
    $trKeys = [];
    foreach ($serviceIds as $sid) { $trKeys[] = 'service.' . $sid . '.name'; }
    $trPh = implode(',', array_fill(0, count($trKeys), '?'));
    $trSt = $pdo->prepare("SELECT lang_key, content FROM {$prefix}translations WHERE lang_key IN ({$trPh}) AND locale = ?");
    $trSt->execute([...$trKeys, $currentLocale]);
    foreach ($trSt->fetchAll(PDO::FETCH_ASSOC) as $tr) { $translations[$tr['lang_key']] = $tr['content']; }
}

// 스태프 정보
$staffName = '';
$designationFee = 0;

if ($type === 'assignment') {
    // POS 설정에서 자동 배정 여부 확인
    $_aaStmt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = 'pos_auto_assign'");
    $_aaStmt->execute();
    $autoAssignEnabled = ($_aaStmt->fetchColumn() ?: '0') === '1';
    if ($autoAssignEnabled && !empty($serviceIds)) {
        // 자동 배정: 서비스 수행 가능한 스태프 중 당일 예약 최소
        $today = date('Y-m-d');
        $svcPh = implode(',', array_fill(0, count($serviceIds), '?'));
        try {
            $candidateStmt = $pdo->prepare("
                SELECT DISTINCT s.id, s.name FROM {$prefix}staff s
                INNER JOIN {$prefix}staff_services ss ON s.id = ss.staff_id
                WHERE s.is_active = 1 AND (s.is_visible = 1 OR s.is_visible IS NULL) AND ss.service_id IN ({$svcPh})
                GROUP BY s.id HAVING COUNT(DISTINCT ss.service_id) = ?
            ");
            $candidateStmt->execute([...array_values($serviceIds), count($serviceIds)]);
            $candidates = $candidateStmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($candidates)) {
                $bestStaff = null; $minCount = PHP_INT_MAX;
                foreach ($candidates as $c) {
                    $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}reservations WHERE staff_id = ? AND reservation_date = ? AND status NOT IN ('cancelled')");
                    $cntStmt->execute([$c['id'], $today]);
                    $cnt = (int)$cntStmt->fetchColumn();
                    if ($cnt < $minCount) { $minCount = $cnt; $bestStaff = $c; }
                }
                if ($bestStaff) { $staffId = $bestStaff['id']; }
            }
        } catch (Exception $e) { /* 자동 배정 실패 시 미배정으로 진행 */ }
    } else {
        $staffId = null;
    }
    $staffName = '';
    $designationFee = 0;
} elseif ($type === 'designation' && $staffId) {
    // 지명: 선택된 스태프 정보 로드
    $stStaff = $pdo->prepare("SELECT name, name_i18n, designation_fee FROM {$prefix}staff WHERE id = ?");
    $stStaff->execute([$staffId]);
    $staffRow = $stStaff->fetch(PDO::FETCH_ASSOC);
    if ($staffRow) {
        if ($staffRow['name_i18n']) {
            $i18n = json_decode($staffRow['name_i18n'], true);
            $staffName = $i18n[$currentLocale] ?? $staffRow['name'];
        } else {
            $staffName = $staffRow['name'];
        }
        $designationFee = (float)$staffRow['designation_fee'];
    }
}

$finalAmount = $totalAmount + $designationFee;

// ─── POST: 접수 발행 ───
$submitted = false;
$resultNumber = '';
$resultWaiting = 0;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'checkin') {
    $customerName = trim($_POST['customer_name'] ?? '') ?: __('reservations.pos_walk_in');
    $customerPhone = trim($_POST['customer_phone'] ?? '');
    $now = new DateTime();
    $reservationDate = $now->format('Y-m-d');
    $startTime = $now->format('H:i:s');
    $endMinutes = (int)$now->format('H') * 60 + (int)$now->format('i') + $totalDuration;
    $endTime = sprintf('%02d:%02d:00', floor($endMinutes / 60) % 24, $endMinutes % 60);

    $source = ($type === 'designation') ? 'designation' : 'walk_in';

    // 회원 매칭 (전화번호)
    $matchedUserId = null;
    if ($customerPhone) {
        $uSt = $pdo->prepare("SELECT id FROM {$prefix}users WHERE phone = ? LIMIT 1");
        $uSt->execute([$customerPhone]);
        $matchedUserId = $uSt->fetchColumn() ?: null;
    }

    $pdo->beginTransaction();
    try {
        $id = bin2hex(random_bytes(18));
        $reservationNumber = 'RZX' . date('YmdHis') . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

        $insertStmt = $pdo->prepare("INSERT INTO {$prefix}reservations
            (id, reservation_number, user_id, staff_id, designation_fee, customer_name, customer_phone,
             reservation_date, start_time, end_time, total_amount, final_amount, status, source, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW(), NOW())");
        $insertStmt->execute([
            $id, $reservationNumber, $matchedUserId, $staffId ?: null, $designationFee,
            $customerName, $customerPhone,
            $reservationDate, $startTime, $endTime,
            $totalAmount, $finalAmount, $source
        ]);

        // 서비스 관계 저장
        require_once BASE_PATH . '/rzxlib/Core/Helpers/ReservationHelper.php';
        \RzxLib\Core\Helpers\ReservationHelper::saveServicesPublic($pdo, $prefix, $id, $services, null);

        $pdo->commit();

        // 오늘 대기번호 계산
        $wSt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}reservations WHERE reservation_date = ? AND source IN ('walk_in','designation','kiosk')");
        $wSt->execute([$reservationDate]);
        $resultWaiting = (int)$wSt->fetchColumn();
        $resultNumber = $reservationNumber;
        $submitted = true;
    } catch (Exception $e) {
        $pdo->rollBack();
        error_log('[Kiosk Confirm] Error: ' . $e->getMessage());
        // 디버그: 에러를 JS 콘솔에 출력
        echo '<script>console.error("[Kiosk] DB Error:", ' . json_encode($e->getMessage()) . ');</script>';
    }
}
?>
<!DOCTYPE html>
<html lang="<?= $currentLocale ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1.0">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#000000">
    <link rel="manifest" href="/manifest-kiosk.json">
    <title><?= htmlspecialchars($siteName) ?> - Kiosk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <?php include __DIR__ . '/_styles.php'; ?>
</head>
<body class="<?= $kioskBgType === 'gradient' ? 'bg-animated' : '' ?> flex flex-col h-screen select-none">

<?php include __DIR__ . '/_bg.php'; ?>

    <div class="kiosk-content flex flex-col h-screen w-full">

<?php if ($submitted): ?>
        <!-- ═══ 접수 완료 화면 ═══ -->
        <?php include __DIR__ . '/confirm-done.php'; ?>
<?php else: ?>
        <!-- ═══ 접수 확인 화면 ═══ -->
        <?php include __DIR__ . '/confirm-form.php'; ?>
<?php endif; ?>

    </div>

<?php include __DIR__ . '/_scripts.php'; ?>
</body>
</html>
