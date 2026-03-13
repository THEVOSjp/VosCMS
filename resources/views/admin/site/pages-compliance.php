<?php
/**
 * RezlyX Admin - 데이터 관리 가이드 편집
 * 국가별/업종별 컴플라이언스 정보를 관리자가 확인하고 커스터마이징
 */
$pageTitle = __('admin.site.pages.compliance.title') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';

// Database connection
try {
    $pdo = new PDO(
        'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx'),
        $_ENV['DB_USERNAME'] ?? 'root',
        $_ENV['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('DB connection failed: ' . $e->getMessage());
}

$prefix = 'rzx_';
$message = '';
$messageType = '';

// Base URLs
$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');

// 현재 설정 로드
$settings = [];
$stmt = $pdo->query("SELECT `key`, `value` FROM {$prefix}settings");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $settings[$row['key']] = $row['value'];
}

$siteCountry = $settings['site_country'] ?? '';
$siteCategory = $settings['site_category'] ?? '';
$currentLocale = current_locale();

// ComplianceData 로드
require_once BASE_PATH . '/rzxlib/Core/Data/ComplianceData.php';
use RzxLib\Core\Data\ComplianceData;

$complianceData = ComplianceData::get($siteCountry, $siteCategory);
$supportedCountries = ComplianceData::getSupportedCountries();
$isDetailedCountry = in_array($siteCountry, $supportedCountries);

// 저장된 커스텀 콘텐츠 로드
$savedContents = [];
$stmt = $pdo->prepare("SELECT locale, title, content FROM {$prefix}page_contents WHERE page_slug = 'data-policy'");
$stmt->execute();
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $savedContents[$row['locale']] = $row;
}

// POST 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'save_compliance') {
        $locale = $_POST['locale'] ?? $currentLocale;
        $title = trim($_POST['page_title'] ?? '');
        $content = trim($_POST['page_content'] ?? '');
        $isActive = isset($_POST['is_active']) ? 1 : 0;

        try {
            $stmt = $pdo->prepare("INSERT INTO {$prefix}page_contents (page_slug, locale, title, content, is_system, is_active)
                VALUES ('data-policy', ?, ?, ?, 1, ?)
                ON DUPLICATE KEY UPDATE title = VALUES(title), content = VALUES(content), is_active = VALUES(is_active)");
            $stmt->execute([$locale, $title, $content, $isActive]);
            $message = __('admin.settings.success');
            $messageType = 'success';

            // 저장 후 다시 로드
            $savedContents[$locale] = ['locale' => $locale, 'title' => $title, 'content' => $content];
        } catch (PDOException $e) {
            $message = __('admin.settings.error_save') . ': ' . $e->getMessage();
            $messageType = 'error';
        }
    } elseif ($action === 'generate_default') {
        // 기본 콘텐츠 생성 (AJAX)
        header('Content-Type: application/json');
        $locale = $_POST['locale'] ?? 'ko';
        $country = $_POST['country'] ?? $siteCountry;
        $category = $_POST['category'] ?? $siteCategory;

        $data = ComplianceData::get($country, $category);
        echo json_encode(['success' => true, 'data' => $data, 'locale' => $locale]);
        exit;
    }
}

// 지원 언어 목록
$supportedLanguages = json_decode($settings['supported_languages'] ?? '["ko","en","ja"]', true) ?: ['ko', 'en', 'ja'];
$languageNames = [
    'ko' => '한국어', 'en' => 'English', 'ja' => '日本語',
    'zh_CN' => '简体中文', 'zh_TW' => '繁體中文', 'de' => 'Deutsch',
    'es' => 'Español', 'fr' => 'Français', 'id' => 'Bahasa Indonesia',
    'mn' => 'Монгол', 'ru' => 'Русский', 'tr' => 'Türkçe', 'vi' => 'Tiếng Việt',
];

// 업종 목록
$categoryKeys = ['beauty_salon', 'nail_salon', 'skincare', 'massage', 'hospital', 'dental', 'studio', 'restaurant', 'accommodation', 'sports', 'education', 'consulting', 'pet', 'car', 'other'];
$categoryNames = [];
foreach ($categoryKeys as $ck) {
    $categoryNames[$ck] = __('admin.settings.site.categories.' . $ck);
}

// 국가 목록
$countryNames = [
    'KR' => '🇰🇷 ' . __('admin.settings.site.country.kr'),
    'JP' => '🇯🇵 ' . __('admin.settings.site.country.jp'),
    'US' => '🇺🇸 ' . __('admin.settings.site.country.us'),
    'CN' => '🇨🇳 ' . __('admin.settings.site.country.cn'),
    'TW' => '🇹🇼 ' . __('admin.settings.site.country.tw'),
    'DE' => '🇩🇪 ' . __('admin.settings.site.country.de'),
    'FR' => '🇫🇷 ' . __('admin.settings.site.country.fr'),
    'ES' => '🇪🇸 ' . __('admin.settings.site.country.es'),
    'GB' => '🇬🇧 ' . __('admin.settings.site.country.gb'),
    'AU' => '🇦🇺 ' . __('admin.settings.site.country.au'),
    'ID' => '🇮🇩 ' . __('admin.settings.site.country.id'),
    'MN' => '🇲🇳 ' . __('admin.settings.site.country.mn'),
    'RU' => '🇷🇺 ' . __('admin.settings.site.country.ru'),
    'TR' => '🇹🇷 ' . __('admin.settings.site.country.tr'),
    'VN' => '🇻🇳 ' . __('admin.settings.site.country.vn'),
    'SG' => '🇸🇬 ' . __('admin.settings.site.country.sg'),
    'NZ' => '🇳🇿 ' . __('admin.settings.site.country.nz'),
];
?>
<!DOCTYPE html>
<html lang="<?= $config['locale'] ?? 'ko' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <!-- Summernote Editor -->
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
    <style>
        body { font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; }
        /* Summernote 스타일 */
        .note-editor { border-radius: 0.5rem; overflow: hidden; }
        .note-editor .note-toolbar { background: #f4f4f5; border-color: #d4d4d8; }
        .note-editor .note-editing-area { background: #fff; }
        .note-editor .note-editable { min-height: 200px; }
        .note-editor .note-statusbar { background: #f4f4f5; border-color: #d4d4d8; }
        .note-editor .note-editable h1 { font-size: 2em !important; font-weight: bold !important; margin: 0.67em 0 !important; }
        .note-editor .note-editable h2 { font-size: 1.5em !important; font-weight: bold !important; margin: 0.83em 0 !important; }
        .note-editor .note-editable h3 { font-size: 1.17em !important; font-weight: bold !important; margin: 1em 0 !important; }
        .note-editor .note-editable p { margin: 1em 0 !important; }
        .note-editor .note-editable ul, .note-editor .note-editable ol { margin: 1em 0 !important; padding-left: 2em !important; list-style: revert !important; }
        .note-editor .note-editable li { margin: 0.5em 0 !important; }
        .note-editor .note-editable table { border-collapse: collapse; width: 100%; }
        .note-editor .note-editable table th, .note-editor .note-editable table td { border: 1px solid #d4d4d8; padding: 0.5rem; }
        /* 다크 모드 */
        .dark .note-editor { border-color: #52525b; }
        .dark .note-editor .note-toolbar { background: #3f3f46; border-color: #52525b; }
        .dark .note-editor .note-toolbar .note-btn { color: #a1a1aa; background: transparent; border-color: #52525b; }
        .dark .note-editor .note-toolbar .note-btn:hover { color: #fff; background: #52525b; }
        .dark .note-editor .note-editing-area { background: #3f3f46; }
        .dark .note-editor .note-editable { color: #fff; background: #3f3f46; }
        .dark .note-editor .note-statusbar { background: #3f3f46; border-color: #52525b; }
        .dark .note-editor .note-codable { background: #27272a; color: #a1a1aa; }
        .dark .note-dropdown-menu { background: #3f3f46; border-color: #52525b; }
        .dark .note-dropdown-menu .note-dropdown-item { color: #a1a1aa; }
        .dark .note-dropdown-menu .note-dropdown-item:hover { background: #52525b; color: #fff; }
        /* 미리보기 모달 콘텐츠 스타일 */
        #previewBody { color: #18181b; line-height: 1.7; }
        #previewBody h1 { font-size: 1.5em; font-weight: bold; margin: 1em 0 0.5em; }
        #previewBody h2 { font-size: 1.25em; font-weight: bold; margin: 1em 0 0.5em; }
        #previewBody h3 { font-size: 1.1em; font-weight: bold; margin: 0.8em 0 0.4em; }
        #previewBody p { margin: 0.8em 0; }
        #previewBody ul, #previewBody ol { margin: 0.8em 0; padding-left: 1.5em; }
        #previewBody ul { list-style: disc; }
        #previewBody ol { list-style: decimal; }
        #previewBody li { margin: 0.3em 0; }
        #previewBody table { width: 100%; border-collapse: collapse; font-size: 0.875rem; margin: 1em 0; }
        #previewBody th { background: #f4f4f5; padding: 0.5rem 1rem; text-align: left; font-weight: 600; border: 1px solid #d4d4d8; }
        #previewBody td { padding: 0.5rem 1rem; border: 1px solid #d4d4d8; }
        #previewBody a { color: #2563eb; text-decoration: underline; }
        #previewBody hr { border-color: #d4d4d8; margin: 1.5em 0; }
        /* 다크 모드 미리보기 */
        .dark #previewBody { color: #e4e4e7; }
        .dark #previewBody th { background: #3f3f46; border-color: #52525b; }
        .dark #previewBody td { border-color: #52525b; }
        .dark #previewBody a { color: #60a5fa; }
        .dark #previewBody hr { border-color: #52525b; }
    </style>
    <script>
        if (localStorage.getItem('darkMode') === 'true' ||
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-zinc-100 dark:bg-zinc-900 min-h-screen transition-colors">
    <div class="flex">
        <?php include __DIR__ . '/../partials/admin-sidebar.php'; ?>

        <main class="flex-1 ml-64">
            <?php
            $pageHeaderTitle = __('admin.site.pages.compliance.title');
            include __DIR__ . '/../partials/admin-topbar.php';
            ?>

            <div class="p-6">
                <!-- Alert -->
                <div id="alertBox" class="mb-6 p-4 rounded-lg border hidden"></div>
                <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-300 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-300 border border-red-200 dark:border-red-800' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
                <?php endif; ?>

                <!-- Header -->
                <div class="mb-6">
                <?php
                $headerIcon = 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z';
                $headerTitle = __('admin.site.pages.compliance.title');
                $headerDescription = __('admin.site.pages.compliance.description');
                $headerIconColor = 'text-amber-600';
                $headerActions = '<a href="' . $adminUrl . '/site/pages" class="px-4 py-2 text-sm font-medium text-zinc-600 dark:text-zinc-300 bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-600 transition">' . __('admin.buttons.back') . '</a>';
                include __DIR__ . '/../components/settings-header.php';
                ?>
                </div>

                <?php if (!$siteCountry): ?>
                <div class="mb-6 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                    <p class="text-sm text-amber-700 dark:text-amber-300">
                        <?= __('admin.site.pages.compliance.no_country_warning') ?>
                        <a href="<?= $adminUrl ?>/settings/general" class="underline font-medium"><?= __('admin.site.pages.compliance.go_settings') ?></a>
                    </p>
                </div>
                <?php endif; ?>

                <!-- 콘텐츠 편집 -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6">
                    <!-- 상단: 설정 요약 + 언어 탭 -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-3">
                            <?php if ($siteCountry): ?>
                            <span class="text-xs text-zinc-500 dark:text-zinc-400">
                                <?= $countryNames[$siteCountry] ?? $siteCountry ?>
                                <?php if ($siteCategory): ?> · <?= $categoryNames[$siteCategory] ?? $siteCategory ?><?php endif; ?>
                            </span>
                            <?php if ($isDetailedCountry): ?>
                            <span class="px-1.5 py-0.5 text-[10px] font-medium rounded bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400"><?= __('admin.site.pages.compliance.detailed') ?></span>
                            <?php endif; ?>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 언어 탭 -->
                    <div class="flex flex-wrap gap-1 mb-4 border-b border-zinc-200 dark:border-zinc-700 pb-3">
                        <?php foreach ($supportedLanguages as $lang): ?>
                        <button type="button" data-lang="<?= $lang ?>"
                                class="lang-tab px-3 py-1.5 text-xs font-medium rounded-lg transition
                                       <?= $lang === $currentLocale ? 'bg-blue-600 text-white' : 'text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' ?>
                                       <?= isset($savedContents[$lang]) ? '' : 'opacity-60' ?>">
                            <?= $languageNames[$lang] ?? $lang ?>
                            <?php if (isset($savedContents[$lang])): ?>
                            <span class="ml-1 w-1.5 h-1.5 inline-block rounded-full bg-green-400"></span>
                            <?php endif; ?>
                        </button>
                        <?php endforeach; ?>
                    </div>

                    <!-- 편집 폼 -->
                    <form method="POST" id="complianceForm">
                        <input type="hidden" name="action" value="save_compliance">
                        <input type="hidden" name="locale" id="editLocale" value="<?= $currentLocale ?>">

                        <div class="space-y-4">
                            <div>
                                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.site.pages.compliance.page_title') ?></label>
                                <input type="text" name="page_title" id="editTitle"
                                       value="<?= htmlspecialchars($savedContents[$currentLocale]['title'] ?? '') ?>"
                                       placeholder="<?= __('admin.site.pages.compliance.title_placeholder') ?>"
                                       class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                            </div>

                            <div>
                                <div class="flex items-center justify-between mb-1">
                                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('admin.site.pages.compliance.page_content') ?></label>
                                    <button type="button" id="btnLoadDefault"
                                            class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                                        <?= __('admin.site.pages.compliance.load_default') ?>
                                    </button>
                                </div>
                                <textarea name="page_content" id="editContent"
                                          class="summernote-editor"><?= htmlspecialchars($savedContents[$currentLocale]['content'] ?? '') ?></textarea>
                            </div>

                            <div class="flex items-center gap-2">
                                <input type="checkbox" name="is_active" id="editActive" value="1" checked
                                       class="w-4 h-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                                <label for="editActive" class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('admin.site.pages.compliance.is_active') ?></label>
                            </div>
                        </div>

                        <div class="flex items-center justify-between mt-6 pt-4 border-t dark:border-zinc-700">
                            <button type="button" id="btnPreview"
                                    class="inline-flex items-center gap-1.5 text-sm text-zinc-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <?= __('admin.site.pages.compliance.preview') ?>
                            </button>
                            <button type="submit" class="px-5 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                                <?= __('admin.buttons.save') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <!-- 미리보기 모달 -->
    <div id="previewModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50" id="previewOverlay"></div>
        <div class="absolute inset-4 md:inset-8 lg:inset-12 bg-white dark:bg-zinc-900 rounded-2xl shadow-2xl flex flex-col overflow-hidden">
            <!-- 모달 헤더 -->
            <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-800">
                <div class="flex items-center gap-3">
                    <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                    </svg>
                    <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('admin.site.pages.compliance.preview') ?></span>
                </div>
                <button type="button" id="btnClosePreview" class="p-1.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                    </svg>
                </button>
            </div>
            <!-- 모달 콘텐츠 (고객 페이지 시뮬레이션) -->
            <div class="flex-1 overflow-y-auto">
                <div class="max-w-3xl mx-auto px-6 py-8">
                    <h1 id="previewTitle" class="text-2xl font-bold text-zinc-900 dark:text-white mb-6"></h1>
                    <div id="previewBody"></div>
                </div>
            </div>
        </div>
    </div>

    <!-- jQuery & Summernote -->
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/lang/summernote-ko-KR.min.js"></script>

    <?php include __DIR__ . '/pages-compliance-js.php'; ?>
</body>
</html>
