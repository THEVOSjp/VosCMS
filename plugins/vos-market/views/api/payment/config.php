<?php
/**
 * GET /api/market/payment/config
 * 클라이언트(VosCMS)가 결제 폼 초기화에 필요한 공개 정보만 반환.
 * secret_key, webhook_token 등 민감 정보는 절대 노출하지 않는다.
 *
 * 응답: { ok, enabled, gateway, public_key, test_mode }
 */
header('Content-Type: application/json; charset=utf-8');
try {
    $pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $pfx = $_ENV['DB_PREFIX'] ?? 'rzx_';

    require_once __DIR__ . '/../../../../../rzxlib/Modules/Payment/PaymentManager.php';
    require_once __DIR__ . '/../../../../../rzxlib/Modules/Payment/Contracts/PaymentGatewayInterface.php';
    require_once __DIR__ . '/../../../../../rzxlib/Modules/Payment/Gateways/StripeGateway.php';
    require_once __DIR__ . '/../../../../../rzxlib/Modules/Payment/Gateways/PayjpGateway.php';

    $pm = new \RzxLib\Modules\Payment\PaymentManager($pdo, $pfx);
    $cfg = $pm->getConfig();

    // 현재 활성 게이트웨이의 public key 추출
    $gateway   = $cfg['gateway'] ?? 'payjp';
    $gateways  = $cfg['gateways'] ?? [];
    $gwConf    = $gateways[$gateway] ?? [];
    $publicKey = $gwConf['public_key'] ?? $cfg['public_key'] ?? '';
    $secretKey = $gwConf['secret_key'] ?? $cfg['secret_key'] ?? '';
    $testMode  = ($gwConf['test_mode'] ?? $cfg['test_mode'] ?? '1') === '1';
    // 활성 판정: 전역 enabled=1 + 현재 게이트웨이의 공개키·비밀키 둘 다 존재
    $enabled   = ($cfg['enabled'] ?? '0') === '1' && !empty($publicKey) && !empty($secretKey);

    echo json_encode([
        'ok'         => true,
        'enabled'    => $enabled,
        'gateway'    => $gateway,
        'public_key' => $publicKey,
        'test_mode'  => $testMode,
    ]);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
