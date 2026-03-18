<?php
/**
 * RezlyX - 개인정보처리방침 (고객용)
 */
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

    $stmt = $pdo->prepare("SELECT title, content FROM rzx_page_contents WHERE page_slug = 'privacy' AND locale = ? AND is_active = 1");
    $stmt->execute([$currentLocale]);
    $customContent = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$customContent) {
        $stmt->execute(['ko']);
        $customContent = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // 무시
}

$customPageTitle = !empty($customContent['title']) ? $customContent['title'] : __('customer.privacy.title');
$_sName = function_exists('get_site_name') ? get_site_name() : ($siteSettings['site_name'] ?? ($config['app_name'] ?? 'RezlyX'));
$pageTitle = $_sName . ' - ' . $customPageTitle;

// page-content 스타일을 headExtra로 주입
$headExtra = '<style>
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
</style>';

if ($isEmbed) {
    // embed 모드: 최소 HTML
?>
<!DOCTYPE html>
<html lang="<?= $config['locale'] ?? 'ko' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <?= $headExtra ?>
    <script>
        if (localStorage.getItem('darkMode') === 'true' ||
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-gray-50 dark:bg-zinc-900">
    <main class="p-6">
<?php } else {
    // 일반 모드: base-header/footer 사용
} ?>

    <div class="<?= $isEmbed ? '' : 'max-w-4xl mx-auto px-4 py-12' ?>">
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg dark:shadow-zinc-900/50 p-8 md:p-12 transition-colors">

            <?php if (!empty($customContent['content'])): ?>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                <?= htmlspecialchars($customContent['title'] ?: __('customer.privacy.title')) ?>
            </h1>
            <p class="text-gray-500 dark:text-zinc-400 mb-8"><?= __('customer.privacy.last_updated') ?>: <?= date('Y-m-d') ?></p>
            <div class="page-content text-gray-700 dark:text-zinc-300">
                <?= $customContent['content'] ?>
            </div>
            <?php else: ?>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2"><?= __('customer.privacy.title') ?></h1>
            <div class="mt-8 p-6 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg text-center">
                <p class="text-zinc-500 dark:text-zinc-400"><?= __('customer.privacy.not_configured') ?></p>
            </div>
            <?php endif; ?>

            <div class="mt-8 pt-6 border-t dark:border-zinc-700">
                <p class="text-sm text-gray-500 dark:text-zinc-400">
                    <?= __('customer.privacy.contact', ['name' => htmlspecialchars($_sName)]) ?>
                </p>
            </div>
        </div>

        <?php if (!$isEmbed): ?>
        <div class="flex justify-between items-center mt-8">
            <a href="<?= $baseUrl ?>/terms" class="text-blue-600 dark:text-blue-400 hover:underline">&larr; <?= __('customer.privacy.terms_link') ?></a>
            <a href="<?= $baseUrl ?>/" class="text-blue-600 dark:text-blue-400 hover:underline"><?= __('customer.privacy.back_home') ?> &rarr;</a>
        </div>
        <?php endif; ?>
    </div>

<?php if ($isEmbed): ?>
    </main>
</body>
</html>
<?php else:
endif; ?>
