<?php
/**
 * RezlyX Member Skin - Default
 * 비밀번호 찾기 페이지 템플릿
 *
 * 사용 가능한 변수:
 * - $config: 스킨 설정
 * - $colorset: 현재 컬러셋
 * - $translations: 번역 데이터
 * - $step: 현재 단계 (email, code, reset, complete)
 * - $errors: 에러 메시지
 * - $siteName: 사이트 이름
 * - $baseUrl: 기본 URL
 */

$colors = $colorset ?? $config['colorsets']['default'];
$step = $step ?? 'email';
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

    <!-- Password Reset Section -->
    <main class="flex items-center justify-center min-h-[calc(100vh-4rem)] py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-md w-full space-y-8">
            <!-- 로고 및 타이틀 -->
            <div class="text-center">
                <?php if (!empty($config['logo'])): ?>
                    <img class="mx-auto h-12 w-auto" src="<?= htmlspecialchars($config['logo']) ?>" alt="Logo">
                <?php endif; ?>
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900 dark:text-white">
                    <?= $translations['password_reset_title'] ?? '비밀번호 찾기' ?>
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-zinc-400">
                    <?php
                    switch ($step) {
                        case 'email':
                            echo $translations['password_reset_email_desc'] ?? '가입 시 사용한 이메일을 입력하세요';
                            break;
                        case 'sent':
                            echo $translations['password_reset_sent_desc'] ?? '이메일을 확인해주세요';
                            break;
                        case 'reset':
                            echo $translations['password_reset_new_desc'] ?? '새 비밀번호를 입력하세요';
                            break;
                        case 'complete':
                            echo $translations['password_reset_complete_desc'] ?? '비밀번호가 성공적으로 변경되었습니다';
                            break;
                    }
                    ?>
                </p>
            </div>

            <!-- 진행 상태 표시 -->
            <div class="flex items-center justify-center space-x-4">
                <?php
                $steps = ['email' => 1, 'sent' => 2, 'reset' => 3, 'complete' => 4];
                $currentStep = $steps[$step] ?? 1;
                for ($i = 1; $i <= 4; $i++):
                ?>
                    <div class="flex items-center">
                        <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-colors
                            <?= $i <= $currentStep ? 'text-white bg-blue-600 dark:bg-blue-500' : 'text-gray-400 dark:text-zinc-500 bg-gray-200 dark:bg-zinc-700' ?>">
                            <?php if ($i < $currentStep): ?>
                                <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                    <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                                </svg>
                            <?php else: ?>
                                <?= $i ?>
                            <?php endif; ?>
                        </div>
                        <?php if ($i < 4): ?>
                            <div class="w-12 h-0.5 ml-2 <?= $i < $currentStep ? 'bg-blue-600 dark:bg-blue-500' : 'bg-gray-200 dark:bg-zinc-700' ?>"></div>
                        <?php endif; ?>
                    </div>
                <?php endfor; ?>
            </div>

            <!-- 에러 메시지 -->
            <?php if (!empty($errors)): ?>
                <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg">
                    <?php foreach ($errors as $error): ?>
                        <p class="text-sm"><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- Step 1: 이메일 입력 -->
            <?php if ($step === 'email'): ?>
            <form class="mt-8 space-y-6" action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?? '' ?>">
                <input type="hidden" name="step" value="email">

                <div>
                    <label for="email" class="block text-sm font-medium mb-1 text-gray-700 dark:text-zinc-300">
                        <?= $translations['email'] ?? '이메일' ?>
                    </label>
                    <input id="email" name="email" type="email" autocomplete="email" required
                        class="appearance-none relative block w-full px-3 py-3 border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 placeholder-gray-500 dark:placeholder-zinc-400 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-all duration-200"
                        placeholder="<?= $translations['email_placeholder'] ?? 'example@email.com' ?>"
                        value="<?= htmlspecialchars($email ?? '') ?>">
                </div>

                <button type="submit"
                    class="w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                    <?= $translations['send_code'] ?? '인증 메일 발송' ?>
                </button>
            </form>
            <?php endif; ?>

            <!-- Step 2: 이메일 발송 완료 -->
            <?php if ($step === 'sent'): ?>
            <div class="mt-8 text-center space-y-6">
                <div class="mx-auto w-16 h-16 rounded-full flex items-center justify-center bg-blue-100 dark:bg-blue-900/30">
                    <svg class="w-8 h-8 text-blue-600 dark:text-blue-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z" />
                    </svg>
                </div>

                <div>
                    <p class="text-lg font-medium text-gray-900 dark:text-white">
                        <?= $translations['email_sent'] ?? '이메일이 발송되었습니다' ?>
                    </p>
                    <p class="text-sm text-gray-600 dark:text-zinc-400 mt-2">
                        <?= htmlspecialchars($email ?? '') ?>
                    </p>
                    <p class="text-xs text-gray-500 dark:text-zinc-500 mt-2">
                        <?= $translations['check_inbox'] ?? '받은 편지함을 확인해주세요' ?>
                    </p>
                </div>
            </div>
            <?php endif; ?>

            <!-- Step 3: 새 비밀번호 입력 -->
            <?php if ($step === 'reset'): ?>
            <form class="mt-8 space-y-6" action="" method="POST">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?? '' ?>">
                <input type="hidden" name="step" value="reset">
                <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">

                <div class="space-y-4">
                    <div>
                        <label for="password" class="block text-sm font-medium mb-1 text-gray-700 dark:text-zinc-300">
                            <?= $translations['new_password'] ?? '새 비밀번호' ?>
                        </label>
                        <div class="relative">
                            <input id="password" name="password" type="password" required
                                class="appearance-none relative block w-full px-3 py-3 border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 placeholder-gray-500 dark:placeholder-zinc-400 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-all duration-200 pr-12"
                                placeholder="<?= $translations['new_password_placeholder'] ?? '8자 이상 입력하세요' ?>">
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

                    <div>
                        <label for="password_confirm" class="block text-sm font-medium mb-1 text-gray-700 dark:text-zinc-300">
                            <?= $translations['password_confirm'] ?? '비밀번호 확인' ?>
                        </label>
                        <input id="password_confirm" name="password_confirm" type="password" required
                            class="appearance-none relative block w-full px-3 py-3 border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 placeholder-gray-500 dark:placeholder-zinc-400 text-gray-900 dark:text-white rounded-lg focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-blue-500 sm:text-sm transition-all duration-200"
                            placeholder="<?= $translations['password_confirm_placeholder'] ?? '비밀번호를 다시 입력하세요' ?>">
                    </div>
                </div>

                <button type="submit"
                    class="w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                    <?= $translations['reset_password'] ?? '비밀번호 변경' ?>
                </button>
            </form>
            <?php endif; ?>

            <!-- Step 4: 완료 -->
            <?php if ($step === 'complete'): ?>
            <div class="mt-8 text-center space-y-6">
                <div class="mx-auto w-16 h-16 rounded-full flex items-center justify-center bg-green-100 dark:bg-green-900/30">
                    <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                    </svg>
                </div>

                <p class="text-lg font-medium text-gray-900 dark:text-white">
                    <?= $translations['password_changed'] ?? '비밀번호가 변경되었습니다' ?>
                </p>

                <a href="<?= $loginUrl ?? '#' ?>"
                    class="inline-flex justify-center py-3 px-6 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
                    <?= $translations['go_to_login'] ?? '로그인하기' ?>
                </a>
            </div>
            <?php endif; ?>

            <!-- 로그인 링크 -->
            <?php if ($step !== 'complete'): ?>
            <div class="text-center">
                <p class="text-sm text-gray-600 dark:text-zinc-400">
                    <a href="<?= $loginUrl ?? '#' ?>" class="font-medium text-blue-600 dark:text-blue-400 hover:text-blue-500 transition-opacity">
                        <?= $translations['back_to_login'] ?? '로그인으로 돌아가기' ?>
                    </a>
                </p>
            </div>
            <?php endif; ?>

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
