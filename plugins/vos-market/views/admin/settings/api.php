<?php
header('Content-Type: application/json; charset=utf-8');
$token = $_POST['_token'] ?? '';
if (!isset($_SESSION['_csrf']) || !hash_equals($_SESSION['_csrf'], $token)) {
    http_response_code(403); echo json_encode(['ok'=>false,'msg'=>'CSRF error']); exit;
}
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
    );
    $pfx = $_ENV['DB_PREFIX'] ?? 'rzx_';

    $keys = ['market_name','default_currency','default_commission_rate','partner_portal_url','max_upload_mb'];
    $checkboxKeys = ['partner_registration_open','auto_approve_partners','require_license_for_download'];

    $stmt = $pdo->prepare("INSERT INTO {$pfx}mkt_settings (`key`,`value`) VALUES (?,?) ON DUPLICATE KEY UPDATE `value`=VALUES(`value`),updated_at=NOW()");
    foreach ($keys as $k) {
        $v = trim($_POST[$k] ?? '');
        $stmt->execute([$k, $v]);
    }
    foreach ($checkboxKeys as $k) {
        $stmt->execute([$k, isset($_POST[$k]) ? '1' : '0']);
    }
    echo json_encode(['ok'=>true,'msg'=>'저장 완료']);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
