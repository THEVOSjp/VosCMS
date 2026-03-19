<?php
/**
 * 번들 상세 관리 - AJAX API
 * edit.php에서 include됨 ($pdo, $prefix, $bundleId 사용 가능)
 */
$input = json_decode(file_get_contents('php://input'), true);
$action = $input['action'] ?? '';

// 번들 전체 저장
if ($action === 'save') {
    $name = trim($input['name'] ?? '');
    $description = trim($input['description'] ?? '');
    $bundlePrice = (float)($input['bundle_price'] ?? 0);
    $displayOrder = (int)($input['display_order'] ?? 0);
    $isActive = (int)($input['is_active'] ?? 1);
    $serviceIds = $input['service_ids'] ?? [];
    $staffIds = $input['staff_ids'] ?? [];

    // 이벤트 할인
    $eventEnabled = (bool)($input['event_enabled'] ?? false);
    $eventPrice = $eventEnabled ? (float)($input['event_price'] ?? 0) : null;
    $eventStart = $eventEnabled && !empty($input['event_start']) ? $input['event_start'] : null;
    $eventEnd = $eventEnabled && !empty($input['event_end']) ? $input['event_end'] : null;
    $eventLabel = $eventEnabled ? trim($input['event_label'] ?? '') : null;

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
        // 기본 정보 업데이트
        $stmt = $pdo->prepare("
            UPDATE {$prefix}service_bundles
            SET name=?, description=?, bundle_price=?, display_order=?, is_active=?,
                event_price=?, event_start=?, event_end=?, event_label=?, updated_at=NOW()
            WHERE id=?
        ");
        $stmt->execute([$name, $description ?: null, $bundlePrice, $displayOrder, $isActive,
            $eventPrice, $eventStart, $eventEnd, $eventLabel ?: null, $bundleId]);

        // 서비스 항목 갱신
        $pdo->prepare("DELETE FROM {$prefix}service_bundle_items WHERE bundle_id = ?")->execute([$bundleId]);
        $insStmt = $pdo->prepare("INSERT INTO {$prefix}service_bundle_items (bundle_id, service_id, sort_order) VALUES (?, ?, ?)");
        foreach ($serviceIds as $idx => $svcId) {
            $insStmt->execute([$bundleId, $svcId, $idx]);
        }

        // 스태프 연동 갱신
        $pdo->prepare("DELETE FROM {$prefix}staff_bundles WHERE bundle_id = ?")->execute([$bundleId]);
        if (!empty($staffIds)) {
            $staffInsStmt = $pdo->prepare("INSERT INTO {$prefix}staff_bundles (staff_id, bundle_id) VALUES (?, ?)");
            foreach ($staffIds as $staffId) {
                $staffInsStmt->execute([(int)$staffId, $bundleId]);
            }
        }

        $pdo->commit();
        echo json_encode(['success' => true, 'message' => __('bundles.saved')]);
    } catch (\Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

// 이미지 삭제
if ($action === 'remove_image') {
    $pdo->prepare("UPDATE {$prefix}service_bundles SET image = NULL, updated_at = NOW() WHERE id = ?")->execute([$bundleId]);
    echo json_encode(['success' => true]);
    exit;
}

// 번들 삭제
if ($action === 'delete') {
    $pdo->beginTransaction();
    try {
        $pdo->prepare("DELETE FROM {$prefix}service_bundle_items WHERE bundle_id = ?")->execute([$bundleId]);
        $pdo->prepare("DELETE FROM {$prefix}staff_bundles WHERE bundle_id = ?")->execute([$bundleId]);
        $pdo->prepare("DELETE FROM {$prefix}service_bundles WHERE id = ?")->execute([$bundleId]);
        $pdo->commit();
        echo json_encode(['success' => true, 'redirect' => true]);
    } catch (\Exception $e) {
        $pdo->rollBack();
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
    }
    exit;
}

echo json_encode(['success' => false, 'message' => 'Unknown action']);
