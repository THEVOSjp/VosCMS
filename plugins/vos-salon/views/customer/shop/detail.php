<?php
/**
 * 업소 상세 페이지
 * /shop/{slug}
 * 등록자 또는 관리자/슈퍼바이저만 수정 가능
 */

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$currentLocale = $config['locale'] ?? (function_exists('current_locale') ? current_locale() : 'ko');

// AJAX: 좋아요(찜) 토글
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'] ?? '';
    require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
    if (!\RzxLib\Core\Auth\Auth::check()) { echo json_encode(['error' => 'login_required']); exit; }
    $userId = \RzxLib\Core\Auth\Auth::user()['id'];
    $shopId = (int)($_POST['shop_id'] ?? 0);

    if ($action === 'toggle_favorite' && $shopId) {
        $chk = $pdo->prepare("SELECT id FROM {$prefix}shop_favorites WHERE shop_id = ? AND user_id = ?");
        $chk->execute([$shopId, $userId]);
        if ($chk->fetch()) {
            $pdo->prepare("DELETE FROM {$prefix}shop_favorites WHERE shop_id = ? AND user_id = ?")->execute([$shopId, $userId]);
            $pdo->prepare("UPDATE {$prefix}shops SET favorite_count = GREATEST(favorite_count - 1, 0) WHERE id = ?")->execute([$shopId]);
            $favorited = false;
        } else {
            $pdo->prepare("INSERT IGNORE INTO {$prefix}shop_favorites (shop_id, user_id) VALUES (?, ?)")->execute([$shopId, $userId]);
            $pdo->prepare("UPDATE {$prefix}shops SET favorite_count = favorite_count + 1 WHERE id = ?")->execute([$shopId]);
            $favorited = true;
        }
        $cnt = (int)$pdo->query("SELECT favorite_count FROM {$prefix}shops WHERE id = {$shopId}")->fetchColumn();
        echo json_encode(['success' => true, 'favorited' => $favorited, 'count' => $cnt]);
        exit;
    }
    echo json_encode(['error' => 'unknown']);
    exit;
}

// 업소 로드
$shop = null;
if (!empty($shopSlug)) {
    $stmt = $pdo->prepare("SELECT s.*, c.slug as category_slug, c.name as category_name FROM {$prefix}shops s LEFT JOIN {$prefix}shop_categories c ON s.category_id = c.id WHERE s.slug = ? LIMIT 1");
    $stmt->execute([$shopSlug]);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$shop) {
    http_response_code(404);
    include BASE_PATH . '/resources/views/customer/404.php';
    return;
}

// 비공개 상태: 등록자 또는 관리자만 접근 가능
// Auth::check()로 프론트엔드 로그인 상태 확인
$_currentUser = null;
try {
    require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
    if (\RzxLib\Core\Auth\Auth::check()) {
        $_currentUser = \RzxLib\Core\Auth\Auth::user();
    }
} catch (\Throwable $e) {}
$isOwner = $_currentUser && $_currentUser['id'] === $shop['user_id'];
$isAdmin = $_currentUser && in_array($_currentUser['role'] ?? '', ['admin', 'supervisor', 'super_admin']);
$isFavorited = false;
if ($_currentUser) {
    try {
        $favChk = $pdo->prepare("SELECT id FROM {$prefix}shop_favorites WHERE shop_id = ? AND user_id = ?");
        $favChk->execute([$shop['id'], $_currentUser['id']]);
        $isFavorited = (bool)$favChk->fetch();
    } catch (\Throwable $e) {}
}
if ($shop['status'] !== 'active' && !$isOwner && !$isAdmin) {
    http_response_code(404);
    include BASE_PATH . '/resources/views/customer/404.php';
    return;
}

$pageTitle = htmlspecialchars($shop['name']);
$seoContext = ['type' => 'sub', 'subpage_title' => $shop['name']];

// 다국어 로드
$shopTr = null;
try {
    $trStmt = $pdo->prepare("SELECT * FROM {$prefix}shop_translations WHERE shop_id = ? AND locale = ? LIMIT 1");
    $trStmt->execute([$shop['id'], $currentLocale]);
    $shopTr = $trStmt->fetch(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {}

$displayName = ($shopTr['name'] ?? '') ?: $shop['name'];
$displayDesc = ($shopTr['description'] ?? '') ?: ($shop['description'] ?? '');
$displayAddr = ($shopTr['address'] ?? '') ?: ($shop['address'] ?? '');

// 카테고리 이름
$catNames = json_decode($shop['category_name'] ?? '{}', true);
$catLabel = $catNames[$currentLocale] ?? $catNames['en'] ?? $catNames['ko'] ?? $shop['category_slug'] ?? '';

// JSON 파싱
$images = json_decode($shop['images'] ?? '[]', true) ?: [];
$businessHours = json_decode($shop['business_hours'] ?? '{}', true) ?: [];
$sns = json_decode($shop['sns'] ?? '{}', true) ?: [];
$features = json_decode($shop['features'] ?? '[]', true) ?: [];

// 특징 태그 라벨 로드
$_shopLang = @include(BASE_PATH . '/plugins/vos-shop/lang/' . $currentLocale . '/shop.php');
if (!is_array($_shopLang)) $_shopLang = @include(BASE_PATH . '/plugins/vos-shop/lang/ko/shop.php');
$_featLabels = $_shopLang['features'] ?? [];
if (is_array($_shopLang) && class_exists('\RzxLib\Core\I18n\Translator')) { \RzxLib\Core\I18n\Translator::merge('shop', $_shopLang); }

// 활성 이벤트 로드
$shopEvents = [];
try {
    $evStmt = $pdo->prepare("SELECT * FROM {$prefix}shop_events WHERE shop_id = ? AND is_active = 1 AND payment_status = 'paid' AND start_date <= NOW() AND (end_date IS NULL OR end_date > NOW()) ORDER BY created_at DESC");
    $evStmt->execute([$shop['id']]);
    $shopEvents = $evStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {}

// 리뷰 로드
$reviews = [];
try {
    $rvStmt = $pdo->prepare("SELECT r.*, u.name as user_name, u.avatar FROM {$prefix}shop_reviews r LEFT JOIN {$prefix}users u ON r.user_id = u.id WHERE r.shop_id = ? AND r.status = 'active' ORDER BY r.created_at DESC LIMIT 10");
    $rvStmt->execute([$shop['id']]);
    $reviews = $rvStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {}

// Q&A 로드
$inquiries = [];
try {
    $iqStmt = $pdo->prepare("SELECT i.*, u.name as user_name FROM {$prefix}shop_inquiries i LEFT JOIN {$prefix}users u ON i.user_id = u.id WHERE i.shop_id = ? AND i.is_public = 1 ORDER BY i.created_at DESC LIMIT 20");
    $iqStmt->execute([$shop['id']]);
    $inquiries = $iqStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {}

// Q&A 작성 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['qa_question'])) {
    require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
    if (\RzxLib\Core\Auth\Auth::check()) {
        $qaUser = \RzxLib\Core\Auth\Auth::user();
        $qaQuestion = trim($_POST['qa_question']);
        if ($qaQuestion) {
            $iqIns = $pdo->prepare("INSERT INTO {$prefix}shop_inquiries (shop_id, user_id, question, status) VALUES (?, ?, ?, 'pending')");
            $iqIns->execute([$shop['id'], $qaUser['id'], $qaQuestion]);
            header('Location: ' . ($config['app_url'] ?? '') . '/shop/' . $shop['slug'] . '#qa-section');
            exit;
        }
    }
}

// Q&A 답변 처리 (사업장 운영자 또는 관리자)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['qa_answer']) && !empty($_POST['qa_id'])) {
    if ($isOwner || $isAdmin) {
        $qaAnswer = trim($_POST['qa_answer']);
        $qaId = (int)$_POST['qa_id'];
        if ($qaAnswer && $qaId) {
            $ansStmt = $pdo->prepare("UPDATE {$prefix}shop_inquiries SET answer = ?, answered_by = ?, answered_at = NOW(), status = 'answered' WHERE id = ? AND shop_id = ?");
            $ansStmt->execute([$qaAnswer, $_SESSION['user_id'] ?? $_SESSION['admin_id'] ?? '', $qaId, $shop['id']]);
            header('Location: ' . ($config['app_url'] ?? '') . '/shop/' . $shop['slug'] . '#qa-section');
            exit;
        }
    }
}

// 쿠폰 로드
$coupons = [];
try {
    $cpStmt = $pdo->prepare("SELECT * FROM {$prefix}coupons WHERE shop_id = ? AND is_active = 1 AND (end_date IS NULL OR end_date > NOW()) ORDER BY created_at DESC LIMIT 5");
    $cpStmt->execute([$shop['id']]);
    $coupons = $cpStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {}

// 요일 라벨
$dayLabels = ['mon' => __('common.days.mon') ?? '월', 'tue' => __('common.days.tue') ?? '화', 'wed' => __('common.days.wed') ?? '수', 'thu' => __('common.days.thu') ?? '목', 'fri' => __('common.days.fri') ?? '금', 'sat' => __('common.days.sat') ?? '토', 'sun' => __('common.days.sun') ?? '일'];

// 조회수 증가
try { $pdo->prepare("UPDATE {$prefix}shops SET view_count = view_count + 1 WHERE id = ?")->execute([$shop['id']]); } catch (\Throwable $e) {}
?>

<!-- 상태 배너 (비공개) -->
<?php if ($shop['status'] !== 'active'): ?>
<div class="bg-amber-50 dark:bg-amber-900/20 border-b border-amber-200 dark:border-amber-800 px-4 py-2 text-center">
    <span class="text-sm text-amber-700 dark:text-amber-300">
        <?php if ($shop['status'] === 'pending'): ?>
            ⏳ <?= __('shop.detail.status_pending') ?? '관리자 승인 대기 중입니다.' ?>
        <?php elseif ($shop['status'] === 'rejected'): ?>
            ❌ <?= __('shop.detail.status_rejected') ?? '등록이 거절되었습니다.' ?>
        <?php elseif ($shop['status'] === 'suspended'): ?>
            ⚠️ <?= __('shop.detail.status_suspended') ?? '일시 정지된 매장입니다.' ?>
        <?php endif; ?>
    </span>
</div>
<?php endif; ?>

<!-- 커버 이미지 -->
<?php if ($shop['cover_image']): ?>
<div class="w-full h-64 md:h-80 overflow-hidden">
    <img src="<?= $baseUrl . htmlspecialchars($shop['cover_image']) ?>" alt="<?= htmlspecialchars($displayName) ?>" class="w-full h-full object-cover">
</div>
<?php endif; ?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">

    <!-- 헤더: 매장명 + 카테고리 + 평점 + 수정 버튼 -->
    <div class="flex flex-col md:flex-row md:items-start md:justify-between gap-4 mb-8">
        <div>
            <div class="flex items-center gap-2 mb-1">
                <span class="px-2 py-0.5 text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded"><?= htmlspecialchars($catLabel) ?></span>
                <?php if ($shop['is_verified']): ?>
                <span class="px-2 py-0.5 text-xs font-medium bg-green-100 dark:bg-green-900/30 text-green-700 dark:text-green-300 rounded">✓ <?= __('shop.detail.verified') ?? '인증됨' ?></span>
                <?php endif; ?>
                <?php if ($shop['rezlyx_url']): ?>
                <span class="px-2 py-0.5 text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded">RezlyX Partner</span>
                <?php endif; ?>
            </div>
            <h1 class="text-2xl md:text-3xl font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($displayName) ?></h1>
            <div class="flex items-center gap-3 mt-2 text-sm text-zinc-500 dark:text-zinc-400">
                <?php if ($shop['rating_avg'] > 0): ?>
                <span class="flex items-center gap-1 text-amber-500">
                    <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    <?= number_format($shop['rating_avg'], 1) ?>
                </span>
                <span><?= __('shop.detail.reviews') ?? '리뷰' ?> <?= $shop['review_count'] ?></span>
                <?php endif; ?>
                <button onclick="toggleFavorite(<?= $shop['id'] ?>)" id="favBtn" class="flex items-center gap-1 <?= $isFavorited ? 'text-red-500' : '' ?> hover:text-red-500 transition">
                    <svg class="w-4 h-4" fill="<?= $isFavorited ? 'currentColor' : 'none' ?>" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/></svg>
                    <span id="favCount"><?= $shop['favorite_count'] ?></span>
                </button>
                <span>👁 <?= $shop['view_count'] ?></span>
            </div>
        </div>
        <div class="flex items-center gap-2">
            <?php if ($shop['rezlyx_url']): ?>
            <a href="<?= htmlspecialchars($shop['rezlyx_url']) ?>" class="px-5 py-2.5 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition"><?= __('shop.detail.book_now') ?? '예약하기' ?></a>
            <?php endif; ?>
            <?php if ($isOwner || $isAdmin): ?>
            <a href="<?= $baseUrl ?>/shop/<?= htmlspecialchars($shop['slug']) ?>/edit" class="px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 text-sm font-medium rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition text-zinc-600 dark:text-zinc-300"><?= __('common.edit') ?? '수정' ?></a>
            <?php endif; ?>
        </div>
    </div>

    <!-- 메인 + 사이드바 -->
    <div class="grid grid-cols-1 lg:grid-cols-[1fr_320px] gap-8">

        <!-- 메인 -->
        <div class="space-y-8">

            <!-- 소개글 -->
            <?php if ($displayDesc): ?>
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-3"><?= __('shop.detail.about') ?? '소개' ?></h2>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed whitespace-pre-line"><?= htmlspecialchars($displayDesc) ?></p>
            </div>
            <?php endif; ?>

            <!-- 사진 갤러리 -->
            <?php if (!empty($images)): ?>
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-3"><?= __('shop.detail.photos') ?? '사진' ?></h2>
                <div class="grid grid-cols-2 md:grid-cols-3 gap-2">
                    <?php foreach ($images as $img):
                        $imgUrl = str_starts_with($img, 'http') ? $img : $baseUrl . $img;
                    ?>
                    <div class="aspect-[4/3] rounded-lg overflow-hidden">
                        <img src="<?= htmlspecialchars($imgUrl) ?>" alt="" class="w-full h-full object-cover hover:scale-105 transition-transform duration-300">
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- 특징 태그 -->
            <?php if (!empty($features)): ?>
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-3"><?= __('shop.detail.features') ?? '매장 특징' ?></h2>
                <div class="flex flex-wrap gap-2">
                    <?php foreach ($features as $f):
                        $fLabel = $_featLabels[$f] ?? $f;
                    ?>
                    <span class="px-3 py-1 bg-zinc-100 dark:bg-zinc-700 text-sm text-zinc-600 dark:text-zinc-300 rounded-full"><?= htmlspecialchars($fLabel) ?></span>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- 지도 -->
            <?php if ($shop['latitude'] && $shop['longitude']): ?>
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-3"><?= __('shop.detail.location') ?? '위치' ?></h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-3"><?= htmlspecialchars($displayAddr) ?><?= $shop['address_detail'] ? ' ' . htmlspecialchars($shop['address_detail']) : '' ?></p>
                <div id="shopDetailMap" class="w-full h-64 rounded-lg"></div>
                <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
                <script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
                <script>
                (function(){
                    var map = L.map('shopDetailMap').setView([<?= $shop['latitude'] ?>, <?= $shop['longitude'] ?>], 16);
                    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution:'© OpenStreetMap',maxZoom:19}).addTo(map);
                    L.marker([<?= $shop['latitude'] ?>, <?= $shop['longitude'] ?>]).addTo(map).bindPopup('<?= htmlspecialchars($displayName) ?>');
                })();
                </script>
            </div>
            <?php endif; ?>

            <!-- 쿠폰 -->
            <?php if (!empty($coupons)): ?>
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-3">🎫 <?= __('shop.detail.coupons') ?? '쿠폰' ?></h2>
                <div class="space-y-3">
                    <?php foreach ($coupons as $cp): ?>
                    <div class="flex items-center justify-between p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 border-dashed rounded-lg">
                        <div>
                            <p class="font-semibold text-sm text-red-700 dark:text-red-300"><?= htmlspecialchars($cp['title']) ?></p>
                            <p class="text-xs text-red-500 dark:text-red-400 mt-0.5">
                                <?= $cp['discount_type'] === 'percent' ? $cp['discount_value'] . '%' : number_format($cp['discount_value']) ?> <?= __('shop.detail.discount') ?? '할인' ?>
                                <?php if ($cp['end_date']): ?> · ~<?= date('Y.m.d', strtotime($cp['end_date'])) ?><?php endif; ?>
                            </p>
                        </div>
                        <button class="px-3 py-1.5 bg-red-500 text-white text-xs font-medium rounded-full hover:bg-red-600 transition"><?= __('shop.detail.get_coupon') ?? '쿠폰 받기' ?></button>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- 리뷰 -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('shop.detail.reviews') ?? '리뷰' ?> (<?= $shop['review_count'] ?>)</h2>
                </div>
                <?php if (empty($reviews)): ?>
                <p class="text-sm text-zinc-400 py-6 text-center"><?= __('shop.detail.no_reviews') ?? '아직 리뷰가 없습니다.' ?></p>
                <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($reviews as $rv): ?>
                    <div class="border-b border-dashed border-zinc-200 dark:border-zinc-700 pb-4 last:border-0 last:pb-0">
                        <div class="flex items-center gap-2 mb-2">
                            <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 text-xs font-bold"><?= mb_substr($rv['user_name'] ?? 'U', 0, 1) ?></div>
                            <div>
                                <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($rv['user_name'] ?? 'User') ?></p>
                                <div class="flex items-center gap-1">
                                    <?php for ($i = 1; $i <= 5; $i++): ?>
                                    <svg class="w-3 h-3 <?= $i <= $rv['rating'] ? 'text-amber-400' : 'text-zinc-200 dark:text-zinc-600' ?>" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                                    <?php endfor; ?>
                                    <span class="text-[10px] text-zinc-400 ml-1"><?= date('Y.m.d', strtotime($rv['created_at'])) ?></span>
                                </div>
                            </div>
                        </div>
                        <?php if ($rv['content']): ?>
                        <p class="text-sm text-zinc-600 dark:text-zinc-400"><?= htmlspecialchars($rv['content']) ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>

            <!-- Q&A -->
            <div id="qa-section" class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
                <div class="flex items-center justify-between mb-4">
                    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('shop.detail.qa') ?? 'Q&A' ?> (<?= count($inquiries) ?>)</h2>
                </div>

                <?php if (empty($inquiries)): ?>
                <p class="text-sm text-zinc-400 py-4 text-center"><?= __('shop.detail.no_qa') ?? '아직 문의가 없습니다.' ?></p>
                <?php else: ?>
                <div class="space-y-4">
                    <?php foreach ($inquiries as $iq): ?>
                    <div class="border-b border-dashed border-zinc-200 dark:border-zinc-700 pb-4 last:border-0 last:pb-0">
                        <div class="flex items-start gap-2 mb-2">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-600 text-xs font-bold flex-shrink-0 mt-0.5">Q</span>
                            <div class="flex-1">
                                <p class="text-sm text-zinc-800 dark:text-zinc-200"><?= nl2br(htmlspecialchars($iq['question'])) ?></p>
                                <p class="text-[10px] text-zinc-400 mt-1"><?= htmlspecialchars($iq['user_name'] ?? '') ?> · <?= date('Y.m.d', strtotime($iq['created_at'])) ?></p>
                            </div>
                        </div>
                        <?php if ($iq['answer']): ?>
                        <div class="flex items-start gap-2 ml-8 mt-2 p-3 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg">
                            <span class="inline-flex items-center justify-center w-6 h-6 rounded-full bg-green-100 dark:bg-green-900/30 text-green-600 text-xs font-bold flex-shrink-0">A</span>
                            <div class="flex-1">
                                <p class="text-sm text-zinc-700 dark:text-zinc-300"><?= nl2br(htmlspecialchars($iq['answer'])) ?></p>
                                <p class="text-[10px] text-zinc-400 mt-1"><?= date('Y.m.d', strtotime($iq['answered_at'])) ?></p>
                            </div>
                        </div>
                        <?php elseif ($isOwner || $isAdmin): ?>
                        <!-- 답변 입력 (운영자/관리자) -->
                        <form method="POST" class="ml-8 mt-2">
                            <input type="hidden" name="qa_id" value="<?= $iq['id'] ?>">
                            <div class="flex gap-2">
                                <input type="text" name="qa_answer" required placeholder="<?= __('shop.detail.qa_answer_placeholder') ?? '답변을 입력하세요' ?>"
                                       class="flex-1 px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                <button type="submit" class="px-4 py-2 text-sm bg-green-600 text-white rounded-lg hover:bg-green-700 transition"><?= __('shop.detail.qa_answer_btn') ?? '답변' ?></button>
                            </div>
                        </form>
                        <?php else: ?>
                        <p class="text-xs text-zinc-400 ml-8 mt-1"><?= __('shop.detail.qa_pending') ?? '답변 대기 중' ?></p>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- 질문 작성 -->
                <?php
                $isLoggedIn = false;
                try { $isLoggedIn = \RzxLib\Core\Auth\Auth::check(); } catch (\Throwable $e) {}
                ?>
                <?php if ($isLoggedIn): ?>
                <form method="POST" class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
                    <div class="flex gap-2">
                        <input type="text" name="qa_question" required placeholder="<?= __('shop.detail.qa_question_placeholder') ?? '궁금한 점을 질문해보세요' ?>"
                               class="flex-1 px-4 py-2.5 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        <button type="submit" class="px-4 py-2.5 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition whitespace-nowrap"><?= __('shop.detail.qa_submit') ?? '질문하기' ?></button>
                    </div>
                </form>
                <?php else: ?>
                <p class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700 text-sm text-zinc-400 text-center">
                    <a href="<?= $baseUrl ?>/login?redirect=shop/<?= urlencode($shop['slug']) ?>" class="text-blue-600 hover:underline"><?= __('shop.detail.login_to_ask') ?? '로그인 후 질문할 수 있습니다.' ?></a>
                </p>
                <?php endif; ?>
            </div>
        </div>

        <!-- 사이드바 -->
        <div class="space-y-4">

            <!-- 연락처 -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-3"><?= __('shop.detail.contact') ?? '연락처' ?></h3>
                <div class="space-y-2 text-sm">
                    <?php if ($shop['phone']): ?>
                    <a href="tel:<?= htmlspecialchars($shop['phone']) ?>" class="flex items-center gap-2 text-zinc-600 dark:text-zinc-400 hover:text-blue-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                        <?= htmlspecialchars($shop['phone']) ?>
                    </a>
                    <?php endif; ?>
                    <?php if ($shop['email']): ?>
                    <a href="mailto:<?= htmlspecialchars($shop['email']) ?>" class="flex items-center gap-2 text-zinc-600 dark:text-zinc-400 hover:text-blue-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <?= htmlspecialchars($shop['email']) ?>
                    </a>
                    <?php endif; ?>
                    <?php if ($shop['website']): ?>
                    <a href="<?= htmlspecialchars($shop['website']) ?>" target="_blank" class="flex items-center gap-2 text-zinc-600 dark:text-zinc-400 hover:text-blue-600">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9"/></svg>
                        <?= __('shop.detail.website') ?? '웹사이트' ?>
                    </a>
                    <?php endif; ?>
                    <?php if ($displayAddr): ?>
                    <div class="flex items-start gap-2 text-zinc-600 dark:text-zinc-400">
                        <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <span><?= htmlspecialchars($displayAddr) ?><?= $shop['address_detail'] ? '<br>' . htmlspecialchars($shop['address_detail']) : '' ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 영업시간 -->
            <?php if (!empty($businessHours)): ?>
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-3"><?= __('shop.detail.hours') ?? '영업시간' ?></h3>
                <div class="space-y-1.5 text-sm">
                    <?php foreach ($dayLabels as $dayKey => $dayLabel):
                        $h = $businessHours[$dayKey] ?? [];
                        $isClosed = !empty($h['closed']);
                    ?>
                    <div class="flex justify-between">
                        <span class="text-zinc-500 dark:text-zinc-400"><?= $dayLabel ?></span>
                        <?php if ($isClosed): ?>
                        <span class="text-red-400"><?= __('shop.register.closed') ?? '휴무' ?></span>
                        <?php else: ?>
                        <span class="text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($h['open'] ?? '') ?> ~ <?= htmlspecialchars($h['close'] ?? '') ?></span>
                        <?php endif; ?>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>

            <!-- 이벤트 등록 CTA (등록자에게만) -->
            <?php if ($isOwner || $isAdmin): ?>
            <a href="<?= $baseUrl ?>/shop/<?= htmlspecialchars($shop['slug']) ?>/events" class="block bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border border-amber-200 dark:border-amber-700 rounded-xl p-4 hover:shadow-md transition group">
                <div class="flex items-center justify-between">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 bg-amber-100 dark:bg-amber-900/40 rounded-lg flex items-center justify-center flex-shrink-0">
                            <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                        </div>
                        <div>
                            <p class="text-sm font-bold text-amber-900 dark:text-amber-200"><?= __('shop.event.banner_cta') ?? '이벤트를 등록하고 고객을 끌어보세요!' ?></p>
                            <p class="text-xs text-amber-600 dark:text-amber-400">&yen;10,000 / <?= __('shop.event.banner_period') ?? '7~30일 노출' ?></p>
                        </div>
                    </div>
                    <svg class="w-5 h-5 text-amber-400 group-hover:translate-x-1 transition-transform flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </div>
            </a>
            <?php endif; ?>

            <!-- 등록된 이벤트 배너 -->
            <?php if (!empty($shopEvents)): ?>
            <?php foreach ($shopEvents as $ev):
                $evDaysLeft = $ev['end_date'] ? max(0, (int)((strtotime($ev['end_date']) - time()) / 86400)) : null;
            ?>
            <div class="bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border border-amber-200 dark:border-amber-700 rounded-xl overflow-hidden">
                <?php if ($ev['image']): ?>
                <div class="aspect-[3/1] overflow-hidden">
                    <img src="<?= $baseUrl . '/' . ltrim(htmlspecialchars($ev['image']), '/') ?>" alt="<?= htmlspecialchars($ev['title']) ?>" class="w-full h-full object-cover">
                </div>
                <?php endif; ?>
                <div class="p-4">
                    <div class="flex items-start justify-between gap-2">
                        <div>
                            <p class="text-sm font-bold text-amber-900 dark:text-amber-200"><?= htmlspecialchars($ev['title']) ?></p>
                            <?php if ($ev['discount_info']): ?>
                            <p class="text-xs font-semibold text-orange-600 dark:text-orange-400 mt-0.5"><?= htmlspecialchars($ev['discount_info']) ?></p>
                            <?php endif; ?>
                            <?php if ($ev['description']): ?>
                            <p class="text-xs text-amber-700 dark:text-amber-400 mt-1 line-clamp-2"><?= htmlspecialchars($ev['description']) ?></p>
                            <?php endif; ?>
                        </div>
                        <?php if ($evDaysLeft !== null): ?>
                        <span class="flex-shrink-0 px-2 py-0.5 text-[10px] font-bold rounded-full <?= $evDaysLeft <= 3 ? 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400' : 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400' ?>">
                            D-<?= $evDaysLeft ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <?php if ($ev['end_date']): ?>
                    <p class="text-[10px] text-amber-500 mt-2"><?= date('Y.m.d', strtotime($ev['start_date'])) ?> ~ <?= date('Y.m.d', strtotime($ev['end_date'])) ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
            <?php endif; ?>

            <!-- SNS -->
            <?php if (!empty($sns)): ?>
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-3">SNS</h3>
                <div class="space-y-2 text-sm">
                    <?php if (!empty($sns['instagram'])): ?>
                    <a href="https://instagram.com/<?= ltrim(htmlspecialchars($sns['instagram']), '@') ?>" target="_blank" class="flex items-center gap-2 text-zinc-600 dark:text-zinc-400 hover:text-pink-600">📷 <?= htmlspecialchars($sns['instagram']) ?></a>
                    <?php endif; ?>
                    <?php if (!empty($sns['x'])): ?>
                    <a href="https://x.com/<?= ltrim(htmlspecialchars($sns['x']), '@') ?>" target="_blank" class="flex items-center gap-2 text-zinc-600 dark:text-zinc-400 hover:text-zinc-900">𝕏 <?= htmlspecialchars($sns['x']) ?></a>
                    <?php endif; ?>
                    <?php if (!empty($sns['facebook'])): ?>
                    <a href="<?= htmlspecialchars($sns['facebook']) ?>" target="_blank" class="flex items-center gap-2 text-zinc-600 dark:text-zinc-400 hover:text-blue-600">📘 Facebook</a>
                    <?php endif; ?>
                    <?php if (!empty($sns['line'])): ?>
                    <span class="flex items-center gap-2 text-zinc-600 dark:text-zinc-400">💬 <?= htmlspecialchars($sns['line']) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

        </div>
    </div>
</div>
<script>
function toggleFavorite(shopId) {
    <?php if (!$_currentUser): ?>
    location.href = '<?= $baseUrl ?>/login?redirect=shop/<?= urlencode($shop['slug']) ?>';
    return;
    <?php endif; ?>
    var fd = new FormData();
    fd.append('action', 'toggle_favorite');
    fd.append('shop_id', shopId);
    fetch(location.href, { method:'POST', headers:{'X-Requested-With':'XMLHttpRequest'}, body:fd })
    .then(function(r){return r.json()}).then(function(d) {
        if (d.success) {
            document.getElementById('favCount').textContent = d.count;
            var btn = document.getElementById('favBtn');
            var svg = btn.querySelector('svg');
            if (d.favorited) { btn.classList.add('text-red-500'); svg.setAttribute('fill','currentColor'); }
            else { btn.classList.remove('text-red-500'); svg.setAttribute('fill','none'); }
        } else if (d.error === 'login_required') {
            location.href = '<?= $baseUrl ?>/login?redirect=shop/<?= urlencode($shop['slug']) ?>';
        }
    });
}
</script>
