    </main>

    <!-- Footer -->
    <?php $footerMenu = $siteMenus['Footer Menu'] ?? []; ?>
    <?php
    $_lc = $__layoutConfig ?? [];
    $_contentWidth = $_lc['content_width'] ?? 'max-w-7xl';
    $_copyright = $_lc['copyright'] ?? '';
    if (is_array($_copyright)) $_copyright = $_copyright[\RzxLib\Core\I18n\Translator::getLocale()] ?? $_copyright['en'] ?? reset($_copyright) ?: '';
    $_copyright = str_replace('{year}', date('Y'), (string)$_copyright);
    ?>
    <footer class="bg-white dark:bg-zinc-800 border-t dark:border-zinc-700 transition-colors duration-200">
        <div class="<?= $_contentWidth ?> mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="flex flex-col md:flex-row items-center justify-between">
                <p class="text-gray-500 dark:text-zinc-400 text-sm">
                    <?= $_copyright ?: __('common.footer.copyright', ['year' => date('Y')]) ?>
                </p>
                <div class="flex flex-wrap items-center gap-x-6 gap-y-2 mt-4 md:mt-0">
                    <?php foreach ($footerMenu as $__fi): ?>
                    <a href="<?= htmlspecialchars(rzxMenuUrl($__fi, $baseUrl)) ?>" class="text-sm text-gray-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400"><?= htmlspecialchars($__fi['title']) ?></a>
                    <?php endforeach; ?>
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

    // 서브 메뉴 토글
    function toggleSubMenu(id) {
        const el = document.getElementById(id);
        if (!el) return;
        // 다른 서브 메뉴 닫기
        document.querySelectorAll('[id^="minSub"]').forEach(s => { if (s.id !== id) s.classList.add('hidden'); });
        el.classList.toggle('hidden');
        console.log('[MinimalMenu] toggleSubMenu:', id);
    }

    // 모바일 Bottom Sheet 메뉴
    function openMobileMenu() {
        document.getElementById('mobileMenuOverlay')?.classList.remove('hidden');
        document.getElementById('mobileMenuPanel')?.classList.add('open');
        document.body.style.overflow = 'hidden';
    }
    function closeMobileMenu() {
        document.getElementById('mobileMenuPanel')?.classList.remove('open');
        document.getElementById('mobileMenuOverlay')?.classList.add('hidden');
        document.body.style.overflow = '';
    }
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
    <?php
    $_mPath = trim(str_replace(rtrim($baseUrl, '/'), '', parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH)), '/');
    $_hasSalon = isset($pluginManager) && $pluginManager->isActive('vos-salon');
    ?>
    <nav class="lg:hidden fixed bottom-0 left-0 right-0 z-50 bg-white dark:bg-zinc-800 border-t border-zinc-200 dark:border-zinc-700 safe-area-bottom">
        <div class="flex items-center justify-around h-14">
            <?php if ($_hasSalon): ?>
            <a href="<?= $baseUrl ?>/staff" class="flex flex-col items-center justify-center flex-1 py-1 text-zinc-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition <?= $_mPath === 'staff' ? 'text-blue-600 dark:text-blue-400' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                <span class="text-[10px] mt-0.5"><?= __('common.nav.staff') ?? '스태프' ?></span>
            </a>
            <a href="<?= $baseUrl ?>/booking" class="flex flex-col items-center justify-center flex-1 py-1 text-zinc-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition <?= $_mPath === 'booking' ? 'text-blue-600 dark:text-blue-400' : '' ?>">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span class="text-[10px] mt-0.5"><?= __('common.nav.booking') ?? '예약' ?></span>
            </a>
            <?php endif; ?>
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
            <button onclick="openMobileMenu()" class="flex flex-col items-center justify-center flex-1 py-1 text-zinc-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
                <span class="text-[10px] mt-0.5"><?= __('common.nav.more') ?? '더보기' ?></span>
            </button>
        </div>
    </nav>

    <!-- 메뉴 오버레이 -->
    <div id="mobileMenuOverlay" class="hidden lg:hidden fixed inset-0 z-40 bg-black/40 backdrop-blur-sm transition-opacity" onclick="closeMobileMenu()"></div>

    <!-- Bottom Sheet 슬라이드 업 메뉴 -->
    <?php
    $_menuIcons = [
        'staff' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>',
        'booking' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
        'lookup' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2m-6 9l2 2 4-4"/>',
        'home' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>',
    ];
    $_defaultIcon = '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/>';
    $_menuCount = count($mainMenu) + 1;
    $_colW = $_menuCount <= 5 ? 'w-1/5' : ($_menuCount <= 6 ? 'w-1/6' : 'w-1/4');
    ?>
    <div id="mobileMenuPanel" class="lg:hidden fixed left-0 right-0 bottom-0 z-50 bg-white dark:bg-zinc-800 rounded-t-2xl shadow-2xl transform translate-y-full transition-transform duration-300 ease-in-out max-h-[70vh] overflow-y-auto safe-area-bottom">
        <!-- 핸들 바 -->
        <div class="flex justify-center pt-3 pb-1 cursor-pointer" onclick="closeMobileMenu()">
            <div class="w-10 h-1 bg-zinc-300 dark:bg-zinc-600 rounded-full"></div>
        </div>
        <!-- 메뉴 그리드 -->
        <div class="px-4 pt-2 pb-3">
            <div class="flex flex-wrap">
                <a href="<?= $baseUrl ?>/" class="flex flex-col items-center justify-center <?= $_colW ?> py-3 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-xl transition">
                    <div class="w-10 h-10 rounded-full bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center mb-1">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><?= $_menuIcons['home'] ?></svg>
                    </div>
                    <span class="text-[10px] text-zinc-700 dark:text-zinc-300"><?= __('common.nav.home') ?? '홈' ?></span>
                </a>
                <?php foreach ($mainMenu as $__miIdx => $__mi):
                    $__href = rzxMenuUrl($__mi, $baseUrl);
                    $__slug = strtolower(trim(parse_url($__href, PHP_URL_PATH) ?? '', '/'));
                    $__slug = basename($__slug);
                    $__icon = $_menuIcons[$__slug] ?? $_defaultIcon;
                    $__hasSub = !empty($__mi['children']);
                ?>
                <?php if ($__hasSub): ?>
                <button type="button" onclick="toggleSubMenu('minSub<?= $__miIdx ?>')" class="flex flex-col items-center justify-center <?= $_colW ?> py-3 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-xl transition">
                    <div class="w-10 h-10 rounded-full bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center mb-1 relative">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><?= $__icon ?></svg>
                        <span class="absolute -bottom-0.5 -right-0.5 w-3 h-3 bg-blue-500 rounded-full flex items-center justify-center">
                            <svg class="w-2 h-2 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M19 9l-7 7-7-7"/></svg>
                        </span>
                    </div>
                    <span class="text-[10px] text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($__mi['title']) ?></span>
                </button>
                <?php else: ?>
                <a href="<?= htmlspecialchars($__href) ?>" class="flex flex-col items-center justify-center <?= $_colW ?> py-3 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-xl transition">
                    <div class="w-10 h-10 rounded-full bg-blue-50 dark:bg-blue-900/30 flex items-center justify-center mb-1">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><?= $__icon ?></svg>
                    </div>
                    <span class="text-[10px] text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($__mi['title']) ?></span>
                </a>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <!-- 서브 메뉴 아코디언 -->
            <?php foreach ($mainMenu as $__miIdx => $__mi):
                if (empty($__mi['children'])) continue;
            ?>
            <div id="minSub<?= $__miIdx ?>" class="hidden px-2 pb-2">
                <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-xl p-2 space-y-0.5">
                    <a href="<?= htmlspecialchars(rzxMenuUrl($__mi, $baseUrl)) ?>" class="flex items-center gap-2 px-3 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-white dark:hover:bg-zinc-600 rounded-lg transition">
                        <?= htmlspecialchars($__mi['title']) ?>
                    </a>
                    <?php foreach ($__mi['children'] as $__ch): ?>
                    <a href="<?= htmlspecialchars(rzxMenuUrl($__ch, $baseUrl)) ?>" class="flex items-center gap-2 px-3 py-2 text-sm text-zinc-500 dark:text-zinc-400 hover:bg-white dark:hover:bg-zinc-600 rounded-lg transition pl-6">
                        <?= htmlspecialchars($__ch['title']) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <!-- 구분선 -->
            <div class="border-t border-zinc-200 dark:border-zinc-700 my-3"></div>
            <!-- 회원 메뉴 -->
            <div class="flex">
                <?php if ($isLoggedIn): ?>
                <a href="<?= $baseUrl ?>/mypage" class="flex flex-col items-center justify-center flex-1 py-2 text-zinc-600 dark:text-zinc-300 hover:text-blue-600 dark:hover:text-blue-400 transition">
                    <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <span class="text-[10px]"><?= __('common.nav.mypage') ?></span>
                </a>
                <a href="<?= $baseUrl ?>/logout" class="flex flex-col items-center justify-center flex-1 py-2 text-red-500 dark:text-red-400 hover:text-red-600 transition">
                    <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/></svg>
                    <span class="text-[10px]"><?= __('common.buttons.logout') ?></span>
                </a>
                <?php else: ?>
                <a href="<?= $baseUrl ?>/login" class="flex flex-col items-center justify-center flex-1 py-2 text-zinc-600 dark:text-zinc-300 hover:text-blue-600 dark:hover:text-blue-400 transition">
                    <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1"/></svg>
                    <span class="text-[10px]"><?= __('common.buttons.login') ?></span>
                </a>
                <a href="<?= $baseUrl ?>/register" class="flex flex-col items-center justify-center flex-1 py-2 text-blue-600 dark:text-blue-400 hover:text-blue-700 transition">
                    <svg class="w-5 h-5 mb-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
                    <span class="text-[10px]"><?= __('common.buttons.register') ?></span>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <style>
    /* 모바일 하단 메뉴바 safe area */
    .safe-area-bottom { padding-bottom: env(safe-area-inset-bottom, 0); }
    /* 하단 메뉴바 높이만큼 본문 여백 (모바일) */
    @media (max-width: 1023px) { body { padding-bottom: 3.5rem; } }
    #mobileMenuPanel.open { transform: translateY(0); }
    </style>
    <?php if (isset($footerExtra)) echo $footerExtra; ?>
</body>
</html>
