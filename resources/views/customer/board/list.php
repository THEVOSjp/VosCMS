<?php
/**
 * RezlyX 게시판 - 글 목록
 */
include __DIR__ . '/_init.php';

// 권한 확인
if (!boardCheckPerm($board, 'perm_list', $currentUser)) {
    http_response_code(403);
    echo '<p>접근 권한이 없습니다.</p>';
    exit;
}

$pageTitle = $board['title'] . ' - ' . ($config['app_name'] ?? 'RezlyX');

// 페이지네이션
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)($board['per_page'] ?? 20);
$offset = ($page - 1) * $perPage;

// 검색
$searchTarget = $_GET['search_target'] ?? '';
$searchKeyword = trim($_GET['search_keyword'] ?? '');
$categoryFilter = (int)($_GET['category'] ?? 0);

// 상담 모드: 본인 글 + 공지만 표시
$isConsultation = (bool)($board['consultation'] ?? 0);

// 쿼리 빌드
$where = "board_id = ? AND status = 'published'";
$params = [$boardId];

if ($isConsultation && (!$currentUser || empty($_SESSION['admin_id']))) {
    if ($currentUser) {
        $where .= " AND (user_id = ? OR is_notice = 1)";
        $params[] = $currentUser['id'];
    } else {
        // 비로그인: 공지만 표시
        $where .= " AND is_notice = 1";
    }
}

if ($categoryFilter > 0) {
    $where .= " AND category_id = ?";
    $params[] = $categoryFilter;
}

if ($searchKeyword !== '') {
    if ($searchTarget === 'title') {
        $where .= " AND title LIKE ?";
        $params[] = '%' . $searchKeyword . '%';
    } elseif ($searchTarget === 'content') {
        $where .= " AND content LIKE ?";
        $params[] = '%' . $searchKeyword . '%';
    } elseif ($searchTarget === 'nick_name') {
        $where .= " AND nick_name LIKE ?";
        $params[] = '%' . $searchKeyword . '%';
    } else {
        $where .= " AND (title LIKE ? OR content LIKE ?)";
        $params[] = '%' . $searchKeyword . '%';
        $params[] = '%' . $searchKeyword . '%';
    }
}

// 공지사항 (별도)
$notices = [];
if ($board['except_notice'] ?? 0) {
    $noticeStmt = $pdo->prepare("SELECT * FROM {$prefix}board_posts WHERE board_id = ? AND is_notice = 1 AND status = 'published' ORDER BY created_at DESC");
    $noticeStmt->execute([$boardId]);
    $notices = $noticeStmt->fetchAll(PDO::FETCH_ASSOC);
    $where .= " AND is_notice = 0";
}

// 총 개수
$cntStmt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}board_posts WHERE {$where}");
$cntStmt->execute($params);
$totalCount = (int)$cntStmt->fetchColumn();
$totalPages = max(1, ceil($totalCount / $perPage));

// 정렬
$sortField = $board['sort_field'] ?? 'created_at';
$sortDir = ($board['sort_direction'] ?? 'desc') === 'asc' ? 'ASC' : 'DESC';
$allowedSort = ['created_at', 'updated_at', 'view_count', 'vote_count'];
if (!in_array($sortField, $allowedSort)) $sortField = 'created_at';

$listStmt = $pdo->prepare("SELECT * FROM {$prefix}board_posts WHERE {$where} ORDER BY {$sortField} {$sortDir} LIMIT " . (int)$perPage . " OFFSET " . (int)$offset);
$listStmt->execute($params);
$posts = $listStmt->fetchAll(PDO::FETCH_ASSOC);

// 글 번호 계산
$startNo = $totalCount - $offset;
?>
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-6">
        <!-- 게시판 제목 -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-zinc-800 dark:text-zinc-100 inline-flex items-center gap-2">
                <?= htmlspecialchars($board['title']) ?>
                <?php if (!empty($_SESSION['admin_id'])): ?>
                <a href="<?= $baseUrl ?>/board/<?= htmlspecialchars($board['slug']) ?>/settings" class="text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition" title="<?= __('board.board_settings') ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                </a>
                <?php endif; ?>
            </h1>
            <?php if (!empty($board['description'])): ?>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400"><?= htmlspecialchars($board['description']) ?></p>
            <?php endif; ?>
        </div>

        <?php if (!empty($board['header_content'])): ?>
        <div class="mb-4"><?= $board['header_content'] ?></div>
        <?php endif; ?>

        <!-- 카테고리 필터 -->
        <?php if (!($board['hide_categories'] ?? 0) && !empty($categories)): ?>
        <div class="flex flex-wrap gap-2 mb-4">
            <a href="<?= $boardUrl ?>" class="px-3 py-1.5 text-sm rounded-full <?= !$categoryFilter ? 'bg-blue-600 text-white' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-300 dark:hover:bg-zinc-600' ?> transition"><?= __('board.all') ?></a>
            <?php foreach ($categories as $cat): ?>
            <a href="<?= $boardUrl ?>?category=<?= $cat['id'] ?>" class="px-3 py-1.5 text-sm rounded-full <?= $categoryFilter === (int)$cat['id'] ? 'bg-blue-600 text-white' : 'bg-zinc-200 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-300 dark:hover:bg-zinc-600' ?> transition">
                <?php if (!empty($cat['color'])): ?><span class="inline-block w-2 h-2 rounded-full mr-1" style="background:<?= htmlspecialchars($cat['color']) ?>"></span><?php endif; ?>
                <?= htmlspecialchars($cat['name']) ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- 글 목록 테이블 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <table class="w-full text-sm">
                <thead>
                    <tr class="bg-zinc-50 dark:bg-zinc-700/50 border-b border-zinc-200 dark:border-zinc-700">
                        <?php foreach ($listColumns as $col): ?>
                        <?php if ($col === 'no'): ?><th class="w-20 py-3 px-2 text-center font-medium text-zinc-600 dark:text-zinc-400"><?= __('board.col_no') ?></th>
                        <?php elseif ($col === 'title'): ?><th class="py-3 px-4 text-left font-medium text-zinc-600 dark:text-zinc-400"><?= __('board.col_title') ?></th>
                        <?php elseif ($col === 'category'): ?><th class="w-24 py-3 px-4 text-center font-medium text-zinc-600 dark:text-zinc-400"><?= __('board.col_category') ?></th>
                        <?php elseif ($col === 'nick_name'): ?><th class="w-28 py-3 px-4 text-center font-medium text-zinc-600 dark:text-zinc-400"><?= __('board.col_author') ?></th>
                        <?php elseif ($col === 'created_at'): ?><th class="w-28 py-3 px-4 text-center font-medium text-zinc-600 dark:text-zinc-400"><?= __('board.col_date') ?></th>
                        <?php elseif ($col === 'view_count'): ?><th class="w-20 py-3 px-4 text-center font-medium text-zinc-600 dark:text-zinc-400"><?= __('board.col_views') ?></th>
                        <?php elseif ($col === 'vote_count'): ?><th class="w-20 py-3 px-4 text-center font-medium text-zinc-600 dark:text-zinc-400"><?= __('board.col_votes') ?></th>
                        <?php elseif ($col === 'comment_count'): ?><th class="w-16 py-3 px-4 text-center font-medium text-zinc-600 dark:text-zinc-400"><?= __('board.col_comments') ?></th>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    // 목록 게시글 제목 다국어 번역 일괄 로드
                    $allPostIds = array_merge(array_column($notices, 'id'), array_column($posts, 'id'));
                    $postTitleTranslations = [];
                    if (!empty($allPostIds) && ($currentLocale ?? 'ko') !== 'ko') {
                        // 현재 로케일 번역 조회
                        $placeholders = implode(',', array_map(fn($id) => "'board_post.{$id}.title'", $allPostIds));
                        $trRows = $pdo->query("SELECT lang_key, content FROM {$prefix}translations WHERE lang_key IN ({$placeholders}) AND locale = '{$currentLocale}'")->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($trRows as $tr) {
                            preg_match('/board_post\.(\d+)\.title/', $tr['lang_key'], $m);
                            if ($m) $postTitleTranslations[(int)$m[1]] = $tr['content'];
                        }
                        // 현재 로케일에 없으면 영어 폴백
                        if ($currentLocale !== 'en') {
                            $missingIds = array_diff($allPostIds, array_keys($postTitleTranslations));
                            if (!empty($missingIds)) {
                                $placeholders2 = implode(',', array_map(fn($id) => "'board_post.{$id}.title'", $missingIds));
                                $enRows = $pdo->query("SELECT lang_key, content FROM {$prefix}translations WHERE lang_key IN ({$placeholders2}) AND locale = 'en'")->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($enRows as $tr) {
                                    preg_match('/board_post\.(\d+)\.title/', $tr['lang_key'], $m);
                                    if ($m && !isset($postTitleTranslations[(int)$m[1]])) $postTitleTranslations[(int)$m[1]] = $tr['content'];
                                }
                            }
                        }
                    }

                    // 공지사항 먼저
                    foreach ($notices as $post) {
                        $post['_is_notice_row'] = true;
                        if (isset($postTitleTranslations[$post['id']])) $post['title'] = $postTitleTranslations[$post['id']];
                        include __DIR__ . '/_list-row.php';
                    }
                    // 일반 글
                    $rowNo = $startNo;
                    foreach ($posts as $post) {
                        if (isset($postTitleTranslations[$post['id']])) $post['title'] = $postTitleTranslations[$post['id']];
                        $post['_row_no'] = $rowNo--;
                        include __DIR__ . '/_list-row.php';
                    }
                    ?>
                    <?php if (empty($notices) && empty($posts)): ?>
                    <tr><td colspan="<?= count($listColumns) ?>" class="py-12 text-center text-zinc-400 dark:text-zinc-500"><?= __('board.no_posts') ?></td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- 페이지네이션 + 버튼 -->
        <div class="flex items-center justify-between mt-4">
            <!-- 페이지네이션 -->
            <div class="flex items-center gap-1">
                <?php if ($page > 1): ?>
                <a href="<?= $boardUrl ?>?page=<?= $page - 1 ?>" class="px-3 py-1.5 text-sm bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700">&laquo;</a>
                <?php endif; ?>
                <?php
                $pageCount = (int)($board['page_count'] ?? 10);
                $startPage = max(1, $page - intdiv($pageCount, 2));
                $endPage = min($totalPages, $startPage + $pageCount - 1);
                $startPage = max(1, $endPage - $pageCount + 1);
                for ($p = $startPage; $p <= $endPage; $p++):
                ?>
                <a href="<?= $boardUrl ?>?page=<?= $p ?><?= $categoryFilter ? '&category=' . $categoryFilter : '' ?>" class="px-3 py-1.5 text-sm rounded-lg <?= $p === $page ? 'bg-blue-600 text-white' : 'bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700' ?>"><?= $p ?></a>
                <?php endfor; ?>
                <?php if ($page < $totalPages): ?>
                <a href="<?= $boardUrl ?>?page=<?= $page + 1 ?>" class="px-3 py-1.5 text-sm bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700">&raquo;</a>
                <?php endif; ?>
            </div>

            <!-- 글쓰기 버튼 -->
            <?php if (boardCheckPerm($board, 'perm_write', $currentUser)): ?>
            <a href="<?= $boardUrl ?>/write" class="px-5 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition"><?= __('board.write') ?></a>
            <?php endif; ?>
        </div>

        <!-- 검색 -->
        <form method="GET" action="<?= $boardUrl ?>" class="mt-4 flex items-center justify-center gap-2">
            <select name="search_target" class="px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-700 dark:text-zinc-300">
                <option value="" <?= $searchTarget === '' ? 'selected' : '' ?>><?= __('board.search_all') ?></option>
                <option value="title" <?= $searchTarget === 'title' ? 'selected' : '' ?>><?= __('board.search_title') ?></option>
                <option value="content" <?= $searchTarget === 'content' ? 'selected' : '' ?>><?= __('board.search_content') ?></option>
                <option value="nick_name" <?= $searchTarget === 'nick_name' ? 'selected' : '' ?>><?= __('board.search_author') ?></option>
            </select>
            <input type="text" name="search_keyword" value="<?= htmlspecialchars($searchKeyword) ?>" placeholder="<?= __('board.search_placeholder') ?>"
                   class="w-64 px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200">
            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-zinc-600 hover:bg-zinc-700 rounded-lg transition"><?= __('board.search') ?></button>
        </form>

        <?php if (!empty($board['footer_content'])): ?>
        <div class="mt-4"><?= $board['footer_content'] ?></div>
        <?php endif; ?>
    </div>

