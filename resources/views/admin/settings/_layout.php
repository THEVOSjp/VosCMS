<?php
/**
 * RezlyX Admin Settings Layout
 * Common layout for all settings sub-pages
 *
 * Required variables:
 * - $pageTitle: Page title
 * - $pageContent: HTML content for the page
 * - $config: Application config
 * - $settings: Settings array
 * - $message: Flash message (optional)
 * - $messageType: Message type (success/error)
 */
?>
<!DOCTYPE html>
<html lang="<?php echo $config['locale'] ?? 'ko'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <!-- PWA Admin -->
    <?php include __DIR__ . '/../partials/pwa-head.php'; ?>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class'
        }
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
            $pageHeaderTitle = __('settings.title');
            include __DIR__ . '/../partials/admin-topbar.php';
            ?>

            <!-- Settings Content -->
            <div class="p-6">
                <?php if (!empty($message)): ?>
                <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-300 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-300 border border-red-200 dark:border-red-800'; ?>">
                    <div class="flex items-center">
                        <?php if ($messageType === 'success'): ?>
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                        <?php else: ?>
                        <svg class="w-5 h-5 mr-2" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7 4a1 1 0 11-2 0 1 1 0 012 0zm-1-9a1 1 0 00-1 1v4a1 1 0 102 0V6a1 1 0 00-1-1z" clip-rule="evenodd"/>
                        </svg>
                        <?php endif; ?>
                        <?php echo htmlspecialchars($message); ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Page Content -->
                <?php echo $pageContent ?? ''; ?>
            </div>
        </main>
    </div>

    <script>
        // Sidebar menu toggles
        function toggleSiteMenu() {
            const subMenu = document.getElementById('siteSubMenu');
            const arrow = document.getElementById('siteMenuArrow');
            if (subMenu && arrow) {
                subMenu.classList.toggle('hidden');
                arrow.classList.toggle('rotate-180');
            }
        }

        function toggleSettingsMenu() {
            const subMenu = document.getElementById('settingsSubMenu');
            const arrow = document.getElementById('settingsMenuArrow');
            if (subMenu && arrow) {
                subMenu.classList.toggle('hidden');
                arrow.classList.toggle('rotate-180');
            }
        }

        function toggleMembersMenu() {
            const subMenu = document.getElementById('membersSubMenu');
            const arrow = document.getElementById('membersMenuArrow');
            if (subMenu && arrow) {
                subMenu.classList.toggle('hidden');
                arrow.classList.toggle('rotate-180');
            }
        }
    </script>

    <!-- PWA Admin Service Worker Registration -->
    <?php include __DIR__ . '/../partials/pwa-scripts.php'; ?>
</body>
</html>
