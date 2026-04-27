<?php
/**
 * 페이지 타이틀 블록 (서브페이지 상단)
 * 필요 변수: $pageSubTitle (있으면 표시), $pageHeaderTitle (선택, 부모 메뉴 표시용)
 *
 * 사용법:
 *   include __DIR__ . '/_head.php';
 *   $pageSubTitle = __('autoinstall.orders');
 *   include __DIR__ . '/_components/page-title.php';
 */
$_parent   = $pageHeaderTitle ?? __('autoinstall.title');
$_subTitle = $pageSubTitle ?? '';
$_action   = $pageTitleAction ?? '';  // 타이틀 우측 액션 HTML (선택)
?>
<div class="mb-6 pb-4 border-b border-zinc-200 dark:border-zinc-700 flex items-end justify-between gap-3 flex-wrap">
    <div class="min-w-0">
        <?php if ($_subTitle !== ''): ?>
        <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-1"><?= htmlspecialchars($_parent) ?></p>
        <h2 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($_subTitle) ?></h2>
        <?php else: ?>
        <h2 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($_parent) ?></h2>
        <?php endif; ?>
    </div>
    <?php if ($_action !== ''): ?>
    <div class="flex-shrink-0"><?= $_action ?></div>
    <?php endif; ?>
</div>
