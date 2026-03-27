<?php
/**
 * RezlyX Admin - 게시판 관리
 */
$pageTitle = __('site.boards.title') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';

// Database connection
try {
    $pdo = new PDO(
        'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx'),
        $_ENV['DB_USERNAME'] ?? 'root',
        $_ENV['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('데이터베이스 연결 실패: ' . $e->getMessage());
}

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$message = '';
$messageType = '';
$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');

$boardsTableMissing = false;
$boards = [];
$totalCount = 0;
$totalPages = 1;
$search = trim($_GET['search'] ?? '');
$searchField = $_GET['search_field'] ?? 'title';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

try {
    // POST 처리 (삭제)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $action = $_POST['action'] ?? '';
        $boardId = (int)($_POST['board_id'] ?? 0);

        if ($action === 'delete' && $boardId > 0) {
            $stmt = $pdo->prepare("DELETE FROM {$prefix}boards WHERE id = ?");
            $stmt->execute([$boardId]);
            $message = __('site.boards.deleted');
            $messageType = 'success';
        }
    }

    // 쿼리 빌드
    $where = '';
    $params = [];
    if ($search !== '') {
        if ($searchField === 'slug') {
            $where = "WHERE slug LIKE ?";
            $params[] = "%{$search}%";
        } else {
            $where = "WHERE title LIKE ?";
            $params[] = "%{$search}%";
        }
    }

    // 총 개수
    $countSql = "SELECT COUNT(*) FROM {$prefix}boards {$where}";
    $countStmt = $pdo->prepare($countSql);
    $countStmt->execute($params);
    $totalCount = (int)$countStmt->fetchColumn();
    $totalPages = max(1, ceil($totalCount / $perPage));

    // 목록 조회
    $sql = "SELECT * FROM {$prefix}boards {$where} ORDER BY sort_order ASC, id DESC LIMIT {$perPage} OFFSET {$offset}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $boards = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 게시판 제목 다국어 적용
    $_bLocale = $config['locale'] ?? 'ko';
    $_bDefLocale = $siteSettings['default_language'] ?? 'ko';
    $_bChain = array_unique(array_filter([$_bLocale, 'en', $_bDefLocale]));
    $_bTr = [];
    try {
        $_bPH = implode(',', array_fill(0, count($_bChain), '?'));
        $_bStmt = $pdo->prepare("SELECT lang_key, locale, content FROM {$prefix}translations WHERE locale IN ({$_bPH}) AND lang_key LIKE 'board.%.title'");
        $_bStmt->execute(array_values($_bChain));
        while ($_bt = $_bStmt->fetch(PDO::FETCH_ASSOC)) { $_bTr[$_bt['lang_key']][$_bt['locale']] = $_bt['content']; }
    } catch (PDOException $e2) {}

    foreach ($boards as &$_bd) {
        $k = "board.{$_bd['id']}.title";
        if (isset($_bTr[$k])) {
            foreach ($_bChain as $lc) { if (!empty($_bTr[$k][$lc])) { $_bd['title'] = $_bTr[$k][$lc]; break; } }
        }
    }
    unset($_bd);
} catch (PDOException $e) {
    if (stripos($e->getMessage(), 'doesn\'t exist') !== false || stripos($e->getMessage(), 'not found') !== false) {
        $boardsTableMissing = true;
        $message = __('admin.dashboard.migration_required');
        $messageType = 'warning';
    } else {
        throw $e;
    }
}


$pageHeaderTitle = __('site.boards.title');
$pageSubTitle = __('site.boards.title');
$pageSubDesc = __('site.boards.description');
?>
<?php include __DIR__ . '/../reservations/_head.php'; ?>
                <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/20 text-green-800 dark:text-green-300 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/20 text-red-800 dark:text-red-300 border border-red-200 dark:border-red-800'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <!-- 총 개수 / 페이지 -->
                <div class="mb-4 flex items-center justify-between">
                    <p class="text-sm text-zinc-600 dark:text-zinc-400">
                        <?= __('site.boards.total') ?>: <span class="font-semibold"><?= $totalCount ?></span>,
                        <?= __('site.boards.page_info') ?>: <span class="font-semibold"><?= $page ?>/<?= $totalPages ?></span>
                    </p>
                </div>

                <!-- Board List Table -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead>
                                <tr class="bg-zinc-50 dark:bg-zinc-700/50 border-b border-zinc-200 dark:border-zinc-700">
                                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300 w-16"><?= __('site.boards.col_no') ?></th>
                                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300"><?= __('site.boards.col_url') ?></th>
                                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300"><?= __('site.boards.col_title') ?></th>
                                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300 w-32"><?= __('site.boards.col_note') ?></th>
                                    <th class="px-4 py-3 text-left font-medium text-zinc-600 dark:text-zinc-300 w-28"><?= __('site.boards.col_created') ?></th>
                                    <th class="px-4 py-3 text-center font-medium text-zinc-600 dark:text-zinc-300 w-48"><?= __('site.boards.col_actions') ?></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                <?php if (empty($boards)): ?>
                                <tr>
                                    <td colspan="6" class="px-4 py-12 text-center text-zinc-400 dark:text-zinc-500">
                                        <svg class="w-12 h-12 mx-auto mb-3 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 20H5a2 2 0 01-2-2V6a2 2 0 012-2h10a2 2 0 012 2v1m2 13a2 2 0 01-2-2V7m2 13a2 2 0 002-2V9a2 2 0 00-2-2h-2m-4-3H9M7 16h6M7 8h6v4H7V8z"/>
                                        </svg>
                                        <?= __('site.boards.empty') ?>
                                    </td>
                                </tr>
                                <?php else: ?>
                                <?php foreach ($boards as $board): ?>
                                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition">
                                    <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400 font-mono text-xs">#<?= $board['id'] ?></td>
                                    <td class="px-4 py-3">
                                        <code class="text-xs bg-zinc-100 dark:bg-zinc-700 px-2 py-1 rounded text-zinc-700 dark:text-zinc-300">/<?= htmlspecialchars($board['slug']) ?></code>
                                    </td>
                                    <td class="px-4 py-3 font-medium">
                                        <a href="<?= $baseUrl ?>/<?= htmlspecialchars($board['slug']) ?>" target="_blank" class="text-blue-600 dark:text-blue-400 hover:underline"><?= htmlspecialchars($board['title']) ?></a>
                                    </td>
                                    <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400 text-xs">
                                        <?php if (!$board['is_active']): ?>
                                        <span class="text-red-500"><?= __('site.boards.inactive') ?></span>
                                        <?php else: ?>
                                        <span class="text-green-600 dark:text-green-400"><?= __('site.boards.active') ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400 text-xs">
                                        <?= date('Y-m-d', strtotime($board['created_at'])) ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <a href="<?= $adminUrl ?>/site/boards/edit?id=<?= $board['id'] ?>"
                                               class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-zinc-600 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 rounded-lg transition"
                                               title="<?= __('site.boards.settings') ?>">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/>
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                                </svg>
                                                <?= __('site.boards.settings') ?>
                                            </a>
                                            <button type="button" onclick="copyBoard(<?= $board['id'] ?>)"
                                                    class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/40 rounded-lg transition"
                                                    title="<?= __('site.boards.copy') ?>">
                                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                                                </svg>
                                                <?= __('site.boards.copy') ?>
                                            </button>
                                            <form method="POST" class="inline" onsubmit="return confirm('<?= __('site.boards.delete_confirm') ?>')">
                                                <input type="hidden" name="action" value="delete">
                                                <input type="hidden" name="board_id" value="<?= $board['id'] ?>">
                                                <button type="submit"
                                                        class="inline-flex items-center gap-1 px-2.5 py-1.5 text-xs font-medium text-red-600 dark:text-red-400 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 dark:hover:bg-red-900/40 rounded-lg transition"
                                                        title="<?= __('site.boards.delete') ?>">
                                                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/>
                                                    </svg>
                                                    <?= __('site.boards.delete') ?>
                                                </button>
                                            </form>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>

                <!-- Pagination -->
                <?php if ($totalPages > 1): ?>
                <div class="mt-6 flex justify-center">
                    <nav class="flex items-center gap-1">
                        <?php if ($page > 1): ?>
                        <a href="?page=<?= $page - 1 ?><?= $search ? '&search=' . urlencode($search) . '&search_field=' . urlencode($searchField) : '' ?>"
                           class="px-3 py-2 text-sm text-zinc-600 dark:text-zinc-400 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700">
                            <?= __('admin.common.prev') ?>
                        </a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?page=<?= $i ?><?= $search ? '&search=' . urlencode($search) . '&search_field=' . urlencode($searchField) : '' ?>"
                           class="px-3 py-2 text-sm rounded-lg border <?= $i === $page ? 'bg-blue-600 text-white border-blue-600' : 'text-zinc-600 dark:text-zinc-400 bg-white dark:bg-zinc-800 border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700' ?>">
                            <?= $i ?>
                        </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                        <a href="?page=<?= $page + 1 ?><?= $search ? '&search=' . urlencode($search) . '&search_field=' . urlencode($searchField) : '' ?>"
                           class="px-3 py-2 text-sm text-zinc-600 dark:text-zinc-400 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700">
                            <?= __('admin.common.next') ?>
                        </a>
                        <?php endif; ?>
                    </nav>
                </div>
                <?php endif; ?>

                <!-- Search Bar -->
                <div class="mt-6 flex items-center justify-center gap-2">
                    <form method="GET" class="flex items-center gap-2">
                        <select name="search_field" class="px-3 py-2 text-sm bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg text-zinc-700 dark:text-zinc-300">
                            <option value="title" <?= $searchField === 'title' ? 'selected' : '' ?>><?= __('site.boards.col_title') ?></option>
                            <option value="slug" <?= $searchField === 'slug' ? 'selected' : '' ?>><?= __('site.boards.col_url') ?></option>
                        </select>
                        <input type="text" name="search" value="<?= htmlspecialchars($search) ?>"
                               placeholder="<?= __('site.boards.search_placeholder') ?>"
                               class="px-3 py-2 text-sm bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg text-zinc-700 dark:text-zinc-300 w-60 placeholder-zinc-400">
                        <button type="submit"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition">
                            <?= __('admin.buttons.search') ?>
                        </button>
                        <?php if ($search): ?>
                        <a href="<?= $adminUrl ?>/site/boards"
                           class="px-4 py-2 text-sm font-medium text-zinc-600 dark:text-zinc-300 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700 rounded-lg transition">
                            <?= __('admin.buttons.cancel') ?>
                        </a>
                        <?php endif; ?>
                    </form>
                </div>
            </div>
        </main>
    </div>

    <script>
    console.log('[Boards] 게시판 관리 페이지 로드됨');

    function copyBoard(boardId) {
        console.log('[Boards] copyBoard called, id:', boardId);
        if (!confirm('<?= __('site.boards.copy_confirm') ?>')) return;

        fetch('<?= $adminUrl ?>/site/boards/copy', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
                'X-Requested-With': 'XMLHttpRequest'
            },
            body: 'board_id=' + boardId
        })
        .then(r => r.json())
        .then(data => {
            console.log('[Boards] copy response:', data);
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || 'Error');
            }
        })
        .catch(err => {
            console.error('[Boards] copy error:', err);
            alert('Error: ' + err.message);
        });
    }
    </script>
</body>
</html>
