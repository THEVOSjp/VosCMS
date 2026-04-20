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

// POST+JSON 요청: 위젯 render.php AJAX 처리 (HTML 출력 전 실행)
if (($_SERVER['REQUEST_METHOD'] ?? '') === 'POST'
    && stripos($_SERVER['CONTENT_TYPE'] ?? '', 'application/json') !== false
    && in_array($pageType, ['widget', 'system'])) {
    require_once BASE_PATH . '/rzxlib/Core/Modules/WidgetLoader.php';
    $locale = $currentLocale ?? 'ko';
    $_ajaxWidgets = $pdo->prepare("SELECT pw.*, w.slug as widget_type FROM {$prefix}page_widgets pw LEFT JOIN {$prefix}widgets w ON pw.widget_id = w.id WHERE pw.page_slug = ? AND pw.is_active = 1 ORDER BY pw.sort_order");
    $_ajaxWidgets->execute([$pageSlug]);
    while ($_aw = $_ajaxWidgets->fetch(PDO::FETCH_ASSOC)) {
        $_awType = $_aw['widget_type'] ?? ($_aw['widget_slug'] ?? '');
        $_awFile = BASE_PATH . '/widgets/' . $_awType . '/render.php';
        if (file_exists($_awFile)) {
            $config = json_decode($_aw['config'] ?? '{}', true) ?: [];
            include $_awFile; // render.php 내부에서 POST 처리 후 exit
        }
    }
}
$seoContext = [
    'type' => 'sub',
    'subpage_title' => $pageTitle,
    'content' => $pageType === 'document' ? $pageContent : '',
];

// 페이지 설정 & 스킨 설정 로드
$_pgCfgKey = 'page_config_' . $pageSlug;
$_pgCfgStmt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = ?");
$_pgCfgStmt->execute([$_pgCfgKey]);
$_pgCfg = json_decode($_pgCfgStmt->fetchColumn() ?: '{}', true) ?: [];
// 스킨 우선순위: 개별 페이지 설정 > 전체 설정 (site_page_skin) > default
$_pageSkinName = !empty($_pgCfg['skin']) ? $_pgCfg['skin'] : ($__sitePageSkin ?? ($siteSettings['site_page_skin'] ?? 'default'));
$_skinCfg = $_pgCfg['skin_config'] ?? [];
// 전체 스킨 설정 폴백 (개별 skin_config가 비어있으면 전체 스킨 설정 사용)
if (empty($_skinCfg)) {
    $_globalSkinKey = 'skin_detail_page_' . $_pageSkinName;
    if (isset($siteSettings[$_globalSkinKey])) {
        $_skinCfg = json_decode($siteSettings[$_globalSkinKey], true) ?: [];
    }
}
$_showTitle = ($_skinCfg['show_title'] ?? '1') !== '0';
$_contentWidth = $_skinCfg['content_width'] ?? 'max-w-7xl';
// === STRONG DEBUG FOR BOOKING PAGE ===
if ($pageSlug === 'booking') error_log("BOOKING_DEBUG: pageType=$pageType, _showTitle=" . ($_showTitle ? 'true' : 'false') . ", _hasTitleBg=" . ($_hasTitleBg ?? 'UNDEF') . ", _adminIcons empty=" . (empty($_adminIcons) ? 'YES' : 'NO'));
$_contentBg = $_skinCfg['content_bg'] ?? 'transparent';
$_showBreadcrumb = ($_skinCfg['show_breadcrumb'] ?? '0') !== '0';
$_primaryColor = $_skinCfg['primary_color'] ?? '';
$_customCss = $_skinCfg['custom_css'] ?? '';
$_customHeader = $_skinCfg['custom_header_html'] ?? '';
$_customFooter = $_skinCfg['custom_footer_html'] ?? '';
$_bgClass = $_contentBg === 'white' ? 'bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6' : ($_contentBg === 'none' ? 'bg-transparent' : '');
// 제목 배경 설정
$_titleBgType = $_skinCfg['title_bg_type'] ?? 'none';
$_titleBgImage = $_skinCfg['title_bg_image'] ?? '';
$_titleBgVideo = $_skinCfg['title_bg_video'] ?? '';
$_titleBgHeight = (int)($_skinCfg['title_bg_height'] ?? 200);
$_titleBgOverlay = (int)($_skinCfg['title_bg_overlay'] ?? 40);
$_titleTextColor = $_skinCfg['title_text_color'] ?? 'auto';
$_hasTitleBg = $_showTitle && $_titleBgType !== 'none' && (($_titleBgType === 'image' && $_titleBgImage) || ($_titleBgType === 'video' && $_titleBgVideo));
$_titleTextClass = 'text-zinc-800 dark:text-zinc-100';
if ($_hasTitleBg) {
    $_titleTextClass = $_titleTextColor === 'dark' ? 'text-zinc-900' : 'text-white';
} elseif ($_titleTextColor === 'white') {
    $_titleTextClass = 'text-white';
} elseif ($_titleTextColor === 'dark') {
    $_titleTextClass = 'text-zinc-900';
}

// 홈페이지 여부 판단
$_homeSlug = $siteSettings['home_page'] ?? 'index';
$_isHomePage = ($pageSlug === $_homeSlug);

// 관리자 아이콘 — 모든 페이지 공통 (헤더 아래 고정 바)
$_editUrl = ($config['app_url'] ?? '') . '/' . urlencode($pageSlug) . '/edit';
$_settingsUrl = ($pageType !== 'widget' && $pageType !== 'system')
    ? ($config['app_url'] ?? '') . '/' . urlencode($pageSlug) . '/settings' : '';
$_adminIcons = ''; // 제목 옆 아이콘은 사용하지 않음
?>

<?php // 관리자 전용: 헤더 아래 가운데 편집/설정 고정 바
if (!empty($_SESSION['admin_id'])): ?>
<div id="rzxAdminBar" class="fixed left-1/2 -translate-x-1/2 z-[9999]" style="top:0">
    <div class="flex items-center gap-2">
        <a href="<?= htmlspecialchars($_editUrl) ?>" class="inline-flex items-center gap-1 px-3 py-1 bg-red-500 hover:bg-red-600 text-white text-[11px] font-medium rounded-full transition">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            <?= __('common.edit') ?? '편집' ?>
        </a>
        <?php if ($_settingsUrl): ?>
        <a href="<?= htmlspecialchars($_settingsUrl) ?>" class="inline-flex items-center gap-1 px-3 py-1 bg-zinc-600 hover:bg-zinc-500 text-white text-[11px] font-medium rounded-full transition">
            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            <?= __('common.settings') ?? '설정' ?>
        </a>
        <?php endif; ?>
    </div>
</div>
<script>
(function(){
    var bar = document.getElementById('rzxAdminBar');
    if (!bar) return;
    var header = document.querySelector('header');
    function pos() {
        var top = header ? header.getBoundingClientRect().bottom : 0;
        if (top < 0) top = 0;
        bar.style.top = Math.round(top + 4) + 'px';
    }
    pos();
    window.addEventListener('scroll', pos, {passive:true});
    window.addEventListener('resize', pos);
})();
</script>
<?php endif; ?>

<?php
// 공통 스킨 스타일 출력 (모든 페이지 타입)
if ($_customCss) echo '<style>' . $_customCss . '</style>';
if ($_primaryColor) echo '<style>:root { --page-primary: ' . htmlspecialchars($_primaryColor) . '; }</style>';
?>


<?php if ($pageType === 'external'): ?>
    <?php include __DIR__ . '/_page-partials/type-external.php'; ?>
<?php elseif ($pageType === 'widget' || $pageType === 'system'): ?>
    <?php include __DIR__ . '/_page-partials/type-widget.php'; ?>
<?php else: ?>
    <?php include __DIR__ . '/_page-partials/type-document.php'; ?>
<?php endif; ?>
