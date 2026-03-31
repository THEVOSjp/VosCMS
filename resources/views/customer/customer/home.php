<?php
/**
 * RezlyX Customer Home Page (다국어 지원)
 */

// 헬퍼 함수 로드 (index.php에서 $currentLocale, $baseUrl 등 이미 설정됨)
$helpersPath = BASE_PATH . '/rzxlib/Core/Helpers/functions.php';
if (file_exists($helpersPath) && !function_exists('get_site_tagline')) {
    require_once $helpersPath;
}

// 사이트 타이틀 (site_tagline이 있으면 사용)
$_siteName = function_exists('get_site_name') ? get_site_name() : ($siteSettings['site_name'] ?? ($config['app_name'] ?? 'RezlyX'));
$siteTagline = function_exists('get_site_tagline') ? get_site_tagline() : '';
if (!empty($siteTagline)) {
    $pageTitle = $_siteName . ' - ' . $siteTagline;
} else {
    $pageTitle = $_siteName . ' - ' . __('common.nav.home');
}
$seoContext = ['type' => 'main'];

// === 위젯 기반 동적 렌더링 (WidgetRenderer 공통 모듈 사용) ===
    require_once BASE_PATH . '/rzxlib/Core/Modules/WidgetRenderer.php';
    $widgetRenderer = null;
    try {
        $homePdo = new PDO(
            'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx'),
            $_ENV['DB_USERNAME'] ?? 'root',
            $_ENV['DB_PASSWORD'] ?? '',
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $widgetRenderer = new \RzxLib\Core\Modules\WidgetRenderer($homePdo, 'home', $currentLocale, $baseUrl);
    } catch (\PDOException $e) {
        // 폴백: 기본 콘텐츠
    }
    ?>

    <?php if ($widgetRenderer && $widgetRenderer->hasWidgets()): ?>
    <!-- 위젯 기반 홈 페이지 -->
    <?= $widgetRenderer->renderAll() ?>

    <?php else: ?>
    <!-- 기본 홈 콘텐츠 (위젯 미배치 시 폴백) -->
    <section class="relative bg-gradient-to-br from-blue-600 to-blue-800 dark:from-blue-800 dark:to-zinc-950 text-white">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-24">
            <div class="text-center">
                <h1 class="text-4xl md:text-5xl font-bold mb-6">
                    <?= __('home.hero.title_1') ?><br>
                    <span class="text-blue-200"><?= __('home.hero.title_2') ?></span>
                </h1>
                <p class="text-xl text-blue-100 dark:text-blue-200 mb-8 max-w-2xl mx-auto"><?= __('home.hero.subtitle') ?></p>
                <a href="<?= $baseUrl ?>/booking" class="inline-flex items-center px-8 py-4 bg-white text-blue-600 font-semibold rounded-xl hover:bg-blue-50 transition shadow-lg">
                    <?= __('home.hero.cta_booking') ?>
                    <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </a>
            </div>
        </div>
        <div class="absolute bottom-0 left-0 right-0 h-16 bg-gradient-to-t from-gray-50 dark:from-zinc-900"></div>
    </section>

    <!-- Development Info -->
    <?php if ($config['debug'] ?? false): ?>
    <section class="bg-yellow-50 dark:bg-yellow-900/20 border-b border-yellow-200 dark:border-yellow-800">
        <div class="max-w-7xl mx-auto px-4 py-4">
            <div class="flex items-center justify-center space-x-4 text-sm">
                <span class="px-2 py-1 bg-yellow-200 dark:bg-yellow-800 text-yellow-800 dark:text-yellow-200 rounded font-medium">DEV MODE</span>
                <span class="text-yellow-700 dark:text-yellow-400">PHP <?= PHP_VERSION ?></span>
                <span class="text-yellow-700 dark:text-yellow-500">|</span>
                <span class="text-yellow-700 dark:text-yellow-400">Locale: <?= $currentLocale ?></span>
                <span class="text-yellow-700 dark:text-yellow-500">|</span>
                <a href="<?= $baseUrl ?>/<?= $config['admin_path'] ?>" class="text-yellow-800 dark:text-yellow-300 hover:underline font-medium"><?= __('common.nav.admin') ?> →</a>
            </div>
        </div>
    </section>
    <?php endif; ?>

    <section class="py-20">
        <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">
            <div class="text-center mb-16">
                <h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4"><?= __('home.features.title') ?></h2>
                <p class="text-gray-600 dark:text-zinc-400"><?= __('home.features.subtitle') ?></p>
            </div>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-8">
                <?php
                $defaultFeatures = [
                    ['icon' => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z', 'color' => 'blue', 'key' => 'mobile'],
                    ['icon' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z', 'color' => 'green', 'key' => 'realtime'],
                    ['icon' => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z', 'color' => 'purple', 'key' => 'easy_payment'],
                ];
                foreach ($defaultFeatures as $df): ?>
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-8 text-center hover:shadow-lg transition">
                    <div class="w-16 h-16 bg-<?= $df['color'] ?>-100 dark:bg-<?= $df['color'] ?>-900/50 rounded-xl flex items-center justify-center mx-auto mb-6">
                        <svg class="w-8 h-8 text-<?= $df['color'] ?>-600 dark:text-<?= $df['color'] ?>-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $df['icon'] ?>"/></svg>
                    </div>
                    <h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3"><?= __('home.features.' . $df['key'] . '.title') ?></h3>
                    <p class="text-gray-600 dark:text-zinc-400"><?= __('home.features.' . $df['key'] . '.desc') ?></p>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </section>
    <?php endif; ?>

<?php
// 디버그 정보 (footerExtra에 추가)
if ($config['debug'] ?? false) {
    $footerExtra = ($footerExtra ?? '') . '<div class="fixed bottom-4 right-4 bg-gray-900 dark:bg-zinc-700 text-white text-xs p-3 rounded-lg shadow-lg"><p>' . number_format((microtime(true) - REZLYX_START) * 1000, 2) . 'ms</p></div>';
}

?>
