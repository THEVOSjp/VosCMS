<?php
// Shared partner auth helper — include at top of protected partner API files
// Sets $partnerId (int) or exits with 401
function mkt_partner_auth(PDO $pdo, string $pfx): int {
    $token = $_SERVER['HTTP_AUTHORIZATION'] ?? '';
    $token = preg_replace('/^Bearer\s+/', '', $token);
    if (!$token) {
        $token = $_POST['token'] ?? $_GET['token'] ?? '';
    }
    if (!$token) { http_response_code(401); echo json_encode(['ok'=>false,'msg'=>'Unauthorized']); exit; }
    $st = $pdo->prepare("SELECT partner_id,expires_at FROM {$pfx}mkt_api_keys WHERE api_key=?");
    $st->execute([$token]); $row = $st->fetch(PDO::FETCH_ASSOC);
    if (!$row) { http_response_code(401); echo json_encode(['ok'=>false,'msg'=>'Invalid token']); exit; }
    if ($row['expires_at'] && strtotime($row['expires_at']) < time()) {
        http_response_code(401); echo json_encode(['ok'=>false,'msg'=>'Token expired']); exit;
    }
    return (int)$row['partner_id'];
}
