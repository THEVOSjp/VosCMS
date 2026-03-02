<?php

declare(strict_types=1);

namespace RzxLib\Reservation\Models;

use RzxLib\Core\Database\Connection;
use RzxLib\Core\Database\QueryBuilder;

/**
 * Service - 서비스/상품 모델
 *
 * 예약 가능한 서비스나 상품을 나타냄
 *
 * @package RzxLib\Reservation\Models
 */
class Service
{
    /**
     * 테이블명 (prefix는 Connection에서 자동 추가됨)
     */
    protected static string $table = 'services';

    /**
     * 속성
     */
    public ?string $id = null;
    public string $name = '';
    public ?string $slug = null;
    public ?string $description = null;
    public ?string $short_description = null;
    public int $duration = 60; // 분 단위
    public float $price = 0;
    public ?string $currency = 'KRW';
    public ?string $category_id = null;
    public ?string $image = null;
    public bool $is_active = true;
    public int $max_capacity = 1; // 동시 예약 가능 인원
    public int $buffer_time = 0; // 예약 간 버퍼 시간 (분)
    public ?int $advance_booking_days = null; // 예약 가능 기간 (일)
    public ?int $min_notice_hours = null; // 최소 예약 사전 알림 (시간)
    public ?array $meta = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    /**
     * QueryBuilder 인스턴스 반환
     */
    public static function query(): QueryBuilder
    {
        return Connection::getInstance()->table(static::$table);
    }

    /**
     * 모든 서비스 가져오기
     */
    public static function all(): array
    {
        return static::query()->orderBy('name')->get();
    }

    /**
     * 활성 서비스만 가져오기
     */
    public static function active(): array
    {
        return static::query()
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * ID로 찾기
     */
    public static function find(string $id): ?static
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
     * ID로 찾기 (없으면 예외)
     */
    public static function findOrFail(string $id): static
    {
        $service = static::find($id);

        if (!$service) {
            throw new \RuntimeException("서비스를 찾을 수 없습니다: {$id}");
        }

        return $service;
    }

    /**
     * 카테고리별 서비스 가져오기
     */
    public static function byCategory(string $categoryId): array
    {
        return static::query()
            ->where('category_id', $categoryId)
            ->where('is_active', true)
            ->orderBy('name')
            ->get();
    }

    /**
     * 배열에서 인스턴스 생성
     */
    public static function fromArray(array $data): static
    {
        $service = new static();

        $service->id = $data['id'] ?? null;
        $service->name = $data['name'] ?? '';
        $service->slug = $data['slug'] ?? null;
        $service->description = $data['description'] ?? null;
        $service->short_description = $data['short_description'] ?? null;
        $service->duration = (int) ($data['duration'] ?? 60);
        $service->price = (float) ($data['price'] ?? 0);
        $service->currency = $data['currency'] ?? 'KRW';
        $service->category_id = $data['category_id'] ?? null;
        $service->image = $data['image'] ?? null;
        $service->is_active = (bool) ($data['is_active'] ?? true);
        $service->max_capacity = (int) ($data['max_capacity'] ?? 1);
        $service->buffer_time = (int) ($data['buffer_time'] ?? 0);
        $service->advance_booking_days = isset($data['advance_booking_days']) ? (int) $data['advance_booking_days'] : null;
        $service->min_notice_hours = isset($data['min_notice_hours']) ? (int) $data['min_notice_hours'] : null;
        $service->meta = isset($data['meta']) ? json_decode($data['meta'], true) : null;
        $service->created_at = $data['created_at'] ?? null;
        $service->updated_at = $data['updated_at'] ?? null;

        return $service;
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
            'short_description' => $this->short_description,
            'duration' => $this->duration,
            'price' => $this->price,
            'currency' => $this->currency,
            'category_id' => $this->category_id,
            'image' => $this->image,
            'is_active' => $this->is_active,
            'max_capacity' => $this->max_capacity,
            'buffer_time' => $this->buffer_time,
            'advance_booking_days' => $this->advance_booking_days,
            'min_notice_hours' => $this->min_notice_hours,
            'meta' => $this->meta,
            'created_at' => $this->created_at,
            'updated_at' => $this->updated_at,
        ];
    }

    /**
     * UUID 생성
     */
    public static function generateUuid(): string
    {
        return sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0xffff)
        );
    }

    /**
     * 저장
     */
    public function save(): bool
    {
        $data = $this->toArray();
        unset($data['created_at'], $data['updated_at']);

        // JSON 필드 변환
        if ($data['meta'] !== null) {
            $data['meta'] = json_encode($data['meta'], JSON_UNESCAPED_UNICODE);
        }

        if ($this->id) {
            // 업데이트
            unset($data['id']);
            $data['updated_at'] = date('Y-m-d H:i:s');

            return static::query()
                ->where('id', $this->id)
                ->update($data) > 0;
        } else {
            // 삽입
            $data['id'] = static::generateUuid();
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');

            // Slug 자동 생성
            if (empty($data['slug'])) {
                $data['slug'] = $this->generateSlug($data['name']);
            }

            $result = static::query()->insert($data);
            if ($result) {
                $this->id = $data['id'];
            }

            return $result;
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

        // 중복 확인
        $originalSlug = $slug;
        $count = 1;

        while (static::query()->where('slug', $slug)->exists()) {
            $slug = $originalSlug . '-' . $count++;
        }

        return $slug;
    }

    /**
     * 가격 포맷팅
     */
    public function formattedPrice(): string
    {
        if ($this->currency === 'KRW') {
            return number_format($this->price) . '원';
        }

        return number_format($this->price, 2) . ' ' . $this->currency;
    }

    /**
     * 소요 시간 포맷팅
     */
    public function formattedDuration(): string
    {
        if ($this->duration >= 60) {
            $hours = floor($this->duration / 60);
            $minutes = $this->duration % 60;

            if ($minutes > 0) {
                return "{$hours}시간 {$minutes}분";
            }

            return "{$hours}시간";
        }

        return "{$this->duration}분";
    }

    /**
     * 예약 가능한 시간대 가져오기
     */
    public function getAvailableSlots(string $date): array
    {
        return TimeSlot::availableForService($this->id, $date);
    }

    /**
     * 특정 날짜에 예약 가능한지 확인
     */
    public function isAvailableOn(string $date): bool
    {
        // 활성 상태 확인
        if (!$this->is_active) {
            return false;
        }

        // 사전 예약 기간 확인
        if ($this->advance_booking_days !== null) {
            $maxDate = date('Y-m-d', strtotime("+{$this->advance_booking_days} days"));
            if ($date > $maxDate) {
                return false;
            }
        }

        // 최소 알림 시간 확인
        if ($this->min_notice_hours !== null) {
            $minDateTime = date('Y-m-d H:i:s', strtotime("+{$this->min_notice_hours} hours"));
            if ($date < substr($minDateTime, 0, 10)) {
                return false;
            }
        }

        return true;
    }
}
