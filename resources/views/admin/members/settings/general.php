<?php
/**
 * RezlyX Admin Members Settings - General
 * Basic member settings configuration
 */

require_once __DIR__ . '/_init.php';

$pageTitle = __('members.settings.tabs.general') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$currentMemberSettingsPage = 'general';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_general') {
        try {
            $stmt = $pdo->prepare("INSERT INTO {$prefix}settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");

            // 회원가입 허용 방식
            $registrationMode = $_POST['member_registration_mode'] ?? 'yes';
            $stmt->execute(['member_registration_mode', $registrationMode]);
            $memberSettings['member_registration_mode'] = $registrationMode;

            // URL 키 (회원가입 허용이 'url_key'인 경우)
            $urlKey = trim($_POST['member_registration_url_key'] ?? '');
            $stmt->execute(['member_registration_url_key', $urlKey]);
            $memberSettings['member_registration_url_key'] = $urlKey;

            // 이메일 인증
            $emailVerification = isset($_POST['member_email_verification']) ? '1' : '0';
            $stmt->execute(['member_email_verification', $emailVerification]);
            $memberSettings['member_email_verification'] = $emailVerification;

            // 인증 메일 유효기간
            $emailValidityDays = intval($_POST['member_email_validity_days'] ?? 1);
            $stmt->execute(['member_email_validity_days', (string)$emailValidityDays]);
            $memberSettings['member_email_validity_days'] = (string)$emailValidityDays;

            // 회원 프로필사진 보이기
            $showProfilePhoto = isset($_POST['member_show_profile_photo']) ? '1' : '0';
            $stmt->execute(['member_show_profile_photo', $showProfilePhoto]);
            $memberSettings['member_show_profile_photo'] = $showProfilePhoto;

            // 비번 변경시 다른 기기 로그아웃
            $logoutOnPasswordChange = isset($_POST['member_logout_on_password_change']) ? '1' : '0';
            $stmt->execute(['member_logout_on_password_change', $logoutOnPasswordChange]);
            $memberSettings['member_logout_on_password_change'] = $logoutOnPasswordChange;

            // ID/PW 찾기 방법
            $passwordRecoveryMethod = $_POST['member_password_recovery_method'] ?? 'link';
            $stmt->execute(['member_password_recovery_method', $passwordRecoveryMethod]);
            $memberSettings['member_password_recovery_method'] = $passwordRecoveryMethod;

            $message = __('settings.success');
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = __('settings.error_save') . ': ' . $e->getMessage();
            $messageType = 'error';
        }
    } elseif ($action === 'sync_member_data') {
        try {
            // 회원정보 동기화 로직
            // TODO: 실제 동기화 로직 구현
            $message = __('members.settings.general.sync_complete');
            $messageType = 'success';
        } catch (Exception $e) {
            $message = __('members.settings.general.sync_error') . ': ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

ob_start();
?>

<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 transition-colors mb-6">
    <?php
    $headerIcon = 'M12 4.354a4 4 0 110 5.292M15 21H3v-1a6 6 0 0112 0v1zm0 0h6v-1a6 6 0 00-9-5.197M13 7a4 4 0 11-8 0 4 4 0 018 0z';
    $headerTitle = __('members.settings.general.title');
    $headerDescription = __('members.settings.general.description');
    $headerIconColor = ''; $headerActions = '';
    include __DIR__ . '/../../components/settings-header.php';
    ?>

    <form method="POST" class="space-y-6">
        <input type="hidden" name="action" value="update_general">

        <!-- 회원 가입 허가 -->
        <div class="py-4 border-b dark:border-zinc-700">
            <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-1"><?= __('members.settings.general.registration_mode') ?></label>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-3"><?= __('members.settings.general.registration_mode_desc') ?></p>
            <div class="flex flex-wrap gap-4 mb-3">
                <?php $currentMode = $memberSettings['member_registration_mode'] ?? 'yes'; ?>
                <label class="flex items-center cursor-pointer">
                    <input type="radio" name="member_registration_mode" value="yes"
                           <?php echo $currentMode === 'yes' ? 'checked' : ''; ?>
                           class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500"
                           onchange="toggleUrlKeyField()">
                    <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('admin.common.yes') ?></span>
                </label>
                <label class="flex items-center cursor-pointer">
                    <input type="radio" name="member_registration_mode" value="no"
                           <?php echo $currentMode === 'no' ? 'checked' : ''; ?>
                           class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500"
                           onchange="toggleUrlKeyField()">
                    <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('admin.common.no') ?></span>
                </label>
                <label class="flex items-center cursor-pointer">
                    <input type="radio" name="member_registration_mode" value="url_key"
                           <?php echo $currentMode === 'url_key' ? 'checked' : ''; ?>
                           class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500"
                           onchange="toggleUrlKeyField()">
                    <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('members.settings.general.registration_url_key') ?></span>
                </label>
            </div>
            <!-- URL 키 입력 필드 -->
            <div id="urlKeyField" class="<?php echo $currentMode === 'url_key' ? '' : 'hidden'; ?> mt-3 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('members.settings.general.url_key_label') ?></label>
                <input type="text" name="member_registration_url_key"
                       value="<?php echo htmlspecialchars($memberSettings['member_registration_url_key'] ?? ''); ?>"
                       class="w-full md:w-1/2 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="<?= __('members.settings.general.url_key_placeholder') ?>">
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-2"><?= __('members.settings.general.url_key_hint') ?></p>
            </div>
        </div>

        <!-- 메일 인증 사용 -->
        <div class="flex items-center justify-between py-4 border-b dark:border-zinc-700">
            <div>
                <h3 class="text-sm font-medium text-zinc-900 dark:text-white"><?= __('members.settings.general.email_verification') ?></h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('members.settings.general.email_verification_desc') ?></p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="member_email_verification" class="sr-only peer" <?php echo ($memberSettings['member_email_verification'] ?? '1') === '1' ? 'checked' : ''; ?>>
                <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-blue-600"></div>
            </label>
        </div>

        <!-- 인증 메일 유효기간 -->
        <div class="py-4 border-b dark:border-zinc-700">
            <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-1"><?= __('members.settings.general.email_validity') ?></label>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-3"><?= __('members.settings.general.email_validity_desc') ?></p>
            <div class="flex items-center space-x-2">
                <input type="number" name="member_email_validity_days"
                       value="<?php echo htmlspecialchars($memberSettings['member_email_validity_days'] ?? '1'); ?>"
                       min="1" max="30"
                       class="w-20 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-center">
                <span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('members.settings.general.days') ?></span>
            </div>
        </div>

        <!-- 회원 프로필사진 보이기 -->
        <div class="flex items-center justify-between py-4 border-b dark:border-zinc-700">
            <div>
                <h3 class="text-sm font-medium text-zinc-900 dark:text-white"><?= __('members.settings.general.show_profile_photo') ?></h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('members.settings.general.show_profile_photo_desc') ?></p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="member_show_profile_photo" class="sr-only peer" <?php echo ($memberSettings['member_show_profile_photo'] ?? '1') === '1' ? 'checked' : ''; ?>>
                <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-blue-600"></div>
            </label>
        </div>

        <!-- 비번 변경시 다른 기기 로그아웃 -->
        <div class="flex items-center justify-between py-4 border-b dark:border-zinc-700">
            <div>
                <h3 class="text-sm font-medium text-zinc-900 dark:text-white"><?= __('members.settings.general.logout_on_password_change') ?></h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('members.settings.general.logout_on_password_change_desc') ?></p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="member_logout_on_password_change" class="sr-only peer" <?php echo ($memberSettings['member_logout_on_password_change'] ?? '1') === '1' ? 'checked' : ''; ?>>
                <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-blue-600"></div>
            </label>
        </div>

        <!-- ID/PW 찾기 방법 -->
        <div class="py-4">
            <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-1"><?= __('members.settings.general.password_recovery_method') ?></label>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-3"><?= __('members.settings.general.password_recovery_method_desc') ?></p>
            <div class="flex flex-wrap gap-4">
                <?php $currentMethod = $memberSettings['member_password_recovery_method'] ?? 'link'; ?>
                <label class="flex items-center cursor-pointer">
                    <input type="radio" name="member_password_recovery_method" value="link"
                           <?php echo $currentMethod === 'link' ? 'checked' : ''; ?>
                           class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('members.settings.general.recovery_link') ?></span>
                    <span class="ml-1 text-xs text-green-600 dark:text-green-400">(<?= __('admin.common.recommended') ?>)</span>
                </label>
                <label class="flex items-center cursor-pointer">
                    <input type="radio" name="member_password_recovery_method" value="random"
                           <?php echo $currentMethod === 'random' ? 'checked' : ''; ?>
                           class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('members.settings.general.recovery_random') ?></span>
                </label>
            </div>
        </div>

        <div class="flex justify-end pt-4 border-t dark:border-zinc-700">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                <?= __('admin.buttons.save') ?>
            </button>
        </div>
    </form>
</div>

<!-- 회원정보 동기화 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 transition-colors">
    <?php
    $headerIcon = 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15';
    $headerTitle = __('members.settings.general.sync_title');
    $headerDescription = ''; $headerActions = '';
    $headerIconColor = 'text-amber-600';
    include __DIR__ . '/../../components/settings-header.php';
    ?>
    <form method="POST" onsubmit="return confirmSync()">
        <input type="hidden" name="action" value="sync_member_data">
        <div class="flex items-center justify-between">
            <p class="text-sm text-zinc-600 dark:text-zinc-400"><?= __('members.settings.general.sync_description') ?></p>
            <button type="submit" class="px-4 py-2 bg-amber-600 text-white font-medium rounded-lg hover:bg-amber-700 transition flex items-center">
                <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <?= __('members.settings.general.sync_button') ?>
            </button>
        </div>
        <p class="mt-3 text-xs text-amber-600 dark:text-amber-400">
            <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
            <?= __('members.settings.general.sync_warning') ?>
        </p>
    </form>
</div>

<script>
    function toggleUrlKeyField() {
        const urlKeyMode = document.querySelector('input[name="member_registration_mode"][value="url_key"]');
        const urlKeyField = document.getElementById('urlKeyField');
        if (urlKeyMode && urlKeyField) {
            if (urlKeyMode.checked) {
                urlKeyField.classList.remove('hidden');
            } else {
                urlKeyField.classList.add('hidden');
            }
        }
        console.log('Registration mode changed');
    }

    function confirmSync() {
        return confirm('<?= __('members.settings.general.sync_confirm') ?>');
    }
</script>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/_layout.php';
