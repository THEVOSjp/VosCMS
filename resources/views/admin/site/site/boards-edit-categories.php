<?php
/**
 * RezlyX Admin - 게시판 설정: 분류 관리 탭
 * boards-edit.php에서 include됨 ($board, $pdo, $prefix, $adminUrl, $boardId, $categories 사용 가능)
 */

// 트리 구조 빌드
function buildCategoryTree(array $cats, int $parentId = 0): array {
    $tree = [];
    foreach ($cats as $cat) {
        if ((int)($cat['parent_id'] ?? 0) === $parentId) {
            $cat['children'] = buildCategoryTree($cats, (int)$cat['id']);
            $tree[] = $cat;
        }
    }
    return $tree;
}
$categoryTree = buildCategoryTree($categories);
?>
<div class="space-y-6">
    <!-- 분류 트리 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200"><?= __('site.boards.cat_mgmt_title') ?></h3>
            <button type="button" onclick="openCatModal(0, 0)"
                    class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition flex items-center gap-1.5">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <?= __('site.boards.cat_add') ?>
            </button>
        </div>

        <div id="categoryList">
            <?php if (empty($categories)): ?>
            <div id="categoryEmpty" class="text-center py-8 text-zinc-400 dark:text-zinc-500">
                <svg class="w-12 h-12 mx-auto mb-3 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A2 2 0 013 12V7a4 4 0 014-4z"/></svg>
                <p><?= __('site.boards.cat_empty') ?></p>
            </div>
            <?php else: ?>
            <div id="catSortRoot" class="cat-sortable-list space-y-2">
            <?php renderCatTree($categoryTree, $adminUrl); ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 분류 설정 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-4"><?= __('site.boards.cat_settings_title') ?></h3>

        <!-- 분류 숨기기 -->
        <div class="mb-4">
            <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                <input type="checkbox" id="hideCat" <?= ($board['hide_categories'] ?? 0) ? 'checked' : '' ?>
                       class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600" onchange="saveBoardField('hide_categories', this.checked ? 1 : 0)">
                <?= __('site.boards.cat_hide') ?>
            </label>
            <p class="text-xs text-zinc-500 ml-6 mt-1"><?= __('site.boards.cat_hide_help') ?></p>
        </div>

        <!-- 미분류 허용 -->
        <div>
            <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                <input type="checkbox" id="allowUncat" <?= ($board['allow_uncategorized'] ?? 1) ? 'checked' : '' ?>
                       class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600" onchange="saveBoardField('allow_uncategorized', this.checked ? 1 : 0)">
                <?= __('site.boards.cat_allow_uncat') ?>
            </label>
            <p class="text-xs text-zinc-500 ml-6 mt-1"><?= __('site.boards.cat_allow_uncat_help') ?></p>
        </div>
    </div>
</div>

<!-- 분류 편집 모달 -->
<div id="catEditModal" class="fixed inset-0 z-50 hidden overflow-y-auto">
    <div class="flex items-center justify-center min-h-screen px-4">
        <div class="fixed inset-0 bg-zinc-900/75" onclick="closeCatModal()"></div>
        <div class="relative z-50 w-full max-w-lg bg-white dark:bg-zinc-800 rounded-xl shadow-xl p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white" id="catModalTitle"><?= __('site.boards.cat_edit_title') ?></h3>
                <button type="button" onclick="closeCatModal()" class="p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 rounded cursor-pointer">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </button>
            </div>

            <input type="hidden" id="catEditId" value="0">
            <input type="hidden" id="catEditParent" value="0">

            <!-- 분류 명 -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.cat_name') ?></label>
                <div class="relative">
                    <input type="text" id="catEditName" class="w-full px-3 py-2 pr-8 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200" placeholder="<?= __('site.boards.cat_name_placeholder') ?>">
                    <button type="button" onclick="openMultilangModal(getCatLangKey('name'), 'catEditName')" class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition"><?= RZX_MULTILANG_SVG ?></button>
                </div>
            </div>

            <!-- 분류 폰트 색깔 -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.cat_font_color') ?></label>
                <div class="flex items-center gap-3">
                    <input type="color" id="catEditFontColor" value="#000000" class="w-10 h-9 rounded border border-zinc-300 dark:border-zinc-600 cursor-pointer">
                    <input type="text" id="catEditFontColorText" class="w-32 px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200" placeholder="#ff0000">
                </div>
                <p class="text-xs text-zinc-500 mt-1"><?= __('site.boards.cat_font_color_help') ?></p>
            </div>

            <!-- 분류 설명 -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.cat_description') ?></label>
                <div class="relative">
                    <textarea id="catEditDesc" rows="3" class="w-full px-3 py-2 pr-8 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200"></textarea>
                    <button type="button" onclick="openMultilangModal(getCatLangKey('description'), 'catEditDesc')" class="absolute right-2 top-2 text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition"><?= RZX_MULTILANG_SVG ?></button>
                </div>
            </div>

            <!-- 작성 허용 그룹 -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('site.boards.cat_allowed_groups') ?></label>
                <div class="flex flex-wrap gap-4">
                    <?php
                    $gradeStmt = $pdo->prepare("SELECT id, name, slug, color FROM {$prefix}member_grades ORDER BY sort_order ASC");
                    $gradeStmt->execute();
                    $memberGrades = $gradeStmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($memberGrades as $grade):
                    ?>
                    <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                        <input type="checkbox" class="cat-group-cb w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600" value="<?= htmlspecialchars($grade['slug']) ?>">
                        <span <?= !empty($grade['color']) ? 'style="color:' . htmlspecialchars($grade['color']) . '"' : '' ?>><?= htmlspecialchars($grade['name']) ?></span>
                    </label>
                    <?php endforeach; ?>
                </div>
                <p class="text-xs text-zinc-500 mt-1"><?= __('site.boards.cat_allowed_groups_help') ?></p>
            </div>

            <!-- 펼침 / 기본 분류 -->
            <div class="flex gap-6 mb-4">
                <div>
                    <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                        <input type="checkbox" id="catEditExpanded" class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
                        <?= __('site.boards.cat_is_expanded') ?>
                    </label>
                    <p class="text-xs text-zinc-500 ml-6"><?= __('site.boards.cat_is_expanded_help') ?></p>
                </div>
                <div>
                    <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                        <input type="checkbox" id="catEditDefault" class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
                        <?= __('site.boards.cat_is_default') ?>
                    </label>
                    <p class="text-xs text-zinc-500 ml-6"><?= __('site.boards.cat_is_default_help') ?></p>
                </div>
            </div>

            <!-- 버튼 -->
            <div class="flex justify-between border-t border-zinc-200 dark:border-zinc-700 pt-4 mt-4">
                <button type="button" onclick="closeCatModal()" class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 rounded-lg transition"><?= __('admin.buttons.cancel') ?></button>
                <button type="button" onclick="saveCatModal()" class="px-6 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition"><?= __('admin.buttons.save') ?></button>
            </div>
        </div>
    </div>
</div>

<?php
// 트리 렌더링 함수
function renderCatTree(array $tree, string $adminUrl, int $depth = 0): void {
    foreach ($tree as $cat):
?>
<div class="category-item" data-id="<?= $cat['id'] ?>" data-parent="<?= $cat['parent_id'] ?? 0 ?>">
    <div class="cat-row flex items-center gap-3 p-3 bg-zinc-50 dark:bg-zinc-700/30 rounded-lg border border-zinc-200 dark:border-zinc-600 group">
        <!-- 드래그 핸들 -->
        <span class="cat-drag-handle cursor-grab active:cursor-grabbing text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
        </span>
        <span class="w-4 h-4 rounded-full flex-shrink-0" style="background-color: <?= htmlspecialchars($cat['color'] ?? '#3B82F6') ?>"></span>
        <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200 flex-1" <?= !empty($cat['font_color']) ? 'style="color:' . htmlspecialchars($cat['font_color']) . '"' : '' ?>><?= htmlspecialchars($cat['name']) ?></span>
        <button type="button" onclick="openCatModal(0, <?= $cat['id'] ?>)" class="p-1 text-zinc-400 hover:text-green-600 dark:hover:text-green-400 opacity-0 group-hover:opacity-100 transition" title="<?= __('site.boards.cat_add_sub') ?>">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        </button>
        <button type="button" onclick="openCatModal(<?= $cat['id'] ?>, <?= $cat['parent_id'] ?? 0 ?>)" class="p-1 text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 opacity-0 group-hover:opacity-100 transition" title="<?= __('site.boards.cat_edit_title') ?>">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
        </button>
        <button type="button" onclick="deleteCat(<?= $cat['id'] ?>)" class="p-1 text-zinc-400 hover:text-red-600 dark:hover:text-red-400 opacity-0 group-hover:opacity-100 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
        </button>
    </div>
    <?php if (!empty($cat['children'])): ?>
    <div class="cat-sortable-list ml-6 mt-2 space-y-2">
        <?php renderCatTree($cat['children'], $adminUrl, $depth + 1); ?>
    </div>
    <?php else: ?>
    <div class="cat-sortable-list ml-6 mt-0 space-y-2"></div>
    <?php endif; ?>
</div>
<?php
    endforeach;
}
?>

<?php include __DIR__ . '/boards-edit-categories-js.php'; ?>
