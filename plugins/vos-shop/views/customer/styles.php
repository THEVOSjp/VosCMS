<?php
/**
 * 스타일북 전체 목록 페이지
 * /styles — 태그 필터, 무한 스크롤, 검색
 */

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$currentLocale = $config['locale'] ?? (function_exists('current_locale') ? current_locale() : 'ko');

// AJAX: 좋아요 토글 / 신고
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'] ?? '';
    require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
    if (!\RzxLib\Core\Auth\Auth::check()) { echo json_encode(['error' => 'login_required']); exit; }
    $userId = \RzxLib\Core\Auth\Auth::user()['id'];
    $postId = (int)($_POST['post_id'] ?? 0);

    if ($action === 'toggle_like' && $postId) {
        $chk = $pdo->prepare("SELECT id FROM {$prefix}style_likes WHERE style_post_id = ? AND user_id = ?");
        $chk->execute([$postId, $userId]);
        if ($chk->fetch()) {
            $pdo->prepare("DELETE FROM {$prefix}style_likes WHERE style_post_id = ? AND user_id = ?")->execute([$postId, $userId]);
            $pdo->prepare("UPDATE {$prefix}style_posts SET like_count = GREATEST(like_count - 1, 0) WHERE id = ?")->execute([$postId]);
            $liked = false;
        } else {
            $pdo->prepare("INSERT IGNORE INTO {$prefix}style_likes (style_post_id, user_id) VALUES (?, ?)")->execute([$postId, $userId]);
            $pdo->prepare("UPDATE {$prefix}style_posts SET like_count = like_count + 1 WHERE id = ?")->execute([$postId]);
            $liked = true;
        }
        $cnt = (int)$pdo->prepare("SELECT like_count FROM {$prefix}style_posts WHERE id = ?")->execute([$postId]) ? $pdo->query("SELECT like_count FROM {$prefix}style_posts WHERE id = {$postId}")->fetchColumn() : 0;
        echo json_encode(['success' => true, 'liked' => $liked, 'count' => $cnt]);
        exit;
    }
    if ($action === 'report' && $postId) {
        $reason = trim($_POST['reason'] ?? '');
        $pdo->prepare("UPDATE {$prefix}style_posts SET status = 'reported' WHERE id = ? AND status = 'active'")->execute([$postId]);
        echo json_encode(['success' => true]);
        exit;
    }
    if ($action === 'delete' && $postId) {
        // 본인 또는 관리자만
        $post = $pdo->prepare("SELECT user_id FROM {$prefix}style_posts WHERE id = ?");
        $post->execute([$postId]);
        $postOwner = $post->fetchColumn();
        $user = \RzxLib\Core\Auth\Auth::user();
        $isAdmin = in_array($user['role'] ?? '', ['admin', 'supervisor', 'manager']);
        if ($postOwner === $userId || $isAdmin) {
            $pdo->prepare("DELETE FROM {$prefix}style_posts WHERE id = ?")->execute([$postId]);
            $pdo->prepare("DELETE FROM {$prefix}style_likes WHERE style_post_id = ?")->execute([$postId]);
            $pdo->prepare("DELETE FROM {$prefix}style_comments WHERE style_post_id = ?")->execute([$postId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'forbidden']);
        }
        exit;
    }
    if ($action === 'add_comment' && $postId) {
        $content = trim($_POST['content'] ?? '');
        if ($content) {
            $pdo->prepare("INSERT INTO {$prefix}style_comments (style_post_id, user_id, content) VALUES (?, ?, ?)")->execute([$postId, $userId, $content]);
            $pdo->prepare("UPDATE {$prefix}style_posts SET comment_count = comment_count + 1 WHERE id = ?")->execute([$postId]);
            echo json_encode(['success' => true]);
        } else {
            echo json_encode(['error' => 'empty']);
        }
        exit;
    }
    echo json_encode(['error' => 'unknown']);
    exit;
}

// 플러그인 번역 로드
$_shopLang = @include(BASE_PATH . '/plugins/vos-shop/lang/' . $currentLocale . '/shop.php');
if (!is_array($_shopLang)) $_shopLang = @include(BASE_PATH . '/plugins/vos-shop/lang/ko/shop.php');
if (is_array($_shopLang) && class_exists('\RzxLib\Core\I18n\Translator')) {
    \RzxLib\Core\I18n\Translator::merge('shop', $_shopLang);
}

$pageTitle = __('shop.stylebook.title') ?? '스타일북';
$seoContext = ['type' => 'sub', 'subpage_title' => $pageTitle];

// 관리자 여부 확인
$_stylesIsAdmin = false;
try {
    require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
    if (\RzxLib\Core\Auth\Auth::check()) {
        $_stylesUser = \RzxLib\Core\Auth\Auth::user();
        $_stylesIsAdmin = in_array($_stylesUser['role'] ?? '', ['admin', 'supervisor', 'manager']);
    }
} catch (\Throwable $e) {}

// 필터
$filterTag = trim($_GET['tag'] ?? '');
$filterCategory = trim($_GET['category'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// AJAX 요청 (무한 스크롤)
$_isAjax = !empty($_SERVER['HTTP_X_REQUESTED_WITH']);

// 데이터 로드
$stylePosts = [];
$totalCount = 0;
$allTags = [];

try {
    $where = ["sp.status = 'active'"];
    $params = [];

    if ($filterCategory) {
        $where[] = "sp.category = ?";
        $params[] = $filterCategory;
    }
    if ($filterTag) {
        $where[] = "JSON_CONTAINS(sp.tags, ?)";
        $params[] = json_encode($filterTag);
    }

    $whereSQL = implode(' AND ', $where);

    // 총 건수
    $cntStmt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}style_posts sp WHERE {$whereSQL}");
    $cntStmt->execute($params);
    $totalCount = (int)$cntStmt->fetchColumn();

    // 데이터
    $stmt = $pdo->prepare("
        SELECT sp.*, s.name as shop_name, s.slug as shop_slug, s.cover_image as shop_image
        FROM {$prefix}style_posts sp
        LEFT JOIN {$prefix}shops s ON sp.shop_id = s.id
        WHERE {$whereSQL}
        ORDER BY sp.created_at DESC
        LIMIT {$perPage} OFFSET {$offset}
    ");
    $stmt->execute($params);
    $stylePosts = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // DB에서 태그 목록 로드
    $tagStmt = $pdo->query("SELECT slug, name FROM {$prefix}style_tags WHERE is_active = 1 ORDER BY sort_order");
    while ($tr = $tagStmt->fetch(PDO::FETCH_ASSOC)) {
        $tName = json_decode($tr['name'], true) ?: [];
        $allTags[$tr['slug']] = $tName[$currentLocale] ?? $tName['ko'] ?? $tr['slug'];
    }

} catch (\Throwable $e) {}

// DB 데이터 없으면 샘플
if (empty($stylePosts) && !$filterTag && !$filterCategory) {
    $_sampleFiles = [
        ['file' => 'short-cut.jpg', 'tags' => ['숏컷','short'], 'name' => 'Short Cut', 'likes' => 234],
        ['file' => 'layered.jpg', 'tags' => ['레이어드','layered'], 'name' => 'Layered Cut', 'likes' => 189],
        ['file' => 'perm-curl.jpg', 'tags' => ['펌','컬','perm'], 'name' => 'Volume Perm', 'likes' => 312],
        ['file' => 'long-straight.jpg', 'tags' => ['롱','스트레이트','long'], 'name' => 'Long Straight', 'likes' => 156],
        ['file' => 'color-blonde.jpg', 'tags' => ['컬러','블론드','color'], 'name' => 'Blonde Color', 'likes' => 278],
        ['file' => 'balayage.jpg', 'tags' => ['발레아쥬','컬러','balayage'], 'name' => 'Balayage', 'likes' => 198],
        ['file' => 'men-cut.jpg', 'tags' => ['남성','숏','men'], 'name' => "Men's Cut", 'likes' => 143],
        ['file' => 'bob-cut.jpg', 'tags' => ['보브','미디엄','bob'], 'name' => 'Bob Cut', 'likes' => 167],
    ];
    foreach ($_sampleFiles as $i => $s) {
        $stylePosts[] = [
            'id' => $i + 1, 'images' => json_encode([['url' => 'widgets/stylebook/samples/' . $s['file'], 'type' => 'result']]),
            'tags' => json_encode($s['tags']), 'content' => $s['name'], 'like_count' => $s['likes'],
            'shop_name' => 'Sample Salon', 'shop_slug' => '', 'staff_name' => 'Designer', 'category' => 'hair', 'created_at' => date('Y-m-d H:i:s'),
        ];
    }
    $allTags = ['숏컷' => 2, '레이어드' => 1, '펌' => 1, '컬러' => 2, '롱' => 1, '남성' => 1, '보브' => 1, '발레아쥬' => 1];
    $totalCount = count($stylePosts);
}

$totalPages = max(1, ceil($totalCount / $perPage));

// AJAX 응답 (무한 스크롤)
if ($_isAjax) {
    header('Content-Type: application/json; charset=utf-8');
    $html = '';
    foreach ($stylePosts as $post) {
        $images = json_decode($post['images'] ?? '[]', true) ?: [];
        $firstImg = $images[0]['url'] ?? '';
        $firstMedia = $images[0] ?? [];
        $tags = json_decode($post['tags'] ?? '[]', true) ?: [];
        $html .= '<div class="style-card group cursor-pointer relative rounded-xl overflow-hidden bg-zinc-100 dark:bg-zinc-800 aspect-[3/4]" data-tags="' . htmlspecialchars(implode(',', $tags)) . '" data-id="' . $post['id'] . '">';
        $isVid = ($firstMedia['media'] ?? '') === 'video' || preg_match('/\.(mp4|mov|webm)$/i', $firstImg);
        if ($firstImg && $isVid) {
            $html .= '<video src="' . $baseUrl . '/' . ltrim($firstImg, '/') . '" class="w-full h-full object-cover" muted playsinline preload="metadata" onmouseenter="this.play()" onmouseleave="this.pause();this.currentTime=0"></video>';
            $html .= '<div class="absolute top-3 left-3 w-7 h-7 bg-black/50 rounded-full flex items-center justify-center pointer-events-none"><svg class="w-3.5 h-3.5 text-white ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg></div>';
        } elseif ($firstImg) {
            $html .= '<img src="' . $baseUrl . '/' . ltrim($firstImg, '/') . '" alt="" class="w-full h-full object-cover" loading="lazy">';
        }
        if ($_stylesIsAdmin) {
            $html .= '<button onclick="event.stopPropagation();adminDelete(' . $post['id'] . ',this)" class="absolute top-2 right-2 w-7 h-7 bg-red-500/80 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600 z-10"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg></button>';
        }
        $html .= '<div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300"><div class="absolute bottom-0 left-0 right-0 p-3">';
        if (!empty($tags)) {
            $html .= '<div class="flex flex-wrap gap-1 mb-2">';
            foreach (array_slice($tags, 0, 3) as $t) { $html .= '<span class="text-[10px] px-2 py-0.5 bg-white/20 backdrop-blur-sm text-white rounded-full">#' . htmlspecialchars($t) . '</span>'; }
            $html .= '</div>';
        }
        $html .= '<div class="flex items-center justify-between"><div class="text-white text-xs truncate">';
        if ($post['shop_name']) $html .= '<span class="font-medium">' . htmlspecialchars($post['shop_name']) . '</span>';
        if ($post['staff_name']) $html .= '<span class="opacity-70"> · ' . htmlspecialchars($post['staff_name']) . '</span>';
        $html .= '</div><span class="text-white text-xs flex items-center gap-1"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>' . number_format($post['like_count'] ?? 0) . '</span>';
        $html .= '</div></div></div></div>';
    }
    echo json_encode(['success' => true, 'html' => $html, 'hasMore' => $page < $totalPages]);
    exit;
}
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <!-- 뒤로가기 -->
    <a href="<?= $baseUrl ?>/Hair" class="inline-flex items-center gap-1 text-sm text-zinc-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition mb-4">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        <?= __('common.back') ?? '돌아가기' ?>
    </a>

    <!-- 헤더 -->
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($pageTitle) ?></h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1"><?= __('shop.stylebook.subtitle') ?? '마음에 드는 스타일을 찾아보세요' ?></p>
        </div>
        <a href="<?= $baseUrl ?>/styles/create" class="inline-flex items-center gap-2 px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <?= __('shop.stylebook.create_btn') ?? '스타일 등록' ?>
        </a>
    </div>

    <!-- 태그 필터 -->
    <?php if (!empty($allTags)): ?>
    <div class="flex flex-wrap gap-2 mb-6">
        <a href="<?= $baseUrl ?>/styles" class="px-4 py-2 text-sm rounded-full transition <?= !$filterTag ? 'bg-blue-500 text-white' : 'border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-400 hover:border-blue-400' ?>">
            <?= __('common.all') ?? '전체' ?>
        </a>
        <?php foreach ($allTags as $tag => $cnt): ?>
        <a href="<?= $baseUrl ?>/styles?tag=<?= urlencode($tag) ?>" class="px-4 py-2 text-sm rounded-full transition <?= $filterTag === $tag ? 'bg-blue-500 text-white' : 'border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-400 hover:border-blue-400' ?>">
            <?= htmlspecialchars($cnt) ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- 그리드 -->
    <div id="stylesGrid" class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
        <?php foreach ($stylePosts as $post):
            $images = json_decode($post['images'] ?? '[]', true) ?: [];
            $firstImg = $images[0]['url'] ?? '';
            $tags = json_decode($post['tags'] ?? '[]', true) ?: [];
        ?>
        <?php
            $firstMedia = $images[0] ?? [];
            $isVideo = ($firstMedia['media'] ?? '') === 'video' || preg_match('/\.(mp4|mov|webm)$/i', $firstImg);
        ?>
        <div class="style-card group cursor-pointer relative rounded-xl overflow-hidden bg-zinc-100 dark:bg-zinc-800 aspect-[3/4]" data-tags="<?= htmlspecialchars(implode(',', $tags)) ?>" data-id="<?= $post['id'] ?>">
            <?php if ($firstImg && $isVideo): ?>
            <video src="<?= $baseUrl . '/' . ltrim(htmlspecialchars($firstImg), '/') ?>" class="w-full h-full object-cover" muted playsinline preload="metadata" onmouseenter="this.play()" onmouseleave="this.pause();this.currentTime=0"></video>
            <div class="absolute top-3 left-3 w-7 h-7 bg-black/50 rounded-full flex items-center justify-center pointer-events-none">
                <svg class="w-3.5 h-3.5 text-white ml-0.5" fill="currentColor" viewBox="0 0 24 24"><path d="M8 5v14l11-7z"/></svg>
            </div>
            <?php elseif ($firstImg): ?>
            <img src="<?= $baseUrl . '/' . ltrim(htmlspecialchars($firstImg), '/') ?>" alt="" class="w-full h-full object-cover" loading="lazy">
            <?php else: ?>
            <div class="w-full h-full flex items-center justify-center text-zinc-300"><svg class="w-12 h-12" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg></div>
            <?php endif; ?>
            <?php if ($_stylesIsAdmin): ?>
            <button onclick="event.stopPropagation();adminDelete(<?= $post['id'] ?>,this)" class="absolute top-2 right-2 w-7 h-7 bg-red-500/80 text-white rounded-full flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600 z-10" title="<?= __('common.buttons.delete') ?? '삭제' ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
            </button>
            <?php endif; ?>
            <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-transparent to-transparent opacity-0 group-hover:opacity-100 transition-opacity duration-300">
                <div class="absolute bottom-0 left-0 right-0 p-3">
                    <?php if (!empty($tags)): ?>
                    <div class="flex flex-wrap gap-1 mb-2">
                        <?php foreach (array_slice($tags, 0, 3) as $t): ?>
                        <span class="text-[10px] px-2 py-0.5 bg-white/20 backdrop-blur-sm text-white rounded-full">#<?= htmlspecialchars($t) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <div class="flex items-center justify-between">
                        <div class="text-white text-xs truncate">
                            <?php if ($post['shop_name']): ?><span class="font-medium"><?= htmlspecialchars($post['shop_name']) ?></span><?php endif; ?>
                            <?php if ($post['staff_name']): ?><span class="opacity-70"> · <?= htmlspecialchars($post['staff_name']) ?></span><?php endif; ?>
                        </div>
                        <span class="text-white text-xs flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 24 24"><path d="M12 21.35l-1.45-1.32C5.4 15.36 2 12.28 2 8.5 2 5.42 4.42 3 7.5 3c1.74 0 3.41.81 4.5 2.09C13.09 3.81 14.76 3 16.5 3 19.58 3 22 5.42 22 8.5c0 3.78-3.4 6.86-8.55 11.54L12 21.35z"/></svg>
                            <?= number_format($post['like_count'] ?? 0) ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <?php if (empty($stylePosts)): ?>
    <div class="text-center py-16 text-zinc-400">
        <svg class="w-16 h-16 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <p><?= __('shop.stylebook.empty') ?? '아직 등록된 스타일이 없습니다.' ?></p>
    </div>
    <?php endif; ?>

    <?php
    // 더보기 최대 5페이지(100개)까지, 이후 페이지 네비게이션
    $maxLoadMore = 5; // 더보기로 로드할 최대 페이지 (그룹 내)
    $pageGroup = ceil($page / $maxLoadMore);
    $groupEndPage = $pageGroup * $maxLoadMore;
    $showLoadMore = $page < $totalPages && $page < $groupEndPage;
    $showPagination = $totalPages > $maxLoadMore;
    $groupStart = ($pageGroup - 1) * $maxLoadMore + 1;
    $groupEnd = min($pageGroup * $maxLoadMore, $totalPages);
    $totalGroups = ceil($totalPages / $maxLoadMore);
    ?>

    <!-- 더보기 버튼 (100개 미만일 때) -->
    <?php if ($showLoadMore): ?>
    <div id="loadMoreWrap" class="text-center py-6">
        <button id="loadMoreBtn" class="px-6 py-3 border border-zinc-300 dark:border-zinc-600 text-sm font-medium rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
            <?= __('shop.stylebook.load_more') ?? '더보기' ?>
        </button>
    </div>
    <script>
    (function() {
        var curPage = <?= $page ?>;
        var maxPage = <?= min($groupEndPage, $totalPages) ?>;
        var loading = false;
        var btn = document.getElementById('loadMoreBtn');
        if (!btn) return;
        btn.addEventListener('click', function() {
            if (loading) return;
            loading = true;
            btn.textContent = '...';
            curPage++;
            var url = '<?= $baseUrl ?>/styles?page=' + curPage + '<?= $filterTag ? '&tag=' . urlencode($filterTag) : '' ?><?= $filterCategory ? '&category=' . urlencode($filterCategory) : '' ?>';
            fetch(url, { headers: { 'X-Requested-With': 'XMLHttpRequest' }, credentials: 'same-origin' })
            .then(function(r) { return r.json(); })
            .then(function(d) {
                if (d.html) document.getElementById('stylesGrid').insertAdjacentHTML('beforeend', d.html);
                if (curPage >= maxPage || !d.hasMore) document.getElementById('loadMoreWrap').remove();
                loading = false;
                btn.textContent = '<?= __('shop.stylebook.load_more') ?? '더보기' ?>';
            });
        });
    })();
    </script>
    <?php endif; ?>

    <!-- 페이지 네비게이션 (100개 이상일 때) -->
    <?php if ($showPagination): ?>
    <div class="flex items-center justify-center gap-1 mt-6 mb-2">
        <?php if ($pageGroup > 1): ?>
        <a href="<?= $baseUrl ?>/styles?page=<?= $groupStart - 1 ?><?= $filterTag ? '&tag=' . urlencode($filterTag) : '' ?>"
           class="px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <?php endif; ?>

        <?php for ($g = 1; $g <= $totalGroups; $g++):
            $gPage = ($g - 1) * $maxLoadMore + 1;
            $gLabel = (($g - 1) * $maxLoadMore * $perPage + 1) . '~' . min($g * $maxLoadMore * $perPage, $totalCount);
        ?>
        <a href="<?= $baseUrl ?>/styles?page=<?= $gPage ?><?= $filterTag ? '&tag=' . urlencode($filterTag) : '' ?>"
           class="px-3 py-2 text-xs rounded-lg transition <?= $g === $pageGroup ? 'bg-blue-600 text-white' : 'border border-zinc-300 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-800' ?>">
            <?= $gLabel ?>
        </a>
        <?php endfor; ?>

        <?php if ($pageGroup < $totalGroups): ?>
        <a href="<?= $baseUrl ?>/styles?page=<?= $groupEnd + 1 ?><?= $filterTag ? '&tag=' . urlencode($filterTag) : '' ?>"
           class="px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-800 transition">
            <svg class="w-4 h-4 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
        </a>
        <?php endif; ?>
    </div>
    <p class="text-center text-xs text-zinc-400 mb-4"><?= $totalCount ?> <?= __('shop.stylebook.total_count') ?? '개의 스타일' ?></p>
    <?php endif; ?>
</div>
<script>
document.addEventListener('click', function(e) {
    var card = e.target.closest('.style-card');
    if (card && card.dataset.id) {
        location.href = '<?= $baseUrl ?>/styles/' + card.dataset.id;
    }
});
function adminDelete(postId, btn) {
    if (!confirm('<?= __('common.msg.confirm_delete') ?? '삭제하시겠습니까?' ?>')) return;
    var fd = new FormData();
    fd.append('action', 'delete');
    fd.append('post_id', postId);
    fetch('<?= $baseUrl ?>/styles', { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:fd })
    .then(function(r){return r.json()}).then(function(d) {
        if (d.success) {
            var card = btn.closest('.style-card');
            if (card) card.remove();
        }
    });
}
</script>
