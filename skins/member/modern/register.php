<?php
/**
 * RezlyX Member Skin - Modern
 * 회원가입 페이지 템플릿
 *
 * 사용 가능한 변수:
 * - $config: 스킨 설정
 * - $colorset: 현재 컬러셋
 * - $translations: 번역 데이터
 * - $errors: 폼 에러 메시지 배열
 * - $oldInput: 이전 입력값
 * - $success: 회원가입 성공 여부
 * - $csrfToken: CSRF 토큰
 * - $loginUrl: 로그인 URL
 * - $termsSettings: 약관 설정 배열
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
    <title><?= $translations['register_title'] ?? '회원가입' ?> - <?= htmlspecialchars($siteName ?? 'RezlyX') ?></title>

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
    $headerPath = dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'header.php';
    if (file_exists($headerPath)) {
        include $headerPath;
    } else {
        echo '<!-- Header not found: ' . htmlspecialchars($headerPath) . ' -->';
    }
    ?>

    <!-- Main Content -->
    <main class="flex items-center justify-center min-h-[calc(100vh-4rem)] py-12 px-4">
        <div class="w-full max-w-lg">
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl dark:shadow-zinc-900/50 p-8 transition-colors duration-200">
                <!-- Title -->
                <div class="text-center mb-8">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= $translations['register_title'] ?? '회원가입' ?></h1>
                    <p class="text-gray-600 dark:text-zinc-400 mt-2"><?= $translations['register_subtitle'] ?? '새 계정을 만드세요' ?></p>
                </div>

                <?php if ($success ?? false): ?>
                <!-- Success Message -->
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-green-700 dark:text-green-300 text-sm"><?= $translations['register_success'] ?? '회원가입이 완료되었습니다!' ?></span>
                    </div>
                </div>
                <div class="text-center">
                    <a href="<?= htmlspecialchars($loginUrl ?? '/login') ?>" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
                        <?= $translations['go_to_login'] ?? '로그인하기' ?>
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </a>
                </div>
                <?php else: ?>

                <!-- Error Messages -->
                <?php if (!empty($errors)): ?>
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg">
                    <?php foreach ($errors as $error): ?>
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-red-700 dark:text-red-300 text-sm"><?= htmlspecialchars($error) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>

                <!-- Register Form -->
                <form method="POST" class="space-y-5" onsubmit="return validateTermsAgreement()">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">

                    <!-- 이름 -->
                    <div>
                        <label for="name" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                            <?= $translations['name'] ?? '이름' ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="text" name="name" id="name"
                               value="<?= htmlspecialchars($oldInput['name'] ?? '') ?>"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                               placeholder="<?= $translations['name_placeholder'] ?? '홍길동' ?>"
                               required>
                    </div>

                    <!-- 이메일 -->
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                            <?= $translations['email'] ?? '이메일' ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="email" name="email" id="email"
                               value="<?= htmlspecialchars($oldInput['email'] ?? '') ?>"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                               placeholder="<?= $translations['email_placeholder'] ?? 'example@email.com' ?>"
                               required>
                    </div>

                    <!-- 전화번호 (국제전화번호 컴포넌트) -->
                    <?php
                    $phoneInputConfig = [
                        'name' => 'phone',
                        'id' => 'phone',
                        'label' => $translations['phone'] ?? __('auth.register.phone'),
                        'value' => $oldInput['phone'] ?? '',
                        'country_code' => $oldInput['phone_country'] ?? '+82',
                        'phone_number' => $oldInput['phone_number'] ?? '',
                        'required' => false,
                        'hint' => $translations['phone_hint'] ?? '',
                        'placeholder' => $translations['phone_placeholder'] ?? '010-1234-5678',
                        'show_label' => true,
                    ];
                    $phoneComponentPath = defined('BASE_PATH')
                        ? BASE_PATH . '/resources/views/components/phone-input.php'
                        : dirname(__DIR__, 3) . '/resources/views/components/phone-input.php';
                    if (file_exists($phoneComponentPath)) {
                        include $phoneComponentPath;
                    }
                    ?>

                    <!-- 비밀번호 -->
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                            <?= $translations['password'] ?? '비밀번호' ?> <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <input type="password" name="password" id="password"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition pr-12"
                                   placeholder="<?= $translations['password_placeholder'] ?? '8자 이상 입력하세요' ?>"
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

                    <!-- 비밀번호 확인 -->
                    <div>
                        <label for="password_confirm" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                            <?= $translations['password_confirm'] ?? '비밀번호 확인' ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="password" name="password_confirm" id="password_confirm"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                               placeholder="<?= $translations['password_confirm_placeholder'] ?? '비밀번호를 다시 입력하세요' ?>"
                               minlength="8"
                               required>
                    </div>

                    <!-- 약관 동의 (Modern 스킨 전용 컴포넌트) -->
                    <?php
                    // 변수명 호환성: $terms가 있으면 $termsSettings로 매핑
                    if (!isset($termsSettings) && isset($terms)) {
                        $termsSettings = $terms;
                    }

                    // 약관 설정이 있으면 Modern 스킨 전용 컴포넌트 로드
                    if (!empty($termsSettings)) {
                        $modernTermsPath = __DIR__ . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'terms_agreement.php';
                        if (file_exists($modernTermsPath)) {
                            include $modernTermsPath;
                        } else {
                            // 폴백: 기본 컴포넌트 사용
                            include dirname(__DIR__, 2) . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'terms_agreement.php';
                        }
                    }
                    ?>

                    <button type="submit" class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-semibold rounded-lg transition shadow-lg shadow-blue-500/30 mt-4">
                        <?= $translations['register_button'] ?? '회원가입' ?>
                    </button>
                </form>

                <!-- Social Login -->
                <?php if (!empty($socialProviders) && is_array($socialProviders)): ?>
                    <?php include dirname(__DIR__, 2) . '/default/components/social_login.php'; ?>
                <?php endif; ?>

                <!-- Login Link -->
                <p class="text-center text-gray-600 dark:text-zinc-400 mt-6">
                    <?= $translations['has_account'] ?? '이미 계정이 있으신가요?' ?>
                    <a href="<?= htmlspecialchars($loginUrl ?? '/login') ?>" class="text-blue-600 dark:text-blue-400 font-medium hover:underline">
                        <?= $translations['login_link'] ?? '로그인' ?>
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

        // 비밀번호 확인 유효성 검사
        var passwordConfirm = document.getElementById('password_confirm');
        if (passwordConfirm && passwordInput) {
            passwordConfirm.addEventListener('input', function() {
                if (this.value !== passwordInput.value) {
                    this.setCustomValidity('<?= $translations['password_mismatch'] ?? '비밀번호가 일치하지 않습니다.' ?>');
                } else {
                    this.setCustomValidity('');
                }
            });
        }
    </script>

    <!-- 전화번호 입력 컴포넌트 JS -->
    <script src="<?= htmlspecialchars($baseUrl ?? '') ?>/assets/js/phone-input.js"></script>
</body>
</html>
