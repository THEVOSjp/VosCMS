<?php

declare(strict_types=1);

namespace RzxLib\Http\Controllers\Admin;

use RzxLib\Core\Http\Controller;
use RzxLib\Core\Http\Request;
use RzxLib\Core\Http\Response;
use RzxLib\Reservation\Models\Reservation;
use RzxLib\Reservation\Models\Service;
use RzxLib\Reservation\Services\ReservationService;
use RzxLib\Core\Validation\ValidationException;

/**
 * ReservationController - 예약 관리
 *
 * @package RzxLib\Http\Controllers\Admin
 */
class ReservationController extends Controller
{
    protected ReservationService $reservationService;

    public function __construct()
    {
        $this->reservationService = new ReservationService();
    }

    /**
     * POS (Point of Sale)
     */
    public function pos(Request $request): Response
    {
        $services = Service::active();

        return $this->view('admin.reservations.pos', [
            'services' => $services,
        ]);
    }

    /**
     * 예약 목록
     */
    public function index(Request $request): Response
    {
        $date = $request->input('date');
        $status = $request->input('status');
        $serviceId = $request->input('service_id');

        $query = Reservation::query()->orderBy('booking_date', 'desc')->orderBy('start_time', 'desc');

        if ($date) {
            $query->where('booking_date', $date);
        }

        if ($status) {
            $query->where('status', $status);
        }

        if ($serviceId) {
            $query->where('service_id', $serviceId);
        }

        $reservations = $query->limit(100)->get();
        $services = Service::active();

        return $this->view('admin.reservations.index', [
            'reservations' => array_map([Reservation::class, 'fromArray'], $reservations),
            'services' => $services,
            'filters' => [
                'date' => $date,
                'status' => $status,
                'service_id' => $serviceId,
            ],
        ]);
    }

    /**
     * 예약 상세
     */
    public function show(Request $request, int $id): Response
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return $this->notFound('예약을 찾을 수 없습니다.');
        }

        return $this->view('admin.reservations.show', [
            'reservation' => $reservation,
            'service' => $reservation->getService(),
        ]);
    }

    /**
     * 예약 생성 폼
     */
    public function create(Request $request): Response
    {
        $services = Service::active();

        return $this->view('admin.reservations.create', [
            'services' => $services,
        ]);
    }

    /**
     * 예약 생성 처리
     */
    public function store(Request $request): Response
    {
        try {
            $reservation = $this->reservationService->create($request->all());

            return $this->redirect('/admin/reservations/' . $reservation->id)
                ->withSuccess('예약이 생성되었습니다. 예약번호: ' . $reservation->booking_code);

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
     * 예약 수정 폼
     */
    public function edit(Request $request, int $id): Response
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return $this->notFound('예약을 찾을 수 없습니다.');
        }

        $services = Service::active();

        return $this->view('admin.reservations.edit', [
            'reservation' => $reservation,
            'services' => $services,
        ]);
    }

    /**
     * 예약 수정 처리
     */
    public function update(Request $request, int $id): Response
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return $this->notFound('예약을 찾을 수 없습니다.');
        }

        try {
            $this->reservationService->update($reservation, $request->all());

            return $this->redirect('/admin/reservations/' . $id)
                ->withSuccess('예약이 수정되었습니다.');

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
     * 예약 확정
     */
    public function confirm(Request $request, int $id): Response
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return $this->json(['error' => true, 'message' => '예약을 찾을 수 없습니다.'], 404);
        }

        try {
            $this->reservationService->confirm($reservation);

            if ($request->wantsJson()) {
                return $this->success(null, '예약이 확정되었습니다.');
            }

            return $this->redirect('/admin/reservations/' . $id)
                ->withSuccess('예약이 확정되었습니다.');

        } catch (\RuntimeException $e) {
            if ($request->wantsJson()) {
                return $this->error($e->getMessage());
            }

            return $this->back()->withError($e->getMessage());
        }
    }

    /**
     * 예약 취소
     */
    public function cancel(Request $request, int $id): Response
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return $this->json(['error' => true, 'message' => '예약을 찾을 수 없습니다.'], 404);
        }

        try {
            $reason = $request->input('reason', '관리자에 의한 취소');
            $this->reservationService->cancel($reservation, $reason);

            if ($request->wantsJson()) {
                return $this->success(null, '예약이 취소되었습니다.');
            }

            return $this->redirect('/admin/reservations')
                ->withSuccess('예약이 취소되었습니다.');

        } catch (\RuntimeException $e) {
            if ($request->wantsJson()) {
                return $this->error($e->getMessage());
            }

            return $this->back()->withError($e->getMessage());
        }
    }

    /**
     * 예약 완료 처리
     */
    public function complete(Request $request, int $id): Response
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return $this->json(['error' => true, 'message' => '예약을 찾을 수 없습니다.'], 404);
        }

        try {
            $this->reservationService->complete($reservation);

            if ($request->wantsJson()) {
                return $this->success(null, '예약이 완료 처리되었습니다.');
            }

            return $this->redirect('/admin/reservations/' . $id)
                ->withSuccess('예약이 완료 처리되었습니다.');

        } catch (\RuntimeException $e) {
            if ($request->wantsJson()) {
                return $this->error($e->getMessage());
            }

            return $this->back()->withError($e->getMessage());
        }
    }

    /**
     * 노쇼 처리
     */
    public function noShow(Request $request, int $id): Response
    {
        $reservation = Reservation::find($id);

        if (!$reservation) {
            return $this->json(['error' => true, 'message' => '예약을 찾을 수 없습니다.'], 404);
        }

        try {
            $this->reservationService->markNoShow($reservation);

            if ($request->wantsJson()) {
                return $this->success(null, '노쇼 처리되었습니다.');
            }

            return $this->redirect('/admin/reservations/' . $id)
                ->withSuccess('노쇼 처리되었습니다.');

        } catch (\RuntimeException $e) {
            if ($request->wantsJson()) {
                return $this->error($e->getMessage());
            }

            return $this->back()->withError($e->getMessage());
        }
    }

    /**
     * 캘린더 뷰
     */
    public function calendar(Request $request): Response
    {
        $year = (int) $request->input('year', date('Y'));
        $month = (int) $request->input('month', date('m'));

        $startDate = sprintf('%04d-%02d-01', $year, $month);
        $endDate = date('Y-m-t', strtotime($startDate));

        $reservations = Reservation::query()
            ->whereBetween('booking_date', [$startDate, $endDate])
            ->whereNotIn('status', [Reservation::STATUS_CANCELLED])
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->get();

        // 날짜별 그룹화
        $grouped = [];
        foreach ($reservations as $row) {
            $r = Reservation::fromArray($row);
            $date = $r->booking_date;

            if (!isset($grouped[$date])) {
                $grouped[$date] = [];
            }
            $grouped[$date][] = $r;
        }

        return $this->view('admin.reservations.calendar', [
            'year' => $year,
            'month' => $month,
            'reservations' => $grouped,
        ]);
    }

    /**
     * 통계 페이지
     */
    public function statistics(Request $request): Response
    {
        $startDate = $request->input('start', date('Y-m-01'));
        $endDate = $request->input('end', date('Y-m-t'));

        $stats = $this->reservationService->getStatistics($startDate, $endDate);

        return $this->view('admin.reservations.statistics', [
            'stats' => $stats,
            'startDate' => $startDate,
            'endDate' => $endDate,
        ]);
    }
}
