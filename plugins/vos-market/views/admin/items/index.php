<?php
include __DIR__ . '/../_head.php';
$pageHeaderTitle = __('marketplace.admin_items') ?: '아이템 관리';
$db = mkt_pdo(); $pfx = $_mktPrefix;

$type   = $_GET['type']   ?? '';
$status = $_GET['status'] ?? '';
$q      = trim($_GET['q'] ?? '');
$page   = max(1, (int)($_GET['page'] ?? 1));
$perPage = 24; $offset = ($page - 1) * $perPage;

// 타입별 카운트
$typeCounts = [''=>0, 'plugin'=>0, 'theme'=>0, 'widget'=>0, 'skin'=>0];
$tcRows = $db->query("SELECT type, COUNT(*) cnt FROM {$pfx}mkt_items GROUP BY type")->fetchAll();
foreach ($tcRows as $r) { $typeCounts[$r['type']] = (int)$r['cnt']; }
$typeCounts[''] = array_sum(array_filter($typeCounts, fn($k) => $k !== '', ARRAY_FILTER_USE_KEY));

$where = []; $params = [];
if ($type)   { $where[] = "i.type=?";   $params[] = $type; }
if ($status) { $where[] = "i.status=?"; $params[] = $status; }
if ($q)      { $where[] = "(i.slug LIKE ? OR JSON_SEARCH(i.name,'one',?) IS NOT NULL)"; $params[] = "%$q%"; $params[] = "%$q%"; }
$ws = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$st = $db->prepare("SELECT COUNT(*) FROM {$pfx}mkt_items i $ws"); $st->execute($params); $total = (int)$st->fetchColumn();
$st2 = $db->prepare("SELECT i.* FROM {$pfx}mkt_items i $ws ORDER BY i.updated_at DESC LIMIT $perPage OFFSET $offset");
$st2->execute($params); $items = $st2->fetchAll();
$totalPages = (int)ceil($total / $perPage);

$typeLabels = [
    ''       => __('marketplace.all')     ?: '전체',
    'plugin' => __('marketplace.plugins') ?: '플러그인',
    'theme'  => __('marketplace.themes')  ?: '테마',
    'widget' => __('marketplace.widgets') ?: '위젯',
    'skin'   => __('marketplace.skins')   ?: '스킨',
];
$typeColors = ['plugin'=>'indigo', 'theme'=>'purple', 'widget'=>'blue', 'skin'=>'emerald'];
$statusMeta = [
    'active'    => [__('marketplace.sf_status_active')    ?: '활성',    'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'],
    'pending'   => [__('marketplace.sf_status_pending')   ?: '대기',    'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'],
    'draft'     => [__('marketplace.sf_status_draft')     ?: '초안',    'bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400'],
    'suspended' => [__('marketplace.sf_status_suspended') ?: '정지',    'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'],
    'archived'  => [__('marketplace.sf_status_archived')  ?: '보관',    'bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-500'],
];
$adminUrl = $_mktAdmin;
?>

<div class="flex items-center justify-between mb-5">
    <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= __('marketplace.admin_items') ?: '아이템 관리' ?></h1>
    </div>
    <div class="flex gap-2">
    <a href="<?= $adminUrl ?>/market/items/bulk-pack"
       class="inline-flex items-center gap-2 px-3 py-2 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg text-sm font-medium hover:border-indigo-400 transition"
       title="packages/ 배포 디렉토리 스캔하여 자동 등록">
        📂 배포 스캔
    </a>
    <a href="<?= $adminUrl ?>/market/items/create"
       class="inline-flex items-center gap-2 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-sm transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
        <?= __('marketplace.admin_item_register') ?: '아이템 등록' ?>
    </a>
    </div>
</div>

<!-- 검색·상태 필터 -->
<form method="GET" class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl p-4 mb-5 flex flex-wrap gap-3 items-center">
    <?php if ($type): ?><input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>"><?php endif; ?>
    <div class="flex-1 min-w-[200px]">
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="<?= htmlspecialchars(__('marketplace.admin_search_slug') ?: '슬러그 · 이름 검색') ?>"
               class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-indigo-500">
    </div>
    <select name="status" class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm bg-white dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300">
        <option value=""><?= __('marketplace.admin_all_status') ?: '모든 상태' ?></option>
        <?php foreach ($statusMeta as $v => [$l]): ?>
        <option value="<?= $v ?>" <?= $status === $v ? 'selected' : '' ?>><?= $l ?></option>
        <?php endforeach; ?>
    </select>
    <button class="px-4 py-2 bg-zinc-800 dark:bg-zinc-600 hover:bg-zinc-900 text-white rounded-lg text-sm font-medium"><?= __('marketplace.search') ?: '검색' ?></button>
    <?php if ($status || $q): ?>
    <a href="<?= $adminUrl ?>/market/items<?= $type ? '?type='.$type : '' ?>"
       class="px-3 py-2 text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300"><?= __('marketplace.admin_reset') ?: '초기화' ?></a>
    <?php endif; ?>
</form>

<!-- 타입 탭 버튼 -->
<div class="flex items-center gap-2 mb-4 flex-wrap">
    <?php foreach ($typeLabels as $v => $l):
        $cnt   = $typeCounts[$v] ?? 0;
        $qs    = http_build_query(array_filter(['status'=>$status,'q'=>$q,'type'=>$v]));
        $isActive = ($type === $v);
    ?>
    <a href="<?= $adminUrl ?>/market/items<?= $qs ? '?'.$qs : '' ?>"
       class="inline-flex items-center gap-1.5 px-4 py-1.5 rounded-full text-sm font-medium border transition
              <?= $isActive
                  ? 'bg-indigo-600 border-indigo-600 text-white shadow-sm'
                  : 'bg-white dark:bg-zinc-800 border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-400 hover:border-indigo-400 hover:text-indigo-600' ?>">
        <?= $l ?>
        <span class="<?= $isActive ? 'bg-indigo-500 text-white' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-500 dark:text-zinc-400' ?> text-[11px] font-bold px-1.5 py-0.5 rounded-full leading-none">
            <?= number_format($cnt) ?>
        </span>
    </a>
    <?php endforeach; ?>
</div>

<!-- 결과 수 -->
<p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">
    <?= sprintf(__('marketplace.admin_items_count') ?: '%d개 아이템', number_format($total)) ?>
    <?php if ($totalPages > 1): ?>(<?= sprintf(__('marketplace.admin_page_of') ?: '%d / %d 페이지', $page, $totalPages) ?>)<?php endif; ?>
</p>

<?php if (empty($items)): ?>
<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-16 text-center">
    <p class="text-zinc-400 dark:text-zinc-500"><?= __('marketplace.admin_no_results') ?: '조건에 맞는 아이템이 없습니다.' ?></p>
</div>
<?php else: ?>
<div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 2xl:grid-cols-6 gap-5">
<?php foreach ($items as $it):
    $nameArr = json_decode($it['name']    ?? '{}', true) ?: [];
    $shortArr = json_decode($it['short_description'] ?? '{}', true) ?: [];
    $itemName = $nameArr[$_mktLocale] ?? $nameArr['en'] ?? $it['slug'];
    $itemDesc = $shortArr[$_mktLocale] ?? $shortArr['en'] ?? '';
    $tc = $typeColors[$it['type']] ?? 'zinc';
    [$sl, $sc] = $statusMeta[$it['status']] ?? ['?', 'bg-zinc-100 text-zinc-500'];
    $bannerSrc = ($it['banner_image'] && str_starts_with($it['banner_image'], '/')) ? $it['banner_image'] : '';
    if (!$bannerSrc && !empty($it['slug'])) {
        $slug = $it['slug'];
        foreach (['widgets', 'plugins', 'themes', 'skins'] as $dir) {
            $rel = '/' . $dir . '/' . rawurlencode($slug) . '/thumbnail.png';
            if (file_exists(BASE_PATH . $rel)) { $bannerSrc = $rel; break; }
        }
    }
?>
<a href="<?= $adminUrl ?>/market/items/show?id=<?= $it['id'] ?>"
   class="group bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden hover:shadow-md hover:border-indigo-400 dark:hover:border-indigo-500 transition-all flex flex-col cursor-pointer">
    <div class="aspect-[16/9] bg-gradient-to-br from-<?= $tc ?>-50 to-<?= $tc ?>-100 dark:from-<?= $tc ?>-900/20 dark:to-<?= $tc ?>-800/20 relative overflow-hidden flex-shrink-0">
        <?php if ($bannerSrc): ?>
        <img src="<?= htmlspecialchars($bannerSrc) ?>" class="w-full h-full object-cover" loading="lazy" onerror="this.style.display='none'">
        <?php endif; ?>
        <span class="absolute top-2 left-2 px-2 py-0.5 text-[10px] font-bold rounded bg-<?= $tc ?>-100 text-<?= $tc ?>-700 dark:bg-<?= $tc ?>-900/60 dark:text-<?= $tc ?>-300">
            <?= $typeLabels[$it['type']] ?? $it['type'] ?>
        </span>
        <span class="absolute top-2 right-2 px-2 py-0.5 text-[10px] font-bold rounded <?= $sc ?>"><?= $sl ?></span>
        <?php if ($it['is_featured']): ?>
        <span class="absolute bottom-2 left-2 px-2 py-0.5 text-[10px] font-bold rounded bg-yellow-100 text-yellow-700 dark:bg-yellow-900/60 dark:text-yellow-300"><?= __('marketplace.admin_featured') ?: '★ 추천' ?></span>
        <?php endif; ?>
    </div>
    <div class="p-4 flex flex-col flex-1">
        <div class="flex-1">
            <h3 class="font-semibold text-zinc-800 dark:text-zinc-100 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 truncate">
                <?= htmlspecialchars($itemName) ?>
            </h3>
            <p class="text-[11px] text-zinc-400 dark:text-zinc-500 font-mono mt-0.5 truncate"><?= htmlspecialchars($it['slug']) ?></p>
            <?php if (!empty($it['product_key'])): ?>
            <button type="button"
                    onclick="event.preventDefault(); event.stopPropagation(); copyProductKey(this,'<?= htmlspecialchars($it['product_key']) ?>')"
                    title="클릭하여 복사"
                    class="mt-0.5 w-full text-left font-mono text-[10px] text-zinc-300 dark:text-zinc-600 hover:text-indigo-500 dark:hover:text-indigo-400 truncate transition leading-tight">
                🔑 <?= htmlspecialchars($it['product_key']) ?>
            </button>
            <?php endif; ?>
            <?php if ($itemDesc): ?>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2 line-clamp-2"><?= htmlspecialchars(mb_substr($itemDesc, 0, 80)) ?></p>
            <?php endif; ?>
        </div>
        <div class="mt-3 pt-3 border-t border-zinc-100 dark:border-zinc-700 flex items-center justify-between">
            <span class="text-xs text-zinc-500 font-mono">v<?= htmlspecialchars($it['latest_version']) ?> · <?= number_format((int)$it['download_count']) ?>↓</span>
            <span class="text-sm font-bold <?= (float)$it['price'] > 0 ? 'text-zinc-800 dark:text-zinc-200' : 'text-green-600' ?>">
                <?= (float)$it['price'] > 0 ? number_format((float)$it['price']) . ' ' . $it['currency'] : (__('marketplace.free') ?: '무료') ?>
            </span>
        </div>
    </div>
</a>
<?php endforeach; ?>
</div>

<?php if ($totalPages > 1): ?>
<div class="mt-8 flex justify-center items-center gap-1">
    <?php if ($page > 1): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page-1])) ?>"
       class="px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">‹</a>
    <?php endif; ?>
    <?php for ($p = max(1, $page-2); $p <= min($totalPages, $page+2); $p++): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"
       class="px-3 py-2 rounded-lg border text-sm font-medium <?= $p === $page ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' ?>">
        <?= $p ?>
    </a>
    <?php endfor; ?>
    <?php if ($page < $totalPages): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page+1])) ?>"
       class="px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">›</a>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<script>
function copyProductKey(btn, key) {
    navigator.clipboard.writeText(key).then(() => {
        const orig = btn.innerHTML;
        btn.innerHTML = '✅ 복사됨';
        btn.classList.add('text-green-500');
        setTimeout(() => { btn.innerHTML = orig; btn.classList.remove('text-green-500'); }, 1500);
    });
}
</script>
<?php include __DIR__ . '/../_foot.php'; ?>
