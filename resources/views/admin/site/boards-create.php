<?php
/**
 * RezlyX Admin - 게시판 생성
 * 공통 컴포넌트 사용: resources/views/admin/components/board/section-*.php
 */
$pageTitle = __('site.boards.create_title') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');

// 생성 모드: $board = null, $boardId = 0
$board = null;
$boardId = 0;
$_componentDir = __DIR__ . '/../components/board';
?>
<!DOCTYPE html>
<html lang="<?php echo $config['locale'] ?? 'ko'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <style>body { font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; }</style>
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
            $pageHeaderTitle = __('site.boards.create_title');
            include __DIR__ . '/../partials/admin-topbar.php';
            ?>
            <div class="p-6">
                <!-- Header -->
                <div class="mb-6">
                <?php
                $headerIcon = 'M12 4v16m8-8H4';
                $headerTitle = __('site.boards.create_title');
                $headerDescription = __('site.boards.create_desc');
                $headerIconColor = '';
                $headerActions = '';
                include __DIR__ . '/../components/settings-header.php';
                ?>
                </div>

                <!-- Form -->
                <form id="boardCreateForm" class="space-y-6">
                    <?php $_collapsed = false; include "{$_componentDir}/section-basic.php"; ?>
                    <?php $_collapsed = true;  include "{$_componentDir}/section-seo.php"; ?>
                    <?php $_collapsed = true;  include "{$_componentDir}/section-display.php"; ?>
                    <?php $_collapsed = true;  include "{$_componentDir}/section-list.php"; ?>
                    <?php $_collapsed = true;  include "{$_componentDir}/section-advanced.php"; ?>

                    <!-- 버튼 -->
                    <div class="flex items-center justify-end gap-3">
                        <a href="<?= $adminUrl ?>/site/boards"
                           class="px-6 py-2.5 text-sm font-medium text-zinc-600 dark:text-zinc-300 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">
                            <?= __('admin.buttons.cancel') ?>
                        </a>
                        <button type="submit"
                                class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition">
                            <?= __('site.boards.create_submit') ?>
                        </button>
                    </div>
                </form>
            </div>
        </main>
    </div>

    <?php include "{$_componentDir}/section-js.php"; ?>

    <script>
    console.log('[BoardCreate] 게시판 생성 페이지 로드됨');
    const adminUrl = '<?= $adminUrl ?>';

    document.getElementById('boardCreateForm').addEventListener('submit', async function(e) {
        e.preventDefault();
        console.log('[BoardCreate] submit');

        const form = new FormData(this);
        form.append('action', 'create');

        try {
            const resp = await fetch(adminUrl + '/site/boards/api', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest' },
                body: new URLSearchParams(form)
            });
            const data = await resp.json();
            console.log('[BoardCreate] response:', data);

            if (data.success) {
                alert(data.message);
                location.href = adminUrl + '/site/boards/edit?id=' + data.board_id;
            } else {
                alert(data.message || 'Error');
            }
        } catch (err) {
            console.error('[BoardCreate] error:', err);
            alert('Error: ' + err.message);
        }
    });
    </script>
</body>
</html>
