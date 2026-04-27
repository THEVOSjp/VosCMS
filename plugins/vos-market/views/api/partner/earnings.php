<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/_auth.php';
try {
    $pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $pfx = $_ENV['DB_PREFIX'] ?? 'rzx_';
    $partnerId = mkt_partner_auth($pdo, $pfx);

    $summary = $pdo->prepare("SELECT total_earnings,total_paid,pending_balance,commission_rate FROM {$pfx}mkt_partners WHERE id=?");
    $summary->execute([$partnerId]); $summary = $summary->fetch();

    $recent = $pdo->prepare("SELECT e.amount,e.type,e.status,e.created_at,i.slug FROM {$pfx}mkt_partner_earnings e LEFT JOIN {$pfx}mkt_items i ON i.id=e.item_id WHERE e.partner_id=? ORDER BY e.created_at DESC LIMIT 50");
    $recent->execute([$partnerId]); $recent = $recent->fetchAll();

    echo json_encode(['ok'=>true,'summary'=>$summary,'recent'=>$recent], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500); echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
