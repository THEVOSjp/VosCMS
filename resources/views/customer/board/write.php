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

    // 현재 로케일이 원본 언어가 아니면 번역 데이터 로드
    $postOriginalLocale = $post['original_locale'] ?? 'ko';
    if ($currentLocale !== $postOriginalLocale) {
        $trStmt = $pdo->prepare("SELECT content FROM {$prefix}translations WHERE lang_key = ? AND locale = ?");
        $trStmt->execute(["board_post.{$postId}.title", $currentLocale]);
        $trTitle = $trStmt->fetchColumn();
        $trStmt->execute(["board_post.{$postId}.content", $currentLocale]);
        $trContent = $trStmt->fetchColumn();
        if ($trTitle !== false) $post['title'] = $trTitle;
        if ($trContent !== false) $post['content'] = $trContent;

        // 확장 변수 번역 로드
        $trStmt->execute(["board_post.{$postId}.extra_vars", $currentLocale]);
        $trEv = $trStmt->fetchColumn();
        if ($trEv !== false) {
            $trEvData = json_decode($trEv, true);
            if ($trEvData) {
                $origEv = !empty($post['extra_vars']) ? (json_decode($post['extra_vars'], true) ?: []) : [];
                $post['extra_vars'] = json_encode(array_merge($origEv, $trEvData), JSON_UNESCAPED_UNICODE);
            }
        }
    }
}

?>
    <?php $_skinContentWidth = $skinConfig['content_width'] ?? 'max-w-7xl'; ?>
    <div class="<?= $_skinContentWidth ?> mx-auto px-4 sm:px-6 py-6">
        <h1 class="text-2xl font-bold text-zinc-800 dark:text-zinc-100 mb-4"><?= htmlspecialchars($board['title']) ?></h1>

        <?php if ($editMode && ($post['original_locale'] ?? 'ko') !== ($currentLocale ?? 'ko')): ?>
        <div class="px-4 py-2 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg flex items-center gap-2 text-sm text-blue-700 dark:text-blue-300 mb-4">
            <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
            <?= __('board.edit_translation_notice', ['locale' => $currentLocale, 'original' => $post['original_locale'] ?? 'ko']) ?>
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

            <!-- 확장 변수 -->
            <?php
            $evStmt = $pdo->prepare("SELECT * FROM {$prefix}board_extra_vars WHERE board_id = ? AND is_active = 1 ORDER BY sort_order");
            $evStmt->execute([$boardId]);
            $extraVarDefs = $evStmt->fetchAll(PDO::FETCH_ASSOC);
            if (!empty($extraVarDefs)) {
                require_once BASE_PATH . '/rzxlib/Core/Modules/ExtraVarRenderer.php';
                $evValues = [];
                if ($editMode && !empty($post['extra_vars'])) {
                    $evValues = json_decode($post['extra_vars'], true) ?: [];
                }
                \RzxLib\Core\Modules\ExtraVarRenderer::renderAll($extraVarDefs, $evValues, 'input', $boardId);
            }
            ?>

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
                        $isPrimary = !empty($file['is_primary']);
                    ?>
                    <div class="relative group border rounded-lg overflow-hidden <?= $isPrimary ? 'border-amber-500 border-2 ring-2 ring-amber-200' : 'border-zinc-200 dark:border-zinc-600' ?>" data-file-id="<?= $file['id'] ?>">
                        <?php if ($isImage): ?>
                        <img src="<?= htmlspecialchars($fileUrl) ?>" class="w-full h-24 object-cover">
                        <?php else: ?>
                        <div class="w-full h-24 bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center">
                            <svg class="w-8 h-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                        </div>
                        <?php endif; ?>
                        <div class="px-2 py-1 text-xs text-zinc-600 dark:text-zinc-400 truncate"><?= htmlspecialchars($file['original_name']) ?></div>
                        <?php if ($isPrimary): ?>
                        <span class="absolute top-1 left-1 px-1.5 py-0.5 bg-amber-500 text-white text-[10px] font-bold rounded">★ 대표</span>
                        <?php endif; ?>
                        <?php if ($isImage && !$isPrimary): ?>
                        <button type="button" class="btn-set-primary absolute top-1 left-1 w-5 h-5 bg-white/90 text-amber-600 border border-amber-400 rounded-full text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition hover:bg-amber-50" data-id="<?= $file['id'] ?>" title="대표 이미지 지정">★</button>
                        <?php endif; ?>
                        <?php if ($isImage): ?>
                        <button type="button" class="btn-insert-content absolute bottom-7 right-1 w-5 h-5 bg-white/90 text-blue-600 border border-blue-400 rounded-full text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition hover:bg-blue-50" data-url="<?= htmlspecialchars($fileUrl) ?>" title="본문에 삽입">+</button>
                        <?php endif; ?>
                        <button type="button" class="btn-remove-file absolute top-1 right-1 w-5 h-5 bg-red-500 text-white rounded-full text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition" data-id="<?= $file['id'] ?>" title="삭제">&times;</button>
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
let primaryPendingIdx = -1; // pendingFiles 중 대표로 지정할 인덱스 (썸네일 캡처 시 자동 지정)

// ─── URL 인풋 옆 [썸네일]·[스크린샷] 버튼 자동 삽입 ───
document.addEventListener('DOMContentLoaded', () => {
    document.querySelectorAll('input[type="url"]').forEach(inp => {
        if (inp.dataset.rzxAttached) return;
        inp.dataset.rzxAttached = '1';
        const wrap = document.createElement('div');
        wrap.className = 'flex gap-1.5 mt-1.5';
        wrap.innerHTML = `
            <button type="button" data-kind="thumbnail"
                title="첫 화면 뷰포트 캡처"
                class="rzx-url-capture-btn px-2.5 py-1 text-xs rounded border border-blue-500 text-blue-600 hover:bg-blue-50 transition inline-flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                썸네일 가져오기
            </button>
            <button type="button" data-kind="screenshot"
                title="전체 스크롤 페이지 캡처"
                class="rzx-url-capture-btn px-2.5 py-1 text-xs rounded border border-indigo-500 text-indigo-600 hover:bg-indigo-50 transition inline-flex items-center gap-1">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9zM15 13a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                스크린샷 가져오기
            </button>
            <div class="rzx-url-capture-progress flex-1 min-w-[120px] self-center ml-1 hidden">
                <div class="flex items-center gap-2">
                    <div class="relative flex-1 h-1.5 bg-zinc-200 dark:bg-zinc-700 rounded-full overflow-hidden">
                        <div class="rzx-url-capture-bar absolute inset-y-0 left-0 bg-blue-500 dark:bg-blue-400 rounded-full" style="width:0%;transition:width 0.3s linear"></div>
                    </div>
                    <span class="rzx-url-capture-pct text-[11px] text-zinc-500 dark:text-zinc-400 shrink-0 tabular-nums">0%</span>
                </div>
                <div class="rzx-url-capture-label text-[10px] text-zinc-500 dark:text-zinc-400 mt-0.5"></div>
            </div>
            <span class="rzx-url-capture-status text-xs text-zinc-500 self-center ml-1"></span>
        `;
        inp.insertAdjacentElement('afterend', wrap);

        wrap.querySelectorAll('.rzx-url-capture-btn').forEach(btn => {
            btn.addEventListener('click', () => rzxCaptureFromUrl(inp, wrap, btn.dataset.kind));
        });
    });
});

async function rzxCaptureFromUrl(inputEl, wrapEl, kind) {
    const url      = (inputEl.value || '').trim();
    const status   = wrapEl.querySelector('.rzx-url-capture-status');
    const btns     = wrapEl.querySelectorAll('.rzx-url-capture-btn');
    const progress = wrapEl.querySelector('.rzx-url-capture-progress');
    const bar      = wrapEl.querySelector('.rzx-url-capture-bar');
    const pct      = wrapEl.querySelector('.rzx-url-capture-pct');
    const labelEl  = wrapEl.querySelector('.rzx-url-capture-label');

    if (!/^https?:\/\//i.test(url)) {
        status.textContent = 'URL 을 먼저 입력하세요';
        status.className = 'rzx-url-capture-status text-xs text-red-500 self-center ml-1';
        return;
    }

    const kindLabel = kind === 'screenshot' ? '스크린샷(전체 페이지)' : '썸네일(첫 화면)';
    const estimate  = kind === 'screenshot' ? 18000 : 8000;   // 스테이지 끝(저장까지) 기준치
    const hardLimit = kind === 'screenshot' ? 110000 : 55000; // 요청 상한 (ms)

    // 단계 정의 — 누적 비율(% of estimate) 과 라벨
    const stages = [
        { until: 0.05, pct: 5,  label: '서버 연결 중…' },
        { until: 0.70, pct: 70, label: '페이지 로딩 중…' },
        { until: 0.90, pct: 90, label: '화면 캡처 중…' },
        { until: 0.97, pct: 96, label: '이미지 생성 중…' },
        { until: 1.00, pct: 98, label: '파일 저장 중…' },
    ];

    // UI 초기화
    btns.forEach(b => b.disabled = true);
    status.textContent = '';
    progress.classList.remove('hidden');
    labelEl.textContent = kindLabel + ' — ' + stages[0].label;
    bar.classList.remove('bg-red-500', 'bg-green-500');
    bar.classList.add('bg-blue-500');
    bar.style.width = '0%';
    pct.textContent = '0%';

    // 스테이지 보간 — 각 구간 내부에서도 prevPct → stage.pct 선형 보간
    const t0 = Date.now();
    const tick = setInterval(() => {
        const t = Date.now() - t0;
        const ratio = t / estimate;
        if (ratio >= 1) {
            bar.style.width = '98%';
            pct.textContent = '98%';
            labelEl.textContent = kindLabel + ' — 응답 대기 중…';
            return;
        }
        let prevUntil = 0, prevPct = 0;
        for (const s of stages) {
            if (ratio < s.until) {
                const local = (ratio - prevUntil) / (s.until - prevUntil);
                const p = prevPct + local * (s.pct - prevPct);
                bar.style.width = p.toFixed(1) + '%';
                pct.textContent = Math.floor(p) + '%';
                labelEl.textContent = kindLabel + ' — ' + s.label;
                return;
            }
            prevUntil = s.until;
            prevPct = s.pct;
        }
    }, 150);

    const ac = new AbortController();
    const killer = setTimeout(() => ac.abort(), hardLimit);

    const finish = (ok, msg) => {
        clearInterval(tick);
        clearTimeout(killer);
        btns.forEach(b => b.disabled = false);
        if (ok) {
            bar.style.transition = 'width 0.25s ease-out';
            bar.style.width = '100%';
            pct.textContent = '100%';
            bar.classList.remove('bg-blue-500');
            bar.classList.add('bg-green-500');
            labelEl.textContent = '';
            status.textContent = '✓ ' + msg;
            status.className = 'rzx-url-capture-status text-xs text-green-600 self-center ml-1';
            setTimeout(() => progress.classList.add('hidden'), 1500);
        } else {
            bar.classList.remove('bg-blue-500');
            bar.classList.add('bg-red-500');
            labelEl.textContent = '';
            status.textContent = msg;
            status.className = 'rzx-url-capture-status text-xs text-red-500 self-center ml-1';
        }
    };

    try {
        const endpoint = boardApiUrl + '/url-capture?type=' + encodeURIComponent(kind) + '&url=' + encodeURIComponent(url);
        const resp = await fetch(endpoint, {
            method: 'GET',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            signal: ac.signal,
        });
        if (!resp.ok) {
            let msg = `실패 (HTTP ${resp.status})`;
            try { const j = await resp.json(); if (j.message) msg += `: ${j.message}`; } catch (_) {}
            finish(false, msg);
            return;
        }
        const blob = await resp.blob();
        const ext = (blob.type && blob.type.split('/')[1]) || 'png';
        const host = (new URL(url)).hostname.replace(/\./g, '-');
        const filename = `${kind}_${host}_${Date.now()}.${ext}`;
        const file = new File([blob], filename, { type: blob.type || 'image/png' });

        // 기존 업로드 파이프라인에 합류 (addFiles 가 pendingFiles.push + 프리뷰 + 삭제버튼 처리)
        const newIdx = pendingFiles.length;
        addFiles([file]);
        if (kind === 'thumbnail') primaryPendingIdx = newIdx;

        finish(true, `${kindLabel} 첨부됨${kind === 'thumbnail' ? ' · 대표 이미지' : ''}`);
    } catch (err) {
        if (err.name === 'AbortError') {
            finish(false, '시간 초과. 대상 사이트 응답이 없습니다.');
        } else {
            console.error('[url-capture] 에러:', err);
            finish(false, '오류: ' + err.message);
        }
    }
}

// 에디터용 OG 카드 fetch + 교체
async function rzxFetchOgAndReplace(node, url) {
    try {
        var fd = new FormData();
        fd.append('url', url);
        var resp = await fetch(boardApiUrl + '/og', { method: 'POST', body: fd });
        var data = await resp.json();
        if (data.success && data.og && data.og.title) {
            if (typeof rzxInsertOgCard === 'function') {
                // buildOgCardHtml은 board-autolink.js에 정의됨
                var og = data.og;
                var esc = function(s) { var d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; };
                var img = og.image ? '<div style="flex-shrink:0;width:120px;min-height:80px;background:#f4f4f5;overflow:hidden;border-radius:8px 0 0 8px"><img src="' + esc(og.image) + '" style="width:100%;height:100%;object-fit:cover;display:block" onerror="this.parentElement.style.display=\'none\'"></div>' : '';
                var favicon = og.favicon ? '<img src="' + esc(og.favicon) + '" style="width:14px;height:14px;border-radius:2px;display:inline;vertical-align:middle" onerror="this.style.display=\'none\'">&nbsp;' : '';
                node.outerHTML = '<div class="rzx-og-card" contenteditable="false" style="margin:12px auto;border:1px solid #d4d4d8;border-radius:8px;overflow:hidden;max-width:480px;cursor:pointer" onclick="window.open(\'' + esc(og.url) + '\',\'_blank\')">'
                    + '<a href="' + esc(og.url) + '" target="_blank" rel="noopener noreferrer" style="display:flex;text-decoration:none;color:inherit">'
                    + img
                    + '<div style="flex:1;padding:12px;min-width:0">'
                    + '<div style="font-size:14px;font-weight:600;color:#18181b;line-height:1.3;margin-bottom:4px;overflow:hidden;text-overflow:ellipsis;white-space:nowrap">' + esc(og.title) + '</div>'
                    + (og.description ? '<div style="font-size:12px;color:#71717a;line-height:1.4;margin-bottom:6px;display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden">' + esc(og.description) + '</div>' : '')
                    + '<div style="font-size:11px;color:#a1a1aa">' + favicon + esc(og.domain || og.site_name || '') + '</div>'
                    + '</div></a></div>';
                console.log('[OgCard] Editor card inserted:', og.title);
            }
        } else {
            // OG 데이터 없으면 원래 URL 복원
            node.innerHTML = '<a href="' + url + '" target="_blank">' + url + '</a>';
        }
    } catch (e) {
        node.innerHTML = '<a href="' + url + '" target="_blank">' + url + '</a>';
        console.error('[OgCard] Fetch error:', e);
    }
}

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
            },
            onKeyup: function(e) {
                // 스페이스 시 자동 링크 변환
                if (e.keyCode === 32) {
                    rzxAutoLinkInEditor(this);
                }
                // 엔터 시 직전 줄이 단독 URL이면 OG 카드 삽입
                if (e.keyCode === 13) {
                    rzxAutoLinkInEditor(this);
                    var editable = this.querySelector('.note-editable');
                    if (editable) {
                        var nodes = editable.querySelectorAll('p, div');
                        for (var i = nodes.length - 1; i >= Math.max(0, nodes.length - 3); i--) {
                            var txt = (nodes[i].textContent || '').trim();
                            if (/^(https?:\/\/|www\.)\S+$/.test(txt) && !nodes[i].querySelector('.rzx-og-card')) {
                                var url = txt.startsWith('www.') ? 'https://' + txt : txt;
                                nodes[i].innerHTML = '<span style="color:#a1a1aa;font-size:12px">🔗 loading preview...</span>';
                                (function(node, u) {
                                    rzxFetchOgAndReplace(node, u);
                                })(nodes[i], url);
                                break;
                            }
                        }
                    }
                }
            },
            onPaste: function(e) {
                var self = this;
                setTimeout(function() {
                    rzxAutoLinkInEditor(self);
                    // 붙여넣기된 단독 URL → OG 카드
                    var editable = self.querySelector('.note-editable');
                    if (editable) {
                        var sel = window.getSelection();
                        if (sel.focusNode) {
                            var p = sel.focusNode.nodeType === 3 ? sel.focusNode.parentElement : sel.focusNode;
                            if (p) {
                                var txt = (p.textContent || '').trim();
                                if (/^(https?:\/\/|www\.)\S+$/.test(txt) && !p.querySelector('.rzx-og-card')) {
                                    var url = txt.startsWith('www.') ? 'https://' + txt : txt;
                                    p.innerHTML = '<span style="color:#a1a1aa;font-size:12px">🔗 loading preview...</span>';
                                    rzxFetchOgAndReplace(p, url);
                                }
                            }
                        }
                    }
                }, 200);
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
                    <button type="button" onclick="setPendingPrimary(${idx})" class="absolute top-1 left-1 w-5 h-5 bg-white/90 text-amber-600 border border-amber-400 rounded-full text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition hover:bg-amber-50" title="대표 이미지 지정">★</button>
                    <button type="button" onclick="mpInsertPendingToContent(pendingFiles[${idx}])" class="absolute bottom-7 right-1 w-5 h-5 bg-white/90 text-blue-600 border border-blue-400 rounded-full text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition hover:bg-blue-50" title="본문에 삽입">+</button>
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

// 신규 파일 중 대표 이미지로 지정 (저장 시 primary_file_pos 로 전송)
function setPendingPrimary(idx) {
    primaryPendingIdx = idx;
    // 시각 피드백: 모든 신규 파일 카드에서 대표 표시 제거 → 선택 카드에 ★ 배지
    document.querySelectorAll('[id^=pending-file-]').forEach(el => {
        el.querySelectorAll('.pending-primary-badge').forEach(b => b.remove());
        el.classList.remove('border-amber-500','border-2','ring-2','ring-amber-200');
    });
    const target = document.getElementById('pending-file-' + idx);
    if (target) {
        target.classList.add('border-amber-500','border-2','ring-2','ring-amber-200');
        const badge = document.createElement('span');
        badge.className = 'pending-primary-badge absolute top-1 left-1 px-1.5 py-0.5 bg-amber-500 text-white text-[10px] font-bold rounded';
        badge.textContent = '★ 대표';
        target.appendChild(badge);
    }
}

// 폼 전송
document.getElementById('writeForm').addEventListener('submit', async (e) => {
    e.preventDefault();
    console.log('[BoardWrite] 폼 전송');
    const form = new FormData(e.target);

    // Summernote 내용 가져오기
    form.set('content', $('#boardContent').summernote('code'));

    // 드래그&드롭 파일 추가 + 대표 위치 계산 (filter된 $_FILES 순서 기준)
    form.delete('files[]');
    let primaryPos = -1, pos = 0;
    pendingFiles.forEach((f, idx) => {
        if (!f) return;
        if (idx === primaryPendingIdx) primaryPos = pos;
        form.append('files[]', f);
        pos++;
    });
    if (primaryPos >= 0) form.set('primary_file_pos', String(primaryPos));

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
        if (!confirm('이 파일을 삭제하시겠습니까?')) return;
        const fid = btn.dataset.id;
        try {
            const resp = await fetch(boardApiUrl + '/files?action=delete&id=' + fid, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await resp.json();
            if (data.success) btn.closest('[data-file-id]').remove();
            else alert(data.message || 'Error');
        } catch (err) { console.error(err); }
    });
});

// 기존 파일 → 대표 이미지 지정
document.querySelectorAll('.btn-set-primary').forEach(btn => {
    btn.addEventListener('click', async () => {
        const fid = btn.dataset.id;
        try {
            const resp = await fetch(boardApiUrl + '/files?action=set_primary&id=' + fid, { method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' } });
            const data = await resp.json();
            if (data.success) {
                // 페이지 새로고침으로 UI 반영 (가장 단순·확실)
                location.reload();
            } else {
                alert(data.message || 'Error');
            }
        } catch (err) { console.error(err); }
    });
});

// 기존 파일 → 본문에 이미지 삽입
document.querySelectorAll('.btn-insert-content').forEach(btn => {
    btn.addEventListener('click', () => {
        const url = btn.dataset.url;
        if (!url) return;
        try {
            $('#boardContent').summernote('insertImage', url);
        } catch (e) { console.warn('insertImage failed:', e); }
    });
});

// 신규 파일 (pendingFiles) → 본문에 이미지 삽입 (FileReader → base64 데이터 URL)
function mpInsertPendingToContent(file) {
    if (!file || !file.type || !file.type.startsWith('image/')) return;
    const reader = new FileReader();
    reader.onload = (e) => {
        try { $('#boardContent').summernote('insertImage', e.target.result); }
        catch (err) { console.warn('insertImage failed:', err); }
    };
    reader.readAsDataURL(file);
}
window.mpInsertPendingToContent = mpInsertPendingToContent;
</script>
