<?php
/**
 * RezlyX Profile Edit Page
 */

require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
use RzxLib\Core\Auth\Auth;

if (!Auth::check()) {
    header('Location: ' . ($config['app_url'] ?? '') . '/login');
    exit;
}

$user = Auth::user();
$pageTitle = ($config['app_name'] ?? 'RezlyX') . ' - ' . __('auth.profile.title');
$baseUrl = $config['app_url'] ?? '';

// 헤더에서 사용할 변수
$isLoggedIn = true;
$currentUser = $user;

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = trim($_POST['name'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($name)) {
        $error = __('validation.required', ['attribute' => __('auth.profile.name')]);
    } else {
        $result = Auth::updateProfile($user['id'], [
            'name' => $name,
            'phone' => $phone,
        ]);

        if ($result['success']) {
            $success = __('auth.profile.success');
            // 업데이트된 사용자 정보 다시 가져오기
            $user = Auth::user();
            $currentUser = $user;
        } else {
            $error = __('auth.profile.error');
        }
    }
}

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
                        <a href="<?php echo $baseUrl; ?>/mypage/profile" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <?php echo __('auth.mypage.menu.profile'); ?>
                        </a>
                        <a href="<?php echo $baseUrl; ?>/mypage/password" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-600 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-700">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <?php echo __('auth.mypage.menu.password'); ?>
                        </a>
                    </nav>
                </div>
            </aside>

            <!-- 메인 콘텐츠 -->
            <div class="flex-1">
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-6">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-2"><?php echo __('auth.profile.title'); ?></h1>
                    <p class="text-gray-500 dark:text-zinc-400 mb-6"><?php echo __('auth.profile.description'); ?></p>

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
                            <label for="name" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                <?php echo __('auth.profile.name'); ?> <span class="text-red-500">*</span>
                            </label>
                            <input type="text" name="name" id="name"
                                   value="<?php echo htmlspecialchars($user['name'] ?? ''); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   required>
                        </div>

                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                <?php echo __('auth.profile.email'); ?>
                            </label>
                            <input type="email" id="email"
                                   value="<?php echo htmlspecialchars($user['email'] ?? ''); ?>"
                                   class="w-full px-4 py-3 border border-gray-200 dark:border-zinc-700 bg-gray-100 dark:bg-zinc-800 text-gray-500 dark:text-zinc-400 rounded-lg cursor-not-allowed"
                                   disabled readonly>
                            <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1"><?php echo __('auth.profile.email_hint'); ?></p>
                        </div>

                        <div>
                            <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                <?php echo __('auth.profile.phone'); ?>
                            </label>
                            <input type="tel" name="phone" id="phone"
                                   value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>

                        <div class="flex items-center gap-3 pt-4">
                            <button type="submit" class="px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition shadow-lg shadow-blue-500/30">
                                <?php echo __('auth.profile.submit'); ?>
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
