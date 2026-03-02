<?php
/**
 * RezlyX Default Theme - Breadcrumbs Component
 *
 * 사용법:
 * $breadcrumbs = [
 *     ['label' => '홈', 'url' => '/'],
 *     ['label' => '서비스', 'url' => '/services'],
 *     ['label' => '상세'], // 마지막 항목은 url 없음
 * ];
 */
$baseUrl = $baseUrl ?? $config['app_url'] ?? '';
?>
<?php if (isset($breadcrumbs) && is_array($breadcrumbs) && count($breadcrumbs) > 0): ?>
<nav class="bg-zinc-100 dark:bg-zinc-800/50 border-b border-zinc-200 dark:border-zinc-700 transition-colors">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
        <ol class="flex items-center space-x-2 py-3 text-sm">
            <?php foreach ($breadcrumbs as $index => $item): ?>
                <?php $isLast = $index === count($breadcrumbs) - 1; ?>

                <?php if ($index > 0): ?>
                    <li class="text-zinc-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                        </svg>
                    </li>
                <?php endif; ?>

                <li>
                    <?php if (!$isLast && isset($item['url'])): ?>
                        <a href="<?php echo $baseUrl . htmlspecialchars($item['url']); ?>"
                           class="text-zinc-600 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition-colors">
                            <?php if ($index === 0): ?>
                                <svg class="w-4 h-4 inline-block mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2"
                                          d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                </svg>
                            <?php endif; ?>
                            <?php echo htmlspecialchars($item['label']); ?>
                        </a>
                    <?php else: ?>
                        <span class="text-zinc-900 dark:text-white font-medium">
                            <?php echo htmlspecialchars($item['label']); ?>
                        </span>
                    <?php endif; ?>
                </li>
            <?php endforeach; ?>
        </ol>
    </div>
</nav>
<?php endif; ?>
