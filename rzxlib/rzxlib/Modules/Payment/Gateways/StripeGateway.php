<?php
namespace RzxLib\Modules\Payment\Gateways;

use RzxLib\Modules\Payment\Contracts\PaymentGatewayInterface;
use RzxLib\Modules\Payment\DTO\PaymentRequest;
use RzxLib\Modules\Payment\DTO\PaymentResult;
use RzxLib\Modules\Payment\DTO\RefundResult;

/**
 * Stripe 결제 게이트웨이
 * Stripe Checkout Session 방식 사용 (서버 SDK 없이 REST API 직접 호출)
 */
class StripeGateway implements PaymentGatewayInterface
{
    private string $secretKey;
    private string $publicKey;
    private string $apiBase = 'https://api.stripe.com/v1';

    public function __construct(string $secretKey, string $publicKey)
    {
        $this->secretKey = $secretKey;
        $this->publicKey = $publicKey;
    }

    public function getName(): string { return 'stripe'; }
    public function getSupportedCountries(): array { return ['US','JP','KR','GB','DE','FR','AU','SG','NZ']; }

    /**
     * Stripe Checkout Session 생성
     */
    public function createCheckoutSession(PaymentRequest $request): array
    {
        // Embedded Checkout: ui_mode=embedded + return_url
        $params = [
            'mode' => 'payment',
            'ui_mode' => 'embedded',
            'return_url' => $request->successUrl . '?session_id={CHECKOUT_SESSION_ID}',
            'line_items[0][price_data][currency]' => $request->currency,
            'line_items[0][price_data][product_data][name]' => $request->description ?: 'Reservation',
            'line_items[0][price_data][unit_amount]' => $this->toMinorUnit($request->amount, $request->currency),
            'line_items[0][quantity]' => 1,
            'metadata[order_id]' => $request->orderId,
            'metadata[reservation_id]' => $request->reservationId ?? '',
        ];

        if ($request->customerEmail) {
            $params['customer_email'] = $request->customerEmail;
        }

        // Stripe 로케일 매핑
        $localeMap = ['ko'=>'ko','en'=>'en','ja'=>'ja','zh_CN'=>'zh','zh_TW'=>'zh-TW','de'=>'de','es'=>'es','fr'=>'fr','id'=>'id','ru'=>'ru','tr'=>'tr','vi'=>'vi'];
        $stripeLocale = $localeMap[$request->metadata['locale'] ?? ''] ?? 'auto';
        $params['locale'] = $stripeLocale;

        $response = $this->apiCall('POST', '/checkout/sessions', $params);

        if (empty($response['id'])) {
            $errMsg = $response['error']['message'] ?? json_encode($response);
            error_log('Stripe session failed: ' . $errMsg);

            // Embedded 실패 시 Redirect 방식으로 폴백
            unset($params['ui_mode'], $params['return_url']);
            $params['success_url'] = $request->successUrl . '?session_id={CHECKOUT_SESSION_ID}';
            $params['cancel_url'] = $request->cancelUrl;
            $response = $this->apiCall('POST', '/checkout/sessions', $params);

            if (empty($response['id'])) {
                throw new \RuntimeException('Stripe session creation failed: ' . ($response['error']['message'] ?? json_encode($response)));
            }
        }

        return [
            'session_id' => $response['id'],
            'payment_key' => $response['id'],
            'client_secret' => $response['client_secret'] ?? null,
            'checkout_url' => $response['url'] ?? null,
            'public_key' => $this->publicKey,
        ];
    }

    /**
     * Checkout Session 결과 확인
     */
    public function confirm(string $sessionId): PaymentResult
    {
        $session = $this->apiCall('GET', '/checkout/sessions/' . $sessionId);

        if (empty($session['id'])) {
            return new PaymentResult(['success' => false, 'failure_message' => 'Session not found']);
        }

        $isPaid = ($session['payment_status'] ?? '') === 'paid';

        // 결제 상세 조회
        $method = null;
        $methodDetail = null;
        $receiptUrl = null;
        if ($isPaid && !empty($session['payment_intent'])) {
            $pi = $this->apiCall('GET', '/payment_intents/' . $session['payment_intent'] . '?expand[]=latest_charge');
            // latest_charge 또는 charges.data[0] 에서 receipt_url 조회
            $charge = $pi['latest_charge'] ?? ($pi['charges']['data'][0] ?? null);
            if (is_string($charge)) {
                // expand되지 않은 경우 charge ID → 직접 조회
                $charge = $this->apiCall('GET', '/charges/' . $charge);
            }
            $receiptUrl = $charge['receipt_url'] ?? null;
            $pm = $pi['charges']['data'][0]['payment_method_details'] ?? [];
            $method = $pm['type'] ?? 'card';
            if ($method === 'card') {
                $card = $pm['card'] ?? [];
                $methodDetail = [
                    'brand' => $card['brand'] ?? '',
                    'last4' => $card['last4'] ?? '',
                    'exp_month' => $card['exp_month'] ?? '',
                    'exp_year' => $card['exp_year'] ?? '',
                ];
            }
        }

        return new PaymentResult([
            'success' => $isPaid,
            'status' => $isPaid ? 'paid' : ($session['status'] ?? 'failed'),
            'payment_key' => $sessionId,
            'transaction_id' => $session['payment_intent'] ?? null,
            'amount' => $this->fromMinorUnit((int)($session['amount_total'] ?? 0), $session['currency'] ?? 'jpy'),
            'method' => $method,
            'method_detail' => $methodDetail,
            'receipt_url' => $receiptUrl,
            'raw' => $session,
        ]);
    }

    /**
     * 결제 취소
     */
    public function cancel(string $paymentKey, string $reason = ''): bool
    {
        // Checkout Session은 expire로 취소
        $response = $this->apiCall('POST', '/checkout/sessions/' . $paymentKey . '/expire');
        return ($response['status'] ?? '') === 'expired';
    }

    /**
     * 환불
     */
    public function refund(string $paymentKey, int $amount, string $reason = ''): RefundResult
    {
        // paymentKey가 세션ID면 payment_intent를 먼저 조회
        $piId = $paymentKey;
        if (str_starts_with($paymentKey, 'cs_')) {
            $session = $this->apiCall('GET', '/checkout/sessions/' . $paymentKey);
            $piId = $session['payment_intent'] ?? '';
        }

        if (empty($piId)) {
            return new RefundResult(['success' => false, 'failure_reason' => 'Payment intent not found']);
        }

        $params = ['payment_intent' => $piId];
        if ($amount > 0) {
            // Stripe는 minor unit 사용
            $session = $this->apiCall('GET', '/payment_intents/' . $piId);
            $currency = $session['currency'] ?? 'jpy';
            $params['amount'] = $this->toMinorUnit($amount, $currency);
        }
        if ($reason) $params['reason'] = 'requested_by_customer';

        $response = $this->apiCall('POST', '/refunds', $params);

        return new RefundResult([
            'success' => ($response['status'] ?? '') === 'succeeded',
            'refund_id' => $response['id'] ?? null,
            'amount' => $amount,
            'status' => ($response['status'] ?? '') === 'succeeded' ? 'completed' : 'failed',
            'failure_reason' => $response['failure_reason'] ?? null,
            'raw' => $response,
        ]);
    }

    /**
     * 거래 조회
     */
    public function getTransaction(string $paymentKey): ?array
    {
        if (str_starts_with($paymentKey, 'cs_')) {
            return $this->apiCall('GET', '/checkout/sessions/' . $paymentKey);
        }
        if (str_starts_with($paymentKey, 'ch_')) {
            return $this->apiCall('GET', '/charges/' . $paymentKey);
        }
        if (str_starts_with($paymentKey, 'pi_')) {
            return $this->apiCall('GET', '/payment_intents/' . $paymentKey . '?expand[]=latest_charge');
        }
        return $this->apiCall('GET', '/payment_intents/' . $paymentKey);
    }

    /**
     * Stripe API 호출
     */
    private function apiCall(string $method, string $endpoint, array $params = []): array
    {
        $ch = curl_init($this->apiBase . $endpoint . ($method === 'GET' && $params ? '?' . http_build_query($params) : ''));
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_HTTPHEADER => ['Authorization: Bearer ' . $this->secretKey],
            CURLOPT_TIMEOUT => 30,
        ]);

        if ($method === 'POST') {
            curl_setopt($ch, CURLOPT_POST, true);
            curl_setopt($ch, CURLOPT_POSTFIELDS, http_build_query($params));
        }

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $data = json_decode($response, true) ?: [];

        if ($httpCode >= 400) {
            error_log("Stripe API error [{$httpCode}] {$endpoint}: " . ($data['error']['message'] ?? $response));
        }

        return $data;
    }

    /** 금액을 Stripe minor unit으로 변환 (JPY는 그대로, 그 외는 ×100) */
    private function toMinorUnit(int $amount, string $currency): int
    {
        $zeroDecimal = ['jpy','krw','vnd','clp','pyg','ugx','gnf','kmf','rwf','xaf','xof','xpf','bif','djf','mga','vuv'];
        return in_array(strtolower($currency), $zeroDecimal) ? $amount : $amount * 100;
    }

    /** Stripe minor unit에서 실제 금액으로 변환 */
    private function fromMinorUnit(int $amount, string $currency): int
    {
        $zeroDecimal = ['jpy','krw','vnd','clp','pyg','ugx','gnf','kmf','rwf','xaf','xof','xpf','bif','djf','mga','vuv'];
        return in_array(strtolower($currency), $zeroDecimal) ? $amount : (int)($amount / 100);
    }
}
