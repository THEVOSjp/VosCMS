<?php
/**
 * RezlyX Messages Inbox Page
 */

require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
use RzxLib\Core\Auth\Auth;

if (!Auth::check()) {
    header('Location: ' . ($config['app_url'] ?? '') . '/login');
    exit;
}

$user = Auth::user();
$pageTitle = ($config['app_name'] ?? 'RezlyX') . ' - ' . __('auth.mypage.menu.messages');
$baseUrl = $config['app_url'] ?? '';

// 헤더에서 사용할 변수
$isLoggedIn = true;
$currentUser = $user;

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 10;
$offset = ($page - 1) * $perPage;

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'mark_read' && isset($_POST['message_id'])) {
        $messageId = intval($_POST['message_id']);
        try {
            global $pdo;
            $stmt = $pdo->prepare("UPDATE rzx_user_notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND user_id = ?");
            $stmt->execute([$messageId, $user['id']]);
        } catch (PDOException $e) {
            // Ignore errors
        }
    } elseif ($action === 'mark_all_read') {
        try {
            global $pdo;
            $stmt = $pdo->prepare("UPDATE rzx_user_notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0");
            $stmt->execute([$user['id']]);
        } catch (PDOException $e) {
            // Ignore errors
        }
    } elseif ($action === 'delete' && isset($_POST['message_id'])) {
        $messageId = intval($_POST['message_id']);
        try {
            global $pdo;
            $stmt = $pdo->prepare("DELETE FROM rzx_user_notifications WHERE id = ? AND user_id = ?");
            $stmt->execute([$messageId, $user['id']]);
        } catch (PDOException $e) {
            // Ignore errors
        }
    }

    // Redirect to avoid form resubmission
    header('Location: ' . $baseUrl . '/mypage/messages');
    exit;
}

// Get messages
$messages = [];
$totalMessages = 0;
$unreadCount = 0;
$tableExists = false;

try {
    global $pdo;

    // Check if table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'rzx_user_notifications'");
    $tableExists = $tableCheck->rowCount() > 0;

    if ($tableExists) {
        // Get total count
        $countStmt = $pdo->prepare("SELECT COUNT(*) FROM rzx_user_notifications WHERE user_id = ?");
        $countStmt->execute([$user['id']]);
        $totalMessages = $countStmt->fetchColumn();

        // Get unread count
        $unreadStmt = $pdo->prepare("SELECT COUNT(*) FROM rzx_user_notifications WHERE user_id = ? AND is_read = 0");
        $unreadStmt->execute([$user['id']]);
        $unreadCount = $unreadStmt->fetchColumn();

        // Get messages
        $stmt = $pdo->prepare("SELECT * FROM rzx_user_notifications WHERE user_id = ? ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([$user['id'], $perPage, $offset]);
        $messages = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    error_log('Messages error: ' . $e->getMessage());
}

$totalPages = ceil($totalMessages / $perPage);

// 헤더 포함
include BASE_PATH . '/resources/views/partials/header.php';
?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="lg:flex lg:gap-8">
            <!-- 사이드바 -->
            <aside class="lg:w-64 mb-6 lg:mb-0">
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-6 sticky top-24">
                    <div class="text-center mb-6">
                        <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-3">
                            <span class="text-2xl font-bold text-white"><?php echo mb_substr($user['name'] ?? 'U', 0, 1); ?></span>
                        </div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($user['name'] ?? ''); ?></h2>
                        <p class="text-sm text-gray-500 dark:text-zinc-400"><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
                    </div>
                    <nav class="space-y-1">
                        <a href="<?php echo $baseUrl; ?>/mypage" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-600 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-700">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            <?php echo __('auth.mypage.menu.dashboard'); ?>
                        </a>
                        <a href="<?php echo $baseUrl; ?>/mypage/reservations" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-600 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-700">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <?php echo __('auth.mypage.menu.reservations'); ?>
                        </a>
                        <a href="<?php echo $baseUrl; ?>/mypage/profile" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-600 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-700">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <?php echo __('auth.mypage.menu.profile'); ?>
                        </a>
                        <a href="<?php echo $baseUrl; ?>/mypage/password" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-600 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-700">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <?php echo __('auth.mypage.menu.password'); ?>
                        </a>
                        <a href="<?php echo $baseUrl; ?>/mypage/messages" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 relative">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <?php echo __('auth.mypage.menu.messages'); ?>
                            <?php if ($unreadCount > 0): ?>
                            <span class="absolute right-3 top-1/2 -translate-y-1/2 px-2 py-0.5 text-xs font-bold bg-red-500 text-white rounded-full"><?php echo $unreadCount > 99 ? '99+' : $unreadCount; ?></span>
                            <?php endif; ?>
                        </a>
                    </nav>
                </div>
            </aside>

            <!-- 메인 콘텐츠 -->
            <div class="flex-1">
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg overflow-hidden">
                    <!-- 헤더 -->
                    <div class="p-6 border-b border-gray-200 dark:border-zinc-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?php echo __('auth.mypage.messages.title'); ?></h1>
                                <p class="text-gray-500 dark:text-zinc-400 mt-1">
                                    <?php echo __('auth.mypage.messages.total', ['count' => $totalMessages]); ?>
                                    <?php if ($unreadCount > 0): ?>
                                    <span class="text-blue-600 dark:text-blue-400">(<?php echo __('auth.mypage.messages.unread', ['count' => $unreadCount]); ?>)</span>
                                    <?php endif; ?>
                                </p>
                            </div>
                            <?php if ($unreadCount > 0): ?>
                            <form method="POST">
                                <input type="hidden" name="action" value="mark_all_read">
                                <button type="submit" class="px-4 py-2 text-sm bg-gray-100 dark:bg-zinc-700 text-gray-700 dark:text-zinc-300 rounded-lg hover:bg-gray-200 dark:hover:bg-zinc-600 transition">
                                    <?php echo __('auth.mypage.messages.mark_all_read'); ?>
                                </button>
                            </form>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!$tableExists): ?>
                    <!-- 테이블 없음 안내 -->
                    <div class="p-12 text-center">
                        <svg class="w-16 h-16 text-gray-300 dark:text-zinc-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-gray-500 dark:text-zinc-400"><?php echo __('auth.mypage.messages.not_available'); ?></p>
                    </div>
                    <?php elseif (empty($messages)): ?>
                    <!-- 메시지 없음 -->
                    <div class="p-12 text-center">
                        <svg class="w-16 h-16 text-gray-300 dark:text-zinc-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-gray-500 dark:text-zinc-400"><?php echo __('auth.mypage.messages.empty'); ?></p>
                    </div>
                    <?php else: ?>
                    <!-- 메시지 목록 -->
                    <div class="divide-y divide-gray-200 dark:divide-zinc-700">
                        <?php foreach ($messages as $msg): ?>
                        <div class="p-4 hover:bg-gray-50 dark:hover:bg-zinc-700/50 transition <?php echo !$msg['is_read'] ? 'bg-blue-50/50 dark:bg-blue-900/10' : ''; ?>">
                            <div class="flex items-start gap-4">
                                <!-- 아이콘 -->
                                <div class="flex-shrink-0 w-10 h-10 rounded-full flex items-center justify-center <?php echo !$msg['is_read'] ? 'bg-blue-100 dark:bg-blue-900/30' : 'bg-gray-100 dark:bg-zinc-700'; ?>">
                                    <?php
                                    $iconClass = !$msg['is_read'] ? 'text-blue-600 dark:text-blue-400' : 'text-gray-500 dark:text-zinc-400';
                                    $iconPath = match($msg['type'] ?? 'push') {
                                        'system' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
                                        'reservation' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
                                        'promotion' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7"/>',
                                        default => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>'
                                    };
                                    ?>
                                    <svg class="w-5 h-5 <?php echo $iconClass; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><?php echo $iconPath; ?></svg>
                                </div>

                                <!-- 내용 -->
                                <div class="flex-1 min-w-0">
                                    <div class="flex items-center gap-2 mb-1">
                                        <?php if (!$msg['is_read']): ?>
                                        <span class="w-2 h-2 bg-blue-500 rounded-full"></span>
                                        <?php endif; ?>
                                        <h3 class="font-semibold text-gray-900 dark:text-white truncate"><?php echo htmlspecialchars($msg['title']); ?></h3>
                                    </div>
                                    <p class="text-sm text-gray-600 dark:text-zinc-400 line-clamp-2"><?php echo htmlspecialchars($msg['body']); ?></p>
                                    <div class="flex items-center gap-4 mt-2">
                                        <span class="text-xs text-gray-400 dark:text-zinc-500">
                                            <?php echo date('Y.m.d H:i', strtotime($msg['created_at'])); ?>
                                        </span>
                                        <?php if (!empty($msg['url'])): ?>
                                        <a href="<?php echo htmlspecialchars($msg['url']); ?>" class="text-xs text-blue-600 dark:text-blue-400 hover:underline">
                                            <?php echo __('auth.mypage.messages.view_detail'); ?> &rarr;
                                        </a>
                                        <?php endif; ?>
                                    </div>
                                </div>

                                <!-- 액션 버튼 -->
                                <div class="flex-shrink-0 flex items-center gap-2">
                                    <?php if (!$msg['is_read']): ?>
                                    <form method="POST" class="inline">
                                        <input type="hidden" name="action" value="mark_read">
                                        <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                        <button type="submit" class="p-2 text-gray-400 hover:text-blue-600 dark:hover:text-blue-400 transition" title="<?php echo __('auth.mypage.messages.mark_read'); ?>">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                                            </svg>
                                        </button>
                                    </form>
                                    <?php endif; ?>
                                    <form method="POST" class="inline" onsubmit="return confirm('<?php echo __('auth.mypage.messages.delete_confirm'); ?>')">
                                        <input type="hidden" name="action" value="delete">
                                        <input type="hidden" name="message_id" value="<?php echo $msg['id']; ?>">
                                        <button type="submit" class="p-2 text-gray-400 hover:text-red-600 dark:hover:text-red-400 transition" title="<?php echo __('auth.mypage.messages.delete'); ?>">
                                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                            </svg>
                                        </button>
                                    </form>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- 페이지네이션 -->
                    <?php if ($totalPages > 1): ?>
                    <div class="px-6 py-4 border-t border-gray-200 dark:border-zinc-700">
                        <div class="flex items-center justify-between">
                            <p class="text-sm text-gray-500 dark:text-zinc-400">
                                <?php echo $offset + 1; ?> - <?php echo min($offset + $perPage, $totalMessages); ?> / <?php echo $totalMessages; ?>
                            </p>
                            <div class="flex gap-2">
                                <?php if ($page > 1): ?>
                                <a href="?page=<?php echo $page - 1; ?>" class="px-3 py-1 text-sm bg-gray-100 dark:bg-zinc-700 text-gray-700 dark:text-zinc-300 rounded hover:bg-gray-200 dark:hover:bg-zinc-600 transition">
                                    <?php echo __('common.prev'); ?>
                                </a>
                                <?php endif; ?>
                                <?php if ($page < $totalPages): ?>
                                <a href="?page=<?php echo $page + 1; ?>" class="px-3 py-1 text-sm bg-gray-100 dark:bg-zinc-700 text-gray-700 dark:text-zinc-300 rounded hover:bg-gray-200 dark:hover:bg-zinc-600 transition">
                                    <?php echo __('common.next'); ?>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

<?php
// 푸터 포함
include BASE_PATH . '/resources/views/partials/footer.php';
?>
