<?php
/**
 * RezlyX Staff Detail Page - 스태프 상세 + 스케줄 + 서비스 메뉴
 */

require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
use RzxLib\Core\Auth\Auth;

$baseUrl = $config['app_url'] ?? '';
$staffId = (int)($routeParams['id'] ?? 0);
$currentLocale = $config['locale'] ?? 'ko';
$isLoggedIn = Auth::check();

if (!$staffId) {
    header('Location: ' . $baseUrl . '/staff');
    exit;
}

// 다국어 헬퍼
function getLocalizedVal($name, $nameI18n, $locale) {
    if (!empty($nameI18n)) {
        $i18n = is_string($nameI18n) ? json_decode($nameI18n, true) : $nameI18n;
        if (is_array($i18n) && !empty($i18n[$locale])) return $i18n[$locale];
    }
    return $name;
}

function getSubNameVal($nameI18n, $locale) {
    if (empty($nameI18n)) return '';
    $i18n = is_string($nameI18n) ? json_decode($nameI18n, true) : $nameI18n;
    if (!is_array($i18n)) return '';
    if ($locale === 'ja' && !empty($i18n['ko'])) return $i18n['ko'];
    if (!empty($i18n['en'])) return $i18n['en'];
    if (!empty($i18n['ja'])) return $i18n['ja'];
    return '';
}

$staff = null;
$staffServices = [];
$schedules = [];
$dayNames = [];

try {
    $pdo = new PDO(
        'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx') . ';charset=utf8mb4',
        $_ENV['DB_USERNAME'] ?? 'root',
        $_ENV['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

    // 스태프 정보
    $stmt = $pdo->prepare("SELECT s.*, p.name as position_name, p.name_i18n as position_name_i18n
        FROM {$prefix}staff s
        LEFT JOIN {$prefix}staff_positions p ON s.position_id = p.id
        WHERE s.id = ? AND s.is_active = 1");
    $stmt->execute([$staffId]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$staff) {
        header('Location: ' . $baseUrl . '/staff');
        exit;
    }

    // 담당 서비스 (가격, 소요시간 포함)
    $stmt = $pdo->prepare("SELECT sv.id, sv.name, sv.slug, sv.description, sv.price, sv.duration, sv.image,
            sc.name as category_name
        FROM {$prefix}staff_services ss
        JOIN {$prefix}services sv ON ss.service_id = sv.id
        LEFT JOIN {$prefix}service_categories sc ON sv.category_id = sc.id
        WHERE ss.staff_id = ? AND sv.is_active = 1
        ORDER BY sv.sort_order, sv.name");
    $stmt->execute([$staffId]);
    $staffServices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 주간 스케줄
    $stmt = $pdo->prepare("SELECT * FROM {$prefix}staff_schedules WHERE staff_id = ? ORDER BY day_of_week");
    $stmt->execute([$staffId]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $schedules[$row['day_of_week']] = $row;
    }

} catch (PDOException $e) {
    if ($config['debug'] ?? false) {
        error_log('Staff detail DB error: ' . $e->getMessage());
    }
    header('Location: ' . $baseUrl . '/staff');
    exit;
}

$staffName = getLocalizedVal($staff['name'], $staff['name_i18n'], $currentLocale);
$subName = getSubNameVal($staff['name_i18n'], $currentLocale);
$positionLabel = getLocalizedVal($staff['position_name'] ?? '', $staff['position_name_i18n'] ?? null, $currentLocale);
$bio = getLocalizedVal($staff['bio'] ?? '', $staff['bio_i18n'] ?? null, $currentLocale);
$designationFee = (float)($staff['designation_fee'] ?? 0);
$avatarUrl = $staff['avatar'] ?? '';

$pageTitle = $staffName . ' - ' . ($config['app_name'] ?? 'RezlyX');

// 요일명
$dayLabels = [
    'ko' => ['일', '월', '화', '수', '목', '금', '토'],
    'ja' => ['日', '月', '火', '水', '木', '金', '土'],
    'en' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
];
$days = $dayLabels[$currentLocale] ?? $dayLabels['en'];

include BASE_PATH . '/resources/views/partials/header.php';
?>

    <main class="max-w-6xl mx-auto px-4 py-8">
        <!-- Breadcrumb -->
        <nav class="text-sm text-gray-500 dark:text-zinc-400 mb-6">
            <a href="<?= $baseUrl ?>/staff" class="hover:text-blue-600 dark:hover:text-blue-400"><?= __('staff_page.back_to_list') ?></a>
            <span class="mx-2">&gt;</span>
            <span class="text-gray-900 dark:text-white"><?= htmlspecialchars($staffName) ?></span>
        </nav>

        <!-- Staff Profile Header -->
        <div class="flex flex-col md:flex-row gap-8 mb-10">
            <!-- Avatar -->
            <div class="w-full md:w-64 flex-shrink-0">
                <div class="aspect-[3/4] overflow-hidden bg-gray-100 dark:bg-zinc-800 rounded-lg">
                    <?php if (!empty($avatarUrl)): ?>
                    <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="<?= htmlspecialchars($staffName) ?>"
                         class="w-full h-full object-cover">
                    <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-200 to-gray-300 dark:from-zinc-700 dark:to-zinc-800">
                        <span class="text-6xl font-bold text-gray-400 dark:text-zinc-500"><?= mb_substr($staffName, 0, 1) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Info -->
            <div class="flex-1">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($staffName) ?></h1>
                <?php if (!empty($subName)): ?>
                <p class="text-gray-500 dark:text-zinc-400 text-sm mt-0.5"><?= htmlspecialchars($subName) ?></p>
                <?php endif; ?>

                <?php if (!empty($positionLabel)): ?>
                <span class="inline-block mt-2 px-3 py-1 text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full">
                    <?= htmlspecialchars($positionLabel) ?>
                </span>
                <?php endif; ?>

                <?php if ($designationFee > 0): ?>
                <p class="mt-3 text-red-600 dark:text-red-400 font-semibold">
                    <?= __('staff_page.designation_fee') ?> &yen;<?= number_format($designationFee) ?>
                </p>
                <?php endif; ?>

                <?php if (!empty($bio)): ?>
                <div class="mt-4 text-gray-700 dark:text-zinc-300 text-sm leading-relaxed">
                    <?= nl2br(htmlspecialchars($bio)) ?>
                </div>
                <?php endif; ?>

                <!-- Weekly Schedule -->
                <?php if (!empty($schedules)): ?>
                <div class="mt-6">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white mb-2"><?= __('staff_page.working_hours') ?></h3>
                    <div class="flex flex-wrap gap-1.5">
                        <?php for ($d = 0; $d < 7; $d++):
                            $sch = $schedules[$d] ?? null;
                            $isWorking = $sch && $sch['is_working'];
                            $dayColor = $d === 0 ? 'text-red-500' : ($d === 6 ? 'text-blue-500' : 'text-gray-700 dark:text-zinc-300');
                        ?>
                        <div class="text-center px-2 py-1.5 rounded <?= $isWorking ? 'bg-gray-50 dark:bg-zinc-800' : 'bg-gray-100 dark:bg-zinc-900 opacity-50' ?>">
                            <div class="text-xs font-medium <?= $dayColor ?>"><?= $days[$d] ?></div>
                            <?php if ($isWorking): ?>
                            <div class="text-[10px] text-gray-500 dark:text-zinc-400 mt-0.5">
                                <?= substr($sch['start_time'], 0, 5) ?>-<?= substr($sch['end_time'], 0, 5) ?>
                            </div>
                            <?php else: ?>
                            <div class="text-[10px] text-gray-400 dark:text-zinc-500 mt-0.5"><?= __('staff_page.day_off') ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Action Button -->
                <div class="mt-6">
                    <a href="<?= $baseUrl ?>/booking?staff=<?= $staff['id'] ?>"
                       class="inline-flex items-center px-6 py-2.5 bg-gray-900 dark:bg-white text-white dark:text-gray-900 font-medium rounded-lg hover:bg-gray-700 dark:hover:bg-gray-200 transition">
                        <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
                        <?= __('staff_page.book_with') ?>
                    </a>
                </div>
            </div>
        </div>

        <!-- Available Services -->
        <?php if (!empty($staffServices)): ?>
        <div class="border-t border-gray-200 dark:border-zinc-700 pt-8">
            <h2 class="text-xl font-bold text-gray-900 dark:text-white mb-6"><?= __('staff_page.available_services') ?></h2>

            <div class="space-y-4">
                <?php foreach ($staffServices as $svc):
                    $svcName = htmlspecialchars($svc['name']);
                    $svcDesc = htmlspecialchars($svc['description'] ?? '');
                    $svcPrice = (float)$svc['price'];
                    $svcDuration = (int)$svc['duration'];
                    $svcImage = $svc['image'] ?? '';
                    $svcCategory = $svc['category_name'] ?? '';
                ?>
                <div class="flex gap-4 p-4 bg-white dark:bg-zinc-800 border border-gray-200 dark:border-zinc-700 rounded-lg hover:shadow-md transition-shadow">
                    <!-- Service Image -->
                    <?php if (!empty($svcImage)): ?>
                    <div class="w-24 h-24 flex-shrink-0 rounded-lg overflow-hidden bg-gray-100 dark:bg-zinc-700">
                        <img src="<?= htmlspecialchars($svcImage) ?>" alt="<?= $svcName ?>" class="w-full h-full object-cover">
                    </div>
                    <?php endif; ?>

                    <!-- Service Info -->
                    <div class="flex-1 min-w-0">
                        <?php if (!empty($svcCategory)): ?>
                        <span class="text-xs text-blue-600 dark:text-blue-400 font-medium"><?= htmlspecialchars($svcCategory) ?></span>
                        <?php endif; ?>
                        <h3 class="font-semibold text-gray-900 dark:text-white"><?= $svcName ?></h3>
                        <p class="text-red-600 dark:text-red-400 font-bold text-sm">&yen;<?= number_format($svcPrice) ?></p>
                        <div class="flex items-center gap-3 text-xs text-gray-500 dark:text-zinc-400 mt-1">
                            <?php if ($svcDuration > 0): ?>
                            <span><?= __('staff_page.duration') ?>: <?= $svcDuration ?><?= __('staff_page.minutes') ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($svcDesc)): ?>
                        <p class="text-sm text-gray-600 dark:text-zinc-400 mt-2 line-clamp-2"><?= $svcDesc ?></p>
                        <?php endif; ?>
                    </div>

                    <!-- Select Button -->
                    <div class="flex-shrink-0 flex items-center">
                        <a href="<?= $baseUrl ?>/booking?staff=<?= $staff['id'] ?>&service=<?= $svc['id'] ?>"
                           class="inline-flex items-center px-4 py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-sm font-medium rounded-lg hover:bg-gray-700 dark:hover:bg-gray-200 transition">
                            <?= __('staff_page.select_service') ?>
                            <svg class="w-4 h-4 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/>
                            </svg>
                        </a>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Back to list -->
        <div class="mt-10 text-center">
            <a href="<?= $baseUrl ?>/staff" class="text-gray-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 text-sm">
                &larr; <?= __('staff_page.back_to_list') ?>
            </a>
        </div>
    </main>

    <script>
        console.log('[Staff Detail] 스태프 상세 페이지 로드: ID=<?= $staffId ?>');
    </script>

<?php
include BASE_PATH . '/resources/views/partials/footer.php';
?>
