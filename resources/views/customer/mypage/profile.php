<?php
/**
 * RezlyX Profile Page (View + Edit)
 * ?edit=1 → 수정 모드, 기본 → 보기 모드
 */

require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
use RzxLib\Core\Auth\Auth;

if (!Auth::check()) {
    header('Location: ' . ($config['app_url'] ?? '') . '/login');
    exit;
}

$user = Auth::user();
$baseUrl = $config['app_url'] ?? '';
$isLoggedIn = true;
$currentUser = $user;

$error = '';
$success = '';
$editMode = isset($_GET['edit']);

// PRG 패턴: 성공 메시지
if (isset($_GET['updated'])) {
    $success = __('auth.profile.success');
}

// DB 설정 로드
$registerFields = ['name', 'email', 'password', 'phone'];
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
    $stmt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = ?");
    $stmt->execute(['member_register_fields']);
    $regFieldsVal = $stmt->fetchColumn();
    if ($regFieldsVal) {
        $registerFields = explode(',', $regFieldsVal);
    }
} catch (PDOException $e) {
    error_log('Profile settings load error: ' . $e->getMessage());
}

// 프로필 이미지 업로드 처리
function handleProfileImageUpload(array $files, string $userId): ?string {
    if (empty($files['profile_image']) || $files['profile_image']['error'] !== UPLOAD_ERR_OK) {
        return null;
    }
    $file = $files['profile_image'];
    $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
    if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) return null;

    $uploadDir = BASE_PATH . '/storage/uploads/profiles/';
    if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);

    $fileName = $userId . '_' . time() . '.' . $ext;
    if (move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
        return '/storage/uploads/profiles/' . $fileName;
    }
    return null;
}

// POST 처리 (수정 모드에서만)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $editMode = true;
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($name)) {
        $error = __('validation.required', ['attribute' => __('auth.profile.name')]);
    } else {
        $updateData = ['name' => $name, 'phone' => $phone];

        $profileImage = handleProfileImageUpload($_FILES, $user['id']);
        if ($profileImage) {
            $updateData['profile_image'] = $profileImage;
        }

        if (in_array('birth_date', $registerFields)) {
            $updateData['birth_date'] = trim($_POST['birth_date'] ?? '') ?: null;
        }
        if (in_array('gender', $registerFields)) {
            $g = $_POST['gender'] ?? '';
            $updateData['gender'] = in_array($g, ['male', 'female', 'other']) ? $g : null;
        }
        if (in_array('company', $registerFields)) {
            $updateData['company'] = trim($_POST['company'] ?? '') ?: null;
        }
        if (in_array('blog', $registerFields)) {
            $updateData['blog'] = trim($_POST['blog'] ?? '') ?: null;
        }

        $result = Auth::updateProfile($user['id'], $updateData);

        if ($result['success']) {
            header('Location: ' . $baseUrl . '/mypage/profile?updated=1');
            exit;
        } else {
            $error = __('auth.profile.error');
        }
    }
}

$pageTitle = ($config['app_name'] ?? 'RezlyX') . ' - ' . __('auth.profile.title');

// 전화번호 파싱
$phoneRaw = $user['phone'] ?? '';
$phoneCountry = '+82';
$phoneNumber = '';
if ($phoneRaw) {
    $codes = ['+886','+880','+856','+855','+852','+998','+977','+976','+975','+974','+973','+971','+968','+967','+966','+965','+964','+963','+962','+961','+960',
        '+98','+95','+94','+93','+92','+91','+90','+86','+84','+82','+81','+66','+65','+63','+62','+61','+60',
        '+58','+57','+56','+55','+54','+53','+52','+51','+49','+48','+47','+46','+45','+44','+43','+41',
        '+40','+39','+36','+34','+33','+32','+31','+30','+27','+20','+7','+1'];
    foreach ($codes as $c) {
        if (str_starts_with($phoneRaw, $c)) {
            $phoneCountry = $c;
            $phoneNumber = substr($phoneRaw, strlen($c));
            break;
        }
    }
    if ($phoneNumber === '' && !str_starts_with($phoneRaw, '+')) {
        $phoneNumber = $phoneRaw;
    }
}

// 프로필 이미지 URL
$profileImgUrl = '';
if (!empty($user['profile_image'])) {
    $profileImgUrl = str_starts_with($user['profile_image'], 'http')
        ? $user['profile_image']
        : $baseUrl . $user['profile_image'];
}

// 성별 라벨
$genderLabels = [
    'male' => __('members.list.gender_male'),
    'female' => __('members.list.gender_female'),
    'other' => __('members.list.gender_other'),
];

$notSet = '<span class="text-zinc-400 dark:text-zinc-500">' . __('auth.profile.not_set') . '</span>';
$inputClass = 'w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-sm';
$labelClass = 'block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1';

// headExtra에 cropper 추가
if ($editMode && in_array('profile_photo', $registerFields)) {
    $headExtra = '<link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">'
        . '<script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>'
        . '<style>.cropper-view-box, .cropper-face { border-radius: 50%; }</style>';
}

// 기본 레이아웃 헤더
?>

    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="lg:flex lg:gap-8">
            <!-- 사이드바 -->
            <?php $sidebarActive = 'profile'; include BASE_PATH . '/resources/views/components/mypage-sidebar.php'; ?>

            <!-- 메인 콘텐츠 -->
            <div class="flex-1">
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-6">

                <?php if (!$editMode): ?>
                    <!-- ═══ VIEW 모드 ═══ -->
                    <div class="flex items-center justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= __('auth.profile.title') ?></h1>
                            <p class="text-gray-500 dark:text-zinc-400 mt-1"><?= __('auth.profile.description') ?></p>
                        </div>
                        <a href="<?= $baseUrl ?>/mypage/profile?edit=1"
                           class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                            <?= __('auth.profile.edit_button') ?>
                        </a>
                    </div>

                    <?php if ($success): ?>
                    <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg">
                        <span class="text-green-700 dark:text-green-300 text-sm"><?= htmlspecialchars($success) ?></span>
                    </div>
                    <?php endif; ?>

                    <!-- 프로필 카드 -->
                    <div class="flex items-center gap-5 mb-8">
                        <div class="w-24 h-24 rounded-full overflow-hidden flex items-center justify-center flex-shrink-0 <?= $profileImgUrl ? '' : 'bg-gradient-to-br from-blue-500 to-purple-600' ?>">
                            <?php if ($profileImgUrl): ?>
                                <img src="<?= htmlspecialchars($profileImgUrl) ?>" alt="" class="w-full h-full object-cover">
                            <?php else: ?>
                                <span class="text-3xl font-bold text-white"><?= mb_substr($user['name'] ?? 'U', 0, 1) ?></span>
                            <?php endif; ?>
                        </div>
                        <div>
                            <h2 class="text-xl font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($user['name'] ?? '') ?></h2>
                            <p class="text-gray-500 dark:text-zinc-400"><?= htmlspecialchars($user['email'] ?? '') ?></p>
                        </div>
                    </div>

                    <!-- 상세 정보 -->
                    <div class="border-t border-zinc-200 dark:border-zinc-700 divide-y divide-zinc-200 dark:divide-zinc-700">
                        <?php if (in_array('phone', $registerFields)): ?>
                        <div class="py-4 flex items-center">
                            <span class="w-32 text-sm font-medium text-gray-500 dark:text-zinc-400 flex-shrink-0"><?= __('auth.profile.phone') ?></span>
                            <span class="text-sm text-gray-900 dark:text-white" id="viewPhoneDisplay"><?= $phoneRaw ? htmlspecialchars($phoneRaw) : $notSet ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if (in_array('birth_date', $registerFields)): ?>
                        <div class="py-4 flex items-center">
                            <span class="w-32 text-sm font-medium text-gray-500 dark:text-zinc-400 flex-shrink-0"><?= __('members.settings.register.fields.birth_date') ?></span>
                            <span class="text-sm text-gray-900 dark:text-white"><?= !empty($user['birth_date']) ? htmlspecialchars($user['birth_date']) : $notSet ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if (in_array('gender', $registerFields)): ?>
                        <div class="py-4 flex items-center">
                            <span class="w-32 text-sm font-medium text-gray-500 dark:text-zinc-400 flex-shrink-0"><?= __('members.settings.register.fields.gender') ?></span>
                            <span class="text-sm text-gray-900 dark:text-white"><?= !empty($user['gender']) ? ($genderLabels[$user['gender']] ?? $user['gender']) : $notSet ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if (in_array('company', $registerFields)): ?>
                        <div class="py-4 flex items-center">
                            <span class="w-32 text-sm font-medium text-gray-500 dark:text-zinc-400 flex-shrink-0"><?= __('members.settings.register.fields.company') ?></span>
                            <span class="text-sm text-gray-900 dark:text-white"><?= !empty($user['company']) ? htmlspecialchars($user['company']) : $notSet ?></span>
                        </div>
                        <?php endif; ?>

                        <?php if (in_array('blog', $registerFields)): ?>
                        <div class="py-4 flex items-center">
                            <span class="w-32 text-sm font-medium text-gray-500 dark:text-zinc-400 flex-shrink-0"><?= __('members.settings.register.fields.blog') ?></span>
                            <span class="text-sm text-gray-900 dark:text-white">
                                <?php if (!empty($user['blog'])): ?>
                                    <a href="<?= htmlspecialchars($user['blog']) ?>" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline"><?= htmlspecialchars($user['blog']) ?></a>
                                <?php else: ?>
                                    <?= $notSet ?>
                                <?php endif; ?>
                            </span>
                        </div>
                        <?php endif; ?>
                    </div>

                <?php else: ?>
                    <!-- ═══ EDIT 모드 ═══ -->
                    <div class="mb-6">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= __('auth.profile.edit_title') ?></h1>
                        <p class="text-gray-500 dark:text-zinc-400 mt-1"><?= __('auth.profile.edit_description') ?></p>
                    </div>

                    <?php if ($error): ?>
                    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg">
                        <span class="text-red-700 dark:text-red-300 text-sm"><?= htmlspecialchars($error) ?></span>
                    </div>
                    <?php endif; ?>

                    <form id="profileForm" method="POST" enctype="multipart/form-data" class="space-y-5">

                        <!-- 프로필 사진 -->
                        <?php if (in_array('profile_photo', $registerFields)): ?>
                        <div class="flex items-center gap-4">
                            <div class="relative">
                                <div id="profileAvatarPreview" class="w-20 h-20 rounded-full overflow-hidden flex items-center justify-center <?= $profileImgUrl ? '' : 'bg-zinc-200 dark:bg-zinc-600' ?>">
                                    <?php if ($profileImgUrl): ?>
                                        <img src="<?= htmlspecialchars($profileImgUrl) ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <svg class="w-10 h-10 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <div class="flex-1">
                                <label class="<?= $labelClass ?>"><?= __('members.settings.register.fields.profile_photo') ?></label>
                                <input type="file" id="profileImageInput" name="profile_image" accept="image/jpeg,image/png,image/webp"
                                       class="text-sm text-zinc-600 dark:text-zinc-400 file:mr-2 file:px-3 file:py-1.5 file:rounded-lg file:border-0 file:text-sm file:bg-blue-50 dark:file:bg-blue-900/30 file:text-blue-600 dark:file:text-blue-400 file:cursor-pointer"
                                       onchange="openProfileCropper(this)">
                                <p class="text-xs text-zinc-400 mt-1">JPG, PNG, WebP (400x400)</p>
                            </div>
                        </div>
                        <?php endif; ?>

                        <!-- 이름 -->
                        <div>
                            <label for="name" class="<?= $labelClass ?>">
                                <?= __('auth.profile.name') ?> <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" id="name"
                                   value="<?= htmlspecialchars($user['name'] ?? '') ?>"
                                   class="<?= $inputClass ?>" required>
                        </div>

                        <!-- 이메일 (읽기전용) -->
                        <div>
                            <label for="email" class="<?= $labelClass ?>"><?= __('auth.profile.email') ?></label>
                            <input type="email" id="email"
                                   value="<?= htmlspecialchars($user['email'] ?? '') ?>"
                                   class="w-full px-4 py-3 border border-gray-200 dark:border-zinc-700 bg-gray-100 dark:bg-zinc-800 text-gray-500 dark:text-zinc-400 rounded-lg cursor-not-allowed text-sm"
                                   disabled readonly>
                            <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1"><?= __('auth.profile.email_hint') ?></p>
                        </div>

                        <!-- 전화번호 -->
                        <?php if (in_array('phone', $registerFields)): ?>
                        <div>
                            <?php
                            $phoneInputConfig = [
                                'name' => 'phone',
                                'id' => 'profilePhone',
                                'label' => __('auth.profile.phone'),
                                'value' => $phoneRaw,
                                'country_code' => $phoneCountry,
                                'phone_number' => $phoneNumber,
                                'required' => false,
                                'show_label' => true,
                                'placeholder' => '010-1234-5678',
                            ];
                            include BASE_PATH . '/resources/views/components/phone-input.php';
                            ?>
                        </div>
                        <?php endif; ?>

                        <!-- 생년월일 -->
                        <?php if (in_array('birth_date', $registerFields)): ?>
                        <div>
                            <label for="birth_date" class="<?= $labelClass ?>"><?= __('members.settings.register.fields.birth_date') ?></label>
                            <input type="date" name="birth_date" id="birth_date"
                                   value="<?= htmlspecialchars($user['birth_date'] ?? '') ?>"
                                   class="<?= $inputClass ?>">
                        </div>
                        <?php endif; ?>

                        <!-- 성별 + 회사 -->
                        <?php if (in_array('gender', $registerFields) || in_array('company', $registerFields)): ?>
                        <div class="grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <?php if (in_array('gender', $registerFields)): ?>
                            <div>
                                <label for="gender" class="<?= $labelClass ?>"><?= __('members.settings.register.fields.gender') ?></label>
                                <select name="gender" id="gender" class="<?= $inputClass ?>">
                                    <option value="">-</option>
                                    <option value="male" <?= ($user['gender'] ?? '') === 'male' ? 'selected' : '' ?>><?= __('members.list.gender_male') ?></option>
                                    <option value="female" <?= ($user['gender'] ?? '') === 'female' ? 'selected' : '' ?>><?= __('members.list.gender_female') ?></option>
                                    <option value="other" <?= ($user['gender'] ?? '') === 'other' ? 'selected' : '' ?>><?= __('members.list.gender_other') ?></option>
                                </select>
                            </div>
                            <?php endif; ?>
                            <?php if (in_array('company', $registerFields)): ?>
                            <div>
                                <label for="company" class="<?= $labelClass ?>"><?= __('members.settings.register.fields.company') ?></label>
                                <input type="text" name="company" id="company"
                                       value="<?= htmlspecialchars($user['company'] ?? '') ?>"
                                       class="<?= $inputClass ?>">
                            </div>
                            <?php endif; ?>
                        </div>
                        <?php endif; ?>

                        <!-- 블로그 -->
                        <?php if (in_array('blog', $registerFields)): ?>
                        <div>
                            <label for="blog" class="<?= $labelClass ?>"><?= __('members.settings.register.fields.blog') ?></label>
                            <input type="url" name="blog" id="blog"
                                   value="<?= htmlspecialchars($user['blog'] ?? '') ?>"
                                   class="<?= $inputClass ?>" placeholder="https://">
                        </div>
                        <?php endif; ?>

                        <!-- 버튼 -->
                        <div class="flex items-center gap-3 pt-4">
                            <button type="button" onclick="submitProfile()" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition shadow-lg shadow-blue-500/30">
                                <?= __('auth.profile.submit') ?>
                            </button>
                            <a href="<?= $baseUrl ?>/mypage/profile" class="px-6 py-3 bg-gray-200 dark:bg-zinc-700 hover:bg-gray-300 dark:hover:bg-zinc-600 text-gray-700 dark:text-zinc-300 font-semibold rounded-lg transition">
                                <?= __('common.buttons.cancel') ?>
                            </a>
                        </div>
                    </form>
                <?php endif; ?>

                </div>
            </div>
        </div>
    </div>

    <?php if ($editMode): ?>
    <!-- Cropper 모달 -->
    <?php if (in_array('profile_photo', $registerFields)): ?>
    <div id="profileCropperModal" class="fixed inset-0 z-[60] hidden">
        <div class="fixed inset-0 bg-black/70 backdrop-blur-sm"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl w-full max-w-md">
                <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                    <h3 class="text-base font-semibold text-zinc-900 dark:text-white"><?= __('staff.photo_editor.title') ?></h3>
                    <button onclick="closeProfileCropper()" class="p-1.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div class="p-4">
                    <div style="height:340px;" class="bg-zinc-100 dark:bg-zinc-900 rounded-lg overflow-hidden">
                        <img id="profileCropperImage" src="" class="max-w-full">
                    </div>
                    <div class="flex items-center justify-center gap-2 mt-3">
                        <button type="button" onclick="profileCropAction('zoom', 0.1)" class="p-2 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg" title="<?= __('staff.photo_editor.zoom_in') ?>">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM10 7v6m3-3H7"/></svg>
                        </button>
                        <button type="button" onclick="profileCropAction('zoom', -0.1)" class="p-2 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg" title="<?= __('staff.photo_editor.zoom_out') ?>">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0zM13 10H7"/></svg>
                        </button>
                        <button type="button" onclick="profileCropAction('rotate', -90)" class="p-2 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg" title="<?= __('staff.photo_editor.rotate_left') ?>">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h10a4 4 0 014 4v2M3 10l4-4M3 10l4 4"/></svg>
                        </button>
                        <button type="button" onclick="profileCropAction('rotate', 90)" class="p-2 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg" title="<?= __('staff.photo_editor.rotate_right') ?>">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 10H11a4 4 0 00-4 4v2M21 10l-4-4M21 10l-4 4"/></svg>
                        </button>
                        <button type="button" onclick="profileCropAction('reset')" class="p-2 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg" title="<?= __('staff.photo_editor.reset') ?>">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h5M20 20v-5h-5M4 9a9 9 0 0114.32-4.32M20 15a9 9 0 01-14.32 4.32"/></svg>
                        </button>
                    </div>
                </div>
                <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex justify-end gap-3">
                    <button onclick="closeProfileCropper()" class="px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg"><?= __('common.buttons.cancel') ?></button>
                    <button onclick="applyProfileCrop()" class="px-4 py-2 text-sm text-white bg-blue-600 hover:bg-blue-700 rounded-lg"><?= __('staff.photo_editor.apply') ?></button>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (in_array('phone', $registerFields)): ?>
    <script src="<?= htmlspecialchars($baseUrl) ?>/assets/js/phone-input.js"></script>
    <?php endif; ?>

    <script>
    (function() {
        'use strict';

        var profileCropper = null;
        var profileCroppedBlob = null;

        function setAvatarPreview(src) {
            var el = document.getElementById('profileAvatarPreview');
            if (!el) return;
            if (src) {
                el.innerHTML = '<img src="' + src + '" class="w-full h-full object-cover">';
                el.className = 'w-20 h-20 rounded-full overflow-hidden flex items-center justify-center';
            } else {
                el.innerHTML = '<svg class="w-10 h-10 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>';
                el.className = 'w-20 h-20 rounded-full overflow-hidden flex items-center justify-center bg-zinc-200 dark:bg-zinc-600';
            }
            console.log('[Profile] Avatar preview updated');
        }

        window.submitProfile = function() {
            var name = document.getElementById('name').value.trim();
            if (!name) { document.getElementById('name').focus(); return; }

            var form = document.getElementById('profileForm');
            var formData = new FormData(form);

            if (typeof PhoneInput !== 'undefined') {
                var phoneVal = PhoneInput.getValue('profilePhone');
                console.log('[Profile] Phone value:', phoneVal);
                if (phoneVal && phoneVal.fullNumber) {
                    formData.set('phone', phoneVal.fullNumber);
                }
            }

            if (profileCroppedBlob) {
                formData.delete('profile_image');
                formData.set('profile_image', profileCroppedBlob, 'profile.jpg');
                console.log('[Profile] Cropped image attached, size:', profileCroppedBlob.size);
            }

            console.log('[Profile] Submitting form...');

            fetch(window.location.pathname, {
                method: 'POST',
                body: formData,
                redirect: 'follow'
            }).then(function(r) {
                window.location.href = r.url || (window.location.pathname + '?updated=1');
            }).catch(function(err) {
                console.error('[Profile] Submit error:', err);
                alert('<?= __('auth.profile.error') ?>');
            });
        };

        window.openProfileCropper = function(input) {
            if (!input.files || !input.files[0]) return;
            var reader = new FileReader();
            reader.onload = function(e) {
                var img = document.getElementById('profileCropperImage');
                if (!img) return;
                img.src = e.target.result;
                document.getElementById('profileCropperModal').classList.remove('hidden');
                if (profileCropper) profileCropper.destroy();
                profileCropper = new Cropper(img, {
                    aspectRatio: 1, viewMode: 1, dragMode: 'move',
                    autoCropArea: 0.9, cropBoxResizable: true, background: false,
                });
                console.log('[Profile] Cropper opened');
            };
            reader.readAsDataURL(input.files[0]);
        };

        window.closeProfileCropper = function() {
            document.getElementById('profileCropperModal').classList.add('hidden');
            if (profileCropper) { profileCropper.destroy(); profileCropper = null; }
            var fi = document.getElementById('profileImageInput');
            if (fi) fi.value = '';
            console.log('[Profile] Cropper closed');
        };

        window.profileCropAction = function(action, value) {
            if (!profileCropper) return;
            if (action === 'zoom') profileCropper.zoom(value);
            else if (action === 'rotate') profileCropper.rotate(value);
            else if (action === 'reset') profileCropper.reset();
            console.log('[Profile] Cropper action:', action, value);
        };

        window.applyProfileCrop = function() {
            if (!profileCropper) return;
            var canvas = profileCropper.getCroppedCanvas({ width: 400, height: 400 });
            canvas.toBlob(function(blob) {
                profileCroppedBlob = blob;
                setAvatarPreview(canvas.toDataURL('image/jpeg', 0.9));
                document.getElementById('profileCropperModal').classList.add('hidden');
                profileCropper.destroy();
                profileCropper = null;
                console.log('[Profile] Crop applied, size:', blob.size);
            }, 'image/jpeg', 0.9);
        };

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                var cm = document.getElementById('profileCropperModal');
                if (cm && !cm.classList.contains('hidden')) closeProfileCropper();
            }
        });

        console.log('[Profile] Edit mode initialized');
    })();
    </script>
    <?php else: ?>
    <!-- View 모드: 전화번호 포맷 표시 -->
    <?php if (in_array('phone', $registerFields) && $phoneRaw): ?>
    <script src="<?= htmlspecialchars($baseUrl) ?>/assets/js/phone-input.js"></script>
    <script>
    (function() {
        var el = document.getElementById('viewPhoneDisplay');
        if (el && typeof PhoneInput !== 'undefined') {
            var formatted = PhoneInput.formatPhoneNumber('<?= addslashes($phoneNumber) ?>', '<?= addslashes($phoneCountry) ?>');
            if (formatted) {
                el.textContent = '<?= addslashes($phoneCountry) ?> ' + formatted;
            }
        }
        console.log('[Profile] View mode initialized');
    })();
    </script>
    <?php endif; ?>
    <?php endif; ?>

<?php
?>
