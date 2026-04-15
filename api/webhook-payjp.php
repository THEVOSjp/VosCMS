<?php
/**
 * PAY.JP Webhook 수신 엔드포인트
 *
 * PAY.JP 대시보드에서 Webhook URL 설정:
 *   https://yourdomain.com/api/webhook-payjp.php
 *
 * 처리 이벤트:
 *   charge.succeeded   — 결제 성공 (첫 결제 + 정기결제 갱신)
 *   charge.failed      — 결제 실패
 *   subscription.renewed — 구독 갱신
 *   subscription.paused  — 결제 실패로 일시정지
 *   subscription.canceled — 구독 해지
 */
if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));

// .env 로드
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') !== false) {
            [$k, $v] = explode('=', $line, 2);
            $_ENV[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
        }
    }
}

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

// Webhook 토큰 검증 (PAY.JP 발신 확인)
// DB payment_config 또는 .env에서 토큰 로드
$webhookToken = $_ENV['PAYJP_WEBHOOK_TOKEN'] ?? '';
if (!$webhookToken) {
    try {
        $_tmpPdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4", $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
        $_tmpStmt = $_tmpPdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key`='payment_config'");
        $_tmpStmt->execute();
        $_tmpConf = json_decode($_tmpStmt->fetchColumn() ?: '{}', true) ?: [];
        $_tmpGw = $_tmpConf['gateway'] ?? 'payjp';
        $webhookToken = $_tmpConf['gateways'][$_tmpGw]['webhook_token'] ?? '';
    } catch (\Throwable $e) {}
}
if ($webhookToken) {
    $headerToken = $_SERVER['HTTP_X_PAYJP_WEBHOOK_TOKEN'] ?? '';
    if ($headerToken !== $webhookToken) {
        http_response_code(403);
        error_log("[Webhook] Invalid token");
        echo json_encode(['error' => 'Invalid webhook token']);
        exit;
    }
}

$input = file_get_contents('php://input');
$event = json_decode($input, true);

if (!$event || empty($event['type'])) {
    http_response_code(400);
    echo json_encode(['error' => 'Invalid payload']);
    exit;
}

// DB 연결
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    error_log("[Webhook] DB error: " . $e->getMessage());
    exit;
}

$type = $event['type'];
$data = $event['data'] ?? [];
$logMsg = date('Y-m-d H:i:s') . " [{$type}] " . json_encode($data, JSON_UNESCAPED_UNICODE);
@file_put_contents(BASE_PATH . '/storage/logs/webhook-payjp.log', $logMsg . "\n", FILE_APPEND);

try {
    switch ($type) {
        case 'charge.succeeded':
            $chargeId = $data['id'] ?? '';
            $amount = (int)($data['amount'] ?? 0);
            $customerId = $data['customer'] ?? '';
            $orderId = $data['metadata']['order_id'] ?? '';

            if ($orderId) {
                // 결제 성공 → 주문 상태 업데이트
                $pdo->prepare("UPDATE {$prefix}orders SET status='paid', started_at=NOW(), expires_at=DATE_ADD(NOW(), INTERVAL contract_months MONTH) WHERE order_number=? AND status='pending'")
                    ->execute([$orderId]);

                // 구독 활성화
                $pdo->prepare("UPDATE {$prefix}subscriptions SET status='active' WHERE order_id=(SELECT id FROM {$prefix}orders WHERE order_number=? LIMIT 1) AND status='pending'")
                    ->execute([$orderId]);

                // 로그
                $oid = $pdo->prepare("SELECT id FROM {$prefix}orders WHERE order_number=? LIMIT 1");
                $oid->execute([$orderId]);
                $orderDbId = $oid->fetchColumn();
                if ($orderDbId) {
                    $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type) VALUES (?, 'webhook_paid', ?, 'system')")
                        ->execute([$orderDbId, json_encode(['charge_id' => $chargeId, 'amount' => $amount])]);
                }
            }

            // 구독 갱신 결제 (order_id 없는 경우 — PAY.JP Subscription 자동 과금)
            if ($customerId && !$orderId) {
                $sub = $pdo->prepare("SELECT * FROM {$prefix}subscriptions WHERE payment_customer_id=? AND status='active' LIMIT 1");
                $sub->execute([$customerId]);
                $subRow = $sub->fetch(PDO::FETCH_ASSOC);
                if ($subRow) {
                    $newExpires = date('Y-m-d H:i:s', strtotime($subRow['expires_at'] . " +{$subRow['billing_months']} months"));
                    $pdo->prepare("UPDATE {$prefix}subscriptions SET expires_at=?, next_billing_at=?, retry_count=0, renew_notified_at=NULL WHERE id=?")
                        ->execute([$newExpires, $newExpires, $subRow['id']]);

                    $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type) VALUES (?, 'renewed_webhook', ?, 'system')")
                        ->execute([$subRow['order_id'], json_encode(['charge_id' => $chargeId, 'amount' => $amount, 'new_expires' => $newExpires])]);
                }
            }
            break;

        case 'charge.failed':
            $customerId = $data['customer'] ?? '';
            $orderId = $data['metadata']['order_id'] ?? '';
            $failCode = $data['failure_code'] ?? '';
            $failMsg = $data['failure_message'] ?? '';

            if ($orderId) {
                $pdo->prepare("UPDATE {$prefix}orders SET status='failed' WHERE order_number=? AND status='pending'")
                    ->execute([$orderId]);
            }

            if ($customerId) {
                $pdo->prepare("UPDATE {$prefix}subscriptions SET retry_count=retry_count+1, last_retry_at=NOW() WHERE payment_customer_id=? AND status='active'")
                    ->execute([$customerId]);
            }
            break;

        case 'subscription.paused':
            $subPayjpId = $data['id'] ?? '';
            $customerId = $data['customer'] ?? '';
            if ($customerId) {
                $pdo->prepare("UPDATE {$prefix}subscriptions SET status='suspended', suspended_at=NOW() WHERE payment_customer_id=? AND status='active'")
                    ->execute([$customerId]);
            }
            break;

        case 'subscription.canceled':
            $customerId = $data['customer'] ?? '';
            if ($customerId) {
                $pdo->prepare("UPDATE {$prefix}subscriptions SET status='cancelled', cancelled_at=NOW() WHERE payment_customer_id=? AND status IN ('active','suspended')")
                    ->execute([$customerId]);
            }
            break;

        case 'subscription.renewed':
            // charge.succeeded에서 처리됨
            break;
    }
} catch (\Throwable $e) {
    error_log("[Webhook] Error: " . $e->getMessage());
    http_response_code(500);
    echo json_encode(['error' => $e->getMessage()]);
    exit;
}

// PAY.JP는 200 응답을 기대
http_response_code(200);
echo json_encode(['received' => true]);
