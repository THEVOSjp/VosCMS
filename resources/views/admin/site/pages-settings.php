<?php
/**
 * RezlyX Admin - 페이지 환경 설정
 * 게시판 설정과 유사한 탭 구조 (기본 정보, 레이아웃, 스킨, SEO, 권한)
 */
if (!function_exists('__')) {
    require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';
}

$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
$pageSlug = $_GET['slug'] ?? '';

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
    $defaultLocale = $config['locale'] ?? 'ko';

    // AJAX 처리
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false || !empty($_SERVER['HTTP_X_REQUESTED_WITH']))) {
        header('Content-Type: application/json; charset=utf-8');
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';

        if ($action === 'save_settings') {
            $slug = $input['slug'] ?? '';
            $settings = $input['settings'] ?? [];

            // 페이지 설정은 rzx_page_contents의 별도 컬럼 또는 JSON 필드에 저장
            // 현재는 rzx_settings에 page_config_{slug}로 저장
            $configKey = 'page_config_' . $slug;
            $configJson = json_encode($settings, JSON_UNESCAPED_UNICODE);
            $stmt = $pdo->prepare("INSERT INTO {$prefix}settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
            $stmt->execute([$configKey, $configJson]);
            echo json_encode(['success' => true, 'message' => '설정이 저장되었습니다.']);
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        exit;
    }

    // 페이지 데이터 로드
    $stmt = $pdo->prepare("SELECT * FROM {$prefix}page_contents WHERE page_slug = ? LIMIT 1");
    $stmt->execute([$pageSlug]);
    $pageData = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$pageData) {
        header("Location: {$adminUrl}/site/pages");
        exit;
    }

    // 페이지 설정 로드
    $pageConfigKey = 'page_config_' . $pageSlug;
    $cfgStmt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = ?");
    $cfgStmt->execute([$pageConfigKey]);
    $pageConfig = json_decode($cfgStmt->fetchColumn() ?: '{}', true) ?: [];

    // 레이아웃 목록
    $layouts = ['default' => __('site.pages.layout_default') ?? '기본 레이아웃'];
    $layoutDir = BASE_PATH . '/skins/default/layouts/';
    if (is_dir($layoutDir)) {
        foreach (glob($layoutDir . '*/layout.json') as $lf) {
            $ld = json_decode(file_get_contents($lf), true);
            $lName = basename(dirname($lf));
            $layouts[$lName] = $ld['title'][$defaultLocale] ?? $ld['title']['en'] ?? $lName;
        }
    }

    // 스킨 목록
    $skins = [];
    $skinsDir = BASE_PATH . '/skins/';
    if (is_dir($skinsDir)) {
        foreach (scandir($skinsDir) as $sd) {
            if ($sd === '.' || $sd === '..') continue;
            $sjPath = $skinsDir . $sd . '/board/skin.json';
            if (file_exists($sjPath)) {
                $sj = json_decode(file_get_contents($sjPath), true);
                $skins[$sd] = $sj['title'][$defaultLocale] ?? $sj['title']['en'] ?? $sd;
            } else {
                $skins[$sd] = $sd;
            }
        }
    }

    $currentTab = $_GET['tab'] ?? 'basic';

} catch (PDOException $e) {
    die('DB Error: ' . $e->getMessage());
}

$pageTitle = htmlspecialchars($pageData['title'] ?? $pageSlug) . ' - ' . (__('site.pages.settings_title') ?? '페이지 설정');
$pageHeaderTitle = __('site.pages.settings_title') ?? '페이지 설정';
?>
<!DOCTYPE html>
<html lang="<?= $config['locale'] ?? 'ko' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $pageTitle ?></title>
    <?php include __DIR__ . '/../partials/pwa-head.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <style>body { font-family: 'Pretendard', sans-serif; }</style>
    <script>
        if (localStorage.getItem('darkMode') === 'true' || (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-zinc-100 dark:bg-zinc-900 min-h-screen transition-colors">
    <div class="flex">
        <?php include BASE_PATH . '/resources/views/admin/partials/admin-sidebar.php'; ?>
        <main class="flex-1 ml-64">
            <?php include BASE_PATH . '/resources/views/admin/partials/admin-topbar.php'; ?>
            <div class="p-6">
                <!-- 헤더 -->
                <div class="mb-6">
                    <div class="flex items-center gap-3 mb-2">
                        <a href="<?= $adminUrl ?>/site/pages" class="text-zinc-400 hover:text-blue-600">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </a>
                        <div>
                            <h1 class="text-xl font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($pageData['title'] ?? $pageSlug) ?> — <?= __('site.pages.settings_title') ?? '환경 설정' ?></h1>
                            <p class="text-sm text-zinc-500 dark:text-zinc-400">/<?= htmlspecialchars($pageSlug) ?> · <?= ucfirst($pageData['page_type'] ?? 'document') ?></p>
                        </div>
                    </div>
                </div>

                <div id="msgArea"></div>

                <!-- 탭 -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm mb-6 overflow-hidden">
                    <div class="border-b border-zinc-200 dark:border-zinc-700">
                        <nav class="flex -mb-px overflow-x-auto">
                            <?php
                            $tabs = [
                                'basic' => ['label' => __('site.pages.tab_basic') ?? '기본 설정', 'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z'],
                                'layout' => ['label' => __('site.pages.tab_layout') ?? '레이아웃', 'icon' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z'],
                                'seo' => ['label' => 'SEO', 'icon' => 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z'],
                            ];
                            foreach ($tabs as $key => $tab):
                                $isActive = $currentTab === $key;
                                $url = $adminUrl . '/site/pages/settings?slug=' . urlencode($pageSlug) . '&tab=' . $key;
                            ?>
                            <a href="<?= $url ?>" class="flex items-center px-4 py-4 text-sm font-medium border-b-2 whitespace-nowrap <?= $isActive ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-zinc-500 hover:text-zinc-700 dark:text-zinc-400' ?>">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $tab['icon'] ?>"/>
                                </svg>
                                <?= $tab['label'] ?>
                            </a>
                            <?php endforeach; ?>
                        </nav>
                    </div>
                </div>

                <!-- 탭 콘텐츠 -->
                <?php if ($currentTab === 'basic'): ?>
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700 p-6 space-y-5">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('site.pages.tab_basic') ?? '기본 설정' ?></h3>

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.pages.page_type') ?? '페이지 타입' ?></label>
                            <div class="px-3 py-2 bg-zinc-50 dark:bg-zinc-700/50 border rounded-lg text-sm text-zinc-700 dark:text-zinc-300">
                                <?= ucfirst($pageData['page_type'] ?? 'document') ?>
                            </div>
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Slug (URL)</label>
                            <div class="px-3 py-2 bg-zinc-50 dark:bg-zinc-700/50 border rounded-lg text-sm text-zinc-700 dark:text-zinc-300">
                                /<?= htmlspecialchars($pageSlug) ?>
                            </div>
                        </div>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.pages.page_title_setting') ?? '페이지 제목 (브라우저 타이틀)' ?></label>
                        <input type="text" id="cfgBrowserTitle" value="<?= htmlspecialchars($pageConfig['browser_title'] ?? '') ?>" placeholder="<?= htmlspecialchars($pageData['title'] ?? '') ?>" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm">
                        <p class="text-xs text-zinc-400 mt-1">비워두면 페이지 제목을 사용합니다.</p>
                    </div>

                    <div class="flex items-center justify-between">
                        <div>
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('site.pages.show_title') ?? '페이지 제목 표시' ?></label>
                            <p class="text-xs text-zinc-400">페이지 상단에 제목을 표시합니다.</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="cfgShowTitle" class="sr-only peer" <?= ($pageConfig['show_title'] ?? true) ? 'checked' : '' ?>>
                            <div class="w-11 h-6 bg-zinc-200 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    <div class="flex items-center justify-between">
                        <div>
                            <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('site.pages.full_width') ?? '전체 너비' ?></label>
                            <p class="text-xs text-zinc-400">콘텐츠를 전체 너비로 표시합니다. (기본: max-w-5xl)</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" id="cfgFullWidth" class="sr-only peer" <?= ($pageConfig['full_width'] ?? false) ? 'checked' : '' ?>>
                            <div class="w-11 h-6 bg-zinc-200 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    <div class="flex justify-end">
                        <button onclick="saveSettings()" class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium"><?= __('common.buttons.save') ?? '저장' ?></button>
                    </div>
                </div>

                <?php elseif ($currentTab === 'layout'): ?>
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700 p-6 space-y-5">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('site.pages.tab_layout') ?? '레이아웃' ?></h3>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.pages.select_layout') ?? '레이아웃 선택' ?></label>
                        <select id="cfgLayout" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm">
                            <?php foreach ($layouts as $lk => $lv): ?>
                            <option value="<?= $lk ?>" <?= ($pageConfig['layout'] ?? 'default') === $lk ? 'selected' : '' ?>><?= htmlspecialchars($lv) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.pages.select_skin') ?? '스킨 선택' ?></label>
                        <select id="cfgSkin" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm">
                            <option value=""><?= __('site.pages.skin_none') ?? '스킨 없음 (기본)' ?></option>
                            <?php foreach ($skins as $sk => $sv): ?>
                            <option value="<?= $sk ?>" <?= ($pageConfig['skin'] ?? '') === $sk ? 'selected' : '' ?>><?= htmlspecialchars($sv) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.pages.custom_css') ?? '커스텀 CSS' ?></label>
                        <textarea id="cfgCustomCss" rows="5" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm font-mono" placeholder=".page-content { ... }"><?= htmlspecialchars($pageConfig['custom_css'] ?? '') ?></textarea>
                    </div>

                    <div class="flex justify-end">
                        <button onclick="saveSettings()" class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium"><?= __('common.buttons.save') ?? '저장' ?></button>
                    </div>
                </div>

                <?php elseif ($currentTab === 'seo'): ?>
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700 p-6 space-y-5">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">SEO</h3>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Meta Title</label>
                        <input type="text" id="cfgMetaTitle" value="<?= htmlspecialchars($pageConfig['meta_title'] ?? '') ?>" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm" placeholder="페이지 제목과 다른 SEO 제목">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Meta Description</label>
                        <textarea id="cfgMetaDesc" rows="3" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm"><?= htmlspecialchars($pageConfig['meta_description'] ?? '') ?></textarea>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Meta Keywords</label>
                        <input type="text" id="cfgMetaKeywords" value="<?= htmlspecialchars($pageConfig['meta_keywords'] ?? '') ?>" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm" placeholder="키워드1, 키워드2">
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">Robots</label>
                        <select id="cfgRobots" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm">
                            <option value="index,follow" <?= ($pageConfig['robots'] ?? '') === 'index,follow' ? 'selected' : '' ?>>index, follow (기본)</option>
                            <option value="noindex,follow" <?= ($pageConfig['robots'] ?? '') === 'noindex,follow' ? 'selected' : '' ?>>noindex, follow</option>
                            <option value="index,nofollow" <?= ($pageConfig['robots'] ?? '') === 'index,nofollow' ? 'selected' : '' ?>>index, nofollow</option>
                            <option value="noindex,nofollow" <?= ($pageConfig['robots'] ?? '') === 'noindex,nofollow' ? 'selected' : '' ?>>noindex, nofollow</option>
                        </select>
                    </div>

                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">OG Image URL</label>
                        <input type="text" id="cfgOgImage" value="<?= htmlspecialchars($pageConfig['og_image'] ?? '') ?>" class="w-full px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm" placeholder="https://...">
                    </div>

                    <div class="flex justify-end">
                        <button onclick="saveSettings()" class="px-5 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium"><?= __('common.buttons.save') ?? '저장' ?></button>
                    </div>
                </div>
                <?php endif; ?>

                <!-- 하단 바로가기 -->
                <div class="mt-6 flex items-center gap-3">
                    <a href="<?= $adminUrl ?>/site/pages/edit-content?slug=<?= urlencode($pageSlug) ?>" class="px-4 py-2 text-sm text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
                        <?= __('site.pages.edit_content') ?? '콘텐츠 편집' ?> →
                    </a>
                    <a href="<?= $baseUrl ?>/<?= htmlspecialchars($pageSlug) ?>" target="_blank" class="px-4 py-2 text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg border border-zinc-200 dark:border-zinc-700">
                        <?= __('site.pages.document.preview') ?? '미리보기' ?> →
                    </a>
                </div>
            </div>
        </main>
    </div>

<script>
var PAGE_URL = '<?= $adminUrl ?>/site/pages/settings';
var SLUG = '<?= htmlspecialchars($pageSlug) ?>';

async function saveSettings() {
    var settings = {
        browser_title: document.getElementById('cfgBrowserTitle')?.value || '',
        show_title: document.getElementById('cfgShowTitle')?.checked ? true : false,
        full_width: document.getElementById('cfgFullWidth')?.checked ? true : false,
        layout: document.getElementById('cfgLayout')?.value || 'default',
        skin: document.getElementById('cfgSkin')?.value || '',
        custom_css: document.getElementById('cfgCustomCss')?.value || '',
        meta_title: document.getElementById('cfgMetaTitle')?.value || '',
        meta_description: document.getElementById('cfgMetaDesc')?.value || '',
        meta_keywords: document.getElementById('cfgMetaKeywords')?.value || '',
        robots: document.getElementById('cfgRobots')?.value || '',
        og_image: document.getElementById('cfgOgImage')?.value || '',
    };

    try {
        var res = await fetch(PAGE_URL + '?slug=' + SLUG, {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
            body: JSON.stringify({ action: 'save_settings', slug: SLUG, settings: settings })
        });
        var data = await res.json();
        var area = document.getElementById('msgArea');
        var cls = data.success ? 'bg-green-50 text-green-800 border-green-200' : 'bg-red-50 text-red-800 border-red-200';
        area.innerHTML = '<div class="mb-4 p-3 rounded-lg border ' + cls + ' text-sm">' + data.message + '</div>';
        setTimeout(function() { area.innerHTML = ''; }, 4000);
    } catch (e) {
        console.error(e);
    }
}
</script>
</body>
</html>
