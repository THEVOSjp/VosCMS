<?php

declare(strict_types=1);

namespace VosMarketplace\Models;

use RzxLib\Core\Database\Connection;
use RzxLib\Core\Database\QueryBuilder;

class MarketplaceCategory
{
    protected static string $table = 'mp_categories';

    public ?int $id = null;
    public string $slug = '';
    public ?array $name = null;
    public ?array $description = null;
    public ?string $icon = null;
    public ?int $parent_id = null;
    public int $sort_order = 0;
    public int $item_count = 0;
    public bool $is_active = true;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public static function query(): QueryBuilder
    {
        return Connection::getInstance()->table(static::$table);
    }

    public static function all(): array
    {
        return static::query()->orderBy('sort_order')->get();
    }

    public static function active(): array
    {
        return static::query()
            ->where('is_active', 1)
            ->orderBy('sort_order')
            ->get();
    }

    public static function find(int $id): ?static
    {
        $data = static::query()->where('id', $id)->first();
        return $data ? static::fromArray($data) : null;
    }

    public static function findBySlug(string $slug): ?static
    {
        $data = static::query()->where('slug', $slug)->first();
        return $data ? static::fromArray($data) : null;
    }

    public static function fromArray(array $data): static
    {
        $cat = new static();
        $cat->id = isset($data['id']) ? (int) $data['id'] : null;
        $cat->slug = $data['slug'] ?? '';
        $cat->name = isset($data['name']) ? (is_string($data['name']) ? json_decode($data['name'], true) : $data['name']) : null;
        $cat->description = isset($data['description']) ? (is_string($data['description']) ? json_decode($data['description'], true) : $data['description']) : null;
        $cat->icon = $data['icon'] ?? null;
        $cat->parent_id = isset($data['parent_id']) ? (int) $data['parent_id'] : null;
        $cat->sort_order = (int) ($data['sort_order'] ?? 0);
        $cat->item_count = (int) ($data['item_count'] ?? 0);
        $cat->is_active = (bool) ($data['is_active'] ?? true);
        $cat->created_at = $data['created_at'] ?? null;
        $cat->updated_at = $data['updated_at'] ?? null;
        return $cat;
    }

    public function localizedName(string $locale = 'ko'): string
    {
        if (!$this->name) return $this->slug;
        return $this->name[$locale] ?? $this->name['en'] ?? $this->slug;
    }
}
