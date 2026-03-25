<?php
/**
 * RezlyX - 데이터 관리 정책 (고객용)
 * 운영 국가/업종에 따른 데이터 보관 및 개인정보 관리 안내
 */
$isEmbed = isset($_GET['embed']) && $_GET['embed'] === '1';
if ($isEmbed) $__layout = false; // embed 모드: 레이아웃 미적용
$currentLocale = current_locale();
$appName = function_exists('get_site_name') ? get_site_name() : ($siteSettings['site_name'] ?? ($config['app_name'] ?? 'RezlyX'));

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

$pageTitle = $appName . ' - ' . __('customer.data_policy.title');

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
<body class="bg-gray-50 dark:bg-zinc-900">
    <main class="p-6">
<?php } else {
    // 일반 모드: base-header/footer 사용
} ?>

    <div class="<?= $isEmbed ? '' : 'max-w-4xl mx-auto px-4 py-12' ?>">
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

    </div>

<?php if ($isEmbed) { ?>
    </main>
</body>
</html>
<?php } else {
} ?>
