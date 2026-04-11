<?php
/**
 * VosCMS Public Marketplace - 아이템 상세 페이지
 * /marketplace/item?slug=xxx
 */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$baseUrl = $_ENV['APP_URL'] ?? '';
$locale = $_SESSION['locale'] ?? ($_COOKIE['locale'] ?? ($_ENV['APP_LOCALE'] ?? 'ko'));
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

// 다국어
$_mpLangFile = BASE_PATH . '/resources/lang/' . $locale . '/marketplace.php';
if (!file_exists($_mpLangFile)) $_mpLangFile = BASE_PATH . '/resources/lang/en/marketplace.php';
$_mpLang = file_exists($_mpLangFile) ? require $_mpLangFile : [];
if (!function_exists('__mp')) {
    function __mp(string $key, string $default = ''): string {
        global $_mpLang;
        return $_mpLang[$key] ?? $default ?: $key;
    }
}

$slug = $_GET['slug'] ?? '';
if (!$slug) { header("Location: {$baseUrl}/marketplace"); exit; }

try {
    $pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4", $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) { die('Service unavailable'); }

$stmt = $pdo->prepare("SELECT * FROM {$prefix}mp_items WHERE slug = ? AND status = 'active'");
$stmt->execute([$slug]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) { header("Location: {$baseUrl}/marketplace"); exit; }

$name = json_decode($item['name'], true);
$itemName = $name[$locale] ?? $name['en'] ?? $item['slug'];
$desc = json_decode($item['description'] ?? '{}', true);
$itemDesc = $desc[$locale] ?? $desc['en'] ?? '';
$shortDesc = json_decode($item['short_description'] ?? '{}', true);
$itemShortDesc = $shortDesc[$locale] ?? $shortDesc['en'] ?? '';
$screenshots = json_decode($item['screenshots'] ?? '[]', true);
$tags = json_decode($item['tags'] ?? '[]', true);
$price = (float)$item['price'];
$isFree = $price <= 0;
$currency = $item['currency'] ?? 'USD';

// 버전 이력
$vStmt = $pdo->prepare("SELECT * FROM {$prefix}mp_item_versions WHERE item_id = ? AND status = 'active' ORDER BY released_at DESC");
$vStmt->execute([$item['id']]);
$versions = $vStmt->fetchAll(PDO::FETCH_ASSOC);

// 리뷰
$rStmt = $pdo->prepare("SELECT * FROM {$prefix}mp_reviews WHERE item_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 10");
$rStmt->execute([$item['id']]);
$reviews = $rStmt->fetchAll(PDO::FETCH_ASSOC);

$typeLabels = ['plugin' => __mp('plugins'), 'theme' => __mp('themes'), 'widget' => __mp('widgets'), 'skin' => __mp('skins')];
$tc = ['plugin'=>'indigo','theme'=>'purple','widget'=>'emerald','skin'=>'orange'][$item['type']] ?? 'zinc';
?>
<!DOCTYPE html>
<html lang="<?= $locale ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($itemName) ?> - VosCMS Marketplace</title>
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
            <?php include BASE_PATH . '/resources/views/components/language-selector.php'; ?>
            <button id="darkModeBtn" class="p-2 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition">
                <svg class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/></svg>
                <svg class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/></svg>
            </button>
            <a href="<?= $baseUrl ?>/developer" class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300"><?= __mp('developer_portal') ?></a>
        </div>
    </div>
</nav>

<div class="max-w-6xl mx-auto px-6 py-8">
    <!-- 뒤로가기 -->
    <a href="<?= $baseUrl ?>/marketplace" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1 mb-6">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        <?= __mp('marketplace') ?>
    </a>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
        <!-- 좌측: 상세 -->
        <div class="lg:col-span-2 space-y-6">
            <!-- 헤더 카드 -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                <div class="flex items-start gap-4">
                    <?php if ($item['icon']): ?>
                    <img src="<?= htmlspecialchars($item['icon']) ?>" alt="" class="w-16 h-16 rounded-xl shadow-md">
                    <?php else: ?>
                    <div class="w-16 h-16 rounded-xl bg-<?= $tc ?>-100 dark:bg-<?= $tc ?>-900/30 flex items-center justify-center">
                        <svg class="w-8 h-8 text-<?= $tc ?>-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                    </div>
                    <?php endif; ?>
                    <div class="flex-1">
                        <div class="flex items-center gap-2 mb-1">
                            <span class="px-2 py-0.5 text-xs font-bold rounded bg-<?= $tc ?>-100 text-<?= $tc ?>-700 dark:bg-<?= $tc ?>-900/30 dark:text-<?= $tc ?>-400"><?= $typeLabels[$item['type']] ?? $item['type'] ?></span>
                            <?php if ($item['is_verified']): ?>
                            <svg class="w-5 h-5 text-blue-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M6.267 3.455a3.066 3.066 0 001.745-.723 3.066 3.066 0 013.976 0 3.066 3.066 0 001.745.723 3.066 3.066 0 012.812 2.812c.051.643.304 1.254.723 1.745a3.066 3.066 0 010 3.976 3.066 3.066 0 00-.723 1.745 3.066 3.066 0 01-2.812 2.812 3.066 3.066 0 00-1.745.723 3.066 3.066 0 01-3.976 0 3.066 3.066 0 00-1.745-.723 3.066 3.066 0 01-2.812-2.812 3.066 3.066 0 00-.723-1.745 3.066 3.066 0 010-3.976 3.066 3.066 0 00.723-1.745 3.066 3.066 0 012.812-2.812zm7.44 5.252a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                            <?php endif; ?>
                            <?php if ($item['is_featured']): ?>
                            <span class="px-2 py-0.5 text-xs font-bold rounded bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400"><?= __mp('featured') ?></span>
                            <?php endif; ?>
                        </div>
                        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($itemName) ?></h1>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1"><?= htmlspecialchars($itemShortDesc) ?></p>
                        <div class="flex items-center gap-4 mt-3 text-sm text-zinc-400">
                            <?php if ($item['author_name']): ?>
                            <span>by <?= htmlspecialchars($item['author_name']) ?></span>
                            <?php endif; ?>
                            <span>v<?= htmlspecialchars($item['latest_version']) ?></span>
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                                <?= number_format($item['download_count']) ?>
                            </span>
                            <?php if ($item['rating_avg'] > 0): ?>
                            <span class="flex items-center gap-1">
                                <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                <?= number_format($item['rating_avg'], 1) ?> (<?= $item['rating_count'] ?>)
                            </span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($tags)): ?>
                        <div class="flex gap-1.5 mt-3">
                            <?php foreach (array_slice($tags, 0, 5) as $tag): ?>
                            <span class="px-2 py-0.5 text-xs rounded-full bg-zinc-100 dark:bg-zinc-700 text-zinc-500 dark:text-zinc-400"><?= htmlspecialchars($tag) ?></span>
                            <?php endforeach; ?>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <?php if (!empty($screenshots)): ?>
            <!-- 스크린샷 -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-4"><?= __mp('screenshots') ?></h3>
                <div class="grid grid-cols-2 gap-3">
                    <?php foreach ($screenshots as $ss): ?>
                    <img src="<?= htmlspecialchars($ss) ?>" alt="" class="rounded-lg border border-zinc-200 dark:border-zinc-700 w-full">
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- 탭 -->
            <div x-data="{ tab: 'description' }" class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <div class="border-b border-zinc-200 dark:border-zinc-700 flex">
                    <button @click="tab='description'" :class="tab==='description' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-zinc-500'" class="px-5 py-3 text-sm font-medium border-b-2 transition-colors"><?= __mp('description_tab') ?></button>
                    <button @click="tab='changelog'" :class="tab==='changelog' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-zinc-500'" class="px-5 py-3 text-sm font-medium border-b-2 transition-colors"><?= __mp('changelog_tab') ?></button>
                    <button @click="tab='reviews'" :class="tab==='reviews' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-zinc-500'" class="px-5 py-3 text-sm font-medium border-b-2 transition-colors"><?= __mp('reviews_tab') ?> (<?= $item['rating_count'] ?>)</button>
                </div>
                <div class="p-6">
                    <!-- 설명 -->
                    <div x-show="tab==='description'" class="prose dark:prose-invert max-w-none text-sm text-zinc-700 dark:text-zinc-300">
                        <?= $itemDesc ? nl2br(htmlspecialchars($itemDesc)) : '<p class="text-zinc-400">' . __mp('no_items') . '</p>' ?>
                    </div>
                    <!-- 변경이력 -->
                    <div x-show="tab==='changelog'" x-cloak class="space-y-4">
                        <?php if (empty($versions)): ?>
                        <p class="text-sm text-zinc-400"><?= __mp('no_items') ?></p>
                        <?php else: ?>
                        <?php foreach ($versions as $v): ?>
                        <div class="border-l-2 border-indigo-300 dark:border-indigo-600 pl-4">
                            <div class="flex items-center gap-2">
                                <span class="font-semibold text-zinc-800 dark:text-zinc-200">v<?= htmlspecialchars($v['version']) ?></span>
                                <span class="text-xs text-zinc-400"><?= $v['released_at'] ? date('Y-m-d', strtotime($v['released_at'])) : '' ?></span>
                            </div>
                            <?php if ($v['changelog']): ?>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1"><?= nl2br(htmlspecialchars($v['changelog'])) ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                    <!-- 리뷰 -->
                    <div x-show="tab==='reviews'" x-cloak class="space-y-4">
                        <?php if (empty($reviews)): ?>
                        <p class="text-sm text-zinc-400"><?= __mp('no_reviews') ?></p>
                        <?php else: ?>
                        <?php foreach ($reviews as $rv): ?>
                        <div class="border-b border-zinc-100 dark:border-zinc-700 pb-4 last:border-0">
                            <div class="flex items-center gap-2 mb-1">
                                <div class="flex">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <svg class="w-4 h-4 <?= $i <= $rv['rating'] ? 'text-yellow-400' : 'text-zinc-200 dark:text-zinc-600' ?>" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    <?php endfor; ?>
                                </div>
                                <?php if ($rv['is_verified_purchase']): ?>
                                <span class="text-xs text-green-600 dark:text-green-400 font-medium"><?= __mp('verified_purchase') ?></span>
                                <?php endif; ?>
                                <span class="text-xs text-zinc-400"><?= $rv['created_at'] ? date('Y-m-d', strtotime($rv['created_at'])) : '' ?></span>
                            </div>
                            <?php if ($rv['title']): ?>
                            <h4 class="font-medium text-zinc-800 dark:text-zinc-200 text-sm"><?= htmlspecialchars($rv['title']) ?></h4>
                            <?php endif; ?>
                            <?php if ($rv['content']): ?>
                            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1"><?= nl2br(htmlspecialchars($rv['content'])) ?></p>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- 우측: 사이드바 -->
        <div class="space-y-4">
            <!-- 가격 카드 -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6 text-center">
                <?php if ($isFree): ?>
                <span class="text-3xl font-bold text-green-600 dark:text-green-400"><?= __mp('free') ?></span>
                <?php else: ?>
                <span class="text-3xl font-bold text-zinc-900 dark:text-white"><?= number_format($price, $currency === 'KRW' || $currency === 'JPY' ? 0 : 2) ?></span>
                <span class="text-lg text-zinc-500 ml-1"><?= $currency ?></span>
                <?php endif; ?>
                <p class="text-xs text-zinc-400 mt-2"><?= $typeLabels[$item['type']] ?? $item['type'] ?> &middot; v<?= htmlspecialchars($item['latest_version']) ?></p>
            </div>

            <!-- 정보 카드 -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                <dl class="space-y-3 text-sm">
                    <div class="flex justify-between">
                        <dt class="text-zinc-500"><?= __mp('version') ?></dt>
                        <dd class="text-zinc-800 dark:text-zinc-200 font-medium"><?= htmlspecialchars($item['latest_version']) ?></dd>
                    </div>
                    <div class="flex justify-between">
                        <dt class="text-zinc-500"><?= __mp('downloads') ?></dt>
                        <dd class="text-zinc-800 dark:text-zinc-200"><?= number_format($item['download_count']) ?></dd>
                    </div>
                    <?php if ($item['rating_avg'] > 0): ?>
                    <div class="flex justify-between">
                        <dt class="text-zinc-500"><?= __mp('rating') ?></dt>
                        <dd class="text-zinc-800 dark:text-zinc-200 flex items-center gap-1">
                            <svg class="w-4 h-4 text-yellow-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            <?= number_format($item['rating_avg'], 1) ?> (<?= $item['rating_count'] ?>)
                        </dd>
                    </div>
                    <?php endif; ?>
                    <?php if ($item['author_name']): ?>
                    <div class="flex justify-between">
                        <dt class="text-zinc-500"><?= __mp('author') ?></dt>
                        <dd class="text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($item['author_name']) ?></dd>
                    </div>
                    <?php endif; ?>
                    <?php if ($item['min_voscms_version']): ?>
                    <div class="flex justify-between">
                        <dt class="text-zinc-500"><?= __mp('min_voscms') ?></dt>
                        <dd class="text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($item['min_voscms_version']) ?></dd>
                    </div>
                    <?php endif; ?>
                    <?php if ($item['min_php_version']): ?>
                    <div class="flex justify-between">
                        <dt class="text-zinc-500"><?= __mp('min_php') ?></dt>
                        <dd class="text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($item['min_php_version']) ?></dd>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between">
                        <dt class="text-zinc-500"><?= __mp('last_updated') ?></dt>
                        <dd class="text-zinc-800 dark:text-zinc-200"><?= $item['updated_at'] ? date('Y-m-d', strtotime($item['updated_at'])) : '-' ?></dd>
                    </div>
                </dl>
            </div>
        </div>
    </div>
</div>

<footer class="border-t border-zinc-200 dark:border-zinc-700 mt-12 py-6 text-center text-xs text-zinc-400 dark:text-zinc-500">
    VosCMS Marketplace &copy; <?= date('Y') ?>
</footer>
<script>
document.getElementById('darkModeBtn')?.addEventListener('click', () => {
    const isDark = document.documentElement.classList.toggle('dark');
    localStorage.setItem('darkMode', isDark);
});
</script>
</body>
</html>
