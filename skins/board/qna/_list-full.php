<?php
/**
 * Q&A Accordion Skin - 목록 출력
 *
 * 디자인: FAQ 스킨과 동일 (검색창 + 아코디언 + 페이지네이션)
 * 차이: 글의 제목+본문을 Q(질문), 댓글을 A(답변)으로 표시
 *
 * 상위 list.php에서 전달:
 *   $posts, $board, $skinConfig, $boardUrl, $currentLocale,
 *   $page, $totalPages, $totalCount, $perPage, $searchKeyword, $searchTarget,
 *   $pdo, $prefix, $config, $catMap
 */

$primaryColor       = $skinConfig['primary_color'] ?? '#3b82f6';
$expandFirst        = !empty($skinConfig['expand_first']);
$showNoAnswerBadge  = !isset($skinConfig['show_no_answer_badge']) || $skinConfig['show_no_answer_badge'];
$contentWidth       = $skinConfig['content_width'] ?? 'max-w-3xl';

// 타이틀 영역
$showTitle = !isset($skinConfig['show_title']) || $skinConfig['show_title'];
$_resolveLocale = function ($v, $loc) {
    if (is_array($v)) return $v[$loc] ?? $v['en'] ?? reset($v) ?: '';
    return (string)($v ?? '');
};
$titleText    = $_resolveLocale($skinConfig['title_text']    ?? ['ko'=>'Q&A','en'=>'Q&A','ja'=>'Q&A'], $currentLocale ?? 'ko') ?: 'Q&A';
$subtitleText = $_resolveLocale($skinConfig['subtitle_text'] ?? ['ko'=>'질문과 답변','en'=>'Questions and Answers','ja'=>'質問と回答'], $currentLocale ?? 'ko');

$titleBgType    = $skinConfig['title_bg_type']    ?? 'none';
$titleBgImage   = $skinConfig['title_bg_image']   ?? '';
$titleBgVideo   = $skinConfig['title_bg_video']   ?? '';
$titleBgHeight  = (int)($skinConfig['title_bg_height']  ?? 280);
$titleBgOverlay = (int)($skinConfig['title_bg_overlay'] ?? 40);
$hasBg = ($titleBgType === 'image' && $titleBgImage) || ($titleBgType === 'video' && $titleBgVideo);

// 다국어 라벨
$searchLabel  = ['ko'=>'궁금한 내용을 검색해 보세요','en'=>'Search for answers...','ja'=>'気になる内容を検索','de'=>'Suchen Sie nach Antworten...','es'=>'Busca respuestas...','fr'=>'Recherchez des réponses...','id'=>'Cari jawaban...','mn'=>'Хариулт хайх...','ru'=>'Поиск ответов...','tr'=>'Cevap arayın...','vi'=>'Tìm câu trả lời...','zh_CN'=>'搜索答案...','zh_TW'=>'搜尋答案...'];
$noResultLabel= ['ko'=>'검색 결과가 없습니다.','en'=>'No results found.','ja'=>'検索結果がありません。','de'=>'Keine Ergebnisse.','es'=>'Sin resultados.','fr'=>'Aucun résultat.','id'=>'Tidak ada hasil.','mn'=>'Илэрц олдсонгүй.','ru'=>'Ничего не найдено.','tr'=>'Sonuç bulunamadı.','vi'=>'Không có kết quả.','zh_CN'=>'没有找到结果。','zh_TW'=>'沒有找到結果。'];
$noAnswerLabel= ['ko'=>'미답변','en'=>'No answer','ja'=>'未回答','zh_CN'=>'未回答','zh_TW'=>'未回答','de'=>'Keine Antwort','es'=>'Sin respuesta','fr'=>'Sans réponse','id'=>'Belum dijawab','mn'=>'Хариугүй','ru'=>'Нет ответа','tr'=>'Cevapsız','vi'=>'Chưa trả lời'];
$_loc = $currentLocale ?? 'ko';

// ── 댓글 일괄 로드 (N+1 방지) — 부모→자식 트리 정렬 ─────────────────
$commentsByPost = [];
if ($posts) {
    $postIds = array_column($posts, 'id');
    $in = implode(',', array_map('intval', $postIds));
    $cs = $pdo->query(
        "SELECT id, post_id, user_id, parent_id, depth, nick_name, content, created_at
           FROM {$prefix}board_comments
          WHERE post_id IN ($in) AND status = 'published'
          ORDER BY created_at ASC, id ASC"
    )->fetchAll(PDO::FETCH_ASSOC);

    // 게시글별로 트리 정렬
    $byPost = [];
    foreach ($cs as $c) $byPost[(int)$c['post_id']][] = $c;
    foreach ($byPost as $pid => $list) {
        $roots = []; $childMap = [];
        foreach ($list as $c) {
            if (empty($c['parent_id'])) $roots[] = $c;
            else $childMap[(int)$c['parent_id']][] = $c;
        }
        $flat = [];
        $append = function ($node) use (&$append, &$flat, &$childMap) {
            $flat[] = $node;
            $cid = (int)$node['id'];
            if (!empty($childMap[$cid])) foreach ($childMap[$cid] as $child) $append($child);
        };
        foreach ($roots as $r) $append($r);
        $commentsByPost[$pid] = $flat;
    }
}

// 다국어 폴백 헬퍼 (게시글 번역)
$_qnaTr = function (int $postId, string $field, string $original) use ($pdo, $prefix, $currentLocale) {
    if (empty($currentLocale)) return $original;
    if (($currentLocale ?? 'ko') === 'ko') return $original;
    try {
        $s = $pdo->prepare("SELECT content FROM {$prefix}translations WHERE lang_key = ? AND locale = ?");
        $s->execute(["board_post.{$postId}.{$field}", $currentLocale]);
        $tr = $s->fetchColumn();
        if ($tr !== false) return $tr;
        if ($currentLocale !== 'en') {
            $s->execute(["board_post.{$postId}.{$field}", 'en']);
            $en = $s->fetchColumn();
            if ($en !== false) return $en;
        }
    } catch (\PDOException $e) {}
    return $original;
};
?>

<style>
.qna-skin { --qna-primary: <?= $primaryColor ?>; }
.qna-skin .qna-q { border-left: 4px solid var(--qna-primary); }
.qna-skin .qna-q-icon, .qna-skin .qna-a-icon { font-weight: 800; }
.qna-skin .qna-q-icon { color: var(--qna-primary); }
.qna-skin .qna-a-icon { color: #f59e0b; }
.qna-skin .qna-search-input { border-color: var(--qna-primary) !important; }
.qna-skin .qna-search-input:focus { box-shadow: 0 0 0 4px rgba(59,130,246,0.18); }
.qna-skin .qna-search-input::placeholder { color: #a1a1aa; }
.qna-skin .qna-page-active { background: var(--qna-primary); color: #fff; }
.qna-skin .qna-chevron { transition: transform 0.2s; }
.qna-skin .qna-item.open .qna-chevron { transform: rotate(180deg); }
.qna-skin .qna-answer { max-height: 0; overflow: hidden; transition: max-height 0.3s ease; }
.qna-skin .qna-item.open .qna-answer { max-height: 4000px; }
</style>

<?php if ($showTitle && $hasBg): ?>
<div class="relative w-full overflow-hidden mb-8" style="height: <?= $titleBgHeight ?>px">
    <?php if ($titleBgType === 'image'): ?>
    <img src="<?= htmlspecialchars($titleBgImage) ?>" alt="" class="absolute inset-0 w-full h-full object-cover">
    <?php elseif ($titleBgType === 'video'): ?>
    <video class="absolute inset-0 w-full h-full object-cover" autoplay muted loop playsinline>
        <source src="<?= htmlspecialchars($titleBgVideo) ?>">
    </video>
    <?php endif; ?>
    <?php if ($titleBgOverlay > 0): ?>
    <div class="absolute inset-0 bg-black" style="opacity: <?= $titleBgOverlay / 100 ?>"></div>
    <?php endif; ?>
    <div class="relative z-10 h-full flex flex-col items-center justify-center text-center px-4">
        <?php if ($subtitleText): ?>
        <p class="text-sm font-medium tracking-widest uppercase mb-2 text-white/90"><?= htmlspecialchars($subtitleText) ?></p>
        <?php endif; ?>
        <h1 class="text-5xl font-black text-white">
            <?= htmlspecialchars($titleText) ?>
        </h1>
    </div>
</div>
<?php endif; ?>

<div class="qna-skin <?= $contentWidth ?> mx-auto px-4 py-8">

    <?php if ($showTitle && !$hasBg): ?>
    <div class="text-center mb-10">
        <?php if ($subtitleText): ?>
        <p class="text-sm font-medium tracking-widest uppercase mb-2" style="color: var(--qna-primary);">
            <?= htmlspecialchars($subtitleText) ?>
        </p>
        <?php endif; ?>
        <h1 class="text-5xl font-black text-zinc-900 dark:text-white mb-4">
            <?= htmlspecialchars($titleText) ?>
        </h1>
    </div>
    <?php endif; ?>

    <!-- 검색창 -->
    <div class="mb-4">
        <form method="GET" action="<?= $boardUrl ?>">
            <input type="hidden" name="search_target" value="title_content">
            <div class="relative max-w-xl mx-auto">
                <svg class="absolute left-5 top-1/2 -translate-y-1/2 w-5 h-5" style="color: var(--qna-primary);" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                <input type="text" name="search_keyword" value="<?= htmlspecialchars($searchKeyword) ?>"
                       placeholder="<?= $searchLabel[$_loc] ?? $searchLabel['en'] ?>"
                       class="qna-search-input w-full pl-14 pr-5 py-5 text-base border-2 rounded-2xl bg-white dark:bg-zinc-800 text-zinc-800 dark:text-zinc-200 focus:outline-none transition">
            </div>
        </form>
    </div>

    <?php if (!empty($_SESSION['admin_id'])): ?>
    <!-- 관리자 설정 아이콘 (검색창 아래, 목록 위) -->
    <div class="flex justify-end mb-4">
        <?= rzx_admin_icons($boardUrl . '/settings', '') ?>
    </div>
    <?php endif; ?>

    <!-- 아코디언 목록 -->
    <?php if (empty($posts)): ?>
    <div class="text-center py-16 text-zinc-400 dark:text-zinc-500">
        <p><?= $noResultLabel[$_loc] ?? $noResultLabel['en'] ?></p>
    </div>
    <?php else: ?>
    <div class="space-y-3">
        <?php foreach ($posts as $idx => $post):
            $pid     = (int)$post['id'];
            $title   = $_qnaTr($pid, 'title',   $post['title']);
            $content = $_qnaTr($pid, 'content', $post['content']);
            $answers = $commentsByPost[$pid] ?? [];
            $isOpen  = ($expandFirst && $idx === 0);
        ?>
        <?php
        $isAdmin = !empty($_SESSION['admin_id']);
        $isPostOwner = $currentUser && !empty($post['user_id']) && (string)$post['user_id'] === (string)($currentUser['id'] ?? '');
        $canEditPost = $isAdmin || $isPostOwner;
        $canComment = boardCheckPerm($board, 'perm_comment', $currentUser ?? null);
        ?>
        <div class="qna-item bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden <?= $isOpen ? 'open' : '' ?>" data-pid="<?= $pid ?>">
            <!-- Q 헤더 (제목) -->
            <div class="qna-q-row flex items-start">
                <button type="button" onclick="this.closest('.qna-item').classList.toggle('open')"
                        class="qna-q flex-1 flex items-start gap-3 p-5 text-left hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition">
                    <span class="qna-q-icon text-2xl mt-0.5 flex-shrink-0">Q.</span>
                    <div class="flex-1 min-w-0">
                        <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 leading-snug"><?= htmlspecialchars($title) ?></h3>
                        <div class="flex items-center gap-2 mt-1 text-xs text-zinc-500 dark:text-zinc-400">
                            <span><?= htmlspecialchars($post['nick_name'] ?? '') ?></span>
                            <span>·</span>
                            <span><?= !empty($post['created_at']) ? date('Y-m-d', strtotime($post['created_at'])) : '' ?></span>
                            <?php if (count($answers) > 0): ?>
                            <span class="inline-flex items-center gap-1 ml-1 text-amber-600 dark:text-amber-400 font-medium">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
                                <?= count($answers) ?>
                            </span>
                            <?php elseif ($showNoAnswerBadge): ?>
                            <span class="ml-1 px-1.5 py-0.5 text-[10px] font-medium rounded bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400">
                                <?= $noAnswerLabel[$_loc] ?? $noAnswerLabel['en'] ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <svg class="qna-chevron w-5 h-5 text-zinc-400 flex-shrink-0 mt-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
                </button>
                <?php if ($canEditPost): ?>
                <a href="<?= $boardUrl ?>/<?= $pid ?>/edit" class="flex-shrink-0 px-3 py-5 text-zinc-400 hover:text-amber-500 dark:hover:text-amber-400 transition" title="<?= __('board.edit') ?? '수정' ?>" onclick="event.stopPropagation()">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                </a>
                <?php endif; ?>
            </div>

            <!-- 답변 영역 (질문 본문 + 댓글들) -->
            <div class="qna-answer">
                <div class="border-t border-zinc-100 dark:border-zinc-700 px-5 py-5 space-y-4">
                    <!-- 질문 본문 -->
                    <?php if (trim(strip_tags($content ?? '')) !== ''): ?>
                    <div class="prose prose-sm dark:prose-invert max-w-none text-zinc-700 dark:text-zinc-300">
                        <?= $content ?>
                    </div>
                    <?php endif; ?>

                    <!-- 답변 (댓글) -->
                    <?php if (!empty($answers)): ?>
                    <div class="pt-3 border-t border-zinc-100 dark:border-zinc-700 space-y-3">
                        <?php foreach ($answers as $a):
                            $cd = (int)($a['depth'] ?? 0);
                            $isReply = $cd > 0;
                            $indent = $cd > 0 ? 'ml-' . min($cd * 8, 16) : '';
                            $isOwn = $currentUser && !empty($a['user_id']) && (string)$a['user_id'] === (string)($currentUser['id'] ?? '');
                            $canEditCmt = $isOwn || $isAdmin;
                        ?>
                        <div class="qna-cmt <?= $indent ?> flex items-start gap-3" data-cid="<?= $a['id'] ?>">
                            <span class="<?= $isReply ? 'text-xs font-semibold text-zinc-400 dark:text-zinc-500 mt-1' : 'qna-a-icon text-xl' ?> flex-shrink-0">
                                <?= $isReply ? '↳ 답글' : 'A.' ?>
                            </span>
                            <div class="flex-1 min-w-0">
                                <div class="flex items-center gap-2 mb-1 text-xs text-zinc-500 dark:text-zinc-400">
                                    <span class="font-medium text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($a['nick_name'] ?? '') ?></span>
                                    <span>·</span>
                                    <span><?= !empty($a['created_at']) ? date('Y-m-d H:i', strtotime($a['created_at'])) : '' ?></span>
                                    <span class="ml-auto inline-flex items-center gap-1">
                                        <?php if ($canComment): ?>
                                        <button type="button" class="qna-reply-btn text-zinc-400 hover:text-blue-500 transition" data-cid="<?= $a['id'] ?>" title="<?= __('board.reply') ?? '답글 달기' ?>">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a8 8 0 018 8v2M3 10l6 6m-6-6l6-6"/></svg>
                                        </button>
                                        <?php endif; ?>
                                        <?php if ($canEditCmt): ?>
                                        <button type="button" class="qna-edit-btn text-zinc-400 hover:text-amber-500 transition" data-cid="<?= $a['id'] ?>" title="<?= __('board.edit') ?? '수정' ?>">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                        </button>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <div class="qna-cmt-body text-sm text-zinc-700 dark:text-zinc-300 whitespace-pre-line"><?= nl2br(htmlspecialchars($a['content'] ?? '')) ?></div>

                                <?php if ($canEditCmt): ?>
                                <div class="qna-edit-form hidden mt-2">
                                    <textarea class="qna-edit-text w-full px-2 py-1.5 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded resize-none" rows="3"><?= htmlspecialchars($a['content'] ?? '') ?></textarea>
                                    <div class="flex gap-2 mt-1">
                                        <button type="button" class="qna-edit-save px-3 py-1 text-xs font-medium text-white rounded" style="background: var(--qna-primary);" data-cid="<?= $a['id'] ?>"><?= __('common.save') ?? '저장' ?></button>
                                        <button type="button" class="qna-edit-cancel px-3 py-1 text-xs bg-zinc-200 dark:bg-zinc-600 text-zinc-700 dark:text-zinc-300 rounded"><?= __('common.cancel') ?? '취소' ?></button>
                                    </div>
                                </div>
                                <?php endif; ?>

                                <?php if ($canComment): ?>
                                <div class="qna-reply-form hidden mt-2">
                                    <textarea class="qna-reply-text w-full px-2 py-1.5 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded resize-none" rows="2" placeholder="<?= __('board.reply_placeholder') ?? '답글을 입력하세요' ?>"></textarea>
                                    <div class="flex gap-2 mt-1">
                                        <button type="button" class="qna-reply-submit px-3 py-1 text-xs font-medium text-white rounded" style="background: var(--qna-primary);" data-cid="<?= $a['id'] ?>" data-pid="<?= $pid ?>"><?= __('board.submit_reply') ?? '답글 달기' ?></button>
                                        <button type="button" class="qna-reply-cancel px-3 py-1 text-xs bg-zinc-200 dark:bg-zinc-600 text-zinc-700 dark:text-zinc-300 rounded"><?= __('common.cancel') ?? '취소' ?></button>
                                    </div>
                                </div>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>

                    <!-- 게시글 상세 + 댓글 작성 링크 -->
                    <div class="pt-3 border-t border-zinc-100 dark:border-zinc-700 flex items-center justify-end">
                        <a href="<?= $boardUrl ?>/<?= $pid ?>" class="text-xs text-zinc-500 dark:text-zinc-400 hover:underline" style="color: var(--qna-primary);">
                            상세보기 / 답변 달기 →
                        </a>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- 페이지네이션 -->
    <?php if (($totalPages ?? 1) > 1): ?>
    <div class="flex items-center justify-center gap-1 mt-8">
        <?php
        $currentPage = (int)($page ?? 1);
        $startPage = max(1, $currentPage - 2);
        $endPage   = min($totalPages, $startPage + 4);
        $startPage = max(1, $endPage - 4);

        $pageQuery = function(int $p) {
            $q = $_GET; $q['page'] = $p;
            return '?' . http_build_query($q);
        };
        ?>
        <?php if ($currentPage > 1): ?>
        <a href="<?= $pageQuery($currentPage - 1) ?>" class="px-3 py-2 text-sm rounded-lg bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50">&lt;</a>
        <?php endif; ?>
        <?php for ($p = $startPage; $p <= $endPage; $p++): ?>
        <a href="<?= $pageQuery($p) ?>"
           class="px-3.5 py-2 text-sm rounded-lg border <?= $p === $currentPage ? 'qna-page-active border-transparent' : 'bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50' ?>">
            <?= $p ?>
        </a>
        <?php endfor; ?>
        <?php if ($currentPage < $totalPages): ?>
        <a href="<?= $pageQuery($currentPage + 1) ?>" class="px-3 py-2 text-sm rounded-lg bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50">&gt;</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <!-- 글쓰기 (질문하기) -->
    <?php if (boardCheckPerm($board, 'perm_write', $currentUser ?? null)): ?>
    <div class="mt-8 text-center">
        <a href="<?= $boardUrl ?>/write" class="inline-flex items-center gap-2 px-6 py-3 rounded-xl text-white font-medium transition hover:opacity-90"
           style="background: var(--qna-primary);">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <?= __('board.write') ?? '질문하기' ?>
        </a>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>

<script>
(function () {
    const apiUrl = '<?= ($config['app_url'] ?? '') ?>/board/api/comments';
    const boardId = <?= (int)$board['id'] ?>;

    // 답글/편집 버튼 클릭 시 아코디언 토글 차단
    document.querySelectorAll('.qna-skin .qna-cmt button, .qna-skin .qna-cmt textarea').forEach(el => {
        el.addEventListener('click', e => e.stopPropagation());
    });

    // 편집 토글
    document.querySelectorAll('.qna-skin .qna-edit-btn').forEach(btn => {
        btn.addEventListener('click', e => {
            e.stopPropagation();
            const cmt = btn.closest('.qna-cmt');
            cmt.querySelector('.qna-edit-form')?.classList.toggle('hidden');
            cmt.querySelector('.qna-reply-form')?.classList.add('hidden');
        });
    });
    document.querySelectorAll('.qna-skin .qna-edit-cancel').forEach(btn => {
        btn.addEventListener('click', e => {
            e.stopPropagation();
            btn.closest('.qna-edit-form').classList.add('hidden');
        });
    });
    document.querySelectorAll('.qna-skin .qna-edit-save').forEach(btn => {
        btn.addEventListener('click', async e => {
            e.stopPropagation();
            const cid = btn.dataset.cid;
            const form = btn.closest('.qna-edit-form');
            const text = form.querySelector('.qna-edit-text').value.trim();
            if (!text) { alert('내용을 입력해주세요.'); return; }
            try {
                const resp = await fetch(apiUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=update&comment_id=' + cid + '&content=' + encodeURIComponent(text)
                });
                const data = await resp.json();
                if (data.success) {
                    const cmt = btn.closest('.qna-cmt');
                    cmt.querySelector('.qna-cmt-body').innerHTML = (data.content || text).replace(/\n/g, '<br>');
                    form.classList.add('hidden');
                } else {
                    alert(data.message || '수정에 실패했습니다.');
                }
            } catch (err) { alert('Error: ' + err.message); }
        });
    });

    // 답글 토글
    document.querySelectorAll('.qna-skin .qna-reply-btn').forEach(btn => {
        btn.addEventListener('click', e => {
            e.stopPropagation();
            const cmt = btn.closest('.qna-cmt');
            cmt.querySelector('.qna-reply-form')?.classList.toggle('hidden');
            cmt.querySelector('.qna-edit-form')?.classList.add('hidden');
        });
    });
    document.querySelectorAll('.qna-skin .qna-reply-cancel').forEach(btn => {
        btn.addEventListener('click', e => {
            e.stopPropagation();
            btn.closest('.qna-reply-form').classList.add('hidden');
        });
    });
    document.querySelectorAll('.qna-skin .qna-reply-submit').forEach(btn => {
        btn.addEventListener('click', async e => {
            e.stopPropagation();
            const cid = btn.dataset.cid;
            const pid = btn.dataset.pid;
            const form = btn.closest('.qna-reply-form');
            const text = form.querySelector('.qna-reply-text').value.trim();
            if (!text) { alert('내용을 입력해주세요.'); return; }
            try {
                const resp = await fetch(apiUrl, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                    body: 'action=create&post_id=' + pid + '&board_id=' + boardId + '&parent_id=' + cid + '&content=' + encodeURIComponent(text)
                });
                const data = await resp.json();
                if (data.success) {
                    location.reload();
                } else {
                    alert(data.message || '답글 작성에 실패했습니다.');
                }
            } catch (err) { alert('Error: ' + err.message); }
        });
    });
})();
</script>
