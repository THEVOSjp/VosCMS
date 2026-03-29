<?php
/**
 * 게시판 설정 - 목록 설정 섹션 (공통 컴포넌트)
 */
$_b = $board ?? [];
$_collapsed = $_collapsed ?? true;
$tgl = 'w-9 h-5 bg-zinc-300 dark:bg-zinc-600 peer-checked:bg-blue-600 rounded-full peer peer-focus:ring-2 peer-focus:ring-blue-300 after:content-[\'\'] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-full';

$currentColumns = json_decode($_b['list_columns'] ?? '[]', true) ?: ['no', 'title', 'nick_name', 'created_at', 'view_count'];
$availableColumns = [
    'no' => __('site.boards.lcol_no'), 'title' => __('site.boards.lcol_title'),
    'nick_name' => __('site.boards.lcol_nick_name'), 'user_id' => __('site.boards.lcol_user_id'),
    'created_at' => __('site.boards.lcol_created_at'), 'updated_at' => __('site.boards.lcol_updated_at'),
    'view_count' => __('site.boards.lcol_view_count'), 'vote_count' => __('site.boards.lcol_vote_count'),
    'comment_count' => __('site.boards.lcol_comment_count'), 'category' => __('site.boards.lcol_category'),
];
?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700" data-section="list">
    <button type="button" onclick="toggleSection(this)" class="w-full flex items-center justify-between p-6 cursor-pointer select-none">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200"><?= __('site.boards.section_list') ?></h3>
        <svg class="section-chevron w-5 h-5 text-zinc-400 transition-transform duration-200 <?= $_collapsed ? 'rotate-180' : '' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/>
        </svg>
    </button>
    <div class="section-body px-6 pb-6 <?= $_collapsed ? 'hidden' : '' ?>">
        <!-- 표시 항목 및 순서 -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.list_columns_title') ?></label>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3"><?= __('site.boards.list_columns_desc') ?></p>
            <div class="grid grid-cols-2 gap-4">
                <div>
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1"><?= __('site.boards.list_available') ?></label>
                    <select id="availColSelect" multiple size="8"
                            class="w-full text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200">
                        <?php foreach ($availableColumns as $col => $label): ?>
                        <?php if (!in_array($col, $currentColumns)): ?>
                        <option value="<?= $col ?>"><?= $label ?></option>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1"><?= __('site.boards.list_selected') ?></label>
                    <select id="selectedColSelect" multiple size="8"
                            class="w-full text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200">
                        <?php foreach ($currentColumns as $col): ?>
                        <?php if (isset($availableColumns[$col])): ?>
                        <option value="<?= $col ?>"><?= $availableColumns[$col] ?></option>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="grid grid-cols-2 gap-4 mt-2">
                <div>
                    <button type="button" onclick="colAdd()" class="px-3 py-1 text-xs bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 border border-zinc-300 dark:border-zinc-600 rounded hover:bg-zinc-200 dark:hover:bg-zinc-600 transition"><?= __('site.boards.btn_add') ?></button>
                </div>
                <div class="flex items-center gap-2">
                    <button type="button" onclick="colMoveUp()" class="px-3 py-1 text-xs bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 border border-zinc-300 dark:border-zinc-600 rounded hover:bg-zinc-200 dark:hover:bg-zinc-600 transition">&#9650; <?= __('site.boards.btn_move_up') ?></button>
                    <button type="button" onclick="colMoveDown()" class="px-3 py-1 text-xs bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 border border-zinc-300 dark:border-zinc-600 rounded hover:bg-zinc-200 dark:hover:bg-zinc-600 transition">&#9660; <?= __('site.boards.btn_move_down') ?></button>
                    <button type="button" onclick="colRemove()" class="px-3 py-1 text-xs bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 border border-red-200 dark:border-red-800 rounded hover:bg-red-100 dark:hover:bg-red-900/40 transition"><?= __('site.boards.btn_remove') ?></button>
                </div>
            </div>
            <input type="hidden" name="list_columns" id="listColumnsInput" value='<?= htmlspecialchars(json_encode($currentColumns)) ?>'>
        </div>

        <!-- 정렬방법 -->
        <div class="mb-6">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('site.boards.sort_title') ?></label>
            <div class="flex gap-4">
                <select name="sort_field" class="px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200">
                    <?php
                    $sortFields = [
                        'created_at' => __('site.boards.lcol_created_at'),
                        'updated_at' => __('site.boards.lcol_updated_at'),
                        'view_count' => __('site.boards.lcol_view_count'),
                        'vote_count' => __('site.boards.lcol_vote_count'),
                    ];
                    foreach ($sortFields as $val => $label):
                    ?>
                    <option value="<?= $val ?>" <?= ($_b['sort_field'] ?? 'created_at') === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
                <select name="sort_direction" class="px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200">
                    <option value="desc" <?= ($_b['sort_direction'] ?? 'desc') === 'desc' ? 'selected' : '' ?>><?= __('site.boards.sort_desc') ?></option>
                    <option value="asc" <?= ($_b['sort_direction'] ?? '') === 'asc' ? 'selected' : '' ?>><?= __('site.boards.sort_asc') ?></option>
                </select>
            </div>
        </div>

        <!-- 공지사항 제외 -->
        <div class="mb-6">
            <label class="flex items-center justify-between mb-1">
                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('site.boards.except_notice') ?></span>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="except_notice" value="0">
                    <input type="checkbox" name="except_notice" value="1" <?= ($_b['except_notice'] ?? 1) ? 'checked' : '' ?> class="sr-only peer">
                    <div class="<?= $tgl ?>"></div>
                </label>
            </label>
            <p class="text-xs text-zinc-500"><?= __('site.boards.except_notice_help') ?></p>
            <p class="text-xs text-amber-600 dark:text-amber-400"><?= __('site.boards.except_notice_warn') ?></p>
        </div>

        <!-- 하단목록 표시 -->
        <div class="mb-6">
            <label class="flex items-center justify-between mb-1">
                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('site.boards.show_bottom_list') ?></span>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="show_bottom_list" value="0">
                    <input type="checkbox" name="show_bottom_list" value="1" <?= ($_b['show_bottom_list'] ?? 1) ? 'checked' : '' ?> class="sr-only peer">
                    <div class="<?= $tgl ?>"></div>
                </label>
            </label>
            <p class="text-xs text-zinc-500"><?= __('site.boards.show_bottom_list_help') ?></p>
        </div>

        <!-- 하단목록 설정 -->
        <div class="p-4 bg-zinc-50 dark:bg-zinc-700/30 rounded-lg border border-zinc-200 dark:border-zinc-600">
            <div class="mb-3">
                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                    <input type="checkbox" name="bottom_skip_old_enabled" value="1" <?= ($_b['bottom_skip_old_days'] ?? 0) > 0 ? 'checked' : '' ?>
                           class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
                    <?= __('site.boards.bottom_skip_old') ?>
                </label>
                <div class="flex items-center gap-2 mt-1 ml-6">
                    <input type="number" name="bottom_skip_old_days" value="<?= (int)($_b['bottom_skip_old_days'] ?? 30) ?>" min="1" max="365"
                           class="w-20 px-3 py-1.5 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200">
                    <span class="text-sm text-zinc-600 dark:text-zinc-400"><?= __('site.boards.bottom_skip_old_days') ?></span>
                </div>
            </div>
            <div class="mb-3">
                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                    <input type="checkbox" name="bottom_skip_robot" value="1" <?= ($_b['bottom_skip_robot'] ?? 1) ? 'checked' : '' ?>
                           class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
                    <?= __('site.boards.bottom_skip_robot') ?>
                </label>
            </div>
            <p class="text-xs text-zinc-500"><?= __('site.boards.bottom_skip_help') ?></p>
        </div>
    </div>
</div>
