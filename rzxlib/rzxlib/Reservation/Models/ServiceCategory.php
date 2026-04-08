<?php

declare(strict_types=1);

namespace RzxLib\Reservation\Models;

use RzxLib\Core\Database\QueryBuilder;

/**
 * ServiceCategory - 서비스 카테고리 모델
 *
 * 서비스를 분류하는 카테고리
 *
 * @package RzxLib\Reservation\Models
 */
class ServiceCategory
{
    /**
     * 테이블명
     */
    protected static string $table = 'rzx_service_categories';

    /**
     * 속성
     */
    public ?int $id = null;
    public string $name;
    public ?string $slug = null;
    public ?string $description = null;
    public ?string $image = null;
    public ?int $parent_id = null;
    public int $sort_order = 0;
    public bool $is_active = true;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    /**
     * QueryBuilder 인스턴스 반환
     */
    public static function query(): QueryBuilder
    {
        return (new QueryBuilder())->table(static::$table);
    }

    /**
     * 모든 카테고리 가져오기
     */
    public static function all(): array
    {
        return static::query()->orderBy('sort_order')->orderBy('name')->get();
    }

    /**
     * 활성 카테고리만 가져오기
     */
    public static function active(): array
    {
        return static::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name')
            ->get();
    }

    /**
     * 최상위 카테고리 가져오기
     */
    public static function roots(): array
    {
        return static::query()
            ->whereNull('parent_id')
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * ID로 찾기
     */
    public static function find(int $id): ?static
    {
        $data = static::query()->where('id', $id)->first();

        return $data ? static::fromArray($data) : null;
    }

    /**
     * Slug로 찾기
     */
    public static function findBySlug(string $slug): ?static
    {
        $data = static::query()->where('slug', $slug)->first();

        return $data ? static::fromArray($data) : null;
    }

    /**
     * 배열에서 인스턴스 생성
     */
    public static function fromArray(array $data): static
    {
        $category = new static();

        $category->id = isset($data['id']) ? (int) $data['id'] : null;
        $category->name = $data['name'] ?? '';
        $category->slug = $data['slug'] ?? null;
        $category->description = $data['description'] ?? null;
        $category->image = $data['image'] ?? null;
        $category->parent_id = isset($data['parent_id']) ? (int) $data['parent_id'] : null;
        $category->sort_order = (int) ($data['sort_order'] ?? 0);
        $category->is_active = (bool) ($data['is_active'] ?? true);
        $category->created_at = $data['created_at'] ?? null;
        $category->updated_at = $data['updated_at'] ?? null;

        return $category;
    }

    /**
     * 배열로 변환
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'slug' => $this->slug,
            'description' => $this->description,
            'image' => $this->image,
            'parent_id' => $this->parent_id,
            'sort_order' => $this->sort_order,
            'is_active' => $this->is_active,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * 저장
     */
    public function save(): bool
    {
        $data = $this->toArray();
        unset($data['id'], $data['created_at'], $data['updated_at']);

        if ($this->id) {
            $data['updated_at'] = date('Y-m-d H:i:s');

            return static::query()
                ->where('id', $this->id)
                ->update($data) > 0;
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');

            if (empty($data['slug'])) {
                $data['slug'] = $this->generateSlug($data['name']);
            }

            $this->id = static::query()->insertGetId($data);

            return $this->id > 0;
        }
    }

    /**
     * 삭제
     */
    public function delete(): bool
    {
        if (!$this->id) {
            return false;
        }

        // 하위 카테고리의 parent_id를 null로 변경
        static::query()
            ->where('parent_id', $this->id)
            ->update(['parent_id' => null]);

        return static::query()->where('id', $this->id)->delete() > 0;
    }

    /**
     * Slug 생성
     */
    protected function generateSlug(string $name): string
    {
        $slug = strtolower(trim($name));
        $slug = preg_replace('/[^a-z0-9가-힣\s-]/', '', $slug);
        $slug = preg_replace('/[\s-]+/', '-', $slug);
        $slug = trim($slug, '-');

        $originalSlug = $slug;
        $count = 1;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        return $slug;
    }

    /**
     * 하위 카테고리 가져오기
     */
    public function children(): array
    {
        return static::query()
            ->where('parent_id', $this->id)
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }

    /**
     * 부모 카테고리 가져오기
     */
    public function parent(): ?static
    {
        if ($this->parent_id === null) {
            return null;
        }

        return static::find($this->parent_id);
    }

    /**
     * 이 카테고리의 서비스 가져오기
     */
    public function services(): array
    {
        return Service::byCategory($this->id);
    }

    /**
     * 서비스 수 가져오기
     */
    public function servicesCount(): int
    {
        return Service::query()
            ->where('category_id', $this->id)
            ->where('is_active', true)
            ->count();
    }
}
