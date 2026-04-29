<?php
/**
 * RezlyX - 동적 회원가입 필드 렌더링 컴포넌트
 *
 * 관리자 설정에서 활성화된 필드만 렌더링합니다.
 * 새 필드가 추가되어도 자동으로 지원됩니다.
 *
 * 사용 변수:
 * - $registerFields: 활성화된 필드 배열 (예: ['name', 'email', 'password', 'phone'])
 * - $translations: 번역 데이터
 * - $oldInput: 이전 입력값
 * - $baseUrl: 기본 URL
 *
 * 지원 필드:
 * - name: 이름 (필수)
 * - email: 이메일 (필수)
 * - password: 비밀번호 + 확인 (필수)
 * - phone: 전화번호 (선택)
 * - birth_date: 생년월일 (선택)
 * - gender: 성별 (선택)
 * - company: 회사/소속 (선택)
 * - blog: 블로그/웹사이트 (선택)
 * - profile_photo: 프로필 사진 (선택)
 */

// 기본값 설정
$registerFields = $registerFields ?? ['name', 'email', 'password'];
$translations = $translations ?? [];
$oldInput = $oldInput ?? [];
$baseUrl = $baseUrl ?? '';

// 현재 로케일 감지
$_currentLocale = 'ko';
if (function_exists('current_locale')) {
    $_currentLocale = current_locale();
} elseif (!empty($_COOKIE['locale'])) {
    $_currentLocale = $_COOKIE['locale'];
} elseif (!empty($_GET['lang'])) {
    $_currentLocale = $_GET['lang'];
}
if (!in_array($_currentLocale, ['ko', 'en', 'ja'])) {
    $_currentLocale = 'ko';
}

// 로케일별 기본 번역 (translations가 비어있을 때 사용)
$_defaultTranslations = [
    'ko' => [
        'name' => '이름', 'name_placeholder' => '홍길동',
        'email' => '이메일', 'email_placeholder' => 'example@email.com',
        'password' => '비밀번호', 'password_placeholder' => '8자 이상 입력하세요',
        'password_hint' => '영문, 숫자를 포함하여 8자 이상',
        'password_confirm' => '비밀번호 확인', 'password_confirm_placeholder' => '비밀번호를 다시 입력하세요',
        'phone' => '전화번호', 'phone_placeholder' => '010-1234-5678',
        'birth_date' => '생년월일',
        'gender' => '성별', 'gender_male' => '남성', 'gender_female' => '여성', 'gender_other' => '기타',
        'company' => '회사/소속', 'company_placeholder' => '회사명을 입력하세요',
        'blog' => '블로그/웹사이트', 'blog_placeholder' => 'https://example.com',
        'profile_photo' => '프로필 사진', 'profile_photo_hint' => '최대 5MB, JPG/PNG/GIF/WebP',
        'select_photo' => '사진 선택', 'change_photo' => '사진 변경',
        'password_mismatch' => '비밀번호가 일치하지 않습니다.',
        // 이미지 크로퍼
        'edit_image' => '이미지 편집', 'select_image' => '이미지 선택',
        'drag_drop_image' => '또는 이미지를 여기에 드래그하세요',
        'zoom_in' => '확대', 'zoom_out' => '축소',
        'rotate_left' => '왼쪽 회전', 'rotate_right' => '오른쪽 회전',
        'reset' => '초기화', 'cancel' => '취소', 'apply' => '적용',
        'file_too_large' => '파일 크기가 너무 큽니다. 최대 5MB까지 업로드 가능합니다.',
        'invalid_file_type' => '지원하지 않는 파일 형식입니다. JPG, PNG, GIF, WebP만 가능합니다.',
    ],
    'en' => [
        'name' => 'Name', 'name_placeholder' => 'John Doe',
        'email' => 'Email', 'email_placeholder' => 'example@email.com',
        'password' => 'Password', 'password_placeholder' => 'At least 8 characters',
        'password_hint' => 'At least 8 characters with letters and numbers',
        'password_confirm' => 'Confirm Password', 'password_confirm_placeholder' => 'Re-enter your password',
        'phone' => 'Phone', 'phone_placeholder' => '010-1234-5678',
        'birth_date' => 'Date of Birth',
        'gender' => 'Gender', 'gender_male' => 'Male', 'gender_female' => 'Female', 'gender_other' => 'Other',
        'company' => 'Company', 'company_placeholder' => 'Enter company name',
        'blog' => 'Blog/Website', 'blog_placeholder' => 'https://example.com',
        'profile_photo' => 'Profile Photo', 'profile_photo_hint' => 'Max 5MB, JPG/PNG/GIF/WebP',
        'select_photo' => 'Select Photo', 'change_photo' => 'Change Photo',
        'password_mismatch' => 'Passwords do not match.',
        // Image Cropper
        'edit_image' => 'Edit Image', 'select_image' => 'Select Image',
        'drag_drop_image' => 'Or drag and drop an image here',
        'zoom_in' => 'Zoom In', 'zoom_out' => 'Zoom Out',
        'rotate_left' => 'Rotate Left', 'rotate_right' => 'Rotate Right',
        'reset' => 'Reset', 'cancel' => 'Cancel', 'apply' => 'Apply',
        'file_too_large' => 'File is too large. Maximum 5MB allowed.',
        'invalid_file_type' => 'Unsupported file type. JPG, PNG, GIF, WebP only.',
    ],
    'ja' => [
        'name' => '名前', 'name_placeholder' => '山田太郎',
        'email' => 'メール', 'email_placeholder' => 'example@email.com',
        'password' => 'パスワード', 'password_placeholder' => '8文字以上',
        'password_hint' => '英数字を含む8文字以上',
        'password_confirm' => 'パスワード確認', 'password_confirm_placeholder' => 'パスワードを再入力',
        'phone' => '電話番号', 'phone_placeholder' => '090-1234-5678',
        'birth_date' => '生年月日',
        'gender' => '性別', 'gender_male' => '男性', 'gender_female' => '女性', 'gender_other' => 'その他',
        'company' => '会社/所属', 'company_placeholder' => '会社名を入力',
        'blog' => 'ブログ/ウェブサイト', 'blog_placeholder' => 'https://example.com',
        'profile_photo' => 'プロフィール写真', 'profile_photo_hint' => '最大5MB、JPG/PNG/GIF/WebP',
        'select_photo' => '写真を選択', 'change_photo' => '写真を変更',
        'password_mismatch' => 'パスワードが一致しません。',
        // 画像クロッパー
        'edit_image' => '画像を編集', 'select_image' => '画像を選択',
        'drag_drop_image' => 'または画像をここにドラッグ＆ドロップ',
        'zoom_in' => '拡大', 'zoom_out' => '縮小',
        'rotate_left' => '左回転', 'rotate_right' => '右回転',
        'reset' => 'リセット', 'cancel' => 'キャンセル', 'apply' => '適用',
        'file_too_large' => 'ファイルサイズが大きすぎます。最大5MBまでアップロード可能です。',
        'invalid_file_type' => 'サポートされていないファイル形式です。JPG、PNG、GIF、WebPのみ対応しています。',
    ],
];

// 번역 헬퍼 함수 (전역 접근 가능하도록)
$_t = function($key) use ($translations, $_defaultTranslations, $_currentLocale) {
    return $translations[$key] ?? $_defaultTranslations[$_currentLocale][$key] ?? $_defaultTranslations['ko'][$key] ?? $key;
};
$GLOBALS['_t'] = $_t;

// 필수 필드 (항상 포함)
$requiredFields = ['name', 'email', 'password'];

// 필드 정의 (확장 가능)
$fieldDefinitions = [
    'name' => [
        'type' => 'text',
        'label' => $_t('name'),
        'placeholder' => $_t('name_placeholder'),
        'required' => true,
        'autocomplete' => 'name',
    ],
    'email' => [
        'type' => 'email',
        'label' => $_t('email'),
        'placeholder' => $_t('email_placeholder'),
        'required' => true,
        'autocomplete' => 'email',
    ],
    'password' => [
        'type' => 'password',
        'label' => $_t('password'),
        'placeholder' => $_t('password_placeholder'),
        'required' => true,
        'hint' => $_t('password_hint'),
        'has_confirm' => true,
        'confirm_label' => $_t('password_confirm'),
        'confirm_placeholder' => $_t('password_confirm_placeholder'),
    ],
    'phone' => [
        'type' => 'phone',
        'label' => $_t('phone'),
        'placeholder' => $_t('phone_placeholder'),
        'required' => false,
        'hint' => $translations['phone_hint'] ?? '',
    ],
    'birth_date' => [
        'type' => 'date',
        'label' => $_t('birth_date'),
        'placeholder' => '',
        'required' => false,
    ],
    'gender' => [
        'type' => 'radio',
        'label' => $_t('gender'),
        'required' => false,
        'options' => [
            'male' => $_t('gender_male'),
            'female' => $_t('gender_female'),
            'other' => $_t('gender_other'),
        ],
    ],
    'company' => [
        'type' => 'text',
        'label' => $_t('company'),
        'placeholder' => $_t('company_placeholder'),
        'required' => false,
        'autocomplete' => 'organization',
    ],
    'blog' => [
        'type' => 'url',
        'label' => $_t('blog'),
        'placeholder' => $_t('blog_placeholder'),
        'required' => false,
        'autocomplete' => 'url',
    ],
    'profile_photo' => [
        'type' => 'file',
        'label' => $_t('profile_photo'),
        'required' => false,
        'accept' => 'image/*',
        'hint' => $_t('profile_photo_hint'),
    ],
];

// 공통 입력 필드 클래스
$inputClass = 'appearance-none relative block w-full px-3 py-3 border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 placeholder-gray-500 dark:placeholder-zinc-400 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-all duration-200';

/**
 * 필드 렌더링 함수
 */
function renderRegisterField($fieldName, $fieldDef, $oldInput, $inputClass, $baseUrl) {
    // 전역 번역 함수 가져오기 (null 체크 강화)
    $_t = (isset($GLOBALS['_t']) && is_callable($GLOBALS['_t']))
        ? $GLOBALS['_t']
        : function($key) { return $key; };

    $value = $oldInput[$fieldName] ?? '';
    $required = $fieldDef['required'] ?? false;
    $requiredMark = $required ? '<span class="text-red-500">*</span>' : '';

    echo '<div class="register-field register-field-' . htmlspecialchars($fieldName) . '">';

    switch ($fieldDef['type']) {
        case 'text':
        case 'email':
        case 'url':
            echo '<label for="' . $fieldName . '" class="block text-sm font-medium mb-1 text-gray-700 dark:text-zinc-300">';
            echo htmlspecialchars($fieldDef['label']) . ' ' . $requiredMark;
            echo '</label>';
            echo '<input id="' . $fieldName . '" name="' . $fieldName . '" type="' . $fieldDef['type'] . '"';
            if ($required) echo ' required';
            if (!empty($fieldDef['autocomplete'])) echo ' autocomplete="' . $fieldDef['autocomplete'] . '"';
            echo ' class="' . $inputClass . '"';
            echo ' placeholder="' . htmlspecialchars($fieldDef['placeholder'] ?? '') . '"';
            echo ' value="' . htmlspecialchars($value) . '">';
            break;

        case 'password':
            // 비밀번호 필드
            echo '<label for="password" class="block text-sm font-medium mb-1 text-gray-700 dark:text-zinc-300">';
            echo htmlspecialchars($fieldDef['label']) . ' ' . $requiredMark;
            echo '</label>';
            echo '<div class="relative">';
            echo '<input id="password" name="password" type="password" required';
            echo ' class="' . $inputClass . ' pr-12"';
            echo ' placeholder="' . htmlspecialchars($fieldDef['placeholder'] ?? '') . '">';
            echo '<button type="button" id="togglePassword" class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 dark:text-zinc-400 hover:text-gray-600 dark:hover:text-zinc-200">';
            echo '<svg id="eyeIcon" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">';
            echo '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />';
            echo '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />';
            echo '</svg>';
            echo '<svg id="eyeOffIcon" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">';
            echo '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>';
            echo '</svg>';
            echo '</button>';
            echo '</div>';
            if (!empty($fieldDef['hint'])) {
                echo '<p class="mt-1 text-xs text-gray-500 dark:text-zinc-400">' . htmlspecialchars($fieldDef['hint']) . '</p>';
            }
            echo '</div>';

            // 비밀번호 확인 필드
            if (!empty($fieldDef['has_confirm'])) {
                echo '<div class="register-field register-field-password_confirm">';
                echo '<label for="password_confirm" class="block text-sm font-medium mb-1 text-gray-700 dark:text-zinc-300">';
                echo htmlspecialchars($fieldDef['confirm_label']) . ' <span class="text-red-500">*</span>';
                echo '</label>';
                echo '<input id="password_confirm" name="password_confirm" type="password" required';
                echo ' class="' . $inputClass . '"';
                echo ' placeholder="' . htmlspecialchars($fieldDef['confirm_placeholder'] ?? '') . '">';
            }
            break;

        case 'phone':
            // 전화번호 컴포넌트 사용
            $phoneInputConfig = [
                'name' => 'phone',
                'id' => 'phone',
                'label' => $fieldDef['label'],
                'value' => $oldInput['phone'] ?? '',
                'country_code' => $oldInput['phone_country'] ?? '+82',
                'phone_number' => $oldInput['phone_number'] ?? '',
                'required' => $required,
                'hint' => $fieldDef['hint'] ?? '',
                'placeholder' => $fieldDef['placeholder'] ?? '010-1234-5678',
                'show_label' => true,
            ];
            $phoneComponentPath = defined('BASE_PATH')
                ? BASE_PATH . '/resources/views/components/phone-input.php'
                : dirname(__DIR__, 3) . '/resources/views/components/phone-input.php';
            if (file_exists($phoneComponentPath)) {
                include $phoneComponentPath;
            }
            return; // 컴포넌트가 자체 div를 가지므로 바로 리턴

        case 'date':
            echo '<label for="' . $fieldName . '" class="block text-sm font-medium mb-1 text-gray-700 dark:text-zinc-300">';
            echo htmlspecialchars($fieldDef['label']) . ' ' . $requiredMark;
            echo '</label>';
            echo '<input id="' . $fieldName . '" name="' . $fieldName . '" type="date"';
            if ($required) echo ' required';
            echo ' class="' . $inputClass . '"';
            echo ' value="' . htmlspecialchars($value) . '">';
            break;

        case 'radio':
            echo '<label class="block text-sm font-medium mb-2 text-gray-700 dark:text-zinc-300">';
            echo htmlspecialchars($fieldDef['label']) . ' ' . $requiredMark;
            echo '</label>';
            echo '<div class="flex flex-wrap gap-4">';
            foreach ($fieldDef['options'] ?? [] as $optValue => $optLabel) {
                $checked = ($value === $optValue) ? ' checked' : '';
                echo '<label class="inline-flex items-center cursor-pointer">';
                echo '<input type="radio" name="' . $fieldName . '" value="' . htmlspecialchars($optValue) . '"';
                if ($required) echo ' required';
                echo ' class="w-4 h-4 text-blue-600 border-gray-300 dark:border-zinc-600 focus:ring-blue-500"' . $checked . '>';
                echo '<span class="ml-2 text-sm text-gray-700 dark:text-zinc-300">' . htmlspecialchars($optLabel) . '</span>';
                echo '</label>';
            }
            echo '</div>';
            break;

        case 'file':
            // profile_photo 필드일 경우 이미지 크롭 컴포넌트 사용
            if ($fieldName === 'profile_photo') {
                $cropperId = 'profile_photo_cropper';

                echo '<label class="block text-sm font-medium mb-2 text-gray-700 dark:text-zinc-300">';
                echo htmlspecialchars($fieldDef['label']) . ' ' . $requiredMark;
                echo '</label>';

                echo '<div class="flex items-center gap-4">';

                // 프로필 사진 미리보기
                echo '<div id="' . $fieldName . '_preview" class="relative w-24 h-24 rounded-full bg-gray-200 dark:bg-zinc-700 flex items-center justify-center overflow-hidden ring-2 ring-gray-300 dark:ring-zinc-600">';
                echo '<svg id="' . $fieldName . '_placeholder_icon" class="w-12 h-12 text-gray-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
                echo '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>';
                echo '</svg>';
                echo '<img id="' . $fieldName . '_preview_img" class="hidden w-full h-full object-cover" src="" alt="프로필 미리보기">';
                echo '</div>';

                echo '<div class="flex-1">';

                // 이미지 선택/편집 버튼
                echo '<button type="button" onclick="ImageCropper.trigger(\'' . $cropperId . '\')"';
                echo ' class="inline-flex items-center px-4 py-2.5 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-medium rounded-lg transition shadow-sm">';
                echo '<svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
                echo '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>';
                echo '</svg>';
                echo '<span id="' . $fieldName . '_btn_text">' . $_t('select_photo') . '</span>';
                echo '</button>';

                // 삭제 버튼 (이미지 선택 후 표시)
                echo '<button type="button" id="' . $fieldName . '_remove_btn" onclick="removeProfilePhoto()" class="hidden ml-2 inline-flex items-center px-3 py-2.5 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/30 font-medium rounded-lg transition">';
                echo '<svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
                echo '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>';
                echo '</svg>';
                echo '</button>';

                if (!empty($fieldDef['hint'])) {
                    echo '<p class="mt-2 text-xs text-gray-500 dark:text-zinc-400">' . htmlspecialchars($fieldDef['hint']) . '</p>';
                }

                echo '</div>';
                echo '</div>';

                // 크롭된 이미지 데이터를 저장할 필드 (폼 전송용)
                echo '<input type="hidden" id="cropped_profile_photo" name="cropped_profile_photo" value="">';

                // 이미지 크롭 컴포넌트 마킹 (나중에 include)
                $GLOBALS['include_image_cropper'] = true;
                $GLOBALS['cropper_id'] = $cropperId;
            } else {
                // 일반 파일 업로드
                echo '<label for="' . $fieldName . '" class="block text-sm font-medium mb-1 text-gray-700 dark:text-zinc-300">';
                echo htmlspecialchars($fieldDef['label']) . ' ' . $requiredMark;
                echo '</label>';
                echo '<div class="flex items-center gap-4">';
                echo '<div id="' . $fieldName . '_preview" class="w-20 h-20 rounded-full bg-gray-200 dark:bg-zinc-700 flex items-center justify-center overflow-hidden">';
                echo '<svg class="w-10 h-10 text-gray-400 dark:text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">';
                echo '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>';
                echo '</svg>';
                echo '</div>';
                echo '<div class="flex-1">';
                echo '<input id="' . $fieldName . '" name="' . $fieldName . '" type="file"';
                if ($required) echo ' required';
                if (!empty($fieldDef['accept'])) echo ' accept="' . $fieldDef['accept'] . '"';
                echo ' class="block w-full text-sm text-gray-500 dark:text-zinc-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 dark:file:bg-zinc-700 file:text-blue-700 dark:file:text-blue-400 hover:file:bg-blue-100 dark:hover:file:bg-zinc-600 cursor-pointer"';
                echo ' onchange="previewProfilePhoto(this)">';
                if (!empty($fieldDef['hint'])) {
                    echo '<p class="mt-1 text-xs text-gray-500 dark:text-zinc-400">' . htmlspecialchars($fieldDef['hint']) . '</p>';
                }
                echo '</div>';
                echo '</div>';
            }
            break;

        default:
            // 알 수 없는 타입은 텍스트로 처리
            echo '<label for="' . $fieldName . '" class="block text-sm font-medium mb-1 text-gray-700 dark:text-zinc-300">';
            echo htmlspecialchars($fieldDef['label']) . ' ' . $requiredMark;
            echo '</label>';
            echo '<input id="' . $fieldName . '" name="' . $fieldName . '" type="text"';
            if ($required) echo ' required';
            echo ' class="' . $inputClass . '"';
            echo ' placeholder="' . htmlspecialchars($fieldDef['placeholder'] ?? '') . '"';
            echo ' value="' . htmlspecialchars($value) . '">';
    }

    echo '</div>';
}

// 필드 렌더링 시작
?>
<div class="rounded-md shadow-sm space-y-4" id="register-fields-container">
    <?php
    // 활성화된 필드만 순서대로 렌더링
    foreach ($registerFields as $fieldName) {
        // 필드 정의가 없으면 건너뛰기
        if (!isset($fieldDefinitions[$fieldName])) {
            continue;
        }

        $fieldDef = $fieldDefinitions[$fieldName];
        renderRegisterField($fieldName, $fieldDef, $oldInput, $inputClass, $baseUrl);
    }
    ?>
</div>

<?php if (in_array('profile_photo', $registerFields) && !empty($GLOBALS['include_image_cropper'])): ?>
<?php
// 이미지 크롭 컴포넌트 include
$cropperConfig = [
    'id' => $GLOBALS['cropper_id'] ?? 'profile_photo_cropper',
    'inputName' => 'cropped_profile_photo',
    'aspectRatio' => 1, // 정사각형 (프로필 사진)
    'outputWidth' => 400,
    'outputHeight' => 400,
    'outputFormat' => 'image/jpeg',
    'outputQuality' => 0.9,
    'cropBoxResizable' => true,
    'translations' => [
        'title' => $_t('edit_image'),
        'select_image' => $_t('select_image'),
        'drag_drop' => $_t('drag_drop_image'),
        'zoom_in' => $_t('zoom_in'),
        'zoom_out' => $_t('zoom_out'),
        'rotate_left' => $_t('rotate_left'),
        'rotate_right' => $_t('rotate_right'),
        'reset' => $_t('reset'),
        'cancel' => $_t('cancel'),
        'apply' => $_t('apply'),
        'file_too_large' => $_t('file_too_large'),
        'invalid_file_type' => $_t('invalid_file_type'),
    ]
];
include __DIR__ . '/image_cropper.php';
?>

<script>
/**
 * ProfilePhotoCropper - 프로필 사진 크롭 연동 모듈 (재사용 가능)
 *
 * 사용법:
 *   ProfilePhotoCropper.init({
 *       cropperId: 'my_cropper',
 *       fieldName: 'profile_photo',
 *       translations: { select_photo: '선택', change_photo: '변경' }
 *   });
 */
const ProfilePhotoCropper = (function() {
    'use strict';

    const instances = {};

    /**
     * 초기화
     */
    function init(config) {
        const cropperId = config.cropperId || 'profile_photo_cropper';
        const fieldName = config.fieldName || 'profile_photo';
        const translations = config.translations || {};

        instances[cropperId] = {
            fieldName: fieldName,
            translations: {
                select_photo: translations.select_photo || '사진 선택',
                change_photo: translations.change_photo || '사진 변경'
            }
        };

        // imageCropped 이벤트 리스너 등록
        document.addEventListener('imageCropped', function(e) {
            if (e.detail.id === cropperId) {
                updatePreview(cropperId, e.detail.dataUrl);
            }
        });

        console.log('[ProfilePhotoCropper] 초기화 완료:', cropperId);
    }

    /**
     * 미리보기 업데이트
     */
    function updatePreview(cropperId, dataUrl) {
        const config = instances[cropperId];
        if (!config) return;

        const fieldName = config.fieldName;
        const previewImg = document.getElementById(fieldName + '_preview_img');
        const placeholderIcon = document.getElementById(fieldName + '_placeholder_icon');
        const btnText = document.getElementById(fieldName + '_btn_text');
        const removeBtn = document.getElementById(fieldName + '_remove_btn');
        const preview = document.getElementById(fieldName + '_preview');

        if (previewImg && dataUrl) {
            previewImg.src = dataUrl;
            previewImg.classList.remove('hidden');
            if (placeholderIcon) placeholderIcon.classList.add('hidden');
            if (btnText) btnText.textContent = config.translations.change_photo;
            if (removeBtn) removeBtn.classList.remove('hidden');
            if (preview) {
                preview.classList.remove('ring-gray-300', 'dark:ring-zinc-600');
                preview.classList.add('ring-blue-500', 'dark:ring-blue-400');
            }
            console.log('[ProfilePhotoCropper] 미리보기 업데이트됨:', cropperId);
        }
    }

    /**
     * 사진 제거
     */
    function remove(cropperId) {
        const config = instances[cropperId];
        if (!config) return;

        const fieldName = config.fieldName;
        const previewImg = document.getElementById(fieldName + '_preview_img');
        const placeholderIcon = document.getElementById(fieldName + '_placeholder_icon');
        const btnText = document.getElementById(fieldName + '_btn_text');
        const removeBtn = document.getElementById(fieldName + '_remove_btn');
        const dataInput = document.getElementById('cropped_' + fieldName);
        const preview = document.getElementById(fieldName + '_preview');

        if (previewImg) {
            previewImg.src = '';
            previewImg.classList.add('hidden');
        }
        if (placeholderIcon) placeholderIcon.classList.remove('hidden');
        if (btnText) btnText.textContent = config.translations.select_photo;
        if (removeBtn) removeBtn.classList.add('hidden');
        if (dataInput) dataInput.value = '';
        if (preview) {
            preview.classList.add('ring-gray-300', 'dark:ring-zinc-600');
            preview.classList.remove('ring-blue-500', 'dark:ring-blue-400');
        }

        console.log('[ProfilePhotoCropper] 사진 제거됨:', cropperId);
    }

    return {
        init: init,
        updatePreview: updatePreview,
        remove: remove
    };
})();

// 초기화
document.addEventListener('DOMContentLoaded', function() {
    ProfilePhotoCropper.init({
        cropperId: '<?= $GLOBALS['cropper_id'] ?? 'profile_photo_cropper' ?>',
        fieldName: 'profile_photo',
        translations: {
            select_photo: '<?= $_t('select_photo') ?>',
            change_photo: '<?= $_t('change_photo') ?>'
        }
    });
});

// 삭제 버튼용 전역 함수
function removeProfilePhoto() {
    ProfilePhotoCropper.remove('<?= $GLOBALS['cropper_id'] ?? 'profile_photo_cropper' ?>');
}
</script>
<?php endif; ?>

<?php if (in_array('password', $registerFields)): ?>
<script>
// 비밀번호 표시/숨기기 토글 — DOMContentLoaded 이미 fire 됐을 수 있어 즉시 + 지연 둘 다 처리
(function() {
    function bindPasswordToggle() {
        var togglePassword = document.getElementById('togglePassword');
        var passwordInput = document.getElementById('password');
        var eyeIcon = document.getElementById('eyeIcon');
        var eyeOffIcon = document.getElementById('eyeOffIcon');

        if (togglePassword && passwordInput && !togglePassword.dataset.bound) {
            togglePassword.dataset.bound = '1';
            togglePassword.addEventListener('click', function() {
                var type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                if (eyeIcon) eyeIcon.classList.toggle('hidden');
                if (eyeOffIcon) eyeOffIcon.classList.toggle('hidden');
            });
        }

        // 비밀번호 확인 유효성 검사
        var passwordConfirm = document.getElementById('password_confirm');
        if (passwordConfirm && passwordInput && !passwordConfirm.dataset.bound) {
            passwordConfirm.dataset.bound = '1';
            passwordConfirm.addEventListener('input', function() {
                if (this.value !== passwordInput.value) {
                    this.setCustomValidity('<?= $_t('password_mismatch') ?>');
                } else {
                    this.setCustomValidity('');
                }
            });
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', bindPasswordToggle);
    } else {
        bindPasswordToggle();
    }
})();
</script>
<?php endif; ?>
