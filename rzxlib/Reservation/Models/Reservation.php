<?php

declare(strict_types=1);

namespace RzxLib\Reservation\Models;

use RzxLib\Core\Database\Connection;
use RzxLib\Core\Database\QueryBuilder;

/**
 * Reservation - 예약 모델
 *
 * 고객 예약 정보를 나타냄
 *
 * @package RzxLib\Reservation\Models
 */
class Reservation
{
    /**
     * 테이블명 (prefix는 Connection에서 자동 추가됨)
     */
    protected static string $table = 'reservations';

    /**
     * 예약 상태 상수
     */
    public const STATUS_PENDING = 'pending';       // 대기중
    public const STATUS_CONFIRMED = 'confirmed';   // 확정
    public const STATUS_CANCELLED = 'cancelled';   // 취소됨
    public const STATUS_COMPLETED = 'completed';   // 완료
    public const STATUS_NO_SHOW = 'no_show';       // 노쇼

    /**
     * 속성 (기존 DB 스키마에 맞춤)
     */
    public ?string $id = null;
    public string $reservation_number = ''; // 예약번호
    public string $service_id = '';
    public ?string $user_id = null;
    public string $customer_name = '';
    public string $customer_phone = '';
    public ?string $customer_email = null;
    public string $reservation_date = ''; // YYYY-MM-DD
    public string $start_time = ''; // HH:MM
    public string $end_time = ''; // HH:MM
    public float $total_amount = 0;
    public float $discount_amount = 0;
    public float $points_used = 0;
    public float $final_amount = 0;
    public string $status = self::STATUS_PENDING;
    public ?string $notes = null;
    public ?string $admin_notes = null;
    public ?string $cancelled_at = null;
    public ?string $cancel_reason = null;
    public ?string $created_at = null;
    public ?string $updated_at = null;

    /**
     * 상태 라벨
     */
    protected static array $statusLabels = [
        self::STATUS_PENDING => '대기중',
        self::STATUS_CONFIRMED => '확정',
        self::STATUS_CANCELLED => '취소됨',
        self::STATUS_COMPLETED => '완료',
        self::STATUS_NO_SHOW => '노쇼',
    ];

    /**
     * 상태 색상 (Tailwind)
     */
    protected static array $statusColors = [
        self::STATUS_PENDING => 'yellow',
        self::STATUS_CONFIRMED => 'blue',
        self::STATUS_CANCELLED => 'red',
        self::STATUS_COMPLETED => 'green',
        self::STATUS_NO_SHOW => 'zinc',
    ];

    /**
     * QueryBuilder 인스턴스 반환
     */
    public static function query(): QueryBuilder
    {
        return Connection::getInstance()->table(static::$table);
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
     * 예약번호로 찾기
     */
    public static function findByReservationNumber(string $reservationNumber): ?static
    {
        $data = static::query()->where('reservation_number', $reservationNumber)->first();

        return $data ? static::fromArray($data) : null;
    }

    /**
     * 예약번호와 이메일로 찾기 (비회원 조회용)
     */
    public static function findByNumberAndEmail(string $reservationNumber, string $email): ?static
    {
        $data = static::query()
            ->where('reservation_number', $reservationNumber)
            ->where('customer_email', $email)
            ->first();

        return $data ? static::fromArray($data) : null;
    }

    /**
     * 예약번호와 전화번호로 찾기 (비회원 조회용)
     */
    public static function findByNumberAndPhone(string $reservationNumber, string $phone): ?static
    {
        $data = static::query()
            ->where('reservation_number', $reservationNumber)
            ->where('customer_phone', $phone)
            ->first();

        return $data ? static::fromArray($data) : null;
    }

    /**
     * 특정 날짜의 예약 가져오기
     */
    public static function forDate(string $date): array
    {
        $data = static::query()
            ->where('reservation_date', $date)
            ->whereNotIn('status', [self::STATUS_CANCELLED])
            ->orderBy('start_time')
            ->get();

        return array_map([static::class, 'fromArray'], $data);
    }

    /**
     * 특정 서비스의 특정 날짜 예약 가져오기
     */
    public static function forServiceAndDate(string $serviceId, string $date): array
    {
        $data = static::query()
            ->where('service_id', $serviceId)
            ->where('reservation_date', $date)
            ->whereNotIn('status', [self::STATUS_CANCELLED])
            ->orderBy('start_time')
            ->get();

        return array_map([static::class, 'fromArray'], $data);
    }

    /**
     * 사용자의 예약 가져오기
     */
    public static function forUser(string $userId, ?string $status = null): array
    {
        $query = static::query()
            ->where('user_id', $userId)
            ->orderBy('reservation_date', 'desc')
            ->orderBy('start_time', 'desc');

        if ($status !== null) {
            $query->where('status', $status);
        }

        return array_map([static::class, 'fromArray'], $query->get());
    }

    /**
     * 다가오는 예약 가져오기
     */
    public static function upcoming(int $limit = 10): array
    {
        $data = static::query()
            ->where('reservation_date', '>=', date('Y-m-d'))
            ->whereIn('status', [self::STATUS_PENDING, self::STATUS_CONFIRMED])
            ->orderBy('reservation_date')
            ->orderBy('start_time')
            ->limit($limit)
            ->get();

        return array_map([static::class, 'fromArray'], $data);
    }

    /**
     * 오늘 예약 가져오기
     */
    public static function today(): array
    {
        return static::forDate(date('Y-m-d'));
    }

    /**
     * 예약 번호 생성
     */
    public static function generateReservationNumber(): string
    {
        $prefix = 'RZ';
        $date = date('ymd');
        $random = strtoupper(bin2hex(random_bytes(3)));

        return $prefix . $date . $random;
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
     * 배열에서 인스턴스 생성
     */
    public static function fromArray(array $data): static
    {
        $reservation = new static();

        $reservation->id = $data['id'] ?? null;
        $reservation->reservation_number = $data['reservation_number'] ?? static::generateReservationNumber();
        $reservation->service_id = $data['service_id'] ?? '';
        $reservation->user_id = $data['user_id'] ?? null;
        $reservation->customer_name = $data['customer_name'] ?? '';
        $reservation->customer_phone = $data['customer_phone'] ?? '';
        $reservation->customer_email = $data['customer_email'] ?? null;
        $reservation->reservation_date = $data['reservation_date'] ?? date('Y-m-d');
        $reservation->start_time = $data['start_time'] ?? '';
        $reservation->end_time = $data['end_time'] ?? '';
        $reservation->total_amount = (float) ($data['total_amount'] ?? 0);
        $reservation->discount_amount = (float) ($data['discount_amount'] ?? 0);
        $reservation->points_used = (float) ($data['points_used'] ?? 0);
        $reservation->final_amount = (float) ($data['final_amount'] ?? 0);
        $reservation->status = $data['status'] ?? self::STATUS_PENDING;
        $reservation->notes = $data['notes'] ?? null;
        $reservation->admin_notes = $data['admin_notes'] ?? null;
        $reservation->cancelled_at = $data['cancelled_at'] ?? null;
        $reservation->cancel_reason = $data['cancel_reason'] ?? null;
        $reservation->created_at = $data['created_at'] ?? null;
        $reservation->updated_at = $data['updated_at'] ?? null;

        return $reservation;
    }

    /**
     * 배열로 변환
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'reservation_number' => $this->reservation_number,
            'service_id' => $this->service_id,
            'user_id' => $this->user_id,
            'customer_name' => $this->customer_name,
            'customer_phone' => $this->customer_phone,
            'customer_email' => $this->customer_email,
            'reservation_date' => $this->reservation_date,
            'start_time' => $this->start_time,
            'end_time' => $this->end_time,
            'total_amount' => $this->total_amount,
            'discount_amount' => $this->discount_amount,
            'points_used' => $this->points_used,
            'final_amount' => $this->final_amount,
            'status' => $this->status,
            'notes' => $this->notes,
            'admin_notes' => $this->admin_notes,
            'cancelled_at' => $this->cancelled_at,
            'cancel_reason' => $this->cancel_reason,
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
        unset($data['created_at'], $data['updated_at']);

        if ($this->id) {
            unset($data['id']);
            $data['updated_at'] = date('Y-m-d H:i:s');

            return static::query()
                ->where('id', $this->id)
                ->update($data) > 0;
        } else {
            $data['id'] = static::generateUuid();
            $data['created_at'] = date('Y-m-d H:i:s');
            $data['updated_at'] = date('Y-m-d H:i:s');

            if (empty($data['reservation_number'])) {
                $data['reservation_number'] = static::generateReservationNumber();
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
     * 예약 확정
     */
    public function confirm(): bool
    {
        $this->status = self::STATUS_CONFIRMED;

        return $this->save();
    }

    /**
     * 예약 취소
     */
    public function cancel(?string $reason = null): bool
    {
        $this->status = self::STATUS_CANCELLED;
        $this->cancelled_at = date('Y-m-d H:i:s');
        $this->cancel_reason = $reason;

        return $this->save();
    }

    /**
     * 예약 완료
     */
    public function complete(): bool
    {
        $this->status = self::STATUS_COMPLETED;

        return $this->save();
    }

    /**
     * 노쇼 처리
     */
    public function markNoShow(): bool
    {
        $this->status = self::STATUS_NO_SHOW;

        return $this->save();
    }

    /**
     * 상태 라벨 반환
     */
    public function getStatusLabel(): string
    {
        return static::$statusLabels[$this->status] ?? '알 수 없음';
    }

    /**
     * 상태 색상 반환
     */
    public function getStatusColor(): string
    {
        return static::$statusColors[$this->status] ?? 'zinc';
    }

    /**
     * 취소 가능 여부
     */
    public function isCancellable(): bool
    {
        // 이미 취소/완료된 예약은 취소 불가
        if (in_array($this->status, [self::STATUS_CANCELLED, self::STATUS_COMPLETED, self::STATUS_NO_SHOW])) {
            return false;
        }

        // 예약 시간이 지났으면 취소 불가
        $reservationDateTime = $this->reservation_date . ' ' . $this->start_time;
        if (strtotime($reservationDateTime) < time()) {
            return false;
        }

        return true;
    }

    /**
     * 서비스 정보 가져오기
     */
    public function getService(): ?Service
    {
        return Service::find($this->service_id);
    }

    /**
     * 예약 일시 포맷팅
     */
    public function getFormattedDateTime(): string
    {
        $date = date('Y년 m월 d일', strtotime($this->reservation_date));
        $dayOfWeek = ['일', '월', '화', '수', '목', '금', '토'][date('w', strtotime($this->reservation_date))];

        return "{$date} ({$dayOfWeek}) {$this->start_time}";
    }

    /**
     * 가격 포맷팅
     */
    public function getFormattedPrice(): string
    {
        return number_format($this->final_amount) . '원';
    }

    /**
     * 총액 포맷팅
     */
    public function getFormattedTotalAmount(): string
    {
        return number_format($this->total_amount) . '원';
    }

    /**
     * 지난 예약인지 확인
     */
    public function isPast(): bool
    {
        $reservationDateTime = $this->reservation_date . ' ' . $this->end_time;
        return strtotime($reservationDateTime) < time();
    }

    /**
     * 오늘 예약인지 확인
     */
    public function isToday(): bool
    {
        return $this->reservation_date === date('Y-m-d');
    }
}
