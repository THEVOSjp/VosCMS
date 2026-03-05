<?php
/**
 * RezlyX Member Skin - Default
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
            --skin-secondary: <?= $colors['secondary'] ?>;
            --skin-accent: <?= $colors['accent'] ?>;
            --skin-background: <?= $colors['background'] ?>;
            --skin-text: <?= $colors['text'] ?>;
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
    <?php include dirname(__DIR__, 2) . '/default/components/header.php'; ?>

    <!-- Login Form Section -->
    <main class="flex items-center justify-center min-h-[calc(100vh-4rem)] py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- 로고 및 타이틀 -->
            <div class="text-center">
                <?php if (!empty($config['logo'])): ?>
                    <img class="mx-auto h-12 w-auto" src="<?= htmlspecialchars($config['logo']) ?>" alt="Logo">
                <?php endif; ?>
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900 dark:text-white">
                    <?= $translations['login_title'] ?? '로그인' ?>
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-zinc-400">
                    <?= $translations['login_subtitle'] ?? '계정에 로그인하세요' ?>
                </p>
            </div>

            <!-- 에러 메시지 -->
            <?php if (!empty($errors)): ?>
                <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg">
                    <?php foreach ($errors as $error): ?>
                        <p class="text-sm"><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- 로그인 폼 -->
            <form class="mt-8 space-y-6" action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?? '' ?>">

                <div class="rounded-md shadow-sm space-y-4">
                    <!-- 이메일/아이디 입력 -->
                    <div>
                        <label for="email" class="block text-sm font-medium mb-1 text-gray-700 dark:text-zinc-300">
                            <?= $translations['email'] ?? '이메일' ?>
                        </label>
                        <input id="email" name="email" type="email" autocomplete="email" required
                            class="appearance-none relative block w-full px-3 py-3 border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 placeholder-gray-500 dark:placeholder-zinc-400 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-all duration-200"
                            placeholder="<?= $translations['email_placeholder'] ?? 'example@email.com' ?>"
                            value="<?= htmlspecialchars($oldInput['email'] ?? '') ?>">
                    </div>

                    <!-- 비밀번호 입력 -->
                    <div>
                        <label for="password" class="block text-sm font-medium mb-1 text-gray-700 dark:text-zinc-300">
                            <?= $translations['password'] ?? '비밀번호' ?>
                        </label>
                        <div class="relative">
                            <input id="password" name="password" type="password" autocomplete="current-password" required
                                class="appearance-none relative block w-full px-3 py-3 border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 placeholder-gray-500 dark:placeholder-zinc-400 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-all duration-200 pr-12"
                                placeholder="<?= $translations['password_placeholder'] ?? '비밀번호를 입력하세요' ?>">
                            <button type="button" id="togglePassword"
                                class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 dark:text-zinc-400 hover:text-gray-600 dark:hover:text-zinc-200">
                                <svg id="eyeIcon" class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                                </svg>
                                <svg id="eyeOffIcon" class="h-5 w-5 hidden" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13.875 18.825A10.05 10.05 0 0112 19c-4.478 0-8.268-2.943-9.543-7a9.97 9.97 0 011.563-3.029m5.858.908a3 3 0 114.243 4.243M9.878 9.878l4.242 4.242M9.88 9.88l-3.29-3.29m7.532 7.532l3.29 3.29M3 3l3.59 3.59m0 0A9.953 9.953 0 0112 5c4.478 0 8.268 2.943 9.543 7a10.025 10.025 0 01-4.132 5.411m0 0L21 21"/>
                                </svg>
                            </button>
                        </div>
                    </div>
                </div>

                <!-- 로그인 유지 & 비밀번호 찾기 -->
                <div class="flex items-center justify-between">
                    <?php if ($config['options']['show_remember_me'] ?? true): ?>
                    <div class="flex items-center">
                        <input id="remember_me" name="remember_me" type="checkbox"
                            class="h-4 w-4 rounded border-gray-300 dark:border-zinc-600 text-blue-600 focus:ring-2 focus:ring-blue-500">
                        <label for="remember_me" class="ml-2 block text-sm text-gray-700 dark:text-zinc-300">
                            <?= $translations['remember_me'] ?? '로그인 유지' ?>
                        </label>
                    </div>
                    <?php endif; ?>

                    <?php if ($config['options']['show_forgot_password'] ?? true): ?>
                    <div class="text-sm">
                        <a href="<?= $passwordResetUrl ?? '#' ?>" class="font-medium text-blue-600 dark:text-blue-400 hover:text-blue-500 transition-opacity">
                            <?= $translations['forgot_password'] ?? '비밀번호를 잊으셨나요?' ?>
                        </a>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- 로그인 버튼 -->
                <div>
                    <button type="submit" name="action" value="login"
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                        <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                            <svg class="h-5 w-5 text-white opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1" />
                            </svg>
                        </span>
                        <?= $translations['login_button'] ?? '로그인' ?>
                    </button>
                </div>
            </form>

            <!-- 소셜 로그인 -->
            <?php if (!empty($socialProviders) && is_array($socialProviders)): ?>
                <?php include __DIR__ . '/components/social_login.php'; ?>
            <?php endif; ?>

            <!-- 회원가입 링크 -->
            <div class="text-center">
                <p class="text-sm text-gray-600 dark:text-zinc-400">
                    <?= $translations['no_account'] ?? '아직 회원이 아니신가요?' ?>
                    <a href="<?= $registerUrl ?? '#' ?>" class="font-medium text-blue-600 dark:text-blue-400 hover:text-blue-500 transition-opacity">
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
