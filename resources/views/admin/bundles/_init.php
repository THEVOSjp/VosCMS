<?php
/**
 * 번들(묶음서비스) 관리 - 초기화 + AJAX API
 * 사용 변수: $pdo, $prefix, $config, $adminUrl
 */

if (!function_exists('__')) {
    require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';
}

$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');

// DB 연결
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB Error']);
    exit;
}

// AJAX 요청 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json; charset=utf-8');
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    // ---- 번들 목록 ----
    if ($action === 'list') {
        $rows = $pdo->query("
            SELECT b.*,
                   COUNT(bi.id) as item_count,
                   COALESCE(SUM(s.price), 0) as original_total,
                   GROUP_CONCAT(s.name ORDER BY bi.sort_order SEPARATOR ', ') as service_names
            FROM {$prefix}service_bundles b
            LEFT JOIN {$prefix}service_bundle_items bi ON b.id = bi.bundle_id
            LEFT JOIN {$prefix}services s ON bi.service_id = s.id
            GROUP BY b.id
            ORDER BY b.display_order, b.created_at DESC
        ")->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'bundles' => $rows]);
        exit;
    }

    // ---- 번들 상세 ----
    if ($action === 'get') {
        $id = $input['id'] ?? '';
        $stmt = $pdo->prepare("SELECT * FROM {$prefix}service_bundles WHERE id = ?");
        $stmt->execute([$id]);
        $bundle = $stmt->fetch(PDO::FETCH_ASSOC);
        if (!$bundle) {
            echo json_encode(['success' => false, 'message' => 'Not found']);
            exit;
        }

        $items = $pdo->prepare("
            SELECT bi.service_id, bi.sort_order, s.name, s.price, s.duration
            FROM {$prefix}service_bundle_items bi
            JOIN {$prefix}services s ON bi.service_id = s.id
            WHERE bi.bundle_id = ?
            ORDER BY bi.sort_order
        ");
        $items->execute([$id]);
        $bundle['items'] = $items->fetchAll(PDO::FETCH_ASSOC);

        echo json_encode(['success' => true, 'bundle' => $bundle]);
        exit;
    }

    // ---- 번들 저장 (생성/수정) ----
    if ($action === 'save') {
        $id = trim($input['id'] ?? '');
        $name = trim($input['name'] ?? '');
        $description = trim($input['description'] ?? '');
        $bundlePrice = (float)($input['bundle_price'] ?? 0);
        $displayOrder = (int)($input['display_order'] ?? 0);
        $isActive = (int)($input['is_active'] ?? 1);
        $serviceIds = $input['service_ids'] ?? [];

        if (!$name) {
            echo json_encode(['success' => false, 'message' => __('bundles.error.name_required')]);
            exit;
        }
        if (empty($serviceIds) || !is_array($serviceIds)) {
            echo json_encode(['success' => false, 'message' => __('bundles.error.services_required')]);
            exit;
        }

        $pdo->beginTransaction();
        try {
            if ($id) {
                // 수정
                $stmt = $pdo->prepare("UPDATE {$prefix}service_bundles SET name=?, description=?, bundle_price=?, display_order=?, is_active=?, updated_at=NOW() WHERE id=?");
                $stmt->execute([$name, $description ?: null, $bundlePrice, $displayOrder, $isActive, $id]);
            } else {
                // 생성
                $id = bin2hex(random_bytes(4)) . '-' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(2)) . '-' . bin2hex(random_bytes(6));
                $stmt = $pdo->prepare("INSERT INTO {$prefix}service_bundles (id, name, description, bundle_price, display_order, is_active) VALUES (?, ?, ?, ?, ?, ?)");
                $stmt->execute([$id, $name, $description ?: null, $bundlePrice, $displayOrder, $isActive]);
            }

            // 기존 아이템 삭제 후 재입력
            $pdo->prepare("DELETE FROM {$prefix}service_bundle_items WHERE bundle_id = ?")->execute([$id]);
            $insStmt = $pdo->prepare("INSERT INTO {$prefix}service_bundle_items (bundle_id, service_id, sort_order) VALUES (?, ?, ?)");
            foreach ($serviceIds as $idx => $svcId) {
                $insStmt->execute([$id, $svcId, $idx]);
            }

            $pdo->commit();
            echo json_encode(['success' => true, 'id' => $id, 'message' => __('bundles.saved')]);
        } catch (\Exception $e) {
            $pdo->rollBack();
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        }
        exit;
    }

    // ---- 번들 삭제 ----
    if ($action === 'delete') {
        $id = $input['id'] ?? '';
        $stmt = $pdo->prepare("DELETE FROM {$prefix}service_bundles WHERE id = ?");
        $stmt->execute([$id]);
        echo json_encode(['success' => true, 'message' => __('bundles.deleted')]);
        exit;
    }

    // ---- 번들 활성/비활성 토글 ----
    if ($action === 'toggle') {
        $id = $input['id'] ?? '';
        $pdo->prepare("UPDATE {$prefix}service_bundles SET is_active = NOT is_active, updated_at = NOW() WHERE id = ?")->execute([$id]);
        echo json_encode(['success' => true]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

// 일반 페이지 로드용 - 서비스 목록
$services = $pdo->query("SELECT id, name, price, duration FROM {$prefix}services WHERE is_active = 1 ORDER BY name")->fetchAll(PDO::FETCH_ASSOC);
