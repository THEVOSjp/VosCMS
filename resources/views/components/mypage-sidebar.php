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

// ── 메뉴 로드 (load_menu 공통 헬퍼 사용) ──
$_allMypageMenus = function_exists('load_menu') ? load_menu('mypage') : [];

// main / bottom 분리
$menuItems = [];
$bottomItems = [];
foreach ($_allMypageMenus as $_mi) {
    if (($_mi['section'] ?? 'main') === 'bottom') {
        $bottomItems[] = $_mi;
    } else {
        $menuItems[] = $_mi;
    }
}

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
<button id="mypageSidebarToggle" onclick="mypageSidebar.open()" class="lg:hidden flex items-center gap-3 mb-4 p-3 bg-white dark:bg-zinc-800 rounded-xl shadow-md w-full">
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
<div id="mypageSidebarOverlay" class="fixed inset-0 z-50 lg:hidden pointer-events-none">
    <!-- 배경 딤 (페이드 인/아웃) -->
    <div id="mypageSidebarDim" onclick="mypageSidebar.close()" class="absolute inset-0 bg-black/0 transition-colors duration-300"></div>
    <!-- 슬라이드 패널 -->
    <div id="mypageSidebarPanel" class="absolute left-0 top-0 h-full w-72 bg-white dark:bg-zinc-800 shadow-2xl transform -translate-x-full transition-transform duration-300 ease-out overflow-y-auto pointer-events-auto">
        <!-- 닫기 버튼 -->
        <div class="flex items-center justify-between p-4 border-b border-zinc-200 dark:border-zinc-700">
            <span class="text-sm font-bold text-gray-900 dark:text-white"><?= __('auth.mypage.menu.profile') ?></span>
            <button onclick="mypageSidebar.close()" class="p-1.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-white rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
<script>
var mypageSidebar = {
    open: function() {
        var overlay = document.getElementById('mypageSidebarOverlay');
        var dim = document.getElementById('mypageSidebarDim');
        var panel = document.getElementById('mypageSidebarPanel');
        overlay.classList.remove('pointer-events-none');
        document.body.style.overflow = 'hidden';
        requestAnimationFrame(function() {
            dim.classList.replace('bg-black/0', 'bg-black/50');
            panel.classList.replace('-translate-x-full', 'translate-x-0');
        });
    },
    close: function() {
        var overlay = document.getElementById('mypageSidebarOverlay');
        var dim = document.getElementById('mypageSidebarDim');
        var panel = document.getElementById('mypageSidebarPanel');
        dim.classList.replace('bg-black/50', 'bg-black/0');
        panel.classList.replace('translate-x-0', '-translate-x-full');
        setTimeout(function() {
            overlay.classList.add('pointer-events-none');
            document.body.style.overflow = '';
        }, 300);
    }
};
</script>
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
