<?php
/**
 * VosCMS Marketplace - 아이템 설치 핸들러
 */
header('Content-Type: application/json; charset=utf-8');

$adminId = $_SESSION['admin_id'] ?? '';
if (!$adminId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    return;
}

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$itemId = (int)($_POST['item_id'] ?? 0);

if (!$itemId) {
    echo json_encode(['success' => false, 'message' => 'Invalid item ID']);
    return;
}

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB connection failed']);
    return;
}

// 아이템 확인
$stmt = $pdo->prepare("SELECT * FROM {$prefix}mp_items WHERE id = ?");
$stmt->execute([$itemId]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    echo json_encode(['success' => false, 'message' => 'Item not found']);
    return;
}

// 구매 확인 (무료 or 유료 구매완료)
$price = (float)$item['price'];
if ($price > 0) {
    $checkStmt = $pdo->prepare(
        "SELECT oi.id FROM {$prefix}mp_order_items oi
         JOIN {$prefix}mp_orders o ON o.id = oi.order_id
         WHERE o.admin_id = ? AND oi.item_id = ? AND o.status = 'paid' LIMIT 1"
    );
    $checkStmt->execute([$adminId, $itemId]);
    if (!$checkStmt->fetch()) {
        echo json_encode(['success' => false, 'message' => 'Purchase required']);
        return;
    }
}

// InstallerService 로드
require_once __DIR__ . '/../../src/InstallerService.php';

$installer = new \VosMarketplace\InstallerService(BASE_PATH);
$result = $installer->install($itemId);

echo json_encode($result);
