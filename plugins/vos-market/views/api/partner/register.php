<?php
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'msg'=>'POST required']); exit; }
try {
    $pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $pfx = $_ENV['DB_PREFIX'] ?? 'rzx_';

    $regOpen = $pdo->prepare("SELECT value FROM {$pfx}mkt_settings WHERE `key`='partner_registration_open'");
    $regOpen->execute(); $regOpen = $regOpen->fetchColumn();
    if ($regOpen === '0') { http_response_code(403); echo json_encode(['ok'=>false,'msg'=>'파트너 등록이 현재 닫혀 있습니다']); exit; }

    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $name     = trim($_POST['display_name'] ?? '');
    if (!$email || !filter_var($email, FILTER_VALIDATE_EMAIL)) { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'유효하지 않은 이메일']); exit; }
    if (strlen($password) < 8) { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'비밀번호는 8자 이상이어야 합니다']); exit; }
    if (!$name) { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'display_name required']); exit; }

    $chk = $pdo->prepare("SELECT id FROM {$pfx}mkt_partners WHERE email=?"); $chk->execute([$email]);
    if ($chk->fetchColumn()) { echo json_encode(['ok'=>false,'msg'=>'이미 등록된 이메일입니다']); exit; }

    $slug = preg_replace('/[^a-z0-9\-]/', '-', strtolower($name));
    $slug = preg_replace('/-+/', '-', trim($slug, '-')) ?: 'partner';
    $chkSlug = $pdo->prepare("SELECT id FROM {$pfx}mkt_partners WHERE slug=?"); $chkSlug->execute([$slug]);
    if ($chkSlug->fetchColumn()) $slug .= '-' . substr(md5($email), 0, 6);

    $autoApprove = $pdo->prepare("SELECT value FROM {$pfx}mkt_settings WHERE `key`='auto_approve_partners'");
    $autoApprove->execute(); $autoApprove = $autoApprove->fetchColumn();
    $status = ($autoApprove === '1') ? 'active' : 'pending';

    $defaultRate = $pdo->prepare("SELECT value FROM {$pfx}mkt_settings WHERE `key`='default_commission_rate'");
    $defaultRate->execute(); $defaultRate = (float)($defaultRate->fetchColumn() ?: 30);

    $hash = password_hash($password, PASSWORD_BCRYPT);
    $pdo->prepare("INSERT INTO {$pfx}mkt_partners (email,password_hash,display_name,slug,commission_rate,status,website_url,bio) VALUES (?,?,?,?,?,?,?,?)")
        ->execute([$email,$hash,$name,$slug,$defaultRate,$status,
            ($_POST['website_url']??'')?:null,
            ($_POST['bio']??'')?:null,
        ]);
    $partnerId = (int)$pdo->lastInsertId();
    echo json_encode(['ok'=>true,'msg'=>$status==='active'?'등록 완료':'가입 신청이 접수되었습니다. 심사 후 활성화됩니다.','partner_id'=>$partnerId,'status'=>$status], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500); echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
