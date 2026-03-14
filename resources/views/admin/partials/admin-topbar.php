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

        <span class="text-sm text-zinc-500 dark:text-zinc-400"><?= date('Y-m-d H:i') ?></span>
        <div class="flex items-center">
            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mr-2">Admin</span>
            <div class="w-8 h-8 bg-blue-600 rounded-full flex items-center justify-center text-white text-sm font-medium">A</div>
        </div>
    </div>
</header>

<script>
(function() {
    // Dark mode toggle
    var darkModeBtn = document.getElementById('darkModeBtn');
    if (darkModeBtn) {
        darkModeBtn.addEventListener('click', function() {
            var isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('darkMode', isDark);
            console.log('[TopBar] Dark mode:', isDark ? 'enabled' : 'disabled');
        });
    }
})();
</script>
