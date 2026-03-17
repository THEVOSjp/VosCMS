<?php
/**
 * RezlyX 게시판 - 글 작성/수정
 */
include __DIR__ . '/_init.php';

if (!boardCheckPerm($board, 'perm_write', $currentUser)) {
    http_response_code(403);
    echo '<p>글쓰기 권한이 없습니다.</p>';
    exit;
}

// 수정 모드
$editMode = isset($postId) && $postId > 0;
$post = null;

if ($editMode) {
    $postStmt = $pdo->prepare("SELECT * FROM {$prefix}board_posts WHERE id = ? AND board_id = ?");
    $postStmt->execute([$postId, $boardId]);
    $post = $postStmt->fetch(PDO::FETCH_ASSOC);
    if (!$post) { http_response_code(404); include BASE_PATH . '/resources/views/customer/404.php'; exit; }

    // 수정 권한 확인
    if (!$currentUser || ($currentUser['id'] != $post['user_id'] && ($currentUser['role'] ?? '') !== 'admin')) {
        http_response_code(403); echo '<p>수정 권한이 없습니다.</p>'; exit;
    }
}

$pageTitle = ($editMode ? __('board.edit_post') : __('board.write_post')) . ' - ' . $board['title'];

// 카테고리 맵
$catMap = [];
foreach ($categories as $cat) $catMap[$cat['id']] = $cat;

// 기존 첨부파일 (수정 시)
$existingFiles = [];
if ($editMode) {
    $fileStmt = $pdo->prepare("SELECT * FROM {$prefix}board_files WHERE post_id = ?");
    $fileStmt->execute([$postId]);
    $existingFiles = $fileStmt->fetchAll(PDO::FETCH_ASSOC);
}

include __DIR__ . '/_header.php';
?>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-6">
        <h1 class="text-2xl font-bold text-zinc-800 dark:text-zinc-100 mb-6"><?= $editMode ? __('board.edit_post') : __('board.write_post') ?></h1>

        <form id="writeForm" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="board_id" value="<?= $boardId ?>">
            <?php if ($editMode): ?><input type="hidden" name="post_id" value="<?= $postId ?>"><?php endif; ?>
            <input type="hidden" name="action" value="<?= $editMode ? 'update' : 'create' ?>">

            <!-- 비회원 정보 -->
            <?php if (!$currentUser): ?>
            <div class="flex gap-3">
                <div class="flex-1">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('board.nickname') ?> <span class="text-red-500">*</span></label>
                    <input type="text" name="nick_name" required value="<?= htmlspecialchars($post['nick_name'] ?? '') ?>"
                           class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200">
                </div>
                <div class="flex-1">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('board.password') ?> <?= $editMode ? '' : '<span class="text-red-500">*</span>' ?></label>
                    <input type="password" name="password" <?= $editMode ? '' : 'required' ?>
                           class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200">
                </div>
            </div>
            <?php endif; ?>

            <!-- 카테고리 -->
            <?php if (($board['show_category'] ?? 0) && !empty($categories)): ?>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('board.category') ?></label>
                <select name="category_id" class="px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200">
                    <option value=""><?= __('board.no_category') ?></option>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= ($post['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($cat['name']) ?></option>
                    <?php endforeach; ?>
                </select>
            </div>
            <?php endif; ?>

            <!-- 제목 -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('board.title') ?> <span class="text-red-500">*</span></label>
                <input type="text" name="title" required value="<?= htmlspecialchars($post['title'] ?? '') ?>"
                       class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200"
                       placeholder="<?= __('board.title_placeholder') ?>">
            </div>

            <!-- 옵션 (관리자용) -->
            <?php if ($currentUser && ($currentUser['role'] ?? '') === 'admin'): ?>
            <div class="flex gap-4">
                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                    <input type="checkbox" name="is_notice" value="1" <?= ($post['is_notice'] ?? 0) ? 'checked' : '' ?> class="rounded border-zinc-300">
                    <?= __('board.set_notice') ?>
                </label>
                <?php if ($board['allow_secret'] ?? 0): ?>
                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                    <input type="checkbox" name="is_secret" value="1" <?= ($post['is_secret'] ?? 0) ? 'checked' : '' ?> class="rounded border-zinc-300">
                    <?= __('board.set_secret') ?>
                </label>
                <?php endif; ?>
            </div>
            <?php elseif ($board['allow_secret'] ?? 0): ?>
            <div>
                <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300">
                    <input type="checkbox" name="is_secret" value="1" <?= ($post['is_secret'] ?? 0) ? 'checked' : '' ?> class="rounded border-zinc-300">
                    <?= __('board.set_secret') ?>
                </label>
            </div>
            <?php endif; ?>

            <!-- 본문 -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('board.content') ?> <span class="text-red-500">*</span></label>
                <textarea name="content" rows="15" required
                          class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200 font-mono"
                          placeholder="<?= __('board.content_placeholder') ?>"><?= htmlspecialchars($post['content'] ?? '') ?></textarea>
                <?php if (($board['doc_length_limit'] ?? 0) > 0): ?>
                <p class="mt-1 text-xs text-zinc-500"><?= __('board.char_limit', ['limit' => number_format($board['doc_length_limit'])]) ?></p>
                <?php endif; ?>
            </div>

            <!-- 파일 첨부 -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('board.file_attach') ?></label>
                <?php if (!empty($existingFiles)): ?>
                <div class="mb-2 space-y-1" id="existingFiles">
                    <?php foreach ($existingFiles as $file): ?>
                    <div class="flex items-center gap-2 text-sm" data-file-id="<?= $file['id'] ?>">
                        <span class="text-zinc-600 dark:text-zinc-400"><?= htmlspecialchars($file['original_name']) ?> (<?= number_format($file['file_size'] / 1024, 1) ?>KB)</span>
                        <button type="button" class="btn-remove-file text-red-500 hover:underline text-xs" data-id="<?= $file['id'] ?>"><?= __('board.remove') ?></button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <input type="file" name="files[]" multiple class="text-sm text-zinc-600 dark:text-zinc-400 file:mr-3 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-zinc-100 dark:file:bg-zinc-700 file:text-zinc-700 dark:file:text-zinc-300 hover:file:bg-zinc-200">
            </div>

            <!-- 버튼 -->
            <div class="flex items-center justify-end gap-3 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                <a href="<?= $editMode ? $boardUrl . '/' . $postId : $boardUrl ?>"
                   class="px-6 py-2.5 text-sm font-medium text-zinc-600 dark:text-zinc-300 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">
                    <?= __('board.cancel') ?>
                </a>
                <button type="submit" class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition">
                    <?= $editMode ? __('board.update') : __('board.submit') ?>
                </button>
            </div>
        </form>
    </div>

<script>
console.log('[BoardWrite] <?= $editMode ? '글 수정' : '글 작성' ?> 페이지 로드');
const boardApiUrl = '<?= ($config['app_url'] ?? '') ?>/board/api';

document.getElementById('writeForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    console.log('[BoardWrite] 폼 전송');
    const form = new FormData(e.target);

    try {
        const resp = await fetch(boardApiUrl + '/posts', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: form
        });
        const data = await resp.json();
        console.log('[BoardWrite] 응답:', data);

        if (data.success) {
            location.href = '<?= $boardUrl ?>/' + (data.post_id || '<?= $postId ?? '' ?>');
        } else {
            alert(data.message || 'Error');
        }
    } catch (err) {
        console.error('[BoardWrite] 에러:', err);
        alert('Error: ' + err.message);
    }
});

// 기존 파일 삭제
document.querySelectorAll('.btn-remove-file').forEach(btn => {
    btn.addEventListener('click', async () => {
        const fid = btn.dataset.id;
        console.log('[BoardWrite] 파일 삭제:', fid);
        try {
            const resp = await fetch(boardApiUrl + '/files?action=delete&id=' + fid, {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await resp.json();
            if (data.success) btn.closest('[data-file-id]').remove();
            else alert(data.message || 'Error');
        } catch (err) { console.error(err); }
    });
});
</script>
<?php include __DIR__ . '/_footer.php'; ?>
