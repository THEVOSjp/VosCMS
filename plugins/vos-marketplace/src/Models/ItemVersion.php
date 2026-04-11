<?php

declare(strict_types=1);

namespace VosMarketplace\Models;

use RzxLib\Core\Database\Connection;
use RzxLib\Core\Database\QueryBuilder;

class ItemVersion
{
    protected static string $table = 'mp_item_versions';

    public ?int $id = null;
    public int $item_id = 0;
    public string $version = '1.0.0';
    public ?string $changelog = null;
    public ?string $download_url = null;
    public ?string $file_hash = null;
    public ?int $file_size = null;
    public ?string $min_voscms_version = null;
    public ?string $min_php_version = null;
    public string $status = 'active';
    public ?string $released_at = null;
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

    public static function latestForItem(int $itemId): ?static
    {
        $data = static::query()
            ->where('item_id', $itemId)
            ->where('status', 'active')
            ->orderBy('released_at', 'DESC')
            ->first();
        return $data ? static::fromArray($data) : null;
    }

    public static function allForItem(int $itemId): array
    {
        return static::query()
            ->where('item_id', $itemId)
            ->orderBy('released_at', 'DESC')
            ->get();
    }

    public static function fromArray(array $data): static
    {
        $v = new static();
        $v->id = isset($data['id']) ? (int) $data['id'] : null;
        $v->item_id = (int) ($data['item_id'] ?? 0);
        $v->version = $data['version'] ?? '1.0.0';
        $v->changelog = $data['changelog'] ?? null;
        $v->download_url = $data['download_url'] ?? null;
        $v->file_hash = $data['file_hash'] ?? null;
        $v->file_size = isset($data['file_size']) ? (int) $data['file_size'] : null;
        $v->min_voscms_version = $data['min_voscms_version'] ?? null;
        $v->min_php_version = $data['min_php_version'] ?? null;
        $v->status = $data['status'] ?? 'active';
        $v->released_at = $data['released_at'] ?? null;
        $v->created_at = $data['created_at'] ?? null;
        return $v;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'item_id' => $this->item_id,
            'version' => $this->version,
            'changelog' => $this->changelog,
            'download_url' => $this->download_url,
            'file_hash' => $this->file_hash,
            'file_size' => $this->file_size,
            'min_voscms_version' => $this->min_voscms_version,
            'min_php_version' => $this->min_php_version,
            'status' => $this->status,
            'released_at' => $this->released_at,
        ];
    }

    public function save(): bool
    {
        $data = $this->toArray();
        unset($data['id']);

        if ($this->id) {
            return static::query()->where('id', $this->id)->update($data) >= 0;
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            if (empty($data['released_at'])) {
                $data['released_at'] = date('Y-m-d H:i:s');
            }
            $result = static::query()->insert($data);
            if ($result) {
                $this->id = (int) Connection::getInstance()->getPdo()->lastInsertId();
            }
            return $result;
        }
    }

    public function formattedFileSize(): string
    {
        if (!$this->file_size) return '-';
        if ($this->file_size >= 1048576) {
            return round($this->file_size / 1048576, 1) . ' MB';
        }
        return round($this->file_size / 1024, 1) . ' KB';
    }
}
