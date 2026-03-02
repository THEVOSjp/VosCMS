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
 */

$colors = $colorset ?? $config['colorsets']['default'];
$step = $step ?? 'email';
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
                <?= $translations['password_reset_title'] ?? '비밀번호 찾기' ?>
            </h2>
            <p class="mt-2 text-sm" style="color: var(--skin-secondary);">
                <?php
                switch ($step) {
                    case 'email':
                        echo $translations['password_reset_email_desc'] ?? '가입 시 사용한 이메일을 입력하세요';
                        break;
                    case 'code':
                        echo $translations['password_reset_code_desc'] ?? '이메일로 전송된 인증 코드를 입력하세요';
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
            $steps = ['email' => 1, 'code' => 2, 'reset' => 3, 'complete' => 4];
            $currentStep = $steps[$step] ?? 1;
            for ($i = 1; $i <= 4; $i++):
            ?>
                <div class="flex items-center">
                    <div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-medium transition-colors
                        <?= $i <= $currentStep ? 'text-white' : 'text-gray-400 bg-gray-200' ?>"
                        style="<?= $i <= $currentStep ? 'background-color: var(--skin-primary);' : '' ?>">
                        <?php if ($i < $currentStep): ?>
                            <svg class="w-4 h-4" fill="currentColor" viewBox="0 0 20 20">
                                <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd" />
                            </svg>
                        <?php else: ?>
                            <?= $i ?>
                        <?php endif; ?>
                    </div>
                    <?php if ($i < 4): ?>
                        <div class="w-12 h-0.5 ml-2 <?= $i < $currentStep ? '' : 'bg-gray-200' ?>"
                            style="<?= $i < $currentStep ? 'background-color: var(--skin-primary);' : '' ?>"></div>
                    <?php endif; ?>
                </div>
            <?php endfor; ?>
        </div>

        <!-- 에러 메시지 -->
        <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
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
                <label for="email" class="block text-sm font-medium mb-1" style="color: var(--skin-text);">
                    <?= $translations['email'] ?? '이메일' ?>
                </label>
                <input id="email" name="email" type="email" autocomplete="email" required
                    class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 rounded-lg focus:outline-none focus:ring-2 focus:z-10 sm:text-sm transition-all duration-200"
                    style="color: var(--skin-text); --tw-ring-color: var(--skin-primary);"
                    placeholder="<?= $translations['email_placeholder'] ?? 'example@email.com' ?>">
            </div>

            <button type="submit"
                class="w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all duration-200 hover:opacity-90"
                style="background-color: var(--skin-primary); --tw-ring-color: var(--skin-primary);">
                <?= $translations['send_code'] ?? '인증 코드 발송' ?>
            </button>
        </form>
        <?php endif; ?>

        <!-- Step 2: 인증 코드 입력 -->
        <?php if ($step === 'code'): ?>
        <form class="mt-8 space-y-6" action="" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?? '' ?>">
            <input type="hidden" name="step" value="code">
            <input type="hidden" name="email" value="<?= htmlspecialchars($email ?? '') ?>">

            <div>
                <label for="code" class="block text-sm font-medium mb-1" style="color: var(--skin-text);">
                    <?= $translations['verification_code'] ?? '인증 코드' ?>
                </label>
                <input id="code" name="code" type="text" required maxlength="6" pattern="[0-9]{6}"
                    class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 rounded-lg focus:outline-none focus:ring-2 focus:z-10 sm:text-sm text-center tracking-widest text-2xl font-mono transition-all duration-200"
                    style="color: var(--skin-text); --tw-ring-color: var(--skin-primary);"
                    placeholder="000000">
                <p class="mt-2 text-xs text-center" style="color: var(--skin-secondary);">
                    <?= $translations['code_sent_to'] ?? '코드가 발송되었습니다:' ?> <?= htmlspecialchars($email ?? '') ?>
                </p>
            </div>

            <button type="submit"
                class="w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all duration-200 hover:opacity-90"
                style="background-color: var(--skin-primary); --tw-ring-color: var(--skin-primary);">
                <?= $translations['verify_code'] ?? '코드 확인' ?>
            </button>

            <div class="text-center">
                <button type="button" onclick="resendCode()" class="text-sm hover:underline" style="color: var(--skin-primary);">
                    <?= $translations['resend_code'] ?? '코드 재발송' ?>
                </button>
            </div>
        </form>
        <?php endif; ?>

        <!-- Step 3: 새 비밀번호 입력 -->
        <?php if ($step === 'reset'): ?>
        <form class="mt-8 space-y-6" action="" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?? '' ?>">
            <input type="hidden" name="step" value="reset">
            <input type="hidden" name="token" value="<?= htmlspecialchars($token ?? '') ?>">

            <div class="space-y-4">
                <div>
                    <label for="password" class="block text-sm font-medium mb-1" style="color: var(--skin-text);">
                        <?= $translations['new_password'] ?? '새 비밀번호' ?>
                    </label>
                    <input id="password" name="password" type="password" required
                        class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 rounded-lg focus:outline-none focus:ring-2 focus:z-10 sm:text-sm transition-all duration-200"
                        style="color: var(--skin-text); --tw-ring-color: var(--skin-primary);"
                        placeholder="<?= $translations['new_password_placeholder'] ?? '8자 이상 입력하세요' ?>">
                </div>

                <div>
                    <label for="password_confirm" class="block text-sm font-medium mb-1" style="color: var(--skin-text);">
                        <?= $translations['password_confirm'] ?? '비밀번호 확인' ?>
                    </label>
                    <input id="password_confirm" name="password_confirm" type="password" required
                        class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 rounded-lg focus:outline-none focus:ring-2 focus:z-10 sm:text-sm transition-all duration-200"
                        style="color: var(--skin-text); --tw-ring-color: var(--skin-primary);"
                        placeholder="<?= $translations['password_confirm_placeholder'] ?? '비밀번호를 다시 입력하세요' ?>">
                </div>
            </div>

            <button type="submit"
                class="w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all duration-200 hover:opacity-90"
                style="background-color: var(--skin-primary); --tw-ring-color: var(--skin-primary);">
                <?= $translations['reset_password'] ?? '비밀번호 변경' ?>
            </button>
        </form>
        <?php endif; ?>

        <!-- Step 4: 완료 -->
        <?php if ($step === 'complete'): ?>
        <div class="mt-8 text-center space-y-6">
            <div class="mx-auto w-16 h-16 rounded-full flex items-center justify-center" style="background-color: rgba(16, 185, 129, 0.1);">
                <svg class="w-8 h-8" style="color: var(--skin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                </svg>
            </div>

            <p class="text-lg font-medium" style="color: var(--skin-text);">
                <?= $translations['password_changed'] ?? '비밀번호가 변경되었습니다' ?>
            </p>

            <a href="<?= $loginUrl ?? '#' ?>"
                class="inline-flex justify-center py-3 px-6 border border-transparent text-sm font-medium rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all duration-200 hover:opacity-90"
                style="background-color: var(--skin-primary); --tw-ring-color: var(--skin-primary);">
                <?= $translations['go_to_login'] ?? '로그인하기' ?>
            </a>
        </div>
        <?php endif; ?>

        <!-- 로그인 링크 -->
        <?php if ($step !== 'complete'): ?>
        <div class="text-center">
            <p class="text-sm" style="color: var(--skin-secondary);">
                <a href="<?= $loginUrl ?? '#' ?>" class="font-medium hover:opacity-80 transition-opacity" style="color: var(--skin-primary);">
                    <?= $translations['back_to_login'] ?? '로그인으로 돌아가기' ?>
                </a>
            </p>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>
function resendCode() {
    // 코드 재발송 로직
    console.log('Resend code');
}
</script>
