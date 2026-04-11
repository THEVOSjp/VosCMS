<?php

declare(strict_types=1);

namespace VosMarketplace\Models;

use RzxLib\Core\Database\Connection;
use RzxLib\Core\Database\QueryBuilder;

class MarketplaceOrder
{
    protected static string $table = 'mp_orders';

    public ?int $id = null;
    public string $uuid = '';
    public string $admin_id = '';
    public ?int $payment_id = null;
    public string $order_number = '';
    public float $subtotal = 0;
    public float $discount = 0;
    public float $total = 0;
    public string $currency = 'USD';
    public ?string $coupon_code = null;
    public string $status = 'pending';
    public ?string $paid_at = null;
    public ?array $metadata = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public static function query(): QueryBuilder
    {
        return Connection::getInstance()->table(static::$table);
    }

    public static function find(int $id): ?static
    {
        $data = static::query()->where('id', $id)->first();
        return $data ? static::fromArray($data) : null;
    }

    public static function findByUuid(string $uuid): ?static
    {
        $data = static::query()->where('uuid', $uuid)->first();
        return $data ? static::fromArray($data) : null;
    }

    public static function findByOrderNumber(string $orderNumber): ?static
    {
        $data = static::query()->where('order_number', $orderNumber)->first();
        return $data ? static::fromArray($data) : null;
    }

    public static function forAdmin(string $adminId, int $limit = 50): array
    {
        return static::query()
            ->where('admin_id', $adminId)
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
    }

    public static function generateOrderNumber(): string
    {
        $date = date('Ymd');
        $rand = strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
        return "MKT-{$date}-{$rand}";
    }

    public static function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );
    }

    public static function fromArray(array $data): static
    {
        $o = new static();
        $o->id = isset($data['id']) ? (int) $data['id'] : null;
        $o->uuid = $data['uuid'] ?? '';
        $o->admin_id = $data['admin_id'] ?? '';
        $o->payment_id = isset($data['payment_id']) ? (int) $data['payment_id'] : null;
        $o->order_number = $data['order_number'] ?? '';
        $o->subtotal = (float) ($data['subtotal'] ?? 0);
        $o->discount = (float) ($data['discount'] ?? 0);
        $o->total = (float) ($data['total'] ?? 0);
        $o->currency = $data['currency'] ?? 'USD';
        $o->coupon_code = $data['coupon_code'] ?? null;
        $o->status = $data['status'] ?? 'pending';
        $o->paid_at = $data['paid_at'] ?? null;
        $o->metadata = isset($data['metadata']) ? (is_string($data['metadata']) ? json_decode($data['metadata'], true) : $data['metadata']) : null;
        $o->created_at = $data['created_at'] ?? null;
        $o->updated_at = $data['updated_at'] ?? null;
        return $o;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'uuid' => $this->uuid,
            'admin_id' => $this->admin_id,
            'payment_id' => $this->payment_id,
            'order_number' => $this->order_number,
            'subtotal' => $this->subtotal,
            'discount' => $this->discount,
            'total' => $this->total,
            'currency' => $this->currency,
            'coupon_code' => $this->coupon_code,
            'status' => $this->status,
            'paid_at' => $this->paid_at,
            'metadata' => $this->metadata,
        ];
    }

    public function save(): bool
    {
        $data = $this->toArray();
        unset($data['id']);

        if ($data['metadata'] !== null) {
            $data['metadata'] = json_encode($data['metadata'], JSON_UNESCAPED_UNICODE);
        }

        if ($this->id) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            return static::query()->where('id', $this->id)->update($data) >= 0;
        } else {
            if (empty($data['uuid'])) $data['uuid'] = static::generateUuid();
            if (empty($data['order_number'])) $data['order_number'] = static::generateOrderNumber();
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            $result = static::query()->insert($data);
            if ($result) {
                $this->id = (int) Connection::getInstance()->getPdo()->lastInsertId();
                $this->uuid = $data['uuid'];
                $this->order_number = $data['order_number'];
            }
            return $result;
        }
    }

    public function markPaid(?int $paymentId = null): bool
    {
        $this->status = 'paid';
        $this->paid_at = date('Y-m-d H:i:s');
        $this->payment_id = $paymentId;
        return $this->save();
    }

    public function getItems(): array
    {
        if (!$this->id) return [];
        return OrderItem::forOrder($this->id);
    }
}
