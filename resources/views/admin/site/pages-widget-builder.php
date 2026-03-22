<?php
/**
 * RezlyX Admin - WYSIWYG 위젯 빌더
 * 실시간 미리보기 + 드래그앤드롭 + 설정 편집
 */
$pageTitle = __('site.widget_builder.title') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';

try {
    $pdo = new PDO(
        'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx'),
        $_ENV['DB_USERNAME'] ?? 'root',
        $_ENV['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('DB 연결 실패');
}

$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
$currentLocale = $config['locale'] ?? 'ko';
$pageSlug = $_GET['slug'] ?? 'home';

// ======= AJAX 요청 처리 (별도 파일) =======
include __DIR__ . '/pages-widget-builder-ajax.php';

// ======= 데이터 로드 =======
$availableWidgets = $pdo->query("SELECT * FROM rzx_widgets WHERE is_active = 1 ORDER BY type, name")->fetchAll(PDO::FETCH_ASSOC);

// 파일 기반 위젯 로더 (widget.json 다국어 데이터 활용)
$widgetLoader = new \RzxLib\Core\Modules\WidgetLoader($pdo, BASE_PATH . '/widgets');
$widgetLoader->syncToDatabase();  // 파일 → DB 자동 동기화
$fileWidgets = $widgetLoader->scan();
$_pwStmt = $pdo->prepare("
    SELECT pw.*, w.slug as widget_slug, w.name as widget_name, w.icon, w.type as widget_type, w.config_schema
    FROM rzx_page_widgets pw
    JOIN rzx_widgets w ON pw.widget_id = w.id
    WHERE pw.page_slug = ?
    ORDER BY pw.sort_order ASC
");
$_pwStmt->execute([$pageSlug]);
$placedWidgets = $_pwStmt->fetchAll(PDO::FETCH_ASSOC);

// 지원 언어 로드
$supportedLangs = ['ko','en','ja'];
try {
    $langSetting = $pdo->query("SELECT `value` FROM rzx_settings WHERE `key` = 'supported_languages'")->fetchColumn();
    if ($langSetting) $supportedLangs = json_decode($langSetting, true) ?: $supportedLangs;
} catch (\Exception $e) {}
$langNames = [
    'ko' => '한국어', 'en' => 'English', 'ja' => '日本語',
    'zh_CN' => '简体中文', 'zh_TW' => '繁體中文',
    'de' => 'Deutsch', 'es' => 'Español', 'fr' => 'Français',
    'id' => 'Indonesia', 'mn' => 'Монгол', 'ru' => 'Русский',
    'tr' => 'Türkçe', 'vi' => 'Tiếng Việt'
];

$iconMap = [
    'sparkles' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
    'grid' => 'M4 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2V6zM14 6a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2V6zM4 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2H6a2 2 0 01-2-2v-2zM14 16a2 2 0 012-2h2a2 2 0 012 2v2a2 2 0 01-2 2h-2a2 2 0 01-2-2v-2z',
    'briefcase' => 'M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    'chart-bar' => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    'chat' => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
    'megaphone' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z',
    'document-text' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z',
    'arrows-expand' => 'M4 8V4m0 0h4M4 4l5 5m11-1V4m0 0h-4m4 0l-5 5M4 16v4m0 0h4m-4 0l5-5m11 5l-5-5m5 5v-4m0 4h-4',
    'cube' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
];


$pageHeaderTitle = __('site.widget_builder.title');
?>
<?php include __DIR__ . '/../reservations/_head.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.0/Sortable.min.js"></script>
    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.css" rel="stylesheet">
    <script src="https://cdn.jsdelivr.net/npm/summernote@0.8.20/dist/summernote-lite.min.js"></script>
    <style>
        .widget-ghost { opacity: 0.3; }
        .widget-chosen .widget-toolbar { opacity: 1 !important; }
        .widget-block:hover .widget-toolbar { opacity: 1; }
        .widget-palette-item { cursor: pointer; }
        .widget-palette-item:hover { transform: translateY(-1px); box-shadow: 0 2px 8px rgba(0,0,0,0.1); }
        .category-item { cursor: pointer; transition: all 0.15s; }
        .category-item:hover, .category-item.active { background: rgb(239 246 255); }
        .dark .category-item:hover, .dark .category-item.active { background: rgb(30 58 138 / 0.2); }
        .category-item.active { border-right: 3px solid rgb(59 130 246); }
        .widget-flyout { transition: opacity 0.15s, transform 0.15s; }
        .widget-flyout.hidden { opacity: 0; transform: translateX(-8px); pointer-events: none; }
        .line-clamp-2 { display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden; }
        #editSidePanel { transition: width 0.2s ease; }
        .note-editor { border-radius: 0.5rem; overflow: hidden; }
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
    </style>
        </div><!-- close p-6 from _head.php -->

            <div class="flex flex-1 overflow-hidden">
                <!-- 왼쪽: 위젯 팔레트 (카테고리 + 플라이아웃) -->
                <?php
                // 카테고리별 그룹핑
                $widgetCategories = [];
                foreach ($availableWidgets as $aw) {
                    $cat = $aw['category'] ?? 'general';
                    $widgetCategories[$cat][] = $aw;
                }
                $categoryMeta = [
                    'system' => ['icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z', 'color' => 'emerald'],
                    'layout' => ['icon' => 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z', 'color' => 'blue'],
                    'content' => ['icon' => 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', 'color' => 'emerald'],
                    'marketing' => ['icon' => 'M11 5.882V19.24a1.76 1.76 0 01-3.417.592l-2.147-6.15M18 13a3 3 0 100-6M5.436 13.683A4.001 4.001 0 017 6h1.832c4.1 0 7.625-1.234 9.168-3v14c-1.543-1.766-5.067-3-9.168-3H7a3.988 3.988 0 01-1.564-.317z', 'color' => 'amber'],
                    'general' => ['icon' => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4', 'color' => 'purple'],
                ];
                ?>
                <aside id="widgetPalettePanel" class="relative w-48 bg-white dark:bg-zinc-800 border-r border-zinc-200 dark:border-zinc-700 flex flex-col overflow-visible flex-shrink-0 z-30">
                    <div class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">
                        <h3 class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider"><?= __('site.widget_builder.available_widgets') ?></h3>
                    </div>
                    <div id="widgetPalette" class="flex-1 overflow-y-auto py-2">
                        <?php foreach ($widgetCategories as $catKey => $catWidgets):
                            $meta = $categoryMeta[$catKey] ?? $categoryMeta['general'];
                            $catLabel = __('site.widget_builder.cat.' . $catKey);
                            if ($catLabel === 'admin.site.widget_builder.cat.' . $catKey) $catLabel = ucfirst($catKey);
                        ?>
                        <div class="category-item group flex items-center px-4 py-2.5" data-category="<?= $catKey ?>">
                            <div class="w-7 h-7 bg-<?= $meta['color'] ?>-100 dark:bg-<?= $meta['color'] ?>-900/30 rounded-lg flex items-center justify-center mr-3 flex-shrink-0">
                                <svg class="w-3.5 h-3.5 text-<?= $meta['color'] ?>-600 dark:text-<?= $meta['color'] ?>-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $meta['icon'] ?>"/>
                                </svg>
                            </div>
                            <div class="flex-1 min-w-0">
                                <p class="text-xs font-semibold text-zinc-700 dark:text-zinc-200"><?= htmlspecialchars($catLabel) ?></p>
                                <p class="text-[10px] text-zinc-400 dark:text-zinc-500"><?= count($catWidgets) ?> widgets</p>
                            </div>
                            <svg class="w-3.5 h-3.5 text-zinc-400 dark:text-zinc-500 flex-shrink-0 transition-transform group-hover:translate-x-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- 플라이아웃 패널 -->
                    <div id="widgetFlyout" class="widget-flyout hidden absolute left-full top-0 bottom-0 w-72 bg-white dark:bg-zinc-800 border-l border-zinc-200 dark:border-zinc-700 shadow-xl flex flex-col z-40" style="min-height:100%;">
                        <div class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                            <h3 id="flyoutTitle" class="text-xs font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider"></h3>
                            <button id="flyoutClose" class="p-1 rounded hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-400">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                        </div>
                        <div id="flyoutContent" class="flex-1 overflow-y-auto p-3 grid grid-cols-2 gap-2 auto-rows-min content-start">
                        </div>
                    </div>
                </aside>

                <!-- 위젯 데이터 (JS용, hidden) -->
                <div id="widgetDataStore" class="hidden">
                    <?php foreach ($availableWidgets as $aw):
                        $slug = $aw['slug'];
                        $fileDef = $fileWidgets[$slug] ?? null;
                        $localName = $fileDef ? \RzxLib\Core\Modules\WidgetLoader::localizedValue($fileDef['name'] ?? $slug, $currentLocale) : $aw['name'];
                        $localDesc = $fileDef ? \RzxLib\Core\Modules\WidgetLoader::localizedValue($fileDef['description'] ?? '', $currentLocale) : ($aw['description'] ?? '');
                        // config_schema: widget.json 우선 → DB 폴백
                        $configSchema = $aw['config_schema'] ?? '{}';
                        if ($fileDef && !empty($fileDef['config_schema'])) {
                            $configSchema = json_encode($fileDef['config_schema'], JSON_UNESCAPED_UNICODE);
                        }
                    ?>
                    <div class="widget-palette-item"
                         data-widget-id="<?= $aw['id'] ?>"
                         data-widget-slug="<?= htmlspecialchars($slug) ?>"
                         data-widget-name="<?= htmlspecialchars($localName) ?>"
                         data-widget-icon="<?= htmlspecialchars($fileDef['icon'] ?? $aw['icon']) ?>"
                         data-widget-category="<?= htmlspecialchars($fileDef['category'] ?? $aw['category'] ?? 'general') ?>"
                         data-widget-description="<?= htmlspecialchars($localDesc) ?>"
                         data-config-schema="<?= htmlspecialchars($configSchema) ?>"
                         data-default-config="<?= htmlspecialchars($aw['default_config'] ?? '{}') ?>"
                         data-widget-thumbnail="<?= $fileDef ? htmlspecialchars($widgetLoader->getThumbnailUrl($slug, $baseUrl) ?? '') : '' ?>">
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- 가운데: WYSIWYG 캔버스 -->
                <div class="flex-1 overflow-y-auto bg-zinc-200/50 dark:bg-zinc-950">
                    <!-- 상단 툴바 -->
                    <div class="sticky top-0 z-30 bg-zinc-200/80 dark:bg-zinc-950/80 backdrop-blur-sm border-b border-zinc-300 dark:border-zinc-800 px-6 py-3">
                        <div class="mx-auto flex items-center justify-between">
                            <div class="flex items-center gap-3">
                                <a href="<?= $adminUrl ?>/site/pages" class="text-xs text-blue-600 dark:text-blue-400 hover:underline flex items-center">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                                    <?= __('site.pages.title') ?>
                                </a>
                                <span class="text-zinc-300 dark:text-zinc-700">|</span>
                                <span id="widgetCount" class="text-xs text-zinc-500 dark:text-zinc-400"></span>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="<?= $baseUrl ?>/<?= $pageSlug === 'home' ? '' : htmlspecialchars($pageSlug) ?>" target="_blank" class="px-3 py-1.5 border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 rounded-lg hover:bg-white dark:hover:bg-zinc-800 transition text-xs font-medium flex items-center">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                                    <?= __('site.widget_builder.preview') ?>
                                </a>
                                <button id="btnSaveLayout" class="px-3 py-1.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition text-xs font-medium flex items-center">
                                    <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                                    <?= __('site.widget_builder.save_layout') ?>
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- 상태 메시지 -->
                    <div class="mx-auto px-6 pt-4">
                        <div id="statusMsg" class="hidden mb-3 p-2.5 rounded-lg text-xs"></div>
                    </div>

                    <!-- 캔버스 프레임 -->
                    <div class="mx-auto px-6 pb-8">
                        <div class="bg-white dark:bg-zinc-900 rounded-xl shadow-lg overflow-hidden border border-zinc-200 dark:border-zinc-800">
                            <!-- 브라우저 바 (시각적 효과) -->
                            <div class="bg-zinc-100 dark:bg-zinc-800 border-b border-zinc-200 dark:border-zinc-700 px-4 py-2 flex items-center gap-2">
                                <div class="flex gap-1.5">
                                    <div class="w-2.5 h-2.5 rounded-full bg-red-400"></div>
                                    <div class="w-2.5 h-2.5 rounded-full bg-yellow-400"></div>
                                    <div class="w-2.5 h-2.5 rounded-full bg-green-400"></div>
                                </div>
                                <div class="flex-1 bg-white dark:bg-zinc-700 rounded-md px-3 py-1 text-[10px] text-zinc-400 dark:text-zinc-500 text-center">
                                    <?= htmlspecialchars($baseUrl) ?>/
                                </div>
                            </div>
                            <!-- 위젯 렌더링 캔버스 -->
                            <div id="widgetCanvas" class="min-h-[400px]">
                                <!-- JS에서 동적 생성 -->
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 오른쪽: 편집 사이드 패널 (기본 숨김) -->
                <aside id="editSidePanel" class="w-80 bg-white dark:bg-zinc-800 border-l border-zinc-200 dark:border-zinc-700 flex-col overflow-hidden flex-shrink-0 hidden">
                    <!-- 헤더: 뒤로가기 + 위젯명 -->
                    <div class="px-4 py-3 border-b border-zinc-200 dark:border-zinc-700 flex items-center gap-2">
                        <button id="btnEditBack" class="p-1 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition" title="<?= __('admin.buttons.cancel') ?>">
                            <svg class="w-4 h-4 text-zinc-500 dark:text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <div class="min-w-0 flex-1">
                            <h3 id="editPanelTitle" class="text-sm font-semibold text-zinc-900 dark:text-white truncate"></h3>
                            <p class="text-[10px] text-zinc-400 dark:text-zinc-500"><?= __('site.widget_builder.edit_content') ?? '콘텐츠 편집' ?></p>
                        </div>
                    </div>
                    <!-- 언어 탭 -->
                    <div id="editLangTabs" class="px-3 py-2 border-b border-zinc-200 dark:border-zinc-700 flex gap-1 overflow-x-auto flex-shrink-0"></div>
                    <!-- 필드 영역 -->
                    <div id="editPanelFields" class="flex-1 overflow-y-auto px-4 py-4 space-y-4"></div>
                    <!-- 하단 버튼 -->
                    <div class="px-4 py-3 border-t border-zinc-200 dark:border-zinc-700 flex gap-2">
                        <button id="btnEditPanelCancel" class="flex-1 px-3 py-2 text-xs font-medium text-zinc-600 dark:text-zinc-300 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">
                            <?= __('admin.buttons.cancel') ?>
                        </button>
                        <button id="btnEditPanelSave" class="flex-1 px-3 py-2 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">
                            <?= __('admin.buttons.apply') ?>
                        </button>
                    </div>
                </aside>
            </div>
        </main>
    </div>

    <!-- 데이터 -->
    <script>
    var widgetIconMap = <?= json_encode($iconMap) ?>;
    // 위젯 이름/설명/카테고리 번역
    var widgetTranslations = <?php
        $wt = [];
        foreach ($availableWidgets as $aw) {
            $slug = $aw['slug'];
            $fileDef = $fileWidgets[$slug] ?? null;
            if ($fileDef) {
                // widget.json 다국어 데이터 우선
                $wt[$slug] = [
                    'name' => \RzxLib\Core\Modules\WidgetLoader::localizedValue($fileDef['name'] ?? $slug, $currentLocale),
                    'desc' => \RzxLib\Core\Modules\WidgetLoader::localizedValue($fileDef['description'] ?? '', $currentLocale)
                ];
            } else {
                // DB 위젯 (커스텀) → 기존 번역 파일 사용
                $wKey = 'admin.site.widget_builder.w.' . $slug;
                $dKey = 'admin.site.widget_builder.w.' . $slug . '_desc';
                $tr = __($wKey);
                $desc = __($dKey);
                $wt[$slug] = [
                    'name' => ($tr !== $wKey) ? $tr : $aw['name'],
                    'desc' => ($desc !== $dKey) ? $desc : ($aw['description'] ?? '')
                ];
            }
        }
        echo json_encode($wt, JSON_UNESCAPED_UNICODE);
    ?>;
    var categoryTranslations = <?php
        $ct = [];
        foreach (array_keys($widgetCategories) as $ck) {
            $cKey = 'admin.site.widget_builder.cat.' . $ck;
            $ctr = __($cKey);
            $ct[$ck] = ($ctr !== $cKey) ? $ctr : ucfirst($ck);
        }
        echo json_encode($ct, JSON_UNESCAPED_UNICODE);
    ?>;
    var translations = {
        empty: '<?= __('site.widget_builder.empty') ?>',
        remove_confirm: '<?= __('site.widget_builder.remove_confirm') ?>',
        saved: '<?= __('site.widget_builder.saved') ?>',
        error_save: '<?= __('site.widget_builder.error_save') ?>',
        remove_widget: '<?= __('site.widget_builder.remove_widget') ?>',
        save_layout: '<?= __('site.widget_builder.save_layout') ?>',
        widgets_count: '<?= __('site.widget_builder.widgets_count') ?? '위젯' ?>',
        no_config: '<?= __('site.widget_builder.no_config') ?? '설정 가능한 항목이 없습니다' ?>',
        config_updated: '<?= __('site.widget_builder.config_updated') ?? '설정이 적용되었습니다' ?>',
        loading: '<?= __('site.widget_builder.loading') ?? '미리보기 로딩 중...' ?>',
        edit_content: '<?= __('site.widget_builder.edit_content') ?? '콘텐츠 편집' ?>',
        i18n_fields: '<?= __('site.widget_builder.i18n_fields') ?? '다국어 텍스트' ?>',
        common_fields: '<?= __('site.widget_builder.common_fields') ?? '공통 설정' ?>',
        save_apply: '<?= __('admin.buttons.apply') ?>',
        multilang: '<?= __('site.widget_builder.multilang') ?? '다국어 입력' ?>',
        inline_editing: '<?= __('site.widget_builder.inline_editing') ?? '인라인 편집 중' ?>',
        save: '<?= __('admin.buttons.save') ?? '저장' ?>',
        cancel: '<?= __('admin.buttons.cancel') ?? '취소' ?>'
    };
    var supportedLangs = <?= json_encode(array_values($supportedLangs)) ?>;
    var langNames = <?= json_encode($langNames) ?>;
    var currentLocale = '<?= $currentLocale ?>';
    var placedWidgetsData = <?= json_encode(array_map(function($pw) use ($fileWidgets) {
        $slug = $pw['widget_slug'];
        // 파일 기반 위젯은 widget.json의 config_schema 우선 사용
        $schema = $pw['config_schema'] ?? '{}';
        if (isset($fileWidgets[$slug]) && !empty($fileWidgets[$slug]['config_schema'])) {
            $schema = json_encode($fileWidgets[$slug]['config_schema'], JSON_UNESCAPED_UNICODE);
        }
        return [
            'widget_id' => $pw['widget_id'],
            'slug' => $slug,
            'name' => $pw['widget_name'],
            'icon' => $pw['icon'],
            'config' => $pw['config'] ?? '{}',
            'config_schema' => $schema,
        ];
    }, $placedWidgets)) ?>;
    </script>
    <!-- Richtext 편집 모달 (JS보다 먼저 로드되어야 함) -->
    <style>
        #richtextModalInner { min-width: 400px; min-height: 300px; }
        #richtextModalInner.rt-maximized { width: 100vw !important; height: 100vh !important; max-width: 100vw !important; max-height: 100vh !important; margin: 0 !important; border-radius: 0 !important; top: 0 !important; left: 0 !important; transform: none !important; }
        #richtextModalHeader { cursor: grab; user-select: none; }
        #richtextModalHeader:active { cursor: grabbing; }
        #richtextModalResize { position: absolute; right: 0; bottom: 0; width: 16px; height: 16px; cursor: nwse-resize; z-index: 10; }
        #richtextModalResize::after { content: ''; position: absolute; right: 3px; bottom: 3px; width: 8px; height: 8px; border-right: 2px solid #a1a1aa; border-bottom: 2px solid #a1a1aa; }
    </style>
    <div id="richtextModal" class="hidden fixed inset-0 z-[100] flex items-center justify-center bg-black/50 backdrop-blur-sm">
        <div id="richtextModalInner" class="bg-white dark:bg-zinc-800 rounded-xl shadow-2xl flex flex-col border border-zinc-200 dark:border-zinc-700 relative" style="width:900px;height:600px;">
            <!-- 헤더: 드래그 이동 + 더블클릭 전체화면 -->
            <div id="richtextModalHeader" class="flex items-center justify-between px-5 py-3 border-b border-zinc-200 dark:border-zinc-700 flex-shrink-0">
                <h3 id="richtextModalTitle" class="text-sm font-semibold text-zinc-800 dark:text-zinc-200"></h3>
                <div class="flex items-center gap-1">
                    <button id="richtextModalMaximize" class="p-1.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition" title="최대화/복원">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8V4h4M20 8V4h-4M4 16v4h4M20 16v4h-4"/></svg>
                    </button>
                    <button id="richtextModalClose" class="p-1.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 transition" title="닫기">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
            </div>
            <!-- 에디터 영역 -->
            <div class="flex-1 overflow-y-auto p-5">
                <textarea id="richtextModalEditor"></textarea>
            </div>
            <!-- 하단 버튼 -->
            <div class="flex justify-end gap-2 px-5 py-3 border-t border-zinc-200 dark:border-zinc-700 flex-shrink-0">
                <button id="richtextModalCancel" class="px-4 py-2 text-xs text-zinc-600 dark:text-zinc-300 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition"><?= __('admin.buttons.cancel') ?? '취소' ?></button>
                <button id="richtextModalSave" class="px-4 py-2 text-xs bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition"><?= __('admin.buttons.apply') ?? '적용' ?></button>
            </div>
            <!-- 리사이즈 핸들 -->
            <div id="richtextModalResize"></div>
        </div>
    </div>

    <?php include __DIR__ . '/pages-widget-builder-js.php'; ?>
</body>
</html>
