<?php
/**
 * RezlyX Admin - 근태 리포트 (기간별 요약 + CSV 다운로드)
 */

if (!function_exists('__')) {
    require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';
}

$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');

$filterDateFrom = $_GET['date_from'] ?? date('Y-m-01');
$filterDateTo = $_GET['date_to'] ?? date('Y-m-d');
$filterStaff = (int)($_GET['staff_id'] ?? 0);

$staffList = [];
$reportData = [];
$summary = ['total_days' => 0, 'total_hours' => 0, 'avg_hours' => 0, 'late' => 0, 'early_leave' => 0, 'break_min' => 0, 'outside' => 0];

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

    $staffList = $pdo->query("SELECT id, name FROM {$prefix}staff WHERE is_active = 1 ORDER BY sort_order ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);

    // CSV 다운로드 처리
    if (isset($_GET['export']) && $_GET['export'] === 'csv') {
        $where = "WHERE DATE(a.clock_in) BETWEEN ? AND ?";
        $params = [$filterDateFrom, $filterDateTo];
        if ($filterStaff) { $where .= " AND a.staff_id = ?"; $params[] = $filterStaff; }

        $sql = "SELECT s.name as staff_name, DATE(a.clock_in) as work_date,
                    TIME(a.clock_in) as in_time, TIME(a.clock_out) as out_time,
                    a.work_hours, a.break_minutes, a.status, a.source, a.memo
                FROM {$prefix}attendance a
                JOIN {$prefix}staff s ON a.staff_id = s.id
                {$where} ORDER BY a.clock_in ASC";
        $stmt = $pdo->prepare($sql);
        $stmt->execute($params);
        $rows = $stmt->fetchAll(PDO::FETCH_ASSOC);

        $statusMap = [
            'working' => __('staff.attendance.status.working'),
            'completed' => __('staff.attendance.status.completed'),
            'absent' => __('staff.attendance.status.absent'),
            'late' => __('staff.attendance.status.late'),
            'early_leave' => __('staff.attendance.status.early_leave'),
            'break' => __('staff.attendance.status.break'),
            'outside' => __('staff.attendance.status.outside'),
        ];

        header('Content-Type: text/csv; charset=utf-8');
        header('Content-Disposition: attachment; filename="attendance_report_' . $filterDateFrom . '_' . $filterDateTo . '.csv"');
        $out = fopen('php://output', 'w');
        fwrite($out, "\xEF\xBB\xBF"); // UTF-8 BOM
        fputcsv($out, [
            __('staff.attendance.col_staff'),
            __('staff.attendance.col_date'),
            __('staff.attendance.clock_in'),
            __('staff.attendance.clock_out'),
            __('staff.attendance.work_hours'),
            __('staff.attendance.break_time'),
            __('staff.attendance.col_status'),
            __('staff.attendance.col_source'),
            __('staff.attendance.col_memo'),
        ]);
        foreach ($rows as $r) {
            $bmVal = (int)$r['break_minutes'];
            $breakTime = $bmVal > 0 ? floor($bmVal / 60) . 'h ' . ($bmVal % 60) . 'm' : '';
            fputcsv($out, [
                $r['staff_name'], $r['work_date'], $r['in_time'], $r['out_time'] ?? '',
                $r['work_hours'] ?? '', $breakTime, $statusMap[$r['status']] ?? $r['status'],
                $r['source'], $r['memo'] ?? ''
            ]);
        }
        fclose($out);
        exit;
    }

    // 스태프별 기간 요약 데이터
    $sql = "SELECT s.id, s.name, s.avatar,
                COUNT(a.id) as work_days,
                SUM(CASE WHEN a.work_hours IS NOT NULL THEN a.work_hours ELSE 0 END) as total_hours,
                ROUND(AVG(CASE WHEN a.work_hours IS NOT NULL THEN a.work_hours ELSE NULL END), 1) as avg_hours,
                SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN a.status = 'early_leave' THEN 1 ELSE 0 END) as early_count,
                SUM(CASE WHEN a.break_minutes > 0 THEN a.break_minutes ELSE 0 END) as break_minutes,
                SUM(CASE WHEN a.status = 'outside' THEN 1 ELSE 0 END) as outside_count
            FROM {$prefix}staff s
            LEFT JOIN {$prefix}attendance a ON s.id = a.staff_id AND DATE(a.clock_in) BETWEEN ? AND ?
            WHERE s.is_active = 1" . ($filterStaff ? " AND s.id = ?" : "") . "
            GROUP BY s.id
            ORDER BY work_days DESC, s.name ASC";

    $rParams = [$filterDateFrom, $filterDateTo];
    if ($filterStaff) $rParams[] = $filterStaff;
    $stmt = $pdo->prepare($sql);
    $stmt->execute($rParams);
    $reportData = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 전체 요약
    foreach ($reportData as $r) {
        $summary['total_days'] += (int)$r['work_days'];
        $summary['total_hours'] += (float)$r['total_hours'];
        $summary['late'] += (int)$r['late_count'];
        $summary['early_leave'] += (int)$r['early_count'];
        $summary['break_min'] += (int)$r['break_minutes'];
        $summary['outside'] += (int)$r['outside_count'];
    }
    $summary['avg_hours'] = count($reportData) > 0 ? round($summary['total_hours'] / max(1, $summary['total_days']), 1) : 0;

    $dbConnected = true;
} catch (PDOException $e) {
    $dbConnected = false;
    $dbError = $e->getMessage();
}

$pageTitle = __('staff.attendance.report_title') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
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
                        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= __('staff.attendance.report_title') ?></h1>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1"><?= __('staff.attendance.report_desc') ?></p>
                    </div>
                    <div class="flex gap-2">
                        <a href="<?= $adminUrl ?>/staff/attendance" class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-lg transition-colors"><?= __('staff.attendance.tab_today') ?></a>
                        <a href="<?= $adminUrl ?>/staff/attendance/report/stats" class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-lg transition-colors"><?= __('staff.attendance.tab_stats') ?></a>
                    </div>
                </div>

                <!-- 필터 -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 shadow-sm mb-6">
                    <form method="GET" class="flex items-end gap-4 flex-wrap">
                        <div>
                            <label class="block text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('staff.attendance.filter_staff') ?></label>
                            <select name="staff_id" class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm">
                                <option value=""><?= __('staff.attendance.all_staff') ?></option>
                                <?php foreach ($staffList as $s): ?>
                                <option value="<?= $s['id'] ?>" <?= $filterStaff == $s['id'] ? 'selected' : '' ?>><?= htmlspecialchars($s['name']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div>
                            <label class="block text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('staff.attendance.filter_from') ?></label>
                            <input type="date" name="date_from" value="<?= $filterDateFrom ?>" class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm">
                        </div>
                        <div>
                            <label class="block text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('staff.attendance.filter_to') ?></label>
                            <input type="date" name="date_to" value="<?= $filterDateTo ?>" class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm">
                        </div>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-zinc-700 hover:bg-zinc-800 dark:bg-zinc-600 dark:hover:bg-zinc-500 rounded-lg transition-colors"><?= __('staff.attendance.search') ?></button>
                        <a href="?staff_id=<?= $filterStaff ?>&date_from=<?= $filterDateFrom ?>&date_to=<?= $filterDateTo ?>&export=csv"
                           class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors flex items-center gap-1">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 10v6m0 0l-3-3m3 3l3-3m2 8H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <?= __('staff.attendance.export_csv') ?>
                        </a>
                    </form>
                </div>

                <!-- 요약 카드 -->
                <div class="grid grid-cols-3 md:grid-cols-6 gap-4 mb-6">
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 shadow-sm">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('staff.attendance.rpt_total_days') ?></p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-white"><?= $summary['total_days'] ?></p>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 shadow-sm">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('staff.attendance.rpt_total_hours') ?></p>
                        <p class="text-2xl font-bold text-blue-600"><?= round($summary['total_hours'], 1) ?>h</p>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 shadow-sm">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('staff.attendance.dash_avg_hours') ?></p>
                        <p class="text-2xl font-bold text-cyan-600"><?= $summary['avg_hours'] ?>h</p>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 shadow-sm">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('staff.attendance.dash_late') ?></p>
                        <p class="text-2xl font-bold text-orange-600"><?= $summary['late'] ?></p>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 shadow-sm">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('staff.attendance.dash_early_leave') ?></p>
                        <p class="text-2xl font-bold text-yellow-600"><?= $summary['early_leave'] ?></p>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 shadow-sm">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('staff.attendance.break_time') ?></p>
                        <?php $bkm = $summary['break_min']; $bkStr = $bkm > 0 ? floor($bkm / 60) . 'h ' . ($bkm % 60) . 'm' : '0'; ?>
                        <p class="text-2xl font-bold text-amber-600"><?= $bkStr ?></p>
                    </div>
                </div>

                <!-- 스태프별 요약 테이블 -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('staff.attendance.rpt_staff_summary') ?> (<?= $filterDateFrom ?> ~ <?= $filterDateTo ?>)</h2>
                    </div>
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-50 dark:bg-zinc-700/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400"><?= __('staff.attendance.col_staff') ?></th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400"><?= __('staff.attendance.dash_work_days') ?></th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400"><?= __('staff.attendance.dash_total_hours') ?></th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400"><?= __('staff.attendance.dash_avg_hours') ?></th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400"><?= __('staff.attendance.dash_late') ?></th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400"><?= __('staff.attendance.dash_early_leave') ?></th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400"><?= __('staff.attendance.break_time') ?></th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400"><?= __('staff.attendance.rpt_outside') ?></th>
                                <th class="px-4 py-3 text-center text-xs font-medium text-zinc-500 dark:text-zinc-400"></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                            <?php foreach ($reportData as $s): ?>
                            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                                <td class="px-4 py-3">
                                    <div class="flex items-center gap-2">
                                        <div class="w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center text-xs font-semibold text-zinc-500 overflow-hidden">
                                            <?php if ($s['avatar']): ?><img src="<?= htmlspecialchars($s['avatar']) ?>" class="w-full h-full object-cover"><?php else: ?><?= mb_substr($s['name'], 0, 1) ?><?php endif; ?>
                                        </div>
                                        <span class="text-zinc-900 dark:text-white font-medium"><?= htmlspecialchars($s['name']) ?></span>
                                    </div>
                                </td>
                                <td class="px-4 py-3 text-center font-mono"><?= (int)$s['work_days'] ?></td>
                                <td class="px-4 py-3 text-center font-mono"><?= round((float)$s['total_hours'], 1) ?>h</td>
                                <td class="px-4 py-3 text-center font-mono"><?= $s['avg_hours'] ?? '0' ?>h</td>
                                <td class="px-4 py-3 text-center"><?php if ((int)$s['late_count'] > 0): ?><span class="px-2 py-0.5 text-xs font-medium rounded-full bg-orange-100 text-orange-700"><?= (int)$s['late_count'] ?></span><?php else: ?><span class="text-zinc-300">0</span><?php endif; ?></td>
                                <td class="px-4 py-3 text-center"><?php if ((int)$s['early_count'] > 0): ?><span class="px-2 py-0.5 text-xs font-medium rounded-full bg-yellow-100 text-yellow-700"><?= (int)$s['early_count'] ?></span><?php else: ?><span class="text-zinc-300">0</span><?php endif; ?></td>
                                <td class="px-4 py-3 text-center font-mono text-xs"><?php $bm = (int)($s['break_minutes'] ?? 0); if ($bm > 0): ?><span class="text-amber-600"><?= floor($bm / 60) ?>h <?= $bm % 60 ?>m</span><?php else: ?><span class="text-zinc-300">-</span><?php endif; ?></td>
                                <td class="px-4 py-3 text-center"><?php if ((int)$s['outside_count'] > 0): ?><span class="px-2 py-0.5 text-xs font-medium rounded-full bg-indigo-100 text-indigo-700"><?= (int)$s['outside_count'] ?></span><?php else: ?><span class="text-zinc-300">0</span><?php endif; ?></td>
                                <td class="px-4 py-3 text-center">
                                    <a href="<?= $adminUrl ?>/staff/attendance/report/personal/<?= $s['id'] ?>?date_from=<?= $filterDateFrom ?>&date_to=<?= $filterDateTo ?>"
                                       class="text-blue-600 hover:text-blue-800 text-xs font-medium"><?= __('staff.attendance.rpt_detail') ?></a>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($reportData)): ?>
                            <tr><td colspan="9" class="px-4 py-8 text-center text-zinc-400"><?= __('staff.attendance.no_staff') ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </main>
    </div>
</body>
</html>
