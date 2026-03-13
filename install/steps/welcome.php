<div class="bg-white rounded-lg shadow-sm p-8">
    <div class="text-center mb-8">
        <div class="inline-flex items-center justify-center w-20 h-20 bg-blue-100 rounded-full mb-4">
            <svg class="w-10 h-10 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
            </svg>
        </div>
        <h2 class="text-2xl font-bold text-gray-900 mb-2"><?= t('welcome_title') ?></h2>
        <p class="text-gray-600"><?= t('welcome_desc') ?></p>
    </div>

    <div class="space-y-4 mb-8">
        <div class="flex items-start p-4 bg-blue-50 rounded-lg">
            <svg class="w-6 h-6 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
            <div>
                <h3 class="font-semibold text-gray-900"><?= t('welcome_check_title') ?></h3>
                <p class="text-sm text-gray-600"><?= t('welcome_check_desc') ?></p>
            </div>
        </div>

        <div class="flex items-start p-4 bg-blue-50 rounded-lg">
            <svg class="w-6 h-6 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
            </svg>
            <div>
                <h3 class="font-semibold text-gray-900"><?= t('welcome_db_title') ?></h3>
                <p class="text-sm text-gray-600"><?= t('welcome_db_desc') ?></p>
            </div>
        </div>

        <div class="flex items-start p-4 bg-blue-50 rounded-lg">
            <svg class="w-6 h-6 text-blue-600 mt-0.5 mr-3 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
            </svg>
            <div>
                <h3 class="font-semibold text-gray-900"><?= t('welcome_admin_title') ?></h3>
                <p class="text-sm text-gray-600"><?= t('welcome_admin_desc') ?></p>
            </div>
        </div>
    </div>

    <div class="border-t pt-6">
        <h3 class="font-semibold text-gray-900 mb-3"><?= t('welcome_prereq') ?></h3>
        <ul class="text-sm text-gray-600 space-y-2">
            <li class="flex items-center">
                <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <?= t('welcome_prereq_db') ?>
            </li>
            <li class="flex items-center">
                <svg class="w-4 h-4 text-green-500 mr-2" fill="currentColor" viewBox="0 0 20 20">
                    <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                </svg>
                <?= t('welcome_prereq_admin') ?>
            </li>
        </ul>
    </div>

    <div class="mt-8 flex justify-end">
        <a href="?step=requirements<?= isset($_GET['lang']) ? '&lang=' . htmlspecialchars($_GET['lang']) : '' ?>"
           class="inline-flex items-center px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition">
            <?= t('start_install') ?>
            <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
        </a>
    </div>
</div>
