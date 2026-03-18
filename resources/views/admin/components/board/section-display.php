<?php
/**
 * 게시판 설정 - 표시 설정 섹션 (공통 컴포넌트)
 */
$_b = $board ?? [];
$_isEdit = !empty($_b);
$_collapsed = $_collapsed ?? false;
?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700" data-section="display">
    <button type="button" onclick="toggleSection(this)" class="w-full flex items-center justify-between p-6 cursor-pointer select-none">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200"><?= __('site.boards.section_display') ?></h3>
        <svg class="section-chevron w-5 h-5 text-zinc-400 transition-transform duration-200 <?= $_collapsed ? 'rotate-180' : '' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
        </svg>
    </button>
    <div class="section-body px-6 pb-6 <?= $_collapsed ? 'hidden' : '' ?>">
        <div class="grid grid-cols-3 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_per_page') ?></label>
                <input type="number" name="per_page" value="<?= (int)($_b['per_page'] ?? 20) ?>" min="1" max="100"
                       class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200">
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_search_per_page') ?></label>
                <input type="number" name="search_per_page" value="<?= (int)($_b['search_per_page'] ?? 20) ?>" min="1" max="100"
                       class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200">
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_page_count') ?></label>
                <input type="number" name="page_count" value="<?= (int)($_b['page_count'] ?? 10) ?>" min="1" max="20"
                       class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200">
            </div>
        </div>

        <div class="mb-4">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_header') ?></label>
            <?php if ($_isEdit && function_exists('rzx_multilang_input')): ?>
                <?php rzx_multilang_input('header_content', $_b['header_content'] ?? '', "board.{$boardId}.header_content", [
                    'type' => 'textarea', 'rows' => 3, 'modal_type' => 'editor',
                    'placeholder' => 'HTML', 'class' => 'font-mono'
                ]); ?>
            <?php else: ?>
                <textarea name="header_content" rows="3"
                          class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200 font-mono"
                          placeholder="HTML"><?= htmlspecialchars($_b['header_content'] ?? '') ?></textarea>
            <?php endif; ?>
            <p class="mt-1 text-xs text-zinc-500"><?= __('site.boards.field_header_help') ?></p>
        </div>

        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_footer') ?></label>
            <?php if ($_isEdit && function_exists('rzx_multilang_input')): ?>
                <?php rzx_multilang_input('footer_content', $_b['footer_content'] ?? '', "board.{$boardId}.footer_content", [
                    'type' => 'textarea', 'rows' => 3, 'modal_type' => 'editor',
                    'placeholder' => 'HTML', 'class' => 'font-mono'
                ]); ?>
            <?php else: ?>
                <textarea name="footer_content" rows="3"
                          class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200 font-mono"
                          placeholder="HTML"><?= htmlspecialchars($_b['footer_content'] ?? '') ?></textarea>
            <?php endif; ?>
        </div>
    </div>
</div>
