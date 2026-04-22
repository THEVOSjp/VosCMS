<?php
/**
 * Marketplace API - 라이선스 검증
 * POST /api/autoinstall/license/validate
 *
 * Body: { license_key, domain, action: activate|deactivate|heartbeat, instance_id?, voscms_version? }
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['valid' => false, 'error' => 'method_not_allowed']);
    return;
}

$input = json_decode(file_get_contents('php://input'), true) ?: $_POST;

$licenseKey = trim($input['license_key'] ?? '');
$domain = trim($input['domain'] ?? '');
$action = $input['action'] ?? 'activate';

if (!$licenseKey || !$domain) {
    http_response_code(400);
    echo json_encode(['valid' => false, 'error' => 'missing_params', 'message' => 'license_key and domain required']);
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
    echo json_encode(['valid' => false, 'error' => 'server_error']);
    return;
}

require_once __DIR__ . '/../../src/LicenseService.php';
require_once __DIR__ . '/../../src/Models/License.php';
require_once __DIR__ . '/../../src/Models/LicenseActivation.php';
require_once __DIR__ . '/../../src/Models/MarketplaceOrder.php';
require_once __DIR__ . '/../../src/Models/OrderItem.php';

$service = new \VosMarketplace\LicenseService($pdo, $prefix);

$meta = [
    'instance_id' => $input['instance_id'] ?? null,
    'ip_address' => $_SERVER['REMOTE_ADDR'] ?? null,
    'voscms_version' => $input['voscms_version'] ?? null,
];

$result = $service->validate($licenseKey, $domain, $action, $meta);

if (!$result['valid']) {
    http_response_code(403);
}

echo json_encode($result);
