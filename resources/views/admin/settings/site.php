<?php
/**
 * RezlyX Admin Settings - Site Settings
 * 사이트 기본 설정 (사이트명, URL, 로고, SEO, 스크립트 등)
 */

// Initialize database and settings
require_once __DIR__ . '/_init.php';

$pageTitle = __('settings.site.page_title') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$currentSettingsPage = 'site';

// 타임존 목록
$timezones = [
    '' => __('settings.site.timezones.system_default'),
    'Asia/Seoul' => __('settings.site.timezones.asia_seoul'),
    'Asia/Tokyo' => __('settings.site.timezones.asia_tokyo'),
    'Asia/Shanghai' => __('settings.site.timezones.asia_shanghai'),
    'Asia/Hong_Kong' => __('settings.site.timezones.asia_hong_kong'),
    'Asia/Singapore' => __('settings.site.timezones.asia_singapore'),
    'America/New_York' => __('settings.site.timezones.america_new_york'),
    'America/Los_Angeles' => __('settings.site.timezones.america_los_angeles'),
    'America/Chicago' => __('settings.site.timezones.america_chicago'),
    'Europe/London' => __('settings.site.timezones.europe_london'),
    'Europe/Paris' => __('settings.site.timezones.europe_paris'),
    'Europe/Berlin' => __('settings.site.timezones.europe_berlin'),
    'Australia/Sydney' => __('settings.site.timezones.australia_sydney'),
    'Pacific/Auckland' => __('settings.site.timezones.pacific_auckland'),
];

// 언어 목록
$locales = [
    '' => __('settings.site.locales.system_default'),
    'ko' => __('settings.site.locales.ko'),
    'en' => __('settings.site.locales.en'),
    'ja' => __('settings.site.locales.ja'),
];

// 색상 조합 옵션
$colorSchemes = [
    'auto' => __('settings.site.color_schemes.auto'),
    'light' => __('settings.site.color_schemes.light'),
    'dark' => __('settings.site.color_schemes.dark'),
];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_site_settings') {
        // 기존 설정
        $siteCategory = $_POST['site_category'] ?? '';
        $siteName = trim($_POST['site_name'] ?? '');
        $siteTagline = trim($_POST['site_tagline'] ?? '');
        $siteUrl = trim($_POST['site_url'] ?? '');
        $homePage = trim($_POST['home_page'] ?? 'index');
        $logoType = $_POST['logo_type'] ?? 'text';

        // 새로 추가된 설정
        $defaultLocale = $_POST['default_locale'] ?? '';
        $forceLocale = isset($_POST['force_locale']) ? '1' : '0';
        $timezone = $_POST['timezone'] ?? '';
        $seoKeywords = trim($_POST['seo_keywords'] ?? '');
        $seoDescription = trim($_POST['seo_description'] ?? '');
        $headerScripts = $_POST['header_scripts'] ?? '';
        $footerScripts = $_POST['footer_scripts'] ?? '';
        $colorScheme = $_POST['color_scheme'] ?? 'auto';

        // 파일 업로드 처리
        $uploadDir = BASE_PATH . '/storage/';
        if (!is_dir($uploadDir)) {
            mkdir($uploadDir, 0755, true);
        }

        // 로고 이미지 업로드
        $logoImage = null;
        if (isset($_FILES['logo_image']) && $_FILES['logo_image']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'];
            if (in_array($_FILES['logo_image']['type'], $allowedTypes)) {
                $ext = pathinfo($_FILES['logo_image']['name'], PATHINFO_EXTENSION);
                $fileName = 'logo_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['logo_image']['tmp_name'], $uploadDir . 'logos/' . $fileName)) {
                    // 기존 로고 삭제
                    $oldLogo = $settings['logo_image'] ?? '';
                    if ($oldLogo && file_exists(BASE_PATH . $oldLogo)) @unlink(BASE_PATH . $oldLogo);
                    $logoImage = '/storage/logos/' . $fileName;
                }
            }
        }

        // 파비콘 업로드
        $favicon = null;
        if (isset($_FILES['favicon']) && $_FILES['favicon']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/x-icon', 'image/vnd.microsoft.icon', 'image/png', 'image/ico'];
            $ext = strtolower(pathinfo($_FILES['favicon']['name'], PATHINFO_EXTENSION));
            if (in_array($_FILES['favicon']['type'], $allowedTypes) || in_array($ext, ['ico', 'png'])) {
                $fileName = 'favicon_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['favicon']['tmp_name'], $uploadDir . $fileName)) {
                    $oldFavicon = $settings['favicon'] ?? '';
                    if ($oldFavicon && file_exists(BASE_PATH . $oldFavicon)) @unlink(BASE_PATH . $oldFavicon);
                    $favicon = '/storage/' . $fileName;
                }
            }
        }

        // 모바일 아이콘 업로드
        $mobileIcon = null;
        if (isset($_FILES['mobile_icon']) && $_FILES['mobile_icon']['error'] === UPLOAD_ERR_OK) {
            if ($_FILES['mobile_icon']['type'] === 'image/png') {
                $fileName = 'mobile_icon_' . time() . '.png';
                if (move_uploaded_file($_FILES['mobile_icon']['tmp_name'], $uploadDir . $fileName)) {
                    $oldIcon = $settings['mobile_icon'] ?? '';
                    if ($oldIcon && file_exists(BASE_PATH . $oldIcon)) @unlink(BASE_PATH . $oldIcon);
                    $mobileIcon = '/storage/' . $fileName;
                }
            }
        }

        // OG 이미지 업로드
        $ogImage = null;
        if (isset($_FILES['og_image']) && $_FILES['og_image']['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png'];
            if (in_array($_FILES['og_image']['type'], $allowedTypes)) {
                $ext = pathinfo($_FILES['og_image']['name'], PATHINFO_EXTENSION);
                $fileName = 'og_image_' . time() . '.' . $ext;
                if (move_uploaded_file($_FILES['og_image']['tmp_name'], $uploadDir . $fileName)) {
                    $oldOg = $settings['og_image'] ?? '';
                    if ($oldOg && file_exists(BASE_PATH . $oldOg)) @unlink(BASE_PATH . $oldOg);
                    $ogImage = '/storage/' . $fileName;
                }
            }
        }

        try {
            $stmt = $pdo->prepare("INSERT INTO rzx_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");

            // 기존 설정 저장
            $stmt->execute(['site_category', $siteCategory]);
            $stmt->execute(['site_name', $siteName]);
            $stmt->execute(['site_tagline', $siteTagline]);
            $stmt->execute(['site_url', $siteUrl]);
            $stmt->execute(['home_page', $homePage]);
            $stmt->execute(['logo_type', $logoType]);

            // 새로 추가된 설정 저장
            $stmt->execute(['default_locale', $defaultLocale]);
            $stmt->execute(['force_locale', $forceLocale]);
            $stmt->execute(['timezone', $timezone]);
            $stmt->execute(['seo_keywords', $seoKeywords]);
            $stmt->execute(['seo_description', $seoDescription]);
            $stmt->execute(['header_scripts', $headerScripts]);
            $stmt->execute(['footer_scripts', $footerScripts]);
            $stmt->execute(['color_scheme', $colorScheme]);

            // 파일 설정 저장
            if ($logoImage) {
                $stmt->execute(['logo_image', $logoImage]);
                $settings['logo_image'] = $logoImage;
            }
            if ($favicon) {
                $stmt->execute(['favicon', $favicon]);
                $settings['favicon'] = $favicon;
            }
            if ($mobileIcon) {
                $stmt->execute(['mobile_icon', $mobileIcon]);
                $settings['mobile_icon'] = $mobileIcon;
            }
            if ($ogImage) {
                $stmt->execute(['og_image', $ogImage]);
                $settings['og_image'] = $ogImage;
            }

            // 설정 배열 업데이트
            $settings['site_category'] = $siteCategory;
            $settings['site_name'] = $siteName;
            $settings['site_tagline'] = $siteTagline;
            $settings['site_url'] = $siteUrl;
            $settings['home_page'] = $homePage;
            $settings['logo_type'] = $logoType;
            $settings['default_locale'] = $defaultLocale;
            $settings['force_locale'] = $forceLocale;
            $settings['timezone'] = $timezone;
            $settings['seo_keywords'] = $seoKeywords;
            $settings['seo_description'] = $seoDescription;
            $settings['header_scripts'] = $headerScripts;
            $settings['footer_scripts'] = $footerScripts;
            $settings['color_scheme'] = $colorScheme;

            $message = __('settings.success');
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = __('settings.error_save') . ': ' . $e->getMessage();
            $messageType = 'error';
        }
    } elseif ($action === 'delete_logo') {
        $logoImage = $settings['logo_image'] ?? '';
        if ($logoImage && file_exists(BASE_PATH . $logoImage)) @unlink(BASE_PATH . $logoImage);
        $pdo->prepare("UPDATE rzx_settings SET `value` = '' WHERE `key` = 'logo_image'")->execute();
        $settings['logo_image'] = '';
        $message = __('settings.site.deleted.logo');
        $messageType = 'success';
    } elseif ($action === 'delete_favicon') {
        $favicon = $settings['favicon'] ?? '';
        if ($favicon && file_exists(BASE_PATH . $favicon)) @unlink(BASE_PATH . $favicon);
        $pdo->prepare("UPDATE rzx_settings SET `value` = '' WHERE `key` = 'favicon'")->execute();
        $settings['favicon'] = '';
        $message = __('settings.site.deleted.favicon');
        $messageType = 'success';
    } elseif ($action === 'delete_mobile_icon') {
        $icon = $settings['mobile_icon'] ?? '';
        if ($icon && file_exists(BASE_PATH . $icon)) @unlink(BASE_PATH . $icon);
        $pdo->prepare("UPDATE rzx_settings SET `value` = '' WHERE `key` = 'mobile_icon'")->execute();
        $settings['mobile_icon'] = '';
        $message = __('settings.site.deleted.mobile_icon');
        $messageType = 'success';
    } elseif ($action === 'delete_og_image') {
        $og = $settings['og_image'] ?? '';
        if ($og && file_exists(BASE_PATH . $og)) @unlink(BASE_PATH . $og);
        $pdo->prepare("UPDATE rzx_settings SET `value` = '' WHERE `key` = 'og_image'")->execute();
        $settings['og_image'] = '';
        $message = __('settings.site.deleted.og_image');
        $messageType = 'success';
    }
}

ob_start();
?>

<!-- Sub Navigation Tabs -->
<?php include __DIR__ . '/_settings_nav.php'; ?>

<!-- Site Settings -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
    <?php
    $headerIcon = 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z';
    $headerTitle = __('settings.site.title');
    $headerDescription = ''; $headerIconColor = ''; $headerActions = '';
    include __DIR__ . '/../components/settings-header.php';
    ?>
    <form method="POST" enctype="multipart/form-data" class="space-y-6">
        <input type="hidden" name="action" value="update_site_settings">

        <!-- Site Category -->
        <div>
            <label for="site_category" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.site.category_label') ?></label>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('settings.site.category_description') ?></p>
            <?php
            $currentCategory = $settings['site_category'] ?? '';
            $categoryKeys = ['beauty_salon', 'nail_salon', 'skincare', 'massage', 'hospital', 'dental', 'studio', 'restaurant', 'accommodation', 'sports', 'education', 'consulting', 'pet', 'car', 'corporate', 'shopping', 'law_firm', 'accounting', 'real_estate', 'it_tech', 'media', 'nonprofit', 'government', 'community', 'portfolio', 'other'];
            $categories = ['' => __('settings.site.category_placeholder')];
            foreach ($categoryKeys as $key) {
                $categories[$key] = __('settings.site.categories.' . $key);
            }
            ?>
            <select name="site_category" id="site_category"
                    class="w-full md:w-1/2 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                <?php foreach ($categories as $value => $label): ?>
                <option value="<?= $value ?>" <?= $currentCategory === $value ? 'selected' : '' ?>><?= htmlspecialchars($label) ?></option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Site Name -->
            <div>
                <label for="site_name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.site.name') ?></label>
                <div class="flex items-center gap-2">
                    <input type="text" name="site_name" id="site_name" value="<?= htmlspecialchars($settings['site_name'] ?? '') ?>"
                           class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                    <?= rzx_multilang_btn("openMultilangModal('site.name', 'site_name')") ?>
                </div>
            </div>
            <div>
                <label for="site_url" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.site.url') ?></label>
                <input type="url" name="site_url" id="site_url" value="<?= htmlspecialchars($settings['site_url'] ?? '') ?>"
                       class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
            </div>
        </div>

        <!-- Site Tagline + Home Page (2열 배치) -->
        <?php
        $currentHome = $settings['home_page'] ?? 'index';
        $currentHomeLabel = $currentHome;
        $__sitemaps = [];
        $__menusByMap = [];
        try {
            $__sitemaps = $pdo->query("SELECT * FROM rzx_sitemaps ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
            $__allMenus = $pdo->query("SELECT * FROM rzx_menu_items WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
            foreach ($__allMenus as $__mi) {
                $__menusByMap[$__mi['sitemap_id']][] = $__mi;
            }
            foreach ($__allMenus as $__mi) {
                $__slug = $__mi['url'] ?? '';
                if ($__slug === '/' . $currentHome || $__slug === $currentHome) {
                    $currentHomeLabel = $__mi['title'] . ' (' . $currentHome . ')';
                    break;
                }
            }
        } catch (\Throwable $e) {}
        if (!function_exists('__buildHomeMenuTree')) {
            function __buildHomeMenuTree($items, $parentId = null) {
                $tree = [];
                foreach ($items as $item) {
                    if ($item['parent_id'] == $parentId) {
                        $item['children'] = __buildHomeMenuTree($items, $item['id']);
                        $tree[] = $item;
                    }
                }
                return $tree;
            }
        }
        ?>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="site_tagline" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.site.tagline') ?></label>
                <div class="flex items-center gap-2">
                    <input type="text" name="site_tagline" id="site_tagline" value="<?= htmlspecialchars($settings['site_tagline'] ?? '') ?>"
                           class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                    <?= rzx_multilang_btn("openMultilangModal('site.tagline', 'site_tagline')") ?>
                </div>
            </div>
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.site.home_page') ?></label>
                <div class="flex items-center gap-2">
                    <input type="hidden" name="home_page" id="home_page" value="<?= htmlspecialchars($currentHome) ?>">
                    <input type="text" id="home_page_display" value="<?= htmlspecialchars($currentHomeLabel) ?>" readonly
                           class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg cursor-pointer focus:ring-2 focus:ring-blue-500"
                           onclick="document.getElementById('homePageModal').classList.remove('hidden')">
                    <button type="button" onclick="document.getElementById('homePageModal').classList.remove('hidden')"
                            class="px-3 py-2 bg-zinc-100 dark:bg-zinc-600 border border-zinc-300 dark:border-zinc-500 rounded-lg hover:bg-zinc-200 dark:hover:bg-zinc-500 text-sm">
                        <?= __('settings.site.home_page_select') ?? '선택' ?>
                    </button>
                </div>
            </div>
        </div>

        <!-- 메뉴 선택 모달 -->
        <div id="homePageModal" class="hidden fixed inset-0 z-[9999] flex items-center justify-center bg-black/50" onclick="if(event.target===this)this.classList.add('hidden')">
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-2xl w-full max-w-md max-h-[70vh] flex flex-col" onclick="event.stopPropagation()">
                <!-- 헤더 -->
                <div class="flex items-center justify-between px-5 py-3 border-b dark:border-zinc-700 bg-zinc-700 dark:bg-zinc-900 rounded-t-xl">
                    <h3 class="text-base font-semibold text-white"><?= __('settings.site.home_page_modal_title') ?? '대상 메뉴 선택' ?></h3>
                    <button type="button" onclick="document.getElementById('homePageModal').classList.add('hidden')"
                            class="text-zinc-300 hover:text-white text-xl leading-none">&times;</button>
                </div>
                <!-- 트리 목록 -->
                <div class="flex-1 overflow-y-auto px-5 py-4">
                    <?php foreach ($__sitemaps as $__sm): ?>
                    <div class="mb-4">
                        <div class="flex items-center gap-2 mb-2">
                            <svg class="w-4 h-4 text-amber-500" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>
                            <span class="text-sm font-medium text-zinc-400 dark:text-zinc-500"><?= htmlspecialchars($__sm['title']) ?></span>
                        </div>
                        <?php
                        $__items = $__menusByMap[$__sm['id']] ?? [];
                        $__tree = __buildHomeMenuTree($__items);

                        // 재귀 렌더링
                        if (!function_exists('__renderHomeMenuTree')) {
                            function __renderHomeMenuTree($nodes, $depth, $currentHome) {
                                foreach ($nodes as $node) {
                                    $slug = ltrim($node['url'] ?? '', '/');
                                    if (!$slug) $slug = $node['title'] ?? '';
                                    $isSelected = ($slug === $currentHome);
                                    $pad = ($depth + 1) * 16;
                                    echo '<div class="home-menu-item flex items-center gap-1 py-1.5 px-2 rounded-md cursor-pointer hover:bg-blue-50 dark:hover:bg-zinc-700 transition-colors' . ($isSelected ? ' bg-blue-100 dark:bg-zinc-600 font-semibold' : '') . '"';
                                    echo ' style="padding-left:' . $pad . 'px"';
                                    echo ' data-slug="' . htmlspecialchars($slug) . '"';
                                    echo ' data-label="' . htmlspecialchars($node['title'] . ' (' . $slug . ')') . '"';
                                    echo ' onclick="selectHomePage(this)">';
                                    echo '<span class="text-zinc-300 dark:text-zinc-600 text-xs">└</span>';
                                    echo '<span class="text-sm text-zinc-800 dark:text-zinc-200 hover:text-blue-600 dark:hover:text-blue-400">' . htmlspecialchars($node['title']) . '</span>';
                                    echo '</div>';
                                    if (!empty($node['children'])) {
                                        __renderHomeMenuTree($node['children'], $depth + 1, $currentHome);
                                    }
                                }
                            }
                        }
                        __renderHomeMenuTree($__tree, 0, $currentHome);
                        ?>
                    </div>
                    <?php endforeach; ?>
                </div>
                <!-- 푸터 -->
                <div class="flex items-center justify-between px-5 py-3 border-t dark:border-zinc-700">
                    <button type="button" onclick="document.getElementById('homePageModal').classList.add('hidden')"
                            class="px-4 py-1.5 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 dark:text-zinc-300"><?= __('common.cancel') ?? '취소' ?></button>
                    <button type="button" id="homePageConfirmBtn" onclick="confirmHomePage()"
                            class="px-4 py-1.5 text-sm bg-red-500 hover:bg-red-600 text-white rounded-lg font-medium"><?= __('common.confirm') ?? '확인' ?></button>
                </div>
            </div>
        </div>

        <script>
        let _selectedHomeSlug = '<?= htmlspecialchars($currentHome) ?>';
        let _selectedHomeLabel = '<?= htmlspecialchars($currentHomeLabel) ?>';

        function selectHomePage(el) {
            document.querySelectorAll('.home-menu-item').forEach(i => {
                i.classList.remove('bg-blue-100', 'dark:bg-zinc-600', 'font-semibold');
            });
            el.classList.add('bg-blue-100', 'dark:bg-zinc-600', 'font-semibold');
            _selectedHomeSlug = el.dataset.slug;
            _selectedHomeLabel = el.dataset.label;
        }

        function confirmHomePage() {
            document.getElementById('home_page').value = _selectedHomeSlug;
            document.getElementById('home_page_display').value = _selectedHomeLabel;
            document.getElementById('homePageModal').classList.add('hidden');
        }
        </script>

        <!-- Language & Timezone -->
        <div class="border-t dark:border-zinc-700 pt-6">
            <h3 class="text-md font-semibold text-zinc-900 dark:text-white mb-4"><?= __('settings.site.language_timezone.title') ?></h3>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="default_locale" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.site.language_timezone.default_locale') ?></label>
                    <div class="flex items-center gap-4">
                        <select name="default_locale" id="default_locale" class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                            <?php foreach ($locales as $value => $label): ?>
                            <option value="<?= $value ?>" <?= ($settings['default_locale'] ?? '') === $value ? 'selected' : '' ?>><?= $label ?></option>
                            <?php endforeach; ?>
                        </select>
                        <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300 whitespace-nowrap">
                            <input type="checkbox" name="force_locale" value="1" <?= ($settings['force_locale'] ?? '0') === '1' ? 'checked' : '' ?>
                                   class="w-4 h-4 text-blue-600 border-zinc-300 rounded focus:ring-blue-500">
                            <?= __('settings.site.language_timezone.force_locale') ?>
                        </label>
                    </div>
                </div>
                <div>
                    <label for="timezone" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.site.language_timezone.timezone') ?></label>
                    <select name="timezone" id="timezone" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                        <?php foreach ($timezones as $value => $label): ?>
                        <option value="<?= $value ?>" <?= ($settings['timezone'] ?? '') === $value ? 'selected' : '' ?>><?= $label ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>

        </div>

        <!-- SEO Settings -->
        <div class="border-t dark:border-zinc-700 pt-6">
            <h3 class="text-md font-semibold text-zinc-900 dark:text-white mb-4"><?= __('settings.site.seo.title') ?></h3>
            <div class="space-y-4">
                <div>
                    <label for="seo_keywords" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.site.seo.keywords') ?></label>
                    <div class="flex items-center gap-2">
                        <input type="text" name="seo_keywords" id="seo_keywords" value="<?= htmlspecialchars($settings['seo_keywords'] ?? '') ?>"
                               class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500"
                               placeholder="<?= __('settings.site.seo.keywords_placeholder') ?>">
                        <?= rzx_multilang_btn("openMultilangModal('seo.keywords', 'seo_keywords')") ?>
                    </div>
                </div>
                <div>
                    <label for="seo_description" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.site.seo.description') ?></label>
                    <div class="flex items-center gap-2">
                        <input type="text" name="seo_description" id="seo_description" value="<?= htmlspecialchars($settings['seo_description'] ?? '') ?>"
                               class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500"
                               placeholder="<?= __('settings.site.seo.description_placeholder') ?>">
                        <?= rzx_multilang_btn("openMultilangModal('seo.description', 'seo_description')") ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Scripts -->
        <div class="border-t dark:border-zinc-700 pt-6">
            <h3 class="text-md font-semibold text-zinc-900 dark:text-white mb-4"><?= __('settings.site.scripts.title') ?></h3>
            <div class="space-y-4">
                <div>
                    <label for="header_scripts" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.site.scripts.header') ?></label>
                    <textarea name="header_scripts" id="header_scripts" rows="4"
                              class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 font-mono text-sm"
                              placeholder="<?= __('settings.site.scripts.placeholder') ?>"><?= htmlspecialchars($settings['header_scripts'] ?? '') ?></textarea>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1"><?= __('settings.site.scripts.header_hint') ?></p>
                </div>
                <div>
                    <label for="footer_scripts" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.site.scripts.footer') ?></label>
                    <textarea name="footer_scripts" id="footer_scripts" rows="4"
                              class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 font-mono text-sm"
                              placeholder="<?= __('settings.site.scripts.placeholder') ?>"><?= htmlspecialchars($settings['footer_scripts'] ?? '') ?></textarea>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1"><?= __('settings.site.scripts.footer_hint') ?></p>
                </div>
            </div>
        </div>

        <!-- Logo Settings -->
        <div class="border-t dark:border-zinc-700 pt-6">
            <h3 class="text-md font-semibold text-zinc-900 dark:text-white mb-4"><?= __('settings.logo.title') ?></h3>
            <!-- Logo Type -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('settings.logo.type_label') ?></label>
                <div class="flex flex-wrap gap-4">
                    <?php $currentLogoType = $settings['logo_type'] ?? 'text'; ?>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="logo_type" value="text" <?= $currentLogoType === 'text' ? 'checked' : '' ?> class="w-4 h-4 text-blue-600">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('settings.logo.type_text') ?></span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="logo_type" value="image" <?= $currentLogoType === 'image' ? 'checked' : '' ?> class="w-4 h-4 text-blue-600">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('settings.logo.type_image') ?></span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="logo_type" value="image_text" <?= $currentLogoType === 'image_text' ? 'checked' : '' ?> class="w-4 h-4 text-blue-600">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('settings.logo.type_image_text') ?></span>
                    </label>
                </div>
            </div>
            <!-- Logo Upload -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('settings.logo.image_label') ?></label>
                <?php if (!empty($settings['logo_image'])): ?>
                <div class="mb-3 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg inline-flex items-center gap-4">
                    <img src="<?= $baseUrl . htmlspecialchars($settings['logo_image']) ?>" alt="<?= __('settings.site.images.current_logo') ?>" class="max-h-12 object-contain">
                    <button type="button" onclick="deleteFile('logo')" class="text-sm text-red-600 hover:text-red-700"><?= __('admin.buttons.delete') ?></button>
                </div>
                <?php endif; ?>
                <input type="file" name="logo_image" accept="image/*" class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
            </div>
        </div>

        <!-- Site Images -->
        <div class="border-t dark:border-zinc-700 pt-6">
            <h3 class="text-md font-semibold text-zinc-900 dark:text-white mb-4"><?= __('settings.site.images.title') ?></h3>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Favicon -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('settings.site.images.favicon') ?></label>
                    <?php if (!empty($settings['favicon'])): ?>
                    <div class="mb-3 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg inline-flex items-center gap-4">
                        <img src="<?= $baseUrl . htmlspecialchars($settings['favicon']) ?>" alt="Favicon" class="w-8 h-8 object-contain">
                        <button type="button" onclick="deleteFile('favicon')" class="text-sm text-red-600 hover:text-red-700"><?= __('admin.buttons.delete') ?></button>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="favicon" accept=".ico,.png" class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1"><?= __('settings.site.images.favicon_hint') ?></p>
                </div>
                <!-- Mobile Icon -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('settings.site.images.mobile_icon') ?></label>
                    <?php if (!empty($settings['mobile_icon'])): ?>
                    <div class="mb-3 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg inline-flex items-center gap-4">
                        <img src="<?= $baseUrl . htmlspecialchars($settings['mobile_icon']) ?>" alt="Mobile Icon" class="w-12 h-12 object-contain rounded-lg">
                        <button type="button" onclick="deleteFile('mobile_icon')" class="text-sm text-red-600 hover:text-red-700"><?= __('admin.buttons.delete') ?></button>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="mobile_icon" accept=".png" class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1"><?= __('settings.site.images.mobile_icon_hint') ?></p>
                </div>
                <!-- OG Image -->
                <div>
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('settings.site.images.og_image') ?></label>
                    <?php if (!empty($settings['og_image'])): ?>
                    <div class="mb-3 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg inline-flex items-center gap-4">
                        <img src="<?= $baseUrl . htmlspecialchars($settings['og_image']) ?>" alt="OG Image" class="w-20 h-12 object-cover rounded">
                        <button type="button" onclick="deleteFile('og_image')" class="text-sm text-red-600 hover:text-red-700"><?= __('admin.buttons.delete') ?></button>
                    </div>
                    <?php endif; ?>
                    <input type="file" name="og_image" accept=".jpg,.jpeg,.png" class="block w-full text-sm text-zinc-500 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 cursor-pointer">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1"><?= __('settings.site.images.og_image_hint') ?></p>
                </div>
            </div>
        </div>

        <!-- Color Scheme -->
        <div class="border-t dark:border-zinc-700 pt-6">
            <h3 class="text-md font-semibold text-zinc-900 dark:text-white mb-4"><?= __('settings.site.theme.title') ?></h3>
            <div>
                <label for="color_scheme" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.site.theme.color_scheme') ?></label>
                <select name="color_scheme" id="color_scheme" class="w-full md:w-1/2 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                    <?php foreach ($colorSchemes as $value => $label): ?>
                    <option value="<?= $value ?>" <?= ($settings['color_scheme'] ?? 'auto') === $value ? 'selected' : '' ?>><?= $label ?></option>
                    <?php endforeach; ?>
                </select>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1"><?= __('settings.site.theme.color_scheme_hint') ?></p>
            </div>
        </div>

        <div class="flex justify-end pt-4 border-t dark:border-zinc-700">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                <?= __('admin.buttons.save') ?>
            </button>
        </div>
    </form>
</div>

<script>
function deleteFile(type) {
    const actions = {
        'logo': 'delete_logo',
        'favicon': 'delete_favicon',
        'mobile_icon': 'delete_mobile_icon',
        'og_image': 'delete_og_image'
    };
    if (confirm('<?= __('settings.site.delete_confirm') ?>')) {
        const form = document.createElement('form');
        form.method = 'POST';
        form.innerHTML = '<input type="hidden" name="action" value="' + actions[type] + '">';
        document.body.appendChild(form);
        form.submit();
    }
}
</script>

<!-- 다국어 모달 컴포넌트 -->
<?php include __DIR__ . '/../components/multilang-modal.php'; ?>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/_layout.php';
