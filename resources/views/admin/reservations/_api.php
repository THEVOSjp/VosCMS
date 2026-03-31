<?php
/**
 * 예약 관리 POST API 핸들러
 * 라우트: POST /admin/reservations/* (AJAX)
 * 변수: $pdo, $prefix, $apiAction, $apiId (index.php에서 설정)
 *
 * DB 구조: rzx_reservations (1건) ↔ rzx_reservation_services (N건)
 */

include __DIR__ . '/_init.php';

header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    $token = $_POST['_token'] ?? '';
    if ($token !== ($_SESSION['csrf_token'] ?? '')) {
        echo json_encode(['error' => true, 'message' => 'CSRF 토큰이 유효하지 않습니다.']);
        exit;
    }
}

$method = $_POST['_method'] ?? $_SERVER['REQUEST_METHOD'];

try {
    // ─── 예약 생성 ───
    if ($apiAction === 'store') {
        $serviceIds = $_POST['service_ids'] ?? [];
        if (empty($serviceIds)) {
            $serviceIds = !empty($_POST['service_id']) ? [$_POST['service_id']] : [];
        }
        if (empty($serviceIds)) {
            echo json_encode(['error' => true, 'message' => '서비스를 선택해주세요.']);
            exit;
        }

        $customerName = trim($_POST['customer_name'] ?? '');
        $customerPhone = trim($_POST['customer_phone'] ?? '');
        $customerEmail = trim($_POST['customer_email'] ?? '');
        $reservationDate = $_POST['reservation_date'] ?? '';
        $startTime = $_POST['start_time'] ?? '';
        $endTime = $_POST['end_time'] ?? '';
        $staffId = $_POST['staff_id'] ?? null;
        $designationFee = (float)($_POST['designation_fee'] ?? 0);
        $notes = trim($_POST['notes'] ?? '');
        $source = $_POST['source'] ?? 'admin';
        $formBundleId = trim($_POST['bundle_id'] ?? '') ?: null;

        if (!$customerName || !$customerPhone || !$reservationDate || !$startTime) {
            $errMsg = __('reservations.error_required') ?? '필수 항목을 모두 입력해주세요.';
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
                header('Content-Type: application/json; charset=utf-8');
                echo json_encode(['success' => false, 'message' => $errMsg]);
                exit;
            }
            $_SESSION['errors'] = [$errMsg];
            $_SESSION['old_input'] = $_POST;
            header("Location: {$adminUrl}/reservations/create");
            exit;
        }

        // 서비스 조회 (번들이면 service_bundle_items에서 전체 조회)
        if ($formBundleId) {
            $svcStmt = $pdo->prepare("SELECT s.id, s.name, s.price, s.duration FROM {$prefix}service_bundle_items bi JOIN {$prefix}services s ON bi.service_id = s.id WHERE bi.bundle_id = ? ORDER BY bi.sort_order");
            $svcStmt->execute([$formBundleId]);
            $services = $svcStmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $ph = implode(',', array_fill(0, count($serviceIds), '?'));
            $svcStmt = $pdo->prepare("SELECT id, name, price, duration FROM {$prefix}services WHERE id IN ({$ph})");
            $svcStmt->execute(array_values($serviceIds));
            $services = $svcStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        if (empty($services)) {
            echo json_encode(['error' => true, 'message' => '유효한 서비스가 없습니다.']);
            exit;
        }

        $totalAmount = 0;
        $totalDuration = 0;
        foreach ($services as $s) {
            $totalAmount += (float)$s['price'];
            $totalDuration += (int)$s['duration'];
        }

        // end_time 계산
        if (!$endTime) {
            $startParts = explode(':', $startTime);
            $endMinutes = ((int)$startParts[0]) * 60 + ((int)($startParts[1] ?? 0)) + $totalDuration;
            $endTime = sprintf('%02d:%02d', floor($endMinutes / 60) % 24, $endMinutes % 60);
        }

        // 회원 매칭: 폼에서 전달된 user_id 우선, 없으면 전화번호 자동 매칭
        $formUserId = trim($_POST['user_id'] ?? '');
        $matchedUserId = !empty($formUserId) ? $formUserId : findUserByPhone($pdo, $prefix, $customerPhone);

        $pdo->beginTransaction();

        $id = bin2hex(random_bytes(18));
        $reservationNumber = 'RZX' . date('YmdHis') . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

        // 번들 가격 조회
        $formBundlePrice = null;
        $finalAmount = $totalAmount + $designationFee;
        if ($formBundleId) {
            try {
                $_fbpStmt = $pdo->prepare("SELECT bundle_price FROM {$prefix}service_bundles WHERE id = ?");
                $_fbpStmt->execute([$formBundleId]);
                $_fbp = $_fbpStmt->fetchColumn();
                if ($_fbp !== false) {
                    $formBundlePrice = (float)$_fbp;
                    $finalAmount = $formBundlePrice + $designationFee;
                }
            } catch (\Throwable $e) {}
        }

        $insertStmt = $pdo->prepare("INSERT INTO {$prefix}reservations
            (id, reservation_number, user_id, staff_id, bundle_id, bundle_price, designation_fee, customer_name, customer_phone, customer_email,
             reservation_date, start_time, end_time, total_amount, final_amount, status, source, notes, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, NOW(), NOW())");
        $insertStmt->execute([
            $id, $reservationNumber, $matchedUserId, $staffId ?: null, $formBundleId, $formBundlePrice, $designationFee,
            $customerName, $customerPhone, $customerEmail,
            $reservationDate, $startTime, $endTime,
            $totalAmount, $finalAmount, $source, $notes
        ]);

        // 서비스 관계 저장
        $idx = 0;
        try {
            $rsStmt = $pdo->prepare("INSERT INTO {$prefix}reservation_services (reservation_id, service_id, service_name, price, duration, sort_order, bundle_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($services as $s) {
                $rsStmt->execute([$id, $s['id'], $s['name'], $s['price'], $s['duration'], $idx++, $formBundleId]);
            }
        } catch (PDOException $e) {
            if (stripos($e->getMessage(), 'Unknown column') !== false) {
                $rsStmt = $pdo->prepare("INSERT INTO {$prefix}reservation_services (reservation_id, service_id, service_name, price, duration, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($services as $s) {
                    $rsStmt->execute([$id, $s['id'], $s['name'], $s['price'], $s['duration'], $idx++]);
                }
            } else {
                throw $e;
            }
        }

        $pdo->commit();
        console_log("[Reservations API] Created: {$reservationNumber} ({$idx} services)");

        if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest') {
            header('Content-Type: application/json; charset=utf-8');
            echo json_encode(['success' => true, 'message' => __('reservations.created_success') ?? '예약이 등록되었습니다.', 'reservation_id' => $id, 'reservation_number' => $reservationNumber]);
            exit;
        }

        if ($source === 'walk_in') {
            header("Location: {$adminUrl}/reservations/pos");
        } else {
            header("Location: {$adminUrl}/reservations/{$id}");
        }
        exit;
    }

    // ─── 예약 수정 (PUT) ───
    if ($apiAction === 'update' && $apiId) {
        $stmt = $pdo->prepare("SELECT * FROM {$prefix}reservations WHERE id = ?");
        $stmt->execute([$apiId]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$r) {
            echo json_encode(['error' => true, 'message' => '예약을 찾을 수 없습니다.']);
            exit;
        }

        // admin_notes만 업데이트 (AJAX)
        if (isset($_POST['admin_notes']) && !isset($_POST['customer_name'])) {
            $updateStmt = $pdo->prepare("UPDATE {$prefix}reservations SET admin_notes = ?, updated_at = NOW() WHERE id = ?");
            $updateStmt->execute([trim($_POST['admin_notes']), $apiId]);
            echo json_encode(['success' => true, 'message' => '저장되었습니다.']);
            exit;
        }

        // 전체 수정
        $totalAmount = (float)($_POST['total_amount'] ?? $r['total_amount']);
        $discountAmount = (float)($_POST['discount_amount'] ?? $r['discount_amount']);
        $finalAmount = $totalAmount - $discountAmount;

        $updateStmt = $pdo->prepare("UPDATE {$prefix}reservations SET
            reservation_date = ?, start_time = ?, end_time = ?,
            customer_name = ?, customer_phone = ?, customer_email = ?,
            total_amount = ?, discount_amount = ?, final_amount = ?,
            notes = ?, admin_notes = ?, updated_at = NOW()
            WHERE id = ?");

        $updateStmt->execute([
            $_POST['reservation_date'] ?? $r['reservation_date'],
            $_POST['start_time'] ?? $r['start_time'],
            $_POST['end_time'] ?? $r['end_time'],
            trim($_POST['customer_name'] ?? $r['customer_name']),
            trim($_POST['customer_phone'] ?? $r['customer_phone']),
            trim($_POST['customer_email'] ?? $r['customer_email']),
            $totalAmount, $discountAmount, $finalAmount,
            trim($_POST['notes'] ?? $r['notes']),
            trim($_POST['admin_notes'] ?? $r['admin_notes']),
            $apiId
        ]);

        header("Location: {$adminUrl}/reservations/{$apiId}");
        exit;
    }

    // ─── 서비스 시작 (POS: 대기 → 이용중) ───
    if ($apiAction === 'start-service' && $apiId) {
        $stmt = $pdo->prepare("SELECT * FROM {$prefix}reservations WHERE id = ?");
        $stmt->execute([$apiId]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$r) {
            echo json_encode(['error' => true, 'message' => '예약을 찾을 수 없습니다.']);
            exit;
        }

        $nowTime = date('H:i:s');
        // junction 테이블에서 총 duration 조회
        $durStmt = $pdo->prepare("SELECT COALESCE(SUM(duration), 60) FROM {$prefix}reservation_services WHERE reservation_id = ?");
        $durStmt->execute([$apiId]);
        $totalDuration = (int)$durStmt->fetchColumn();

        $endMinutes = intval(date('H')) * 60 + intval(date('i')) + $totalDuration;
        $endTime = sprintf('%02d:%02d:00', floor($endMinutes / 60) % 24, $endMinutes % 60);

        $updateStmt = $pdo->prepare("UPDATE {$prefix}reservations SET status = 'confirmed', start_time = ?, end_time = ?, updated_at = NOW() WHERE id = ?");
        $updateStmt->execute([$nowTime, $endTime, $apiId]);

        console_log("[Reservations API] Service started: {$apiId}, {$nowTime} ~ {$endTime}");
        echo json_encode(['success' => true, 'message' => '서비스가 시작되었습니다.']);
        exit;
    }

    // ─── 결제 처리 (POS) ───
    if ($apiAction === 'payment' && $apiId) {
        $stmt = $pdo->prepare("SELECT * FROM {$prefix}reservations WHERE id = ?");
        $stmt->execute([$apiId]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$r) {
            echo json_encode(['error' => true, 'message' => '예약을 찾을 수 없습니다.']);
            exit;
        }

        $payAmount = (float)($_POST['amount'] ?? 0);
        $pointsUsed = max(0, (float)($_POST['points_used'] ?? 0));
        $pointsUserId = trim($_POST['user_id'] ?? '') ?: ($r['user_id'] ?? '');
        $currentPaid = (float)($r['paid_amount'] ?? 0);
        $currentPoints = (float)($r['points_used'] ?? 0);
        $finalAmount = (float)($r['final_amount'] ?? 0);

        // 적립금 사용 시 final_amount 재계산
        if ($pointsUsed > 0 && $pointsUserId) {
            // 잔액 확인
            $balStmt = $pdo->prepare("SELECT points_balance FROM {$prefix}users WHERE id = ?");
            $balStmt->execute([$pointsUserId]);
            $bal = (float)($balStmt->fetchColumn() ?: 0);
            $pointsUsed = min($pointsUsed, $bal);
            if ($pointsUsed > 0) {
                // 적립금 차감
                $pdo->prepare("UPDATE {$prefix}users SET points_balance = points_balance - ? WHERE id = ? AND points_balance >= ?")->execute([$pointsUsed, $pointsUserId, $pointsUsed]);
                $newBal = (float)$pdo->query("SELECT points_balance FROM {$prefix}users WHERE id = " . $pdo->quote($pointsUserId))->fetchColumn();
                // 트랜잭션 기록
                $txId = sprintf('%s-%s-%s-%s-%s', bin2hex(random_bytes(4)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(6)));
                $pdo->prepare("INSERT INTO {$prefix}point_transactions (id, user_id, type, amount, balance_after, source, source_id, description) VALUES (?, ?, 'use', ?, ?, 'pos_payment', ?, ?)")
                    ->execute([$txId, $pointsUserId, $pointsUsed, $newBal, $apiId, 'POS 결제 적립금 사용 (' . ($r['reservation_number'] ?? $apiId) . ')']);
                // 예약에 적립금 기록 + final_amount 재계산
                $newPointsUsed = $currentPoints + $pointsUsed;
                $finalAmount = $finalAmount - $pointsUsed;
                $pdo->prepare("UPDATE {$prefix}reservations SET points_used = ?, final_amount = ? WHERE id = ?")->execute([$newPointsUsed, $finalAmount, $apiId]);
                console_log("[Reservations API] Points used: {$pointsUsed}, new balance: {$newBal}");
            }
        }

        $newPaid = $currentPaid + $payAmount;

        if ($newPaid >= $finalAmount) {
            $paymentStatus = 'paid';
            $newPaid = $finalAmount;
        } elseif ($newPaid > 0) {
            $paymentStatus = 'partial';
        } else {
            $paymentStatus = 'unpaid';
        }

        $updateStmt = $pdo->prepare("UPDATE {$prefix}reservations SET paid_amount = ?, payment_status = ?, updated_at = NOW() WHERE id = ?");
        $updateStmt->execute([$newPaid, $paymentStatus, $apiId]);

        console_log("[Reservations API] Payment: {$apiId}, +{$payAmount}, points: {$pointsUsed}, total: {$newPaid}/{$finalAmount} → {$paymentStatus}");
        echo json_encode(['success' => true, 'message' => '결제가 처리되었습니다.', 'payment_status' => $paymentStatus, 'paid_amount' => $newPaid]);
        exit;
    }

    // ─── 상태 변경 ───
    if (in_array($apiAction, ['confirm', 'cancel', 'complete', 'no-show']) && $apiId) {
        $stmt = $pdo->prepare("SELECT * FROM {$prefix}reservations WHERE id = ?");
        $stmt->execute([$apiId]);
        $r = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$r) {
            echo json_encode(['error' => true, 'message' => '예약을 찾을 수 없습니다.']);
            exit;
        }

        $newStatus = match($apiAction) {
            'confirm'  => 'confirmed',
            'cancel'   => 'cancelled',
            'complete' => 'completed',
            'no-show'  => 'no_show',
        };

        $sql = "UPDATE {$prefix}reservations SET status = ?, updated_at = NOW()";
        $params = [$newStatus];

        if ($apiAction === 'cancel') {
            $reason = trim($_POST['reason'] ?? '관리자에 의한 취소');
            $sql .= ", cancel_reason = ?, cancelled_at = NOW()";
            $params[] = $reason;
        }

        $sql .= " WHERE id = ?";
        $params[] = $apiId;

        $updateStmt = $pdo->prepare($sql);
        $updateStmt->execute($params);

        $statusLabel = match($apiAction) {
            'confirm'  => '확정',
            'cancel'   => '취소',
            'complete' => '완료',
            'no-show'  => '노쇼',
        };

        console_log("[Reservations API] Status: {$apiId} → {$newStatus}");

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($isAjax) {
            echo json_encode(['success' => true, 'message' => "예약이 {$statusLabel} 처리되었습니다."]);
        } else {
            header("Location: {$adminUrl}/reservations/{$apiId}");
        }
        exit;
    }

    // ─── 고객 검색 (이름/전화번호 자동완성, 암호화 필드 복호화 후 검색) ───
    if ($apiAction === 'search-customers' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $q = trim($_GET['q'] ?? '');
        if (mb_strlen($q) < 1) {
            echo json_encode(['success' => true, 'customers' => []]);
            exit;
        }

        require_once BASE_PATH . '/rzxlib/Core/Helpers/Encryption.php';
        $qLower = mb_strtolower($q);

        $stmt = $pdo->query("SELECT id, name, phone, email FROM {$prefix}users WHERE status = 'active' AND deleted_at IS NULL ORDER BY created_at DESC");
        $allUsers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $results = [];
        foreach ($allUsers as $u) {
            $decName = \RzxLib\Core\Helpers\Encryption::decrypt($u['name'] ?? '') ?: '';
            $decPhone = \RzxLib\Core\Helpers\Encryption::decrypt($u['phone'] ?? '') ?: '';
            $decEmail = \RzxLib\Core\Helpers\Encryption::decrypt($u['email'] ?? '') ?: '';
            if (mb_strpos(mb_strtolower($decName), $qLower) !== false ||
                mb_strpos($decPhone, $q) !== false ||
                mb_strpos(mb_strtolower($decEmail), $qLower) !== false) {
                $results[] = [
                    'id' => $u['id'],
                    'name' => $decName,
                    'phone' => $decPhone,
                    'email' => $decEmail,
                ];
                if (count($results) >= 10) break;
            }
        }

        echo json_encode(['success' => true, 'customers' => $results]);
        exit;
    }

    // ─── 회원 적립금 조회 (POS) ───
    if ($apiAction === 'user-points' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $userId = trim($_GET['user_id'] ?? '');
        if (!$userId) {
            echo json_encode(['success' => false, 'points_balance' => 0]);
            exit;
        }
        $stmt = $pdo->prepare("SELECT points_balance FROM {$prefix}users WHERE id = ?");
        $stmt->execute([$userId]);
        $balance = (float)($stmt->fetchColumn() ?: 0);
        echo json_encode(['success' => true, 'points_balance' => $balance]);
        exit;
    }

    // ─── 가용 스태프 조회 (예약 폼) ───
    if ($apiAction === 'available-staff' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $date = trim($_GET['date'] ?? '');
        $startTime = trim($_GET['start_time'] ?? '');
        $endTime = trim($_GET['end_time'] ?? '');

        if (!$date || !$startTime) {
            echo json_encode(['success' => false, 'message' => '날짜와 시작 시간이 필요합니다.']);
            exit;
        }
        // end_time 없으면 start_time + 1시간
        if (!$endTime) {
            $parts = explode(':', $startTime);
            $endMin = ((int)$parts[0]) * 60 + ((int)($parts[1] ?? 0)) + 60;
            $endTime = sprintf('%02d:%02d', floor($endMin / 60) % 24, $endMin % 60);
        }

        // 전체 활성 스태프
        $allStaff = $pdo->query("SELECT id, name, avatar, designation_fee FROM ${prefix}staff WHERE is_active = 1 AND (is_visible = 1 OR is_visible IS NULL) ORDER BY sort_order ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);

        // 해당 일시에 예약이 겹치는 스태프 ID 조회
        $busyStmt = $pdo->prepare("
            SELECT DISTINCT staff_id FROM {$prefix}reservations
            WHERE staff_id IS NOT NULL
              AND reservation_date = ?
              AND status NOT IN ('cancelled', 'no_show', 'completed')
              AND start_time < ? AND end_time > ?
        ");
        $busyStmt->execute([$date, $endTime, $startTime]);
        $busyIds = array_column($busyStmt->fetchAll(PDO::FETCH_ASSOC), 'staff_id');

        // 가용 여부 표시
        foreach ($allStaff as &$s) {
            $s['available'] = !in_array($s['id'], $busyIds);
        }
        unset($s);

        echo json_encode(['success' => true, 'staff' => $allStaff]);
        exit;
    }

    // ─── 고객 서비스 내역 조회 (POS) — 예약 ID 기반 ───
    if ($apiAction === 'customer-services' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $idsParam = trim($_GET['ids'] ?? '');
        if (!$idsParam) {
            echo json_encode(['error' => true, 'message' => '예약 ID가 필요합니다.']);
            exit;
        }
        $ids = array_filter(array_map('trim', explode(',', $idsParam)));
        if (empty($ids)) {
            echo json_encode(['success' => true, 'data' => []]);
            exit;
        }
        $ph = implode(',', array_fill(0, count($ids), '?'));
        try {
            $stmt = $pdo->prepare("
                SELECT r.id as reservation_id, r.reservation_number, r.status, r.start_time, r.end_time,
                       r.paid_amount as reservation_paid, r.final_amount, r.payment_status, r.source,
                       r.designation_fee, r.bundle_id as reservation_bundle_id, r.bundle_price, r.staff_id,
                       st.name as staff_name, st.name_i18n as staff_name_i18n, st.avatar as staff_avatar,
                       rs.service_id, rs.bundle_id, COALESCE(rs.service_name, s2.name) as service_name, rs.price, rs.duration as service_duration,
                       rs.sort_order, s2.image as service_image
                FROM {$prefix}reservations r
                JOIN {$prefix}reservation_services rs ON r.id = rs.reservation_id
                LEFT JOIN {$prefix}services s2 ON rs.service_id = s2.id
                LEFT JOIN {$prefix}staff st ON r.staff_id = st.id
                WHERE r.id IN ({$ph})
                ORDER BY r.start_time ASC, rs.sort_order ASC
            ");
            $stmt->execute(array_values($ids));
            $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        } catch (PDOException $e) {
            $stmt = $pdo->prepare("
                SELECT r.id as reservation_id, r.reservation_number, r.status, r.start_time, r.end_time,
                       r.paid_amount as reservation_paid, r.final_amount, r.payment_status, r.source,
                       r.designation_fee, r.bundle_id as reservation_bundle_id, r.bundle_price, r.staff_id,
                       st.name as staff_name, st.avatar as staff_avatar,
                       rs.service_id, s2.name as service_name, rs.price, rs.duration as service_duration,
                       0 as sort_order, s2.image as service_image
                FROM {$prefix}reservations r
                JOIN {$prefix}reservation_services rs ON r.id = rs.reservation_id
                LEFT JOIN {$prefix}services s2 ON rs.service_id = s2.id
                LEFT JOIN {$prefix}staff st ON r.staff_id = st.id
                WHERE r.id IN ({$ph})
                ORDER BY r.start_time ASC
            ");
            $stmt->execute(array_values($ids));
            $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        }

        // 다국어 적용 (스태프명, 서비스명)
        $_apiLocale = $config['locale'] ?? 'ko';
        $_apiTrMap = [];
        try {
            $_apiLc = array_unique([$_apiLocale, 'en']);
            $_apiLcPh = implode(',', array_fill(0, count($_apiLc), '?'));
            $_apiTrSt = $pdo->prepare("SELECT lang_key, locale, content FROM {$prefix}translations WHERE locale IN ({$_apiLcPh}) AND lang_key LIKE 'service.%.name'");
            $_apiTrSt->execute(array_values($_apiLc));
            while ($_at = $_apiTrSt->fetch(PDO::FETCH_ASSOC)) $_apiTrMap[$_at['lang_key']][$_at['locale']] = $_at['content'];
        } catch (\Throwable $e) {}

        foreach ($services as &$_sv) {
            // 스태프명
            if (!empty($_sv['staff_name_i18n'])) {
                $_si = is_string($_sv['staff_name_i18n']) ? json_decode($_sv['staff_name_i18n'], true) : $_sv['staff_name_i18n'];
                if (is_array($_si)) {
                    if (!empty($_si[$_apiLocale])) $_sv['staff_name'] = $_si[$_apiLocale];
                    elseif (!empty($_si['en'])) $_sv['staff_name'] = $_si['en'];
                }
            }
            unset($_sv['staff_name_i18n']);
            // 서비스명
            $_sKey = 'service.' . $_sv['service_id'] . '.name';
            if (isset($_apiTrMap[$_sKey])) {
                if (!empty($_apiTrMap[$_sKey][$_apiLocale])) $_sv['service_name'] = $_apiTrMap[$_sKey][$_apiLocale];
                elseif (!empty($_apiTrMap[$_sKey]['en'])) $_sv['service_name'] = $_apiTrMap[$_sKey]['en'];
            }
        }
        unset($_sv);

        // 고객 상세 정보 (첫 번째 예약 기준)
        $firstR = $pdo->prepare("SELECT r.notes, r.admin_notes, r.source, r.user_id, r.designation_fee,
                u.name as user_name, u.profile_image, u.birth_date, u.gender, u.created_at as member_since,
                u.grade_id, g.name as grade_name, g.color as grade_color, g.discount_rate, g.point_rate,
                u.points_balance
            FROM {$prefix}reservations r
            LEFT JOIN {$prefix}users u ON r.user_id = u.id
            LEFT JOIN {$prefix}member_grades g ON u.grade_id = g.id
            WHERE r.id = ?");
        $firstR->execute([$ids[0]]);
        $custInfo = $firstR->fetch(PDO::FETCH_ASSOC) ?: [];

        // 번들 정보 조회 (모든 예약에서 bundle_id 찾기)
        $_bundleName = '';
        $_bundleId = null;
        $_bundleItems = [];
        foreach ($services as $_sv) {
            if (!empty($_sv['reservation_bundle_id'])) { $_bundleId = $_sv['reservation_bundle_id']; break; }
        }
        if ($_bundleId) {
            try {
                $_bdlNStmt = $pdo->prepare("SELECT name, bundle_price, image FROM {$prefix}service_bundles WHERE id = ?");
                $_bdlNStmt->execute([$_bundleId]);
                $_bdlRow = $_bdlNStmt->fetch(PDO::FETCH_ASSOC);
                $_bundleName = $_bdlRow['name'] ?? '';
                // 다국어
                $_bdlNKey = 'bundle.' . $_bundleId . '.name';
                if (isset($_apiTrMap[$_bdlNKey])) {
                    if (!empty($_apiTrMap[$_bdlNKey][$_apiLocale])) $_bundleName = $_apiTrMap[$_bdlNKey][$_apiLocale];
                    elseif (!empty($_apiTrMap[$_bdlNKey]['en'])) $_bundleName = $_apiTrMap[$_bdlNKey]['en'];
                } else {
                    try { $_t = $pdo->prepare("SELECT content FROM {$prefix}translations WHERE lang_key = ? AND locale = ?"); $_t->execute([$_bdlNKey, $_apiLocale]); $_tv = $_t->fetchColumn(); if ($_tv) $_bundleName = $_tv; } catch (\Throwable $e) {}
                }
                // 번들 포함 서비스 목록
                $_biStmt = $pdo->prepare("SELECT bi.service_id, s.name, s.duration, s.price FROM {$prefix}service_bundle_items bi JOIN {$prefix}services s ON bi.service_id = s.id WHERE bi.bundle_id = ? ORDER BY bi.sort_order");
                $_biStmt->execute([$_bundleId]);
                while ($_bi = $_biStmt->fetch(PDO::FETCH_ASSOC)) {
                    // 서비스명 다국어
                    $_biKey = 'service.' . $_bi['service_id'] . '.name';
                    if (isset($_apiTrMap[$_biKey])) {
                        if (!empty($_apiTrMap[$_biKey][$_apiLocale])) $_bi['name'] = $_apiTrMap[$_biKey][$_apiLocale];
                        elseif (!empty($_apiTrMap[$_biKey]['en'])) $_bi['name'] = $_apiTrMap[$_biKey]['en'];
                    }
                    $_bundleItems[] = $_bi;
                }
            } catch (\Throwable $e) {}
            foreach ($services as &$_sv) { $_sv['bundle_name'] = $_bundleName; }
            unset($_sv);
        }

        // 등급명 다국어
        if (!empty($custInfo['grade_id']) && !empty($custInfo['grade_name'])) {
            $_gKey = 'grade.' . $custInfo['grade_id'] . '.name';
            if (isset($_apiTrMap[$_gKey])) {
                if (!empty($_apiTrMap[$_gKey][$_apiLocale])) $custInfo['grade_name'] = $_apiTrMap[$_gKey][$_apiLocale];
                elseif (!empty($_apiTrMap[$_gKey]['en'])) $custInfo['grade_name'] = $_apiTrMap[$_gKey]['en'];
            } else {
                // 개별 조회
                try {
                    $_gTrSt = $pdo->prepare("SELECT content FROM {$prefix}translations WHERE lang_key = ? AND locale = ?");
                    $_gTrSt->execute([$_gKey, $_apiLocale]);
                    $_gTrVal = $_gTrSt->fetchColumn();
                    if ($_gTrVal) $custInfo['grade_name'] = $_gTrVal;
                } catch (\Throwable $e) {}
            }
        }

        // 방문 통계
        $visitStats = null;
        if (!empty($custInfo['user_id'])) {
            $vs = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status='completed' THEN 1 ELSE 0 END) as completed, SUM(CASE WHEN status='no_show' THEN 1 ELSE 0 END) as no_show FROM {$prefix}reservations WHERE user_id = ?");
            $vs->execute([$custInfo['user_id']]);
            $visitStats = $vs->fetch(PDO::FETCH_ASSOC);
        }

        // 최근 관리자 메모 (최대 3건)
        $recentMemos = [];
        if (!empty($custInfo['user_id'])) {
            $ms = $pdo->prepare("SELECT m.content, m.created_at, a.name as admin_name FROM {$prefix}admin_memos m LEFT JOIN {$prefix}admins a ON m.admin_id = a.id WHERE m.user_id = ? ORDER BY m.created_at DESC LIMIT 3");
            $ms->execute([$custInfo['user_id']]);
            $recentMemos = $ms->fetchAll(PDO::FETCH_ASSOC);
        }

        echo json_encode([
            'success' => true,
            'data' => $services,
            'customer' => [
                'notes' => $custInfo['notes'] ?? '',
                'admin_notes' => $custInfo['admin_notes'] ?? '',
                'source' => $custInfo['source'] ?? 'online',
                'designation_fee' => (float)($custInfo['designation_fee'] ?? 0),
                'is_member' => !empty($custInfo['user_id']),
                'profile_image' => $custInfo['profile_image'] ?? '',
                'birth_date' => $custInfo['birth_date'] ?? '',
                'gender' => $custInfo['gender'] ?? '',
                'member_since' => $custInfo['member_since'] ?? '',
                'grade_name' => $custInfo['grade_name'] ?? '',
                'grade_color' => $custInfo['grade_color'] ?? '',
                'discount_rate' => (float)($custInfo['discount_rate'] ?? 0),
                'point_rate' => (float)($custInfo['point_rate'] ?? 0),
                'points_balance' => (float)($custInfo['points_balance'] ?? 0),
                'visit_total' => (int)($visitStats['total'] ?? 0),
                'visit_completed' => (int)($visitStats['completed'] ?? 0),
                'visit_no_show' => (int)($visitStats['no_show'] ?? 0),
            ],
            'memos' => $recentMemos,
            'bundle' => $_bundleId ? [
                'id' => $_bundleId,
                'name' => $_bundleName,
                'price' => (float)($_bdlRow['bundle_price'] ?? 0),
                'items' => $_bundleItems,
            ] : null,
        ]);
        exit;
    }

    // ─── 스태프 배정/변경/해제 ───
    if ($apiAction === 'assign-staff' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $reservationIds = $_POST['reservation_ids'] ?? [];
        $staffId = $_POST['staff_id'] ?? '';
        if (empty($reservationIds)) {
            echo json_encode(['success' => false, 'message' => '필수 값 누락']);
            exit;
        }

        // 미배정 처리 (staff_id 비어있으면)
        if ($staffId === '' || $staffId === null) {
            $ph = implode(',', array_fill(0, count($reservationIds), '?'));
            $pdo->prepare("UPDATE {$prefix}reservations SET staff_id = NULL, designation_fee = 0 WHERE id IN ({$ph})")
                ->execute(array_values($reservationIds));
            echo json_encode(['success' => true, 'staff_name' => null, 'unassigned' => true]);
            exit;
        }

        // 스태프 존재 확인
        $stStaff = $pdo->prepare("SELECT id, name, designation_fee FROM ${prefix}staff WHERE id = ? AND is_active = 1 AND (is_visible = 1 OR is_visible IS NULL)");
        $stStaff->execute([$staffId]);
        $staff = $stStaff->fetch(PDO::FETCH_ASSOC);
        if (!$staff) {
            echo json_encode(['success' => false, 'message' => '유효하지 않은 스태프']);
            exit;
        }
        $isDesignation = !empty($_POST['designation']);
        $designationFee = $isDesignation ? (float)($staff['designation_fee'] ?? 0) : 0;

        $ph = implode(',', array_fill(0, count($reservationIds), '?'));
        $pdo->prepare("UPDATE {$prefix}reservations SET staff_id = ?, designation_fee = ? WHERE id IN ({$ph})")
            ->execute(array_merge([$staffId, $designationFee], array_values($reservationIds)));
        echo json_encode(['success' => true, 'staff_name' => $staff['name'], 'designation' => $isDesignation, 'designation_fee' => $designationFee]);
        exit;
    }

    // ─── 서비스 추가 (POS 현장) ───
    if ($apiAction === 'add-service') {
        $serviceIds = $_POST['service_ids'] ?? [];
        $customerName = trim($_POST['customer_name'] ?? '');
        $customerPhone = trim($_POST['customer_phone'] ?? '');
        $customerEmail = trim($_POST['customer_email'] ?? '');
        $reservationDate = $_POST['reservation_date'] ?? date('Y-m-d');
        $source = $_POST['source'] ?? 'walk_in';
        $addBundleId = trim($_POST['bundle_id'] ?? '') ?: null;

        if (!$customerName || !$customerPhone) {
            echo json_encode(['error' => true, 'message' => '필수 항목을 입력해주세요.']);
            exit;
        }

        // 번들이 있으면 service_bundle_items에서 전체 서비스 조회 (스냅샷 저장)
        if ($addBundleId) {
            $svcStmt = $pdo->prepare("SELECT s.id, s.name, s.price, s.duration FROM {$prefix}service_bundle_items bi JOIN {$prefix}services s ON bi.service_id = s.id WHERE bi.bundle_id = ? ORDER BY bi.sort_order");
            $svcStmt->execute([$addBundleId]);
            $services = $svcStmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            if (empty($serviceIds)) {
                echo json_encode(['error' => true, 'message' => '서비스를 선택해주세요.']);
                exit;
            }
            $ph = implode(',', array_fill(0, count($serviceIds), '?'));
            $svcStmt = $pdo->prepare("SELECT id, name, price, duration FROM {$prefix}services WHERE id IN ({$ph})");
            $svcStmt->execute(array_values($serviceIds));
            $services = $svcStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        if (empty($services)) {
            echo json_encode(['error' => true, 'message' => '유효한 서비스가 없습니다.']);
            exit;
        }

        $totalAmount = 0;
        $totalDuration = 0;
        foreach ($services as $s) {
            $totalAmount += (float)$s['price'];
            $totalDuration += (int)$s['duration'];
        }

        $nowTime = date('H:i:s');
        $endMinutes = intval(date('H')) * 60 + intval(date('i')) + $totalDuration;
        $endTime = sprintf('%02d:%02d:00', floor($endMinutes / 60) % 24, $endMinutes % 60);

        // 회원 매칭: 폼에서 전달된 user_id 우선, 없으면 전화번호 자동 매칭
        $formUserId = trim($_POST['user_id'] ?? '');
        $matchedUserId = !empty($formUserId) ? $formUserId : findUserByPhone($pdo, $prefix, $customerPhone);

        $pdo->beginTransaction();

        $id = bin2hex(random_bytes(18));
        $resNum = 'RZX' . date('YmdHis') . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

        // 번들 가격 조회
        $addBundlePrice = null;
        $finalAmount = $totalAmount;
        if ($addBundleId) {
            try {
                $bdlPrStmt = $pdo->prepare("SELECT bundle_price FROM {$prefix}service_bundles WHERE id = ? AND is_active = 1");
                $bdlPrStmt->execute([$addBundleId]);
                $dbBdlPr = $bdlPrStmt->fetchColumn();
                if ($dbBdlPr !== false) {
                    $addBundlePrice = (float)$dbBdlPr;
                    $finalAmount = $addBundlePrice; // 번들 가격으로 설정
                }
            } catch (\Throwable $e) {}
        }

        $insertStmt = $pdo->prepare("INSERT INTO {$prefix}reservations
            (id, reservation_number, user_id, bundle_id, bundle_price, customer_name, customer_phone, customer_email,
             reservation_date, start_time, end_time, total_amount, final_amount, status, source, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW(), NOW())");
        $insertStmt->execute([
            $id, $resNum, $matchedUserId, $addBundleId, $addBundlePrice, $customerName, $customerPhone, $customerEmail,
            $reservationDate, $nowTime, $endTime, $totalAmount, $finalAmount, $source
        ]);

        $idx = 0;
        try {
            $rsStmt = $pdo->prepare("INSERT INTO {$prefix}reservation_services (reservation_id, service_id, service_name, price, duration, sort_order, bundle_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($services as $s) {
                $rsStmt->execute([$id, $s['id'], $s['name'], $s['price'], $s['duration'], $idx++, $addBundleId]);
            }
        } catch (PDOException $e) {
            if (stripos($e->getMessage(), 'Unknown column') !== false) {
                $rsStmt = $pdo->prepare("INSERT INTO {$prefix}reservation_services (reservation_id, service_id, service_name, price, duration, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($services as $s) {
                    $rsStmt->execute([$id, $s['id'], $s['name'], $s['price'], $s['duration'], $idx++]);
                }
            } else {
                throw $e;
            }
        }

        $pdo->commit();
        console_log("[POS API] Added {$idx} services for {$customerName} (reservation: {$id}, bundle: " . ($addBundleId ?? 'none') . ")");
        echo json_encode(['success' => true, 'message' => '서비스가 추가되었습니다.', 'ids' => [$id]]);
        exit;
    }

    // ─── 기존 예약에 서비스 추가 ───
    if ($apiAction === 'append-service') {
        $reservationId = trim($_POST['reservation_id'] ?? '');
        $serviceIds = $_POST['service_ids'] ?? [];
        $appendBundleId = trim($_POST['bundle_id'] ?? '') ?: null;

        if (!$reservationId || (empty($serviceIds) && !$appendBundleId)) {
            echo json_encode(['error' => true, 'message' => '필수 항목이 누락되었습니다.']);
            exit;
        }

        // 예약 존재 확인
        $resCheck = $pdo->prepare("SELECT id, start_time FROM {$prefix}reservations WHERE id = ?");
        $resCheck->execute([$reservationId]);
        $resRow = $resCheck->fetch(PDO::FETCH_ASSOC);
        if (!$resRow) {
            echo json_encode(['error' => true, 'message' => '예약을 찾을 수 없습니다.']);
            exit;
        }

        // 서비스 조회 (번들이면 service_bundle_items에서 전체 조회)
        if ($appendBundleId) {
            $svcStmt = $pdo->prepare("SELECT s.id, s.name, s.price, s.duration FROM {$prefix}service_bundle_items bi JOIN {$prefix}services s ON bi.service_id = s.id WHERE bi.bundle_id = ? ORDER BY bi.sort_order");
            $svcStmt->execute([$appendBundleId]);
            $services = $svcStmt->fetchAll(PDO::FETCH_ASSOC);
        } else {
            $ph = implode(',', array_fill(0, count($serviceIds), '?'));
            $svcStmt = $pdo->prepare("SELECT id, name, price, duration FROM {$prefix}services WHERE id IN ({$ph})");
            $svcStmt->execute(array_values($serviceIds));
            $services = $svcStmt->fetchAll(PDO::FETCH_ASSOC);
        }
        if (empty($services)) {
            echo json_encode(['error' => true, 'message' => '유효한 서비스가 없습니다.']);
            exit;
        }

        // 현재 최대 sort_order
        $sortIdx = 0;
        try {
            $maxSort = $pdo->prepare("SELECT COALESCE(MAX(sort_order), -1) FROM {$prefix}reservation_services WHERE reservation_id = ?");
            $maxSort->execute([$reservationId]);
            $sortIdx = (int)$maxSort->fetchColumn() + 1;
        } catch (PDOException $e) {
            // sort_order 컬럼 미존재 시 무시
        }

        $pdo->beginTransaction();

        try {
            $rsStmt = $pdo->prepare("INSERT INTO {$prefix}reservation_services (reservation_id, service_id, service_name, price, duration, sort_order, bundle_id) VALUES (?, ?, ?, ?, ?, ?, ?)");
            foreach ($services as $s) {
                $rsStmt->execute([$reservationId, $s['id'], $s['name'], $s['price'], $s['duration'], $sortIdx++, $appendBundleId]);
            }
        } catch (PDOException $e) {
            if (stripos($e->getMessage(), 'Unknown column') !== false) {
                $rsStmt = $pdo->prepare("INSERT INTO {$prefix}reservation_services (reservation_id, service_id, service_name, price, duration, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
                foreach ($services as $s) {
                    $rsStmt->execute([$reservationId, $s['id'], $s['name'], $s['price'], $s['duration'], $sortIdx++]);
                }
            } else {
                throw $e;
            }
        }

        // 금액/시간 재계산
        $recalc = $pdo->prepare("SELECT SUM(price) as total, SUM(duration) as dur FROM {$prefix}reservation_services WHERE reservation_id = ?");
        $recalc->execute([$reservationId]);
        $sums = $recalc->fetch(PDO::FETCH_ASSOC);
        $newTotal = (float)($sums['total'] ?? 0);
        $newDur = (int)($sums['dur'] ?? 0);

        $startTime = $resRow['start_time'];
        if ($startTime) {
            $parts = explode(':', $startTime);
            $endMin = ((int)$parts[0]) * 60 + ((int)$parts[1]) + $newDur;
            $newEndTime = sprintf('%02d:%02d:00', floor($endMin / 60) % 24, $endMin % 60);
        } else {
            $newEndTime = null;
        }

        // 번들 가격 조회
        $newFinal = $newTotal;
        if ($appendBundleId) {
            try {
                $bdlPrStmt = $pdo->prepare("SELECT bundle_price FROM {$prefix}service_bundles WHERE id = ?");
                $bdlPrStmt->execute([$appendBundleId]);
                $bdlPr = $bdlPrStmt->fetchColumn();
                if ($bdlPr !== false) $newFinal = (float)$bdlPr;
                $pdo->prepare("UPDATE {$prefix}reservations SET bundle_id = ?, bundle_price = ? WHERE id = ?")->execute([$appendBundleId, $bdlPr, $reservationId]);
            } catch (\Throwable $e) {}
        }

        $upd = $pdo->prepare("UPDATE {$prefix}reservations SET total_amount = ?, final_amount = ?, end_time = ?, updated_at = NOW() WHERE id = ?");
        $upd->execute([$newTotal, $newFinal, $newEndTime, $reservationId]);

        $pdo->commit();
        console_log("[API] Appended " . count($services) . " services to reservation {$reservationId}");
        echo json_encode(['success' => true, 'message' => '서비스가 추가되었습니다.']);
        exit;
    }

    // ─── 서비스 삭제 (POS) ───
    if ($apiAction === 'remove-service') {
        $reservationId = trim($_POST['reservation_id'] ?? '');
        $serviceId = trim($_POST['service_id'] ?? '');

        if (!$reservationId || !$serviceId) {
            echo json_encode(['error' => true, 'message' => '필수 항목이 누락되었습니다.']);
            exit;
        }

        // 해당 예약-서비스 존재 확인
        $check = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}reservation_services WHERE reservation_id = ? AND service_id = ?");
        $check->execute([$reservationId, $serviceId]);
        if ($check->fetchColumn() == 0) {
            echo json_encode(['error' => true, 'message' => '해당 서비스를 찾을 수 없습니다.']);
            exit;
        }

        $pdo->beginTransaction();

        // reservation_services에서 삭제
        $del = $pdo->prepare("DELETE FROM {$prefix}reservation_services WHERE reservation_id = ? AND service_id = ?");
        $del->execute([$reservationId, $serviceId]);

        // 남은 서비스 수 확인
        $remain = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}reservation_services WHERE reservation_id = ?");
        $remain->execute([$reservationId]);
        $remainCount = (int)$remain->fetchColumn();

        if ($remainCount === 0) {
            // 서비스가 모두 삭제되어도 예약 유지 (금액 0으로 리셋)
            $pdo->prepare("UPDATE {$prefix}reservations SET total_amount = 0, final_amount = 0 WHERE id = ?")->execute([$reservationId]);
        } else {
            // 남은 서비스 기준으로 금액/시간 재계산
            $recalc = $pdo->prepare("SELECT SUM(price) as total, SUM(duration) as dur FROM {$prefix}reservation_services WHERE reservation_id = ?");
            $recalc->execute([$reservationId]);
            $sums = $recalc->fetch(PDO::FETCH_ASSOC);
            $newTotal = (float)($sums['total'] ?? 0);
            $newDur = (int)($sums['dur'] ?? 0);

            // start_time 기준으로 end_time 재계산
            $stStmt = $pdo->prepare("SELECT start_time FROM {$prefix}reservations WHERE id = ?");
            $stStmt->execute([$reservationId]);
            $startTime = $stStmt->fetchColumn();
            if ($startTime) {
                $parts = explode(':', $startTime);
                $endMin = ((int)$parts[0]) * 60 + ((int)$parts[1]) + $newDur;
                $newEndTime = sprintf('%02d:%02d:00', floor($endMin / 60) % 24, $endMin % 60);
            } else {
                $newEndTime = null;
            }

            $upd = $pdo->prepare("UPDATE {$prefix}reservations SET total_amount = ?, final_amount = ?, end_time = ?, updated_at = NOW() WHERE id = ?");
            $upd->execute([$newTotal, $newTotal, $newEndTime, $reservationId]);
            console_log("[POS API] Service {$serviceId} removed from reservation {$reservationId}, {$remainCount} services remain");
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'remaining' => $remainCount]);
        exit;
    }

    // ─── 관리자 메모 저장 ───
    if ($apiAction === 'save-memo') {
        $userId = trim($_POST['user_id'] ?? '');
        $reservationId = trim($_POST['reservation_id'] ?? '') ?: null;
        $reservationNumber = trim($_POST['reservation_number'] ?? '') ?: null;
        $content = trim($_POST['content'] ?? '');
        $adminId = $_SESSION['admin_id'] ?? '';

        if (!$userId || !$content || !$adminId) {
            echo json_encode(['error' => true, 'message' => '필수 항목이 누락되었습니다.']);
            exit;
        }

        $memoId = sprintf('%s-%s-%s-%s-%s', bin2hex(random_bytes(4)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(6)));
        $stmt = $pdo->prepare("INSERT INTO {$prefix}admin_memos (id, user_id, reservation_id, reservation_number, admin_id, content, created_at) VALUES (?, ?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$memoId, $userId, $reservationId, $reservationNumber, $adminId, $content]);

        $adminName = $_SESSION['admin_name'] ?? 'Admin';
        console_log("[Reservations API] Memo saved: {$memoId} for user {$userId}");
        echo json_encode([
            'success' => true,
            'message' => '메모가 저장되었습니다.',
            'memo' => [
                'id' => $memoId,
                'content' => $content,
                'admin_name' => $adminName,
                'reservation_number' => $reservationNumber,
                'created_at' => date('Y-m-d H:i:s'),
            ]
        ]);
        exit;
    }

    // ─── 고객 메모 목록 조회 ───
    if ($apiAction === 'customer-memos' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $userId = trim($_GET['user_id'] ?? '');
        if (!$userId) {
            echo json_encode(['success' => true, 'memos' => []]);
            exit;
        }

        $stmt = $pdo->prepare("SELECT m.id, m.content, m.reservation_id, m.reservation_number, m.admin_id, m.created_at, a.name as admin_name
            FROM {$prefix}admin_memos m
            LEFT JOIN {$prefix}admins a ON m.admin_id = a.id
            WHERE m.user_id = ?
            ORDER BY m.created_at DESC
            LIMIT 50");
        $stmt->execute([$userId]);
        $memos = $stmt->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'memos' => $memos]);
        exit;
    }

    echo json_encode(['error' => true, 'message' => '알 수 없는 요청입니다.']);

} catch (\PDOException $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    error_log("[Reservations API] DB Error: " . $e->getMessage());
    echo json_encode(['error' => true, 'message' => 'DB 오류가 발생했습니다.']);
} catch (\Exception $e) {
    error_log("[Reservations API] Error: " . $e->getMessage());
    echo json_encode(['error' => true, 'message' => $e->getMessage()]);
}

function console_log(string $msg): void {
    error_log($msg);
}
