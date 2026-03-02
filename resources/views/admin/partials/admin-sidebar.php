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

// 사이트 관리 서브페이지 여부
$isSitePage = strpos($currentPath, '/site/') !== false;

// 설정 서브페이지 여부
$isSettingsPage = strpos($currentPath, '/settings') !== false;
?>
<aside class="w-64 bg-zinc-950 min-h-screen fixed">
    <div class="p-6">
        <a href="<?php echo $adminUrl; ?>" class="text-xl font-bold text-white">
            <?php echo htmlspecialchars($config['app_name'] ?? 'RezlyX'); ?>
            <span class="text-blue-400 text-sm ml-1"><?= __('admin.title') ?></span>
        </a>
    </div>
    <nav class="mt-6">
        <a href="<?php echo $adminUrl; ?>" class="flex items-center px-6 py-3 <?php echo !isActiveMenu('/settings', $currentPath) && !isActiveMenu('/reservations', $currentPath) && !isActiveMenu('/services', $currentPath) && !isActiveMenu('/members', $currentPath) && !isActiveMenu('/points', $currentPath) && !$isSitePage ? 'text-white bg-blue-600' : 'text-zinc-300 hover:bg-zinc-800 hover:text-white'; ?>">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
            </svg>
            <?= __('admin.nav.dashboard') ?>
        </a>
        <a href="<?php echo $adminUrl; ?>/reservations" class="flex items-center px-6 py-3 <?php echo isActiveMenu('/reservations', $currentPath) ? 'text-white bg-blue-600' : 'text-zinc-300 hover:bg-zinc-800 hover:text-white'; ?>">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
            <?= __('admin.nav.reservations') ?>
        </a>
        <a href="<?php echo $adminUrl; ?>/services" class="flex items-center px-6 py-3 <?php echo isActiveMenu('/services', $currentPath) ? 'text-white bg-blue-600' : 'text-zinc-300 hover:bg-zinc-800 hover:text-white'; ?>">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            <?= __('admin.nav.services') ?>
        </a>
        <a href="<?php echo $adminUrl; ?>/members" class="flex items-center px-6 py-3 <?php echo isActiveMenu('/members', $currentPath) ? 'text-white bg-blue-600' : 'text-zinc-300 hover:bg-zinc-800 hover:text-white'; ?>">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <?= __('admin.nav.members') ?>
        </a>
        <a href="<?php echo $adminUrl; ?>/points" class="flex items-center px-6 py-3 <?php echo isActiveMenu('/points', $currentPath) ? 'text-white bg-blue-600' : 'text-zinc-300 hover:bg-zinc-800 hover:text-white'; ?>">
            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <?= __('admin.nav.points') ?>
        </a>
        <!-- 사이트 관리 메뉴 -->
        <div class="site-management-menu">
            <button onclick="toggleSiteMenu()" class="flex items-center justify-between w-full px-6 py-3 <?php echo $isSitePage ? 'text-white bg-zinc-800' : 'text-zinc-300 hover:bg-zinc-800 hover:text-white'; ?>">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                    </svg>
                    <?= __('admin.nav.site_management') ?>
                </div>
                <svg id="siteMenuArrow" class="w-4 h-4 transition-transform <?php echo $isSitePage ? 'rotate-180' : ''; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
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
                <a href="<?php echo $adminUrl; ?>/site/pages" class="flex items-center px-6 py-2.5 pl-14 <?php echo isActiveMenu('/site/pages', $currentPath) ? 'text-blue-400 bg-zinc-800' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?> text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                    </svg>
                    <?= __('admin.nav.page_management') ?>
                </a>
            </div>
        </div>
        <!-- 설정 메뉴 -->
        <div class="settings-menu">
            <button onclick="toggleSettingsMenu()" class="flex items-center justify-between w-full px-6 py-3 <?php echo $isSettingsPage ? 'text-white bg-zinc-800' : 'text-zinc-300 hover:bg-zinc-800 hover:text-white'; ?>">
                <div class="flex items-center">
                    <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <?= __('admin.nav.settings') ?>
                </div>
                <svg id="settingsMenuArrow" class="w-4 h-4 transition-transform <?php echo $isSettingsPage ? 'rotate-180' : ''; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                </svg>
            </button>
            <div id="settingsSubMenu" class="<?php echo $isSettingsPage ? '' : 'hidden'; ?> bg-zinc-900">
                <a href="<?php echo $adminUrl; ?>/settings/general" class="flex items-center px-6 py-2.5 pl-14 <?php echo isActiveMenu('/settings/general', $currentPath) ? 'text-blue-400 bg-zinc-800' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?> text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                    <?= __('admin.settings.tabs.general') ?>
                </a>
                <a href="<?php echo $adminUrl; ?>/settings/seo" class="flex items-center px-6 py-2.5 pl-14 <?php echo isActiveMenu('/settings/seo', $currentPath) ? 'text-blue-400 bg-zinc-800' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?> text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <?= __('admin.settings.tabs.seo') ?>
                </a>
                <a href="<?php echo $adminUrl; ?>/settings/pwa" class="flex items-center px-6 py-2.5 pl-14 <?php echo isActiveMenu('/settings/pwa', $currentPath) ? 'text-blue-400 bg-zinc-800' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?> text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z"/>
                    </svg>
                    <?= __('admin.settings.tabs.pwa') ?>
                </a>
                <a href="<?php echo $adminUrl; ?>/settings/system" class="flex items-center px-6 py-2.5 pl-14 <?php echo isActiveMenu('/settings/system', $currentPath) ? 'text-blue-400 bg-zinc-800' : 'text-zinc-400 hover:bg-zinc-800 hover:text-white'; ?> text-sm">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 3v2m6-2v2M9 19v2m6-2v2M5 9H3m2 6H3m18-6h-2m2 6h-2M7 19h10a2 2 0 002-2V7a2 2 0 00-2-2H7a2 2 0 00-2 2v10a2 2 0 002 2zM9 9h6v6H9V9z"/>
                    </svg>
                    <?= __('admin.settings.tabs.system') ?>
                </a>
            </div>
        </div>
    </nav>
    <div class="absolute bottom-0 w-64 p-4 border-t border-zinc-800/50">
        <?php
        $versionFile = BASE_PATH . '/version.json';
        $versionInfo = file_exists($versionFile) ? json_decode(file_get_contents($versionFile), true) : ['version' => '1.0.0'];
        ?>
        <div class="flex items-center justify-between text-zinc-500 text-xs">
            <span class="font-medium">RezlyX</span>
            <span>v<?= htmlspecialchars($versionInfo['version'] ?? '1.0.0') ?></span>
        </div>
    </div>
</aside>
