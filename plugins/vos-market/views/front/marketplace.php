<?php
// Public marketplace — catalog + detail via ?slug=xxx
$pfx = $_ENV['DB_PREFIX'] ?? 'rzx_';
$locale = $config['locale'] ?? 'ko';
$baseUrl = $config['app_url'] ?? '';

function mkt_pdo_front(): PDO {
    static $pdo = null;
    if ($pdo) return $pdo;
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
    );
    return $pdo;
}
function mkt_lv(?string $json, string $locale): string {
    if (!$json) return '';
    $d = json_decode($json, true);
    if (!is_array($d)) return $json;
    return $d[$locale] ?? $d['en'] ?? reset($d) ?: '';
}
function mkt_price(float $price, string $currency): string {
    if ($price <= 0) return '무료';
    return match($currency) {
        'JPY' => '¥' . number_format($price, 0),
        'KRW' => '₩' . number_format($price, 0),
        'USD' => '$' . number_format($price, 2),
        default => number_format($price) . ' ' . $currency,
    };
}

$db = mkt_pdo_front();
$slug = trim($_GET['slug'] ?? '');

// ─── Item detail ────────────────────────────────────────────────────────────
if ($slug) {
    $st = $db->prepare("SELECT i.*,c.name cat_name,p.display_name author FROM {$pfx}mkt_items i LEFT JOIN {$pfx}mkt_categories c ON c.id=i.category_id LEFT JOIN {$pfx}mkt_partners p ON p.id=i.partner_id WHERE i.slug=? AND i.status='active'");
    $st->execute([$slug]); $item = $st->fetch();
    if (!$item) { http_response_code(404); ?>
    <!DOCTYPE html><html lang="<?= $locale ?>"><head><meta charset="UTF-8"><title>Not Found</title></head>
    <body class="flex items-center justify-center min-h-screen bg-zinc-50"><p class="text-zinc-500">아이템을 찾을 수 없습니다.</p></body></html>
    <?php return; }

    $versions = $db->prepare("SELECT version,changelog,created_at FROM {$pfx}mkt_item_versions WHERE item_id=? AND status='active' ORDER BY created_at DESC LIMIT 10");
    $versions->execute([$item['id']]); $versions = $versions->fetchAll();

    $iname   = mkt_lv($item['name'], $locale);
    $ishort  = mkt_lv($item['short_description'], $locale);
    $idesc   = mkt_lv($item['description'], $locale);
    $catname = mkt_lv($item['cat_name']??null, $locale);
    $tags    = json_decode($item['tags']??'[]', true) ?: [];
    $screens = json_decode($item['screenshots']??'[]', true) ?: [];
    $priceStr = mkt_price((float)$item['price'], $item['currency']??'JPY');
    $typeLabels = ['plugin'=>'플러그인','theme'=>'테마','widget'=>'위젯','skin'=>'스킨'];
    include __DIR__ . '/layout_head.php';
    ?>
<div class="max-w-6xl mx-auto px-4 py-10">
    <div class="mb-6">
        <a href="<?= $baseUrl ?>/marketplace" class="text-sm text-zinc-500 hover:text-zinc-700">← 목록으로</a>
    </div>
    <div class="grid grid-cols-1 lg:grid-cols-4 gap-8">
    <div class="lg:col-span-3 space-y-8">
        <?php if ($item['banner_image']): ?>
        <img src="<?= htmlspecialchars($item['banner_image']) ?>" alt="" class="w-full rounded-2xl object-cover max-h-64">
        <?php endif; ?>

        <div class="flex gap-4 items-start">
            <?php if ($item['icon']): ?>
            <img src="<?= htmlspecialchars($item['icon']) ?>" alt="" class="w-20 h-20 rounded-2xl border border-zinc-200 dark:border-zinc-700 object-cover flex-shrink-0" onerror="this.style.display='none'">
            <?php endif; ?>
            <div>
                <h1 class="text-3xl font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($iname ?: $slug) ?></h1>
                <?php if ($ishort): ?><p class="text-zinc-500 mt-2"><?= htmlspecialchars($ishort) ?></p><?php endif; ?>
                <div class="flex flex-wrap gap-2 mt-3">
                    <?php if ($catname): ?><span class="text-xs px-2 py-0.5 rounded bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400"><?= htmlspecialchars($catname) ?></span><?php endif; ?>
                    <span class="text-xs px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400"><?= $typeLabels[$item['type']]??$item['type'] ?></span>
                    <span class="text-xs px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-700 text-zinc-500 font-mono">v<?= htmlspecialchars($item['latest_version']??'') ?></span>
                </div>
            </div>
        </div>

        <?php if ($idesc): ?>
        <div class="prose dark:prose-invert max-w-none text-zinc-700 dark:text-zinc-300 whitespace-pre-line">
            <?= htmlspecialchars($idesc) ?>
        </div>
        <?php endif; ?>

        <?php if ($screens): ?>
        <div>
            <h3 class="font-semibold text-zinc-800 dark:text-zinc-200 mb-3">스크린샷</h3>
            <div class="grid grid-cols-2 gap-3">
                <?php foreach ($screens as $sc): ?>
                <img src="<?= htmlspecialchars($sc) ?>" alt="" class="rounded-xl border border-zinc-200 dark:border-zinc-700 w-full object-cover">
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <?php if ($versions): ?>
        <div>
            <h3 class="font-semibold text-zinc-800 dark:text-zinc-200 mb-3">버전 내역</h3>
            <div class="space-y-3">
            <?php foreach ($versions as $ver): ?>
            <div class="bg-zinc-50 dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl p-4">
                <div class="flex items-center justify-between mb-1">
                    <span class="font-mono font-bold text-zinc-700 dark:text-zinc-300">v<?= htmlspecialchars($ver['version']) ?></span>
                    <span class="text-xs text-zinc-400"><?= htmlspecialchars(substr($ver['created_at']??'',0,10)) ?></span>
                </div>
                <?php if ($ver['changelog']): ?><p class="text-sm text-zinc-500 whitespace-pre-line"><?= htmlspecialchars($ver['changelog']) ?></p><?php endif; ?>
            </div>
            <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <div class="space-y-5">
        <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700 p-5 sticky top-6">
            <p class="text-3xl font-bold text-zinc-900 dark:text-white mb-4"><?= $priceStr ?></p>
            <?php if ((float)$item['price'] > 0): ?>
            <button class="w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-medium transition mb-3">구매하기</button>
            <?php else: ?>
            <a href="<?= $baseUrl ?>/api/market/download?slug=<?= urlencode($slug) ?>" class="block w-full py-3 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl font-medium transition text-center mb-3">무료 다운로드</a>
            <?php endif; ?>
            <?php if ($item['demo_url']): ?>
            <a href="<?= htmlspecialchars($item['demo_url']) ?>" target="_blank" class="block w-full py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-xl text-sm text-center text-zinc-600 dark:text-zinc-400 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">데모 보기</a>
            <?php endif; ?>
            <dl class="mt-5 space-y-2 text-sm border-t border-zinc-200 dark:border-zinc-700 pt-4">
                <div class="flex justify-between"><dt class="text-zinc-500">제작자</dt><dd class="text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($item['author']??'-') ?></dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">버전</dt><dd class="font-mono text-zinc-600 dark:text-zinc-400"><?= htmlspecialchars($item['latest_version']??'-') ?></dd></div>
                <div class="flex justify-between"><dt class="text-zinc-500">다운로드</dt><dd class="text-zinc-600 dark:text-zinc-400"><?= number_format((int)$item['download_count']) ?></dd></div>
                <?php if ($item['min_voscms_version']): ?><div class="flex justify-between"><dt class="text-zinc-500">최소 VosCMS</dt><dd class="font-mono text-xs text-zinc-500"><?= htmlspecialchars($item['min_voscms_version']) ?></dd></div><?php endif; ?>
                <?php if ($item['min_php_version']): ?><div class="flex justify-between"><dt class="text-zinc-500">최소 PHP</dt><dd class="font-mono text-xs text-zinc-500"><?= htmlspecialchars($item['min_php_version']) ?></dd></div><?php endif; ?>
            </dl>
            <?php if ($tags): ?>
            <div class="mt-4 flex flex-wrap gap-1">
                <?php foreach ($tags as $tag): ?><span class="text-xs px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-700 text-zinc-500"><?= htmlspecialchars($tag) ?></span><?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    </div>
</div>
    <?php include __DIR__ . '/layout_foot.php'; return;
}

// ─── Catalog ────────────────────────────────────────────────────────────────
$type    = $_GET['type'] ?? '';
$cat     = $_GET['cat'] ?? '';
$q       = trim($_GET['q'] ?? '');
$page    = max(1,(int)($_GET['page']??1));
$perPage = 24; $offset = ($page-1)*$perPage;
$where = ["i.status='active'"]; $params = [];
if ($type) { $where[] = "i.type=?"; $params[] = $type; }
if ($cat)  { $where[] = "c.slug=?"; $params[] = $cat; }
if ($q)    { $where[] = "(i.slug LIKE ? OR i.name LIKE ?)"; $params[] = "%$q%"; $params[] = "%$q%"; }
$ws = 'WHERE ' . implode(' AND ', $where);

$total = (int)$db->prepare("SELECT COUNT(*) FROM {$pfx}mkt_items i LEFT JOIN {$pfx}mkt_categories c ON c.id=i.category_id $ws")->execute($params)
    ? ($db->prepare("SELECT COUNT(*) FROM {$pfx}mkt_items i LEFT JOIN {$pfx}mkt_categories c ON c.id=i.category_id $ws") ?: null) : null;
$stc = $db->prepare("SELECT COUNT(*) FROM {$pfx}mkt_items i LEFT JOIN {$pfx}mkt_categories c ON c.id=i.category_id $ws");
$stc->execute($params); $total = (int)$stc->fetchColumn();

$sti = $db->prepare("SELECT i.*,c.slug cat_slug,c.name cat_name,p.display_name author FROM {$pfx}mkt_items i LEFT JOIN {$pfx}mkt_categories c ON c.id=i.category_id LEFT JOIN {$pfx}mkt_partners p ON p.id=i.partner_id $ws ORDER BY i.created_at DESC LIMIT $perPage OFFSET $offset");
$sti->execute($params); $items = $sti->fetchAll();
$totalPages = (int)ceil($total/$perPage);
$cats = $db->query("SELECT slug,name FROM {$pfx}mkt_categories WHERE is_active=1 ORDER BY sort_order")->fetchAll();
$typeLabels = ['plugin'=>'플러그인','theme'=>'테마','widget'=>'위젯','skin'=>'스킨'];
include __DIR__ . '/layout_head.php';
?>
<div class="max-w-7xl mx-auto px-4 py-10">
    <div class="mb-8">
        <h1 class="text-3xl font-bold text-zinc-900 dark:text-white">VosCMS 마켓플레이스</h1>
        <p class="text-zinc-500 mt-2">플러그인, 테마, 위젯을 탐색하세요</p>
    </div>

    <div class="flex gap-8">
    <aside class="w-48 flex-shrink-0 hidden md:block">
        <div class="space-y-6">
            <div>
                <p class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-2">타입</p>
                <ul class="space-y-1">
                    <li><a href="?<?= http_build_query(array_merge($_GET,['type'=>'','page'=>1])) ?>" class="text-sm <?= !$type?'text-indigo-600 dark:text-indigo-400 font-medium':'text-zinc-600 dark:text-zinc-400 hover:text-zinc-800' ?>">전체</a></li>
                    <?php foreach ($typeLabels as $tv=>$tl): ?>
                    <li><a href="?<?= http_build_query(array_merge($_GET,['type'=>$tv,'page'=>1])) ?>" class="text-sm <?= $type===$tv?'text-indigo-600 dark:text-indigo-400 font-medium':'text-zinc-600 dark:text-zinc-400 hover:text-zinc-800' ?>"><?= $tl ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php if ($cats): ?>
            <div>
                <p class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-2">카테고리</p>
                <ul class="space-y-1">
                    <li><a href="?<?= http_build_query(array_merge($_GET,['cat'=>'','page'=>1])) ?>" class="text-sm <?= !$cat?'text-indigo-600 dark:text-indigo-400 font-medium':'text-zinc-600 dark:text-zinc-400 hover:text-zinc-800' ?>">전체</a></li>
                    <?php foreach ($cats as $c): $cn=mkt_lv($c['name'],$locale)?:$c['slug']; ?>
                    <li><a href="?<?= http_build_query(array_merge($_GET,['cat'=>$c['slug'],'page'=>1])) ?>" class="text-sm <?= $cat===$c['slug']?'text-indigo-600 dark:text-indigo-400 font-medium':'text-zinc-600 dark:text-zinc-400 hover:text-zinc-800' ?>"><?= htmlspecialchars($cn) ?></a></li>
                    <?php endforeach; ?>
                </ul>
            </div>
            <?php endif; ?>
        </div>
    </aside>

    <div class="flex-1">
        <form method="GET" class="flex gap-2 mb-6">
            <?php if ($type): ?><input type="hidden" name="type" value="<?= htmlspecialchars($type) ?>"><?php endif; ?>
            <?php if ($cat): ?><input type="hidden" name="cat" value="<?= htmlspecialchars($cat) ?>"><?php endif; ?>
            <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="아이템 검색..."
                class="flex-1 px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-xl text-sm bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200">
            <button class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-xl text-sm transition">검색</button>
        </form>

        <?php if (empty($items)): ?>
        <div class="text-center py-24"><p class="text-zinc-400">아이템을 찾을 수 없습니다.</p></div>
        <?php else: ?>
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-4">
        <?php foreach ($items as $it):
            $iname = mkt_lv($it['name'], $locale) ?: $it['slug'];
            $ishort = mkt_lv($it['short_description'], $locale);
            $priceStr = mkt_price((float)$it['price'], $it['currency']??'JPY');
        ?>
        <a href="?slug=<?= urlencode($it['slug']) ?>" class="group bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700 overflow-hidden hover:border-indigo-400 dark:hover:border-indigo-500 hover:shadow-lg transition-all">
            <?php if ($it['banner_image'] || $it['icon']): ?>
            <div class="h-32 bg-zinc-100 dark:bg-zinc-900 flex items-center justify-center overflow-hidden">
                <?php if ($it['banner_image']): ?>
                <img src="<?= htmlspecialchars($it['banner_image']) ?>" alt="" class="w-full h-full object-cover">
                <?php elseif ($it['icon']): ?>
                <img src="<?= htmlspecialchars($it['icon']) ?>" alt="" class="w-16 h-16 rounded-xl" onerror="this.style.display='none'">
                <?php endif; ?>
            </div>
            <?php endif; ?>
            <div class="p-4">
                <div class="flex items-start justify-between gap-2">
                    <h3 class="font-semibold text-zinc-800 dark:text-zinc-200 text-sm leading-snug group-hover:text-indigo-600 dark:group-hover:text-indigo-400 line-clamp-2"><?= htmlspecialchars($iname) ?></h3>
                    <span class="text-xs font-bold text-indigo-600 dark:text-indigo-400 flex-shrink-0"><?= $priceStr ?></span>
                </div>
                <?php if ($ishort): ?><p class="text-xs text-zinc-500 mt-1 line-clamp-2"><?= htmlspecialchars($ishort) ?></p><?php endif; ?>
                <div class="flex items-center gap-1.5 mt-3">
                    <span class="text-[10px] px-1.5 py-0.5 rounded bg-zinc-100 dark:bg-zinc-700 text-zinc-500"><?= $typeLabels[$it['type']]??$it['type'] ?></span>
                    <span class="text-[10px] text-zinc-400">↓ <?= number_format((int)$it['download_count']) ?></span>
                </div>
            </div>
        </a>
        <?php endforeach; ?>
        </div>

        <?php if ($totalPages>1): ?>
        <div class="mt-8 flex justify-center gap-1">
            <?php for ($p=max(1,$page-2);$p<=min($totalPages,$page+2);$p++): ?>
            <a href="?<?= http_build_query(array_merge($_GET,['page'=>$p])) ?>" class="px-3 py-2 rounded-lg border text-sm <?= $p===$page?'bg-indigo-600 border-indigo-600 text-white':'border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100' ?>"><?= $p ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
        <?php endif; ?>
    </div>
    </div>
</div>
<?php include __DIR__ . '/layout_foot.php'; ?>
