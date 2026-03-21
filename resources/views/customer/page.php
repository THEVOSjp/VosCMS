<?php
/**
 * RezlyX 동적 페이지 렌더러
 * - document: 문서 페이지 (HTML 콘텐츠)
 * - widget: 위젯 빌더 페이지
 * - external: 외부 페이지 (URL → iframe, 내부 파일 → include)
 *
 * $pageSlug — index.php에서 전달
 */

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$currentLocale = $config['locale'] ?? (function_exists('current_locale') ? current_locale() : 'ko');

// 페이지 데이터 로드 (현재 로케일 → en → 기본)
$pageData = null;
$localeChain = array_unique([$currentLocale, 'en', 'ko']);

foreach ($localeChain as $loc) {
    $stmt = $pdo->prepare("SELECT * FROM {$prefix}page_contents WHERE page_slug = ? AND locale = ? AND is_active = 1");
    $stmt->execute([$pageSlug, $loc]);
    $pageData = $stmt->fetch(PDO::FETCH_ASSOC);
    if ($pageData) break;
}

// 로케일 번역 없으면 아무 로케일이나
if (!$pageData) {
    $stmt = $pdo->prepare("SELECT * FROM {$prefix}page_contents WHERE page_slug = ? AND is_active = 1 LIMIT 1");
    $stmt->execute([$pageSlug]);
    $pageData = $stmt->fetch(PDO::FETCH_ASSOC);
}

if (!$pageData) {
    http_response_code(404);
    include BASE_PATH . '/resources/views/customer/404.php';
    return;
}

$pageType = $pageData['page_type'] ?? 'document';
$pageTitle = $pageData['title'] ?? $pageSlug;
$pageContent = $pageData['content'] ?? '';
$seoContext = [
    'type' => 'sub',
    'subpage_title' => $pageTitle,
    'content' => $pageType === 'document' ? $pageContent : '',
];

// 관리자 아이콘 (설정 + 편집)
$_adminIcons = '';
if (!empty($_SESSION['admin_id'])) {
    $adminPath = $config['admin_path'] ?? 'admin';
    $_settingsUrl = ($config['app_url'] ?? '') . '/' . urlencode($pageSlug) . '/settings';
    $_editUrl = ($config['app_url'] ?? '') . '/' . urlencode($pageSlug) . '/edit';
    // 기어 아이콘 (환경 설정)
    $_adminIcons .= '<a href="' . htmlspecialchars($_settingsUrl) . '" class="text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition" title="' . (__('common.page_settings') ?? '페이지 설정') . '"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg></a>';
    // 편집 아이콘 (콘텐츠 편집)
    $_adminIcons .= '<a href="' . htmlspecialchars($_editUrl) . '" class="text-zinc-400 hover:text-green-600 dark:hover:text-green-400 transition" title="' . (__('common.edit') ?? '편집') . '"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg></a>';
}
?>

<?php if ($pageType === 'external'): ?>
    <?php
    $isExternalUrl = preg_match('#^https?://#', $pageContent);
    $isPhpFile = str_ends_with(trim($pageContent), '.php');
    $isHtmlFile = str_ends_with(trim($pageContent), '.html') || str_ends_with(trim($pageContent), '.htm');
    ?>

    <?php if ($isExternalUrl): ?>
    <!-- 외부 URL: iframe으로 RezlyX 레이아웃 안에 표시 -->
    <div class="max-w-7xl mx-auto px-4 sm:px-6 py-4">
        <div class="flex items-center justify-between mb-3">
            <h1 class="text-xl font-bold text-zinc-800 dark:text-zinc-100 inline-flex items-center gap-2"><?= htmlspecialchars($pageTitle) ?> <?= $_adminIcons ?></h1>
            <a href="<?= htmlspecialchars($pageContent) ?>" target="_blank" class="text-sm text-blue-600 dark:text-blue-400 hover:underline flex items-center gap-1">
                <?= __('common.open_new_window') ?? '새 창에서 열기' ?>
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
            </a>
        </div>
        <div class="rounded-xl overflow-hidden border border-zinc-200 dark:border-zinc-700" style="height: calc(100vh - 200px);">
            <iframe src="<?= htmlspecialchars($pageContent) ?>"
                    class="w-full h-full border-0"
                    sandbox="allow-scripts allow-same-origin allow-forms allow-popups"
                    loading="lazy"
                    onerror="this.parentElement.innerHTML='<div class=\'p-8 text-center text-zinc-500\'><p>이 사이트는 외부 표시를 허용하지 않습니다.</p><a href=\'<?= htmlspecialchars($pageContent) ?>\' target=\'_blank\' class=\'mt-4 inline-block px-4 py-2 bg-blue-600 text-white rounded-lg\'>새 창에서 열기</a></div>'">
            </iframe>
        </div>
    </div>

    <?php elseif ($isPhpFile || $isHtmlFile): ?>
    <!-- 내부 파일: 직접 include -->
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-6">
        <?php
        $filePath = BASE_PATH . '/' . ltrim($pageContent, '/');
        if (file_exists($filePath)) {
            include $filePath;
        } else {
            echo '<div class="p-8 text-center text-zinc-500">페이지 파일을 찾을 수 없습니다: ' . htmlspecialchars($pageContent) . '</div>';
        }
        ?>
    </div>

    <?php else: ?>
    <!-- 외부 타입이지만 HTML 콘텐츠 -->
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-6">
        <h1 class="text-2xl font-bold text-zinc-800 dark:text-zinc-100 mb-4 inline-flex items-center gap-2"><?= htmlspecialchars($pageTitle) ?> <?= $_adminIcons ?></h1>
        <div class="board-content bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <?= $pageContent ?>
        </div>
    </div>
    <?php endif; ?>

<?php elseif ($pageType === 'widget'): ?>
    <!-- 위젯 페이지: WidgetRenderer로 렌더링 -->
    <?php
    require_once BASE_PATH . '/rzxlib/Core/Modules/WidgetRenderer.php';
    $widgets = $pdo->prepare("SELECT pw.*, w.widget_type, w.name as widget_name FROM {$prefix}page_widgets pw LEFT JOIN {$prefix}widgets w ON pw.widget_id = w.id WHERE pw.page_slug = ? AND pw.is_active = 1 ORDER BY pw.sort_order");
    $widgets->execute([$pageSlug]);
    $widgetList = $widgets->fetchAll(PDO::FETCH_ASSOC);

    if (!empty($widgetList)) {
        foreach ($widgetList as $w) {
            $widgetConfig = json_decode($w['config'] ?? '{}', true) ?: [];
            $widgetType = $w['widget_type'] ?? 'text';
            $renderFile = BASE_PATH . '/widgets/' . $widgetType . '/render.php';
            if (file_exists($renderFile)) {
                include $renderFile;
            }
        }
    } else {
        echo '<div class="max-w-5xl mx-auto px-4 py-16 text-center text-zinc-400">위젯이 아직 추가되지 않았습니다.</div>';
    }
    ?>

<?php else: ?>
    <!-- 문서 페이지: HTML 콘텐츠 렌더링 -->
    <div class="max-w-5xl mx-auto px-4 sm:px-6 py-6">
        <h1 class="text-2xl font-bold text-zinc-800 dark:text-zinc-100 mb-4 inline-flex items-center gap-2"><?= htmlspecialchars($pageTitle) ?> <?= $_adminIcons ?></h1>
        <?php if ($pageContent): ?>
        <div class="board-content bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <?= $pageContent ?>
        </div>
        <?php else: ?>
        <div class="p-16 text-center text-zinc-400 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700">
            <?= __('common.page_empty') ?? '페이지 내용이 아직 작성되지 않았습니다.' ?>
        </div>
        <?php endif; ?>
    </div>
<?php endif; ?>
