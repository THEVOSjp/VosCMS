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
$seoContext = ['type' => 'sub', 'subpage_title' => $board['title'], 'page' => $page];
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
    <?php
    // 스킨 설정 적용
    $_skinPrimaryColor = $skinConfig['primary_color'] ?? '#3B82F6';
    $_skinBgColor = $skinConfig['board_bg_color'] ?? '';
    $_skinBorderRadius = ($skinConfig['border_radius'] ?? 'rounded') === 'square' ? 'rounded-none' : 'rounded-xl';
    $_skinPostsPerRow = max(2, min(6, (int)($skinConfig['posts_per_row'] ?? 3)));
    $_skinUseLinkBoard = ($skinConfig['use_link_board'] ?? 'none') !== 'none';
    $_skinLinkTarget = $skinConfig['link_target'] ?? '_blank';
    $_skinFontAwesome = ($skinConfig['font_awesome'] ?? 'internal') === 'internal';
    $_skinCustomCss = trim($skinConfig['custom_css'] ?? '');
    $_skinTitleBgImage = $skinConfig['title_bg_image'] ?? ($skinConfig['title_image'] ?? '');
    $_skinTitleBgVideo = $skinConfig['title_bg_video'] ?? '';
    $_skinTitleBgType = $skinConfig['title_bg_type'] ?? 'none';
    // 자동 감지: 타입이 none이지만 이미지/동영상 URL이 있으면 자동 설정
    if ($_skinTitleBgType === 'none' && $_skinTitleBgImage) $_skinTitleBgType = 'image';
    if ($_skinTitleBgType === 'none' && $_skinTitleBgVideo) $_skinTitleBgType = 'video';
    // 둘 다 있으면 설정된 타입 우선, 동영상 > 이미지 폴백
    if ($_skinTitleBgType === 'video' && !$_skinTitleBgVideo && $_skinTitleBgImage) $_skinTitleBgType = 'image';
    if ($_skinTitleBgType === 'image' && !$_skinTitleBgImage && $_skinTitleBgVideo) $_skinTitleBgType = 'video';
    $_skinTitleBgHeight = max(100, min(600, (int)($skinConfig['title_bg_height'] ?? 200)));
    $_skinTitleBgOverlay = max(0, min(100, (int)($skinConfig['title_bg_overlay'] ?? 40)));
    $_skinTitleTextColor = $skinConfig['title_text_color'] ?? 'auto';
    $_hasTitleBg = ($_skinTitleBgType === 'image' && $_skinTitleBgImage) || ($_skinTitleBgType === 'video' && $_skinTitleBgVideo);
    $_skinCustomHeader = trim($skinConfig['custom_html_header'] ?? '');
    $_skinCustomFooter = trim($skinConfig['custom_html_footer'] ?? '');
    ?>
    <?php if ($_skinFontAwesome): ?>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.5.1/css/all.min.css">
    <?php endif; ?>
    <?php if ($_skinCustomCss): ?>
    <style><?= $_skinCustomCss ?></style>
    <?php endif; ?>
    <style>
        .board-skin-wrap { --skin-primary: <?= htmlspecialchars($_skinPrimaryColor) ?>; }
        .board-skin-wrap a:not(.vs-btn):not([class*="bg-"]):hover { color: var(--skin-primary); }
        .board-skin-wrap .skin-primary-bg { background-color: var(--skin-primary); }
        .board-skin-wrap .skin-primary-text { color: var(--skin-primary); }
    </style>

    <div class="board-skin-wrap max-w-7xl mx-auto px-4 sm:px-6 py-6">
        <!-- 게시판 제목 (배경 이미지/동영상 지원) -->
        <?php require_once BASE_PATH . '/rzxlib/Core/Helpers/admin-icons.php'; ?>
        <?php if ($_hasTitleBg): ?>
        <?php
            $_txtColorClass = $_skinTitleTextColor === 'white' ? 'text-white' : ($_skinTitleTextColor === 'dark' ? 'text-zinc-800' : 'text-white');
            $_descColorClass = $_skinTitleTextColor === 'dark' ? 'text-zinc-600' : 'text-white/70';
            $_gearColorClass = $_skinTitleTextColor === 'dark' ? 'text-zinc-400 hover:text-zinc-600' : 'text-white/50 hover:text-white';
        ?>
        <div class="relative mb-6 <?= $_skinBorderRadius ?> overflow-hidden" style="height:<?= $_skinTitleBgHeight ?>px">
            <!-- 배경 미디어 -->
            <?php if ($_skinTitleBgType === 'video' && $_skinTitleBgVideo): ?>
            <video autoplay muted loop playsinline class="absolute inset-0 w-full h-full object-cover">
                <source src="<?= htmlspecialchars($_skinTitleBgVideo) ?>" type="video/mp4">
            </video>
            <?php elseif ($_skinTitleBgType === 'image' && $_skinTitleBgImage): ?>
            <img src="<?= htmlspecialchars($_skinTitleBgImage) ?>" alt="" class="absolute inset-0 w-full h-full object-cover">
            <?php endif; ?>
            <!-- 오버레이 -->
            <div class="absolute inset-0 bg-black" style="opacity:<?= $_skinTitleBgOverlay / 100 ?>"></div>
            <!-- 제목 콘텐츠 -->
            <div class="relative z-10 flex flex-col justify-end h-full p-6">
                <h1 class="text-2xl font-bold <?= $_txtColorClass ?> inline-flex items-center gap-2 drop-shadow-sm">
                    <?= htmlspecialchars($board['title']) ?>
                    <?= rzx_admin_icons($baseUrl . '/board/' . htmlspecialchars($board['slug']) . '/settings', '') ?>
                </h1>
                <?php if (!empty($board['description'])): ?>
                <p class="mt-1 text-sm <?= $_descColorClass ?> drop-shadow-sm"><?= htmlspecialchars($board['description']) ?></p>
                <?php endif; ?>
            </div>
        </div>
        <?php else: ?>
        <!-- 기본 제목 (배경 없음) -->
        <div class="mb-6">
            <h1 class="text-2xl font-bold text-zinc-800 dark:text-zinc-100 inline-flex items-center gap-2">
                <?= htmlspecialchars($board['title']) ?>
                <?= rzx_admin_icons($baseUrl . '/board/' . htmlspecialchars($board['slug']) . '/settings', '') ?>
            </h1>
            <?php if (!empty($board['description'])): ?>
            <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400"><?= htmlspecialchars($board['description']) ?></p>
            <?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if (!empty($board['header_content'])): ?>
        <div class="mb-4"><?= $board['header_content'] ?></div>
        <?php endif; ?>

        <!-- 스킨 커스텀 헤더 -->
        <?php if ($_skinCustomHeader): ?>
        <div class="mb-4"><?= $_skinCustomHeader ?></div>
        <?php endif; ?>

        <!-- 스타일 전환 + 카테고리 필터 -->
        <div class="flex items-center justify-between mb-4">
            <div class="flex-1">
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
            </div>
            <!-- 스타일 전환 버튼 -->
            <?php $allowSwitch = !empty($skinConfig['allow_style_switch']) && $skinConfig['allow_style_switch'] !== '0' && $skinConfig['allow_style_switch'] !== false; ?>
            <?php if ($allowSwitch): ?>
            <div class="flex items-center gap-1 ml-4 flex-shrink-0" id="viewStyleBtns">
                <button onclick="setViewStyle('table')" class="vs-btn p-1.5 rounded-lg transition" data-style="table" title="<?= __('board.style_table') ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                </button>
                <button onclick="setViewStyle('webzine')" class="vs-btn p-1.5 rounded-lg transition" data-style="webzine" title="<?= __('board.style_webzine') ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                </button>
                <button onclick="setViewStyle('gallery')" class="vs-btn p-1.5 rounded-lg transition" data-style="gallery" title="<?= __('board.style_gallery') ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                </button>
                <button onclick="setViewStyle('card')" class="vs-btn p-1.5 rounded-lg transition" data-style="card" title="<?= __('board.style_card') ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/></svg>
                </button>
            </div>
            <?php endif; ?>
        </div>

        <!-- 글 목록 테이블 -->
        <div id="viewTable" class="view-mode bg-white dark:bg-zinc-800 <?= $_skinBorderRadius ?> shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
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
                    // 목록 게시글 제목+본문 다국어 번역 일괄 로드 (로케일 → en → 원본)
                    $allPostIds = array_merge(array_column($notices, 'id'), array_column($posts, 'id'));
                    $postTitleTranslations = [];
                    $postContentTranslations = [];
                    $_cl = $currentLocale ?? 'ko';
                    if (!empty($allPostIds)) {
                        // 제목+본문 모두 조회
                        $titleKeys = implode(',', array_map(fn($id) => "'board_post.{$id}.title'", $allPostIds));
                        $contentKeys = implode(',', array_map(fn($id) => "'board_post.{$id}.content'", $allPostIds));
                        $allKeys = $titleKeys . ',' . $contentKeys;

                        // 1. 현재 로케일 번역 조회
                        $trRows = $pdo->query("SELECT lang_key, content FROM {$prefix}translations WHERE lang_key IN ({$allKeys}) AND locale = '{$_cl}'")->fetchAll(PDO::FETCH_ASSOC);
                        foreach ($trRows as $tr) {
                            if (preg_match('/board_post\.(\d+)\.title/', $tr['lang_key'], $m)) {
                                $postTitleTranslations[(int)$m[1]] = $tr['content'];
                            } elseif (preg_match('/board_post\.(\d+)\.content/', $tr['lang_key'], $m)) {
                                $postContentTranslations[(int)$m[1]] = $tr['content'];
                            }
                        }

                        // 2. 영어 폴백
                        if ($_cl !== 'en') {
                            $origLocaleMap = [];
                            foreach (array_merge($notices, $posts) as $_p) {
                                $origLocaleMap[(int)$_p['id']] = $_p['original_locale'] ?? 'ko';
                            }
                            $missingIds = [];
                            foreach ($allPostIds as $_pid) {
                                if (!isset($postTitleTranslations[(int)$_pid]) && ($origLocaleMap[(int)$_pid] ?? 'ko') !== $_cl) {
                                    $missingIds[] = $_pid;
                                }
                            }
                            if (!empty($missingIds)) {
                                $titleKeys2 = implode(',', array_map(fn($id) => "'board_post.{$id}.title'", $missingIds));
                                $contentKeys2 = implode(',', array_map(fn($id) => "'board_post.{$id}.content'", $missingIds));
                                $enRows = $pdo->query("SELECT lang_key, content FROM {$prefix}translations WHERE lang_key IN ({$titleKeys2},{$contentKeys2}) AND locale = 'en'")->fetchAll(PDO::FETCH_ASSOC);
                                foreach ($enRows as $tr) {
                                    if (preg_match('/board_post\.(\d+)\.title/', $tr['lang_key'], $m)) {
                                        if (!isset($postTitleTranslations[(int)$m[1]])) $postTitleTranslations[(int)$m[1]] = $tr['content'];
                                    } elseif (preg_match('/board_post\.(\d+)\.content/', $tr['lang_key'], $m)) {
                                        if (!isset($postContentTranslations[(int)$m[1]])) $postContentTranslations[(int)$m[1]] = $tr['content'];
                                    }
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

        <!-- 웹진형 (스킨 오버라이드 가능) -->
        <?php include boardSkinFile('_list-webzine.php'); ?>

        <!-- 갤러리형 (스킨 오버라이드 가능) -->
        <?php include boardSkinFile('_list-gallery.php'); ?>

        <!-- 카드형 (스킨 오버라이드 가능) -->
        <?php include boardSkinFile('_list-card.php'); ?>

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

        <!-- 스킨 커스텀 푸터 -->
        <?php if ($_skinCustomFooter): ?>
        <div class="mt-4"><?= $_skinCustomFooter ?></div>
        <?php endif; ?>
    </div>

<script>
// 뷰 스타일 전환
var _boardSlug = '<?= htmlspecialchars($board['slug']) ?>';
function setViewStyle(style) {
    document.querySelectorAll('.view-mode').forEach(function(el) { el.classList.add('hidden'); });
    var target = document.getElementById('view' + style.charAt(0).toUpperCase() + style.slice(1));
    if (target) target.classList.remove('hidden');

    document.querySelectorAll('.vs-btn').forEach(function(btn) {
        var isActive = btn.dataset.style === style;
        btn.className = 'vs-btn p-1.5 rounded-lg transition ' + (isActive
            ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400'
            : 'text-zinc-400 dark:text-zinc-500 hover:text-zinc-600 dark:hover:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700');
    });

    localStorage.setItem('board_view_' + _boardSlug, style);
    console.log('[BoardList] View style:', style);
}

// 초기 스타일 복원
(function() {
    var skinDefault = '<?= htmlspecialchars($skinConfig['list_style'] ?? 'table') ?>';
    var allowSwitch = <?= $allowSwitch ? 'true' : 'false' ?>;
    // 스타일 전환 비허용이면 스킨 설정만 사용, 허용이면 localStorage 우선
    var saved = allowSwitch ? (localStorage.getItem('board_view_' + _boardSlug) || skinDefault) : skinDefault;
    setViewStyle(saved);
})();
</script>

