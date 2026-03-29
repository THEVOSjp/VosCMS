<?php
/**
 * RezlyX Admin - 게시판 설정: 기본 설정 탭
 * boards-edit.php에서 include됨 ($board, $pdo, $prefix, $adminUrl, $boardId 사용 가능)
 *
 * 공통 컴포넌트 사용: resources/views/admin/components/board/section-*.php
 */
$_componentDir = dirname(__DIR__) . '/components/board';
?>
<form id="boardEditForm" class="space-y-6">
    <input type="hidden" name="board_id" value="<?= $boardId ?>">
    <input type="hidden" name="action" value="update">

    <?php $_collapsed = false; include "{$_componentDir}/section-basic.php"; ?>
    <?php $_collapsed = false; include "{$_componentDir}/section-seo.php"; ?>
    <?php $_collapsed = false; include "{$_componentDir}/section-layout-select.php"; ?>
    <?php $_collapsed = false; include "{$_componentDir}/section-skin-select.php"; ?>
    <?php $_collapsed = false; include "{$_componentDir}/section-display.php"; ?>
    <?php $_collapsed = true;  include "{$_componentDir}/section-list.php"; ?>
    <?php $_collapsed = true;  include "{$_componentDir}/section-advanced.php"; ?>

    <!-- 버튼 -->
    <div class="flex items-center justify-between">
        <a href="<?= $adminUrl ?>/site/boards"
           class="px-6 py-2.5 text-sm font-medium text-zinc-600 dark:text-zinc-300 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">
            <?= __('admin.buttons.cancel') ?>
        </a>
        <div class="flex gap-3">
            <span id="saveStatus" class="text-sm text-green-600 dark:text-green-400 hidden self-center"></span>
            <button type="submit"
                    class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition">
                <?= __('admin.buttons.save') ?>
            </button>
        </div>
    </div>
</form>

<?php include "{$_componentDir}/section-js.php"; ?>

<script>
console.log('[BoardEdit] 게시판 설정 JS 로드됨, boardId=<?= $boardId ?>');
</script>

<?php include __DIR__ . '/boards-edit-js.php'; ?>
