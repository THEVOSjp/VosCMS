<?php
/**
 * RezlyX Admin - 게시판 설정: 목록 설정 탭
 * boards-edit.php에서 include됨
 */
$currentColumns = json_decode($board['list_columns'] ?? '[]', true) ?: ['no', 'title', 'nick_name', 'created_at', 'view_count'];
$availableColumns = [
    'no' => __('site.boards.lcol_no'),
    'title' => __('site.boards.lcol_title'),
    'nick_name' => __('site.boards.lcol_nick_name'),
    'user_id' => __('site.boards.lcol_user_id'),
    'created_at' => __('site.boards.lcol_created_at'),
    'updated_at' => __('site.boards.lcol_updated_at'),
    'view_count' => __('site.boards.lcol_view_count'),
    'vote_count' => __('site.boards.lcol_vote_count'),
    'comment_count' => __('site.boards.lcol_comment_count'),
    'category' => __('site.boards.lcol_category'),
];
?>
<form id="boardListForm" class="space-y-6">
    <input type="hidden" name="board_id" value="<?= $boardId ?>">
    <input type="hidden" name="action" value="update">

    <!-- 목록 컬럼 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-2"><?= __('site.boards.list_columns_title') ?></h3>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4"><?= __('site.boards.list_columns_desc') ?></p>

        <!-- 선택된 컬럼 -->
        <div class="mb-4">
            <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-2"><?= __('site.boards.list_selected') ?></label>
            <div id="selectedColumns" class="flex flex-wrap gap-2 min-h-[40px] p-3 bg-zinc-50 dark:bg-zinc-700/30 rounded-lg border-2 border-dashed border-zinc-300 dark:border-zinc-600">
                <?php foreach ($currentColumns as $col): ?>
                <?php if (isset($availableColumns[$col])): ?>
                <span class="selected-col inline-flex items-center gap-1 px-3 py-1.5 bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 text-sm rounded-lg cursor-grab" data-col="<?= $col ?>">
                    <?= $availableColumns[$col] ?>
                    <button type="button" class="btn-remove-col ml-1 text-blue-400 hover:text-red-500" data-col="<?= $col ?>">&times;</button>
                </span>
                <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 추가 가능한 컬럼 -->
        <div>
            <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-2"><?= __('site.boards.list_available') ?></label>
            <div id="availableColumns" class="flex flex-wrap gap-2">
                <?php foreach ($availableColumns as $col => $label): ?>
                <button type="button" class="btn-add-col px-3 py-1.5 text-sm rounded-lg border transition
                    <?= in_array($col, $currentColumns) ? 'bg-zinc-100 dark:bg-zinc-700 text-zinc-400 dark:text-zinc-500 border-zinc-200 dark:border-zinc-600 opacity-50 cursor-not-allowed' : 'bg-white dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 border-zinc-300 dark:border-zinc-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:border-blue-300 cursor-pointer' ?>"
                    data-col="<?= $col ?>" data-label="<?= $label ?>" <?= in_array($col, $currentColumns) ? 'disabled' : '' ?>>
                    + <?= $label ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <input type="hidden" name="list_columns" id="listColumnsInput" value='<?= htmlspecialchars(json_encode($currentColumns)) ?>'>
    </div>

    <!-- 정렬 설정 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-4"><?= __('site.boards.sort_title') ?></h3>
        <div class="grid grid-cols-2 gap-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.sort_field') ?></label>
                <select name="sort_field" class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200">
                    <?php
                    $sortFields = ['created_at' => __('site.boards.lcol_created_at'), 'updated_at' => __('site.boards.lcol_updated_at'), 'view_count' => __('site.boards.lcol_view_count'), 'vote_count' => __('site.boards.lcol_vote_count')];
                    foreach ($sortFields as $val => $label):
                    ?>
                    <option value="<?= $val ?>" <?= ($board['sort_field'] ?? 'created_at') === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.sort_direction') ?></label>
                <select name="sort_direction" class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200">
                    <option value="desc" <?= ($board['sort_direction'] ?? 'desc') === 'desc' ? 'selected' : '' ?>><?= __('site.boards.sort_desc') ?></option>
                    <option value="asc" <?= ($board['sort_direction'] ?? '') === 'asc' ? 'selected' : '' ?>><?= __('site.boards.sort_asc') ?></option>
                </select>
            </div>
        </div>

        <div class="mt-4">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="except_notice" value="1" <?= ($board['except_notice'] ?? 0) ? 'checked' : '' ?>
                       class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
                <span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('site.boards.except_notice') ?></span>
            </label>
            <p class="mt-1 text-xs text-zinc-500 ml-6"><?= __('site.boards.except_notice_help') ?></p>
        </div>
    </div>

    <!-- 버튼 -->
    <div class="flex items-center justify-end gap-3">
        <span id="saveStatus" class="text-sm text-green-600 dark:text-green-400 hidden"></span>
        <button type="submit" class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition"><?= __('admin.buttons.save') ?></button>
    </div>
</form>

<script>
console.log('[BoardList] 목록 설정 탭 로드됨');

// 컬럼 추가
document.querySelectorAll('.btn-add-col:not([disabled])').forEach(btn => {
    btn.addEventListener('click', () => {
        const col = btn.dataset.col;
        const label = btn.dataset.label;
        console.log('[BoardList] 컬럼 추가:', col);
        const container = document.getElementById('selectedColumns');
        const span = document.createElement('span');
        span.className = 'selected-col inline-flex items-center gap-1 px-3 py-1.5 bg-blue-100 dark:bg-blue-900/40 text-blue-700 dark:text-blue-300 text-sm rounded-lg cursor-grab';
        span.dataset.col = col;
        span.innerHTML = label + ' <button type="button" class="btn-remove-col ml-1 text-blue-400 hover:text-red-500" data-col="' + col + '">&times;</button>';
        container.appendChild(span);
        btn.disabled = true;
        btn.classList.add('opacity-50', 'cursor-not-allowed');
        btn.classList.remove('cursor-pointer');
        updateColumnInput();
        bindRemoveButtons();
    });
});

function bindRemoveButtons() {
    document.querySelectorAll('.btn-remove-col').forEach(btn => {
        btn.onclick = () => {
            const col = btn.dataset.col;
            console.log('[BoardList] 컬럼 제거:', col);
            btn.closest('.selected-col').remove();
            const addBtn = document.querySelector('.btn-add-col[data-col="' + col + '"]');
            if (addBtn) { addBtn.disabled = false; addBtn.classList.remove('opacity-50', 'cursor-not-allowed'); addBtn.classList.add('cursor-pointer'); }
            updateColumnInput();
        };
    });
}
bindRemoveButtons();

function updateColumnInput() {
    const cols = [...document.querySelectorAll('#selectedColumns .selected-col')].map(el => el.dataset.col);
    document.getElementById('listColumnsInput').value = JSON.stringify(cols);
    console.log('[BoardList] 컬럼 업데이트:', cols);
}
</script>
<?php include __DIR__ . '/boards-edit-js.php'; ?>
