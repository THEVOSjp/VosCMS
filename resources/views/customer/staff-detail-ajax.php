<?php
/**
 * Staff Detail AJAX Handler - 월간 스케줄, 슬롯 조회, 예약 생성
 * staff-detail.php 에서 include 됨
 * 사용 가능 변수: $pdo, $prefix, $staffId, $scheduleEnabled, $slotInterval, $isLoggedIn, $currentUser, $config
 */

header('Content-Type: application/json; charset=utf-8');
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// 기본 영업시간
$bhRows = $pdo->query("SELECT day_of_week, is_open, open_time, close_time, break_start, break_end FROM {$prefix}business_hours ORDER BY day_of_week")->fetchAll(PDO::FETCH_ASSOC);
$bh = [];
foreach ($bhRows as $r) $bh[$r['day_of_week']] = $r;

// ---- 월별 근무일 조회 ----
if ($action === 'get_month_schedule') {
    $year = (int)($input['year'] ?? date('Y'));
    $month = (int)($input['month'] ?? date('n'));
    $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);

    $wkStmt = $pdo->prepare("SELECT day_of_week, is_working, start_time, end_time FROM {$prefix}staff_schedules WHERE staff_id = ?");
    $wkStmt->execute([$staffId]);
    $weekly = [];
    while ($w = $wkStmt->fetch(PDO::FETCH_ASSOC)) $weekly[$w['day_of_week']] = $w;

    $ovStmt = $pdo->prepare("SELECT override_date, is_working, start_time, end_time FROM {$prefix}staff_schedule_overrides WHERE staff_id = ? AND override_date BETWEEN ? AND ?");
    $firstDay = sprintf('%04d-%02d-01', $year, $month);
    $lastDay = sprintf('%04d-%02d-%02d', $year, $month, $daysInMonth);
    $ovStmt->execute([$staffId, $firstDay, $lastDay]);
    $overrides = [];
    while ($o = $ovStmt->fetch(PDO::FETCH_ASSOC)) $overrides[$o['override_date']] = $o;

    $holStmt = $pdo->prepare("SELECT date FROM {$prefix}holidays WHERE date BETWEEN ? AND ?");
    $holStmt->execute([$firstDay, $lastDay]);
    $holidays = [];
    while ($h = $holStmt->fetch(PDO::FETCH_ASSOC)) $holidays[] = $h['date'];

    $days = [];
    $today = date('Y-m-d');
    for ($d = 1; $d <= $daysInMonth; $d++) {
        $date = sprintf('%04d-%02d-%02d', $year, $month, $d);
        $dow = (int)date('w', strtotime($date));
        $working = false;
        $hours = '';

        if ($date < $today) {
            $days[] = ['date' => $date, 'working' => false, 'past' => true, 'hours' => ''];
            continue;
        }
        if (in_array($date, $holidays)) {
            $days[] = ['date' => $date, 'working' => false, 'holiday' => true, 'hours' => ''];
            continue;
        }

        if (isset($overrides[$date])) {
            $ov = $overrides[$date];
            $working = (bool)$ov['is_working'];
            if ($working) $hours = substr($ov['start_time'], 0, 5) . '-' . substr($ov['end_time'], 0, 5);
        } elseif ($scheduleEnabled && isset($weekly[$dow])) {
            $wk = $weekly[$dow];
            $working = (bool)$wk['is_working'];
            if ($working) $hours = substr($wk['start_time'], 0, 5) . '-' . substr($wk['end_time'], 0, 5);
        } elseif (isset($bh[$dow])) {
            $working = (bool)$bh[$dow]['is_open'];
            if ($working) $hours = substr($bh[$dow]['open_time'], 0, 5) . '-' . substr($bh[$dow]['close_time'], 0, 5);
        }

        $days[] = ['date' => $date, 'working' => $working, 'hours' => $hours];
    }

    echo json_encode(['success' => true, 'days' => $days, 'year' => $year, 'month' => $month]);
    exit;
}

// ---- 날짜별 슬롯 조회 ----
if ($action === 'get_day_slots') {
    $reqDate = $input['date'] ?? '';
    $reqDuration = max(15, (int)($input['duration'] ?? 60));

    if (!$reqDate || $reqDate < date('Y-m-d')) {
        echo json_encode(['success' => true, 'slots' => []]);
        exit;
    }

    $dow = (int)date('w', strtotime($reqDate));
    $openTime = null; $closeTime = null; $breakStart = null; $breakEnd = null; $isWorking = false;

    if ($scheduleEnabled) {
        $ovStmt = $pdo->prepare("SELECT is_working, start_time, end_time, break_start, break_end FROM {$prefix}staff_schedule_overrides WHERE staff_id = ? AND override_date = ?");
        $ovStmt->execute([$staffId, $reqDate]);
        $override = $ovStmt->fetch(PDO::FETCH_ASSOC);
        if ($override) {
            $isWorking = (bool)$override['is_working'];
            $openTime = $override['start_time']; $closeTime = $override['end_time'];
            $breakStart = $override['break_start']; $breakEnd = $override['break_end'];
        }
    }
    if ($openTime === null && $scheduleEnabled) {
        $schStmt = $pdo->prepare("SELECT is_working, start_time, end_time, break_start, break_end FROM {$prefix}staff_schedules WHERE staff_id = ? AND day_of_week = ?");
        $schStmt->execute([$staffId, $dow]);
        $sch = $schStmt->fetch(PDO::FETCH_ASSOC);
        if ($sch) {
            $isWorking = (bool)$sch['is_working'];
            $openTime = $sch['start_time']; $closeTime = $sch['end_time'];
            $breakStart = $sch['break_start']; $breakEnd = $sch['break_end'];
        }
    }
    if ($openTime === null) {
        $bhItem = $bh[$dow] ?? null;
        if ($bhItem) {
            $isWorking = (bool)$bhItem['is_open'];
            $openTime = $bhItem['open_time']; $closeTime = $bhItem['close_time'];
            $breakStart = $bhItem['break_start']; $breakEnd = $bhItem['break_end'];
        } else {
            $isWorking = true; $openTime = '09:00:00'; $closeTime = '20:00:00';
        }
    }

    if (!$isWorking) {
        echo json_encode(['success' => true, 'slots' => [], 'message' => 'day_off']);
        exit;
    }

    $bookedSlots = [];
    $bkStmt = $pdo->prepare("SELECT start_time, end_time FROM {$prefix}reservations WHERE reservation_date = ? AND staff_id = ? AND status NOT IN ('cancelled', 'no_show')");
    $bkStmt->execute([$reqDate, $staffId]);
    while ($bk = $bkStmt->fetch(PDO::FETCH_ASSOC)) {
        $bookedSlots[] = ['start' => $bk['start_time'], 'end' => $bk['end_time']];
    }

    $slots = [];
    $startMin = (int)substr($openTime, 0, 2) * 60 + (int)substr($openTime, 3, 2);
    $endMin = (int)substr($closeTime, 0, 2) * 60 + (int)substr($closeTime, 3, 2);
    $breakStartMin = $breakStart ? ((int)substr($breakStart, 0, 2) * 60 + (int)substr($breakStart, 3, 2)) : null;
    $breakEndMin = $breakEnd ? ((int)substr($breakEnd, 0, 2) * 60 + (int)substr($breakEnd, 3, 2)) : null;

    for ($m = $startMin; $m + $reqDuration <= $endMin; $m += $slotInterval) {
        $slotEnd = $m + $reqDuration;
        if ($breakStartMin !== null && $breakEndMin !== null) {
            if ($m < $breakEndMin && $slotEnd > $breakStartMin) continue;
        }
        $slotTimeStr = sprintf('%02d:%02d', intdiv($m, 60), $m % 60);
        $slotEndStr = sprintf('%02d:%02d:00', intdiv($slotEnd, 60), $slotEnd % 60);
        $slotStartFull = $slotTimeStr . ':00';

        $conflict = false;
        foreach ($bookedSlots as $booked) {
            if ($slotStartFull < $booked['end'] && $slotEndStr > $booked['start']) { $conflict = true; break; }
        }
        if ($conflict) continue;
        if ($reqDate === date('Y-m-d') && $slotTimeStr <= date('H:i')) continue;

        $slots[] = $slotTimeStr;
    }

    echo json_encode(['success' => true, 'slots' => $slots]);
    exit;
}

// ---- 예약 생성 ----
if ($action === 'create_reservation') {
    $serviceIds = $input['service_ids'] ?? [];
    $date = $input['date'] ?? '';
    $time = $input['time'] ?? '';
    $name = trim($input['customer_name'] ?? '');
    $phone = trim($input['customer_phone'] ?? '');
    $email = trim($input['customer_email'] ?? '');
    $notes = trim($input['notes'] ?? '');

    if (!is_array($serviceIds) || empty($serviceIds) || !$date || !$time || !$name || !$phone) {
        echo json_encode(['success' => false, 'message' => __('booking.error.required_fields')]);
        exit;
    }

    // 서비스 조회
    $ph = implode(',', array_fill(0, count($serviceIds), '?'));
    $svcStmt = $pdo->prepare("SELECT id, name, price, duration FROM {$prefix}services WHERE id IN ({$ph}) AND is_active = 1");
    $svcStmt->execute(array_values($serviceIds));
    $selectedServices = $svcStmt->fetchAll(PDO::FETCH_ASSOC);
    if (empty($selectedServices)) {
        echo json_encode(['success' => false, 'message' => __('booking.error.invalid_service')]);
        exit;
    }

    $totalPrice = 0;
    $totalDuration = 0;
    foreach ($selectedServices as $s) {
        $totalPrice += (float)$s['price'];
        $totalDuration += (int)$s['duration'];
    }

    // 지명비
    $designationFee = 0;
    $feeStmt = $pdo->prepare("SELECT designation_fee FROM {$prefix}staff WHERE id = ?");
    $feeStmt->execute([$staffId]);
    $designationFee = (float)($feeStmt->fetchColumn() ?: 0);

    // 회원 등급 할인
    $discountAmount = 0;
    $discountEnabled = ($config['service_discount_enabled'] ?? '0') === '1';
    if ($isLoggedIn && $discountEnabled && !empty($currentUser['id'])) {
        $grStmt = $pdo->prepare("SELECT g.discount_rate FROM {$prefix}users u JOIN {$prefix}member_grades g ON u.grade_id = g.id WHERE u.id = ?");
        $grStmt->execute([$currentUser['id']]);
        $discountRate = (float)($grStmt->fetchColumn() ?: 0);
        if ($discountRate > 0) {
            $discountAmount = floor($totalPrice * $discountRate / 100);
        }
    }

    // 적립금 사용
    $pointsUsed = 0;
    $pointsEnabled = ($config['service_points_enabled'] ?? '0') === '1';
    $reqPoints = max(0, (int)($input['points_used'] ?? 0));
    if ($isLoggedIn && $pointsEnabled && $reqPoints > 0 && !empty($currentUser['id'])) {
        // 보유 잔액 확인
        $balStmt = $pdo->prepare("SELECT points_balance FROM {$prefix}users WHERE id = ?");
        $balStmt->execute([$currentUser['id']]);
        $balance = (float)($balStmt->fetchColumn() ?: 0);
        // 사용 가능 한도: 잔액 vs (소계 - 할인)
        $maxUsable = min($balance, $totalPrice + $designationFee - $discountAmount);
        $pointsUsed = min($reqPoints, $maxUsable);
        if ($pointsUsed < 0) $pointsUsed = 0;
    }

    $finalAmount = $totalPrice + $designationFee - $discountAmount - $pointsUsed;

    // 예약금 설정에 따른 결제 금액 결정
    $depositEnabled = ($config['service_deposit_enabled'] ?? '0') === '1';
    if ($depositEnabled) {
        $depositType = $config['service_deposit_type'] ?? 'fixed';
        if ($depositType === 'percent') {
            $paidAmount = ceil($finalAmount * (float)($config['service_deposit_percent'] ?? 0) / 100);
        } else {
            $paidAmount = min((float)($config['service_deposit_amount'] ?? 0), $finalAmount);
        }
        $paymentStatus = ($paidAmount >= $finalAmount) ? 'paid' : 'partial';
    } else {
        // 예약금 미사용: 전액 결제
        $paidAmount = $finalAmount;
        $paymentStatus = 'paid';
    }

    // 예약번호
    $reservationNumber = 'RZX' . date('ymd') . strtoupper(bin2hex(random_bytes(3)));

    // end_time
    $startDt = new DateTime("$date $time");
    $endDt = clone $startDt;
    $endDt->modify("+{$totalDuration} minutes");

    $userId = $isLoggedIn ? ($currentUser['id'] ?? null) : null;
    $id = bin2hex(random_bytes(4)) . '-' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(6));

    $sql = "INSERT INTO {$prefix}reservations
        (id, reservation_number, user_id, staff_id, customer_name, customer_phone, customer_email,
         reservation_date, start_time, end_time, total_amount, final_amount, designation_fee, discount_amount, points_used,
         paid_amount, payment_status, status, source, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', 'designation', ?)";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([
        $id, $reservationNumber, $userId,
        $staffId,
        $name, $phone, $email ?: null,
        $date, $time . ':00', $endDt->format('H:i:s'),
        $totalPrice, $finalAmount, $designationFee, $discountAmount, $pointsUsed,
        $paidAmount, $paymentStatus, $notes ?: null,
    ]);

    // 적립금 차감 + 트랜잭션 기록
    if ($pointsUsed > 0 && !empty($currentUser['id'])) {
        $pdo->prepare("UPDATE {$prefix}users SET points_balance = points_balance - ? WHERE id = ? AND points_balance >= ?")->execute([$pointsUsed, $currentUser['id'], $pointsUsed]);
        $newBal = (float)$pdo->query("SELECT points_balance FROM {$prefix}users WHERE id = " . $pdo->quote($currentUser['id']))->fetchColumn();
        $txId = sprintf('%s-%s-%s-%s-%s', bin2hex(random_bytes(4)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(6)));
        $pdo->prepare("INSERT INTO {$prefix}point_transactions (id, user_id, type, amount, balance_after, source, source_id, description) VALUES (?, ?, 'use', ?, ?, 'reservation', ?, ?)")
            ->execute([$txId, $currentUser['id'], $pointsUsed, $newBal, $id, '예약 적립금 사용 (' . $reservationNumber . ')']);
    }

    // 번들 ID (있는 경우)
    $bundleId = !empty($input['bundle_id']) ? $input['bundle_id'] : null;

    // 예약-서비스 관계 (service_name 스냅샷 + bundle_id 포함)
    $rsStmt = $pdo->prepare("INSERT INTO {$prefix}reservation_services (reservation_id, service_id, service_name, price, duration, sort_order, bundle_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
    $sortIdx = 0;

    // 번들 선택 시: 번들에 포함된 서비스는 bundle_id 기록, 번들 가격을 첫 서비스에 배분
    if ($bundleId) {
        $bdlStmt = $pdo->prepare("SELECT bundle_price FROM {$prefix}service_bundles WHERE id = ? AND is_active = 1");
        $bdlStmt->execute([$bundleId]);
        $bundlePrice = (float)($bdlStmt->fetchColumn() ?: 0);

        $bdlItemStmt = $pdo->prepare("SELECT service_id FROM {$prefix}service_bundle_items WHERE bundle_id = ?");
        $bdlItemStmt->execute([$bundleId]);
        $bundleServiceIds = $bdlItemStmt->fetchAll(PDO::FETCH_COLUMN);

        // 번들 가격을 서비스 수로 균등 배분
        $bundleSvcCount = 0;
        foreach ($selectedServices as $s) {
            if (in_array($s['id'], $bundleServiceIds)) $bundleSvcCount++;
        }
        $perSvcPrice = $bundleSvcCount > 0 ? round($bundlePrice / $bundleSvcCount, 2) : 0;

        // total/final 재계산: 번들가격 + 비번들서비스 합계
        $totalPrice = $bundlePrice;
        foreach ($selectedServices as $s) {
            if (!in_array($s['id'], $bundleServiceIds)) {
                $totalPrice += (float)$s['price'];
            }
        }
        // 할인 재계산 (번들 가격 기준)
        if ($discountAmount > 0) {
            $discountAmount = floor($totalPrice * ($discountEnabled ? ($discountRate ?? 0) : 0) / 100);
        }
        $finalAmount = $totalPrice + $designationFee - $discountAmount - $pointsUsed;

        // 예약금 재계산 (번들 가격 기준)
        if ($depositEnabled) {
            if (($config['service_deposit_type'] ?? 'fixed') === 'percent') {
                $paidAmount = ceil($finalAmount * (float)($config['service_deposit_percent'] ?? 0) / 100);
            } else {
                $paidAmount = min((float)($config['service_deposit_amount'] ?? 0), $finalAmount);
            }
            $paymentStatus = ($paidAmount >= $finalAmount) ? 'paid' : 'partial';
        } else {
            $paidAmount = $finalAmount;
            $paymentStatus = 'paid';
        }

        // UPDATE reservations with recalculated amounts
        $pdo->prepare("UPDATE {$prefix}reservations SET total_amount = ?, final_amount = ?, discount_amount = ?, paid_amount = ?, payment_status = ? WHERE id = ?")->execute([$totalPrice, $finalAmount, $discountAmount, $paidAmount, $paymentStatus, $id]);

        foreach ($selectedServices as $s) {
            $isBundled = in_array($s['id'], $bundleServiceIds);
            $svcPrice = $isBundled ? $perSvcPrice : (float)$s['price'];
            $rsStmt->execute([$id, $s['id'], $s['name'], $svcPrice, $s['duration'], $sortIdx++, $isBundled ? $bundleId : null]);
        }
    } else {
        foreach ($selectedServices as $s) {
            $rsStmt->execute([$id, $s['id'], $s['name'], $s['price'], $s['duration'], $sortIdx++, null]);
        }
    }

    echo json_encode([
        'success' => true,
        'message' => __('booking.success'),
        'reservation_number' => $reservationNumber,
    ], JSON_UNESCAPED_UNICODE);
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
exit;
