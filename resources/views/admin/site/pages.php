<?php
/**
 * RezlyX Admin - 페이지 관리
 */
$pageTitle = '페이지 관리 - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';

// Database connection
try {
    $pdo = new PDO(
        'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx'),
        $_ENV['DB_USERNAME'] ?? 'root',
        $_ENV['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('데이터베이스 연결 실패: ' . $e->getMessage());
}

$message = '';
$messageType = '';

// Base URLs for navigation
$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
?>
<!DOCTYPE html>
<html lang="<?php echo $config['locale'] ?? 'ko'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = { darkMode: 'class' }
    </script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <style>
        body { font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; }
    </style>
    <script>
        if (localStorage.getItem('darkMode') === 'true' ||
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-zinc-100 dark:bg-zinc-900 min-h-screen transition-colors">
    <div class="flex">
        <!-- Sidebar -->
        <?php include __DIR__ . '/../partials/admin-sidebar.php'; ?>

        <!-- Main Content -->
        <main class="flex-1 ml-64">
            <!-- Top Bar -->
            <?php
            $pageHeaderTitle = __('site.pages.title');
            include __DIR__ . '/../partials/admin-topbar.php';
            ?>

            <!-- Page Content -->
            <div class="p-6">
                <?php if ($message): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 text-green-800 border border-green-200' : 'bg-red-50 text-red-800 border border-red-200'; ?>">
                    <?php echo htmlspecialchars($message); ?>
                </div>
                <?php endif; ?>

                <!-- Header -->
                <div class="mb-6">
                <?php
                $headerIcon = 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z';
                $headerTitle = __('site.pages.title');
                $headerDescription = __('site.pages.description');
                $headerIconColor = '';
                $headerActions = '<button class="px-4 py-2 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition flex items-center"><svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>' . __('site.pages.add') . '</button>';
                include __DIR__ . '/../components/settings-header.php';
                ?>
                </div>

                <!-- Page List -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm transition-colors">
                    <div class="p-4 border-b border-zinc-200 dark:border-zinc-700">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('site.pages.list') ?></h2>
                    </div>

                    <!-- Default Pages -->
                    <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
                        <div class="p-4 flex items-center justify-between hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-medium text-zinc-900 dark:text-white"><?= __('site.pages.home') ?></h4>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">/</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium px-2 py-0.5 bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300 rounded"><?= __('site.pages.type_widget') ?></span>
                                <span class="text-xs font-medium px-2 py-0.5 bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 rounded"><?= __('site.pages.system_page') ?></span>
                                <a href="<?= $adminUrl ?>/site/pages/widget-builder"
                                   class="px-3 py-1.5 text-sm font-medium text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/30 rounded-lg transition">
                                    <?= __('admin.buttons.edit') ?>
                                </a>
                            </div>
                        </div>

                        <div class="p-4 flex items-center justify-between hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-medium text-zinc-900 dark:text-white"><?= __('site.pages.terms') ?></h4>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">/terms</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded"><?= __('site.pages.type_document') ?></span>
                                <span class="text-xs font-medium px-2 py-0.5 bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 rounded"><?= __('site.pages.system_page') ?></span>
                                <a href="<?= $adminUrl ?>/site/pages/edit?slug=terms"
                                   class="px-3 py-1.5 text-sm font-medium text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/30 rounded-lg transition">
                                    <?= __('admin.buttons.edit') ?>
                                </a>
                            </div>
                        </div>

                        <div class="p-4 flex items-center justify-between hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-medium text-zinc-900 dark:text-white"><?= __('site.pages.privacy') ?></h4>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">/privacy</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded"><?= __('site.pages.type_document') ?></span>
                                <span class="text-xs font-medium px-2 py-0.5 bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 rounded"><?= __('site.pages.system_page') ?></span>
                                <a href="<?= $adminUrl ?>/site/pages/edit?slug=privacy"
                                   class="px-3 py-1.5 text-sm font-medium text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/30 rounded-lg transition">
                                    <?= __('admin.buttons.edit') ?>
                                </a>
                            </div>
                        </div>

                        <!-- 데이터 관리 가이드 (문서 페이지) -->
                        <div class="p-4 flex items-center justify-between hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-amber-100 dark:bg-amber-900/30 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-medium text-zinc-900 dark:text-white"><?= __('site.pages.data_policy') ?></h4>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">/data-policy</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded"><?= __('site.pages.type_document') ?></span>
                                <span class="text-xs font-medium px-2 py-0.5 bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 rounded"><?= __('site.pages.system_page') ?></span>
                                <a href="<?= $adminUrl ?>/site/pages/compliance"
                                   class="px-3 py-1.5 text-sm font-medium text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/30 rounded-lg transition">
                                    <?= __('admin.buttons.edit') ?>
                                </a>
                            </div>
                        </div>

                        <!-- 취소 환불 규정 (문서 페이지) -->
                        <div class="p-4 flex items-center justify-between hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
                            <div class="flex items-center">
                                <div class="w-10 h-10 bg-red-100 dark:bg-red-900/30 rounded-lg flex items-center justify-center mr-3">
                                    <svg class="w-5 h-5 text-red-600 dark:text-red-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/>
                                    </svg>
                                </div>
                                <div>
                                    <h4 class="font-medium text-zinc-900 dark:text-white"><?= __('site.pages.refund_policy') ?></h4>
                                    <p class="text-sm text-zinc-500 dark:text-zinc-400">/refund-policy</p>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <span class="text-xs font-medium px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300 rounded"><?= __('site.pages.type_document') ?></span>
                                <span class="text-xs font-medium px-2 py-0.5 bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 rounded"><?= __('site.pages.system_page') ?></span>
                                <a href="<?= $adminUrl ?>/site/pages/edit?slug=refund-policy"
                                   class="px-3 py-1.5 text-sm font-medium text-blue-600 hover:text-blue-700 hover:bg-blue-50 dark:text-blue-400 dark:hover:bg-blue-900/30 rounded-lg transition">
                                    <?= __('admin.buttons.edit') ?>
                                </a>
                            </div>
                        </div>
                    </div>

                    <!-- Empty State for Custom Pages -->
                    <div class="p-8 text-center border-t border-zinc-200 dark:border-zinc-700">
                        <svg class="w-12 h-12 mx-auto mb-4 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 13h6m-3-3v6m5 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/>
                        </svg>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('site.pages.empty') ?></p>
                        <p class="text-xs text-zinc-400 dark:text-zinc-500 mt-1"><?= __('site.pages.empty_hint') ?></p>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Dark mode toggle is handled by admin-topbar.php -->
</body>
</html>
