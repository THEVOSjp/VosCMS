<?php
/**
 * RezlyX Admin - 서비스 관리 API 핸들러
 * services.php에서 include
 *
 * 필요 변수: $pdo, $prefix
 */

// 이미지 업로드 헬퍼 로드
require_once BASE_PATH . '/rzxlib/Core/Helpers/image-upload.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'];

    try {
        switch ($action) {
            // ── 서비스 CRUD ──
            case 'create_service':
                $id = bin2hex(random_bytes(16));
                $id = substr($id, 0, 8) . '-' . substr($id, 8, 4) . '-' . substr($id, 12, 4) . '-' . substr($id, 16, 4) . '-' . substr($id, 20, 12);
                $name = trim($_POST['name'] ?? '');
                $slug = trim($_POST['slug'] ?? '');
                $categoryId = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
                $description = trim($_POST['description'] ?? '');
                $price = floatval($_POST['price'] ?? 0);
                $duration = intval($_POST['duration'] ?? 30);
                $bufferTime = intval($_POST['buffer_time'] ?? 0);
                $isActive = isset($_POST['is_active']) ? 1 : 0;

                if (empty($name)) {
                    echo json_encode(['success' => false, 'message' => __('services.fields.name') . ' required']);
                    exit;
                }
                if (empty($slug)) {
                    $slug = preg_replace('/[^a-z0-9-]/', '-', strtolower($name));
                    $slug = preg_replace('/-+/', '-', trim($slug, '-'));
                }

                // 이미지 처리
                $imagePath = rzx_upload_image('image', 'services', $id, intval($_POST['image_width'] ?? 800), intval($_POST['image_height'] ?? 600));

                $maxSort = $pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM {$prefix}services")->fetchColumn();
                $stmt = $pdo->prepare("INSERT INTO {$prefix}services (id, category_id, name, slug, description, price, duration, buffer_time, sort_order, is_active, image) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                $stmt->execute([$id, $categoryId, $name, $slug, $description, $price, $duration, $bufferTime, $maxSort, $isActive, $imagePath]);

                echo json_encode(['success' => true, 'message' => __('services.success.created'), 'id' => $id]);
                exit;

            case 'update_service':
                $id = $_POST['id'];
                $name = trim($_POST['name'] ?? '');
                $slug = trim($_POST['slug'] ?? '');
                $categoryId = !empty($_POST['category_id']) ? $_POST['category_id'] : null;
                $description = trim($_POST['description'] ?? '');
                $price = floatval($_POST['price'] ?? 0);
                $duration = intval($_POST['duration'] ?? 30);
                $bufferTime = intval($_POST['buffer_time'] ?? 0);
                $isActive = isset($_POST['is_active']) ? 1 : 0;

                // 이미지 처리
                $removeImage = intval($_POST['remove_image'] ?? 0);
                $existingImage = trim($_POST['existing_image'] ?? '');
                $imagePath = $existingImage;

                if ($removeImage) {
                    rzx_delete_image($existingImage);
                    $imagePath = null;
                }

                // 새 이미지 업로드
                $newImage = rzx_upload_image('image', 'services', $id, intval($_POST['image_width'] ?? 800), intval($_POST['image_height'] ?? 600));
                if ($newImage) {
                    rzx_delete_image($existingImage);
                    $imagePath = $newImage;
                }

                $stmt = $pdo->prepare("UPDATE {$prefix}services SET category_id=?, name=?, slug=?, description=?, price=?, duration=?, buffer_time=?, is_active=?, image=? WHERE id=?");
                $stmt->execute([$categoryId, $name, $slug, $description, $price, $duration, $bufferTime, $isActive, $imagePath, $id]);

                echo json_encode(['success' => true, 'message' => __('services.success.updated')]);
                exit;

            case 'delete_service':
                $id = $_POST['id'];
                // 기존 이미지 삭제
                $imgStmt = $pdo->prepare("SELECT image FROM {$prefix}services WHERE id = ?");
                $imgStmt->execute([$id]);
                $existImg = $imgStmt->fetchColumn();
                if ($existImg) rzx_delete_image($existImg);

                // 예약 확인
                $cnt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}reservations WHERE service_id = ?");
                $cnt->execute([$id]);
                if ($cnt->fetchColumn() > 0) {
                    echo json_encode(['success' => false, 'message' => __('services.error.has_reservations')]);
                    exit;
                }
                $pdo->prepare("DELETE FROM {$prefix}services WHERE id = ?")->execute([$id]);
                echo json_encode(['success' => true, 'message' => __('services.success.deleted')]);
                exit;

            case 'toggle_service':
                $id = $_POST['id'];
                $stmt = $pdo->prepare("UPDATE {$prefix}services SET is_active = NOT is_active WHERE id = ?");
                $stmt->execute([$id]);
                echo json_encode(['success' => true, 'message' => 'OK']);
                exit;

            case 'reorder_services':
                $ids = json_decode($_POST['ids'] ?? '[]', true);
                if (!is_array($ids) || empty($ids)) {
                    echo json_encode(['success' => false, 'message' => 'No IDs']);
                    exit;
                }
                $stmt = $pdo->prepare("UPDATE {$prefix}services SET sort_order = ? WHERE id = ?");
                foreach ($ids as $i => $id) {
                    $stmt->execute([$i, $id]);
                }
                echo json_encode(['success' => true, 'message' => 'OK']);
                exit;

            default:
                echo json_encode(['success' => false, 'message' => 'Unknown action']);
                exit;
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}
