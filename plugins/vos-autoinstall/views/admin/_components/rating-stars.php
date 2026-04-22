<?php
/**
 * 별점 표시 컴포넌트
 * $rating (float, 0-5), $count (int, optional) 필요
 */
$_rating = $rating ?? 0;
$_count = $count ?? null;
$_size = $size ?? 'w-4 h-4';
?>
<div class="flex items-center gap-1">
    <div class="flex">
        <?php for ($i = 1; $i <= 5; $i++): ?>
        <svg class="<?= $_size ?> <?= $i <= round($_rating) ? 'text-yellow-400' : 'text-zinc-200 dark:text-zinc-600' ?>" fill="currentColor" viewBox="0 0 20 20">
            <path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/>
        </svg>
        <?php endfor; ?>
    </div>
    <?php if ($_rating > 0): ?>
    <span class="text-sm text-zinc-600 dark:text-zinc-400 font-medium"><?= number_format($_rating, 1) ?></span>
    <?php endif; ?>
    <?php if ($_count !== null): ?>
    <span class="text-xs text-zinc-400">(<?= number_format($_count) ?>)</span>
    <?php endif; ?>
</div>
