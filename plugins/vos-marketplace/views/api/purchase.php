<?php
/**
 * Marketplace API - 구매 처리
 * POST /api/marketplace/purchase
 */
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['error' => 'Method not allowed']);
    return;
}

$adminId = $_SESSION['admin_id'] ?? '';
if (!$adminId) {
    http_response_code(401);
    echo json_encode(['error' => 'Unauthorized']);
    return;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;
$itemId = (int)($input['item_id'] ?? 0);

if (!$itemId) {
    http_response_code(400);
    echo json_encode(['error' => 'item_id required']);
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

require_once __DIR__ . '/../../src/MarketplaceService.php';
require_once __DIR__ . '/../../src/LicenseService.php';
require_once __DIR__ . '/../../src/Models/MarketplaceItem.php';
require_once __DIR__ . '/../../src/Models/MarketplaceOrder.php';
require_once __DIR__ . '/../../src/Models/OrderItem.php';
require_once __DIR__ . '/../../src/Models/License.php';
require_once __DIR__ . '/../../src/Models/LicenseActivation.php';

$service = new \VosMarketplace\MarketplaceService($pdo, $prefix);
$result = $service->createOrder($adminId, $itemId);

echo json_encode($result);
