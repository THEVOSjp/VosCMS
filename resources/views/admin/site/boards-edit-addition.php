<?php
/**
 * RezlyX Admin - 게시판 설정: 추가 설정 탭
 * 통합 게시판, 문서, 댓글, 에디터, 파일, 피드 설정
 */
$inp = 'px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200';
$lbl = 'text-sm text-zinc-700 dark:text-zinc-300';
$hint = 'text-xs text-zinc-500 dark:text-zinc-400';
$tgl = 'w-9 h-5 bg-zinc-300 dark:bg-zinc-600 peer-checked:bg-blue-600 rounded-full peer after:content-[\'\'] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-full';

// 다른 게시판 목록 (통합 게시판용)
$otherBoards = $pdo->prepare("SELECT id, slug, title FROM {$prefix}boards WHERE id != ? ORDER BY sort_order");
$otherBoards->execute([$boardId]);
$otherBoardList = $otherBoards->fetchAll(PDO::FETCH_ASSOC);
$mergeBoards = json_decode($board['merge_boards'] ?? '[]', true) ?: [];

// 회원 등급 목록
$gradeStmt = $pdo->prepare("SELECT slug, name, color FROM {$prefix}member_grades ORDER BY sort_order ASC");
$gradeStmt->execute();
$memberGrades = $gradeStmt->fetchAll(PDO::FETCH_ASSOC);
?>
<div class="space-y-6">

<?php // === 통합 게시판 === ?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700" data-section="merge">
    <button type="button" onclick="toggleSection(this)" class="w-full flex items-center justify-between p-6 cursor-pointer select-none">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200"><?= __('site.boards.add_merge_title') ?></h3>
        <svg class="section-chevron w-5 h-5 text-zinc-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
    </button>
    <div class="section-body px-6 pb-6">
        <form id="addMergeForm">
        <input type="hidden" name="board_id" value="<?= $boardId ?>">
        <input type="hidden" name="action" value="update">
        <div class="mb-4">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.add_merge_select') ?></label>
            <select name="merge_boards[]" multiple size="6" class="w-full <?= $inp ?>">
                <option value="" <?= empty($mergeBoards) ? 'selected' : '' ?>><?= __('site.boards.add_merge_none') ?></option>
                <?php foreach ($otherBoardList as $ob): ?>
                <option value="<?= $ob['id'] ?>" <?= in_array($ob['id'], $mergeBoards) ? 'selected' : '' ?>><?= htmlspecialchars($ob['title']) ?> (<?= $ob['slug'] ?>)</option>
                <?php endforeach; ?>
            </select>
            <p class="<?= $hint ?> mt-1"><?= __('site.boards.add_merge_select_help') ?></p>
            <p class="text-xs text-amber-600 dark:text-amber-400 mt-1"><?= __('site.boards.add_merge_warn') ?></p>
        </div>
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.add_merge_period') ?></label>
                <div class="flex items-center gap-2">
                    <input type="number" name="merge_period" value="<?= (int)($board['merge_period'] ?? 0) ?>" min="0" class="w-24 <?= $inp ?>">
                    <span class="<?= $lbl ?>"><?= __('site.boards.add_merge_period_unit') ?></span>
                </div>
                <p class="<?= $hint ?> mt-1"><?= __('site.boards.add_merge_period_help') ?></p>
            </div>
            <div>
                <label class="flex items-center justify-between mt-6">
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('site.boards.add_merge_notice') ?></span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="merge_notice" value="0">
                        <input type="checkbox" name="merge_notice" value="1" <?= ($board['merge_notice'] ?? 1) ? 'checked' : '' ?> class="sr-only peer">
                        <div class="<?= $tgl ?>"></div>
                    </label>
                </label>
            </div>
        </div>
        <div class="flex justify-end"><button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition"><?= __('admin.buttons.save') ?></button></div>
        </form>
    </div>
</div>

<?php // === 문서 === ?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700" data-section="doc">
    <button type="button" onclick="toggleSection(this)" class="w-full flex items-center justify-between p-6 cursor-pointer select-none">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200"><?= __('site.boards.add_doc_title') ?></h3>
        <svg class="section-chevron w-5 h-5 text-zinc-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
    </button>
    <div class="section-body px-6 pb-6">
        <form id="addDocForm">
        <input type="hidden" name="board_id" value="<?= $boardId ?>">
        <input type="hidden" name="action" value="update">
        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
                <label class="flex items-center justify-between">
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('site.boards.add_doc_history') ?></span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="use_history" value="none">
                        <input type="checkbox" name="use_history" value="use" <?= ($board['use_history'] ?? 'none') === 'use' ? 'checked' : '' ?> class="sr-only peer">
                        <div class="<?= $tgl ?>"></div>
                    </label>
                </label>
                <p class="<?= $hint ?> mt-1"><?= __('site.boards.add_doc_history_help') ?></p>
            </div>
            <div>
                <label class="flex items-center justify-between">
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('site.boards.add_vote') ?></span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="use_vote" value="none">
                        <input type="checkbox" name="use_vote" value="use" <?= ($board['use_vote'] ?? 'use') !== 'none' ? 'checked' : '' ?> class="sr-only peer">
                        <div class="<?= $tgl ?>"></div>
                    </label>
                </label>
            </div>
            <div>
                <label class="flex items-center justify-between">
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('site.boards.add_downvote') ?></span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="hidden" name="use_downvote" value="none">
                        <input type="checkbox" name="use_downvote" value="use" <?= ($board['use_downvote'] ?? 'use') !== 'none' ? 'checked' : '' ?> class="sr-only peer">
                        <div class="<?= $tgl ?>"></div>
                    </label>
                </label>
            </div>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('site.boards.add_vote_settings') ?></label>
            <div class="flex flex-wrap gap-4">
                <label class="flex items-center gap-2 <?= $lbl ?>"><input type="checkbox" name="vote_same_ip" value="1" <?= ($board['vote_same_ip'] ?? 0) ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600"> <?= __('site.boards.add_vote_same_ip') ?></label>
                <label class="flex items-center gap-2 <?= $lbl ?>"><input type="checkbox" name="vote_cancel" value="1" <?= ($board['vote_cancel'] ?? 0) ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600"> <?= __('site.boards.add_vote_cancel') ?></label>
                <label class="flex items-center gap-2 <?= $lbl ?>"><input type="checkbox" name="vote_non_member" value="1" <?= ($board['vote_non_member'] ?? 0) ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600"> <?= __('site.boards.add_vote_non_member') ?></label>
            </div>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('site.boards.add_report_settings') ?></label>
            <div class="flex flex-wrap gap-4">
                <label class="flex items-center gap-2 <?= $lbl ?>"><input type="checkbox" name="report_same_ip" value="1" <?= ($board['report_same_ip'] ?? 0) ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600"> <?= __('site.boards.add_report_same_ip') ?></label>
                <label class="flex items-center gap-2 <?= $lbl ?>"><input type="checkbox" name="report_cancel" value="1" <?= ($board['report_cancel'] ?? 0) ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600"> <?= __('site.boards.add_report_cancel') ?></label>
            </div>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('site.boards.add_report_notify') ?></label>
            <div class="flex gap-4">
                <label class="flex items-center gap-2 <?= $lbl ?>"><input type="checkbox" name="report_notify_super" value="1" <?= str_contains($board['report_notify'] ?? '', 'super') ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600"> <?= __('site.boards.add_report_super_admin') ?></label>
                <label class="flex items-center gap-2 <?= $lbl ?>"><input type="checkbox" name="report_notify_board" value="1" <?= str_contains($board['report_notify'] ?? '', 'board') ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600"> <?= __('site.boards.add_report_board_admin') ?></label>
            </div>
        </div>
        <div class="flex justify-end"><button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition"><?= __('admin.buttons.save') ?></button></div>
        </form>
    </div>
</div>

<?php include __DIR__ . '/boards-edit-addition-part2.php'; ?>

</div>

<?php include __DIR__ . '/boards-edit-addition-js.php'; ?>
