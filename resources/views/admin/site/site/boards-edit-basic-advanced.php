<?php
/**
 * RezlyX Admin - 게시판 설정: 고급 설정 섹션
 * boards-edit-basic.php에서 include됨
 * 사용 변수: $board, $boardId
 */
$tgl = 'w-9 h-5 bg-zinc-300 dark:bg-zinc-600 peer-checked:bg-blue-600 rounded-full peer peer-focus:ring-2 peer-focus:ring-blue-300 after:content-[\'\'] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-full';
$lbl = 'text-sm text-zinc-700 dark:text-zinc-300';
$inp = 'px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200';
$hint = 'text-xs text-zinc-500 dark:text-zinc-400';
$warn = 'text-xs text-amber-600 dark:text-amber-400';
$sep = 'border-t border-zinc-200 dark:border-zinc-700 pt-4 mt-4';
?>

<!-- 상담 기능 -->
<div class="mb-4">
    <label class="flex items-center justify-between">
        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('site.boards.adv_consultation') ?></span>
        <label class="relative inline-flex items-center cursor-pointer">
            <input type="hidden" name="consultation" value="0">
            <input type="checkbox" name="consultation" value="1" <?= ($board['consultation'] ?? 0) ? 'checked' : '' ?> class="sr-only peer">
            <div class="<?= $tgl ?>"></div>
        </label>
    </label>
    <p class="<?= $hint ?> mt-1"><?= __('site.boards.adv_consultation_help') ?></p>
</div>

<!-- 익명 사용 -->
<div class="mb-4">
    <label class="flex items-center justify-between">
        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('site.boards.adv_use_anonymous') ?></span>
        <label class="relative inline-flex items-center cursor-pointer">
            <input type="hidden" name="use_anonymous" value="0">
            <input type="checkbox" name="use_anonymous" value="1" <?= ($board['use_anonymous'] ?? 0) ? 'checked' : '' ?> class="sr-only peer">
            <div class="<?= $tgl ?>"></div>
        </label>
    </label>
    <p class="<?= $hint ?> mt-1"><?= __('site.boards.adv_anonymous_help') ?></p>
</div>

<!-- 관리자 익명 제외 -->
<div class="mb-4 ml-4">
    <label class="flex items-center gap-2 <?= $lbl ?>">
        <input type="checkbox" name="admin_anon_exclude" value="1" <?= ($board['admin_anon_exclude'] ?? 0) ? 'checked' : '' ?>
               class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
        <?= __('site.boards.adv_admin_anon_exclude') ?>
    </label>
    <p class="<?= $hint ?> ml-6"><?= __('site.boards.adv_admin_anon_exclude_help') ?></p>
</div>

<!-- 익명 닉네임 -->
<div class="mb-4 ml-4">
    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.adv_anonymous_name') ?></label>
    <?php if (!empty($board) && isset($boardId) && function_exists('rzx_multilang_input')): ?>
        <?php rzx_multilang_input('anonymous_name', $board['anonymous_name'] ?? 'anonymous', "board.{$boardId}.anonymous_name", [
            'placeholder' => __('site.boards.adv_anonymous_name_placeholder')
        ]); ?>
    <?php else: ?>
        <input type="text" name="anonymous_name" value="<?= htmlspecialchars($board['anonymous_name'] ?? 'anonymous') ?>"
               class="w-64 <?= $inp ?>" placeholder="<?= __('site.boards.adv_anonymous_name_placeholder') ?>">
    <?php endif; ?>
    <p class="<?= $hint ?> mt-1"><?= __('site.boards.adv_anonymous_name_help') ?></p>
</div>

<div class="<?= $sep ?>"></div>

<!-- 문서 길이 제한 -->
<div class="mb-4">
    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.adv_doc_length') ?></label>
    <div class="flex items-center gap-2">
        <input type="number" name="doc_length_limit" value="<?= (int)($board['doc_length_limit'] ?? 1024) ?>" min="0"
               class="w-28 <?= $inp ?>">
        <span class="<?= $lbl ?>"><?= __('site.boards.adv_unit_kb') ?></span>
    </div>
    <p class="<?= $hint ?> mt-1"><?= __('site.boards.adv_doc_length_help') ?></p>
</div>

<!-- 댓글 길이 제한 -->
<div class="mb-4">
    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.adv_comment_length') ?></label>
    <div class="flex items-center gap-2">
        <input type="number" name="comment_length_limit" value="<?= (int)($board['comment_length_limit'] ?? 128) ?>" min="0"
               class="w-28 <?= $inp ?>">
        <span class="<?= $lbl ?>"><?= __('site.boards.adv_unit_kb') ?></span>
    </div>
    <p class="<?= $hint ?> mt-1"><?= __('site.boards.adv_comment_length_help') ?></p>
</div>

<!-- Data URL 제한 -->
<div class="mb-4">
    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.adv_data_url_limit') ?></label>
    <div class="flex items-center gap-2">
        <input type="number" name="data_url_limit" value="<?= (int)($board['data_url_limit'] ?? 64) ?>" min="0"
               class="w-28 <?= $inp ?>">
        <span class="<?= $lbl ?>"><?= __('site.boards.adv_unit_kb') ?></span>
    </div>
    <p class="<?= $hint ?> mt-1"><?= __('site.boards.adv_data_url_limit_help') ?></p>
</div>

<div class="<?= $sep ?>"></div>

<!-- 게시글 수정 내역 -->
<div class="mb-4">
    <label class="flex items-center gap-2 <?= $lbl ?>">
        <input type="checkbox" name="use_edit_history" value="1" <?= ($board['use_edit_history'] ?? 0) ? 'checked' : '' ?>
               class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
        <?= __('site.boards.adv_use_edit_history') ?>
    </label>
    <p class="<?= $hint ?> ml-6"><?= __('site.boards.adv_use_edit_history_help') ?></p>
    <p class="<?= $warn ?> ml-6"><?= __('site.boards.adv_use_edit_history_warn') ?></p>
</div>

<!-- 댓글 작성시 글 수정 시각 갱신 -->
<div class="mb-4">
    <label class="flex items-center justify-between">
        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('site.boards.adv_update_order_comment') ?></span>
        <label class="relative inline-flex items-center cursor-pointer">
            <input type="hidden" name="update_order_on_comment" value="0">
            <input type="checkbox" name="update_order_on_comment" value="1" <?= ($board['update_order_on_comment'] ?? 0) ? 'checked' : '' ?> class="sr-only peer">
            <div class="<?= $tgl ?>"></div>
        </label>
    </label>
    <p class="<?= $hint ?> mt-1"><?= __('site.boards.adv_update_order_comment_help') ?></p>
</div>

<!-- 삭제시 휴지통 사용 -->
<div class="mb-4">
    <label class="flex items-center justify-between">
        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('site.boards.adv_use_trash') ?></span>
        <label class="relative inline-flex items-center cursor-pointer">
            <input type="hidden" name="use_trash" value="0">
            <input type="checkbox" name="use_trash" value="1" <?= ($board['use_trash'] ?? 1) ? 'checked' : '' ?> class="sr-only peer">
            <div class="<?= $tgl ?>"></div>
        </label>
    </label>
    <p class="<?= $hint ?> mt-1"><?= __('site.boards.adv_use_trash_help') ?></p>
</div>

<!-- 유니코드 특수문자 오남용 금지 -->
<div class="mb-4">
    <label class="flex items-center justify-between">
        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('site.boards.adv_unicode_abuse') ?></span>
        <label class="relative inline-flex items-center cursor-pointer">
            <input type="hidden" name="unicode_abuse_block" value="0">
            <input type="checkbox" name="unicode_abuse_block" value="1" <?= ($board['unicode_abuse_block'] ?? 1) ? 'checked' : '' ?> class="sr-only peer">
            <div class="<?= $tgl ?>"></div>
        </label>
    </label>
    <p class="<?= $hint ?> mt-1"><?= __('site.boards.adv_unicode_abuse_help') ?></p>
</div>

<div class="<?= $sep ?>"></div>

<!-- 글 보호 기능 -->
<div class="mb-4">
    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('site.boards.adv_protect_post_title') ?></label>
    <div class="space-y-2 ml-2">
        <label class="flex items-center gap-2 <?= $lbl ?>">
            <input type="checkbox" name="protect_edit_comment_enabled" value="1" <?= ($board['protect_edit_comment_count'] ?? 0) > 0 ? 'checked' : '' ?>
                   class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
            <?= __('site.boards.adv_protect_edit_by_comment') ?>
            <input type="number" name="protect_edit_comment_count" value="<?= max(1, (int)($board['protect_edit_comment_count'] ?? 1)) ?>" min="1"
                   class="w-16 px-2 py-1 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded text-zinc-800 dark:text-zinc-200 text-center">
            <?= __('site.boards.adv_protect_edit_by_comment_after') ?>
        </label>
        <label class="flex items-center gap-2 <?= $lbl ?>">
            <input type="checkbox" name="protect_delete_comment_enabled" value="1" <?= ($board['protect_delete_comment_count'] ?? 0) > 0 ? 'checked' : '' ?>
                   class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
            <?= __('site.boards.adv_protect_delete_by_comment') ?>
            <input type="number" name="protect_delete_comment_count" value="<?= max(1, (int)($board['protect_delete_comment_count'] ?? 1)) ?>" min="1"
                   class="w-16 px-2 py-1 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded text-zinc-800 dark:text-zinc-200 text-center">
            <?= __('site.boards.adv_protect_delete_by_comment_after') ?>
        </label>
    </div>
</div>

<!-- 댓글 보호 기능 -->
<div class="mb-4">
    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('site.boards.adv_protect_comment_title') ?></label>
    <div class="space-y-2 ml-2">
        <label class="flex items-center gap-2 <?= $lbl ?>">
            <input type="checkbox" name="protect_comment_edit_reply_enabled" value="1" <?= ($board['protect_comment_edit_reply'] ?? 0) > 0 ? 'checked' : '' ?>
                   class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
            <?= __('site.boards.adv_protect_comment_edit_reply') ?>
            <input type="number" name="protect_comment_edit_reply" value="<?= max(1, (int)($board['protect_comment_edit_reply'] ?? 1)) ?>" min="1"
                   class="w-16 px-2 py-1 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded text-zinc-800 dark:text-zinc-200 text-center">
            <?= __('site.boards.adv_protect_comment_edit_reply_after') ?>
        </label>
        <label class="flex items-center gap-2 <?= $lbl ?>">
            <input type="checkbox" name="protect_comment_delete_reply_enabled" value="1" <?= ($board['protect_comment_delete_reply'] ?? 0) > 0 ? 'checked' : '' ?>
                   class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
            <?= __('site.boards.adv_protect_comment_delete_reply') ?>
            <input type="number" name="protect_comment_delete_reply" value="<?= max(1, (int)($board['protect_comment_delete_reply'] ?? 1)) ?>" min="1"
                   class="w-16 px-2 py-1 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded text-zinc-800 dark:text-zinc-200 text-center">
            <?= __('site.boards.adv_protect_comment_delete_reply_after') ?>
        </label>
    </div>
</div>

<!-- 기간 제한 기능 -->
<div class="mb-4">
    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('site.boards.adv_restrict_title') ?></label>
    <div class="space-y-2 ml-2">
        <label class="flex items-center gap-2 <?= $lbl ?>">
            <input type="checkbox" name="restrict_edit_enabled" value="1" <?= ($board['restrict_edit_days'] ?? 0) > 0 ? 'checked' : '' ?>
                   class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
            <?= __('site.boards.adv_restrict_edit') ?>
            <input type="number" name="restrict_edit_days" value="<?= max(1, (int)($board['restrict_edit_days'] ?? 30)) ?>" min="1"
                   class="w-20 px-2 py-1 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded text-zinc-800 dark:text-zinc-200 text-center">
            <?= __('site.boards.adv_restrict_edit_after') ?>
        </label>
        <label class="flex items-center gap-2 <?= $lbl ?>">
            <input type="checkbox" name="restrict_comment_enabled" value="1" <?= ($board['restrict_comment_days'] ?? 0) > 0 ? 'checked' : '' ?>
                   class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
            <?= __('site.boards.adv_restrict_comment') ?>
            <input type="number" name="restrict_comment_days" value="<?= max(1, (int)($board['restrict_comment_days'] ?? 30)) ?>" min="1"
                   class="w-20 px-2 py-1 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded text-zinc-800 dark:text-zinc-200 text-center">
            <?= __('site.boards.adv_restrict_comment_after') ?>
        </label>
    </div>
</div>

<div class="<?= $sep ?>"></div>

<!-- 최고관리자 보호 기능 -->
<div class="mb-4">
    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('site.boards.adv_admin_protect_title') ?></label>
    <div class="flex gap-4 ml-2">
        <label class="flex items-center gap-2 <?= $lbl ?>">
            <input type="checkbox" name="admin_protect_delete" value="1" <?= ($board['admin_protect_delete'] ?? 1) ? 'checked' : '' ?>
                   class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
            <?= __('site.boards.adv_admin_protect_delete') ?>
        </label>
        <label class="flex items-center gap-2 <?= $lbl ?>">
            <input type="checkbox" name="admin_protect_edit" value="1" <?= ($board['admin_protect_edit'] ?? 1) ? 'checked' : '' ?>
                   class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
            <?= __('site.boards.adv_admin_protect_edit') ?>
        </label>
    </div>
    <p class="<?= $hint ?> mt-1"><?= __('site.boards.adv_admin_protect_help') ?></p>
</div>

<!-- 댓글 자리 남김 -->
<div class="mb-4">
    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.adv_comment_placeholder_title') ?></label>
    <select name="comment_placeholder_type" class="<?= $inp ?>">
        <option value="none" <?= ($board['comment_placeholder_type'] ?? 'none') === 'none' ? 'selected' : '' ?>><?= __('site.boards.adv_comment_placeholder_none') ?></option>
        <option value="message" <?= ($board['comment_placeholder_type'] ?? '') === 'message' ? 'selected' : '' ?>><?= __('site.boards.adv_comment_placeholder_message') ?></option>
    </select>
    <p class="<?= $hint ?> mt-1"><?= __('site.boards.adv_comment_placeholder_help') ?></p>
</div>

<!-- 상태 -->
<div class="mb-4">
    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('site.boards.adv_status_title') ?></label>
    <div class="flex gap-4 ml-2">
        <label class="flex items-center gap-2 <?= $lbl ?>">
            <input type="checkbox" name="allow_public" value="1" <?= ($board['allow_public'] ?? 1) ? 'checked' : '' ?>
                   class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
            <?= __('site.boards.adv_allow_public') ?>
        </label>
        <label class="flex items-center gap-2 <?= $lbl ?>">
            <input type="checkbox" name="allow_secret_default" value="1" <?= ($board['allow_secret_default'] ?? 0) ? 'checked' : '' ?>
                   class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
            <?= __('site.boards.adv_allow_secret') ?>
        </label>
    </div>
    <p class="<?= $hint ?> mt-1"><?= __('site.boards.adv_status_help') ?></p>
</div>

<div class="<?= $sep ?>"></div>

<!-- 관리자 메일 (알림 수신) -->
<div class="mb-4">
    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.adv_admin_mail') ?></label>
    <input type="text" name="admin_mail" value="<?= htmlspecialchars($board['admin_mail'] ?? '') ?>"
           class="w-full <?= $inp ?>" placeholder="admin@example.com">
    <p class="<?= $hint ?> mt-1"><?= __('site.boards.adv_admin_mail_help') ?></p>
</div>
