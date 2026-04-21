<?php
/**
 * VosCMS Marketplace - 아이템 상세 페이지
 */
include __DIR__ . '/_head.php';
$pageHeaderTitle = __mp('title');

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$slug = $_GET['slug'] ?? '';

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    echo '<div class="p-4 bg-red-50 dark:bg-red-900/20 text-red-600 rounded-lg">DB Error</div>';
    include __DIR__ . '/_foot.php';
    return;
}

$stmt = $pdo->prepare("SELECT * FROM {$prefix}mp_items WHERE slug = ? LIMIT 1");
$stmt->execute([$slug]);
$item = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$item) {
    echo '<div class="p-4 bg-zinc-100 dark:bg-zinc-800 text-zinc-500 rounded-lg text-center">Item not found</div>';
    include __DIR__ . '/_foot.php';
    return;
}

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
$versions = $pdo->prepare("SELECT * FROM {$prefix}mp_item_versions WHERE item_id = ? ORDER BY released_at DESC");
$versions->execute([$item['id']]);
$versionList = $versions->fetchAll(PDO::FETCH_ASSOC);

// 리뷰
$reviews = $pdo->prepare("SELECT * FROM {$prefix}mp_reviews WHERE item_id = ? AND status = 'active' ORDER BY created_at DESC LIMIT 10");
$reviews->execute([$item['id']]);
$reviewList = $reviews->fetchAll(PDO::FETCH_ASSOC);

// 구매 여부 확인
$adminId = $_SESSION['admin_id'] ?? '';
$hasPurchased = false;
if ($adminId) {
    $pStmt = $pdo->prepare("SELECT oi.id FROM {$prefix}mp_order_items oi JOIN {$prefix}mp_orders o ON o.id = oi.order_id WHERE o.admin_id = ? AND oi.item_id = ? AND o.status = 'paid' LIMIT 1");
    $pStmt->execute([$adminId, $item['id']]);
    $hasPurchased = (bool)$pStmt->fetch();
}

// 설치 여부
$isInstalled = false;
$pm = $pluginManager ?? \RzxLib\Core\Plugin\PluginManager::getInstance();
if ($pm && $item['type'] === 'plugin') {
    $isInstalled = $pm->isInstalled($item['slug']);
}
?>

<!-- 뒤로가기 -->
<div class="mb-4">
    <a href="<?= $adminUrl ?>/marketplace" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        <?= __mp('browse') ?>
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- 좌측: 상세 정보 -->
    <div class="lg:col-span-2 space-y-6">
        <!-- 헤더 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-start gap-4">
                <?php
                // icon 필드가 실제 이미지 경로인지 확인, 아니면 banner_image 폴백
                $_iconSrc = '';
                if (!empty($item['icon']) && (str_starts_with($item['icon'], '/') || str_starts_with($item['icon'], 'http'))) {
                    $_iconSrc = $item['icon'];
                } elseif (!empty($item['banner_image'])) {
                    $_iconSrc = $item['banner_image'];
                }
                ?>
                <?php if ($_iconSrc): ?>
                <img src="<?= htmlspecialchars($_iconSrc) ?>" alt="" class="w-24 h-24 rounded-xl shadow-md object-cover">
                <?php else: ?>
                <div class="w-24 h-24 rounded-xl bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                    <svg class="w-12 h-12 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                </div>
                <?php endif; ?>
                <div class="flex-1">
                    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($itemName) ?></h1>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1"><?= htmlspecialchars($itemShortDesc) ?></p>
                    <div class="flex items-center gap-4 mt-3 text-sm text-zinc-400">
                        <?php if ($item['author_name']): ?>
                        <span><?= __mp('author') ?>: <?= htmlspecialchars($item['author_name']) ?></span>
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
                    <div class="flex gap-1.5 mt-2">
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

        <!-- 탭 콘텐츠 -->
        <div x-data="{ tab: 'description' }" class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="border-b border-zinc-200 dark:border-zinc-700 flex">
                <button @click="tab='description'" :class="tab==='description' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-zinc-500'" class="px-5 py-3 text-sm font-medium border-b-2 transition-colors"><?= __mp('description_tab') ?></button>
                <button @click="tab='changelog'" :class="tab==='changelog' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-zinc-500'" class="px-5 py-3 text-sm font-medium border-b-2 transition-colors"><?= __mp('changelog_tab') ?></button>
                <button @click="tab='reviews'" :class="tab==='reviews' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-zinc-500'" class="px-5 py-3 text-sm font-medium border-b-2 transition-colors"><?= __mp('reviews_tab') ?> (<?= $item['rating_count'] ?>)</button>
            </div>
            <div class="p-6">
                <div x-show="tab==='description'" class="prose dark:prose-invert max-w-none text-sm">
                    <?= nl2br(htmlspecialchars($itemDesc)) ?>
                </div>
                <div x-show="tab==='changelog'" x-cloak class="space-y-4">
                    <?php if (empty($versionList)): ?>
                    <p class="text-sm text-zinc-400">No changelog available.</p>
                    <?php else: ?>
                    <?php foreach ($versionList as $v): ?>
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
                <div x-show="tab==='reviews'" x-cloak class="space-y-4">
                    <?php if (empty($reviewList)): ?>
                    <p class="text-sm text-zinc-400"><?= __mp('no_reviews') ?></p>
                    <?php else: ?>
                    <?php foreach ($reviewList as $rv): ?>
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
        <!-- 구매/설치 카드 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="text-center mb-4">
                <?php if ($isFree): ?>
                <span class="text-3xl font-bold text-green-600 dark:text-green-400"><?= __mp('free') ?></span>
                <?php else: ?>
                <span class="text-3xl font-bold text-zinc-900 dark:text-white"><?= number_format($price, $currency === 'KRW' || $currency === 'JPY' ? 0 : 2) ?></span>
                <span class="text-lg text-zinc-500 ml-1"><?= $currency ?></span>
                <?php endif; ?>
            </div>

            <?php if ($isInstalled): ?>
            <div class="px-4 py-3 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 rounded-lg text-center text-sm font-medium">
                <?= __mp('installed') ?>
            </div>
            <?php elseif ($hasPurchased || $isFree): ?>
            <button onclick="installItem(<?= $item['id'] ?>)"
                    class="w-full px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors text-sm">
                <?= __mp('install_now') ?>
            </button>
            <?php else: ?>
            <button onclick="purchaseItem(<?= $item['id'] ?>)"
                    class="w-full px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors text-sm">
                <?= __mp('purchase') ?>
            </button>
            <?php endif; ?>
        </div>

        <!-- 정보 카드 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 mb-3"><?= __mp('requirements') ?></h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-zinc-500"><?= __mp('version') ?></dt>
                    <dd class="text-zinc-800 dark:text-zinc-200 font-medium"><?= htmlspecialchars($item['latest_version']) ?></dd>
                </div>
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
                    <dt class="text-zinc-500"><?= __mp('downloads') ?></dt>
                    <dd class="text-zinc-800 dark:text-zinc-200"><?= number_format($item['download_count']) ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-zinc-500"><?= __mp('last_updated') ?></dt>
                    <dd class="text-zinc-800 dark:text-zinc-200"><?= $item['updated_at'] ? date('Y-m-d', strtotime($item['updated_at'])) : '-' ?></dd>
                </div>
            </dl>
        </div>
    </div>
</div>

<script>
function purchaseItem(itemId) {
    if (!confirm('<?= $isFree ? __mp('install_now') : __mp('purchase') ?>?')) return;
    fetch('<?= $adminUrl ?>/marketplace/api', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=purchase&item_id=' + itemId
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            if (data.redirect) {
                window.location.href = data.redirect;
            } else {
                window.location.reload();
            }
        } else {
            alert(data.message || 'Error');
        }
    });
}

function installItem(itemId) {
    if (!confirm('<?= __mp('install_now') ?>?')) return;
    const btn = event.target;
    btn.disabled = true;
    btn.textContent = '<?= __mp('installing') ?>';

    fetch('<?= $adminUrl ?>/marketplace/install', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'item_id=' + itemId
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            alert('<?= __mp('install_success') ?>');
            window.location.reload();
        } else {
            alert(data.message || '<?= __mp('install_failed') ?>');
            btn.disabled = false;
            btn.textContent = '<?= __mp('install_now') ?>';
        }
    });
}
</script>

<?php include __DIR__ . '/_foot.php'; ?>
