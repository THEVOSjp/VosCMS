<?php
/**
 * RezlyX Member Skin - Default
 * 로그인 페이지 템플릿
 *
 * 사용 가능한 변수:
 * - $config: 스킨 설정
 * - $colorset: 현재 컬러셋
 * - $translations: 번역 데이터
 * - $errors: 폼 에러 메시지
 * - $oldInput: 이전 입력값
 */

// 컬러셋 CSS 변수 적용
$colors = $colorset ?? $config['colorsets']['default'];
?>

<style>
:root {
    --skin-primary: <?= $colors['primary'] ?>;
    --skin-secondary: <?= $colors['secondary'] ?>;
    --skin-accent: <?= $colors['accent'] ?>;
    --skin-background: <?= $colors['background'] ?>;
    --skin-text: <?= $colors['text'] ?>;
}
</style>

<div class="min-h-screen flex items-center justify-center py-12 px-4 sm:px-6 lg:px-8" style="background-color: var(--skin-background);">
    <div class="max-w-md w-full space-y-8">
        <!-- 로고 및 타이틀 -->
        <div class="text-center">
            <?php if (!empty($config['logo'])): ?>
                <img class="mx-auto h-12 w-auto" src="<?= htmlspecialchars($config['logo']) ?>" alt="Logo">
            <?php endif; ?>
            <h2 class="mt-6 text-3xl font-extrabold" style="color: var(--skin-text);">
                <?= $translations['login_title'] ?? '로그인' ?>
            </h2>
            <p class="mt-2 text-sm" style="color: var(--skin-secondary);">
                <?= $translations['login_subtitle'] ?? '계정에 로그인하세요' ?>
            </p>
        </div>

        <!-- 에러 메시지 -->
        <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
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
                    <label for="email" class="block text-sm font-medium mb-1" style="color: var(--skin-text);">
                        <?= $translations['email'] ?? '이메일' ?>
                    </label>
                    <input id="email" name="email" type="email" autocomplete="email" required
                        class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 rounded-lg focus:outline-none focus:ring-2 focus:z-10 sm:text-sm transition-all duration-200"
                        style="color: var(--skin-text); --tw-ring-color: var(--skin-primary);"
                        placeholder="<?= $translations['email_placeholder'] ?? 'example@email.com' ?>"
                        value="<?= htmlspecialchars($oldInput['email'] ?? '') ?>">
                </div>

                <!-- 비밀번호 입력 -->
                <div>
                    <label for="password" class="block text-sm font-medium mb-1" style="color: var(--skin-text);">
                        <?= $translations['password'] ?? '비밀번호' ?>
                    </label>
                    <div class="relative">
                        <input id="password" name="password" type="password" autocomplete="current-password" required
                            class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 rounded-lg focus:outline-none focus:ring-2 focus:z-10 sm:text-sm transition-all duration-200"
                            style="color: var(--skin-text); --tw-ring-color: var(--skin-primary);"
                            placeholder="<?= $translations['password_placeholder'] ?? '비밀번호를 입력하세요' ?>">
                        <button type="button" onclick="togglePassword('password')"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
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
                        class="h-4 w-4 rounded border-gray-300 focus:ring-2"
                        style="color: var(--skin-primary); --tw-ring-color: var(--skin-primary);">
                    <label for="remember_me" class="ml-2 block text-sm" style="color: var(--skin-text);">
                        <?= $translations['remember_me'] ?? '로그인 유지' ?>
                    </label>
                </div>
                <?php endif; ?>

                <?php if ($config['options']['show_forgot_password'] ?? true): ?>
                <div class="text-sm">
                    <a href="<?= $passwordResetUrl ?? '#' ?>" class="font-medium hover:opacity-80 transition-opacity" style="color: var(--skin-primary);">
                        <?= $translations['forgot_password'] ?? '비밀번호를 잊으셨나요?' ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>

            <!-- 로그인 버튼 -->
            <div>
                <button type="submit" name="action" value="login"
                    class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all duration-200 hover:opacity-90"
                    style="background-color: var(--skin-primary); --tw-ring-color: var(--skin-primary);">
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
        <?php if ($config['options']['show_social_login'] ?? true): ?>
            <?php include __DIR__ . '/components/social_login.php'; ?>
        <?php endif; ?>

        <!-- 회원가입 링크 -->
        <div class="text-center">
            <p class="text-sm" style="color: var(--skin-secondary);">
                <?= $translations['no_account'] ?? '아직 회원이 아니신가요?' ?>
                <a href="<?= $registerUrl ?? '#' ?>" class="font-medium hover:opacity-80 transition-opacity" style="color: var(--skin-primary);">
                    <?= $translations['register_link'] ?? '회원가입' ?>
                </a>
            </p>
        </div>
    </div>
</div>

<script>
function togglePassword(inputId) {
    const input = document.getElementById(inputId);
    input.type = input.type === 'password' ? 'text' : 'password';
}
</script>
