<?php
/**
 * RezlyX Admin - 근태 대시보드 (월간 통계)
 */

if (!function_exists('__')) {
    require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';
}

$pageTitle = __('staff.attendance.dashboard_title') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');

$filterMonth = $_GET['month'] ?? date('Y-m');
$monthStart = $filterMonth . '-01';
$monthEnd = date('Y-m-t', strtotime($monthStart));

$stats = ['attendance_rate' => 0, 'avg_hours' => 0, 'late_count' => 0, 'early_leave_count' => 0, 'break_count' => 0, 'outside_count' => 0];
$staffSummary = [];

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

    // 활성 스태프 수
    $totalStaff = (int)$pdo->query("SELECT COUNT(*) FROM {$prefix}staff WHERE is_active = 1")->fetchColumn();

    // 이번 달 영업일 (오늘까지, 주말 제외 간단 계산)
    $daysInMonth = 0;
    $checkEnd = min($monthEnd, date('Y-m-d'));
    $d = new DateTime($monthStart);
    $end = new DateTime($checkEnd);
    while ($d <= $end) {
        $dow = (int)$d->format('N');
        if ($dow <= 5) $daysInMonth++; // 월~금
        $d->modify('+1 day');
    }
    $expectedTotal = $totalStaff * max(1, $daysInMonth);

    // 월간 출근 건수
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}attendance WHERE DATE(clock_in) BETWEEN ? AND ?");
    $stmt->execute([$monthStart, $checkEnd]);
    $totalAttendance = (int)$stmt->fetchColumn();
    $stats['attendance_rate'] = $expectedTotal > 0 ? round(($totalAttendance / $expectedTotal) * 100, 1) : 0;

    // 평균 근무시간
    $stmt = $pdo->prepare("SELECT AVG(work_hours) FROM {$prefix}attendance WHERE DATE(clock_in) BETWEEN ? AND ? AND work_hours IS NOT NULL");
    $stmt->execute([$monthStart, $checkEnd]);
    $stats['avg_hours'] = round((float)$stmt->fetchColumn(), 1);

    // 지각/조퇴/외출/외근 건수
    $stmt = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM {$prefix}attendance WHERE DATE(clock_in) BETWEEN ? AND ? AND status IN ('late','early_leave','break','outside') GROUP BY status");
    $stmt->execute([$monthStart, $checkEnd]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        if ($row['status'] === 'late') $stats['late_count'] = (int)$row['cnt'];
        if ($row['status'] === 'early_leave') $stats['early_leave_count'] = (int)$row['cnt'];
        if ($row['status'] === 'break') $stats['break_count'] = (int)$row['cnt'];
        if ($row['status'] === 'outside') $stats['outside_count'] = (int)$row['cnt'];
    }

    // 외출 총 시간 (break_minutes 합산)
    $stmt = $pdo->prepare("SELECT SUM(break_minutes) FROM {$prefix}attendance WHERE DATE(clock_in) BETWEEN ? AND ? AND break_minutes > 0");
    $stmt->execute([$monthStart, $checkEnd]);
    $stats['total_break_minutes'] = (int)$stmt->fetchColumn();

    // 스태프별 월간 요약
    $sql = "SELECT s.id, s.name, s.avatar,
                COUNT(a.id) as work_days,
                SUM(CASE WHEN a.work_hours IS NOT NULL THEN a.work_hours ELSE 0 END) as total_hours,
                ROUND(AVG(CASE WHEN a.work_hours IS NOT NULL THEN a.work_hours ELSE NULL END), 1) as avg_hours,
                SUM(CASE WHEN a.status = 'late' THEN 1 ELSE 0 END) as late_count,
                SUM(CASE WHEN a.status = 'early_leave' THEN 1 ELSE 0 END) as early_count,
                SUM(CASE WHEN a.break_minutes > 0 THEN a.break_minutes ELSE 0 END) as break_minutes,
                SUM(CASE WHEN a.status = 'outside' OR a.break_out IS NOT NULL THEN 1 ELSE 0 END) as outside_count
            FROM {$prefix}staff s
            LEFT JOIN {$prefix}attendance a ON s.id = a.staff_id AND DATE(a.clock_in) BETWEEN ? AND ?
            WHERE s.is_active = 1
            GROUP BY s.id
            ORDER BY work_days DESC, s.name ASC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$monthStart, $checkEnd]);
    $staffSummary = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $dbConnected = true;
} catch (PDOException $e) {
    $dbConnected = false;
    $dbError = $e->getMessage();
}
$pageHeaderTitle = __('staff.attendance.dashboard_title');
$pageSubTitle = __('staff.attendance.dashboard_title');
$pageSubDesc = __('staff.attendance.dashboard_desc');
?>
<?php include __DIR__ . '/../reservations/_head.php'; ?>
                <!-- 탭/필터 -->
                <div class="flex justify-end gap-2 items-center mb-6">
                    <a href="<?= $adminUrl ?>/staff/attendance" class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-lg transition-colors">
                        <?= __('staff.attendance.tab_today') ?>
                    </a>
                    <form method="GET" class="flex gap-2">
                        <input type="month" name="month" value="<?= $filterMonth ?>" class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm">
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-zinc-700 hover:bg-zinc-800 dark:bg-zinc-600 dark:hover:bg-zinc-500 rounded-lg"><?= __('staff.attendance.search') ?></button>
                    </form>
                </div>

                <!-- 통계 카드 -->
                <div class="grid grid-cols-3 md:grid-cols-6 gap-4 mb-6">
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-5 shadow-sm">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('staff.attendance.dash_rate') ?></p>
                        <p class="text-3xl font-bold text-zinc-900 dark:text-white"><?= $stats['attendance_rate'] ?><span class="text-lg">%</span></p>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-5 shadow-sm">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('staff.attendance.dash_avg_hours') ?></p>
                        <p class="text-3xl font-bold text-blue-600"><?= $stats['avg_hours'] ?><span class="text-lg">h</span></p>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-5 shadow-sm">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('staff.attendance.dash_late') ?></p>
                        <p class="text-3xl font-bold text-orange-600"><?= $stats['late_count'] ?></p>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-5 shadow-sm">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('staff.attendance.dash_early_leave') ?></p>
                        <p class="text-3xl font-bold text-yellow-600"><?= $stats['early_leave_count'] ?></p>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-5 shadow-sm">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('staff.attendance.stats.on_break') ?></p>
                        <p class="text-3xl font-bold text-amber-600"><?= $stats['break_count'] ?></p>
                        <?php if ($stats['total_break_minutes'] > 0): ?>
                        <p class="text-xs text-zinc-400 mt-1"><?= floor($stats['total_break_minutes'] / 60) ?>h <?= $stats['total_break_minutes'] % 60 ?>m</p>
                        <?php endif; ?>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-5 shadow-sm">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('staff.attendance.stats.on_outside') ?></p>
                        <p class="text-3xl font-bold text-indigo-600"><?= $stats['outside_count'] ?></p>
                    </div>
                </div>

                <!-- 스태프별 월간 요약 -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm overflow-hidden">
                    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                        <h2 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('staff.attendance.staff_summary') ?> — <?= $filterMonth ?></h2>
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
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                            <?php foreach ($staffSummary as $s): ?>
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
                                <td class="px-4 py-3 text-center">
                                    <?php if ((int)$s['late_count'] > 0): ?>
                                        <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-orange-100 text-orange-700"><?= (int)$s['late_count'] ?></span>
                                    <?php else: ?>
                                        <span class="text-zinc-300">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-center">
                                    <?php if ((int)$s['early_count'] > 0): ?>
                                        <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-yellow-100 text-yellow-700"><?= (int)$s['early_count'] ?></span>
                                    <?php else: ?>
                                        <span class="text-zinc-300">0</span>
                                    <?php endif; ?>
                                </td>
                                <td class="px-4 py-3 text-center font-mono text-xs">
                                    <?php $bm = (int)($s['break_minutes'] ?? 0); ?>
                                    <?php if ($bm > 0): ?>
                                        <span class="text-amber-600"><?= floor($bm / 60) ?>h <?= $bm % 60 ?>m</span>
                                    <?php else: ?>
                                        <span class="text-zinc-300">-</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($staffSummary)): ?>
                            <tr><td colspan="7" class="px-4 py-8 text-center text-zinc-400"><?= __('staff.attendance.no_staff') ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
    </main>
</div>
</body>
</html>
