<?php
namespace RzxLib\Modules\Payment\DTO;

class PaymentResult
{
    public bool $success;
    public string $status;
    public ?string $paymentKey;
    public ?string $transactionId;
    public int $amount;
    public ?string $method;
    public ?array $methodDetail;
    public ?string $receiptUrl;
    public ?string $failureCode;
    public ?string $failureMessage;
    public array $raw;

    public function __construct(array $data = [])
    {
        $this->success = $data['success'] ?? false;
        $this->status = $data['status'] ?? 'failed';
        $this->paymentKey = $data['payment_key'] ?? null;
        $this->transactionId = $data['transaction_id'] ?? null;
        $this->amount = (int)($data['amount'] ?? 0);
        $this->method = $data['method'] ?? null;
        $this->methodDetail = $data['method_detail'] ?? null;
        $this->receiptUrl = $data['receipt_url'] ?? null;
        $this->failureCode = $data['failure_code'] ?? null;
        $this->failureMessage = $data['failure_message'] ?? null;
        $this->raw = $data['raw'] ?? [];
    }

    public function isSuccessful(): bool
    {
        return $this->success && $this->status === 'paid';
    }
}
