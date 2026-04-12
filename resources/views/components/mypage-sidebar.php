<?php
/**
 * 마이페이지 공통 사이드바 컴포넌트
 *
 * 필수 변수: $baseUrl, $user, $profileImgUrl
 * 선택 변수: $sidebarActive (기본: 'dashboard')
 *
 * 데스크톱: 좌측 고정 사이드바
 * 모바일: 아바타 토글 → 좌측 슬라이드인 오버레이
 *
 * 스킨 오버라이드:
 *   skins/member/{스킨명}/components/mypage-sidebar.php 파일이 있으면 해당 파일의 디자인을 사용
 */

$sidebarActive = $sidebarActive ?? 'dashboard';

// ── 메뉴 데이터 정의 ──
$menuItems = [
    ['key' => 'dashboard', 'url' => '/mypage', 'label' => __('auth.mypage.menu.dashboard'),
     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>'],
    ['key' => 'reservations', 'url' => '/mypage/reservations', 'label' => __('auth.mypage.menu.reservations'),
     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>'],
    ['key' => 'profile', 'url' => '/mypage/profile', 'label' => __('auth.mypage.menu.profile'),
     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>'],
    ['key' => 'messages', 'url' => '/mypage/messages', 'label' => __('auth.mypage.menu.messages'),
     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 10h.01M12 10h.01M16 10h.01M9 16H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-5l-5 5v-5z"/>'],
    ['key' => 'settings', 'url' => '/mypage/settings', 'label' => __('auth.mypage.menu.settings'),
     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>'],
    ['key' => 'password', 'url' => '/mypage/password', 'label' => __('auth.mypage.menu.password'),
     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>'],
];

// ── 하단 메뉴 (구분선 아래) ──
$bottomItems = [
    ['key' => 'withdraw', 'url' => '/mypage/withdraw', 'label' => __('auth.mypage.menu.withdraw'),
     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>',
     'style' => 'muted'],
    ['key' => 'logout', 'url' => '/logout', 'label' => __('auth.mypage.menu.logout'),
     'icon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 16l4-4m0 0l-4-4m4 4H7m6 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h4a3 3 0 013 3v1"/>',
     'style' => 'danger'],
];

// ── 아바타 HTML (재사용) ──
$_avatarHtml = '';
if (!empty($profileImgUrl)) {
    $_avatarHtml = '<img src="' . htmlspecialchars($profileImgUrl) . '" alt="" class="w-full h-full object-cover">';
} else {
    $_avatarHtml = '<span class="text-2xl font-bold text-white">' . mb_substr($user['name'] ?? 'U', 0, 1) . '</span>';
}
$_avatarBg = !empty($profileImgUrl) ? '' : 'bg-gradient-to-br from-blue-500 to-purple-600';

// ── 스킨 오버라이드 확인 ──
$_sidebarSkinOverride = null;
if (defined('BASE_PATH')) {
    $_memberSkin = 'default';
    if (!empty($siteSettings['member_skin'])) {
        $_memberSkin = $siteSettings['member_skin'];
    } else {
        try {
            $_pdo = \RzxLib\Core\Database\Connection::getInstance()->getPdo();
            $_stmt = $_pdo->prepare("SELECT `value` FROM " . ($_ENV['DB_PREFIX'] ?? 'rzx_') . "settings WHERE `key` = 'member_skin' LIMIT 1");
            $_stmt->execute();
            $_val = $_stmt->fetchColumn();
            if ($_val) $_memberSkin = $_val;
        } catch (\Throwable $e) {}
    }
    $_skinSidebarPath = BASE_PATH . '/skins/member/' . $_memberSkin . '/components/mypage-sidebar.php';
    if (file_exists($_skinSidebarPath)) {
        $_sidebarSkinOverride = $_skinSidebarPath;
    }
}

if ($_sidebarSkinOverride): ?>
<?php include $_sidebarSkinOverride; ?>
<?php else: ?>

<!-- 모바일: 아바타 토글 버튼 (lg 이상에서 숨김) -->
<button id="mypageSidebarToggle" onclick="document.getElementById('mypageSidebarOverlay').classList.remove('hidden');document.getElementById('mypageSidebarPanel').classList.replace('-translate-x-full','translate-x-0')" class="lg:hidden flex items-center gap-3 mb-4 p-3 bg-white dark:bg-zinc-800 rounded-xl shadow-md w-full">
    <div class="w-10 h-10 rounded-full flex items-center justify-center overflow-hidden flex-shrink-0 <?= $_avatarBg ?>">
        <?= $_avatarHtml ?>
    </div>
    <div class="min-w-0 flex-1 text-left">
        <p class="text-sm font-bold text-gray-900 dark:text-white truncate"><?= htmlspecialchars($user['name'] ?? '') ?></p>
        <p class="text-xs text-gray-500 dark:text-zinc-400 truncate"><?= htmlspecialchars($user['email'] ?? '') ?></p>
    </div>
    <svg class="w-5 h-5 text-zinc-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16M4 18h16"/></svg>
</button>

<!-- 모바일: 슬라이드인 오버레이 -->
<div id="mypageSidebarOverlay" class="hidden fixed inset-0 z-50 lg:hidden">
    <!-- 배경 딤 -->
    <div onclick="document.getElementById('mypageSidebarOverlay').classList.add('hidden');document.getElementById('mypageSidebarPanel').classList.replace('translate-x-0','-translate-x-full')" class="absolute inset-0 bg-black/50"></div>
    <!-- 슬라이드 패널 -->
    <div id="mypageSidebarPanel" class="absolute left-0 top-0 h-full w-72 bg-white dark:bg-zinc-800 shadow-2xl transform -translate-x-full transition-transform duration-300 overflow-y-auto">
        <!-- 닫기 버튼 -->
        <div class="flex items-center justify-between p-4 border-b border-zinc-200 dark:border-zinc-700">
            <span class="text-sm font-bold text-gray-900 dark:text-white"><?= __('auth.mypage.menu.profile') ?></span>
            <button onclick="document.getElementById('mypageSidebarOverlay').classList.add('hidden');document.getElementById('mypageSidebarPanel').classList.replace('translate-x-0','-translate-x-full')" class="p-1.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-white rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <!-- 프로필 -->
        <div class="text-center p-6 pb-4">
            <div class="w-16 h-16 rounded-full flex items-center justify-center mx-auto mb-3 overflow-hidden <?= $_avatarBg ?>">
                <?= $_avatarHtml ?>
            </div>
            <h2 class="text-base font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($user['name'] ?? '') ?></h2>
            <p class="text-xs text-gray-500 dark:text-zinc-400"><?= htmlspecialchars($user['email'] ?? '') ?></p>
        </div>
        <!-- 메뉴 -->
        <nav class="px-3 space-y-1">
            <?php foreach ($menuItems as $item):
                $isActive = $sidebarActive === $item['key'];
                $cls = $isActive
                    ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400'
                    : 'text-gray-600 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-700';
            ?>
            <a href="<?= $baseUrl ?><?= $item['url'] ?>" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg <?= $cls ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><?= $item['icon'] ?></svg>
                <?= $item['label'] ?>
            </a>
            <?php endforeach; ?>
        </nav>
        <div class="mx-3 mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700 space-y-1 pb-6">
            <?php foreach ($bottomItems as $bItem):
                $isActive = $sidebarActive === $bItem['key'];
                if ($bItem['style'] === 'danger') {
                    $cls = 'text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20';
                } elseif ($isActive) {
                    $cls = 'bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400';
                } else {
                    $cls = 'text-gray-400 dark:text-zinc-500 hover:bg-gray-100 dark:hover:bg-zinc-700';
                }
            ?>
            <a href="<?= $baseUrl ?><?= $bItem['url'] ?>" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg <?= $cls ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><?= $bItem['icon'] ?></svg>
                <?= $bItem['label'] ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<!-- 데스크톱: 기존 사이드바 (lg 미만에서 숨김) -->
<aside class="hidden lg:block lg:w-64 mb-6 lg:mb-0">
    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-6 sticky top-24">
        <!-- 프로필 영역 -->
        <div class="text-center mb-6">
            <div class="w-20 h-20 rounded-full flex items-center justify-center mx-auto mb-3 overflow-hidden <?= $_avatarBg ?>">
                <?= $_avatarHtml ?>
            </div>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($user['name'] ?? '') ?></h2>
            <p class="text-sm text-gray-500 dark:text-zinc-400"><?= htmlspecialchars($user['email'] ?? '') ?></p>
            <?php if (!empty($user['created_at'])): ?>
            <p class="text-xs text-gray-400 dark:text-zinc-500 mt-1"><?= __('auth.mypage.member_since', ['date' => date('Y.m.d', strtotime($user['created_at']))]) ?></p>
            <?php endif; ?>
        </div>

        <!-- 메인 메뉴 -->
        <nav class="space-y-1">
            <?php foreach ($menuItems as $item):
                $isActive = $sidebarActive === $item['key'];
                $cls = $isActive
                    ? 'bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400'
                    : 'text-gray-600 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-700';
            ?>
            <a href="<?= $baseUrl ?><?= $item['url'] ?>" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg <?= $cls ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><?= $item['icon'] ?></svg>
                <?= $item['label'] ?>
            </a>
            <?php endforeach; ?>
        </nav>

        <!-- 하단 메뉴 (탈퇴/로그아웃) -->
        <div class="mt-4 pt-4 border-t border-zinc-200 dark:border-zinc-700 space-y-1">
            <?php foreach ($bottomItems as $bItem):
                $isActive = $sidebarActive === $bItem['key'];
                if ($bItem['style'] === 'danger') {
                    $cls = 'text-red-500 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20';
                } elseif ($isActive) {
                    $cls = 'bg-red-50 dark:bg-red-900/30 text-red-600 dark:text-red-400';
                } else {
                    $cls = 'text-gray-400 dark:text-zinc-500 hover:bg-gray-100 dark:hover:bg-zinc-700';
                }
            ?>
            <a href="<?= $baseUrl ?><?= $bItem['url'] ?>" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg <?= $cls ?>">
                <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><?= $bItem['icon'] ?></svg>
                <?= $bItem['label'] ?>
            </a>
            <?php endforeach; ?>
        </div>
    </div>
</aside>
<?php endif; ?>
