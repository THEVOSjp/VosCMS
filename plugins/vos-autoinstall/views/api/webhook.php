<?php
/**
 * Marketplace API - 결제 웹훅
 * POST /api/autoinstall/webhook
 *
 * Stripe 결제 완료 시 호출. metadata.type === 'autoinstall' 인 것만 처리.
 */
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    return;
}

$payload = file_get_contents('php://input');
$event = json_decode($payload, true);

if (!$event || empty($event['type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    return;
}

// checkout.session.completed 이벤트만 처리
if ($event['type'] !== 'checkout.session.completed') {
    echo json_encode(['received' => true, 'ignored' => true]);
    return;
}

$session = $event['data']['object'] ?? [];
$metadata = $session['metadata'] ?? [];

// 마켓플레이스 결제인지 확인
if (($metadata['type'] ?? '') !== 'autoinstall') {
    echo json_encode(['received' => true, 'ignored' => true]);
    return;
}

$orderUuid = $metadata['mp_order_uuid'] ?? '';
if (!$orderUuid) {
    http_response_code(400);
    echo json_encode(['error' => 'Missing order UUID']);
    return;
}

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['error' => 'Database error']);
    return;
}

// 주문 확인
$stmt = $pdo->prepare("SELECT * FROM {$prefix}mp_orders WHERE uuid = ?");
$stmt->execute([$orderUuid]);
$order = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    http_response_code(404);
    echo json_encode(['error' => 'Order not found']);
    return;
}

if ($order['status'] === 'paid') {
    echo json_encode(['received' => true, 'already_processed' => true]);
    return;
}

// 주문 상태 업데이트
$pdo->prepare("UPDATE {$prefix}mp_orders SET status = 'paid', paid_at = NOW(), updated_at = NOW() WHERE id = ?")
    ->execute([$order['id']]);

// 주문 항목 조회
$oiStmt = $pdo->prepare("SELECT * FROM {$prefix}mp_order_items WHERE order_id = ?");
$oiStmt->execute([$order['id']]);
$orderItems = $oiStmt->fetchAll(PDO::FETCH_ASSOC);

// 라이선스 발급
foreach ($orderItems as $oi) {
    $licKey = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));

    $licStmt = $pdo->prepare(
        "INSERT INTO {$prefix}mp_licenses (license_key, order_item_id, item_id, admin_id, type, max_activations, status, created_at, updated_at)
         VALUES (?, ?, ?, ?, 'single', 1, 'active', NOW(), NOW())"
    );
    $licStmt->execute([$licKey, $oi['id'], $oi['item_id'], $order['admin_id']]);

    // 다운로드 카운트 증가
    $pdo->prepare("UPDATE {$prefix}mp_items SET download_count = download_count + 1 WHERE id = ?")
        ->execute([$oi['item_id']]);
}

echo json_encode(['received' => true, 'processed' => true, 'licenses_created' => count($orderItems)]);
