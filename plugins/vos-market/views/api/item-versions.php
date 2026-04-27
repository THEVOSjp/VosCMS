<?php
header('Content-Type: application/json; charset=utf-8');
try {
    $pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $pfx = $_ENV['DB_PREFIX'] ?? 'rzx_';
    $slug = trim($_GET['slug'] ?? '');
    if (!$slug) { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'slug required']); exit; }
    $item = $pdo->prepare("SELECT id FROM {$pfx}mkt_items WHERE slug=? AND status='active'");
    $item->execute([$slug]); $item = $item->fetchColumn();
    if (!$item) { http_response_code(404); echo json_encode(['ok'=>false,'msg'=>'Not found']); exit; }
    $st = $pdo->prepare("SELECT version,changelog,min_voscms_version,min_php_version,file_size,created_at FROM {$pfx}mkt_item_versions WHERE item_id=? AND status='active' ORDER BY created_at DESC");
    $st->execute([$item]); $versions = $st->fetchAll();
    echo json_encode(['ok'=>true,'data'=>$versions], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500); echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
