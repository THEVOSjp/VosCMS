<?php
/**
 * RezlyX Admin - 페이지 환경 설정
 * 게시판 설정과 유사한 탭 구조 (기본 정보, 레이아웃, 스킨, SEO, 권한)
 */
if (!function_exists('__')) {
    require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';
}
require_once BASE_PATH . '/rzxlib/Core/Skin/SkinConfigRenderer.php';
use RzxLib\Core\Skin\SkinConfigRenderer;

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

    // AJAX 처리 - 스킨 설정 저장 (multipart/form-data, 파일 업로드 포함)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['action'] ?? '') === 'save_skin_config_multipart') {
        header('Content-Type: application/json; charset=utf-8');
        $slug = $_POST['slug'] ?? '';
        $skinName = $_POST['skin'] ?? 'default';

        // 기존 설정 로드
        $configKey = 'page_config_' . $slug;
        $cfgStmt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = ?");
        $cfgStmt->execute([$configKey]);
        $existing = json_decode($cfgStmt->fetchColumn() ?: '{}', true) ?: [];
        $skinConfig = [];

        // skin_config 값 수집
        foreach ($_POST as $k => $v) {
            if (preg_match('/^skin_config\[(.+)]$/', $k, $m)) {
                $skinConfig[$m[1]] = $v;
            }
        }
        // PHP는 skin_config[key] 형태를 배열로 파싱
        if (isset($_POST['skin_config']) && is_array($_POST['skin_config'])) {
            $skinConfig = array_merge($skinConfig, $_POST['skin_config']);
        }

        // 파일 업로드 처리 (skin_file_*)
        $uploadDir = BASE_PATH . '/storage/skins/page/' . $skinName . '/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
        $baseUrl = $config['app_url'] ?? '';

        foreach ($_FILES as $fileKey => $file) {
            if (strpos($fileKey, 'skin_file_') !== 0 || $file['error'] !== UPLOAD_ERR_OK) continue;
            $varName = str_replace('skin_file_', '', $fileKey);
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            $fileName = $varName . '_' . time() . '.' . $ext;
            if (move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
                // 기존 파일 삭제
                $oldVal = $existing['skin_config'][$varName] ?? '';
                if ($oldVal && file_exists(BASE_PATH . $oldVal)) @unlink(BASE_PATH . $oldVal);
                $skinConfig[$varName] = '/storage/skins/page/' . $skinName . '/' . $fileName;
            }
        }

        // 삭제 처리 (skin_delete[key] = "1")
        $deletes = $_POST['skin_delete'] ?? [];
        if (is_array($deletes)) {
            foreach ($deletes as $dk => $dv) {
                if ($dv === '1' && !empty($existing['skin_config'][$dk])) {
                    $oldPath = BASE_PATH . $existing['skin_config'][$dk];
                    if (file_exists($oldPath)) @unlink($oldPath);
                    $skinConfig[$dk] = '';
                }
            }
        }

        $existing['skin_config'] = $skinConfig;
        $configJson = json_encode($existing, JSON_UNESCAPED_UNICODE);
        $stmt = $pdo->prepare("INSERT INTO {$prefix}settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
        $stmt->execute([$configKey, $configJson]);
        echo json_encode(['success' => true, 'message' => __('common.msg.saved') ?? 'Saved.']);
        exit;
    }

    // AJAX 처리 - OG 이미지 업로드 (multipart/form-data)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_FILES['og_image_file'])) {
        header('Content-Type: application/json; charset=utf-8');
        $slug = $_POST['slug'] ?? '';
        $file = $_FILES['og_image_file'];
        if ($file['error'] === UPLOAD_ERR_OK) {
            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp', 'image/gif'];
            if (in_array($file['type'], $allowedTypes)) {
                $uploadDir = BASE_PATH . '/storage/pages/';
                if (!is_dir($uploadDir)) mkdir($uploadDir, 0755, true);
                $ext = pathinfo($file['name'], PATHINFO_EXTENSION);
                $fileName = 'og_' . $slug . '_' . time() . '.' . $ext;
                if (move_uploaded_file($file['tmp_name'], $uploadDir . $fileName)) {
                    // 기존 이미지 삭제
                    $cfgKey = 'page_config_' . $slug;
                    $stmtOld = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = ?");
                    $stmtOld->execute([$cfgKey]);
                    $oldCfg = json_decode($stmtOld->fetchColumn() ?: '{}', true) ?: [];
                    $oldImg = $oldCfg['og_image'] ?? '';
                    if ($oldImg && strpos($oldImg, '/storage/pages/') !== false && file_exists(BASE_PATH . $oldImg)) {
                        @unlink(BASE_PATH . $oldImg);
                    }
                    $imgUrl = '/storage/pages/' . $fileName;
                    echo json_encode(['success' => true, 'url' => $imgUrl]);
                } else {
                    echo json_encode(['success' => false, 'message' => 'Failed to move uploaded file']);
                }
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid file type. Allowed: JPG, PNG, WebP, GIF']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Upload error: ' . $file['error']]);
        }
        exit;
    }

    // AJAX 처리 - JSON POST (한 번만 읽기)
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && (strpos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false || !empty($_SERVER['HTTP_X_REQUESTED_WITH']))) {
        header('Content-Type: application/json; charset=utf-8');
        $input = json_decode(file_get_contents('php://input'), true);
        $action = $input['action'] ?? '';

        if ($action === 'delete_og_image') {
            $slug = $input['slug'] ?? '';
            $imgPath = $input['image_path'] ?? '';
            if ($imgPath && strpos($imgPath, '/storage/pages/') !== false && file_exists(BASE_PATH . $imgPath)) {
                @unlink(BASE_PATH . $imgPath);
            }
            echo json_encode(['success' => true]);
            exit;
        }

        if ($action === 'save_settings') {
            $slug = $input['slug'] ?? '';
            $settings = $input['settings'] ?? [];

            $configKey = 'page_config_' . $slug;
            $configJson = json_encode($settings, JSON_UNESCAPED_UNICODE);
            $stmt = $pdo->prepare("INSERT INTO {$prefix}settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
            $stmt->execute([$configKey, $configJson]);
            echo json_encode(['success' => true, 'message' => __('common.msg.saved') ?? 'Settings saved.']);
            exit;
        }

        if ($action === 'save_skin_config') {
            $slug = $input['slug'] ?? '';
            $skinConfig = $input['skin_config'] ?? [];

            $configKey = 'page_config_' . $slug;
            $cfgStmt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = ?");
            $cfgStmt->execute([$configKey]);
            $existing = json_decode($cfgStmt->fetchColumn() ?: '{}', true) ?: [];
            $existing['skin_config'] = $skinConfig;

            $configJson = json_encode($existing, JSON_UNESCAPED_UNICODE);
            $stmt = $pdo->prepare("INSERT INTO {$prefix}settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
            $stmt->execute([$configKey, $configJson]);
            echo json_encode(['success' => true, 'message' => __('common.msg.saved') ?? 'Settings saved.']);
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

    // 레이아웃 목록 (skins/layouts/*/layout.json)
    $layouts = [];
    $layoutsDir = BASE_PATH . '/skins/layouts/';
    if (is_dir($layoutsDir)) {
        foreach (scandir($layoutsDir) as $ld) {
            if ($ld === '.' || $ld === '..' || !is_dir($layoutsDir . $ld)) continue;
            $ljPath = $layoutsDir . $ld . '/layout.json';
            if (file_exists($ljPath)) {
                $lj = json_decode(file_get_contents($ljPath), true);
                $layouts[$ld] = [
                    'title' => $lj['title'][$defaultLocale] ?? $lj['title']['en'] ?? $ld,
                    'version' => $lj['version'] ?? '',
                    'thumbnail' => !empty($lj['thumbnail']) ? $baseUrl . '/skins/layouts/' . $ld . '/' . $lj['thumbnail'] : '',
                ];
            }
        }
    }
    if (empty($layouts)) $layouts['default'] = ['title' => __('site.pages.cfg.layout_default') ?? '기본 레이아웃', 'version' => '', 'thumbnail' => ''];

    // 페이지 스킨 목록 (skins/page/*/skin.json)
    $skins = [];
    $pageSkinDir = BASE_PATH . '/skins/page/';
    if (is_dir($pageSkinDir)) {
        foreach (scandir($pageSkinDir) as $sd) {
            if ($sd === '.' || $sd === '..' || !is_dir($pageSkinDir . $sd)) continue;
            $sjPath = $pageSkinDir . $sd . '/skin.json';
            if (file_exists($sjPath)) {
                $sj = json_decode(file_get_contents($sjPath), true);
                $skins[$sd] = [
                    'title' => $sj['title'][$defaultLocale] ?? $sj['title']['en'] ?? $sd,
                    'version' => $sj['version'] ?? '',
                    'thumbnail' => !empty($sj['thumbnail']) ? $baseUrl . '/skins/page/' . $sd . '/' . $sj['thumbnail'] : '',
                ];
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
<?php $embedMode = !empty($_GET['embed']); ?>
</head>
<?php if (!$embedMode): ?>
<body class="bg-zinc-100 dark:bg-zinc-900 min-h-screen transition-colors">
    <div class="flex">
        <?php include BASE_PATH . '/resources/views/admin/partials/admin-sidebar.php'; ?>
        <main class="flex-1 ml-64">
            <?php include BASE_PATH . '/resources/views/admin/partials/admin-topbar.php'; ?>
            <div class="p-6">
<?php else: ?>
<div class="p-2">
<?php endif; ?>
                <!-- 헤더 -->
                <div class="mb-6">
                    <div class="flex items-center gap-3 mb-2">
                        <a href="<?= $embedMode ? $baseUrl . '/' . htmlspecialchars($pageSlug) : $adminUrl . '/site/pages' ?>" class="text-zinc-400 hover:text-blue-600">
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
                                'addition' => ['label' => __('site.pages.tab_addition') ?? '추가 설정', 'icon' => 'M12 6V4m0 2a2 2 0 100 4m0-4a2 2 0 110 4m-6 8a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4m6 6v10m6-2a2 2 0 100-4m0 4a2 2 0 110-4m0 4v2m0-6V4'],
                                'permissions' => ['label' => __('site.pages.tab_permissions') ?? '권한 관리', 'icon' => 'M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z'],
                                'skin' => ['label' => __('site.pages.tab_skin') ?? '스킨', 'icon' => 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01'],
                            ];
                            foreach ($tabs as $key => $tab):
                                $isActive = $currentTab === $key;
                                $url = $embedMode
                                    ? $baseUrl . '/' . urlencode($pageSlug) . '/settings?tab=' . $key
                                    : $adminUrl . '/site/pages/settings?slug=' . urlencode($pageSlug) . '&tab=' . $key;
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
                <?php
                    $_typeLabels = [
                        'document' => __('site.pages.type_document') ?? '문서 페이지',
                        'widget' => __('site.pages.type_widget') ?? '위젯 페이지',
                        'external' => __('site.pages.type_external') ?? '외부 페이지',
                    ];
                    $_inp = 'w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500';
                ?>
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700 overflow-hidden">
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        <!-- 페이지 타입 -->
                        <div class="flex items-start px-6 py-4">
                            <label class="w-40 shrink-0 text-sm font-medium text-zinc-700 dark:text-zinc-300 pt-2"><?= __('site.pages.cfg.page_type') ?? '페이지 타입' ?></label>
                            <div class="flex-1">
                                <div class="text-sm text-zinc-800 dark:text-zinc-200 font-medium"><?= $_typeLabels[$pageData['page_type'] ?? 'document'] ?? ucfirst($pageData['page_type'] ?? 'document') ?></div>
                            </div>
                        </div>
                        <!-- URL -->
                        <div class="flex items-start px-6 py-4">
                            <label class="w-40 shrink-0 text-sm font-medium text-zinc-700 dark:text-zinc-300 pt-2">URL</label>
                            <div class="flex-1">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm text-zinc-400"><?= $baseUrl ?>/</span>
                                    <input type="text" id="cfgSlug" value="<?= htmlspecialchars($pageSlug) ?>" class="<?= $_inp ?> max-w-xs" <?= !empty($pageData['is_system']) ? 'disabled' : '' ?>>
                                </div>
                                <p class="text-xs text-zinc-400 mt-1"><?= __('site.pages.cfg.url_desc') ?? 'URL상의 모듈 이름은 영문, 숫자, _ 만으로 이루어져야 하며, 첫 글자는 반드시 영문 알파벳이어야 합니다.' ?></p>
                            </div>
                        </div>
                        <!-- 브라우저 제목 -->
                        <div class="flex items-start px-6 py-4">
                            <label class="w-40 shrink-0 text-sm font-medium text-zinc-700 dark:text-zinc-300 pt-2"><?= __('site.pages.cfg.browser_title') ?? '브라우저 제목' ?></label>
                            <div class="flex-1">
                                <?php rzx_multilang_input('cfgBrowserTitle', $pageConfig['browser_title'] ?? $pageData['title'] ?? '', 'page.' . $pageSlug . '.browser_title', [
                                    'placeholder' => $pageData['title'] ?? '',
                                ]); ?>
                            </div>
                        </div>
                        <!-- 검색엔진 색인 -->
                        <div class="flex items-start px-6 py-4">
                            <label class="w-40 shrink-0 text-sm font-medium text-zinc-700 dark:text-zinc-300 pt-2"><?= __('site.pages.cfg.search_index') ?? '검색엔진 색인' ?></label>
                            <div class="flex-1">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" id="cfgSearchIndex" class="sr-only peer" <?= ($pageConfig['search_index'] ?? 'yes') === 'yes' ? 'checked' : '' ?>>
                                    <div class="w-11 h-6 bg-zinc-200 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                                <p class="text-xs text-zinc-400 mt-1"><?= __('site.pages.cfg.search_index_desc') ?? '검색엔진이 이 페이지를 색인하는 것을 허용합니다.' ?></p>
                            </div>
                        </div>
                        <!-- SEO 키워드 -->
                        <div class="flex items-start px-6 py-4">
                            <label class="w-40 shrink-0 text-sm font-medium text-zinc-700 dark:text-zinc-300 pt-2"><?= __('site.pages.cfg.seo_keywords') ?? 'SEO 키워드' ?></label>
                            <div class="flex-1">
                                <?php rzx_multilang_input('cfgMetaKeywords', $pageConfig['meta_keywords'] ?? '', 'page.' . $pageSlug . '.meta_keywords', [
                                    'placeholder' => __('site.pages.cfg.seo_keywords_placeholder') ?? '키워드1, 키워드2',
                                ]); ?>
                            </div>
                        </div>
                        <!-- SEO 설명 -->
                        <div class="flex items-start px-6 py-4">
                            <label class="w-40 shrink-0 text-sm font-medium text-zinc-700 dark:text-zinc-300 pt-2"><?= __('site.pages.cfg.seo_description') ?? 'SEO 설명' ?></label>
                            <div class="flex-1">
                                <?php rzx_multilang_input('cfgMetaDesc', $pageConfig['meta_description'] ?? '', 'page.' . $pageSlug . '.meta_description', [
                                    'type' => 'textarea',
                                    'rows' => 2,
                                ]); ?>
                            </div>
                        </div>
                        <!-- Meta Title -->
                        <div class="flex items-start px-6 py-4">
                            <label class="w-40 shrink-0 text-sm font-medium text-zinc-700 dark:text-zinc-300 pt-2">Meta Title</label>
                            <div class="flex-1">
                                <input type="text" id="cfgMetaTitle" value="<?= htmlspecialchars($pageConfig['meta_title'] ?? '') ?>" class="<?= $_inp ?>" placeholder="<?= __('site.pages.cfg.meta_title_placeholder') ?? '페이지 제목과 다른 SEO 제목' ?>">
                            </div>
                        </div>
                        <!-- OG Image -->
                        <div class="flex items-start px-6 py-4">
                            <label class="w-40 shrink-0 text-sm font-medium text-zinc-700 dark:text-zinc-300 pt-2">OG Image</label>
                            <div class="flex-1">
                                <?php $ogImgVal = $pageConfig['og_image'] ?? ''; ?>
                                <!-- 미리보기 -->
                                <div id="ogPreviewArea" class="<?= $ogImgVal ? '' : 'hidden' ?> mb-3">
                                    <div class="relative inline-block group">
                                        <img id="ogPreviewImg" src="<?= $ogImgVal ? $baseUrl . htmlspecialchars($ogImgVal) : '' ?>" alt="OG Image" class="max-h-32 rounded-lg border dark:border-zinc-600 object-contain bg-zinc-50 dark:bg-zinc-900">
                                        <button type="button" onclick="deleteOgImage()" class="absolute -top-2 -right-2 w-6 h-6 bg-red-500 text-white rounded-full text-xs flex items-center justify-center opacity-0 group-hover:opacity-100 transition-opacity hover:bg-red-600" title="<?= __('common.buttons.delete') ?? '삭제' ?>">✕</button>
                                    </div>
                                </div>
                                <!-- 이미지 업로드 -->
                                <div class="flex items-center gap-3 mb-2">
                                    <label class="relative cursor-pointer">
                                        <input type="file" id="ogImageFile" accept="image/jpeg,image/png,image/webp,image/gif" class="hidden" onchange="uploadOgImage(this)">
                                        <span class="inline-flex items-center gap-1.5 px-4 py-2 text-sm font-medium text-blue-700 bg-blue-50 dark:text-blue-400 dark:bg-blue-900/30 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/50 transition">
                                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                            <?= __('site.pages.cfg.og_image_upload') ?? '이미지 업로드' ?>
                                        </span>
                                    </label>
                                    <span id="ogUploadStatus" class="text-xs text-zinc-400"></span>
                                </div>
                                <!-- URL 직접 입력 -->
                                <input type="text" id="cfgOgImage" value="<?= htmlspecialchars($ogImgVal) ?>" class="<?= $_inp ?>" placeholder="https://..." onchange="updateOgPreview()">
                                <p class="text-xs text-zinc-400 mt-1"><?= __('site.pages.cfg.og_image_desc') ?? 'SNS 공유 시 표시될 대표 이미지 URL' ?></p>
                            </div>
                        </div>
                        <!-- Robots -->
                        <div class="flex items-start px-6 py-4">
                            <label class="w-40 shrink-0 text-sm font-medium text-zinc-700 dark:text-zinc-300 pt-2">Robots</label>
                            <div class="flex-1">
                                <select id="cfgRobots" class="<?= $_inp ?> max-w-xs">
                                    <option value="index,follow" <?= ($pageConfig['robots'] ?? 'index,follow') === 'index,follow' ? 'selected' : '' ?>>index, follow (<?= __('common.default') ?? '기본' ?>)</option>
                                    <option value="noindex,follow" <?= ($pageConfig['robots'] ?? '') === 'noindex,follow' ? 'selected' : '' ?>>noindex, follow</option>
                                    <option value="index,nofollow" <?= ($pageConfig['robots'] ?? '') === 'index,nofollow' ? 'selected' : '' ?>>index, nofollow</option>
                                    <option value="noindex,nofollow" <?= ($pageConfig['robots'] ?? '') === 'noindex,nofollow' ? 'selected' : '' ?>>noindex, nofollow</option>
                                </select>
                            </div>
                        </div>
                        </div>
                    </div>

                    <!-- 레이아웃 선택 (카드형) -->
                    <input type="hidden" id="cfgLayout" value="<?= htmlspecialchars($pageConfig['layout'] ?? 'default') ?>">
                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-1"><?= __('site.pages.cfg.layout_select') ?? '레이아웃 선택' ?></h3>
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 mt-4">
                            <?php foreach ($layouts as $lk => $lInfo):
                                $isSelected = ($pageConfig['layout'] ?? 'default') === $lk;
                            ?>
                            <div onclick="selectLayout('<?= $lk ?>')" id="layout-card-<?= $lk ?>"
                                 class="cursor-pointer rounded-xl border-2 p-1 transition-all <?= $isSelected ? 'border-blue-500 ring-2 ring-blue-200 dark:ring-blue-800' : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-400' ?>">
                                <div class="h-24 bg-zinc-100 dark:bg-zinc-700 rounded-lg flex items-center justify-center">
                                    <?php if ($lInfo['thumbnail']): ?>
                                    <img src="<?= htmlspecialchars($lInfo['thumbnail']) ?>" alt="" class="max-h-full max-w-full object-contain rounded-lg" onerror="this.parentElement.innerHTML='<svg class=\'w-8 h-8 text-zinc-400\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z\'/></svg>'">
                                    <?php else: ?>
                                    <svg class="w-8 h-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/></svg>
                                    <?php endif; ?>
                                </div>
                                <div class="px-2 py-2">
                                    <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($lInfo['title']) ?></p>
                                    <?php if ($lInfo['version']): ?>
                                    <p class="text-xs text-zinc-400">v<?= htmlspecialchars($lInfo['version']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-xs text-zinc-400 mt-3"><?= __('site.pages.cfg.layout_desc') ?? '게시판에 적용할 레이아웃을 선택합니다. 레이아웃을 변경하면 페이지가 새로고침됩니다.' ?></p>
                    </div>

                    <!-- 스킨 선택 (카드형) -->
                    <input type="hidden" id="cfgSkin" value="<?= htmlspecialchars($pageConfig['skin'] ?? '') ?>">
                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-1"><?= __('site.pages.cfg.skin_select') ?? '스킨 선택' ?></h3>
                        <div class="grid grid-cols-2 sm:grid-cols-3 lg:grid-cols-4 gap-4 mt-4">
                            <?php foreach ($skins as $sk => $sInfo):
                                $isSelected = ($pageConfig['skin'] ?? '') === $sk || (empty($pageConfig['skin']) && $sk === 'default');
                            ?>
                            <div onclick="selectSkin('<?= $sk ?>')" id="skin-card-<?= $sk ?>"
                                 class="cursor-pointer rounded-xl border-2 p-1 transition-all <?= $isSelected ? 'border-blue-500 ring-2 ring-blue-200 dark:ring-blue-800' : 'border-zinc-200 dark:border-zinc-700 hover:border-zinc-400' ?>">
                                <div class="h-24 bg-zinc-100 dark:bg-zinc-700 rounded-lg flex items-center justify-center">
                                    <?php if ($sInfo['thumbnail']): ?>
                                    <img src="<?= htmlspecialchars($sInfo['thumbnail']) ?>" alt="" class="max-h-full max-w-full object-contain rounded-lg" onerror="this.parentElement.innerHTML='<svg class=\'w-8 h-8 text-zinc-400\' fill=\'none\' stroke=\'currentColor\' viewBox=\'0 0 24 24\'><path stroke-linecap=\'round\' stroke-linejoin=\'round\' stroke-width=\'2\' d=\'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01\'/></svg>'">
                                    <?php else: ?>
                                    <svg class="w-8 h-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/></svg>
                                    <?php endif; ?>
                                </div>
                                <div class="px-2 py-2">
                                    <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($sInfo['title']) ?></p>
                                    <?php if ($sInfo['version']): ?>
                                    <p class="text-xs text-zinc-400">v<?= htmlspecialchars($sInfo['version']) ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-xs text-zinc-400 mt-3"><?= __('site.pages.cfg.skin_card_desc') ?? '스킨을 변경하면 페이지가 새로고침됩니다. 스킨별 세부 설정은 스킨 탭에서 할 수 있습니다.' ?></p>
                    </div>

                    <!-- 추가 설정 카드 -->
                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700">
                        <div class="divide-y divide-zinc-100 dark:divide-zinc-700">
                        <!-- 전체 너비 -->
                        <div class="flex items-start px-6 py-4">
                            <label class="w-40 shrink-0 text-sm font-medium text-zinc-700 dark:text-zinc-300 pt-2"><?= __('site.pages.cfg.full_width') ?? '전체 너비' ?></label>
                            <div class="flex-1">
                                <label class="relative inline-flex items-center cursor-pointer">
                                    <input type="checkbox" id="cfgFullWidth" class="sr-only peer" <?= ($pageConfig['full_width'] ?? false) ? 'checked' : '' ?>>
                                    <div class="w-11 h-6 bg-zinc-200 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                                </label>
                                <p class="text-xs text-zinc-400 mt-1"><?= __('site.pages.cfg.full_width_desc') ?? '콘텐츠를 전체 너비로 표시합니다. (기본: max-w-5xl)' ?></p>
                            </div>
                        </div>
                        </div>
                    </div>

                    <!-- 저장 -->
                    <div class="flex justify-end mt-4">
                        <button onclick="saveSettings()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium"><?= __('common.buttons.save') ?? '저장' ?></button>
                    </div>

                <?php elseif ($currentTab === 'skin'): ?>
                <?php
                    $currentPageSkin = !empty($pageConfig['skin']) ? $pageConfig['skin'] : 'default';
                    $savedSkinConfig = !empty($pageConfig['skin_config']) ? (is_array($pageConfig['skin_config']) ? $pageConfig['skin_config'] : json_decode($pageConfig['skin_config'], true)) : [];
                    $pageSkinJsonPath = BASE_PATH . '/skins/page/' . $currentPageSkin . '/skin.json';
                    $skinRenderer = new SkinConfigRenderer($pageSkinJsonPath, $savedSkinConfig ?: [], $defaultLocale, $baseUrl);
                    $skinMeta = $skinRenderer->getMeta();
                    $skinThumbnail = $skinMeta['thumbnail'] ?? '';
                    $skinThumbnailUrl = $skinThumbnail ? $baseUrl . '/skins/page/' . $currentPageSkin . '/' . $skinThumbnail : '';
                ?>
                <form id="pageSkinForm" class="space-y-6">
                    <!-- 스킨 기본정보 -->
                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                        <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
                            <h3 class="text-base font-semibold text-zinc-800 dark:text-zinc-200"><?= __('site.pages.skin_info') ?? '스킨 기본정보' ?></h3>
                        </div>
                        <div class="flex">
                            <div class="flex-1 divide-y divide-zinc-100 dark:divide-zinc-700">
                                <!-- 스킨명 -->
                                <div class="flex px-6 py-3">
                                    <span class="w-32 text-sm text-zinc-500 dark:text-zinc-400 shrink-0"><?= __('site.boards.skin_name') ?? '스킨' ?></span>
                                    <span class="text-sm text-zinc-800 dark:text-zinc-200 font-medium">
                                        <?= htmlspecialchars($skinMeta['title'] ?: $currentPageSkin) ?>
                                        <span class="ml-2 px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 text-xs rounded-full"><?= htmlspecialchars($currentPageSkin) ?></span>
                                    </span>
                                </div>
                                <!-- 제작자 -->
                                <?php if (!empty($skinMeta['author']['name'])): ?>
                                <div class="flex px-6 py-3">
                                    <span class="w-32 text-sm text-zinc-500 dark:text-zinc-400 shrink-0"><?= __('site.boards.skin_author') ?? '스킨 제작자' ?></span>
                                    <span class="text-sm text-zinc-800 dark:text-zinc-200">
                                        <?php if (!empty($skinMeta['author']['url'])): ?>
                                        <a href="<?= htmlspecialchars($skinMeta['author']['url']) ?>" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline"><?= htmlspecialchars($skinMeta['author']['name']) ?></a>
                                        <svg class="w-3 h-3 inline ml-0.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                        <span class="text-xs text-zinc-400 ml-2"><?= htmlspecialchars($skinMeta['author']['url']) ?></span>
                                        <?php else: ?>
                                        <?= htmlspecialchars($skinMeta['author']['name']) ?>
                                        <?php endif; ?>
                                        <?php if (!empty($skinMeta['author']['email'])): ?>
                                        <span class="text-xs text-zinc-400 ml-2">, <?= htmlspecialchars($skinMeta['author']['email']) ?></span>
                                        <?php endif; ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                                <!-- 날짜 -->
                                <?php if (!empty($skinMeta['date'])): ?>
                                <div class="flex px-6 py-3">
                                    <span class="w-32 text-sm text-zinc-500 dark:text-zinc-400 shrink-0"><?= __('site.boards.skin_date') ?? '날짜' ?></span>
                                    <span class="text-sm text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($skinMeta['date']) ?></span>
                                </div>
                                <?php endif; ?>
                                <!-- 버전 -->
                                <?php if (!empty($skinMeta['version'])): ?>
                                <div class="flex px-6 py-3">
                                    <span class="w-32 text-sm text-zinc-500 dark:text-zinc-400 shrink-0"><?= __('site.boards.skin_version') ?? '버전' ?></span>
                                    <span class="text-sm text-zinc-800 dark:text-zinc-200">v<?= htmlspecialchars($skinMeta['version']) ?></span>
                                </div>
                                <?php endif; ?>
                                <!-- 설명 -->
                                <?php if (!empty($skinMeta['description'])): ?>
                                <div class="flex px-6 py-3">
                                    <span class="w-32 text-sm text-zinc-500 dark:text-zinc-400 shrink-0"><?= __('site.boards.skin_desc') ?? '설명' ?></span>
                                    <span class="text-sm text-zinc-500 dark:text-zinc-400"><?= htmlspecialchars($skinMeta['description']) ?></span>
                                </div>
                                <?php endif; ?>
                            </div>
                            <!-- 썸네일 -->
                            <?php if ($skinThumbnailUrl): ?>
                            <div class="w-52 shrink-0 border-l border-zinc-100 dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-700/30 flex items-center justify-center p-4">
                                <img src="<?= htmlspecialchars($skinThumbnailUrl) ?>" alt="<?= htmlspecialchars($skinMeta['title'] ?? '') ?>" class="max-w-full max-h-48 rounded-lg shadow-sm object-contain" onerror="this.parentElement.innerHTML='<span class=\'text-zinc-400 text-xs\'>No preview</span>'">
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- 확장 변수 (skin.json vars) -->
                    <?php if ($skinRenderer->hasVars()): ?>
                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-4"><?= __('site.boards.skin_settings') ?? '확장 변수' ?></h3>
                        <div id="skinConfigForm">
                            <?php $skinRenderer->renderForm(); ?>
                        </div>
                    </div>

                    <div class="flex items-center justify-end gap-3">
                        <span id="skinSaveStatus" class="text-sm text-green-600 dark:text-green-400 hidden"></span>
                        <button type="submit" class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition"><?= __('common.buttons.save') ?? '저장' ?></button>
                    </div>
                    <?php else: ?>
                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6 text-center">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('site.boards.skin_no_settings') ?? '이 스킨에는 설정 가능한 항목이 없습니다.' ?></p>
                    </div>
                    <?php endif; ?>
                </form>

                <script>
                console.log('[PageSkin] 스킨 탭 로드됨, skin:', '<?= $currentPageSkin ?>');
                document.getElementById('pageSkinForm')?.addEventListener('submit', async function(e) {
                    e.preventDefault();
                    const fd = new FormData(this);
                    // 체크되지 않은 checkbox → "0" 추가
                    this.querySelectorAll('.skin-checkbox').forEach(cb => {
                        if (!cb.checked) fd.set('skin_config[' + cb.dataset.name + ']', '0');
                    });
                    fd.append('action', 'save_skin_config_multipart');
                    fd.append('slug', '<?= $pageSlug ?>');
                    fd.append('skin', '<?= $currentPageSkin ?>');

                    try {
                        const apiUrl = '<?= $adminUrl ?>/site/pages/settings?slug=<?= urlencode($pageSlug) ?>&tab=skin';
                        const resp = await fetch(apiUrl, {
                            method: 'POST',
                            headers: { 'X-Requested-With': 'XMLHttpRequest' },
                            body: fd
                        });
                        const data = await resp.json();
                        showResultModal(data.success, data.success ? '' : data.message);
                        if (data.success) setTimeout(() => location.reload(), 1500);
                    } catch (err) {
                        console.error('[PageSkin] 에러:', err);
                        showResultModal(false, err.message);
                    }
                });
                </script>

                <?php elseif ($currentTab === 'addition'): ?>
                <?php
                    $editorConfig = $pageConfig;
                    include BASE_PATH . '/resources/views/admin/components/editor-permissions.php';
                ?>
                <div class="flex justify-end mt-4">
                    <button onclick="saveSettings()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium"><?= __('common.buttons.save') ?? '저장' ?></button>
                </div>

                <?php elseif ($currentTab === 'permissions'): ?>
                <?php
                    $permLevels = [
                        'all' => __('site.pages.perm.all') ?? '모든 방문자',
                        'member' => __('site.pages.perm.member') ?? '로그인 회원',
                        'admin' => __('site.pages.perm.admin') ?? '관리자만',
                    ];
                    // 회원 그룹 추가
                    $gradeStmt = $pdo->query("SELECT id, name, slug FROM {$prefix}member_grades ORDER BY sort_order");
                    $grades = $gradeStmt->fetchAll(PDO::FETCH_ASSOC);
                    foreach ($grades as $g) {
                        if ($g['slug'] !== 'staff') {
                            $permLevels['grade:' . $g['slug']] = $g['name'] . ' ' . (__('site.pages.perm.above') ?? '이상');
                        }
                    }
                    $_sel = 'w-full max-w-xs px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm';
                ?>
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border dark:border-zinc-700 overflow-hidden">
                    <!-- 모듈 관리자 -->
                    <div class="p-6 border-b border-zinc-100 dark:border-zinc-700">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('site.pages.perm.module_admin') ?? '모듈 관리자' ?></h3>
                        <div class="flex items-start gap-3 mb-3">
                            <input type="text" id="permAdminId" placeholder="<?= __('site.pages.perm.admin_id_placeholder') ?? '이메일 주소 입력' ?>" class="flex-1 max-w-sm px-3 py-2 border rounded-lg dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm">
                            <button onclick="addModuleAdmin()" class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700"><?= __('common.buttons.add') ?? '추가' ?></button>
                        </div>
                        <div id="moduleAdminList" class="space-y-2">
                            <?php
                            $_ma = $pageConfig['module_admins'] ?? [];
                            $admins = is_array($_ma) ? $_ma : (json_decode($_ma, true) ?: []);
                            foreach ($admins as $adm): ?>
                            <div class="flex items-center justify-between px-3 py-2 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg text-sm">
                                <span class="text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($adm) ?></span>
                                <button onclick="removeModuleAdmin(this)" class="text-red-500 hover:text-red-700 text-xs"><?= __('common.buttons.delete') ?? '삭제' ?></button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <p class="text-xs text-zinc-400 mt-2"><?= __('site.pages.perm.admin_desc') ?? '특정 회원에게 이 모듈의 관리 권한을 부여할 수 있습니다.' ?></p>
                        <div class="mt-3 flex items-center gap-4">
                            <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('site.pages.perm.admin_scope') ?? '관리자 권한 범위' ?></span>
                            <label class="flex items-center gap-1.5 text-sm text-zinc-600 dark:text-zinc-400">
                                <input type="checkbox" id="permDocManage" class="rounded" <?= ($pageConfig['perm_doc_manage'] ?? true) ? 'checked' : '' ?>>
                                <?= __('site.pages.perm.doc_manage') ?? '문서 관리' ?>
                            </label>
                            <label class="flex items-center gap-1.5 text-sm text-zinc-600 dark:text-zinc-400">
                                <input type="checkbox" id="permCommentManage" class="rounded" <?= ($pageConfig['perm_comment_manage'] ?? true) ? 'checked' : '' ?>>
                                <?= __('site.pages.perm.comment_manage') ?? '댓글 관리' ?>
                            </label>
                            <label class="flex items-center gap-1.5 text-sm text-zinc-600 dark:text-zinc-400">
                                <input type="checkbox" id="permSettingsManage" class="rounded" <?= ($pageConfig['perm_settings_manage'] ?? true) ? 'checked' : '' ?>>
                                <?= __('site.pages.perm.settings_manage') ?? '모듈 설정 변경' ?>
                            </label>
                        </div>
                    </div>

                    <!-- 권한 설정 -->
                    <div class="p-6">
                        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('site.pages.perm.access_settings') ?? '권한 설정' ?></h3>
                        <div class="divide-y divide-zinc-100 dark:divide-zinc-700">
                            <!-- 접근 권한 -->
                            <div class="flex items-center py-3">
                                <label class="w-40 shrink-0 text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('site.pages.perm.access') ?? '접근 권한' ?></label>
                                <select id="permAccess" class="<?= $_sel ?>">
                                    <?php foreach ($permLevels as $pk => $pv): ?>
                                    <option value="<?= $pk ?>" <?= ($pageConfig['perm_access'] ?? 'all') === $pk ? 'selected' : '' ?>><?= htmlspecialchars($pv) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- 페이지 수정 -->
                            <div class="flex items-center py-3">
                                <label class="w-40 shrink-0 text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('site.pages.perm.edit') ?? '페이지 수정' ?></label>
                                <select id="permEdit" class="<?= $_sel ?>">
                                    <?php foreach ($permLevels as $pk => $pv): ?>
                                    <option value="<?= $pk ?>" <?= ($pageConfig['perm_edit'] ?? 'admin') === $pk ? 'selected' : '' ?>><?= htmlspecialchars($pv) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- 관리 권한 -->
                            <div class="flex items-center py-3">
                                <label class="w-40 shrink-0 text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('site.pages.perm.manage') ?? '관리 권한' ?></label>
                                <select id="permManage" class="<?= $_sel ?>">
                                    <?php foreach ($permLevels as $pk => $pv): ?>
                                    <option value="<?= $pk ?>" <?= ($pageConfig['perm_manage'] ?? 'admin') === $pk ? 'selected' : '' ?>><?= htmlspecialchars($pv) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>

                    <div class="flex justify-end px-6 py-4 border-t border-zinc-100 dark:border-zinc-700">
                        <button onclick="saveSettings()" class="px-6 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium"><?= __('common.buttons.save') ?? '저장' ?></button>
                    </div>
                </div>

                <?php endif; ?>

                <!-- 하단 바로가기 -->
                <div class="mt-6 flex items-center gap-3">
                    <a href="<?= $embedMode ? $baseUrl . '/' . urlencode($pageSlug) . '/edit' : $adminUrl . '/site/pages/edit-content?slug=' . urlencode($pageSlug) ?>" class="px-4 py-2 text-sm text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg border border-blue-200 dark:border-blue-800">
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

function selectLayout(key) {
    console.log('[PageSettings] selectLayout:', key);
    document.getElementById('cfgLayout').value = key;
    document.querySelectorAll('[id^="layout-card-"]').forEach(function(el) {
        el.className = el.className.replace(/border-blue-500 ring-2 ring-blue-200 dark:ring-blue-800/g, '').replace(/border-zinc-200 dark:border-zinc-700 hover:border-zinc-400/g, '') + ' border-zinc-200 dark:border-zinc-700 hover:border-zinc-400';
    });
    var card = document.getElementById('layout-card-' + key);
    if (card) card.className = card.className.replace(/border-zinc-200 dark:border-zinc-700 hover:border-zinc-400/g, '') + ' border-blue-500 ring-2 ring-blue-200 dark:ring-blue-800';
}

function selectSkin(key) {
    console.log('[PageSettings] selectSkin:', key);
    document.getElementById('cfgSkin').value = key;
    document.querySelectorAll('[id^="skin-card-"]').forEach(function(el) {
        el.className = el.className.replace(/border-blue-500 ring-2 ring-blue-200 dark:ring-blue-800/g, '').replace(/border-zinc-200 dark:border-zinc-700 hover:border-zinc-400/g, '') + ' border-zinc-200 dark:border-zinc-700 hover:border-zinc-400';
    });
    var card = document.getElementById('skin-card-' + key);
    if (card) card.className = card.className.replace(/border-zinc-200 dark:border-zinc-700 hover:border-zinc-400/g, '') + ' border-blue-500 ring-2 ring-blue-200 dark:ring-blue-800';
}

function getModuleAdmins() {
    var list = [];
    document.querySelectorAll('#moduleAdminList > div').forEach(function(el) {
        var email = el.querySelector('span')?.textContent?.trim();
        if (email) list.push(email);
    });
    return list;
}

function addModuleAdmin() {
    var input = document.getElementById('permAdminId');
    var email = input.value.trim();
    if (!email) return;
    var list = document.getElementById('moduleAdminList');
    var div = document.createElement('div');
    div.className = 'flex items-center justify-between px-3 py-2 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg text-sm';
    div.innerHTML = '<span class="text-zinc-800 dark:text-zinc-200">' + email + '</span><button onclick="removeModuleAdmin(this)" class="text-red-500 hover:text-red-700 text-xs"><?= __("common.buttons.delete") ?? "삭제" ?></button>';
    list.appendChild(div);
    input.value = '';
}

function removeModuleAdmin(btn) {
    btn.closest('div').remove();
}

function uploadOgImage(input) {
    if (!input.files || !input.files[0]) return;
    var file = input.files[0];
    if (file.size > 5 * 1024 * 1024) {
        document.getElementById('ogUploadStatus').textContent = 'Max 5MB';
        return;
    }
    var status = document.getElementById('ogUploadStatus');
    status.textContent = '<?= __("common.msg.loading") ?? "업로드 중..." ?>';

    var fd = new FormData();
    fd.append('og_image_file', file);
    fd.append('slug', SLUG);

    fetch(PAGE_URL + '?slug=' + SLUG, { method: 'POST', body: fd })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        if (data.success) {
            document.getElementById('cfgOgImage').value = data.url;
            document.getElementById('ogPreviewImg').src = '<?= $baseUrl ?>' + data.url;
            document.getElementById('ogPreviewArea').classList.remove('hidden');
            status.textContent = '';
            console.log('[OG Image] uploaded:', data.url);
        } else {
            status.textContent = data.message || 'Error';
        }
    })
    .catch(function(e) { status.textContent = 'Error'; console.error('[OG Image]', e); });
    input.value = '';
}

function deleteOgImage() {
    var imgUrl = document.getElementById('cfgOgImage').value;
    if (imgUrl && imgUrl.indexOf('/storage/pages/') !== -1) {
        fetch(PAGE_URL + '?slug=' + SLUG, {
            method: 'POST',
            headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ action: 'delete_og_image', slug: SLUG, image_path: imgUrl })
        }).catch(function(e) { console.error(e); });
    }
    document.getElementById('cfgOgImage').value = '';
    document.getElementById('ogPreviewArea').classList.add('hidden');
    document.getElementById('ogPreviewImg').src = '';
    console.log('[OG Image] deleted');
}

function updateOgPreview() {
    var url = document.getElementById('cfgOgImage').value.trim();
    var area = document.getElementById('ogPreviewArea');
    var img = document.getElementById('ogPreviewImg');
    if (url) {
        img.src = url.startsWith('http') ? url : '<?= $baseUrl ?>' + url;
        area.classList.remove('hidden');
    } else {
        area.classList.add('hidden');
        img.src = '';
    }
}

async function saveSettings() {
    var settings = {
        slug: document.getElementById('cfgSlug')?.value || SLUG,
        browser_title: document.getElementById('cfgBrowserTitle')?.value || '',
        full_width: document.getElementById('cfgFullWidth')?.checked ? true : false,
        search_index: document.getElementById('cfgSearchIndex')?.checked ? 'yes' : 'no',
        layout: document.getElementById('cfgLayout')?.value || 'default',
        skin: document.getElementById('cfgSkin')?.value || '',
        meta_title: document.getElementById('cfgMetaTitle')?.value || '',
        meta_description: document.getElementById('cfgMetaDesc')?.value || '',
        meta_keywords: document.getElementById('cfgMetaKeywords')?.value || '',
        robots: document.getElementById('cfgRobots')?.value || '',
        og_image: document.getElementById('cfgOgImage')?.value || '',
        // 에디터 권한 (추가 설정 탭)
        ...(typeof getEditorConfig === 'function' ? getEditorConfig() : {}),
        // 권한
        perm_access: document.getElementById('permAccess')?.value || 'all',
        perm_edit: document.getElementById('permEdit')?.value || 'admin',
        perm_manage: document.getElementById('permManage')?.value || 'admin',
        perm_doc_manage: document.getElementById('permDocManage')?.checked ? true : false,
        perm_comment_manage: document.getElementById('permCommentManage')?.checked ? true : false,
        perm_settings_manage: document.getElementById('permSettingsManage')?.checked ? true : false,
        module_admins: getModuleAdmins(),
    };

    try {
        var res = await fetch(PAGE_URL + '?slug=' + SLUG, {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
            body: JSON.stringify({ action: 'save_settings', slug: SLUG, settings: settings })
        });
        var data = await res.json();
        showResultModal(data.success, data.success ? '' : data.message);
        console.log('[saveSettings]', data);
    } catch (e) {
        showResultModal(false, '<?= __("common.msg.error") ?? "오류가 발생했습니다." ?>');
        console.error('[saveSettings]', e);
    }
}
</script>
<?php include BASE_PATH . '/resources/views/admin/partials/result-modal.php'; ?>
<?php if (!$embedMode): ?>
</body>
</html>
<?php else: ?>
</div>
<?php endif; ?>
