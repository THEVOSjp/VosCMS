<?php
/**
 * RezlyX Admin Settings - Translation Management
 * 번역 관리 (번역 파일 생성, 편집, 관리)
 */

// Initialize database and settings
require_once __DIR__ . '/_init.php';

$pageTitle = __('settings.translations.title') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$currentSettingsPage = 'translations';

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
$supportedLanguages = json_decode($settings['supported_languages'] ?? '["ko","en","ja"]', true) ?: ['ko', 'en', 'ja'];
$defaultLanguage = $settings['default_language'] ?? 'ko';

// 번역 파일 경로
$langPath = realpath(__DIR__ . '/../../../../resources/lang');

// 각 지원 언어별 번역 파일 상태 확인
$langFileStatus = [];
foreach ($supportedLanguages as $locale) {
    $localePath = $langPath . DIRECTORY_SEPARATOR . $locale;
    $hasFiles = is_dir($localePath) && count(glob($localePath . '/*.php')) > 0;
    $files = [];
    if ($hasFiles) {
        foreach (glob($localePath . '/*.php') as $file) {
            $files[] = pathinfo($file, PATHINFO_FILENAME);
        }
    }
    $langFileStatus[$locale] = [
        'exists' => $hasFiles,
        'files' => $files,
        'path' => $localePath,
    ];
}

// 기본 번역 그룹 (한국어 기준)
$defaultLangPath = $langPath . DIRECTORY_SEPARATOR . 'ko';
$translationGroups = [];
if (is_dir($defaultLangPath)) {
    foreach (glob($defaultLangPath . '/*.php') as $file) {
        $translationGroups[] = pathinfo($file, PATHINFO_FILENAME);
    }
}

// 선택된 파일 편집 모드
$editMode = isset($_GET['edit']) && isset($_GET['locale']);
$editLocale = $_GET['locale'] ?? '';
$editFile = $_GET['edit'] ?? '';
$editContent = [];
$originalContent = [];

if ($editMode && $editLocale && $editFile) {
    $filePath = $langPath . DIRECTORY_SEPARATOR . $editLocale . DIRECTORY_SEPARATOR . $editFile . '.php';
    if (file_exists($filePath)) {
        $editContent = include $filePath;
        // 원본 (한국어) 파일도 로드
        $originalPath = $langPath . DIRECTORY_SEPARATOR . 'ko' . DIRECTORY_SEPARATOR . $editFile . '.php';
        if (file_exists($originalPath)) {
            $originalContent = include $originalPath;
        }
    }
}

// POST 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    // 번역 파일 삭제
    if ($action === 'delete_lang_files') {
        $targetLocale = $_POST['target_locale'] ?? '';

        if (empty($targetLocale)) {
            $message = __('settings.translations.msg_no_locale_delete');
            $messageType = 'error';
        } elseif ($targetLocale === $defaultLanguage) {
            $message = __('settings.translations.msg_default_nodelete');
            $messageType = 'error';
        } else {
            $targetPath = $langPath . DIRECTORY_SEPARATOR . $targetLocale;

            if (!is_dir($targetPath)) {
                $message = __('settings.translations.msg_no_files_delete');
                $messageType = 'error';
            } else {
                try {
                    // 폴더 내 파일 삭제
                    $files = glob($targetPath . '/*');
                    foreach ($files as $file) {
                        if (is_file($file)) {
                            unlink($file);
                        }
                    }
                    // 폴더 삭제
                    rmdir($targetPath);

                    $langName = $allLanguages[$targetLocale]['native'] ?? $targetLocale;
                    $message = str_replace('{name}', $langName, __('settings.translations.msg_deleted'));
                    $messageType = 'success';

                    // 상태 업데이트
                    $langFileStatus[$targetLocale] = [
                        'exists' => false,
                        'files' => [],
                        'path' => $targetPath,
                    ];
                } catch (Exception $e) {
                    $message = __('settings.translations.msg_error_delete') . ': ' . $e->getMessage();
                    $messageType = 'error';
                }
            }
        }
    }

    // 번역 파일 다운로드 (ZIP)
    if ($action === 'download_lang_files') {
        $targetLocale = $_POST['target_locale'] ?? '';

        if (empty($targetLocale)) {
            $message = __('settings.translations.msg_no_locale_download');
            $messageType = 'error';
        } else {
            $targetPath = $langPath . DIRECTORY_SEPARATOR . $targetLocale;

            if (!is_dir($targetPath)) {
                $message = __('settings.translations.msg_no_files_download');
                $messageType = 'error';
            } else {
                try {
                    $zipFile = sys_get_temp_dir() . DIRECTORY_SEPARATOR . "lang_{$targetLocale}_" . date('Ymd_His') . '.zip';
                    $zip = new ZipArchive();

                    if ($zip->open($zipFile, ZipArchive::CREATE | ZipArchive::OVERWRITE) === true) {
                        $files = glob($targetPath . '/*.php');
                        foreach ($files as $file) {
                            $zip->addFile($file, basename($file));
                        }
                        $zip->close();

                        // 다운로드 헤더 전송
                        header('Content-Type: application/zip');
                        header('Content-Disposition: attachment; filename="lang_' . $targetLocale . '.zip"');
                        header('Content-Length: ' . filesize($zipFile));
                        header('Cache-Control: no-cache');
                        readfile($zipFile);
                        unlink($zipFile); // 임시 파일 삭제
                        exit;
                    } else {
                        $message = __('settings.translations.msg_error_zip');
                        $messageType = 'error';
                    }
                } catch (Exception $e) {
                    $message = __('settings.translations.msg_error_download') . ': ' . $e->getMessage();
                    $messageType = 'error';
                }
            }
        }
    }

    // 번역 파일 업로드 (ZIP)
    if ($action === 'upload_lang_files') {
        $targetLocale = $_POST['target_locale'] ?? '';

        if (empty($targetLocale)) {
            $message = __('settings.translations.msg_no_locale_upload');
            $messageType = 'error';
        } elseif (!isset($_FILES['lang_zip']) || $_FILES['lang_zip']['error'] !== UPLOAD_ERR_OK) {
            $message = __('settings.translations.msg_error_upload_file');
            $messageType = 'error';
        } else {
            $uploadedFile = $_FILES['lang_zip']['tmp_name'];
            $targetPath = $langPath . DIRECTORY_SEPARATOR . $targetLocale;

            // 폴더 생성
            if (!is_dir($targetPath)) {
                mkdir($targetPath, 0755, true);
            }

            try {
                $zip = new ZipArchive();
                if ($zip->open($uploadedFile) === true) {
                    $uploadedCount = 0;
                    for ($i = 0; $i < $zip->numFiles; $i++) {
                        $filename = $zip->getNameIndex($i);
                        // PHP 파일만 추출
                        if (pathinfo($filename, PATHINFO_EXTENSION) === 'php') {
                            $content = $zip->getFromIndex($i);
                            $targetFile = $targetPath . DIRECTORY_SEPARATOR . basename($filename);
                            file_put_contents($targetFile, $content);
                            $uploadedCount++;
                        }
                    }
                    $zip->close();

                    $langName = $allLanguages[$targetLocale]['native'] ?? $targetLocale;
                    $message = str_replace(['{name}', '{count}'], [$langName, $uploadedCount], __('settings.translations.msg_uploaded'));
                    $messageType = 'success';

                    // 상태 업데이트
                    $files = [];
                    foreach (glob($targetPath . '/*.php') as $file) {
                        $files[] = pathinfo($file, PATHINFO_FILENAME);
                    }
                    $langFileStatus[$targetLocale] = [
                        'exists' => true,
                        'files' => $files,
                        'path' => $targetPath,
                    ];
                } else {
                    $message = __('settings.translations.msg_error_zip_open');
                    $messageType = 'error';
                }
            } catch (Exception $e) {
                $message = __('settings.translations.msg_error_upload') . ': ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }

    // 번역 파일 생성
    if ($action === 'create_lang_files') {
        $targetLocale = $_POST['target_locale'] ?? '';
        $sourceLocale = $_POST['source_locale'] ?? 'ko';

        if (empty($targetLocale)) {
            $message = __('settings.translations.msg_no_locale_create');
            $messageType = 'error';
        } elseif (!isset($allLanguages[$targetLocale])) {
            $message = __('settings.translations.msg_invalid_code');
            $messageType = 'error';
        } else {
            $sourcePath = $langPath . DIRECTORY_SEPARATOR . $sourceLocale;
            $targetPath = $langPath . DIRECTORY_SEPARATOR . $targetLocale;

            if (!is_dir($sourcePath)) {
                $message = __('settings.translations.msg_source_missing');
                $messageType = 'error';
            } else {
                try {
                    // 대상 폴더 생성
                    if (!is_dir($targetPath)) {
                        mkdir($targetPath, 0755, true);
                    }

                    // 파일 복사
                    $copiedFiles = 0;
                    foreach (glob($sourcePath . '/*.php') as $sourceFile) {
                        $filename = basename($sourceFile);
                        $targetFile = $targetPath . DIRECTORY_SEPARATOR . $filename;

                        // 이미 존재하는 파일은 건너뛰기
                        if (!file_exists($targetFile)) {
                            copy($sourceFile, $targetFile);
                            $copiedFiles++;
                        }
                    }

                    // 상태 업데이트
                    $langFileStatus[$targetLocale] = [
                        'exists' => true,
                        'files' => $translationGroups,
                        'path' => $targetPath,
                    ];

                    $langName = $allLanguages[$targetLocale]['native'] ?? $targetLocale;
                    $message = str_replace(['{name}', '{count}'], [$langName, $copiedFiles], __('settings.translations.msg_created'));
                    $messageType = 'success';
                } catch (Exception $e) {
                    $message = __('settings.translations.msg_error_create') . ': ' . $e->getMessage();
                    $messageType = 'error';
                }
            }
        }
    }

    // 번역 내용 저장
    if ($action === 'save_translations') {
        $saveLocale = $_POST['locale'] ?? '';
        $saveFile = $_POST['file'] ?? '';
        $translations = $_POST['translations'] ?? [];

        if (empty($saveLocale) || empty($saveFile)) {
            $message = __('settings.translations.msg_no_save_info');
            $messageType = 'error';
        } else {
            $filePath = $langPath . DIRECTORY_SEPARATOR . $saveLocale . DIRECTORY_SEPARATOR . $saveFile . '.php';

            try {
                // 기존 파일 로드
                $existingContent = [];
                if (file_exists($filePath)) {
                    $existingContent = include $filePath;
                }

                // 번역 내용 업데이트 (중첩 배열 지원)
                foreach ($translations as $key => $value) {
                    $keys = explode('.', $key);
                    $ref = &$existingContent;
                    foreach ($keys as $i => $k) {
                        if ($i === count($keys) - 1) {
                            $ref[$k] = $value;
                        } else {
                            if (!isset($ref[$k]) || !is_array($ref[$k])) {
                                $ref[$k] = [];
                            }
                            $ref = &$ref[$k];
                        }
                    }
                    unset($ref);
                }

                // 파일 저장
                $phpContent = "<?php\n/**\n * " . ($allLanguages[$saveLocale]['native'] ?? $saveLocale) . " Language File\n */\n\nreturn " . var_export($existingContent, true) . ";\n";
                file_put_contents($filePath, $phpContent);

                $message = __('settings.translations.translation_saved');
                $messageType = 'success';

                // 편집 내용 다시 로드
                $editContent = $existingContent;
            } catch (Exception $e) {
                $message = __('settings.translations.msg_error_save') . ': ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    }
}

// 번역 배열을 평탄화하는 함수
function flattenTranslations($array, $prefix = '') {
    $result = [];
    foreach ($array as $key => $value) {
        $newKey = $prefix ? $prefix . '.' . $key : $key;
        if (is_array($value)) {
            $result = array_merge($result, flattenTranslations($value, $newKey));
        } else {
            $result[$newKey] = $value;
        }
    }
    return $result;
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

<?php if ($editMode && !empty($editContent)): ?>
<!-- 번역 편집 모드 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
    <div class="flex items-center justify-between mb-4">
        <div class="flex items-center">
            <a href="<?= $adminUrl ?>/settings/translations" class="mr-3 p-2 text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
            </a>
            <div>
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white">
                    <?= htmlspecialchars($editFile) ?>.php
                    <span class="ml-2 px-2 py-0.5 text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded">
                        <?= htmlspecialchars($allLanguages[$editLocale]['native'] ?? $editLocale) ?>
                    </span>
                </h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('settings.translations.edit_hint') ?></p>
            </div>
        </div>
    </div>

    <form method="POST">
        <input type="hidden" name="action" value="save_translations">
        <input type="hidden" name="locale" value="<?= htmlspecialchars($editLocale) ?>">
        <input type="hidden" name="file" value="<?= htmlspecialchars($editFile) ?>">

        <div class="space-y-4 max-h-[600px] overflow-y-auto pr-2">
            <?php
            $flatEdit = flattenTranslations($editContent);
            $flatOriginal = flattenTranslations($originalContent);
            foreach ($flatEdit as $key => $value):
            ?>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                <div>
                    <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1">
                        <?= htmlspecialchars($key) ?>
                    </label>
                    <?php if ($editLocale !== 'ko' && isset($flatOriginal[$key])): ?>
                    <div class="text-sm text-zinc-600 dark:text-zinc-300 p-2 bg-zinc-100 dark:bg-zinc-800 rounded">
                        <?= htmlspecialchars($flatOriginal[$key]) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <div>
                    <?php if ($editLocale !== 'ko'): ?>
                    <label class="block text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1"><?= __('settings.translations.translation') ?></label>
                    <?php endif; ?>
                    <input type="text" name="translations[<?= htmlspecialchars($key) ?>]"
                           value="<?= htmlspecialchars($value) ?>"
                           class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="flex justify-end gap-3 pt-6 mt-6 border-t dark:border-zinc-700">
            <a href="<?= $adminUrl ?>/settings/translations"
               class="px-4 py-2 text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 rounded-lg hover:bg-zinc-200 dark:hover:bg-zinc-600 transition">
                <?= __('settings.translations.cancel') ?>
            </a>
            <button type="submit"
                    class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                <?= __('settings.translations.save') ?>
            </button>
        </div>
    </form>
</div>

<?php else: ?>
<!-- 번역 파일 목록 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
    <?php
    $headerIcon = 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z';
    $headerTitle = __('settings.translations.translation_files');
    $headerDescription = __('settings.translations.description_full');
    $headerIconColor = ''; $headerActions = '';
    include __DIR__ . '/../components/settings-header.php';
    ?>

    <!-- 번역 그룹 목록 -->
    <div class="mb-6">
        <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('settings.translations.translation_groups') ?></h3>
        <div class="flex flex-wrap gap-2">
            <?php foreach ($translationGroups as $group): ?>
            <span class="px-2 py-1 text-xs font-medium bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 rounded">
                <?= htmlspecialchars($group) ?>.php
            </span>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 언어별 번역 파일 상태 -->
    <div class="border-t dark:border-zinc-700 pt-6">
        <h3 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-4"><?= __('settings.translations.supported_languages') ?></h3>
        <div class="space-y-4">
            <?php
            // 기본 언어를 맨 위로 정렬
            $sortedLanguages = $supportedLanguages;
            usort($sortedLanguages, function($a, $b) use ($defaultLanguage) {
                if ($a === $defaultLanguage) return -1;
                if ($b === $defaultLanguage) return 1;
                return 0;
            });
            ?>
            <?php foreach ($sortedLanguages as $locale): ?>
            <?php
                $status = $langFileStatus[$locale] ?? ['exists' => false, 'files' => []];
                $langInfo = $allLanguages[$locale] ?? ['native' => $locale];
                $hasAllFiles = $status['exists'] && count($status['files']) >= count($translationGroups);
                $missingFiles = array_diff($translationGroups, $status['files']);
            ?>
            <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                <div class="flex items-center justify-between mb-3">
                    <div class="flex items-center gap-3">
                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300">
                            <?= htmlspecialchars($langInfo['native']) ?>
                            <span class="text-zinc-400 dark:text-zinc-500">(<?= htmlspecialchars($locale) ?>)</span>
                        </span>
                        <?php if ($locale === $defaultLanguage): ?>
                        <span class="px-2 py-0.5 text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded">
                            <?= __('settings.translations.default') ?>
                        </span>
                        <?php endif; ?>
                    </div>
                    <div class="flex items-center gap-2">
                        <?php if ($status['exists']): ?>
                            <?php if ($hasAllFiles): ?>
                            <span class="flex items-center text-sm text-green-600 dark:text-green-400">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                </svg>
                                <?= count($status['files']) ?>개
                            </span>
                            <?php else: ?>
                            <span class="flex items-center text-sm text-amber-600 dark:text-amber-400">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                                </svg>
                                <?= count($status['files']) ?>/<?= count($translationGroups) ?>개
                            </span>
                            <button type="button" onclick="openCreateFilesModal('<?= htmlspecialchars($locale) ?>', '<?= htmlspecialchars($langInfo['native']) ?>')"
                                    class="px-2 py-1 text-xs font-medium text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 rounded hover:bg-amber-100 dark:hover:bg-amber-900/30 transition">
                                <?= __('settings.translations.create_missing') ?>
                            </button>
                            <?php endif; ?>
                            <!-- 내보내기 버튼 -->
                            <form method="POST" class="inline">
                                <input type="hidden" name="action" value="download_lang_files">
                                <input type="hidden" name="target_locale" value="<?= htmlspecialchars($locale) ?>">
                                <button type="submit" class="px-2 py-1 text-xs font-medium text-zinc-600 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 rounded hover:bg-zinc-200 dark:hover:bg-zinc-600 transition" title="<?= __('settings.translations.export') ?>">
                                    <?= __('settings.translations.export') ?>
                                </button>
                            </form>
                            <!-- 가져오기 버튼 -->
                            <button type="button" onclick="openUploadModal('<?= htmlspecialchars($locale) ?>', '<?= htmlspecialchars($langInfo['native']) ?>')"
                                    class="px-2 py-1 text-xs font-medium text-zinc-600 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 rounded hover:bg-zinc-200 dark:hover:bg-zinc-600 transition" title="<?= __('settings.translations.import') ?>">
                                <?= __('settings.translations.import') ?>
                            </button>
                            <!-- 삭제 버튼 (기본 언어 제외) -->
                            <?php if ($locale !== $defaultLanguage): ?>
                            <button type="button" onclick="openDeleteModal('<?= htmlspecialchars($locale) ?>', '<?= htmlspecialchars($langInfo['native']) ?>')"
                                    class="px-2 py-1 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 rounded hover:bg-red-100 dark:hover:bg-red-900/30 transition" title="<?= __('settings.translations.delete') ?>">
                                <?= __('settings.translations.delete') ?>
                            </button>
                            <?php endif; ?>
                        <?php else: ?>
                            <span class="flex items-center text-sm text-red-600 dark:text-red-400">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                </svg>
                                <?= __('settings.translations.no_files') ?>
                            </span>
                            <button type="button" onclick="openCreateFilesModal('<?= htmlspecialchars($locale) ?>', '<?= htmlspecialchars($langInfo['native']) ?>')"
                                    class="px-2 py-1 text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 rounded hover:bg-blue-100 dark:hover:bg-blue-900/30 transition">
                                <?= __('settings.translations.create_files') ?>
                            </button>
                            <!-- 가져오기 버튼 (파일 없을 때도) -->
                            <button type="button" onclick="openUploadModal('<?= htmlspecialchars($locale) ?>', '<?= htmlspecialchars($langInfo['native']) ?>')"
                                    class="px-2 py-1 text-xs font-medium text-zinc-600 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 rounded hover:bg-zinc-200 dark:hover:bg-zinc-600 transition" title="<?= __('settings.translations.import') ?>">
                                <?= __('settings.translations.import') ?>
                            </button>
                        <?php endif; ?>
                    </div>
                </div>

                <?php if ($status['exists'] && count($status['files']) > 0): ?>
                <!-- 파일 목록 -->
                <div class="flex flex-wrap gap-2 mt-3 pt-3 border-t dark:border-zinc-700">
                    <?php foreach ($status['files'] as $file): ?>
                    <a href="<?= $adminUrl ?>/settings/translations?locale=<?= urlencode($locale) ?>&edit=<?= urlencode($file) ?>"
                       class="inline-flex items-center px-3 py-1.5 text-xs font-medium text-zinc-600 dark:text-zinc-300 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-600 rounded-lg hover:border-blue-500 hover:text-blue-600 dark:hover:text-blue-400 transition">
                        <svg class="w-3 h-3 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        <?= htmlspecialchars($file) ?>.php
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 안내 -->
    <div class="mt-6 p-4 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
        <div class="flex items-start">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <p class="text-sm font-medium text-blue-800 dark:text-blue-300"><?= __('settings.translations.edit_tip_title') ?></p>
                <p class="text-xs text-blue-700 dark:text-blue-400 mt-1">
                    <?= __('settings.translations.edit_tip_desc') ?>
                </p>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- 번역 파일 생성 모달 -->
<div id="createFilesModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeCreateFilesModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="relative w-full max-w-md bg-white dark:bg-zinc-800 rounded-xl shadow-xl" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between p-4 border-b dark:border-zinc-700">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('settings.translations.create_modal_title') ?></h3>
                <button type="button" onclick="closeCreateFilesModal()" class="p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form method="POST" class="p-4 space-y-4">
                <input type="hidden" name="action" value="create_lang_files">
                <input type="hidden" name="target_locale" id="targetLocale">

                <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                    <p class="text-sm text-zinc-700 dark:text-zinc-300">
                        <strong id="targetLanguageName"></strong> - <?= __('settings.translations.create_modal_desc') ?>
                    </p>
                </div>

                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('settings.translations.source_language') ?></label>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('settings.translations.source_language_hint') ?></p>
                    <select name="source_locale" id="sourceLocale"
                            class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                        <?php foreach ($langFileStatus as $locale => $status): ?>
                        <?php if ($status['exists']): ?>
                        <option value="<?= htmlspecialchars($locale) ?>" <?= $locale === 'ko' ? 'selected' : '' ?>>
                            <?= htmlspecialchars($allLanguages[$locale]['native'] ?? $locale) ?> (<?= count($status['files']) ?>개 파일)
                        </option>
                        <?php endif; ?>
                        <?php endforeach; ?>
                    </select>
                </div>

                <div class="flex justify-end gap-3 pt-4 border-t dark:border-zinc-700">
                    <button type="button" onclick="closeCreateFilesModal()"
                            class="px-4 py-2 text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 rounded-lg hover:bg-zinc-200 dark:hover:bg-zinc-600 transition">
                        <?= __('settings.translations.cancel') ?>
                    </button>
                    <button type="submit"
                            class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                        <?= __('settings.translations.create') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- 삭제 확인 모달 -->
<div id="deleteModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeDeleteModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="relative w-full max-w-sm bg-white dark:bg-zinc-800 rounded-xl shadow-xl" onclick="event.stopPropagation()">
            <div class="p-6 text-center">
                <svg class="w-12 h-12 mx-auto mb-4 text-red-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                </svg>
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2"><?= __('settings.translations.delete_modal_title') ?></h3>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-6">
                    <strong id="deleteLanguageName"></strong> <?= __('settings.translations.delete_confirm') ?><br><?= __('settings.translations.delete_warning') ?>
                </p>
                <form method="POST" class="flex gap-3 justify-center">
                    <input type="hidden" name="action" value="delete_lang_files">
                    <input type="hidden" name="target_locale" id="deleteLocale">
                    <button type="button" onclick="closeDeleteModal()"
                            class="px-4 py-2 text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 rounded-lg hover:bg-zinc-200 dark:hover:bg-zinc-600 transition">
                        <?= __('settings.translations.cancel') ?>
                    </button>
                    <button type="submit" class="px-4 py-2 bg-red-600 text-white font-medium rounded-lg hover:bg-red-700 transition">
                        <?= __('settings.translations.delete') ?>
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>

<!-- 가져오기(업로드) 모달 -->
<div id="uploadModal" class="fixed inset-0 z-50 hidden">
    <div class="absolute inset-0 bg-black/50" onclick="closeUploadModal()"></div>
    <div class="absolute inset-0 flex items-center justify-center p-4">
        <div class="relative w-full max-w-md bg-white dark:bg-zinc-800 rounded-xl shadow-xl" onclick="event.stopPropagation()">
            <div class="flex items-center justify-between p-4 border-b dark:border-zinc-700">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('settings.translations.import_title') ?></h3>
                <button type="button" onclick="closeUploadModal()" class="p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <form method="POST" enctype="multipart/form-data" class="p-4 space-y-4">
                <input type="hidden" name="action" value="upload_lang_files">
                <input type="hidden" name="target_locale" id="uploadLocale">
                <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                    <p class="text-sm text-zinc-700 dark:text-zinc-300">
                        <strong id="uploadLanguageName"></strong> - <?= __('settings.translations.import_desc') ?>
                    </p>
                </div>
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('settings.translations.select_zip') ?></label>
                    <input type="file" name="lang_zip" accept=".zip" required
                           class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1"><?= __('settings.translations.import_hint') ?></p>
                </div>
                <div class="flex justify-end gap-3 pt-4 border-t dark:border-zinc-700">
                    <button type="button" onclick="closeUploadModal()"
                            class="px-4 py-2 text-zinc-700 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 rounded-lg hover:bg-zinc-200 dark:hover:bg-zinc-600 transition">
                        <?= __('settings.translations.cancel') ?>
                    </button>
                    <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                        <?= __('settings.translations.import') ?>
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
// 번역 파일 생성 모달
function openCreateFilesModal(locale, name) {
    document.getElementById('targetLocale').value = locale;
    document.getElementById('targetLanguageName').textContent = name;
    document.getElementById('createFilesModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeCreateFilesModal() {
    document.getElementById('createFilesModal').classList.add('hidden');
    document.body.style.overflow = '';
}

// 삭제 모달
function openDeleteModal(locale, name) {
    document.getElementById('deleteLocale').value = locale;
    document.getElementById('deleteLanguageName').textContent = name;
    document.getElementById('deleteModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    document.getElementById('deleteModal').classList.add('hidden');
    document.body.style.overflow = '';
}

// 가져오기 모달
function openUploadModal(locale, name) {
    document.getElementById('uploadLocale').value = locale;
    document.getElementById('uploadLanguageName').textContent = name;
    document.getElementById('uploadModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeUploadModal() {
    document.getElementById('uploadModal').classList.add('hidden');
    document.body.style.overflow = '';
}

// ESC 키로 모달 닫기
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        closeCreateFilesModal();
        closeDeleteModal();
        closeUploadModal();
    }
});
</script>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/_layout.php';
