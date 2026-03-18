<?php
/**
 * 추가 설정 파트2: 댓글, 에디터, 파일, 피드
 * boards-edit-addition.php에서 include됨
 */
?>

<?php // === 댓글 === ?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700" data-section="comment">
    <button type="button" onclick="toggleSection(this)" class="w-full flex items-center justify-between p-6 cursor-pointer select-none">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200"><?= __('site.boards.add_comment_title') ?></h3>
        <svg class="section-chevron w-5 h-5 text-zinc-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
    </button>
    <div class="section-body px-6 pb-6">
        <form id="addCommentForm">
        <input type="hidden" name="board_id" value="<?= $boardId ?>">
        <input type="hidden" name="action" value="update">
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.add_comment_count') ?></label>
                <div class="flex items-center gap-2">
                    <input type="number" name="comment_count" value="<?= (int)($board['comment_count'] ?? 50) ?>" min="1" class="w-24 <?= $inp ?>">
                    <span class="<?= $hint ?>"><?= __('site.boards.add_comment_count_help') ?></span>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.add_comment_page_count') ?></label>
                <div class="flex items-center gap-2">
                    <input type="number" name="comment_page_count" value="<?= (int)($board['comment_page_count'] ?? 10) ?>" min="1" class="w-24 <?= $inp ?>">
                    <span class="<?= $hint ?>"><?= __('site.boards.add_comment_page_count_help') ?></span>
                </div>
            </div>
        </div>
        <div class="grid grid-cols-2 gap-4 mb-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.add_comment_max_depth') ?></label>
                <div class="flex items-center gap-2">
                    <input type="number" name="comment_max_depth" value="<?= (int)($board['comment_max_depth'] ?? 0) ?>" min="0" class="w-24 <?= $inp ?>">
                    <span class="<?= $hint ?>"><?= __('site.boards.add_comment_max_depth_help') ?></span>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.add_comment_default_page') ?></label>
                <select name="comment_default_page" class="<?= $inp ?>">
                    <option value="last" <?= ($board['comment_default_page'] ?? 'last') === 'last' ? 'selected' : '' ?>><?= __('site.boards.add_comment_page_last') ?></option>
                    <option value="first" <?= ($board['comment_default_page'] ?? '') === 'first' ? 'selected' : '' ?>><?= __('site.boards.add_comment_page_first') ?></option>
                </select>
            </div>
        </div>
        <div class="mb-4">
            <label class="flex items-center justify-between mb-1">
                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('site.boards.add_comment_approval') ?></span>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="comment_approval" value="0">
                    <input type="checkbox" name="comment_approval" value="1" <?= ($board['comment_approval'] ?? 0) ? 'checked' : '' ?> class="sr-only peer">
                    <div class="w-9 h-5 bg-zinc-300 dark:bg-zinc-600 peer-checked:bg-blue-600 rounded-full peer after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-full"></div>
                </label>
            </label>
            <p class="<?= $hint ?>"><?= __('site.boards.add_comment_approval_help') ?></p>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <label class="flex items-center justify-between">
                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('site.boards.add_vote') ?></span>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="comment_vote" value="none">
                    <input type="checkbox" name="comment_vote" value="use" <?= ($board['comment_vote'] ?? 'use') !== 'none' ? 'checked' : '' ?> class="sr-only peer">
                    <div class="w-9 h-5 bg-zinc-300 dark:bg-zinc-600 peer-checked:bg-blue-600 rounded-full peer after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-full"></div>
                </label>
            </label>
            <label class="flex items-center justify-between">
                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('site.boards.add_downvote') ?></span>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="comment_downvote" value="none">
                    <input type="checkbox" name="comment_downvote" value="use" <?= ($board['comment_downvote'] ?? 'use') !== 'none' ? 'checked' : '' ?> class="sr-only peer">
                    <div class="w-9 h-5 bg-zinc-300 dark:bg-zinc-600 peer-checked:bg-blue-600 rounded-full peer after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-full"></div>
                </label>
            </label>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('site.boards.add_vote_settings') ?></label>
            <div class="flex flex-wrap gap-4">
                <label class="flex items-center gap-2 <?= $lbl ?>"><input type="checkbox" name="comment_vote_same_ip" value="1" <?= ($board['comment_vote_same_ip'] ?? 0) ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600"> <?= __('site.boards.add_vote_same_ip') ?></label>
                <label class="flex items-center gap-2 <?= $lbl ?>"><input type="checkbox" name="comment_vote_cancel" value="1" <?= ($board['comment_vote_cancel'] ?? 0) ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600"> <?= __('site.boards.add_vote_cancel') ?></label>
                <label class="flex items-center gap-2 <?= $lbl ?>"><input type="checkbox" name="comment_vote_non_member" value="1" <?= ($board['comment_vote_non_member'] ?? 0) ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600"> <?= __('site.boards.add_vote_non_member') ?></label>
            </div>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('site.boards.add_report_settings') ?></label>
            <div class="flex flex-wrap gap-4">
                <label class="flex items-center gap-2 <?= $lbl ?>"><input type="checkbox" name="comment_report_same_ip" value="1" <?= ($board['comment_report_same_ip'] ?? 0) ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600"> <?= __('site.boards.add_report_same_ip') ?></label>
                <label class="flex items-center gap-2 <?= $lbl ?>"><input type="checkbox" name="comment_report_cancel" value="1" <?= ($board['comment_report_cancel'] ?? 0) ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600"> <?= __('site.boards.add_report_cancel') ?></label>
            </div>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('site.boards.add_report_notify') ?></label>
            <div class="flex gap-4">
                <label class="flex items-center gap-2 <?= $lbl ?>"><input type="checkbox" name="comment_report_notify_super" value="1" <?= str_contains($board['comment_report_notify'] ?? '', 'super') ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600"> <?= __('site.boards.add_report_super_admin') ?></label>
                <label class="flex items-center gap-2 <?= $lbl ?>"><input type="checkbox" name="comment_report_notify_board" value="1" <?= str_contains($board['comment_report_notify'] ?? '', 'board') ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600"> <?= __('site.boards.add_report_board_admin') ?></label>
            </div>
        </div>
        <div class="flex justify-end"><button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition"><?= __('admin.buttons.save') ?></button></div>
        </form>
    </div>
</div>

<?php // === 위지윅 에디터 === ?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700" data-section="editor">
    <button type="button" onclick="toggleSection(this)" class="w-full flex items-center justify-between p-6 cursor-pointer select-none">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200"><?= __('site.boards.add_editor_title') ?></h3>
        <svg class="section-chevron w-5 h-5 text-zinc-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
    </button>
    <div class="section-body px-6 pb-6">
        <form id="addEditorForm">
        <input type="hidden" name="board_id" value="<?= $boardId ?>">
        <input type="hidden" name="action" value="update">
        <div class="mb-4">
            <label class="flex items-center justify-between mb-1">
                <span class="<?= $lbl ?>"><?= __('site.boards.add_editor_use_default') ?></span>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="editor_use_default" value="0">
                    <input type="checkbox" name="editor_use_default" value="1" <?= ($board['editor_use_default'] ?? 1) ? 'checked' : '' ?> class="sr-only peer">
                    <div class="w-9 h-5 bg-zinc-300 dark:bg-zinc-600 peer-checked:bg-blue-600 rounded-full peer after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-full"></div>
                </label>
            </label>
            <p class="<?= $hint ?>"><?= __('site.boards.add_editor_use_default_help') ?></p>
        </div>
        <?php
        $editorPerms = [
            'editor_html_perm' => __('site.boards.add_editor_html_perm'),
            'editor_file_perm' => __('site.boards.add_editor_file_perm'),
            'editor_component_perm' => __('site.boards.add_editor_component_perm'),
            'editor_ext_component_perm' => __('site.boards.add_editor_ext_component_perm'),
        ];
        ?>
        <table class="w-full text-sm mb-4">
            <thead><tr>
                <th class="text-left py-2 font-medium text-zinc-700 dark:text-zinc-300"></th>
                <th class="text-center py-2 font-medium text-zinc-700 dark:text-zinc-300"><?= __('site.boards.add_editor_col_doc') ?></th>
                <th class="text-center py-2 font-medium text-zinc-700 dark:text-zinc-300"><?= __('site.boards.add_editor_col_comment') ?></th>
            </tr></thead>
            <tbody>
            <?php foreach ($editorPerms as $field => $label):
                $docVal = $board[$field] ?? '';
                $comField = 'comment_' . $field;
                $comVal = $board[$comField] ?? '';
            ?>
            <tr class="border-t border-zinc-100 dark:border-zinc-700">
                <td class="py-2 font-medium text-zinc-700 dark:text-zinc-300"><?= $label ?></td>
                <td class="py-2 text-center">
                    <div class="flex justify-center gap-2">
                    <?php foreach ($memberGrades as $g): ?>
                        <label class="flex items-center gap-1 text-xs <?= $lbl ?>">
                            <input type="checkbox" name="<?= $field ?>[]" value="<?= $g['slug'] ?>" <?= str_contains($docVal, $g['slug']) ? 'checked' : '' ?> class="w-3.5 h-3.5 text-blue-600 rounded border-zinc-300">
                            <span <?= $g['color'] ? 'style="color:' . htmlspecialchars($g['color']) . '"' : '' ?>><?= htmlspecialchars($g['name']) ?></span>
                        </label>
                    <?php endforeach; ?>
                    </div>
                </td>
                <td class="py-2 text-center">
                    <div class="flex justify-center gap-2">
                    <?php foreach ($memberGrades as $g): ?>
                        <label class="flex items-center gap-1 text-xs <?= $lbl ?>">
                            <input type="checkbox" name="<?= $comField ?>[]" value="<?= $g['slug'] ?>" <?= str_contains($comVal, $g['slug']) ? 'checked' : '' ?> class="w-3.5 h-3.5 text-blue-600 rounded border-zinc-300">
                            <span <?= $g['color'] ? 'style="color:' . htmlspecialchars($g['color']) . '"' : '' ?>><?= htmlspecialchars($g['name']) ?></span>
                        </label>
                    <?php endforeach; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <div class="flex justify-end"><button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition"><?= __('admin.buttons.save') ?></button></div>
        </form>
    </div>
</div>

<?php // === 파일 === ?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700" data-section="file">
    <button type="button" onclick="toggleSection(this)" class="w-full flex items-center justify-between p-6 cursor-pointer select-none">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200"><?= __('site.boards.add_file_title') ?></h3>
        <svg class="section-chevron w-5 h-5 text-zinc-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
    </button>
    <div class="section-body px-6 pb-6">
        <form id="addFileForm">
        <input type="hidden" name="board_id" value="<?= $boardId ?>">
        <input type="hidden" name="action" value="update">
        <div class="space-y-3 mb-4">
            <label class="flex items-center justify-between">
                <span class="<?= $lbl ?>"><?= __('site.boards.add_file_use_default') ?></span>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="file_use_default" value="0">
                    <input type="checkbox" name="file_use_default" value="1" <?= ($board['file_use_default'] ?? 1) ? 'checked' : '' ?> class="sr-only peer">
                    <div class="w-9 h-5 bg-zinc-300 dark:bg-zinc-600 peer-checked:bg-blue-600 rounded-full peer after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-full"></div>
                </label>
            </label>
            <label class="flex items-center justify-between">
                <span class="<?= $lbl ?>"><?= __('site.boards.add_file_image_default') ?></span>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="file_image_default" value="0">
                    <input type="checkbox" name="file_image_default" value="1" <?= ($board['file_image_default'] ?? 1) ? 'checked' : '' ?> class="sr-only peer">
                    <div class="w-9 h-5 bg-zinc-300 dark:bg-zinc-600 peer-checked:bg-blue-600 rounded-full peer after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-full"></div>
                </label>
            </label>
            <label class="flex items-center justify-between">
                <span class="<?= $lbl ?>"><?= __('site.boards.add_file_video_default') ?></span>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="file_video_default" value="0">
                    <input type="checkbox" name="file_video_default" value="1" <?= ($board['file_video_default'] ?? 1) ? 'checked' : '' ?> class="sr-only peer">
                    <div class="w-9 h-5 bg-zinc-300 dark:bg-zinc-600 peer-checked:bg-blue-600 rounded-full peer after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-full"></div>
                </label>
            </label>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('site.boards.add_file_download_groups') ?></label>
            <div class="flex flex-col gap-1 ml-2">
                <?php foreach ($memberGrades as $g): ?>
                <label class="flex items-center gap-2 <?= $lbl ?>">
                    <input type="checkbox" name="file_download_groups[]" value="<?= $g['slug'] ?>" <?= str_contains($board['file_download_groups'] ?? '', $g['slug']) ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 rounded border-zinc-300 dark:border-zinc-600">
                    <span <?= $g['color'] ? 'style="color:' . htmlspecialchars($g['color']) . '"' : '' ?>><?= htmlspecialchars($g['name']) ?></span>
                </label>
                <?php endforeach; ?>
            </div>
        </div>
        <div class="flex justify-end"><button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition"><?= __('admin.buttons.save') ?></button></div>
        </form>
    </div>
</div>

<?php // === 피드(Feed) 공개 === ?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700" data-section="feed">
    <button type="button" onclick="toggleSection(this)" class="w-full flex items-center justify-between p-6 cursor-pointer select-none">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200"><?= __('site.boards.add_feed_title') ?></h3>
        <svg class="section-chevron w-5 h-5 text-zinc-400 transition-transform duration-200" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 15l7-7 7 7"/></svg>
    </button>
    <div class="section-body px-6 pb-6">
        <form id="addFeedForm">
        <input type="hidden" name="board_id" value="<?= $boardId ?>">
        <input type="hidden" name="action" value="update">
        <p class="<?= $hint ?> mb-4"><?= __('site.boards.add_feed_help') ?></p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <label class="flex items-center justify-between">
                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('site.boards.add_feed_type') ?></span>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="feed_type" value="none">
                    <input type="checkbox" name="feed_type" value="public" <?= ($board['feed_type'] ?? 'none') === 'public' ? 'checked' : '' ?> class="sr-only peer">
                    <div class="w-9 h-5 bg-zinc-300 dark:bg-zinc-600 peer-checked:bg-blue-600 rounded-full peer after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-full"></div>
                </label>
            </label>
            <label class="flex items-center justify-between">
                <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('site.boards.add_feed_include_merged') ?></span>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="hidden" name="feed_include_merged" value="0">
                    <input type="checkbox" name="feed_include_merged" value="1" <?= ($board['feed_include_merged'] ?? 1) ? 'checked' : '' ?> class="sr-only peer">
                    <div class="w-9 h-5 bg-zinc-300 dark:bg-zinc-600 peer-checked:bg-blue-600 rounded-full peer after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:after:translate-x-full"></div>
                </label>
            </label>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.add_feed_description') ?></label>
            <div class="relative">
                <textarea name="feed_description" id="feed_description" rows="3" class="w-full <?= $inp ?> pr-8"><?= htmlspecialchars($board['feed_description'] ?? '') ?></textarea>
                <button type="button" onclick="openMultilangModal('board.<?= $boardId ?>.feed_description', 'feed_description', 'editor')" class="absolute right-2 top-2 text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition"><?= RZX_MULTILANG_SVG ?></button>
            </div>
            <p class="<?= $hint ?> mt-1"><?= __('site.boards.add_feed_description_help') ?></p>
        </div>
        <div class="mb-4">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.boards.add_feed_copyright') ?></label>
            <div class="relative">
                <textarea name="feed_copyright" id="feed_copyright" rows="3" class="w-full <?= $inp ?> pr-8"><?= htmlspecialchars($board['feed_copyright'] ?? '') ?></textarea>
                <button type="button" onclick="openMultilangModal('board.<?= $boardId ?>.feed_copyright', 'feed_copyright', 'editor')" class="absolute right-2 top-2 text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition"><?= RZX_MULTILANG_SVG ?></button>
            </div>
            <p class="<?= $hint ?> mt-1"><?= __('site.boards.add_feed_copyright_help') ?></p>
        </div>
        <div class="flex justify-end"><button type="submit" class="px-6 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition"><?= __('admin.buttons.save') ?></button></div>
        </form>
    </div>
</div>
