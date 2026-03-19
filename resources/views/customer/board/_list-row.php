<?php
/**
 * 게시판 목록 행 (list.php에서 include)
 * $post, $listColumns, $boardUrl, $catMap, $board 사용
 */
$isNotice = !empty($post['_is_notice_row']);
$rowClass = $isNotice ? 'bg-blue-50/50 dark:bg-blue-900/10' : 'hover:bg-zinc-50 dark:hover:bg-zinc-700/30';
$postUrl = $boardUrl . '/' . $post['id'];
?>
<tr class="border-b border-zinc-100 dark:border-zinc-700/50 <?= $rowClass ?> transition">
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
        <a href="<?= $postUrl ?>" class="text-zinc-800 dark:text-zinc-200 hover:text-blue-600 dark:hover:text-blue-400 font-medium">
            <?php if ($post['is_secret'] ?? 0): ?>
            <svg class="w-3.5 h-3.5 inline mr-1 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <?php endif; ?>
            <?= htmlspecialchars($post['title']) ?>
        </a>
        <?php if (!empty($post['original_locale']) && ($post['original_locale'] ?? 'ko') !== ($currentLocale ?? 'ko') && !isset($postTitleTranslations[$post['id']])): ?>
        <span class="ml-1 px-1 py-0.5 text-[10px] font-medium bg-zinc-200 dark:bg-zinc-600 text-zinc-500 dark:text-zinc-400 rounded uppercase"><?= $post['original_locale'] ?></span>
        <?php endif; ?>
        <?php if (($post['comment_count'] ?? 0) > 0 && !in_array('comment_count', $listColumns)): ?>
        <span class="ml-1 text-xs text-blue-500">[<?= $post['comment_count'] ?>]</span>
        <?php endif; ?>
        <?php if (($post['file_count'] ?? 0) > 0): ?>
        <svg class="w-3.5 h-3.5 inline ml-1 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
        <?php endif; ?>
    </td>

    <?php elseif ($col === 'category'): ?>
    <td class="py-3 px-4 text-center text-xs text-zinc-500 dark:text-zinc-400">
        <?php if (isset($catMap[$post['category_id'] ?? 0])): ?>
        <span class="inline-flex items-center gap-1">
            <?php if (!empty($catMap[$post['category_id']]['color'])): ?><span class="w-2 h-2 rounded-full" style="background:<?= htmlspecialchars($catMap[$post['category_id']]['color']) ?>"></span><?php endif; ?>
            <?= htmlspecialchars($catMap[$post['category_id']]['name']) ?>
        </span>
        <?php endif; ?>
    </td>

    <?php elseif ($col === 'nick_name'): ?>
    <td class="py-3 px-4 text-center text-sm text-zinc-600 dark:text-zinc-400"><?= htmlspecialchars($post['nick_name'] ?? '') ?></td>

    <?php elseif ($col === 'user_id'): ?>
    <td class="py-3 px-4 text-center text-sm text-zinc-500 dark:text-zinc-400"><?= $post['user_id'] ?? '-' ?></td>

    <?php elseif ($col === 'created_at'): ?>
    <td class="py-3 px-4 text-center text-xs text-zinc-500 dark:text-zinc-400">
        <?php
        $dt = $post['created_at'] ?? '';
        if ($dt) {
            $ts = strtotime($dt);
            echo date('Y-m-d') === date('Y-m-d', $ts) ? date('H:i', $ts) : date('Y.m.d', $ts);
        }
        ?>
    </td>

    <?php elseif ($col === 'updated_at'): ?>
    <td class="py-3 px-4 text-center text-xs text-zinc-500 dark:text-zinc-400">
        <?= $post['updated_at'] ? date('Y.m.d', strtotime($post['updated_at'])) : '-' ?>
    </td>

    <?php elseif ($col === 'view_count'): ?>
    <td class="py-3 px-4 text-center text-sm text-zinc-500 dark:text-zinc-400"><?= number_format($post['view_count'] ?? 0) ?></td>

    <?php elseif ($col === 'vote_count'): ?>
    <td class="py-3 px-4 text-center text-sm text-zinc-500 dark:text-zinc-400"><?= $post['like_count'] ?? 0 ?></td>

    <?php elseif ($col === 'comment_count'): ?>
    <td class="py-3 px-4 text-center text-sm text-zinc-500 dark:text-zinc-400"><?= $post['comment_count'] ?? 0 ?></td>

    <?php endif; ?>
    <?php endforeach; ?>
</tr>
