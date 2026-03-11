<?php
/**
 * RezlyX Password Change Page
 */

require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
use RzxLib\Core\Auth\Auth;

if (!Auth::check()) {
    header('Location: ' . ($config['app_url'] ?? '') . '/login');
    exit;
}

$user = Auth::user();
$pageTitle = ($config['app_name'] ?? 'RezlyX') . ' - ' . __('auth.password_change.title');
$baseUrl = $config['app_url'] ?? '';

// 헤더에서 사용할 변수
$isLoggedIn = true;
$currentUser = $user;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $currentPassword = $_POST['current_password'] ?? '';
    $newPassword = $_POST['new_password'] ?? '';
    $confirmPassword = $_POST['confirm_password'] ?? '';

    if (empty($currentPassword) || empty($newPassword) || empty($confirmPassword)) {
        $error = __('validation.required', ['attribute' => __('auth.password_change.current')]);
    } elseif ($newPassword !== $confirmPassword) {
        $error = __('validation.confirmed', ['attribute' => __('auth.password_change.new')]);
    } elseif (strlen($newPassword) < 12) {
        $error = __('validation.min.string', ['attribute' => __('auth.password_change.new'), 'min' => 12]);
    } elseif (!preg_match('/[A-Z]/', $newPassword) || !preg_match('/[a-z]/', $newPassword) ||
              !preg_match('/[0-9]/', $newPassword) || !preg_match('/[^A-Za-z0-9]/', $newPassword)) {
        $error = __('auth.register.password_hint');
    } else {
        // Verify current password
        if (!password_verify($currentPassword, $user['password'] ?? '')) {
            $error = __('auth.password_change.wrong_password');
        } else {
            // Update password
            $result = Auth::changePassword($user['id'], $newPassword);
            if ($result['success']) {
                $success = __('auth.password_change.success');
            } else {
                $error = $result['error'] ?? __('auth.password_change.error');
            }
        }
    }
}

// 헤더 포함
include BASE_PATH . '/resources/views/partials/header.php';
?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="lg:flex lg:gap-8">
            <!-- 사이드바 -->
            <?php
            $profileImgUrl = '';
            if (!empty($user['profile_image'])) {
                $profileImgUrl = str_starts_with($user['profile_image'], 'http')
                    ? $user['profile_image']
                    : $baseUrl . $user['profile_image'];
            }
            $sidebarActive = 'password';
            include BASE_PATH . '/resources/views/components/mypage-sidebar.php';
            ?>

            <!-- 메인 콘텐츠 -->
            <div class="flex-1">
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-6">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2"><?php echo __('auth.password_change.title'); ?></h1>
                    <p class="text-gray-500 dark:text-zinc-400 mb-6"><?php echo __('auth.password_change.description'); ?></p>

                    <?php if ($error): ?>
                    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg">
                        <span class="text-red-700 dark:text-red-300 text-sm"><?php echo htmlspecialchars($error); ?></span>
                    </div>
                    <?php endif; ?>

                    <?php if ($success): ?>
                    <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg">
                        <span class="text-green-700 dark:text-green-300 text-sm"><?php echo htmlspecialchars($success); ?></span>
                    </div>
                    <?php endif; ?>

                    <form method="POST" class="space-y-5">
                        <div>
                            <label for="current_password" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                <?php echo __('auth.password_change.current'); ?> <span class="text-red-500">*</span>
                            </label>
                            <input type="password" name="current_password" id="current_password"
                                   placeholder="<?php echo __('auth.password_change.current_placeholder'); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                        </div>

                        <div>
                            <label for="new_password" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                <?php echo __('auth.password_change.new'); ?> <span class="text-red-500">*</span>
                            </label>
                            <input type="password" name="new_password" id="new_password"
                                   placeholder="<?php echo __('auth.password_change.new_placeholder'); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                            <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1"><?php echo __('auth.register.password_hint'); ?></p>
                        </div>

                        <div>
                            <label for="confirm_password" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                <?php echo __('auth.password_change.confirm'); ?> <span class="text-red-500">*</span>
                            </label>
                            <input type="password" name="confirm_password" id="confirm_password"
                                   placeholder="<?php echo __('auth.password_change.confirm_placeholder'); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                        </div>

                        <div class="flex items-center gap-3 pt-4">
                            <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition shadow-lg shadow-blue-500/30">
                                <?php echo __('auth.password_change.submit'); ?>
                            </button>
                            <a href="<?php echo $baseUrl; ?>/mypage" class="px-6 py-3 bg-gray-200 dark:bg-zinc-700 hover:bg-gray-300 dark:hover:bg-zinc-600 text-gray-700 dark:text-zinc-300 font-semibold rounded-lg transition">
                                <?php echo __('common.buttons.cancel'); ?>
                            </a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

<?php
// 푸터 포함
include BASE_PATH . '/resources/views/partials/footer.php';
?>
