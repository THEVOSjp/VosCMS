<?php
/**
 * RezlyX Member Skin - Default
 * 회원가입 페이지 템플릿
 *
 * 사용 가능한 변수:
 * - $config: 스킨 설정
 * - $colorset: 현재 컬러셋
 * - $translations: 번역 데이터
 * - $errors: 폼 에러 메시지
 * - $oldInput: 이전 입력값
 * - $terms: 약관 데이터
 * - $registerFields: 표시할 필드 목록
 */

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
    <div class="max-w-lg w-full space-y-8">
        <!-- 로고 및 타이틀 -->
        <div class="text-center">
            <?php if (!empty($config['logo'])): ?>
                <img class="mx-auto h-12 w-auto" src="<?= htmlspecialchars($config['logo']) ?>" alt="Logo">
            <?php endif; ?>
            <h2 class="mt-6 text-3xl font-extrabold" style="color: var(--skin-text);">
                <?= $translations['register_title'] ?? '회원가입' ?>
            </h2>
            <p class="mt-2 text-sm" style="color: var(--skin-secondary);">
                <?= $translations['register_subtitle'] ?? '새 계정을 만드세요' ?>
            </p>
        </div>

        <!-- 에러 메시지 -->
        <?php if (!empty($errors)): ?>
            <div class="bg-red-50 border border-red-200 text-red-700 px-4 py-3 rounded-lg">
                <?php foreach ($errors as $field => $error): ?>
                    <p class="text-sm"><?= htmlspecialchars($error) ?></p>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>

        <!-- 회원가입 폼 -->
        <form class="mt-8 space-y-6" action="" method="POST">
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?? '' ?>">

            <div class="rounded-md shadow-sm space-y-4">
                <!-- 이름 -->
                <div>
                    <label for="name" class="block text-sm font-medium mb-1" style="color: var(--skin-text);">
                        <?= $translations['name'] ?? '이름' ?> <span class="text-red-500">*</span>
                    </label>
                    <input id="name" name="name" type="text" required
                        class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 rounded-lg focus:outline-none focus:ring-2 focus:z-10 sm:text-sm transition-all duration-200"
                        style="color: var(--skin-text); --tw-ring-color: var(--skin-primary);"
                        placeholder="<?= $translations['name_placeholder'] ?? '홍길동' ?>"
                        value="<?= htmlspecialchars($oldInput['name'] ?? '') ?>">
                </div>

                <!-- 이메일 -->
                <div>
                    <label for="email" class="block text-sm font-medium mb-1" style="color: var(--skin-text);">
                        <?= $translations['email'] ?? '이메일' ?> <span class="text-red-500">*</span>
                    </label>
                    <input id="email" name="email" type="email" autocomplete="email" required
                        class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 rounded-lg focus:outline-none focus:ring-2 focus:z-10 sm:text-sm transition-all duration-200"
                        style="color: var(--skin-text); --tw-ring-color: var(--skin-primary);"
                        placeholder="<?= $translations['email_placeholder'] ?? 'example@email.com' ?>"
                        value="<?= htmlspecialchars($oldInput['email'] ?? '') ?>">
                </div>

                <!-- 비밀번호 -->
                <div>
                    <label for="password" class="block text-sm font-medium mb-1" style="color: var(--skin-text);">
                        <?= $translations['password'] ?? '비밀번호' ?> <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <input id="password" name="password" type="password" required
                            class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 rounded-lg focus:outline-none focus:ring-2 focus:z-10 sm:text-sm transition-all duration-200"
                            style="color: var(--skin-text); --tw-ring-color: var(--skin-primary);"
                            placeholder="<?= $translations['password_placeholder'] ?? '8자 이상 입력하세요' ?>">
                        <button type="button" onclick="togglePassword('password')"
                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                            <svg class="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z" />
                            </svg>
                        </button>
                    </div>
                    <p class="mt-1 text-xs" style="color: var(--skin-secondary);">
                        <?= $translations['password_hint'] ?? '영문, 숫자를 포함하여 8자 이상' ?>
                    </p>
                </div>

                <!-- 비밀번호 확인 -->
                <div>
                    <label for="password_confirm" class="block text-sm font-medium mb-1" style="color: var(--skin-text);">
                        <?= $translations['password_confirm'] ?? '비밀번호 확인' ?> <span class="text-red-500">*</span>
                    </label>
                    <input id="password_confirm" name="password_confirm" type="password" required
                        class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 rounded-lg focus:outline-none focus:ring-2 focus:z-10 sm:text-sm transition-all duration-200"
                        style="color: var(--skin-text); --tw-ring-color: var(--skin-primary);"
                        placeholder="<?= $translations['password_confirm_placeholder'] ?? '비밀번호를 다시 입력하세요' ?>">
                </div>

                <!-- 전화번호 (선택) -->
                <div>
                    <label for="phone" class="block text-sm font-medium mb-1" style="color: var(--skin-text);">
                        <?= $translations['phone'] ?? '전화번호' ?>
                    </label>
                    <input id="phone" name="phone" type="tel"
                        class="appearance-none relative block w-full px-3 py-3 border border-gray-300 placeholder-gray-500 rounded-lg focus:outline-none focus:ring-2 focus:z-10 sm:text-sm transition-all duration-200"
                        style="color: var(--skin-text); --tw-ring-color: var(--skin-primary);"
                        placeholder="<?= $translations['phone_placeholder'] ?? '010-0000-0000' ?>"
                        value="<?= htmlspecialchars($oldInput['phone'] ?? '') ?>">
                </div>
            </div>

            <!-- 약관 동의 -->
            <?php if (!empty($terms)): ?>
            <div class="space-y-3 p-4 bg-gray-50 rounded-lg">
                <p class="text-sm font-medium" style="color: var(--skin-text);">
                    <?= $translations['terms_agreement'] ?? '약관 동의' ?>
                </p>

                <!-- 전체 동의 -->
                <div class="flex items-center pb-2 border-b border-gray-200">
                    <input id="agree_all" type="checkbox" onchange="toggleAllTerms(this)"
                        class="h-4 w-4 rounded border-gray-300 focus:ring-2"
                        style="color: var(--skin-primary); --tw-ring-color: var(--skin-primary);">
                    <label for="agree_all" class="ml-2 block text-sm font-medium" style="color: var(--skin-text);">
                        <?= $translations['agree_all'] ?? '전체 동의' ?>
                    </label>
                </div>

                <?php foreach ($terms as $i => $term): ?>
                    <?php if (!empty($term['title']) && $term['consent'] !== 'disabled'): ?>
                    <div class="flex items-start">
                        <input id="term_<?= $i ?>" name="terms[<?= $i ?>]" type="checkbox"
                            class="term-checkbox h-4 w-4 mt-0.5 rounded border-gray-300 focus:ring-2"
                            style="color: var(--skin-primary); --tw-ring-color: var(--skin-primary);"
                            <?= $term['consent'] === 'required' ? 'required' : '' ?>>
                        <label for="term_<?= $i ?>" class="ml-2 block text-sm" style="color: var(--skin-text);">
                            <?= htmlspecialchars($term['title']) ?>
                            <?php if ($term['consent'] === 'required'): ?>
                                <span class="text-red-500">(필수)</span>
                            <?php else: ?>
                                <span style="color: var(--skin-secondary);">(선택)</span>
                            <?php endif; ?>
                        </label>
                        <button type="button" onclick="showTermDetail(<?= $i ?>)"
                            class="ml-auto text-xs hover:underline" style="color: var(--skin-primary);">
                            보기
                        </button>
                    </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- 회원가입 버튼 -->
            <div>
                <button type="submit" name="action" value="register"
                    class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white focus:outline-none focus:ring-2 focus:ring-offset-2 transition-all duration-200 hover:opacity-90"
                    style="background-color: var(--skin-primary); --tw-ring-color: var(--skin-primary);">
                    <span class="absolute left-0 inset-y-0 flex items-center pl-3">
                        <svg class="h-5 w-5 text-white opacity-70" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z" />
                        </svg>
                    </span>
                    <?= $translations['register_button'] ?? '회원가입' ?>
                </button>
            </div>
        </form>

        <!-- 소셜 로그인 -->
        <?php if ($config['options']['show_social_login'] ?? true): ?>
            <?php include __DIR__ . '/components/social_login.php'; ?>
        <?php endif; ?>

        <!-- 로그인 링크 -->
        <div class="text-center">
            <p class="text-sm" style="color: var(--skin-secondary);">
                <?= $translations['has_account'] ?? '이미 계정이 있으신가요?' ?>
                <a href="<?= $loginUrl ?? '#' ?>" class="font-medium hover:opacity-80 transition-opacity" style="color: var(--skin-primary);">
                    <?= $translations['login_link'] ?? '로그인' ?>
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

function toggleAllTerms(checkbox) {
    document.querySelectorAll('.term-checkbox').forEach(cb => {
        cb.checked = checkbox.checked;
    });
}

function showTermDetail(termIndex) {
    // 약관 상세 모달 표시 로직
    console.log('Show term detail:', termIndex);
}
</script>
