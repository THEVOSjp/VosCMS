<?php
/**
 * RezlyX Admin - 개인별 근태 상세 리포트
 */

if (!function_exists('__')) {
    require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';
}

$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');

$staffId = $reportStaffId ?? (int)($_GET['staff_id'] ?? 0);
$filterDateFrom = $_GET['date_from'] ?? date('Y-m-01');
$filterDateTo = $_GET['date_to'] ?? date('Y-m-d');

$staffInfo = null;
$records = [];
$personalStats = ['work_days' => 0, 'total_hours' => 0, 'avg_hours' => 0, 'late' => 0, 'early_leave' => 0, 'break_min' => 0, 'outside' => 0];
$dailySummary = [];
$staffList = [];

$statusLabels = [
    'working' => __('admin.staff.attendance.status.working'),
    'completed' => __('admin.staff.attendance.status.completed'),
    'absent' => __('admin.staff.attendance.status.absent'),
    'late' => __('admin.staff.attendance.status.late'),
    'early_leave' => __('admin.staff.attendance.status.early_leave'),
    'break' => __('admin.staff.attendance.status.break'),
    'outside' => __('admin.staff.attendance.status.outside'),
];
$statusColors = [
    'working'=>'bg-green-100 text-green-700','completed'=>'bg-zinc-100 text-zinc-600',
    'absent'=>'bg-red-100 text-red-700','late'=>'bg-orange-100 text-orange-700',
    'early_leave'=>'bg-yellow-100 text-yellow-700','break'=>'bg-amber-100 text-amber-700',
    'outside'=>'bg-indigo-100 text-indigo-700'
];

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

    $staffList = $pdo->query("SELECT id, name FROM {$prefix}staff WHERE is_active = 1 ORDER BY sort_order ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);

    if ($staffId) {
        $stmt = $pdo->prepare("SELECT * FROM {$prefix}staff WHERE id = ?");
        $stmt->execute([$staffId]);
        $staffInfo = $stmt->fetch(PDO::FETCH_ASSOC);
    }

    if ($staffInfo) {
        // CSV 다운로드
        if (isset($_GET['export']) && $_GET['export'] === 'csv') {
            $stmt = $pdo->prepare("SELECT DATE(clock_in) as work_date, TIME(clock_in) as in_time, TIME(clock_out) as out_time, work_hours, break_minutes, status, source, memo FROM {$prefix}attendance WHERE staff_id = ? AND DATE(clock_in) BETWEEN ? AND ? ORDER BY clock_in ASC");
            $stmt->execute([$staffId, $filterDateFrom, $filterDateTo]);
            $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

            header('Content-Type: text/csv; charset=utf-8');
            header('Content-Disposition: attachment; filename="attendance_' . preg_replace('/[^a-zA-Z0-9]/', '_', $staffInfo['name']) . '_' . $filterDateFrom . '_' . $filterDateTo . '.csv"');
            $out = fopen('php://output', 'w');
            fwrite($out, "\xEF\xBB\xBF");
            fputcsv($out, [__('admin.staff.attendance.col_date'), __('admin.staff.attendance.clock_in'), __('admin.staff.attendance.clock_out'), __('admin.staff.attendance.work_hours'), __('admin.staff.attendance.break_time'), __('admin.staff.attendance.col_status'), __('admin.staff.attendance.col_memo')]);
            foreach ($rows as $r) {
                $bmVal = (int)$r['break_minutes'];
                $bt = $bmVal > 0 ? floor($bmVal / 60) . 'h ' . ($bmVal % 60) . 'm' : '';
                fputcsv($out, [$r['work_date'], $r['in_time'], $r['out_time'] ?? '', $r['work_hours'] ?? '', $bt, $statusLabels[$r['status']] ?? $r['status'], $r['memo'] ?? '']);
            }
            fclose($out);
            exit;
        }

        // 개인 기록
        $stmt = $pdo->prepare("SELECT * FROM {$prefix}attendance WHERE staff_id = ? AND DATE(clock_in) BETWEEN ? AND ? ORDER BY clock_in DESC");
        $stmt->execute([$staffId, $filterDateFrom, $filterDateTo]);
        $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

        // 통계 계산
        foreach ($records as $r) {
            $personalStats['work_days']++;
            $personalStats['total_hours'] += (float)($r['work_hours'] ?? 0);
            if ($r['status'] === 'late') $personalStats['late']++;
            if ($r['status'] === 'early_leave') $personalStats['early_leave']++;
            $personalStats['break_min'] += (int)($r['break_minutes'] ?? 0);
            if ($r['status'] === 'outside') $personalStats['outside']++;
        }
        $personalStats['avg_hours'] = $personalStats['work_days'] > 0 ? round($personalStats['total_hours'] / $personalStats['work_days'], 1) : 0;

        // 일별 근무시간 (차트용)
        foreach ($records as $r) {
            $date = date('m/d', strtotime($r['clock_in']));
            $dailySummary[$date] = (float)($r['work_hours'] ?? 0);
        }
        $dailySummary = array_reverse($dailySummary);
    }

    $dbConnected = true;
} catch (PDOException $e) {
    $dbConnected = false;
    $dbError = $e->getMessage();
}

$pageTitle = __('admin.staff.attendance.rpt_personal_title') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
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
                        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= __('admin.staff.attendance.rpt_personal_title') ?></h1>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1"><?= $staffInfo ? htmlspecialchars($staffInfo['name']) . ' — ' : '' ?><?= $filterDateFrom ?> ~ <?= $filterDateTo ?></p>
                    </div>
                    <div class="flex gap-2">
                        <a href="<?= $adminUrl ?>/staff/attendance/report?date_from=<?= $filterDateFrom ?>&date_to=<?= $filterDateTo ?>" class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-lg transition-colors"><?= __('admin.staff.attendance.report_title') ?></a>
                        <?php if ($staffInfo): ?>
                        <a href="?staff_id=<?= $staffId ?>&date_from=<?= $filterDateFrom ?>&date_to=<?= $filterDateTo ?>&export=csv" class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            CSV
                        </a>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- 스태프/기간 선택 -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 shadow-sm mb-6">
                    <form method="GET" action="<?= $adminUrl ?>/staff/attendance/report/personal" class="flex items-end gap-4 flex-wrap">
                        <div>
                            <label class="block text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.staff.attendance.filter_staff') ?></label>
                            <select name="staff_id" required class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm">
                                <option value=""><?= __('admin.staff.attendance.rpt_select_staff') ?></option>
                                <?php foreach ($staffList as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= $staffId == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.staff.attendance.filter_from') ?></label>
                            <input type="date" name="date_from" value="<?= $filterDateFrom ?>" class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.staff.attendance.filter_to') ?></label>
                            <input type="date" name="date_to" value="<?= $filterDateTo ?>" class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm">
                        </div>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-zinc-700 hover:bg-zinc-800 dark:bg-zinc-600 dark:hover:bg-zinc-500 rounded-lg"><?= __('admin.staff.attendance.search') ?></button>
                    </form>
                </div>

                <?php if ($staffInfo): ?>
                <!-- 개인 통계 카드 -->
                <div class="grid grid-cols-3 md:grid-cols-6 gap-4 mb-6">
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 shadow-sm">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.staff.attendance.dash_work_days') ?></p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-white"><?= $personalStats['work_days'] ?></p>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 shadow-sm">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.staff.attendance.rpt_total_hours') ?></p>
                        <p class="text-2xl font-bold text-blue-600"><?= round($personalStats['total_hours'], 1) ?>h</p>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 shadow-sm">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.staff.attendance.dash_avg_hours') ?></p>
                        <p class="text-2xl font-bold text-cyan-600"><?= $personalStats['avg_hours'] ?>h</p>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 shadow-sm">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.staff.attendance.dash_late') ?></p>
                        <p class="text-2xl font-bold text-orange-600"><?= $personalStats['late'] ?></p>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 shadow-sm">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.staff.attendance.dash_early_leave') ?></p>
                        <p class="text-2xl font-bold text-yellow-600"><?= $personalStats['early_leave'] ?></p>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 shadow-sm">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('admin.staff.attendance.break_time') ?></p>
                        <?php $bkm = $personalStats['break_min']; $bkStr = $bkm > 0 ? floor($bkm / 60) . 'h ' . ($bkm % 60) . 'm' : '0'; ?>
                        <p class="text-2xl font-bold text-amber-600"><?= $bkStr ?></p>
                    </div>
                </div>

                <!-- 일별 근무시간 차트 -->
                <?php if (!empty($dailySummary)): ?>
                <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 shadow-sm mb-6">
                    <h3 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-4"><?= __('admin.staff.attendance.rpt_daily_chart') ?></h3>
                    <div style="height:200px;"><canvas id="dailyChart"></canvas></div>
                </div>
                <?php endif; ?>

                <!-- 상세 기록 테이블 -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('admin.staff.attendance.rpt_detail_records') ?></h2>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-50 dark:bg-zinc-700/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400"><?= __('admin.staff.attendance.col_date') ?></th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400"><?= __('admin.staff.attendance.clock_in') ?></th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400"><?= __('admin.staff.attendance.clock_out') ?></th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400"><?= __('admin.staff.attendance.work_hours') ?></th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400"><?= __('admin.staff.attendance.break_time') ?></th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400"><?= __('admin.staff.attendance.col_status') ?></th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400"><?= __('admin.staff.attendance.col_memo') ?></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                            <?php foreach ($records as $r): ?>
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                                <td class="px-4 py-3 font-mono text-xs"><?= date('Y-m-d (D)', strtotime($r['clock_in'])) ?></td>
                                <td class="px-4 py-3 text-center font-mono text-xs"><?= date('H:i', strtotime($r['clock_in'])) ?></td>
                                <td class="px-4 py-3 text-center font-mono text-xs"><?= $r['clock_out'] ? date('H:i', strtotime($r['clock_out'])) : '-' ?></td>
                                <td class="px-4 py-3 text-center font-mono text-xs"><?= $r['work_hours'] ? $r['work_hours'].'h' : '-' ?></td>
                                <td class="px-4 py-3 text-center font-mono text-xs"><?php $bm = (int)($r['break_minutes'] ?? 0); echo $bm > 0 ? floor($bm / 60) . 'h ' . ($bm % 60) . 'm' : '-'; ?></td>
                                <td class="px-4 py-3 text-center"><span class="px-2 py-0.5 text-xs font-medium rounded-full <?= $statusColors[$r['status']] ?? $statusColors['absent'] ?>"><?= $statusLabels[$r['status']] ?? $r['status'] ?></span></td>
                                <td class="px-4 py-3 text-xs text-zinc-500 max-w-[200px] truncate"><?= htmlspecialchars($r['memo'] ?? '') ?></td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($records)): ?>
                            <tr><td colspan="7" class="px-4 py-8 text-center text-zinc-400"><?= __('admin.staff.attendance.no_records') ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
                <?php elseif (!$staffId): ?>
                <div class="bg-white dark:bg-zinc-800 rounded-xl p-12 shadow-sm text-center">
                    <svg class="w-16 h-16 mx-auto text-zinc-300 dark:text-zinc-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <p class="text-zinc-500 dark:text-zinc-400"><?= __('admin.staff.attendance.rpt_select_staff') ?></p>
                </div>
                <?php endif; ?>
            </div>
        </main>
    </div>

    <?php if ($staffInfo && !empty($dailySummary)): ?>
    <script>
    (function() {
        var ctx = document.getElementById('dailyChart').getContext('2d');
        var isDark = document.documentElement.classList.contains('dark');
        new Chart(ctx, {
            type: 'bar',
            data: {
                labels: <?= json_encode(array_keys($dailySummary)) ?>,
                datasets: [{
                    label: '<?= __('admin.staff.attendance.work_hours') ?>',
                    data: <?= json_encode(array_values($dailySummary)) ?>,
                    backgroundColor: 'rgba(59,130,246,0.6)',
                    borderRadius: 4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                plugins: { legend: { display: false } },
                scales: {
                    y: { beginAtZero: true, grid: { color: isDark ? 'rgba(255,255,255,0.1)' : 'rgba(0,0,0,0.1)' }, ticks: { color: isDark ? '#aaa' : '#666' } },
                    x: { grid: { display: false }, ticks: { color: isDark ? '#aaa' : '#666' } }
                }
            }
        });
        console.log('[PersonalReport] Chart rendered');
    })();
    </script>
    <?php endif; ?>
</body>
</html>
