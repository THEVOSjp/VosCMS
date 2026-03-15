<?php
/**
 * 예약 관리 POST API 핸들러
 * 라우트: POST /admin/reservations/* (AJAX)
 * 변수: $pdo, $prefix, $apiAction, $apiId (index.php에서 설정)
 */

include __DIR__ . '/_init.php';

header('Content-Type: application/json; charset=utf-8');

// CSRF 검증
$token = $_POST['_token'] ?? '';
if ($token !== ($_SESSION['csrf_token'] ?? '')) {
    echo json_encode(['error' => true, 'message' => 'CSRF 토큰이 유효하지 않습니다.']);
    exit;
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
        $notes = trim($_POST['notes'] ?? '');
        $source = $_POST['source'] ?? 'online'; // online, walk_in, admin

        if (!$customerName || !$customerPhone || !$reservationDate || !$startTime) {
            $_SESSION['errors'] = ['필수 항목을 모두 입력해주세요.'];
            $_SESSION['old_input'] = $_POST;
            header("Location: {$adminUrl}/reservations/create");
            exit;
        }

        $createdIds = [];
        $pdo->beginTransaction();

        foreach ($serviceIds as $serviceId) {
            // 서비스 정보 조회
            $svcStmt = $pdo->prepare("SELECT * FROM {$prefix}services WHERE id = ?");
            $svcStmt->execute([$serviceId]);
            $svc = $svcStmt->fetch(PDO::FETCH_ASSOC);
            if (!$svc) continue;

            $totalAmount = (float)($svc['price'] ?? 0);
            $finalAmount = $totalAmount;
            $duration = (int)($svc['duration'] ?? 60);

            // 종료 시간 계산
            $calcEnd = $endTime;
            if (!$calcEnd) {
                $startParts = explode(':', $startTime);
                $endMinutes = ((int)$startParts[0]) * 60 + ((int)($startParts[1] ?? 0)) + $duration;
                $calcEnd = sprintf('%02d:%02d', floor($endMinutes / 60) % 24, $endMinutes % 60);
            }

            // 예약번호 생성
            $reservationNumber = 'RZX' . date('YmdHis') . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

            $id = bin2hex(random_bytes(18));

            $insertStmt = $pdo->prepare("INSERT INTO {$prefix}reservations
                (id, reservation_number, service_id, customer_name, customer_phone, customer_email,
                 reservation_date, start_time, end_time, total_amount, final_amount, status, source, notes, created_at, updated_at)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?, ?, NOW(), NOW())");

            $insertStmt->execute([
                $id, $reservationNumber, $serviceId,
                $customerName, $customerPhone, $customerEmail,
                $reservationDate, $startTime, $calcEnd,
                $totalAmount, $finalAmount, $source, $notes
            ]);

            $createdIds[] = $id;
            console_log("[Reservations API] Created: {$reservationNumber}");
        }

        $pdo->commit();

        // POS 접수(walk_in)이면 POS 페이지로, 그 외엔 상세/목록으로
        if ($source === 'walk_in') {
            header("Location: {$adminUrl}/reservations/pos");
        } elseif (count($createdIds) === 1) {
            header("Location: {$adminUrl}/reservations/{$createdIds[0]}");
        } else {
            header("Location: {$adminUrl}/reservations");
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
        $serviceId = $_POST['service_id'] ?? $r['service_id'];
        $totalAmount = (float)($_POST['total_amount'] ?? $r['total_amount']);
        $discountAmount = (float)($_POST['discount_amount'] ?? $r['discount_amount']);
        $finalAmount = $totalAmount - $discountAmount;

        $updateStmt = $pdo->prepare("UPDATE {$prefix}reservations SET
            service_id = ?, reservation_date = ?, start_time = ?, end_time = ?,
            customer_name = ?, customer_phone = ?, customer_email = ?,
            total_amount = ?, discount_amount = ?, final_amount = ?,
            notes = ?, admin_notes = ?, updated_at = NOW()
            WHERE id = ?");

        $updateStmt->execute([
            $serviceId,
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

        // 현재 시간으로 start_time 업데이트, 서비스 duration으로 end_time 재계산
        $nowTime = date('H:i:s');
        $svc = $pdo->prepare("SELECT duration FROM {$prefix}services WHERE id = ?");
        $svc->execute([$r['service_id']]);
        $duration = (int)($svc->fetchColumn() ?: 60);
        $endMinutes = intval(date('H')) * 60 + intval(date('i')) + $duration;
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
        $payMethod = trim($_POST['method'] ?? 'card');
        $currentPaid = (float)($r['paid_amount'] ?? 0);
        $finalAmount = (float)($r['final_amount'] ?? 0);
        $newPaid = $currentPaid + $payAmount;

        // 결제 상태 결정
        if ($newPaid >= $finalAmount) {
            $paymentStatus = 'paid';
            $newPaid = $finalAmount; // 초과 방지
        } elseif ($newPaid > 0) {
            $paymentStatus = 'partial';
        } else {
            $paymentStatus = 'unpaid';
        }

        $updateStmt = $pdo->prepare("UPDATE {$prefix}reservations SET paid_amount = ?, payment_status = ?, updated_at = NOW() WHERE id = ?");
        $updateStmt->execute([$newPaid, $paymentStatus, $apiId]);

        console_log("[Reservations API] Payment: {$apiId}, +{$payAmount} ({$payMethod}), total paid: {$newPaid}/{$finalAmount} → {$paymentStatus}");
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

        console_log("[Reservations API] Status changed: {$apiId} → {$newStatus}");

        $isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest';
        if ($isAjax) {
            echo json_encode(['success' => true, 'message' => "예약이 {$statusLabel} 처리되었습니다."]);
        } else {
            header("Location: {$adminUrl}/reservations/{$apiId}");
        }
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
