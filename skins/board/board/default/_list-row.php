<?php
/**
 * 게시판 목록 행 (list.php에서 include)
 * PC: 테이블 행, 모바일: 카드형
 * $post, $listColumns, $boardUrl, $catMap, $board 사용
 */
$isNotice = !empty($post['_is_notice_row']);
$rowClass = $isNotice ? 'bg-blue-50/50 dark:bg-blue-900/10' : 'hover:bg-zinc-50 dark:hover:bg-zinc-700/30';
$postUrl = $boardUrl . '/' . $post['id'];
if (!empty($_skinUseLinkBoard) && !empty($post['extra_vars'])) {
    $_evData = is_string($post['extra_vars']) ? json_decode($post['extra_vars'], true) : $post['extra_vars'];
    if (!empty($_evData['link_url'])) {
        $postUrl = $_evData['link_url'];
    }
}
$_linkTarget = (!empty($_skinUseLinkBoard) && $postUrl !== $boardUrl . '/' . $post['id']) ? ($_skinLinkTarget ?? '_blank') : '';
$_postDate = '';
if ($post['created_at'] ?? '') {
    $ts = strtotime($post['created_at']);
    $_postDate = date('Y-m-d') === date('Y-m-d', $ts) ? date('H:i', $ts) : date('Y.m.d', $ts);
}
$_postAuthor = htmlspecialchars($post['nick_name'] ?? '');
$_postViews = number_format($post['view_count'] ?? 0);
$_postComments = $post['comment_count'] ?? 0;
$_postCategory = '';
if (isset($catMap[$post['category_id'] ?? 0])) {
    $_postCategory = htmlspecialchars($catMap[$post['category_id']]['name']);
}
?>
<!-- PC: 테이블 행 -->
<tr class="hidden md:table-row border-b border-zinc-100 dark:border-zinc-700/50 <?= $rowClass ?> transition">
    <?php foreach ($listColumns as $col): ?>

    <?php if ($col === 'no'): ?>
    <td class="py-3 px-1 text-center text-zinc-500 dark:text-zinc-400">
        <?php if ($isNotice): ?>
        <span class="inline-flex items-center px-1.5 py-0.5 text-xs font-medium bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded whitespace-nowrap"><?= __('board.notice') ?></span>
        <?php else: ?>
        <?= $post['_row_no'] ?? '' ?>
        <?php endif; ?>
    </td>

    <?php elseif ($col === 'title'): ?>
    <td class="py-3 px-4">
        <a href="<?= $postUrl ?>" <?= $_linkTarget ? 'target="' . $_linkTarget . '"' : '' ?> class="text-zinc-800 dark:text-zinc-200 hover:text-blue-600 dark:hover:text-blue-400 font-medium">
            <?php if ($post['is_secret'] ?? 0): ?>
            <svg class="w-3.5 h-3.5 inline mr-1 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <?php endif; ?>
            <?= htmlspecialchars($post['title']) ?>
        </a>
        <?php if (!empty($post['original_locale']) && ($post['original_locale'] ?? 'ko') !== ($currentLocale ?? 'ko') && !isset($postTitleTranslations[$post['id']])): ?>
        <span class="ml-1 px-1 py-0.5 text-[10px] font-medium bg-zinc-200 dark:bg-zinc-600 text-zinc-500 dark:text-zinc-400 rounded uppercase"><?= $post['original_locale'] ?></span>
        <?php endif; ?>
        <?php if ($_postComments > 0 && !in_array('comment_count', $listColumns)): ?>
        <span class="ml-1 text-xs text-blue-500">[<?= $_postComments ?>]</span>
        <?php endif; ?>
        <?php if (($post['file_count'] ?? 0) > 0): ?>
        <svg class="w-3.5 h-3.5 inline ml-1 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
        <?php endif; ?>
    </td>

    <?php elseif ($col === 'category'): ?>
    <td class="py-3 px-4 text-center text-xs text-zinc-500 dark:text-zinc-400">
        <?php if ($_postCategory): ?>
        <span class="inline-flex items-center gap-1">
            <?php if (!empty($catMap[$post['category_id']]['color'])): ?><span class="w-2 h-2 rounded-full" style="background:<?= htmlspecialchars($catMap[$post['category_id']]['color']) ?>"></span><?php endif; ?>
            <?= $_postCategory ?>
        </span>
        <?php endif; ?>
    </td>

    <?php elseif ($col === 'nick_name'): ?>
    <td class="py-3 px-4 text-center text-sm text-zinc-600 dark:text-zinc-400"><?= $_postAuthor ?></td>

    <?php elseif ($col === 'user_id'): ?>
    <td class="py-3 px-4 text-center text-sm text-zinc-500 dark:text-zinc-400"><?= $post['user_id'] ?? '-' ?></td>

    <?php elseif ($col === 'created_at'): ?>
    <td class="py-3 px-4 text-center text-xs text-zinc-500 dark:text-zinc-400"><?= $_postDate ?></td>

    <?php elseif ($col === 'updated_at'): ?>
    <td class="py-3 px-4 text-center text-xs text-zinc-500 dark:text-zinc-400">
        <?= $post['updated_at'] ? date('Y.m.d', strtotime($post['updated_at'])) : '-' ?>
    </td>

    <?php elseif ($col === 'view_count'): ?>
    <td class="py-3 px-4 text-center text-sm text-zinc-500 dark:text-zinc-400"><?= $_postViews ?></td>

    <?php elseif ($col === 'vote_count'): ?>
    <td class="py-3 px-4 text-center text-sm text-zinc-500 dark:text-zinc-400"><?= $post['like_count'] ?? 0 ?></td>

    <?php elseif ($col === 'comment_count'): ?>
    <td class="py-3 px-4 text-center text-sm text-zinc-500 dark:text-zinc-400"><?= $_postComments ?></td>

    <?php endif; ?>
    <?php endforeach; ?>
</tr>

<!-- 모바일: 카드형 -->
<tr class="md:hidden">
    <td colspan="<?= count($listColumns) ?>">
        <a href="<?= $postUrl ?>" <?= $_linkTarget ? 'target="' . $_linkTarget . '"' : '' ?>
           class="flex items-start gap-3 px-4 py-3 border-b border-zinc-100 dark:border-zinc-700/50 <?= $rowClass ?> transition">
            <!-- 좌측: 제목 영역 (가변 최대) -->
            <div class="flex-1 min-w-0">
                <?php if ($isNotice || $_postCategory): ?>
                <div class="flex items-center gap-1.5 mb-1">
                    <?php if ($isNotice): ?>
                    <span class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-bold bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 rounded"><?= __('board.notice') ?></span>
                    <?php endif; ?>
                    <?php if ($_postCategory): ?>
                    <span class="inline-flex items-center px-1.5 py-0.5 text-[10px] font-medium bg-zinc-100 dark:bg-zinc-700 text-zinc-500 dark:text-zinc-400 rounded"><?= $_postCategory ?></span>
                    <?php endif; ?>
                </div>
                <?php endif; ?>
                <div class="text-sm font-medium text-zinc-800 dark:text-zinc-200 truncate">
                    <?php if ($post['is_secret'] ?? 0): ?>
                    <svg class="w-3.5 h-3.5 inline mr-0.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    <?php endif; ?>
                    <?= htmlspecialchars($post['title']) ?>
                    <?php if ($_postComments > 0): ?>
                    <span class="text-blue-500 text-xs font-normal">[<?= $_postComments ?>]</span>
                    <?php endif; ?>
                    <?php if (($post['file_count'] ?? 0) > 0): ?>
                    <svg class="w-3 h-3 inline ml-0.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    <?php endif; ?>
                </div>
            </div>
            <!-- 우측: 작성자 + 날짜 (고정 폭) -->
            <div class="flex-shrink-0 text-right">
                <?php if ($_postAuthor): ?>
                <div class="text-xs text-zinc-600 dark:text-zinc-400"><?= $_postAuthor ?></div>
                <?php endif; ?>
                <div class="flex items-center justify-end gap-2 text-[11px] text-zinc-400 dark:text-zinc-500 mt-0.5">
                    <?php if ($_postDate): ?>
                    <span><?= $_postDate ?></span>
                    <?php endif; ?>
                    <span class="flex items-center gap-0.5">
                        <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                        <?= $_postViews ?>
                    </span>
                </div>
            </div>
        </a>
    </td>
</tr>
