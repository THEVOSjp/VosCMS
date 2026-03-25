<?php
/**
 * RezlyX Staff Page - 스태프 소개 (HIRO GINZA 스타일)
 */

require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
use RzxLib\Core\Auth\Auth;

$pageTitle = ($config['app_name'] ?? 'RezlyX') . ' - ' . __('staff_page.title');
$baseUrl = $config['app_url'] ?? '';

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
            WHERE s.is_active = 1 AND (s.is_visible = 1 OR s.is_visible IS NULL)";

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
        $_locale = $currentLocale ?? ($config['locale'] ?? 'ko');
        $stmt = $pdo->prepare("SELECT ss.staff_id, sv.name as service_name,
                COALESCE(t.content, te.content, sv.name) as localized_name
            FROM {$prefix}staff_services ss
            JOIN {$prefix}services sv ON ss.service_id = sv.id
            LEFT JOIN {$prefix}translations t ON t.lang_key = CONCAT('service.', sv.id, '.name') AND t.locale = ?
            LEFT JOIN {$prefix}translations te ON te.lang_key = CONCAT('service.', sv.id, '.name') AND te.locale = 'en'
            WHERE ss.staff_id IN ({$placeholders}) AND sv.is_active = 1
            ORDER BY sv.sort_order, sv.name");
        $stmt->execute(array_merge([$_locale], $staffIds));
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $staffServices[$row['staff_id']][] = $row['localized_name'];
        }
    }
} catch (PDOException $e) {
    if ($config['debug'] ?? false) {
        error_log('Staff page DB error: ' . $e->getMessage());
    }
}

// 다국어 헬퍼
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

// 후리가나/영문명 가져오기 (일본어 외 로케일에서는 영문, 일본어면 후리가나)
function getSubName($nameI18n, $locale) {
    if (empty($nameI18n)) return '';
    $i18n = is_string($nameI18n) ? json_decode($nameI18n, true) : $nameI18n;
    if (!is_array($i18n)) return '';
    if ($locale === 'ja' && !empty($i18n['ko'])) return $i18n['ko'];
    if (!empty($i18n['en'])) return $i18n['en'];
    if (!empty($i18n['ja'])) return $i18n['ja'];
    return '';
}

// === 위젯 기반 렌더링 ===
require_once BASE_PATH . '/rzxlib/Core/Modules/WidgetRenderer.php';
$_staffWidgetRenderer = null;
try {
    $_staffWidgetRenderer = new \RzxLib\Core\Modules\WidgetRenderer($pdo, 'staff', $currentLocale, $baseUrl);
} catch (\Throwable $e) {}

$seoContext = ['type' => 'sub', 'subpage_title' => __('staff_page.title')];
?>

<?php if ($_staffWidgetRenderer && $_staffWidgetRenderer->hasWidgets()): ?>
<!-- 위젯 기반 스태프 페이지 -->
<?= $_staffWidgetRenderer->renderAll() ?>

<?php else: ?>
<!-- 기본 스태프 페이지 (위젯 미배치 시 폴백) -->
    <main class="max-w-7xl mx-auto px-4 py-8">
        <!-- Page Title -->
        <div class="mb-8">
            <?php require_once BASE_PATH . '/rzxlib/Core/Helpers/admin-icons.php'; ?>
            <h1 class="text-3xl font-bold text-gray-900 dark:text-white flex items-center gap-3">
                <?= __('staff_page.subtitle') ?>
                <span class="text-lg font-normal text-gray-500 dark:text-zinc-400"><?= __('staff_page.title') ?></span>
                <?= rzx_admin_icons($baseUrl . '/staff/settings', $baseUrl . '/staff/edit') ?>
            </h1>
        </div>

        <!-- Position Filter -->
        <?php if (!empty($positions)): ?>
        <div class="flex flex-wrap gap-2 mb-8 border-b border-gray-200 dark:border-zinc-700 pb-4">
            <a href="<?= $baseUrl ?>/staff"
               class="px-4 py-2 text-sm font-medium transition <?= $selectedPosition === 'all' ? 'text-blue-600 border-b-2 border-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-zinc-400 hover:text-gray-900 dark:hover:text-white' ?>">
                <?= __('staff_page.all_staff') ?>
            </a>
            <?php foreach ($positions as $pos): ?>
            <a href="<?= $baseUrl ?>/staff?position=<?= $pos['id'] ?>"
               class="px-4 py-2 text-sm font-medium transition <?= $selectedPosition == $pos['id'] ? 'text-blue-600 border-b-2 border-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-zinc-400 hover:text-gray-900 dark:hover:text-white' ?>">
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
        <div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-4 gap-6">
            <?php foreach ($staffList as $staff):
                $positionLabel = getLocalizedName(
                    $staff['position_name'] ?? '',
                    $staff['position_name_i18n'] ?? null,
                    $currentLocale
                );
                $staffName = getLocalizedName($staff['name'], $staff['name_i18n'], $currentLocale);
                $subName = getSubName($staff['name_i18n'], $currentLocale);
                $bio = getLocalizedName($staff['bio'] ?? '', $staff['bio_i18n'] ?? null, $currentLocale);
                $services = $staffServices[$staff['id']] ?? [];
                $rawAvatar = $staff['avatar'] ?? '';
                $avatarUrl = !empty($rawAvatar) ? (str_starts_with($rawAvatar, 'http') ? $rawAvatar : $baseUrl . '/' . ltrim($rawAvatar, '/')) : '';
                $designationFee = (float)($staff['designation_fee'] ?? 0);
            ?>
            <div class="text-center group">
                <!-- Avatar (세로형) -->
                <a href="<?= $baseUrl ?>/staff/<?= $staff['id'] ?>" class="block mb-3">
                    <div class="aspect-[3/4] overflow-hidden bg-gray-100 dark:bg-zinc-800 rounded-lg">
                        <?php if (!empty($avatarUrl)): ?>
                        <img src="<?= htmlspecialchars($avatarUrl) ?>"
                             alt="<?= htmlspecialchars($staffName) ?>"
                             class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-200 to-gray-300 dark:from-zinc-700 dark:to-zinc-800">
                            <span class="text-5xl font-bold text-gray-400 dark:text-zinc-500"><?= mb_substr($staffName, 0, 1) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </a>

                <!-- Name -->
                <h3 class="text-base font-bold text-gray-900 dark:text-white leading-tight">
                    <a href="<?= $baseUrl ?>/staff/<?= $staff['id'] ?>" class="hover:text-blue-600 dark:hover:text-blue-400">
                        <?= htmlspecialchars($staffName) ?>
                    </a>
                </h3>

                <!-- Designation Fee -->
                <?php if ($designationFee > 0): ?>
                <p class="text-red-600 dark:text-red-400 text-sm font-semibold mt-0.5">
                    <?= __('staff_page.designation_fee') ?> &yen;<?= number_format($designationFee) ?>
                </p>
                <?php endif; ?>

                <!-- Sub Name (영문/후리가나) -->
                <?php if (!empty($subName)): ?>
                <p class="text-gray-500 dark:text-zinc-400 text-xs mt-0.5"><?= htmlspecialchars($subName) ?></p>
                <?php endif; ?>

                <!-- Position + Services -->
                <div class="text-xs text-gray-500 dark:text-zinc-400 mt-1 space-y-0.5">
                    <?php if (!empty($positionLabel)): ?>
                    <p><?= htmlspecialchars($positionLabel) ?></p>
                    <?php endif; ?>
                    <?php if (!empty($services)): ?>
                    <p class="text-gray-400 dark:text-zinc-500">
                        <?= htmlspecialchars(implode(' / ', array_slice($services, 0, 3))) ?>
                        <?php if (count($services) > 3): ?>...<?php endif; ?>
                    </p>
                    <?php endif; ?>
                </div>

                <!-- Button -->
                <div class="mt-3">
                    <a href="<?= $baseUrl ?>/staff/<?= $staff['id'] ?>"
                       class="flex items-center justify-center w-full py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-xs font-medium rounded hover:bg-gray-700 dark:hover:bg-gray-200 transition">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/>
                        </svg>
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
<?php endif; ?>
