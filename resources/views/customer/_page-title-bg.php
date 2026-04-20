<?php
/**
 * 페이지 제목 배경 공통 partial
 *
 * 필요 변수: $_titleBgType, $_titleBgImage, $_titleBgVideo, $_titleBgHeight,
 *           $_titleBgOverlay, $_titleTextClass, $_showBreadcrumb, $_hasTitleBg,
 *           $pageTitle, $_adminIcons, $config
 * 선택 변수: $_contentWidth (비어있으면 max-w-7xl)
 */
$_baseUrl = $config['app_url'] ?? '';
$_bgSrc = function($path) use ($_baseUrl) {
    return str_starts_with($path, 'http') ? htmlspecialchars($path) : htmlspecialchars($_baseUrl . $path);
};
$_titleContainerWidth = !empty($_contentWidth) ? $_contentWidth : 'max-w-7xl';
?>
<div class="<?= htmlspecialchars($_titleContainerWidth) ?> mx-auto px-4">
    <div class="relative rounded-xl overflow-hidden mb-6" style="height:<?= $_titleBgHeight ?>px">
        <?php if ($_titleBgType === 'video' && $_titleBgVideo): ?>
        <video class="absolute inset-0 w-full h-full object-cover" autoplay muted loop playsinline>
            <source src="<?= $_bgSrc($_titleBgVideo) ?>" type="video/mp4">
        </video>
        <?php elseif ($_titleBgType === 'image' && $_titleBgImage): ?>
        <div class="absolute inset-0 bg-cover bg-center" style="background-image:url('<?= $_bgSrc($_titleBgImage) ?>')"></div>
        <?php endif; ?>
        <?php if ($_titleBgOverlay > 0): ?>
        <div class="absolute inset-0 bg-black" style="opacity:<?= $_titleBgOverlay / 100 ?>"></div>
        <?php endif; ?>
        <div class="relative h-full flex flex-col justify-center items-center text-center px-6">
            <h1 class="text-3xl font-bold <?= $_titleTextClass ?> inline-flex items-center gap-2"><?= htmlspecialchars($pageTitle) ?> <?= $_adminIcons ?></h1>
            <?php if ($_showBreadcrumb): ?>
            <nav class="text-sm <?= $_titleTextClass ?> opacity-70 mt-2">
                <a href="<?= $_baseUrl ?>/" class="hover:opacity-100"><?= __('common.home') ?? '홈' ?></a>
                <span class="mx-1">/</span>
                <span><?= htmlspecialchars($pageTitle) ?></span>
            </nav>
            <?php endif; ?>
        </div>
    </div>
</div>
