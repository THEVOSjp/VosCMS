<?php
/**
 * RezlyX Staff Page - 스태프 소개
 */

require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
use RzxLib\Core\Auth\Auth;

$pageTitle = ($config['app_name'] ?? 'RezlyX') . ' - ' . __('staff_page.title');
$baseUrl = $config['app_url'] ?? '';

// 로그인 상태 확인
$isLoggedIn = Auth::check();
$currentUser = $isLoggedIn ? Auth::user() : null;

// 스태프 데이터 로드
$positions = [];
$staffList = [];
$selectedPosition = $_GET['position'] ?? 'all';

try {
    $pdo = new PDO(
        'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx') . ';charset=utf8mb4',
        $_ENV['DB_USERNAME'] ?? 'root',
        $_ENV['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

    // 포지션 목록
    $stmt = $pdo->query("SELECT * FROM {$prefix}staff_positions WHERE is_active = 1 ORDER BY sort_order, name");
    $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 스태프 목록 (활성만)
    $sql = "SELECT s.*, p.name as position_name, p.name_i18n as position_name_i18n
            FROM {$prefix}staff s
            LEFT JOIN {$prefix}staff_positions p ON s.position_id = p.id
            WHERE s.is_active = 1";

    if ($selectedPosition !== 'all' && is_numeric($selectedPosition)) {
        $sql .= " AND s.position_id = :position_id";
        $stmt = $pdo->prepare($sql . " ORDER BY s.sort_order, s.name");
        $stmt->execute(['position_id' => $selectedPosition]);
    } else {
        $stmt = $pdo->query($sql . " ORDER BY s.sort_order, s.name");
    }
    $staffList = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 담당 서비스 로드
    $staffIds = array_column($staffList, 'id');
    $staffServices = [];
    if (!empty($staffIds)) {
        $placeholders = implode(',', array_fill(0, count($staffIds), '?'));
        $stmt = $pdo->prepare("SELECT ss.staff_id, sv.name as service_name
            FROM {$prefix}staff_services ss
            JOIN {$prefix}services sv ON ss.service_id = sv.id
            WHERE ss.staff_id IN ({$placeholders}) AND sv.is_active = 1
            ORDER BY sv.sort_order, sv.name");
        $stmt->execute($staffIds);
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $staffServices[$row['staff_id']][] = $row['service_name'];
        }
    }
} catch (PDOException $e) {
    if ($config['debug'] ?? false) {
        error_log('Staff page DB error: ' . $e->getMessage());
    }
}

// 다국어 포지션명 해석
$currentLocale = $config['locale'] ?? 'ko';
function getLocalizedName($name, $nameI18n, $locale) {
    if (!empty($nameI18n)) {
        $i18n = is_string($nameI18n) ? json_decode($nameI18n, true) : $nameI18n;
        if (is_array($i18n) && !empty($i18n[$locale])) {
            return $i18n[$locale];
        }
    }
    return $name;
}

// 헤더 포함
include BASE_PATH . '/resources/views/partials/header.php';
?>

    <!-- Main Content -->
    <main class="max-w-6xl mx-auto px-4 py-8">
        <!-- Page Title -->
        <div class="text-center mb-8">
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2"><?= __('staff_page.title') ?></h1>
            <p class="text-gray-600 dark:text-zinc-400"><?= __('staff_page.description') ?></p>
        </div>

        <!-- Position Filter -->
        <?php if (!empty($positions)): ?>
        <div class="flex flex-wrap justify-center gap-2 mb-8">
            <a href="<?= $baseUrl ?>/staff"
               class="px-4 py-2 rounded-full text-sm font-medium transition <?= $selectedPosition === 'all' ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-zinc-700 text-gray-700 dark:text-zinc-300 hover:bg-gray-200 dark:hover:bg-zinc-600' ?>">
                <?= __('staff_page.all_staff') ?>
            </a>
            <?php foreach ($positions as $pos): ?>
            <a href="<?= $baseUrl ?>/staff?position=<?= $pos['id'] ?>"
               class="px-4 py-2 rounded-full text-sm font-medium transition <?= $selectedPosition == $pos['id'] ? 'bg-blue-600 text-white' : 'bg-gray-100 dark:bg-zinc-700 text-gray-700 dark:text-zinc-300 hover:bg-gray-200 dark:hover:bg-zinc-600' ?>">
                <?= htmlspecialchars(getLocalizedName($pos['name'], $pos['name_i18n'], $currentLocale)) ?>
            </a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- Staff Grid -->
        <?php if (empty($staffList)): ?>
        <div class="text-center py-16">
            <svg class="w-20 h-20 text-gray-300 dark:text-zinc-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>
            </svg>
            <p class="text-xl text-gray-500 dark:text-zinc-400"><?= __('staff_page.no_staff') ?></p>
        </div>
        <?php else: ?>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
            <?php foreach ($staffList as $staff):
                $positionLabel = getLocalizedName(
                    $staff['position_name'] ?? '',
                    $staff['position_name_i18n'] ?? null,
                    $currentLocale
                );
                $staffName = getLocalizedName($staff['name'], $staff['name_i18n'], $currentLocale);
                $bio = getLocalizedName($staff['bio'] ?? '', $staff['bio_i18n'] ?? null, $currentLocale);
                $services = $staffServices[$staff['id']] ?? [];
                $avatarUrl = $staff['avatar'] ?? '';
            ?>
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg overflow-hidden hover:shadow-xl transition-shadow group">
                <!-- Avatar -->
                <div class="aspect-[4/3] overflow-hidden bg-gradient-to-br from-blue-500 to-purple-600 flex items-center justify-center">
                    <?php if (!empty($avatarUrl)): ?>
                    <img src="<?= htmlspecialchars($avatarUrl) ?>"
                         alt="<?= htmlspecialchars($staffName) ?>"
                         class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                    <?php else: ?>
                    <span class="text-6xl font-bold text-white/60"><?= mb_substr($staffName, 0, 1) ?></span>
                    <?php endif; ?>
                </div>

                <!-- Info -->
                <div class="p-6">
                    <!-- Position Badge -->
                    <?php if (!empty($positionLabel)): ?>
                    <span class="inline-block px-3 py-1 text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full mb-3">
                        <?= htmlspecialchars($positionLabel) ?>
                    </span>
                    <?php endif; ?>

                    <!-- Name -->
                    <h3 class="text-xl font-bold text-gray-900 dark:text-white mb-2">
                        <?= htmlspecialchars($staffName) ?>
                    </h3>

                    <!-- Bio -->
                    <?php if (!empty($bio)): ?>
                    <p class="text-gray-600 dark:text-zinc-400 text-sm mb-4 line-clamp-3">
                        <?= nl2br(htmlspecialchars($bio)) ?>
                    </p>
                    <?php endif; ?>

                    <!-- Services -->
                    <?php if (!empty($services)): ?>
                    <div class="mb-4">
                        <p class="text-xs text-gray-400 dark:text-zinc-500 mb-2"><?= __('staff_page.services') ?></p>
                        <div class="flex flex-wrap gap-1.5">
                            <?php foreach (array_slice($services, 0, 4) as $svc): ?>
                            <span class="px-2 py-0.5 text-xs bg-gray-100 dark:bg-zinc-700 text-gray-600 dark:text-zinc-300 rounded-md">
                                <?= htmlspecialchars($svc) ?>
                            </span>
                            <?php endforeach; ?>
                            <?php if (count($services) > 4): ?>
                            <span class="px-2 py-0.5 text-xs text-gray-400 dark:text-zinc-500">
                                +<?= count($services) - 4 ?>
                            </span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- Booking Button -->
                    <a href="<?= $baseUrl ?>/booking<?= !empty($staff['id']) ? '?staff=' . $staff['id'] : '' ?>"
                       class="block w-full px-4 py-2.5 bg-blue-600 text-white font-medium rounded-lg hover:bg-blue-700 transition text-center">
                        <?= __('staff_page.book_with') ?>
                    </a>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <!-- CTA -->
        <div class="mt-16 text-center bg-gradient-to-r from-blue-600 to-purple-600 rounded-2xl p-8 md:p-12">
            <h2 class="text-2xl md:text-3xl font-bold text-white mb-4"><?= __('staff_page.cta_title') ?></h2>
            <p class="text-blue-100 mb-6 max-w-2xl mx-auto"><?= __('staff_page.cta_description') ?></p>
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
        console.log('[Staff] 스태프 소개 페이지 로드 완료');
    </script>

<?php
include BASE_PATH . '/resources/views/partials/footer.php';
?>
