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
</body>
</html>
