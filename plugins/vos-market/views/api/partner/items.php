<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/_auth.php';
try {
    $pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $pfx = $_ENV['DB_PREFIX'] ?? 'rzx_';
    $partnerId = mkt_partner_auth($pdo, $pfx);
    $st = $pdo->prepare("SELECT id,slug,type,name,short_description,price,currency,latest_version,download_count,status,created_at FROM {$pfx}mkt_items WHERE partner_id=? ORDER BY created_at DESC");
    $st->execute([$partnerId]); $items = $st->fetchAll();
    echo json_encode(['ok'=>true,'data'=>$items], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500); echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
