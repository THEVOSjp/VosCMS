<?php
/**
 * RezlyX - 회원 탈퇴 페이지
 */

require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
use RzxLib\Core\Auth\Auth;

if (!Auth::check()) {
    header('Location: ' . ($config['app_url'] ?? '') . '/login');
    exit;
}

$user = Auth::user();
$baseUrl = $config['app_url'] ?? '';

// 번역 파일에서 배열 직접 로드 (__ 함수는 string만 반환)
$currentLocale = function_exists('current_locale') ? current_locale() : 'ko';
$authLang = include BASE_PATH . '/resources/lang/' . $currentLocale . '/auth.php';
$withdrawLang = $authLang['withdraw'] ?? [];
$pageTitle = ($config['app_name'] ?? 'RezlyX') . ' - ' . __('auth.withdraw.title');
$isLoggedIn = true;
$currentUser = $user;

$error = '';
$success = '';

// POST 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $reason = $_POST['reason'] ?? '';
    $reasonOther = trim($_POST['reason_other'] ?? '');
    $confirm = isset($_POST['confirm']);

    if (!$confirm) {
        $error = __('auth.withdraw.confirm_required');
    } elseif (empty($password)) {
        $error = __('auth.withdraw.wrong_password');
    } else {
        $reasonText = $reason;
        if ($reason === 'other' && $reasonOther) {
            $reasonText = 'other: ' . $reasonOther;
        }

        $result = Auth::deleteAccount($user['id'], $password, $reasonText);

        if ($result['success']) {
            // 탈퇴 성공 - 홈으로 리다이렉트
            $_SESSION['withdraw_success'] = true;
            header('Location: ' . $baseUrl . '/');
            exit;
        } elseif ($result['message'] === 'wrong_password') {
            $error = __('auth.withdraw.wrong_password');
        } else {
            $error = __('auth.withdraw.error');
        }
    }
}

// 프로필 이미지 URL
$profileImgUrl = '';
if (!empty($user['profile_image'])) {
    $profileImgUrl = str_starts_with($user['profile_image'], 'http')
        ? $user['profile_image']
        : $baseUrl . $user['profile_image'];
}

include BASE_PATH . '/resources/views/partials/header.php';
?>

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="lg:flex lg:gap-8">
            <!-- 사이드바 -->
            <?php
            $sidebarActive = 'withdraw';
            include BASE_PATH . '/resources/views/components/mypage-sidebar.php';
            ?>

            <!-- 메인 콘텐츠 -->
            <div class="flex-1">
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-6">
                    <div class="mb-6">
                        <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= __('auth.withdraw.title') ?></h1>
                        <p class="text-gray-500 dark:text-zinc-400 mt-1"><?= __('auth.withdraw.description') ?></p>
                    </div>

                    <?php if ($error): ?>
                    <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg">
                        <span class="text-red-700 dark:text-red-300 text-sm"><?= htmlspecialchars($error) ?></span>
                    </div>
                    <?php endif; ?>

                    <!-- 경고 박스 -->
                    <div class="mb-6 p-5 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-xl">
                        <div class="flex items-start gap-3 mb-3">
                            <svg class="w-6 h-6 text-red-500 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L3.34 16.5c-.77.833.192 2.5 1.732 2.5z"/>
                            </svg>
                            <h3 class="text-base font-semibold text-red-700 dark:text-red-300"><?= __('auth.withdraw.warning_title') ?></h3>
                        </div>
                        <ul class="space-y-2 ml-9">
                            <?php foreach ($withdrawLang['warnings'] ?? [] as $warning): ?>
                            <li class="flex items-start gap-2 text-sm text-red-600 dark:text-red-400">
                                <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zM8.707 7.293a1 1 0 00-1.414 1.414L8.586 10l-1.293 1.293a1 1 0 101.414 1.414L10 11.414l1.293 1.293a1 1 0 001.414-1.414L11.414 10l1.293-1.293a1 1 0 00-1.414-1.414L10 8.586 8.707 7.293z" clip-rule="evenodd"/></svg>
                                <?= $warning ?>
                            </li>
                            <?php endforeach; ?>
                        </ul>
                        <p class="mt-4 ml-9 text-xs text-red-500 dark:text-red-400/80"><?= __('auth.withdraw.retention_notice') ?></p>
                    </div>

                    <form method="POST" class="space-y-6" id="withdrawForm">
                        <!-- 탈퇴 사유 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-2"><?= __('auth.withdraw.reason') ?></label>
                            <select name="reason" id="withdrawReason" class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-300 dark:focus:ring-red-800 focus:border-red-500">
                                <option value=""><?= __('auth.withdraw.reason_placeholder') ?></option>
                                <?php foreach ($withdrawLang['reasons'] ?? [] as $key => $label): ?>
                                <option value="<?= $key ?>" <?= ($_POST['reason'] ?? '') === $key ? 'selected' : '' ?>><?= $label ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <!-- 기타 사유 입력 -->
                        <div id="otherReasonWrap" class="hidden">
                            <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-2"><?= __('auth.withdraw.reason_other') ?></label>
                            <textarea name="reason_other" rows="3" class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-300 dark:focus:ring-red-800 focus:border-red-500 resize-none" placeholder="<?= __('auth.withdraw.reason_other_placeholder') ?>"><?= htmlspecialchars($_POST['reason_other'] ?? '') ?></textarea>
                        </div>

                        <!-- 비밀번호 확인 -->
                        <div>
                            <label class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-2"><?= __('auth.withdraw.password') ?></label>
                            <input type="password" name="password" required autocomplete="current-password"
                                class="w-full px-4 py-3 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-red-300 dark:focus:ring-red-800 focus:border-red-500"
                                placeholder="<?= __('auth.withdraw.password_placeholder') ?>">
                            <p class="mt-1 text-xs text-gray-500 dark:text-zinc-400"><?= __('auth.withdraw.password_hint') ?></p>
                        </div>

                        <!-- 동의 체크 -->
                        <div class="flex items-start gap-3 p-4 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg">
                            <input type="checkbox" name="confirm" id="withdrawConfirm" class="mt-1 w-4 h-4 text-red-600 border-zinc-300 rounded focus:ring-red-500">
                            <label for="withdrawConfirm" class="text-sm text-gray-700 dark:text-zinc-300"><?= __('auth.withdraw.confirm_text') ?></label>
                        </div>

                        <!-- 제출 -->
                        <div class="pt-2">
                            <button type="submit" id="withdrawBtn" disabled
                                class="px-6 py-3 bg-red-600 hover:bg-red-700 disabled:bg-gray-300 dark:disabled:bg-zinc-600 disabled:cursor-not-allowed text-white font-semibold rounded-lg transition shadow-lg shadow-red-500/30 disabled:shadow-none">
                                <?= __('auth.withdraw.submit') ?>
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const reasonSelect = document.getElementById('withdrawReason');
        const otherWrap = document.getElementById('otherReasonWrap');
        const confirmCheck = document.getElementById('withdrawConfirm');
        const submitBtn = document.getElementById('withdrawBtn');
        const form = document.getElementById('withdrawForm');

        // 기타 사유 토글
        reasonSelect.addEventListener('change', function() {
            console.log('[withdraw] reason changed:', this.value);
            otherWrap.classList.toggle('hidden', this.value !== 'other');
        });
        // 초기 상태
        if (reasonSelect.value === 'other') otherWrap.classList.remove('hidden');

        // 동의 체크 → 버튼 활성화
        confirmCheck.addEventListener('change', function() {
            console.log('[withdraw] confirm changed:', this.checked);
            submitBtn.disabled = !this.checked;
        });

        // 폼 제출 확인
        form.addEventListener('submit', function(e) {
            console.log('[withdraw] form submit');
            if (!confirmCheck.checked) {
                e.preventDefault();
                return;
            }
            if (!confirm('<?= __('auth.withdraw.title') ?>: <?= __('auth.withdraw.description') ?>')) {
                e.preventDefault();
                console.log('[withdraw] cancelled by user');
            }
        });
    });
    </script>

<?php
include BASE_PATH . '/resources/views/partials/footer.php';
?>
