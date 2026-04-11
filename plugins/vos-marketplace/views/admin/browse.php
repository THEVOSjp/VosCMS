<?php
/**
 * VosCMS Marketplace - 카탈로그 탐색 페이지
 */
include __DIR__ . '/_head.php';

$pageHeaderTitle = __mp('title');
$pageSubTitle = __mp('browse');

// DB 연결
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    echo '<div class="bg-red-50 dark:bg-red-900/20 text-red-600 dark:text-red-400 p-4 rounded-lg">DB 연결 실패</div>';
    include __DIR__ . '/_foot.php';
    return;
}

// 필터 파라미터
$filterType = $_GET['type'] ?? '';
$filterCategory = $_GET['category'] ?? '';
$filterSort = $_GET['sort'] ?? 'popular';
$filterKeyword = $_GET['q'] ?? '';
$filterFree = isset($_GET['free']) ? (bool)$_GET['free'] : false;

// 카테고리 로드
$categories = $pdo->query("SELECT * FROM {$prefix}mp_categories WHERE is_active = 1 ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC);

// 아이템 쿼리
$where = ["status = 'active'"];
$params = [];

if ($filterType) {
    $where[] = "type = ?";
    $params[] = $filterType;
}
if ($filterCategory) {
    $where[] = "category_id = ?";
    $params[] = $filterCategory;
}
if ($filterFree) {
    $where[] = "price = 0";
}
if ($filterKeyword) {
    $where[] = "(slug LIKE ? OR author_name LIKE ?)";
    $params[] = "%{$filterKeyword}%";
    $params[] = "%{$filterKeyword}%";
}

$whereClause = implode(' AND ', $where);
$orderBy = match ($filterSort) {
    'newest' => 'created_at DESC',
    'price_asc' => 'price ASC',
    'price_desc' => 'price DESC',
    'rating' => 'rating_avg DESC',
    default => 'download_count DESC',
};

$stmt = $pdo->prepare("SELECT * FROM {$prefix}mp_items WHERE {$whereClause} ORDER BY is_featured DESC, {$orderBy} LIMIT 48");
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 타입별 카운트
$typeCounts = [];
foreach (['plugin', 'theme', 'widget', 'skin'] as $t) {
    $cs = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}mp_items WHERE status='active' AND type=?");
    $cs->execute([$t]);
    $typeCounts[$t] = (int)$cs->fetchColumn();
}
$totalCount = array_sum($typeCounts);

// featured 아이템
$featuredStmt = $pdo->query("SELECT * FROM {$prefix}mp_items WHERE status='active' AND is_featured=1 ORDER BY download_count DESC LIMIT 4");
$featuredItems = $featuredStmt->fetchAll(PDO::FETCH_ASSOC);
?>

<div x-data="{ showFilters: true }" class="space-y-6">

    <!-- 검색 바 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <form method="GET" action="<?= $adminUrl ?>/marketplace" class="flex flex-col md:flex-row gap-3">
            <div class="flex-1 relative">
                <svg class="absolute left-3 top-1/2 -translate-y-1/2 w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                </svg>
                <input type="text" name="q" value="<?= htmlspecialchars($filterKeyword) ?>"
                       placeholder="<?= __mp('search_placeholder') ?>"
                       class="w-full pl-10 pr-4 py-2.5 bg-zinc-50 dark:bg-zinc-700 border border-zinc-200 dark:border-zinc-600 rounded-lg text-sm text-zinc-800 dark:text-zinc-200 placeholder-zinc-400 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
            </div>
            <div class="flex gap-2 flex-wrap">
                <!-- 타입 필터 -->
                <select name="type" onchange="this.form.submit()"
                        class="px-3 py-2.5 bg-zinc-50 dark:bg-zinc-700 border border-zinc-200 dark:border-zinc-600 rounded-lg text-sm text-zinc-700 dark:text-zinc-300">
                    <option value=""><?= __mp('all_types') ?> (<?= $totalCount ?>)</option>
                    <option value="plugin" <?= $filterType === 'plugin' ? 'selected' : '' ?>><?= __mp('plugins') ?> (<?= $typeCounts['plugin'] ?>)</option>
                    <option value="theme" <?= $filterType === 'theme' ? 'selected' : '' ?>><?= __mp('themes') ?> (<?= $typeCounts['theme'] ?>)</option>
                    <option value="widget" <?= $filterType === 'widget' ? 'selected' : '' ?>><?= __mp('widgets') ?> (<?= $typeCounts['widget'] ?>)</option>
                    <option value="skin" <?= $filterType === 'skin' ? 'selected' : '' ?>><?= __mp('skins') ?> (<?= $typeCounts['skin'] ?>)</option>
                </select>

                <!-- 카테고리 필터 -->
                <select name="category" onchange="this.form.submit()"
                        class="px-3 py-2.5 bg-zinc-50 dark:bg-zinc-700 border border-zinc-200 dark:border-zinc-600 rounded-lg text-sm text-zinc-700 dark:text-zinc-300">
                    <option value=""><?= __mp('all_categories') ?></option>
                    <?php foreach ($categories as $cat):
                        $catName = json_decode($cat['name'], true);
                        $catLabel = $catName[$locale] ?? $catName['en'] ?? $cat['slug'];
                    ?>
                    <option value="<?= $cat['id'] ?>" <?= $filterCategory == $cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($catLabel) ?></option>
                    <?php endforeach; ?>
                </select>

                <!-- 정렬 -->
                <select name="sort" onchange="this.form.submit()"
                        class="px-3 py-2.5 bg-zinc-50 dark:bg-zinc-700 border border-zinc-200 dark:border-zinc-600 rounded-lg text-sm text-zinc-700 dark:text-zinc-300">
                    <option value="popular" <?= $filterSort === 'popular' ? 'selected' : '' ?>><?= __mp('sort_popular') ?></option>
                    <option value="newest" <?= $filterSort === 'newest' ? 'selected' : '' ?>><?= __mp('sort_newest') ?></option>
                    <option value="price_asc" <?= $filterSort === 'price_asc' ? 'selected' : '' ?>><?= __mp('sort_price_asc') ?></option>
                    <option value="price_desc" <?= $filterSort === 'price_desc' ? 'selected' : '' ?>><?= __mp('sort_price_desc') ?></option>
                    <option value="rating" <?= $filterSort === 'rating' ? 'selected' : '' ?>><?= __mp('sort_rating') ?></option>
                </select>

                <!-- 무료 필터 -->
                <label class="flex items-center gap-2 px-3 py-2.5 bg-zinc-50 dark:bg-zinc-700 border border-zinc-200 dark:border-zinc-600 rounded-lg text-sm text-zinc-700 dark:text-zinc-300 cursor-pointer">
                    <input type="checkbox" name="free" value="1" <?= $filterFree ? 'checked' : '' ?> onchange="this.form.submit()" class="rounded border-zinc-300 dark:border-zinc-600 text-indigo-600 focus:ring-indigo-500">
                    <?= __mp('free_only') ?>
                </label>

                <button type="submit" class="px-4 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
                </button>
            </div>
        </form>
    </div>

    <?php if (!empty($featuredItems) && empty($filterType) && empty($filterKeyword)): ?>
    <!-- 추천 아이템 -->
    <div>
        <h2 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-3">
            <svg class="w-5 h-5 inline-block text-yellow-500 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            <?= __mp('featured') ?>
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach ($featuredItems as $fi): ?>
                <?php $item = $fi; include __DIR__ . '/_components/item-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- 아이템 그리드 -->
    <div>
        <div class="flex items-center justify-between mb-3">
            <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= count($items) ?><?= __mp('items_count') ?></p>
        </div>

        <?php if (empty($items)): ?>
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-12 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <p class="text-zinc-400 dark:text-zinc-500"><?= __mp('no_items') ?></p>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <?php foreach ($items as $item): ?>
                <?php include __DIR__ . '/_components/item-card.php'; ?>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include __DIR__ . '/_foot.php'; ?>
