    <!-- Footer -->
    <footer class="bg-white dark:bg-zinc-800 border-t dark:border-zinc-700 mt-auto">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
            <div class="grid grid-cols-1 md:grid-cols-4 gap-8">
                <!-- 회사 정보 -->
                <div class="col-span-1 md:col-span-2">
                    <a href="<?= $baseUrl ?>/" class="text-xl font-bold text-blue-600 dark:text-blue-400">
                        <?= htmlspecialchars($config['app_name'] ?? 'RezlyX') ?>
                    </a>
                    <p class="mt-3 text-sm text-gray-500 dark:text-zinc-400">
                        <?= __('home.footer.description') ?>
                    </p>
                </div>
                <!-- 빠른 링크 -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3"><?= __('home.footer.quick_links') ?></h3>
                    <ul class="space-y-2">
                        <li><a href="<?= $baseUrl ?>/services" class="text-sm text-gray-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400"><?= __('common.nav.services') ?></a></li>
                        <li><a href="<?= $baseUrl ?>/booking" class="text-sm text-gray-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400"><?= __('common.nav.booking') ?></a></li>
                        <li><a href="<?= $baseUrl ?>/contact" class="text-sm text-gray-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400"><?= __('common.nav.contact') ?></a></li>
                    </ul>
                </div>
                <!-- 고객 지원 -->
                <div>
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-3"><?= __('home.footer.support') ?></h3>
                    <ul class="space-y-2">
                        <li><a href="<?= $baseUrl ?>/faq" class="text-sm text-gray-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400"><?= __('common.nav.faq') ?></a></li>
                        <li><a href="<?= $baseUrl ?>/terms" class="text-sm text-gray-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400"><?= __('common.nav.terms') ?></a></li>
                        <li><a href="<?= $baseUrl ?>/privacy" class="text-sm text-gray-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400"><?= __('common.nav.privacy') ?></a></li>
                    </ul>
                </div>
            </div>
            <div class="border-t dark:border-zinc-700 mt-8 pt-8 text-center">
                <p class="text-sm text-gray-500 dark:text-zinc-400">
                    &copy; <?= date('Y') ?> <?= htmlspecialchars($config['app_name'] ?? 'RezlyX') ?>. <?= __('home.footer.copyright') ?>
                </p>
            </div>
        </div>
    </footer>

    <script>
        // 언어 드롭다운 토글
        const langBtn = document.getElementById('langBtn');
        const langDropdown = document.getElementById('langDropdown');

        if (langBtn && langDropdown) {
            langBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                langDropdown.classList.toggle('hidden');
                console.log('[Header] 언어 드롭다운 토글');
            });
        }

        // 사용자 메뉴 드롭다운
        const userMenuBtn = document.getElementById('userMenuBtn');
        const userMenuDropdown = document.getElementById('userMenuDropdown');

        if (userMenuBtn && userMenuDropdown) {
            userMenuBtn.addEventListener('click', (e) => {
                e.stopPropagation();
                userMenuDropdown.classList.toggle('hidden');
                if (langDropdown) langDropdown.classList.add('hidden');
                console.log('[Header] 사용자 메뉴 드롭다운 토글');
            });
        }

        // 외부 클릭 시 드롭다운 닫기
        document.addEventListener('click', () => {
            if (langDropdown) langDropdown.classList.add('hidden');
            if (userMenuDropdown) userMenuDropdown.classList.add('hidden');
        });

        // 다크 모드 토글
        const darkModeBtn = document.getElementById('darkModeBtn');

        if (darkModeBtn) {
            darkModeBtn.addEventListener('click', () => {
                const isDark = document.documentElement.classList.toggle('dark');
                localStorage.setItem('darkMode', isDark);
                console.log('[Header] 다크 모드 토글:', isDark);
            });
        }

        console.log('[Footer] 공통 스크립트 로드 완료');
    </script>
</body>
</html>
