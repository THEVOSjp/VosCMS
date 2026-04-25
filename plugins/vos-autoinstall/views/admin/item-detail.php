<?php
/**
 * VosCMS Marketplace - 아이템 상세 페이지
 */
include __DIR__ . '/_head.php';
$pageHeaderTitle = __('autoinstall.title');

$slug           = trim($_GET['slug'] ?? '');
$_pm            = \RzxLib\Core\Plugin\PluginManager::getInstance();
$marketApiBase  = rtrim($_pm ? $_pm->getSetting('vos-autoinstall', 'market_api_url', $_ENV['MARKET_API_URL'] ?? 'https://market.21ces.com/api/market') : ($_ENV['MARKET_API_URL'] ?? 'https://market.21ces.com/api/market'), '/');
$payjpPublicKey = $_ENV['PAYJP_PUBLIC_KEY'] ?? '';
$_apiUrl        = $adminUrl . '/autoinstall/api';
$cacheDir       = (defined('BASE_PATH') ? BASE_PATH : __DIR__ . '/../../../../..') . '/storage/cache';
$_cacheTtl      = (int)($_pm ? $_pm->getSetting('vos-autoinstall', 'cache_ttl', '300') : 300);

if (!function_exists('mpApiFetch')) {
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
}

// ── market API에서 아이템 조회 (locale 지정 → 서버에서 다국어 해석 후 단일 문자열 반환) ─
$itemData = mpApiFetch($marketApiBase . '/item?slug=' . rawurlencode($slug) . '&locale=' . urlencode($locale), $cacheDir, $_cacheTtl);
$item = $itemData['data'] ?? null;

if (!$item) {
    echo '<div class="p-4 bg-zinc-100 dark:bg-zinc-800 text-zinc-500 rounded-lg text-center">Item not found</div>';
    include __DIR__ . '/_foot.php';
    return;
}

// author 필드 정규화 (market API: author, local: author_name)
if (empty($item['author_name']) && !empty($item['author'])) {
    $item['author_name'] = $item['author'];
}

$itemName      = $item['name'] ?: $item['slug'];
$itemDesc      = $item['description'] ?? '';
// HTML 정제: script/iframe/onXX 등 위험 요소만 제거하고 마크업 유지
$safeItemDesc  = '';
if ($itemDesc !== '') {
    $safeItemDesc = preg_replace('#<(script|iframe|object|embed|style)[^>]*>.*?</\1>#isu', '', $itemDesc);
    $safeItemDesc = preg_replace('#\son\w+\s*=\s*(["\']).*?\1#isu', '', $safeItemDesc);
}
$itemShortDesc = $item['short_description'] ?? '';
$screenshots   = json_decode($item['screenshots'] ?? '[]', true);
$tags          = json_decode($item['tags'] ?? '[]', true);
$price         = (float)($item['price'] ?? 0);
$isFree        = $price <= 0;
$currency      = $item['currency'] ?? 'USD';

// ── 버전 이력 (market API) ────────────────────────────
$versionsData = mpApiFetch($marketApiBase . '/item/versions?slug=' . rawurlencode($slug), $cacheDir, $_cacheTtl);
$versionList  = $versionsData['data'] ?? [];

// released_at → created_at 정규화
foreach ($versionList as &$v) {
    if (empty($v['released_at']) && !empty($v['created_at'])) {
        $v['released_at'] = $v['created_at'];
    }
}
unset($v);

// ── 리뷰 (마켓 API에서 승인된 리뷰만 조회) ──────────────
$reviewsData = mpApiFetch($marketApiBase . '/item/reviews?slug=' . rawurlencode($slug), $cacheDir, $_cacheTtl);
$reviewList  = $reviewsData['data'] ?? [];

// ── 구매/설치 여부 (로컬 DB) ──────────────────────────
$hasPurchased   = false;
$isInstalled    = false;
$itemLicenseKey = '';
$prefix         = $_ENV['DB_PREFIX'] ?? 'rzx_';
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $adminId = $_SESSION['admin_id'] ?? '';
    if ($adminId && !empty($item['id'])) {
        $pStmt = $pdo->prepare(
            "SELECT oi.id FROM {$prefix}mp_order_items oi
             JOIN {$prefix}mp_orders o ON o.id = oi.order_id
             WHERE o.admin_id = ? AND oi.item_id = ? AND o.status = 'paid' LIMIT 1"
        );
        $pStmt->execute([$adminId, $item['id']]);
        $hasPurchased = (bool)$pStmt->fetch();
    }
    // 무료/구매완료 항목의 다운로드용 라이선스 키 조회
    if ($hasPurchased && !empty($item['slug'])) {
        $lStmt = $pdo->prepare("SELECT license_key FROM {$prefix}mp_licenses WHERE item_slug = ? ORDER BY id DESC LIMIT 1");
        $lStmt->execute([$item['slug']]);
        $itemLicenseKey = (string)($lStmt->fetchColumn() ?: '');
    }
} catch (PDOException $e) {
    // 구매 여부 확인 실패 시 무시
}
$pm = $pluginManager ?? \RzxLib\Core\Plugin\PluginManager::getInstance();
if ($pm && ($item['type'] ?? '') === 'plugin') {
    $isInstalled = $pm->isInstalled($item['slug']);
}
?>

<!-- 뒤로가기 -->
<div class="mb-4">
    <a href="<?= $adminUrl ?>/autoinstall" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        <?= __('autoinstall.title') ?>
    </a>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
    <!-- 좌측: 상세 정보 -->
    <div class="lg:col-span-3 space-y-6">
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
                        <span><?= __('autoinstall.author') ?>: <?= htmlspecialchars($item['author_name']) ?></span>
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

        <!-- 메타 정보 테이블 -->
        <?php
            $_typeLabels = [
                'plugin' => __('autoinstall.plugins'),
                'theme'  => __('autoinstall.themes'),
                'widget' => __('autoinstall.widgets'),
                'skin'   => __('autoinstall.skins'),
            ];
            $_priceLabel = $isFree
                ? __('autoinstall.free')
                : number_format($price, $currency === 'KRW' || $currency === 'JPY' ? 0 : 2) . ' ' . $currency;
        ?>
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <table class="w-full text-sm">
                <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
                    <tr>
                        <th class="w-36 bg-zinc-50 dark:bg-zinc-900/50 px-5 py-3 text-left text-zinc-500 dark:text-zinc-400 font-medium"><?= __('autoinstall.meta_type') ?></th>
                        <td class="px-5 py-3 text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($_typeLabels[$item['type']] ?? $item['type']) ?></td>
                    </tr>
                    <tr>
                        <th class="bg-zinc-50 dark:bg-zinc-900/50 px-5 py-3 text-left text-zinc-500 dark:text-zinc-400 font-medium"><?= __('autoinstall.meta_short') ?></th>
                        <td class="px-5 py-3 text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($itemShortDesc ?: '-') ?></td>
                    </tr>
                    <tr>
                        <th class="bg-zinc-50 dark:bg-zinc-900/50 px-5 py-3 text-left text-zinc-500 dark:text-zinc-400 font-medium"><?= __('autoinstall.meta_slug') ?></th>
                        <td class="px-5 py-3 text-zinc-700 dark:text-zinc-300 font-mono text-xs"><?= htmlspecialchars($item['slug']) ?></td>
                    </tr>
                    <?php if (!empty($item['product_key'])): ?>
                    <tr>
                        <th class="bg-zinc-50 dark:bg-zinc-900/50 px-5 py-3 text-left text-zinc-500 dark:text-zinc-400 font-medium"><?= __('autoinstall.meta_product_key') ?></th>
                        <td class="px-5 py-3 text-zinc-700 dark:text-zinc-300 font-mono text-[11px]"><?= htmlspecialchars($item['product_key']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($item['min_voscms_version'])): ?>
                    <tr>
                        <th class="bg-zinc-50 dark:bg-zinc-900/50 px-5 py-3 text-left text-zinc-500 dark:text-zinc-400 font-medium"><?= __('autoinstall.min_voscms') ?></th>
                        <td class="px-5 py-3 text-zinc-700 dark:text-zinc-300 font-mono text-xs"><?= htmlspecialchars($item['min_voscms_version']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <?php if (!empty($item['min_php_version'])): ?>
                    <tr>
                        <th class="bg-zinc-50 dark:bg-zinc-900/50 px-5 py-3 text-left text-zinc-500 dark:text-zinc-400 font-medium"><?= __('autoinstall.min_php') ?></th>
                        <td class="px-5 py-3 text-zinc-700 dark:text-zinc-300 font-mono text-xs"><?= htmlspecialchars($item['min_php_version']) ?></td>
                    </tr>
                    <?php endif; ?>
                    <tr>
                        <th class="bg-zinc-50 dark:bg-zinc-900/50 px-5 py-3 text-left text-zinc-500 dark:text-zinc-400 font-medium"><?= __('autoinstall.meta_partner') ?></th>
                        <td class="px-5 py-3 text-zinc-700 dark:text-zinc-300">
                            <?php if (!empty($item['author_name'])): ?>
                            <?= htmlspecialchars($item['author_name']) ?>
                            <?php else: ?>
                            <span class="text-zinc-400"><?= __('autoinstall.meta_partner_default') ?></span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <tr>
                        <th class="bg-zinc-50 dark:bg-zinc-900/50 px-5 py-3 text-left text-zinc-500 dark:text-zinc-400 font-medium"><?= __('autoinstall.meta_price') ?></th>
                        <td class="px-5 py-3 text-zinc-700 dark:text-zinc-300 font-medium"><?= htmlspecialchars($_priceLabel) ?></td>
                    </tr>
                    <?php if (!empty($item['license'])): ?>
                    <tr>
                        <th class="bg-zinc-50 dark:bg-zinc-900/50 px-5 py-3 text-left text-zinc-500 dark:text-zinc-400 font-medium"><?= __('autoinstall.meta_license') ?></th>
                        <td class="px-5 py-3 text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($item['license']) ?></td>
                    </tr>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>

        <!-- 탭 콘텐츠 -->
        <div x-data="{ tab: 'description' }" class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="border-b border-zinc-200 dark:border-zinc-700 flex">
                <button @click="tab='description'" :class="tab==='description' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-zinc-500'" class="px-5 py-3 text-sm font-medium border-b-2 transition-colors"><?= __('autoinstall.description_tab') ?></button>
                <button @click="tab='changelog'" :class="tab==='changelog' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-zinc-500'" class="px-5 py-3 text-sm font-medium border-b-2 transition-colors"><?= __('autoinstall.changelog_tab') ?></button>
                <button @click="tab='reviews'" :class="tab==='reviews' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-zinc-500'" class="px-5 py-3 text-sm font-medium border-b-2 transition-colors"><?= __('autoinstall.reviews_tab') ?> (<?= $item['rating_count'] ?>)</button>
            </div>
            <div class="p-6">
                <div x-show="tab==='description'" class="prose dark:prose-invert max-w-none text-sm">
                    <?= $safeItemDesc ?: '<p class="text-zinc-400">No description.</p>' ?>
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
                    <!-- 리뷰 작성 버튼 -->
                    <div class="flex justify-end pb-3 border-b border-zinc-100 dark:border-zinc-700">
                        <button type="button" onclick="mpOpenReview()"
                                class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg bg-indigo-600 hover:bg-indigo-700 text-white transition-colors">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 20h9M16.5 3.5a2.121 2.121 0 113 3L7 19l-4 1 1-4L16.5 3.5z"/></svg>
                            <?= __('autoinstall.write_review') ?>
                        </button>
                    </div>

                    <?php if (empty($reviewList)): ?>
                    <p class="text-sm text-zinc-400"><?= __('autoinstall.no_reviews') ?></p>
                    <?php else: ?>
                    <?php foreach ($reviewList as $rv): ?>
                    <div class="border-b border-zinc-100 dark:border-zinc-700 pb-4 last:border-0">
                        <div class="flex items-center gap-2 mb-1 flex-wrap">
                            <div class="flex">
                                <?php for ($i = 1; $i <= 5; $i++): ?>
                                <svg class="w-4 h-4 <?= $i <= (int)$rv['rating'] ? 'text-yellow-400' : 'text-zinc-200 dark:text-zinc-600' ?>" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                <?php endfor; ?>
                            </div>
                            <?php if (!empty($rv['reviewer_name'])): ?>
                            <span class="text-xs text-zinc-700 dark:text-zinc-300 font-medium"><?= htmlspecialchars($rv['reviewer_name']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($rv['reviewer_domain'])): ?>
                            <span class="text-xs text-zinc-400 font-mono"><?= htmlspecialchars($rv['reviewer_domain']) ?></span>
                            <?php endif; ?>
                            <?php if (!empty($rv['is_verified'])): ?>
                            <span class="text-xs text-green-600 dark:text-green-400 font-medium"><?= __('autoinstall.verified_purchase') ?></span>
                            <?php endif; ?>
                            <span class="text-xs text-zinc-400 ml-auto"><?= !empty($rv['created_at']) ? date('Y-m-d', strtotime($rv['created_at'])) : '' ?></span>
                        </div>
                        <?php if (!empty($rv['body'])): ?>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1"><?= nl2br(htmlspecialchars($rv['body'])) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <?php if (!empty($screenshots)): ?>
        <!-- 스크린샷 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-4">
                <?= __('autoinstall.screenshots') ?>
                <span class="ml-1 text-sm font-normal text-zinc-500 dark:text-zinc-400">(<?= count($screenshots) ?>)</span>
            </h3>
            <div class="grid grid-cols-2 gap-3">
                <?php foreach ($screenshots as $ss): ?>
                <img src="<?= htmlspecialchars($ss) ?>" alt="" class="rounded-lg border border-zinc-200 dark:border-zinc-700 w-full">
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- 우측: 사이드바 -->
    <div class="space-y-4">
        <!-- 구매/설치 카드 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="text-center mb-4">
                <?php if ($isFree): ?>
                <span class="text-3xl font-bold text-green-600 dark:text-green-400"><?= __('autoinstall.free') ?></span>
                <?php else: ?>
                <span class="text-3xl font-bold text-zinc-900 dark:text-white"><?= number_format($price, $currency === 'KRW' || $currency === 'JPY' ? 0 : 2) ?></span>
                <span class="text-lg text-zinc-500 ml-1"><?= $currency ?></span>
                <?php endif; ?>
            </div>

            <?php if ($isInstalled): ?>
            <div class="px-4 py-3 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 rounded-lg text-center text-sm font-medium">
                <?= __('autoinstall.installed') ?>
            </div>
            <?php elseif ($hasPurchased || $isFree): ?>
            <!-- 무료 또는 구매 완료: 설치 + 다운로드 -->
            <div class="space-y-2">
                <button type="button" data-slug="<?= htmlspecialchars($item['slug']) ?>" onclick="mpInstallItem(this)"
                        class="w-full px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors text-sm">
                    <?= __('autoinstall.install_now') ?>
                </button>
                <button type="button" data-slug="<?= htmlspecialchars($item['slug']) ?>" data-license-key="<?= htmlspecialchars($itemLicenseKey) ?>" onclick="mpDownloadItem(this)"
                        class="w-full px-4 py-2.5 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 text-zinc-700 dark:text-zinc-200 font-medium rounded-lg transition-colors text-sm">
                    <?= __('autoinstall.download') ?>
                </button>
            </div>
            <?php else: ?>
            <!-- 유료 미구매: 마켓플레이스 결제 모달 호출 -->
            <button type="button"
                    data-slug="<?= htmlspecialchars($item['slug']) ?>"
                    data-name="<?= htmlspecialchars($itemName) ?>"
                    data-price="<?= (int)$price ?>"
                    data-currency="<?= htmlspecialchars($currency) ?>"
                    data-price-label="<?= htmlspecialchars(number_format($price, $currency === 'KRW' || $currency === 'JPY' ? 0 : 2) . ' ' . $currency) ?>"
                    onclick="mpOpenPurchase(this)"
                    class="w-full px-4 py-3 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-lg transition-colors text-sm">
                <?= __('autoinstall.purchase') ?>
            </button>
            <?php endif; ?>
        </div>

        <!-- 정보 카드 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 mb-3"><?= __('autoinstall.requirements') ?></h3>
            <dl class="space-y-2 text-sm">
                <div class="flex justify-between">
                    <dt class="text-zinc-500"><?= __('autoinstall.version') ?></dt>
                    <dd class="text-zinc-800 dark:text-zinc-200 font-medium"><?= htmlspecialchars($item['latest_version']) ?></dd>
                </div>
                <?php if ($item['min_voscms_version']): ?>
                <div class="flex justify-between">
                    <dt class="text-zinc-500"><?= __('autoinstall.min_voscms') ?></dt>
                    <dd class="text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($item['min_voscms_version']) ?></dd>
                </div>
                <?php endif; ?>
                <?php if ($item['min_php_version']): ?>
                <div class="flex justify-between">
                    <dt class="text-zinc-500"><?= __('autoinstall.min_php') ?></dt>
                    <dd class="text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($item['min_php_version']) ?></dd>
                </div>
                <?php endif; ?>
                <div class="flex justify-between">
                    <dt class="text-zinc-500"><?= __('autoinstall.downloads') ?></dt>
                    <dd class="text-zinc-800 dark:text-zinc-200"><?= number_format($item['download_count']) ?></dd>
                </div>
                <div class="flex justify-between">
                    <dt class="text-zinc-500"><?= __('autoinstall.last_updated') ?></dt>
                    <dd class="text-zinc-800 dark:text-zinc-200"><?= $item['updated_at'] ? date('Y-m-d', strtotime($item['updated_at'])) : '-' ?></dd>
                </div>
            </dl>
        </div>
    </div>
</div>

<!-- 리뷰 작성 모달 -->
<div id="mpReviewModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="mpCloseReview()"></div>
    <div class="relative bg-white dark:bg-zinc-800 rounded-2xl shadow-xl w-full max-w-md p-6">
        <button onclick="mpCloseReview()" class="absolute top-4 right-4 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-1"><?= __('autoinstall.write_review') ?></h3>
        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-4"><?= __('autoinstall.review_pending_notice') ?></p>
        <div class="space-y-3">
            <!-- 별점 -->
            <div>
                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1.5"><?= __('autoinstall.rating') ?></label>
                <div id="mpRatingStars" class="flex gap-1">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <button type="button" data-value="<?= $i ?>" onclick="mpSetRating(<?= $i ?>)"
                            class="mpRatingStar text-zinc-300 hover:text-yellow-400 transition-colors">
                        <svg class="w-7 h-7" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    </button>
                    <?php endfor; ?>
                </div>
            </div>
            <!-- 닉네임 -->
            <div>
                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1"><?= __('autoinstall.reviewer_name') ?></label>
                <input type="text" id="mpReviewerName" maxlength="100" placeholder="(선택)"
                       class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <!-- 본문 -->
            <div>
                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1"><?= __('autoinstall.review_body') ?></label>
                <textarea id="mpReviewBody" rows="4" maxlength="2000"
                          class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-indigo-500"></textarea>
            </div>
        </div>
        <div id="mpReviewError" class="hidden mt-3 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 text-sm text-red-600 dark:text-red-400"></div>
        <div id="mpReviewSuccess" class="hidden mt-3 p-3 rounded-lg bg-green-50 dark:bg-green-900/20 text-sm text-green-700 dark:text-green-400"></div>
        <button id="mpReviewSubmitBtn" onclick="mpSubmitReview()"
                class="mt-4 w-full py-2.5 px-4 bg-indigo-600 hover:bg-indigo-700 text-white font-medium rounded-xl transition-colors text-sm">
            <?= __('autoinstall.submit_review') ?>
        </button>
    </div>
</div>

<script>
var _mpReviewSlug   = '<?= addslashes($item['slug']) ?>';
var _mpReviewApiUrl = '<?= addslashes($adminUrl . '/autoinstall/api') ?>';
var _mpRating       = 0;

function mpOpenReview() {
    _mpRating = 0;
    mpUpdateStars();
    document.getElementById('mpReviewerName').value = '';
    document.getElementById('mpReviewBody').value   = '';
    document.getElementById('mpReviewError').classList.add('hidden');
    document.getElementById('mpReviewSuccess').classList.add('hidden');
    var sb = document.getElementById('mpReviewSubmitBtn');
    sb.disabled = false;
    sb.textContent = '<?= __('autoinstall.submit_review') ?>';
    document.getElementById('mpReviewModal').classList.remove('hidden');
}
function mpCloseReview() {
    document.getElementById('mpReviewModal').classList.add('hidden');
}
function mpSetRating(v) {
    _mpRating = v;
    mpUpdateStars();
}
function mpUpdateStars() {
    var stars = document.querySelectorAll('.mpRatingStar');
    stars.forEach(function(s, idx) {
        if (idx < _mpRating) s.classList.add('text-yellow-400'), s.classList.remove('text-zinc-300');
        else                 s.classList.add('text-zinc-300'),  s.classList.remove('text-yellow-400');
    });
}
function mpSubmitReview() {
    var errEl = document.getElementById('mpReviewError');
    var okEl  = document.getElementById('mpReviewSuccess');
    errEl.classList.add('hidden'); okEl.classList.add('hidden');

    if (_mpRating < 1 || _mpRating > 5) {
        errEl.textContent = '<?= __('autoinstall.rating_required') ?>';
        errEl.classList.remove('hidden');
        return;
    }
    var sb = document.getElementById('mpReviewSubmitBtn');
    sb.disabled = true;
    sb.textContent = '...';

    var body = new URLSearchParams({
        action: 'submit_review',
        slug:   _mpReviewSlug,
        rating: _mpRating,
        body:   document.getElementById('mpReviewBody').value,
        reviewer_name: document.getElementById('mpReviewerName').value
    });

    fetch(_mpReviewApiUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: body.toString()
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success) {
            okEl.textContent = '<?= __('autoinstall.review_submitted') ?>';
            okEl.classList.remove('hidden');
            sb.textContent = '<?= __('autoinstall.done') ?>';
            setTimeout(function() { mpCloseReview(); window.location.reload(); }, 1500);
        } else {
            errEl.textContent = d.message || '<?= __('autoinstall.review_failed') ?>';
            errEl.classList.remove('hidden');
            sb.disabled = false;
            sb.textContent = '<?= __('autoinstall.submit_review') ?>';
        }
    })
    .catch(function() {
        errEl.textContent = '<?= __('autoinstall.network_error') ?>';
        errEl.classList.remove('hidden');
        sb.disabled = false;
        sb.textContent = '<?= __('autoinstall.submit_review') ?>';
    });
}
</script>

<?php include __DIR__ . '/_components/mp-actions.php'; ?>

<?php include __DIR__ . '/_foot.php'; ?>
