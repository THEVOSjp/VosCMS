<?php
namespace RzxLib\Modules\Payment\Gateways;

use RzxLib\Modules\Payment\Contracts\PaymentGatewayInterface;
use RzxLib\Modules\Payment\DTO\PaymentRequest;
use RzxLib\Modules\Payment\DTO\PaymentResult;
use RzxLib\Modules\Payment\DTO\RefundResult;

/**
 * PAY.JP 결제 게이트웨이
 * https://pay.jp/docs/api
 *
 * 일본 특화 결제: 카드, コンビニ決済
 * REST API 직접 호출 (SDK 미사용)
 */
class PayjpGateway implements PaymentGatewayInterface
{
    private string $secretKey;
    private string $publicKey;
    private string $apiBase = 'https://api.pay.jp/v1';

    public function __construct(string $secretKey, string $publicKey)
    {
        $this->secretKey = $secretKey;
        $this->publicKey = $publicKey;
    }

    public function getName(): string { return 'payjp'; }
    public function getSupportedCountries(): array { return ['JP']; }

    /**
     * PAY.JP 결제 토큰 기반 Checkout
     *
     * PAY.JP는 Stripe와 달리 Checkout Session이 없으므로:
     * 1. 프론트에서 payjp.js로 카드 토큰(tok_xxx) 생성
     * 2. 서버에서 토큰으로 Charge 생성
     *
     * createCheckoutSession은 프론트에 필요한 정보(public_key 등)를 반환
     */
    public function createCheckoutSession(PaymentRequest $request): array
    {
        return [
            'gateway' => 'payjp',
            'public_key' => $this->publicKey,
            'amount' => $request->amount,
            'currency' => $request->currency,
            'order_id' => $request->orderId,
            'description' => $request->description,
            'customer_email' => $request->customerEmail ?? '',
            'metadata' => $request->metadata,
            // 프론트에서 payjp.js Checkout을 사용하여 토큰 생성 후
            // confirm()에 토큰을 전달하여 결제 완료
        ];
    }

    /**
     * 카드 토큰으로 결제 (Charge 생성)
     *
     * @param string $token payjp.js에서 생성한 카드 토큰 (tok_xxx)
     * @return PaymentResult
     */
    public function confirm(string $token): PaymentResult
    {
        // 토큰에서 order_id 등 메타데이터를 분리
        // confirm 호출 시 $token은 "tok_xxx|amount|currency|orderId|description" 형식
        // 또는 별도 파라미터로 전달
        $parts = explode('|', $token);
        $cardToken = $parts[0];
        $amount = (int)($parts[1] ?? 0);
        $currency = $parts[2] ?? 'jpy';
        $orderId = $parts[3] ?? '';
        $description = $parts[4] ?? '';

        if (!$cardToken || !$amount) {
            return new PaymentResult([
                'success' => false,
                'status' => 'failed',
                'failure_code' => 'invalid_token',
                'failure_message' => 'Card token and amount are required',
            ]);
        }

        $params = [
            'amount' => $amount,
            'currency' => strtolower($currency),
            'card' => $cardToken,
            'capture' => 'true',
            'description' => $description ?: 'RezlyX Order: ' . $orderId,
        ];

        if ($orderId) {
            $params['metadata[order_id]'] = $orderId;
        }

        $response = $this->apiCall('POST', '/charges', $params);

        if (!empty($response['error'])) {
            return new PaymentResult([
                'success' => false,
                'status' => 'failed',
                'failure_code' => $response['error']['code'] ?? 'unknown',
                'failure_message' => $response['error']['message'] ?? 'Payment failed',
                'raw' => $response,
            ]);
        }

        $paid = ($response['paid'] ?? false) && ($response['captured'] ?? false);

        return new PaymentResult([
            'success' => $paid,
            'status' => $paid ? 'paid' : 'pending',
            'payment_key' => $response['id'] ?? null,
            'transaction_id' => $response['id'] ?? null,
            'amount' => (int)($response['amount'] ?? 0),
            'method' => 'card',
            'method_detail' => [
                'card_brand' => $response['card']['brand'] ?? null,
                'card_last4' => $response['card']['last4'] ?? null,
                'card_exp_month' => $response['card']['exp_month'] ?? null,
                'card_exp_year' => $response['card']['exp_year'] ?? null,
            ],
            'receipt_url' => null, // PAY.JP는 별도 영수증 URL 없음
            'raw' => $response,
        ]);
    }

    /**
     * 결제 취소 (Charge Refund — 전액)
     */
    public function cancel(string $chargeId, string $reason = ''): bool
    {
        $params = [];
        if ($reason) {
            $params['metadata[cancel_reason]'] = $reason;
        }

        $response = $this->apiCall('POST', "/charges/{$chargeId}/refund", $params);

        return !empty($response['refunded']) && $response['refunded'] === true;
    }

    /**
     * 환불 (부분 환불 지원)
     */
    public function refund(string $chargeId, int $amount, string $reason = ''): RefundResult
    {
        // PAY.JP refund API 는 amount + refund_reason 만 지원 (metadata 미지원)
        $params = ['amount' => $amount];
        if ($reason) {
            $params['refund_reason'] = mb_substr($reason, 0, 255);
        }

        $response = $this->apiCall('POST', "/charges/{$chargeId}/refund", $params);

        if (!empty($response['error'])) {
            return new RefundResult([
                'success' => false,
                'failure_reason' => $response['error']['message'] ?? 'Refund failed',
                'raw' => $response,
            ]);
        }

        return new RefundResult([
            'success' => ($response['refunded'] ?? false),
            'refund_id' => $response['id'] ?? null,
            'amount' => $amount,
            'status' => ($response['refunded'] ?? false) ? 'refunded' : 'failed',
            'raw' => $response,
        ]);
    }

    /**
     * 거래 조회
     */
    public function getTransaction(string $chargeId): ?array
    {
        $response = $this->apiCall('GET', "/charges/{$chargeId}");

        if (!empty($response['error'])) {
            return null;
        }

        return [
            'id' => $response['id'] ?? null,
            'amount' => (int)($response['amount'] ?? 0),
            'currency' => $response['currency'] ?? 'jpy',
            'paid' => $response['paid'] ?? false,
            'captured' => $response['captured'] ?? false,
            'refunded' => $response['refunded'] ?? false,
            'amount_refunded' => (int)($response['amount_refunded'] ?? 0),
            'card' => $response['card'] ?? [],
            'description' => $response['description'] ?? '',
            'metadata' => $response['metadata'] ?? [],
            'created' => $response['created'] ?? null,
            'raw' => $response,
        ];
    }

    // ===== 헬퍼 =====

    /**
     * PAY.JP에서 사용 가능한 결제 수단 조회
     */
    public function getPaymentMethods(): array
    {
        return [
            ['method' => 'card', 'label' => 'クレジットカード', 'label_en' => 'Credit Card'],
        ];
    }

    /**
     * Public Key 반환 (프론트엔드 payjp.js 초기화용)
     */
    public function getPublicKey(): string
    {
        return $this->publicKey;
    }

    /**
     * Charge에 3D Secure 적용 여부 확인
     */
    public function createChargeWith3DS(PaymentRequest $request, string $cardToken): PaymentResult
    {
        $params = [
            'amount' => $request->amount,
            'currency' => strtolower($request->currency),
            'card' => $cardToken,
            'capture' => 'true',
            'three_d_secure' => 'true',
            'description' => $request->description ?: 'RezlyX Order: ' . $request->orderId,
        ];

        if ($request->orderId) {
            $params['metadata[order_id]'] = $request->orderId;
        }

        $response = $this->apiCall('POST', '/charges', $params);

        if (!empty($response['error'])) {
            return new PaymentResult([
                'success' => false,
                'status' => 'failed',
                'failure_code' => $response['error']['code'] ?? 'unknown',
                'failure_message' => $response['error']['message'] ?? '3DS payment failed',
                'raw' => $response,
            ]);
        }

        // 3D Secure 인증 필요 시 redirect URL 반환
        if (!empty($response['three_d_secure_status']) && $response['three_d_secure_status'] === 'attempt') {
            return new PaymentResult([
                'success' => false,
                'status' => '3ds_required',
                'payment_key' => $response['id'] ?? null,
                'raw' => $response,
            ]);
        }

        $paid = ($response['paid'] ?? false) && ($response['captured'] ?? false);

        return new PaymentResult([
            'success' => $paid,
            'status' => $paid ? 'paid' : 'pending',
            'payment_key' => $response['id'] ?? null,
            'transaction_id' => $response['id'] ?? null,
            'amount' => (int)($response['amount'] ?? 0),
            'method' => 'card',
            'method_detail' => [
                'card_brand' => $response['card']['brand'] ?? null,
                'card_last4' => $response['card']['last4'] ?? null,
                'three_d_secure' => $response['three_d_secure_status'] ?? null,
            ],
            'raw' => $response,
        ]);
    }

    // ===== Customer (구독 결제용) =====

    /**
     * Customer 생성 — 카드 토큰을 고객에 연결하여 재결제 가능
     */
    public function createCustomer(string $cardToken, string $email, array $metadata = []): array
    {
        $params = [
            'card' => $cardToken,
            'email' => $email,
        ];
        foreach ($metadata as $k => $v) {
            $params["metadata[{$k}]"] = $v;
        }

        $response = $this->apiCall('POST', '/customers', $params);

        if (!empty($response['error'])) {
            return ['success' => false, 'message' => $response['error']['message'] ?? 'Customer creation failed'];
        }

        return [
            'success' => true,
            'customer_id' => $response['id'] ?? null,
            'card' => [
                'brand' => $response['cards']['data'][0]['brand'] ?? null,
                'last4' => $response['cards']['data'][0]['last4'] ?? null,
            ],
        ];
    }

    /**
     * Customer의 저장된 카드로 결제
     */
    public function chargeCustomer(string $customerId, int $amount, string $currency = 'jpy', string $description = ''): PaymentResult
    {
        $params = [
            'amount' => $amount,
            'currency' => strtolower($currency),
            'customer' => $customerId,
            'capture' => 'true',
        ];
        if ($description) $params['description'] = $description;

        $response = $this->apiCall('POST', '/charges', $params);

        if (!empty($response['error'])) {
            return new PaymentResult([
                'success' => false,
                'status' => 'failed',
                'failure_code' => $response['error']['code'] ?? 'unknown',
                'failure_message' => $response['error']['message'] ?? 'Charge failed',
                'raw' => $response,
            ]);
        }

        $paid = ($response['paid'] ?? false) && ($response['captured'] ?? false);

        return new PaymentResult([
            'success' => $paid,
            'status' => $paid ? 'paid' : 'failed',
            'payment_key' => $response['id'] ?? null,
            'transaction_id' => $response['id'] ?? null,
            'amount' => (int)($response['amount'] ?? 0),
            'method' => 'card',
            'method_detail' => [
                'card_brand' => $response['card']['brand'] ?? null,
                'card_last4' => $response['card']['last4'] ?? null,
            ],
            'raw' => $response,
        ]);
    }

    /**
     * Customer 삭제
     */
    public function deleteCustomer(string $customerId): bool
    {
        $response = $this->apiCall('DELETE', "/customers/{$customerId}");
        return !empty($response['deleted']);
    }

    // ===== Plan & Subscription (定期課金) =====

    /**
     * Plan 생成 — 定期課金プラン
     */
    public function createPlan(string $id, int $amount, string $interval = 'month', string $name = '', string $currency = 'jpy'): array
    {
        $params = [
            'id' => $id,
            'amount' => $amount,
            'currency' => strtolower($currency),
            'interval' => $interval, // 'month' or 'year'
        ];
        if ($name) $params['name'] = $name;
        $response = $this->apiCall('POST', '/plans', $params);
        if (!empty($response['error'])) {
            // プラン既存の場合はOK
            if (($response['error']['code'] ?? '') === 'already_exists') {
                return ['success' => true, 'plan_id' => $id, 'exists' => true];
            }
            return ['success' => false, 'message' => $response['error']['message'] ?? 'Plan creation failed'];
        }
        return ['success' => true, 'plan_id' => $response['id'] ?? $id];
    }

    /**
     * Subscription 生成 — 定期課金開始
     */
    public function createSubscription(string $customerId, string $planId, ?int $trialEnd = null): array
    {
        $params = [
            'customer' => $customerId,
            'plan' => $planId,
        ];
        if ($trialEnd) $params['trial_end'] = $trialEnd;

        $response = $this->apiCall('POST', '/subscriptions', $params);
        if (!empty($response['error'])) {
            return ['success' => false, 'message' => $response['error']['message'] ?? 'Subscription creation failed'];
        }
        return [
            'success' => true,
            'subscription_id' => $response['id'] ?? null,
            'status' => $response['status'] ?? 'active',
            'current_period_end' => $response['current_period_end'] ?? null,
        ];
    }

    /**
     * Subscription 解約
     */
    public function cancelSubscription(string $subscriptionId): bool
    {
        $response = $this->apiCall('POST', "/subscriptions/{$subscriptionId}/cancel");
        return ($response['status'] ?? '') === 'canceled';
    }

    /**
     * Subscription 一時停止
     */
    public function pauseSubscription(string $subscriptionId): bool
    {
        $response = $this->apiCall('POST', "/subscriptions/{$subscriptionId}/pause");
        return ($response['status'] ?? '') === 'paused';
    }

    /**
     * Subscription 再開
     */
    public function resumeSubscription(string $subscriptionId): bool
    {
        $response = $this->apiCall('POST', "/subscriptions/{$subscriptionId}/resume");
        return ($response['status'] ?? '') === 'active';
    }

    /**
     * PAY.JP REST API 호출
     */
    private function apiCall(string $method, string $endpoint, array $params = []): array
    {
        $url = $this->apiBase . $endpoint;

        $ch = curl_init();
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_USERPWD, $this->secretKey . ':');
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Accept: application/json',
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        } elseif ($method === 'GET' && !empty($params)) {
            $url .= '?' . http_build_query($params);
        } elseif ($method === 'DELETE') {
            curl_setopt($ch, CURLOPT_CUSTOMREQUEST, 'DELETE');
        }

        curl_setopt($ch, CURLOPT_URL, $url);
        curl_setopt($ch, CURLOPT_TIMEOUT, 30);
        curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 10);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        if (curl_errno($ch)) {
            $error = curl_error($ch);
            curl_close($ch);
            error_log("[PAY.JP] cURL error: {$error}");
            return ['error' => ['code' => 'curl_error', 'message' => $error]];
        }

        curl_close($ch);

        $decoded = json_decode($response, true);
        if ($decoded === null) {
            error_log("[PAY.JP] Invalid JSON response (HTTP {$httpCode}): " . substr($response, 0, 500));
            return ['error' => ['code' => 'invalid_response', 'message' => 'Invalid JSON response']];
        }

        if ($httpCode >= 400) {
            error_log("[PAY.JP] API error (HTTP {$httpCode}): " . json_encode($decoded));
        }

        return $decoded;
    }
}
