<?php
/**
 * RezlyX Member Skin - Modern
 * 로그인 페이지 템플릿
 *
 * 사용 가능한 변수:
 * - $config: 스킨 설정
 * - $colorset: 현재 컬러셋
 * - $translations: 번역 데이터
 * - $errors: 폼 에러 메시지 배열
 * - $oldInput: 이전 입력값
 * - $csrfToken: CSRF 토큰
 * - $registerUrl: 회원가입 URL
 * - $passwordResetUrl: 비밀번호 찾기 URL
 * - $socialProviders: 활성화된 소셜 로그인 제공자
 * - $siteName: 사이트 이름
 * - $baseUrl: 기본 URL
 */

// 컬러셋 CSS 변수
$colors = $colorset ?? $config['colorsets']['default'];
?>
<!DOCTYPE html>
<html lang="ko">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= $translations['login_title'] ?? '로그인' ?> - <?= htmlspecialchars($siteName ?? 'RezlyX') ?></title>

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
    // 우선 modern 스킨 자체 헤더 사용, 없으면 member/default, 최종 폴백은 기본 스킨 헤더
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

    <!-- Login Form Section -->
    <main class="flex items-center justify-center min-h-[calc(100vh-4rem)] py-12 px-4">
        <div class="w-full max-w-md">
            <!-- Login Card -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl dark:shadow-zinc-900/50 p-8 transition-colors duration-200">
                <!-- Title -->
                <div class="text-center mb-8">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= $translations['login_title'] ?? '로그인' ?></h1>
                    <p class="text-gray-600 dark:text-zinc-400 mt-2"><?= $translations['login_subtitle'] ?? '계정에 로그인하세요' ?></p>
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

                <!-- Login Form -->
                <form method="POST" class="space-y-5">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">

                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                            <?= $translations['email'] ?? '이메일' ?>
                        </label>
                        <input type="email" name="email" id="email"
                               value="<?= htmlspecialchars($oldInput['email'] ?? '') ?>"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                               placeholder="<?= $translations['email_placeholder'] ?? 'example@email.com' ?>"
                               required>
                    </div>

                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                            <?= $translations['password'] ?? '비밀번호' ?>
                        </label>
                        <div class="relative">
                            <input type="password" name="password" id="password"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition pr-12"
                                   placeholder="<?= $translations['password_placeholder'] ?? '비밀번호를 입력하세요' ?>"
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
                    </div>

                    <div class="flex items-center justify-between">
                        <?php if ($config['options']['show_remember_me'] ?? true): ?>
                        <label class="flex items-center">
                            <input type="checkbox" name="remember" class="w-4 h-4 text-blue-600 border-gray-300 dark:border-zinc-600 rounded focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-600 dark:text-zinc-400"><?= $translations['remember_me'] ?? '로그인 유지' ?></span>
                        </label>
                        <?php endif; ?>

                        <?php if ($config['options']['show_forgot_password'] ?? true): ?>
                        <a href="<?= htmlspecialchars($passwordResetUrl ?? '#') ?>" class="text-sm text-blue-600 dark:text-blue-400 hover:underline">
                            <?= $translations['forgot_password'] ?? '비밀번호를 잊으셨나요?' ?>
                        </a>
                        <?php endif; ?>
                    </div>

                    <button type="submit" class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-semibold rounded-lg transition shadow-lg shadow-blue-500/30">
                        <?= $translations['login_button'] ?? '로그인' ?>
                    </button>
                </form>

                <!-- Social Login -->
                <?php if (!empty($socialProviders) && is_array($socialProviders)): ?>
                    <?php include __DIR__ . '/components/social_login.php'; ?>
                <?php endif; ?>

                <!-- Register Link -->
                <p class="text-center text-gray-600 dark:text-zinc-400 mt-8">
                    <?= $translations['no_account'] ?? '아직 회원이 아니신가요?' ?>
                    <a href="<?= htmlspecialchars($registerUrl ?? '#') ?>" class="text-blue-600 dark:text-blue-400 font-medium hover:underline">
                        <?= $translations['register_link'] ?? '회원가입' ?>
                    </a>
                </p>
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
    </script>
</body>
</html>
