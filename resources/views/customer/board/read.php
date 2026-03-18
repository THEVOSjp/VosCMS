<?php
/**
 * RezlyX 게시판 - 글 상세
 */
include __DIR__ . '/_init.php';

if (!boardCheckPerm($board, 'perm_read', $currentUser)) {
    http_response_code(403);
    echo '<p>접근 권한이 없습니다.</p>';
    exit;
}

// 게시글 로드
$postStmt = $pdo->prepare("SELECT * FROM {$prefix}board_posts WHERE id = ? AND board_id = ? AND status != 'trash'");
$postStmt->execute([$postId, $boardId]);
$post = $postStmt->fetch(PDO::FETCH_ASSOC);

if (!$post) {
    http_response_code(404);
    include BASE_PATH . '/resources/views/customer/404.php';
    exit;
}

// 비밀글 접근 제한
if ($post['is_secret'] && (!$currentUser || ($currentUser['id'] != $post['user_id'] && !$isAdmin))) {
    // 비밀번호 확인 폼
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['password'])) {
        if (!password_verify($_POST['password'], $post['password'] ?? '')) {
            $secretError = true;
        } else {
            $secretVerified = true;
        }
    }
    if (!isset($secretVerified)) {
        $pageTitle = __('board.secret_post') . ' - ' . $board['title'];
        ?>
        <div class="max-w-5xl mx-auto px-4 sm:px-6 py-12 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
            <h2 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-2"><?= __('board.secret_post') ?></h2>
            <p class="text-sm text-zinc-500 mb-4"><?= __('board.enter_password') ?></p>
            <?php if (isset($secretError)): ?><p class="text-sm text-red-500 mb-3"><?= __('board.wrong_password') ?></p><?php endif; ?>
            <form method="POST" class="inline-flex gap-2">
                <input type="password" name="password" class="px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200" placeholder="<?= __('board.password') ?>">
                <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg"><?= __('board.confirm') ?></button>
            </form>
        </div>
        <?php return; // 레이아웃 내에서 여기서 종료
    }
}

// 상담 모드 접근 제한
if (($board['consultation'] ?? 0) && $currentUser && $currentUser['id'] != $post['user_id'] && !$isAdmin) {
    http_response_code(403);
    echo '<p>본인이 작성한 글만 볼 수 있습니다.</p>';
    exit;
}

// 조회수 증가
$pdo->prepare("UPDATE {$prefix}board_posts SET view_count = view_count + 1 WHERE id = ?")->execute([$postId]);
$post['view_count']++;

// 다국어 폴백: source_locale → en → original_locale
$postSourceLocale = $post['source_locale'] ?? $currentLocale;
$postOriginalLocale = $post['original_locale'] ?? $postSourceLocale;

if ($currentLocale !== $postSourceLocale) {
    // 현재 언어 != source_locale → 폴백 체인으로 콘텐츠 검색
    // 1. source_locale의 콘텐츠 (현재 DB에 저장된 값) → 기본 사용
    // 2. 추후 rzx_translations에 번역이 있으면 우선 표시 가능
    // 현재는 source_locale 콘텐츠를 그대로 표시
}

// 카테고리 정보
$postCategory = $catMap[$post['category_id'] ?? 0] ?? null;

// 댓글 로드
$commentStmt = $pdo->prepare("SELECT * FROM {$prefix}board_comments WHERE post_id = ? AND status = 'published' ORDER BY created_at ASC");
$commentStmt->execute([$postId]);
$comments = $commentStmt->fetchAll(PDO::FETCH_ASSOC);

// 첨부파일 로드
$fileStmt = $pdo->prepare("SELECT * FROM {$prefix}board_files WHERE post_id = ? ORDER BY id ASC");
$fileStmt->execute([$postId]);
$files = $fileStmt->fetchAll(PDO::FETCH_ASSOC);

// 이전/다음 글
$prevStmt = $pdo->prepare("SELECT id, title FROM {$prefix}board_posts WHERE board_id = ? AND status = 'published' AND id < ? ORDER BY id DESC LIMIT 1");
$prevStmt->execute([$boardId, $postId]);
$prevPost = $prevStmt->fetch(PDO::FETCH_ASSOC);

$nextStmt = $pdo->prepare("SELECT id, title FROM {$prefix}board_posts WHERE board_id = ? AND status = 'published' AND id > ? ORDER BY id ASC LIMIT 1");
$nextStmt->execute([$boardId, $postId]);
$nextPost = $nextStmt->fetch(PDO::FETCH_ASSOC);

// 수정/삭제 권한
$canEdit = $currentUser && ($currentUser['id'] == $post['user_id'] || $isAdmin);
$canDelete = $canEdit;

// 보호 로직
if ($canEdit && !$isAdmin) {
    if (($board['protect_content_by_comment'] ?? 0) && ($post['comment_count'] ?? 0) > 0) {
        $canEdit = false; $canDelete = false;
    }
    if (($board['protect_by_days'] ?? 0) > 0) {
        $daysSince = (time() - strtotime($post['created_at'])) / 86400;
        if ($daysSince > (int)$board['protect_by_days']) { $canEdit = false; $canDelete = false; }
    }
}

$pageTitle = htmlspecialchars($post['title']) . ' - ' . $board['title'];
?>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-6">
        <!-- 글 헤더 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="p-6 border-b border-zinc-100 dark:border-zinc-700">
                <?php if ($postCategory): ?>
                <span class="inline-flex items-center gap-1 px-2 py-0.5 text-xs font-medium bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400 rounded mb-2">
                    <?php if (!empty($postCategory['color'])): ?><span class="w-2 h-2 rounded-full" style="background:<?= htmlspecialchars($postCategory['color']) ?>"></span><?php endif; ?>
                    <?= htmlspecialchars($postCategory['name']) ?>
                </span>
                <?php endif; ?>
                <h1 class="text-xl font-bold text-zinc-800 dark:text-zinc-100">
                    <?php if ($post['is_notice']): ?><span class="text-red-500">[<?= __('board.notice') ?>]</span> <?php endif; ?>
                    <?= htmlspecialchars($post['title']) ?>
                </h1>
                <div class="flex items-center gap-4 mt-3 text-sm text-zinc-500 dark:text-zinc-400">
                    <span><?= htmlspecialchars($post['nick_name']) ?></span>
                    <span><?= date('Y.m.d H:i', strtotime($post['created_at'])) ?></span>
                    <span><?= __('board.col_views') ?>: <?= number_format($post['view_count']) ?></span>
                    <?php if (($post['like_count'] ?? 0) > 0): ?>
                    <span><?= __('board.col_votes') ?>: <?= $post['like_count'] ?></span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 원문 언어 안내 -->
            <?php if ($currentLocale !== $postSourceLocale): ?>
            <div class="mx-6 mt-4 px-4 py-2 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg flex items-center gap-2 text-sm text-amber-700 dark:text-amber-300">
                <svg class="w-4 h-4 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/></svg>
                <?= __('board.source_locale_notice', ['locale' => $postSourceLocale]) ?>
            </div>
            <?php endif; ?>

            <!-- 본문 -->
            <div class="p-6 prose dark:prose-invert max-w-none text-zinc-800 dark:text-zinc-200 leading-relaxed">
                <?= $post['content'] ?>
            </div>

            <!-- 첨부파일 -->
            <?php if (!empty($files)): ?>
            <div class="px-6 pb-4 border-t border-zinc-100 dark:border-zinc-700 pt-4">
                <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-2"><?= __('board.attachments') ?> [<?= count($files) ?>]</p>
                <ul class="space-y-1">
                    <?php foreach ($files as $file): ?>
                    <li>
                        <a href="<?= ($config['app_url'] ?? '') ?>/board/api/files?action=download&id=<?= $file['id'] ?>" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                            <?= htmlspecialchars($file['original_name']) ?>
                            <span class="text-zinc-400 text-xs">(<?= number_format($file['file_size'] / 1024, 1) ?>KB)</span>
                        </a>
                    </li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>

            <!-- 추천/비추천 + 액션 버튼 -->
            <div class="px-6 py-4 border-t border-zinc-100 dark:border-zinc-700 flex items-center justify-between">
                <div class="flex items-center gap-2">
                    <button type="button" id="btnLike" class="flex items-center gap-1.5 px-4 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition text-zinc-600 dark:text-zinc-400">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/></svg>
                        <?= __('board.like') ?> <span id="likeCount"><?= $post['like_count'] ?? 0 ?></span>
                    </button>
                    <button type="button" id="btnDislike" class="flex items-center gap-1.5 px-4 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition text-zinc-600 dark:text-zinc-400">
                        <svg class="w-4 h-4 rotate-180" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 10h4.764a2 2 0 011.789 2.894l-3.5 7A2 2 0 0115.263 21h-4.017c-.163 0-.326-.02-.485-.06L7 20m7-10V5a2 2 0 00-2-2h-.095c-.5 0-.905.405-.905.905 0 .714-.211 1.412-.608 2.006L7 11v9m7-10h-2M7 20H5a2 2 0 01-2-2v-6a2 2 0 012-2h2.5"/></svg>
                        <?= __('board.dislike') ?> <span id="dislikeCount"><?= $post['dislike_count'] ?? 0 ?></span>
                    </button>
                </div>
                <div class="flex items-center gap-2">
                    <?php if ($canEdit): ?>
                    <a href="<?= $boardUrl ?>/<?= $postId ?>/edit" class="px-4 py-2 text-sm text-zinc-600 dark:text-zinc-400 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition"><?= __('board.edit') ?></a>
                    <?php endif; ?>
                    <?php if ($canDelete): ?>
                    <button type="button" id="btnDelete" class="px-4 py-2 text-sm text-red-600 dark:text-red-400 border border-red-300 dark:border-red-600 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition"><?= __('board.delete_post') ?></button>
                    <?php endif; ?>
                    <a href="<?= $boardUrl ?>" class="px-4 py-2 text-sm text-zinc-600 dark:text-zinc-400 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition"><?= __('board.list') ?></a>
                </div>
            </div>
        </div>

        <!-- 이전/다음 글 -->
        <div class="mt-4 bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-100 dark:divide-zinc-700">
            <?php if ($prevPost): ?>
            <a href="<?= $boardUrl ?>/<?= $prevPost['id'] ?>" class="flex items-center px-6 py-3 text-sm hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition">
                <span class="w-16 text-zinc-400 dark:text-zinc-500"><?= __('board.prev') ?></span>
                <span class="text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($prevPost['title']) ?></span>
            </a>
            <?php endif; ?>
            <?php if ($nextPost): ?>
            <a href="<?= $boardUrl ?>/<?= $nextPost['id'] ?>" class="flex items-center px-6 py-3 text-sm hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition">
                <span class="w-16 text-zinc-400 dark:text-zinc-500"><?= __('board.next') ?></span>
                <span class="text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($nextPost['title']) ?></span>
            </a>
            <?php endif; ?>
        </div>

        <!-- 댓글 -->
        <?php if ($board['allow_comment'] ?? 1): ?>
        <div class="mt-6" id="commentSection">
            <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-4"><?= __('board.comments') ?> <span class="text-blue-600"><?= count($comments) ?></span></h3>

            <!-- 댓글 목록 -->
            <div class="space-y-3" id="commentList">
                <?php foreach ($comments as $comment): ?>
                <div class="bg-white dark:bg-zinc-800 rounded-lg border border-zinc-200 dark:border-zinc-700 p-4 <?= ($comment['depth'] ?? 0) > 0 ? 'ml-8' : '' ?>" data-id="<?= $comment['id'] ?>">
                    <?php if ($comment['status'] === 'deleted'): ?>
                    <p class="text-sm text-zinc-400 italic"><?= htmlspecialchars($board['comment_delete_message'] ?? __('board.comment_deleted')) ?></p>
                    <?php else: ?>
                    <div class="flex items-center justify-between mb-2">
                        <div class="flex items-center gap-2 text-sm">
                            <span class="font-medium text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($comment['nick_name']) ?></span>
                            <span class="text-zinc-400 dark:text-zinc-500"><?= date('Y.m.d H:i', strtotime($comment['created_at'])) ?></span>
                        </div>
                        <?php if ($currentUser && ($currentUser['id'] == $comment['user_id'] || $isAdmin)): ?>
                        <button type="button" class="btn-comment-delete text-xs text-red-500 hover:underline" data-id="<?= $comment['id'] ?>"><?= __('board.delete') ?></button>
                        <?php endif; ?>
                    </div>
                    <div class="text-sm text-zinc-700 dark:text-zinc-300"><?= nl2br(htmlspecialchars($comment['content'])) ?></div>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- 댓글 작성 폼 -->
            <?php if (boardCheckPerm($board, 'perm_comment', $currentUser)): ?>
            <form id="commentForm" class="mt-4">
                <?php if (!$currentUser): ?>
                <div class="flex gap-2 mb-2">
                    <input type="text" name="nick_name" placeholder="<?= __('board.nickname') ?>" required class="w-32 px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200">
                    <input type="password" name="password" placeholder="<?= __('board.password') ?>" required class="w-32 px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200">
                </div>
                <?php endif; ?>
                <div class="flex gap-2">
                    <textarea name="content" rows="3" required placeholder="<?= __('board.comment_placeholder') ?>"
                              class="flex-1 px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200 resize-none"></textarea>
                    <button type="submit" class="self-end px-5 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition"><?= __('board.submit_comment') ?></button>
                </div>
            </form>
            <?php endif; ?>
        </div>
        <?php endif; ?>
    </div>

<script>
console.log('[BoardRead] 게시글 상세 로드, postId=<?= $postId ?>');
const boardApiUrl = '<?= ($config['app_url'] ?? '') ?>/board/api';
const postId = <?= $postId ?>;
const boardId = <?= $boardId ?>;

// 추천
document.getElementById('btnLike')?.addEventListener('click', async () => {
    console.log('[BoardRead] 추천 클릭');
    try {
        const resp = await fetch(boardApiUrl + '/posts', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: 'action=like&post_id=' + postId
        });
        const data = await resp.json();
        if (data.success) document.getElementById('likeCount').textContent = data.like_count;
        else alert(data.message || 'Error');
    } catch (err) { console.error(err); }
});

// 비추천
document.getElementById('btnDislike')?.addEventListener('click', async () => {
    console.log('[BoardRead] 비추천 클릭');
    try {
        const resp = await fetch(boardApiUrl + '/posts', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: 'action=dislike&post_id=' + postId
        });
        const data = await resp.json();
        if (data.success) document.getElementById('dislikeCount').textContent = data.dislike_count;
        else alert(data.message || 'Error');
    } catch (err) { console.error(err); }
});

// 삭제
document.getElementById('btnDelete')?.addEventListener('click', async () => {
    if (!confirm('<?= __('board.delete_confirm') ?>')) return;
    console.log('[BoardRead] 글 삭제');
    try {
        const resp = await fetch(boardApiUrl + '/posts', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: 'action=delete&post_id=' + postId + '&board_id=' + boardId
        });
        const data = await resp.json();
        if (data.success) location.href = '<?= $boardUrl ?>';
        else alert(data.message || 'Error');
    } catch (err) { console.error(err); alert('Error: ' + err.message); }
});

// 댓글 작성
document.getElementById('commentForm')?.addEventListener('submit', async (e) => {
    e.preventDefault();
    console.log('[BoardRead] 댓글 작성');
    const form = new FormData(e.target);
    form.append('action', 'create');
    form.append('post_id', postId);
    form.append('board_id', boardId);
    try {
        const resp = await fetch(boardApiUrl + '/comments', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: new URLSearchParams(form)
        });
        const data = await resp.json();
        if (data.success) location.reload();
        else alert(data.message || 'Error');
    } catch (err) { console.error(err); alert('Error: ' + err.message); }
});

// 댓글 삭제
document.querySelectorAll('.btn-comment-delete').forEach(btn => {
    btn.addEventListener('click', async () => {
        if (!confirm('<?= __('board.comment_delete_confirm') ?>')) return;
        const cid = btn.dataset.id;
        console.log('[BoardRead] 댓글 삭제:', cid);
        try {
            const resp = await fetch(boardApiUrl + '/comments', {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: 'action=delete&comment_id=' + cid
            });
            const data = await resp.json();
            if (data.success) location.reload();
            else alert(data.message || 'Error');
        } catch (err) { console.error(err); }
    });
});
</script>
