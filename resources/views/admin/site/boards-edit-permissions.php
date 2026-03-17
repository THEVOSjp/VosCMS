<?php
/**
 * RezlyX Admin - 게시판 설정: 권한 설정 탭
 * boards-edit.php에서 include됨
 */
$permLevels = [
    'all' => __('site.boards.perm_all'),
    'member' => __('site.boards.perm_member'),
    'admin' => __('site.boards.perm_admin'),
];
$permFields = [
    'perm_list' => ['label' => __('site.boards.perm_list_label'), 'desc' => __('site.boards.perm_list_desc'), 'default' => 'all'],
    'perm_read' => ['label' => __('site.boards.perm_read_label'), 'desc' => __('site.boards.perm_read_desc'), 'default' => 'all'],
    'perm_write' => ['label' => __('site.boards.perm_write_label'), 'desc' => __('site.boards.perm_write_desc'), 'default' => 'member'],
    'perm_comment' => ['label' => __('site.boards.perm_comment_label'), 'desc' => __('site.boards.perm_comment_desc'), 'default' => 'member'],
    'perm_manage' => ['label' => __('site.boards.perm_manage_label'), 'desc' => __('site.boards.perm_manage_desc'), 'default' => 'admin'],
];
?>
<form id="boardPermForm" class="space-y-6">
    <input type="hidden" name="board_id" value="<?= $boardId ?>">
    <input type="hidden" name="action" value="update">

    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-4"><?= __('site.boards.perm_title') ?></h3>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6"><?= __('site.boards.perm_desc') ?></p>

        <div class="space-y-4">
            <?php foreach ($permFields as $field => $info): ?>
            <div class="flex items-center justify-between py-3 border-b border-zinc-100 dark:border-zinc-700 last:border-0">
                <div>
                    <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200"><?= $info['label'] ?></p>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5"><?= $info['desc'] ?></p>
                </div>
                <select name="<?= $field ?>" class="px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200 min-w-[140px]">
                    <?php foreach ($permLevels as $val => $label): ?>
                    <option value="<?= $val ?>" <?= ($board[$field] ?? $info['default']) === $val ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 관리자 메일 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-4"><?= __('site.boards.admin_mail_title') ?></h3>
        <div>
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.admin_mail_label') ?></label>
            <input type="email" name="admin_mail" value="<?= htmlspecialchars($board['admin_mail'] ?? '') ?>"
                   class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200"
                   placeholder="admin@example.com">
            <p class="mt-1 text-xs text-zinc-500"><?= __('site.boards.admin_mail_help') ?></p>
        </div>
    </div>

    <!-- 버튼 -->
    <div class="flex items-center justify-end gap-3">
        <span id="saveStatus" class="text-sm text-green-600 dark:text-green-400 hidden"></span>
        <button type="submit" class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition"><?= __('admin.buttons.save') ?></button>
    </div>
</form>

<?php include __DIR__ . '/boards-edit-js.php'; ?>
