<?php
/**
 * VosCMS Marketplace - Admin AJAX API Handler
 */
header('Content-Type: application/json; charset=utf-8');

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$adminId = $_SESSION['admin_id'] ?? '';

if (!$adminId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
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

$action = $_POST['action'] ?? '';

switch ($action) {
    case 'purchase':
        $itemId = (int)($_POST['item_id'] ?? 0);
        if (!$itemId) {
            echo json_encode(['success' => false, 'message' => 'Invalid item']);
            return;
        }

        $stmt = $pdo->prepare("SELECT * FROM {$prefix}mp_items WHERE id = ? AND status = 'active'");
        $stmt->execute([$itemId]);
        $item = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$item) {
            echo json_encode(['success' => false, 'message' => 'Item not found']);
            return;
        }

        // 이미 구매 확인
        $checkStmt = $pdo->prepare(
            "SELECT oi.id FROM {$prefix}mp_order_items oi
             JOIN {$prefix}mp_orders o ON o.id = oi.order_id
             WHERE o.admin_id = ? AND oi.item_id = ? AND o.status = 'paid' LIMIT 1"
        );
        $checkStmt->execute([$adminId, $itemId]);
        if ($checkStmt->fetch()) {
            echo json_encode(['success' => false, 'message' => 'Already purchased']);
            return;
        }

        $price = (float)$item['price'];
        $locale = $_SESSION['locale'] ?? 'ko';
        $name = json_decode($item['name'], true);
        $itemName = $name[$locale] ?? $name['en'] ?? $item['slug'];

        // UUID 생성
        $uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
        $orderNum = 'MKT-' . date('Ymd') . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

        $isFree = $price <= 0;

        // 주문 생성
        $orderStmt = $pdo->prepare(
            "INSERT INTO {$prefix}mp_orders (uuid, admin_id, order_number, subtotal, total, currency, status, paid_at, metadata, created_at, updated_at)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())"
        );
        $orderStmt->execute([
            $uuid, $adminId, $orderNum, $price, $price, $item['currency'],
            $isFree ? 'paid' : 'pending',
            $isFree ? date('Y-m-d H:i:s') : null,
            json_encode(['type' => 'marketplace'])
        ]);
        $orderId = (int)$pdo->lastInsertId();

        // 주문 항목 생성
        $oiStmt = $pdo->prepare(
            "INSERT INTO {$prefix}mp_order_items (order_id, item_id, item_name, item_type, item_slug, price, created_at)
             VALUES (?, ?, ?, ?, ?, ?, NOW())"
        );
        $oiStmt->execute([$orderId, $itemId, $itemName, $item['type'], $item['slug'], $price]);
        $orderItemId = (int)$pdo->lastInsertId();

        if ($isFree) {
            // 무료: 바로 라이선스 발급
            $licKey = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));

            $licStmt = $pdo->prepare(
                "INSERT INTO {$prefix}mp_licenses (license_key, order_item_id, item_id, admin_id, type, max_activations, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, 'single', 1, 'active', NOW(), NOW())"
            );
            $licStmt->execute([$licKey, $orderItemId, $itemId, $adminId]);

            // 다운로드 카운트 증가
            $pdo->prepare("UPDATE {$prefix}mp_items SET download_count = download_count + 1 WHERE id = ?")->execute([$itemId]);

            echo json_encode(['success' => true, 'is_free' => true, 'redirect' => null]);
        } else {
            // 유료: 결제 페이지로 리다이렉트 (PaymentService 연동)
            $baseUrl = $config['app_url'] ?? '';
            $adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
            echo json_encode([
                'success' => true,
                'is_free' => false,
                'order_uuid' => $uuid,
                'redirect' => $adminUrl . '/marketplace/purchases',
                'message' => 'Order created. Payment integration pending.',
            ]);
        }
        break;

    case 'deactivate_domain':
        $licenseId = (int)($_POST['license_id'] ?? 0);
        $domain = trim($_POST['domain'] ?? '');

        if (!$licenseId || !$domain) {
            echo json_encode(['success' => false, 'message' => 'Invalid parameters']);
            return;
        }

        // 소유자 확인
        $licCheck = $pdo->prepare("SELECT id FROM {$prefix}mp_licenses WHERE id = ? AND admin_id = ?");
        $licCheck->execute([$licenseId, $adminId]);
        if (!$licCheck->fetch()) {
            echo json_encode(['success' => false, 'message' => 'License not found']);
            return;
        }

        $pdo->prepare(
            "UPDATE {$prefix}mp_license_activations SET is_active = 0, deactivated_at = NOW()
             WHERE license_id = ? AND domain = ?"
        )->execute([$licenseId, $domain]);

        echo json_encode(['success' => true]);
        break;

    case 'sync_catalog':
        $pm = $pluginManager ?? \RzxLib\Core\Plugin\PluginManager::getInstance();
        $apiUrl = $pm ? $pm->getSetting('vos-marketplace', 'marketplace_api_url', 'https://marketplace.voscms.com/api') : '';

        if (!$apiUrl) {
            echo json_encode(['success' => false, 'message' => 'API URL not configured']);
            return;
        }

        // CatalogClient 사용
        require_once __DIR__ . '/../../src/CatalogClient.php';
        $client = new \VosMarketplace\CatalogClient($apiUrl);
        $result = $client->syncCatalog();

        echo json_encode($result);
        break;

    default:
        echo json_encode(['success' => false, 'message' => 'Unknown action']);
}
