<?php
/**
 * RezlyX Services Page - 서비스 목록
 */

require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
use RzxLib\Core\Auth\Auth;

$pageTitle = ($config['app_name'] ?? 'RezlyX') . ' - ' . __('common.nav.services');
$baseUrl = $config['app_url'] ?? '';
$appName = $config['app_name'] ?? 'RezlyX';

// 로그인 상태 확인
$isLoggedIn = Auth::check();
$currentUser = $isLoggedIn ? Auth::user() : null;

// Load categories and services from database
$categories = [];
$services = [];
$selectedCategory = $_GET['category'] ?? 'all';

try {
    $pdo = new PDO(
        'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx'),
        $_ENV['DB_USERNAME'] ?? 'root',
        $_ENV['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

    // Load categories
    $stmt = $pdo->query("SELECT * FROM {$prefix}categories WHERE is_active = 1 ORDER BY sort_order, name");
    $categories = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Load services with category filter
    $sql = "SELECT s.*, c.name as category_name, c.slug as category_slug
            FROM {$prefix}services s
            LEFT JOIN {$prefix}categories c ON s.category_id = c.id
            WHERE s.is_active = 1";

    if ($selectedCategory !== 'all' && is_numeric($selectedCategory)) {
        $sql .= " AND s.category_id = :category_id";
        $stmt = $pdo->prepare($sql . " ORDER BY s.sort_order, s.name");
        $stmt->execute(['category_id' => $selectedCategory]);
    } else {
        $stmt = $pdo->query($sql . " ORDER BY s.sort_order, s.name");
    }
    $services = $stmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    if ($config['debug'] ?? false) {
        error_log('Services page DB error: ' . $e->getMessage());
    }
}

// 통화·가격 표시 설정 (서비스 설정 > 기본설정)
$serviceCurrency = $siteSettings['service_currency'] ?? 'KRW';
$priceDisplay = $siteSettings['service_price_display'] ?? 'show';
$_currencySymbols = ['KRW' => '₩', 'USD' => '$', 'JPY' => '¥', 'EUR' => '€', 'CNY' => '¥'];
$currencySymbol = $_currencySymbols[$serviceCurrency] ?? $serviceCurrency;

// 헤더 포함
include BASE_PATH . '/resources/views/partials/header.php';
?>

    <!-- Main Content -->
    <main class="max-w-6xl mx-auto px-4 py-8">
        <!-- Page Title -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2"><?= __('services.title') ?></h1>
            <p class="text-gray-600 dark:text-zinc-400"><?= __('services.description') ?></p>
        </div>

        <!-- Category Filter -->
        <?php if (!empty($categories)): ?>
        <div class="flex flex-wrap justify-center gap-2 mb-8">
            <a href="<?= $baseUrl ?>/services"
               class="px-4 py-2 rounded-full text-sm font-medium transition <?= $selectedCategory === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-zinc-700 text-gray-700 dark:text-zinc-300 hover:bg-gray-200 dark:hover:bg-zinc-600' ?>">
                <?= __('services.all_categories') ?>
            </a>
            <?php foreach ($categories as $category): ?>
            <a href="<?= $baseUrl ?>/services?category=<?= $category['id'] ?>"
               class="px-4 py-2 rounded-full text-sm font-medium transition <?= $selectedCategory == $category['id'] ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-zinc-700 text-gray-700 dark:text-zinc-300 hover:bg-gray-200 dark:hover:bg-zinc-600' ?>">
                <?= htmlspecialchars($category['name']) ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Services Grid -->
        <?php if (empty($services)): ?>
        <div class="text-center py-16">
            <svg class="w-20 h-20 text-gray-300 dark:text-zinc-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 11H5m14 0a2 2 0 012 2v6a2 2 0 01-2 2H5a2 2 0 01-2-2v-6a2 2 0 012-2m14 0V9a2 2 0 00-2-2M5 11V9a2 2 0 012-2m0 0V5a2 2 0 012-2h6a2 2 0 012 2v2M7 7h10"/>
            </svg>
            <p class="text-xl text-gray-500 dark:text-zinc-400"><?= __('booking.no_services') ?></p>
            <p class="text-gray-400 dark:text-zinc-500 mt-2"><?= __('booking.contact_admin') ?></p>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($services as $service): ?>
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow group">
                <!-- Service Image -->
                <?php if (!empty($service['image'])): ?>
                <div class="aspect-video overflow-hidden">
                    <img src="<?= $baseUrl ?>/uploads/services/<?= htmlspecialchars($service['image']) ?>"
                         alt="<?= htmlspecialchars($service['name']) ?>"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                </div>
                <?php else: ?>
                <div class="aspect-video bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                    <svg class="w-16 h-16 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                    </svg>
                </div>
                <?php endif; ?>

                <!-- Service Info -->
                <div class="p-6">
                    <!-- Category Badge -->
                    <?php if (!empty($service['category_name'])): ?>
                    <span class="inline-block px-3 py-1 text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full mb-3">
                        <?= htmlspecialchars($service['category_name']) ?>
                    </span>
                    <?php endif; ?>

                    <!-- Title & Description -->
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                        <?= htmlspecialchars($service['name']) ?>
                    </h3>
                    <p class="text-gray-600 dark:text-zinc-400 text-sm mb-4 line-clamp-2">
                        <?= htmlspecialchars($service['description'] ?? '') ?>
                    </p>

                    <!-- Price & Duration -->
                    <div class="flex items-center justify-between mb-4">
                        <div class="flex items-center gap-4">
                            <div class="flex items-center text-gray-500 dark:text-zinc-400">
                                <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                                </svg>
                                <span class="text-sm"><?= $service['duration'] ?? 60 ?><?= __('common.minutes') ?></span>
                            </div>
                        </div>
                        <div class="text-right">
                            <?php if ($priceDisplay === 'show'): ?>
                            <span class="text-2xl font-bold text-blue-600 dark:text-blue-400">
                                <?= $currencySymbol ?><?= number_format($service['price']) ?>
                            </span>
                            <?php elseif ($priceDisplay === 'contact'): ?>
                            <span class="text-sm font-medium text-gray-500 dark:text-zinc-400"><?= __('admin.services.settings.general.price_contact') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>

                    <!-- Action Buttons -->
                    <div class="flex gap-2">
                        <a href="<?= $baseUrl ?>/services/<?= $service['id'] ?>"
                           class="flex-1 px-4 py-2.5 border border-gray-300 dark:border-zinc-600 text-gray-700 dark:text-zinc-300 font-medium rounded-lg hover:bg-gray-50 dark:hover:bg-zinc-700 transition text-center">
                            <?= __('services.view_detail') ?>
                        </a>
                        <a href="<?= $baseUrl ?>/booking?service=<?= $service['id'] ?>"
                           class="flex-1 px-4 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition text-center">
                            <?= __('services.book_now') ?>
                        </a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Call to Action -->
        <div class="mt-16 text-center bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl p-8 md:p-12">
            <h2 class="text-2xl md:text-3xl font-bold text-white mb-4"><?= __('services.cta_title') ?></h2>
            <p class="text-blue-100 mb-6 max-w-2xl mx-auto"><?= __('services.cta_description') ?></p>
            <a href="<?= $baseUrl ?>/booking"
               class="inline-flex items-center px-8 py-3 bg-white text-blue-600 font-semibold rounded-lg hover:bg-gray-100 transition shadow-lg">
                <?= __('common.nav.booking') ?>
                <svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 8l4 4m0 0l-4 4m4-4H3"/>
                </svg>
            </a>
        </div>
    </main>

    <script>
        console.log('[Services] 서비스 목록 페이지 로드 완료');
    </script>

<?php
// 푸터 포함
include BASE_PATH . '/resources/views/partials/footer.php';
?>
