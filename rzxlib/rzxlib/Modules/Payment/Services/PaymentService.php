<?php
namespace RzxLib\Modules\Payment\Services;

use RzxLib\Modules\Payment\PaymentManager;
use RzxLib\Modules\Payment\DTO\PaymentRequest;
use RzxLib\Modules\Payment\DTO\PaymentResult;

/**
 * 결제 처리 서비스
 * 예약 → 결제 생성 → PG 연동 → 상태 업데이트
 */
class PaymentService
{
    private PaymentManager $manager;
    private \PDO $pdo;
    private string $prefix;

    public function __construct(PaymentManager $manager, \PDO $pdo, string $prefix = 'rzx_')
    {
        $this->manager = $manager;
        $this->pdo = $pdo;
        $this->prefix = $prefix;
    }

    /**
     * 주문번호 생성
     */
    public function generateOrderId(): string
    {
        return 'ORD-' . date('Ymd') . '-' . strtoupper(bin2hex(random_bytes(4)));
    }

    /**
     * 결제 준비: DB에 pending 레코드 생성 + PG 세션 생성
     */
    public function prepare(PaymentRequest $request): array
    {
        $uuid = sprintf('%s-%s-%s-%s-%s',
            bin2hex(random_bytes(4)), bin2hex(random_bytes(2)),
            bin2hex(random_bytes(2)), bin2hex(random_bytes(2)),
            bin2hex(random_bytes(6))
        );

        if (!$request->orderId) {
            $request->orderId = $this->generateOrderId();
        }

        // DB에 pending 레코드 생성
        $stmt = $this->pdo->prepare("INSERT INTO {$this->prefix}payments
            (uuid, reservation_id, user_id, order_id, gateway, amount, discount_amount, point_amount, status, metadata)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
        $stmt->execute([
            $uuid,
            $request->reservationId,
            $request->userId,
            $request->orderId,
            $this->manager->getGatewayName(),
            $request->amount,
            0, // discount
            0, // points
            json_encode($request->metadata, JSON_UNESCAPED_UNICODE),
        ]);
        $paymentId = (int)$this->pdo->lastInsertId();

        // PG 세션 생성
        $gateway = $this->manager->gateway();
        $session = $gateway->createCheckoutSession($request);

        // payment_key 저장
        if (!empty($session['payment_key'])) {
            $this->pdo->prepare("UPDATE {$this->prefix}payments SET payment_key = ? WHERE id = ?")
                ->execute([$session['payment_key'], $paymentId]);
        }

        return array_merge($session, [
            'payment_id' => $paymentId,
            'payment_uuid' => $uuid,
            'order_id' => $request->orderId,
        ]);
    }

    /**
     * 결제 승인 확인 (Webhook 또는 콜백에서 호출)
     */
    public function confirm(string $sessionId): PaymentResult
    {
        $gateway = $this->manager->gateway();
        $result = $gateway->confirm($sessionId);

        if ($result->isSuccessful()) {
            // payments 상태 업데이트
            $stmt = $this->pdo->prepare("UPDATE {$this->prefix}payments
                SET status = 'paid', paid_at = NOW(), method = ?, method_detail = ?,
                    receipt_url = ?, raw_response = ?, payment_key = COALESCE(payment_key, ?)
                WHERE payment_key = ? OR order_id = ?");
            $stmt->execute([
                $result->method,
                $result->methodDetail ? json_encode($result->methodDetail) : null,
                $result->receiptUrl,
                json_encode($result->raw, JSON_UNESCAPED_UNICODE),
                $result->paymentKey,
                $result->paymentKey ?? $sessionId,
                $result->raw['metadata']['order_id'] ?? $sessionId,
            ]);

            // 연결된 예약 상태 변경 (BR-031)
            $payStmt = $this->pdo->prepare("SELECT id, reservation_id FROM {$this->prefix}payments WHERE payment_key = ? OR order_id = ? LIMIT 1");
            $payStmt->execute([$result->paymentKey ?? $sessionId, $result->raw['metadata']['order_id'] ?? $sessionId]);
            $payment = $payStmt->fetch(\PDO::FETCH_ASSOC);

            if ($payment && $payment['reservation_id']) {
                // 예약금 결제인지 확인: 결제 금액 < final_amount → partial
                $resStmt = $this->pdo->prepare("SELECT final_amount FROM {$this->prefix}reservations WHERE id = ?");
                $resStmt->execute([$payment['reservation_id']]);
                $resFinal = (float)($resStmt->fetchColumn() ?: 0);
                $payStatus = ($result->amount >= $resFinal) ? 'paid' : 'partial';

                $this->pdo->prepare("UPDATE {$this->prefix}reservations SET status = 'confirmed', payment_status = ?, paid_amount = paid_amount + ?, payment_id = ? WHERE id = ?")
                    ->execute([$payStatus, $result->amount, $payment['id'], $payment['reservation_id']]);
            }
        } else {
            // 실패 기록
            $this->pdo->prepare("UPDATE {$this->prefix}payments
                SET status = 'failed', failure_code = ?, failure_message = ?, raw_response = ?
                WHERE payment_key = ?")
                ->execute([
                    $result->failureCode,
                    $result->failureMessage,
                    json_encode($result->raw, JSON_UNESCAPED_UNICODE),
                    $result->paymentKey ?? $sessionId,
                ]);
        }

        return $result;
    }

    /**
     * 결제 ID로 조회
     */
    public function findByReservation(string $reservationId): ?array
    {
        $stmt = $this->pdo->prepare("SELECT * FROM {$this->prefix}payments WHERE reservation_id = ? ORDER BY created_at DESC LIMIT 1");
        $stmt->execute([$reservationId]);
        return $stmt->fetch(\PDO::FETCH_ASSOC) ?: null;
    }
}
