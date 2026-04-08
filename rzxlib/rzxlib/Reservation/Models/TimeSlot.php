<?php

declare(strict_types=1);

namespace RzxLib\Reservation\Models;

use RzxLib\Core\Database\QueryBuilder;

/**
 * TimeSlot - 시간대 모델
 *
 * 예약 가능한 시간대를 나타냄
 *
 * @package RzxLib\Reservation\Models
 */
class TimeSlot
{
    /**
     * 테이블명
     */
    protected static string $table = 'rzx_time_slots';

    /**
     * 속성
     */
    public ?int $id = null;
    public ?int $service_id = null; // null이면 모든 서비스에 적용
    public int $day_of_week; // 0=일, 1=월, ..., 6=토
    public string $start_time; // HH:MM 형식
    public string $end_time; // HH:MM 형식
    public int $max_bookings = 1;
    public bool $is_active = true;
    public ?string $specific_date = null; // 특정 날짜용 (휴일 등)
    public bool $is_blocked = false; // true면 예약 불가
    public ?string $created_at = null;

    /**
     * 요일 라벨
     */
    protected static array $dayLabels = [
        0 => '일요일',
        1 => '월요일',
        2 => '화요일',
        3 => '수요일',
        4 => '목요일',
        5 => '금요일',
        6 => '토요일',
    ];

    /**
     * QueryBuilder 인스턴스 반환
     */
    public static function query(): QueryBuilder
    {
        return (new QueryBuilder())->table(static::$table);
    }

    /**
     * 서비스의 특정 날짜 가용 슬롯 가져오기
     */
    public static function availableForService(int $serviceId, string $date): array
    {
        $dayOfWeek = (int) date('w', strtotime($date));

        // 특정 날짜 차단 확인
        $blocked = static::query()
            ->where('specific_date', $date)
            ->where('is_blocked', true)
            ->where(function($q) use ($serviceId) {
                $q->whereNull('service_id')
                  ->orWhere('service_id', $serviceId);
            })
            ->exists();

        if ($blocked) {
            return [];
        }

        // 특정 날짜의 슬롯 우선
        $specificSlots = static::query()
            ->where('specific_date', $date)
            ->where('is_blocked', false)
            ->where('is_active', true)
            ->where(function($q) use ($serviceId) {
                $q->whereNull('service_id')
                  ->orWhere('service_id', $serviceId);
            })
            ->orderBy('start_time')
            ->get();

        if (!empty($specificSlots)) {
            return array_map([static::class, 'fromArray'], $specificSlots);
        }

        // 기본 요일 슬롯
        $slots = static::query()
            ->where('day_of_week', $dayOfWeek)
            ->whereNull('specific_date')
            ->where('is_active', true)
            ->where(function($q) use ($serviceId) {
                $q->whereNull('service_id')
                  ->orWhere('service_id', $serviceId);
            })
            ->orderBy('start_time')
            ->get();

        return array_map([static::class, 'fromArray'], $slots);
    }

    /**
     * 요일별 기본 슬롯 가져오기
     */
    public static function defaultSlots(int $dayOfWeek, ?int $serviceId = null): array
    {
        $query = static::query()
            ->where('day_of_week', $dayOfWeek)
            ->whereNull('specific_date')
            ->where('is_active', true)
            ->orderBy('start_time');

        if ($serviceId !== null) {
            $query->where(function($q) use ($serviceId) {
                $q->whereNull('service_id')
                  ->orWhere('service_id', $serviceId);
            });
        }

        return array_map([static::class, 'fromArray'], $query->get());
    }

    /**
     * 특정 날짜가 차단되었는지 확인
     */
    public static function isDateBlocked(string $date, ?int $serviceId = null): bool
    {
        $query = static::query()
            ->where('specific_date', $date)
            ->where('is_blocked', true);

        if ($serviceId !== null) {
            $query->where(function($q) use ($serviceId) {
                $q->whereNull('service_id')
                  ->orWhere('service_id', $serviceId);
            });
        }

        return $query->exists();
    }

    /**
     * 날짜 차단
     */
    public static function blockDate(string $date, ?int $serviceId = null, ?string $reason = null): bool
    {
        $slot = new static();
        $slot->specific_date = $date;
        $slot->service_id = $serviceId;
        $slot->is_blocked = true;
        $slot->day_of_week = (int) date('w', strtotime($date));
        $slot->start_time = '00:00';
        $slot->end_time = '23:59';

        return $slot->save();
    }

    /**
     * 날짜 차단 해제
     */
    public static function unblockDate(string $date, ?int $serviceId = null): bool
    {
        $query = static::query()
            ->where('specific_date', $date)
            ->where('is_blocked', true);

        if ($serviceId !== null) {
            $query->where('service_id', $serviceId);
        } else {
            $query->whereNull('service_id');
        }

        return $query->delete() > 0;
    }

    /**
     * 배열에서 인스턴스 생성
     */
    public static function fromArray(array $data): static
    {
        $slot = new static();

        $slot->id = isset($data['id']) ? (int) $data['id'] : null;
        $slot->service_id = isset($data['service_id']) ? (int) $data['service_id'] : null;
        $slot->day_of_week = (int) ($data['day_of_week'] ?? 0);
        $slot->start_time = $data['start_time'] ?? '09:00';
        $slot->end_time = $data['end_time'] ?? '18:00';
        $slot->max_bookings = (int) ($data['max_bookings'] ?? 1);
        $slot->is_active = (bool) ($data['is_active'] ?? true);
        $slot->specific_date = $data['specific_date'] ?? null;
        $slot->is_blocked = (bool) ($data['is_blocked'] ?? false);
        $slot->created_at = $data['created_at'] ?? null;

        return $slot;
    }

    /**
     * 배열로 변환
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'service_id' => $this->service_id,
            'day_of_week' => $this->day_of_week,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'max_bookings' => $this->max_bookings,
            'is_active' => $this->is_active,
            'specific_date' => $this->specific_date,
            'is_blocked' => $this->is_blocked,
            'created_at' => $this->created_at,
        ];
    }

    /**
     * 저장
     */
    public function save(): bool
    {
        $data = $this->toArray();
        unset($data['id'], $data['created_at']);

        if ($this->id) {
            return static::query()
                ->where('id', $this->id)
                ->update($data) > 0;
        } else {
            $data['created_at'] = date('Y-m-d H:i:s');
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

        return static::query()->where('id', $this->id)->delete() > 0;
    }

    /**
     * 요일 라벨 반환
     */
    public function getDayLabel(): string
    {
        return static::$dayLabels[$this->day_of_week] ?? '알 수 없음';
    }

    /**
     * 시간 범위 문자열
     */
    public function getTimeRange(): string
    {
        return $this->start_time . ' - ' . $this->end_time;
    }

    /**
     * 슬롯 길이 (분)
     */
    public function getDuration(): int
    {
        $start = strtotime($this->start_time);
        $end = strtotime($this->end_time);

        return (int) (($end - $start) / 60);
    }

    /**
     * 시간대 내 세부 슬롯 생성
     */
    public function generateSlots(int $intervalMinutes): array
    {
        $slots = [];
        $start = strtotime($this->start_time);
        $end = strtotime($this->end_time);
        $interval = $intervalMinutes * 60;

        while ($start < $end) {
            $slots[] = date('H:i', $start);
            $start += $interval;
        }

        return $slots;
    }
}
