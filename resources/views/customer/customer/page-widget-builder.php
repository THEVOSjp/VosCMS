<?php
/**
 * 위젯 빌더 - 프론트 레이아웃 래퍼
 * 관리자만 접근 가능. pages-widget-builder.php를 embed 모드로 include.
 */
if (empty($_SESSION['admin_id'])) {
    http_response_code(403);
    echo '<div class="max-w-5xl mx-auto px-4 py-16 text-center text-zinc-500">' . (__('common.no_permission') ?? '관리자 권한이 필요합니다.') . '</div>';
    return;
}

$_GET['slug'] = $pageSlug;
$_GET['embed'] = '1';
?>
<?php
// AJAX 요청은 직접 처리 (래퍼 HTML 출력 없이)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    $_GET['slug'] = $pageSlug;
    $_GET['embed'] = '1';
    include BASE_PATH . '/resources/views/admin/site/pages-widget-builder.php';
    return;
}

// 페이지 제목 가져오기
$_wbPrefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$_wbLocale = $currentLocale ?? 'ko';
$_wbTitleStmt = $pdo->prepare("SELECT title FROM {$_wbPrefix}page_contents WHERE page_slug = ? AND locale = ? LIMIT 1");
$_wbTitleStmt->execute([$pageSlug, $_wbLocale]);
$_wbPageTitle = $_wbTitleStmt->fetchColumn() ?: $pageSlug;
?>
<div class="w-full px-4 sm:px-6 py-6">
    <div class="flex items-center justify-between mb-4">
        <a href="<?= $baseUrl ?>/<?= htmlspecialchars($pageSlug) ?>" class="inline-flex items-center gap-1 text-sm text-zinc-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            <?= __('common.back') ?? '돌아가기' ?>
        </a>
        <h2 class="text-lg font-bold text-zinc-800 dark:text-zinc-200">
            <?= htmlspecialchars($_wbPageTitle) ?>
            <span class="text-sm font-normal text-zinc-400 ml-2">/<?= htmlspecialchars($pageSlug) ?> · <?= __('site.widget_builder.page_edit') ?? '페이지 편집' ?></span>
        </h2>
        <div></div>
    </div>
    <?php include BASE_PATH . '/resources/views/admin/site/pages-widget-builder.php'; ?>
</div>
