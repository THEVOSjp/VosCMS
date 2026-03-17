<?php
/**
 * RezlyX Admin - 게시판 설정: 분류 관리 탭
 * boards-edit.php에서 include됨 ($board, $pdo, $prefix, $adminUrl, $boardId, $categories 사용 가능)
 */
?>
<div class="space-y-6">
    <!-- 분류 추가 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200"><?= __('site.boards.cat_mgmt_title') ?></h3>
            <button type="button" id="btnAddCategory"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <?= __('site.boards.cat_add') ?>
            </button>
        </div>

        <!-- 추가 폼 (숨김) -->
        <div id="categoryAddForm" class="hidden mb-4 p-4 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg border border-zinc-200 dark:border-zinc-600">
            <div class="grid grid-cols-12 gap-3 items-end">
                <div class="col-span-4">
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1"><?= __('site.boards.cat_name') ?></label>
                    <input type="text" id="newCatName" class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200" placeholder="<?= __('site.boards.cat_name_placeholder') ?>">
                </div>
                <div class="col-span-3">
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1"><?= __('site.boards.cat_slug') ?></label>
                    <input type="text" id="newCatSlug" class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200" placeholder="general" pattern="[a-z0-9_-]+">
                </div>
                <div class="col-span-2">
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1"><?= __('site.boards.cat_color') ?></label>
                    <input type="color" id="newCatColor" value="#3B82F6" class="w-full h-9 rounded-lg border border-zinc-300 dark:border-zinc-600 cursor-pointer">
                </div>
                <div class="col-span-3 flex gap-2">
                    <button type="button" id="btnSaveCategory" class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition"><?= __('admin.buttons.save') ?></button>
                    <button type="button" id="btnCancelCategory" class="px-4 py-2 text-sm font-medium text-zinc-600 dark:text-zinc-300 bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-600 transition"><?= __('admin.buttons.cancel') ?></button>
                </div>
            </div>
        </div>

        <!-- 분류 목록 -->
        <div id="categoryList">
            <?php if (empty($categories)): ?>
            <div id="categoryEmpty" class="text-center py-8 text-zinc-400 dark:text-zinc-500">
                <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
                <p><?= __('site.boards.cat_empty') ?></p>
            </div>
            <?php else: ?>
            <div class="space-y-2" id="categorySortable">
                <?php foreach ($categories as $cat): ?>
                <div class="category-item flex items-center gap-3 p-3 bg-zinc-50 dark:bg-zinc-700/30 rounded-lg border border-zinc-200 dark:border-zinc-600 group" data-id="<?= $cat['id'] ?>">
                    <span class="cursor-grab text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300" title="Drag to reorder">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
                    </span>
                    <span class="w-4 h-4 rounded-full flex-shrink-0" style="background-color: <?= htmlspecialchars($cat['color'] ?? '#3B82F6') ?>"></span>
                    <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200 flex-1"><?= htmlspecialchars($cat['name']) ?></span>
                    <span class="text-xs text-zinc-500 dark:text-zinc-400"><?= htmlspecialchars($cat['slug'] ?? '') ?></span>
                    <label class="flex items-center gap-1 text-xs">
                        <input type="checkbox" class="cat-active-toggle rounded border-zinc-300" data-id="<?= $cat['id'] ?>" <?= ($cat['is_active'] ?? 1) ? 'checked' : '' ?>>
                        <span class="text-zinc-500 dark:text-zinc-400"><?= __('site.boards.active') ?></span>
                    </label>
                    <button type="button" class="btn-cat-edit p-1.5 text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 opacity-0 group-hover:opacity-100 transition" data-id="<?= $cat['id'] ?>" title="<?= __('site.boards.settings') ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                    <button type="button" class="btn-cat-delete p-1.5 text-zinc-400 hover:text-red-600 dark:hover:text-red-400 opacity-0 group-hover:opacity-100 transition" data-id="<?= $cat['id'] ?>" title="<?= __('site.boards.delete') ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 분류 사용 설정 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-4"><?= __('site.boards.cat_usage_title') ?></h3>
        <label class="flex items-center gap-2">
            <input type="checkbox" id="showCategory" <?= ($board['show_category'] ?? 0) ? 'checked' : '' ?>
                   class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
            <span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('site.boards.cat_show_in_list') ?></span>
        </label>
        <p class="mt-1 text-xs text-zinc-500 ml-6"><?= __('site.boards.cat_show_in_list_help') ?></p>
    </div>
</div>

<script>
console.log('[BoardCategories] 분류 관리 탭 로드됨');
const adminUrl = '<?= $adminUrl ?>';
const boardId = <?= $boardId ?>;

// 추가 폼 토글
document.getElementById('btnAddCategory').addEventListener('click', () => {
    console.log('[BoardCategories] 추가 폼 열기');
    document.getElementById('categoryAddForm').classList.remove('hidden');
});
document.getElementById('btnCancelCategory').addEventListener('click', () => {
    console.log('[BoardCategories] 추가 폼 닫기');
    document.getElementById('categoryAddForm').classList.add('hidden');
    document.getElementById('newCatName').value = '';
    document.getElementById('newCatSlug').value = '';
});

// 분류 저장
document.getElementById('btnSaveCategory').addEventListener('click', async () => {
    const name = document.getElementById('newCatName').value.trim();
    const slug = document.getElementById('newCatSlug').value.trim();
    const color = document.getElementById('newCatColor').value;
    if (!name) { alert('<?= __('site.boards.cat_name_required') ?>'); return; }
    console.log('[BoardCategories] 분류 저장:', { name, slug, color });

    try {
        const form = new URLSearchParams({ action: 'category_add', board_id: boardId, name, slug, color });
        const resp = await fetch(adminUrl + '/site/boards/api', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: form });
        const data = await resp.json();
        console.log('[BoardCategories] 응답:', data);
        if (data.success) location.reload();
        else alert(data.message || 'Error');
    } catch (err) { console.error('[BoardCategories] 에러:', err); alert('Error: ' + err.message); }
});

// 분류 삭제
document.querySelectorAll('.btn-cat-delete').forEach(btn => {
    btn.addEventListener('click', async () => {
        if (!confirm('<?= __('site.boards.cat_delete_confirm') ?>')) return;
        const catId = btn.dataset.id;
        console.log('[BoardCategories] 분류 삭제:', catId);
        try {
            const form = new URLSearchParams({ action: 'category_delete', board_id: boardId, category_id: catId });
            const resp = await fetch(adminUrl + '/site/boards/api', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: form });
            const data = await resp.json();
            if (data.success) btn.closest('.category-item').remove();
            else alert(data.message || 'Error');
        } catch (err) { console.error(err); alert('Error: ' + err.message); }
    });
});

// 분류 사용 설정 토글
document.getElementById('showCategory')?.addEventListener('change', async function() {
    console.log('[BoardCategories] show_category 변경:', this.checked);
    const form = new URLSearchParams({ action: 'update', board_id: boardId, show_category: this.checked ? '1' : '0' });
    try {
        await fetch(adminUrl + '/site/boards/api', { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: form });
    } catch (err) { console.error(err); }
});
</script>
