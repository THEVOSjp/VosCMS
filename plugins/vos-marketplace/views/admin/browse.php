<?php
/**
 * VosCMS Marketplace - 카탈로그 탐색 페이지
 * 데이터 소스: market.21ces.com API (30분 캐시)
 */
include __DIR__ . '/_head.php';

$pageHeaderTitle = __mp('title');
$pageSubTitle    = __mp('browse');

$marketApiBase = rtrim($_ENV['MARKET_API_URL'] ?? 'https://market.21ces.com/api/market', '/');
$cacheDir      = (defined('BASE_PATH') ? BASE_PATH : __DIR__ . '/../../../../..') . '/storage/cache';

// ── 필터 파라미터 ─────────────────────────────────────
$filterType     = $_GET['type']     ?? '';
$filterCat      = $_GET['cat']      ?? '';
$filterSort     = $_GET['sort']     ?? 'newest';
$filterKeyword  = $_GET['q']        ?? '';
$filterFree     = !empty($_GET['free']);
$page           = max(1, (int)($_GET['page'] ?? 1));
$limit          = 24;

// ── API 호출 헬퍼 (캐시 30분) ────────────────────────
function mpApiFetch(string $url, string $cacheDir, int $ttl = 1800): ?array {
    $cacheFile = $cacheDir . '/mp_api_' . md5($url) . '.json';
    if (file_exists($cacheFile) && (time() - filemtime($cacheFile)) < $ttl) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        if (!empty($cached['ok'])) return $cached;
    }
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL            => $url,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT        => 10,
        CURLOPT_CONNECTTIMEOUT => 5,
        CURLOPT_HTTPHEADER     => ['Accept: application/json', 'User-Agent: VosCMS/' . ($_ENV['APP_VERSION'] ?? '2.0')],
        CURLOPT_SSL_VERIFYPEER => true,
    ]);
    $response = curl_exec($ch);
    $code     = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);
    if ($code !== 200 || !$response) return null;
    $data = json_decode($response, true);
    if (!empty($data['ok']) && is_dir($cacheDir)) {
        file_put_contents($cacheFile, $response, LOCK_EX);
    }
    return $data;
}

// ── 카탈로그 조회 ─────────────────────────────────────
$catalogParams = array_filter([
    'limit' => $limit,
    'page'  => $page,
    'sort'  => $filterSort,
    'type'  => $filterType,
    'cat'   => $filterCat,
    'q'     => $filterKeyword,
    'free'  => $filterFree ? '1' : '',
]);
$catalogUrl  = $marketApiBase . '/catalog?' . http_build_query($catalogParams);
$catalogData = mpApiFetch($catalogUrl, $cacheDir);
$items       = $catalogData['data'] ?? [];
$meta        = $catalogData['meta'] ?? ['total' => 0, 'page' => 1, 'pages' => 1];
$apiError    = empty($catalogData['ok']);

// ── 추천 아이템 (첫 페이지 + 필터 없을 때) ─────────────
$featuredItems = [];
if ($page === 1 && !$filterType && !$filterKeyword && !$filterCat) {
    $featuredUrl   = $marketApiBase . '/catalog?' . http_build_query(['featured' => '1', 'limit' => 4, 'sort' => 'popular']);
    $featuredData  = mpApiFetch($featuredUrl, $cacheDir);
    $featuredItems = $featuredData['data'] ?? [];
}

// ── 타입별 카운트 ─────────────────────────────────────
$typeCounts = ['plugin' => 0, 'theme' => 0, 'widget' => 0, 'skin' => 0];
$countUrl   = $marketApiBase . '/catalog?' . http_build_query(['limit' => 1]);
$countData  = mpApiFetch($countUrl, $cacheDir);
$totalCount = $countData['meta']['total'] ?? 0;
foreach (array_keys($typeCounts) as $t) {
    $tData = mpApiFetch($marketApiBase . '/catalog?' . http_build_query(['type' => $t, 'limit' => 1]), $cacheDir);
    $typeCounts[$t] = $tData['meta']['total'] ?? 0;
}

// ── 카테고리 목록 (전체 카탈로그에서 추출) ───────────
$allCatsData = mpApiFetch($marketApiBase . '/catalog?' . http_build_query(['limit' => 100]), $cacheDir);
$catSlugs = [];
foreach ($allCatsData['data'] ?? [] as $it) {
    if (!empty($it['cat_slug'])) $catSlugs[$it['cat_slug']] = $it['cat_slug'];
}
if ($filterCat && !isset($catSlugs[$filterCat])) $catSlugs[$filterCat] = $filterCat;
?>

<div class="space-y-6">

    <?php if ($apiError): ?>
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-xl p-4 flex items-center gap-3 text-sm text-amber-700 dark:text-amber-400">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
        마켓 서버에 연결할 수 없습니다. 잠시 후 다시 시도해주세요.
    </div>
    <?php endif; ?>

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
                    <option value=""><?= __mp('all_types') ?> (<?= number_format($totalCount) ?>)</option>
                    <option value="plugin" <?= $filterType === 'plugin' ? 'selected' : '' ?>><?= __mp('plugins') ?> (<?= $typeCounts['plugin'] ?>)</option>
                    <option value="theme"  <?= $filterType === 'theme'  ? 'selected' : '' ?>><?= __mp('themes') ?>  (<?= $typeCounts['theme'] ?>)</option>
                    <option value="widget" <?= $filterType === 'widget' ? 'selected' : '' ?>><?= __mp('widgets') ?> (<?= $typeCounts['widget'] ?>)</option>
                    <option value="skin"   <?= $filterType === 'skin'   ? 'selected' : '' ?>><?= __mp('skins') ?>   (<?= $typeCounts['skin'] ?>)</option>
                </select>

                <!-- 정렬 -->
                <select name="sort" onchange="this.form.submit()"
                        class="px-3 py-2.5 bg-zinc-50 dark:bg-zinc-700 border border-zinc-200 dark:border-zinc-600 rounded-lg text-sm text-zinc-700 dark:text-zinc-300">
                    <option value="newest"     <?= $filterSort === 'newest'     ? 'selected' : '' ?>><?= __mp('sort_newest') ?></option>
                    <option value="popular"    <?= $filterSort === 'popular'    ? 'selected' : '' ?>><?= __mp('sort_popular') ?></option>
                    <option value="price_asc"  <?= $filterSort === 'price_asc'  ? 'selected' : '' ?>><?= __mp('sort_price_asc') ?></option>
                    <option value="price_desc" <?= $filterSort === 'price_desc' ? 'selected' : '' ?>><?= __mp('sort_price_desc') ?></option>
                    <option value="rating"     <?= $filterSort === 'rating'     ? 'selected' : '' ?>><?= __mp('sort_rating') ?></option>
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

    <?php if (!empty($featuredItems)): ?>
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

    <!-- 카테고리 버튼 -->
    <?php if (!empty($catSlugs)): ?>
    <?php
    $catLabels = [
        'content'   => ['ko' => '콘텐츠',   'en' => 'Content',   'ja' => 'コンテンツ'],
        'utility'   => ['ko' => '유틸리티', 'en' => 'Utility',   'ja' => 'ユーティリティ'],
        'design'    => ['ko' => '디자인',   'en' => 'Design',    'ja' => 'デザイン'],
        'marketing' => ['ko' => '마케팅',   'en' => 'Marketing', 'ja' => 'マーケティング'],
        'seo'       => ['ko' => 'SEO',      'en' => 'SEO',       'ja' => 'SEO'],
        'ecommerce' => ['ko' => '이커머스', 'en' => 'E-Commerce','ja' => 'EC'],
        'security'  => ['ko' => '보안',     'en' => 'Security',  'ja' => 'セキュリティ'],
        'social'    => ['ko' => '소셜',     'en' => 'Social',    'ja' => 'ソーシャル'],
    ];
    $locale = current_locale() ?: 'ko';
    $catQueryBase = array_filter(['type' => $filterType, 'sort' => $filterSort, 'q' => $filterKeyword, 'free' => $filterFree ? '1' : '']);
    ?>
    <div class="flex flex-wrap gap-2">
        <a href="?<?= http_build_query(array_merge($catQueryBase, ['cat' => ''])) ?>"
           class="px-4 py-1.5 rounded-full text-sm font-medium border transition-colors
                  <?= $filterCat === '' ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:border-indigo-400 hover:text-indigo-600' ?>">
            <?= __mp('all_categories') ?>
        </a>
        <?php foreach ($catSlugs as $slug): ?>
        <?php $label = $catLabels[$slug][$locale] ?? $catLabels[$slug]['en'] ?? ucfirst($slug); ?>
        <a href="?<?= http_build_query(array_merge($catQueryBase, ['cat' => $slug])) ?>"
           class="px-4 py-1.5 rounded-full text-sm font-medium border transition-colors
                  <?= $filterCat === $slug ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:border-indigo-400 hover:text-indigo-600' ?>">
            <?= htmlspecialchars($label) ?>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <!-- 아이템 목록 -->
    <div>
        <div class="flex items-center justify-between mb-3">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                <?= number_format($meta['total']) ?><?= __mp('items_count') ?>
                <?php if ($meta['pages'] > 1): ?>
                <span class="ml-2 text-zinc-400">(<?= $page ?> / <?= $meta['pages'] ?>)</span>
                <?php endif; ?>
            </p>
            <!-- 뷰 스타일 전환 버튼 -->
            <div class="flex items-center gap-1" id="mpViewStyleBtns">
                <button onclick="mpSetViewStyle('list')" class="vs-btn p-1.5 rounded-lg transition" data-style="list" title="리스트">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                </button>
                <button onclick="mpSetViewStyle('card')" class="vs-btn p-1.5 rounded-lg transition" data-style="card" title="카드">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </button>
                <button onclick="mpSetViewStyle('grid')" class="vs-btn p-1.5 rounded-lg transition" data-style="grid" title="그리드">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/></svg>
                </button>
            </div>
        </div>

        <?php if (empty($items)): ?>
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-12 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <p class="text-zinc-400 dark:text-zinc-500"><?= __mp('no_items') ?></p>
        </div>
        <?php else: ?>

        <!-- 리스트 뷰 -->
        <div id="viewList" class="view-mode hidden bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <?php foreach ($items as $item):
                $_name = json_decode($item['name'] ?? '{}', true);
                $_locale = current_locale() ?: 'ko';
                $_itemName = $_name[$_locale] ?? $_name['en'] ?? $item['slug'] ?? '';
                $_price = (float)($item['price'] ?? 0);
                $_isFree = $_price <= 0;
                $_currency = $item['currency'] ?? 'USD';
                $_typeLabels = ['plugin' => __mp('plugins'), 'theme' => __mp('themes'), 'widget' => __mp('widgets'), 'skin' => __mp('skins')];
                $_typeColors = ['plugin' => 'indigo', 'theme' => 'purple', 'widget' => 'emerald', 'skin' => 'orange'];
                $_type = $item['type'] ?? 'plugin';
                $_color = $_typeColors[$_type] ?? 'indigo';
            ?>
            <a href="<?= $adminUrl ?>/marketplace/item?slug=<?= urlencode($item['slug'] ?? '') ?>"
               class="flex items-center gap-4 px-4 py-3 border-b border-zinc-100 dark:border-zinc-700 last:border-0 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors">
                <!-- 아이콘 -->
                <div class="w-10 h-10 flex-shrink-0 rounded-lg bg-<?= $_color ?>-100 dark:bg-<?= $_color ?>-900/30 flex items-center justify-center overflow-hidden">
                    <?php if (!empty($item['icon']) && (str_starts_with($item['icon'], '/') || str_starts_with($item['icon'], 'http'))): ?>
                    <img src="<?= htmlspecialchars($item['icon']) ?>" alt="" class="w-full h-full object-cover rounded-lg">
                    <?php else: ?>
                    <svg class="w-5 h-5 text-<?= $_color ?>-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    <?php endif; ?>
                </div>
                <!-- 이름/타입 -->
                <div class="flex-1 min-w-0">
                    <span class="font-medium text-sm text-zinc-800 dark:text-zinc-200 truncate block"><?= htmlspecialchars($_itemName) ?></span>
                    <span class="text-xs text-zinc-400"><?= $_typeLabels[$_type] ?? $_type ?> <?= !empty($item['author_name']) ? '· ' . htmlspecialchars($item['author_name']) : '' ?></span>
                </div>
                <!-- 평점 -->
                <?php if (($item['rating_avg'] ?? 0) > 0): ?>
                <div class="flex items-center gap-0.5 text-xs text-zinc-400 flex-shrink-0">
                    <svg class="w-3.5 h-3.5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    <?= number_format((float)$item['rating_avg'], 1) ?>
                </div>
                <?php endif; ?>
                <!-- 가격 -->
                <div class="flex-shrink-0 text-sm font-semibold <?= $_isFree ? 'text-green-600 dark:text-green-400' : 'text-zinc-800 dark:text-zinc-200' ?>">
                    <?= $_isFree ? __mp('free') : number_format($_price, in_array($_currency, ['KRW','JPY']) ? 0 : 2) . ' ' . $_currency ?>
                </div>
            </a>
            <?php endforeach; ?>
        </div>

        <!-- 카드 뷰 (2열) -->
        <div id="viewCard" class="view-mode hidden grid grid-cols-1 md:grid-cols-2 gap-4">
            <?php foreach ($items as $item): ?>
                <?php include __DIR__ . '/_components/item-card.php'; ?>
            <?php endforeach; ?>
        </div>

        <!-- 그리드 뷰 (4열) -->
        <div id="viewGrid" class="view-mode hidden grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
            <?php foreach ($items as $item): ?>
                <?php include __DIR__ . '/_components/item-card.php'; ?>
            <?php endforeach; ?>
        </div>

        <?php endif; ?>
    </div>

    <!-- 페이지네이션 -->
    <?php if ($meta['pages'] > 1): ?>
    <div class="flex items-center justify-center gap-2 pt-2">
        <?php
        $baseQuery = array_filter(['type' => $filterType, 'cat' => $filterCat, 'sort' => $filterSort, 'q' => $filterKeyword, 'free' => $filterFree ? '1' : '']);
        $prevPage  = $page > 1 ? $page - 1 : null;
        $nextPage  = $page < $meta['pages'] ? $page + 1 : null;
        $btnBase   = 'px-3 py-1.5 text-sm rounded-lg border transition-colors';
        $btnActive = 'bg-indigo-600 border-indigo-600 text-white';
        $btnNormal = 'bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 hover:border-indigo-400';
        $btnDisabled = 'bg-zinc-50 dark:bg-zinc-900 border-zinc-200 dark:border-zinc-700 text-zinc-300 dark:text-zinc-600 cursor-not-allowed';
        ?>
        <?php if ($prevPage): ?>
        <a href="?<?= http_build_query(array_merge($baseQuery, ['page' => $prevPage])) ?>" class="<?= $btnBase ?> <?= $btnNormal ?>">←</a>
        <?php else: ?>
        <span class="<?= $btnBase ?> <?= $btnDisabled ?>">←</span>
        <?php endif; ?>

        <?php
        $startPage = max(1, $page - 2);
        $endPage   = min($meta['pages'], $page + 2);
        if ($startPage > 1) echo '<span class="text-zinc-400 text-sm px-1">…</span>';
        for ($p = $startPage; $p <= $endPage; $p++):
        ?>
        <a href="?<?= http_build_query(array_merge($baseQuery, ['page' => $p])) ?>"
           class="<?= $btnBase ?> <?= $p === $page ? $btnActive : $btnNormal ?>">
            <?= $p ?>
        </a>
        <?php endfor;
        if ($endPage < $meta['pages']) echo '<span class="text-zinc-400 text-sm px-1">…</span>';
        ?>

        <?php if ($nextPage): ?>
        <a href="?<?= http_build_query(array_merge($baseQuery, ['page' => $nextPage])) ?>" class="<?= $btnBase ?> <?= $btnNormal ?>">→</a>
        <?php else: ?>
        <span class="<?= $btnBase ?> <?= $btnDisabled ?>">→</span>
        <?php endif; ?>
    </div>
    <?php endif; ?>

</div>

<script>
function mpSetViewStyle(style) {
    document.querySelectorAll('.view-mode').forEach(function(el) { el.classList.add('hidden'); });
    var target = document.getElementById('view' + style.charAt(0).toUpperCase() + style.slice(1));
    if (target) target.classList.remove('hidden');

    document.querySelectorAll('#mpViewStyleBtns .vs-btn').forEach(function(btn) {
        var isActive = btn.dataset.style === style;
        btn.className = 'vs-btn p-1.5 rounded-lg transition ' + (isActive
            ? 'bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400'
            : 'text-zinc-400 dark:text-zinc-500 hover:text-zinc-600 dark:hover:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700');
    });

    localStorage.setItem('mp_view_style', style);
}

(function() {
    var saved = localStorage.getItem('mp_view_style') || 'grid';
    mpSetViewStyle(saved);
})();
</script>

<?php include __DIR__ . '/_foot.php'; ?>
