<?php
/**
 * 게시판 웹진형 목록 (기본 스킨)
 * 스킨 오버라이드: skins/{name}/board/_list-webzine.php
 * 사용 변수: $notices, $posts, $postTitleTranslations, $boardUrl, $board, $catMap
 */
?>
<div id="viewWebzine" class="view-mode hidden space-y-3">
    <?php include boardSkinFile('_list-notices.php'); ?>

    <?php if (empty($posts)): ?>
    <div class="py-16 text-center text-zinc-400 dark:text-zinc-500"><?= __('board.no_posts') ?></div>
    <?php endif; ?>

    <?php foreach ($posts as $_wp): ?>
    <?php
        $_wp['_is_notice'] = false;
        $_wpTitle = $postTitleTranslations[$_wp['id']] ?? $_wp['title'];
        // 본문에서 첫 이미지 추출
        $_wpImg = '';
        if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/', $_wp['content'] ?? '', $_im)) $_wpImg = $_im[1];
        // 본문 미리보기 (HTML 태그 제거, 150자)
        $_wpText = strip_tags($_wp['content'] ?? '');
        $_wpText = preg_replace('/\s+/', ' ', $_wpText);
        $_wpExcerpt = mb_substr(trim($_wpText), 0, 150);
        if (mb_strlen($_wpText) > 150) $_wpExcerpt .= '...';
        // 카테고리
        $_wpCat = $catMap[$_wp['category_id'] ?? 0] ?? null;
        // 새 글 (24시간 이내)
        $_wpNew = (time() - strtotime($_wp['created_at'] ?? 'now')) < 86400;
        // 비밀글
        $_wpSecret = !empty($_wp['is_secret']);
    ?>
    <a href="<?= $boardUrl ?>/<?= $_wp['id'] ?>" class="group block bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden hover:shadow-lg hover:border-zinc-300 dark:hover:border-zinc-600 transition-all">
        <div class="flex flex-col sm:flex-row">
            <!-- 썸네일 -->
            <?php if ($_wpImg): ?>
            <div class="sm:w-48 sm:h-36 h-44 flex-shrink-0 bg-zinc-100 dark:bg-zinc-700 overflow-hidden relative">
                <img src="<?= htmlspecialchars($_wpImg) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy" alt="">
                <?php if ($_wp['_is_notice']): ?>
                <span class="absolute top-2 left-2 px-2 py-0.5 bg-red-500 text-white text-[10px] font-bold rounded"><?= __('board.notice') ?></span>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- 내용 -->
            <div class="flex-1 p-4 sm:p-5 min-w-0 flex flex-col justify-between">
                <div>
                    <!-- 상단: 카테고리 + 날짜 -->
                    <div class="flex items-center gap-2 mb-2">
                        <?php if (!$_wpImg && $_wp['_is_notice']): ?>
                        <span class="px-1.5 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-[10px] font-bold rounded"><?= __('board.notice') ?></span>
                        <?php endif; ?>
                        <?php $_showCat = !empty($skinConfig['show_category']) && $skinConfig['show_category'] !== '0'; ?>
                        <?php if ($_showCat && $_wpCat): ?>
                        <span class="inline-flex items-center gap-1 text-xs text-zinc-500 dark:text-zinc-400">
                            <?php if (!empty($_wpCat['color'])): ?><span class="w-1.5 h-1.5 rounded-full" style="background:<?= htmlspecialchars($_wpCat['color']) ?>"></span><?php endif; ?>
                            <?= htmlspecialchars($_wpCat['name']) ?>
                        </span>
                        <?php endif; ?>
                        <span class="text-xs text-zinc-400 dark:text-zinc-500"><?= date('Y.m.d', strtotime($_wp['created_at'])) ?></span>
                    </div>

                    <!-- 제목 -->
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors mb-1.5 line-clamp-1">
                        <?php if ($_wpSecret): ?>
                        <svg class="w-3.5 h-3.5 inline mr-0.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                        <?php endif; ?>
                        <?= htmlspecialchars($_wpTitle) ?>
                        <?php if ($_wpNew): ?>
                        <span class="inline-block w-1.5 h-1.5 bg-red-500 rounded-full ml-1 align-middle"></span>
                        <?php endif; ?>
                        <?php if (($_wp['comment_count'] ?? 0) > 0): ?>
                        <span class="text-xs text-blue-500 font-normal ml-1">[<?= $_wp['comment_count'] ?>]</span>
                        <?php endif; ?>
                    </h3>

                    <!-- 미리보기 -->
                    <?php if ($_wpExcerpt && !$_wpSecret): ?>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 line-clamp-2 leading-relaxed"><?= htmlspecialchars($_wpExcerpt) ?></p>
                    <?php endif; ?>
                </div>

                <!-- 하단: 작성자 + 통계 -->
                <div class="flex items-center gap-4 mt-3 text-xs text-zinc-400 dark:text-zinc-500">
                    <span class="font-medium text-zinc-600 dark:text-zinc-400"><?= htmlspecialchars($_wp['nick_name'] ?? '') ?></span>
                    <span class="flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <?= number_format($_wp['view_count'] ?? 0) ?>
                    </span>
                    <?php if (($_wp['like_count'] ?? 0) > 0): ?>
                    <span class="flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/></svg>
                        <?= $_wp['like_count'] ?>
                    </span>
                    <?php endif; ?>
                    <?php if (($_wp['file_count'] ?? 0) > 0): ?>
                    <span class="flex items-center gap-1">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                        <?= $_wp['file_count'] ?>
                    </span>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </a>
    <?php endforeach; ?>
</div>
