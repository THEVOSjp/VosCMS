<?php
/**
 * RezlyX Member Skin - Modern
 * 비밀번호 찾기/재설정 페이지 템플릿
 *
 * 사용 가능한 변수:
 * - $config: 스킨 설정
 * - $colorset: 현재 컬러셋
 * - $translations: 번역 데이터
 * - $step: 현재 단계 (email, sent, reset, complete)
 * - $errors: 폼 에러 메시지 배열
 * - $email: 이메일 주소
 * - $token: 재설정 토큰
 * - $csrfToken: CSRF 토큰
 * - $loginUrl: 로그인 URL
 * - $baseUrl: 기본 URL
 * - $siteName: 사이트 이름
 */

// 컬러셋 CSS 변수
$colors = $colorset ?? $config['colorsets']['default'];
$step = $step ?? 'email';

// ============================================================================
// 로케일 직접 감지 및 번역 재설정
// ============================================================================
$_skinLocale = 'ko';
if (!empty($_SESSION['locale']) && in_array($_SESSION['locale'], ['ko', 'en', 'ja'])) {
    $_skinLocale = $_SESSION['locale'];
} elseif (!empty($_COOKIE['locale']) && in_array($_COOKIE['locale'], ['ko', 'en', 'ja'])) {
    $_skinLocale = $_COOKIE['locale'];
} elseif (function_exists('current_locale')) {
    $_skinLocale = current_locale();
}

// 로케일별 번역 데이터
$_skinTranslations = [
    'ko' => [
        'password_reset_title' => '비밀번호 찾기',
        'password_reset_email_desc' => '가입 시 사용한 이메일을 입력하세요',
        'password_reset_sent_desc' => '이메일을 확인해주세요',
        'password_reset_new_desc' => '새 비밀번호를 입력하세요',
        'password_reset_complete_desc' => '새 비밀번호로 로그인하세요',
        'email' => '이메일',
        'email_placeholder' => 'example@email.com',
        'send_code' => '인증 메일 발송',
        'email_sent_title' => '이메일이 발송되었습니다',
        'email_sent_desc' => '비밀번호 재설정 링크가 이메일로 전송되었습니다.',
        'new_password_title' => '새 비밀번호 설정',
        'new_password' => '새 비밀번호',
        'new_password_placeholder' => '8자 이상 입력하세요',
        'password_hint' => '영문, 숫자를 포함하여 8자 이상',
        'password_confirm' => '비밀번호 확인',
        'password_confirm_placeholder' => '비밀번호를 다시 입력하세요',
        'reset_password' => '비밀번호 변경',
        'password_changed' => '비밀번호가 변경되었습니다',
        'go_to_login' => '로그인하기',
        'back_to_login' => '로그인으로 돌아가기',
        'back_to_home' => '홈으로 돌아가기',
    ],
    'en' => [
        'password_reset_title' => 'Reset Password',
        'password_reset_email_desc' => 'Enter the email you used to sign up',
        'password_reset_sent_desc' => 'Check your email',
        'password_reset_new_desc' => 'Enter your new password',
        'password_reset_complete_desc' => 'Log in with your new password',
        'email' => 'Email',
        'email_placeholder' => 'example@email.com',
        'send_code' => 'Send Reset Email',
        'email_sent_title' => 'Email has been sent',
        'email_sent_desc' => 'A password reset link has been sent to your email.',
        'new_password_title' => 'Set New Password',
        'new_password' => 'New Password',
        'new_password_placeholder' => 'At least 8 characters',
        'password_hint' => 'At least 8 characters with letters and numbers',
        'password_confirm' => 'Confirm Password',
        'password_confirm_placeholder' => 'Re-enter your password',
        'reset_password' => 'Reset Password',
        'password_changed' => 'Password has been changed',
        'go_to_login' => 'Go to Login',
        'back_to_login' => 'Back to Login',
        'back_to_home' => 'Back to Home',
    ],
    'ja' => [
        'password_reset_title' => 'パスワードリセット',
        'password_reset_email_desc' => '登録時のメールアドレスを入力してください',
        'password_reset_sent_desc' => 'メールをご確認ください',
        'password_reset_new_desc' => '新しいパスワードを入力してください',
        'password_reset_complete_desc' => '新しいパスワードでログインしてください',
        'email' => 'メール',
        'email_placeholder' => 'example@email.com',
        'send_code' => '認証メール送信',
        'email_sent_title' => 'メールが送信されました',
        'email_sent_desc' => 'パスワードリセットリンクがメールで送信されました。',
        'new_password_title' => '新しいパスワードを設定',
        'new_password' => '新しいパスワード',
        'new_password_placeholder' => '8文字以上で入力してください',
        'password_hint' => '英数字を含む8文字以上',
        'password_confirm' => 'パスワード確認',
        'password_confirm_placeholder' => 'パスワードを再入力してください',
        'reset_password' => 'パスワード変更',
        'password_changed' => 'パスワードが変更されました',
        'go_to_login' => 'ログインへ',
        'back_to_login' => 'ログインに戻る',
        'back_to_home' => 'ホームに戻る',
    ],
];

// 현재 로케일의 번역으로 $translations 병합
$translations = array_merge($translations ?? [], $_skinTranslations[$_skinLocale] ?? $_skinTranslations['ko']);
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $translations['password_reset_title'] ?? '비밀번호 찾기' ?> - <?= htmlspecialchars($siteName ?? 'RezlyX') ?></title>

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>

    <!-- Pretendard Font -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">

    <style>
        body { font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; }

        /* 스킨 컬러셋 CSS 변수 */
        :root {
            --skin-primary: <?= $colors['primary'] ?>;
            --skin-primary-hover: <?= $colors['primary_hover'] ?? $colors['primary'] ?>;
            --skin-secondary: <?= $colors['secondary'] ?>;
            --skin-background: <?= $colors['background'] ?>;
            --skin-card: <?= $colors['card'] ?? '#FFFFFF' ?>;
            --skin-text: <?= $colors['text'] ?>;
            --skin-border: <?= $colors['border'] ?? '#E5E7EB' ?>;
        }
        .dark {
            --skin-primary: <?= $colors['primary_dark'] ?? $colors['primary'] ?>;
            --skin-background: <?= $colors['background_dark'] ?? '#18181B' ?>;
            --skin-card: <?= $colors['card_dark'] ?? '#27272A' ?>;
            --skin-text: <?= $colors['text_dark'] ?? '#F4F4F5' ?>;
            --skin-border: <?= $colors['border_dark'] ?? '#3F3F46' ?>;
        }
    </style>

    <!-- 다크 모드 초기화 (깜빡임 방지) -->
    <script>
        (function() {
            var darkMode = localStorage.getItem('darkMode');
            var shouldBeDark = false;
            if (darkMode === 'true') {
                shouldBeDark = true;
            } else if (darkMode === 'false') {
                shouldBeDark = false;
            } else {
                shouldBeDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            }
            if (shouldBeDark) {
                document.documentElement.classList.add('dark');
            }
        })();
    </script>
</head>
<body class="bg-gray-50 dark:bg-zinc-900 min-h-screen transition-colors duration-200">
    <!-- Header (재사용 컴포넌트) -->
    <?php
    $headerPath = __DIR__ . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'header.php';
    if (!file_exists($headerPath)) {
        $headerPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'header.php';
    }
    if (file_exists($headerPath)) {
        include $headerPath;
    } else {
        echo '<!-- Header not found -->';
    }
    ?>

    <!-- Password Reset Form Section -->
    <main class="flex items-center justify-center min-h-[calc(100vh-4rem)] py-12 px-4">
        <div class="w-full max-w-md">
            <!-- Password Reset Card -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl dark:shadow-zinc-900/50 p-8 transition-colors duration-200">

                <?php if ($step === 'email'): ?>
                <!-- Step 1: 이메일 입력 -->
                <div class="text-center mb-8">
                    <div class="w-16 h-16 mx-auto bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z" />
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= $translations['password_reset_title'] ?? '비밀번호 찾기' ?></h1>
                    <p class="text-gray-600 dark:text-zinc-400 mt-2"><?= $translations['password_reset_email_desc'] ?? '가입 시 사용한 이메일을 입력하세요' ?></p>
                </div>

                <!-- Error Messages -->
                <?php if (!empty($errors)): ?>
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg">
                    <?php foreach ($errors as $error): ?>
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-red-700 dark:text-red-300 text-sm"><?= htmlspecialchars($error) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <form method="POST" class="space-y-5">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                            <?= $translations['email'] ?? '이메일' ?>
                        </label>
                        <input type="email" name="email" id="email"
                               value="<?= htmlspecialchars($email ?? '') ?>"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                               placeholder="<?= $translations['email_placeholder'] ?? 'example@email.com' ?>"
                               required>
                    </div>

                    <button type="submit" class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-semibold rounded-lg transition shadow-lg shadow-blue-500/30">
                        <?= $translations['send_code'] ?? '인증 메일 발송' ?>
                    </button>
                </form>

                <?php elseif ($step === 'sent'): ?>
                <!-- Step 2: 이메일 발송 완료 -->
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= $translations['email_sent_title'] ?? '이메일이 발송되었습니다' ?></h1>
                    <p class="text-gray-600 dark:text-zinc-400 mt-2">
                        <?= $translations['email_sent_desc'] ?? '비밀번호 재설정 링크가 이메일로 전송되었습니다.' ?>
                    </p>
                    <p class="text-sm text-gray-500 dark:text-zinc-500 mt-2">
                        <?= htmlspecialchars($email ?? '') ?>
                    </p>

                    <div class="mt-8 space-y-3">
                        <a href="<?= htmlspecialchars($loginUrl ?? '/login') ?>" class="block w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-semibold rounded-lg transition text-center">
                            <?= $translations['back_to_login'] ?? '로그인으로 돌아가기' ?>
                        </a>
                    </div>
                </div>

                <?php elseif ($step === 'reset'): ?>
                <!-- Step 3: 새 비밀번호 입력 -->
                <div class="text-center mb-8">
                    <div class="w-16 h-16 mx-auto bg-blue-100 dark:bg-blue-900/30 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= $translations['new_password_title'] ?? '새 비밀번호 설정' ?></h1>
                    <p class="text-gray-600 dark:text-zinc-400 mt-2"><?= $translations['password_reset_new_desc'] ?? '새 비밀번호를 입력하세요' ?></p>
                </div>

                <!-- Error Messages -->
                <?php if (!empty($errors)): ?>
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg">
                    <?php foreach ($errors as $error): ?>
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-red-700 dark:text-red-300 text-sm"><?= htmlspecialchars($error) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <form method="POST" class="space-y-5">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                    <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                            <?= $translations['new_password'] ?? '새 비밀번호' ?>
                        </label>
                        <div class="relative">
                            <input type="password" name="password" id="password"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition pr-12"
                                   placeholder="<?= $translations['new_password_placeholder'] ?? '8자 이상 입력하세요' ?>"
                                   minlength="8"
                                   required>
                            <button type="button" id="togglePassword" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-zinc-400 hover:text-gray-700 dark:hover:text-zinc-200">
                                <svg id="eyeIcon" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg id="eyeOffIcon" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1"><?= $translations['password_hint'] ?? '영문, 숫자를 포함하여 8자 이상' ?></p>
                    </div>

                    <div>
                        <label for="password_confirm" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                            <?= $translations['password_confirm'] ?? '비밀번호 확인' ?>
                        </label>
                        <div class="relative">
                            <input type="password" name="password_confirm" id="password_confirm"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition pr-12"
                                   placeholder="<?= $translations['password_confirm_placeholder'] ?? '비밀번호를 다시 입력하세요' ?>"
                                   minlength="8"
                                   required>
                            <button type="button" id="togglePasswordConfirm" class="absolute right-3 top-1/2 -translate-y-1/2 text-gray-500 dark:text-zinc-400 hover:text-gray-700 dark:hover:text-zinc-200">
                                <svg id="eyeIconConfirm" class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                                </svg>
                                <svg id="eyeOffIconConfirm" class="w-5 h-5 hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                    </div>

                    <button type="submit" class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-semibold rounded-lg transition shadow-lg shadow-blue-500/30">
                        <?= $translations['reset_password'] ?? '비밀번호 변경' ?>
                    </button>
                </form>

                <?php elseif ($step === 'complete'): ?>
                <!-- Step 4: 완료 -->
                <div class="text-center">
                    <div class="w-16 h-16 mx-auto bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center mb-4">
                        <svg class="w-8 h-8 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= $translations['password_changed'] ?? '비밀번호가 변경되었습니다' ?></h1>
                    <p class="text-gray-600 dark:text-zinc-400 mt-2"><?= $translations['password_reset_complete_desc'] ?? '새 비밀번호로 로그인하세요' ?></p>

                    <div class="mt-8">
                        <a href="<?= htmlspecialchars($loginUrl ?? '/login') ?>" class="block w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-semibold rounded-lg transition text-center">
                            <?= $translations['go_to_login'] ?? '로그인하기' ?>
                        </a>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Back to Login (only for email step) -->
                <?php if ($step === 'email'): ?>
                <p class="text-center text-gray-600 dark:text-zinc-400 mt-6">
                    <a href="<?= htmlspecialchars($loginUrl ?? '/login') ?>" class="text-blue-600 dark:text-blue-400 font-medium hover:underline">
                        <?= $translations['back_to_login'] ?? '로그인으로 돌아가기' ?>
                    </a>
                </p>
                <?php endif; ?>
            </div>

            <!-- Footer Link -->
            <p class="text-center text-gray-500 dark:text-zinc-400 text-sm mt-6">
                <a href="<?= htmlspecialchars($baseUrl ?? '') ?>/" class="hover:text-blue-600 dark:hover:text-blue-400">
                    <?= $translations['back_to_home'] ?? '홈으로 돌아가기' ?>
                </a>
            </p>
        </div>
    </main>

    <!-- 비밀번호 표시/숨기기 토글 -->
    <script>
        var togglePassword = document.getElementById('togglePassword');
        var passwordInput = document.getElementById('password');
        var eyeIcon = document.getElementById('eyeIcon');
        var eyeOffIcon = document.getElementById('eyeOffIcon');

        if (togglePassword && passwordInput) {
            togglePassword.addEventListener('click', function() {
                var type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordInput.setAttribute('type', type);
                eyeIcon.classList.toggle('hidden');
                eyeOffIcon.classList.toggle('hidden');
            });
        }

        var togglePasswordConfirm = document.getElementById('togglePasswordConfirm');
        var passwordConfirmInput = document.getElementById('password_confirm');
        var eyeIconConfirm = document.getElementById('eyeIconConfirm');
        var eyeOffIconConfirm = document.getElementById('eyeOffIconConfirm');

        if (togglePasswordConfirm && passwordConfirmInput) {
            togglePasswordConfirm.addEventListener('click', function() {
                var type = passwordConfirmInput.getAttribute('type') === 'password' ? 'text' : 'password';
                passwordConfirmInput.setAttribute('type', type);
                eyeIconConfirm.classList.toggle('hidden');
                eyeOffIconConfirm.classList.toggle('hidden');
            });
        }
    </script>
</body>
</html>
