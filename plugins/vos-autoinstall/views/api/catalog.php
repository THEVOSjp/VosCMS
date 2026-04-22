<?php
/**
 * Marketplace API - 카탈로그 조회
 * GET /api/autoinstall/catalog
 */
header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');

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

$type = $_GET['type'] ?? '';
$category = $_GET['category'] ?? '';
$sort = $_GET['sort'] ?? 'popular';
$limit = min((int)($_GET['limit'] ?? 24), 100);
$offset = max((int)($_GET['offset'] ?? 0), 0);

$where = ["status = 'active'"];
$params = [];

if ($type && in_array($type, ['plugin', 'theme', 'widget', 'skin'])) {
    $where[] = "type = ?";
    $params[] = $type;
}
if ($category) {
    $where[] = "category_id = ?";
    $params[] = (int)$category;
}

$whereClause = implode(' AND ', $where);
$orderBy = match ($sort) {
    'newest' => 'created_at DESC',
    'rating' => 'rating_avg DESC',
    'price_asc' => 'price ASC',
    'price_desc' => 'price DESC',
    default => 'download_count DESC',
};

$stmt = $pdo->prepare("SELECT * FROM {$prefix}mp_items WHERE {$whereClause} ORDER BY {$orderBy} LIMIT ? OFFSET ?");
$params[] = $limit;
$params[] = $offset;
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// JSON 필드 파싱
foreach ($items as &$item) {
    foreach (['name', 'description', 'short_description', 'tags', 'screenshots', 'requires_plugins'] as $field) {
        if (isset($item[$field]) && is_string($item[$field])) {
            $item[$field] = json_decode($item[$field], true);
        }
    }
}

$countStmt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}mp_items WHERE " . implode(' AND ', array_slice($where, 0)));
$countStmt->execute(array_slice($params, 0, -2));
$total = (int)$countStmt->fetchColumn();

// 카테고리
$categories = $pdo->query("SELECT * FROM {$prefix}mp_categories WHERE is_active = 1 ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC);
foreach ($categories as &$cat) {
    if (isset($cat['name']) && is_string($cat['name'])) {
        $cat['name'] = json_decode($cat['name'], true);
    }
}

echo json_encode([
    'items' => $items,
    'categories' => $categories,
    'total' => $total,
    'limit' => $limit,
    'offset' => $offset,
]);
