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
        $allowed = ['pending','active','suspended','rejected'];
        if (!in_array($status, $allowed)) { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'Invalid status']); exit; }
        $pdo->prepare("UPDATE {$pfx}mkt_partners SET status=?,updated_at=NOW() WHERE id=?")->execute([$status,$id]);
        echo json_encode(['ok'=>true,'msg'=>'상태 변경 완료']);

    } elseif ($action === 'commission') {
        $rate = (float)($_POST['rate'] ?? 0);
        if ($rate < 0 || $rate > 100) { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'Invalid rate']); exit; }
        $pdo->prepare("UPDATE {$pfx}mkt_partners SET commission_rate=?,updated_at=NOW() WHERE id=?")->execute([$rate,$id]);
        echo json_encode(['ok'=>true,'msg'=>'수수료율 변경 완료']);

    } elseif ($action === 'type') {
        $type = $_POST['type'] ?? '';
        $allowed = ['general','verified','partner'];
        if (!in_array($type, $allowed)) { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'Invalid type']); exit; }
        $pdo->prepare("UPDATE {$pfx}mkt_partners SET type=?,updated_at=NOW() WHERE id=?")->execute([$type,$id]);
        echo json_encode(['ok'=>true,'msg'=>'타입 변경 완료']);

    } else {
        http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'Unknown action']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
