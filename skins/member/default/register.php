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
 * - $socialProviders: 활성화된 소셜 로그인 제공자
 * - $siteName: 사이트 이름
 * - $baseUrl: 기본 URL
 */

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

    <!-- Register Form Section -->
    <main class="flex items-center justify-center min-h-[calc(100vh-4rem)] py-12 px-4 sm:px-6 lg:px-8">
        <div class="max-w-lg w-full space-y-8">
            <!-- 로고 및 타이틀 -->
            <div class="text-center">
                <?php if (!empty($config['logo'])): ?>
                    <img class="mx-auto h-12 w-auto" src="<?= htmlspecialchars($config['logo']) ?>" alt="Logo">
                <?php endif; ?>
                <h2 class="mt-6 text-3xl font-extrabold text-gray-900 dark:text-white">
                    <?= $translations['register_title'] ?? '회원가입' ?>
                </h2>
                <p class="mt-2 text-sm text-gray-600 dark:text-zinc-400">
                    <?= $translations['register_subtitle'] ?? '새 계정을 만드세요' ?>
                </p>
            </div>

            <!-- 에러 메시지 -->
            <?php if (!empty($errors)): ?>
                <div class="bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 text-red-700 dark:text-red-300 px-4 py-3 rounded-lg">
                    <?php foreach ($errors as $field => $error): ?>
                        <p class="text-sm"><?= htmlspecialchars($error) ?></p>
                    <?php endforeach; ?>
                </div>
            <?php endif; ?>

            <!-- 회원가입 폼 -->
            <form class="mt-8 space-y-6" action="" method="POST" enctype="multipart/form-data" onsubmit="return validateTermsAgreement ? validateTermsAgreement() : true">
                <input type="hidden" name="csrf_token" value="<?= $csrfToken ?? '' ?>">

                <?php
                /**
                 * 동적 회원가입 필드 렌더링
                 * $registerFields: 관리자 설정에서 활성화된 필드 목록
                 * 기본값: ['name', 'email', 'password', 'phone']
                 */
                $registerFields = $registerFields ?? ['name', 'email', 'password', 'phone'];
                include dirname(__DIR__, 2) . '/default/components/register_fields.php';
                ?>

                <!-- 약관 동의 (재사용 컴포넌트) -->
                <?php
                // 변수명 호환성: $terms가 있으면 $termsSettings로 매핑
                if (!isset($termsSettings) && isset($terms)) {
                    $termsSettings = $terms;
                }

                // 약관 설정이 있으면 컴포넌트 로드
                if (!empty($termsSettings)) {
                    include dirname(__DIR__, 2) . '/default/components/terms_agreement.php';
                }
                ?>

                <!-- 회원가입 버튼 -->
                <div>
                    <button type="submit" name="action" value="register"
                        class="group relative w-full flex justify-center py-3 px-4 border border-transparent text-sm font-medium rounded-lg text-white bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 transition-all duration-200">
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
            <?php if (!empty($socialProviders) && is_array($socialProviders)): ?>
                <?php include __DIR__ . '/components/social_login.php'; ?>
            <?php endif; ?>

            <!-- 로그인 링크 -->
            <div class="text-center">
                <p class="text-sm text-gray-600 dark:text-zinc-400">
                    <?= $translations['has_account'] ?? '이미 계정이 있으신가요?' ?>
                    <a href="<?= $loginUrl ?? '#' ?>" class="font-medium text-blue-600 dark:text-blue-400 hover:text-blue-500 transition-opacity">
                        <?= $translations['login_link'] ?? '로그인' ?>
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

    <!-- 전화번호 입력 컴포넌트 JS (동적 필드에서 phone이 있을 경우에만 필요) -->
    <?php if (in_array('phone', $registerFields ?? [])): ?>
    <script src="<?= htmlspecialchars($baseUrl ?? '') ?>/assets/js/phone-input.js"></script>
    <?php endif; ?>
</body>
</html>
