    <!-- 문서 페이지: HTML 콘텐츠 렌더링 (스킨 설정 적용) -->
    <div class="<?= $_contentWidth ?> mx-auto px-4 sm:px-6 py-6">
        <?php if ($_showBreadcrumb && !$_hasTitleBg): ?>
        <nav class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">
            <a href="<?= $config['app_url'] ?? '' ?>/" class="hover:text-blue-600"><?= __('common.home') ?? '홈' ?></a>
            <span class="mx-1">/</span>
            <span class="text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($pageTitle) ?></span>
        </nav>
        <?php endif; ?>
        <?php if ($_customHeader): ?><div class="mb-4"><?= $_customHeader ?></div><?php endif; ?>
        <?php if ($_showTitle && $_hasTitleBg): ?>
            <?php include __DIR__ . '/_page-title-bg.php'; ?>
        <?php elseif ($_showTitle): ?>
        <h1 class="text-2xl font-bold <?= $_titleTextClass ?> mb-4 inline-flex items-center gap-2"><?= htmlspecialchars($pageTitle) ?> <?= $_adminIcons ?></h1>
        <?php elseif ($_adminIcons): ?>
        <div class="flex justify-end mb-2 gap-2"><?= $_adminIcons ?></div>
        <?php endif; ?>
        <?php if ($pageContent): ?>
        <div class="board-content <?= $_bgClass ?: 'bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6' ?>">
            <?= $pageContent ?>
        </div>
        <?php else: ?>
        <div class="p-16 text-center text-zinc-400 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700">
            <?= __('common.page_empty') ?? '페이지 내용이 아직 작성되지 않았습니다.' ?>
        </div>
        <?php endif; ?>
        <?php if ($_customFooter): ?><div class="mt-4"><?= $_customFooter ?></div><?php endif; ?>
    </div>
