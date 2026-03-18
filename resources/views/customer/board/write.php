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
    if (!$currentUser || ($currentUser['id'] != $post['user_id'] && !$isAdmin)) {
        http_response_code(403); echo '<p>수정 권한이 없습니다.</p>'; exit;
    }
}

$pageTitle = ($editMode ? __('board.edit_post') : __('board.write_post')) . ' - ' . $board['title'];

// catMap은 _init.php에서 이미 로드됨

// 기존 첨부파일 (수정 시)
$existingFiles = [];
if ($editMode) {
    $fileStmt = $pdo->prepare("SELECT * FROM {$prefix}board_files WHERE post_id = ?");
    $fileStmt->execute([$postId]);
    $existingFiles = $fileStmt->fetchAll(PDO::FETCH_ASSOC);
}

?>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-6">
        <h1 class="text-2xl font-bold text-zinc-800 dark:text-zinc-100 mb-4"><?= htmlspecialchars($board['title']) ?></h1>

        <?php if ($editMode && ($post['source_locale'] ?? '') !== ($currentLocale ?? 'ko')): ?>
        <div class="px-4 py-2 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg flex items-center gap-2 text-sm text-amber-700 dark:text-amber-300 mb-4">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
            <?= __('board.edit_locale_notice', ['from' => $post['source_locale'] ?? '?', 'to' => $currentLocale]) ?>
        </div>
        <?php endif; ?>

        <form id="writeForm" enctype="multipart/form-data" class="space-y-4">
            <input type="hidden" name="board_id" value="<?= $boardId ?>">
            <?php if ($editMode): ?><input type="hidden" name="post_id" value="<?= $postId ?>"><?php endif; ?>
            <input type="hidden" name="action" value="<?= $editMode ? 'update' : 'create' ?>">
            <input type="hidden" name="locale" value="<?= $currentLocale ?? 'ko' ?>">

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

            <!-- 분류 + 제목 (한줄) -->
            <div class="flex gap-3">
                <?php if (!($board['hide_categories'] ?? 0) && !empty($categories)): ?>
                <div class="w-40 shrink-0">
                    <select name="category_id" <?= !($board['allow_uncategorized'] ?? 1) ? 'required' : '' ?>
                            class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200">
                    <?php if ($board['allow_uncategorized'] ?? 1): ?>
                    <option value=""><?= __('board.no_category') ?></option>
                    <?php endif; ?>
                    <?php foreach ($categories as $cat): ?>
                    <option value="<?= $cat['id'] ?>" <?= ($post['category_id'] ?? '') == $cat['id'] ? 'selected' : '' ?>
                            <?= !empty($cat['font_color']) ? 'style="color:' . htmlspecialchars($cat['font_color']) . '"' : '' ?>>
                        <?= htmlspecialchars($cat['name']) ?>
                    </option>
                    <?php endforeach; ?>
                    </select>
                </div>
                <?php endif; ?>
                <div class="flex-1">
                    <input type="text" name="title" required value="<?= htmlspecialchars($post['title'] ?? '') ?>"
                           class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200"
                           placeholder="<?= __('board.title_placeholder') ?>">
                </div>
            </div>

            <!-- 옵션 (관리자용) -->
            <?php if ($currentUser && $isAdmin): ?>
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

            <!-- 본문 (Summernote 에디터) -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('board.content') ?> <span class="text-red-500">*</span></label>
                <textarea name="content" id="boardContent"><?= htmlspecialchars($post['content'] ?? '') ?></textarea>
                <?php if (($board['doc_length_limit'] ?? 0) > 0): ?>
                <p class="mt-1 text-xs text-zinc-500"><?= __('board.char_limit', ['limit' => number_format($board['doc_length_limit'])]) ?></p>
                <?php endif; ?>
            </div>

            <!-- 파일 첨부 (드래그&드롭) -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('board.file_attach') ?></label>
                <?php if (!empty($existingFiles)): ?>
                <div class="mb-3 grid grid-cols-2 md:grid-cols-4 gap-2" id="existingFiles">
                    <?php foreach ($existingFiles as $file):
                        $isImage = str_starts_with($file['mime_type'] ?? '', 'image/');
                        $fileUrl = ($config['app_url'] ?? '') . '/' . ($file['file_path'] ?? '');
                    ?>
                    <div class="relative group border border-zinc-200 dark:border-zinc-600 rounded-lg overflow-hidden" data-file-id="<?= $file['id'] ?>">
                        <?php if ($isImage): ?>
                        <img src="<?= htmlspecialchars($fileUrl) ?>" class="w-full h-24 object-cover">
                        <?php else: ?>
                        <div class="w-full h-24 bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center">
                            <svg class="w-8 h-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <?php endif; ?>
                        <div class="px-2 py-1 text-xs text-zinc-600 dark:text-zinc-400 truncate"><?= htmlspecialchars($file['original_name']) ?></div>
                        <button type="button" class="btn-remove-file absolute top-1 right-1 w-5 h-5 bg-red-500 text-white rounded-full text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition" data-id="<?= $file['id'] ?>">&times;</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
                <div id="dropZone" class="border-2 border-dashed border-zinc-300 dark:border-zinc-600 rounded-lg p-6 text-center cursor-pointer hover:border-blue-400 dark:hover:border-blue-500 transition">
                    <svg class="w-8 h-8 mx-auto mb-2 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M7 16a4 4 0 01-.88-7.903A5 5 0 1115.9 6L16 6a5 5 0 011 9.9M15 13l-3-3m0 0l-3 3m3-3v12"/></svg>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('board.drop_files') ?></p>
                    <p class="text-xs text-zinc-400 mt-1"><?= __('board.drop_files_hint') ?></p>
                    <input type="file" name="files[]" id="fileInput" multiple class="hidden">
                </div>
                <div id="fileList" class="mt-3 grid grid-cols-2 md:grid-cols-4 gap-2"></div>
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

<!-- Summernote -->
<script src="https://cdn.jsdelivr.net/npm/jquery@3.7.1/dist/jquery.min.js"></script>
<link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
<?php if (($config['locale'] ?? 'ko') === 'ko'): ?>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/lang/summernote-ko-KR.min.js"></script>
<?php elseif (($config['locale'] ?? '') === 'ja'): ?>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/lang/summernote-ja-JP.min.js"></script>
<?php endif; ?>
<style>
.note-editor { border-radius: 0.5rem; overflow: hidden; border-color: #d4d4d8 !important; }
.note-editor .note-toolbar { background: #f4f4f5; border-color: #d4d4d8; }
.note-editor .note-editing-area { background: #fff; }
.note-editor .note-editable { min-height: 300px; font-size: 14px; line-height: 1.8; padding: 16px; }
.note-editor .note-statusbar { background: #f4f4f5; border-color: #d4d4d8; }
.dark .note-editor { border-color: #52525b !important; }
.dark .note-editor .note-toolbar { background: #3f3f46; border-color: #52525b; }
.dark .note-editor .note-toolbar .note-btn { color: #a1a1aa; background: transparent; border-color: #52525b; }
.dark .note-editor .note-toolbar .note-btn:hover { color: #fff; background: #52525b; }
.dark .note-editor .note-editing-area { background: #3f3f46; }
.dark .note-editor .note-editable { color: #fff; background: #3f3f46; }
.dark .note-editor .note-statusbar { background: #3f3f46; border-color: #52525b; }
.dark .note-dropdown-menu { background: #3f3f46; border-color: #52525b; }
.dark .note-dropdown-menu .note-dropdown-item { color: #a1a1aa; }
.dark .note-dropdown-menu .note-dropdown-item:hover { background: #52525b; color: #fff; }
#dropZone.drag-over { border-color: #3b82f6; background: rgba(59,130,246,0.05); }
.dark #dropZone.drag-over { background: rgba(59,130,246,0.1); }
</style>

<script>
console.log('[BoardWrite] <?= $editMode ? '글 수정' : '글 작성' ?> 페이지 로드');
const boardApiUrl = '<?= ($config['app_url'] ?? '') ?>/board/api';
let pendingFiles = [];

// Summernote 초기화
$(document).ready(function() {
    $('#boardContent').summernote({
        height: 350,
        placeholder: '<?= __('board.content_placeholder') ?>',
        lang: '<?= ($config['locale'] ?? 'ko') === 'ko' ? 'ko-KR' : 'en-US' ?>',
        toolbar: [
            ['style', ['style']],
            ['font', ['bold', 'italic', 'underline', 'strikethrough', 'clear']],
            ['fontsize', ['fontsize']],
            ['color', ['color']],
            ['para', ['ul', 'ol', 'paragraph']],
            ['table', ['table']],
            ['insert', ['link', 'picture', 'video', 'hr']],
            ['view', ['codeview', 'fullscreen', 'help']]
        ],
        callbacks: {
            onInit: function() { console.log('[BoardWrite] Summernote initialized'); },
            onImageUpload: function(files) {
                for (const file of files) uploadImage(file);
            }
        }
    });
});

// 에디터 내 이미지 업로드
async function uploadImage(file) {
    console.log('[BoardWrite] 이미지 업로드 시작:', file.name, file.type, file.size);
    const fd = new FormData();
    fd.append('file', file);
    fd.append('board_id', '<?= $boardId ?>');
    fd.append('action', 'upload_image');
    try {
        const resp = await fetch(boardApiUrl + '/files', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: fd
        });
        const text = await resp.text();
        console.log('[BoardWrite] 이미지 업로드 응답:', text);
        const data = JSON.parse(text);
        if (data.success && data.url) {
            $('#boardContent').summernote('insertImage', data.url);
            console.log('[BoardWrite] 이미지 삽입 완료:', data.url);
        } else {
            console.error('[BoardWrite] 이미지 업로드 실패:', data.message);
            alert(data.message || '이미지 업로드 실패');
        }
    } catch (err) { console.error('[BoardWrite] 이미지 업로드 에러:', err); }
}

// 드래그&드롭 파일 첨부
const dropZone = document.getElementById('dropZone');
const fileInput = document.getElementById('fileInput');
const fileList = document.getElementById('fileList');

dropZone.addEventListener('click', () => fileInput.click());
dropZone.addEventListener('dragover', (e) => { e.preventDefault(); dropZone.classList.add('drag-over'); });
dropZone.addEventListener('dragleave', () => dropZone.classList.remove('drag-over'));
dropZone.addEventListener('drop', (e) => {
    e.preventDefault();
    dropZone.classList.remove('drag-over');
    addFiles(e.dataTransfer.files);
});
fileInput.addEventListener('change', (e) => { addFiles(e.target.files); e.target.value = ''; });

function addFiles(files) {
    for (const file of files) {
        pendingFiles.push(file);
        const idx = pendingFiles.length - 1;
        const isImage = file.type.startsWith('image/');
        const div = document.createElement('div');
        div.className = 'relative group border border-zinc-200 dark:border-zinc-600 rounded-lg overflow-hidden';
        div.id = 'pending-file-' + idx;

        if (isImage) {
            const reader = new FileReader();
            reader.onload = (e) => {
                div.innerHTML = `<img src="${e.target.result}" class="w-full h-24 object-cover">
                    <div class="px-2 py-1 text-xs text-zinc-600 dark:text-zinc-400 truncate">${file.name}</div>
                    <button type="button" onclick="removeFile(${idx})" class="absolute top-1 right-1 w-5 h-5 bg-red-500 text-white rounded-full text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition">&times;</button>`;
            };
            reader.readAsDataURL(file);
        } else {
            div.innerHTML = `<div class="w-full h-24 bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center">
                    <svg class="w-8 h-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                </div>
                <div class="px-2 py-1 text-xs text-zinc-600 dark:text-zinc-400 truncate">${file.name}</div>
                <button type="button" onclick="removeFile(${idx})" class="absolute top-1 right-1 w-5 h-5 bg-red-500 text-white rounded-full text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition">&times;</button>`;
        }
        fileList.appendChild(div);
        console.log('[BoardWrite] 파일 추가:', file.name, isImage ? '(이미지)' : '');
    }
}

function removeFile(idx) {
    pendingFiles[idx] = null;
    const el = document.getElementById('pending-file-' + idx);
    if (el) el.remove();
}

// 폼 전송
document.getElementById('writeForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    console.log('[BoardWrite] 폼 전송');
    const form = new FormData(e.target);

    // Summernote 내용 가져오기
    form.set('content', $('#boardContent').summernote('code'));

    // 드래그&드롭 파일 추가
    form.delete('files[]');
    pendingFiles.forEach(f => { if (f) form.append('files[]', f); });

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
        try {
            const resp = await fetch(boardApiUrl + '/files?action=delete&id=' + fid, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await resp.json();
            if (data.success) btn.closest('[data-file-id]').remove();
            else alert(data.message || 'Error');
        } catch (err) { console.error(err); }
    });
});
</script>
