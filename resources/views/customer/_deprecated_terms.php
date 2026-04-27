<?php
/**
 * RezlyX - 이용약관 (고객용)
 */
$siteName = function_exists('get_site_name') ? get_site_name() : ($siteSettings['site_name'] ?? ($config['app_name'] ?? 'RezlyX'));
$logoType = $siteSettings['logo_type'] ?? 'text';
$logoImage = $siteSettings['logo_image'] ?? '';

if (!empty($config['app_url'])) {
    $parsedUrl = parse_url($config['app_url']);
    $baseUrl = rtrim($parsedUrl['path'] ?? '', '/');
} else {
    $baseUrl = '';
}
$isEmbed = isset($_GET['embed']) && $_GET['embed'] === '1';
if ($isEmbed) $__layout = false; // embed 모드: 레이아웃 미적용
$currentLocale = current_locale();

// DB에서 커스텀 콘텐츠 로드
$customContent = null;
try {
    $pdo = new PDO(
        'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx'),
        $_ENV['DB_USERNAME'] ?? 'root',
        $_ENV['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );

    $stmt = $pdo->prepare("SELECT title, content FROM rzx_page_contents WHERE page_slug = 'terms' AND locale = ? AND is_active = 1");
    $stmt->execute([$currentLocale]);
    $customContent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customContent) {
        $stmt->execute(['ko']);
        $customContent = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // 무시
}

$customPageTitle = !empty($customContent['title']) ? $customContent['title'] : __('customer.terms.title');
$pageTitle = $siteName . ' - ' . $customPageTitle;
?>
<!DOCTYPE html>
<html lang="<?= $config['locale'] ?? 'ko' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <style>
        body { font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; }
        .page-content h1 { font-size: 1.5em; font-weight: bold; margin: 1em 0 0.5em; }
        .page-content h2 { font-size: 1.25em; font-weight: bold; margin: 1em 0 0.5em; }
        .page-content h3 { font-size: 1.1em; font-weight: bold; margin: 0.8em 0 0.4em; }
        .page-content p { margin: 0.8em 0; }
        .page-content ul, .page-content ol { margin: 0.8em 0; padding-left: 1.5em; }
        .page-content ul { list-style: disc; }
        .page-content ol { list-style: decimal; }
        .page-content li { margin: 0.3em 0; }
        .page-content table { width: 100%; border-collapse: collapse; font-size: 0.875rem; margin: 1em 0; }
        .page-content th { background: #f4f4f5; padding: 0.5rem 1rem; text-align: left; font-weight: 600; border: 1px solid #d4d4d8; }
        .page-content td { padding: 0.5rem 1rem; border: 1px solid #d4d4d8; }
        .page-content a { color: #2563eb; text-decoration: underline; }
        .dark .page-content th { background: #3f3f46; border-color: #52525b; }
        .dark .page-content td { border-color: #52525b; }
        .dark .page-content a { color: #60a5fa; }
    </style>
    <script>
        if (localStorage.getItem('darkMode') === 'true' ||
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-gray-50 dark:bg-zinc-900 min-h-screen transition-colors duration-200">
    <?php if (!$isEmbed): ?>
    <header class="bg-white dark:bg-zinc-800 shadow-sm sticky top-0 z-50 transition-colors duration-200">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="flex items-center justify-between h-16">
                <a href="<?= $baseUrl ?>/" class="flex items-center text-xl font-bold text-blue-600 dark:text-blue-400">
                    <?php if ($logoType === 'image' && $logoImage): ?>
                        <img src="<?= $baseUrl . htmlspecialchars($logoImage) ?>" alt="<?= htmlspecialchars($siteName) ?>" class="h-10 object-contain">
                    <?php elseif ($logoType === 'image_text' && $logoImage): ?>
                        <img src="<?= $baseUrl . htmlspecialchars($logoImage) ?>" alt="" class="h-10 object-contain mr-2">
                        <span><?= htmlspecialchars($siteName) ?></span>
                    <?php else: ?>
                        <span><?= htmlspecialchars($siteName) ?></span>
                    <?php endif; ?>
                </a>
                <div class="flex items-center space-x-3">
                    <button id="darkModeBtn" class="p-2 text-gray-600 dark:text-zinc-300 hover:text-blue-600 dark:hover:text-blue-400 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-700">
                        <svg id="sunIcon" class="w-5 h-5 hidden dark:block" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 3v1m0 16v1m9-9h-1M4 12H3m15.364 6.364l-.707-.707M6.343 6.343l-.707-.707m12.728 0l-.707.707M6.343 17.657l-.707.707M16 12a4 4 0 11-8 0 4 4 0 018 0z"/>
                        </svg>
                        <svg id="moonIcon" class="w-5 h-5 block dark:hidden" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20.354 15.354A9 9 0 018.646 3.646 9.003 9.003 0 0012 21a9.003 9.003 0 008.354-5.646z"/>
                        </svg>
                    </button>
                    <a href="<?= $baseUrl ?>/login" class="px-4 py-2 text-sm font-medium text-gray-700 dark:text-zinc-300 hover:text-blue-600 dark:hover:text-blue-400"><?= __('customer.login') ?></a>
                </div>
            </div>
        </div>
    </header>
    <?php endif; ?>

    <main class="<?= $isEmbed ? 'p-6' : 'max-w-4xl mx-auto px-4 py-12' ?>">
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg dark:shadow-zinc-900/50 p-8 md:p-12 transition-colors">

            <?php if (!empty($customContent['content'])): ?>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                <?= htmlspecialchars($customContent['title'] ?: __('customer.terms.title')) ?>
            </h1>
            <p class="text-gray-500 dark:text-zinc-400 mb-8"><?= __('customer.terms.last_updated') ?>: <?= date('Y-m-d') ?></p>
            <div class="page-content text-gray-700 dark:text-zinc-300">
                <?= $customContent['content'] ?>
            </div>
            <?php else: ?>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2"><?= __('customer.terms.title') ?></h1>
            <div class="mt-8 p-6 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg text-center">
                <p class="text-zinc-500 dark:text-zinc-400"><?= __('customer.terms.not_configured') ?></p>
            </div>
            <?php endif; ?>

            <div class="mt-8 pt-6 border-t dark:border-zinc-700">
                <p class="text-sm text-gray-500 dark:text-zinc-400">
                    <?= __('customer.terms.contact', ['name' => htmlspecialchars($siteName)]) ?>
                </p>
            </div>
        </div>

    </main>

    <?php if (!$isEmbed): ?>
    <footer class="bg-white dark:bg-zinc-800 border-t dark:border-zinc-700 mt-12">
        <div class="max-w-7xl mx-auto px-4 py-6">
            <p class="text-center text-gray-500 dark:text-zinc-400 text-sm">
                &copy; <?= date('Y') ?> <?= htmlspecialchars($siteName) ?>. All rights reserved.
            </p>
        </div>
    </footer>
    <script>
        var darkModeBtn = document.getElementById('darkModeBtn');
        if (darkModeBtn) {
            darkModeBtn.addEventListener('click', function() {
                var isDark = document.documentElement.classList.toggle('dark');
                localStorage.setItem('darkMode', isDark);
            });
        }
    </script>
    <?php endif; ?>
</body>
</html>
