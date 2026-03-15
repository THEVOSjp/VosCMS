<?php
/**
 * RezlyX Admin Sidebar Component
 *
 * 필요한 변수:
 * - $adminUrl: 관리자 기본 URL
 * - $baseUrl: 사이트 기본 URL
 * - $config: 앱 설정 배열
 */

// 현재 페이지 경로 확인
$currentPath = $_SERVER['REQUEST_URI'] ?? '';
$adminPath = $config['admin_path'] ?? 'admin';

// 메뉴 활성화 상태 확인 함수
if (!function_exists('isActiveMenu')) {
    function isActiveMenu($path, $currentPath) {
        return strpos($currentPath, $path) !== false;
    }
}

// 예약 관리 서브페이지 여부
$isReservationsPage = strpos($currentPath, '/reservations') !== false;
$isReservationsPosPage = strpos($currentPath, '/reservations/pos') !== false;
$isReservationsCalendarPage = strpos($currentPath, '/reservations/calendar') !== false;
$isReservationsStatsPage = strpos($currentPath, '/reservations/statistics') !== false;
$isReservationsCreatePage = strpos($currentPath, '/reservations/create') !== false;

// 서비스 관리 서브페이지 여부
$isServicesPage = strpos($currentPath, '/services') !== false && strpos($currentPath, '/staff') === false;
$isServicesSettingsPage = strpos($currentPath, '/services/settings') !== false;

// 스태프 관리 여부
$isStaffPage = strpos($currentPath, '/staff') !== false;
$isStaffSettingsPage = strpos($currentPath, '/staff/settings') !== false;
$isStaffSchedulePage = strpos($currentPath, '/staff/schedule') !== false;
$isStaffAttendancePage = strpos($currentPath, '/staff/attendance') !== false;
$isStaffAdminsPage = strpos($currentPath, '/staff/admins') !== false;

// 사이트 관리 서브페이지 여부
$isSitePage = strpos($currentPath, '/site/') !== false;
$isWidgetsPage = strpos($currentPath, '/site/widgets') !== false;

// 회원 관리 서브페이지 여부
$isMembersPage = strpos($currentPath, '/members') !== false || strpos($currentPath, '/points') !== false;

// 설정 서브페이지 여부
$isSettingsPage = strpos($currentPath, '/settings') !== false;
?>
<script>
    // 사이드바 상태를 DOM 렌더 전에 html에 클래스 적용 (깜빡임 방지)
    if (localStorage.getItem('sidebarCollapsed') === 'true') {
        document.documentElement.classList.add('sidebar-is-collapsed');
    }
</script>
<style>
    /* 사이드바 접힌 상태 CSS */
    #adminSidebar.sidebar-collapsed nav a,
    #adminSidebar.sidebar-collapsed nav button { padding-left: 0; padding-right: 0; justify-content: center; }
    #adminSidebar.sidebar-collapsed nav a svg.flex-shrink-0,
    #adminSidebar.sidebar-collapsed nav button svg.flex-shrink-0 { margin-right: 0; }
    #adminSidebar.sidebar-collapsed .p-6 { padding: 1rem 0.5rem; justify-content: center; }
    #adminSidebar.sidebar-collapsed #sidebarToggleBtn { margin: 0 auto; }
    #adminSidebar.sidebar-collapsed .sidebar-text { display: none; }
    #adminSidebar.sidebar-collapsed [id$="SubMenu"] { display: none; }
    #adminSidebar.sidebar-collapsed #sidebarToggleIcon { transform: rotate(180deg); }
    /* 페이지 로드 시 깜빡임 방지: html 클래스 기반 즉시 적용 */
    .sidebar-is-collapsed #adminSidebar { width: 4rem !important; }
    .sidebar-is-collapsed main.flex-1 { margin-left: 4rem !important; }
</style>
<aside id="adminSidebar" class="w-64 bg-zinc-950 min-h-screen fixed transition-all duration-300 z-40 overflow-hidden">
    <div class="p-6 flex items-center justify-between">
        <a href="<?php echo $adminUrl; ?>" class="text-xl font-bold text-white truncate sidebar-text">
            <?php echo htmlspecialchars($config['app_name'] ?? 'RezlyX'); ?>
            <span class="text-blue-400 text-sm ml-1"><?= __('admin.title') ?></span>
        </a>
        <button id="sidebarToggleBtn" onclick="toggleSidebar()" class="p-1.5 text-zinc-400 hover:text-white hover:bg-zinc-800 rounded-lg transition flex-shrink-0" title="Toggle Sidebar">
            <svg id="sidebarToggleIcon" class="w-4 h-4 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </button>
    </div>
    <nav class="mt-6">
        <a href="<?php echo $adminUrl; ?>" class="flex items-center px-6 py-3 <?php echo !isActiveMenu('/settings', $currentPath) && !isActiveMenu('/reservations', $currentPath) && !isActiveMenu('/services', $currentPath) && !isActiveMenu('/staff', $currentPath) && !isActiveMenu('/members', $currentPath) && !isActiveMenu('/points', $currentPath) && !$isSitePage ? 'text-white bg-blue-600' : 'text-zinc-300 hover:bg-zinc-800 hover:text-white'; ?>" title="<?= __('admin.nav.dashboard') ?>">
            <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <span class="sidebar-text"><?= __('admin.nav.dashboard') ?></span>
        </a>
        <?php if (\RzxLib\Core\Auth\AdminAuth::can('reservations')): ?>
        <div class="reservations-management-menu has-submenu" data-submenu="reservationsSubMenu">
            <button onclick="toggleReservationsMenu()" class="flex items-center justify-between w-full px-6 py-3 <?php echo $isReservationsPage ? 'text-white bg-zinc-800' : 'text-zinc-300 hover:bg-zinc-800 hover:text-white'; ?>" title="<?= __('admin.nav.reservations') ?>">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="sidebar-text"><?= __('admin.nav.reservations') ?></span>
                </div>
                <svg id="reservationsMenuArrow" class="w-4 h-4 transition-transform sidebar-text <?php echo $isReservationsPage ? 'rotate-180' : ''; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div id="reservationsSubMenu" class="<?php echo $isReservationsPage ? '' : 'hidden'; ?> bg-zinc-900">
                <a href="<?php echo $adminUrl; ?>/reservations/pos" class="flex items-center px-6 py-2.5 pl-14 <?php echo $isReservationsPosPage ? 'text-blue-400 bg-zinc-800' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?> text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 7h6m0 10v-3m-3 3h.01M9 17h.01M9 14h.01M12 14h.01M15 11h.01M12 11h.01M9 11h.01M7 21h10a2 2 0 002-2V5a2 2 0 00-2-2H7a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <?= __('reservations.pos') ?>
                </a>
                <a href="<?php echo $adminUrl; ?>/reservations" class="flex items-center px-6 py-2.5 pl-14 <?php echo $isReservationsPage && !$isReservationsPosPage && !$isReservationsCalendarPage && !$isReservationsStatsPage && !$isReservationsCreatePage ? 'text-blue-400 bg-zinc-800' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?> text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <?= __('reservations.list') ?>
                </a>
                <a href="<?php echo $adminUrl; ?>/reservations/calendar" class="flex items-center px-6 py-2.5 pl-14 <?php echo $isReservationsCalendarPage ? 'text-blue-400 bg-zinc-800' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?> text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <?= __('reservations.calendar') ?>
                </a>
                <a href="<?php echo $adminUrl; ?>/reservations/statistics" class="flex items-center px-6 py-2.5 pl-14 <?php echo $isReservationsStatsPage ? 'text-blue-400 bg-zinc-800' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?> text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>
                    </svg>
                    <?= __('reservations.statistics') ?>
                </a>
                <a href="<?php echo $adminUrl; ?>/reservations/create" class="flex items-center px-6 py-2.5 pl-14 <?php echo $isReservationsCreatePage ? 'text-blue-400 bg-zinc-800' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?> text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>
                    </svg>
                    <?= __('reservations.create') ?>
                </a>
            </div>
        </div>
        <?php endif; ?>
        <!-- 서비스 관리 메뉴 -->
        <?php if (\RzxLib\Core\Auth\AdminAuth::can('services')): ?>
        <div class="services-management-menu has-submenu" data-submenu="servicesSubMenu">
            <button onclick="toggleServicesMenu()" class="flex items-center justify-between w-full px-6 py-3 <?php echo $isServicesPage ? 'text-white bg-zinc-800' : 'text-zinc-300 hover:bg-zinc-800 hover:text-white'; ?>" title="<?= __('admin.nav.services') ?>">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <span class="sidebar-text"><?= __('admin.nav.services') ?></span>
                </div>
                <svg id="servicesMenuArrow" class="w-4 h-4 transition-transform sidebar-text <?php echo $isServicesPage ? 'rotate-180' : ''; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div id="servicesSubMenu" class="<?php echo $isServicesPage ? '' : 'hidden'; ?> bg-zinc-900">
                <a href="<?php echo $adminUrl; ?>/services" class="flex items-center px-6 py-2.5 pl-14 <?php echo $isServicesPage && !$isServicesSettingsPage ? 'text-blue-400 bg-zinc-800' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?> text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/>
                    </svg>
                    <?= __('services.list') ?>
                </a>
                <a href="<?php echo $adminUrl; ?>/services/settings" class="flex items-center px-6 py-2.5 pl-14 <?php echo $isServicesSettingsPage ? 'text-blue-400 bg-zinc-800' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?> text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <?= __('admin.nav.services_settings') ?>
                </a>
            </div>
        </div>
        <?php endif; ?>
        <!-- 스태프(디자이너) 관리 메뉴 -->
        <?php if (\RzxLib\Core\Auth\AdminAuth::can('staff')): ?>
        <div class="staff-management-menu has-submenu" data-submenu="staffSubMenu">
            <button onclick="toggleStaffMenu()" class="flex items-center justify-between w-full px-6 py-3 <?php echo $isStaffPage ? 'text-white bg-zinc-800' : 'text-zinc-300 hover:bg-zinc-800 hover:text-white'; ?>" title="<?= __('admin.nav.staff') ?>">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                    <span class="sidebar-text"><?= __('admin.nav.staff') ?></span>
                </div>
                <svg id="staffMenuArrow" class="w-4 h-4 transition-transform sidebar-text <?php echo $isStaffPage ? 'rotate-180' : ''; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div id="staffSubMenu" class="<?php echo $isStaffPage ? '' : 'hidden'; ?> bg-zinc-900">
                <a href="<?php echo $adminUrl; ?>/staff" class="flex items-center px-6 py-2.5 pl-14 <?php echo $isStaffPage && !$isStaffSettingsPage && !$isStaffAttendancePage ? 'text-blue-400 bg-zinc-800' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?> text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <?= __('staff.list') ?>
                </a>
                <a href="<?php echo $adminUrl; ?>/staff/schedule" class="flex items-center px-6 py-2.5 pl-14 <?php echo $isStaffSchedulePage ? 'text-blue-400 bg-zinc-800' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?> text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <?= __('staff.schedule.title') ?>
                </a>
                <a href="<?php echo $adminUrl; ?>/staff/attendance" class="flex items-center px-6 py-2.5 pl-14 <?php echo $isStaffAttendancePage ? 'text-blue-400 bg-zinc-800' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?> text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <?= __('admin.nav.staff_attendance') ?>
                </a>
                <?php if (\RzxLib\Core\Auth\AdminAuth::isMaster()): ?>
                <a href="<?php echo $adminUrl; ?>/staff/admins" class="flex items-center px-6 py-2.5 pl-14 <?php echo $isStaffAdminsPage ? 'text-blue-400 bg-zinc-800' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?> text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                    </svg>
                    관리자 권한
                </a>
                <?php endif; ?>
                <a href="<?php echo $adminUrl; ?>/staff/settings" class="flex items-center px-6 py-2.5 pl-14 <?php echo $isStaffSettingsPage ? 'text-blue-400 bg-zinc-800' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?> text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <?= __('admin.nav.staff_settings') ?>
                </a>
            </div>
        </div>
        <?php endif; ?>
        <!-- 회원 관리 메뉴 -->
        <?php if (\RzxLib\Core\Auth\AdminAuth::can('members')): ?>
        <div class="members-management-menu has-submenu" data-submenu="membersSubMenu">
            <button onclick="toggleMembersMenu()" class="flex items-center justify-between w-full px-6 py-3 <?php echo $isMembersPage ? 'text-white bg-zinc-800' : 'text-zinc-300 hover:bg-zinc-800 hover:text-white'; ?>" title="<?= __('admin.nav.members') ?>">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <span class="sidebar-text"><?= __('admin.nav.members') ?></span>
                </div>
                <svg id="membersMenuArrow" class="w-4 h-4 transition-transform sidebar-text <?php echo $isMembersPage ? 'rotate-180' : ''; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div id="membersSubMenu" class="<?php echo $isMembersPage ? '' : 'hidden'; ?> bg-zinc-900">
                <a href="<?php echo $adminUrl; ?>/members" class="flex items-center px-6 py-2.5 pl-14 <?php echo isActiveMenu('/members', $currentPath) && !isActiveMenu('/members/settings', $currentPath) && !isActiveMenu('/members/groups', $currentPath) ? 'text-blue-400 bg-zinc-800' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?> text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
                    </svg>
                    <?= __('admin.nav.members_list') ?>
                </a>
                <a href="<?php echo $adminUrl; ?>/members/settings" class="flex items-center px-6 py-2.5 pl-14 <?php echo isActiveMenu('/members/settings', $currentPath) ? 'text-blue-400 bg-zinc-800' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?> text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <?= __('admin.nav.members_settings') ?>
                </a>
                <a href="<?php echo $adminUrl; ?>/members/groups" class="flex items-center px-6 py-2.5 pl-14 <?php echo isActiveMenu('/members/groups', $currentPath) ? 'text-blue-400 bg-zinc-800' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?> text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                    </svg>
                    <?= __('admin.nav.members_groups') ?>
                </a>
                <a href="<?php echo $adminUrl; ?>/points" class="flex items-center px-6 py-2.5 pl-14 <?php echo isActiveMenu('/points', $currentPath) ? 'text-blue-400 bg-zinc-800' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?> text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <?= __('admin.nav.points') ?>
                </a>
            </div>
        </div>
        <?php endif; ?>
        <!-- 사이트 관리 메뉴 -->
        <?php if (\RzxLib\Core\Auth\AdminAuth::can('site')): ?>
        <div class="site-management-menu has-submenu" data-submenu="siteSubMenu">
            <button onclick="toggleSiteMenu()" class="flex items-center justify-between w-full px-6 py-3 <?php echo $isSitePage ? 'text-white bg-zinc-800' : 'text-zinc-300 hover:bg-zinc-800 hover:text-white'; ?>" title="<?= __('admin.nav.site_management') ?>">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                    </svg>
                    <span class="sidebar-text"><?= __('admin.nav.site_management') ?></span>
                </div>
                <svg id="siteMenuArrow" class="w-4 h-4 transition-transform sidebar-text <?php echo $isSitePage ? 'rotate-180' : ''; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div id="siteSubMenu" class="<?php echo $isSitePage ? '' : 'hidden'; ?> bg-zinc-900">
                <a href="<?php echo $adminUrl; ?>/site/menus" class="flex items-center px-6 py-2.5 pl-14 <?php echo isActiveMenu('/site/menus', $currentPath) ? 'text-blue-400 bg-zinc-800' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?> text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <?= __('admin.nav.menu_management') ?>
                </a>
                <a href="<?php echo $adminUrl; ?>/site/design" class="flex items-center px-6 py-2.5 pl-14 <?php echo isActiveMenu('/site/design', $currentPath) ? 'text-blue-400 bg-zinc-800' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?> text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                    </svg>
                    <?= __('admin.nav.design_management') ?>
                </a>
                <a href="<?php echo $adminUrl; ?>/site/pages" class="flex items-center px-6 py-2.5 pl-14 <?php echo isActiveMenu('/site/pages', $currentPath) && !$isWidgetsPage ? 'text-blue-400 bg-zinc-800' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?> text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <?= __('admin.nav.page_management') ?>
                </a>
                <a href="<?php echo $adminUrl; ?>/site/widgets" class="flex items-center px-6 py-2.5 pl-14 <?php echo $isWidgetsPage ? 'text-blue-400 bg-zinc-800' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?> text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z"/>
                    </svg>
                    <?= __('admin.nav.widget_management') ?>
                </a>
            </div>
        </div>
        <?php endif; ?>
        <!-- 설정 메뉴 -->
        <?php if (\RzxLib\Core\Auth\AdminAuth::can('settings')): ?>
        <div class="settings-menu has-submenu" data-submenu="settingsSubMenu">
            <button onclick="toggleSettingsMenu()" class="flex items-center justify-between w-full px-6 py-3 <?php echo $isSettingsPage ? 'text-white bg-zinc-800' : 'text-zinc-300 hover:bg-zinc-800 hover:text-white'; ?>" title="<?= __('admin.nav.settings') ?>">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <span class="sidebar-text"><?= __('admin.nav.settings') ?></span>
                </div>
                <svg id="settingsMenuArrow" class="w-4 h-4 transition-transform sidebar-text <?php echo $isSettingsPage ? 'rotate-180' : ''; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div id="settingsSubMenu" class="<?php echo $isSettingsPage ? '' : 'hidden'; ?> bg-zinc-900">
                <a href="<?php echo $adminUrl; ?>/settings/general" class="flex items-center px-6 py-2.5 pl-14 <?php echo isActiveMenu('/settings/general', $currentPath) ? 'text-blue-400 bg-zinc-800' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?> text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <?= __('settings.tabs.general') ?>
                </a>
                <a href="<?php echo $adminUrl; ?>/settings/seo" class="flex items-center px-6 py-2.5 pl-14 <?php echo isActiveMenu('/settings/seo', $currentPath) ? 'text-blue-400 bg-zinc-800' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?> text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <?= __('settings.tabs.seo') ?>
                </a>
                <a href="<?php echo $adminUrl; ?>/settings/pwa" class="flex items-center px-6 py-2.5 pl-14 <?php echo isActiveMenu('/settings/pwa', $currentPath) ? 'text-blue-400 bg-zinc-800' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?> text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <?= __('settings.tabs.pwa') ?>
                </a>
                <a href="<?php echo $adminUrl; ?>/settings/system" class="flex items-center px-6 py-2.5 pl-14 <?php echo isActiveMenu('/settings/system', $currentPath) ? 'text-blue-400 bg-zinc-800' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?> text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                    </svg>
                    <?= __('settings.tabs.system') ?>
                </a>
            </div>
        </div>
        <?php endif; ?>
    </nav>
    <div class="absolute bottom-0 w-full p-4 border-t border-zinc-800/50">
        <?php
        $versionFile = BASE_PATH . '/version.json';
        $versionInfo = file_exists($versionFile) ? json_decode(file_get_contents($versionFile), true) : ['version' => '1.0.0'];
        ?>
        <div class="flex items-center justify-between text-zinc-500 text-xs">
            <span class="font-medium sidebar-text">RezlyX</span>
            <span class="sidebar-text">v<?= htmlspecialchars($versionInfo['version'] ?? '1.0.0') ?></span>
        </div>
    </div>
</aside>

<!-- 플라이아웃 팝업 (접힌 사이드바 호버 시) -->
<div id="sidebarFlyout" class="fixed hidden bg-zinc-900 rounded-r-lg shadow-2xl border border-zinc-700/50 py-1.5 min-w-[200px] z-50" style="left:4rem">
    <div id="flyoutTitle" class="px-4 py-2 text-xs font-semibold text-zinc-300 border-b border-zinc-700/50 mb-1"></div>
    <div id="flyoutLinks"></div>
</div>

<!-- 사이드바 접기/펼치기 + 메뉴 토글 스크립트 -->
<script>
    // ===== 사이드바 접기/펼치기 =====
    function toggleSidebar() {
        var sidebar = document.getElementById('adminSidebar');
        var icon = document.getElementById('sidebarToggleIcon');
        var isCollapsed = sidebar.classList.contains('sidebar-collapsed');

        if (isCollapsed) {
            // 펼치기
            document.documentElement.classList.remove('sidebar-is-collapsed');
            sidebar.classList.remove('sidebar-collapsed');
            sidebar.style.width = '16rem';
            var main = document.querySelector('main.flex-1');
            if (main) main.style.marginLeft = '16rem';
            localStorage.setItem('sidebarCollapsed', 'false');
            console.log('[Sidebar] Expanded');
        } else {
            // 접기
            document.documentElement.classList.add('sidebar-is-collapsed');
            sidebar.classList.add('sidebar-collapsed');
            sidebar.style.width = '4rem';
            var main = document.querySelector('main.flex-1');
            if (main) main.style.marginLeft = '4rem';
            localStorage.setItem('sidebarCollapsed', 'true');
            console.log('[Sidebar] Collapsed');
        }
    }

    // 페이지 로드 시 저장된 상태 복원
    (function() {
        var main = document.querySelector('main.flex-1');
        if (main) main.style.transition = 'margin-left 0.3s';

        if (localStorage.getItem('sidebarCollapsed') === 'true') {
            var sidebar = document.getElementById('adminSidebar');
            sidebar.classList.add('sidebar-collapsed');
            sidebar.style.width = '4rem';
            if (main) main.style.marginLeft = '4rem';
        }
    })();

    // ===== 플라이아웃 (접힌 상태 호버) =====
    (function() {
        var flyout = document.getElementById('sidebarFlyout');
        var flyoutTitle = document.getElementById('flyoutTitle');
        var flyoutLinks = document.getElementById('flyoutLinks');
        var hideTimer = null;

        function showFlyout(menuDiv) {
            var sidebar = document.getElementById('adminSidebar');
            if (!sidebar.classList.contains('sidebar-collapsed')) return;

            var submenuId = menuDiv.dataset.submenu;
            var submenu = document.getElementById(submenuId);
            if (!submenu) return;

            clearTimeout(hideTimer);

            // 메뉴 타이틀 가져오기
            var titleEl = menuDiv.querySelector('.sidebar-text');
            flyoutTitle.textContent = titleEl ? titleEl.textContent.trim() : '';

            // 서브메뉴 링크 복제
            flyoutLinks.innerHTML = '';
            submenu.querySelectorAll('a').forEach(function(a) {
                var clone = a.cloneNode(true);
                // 스타일 리셋 — 플라이아웃용
                clone.className = 'flex items-center px-4 py-2 text-sm text-zinc-300 hover:bg-zinc-800 hover:text-white transition';
                clone.querySelectorAll('svg').forEach(function(svg) {
                    svg.className.baseVal = 'w-4 h-4 mr-2.5 flex-shrink-0';
                });
                flyoutLinks.appendChild(clone);
            });

            // 위치 계산
            var rect = menuDiv.querySelector('button').getBoundingClientRect();
            flyout.style.top = rect.top + 'px';
            flyout.classList.remove('hidden');
            console.log('[Sidebar] Flyout shown:', submenuId);
        }

        function scheduleFlyoutHide() {
            hideTimer = setTimeout(function() {
                flyout.classList.add('hidden');
            }, 200);
        }

        function cancelFlyoutHide() {
            clearTimeout(hideTimer);
        }

        // 메뉴 그룹에 호버 이벤트 바인딩
        document.querySelectorAll('.has-submenu').forEach(function(menuDiv) {
            menuDiv.addEventListener('mouseenter', function() { showFlyout(menuDiv); });
            menuDiv.addEventListener('mouseleave', scheduleFlyoutHide);
        });

        // 플라이아웃 자체에도 호버 유지
        flyout.addEventListener('mouseenter', cancelFlyoutHide);
        flyout.addEventListener('mouseleave', function() { flyout.classList.add('hidden'); });
    })();

    // ===== 서브메뉴 토글 (펼친 상태) =====

    function isCollapsed() {
        return document.getElementById('adminSidebar').classList.contains('sidebar-collapsed');
    }

    function toggleReservationsMenu() {
        if (isCollapsed()) return;
        var s = document.getElementById('reservationsSubMenu'), a = document.getElementById('reservationsMenuArrow');
        if (s && a) { s.classList.toggle('hidden'); a.classList.toggle('rotate-180'); }
    }
    function toggleServicesMenu() {
        if (isCollapsed()) return;
        var s = document.getElementById('servicesSubMenu'), a = document.getElementById('servicesMenuArrow');
        if (s && a) { s.classList.toggle('hidden'); a.classList.toggle('rotate-180'); }
    }
    function toggleSiteMenu() {
        if (isCollapsed()) return;
        var s = document.getElementById('siteSubMenu'), a = document.getElementById('siteMenuArrow');
        if (s && a) { s.classList.toggle('hidden'); a.classList.toggle('rotate-180'); }
    }
    function toggleStaffMenu() {
        if (isCollapsed()) return;
        var s = document.getElementById('staffSubMenu'), a = document.getElementById('staffMenuArrow');
        if (s && a) { s.classList.toggle('hidden'); a.classList.toggle('rotate-180'); }
    }
    function toggleMembersMenu() {
        if (isCollapsed()) return;
        var s = document.getElementById('membersSubMenu'), a = document.getElementById('membersMenuArrow');
        if (s && a) { s.classList.toggle('hidden'); a.classList.toggle('rotate-180'); }
    }
    function toggleSettingsMenu() {
        if (isCollapsed()) return;
        var s = document.getElementById('settingsSubMenu'), a = document.getElementById('settingsMenuArrow');
        if (s && a) { s.classList.toggle('hidden'); a.classList.toggle('rotate-180'); }
    }
</script>
