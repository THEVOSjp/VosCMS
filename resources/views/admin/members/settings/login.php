<?php
/**
 * RezlyX Admin Members Settings - Login
 * Login settings configuration
 */

require_once __DIR__ . '/_init.php';

$pageTitle = __('admin.members.settings.tabs.login') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$currentMemberSettingsPage = 'login';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_login') {
        try {
            $stmt = $pdo->prepare("INSERT INTO {$prefix}settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");

            // 로그인 방식
            $loginMethod = $_POST['member_login_method'] ?? 'email';
            $stmt->execute(['member_login_method', $loginMethod]);
            $memberSettings['member_login_method'] = $loginMethod;

            // 로그인 상태 유지
            $rememberMe = isset($_POST['member_remember_me']) ? '1' : '0';
            $stmt->execute(['member_remember_me', $rememberMe]);
            $memberSettings['member_remember_me'] = $rememberMe;

            // 로그인 시도 제한
            $loginAttempts = intval($_POST['member_login_attempts'] ?? 5);
            $stmt->execute(['member_login_attempts', (string)$loginAttempts]);
            $memberSettings['member_login_attempts'] = (string)$loginAttempts;

            // 잠금 시간
            $lockoutDuration = intval($_POST['member_lockout_duration'] ?? 30);
            $stmt->execute(['member_lockout_duration', (string)$lockoutDuration]);
            $memberSettings['member_lockout_duration'] = (string)$lockoutDuration;

            // 무한 대입 방지
            $bruteForce = isset($_POST['member_brute_force']) ? '1' : '0';
            $stmt->execute(['member_brute_force', $bruteForce]);
            $memberSettings['member_brute_force'] = $bruteForce;

            $bruteForceAttempts = intval($_POST['member_brute_force_attempts'] ?? 10);
            $stmt->execute(['member_brute_force_attempts', (string)$bruteForceAttempts]);
            $memberSettings['member_brute_force_attempts'] = (string)$bruteForceAttempts;

            $bruteForceSeconds = intval($_POST['member_brute_force_seconds'] ?? 300);
            $stmt->execute(['member_brute_force_seconds', (string)$bruteForceSeconds]);
            $memberSettings['member_brute_force_seconds'] = (string)$bruteForceSeconds;

            // 다른 기기 로그아웃
            $singleDevice = isset($_POST['member_single_device']) ? '1' : '0';
            $stmt->execute(['member_single_device', $singleDevice]);
            $memberSettings['member_single_device'] = $singleDevice;

            // 로그인 후 이동 URL
            $loginRedirectUrl = trim($_POST['member_login_redirect_url'] ?? '');
            $stmt->execute(['member_login_redirect_url', $loginRedirectUrl]);
            $memberSettings['member_login_redirect_url'] = $loginRedirectUrl;

            // 로그아웃 후 이동 URL
            $logoutRedirectUrl = trim($_POST['member_logout_redirect_url'] ?? '');
            $stmt->execute(['member_logout_redirect_url', $logoutRedirectUrl]);
            $memberSettings['member_logout_redirect_url'] = $logoutRedirectUrl;

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
    <?php
    $headerIcon = 'M11 16l-4-4m0 0l4-4m-4 4h14m-5 4v1a3 3 0 01-3 3H6a3 3 0 01-3-3V7a3 3 0 013-3h7a3 3 0 013 3v1';
    $headerTitle = __('admin.members.settings.login.title');
    $headerDescription = __('admin.members.settings.login.description');
    $headerIconColor = ''; $headerActions = '';
    include __DIR__ . '/../../components/settings-header.php';
    ?>

    <form method="POST" class="space-y-6">
        <input type="hidden" name="action" value="update_login">

        <!-- 로그인 방식 -->
        <div class="py-4 border-b dark:border-zinc-700">
            <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-1"><?= __('admin.members.settings.login.method') ?></label>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-3"><?= __('admin.members.settings.login.method_desc') ?></p>
            <div class="flex flex-wrap gap-4">
                <?php $currentMethod = $memberSettings['member_login_method'] ?? 'email'; ?>
                <label class="flex items-center cursor-pointer">
                    <input type="radio" name="member_login_method" value="email"
                           <?php echo $currentMethod === 'email' ? 'checked' : ''; ?>
                           class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('admin.members.settings.login.method_email') ?></span>
                </label>
                <label class="flex items-center cursor-pointer">
                    <input type="radio" name="member_login_method" value="phone"
                           <?php echo $currentMethod === 'phone' ? 'checked' : ''; ?>
                           class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('admin.members.settings.login.method_phone') ?></span>
                </label>
                <label class="flex items-center cursor-pointer">
                    <input type="radio" name="member_login_method" value="both"
                           <?php echo $currentMethod === 'both' ? 'checked' : ''; ?>
                           class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('admin.members.settings.login.method_both') ?></span>
                </label>
            </div>
        </div>

        <!-- 로그인 상태 유지 -->
        <div class="flex items-center justify-between py-4 border-b dark:border-zinc-700">
            <div>
                <h3 class="text-sm font-medium text-zinc-900 dark:text-white"><?= __('admin.members.settings.login.remember_me') ?></h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('admin.members.settings.login.remember_me_desc') ?></p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="member_remember_me" class="sr-only peer" <?php echo ($memberSettings['member_remember_me'] ?? '1') === '1' ? 'checked' : ''; ?>>
                <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-blue-600"></div>
            </label>
        </div>

        <!-- 로그인 시도 제한 -->
        <div class="py-4 border-b dark:border-zinc-700">
            <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-1"><?= __('admin.members.settings.login.attempts') ?></label>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-3"><?= __('admin.members.settings.login.attempts_desc') ?></p>
            <select name="member_login_attempts" class="w-full md:w-1/4 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="0" <?php echo ($memberSettings['member_login_attempts'] ?? '5') === '0' ? 'selected' : ''; ?>><?= __('admin.members.settings.login.unlimited') ?></option>
                <?php for ($i = 3; $i <= 10; $i++): ?>
                <option value="<?php echo $i; ?>" <?php echo ($memberSettings['member_login_attempts'] ?? '5') == $i ? 'selected' : ''; ?>><?php echo $i; ?> <?= __('admin.members.settings.login.times') ?></option>
                <?php endfor; ?>
            </select>
        </div>

        <!-- 잠금 시간 -->
        <div class="py-4 border-b dark:border-zinc-700">
            <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-1"><?= __('admin.members.settings.login.lockout') ?></label>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-3"><?= __('admin.members.settings.login.lockout_desc') ?></p>
            <select name="member_lockout_duration" class="w-full md:w-1/4 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <option value="5" <?php echo ($memberSettings['member_lockout_duration'] ?? '30') === '5' ? 'selected' : ''; ?>>5 <?= __('admin.members.settings.login.minutes') ?></option>
                <option value="15" <?php echo ($memberSettings['member_lockout_duration'] ?? '30') === '15' ? 'selected' : ''; ?>>15 <?= __('admin.members.settings.login.minutes') ?></option>
                <option value="30" <?php echo ($memberSettings['member_lockout_duration'] ?? '30') === '30' ? 'selected' : ''; ?>>30 <?= __('admin.members.settings.login.minutes') ?></option>
                <option value="60" <?php echo ($memberSettings['member_lockout_duration'] ?? '30') === '60' ? 'selected' : ''; ?>>1 <?= __('admin.members.settings.login.hour') ?></option>
                <option value="1440" <?php echo ($memberSettings['member_lockout_duration'] ?? '30') === '1440' ? 'selected' : ''; ?>>24 <?= __('admin.members.settings.login.hours') ?></option>
            </select>
        </div>

        <!-- 무한 대입 방지 -->
        <div class="py-4 border-b dark:border-zinc-700">
            <div class="flex items-center justify-between mb-3">
                <div>
                    <h3 class="text-sm font-medium text-zinc-900 dark:text-white"><?= __('admin.members.settings.login.brute_force') ?></h3>
                    <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('admin.members.settings.login.brute_force_desc') ?></p>
                </div>
                <div class="flex items-center space-x-4">
                    <?php $bruteForceEnabled = ($memberSettings['member_brute_force'] ?? '1') === '1'; ?>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="member_brute_force" value="1" <?= $bruteForceEnabled ? 'checked' : '' ?> onchange="toggleBruteForceOptions()" class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('admin.common.yes') ?></span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="member_brute_force" value="0" <?= !$bruteForceEnabled ? 'checked' : '' ?> onchange="toggleBruteForceOptions()" class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('admin.common.no') ?></span>
                    </label>
                </div>
            </div>
            <div id="bruteForceOptions" class="flex items-center gap-2 <?= !$bruteForceEnabled ? 'hidden' : '' ?>">
                <input type="number" name="member_brute_force_attempts" value="<?= htmlspecialchars($memberSettings['member_brute_force_attempts'] ?? '10') ?>" min="1" max="100" class="w-20 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-center">
                <span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('admin.members.settings.login.times') ?> /</span>
                <input type="number" name="member_brute_force_seconds" value="<?= htmlspecialchars($memberSettings['member_brute_force_seconds'] ?? '300') ?>" min="1" max="3600" class="w-24 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 text-center">
                <span class="text-sm text-zinc-700 dark:text-zinc-300"><?= __('admin.members.settings.login.seconds') ?></span>
            </div>
        </div>

        <!-- 다른 기기 로그아웃 -->
        <div class="flex items-center justify-between py-4 border-b dark:border-zinc-700">
            <div>
                <h3 class="text-sm font-medium text-zinc-900 dark:text-white"><?= __('admin.members.settings.login.single_device') ?></h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('admin.members.settings.login.single_device_desc') ?></p>
            </div>
            <div class="flex items-center space-x-4">
                <?php $singleDevice = ($memberSettings['member_single_device'] ?? '0') === '1'; ?>
                <label class="flex items-center cursor-pointer">
                    <input type="radio" name="member_single_device" value="1" <?= $singleDevice ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('admin.common.yes') ?></span>
                </label>
                <label class="flex items-center cursor-pointer">
                    <input type="radio" name="member_single_device" value="0" <?= !$singleDevice ? 'checked' : '' ?> class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500">
                    <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('admin.common.no') ?></span>
                </label>
            </div>
        </div>

        <!-- 로그인 후 이동 URL -->
        <div class="py-4 border-b dark:border-zinc-700">
            <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-1"><?= __('admin.members.settings.login.login_redirect_url') ?></label>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-3"><?= __('admin.members.settings.login.login_redirect_url_desc') ?></p>
            <input type="text" name="member_login_redirect_url"
                   value="<?= htmlspecialchars($memberSettings['member_login_redirect_url'] ?? '') ?>"
                   placeholder="<?= __('admin.members.settings.login.redirect_url_placeholder') ?>"
                   class="w-full md:w-1/2 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>

        <!-- 로그아웃 후 이동 URL -->
        <div class="py-4">
            <label class="block text-sm font-medium text-zinc-900 dark:text-white mb-1"><?= __('admin.members.settings.login.logout_redirect_url') ?></label>
            <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-3"><?= __('admin.members.settings.login.logout_redirect_url_desc') ?></p>
            <input type="text" name="member_logout_redirect_url"
                   value="<?= htmlspecialchars($memberSettings['member_logout_redirect_url'] ?? '') ?>"
                   placeholder="<?= __('admin.members.settings.login.redirect_url_placeholder') ?>"
                   class="w-full md:w-1/2 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
        </div>

        <div class="flex justify-end pt-4 border-t dark:border-zinc-700">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                <?= __('admin.buttons.save') ?>
            </button>
        </div>
    </form>
</div>

<script>
function toggleBruteForceOptions() {
    const enabled = document.querySelector('input[name="member_brute_force"]:checked').value === '1';
    const options = document.getElementById('bruteForceOptions');
    if (enabled) {
        options.classList.remove('hidden');
    } else {
        options.classList.add('hidden');
    }
}
</script>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/_layout.php';
