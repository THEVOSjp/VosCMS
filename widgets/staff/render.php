<?php
/**
 * Staff Widget - render.php
 * 스태프 목록을 카드 형태로 표시하는 시스템 위젯
 *
 * Config options:
 *   title, subtitle, columns(2-6), show_position_filter, show_designation_fee,
 *   show_booking_btn, show_services, max_staff(0=all)
 */
// $config: WidgetLoader에서 전달되는 설정 배열
$wColumns = (int)($config['columns'] ?? 4);
$wShowFilter = ($config['show_position_filter'] ?? true) !== false && ($config['show_position_filter'] ?? '1') !== '0';
$wShowFee = ($config['show_designation_fee'] ?? true) !== false && ($config['show_designation_fee'] ?? '1') !== '0';
$wShowBtn = ($config['show_booking_btn'] ?? true) !== false && ($config['show_booking_btn'] ?? '1') !== '0';
$wShowSvc = ($config['show_services'] ?? true) !== false && ($config['show_services'] ?? '1') !== '0';
$wMaxStaff = (int)($config['max_staff'] ?? 0);
$wTitle = $config['title'] ?? '';
$wSubtitle = $config['subtitle'] ?? '';

$baseUrl = $baseUrl ?? ($config['app_url'] ?? '');
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$currentLocale = $locale ?? ($config['locale'] ?? 'ko');
$selectedPosition = $_GET['position'] ?? 'all';

// 다국어 헬퍼
if (!function_exists('_wStaffLocName')) {
    function _wStaffLocName($name, $nameI18n, $locale) {
        if (!empty($nameI18n)) {
            $i18n = is_string($nameI18n) ? json_decode($nameI18n, true) : $nameI18n;
            if (is_array($i18n) && !empty($i18n[$locale])) return $i18n[$locale];
        }
        return $name;
    }
    function _wStaffSubName($nameI18n, $locale) {
        if (empty($nameI18n)) return '';
        $i18n = is_string($nameI18n) ? json_decode($nameI18n, true) : $nameI18n;
        if (!is_array($i18n)) return '';
        if (!empty($i18n['en'])) return $i18n['en'];
        return '';
    }
}

try {
    // 포지션 목록
    $positions = [];
    if ($wShowFilter) {
        $stmt = $pdo->query("SELECT * FROM {$prefix}staff_positions WHERE is_active = 1 ORDER BY sort_order, name");
        $positions = $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    // 스태프 목록
    $sql = "SELECT s.*, p.name as position_name, p.name_i18n as position_name_i18n
            FROM {$prefix}staff s
            LEFT JOIN {$prefix}staff_positions p ON s.position_id = p.id
            WHERE s.is_active = 1 AND (s.is_visible = 1 OR s.is_visible IS NULL)";
    if ($selectedPosition !== 'all' && is_numeric($selectedPosition)) {
        $sql .= " AND s.position_id = " . (int)$selectedPosition;
    }
    $sql .= " ORDER BY s.sort_order, s.name";
    if ($wMaxStaff > 0) $sql .= " LIMIT " . $wMaxStaff;

    $staffList = $pdo->query($sql)->fetchAll(PDO::FETCH_ASSOC);

    // 담당 서비스
    $staffServices = [];
    if ($wShowSvc && !empty($staffList)) {
        $ids = array_column($staffList, 'id');
        $ph = implode(',', array_fill(0, count($ids), '?'));
        $_wLocale = $locale ?? ($currentLocale ?? 'ko');
        $stmt = $pdo->prepare("SELECT ss.staff_id, COALESCE(t.content, te.content, sv.name) as service_name
            FROM {$prefix}staff_services ss
            JOIN {$prefix}services sv ON ss.service_id = sv.id
            LEFT JOIN {$prefix}translations t ON t.lang_key = CONCAT('service.', sv.id, '.name') AND t.locale = ?
            LEFT JOIN {$prefix}translations te ON te.lang_key = CONCAT('service.', sv.id, '.name') AND te.locale = 'en'
            WHERE ss.staff_id IN ({$ph}) AND sv.is_active = 1
            ORDER BY sv.sort_order, sv.name");
        $stmt->execute(array_merge([$_wLocale], $ids));
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $staffServices[$row['staff_id']][] = $row['service_name'];
        }
    }
} catch (PDOException $e) {
    $positions = [];
    $staffList = [];
    $staffServices = [];
}

$colClass = ['2'=>'grid-cols-2','3'=>'grid-cols-2 md:grid-cols-3','4'=>'grid-cols-2 md:grid-cols-3 lg:grid-cols-4','5'=>'grid-cols-2 md:grid-cols-3 lg:grid-cols-5','6'=>'grid-cols-2 md:grid-cols-3 lg:grid-cols-6'];
$gridCols = $colClass[$wColumns] ?? $colClass[4];

ob_start();
?>

<section class="py-12 w-full">
    <!-- 배너, 카테고리, 카드 그리드: 모두 max-w-7xl로 제약 -->
    <div class="max-w-7xl mx-auto px-4">
        <?php if ($wTitle || $wSubtitle): ?>
        <div class="text-center mb-8">
            <?php if ($wTitle): ?><h2 class="text-3xl font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($wTitle) ?></h2><?php endif; ?>
            <?php if ($wSubtitle): ?><p class="text-gray-600 dark:text-zinc-400 mt-2"><?= htmlspecialchars($wSubtitle) ?></p><?php endif; ?>
        </div>
        <?php endif; ?>

        <?php if ($wShowFilter && !empty($positions)): ?>
        <div class="flex flex-wrap gap-2 mb-8 border-b border-gray-200 dark:border-zinc-700 pb-4">
            <a href="<?= $baseUrl ?>/staff" class="px-4 py-2 text-sm font-medium transition <?= $selectedPosition === 'all' ? 'text-blue-600 border-b-2 border-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-zinc-400 hover:text-gray-900' ?>"><?= __('staff_page.all_staff') ?? '전체' ?></a>
            <?php foreach ($positions as $pos): ?>
            <a href="<?= $baseUrl ?>/staff?position=<?= $pos['id'] ?>" class="px-4 py-2 text-sm font-medium transition <?= $selectedPosition == $pos['id'] ? 'text-blue-600 border-b-2 border-blue-600 dark:text-blue-400' : 'text-gray-600 dark:text-zinc-400 hover:text-gray-900' ?>"><?= htmlspecialchars(_wStaffLocName($pos['name'], $pos['name_i18n'], $currentLocale)) ?></a>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if (empty($staffList)): ?>
        <div class="text-center py-16">
            <svg class="w-20 h-20 text-gray-300 dark:text-zinc-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
            <p class="text-xl text-gray-500 dark:text-zinc-400"><?= __('staff_page.no_staff') ?? '등록된 스태프가 없습니다.' ?></p>
        </div>
        <?php else: ?>
        <div class="grid <?= $gridCols ?> gap-6">
            <?php foreach ($staffList as $staff):
                $posLabel = _wStaffLocName($staff['position_name'] ?? '', $staff['position_name_i18n'] ?? null, $currentLocale);
                $sName = _wStaffLocName($staff['name'], $staff['name_i18n'] ?? null, $currentLocale);
                $subName = _wStaffSubName($staff['name_i18n'] ?? null, $currentLocale);
                $svcs = $staffServices[$staff['id']] ?? [];
                $_rawAvatar = $staff['avatar'] ?? '';
                $avatar = !empty($_rawAvatar) ? (str_starts_with($_rawAvatar, 'http') ? $_rawAvatar : $baseUrl . '/' . ltrim($_rawAvatar, '/')) : '';
                $fee = (float)($staff['designation_fee'] ?? 0);
            ?>
            <div class="text-center group">
                <a href="<?= $baseUrl ?>/staff/<?= $staff['id'] ?>" class="block mb-3">
                    <div class="aspect-[3/4] overflow-hidden bg-gray-100 dark:bg-zinc-800 rounded-lg">
                        <?php if ($avatar): ?>
                        <img src="<?= htmlspecialchars($avatar) ?>" alt="<?= htmlspecialchars($sName) ?>" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">
                        <?php else: ?>
                        <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-200 to-gray-300 dark:from-zinc-700 dark:to-zinc-800">
                            <span class="text-5xl font-bold text-gray-400 dark:text-zinc-500"><?= mb_substr($sName, 0, 1) ?></span>
                        </div>
                        <?php endif; ?>
                    </div>
                </a>
                <h3 class="text-base font-bold text-gray-900 dark:text-white"><a href="<?= $baseUrl ?>/staff/<?= $staff['id'] ?>" class="hover:text-blue-600"><?= htmlspecialchars($sName) ?></a></h3>
                <?php if ($wShowFee && $fee > 0): ?>
                <p class="text-red-600 dark:text-red-400 text-sm font-semibold mt-0.5"><?= __('staff_page.designation_fee') ?? '지명료' ?> &yen;<?= number_format($fee) ?></p>
                <?php endif; ?>
                <?php if ($subName): ?><p class="text-gray-500 dark:text-zinc-400 text-xs mt-0.5"><?= htmlspecialchars($subName) ?></p><?php endif; ?>
                <div class="text-xs text-gray-500 dark:text-zinc-400 mt-1">
                    <?php if ($posLabel): ?><p><?= htmlspecialchars($posLabel) ?></p><?php endif; ?>
                    <?php if ($wShowSvc && !empty($svcs)): ?>
                    <p class="text-gray-400 dark:text-zinc-500"><?= htmlspecialchars(implode(' / ', array_slice($svcs, 0, 3))) ?><?= count($svcs) > 3 ? '...' : '' ?></p>
                    <?php endif; ?>
                </div>
                <?php if ($wShowBtn): ?>
                <div class="mt-3">
                    <a href="<?= $baseUrl ?>/staff/<?= $staff['id'] ?>" class="flex items-center justify-center w-full py-2 bg-gray-900 dark:bg-white text-white dark:text-gray-900 text-xs font-medium rounded hover:bg-gray-700 dark:hover:bg-gray-200 transition">
                        <svg class="w-3.5 h-3.5 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                        <?= __('staff_page.book_with') ?? '지명예약' ?>
                    </a>
                </div>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php return ob_get_clean();
