<?php
/**
 * 공지사항 목록형 표시 (웹진/갤러리/카드 뷰 상단에 공통 사용)
 * 스킨 오버라이드: skins/{name}/board/_list-notices.php
 */
if (empty($notices)) return;
?>
<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden mb-4">
    <?php foreach ($notices as $_np): ?>
    <?php $_npTitle = $postTitleTranslations[$_np['id']] ?? $_np['title']; ?>
    <a href="<?= $boardUrl ?>/<?= $_np['id'] ?>" class="flex items-center px-4 py-2.5 border-b last:border-b-0 border-zinc-100 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition text-sm">
        <span class="px-1.5 py-0.5 bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400 text-[10px] font-bold rounded mr-3 flex-shrink-0"><?= __('board.notice') ?></span>
        <span class="flex-1 text-zinc-800 dark:text-zinc-200 font-medium truncate"><?= htmlspecialchars($_npTitle) ?></span>
        <?php if (($_np['comment_count'] ?? 0) > 0): ?>
        <span class="text-xs text-blue-500 ml-2">[<?= $_np['comment_count'] ?>]</span>
        <?php endif; ?>
        <span class="text-xs text-zinc-400 ml-3 flex-shrink-0"><?= date('Y.m.d', strtotime($_np['created_at'])) ?></span>
    </a>
    <?php endforeach; ?>
</div>
