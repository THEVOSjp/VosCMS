<?php
/**
 * VosCMS Marketplace - 카탈로그 탐색 페이지
 * 데이터 소스: market.21ces.com API (30분 캐시)
 */
include __DIR__ . '/_head.php';

$pageHeaderTitle = __('autoinstall.title');
$pageSubTitle    = __('autoinstall.browse');

$_pm            = \RzxLib\Core\Plugin\PluginManager::getInstance();
$marketApiBase  = rtrim($_pm ? $_pm->getSetting('vos-autoinstall', 'market_api_url', $_ENV['MARKET_API_URL'] ?? 'https://market.21ces.com/api/market') : ($_ENV['MARKET_API_URL'] ?? 'https://market.21ces.com/api/market'), '/');
$cacheDir       = (defined('BASE_PATH') ? BASE_PATH : __DIR__ . '/../../../../..') . '/storage/cache';
$payjpPublicKey = $_ENV['PAYJP_PUBLIC_KEY'] ?? '';
$_apiUrl        = $adminUrl . '/autoinstall/api';
$_cacheTtl      = (int)($_pm ? $_pm->getSetting('vos-autoinstall', 'cache_ttl', '300') : 300);

// ── 필터 파라미터 ─────────────────────────────────────
$filterType     = $_GET['type']     ?? '';
$filterSort     = $_GET['sort']     ?? 'newest';
$filterKeyword  = $_GET['q']        ?? '';
$filterPricing  = $_GET['pricing'] ?? '';  // '', 'free', 'paid'
$filterFree     = $filterPricing === 'free';
$filterPaid     = $filterPricing === 'paid';
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
$_locale = current_locale() ?: 'ko';
$catalogParams = array_filter([
    'limit'  => $limit,
    'page'   => $page,
    'sort'   => $filterSort,
    'type'   => $filterType,
    'q'      => $filterKeyword,
    'free'   => $filterFree ? '1' : '',
    'paid'   => $filterPaid ? '1' : '',
    'locale' => $_locale,
]);
$catalogUrl  = $marketApiBase . '/catalog?' . http_build_query($catalogParams);
$catalogData = mpApiFetch($catalogUrl, $cacheDir, $_cacheTtl);
$items       = $catalogData['data'] ?? [];
$meta        = $catalogData['meta'] ?? ['total' => 0, 'page' => 1, 'pages' => 1];
$apiError    = empty($catalogData['ok']);

// ── 추천 아이템 (첫 페이지 + 필터 없을 때) ─────────────
$featuredItems = [];
if ($page === 1 && !$filterType && !$filterKeyword) {
    $featuredUrl   = $marketApiBase . '/catalog?' . http_build_query(['featured' => '1', 'limit' => 4, 'sort' => 'popular', 'locale' => $_locale]);
    $featuredData  = mpApiFetch($featuredUrl, $cacheDir, $_cacheTtl);
    $featuredItems = $featuredData['data'] ?? [];
}

// ── 타입별 카운트 ─────────────────────────────────────
$typeCounts = ['plugin' => 0, 'theme' => 0, 'widget' => 0, 'skin' => 0];
$countUrl   = $marketApiBase . '/catalog?' . http_build_query(['limit' => 1]);
$countData  = mpApiFetch($countUrl, $cacheDir, $_cacheTtl);
$totalCount = $countData['meta']['total'] ?? 0;
foreach (array_keys($typeCounts) as $t) {
    $tData = mpApiFetch($marketApiBase . '/catalog?' . http_build_query(['type' => $t, 'limit' => 1]), $cacheDir, $_cacheTtl);
    $typeCounts[$t] = $tData['meta']['total'] ?? 0;
}

// ── 구매 완료 아이템 슬러그 셋 (유료 아이템 버튼 분기용) ─
$purchasedSlugs = [];
try {
    $_pfx = $_ENV['DB_PREFIX'] ?? 'rzx_';
    $_pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $_adminId = $_SESSION['admin_id'] ?? 0;
    if ($_adminId) {
        $_st = $_pdo->prepare(
            "SELECT DISTINCT i.slug
               FROM {$_pfx}mp_order_items oi
               JOIN {$_pfx}mp_orders o ON o.id = oi.order_id
               JOIN {$_pfx}mp_items  i ON i.id = oi.item_id
              WHERE o.admin_id = ? AND o.status = 'paid'"
        );
        $_st->execute([$_adminId]);
        $purchasedSlugs = array_flip($_st->fetchAll(PDO::FETCH_COLUMN));
    }
} catch (Throwable $e) { /* 무시 */ }

?>

<div class="space-y-6">

    <!-- 우상단 캐시 갱신 버튼 -->
    <div class="flex justify-end">
        <button type="button" id="mpCacheRefreshBtn" onclick="mpRefreshCache(this)"
                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
            <span><?= __('autoinstall.cache_refresh') ?></span>
        </button>
    </div>

    <?php if ($apiError): ?>
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-700 rounded-xl p-4 flex items-center gap-3 text-sm text-amber-700 dark:text-amber-400">
        <svg class="w-5 h-5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M10.29 3.86L1.82 18a2 2 0 001.71 3h16.94a2 2 0 001.71-3L13.71 3.86a2 2 0 00-3.42 0z"/></svg>
        마켓 서버에 연결할 수 없습니다. 잠시 후 다시 시도해주세요.
    </div>
    <?php endif; ?>

    <!-- 검색 바 (밝은 녹색 강조) -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-4">
        <form method="GET" action="<?= $adminUrl ?>/autoinstall" class="flex gap-2">
            <!-- type/sort/pricing 유지를 위한 hidden -->
            <?php if ($filterType !== ''): ?><input type="hidden" name="type" value="<?= htmlspecialchars($filterType) ?>"><?php endif; ?>
            <?php if ($filterSort !== '' && $filterSort !== 'newest'): ?><input type="hidden" name="sort" value="<?= htmlspecialchars($filterSort) ?>"><?php endif; ?>
            <?php if ($filterPricing !== ''): ?><input type="hidden" name="pricing" value="<?= htmlspecialchars($filterPricing) ?>"><?php endif; ?>

            <div class="flex-1 relative">
                <span class="absolute left-1.5 top-1/2 -translate-y-1/2 w-9 h-9 rounded-md bg-emerald-100 dark:bg-emerald-900/40 flex items-center justify-center">
                    <svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                </span>
                <input type="text" name="q" value="<?= htmlspecialchars($filterKeyword) ?>"
                       placeholder="<?= __('autoinstall.search_placeholder') ?>"
                       class="w-full pl-12 pr-4 py-2.5 bg-white dark:bg-zinc-700 border-2 border-emerald-300 dark:border-emerald-700 rounded-lg text-sm text-zinc-800 dark:text-zinc-200 placeholder-zinc-400 focus:outline-none focus:ring-2 focus:ring-emerald-400 focus:border-emerald-400">
            </div>
            <button type="submit" aria-label="<?= __('autoinstall.search') ?>"
                    class="px-4 py-2.5 bg-emerald-500 hover:bg-emerald-600 text-white text-sm font-medium rounded-lg transition-colors">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/></svg>
            </button>
        </form>
    </div>

    <?php if (!empty($featuredItems)): ?>
    <!-- 추천 아이템 -->
    <div>
        <h2 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-3">
            <svg class="w-5 h-5 inline-block text-yellow-500 mr-1" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
            <?= __('autoinstall.featured') ?>
        </h2>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-4">
            <?php foreach ($featuredItems as $fi): ?>
                <?php $item = $fi; include __DIR__ . '/_components/item-card.php'; ?>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- 타입 버튼 -->
    <?php
    $typeQueryBase = array_filter(['sort' => $filterSort, 'q' => $filterKeyword, 'pricing' => $filterPricing]);
    $typeList = [
        ''       => [__('autoinstall.all_types'),  $totalCount],
        'plugin' => [__('autoinstall.plugins'),    $typeCounts['plugin']],
        'theme'  => [__('autoinstall.themes'),     $typeCounts['theme']],
        'widget' => [__('autoinstall.widgets'),    $typeCounts['widget']],
        'skin'   => [__('autoinstall.skins'),      $typeCounts['skin']],
    ];
    ?>
    <div class="flex flex-wrap gap-2">
        <?php foreach ($typeList as $val => [$label, $count]): ?>
        <a href="?<?= http_build_query(array_merge($typeQueryBase, ['type' => $val])) ?>"
           class="px-4 py-1.5 rounded-full text-sm font-medium border transition-colors
                  <?= $filterType === $val ? 'bg-indigo-600 border-indigo-600 text-white' : 'bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 text-zinc-600 dark:text-zinc-300 hover:border-indigo-400 hover:text-indigo-600' ?>">
            <?= $label ?> (<?= number_format($count) ?>)
        </a>
        <?php endforeach; ?>
    </div>

    <!-- 아이템 목록 -->
    <div>
        <div class="flex items-center justify-between mb-3">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                <?= number_format($meta['total']) ?><?= __('autoinstall.items_count') ?>
                <?php if ($meta['pages'] > 1): ?>
                <span class="ml-2 text-zinc-400">(<?= $page ?> / <?= $meta['pages'] ?>)</span>
                <?php endif; ?>
            </p>
            <!-- 정렬 + 무료/유료 필터 + 뷰 스타일 전환 -->
            <div class="flex items-center gap-2 flex-wrap">
                <!-- 정렬 -->
                <form method="GET" action="<?= $adminUrl ?>/autoinstall" class="inline-flex">
                    <?php if ($filterType !== ''): ?><input type="hidden" name="type" value="<?= htmlspecialchars($filterType) ?>"><?php endif; ?>
                    <?php if ($filterKeyword !== ''): ?><input type="hidden" name="q" value="<?= htmlspecialchars($filterKeyword) ?>"><?php endif; ?>
                    <?php if ($filterPricing !== ''): ?><input type="hidden" name="pricing" value="<?= htmlspecialchars($filterPricing) ?>"><?php endif; ?>
                    <select name="sort" onchange="this.form.submit()"
                            class="px-2.5 py-1.5 bg-white dark:bg-zinc-700 border border-zinc-200 dark:border-zinc-600 rounded-lg text-xs text-zinc-700 dark:text-zinc-300">
                        <option value="newest"     <?= $filterSort === 'newest'     ? 'selected' : '' ?>><?= __('autoinstall.sort_newest') ?></option>
                        <option value="popular"    <?= $filterSort === 'popular'    ? 'selected' : '' ?>><?= __('autoinstall.sort_popular') ?></option>
                        <option value="price_asc"  <?= $filterSort === 'price_asc'  ? 'selected' : '' ?>><?= __('autoinstall.sort_price_asc') ?></option>
                        <option value="price_desc" <?= $filterSort === 'price_desc' ? 'selected' : '' ?>><?= __('autoinstall.sort_price_desc') ?></option>
                        <option value="rating"     <?= $filterSort === 'rating'     ? 'selected' : '' ?>><?= __('autoinstall.sort_rating') ?></option>
                    </select>
                </form>

                <!-- 가격 필터 (전체 / 무료 / 유료) -->
                <?php
                    $_pricingBase = array_filter([
                        'type' => $filterType,
                        'q'    => $filterKeyword,
                        'sort' => $filterSort !== 'newest' ? $filterSort : '',
                    ]);
                    $_pricingOptions = [
                        ''     => __('autoinstall.all_types'),
                        'free' => __('autoinstall.free'),
                        'paid' => __('autoinstall.paid'),
                    ];
                ?>
                <div class="inline-flex rounded-lg border border-zinc-200 dark:border-zinc-600 overflow-hidden text-xs">
                    <?php foreach ($_pricingOptions as $val => $label):
                        $_pUrl = '?' . http_build_query(array_merge($_pricingBase, $val !== '' ? ['pricing' => $val] : []));
                        $_active = $filterPricing === $val;
                    ?>
                    <a href="<?= $_pUrl ?>"
                       class="px-3 py-1.5 transition-colors <?= $_active ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-600' ?>">
                        <?= htmlspecialchars($label) ?>
                    </a>
                    <?php endforeach; ?>
                </div>

                <!-- 뷰 스타일 전환 버튼 -->
                <div class="flex items-center gap-1" id="mpViewStyleBtns">
                <button type="button" onclick="mpSetViewStyle('list')" data-style="list"
                        title="<?= __('autoinstall.view_list') ?>" aria-label="<?= __('autoinstall.view_list') ?>"
                        class="vs-btn p-2 rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 10h16M4 14h16M4 18h16"/></svg>
                </button>
                <button type="button" onclick="mpSetViewStyle('webzine')" data-style="webzine"
                        title="<?= __('autoinstall.view_webzine') ?>" aria-label="<?= __('autoinstall.view_webzine') ?>"
                        class="vs-btn p-2 rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/></svg>
                </button>
                <button type="button" onclick="mpSetViewStyle('card')" data-style="card"
                        title="<?= __('autoinstall.view_card') ?>" aria-label="<?= __('autoinstall.view_card') ?>"
                        class="vs-btn p-2 rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/></svg>
                </button>
                <button type="button" onclick="mpSetViewStyle('grid')" data-style="grid"
                        title="<?= __('autoinstall.view_grid') ?>" aria-label="<?= __('autoinstall.view_grid') ?>"
                        class="vs-btn p-2 rounded-lg transition">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1V5zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1V5zM4 15a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1H5a1 1 0 01-1-1v-4zm10 0a1 1 0 011-1h4a1 1 0 011 1v4a1 1 0 01-1 1h-4a1 1 0 01-1-1v-4z"/></svg>
                </button>
                </div>
            </div>
        </div>

        <?php if (empty($items)): ?>
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-12 text-center">
            <svg class="w-16 h-16 mx-auto mb-4 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/>
            </svg>
            <p class="text-zinc-400 dark:text-zinc-500"><?= __('autoinstall.no_items') ?></p>
        </div>
        <?php else: ?>

        <!-- 리스트 뷰 -->
        <div id="viewList" class="view-mode hidden bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <?php foreach ($items as $item):
                $_itemName = $item['name'] ?: ($item['slug'] ?? '');
                $_price = (float)($item['price'] ?? 0);
                $_salePrice = isset($item['sale_price']) ? (float)$item['sale_price'] : null;
                $_onSale = $_salePrice !== null && !empty($item['sale_ends_at']) && strtotime($item['sale_ends_at']) > time();
                $_effectivePrice = $_onSale ? $_salePrice : $_price;
                $_isFree = $_effectivePrice <= 0;
                $_currency = $item['currency'] ?? 'JPY';
                $_priceInt = (int)$_effectivePrice;
                $_priceLabel = $_isFree ? __('autoinstall.free') : number_format($_priceInt) . ' ' . $_currency;
                $_typeLabels = ['plugin' => __('autoinstall.plugins'), 'theme' => __('autoinstall.themes'), 'widget' => __('autoinstall.widgets'), 'skin' => __('autoinstall.skins')];
                $_typeColors = ['plugin' => 'indigo', 'theme' => 'purple', 'widget' => 'emerald', 'skin' => 'orange'];
                $_type = $item['type'] ?? 'plugin';
                $_color = $_typeColors[$_type] ?? 'indigo';
                $_detailUrl = $adminUrl . '/autoinstall/item?slug=' . urlencode($item['slug'] ?? '');
            ?>
            <div onclick="window.location='<?= $_detailUrl ?>'"
                 class="flex items-center gap-4 px-4 py-3 border-b border-zinc-100 dark:border-zinc-700 last:border-0 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition-colors cursor-pointer">
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
                    <?= $_priceLabel ?>
                </div>
                <!-- 버튼 -->
                <?php $_canInstall = $_isFree || isset($purchasedSlugs[$item['slug']]); ?>
                <div class="flex items-center gap-1.5 flex-shrink-0" onclick="event.stopPropagation()">
                    <?php if ($_canInstall): ?>
                    <button type="button" onclick="mpInstallItem(this)"
                            data-slug="<?= htmlspecialchars($item['slug']) ?>"
                            class="px-3 py-1 text-xs font-medium rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition-colors">
                        설치
                    </button>
                    <button type="button" onclick="mpDownloadItem(this)"
                            data-slug="<?= htmlspecialchars($item['slug']) ?>"
                            class="px-3 py-1 text-xs font-medium rounded-lg bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-500 dark:text-zinc-400 transition-colors">
                        다운로드
                    </button>
                    <?php else: ?>
                    <button type="button" onclick="mpOpenPurchase(this)"
                            data-slug="<?= htmlspecialchars($item['slug']) ?>"
                            data-name="<?= htmlspecialchars($_itemName) ?>"
                            data-price="<?= $_priceInt ?>"
                            data-currency="<?= htmlspecialchars($_currency) ?>"
                            data-price-label="<?= htmlspecialchars($_priceLabel) ?>"
                            class="px-3 py-1 text-xs font-medium rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition-colors">
                        구매
                    </button>
                    <?php endif; ?>
                    <?php if (!empty($item['demo_url'])): ?>
                    <a href="<?= htmlspecialchars($item['demo_url']) ?>" target="_blank" rel="noopener"
                       class="px-3 py-1 text-xs font-medium rounded-lg bg-violet-100 hover:bg-violet-200 dark:bg-violet-900/30 dark:hover:bg-violet-800/40 text-violet-600 dark:text-violet-400 transition-colors">
                        미리보기
                    </a>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- 웹진 뷰 -->
        <div id="viewWebzine" class="view-mode hidden space-y-3">
            <?php foreach ($items as $item):
                $_itemName = $item['name'] ?: ($item['slug'] ?? '');
                $_itemDesc = $item['short_description'] ?? '';
                $_price = (float)($item['price'] ?? 0);
                $_salePrice = isset($item['sale_price']) ? (float)$item['sale_price'] : null;
                $_onSale = $_salePrice !== null && !empty($item['sale_ends_at']) && strtotime($item['sale_ends_at']) > time();
                $_effectivePrice = $_onSale ? $_salePrice : $_price;
                $_isFree = $_effectivePrice <= 0;
                $_currency = $item['currency'] ?? 'USD';
                $_typeLabels = ['plugin' => __('autoinstall.plugins'), 'theme' => __('autoinstall.themes'), 'widget' => __('autoinstall.widgets'), 'skin' => __('autoinstall.skins')];
                $_typeColors = ['plugin' => 'indigo', 'theme' => 'purple', 'widget' => 'emerald', 'skin' => 'orange'];
                $_type = $item['type'] ?? 'plugin';
                $_color = $_typeColors[$_type] ?? 'indigo';
                $_hasBanner = !empty($item['banner_image']);
                $_hasIcon   = !empty($item['icon']) && (str_starts_with($item['icon'], '/') || str_starts_with($item['icon'], 'http'));
                $_priceInt  = (int)$_effectivePrice;
                $_priceLabel = $_isFree ? __('autoinstall.free') : number_format($_priceInt) . ' ' . $_currency;
                $_detailUrl = $adminUrl . '/autoinstall/item?slug=' . urlencode($item['slug'] ?? '');
            ?>
            <div onclick="window.location='<?= $_detailUrl ?>'"
                 class="group block bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden hover:shadow-lg hover:border-zinc-300 dark:hover:border-zinc-600 transition-all cursor-pointer">
                <div class="flex flex-col sm:flex-row">
                    <!-- 썸네일 -->
                    <div class="sm:w-48 sm:h-36 h-44 flex-shrink-0 bg-gradient-to-br from-<?= $_color ?>-50 to-<?= $_color ?>-100 dark:from-<?= $_color ?>-900/20 dark:to-<?= $_color ?>-800/20 flex items-center justify-center overflow-hidden relative">
                        <?php if ($_hasBanner): ?>
                        <img src="<?= htmlspecialchars($item['banner_image']) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300" loading="lazy" alt="">
                        <?php elseif ($_hasIcon): ?>
                        <img src="<?= htmlspecialchars($item['icon']) ?>" class="w-16 h-16 rounded-xl shadow-lg" loading="lazy" alt="">
                        <?php else: ?>
                        <svg class="w-12 h-12 text-<?= $_color ?>-300 dark:text-<?= $_color ?>-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                        <?php endif; ?>
                        <!-- 타입 배지 -->
                        <span class="absolute top-2 left-2 px-2 py-0.5 text-xs font-medium rounded-full bg-zinc-900/85 text-<?= $_color ?>-400 backdrop-blur-sm">
                            <?= $_typeLabels[$_type] ?? $_type ?>
                        </span>
                    </div>
                    <!-- 내용 -->
                    <div class="flex-1 p-4 sm:p-5 min-w-0 flex flex-col justify-between">
                        <div>
                            <div class="flex items-center gap-2 mb-1.5">
                                <?php if ($item['is_verified'] ?? false): ?>
                                <svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                <?php endif; ?>
                                <span class="text-xs text-zinc-400"><?= !empty($item['author_name']) ? htmlspecialchars($item['author_name']) : '' ?> <?= !empty($item['latest_version']) ? '· v' . htmlspecialchars($item['latest_version']) : '' ?></span>
                            </div>
                            <h3 class="text-base font-semibold text-zinc-900 dark:text-zinc-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 transition-colors mb-1.5 line-clamp-1">
                                <?= htmlspecialchars($_itemName) ?>
                            </h3>
                            <?php if ($_itemDesc): ?>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400 line-clamp-2 leading-relaxed"><?= htmlspecialchars(strip_tags($_itemDesc)) ?></p>
                            <?php endif; ?>
                        </div>
                        <!-- 하단: 가격 + 평점 + 버튼 -->
                        <div class="flex items-center justify-between mt-3 gap-3" onclick="event.stopPropagation()">
                            <div class="flex items-center gap-3 text-xs text-zinc-400 flex-shrink-0">
                                <span class="font-semibold text-sm <?= $_isFree ? 'text-green-600 dark:text-green-400' : ($_onSale ? 'text-red-600 dark:text-red-400' : 'text-zinc-800 dark:text-zinc-200') ?>">
                                    <?php if ($_isFree): ?>
                                    <?= __('autoinstall.free') ?>
                                    <?php elseif ($_onSale): ?>
                                    <span class="line-through text-zinc-400 mr-1 text-xs font-normal"><?= number_format((int)$_price) ?></span>
                                    <?= number_format($_priceInt) ?> <?= $_currency ?>
                                    <?php else: ?>
                                    <?= number_format($_priceInt) ?> <?= $_currency ?>
                                    <?php endif; ?>
                                </span>
                                <?php if (($item['rating_avg'] ?? 0) > 0): ?>
                                <span class="flex items-center gap-0.5">
                                    <svg class="w-3.5 h-3.5 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    <?= number_format((float)$item['rating_avg'], 1) ?>
                                </span>
                                <?php endif; ?>
                                <span class="flex items-center gap-0.5">
                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                    <?= number_format((int)($item['download_count'] ?? 0)) ?>
                                </span>
                            </div>
                            <!-- 버튼 -->
                            <?php $_canInstall = $_isFree || isset($purchasedSlugs[$item['slug']]); ?>
                            <div class="flex items-center gap-1.5">
                                <?php if ($_canInstall): ?>
                                <button type="button" onclick="mpInstallItem(this)"
                                        data-slug="<?= htmlspecialchars($item['slug']) ?>"
                                        class="px-3 py-1.5 text-xs font-medium rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition-colors">
                                    설치
                                </button>
                                <button type="button" onclick="mpDownloadItem(this)"
                                        data-slug="<?= htmlspecialchars($item['slug']) ?>"
                                        class="px-3 py-1.5 text-xs font-medium rounded-lg bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-500 dark:text-zinc-400 transition-colors">
                                    다운로드
                                </button>
                                <?php else: ?>
                                <button type="button" onclick="mpOpenPurchase(this)"
                                        data-slug="<?= htmlspecialchars($item['slug']) ?>"
                                        data-name="<?= htmlspecialchars($_itemName) ?>"
                                        data-price="<?= $_priceInt ?>"
                                        data-currency="<?= htmlspecialchars($_currency) ?>"
                                        data-price-label="<?= htmlspecialchars($_priceLabel) ?>"
                                        class="px-3 py-1.5 text-xs font-medium rounded-lg bg-emerald-600 hover:bg-emerald-700 text-white transition-colors">
                                    구매
                                </button>
                                <?php endif; ?>
                                <?php if (!empty($item['demo_url'])): ?>
                                <a href="<?= htmlspecialchars($item['demo_url']) ?>" target="_blank" rel="noopener"
                                   class="px-3 py-1.5 text-xs font-medium rounded-lg bg-violet-100 hover:bg-violet-200 dark:bg-violet-900/30 dark:hover:bg-violet-800/40 text-violet-600 dark:text-violet-400 transition-colors">
                                    미리보기
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- 카드 뷰 (2열) -->
        <div id="viewCard" class="view-mode hidden grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-4">
            <?php foreach ($items as $item): ?>
                <?php include __DIR__ . '/_components/item-card.php'; ?>
            <?php endforeach; ?>
        </div>

        <!-- 그리드 뷰 — 배경 이미지 전체, 호버 슬라이드인 -->
        <div id="viewGrid" class="view-mode hidden grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 xl:grid-cols-6 gap-3">
            <?php foreach ($items as $item):
                $_itemName = $item['name'] ?: ($item['slug'] ?? '');
                $_itemDesc = $item['short_description'] ?? '';
                $_price = (float)($item['price'] ?? 0);
                $_salePrice = isset($item['sale_price']) ? (float)$item['sale_price'] : null;
                $_onSale = $_salePrice !== null && !empty($item['sale_ends_at']) && strtotime($item['sale_ends_at']) > time();
                $_effectivePrice = $_onSale ? $_salePrice : $_price;
                $_isFree = $_effectivePrice <= 0;
                $_currency = $item['currency'] ?? 'JPY';
                $_typeColors = ['plugin' => 'indigo', 'theme' => 'purple', 'widget' => 'emerald', 'skin' => 'orange'];
                $_typeLabels = ['plugin' => __('autoinstall.plugins'), 'theme' => __('autoinstall.themes'), 'widget' => __('autoinstall.widgets'), 'skin' => __('autoinstall.skins')];
                $_type = $item['type'] ?? 'plugin';
                $_color = $_typeColors[$_type] ?? 'indigo';
                $_hasBanner = !empty($item['banner_image']);
                $_hasIcon   = !empty($item['icon']);
                $_priceInt  = (int)$_effectivePrice;
                $_priceLabel = $_isFree ? __('autoinstall.free') : number_format($_priceInt) . ' ' . $_currency;
            ?>
            <div class="group relative overflow-hidden rounded-xl aspect-square bg-zinc-900 cursor-pointer shadow-sm hover:shadow-xl transition-shadow duration-300"
                 onclick="window.location='<?= $adminUrl ?>/autoinstall/item?slug=<?= urlencode($item['slug'] ?? '') ?>'"
            >
                <!-- 배경 이미지 -->
                <?php if ($_hasBanner): ?>
                <img src="<?= htmlspecialchars($item['banner_image']) ?>" alt=""
                     class="absolute inset-0 w-full h-full object-cover transition-transform duration-500 group-hover:scale-110">
                <?php elseif ($_hasIcon): ?>
                <div class="absolute inset-0 bg-gradient-to-br from-<?= $_color ?>-800 to-<?= $_color ?>-950 flex items-center justify-center">
                    <img src="<?= htmlspecialchars($item['icon']) ?>" alt="" class="w-16 h-16 rounded-2xl shadow-xl opacity-80">
                </div>
                <?php else: ?>
                <div class="absolute inset-0 bg-gradient-to-br from-<?= $_color ?>-800 to-<?= $_color ?>-950 flex items-center justify-center">
                    <svg class="w-14 h-14 text-<?= $_color ?>-400 opacity-50" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <?php endif; ?>

                <!-- 어두운 그라데이션 오버레이 -->
                <div class="absolute inset-0 bg-gradient-to-t from-black/80 via-black/20 to-transparent"></div>

                <!-- 기본 상태: 제목 + 타입 배지 -->
                <div class="absolute bottom-0 left-0 right-0 p-3 transition-transform duration-300 group-hover:translate-y-full">
                    <span class="inline-block px-1.5 py-0.5 text-[10px] font-medium rounded bg-zinc-900/85 text-<?= $_color ?>-400 backdrop-blur-sm mb-1">
                        <?= $_typeLabels[$_type] ?? $_type ?>
                    </span>
                    <h3 class="text-white text-xs font-semibold leading-tight line-clamp-2">
                        <?= htmlspecialchars($_itemName) ?>
                    </h3>
                </div>

                <!-- 호버 슬라이드인 패널 -->
                <div class="absolute inset-x-0 bottom-0 translate-y-full group-hover:translate-y-0 transition-transform duration-300 ease-out bg-gradient-to-t from-black/95 to-black/80 p-3 flex flex-col gap-2">
                    <div>
                        <span class="inline-block px-1.5 py-0.5 text-[10px] font-medium rounded bg-<?= $_color ?>-500/80 text-white mb-1">
                            <?= $_typeLabels[$_type] ?? $_type ?>
                        </span>
                        <h3 class="text-white text-xs font-bold leading-tight line-clamp-1 mb-1">
                            <?= htmlspecialchars($_itemName) ?>
                        </h3>
                        <?php if ($_itemDesc): ?>
                        <p class="text-zinc-300 text-[10px] leading-relaxed line-clamp-2">
                            <?= htmlspecialchars(mb_substr(strip_tags($_itemDesc), 0, 60)) ?>
                        </p>
                        <?php endif; ?>
                    </div>
                    <!-- 가격 -->
                    <div class="flex items-center justify-between">
                        <?php if ($_isFree): ?>
                        <span class="text-xs font-bold text-green-400"><?= __('autoinstall.free') ?></span>
                        <?php elseif ($_onSale): ?>
                        <div>
                            <span class="text-[10px] text-zinc-400 line-through"><?= number_format((int)$_price) ?></span>
                            <span class="text-xs font-bold text-red-400 ml-1"><?= number_format($_priceInt) ?> <?= $_currency ?></span>
                        </div>
                        <?php else: ?>
                        <span class="text-xs font-bold text-white"><?= number_format($_priceInt) ?> <?= $_currency ?></span>
                        <?php endif; ?>
                        <?php if (($item['rating_avg'] ?? 0) > 0): ?>
                        <span class="flex items-center gap-0.5 text-[10px] text-yellow-400">
                            <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            <?= number_format((float)$item['rating_avg'], 1) ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <!-- 버튼 -->
                    <?php $_canInstall = $_isFree || isset($purchasedSlugs[$item['slug']]); ?>
                    <div class="flex gap-1.5">
                        <?php if ($_canInstall): ?>
                        <button type="button" onclick="event.stopPropagation(); mpInstallItem(this)"
                                data-slug="<?= htmlspecialchars($item['slug']) ?>"
                                class="flex-1 py-1 text-[11px] font-medium rounded-md bg-indigo-600 hover:bg-indigo-500 text-white transition-colors">
                            설치
                        </button>
                        <button type="button" onclick="event.stopPropagation(); mpDownloadItem(this)"
                                data-slug="<?= htmlspecialchars($item['slug']) ?>"
                                class="py-1 px-2 text-[11px] font-medium rounded-md bg-white/10 hover:bg-white/20 text-white transition-colors">
                            다운로드
                        </button>
                        <?php else: ?>
                        <button type="button" onclick="event.stopPropagation(); mpOpenPurchase(this)"
                                data-slug="<?= htmlspecialchars($item['slug']) ?>"
                                data-name="<?= htmlspecialchars($_itemName) ?>"
                                data-price="<?= $_priceInt ?>"
                                data-currency="<?= htmlspecialchars($_currency) ?>"
                                data-price-label="<?= htmlspecialchars($_priceLabel) ?>"
                                class="flex-1 py-1 text-[11px] font-medium rounded-md bg-emerald-600 hover:bg-emerald-500 text-white transition-colors">
                            구매
                        </button>
                        <?php endif; ?>
                        <?php if (!empty($item['demo_url'])): ?>
                        <a href="<?= htmlspecialchars($item['demo_url']) ?>" target="_blank" rel="noopener"
                           onclick="event.stopPropagation()"
                           class="py-1 px-2 text-[11px] font-medium rounded-md bg-violet-500/30 hover:bg-violet-500/50 text-violet-200 transition-colors">
                            미리보기
                        </a>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <?php endif; ?>
    </div>

    <!-- 페이지네이션 -->
    <?php if ($meta['pages'] > 1): ?>
    <div class="flex items-center justify-center gap-2 pt-2">
        <?php
        $baseQuery = array_filter(['type' => $filterType, 'sort' => $filterSort, 'q' => $filterKeyword, 'free' => $filterFree ? '1' : '']);
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

<?php include __DIR__ . '/_components/mp-actions.php'; ?>

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

function mpRefreshCache(btn) {
    var orig = btn.innerHTML;
    btn.disabled = true;
    btn.querySelector('svg').classList.add('animate-spin');
    fetch('<?= $adminUrl ?>/autoinstall/api', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=clear_cache'
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success) { window.location.reload(); }
        else { alert(d.message || 'Failed'); btn.disabled = false; btn.innerHTML = orig; }
    })
    .catch(function() { btn.disabled = false; btn.innerHTML = orig; });
}
</script>

<?php include __DIR__ . '/_foot.php'; ?>
