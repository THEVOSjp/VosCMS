<?php
namespace RzxLib\Modules\Payment\DTO;

class RefundResult
{
    public bool $success;
    public ?string $refundId;
    public int $amount;
    public ?string $status;
    public ?string $failureReason;
    public array $raw;

    public function __construct(array $data = [])
    {
        $this->success = $data['success'] ?? false;
        $this->refundId = $data['refund_id'] ?? null;
        $this->amount = (int)($data['amount'] ?? 0);
        $this->status = $data['status'] ?? 'failed';
        $this->failureReason = $data['failure_reason'] ?? null;
        $this->raw = $data['raw'] ?? [];
    }
}
