<?php
header('Content-Type: application/json; charset=utf-8');
$token = $_POST['_token'] ?? '';
if (!isset($_SESSION['_csrf']) || !hash_equals($_SESSION['_csrf'], $token)) {
    http_response_code(403); echo json_encode(['ok'=>false,'msg'=>'CSRF error']); exit;
}
$action = $_POST['action'] ?? '';
$id     = (int)($_POST['id'] ?? 0);
if (!$id) { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'id required']); exit; }
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
    );
    $pfx = $_ENV['DB_PREFIX'] ?? 'rzx_';

    if ($action === 'status') {
        $status = $_POST['status'] ?? '';
        $allowed = ['active','expired','suspended','refunded'];
        if (!in_array($status, $allowed)) { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'Invalid status']); exit; }
        $pdo->prepare("UPDATE {$pfx}mkt_licenses SET status=?,updated_at=NOW() WHERE id=?")->execute([$status,$id]);
        echo json_encode(['ok'=>true,'msg'=>'상태 변경 완료']);

    } elseif ($action === 'reset_activations') {
        $pdo->prepare("UPDATE {$pfx}mkt_license_activations SET is_active=0 WHERE license_id=?")->execute([$id]);
        $pdo->prepare("UPDATE {$pfx}mkt_licenses SET activation_count=0,updated_at=NOW() WHERE id=?")->execute([$id]);
        echo json_encode(['ok'=>true,'msg'=>'활성화 초기화 완료']);

    } else {
        http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'Unknown action']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
