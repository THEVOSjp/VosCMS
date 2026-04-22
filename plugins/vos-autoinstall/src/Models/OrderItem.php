<?php

declare(strict_types=1);

namespace VosMarketplace\Models;

use RzxLib\Core\Database\Connection;
use RzxLib\Core\Database\QueryBuilder;

class OrderItem
{
    protected static string $table = 'mp_order_items';

    public ?int $id = null;
    public int $order_id = 0;
    public int $item_id = 0;
    public ?int $version_id = null;
    public string $item_name = '';
    public string $item_type = 'plugin';
    public string $item_slug = '';
    public float $price = 0;
    public float $discount = 0;
    public ?string $created_at = null;

    public static function query(): QueryBuilder
    {
        return Connection::getInstance()->table(static::$table);
    }

    public static function find(int $id): ?static
    {
        $data = static::query()->where('id', $id)->first();
        return $data ? static::fromArray($data) : null;
    }

    public static function forOrder(int $orderId): array
    {
        return static::query()
            ->where('order_id', $orderId)
            ->orderBy('id')
            ->get();
    }

    public static function hasPurchased(string $adminId, int $itemId): bool
    {
        $pdo = Connection::getInstance()->getPdo();
        $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

        $stmt = $pdo->prepare(
            "SELECT oi.id FROM {$prefix}mp_order_items oi
             JOIN {$prefix}mp_orders o ON o.id = oi.order_id
             WHERE o.admin_id = ? AND oi.item_id = ? AND o.status = 'paid'
             LIMIT 1"
        );
        $stmt->execute([$adminId, $itemId]);
        return (bool) $stmt->fetch();
    }

    public static function fromArray(array $data): static
    {
        $oi = new static();
        $oi->id = isset($data['id']) ? (int) $data['id'] : null;
        $oi->order_id = (int) ($data['order_id'] ?? 0);
        $oi->item_id = (int) ($data['item_id'] ?? 0);
        $oi->version_id = isset($data['version_id']) ? (int) $data['version_id'] : null;
        $oi->item_name = $data['item_name'] ?? '';
        $oi->item_type = $data['item_type'] ?? 'plugin';
        $oi->item_slug = $data['item_slug'] ?? '';
        $oi->price = (float) ($data['price'] ?? 0);
        $oi->discount = (float) ($data['discount'] ?? 0);
        $oi->created_at = $data['created_at'] ?? null;
        return $oi;
    }

    public function toArray(): array
    {
        return [
            'order_id' => $this->order_id,
            'item_id' => $this->item_id,
            'version_id' => $this->version_id,
            'item_name' => $this->item_name,
            'item_type' => $this->item_type,
            'item_slug' => $this->item_slug,
            'price' => $this->price,
            'discount' => $this->discount,
        ];
    }

    public function save(): bool
    {
        $data = $this->toArray();
        if ($this->id) {
            return static::query()->where('id', $this->id)->update($data) >= 0;
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $result = static::query()->insert($data);
            if ($result) {
                $this->id = (int) Connection::getInstance()->getPdo()->lastInsertId();
            }
            return $result;
        }
    }
}
