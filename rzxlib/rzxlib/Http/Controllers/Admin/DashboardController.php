<?php

declare(strict_types=1);

namespace RzxLib\Http\Controllers\Admin;

use RzxLib\Core\Http\Controller;
use RzxLib\Core\Http\Request;
use RzxLib\Core\Http\Response;
use RzxLib\Reservation\Models\Reservation;
use RzxLib\Reservation\Models\Service;
use RzxLib\Reservation\Services\ReservationService;

/**
 * DashboardController - 관리자 대시보드
 *
 * @package RzxLib\Http\Controllers\Admin
 */
class DashboardController extends Controller
{
    protected ReservationService $reservationService;

    public function __construct()
    {
        $this->reservationService = new ReservationService();
    }

    /**
     * 대시보드 메인
     */
    public function index(Request $request): Response
    {
        // 오늘 예약
        $todayReservations = Reservation::today();

        // 다가오는 예약
        $upcomingReservations = Reservation::upcoming(5);

        // 이번 달 통계
        $startOfMonth = date('Y-m-01');
        $endOfMonth = date('Y-m-t');
        $monthlyStats = $this->reservationService->getStatistics($startOfMonth, $endOfMonth);

        // 오늘 통계
        $todayStats = $this->reservationService->getStatistics(date('Y-m-d'), date('Y-m-d'));

        // 활성 서비스 수
        $activeServices = count(Service::active());

        return $this->view('admin.dashboard', [
            'todayReservations' => $todayReservations,
            'upcomingReservations' => $upcomingReservations,
            'monthlyStats' => $monthlyStats,
            'todayStats' => $todayStats,
            'activeServices' => $activeServices,
        ]);
    }

    /**
     * 빠른 통계 API
     */
    public function stats(Request $request): Response
    {
        $period = $request->input('period', 'month');

        switch ($period) {
            case 'today':
                $start = $end = date('Y-m-d');
                break;
            case 'week':
                $start = date('Y-m-d', strtotime('monday this week'));
                $end = date('Y-m-d', strtotime('sunday this week'));
                break;
            case 'year':
                $start = date('Y-01-01');
                $end = date('Y-12-31');
                break;
            default: // month
                $start = date('Y-m-01');
                $end = date('Y-m-t');
        }

        $stats = $this->reservationService->getStatistics($start, $end);

        return $this->json([
            'period' => $period,
            'start' => $start,
            'end' => $end,
            'stats' => $stats,
        ]);
    }
}
