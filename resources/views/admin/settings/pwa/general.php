<?php
/**
 * RezlyX Admin Settings - PWA General
 * PWA configuration for frontend and admin panel
 */

// Initialize database and settings
require_once dirname(__DIR__) . '/_init.php';

$pageTitle = __('settings.pwa.tabs.general') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$currentSettingsPage = 'pwa';
$currentPwaTab = 'general';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_pwa_settings') {
        // Frontend PWA settings
        $pwaFrontEnabled = isset($_POST['pwa_front_enabled']) ? '1' : '0';
        $pwaFrontName = trim($_POST['pwa_front_name'] ?? '');
        $pwaFrontShortName = trim($_POST['pwa_front_short_name'] ?? '');
        $pwaFrontDescription = trim($_POST['pwa_front_description'] ?? '');
        $pwaFrontThemeColor = trim($_POST['pwa_front_theme_color'] ?? '#3b82f6');
        $pwaFrontBgColor = trim($_POST['pwa_front_bg_color'] ?? '#ffffff');
        $pwaFrontDisplay = $_POST['pwa_front_display'] ?? 'standalone';

        // Admin PWA settings
        $pwaAdminEnabled = isset($_POST['pwa_admin_enabled']) ? '1' : '0';
        $pwaAdminName = trim($_POST['pwa_admin_name'] ?? '');
        $pwaAdminShortName = trim($_POST['pwa_admin_short_name'] ?? '');
        $pwaAdminThemeColor = trim($_POST['pwa_admin_theme_color'] ?? '#18181b');
        $pwaAdminBgColor = trim($_POST['pwa_admin_bg_color'] ?? '#18181b');

        // Frontend PWA icon upload
        $pwaFrontIcon = null;
        if (isset($_FILES['pwa_front_icon']) && $_FILES['pwa_front_icon']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = BASE_PATH . '/storage/pwa/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $allowedTypes = ['image/png', 'image/webp'];
            $fileType = $_FILES['pwa_front_icon']['type'];

            if (in_array($fileType, $allowedTypes)) {
                $extension = pathinfo($_FILES['pwa_front_icon']['name'], PATHINFO_EXTENSION);
                $fileName = 'pwa_front_icon_' . time() . '.' . $extension;
                $targetPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['pwa_front_icon']['tmp_name'], $targetPath)) {
                    $pwaFrontIcon = '/storage/pwa/' . $fileName;
                    $oldIcon = $settings['pwa_front_icon'] ?? '';
                    if ($oldIcon && file_exists(BASE_PATH . $oldIcon)) {
                        @unlink(BASE_PATH . $oldIcon);
                    }
                }
            } else {
                $message = __('settings.pwa.error_icon_type');
                $messageType = 'error';
            }
        }

        // Admin PWA icon upload
        $pwaAdminIcon = null;
        if (isset($_FILES['pwa_admin_icon']) && $_FILES['pwa_admin_icon']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = BASE_PATH . '/storage/pwa/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $allowedTypes = ['image/png', 'image/webp'];
            $fileType = $_FILES['pwa_admin_icon']['type'];

            if (in_array($fileType, $allowedTypes)) {
                $extension = pathinfo($_FILES['pwa_admin_icon']['name'], PATHINFO_EXTENSION);
                $fileName = 'pwa_admin_icon_' . time() . '.' . $extension;
                $targetPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['pwa_admin_icon']['tmp_name'], $targetPath)) {
                    $pwaAdminIcon = '/storage/pwa/' . $fileName;
                    $oldIcon = $settings['pwa_admin_icon'] ?? '';
                    if ($oldIcon && file_exists(BASE_PATH . $oldIcon)) {
                        @unlink(BASE_PATH . $oldIcon);
                    }
                }
            } else {
                $message = __('settings.pwa.error_icon_type');
                $messageType = 'error';
            }
        }

        if ($messageType !== 'error') {
            try {
                $stmt = $pdo->prepare("INSERT INTO rzx_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");

                // Save frontend PWA settings
                $stmt->execute(['pwa_front_enabled', $pwaFrontEnabled]);
                $stmt->execute(['pwa_front_name', $pwaFrontName]);
                $stmt->execute(['pwa_front_short_name', $pwaFrontShortName]);
                $stmt->execute(['pwa_front_description', $pwaFrontDescription]);
                $stmt->execute(['pwa_front_theme_color', $pwaFrontThemeColor]);
                $stmt->execute(['pwa_front_bg_color', $pwaFrontBgColor]);
                $stmt->execute(['pwa_front_display', $pwaFrontDisplay]);

                // Save admin PWA settings
                $stmt->execute(['pwa_admin_enabled', $pwaAdminEnabled]);
                $stmt->execute(['pwa_admin_name', $pwaAdminName]);
                $stmt->execute(['pwa_admin_short_name', $pwaAdminShortName]);
                $stmt->execute(['pwa_admin_theme_color', $pwaAdminThemeColor]);
                $stmt->execute(['pwa_admin_bg_color', $pwaAdminBgColor]);

                // Save icons if uploaded
                if ($pwaFrontIcon) {
                    $stmt->execute(['pwa_front_icon', $pwaFrontIcon]);
                    $settings['pwa_front_icon'] = $pwaFrontIcon;
                }
                if ($pwaAdminIcon) {
                    $stmt->execute(['pwa_admin_icon', $pwaAdminIcon]);
                    $settings['pwa_admin_icon'] = $pwaAdminIcon;
                }

                // Update local settings array
                $settings['pwa_front_enabled'] = $pwaFrontEnabled;
                $settings['pwa_front_name'] = $pwaFrontName;
                $settings['pwa_front_short_name'] = $pwaFrontShortName;
                $settings['pwa_front_description'] = $pwaFrontDescription;
                $settings['pwa_front_theme_color'] = $pwaFrontThemeColor;
                $settings['pwa_front_bg_color'] = $pwaFrontBgColor;
                $settings['pwa_front_display'] = $pwaFrontDisplay;
                $settings['pwa_admin_enabled'] = $pwaAdminEnabled;
                $settings['pwa_admin_name'] = $pwaAdminName;
                $settings['pwa_admin_short_name'] = $pwaAdminShortName;
                $settings['pwa_admin_theme_color'] = $pwaAdminThemeColor;
                $settings['pwa_admin_bg_color'] = $pwaAdminBgColor;

                $message = __('settings.pwa.success');
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = __('settings.error_save') . ': ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    } elseif ($action === 'delete_pwa_front_icon') {
        try {
            $oldIcon = $settings['pwa_front_icon'] ?? '';
            if ($oldIcon && file_exists(BASE_PATH . $oldIcon)) {
                @unlink(BASE_PATH . $oldIcon);
            }

            $stmt = $pdo->prepare("DELETE FROM rzx_settings WHERE `key` = ?");
            $stmt->execute(['pwa_front_icon']);

            unset($settings['pwa_front_icon']);
            $message = __('settings.pwa.icon_deleted');
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = __('settings.error_save') . ': ' . $e->getMessage();
            $messageType = 'error';
        }
    } elseif ($action === 'delete_pwa_admin_icon') {
        try {
            $oldIcon = $settings['pwa_admin_icon'] ?? '';
            if ($oldIcon && file_exists(BASE_PATH . $oldIcon)) {
                @unlink(BASE_PATH . $oldIcon);
            }

            $stmt = $pdo->prepare("DELETE FROM rzx_settings WHERE `key` = ?");
            $stmt->execute(['pwa_admin_icon']);

            unset($settings['pwa_admin_icon']);
            $message = __('settings.pwa.icon_deleted');
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = __('settings.error_save') . ': ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Default values
$pwaFrontEnabled = ($settings['pwa_front_enabled'] ?? '1') === '1';
$pwaAdminEnabled = ($settings['pwa_admin_enabled'] ?? '1') === '1';

// Start content buffering
ob_start();

// Include tabs
include __DIR__ . '/_tabs.php';
?>

<form method="POST" enctype="multipart/form-data" class="space-y-6">
    <input type="hidden" name="action" value="update_pwa_settings">

    <!-- Frontend PWA Settings -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 transition-colors">
        <?php
        $headerIcon = 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z';
        $headerTitle = __('settings.pwa.front.title');
        $headerDescription = __('settings.pwa.front.description');
        $headerIconColor = 'text-blue-600';
        $headerActions = '<label class="relative inline-flex items-center cursor-pointer"><input type="checkbox" name="pwa_front_enabled" class="sr-only peer" ' . ($pwaFrontEnabled ? 'checked' : '') . '><div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-zinc-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[\'\'] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-blue-600"></div></label>';
        include __DIR__ . '/../../components/settings-header.php';
        ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="pwa_front_name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.pwa.front.name_label') ?></label>
                <input type="text" name="pwa_front_name" id="pwa_front_name"
                       value="<?= htmlspecialchars($settings['pwa_front_name'] ?? ($settings['site_name'] ?? 'RezlyX')); ?>"
                       class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="<?= __('settings.pwa.front.name_placeholder') ?>">
            </div>
            <div>
                <label for="pwa_front_short_name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.pwa.front.short_name_label') ?></label>
                <input type="text" name="pwa_front_short_name" id="pwa_front_short_name"
                       value="<?= htmlspecialchars($settings['pwa_front_short_name'] ?? ''); ?>"
                       class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="<?= __('settings.pwa.front.short_name_placeholder') ?>" maxlength="12">
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1"><?= __('settings.pwa.front.short_name_hint') ?></p>
            </div>
        </div>

        <div class="mb-4">
            <label for="pwa_front_description" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.pwa.front.description_label') ?></label>
            <textarea name="pwa_front_description" id="pwa_front_description" rows="2"
                      class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                      maxlength="200"><?= htmlspecialchars($settings['pwa_front_description'] ?? ''); ?></textarea>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-3 gap-4 mb-4">
            <div>
                <label for="pwa_front_theme_color" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.pwa.front.theme_color_label') ?></label>
                <div class="flex items-center gap-2">
                    <input type="color" name="pwa_front_theme_color" id="pwa_front_theme_color"
                           value="<?= htmlspecialchars($settings['pwa_front_theme_color'] ?? '#3b82f6'); ?>"
                           class="w-10 h-10 rounded border border-zinc-300 dark:border-zinc-600 cursor-pointer">
                    <input type="text" id="pwa_front_theme_color_text"
                           value="<?= htmlspecialchars($settings['pwa_front_theme_color'] ?? '#3b82f6'); ?>"
                           class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           pattern="^#[0-9A-Fa-f]{6}$" maxlength="7">
                </div>
            </div>
            <div>
                <label for="pwa_front_bg_color" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.pwa.front.bg_color_label') ?></label>
                <div class="flex items-center gap-2">
                    <input type="color" name="pwa_front_bg_color" id="pwa_front_bg_color"
                           value="<?= htmlspecialchars($settings['pwa_front_bg_color'] ?? '#ffffff'); ?>"
                           class="w-10 h-10 rounded border border-zinc-300 dark:border-zinc-600 cursor-pointer">
                    <input type="text" id="pwa_front_bg_color_text"
                           value="<?= htmlspecialchars($settings['pwa_front_bg_color'] ?? '#ffffff'); ?>"
                           class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           pattern="^#[0-9A-Fa-f]{6}$" maxlength="7">
                </div>
            </div>
            <div>
                <label for="pwa_front_display" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.pwa.front.display_label') ?></label>
                <select name="pwa_front_display" id="pwa_front_display"
                        class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <?php $currentDisplay = $settings['pwa_front_display'] ?? 'standalone'; ?>
                    <option value="standalone" <?= $currentDisplay === 'standalone' ? 'selected' : ''; ?>>Standalone</option>
                    <option value="fullscreen" <?= $currentDisplay === 'fullscreen' ? 'selected' : ''; ?>>Fullscreen</option>
                    <option value="minimal-ui" <?= $currentDisplay === 'minimal-ui' ? 'selected' : ''; ?>>Minimal UI</option>
                    <option value="browser" <?= $currentDisplay === 'browser' ? 'selected' : ''; ?>>Browser</option>
                </select>
            </div>
        </div>

        <!-- Frontend PWA Icon -->
        <div class="border-t dark:border-zinc-700 pt-4">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('settings.pwa.front.icon_label') ?></label>

            <?php if (!empty($settings['pwa_front_icon'])): ?>
            <div class="mb-3 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg inline-block">
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('settings.pwa.icon_current') ?>:</p>
                <img src="<?= $baseUrl . htmlspecialchars($settings['pwa_front_icon']); ?>" alt="PWA Icon" class="w-16 h-16 object-contain rounded">
            </div>
            <?php endif; ?>

            <div class="flex items-center gap-4">
                <div class="flex-1">
                    <input type="file" name="pwa_front_icon" id="pwa_front_icon"
                           accept="image/png,image/webp"
                           class="block w-full text-sm text-zinc-500 dark:text-zinc-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 dark:file:bg-blue-900/30 dark:file:text-blue-400 hover:file:bg-blue-100 dark:hover:file:bg-blue-900/50 cursor-pointer">
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400"><?= __('settings.pwa.icon_hint') ?></p>
                </div>

                <?php if (!empty($settings['pwa_front_icon'])): ?>
                <button type="button" onclick="deletePwaIcon('front')" class="px-3 py-2 text-sm text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition">
                    <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    <?= __('settings.pwa.icon_delete') ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Admin PWA Settings -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 transition-colors">
        <?php
        $headerIcon = 'M9.75 17L9 20l-1 1h8l-1-1-.75-3M3 13h18M5 17h14a2 2 0 002-2V5a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z';
        $headerTitle = __('settings.pwa.admin.title');
        $headerDescription = __('settings.pwa.admin.description');
        $headerIconColor = 'text-purple-600';
        $headerActions = '<label class="relative inline-flex items-center cursor-pointer"><input type="checkbox" name="pwa_admin_enabled" class="sr-only peer" ' . ($pwaAdminEnabled ? 'checked' : '') . '><div class="w-11 h-6 bg-zinc-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 dark:peer-focus:ring-blue-800 rounded-full peer dark:bg-zinc-700 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[\'\'] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-blue-600"></div></label>';
        include __DIR__ . '/../../components/settings-header.php';
        ?>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="pwa_admin_name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.pwa.admin.name_label') ?></label>
                <input type="text" name="pwa_admin_name" id="pwa_admin_name"
                       value="<?= htmlspecialchars($settings['pwa_admin_name'] ?? 'RezlyX Admin'); ?>"
                       class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
            <div>
                <label for="pwa_admin_short_name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.pwa.admin.short_name_label') ?></label>
                <input type="text" name="pwa_admin_short_name" id="pwa_admin_short_name"
                       value="<?= htmlspecialchars($settings['pwa_admin_short_name'] ?? 'Admin'); ?>"
                       class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       maxlength="12">
            </div>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
            <div>
                <label for="pwa_admin_theme_color" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.pwa.admin.theme_color_label') ?></label>
                <div class="flex items-center gap-2">
                    <input type="color" name="pwa_admin_theme_color" id="pwa_admin_theme_color"
                           value="<?= htmlspecialchars($settings['pwa_admin_theme_color'] ?? '#18181b'); ?>"
                           class="w-10 h-10 rounded border border-zinc-300 dark:border-zinc-600 cursor-pointer">
                    <input type="text" id="pwa_admin_theme_color_text"
                           value="<?= htmlspecialchars($settings['pwa_admin_theme_color'] ?? '#18181b'); ?>"
                           class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           pattern="^#[0-9A-Fa-f]{6}$" maxlength="7">
                </div>
            </div>
            <div>
                <label for="pwa_admin_bg_color" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.pwa.admin.bg_color_label') ?></label>
                <div class="flex items-center gap-2">
                    <input type="color" name="pwa_admin_bg_color" id="pwa_admin_bg_color"
                           value="<?= htmlspecialchars($settings['pwa_admin_bg_color'] ?? '#18181b'); ?>"
                           class="w-10 h-10 rounded border border-zinc-300 dark:border-zinc-600 cursor-pointer">
                    <input type="text" id="pwa_admin_bg_color_text"
                           value="<?= htmlspecialchars($settings['pwa_admin_bg_color'] ?? '#18181b'); ?>"
                           class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           pattern="^#[0-9A-Fa-f]{6}$" maxlength="7">
                </div>
            </div>
        </div>

        <!-- Admin PWA Icon -->
        <div class="border-t dark:border-zinc-700 pt-4">
            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('settings.pwa.admin.icon_label') ?></label>

            <?php if (!empty($settings['pwa_admin_icon'])): ?>
            <div class="mb-3 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg inline-block">
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('settings.pwa.icon_current') ?>:</p>
                <img src="<?= $baseUrl . htmlspecialchars($settings['pwa_admin_icon']); ?>" alt="Admin PWA Icon" class="w-16 h-16 object-contain rounded">
            </div>
            <?php endif; ?>

            <div class="flex items-center gap-4">
                <div class="flex-1">
                    <input type="file" name="pwa_admin_icon" id="pwa_admin_icon"
                           accept="image/png,image/webp"
                           class="block w-full text-sm text-zinc-500 dark:text-zinc-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 dark:file:bg-blue-900/30 dark:file:text-blue-400 hover:file:bg-blue-100 dark:hover:file:bg-blue-900/50 cursor-pointer">
                    <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400"><?= __('settings.pwa.icon_hint') ?></p>
                </div>

                <?php if (!empty($settings['pwa_admin_icon'])): ?>
                <button type="button" onclick="deletePwaIcon('admin')" class="px-3 py-2 text-sm text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition">
                    <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                    </svg>
                    <?= __('settings.pwa.icon_delete') ?>
                </button>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Save Button -->
    <div class="flex justify-end">
        <button type="submit" class="px-6 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
            <?= __('admin.buttons.save') ?>
        </button>
    </div>
</form>

<script>
    // Delete PWA icon function
    function deletePwaIcon(type) {
        if (confirm('<?= __('settings.pwa.icon_delete_confirm') ?>')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="action" value="delete_pwa_' + type + '_icon">';
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Color picker sync
    function syncColorInputs(colorId, textId) {
        const colorInput = document.getElementById(colorId);
        const textInput = document.getElementById(textId);

        if (colorInput && textInput) {
            colorInput.addEventListener('input', () => {
                textInput.value = colorInput.value;
                console.log('Color updated:', colorId, colorInput.value);
            });

            textInput.addEventListener('input', () => {
                if (/^#[0-9A-Fa-f]{6}$/.test(textInput.value)) {
                    colorInput.value = textInput.value;
                }
            });
        }
    }

    // Initialize color syncs
    syncColorInputs('pwa_front_theme_color', 'pwa_front_theme_color_text');
    syncColorInputs('pwa_front_bg_color', 'pwa_front_bg_color_text');
    syncColorInputs('pwa_admin_theme_color', 'pwa_admin_theme_color_text');
    syncColorInputs('pwa_admin_bg_color', 'pwa_admin_bg_color_text');

    console.log('PWA general settings page loaded');
</script>

<?php
$pageContent = ob_get_clean();
include dirname(__DIR__) . '/_layout.php';
