<?php
/**
 * RezlyX Admin Settings - Language Settings
 * 언어 설정 (자동 선택, 지원 언어, 기본 언어, 커스텀 언어)
 */

// Initialize database and settings
require_once __DIR__ . '/_init.php';

$pageTitle = __('admin.settings.language.page_title') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$currentSettingsPage = 'language';

// 기본 제공 언어 목록
$defaultLanguages = [
    'ko' => ['name' => '한국어', 'native' => '한국어', 'builtin' => true],
    'en' => ['name' => 'English', 'native' => 'English', 'builtin' => true],
    'ja' => ['name' => '日本語', 'native' => '日本語', 'builtin' => true],
    'zh_CN' => ['name' => '중국어(간체)', 'native' => '中文(中国)', 'builtin' => true],
    'zh_TW' => ['name' => '중국어(번체)', 'native' => '中文(臺灣)', 'builtin' => true],
    'de' => ['name' => '독일어', 'native' => 'Deutsch', 'builtin' => true],
    'es' => ['name' => '스페인어', 'native' => 'Español', 'builtin' => true],
    'fr' => ['name' => '프랑스어', 'native' => 'Français', 'builtin' => true],
    'mn' => ['name' => '몽골어', 'native' => 'Монгол', 'builtin' => true],
    'ru' => ['name' => '러시아어', 'native' => 'Русский', 'builtin' => true],
    'tr' => ['name' => '터키어', 'native' => 'Türkçe', 'builtin' => true],
    'vi' => ['name' => '베트남어', 'native' => 'Tiếng Việt', 'builtin' => true],
    'id' => ['name' => '인도네시아어', 'native' => 'Bahasa Indonesia', 'builtin' => true],
];

// 커스텀 언어 가져오기
$customLanguages = json_decode($settings['custom_languages'] ?? '{}', true) ?: [];

// 전체 언어 목록 (기본 + 커스텀)
$allLanguages = array_merge($defaultLanguages, $customLanguages);

// 현재 설정 가져오기
$autoDetect = ($settings['language_auto_detect'] ?? '1') === '1';
$supportedLanguages = json_decode($settings['supported_languages'] ?? '["ko","en","ja"]', true) ?: ['ko', 'en', 'ja'];
$defaultLanguage = $settings['default_language'] ?? 'ko';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 언어 설정 업데이트
    if ($action === 'update_language_settings') {
        $autoDetect = isset($_POST['auto_detect']) && $_POST['auto_detect'] === '1' ? '1' : '0';
        $selectedLanguages = $_POST['supported_languages'] ?? [];
        $defaultLang = $_POST['default_language'] ?? 'ko';

        // 최소 1개 언어는 선택되어야 함
        if (empty($selectedLanguages)) {
            $selectedLanguages = ['ko'];
        }

        // 기본 언어가 지원 언어에 포함되어야 함
        if (!in_array($defaultLang, $selectedLanguages)) {
            $defaultLang = $selectedLanguages[0];
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO rzx_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
            $stmt->execute(['language_auto_detect', $autoDetect]);
            $stmt->execute(['supported_languages', json_encode($selectedLanguages)]);
            $stmt->execute(['default_language', $defaultLang]);

            // 설정 배열 업데이트
            $settings['language_auto_detect'] = $autoDetect;
            $settings['supported_languages'] = json_encode($selectedLanguages);
            $settings['default_language'] = $defaultLang;

            // 로컬 변수 업데이트
            $autoDetect = $autoDetect === '1';
            $supportedLanguages = $selectedLanguages;
            $defaultLanguage = $defaultLang;

            $message = __('admin.settings.success');
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = __('admin.settings.error_save') . ': ' . $e->getMessage();
            $messageType = 'error';
        }
    }

    // 커스텀 언어 추가
    if ($action === 'add_language') {
        $langCode = trim($_POST['lang_code'] ?? '');
        $langNative = trim($_POST['lang_native'] ?? '');

        // 유효성 검사
        if (empty($langCode) || empty($langNative)) {
            $message = __('admin.settings.language.messages.error_empty');
            $messageType = 'error';
        } elseif (!preg_match('/^[a-z]{2}(_[A-Z]{2})?$/', $langCode)) {
            $message = __('admin.settings.language.messages.error_invalid_code');
            $messageType = 'error';
        } elseif (isset($allLanguages[$langCode])) {
            $message = __('admin.settings.language.messages.error_exists');
            $messageType = 'error';
        } else {
            // 커스텀 언어 추가
            $customLanguages[$langCode] = [
                'name' => $langNative,
                'native' => $langNative,
                'builtin' => false
            ];

            try {
                $stmt = $pdo->prepare("INSERT INTO rzx_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
                $stmt->execute(['custom_languages', json_encode($customLanguages, JSON_UNESCAPED_UNICODE)]);

                // 전체 언어 목록 업데이트
                $allLanguages = array_merge($defaultLanguages, $customLanguages);

                $message = str_replace('{name}', $langNative, __('admin.settings.language.messages.added'));
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = __('admin.settings.language.messages.error_add') . ': ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }

    // 커스텀 언어 삭제
    if ($action === 'delete_language') {
        $langCode = $_POST['lang_code'] ?? '';

        if (isset($customLanguages[$langCode])) {
            $deletedLang = $customLanguages[$langCode]['native'];
            unset($customLanguages[$langCode]);

            // 지원 언어에서도 제거
            $supportedLanguages = array_values(array_diff($supportedLanguages, [$langCode]));
            if (empty($supportedLanguages)) {
                $supportedLanguages = ['ko'];
            }

            // 기본 언어가 삭제된 언어인 경우
            if ($defaultLanguage === $langCode) {
                $defaultLanguage = $supportedLanguages[0];
            }

            try {
                $stmt = $pdo->prepare("INSERT INTO rzx_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
                $stmt->execute(['custom_languages', json_encode($customLanguages, JSON_UNESCAPED_UNICODE)]);
                $stmt->execute(['supported_languages', json_encode($supportedLanguages)]);
                $stmt->execute(['default_language', $defaultLanguage]);

                // 전체 언어 목록 업데이트
                $allLanguages = array_merge($defaultLanguages, $customLanguages);

                $message = str_replace('{name}', $deletedLang, __('admin.settings.language.messages.deleted'));
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = __('admin.settings.language.messages.error_delete') . ': ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

ob_start();
?>

<!-- Sub Navigation Tabs -->
<?php include __DIR__ . '/_settings_nav.php'; ?>

<?php if (!empty($message)): ?>
<div class="mb-6 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300' : 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300' ?>">
    <div class="flex items-center">
        <?php if ($messageType === 'success'): ?>
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
        </svg>
        <?php else: ?>
        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
        </svg>
        <?php endif; ?>
        <?= htmlspecialchars($message) ?>
    </div>
</div>
<?php endif; ?>

<!-- Language Settings -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
    <?php
    $headerIcon = 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9';
    $headerTitle = __('admin.settings.language.title');
    $headerDescription = __('admin.settings.language.description');
    $headerIconColor = ''; $headerActions = '';
    include __DIR__ . '/../components/settings-header.php';
    ?>

    <form method="POST" class="space-y-6">
        <input type="hidden" name="action" value="update_language_settings">

        <!-- 언어 자동 선택 -->
        <div class="space-y-3">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('admin.settings.language.auto_detect.label') ?></label>
                <div class="flex items-center gap-6">
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="auto_detect" value="1" <?= $autoDetect ? 'checked' : '' ?>
                               class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('admin.settings.language.auto_detect.yes') ?></span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="auto_detect" value="0" <?= !$autoDetect ? 'checked' : '' ?>
                               class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('admin.settings.language.auto_detect.no') ?></span>
                    </label>
                </div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2"><?= __('admin.settings.language.auto_detect.hint') ?></p>
            </div>
        </div>

        <!-- 지원 언어 선택 -->
        <div class="border-t dark:border-zinc-700 pt-6">
            <div class="flex items-center justify-between mb-3">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('admin.settings.language.supported.label') ?></label>
                <button type="button" onclick="openAddLanguageModal()"
                        class="inline-flex items-center px-3 py-1.5 text-sm font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/30 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/50 transition">
                    <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                    </svg>
                    <?= __('admin.settings.language.supported.add_button') ?>
                </button>
            </div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-4"><?= __('admin.settings.language.supported.hint') ?></p>
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3" id="languageGrid">
                <?php foreach ($allLanguages as $code => $lang): ?>
                <label class="group relative flex items-center p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg cursor-pointer hover:bg-zinc-100 dark:hover:bg-zinc-800 transition">
                    <input type="checkbox" name="supported_languages[]" value="<?= htmlspecialchars($code) ?>"
                           <?= in_array($code, $supportedLanguages) ? 'checked' : '' ?>
                           class="w-4 h-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500"
                           onchange="updateDefaultLanguageOptions()">
                    <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($lang['native']) ?></span>
                    <?php if (empty($lang['builtin'])): ?>
                    <button type="button" onclick="confirmDeleteLanguage('<?= htmlspecialchars($code) ?>', '<?= htmlspecialchars($lang['native']) ?>')"
                            class="absolute top-1 right-1 p-1 text-zinc-400 hover:text-red-500 opacity-0 group-hover:opacity-100 transition-opacity"
                            title="<?= __('admin.settings.language.delete_modal.delete') ?>"
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                        </svg>
                    </button>
                    <?php endif; ?>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 기본 언어 선택 -->
        <div class="border-t dark:border-zinc-700 pt-6">
            <label for="default_language" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('admin.settings.language.default.label') ?></label>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3"><?= __('admin.settings.language.default.hint') ?></p>
            <select name="default_language" id="default_language"
                    class="w-full md:w-1/3 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                <?php foreach ($allLanguages as $code => $lang): ?>
                <?php if (in_array($code, $supportedLanguages)): ?>
                <option value="<?= htmlspecialchars($code) ?>" <?= $defaultLanguage === $code ? 'selected' : '' ?>><?= htmlspecialchars($lang['native']) ?></option>
                <?php endif; ?>
                <?php endforeach; ?>
            </select>
        </div>

        <!-- 언어별 사이트 정보 안내 -->
        <div class="border-t dark:border-zinc-700 pt-6">
            <div class="p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <div>
                        <p class="text-sm font-medium text-blue-800 dark:text-blue-300"><?= __('admin.settings.language.content_info.title') ?></p>
                        <p class="text-xs text-blue-700 dark:text-blue-400 mt-1">
                            <?= __('admin.settings.language.content_info.description') ?>
                        </p>
                    </div>
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-4 border-t dark:border-zinc-700">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                <?= __('admin.buttons.save') ?>
            </button>
        </div>
    </form>
</div>

<!-- 언어 추가 모달 -->
<div id="addLanguageModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeAddLanguageModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="relative w-full max-w-md bg-white dark:bg-zinc-800 rounded-xl shadow-xl" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between p-4 border-b dark:border-zinc-700">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('admin.settings.language.add_modal.title') ?></h3>
                <button type="button" onclick="closeAddLanguageModal()" class="p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form method="POST" class="p-4 space-y-4">
                <input type="hidden" name="action" value="add_language">

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.language.add_modal.code_label') ?> <span class="text-red-500"><?= __('admin.settings.language.add_modal.required') ?></span></label>
                    <input type="text" name="lang_code" id="langCode" required
                           placeholder="<?= __('admin.settings.language.add_modal.code_placeholder') ?>"
                           pattern="[a-z]{2}(_[A-Z]{2})?"
                           class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1"><?= __('admin.settings.language.add_modal.code_hint') ?></p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.language.add_modal.name_label') ?> <span class="text-red-500"><?= __('admin.settings.language.add_modal.required') ?></span></label>
                    <input type="text" name="lang_native" id="langNative" required
                           placeholder="<?= __('admin.settings.language.add_modal.name_placeholder') ?>"
                           class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1"><?= __('admin.settings.language.add_modal.name_hint') ?></p>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t dark:border-zinc-700">
                    <button type="button" onclick="closeAddLanguageModal()"
                            class="px-4 py-2 text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 rounded-lg hover:bg-zinc-200 dark:hover:bg-zinc-600 transition">
                        <?= __('admin.settings.language.add_modal.cancel') ?>
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                        <?= __('admin.settings.language.add_modal.add') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 언어 삭제 확인 모달 -->
<div id="deleteLanguageModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeDeleteLanguageModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="relative w-full max-w-sm bg-white dark:bg-zinc-800 rounded-xl shadow-xl" onclick="event.stopPropagation()">
            <div class="p-6 text-center">
                <svg class="w-12 h-12 text-red-500 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2"><?= __('admin.settings.language.delete_modal.title') ?></h3>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-6">
                    '<span id="deleteLanguageName"></span>' <?= __('admin.settings.language.delete_modal.confirm') ?><br>
                    <span class="text-red-500"><?= __('admin.settings.language.delete_modal.warning') ?></span>
                </p>
                <form method="POST" class="flex justify-center gap-3">
                    <input type="hidden" name="action" value="delete_language">
                    <input type="hidden" name="lang_code" id="deleteLanguageCode">
                    <button type="button" onclick="closeDeleteLanguageModal()"
                            class="px-4 py-2 text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 rounded-lg hover:bg-zinc-200 dark:hover:bg-zinc-600 transition">
                        <?= __('admin.settings.language.delete_modal.cancel') ?>
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition">
                        <?= __('admin.settings.language.delete_modal.delete') ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
// 언어 정보
const allLanguages = <?= json_encode($allLanguages, JSON_UNESCAPED_UNICODE) ?>;

// 지원 언어가 변경될 때 기본 언어 옵션 업데이트
function updateDefaultLanguageOptions() {
    const checkboxes = document.querySelectorAll('input[name="supported_languages[]"]');
    const defaultSelect = document.getElementById('default_language');
    const currentDefault = defaultSelect.value;

    // 선택된 언어 목록 가져오기
    const selectedLanguages = [];
    checkboxes.forEach(cb => {
        if (cb.checked) {
            selectedLanguages.push(cb.value);
        }
    });

    // 최소 1개 언어는 선택되어야 함
    if (selectedLanguages.length === 0) {
        // 한국어를 기본으로 체크
        const koCheckbox = document.querySelector('input[name="supported_languages[]"][value="ko"]');
        if (koCheckbox) {
            koCheckbox.checked = true;
            selectedLanguages.push('ko');
        }
    }

    // 기본 언어 옵션 업데이트
    defaultSelect.innerHTML = '';
    selectedLanguages.forEach(code => {
        const option = document.createElement('option');
        option.value = code;
        option.textContent = allLanguages[code]?.native || code;
        if (code === currentDefault) {
            option.selected = true;
        }
        defaultSelect.appendChild(option);
    });

    // 현재 기본 언어가 선택된 언어에 없으면 첫 번째 선택
    if (!selectedLanguages.includes(currentDefault) && selectedLanguages.length > 0) {
        defaultSelect.value = selectedLanguages[0];
    }

    console.log('Supported languages updated:', selectedLanguages);
}

// 언어 추가 모달
function openAddLanguageModal() {
    document.getElementById('addLanguageModal').classList.remove('hidden');
    document.getElementById('langCode').focus();
    document.body.style.overflow = 'hidden';
}

function closeAddLanguageModal() {
    document.getElementById('addLanguageModal').classList.add('hidden');
    document.getElementById('langCode').value = '';
    document.getElementById('langNative').value = '';
    document.body.style.overflow = '';
}

// 언어 삭제 확인 모달
function confirmDeleteLanguage(code, name) {
    document.getElementById('deleteLanguageCode').value = code;
    document.getElementById('deleteLanguageName').textContent = name;
    document.getElementById('deleteLanguageModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeDeleteLanguageModal() {
    document.getElementById('deleteLanguageModal').classList.add('hidden');
    document.body.style.overflow = '';
}

// ESC 키로 모달 닫기
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeAddLanguageModal();
        closeDeleteLanguageModal();
    }
});

// 페이지 로드 시 초기화
document.addEventListener('DOMContentLoaded', function() {
    console.log('Language settings page loaded');
});
</script>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/_layout.php';
