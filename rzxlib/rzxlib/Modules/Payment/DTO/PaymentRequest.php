<?php
namespace RzxLib\Modules\Payment\DTO;

class PaymentRequest
{
    public string $orderId;
    public int $amount;
    public string $currency;
    public string $description;
    public ?string $reservationId;
    public ?string $userId;
    public ?string $customerEmail;
    public ?string $customerName;
    public string $successUrl;
    public string $cancelUrl;
    public array $metadata;

    public function __construct(array $data = [])
    {
        $this->orderId = $data['order_id'] ?? '';
        $this->amount = (int)($data['amount'] ?? 0);
        $this->currency = strtolower($data['currency'] ?? 'jpy');
        $this->description = $data['description'] ?? '';
        $this->reservationId = $data['reservation_id'] ?? null;
        $this->userId = $data['user_id'] ?? null;
        $this->customerEmail = $data['customer_email'] ?? null;
        $this->customerName = $data['customer_name'] ?? null;
        $this->successUrl = $data['success_url'] ?? '';
        $this->cancelUrl = $data['cancel_url'] ?? '';
        $this->metadata = $data['metadata'] ?? [];
    }
}
