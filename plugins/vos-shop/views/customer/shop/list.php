<?php
/**
 * 업소 목록 페이지
 * /shops — 전체 목록
 * /shops/{category} — 카테고리별 목록
 */

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$currentLocale = $config['locale'] ?? (function_exists('current_locale') ? current_locale() : 'ko');
$perPage = 12;
$page = max(1, (int)($_GET['page'] ?? 1));
$offset = ($page - 1) * $perPage;
$search = trim($_GET['q'] ?? '');
$sort = $_GET['sort'] ?? 'latest';

// 카테고리 목록 로드
$categories = $pdo->query("SELECT id, slug, name, icon FROM {$prefix}shop_categories WHERE is_active = 1 ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC);
$_catMap = [];
foreach ($categories as $c) { $_catMap[$c['slug']] = $c; }

// 현재 카테고리
$currentCat = null;
if (!empty($shopCategory) && isset($_catMap[$shopCategory])) {
    $currentCat = $_catMap[$shopCategory];
}

// 카테고리 이름 (다국어)
$catLabel = '';
if ($currentCat) {
    $catNames = json_decode($currentCat['name'], true);
    $catLabel = $catNames[$currentLocale] ?? $catNames['en'] ?? $catNames['ko'] ?? $currentCat['slug'];
}

$pageTitle = $catLabel ?: (__('shop.list.title') ?? '매장 찾기');
$seoContext = ['type' => 'sub', 'subpage_title' => $pageTitle];

// 쿼리 빌드
$where = ["s.status = 'active'"];
$params = [];

if ($currentCat) {
    $where[] = "s.category_id = ?";
    $params[] = $currentCat['id'];
}

if ($search) {
    $where[] = "(s.name LIKE ? OR s.address LIKE ? OR s.description LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}

$whereSQL = implode(' AND ', $where);

// 정렬
$orderMap = [
    'latest' => 's.created_at DESC',
    'rating' => 's.rating_avg DESC, s.review_count DESC',
    'reviews' => 's.review_count DESC',
    'popular' => 's.favorite_count DESC',
    'views' => 's.view_count DESC',
];
$orderSQL = $orderMap[$sort] ?? $orderMap['latest'];

// 카운트
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}shops s WHERE {$whereSQL}");
$countStmt->execute($params);
$totalCount = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalCount / $perPage));

// 목록 조회
$listStmt = $pdo->prepare("SELECT s.*, c.slug as category_slug, c.name as category_name FROM {$prefix}shops s LEFT JOIN {$prefix}shop_categories c ON s.category_id = c.id WHERE {$whereSQL} ORDER BY {$orderSQL} LIMIT {$perPage} OFFSET {$offset}");
$listStmt->execute($params);
$shops = $listStmt->fetchAll(PDO::FETCH_ASSOC);

// 정렬 라벨
$sortLabels = [
    'latest' => __('shop.list.sort_latest') ?? '최신순',
    'rating' => __('shop.list.sort_rating') ?? '평점순',
    'reviews' => __('shop.list.sort_reviews') ?? '리뷰순',
    'popular' => __('shop.list.sort_popular') ?? '인기순',
    'views' => __('shop.list.sort_views') ?? '조회순',
];

// 특징 태그 라벨
$_shopLang = @include(BASE_PATH . '/plugins/vos-shop/lang/' . $currentLocale . '/shop.php');
if (!is_array($_shopLang)) $_shopLang = @include(BASE_PATH . '/plugins/vos-shop/lang/ko/shop.php');
$_featLabels = $_shopLang['features'] ?? [];
if (is_array($_shopLang) && class_exists('\RzxLib\Core\I18n\Translator')) { \RzxLib\Core\I18n\Translator::merge('shop', $_shopLang); }
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- 헤더 -->
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($pageTitle) ?></h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1"><?= __('shop.list.total') ?? '총' ?> <?= number_format($totalCount) ?> <?= __('shop.list.shops') ?? '매장' ?></p>
    </div>

    <!-- 카테고리 필터 탭 -->
    <div class="flex gap-2 overflow-x-auto pb-3 mb-6 scrollbar-hide" style="scrollbar-width:none">
        <a href="<?= $baseUrl ?>/shops" class="flex-shrink-0 px-4 py-2 rounded-full text-sm font-medium transition <?= !$currentCat ? 'bg-blue-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600' ?>">
            <?= __('common.all') ?? '전체' ?>
        </a>
        <?php foreach ($categories as $cat):
            $cn = json_decode($cat['name'], true);
            $cl = $cn[$currentLocale] ?? $cn['en'] ?? $cn['ko'] ?? $cat['slug'];
            $isActive = ($currentCat && $currentCat['id'] === $cat['id']);
        ?>
        <a href="<?= $baseUrl ?>/shops/<?= htmlspecialchars($cat['slug']) ?>" class="flex-shrink-0 px-4 py-2 rounded-full text-sm font-medium transition <?= $isActive ? 'bg-blue-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-600' ?>">
            <?= htmlspecialchars($cl) ?>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- 검색 + 정렬 -->
    <div class="flex flex-col sm:flex-row gap-3 mb-6">
        <form method="GET" class="flex-1 flex gap-2">
            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="<?= __('shop.list.search_placeholder') ?? '매장명, 지역으로 검색' ?>"
                   class="flex-1 px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500">
            <button type="submit" class="px-4 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition"><?= __('common.buttons.search') ?? '검색' ?></button>
        </form>
        <div class="flex gap-1">
            <?php foreach ($sortLabels as $sk => $sl):
                $sortUrl = '?' . http_build_query(array_merge($_GET, ['sort' => $sk, 'page' => 1]));
            ?>
            <a href="<?= $sortUrl ?>" class="px-3 py-2 text-xs font-medium rounded-lg transition <?= $sort === $sk ? 'bg-zinc-900 dark:bg-white text-white dark:text-zinc-900' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-500 dark:text-zinc-400 hover:bg-zinc-200 dark:hover:bg-zinc-600' ?>"><?= htmlspecialchars($sl) ?></a>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 매장 목록 -->
    <?php if (empty($shops)): ?>
    <div class="py-16 text-center">
        <svg class="w-16 h-16 mx-auto mb-4 text-zinc-200 dark:text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
        <p class="text-zinc-400 dark:text-zinc-500"><?= __('shop.list.empty') ?? '등록된 매장이 없습니다.' ?></p>
        <a href="<?= $baseUrl ?>/shop/register" class="inline-block mt-4 px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition"><?= __('shop.list.register_cta') ?? '매장 등록하기' ?></a>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 xl:grid-cols-6 gap-3">
        <?php foreach ($shops as $s):
            $coverImg = $s['cover_image'] ? ($baseUrl . $s['cover_image']) : '';
            if (!$coverImg) {
                $imgs = json_decode($s['images'] ?? '[]', true) ?: [];
                if (!empty($imgs[0])) $coverImg = $baseUrl . $imgs[0];
            }
            $sCatNames = json_decode($s['category_name'] ?? '{}', true);
            $sCatLabel = $sCatNames[$currentLocale] ?? $sCatNames['en'] ?? $sCatNames['ko'] ?? '';
            $features = json_decode($s['features'] ?? '[]', true) ?: [];
        ?>
        <a href="<?= $baseUrl ?>/shop/<?= htmlspecialchars($s['slug']) ?>" class="group bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden hover:shadow-lg transition-all hover:-translate-y-0.5">
            <!-- 이미지 -->
            <div class="aspect-[16/10] bg-zinc-100 dark:bg-zinc-900 overflow-hidden">
                <?php if ($coverImg): ?>
                <img src="<?= htmlspecialchars($coverImg) ?>" alt="<?= htmlspecialchars($s['name']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                <?php else: ?>
                <div class="w-full h-full flex items-center justify-center">
                    <svg class="w-12 h-12 text-zinc-300 dark:text-zinc-700" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg>
                </div>
                <?php endif; ?>
            </div>
            <!-- 정보 -->
            <div class="p-2.5">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white group-hover:text-blue-600 transition truncate"><?= htmlspecialchars($s['name']) ?></h3>
                <div class="flex items-center gap-1.5 mt-1 text-[10px] text-zinc-400">
                    <?php if ($s['rating_avg'] > 0): ?>
                    <span class="text-amber-500">⭐<?= number_format($s['rating_avg'], 1) ?></span>
                    <?php endif; ?>
                    <?php if ($s['review_count'] > 0): ?>
                    <span><?= $s['review_count'] ?><?= __('shop.detail.reviews') ?? '리뷰' ?></span>
                    <?php endif; ?>
                    <span>♡<?= $s['favorite_count'] ?></span>
                </div>
                <?php if ($s['address']): ?>
                <p class="text-[10px] text-zinc-400 mt-1 truncate"><?= htmlspecialchars($s['address']) ?></p>
                <?php endif; ?>
            </div>
        </a>
        <?php endforeach; ?>
    </div>

    <!-- 페이지네이션 -->
    <?php if ($totalPages > 1): ?>
    <div class="flex justify-center gap-1 mt-8">
        <?php if ($page > 1): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page - 1])) ?>" class="px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">←</a>
        <?php endif; ?>
        <?php
        $start = max(1, $page - 2);
        $end = min($totalPages, $page + 2);
        for ($i = $start; $i <= $end; $i++): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $i])) ?>" class="px-3 py-2 text-sm rounded-lg transition <?= $i === $page ? 'bg-blue-600 text-white' : 'border border-zinc-300 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-700' ?>"><?= $i ?></a>
        <?php endfor; ?>
        <?php if ($page < $totalPages): ?>
        <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page + 1])) ?>" class="px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">→</a>
        <?php endif; ?>
    </div>
    <?php endif; ?>

    <?php endif; ?>
</div>
