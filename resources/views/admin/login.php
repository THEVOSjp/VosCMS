<?php
/**
 * VosCMS 관리자 로그인 페이지
 * 고객 로그인과 별도 세션 (admin_id)
 */

// 이미 로그인된 경우 대시보드로 이동
if (\RzxLib\Core\Auth\AdminAuth::check()) {
    header('Location: ' . $basePath . '/' . ($config['admin_path'] ?? 'admin'));
    exit;
}

// POST 처리
$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = __('auth.login.required');
    } else {
        require_once BASE_PATH . '/rzxlib/Core/Auth/AdminAuth.php';
        \RzxLib\Core\Auth\AdminAuth::init($pdo);

        $remember = !empty($_POST['remember']);
        $result = \RzxLib\Core\Auth\AdminAuth::attempt($email, $password, $remember);

        if (is_array($result)) {
            // 로그인 성공 → 대시보드로 리다이렉트
            header('Location: ' . $basePath . '/' . ($config['admin_path'] ?? 'admin'));
            exit;
        } else {
            // 로그인 실패 — 에러 메시지 다국어 매핑
            $errorMessages = [
                'invalid_credentials' => __('auth.login.failed'),
                'account_inactive'    => __('auth.admin_login.errors.account_inactive'),
                'not_admin'           => __('auth.admin_login.errors.not_admin'),
                'user_inactive'       => __('auth.admin_login.errors.user_inactive'),
                'staff_inactive'      => __('auth.admin_login.errors.staff_inactive'),
            ];
            $error = $errorMessages[$result] ?? __('auth.admin_login.errors.login_failed');
        }
    }
    error_log('[Admin Login] Attempt: ' . $email . ' → ' . (is_array($result ?? null) ? 'success' : ($result ?? 'unknown')));
}

// site_name 이 다국어 JSON 으로 저장될 수 있으므로 헬퍼로 현재 로케일 추출
$siteName = function_exists('get_site_name') ? get_site_name() : ($siteSettings['site_name'] ?? 'RezlyX');
$_locale = function_exists('current_locale') ? current_locale() : ($config['locale'] ?? 'ko');
?>
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($_locale) ?>" class="h-full">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($siteName) ?> - <?= htmlspecialchars(__('auth.admin_login.title')) ?></title>
    <?php if ($pwaSettings = $siteSettings ?? []): ?>
        <?php include __DIR__ . '/partials/pwa-head.php'; ?>
    <?php endif; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        @import url('https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css');
        body { font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; }
    </style>
</head>
<body class="h-full bg-zinc-100 dark:bg-zinc-900">
    <div class="min-h-full flex items-center justify-center py-12 px-4">
        <div class="w-full max-w-md">
            <!-- 로고 영역 -->
            <div class="text-center mb-8">
                <div class="inline-flex items-center justify-center w-16 h-16 bg-blue-600 rounded-2xl mb-4">
                    <svg class="w-8 h-8 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($siteName) ?></h1>
                <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1"><?= htmlspecialchars(__('auth.admin_login.title')) ?></p>
            </div>

            <!-- 로그인 폼 -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-8">
                <?php if ($error): ?>
                <div class="mb-6 p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-4 h-4 text-red-600 dark:text-red-400 mr-2 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <span class="text-sm text-red-700 dark:text-red-300"><?= htmlspecialchars($error) ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <form method="POST" action="" class="space-y-5">
                    <div>
                        <label for="email" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5"><?= htmlspecialchars(__('auth.login.email')) ?></label>
                        <input type="email" id="email" name="email" autocomplete="email" required
                               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                               class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                               placeholder="admin@example.com">
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1.5"><?= htmlspecialchars(__('auth.login.password')) ?></label>
                        <input type="password" id="password" name="password" autocomplete="current-password" required
                               class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-600 rounded-xl bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                               placeholder="••••••••">
                    </div>

                    <label class="flex items-center gap-2 cursor-pointer">
                        <input type="checkbox" name="remember" value="1" checked
                               class="w-4 h-4 rounded border-zinc-300 dark:border-zinc-600 text-blue-600 focus:ring-blue-500">
                        <span class="text-sm text-zinc-600 dark:text-zinc-400"><?= htmlspecialchars(__('auth.login.remember')) ?></span>
                    </label>

                    <button type="submit"
                            class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-xl transition focus:ring-2 focus:ring-blue-500 focus:ring-offset-2">
                        <?= htmlspecialchars(__('auth.login.submit')) ?>
                    </button>
                </form>
            </div>

            <!-- 하단 -->
            <p class="text-center text-xs text-zinc-400 dark:text-zinc-500 mt-6">
                &copy; <?= date('Y') ?> <?= htmlspecialchars($siteName) ?>. Powered by VosCMS.
            </p>
        </div>
    </div>

    <script>
    // 자동 포커스
    document.getElementById('email').focus();
    </script>
</body>
</html>
