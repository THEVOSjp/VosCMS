<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/_auth.php';
try {
    $pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $pfx = $_ENV['DB_PREFIX'] ?? 'rzx_';
    $partnerId = mkt_partner_auth($pdo, $pfx);
    $st = $pdo->prepare("SELECT id,item_type,submitted_slug,submitted_version,is_update,status,rejection_reason,submitted_at,reviewed_at FROM {$pfx}mkt_submissions WHERE partner_id=? ORDER BY submitted_at DESC LIMIT 50");
    $st->execute([$partnerId]); $subs = $st->fetchAll();
    echo json_encode(['ok'=>true,'data'=>$subs], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500); echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
