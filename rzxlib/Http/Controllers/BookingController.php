<?php

declare(strict_types=1);

namespace RzxLib\Http\Controllers;

use RzxLib\Core\Http\Controller;
use RzxLib\Core\Http\Request;
use RzxLib\Core\Http\Response;
use RzxLib\Reservation\Models\Service;
use RzxLib\Reservation\Models\ServiceCategory;
use RzxLib\Reservation\Models\Reservation;
use RzxLib\Reservation\Services\ReservationService;
use RzxLib\Core\Validation\ValidationException;

/**
 * BookingController - 고객 예약 처리
 *
 * @package RzxLib\Http\Controllers
 */
class BookingController extends Controller
{
    protected ReservationService $reservationService;

    public function __construct()
    {
        $this->reservationService = new ReservationService();
    }

    /**
     * 예약 메인 페이지 - 서비스 목록
     */
    public function index(Request $request): Response
    {
        $categoryId = $request->input('category');

        $categories = ServiceCategory::active();

        if ($categoryId) {
            $services = Service::byCategory((int) $categoryId);
        } else {
            $services = Service::active();
        }

        return $this->view('customer.booking.index', [
            'categories' => $categories,
            'services' => $services,
            'selectedCategory' => $categoryId,
        ]);
    }

    /**
     * 서비스 상세 페이지
     */
    public function service(Request $request, int $id): Response
    {
        $service = Service::find($id);

        if (!$service || !$service->is_active) {
            return $this->notFound('서비스를 찾을 수 없습니다.');
        }

        $category = $service->category_id ? ServiceCategory::find($service->category_id) : null;

        return $this->view('customer.booking.service', [
            'service' => $service,
            'category' => $category,
        ]);
    }

    /**
     * 예약 폼 페이지
     */
    public function form(Request $request, int $serviceId): Response
    {
        $service = Service::find($serviceId);

        if (!$service || !$service->is_active) {
            return $this->notFound('서비스를 찾을 수 없습니다.');
        }

        $date = $request->input('date', date('Y-m-d'));

        // 가용 날짜 목록 (이번 달 + 다음 달)
        $year = (int) date('Y');
        $month = (int) date('m');

        $availableDates = $this->reservationService->getAvailableDates($service, $year, $month);

        // 다음 달 추가
        $nextMonth = $month + 1;
        $nextYear = $year;
        if ($nextMonth > 12) {
            $nextMonth = 1;
            $nextYear++;
        }
        $nextMonthDates = $this->reservationService->getAvailableDates($service, $nextYear, $nextMonth);
        $availableDates = array_merge($availableDates, $nextMonthDates);

        // 선택된 날짜의 가용 시간대
        $availableSlots = [];
        if ($date) {
            $availableSlots = $this->reservationService->getAvailableSlots($service, $date);
        }

        return $this->view('customer.booking.form', [
            'service' => $service,
            'date' => $date,
            'availableDates' => $availableDates,
            'availableSlots' => $availableSlots,
        ]);
    }

    /**
     * 예약 생성 처리
     */
    public function store(Request $request): Response
    {
        try {
            $reservation = $this->reservationService->create($request->all());

            // 예약 완료 페이지로 리다이렉트
            return $this->redirect('/booking/complete/' . $reservation->booking_code)
                ->withSuccess('예약이 완료되었습니다.');

        } catch (ValidationException $e) {
            return $this->back()
                ->withErrors($e->errors())
                ->withInput();

        } catch (\RuntimeException $e) {
            return $this->back()
                ->withError($e->getMessage())
                ->withInput();
        }
    }

    /**
     * 예약 완료 페이지
     */
    public function complete(Request $request, string $bookingCode): Response
    {
        $reservation = Reservation::findByCode($bookingCode);

        if (!$reservation) {
            return $this->notFound('예약을 찾을 수 없습니다.');
        }

        $service = $reservation->getService();

        return $this->view('customer.booking.complete', [
            'reservation' => $reservation,
            'service' => $service,
        ]);
    }

    /**
     * 예약 조회 페이지
     */
    public function lookup(Request $request): Response
    {
        return $this->view('customer.booking.lookup');
    }

    /**
     * 예약 조회 처리
     */
    public function find(Request $request): Response
    {
        $bookingCode = $request->input('booking_code');
        $email = $request->input('email');

        if (!$bookingCode || !$email) {
            return $this->back()
                ->withError('예약번호와 이메일을 입력해주세요.')
                ->withInput();
        }

        $reservation = Reservation::findByCode($bookingCode);

        if (!$reservation || $reservation->customer_email !== $email) {
            return $this->back()
                ->withError('예약 정보를 찾을 수 없습니다. 입력 정보를 확인해주세요.')
                ->withInput();
        }

        return $this->redirect('/booking/detail/' . $bookingCode);
    }

    /**
     * 예약 상세 페이지
     */
    public function detail(Request $request, string $bookingCode): Response
    {
        $reservation = Reservation::findByCode($bookingCode);

        if (!$reservation) {
            return $this->notFound('예약을 찾을 수 없습니다.');
        }

        $service = $reservation->getService();

        return $this->view('customer.booking.detail', [
            'reservation' => $reservation,
            'service' => $service,
        ]);
    }

    /**
     * 예약 취소 페이지
     */
    public function cancelForm(Request $request, string $bookingCode): Response
    {
        $reservation = Reservation::findByCode($bookingCode);

        if (!$reservation) {
            return $this->notFound('예약을 찾을 수 없습니다.');
        }

        if (!$reservation->isCancellable()) {
            return $this->back()->withError('이 예약은 취소할 수 없습니다.');
        }

        $service = $reservation->getService();

        return $this->view('customer.booking.cancel', [
            'reservation' => $reservation,
            'service' => $service,
        ]);
    }

    /**
     * 예약 취소 처리
     */
    public function cancel(Request $request, string $bookingCode): Response
    {
        $reservation = Reservation::findByCode($bookingCode);

        if (!$reservation) {
            return $this->notFound('예약을 찾을 수 없습니다.');
        }

        // 이메일 확인
        $email = $request->input('email');
        if ($reservation->customer_email !== $email) {
            return $this->back()
                ->withError('이메일이 일치하지 않습니다.')
                ->withInput();
        }

        if (!$reservation->isCancellable()) {
            return $this->back()->withError('이 예약은 취소할 수 없습니다.');
        }

        try {
            $reason = $request->input('reason', '고객 요청');
            $this->reservationService->cancel($reservation, $reason);

            return $this->redirect('/booking/lookup')
                ->withSuccess('예약이 취소되었습니다.');

        } catch (\RuntimeException $e) {
            return $this->back()->withError($e->getMessage());
        }
    }
}
