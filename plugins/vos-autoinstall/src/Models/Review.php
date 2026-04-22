<?php

declare(strict_types=1);

namespace VosMarketplace\Models;

use RzxLib\Core\Database\Connection;
use RzxLib\Core\Database\QueryBuilder;

class Review
{
    protected static string $table = 'mp_reviews';

    public ?int $id = null;
    public int $item_id = 0;
    public string $admin_id = '';
    public ?int $order_item_id = null;
    public int $rating = 5;
    public ?string $title = null;
    public ?string $content = null;
    public bool $is_verified_purchase = false;
    public int $helpful_count = 0;
    public string $status = 'active';
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

    public static function forItem(int $itemId, int $limit = 20): array
    {
        return static::query()
            ->where('item_id', $itemId)
            ->where('status', 'active')
            ->orderBy('created_at', 'DESC')
            ->limit($limit)
            ->get();
    }

    public static function averageForItem(int $itemId): float
    {
        $pdo = Connection::getInstance()->getPdo();
        $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
        $stmt = $pdo->prepare("SELECT AVG(rating) FROM {$prefix}mp_reviews WHERE item_id = ? AND status = 'active'");
        $stmt->execute([$itemId]);
        return round((float) $stmt->fetchColumn(), 1);
    }

    public static function countForItem(int $itemId): int
    {
        return (int) static::query()
            ->where('item_id', $itemId)
            ->where('status', 'active')
            ->count();
    }

    public static function fromArray(array $data): static
    {
        $r = new static();
        $r->id = isset($data['id']) ? (int) $data['id'] : null;
        $r->item_id = (int) ($data['item_id'] ?? 0);
        $r->admin_id = $data['admin_id'] ?? '';
        $r->order_item_id = isset($data['order_item_id']) ? (int) $data['order_item_id'] : null;
        $r->rating = max(1, min(5, (int) ($data['rating'] ?? 5)));
        $r->title = $data['title'] ?? null;
        $r->content = $data['content'] ?? null;
        $r->is_verified_purchase = (bool) ($data['is_verified_purchase'] ?? false);
        $r->helpful_count = (int) ($data['helpful_count'] ?? 0);
        $r->status = $data['status'] ?? 'active';
        $r->created_at = $data['created_at'] ?? null;
        $r->updated_at = $data['updated_at'] ?? null;
        return $r;
    }

    public function toArray(): array
    {
        return [
            'item_id' => $this->item_id,
            'admin_id' => $this->admin_id,
            'order_item_id' => $this->order_item_id,
            'rating' => $this->rating,
            'title' => $this->title,
            'content' => $this->content,
            'is_verified_purchase' => (int) $this->is_verified_purchase,
            'helpful_count' => $this->helpful_count,
            'status' => $this->status,
        ];
    }

    public function save(): bool
    {
        $data = $this->toArray();

        if ($this->id) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            return static::query()->where('id', $this->id)->update($data) >= 0;
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            $result = static::query()->insert($data);
            if ($result) {
                $this->id = (int) Connection::getInstance()->getPdo()->lastInsertId();
            }
            return $result;
        }
    }
}
