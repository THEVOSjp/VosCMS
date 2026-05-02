<?php
/**
 * RezlyX Admin - 게시판 휴지통 관리
 */
$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');

$boardId = (int)($_GET['id'] ?? 0);
if (!$boardId) { header('Location: ' . $adminUrl . '/site/boards'); exit; }

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

$boardStmt = $pdo->prepare("SELECT * FROM {$prefix}boards WHERE id = ?");
$boardStmt->execute([$boardId]);
$board = $boardStmt->fetch(PDO::FETCH_ASSOC);
if (!$board) { header('Location: ' . $adminUrl . '/site/boards'); exit; }

// POST 처리 (복원/영구삭제)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'] ?? '';
    $postIds = $_POST['post_ids'] ?? [];
    if (is_string($postIds)) $postIds = explode(',', $postIds);
    $postIds = array_filter(array_map('intval', $postIds));

    if (empty($postIds)) {
        echo json_encode(['success' => false, 'message' => __('site.boards.trash_no_selected')]);
        exit;
    }

    $placeholders = implode(',', array_fill(0, count($postIds), '?'));

    if ($action === 'restore') {
        $pdo->prepare("UPDATE {$prefix}board_posts SET status = 'published' WHERE id IN ({$placeholders}) AND board_id = ? AND status = 'trash'")
            ->execute([...$postIds, $boardId]);
        echo json_encode(['success' => true, 'message' => __('site.boards.trash_restored')]);
        exit;
    }

    if ($action === 'permanent_delete') {
        // 댓글, 파일, 글 영구 삭제
        $pdo->prepare("DELETE FROM {$prefix}board_comments WHERE post_id IN ({$placeholders})")->execute($postIds);
        // 물리 파일 삭제
        $fileStmt = $pdo->prepare("SELECT file_path FROM {$prefix}board_files WHERE post_id IN ({$placeholders})");
        $fileStmt->execute($postIds);
        while ($f = $fileStmt->fetch(PDO::FETCH_ASSOC)) {
            $fullPath = BASE_PATH . $f['file_path'];
            if (file_exists($fullPath)) @unlink($fullPath);
        }
        $pdo->prepare("DELETE FROM {$prefix}board_files WHERE post_id IN ({$placeholders})")->execute($postIds);
        $pdo->prepare("DELETE FROM {$prefix}board_votes WHERE post_id IN ({$placeholders})")->execute($postIds);
        // 다국어 번역 row 도 함께 정리
        foreach ($postIds as $_pid) {
            board_post_text_delete_all($pdo, $prefix, (int)$_pid);
        }
        $pdo->prepare("DELETE FROM {$prefix}board_posts WHERE id IN ({$placeholders}) AND board_id = ? AND status = 'trash'")
            ->execute([...$postIds, $boardId]);
        echo json_encode(['success' => true, 'message' => __('site.boards.trash_deleted')]);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

// 목록 조회
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$cntStmt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}board_posts WHERE board_id = ? AND status = 'trash'");
$cntStmt->execute([$boardId]);
$totalCount = (int)$cntStmt->fetchColumn();
$totalPages = max(1, ceil($totalCount / $perPage));

$listStmt = $pdo->prepare("SELECT * FROM {$prefix}board_posts WHERE board_id = ? AND status = 'trash' ORDER BY updated_at DESC LIMIT " . (int)$perPage . " OFFSET " . (int)$offset);
$listStmt->execute([$boardId]);
$posts = $listStmt->fetchAll(PDO::FETCH_ASSOC);

// 다국어 title 적용 (rzx_translations 단일 저장소)
if (!empty($posts) && function_exists('board_post_text_bulk_load')) {
    $_locale = function_exists('current_locale') ? current_locale() : 'ko';
    $_trMap = board_post_text_bulk_load($pdo, $prefix, array_column($posts, 'id'), $_locale, ['title']);
    foreach ($posts as &$_p) {
        if (isset($_trMap[(int)$_p['id']]['title'])) $_p['title'] = $_trMap[(int)$_p['id']]['title'];
    }
    unset($_p);
}

$pageTitle = __('site.boards.trash') . ' - ' . htmlspecialchars($board['title']);


$pageHeaderTitle = __('site.boards.trash');
$pageSubTitle = __('site.boards.trash');
?>
<?php include __DIR__ . '/../reservations/_head.php'; ?>
                <!-- 헤더 -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h2 class="text-xl font-bold text-zinc-800 dark:text-zinc-100"><?= __('site.boards.trash') ?></h2>
                        <p class="text-sm text-zinc-500 mt-1"><?= htmlspecialchars($board['title']) ?> — <?= __('site.boards.trash_count', ['count' => $totalCount]) ?></p>
                    </div>
                    <div class="flex items-center gap-2">
                        <button type="button" id="btnRestoreSelected" class="px-4 py-2 text-sm font-medium text-blue-600 bg-blue-50 dark:bg-blue-900/20 dark:text-blue-400 border border-blue-200 dark:border-blue-800 rounded-lg hover:bg-blue-100 dark:hover:bg-blue-900/40 transition"><?= __('site.boards.trash_restore') ?></button>
                        <button type="button" id="btnDeleteSelected" class="px-4 py-2 text-sm font-medium text-red-600 bg-red-50 dark:bg-red-900/20 dark:text-red-400 border border-red-200 dark:border-red-800 rounded-lg hover:bg-red-100 dark:hover:bg-red-900/40 transition"><?= __('site.boards.trash_permanent_delete') ?></button>
                        <a href="<?= $adminUrl ?>/site/boards/edit?id=<?= $boardId ?>" class="px-4 py-2 text-sm font-medium text-zinc-600 dark:text-zinc-300 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition"><?= __('site.boards.back_to_settings') ?></a>
                    </div>
                </div>

                <!-- 테이블 -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <table class="w-full text-sm">
                        <thead>
                            <tr class="bg-zinc-50 dark:bg-zinc-700/50 border-b border-zinc-200 dark:border-zinc-700">
                                <th class="w-10 py-3 px-4"><input type="checkbox" id="checkAll" class="w-4 h-4 rounded border-zinc-300"></th>
                                <th class="w-16 py-3 px-4 text-center text-zinc-600 dark:text-zinc-400">ID</th>
                                <th class="py-3 px-4 text-left text-zinc-600 dark:text-zinc-400"><?= __('board.col_title') ?></th>
                                <th class="w-28 py-3 px-4 text-center text-zinc-600 dark:text-zinc-400"><?= __('board.col_author') ?></th>
                                <th class="w-36 py-3 px-4 text-center text-zinc-600 dark:text-zinc-400"><?= __('site.boards.trash_deleted_at') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($posts)): ?>
                            <tr><td colspan="5" class="py-12 text-center text-zinc-400"><?= __('site.boards.trash_empty') ?></td></tr>
                            <?php else: foreach ($posts as $p): ?>
                            <tr class="border-b border-zinc-100 dark:border-zinc-700/50 hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition">
                                <td class="py-3 px-4"><input type="checkbox" class="post-check w-4 h-4 rounded border-zinc-300" value="<?= $p['id'] ?>"></td>
                                <td class="py-3 px-4 text-center text-zinc-500"><?= $p['id'] ?></td>
                                <td class="py-3 px-4 text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($p['title']) ?></td>
                                <td class="py-3 px-4 text-center text-zinc-500"><?= htmlspecialchars($p['nick_name']) ?></td>
                                <td class="py-3 px-4 text-center text-zinc-500"><?= date('Y.m.d H:i', strtotime($p['updated_at'])) ?></td>
                            </tr>
                            <?php endforeach; endif; ?>
                        </tbody>
                    </table>
                </div>

                <!-- 페이지네이션 -->
                <?php if ($totalPages > 1): ?>
                <div class="flex justify-center mt-4 gap-1">
                    <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                    <a href="<?= $adminUrl ?>/site/boards/trash?id=<?= $boardId ?>&page=<?= $p ?>" class="px-3 py-1.5 text-sm rounded-lg <?= $p === $page ? 'bg-blue-600 text-white' : 'bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300' ?>"><?= $p ?></a>
                    <?php endfor; ?>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

<script>
console.log('[BoardTrash] 휴지통 페이지 로드, boardId=<?= $boardId ?>');
const trashApiUrl = '<?= $adminUrl ?>/site/boards/trash?id=<?= $boardId ?>';

// 전체 선택
document.getElementById('checkAll')?.addEventListener('change', function() {
    document.querySelectorAll('.post-check').forEach(cb => cb.checked = this.checked);
});

function getSelectedIds() {
    return Array.from(document.querySelectorAll('.post-check:checked')).map(cb => cb.value);
}

// 복원
document.getElementById('btnRestoreSelected')?.addEventListener('click', async () => {
    const ids = getSelectedIds();
    if (!ids.length) { alert('<?= __('site.boards.trash_no_selected') ?>'); return; }
    if (!confirm('<?= __('site.boards.trash_restore_confirm') ?>')) return;
    console.log('[BoardTrash] 복원:', ids);
    try {
        const fd = new URLSearchParams();
        fd.set('action', 'restore');
        fd.set('post_ids', ids.join(','));
        const resp = await fetch(trashApiUrl, {
            method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd
        });
        const data = await resp.json();
        if (data.success) location.reload();
        else alert(data.message);
    } catch (err) { console.error(err); alert('Error: ' + err.message); }
});

// 영구 삭제
document.getElementById('btnDeleteSelected')?.addEventListener('click', async () => {
    const ids = getSelectedIds();
    if (!ids.length) { alert('<?= __('site.boards.trash_no_selected') ?>'); return; }
    if (!confirm('<?= __('site.boards.trash_delete_confirm') ?>')) return;
    console.log('[BoardTrash] 영구삭제:', ids);
    try {
        const fd = new URLSearchParams();
        fd.set('action', 'permanent_delete');
        fd.set('post_ids', ids.join(','));
        const resp = await fetch(trashApiUrl, {
            method: 'POST', headers: { 'X-Requested-With': 'XMLHttpRequest' }, body: fd
        });
        const data = await resp.json();
        if (data.success) location.reload();
        else alert(data.message);
    } catch (err) { console.error(err); alert('Error: ' + err.message); }
});
</script>
</body>
</html>
