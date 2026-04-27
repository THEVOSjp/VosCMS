<?php
/**
 * RezlyX Forgot Password Page
 */

// 스킨 시스템 로드
require_once BASE_PATH . '/rzxlib/Core/Skin/MemberSkinLoader.php';
use RzxLib\Core\Skin\MemberSkinLoader;

// 로고 설정
$siteName = function_exists('get_site_name') ? get_site_name() : ($siteSettings['site_name'] ?? ($config['app_name'] ?? 'RezlyX'));
$logoType = $siteSettings['logo_type'] ?? 'text';
$logoImage = $siteSettings['logo_image'] ?? '';

$pageTitle = $siteName . ' - ' . __('auth.forgot_password.title');

// baseUrl 경로만 추출
if (!empty($config['app_url'])) {
    $parsedUrl = parse_url($config['app_url']);
    $baseUrl = rtrim($parsedUrl['path'] ?? '', '/');
} else {
    $baseUrl = '';
}

// ============================================================================
// 스킨 시스템 적용
// ============================================================================
$memberSkin = $siteSettings['site_member_skin'] ?? $siteSettings['member_skin'] ?? 'default';
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

// Auth 클래스 로드
require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
use RzxLib\Core\Auth\Auth;

// 스킨을 사용하는 경우
if ($useSkin) {
    $errors = [];
    $success = '';
    $step = 'email'; // email, sent

    // Handle form submission
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $email = trim($_POST['email'] ?? '');

        if (empty($email)) {
            $errors[] = __('auth.forgot_password.email_required');
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errors[] = __('auth.forgot_password.email_invalid');
        } else {
            // Auth 클래스를 통해 비밀번호 재설정 이메일 발송
            $result = Auth::sendPasswordResetEmail($email, $config['locale'] ?? 'ko');

            if ($result['success']) {
                $step = 'sent';
                $success = __('auth.forgot_password.sent');

                // 개발 환경: debug_link가 있으면 저장
                $debugLink = $result['debug_link'] ?? null;

                // 디버그: debug_link가 없으면 이메일이 미등록 상태
                if (empty($debugLink) && ($_ENV['APP_DEBUG'] ?? 'false') === 'true') {
                    $errors[] = '[DEV] 해당 이메일로 등록된 회원이 없습니다.';
                }
            } else {
                $errors[] = $result['error'] ?? __('auth.forgot_password.error');
            }
        }
    }

    // 스킨 렌더링 (로고, 언어는 모듈이 자동 처리)
    $skinHtml = $skinLoader->render('password_reset', [
        'errors' => $errors,
        'success' => $success,
        'step' => $step,
        'email' => $email ?? '',
        'debugLink' => $debugLink ?? null,
        'csrfToken' => $_SESSION['csrf_token'] ?? '',
        'loginUrl' => $baseUrl . '/login',
        'baseUrl' => $baseUrl,
    ]);

    // 레이아웃이 적용되면 <main> 콘텐츠 + 스크립트 추출
    if (isset($__layout) && $__layout !== false) {
        $__out = '';
        if (preg_match('/<main[^>]*>(.*)<\/main>/is', $skinHtml, $__mm)) {
            $__out .= '<div class="py-12 px-4">' . $__mm[1] . '</div>';
        }
        if (preg_match_all('/<script\b[^>]*>.*?<\/script>/is', $skinHtml, $__scripts)) {
            $__out .= implode("\n", $__scripts[0]);
        }
        echo $__out;
    } else {
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
    <style>body { font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; }</style>
</head>
<body>
<?= $skinHtml ?>
</body>
</html>
        <?php
    }
    return;
}

// ============================================================================
// 스킨이 없는 경우: 기존 뷰 사용 (아래 코드 계속)
// ============================================================================

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email = trim($_POST['email'] ?? '');

    if (empty($email)) {
        $error = '이메일을 입력해주세요.';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = '올바른 이메일 형식이 아닙니다.';
    } else {
        // Auth 클래스를 통해 비밀번호 재설정 이메일 발송
        $result = Auth::sendPasswordResetEmail($email, $config['locale'] ?? 'ko');

        if ($result['success']) {
            $success = '비밀번호 재설정 링크가 이메일로 발송되었습니다. 이메일을 확인해주세요.';
        } else {
            $error = '처리 중 오류가 발생했습니다. 잠시 후 다시 시도해주세요.';
        }
    }
}
?>

    <!-- Forgot Password Form Section -->
    <div class="flex items-center justify-center py-12 px-4">
        <div class="w-full max-w-md">
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl dark:shadow-zinc-900/50 p-8 transition-colors duration-200">
                <!-- Title -->
                <div class="text-center mb-8">
                    <h1 class="text-2xl font-bold text-gray-900 dark:text-white">비밀번호 찾기</h1>
                    <p class="text-gray-600 dark:text-zinc-400 mt-2">가입한 이메일을 입력하시면<br>비밀번호 재설정 링크를 보내드립니다</p>
                </div>

                <!-- Success Message -->
                <?php if ($success): ?>
                <div class="mb-6 p-4 bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800 rounded-lg">
                    <div class="flex items-center">
                        <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                        </svg>
                        <span class="text-green-700 dark:text-green-300 text-sm"><?php echo htmlspecialchars($success); ?></span>
                    </div>
                </div>
                <?php endif; ?>

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

                <!-- Form -->
                <form method="POST" class="space-y-5">
                    <div>
                        <label for="email" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">이메일</label>
                        <input type="email" name="email" id="email"
                               value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                               class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 transition"
                               placeholder="example@email.com"
                               required>
                    </div>
                    <button type="submit" class="w-full py-3 px-4 bg-blue-600 hover:bg-blue-700 dark:bg-blue-500 dark:hover:bg-blue-600 text-white font-semibold rounded-lg transition shadow-lg shadow-blue-500/30">
                        재설정 링크 보내기
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
            </div>

            <p class="text-center text-gray-500 dark:text-zinc-400 text-sm mt-6">
                <a href="<?php echo $baseUrl; ?>/" class="hover:text-blue-600 dark:hover:text-blue-400">← 홈으로 돌아가기</a>
            </p>
        </div>
    </div>
