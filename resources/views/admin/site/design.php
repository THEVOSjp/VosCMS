<?php
/**
 * RezlyX Admin - 디자인 관리 페이지
 */
$pageTitle = '디자인 관리 - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';

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
            $pageHeaderTitle = __('site.design.title');
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
                $headerIcon = 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01';
                $headerTitle = __('site.design.title');
                $headerDescription = __('site.design.description');
                $headerIconColor = ''; $headerActions = '';
                include __DIR__ . '/../components/settings-header.php';
                ?>
                </div>

                <!-- Design Options Grid -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
                    <!-- Theme Settings -->
                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 transition-colors">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('site.design.theme_title') ?></h3>
                        </div>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4"><?= __('site.design.theme_desc') ?></p>
                        <span class="inline-block px-3 py-1 text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 rounded-full"><?= __('site.design.coming_soon') ?></span>
                    </div>

                    <!-- Layout Settings -->
                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 transition-colors">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('site.design.layout_title') ?></h3>
                        </div>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4"><?= __('site.design.layout_desc') ?></p>
                        <span class="inline-block px-3 py-1 text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 rounded-full"><?= __('site.design.coming_soon') ?></span>
                    </div>

                    <!-- Header/Footer -->
                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 transition-colors">
                        <div class="flex items-center mb-4">
                            <div class="w-10 h-10 bg-green-100 dark:bg-green-900/30 rounded-lg flex items-center justify-center mr-3">
                                <svg class="w-6 h-6 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 6h16M4 12h16m-7 6h7"/>
                                </svg>
                            </div>
                            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('site.design.header_footer_title') ?></h3>
                        </div>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4"><?= __('site.design.header_footer_desc') ?></p>
                        <span class="inline-block px-3 py-1 text-xs font-medium bg-amber-100 text-amber-800 dark:bg-amber-900/30 dark:text-amber-400 rounded-full"><?= __('site.design.coming_soon') ?></span>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Dark mode toggle is handled by admin-topbar.php -->
</body>
</html>
