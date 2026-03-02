<?php

declare(strict_types=1);

namespace RzxLib\Http\Controllers;

use RzxLib\Core\Http\Controller;
use RzxLib\Core\Http\Request;
use RzxLib\Core\Http\Response;
use RzxLib\Core\Auth\Auth;
use RzxLib\Reservation\Models\Reservation;
use RzxLib\Reservation\Models\Service;
use RzxLib\Reservation\Services\ReservationService;
use RzxLib\Core\Validation\Validator;
use RzxLib\Core\Validation\ValidationException;

/**
 * MyPageController - 고객 마이페이지
 *
 * @package RzxLib\Http\Controllers
 */
class MyPageController extends Controller
{
    protected ReservationService $reservationService;

    public function __construct()
    {
        $this->reservationService = new ReservationService();
    }

    /**
     * 마이페이지 메인
     */
    public function index(Request $request): Response
    {
        $user = Auth::user();

        if (!$user) {
            return $this->redirect('/login');
        }

        // 다가오는 예약
        $upcomingReservations = Reservation::query()
            ->where('user_id', $user['id'])
            ->where('booking_date', '>=', date('Y-m-d'))
            ->whereNotIn('status', [Reservation::STATUS_CANCELLED])
            ->orderBy('booking_date')
            ->orderBy('start_time')
            ->limit(5)
            ->get();

        // 최근 예약
        $recentReservations = Reservation::query()
            ->where('user_id', $user['id'])
            ->orderBy('created_at', 'desc')
            ->limit(5)
            ->get();

        return $this->view('customer.mypage', [
            'user' => $user,
            'upcomingReservations' => array_map([Reservation::class, 'fromArray'], $upcomingReservations),
            'recentReservations' => array_map([Reservation::class, 'fromArray'], $recentReservations),
        ]);
    }

    /**
     * 예약 내역
     */
    public function reservations(Request $request): Response
    {
        $user = Auth::user();

        if (!$user) {
            return $this->redirect('/login');
        }

        $status = $request->input('status');
        $page = (int) $request->input('page', 1);
        $perPage = 10;

        $query = Reservation::query()
            ->where('user_id', $user['id'])
            ->orderBy('booking_date', 'desc')
            ->orderBy('start_time', 'desc');

        if ($status) {
            $query->where('status', $status);
        }

        // 간단한 페이지네이션
        $offset = ($page - 1) * $perPage;
        $reservations = $query->limit($perPage)->offset($offset)->get();

        // 총 개수
        $totalQuery = Reservation::query()->where('user_id', $user['id']);
        if ($status) {
            $totalQuery->where('status', $status);
        }
        $total = $totalQuery->count();
        $totalPages = (int) ceil($total / $perPage);

        return $this->view('customer.mypage-reservations', [
            'reservations' => array_map([Reservation::class, 'fromArray'], $reservations),
            'status' => $status,
            'page' => $page,
            'totalPages' => $totalPages,
            'total' => $total,
        ]);
    }

    /**
     * 예약 상세
     */
    public function reservationDetail(Request $request, int $id): Response
    {
        $user = Auth::user();

        if (!$user) {
            return $this->redirect('/login');
        }

        $reservation = Reservation::find($id);

        if (!$reservation || $reservation->user_id !== $user['id']) {
            return $this->notFound('예약을 찾을 수 없습니다.');
        }

        $service = $reservation->getService();

        return $this->view('customer.mypage-reservation-detail', [
            'reservation' => $reservation,
            'service' => $service,
        ]);
    }

    /**
     * 예약 취소
     */
    public function cancelReservation(Request $request, int $id): Response
    {
        $user = Auth::user();

        if (!$user) {
            return $this->json(['error' => true, 'message' => '로그인이 필요합니다.'], 401);
        }

        $reservation = Reservation::find($id);

        if (!$reservation || $reservation->user_id !== $user['id']) {
            return $this->json(['error' => true, 'message' => '예약을 찾을 수 없습니다.'], 404);
        }

        if (!$reservation->isCancellable()) {
            return $this->json(['error' => true, 'message' => '이 예약은 취소할 수 없습니다.']);
        }

        try {
            $reason = $request->input('reason', '고객 요청');
            $this->reservationService->cancel($reservation, $reason);

            if ($request->wantsJson()) {
                return $this->success(null, '예약이 취소되었습니다.');
            }

            return $this->redirect('/mypage/reservations')
                ->withSuccess('예약이 취소되었습니다.');

        } catch (\RuntimeException $e) {
            if ($request->wantsJson()) {
                return $this->error($e->getMessage());
            }

            return $this->back()->withError($e->getMessage());
        }
    }

    /**
     * 프로필 페이지
     */
    public function profile(Request $request): Response
    {
        $user = Auth::user();

        if (!$user) {
            return $this->redirect('/login');
        }

        return $this->view('customer.mypage-profile', [
            'user' => $user,
        ]);
    }

    /**
     * 프로필 수정
     */
    public function updateProfile(Request $request): Response
    {
        $user = Auth::user();

        if (!$user) {
            return $this->redirect('/login');
        }

        try {
            $rules = [
                'name' => 'required|string|max:100',
                'phone' => 'string|max:20',
            ];

            $validator = new Validator($request->all(), $rules, [
                'name.required' => '이름을 입력해주세요.',
            ]);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $validated = $validator->validated();

            // 사용자 정보 업데이트
            $db = \RzxLib\Core\Database\DB::connection();
            $db->table('rzx_users')
                ->where('id', $user['id'])
                ->update([
                    'name' => $validated['name'],
                    'phone' => $validated['phone'] ?? null,
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            return $this->redirect('/mypage/profile')
                ->withSuccess('프로필이 수정되었습니다.');

        } catch (ValidationException $e) {
            return $this->back()
                ->withErrors($e->errors())
                ->withInput();
        }
    }

    /**
     * 비밀번호 변경 페이지
     */
    public function password(Request $request): Response
    {
        $user = Auth::user();

        if (!$user) {
            return $this->redirect('/login');
        }

        return $this->view('customer.mypage-password', [
            'user' => $user,
        ]);
    }

    /**
     * 비밀번호 변경 처리
     */
    public function updatePassword(Request $request): Response
    {
        $user = Auth::user();

        if (!$user) {
            return $this->redirect('/login');
        }

        try {
            $rules = [
                'current_password' => 'required',
                'password' => 'required|min:8',
                'password_confirmation' => 'required|same:password',
            ];

            $messages = [
                'current_password.required' => '현재 비밀번호를 입력해주세요.',
                'password.required' => '새 비밀번호를 입력해주세요.',
                'password.min' => '비밀번호는 8자 이상이어야 합니다.',
                'password_confirmation.same' => '비밀번호 확인이 일치하지 않습니다.',
            ];

            $validator = new Validator($request->all(), $rules, $messages);

            if ($validator->fails()) {
                throw new ValidationException($validator);
            }

            $validated = $validator->validated();

            // 현재 비밀번호 확인
            if (!password_verify($validated['current_password'], $user['password'])) {
                return $this->back()
                    ->withError('현재 비밀번호가 일치하지 않습니다.')
                    ->withInput();
            }

            // 비밀번호 변경
            $db = \RzxLib\Core\Database\DB::connection();
            $db->table('rzx_users')
                ->where('id', $user['id'])
                ->update([
                    'password' => password_hash($validated['password'], PASSWORD_DEFAULT),
                    'updated_at' => date('Y-m-d H:i:s'),
                ]);

            return $this->redirect('/mypage/profile')
                ->withSuccess('비밀번호가 변경되었습니다.');

        } catch (ValidationException $e) {
            return $this->back()
                ->withErrors($e->errors())
                ->withInput();
        }
    }
}
