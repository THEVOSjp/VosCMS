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
// 스태프 소개
// ============================================================================

$router->get('/staff', function () {
    return view('customer.staff');
})->name('staff.index');

$router->get('/staff/{id}', function ($id) {
    return view('customer.staff-detail', ['routeParams' => ['id' => $id]]);
})->where('id', '\d+')->name('staff.detail');

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

    // 개인정보 설정
    $router->get('/settings', 'MyPageController@settings')->name('mypage.settings');
    $router->post('/settings', 'MyPageController@updateSettings')
        ->middleware('csrf')
        ->name('mypage.settings.update');

    // 비밀번호 변경
    $router->get('/password', 'MyPageController@password')->name('mypage.password');
    $router->post('/password', 'MyPageController@updatePassword')
        ->middleware('csrf')
        ->name('mypage.password.update');

    // 메시지
    $router->get('/messages', 'MyPageController@messages')->name('mypage.messages');
    $router->post('/messages', 'MyPageController@messagesAction')
        ->middleware('csrf')
        ->name('mypage.messages.action');
});

// ============================================================================
// 관리자
// ============================================================================

$router->group(['prefix' => '/admin', 'middleware' => ['auth', 'admin']], function (Router $router) {
    // 대시보드
    $router->get('/', 'Admin\\DashboardController@index')->name('admin.dashboard');
    $router->get('/stats', 'Admin\\DashboardController@stats')->name('admin.stats');

    // 예약 관리
    $router->get('/reservations/pos', 'Admin\\ReservationController@pos')
        ->name('admin.reservations.pos');
    $router->get('/kiosk', 'Admin\\ReservationController@kiosk')
        ->name('admin.kiosk');
    $router->get('/kiosk/settings', 'Admin\\ReservationController@kioskSettings')
        ->name('admin.kiosk.settings');
    $router->post('/kiosk/settings', 'Admin\\ReservationController@kioskSettingsSave')
        ->middleware('csrf')
        ->name('admin.kiosk.settings.save');
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

    // 서비스 설정 (서브페이지) - resource 라우트보다 먼저 등록
    $router->get('/services/settings', function () {
        header('Location: ' . url('/admin/services/settings/general'));
        exit;
    })->name('admin.services.settings.index');

    $router->get('/services/settings/general', function () {
        global $config, $siteSettings;
        $settingsTab = 'general';
        include BASE_PATH . '/resources/views/admin/services/settings.php';
    })->name('admin.services.settings.general');

    $router->post('/services/settings/general', function () {
        global $config, $siteSettings;
        $settingsTab = 'general';
        include BASE_PATH . '/resources/views/admin/services/settings.php';
    })->middleware('csrf')->name('admin.services.settings.general.update');

    $router->get('/services/settings/categories', function () {
        global $config, $siteSettings;
        $settingsTab = 'categories';
        include BASE_PATH . '/resources/views/admin/services/settings.php';
    })->name('admin.services.settings.categories');

    $router->post('/services/settings/categories', function () {
        global $config, $siteSettings;
        $settingsTab = 'categories';
        include BASE_PATH . '/resources/views/admin/services/settings.php';
    })->middleware('csrf')->name('admin.services.settings.categories.update');

    $router->get('/services/settings/holidays', function () {
        global $config, $siteSettings;
        $settingsTab = 'holidays';
        include BASE_PATH . '/resources/views/admin/services/settings.php';
    })->name('admin.services.settings.holidays');

    $router->post('/services/settings/holidays', function () {
        global $config, $siteSettings;
        $settingsTab = 'holidays';
        include BASE_PATH . '/resources/views/admin/services/settings.php';
    })->middleware('csrf')->name('admin.services.settings.holidays.update');

    // 서비스 관리
    $router->resource('services', 'Admin\\ServiceController');
    $router->post('/services/{id}/toggle-active', 'Admin\\ServiceController@toggleActive')
        ->where('id', '\d+')
        ->middleware('csrf')
        ->name('admin.services.toggle');

    // 번들(묶음서비스) 관리
    $router->get('/bundles', function () {
        $config = app()->getConfig();
        include BASE_PATH . '/resources/views/admin/bundles/index.php';
    })->name('admin.bundles.index');

    $router->post('/bundles', function () {
        $config = app()->getConfig();
        include BASE_PATH . '/resources/views/admin/bundles/index.php';
    })->name('admin.bundles.store');

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

    // 회원 관리
    $router->get('/members', 'Admin\\MemberController@index')
        ->name('admin.members.index');
    $router->get('/members/create', 'Admin\\MemberController@create')
        ->name('admin.members.create');
    $router->post('/members', 'Admin\\MemberController@store')
        ->middleware('csrf')
        ->name('admin.members.store');
    $router->get('/members/{id}', 'Admin\\MemberController@show')
        ->where('id', '\d+')
        ->name('admin.members.show');
    $router->get('/members/{id}/edit', 'Admin\\MemberController@edit')
        ->where('id', '\d+')
        ->name('admin.members.edit');
    $router->put('/members/{id}', 'Admin\\MemberController@update')
        ->where('id', '\d+')
        ->middleware('csrf')
        ->name('admin.members.update');
    $router->delete('/members/{id}', 'Admin\\MemberController@destroy')
        ->where('id', '\d+')
        ->middleware('csrf')
        ->name('admin.members.destroy');

    // 회원 설정 (서브페이지)
    $router->get('/members/settings', function () {
        header('Location: ' . url('/admin/members/settings/general'));
        exit;
    })->name('admin.members.settings.index');

    $router->get('/members/settings/general', 'Admin\\MemberSettingsController@general')
        ->name('admin.members.settings.general');
    $router->post('/members/settings/general', 'Admin\\MemberSettingsController@updateGeneral')
        ->middleware('csrf')
        ->name('admin.members.settings.general.update');

    $router->get('/members/settings/features', 'Admin\\MemberSettingsController@features')
        ->name('admin.members.settings.features');
    $router->post('/members/settings/features', 'Admin\\MemberSettingsController@updateFeatures')
        ->middleware('csrf')
        ->name('admin.members.settings.features.update');

    $router->get('/members/settings/terms', 'Admin\\MemberSettingsController@terms')
        ->name('admin.members.settings.terms');
    $router->post('/members/settings/terms', 'Admin\\MemberSettingsController@updateTerms')
        ->middleware('csrf')
        ->name('admin.members.settings.terms.update');

    $router->get('/members/settings/register', 'Admin\\MemberSettingsController@register')
        ->name('admin.members.settings.register');
    $router->post('/members/settings/register', 'Admin\\MemberSettingsController@updateRegister')
        ->middleware('csrf')
        ->name('admin.members.settings.register.update');

    $router->get('/members/settings/login', 'Admin\\MemberSettingsController@login')
        ->name('admin.members.settings.login');
    $router->post('/members/settings/login', 'Admin\\MemberSettingsController@updateLogin')
        ->middleware('csrf')
        ->name('admin.members.settings.login.update');

    $router->get('/members/settings/design', 'Admin\\MemberSettingsController@design')
        ->name('admin.members.settings.design');
    $router->post('/members/settings/design', 'Admin\\MemberSettingsController@updateDesign')
        ->middleware('csrf')
        ->name('admin.members.settings.design.update');

    // 회원 그룹
    $router->get('/members/groups', 'Admin\\MemberGroupController@index')
        ->name('admin.members.groups.index');
    $router->post('/members/groups', 'Admin\\MemberGroupController@store')
        ->middleware('csrf')
        ->name('admin.members.groups.store');
    $router->put('/members/groups/{id}', 'Admin\\MemberGroupController@update')
        ->where('id', '\d+')
        ->middleware('csrf')
        ->name('admin.members.groups.update');
    $router->delete('/members/groups/{id}', 'Admin\\MemberGroupController@destroy')
        ->where('id', '\d+')
        ->middleware('csrf')
        ->name('admin.members.groups.destroy');

    // 적립금 관리
    $router->get('/points', 'Admin\\PointController@index')
        ->name('admin.points.index');
    $router->get('/points/history', 'Admin\\PointController@history')
        ->name('admin.points.history');
    $router->post('/points/add', 'Admin\\PointController@add')
        ->middleware('csrf')
        ->name('admin.points.add');
    $router->post('/points/deduct', 'Admin\\PointController@deduct')
        ->middleware('csrf')
        ->name('admin.points.deduct');

    // 사이트 관리 - 메뉴 관리
    $router->get('/site/menus', function () {
        global $config, $siteSettings;
        $pageHeaderTitle = __('site.menus.title');
        include BASE_PATH . '/resources/views/admin/site/menus.php';
    })->name('admin.site.menus');

    $router->post('/site/menus/api', function () {
        global $config, $siteSettings;
        include BASE_PATH . '/resources/views/admin/site/menus-api.php';
    })->middleware('csrf')->name('admin.site.menus.api');

    // 사이트 관리 - 디자인
    $router->get('/site/design', function () {
        global $config, $siteSettings;
        $pageHeaderTitle = __('site.design.title');
        include BASE_PATH . '/resources/views/admin/site/design.php';
    })->name('admin.site.design');

    // 사이트 관리 - 페이지
    $router->get('/site/pages', function () {
        global $config, $siteSettings;
        $pageHeaderTitle = __('site.pages.title');
        include BASE_PATH . '/resources/views/admin/site/pages.php';
    })->name('admin.site.pages');

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

    $router->get('/settings/site', 'Admin\\SettingsController@site')
        ->name('admin.settings.site');
    $router->post('/settings/site', 'Admin\\SettingsController@updateSite')
        ->middleware('csrf')
        ->name('admin.settings.site.update');

    $router->get('/settings/mail', 'Admin\\SettingsController@mail')
        ->name('admin.settings.mail');
    $router->post('/settings/mail', 'Admin\\SettingsController@updateMail')
        ->middleware('csrf')
        ->name('admin.settings.mail.update');

    $router->get('/settings/language', 'Admin\\SettingsController@language')
        ->name('admin.settings.language');
    $router->post('/settings/language', 'Admin\\SettingsController@updateLanguage')
        ->middleware('csrf')
        ->name('admin.settings.language.update');

    $router->get('/settings/translations', 'Admin\\SettingsController@translations')
        ->name('admin.settings.translations');
    $router->post('/settings/translations', 'Admin\\SettingsController@updateTranslations')
        ->middleware('csrf')
        ->name('admin.settings.translations.update');

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
    $router->get('/settings/system/updates', 'Admin\\SettingsController@systemUpdates')
        ->name('admin.settings.system.updates');
    $router->post('/settings/system/updates/ajax', 'Admin\\SettingsController@systemUpdatesAjax')
        ->name('admin.settings.system.updates.ajax');
});
