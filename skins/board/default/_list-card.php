<?php
/**
 * 게시판 카드형 목록 (기본 스킨)
 * 스킨 오버라이드: skins/{name}/board/_list-card.php
 */
?>
<div id="viewCard" class="view-mode hidden">
    <?php include boardSkinFile('_list-notices.php'); ?>

    <?php $_cCols = $_skinPostsPerRow ?? 3; ?>
    <div class="grid grid-cols-1 md:grid-cols-<?= min($_cCols, 2) ?> lg:grid-cols-<?= $_cCols ?> gap-4">
    <?php foreach ($posts as $_cp): ?>
    <?php
        $_cpTitle = $postTitleTranslations[$_cp['id']] ?? $_cp['title'];
        $_cpContent = $postContentTranslations[$_cp['id']] ?? $_cp['content'] ?? '';
        // 대표 이미지 우선 (첨부 is_primary), 없으면 본문 첫 이미지
        $_cpImg = $_cp['_primary_image'] ?? '';
        if (!$_cpImg && preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $_cpContent, $_cm)) $_cpImg = $_cm[1];
        // 처리 단계 배지 (extra_vars.status)
        $_cpStatusBadge = '';
        if (!empty($_cp['extra_vars'])) {
            $_evRow = is_string($_cp['extra_vars']) ? json_decode($_cp['extra_vars'], true) : $_cp['extra_vars'];
            if (is_array($_evRow) && !empty($_evRow['status'])) {
                require_once BASE_PATH . '/rzxlib/Core/Modules/ExtraVarRenderer.php';
                $_cpStatusLabel = \RzxLib\Core\Modules\ExtraVarRenderer::getOptionLabel($boardId, 'status', (string)$_evRow['status']);
                $_cpStatusBadge = \RzxLib\Core\Modules\ExtraVarRenderer::renderStatusBadge((string)$_evRow['status'], 'px-2 py-0.5 text-xs', $_cpStatusLabel);
            }
        }
        $_cpExcerpt = mb_substr(strip_tags($_cpContent), 0, 80);
    ?>
    <a href="<?= $boardUrl ?>/<?= $_cp['id'] ?>" class="block bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden hover:shadow-md transition group">
        <div class="h-40 bg-zinc-100 dark:bg-zinc-700 overflow-hidden">
            <?php if ($_cpImg): ?>
            <img src="<?= htmlspecialchars($_cpImg) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform" loading="lazy">
            <?php else: ?>
            <div class="w-full h-full flex items-center justify-center text-zinc-300 dark:text-zinc-600">
                <svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
            </div>
            <?php endif; ?>
        </div>
        <div class="p-4">
            <?php
                $_showCat = !empty($skinConfig['show_category']) && $skinConfig['show_category'] !== '0';
                $_cpCat = $catMap[$_cp['category_id'] ?? 0] ?? null;
            ?>
            <?php if ($_showCat && $_cpCat): ?>
            <span class="inline-flex items-center gap-1 text-xs text-zinc-500 dark:text-zinc-400 mb-1">
                <?php if (!empty($_cpCat['color'])): ?><span class="w-1.5 h-1.5 rounded-full" style="background:<?= htmlspecialchars($_cpCat['color']) ?>"></span><?php endif; ?>
                <?= htmlspecialchars($_cpCat['name']) ?>
            </span>
            <?php endif; ?>
            <h3 class="font-semibold text-zinc-800 dark:text-zinc-100 truncate mb-1"><?php if ($_cpStatusBadge): ?><?= $_cpStatusBadge ?> <?php endif; ?><?= htmlspecialchars($_cpTitle) ?></h3>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 line-clamp-2 mb-2"><?= htmlspecialchars($_cpExcerpt) ?></p>
            <div class="flex items-center gap-3 text-xs text-zinc-400">
                <?= boardAuthorMention($_cp) ?>
                <span><?= date('Y.m.d', strtotime($_cp['created_at'])) ?></span>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
    <?php if (empty($posts)): ?>
    <div class="col-span-full py-12 text-center text-zinc-400"><?= __('board.no_posts') ?></div>
    <?php endif; ?>
    </div>
</div>
