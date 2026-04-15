#!/usr/bin/env php
<?php
/**
 * 구독 자동 갱신 — 매일 실행
 *
 * crontab: 0 9 * * * /usr/bin/php /var/www/voscms/scripts/cron-subscription-renew.php >> /var/www/voscms/storage/logs/cron-subscription.log 2>&1
 *
 * 1. 만기 7일 전: 안내 메일 발송
 * 2. 만기일 당일: 자동 결제 (auto_renew=1)
 * 3. 결제 실패 시 3일 후 재시도 (최대 3회)
 * 4. 최종 실패: 서비스 정지
 */
date_default_timezone_set('Asia/Tokyo');
$start = microtime(true);
$log = function($msg) { echo '[' . date('Y-m-d H:i:s') . '] ' . $msg . PHP_EOL; };

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

require_once BASE_PATH . '/vendor/autoload.php';

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    $log('DB 연결 실패: ' . $e->getMessage());
    exit(1);
}

$log('===== 구독 자동 갱신 시작 =====');

$today = date('Y-m-d');
$in7days = date('Y-m-d', strtotime('+7 days'));

// ===== 1. 만기 7일 전 안내 메일 =====
$log('--- 만기 안내 메일 확인 ---');
$notifyStmt = $pdo->prepare("
    SELECT s.*, o.order_number, u.email as user_email, u.name as user_name
    FROM {$prefix}subscriptions s
    JOIN {$prefix}orders o ON s.order_id = o.id
    JOIN {$prefix}users u ON s.user_id = u.id
    WHERE s.status = 'active'
      AND s.auto_renew = 1
      AND DATE(s.expires_at) = ?
      AND (s.renew_notified_at IS NULL OR DATE(s.renew_notified_at) < ?)
");
$notifyStmt->execute([$in7days, $today]);
$notifyRows = $notifyStmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($notifyRows as $sub) {
    $log("안내 메일: {$sub['label']} (user: {$sub['user_email']}, expires: {$sub['expires_at']})");

    // TODO: 메일 발송 (메일 시스템 구현 후 연동)
    // sendRenewalNotification($sub);

    $pdo->prepare("UPDATE {$prefix}subscriptions SET renew_notified_at = NOW() WHERE id = ?")
        ->execute([$sub['id']]);
}
$log("안내 메일 대상: " . count($notifyRows) . "건");

// ===== 2. 만기일 당일 자동 결제 =====
$log('--- 자동 갱신 결제 ---');
$renewStmt = $pdo->prepare("
    SELECT s.*, o.order_number, u.email as user_email, u.name as user_name
    FROM {$prefix}subscriptions s
    JOIN {$prefix}orders o ON s.order_id = o.id
    JOIN {$prefix}users u ON s.user_id = u.id
    WHERE s.status = 'active'
      AND s.auto_renew = 1
      AND s.payment_customer_id IS NOT NULL
      AND DATE(s.expires_at) <= ?
      AND s.retry_count < 3
");
$renewStmt->execute([$today]);
$renewRows = $renewStmt->fetchAll(PDO::FETCH_ASSOC);

$payMgr = new \RzxLib\Modules\Payment\PaymentManager($pdo, $prefix);
$renewed = 0;
$failed = 0;

foreach ($renewRows as $sub) {
    $log("갱신 시도: {$sub['label']} (user: {$sub['user_email']}, customer: {$sub['payment_customer_id']})");

    try {
        $gateway = $payMgr->gateway($sub['payment_gateway'] ?? null);

        // Customer 카드로 결제
        if (!method_exists($gateway, 'chargeCustomer')) {
            $log("  → 건너뜀: {$sub['payment_gateway']}에 chargeCustomer 미지원");
            continue;
        }

        $amount = (int)$sub['billing_amount'];
        $description = "VosCMS 구독 갱신: {$sub['label']}";
        $result = $gateway->chargeCustomer($sub['payment_customer_id'], $amount, $sub['currency'], $description);

        if ($result->isSuccessful()) {
            // 성공: 기간 연장
            $newExpires = date('Y-m-d H:i:s', strtotime($sub['expires_at'] . " +{$sub['billing_months']} months"));
            $pdo->prepare("UPDATE {$prefix}subscriptions SET
                expires_at = ?, next_billing_at = ?, retry_count = 0, renew_notified_at = NULL, updated_at = NOW()
                WHERE id = ?")
                ->execute([$newExpires, $newExpires, $sub['id']]);

            // 결제 기록
            $payUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
            $pdo->prepare("INSERT INTO {$prefix}payments (uuid, user_id, order_id, payment_key, gateway, method, amount, status, paid_at)
                VALUES (?, ?, ?, ?, ?, 'card', ?, 'paid', NOW())")
                ->execute([$payUuid, $sub['user_id'], $sub['order_id'], $result->paymentKey, $sub['payment_gateway'], $amount]);

            // 주문 로그
            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type) VALUES (?, 'renewed', ?, 'cron')")
                ->execute([$sub['order_id'], json_encode(['subscription_id' => $sub['id'], 'amount' => $amount, 'new_expires' => $newExpires])]);

            $log("  → 성공: ¥{$amount}, 새 만기: {$newExpires}");
            $renewed++;

            // TODO: 갱신 성공 메일 발송

        } else {
            // 실패: 재시도 카운트 증가
            $retryCount = (int)$sub['retry_count'] + 1;
            $pdo->prepare("UPDATE {$prefix}subscriptions SET retry_count = ?, last_retry_at = NOW() WHERE id = ?")
                ->execute([$retryCount, $sub['id']]);

            $log("  → 실패 (시도 {$retryCount}/3): " . ($result->failureMessage ?? 'unknown'));
            $failed++;

            // 3회 실패: 서비스 정지
            if ($retryCount >= 3) {
                $pdo->prepare("UPDATE {$prefix}subscriptions SET status = 'suspended', suspended_at = NOW() WHERE id = ?")
                    ->execute([$sub['id']]);

                $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type) VALUES (?, 'suspended', ?, 'cron')")
                    ->execute([$sub['order_id'], json_encode(['subscription_id' => $sub['id'], 'reason' => '결제 3회 실패'])]);

                $log("  → 서비스 정지: 결제 3회 실패");

                // TODO: 서비스 정지 안내 메일 발송
            }
        }
    } catch (\Throwable $e) {
        $log("  → 에러: " . $e->getMessage());
        $failed++;
    }
}

// ===== 3. auto_renew=0인 만기 구독 만료 처리 =====
$expireStmt = $pdo->prepare("
    UPDATE {$prefix}subscriptions
    SET status = 'expired', updated_at = NOW()
    WHERE status = 'active' AND auto_renew = 0 AND DATE(expires_at) < ?
");
$expireStmt->execute([$today]);
$expired = $expireStmt->rowCount();

$elapsed = round(microtime(true) - $start, 2);
$log("갱신: {$renewed}건 성공, {$failed}건 실패, {$expired}건 만료 처리");
$log("===== 완료 ({$elapsed}s) =====\n");
