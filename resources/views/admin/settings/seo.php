<?php
/**
 * RezlyX Admin Settings - SEO
 * Meta tags, Open Graph, webmaster tools, analytics
 */

// Initialize database and settings
require_once __DIR__ . '/_init.php';

$pageTitle = __('settings.tabs.seo') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$currentSettingsPage = 'seo';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $action = $_POST['action'] ?? '';

    if ($action === 'update_seo_settings') {
        $seoDescription = trim($_POST['seo_description'] ?? '');
        $seoKeywords = trim($_POST['seo_keywords'] ?? '');
        $seoRobots = $_POST['seo_robots'] ?? 'index';
        $googleVerification = trim($_POST['google_verification'] ?? '');
        $naverVerification = trim($_POST['naver_verification'] ?? '');
        $gaTrackingId = trim($_POST['ga_tracking_id'] ?? '');
        $gtmId = trim($_POST['gtm_id'] ?? '');

        // OG Image upload
        $ogImage = null;
        if (isset($_FILES['og_image']) && $_FILES['og_image']['error'] === UPLOAD_ERR_OK) {
            $uploadDir = BASE_PATH . '/storage/seo/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }

            $allowedTypes = ['image/jpeg', 'image/png', 'image/webp'];
            $fileType = $_FILES['og_image']['type'];

            if (in_array($fileType, $allowedTypes)) {
                $extension = pathinfo($_FILES['og_image']['name'], PATHINFO_EXTENSION);
                $fileName = 'og_image_' . time() . '.' . $extension;
                $targetPath = $uploadDir . $fileName;

                if (move_uploaded_file($_FILES['og_image']['tmp_name'], $targetPath)) {
                    $ogImage = '/storage/seo/' . $fileName;
                    $oldOgImage = $settings['og_image'] ?? '';
                    if ($oldOgImage && file_exists(BASE_PATH . $oldOgImage)) {
                        @unlink(BASE_PATH . $oldOgImage);
                    }
                }
            } else {
                $message = __('settings.error_image_type');
                $messageType = 'error';
            }
        }

        if ($messageType !== 'error') {
            try {
                $stmt = $pdo->prepare("INSERT INTO rzx_settings (`key`, `value`) VALUES (?, ?) ON DUPLICATE KEY UPDATE `value` = VALUES(`value`)");
                $stmt->execute(['seo_description', $seoDescription]);
                $stmt->execute(['seo_keywords', $seoKeywords]);
                $stmt->execute(['seo_robots', $seoRobots]);
                $stmt->execute(['google_verification', $googleVerification]);
                $stmt->execute(['naver_verification', $naverVerification]);
                $stmt->execute(['ga_tracking_id', $gaTrackingId]);
                $stmt->execute(['gtm_id', $gtmId]);

                if ($ogImage) {
                    $stmt->execute(['og_image', $ogImage]);
                    $settings['og_image'] = $ogImage;
                }

                $settings['seo_description'] = $seoDescription;
                $settings['seo_keywords'] = $seoKeywords;
                $settings['seo_robots'] = $seoRobots;
                $settings['google_verification'] = $googleVerification;
                $settings['naver_verification'] = $naverVerification;
                $settings['ga_tracking_id'] = $gaTrackingId;
                $settings['gtm_id'] = $gtmId;

                $message = __('settings.seo.success');
                $messageType = 'success';
            } catch (PDOException $e) {
                $message = __('settings.error_save') . ': ' . $e->getMessage();
                $messageType = 'error';
            }
        }
    } elseif ($action === 'delete_og_image') {
        try {
            $oldOgImage = $settings['og_image'] ?? '';
            if ($oldOgImage && file_exists(BASE_PATH . $oldOgImage)) {
                @unlink(BASE_PATH . $oldOgImage);
            }

            $stmt = $pdo->prepare("DELETE FROM rzx_settings WHERE `key` = ?");
            $stmt->execute(['og_image']);

            unset($settings['og_image']);
            $message = __('settings.seo.og.image_deleted');
            $messageType = 'success';
        } catch (PDOException $e) {
            $message = __('settings.error_save') . ': ' . $e->getMessage();
            $messageType = 'error';
        }
    }
}

// Start content buffering
ob_start();
?>

<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6 transition-colors">
    <?php
    $headerIcon = 'M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z';
    $headerTitle = __('settings.seo.title');
    $headerDescription = __('settings.seo.description');
    $headerIconColor = ''; $headerActions = '';
    include __DIR__ . '/../components/settings-header.php';
    ?>

    <form method="POST" enctype="multipart/form-data" class="space-y-6">
        <input type="hidden" name="action" value="update_seo_settings">

        <!-- Meta Tags Section -->
        <div class="border-b dark:border-zinc-700 pb-6">
            <h3 class="text-md font-semibold text-zinc-900 dark:text-white mb-4"><?= __('settings.seo.meta.title') ?></h3>

            <div class="mb-4">
                <label for="seo_description" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.seo.meta.description_label') ?></label>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('settings.seo.meta.description_hint') ?></p>
                <textarea name="seo_description" id="seo_description" rows="3"
                          class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                          maxlength="200"><?= htmlspecialchars($settings['seo_description'] ?? ''); ?></textarea>
                <div class="text-xs text-zinc-400 mt-1"><span id="descCharCount">0</span>/200</div>
            </div>

            <div>
                <label for="seo_keywords" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.seo.meta.keywords_label') ?></label>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('settings.seo.meta.keywords_hint') ?></p>
                <input type="text" name="seo_keywords" id="seo_keywords"
                       value="<?= htmlspecialchars($settings['seo_keywords'] ?? ''); ?>"
                       class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       placeholder="<?= __('settings.seo.meta.keywords_placeholder') ?>">
            </div>
        </div>

        <!-- Open Graph Section -->
        <div class="border-b dark:border-zinc-700 pb-6">
            <h3 class="text-md font-semibold text-zinc-900 dark:text-white mb-2"><?= __('settings.seo.og.title') ?></h3>
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-4"><?= __('settings.seo.og.description') ?></p>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('settings.seo.og.image_label') ?></label>

                <?php if (!empty($settings['og_image'])): ?>
                <div class="mb-3 p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg inline-block">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('settings.seo.og.image_current') ?>:</p>
                    <img src="<?= $baseUrl . htmlspecialchars($settings['og_image']); ?>" alt="OG Image" class="max-h-32 object-contain rounded">
                </div>
                <?php endif; ?>

                <div class="flex items-center gap-4">
                    <div class="flex-1">
                        <input type="file" name="og_image" id="og_image"
                               accept="image/jpeg,image/png,image/webp"
                               class="block w-full text-sm text-zinc-500 dark:text-zinc-400 file:mr-4 file:py-2 file:px-4 file:rounded-lg file:border-0 file:text-sm file:font-medium file:bg-blue-50 file:text-blue-700 dark:file:bg-blue-900/30 dark:file:text-blue-400 hover:file:bg-blue-100 dark:hover:file:bg-blue-900/50 cursor-pointer">
                        <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400"><?= __('settings.seo.og.image_hint') ?></p>
                    </div>

                    <?php if (!empty($settings['og_image'])): ?>
                    <button type="button" onclick="deleteOgImage()" class="px-3 py-2 text-sm text-red-600 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition">
                        <svg class="w-5 h-5 inline mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/>
                        </svg>
                        <?= __('settings.seo.og.image_delete') ?>
                    </button>
                    <?php endif; ?>
                </div>

                <div id="ogImagePreview" class="mt-3 hidden">
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('settings.seo.og.image_preview') ?>:</p>
                    <div class="p-4 bg-zinc-50 dark:bg-zinc-900 rounded-lg inline-block">
                        <img id="ogImagePreviewImg" src="" alt="Preview" class="max-h-32 object-contain rounded">
                    </div>
                </div>
            </div>
        </div>

        <!-- Search Engine Section -->
        <div class="border-b dark:border-zinc-700 pb-6">
            <h3 class="text-md font-semibold text-zinc-900 dark:text-white mb-4"><?= __('settings.seo.search_engine.title') ?></h3>

            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('settings.seo.search_engine.robots_label') ?></label>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('settings.seo.search_engine.robots_hint') ?></p>
                <?php $currentRobots = $settings['seo_robots'] ?? 'index'; ?>
                <div class="flex flex-wrap gap-4">
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="seo_robots" value="index" <?= $currentRobots === 'index' ? 'checked' : ''; ?>
                               class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('settings.seo.search_engine.robots_index') ?></span>
                    </label>
                    <label class="flex items-center cursor-pointer">
                        <input type="radio" name="seo_robots" value="noindex" <?= $currentRobots === 'noindex' ? 'checked' : ''; ?>
                               class="w-4 h-4 text-blue-600 border-zinc-300 focus:ring-blue-500">
                        <span class="ml-2 text-sm text-zinc-700 dark:text-zinc-300"><?= __('settings.seo.search_engine.robots_noindex') ?></span>
                    </label>
                </div>
            </div>
        </div>

        <!-- Webmaster Tools Section -->
        <div class="border-b dark:border-zinc-700 pb-6">
            <h3 class="text-md font-semibold text-zinc-900 dark:text-white mb-4"><?= __('settings.seo.webmaster.title') ?></h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="google_verification" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.seo.webmaster.google_label') ?></label>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('settings.seo.webmaster.google_hint') ?></p>
                    <input type="text" name="google_verification" id="google_verification"
                           value="<?= htmlspecialchars($settings['google_verification'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="<?= __('settings.seo.webmaster.google_placeholder') ?>">
                </div>

                <div>
                    <label for="naver_verification" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.seo.webmaster.naver_label') ?></label>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('settings.seo.webmaster.naver_hint') ?></p>
                    <input type="text" name="naver_verification" id="naver_verification"
                           value="<?= htmlspecialchars($settings['naver_verification'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="<?= __('settings.seo.webmaster.naver_placeholder') ?>">
                </div>
            </div>
        </div>

        <!-- Analytics Section -->
        <div>
            <h3 class="text-md font-semibold text-zinc-900 dark:text-white mb-4"><?= __('settings.seo.analytics.title') ?></h3>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div>
                    <label for="ga_tracking_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.seo.analytics.ga_label') ?></label>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('settings.seo.analytics.ga_hint') ?></p>
                    <input type="text" name="ga_tracking_id" id="ga_tracking_id"
                           value="<?= htmlspecialchars($settings['ga_tracking_id'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="<?= __('settings.seo.analytics.ga_placeholder') ?>">
                </div>

                <div>
                    <label for="gtm_id" class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('settings.seo.analytics.gtm_label') ?></label>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-2"><?= __('settings.seo.analytics.gtm_hint') ?></p>
                    <input type="text" name="gtm_id" id="gtm_id"
                           value="<?= htmlspecialchars($settings['gtm_id'] ?? ''); ?>"
                           class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           placeholder="<?= __('settings.seo.analytics.gtm_placeholder') ?>">
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
    function deleteOgImage() {
        if (confirm('<?= __('settings.seo.og.image_delete_confirm') ?>')) {
            const form = document.createElement('form');
            form.method = 'POST';
            form.innerHTML = '<input type="hidden" name="action" value="delete_og_image">';
            document.body.appendChild(form);
            form.submit();
        }
    }

    // OG Image preview
    const ogImageInput = document.getElementById('og_image');
    const ogImagePreview = document.getElementById('ogImagePreview');
    const ogImagePreviewImg = document.getElementById('ogImagePreviewImg');

    if (ogImageInput) {
        ogImageInput.addEventListener('change', (e) => {
            const file = e.target.files[0];
            if (file) {
                const reader = new FileReader();
                reader.onload = (e) => {
                    ogImagePreviewImg.src = e.target.result;
                    ogImagePreview.classList.remove('hidden');
                };
                reader.readAsDataURL(file);
            } else {
                ogImagePreview.classList.add('hidden');
            }
        });
    }

    // Character count for description
    const seoDescInput = document.getElementById('seo_description');
    const descCharCount = document.getElementById('descCharCount');

    if (seoDescInput && descCharCount) {
        descCharCount.textContent = seoDescInput.value.length;
        seoDescInput.addEventListener('input', () => {
            descCharCount.textContent = seoDescInput.value.length;
        });
    }
</script>

<?php
$pageContent = ob_get_clean();
include __DIR__ . '/_layout.php';
