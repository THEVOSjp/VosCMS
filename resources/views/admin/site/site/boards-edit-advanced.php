<?php
/**
 * RezlyX Admin - 게시판 설정: 고급 설정 탭
 * boards-edit.php에서 include됨
 */
?>
<form id="boardAdvForm" class="space-y-6">
    <input type="hidden" name="board_id" value="<?= $boardId ?>">
    <input type="hidden" name="action" value="update">

    <!-- 댓글 설정 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-4"><?= __('site.boards.adv_comment_title') ?></h3>
        <div class="space-y-3">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="allow_comment" value="1" <?= ($board['allow_comment'] ?? 1) ? 'checked' : '' ?>
                       class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
                <span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('site.boards.adv_allow_comment') ?></span>
            </label>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="update_order_on_comment" value="1" <?= ($board['update_order_on_comment'] ?? 0) ? 'checked' : '' ?>
                       class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
                <span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('site.boards.adv_update_order_comment') ?></span>
            </label>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.adv_comment_delete_msg') ?></label>
                <input type="text" name="comment_delete_message" value="<?= htmlspecialchars($board['comment_delete_message'] ?? '') ?>"
                       class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200"
                       placeholder="<?= __('site.boards.adv_comment_delete_msg_placeholder') ?>">
            </div>
        </div>
    </div>

    <!-- 익명/비밀 설정 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-4"><?= __('site.boards.adv_anon_title') ?></h3>
        <div class="space-y-3">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="use_anonymous" value="1" <?= ($board['use_anonymous'] ?? 0) ? 'checked' : '' ?>
                       class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
                <span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('site.boards.adv_use_anonymous') ?></span>
            </label>
            <div class="ml-6">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.adv_anonymous_name') ?></label>
                <input type="text" name="anonymous_name" value="<?= htmlspecialchars($board['anonymous_name'] ?? '') ?>"
                       class="w-64 px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200"
                       placeholder="<?= __('site.boards.adv_anonymous_name_placeholder') ?>">
            </div>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="allow_secret" value="1" <?= ($board['allow_secret'] ?? 0) ? 'checked' : '' ?>
                       class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
                <span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('site.boards.adv_allow_secret') ?></span>
            </label>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="consultation" value="1" <?= ($board['consultation'] ?? 0) ? 'checked' : '' ?>
                       class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
                <span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('site.boards.adv_consultation') ?></span>
            </label>
            <p class="text-xs text-zinc-500 ml-6"><?= __('site.boards.adv_consultation_help') ?></p>
        </div>
    </div>

    <!-- 제한 설정 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-4"><?= __('site.boards.adv_limits_title') ?></h3>
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.adv_doc_length') ?></label>
                <input type="number" name="doc_length_limit" value="<?= (int)($board['doc_length_limit'] ?? 0) ?>" min="0"
                       class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200">
                <p class="mt-1 text-xs text-zinc-500"><?= __('site.boards.adv_zero_unlimited') ?></p>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.adv_comment_length') ?></label>
                <input type="number" name="comment_length_limit" value="<?= (int)($board['comment_length_limit'] ?? 0) ?>" min="0"
                       class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200">
                <p class="mt-1 text-xs text-zinc-500"><?= __('site.boards.adv_zero_unlimited') ?></p>
            </div>
        </div>
    </div>

    <!-- 보호/휴지통 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-4"><?= __('site.boards.adv_protect_title') ?></h3>
        <div class="space-y-3">
            <label class="flex items-center gap-2">
                <input type="checkbox" name="use_trash" value="1" <?= ($board['use_trash'] ?? 0) ? 'checked' : '' ?>
                       class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
                <span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('site.boards.adv_use_trash') ?></span>
            </label>
            <label class="flex items-center gap-2">
                <input type="checkbox" name="protect_content_by_comment" value="1" <?= ($board['protect_content_by_comment'] ?? 0) ? 'checked' : '' ?>
                       class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
                <span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('site.boards.adv_protect_by_comment') ?></span>
            </label>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.adv_protect_days') ?></label>
                <input type="number" name="protect_by_days" value="<?= (int)($board['protect_by_days'] ?? 0) ?>" min="0"
                       class="w-32 px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200">
                <p class="mt-1 text-xs text-zinc-500"><?= __('site.boards.adv_zero_unlimited') ?></p>
            </div>
        </div>
    </div>

    <!-- 버튼 -->
    <div class="flex items-center justify-end gap-3">
        <span id="saveStatus" class="text-sm text-green-600 dark:text-green-400 hidden"></span>
        <button type="submit" class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition"><?= __('admin.buttons.save') ?></button>
    </div>
</form>

<?php include __DIR__ . '/boards-edit-js.php'; ?>
