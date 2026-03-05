<?php
/**
 * RezlyX Customer Login Page
 */

// 인증 헬퍼 로드
require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
require_once BASE_PATH . '/rzxlib/Core/Skin/MemberSkinLoader.php';
use RzxLib\Core\Auth\Auth;
use RzxLib\Core\Skin\MemberSkinLoader;

// 로고 설정
$siteName = $siteSettings['site_name'] ?? ($config['app_name'] ?? 'RezlyX');
$logoType = $siteSettings['logo_type'] ?? 'text';
$logoImage = $siteSettings['logo_image'] ?? '';

$pageTitle = $siteName . ' - ' . __('auth.login.title');

// baseUrl 경로만 추출
if (!empty($config['app_url'])) {
    $parsedUrl = parse_url($config['app_url']);
    $baseUrl = rtrim($parsedUrl['path'] ?? '', '/');
} else {
    $baseUrl = '';
}

// 이미 로그인된 경우 홈으로 리다이렉트
if (Auth::check()) {
    header('Location: ' . $baseUrl . '/');
    exit;
}

// ============================================================================
// 스킨 시스템 적용
// ============================================================================
$memberSkin = $siteSettings['member_skin'] ?? 'default';
$skinBasePath = BASE_PATH . '/skins/member';
$useSkin = false;

// 스킨이 존재하는지 확인
if (is_dir($skinBasePath . '/' . $memberSkin)) {
    $skinLoader = new MemberSkinLoader($skinBasePath, $memberSkin);

    // 해당 스킨에 login.php 템플릿이 있는지 확인
    if ($skinLoader->pageExists('login')) {
        $useSkin = true;
    }
}

// 스킨을 사용하는 경우: 로그인 처리 후 스킨 렌더링
if ($useSkin) {
    $errors = [];
    $oldInput = [];

    // Handle login form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        $oldInput['email'] = $email;

        if (empty($email) || empty($password)) {
            $errors[] = __('auth.login.required');
        } else {
            $result = Auth::attempt($email, $password, $remember);

            if ($result['success']) {
                $redirect = $_GET['redirect'] ?? $baseUrl . '/';
                header('Location: ' . $redirect);
                exit;
            } else {
                $errors[] = __('auth.login.' . ($result['error'] ?? 'failed'));
            }
        }
    }

    // 스킨에 필요한 소셜 로그인 정보 로드
    $socialProviders = [];
    if (($siteSettings['member_social_login_enabled'] ?? '0') === '1') {
        if (($siteSettings['member_social_google'] ?? '0') === '1') {
            $socialProviders[] = 'google';
        }
        if (($siteSettings['member_social_line'] ?? '0') === '1') {
            $socialProviders[] = 'line';
        }
        if (($siteSettings['member_social_kakao'] ?? '0') === '1') {
            $socialProviders[] = 'kakao';
        }
    }

    // 스킨 렌더링
    $skinHtml = $skinLoader->render('login', [
        'errors' => $errors,
        'oldInput' => $oldInput,
        'csrfToken' => $_SESSION['csrf_token'] ?? '',
        'registerUrl' => $baseUrl . '/register',
        'passwordResetUrl' => $baseUrl . '/forgot-password',
        'socialProviders' => $socialProviders,
        'siteName' => $siteName,
        'baseUrl' => $baseUrl,
    ]);

    // 스킨은 body 내용만 포함하므로 전체 HTML 래퍼 필요
    ?>
<!DOCTYPE html>
<html lang="<?= $config['locale'] ?? 'ko' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <style>
        body { font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; }
    </style>
</head>
<body>
<?= $skinHtml ?>
</body>
</html>
    <?php
    exit;
}
// ============================================================================
// 스킨이 없는 경우: 기존 뷰 사용 (아래 코드 계속)
// ============================================================================

// Handle login form submission
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);

    if (empty($email) || empty($password)) {
        $error = __('auth.login.required');
    } else {
        $result = Auth::attempt($email, $password, $remember);

        if ($result['success']) {
            // 리다이렉트 (의도한 페이지 또는 홈)
            $redirect = $_GET['redirect'] ?? $baseUrl . '/';
            header('Location: ' . $redirect);
            exit;
        } else {
            $error = __('auth.login.' . ($result['error'] ?? 'failed'));
        }
    }
}
?>
<!DOCTYPE html>
<html lang="<?php echo $config['locale'] ?? 'ko'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
    </script>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <style>
        body { font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; }
    </style>
    <script>
        // 다크 모드 초기화
        if (localStorage.getItem('darkMode') === 'true' ||
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-gray-50 dark:bg-zinc-900 min-h-screen transition-colors duration-200">
    <!-- Header -->
    <header class="bg-white dark:bg-zinc-800 shadow-sm sticky top-0 z-50 transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="<?php echo $baseUrl; ?>/" class="flex items-center text-xl font-bold text-blue-600 dark:text-blue-400">
                    <?php if ($logoType === 'image' && $logoImage): ?>
                        <img src="<?php echo $baseUrl . htmlspecialchars($logoImage); ?>" alt="<?php echo htmlspecialchars($siteName); ?>" class="h-10 object-contain">
                    <?php elseif ($logoType === 'image_text' && $logoImage): ?>
                        <img src="<?php echo $baseUrl . htmlspecialchars($logoImage); ?>" alt="" class="h-10 object-contain mr-2">
                        <span><?php echo htmlspecialchars($siteName); ?></span>
                    <?php else: ?>
                        <span><?php echo htmlspecialchars($siteName); ?></span>
                    <?php endif; ?>
                </a>
                <div class="flex items-center space-x-3">
                    <!-- 언어 선택 -->
                    <div class="relative">
                        <button id="langBtn" class="flex items-center space-x-1 px-3 py-2 text-sm font-medium text-gray-600 dark:text-zinc-300 hover:text-blue-600 dark:hover:text-blue-400 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-700">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>
                            </svg>
                            <span id="currentLang"><?= strtoupper(current_locale()) ?></span>
                            <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                            </svg>
                        </button>
                        <div id="langDropdown" class="hidden absolute right-0 mt-2 w-32 bg-white dark:bg-zinc-800 rounded-lg shadow-lg border dark:border-zinc-700 py-1 z-50">
                            <a href="?lang=ko" class="block px-4 py-2 text-sm text-gray-700 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-700">한국어</a>
                            <a href="?lang=en" class="block px-4 py-2 text-sm text-gray-700 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-700">English</a>
                            <a href="?lang=ja" class="block px-4 py-2 text-sm text-gray-700 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-700">日本語</a>
                        </div>
                    </div>
                    <!-- 다크모드 토글 -->
                    <button id="darkModeBtn" class="p-2 text-gray-600 dark:text-zinc-300 hover:text-blue-600 dark:hover:text-blue-400 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-700">
                        <svg id="sunIcon" class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <svg id="moonIcon" class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>
    </header>

    <!-- Login Form Section -->
    <main class="flex items-center justify-center min-h-[calc(100vh-4rem)] py-12 px-4">
        <div class="w-full max-w-md">
            <!-- Login Card -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl dark:shadow-zinc-900/50 p-8 transition-colors duration-200">
                <!-- Title -->
                <div class="text-center mb-8">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= __('auth.login.title') ?></h1>
                    <p class="text-gray-600 dark:text-zinc-400 mt-2"><?= __('auth.login.description') ?></p>
                </div>

                <!-- Error Message -->
                <?php if ($error): ?>
                <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-red-700 dark:text-red-300 text-sm"><?php echo htmlspecialchars($error); ?></span>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Login Form -->
                <form method="POST" class="space-y-5">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1"><?= __('auth.login.email') ?></label>
                        <input type="email" name="email" id="email"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                               placeholder="<?= __('auth.login.email_placeholder') ?>"
                               required>
                    </div>
                    <div>
                        <label for="password" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1"><?= __('auth.login.password') ?></label>
                        <div class="relative">
                            <input type="password" name="password" id="password"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition pr-12"
                                   placeholder="<?= __('auth.login.password_placeholder') ?>"
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
                        <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1"><?= __('auth.register.password_hint') ?></p>
                    </div>
                    <div class="flex items-center justify-between">
                        <label class="flex items-center">
                            <input type="checkbox" name="remember" class="w-4 h-4 text-blue-600 border-gray-300 dark:border-zinc-600 rounded focus:ring-blue-500">
                            <span class="ml-2 text-sm text-gray-600 dark:text-zinc-400"><?= __('auth.login.remember') ?></span>
                        </label>
                        <a href="<?php echo $baseUrl; ?>/forgot-password" class="text-sm text-blue-600 dark:text-blue-400 hover:underline"><?= __('auth.login.forgot') ?></a>
                    </div>
                    <button type="submit" class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-semibold rounded-lg transition shadow-lg shadow-blue-500/30">
                        <?= __('auth.login.submit') ?>
                    </button>
                </form>

                <!-- Divider -->
                <div class="relative my-8">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200 dark:border-zinc-700"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-4 bg-white dark:bg-zinc-800 text-gray-500 dark:text-zinc-400"><?= __('auth.social.or') ?></span>
                    </div>
                </div>

                <!-- Social Login -->
                <div class="space-y-3">
                    <!-- LINE 로그인 -->
                    <button type="button" onclick="loginWithLine()" class="w-full flex items-center justify-center py-3 px-4 bg-[#00B900] hover:bg-[#00a000] text-white rounded-lg transition shadow-sm">
                        <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24" fill="currentColor">
                            <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                        </svg>
                        <span class="font-medium"><?= __('auth.login_with_line') ?></span>
                    </button>
                    <!-- Google 로그인 -->
                    <button type="button" class="w-full flex items-center justify-center py-3 px-4 border border-gray-300 dark:border-zinc-600 rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-700 transition">
                        <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24">
                            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                        </svg>
                        <span class="text-gray-700 dark:text-zinc-300 font-medium"><?= __('auth.login_with_google') ?></span>
                    </button>
                    <!-- 카카오 로그인 -->
                    <button type="button" class="w-full flex items-center justify-center py-3 px-4 bg-[#FEE500] hover:bg-[#fdd800] rounded-lg transition">
                        <svg class="w-5 h-5 mr-3" viewBox="0 0 24 24" fill="#000000">
                            <path d="M12 3C6.477 3 2 6.463 2 10.691c0 2.683 1.803 5.044 4.5 6.365-.15.54-.553 1.973-.638 2.28-.103.374.137.369.29.268.119-.08 1.905-1.27 2.672-1.773.764.114 1.553.169 2.176.169 5.523 0 10-3.463 10-7.309C21 6.463 17.523 3 12 3z"/>
                        </svg>
                        <span class="text-gray-900 font-medium"><?= __('auth.login_with_kakao') ?></span>
                    </button>
                </div>

                <!-- Register Link -->
                <p class="text-center text-gray-600 dark:text-zinc-400 mt-8">
                    <?= __('auth.login.no_account') ?>
                    <a href="<?php echo $baseUrl; ?>/register" class="text-blue-600 dark:text-blue-400 font-medium hover:underline"><?= __('auth.login.register_link') ?></a>
                </p>
            </div>

            <!-- Footer Link -->
            <p class="text-center text-gray-500 dark:text-zinc-400 text-sm mt-6">
                <a href="<?php echo $baseUrl; ?>/" class="hover:text-blue-600 dark:hover:text-blue-400"><?= __('auth.login.back_home') ?></a>
            </p>
        </div>
    </main>

    <script>
        // 언어 드롭다운 토글
        const langBtn = document.getElementById('langBtn');
        const langDropdown = document.getElementById('langDropdown');

        langBtn.addEventListener('click', (e) => {
            e.stopPropagation();
            langDropdown.classList.toggle('hidden');
            console.log('[Login] 언어 드롭다운 토글');
        });

        document.addEventListener('click', () => {
            langDropdown.classList.add('hidden');
        });

        // 다크 모드 토글
        const darkModeBtn = document.getElementById('darkModeBtn');

        darkModeBtn.addEventListener('click', () => {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('darkMode', isDark);
            console.log('[Login] 다크 모드 토글:', isDark);
        });

        // 비밀번호 표시/숨기기 토글
        const togglePassword = document.getElementById('togglePassword');
        const passwordInput = document.getElementById('password');
        const eyeIcon = document.getElementById('eyeIcon');
        const eyeOffIcon = document.getElementById('eyeOffIcon');

        togglePassword.addEventListener('click', () => {
            const type = passwordInput.getAttribute('type') === 'password' ? 'text' : 'password';
            passwordInput.setAttribute('type', type);
            eyeIcon.classList.toggle('hidden');
            eyeOffIcon.classList.toggle('hidden');
            console.log('[Login] 비밀번호 표시 토글');
        });
    </script>
</body>
</html>
