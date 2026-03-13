<?php
/**
 * Admin Account Setup Step
 */

$error = $_SESSION['install_error'] ?? null;
$data = $_SESSION['install_admin'] ?? [];
unset($_SESSION['install_error']);

$langParam = isset($_GET['lang']) ? '&lang=' . htmlspecialchars($_GET['lang']) : '';
?>

<div class="bg-white rounded-lg shadow-sm p-8">
    <h2 class="text-xl font-bold text-gray-900 mb-6"><?= t('admin_title') ?></h2>

    <?php if ($error): ?>
    <div class="p-4 bg-red-50 border border-red-200 rounded-lg mb-6">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-red-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <span class="text-red-800"><?= htmlspecialchars($error) ?></span>
        </div>
    </div>
    <?php endif; ?>

    <form method="POST" action="?step=admin<?= $langParam ?>" class="space-y-6">
        <input type="hidden" name="action" value="admin">

        <!-- Site Settings -->
        <div class="border-b pb-6">
            <h3 class="font-semibold text-gray-900 mb-4"><?= t('admin_site_settings') ?></h3>

            <div class="space-y-4">
                <div>
                    <label for="site_name" class="block text-sm font-medium text-gray-700 mb-1">
                        <?= t('admin_site_name') ?> <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="site_name" name="site_name"
                           value="<?= htmlspecialchars($data['site_name'] ?? 'RezlyX') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           required>
                </div>

                <div>
                    <label for="site_url" class="block text-sm font-medium text-gray-700 mb-1">
                        <?= t('admin_site_url') ?> <span class="text-red-500">*</span>
                    </label>
                    <input type="url" id="site_url" name="site_url"
                           value="<?= htmlspecialchars($data['site_url'] ?? 'http://' . ($_SERVER['HTTP_HOST'] ?? 'localhost')) ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           required>
                </div>

                <div>
                    <label for="admin_path" class="block text-sm font-medium text-gray-700 mb-1">
                        <?= t('admin_path') ?>
                    </label>
                    <div class="flex items-center">
                        <span class="text-gray-500 mr-2"><?= $_SERVER['HTTP_HOST'] ?? 'localhost' ?>/</span>
                        <input type="text" id="admin_path" name="admin_path"
                               value="<?= htmlspecialchars($data['admin_path'] ?? 'admin') ?>"
                               class="flex-1 px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               pattern="[a-zA-Z0-9_-]+"
                               title="<?= t('admin_path_pattern') ?>">
                    </div>
                    <p class="mt-1 text-sm text-gray-500"><?= t('admin_path_hint') ?></p>
                </div>
            </div>
        </div>

        <!-- Admin Account -->
        <div>
            <h3 class="font-semibold text-gray-900 mb-4"><?= t('admin_account') ?></h3>

            <div class="space-y-4">
                <div>
                    <label for="admin_email" class="block text-sm font-medium text-gray-700 mb-1">
                        <?= t('admin_email') ?> <span class="text-red-500">*</span>
                    </label>
                    <input type="email" id="admin_email" name="admin_email"
                           value="<?= htmlspecialchars($data['admin_email'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           required>
                </div>

                <div>
                    <label for="admin_name" class="block text-sm font-medium text-gray-700 mb-1">
                        <?= t('admin_name') ?> <span class="text-red-500">*</span>
                    </label>
                    <input type="text" id="admin_name" name="admin_name"
                           value="<?= htmlspecialchars($data['admin_name'] ?? '') ?>"
                           class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                           required>
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                    <div>
                        <label for="admin_password" class="block text-sm font-medium text-gray-700 mb-1">
                            <?= t('admin_password') ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="password" id="admin_password" name="admin_password"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               minlength="8"
                               required>
                        <p class="mt-1 text-sm text-gray-500"><?= t('admin_password_hint') ?></p>
                    </div>

                    <div>
                        <label for="admin_password_confirm" class="block text-sm font-medium text-gray-700 mb-1">
                            <?= t('admin_password_confirm') ?> <span class="text-red-500">*</span>
                        </label>
                        <input type="password" id="admin_password_confirm" name="admin_password_confirm"
                               class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               minlength="8"
                               required>
                    </div>
                </div>
            </div>
        </div>

        <div class="border-t pt-6 flex justify-between">
            <a href="?step=database<?= $langParam ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                <?= t('prev') ?>
            </a>
            <button type="submit" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
                <?= t('admin_start_install') ?>
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
    </form>
</div>
