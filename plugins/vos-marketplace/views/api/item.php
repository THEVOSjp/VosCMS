<?php
/**
 * Marketplace API - 아이템 상세 조회
 * GET /api/marketplace/item?slug=xxx
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$slug = $_GET['slug'] ?? '';

if (!$slug) {
    http_response_code(400);
    echo json_encode(['error' => 'slug parameter required']);
    return;
}

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

$stmt = $pdo->prepare("SELECT * FROM {$prefix}mp_items WHERE slug = ? AND status = 'active'");
$stmt->execute([$slug]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    http_response_code(404);
    echo json_encode(['error' => 'Item not found']);
    return;
}

// JSON 필드 파싱
foreach (['name', 'description', 'short_description', 'tags', 'screenshots', 'requires_plugins'] as $field) {
    if (isset($item[$field]) && is_string($item[$field])) {
        $item[$field] = json_decode($item[$field], true);
    }
}

// 버전 이력
$vStmt = $pdo->prepare("SELECT * FROM {$prefix}mp_item_versions WHERE item_id = ? AND status = 'active' ORDER BY released_at DESC");
$vStmt->execute([$item['id']]);
$item['versions'] = $vStmt->fetchAll(PDO::FETCH_ASSOC);

// 리뷰
$rStmt = $pdo->prepare("SELECT id, rating, title, content, is_verified_purchase, helpful_count, created_at FROM {$prefix}mp_reviews WHERE item_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 20");
$rStmt->execute([$item['id']]);
$item['reviews'] = $rStmt->fetchAll(PDO::FETCH_ASSOC);

echo json_encode($item);
