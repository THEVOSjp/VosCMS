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
        $notes = trim($_POST['notes'] ?? '');
        $source = $_POST['source'] ?? 'admin';

        if (!$customerName || !$customerPhone || !$reservationDate || !$startTime) {
            $_SESSION['errors'] = ['필수 항목을 모두 입력해주세요.'];
            $_SESSION['old_input'] = $_POST;
            header("Location: {$adminUrl}/reservations/create");
            exit;
        }

        // 서비스 조회 + 합산
        $ph = implode(',', array_fill(0, count($serviceIds), '?'));
        $svcStmt = $pdo->prepare("SELECT id, name, price, duration FROM {$prefix}services WHERE id IN ({$ph})");
        $svcStmt->execute(array_values($serviceIds));
        $services = $svcStmt->fetchAll(PDO::FETCH_ASSOC);
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

        $pdo->beginTransaction();

        $id = bin2hex(random_bytes(18));
        $reservationNumber = 'RZX' . date('YmdHis') . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

        $insertStmt = $pdo->prepare("INSERT INTO {$prefix}reservations
            (id, reservation_number, staff_id, customer_name, customer_phone, customer_email,
             reservation_date, start_time, end_time, total_amount, final_amount, status, source, notes, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, NOW(), NOW())");
        $insertStmt->execute([
            $id, $reservationNumber, $staffId ?: null,
            $customerName, $customerPhone, $customerEmail,
            $reservationDate, $startTime, $endTime,
            $totalAmount, $totalAmount, $source, $notes
        ]);

        // 서비스 관계 저장
        $rsStmt = $pdo->prepare("INSERT INTO {$prefix}reservation_services (reservation_id, service_id, service_name, price, duration, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
        $idx = 0;
        foreach ($services as $s) {
            $rsStmt->execute([$id, $s['id'], $s['name'], $s['price'], $s['duration'], $idx++]);
        }

        $pdo->commit();
        console_log("[Reservations API] Created: {$reservationNumber} ({$idx} services)");

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
        $currentPaid = (float)($r['paid_amount'] ?? 0);
        $finalAmount = (float)($r['final_amount'] ?? 0);
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

        console_log("[Reservations API] Payment: {$apiId}, +{$payAmount}, total: {$newPaid}/{$finalAmount} → {$paymentStatus}");
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

    // ─── 고객 서비스 내역 조회 (POS) ───
    if ($apiAction === 'customer-services' && $_SERVER['REQUEST_METHOD'] === 'GET') {
        $name = trim($_GET['name'] ?? '');
        $phone = trim($_GET['phone'] ?? '');
        $date = trim($_GET['date'] ?? date('Y-m-d'));
        if (!$name || !$phone) {
            echo json_encode(['error' => true, 'message' => '고객 정보가 필요합니다.']);
            exit;
        }
        // 예약 목록 + junction 테이블에서 서비스 정보 조회
        $stmt = $pdo->prepare("
            SELECT r.id, r.status, r.start_time, r.end_time,
                   r.total_amount, r.final_amount, r.paid_amount, r.payment_status, r.source,
                   GROUP_CONCAT(rs.service_name ORDER BY rs.sort_order SEPARATOR ', ') as service_names,
                   SUM(rs.duration) as total_duration
            FROM {$prefix}reservations r
            LEFT JOIN {$prefix}reservation_services rs ON r.id = rs.reservation_id
            WHERE r.customer_name = ? AND r.customer_phone = ? AND r.reservation_date = ?
              AND r.status NOT IN ('cancelled','no_show')
            GROUP BY r.id
            ORDER BY r.created_at ASC
        ");
        $stmt->execute([$name, $phone, $date]);
        $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
        echo json_encode(['success' => true, 'data' => $services]);
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

        if (empty($serviceIds) || !$customerName || !$customerPhone) {
            echo json_encode(['error' => true, 'message' => '필수 항목을 입력해주세요.']);
            exit;
        }

        // 서비스 조회
        $ph = implode(',', array_fill(0, count($serviceIds), '?'));
        $svcStmt = $pdo->prepare("SELECT id, name, price, duration FROM {$prefix}services WHERE id IN ({$ph})");
        $svcStmt->execute(array_values($serviceIds));
        $services = $svcStmt->fetchAll(PDO::FETCH_ASSOC);
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

        $pdo->beginTransaction();

        $id = bin2hex(random_bytes(18));
        $resNum = 'RZX' . date('YmdHis') . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

        $insertStmt = $pdo->prepare("INSERT INTO {$prefix}reservations
            (id, reservation_number, customer_name, customer_phone, customer_email,
             reservation_date, start_time, end_time, total_amount, final_amount, status, source, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, NOW(), NOW())");
        $insertStmt->execute([
            $id, $resNum, $customerName, $customerPhone, $customerEmail,
            $reservationDate, $nowTime, $endTime, $totalAmount, $totalAmount, $source
        ]);

        $rsStmt = $pdo->prepare("INSERT INTO {$prefix}reservation_services (reservation_id, service_id, service_name, price, duration, sort_order) VALUES (?, ?, ?, ?, ?, ?)");
        $idx = 0;
        foreach ($services as $s) {
            $rsStmt->execute([$id, $s['id'], $s['name'], $s['price'], $s['duration'], $idx++]);
        }

        $pdo->commit();
        console_log("[POS API] Added {$idx} services for {$customerName} (reservation: {$id})");
        echo json_encode(['success' => true, 'message' => '서비스가 추가되었습니다.', 'ids' => [$id]]);
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
