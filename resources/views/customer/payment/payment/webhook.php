<?php
/**
 * Stripe Webhook 수신
 * 결제 완료 이벤트를 서버에서 직접 확인 (콜백 실패 대비)
 */
header('Content-Type: application/json');

require_once BASE_PATH . '/rzxlib/Modules/Payment/PaymentManager.php';
require_once BASE_PATH . '/rzxlib/Modules/Payment/Services/PaymentService.php';
require_once BASE_PATH . '/rzxlib/Modules/Payment/DTO/PaymentRequest.php';
require_once BASE_PATH . '/rzxlib/Modules/Payment/Gateways/StripeGateway.php';
require_once BASE_PATH . '/rzxlib/Modules/Payment/Contracts/PaymentGatewayInterface.php';
require_once BASE_PATH . '/rzxlib/Modules/Payment/DTO/PaymentResult.php';
require_once BASE_PATH . '/rzxlib/Modules/Payment/DTO/RefundResult.php';

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$payload = file_get_contents('php://input');
$event = json_decode($payload, true);

if (!$event || empty($event['type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

error_log('[Webhook] Received: ' . $event['type']);

try {
    $manager = new \RzxLib\Modules\Payment\PaymentManager($pdo, $prefix);
    $service = new \RzxLib\Modules\Payment\Services\PaymentService($manager, $pdo, $prefix);

    switch ($event['type']) {
        case 'checkout.session.completed':
            $session = $event['data']['object'] ?? [];
            $sessionId = $session['id'] ?? '';
            if ($sessionId && ($session['payment_status'] ?? '') === 'paid') {
                $service->confirm($sessionId);
                error_log('[Webhook] Payment confirmed: ' . $sessionId);
            }
            break;

        case 'charge.refunded':
            $charge = $event['data']['object'] ?? [];
            error_log('[Webhook] Refund received: ' . ($charge['id'] ?? ''));
            break;

        default:
            error_log('[Webhook] Unhandled event: ' . $event['type']);
    }

    echo json_encode(['received' => true]);
} catch (\Throwable $e) {
    error_log('[Webhook] Error: ' . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
}
exit;
