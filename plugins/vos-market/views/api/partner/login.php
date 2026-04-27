<?php
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'msg'=>'POST required']); exit; }
try {
    $pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $pfx = $_ENV['DB_PREFIX'] ?? 'rzx_';

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    if (!$email || !$password) { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'email and password required']); exit; }

    $st = $pdo->prepare("SELECT id,email,display_name,password_hash,status,commission_rate FROM {$pfx}mkt_partners WHERE email=?");
    $st->execute([$email]); $partner = $st->fetch();
    if (!$partner || !password_verify($password, $partner['password_hash'])) {
        http_response_code(401); echo json_encode(['ok'=>false,'msg'=>'이메일 또는 비밀번호가 올바르지 않습니다']); exit;
    }
    if ($partner['status'] !== 'active') {
        http_response_code(403); echo json_encode(['ok'=>false,'msg'=>'계정이 활성화되지 않았습니다','status'=>$partner['status']]); exit;
    }

    // Generate API token
    $token = bin2hex(random_bytes(32));
    $expires = date('Y-m-d H:i:s', time() + 86400 * 30);
    $pdo->prepare("INSERT INTO {$pfx}mkt_api_keys (partner_id,api_key,expires_at) VALUES (?,?,?) ON DUPLICATE KEY UPDATE api_key=VALUES(api_key),expires_at=VALUES(expires_at),created_at=NOW()")
        ->execute([$partner['id'],$token,$expires]);

    unset($partner['password_hash']);
    echo json_encode(['ok'=>true,'token'=>$token,'expires_at'=>$expires,'partner'=>$partner], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500); echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
