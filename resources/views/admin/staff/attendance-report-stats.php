<?php
/**
 * RezlyX Admin - 근태 통계 리포트 (차트)
 */

if (!function_exists('__')) {
    require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';
}

$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
$filterMonth = $_GET['month'] ?? date('Y-m');
$monthStart = $filterMonth . '-01';
$monthEnd = date('Y-m-t', strtotime($monthStart));
$checkEnd = min($monthEnd, date('Y-m-d'));

$monthlyTrend = []; // 최근 6개월 출근율
$statusDist = [];   // 상태별 분포
$staffHours = [];   // 스태프별 근무시간 비교
$dailyData = [];    // 일별 출근 인원수

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

    $totalStaff = (int)$pdo->query("SELECT COUNT(*) FROM {$prefix}staff WHERE is_active = 1")->fetchColumn();

    // 1) 최근 6개월 출근율 트렌드
    for ($i = 5; $i >= 0; $i--) {
        $m = date('Y-m', strtotime("-{$i} months", strtotime($filterMonth . '-01')));
        $ms = $m . '-01';
        $me = date('Y-m-t', strtotime($ms));
        $ce = min($me, date('Y-m-d'));

        // 영업일 계산
        $bd = 0;
        $d = new DateTime($ms);
        $e = new DateTime($ce);
        while ($d <= $e) {
            if ((int)$d->format('N') <= 5) $bd++;
            $d->modify('+1 day');
        }
        $expected = $totalStaff * max(1, $bd);

        $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}attendance WHERE DATE(clock_in) BETWEEN ? AND ?");
        $stmt->execute([$ms, $ce]);
        $actual = (int)$stmt->fetchColumn();

        $monthlyTrend[$m] = $expected > 0 ? round(($actual / $expected) * 100, 1) : 0;
    }

    // 2) 이번 달 상태별 분포
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM {$prefix}attendance WHERE DATE(clock_in) BETWEEN ? AND ? GROUP BY status");
    $stmt->execute([$monthStart, $checkEnd]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $statusDist[$row['status']] = (int)$row['cnt'];
    }

    // 3) 스태프별 총 근무시간 비교 (이번 달)
    $stmt = $pdo->prepare("SELECT s.name, SUM(CASE WHEN a.work_hours IS NOT NULL THEN a.work_hours ELSE 0 END) as hours
        FROM {$prefix}staff s
        LEFT JOIN {$prefix}attendance a ON s.id = a.staff_id AND DATE(a.clock_in) BETWEEN ? AND ?
        WHERE s.is_active = 1 GROUP BY s.id ORDER BY hours DESC LIMIT 15");
    $stmt->execute([$monthStart, $checkEnd]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $staffHours[$row['name']] = round((float)$row['hours'], 1);
    }

    // 4) 일별 출근 인원수 (이번 달)
    $stmt = $pdo->prepare("SELECT DATE(clock_in) as d, COUNT(*) as cnt FROM {$prefix}attendance WHERE DATE(clock_in) BETWEEN ? AND ? GROUP BY DATE(clock_in) ORDER BY d ASC");
    $stmt->execute([$monthStart, $checkEnd]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $dailyData[date('m/d', strtotime($row['d']))] = (int)$row['cnt'];
    }

    $dbConnected = true;
} catch (PDOException $e) {
    $dbConnected = false;
    $dbError = $e->getMessage();
}

$statusLabels = [
    'working' => __('admin.staff.attendance.status.working'),
    'completed' => __('admin.staff.attendance.status.completed'),
    'absent' => __('admin.staff.attendance.status.absent'),
    'late' => __('admin.staff.attendance.status.late'),
    'early_leave' => __('admin.staff.attendance.status.early_leave'),
    'break' => __('admin.staff.attendance.status.break'),
    'outside' => __('admin.staff.attendance.status.outside'),
];
$statusColorMap = [
    'working'=>'#22c55e','completed'=>'#71717a','absent'=>'#ef4444',
    'late'=>'#f97316','early_leave'=>'#eab308','break'=>'#f59e0b','outside'=>'#6366f1'
];

$pageTitle = __('admin.staff.attendance.stats_title') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
?>
<!DOCTYPE html>
<html lang="<?= $config['locale'] ?? 'ko' ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <style>body { font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; }</style>
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4/dist/chart.umd.min.js"></script>
    <script>
        if (localStorage.getItem('darkMode') === 'true' ||
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-zinc-100 dark:bg-zinc-900 min-h-screen transition-colors">
    <div class="flex">
        <?php include dirname(__DIR__) . '/partials/admin-sidebar.php'; ?>
        <main class="flex-1 ml-64">
            <?php include dirname(__DIR__) . '/partials/admin-topbar.php'; ?>

            <div class="p-8">
                <!-- 헤더 -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= __('admin.staff.attendance.stats_title') ?></h1>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1"><?= __('admin.staff.attendance.stats_desc') ?></p>
                    </div>
                    <div class="flex gap-2 items-center">
                        <a href="<?= $adminUrl ?>/staff/attendance/report" class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-lg transition-colors"><?= __('admin.staff.attendance.report_title') ?></a>
                        <form method="GET" class="flex gap-2">
                            <input type="month" name="month" value="<?= $filterMonth ?>" class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm">
                            <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-zinc-700 hover:bg-zinc-800 dark:bg-zinc-600 dark:hover:bg-zinc-500 rounded-lg"><?= __('admin.staff.attendance.search') ?></button>
                        </form>
                    </div>
                </div>

                <!-- 차트 그리드 -->
                <div class="grid grid-cols-1 lg:grid-cols-2 gap-6 mb-6">
                    <!-- 출근율 트렌드 (6개월) -->
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 shadow-sm">
                        <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-4"><?= __('admin.staff.attendance.chart_trend') ?></h3>
                        <div style="height:250px;"><canvas id="trendChart"></canvas></div>
                    </div>

                    <!-- 상태별 분포 (도넛) -->
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 shadow-sm">
                        <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-4"><?= __('admin.staff.attendance.chart_status') ?> — <?= $filterMonth ?></h3>
                        <div style="height:250px;"><canvas id="statusChart"></canvas></div>
                    </div>

                    <!-- 스태프별 근무시간 -->
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 shadow-sm">
                        <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-4"><?= __('admin.staff.attendance.chart_staff_hours') ?> — <?= $filterMonth ?></h3>
                        <div style="height:250px;"><canvas id="staffChart"></canvas></div>
                    </div>

                    <!-- 일별 출근 인원 -->
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 shadow-sm">
                        <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-4"><?= __('admin.staff.attendance.chart_daily') ?> — <?= $filterMonth ?></h3>
                        <div style="height:250px;"><canvas id="dailyChart"></canvas></div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <script>
    (function() {
        'use strict';
        var isDark = document.documentElement.classList.contains('dark');
        var gridColor = isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.08)';
        var tickColor = isDark ? '#aaa' : '#666';

        // 1) 출근율 트렌드
        new Chart(document.getElementById('trendChart'), {
            type: 'line',
            data: {
                labels: <?= json_encode(array_keys($monthlyTrend)) ?>,
                datasets: [{
                    label: '<?= __('admin.staff.attendance.dash_rate') ?>',
                    data: <?= json_encode(array_values($monthlyTrend)) ?>,
                    borderColor: '#3b82f6', backgroundColor: 'rgba(59,130,246,0.1)',
                    fill: true, tension: 0.3, pointRadius: 5, pointBackgroundColor: '#3b82f6'
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { min: 0, max: 100, grid: { color: gridColor }, ticks: { color: tickColor, callback: function(v) { return v + '%'; } } },
                    x: { grid: { display: false }, ticks: { color: tickColor } }
                }
            }
        });

        // 2) 상태별 분포
        var statusData = <?= json_encode($statusDist) ?>;
        var statusLabelsMap = <?= json_encode($statusLabels) ?>;
        var statusColorsMap = <?= json_encode($statusColorMap) ?>;
        var sKeys = Object.keys(statusData);
        new Chart(document.getElementById('statusChart'), {
            type: 'doughnut',
            data: {
                labels: sKeys.map(function(k) { return statusLabelsMap[k] || k; }),
                datasets: [{
                    data: sKeys.map(function(k) { return statusData[k]; }),
                    backgroundColor: sKeys.map(function(k) { return statusColorsMap[k] || '#999'; })
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { position: 'right', labels: { color: tickColor, padding: 12 } } }
            }
        });

        // 3) 스태프별 근무시간
        var staffData = <?= json_encode($staffHours) ?>;
        new Chart(document.getElementById('staffChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(staffData),
                datasets: [{
                    label: '<?= __('admin.staff.attendance.work_hours') ?>',
                    data: Object.values(staffData),
                    backgroundColor: 'rgba(99,102,241,0.6)',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false, indexAxis: 'y',
                plugins: { legend: { display: false } },
                scales: {
                    x: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: tickColor, callback: function(v) { return v + 'h'; } } },
                    y: { grid: { display: false }, ticks: { color: tickColor } }
                }
            }
        });

        // 4) 일별 출근 인원
        var dailyData = <?= json_encode($dailyData) ?>;
        new Chart(document.getElementById('dailyChart'), {
            type: 'bar',
            data: {
                labels: Object.keys(dailyData),
                datasets: [{
                    label: '<?= __('admin.staff.attendance.chart_daily_count') ?>',
                    data: Object.values(dailyData),
                    backgroundColor: 'rgba(34,197,94,0.6)',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: gridColor }, ticks: { color: tickColor, stepSize: 1 } },
                    x: { grid: { display: false }, ticks: { color: tickColor, maxRotation: 45 } }
                }
            }
        });

        console.log('[AttendanceStats] All charts rendered');
    })();
    </script>
</body>
</html>
