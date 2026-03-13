<?php
/**
 * Database Configuration Step
 */

$error = $_SESSION['install_error'] ?? null;
$data = $_SESSION['install_db'] ?? [];
unset($_SESSION['install_error']);

$langParam = isset($_GET['lang']) ? '&lang=' . htmlspecialchars($_GET['lang']) : '';
?>

<div class="bg-white rounded-lg shadow-sm p-8">
    <h2 class="text-xl font-bold text-gray-900 mb-6"><?= t('db_title') ?></h2>

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

    <form method="POST" action="?step=database<?= $langParam ?>" class="space-y-6">
        <input type="hidden" name="action" value="database">

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="db_host" class="block text-sm font-medium text-gray-700 mb-1">
                    <?= t('db_host') ?> <span class="text-red-500">*</span>
                </label>
                <input type="text" id="db_host" name="db_host"
                       value="<?= htmlspecialchars($data['db_host'] ?? '127.0.0.1') ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       required>
            </div>

            <div>
                <label for="db_port" class="block text-sm font-medium text-gray-700 mb-1">
                    <?= t('db_port') ?>
                </label>
                <input type="text" id="db_port" name="db_port"
                       value="<?= htmlspecialchars($data['db_port'] ?? '3306') ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        <div>
            <label for="db_name" class="block text-sm font-medium text-gray-700 mb-1">
                <?= t('db_name') ?> <span class="text-red-500">*</span>
            </label>
            <input type="text" id="db_name" name="db_name"
                   value="<?= htmlspecialchars($data['db_name'] ?? 'rezlyx') ?>"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                   required>
            <p class="mt-1 text-sm text-gray-500"><?= t('db_auto_create') ?></p>
        </div>

        <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
            <div>
                <label for="db_user" class="block text-sm font-medium text-gray-700 mb-1">
                    <?= t('db_user') ?> <span class="text-red-500">*</span>
                </label>
                <input type="text" id="db_user" name="db_user"
                       value="<?= htmlspecialchars($data['db_user'] ?? 'root') ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                       required>
            </div>

            <div>
                <label for="db_pass" class="block text-sm font-medium text-gray-700 mb-1">
                    <?= t('db_pass') ?>
                </label>
                <input type="password" id="db_pass" name="db_pass"
                       value="<?= htmlspecialchars($data['db_pass'] ?? '') ?>"
                       class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            </div>
        </div>

        <div>
            <label for="db_prefix" class="block text-sm font-medium text-gray-700 mb-1">
                <?= t('db_prefix') ?>
            </label>
            <input type="text" id="db_prefix" name="db_prefix"
                   value="<?= htmlspecialchars($data['db_prefix'] ?? 'rzx_') ?>"
                   class="w-full px-4 py-2 border border-gray-300 rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
            <p class="mt-1 text-sm text-gray-500"><?= t('db_prefix_hint') ?></p>
        </div>

        <div class="border-t pt-6 flex justify-between">
            <a href="?step=requirements<?= $langParam ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                </svg>
                <?= t('prev') ?>
            </a>
            <button type="submit" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
                <?= t('db_test_next') ?>
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                </svg>
            </button>
        </div>
    </form>
</div>
