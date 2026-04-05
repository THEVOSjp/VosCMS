<?php
/**
 * RezlyX Admin - 서비스 설정 페이지
 * 탭: 기본설정 / 카테고리 관리 / 공휴일 관리
 */

if (!function_exists('__')) {
    require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';
}

$pageTitle = __('admin.nav.services_settings') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
$currentTab = $settingsTab ?? 'general';

// 메시지 처리
$message = '';
$messageType = '';

// DB 연결
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

    // 설정 로드
    $settings = [];
    $stmt = $pdo->query("SELECT `key`, `value` FROM {$prefix}settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['key']] = $row['value'];
    }
} catch (PDOException $e) {
    $message = 'DB 연결 오류: ' . $e->getMessage();
    $messageType = 'error';
}

// 탭별 컨텐츠 로드
ob_start();
switch ($currentTab) {
    case 'categories':
        include __DIR__ . '/settings-categories.php';
        break;
    case 'holidays':
        include __DIR__ . '/settings-holidays.php';
        break;
    default:
        include __DIR__ . '/settings-general.php';
        break;
}
$pageContent = ob_get_clean();
?>
<!DOCTYPE html>
<html lang="<?php echo $config['locale'] ?? 'ko'; ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <?php include BASE_PATH . '/resources/views/admin/partials/pwa-head.php'; ?>

    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <style>body { font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; }</style>
    <script>
        if (localStorage.getItem('darkMode') === 'true' ||
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-zinc-100 dark:bg-zinc-900 min-h-screen transition-colors">
    <div class="flex">
        <?php include BASE_PATH . '/resources/views/admin/partials/admin-sidebar.php'; ?>

        <main class="flex-1 ml-64">
            <?php
            $pageHeaderTitle = __('admin.nav.services_settings');
            include BASE_PATH . '/resources/views/admin/partials/admin-topbar.php';
            ?>

            <div class="p-6">
                <!-- 탭 네비게이션 -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm mb-6 overflow-hidden">
                    <div class="border-b border-zinc-200 dark:border-zinc-700">
                        <nav class="flex -mb-px overflow-x-auto" aria-label="Tabs">
                            <?php
                            $tabs = [
                                'general' => [
                                    'label' => __('services.settings.tabs.general'),
                                    'icon' => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.065 2.572c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.572 1.065c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.065-2.572c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z M15 12a3 3 0 11-6 0 3 3 0 016 0z',
                                ],
                                'categories' => [
                                    'label' => __('services.categories.title'),
                                    'icon' => 'M7 7h.01M7 3h5c.512 0 1.024.195 1.414.586l7 7a2 2 0 010 2.828l-7 7a2 2 0 01-2.828 0l-7-7A1.994 1.994 0 013 12V7a4 4 0 014-4z',
                                ],
                                'holidays' => [
                                    'label' => __('services.settings.tabs.holidays'),
                                    'icon' => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
                                ],
                            ];

                            foreach ($tabs as $key => $tab):
                                $isActive = $currentTab === $key;
                                $url = $adminUrl . '/services/settings/' . $key;
                            ?>
                            <a href="<?php echo $url; ?>"
                               class="flex items-center px-4 py-4 text-sm font-medium border-b-2 whitespace-nowrap <?php echo $isActive
                                   ? 'border-blue-500 text-blue-600 dark:text-blue-400'
                                   : 'border-transparent text-zinc-500 hover:text-zinc-700 hover:border-zinc-300 dark:text-zinc-400 dark:hover:text-zinc-300'; ?>">
                                <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?php echo $tab['icon']; ?>"/>
                                </svg>
                                <?php echo $tab['label']; ?>
                            </a>
                            <?php endforeach; ?>
                        </nav>
                    </div>
                </div>

                <!-- 탭 컨텐츠 -->
                <?php echo $pageContent; ?>
            </div>
        </main>
    </div>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <?php include BASE_PATH . '/resources/views/admin/components/multilang-modal.php'; ?>
    <?php include BASE_PATH . '/resources/views/admin/partials/result-modal.php'; ?>
</body>
</html>
