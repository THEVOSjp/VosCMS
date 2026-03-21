<?php
/**
 * RezlyX Admin Top Bar Component
 * 관리자 상단 바 (제목, 언어 선택, 다크모드 토글)
 *
 * Required variables:
 * - $pageHeaderTitle: string - 페이지 헤더 제목
 *
 * Optional variables:
 * - $config: array - 설정 배열
 * - $siteSettings: array - DB 설정 (language-selector가 자동 로드)
 */
?>
<header class="bg-white dark:bg-zinc-800 shadow-sm h-16 flex items-center justify-between px-6 transition-colors">
    <h1 class="text-xl font-semibold text-zinc-900 dark:text-white"><?= htmlspecialchars($pageHeaderTitle ?? '') ?></h1>
    <div class="flex items-center space-x-4">
        <!-- Update Notification Badge -->
        <?php if (!empty($updateInfo) && !empty($updateInfo['has_update'])): ?>
        <?php
            $updateUrl = ($config['app_url'] ?? '') . '/' . ($config['admin_path'] ?? 'admin') . '/settings/system/updates';
        ?>
        <a href="<?= htmlspecialchars($updateUrl) ?>" class="relative p-2 text-orange-500 hover:bg-orange-50 dark:hover:bg-orange-900/30 rounded-lg transition group" title="<?= __('system.updates.new_version_available') ?>">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/>
            </svg>
            <span class="absolute -top-0.5 -right-0.5 w-3 h-3 bg-red-500 rounded-full border-2 border-white dark:border-zinc-800 animate-pulse"></span>
            <span class="absolute invisible group-hover:visible -bottom-10 left-1/2 -translate-x-1/2 px-2 py-1 bg-zinc-900 dark:bg-zinc-100 text-white dark:text-zinc-900 text-xs rounded whitespace-nowrap z-50">
                v<?= htmlspecialchars($updateInfo['latest'] ?? '') ?> <?= __('system.updates.available_short') ?>
            </span>
        </a>
        <?php endif; ?>

        <!-- Language Selector (공용 컴포넌트) -->
        <?php include BASE_PATH . '/resources/views/components/language-selector.php'; ?>

        <!-- Dark Mode Toggle -->
        <button id="darkModeBtn" class="p-2 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition" title="<?= __('admin.dark_mode') ?>">
            <svg id="sunIcon" class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
            </svg>
            <svg id="moonIcon" class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
            </svg>
        </button>

        <span id="topbarClock" class="text-sm font-mono text-blue-600 dark:text-blue-400"><?= date('Y-m-d H:i:s') ?></span>
        <?php
        $_adminName = $_SESSION['admin_name'] ?? 'Admin';
        $_adminEmail = $_SESSION['admin_email'] ?? '';
        $_adminInitial = mb_substr($_adminName, 0, 1);
        $_baseUrl = $config['app_url'] ?? '';
        // 프로필 사진 조회 (staff avatar → user profile_image)
        $_adminAvatar = '';
        if (isset($pdo) && !empty($_SESSION['admin_id'])) {
            try {
                $_prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
                $_aStmt = $pdo->prepare("SELECT s.avatar FROM {$_prefix}admins a LEFT JOIN {$_prefix}staff s ON a.staff_id = s.id WHERE a.id = ?");
                $_aStmt->execute([$_SESSION['admin_id']]);
                $_aRow = $_aStmt->fetch(PDO::FETCH_ASSOC);
                $_adminAvatar = $_aRow['avatar'] ?? '';
            } catch (Exception $e) {}
        }
        ?>
        <div class="relative" id="adminProfileWrap">
            <button type="button" id="adminProfileBtn" class="flex items-center gap-2 px-2 py-1 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($_adminName) ?></span>
                <?php if ($_adminAvatar): ?>
                <img src="<?= htmlspecialchars($_adminAvatar) ?>" class="w-8 h-8 rounded-full object-cover" onerror="this.style.display='none';this.nextElementSibling.style.display='flex'">
                <div class="w-8 h-8 bg-blue-600 rounded-full items-center justify-center text-white text-sm font-semibold" style="display:none"><?= htmlspecialchars($_adminInitial) ?></div>
                <?php else: ?>
                <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-semibold"><?= htmlspecialchars($_adminInitial) ?></div>
                <?php endif; ?>
                <svg class="w-3.5 h-3.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div id="adminProfileDropdown" class="hidden absolute right-0 mt-2 w-56 bg-white dark:bg-zinc-800 rounded-lg shadow-lg border border-zinc-200 dark:border-zinc-700 py-1 z-50">
                <div class="px-4 py-2 border-b border-zinc-100 dark:border-zinc-700">
                    <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($_adminName) ?></p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= htmlspecialchars($_adminEmail) ?></p>
                </div>
                <a href="<?= $_baseUrl ?>/" class="flex items-center gap-2 px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    <?= __('common.nav.home') ?? '홈페이지' ?>
                </a>
                <a href="<?= $_baseUrl ?>/mypage" class="flex items-center gap-2 px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <?= __('common.nav.mypage') ?? '마이페이지' ?>
                </a>
                <div class="border-t border-zinc-100 dark:border-zinc-700"></div>
                <a href="<?= $_baseUrl ?>/<?= $config['admin_path'] ?? 'admin' ?>/logout" class="flex items-center gap-2 px-4 py-2 text-sm text-red-600 dark:text-red-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <?= __('common.buttons.logout') ?? '로그아웃' ?>
                </a>
            </div>
        </div>
    </div>
</header>

<script>
(function() {
    // Admin profile dropdown
    var profileBtn = document.getElementById('adminProfileBtn');
    var profileDrop = document.getElementById('adminProfileDropdown');
    if (profileBtn && profileDrop) {
        profileBtn.addEventListener('click', function(e) { e.stopPropagation(); profileDrop.classList.toggle('hidden'); });
        document.addEventListener('click', function(e) { if (!e.target.closest('#adminProfileWrap')) profileDrop.classList.add('hidden'); });
    }

    // Dark mode toggle
    var darkModeBtn = document.getElementById('darkModeBtn');
    if (darkModeBtn) {
    // 시계 업데이트
    var clockEl = document.getElementById('topbarClock');
    if (clockEl) {
        setInterval(function() {
            var d = new Date();
            var y = d.getFullYear();
            var m = String(d.getMonth() + 1).padStart(2, '0');
            var day = String(d.getDate()).padStart(2, '0');
            var h = String(d.getHours()).padStart(2, '0');
            var min = String(d.getMinutes()).padStart(2, '0');
            var s = String(d.getSeconds()).padStart(2, '0');
            clockEl.textContent = y + '-' + m + '-' + day + ' ' + h + ':' + min + ':' + s;
        }, 1000);
    }

        darkModeBtn.addEventListener('click', function() {
            var isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('darkMode', isDark);
            console.log('[TopBar] Dark mode:', isDark ? 'enabled' : 'disabled');
        });
    }
})();
</script>
