<?php
/**
 * RezlyX - 데이터 관리 정책 (고객용)
 * 운영 국가/업종에 따른 데이터 보관 및 개인정보 관리 안내
 */
$siteName = $siteSettings['site_name'] ?? ($config['app_name'] ?? 'RezlyX');
$logoType = $siteSettings['logo_type'] ?? 'text';
$logoImage = $siteSettings['logo_image'] ?? '';
$appName = $siteName;

if (!empty($config['app_url'])) {
    $parsedUrl = parse_url($config['app_url']);
    $baseUrl = rtrim($parsedUrl['path'] ?? '', '/');
} else {
    $baseUrl = '';
}
$isEmbed = isset($_GET['embed']) && $_GET['embed'] === '1';
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

    $stmt = $pdo->prepare("SELECT title, content FROM rzx_page_contents WHERE page_slug = 'data-policy' AND locale = ? AND is_active = 1");
    $stmt->execute([$currentLocale]);
    $customContent = $stmt->fetch(PDO::FETCH_ASSOC);

    // 현재 로캘에 없으면 ko로 폴백
    if (!$customContent) {
        $stmt->execute(['ko']);
        $customContent = $stmt->fetch(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    // 무시
}

// 커스텀 콘텐츠가 없으면 기본 데이터 사용
$useDefault = empty($customContent['content']);
$complianceData = null;

if ($useDefault) {
    require_once BASE_PATH . '/rzxlib/Core/Data/ComplianceData.php';
    $siteCountry = $siteSettings['site_country'] ?? '';
    $siteCategory = $siteSettings['site_category'] ?? '';
    if ($siteCountry) {
        $complianceData = \RzxLib\Core\Data\ComplianceData::get($siteCountry, $siteCategory);
    }
}

$pageTitle = $siteName . ' - ' . __('customer.data_policy.title');
?>
<!DOCTYPE html>
<html lang="<?= $config['locale'] ?? 'ko' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <style>body { font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; }</style>
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

            <?php if (!$useDefault && $customContent): ?>
            <!-- 커스텀 콘텐츠 -->
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2">
                <?= htmlspecialchars($customContent['title'] ?: __('customer.data_policy.title')) ?>
            </h1>
            <p class="text-gray-500 dark:text-zinc-400 mb-8"><?= __('customer.data_policy.last_updated') ?>: <?= date('Y-m-d') ?></p>
            <div class="prose prose-gray dark:prose-invert max-w-none">
                <?= $customContent['content'] ?>
            </div>

            <?php elseif ($complianceData && !empty($complianceData['retention'])): ?>
            <!-- 기본 데이터 기반 표시 -->
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2"><?= __('customer.data_policy.title') ?></h1>
            <p class="text-gray-500 dark:text-zinc-400 mb-8"><?= __('customer.data_policy.last_updated') ?>: <?= date('Y-m-d') ?></p>

            <div class="prose prose-gray dark:prose-invert max-w-none">
                <p class="text-gray-600 dark:text-zinc-300 leading-relaxed mb-6">
                    <?= __('customer.data_policy.intro', ['name' => htmlspecialchars($appName)]) ?>
                </p>

                <!-- 데이터 보관 기간 -->
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4"><?= __('customer.data_policy.retention_title') ?></h2>
                <div class="overflow-x-auto mb-8">
                    <table class="w-full text-sm border-collapse border border-zinc-200 dark:border-zinc-600">
                        <thead>
                            <tr class="bg-zinc-50 dark:bg-zinc-700">
                                <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-600"><?= __('customer.data_policy.col_type') ?></th>
                                <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-600"><?= __('customer.data_policy.col_retention') ?></th>
                                <th class="px-4 py-3 text-left font-medium text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-600"><?= __('customer.data_policy.col_basis') ?></th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($complianceData['retention'] as $item): ?>
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                                <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white border border-zinc-200 dark:border-zinc-600"><?= __($item['category_key']) ?></td>
                                <td class="px-4 py-3 text-zinc-700 dark:text-zinc-300 border border-zinc-200 dark:border-zinc-600"><?= __($item['retention_key']) ?></td>
                                <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400 text-xs border border-zinc-200 dark:border-zinc-600"><?= __($item['basis_key']) ?></td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>

                <!-- 안내 사항 -->
                <?php if (!empty($complianceData['tips'])): ?>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4"><?= __('customer.data_policy.notice_title') ?></h2>
                <ul class="list-disc list-inside text-gray-600 dark:text-zinc-300 space-y-2 mb-8">
                    <?php foreach ($complianceData['tips'] as $tipKey): ?>
                    <li><?= __($tipKey) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>

                <!-- 관련 법률 -->
                <?php if (!empty($complianceData['laws'])): ?>
                <h2 class="text-xl font-semibold text-gray-900 dark:text-white mb-4"><?= __('customer.data_policy.laws_title') ?></h2>
                <ul class="list-disc list-inside text-gray-600 dark:text-zinc-300 space-y-1 mb-8">
                    <?php foreach ($complianceData['laws'] as $law): ?>
                    <li><?= __($law['name']) ?></li>
                    <?php endforeach; ?>
                </ul>
                <?php endif; ?>

                <!-- 참고 링크 -->
                <?php if (!empty($complianceData['references'])): ?>
                <div class="bg-gray-50 dark:bg-zinc-700/50 rounded-lg p-6">
                    <h3 class="text-lg font-semibold text-gray-900 dark:text-white mb-3"><?= __('customer.data_policy.references') ?></h3>
                    <ul class="space-y-1">
                        <?php foreach ($complianceData['references'] as $ref): ?>
                        <li><a href="<?= htmlspecialchars($ref['url']) ?>" target="_blank" rel="noopener" class="text-blue-600 dark:text-blue-400 hover:underline text-sm"><?= __($ref['title_key']) ?></a></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>

                <!-- 기본 템플릿 안내 -->
                <?php if (!empty($complianceData['is_default'])): ?>
                <div class="mt-6 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                    <p class="text-sm text-amber-700 dark:text-amber-300"><?= __('customer.data_policy.default_notice') ?></p>
                </div>
                <?php endif; ?>
            </div>

            <?php else: ?>
            <!-- 설정 없음 -->
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2"><?= __('customer.data_policy.title') ?></h1>
            <div class="mt-8 p-6 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg text-center">
                <p class="text-zinc-500 dark:text-zinc-400"><?= __('customer.data_policy.not_configured') ?></p>
            </div>
            <?php endif; ?>

            <!-- 문의 안내 -->
            <div class="mt-8 pt-6 border-t dark:border-zinc-700">
                <p class="text-sm text-gray-500 dark:text-zinc-400">
                    <?= __('customer.data_policy.contact', ['name' => htmlspecialchars($appName)]) ?>
                </p>
            </div>
        </div>

        <?php if (!$isEmbed): ?>
        <div class="flex justify-between items-center mt-8">
            <a href="<?= $baseUrl ?>/" class="text-blue-600 dark:text-blue-400 hover:underline">&larr; <?= __('customer.data_policy.back_home') ?></a>
            <a href="<?= $baseUrl ?>/privacy" class="text-blue-600 dark:text-blue-400 hover:underline"><?= __('customer.data_policy.privacy_link') ?> &rarr;</a>
        </div>
        <?php endif; ?>
    </main>

    <?php if (!$isEmbed): ?>
    <footer class="bg-white dark:bg-zinc-800 border-t dark:border-zinc-700 mt-12">
        <div class="max-w-7xl mx-auto px-4 py-6">
            <p class="text-center text-gray-500 dark:text-zinc-400 text-sm">
                &copy; <?= date('Y') ?> <?= htmlspecialchars($appName) ?>. All rights reserved.
            </p>
        </div>
    </footer>

    <script>
        var darkModeBtn = document.getElementById('darkModeBtn');
        if (darkModeBtn) {
            darkModeBtn.addEventListener('click', function() {
                var isDark = document.documentElement.classList.toggle('dark');
                localStorage.setItem('darkMode', isDark);
                console.log('[DataPolicy] Dark mode:', isDark);
            });
        }
    </script>
    <?php endif; ?>
</body>
</html>
