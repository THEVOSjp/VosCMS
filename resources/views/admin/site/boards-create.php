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


$pageHeaderTitle = __('site.boards.create');
$pageSubTitle = __('site.boards.create');
?>
<?php include __DIR__ . '/../reservations/_head.php'; ?>
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
