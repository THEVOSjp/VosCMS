<?php
/**
 * RezlyX Admin Members Settings - Features
 * Member feature settings configuration
 */

require_once __DIR__ . '/_init.php';

$pageTitle = __('admin.members.settings.tabs.features') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$currentMemberSettingsPage = 'features';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_features') {
        try {
            $stmt = $pdo->prepare("INSERT INTO {$prefix}settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");

            // 스크랩 보기
            $viewScrap = isset($_POST['member_view_scrap']) ? '1' : '0';
            $stmt->execute(['member_view_scrap', $viewScrap]);
            $memberSettings['member_view_scrap'] = $viewScrap;

            // 저장함 보기
            $viewBookmark = isset($_POST['member_view_bookmark']) ? '1' : '0';
            $stmt->execute(['member_view_bookmark', $viewBookmark]);
            $memberSettings['member_view_bookmark'] = $viewBookmark;

            // 작성 글 보기
            $viewPosts = isset($_POST['member_view_posts']) ? '1' : '0';
            $stmt->execute(['member_view_posts', $viewPosts]);
            $memberSettings['member_view_posts'] = $viewPosts;

            // 작성 댓글 보기
            $viewComments = isset($_POST['member_view_comments']) ? '1' : '0';
            $stmt->execute(['member_view_comments', $viewComments]);
            $memberSettings['member_view_comments'] = $viewComments;

            // 자동 로그인 관리
            $autoLoginManage = isset($_POST['member_auto_login_manage']) ? '1' : '0';
            $stmt->execute(['member_auto_login_manage', $autoLoginManage]);
            $memberSettings['member_auto_login_manage'] = $autoLoginManage;

            $message = __('admin.settings.success');
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = __('admin.settings.error_save') . ': ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

ob_start();
?>

<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 transition-colors">
    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2"><?= __('admin.members.settings.features.title') ?></h2>
    <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-6"><?= __('admin.members.settings.features.description') ?></p>

    <form method="POST" class="space-y-6">
        <input type="hidden" name="action" value="update_features">

        <!-- 스크랩 보기 -->
        <div class="flex items-center justify-between py-4 border-b dark:border-zinc-700">
            <div>
                <h3 class="text-sm font-medium text-zinc-900 dark:text-white"><?= __('admin.members.settings.features.view_scrap') ?></h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('admin.members.settings.features.view_scrap_desc') ?></p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="member_view_scrap" class="sr-only peer" <?php echo ($memberSettings['member_view_scrap'] ?? '1') === '1' ? 'checked' : ''; ?>>
                <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-blue-600"></div>
            </label>
        </div>

        <!-- 저장함 보기 -->
        <div class="flex items-center justify-between py-4 border-b dark:border-zinc-700">
            <div>
                <h3 class="text-sm font-medium text-zinc-900 dark:text-white"><?= __('admin.members.settings.features.view_bookmark') ?></h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('admin.members.settings.features.view_bookmark_desc') ?></p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="member_view_bookmark" class="sr-only peer" <?php echo ($memberSettings['member_view_bookmark'] ?? '1') === '1' ? 'checked' : ''; ?>>
                <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-blue-600"></div>
            </label>
        </div>

        <!-- 작성 글 보기 -->
        <div class="flex items-center justify-between py-4 border-b dark:border-zinc-700">
            <div>
                <h3 class="text-sm font-medium text-zinc-900 dark:text-white"><?= __('admin.members.settings.features.view_posts') ?></h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('admin.members.settings.features.view_posts_desc') ?></p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="member_view_posts" class="sr-only peer" <?php echo ($memberSettings['member_view_posts'] ?? '1') === '1' ? 'checked' : ''; ?>>
                <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-blue-600"></div>
            </label>
        </div>

        <!-- 작성 댓글 보기 -->
        <div class="flex items-center justify-between py-4 border-b dark:border-zinc-700">
            <div>
                <h3 class="text-sm font-medium text-zinc-900 dark:text-white"><?= __('admin.members.settings.features.view_comments') ?></h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('admin.members.settings.features.view_comments_desc') ?></p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="member_view_comments" class="sr-only peer" <?php echo ($memberSettings['member_view_comments'] ?? '1') === '1' ? 'checked' : ''; ?>>
                <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-blue-600"></div>
            </label>
        </div>

        <!-- 자동 로그인 관리 -->
        <div class="flex items-center justify-between py-4">
            <div>
                <h3 class="text-sm font-medium text-zinc-900 dark:text-white"><?= __('admin.members.settings.features.auto_login_manage') ?></h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('admin.members.settings.features.auto_login_manage_desc') ?></p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="member_auto_login_manage" class="sr-only peer" <?php echo ($memberSettings['member_auto_login_manage'] ?? '1') === '1' ? 'checked' : ''; ?>>
                <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-blue-600"></div>
            </label>
        </div>

        <div class="flex justify-end pt-4 border-t dark:border-zinc-700">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                <?= __('admin.buttons.save') ?>
            </button>
        </div>
    </form>
</div>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/_layout.php';
