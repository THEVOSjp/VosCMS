    </main>

    <!-- Footer -->
    <footer class="bg-white dark:bg-zinc-800 border-t dark:border-zinc-700 transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col md:flex-row items-center justify-between">
                <p class="text-gray-500 dark:text-zinc-400 text-sm">
                    <?= __('common.footer.copyright', ['year' => date('Y')]) ?>
                </p>
                <div class="flex items-center space-x-6 mt-4 md:mt-0">
                    <a href="<?= $baseUrl ?>/terms" class="text-sm text-gray-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400"><?= __('common.footer.terms') ?></a>
                    <a href="<?= $baseUrl ?>/privacy" class="text-sm text-gray-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400"><?= __('common.footer.privacy') ?></a>
                </div>
            </div>
        </div>
    </footer>

    <script>
    // 사용자 메뉴 드롭다운
    const userMenuBtn = document.getElementById('userMenuBtn');
    const userMenuDropdown = document.getElementById('userMenuDropdown');
    if (userMenuBtn && userMenuDropdown) {
        userMenuBtn.addEventListener('click', (e) => { e.stopPropagation(); userMenuDropdown.classList.toggle('hidden'); });
    }
    document.addEventListener('click', () => { if (userMenuDropdown) userMenuDropdown.classList.add('hidden'); });
    // 다크 모드 토글
    document.getElementById('darkModeBtn')?.addEventListener('click', () => {
        const isDark = document.documentElement.classList.toggle('dark');
        localStorage.setItem('darkMode', isDark);
    });
    </script>
    <?php if (($siteSettings['pwa_front_enabled'] ?? '1') === '1'): ?>
    <script>
    if ('serviceWorker' in navigator) {
        window.addEventListener('load', async () => {
            try {
                const bp = '<?= rtrim($baseUrl, "/") ?>';
                await navigator.serviceWorker.register(bp + '/sw.js', { scope: bp + '/' });
            } catch (e) { console.error('[PWA] SW failed:', e); }
        });
    }
    </script>
    <?php endif; ?>
    <?php if (isset($footerExtra)) echo $footerExtra; ?>

    <!-- 모바일 하단 메뉴바 (md 이하에서만 표시) -->
    <?php $_mPath = trim(str_replace(rtrim($baseUrl, '/'), '', parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH)), '/'); ?>
    <nav class="md:hidden fixed bottom-0 left-0 right-0 z-50 bg-white dark:bg-zinc-800 border-t border-zinc-200 dark:border-zinc-700 safe-area-bottom">
        <div class="flex items-center justify-around h-14">
            <a href="<?= $baseUrl ?>/staff" class="flex flex-col items-center justify-center flex-1 py-1 text-zinc-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition <?= $_mPath === 'staff' ? 'text-blue-600 dark:text-blue-400' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span class="text-[10px] mt-0.5"><?= __('common.nav.staff') ?? '스태프' ?></span>
            </a>
            <a href="<?= $baseUrl ?>/booking" class="flex flex-col items-center justify-center flex-1 py-1 text-zinc-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition <?= $_mPath === 'booking' ? 'text-blue-600 dark:text-blue-400' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span class="text-[10px] mt-0.5"><?= __('common.nav.booking') ?? '예약' ?></span>
            </a>
            <a href="<?= $baseUrl ?>/" class="flex flex-col items-center justify-center flex-1 py-1 text-zinc-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition <?= $_mPath === '' ? 'text-blue-600 dark:text-blue-400' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                <span class="text-[10px] mt-0.5"><?= __('common.nav.home') ?? '홈' ?></span>
            </a>
            <?php if ($isLoggedIn): ?>
            <a href="<?= $baseUrl ?>/mypage" class="flex flex-col items-center justify-center flex-1 py-1 text-zinc-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition <?= str_starts_with($_mPath, 'mypage') ? 'text-blue-600 dark:text-blue-400' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <span class="text-[10px] mt-0.5"><?= __('common.nav.mypage') ?? '마이' ?></span>
            </a>
            <?php else: ?>
            <a href="<?= $baseUrl ?>/login" class="flex flex-col items-center justify-center flex-1 py-1 text-zinc-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition <?= $_mPath === 'login' ? 'text-blue-600 dark:text-blue-400' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                <span class="text-[10px] mt-0.5"><?= __('common.buttons.login') ?? '로그인' ?></span>
            </a>
            <a href="<?= $baseUrl ?>/register" class="flex flex-col items-center justify-center flex-1 py-1 text-zinc-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition <?= $_mPath === 'register' ? 'text-blue-600 dark:text-blue-400' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                <span class="text-[10px] mt-0.5"><?= __('common.buttons.register') ?? '회원가입' ?></span>
            </a>
            <?php endif; ?>
            <button onclick="document.getElementById('mobileMenuPanel').classList.toggle('hidden')" class="flex flex-col items-center justify-center flex-1 py-1 text-zinc-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                <span class="text-[10px] mt-0.5"><?= __('common.nav.more') ?? '더보기' ?></span>
            </button>
        </div>
    </nav>

    <!-- 모바일 더보기 패널 -->
    <div id="mobileMenuPanel" class="hidden md:hidden fixed bottom-14 left-0 right-0 z-40 bg-white dark:bg-zinc-800 border-t border-zinc-200 dark:border-zinc-700 shadow-lg safe-area-bottom max-h-[60vh] overflow-y-auto">
        <div class="p-4 space-y-1">
            <?php foreach ($mainMenu as $__mi):
                $__href = rzxMenuUrl($__mi, $baseUrl);
            ?>
            <a href="<?= htmlspecialchars($__href) ?>" class="flex items-center gap-3 px-3 py-2.5 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg">
                <?= htmlspecialchars($__mi['title']) ?>
            </a>
            <?php if (!empty($__mi['children'])): ?>
                <?php foreach ($__mi['children'] as $__ch): ?>
                <a href="<?= htmlspecialchars(rzxMenuUrl($__ch, $baseUrl)) ?>" class="flex items-center gap-3 px-3 py-2 text-sm text-zinc-500 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg pl-8">
                    <?= htmlspecialchars($__ch['title']) ?>
                </a>
                <?php endforeach; ?>
            <?php endif; ?>
            <?php endforeach; ?>
            <div class="border-t border-zinc-200 dark:border-zinc-700 my-2"></div>
            <?php if ($isLoggedIn): ?>
            <a href="<?= $baseUrl ?>/mypage" class="flex items-center gap-3 px-3 py-2.5 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                <?= __('common.nav.mypage') ?>
            </a>
            <a href="<?= $baseUrl ?>/logout" class="flex items-center gap-3 px-3 py-2.5 text-sm text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                <?= __('common.buttons.logout') ?>
            </a>
            <?php endif; ?>
        </div>
    </div>

    <style>
    /* 모바일 하단 메뉴바 safe area */
    .safe-area-bottom { padding-bottom: env(safe-area-inset-bottom, 0); }
    /* 하단 메뉴바 높이만큼 본문 여백 (모바일) */
    @media (max-width: 767px) { body { padding-bottom: 3.5rem; } }
    </style>
    <?php if (isset($footerExtra)) echo $footerExtra; ?>
</body>
</html>
