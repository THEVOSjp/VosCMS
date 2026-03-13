<?php
/**
 * Requirements Check Step
 */

$requirements = [
    'php' => [
        'label' => t('req_php_version'),
        'required' => '8.0.0',
        'current' => PHP_VERSION,
        'passed' => version_compare(PHP_VERSION, '8.0.0', '>='),
    ],
    'pdo' => [
        'label' => t('req_pdo'),
        'required' => t('required'),
        'current' => extension_loaded('pdo') ? t('installed') : t('not_installed'),
        'passed' => extension_loaded('pdo'),
    ],
    'pdo_mysql' => [
        'label' => t('req_pdo_mysql'),
        'required' => t('required'),
        'current' => extension_loaded('pdo_mysql') ? t('installed') : t('not_installed'),
        'passed' => extension_loaded('pdo_mysql'),
    ],
    'mbstring' => [
        'label' => t('req_mbstring'),
        'required' => t('required'),
        'current' => extension_loaded('mbstring') ? t('installed') : t('not_installed'),
        'passed' => extension_loaded('mbstring'),
    ],
    'json' => [
        'label' => t('req_json'),
        'required' => t('required'),
        'current' => extension_loaded('json') ? t('installed') : t('not_installed'),
        'passed' => extension_loaded('json'),
    ],
    'openssl' => [
        'label' => t('req_openssl'),
        'required' => t('required'),
        'current' => extension_loaded('openssl') ? t('installed') : t('not_installed'),
        'passed' => extension_loaded('openssl'),
    ],
    'curl' => [
        'label' => t('req_curl'),
        'required' => t('recommended'),
        'current' => extension_loaded('curl') ? t('installed') : t('not_installed'),
        'passed' => extension_loaded('curl'),
        'optional' => true,
    ],
    'gd' => [
        'label' => t('req_gd'),
        'required' => t('recommended'),
        'current' => extension_loaded('gd') ? t('installed') : t('not_installed'),
        'passed' => extension_loaded('gd'),
        'optional' => true,
    ],
];

$directories = [
    BASE_PATH . '/storage/cache' => is_writable(BASE_PATH . '/storage/cache'),
    BASE_PATH . '/storage/logs' => is_writable(BASE_PATH . '/storage/logs'),
    BASE_PATH . '/storage/uploads' => is_writable(BASE_PATH . '/storage/uploads'),
    BASE_PATH . '/storage/sessions' => is_writable(BASE_PATH . '/storage/sessions'),
    BASE_PATH . '/assets' => is_writable(BASE_PATH . '/assets'),
];

$allPassed = true;
foreach ($requirements as $req) {
    if (!($req['optional'] ?? false) && !$req['passed']) {
        $allPassed = false;
        break;
    }
}
foreach ($directories as $writable) {
    if (!$writable) {
        $allPassed = false;
        break;
    }
}

$langParam = isset($_GET['lang']) ? '&lang=' . htmlspecialchars($_GET['lang']) : '';
?>

<div class="bg-white rounded-lg shadow-sm p-8">
    <h2 class="text-xl font-bold text-gray-900 mb-6"><?= t('req_title') ?></h2>

    <!-- PHP Extensions -->
    <div class="mb-8">
        <h3 class="font-semibold text-gray-900 mb-4"><?= t('req_php_ext') ?></h3>
        <div class="border rounded-lg overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-600"><?= t('req_col_item') ?></th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-600"><?= t('req_col_required') ?></th>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-600"><?= t('req_col_current') ?></th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-gray-600"><?= t('req_col_result') ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($requirements as $key => $req): ?>
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900"><?= $req['label'] ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600"><?= $req['required'] ?></td>
                        <td class="px-4 py-3 text-sm text-gray-600"><?= $req['current'] ?></td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($req['passed']): ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/>
                                    </svg>
                                    <?= t('req_pass') ?>
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium <?= ($req['optional'] ?? false) ? 'bg-yellow-100 text-yellow-800' : 'bg-red-100 text-red-800' ?>">
                                    <svg class="w-3 h-3 mr-1" fill="currentColor" viewBox="0 0 20 20">
                                        <path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z" clip-rule="evenodd"/>
                                    </svg>
                                    <?= ($req['optional'] ?? false) ? t('req_warn') : t('req_fail') ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Directory Permissions -->
    <div class="mb-8">
        <h3 class="font-semibold text-gray-900 mb-4"><?= t('req_dir_title') ?></h3>
        <div class="border rounded-lg overflow-hidden">
            <table class="w-full">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-4 py-3 text-left text-sm font-medium text-gray-600"><?= t('req_col_dir') ?></th>
                        <th class="px-4 py-3 text-center text-sm font-medium text-gray-600"><?= t('req_col_write') ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y">
                    <?php foreach ($directories as $path => $writable): ?>
                    <tr>
                        <td class="px-4 py-3 text-sm text-gray-900 font-mono">
                            <?= str_replace(BASE_PATH, '', $path) ?>
                        </td>
                        <td class="px-4 py-3 text-center">
                            <?php if ($writable): ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-green-100 text-green-800">
                                    <?= t('req_writable') ?>
                                </span>
                            <?php else: ?>
                                <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-800">
                                    <?= t('req_not_writable') ?>
                                </span>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>

    <?php if (!$allPassed): ?>
    <div class="p-4 bg-red-50 border border-red-200 rounded-lg mb-6">
        <div class="flex items-center">
            <svg class="w-5 h-5 text-red-600 mr-2" fill="currentColor" viewBox="0 0 20 20">
                <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
            </svg>
            <span class="text-red-800 font-medium"><?= t('req_not_met') ?></span>
        </div>
    </div>
    <?php endif; ?>

    <div class="flex justify-between">
        <a href="?step=welcome<?= $langParam ?>" class="inline-flex items-center px-4 py-2 border border-gray-300 text-gray-700 rounded-lg hover:bg-gray-50 transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
            </svg>
            <?= t('prev') ?>
        </a>
        <?php if ($allPassed): ?>
        <a href="?step=database<?= $langParam ?>" class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
            <?= t('next') ?>
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
        <?php else: ?>
        <button onclick="location.reload()" class="inline-flex items-center px-6 py-3 bg-gray-600 text-white font-semibold rounded-lg hover:bg-gray-700 transition">
            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/>
            </svg>
            <?= t('req_recheck') ?>
        </button>
        <?php endif; ?>
    </div>
</div>
