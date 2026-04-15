<?php
/**
 * RezlyX Member Skin - Default
 * 소셜 로그인 컴포넌트
 *
 * 사용 가능한 변수:
 * - $socialProviders: 활성화된 소셜 로그인 제공자 목록
 * - $translations: 번역 데이터
 * - $baseUrl: 기본 URL
 */

$providers = $socialProviders ?? [];

// 소셜 로그인이 활성화되어 있는지 확인
if (empty($providers) || !is_array($providers)) return;
?>

<!-- Divider -->
<div class="relative my-8">
    <div class="absolute inset-0 flex items-center">
        <div class="w-full border-t border-gray-200 dark:border-zinc-700"></div>
    </div>
    <div class="relative flex justify-center text-sm">
        <span class="px-4 bg-white dark:bg-zinc-800 text-gray-500 dark:text-zinc-400">
            <?= __('auth.or_continue_with') ?>
        </span>
    </div>
</div>

<!-- Social Login Buttons (가로 균등 배열, 로그인 버튼과 동일한 높이/스타일) -->
<div class="flex gap-3">
    <?php if (in_array('google', $providers)): ?>
    <button type="button" onclick="location.href='<?= htmlspecialchars($baseUrl ?? '') ?>/auth/google'"
            class="flex-1 flex items-center justify-center py-3 border border-gray-300 dark:border-zinc-600 rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-700 bg-white dark:bg-zinc-800 transition-all duration-200"
            title="<?= __('auth.login_with_google') ?>">
        <svg class="w-5 h-5" viewBox="0 0 24 24">
            <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
            <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
            <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
            <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
        </svg>
    </button>
    <?php endif; ?>

    <?php if (in_array('kakao', $providers)): ?>
    <button type="button" onclick="location.href='<?= htmlspecialchars($baseUrl ?? '') ?>/auth/kakao'"
            class="flex-1 flex items-center justify-center py-3 bg-[#FEE500] hover:bg-[#fdd800] rounded-lg transition-all duration-200"
            title="<?= __('auth.login_with_kakao') ?>">
        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="#191919">
            <path d="M12 3C6.477 3 2 6.463 2 10.691c0 2.683 1.803 5.044 4.5 6.365-.15.54-.553 1.973-.638 2.28-.103.374.137.369.29.268.119-.08 1.905-1.27 2.672-1.773.764.114 1.553.169 2.176.169 5.523 0 10-3.463 10-7.309C21 6.463 17.523 3 12 3z"/>
        </svg>
    </button>
    <?php endif; ?>

    <?php if (in_array('line', $providers)): ?>
    <button type="button" onclick="location.href='<?= htmlspecialchars($baseUrl ?? '') ?>/auth/line'"
            class="flex-1 flex items-center justify-center py-3 bg-[#00B900] hover:bg-[#00a000] text-white rounded-lg transition-all duration-200"
            title="<?= __('auth.login_with_line') ?>">
        <svg class="w-5 h-5" viewBox="0 0 24 24" fill="currentColor">
            <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63h2.386c.349 0 .63.285.63.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.627-.63.349 0 .631.285.631.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.349 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.281.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
        </svg>
    </button>
    <?php endif; ?>

    <?php if (in_array('naver', $providers)): ?>
    <button type="button" onclick="location.href='<?= htmlspecialchars($baseUrl ?? '') ?>/auth/naver'"
            class="flex-1 flex items-center justify-center py-3 bg-[#03C75A] hover:bg-[#02B350] text-white rounded-lg transition-all duration-200"
            title="<?= __('auth.login_with_naver') ?>">
        <span class="font-bold text-lg">N</span>
    </button>
    <?php endif; ?>

    <?php if (in_array('apple', $providers)): ?>
    <button type="button" onclick="location.href='<?= htmlspecialchars($baseUrl ?? '') ?>/auth/apple'"
            class="flex-1 flex items-center justify-center py-3 bg-black hover:bg-gray-800 text-white rounded-lg transition-all duration-200"
            title="<?= __('auth.login_with_apple') ?>">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
            <path d="M17.05 20.28c-.98.95-2.05.8-3.08.35-1.09-.46-2.09-.48-3.24 0-1.44.62-2.2.44-3.06-.35C2.79 15.25 3.51 7.59 9.05 7.31c1.35.07 2.29.74 3.08.8 1.18-.24 2.31-.93 3.57-.84 1.51.12 2.65.72 3.4 1.8-3.12 1.87-2.38 5.98.48 7.13-.57 1.5-1.31 2.99-2.54 4.09l.01-.01zM12.03 7.25c-.15-2.23 1.66-4.07 3.74-4.25.29 2.58-2.34 4.5-3.74 4.25z"/>
        </svg>
    </button>
    <?php endif; ?>

    <?php if (in_array('facebook', $providers)): ?>
    <button type="button" onclick="location.href='<?= htmlspecialchars($baseUrl ?? '') ?>/auth/facebook'"
            class="flex-1 flex items-center justify-center py-3 bg-[#1877F2] hover:bg-[#166FE5] text-white rounded-lg transition-all duration-200"
            title="<?= __('auth.login_with_facebook') ?>">
        <svg class="w-5 h-5" fill="currentColor" viewBox="0 0 24 24">
            <path d="M24 12.073c0-6.627-5.373-12-12-12s-12 5.373-12 12c0 5.99 4.388 10.954 10.125 11.854v-8.385H7.078v-3.47h3.047V9.43c0-3.007 1.792-4.669 4.533-4.669 1.312 0 2.686.235 2.686.235v2.953H15.83c-1.491 0-1.956.925-1.956 1.874v2.25h3.328l-.532 3.47h-2.796v8.385C19.612 23.027 24 18.062 24 12.073z"/>
        </svg>
    </button>
    <?php endif; ?>
</div>
