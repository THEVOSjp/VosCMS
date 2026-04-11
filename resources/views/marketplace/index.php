<?php
/**
 * VosCMS Public Marketplace - 카탈로그 메인
 * /marketplace — 다국어 + 다크모드 + 언어변환기
 */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$baseUrl = $_ENV['APP_URL'] ?? '';
$locale = $_SESSION['locale'] ?? ($_COOKIE['locale'] ?? ($_ENV['APP_LOCALE'] ?? 'ko'));
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

// 다국어 로드
$_mpLangFile = BASE_PATH . '/resources/lang/' . $locale . '/marketplace.php';
if (!file_exists($_mpLangFile)) $_mpLangFile = BASE_PATH . '/resources/lang/en/marketplace.php';
$_mpLang = file_exists($_mpLangFile) ? require $_mpLangFile : [];
if (!function_exists('__mp')) {
    function __mp(string $key, string $default = ''): string {
        global $_mpLang;
        return $_mpLang[$key] ?? $default ?: $key;
    }
}

try {
    $pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4", $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) { die('Service unavailable'); }

$type = $_GET['type'] ?? '';
$sort = $_GET['sort'] ?? 'popular';
$q = trim($_GET['q'] ?? '');

$where = ["status = 'active'"];
$params = [];
if ($type && in_array($type, ['plugin', 'widget', 'theme', 'skin'])) {
    $where[] = "type = ?"; $params[] = $type;
}
if ($q) {
    $where[] = "(slug LIKE ? OR author_name LIKE ?)"; $params[] = "%{$q}%"; $params[] = "%{$q}%";
}
$orderBy = match ($sort) { 'newest' => 'created_at DESC', 'rating' => 'rating_avg DESC', default => 'download_count DESC' };

$stmt = $pdo->prepare("SELECT * FROM {$prefix}mp_items WHERE " . implode(' AND ', $where) . " ORDER BY is_featured DESC, {$orderBy} LIMIT 48");
$stmt->execute($params);
$items = $stmt->fetchAll(PDO::FETCH_ASSOC);

$typeCounts = [];
foreach (['plugin', 'theme', 'widget', 'skin'] as $t) {
    $cs = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}mp_items WHERE status='active' AND type=?"); $cs->execute([$t]); $typeCounts[$t] = (int)$cs->fetchColumn();
}
$totalCount = array_sum($typeCounts);

$typeLabels = ['plugin' => __mp('plugins'), 'theme' => __mp('themes'), 'widget' => __mp('widgets'), 'skin' => __mp('skins')];
?>
<!DOCTYPE html>
<html lang="<?= $locale ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= __mp('hero_title') ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <style>body { font-family: 'Pretendard', -apple-system, sans-serif; } [x-cloak]{display:none!important;}</style>
    <script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.13/dist/cdn.min.js"></script>
    <script>
        if (localStorage.getItem('darkMode') === 'true' || (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-zinc-50 dark:bg-zinc-900 min-h-screen transition-colors">
<!-- 헤더 -->
<nav class="bg-white dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700">
    <div class="max-w-6xl mx-auto px-6 h-14 flex items-center justify-between">
        <a href="<?= $baseUrl ?>/marketplace" class="font-bold text-lg text-zinc-800 dark:text-white">
            <span class="text-indigo-600">Vos</span>CMS <span class="text-sm font-normal text-zinc-400"><?= __mp('marketplace') ?></span>
        </a>
        <div class="flex items-center gap-2">
            <!-- 언어 변환기 -->
            <?php include BASE_PATH . '/resources/views/components/language-selector.php'; ?>

            <!-- 다크모드 토글 -->
            <button id="darkModeBtn" class="p-2 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition" title="Dark Mode">
                <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            </button>

            <a href="<?= $baseUrl ?>/developer" class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300"><?= __mp('developer_portal') ?></a>
            <a href="<?= $baseUrl ?>/" class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">VosCMS</a>
        </div>
    </div>
</nav>

<!-- 히어로 -->
<div class="bg-gradient-to-br from-indigo-600 to-purple-700 text-white py-12">
    <div class="max-w-6xl mx-auto px-6 text-center">
        <h1 class="text-3xl font-bold mb-3"><?= __mp('hero_title') ?></h1>
        <p class="text-indigo-200 mb-6"><?= __mp('hero_desc') ?></p>
        <form method="GET" action="<?= $baseUrl ?>/marketplace" class="max-w-lg mx-auto flex gap-2">
            <input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="<?= __mp('search_placeholder') ?>" class="flex-1 px-4 py-3 rounded-lg text-zinc-800 dark:text-white dark:bg-zinc-700 text-sm focus:ring-2 focus:ring-white/50">
            <button type="submit" class="px-6 py-3 bg-white/20 hover:bg-white/30 rounded-lg font-medium text-sm transition-colors"><?= __mp('search') ?></button>
        </form>
    </div>
</div>

<div class="max-w-6xl mx-auto px-6 py-8">
    <!-- 타입 필터 -->
    <div class="flex gap-2 mb-6 flex-wrap">
        <a href="<?= $baseUrl ?>/marketplace" class="px-4 py-2 rounded-lg text-sm font-medium <?= !$type ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700' ?>"><?= __mp('all') ?> (<?= $totalCount ?>)</a>
        <?php foreach ($typeLabels as $tk => $tl): ?>
        <a href="<?= $baseUrl ?>/marketplace?type=<?= $tk ?>" class="px-4 py-2 rounded-lg text-sm font-medium <?= $type === $tk ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700' ?>"><?= $tl ?> (<?= $typeCounts[$tk] ?>)</a>
        <?php endforeach; ?>
    </div>

    <!-- 아이템 그리드 -->
    <?php if (empty($items)): ?>
    <div class="text-center py-16">
        <p class="text-zinc-400 dark:text-zinc-500 text-lg"><?= __mp('no_items') ?></p>
        <p class="text-zinc-300 dark:text-zinc-600 text-sm mt-2"><?= __mp('coming_soon') ?></p>
    </div>
    <?php else: ?>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-5">
        <?php foreach ($items as $item):
            $name = json_decode($item['name'], true);
            $itemName = $name[$locale] ?? $name['en'] ?? $item['slug'];
            $desc = json_decode($item['short_description'] ?? $item['description'] ?? '{}', true);
            $itemDesc = $desc[$locale] ?? $desc['en'] ?? '';
            $price = (float)$item['price'];
            $tc = ['plugin'=>'indigo','theme'=>'purple','widget'=>'emerald','skin'=>'orange'][$item['type']] ?? 'zinc';
        ?>
        <a href="<?= $baseUrl ?>/marketplace/item?slug=<?= urlencode($item['slug']) ?>" class="group bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden hover:shadow-md hover:border-indigo-200 dark:hover:border-indigo-600 transition-all">
            <div class="aspect-[16/9] bg-gradient-to-br from-<?= $tc ?>-50 to-<?= $tc ?>-100 dark:from-<?= $tc ?>-900/20 dark:to-<?= $tc ?>-800/20 flex items-center justify-center relative">
                <?php if ($item['banner_image']): ?>
                <img src="<?= htmlspecialchars($item['banner_image']) ?>" class="w-full h-full object-cover">
                <?php elseif ($item['icon']): ?>
                <img src="<?= htmlspecialchars($item['icon']) ?>" class="w-14 h-14 rounded-xl shadow">
                <?php else: ?>
                <svg class="w-10 h-10 text-<?= $tc ?>-300 dark:text-<?= $tc ?>-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                <?php endif; ?>
                <span class="absolute top-2 left-2 px-2 py-0.5 text-[10px] font-bold rounded bg-<?= $tc ?>-100 text-<?= $tc ?>-700 dark:bg-<?= $tc ?>-900/40 dark:text-<?= $tc ?>-400"><?= $typeLabels[$item['type']] ?? $item['type'] ?></span>
                <?php if ($item['is_featured']): ?>
                <span class="absolute top-2 right-2 px-2 py-0.5 text-[10px] font-bold rounded bg-yellow-100 text-yellow-700 dark:bg-yellow-900/40 dark:text-yellow-400"><?= __mp('featured') ?></span>
                <?php endif; ?>
            </div>
            <div class="p-4">
                <h3 class="font-semibold text-zinc-800 dark:text-zinc-200 group-hover:text-indigo-600 dark:group-hover:text-indigo-400 truncate"><?= htmlspecialchars($itemName) ?></h3>
                <?php if ($itemDesc): ?><p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 line-clamp-2" style="display:-webkit-box;-webkit-line-clamp:2;-webkit-box-orient:vertical;overflow:hidden"><?= htmlspecialchars(mb_substr($itemDesc, 0, 80)) ?></p><?php endif; ?>
                <div class="flex items-center justify-between mt-3">
                    <span class="text-sm font-bold <?= $price <= 0 ? 'text-green-600 dark:text-green-400' : 'text-zinc-800 dark:text-zinc-200' ?>"><?= $price <= 0 ? __mp('free') : number_format($price, 2) . ' ' . $item['currency'] ?></span>
                    <div class="flex items-center gap-2 text-xs text-zinc-400">
                        <?php if ($item['rating_avg'] > 0): ?>
                        <span class="flex items-center gap-0.5"><svg class="w-3 h-3 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg><?= number_format($item['rating_avg'], 1) ?></span>
                        <?php endif; ?>
                        <span><?= number_format($item['download_count']) ?> DL</span>
                    </div>
                </div>
                <?php if ($item['author_name']): ?><p class="text-xs text-zinc-400 mt-2"><?= htmlspecialchars($item['author_name']) ?></p><?php endif; ?>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<footer class="border-t border-zinc-200 dark:border-zinc-700 mt-12 py-6 text-center text-xs text-zinc-400 dark:text-zinc-500">
    VosCMS Marketplace &copy; <?= date('Y') ?> &middot; <a href="<?= $baseUrl ?>/developer" class="hover:underline"><?= __mp('developer_portal') ?></a>
</footer>
<script>
document.getElementById('darkModeBtn')?.addEventListener('click', () => {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('darkMode', isDark);
});
</script>
</body>
</html>
