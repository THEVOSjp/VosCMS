<?php
/**
 * RezlyX Service Detail Page - 서비스 상세
 */

require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
use RzxLib\Core\Auth\Auth;

$baseUrl = $config['app_url'] ?? '';
$appName = $config['app_name'] ?? 'RezlyX';

// 로그인 상태 확인
$isLoggedIn = Auth::check();
$currentUser = $isLoggedIn ? Auth::user() : null;

// Get service ID from URL
$serviceId = $routeParams['id'] ?? $_GET['id'] ?? null;
$service = null;
$relatedServices = [];

if (!$serviceId) {
    header('Location: ' . $baseUrl . '/services');
    exit;
}

try {
    $pdo = new PDO(
        'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx'),
        $_ENV['DB_USERNAME'] ?? 'root',
        $_ENV['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

    // Load service
    $stmt = $pdo->prepare("
        SELECT s.*, c.name as category_name, c.slug as category_slug
        FROM {$prefix}services s
        LEFT JOIN {$prefix}categories c ON s.category_id = c.id
        WHERE s.id = :id AND s.is_active = 1
    ");
    $stmt->execute(['id' => $serviceId]);
    $service = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$service) {
        header('Location: ' . $baseUrl . '/services');
        exit;
    }

    // Load related services (same category)
    if ($service['category_id']) {
        $stmt = $pdo->prepare("
            SELECT * FROM {$prefix}services
            WHERE category_id = :category_id AND id != :id AND is_active = 1
            ORDER BY sort_order, name LIMIT 3
        ");
        $stmt->execute(['category_id' => $service['category_id'], 'id' => $serviceId]);
        $relatedServices = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (PDOException $e) {
    if ($config['debug'] ?? false) {
        error_log('Service detail DB error: ' . $e->getMessage());
    }
    header('Location: ' . $baseUrl . '/services');
    exit;
}

$pageTitle = ($config['app_name'] ?? 'RezlyX') . ' - ' . htmlspecialchars($service['name']);

// 통화·가격 표시 설정 (서비스 설정 > 기본설정)
$serviceCurrency = $siteSettings['service_currency'] ?? 'KRW';
$priceDisplay = $siteSettings['service_price_display'] ?? 'show';
$_currencySymbols = ['KRW' => '₩', 'USD' => '$', 'JPY' => '¥', 'EUR' => '€', 'CNY' => '¥'];
$currencySymbol = $_currencySymbols[$serviceCurrency] ?? $serviceCurrency;

// 헤더 포함
include BASE_PATH . '/resources/views/partials/header.php';
?>

    <!-- Main Content -->
    <main class="max-w-4xl mx-auto px-4 py-8">
        <!-- Breadcrumb -->
        <nav class="flex items-center text-sm text-gray-500 dark:text-zinc-400 mb-6">
            <a href="<?= $baseUrl ?>/" class="hover:text-blue-600 dark:hover:text-blue-400"><?= __('common.nav.home') ?></a>
            <svg class="w-4 h-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <a href="<?= $baseUrl ?>/services" class="hover:text-blue-600 dark:hover:text-blue-400"><?= __('common.nav.services') ?></a>
            <svg class="w-4 h-4 mx-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
            </svg>
            <span class="text-gray-900 dark:text-white"><?= htmlspecialchars($service['name']) ?></span>
        </nav>

        <!-- Service Card -->
        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg overflow-hidden">
            <!-- Service Image -->
            <?php if (!empty($service['image'])): ?>
            <div class="aspect-video md:aspect-[21/9] overflow-hidden">
                <img src="<?= $baseUrl ?>/uploads/services/<?= htmlspecialchars($service['image']) ?>"
                     alt="<?= htmlspecialchars($service['name']) ?>"
                     class="w-full h-full object-cover">
            </div>
            <?php else: ?>
            <div class="aspect-video md:aspect-[21/9] bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                <svg class="w-24 h-24 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                </svg>
            </div>
            <?php endif; ?>

            <!-- Service Info -->
            <div class="p-6 md:p-8">
                <!-- Category Badge -->
                <?php if (!empty($service['category_name'])): ?>
                <a href="<?= $baseUrl ?>/services?category=<?= $service['category_id'] ?>"
                   class="inline-block px-3 py-1 text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full mb-4 hover:bg-blue-200 dark:hover:bg-blue-900/50 transition">
                    <?= htmlspecialchars($service['category_name']) ?>
                </a>
                <?php endif; ?>

                <!-- Title -->
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">
                    <?= htmlspecialchars($service['name']) ?>
                </h1>

                <!-- Price & Duration -->
                <div class="flex flex-wrap items-center gap-6 mb-6 pb-6 border-b border-gray-200 dark:border-zinc-700">
                    <div>
                        <span class="text-sm text-gray-500 dark:text-zinc-400"><?= __('booking.service.price') ?></span>
                        <div class="flex items-baseline gap-1">
                            <?php if ($priceDisplay === 'show'): ?>
                            <span class="text-3xl font-bold text-blue-600 dark:text-blue-400">
                                <?= $currencySymbol ?><?= number_format($service['price']) ?>
                            </span>
                            <?php elseif ($priceDisplay === 'contact'): ?>
                            <span class="text-lg font-medium text-gray-500 dark:text-zinc-400"><?= __('services.settings.general.price_contact') ?></span>
                            <?php else: ?>
                            <span class="text-gray-400">-</span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="h-12 w-px bg-gray-200 dark:bg-zinc-700"></div>
                    <div>
                        <span class="text-sm text-gray-500 dark:text-zinc-400"><?= __('booking.service.duration') ?></span>
                        <div class="flex items-center gap-2">
                            <svg class="w-5 h-5 text-gray-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>
                            </svg>
                            <span class="text-xl font-semibold text-gray-900 dark:text-white">
                                <?= $service['duration'] ?? 60 ?><?= __('common.minutes') ?>
                            </span>
                        </div>
                    </div>
                </div>

                <!-- Description -->
                <div class="mb-8">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white mb-3"><?= __('services.about_service') ?></h2>
                    <div class="prose dark:prose-invert max-w-none text-gray-600 dark:text-zinc-400">
                        <?php if (!empty($service['description'])): ?>
                            <?= nl2br(htmlspecialchars($service['description'])) ?>
                        <?php else: ?>
                            <p class="text-gray-400 dark:text-zinc-500 italic"><?= __('services.no_description') ?></p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="flex flex-col sm:flex-row gap-4">
                    <a href="<?= $baseUrl ?>/booking?service=<?= $service['id'] ?>"
                       class="flex-1 inline-flex items-center justify-center px-8 py-4 bg-blue-600 text-white font-semibold rounded-xl hover:bg-blue-700 transition shadow-lg shadow-blue-500/30">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <?= __('services.book_now') ?>
                    </a>
                    <a href="<?= $baseUrl ?>/services"
                       class="flex-1 sm:flex-none inline-flex items-center justify-center px-8 py-4 border border-gray-300 dark:border-zinc-600 text-gray-700 dark:text-zinc-300 font-semibold rounded-xl hover:bg-gray-50 dark:hover:bg-zinc-700 transition">
                        <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/>
                        </svg>
                        <?= __('services.back_to_list') ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Related Services -->
        <?php if (!empty($relatedServices)): ?>
        <div class="mt-12">
            <h2 class="text-2xl font-bold text-gray-900 dark:text-white mb-6"><?= __('services.related_services') ?></h2>
            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <?php foreach ($relatedServices as $related): ?>
                <a href="<?= $baseUrl ?>/services/<?= $related['id'] ?>"
                   class="bg-white dark:bg-zinc-800 rounded-xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow group">
                    <?php if (!empty($related['image'])): ?>
                    <div class="aspect-video overflow-hidden">
                        <img src="<?= $baseUrl ?>/uploads/services/<?= htmlspecialchars($related['image']) ?>"
                             alt="<?= htmlspecialchars($related['name']) ?>"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    </div>
                    <?php else: ?>
                    <div class="aspect-video bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                        <svg class="w-12 h-12 text-white/50" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>
                        </svg>
                    </div>
                    <?php endif; ?>
                    <div class="p-4">
                        <h3 class="font-semibold text-gray-900 dark:text-white mb-1"><?= htmlspecialchars($related['name']) ?></h3>
                        <div class="flex items-center justify-between">
                            <span class="text-sm text-gray-500 dark:text-zinc-400"><?= $related['duration'] ?? 60 ?><?= __('common.minutes') ?></span>
                            <?php if ($priceDisplay === 'show'): ?>
                            <span class="font-bold text-blue-600 dark:text-blue-400"><?= $currencySymbol ?><?= number_format($related['price']) ?></span>
                            <?php elseif ($priceDisplay === 'contact'): ?>
                            <span class="text-sm text-gray-500 dark:text-zinc-400"><?= __('services.settings.general.price_contact') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                </a>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </main>

    <script>
        console.log('[Services] 서비스 상세 페이지 로드 완료 - ID:', <?= $serviceId ?>);
    </script>

<?php
// 푸터 포함
include BASE_PATH . '/resources/views/partials/footer.php';
?>
