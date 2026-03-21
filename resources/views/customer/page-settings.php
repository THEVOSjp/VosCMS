<?php
/**
 * 페이지 환경 설정 - 프론트 레이아웃 래퍼
 * 관리자만 접근 가능. pages-settings.php를 embed 모드로 include.
 */
if (empty($_SESSION['admin_id'])) {
    http_response_code(403);
    echo '<div class="max-w-5xl mx-auto px-4 py-16 text-center text-zinc-500">' . (__('common.no_permission') ?? '관리자 권한이 필요합니다.') . '</div>';
    return;
}

$_GET['slug'] = $pageSlug;
$_GET['embed'] = '1';
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 py-6">
    <div class="mb-4">
        <a href="<?= $baseUrl ?>/<?= htmlspecialchars($pageSlug) ?>" class="inline-flex items-center gap-1 text-sm text-zinc-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            <?= __('common.back') ?? '돌아가기' ?>
        </a>
    </div>
    <?php include BASE_PATH . '/resources/views/admin/site/pages-settings.php'; ?>
</div>
