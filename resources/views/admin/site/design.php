<?php
/**
 * RezlyX Admin - 레이아웃 관리 페이지
 * 좌측: 사이트 미리보기 + 메뉴 목록
 * 우측: 메뉴 클릭 시 스킨/레이아웃 목록 표시
 */
if (!function_exists('__')) {
    require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';
}
require_once BASE_PATH . '/rzxlib/Core/Skin/SkinConfigRenderer.php';
use RzxLib\Core\Skin\SkinConfigRenderer;

$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
$defaultLocale = $config['locale'] ?? 'ko';

try {
    $pdo = new PDO(
        'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx') . ';charset=utf8mb4',
        $_ENV['DB_USERNAME'] ?? 'root', $_ENV['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

    // AJAX 저장
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
        header('Content-Type: application/json; charset=utf-8');
        $input = json_decode(file_get_contents('php://input'), true);
        if (($input['action'] ?? '') === 'save_layout') {
            $fields = ['site_layout', 'site_page_skin', 'site_board_skin', 'site_member_skin'];
            $stmt = $pdo->prepare("INSERT INTO {$prefix}settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
            foreach ($fields as $f) {
                if (isset($input[$f])) $stmt->execute([$f, $input[$f]]);
            }
            echo json_encode(['success' => true]);
            exit;
        }

        // 레이아웃 복사
        // 스킨 복사 (레이아웃/페이지/게시판/회원 공통)
        if (($input['action'] ?? '') === 'copy_skin') {
            $group = $input['group'] ?? 'layout';
            $src = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['slug'] ?? '');
            $dirMap = ['layout' => 'layouts', 'page' => 'page', 'board' => 'board', 'member' => 'member'];
            $srcDir = BASE_PATH . '/skins/' . ($dirMap[$group] ?? $group) . '/' . $src;
            if (!$src || !is_dir($srcDir)) { echo json_encode(['success' => false, 'error' => 'Skin not found']); exit; }
            $newSlug = $src . '-copy-' . date('ymd');
            $i = 1;
            $baseDir = dirname($srcDir);
            while (is_dir($baseDir . '/' . $newSlug)) { $newSlug = $src . '-copy-' . date('ymd') . '-' . $i++; }
            $newDir = $baseDir . '/' . $newSlug;
            mkdir($newDir, 0775, true);
            foreach (scandir($srcDir) as $f) {
                if ($f === '.' || $f === '..') continue;
                copy($srcDir . '/' . $f, $newDir . '/' . $f);
            }
            // JSON 제목 수정
            $jsonFile = $group === 'layout' ? 'layout.json' : 'skin.json';
            $jPath = $newDir . '/' . $jsonFile;
            if (file_exists($jPath)) {
                $j = json_decode(file_get_contents($jPath), true);
                if (isset($j['title']) && is_array($j['title'])) {
                    foreach ($j['title'] as $lang => &$t) { $t .= ' (Copy)'; }
                    unset($t);
                }
                file_put_contents($jPath, json_encode($j, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            }
            echo json_encode(['success' => true, 'slug' => $newSlug]);
            exit;
        }

        // 스킨 삭제 (공통)
        if (($input['action'] ?? '') === 'delete_skin') {
            $group = $input['group'] ?? 'layout';
            $slug = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['slug'] ?? '');
            if (!$slug || $slug === 'default') { echo json_encode(['success' => false, 'error' => 'Cannot delete default']); exit; }
            $dirMap = ['layout' => 'layouts', 'page' => 'page', 'board' => 'board', 'member' => 'member'];
            $dir = BASE_PATH . '/skins/' . ($dirMap[$group] ?? $group) . '/' . $slug;
            if (!is_dir($dir)) { echo json_encode(['success' => false, 'error' => 'Skin not found']); exit; }
            // 현재 활성 스킨이면 삭제 불가
            $activeKeys = ['layout' => 'site_layout', 'page' => 'site_page_skin', 'board' => 'site_board_skin', 'member' => 'site_member_skin'];
            $activeKey = $activeKeys[$group] ?? '';
            if ($activeKey && ($siteSettings[$activeKey] ?? 'default') === $slug) {
                echo json_encode(['success' => false, 'error' => 'Cannot delete active skin']);
                exit;
            }
            foreach (scandir($dir) as $f) { if ($f !== '.' && $f !== '..') unlink($dir . '/' . $f); }
            rmdir($dir);
            $pdo->prepare("DELETE FROM {$prefix}settings WHERE `key` = ?")->execute(['skin_detail_' . $group . '_' . $slug]);
            echo json_encode(['success' => true]);
            exit;
        }

        // 레이아웃/스킨 이름 변경
        if (($input['action'] ?? '') === 'rename_skin') {
            $group = $input['group'] ?? '';
            $slug = preg_replace('/[^a-zA-Z0-9_-]/', '', $input['slug'] ?? '');
            $newTitle = trim($input['title'] ?? '');
            if (!$slug || !$newTitle) { echo json_encode(['success' => false, 'error' => 'Invalid params']); exit; }
            $jsonMap = ['layout' => 'layout.json', 'page' => 'skin.json', 'board' => 'skin.json', 'member' => 'skin.json'];
            $dirMap = ['layout' => 'layouts', 'page' => 'page', 'board' => 'board', 'member' => 'member'];
            $jsonFile = BASE_PATH . '/skins/' . ($dirMap[$group] ?? $group) . '/' . $slug . '/' . ($jsonMap[$group] ?? 'skin.json');
            if (!file_exists($jsonFile)) { echo json_encode(['success' => false, 'error' => 'File not found']); exit; }
            $data = json_decode(file_get_contents($jsonFile), true);
            $locale = $config['locale'] ?? 'ko';
            if (is_array($data['title'] ?? null)) {
                $data['title'][$locale] = $newTitle;
            } else {
                $data['title'] = [$locale => $newTitle];
            }
            file_put_contents($jsonFile, json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            echo json_encode(['success' => true]);
            exit;
        }

        // 스킨 상세 설정 폼 로드
        if (($input['action'] ?? '') === 'load_skin_settings') {
            $group = $input['group'] ?? '';
            $slug = $input['slug'] ?? '';
            $locale = $config['locale'] ?? 'ko';

            // 그룹별 경로/JSON 파일 매핑
            $pathMap = [
                'layout' => ['dir' => BASE_PATH . '/skins/layouts/' . $slug . '/', 'json' => 'layout.json'],
                'page' => ['dir' => BASE_PATH . '/skins/page/' . $slug . '/', 'json' => 'skin.json'],
                'board' => ['dir' => BASE_PATH . '/skins/board/' . $slug . '/', 'json' => 'skin.json'],
                'member' => ['dir' => BASE_PATH . '/skins/member/' . $slug . '/', 'json' => 'skin.json'],
            ];
            $pm = $pathMap[$group] ?? null;
            if (!$pm || !is_dir($pm['dir'])) {
                echo json_encode(['success' => false, 'message' => 'Skin not found']);
                exit;
            }

            $jsonPath = $pm['dir'] . $pm['json'];
            $skinData = file_exists($jsonPath) ? json_decode(file_get_contents($jsonPath), true) : [];

            // 저장된 설정 로드
            $cfgKey = 'skin_detail_' . $group . '_' . $slug;
            $cfgStmt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = ?");
            $cfgStmt->execute([$cfgKey]);
            $savedConfig = json_decode($cfgStmt->fetchColumn() ?: '{}', true) ?: [];

            // 메타 정보 HTML
            $t = function($v) use ($locale) {
                if (is_string($v)) return $v;
                return $v[$locale] ?? $v['en'] ?? $v['ko'] ?? reset($v) ?: '';
            };

            ob_start();
            // 제목 (편집 가능)
            $_skinTitle = $t($skinData['title'] ?? $slug);
            echo '<div class="flex items-center gap-2 mb-4">';
            echo '<input type="text" id="skinTitleInput" value="' . htmlspecialchars($_skinTitle) . '" class="flex-1 px-3 py-2 border border-zinc-200 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg text-sm font-semibold focus:ring-2 focus:ring-blue-500" placeholder="' . (__('site.design.cfg_title') ?? '레이아웃 이름') . '">';
            echo '<button onclick="renameSkin(\'' . $group . '\',\'' . $slug . '\')" class="px-4 py-2 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition whitespace-nowrap">' . (__('site.design.rename') ?? '이름 변경') . '</button>';
            echo '</div>';
            // 경로
            echo '<div class="divide-y divide-zinc-100 dark:divide-zinc-700 mb-6">';
            echo '<div class="flex py-3"><span class="w-20 text-sm text-zinc-500 shrink-0">' . (__('site.design.cfg_path') ?? '경로') . '</span><span class="text-sm text-zinc-800 dark:text-zinc-200">/skins/' . htmlspecialchars($group === 'layout' ? 'layouts' : $group) . '/' . htmlspecialchars($slug) . '/</span></div>';
            // 설명
            if (!empty($skinData['description'])) {
                echo '<div class="flex py-3"><span class="w-20 text-sm text-zinc-500 shrink-0">' . (__('site.design.cfg_desc') ?? '설명') . '</span><span class="text-sm text-zinc-800 dark:text-zinc-200">' . htmlspecialchars($t($skinData['description'])) . '</span></div>';
            }
            // 작성자
            if (!empty($skinData['author']['name'])) {
                $authorHtml = htmlspecialchars($skinData['author']['name']);
                if (!empty($skinData['author']['url'])) {
                    $authorHtml = '<a href="' . htmlspecialchars($skinData['author']['url']) . '" target="_blank" class="text-blue-600 hover:underline">' . $authorHtml . ' <svg class="w-3 h-3 inline" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg></a>';
                }
                echo '<div class="flex py-3"><span class="w-20 text-sm text-zinc-500 shrink-0">' . (__('site.design.cfg_author') ?? '작성자') . '</span><span class="text-sm">' . $authorHtml . '</span></div>';
            }
            // 버전
            if (!empty($skinData['version'])) {
                echo '<div class="flex py-3"><span class="w-20 text-sm text-zinc-500 shrink-0">' . (__('site.design.cfg_version') ?? '버전') . '</span><span class="text-sm text-zinc-800 dark:text-zinc-200">v' . htmlspecialchars($skinData['version']) . '</span></div>';
            }
            echo '</div>';

            // 메뉴 설정 (menus)
            if (!empty($skinData['menus'])) {
                // DB 메뉴 목록 조회
                $dbMenus = $pdo->query("SELECT id, title FROM {$prefix}sitemaps ORDER BY sort_order")->fetchAll(\PDO::FETCH_ASSOC);
                $savedMenus = $savedConfig['_menus'] ?? [];

                echo '<h4 class="text-base font-semibold text-zinc-800 dark:text-zinc-200 mb-4">' . (__('site.design.cfg_menus') ?? '메뉴') . '</h4>';
                echo '<div class="divide-y divide-zinc-100 dark:divide-zinc-700 mb-6">';
                foreach ($skinData['menus'] as $menuKey => $menuDef) {
                    $menuTitle = $t($menuDef['title'] ?? $menuKey);
                    $savedVal = $savedMenus[$menuKey] ?? '';
                    echo '<div class="flex items-center py-3">';
                    echo '<span class="w-40 text-sm font-medium text-zinc-700 dark:text-zinc-300 shrink-0">' . htmlspecialchars($menuTitle) . ' (' . htmlspecialchars($menuKey) . ')</span>';
                    echo '<select name="skin_menu[' . htmlspecialchars($menuKey) . ']" class="flex-1 px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200">';
                    echo '<option value="">-- ' . (__('site.design.select_menu') ?? '메뉴 선택') . ' --</option>';
                    foreach ($dbMenus as $dm) {
                        $sel = ((string)$dm['id'] === (string)$savedVal) ? 'selected' : '';
                        echo '<option value="' . $dm['id'] . '" ' . $sel . '>' . htmlspecialchars($dm['title']) . '</option>';
                    }
                    echo '</select>';
                    echo '</div>';
                }
                echo '</div>';
            }

            // 확장 변수 (vars)
            if (!empty($skinData['vars'])) {
                $renderer = new SkinConfigRenderer($jsonPath, $savedConfig, $locale, $baseUrl);
                echo '<h4 class="text-base font-semibold text-zinc-800 dark:text-zinc-200 mb-4">' . (__('site.design.cfg_vars') ?? '확장 변수') . '</h4>';
                echo '<div id="skinDetailForm">';
                $renderer->renderForm();
                echo '</div>';
            }

            $html = ob_get_clean();
            $hasForm = !empty($skinData['vars']) || !empty($skinData['menus']);
            echo json_encode(['success' => true, 'html' => $html, 'title' => $t($skinData['title'] ?? $slug), 'hasForm' => $hasForm]);
            exit;
        }

        // 스킨 상세 설정 저장
        if (($input['action'] ?? '') === 'save_skin_detail') {
            $group = $input['group'] ?? '';
            $slug = $input['slug'] ?? '';
            $skinConfig = $input['config'] ?? [];
            $cfgKey = 'skin_detail_' . $group . '_' . $slug;
            $stmt = $pdo->prepare("INSERT INTO {$prefix}settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
            $stmt->execute([$cfgKey, json_encode($skinConfig, JSON_UNESCAPED_UNICODE)]);
            echo json_encode(['success' => true]);
            exit;
        }
    }

    // 파일 업로드 (multipart/form-data) — 로고, 푸터 로고 등 이미지
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_POST['action']) && $_POST['action'] === 'upload_skin_image') {
        header('Content-Type: application/json; charset=utf-8');
        $group = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['group'] ?? '');
        $slug = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['slug'] ?? '');
        $fieldName = preg_replace('/[^a-zA-Z0-9_-]/', '', $_POST['field'] ?? '');

        if (!$group || !$slug || !$fieldName || empty($_FILES['file'])) {
            echo json_encode(['success' => false, 'error' => 'Invalid params']);
            exit;
        }

        $file = $_FILES['file'];
        if ($file['error'] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'error' => 'Upload failed: ' . $file['error']]);
            exit;
        }

        // 이미지 유효성 검사
        $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'];
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mimeType = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);
        if (!in_array($mimeType, $allowedTypes)) {
            echo json_encode(['success' => false, 'error' => 'Invalid image type']);
            exit;
        }

        // 저장 경로
        $dirMap = ['layout' => 'layouts', 'page' => 'page', 'board' => 'board', 'member' => 'member'];
        $uploadDir = BASE_PATH . '/storage/skins/' . ($dirMap[$group] ?? $group) . '/' . $slug . '/';
        if (!is_dir($uploadDir)) mkdir($uploadDir, 0775, true);

        $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'png';
        $fileName = $fieldName . '_' . time() . '.' . $ext;
        $filePath = $uploadDir . $fileName;

        if (move_uploaded_file($file['tmp_name'], $filePath)) {
            $webPath = '/storage/skins/' . ($dirMap[$group] ?? $group) . '/' . $slug . '/' . $fileName;

            // DB 설정에도 반영
            $cfgKey = 'skin_detail_' . $group . '_' . $slug;
            $cfgStmt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = ?");
            $cfgStmt->execute([$cfgKey]);
            $existingConfig = json_decode($cfgStmt->fetchColumn() ?: '{}', true) ?: [];
            $existingConfig[$fieldName] = $webPath;
            $pdo->prepare("INSERT INTO {$prefix}settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)")
                ->execute([$cfgKey, json_encode($existingConfig, JSON_UNESCAPED_UNICODE)]);

            echo json_encode(['success' => true, 'path' => $webPath, 'url' => ($config['app_url'] ?? '') . $webPath]);
        } else {
            echo json_encode(['success' => false, 'error' => 'File move failed']);
        }
        exit;
    }

    // 현재 설정 로드
    $settings = [];
    $rows = $pdo->query("SELECT `key`, `value` FROM {$prefix}settings WHERE `key` IN ('site_layout','site_page_skin','site_board_skin','site_member_skin')")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($rows as $r) $settings[$r['key']] = $r['value'];

    $currentLayout = $settings['site_layout'] ?? 'default';
    $currentPageSkin = $settings['site_page_skin'] ?? 'default';
    $currentBoardSkin = $settings['site_board_skin'] ?? 'default';
    $currentMemberSkin = $settings['site_member_skin'] ?? 'default';

    // 스킨 스캔 헬퍼
    function scanSkins($dir, $type, $baseUrl, $locale) {
        $skins = [];
        if (!is_dir($dir)) return $skins;
        $jsonFile = $type === 'layouts' ? 'layout.json' : 'skin.json';
        foreach (scandir($dir) as $d) {
            if ($d === '.' || $d === '..' || !is_dir($dir . $d)) continue;
            $jf = $dir . $d . '/' . $jsonFile;
            $j = file_exists($jf) ? json_decode(file_get_contents($jf), true) : null;
            $skins[$d] = [
                'title' => $j ? ($j['title'][$locale] ?? $j['title']['en'] ?? $d) : ucfirst($d),
                'desc' => $j ? ($j['description'][$locale] ?? $j['description']['en'] ?? '') : '',
                'version' => $j['version'] ?? '',
                'thumbnail' => file_exists($dir . $d . '/thumbnail.png') ? $baseUrl . '/skins/' . $type . '/' . $d . '/thumbnail.png' : '',
            ];
        }
        return $skins;
    }

    $layouts = scanSkins(BASE_PATH . '/skins/layouts/', 'layouts', $baseUrl, $defaultLocale);
    $pageSkins = scanSkins(BASE_PATH . '/skins/page/', 'page', $baseUrl, $defaultLocale);
    $boardSkins = scanSkins(BASE_PATH . '/skins/board/', 'board', $baseUrl, $defaultLocale);
    $memberSkins = scanSkins(BASE_PATH . '/skins/member/', 'member', $baseUrl, $defaultLocale);

} catch (PDOException $e) {
    die('DB 연결 실패');
}

$pageTitle = __('site.design.title') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$pageHeaderTitle = __('site.design.title');
$pageSubTitle = __('site.design.title');
$pageSubDesc = __('site.design.description');

$menuItems = [
    'layout' => ['label' => __('site.design.layout_title') ?? '레이아웃', 'current' => $currentLayout, 'skins' => $layouts, 'dbKey' => 'site_layout'],
    'page' => ['label' => __('site.design.page_skin') ?? '페이지', 'current' => $currentPageSkin, 'skins' => $pageSkins, 'dbKey' => 'site_page_skin'],
    'board' => ['label' => __('site.design.board_skin') ?? '게시판', 'current' => $currentBoardSkin, 'skins' => $boardSkins, 'dbKey' => 'site_board_skin'],
    'member' => ['label' => __('site.design.member_skin') ?? '회원', 'current' => $currentMemberSkin, 'skins' => $memberSkins, 'dbKey' => 'site_member_skin'],
];
?>
<?php include __DIR__ . '/../reservations/_head.php'; ?>

    <div class="flex gap-6">
        <!-- 좌측: 미리보기 + 메뉴 -->
        <div class="w-80 shrink-0">
            <!-- 사이트 미리보기 -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden mb-4">
                <div class="h-52 bg-zinc-100 dark:bg-zinc-700 relative overflow-hidden">
                    <iframe id="sitePreviewFrame" src="<?= $baseUrl ?>/" class="w-full h-full border-0 pointer-events-none" style="transform:scale(0.33);transform-origin:top left;width:303%;height:303%" loading="lazy"></iframe>
                </div>
            </div>

            <!-- 메뉴 목록 -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                <?php foreach ($menuItems as $key => $item): ?>
                <div onclick="switchMenu('<?= $key ?>')" id="menu-<?= $key ?>"
                     class="flex items-center justify-between px-4 py-3.5 cursor-pointer transition border-b border-zinc-100 dark:border-zinc-700 last:border-b-0 hover:bg-zinc-50 dark:hover:bg-zinc-700/50">
                    <div>
                        <span class="text-sm font-semibold text-zinc-900 dark:text-white"><?= $item['label'] ?></span>
                        <span class="text-xs text-zinc-400 ml-1" id="current-<?= $key ?>">[<?= htmlspecialchars($item['skins'][$item['current']]['title'] ?? $item['current']) ?>]</span>
                    </div>
                    <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a10 10 0 11-20 0 10 10 0 0120 0z"/></svg>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- 저장 버튼 -->
            <div class="mt-4">
                <button onclick="saveLayout()" class="w-full px-6 py-2.5 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                    <?= __('site.design.save_layout') ?? '레이아웃 저장' ?>
                </button>
            </div>
        </div>

        <!-- 우측: 선택한 메뉴의 스킨 목록 (초기 숨김) -->
        <div class="w-80 shrink-0" id="rightPanel" style="display:none">
            <?php foreach ($menuItems as $key => $item): ?>
            <div id="panel-<?= $key ?>" class="hidden">
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700">
                    <!-- 패널 헤더 -->
                    <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200"><?= $item['label'] ?></h3>
                        <button onclick="closePanel()" class="p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                        </button>
                    </div>
                    <!-- 스킨 목록 -->
                    <div class="divide-y divide-zinc-100 dark:divide-zinc-700 max-h-[75vh] overflow-y-auto">
                        <!-- 사용 안 함 -->
                        <?php $isNone = $item['current'] === 'none'; ?>
                        <div onclick="selectSkin('<?= $key ?>', 'none', '<?= __('site.design.not_use') ?? '사용 안 함' ?>')" id="skin-<?= $key ?>-none"
                             class="px-6 py-3 cursor-pointer transition <?= $isNone ? 'bg-yellow-50 dark:bg-yellow-900/10' : 'hover:bg-zinc-50 dark:hover:bg-zinc-700/30' ?>">
                            <div class="flex items-center gap-2">
                                <input type="radio" name="skin_<?= $key ?>" value="none" <?= $isNone ? 'checked' : '' ?> class="text-blue-600" readonly>
                                <span class="text-sm text-zinc-600 dark:text-zinc-300"><?= __('site.design.not_use') ?? ($item['label'] . ' 사용 안 함') ?></span>
                            </div>
                        </div>
                        <!-- 다른 스킨 설치 -->
                        <div class="px-6 py-3 text-sm text-zinc-400 dark:text-zinc-500 flex items-center gap-2">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <span><?= __('site.design.install_other') ?? '다른 ' . $item['label'] . ' 설치' ?></span>
                            <span class="text-xs text-zinc-300 dark:text-zinc-600 ml-auto"><?= __('site.design.coming_soon') ?? '준비 중' ?></span>
                        </div>
                        <!-- 설치된 스킨 목록 -->
                        <?php foreach ($item['skins'] as $slug => $skin):
                            $isSelected = $slug === $item['current'];
                        ?>
                        <div onclick="selectSkin('<?= $key ?>', '<?= $slug ?>', '<?= htmlspecialchars($skin['title'], ENT_QUOTES) ?>')" id="skin-<?= $key ?>-<?= $slug ?>" data-skin-slug="<?= $slug ?>"
                             class="px-6 py-4 cursor-pointer transition <?= $isSelected ? 'bg-yellow-50 dark:bg-yellow-900/10' : 'hover:bg-zinc-50 dark:hover:bg-zinc-700/30' ?>">
                            <!-- 라디오 + 타이틀 -->
                            <div class="flex items-center gap-2 mb-3">
                                <input type="radio" name="skin_<?= $key ?>" value="<?= $slug ?>" <?= $isSelected ? 'checked' : '' ?> class="text-blue-600" readonly>
                                <span class="text-sm font-medium text-zinc-900 dark:text-white skin-title"><?= htmlspecialchars($skin['title']) ?></span>
                            </div>
                            <!-- 썸네일 + 액션 링크 -->
                            <div class="flex gap-3 ml-5">
                                <div class="w-32 h-24 shrink-0 rounded-lg overflow-hidden border border-zinc-200 dark:border-zinc-600 bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center">
                                    <?php if (!empty($skin['thumbnail'])): ?>
                                    <img src="<?= htmlspecialchars($skin['thumbnail']) ?>" alt="" class="w-full h-full object-cover" onerror="this.style.display='none'">
                                    <?php else: ?>
                                    <svg class="w-8 h-8 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                    <?php endif; ?>
                                </div>
                                <div class="flex flex-col gap-1.5 text-sm pt-0.5 whitespace-nowrap">
                                    <a href="#" onclick="event.stopPropagation();openSkinSettings('<?= $key ?>','<?= $slug ?>')" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 hover:underline flex items-center gap-1">
                                        <?= __('site.design.detail_settings') ?? '상세 설정' ?>
                                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a10 10 0 11-20 0 10 10 0 0120 0z"/></svg>
                                    </a>
                                    <a href="#" onclick="event.stopPropagation();copySkin('<?= $key ?>','<?= $slug ?>')" class="text-blue-600 hover:text-blue-700 dark:text-blue-400 hover:underline">
                                        <?= __('site.design.duplicate') ?? '복사본 생성' ?>
                                    </a>
                                    <?php if ($slug !== 'default'): ?>
                                    <a href="#" onclick="event.stopPropagation();deleteSkin('<?= $key ?>','<?= $slug ?>')" class="text-red-500 hover:text-red-600 dark:text-red-400 hover:underline">
                                        <?= __('site.design.delete') ?? '삭제' ?>
                                    </a>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <!-- 설정 패널 (상세 설정 클릭 시 표시) -->
        <div class="flex-1 min-w-0" id="settingsPanel" style="display:none">
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700">
                <div class="flex items-center justify-between px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                    <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200" id="settingsPanelTitle"><?= __('site.design.detail_settings') ?? '설정' ?></h3>
                    <button onclick="closeSettings()" class="p-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 transition">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div id="settingsPanelContent" class="p-6 max-h-[75vh] overflow-y-auto">
                    <p class="text-sm text-zinc-400 text-center py-8"><?= __('common.msg.loading') ?? '로딩 중...' ?></p>
                </div>
                <div id="settingsPanelFooter" class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 hidden">
                    <button onclick="saveSkinDetail()" class="px-6 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition">
                        <?= __('common.buttons.save') ?? '저장' ?>
                    </button>
                </div>
            </div>
        </div>
    </div>

<script>
var selectedSkins = {
    layout: '<?= $currentLayout ?>',
    page: '<?= $currentPageSkin ?>',
    board: '<?= $currentBoardSkin ?>',
    member: '<?= $currentMemberSkin ?>'
};
var activeMenu = null;

function switchMenu(key) {
    console.log('[Layout] switchMenu:', key);

    // 같은 메뉴 다시 클릭 → 닫기
    if (activeMenu === key) { closePanel(); return; }
    activeMenu = key;

    // 메뉴 활성화
    document.querySelectorAll('[id^="menu-"]').forEach(function(el) {
        el.classList.remove('bg-blue-50', 'dark:bg-blue-900/20');
    });
    document.getElementById('menu-' + key)?.classList.add('bg-blue-50', 'dark:bg-blue-900/20');

    // 패널 표시
    document.getElementById('rightPanel').style.display = '';
    document.querySelectorAll('[id^="panel-"]').forEach(function(el) { el.classList.add('hidden'); });
    document.getElementById('panel-' + key)?.classList.remove('hidden');
}

function closePanel() {
    document.getElementById('rightPanel').style.display = 'none';
    closeSettings();
    document.querySelectorAll('[id^="menu-"]').forEach(function(el) {
        el.classList.remove('bg-blue-50', 'dark:bg-blue-900/20');
    });
    activeMenu = null;
}

var currentSettingsGroup = '';
var currentSettingsSlug = '';

function renameSkin(group, slug) {
    var input = document.getElementById('skinTitleInput');
    if (!input || !input.value.trim()) return;
    var newTitle = input.value.trim();
    fetch(window.location.href, {
        method: 'POST', headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
        body: JSON.stringify({action:'rename_skin', group:group, slug:slug, title:newTitle})
    }).then(r=>r.json()).then(data=>{
        if (data.success) {
            // 좌측 목록의 제목도 업데이트
            var card = document.querySelector('[data-skin-slug="' + slug + '"] .skin-title');
            if (card) card.textContent = newTitle;
            // 설정 패널 제목 업데이트
            var panelTitle = document.getElementById('settingsPanelTitle');
            if (panelTitle) panelTitle.textContent = newTitle + ' 상세 설정';
            showResultModal(true, '이름이 변경되었습니다.');
        } else {
            showResultModal(false, data.error || '변경에 실패했습니다.');
        }
    });
}

function copySkin(group, slug) {
    fetch(window.location.href, {
        method: 'POST', headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
        body: JSON.stringify({action:'copy_skin', group:group, slug:slug})
    }).then(r=>r.json()).then(data=>{
        if (data.success) {
            showResultModal(true, '복사본이 생성되었습니다.');
            setTimeout(()=>location.reload(), 1000);
        } else {
            showResultModal(false, data.error || '복사에 실패했습니다.');
        }
    });
}

function deleteSkin(group, slug) {
    showConfirmModal({
        title: '이 스킨을 삭제하시겠습니까?',
        message: '「' + slug + '」이(가) 삭제됩니다.',
        checkLabel: '스킨 파일과 설정이 영구 삭제된다는 것을 알고 있습니다.',
        confirmText: '삭제',
        danger: true,
        onConfirm: function() {
            fetch(window.location.href, {
                method: 'POST', headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
                body: JSON.stringify({action:'delete_skin', group:group, slug:slug})
            }).then(r=>r.json()).then(data=>{
                if (data.success) { showResultModal(true, '삭제되었습니다.'); setTimeout(()=>location.reload(), 1000); }
                else { showResultModal(false, data.error || '삭제에 실패했습니다.'); }
            });
        }
    });
}

async function openSkinSettings(group, slug) {
    console.log('[Layout] openSkinSettings:', group, slug);
    currentSettingsGroup = group;
    currentSettingsSlug = slug;

    var panel = document.getElementById('settingsPanel');
    var title = document.getElementById('settingsPanelTitle');
    var content = document.getElementById('settingsPanelContent');
    var footer = document.getElementById('settingsPanelFooter');

    content.innerHTML = '<p class="text-sm text-zinc-400 text-center py-8"><?= __('common.msg.loading') ?? '로딩 중...' ?></p>';
    footer.classList.add('hidden');
    panel.style.display = '';

    try {
        var resp = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ action: 'load_skin_settings', group: group, slug: slug })
        });
        var data = await resp.json();
        if (data.success) {
            title.textContent = data.title + ' <?= __('site.design.detail_settings') ?? '설정' ?>';
            content.innerHTML = data.html;
            // vars 또는 menus가 있으면 저장 버튼 표시
            if (data.hasForm) {
                footer.classList.remove('hidden');
            }
        } else {
            content.innerHTML = '<p class="text-sm text-red-500 text-center py-8">' + (data.message || 'Error') + '</p>';
        }
    } catch (e) {
        content.innerHTML = '<p class="text-sm text-red-500 text-center py-8">' + e.message + '</p>';
    }
}

// 이미지 즉시 업로드 + 프리뷰
async function uploadSkinImage(fileInput, fieldName) {
    var file = fileInput.files[0];
    if (!file) return;

    var fd = new FormData();
    fd.append('action', 'upload_skin_image');
    fd.append('group', currentSettingsGroup);
    fd.append('slug', currentSettingsSlug);
    fd.append('field', fieldName);
    fd.append('file', file);

    try {
        var resp = await fetch(window.location.href, { method: 'POST', body: fd });
        var data = await resp.json();
        if (data.success) {
            // 프리뷰 업데이트
            var img = document.getElementById('img_preview_' + fieldName);
            var ph = document.getElementById('img_preview_' + fieldName + '_placeholder');
            if (img) { img.src = data.url; img.classList.remove('hidden'); }
            if (ph) ph.classList.add('hidden');
            // hidden input 업데이트
            var val = document.getElementById('skin_val_' + fieldName);
            if (val) val.value = data.path;
            showResultModal(true, '이미지가 업로드되었습니다.');
        } else {
            showResultModal(false, data.error || '업로드 실패');
        }
    } catch (e) { showResultModal(false, e.message); }
}

function removeSkinImage(fieldName) {
    var img = document.getElementById('img_preview_' + fieldName);
    var ph = document.getElementById('img_preview_' + fieldName + '_placeholder');
    if (img) { img.src = ''; img.classList.add('hidden'); }
    if (ph) ph.classList.remove('hidden');
    var val = document.getElementById('skin_val_' + fieldName);
    if (val) val.value = '';
}

async function saveSkinDetail() {
    var content = document.getElementById('settingsPanelContent');
    var form = document.getElementById('skinDetailForm');

    var config = {};
    // 메뉴 설정 수집
    var menus = {};
    content.querySelectorAll('[name^="skin_menu["]').forEach(function(el) {
        var m = el.name.match(/^skin_menu\[(.+)]$/);
        if (m) menus[m[1]] = el.value;
    });
    if (Object.keys(menus).length > 0) config['_menus'] = menus;

    if (form) form.querySelectorAll('[name^="skin_config["]').forEach(function(el) {
        var m = el.name.match(/^skin_config\[(.+)]$/);
        if (!m) return;
        if (el.type === 'radio' && !el.checked) return;
        if (el.type === 'checkbox') {
            config[m[1]] = el.checked ? '1' : '0';
        } else {
            config[m[1]] = el.value;
        }
    });
    // 체크 안 된 checkbox 처리
    form.querySelectorAll('.skin-checkbox').forEach(function(cb) {
        if (!cb.checked) config[cb.dataset.name] = '0';
    });

    console.log('[Layout] saveSkinDetail:', currentSettingsGroup, currentSettingsSlug, config);
    try {
        var resp = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({ action: 'save_skin_detail', group: currentSettingsGroup, slug: currentSettingsSlug, config: config })
        });
        var data = await resp.json();
        showResultModal(data.success, data.success ? '' : (data.message || 'Error'));
    } catch (e) {
        showResultModal(false, e.message);
    }
}

function closeSettings() {
    document.getElementById('settingsPanel').style.display = 'none';
}

function selectSkin(group, slug, title) {
    console.log('[Layout] selectSkin:', group, slug);
    selectedSkins[group] = slug;

    // 라디오 업데이트
    document.querySelectorAll('[name="skin_' + group + '"]').forEach(function(r) { r.checked = r.value === slug; });

    // 배경 업데이트
    document.querySelectorAll('#panel-' + group + ' [id^="skin-' + group + '-"]').forEach(function(el) {
        el.classList.remove('bg-yellow-50', 'dark:bg-yellow-900/10');
    });
    document.getElementById('skin-' + group + '-' + slug)?.classList.add('bg-yellow-50', 'dark:bg-yellow-900/10');

    // 좌측 메뉴 현재값 업데이트
    var cur = document.getElementById('current-' + group);
    if (cur) cur.textContent = '[' + title + ']';

    // 레이아웃 변경 시 미리보기 업데이트
    if (group === 'layout') {
        var frame = document.getElementById('sitePreviewFrame');
        if (frame) {
            frame.src = '<?= $baseUrl ?>/' + (slug === 'none' ? '?no_layout=1' : '');
        }
    }
}

async function saveLayout() {
    console.log('[Layout] Saving:', selectedSkins);
    try {
        var resp = await fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
            body: JSON.stringify({
                action: 'save_layout',
                site_layout: selectedSkins.layout,
                site_page_skin: selectedSkins.page,
                site_board_skin: selectedSkins.board,
                site_member_skin: selectedSkins.member
            })
        });
        var data = await resp.json();
        showResultModal(data.success, data.success ? '' : (data.message || 'Error'));
    } catch (e) {
        showResultModal(false, e.message);
    }
}
</script>
<?php include BASE_PATH . '/resources/views/admin/components/multilang-modal.php'; ?>
<?php include BASE_PATH . '/resources/views/admin/partials/result-modal.php'; ?>
    </div>
    </main>
</div>
</body>
</html>
