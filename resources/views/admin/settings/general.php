<?php
/**
 * RezlyX Admin Settings - General
 * Site settings, admin path, and logo configuration
 */

// Initialize database and settings
require_once __DIR__ . '/_init.php';

$pageTitle = __('admin.settings.tabs.general') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$currentSettingsPage = 'general';

// Check if redirected after path change
if (isset($_GET['changed']) && $_GET['changed'] === '1') {
    $message = __('admin.settings.admin_path.changed');
    $messageType = 'success';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_admin_path') {
        $newAdminPath = trim($_POST['admin_path'] ?? '');

        if (empty($newAdminPath)) {
            $message = __('admin.settings.admin_path.error_empty');
            $messageType = 'error';
        } elseif (!preg_match('/^[a-zA-Z0-9_-]+$/', $newAdminPath)) {
            $message = __('admin.settings.admin_path.error_invalid');
            $messageType = 'error';
        } elseif (in_array($newAdminPath, ['api', 'assets', 'storage', 'install', 'public'])) {
            $message = __('admin.settings.admin_path.error_reserved');
            $messageType = 'error';
        } else {
            try {
                $stmt = $pdo->prepare("INSERT INTO rzx_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
                $stmt->execute(['admin_path', $newAdminPath]);

                // Redirect to new admin path
                $newAdminUrl = ($config['app_url'] ?? '') . '/' . $newAdminPath . '/settings/general?changed=1';
                header('Location: ' . $newAdminUrl);
                exit;
            } catch (PDOException $e) {
                $message = __('admin.settings.error_save') . ': ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    } elseif ($action === 'update_site_settings') {
        $siteCategory = $_POST['site_category'] ?? '';
        $siteName = trim($_POST['site_name'] ?? '');
        $siteTagline = trim($_POST['site_tagline'] ?? '');
        $siteUrl = trim($_POST['site_url'] ?? '');
        $logoType = $_POST['logo_type'] ?? 'text';

        // Logo image upload
        $logoImage = null;
        if (isset($_FILES['logo_image']) && $_FILES['logo_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = BASE_PATH . '/storage/logos/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/svg+xml', 'image/webp'];
            $fileType = $_FILES['logo_image']['type'];

            if (in_array($fileType, $allowedTypes)) {
                $extension = pathinfo($_FILES['logo_image']['name'], PATHINFO_EXTENSION);
                $fileName = 'logo_' . time() . '.' . $extension;
                $targetPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['logo_image']['tmp_name'], $targetPath)) {
                    $logoImage = '/storage/logos/' . $fileName;

                    // Delete old logo
                    $oldLogo = $settings['logo_image'] ?? '';
                    if ($oldLogo && file_exists(BASE_PATH . $oldLogo)) {
                        @unlink(BASE_PATH . $oldLogo);
                    }
                }
            } else {
                $message = __('admin.settings.error_image_type');
                $messageType = 'error';
            }
        }

        if ($messageType !== 'error') {
            try {
                $stmt = $pdo->prepare("INSERT INTO rzx_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
                $stmt->execute(['site_category', $siteCategory]);
                $stmt->execute(['site_name', $siteName]);
                $stmt->execute(['site_tagline', $siteTagline]);
                $stmt->execute(['site_url', $siteUrl]);
                $stmt->execute(['logo_type', $logoType]);

                if ($logoImage) {
                    $stmt->execute(['logo_image', $logoImage]);
                    $settings['logo_image'] = $logoImage;
                }

                $settings['site_category'] = $siteCategory;
                $settings['site_name'] = $siteName;
                $settings['site_tagline'] = $siteTagline;
                $settings['site_url'] = $siteUrl;
                $settings['logo_type'] = $logoType;

                $message = __('admin.settings.success');
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = __('admin.settings.error_save') . ': ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    } elseif ($action === 'delete_logo') {
        $logoImage = $settings['logo_image'] ?? '';
        if ($logoImage && file_exists(BASE_PATH . $logoImage)) {
            @unlink(BASE_PATH . $logoImage);
        }

        try {
            $stmt = $pdo->prepare("UPDATE rzx_settings SET `value` = '' WHERE `key` = 'logo_image'");
            $stmt->execute();
            $settings['logo_image'] = '';
            $message = __('admin.settings.logo_deleted');
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = __('admin.settings.error_save') . ': ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Start output buffering for page content
ob_start();
?>

<!-- Admin Path Settings -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('admin.settings.admin_path.title') ?></h2>
    <p class="text-sm text-zinc-600 dark:text-zinc-400 mb-4">
        <?= __('admin.settings.admin_path.description') ?><br>
        <?= __('admin.settings.admin_path.current_url') ?>: <code class="bg-zinc-100 dark:bg-zinc-700 px-2 py-1 rounded text-blue-600 dark:text-blue-400"><?php echo htmlspecialchars($config['app_url'] ?? ''); ?>/<?php echo htmlspecialchars($settings['admin_path'] ?? 'admin'); ?>/</code>
    </p>

    <form method="POST" class="space-y-4">
        <input type="hidden" name="action" value="update_admin_path">
        <div>
            <label for="admin_path" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.admin_path.label') ?></label>
            <div class="flex items-center space-x-2">
                <span class="text-zinc-500 dark:text-zinc-400">/</span>
                <input type="text" name="admin_path" id="admin_path"
                       value="<?php echo htmlspecialchars($settings['admin_path'] ?? 'admin'); ?>"
                       class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="admin"
                       pattern="[a-zA-Z0-9_-]+"
                       required>
                <span class="text-zinc-500 dark:text-zinc-400">/</span>
            </div>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mt-1"><?= __('admin.settings.admin_path.hint') ?></p>
        </div>
        <div class="flex items-center justify-between pt-4 border-t dark:border-zinc-700">
            <p class="text-sm text-amber-600">
                <svg class="w-4 h-4 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                </svg>
                <?= __('admin.settings.admin_path.warning') ?>
            </p>
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                <?= __('admin.settings.admin_path.button') ?>
            </button>
        </div>
    </form>
</div>

<!-- Site Settings -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
    <h2 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('admin.settings.site.title') ?></h2>
    <form method="POST" enctype="multipart/form-data" class="space-y-6">
        <input type="hidden" name="action" value="update_site_settings">

        <!-- Site Category -->
        <div>
            <label for="site_category" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.site.category_label') ?></label>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.site.category_description') ?></p>
            <?php
            $currentCategory = $settings['site_category'] ?? '';
            $categoryKeys = ['beauty_salon', 'nail_salon', 'skincare', 'massage', 'hospital', 'dental', 'studio', 'restaurant', 'accommodation', 'sports', 'education', 'consulting', 'pet', 'car', 'other'];
            $categories = ['' => __('admin.settings.site.category_placeholder')];
            foreach ($categoryKeys as $key) {
                $categories[$key] = __('admin.settings.site.categories.' . $key);
            }
            ?>
            <select name="site_category" id="site_category"
                    class="w-full md:w-1/2 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                <?php foreach ($categories as $value => $label): ?>
                <option value="<?php echo $value; ?>" <?php echo $currentCategory === $value ? 'selected' : ''; ?>>
                    <?php echo htmlspecialchars($label); ?>
                </option>
                <?php endforeach; ?>
            </select>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <!-- Site Name -->
            <div>
                <label for="site_name" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.site.name') ?></label>
                <div class="flex items-center gap-2">
                    <input type="text" name="site_name" id="site_name"
                           value="<?php echo htmlspecialchars($settings['site_name'] ?? ''); ?>"
                           class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    <button type="button" onclick="openMultilangModal('site.name', 'site_name')"
                            class="p-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition"
                            title="<?= __('admin.settings.multilang.button_title') ?>">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                        </svg>
                    </button>
                </div>
            </div>
            <div>
                <label for="site_url" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.site.url') ?></label>
                <input type="url" name="site_url" id="site_url"
                       value="<?php echo htmlspecialchars($settings['site_url'] ?? ''); ?>"
                       class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        <!-- Site Tagline -->
        <div>
            <label for="site_tagline" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('admin.settings.site.tagline') ?></label>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.site.tagline_hint') ?></p>
            <div class="flex items-center gap-2">
                <input type="text" name="site_tagline" id="site_tagline"
                       value="<?php echo htmlspecialchars($settings['site_tagline'] ?? ''); ?>"
                       class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="<?= __('admin.settings.multilang.placeholder') ?>">
                <button type="button" onclick="openMultilangModal('site.tagline', 'site_tagline')"
                        class="p-2 text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition"
                        title="<?= __('admin.settings.multilang.button_title') ?>">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5h12M9 3v2m1.048 9.5A18.022 18.022 0 016.412 9m6.088 9h7M11 21l5-10 5 10M12.751 5C11.783 10.77 8.07 15.61 3 18.129"/>
                    </svg>
                </button>
            </div>
        </div>

        <!-- Logo Settings -->
        <div class="border-t dark:border-zinc-700 pt-6">
            <h3 class="text-md font-semibold text-zinc-900 dark:text-white mb-4"><?= __('admin.settings.logo.title') ?></h3>

            <!-- Logo Type Selection -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('admin.settings.logo.type_label') ?></label>
                <div class="flex flex-wrap gap-4">
                    <?php $currentLogoType = $settings['logo_type'] ?? 'text'; ?>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="logo_type" value="text"
                               <?php echo $currentLogoType === 'text' ? 'checked' : ''; ?>
                               class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('admin.settings.logo.type_text') ?></span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="logo_type" value="image"
                               <?php echo $currentLogoType === 'image' ? 'checked' : ''; ?>
                               class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('admin.settings.logo.type_image') ?></span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="logo_type" value="image_text"
                               <?php echo $currentLogoType === 'image_text' ? 'checked' : ''; ?>
                               class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('admin.settings.logo.type_image_text') ?></span>
                    </label>
                </div>
            </div>

            <!-- Logo Image Upload -->
            <div class="mb-4">
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('admin.settings.logo.image_label') ?></label>

                <?php if (!empty($settings['logo_image'])): ?>
                <div class="mb-3 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg inline-block">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.logo.current') ?>:</p>
                    <img src="<?php echo $baseUrl . htmlspecialchars($settings['logo_image']); ?>"
                         alt="<?= __('admin.settings.logo.current') ?>" class="max-h-16 object-contain">
                </div>
                <?php endif; ?>

                <div class="flex items-center gap-4">
                    <div class="flex-1">
                        <input type="file" name="logo_image" id="logo_image"
                               accept="image/jpeg,image/png,image/gif,image/svg+xml,image/webp"
                               class="block w-full text-sm text-zinc-500 dark:text-zinc-400
                                      file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0
                                      file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700
                                      dark:file:bg-blue-900/30 dark:file:text-blue-400
                                      hover:file:bg-blue-100 dark:hover:file:bg-blue-900/50 cursor-pointer">
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400"><?= __('admin.settings.logo.hint') ?></p>
                    </div>

                    <?php if (!empty($settings['logo_image'])): ?>
                    <button type="button" onclick="deleteLogo()" class="px-3 py-2 text-sm text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition">
                        <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <?= __('admin.settings.logo.delete') ?>
                    </button>
                    <?php endif; ?>
                </div>

                <!-- Image Preview -->
                <div id="logoPreview" class="mt-3 hidden">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.logo.preview') ?>:</p>
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg inline-block">
                        <img id="logoPreviewImg" src="" alt="<?= __('admin.settings.logo.preview') ?>" class="max-h-16 object-contain">
                    </div>
                </div>
            </div>

            <!-- Logo Display Preview -->
            <div class="p-4 bg-zinc-100 dark:bg-zinc-900 rounded-lg">
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('admin.settings.logo.display_preview') ?>:</p>
                <div id="logoDisplayPreview" class="flex items-center text-xl font-bold text-blue-600 dark:text-blue-400">
                    <?php
                    $logoType = $settings['logo_type'] ?? 'text';
                    $siteName = $settings['site_name'] ?? 'RezlyX';
                    $logoImage = $settings['logo_image'] ?? '';

                    if ($logoType === 'image' && $logoImage): ?>
                        <img src="<?php echo $baseUrl . htmlspecialchars($logoImage); ?>" alt="<?php echo htmlspecialchars($siteName); ?>" class="h-10 object-contain">
                    <?php elseif ($logoType === 'image_text' && $logoImage): ?>
                        <img src="<?php echo $baseUrl . htmlspecialchars($logoImage); ?>" alt="" class="h-10 object-contain mr-2">
                        <span><?php echo htmlspecialchars($siteName); ?></span>
                    <?php else: ?>
                        <span><?php echo htmlspecialchars($siteName); ?></span>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <div class="flex justify-end pt-4 border-t dark:border-zinc-700">
            <button type="submit" class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition">
                <?= __('admin.buttons.save') ?>
            </button>
        </div>
    </form>
</div>

<script>
    // Logo delete function
    function deleteLogo() {
        if (confirm('<?= __('admin.settings.logo.delete_confirm') ?>')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="action" value="delete_logo">';
            document.body.appendChild(form);
            form.submit();
        }
    }

    // Logo image preview
    const logoInput = document.getElementById('logo_image');
    const logoPreview = document.getElementById('logoPreview');
    const logoPreviewImg = document.getElementById('logoPreviewImg');

    if (logoInput) {
        logoInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    logoPreviewImg.src = e.target.result;
                    logoPreview.classList.remove('hidden');
                    console.log('Logo preview updated');
                };
                reader.readAsDataURL(file);
            } else {
                logoPreview.classList.add('hidden');
            }
        });
    }
</script>

<?php
$pageContent = ob_get_clean();

// Render layout with content
include __DIR__ . '/_layout.php';
