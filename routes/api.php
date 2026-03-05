<?php

declare(strict_types=1);

/**
 * API Routes
 *
 * RESTful API 라우트 정의
 * 모든 API는 /api 접두사를 가집니다.
 */

use RzxLib\Core\Routing\Router;

/** @var Router $router */

// ============================================================================
// 공개 API
// ============================================================================

$router->group(['prefix' => '/api'], function (Router $router) {

    // 서비스 API
    $router->get('/services', 'Api\\BookingApiController@services')
        ->name('api.services');
    $router->get('/services/{id}', 'Api\\BookingApiController@service')
        ->where('id', '\d+')
        ->name('api.services.show');

    // 카테고리 API
    $router->get('/categories', 'Api\\BookingApiController@categories')
        ->name('api.categories');

    // 가용성 API
    $router->get('/services/{id}/available-dates', 'Api\\BookingApiController@availableDates')
        ->where('id', '\d+')
        ->name('api.available-dates');
    $router->get('/services/{id}/available-slots', 'Api\\BookingApiController@availableSlots')
        ->where('id', '\d+')
        ->name('api.available-slots');

    // 예약 API
    $router->post('/reservations', 'Api\\BookingApiController@createReservation')
        ->name('api.reservations.create');
    $router->get('/reservations/{code}', 'Api\\BookingApiController@getReservation')
        ->name('api.reservations.show');
    $router->post('/reservations/{code}/cancel', 'Api\\BookingApiController@cancelReservation')
        ->name('api.reservations.cancel');
});

// ============================================================================
// 인증 API
// ============================================================================

$router->group(['prefix' => '/api/auth'], function (Router $router) {
    // 로그인
    $router->post('/login', 'Api\\AuthApiController@login')
        ->name('api.auth.login');

    // 회원가입
    $router->post('/register', 'Api\\AuthApiController@register')
        ->name('api.auth.register');

    // 로그아웃
    $router->post('/logout', 'Api\\AuthApiController@logout')
        ->middleware('auth:api')
        ->name('api.auth.logout');

    // 현재 사용자
    $router->get('/me', 'Api\\AuthApiController@me')
        ->middleware('auth:api')
        ->name('api.auth.me');

    // 토큰 갱신
    $router->post('/refresh', 'Api\\AuthApiController@refresh')
        ->middleware('auth:api')
        ->name('api.auth.refresh');
});

// ============================================================================
// 사용자 API (인증 필요)
// ============================================================================

$router->group(['prefix' => '/api/user', 'middleware' => 'auth:api'], function (Router $router) {
    // 내 예약 목록
    $router->get('/reservations', 'Api\\UserApiController@reservations')
        ->name('api.user.reservations');

    // 내 예약 상세
    $router->get('/reservations/{id}', 'Api\\UserApiController@reservationDetail')
        ->where('id', '\d+')
        ->name('api.user.reservations.show');

    // 내 예약 취소
    $router->post('/reservations/{id}/cancel', 'Api\\UserApiController@cancelReservation')
        ->where('id', '\d+')
        ->name('api.user.reservations.cancel');

    // 프로필
    $router->get('/profile', 'Api\\UserApiController@profile')
        ->name('api.user.profile');
    $router->put('/profile', 'Api\\UserApiController@updateProfile')
        ->name('api.user.profile.update');

    // 비밀번호 변경
    $router->put('/password', 'Api\\UserApiController@updatePassword')
        ->name('api.user.password.update');
});

// ============================================================================
// 관리자 API
// ============================================================================

$router->group(['prefix' => '/api/admin', 'middleware' => ['auth:api', 'admin']], function (Router $router) {
    // 대시보드 통계
    $router->get('/stats', 'Api\\AdminApiController@stats')
        ->name('api.admin.stats');
    $router->get('/stats/daily', 'Api\\AdminApiController@dailyStats')
        ->name('api.admin.stats.daily');

    // 예약 관리
    $router->get('/reservations', 'Api\\AdminApiController@reservations')
        ->name('api.admin.reservations');
    $router->get('/reservations/{id}', 'Api\\AdminApiController@reservationDetail')
        ->where('id', '\d+')
        ->name('api.admin.reservations.show');
    $router->put('/reservations/{id}', 'Api\\AdminApiController@updateReservation')
        ->where('id', '\d+')
        ->name('api.admin.reservations.update');
    $router->post('/reservations/{id}/confirm', 'Api\\AdminApiController@confirmReservation')
        ->where('id', '\d+')
        ->name('api.admin.reservations.confirm');
    $router->post('/reservations/{id}/cancel', 'Api\\AdminApiController@cancelReservation')
        ->where('id', '\d+')
        ->name('api.admin.reservations.cancel');
    $router->post('/reservations/{id}/complete', 'Api\\AdminApiController@completeReservation')
        ->where('id', '\d+')
        ->name('api.admin.reservations.complete');

    // 서비스 관리
    $router->get('/services', 'Api\\AdminApiController@services')
        ->name('api.admin.services');
    $router->post('/services', 'Api\\AdminApiController@createService')
        ->name('api.admin.services.create');
    $router->put('/services/{id}', 'Api\\AdminApiController@updateService')
        ->where('id', '\d+')
        ->name('api.admin.services.update');
    $router->delete('/services/{id}', 'Api\\AdminApiController@deleteService')
        ->where('id', '\d+')
        ->name('api.admin.services.delete');
    $router->post('/services/{id}/toggle', 'Api\\AdminApiController@toggleService')
        ->where('id', '\d+')
        ->name('api.admin.services.toggle');

    // 사용자 관리
    $router->get('/users', 'Api\\AdminApiController@users')
        ->name('api.admin.users');
    $router->get('/users/{id}', 'Api\\AdminApiController@userDetail')
        ->where('id', '\d+')
        ->name('api.admin.users.show');
    $router->put('/users/{id}', 'Api\\AdminApiController@updateUser')
        ->where('id', '\d+')
        ->name('api.admin.users.update');

    // 설정
    $router->get('/settings', 'Api\\AdminApiController@settings')
        ->name('api.admin.settings');
    $router->put('/settings', 'Api\\AdminApiController@updateSettings')
        ->name('api.admin.settings.update');
});

// ============================================================================
// 업데이트 API (관리자 전용)
// ============================================================================

$router->group(['prefix' => '/api/admin/updates', 'middleware' => ['auth:api', 'admin']], function (Router $router) {
    // 업데이트 확인
    $router->get('/check', 'Api\\UpdateApiController@check')
        ->name('api.admin.updates.check');

    // 업데이트 실행
    $router->post('/perform', 'Api\\UpdateApiController@perform')
        ->name('api.admin.updates.perform');

    // 롤백
    $router->post('/rollback', 'Api\\UpdateApiController@rollback')
        ->name('api.admin.updates.rollback');

    // 백업 목록
    $router->get('/backups', 'Api\\UpdateApiController@backups')
        ->name('api.admin.updates.backups');

    // 시스템 요구사항
    $router->get('/requirements', 'Api\\UpdateApiController@requirements')
        ->name('api.admin.updates.requirements');

    // 현재 버전 정보
    $router->get('/version', 'Api\\UpdateApiController@version')
        ->name('api.admin.updates.version');
});

// ============================================================================
// Webhook
// ============================================================================

$router->group(['prefix' => '/api/webhook'], function (Router $router) {
    // 결제 웹훅
    $router->post('/payment', 'Api\\WebhookController@payment')
        ->name('api.webhook.payment');

    // SMS 웹훅
    $router->post('/sms', 'Api\\WebhookController@sms')
        ->name('api.webhook.sms');
});
