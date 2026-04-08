<?php
namespace RzxLib\Modules\Payment\Contracts;

use RzxLib\Modules\Payment\DTO\PaymentRequest;
use RzxLib\Modules\Payment\DTO\PaymentResult;
use RzxLib\Modules\Payment\DTO\RefundResult;

interface PaymentGatewayInterface
{
    public function getName(): string;
    public function getSupportedCountries(): array;
    public function createCheckoutSession(PaymentRequest $request): array;
    public function confirm(string $sessionId): PaymentResult;
    public function cancel(string $paymentKey, string $reason = ''): bool;
    public function refund(string $paymentKey, int $amount, string $reason = ''): RefundResult;
    public function getTransaction(string $paymentKey): ?array;
}
