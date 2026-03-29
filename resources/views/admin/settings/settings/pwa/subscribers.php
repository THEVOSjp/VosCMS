<?php
/**
 * RezlyX Admin Settings - Push Subscribers & Messages
 * Manage push notification subscribers and send messages
 */

// Initialize database and settings
require_once dirname(__DIR__) . '/_init.php';

$pageTitle = __('settings.pwa.tabs.subscribers') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$currentSettingsPage = 'pwa';
$currentPwaTab = 'subscribers';

// Pagination
$page = max(1, intval($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

// Get total subscribers count
$totalSubscribers = 0;
$subscribers = [];
$messages = [];

try {
    // Check if table exists
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'rzx_push_subscribers'");
    $tableExists = $tableCheck->rowCount() > 0;

    if ($tableExists) {
        // Get total count
        $countStmt = $pdo->query("SELECT COUNT(*) FROM rzx_push_subscribers");
        $totalSubscribers = $countStmt->fetchColumn();

        // Get subscribers
        $stmt = $pdo->prepare("SELECT * FROM rzx_push_subscribers ORDER BY created_at DESC LIMIT ? OFFSET ?");
        $stmt->execute([$perPage, $offset]);
        $subscribers = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // Get recent messages
        $msgCheck = $pdo->query("SHOW TABLES LIKE 'rzx_push_messages'");
        if ($msgCheck->rowCount() > 0) {
            $msgStmt = $pdo->query("SELECT * FROM rzx_push_messages ORDER BY created_at DESC LIMIT 10");
            $messages = $msgStmt->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (PDOException $e) {
    error_log('Push subscribers error: ' . $e->getMessage());
}

$totalPages = ceil($totalSubscribers / $perPage);

// Handle actions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'send_notification') {
        $notifTitle = trim($_POST['notif_title'] ?? '');
        $notifBody = trim($_POST['notif_body'] ?? '');
        $notifUrl = trim($_POST['notif_url'] ?? '');
        $notifTarget = $_POST['notif_target'] ?? 'all';
        $saveToInbox = isset($_POST['save_to_inbox']) ? 1 : 0;

        if (empty($notifTitle) || empty($notifBody)) {
            $message = __('settings.pwa.subscribers.error_empty_fields');
            $messageType = 'error';
        } else {
            try {
                $messageId = null;

                // Save message to database
                $msgCheck = $pdo->query("SHOW TABLES LIKE 'rzx_push_messages'");
                if ($msgCheck->rowCount() > 0) {
                    $stmt = $pdo->prepare("INSERT INTO rzx_push_messages (title, body, url, target, save_to_inbox, sent_count, created_at) VALUES (?, ?, ?, ?, ?, 0, NOW())");
                    $stmt->execute([$notifTitle, $notifBody, $notifUrl, $notifTarget, $saveToInbox]);
                    $messageId = $pdo->lastInsertId();
                }

                // Save to user inbox if enabled
                if ($saveToInbox && $messageId) {
                    $inboxCheck = $pdo->query("SHOW TABLES LIKE 'rzx_user_notifications'");
                    if ($inboxCheck->rowCount() > 0) {
                        // Get target users based on target type
                        $userIds = [];
                        $usersCheck = $pdo->query("SHOW TABLES LIKE 'rzx_users'");

                        if ($usersCheck->rowCount() > 0) {
                            if ($notifTarget === 'all') {
                                $userStmt = $pdo->query("SELECT id FROM rzx_users WHERE status = 'active'");
                                $userIds = $userStmt->fetchAll(PDO::FETCH_COLUMN);
                            } elseif ($notifTarget === 'customers') {
                                $userStmt = $pdo->query("SELECT id FROM rzx_users WHERE role = 'customer' AND status = 'active'");
                                $userIds = $userStmt->fetchAll(PDO::FETCH_COLUMN);
                            } elseif ($notifTarget === 'admins') {
                                $userStmt = $pdo->query("SELECT id FROM rzx_users WHERE role IN ('admin', 'super_admin') AND status = 'active'");
                                $userIds = $userStmt->fetchAll(PDO::FETCH_COLUMN);
                            }
                        }

                        // Insert notification for each user
                        if (!empty($userIds)) {
                            $inboxStmt = $pdo->prepare("INSERT INTO rzx_user_notifications (user_id, message_id, title, body, url, type, created_at) VALUES (?, ?, ?, ?, ?, 'push', NOW())");
                            foreach ($userIds as $userId) {
                                $inboxStmt->execute([$userId, $messageId, $notifTitle, $notifBody, $notifUrl]);
                            }
                        }

                        // Also save as a "broadcast" notification (user_id = NULL for guest view)
                        $broadcastStmt = $pdo->prepare("INSERT INTO rzx_user_notifications (user_id, message_id, title, body, url, type, created_at) VALUES (NULL, ?, ?, ?, ?, 'push', NOW())");
                        $broadcastStmt->execute([$messageId, $notifTitle, $notifBody, $notifUrl]);
                    }
                }

                // TODO: Actual push notification sending logic using web-push library
                $message = __('settings.pwa.subscribers.notification_queued');
                if ($saveToInbox) {
                    $message .= ' ' . __('settings.pwa.subscribers.saved_to_inbox');
                }
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = __('settings.error_save') . ': ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    } elseif ($action === 'delete_subscriber') {
        $subscriberId = intval($_POST['subscriber_id'] ?? 0);
        if ($subscriberId > 0) {
            try {
                $stmt = $pdo->prepare("DELETE FROM rzx_push_subscribers WHERE id = ?");
                $stmt->execute([$subscriberId]);
                $message = __('settings.pwa.subscribers.deleted');
                $messageType = 'success';

                // Refresh subscribers list
                header('Location: ' . $_SERVER['REQUEST_URI']);
                exit;
            } catch (PDOException $e) {
                $message = __('settings.error_save') . ': ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    } elseif ($action === 'create_tables') {
        try {
            // Create subscribers table
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS rzx_push_subscribers (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    endpoint TEXT NOT NULL,
                    p256dh VARCHAR(255),
                    auth VARCHAR(255),
                    user_agent VARCHAR(500),
                    ip_address VARCHAR(45),
                    user_id INT NULL,
                    is_active TINYINT(1) DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    updated_at DATETIME DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id),
                    INDEX idx_is_active (is_active)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Create messages table
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS rzx_push_messages (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    title VARCHAR(255) NOT NULL,
                    body TEXT NOT NULL,
                    url VARCHAR(500),
                    icon VARCHAR(500),
                    target ENUM('all', 'customers', 'admins') DEFAULT 'all',
                    sent_count INT DEFAULT 0,
                    failed_count INT DEFAULT 0,
                    status ENUM('pending', 'sending', 'completed', 'failed') DEFAULT 'pending',
                    save_to_inbox TINYINT(1) DEFAULT 1,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    sent_at DATETIME NULL,
                    INDEX idx_status (status),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            // Create user notifications inbox table
            $pdo->exec("
                CREATE TABLE IF NOT EXISTS rzx_user_notifications (
                    id INT AUTO_INCREMENT PRIMARY KEY,
                    user_id INT NULL COMMENT 'NULL for guest/anonymous users',
                    message_id INT NULL COMMENT 'Reference to rzx_push_messages',
                    title VARCHAR(255) NOT NULL,
                    body TEXT NOT NULL,
                    url VARCHAR(500),
                    icon VARCHAR(500),
                    type ENUM('push', 'system', 'reservation', 'promotion') DEFAULT 'push',
                    is_read TINYINT(1) DEFAULT 0,
                    read_at DATETIME NULL,
                    created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
                    INDEX idx_user_id (user_id),
                    INDEX idx_message_id (message_id),
                    INDEX idx_is_read (is_read),
                    INDEX idx_type (type),
                    INDEX idx_created_at (created_at)
                ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
            ");

            $message = __('settings.pwa.subscribers.tables_created');
            $messageType = 'success';

            // Refresh page
            header('Location: ' . $_SERVER['REQUEST_URI']);
            exit;
        } catch (PDOException $e) {
            $message = __('settings.error_save') . ': ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Check if tables exist
$tablesExist = false;
try {
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'rzx_push_subscribers'");
    $tablesExist = $tableCheck->rowCount() > 0;
} catch (PDOException $e) {
    // Ignore
}

// Start content buffering
ob_start();

// Include tabs
include __DIR__ . '/_tabs.php';
?>

<?php if (!$tablesExist): ?>
<!-- Tables Not Created Warning -->
<div class="bg-yellow-50 dark:bg-yellow-900/20 border border-yellow-200 dark:border-yellow-800 rounded-xl p-6 mb-6">
    <div class="flex items-start">
        <svg class="w-6 h-6 text-yellow-600 dark:text-yellow-400 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
        </svg>
        <div class="flex-1">
            <h3 class="text-lg font-semibold text-yellow-800 dark:text-yellow-200"><?= __('settings.pwa.subscribers.tables_missing') ?></h3>
            <p class="text-sm text-yellow-700 dark:text-yellow-300 mt-1"><?= __('settings.pwa.subscribers.tables_missing_desc') ?></p>
            <form method="POST" class="mt-4">
                <input type="hidden" name="action" value="create_tables">
                <button type="submit" class="px-4 py-2 bg-yellow-600 text-white font-medium rounded-lg hover:bg-yellow-700 transition">
                    <?= __('settings.pwa.subscribers.create_tables') ?>
                </button>
            </form>
        </div>
    </div>
</div>
<?php else: ?>

<!-- Stats Overview -->
<div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-6">
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 transition-colors">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('settings.pwa.subscribers.stats.total') ?></p>
                <p class="text-2xl font-bold text-zinc-900 dark:text-white"><?= number_format($totalSubscribers) ?></p>
            </div>
            <div class="w-12 h-12 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center">
                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                </svg>
            </div>
        </div>
    </div>
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 transition-colors">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('settings.pwa.subscribers.stats.messages_sent') ?></p>
                <p class="text-2xl font-bold text-zinc-900 dark:text-white"><?= number_format(count($messages)) ?></p>
            </div>
            <div class="w-12 h-12 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center">
                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>
                </svg>
            </div>
        </div>
    </div>
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 transition-colors">
        <div class="flex items-center justify-between">
            <div>
                <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('settings.pwa.subscribers.stats.webpush_status') ?></p>
                <p class="text-lg font-semibold <?= ($settings['webpush_enabled'] ?? '0') === '1' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>">
                    <?= ($settings['webpush_enabled'] ?? '0') === '1' ? __('admin.common.enabled') : __('admin.common.disabled') ?>
                </p>
            </div>
            <div class="w-12 h-12 rounded-full <?= ($settings['webpush_enabled'] ?? '0') === '1' ? 'bg-green-100 dark:bg-green-900/30' : 'bg-red-100 dark:bg-red-900/30'; ?> flex items-center justify-center">
                <svg class="w-6 h-6 <?= ($settings['webpush_enabled'] ?? '0') === '1' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'; ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
                </svg>
            </div>
        </div>
    </div>
</div>

<!-- Send Notification Form -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('settings.pwa.subscribers.send.title') ?></h3>

    <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="send_notification">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label for="notif_title" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                    <?= __('settings.pwa.subscribers.send.title_label') ?> <span class="text-red-500">*</span>
                </label>
                <input type="text" name="notif_title" id="notif_title" required
                       class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="<?= __('settings.pwa.subscribers.send.title_placeholder') ?>">
            </div>
            <div>
                <label for="notif_url" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                    <?= __('settings.pwa.subscribers.send.url_label') ?>
                </label>
                <input type="url" name="notif_url" id="notif_url"
                       class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="https://example.com/page">
            </div>
        </div>

        <div>
            <label for="notif_body" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                <?= __('settings.pwa.subscribers.send.body_label') ?> <span class="text-red-500">*</span>
            </label>
            <textarea name="notif_body" id="notif_body" rows="3" required
                      class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      placeholder="<?= __('settings.pwa.subscribers.send.body_placeholder') ?>"></textarea>
        </div>

        <div class="flex items-center justify-between flex-wrap gap-4">
            <div class="flex items-center gap-6">
                <div>
                    <label for="notif_target" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                        <?= __('settings.pwa.subscribers.send.target_label') ?>
                    </label>
                    <select name="notif_target" id="notif_target"
                            class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        <option value="all"><?= __('settings.pwa.subscribers.send.target_all') ?></option>
                        <option value="customers"><?= __('settings.pwa.subscribers.send.target_customers') ?></option>
                        <option value="admins"><?= __('settings.pwa.subscribers.send.target_admins') ?></option>
                    </select>
                </div>

                <div class="pt-5">
                    <label class="flex items-center cursor-pointer">
                        <input type="checkbox" name="save_to_inbox" value="1" checked
                               class="w-4 h-4 text-blue-600 bg-zinc-100 border-zinc-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-zinc-800 focus:ring-2 dark:bg-zinc-700 dark:border-zinc-600">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('settings.pwa.subscribers.send.save_to_inbox') ?></span>
                    </label>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1 ml-6"><?= __('settings.pwa.subscribers.send.save_to_inbox_hint') ?></p>
                </div>
            </div>

            <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/>
                </svg>
                <?= __('settings.pwa.subscribers.send.submit') ?>
            </button>
        </div>
    </form>
</div>

<!-- Subscribers List -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm transition-colors">
    <div class="p-6 border-b border-zinc-200 dark:border-zinc-700">
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('settings.pwa.subscribers.list.title') ?></h3>
    </div>

    <?php if (empty($subscribers)): ?>
    <div class="p-8 text-center">
        <svg class="w-12 h-12 mx-auto text-zinc-300 dark:text-zinc-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
        </svg>
        <p class="text-zinc-500 dark:text-zinc-400"><?= __('settings.pwa.subscribers.list.empty') ?></p>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">ID</th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider"><?= __('settings.pwa.subscribers.list.endpoint') ?></th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider"><?= __('settings.pwa.subscribers.list.user_agent') ?></th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider"><?= __('settings.pwa.subscribers.list.created') ?></th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider"><?= __('settings.pwa.subscribers.list.status') ?></th>
                    <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider"><?= __('admin.common.actions') ?></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                <?php foreach ($subscribers as $sub): ?>
                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50 transition-colors">
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-900 dark:text-white"><?= $sub['id'] ?></td>
                    <td class="px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                        <div class="max-w-xs truncate" title="<?= htmlspecialchars($sub['endpoint']) ?>">
                            <?= htmlspecialchars(substr($sub['endpoint'], 0, 50)) ?>...
                        </div>
                    </td>
                    <td class="px-6 py-4 text-sm text-zinc-500 dark:text-zinc-400">
                        <div class="max-w-xs truncate">
                            <?= htmlspecialchars($sub['user_agent'] ?? '-') ?>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                        <?= date('Y-m-d H:i', strtotime($sub['created_at'])) ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php if ($sub['is_active']): ?>
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400">
                            <?= __('admin.common.active') ?>
                        </span>
                        <?php else: ?>
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400">
                            <?= __('admin.common.inactive') ?>
                        </span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm">
                        <form method="POST" class="inline" onsubmit="return confirm('<?= __('settings.pwa.subscribers.delete_confirm') ?>')">
                            <input type="hidden" name="action" value="delete_subscriber">
                            <input type="hidden" name="subscriber_id" value="<?= $sub['id'] ?>">
                            <button type="submit" class="text-red-600 hover:text-red-700 dark:text-red-400 dark:hover:text-red-300">
                                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                                </svg>
                            </button>
                        </form>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <!-- Pagination -->
    <?php if ($totalPages > 1): ?>
    <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700">
        <div class="flex items-center justify-between">
            <p class="text-sm text-zinc-500 dark:text-zinc-400">
                <?= __('admin.common.showing') ?> <?= $offset + 1 ?> - <?= min($offset + $perPage, $totalSubscribers) ?> <?= __('admin.common.of') ?> <?= $totalSubscribers ?>
            </p>
            <div class="flex gap-2">
                <?php if ($page > 1): ?>
                <a href="?page=<?= $page - 1 ?>" class="px-3 py-1 text-sm bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 rounded hover:bg-zinc-200 dark:hover:bg-zinc-600">
                    <?= __('admin.common.prev') ?>
                </a>
                <?php endif; ?>

                <?php if ($page < $totalPages): ?>
                <a href="?page=<?= $page + 1 ?>" class="px-3 py-1 text-sm bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 rounded hover:bg-zinc-200 dark:hover:bg-zinc-600">
                    <?= __('admin.common.next') ?>
                </a>
                <?php endif; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>
    <?php endif; ?>
</div>

<!-- Recent Messages -->
<?php if (!empty($messages)): ?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm mt-6 transition-colors">
    <div class="p-6 border-b border-zinc-200 dark:border-zinc-700">
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('settings.pwa.subscribers.messages.title') ?></h3>
    </div>
    <div class="overflow-x-auto">
        <table class="w-full">
            <thead class="bg-zinc-50 dark:bg-zinc-900">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider"><?= __('settings.pwa.subscribers.messages.title_col') ?></th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider"><?= __('settings.pwa.subscribers.messages.sent_count') ?></th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider"><?= __('settings.pwa.subscribers.messages.status') ?></th>
                    <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider"><?= __('settings.pwa.subscribers.messages.created') ?></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                <?php foreach ($messages as $msg): ?>
                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-900/50 transition-colors">
                    <td class="px-6 py-4">
                        <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($msg['title']) ?></p>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 truncate max-w-xs"><?= htmlspecialchars($msg['body']) ?></p>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                        <?= $msg['sent_count'] ?>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap">
                        <?php
                        $statusColors = [
                            'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                            'sending' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                            'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                            'failed' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                        ];
                        $statusClass = $statusColors[$msg['status']] ?? $statusColors['pending'];
                        ?>
                        <span class="px-2 py-1 text-xs font-medium rounded-full <?= $statusClass ?>">
                            <?= __('settings.pwa_status_' . $msg['status']) ?? ucfirst($msg['status']) ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-zinc-500 dark:text-zinc-400">
                        <?= date('Y-m-d H:i', strtotime($msg['created_at'])) ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php endif; ?>

<?php endif; ?>

<script>
    console.log('Push subscribers page loaded');
</script>

<?php
$pageContent = ob_get_clean();
include dirname(__DIR__) . '/_layout.php';
