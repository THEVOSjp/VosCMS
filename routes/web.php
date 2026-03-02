<?php

declare(strict_types=1);

/**
 * Web Routes
 *
 * 웹 애플리케이션 라우트 정의
 */

use RzxLib\Core\Routing\Router;

/** @var Router $router */

// ============================================================================
// 홈 & 공개 페이지
// ============================================================================

$router->get('/', function () {
    return view('customer.home');
})->name('home');

$router->get('/about', function () {
    return view('customer.about');
})->name('about');

$router->get('/contact', function () {
    return view('customer.contact');
})->name('contact');

// ============================================================================
// 서비스 페이지
// ============================================================================

$router->get('/services', function () {
    return view('customer.services');
})->name('services.index');

$router->get('/services/{id}', function ($id) {
    return view('customer.services.detail', ['routeParams' => ['id' => $id]]);
})->where('id', '\d+')->name('services.detail');

// ============================================================================
// 예약 시스템 (고객)
// ============================================================================

$router->group(['prefix' => '/booking'], function (Router $router) {
    // 서비스 목록
    $router->get('/', 'BookingController@index')->name('booking.index');

    // 서비스 상세
    $router->get('/service/{id}', 'BookingController@service')
        ->where('id', '\d+')
        ->name('booking.service');

    // 예약 폼
    $router->get('/form/{serviceId}', 'BookingController@form')
        ->where('serviceId', '\d+')
        ->name('booking.form');

    // 예약 생성
    $router->post('/store', 'BookingController@store')
        ->middleware('csrf')
        ->name('booking.store');

    // 예약 완료
    $router->get('/complete/{bookingCode}', 'BookingController@complete')
        ->name('booking.complete');

    // 예약 조회
    $router->get('/lookup', 'BookingController@lookup')->name('booking.lookup');
    $router->post('/find', 'BookingController@find')
        ->middleware('csrf')
        ->name('booking.find');

    // 예약 상세
    $router->get('/detail/{bookingCode}', 'BookingController@detail')
        ->name('booking.detail');

    // 예약 취소
    $router->get('/cancel/{bookingCode}', 'BookingController@cancelForm')
        ->name('booking.cancel.form');
    $router->post('/cancel/{bookingCode}', 'BookingController@cancel')
        ->middleware('csrf')
        ->name('booking.cancel');
});

// ============================================================================
// 인증
// ============================================================================

$router->group(['prefix' => '/auth', 'middleware' => 'guest'], function (Router $router) {
    // 로그인
    $router->get('/login', 'AuthController@loginForm')->name('login');
    $router->post('/login', 'AuthController@login')->middleware('csrf');

    // 회원가입
    $router->get('/register', 'AuthController@registerForm')->name('register');
    $router->post('/register', 'AuthController@register')->middleware('csrf');

    // 비밀번호 찾기
    $router->get('/forgot-password', 'AuthController@forgotPasswordForm')
        ->name('password.forgot');
    $router->post('/forgot-password', 'AuthController@forgotPassword')
        ->middleware('csrf');

    // 비밀번호 재설정
    $router->get('/reset-password/{token}', 'AuthController@resetPasswordForm')
        ->name('password.reset');
    $router->post('/reset-password', 'AuthController@resetPassword')
        ->middleware('csrf');
});

// 로그아웃 (인증 필요)
$router->post('/auth/logout', 'AuthController@logout')
    ->middleware(['auth', 'csrf'])
    ->name('logout');

// ============================================================================
// 마이페이지 (인증 필요)
// ============================================================================

$router->group(['prefix' => '/mypage', 'middleware' => 'auth'], function (Router $router) {
    // 메인
    $router->get('/', 'MyPageController@index')->name('mypage.index');

    // 예약 내역
    $router->get('/reservations', 'MyPageController@reservations')
        ->name('mypage.reservations');
    $router->get('/reservations/{id}', 'MyPageController@reservationDetail')
        ->where('id', '\d+')
        ->name('mypage.reservation.detail');
    $router->post('/reservations/{id}/cancel', 'MyPageController@cancelReservation')
        ->where('id', '\d+')
        ->middleware('csrf')
        ->name('mypage.reservation.cancel');

    // 프로필
    $router->get('/profile', 'MyPageController@profile')->name('mypage.profile');
    $router->post('/profile', 'MyPageController@updateProfile')
        ->middleware('csrf')
        ->name('mypage.profile.update');

    // 비밀번호 변경
    $router->get('/password', 'MyPageController@password')->name('mypage.password');
    $router->post('/password', 'MyPageController@updatePassword')
        ->middleware('csrf')
        ->name('mypage.password.update');
});

// ============================================================================
// 관리자
// ============================================================================

$router->group(['prefix' => '/admin', 'middleware' => ['auth', 'admin']], function (Router $router) {
    // 대시보드
    $router->get('/', 'Admin\\DashboardController@index')->name('admin.dashboard');
    $router->get('/stats', 'Admin\\DashboardController@stats')->name('admin.stats');

    // 예약 관리
    $router->get('/reservations', 'Admin\\ReservationController@index')
        ->name('admin.reservations.index');
    $router->get('/reservations/calendar', 'Admin\\ReservationController@calendar')
        ->name('admin.reservations.calendar');
    $router->get('/reservations/statistics', 'Admin\\ReservationController@statistics')
        ->name('admin.reservations.statistics');
    $router->get('/reservations/create', 'Admin\\ReservationController@create')
        ->name('admin.reservations.create');
    $router->post('/reservations', 'Admin\\ReservationController@store')
        ->middleware('csrf')
        ->name('admin.reservations.store');
    $router->get('/reservations/{id}', 'Admin\\ReservationController@show')
        ->where('id', '\d+')
        ->name('admin.reservations.show');
    $router->get('/reservations/{id}/edit', 'Admin\\ReservationController@edit')
        ->where('id', '\d+')
        ->name('admin.reservations.edit');
    $router->put('/reservations/{id}', 'Admin\\ReservationController@update')
        ->where('id', '\d+')
        ->middleware('csrf')
        ->name('admin.reservations.update');
    $router->post('/reservations/{id}/confirm', 'Admin\\ReservationController@confirm')
        ->where('id', '\d+')
        ->middleware('csrf')
        ->name('admin.reservations.confirm');
    $router->post('/reservations/{id}/cancel', 'Admin\\ReservationController@cancel')
        ->where('id', '\d+')
        ->middleware('csrf')
        ->name('admin.reservations.cancel');
    $router->post('/reservations/{id}/complete', 'Admin\\ReservationController@complete')
        ->where('id', '\d+')
        ->middleware('csrf')
        ->name('admin.reservations.complete');
    $router->post('/reservations/{id}/no-show', 'Admin\\ReservationController@noShow')
        ->where('id', '\d+')
        ->middleware('csrf')
        ->name('admin.reservations.noshow');

    // 서비스 관리
    $router->resource('services', 'Admin\\ServiceController');
    $router->post('/services/{id}/toggle-active', 'Admin\\ServiceController@toggleActive')
        ->where('id', '\d+')
        ->middleware('csrf')
        ->name('admin.services.toggle');

    // 카테고리 관리
    $router->resource('categories', 'Admin\\CategoryController');

    // 시간대 관리
    $router->get('/time-slots', 'Admin\\TimeSlotController@index')
        ->name('admin.timeslots.index');
    $router->post('/time-slots', 'Admin\\TimeSlotController@store')
        ->middleware('csrf')
        ->name('admin.timeslots.store');
    $router->delete('/time-slots/{id}', 'Admin\\TimeSlotController@destroy')
        ->where('id', '\d+')
        ->middleware('csrf')
        ->name('admin.timeslots.destroy');
    $router->post('/time-slots/block-date', 'Admin\\TimeSlotController@blockDate')
        ->middleware('csrf')
        ->name('admin.timeslots.block');
    $router->post('/time-slots/unblock-date', 'Admin\\TimeSlotController@unblockDate')
        ->middleware('csrf')
        ->name('admin.timeslots.unblock');

    // 사용자 관리
    $router->resource('users', 'Admin\\UserController');

    // 설정 (서브페이지)
    $router->get('/settings', function () {
        // 기본 설정 페이지로 리다이렉트
        header('Location: ' . url('/admin/settings/general'));
        exit;
    })->name('admin.settings.index');

    $router->get('/settings/general', 'Admin\\SettingsController@general')
        ->name('admin.settings.general');
    $router->post('/settings/general', 'Admin\\SettingsController@updateGeneral')
        ->middleware('csrf')
        ->name('admin.settings.general.update');

    $router->get('/settings/seo', 'Admin\\SettingsController@seo')
        ->name('admin.settings.seo');
    $router->post('/settings/seo', 'Admin\\SettingsController@updateSeo')
        ->middleware('csrf')
        ->name('admin.settings.seo.update');

    $router->get('/settings/pwa', 'Admin\\SettingsController@pwa')
        ->name('admin.settings.pwa');
    $router->post('/settings/pwa', 'Admin\\SettingsController@updatePwa')
        ->middleware('csrf')
        ->name('admin.settings.pwa.update');

    // 시스템 설정 (서브페이지)
    $router->get('/settings/system', function () {
        header('Location: ' . url('/admin/settings/system/info'));
        exit;
    })->name('admin.settings.system');

    $router->get('/settings/system/info', 'Admin\\SettingsController@systemInfo')
        ->name('admin.settings.system.info');
    $router->get('/settings/system/cache', 'Admin\\SettingsController@systemCache')
        ->name('admin.settings.system.cache');
    $router->post('/settings/system/cache', 'Admin\\SettingsController@systemCacheAction')
        ->middleware('csrf')
        ->name('admin.settings.system.cache.action');
    $router->get('/settings/system/mode', 'Admin\\SettingsController@systemMode')
        ->name('admin.settings.system.mode');
    $router->post('/settings/system/mode', 'Admin\\SettingsController@systemModeAction')
        ->middleware('csrf')
        ->name('admin.settings.system.mode.action');
    $router->get('/settings/system/logs', 'Admin\\SettingsController@systemLogs')
        ->name('admin.settings.system.logs');
    $router->post('/settings/system/logs', 'Admin\\SettingsController@systemLogsAction')
        ->middleware('csrf')
        ->name('admin.settings.system.logs.action');
});
