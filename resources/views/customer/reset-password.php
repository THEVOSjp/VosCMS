<?php
/**
 * RezlyX Reset Password Page (Token-based)
 */

// 스킨 시스템 로드
require_once BASE_PATH . '/rzxlib/Core/Skin/MemberSkinLoader.php';
require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
use RzxLib\Core\Skin\MemberSkinLoader;
use RzxLib\Core\Auth\Auth;

// 로고 설정
$siteName = $siteSettings['site_name'] ?? ($config['app_name'] ?? 'RezlyX');
$logoType = $siteSettings['logo_type'] ?? 'text';
$logoImage = $siteSettings['logo_image'] ?? '';

$pageTitle = $siteName . ' - ' . __('auth.password_reset.title');

// baseUrl 경로만 추출
if (!empty($config['app_url'])) {
    $parsedUrl = parse_url($config['app_url']);
    $baseUrl = rtrim($parsedUrl['path'] ?? '', '/');
} else {
    $baseUrl = '';
}

// 토큰 가져오기
$token = $_GET['token'] ?? '';

// ============================================================================
// 스킨 시스템 적용
// ============================================================================
$memberSkin = $siteSettings['member_skin'] ?? 'default';
$skinBasePath = BASE_PATH . '/skins/member';
$useSkin = false;

// 스킨이 존재하는지 확인
if (is_dir($skinBasePath . '/' . $memberSkin)) {
    $skinLoader = new MemberSkinLoader($skinBasePath, $memberSkin);
    $skinLoader->setSiteSettings($siteSettings);

    // 해당 스킨에 password_reset.php 템플릿이 있는지 확인
    if ($skinLoader->pageExists('password_reset')) {
        $useSkin = true;
    }
}

$errors = [];
$success = '';
$step = 'reset'; // reset, complete, invalid

// 토큰이 없으면 invalid
if (empty($token)) {
    $step = 'invalid';
    $errors[] = __('auth.password_reset.invalid_token');
} else {
    // 토큰 유효성 검증
    $user = Auth::verifyPasswordResetToken($token);

    if (!$user) {
        $step = 'invalid';
        $errors[] = __('auth.password_reset.invalid_token');
    } else {
        // POST 요청 처리 (비밀번호 변경)
        if ($_SERVER['REQUEST_METHOD'] === 'POST') {
            $password = $_POST['password'] ?? '';
            $passwordConfirm = $_POST['password_confirm'] ?? '';

            // 유효성 검사
            if (empty($password)) {
                $errors[] = __('validation.required', ['attribute' => __('auth.register.password')]);
            } elseif (strlen($password) < 8) {
                $errors[] = __('validation.min.string', ['attribute' => __('auth.register.password'), 'min' => 8]);
            } elseif ($password !== $passwordConfirm) {
                $errors[] = __('validation.confirmed', ['attribute' => __('auth.register.password')]);
            }

            if (empty($errors)) {
                // 비밀번호 재설정 처리
                $result = Auth::resetPassword($token, $password);

                if ($result['success']) {
                    $step = 'complete';
                    $success = __('auth.password_reset.success');
                } else {
                    $errorKey = $result['error'] ?? 'error';
                    $errors[] = __('auth.password_reset.' . $errorKey);
                }
            }
        }
    }
}

// 스킨을 사용하는 경우
if ($useSkin) {
    // 스킨 렌더링 (로고, 언어는 모듈이 자동 처리)
    $skinHtml = $skinLoader->render('password_reset', [
        'errors' => $errors,
        'success' => $success,
        'step' => $step,
        'token' => $token,
        'csrfToken' => $_SESSION['csrf_token'] ?? '',
        'loginUrl' => $baseUrl . '/login',
        'baseUrl' => $baseUrl,
    ]);

    echo $skinHtml;
    exit;
}

// ============================================================================
// 스킨이 없는 경우: 기본 뷰 사용
// ============================================================================
?>
<!DOCTYPE html>
<html lang="<?php echo $config['locale'] ?? 'ko'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <style>
        body { font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; }
    </style>
    <script>
        if (localStorage.getItem('darkMode') === 'true' ||
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-zinc-50 dark:bg-zinc-900 min-h-screen transition-colors duration-200">
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
    </header>

    <!-- Main Content -->
    <main class="flex items-center justify-center min-h-[calc(100vh-4rem)] py-12 px-4">
        <div class="w-full max-w-md">
            <?php if ($step === 'invalid'): ?>
            <!-- Invalid Token -->
            <div class="text-center">
                <div class="w-20 h-20 mx-auto mb-6 bg-red-100 dark:bg-red-900/30 rounded-full flex items-center justify-center">
                    <svg class="w-10 h-10 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">링크가 유효하지 않습니다</h1>
                <p class="text-gray-600 dark:text-zinc-400 mb-8">비밀번호 재설정 링크가 만료되었거나 유효하지 않습니다.<br>다시 시도해주세요.</p>
                <a href="<?php echo $baseUrl; ?>/forgot-password" class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-semibold rounded-lg transition">
                    비밀번호 찾기로 돌아가기
                </a>
            </div>

            <?php elseif ($step === 'complete'): ?>
            <!-- Password Reset Complete -->
            <div class="text-center">
                <div class="w-20 h-20 mx-auto mb-6 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
                    <svg class="w-10 h-10 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/>
                    </svg>
                </div>
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-4">비밀번호가 변경되었습니다</h1>
                <p class="text-gray-600 dark:text-zinc-400 mb-8">새 비밀번호로 로그인해주세요.</p>
                <a href="<?php echo $baseUrl; ?>/login" class="inline-flex items-center px-6 py-3 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-semibold rounded-lg transition">
                    로그인하기
                </a>
            </div>

            <?php else: ?>
            <!-- Reset Password Form -->
            <div class="text-center mb-8">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white">새 비밀번호 설정</h1>
                <p class="text-gray-600 dark:text-zinc-400 mt-2">새로 사용할 비밀번호를 입력해주세요.</p>
            </div>

            <!-- Error Messages -->
            <?php if (!empty($errors)): ?>
            <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg">
                <?php foreach ($errors as $error): ?>
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-red-600 dark:text-red-400 mr-2 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-red-700 dark:text-red-300 text-sm"><?php echo htmlspecialchars($error); ?></span>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <!-- Form -->
            <form method="POST" class="space-y-5">
                <input type="hidden" name="token" value="<?php echo htmlspecialchars($token); ?>">

                <div>
                    <label for="password" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">새 비밀번호</label>
                    <input type="password" name="password" id="password"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                           placeholder="8자 이상 입력"
                           required minlength="8">
                </div>

                <div>
                    <label for="password_confirm" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">비밀번호 확인</label>
                    <input type="password" name="password_confirm" id="password_confirm"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                           placeholder="비밀번호 재입력"
                           required minlength="8">
                </div>

                <button type="submit" class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-semibold rounded-lg transition shadow-lg shadow-blue-500/30">
                    비밀번호 변경
                </button>
            </form>

            <!-- Back to Login -->
            <div class="mt-8 text-center">
                <a href="<?php echo $baseUrl; ?>/login" class="inline-flex items-center text-blue-600 dark:text-blue-400 font-medium hover:underline">
                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                    </svg>
                    로그인으로 돌아가기
                </a>
            </div>
            <?php endif; ?>

            <p class="text-center text-gray-500 dark:text-zinc-400 text-sm mt-6">
                <a href="<?php echo $baseUrl; ?>/" class="hover:text-blue-600 dark:hover:text-blue-400">&larr; 홈으로 돌아가기</a>
            </p>
        </div>
    </main>

    <script>
        const darkModeBtn = document.getElementById('darkModeBtn');
        darkModeBtn.addEventListener('click', () => {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('darkMode', isDark);
        });
    </script>
</body>
</html>
