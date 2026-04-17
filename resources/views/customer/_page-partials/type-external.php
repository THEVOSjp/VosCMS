    <?php
    $isExternalUrl = preg_match('#^https?://#', $pageContent);
    $isPhpFile = str_ends_with(trim($pageContent), '.php');
    $isHtmlFile = str_ends_with(trim($pageContent), '.html') || str_ends_with(trim($pageContent), '.htm');
    ?>

    <div class="<?= $_contentWidth ?> mx-auto px-4 sm:px-6 py-4">
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
            <div class="flex items-center justify-between mb-3">
                <h1 class="text-xl font-bold <?= $_titleTextClass ?> inline-flex items-center gap-2"><?= htmlspecialchars($pageTitle) ?> <?= $_adminIcons ?></h1>
                <?php if ($isExternalUrl): ?>
                <a href="<?= htmlspecialchars($pageContent) ?>" target="_blank" class="text-sm text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1">
                    <?= __('common.open_new_window') ?? '새 창에서 열기' ?>
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                </a>
                <?php endif; ?>
            </div>
        <?php endif; ?>

        <?php if ($isExternalUrl): ?>
        <div class="rounded-xl overflow-hidden border border-zinc-200 dark:border-zinc-700" style="height: calc(100vh - 200px);">
            <iframe src="<?= htmlspecialchars($pageContent) ?>"
                    class="w-full h-full border-0"
                    sandbox="allow-scripts allow-same-origin allow-forms allow-popups"
                    loading="lazy"
                    onerror="this.parentElement.innerHTML='<div class=\'p-8 text-center text-zinc-500\'><p>이 사이트는 외부 표시를 허용하지 않습니다.</p><a href=\'<?= htmlspecialchars($pageContent) ?>\' target=\'_blank\' class=\'mt-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-lg\'>새 창에서 열기</a></div>'">
            </iframe>
        </div>
        <?php elseif ($isPhpFile || $isHtmlFile): ?>
        <?php
        $filePath = BASE_PATH . '/' . ltrim($pageContent, '/');
        if (file_exists($filePath)) {
            include $filePath;
        } else {
            echo '<div class="p-8 text-center text-zinc-500">페이지 파일을 찾을 수 없습니다: ' . htmlspecialchars($pageContent) . '</div>';
        }
        ?>
        <?php else: ?>
        <div class="board-content bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <?= $pageContent ?>
        </div>
        <?php endif; ?>
        <?php if ($_customFooter): ?><div class="mt-4"><?= $_customFooter ?></div><?php endif; ?>
    </div>

