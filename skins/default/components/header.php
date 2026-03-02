<?php
/**
 * RezlyX Default Theme - Header Component
 *
 * 반응형 네비게이션 헤더 (다국어 지원)
 */
$appName = $config['app_name'] ?? 'RezlyX';
$baseUrl = $baseUrl ?? $config['app_url'] ?? '';
$currentPath = $_SERVER['REQUEST_URI'] ?? '/';

// 현재 로케일 가져오기
$currentLocale = function_exists('current_locale') ? current_locale() : 'ko';
$localeLabels = [
    'ko' => 'KO',
    'en' => 'EN',
    'ja' => 'JA',
];
$currentLocaleLabel = $localeLabels[$currentLocale] ?? 'KO';

// 언어별 이름
$languages = [
    'ko' => '한국어',
    'en' => 'English',
    'ja' => '日本語',
];
?>
<header class="bg-white dark:bg-zinc-800 shadow-sm sticky top-0 z-50 transition-colors duration-200"
        x-data="{ mobileMenu: false }">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <div class="flex items-center justify-between h-16">
            <!-- Logo -->
            <div class="flex items-center">
                <a href="<?php echo $baseUrl; ?>/" class="flex items-center space-x-2">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="text-xl font-bold text-blue-600 dark:text-blue-400">
                        <?php echo htmlspecialchars($appName); ?>
                    </span>
                </a>
            </div>

            <!-- Desktop Navigation -->
            <nav class="hidden md:flex items-center space-x-1">
                <a href="<?php echo $baseUrl; ?>/"
                   class="px-4 py-2 text-sm font-medium rounded-lg transition-colors
                          <?php echo $currentPath === '/' ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30' : 'text-zinc-600 dark:text-zinc-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-zinc-100 dark:hover:bg-zinc-700'; ?>">
                    <?= __('common.nav.home') ?>
                </a>
                <a href="<?php echo $baseUrl; ?>/services"
                   class="px-4 py-2 text-sm font-medium rounded-lg transition-colors
                          <?php echo strpos($currentPath, '/services') === 0 ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30' : 'text-zinc-600 dark:text-zinc-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-zinc-100 dark:hover:bg-zinc-700'; ?>">
                    <?= __('common.nav.services') ?>
                </a>
                <a href="<?php echo $baseUrl; ?>/booking"
                   class="px-4 py-2 text-sm font-medium rounded-lg transition-colors
                          <?php echo strpos($currentPath, '/booking') === 0 ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30' : 'text-zinc-600 dark:text-zinc-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-zinc-100 dark:hover:bg-zinc-700'; ?>">
                    <?= __('common.nav.booking') ?>
                </a>
                <a href="<?php echo $baseUrl; ?>/about"
                   class="px-4 py-2 text-sm font-medium rounded-lg transition-colors
                          <?php echo strpos($currentPath, '/about') === 0 ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30' : 'text-zinc-600 dark:text-zinc-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-zinc-100 dark:hover:bg-zinc-700'; ?>">
                    <?= __('common.nav.about') ?>
                </a>
                <a href="<?php echo $baseUrl; ?>/contact"
                   class="px-4 py-2 text-sm font-medium rounded-lg transition-colors
                          <?php echo strpos($currentPath, '/contact') === 0 ? 'text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30' : 'text-zinc-600 dark:text-zinc-300 hover:text-blue-600 dark:hover:text-blue-400 hover:bg-zinc-100 dark:hover:bg-zinc-700'; ?>">
                    <?= __('common.nav.contact') ?>
                </a>
            </nav>

            <!-- Right side controls -->
            <div class="flex items-center space-x-2">
                <!-- Language Selector -->
                <div class="relative" x-data="{ langOpen: false }">
                    <button @click="langOpen = !langOpen"
                            @click.away="langOpen = false"
                            class="flex items-center space-x-1 p-2 text-zinc-600 dark:text-zinc-300
                                   hover:text-blue-600 dark:hover:text-blue-400
                                   rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                        </svg>
                        <span class="text-xs font-medium hidden sm:inline"><?php echo $currentLocaleLabel; ?></span>
                    </button>
                    <div x-show="langOpen"
                         x-transition:enter="transition ease-out duration-100"
                         x-transition:enter-start="opacity-0 scale-95"
                         x-transition:enter-end="opacity-100 scale-100"
                         x-transition:leave="transition ease-in duration-75"
                         x-transition:leave-start="opacity-100 scale-100"
                         x-transition:leave-end="opacity-0 scale-95"
                         class="absolute right-0 mt-2 w-32 bg-white dark:bg-zinc-800 rounded-lg shadow-lg
                                border border-zinc-200 dark:border-zinc-700 py-1 z-50">
                        <?php foreach ($languages as $code => $name): ?>
                            <a href="?lang=<?php echo $code; ?>"
                               class="block px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300
                                      hover:bg-zinc-100 dark:hover:bg-zinc-700
                                      <?php echo $currentLocale === $code ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400' : ''; ?>">
                                <?php echo $name; ?>
                                <?php if ($currentLocale === $code): ?>
                                    <svg class="inline-block w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                    </svg>
                                <?php endif; ?>
                            </a>
                        <?php endforeach; ?>
                    </div>
                </div>

                <!-- Dark Mode Toggle -->
                <button onclick="toggleDarkMode()"
                        class="p-2 text-zinc-600 dark:text-zinc-300
                               hover:text-blue-600 dark:hover:text-blue-400
                               rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                    <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                    </svg>
                    <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                    </svg>
                </button>

                <!-- Auth Buttons -->
                <?php if (function_exists('auth') && auth()->check()): ?>
                    <a href="<?php echo $baseUrl; ?>/my"
                       class="p-2 text-zinc-600 dark:text-zinc-300
                              hover:text-blue-600 dark:hover:text-blue-400
                              rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                  d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                        </svg>
                    </a>
                <?php else: ?>
                    <a href="<?php echo $baseUrl; ?>/login"
                       class="hidden sm:inline-flex items-center px-4 py-2 text-sm font-medium
                              text-white bg-blue-600 hover:bg-blue-700
                              rounded-lg transition-colors shadow-sm">
                        <?= __('common.buttons.login') ?>
                    </a>
                <?php endif; ?>

                <!-- Mobile menu button -->
                <button @click="mobileMenu = !mobileMenu"
                        class="md:hidden p-2 text-zinc-600 dark:text-zinc-300
                               rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                    <svg x-show="!mobileMenu" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/>
                    </svg>
                    <svg x-show="mobileMenu" class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
        </div>
    </div>

    <!-- Mobile Navigation -->
    <div x-show="mobileMenu"
         x-transition:enter="transition ease-out duration-200"
         x-transition:enter-start="opacity-0 -translate-y-2"
         x-transition:enter-end="opacity-100 translate-y-0"
         x-transition:leave="transition ease-in duration-150"
         x-transition:leave-start="opacity-100 translate-y-0"
         x-transition:leave-end="opacity-0 -translate-y-2"
         class="md:hidden bg-white dark:bg-zinc-800 border-t border-zinc-200 dark:border-zinc-700">
        <div class="px-4 py-3 space-y-1">
            <a href="<?php echo $baseUrl; ?>/"
               class="block px-4 py-3 text-zinc-700 dark:text-zinc-300
                      hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition-colors">
                <?= __('common.nav.home') ?>
            </a>
            <a href="<?php echo $baseUrl; ?>/services"
               class="block px-4 py-3 text-zinc-700 dark:text-zinc-300
                      hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition-colors">
                <?= __('common.nav.services') ?>
            </a>
            <a href="<?php echo $baseUrl; ?>/booking"
               class="block px-4 py-3 text-zinc-700 dark:text-zinc-300
                      hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition-colors">
                <?= __('common.nav.booking') ?>
            </a>
            <a href="<?php echo $baseUrl; ?>/about"
               class="block px-4 py-3 text-zinc-700 dark:text-zinc-300
                      hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition-colors">
                <?= __('common.nav.about') ?>
            </a>
            <a href="<?php echo $baseUrl; ?>/contact"
               class="block px-4 py-3 text-zinc-700 dark:text-zinc-300
                      hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition-colors">
                <?= __('common.nav.contact') ?>
            </a>

            <?php if (!function_exists('auth') || !auth()->check()): ?>
                <div class="pt-2 border-t border-zinc-200 dark:border-zinc-700">
                    <a href="<?php echo $baseUrl; ?>/login"
                       class="block px-4 py-3 text-center text-white bg-blue-600 hover:bg-blue-700
                              rounded-lg transition-colors font-medium">
                        <?= __('common.buttons.login') ?>
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </div>
</header>
