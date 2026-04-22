<?php

declare(strict_types=1);

namespace VosMarketplace\Models;

use RzxLib\Core\Database\Connection;
use RzxLib\Core\Database\QueryBuilder;

class MarketplaceItem
{
    protected static string $table = 'mp_items';

    public ?int $id = null;
    public ?string $remote_id = null;
    public string $slug = '';
    public string $type = 'plugin';
    public ?array $name = null;
    public ?array $description = null;
    public ?array $short_description = null;
    public ?string $author_name = null;
    public ?string $author_url = null;
    public ?int $seller_id = null;
    public ?int $category_id = null;
    public ?array $tags = null;
    public ?string $icon = null;
    public ?string $banner_image = null;
    public ?array $screenshots = null;
    public float $price = 0;
    public string $currency = 'USD';
    public ?float $sale_price = null;
    public ?string $sale_ends_at = null;
    public string $latest_version = '1.0.0';
    public ?string $min_voscms_version = null;
    public ?string $min_php_version = null;
    public ?array $requires_plugins = null;
    public int $download_count = 0;
    public float $rating_avg = 0.0;
    public int $rating_count = 0;
    public bool $is_featured = false;
    public bool $is_verified = false;
    public string $status = 'active';
    public ?string $created_at = null;
    public ?string $updated_at = null;

    public static function query(): QueryBuilder
    {
        return Connection::getInstance()->table(static::$table);
    }

    public static function all(): array
    {
        return static::query()->orderBy('created_at', 'DESC')->get();
    }

    public static function active(): array
    {
        return static::query()
            ->where('status', 'active')
            ->orderBy('is_featured', 'DESC')
            ->orderBy('download_count', 'DESC')
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

    public static function findByRemoteId(string $remoteId): ?static
    {
        $data = static::query()->where('remote_id', $remoteId)->first();
        return $data ? static::fromArray($data) : null;
    }

    public static function search(array $filters = []): array
    {
        $q = static::query()->where('status', 'active');

        if (!empty($filters['type'])) {
            $q->where('type', $filters['type']);
        }
        if (!empty($filters['category_id'])) {
            $q->where('category_id', $filters['category_id']);
        }
        if (isset($filters['is_featured'])) {
            $q->where('is_featured', (int) $filters['is_featured']);
        }
        if (!empty($filters['keyword'])) {
            $keyword = '%' . $filters['keyword'] . '%';
            $q->where('slug', 'LIKE', $keyword);
        }
        if (isset($filters['free']) && $filters['free']) {
            $q->where('price', 0);
        }

        $sort = $filters['sort'] ?? 'popular';
        match ($sort) {
            'newest' => $q->orderBy('created_at', 'DESC'),
            'price_asc' => $q->orderBy('price', 'ASC'),
            'price_desc' => $q->orderBy('price', 'DESC'),
            'rating' => $q->orderBy('rating_avg', 'DESC'),
            default => $q->orderBy('download_count', 'DESC'),
        };

        $limit = min((int) ($filters['limit'] ?? 24), 100);
        $offset = (int) ($filters['offset'] ?? 0);

        return $q->limit($limit)->offset($offset)->get();
    }

    public static function featured(int $limit = 8): array
    {
        return static::query()
            ->where('status', 'active')
            ->where('is_featured', 1)
            ->orderBy('download_count', 'DESC')
            ->limit($limit)
            ->get();
    }

    public static function byType(string $type, int $limit = 24): array
    {
        return static::query()
            ->where('status', 'active')
            ->where('type', $type)
            ->orderBy('download_count', 'DESC')
            ->limit($limit)
            ->get();
    }

    public static function fromArray(array $data): static
    {
        $item = new static();
        $item->id = isset($data['id']) ? (int) $data['id'] : null;
        $item->remote_id = $data['remote_id'] ?? null;
        $item->slug = $data['slug'] ?? '';
        $item->type = $data['type'] ?? 'plugin';
        $item->name = isset($data['name']) ? (is_string($data['name']) ? json_decode($data['name'], true) : $data['name']) : null;
        $item->description = isset($data['description']) ? (is_string($data['description']) ? json_decode($data['description'], true) : $data['description']) : null;
        $item->short_description = isset($data['short_description']) ? (is_string($data['short_description']) ? json_decode($data['short_description'], true) : $data['short_description']) : null;
        $item->author_name = $data['author_name'] ?? null;
        $item->author_url = $data['author_url'] ?? null;
        $item->seller_id = isset($data['seller_id']) ? (int) $data['seller_id'] : null;
        $item->category_id = isset($data['category_id']) ? (int) $data['category_id'] : null;
        $item->tags = isset($data['tags']) ? (is_string($data['tags']) ? json_decode($data['tags'], true) : $data['tags']) : null;
        $item->icon = $data['icon'] ?? null;
        $item->banner_image = $data['banner_image'] ?? null;
        $item->screenshots = isset($data['screenshots']) ? (is_string($data['screenshots']) ? json_decode($data['screenshots'], true) : $data['screenshots']) : null;
        $item->price = (float) ($data['price'] ?? 0);
        $item->currency = $data['currency'] ?? 'USD';
        $item->sale_price = isset($data['sale_price']) ? (float) $data['sale_price'] : null;
        $item->sale_ends_at = $data['sale_ends_at'] ?? null;
        $item->latest_version = $data['latest_version'] ?? '1.0.0';
        $item->min_voscms_version = $data['min_voscms_version'] ?? null;
        $item->min_php_version = $data['min_php_version'] ?? null;
        $item->requires_plugins = isset($data['requires_plugins']) ? (is_string($data['requires_plugins']) ? json_decode($data['requires_plugins'], true) : $data['requires_plugins']) : null;
        $item->download_count = (int) ($data['download_count'] ?? 0);
        $item->rating_avg = (float) ($data['rating_avg'] ?? 0);
        $item->rating_count = (int) ($data['rating_count'] ?? 0);
        $item->is_featured = (bool) ($data['is_featured'] ?? false);
        $item->is_verified = (bool) ($data['is_verified'] ?? false);
        $item->status = $data['status'] ?? 'active';
        $item->created_at = $data['created_at'] ?? null;
        $item->updated_at = $data['updated_at'] ?? null;

        return $item;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'remote_id' => $this->remote_id,
            'slug' => $this->slug,
            'type' => $this->type,
            'name' => $this->name,
            'description' => $this->description,
            'short_description' => $this->short_description,
            'author_name' => $this->author_name,
            'author_url' => $this->author_url,
            'seller_id' => $this->seller_id,
            'category_id' => $this->category_id,
            'tags' => $this->tags,
            'icon' => $this->icon,
            'banner_image' => $this->banner_image,
            'screenshots' => $this->screenshots,
            'price' => $this->price,
            'currency' => $this->currency,
            'sale_price' => $this->sale_price,
            'sale_ends_at' => $this->sale_ends_at,
            'latest_version' => $this->latest_version,
            'min_voscms_version' => $this->min_voscms_version,
            'min_php_version' => $this->min_php_version,
            'requires_plugins' => $this->requires_plugins,
            'download_count' => $this->download_count,
            'rating_avg' => $this->rating_avg,
            'rating_count' => $this->rating_count,
            'is_featured' => $this->is_featured,
            'is_verified' => $this->is_verified,
            'status' => $this->status,
        ];
    }

    public function save(): bool
    {
        $data = $this->toArray();
        unset($data['id']);

        // JSON 필드 인코딩
        foreach (['name', 'description', 'short_description', 'tags', 'screenshots', 'requires_plugins'] as $jsonField) {
            if ($data[$jsonField] !== null) {
                $data[$jsonField] = json_encode($data[$jsonField], JSON_UNESCAPED_UNICODE);
            }
        }

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

    public function delete(): bool
    {
        if (!$this->id) return false;
        return static::query()->where('id', $this->id)->delete() > 0;
    }

    /**
     * 현재 로케일 기준 이름 반환
     */
    public function localizedName(string $locale = 'ko'): string
    {
        if (!$this->name) return $this->slug;
        return $this->name[$locale] ?? $this->name['en'] ?? $this->slug;
    }

    /**
     * 현재 유효 가격 (세일 적용)
     */
    public function effectivePrice(): float
    {
        if ($this->sale_price !== null && $this->sale_ends_at !== null && strtotime($this->sale_ends_at) > time()) {
            return $this->sale_price;
        }
        return $this->price;
    }

    /**
     * 무료 여부
     */
    public function isFree(): bool
    {
        return $this->effectivePrice() <= 0;
    }

    /**
     * 가격 포맷
     */
    public function formattedPrice(): string
    {
        $price = $this->effectivePrice();
        if ($price <= 0) return 'Free';

        $symbols = ['KRW' => '₩', 'USD' => '$', 'JPY' => '¥', 'EUR' => '€'];
        $symbol = $symbols[$this->currency] ?? $this->currency . ' ';

        if ($this->currency === 'KRW' || $this->currency === 'JPY') {
            return $symbol . number_format($price);
        }
        return $symbol . number_format($price, 2);
    }

    /**
     * 타입 라벨
     */
    public function typeLabel(string $locale = 'ko'): string
    {
        $labels = [
            'ko' => ['plugin' => '플러그인', 'theme' => '테마', 'widget' => '위젯', 'skin' => '스킨'],
            'en' => ['plugin' => 'Plugin', 'theme' => 'Theme', 'widget' => 'Widget', 'skin' => 'Skin'],
            'ja' => ['plugin' => 'プラグイン', 'theme' => 'テーマ', 'widget' => 'ウィジェット', 'skin' => 'スキン'],
        ];
        return $labels[$locale][$this->type] ?? $labels['en'][$this->type] ?? $this->type;
    }
}
