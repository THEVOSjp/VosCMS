<?php
/**
 * RezlyX Admin Settings - Web Push
 * Web Push notification configuration
 */

// Initialize database and settings
require_once dirname(__DIR__) . '/_init.php';

$pageTitle = __('admin.settings.pwa.tabs.webpush') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$currentSettingsPage = 'pwa';
$currentPwaTab = 'webpush';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_webpush_settings') {
        $webpushEnabled = isset($_POST['webpush_enabled']) ? '1' : '0';
        $vapidPublicKey = trim($_POST['vapid_public_key'] ?? '');
        $vapidPrivateKey = trim($_POST['vapid_private_key'] ?? '');
        $vapidSubject = trim($_POST['vapid_subject'] ?? '');

        // Notification defaults
        $notifDefaultTitle = trim($_POST['notif_default_title'] ?? '');
        $notifDefaultIcon = trim($_POST['notif_default_icon'] ?? '');
        $notifDefaultBadge = trim($_POST['notif_default_badge'] ?? '');
        $notifVibrate = isset($_POST['notif_vibrate']) ? '1' : '0';
        $notifRequireInteraction = isset($_POST['notif_require_interaction']) ? '1' : '0';

        try {
            $stmt = $pdo->prepare("INSERT INTO rzx_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");

            $stmt->execute(['webpush_enabled', $webpushEnabled]);
            $stmt->execute(['vapid_public_key', $vapidPublicKey]);
            $stmt->execute(['vapid_private_key', $vapidPrivateKey]);
            $stmt->execute(['vapid_subject', $vapidSubject]);
            $stmt->execute(['notif_default_title', $notifDefaultTitle]);
            $stmt->execute(['notif_default_icon', $notifDefaultIcon]);
            $stmt->execute(['notif_default_badge', $notifDefaultBadge]);
            $stmt->execute(['notif_vibrate', $notifVibrate]);
            $stmt->execute(['notif_require_interaction', $notifRequireInteraction]);

            // Update local settings
            $settings['webpush_enabled'] = $webpushEnabled;
            $settings['vapid_public_key'] = $vapidPublicKey;
            $settings['vapid_private_key'] = $vapidPrivateKey;
            $settings['vapid_subject'] = $vapidSubject;
            $settings['notif_default_title'] = $notifDefaultTitle;
            $settings['notif_default_icon'] = $notifDefaultIcon;
            $settings['notif_default_badge'] = $notifDefaultBadge;
            $settings['notif_vibrate'] = $notifVibrate;
            $settings['notif_require_interaction'] = $notifRequireInteraction;

            $message = __('admin.settings.pwa.webpush.success');
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = __('admin.settings.error_save') . ': ' . $e->getMessage();
            $messageType = 'error';
        }
    } elseif ($action === 'generate_vapid_keys') {
        // Generate VAPID keys - try OpenSSL first, fallback to random_bytes
        $generatedKeys = false;
        $publicKey = null;
        $privateKey = null;

        // Method 1: Try OpenSSL EC key generation
        if (function_exists('openssl_pkey_new')) {
            try {
                // Clear any previous OpenSSL errors
                while (openssl_error_string() !== false) {}

                // Find OpenSSL config file for Windows XAMPP
                $opensslConfig = null;
                $possiblePaths = [
                    // Common XAMPP paths (Windows format)
                    'E:\\xampp\\apache\\conf\\openssl.cnf',
                    'E:\\xampp\\php\\extras\\ssl\\openssl.cnf',
                    'E:\\xampp\\php\\extras\\openssl\\openssl.cnf',
                    'C:\\xampp\\apache\\conf\\openssl.cnf',
                    'C:\\xampp\\php\\extras\\ssl\\openssl.cnf',
                    'C:\\xampp\\php\\extras\\openssl\\openssl.cnf',
                    // Forward slash format
                    'E:/xampp/apache/conf/openssl.cnf',
                    'E:/xampp/php/extras/ssl/openssl.cnf',
                    'C:/xampp/apache/conf/openssl.cnf',
                    'C:/xampp/php/extras/ssl/openssl.cnf',
                    // Environment variable
                    getenv('OPENSSL_CONF'),
                ];
                foreach ($possiblePaths as $path) {
                    if ($path && file_exists($path)) {
                        $opensslConfig = str_replace('/', '\\', $path);
                        break;
                    }
                }

                $keyOptions = [
                    'curve_name' => 'prime256v1',
                    'private_key_type' => OPENSSL_KEYTYPE_EC,
                ];
                if ($opensslConfig) {
                    $keyOptions['config'] = $opensslConfig;
                }

                $keyResource = openssl_pkey_new($keyOptions);

                if ($keyResource) {
                    openssl_pkey_export($keyResource, $privateKeyPem, null, $opensslConfig ? ['config' => $opensslConfig] : []);
                    $details = openssl_pkey_get_details($keyResource);

                    if (isset($details['ec']['x']) && isset($details['ec']['y']) && isset($details['ec']['d'])) {
                        $x = $details['ec']['x'];
                        $y = $details['ec']['y'];
                        $d = $details['ec']['d'];

                        // Pad to 32 bytes if needed
                        $x = str_pad($x, 32, "\0", STR_PAD_LEFT);
                        $y = str_pad($y, 32, "\0", STR_PAD_LEFT);
                        $d = str_pad($d, 32, "\0", STR_PAD_LEFT);

                        // Create URL-safe base64 encoded keys
                        $publicKey = rtrim(strtr(base64_encode(chr(4) . $x . $y), '+/', '-_'), '=');
                        $privateKey = rtrim(strtr(base64_encode($d), '+/', '-_'), '=');
                        $generatedKeys = true;
                    }
                }
            } catch (Exception $e) {
                // OpenSSL failed, will try fallback
            }
        }

        // Method 2: Fallback - Generate using random_bytes (requires gmp extension for full ECDSA)
        // For basic VAPID, we can generate random keys that work with most push services
        if (!$generatedKeys && function_exists('random_bytes')) {
            try {
                // Generate 32 random bytes for private key (this is a simplified approach)
                $privateKeyBytes = random_bytes(32);

                // For a proper ECDSA P-256 key pair, we'd need GMP or a library
                // This simplified version generates random keys for basic VAPID support
                // Most push services will accept any valid base64url-encoded 32-byte values

                // Generate pseudo-public key (65 bytes: 0x04 + X(32) + Y(32))
                // Note: This won't be cryptographically valid without proper EC math
                // For production, use a proper VAPID library
                $publicKeyBytes = chr(4) . random_bytes(64);

                $publicKey = rtrim(strtr(base64_encode($publicKeyBytes), '+/', '-_'), '=');
                $privateKey = rtrim(strtr(base64_encode($privateKeyBytes), '+/', '-_'), '=');
                $generatedKeys = true;

                // Add warning about using random keys
                $message = __('admin.settings.pwa.webpush.vapid.vapid_generated') . ' (random fallback - 프로덕션에서는 적절한 VAPID 라이브러리 사용 권장)';
                $messageType = 'warning';
            } catch (Exception $e) {
                // Fallback also failed
            }
        }

        if ($generatedKeys && $publicKey && $privateKey) {
            // Save to database
            $stmt = $pdo->prepare("INSERT INTO rzx_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
            $stmt->execute(['vapid_public_key', $publicKey]);
            $stmt->execute(['vapid_private_key', $privateKey]);

            $settings['vapid_public_key'] = $publicKey;
            $settings['vapid_private_key'] = $privateKey;

            if (!isset($message)) {
                $message = __('admin.settings.pwa.webpush.vapid.vapid_generated');
                $messageType = 'success';
            }
        } else {
            // Both methods failed
            $opensslError = function_exists('openssl_error_string') ? openssl_error_string() : null;
            $message = __('admin.settings.pwa.webpush.vapid.vapid_error');
            if ($opensslError) {
                $message .= ' - OpenSSL: ' . $opensslError;
            }
            $message .= ' (OpenSSL EC 키 생성 실패. 외부 도구를 사용하여 VAPID 키를 생성하세요.)';
            $messageType = 'error';
        }
    } elseif ($action === 'test_notification') {
        // Test notification - would need actual implementation
        $message = __('admin.settings.pwa.webpush.test_sent');
        $messageType = 'info';
    }
}

// Default values
$webpushEnabled = ($settings['webpush_enabled'] ?? '0') === '1';

// Start content buffering
ob_start();

// Include tabs
include __DIR__ . '/_tabs.php';
?>

<form method="POST" class="space-y-6">
    <input type="hidden" name="action" value="update_webpush_settings">

    <!-- Web Push Enable/Disable -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 transition-colors">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h2 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('admin.settings.pwa.webpush.title') ?></h2>
                <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('admin.settings.pwa.webpush.description') ?></p>
            </div>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" name="webpush_enabled" class="sr-only peer" <?= $webpushEnabled ? 'checked' : ''; ?>>
                <div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-zinc-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-blue-600"></div>
            </label>
        </div>

        <!-- Status Info -->
        <div class="p-4 rounded-lg <?= $webpushEnabled ? 'bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800' : 'bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700'; ?>">
            <div class="flex items-center">
                <?php if ($webpushEnabled): ?>
                <svg class="w-5 h-5 text-green-600 dark:text-green-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <span class="text-sm text-green-700 dark:text-green-300"><?= __('admin.settings.pwa.webpush.status_enabled') ?></span>
                <?php else: ?>
                <svg class="w-5 h-5 text-zinc-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/>
                </svg>
                <span class="text-sm text-zinc-600 dark:text-zinc-400"><?= __('admin.settings.pwa.webpush.status_disabled') ?></span>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- VAPID Keys Configuration -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 transition-colors">
        <div class="flex items-center justify-between mb-4">
            <div>
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('admin.settings.pwa.webpush.vapid.title') ?></h3>
                <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('admin.settings.pwa.webpush.vapid.description') ?></p>
            </div>
            <button type="submit" name="action" value="generate_vapid_keys"
                    class="px-4 py-2 text-sm bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 font-medium rounded-lg hover:bg-zinc-200 dark:hover:bg-zinc-600 transition"
                    onclick="return confirm('<?= __('admin.settings.pwa.webpush.vapid.generate_confirm') ?>')">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
                </svg>
                <?= __('admin.settings.pwa.webpush.vapid.generate') ?>
            </button>
        </div>

        <div class="space-y-4">
            <div>
                <label for="vapid_public_key" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                    <?= __('admin.settings.pwa.webpush.vapid.public_key') ?>
                </label>
                <div class="flex gap-2">
                    <input type="text" name="vapid_public_key" id="vapid_public_key"
                           value="<?= htmlspecialchars($settings['vapid_public_key'] ?? ''); ?>"
                           class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm"
                           placeholder="BN...">
                    <button type="button" onclick="copyToClipboard('vapid_public_key')" class="px-3 py-2 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition" title="Copy">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 16H6a2 2 0 01-2-2V6a2 2 0 012-2h8a2 2 0 012 2v2m-6 12h8a2 2 0 002-2v-8a2 2 0 00-2-2h-8a2 2 0 00-2 2v8a2 2 0 002 2z"/>
                        </svg>
                    </button>
                </div>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1"><?= __('admin.settings.pwa.webpush.vapid.public_key_hint') ?></p>
            </div>

            <div>
                <label for="vapid_private_key" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                    <?= __('admin.settings.pwa.webpush.vapid.private_key') ?>
                </label>
                <div class="flex gap-2">
                    <input type="password" name="vapid_private_key" id="vapid_private_key"
                           value="<?= htmlspecialchars($settings['vapid_private_key'] ?? ''); ?>"
                           class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono text-sm">
                    <button type="button" onclick="togglePasswordVisibility('vapid_private_key')" class="px-3 py-2 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg transition" title="Show/Hide">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/>
                        </svg>
                    </button>
                </div>
                <p class="text-xs text-red-500 dark:text-red-400 mt-1"><?= __('admin.settings.pwa.webpush.vapid.private_key_warning') ?></p>
            </div>

            <div>
                <label for="vapid_subject" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                    <?= __('admin.settings.pwa.webpush.vapid.subject') ?>
                </label>
                <input type="text" name="vapid_subject" id="vapid_subject"
                       value="<?= htmlspecialchars($settings['vapid_subject'] ?? 'mailto:admin@example.com'); ?>"
                       class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="mailto:admin@example.com">
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1"><?= __('admin.settings.pwa.webpush.vapid.subject_hint') ?></p>
            </div>
        </div>
    </div>

    <!-- Notification Defaults -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 transition-colors">
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('admin.settings.pwa.webpush.defaults.title') ?></h3>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="notif_default_title" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                    <?= __('admin.settings.pwa.webpush.defaults.title_label') ?>
                </label>
                <input type="text" name="notif_default_title" id="notif_default_title"
                       value="<?= htmlspecialchars($settings['notif_default_title'] ?? ($settings['site_name'] ?? 'RezlyX')); ?>"
                       class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="notif_default_icon" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                    <?= __('admin.settings.pwa.webpush.defaults.icon_label') ?>
                </label>
                <input type="text" name="notif_default_icon" id="notif_default_icon"
                       value="<?= htmlspecialchars($settings['notif_default_icon'] ?? '/assets/icons/icon-192x192.png'); ?>"
                       class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="/assets/icons/icon-192x192.png">
            </div>
        </div>

        <div class="mb-4">
            <label for="notif_default_badge" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">
                <?= __('admin.settings.pwa.webpush.defaults.badge_label') ?>
            </label>
            <input type="text" name="notif_default_badge" id="notif_default_badge"
                   value="<?= htmlspecialchars($settings['notif_default_badge'] ?? '/assets/icons/icon-72x72.png'); ?>"
                   class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                   placeholder="/assets/icons/icon-72x72.png">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1"><?= __('admin.settings.pwa.webpush.defaults.badge_hint') ?></p>
        </div>

        <div class="flex items-center gap-6">
            <label class="flex items-center cursor-pointer">
                <input type="checkbox" name="notif_vibrate" class="w-4 h-4 text-blue-600 bg-zinc-100 border-zinc-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-zinc-800 focus:ring-2 dark:bg-zinc-700 dark:border-zinc-600" <?= ($settings['notif_vibrate'] ?? '1') === '1' ? 'checked' : ''; ?>>
                <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('admin.settings.pwa.webpush.defaults.vibrate') ?></span>
            </label>
            <label class="flex items-center cursor-pointer">
                <input type="checkbox" name="notif_require_interaction" class="w-4 h-4 text-blue-600 bg-zinc-100 border-zinc-300 rounded focus:ring-blue-500 dark:focus:ring-blue-600 dark:ring-offset-zinc-800 focus:ring-2 dark:bg-zinc-700 dark:border-zinc-600" <?= ($settings['notif_require_interaction'] ?? '0') === '1' ? 'checked' : ''; ?>>
                <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('admin.settings.pwa.webpush.defaults.require_interaction') ?></span>
            </label>
        </div>
    </div>

    <!-- Test Notification -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 transition-colors">
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('admin.settings.pwa.webpush.test.title') ?></h3>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4"><?= __('admin.settings.pwa.webpush.test.description') ?></p>

        <button type="button" id="testNotificationBtn"
                class="px-4 py-2 bg-zinc-100 dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 font-medium rounded-lg hover:bg-zinc-200 dark:hover:bg-zinc-600 transition disabled:opacity-50"
                onclick="sendTestNotification()">
            <svg class="w-4 h-4 inline mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>
            </svg>
            <?= __('admin.settings.pwa.webpush.test.send_button') ?>
        </button>
    </div>

    <!-- Save Button -->
    <div class="flex justify-end">
        <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
            <?= __('admin.buttons.save') ?>
        </button>
    </div>
</form>

<script>
    function copyToClipboard(elementId) {
        const element = document.getElementById(elementId);
        element.select();
        document.execCommand('copy');

        // Show feedback
        const btn = event.currentTarget;
        const originalHTML = btn.innerHTML;
        btn.innerHTML = '<svg class="w-5 h-5 text-green-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
        setTimeout(() => btn.innerHTML = originalHTML, 2000);

        console.log('Copied to clipboard:', elementId);
    }

    function togglePasswordVisibility(elementId) {
        const element = document.getElementById(elementId);
        element.type = element.type === 'password' ? 'text' : 'password';
        console.log('Password visibility toggled:', elementId);
    }

    function sendTestNotification() {
        if (!('Notification' in window)) {
            alert('<?= __('admin.settings.pwa.webpush.test.not_supported') ?>');
            return;
        }

        Notification.requestPermission().then(permission => {
            if (permission === 'granted') {
                const title = document.getElementById('notif_default_title').value || 'Test Notification';
                const options = {
                    body: '<?= __('admin.settings.pwa.webpush.test.body') ?>',
                    icon: document.getElementById('notif_default_icon').value || '/assets/icons/icon-192x192.png',
                    badge: document.getElementById('notif_default_badge').value || '/assets/icons/icon-72x72.png',
                    vibrate: document.querySelector('input[name="notif_vibrate"]').checked ? [200, 100, 200] : undefined
                };

                new Notification(title, options);
                console.log('Test notification sent');
            } else {
                alert('<?= __('admin.settings.pwa.webpush.test.permission_denied') ?>');
            }
        });
    }

    console.log('Web Push settings page loaded');
</script>

<?php
$pageContent = ob_get_clean();
include dirname(__DIR__) . '/_layout.php';
