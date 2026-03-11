<?php
/**
 * RezlyX Member Skin - Modern
 * 마이페이지 템플릿
 *
 * 사용 가능한 변수:
 * - $config: 스킨 설정
 * - $colorset: 현재 컬러셋
 * - $translations: 번역 데이터
 * - $user: 현재 로그인한 사용자 정보
 * - $stats: 사용자 통계 (게시글 수, 댓글 수 등)
 * - $baseUrl: 기본 URL
 * - $siteName: 사이트 이름
 */

$colors = $colorset ?? $config['colorsets']['default'];
?>

<style>
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

<!-- Alpine.js -->
<script defer src="https://cdn.jsdelivr.net/npm/alpinejs@3.x.x/dist/cdn.min.js"></script>

<script>
    // 다크 모드 초기화
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
        } else {
            document.documentElement.classList.remove('dark');
        }
    })();

    // 다크 모드 토글 함수 (헤더 컴포넌트에서 사용)
    function toggleDarkMode() {
        var html = document.documentElement;
        var isDark = html.classList.toggle('dark');
        localStorage.setItem('darkMode', isDark ? 'true' : 'false');
    }
</script>

<div class="min-h-screen bg-gray-50 dark:bg-zinc-900 transition-colors duration-200">
    <!-- Header (재사용 컴포넌트) -->
    <?php
    $headerPath = __DIR__ . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'header.php';
    if (!file_exists($headerPath)) {
        $headerPath = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'default' . DIRECTORY_SEPARATOR . 'components' . DIRECTORY_SEPARATOR . 'header.php';
    }
    if (file_exists($headerPath)) {
        include $headerPath;
    }
    ?>

    <!-- Main Content -->
    <main class="py-8 px-4 sm:px-6 lg:px-8">
        <div class="max-w-4xl mx-auto space-y-6">

            <!-- 프로필 헤더 -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm overflow-hidden transition-colors duration-200">
                <div class="h-32 bg-gradient-to-r from-blue-500 to-purple-600"></div>
                <div class="relative px-6 pb-6">
                    <div class="flex flex-col sm:flex-row sm:items-end sm:space-x-5">
                        <!-- 프로필 이미지 -->
                        <div class="-mt-16 relative">
                            <?php if (!empty($user['profile_photo'])): ?>
                                <img src="<?= htmlspecialchars($user['profile_photo']) ?>" alt="Profile"
                                    class="w-32 h-32 rounded-full border-4 border-white dark:border-zinc-800 shadow-md object-cover">
                            <?php else: ?>
                                <div class="w-32 h-32 rounded-full border-4 border-white dark:border-zinc-800 shadow-md flex items-center justify-center text-4xl font-bold text-white bg-blue-500">
                                    <?= mb_substr($user['name'] ?? 'U', 0, 1) ?>
                                </div>
                            <?php endif; ?>
                            <button class="absolute bottom-0 right-0 p-2 bg-white dark:bg-zinc-700 rounded-full shadow-md hover:bg-gray-50 dark:hover:bg-zinc-600 transition-colors">
                                <svg class="w-4 h-4 text-gray-600 dark:text-zinc-300" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                                </svg>
                            </button>
                        </div>

                        <!-- 사용자 정보 -->
                        <div class="mt-4 sm:mt-0 flex-1">
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white">
                                <?= htmlspecialchars($user['name'] ?? '사용자') ?>
                            </h1>
                            <p class="text-sm text-gray-600 dark:text-zinc-400">
                                <?= htmlspecialchars($user['email'] ?? '') ?>
                            </p>
                            <p class="text-xs mt-1 text-gray-500 dark:text-zinc-500">
                                <?= $translations['member_since'] ?? '가입일' ?>: <?= date('Y.m.d', strtotime($user['created_at'] ?? 'now')) ?>
                            </p>
                        </div>

                        <!-- 프로필 수정 버튼 -->
                        <div class="mt-4 sm:mt-0">
                            <a href="<?= htmlspecialchars($profileEditUrl ?? ($baseUrl . '/mypage/profile')) ?>"
                                class="inline-flex items-center px-4 py-2 border border-gray-300 dark:border-zinc-600 rounded-lg text-sm font-medium text-gray-700 dark:text-zinc-300 hover:bg-gray-50 dark:hover:bg-zinc-700 transition-colors">
                                <svg class="w-4 h-4 mr-2" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z" />
                                </svg>
                                <?= $translations['edit_profile'] ?? '프로필 수정' ?>
                            </a>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 통계 카드 -->
            <div class="grid grid-cols-2 sm:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-5 text-center transition-colors duration-200">
                    <div class="text-2xl font-bold text-blue-500">
                        <?= number_format($stats['reservations'] ?? 0) ?>
                    </div>
                    <div class="text-sm mt-1 text-gray-600 dark:text-zinc-400">
                        <?= $translations['my_reservations'] ?? '내 예약' ?>
                    </div>
                </div>
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-5 text-center transition-colors duration-200">
                    <div class="text-2xl font-bold text-green-500">
                        <?= number_format($stats['completed'] ?? 0) ?>
                    </div>
                    <div class="text-sm mt-1 text-gray-600 dark:text-zinc-400">
                        <?= $translations['completed'] ?? '완료됨' ?>
                    </div>
                </div>
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-5 text-center transition-colors duration-200">
                    <div class="text-2xl font-bold text-yellow-500">
                        <?= number_format($stats['upcoming'] ?? 0) ?>
                    </div>
                    <div class="text-sm mt-1 text-gray-600 dark:text-zinc-400">
                        <?= $translations['upcoming'] ?? '예정됨' ?>
                    </div>
                </div>
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-5 text-center transition-colors duration-200">
                    <div class="text-2xl font-bold text-purple-500">
                        <?= number_format($stats['points'] ?? 0) ?>
                    </div>
                    <div class="text-sm mt-1 text-gray-600 dark:text-zinc-400">
                        <?= $translations['points'] ?? '적립금' ?>
                    </div>
                </div>
            </div>

            <!-- 메뉴 카드 -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm divide-y divide-gray-100 dark:divide-zinc-700 transition-colors duration-200">
                <a href="<?= htmlspecialchars($baseUrl . '/mypage/reservations') ?>" class="flex items-center justify-between p-4 hover:bg-gray-50 dark:hover:bg-zinc-700 transition-colors">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center bg-blue-100 dark:bg-blue-900/30">
                            <svg class="w-5 h-5 text-blue-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <span class="ml-3 font-medium text-gray-900 dark:text-white">
                            <?= $translations['my_reservations'] ?? '예약 내역' ?>
                        </span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>

                <a href="<?= htmlspecialchars($baseUrl . '/mypage/profile') ?>" class="flex items-center justify-between p-4 hover:bg-gray-50 dark:hover:bg-zinc-700 transition-colors">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center bg-purple-100 dark:bg-purple-900/30">
                            <svg class="w-5 h-5 text-purple-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                            </svg>
                        </div>
                        <span class="ml-3 font-medium text-gray-900 dark:text-white">
                            <?= $translations['profile_settings'] ?? '프로필 설정' ?>
                        </span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>

                <a href="<?= htmlspecialchars($baseUrl . '/mypage/password') ?>" class="flex items-center justify-between p-4 hover:bg-gray-50 dark:hover:bg-zinc-700 transition-colors">
                    <div class="flex items-center">
                        <div class="w-10 h-10 rounded-full flex items-center justify-center bg-green-100 dark:bg-green-900/30">
                            <svg class="w-5 h-5 text-green-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                            </svg>
                        </div>
                        <span class="ml-3 font-medium text-gray-900 dark:text-white">
                            <?= $translations['change_password'] ?? '비밀번호 변경' ?>
                        </span>
                    </div>
                    <svg class="w-5 h-5 text-gray-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                    </svg>
                </a>

                <form action="<?= htmlspecialchars($baseUrl . '/auth/logout') ?>" method="POST" class="block">
                    <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrfToken ?? '') ?>">
                    <button type="submit" class="w-full flex items-center justify-between p-4 hover:bg-gray-50 dark:hover:bg-zinc-700 transition-colors text-left">
                        <div class="flex items-center">
                            <div class="w-10 h-10 rounded-full flex items-center justify-center bg-red-100 dark:bg-red-900/30">
                                <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                                </svg>
                            </div>
                            <span class="ml-3 font-medium text-red-500">
                                <?= $translations['logout'] ?? '로그아웃' ?>
                            </span>
                        </div>
                        <svg class="w-5 h-5 text-gray-400 dark:text-zinc-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                        </svg>
                    </button>
                </form>
            </div>
        </div>
    </main>
</div>
