<?php
/**
 * RezlyX Member Skin - Default
 * 마이페이지 템플릿
 *
 * 사용 가능한 변수:
 * - $config: 스킨 설정
 * - $colorset: 현재 컬러셋
 * - $translations: 번역 데이터
 * - $user: 현재 로그인한 사용자 정보
 * - $stats: 사용자 통계 (게시글 수, 댓글 수 등)
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

<div class="min-h-screen py-8 px-4 sm:px-6 lg:px-8" style="background-color: var(--skin-background);">
    <div class="max-w-4xl mx-auto space-y-6">

        <!-- 프로필 헤더 -->
        <div class="bg-white rounded-xl shadow-sm overflow-hidden">
            <div class="h-32 bg-gradient-to-r from-blue-500 to-purple-600"></div>
            <div class="relative px-6 pb-6">
                <div class="flex flex-col sm:flex-row sm:items-end sm:space-x-5">
                    <!-- 프로필 이미지 -->
                    <div class="-mt-16 relative">
                        <?php if (!empty($user['profile_photo'])): ?>
                            <img src="<?= htmlspecialchars($user['profile_photo']) ?>" alt="Profile"
                                class="w-32 h-32 rounded-full border-4 border-white shadow-md object-cover">
                        <?php else: ?>
                            <div class="w-32 h-32 rounded-full border-4 border-white shadow-md flex items-center justify-center text-4xl font-bold text-white"
                                style="background-color: var(--skin-primary);">
                                <?= mb_substr($user['name'] ?? 'U', 0, 1) ?>
                            </div>
                        <?php endif; ?>
                        <button class="absolute bottom-0 right-0 p-2 bg-white rounded-full shadow-md hover:bg-gray-50 transition-colors">
                            <svg class="w-4 h-4 text-gray-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z" />
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 13a3 3 0 11-6 0 3 3 0 016 0z" />
                            </svg>
                        </button>
                    </div>

                    <!-- 사용자 정보 -->
                    <div class="mt-4 sm:mt-0 flex-1">
                        <h1 class="text-2xl font-bold" style="color: var(--skin-text);">
                            <?= htmlspecialchars($user['name'] ?? '사용자') ?>
                        </h1>
                        <p class="text-sm" style="color: var(--skin-secondary);">
                            <?= htmlspecialchars($user['email'] ?? '') ?>
                        </p>
                        <p class="text-xs mt-1" style="color: var(--skin-secondary);">
                            <?= $translations['member_since'] ?? '가입일' ?>: <?= date('Y.m.d', strtotime($user['created_at'] ?? 'now')) ?>
                        </p>
                    </div>

                    <!-- 프로필 수정 버튼 -->
                    <div class="mt-4 sm:mt-0">
                        <a href="<?= $profileEditUrl ?? '#' ?>"
                            class="inline-flex items-center px-4 py-2 border border-gray-300 rounded-lg text-sm font-medium hover:bg-gray-50 transition-colors"
                            style="color: var(--skin-text);">
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
            <div class="bg-white rounded-xl shadow-sm p-5 text-center">
                <div class="text-2xl font-bold" style="color: var(--skin-primary);">
                    <?= number_format($stats['posts'] ?? 0) ?>
                </div>
                <div class="text-sm mt-1" style="color: var(--skin-secondary);">
                    <?= $translations['my_posts'] ?? '내 게시글' ?>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5 text-center">
                <div class="text-2xl font-bold" style="color: var(--skin-accent);">
                    <?= number_format($stats['comments'] ?? 0) ?>
                </div>
                <div class="text-sm mt-1" style="color: var(--skin-secondary);">
                    <?= $translations['my_comments'] ?? '내 댓글' ?>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5 text-center">
                <div class="text-2xl font-bold" style="color: #F59E0B;">
                    <?= number_format($stats['scraps'] ?? 0) ?>
                </div>
                <div class="text-sm mt-1" style="color: var(--skin-secondary);">
                    <?= $translations['scraps'] ?? '스크랩' ?>
                </div>
            </div>
            <div class="bg-white rounded-xl shadow-sm p-5 text-center">
                <div class="text-2xl font-bold" style="color: #EF4444;">
                    <?= number_format($stats['bookmarks'] ?? 0) ?>
                </div>
                <div class="text-sm mt-1" style="color: var(--skin-secondary);">
                    <?= $translations['bookmarks'] ?? '북마크' ?>
                </div>
            </div>
        </div>

        <!-- 메뉴 카드 -->
        <div class="bg-white rounded-xl shadow-sm divide-y divide-gray-100">
            <a href="<?= $profileEditUrl ?? '#' ?>" class="flex items-center justify-between p-4 hover:bg-gray-50 transition-colors">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center" style="background-color: rgba(59, 130, 246, 0.1);">
                        <svg class="w-5 h-5" style="color: var(--skin-primary);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z" />
                        </svg>
                    </div>
                    <span class="ml-3 font-medium" style="color: var(--skin-text);">
                        <?= $translations['profile_settings'] ?? '프로필 설정' ?>
                    </span>
                </div>
                <svg class="w-5 h-5" style="color: var(--skin-secondary);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>

            <a href="<?= $passwordChangeUrl ?? '#' ?>" class="flex items-center justify-between p-4 hover:bg-gray-50 transition-colors">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center" style="background-color: rgba(16, 185, 129, 0.1);">
                        <svg class="w-5 h-5" style="color: var(--skin-accent);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z" />
                        </svg>
                    </div>
                    <span class="ml-3 font-medium" style="color: var(--skin-text);">
                        <?= $translations['change_password'] ?? '비밀번호 변경' ?>
                    </span>
                </div>
                <svg class="w-5 h-5" style="color: var(--skin-secondary);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>

            <a href="<?= $myPostsUrl ?? '#' ?>" class="flex items-center justify-between p-4 hover:bg-gray-50 transition-colors">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center" style="background-color: rgba(245, 158, 11, 0.1);">
                        <svg class="w-5 h-5 text-yellow-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z" />
                        </svg>
                    </div>
                    <span class="ml-3 font-medium" style="color: var(--skin-text);">
                        <?= $translations['my_posts'] ?? '내 게시글' ?>
                    </span>
                </div>
                <svg class="w-5 h-5" style="color: var(--skin-secondary);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>

            <a href="<?= $logoutUrl ?? '#' ?>" class="flex items-center justify-between p-4 hover:bg-gray-50 transition-colors">
                <div class="flex items-center">
                    <div class="w-10 h-10 rounded-full flex items-center justify-center bg-red-50">
                        <svg class="w-5 h-5 text-red-500" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1" />
                        </svg>
                    </div>
                    <span class="ml-3 font-medium text-red-500">
                        <?= $translations['logout'] ?? '로그아웃' ?>
                    </span>
                </div>
                <svg class="w-5 h-5" style="color: var(--skin-secondary);" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7" />
                </svg>
            </a>
        </div>
    </div>
</div>
