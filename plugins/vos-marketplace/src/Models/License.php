<?php

declare(strict_types=1);

namespace VosMarketplace\Models;

use RzxLib\Core\Database\Connection;
use RzxLib\Core\Database\QueryBuilder;

class License
{
    protected static string $table = 'mp_licenses';

    public ?int $id = null;
    public string $license_key = '';
    public ?int $order_item_id = null;
    public int $item_id = 0;
    public string $admin_id = '';
    public string $type = 'single';
    public int $max_activations = 1;
    public string $status = 'active';
    public ?string $expires_at = null;
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

    public static function findByKey(string $licenseKey): ?static
    {
        $data = static::query()->where('license_key', $licenseKey)->first();
        return $data ? static::fromArray($data) : null;
    }

    public static function forAdmin(string $adminId): array
    {
        return static::query()
            ->where('admin_id', $adminId)
            ->orderBy('created_at', 'DESC')
            ->get();
    }

    public static function forItem(int $itemId, string $adminId): ?static
    {
        $data = static::query()
            ->where('item_id', $itemId)
            ->where('admin_id', $adminId)
            ->where('status', 'active')
            ->first();
        return $data ? static::fromArray($data) : null;
    }

    public static function generateKey(): string
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
        $l = new static();
        $l->id = isset($data['id']) ? (int) $data['id'] : null;
        $l->license_key = $data['license_key'] ?? '';
        $l->order_item_id = isset($data['order_item_id']) ? (int) $data['order_item_id'] : null;
        $l->item_id = (int) ($data['item_id'] ?? 0);
        $l->admin_id = $data['admin_id'] ?? '';
        $l->type = $data['type'] ?? 'single';
        $l->max_activations = (int) ($data['max_activations'] ?? 1);
        $l->status = $data['status'] ?? 'active';
        $l->expires_at = $data['expires_at'] ?? null;
        $l->created_at = $data['created_at'] ?? null;
        $l->updated_at = $data['updated_at'] ?? null;
        return $l;
    }

    public function toArray(): array
    {
        return [
            'license_key' => $this->license_key,
            'order_item_id' => $this->order_item_id,
            'item_id' => $this->item_id,
            'admin_id' => $this->admin_id,
            'type' => $this->type,
            'max_activations' => $this->max_activations,
            'status' => $this->status,
            'expires_at' => $this->expires_at,
        ];
    }

    public function save(): bool
    {
        $data = $this->toArray();

        if ($this->id) {
            $data['updated_at'] = date('Y-m-d H:i:s');
            return static::query()->where('id', $this->id)->update($data) >= 0;
        } else {
            if (empty($data['license_key'])) $data['license_key'] = static::generateKey();
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');
            $result = static::query()->insert($data);
            if ($result) {
                $this->id = (int) Connection::getInstance()->getPdo()->lastInsertId();
                $this->license_key = $data['license_key'];
            }
            return $result;
        }
    }

    public function isValid(): bool
    {
        if ($this->status !== 'active') return false;
        if ($this->expires_at && strtotime($this->expires_at) < time()) return false;
        return true;
    }

    public function isExpired(): bool
    {
        return $this->expires_at && strtotime($this->expires_at) < time();
    }

    public function activeActivationCount(): int
    {
        return (int) LicenseActivation::countActive($this->id);
    }

    public function canActivate(): bool
    {
        if (!$this->isValid()) return false;
        if ($this->type === 'unlimited') return true;
        return $this->activeActivationCount() < $this->max_activations;
    }

    public function getActivations(): array
    {
        if (!$this->id) return [];
        return LicenseActivation::forLicense($this->id);
    }
}
