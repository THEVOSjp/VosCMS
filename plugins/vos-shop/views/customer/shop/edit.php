<?php
/**
 * 업소 수정 페이지
 * /shop/{slug}/edit
 * 등록자 본인 또는 관리자/슈퍼바이저만 접근 가능
 */

require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
if (!\RzxLib\Core\Auth\Auth::check()) {
    header('Location: ' . ($config['app_url'] ?? '') . '/login?redirect=shop/' . urlencode($shopSlug) . '/edit');
    exit;
}

$currentUser = \RzxLib\Core\Auth\Auth::user();
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$currentLocale = $config['locale'] ?? (function_exists('current_locale') ? current_locale() : 'ko');

// 플러그인 번역 로드 → 코어 번역 시스템에 병합 (최상단)
$_shopLangEarly = @include(BASE_PATH . '/plugins/vos-shop/lang/' . $currentLocale . '/shop.php');
if (!is_array($_shopLangEarly)) $_shopLangEarly = @include(BASE_PATH . '/plugins/vos-shop/lang/ko/shop.php');
if (is_array($_shopLangEarly) && class_exists('\RzxLib\Core\I18n\Translator')) {
    \RzxLib\Core\I18n\Translator::merge('shop', $_shopLangEarly);
}

// 업소 로드
$shop = null;
if (!empty($shopSlug)) {
    $stmt = $pdo->prepare("SELECT * FROM {$prefix}shops WHERE slug = ? LIMIT 1");
    $stmt->execute([$shopSlug]);
    $shop = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$shop) {
    http_response_code(404);
    include BASE_PATH . '/resources/views/customer/404.php';
    return;
}

// 권한 확인: 등록자 본인 또는 관리자/슈퍼바이저
$isOwner = $currentUser['id'] === $shop['user_id'];
$isAdmin = !empty($_SESSION['admin_id']);
if (!$isOwner && !$isAdmin) {
    http_response_code(403);
    echo '<div class="max-w-md mx-auto py-16 text-center"><h1 class="text-xl font-bold text-zinc-900 dark:text-white mb-2">' . (__('common.forbidden') ?? '접근 권한이 없습니다.') . '</h1><a href="' . $baseUrl . '/shop/' . htmlspecialchars($shop['slug']) . '" class="text-blue-600 hover:underline">' . (__('common.nav.back') ?? '돌아가기') . '</a></div>';
    return;
}

$pageTitle = (__('shop.edit.title') ?? '매장 수정') . ' - ' . $shop['name'];

// 카테고리 로드
$categories = $pdo->query("SELECT id, slug, name FROM {$prefix}shop_categories WHERE is_active = 1 ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC);

// 기존 JSON 데이터 파싱
$existingImages = json_decode($shop['images'] ?? '[]', true) ?: [];
$existingHours = json_decode($shop['business_hours'] ?? '{}', true) ?: [];
$existingSns = json_decode($shop['sns'] ?? '{}', true) ?: [];
$existingFeatures = json_decode($shop['features'] ?? '[]', true) ?: [];

// POST 처리
$errors = [];
$success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $categoryId = (int)($_POST['category_id'] ?? 0);
    $phone = trim($_POST['phone'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $website = trim($_POST['website'] ?? '');
    $country = trim($_POST['country'] ?? '');
    $postalCode = trim($_POST['postal_code'] ?? '');
    $address = trim($_POST['address'] ?? '');
    $addressDetail = trim($_POST['address_detail'] ?? '');
    $latitude = floatval($_POST['latitude'] ?? 0);
    $longitude = floatval($_POST['longitude'] ?? 0);
    $description = trim($_POST['description'] ?? '');

    $sns = json_encode(array_filter([
        'instagram' => trim($_POST['sns_instagram'] ?? ''),
        'x' => trim($_POST['sns_x'] ?? ''),
        'facebook' => trim($_POST['sns_facebook'] ?? ''),
        'line' => trim($_POST['sns_line'] ?? ''),
    ]));

    $businessHours = [];
    foreach (['mon','tue','wed','thu','fri','sat','sun'] as $day) {
        $open = trim($_POST["hours_{$day}_open"] ?? '');
        $close = trim($_POST["hours_{$day}_close"] ?? '');
        $closed = isset($_POST["hours_{$day}_closed"]);
        $businessHours[$day] = $closed ? ['closed' => true] : ['open' => $open, 'close' => $close];
    }

    $features = $_POST['features'] ?? [];
    $representative = trim($_POST['representative'] ?? '');
    $contactPerson = trim($_POST['contact_person'] ?? '');
    $businessNumber = trim($_POST['business_number'] ?? '');
    $seatCount = ($_POST['seat_count'] ?? '') !== '' ? (int)$_POST['seat_count'] : null;
    $openingStatus = in_array($_POST['opening_status'] ?? '', ['planned', 'opened']) ? $_POST['opening_status'] : 'opened';
    $openedAtY = (int)($_POST['opened_at_year'] ?? 0);
    $openedAtM = (int)($_POST['opened_at_month'] ?? 0);
    $openedAt = ($openedAtY && $openedAtM) ? sprintf('%04d-%02d-01', $openedAtY, $openedAtM) : null;

    if (!$name) $errors[] = __('shop.register.error_name') ?? '매장 이름을 입력해주세요.';
    if (!$categoryId) $errors[] = __('shop.register.error_category') ?? '업종을 선택해주세요.';

    if (empty($errors)) {
        // 커버 이미지
        $coverSql = '';
        $coverParam = [];
        if (!empty($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = BASE_PATH . '/storage/uploads/shops/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION) ?: 'jpg';
            $filename = 'shop_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $uploadDir . $filename)) {
                $coverSql = ', cover_image = ?';
                $coverParam = ['/storage/uploads/shops/' . $filename];
            }
        }

        // 기존 사진 (삭제된 것 제외)
        $keepImages = $_POST['existing_images'] ?? [];
        $newImages = is_array($keepImages) ? $keepImages : [];
        if (!empty($_FILES['images'])) {
            $uploadDir = BASE_PATH . '/storage/uploads/shops/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
                if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
                if (count($newImages) >= 10) break;
                $ext = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION) ?: 'jpg';
                $filename = 'shop_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $uploadDir . $filename)) {
                    $newImages[] = '/storage/uploads/shops/' . $filename;
                }
            }
        }

        // 사업자등록증 업로드
        $licenseSql = '';
        $licenseParam = [];
        if (!empty($_FILES['business_license']) && $_FILES['business_license']['error'] === UPLOAD_ERR_OK) {
            $licDir = BASE_PATH . '/storage/private/licenses/';
            if (!is_dir($licDir)) mkdir($licDir, 0755, true);
            $ext = pathinfo($_FILES['business_license']['name'], PATHINFO_EXTENSION) ?: 'pdf';
            $fn = 'license_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            if (move_uploaded_file($_FILES['business_license']['tmp_name'], $licDir . $fn)) {
                $licenseSql = ', business_license=?';
                $licenseParam = ['/storage/private/licenses/' . $fn];
            }
        }

        $params = [$name, $categoryId, $description, $phone, $email, $website, $country, $postalCode, $address, $addressDetail, $latitude ?: null, $longitude ?: null, json_encode($newImages), json_encode($businessHours), $sns, json_encode($features), $representative, $contactPerson, $businessNumber, $seatCount, $openingStatus, $openedAt];
        $params = array_merge($params, $coverParam, $licenseParam, [$shop['id']]);

        $pdo->prepare("UPDATE {$prefix}shops SET name=?, category_id=?, description=?, phone=?, email=?, website=?, country=?, postal_code=?, address=?, address_detail=?, latitude=?, longitude=?, images=?, business_hours=?, sns=?, features=?, representative=?, contact_person=?, business_number=?, seat_count=?, opening_status=?, opened_at=? {$coverSql} {$licenseSql} WHERE id=?")->execute($params);

        $success = true;
        // 데이터 새로고침
        $stmt = $pdo->prepare("SELECT * FROM {$prefix}shops WHERE id = ?");
        $stmt->execute([$shop['id']]);
        $shop = $stmt->fetch(PDO::FETCH_ASSOC);
        $existingImages = json_decode($shop['images'] ?? '[]', true) ?: [];
        $existingHours = json_decode($shop['business_hours'] ?? '{}', true) ?: [];
        $existingSns = json_decode($shop['sns'] ?? '{}', true) ?: [];
        $existingFeatures = json_decode($shop['features'] ?? '[]', true) ?: [];
    }
}

// 플러그인 번역 로드 → 코어 번역 시스템에 병합
$_shopLang = @include(BASE_PATH . '/plugins/vos-shop/lang/' . $currentLocale . '/shop.php');
if (!is_array($_shopLang)) $_shopLang = @include(BASE_PATH . '/plugins/vos-shop/lang/ko/shop.php');
$_featuresRaw = $_shopLang['features'] ?? [];
if (is_array($_shopLang) && class_exists('\RzxLib\Core\I18n\Translator')) {
    \RzxLib\Core\I18n\Translator::merge('shop', $_shopLang);
}
$_allFeatures = [];
$_currentGroup = '_common';
foreach ($_featuresRaw as $key => $label) {
    if (str_starts_with($key, '_')) { $_currentGroup = $key; continue; }
    $_allFeatures[$_currentGroup][$key] = $label;
}

$_featureCategoryMap = [
    'hair'=>['_common','_hair'],'nail'=>['_common','_nail'],'clinic'=>['_common','_clinic'],'fitness'=>['_common','_fitness'],
    'restaurant'=>['_common','_restaurant'],'pet'=>['_common','_pet'],'hotel'=>['_common','_hotel'],'rental'=>['_common','_rental'],
    'education'=>['_common','_other'],'photo'=>['_common','_other'],'spa'=>['_common','_nail'],
];
$_catSlugMap = [];
foreach ($categories as $c) { $_catSlugMap[$c['id']] = $c['slug']; }

$featureOptions = [];
$dayLabels = ['mon' => __('common.days.mon') ?? '월', 'tue' => __('common.days.tue') ?? '화', 'wed' => __('common.days.wed') ?? '수', 'thu' => __('common.days.thu') ?? '목', 'fri' => __('common.days.fri') ?? '금', 'sat' => __('common.days.sat') ?? '토', 'sun' => __('common.days.sun') ?? '일'];

$seoContext = ['type' => 'sub', 'subpage_title' => $pageTitle];
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex items-center justify-between mb-6">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= __('shop.edit.title') ?? '매장 수정' ?></h1>
            <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= htmlspecialchars($shop['name']) ?></p>
        </div>
        <a href="<?= $baseUrl ?>/shop/<?= htmlspecialchars($shop['slug']) ?>" class="text-sm text-blue-600 hover:underline"><?= __('shop.edit.view_page') ?? '매장 페이지 보기' ?> →</a>
    </div>

    <?php if ($success): ?>
    <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-lg text-sm text-green-700 dark:text-green-300">
        ✓ <?= __('shop.edit.success') ?? '수정이 완료되었습니다.' ?>
    </div>
    <?php endif; ?>

    <?php if (!empty($errors)): ?>
    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
        <?php foreach ($errors as $e): ?>
        <p class="text-sm text-red-700 dark:text-red-300"><?= htmlspecialchars($e) ?></p>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-6">

        <!-- 기본 정보 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('shop.register.basic_info') ?? '기본 정보' ?></h2>
            <div class="space-y-4">
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.register.shop_name') ?? '매장 이름' ?> <span class="text-red-500">*</span></label>
                    <input type="text" name="name" value="<?= htmlspecialchars($shop['name']) ?>" required class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                           placeholder="<?= __('shop.register.shop_name_placeholder') ?? 'Salon Hana' ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.register.category') ?? '업종' ?> <span class="text-red-500">*</span></label>
                    <select name="category_id" required class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        <?php foreach ($categories as $cat):
                            $catName = json_decode($cat['name'], true);
                            $catLabel = $catName[$currentLocale] ?? $catName['en'] ?? $catName['ko'] ?? $cat['slug'];
                        ?>
                        <option value="<?= $cat['id'] ?>" <?= (int)$shop['category_id'] === (int)$cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($catLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.register.description_label') ?? '소개글' ?></label>
                    <textarea name="description" rows="4" class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                              placeholder="<?= __('shop.register.description_placeholder') ?? '사업장을 소개해주세요.' ?>"><?= htmlspecialchars($shop['description'] ?? '') ?></textarea>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.register.contact_person') ?? '담당자명' ?></label>
                        <input type="text" name="contact_person" value="<?= htmlspecialchars($shop['contact_person'] ?? '') ?>" class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                               placeholder="<?= __('shop.register.contact_person_placeholder') ?? '실제 연락 담당자' ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.register.seat_count') ?? '좌석/시술대 수' ?></label>
                        <input type="number" name="seat_count" min="1" value="<?= htmlspecialchars($shop['seat_count'] ?? '') ?>" class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                               placeholder="<?= __('shop.register.seat_count_placeholder') ?? '미정인 경우 예상치' ?>">
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.register.phone') ?? '전화번호' ?></label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($shop['phone'] ?? '') ?>" class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.register.email') ?? '이메일' ?></label>
                        <input type="email" name="email" value="<?= htmlspecialchars($shop['email'] ?? '') ?>" class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.register.website') ?? '웹사이트' ?></label>
                    <input type="url" name="website" value="<?= htmlspecialchars($shop['website'] ?? '') ?>" class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500" placeholder="https://">
                </div>
            </div>
        </div>

        <!-- 주소 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('shop.register.address_title') ?? '주소' ?></h2>
            <div class="space-y-4">
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.register.country') ?? '국가' ?></label>
                        <select name="country" class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <?php foreach (['JP'=>'🇯🇵 Japan','KR'=>'🇰🇷 Korea','US'=>'🇺🇸 US','DE'=>'🇩🇪 Germany','FR'=>'🇫🇷 France','ES'=>'🇪🇸 Spain','ID'=>'🇮🇩 Indonesia','MN'=>'🇲🇳 Mongolia','RU'=>'🇷🇺 Russia','TR'=>'🇹🇷 Turkey','VN'=>'🇻🇳 Vietnam','CN'=>'🇨🇳 China','TW'=>'🇹🇼 Taiwan'] as $code => $label): ?>
                            <option value="<?= $code ?>" <?= ($shop['country'] ?? '') === $code ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.register.postal_code') ?? '우편번호' ?></label>
                        <input type="text" name="postal_code" value="<?= htmlspecialchars($shop['postal_code'] ?? '') ?>" class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.register.address') ?? '주소' ?></label>
                    <input type="text" name="address" value="<?= htmlspecialchars($shop['address'] ?? '') ?>" class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                           placeholder="<?= __('shop.register.address_placeholder') ?? '시/구/동 또는 도로명 주소' ?>">
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.register.address_detail') ?? '상세 주소' ?></label>
                    <input type="text" name="address_detail" value="<?= htmlspecialchars($shop['address_detail'] ?? '') ?>" class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                           placeholder="<?= __('shop.register.address_detail_placeholder') ?? '빌딩명, 층수 등' ?>">
                </div>
                <input type="hidden" name="latitude" id="shopLat" value="<?= $shop['latitude'] ?? '' ?>">
                <input type="hidden" name="longitude" id="shopLng" value="<?= $shop['longitude'] ?? '' ?>">
                <div id="editMap" class="w-full h-64 rounded-lg border border-zinc-200 dark:border-zinc-700"></div>
            </div>
        </div>

        <!-- 사진 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('shop.register.photos') ?? '사진' ?></h2>

            <!-- 커버 이미지 -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('shop.register.cover_image') ?? '커버 이미지' ?></label>
                <div id="coverPreview" class="<?= $shop['cover_image'] ? '' : 'hidden' ?> mb-2 relative inline-block">
                    <img id="coverPreviewImg" class="h-32 rounded-lg object-cover" <?= $shop['cover_image'] ? 'src="' . $baseUrl . htmlspecialchars($shop['cover_image']) . '"' : '' ?>>
                    <button type="button" onclick="removeCover()" class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full text-xs flex items-center justify-center hover:bg-red-600">&times;</button>
                </div>
                <label class="inline-flex items-center gap-2 px-4 py-2.5 border-2 border-dashed border-zinc-300 dark:border-zinc-600 rounded-lg cursor-pointer hover:border-blue-400 hover:bg-blue-50/50 dark:hover:bg-blue-900/20 transition text-sm text-zinc-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span id="coverBtnText"><?= $shop['cover_image'] ? (__('shop.register.change_cover') ?? '변경') : (__('shop.register.cover_image') ?? '커버 이미지 선택') ?></span>
                    <input type="file" name="cover_image" accept="image/*" class="hidden" onchange="previewCover(this)">
                </label>
            </div>

            <!-- 매장 사진 -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('shop.register.photos_label') ?? '매장 사진' ?> (<span id="photoCount"><?= count($existingImages) ?></span>/10)</label>
                <div id="photosGrid" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-2 mb-3">
                    <?php foreach ($existingImages as $idx => $img): ?>
                    <div class="relative aspect-square rounded-lg overflow-hidden bg-zinc-100 dark:bg-zinc-700 group existing-photo" data-path="<?= htmlspecialchars($img) ?>">
                        <img src="<?= $baseUrl . htmlspecialchars($img) ?>" class="w-full h-full object-cover">
                        <button type="button" onclick="removeExistingPhoto(this)" class="absolute top-1 right-1 w-5 h-5 bg-red-500 text-white rounded-full text-[10px] flex items-center justify-center opacity-0 group-hover:opacity-100 transition">&times;</button>
                    </div>
                    <?php endforeach; ?>
                </div>
                <label id="addPhotoBtn" class="inline-flex items-center gap-2 px-4 py-2.5 border-2 border-dashed border-zinc-300 dark:border-zinc-600 rounded-lg cursor-pointer hover:border-blue-400 hover:bg-blue-50/50 dark:hover:bg-blue-900/20 transition text-sm text-zinc-500" <?= count($existingImages) >= 10 ? 'style="display:none"' : '' ?>>
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <?= __('shop.register.add_photo') ?? '사진 추가' ?>
                    <input type="file" accept="image/*" multiple class="hidden" onchange="addPhotos(this)">
                </label>
            </div>

            <!-- 유지할 기존 사진 경로 -->
            <div id="existingPhotoPaths">
                <?php foreach ($existingImages as $img): ?>
                <input type="hidden" name="existing_images[]" value="<?= htmlspecialchars($img) ?>">
                <?php endforeach; ?>
            </div>
            <div id="photoFileInputs" class="hidden"></div>
        </div>

        <script>
        function previewCover(input) {
            if (!input.files[0]) return;
            var reader = new FileReader();
            reader.onload = function(e) {
                document.getElementById('coverPreviewImg').src = e.target.result;
                document.getElementById('coverPreview').classList.remove('hidden');
                document.getElementById('coverBtnText').textContent = '<?= __('shop.register.change_cover') ?? '변경' ?>';
            };
            reader.readAsDataURL(input.files[0]);
        }
        function removeCover() {
            document.getElementById('coverPreview').classList.add('hidden');
            document.getElementById('coverBtnText').textContent = '<?= __('shop.register.cover_image') ?? '커버 이미지 선택' ?>';
            document.querySelector('input[name="cover_image"]').value = '';
        }

        function removeExistingPhoto(btn) {
            var div = btn.closest('.existing-photo');
            var path = div.dataset.path;
            div.remove();
            // hidden input 제거
            document.querySelectorAll('input[name="existing_images[]"]').forEach(function(inp) {
                if (inp.value === path) inp.remove();
            });
            updatePhotoCount();
        }

        var photoFiles = [];
        var photosDT = new DataTransfer();

        function addPhotos(input) {
            var grid = document.getElementById('photosGrid');
            var totalCount = grid.querySelectorAll('div').length;
            var files = input.files;
            for (var i = 0; i < files.length; i++) {
                if (totalCount + photoFiles.length >= 10) break;
                var file = files[i];
                if (!file.type.startsWith('image/')) continue;
                photoFiles.push(file);
                photosDT.items.add(file);

                var idx = photoFiles.length - 1;
                var div = document.createElement('div');
                div.className = 'relative aspect-square rounded-lg overflow-hidden bg-zinc-100 dark:bg-zinc-700 group new-photo';
                var img = document.createElement('img');
                img.className = 'w-full h-full object-cover';
                var reader = new FileReader();
                reader.onload = (function(imgEl) { return function(e) { imgEl.src = e.target.result; }; })(img);
                reader.readAsDataURL(file);
                var removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'absolute top-1 right-1 w-5 h-5 bg-red-500 text-white rounded-full text-[10px] flex items-center justify-center opacity-0 group-hover:opacity-100 transition';
                removeBtn.innerHTML = '&times;';
                removeBtn.onclick = (function(index, el) { return function() { removeNewPhoto(index, el); }; })(idx, div);
                div.appendChild(img);
                div.appendChild(removeBtn);
                grid.appendChild(div);
            }
            updatePhotoCount();
            syncPhotoInput();
            input.value = '';
        }

        function removeNewPhoto(idx, el) {
            el.remove();
            photoFiles[idx] = null;
            photosDT = new DataTransfer();
            photoFiles.forEach(function(f) { if (f) photosDT.items.add(f); });
            photoFiles = photoFiles.filter(function(f) { return f !== null; });
            updatePhotoCount();
            syncPhotoInput();
        }

        function updatePhotoCount() {
            var grid = document.getElementById('photosGrid');
            var count = grid.querySelectorAll('div').length;
            document.getElementById('photoCount').textContent = count;
            document.getElementById('addPhotoBtn').style.display = count >= 10 ? 'none' : '';
        }

        function syncPhotoInput() {
            var container = document.getElementById('photoFileInputs');
            container.innerHTML = '';
            if (photosDT.files.length > 0) {
                var input = document.createElement('input');
                input.type = 'file'; input.name = 'images[]'; input.multiple = true;
                input.files = photosDT.files;
                container.appendChild(input);
            }
        }
        </script>

        <!-- 영업시간 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('shop.register.business_hours') ?? '영업시간' ?></h2>
            <div class="space-y-2">
                <?php foreach ($dayLabels as $dayKey => $dayLabel):
                    $h = $existingHours[$dayKey] ?? [];
                    $isClosed = !empty($h['closed']);
                ?>
                <div class="flex items-center gap-3">
                    <span class="w-8 text-sm font-medium text-zinc-600 dark:text-zinc-400"><?= $dayLabel ?></span>
                    <input type="time" name="hours_<?= $dayKey ?>_open" value="<?= htmlspecialchars($h['open'] ?? '09:00') ?>" class="px-3 py-1.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-sm text-zinc-900 dark:text-white">
                    <span class="text-zinc-400">~</span>
                    <input type="time" name="hours_<?= $dayKey ?>_close" value="<?= htmlspecialchars($h['close'] ?? '19:00') ?>" class="px-3 py-1.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-sm text-zinc-900 dark:text-white">
                    <label class="flex items-center gap-1.5 text-sm text-zinc-500">
                        <input type="checkbox" name="hours_<?= $dayKey ?>_closed" <?= $isClosed ? 'checked' : '' ?> class="rounded border-zinc-300 text-blue-600">
                        <?= __('shop.register.closed') ?? '휴무' ?>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 이벤트 등록 배너 -->
        <a href="<?= $baseUrl ?>/shop/<?= htmlspecialchars($shop['slug']) ?>/events" class="block bg-gradient-to-r from-amber-50 to-orange-50 dark:from-amber-900/20 dark:to-orange-900/20 border border-amber-200 dark:border-amber-700 rounded-xl p-5 hover:shadow-md transition group">
            <div class="flex items-center justify-between">
                <div class="flex items-center gap-3">
                    <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/40 rounded-lg flex items-center justify-center">
                        <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                    </div>
                    <div>
                        <p class="text-sm font-bold text-amber-900 dark:text-amber-200"><?= __('shop.event.banner_cta') ?? '이벤트를 등록하고 고객을 끌어보세요!' ?></p>
                        <p class="text-xs text-amber-600 dark:text-amber-400">&yen;10,000 / <?= __('shop.event.banner_period') ?? '7~30일 노출' ?></p>
                    </div>
                </div>
                <svg class="w-5 h-5 text-amber-400 group-hover:translate-x-1 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </div>
        </a>

        <!-- 사업자 정보 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2"><?= __('shop.register.business_info') ?? '사업자 정보' ?></h2>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-4"><?= __('shop.register.business_info_hint') ?></p>
            <div class="space-y-4">
                <!-- 개업 상황 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('shop.register.opening_status') ?? '개업 상황' ?></label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300 cursor-pointer">
                            <input type="radio" name="opening_status" value="opened" <?= ($shop['opening_status'] ?? 'opened') === 'opened' ? 'checked' : '' ?> class="text-blue-600 border-zinc-300 dark:border-zinc-600">
                            <?= __('shop.register.status_opened') ?? '개업 완료' ?>
                        </label>
                        <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300 cursor-pointer">
                            <input type="radio" name="opening_status" value="planned" <?= ($shop['opening_status'] ?? '') === 'planned' ? 'checked' : '' ?> class="text-blue-600 border-zinc-300 dark:border-zinc-600">
                            <?= __('shop.register.status_planned') ?? '개업 예정' ?>
                        </label>
                    </div>
                </div>
                <!-- 개업 시기 -->
                <?php
                    $_oy = $shop['opened_at'] ? (int)date('Y', strtotime($shop['opened_at'])) : 0;
                    $_om = $shop['opened_at'] ? (int)date('m', strtotime($shop['opened_at'])) : 0;
                ?>
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.register.opened_at') ?? '개업 시기' ?></label>
                        <div class="flex gap-2">
                            <select name="opened_at_year" class="flex-1 px-3 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm">
                                <option value=""><?= __('shop.register.year') ?? '연도' ?></option>
                                <?php for ($y = (int)date('Y') + 2; $y >= 2000; $y--): ?>
                                <option value="<?= $y ?>" <?= $_oy === $y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                            <select name="opened_at_month" class="flex-1 px-3 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm">
                                <option value=""><?= __('shop.register.month') ?? '월' ?></option>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= $_om === $m ? 'selected' : '' ?>><?= $m ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.register.representative') ?? '대표자명' ?></label>
                        <input type="text" name="representative" value="<?= htmlspecialchars($shop['representative'] ?? '') ?>"
                               class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.register.business_number') ?? '사업자등록번호' ?></label>
                        <input type="text" name="business_number" value="<?= htmlspecialchars($shop['business_number'] ?? '') ?>"
                               class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                               placeholder="000-00-00000">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.register.business_license') ?? '사업자등록증' ?></label>
                    <?php if (!empty($shop['business_license'])): ?>
                    <div class="mb-2 flex items-center gap-2">
                        <span class="text-xs text-green-600 dark:text-green-400">✓ <?= __('shop.edit.license_submitted') ?? '제출됨' ?></span>
                        <a href="<?= $baseUrl . htmlspecialchars($shop['business_license']) ?>" target="_blank" class="text-xs text-blue-600 hover:underline"><?= __('shop.edit.view_license') ?? '확인' ?></a>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="business_license" accept="image/*,.pdf"
                           class="w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-amber-50 file:text-amber-600 hover:file:bg-amber-100 dark:file:bg-amber-900/30 dark:file:text-amber-400">
                    <p class="text-[10px] text-zinc-400 mt-1"><?= __('shop.register.business_license_hint') ?? '이미지(JPG, PNG) 또는 PDF 파일' ?></p>
                </div>
            </div>
        </div>

        <!-- SNS -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">SNS</h2>
            <div class="space-y-3">
                <?php
                $snsPlaceholders = ['instagram'=>'@username','x'=>'@username','facebook'=>'https://facebook.com/...','line'=>'@line-id'];
                foreach (['instagram'=>'Instagram','x'=>'X (Twitter)','facebook'=>'Facebook','line'=>'LINE'] as $snsKey => $snsLabel): ?>
                <div class="flex items-center gap-3">
                    <span class="text-sm w-24 text-zinc-600 dark:text-zinc-400"><?= $snsLabel ?></span>
                    <input type="text" name="sns_<?= $snsKey ?>" value="<?= htmlspecialchars($existingSns[$snsKey] ?? '') ?>" class="flex-1 px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm"
                           placeholder="<?= $snsPlaceholders[$snsKey] ?>">
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 특징 태그 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('shop.register.features') ?? '매장 특징' ?></h2>
            <div id="featureTagsArea">
                <?php foreach ($_allFeatures as $group => $tags):
                    $groupLabel = $_featuresRaw[$group] ?? $group;
                    $groupClasses = ($group === '_common') ? '' : ' feature-group';
                    $groupData = ($group === '_common') ? '' : ' data-group="' . htmlspecialchars($group) . '"';
                ?>
                <div class="mb-3<?= $groupClasses ?>"<?= $groupData ?>>
                    <p class="text-xs font-medium text-zinc-400 mb-1.5"><?= htmlspecialchars($groupLabel) ?></p>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($tags as $key => $label): ?>
                        <label class="flex items-center gap-1.5 px-3 py-1.5 border border-zinc-200 dark:border-zinc-600 rounded-full text-sm text-zinc-600 dark:text-zinc-400 cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/20 has-[:checked]:bg-blue-100 has-[:checked]:border-blue-300 has-[:checked]:text-blue-700 dark:has-[:checked]:bg-blue-900/30 dark:has-[:checked]:text-blue-400 transition">
                            <input type="checkbox" name="features[]" value="<?= $key ?>" <?= in_array($key, $existingFeatures) ? 'checked' : '' ?> class="hidden">
                            <?= htmlspecialchars($label) ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <script>
            var featureCatMap = <?= json_encode($_featureCategoryMap) ?>;
            var catSlugMap = <?= json_encode($_catSlugMap) ?>;
            document.querySelector('[name=category_id]').addEventListener('change', function() {
                var catSlug = catSlugMap[this.value] || '';
                var groups = featureCatMap[catSlug] || ['_common'];
                document.querySelectorAll('.feature-group').forEach(function(el) {
                    el.style.display = groups.indexOf(el.dataset.group) !== -1 ? '' : 'none';
                });
            });
            document.querySelector('[name=category_id]').dispatchEvent(new Event('change'));
            </script>
        </div>

        <!-- 제출 -->
        <div class="flex justify-end gap-3">
            <a href="<?= $baseUrl ?>/shop/<?= htmlspecialchars($shop['slug']) ?>" class="px-6 py-3 text-sm font-medium text-zinc-600 dark:text-zinc-400 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition"><?= __('common.buttons.cancel') ?? '취소' ?></a>
            <button type="submit" class="px-6 py-3 text-sm font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"><?= __('common.buttons.save') ?? '저장' ?></button>
        </div>
    </form>
</div>

<!-- 1:1 관리자 상담 -->
<?php
// 비공개 Q&A 로드
$privateQA = [];
try {
    $pqStmt = $pdo->prepare("SELECT i.*, u.name as user_name FROM {$prefix}shop_inquiries i LEFT JOIN {$prefix}users u ON i.user_id = u.id WHERE i.shop_id = ? AND i.is_public = 0 ORDER BY i.created_at ASC");
    $pqStmt->execute([$shop['id']]);
    $privateQA = $pqStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (\Throwable $e) {}

// 상담 메시지 작성 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['consultation_msg'])) {
    $cMsg = trim($_POST['consultation_msg']);
    $cAttachments = [];
    if (!empty($_FILES['consultation_files'])) {
        $uploadDir = BASE_PATH . '/storage/uploads/consultations/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        for ($fi = 0; $fi < count($_FILES['consultation_files']['name']); $fi++) {
            if ($_FILES['consultation_files']['error'][$fi] !== UPLOAD_ERR_OK) continue;
            if (count($cAttachments) >= 5) break;
            $origName = $_FILES['consultation_files']['name'][$fi];
            $ext = strtolower(pathinfo($origName, PATHINFO_EXTENSION));
            $size = $_FILES['consultation_files']['size'][$fi];
            $filename = 'consult_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['consultation_files']['tmp_name'][$fi], $uploadDir . $filename)) {
                $isImage = in_array($ext, ['jpg','jpeg','png','gif','webp']);
                $cAttachments[] = ['name' => $origName, 'path' => '/storage/uploads/consultations/' . $filename, 'type' => $isImage ? 'image' : 'file', 'size' => $size];
            }
        }
    }
    if ($cMsg || !empty($cAttachments)) {
        $cIns = $pdo->prepare("INSERT INTO {$prefix}shop_inquiries (shop_id, user_id, question, attachments, is_public, status) VALUES (?, ?, ?, ?, 0, 'pending')");
        $cIns->execute([$shop['id'], $currentUser['id'], $cMsg ?: '', !empty($cAttachments) ? json_encode($cAttachments) : null]);
        header('Location: ' . ($config['app_url'] ?? '') . '/shop/' . $shop['slug'] . '/edit#consultation');
        exit;
    }
}
?>
<style>
.ct-chat-wrap { }
.ct-msg-row { display:flex; margin-bottom:0.75rem; }
.ct-msg-row.mine { justify-content:flex-end; }
.ct-msg-row.theirs { justify-content:flex-start; }
.ct-bubble { display:inline-block; max-width:480px; padding:0.5rem 0.75rem; border-radius:1rem; font-size:14px; line-height:1.5; white-space:pre-wrap; word-break:break-word; }
.ct-msg-row.mine .ct-bubble { background:#6366f1; color:#fff; border-bottom-right-radius:0.25rem; }
.ct-msg-row.theirs .ct-bubble { background:#f4f4f5; color:#18181b; border-bottom-left-radius:0.25rem; }
.dark .ct-msg-row.theirs .ct-bubble { background:#3f3f46; color:#e4e4e7; }
.ct-meta { font-size:10px; opacity:0.5; margin-top:2px; }
.ct-msg-row.mine .ct-meta { text-align:right; color:rgba(255,255,255,0.6); }
.ct-sender { font-size:11px; font-weight:600; color:#6366f1; margin-bottom:2px; }
.dark .ct-sender { color:#818cf8; }
.ct-attachments { display:flex; flex-wrap:wrap; gap:4px; margin-top:6px; }
.ct-attach { display:inline-block; }
.ct-attach img { max-width:180px; max-height:120px; border-radius:0.5rem; cursor:pointer; display:block; }
.ct-attach a { display:inline-flex; align-items:center; gap:4px; padding:3px 8px; background:rgba(0,0,0,0.08); border-radius:6px; font-size:11px; text-decoration:none; color:inherit; }
.ct-msg-row.mine .ct-attach a { background:rgba(255,255,255,0.18); color:#fff; }
.dark .ct-attach a { background:rgba(255,255,255,0.1); }
.ct-file-preview { display:flex; gap:0.375rem; flex-wrap:wrap; margin-bottom:0.5rem; }
.ct-fp-item { position:relative; }
.ct-fp-item img { width:50px; height:50px; object-fit:cover; border-radius:6px; border:1px solid #e4e4e7; }
.ct-fp-item .ct-fp-icon { width:50px; height:50px; border-radius:6px; border:1px solid #e4e4e7; display:flex; align-items:center; justify-content:center; font-size:9px; text-align:center; background:#fafafa; padding:2px; word-break:break-all; }
.dark .ct-fp-item .ct-fp-icon { background:#27272a; border-color:#3f3f46; }
.ct-fp-item .ct-fp-rm { position:absolute; top:-4px; right:-4px; width:16px; height:16px; background:#ef4444; color:#fff; border-radius:50%; font-size:9px; display:flex; align-items:center; justify-content:center; cursor:pointer; border:none; }
</style>
<div id="consultation" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 pb-8">
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-5 py-3 border-b border-zinc-200 dark:border-zinc-700 flex items-center gap-2">
            <svg class="w-5 h-5 text-indigo-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/></svg>
            <h2 class="text-base font-semibold text-zinc-900 dark:text-white"><?= __('shop.consultation.title') ?? '관리자 상담' ?></h2>
        </div>
        <p class="px-5 py-2 text-xs text-zinc-400 dark:text-zinc-500 border-b border-zinc-100 dark:border-zinc-700"><?= __('shop.consultation.hint') ?? '등록, 승인, 운영 관련 문의사항을 관리자에게 직접 문의할 수 있습니다. 이 내용은 비공개입니다.' ?></p>

        <div class="px-5 py-4 max-h-[500px] overflow-y-auto" id="ctMsgArea"><div class="ct-chat-wrap">
            <?php if (empty($privateQA)): ?>
            <p class="text-sm text-zinc-300 dark:text-zinc-600 text-center py-8"><?= __('shop.consultation.empty') ?? '상담 내역이 없습니다.' ?></p>
            <?php else:
                $lastDate = '';
                foreach ($privateQA as $pq):
                    $isMine = $pq['user_id'] === $currentUser['id'];
                    $msgDate = date('Y-m-d', strtotime($pq['created_at']));
                    if ($msgDate !== $lastDate): $lastDate = $msgDate; ?>
                    <div class="text-center my-3"><span class="text-[10px] text-zinc-400 bg-zinc-100 dark:bg-zinc-700 px-3 py-1 rounded-full"><?= $msgDate ?></span></div>
                    <?php endif; ?>
            <div class="ct-msg-row <?= $isMine ? 'mine' : 'theirs' ?>">
                <div class="ct-bubble">
                    <?php if (!$isMine): ?><div class="ct-sender"><?= __('shop.consultation.admin') ?? '관리자' ?></div><?php endif; ?>
                    <?php if ($pq['question']): ?><?= nl2br(htmlspecialchars($pq['question'])) ?><?php endif; ?>
                    <?php $att = json_decode($pq['attachments'] ?? 'null', true) ?: [];
                    if (!empty($att)): ?>
                    <div class="ct-attachments">
                        <?php foreach ($att as $af): ?>
                        <div class="ct-attach">
                            <?php if ($af['type'] === 'image'): ?>
                            <img src="<?= $baseUrl . htmlspecialchars($af['path']) ?>" onclick="window.open(this.src)" alt="<?= htmlspecialchars($af['name']) ?>">
                            <?php else:
                                $sizeStr = $af['size'] > 1048576 ? number_format($af['size']/1048576,1).'MB' : round($af['size']/1024).'KB';
                            ?>
                            <a href="<?= $baseUrl . htmlspecialchars($af['path']) ?>" target="_blank">📎 <?= htmlspecialchars($af['name']) ?> (<?= $sizeStr ?>)</a>
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <div class="ct-meta"><?= date('H:i', strtotime($pq['created_at'])) ?></div>
                </div>
            </div>
            <?php if ($pq['answer']): ?>
            <div class="ct-msg-row theirs">
                <div class="ct-bubble">
                    <div class="ct-sender"><?= __('shop.consultation.admin') ?? '관리자' ?></div>
                    <?= nl2br(htmlspecialchars($pq['answer'])) ?>
                    <div class="ct-meta"><?= $pq['answered_at'] ? date('H:i', strtotime($pq['answered_at'])) : '' ?></div>
                </div>
            </div>
            <?php endif; ?>
                <?php endforeach; ?>
            <?php endif; ?>
        </div></div>

        <div class="px-5 py-3 border-t border-zinc-200 dark:border-zinc-700">
            <div class="ct-chat-wrap">
            <div id="ctFilePreview" class="ct-file-preview"></div>
            <form method="POST" enctype="multipart/form-data" id="ctForm" class="flex items-end gap-2">
                <label class="cursor-pointer flex-shrink-0 p-2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition" title="<?= __('shop.consultation.attach') ?? '파일 첨부' ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>
                    <input type="file" name="consultation_files[]" multiple class="hidden" onchange="ctPreviewFiles(this)" accept="image/*,.pdf,.doc,.docx,.xls,.xlsx,.txt,.zip">
                </label>
                <input type="text" name="consultation_msg"
                       placeholder="<?= __('shop.consultation.placeholder') ?? '문의사항을 입력하세요' ?>"
                       class="flex-1 px-4 py-2 text-sm border border-zinc-200 dark:border-zinc-600 rounded-xl bg-zinc-50 dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-indigo-500 focus:bg-white dark:focus:bg-zinc-600">
                <button type="submit" class="flex-shrink-0 p-2 bg-indigo-600 text-white rounded-xl hover:bg-indigo-700 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                </button>
            </form>
            </div>
        </div>
    </div>
</div>
<script>
// 스크롤 하단으로
(function(){ var a = document.getElementById('ctMsgArea'); if(a) a.scrollTop = a.scrollHeight; })();
// 파일 미리보기
var ctPendingDT = new DataTransfer();
function ctPreviewFiles(input) {
    for (var i = 0; i < input.files.length; i++) {
        if (ctPendingDT.files.length >= 5) break;
        ctPendingDT.items.add(input.files[i]);
    }
    input.files = ctPendingDT.files;
    ctRenderPreviews();
}
function ctRenderPreviews() {
    var c = document.getElementById('ctFilePreview');
    c.innerHTML = '';
    for (var i = 0; i < ctPendingDT.files.length; i++) {
        var f = ctPendingDT.files[i];
        var div = document.createElement('div');
        div.className = 'ct-fp-item';
        if (f.type.startsWith('image/')) {
            var img = document.createElement('img');
            var reader = new FileReader();
            reader.onload = (function(el) { return function(e) { el.src = e.target.result; }; })(img);
            reader.readAsDataURL(f);
            div.appendChild(img);
        } else {
            var icon = document.createElement('div');
            icon.className = 'ct-fp-icon';
            icon.textContent = f.name.length > 10 ? f.name.substring(0,10)+'...' : f.name;
            div.appendChild(icon);
        }
        var btn = document.createElement('button');
        btn.type = 'button';
        btn.className = 'ct-fp-rm';
        btn.innerHTML = '&times;';
        btn.onclick = (function(idx) { return function() {
            var ndt = new DataTransfer();
            for (var j = 0; j < ctPendingDT.files.length; j++) { if (j !== idx) ndt.items.add(ctPendingDT.files[j]); }
            ctPendingDT = ndt;
            document.querySelector('[name="consultation_files[]"]').files = ctPendingDT.files;
            ctRenderPreviews();
        }; })(i);
        div.appendChild(btn);
        c.appendChild(div);
    }
}
</script>

<!-- Leaflet 지도 -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function() {
    var mapEl = document.getElementById('editMap');
    if (!mapEl) return;
    var lat = parseFloat(document.getElementById('shopLat').value) || 35.6762;
    var lng = parseFloat(document.getElementById('shopLng').value) || 139.6503;
    var map = L.map(mapEl).setView([lat, lng], 15);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {attribution:'© OpenStreetMap',maxZoom:19}).addTo(map);
    var marker = L.marker([lat, lng], {draggable: true}).addTo(map);
    function updateLatLng(ll) {
        document.getElementById('shopLat').value = ll.lat.toFixed(7);
        document.getElementById('shopLng').value = ll.lng.toFixed(7);
    }
    marker.on('dragend', function(e) { updateLatLng(e.target.getLatLng()); });
    map.on('click', function(e) { marker.setLatLng(e.latlng); updateLatLng(e.latlng); });
})();
</script>
