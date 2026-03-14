<?php
/**
 * RezlyX Admin - 메뉴 관리 페이지
 * 4단 캐스케이딩 패널: 트리 | 컨텍스트 | 메뉴타입 | 상세폼
 */
include_once __DIR__ . '/../components/multilang-button.php';
$pageTitle = __('site.menus.title') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$baseUrl = '';
if (!empty($config['app_url'])) {
    $parsedUrl = parse_url($config['app_url']);
    $baseUrl = rtrim($parsedUrl['path'] ?? '', '/');
}
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');

// DB 연결
try {
    $pdo = new PDO(
        'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx') . ';charset=utf8mb4',
        $_ENV['DB_USERNAME'] ?? 'root',
        $_ENV['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('데이터베이스 연결 실패');
}

// 사이트맵 목록
$sitemaps = $pdo->query("SELECT * FROM rzx_sitemaps ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);

// 메뉴 항목 (사이트맵별)
$menuItems = [];
$stmt = $pdo->query("SELECT * FROM rzx_menu_items ORDER BY sort_order ASC");
while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
    $menuItems[$row['sitemap_id']][] = $row;
}

// 메뉴 항목을 트리 구조로 변환
function buildMenuTree($items, $parentId = null) {
    $tree = [];
    foreach ($items as $item) {
        if ($item['parent_id'] == $parentId) {
            $children = buildMenuTree($items, $item['id']);
            $item['children'] = $children;
            $tree[] = $item;
        }
    }
    return $tree;
}

$sitemapTrees = [];
foreach ($sitemaps as $sitemap) {
    $items = $menuItems[$sitemap['id']] ?? [];
    $sitemapTrees[$sitemap['id']] = buildMenuTree($items);
}

$message = $_GET['msg'] ?? '';
$messageType = $_GET['type'] ?? '';

// 다국어 표시 룰: 선택언어 → 영어 → 기본언어 → DB 원본
$currentLocale = $config['locale'] ?? 'ko';
$defaultLocale = $config['default_language'] ?? 'ko';
$menuLocaleChain = array_unique(array_filter([$currentLocale, 'en', $defaultLocale]));

$placeholders = implode(',', array_fill(0, count($menuLocaleChain), '?'));
$trStmt = $pdo->prepare("SELECT lang_key, locale, content FROM rzx_translations WHERE locale IN ({$placeholders}) AND lang_key LIKE 'menu_item.%'");
$trStmt->execute(array_values($menuLocaleChain));

$menuAllTranslations = []; // [lang_key][locale] = content
while ($tr = $trStmt->fetch(PDO::FETCH_ASSOC)) {
    $menuAllTranslations[$tr['lang_key']][$tr['locale']] = $tr['content'];
}

/**
 * 메뉴 항목의 번역된 제목 가져오기
 * 폴백: 선택언어 → 영어 → 기본언어 → DB 원본
 */
function getMenuTranslatedTitle($itemId, $field, $default) {
    global $menuAllTranslations, $menuLocaleChain;
    $key = "menu_item.{$itemId}.{$field}";
    if (isset($menuAllTranslations[$key])) {
        foreach ($menuLocaleChain as $loc) {
            if (!empty($menuAllTranslations[$key][$loc])) {
                return $menuAllTranslations[$key][$loc];
            }
        }
    }
    return $default;
}

// 메뉴 항목 렌더링 함수
function renderMenuItem($item, $sitemapId, $depth = 0) {
    global $menuAllTranslations, $menuLocaleChain;
    $isHome = $item['is_home'] ? 1 : 0;
    $homeIcon = $item['is_home'] ? ' <span class="text-amber-500" title="Home">&#9751;</span>' : '';
    $translatedTitle = getMenuTranslatedTitle($item['id'], 'title', $item['title']);
    ?>
    <div class="tree-item flex items-center px-3 py-1.5 rounded text-sm text-zinc-700 dark:text-zinc-300"
         draggable="true"
         onclick="event.stopPropagation(); selectMenuItem(<?= $item['id'] ?>, '<?= htmlspecialchars($translatedTitle, ENT_QUOTES) ?>', <?= $sitemapId ?>, <?= $isHome ?>)"
         data-type="menuItem" data-id="<?= $item['id'] ?>"
         data-sitemap-id="<?= $sitemapId ?>"
         data-parent-id="<?= $item['parent_id'] ?? '' ?>"
         data-sort="<?= $item['sort_order'] ?? 0 ?>"
         data-title="<?= htmlspecialchars($translatedTitle, ENT_QUOTES) ?>"
         data-title-original="<?= htmlspecialchars($item['title'], ENT_QUOTES) ?>"
         data-url="<?= htmlspecialchars($item['url'] ?? '', ENT_QUOTES) ?>"
         data-target="<?= htmlspecialchars($item['target'] ?? '_self', ENT_QUOTES) ?>"
         data-icon="<?= htmlspecialchars($item['icon'] ?? '', ENT_QUOTES) ?>"
         data-css-class="<?= htmlspecialchars($item['css_class'] ?? '', ENT_QUOTES) ?>"
         data-description="<?= htmlspecialchars($item['description'] ?? '', ENT_QUOTES) ?>"
         data-open-window="<?= $item['open_window'] ?? 0 ?>"
         data-expand="<?= $item['expand'] ?? 0 ?>"
         data-group-srls="<?= htmlspecialchars($item['group_srls'] ?? '', ENT_QUOTES) ?>">
        <svg class="drag-handle w-3.5 h-3.5 text-zinc-400" fill="currentColor" viewBox="0 0 24 24"><circle cx="9" cy="6" r="1.5"/><circle cx="15" cy="6" r="1.5"/><circle cx="9" cy="12" r="1.5"/><circle cx="15" cy="12" r="1.5"/><circle cx="9" cy="18" r="1.5"/><circle cx="15" cy="18" r="1.5"/></svg>
        <?php if (!empty($item['children'])): ?>
        <button type="button" class="tree-toggle mr-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300" onclick="toggleTreeChildren(this, event)">
            <svg class="w-3.5 h-3.5 transition-transform" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
        </button>
        <?php else: ?>
        <span class="text-zinc-400 dark:text-zinc-500 mr-1">├</span>
        <?php endif; ?>
        <span class="truncate"><?= htmlspecialchars($translatedTitle) ?><?= $homeIcon ?></span>
        <?php if (!empty($item['icon'])): ?>
        <span class="ml-1 text-zinc-400 text-xs"><?= htmlspecialchars($item['icon']) ?></span>
        <?php endif; ?>
    </div>
    <?php if (!empty($item['children'])): ?>
    <div class="tree-children">
        <?php foreach ($item['children'] as $child): ?>
        <?php renderMenuItem($child, $sitemapId, $depth + 1); ?>
        <?php endforeach; ?>
    </div>
    <?php endif;
}

// 바로가기 메뉴 선택 트리 렌더링
function renderShortcutMenuItem($item, $depth = 1) {
    global $menuAllTranslations, $menuLocaleChain;
    $translatedTitle = getMenuTranslatedTitle($item['id'], 'title', $item['title']);
    $pl = ($depth * 12) + 8;
    ?>
    <div class="shortcut-menu-item flex items-center px-2 py-1 text-xs cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/30 text-blue-600 dark:text-blue-400"
         style="padding-left: <?= $pl ?>px"
         onclick="selectShortcutMenu(<?= $item['id'] ?>, '<?= htmlspecialchars($translatedTitle, ENT_QUOTES) ?>', '<?= htmlspecialchars($item['url'] ?? '', ENT_QUOTES) ?>')">
        <span class="text-zinc-400 mr-1">└</span>
        <?= htmlspecialchars($translatedTitle) ?>
    </div>
    <?php if (!empty($item['children'])): ?>
        <?php foreach ($item['children'] as $child): ?>
            <?php renderShortcutMenuItem($child, $depth + 1); ?>
        <?php endforeach; ?>
    <?php endif;
}
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
    <style>
        body { font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; }
        .tree-item { cursor: pointer; user-select: none; }
        .tree-item:hover { background-color: rgba(59,130,246,0.08); }
        .tree-item.selected { background-color: rgba(59,130,246,0.15); }
        .tree-item.selected .sitemap-title { font-weight: 700; }
        .tree-children { padding-left: 1.25rem; border-left: 1px solid #e4e4e7; margin-left: 0.75rem; }
        .dark .tree-children { border-left-color: #3f3f46; }
        .dark .tree-item:hover { background-color: rgba(59,130,246,0.15); }
        /* 드래그&드롭 */
        .tree-item[draggable="true"] { cursor: grab; }
        .tree-item[draggable="true"]:active { cursor: grabbing; }
        .tree-item.dragging { opacity: 0.4; }
        .tree-item.drag-over-top { box-shadow: 0 -2px 0 0 #3b82f6 inset; }
        .tree-item.drag-over-bottom { box-shadow: 0 2px 0 0 #3b82f6 inset; }
        .tree-item.drag-over-inside { background-color: rgba(59,130,246,0.18); outline: 2px dashed #3b82f6; outline-offset: -2px; }
        .drag-handle { cursor: grab; opacity: 0.35; flex-shrink: 0; margin-right: 4px; }
        .tree-item:hover .drag-handle { opacity: 0.7; }
        .drag-handle:active { cursor: grabbing; }
        .tree-item.cut-item { opacity: 0.45; border-left: 2px dashed #f59e0b; }
        .tree-toggle svg { transition: transform 0.15s; }
        .tree-toggle.collapsed svg { transform: rotate(-90deg); }
        .panel-card { min-width: 240px; max-width: 280px; }
        .ctx-btn { display: flex; align-items: center; justify-content: space-between; width: 100%; padding: 0.5rem 0.875rem; font-size: 0.875rem; color: #374151; transition: background-color 0.15s; border-bottom: 1px solid #f4f4f5; }
        .ctx-btn:last-child { border-bottom: none; }
        .ctx-btn:hover { background-color: #f4f4f5; }
        .ctx-btn.active { background-color: #374151; color: #fff; }
        .ctx-btn.danger { color: #dc2626; }
        .ctx-btn.danger:hover { background-color: #fef2f2; }
        .dark .ctx-btn { color: #d4d4d8; border-bottom-color: #3f3f46; }
        .dark .ctx-btn:hover { background-color: #3f3f46; }
        .dark .ctx-btn.active { background-color: #3b82f6; color: #fff; }
        .dark .ctx-btn.danger { color: #f87171; }
        .dark .ctx-btn.danger:hover { background-color: rgba(239,68,68,0.1); }
        .chevron { width: 16px; height: 16px; flex-shrink: 0; }
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
            $pageHeaderTitle = __('site.menus.title');
            include __DIR__ . '/../partials/admin-topbar.php';
            ?>

            <div class="p-6">
                <?php if ($message): ?>
                <div class="mb-4 p-4 rounded-lg <?= $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-300 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-300 border border-red-200 dark:border-red-800' ?>">
                    <?= htmlspecialchars($message) ?>
                </div>
                <?php endif; ?>

                <!-- 4단 캐스케이딩 패널 -->
                <div class="flex items-start" style="gap:5px;">

                    <!-- ▶ 패널1: 사이트맵 트리 -->
                    <div class="flex-shrink-0 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden" style="min-width:300px; max-width:340px;">
                        <div class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">
                            <h2 class="text-sm font-bold text-zinc-900 dark:text-white"><?= __('site.menus.site_menu_edit') ?></h2>
                        </div>
                        <!-- 검색 -->
                        <div class="p-3 border-b border-zinc-200 dark:border-zinc-700">
                            <div class="flex gap-1.5">
                                <input type="text" id="menuSearch" placeholder="<?= __('site.menus.search_placeholder') ?>"
                                       class="flex-1 px-2.5 py-1.5 text-sm border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                <button onclick="searchMenu()" class="px-3 py-1.5 text-xs bg-zinc-800 dark:bg-zinc-600 text-white rounded hover:bg-zinc-700 dark:hover:bg-zinc-500 transition font-medium">
                                    <?= __('site.menus.search') ?>
                                </button>
                            </div>
                        </div>
                        <!-- 트리 -->
                        <div class="p-3 space-y-2 min-h-[400px] max-h-[550px] overflow-y-auto" id="sitemapTree">
                            <?php foreach ($sitemaps as $sitemap): ?>
                            <div class="sitemap-group" data-sitemap-id="<?= $sitemap['id'] ?>">
                                <div class="tree-item flex items-center px-3 py-2 rounded text-sm font-medium text-zinc-900 dark:text-white"
                                     onclick="selectSitemap(<?= $sitemap['id'] ?>, '<?= htmlspecialchars($sitemap['title'], ENT_QUOTES) ?>')"
                                     data-type="sitemap" data-id="<?= $sitemap['id'] ?>">
                                    <svg class="w-4 h-4 mr-2 text-zinc-500 dark:text-zinc-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
                                    </svg>
                                    <span class="sitemap-title"><?= htmlspecialchars($sitemap['title']) ?></span>
                                </div>
                                <?php if (!empty($sitemapTrees[$sitemap['id']])): ?>
                                <div class="tree-children mt-1">
                                    <?php foreach ($sitemapTrees[$sitemap['id']] as $item): ?>
                                    <?php renderMenuItem($item, $sitemap['id']); ?>
                                    <?php endforeach; ?>
                                </div>
                                <?php endif; ?>
                            </div>
                            <?php if ($sitemap !== end($sitemaps)): ?>
                            <hr class="border-zinc-200 dark:border-zinc-700">
                            <?php endif; ?>
                            <?php endforeach; ?>

                            <?php if (empty($sitemaps)): ?>
                            <div class="text-center py-8 text-zinc-400 dark:text-zinc-500">
                                <p class="text-sm"><?= __('site.menus.no_sitemaps') ?></p>
                            </div>
                            <?php endif; ?>
                        </div>
                        <!-- 사이트맵 추가 -->
                        <div class="p-3 border-t border-zinc-200 dark:border-zinc-700">
                            <button onclick="addSitemap()" class="flex items-center text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <?= __('site.menus.add_sitemap') ?>
                            </button>
                        </div>
                    </div>

                    <!-- ▶ 패널2: 컨텍스트 액션 -->
                    <div id="panel2" class="panel-card flex-shrink-0 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden hidden">
                        <div class="flex items-center justify-between px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">
                            <h3 id="panel2Title" class="text-sm font-bold text-zinc-900 dark:text-white truncate"></h3>
                            <button onclick="closePanel(2)" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 ml-2 flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <!-- 사이트맵 컨텍스트 -->
                        <div id="sitemapCtx" class="hidden">
                            <button onclick="editSitemapItems()" class="ctx-btn" data-ctx="edit_sitemap">
                                <span><?= __('site.menus.edit_sitemap') ?></span>
                                <svg class="chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                            <button onclick="openAddMenu()" class="ctx-btn" data-ctx="add_menu">
                                <span><?= __('site.menus.add_menu') ?></span>
                                <svg class="chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                            <button onclick="applyDesignBulk()" class="ctx-btn">
                                <span><?= __('site.menus.design_bulk') ?></span>
                                <svg class="chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                            <button id="pasteSitemapBtn" onclick="pasteSitemap()" class="ctx-btn" style="opacity:0.4; pointer-events:none;">
                                <span><?= __('site.menus.paste') ?></span>
                            </button>
                            <button onclick="deleteSitemap()" class="ctx-btn danger">
                                <span><?= __('site.menus.delete') ?></span>
                            </button>
                            <button onclick="renameSitemap()" class="ctx-btn">
                                <span><?= __('site.menus.rename') ?></span>
                            </button>
                        </div>
                        <!-- 메뉴 항목 컨텍스트 -->
                        <div id="menuItemCtx" class="hidden">
                            <button onclick="editMenuItem()" class="ctx-btn">
                                <span><?= __('site.menus.edit_item') ?></span>
                                <svg class="chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                            <button onclick="openAddSubMenu()" class="ctx-btn" data-ctx="add_sub">
                                <span><?= __('site.menus.add_sub_menu') ?></span>
                                <svg class="chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                            <button onclick="toggleHomeMenu()" class="ctx-btn">
                                <span id="homeToggleText"><?= __('site.menus.set_home') ?></span>
                            </button>
                            <button onclick="cutMenuItem()" class="ctx-btn">
                                <span><?= __('site.menus.cut') ?></span>
                            </button>
                            <button onclick="copyMenuItem()" class="ctx-btn">
                                <span><?= __('site.menus.copy') ?></span>
                            </button>
                            <button onclick="pasteAsChild()" class="ctx-btn">
                                <span><?= __('site.menus.paste') ?></span>
                            </button>
                            <button onclick="deleteMenuItem()" class="ctx-btn danger">
                                <span><?= __('site.menus.delete') ?></span>
                            </button>
                            <button onclick="renameMenuItem()" class="ctx-btn">
                                <span><?= __('site.menus.rename') ?></span>
                            </button>
                        </div>
                    </div>

                    <!-- ▶ 패널3: 메뉴 타입 선택 -->
                    <div id="panel3" class="panel-card flex-shrink-0 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden hidden">
                        <div class="flex items-center justify-between px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">
                            <h3 id="panel3Title" class="text-sm font-bold text-zinc-900 dark:text-white"><?= __('site.menus.add_menu') ?></h3>
                            <button onclick="closePanel(3)" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 ml-2 flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <!-- 사이트맵 편집 패널 (동적 표시) -->
                        <div id="sitemapEditPanel" class="hidden p-4 space-y-3">
                            <div>
                                <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.menus.sitemap_name') ?></label>
                                <input type="text" id="editSitemapTitle"
                                       class="w-full px-2.5 py-1.5 text-sm border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                            </div>
                            <div class="flex justify-end">
                                <button type="button" onclick="saveSitemapEdit()" class="px-4 py-1.5 text-sm text-white bg-blue-600 rounded hover:bg-blue-700 transition font-medium">
                                    <?= __('site.menus.confirm') ?>
                                </button>
                            </div>
                        </div>
                        <!-- 일괄 디자인 설정 패널 -->
                        <div id="designBulkPanel" class="hidden p-4 space-y-3">
                            <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= __('site.menus.design_bulk_desc') ?></p>
                            <div>
                                <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.menus.layout') ?></label>
                                <select id="bulkLayoutSelect" class="w-full px-2.5 py-1.5 text-sm border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                                    <option value="">— <?= __('site.menus.select_layout') ?> —</option>
                                    <option value="default"><?= __('site.menus.default_layout') ?></option>
                                </select>
                            </div>
                            <div class="flex justify-end">
                                <button type="button" onclick="alert('<?= __('site.menus.design_coming_soon') ?>')" class="px-4 py-1.5 text-sm text-white bg-blue-600 rounded hover:bg-blue-700 transition font-medium">
                                    <?= __('site.menus.apply') ?>
                                </button>
                            </div>
                        </div>
                        <!-- 메뉴 타입 목록 -->
                        <div id="menuTypeList">
                            <button onclick="selectMenuType('page')" class="ctx-btn" data-mtype="page">
                                <span><?= __('site.menus.type_page') ?></span>
                                <svg class="chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                            <button onclick="selectMenuType('widget')" class="ctx-btn" data-mtype="widget">
                                <span><?= __('site.menus.type_widget') ?></span>
                                <svg class="chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                            <button onclick="selectMenuType('external')" class="ctx-btn" data-mtype="external">
                                <span><?= __('site.menus.type_external') ?></span>
                                <svg class="chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                            <button onclick="selectMenuType('board')" class="ctx-btn" data-mtype="board">
                                <span><?= __('site.menus.type_board') ?></span>
                                <svg class="chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                            <button onclick="selectMenuType('member')" class="ctx-btn" data-mtype="member">
                                <span><?= __('site.menus.type_member') ?></span>
                                <svg class="chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                            <button onclick="selectMenuType('shortcut')" class="ctx-btn" data-mtype="shortcut">
                                <span><?= __('site.menus.type_shortcut') ?></span>
                                <svg class="chevron" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                            </button>
                        </div>
                        <div class="p-3 border-t border-zinc-200 dark:border-zinc-700">
                            <button onclick="installMenuType()" class="flex items-center text-sm text-blue-600 dark:text-blue-400 hover:text-blue-800 dark:hover:text-blue-300 transition">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <?= __('site.menus.install_menu_type') ?>
                            </button>
                        </div>
                    </div>

                    <!-- ▶ 패널4: 상세 폼 -->
                    <div id="panel4" class="panel-card flex-shrink-0 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden hidden" style="min-width:280px; max-width:320px;">
                        <div class="flex items-center justify-between px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">
                            <h3 id="panel4Title" class="text-sm font-bold text-zinc-900 dark:text-white"></h3>
                            <button onclick="closePanel(4)" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 ml-2 flex-shrink-0">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div class="p-4">
                            <p id="panel4Desc" class="text-xs text-blue-600 dark:text-blue-400 mb-4"></p>
                            <form id="menuForm" class="space-y-3">
                                <input type="hidden" id="formAction" value="">
                                <input type="hidden" id="formId" value="">
                                <input type="hidden" id="formSitemapId" value="">
                                <input type="hidden" id="formParentId" value="">
                                <input type="hidden" id="formMenuType" value="">
                                <!-- 메뉴 이름 -->
                                <div>
                                    <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.menus.field_name') ?></label>
                                    <div class="flex items-center gap-1">
                                        <input type="text" id="formTitle" required
                                               class="flex-1 px-2.5 py-1.5 text-sm border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        <?= rzx_multilang_btn("openMenuMultilang('title')") ?>
                                    </div>
                                </div>
                                <!-- 메뉴 아이콘 -->
                                <div>
                                    <label class="flex items-center text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">
                                        <?= __('site.menus.field_icon') ?>
                                        <button type="button" onclick="toggleHelp('icon')" class="ml-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4m0-4h.01"/></svg>
                                        </button>
                                    </label>
                                    <div id="help-icon" class="field-help hidden mb-1.5 p-2.5 bg-sky-50 dark:bg-sky-900/30 border border-sky-200 dark:border-sky-800 rounded text-xs text-sky-700 dark:text-sky-300 relative">
                                        <?= __('site.menus.help_icon') ?>
                                        <button type="button" onclick="closeHelp('icon')" class="absolute top-1.5 right-1.5 text-sky-400 hover:text-sky-600">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                    <input type="text" id="formIcon" placeholder="fa-home"
                                           class="w-full px-2.5 py-1.5 text-sm border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                                <!-- 메뉴 클래스 -->
                                <div>
                                    <label class="flex items-center text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">
                                        <?= __('site.menus.field_class') ?>
                                        <button type="button" onclick="toggleHelp('class')" class="ml-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4m0-4h.01"/></svg>
                                        </button>
                                    </label>
                                    <div id="help-class" class="field-help hidden mb-1.5 p-2.5 bg-sky-50 dark:bg-sky-900/30 border border-sky-200 dark:border-sky-800 rounded text-xs text-sky-700 dark:text-sky-300 relative">
                                        <?= __('site.menus.help_class') ?>
                                        <button type="button" onclick="closeHelp('class')" class="absolute top-1.5 right-1.5 text-sky-400 hover:text-sky-600">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                    <input type="text" id="formCssClass"
                                           class="w-full px-2.5 py-1.5 text-sm border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                                <!-- 메뉴 설명 -->
                                <div>
                                    <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.menus.field_desc') ?></label>
                                    <div class="flex items-start gap-1">
                                        <textarea id="formDesc" rows="3"
                                                  class="flex-1 px-2.5 py-1.5 text-sm border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent resize-y"></textarea>
                                        <?= rzx_multilang_btn("openMenuMultilang('description')") ?>
                                    </div>
                                </div>
                                <!-- 메뉴 ID (일반 메뉴용) -->
                                <div id="formMenuIdWrap">
                                    <label class="flex items-center text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1">
                                        <?= __('site.menus.field_menu_id') ?>
                                        <button type="button" onclick="toggleHelp('url')" class="ml-1 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10"/><path d="M12 16v-4m0-4h.01"/></svg>
                                        </button>
                                    </label>
                                    <div id="help-url" class="field-help hidden mb-1.5 p-2.5 bg-sky-50 dark:bg-sky-900/30 border border-sky-200 dark:border-sky-800 rounded text-xs text-sky-700 dark:text-sky-300 relative">
                                        <?= __('site.menus.help_menu_id') ?>
                                        <button type="button" onclick="closeHelp('url')" class="absolute top-1.5 right-1.5 text-sky-400 hover:text-sky-600">
                                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                                        </button>
                                    </div>
                                    <input type="text" id="formUrl" placeholder="<?= __('site.menus.menu_id_placeholder') ?>"
                                           class="w-full px-2.5 py-1.5 text-sm border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                </div>
                                <!-- 바로가기 링크 (shortcut 전용) -->
                                <div id="formShortcutWrap" class="hidden">
                                    <!-- 탭 -->
                                    <div class="flex border-b border-zinc-300 dark:border-zinc-600 mb-2">
                                        <button type="button" id="tabUrlLink" onclick="switchShortcutTab('url')"
                                                class="px-3 py-1.5 text-xs font-medium border-b-2 transition">
                                            <?= __('site.menus.tab_url_link') ?>
                                        </button>
                                        <button type="button" id="tabMenuLink" onclick="switchShortcutTab('menu')"
                                                class="px-3 py-1.5 text-xs font-medium border-b-2 transition">
                                            <?= __('site.menus.tab_menu_link') ?>
                                        </button>
                                    </div>
                                    <!-- URL 링크 탭 -->
                                    <div id="shortcutUrlPanel">
                                        <input type="text" id="formShortcutUrl" placeholder="http://"
                                               class="w-full px-2.5 py-1.5 text-sm border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                    </div>
                                    <!-- 메뉴 링크 탭 -->
                                    <div id="shortcutMenuPanel" class="hidden">
                                        <div class="px-2.5 py-1.5 mb-2 text-xs text-zinc-500 dark:text-zinc-400 bg-zinc-100 dark:bg-zinc-700 rounded border border-zinc-200 dark:border-zinc-600" id="shortcutSelectedMenu">
                                            <?= __('site.menus.select_menu_hint') ?>
                                        </div>
                                        <div class="max-h-[250px] overflow-y-auto border border-zinc-200 dark:border-zinc-600 rounded">
                                            <?php foreach ($sitemaps as $sm): ?>
                                            <div class="px-2 pt-2 pb-1">
                                                <div class="flex items-center text-xs font-bold text-zinc-600 dark:text-zinc-300">
                                                    <svg class="w-3.5 h-3.5 mr-1 text-zinc-400" fill="currentColor" viewBox="0 0 20 20"><path d="M2 6a2 2 0 012-2h5l2 2h5a2 2 0 012 2v6a2 2 0 01-2 2H4a2 2 0 01-2-2V6z"/></svg>
                                                    <?= htmlspecialchars($sm['title']) ?>
                                                </div>
                                            </div>
                                            <?php if (!empty($sitemapTrees[$sm['id']])): ?>
                                                <?php foreach ($sitemapTrees[$sm['id']] as $mi): ?>
                                                    <?php renderShortcutMenuItem($mi, 1); ?>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                            <?php endforeach; ?>
                                        </div>
                                    </div>
                                </div>
                                <!-- 링크 열기 -->
                                <div id="formTargetWrap">
                                    <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-1"><?= __('site.menus.link_target') ?></label>
                                    <select id="formTarget"
                                            class="w-full px-2.5 py-1.5 text-sm border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-transparent">
                                        <option value="_self"><?= __('site.menus.target_self') ?></option>
                                        <option value="_blank"><?= __('site.menus.target_blank') ?></option>
                                    </select>
                                </div>
                                <!-- 하위 메뉴 확장 -->
                                <div id="formExpandWrap">
                                    <label class="flex items-center text-xs text-zinc-700 dark:text-zinc-300 cursor-pointer">
                                        <input type="checkbox" id="formExpand" class="mr-2 rounded border-zinc-300 dark:border-zinc-600">
                                        <?= __('site.menus.expand_default') ?>
                                    </label>
                                </div>
                                <!-- 확인 버튼 -->
                                <div class="flex justify-end pt-2">
                                    <button type="button" onclick="saveForm()" class="px-4 py-1.5 text-sm text-white bg-blue-600 rounded hover:bg-blue-700 transition font-medium">
                                        <?= __('site.menus.confirm') ?>
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>

                </div><!-- /flex -->
            </div>
        </main>
    </div>

<!-- Toast -->
<div id="menuToast" class="hidden fixed bottom-6 left-1/2 -translate-x-1/2 px-4 py-2 bg-zinc-800 text-white text-sm rounded-lg shadow-lg z-50 transition-opacity"></div>

<!-- jQuery + Summernote (다국어 모달 의존성) -->
<script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>

<?php include __DIR__ . '/../components/multilang-modal.php'; ?>
<?php include __DIR__ . '/menus-js.php'; ?>
</body>
</html>
