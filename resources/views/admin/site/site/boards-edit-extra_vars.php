<?php
/**
 * RezlyX Admin - 게시판 설정: 확장 변수 탭
 * 게시판별 커스텀 필드를 정의/관리
 */
$evStmt = $pdo->prepare("SELECT * FROM {$prefix}board_extra_vars WHERE board_id = ? ORDER BY sort_order ASC, id ASC");
$evStmt->execute([$boardId]);
$extraVars = $evStmt->fetchAll(PDO::FETCH_ASSOC);

$varTypes = [
    'text' => __('site.boards.ev_type_text'),
    'text_multilang' => __('site.boards.ev_type_text_multilang'),
    'textarea' => __('site.boards.ev_type_textarea'),
    'textarea_multilang' => __('site.boards.ev_type_textarea_multilang'),
    'textarea_editor' => __('site.boards.ev_type_textarea_editor'),
    'number' => __('site.boards.ev_type_number'),
    'select' => __('site.boards.ev_type_select'),
    'checkbox' => __('site.boards.ev_type_checkbox'),
    'radio' => __('site.boards.ev_type_radio'),
    'date' => __('site.boards.ev_type_date'),
    'email' => __('site.boards.ev_type_email'),
    'url' => __('site.boards.ev_type_url'),
    'tel' => __('site.boards.ev_type_tel'),
    'color' => __('site.boards.ev_type_color'),
    'file' => __('site.boards.ev_type_file'),
];
?>
<div class="space-y-6">
    <!-- 확장 변수 목록 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200"><?= __('site.boards.ev_title') ?></h3>
            <button type="button" onclick="openEvModal(0)"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <?= __('site.boards.ev_add') ?>
            </button>
        </div>

        <?php if (empty($extraVars)): ?>
        <div class="text-center py-8 text-zinc-400 dark:text-zinc-500">
            <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            <p><?= __('site.boards.ev_empty') ?></p>
        </div>
        <?php else: ?>
        <div id="evSortList" class="space-y-2">
            <?php foreach ($extraVars as $ev): ?>
            <div class="ev-item flex items-center gap-3 p-3 bg-zinc-50 dark:bg-zinc-700/30 rounded-lg border border-zinc-200 dark:border-zinc-600 group" data-id="<?= $ev['id'] ?>">
                <span class="ev-drag-handle cursor-grab active:cursor-grabbing text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
                </span>
                <div class="flex-1 min-w-0">
                    <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($ev['title']) ?></span>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400 ml-2"><?= htmlspecialchars($ev['var_name']) ?></span>
                </div>
                <span class="px-2 py-0.5 text-xs rounded-full bg-zinc-200 dark:bg-zinc-600 text-zinc-600 dark:text-zinc-300"><?= $varTypes[$ev['var_type']] ?? $ev['var_type'] ?></span>
                <?php if ($ev['is_required']): ?>
                <span class="px-2 py-0.5 text-xs rounded-full bg-red-100 dark:bg-red-900/30 text-red-600 dark:text-red-400"><?= __('site.boards.ev_is_required') ?></span>
                <?php endif; ?>
                <?php if ($ev['is_searchable']): ?>
                <span class="px-2 py-0.5 text-xs rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 dark:text-green-400"><?= __('site.boards.ev_is_searchable') ?></span>
                <?php endif; ?>
                <button type="button" onclick="openEvModal(<?= $ev['id'] ?>)" class="p-1 text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 opacity-0 group-hover:opacity-100 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </button>
                <button type="button" onclick="deleteEv(<?= $ev['id'] ?>)" class="p-1 text-zinc-400 hover:text-red-600 dark:hover:text-red-400 opacity-0 group-hover:opacity-100 transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                </button>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- 확장 변수 편집 모달 -->
<div id="evModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-zinc-900/75" onclick="closeEvModal()"></div>
        <div class="relative z-50 w-full max-w-lg bg-white dark:bg-zinc-800 rounded-xl shadow-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('site.boards.ev_edit_title') ?></h3>
                <button type="button" onclick="closeEvModal()" class="p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 rounded cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <input type="hidden" id="evEditId" value="0">
            <?php
            $inp = 'w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200';
            ?>

            <!-- 변수 이름 -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.ev_var_name') ?></label>
                <input type="text" id="evVarName" class="<?= $inp ?>" pattern="[a-zA-Z_][a-zA-Z0-9_]*" placeholder="custom_field">
                <p class="text-xs text-zinc-500 mt-1"><?= __('site.boards.ev_var_name_help') ?></p>
            </div>

            <!-- 표시 이름 -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.ev_display_name') ?></label>
                <div class="relative">
                    <input type="text" id="evTitle" class="<?= $inp ?> pr-8">
                    <button type="button" onclick="openMultilangModal(getEvLangKey('title'), 'evTitle')" class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition"><?= RZX_MULTILANG_SVG ?></button>
                </div>
                <p class="text-xs text-zinc-500 mt-1"><?= __('site.boards.ev_display_name_help') ?></p>
            </div>

            <!-- 입력 타입 -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.ev_var_type') ?></label>
                <select id="evVarType" class="<?= $inp ?>" onchange="toggleEvOptions()">
                    <?php foreach ($varTypes as $val => $label): ?>
                    <option value="<?= $val ?>"><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>

            <!-- 설명 -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.ev_description') ?></label>
                <div class="relative">
                    <textarea id="evDesc" rows="3" class="<?= $inp ?> pr-8"></textarea>
                    <button type="button" onclick="openMultilangModal(getEvLangKey('description'), 'evDesc', 'editor')" class="absolute right-2 top-2 text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition"><?= RZX_MULTILANG_SVG ?></button>
                </div>
            </div>

            <!-- 선택 항목 (select/radio/checkbox) -->
            <div class="mb-4 hidden" id="evOptionsWrap">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.ev_options') ?></label>
                <div class="relative">
                    <textarea id="evOptions" rows="4" class="<?= $inp ?> pr-8 font-mono" placeholder="option1&#10;option2&#10;option3"></textarea>
                    <button type="button" onclick="openMultilangModal(getEvLangKey('options'), 'evOptions', 'editor')" class="absolute right-2 top-2 text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition"><?= RZX_MULTILANG_SVG ?></button>
                </div>
                <p class="text-xs text-zinc-500 mt-1"><?= __('site.boards.ev_options_help') ?></p>
            </div>

            <!-- 기본값 -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.ev_default_value') ?></label>
                <div class="relative">
                    <input type="text" id="evDefault" class="<?= $inp ?> pr-8">
                    <button type="button" onclick="openMultilangModal(getEvLangKey('default_value'), 'evDefault')" class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition"><?= RZX_MULTILANG_SVG ?></button>
                </div>
            </div>

            <!-- 옵션 체크박스 -->
            <div class="flex flex-wrap gap-4 mb-4">
                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                    <input type="checkbox" id="evRequired" class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
                    <?= __('site.boards.ev_is_required') ?>
                </label>
                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                    <input type="checkbox" id="evSearchable" class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
                    <?= __('site.boards.ev_is_searchable') ?>
                </label>
                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                    <input type="checkbox" id="evShownInList" class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
                    <?= __('site.boards.ev_is_shown_in_list') ?>
                </label>
            </div>

            <!-- 버튼 -->
            <div class="flex justify-between border-t border-zinc-200 dark:border-zinc-700 pt-4">
                <button type="button" onclick="closeEvModal()" class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 rounded-lg transition"><?= __('admin.buttons.cancel') ?></button>
                <button type="button" onclick="saveEv()" class="px-6 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition"><?= __('admin.buttons.save') ?></button>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/boards-edit-extra_vars-js.php'; ?>
