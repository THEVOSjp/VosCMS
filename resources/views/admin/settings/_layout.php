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

    <!-- 공통 결과 모달 -->
    <?php include __DIR__ . '/../partials/result-modal.php'; ?>
    <!-- PWA Admin Service Worker Registration -->
    <?php include __DIR__ . '/../partials/pwa-scripts.php'; ?>
</body>
</html>
