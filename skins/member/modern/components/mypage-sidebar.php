<?php
/**
 * Modern 스킨 - 마이페이지 사이드바 오버라이드
 *
 * 공통 컴포넌트(resources/views/components/mypage-sidebar.php)에서 전달되는 변수:
 *   $menuItems     - 메인 메뉴 배열 [{key, url, label, icon}, ...]
 *   $bottomItems   - 하단 메뉴 배열 [{key, url, label, icon, style}, ...]
 *   $sidebarActive - 현재 활성 메뉴 키
 *   $baseUrl       - 사이트 기본 URL
 *   $user          - 로그인 사용자 정보
 *   $profileImgUrl - 프로필 이미지 URL
 *
 * 이 파일에서 디자인만 자유롭게 변경 가능합니다.
 * 메뉴 데이터는 공통 컴포넌트에서 관리합니다.
 */
?>
<aside class="lg:w-72 mb-6 lg:mb-0">
    <div class="bg-white dark:bg-zinc-800 rounded-3xl shadow-xl p-8 sticky top-24 border border-zinc-100 dark:border-zinc-700">
        <!-- 프로필 영역 (모던 스타일) -->
        <div class="text-center mb-8">
            <div class="relative inline-block">
                <div class="w-24 h-24 rounded-full flex items-center justify-center mx-auto mb-4 overflow-hidden ring-4 ring-blue-100 dark:ring-blue-900/50 <?= !empty($profileImgUrl) ? '' : 'bg-gradient-to-br from-indigo-500 via-purple-500 to-pink-500' ?>">
                    <?php if (!empty($profileImgUrl)): ?>
                        <img src="<?= htmlspecialchars($profileImgUrl) ?>" alt="" class="w-full h-full object-cover">
                    <?php else: ?>
                        <span class="text-3xl font-bold text-white"><?= mb_substr($user['name'] ?? 'U', 0, 1) ?></span>
                    <?php endif; ?>
                </div>
            </div>
            <h2 class="text-xl font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($user['name'] ?? '') ?></h2>
            <p class="text-sm text-gray-500 dark:text-zinc-400 mt-1"><?= htmlspecialchars($user['email'] ?? '') ?></p>
            <?php if (!empty($user['created_at'])): ?>
            <div class="inline-flex items-center gap-1 mt-2 px-3 py-1 bg-zinc-100 dark:bg-zinc-700 rounded-full">
                <svg class="w-3.5 h-3.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                <span class="text-xs text-gray-400 dark:text-zinc-500"><?= __('auth.mypage.member_since', ['date' => date('Y.m.d', strtotime($user['created_at']))]) ?></span>
            </div>
            <?php endif; ?>
        </div>

        <!-- 메인 메뉴 (모던 스타일) -->
        <nav class="space-y-1.5">
            <?php foreach ($menuItems as $item):
                $isActive = $sidebarActive === $item['key'];
                $cls = $isActive
                    ? 'bg-gradient-to-r from-blue-500 to-indigo-500 text-white shadow-md shadow-blue-500/20'
                    : 'text-gray-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 hover:translate-x-1';
            ?>
            <a href="<?= $baseUrl ?><?= $item['url'] ?>" class="flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $cls ?>">
                <svg class="w-5 h-5 mr-3 <?= $isActive ? 'text-white' : '' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><?= $item['icon'] ?></svg>
                <?= $item['label'] ?>
            </a>
            <?php endforeach; ?>
        </nav>

        <!-- 하단 메뉴 -->
        <div class="mt-6 pt-6 border-t border-zinc-200 dark:border-zinc-700 space-y-1.5">
            <?php foreach ($bottomItems as $bItem):
                $isActive = $sidebarActive === $bItem['key'];
                if ($bItem['style'] === 'danger') {
                    $cls = 'text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20';
                } elseif ($isActive) {
                    $cls = 'bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400';
                } else {
                    $cls = 'text-gray-400 dark:text-zinc-500 hover:bg-zinc-50 dark:hover:bg-zinc-700/50';
                }
            ?>
            <a href="<?= $baseUrl ?><?= $bItem['url'] ?>" class="flex items-center px-4 py-3 text-sm font-medium rounded-xl transition-all duration-200 <?= $cls ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><?= $bItem['icon'] ?></svg>
                <?= $bItem['label'] ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</aside>
