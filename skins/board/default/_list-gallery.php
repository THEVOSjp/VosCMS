<?php
/**
 * 게시판 갤러리형 목록 (기본 스킨)
 * 스킨 오버라이드: skins/{name}/board/_list-gallery.php
 */
?>
<div id="viewGallery" class="view-mode hidden">
    <?php include boardSkinFile('_list-notices.php'); ?>

    <?php $_gCols = $_skinPostsPerRow ?? 4; ?>
    <div class="grid grid-cols-2 md:grid-cols-<?= min($_gCols, 3) ?> lg:grid-cols-<?= $_gCols ?> gap-3">
    <?php if (empty($posts)): ?>
    <div class="col-span-full py-16 text-center text-zinc-400 dark:text-zinc-500"><?= __('board.no_posts') ?></div>
    <?php endif; ?>

    <?php foreach ($posts as $_gp): ?>
    <?php $_gp['_is_notice'] = false; ?>
    <?php
        $_gpTitle = $postTitleTranslations[$_gp['id']] ?? $_gp['title'];
        $_gpContent = $postContentTranslations[$_gp['id']] ?? $_gp['content'] ?? '';
        // 대표 이미지 우선 (첨부 is_primary), 없으면 본문 첫 이미지
        $_gpImg = $_gp['_primary_image'] ?? '';
        if (!$_gpImg && preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $_gpContent, $_gm)) $_gpImg = $_gm[1];
        // 처리 단계 배지 (extra_vars.status)
        $_gpStatusBadge = '';
        if (!empty($_gp['extra_vars'])) {
            $_evRow = is_string($_gp['extra_vars']) ? json_decode($_gp['extra_vars'], true) : $_gp['extra_vars'];
            if (is_array($_evRow) && !empty($_evRow['status'])) {
                require_once BASE_PATH . '/rzxlib/Core/Modules/ExtraVarRenderer.php';
                $_gpStatusLabel = \RzxLib\Core\Modules\ExtraVarRenderer::getOptionLabel($boardId, 'status', (string)$_evRow['status']);
                $_gpStatusBadge = \RzxLib\Core\Modules\ExtraVarRenderer::renderStatusBadge((string)$_evRow['status'], 'px-2 py-0.5 text-xs', $_gpStatusLabel);
            }
        }
        $_gpCat = $catMap[$_gp['category_id'] ?? 0] ?? null;
        $_gpNew = (time() - strtotime($_gp['created_at'] ?? 'now')) < 86400;
    ?>
    <a href="<?= $boardUrl ?>/<?= $_gp['id'] ?>" class="group block relative aspect-square rounded-xl overflow-hidden bg-zinc-200 dark:bg-zinc-700">
        <!-- 풀 이미지 배경 -->
        <?php if ($_gpImg): ?>
        <img src="<?= htmlspecialchars($_gpImg) ?>" class="absolute inset-0 w-full h-full object-cover group-hover:scale-110 transition-transform duration-500" loading="lazy" alt="">
        <?php else: ?>
        <div class="absolute inset-0 flex items-center justify-center bg-gradient-to-br from-zinc-200 to-zinc-300 dark:from-zinc-700 dark:to-zinc-800">
            <svg class="w-12 h-12 text-zinc-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        </div>
        <?php endif; ?>

        <!-- 상단 배지 (항상 표시) -->
        <div class="absolute top-2 left-2 flex items-center gap-1 z-10">
            <?php if ($_gp['_is_notice']): ?>
            <span class="px-1.5 py-0.5 bg-red-500 text-white text-[10px] font-bold rounded shadow"><?= __('board.notice') ?></span>
            <?php endif; ?>
            <?php if ($_gpNew): ?>
            <span class="w-2 h-2 bg-red-500 rounded-full shadow"></span>
            <?php endif; ?>
        </div>

        <?php if (($_gp['comment_count'] ?? 0) > 0): ?>
        <div class="absolute top-2 right-2 z-10">
            <span class="px-1.5 py-0.5 bg-black/50 text-white text-[10px] font-medium rounded backdrop-blur-sm">
                <svg class="w-2.5 h-2.5 inline mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                <?= $_gp['comment_count'] ?>
            </span>
        </div>
        <?php endif; ?>

        <?php if ($_gpStatusBadge): ?>
        <div class="absolute bottom-2 left-2 z-10"><?= $_gpStatusBadge ?></div>
        <?php endif; ?>

        <!-- 호버 시 오버레이 + 정보 (아래에서 위로 슬라이드) -->
        <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/30 to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300 z-10"></div>
        <div class="absolute inset-x-0 bottom-0 p-3 translate-y-full group-hover:translate-y-0 transition-transform duration-300 ease-out z-20">
            <?php $_showCat = !empty($skinConfig['show_category']) && $skinConfig['show_category'] !== '0'; ?>
            <?php if ($_showCat && $_gpCat): ?>
            <div class="flex items-center gap-1 mb-1">
                <?php if (!empty($_gpCat['color'])): ?><span class="w-1.5 h-1.5 rounded-full" style="background:<?= htmlspecialchars($_gpCat['color']) ?>"></span><?php endif; ?>
                <span class="text-[10px] text-white/70"><?= htmlspecialchars($_gpCat['name']) ?></span>
            </div>
            <?php endif; ?>
            <h3 class="text-sm font-semibold text-white leading-snug line-clamp-2 mb-1.5"><?= htmlspecialchars($_gpTitle) ?></h3>
            <div class="flex items-center gap-3 text-[10px] text-white/60">
                <span><?= htmlspecialchars($_gp['nick_name'] ?? '') ?></span>
                <span><?= date('Y.m.d', strtotime($_gp['created_at'])) ?></span>
                <span class="flex items-center gap-0.5">
                    <svg class="w-2.5 h-2.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                    <?= number_format($_gp['view_count'] ?? 0) ?>
                </span>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
    </div>
</div>
