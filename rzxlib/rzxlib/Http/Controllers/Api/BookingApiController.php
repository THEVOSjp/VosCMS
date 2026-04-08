<?php

declare(strict_types=1);

namespace RzxLib\Http\Controllers\Api;

use RzxLib\Core\Http\Controller;
use RzxLib\Core\Http\Request;
use RzxLib\Core\Http\Response;
use RzxLib\Reservation\Models\Service;
use RzxLib\Reservation\Models\ServiceCategory;
use RzxLib\Reservation\Models\Reservation;
use RzxLib\Reservation\Services\ReservationService;
use RzxLib\Core\Validation\ValidationException;

/**
 * BookingApiController - 예약 API
 *
 * @package RzxLib\Http\Controllers\Api
 */
class BookingApiController extends Controller
{
    protected ReservationService $reservationService;

    public function __construct()
    {
        $this->reservationService = new ReservationService();
    }

    /**
     * 서비스 목록 API
     */
    public function services(Request $request): Response
    {
        $categoryId = $request->input('category_id');

        if ($categoryId) {
            $services = Service::byCategory((int) $categoryId);
        } else {
            $services = Service::active();
        }

        return $this->json([
            'success' => true,
            'data' => array_map(fn($s) => $s->toArray(), $services),
        ]);
    }

    /**
     * 서비스 상세 API
     */
    public function service(Request $request, int $id): Response
    {
        $service = Service::find($id);

        if (!$service || !$service->is_active) {
            return $this->json([
                'success' => false,
                'message' => '서비스를 찾을 수 없습니다.',
            ], 404);
        }

        $category = $service->category_id ? ServiceCategory::find($service->category_id) : null;

        return $this->json([
            'success' => true,
            'data' => [
                'service' => $service->toArray(),
                'category' => $category?->toArray(),
            ],
        ]);
    }

    /**
     * 가용 날짜 API
     */
    public function availableDates(Request $request, int $serviceId): Response
    {
        $service = Service::find($serviceId);

        if (!$service || !$service->is_active) {
            return $this->json([
                'success' => false,
                'message' => '서비스를 찾을 수 없습니다.',
            ], 404);
        }

        $year = (int) $request->input('year', date('Y'));
        $month = (int) $request->input('month', date('m'));

        $dates = $this->reservationService->getAvailableDates($service, $year, $month);

        return $this->json([
            'success' => true,
            'data' => [
                'year' => $year,
                'month' => $month,
                'dates' => $dates,
            ],
        ]);
    }

    /**
     * 가용 시간대 API
     */
    public function availableSlots(Request $request, int $serviceId): Response
    {
        $service = Service::find($serviceId);

        if (!$service || !$service->is_active) {
            return $this->json([
                'success' => false,
                'message' => '서비스를 찾을 수 없습니다.',
            ], 404);
        }

        $date = $request->input('date');

        if (!$date) {
            return $this->json([
                'success' => false,
                'message' => '날짜를 입력해주세요.',
            ], 400);
        }

        // 날짜 유효성 검사
        if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $date)) {
            return $this->json([
                'success' => false,
                'message' => '올바른 날짜 형식이 아닙니다. (YYYY-MM-DD)',
            ], 400);
        }

        $slots = $this->reservationService->getAvailableSlots($service, $date);

        return $this->json([
            'success' => true,
            'data' => [
                'date' => $date,
                'service_id' => $serviceId,
                'slots' => $slots,
            ],
        ]);
    }

    /**
     * 예약 생성 API
     */
    public function createReservation(Request $request): Response
    {
        try {
            $reservation = $this->reservationService->create($request->all());

            return $this->json([
                'success' => true,
                'message' => '예약이 완료되었습니다.',
                'data' => [
                    'booking_code' => $reservation->booking_code,
                    'booking_date' => $reservation->booking_date,
                    'start_time' => $reservation->start_time,
                    'end_time' => $reservation->end_time,
                    'status' => $reservation->status,
                    'total_price' => $reservation->total_price,
                ],
            ], 201);

        } catch (ValidationException $e) {
            return $this->json([
                'success' => false,
                'message' => '입력값을 확인해주세요.',
                'errors' => $e->errors(),
            ], 422);

        } catch (\RuntimeException $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 예약 조회 API
     */
    public function getReservation(Request $request, string $bookingCode): Response
    {
        $reservation = Reservation::findByCode($bookingCode);

        if (!$reservation) {
            return $this->json([
                'success' => false,
                'message' => '예약을 찾을 수 없습니다.',
            ], 404);
        }

        // 이메일 인증 (선택)
        $email = $request->input('email');
        if ($email && $reservation->customer_email !== $email) {
            return $this->json([
                'success' => false,
                'message' => '예약 정보를 찾을 수 없습니다.',
            ], 404);
        }

        $service = $reservation->getService();

        return $this->json([
            'success' => true,
            'data' => [
                'reservation' => $reservation->toArray(),
                'service' => $service?->toArray(),
            ],
        ]);
    }

    /**
     * 예약 취소 API
     */
    public function cancelReservation(Request $request, string $bookingCode): Response
    {
        $reservation = Reservation::findByCode($bookingCode);

        if (!$reservation) {
            return $this->json([
                'success' => false,
                'message' => '예약을 찾을 수 없습니다.',
            ], 404);
        }

        // 이메일 확인
        $email = $request->input('email');
        if (!$email || $reservation->customer_email !== $email) {
            return $this->json([
                'success' => false,
                'message' => '이메일이 일치하지 않습니다.',
            ], 403);
        }

        if (!$reservation->isCancellable()) {
            return $this->json([
                'success' => false,
                'message' => '이 예약은 취소할 수 없습니다.',
            ], 400);
        }

        try {
            $reason = $request->input('reason', '고객 요청');
            $this->reservationService->cancel($reservation, $reason);

            return $this->json([
                'success' => true,
                'message' => '예약이 취소되었습니다.',
                'data' => [
                    'booking_code' => $reservation->booking_code,
                    'status' => $reservation->status,
                ],
            ]);

        } catch (\RuntimeException $e) {
            return $this->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * 카테고리 목록 API
     */
    public function categories(Request $request): Response
    {
        $categories = ServiceCategory::active();

        return $this->json([
            'success' => true,
            'data' => array_map(fn($c) => $c->toArray(), $categories),
        ]);
    }
}
