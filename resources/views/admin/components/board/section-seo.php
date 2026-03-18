<?php
/**
 * 게시판 설정 - SEO 섹션 (공통 컴포넌트)
 */
$_b = $board ?? [];
$_isEdit = !empty($_b);
$_collapsed = $_collapsed ?? false;
$tgl = 'w-9 h-5 bg-zinc-300 dark:bg-zinc-600 peer-checked:bg-blue-600 rounded-full peer peer-focus:ring-2 peer-focus:ring-blue-300 after:content-[\'\'] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-full';
?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700" data-section="seo">
    <button type="button" onclick="toggleSection(this)" class="w-full flex items-center justify-between p-6 cursor-pointer select-none">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200"><?= __('site.boards.section_seo') ?></h3>
        <svg class="section-chevron w-5 h-5 text-zinc-400 transition-transform duration-200 <?= $_collapsed ? 'rotate-180' : '' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
        </svg>
    </button>
    <div class="section-body px-6 pb-6 <?= $_collapsed ? 'hidden' : '' ?>">
        <div class="mb-4">
            <label class="flex items-center justify-between">
                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('site.boards.field_robots') ?></span>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="robots_tag" value="noindex">
                    <input type="checkbox" name="robots_tag" value="all" <?= ($_b['robots_tag'] ?? 'all') === 'all' ? 'checked' : '' ?> class="sr-only peer">
                    <div class="<?= $tgl ?>"></div>
                </label>
            </label>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_seo_keywords') ?></label>
            <?php if ($_isEdit && function_exists('rzx_multilang_input')): ?>
                <?php rzx_multilang_input('seo_keywords', $_b['seo_keywords'] ?? '', "board.{$boardId}.seo_keywords", [
                    'placeholder' => 'keyword1, keyword2'
                ]); ?>
            <?php else: ?>
                <input type="text" name="seo_keywords"
                       value="<?= htmlspecialchars($_b['seo_keywords'] ?? '') ?>"
                       class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200 placeholder-zinc-400"
                       placeholder="keyword1, keyword2">
            <?php endif; ?>
        </div>

        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_seo_desc') ?></label>
            <?php if ($_isEdit && function_exists('rzx_multilang_input')): ?>
                <?php rzx_multilang_input('seo_description', $_b['seo_description'] ?? '', "board.{$boardId}.seo_description"); ?>
            <?php else: ?>
                <input type="text" name="seo_description"
                       value="<?= htmlspecialchars($_b['seo_description'] ?? '') ?>"
                       class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200 placeholder-zinc-400">
            <?php endif; ?>
        </div>
    </div>
</div>
