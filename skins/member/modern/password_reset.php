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

$colors = $colorset ?? $config['colorsets']['default'];
$step = $step ?? 'email';
?>

<style>
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

<!-- Alpine.js -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<script>
    // 다크 모드 초기화
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
        } else {
            document.documentElement.classList.remove('dark');
        }
    })();

    // 다크 모드 토글 함수 (헤더 컴포넌트에서 사용)
    function toggleDarkMode() {
        var html = document.documentElement;
        var isDark = html.classList.toggle('dark');
        localStorage.setItem('darkMode', isDark ? 'true' : 'false');
    }
</script>

<div class="min-h-screen bg-gray-50 dark:bg-zinc-900 transition-colors duration-200">
    <!-- Header (재사용 컴포넌트) -->
    <?php include dirname(__DIR__, 2) . '/default/components/header.php'; ?>

    <!-- Main Content -->
    <main class="flex items-center justify-center min-h-[calc(100vh-4rem)] py-12 px-4">
        <div class="w-full max-w-md">
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
                        <input type="password" name="password" id="password"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                               placeholder="<?= $translations['new_password_placeholder'] ?? '8자 이상 입력하세요' ?>"
                               minlength="8"
                               required>
                        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1"><?= $translations['password_hint'] ?? '영문, 숫자를 포함하여 8자 이상' ?></p>
                    </div>

                    <div>
                        <label for="password_confirm" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                            <?= $translations['password_confirm'] ?? '비밀번호 확인' ?>
                        </label>
                        <input type="password" name="password_confirm" id="password_confirm"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                               placeholder="<?= $translations['password_confirm_placeholder'] ?? '비밀번호를 다시 입력하세요' ?>"
                               minlength="8"
                               required>
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

            <p class="text-center text-gray-500 dark:text-zinc-400 text-sm mt-6">
                <a href="<?= htmlspecialchars($baseUrl ?? '') ?>/" class="hover:text-blue-600 dark:hover:text-blue-400">
                    <?= $translations['back_to_home'] ?? '홈으로 돌아가기' ?>
                </a>
            </p>
        </div>
    </main>
</div>
