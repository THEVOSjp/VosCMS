<?php
/**
 * 예약 통계 페이지
 */
include __DIR__ . '/_init.php';

$period = $_GET['period'] ?? 'month';
$year = (int)($_GET['year'] ?? date('Y'));
$month = (int)($_GET['month'] ?? date('m'));

// 기간 계산
if ($period === 'year') {
    $dateFrom = "$year-01-01";
    $dateTo = "$year-12-31";
    $periodLabel = "{$year}년";
} elseif ($period === 'week') {
    $baseDate = $_GET['date'] ?? date('Y-m-d');
    $ts = strtotime($baseDate);
    $dayOfWeek = (int)date('w', $ts);
    $dateFrom = date('Y-m-d', strtotime("-{$dayOfWeek} days", $ts));
    $dateTo = date('Y-m-d', strtotime("+" . (6 - $dayOfWeek) . " days", $ts));
    $periodLabel = "{$dateFrom} ~ {$dateTo}";
} else {
    $dateFrom = sprintf('%04d-%02d-01', $year, $month);
    $dateTo = sprintf('%04d-%02d-%02d', $year, $month, (int)date('t', mktime(0, 0, 0, $month, 1, $year)));
    $periodLabel = sprintf('%04d년 %02d월', $year, $month);
}

// 전체 통계
$stmt = $pdo->prepare("SELECT
    COUNT(*) as total,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending,
    SUM(CASE WHEN status = 'confirmed' THEN 1 ELSE 0 END) as confirmed,
    SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed,
    SUM(CASE WHEN status = 'cancelled' THEN 1 ELSE 0 END) as cancelled,
    SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) as no_show,
    COALESCE(SUM(final_amount), 0) as total_revenue,
    COALESCE(SUM(CASE WHEN status IN ('completed','confirmed') THEN final_amount ELSE 0 END), 0) as confirmed_revenue,
    COALESCE(SUM(discount_amount), 0) as total_discount
    FROM {$prefix}reservations
    WHERE reservation_date BETWEEN ? AND ?");
$stmt->execute([$dateFrom, $dateTo]);
$stats = $stmt->fetch(PDO::FETCH_ASSOC);

// 서비스별 통계 (junction table 기반)
$stmt = $pdo->prepare("SELECT
    rs.service_id, rs.service_name,
    COUNT(DISTINCT rs.reservation_id) as count,
    COALESCE(SUM(rs.price), 0) as revenue
    FROM {$prefix}reservation_services rs
    JOIN {$prefix}reservations r ON rs.reservation_id = r.id
    WHERE r.reservation_date BETWEEN ? AND ?
    AND r.status NOT IN ('cancelled','no_show')
    GROUP BY rs.service_id, rs.service_name
    ORDER BY revenue DESC");
$stmt->execute([$dateFrom, $dateTo]);
$serviceStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 번들별 통계
$stmt = $pdo->prepare("SELECT
    rs.bundle_id, b.name as bundle_name, b.bundle_price,
    COUNT(DISTINCT rs.reservation_id) as count,
    COUNT(DISTINCT rs.reservation_id) * b.bundle_price as revenue
    FROM {$prefix}reservation_services rs
    JOIN {$prefix}reservations r ON rs.reservation_id = r.id
    JOIN {$prefix}service_bundles b ON rs.bundle_id = b.id
    WHERE r.reservation_date BETWEEN ? AND ?
    AND r.status NOT IN ('cancelled','no_show')
    AND rs.bundle_id IS NOT NULL
    GROUP BY rs.bundle_id, b.name, b.bundle_price
    ORDER BY count DESC");
$stmt->execute([$dateFrom, $dateTo]);
$bundleStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 일별 추이 (최근 30일 또는 기간 내)
$stmt = $pdo->prepare("SELECT
    reservation_date as date,
    COUNT(*) as count,
    COALESCE(SUM(final_amount), 0) as revenue
    FROM {$prefix}reservations
    WHERE reservation_date BETWEEN ? AND ?
    GROUP BY reservation_date
    ORDER BY reservation_date ASC");
$stmt->execute([$dateFrom, $dateTo]);
$dailyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 시간대별 통계
$stmt = $pdo->prepare("SELECT
    HOUR(start_time) as hour,
    COUNT(*) as count
    FROM {$prefix}reservations
    WHERE reservation_date BETWEEN ? AND ?
    GROUP BY HOUR(start_time)
    ORDER BY hour");
$stmt->execute([$dateFrom, $dateTo]);
$hourlyStats = $stmt->fetchAll(PDO::FETCH_ASSOC);

$pageTitle = __('reservations.statistics') . ' - ' . $periodLabel;

include __DIR__ . '/_head.php';
?>

<div class="max-w-6xl mx-auto">
    <!-- 헤더 -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="<?= $adminUrl ?>/reservations" class="p-2 text-zinc-400 hover:text-zinc-600 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-lg transition">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <h2 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= __('reservations.statistics') ?></h2>
        </div>
    </div>

    <!-- 기간 선택 -->
    <div class="flex items-center gap-2 mb-6">
        <a href="<?= $adminUrl ?>/reservations/statistics?period=week&date=<?= date('Y-m-d') ?>"
           class="px-4 py-2 rounded-lg text-sm transition <?= $period === 'week' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 border border-zinc-300 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-700' ?>">
            이번 주
        </a>
        <a href="<?= $adminUrl ?>/reservations/statistics?period=month&year=<?= date('Y') ?>&month=<?= date('m') ?>"
           class="px-4 py-2 rounded-lg text-sm transition <?= $period === 'month' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 border border-zinc-300 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-700' ?>">
            이번 달
        </a>
        <a href="<?= $adminUrl ?>/reservations/statistics?period=year&year=<?= date('Y') ?>"
           class="px-4 py-2 rounded-lg text-sm transition <?= $period === 'year' ? 'bg-blue-600 text-white' : 'bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 border border-zinc-300 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-700' ?>">
            올해
        </a>
        <span class="ml-4 text-sm text-zinc-500 dark:text-zinc-400"><?= $periodLabel ?></span>
    </div>

    <!-- 요약 카드 -->
    <div class="grid grid-cols-2 lg:grid-cols-4 gap-4 mb-6">
        <?php
        $cards = [
            ['전체 예약', $stats['total'], 'bg-zinc-600', '건'],
            ['확정/완료', (int)$stats['confirmed'] + (int)$stats['completed'], 'bg-blue-600', '건'],
            ['취소/노쇼', (int)$stats['cancelled'] + (int)$stats['no_show'], 'bg-red-600', '건'],
            ['확정 매출', formatPrice((float)$stats['confirmed_revenue']), 'bg-green-600', ''],
        ];
        foreach ($cards as [$label, $value, $color, $unit]):
        ?>
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-5">
            <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= $label ?></p>
            <p class="text-2xl font-bold text-zinc-900 dark:text-white"><?= $value ?><span class="text-sm font-normal text-zinc-400 ml-1"><?= $unit ?></span></p>
        </div>
        <?php endforeach; ?>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
        <!-- 상태별 분포 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">상태별 분포</h3>
            <?php
            $total = max((int)$stats['total'], 1);
            $statuses = [
                ['대기중', (int)$stats['pending'], 'bg-yellow-400'],
                ['확정', (int)$stats['confirmed'], 'bg-blue-400'],
                ['완료', (int)$stats['completed'], 'bg-green-400'],
                ['취소', (int)$stats['cancelled'], 'bg-red-400'],
                ['노쇼', (int)$stats['no_show'], 'bg-zinc-400'],
            ];
            ?>
            <!-- 바 차트 -->
            <div class="flex h-8 rounded-lg overflow-hidden mb-4">
                <?php foreach ($statuses as [$label, $count, $color]):
                    $pct = round($count / $total * 100, 1);
                    if ($pct <= 0) continue;
                ?>
                <div class="<?= $color ?> relative" style="width: <?= $pct ?>%" title="<?= $label ?>: <?= $count ?>건 (<?= $pct ?>%)">
                    <?php if ($pct > 10): ?>
                    <span class="absolute inset-0 flex items-center justify-center text-[10px] font-bold text-white"><?= $pct ?>%</span>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
            </div>
            <!-- 범례 -->
            <div class="space-y-2">
                <?php foreach ($statuses as [$label, $count, $color]):
                    $pct = round($count / $total * 100, 1);
                ?>
                <div class="flex items-center justify-between text-sm">
                    <span class="flex items-center gap-2">
                        <span class="w-3 h-3 rounded <?= $color ?>"></span>
                        <span class="text-zinc-600 dark:text-zinc-400"><?= $label ?></span>
                    </span>
                    <span class="text-zinc-900 dark:text-white font-medium"><?= $count ?>건 <span class="text-zinc-400 text-xs">(<?= $pct ?>%)</span></span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 서비스별 통계 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">서비스별 예약</h3>
            <?php if (empty($serviceStats)): ?>
            <p class="text-sm text-zinc-500 text-center py-8">데이터가 없습니다.</p>
            <?php else: ?>
            <div class="space-y-3">
                <?php
                $maxCount = max(array_column($serviceStats, 'count'));
                foreach ($serviceStats as $ss):
                    $barWidth = max(round($ss['count'] / max($maxCount, 1) * 100), 2);
                ?>
                <div>
                    <div class="flex items-center justify-between text-sm mb-1">
                        <span class="text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($ss['service_name'] ?? '미지정') ?></span>
                        <span class="text-zinc-500"><?= $ss['count'] ?>건 / <?= formatPrice((float)$ss['revenue']) ?></span>
                    </div>
                    <div class="h-2 bg-zinc-100 dark:bg-zinc-700 rounded-full overflow-hidden">
                        <div class="h-full bg-blue-500 rounded-full" style="width: <?= $barWidth ?>%"></div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 번들별 통계 -->
    <?php if (!empty($bundleStats)): ?>
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6 mb-6">
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
            <svg class="w-5 h-5 inline -mt-0.5 mr-1 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            <?= __('bundles.nav') ?> <?= __('reservations.statistics') ?>
        </h3>
        <div class="space-y-3">
            <?php
            $maxBdlCount = max(array_column($bundleStats, 'count'));
            foreach ($bundleStats as $bs):
                $barWidth = max(round($bs['count'] / max($maxBdlCount, 1) * 100), 2);
            ?>
            <div>
                <div class="flex items-center justify-between text-sm mb-1">
                    <span class="text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($bs['bundle_name']) ?></span>
                    <span class="text-zinc-500"><?= $bs['count'] ?>건 / <?= formatPrice((float)$bs['revenue']) ?></span>
                </div>
                <div class="h-2 bg-zinc-100 dark:bg-zinc-700 rounded-full overflow-hidden">
                    <div class="h-full bg-purple-500 rounded-full" style="width: <?= $barWidth ?>%"></div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

    <!-- 시간대별 분포 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6 mb-6">
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">시간대별 예약</h3>
        <?php if (empty($hourlyStats)): ?>
        <p class="text-sm text-zinc-500 text-center py-8">데이터가 없습니다.</p>
        <?php else: ?>
        <div class="flex items-end gap-1 h-40">
            <?php
            $hourMap = array_column($hourlyStats, 'count', 'hour');
            $maxHourly = max(array_values($hourMap) ?: [1]);
            for ($h = 0; $h < 24; $h++):
                $count = $hourMap[$h] ?? 0;
                $barH = $count > 0 ? max(round($count / $maxHourly * 100), 4) : 0;
                $isBusinessHour = ($h >= 9 && $h <= 18);
            ?>
            <div class="flex-1 flex flex-col items-center gap-1">
                <div class="w-full rounded-t relative" style="height: <?= $barH ?>%"
                     title="<?= $h ?>시: <?= $count ?>건">
                    <div class="absolute inset-0 <?= $isBusinessHour ? 'bg-blue-400 dark:bg-blue-500' : 'bg-zinc-300 dark:bg-zinc-600' ?> rounded-t"></div>
                </div>
                <?php if ($h % 3 === 0): ?>
                <span class="text-[9px] text-zinc-400"><?= $h ?></span>
                <?php endif; ?>
            </div>
            <?php endfor; ?>
        </div>
        <p class="text-xs text-zinc-400 mt-2 text-center">시간 (0~23시) · <span class="inline-block w-3 h-2 bg-blue-400 rounded"></span> 영업시간 · <span class="inline-block w-3 h-2 bg-zinc-300 rounded"></span> 기타</p>
        <?php endif; ?>
    </div>

    <!-- 일별 추이 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">일별 예약 추이</h3>
        <?php if (empty($dailyStats)): ?>
        <p class="text-sm text-zinc-500 text-center py-8">데이터가 없습니다.</p>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-sm">
                <thead>
                    <tr class="border-b border-zinc-200 dark:border-zinc-700">
                        <th class="py-2 text-left text-zinc-500 dark:text-zinc-400 font-medium">날짜</th>
                        <th class="py-2 text-right text-zinc-500 dark:text-zinc-400 font-medium">예약 수</th>
                        <th class="py-2 text-right text-zinc-500 dark:text-zinc-400 font-medium">매출</th>
                        <th class="py-2 text-left pl-4 text-zinc-500 dark:text-zinc-400 font-medium">그래프</th>
                    </tr>
                </thead>
                <tbody>
                    <?php
                    $maxDaily = max(array_column($dailyStats, 'count') ?: [1]);
                    foreach ($dailyStats as $ds):
                        $barW = max(round($ds['count'] / $maxDaily * 100), 2);
                    ?>
                    <tr class="border-b border-zinc-100 dark:border-zinc-700/50">
                        <td class="py-2 text-zinc-700 dark:text-zinc-300 font-mono"><?= $ds['date'] ?></td>
                        <td class="py-2 text-right text-zinc-900 dark:text-white font-medium"><?= $ds['count'] ?>건</td>
                        <td class="py-2 text-right text-zinc-600 dark:text-zinc-400"><?= formatPrice((float)$ds['revenue']) ?></td>
                        <td class="py-2 pl-4">
                            <div class="h-4 bg-zinc-100 dark:bg-zinc-700 rounded-full overflow-hidden w-32">
                                <div class="h-full bg-blue-400 rounded-full" style="width: <?= $barW ?>%"></div>
                            </div>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>
    </div>
</div>

<script>console.log('[Reservations] Statistics page loaded, period=<?= $period ?>');</script>

<?php include __DIR__ . '/_foot.php'; ?>
