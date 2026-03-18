<?php
/**
 * 게시판 설정 - 기본 정보 섹션 (공통 컴포넌트)
 *
 * 사용 변수:
 *   $board    (array|null) - 게시판 데이터 (수정 모드), null이면 생성 모드
 *   $boardId  (int)        - 게시판 ID (수정 모드에서만 사용)
 *   $_collapsed (bool)     - 기본 접힘 여부 (기본: false)
 */
$_b = $board ?? [];
$_isEdit = !empty($_b);
$_collapsed = $_collapsed ?? false;
?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700" data-section="basic">
    <button type="button" onclick="toggleSection(this)" class="w-full flex items-center justify-between p-6 cursor-pointer select-none">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200"><?= __('site.boards.section_basic') ?></h3>
        <svg class="section-chevron w-5 h-5 text-zinc-400 transition-transform duration-200 <?= $_collapsed ? 'rotate-180' : '' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
        </svg>
    </button>
    <div class="section-body px-6 pb-6 <?= $_collapsed ? 'hidden' : '' ?>">
        <!-- URL (slug) -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_url') ?> <span class="text-red-500">*</span></label>
            <div class="flex items-center gap-2">
                <span class="text-sm text-zinc-500 dark:text-zinc-400">/board/</span>
                <input type="text" name="slug" id="slug" required
                       pattern="[a-z0-9_-]+"
                       value="<?= htmlspecialchars($_b['slug'] ?? '') ?>"
                       placeholder="<?= $_isEdit ? '' : 'notice' ?>"
                       class="flex-1 px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200 placeholder-zinc-400">
            </div>
            <p class="mt-1 text-xs text-zinc-500"><?= __('site.boards.field_url_help') ?></p>
        </div>

        <!-- 브라우저 제목 -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_title') ?> <span class="text-red-500">*</span></label>
            <?php if ($_isEdit && function_exists('rzx_multilang_input')): ?>
                <?php rzx_multilang_input('title', $_b['title'] ?? '', "board.{$boardId}.title", ['required' => true]); ?>
            <?php else: ?>
                <input type="text" name="title" id="title" required
                       value="<?= htmlspecialchars($_b['title'] ?? '') ?>"
                       placeholder="<?= __('site.boards.field_title_placeholder') ?>"
                       class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200 placeholder-zinc-400">
            <?php endif; ?>
        </div>

        <!-- 모듈 분류 -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_category') ?></label>
            <select name="category" class="px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200">
                <?php foreach (['board', 'notice', 'qna', 'faq', 'gallery'] as $c): ?>
                <option value="<?= $c ?>" <?= ($_b['category'] ?? 'board') === $c ? 'selected' : '' ?>><?= __('site.boards.cat_' . $c) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- 설명 -->
        <div class="mb-4">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.field_description') ?></label>
            <?php if ($_isEdit && function_exists('rzx_multilang_input')): ?>
                <?php rzx_multilang_input('description', $_b['description'] ?? '', "board.{$boardId}.description", [
                    'type' => 'textarea', 'rows' => 2, 'modal_type' => 'editor',
                    'placeholder' => __('site.boards.field_description_placeholder')
                ]); ?>
            <?php else: ?>
                <textarea name="description" rows="2"
                          class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200 placeholder-zinc-400"
                          placeholder="<?= __('site.boards.field_description_placeholder') ?>"><?= htmlspecialchars($_b['description'] ?? '') ?></textarea>
            <?php endif; ?>
        </div>

        <!-- 활성 상태 -->
        <div>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="is_active" value="1" <?= ($_b['is_active'] ?? 1) ? 'checked' : '' ?>
                       class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
                <span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('site.boards.field_is_active') ?></span>
            </label>
        </div>
    </div>
</div>
