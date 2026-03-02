<?php

declare(strict_types=1);

namespace RzxLib\Reservation\Services;

use RzxLib\Reservation\Models\Reservation;
use RzxLib\Reservation\Models\Service;
use RzxLib\Reservation\Models\TimeSlot;
use RzxLib\Core\Validation\Validator;
use RzxLib\Core\Validation\ValidationException;

/**
 * ReservationService - 예약 서비스
 *
 * 예약 생성, 수정, 취소 등의 비즈니스 로직
 *
 * @package RzxLib\Reservation\Services
 */
class ReservationService
{
    /**
     * 예약 생성
     */
    public function create(array $data): Reservation
    {
        // 유효성 검사
        $validated = $this->validateReservationData($data);

        // 서비스 확인
        $service = Service::findOrFail($validated['service_id']);

        // 가용성 확인
        $this->checkAvailability(
            $service,
            $validated['booking_date'],
            $validated['start_time'],
            $validated['guests'] ?? 1
        );

        // 종료 시간 계산
        $endTime = $this->calculateEndTime($validated['start_time'], $service->duration);

        // 가격 계산
        $totalPrice = $this->calculatePrice($service, $validated['guests'] ?? 1);

        // 예약 생성
        $reservation = Reservation::fromArray([
            'booking_code' => Reservation::generateBookingCode(),
            'service_id' => $service->id,
            'user_id' => $validated['user_id'] ?? null,
            'customer_name' => $validated['customer_name'],
            'customer_email' => $validated['customer_email'],
            'customer_phone' => $validated['customer_phone'] ?? null,
            'booking_date' => $validated['booking_date'],
            'start_time' => $validated['start_time'],
            'end_time' => $endTime,
            'guests' => $validated['guests'] ?? 1,
            'total_price' => $totalPrice,
            'status' => Reservation::STATUS_PENDING,
            'notes' => $validated['notes'] ?? null,
        ]);

        if (!$reservation->save()) {
            throw new \RuntimeException('예약 저장에 실패했습니다.');
        }

        // 이벤트 발생 (추후 구현)
        // event(new ReservationCreated($reservation));

        return $reservation;
    }

    /**
     * 예약 수정
     */
    public function update(Reservation $reservation, array $data): Reservation
    {
        // 취소된 예약은 수정 불가
        if ($reservation->status === Reservation::STATUS_CANCELLED) {
            throw new \RuntimeException('취소된 예약은 수정할 수 없습니다.');
        }

        // 완료된 예약은 수정 불가
        if ($reservation->status === Reservation::STATUS_COMPLETED) {
            throw new \RuntimeException('완료된 예약은 수정할 수 없습니다.');
        }

        // 날짜/시간 변경 시 가용성 확인
        if (isset($data['booking_date']) || isset($data['start_time'])) {
            $service = Service::findOrFail($reservation->service_id);

            $bookingDate = $data['booking_date'] ?? $reservation->booking_date;
            $startTime = $data['start_time'] ?? $reservation->start_time;
            $guests = $data['guests'] ?? $reservation->guests;

            $this->checkAvailability(
                $service,
                $bookingDate,
                $startTime,
                $guests,
                $reservation->id // 자신은 제외
            );

            $reservation->booking_date = $bookingDate;
            $reservation->start_time = $startTime;
            $reservation->end_time = $this->calculateEndTime($startTime, $service->duration);
        }

        // 기타 필드 업데이트
        if (isset($data['customer_name'])) {
            $reservation->customer_name = $data['customer_name'];
        }
        if (isset($data['customer_email'])) {
            $reservation->customer_email = $data['customer_email'];
        }
        if (isset($data['customer_phone'])) {
            $reservation->customer_phone = $data['customer_phone'];
        }
        if (isset($data['guests'])) {
            $reservation->guests = (int) $data['guests'];
        }
        if (isset($data['notes'])) {
            $reservation->notes = $data['notes'];
        }
        if (isset($data['admin_notes'])) {
            $reservation->admin_notes = $data['admin_notes'];
        }

        if (!$reservation->save()) {
            throw new \RuntimeException('예약 수정에 실패했습니다.');
        }

        return $reservation;
    }

    /**
     * 예약 취소
     */
    public function cancel(Reservation $reservation, ?string $reason = null): Reservation
    {
        if (!$reservation->isCancellable()) {
            throw new \RuntimeException('이 예약은 취소할 수 없습니다.');
        }

        if (!$reservation->cancel($reason)) {
            throw new \RuntimeException('예약 취소에 실패했습니다.');
        }

        // 환불 처리 (추후 구현)
        // if ($reservation->payment_status === Reservation::PAYMENT_PAID) {
        //     $this->processRefund($reservation);
        // }

        return $reservation;
    }

    /**
     * 예약 확정
     */
    public function confirm(Reservation $reservation): Reservation
    {
        if ($reservation->status !== Reservation::STATUS_PENDING) {
            throw new \RuntimeException('대기 중인 예약만 확정할 수 있습니다.');
        }

        if (!$reservation->confirm()) {
            throw new \RuntimeException('예약 확정에 실패했습니다.');
        }

        // 확인 이메일 발송 (추후 구현)
        // $this->sendConfirmationEmail($reservation);

        return $reservation;
    }

    /**
     * 예약 완료 처리
     */
    public function complete(Reservation $reservation): Reservation
    {
        if (!$reservation->isPast()) {
            throw new \RuntimeException('아직 완료할 수 없는 예약입니다.');
        }

        if (!$reservation->complete()) {
            throw new \RuntimeException('예약 완료 처리에 실패했습니다.');
        }

        return $reservation;
    }

    /**
     * 노쇼 처리
     */
    public function markNoShow(Reservation $reservation): Reservation
    {
        if (!$reservation->isPast()) {
            throw new \RuntimeException('아직 노쇼 처리할 수 없는 예약입니다.');
        }

        if (!$reservation->markNoShow()) {
            throw new \RuntimeException('노쇼 처리에 실패했습니다.');
        }

        return $reservation;
    }

    /**
     * 가용성 확인
     */
    public function checkAvailability(
        Service $service,
        string $date,
        string $time,
        int $guests = 1,
        ?int $excludeReservationId = null
    ): bool {
        // 서비스 활성 상태 확인
        if (!$service->is_active) {
            throw new \RuntimeException('현재 예약할 수 없는 서비스입니다.');
        }

        // 날짜 가용성 확인
        if (!$service->isAvailableOn($date)) {
            throw new \RuntimeException('해당 날짜에는 예약할 수 없습니다.');
        }

        // 날짜 차단 확인
        if (TimeSlot::isDateBlocked($date, $service->id)) {
            throw new \RuntimeException('해당 날짜는 예약이 불가능합니다.');
        }

        // 시간대 확인
        $availableSlots = TimeSlot::availableForService($service->id, $date);
        $isValidTime = false;

        foreach ($availableSlots as $slot) {
            if ($time >= $slot->start_time && $time < $slot->end_time) {
                $isValidTime = true;
                break;
            }
        }

        if (!$isValidTime && !empty($availableSlots)) {
            throw new \RuntimeException('해당 시간에는 예약할 수 없습니다.');
        }

        // 기존 예약 확인
        $existingReservations = Reservation::forServiceAndDate($service->id, $date);
        $conflictCount = 0;

        foreach ($existingReservations as $existing) {
            // 자신은 제외
            if ($excludeReservationId !== null && $existing->id === $excludeReservationId) {
                continue;
            }

            // 시간 충돌 확인
            $endTime = $this->calculateEndTime($time, $service->duration);

            if ($this->timesOverlap($time, $endTime, $existing->start_time, $existing->end_time)) {
                $conflictCount += $existing->guests;
            }
        }

        // 최대 수용 인원 확인
        if ($conflictCount + $guests > $service->max_capacity) {
            throw new \RuntimeException('해당 시간대의 예약이 마감되었습니다.');
        }

        return true;
    }

    /**
     * 특정 날짜의 가용 시간대 가져오기
     */
    public function getAvailableSlots(Service $service, string $date): array
    {
        if (!$service->isAvailableOn($date)) {
            return [];
        }

        if (TimeSlot::isDateBlocked($date, $service->id)) {
            return [];
        }

        $timeSlots = TimeSlot::availableForService($service->id, $date);

        if (empty($timeSlots)) {
            // 기본 시간대 사용 (09:00 ~ 18:00)
            $timeSlots = $this->getDefaultTimeSlots();
        }

        $availableSlots = [];
        $existingReservations = Reservation::forServiceAndDate($service->id, $date);

        foreach ($timeSlots as $slot) {
            // 슬롯을 서비스 길이만큼 분할
            $times = $slot->generateSlots($service->duration + $service->buffer_time);

            foreach ($times as $time) {
                $endTime = $this->calculateEndTime($time, $service->duration);

                // 종료 시간이 슬롯을 벗어나면 스킵
                if ($endTime > $slot->end_time) {
                    continue;
                }

                // 현재 시간 이전이면 스킵 (오늘인 경우)
                if ($date === date('Y-m-d') && $time < date('H:i')) {
                    continue;
                }

                // 충돌하는 예약 수 계산
                $bookings = 0;
                foreach ($existingReservations as $existing) {
                    if ($this->timesOverlap($time, $endTime, $existing->start_time, $existing->end_time)) {
                        $bookings += $existing->guests;
                    }
                }

                // 가용 인원 계산
                $available = $service->max_capacity - $bookings;

                if ($available > 0) {
                    $availableSlots[] = [
                        'time' => $time,
                        'end_time' => $endTime,
                        'available' => $available,
                        'max_capacity' => $service->max_capacity,
                    ];
                }
            }
        }

        return $availableSlots;
    }

    /**
     * 특정 월의 가용 날짜 가져오기
     */
    public function getAvailableDates(Service $service, int $year, int $month): array
    {
        $dates = [];
        $daysInMonth = cal_days_in_month(CAL_GREGORIAN, $month, $year);
        $today = date('Y-m-d');

        for ($day = 1; $day <= $daysInMonth; $day++) {
            $date = sprintf('%04d-%02d-%02d', $year, $month, $day);

            // 과거 날짜 스킵
            if ($date < $today) {
                continue;
            }

            // 가용성 확인
            if ($service->isAvailableOn($date) && !TimeSlot::isDateBlocked($date, $service->id)) {
                $availableSlots = $this->getAvailableSlots($service, $date);

                $dates[] = [
                    'date' => $date,
                    'available' => !empty($availableSlots),
                    'slots_count' => count($availableSlots),
                ];
            }
        }

        return $dates;
    }

    /**
     * 예약 데이터 유효성 검사
     */
    protected function validateReservationData(array $data): array
    {
        $validator = new Validator($data, [
            'service_id' => 'required|integer',
            'customer_name' => 'required|string|max:100',
            'customer_email' => 'required|email',
            'customer_phone' => 'string|max:20',
            'booking_date' => 'required|date',
            'start_time' => 'required|string',
            'guests' => 'integer|min:1',
            'notes' => 'string|max:1000',
        ], [
            'service_id.required' => '서비스를 선택해주세요.',
            'customer_name.required' => '이름을 입력해주세요.',
            'customer_email.required' => '이메일을 입력해주세요.',
            'customer_email.email' => '올바른 이메일 형식이 아닙니다.',
            'booking_date.required' => '예약 날짜를 선택해주세요.',
            'start_time.required' => '예약 시간을 선택해주세요.',
        ]);

        if ($validator->fails()) {
            throw new ValidationException($validator);
        }

        return $validator->validated();
    }

    /**
     * 종료 시간 계산
     */
    protected function calculateEndTime(string $startTime, int $durationMinutes): string
    {
        $start = strtotime($startTime);
        $end = $start + ($durationMinutes * 60);

        return date('H:i', $end);
    }

    /**
     * 가격 계산
     */
    protected function calculatePrice(Service $service, int $guests): float
    {
        return $service->price * $guests;
    }

    /**
     * 시간 충돌 확인
     */
    protected function timesOverlap(
        string $start1,
        string $end1,
        string $start2,
        string $end2
    ): bool {
        return $start1 < $end2 && $end1 > $start2;
    }

    /**
     * 기본 시간대 가져오기
     */
    protected function getDefaultTimeSlots(): array
    {
        $slot = TimeSlot::fromArray([
            'start_time' => '09:00',
            'end_time' => '18:00',
            'max_bookings' => 10,
        ]);

        return [$slot];
    }

    /**
     * 통계 데이터 가져오기
     */
    public function getStatistics(string $startDate, string $endDate): array
    {
        $reservations = Reservation::query()
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->get();

        $stats = [
            'total' => count($reservations),
            'confirmed' => 0,
            'pending' => 0,
            'cancelled' => 0,
            'completed' => 0,
            'no_show' => 0,
            'total_revenue' => 0,
            'by_date' => [],
            'by_service' => [],
        ];

        foreach ($reservations as $row) {
            $reservation = Reservation::fromArray($row);

            // 상태별 카운트
            $stats[$reservation->status]++;

            // 매출 (완료 + 확정만)
            if (in_array($reservation->status, [Reservation::STATUS_CONFIRMED, Reservation::STATUS_COMPLETED])) {
                $stats['total_revenue'] += $reservation->total_price;
            }

            // 날짜별 카운트
            $date = $reservation->booking_date;
            if (!isset($stats['by_date'][$date])) {
                $stats['by_date'][$date] = 0;
            }
            $stats['by_date'][$date]++;

            // 서비스별 카운트
            $serviceId = $reservation->service_id;
            if (!isset($stats['by_service'][$serviceId])) {
                $stats['by_service'][$serviceId] = 0;
            }
            $stats['by_service'][$serviceId]++;
        }

        return $stats;
    }
}
