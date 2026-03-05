<?php
/**
 * RezlyX Admin Members Settings - Design
 * Member page design settings configuration
 */

require_once __DIR__ . '/_init.php';

$pageTitle = __('admin.members.settings.tabs.design') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$currentMemberSettingsPage = 'design';

/**
 * Get available member skins from the skins/member directory
 * @return array List of available skin data with metadata
 */
function getAvailableMemberSkins(): array
{
    $skinsPath = realpath(__DIR__ . '/../../../../../skins/member');
    $availableSkins = [];

    if ($skinsPath && is_dir($skinsPath)) {
        $dirs = scandir($skinsPath);
        foreach ($dirs as $dir) {
            if ($dir !== '.' && $dir !== '..' && is_dir($skinsPath . '/' . $dir)) {
                $skinData = [
                    'name' => $dir,
                    'display_name' => ucfirst($dir),
                    'version' => '1.0.0',
                    'author' => 'Unknown',
                    'description' => '',
                    'preview' => null,
                    'thumbnail' => null,
                    'colorsets' => ['default'],
                ];

                // config.php 로드
                $configPath = $skinsPath . '/' . $dir . '/config.php';
                if (file_exists($configPath)) {
                    $config = require $configPath;
                    $skinData['display_name'] = $config['name'] ?? ucfirst($dir);
                    $skinData['version'] = $config['version'] ?? '1.0.0';
                    $skinData['author'] = $config['author'] ?? 'Unknown';
                    $skinData['description'] = $config['description'] ?? '';
                    $skinData['colorsets'] = array_keys($config['colorsets'] ?? ['default' => []]);
                }

                // 미리보기 이미지 확인 (baseUrl 포함)
                global $baseUrl;
                $skinBaseUrl = $baseUrl ?? '';
                if (file_exists($skinsPath . '/' . $dir . '/preview.png')) {
                    $skinData['preview'] = $skinBaseUrl . '/skins/member/' . $dir . '/preview.png';
                }
                if (file_exists($skinsPath . '/' . $dir . '/thumbnail.png')) {
                    $skinData['thumbnail'] = $skinBaseUrl . '/skins/member/' . $dir . '/thumbnail.png';
                }

                $availableSkins[$dir] = $skinData;
            }
        }
    }

    // 기본값 보장
    if (empty($availableSkins)) {
        $availableSkins['default'] = [
            'name' => 'default',
            'display_name' => 'Default',
            'version' => '1.0.0',
            'author' => 'RezlyX',
            'description' => '기본 회원 스킨',
            'preview' => null,
            'thumbnail' => null,
            'colorsets' => ['default', 'dark', 'blue', 'green'],
        ];
    }

    return $availableSkins;
}

/**
 * Get available skins from the skins directory (legacy)
 * @return array List of available skin names
 */
function getAvailableSkins(): array
{
    $skinsPath = realpath(__DIR__ . '/../../../../../skins');
    $availableSkins = [];

    if ($skinsPath && is_dir($skinsPath)) {
        $dirs = scandir($skinsPath);
        foreach ($dirs as $dir) {
            if ($dir !== '.' && $dir !== '..' && is_dir($skinsPath . '/' . $dir)) {
                $availableSkins[] = $dir;
            }
        }
    }

    // 기본값 보장
    if (empty($availableSkins)) {
        $availableSkins[] = 'default';
    }

    return $availableSkins;
}

/**
 * Get available layouts from the layouts directory
 * @return array List of available layout names
 */
function getAvailableLayouts(): array
{
    $layoutsPath = realpath(__DIR__ . '/../../../../../layouts');
    $availableLayouts = [];

    if ($layoutsPath && is_dir($layoutsPath)) {
        $dirs = scandir($layoutsPath);
        foreach ($dirs as $dir) {
            if ($dir !== '.' && $dir !== '..' && is_dir($layoutsPath . '/' . $dir)) {
                $availableLayouts[] = $dir;
            }
        }
    }

    return $availableLayouts;
}

// 사용 가능한 스킨 및 레이아웃 로드
$availableSkins = getAvailableSkins();
$availableMemberSkins = getAvailableMemberSkins();
$availableLayouts = getAvailableLayouts();

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_design') {
        try {
            $stmt = $pdo->prepare("INSERT INTO {$prefix}settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");

            // 스킨
            $skin = $_POST['member_skin'] ?? 'default';
            $stmt->execute(['member_skin', $skin]);
            $memberSettings['member_skin'] = $skin;

            // 소셜 로그인 기능
            $socialLoginEnabled = isset($_POST['member_social_login_enabled']) ? '1' : '0';
            $stmt->execute(['member_social_login_enabled', $socialLoginEnabled]);
            $memberSettings['member_social_login_enabled'] = $socialLoginEnabled;

            // 구글 로그인
            $socialGoogle = isset($_POST['member_social_google']) ? '1' : '0';
            $stmt->execute(['member_social_google', $socialGoogle]);
            $memberSettings['member_social_google'] = $socialGoogle;

            // 라인 로그인
            $socialLine = isset($_POST['member_social_line']) ? '1' : '0';
            $stmt->execute(['member_social_line', $socialLine]);
            $memberSettings['member_social_line'] = $socialLine;

            // 카카오톡 로그인
            $socialKakao = isset($_POST['member_social_kakao']) ? '1' : '0';
            $stmt->execute(['member_social_kakao', $socialKakao]);
            $memberSettings['member_social_kakao'] = $socialKakao;

            // 로그인 페이지 배경
            $loginBackground = $_POST['member_login_background'] ?? 'none';
            $stmt->execute(['member_login_background', $loginBackground]);
            $memberSettings['member_login_background'] = $loginBackground;

            // 회원가입 페이지 레이아웃
            $registerLayout = $_POST['member_register_layout'] ?? 'single';
            $stmt->execute(['member_register_layout', $registerLayout]);
            $memberSettings['member_register_layout'] = $registerLayout;

            // 프로필 페이지 스타일
            $profileStyle = $_POST['member_profile_style'] ?? 'card';
            $stmt->execute(['member_profile_style', $profileStyle]);
            $memberSettings['member_profile_style'] = $profileStyle;

            $message = __('admin.settings.success');
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = __('admin.settings.error_save') . ': ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

ob_start();
?>

<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 transition-colors mb-6">
    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-2"><?= __('admin.members.settings.design.title') ?></h2>
    <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-6"><?= __('admin.members.settings.design.description') ?></p>

    <form method="POST" class="space-y-6">
        <input type="hidden" name="action" value="update_design">

        <!-- 회원 스킨 선택 (카드 형식) -->
        <div class="py-4 border-b dark:border-zinc-700">
            <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-1"><?= __('admin.members.settings.design.skin') ?></label>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4"><?= __('admin.members.settings.design.skin_desc') ?></p>

            <?php $currentSkin = $memberSettings['member_skin'] ?? 'default'; ?>

            <!-- 스킨 관리 버튼 그룹 -->
            <div class="flex items-center justify-end mb-4">
                <div class="relative" id="addSkinContainer">
                    <button type="button" id="addSkinBtn"
                            class="inline-flex items-center px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition shadow-sm">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/>
                        </svg>
                        <?= __('admin.members.settings.design.add_skin') ?? '신규 스킨 추가' ?>
                        <svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/>
                        </svg>
                    </button>
                    <div id="addSkinDropdown" class="hidden absolute right-0 mt-2 w-56 bg-white dark:bg-zinc-800 rounded-lg shadow-lg border border-zinc-200 dark:border-zinc-700 py-1 z-50">
                        <a href="#" onclick="showDirectRegisterModal(); return false;"
                           class="flex items-center px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                            <svg class="w-5 h-5 mr-3 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-8l-4-4m0 0L8 8m4-4v12"/>
                            </svg>
                            <div>
                                <div class="font-medium"><?= __('admin.members.settings.design.direct_register') ?? '직접 등록' ?></div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400"><?= __('admin.members.settings.design.direct_register_desc') ?? '스킨 파일을 직접 업로드합니다' ?></div>
                            </div>
                        </a>
                        <a href="#" onclick="goToMarketplace(); return false;"
                           class="flex items-center px-4 py-3 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 transition-colors">
                            <svg class="w-5 h-5 mr-3 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>
                            </svg>
                            <div>
                                <div class="font-medium"><?= __('admin.members.settings.design.marketplace') ?? '마켓플레이스로부터 구입' ?></div>
                                <div class="text-xs text-zinc-500 dark:text-zinc-400"><?= __('admin.members.settings.design.marketplace_desc') ?? '다양한 스킨을 찾아보세요' ?></div>
                            </div>
                        </a>
                    </div>
                </div>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
                <?php foreach ($availableMemberSkins as $skinKey => $skinData): ?>
                <label class="cursor-pointer group relative">
                    <input type="radio" name="member_skin" value="<?= htmlspecialchars($skinKey) ?>"
                           <?= $currentSkin === $skinKey ? 'checked' : '' ?> class="sr-only peer">
                    <div class="border-2 rounded-xl overflow-hidden transition-all
                                peer-checked:border-blue-500 peer-checked:ring-2 peer-checked:ring-blue-500/20
                                border-zinc-200 dark:border-zinc-700 hover:border-blue-300 dark:hover:border-blue-600">
                        <!-- 미리보기 이미지 영역 -->
                        <div class="aspect-[4/3] bg-gradient-to-br from-zinc-100 to-zinc-200 dark:from-zinc-800 dark:to-zinc-700 relative overflow-hidden">
                            <?php if ($skinData['thumbnail']): ?>
                                <img src="<?= htmlspecialchars($skinData['thumbnail']) ?>" alt="<?= htmlspecialchars($skinData['display_name']) ?>"
                                     class="w-full h-full object-cover">
                            <?php else: ?>
                                <!-- 기본 스킨 미리보기 (CSS) -->
                                <div class="absolute inset-4 bg-white dark:bg-zinc-800 rounded-lg shadow-sm flex flex-col">
                                    <!-- 헤더 -->
                                    <div class="h-6 bg-blue-500 rounded-t-lg flex items-center px-2">
                                        <div class="flex gap-1">
                                            <div class="w-1.5 h-1.5 rounded-full bg-white/50"></div>
                                            <div class="w-1.5 h-1.5 rounded-full bg-white/50"></div>
                                            <div class="w-1.5 h-1.5 rounded-full bg-white/50"></div>
                                        </div>
                                    </div>
                                    <!-- 로그인 폼 미리보기 -->
                                    <div class="flex-1 p-2 flex flex-col items-center justify-center">
                                        <div class="w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-600 mb-2"></div>
                                        <div class="w-full max-w-[60%] space-y-1">
                                            <div class="h-1.5 bg-zinc-200 dark:bg-zinc-600 rounded"></div>
                                            <div class="h-3 bg-zinc-100 dark:bg-zinc-700 rounded border border-zinc-200 dark:border-zinc-600"></div>
                                            <div class="h-3 bg-zinc-100 dark:bg-zinc-700 rounded border border-zinc-200 dark:border-zinc-600"></div>
                                            <div class="h-3 bg-blue-500 rounded mt-1"></div>
                                        </div>
                                    </div>
                                </div>
                            <?php endif; ?>
                        </div>

                        <!-- 스킨 정보 -->
                        <div class="p-3 bg-white dark:bg-zinc-800">
                            <div class="flex items-center justify-between mb-1">
                                <h4 class="font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($skinData['display_name']) ?></h4>
                                <span class="text-xs text-zinc-500 dark:text-zinc-400">v<?= htmlspecialchars($skinData['version']) ?></span>
                            </div>
                            <p class="text-xs text-zinc-500 dark:text-zinc-400 line-clamp-2">
                                <?= htmlspecialchars($skinData['description'] ?: '회원 페이지 스킨') ?>
                            </p>
                            <!-- 컬러셋 표시 -->
                            <div class="flex items-center gap-1 mt-2">
                                <span class="text-xs text-zinc-400 dark:text-zinc-500"><?= __('admin.members.settings.design.colorset') ?>:</span>
                                <?php foreach (array_slice($skinData['colorsets'], 0, 4) as $cs): ?>
                                    <span class="px-1.5 py-0.5 text-xs bg-zinc-100 dark:bg-zinc-700 rounded text-zinc-600 dark:text-zinc-300">
                                        <?= htmlspecialchars($cs) ?>
                                    </span>
                                <?php endforeach; ?>
                                <?php if (count($skinData['colorsets']) > 4): ?>
                                    <span class="text-xs text-zinc-400">+<?= count($skinData['colorsets']) - 4 ?></span>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <!-- 선택됨 표시 (peer 형제로 배치) -->
                    <div class="absolute top-6 right-6 w-6 h-6 rounded-full bg-blue-500 text-white items-center justify-center shadow-lg
                                hidden peer-checked:flex transition-all z-10">
                        <svg class="w-4 h-4" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7" />
                        </svg>
                    </div>
                </label>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 로그인 페이지 배경 -->
        <div class="py-4 border-b dark:border-zinc-700">
            <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-1"><?= __('admin.members.settings.design.login_background') ?></label>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-3"><?= __('admin.members.settings.design.login_background_desc') ?></p>
            <select name="member_login_background" class="w-full md:w-1/3 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <?php $currentBg = $memberSettings['member_login_background'] ?? 'none'; ?>
                <option value="none" <?php echo $currentBg === 'none' ? 'selected' : ''; ?>><?= __('admin.members.settings.design.bg_none') ?></option>
                <option value="gradient" <?php echo $currentBg === 'gradient' ? 'selected' : ''; ?>><?= __('admin.members.settings.design.bg_gradient') ?></option>
                <option value="image" <?php echo $currentBg === 'image' ? 'selected' : ''; ?>><?= __('admin.members.settings.design.bg_image') ?></option>
                <option value="pattern" <?php echo $currentBg === 'pattern' ? 'selected' : ''; ?>><?= __('admin.members.settings.design.bg_pattern') ?></option>
            </select>
        </div>

        <!-- 회원가입 페이지 레이아웃 -->
        <div class="py-4 border-b dark:border-zinc-700">
            <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-1"><?= __('admin.members.settings.design.register_layout') ?></label>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-3"><?= __('admin.members.settings.design.register_layout_desc') ?></p>
            <div class="flex flex-wrap gap-4">
                <?php $currentLayout = $memberSettings['member_register_layout'] ?? 'single'; ?>
                <label class="flex items-center cursor-pointer">
                    <input type="radio" name="member_register_layout" value="single" <?php echo $currentLayout === 'single' ? 'checked' : ''; ?>
                           class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('admin.members.settings.design.layout_single') ?></span>
                </label>
                <label class="flex items-center cursor-pointer">
                    <input type="radio" name="member_register_layout" value="steps" <?php echo $currentLayout === 'steps' ? 'checked' : ''; ?>
                           class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('admin.members.settings.design.layout_steps') ?></span>
                </label>
                <label class="flex items-center cursor-pointer">
                    <input type="radio" name="member_register_layout" value="split" <?php echo $currentLayout === 'split' ? 'checked' : ''; ?>
                           class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('admin.members.settings.design.layout_split') ?></span>
                </label>
            </div>
        </div>

        <!-- 소셜 로그인 기능 -->
        <div class="py-4 border-b dark:border-zinc-700">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h3 class="text-sm font-medium text-zinc-900 dark:text-white"><?= __('admin.members.settings.design.social_login') ?></h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('admin.members.settings.design.social_login_desc') ?></p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <?php $socialLoginEnabled = ($memberSettings['member_social_login_enabled'] ?? '0') === '1'; ?>
                    <input type="checkbox" name="member_social_login_enabled" id="socialLoginToggle" class="sr-only peer" <?= $socialLoginEnabled ? 'checked' : '' ?> onchange="toggleSocialLoginOptions()">
                    <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-blue-600"></div>
                </label>
            </div>

            <!-- 소셜 로그인 제공자 목록 -->
            <div id="socialLoginOptions" class="ml-4 space-y-3 <?= !$socialLoginEnabled ? 'hidden' : '' ?>">
                <!-- Google -->
                <div class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 flex items-center justify-center bg-white dark:bg-zinc-800 rounded-lg shadow-sm">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg">
                                <path fill="#4285F4" d="M22.56 12.25c0-.78-.07-1.53-.2-2.25H12v4.26h5.92c-.26 1.37-1.04 2.53-2.21 3.31v2.77h3.57c2.08-1.92 3.28-4.74 3.28-8.09z"/>
                                <path fill="#34A853" d="M12 23c2.97 0 5.46-.98 7.28-2.66l-3.57-2.77c-.98.66-2.23 1.06-3.71 1.06-2.86 0-5.29-1.93-6.16-4.53H2.18v2.84C3.99 20.53 7.7 23 12 23z"/>
                                <path fill="#FBBC05" d="M5.84 14.09c-.22-.66-.35-1.36-.35-2.09s.13-1.43.35-2.09V7.07H2.18C1.43 8.55 1 10.22 1 12s.43 3.45 1.18 4.93l2.85-2.22.81-.62z"/>
                                <path fill="#EA4335" d="M12 5.38c1.62 0 3.06.56 4.21 1.64l3.15-3.15C17.45 2.09 14.97 1 12 1 7.7 1 3.99 3.47 2.18 7.07l3.66 2.84c.87-2.6 3.3-4.53 6.16-4.53z"/>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('admin.members.settings.design.social_google') ?></span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="member_social_google" class="sr-only peer" <?= ($memberSettings['member_social_google'] ?? '0') === '1' ? 'checked' : '' ?>>
                        <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <!-- LINE -->
                <div class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 flex items-center justify-center bg-[#06C755] rounded-lg shadow-sm">
                            <svg class="w-5 h-5 text-white" viewBox="0 0 24 24" fill="currentColor" xmlns="http://www.w3.org/2000/svg">
                                <path d="M19.365 9.863c.349 0 .63.285.63.631 0 .345-.281.63-.63.63H17.61v1.125h1.755c.349 0 .63.283.63.63 0 .344-.281.629-.63.629h-2.386c-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63h2.386c.346 0 .627.285.627.63 0 .349-.281.63-.63.63H17.61v1.125h1.755zm-3.855 3.016c0 .27-.174.51-.432.596-.064.021-.133.031-.199.031-.211 0-.391-.09-.51-.25l-2.443-3.317v2.94c0 .344-.279.629-.631.629-.346 0-.626-.285-.626-.629V8.108c0-.27.173-.51.43-.595.06-.023.136-.033.194-.033.195 0 .375.104.495.254l2.462 3.33V8.108c0-.345.282-.63.63-.63.345 0 .63.285.63.63v4.771zm-5.741 0c0 .344-.282.629-.631.629-.345 0-.627-.285-.627-.629V8.108c0-.345.282-.63.63-.63.346 0 .628.285.628.63v4.771zm-2.466.629H4.917c-.345 0-.63-.285-.63-.629V8.108c0-.345.285-.63.63-.63.348 0 .63.285.63.63v4.141h1.756c.348 0 .629.283.629.63 0 .344-.282.629-.629.629M24 10.314C24 4.943 18.615.572 12 .572S0 4.943 0 10.314c0 4.811 4.27 8.842 10.035 9.608.391.082.923.258 1.058.59.12.301.079.766.038 1.08l-.164 1.02c-.045.301-.24 1.186 1.049.645 1.291-.539 6.916-4.078 9.436-6.975C23.176 14.393 24 12.458 24 10.314"/>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('admin.members.settings.design.social_line') ?></span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="member_social_line" class="sr-only peer" <?= ($memberSettings['member_social_line'] ?? '0') === '1' ? 'checked' : '' ?>>
                        <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-blue-600"></div>
                    </label>
                </div>

                <!-- KakaoTalk -->
                <div class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg">
                    <div class="flex items-center gap-3">
                        <div class="w-8 h-8 flex items-center justify-center bg-[#FEE500] rounded-lg shadow-sm">
                            <svg class="w-5 h-5" viewBox="0 0 24 24" fill="#000000" xmlns="http://www.w3.org/2000/svg">
                                <path d="M12 3c-5.52 0-10 3.59-10 8.03 0 2.82 1.86 5.3 4.67 6.71-.15.53-.96 3.39-1 3.56 0 0-.02.09.05.13.07.04.14.01.14.01.18-.03 2.15-1.42 3.13-2.1.65.1 1.32.15 2.01.15 5.52 0 10-3.59 10-8.03S17.52 3 12 3z"/>
                            </svg>
                        </div>
                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('admin.members.settings.design.social_kakao') ?></span>
                    </div>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" name="member_social_kakao" class="sr-only peer" <?= ($memberSettings['member_social_kakao'] ?? '0') === '1' ? 'checked' : '' ?>>
                        <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-blue-600"></div>
                    </label>
                </div>
            </div>
        </div>

        <!-- 프로필 페이지 스타일 -->
        <div class="py-4">
            <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-1"><?= __('admin.members.settings.design.profile_style') ?></label>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-3"><?= __('admin.members.settings.design.profile_style_desc') ?></p>
            <select name="member_profile_style" class="w-full md:w-1/3 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <?php $currentProfile = $memberSettings['member_profile_style'] ?? 'card'; ?>
                <option value="card" <?php echo $currentProfile === 'card' ? 'selected' : ''; ?>><?= __('admin.members.settings.design.profile_card') ?></option>
                <option value="sidebar" <?php echo $currentProfile === 'sidebar' ? 'selected' : ''; ?>><?= __('admin.members.settings.design.profile_sidebar') ?></option>
                <option value="tabs" <?php echo $currentProfile === 'tabs' ? 'selected' : ''; ?>><?= __('admin.members.settings.design.profile_tabs') ?></option>
            </select>
        </div>

        <div class="flex justify-end pt-4 border-t dark:border-zinc-700">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                <?= __('admin.buttons.save') ?>
            </button>
        </div>
    </form>
</div>

<script>
function toggleSocialLoginOptions() {
    const enabled = document.getElementById('socialLoginToggle').checked;
    const options = document.getElementById('socialLoginOptions');
    if (enabled) {
        options.classList.remove('hidden');
    } else {
        options.classList.add('hidden');
    }
}

// 신규 스킨 추가 드롭다운 토글
(function() {
    var addSkinBtn = document.getElementById('addSkinBtn');
    var addSkinDropdown = document.getElementById('addSkinDropdown');
    var addSkinContainer = document.getElementById('addSkinContainer');

    if (addSkinBtn && addSkinDropdown) {
        addSkinBtn.addEventListener('click', function(e) {
            e.stopPropagation();
            addSkinDropdown.classList.toggle('hidden');
            console.log('Add skin dropdown toggled');
        });

        // 외부 클릭 시 드롭다운 닫기
        document.addEventListener('click', function(e) {
            if (addSkinContainer && !addSkinContainer.contains(e.target)) {
                addSkinDropdown.classList.add('hidden');
            }
        });
    }
})();

// 직접 등록 모달 표시
function showDirectRegisterModal() {
    // 드롭다운 닫기
    document.getElementById('addSkinDropdown').classList.add('hidden');

    // TODO: 직접 등록 모달 구현
    alert('직접 등록 기능은 준비 중입니다.\n스킨 ZIP 파일 업로드 기능이 추가될 예정입니다.');
    console.log('Direct register modal requested');
}

// 마켓플레이스 이동
function goToMarketplace() {
    // 드롭다운 닫기
    document.getElementById('addSkinDropdown').classList.add('hidden');

    // TODO: 마켓플레이스 URL 설정
    alert('마켓플레이스 기능은 준비 중입니다.\n다양한 스킨을 구매할 수 있는 마켓플레이스가 추가될 예정입니다.');
    console.log('Marketplace navigation requested');
}
</script>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/_layout.php';
