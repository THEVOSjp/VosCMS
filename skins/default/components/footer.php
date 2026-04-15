<?php
/**
 * RezlyX Default Theme - Footer Component
 *
 * DB 메뉴 기반: "Footer Menu" 사이트맵의 메뉴를 렌더링
 */
$appName = $config['app_name'] ?? 'RezlyX';
$baseUrl = $baseUrl ?? $config['app_url'] ?? '';
$year = date('Y');

// 메뉴 데이터 로드 (header에서 이미 로드되었으면 재사용)
include_once BASE_PATH . '/resources/views/components/menu-loader.php';
$footerMenu = $siteMenus['Footer Menu'] ?? [];
$utilityMenu = $siteMenus['Utility Menu'] ?? [];
?>
<footer class="bg-white dark:bg-zinc-800 border-t border-zinc-200 dark:border-zinc-700 mt-auto transition-colors duration-200">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <!-- Main Footer -->
        <div class="py-12 grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-8">
            <!-- Brand -->
            <div class="lg:col-span-1">
                <a href="<?= $baseUrl ?>/" class="flex items-center space-x-2">
                    <svg class="w-8 h-8 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                              d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                    </svg>
                    <span class="text-xl font-bold text-blue-600 dark:text-blue-400"><?= htmlspecialchars($appName) ?></span>
                </a>
                <p class="mt-4 text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed">
                    <?= __('common.footer.tagline') ?? '예약을 넘어, 세계로' ?><br>
                    <span class="text-zinc-500"><?= __('common.footer.tagline_en') ?? 'Beyond reservation, to the world.' ?></span>
                </p>
            </div>

            <!-- Footer Menu Links -->
            <?php if (!empty($footerMenu)): ?>
            <div>
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider mb-4">
                    <?= __('common.footer.links') ?? '바로가기' ?>
                </h3>
                <ul class="space-y-3">
                    <?php foreach ($footerMenu as $item):
                        $href = rzxMenuUrl($item, $baseUrl);
                    ?>
                    <li>
                        <a href="<?= htmlspecialchars($href) ?>"
                           class="text-sm text-zinc-600 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                            <?= htmlspecialchars($item['title']) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Utility Menu Links -->
            <?php if (!empty($utilityMenu)): ?>
            <div>
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider mb-4">
                    <?= __('common.footer.info') ?? '정보' ?>
                </h3>
                <ul class="space-y-3">
                    <?php foreach ($utilityMenu as $item):
                        $href = rzxMenuUrl($item, $baseUrl);
                    ?>
                    <li>
                        <a href="<?= htmlspecialchars($href) ?>"
                           class="text-sm text-zinc-600 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                            <?= htmlspecialchars($item['title']) ?>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- Contact -->
            <div>
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white uppercase tracking-wider mb-4">
                    <?= __('common.footer.contact') ?? '연락처' ?>
                </h3>
                <ul class="space-y-3 text-sm text-zinc-600 dark:text-zinc-400">
                    <li class="flex items-center space-x-3">
                        <svg class="w-5 h-5 text-zinc-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <span><?= htmlspecialchars($siteSettings['contact_email'] ?? 'contact@rezlyx.com') ?></span>
                    </li>
                </ul>
            </div>
        </div>

        <!-- Bottom Bar -->
        <div class="py-6 border-t border-zinc-200 dark:border-zinc-700">
            <div class="flex flex-col md:flex-row items-center justify-between space-y-4 md:space-y-0">
                <p class="text-sm text-zinc-500 dark:text-zinc-400">
                    &copy; <?= $year ?> <?= htmlspecialchars($appName) ?>. All rights reserved.
                </p>
                <?php if (!empty($footerMenu)): ?>
                <div class="flex items-center space-x-6">
                    <?php foreach ($footerMenu as $item):
                        $href = rzxMenuUrl($item, $baseUrl);
                    ?>
                    <a href="<?= htmlspecialchars($href) ?>"
                       class="text-sm text-zinc-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                        <?= htmlspecialchars($item['title']) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</footer>
