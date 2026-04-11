<?php
/**
 * 업소 등록 페이지 (프론트엔드)
 * 로그인 필수. 등록 후 관리자 승인 대기.
 */

require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
if (!\RzxLib\Core\Auth\Auth::check()) {
    header('Location: ' . ($config['app_url'] ?? '') . '/login?redirect=shop/register');
    exit;
}

$currentUser = \RzxLib\Core\Auth\Auth::user();
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$currentLocale = $config['locale'] ?? (function_exists('current_locale') ? current_locale() : 'ko');

// 플러그인 번역 로드 → 코어 번역 시스템에 병합 (최상단에서 실행)
$_shopLangEarly = @include(BASE_PATH . '/plugins/vos-shop/lang/' . $currentLocale . '/shop.php');
if (!is_array($_shopLangEarly)) $_shopLangEarly = @include(BASE_PATH . '/plugins/vos-shop/lang/ko/shop.php');
if (is_array($_shopLangEarly) && class_exists('\RzxLib\Core\I18n\Translator')) {
    \RzxLib\Core\I18n\Translator::merge('shop', $_shopLangEarly);
}

$pageTitle = __('shop.register.title') ?? '매장 등록';

// 카테고리 로드
$categories = $pdo->query("SELECT id, slug, name FROM {$prefix}shop_categories WHERE is_active = 1 ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC);
$currentLocale = $config['locale'] ?? (function_exists('current_locale') ? current_locale() : 'ko');

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
    $instagram = trim($_POST['sns_instagram'] ?? '');
    $x = trim($_POST['sns_x'] ?? '');
    $facebook = trim($_POST['sns_facebook'] ?? '');
    $line = trim($_POST['sns_line'] ?? '');

    // 영업시간
    $businessHours = [];
    foreach (['mon','tue','wed','thu','fri','sat','sun'] as $day) {
        $open = trim($_POST["hours_{$day}_open"] ?? '');
        $close = trim($_POST["hours_{$day}_close"] ?? '');
        $closed = isset($_POST["hours_{$day}_closed"]);
        $businessHours[$day] = $closed ? ['closed' => true] : ['open' => $open, 'close' => $close];
    }

    // 특징 태그
    $features = $_POST['features'] ?? [];

    // 사업자 정보
    $representative = trim($_POST['representative'] ?? '');
    $contactPerson = trim($_POST['contact_person'] ?? '');
    $businessNumber = trim($_POST['business_number'] ?? '');
    $seatCount = ($_POST['seat_count'] ?? '') !== '' ? (int)$_POST['seat_count'] : null;
    $openingStatus = in_array($_POST['opening_status'] ?? '', ['planned', 'opened']) ? $_POST['opening_status'] : 'opened';
    $openedAtY = (int)($_POST['opened_at_year'] ?? 0);
    $openedAtM = (int)($_POST['opened_at_month'] ?? 0);
    $openedAt = ($openedAtY && $openedAtM) ? sprintf('%04d-%02d-01', $openedAtY, $openedAtM) : null;
    $inquiryNote = trim($_POST['inquiry_note'] ?? '');

    // 유효성 검사
    if (!$name) $errors[] = __('shop.register.error_name') ?? '매장 이름을 입력해주세요.';
    if (!$categoryId) $errors[] = __('shop.register.error_category') ?? '업종을 선택해주세요.';
    if (!$address) $errors[] = __('shop.register.error_address') ?? '주소를 입력해주세요.';

    // slug 생성
    $slug = preg_replace('/[^a-zA-Z0-9-]/', '', str_replace(' ', '-', strtolower($name)));
    $slug = $slug ?: 'shop-' . time();
    // 중복 체크
    $chk = $pdo->prepare("SELECT id FROM {$prefix}shops WHERE slug = ?");
    $chk->execute([$slug]);
    if ($chk->fetch()) $slug .= '-' . substr(bin2hex(random_bytes(3)), 0, 6);

    if (empty($errors)) {
        $uuid = sprintf('%s-%s-%s-%s-%s', bin2hex(random_bytes(4)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(2)), bin2hex(random_bytes(6)));
        $sns = json_encode(array_filter(['instagram' => $instagram, 'x' => $x, 'facebook' => $facebook, 'line' => $line]));
        $featuresJson = json_encode($features);

        // 커버 이미지 업로드
        $coverImage = '';
        if (!empty($_FILES['cover_image']) && $_FILES['cover_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = BASE_PATH . '/storage/uploads/shops/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            $ext = pathinfo($_FILES['cover_image']['name'], PATHINFO_EXTENSION) ?: 'jpg';
            $filename = 'shop_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
            if (move_uploaded_file($_FILES['cover_image']['tmp_name'], $uploadDir . $filename)) {
                $coverImage = '/storage/uploads/shops/' . $filename;
            }
        }

        // 추가 사진 업로드 (최대 10장)
        $images = [];
        if (!empty($_FILES['images'])) {
            $uploadDir = BASE_PATH . '/storage/uploads/shops/';
            if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
            for ($i = 0; $i < count($_FILES['images']['name']); $i++) {
                if ($_FILES['images']['error'][$i] !== UPLOAD_ERR_OK) continue;
                if (count($images) >= 10) break;
                $ext = pathinfo($_FILES['images']['name'][$i], PATHINFO_EXTENSION) ?: 'jpg';
                $filename = 'shop_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
                if (move_uploaded_file($_FILES['images']['tmp_name'][$i], $uploadDir . $filename)) {
                    $images[] = '/storage/uploads/shops/' . $filename;
                }
            }
        }

        // 사업자등록증 업로드 (비공개 디렉토리)
        $businessLicense = '';
        if (!empty($_FILES['business_license']) && $_FILES['business_license']['error'] === UPLOAD_ERR_OK) {
            $licDir = BASE_PATH . '/storage/private/licenses/';
            if (!is_dir($licDir)) mkdir($licDir, 0755, true);
            $ext = pathinfo($_FILES['business_license']['name'], PATHINFO_EXTENSION) ?: 'pdf';
            $filename = 'license_' . time() . '_' . bin2hex(random_bytes(6)) . '.' . $ext;
            if (move_uploaded_file($_FILES['business_license']['tmp_name'], $licDir . $filename)) {
                $businessLicense = '/storage/private/licenses/' . $filename;
            }
        }

        $stmt = $pdo->prepare("INSERT INTO {$prefix}shops (uuid, user_id, category_id, name, slug, description, phone, email, website, country, postal_code, address, address_detail, latitude, longitude, cover_image, images, business_hours, sns, features, representative, contact_person, business_number, business_license, seat_count, opening_status, opened_at, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending')");
        $stmt->execute([
            $uuid, $currentUser['id'], $categoryId, $name, $slug, $description,
            $phone, $email, $website, $country, $postalCode, $address, $addressDetail,
            $latitude ?: null, $longitude ?: null,
            $coverImage, json_encode($images), json_encode($businessHours), $sns, $featuresJson,
            $representative, $contactPerson, $businessNumber, $businessLicense,
            $seatCount, $openingStatus, $openedAt
        ]);

        // 문의사항이 있으면 Q&A로 저장
        if ($inquiryNote) {
            $shopId = $pdo->lastInsertId();
            $iq = $pdo->prepare("INSERT INTO {$prefix}shop_inquiries (shop_id, user_id, question, is_public, status) VALUES (?, ?, ?, 0, 'pending')");
            $iq->execute([$shopId, $currentUser['id'], $inquiryNote]);
        }

        $success = true;
        $registeredSlug = $slug;
    }
}

// 업종별 특징 태그 매핑
$_featureCategoryMap = [
    'hair' => ['_common', '_hair'],
    'nail' => ['_common', '_nail'],
    'clinic' => ['_common', '_clinic'],
    'fitness' => ['_common', '_fitness'],
    'restaurant' => ['_common', '_restaurant'],
    'pet' => ['_common', '_pet'],
    'hotel' => ['_common', '_hotel'],
    'rental' => ['_common', '_rental'],
    'education' => ['_common', '_other'],
    'photo' => ['_common', '_other'],
    'spa' => ['_common', '_nail'],
];

// 카테고리 slug → id 매핑
$_catSlugMap = [];
foreach ($categories as $c) { $_catSlugMap[$c['id']] = $c['slug']; }

// 전체 특징 태그 로드 (그룹별)
$_allFeatures = [];
$_currentGroup = '_common';
$_shopLang = include(BASE_PATH . '/plugins/vos-shop/lang/' . $currentLocale . '/shop.php');
if (!is_array($_shopLang)) $_shopLang = include(BASE_PATH . '/plugins/vos-shop/lang/ko/shop.php');
$_featuresRaw = $_shopLang['features'] ?? [];
foreach ($_featuresRaw as $key => $label) {
    if (str_starts_with($key, '_')) {
        $_currentGroup = $key;
        continue;
    }
    $_allFeatures[$_currentGroup][$key] = $label;
}

$seoContext = ['type' => 'sub', 'subpage_title' => $pageTitle];
?>

<?php if ($success): ?>
<div class="max-w-2xl mx-auto px-4 py-16 text-center">
    <div class="w-16 h-16 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mx-auto mb-4">
        <svg class="w-8 h-8 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
    </div>
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white mb-2"><?= __('shop.register.success_title') ?? '등록이 완료되었습니다!' ?></h1>
    <p class="text-zinc-500 dark:text-zinc-400 mb-6"><?= __('shop.register.success_desc') ?? '관리자 승인 후 페이지가 공개됩니다.' ?></p>
    <div class="flex items-center justify-center gap-3">
        <a href="<?= $baseUrl ?>/shop/<?= htmlspecialchars($registeredSlug ?? '') ?>/edit" class="inline-flex items-center px-6 py-3 bg-indigo-600 text-white rounded-lg hover:bg-indigo-700 transition"><?= __('shop.register.go_edit') ?? '사업장 정보 수정' ?></a>
        <a href="<?= $baseUrl ?>/shop/<?= htmlspecialchars($registeredSlug ?? '') ?>" class="inline-flex items-center px-6 py-3 border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition"><?= __('shop.register.go_detail') ?? '사업장 페이지 보기' ?></a>
    </div>
</div>

<?php else: ?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white mb-2"><?= $pageTitle ?></h1>
    <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-6"><?= __('shop.register.description') ?? '매장 정보를 입력하고 무료로 등록하세요.' ?></p>

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
                    <input type="text" name="name" value="<?= htmlspecialchars($_POST['name'] ?? '') ?>" required
                           class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                           placeholder="<?= __('shop.register.shop_name_placeholder') ?? 'Salon Hana' ?>">
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.register.category') ?? '업종' ?> <span class="text-red-500">*</span></label>
                    <select name="category_id" required class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                        <option value="">-- <?= __('common.select') ?? '선택' ?> --</option>
                        <?php foreach ($categories as $cat):
                            $catName = json_decode($cat['name'], true);
                            $catLabel = $catName[$currentLocale] ?? $catName['en'] ?? $catName['ko'] ?? $cat['slug'];
                        ?>
                        <option value="<?= $cat['id'] ?>" <?= (int)($_POST['category_id'] ?? 0) === (int)$cat['id'] ? 'selected' : '' ?>><?= htmlspecialchars($catLabel) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.register.description_label') ?? '소개글' ?></label>
                    <textarea name="description" rows="4"
                              class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                              placeholder="<?= __('shop.register.description_placeholder') ?? '매장을 소개해주세요.' ?>"><?= htmlspecialchars($_POST['description'] ?? '') ?></textarea>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.register.contact_person') ?? '담당자명' ?></label>
                        <input type="text" name="contact_person" value="<?= htmlspecialchars($_POST['contact_person'] ?? '') ?>"
                               class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                               placeholder="<?= __('shop.register.contact_person_placeholder') ?? '실제 연락 담당자' ?>">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.register.seat_count') ?? '좌석/시술대 수' ?></label>
                        <input type="number" name="seat_count" min="1" value="<?= htmlspecialchars($_POST['seat_count'] ?? '') ?>"
                               class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                               placeholder="<?= __('shop.register.seat_count_placeholder') ?? '미정인 경우 예상치' ?>">
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.register.phone') ?? '전화번호' ?></label>
                        <input type="tel" name="phone" value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                               class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.register.email') ?? '이메일' ?></label>
                        <input type="email" name="email" value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                               class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.register.website') ?? '웹사이트' ?></label>
                    <input type="url" name="website" value="<?= htmlspecialchars($_POST['website'] ?? '') ?>"
                           class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                           placeholder="https://">
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
                            <option value="JP" <?= ($_POST['country'] ?? '') === 'JP' ? 'selected' : '' ?>>🇯🇵 Japan</option>
                            <option value="KR" <?= ($_POST['country'] ?? '') === 'KR' ? 'selected' : '' ?>>🇰🇷 Korea</option>
                            <option value="US" <?= ($_POST['country'] ?? '') === 'US' ? 'selected' : '' ?>>🇺🇸 United States</option>
                            <option value="DE" <?= ($_POST['country'] ?? '') === 'DE' ? 'selected' : '' ?>>🇩🇪 Germany</option>
                            <option value="FR" <?= ($_POST['country'] ?? '') === 'FR' ? 'selected' : '' ?>>🇫🇷 France</option>
                            <option value="ES" <?= ($_POST['country'] ?? '') === 'ES' ? 'selected' : '' ?>>🇪🇸 Spain</option>
                            <option value="ID" <?= ($_POST['country'] ?? '') === 'ID' ? 'selected' : '' ?>>🇮🇩 Indonesia</option>
                            <option value="MN" <?= ($_POST['country'] ?? '') === 'MN' ? 'selected' : '' ?>>🇲🇳 Mongolia</option>
                            <option value="RU" <?= ($_POST['country'] ?? '') === 'RU' ? 'selected' : '' ?>>🇷🇺 Russia</option>
                            <option value="TR" <?= ($_POST['country'] ?? '') === 'TR' ? 'selected' : '' ?>>🇹🇷 Turkey</option>
                            <option value="VN" <?= ($_POST['country'] ?? '') === 'VN' ? 'selected' : '' ?>>🇻🇳 Vietnam</option>
                            <option value="CN" <?= ($_POST['country'] ?? '') === 'CN' ? 'selected' : '' ?>>🇨🇳 China</option>
                            <option value="TW" <?= ($_POST['country'] ?? '') === 'TW' ? 'selected' : '' ?>>🇹🇼 Taiwan</option>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.register.postal_code') ?? '우편번호' ?></label>
                        <input type="text" name="postal_code" value="<?= htmlspecialchars($_POST['postal_code'] ?? '') ?>"
                               class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.register.address') ?? '주소' ?> <span class="text-red-500">*</span></label>
                    <input type="text" name="address" value="<?= htmlspecialchars($_POST['address'] ?? '') ?>" required
                           class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                           placeholder="<?= __('shop.register.address_placeholder') ?? '시/구/동 또는 도로명 주소' ?>">
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.register.address_detail') ?? '상세 주소' ?></label>
                    <input type="text" name="address_detail" value="<?= htmlspecialchars($_POST['address_detail'] ?? '') ?>"
                           class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                           placeholder="<?= __('shop.register.address_detail_placeholder') ?? '빌딩명, 층수 등' ?>">
                </div>

                <!-- 위도/경도 (지도에서 선택 또는 주소 검색으로 자동 입력) -->
                <input type="hidden" name="latitude" id="shopLat" value="<?= htmlspecialchars($_POST['latitude'] ?? '') ?>">
                <input type="hidden" name="longitude" id="shopLng" value="<?= htmlspecialchars($_POST['longitude'] ?? '') ?>">

                <div id="registerMap" class="w-full h-64 rounded-lg border border-zinc-200 dark:border-zinc-700 bg-zinc-100 dark:bg-zinc-900"></div>
                <p class="text-xs text-zinc-400"><?= __('shop.register.map_hint') ?? '지도를 클릭하여 매장 위치를 정확하게 지정할 수 있습니다.' ?></p>
            </div>
        </div>

        <!-- 사진 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('shop.register.photos') ?? '사진' ?></h2>

            <!-- 커버 이미지 -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('shop.register.cover_image') ?? '커버 이미지' ?></label>
                <div id="coverPreview" class="hidden mb-2 relative inline-block">
                    <img id="coverPreviewImg" class="h-32 rounded-lg object-cover">
                    <button type="button" onclick="removeCover()" class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full text-xs flex items-center justify-center hover:bg-red-600">&times;</button>
                </div>
                <label class="inline-flex items-center gap-2 px-4 py-2.5 border-2 border-dashed border-zinc-300 dark:border-zinc-600 rounded-lg cursor-pointer hover:border-blue-400 hover:bg-blue-50/50 dark:hover:bg-blue-900/20 transition text-sm text-zinc-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <span id="coverBtnText"><?= __('shop.register.cover_image') ?? '커버 이미지 선택' ?></span>
                    <input type="file" name="cover_image" accept="image/*" class="hidden" onchange="previewCover(this)">
                </label>
            </div>

            <!-- 매장 사진 (여러장) -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('shop.register.photos_label') ?? '매장 사진' ?> (<span id="photoCount">0</span>/10)</label>
                <div id="photosGrid" class="grid grid-cols-3 sm:grid-cols-4 md:grid-cols-5 gap-2 mb-3">
                    <!-- 미리보기가 여기에 추가됨 -->
                </div>
                <label id="addPhotoBtn" class="inline-flex items-center gap-2 px-4 py-2.5 border-2 border-dashed border-zinc-300 dark:border-zinc-600 rounded-lg cursor-pointer hover:border-blue-400 hover:bg-blue-50/50 dark:hover:bg-blue-900/20 transition text-sm text-zinc-500">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    <?= __('shop.register.add_photo') ?? '사진 추가' ?>
                    <input type="file" accept="image/*" multiple class="hidden" onchange="addPhotos(this)">
                </label>
                <p class="text-[10px] text-zinc-400 mt-1"><?= __('shop.register.photos_max') ?? '최대 10장' ?></p>
            </div>

            <!-- hidden file inputs (실제 전송용) -->
            <div id="photoFileInputs" class="hidden"></div>
        </div>

        <script>
        // 커버 이미지 미리보기
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
            var input = document.querySelector('input[name="cover_image"]');
            input.value = '';
        }

        // 매장 사진 추가
        var photoFiles = []; // DataTransfer로 관리
        var photosDT = new DataTransfer();

        function addPhotos(input) {
            var grid = document.getElementById('photosGrid');
            var files = input.files;
            for (var i = 0; i < files.length; i++) {
                if (photoFiles.length >= 10) break;
                var file = files[i];
                if (!file.type.startsWith('image/')) continue;
                photoFiles.push(file);
                photosDT.items.add(file);

                // 미리보기 생성
                var idx = photoFiles.length - 1;
                var div = document.createElement('div');
                div.className = 'relative aspect-square rounded-lg overflow-hidden bg-zinc-100 dark:bg-zinc-700 group';
                div.dataset.idx = idx;

                var img = document.createElement('img');
                img.className = 'w-full h-full object-cover';
                var reader = new FileReader();
                reader.onload = (function(imgEl) { return function(e) { imgEl.src = e.target.result; }; })(img);
                reader.readAsDataURL(file);

                var removeBtn = document.createElement('button');
                removeBtn.type = 'button';
                removeBtn.className = 'absolute top-1 right-1 w-5 h-5 bg-red-500 text-white rounded-full text-[10px] flex items-center justify-center opacity-0 group-hover:opacity-100 transition';
                removeBtn.innerHTML = '&times;';
                removeBtn.onclick = (function(index, el) { return function() { removePhoto(index, el); }; })(idx, div);

                div.appendChild(img);
                div.appendChild(removeBtn);
                grid.appendChild(div);
            }
            updatePhotoCount();
            syncPhotoInput();
            input.value = ''; // 같은 파일 재선택 가능
        }

        function removePhoto(idx, el) {
            el.remove();
            photoFiles[idx] = null;
            // DataTransfer 재구성
            photosDT = new DataTransfer();
            photoFiles.forEach(function(f) { if (f) photosDT.items.add(f); });
            photoFiles = photoFiles.filter(function(f) { return f !== null; });
            // 인덱스 재정렬
            document.querySelectorAll('#photosGrid > div').forEach(function(d, i) { d.dataset.idx = i; });
            updatePhotoCount();
            syncPhotoInput();
        }

        function updatePhotoCount() {
            var count = photoFiles.filter(function(f) { return f !== null; }).length;
            document.getElementById('photoCount').textContent = count;
            document.getElementById('addPhotoBtn').style.display = count >= 10 ? 'none' : '';
        }

        function syncPhotoInput() {
            var container = document.getElementById('photoFileInputs');
            container.innerHTML = '';
            var input = document.createElement('input');
            input.type = 'file';
            input.name = 'images[]';
            input.multiple = true;
            input.files = photosDT.files;
            container.appendChild(input);
        }
        </script>

        <!-- 영업시간 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('shop.register.business_hours') ?? '영업시간' ?></h2>

            <div class="space-y-2">
                <?php
                $days = ['mon' => __('common.days.mon') ?? '월', 'tue' => __('common.days.tue') ?? '화', 'wed' => __('common.days.wed') ?? '수', 'thu' => __('common.days.thu') ?? '목', 'fri' => __('common.days.fri') ?? '금', 'sat' => __('common.days.sat') ?? '토', 'sun' => __('common.days.sun') ?? '일'];
                foreach ($days as $dayKey => $dayLabel): ?>
                <div class="flex items-center gap-3">
                    <span class="w-8 text-sm font-medium text-zinc-600 dark:text-zinc-400"><?= $dayLabel ?></span>
                    <input type="time" name="hours_<?= $dayKey ?>_open" value="09:00" class="px-3 py-1.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-sm text-zinc-900 dark:text-white">
                    <span class="text-zinc-400">~</span>
                    <input type="time" name="hours_<?= $dayKey ?>_close" value="19:00" class="px-3 py-1.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-sm text-zinc-900 dark:text-white">
                    <label class="flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400">
                        <input type="checkbox" name="hours_<?= $dayKey ?>_closed" class="rounded border-zinc-300 dark:border-zinc-600 text-blue-600">
                        <?= __('shop.register.closed') ?? '휴무' ?>
                    </label>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 사업자 정보 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2"><?= __('shop.register.business_info') ?? '사업자 정보' ?></h2>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-4"><?= __('shop.register.business_info_hint') ?? '사업자등록증은 관리자 확인 후 비공개 처리됩니다. 일반 이용자에게 노출되지 않습니다.' ?></p>

            <div class="space-y-4">
                <!-- 개업 상황 -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('shop.register.opening_status') ?? '개업 상황' ?></label>
                    <div class="flex gap-4">
                        <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300 cursor-pointer">
                            <input type="radio" name="opening_status" value="opened" <?= ($_POST['opening_status'] ?? 'opened') === 'opened' ? 'checked' : '' ?>
                                   class="text-blue-600 border-zinc-300 dark:border-zinc-600">
                            <?= __('shop.register.status_opened') ?? '개업 완료' ?>
                        </label>
                        <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300 cursor-pointer">
                            <input type="radio" name="opening_status" value="planned" <?= ($_POST['opening_status'] ?? '') === 'planned' ? 'checked' : '' ?>
                                   class="text-blue-600 border-zinc-300 dark:border-zinc-600">
                            <?= __('shop.register.status_planned') ?? '개업 예정' ?>
                        </label>
                    </div>
                </div>

                <!-- 개업 시기 -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.register.opened_at') ?? '개업 시기' ?></label>
                        <div class="flex gap-2">
                            <select name="opened_at_year" class="flex-1 px-3 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm">
                                <option value=""><?= __('shop.register.year') ?? '연도' ?></option>
                                <?php for ($y = (int)date('Y') + 2; $y >= 2000; $y--): ?>
                                <option value="<?= $y ?>" <?= (int)($_POST['opened_at_year'] ?? '') === $y ? 'selected' : '' ?>><?= $y ?></option>
                                <?php endfor; ?>
                            </select>
                            <select name="opened_at_month" class="flex-1 px-3 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm">
                                <option value=""><?= __('shop.register.month') ?? '월' ?></option>
                                <?php for ($m = 1; $m <= 12; $m++): ?>
                                <option value="<?= $m ?>" <?= (int)($_POST['opened_at_month'] ?? '') === $m ? 'selected' : '' ?>><?= $m ?></option>
                                <?php endfor; ?>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.register.representative') ?? '대표자명' ?></label>
                        <input type="text" name="representative" value="<?= htmlspecialchars($_POST['representative'] ?? '') ?>"
                               class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.register.business_number') ?? '사업자등록번호' ?></label>
                        <input type="text" name="business_number" value="<?= htmlspecialchars($_POST['business_number'] ?? '') ?>"
                               class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                               placeholder="000-00-00000">
                    </div>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('shop.register.business_license') ?? '사업자등록증' ?></label>
                    <input type="file" name="business_license" accept="image/*,.pdf"
                           class="w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-amber-50 file:text-amber-600 hover:file:bg-amber-100 dark:file:bg-amber-900/30 dark:file:text-amber-400">
                    <p class="text-[10px] text-zinc-400 mt-1"><?= __('shop.register.business_license_hint') ?? '이미지(JPG, PNG) 또는 PDF 파일' ?>. <?= __('shop.register.business_license_later') ?? '지금 준비되지 않은 경우 등록 후 편집에서 추가할 수 있습니다.' ?></p>
                </div>
            </div>
        </div>

        <!-- SNS -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">SNS</h2>

            <div class="space-y-3">
                <div class="flex items-center gap-3">
                    <span class="text-sm w-24 text-zinc-600 dark:text-zinc-400">Instagram</span>
                    <input type="text" name="sns_instagram" value="<?= htmlspecialchars($_POST['sns_instagram'] ?? '') ?>"
                           class="flex-1 px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm" placeholder="@username">
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm w-24 text-zinc-600 dark:text-zinc-400">X (Twitter)</span>
                    <input type="text" name="sns_x" value="<?= htmlspecialchars($_POST['sns_x'] ?? '') ?>"
                           class="flex-1 px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm" placeholder="@username">
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm w-24 text-zinc-600 dark:text-zinc-400">Facebook</span>
                    <input type="text" name="sns_facebook" value="<?= htmlspecialchars($_POST['sns_facebook'] ?? '') ?>"
                           class="flex-1 px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm" placeholder="https://facebook.com/...">
                </div>
                <div class="flex items-center gap-3">
                    <span class="text-sm w-24 text-zinc-600 dark:text-zinc-400">LINE</span>
                    <input type="text" name="sns_line" value="<?= htmlspecialchars($_POST['sns_line'] ?? '') ?>"
                           class="flex-1 px-4 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm" placeholder="@line-id">
                </div>
            </div>
        </div>

        <!-- 특징 태그 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('shop.register.features') ?? '매장 특징' ?></h2>

            <div id="featureTagsArea">
                <?php foreach ($_allFeatures as $group => $tags):
                    $groupLabel = $_featuresRaw[$group] ?? $group;
                    // 공통 그룹의 data 속성
                    $groupClasses = ($group === '_common') ? '' : ' feature-group';
                    $groupData = ($group === '_common') ? '' : ' data-group="' . htmlspecialchars($group) . '"';
                ?>
                <div class="mb-3<?= $groupClasses ?>"<?= $groupData ?>>
                    <p class="text-xs font-medium text-zinc-400 dark:text-zinc-500 mb-1.5"><?= htmlspecialchars($groupLabel) ?></p>
                    <div class="flex flex-wrap gap-2">
                        <?php foreach ($tags as $key => $label): ?>
                        <label class="flex items-center gap-1.5 px-3 py-1.5 border border-zinc-200 dark:border-zinc-600 rounded-full text-sm text-zinc-600 dark:text-zinc-400 cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/20 has-[:checked]:bg-blue-100 has-[:checked]:border-blue-300 has-[:checked]:text-blue-700 dark:has-[:checked]:bg-blue-900/30 dark:has-[:checked]:text-blue-400 transition">
                            <input type="checkbox" name="features[]" value="<?= $key ?>" class="hidden">
                            <?= htmlspecialchars($label) ?>
                        </label>
                        <?php endforeach; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <script>
            // 업종 선택 시 해당 태그 그룹만 표시
            var featureCatMap = <?= json_encode($_featureCategoryMap) ?>;
            var catSlugMap = <?= json_encode($_catSlugMap) ?>;
            document.querySelector('[name=category_id]').addEventListener('change', function() {
                var catId = this.value;
                var catSlug = catSlugMap[catId] || '';
                var groups = featureCatMap[catSlug] || ['_common'];
                document.querySelectorAll('.feature-group').forEach(function(el) {
                    var g = el.dataset.group;
                    el.style.display = groups.indexOf(g) !== -1 ? '' : 'none';
                });
            });
            // 초기 실행
            document.querySelector('[name=category_id]').dispatchEvent(new Event('change'));
            </script>
        </div>

        <!-- 문의사항 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2"><?= __('shop.register.inquiry') ?? '문의/요청사항' ?></h2>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 mb-3"><?= __('shop.register.inquiry_hint') ?? '궁금한 점이 있으시면 자유롭게 작성해주세요. 담당자가 Q&A로 답변드립니다.' ?></p>
            <textarea name="inquiry_note" rows="3"
                      class="w-full px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500"
                      placeholder="<?= __('shop.register.inquiry_placeholder') ?? '문의사항을 입력해주세요.' ?>"><?= htmlspecialchars($_POST['inquiry_note'] ?? '') ?></textarea>
        </div>

        <!-- 제출 -->
        <div class="flex justify-end gap-3">
            <a href="<?= $baseUrl ?>/" class="px-6 py-3 text-sm font-medium text-zinc-600 dark:text-zinc-400 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition"><?= __('common.buttons.cancel') ?? '취소' ?></a>
            <button type="submit" class="px-6 py-3 text-sm font-medium bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"><?= __('shop.register.submit') ?? '매장 등록하기' ?></button>
        </div>
    </form>
</div>

<!-- Leaflet 지도 -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function() {
    var mapEl = document.getElementById('registerMap');
    if (!mapEl) return;
    var lat = parseFloat(document.getElementById('shopLat').value) || 35.6762;
    var lng = parseFloat(document.getElementById('shopLng').value) || 139.6503;
    var map = L.map(mapEl).setView([lat, lng], 14);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap',
        maxZoom: 19
    }).addTo(map);
    var marker = L.marker([lat, lng], {draggable: true}).addTo(map);

    function updateLatLng(ll) {
        document.getElementById('shopLat').value = ll.lat.toFixed(7);
        document.getElementById('shopLng').value = ll.lng.toFixed(7);
    }

    marker.on('dragend', function(e) { updateLatLng(e.target.getLatLng()); });
    map.on('click', function(e) { marker.setLatLng(e.latlng); updateLatLng(e.latlng); });

    // 현재 위치 자동 감지 (사용자가 마커를 이동하지 않은 경우에만)
    var userMoved = false;
    marker.on('dragend', function() { userMoved = true; });
    map.on('click', function() { userMoved = true; });

    if (navigator.geolocation && !document.getElementById('shopLat').value) {
        navigator.geolocation.getCurrentPosition(function(pos) {
            if (userMoved) return; // 사용자가 이미 마커를 이동한 경우 무시
            var ll = [pos.coords.latitude, pos.coords.longitude];
            map.setView(ll, 15);
            marker.setLatLng(ll);
            updateLatLng({lat: ll[0], lng: ll[1]});
        });
    }
})();
</script>
<?php endif; ?>
