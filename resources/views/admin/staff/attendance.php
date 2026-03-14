<?php
/**
 * RezlyX Admin - 근태 현황 페이지 (오늘의 출퇴근)
 */

if (!function_exists('__')) {
    require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';
}

$pageTitle = __('staff.attendance.title') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');

// DB 연결
$staffList = [];
$todayRecords = [];
$stats = ['total' => 0, 'working' => 0, 'completed' => 0, 'absent' => 0, 'break' => 0, 'outside' => 0];

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
    $today = date('Y-m-d');

    // POST API 처리
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=utf-8');
        $action = $_POST['action'] ?? '';

        switch ($action) {
            case 'clock_in':
                $staffId = (int)($_POST['staff_id'] ?? 0);
                $memo = trim($_POST['memo'] ?? '');
                if (!$staffId) { echo json_encode(['success' => false, 'message' => 'Invalid staff']); exit; }

                // 오늘 이미 출근했는지 확인
                $chk = $pdo->prepare("SELECT id FROM {$prefix}attendance WHERE staff_id = ? AND DATE(clock_in) = ? ORDER BY clock_in DESC LIMIT 1");
                $chk->execute([$staffId, $today]);
                $existing = $chk->fetch(PDO::FETCH_ASSOC);
                if ($existing) {
                    echo json_encode(['success' => false, 'message' => __('staff.attendance.error.already_clocked_in')]);
                    exit;
                }

                $id = bin2hex(random_bytes(18));
                $now = date('Y-m-d H:i:s');
                $stmt = $pdo->prepare("INSERT INTO {$prefix}attendance (id, staff_id, clock_in, status, source, memo, created_at) VALUES (?, ?, ?, 'working', 'manual', ?, ?)");
                $stmt->execute([$id, $staffId, $now, $memo, $now]);
                echo json_encode(['success' => true, 'message' => __('staff.attendance.success.clock_in'), 'time' => $now]);
                exit;

            case 'clock_out':
                $staffId = (int)($_POST['staff_id'] ?? 0);
                $memo = trim($_POST['memo'] ?? '');
                if (!$staffId) { echo json_encode(['success' => false, 'message' => 'Invalid staff']); exit; }

                // 오늘 출근 기록 찾기 (working 또는 break 상태)
                $chk = $pdo->prepare("SELECT id, clock_in, break_minutes FROM {$prefix}attendance WHERE staff_id = ? AND DATE(clock_in) = ? AND clock_out IS NULL ORDER BY clock_in DESC LIMIT 1");
                $chk->execute([$staffId, $today]);
                $record = $chk->fetch(PDO::FETCH_ASSOC);
                if (!$record) {
                    echo json_encode(['success' => false, 'message' => __('staff.attendance.error.no_clock_in')]);
                    exit;
                }

                $now = date('Y-m-d H:i:s');
                $clockIn = new DateTime($record['clock_in']);
                $clockOut = new DateTime($now);
                $diff = $clockIn->diff($clockOut);
                $totalMinutes = ($diff->h * 60) + $diff->i;
                $breakMin = (int)($record['break_minutes'] ?? 0);
                $workMinutes = max(0, $totalMinutes - $breakMin);
                $workHours = round($workMinutes / 60, 2);

                $stmt = $pdo->prepare("UPDATE {$prefix}attendance SET clock_out = ?, work_hours = ?, status = 'completed', break_in = NULL, break_out = NULL, memo = CASE WHEN memo IS NOT NULL AND memo != '' THEN CONCAT(memo, ' | ', ?) ELSE ? END WHERE id = ?");
                $stmt->execute([$now, $workHours, $memo, $memo, $record['id']]);
                echo json_encode(['success' => true, 'message' => __('staff.attendance.success.clock_out'), 'time' => $now, 'work_hours' => $workHours, 'break_minutes' => $breakMin]);
                exit;

            case 'break_out':
                $staffId = (int)($_POST['staff_id'] ?? 0);
                if (!$staffId) { echo json_encode(['success' => false, 'message' => 'Invalid staff']); exit; }

                // 오늘 working 상태 기록 찾기
                $chk = $pdo->prepare("SELECT id FROM {$prefix}attendance WHERE staff_id = ? AND DATE(clock_in) = ? AND status = 'working' AND clock_out IS NULL ORDER BY clock_in DESC LIMIT 1");
                $chk->execute([$staffId, $today]);
                $record = $chk->fetch(PDO::FETCH_ASSOC);
                if (!$record) {
                    echo json_encode(['success' => false, 'message' => __('staff.attendance.error.not_working')]);
                    exit;
                }

                $now = date('Y-m-d H:i:s');
                $stmt = $pdo->prepare("UPDATE {$prefix}attendance SET break_out = ?, status = 'break' WHERE id = ?");
                $stmt->execute([$now, $record['id']]);
                echo json_encode(['success' => true, 'message' => __('staff.attendance.success.break_out'), 'time' => $now]);
                exit;

            case 'break_in':
                $staffId = (int)($_POST['staff_id'] ?? 0);
                if (!$staffId) { echo json_encode(['success' => false, 'message' => 'Invalid staff']); exit; }

                // 오늘 break 상태 기록 찾기
                $chk = $pdo->prepare("SELECT id, break_out, break_minutes FROM {$prefix}attendance WHERE staff_id = ? AND DATE(clock_in) = ? AND status = 'break' AND clock_out IS NULL ORDER BY clock_in DESC LIMIT 1");
                $chk->execute([$staffId, $today]);
                $record = $chk->fetch(PDO::FETCH_ASSOC);
                if (!$record) {
                    echo json_encode(['success' => false, 'message' => __('staff.attendance.error.not_on_break')]);
                    exit;
                }

                $now = date('Y-m-d H:i:s');
                $breakOutTime = new DateTime($record['break_out']);
                $breakInTime = new DateTime($now);
                $diff = $breakOutTime->diff($breakInTime);
                $thisBreakMin = ($diff->h * 60) + $diff->i;
                $totalBreakMin = (int)($record['break_minutes'] ?? 0) + $thisBreakMin;

                $stmt = $pdo->prepare("UPDATE {$prefix}attendance SET break_in = ?, break_out = NULL, break_minutes = ?, status = 'working' WHERE id = ?");
                $stmt->execute([$now, $totalBreakMin, $record['id']]);
                echo json_encode(['success' => true, 'message' => __('staff.attendance.success.break_in'), 'time' => $now, 'break_minutes' => $totalBreakMin]);
                exit;

            case 'outside_out':
                $staffId = (int)($_POST['staff_id'] ?? 0);
                if (!$staffId) { echo json_encode(['success' => false, 'message' => 'Invalid staff']); exit; }

                $chk = $pdo->prepare("SELECT id FROM {$prefix}attendance WHERE staff_id = ? AND DATE(clock_in) = ? AND status = 'working' AND clock_out IS NULL ORDER BY clock_in DESC LIMIT 1");
                $chk->execute([$staffId, $today]);
                $record = $chk->fetch(PDO::FETCH_ASSOC);
                if (!$record) {
                    echo json_encode(['success' => false, 'message' => __('staff.attendance.error.not_working')]);
                    exit;
                }

                $now = date('Y-m-d H:i:s');
                $stmt = $pdo->prepare("UPDATE {$prefix}attendance SET break_out = ?, status = 'outside' WHERE id = ?");
                $stmt->execute([$now, $record['id']]);
                echo json_encode(['success' => true, 'message' => __('staff.attendance.success.outside_out'), 'time' => $now]);
                exit;

            case 'outside_in':
                $staffId = (int)($_POST['staff_id'] ?? 0);
                if (!$staffId) { echo json_encode(['success' => false, 'message' => 'Invalid staff']); exit; }

                $chk = $pdo->prepare("SELECT id, break_out FROM {$prefix}attendance WHERE staff_id = ? AND DATE(clock_in) = ? AND status = 'outside' AND clock_out IS NULL ORDER BY clock_in DESC LIMIT 1");
                $chk->execute([$staffId, $today]);
                $record = $chk->fetch(PDO::FETCH_ASSOC);
                if (!$record) {
                    echo json_encode(['success' => false, 'message' => __('staff.attendance.error.not_outside')]);
                    exit;
                }

                $now = date('Y-m-d H:i:s');
                // 외근은 근무시간에서 차감하지 않음 (break_minutes 증가 안 함)
                $stmt = $pdo->prepare("UPDATE {$prefix}attendance SET break_in = ?, break_out = NULL, status = 'working' WHERE id = ?");
                $stmt->execute([$now, $record['id']]);
                echo json_encode(['success' => true, 'message' => __('staff.attendance.success.outside_in'), 'time' => $now]);
                exit;

            case 'card_clock':
                $cardNumber = trim($_POST['card_number'] ?? '');
                if (!$cardNumber) { echo json_encode(['success' => false, 'message' => __('staff.attendance.error.invalid_card')]); exit; }

                // 카드번호로 스태프 찾기
                $stmt = $pdo->prepare("SELECT id, name FROM {$prefix}staff WHERE card_number = ? AND is_active = 1");
                $stmt->execute([$cardNumber]);
                $staff = $stmt->fetch(PDO::FETCH_ASSOC);
                if (!$staff) {
                    echo json_encode(['success' => false, 'message' => __('staff.attendance.error.card_not_found')]);
                    exit;
                }

                // 오늘 출근 기록 확인
                $chk = $pdo->prepare("SELECT id, clock_in, clock_out, status, break_minutes FROM {$prefix}attendance WHERE staff_id = ? AND DATE(clock_in) = ? ORDER BY clock_in DESC LIMIT 1");
                $chk->execute([$staff['id'], $today]);
                $record = $chk->fetch(PDO::FETCH_ASSOC);

                $now = date('Y-m-d H:i:s');
                if (!$record) {
                    // 출근 처리
                    $id = bin2hex(random_bytes(18));
                    $ins = $pdo->prepare("INSERT INTO {$prefix}attendance (id, staff_id, clock_in, status, source, created_at) VALUES (?, ?, ?, 'working', 'card', ?)");
                    $ins->execute([$id, $staff['id'], $now, $now]);
                    echo json_encode(['success' => true, 'type' => 'clock_in', 'staff_name' => $staff['name'], 'message' => __('staff.attendance.success.clock_in'), 'time' => $now]);
                } elseif (!$record['clock_out'] && !in_array($record['status'], ['break', 'outside'])) {
                    // 퇴근 처리
                    $clockIn = new DateTime($record['clock_in']);
                    $clockOut = new DateTime($now);
                    $diff = $clockIn->diff($clockOut);
                    $totalMinutes = ($diff->h * 60) + $diff->i;
                    $breakMin = (int)($record['break_minutes'] ?? 0);
                    $workHours = round(max(0, $totalMinutes - $breakMin) / 60, 2);
                    $upd = $pdo->prepare("UPDATE {$prefix}attendance SET clock_out = ?, work_hours = ?, status = 'completed' WHERE id = ?");
                    $upd->execute([$now, $workHours, $record['id']]);
                    echo json_encode(['success' => true, 'type' => 'clock_out', 'staff_name' => $staff['name'], 'message' => __('staff.attendance.success.clock_out'), 'time' => $now, 'work_hours' => $workHours]);
                } elseif ($record['status'] === 'break') {
                    // 외출중이면 복귀 처리 (근무시간 차감)
                    $breakOutTime = new DateTime($record['break_out'] ?? $now);
                    $breakInTime = new DateTime($now);
                    $diff = $breakOutTime->diff($breakInTime);
                    $thisBreakMin = ($diff->h * 60) + $diff->i;
                    $totalBreakMin = (int)($record['break_minutes'] ?? 0) + $thisBreakMin;
                    $upd = $pdo->prepare("UPDATE {$prefix}attendance SET break_in = ?, break_out = NULL, break_minutes = ?, status = 'working' WHERE id = ?");
                    $upd->execute([$now, $totalBreakMin, $record['id']]);
                    echo json_encode(['success' => true, 'type' => 'break_in', 'staff_name' => $staff['name'], 'message' => __('staff.attendance.success.break_in'), 'time' => $now]);
                } elseif ($record['status'] === 'outside') {
                    // 외근중이면 복귀 처리 (근무시간 차감 안 함)
                    $upd = $pdo->prepare("UPDATE {$prefix}attendance SET break_in = ?, break_out = NULL, status = 'working' WHERE id = ?");
                    $upd->execute([$now, $record['id']]);
                    echo json_encode(['success' => true, 'type' => 'outside_in', 'staff_name' => $staff['name'], 'message' => __('staff.attendance.success.outside_in'), 'time' => $now]);
                } else {
                    echo json_encode(['success' => false, 'message' => __('staff.attendance.error.already_completed')]);
                }
                exit;
        }
    }

    // 활성 스태프 목록
    $staffList = $pdo->query("SELECT id, name, email, avatar, card_number FROM {$prefix}staff WHERE is_active = 1 ORDER BY sort_order ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);
    $stats['total'] = count($staffList);

    // 오늘 근태 기록
    $stmt = $pdo->prepare("SELECT a.*, s.name as staff_name, s.avatar as staff_avatar FROM {$prefix}attendance a JOIN {$prefix}staff s ON a.staff_id = s.id WHERE DATE(a.clock_in) = ? ORDER BY a.clock_in ASC");
    $stmt->execute([$today]);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // staff_id 기준으로 매핑
    foreach ($records as $r) {
        $todayRecords[$r['staff_id']] = $r;
        if ($r['status'] === 'working') $stats['working']++;
        elseif ($r['status'] === 'break') $stats['break']++;
        elseif ($r['status'] === 'outside') $stats['outside']++;
        elseif ($r['status'] === 'completed') $stats['completed']++;
    }
    $stats['absent'] = $stats['total'] - $stats['working'] - $stats['break'] - $stats['outside'] - $stats['completed'];

    $dbConnected = true;
} catch (PDOException $e) {
    $dbConnected = false;
    $dbError = $e->getMessage();
}
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
                <!-- 알림 -->
                <div id="alertBox" class="mb-6 p-4 rounded-lg border hidden"></div>

                <!-- 헤더 + 탭 -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= __('staff.attendance.title') ?></h1>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1"><?= __('staff.attendance.description') ?> — <?= date('Y-m-d (D)') ?></p>
                    </div>
                    <div class="flex gap-2">
                        <a href="<?= $adminUrl ?>/staff/attendance/history" class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-lg transition-colors">
                            <?= __('staff.attendance.tab_history') ?>
                        </a>
                        <a href="<?= $adminUrl ?>/staff/attendance/dashboard" class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-lg transition-colors">
                            <?= __('staff.attendance.tab_dashboard') ?>
                        </a>
                        <a href="<?= $adminUrl ?>/staff/attendance/report" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors flex items-center gap-1.5">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 17v-2m3 2v-4m3 4v-6m2 10H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                            <?= __('staff.attendance.report_title') ?>
                        </a>
                        <a href="<?= $adminUrl ?>/staff/attendance/kiosk" target="_blank" class="px-4 py-2 text-sm font-medium text-white bg-purple-600 hover:bg-purple-700 rounded-lg transition-colors">
                            <?= __('staff.attendance.kiosk_mode') ?>
                        </a>
                    </div>
                </div>

                <!-- 통계 카드 -->
                <div class="grid grid-cols-3 md:grid-cols-6 gap-4 mb-6">
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 shadow-sm">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('staff.attendance.stats.total') ?></p>
                        <p class="text-2xl font-bold text-zinc-900 dark:text-white"><?= $stats['total'] ?></p>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 shadow-sm">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('staff.attendance.stats.working') ?></p>
                        <p class="text-2xl font-bold text-green-600"><?= $stats['working'] ?></p>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 shadow-sm">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('staff.attendance.stats.on_break') ?></p>
                        <p class="text-2xl font-bold text-amber-600"><?= $stats['break'] ?></p>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 shadow-sm">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('staff.attendance.stats.on_outside') ?></p>
                        <p class="text-2xl font-bold text-indigo-600"><?= $stats['outside'] ?></p>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 shadow-sm">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('staff.attendance.stats.completed') ?></p>
                        <p class="text-2xl font-bold text-blue-600"><?= $stats['completed'] ?></p>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 shadow-sm">
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('staff.attendance.stats.absent') ?></p>
                        <p class="text-2xl font-bold text-red-600"><?= $stats['absent'] ?></p>
                    </div>
                </div>

                <!-- 스태프 출퇴근 카드 목록 -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($staffList as $staff):
                        $record = $todayRecords[$staff['id']] ?? null;
                        $status = $record ? $record['status'] : 'absent';
                        $statusColors = [
                            'working'    => 'border-green-400 bg-green-50 dark:bg-green-900/20',
                            'break'      => 'border-amber-400 bg-amber-50 dark:bg-amber-900/20',
                            'outside'    => 'border-indigo-400 bg-indigo-50 dark:bg-indigo-900/20',
                            'completed'  => 'border-zinc-300 bg-zinc-50 dark:bg-zinc-800/50 dark:border-zinc-700',
                            'absent'     => 'border-red-300 bg-red-50 dark:bg-red-900/20 dark:border-red-800',
                            'late'       => 'border-orange-300 bg-orange-50 dark:bg-orange-900/20',
                            'early_leave'=> 'border-yellow-300 bg-yellow-50 dark:bg-yellow-900/20',
                        ];
                        $cardClass = $statusColors[$status] ?? $statusColors['absent'];
                    ?>
                    <div id="staff-card-<?= $staff['id'] ?>" class="rounded-xl border-2 p-4 <?= $cardClass ?> transition-all">
                        <div class="flex items-center justify-between mb-3">
                            <div class="flex items-center gap-3">
                                <div class="w-10 h-10 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center text-zinc-500 dark:text-zinc-400 font-semibold text-sm overflow-hidden">
                                    <?php if ($staff['avatar']): ?>
                                        <img src="<?= htmlspecialchars($staff['avatar']) ?>" class="w-full h-full object-cover">
                                    <?php else: ?>
                                        <?= mb_substr($staff['name'], 0, 1) ?>
                                    <?php endif; ?>
                                </div>
                                <div>
                                    <p class="font-semibold text-zinc-900 dark:text-white text-sm"><?= htmlspecialchars($staff['name']) ?></p>
                                    <?php if ($staff['card_number']): ?>
                                        <p class="text-xs text-purple-500"><svg class="w-3 h-3 inline -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg> <?= __('staff.attendance.card_registered') ?></p>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <!-- 상태 배지 -->
                            <span id="status-badge-<?= $staff['id'] ?>" class="px-2 py-1 text-xs font-medium rounded-full <?php
                                switch ($status) {
                                    case 'working': echo 'bg-green-100 text-green-700 dark:bg-green-800/50 dark:text-green-300'; break;
                                    case 'break': echo 'bg-amber-100 text-amber-700 dark:bg-amber-800/50 dark:text-amber-300'; break;
                                    case 'outside': echo 'bg-indigo-100 text-indigo-700 dark:bg-indigo-800/50 dark:text-indigo-300'; break;
                                    case 'completed': echo 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300'; break;
                                    case 'late': echo 'bg-orange-100 text-orange-700 dark:bg-orange-800/50 dark:text-orange-300'; break;
                                    case 'early_leave': echo 'bg-yellow-100 text-yellow-700 dark:bg-yellow-800/50 dark:text-yellow-300'; break;
                                    default: echo 'bg-red-100 text-red-700 dark:bg-red-800/50 dark:text-red-300';
                                }
                            ?>">
                                <?= __('staff.attendance.status.' . $status) ?>
                            </span>
                        </div>

                        <!-- 시간 정보 -->
                        <div class="text-xs text-zinc-600 dark:text-zinc-400 mb-3 space-y-1">
                            <div class="flex justify-between">
                                <span><?= __('staff.attendance.clock_in') ?>:</span>
                                <span id="clockin-<?= $staff['id'] ?>" class="font-mono"><?= $record ? date('H:i', strtotime($record['clock_in'])) : '--:--' ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span><?= __('staff.attendance.clock_out') ?>:</span>
                                <span id="clockout-<?= $staff['id'] ?>" class="font-mono"><?= ($record && $record['clock_out']) ? date('H:i', strtotime($record['clock_out'])) : '--:--' ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span><?= __('staff.attendance.break_time') ?>:</span>
                                <span id="breaktime-<?= $staff['id'] ?>" class="font-mono"><?php
                                    $bm = ($record && isset($record['break_minutes'])) ? (int)$record['break_minutes'] : 0;
                                    echo $bm > 0 ? $bm . 'min' : '-';
                                ?></span>
                            </div>
                            <div class="flex justify-between">
                                <span><?= __('staff.attendance.work_hours') ?>:</span>
                                <span id="workhours-<?= $staff['id'] ?>" class="font-mono"><?= ($record && $record['work_hours']) ? $record['work_hours'] . 'h' : '-' ?></span>
                            </div>
                        </div>

                        <!-- 액션 버튼 -->
                        <div class="flex gap-2 flex-wrap">
                            <?php if (!$record): ?>
                                <button onclick="clockIn(<?= $staff['id'] ?>)" class="flex-1 px-3 py-2 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors">
                                    <?= __('staff.attendance.btn_clock_in') ?>
                                </button>
                            <?php elseif ($status === 'working'): ?>
                                <button onclick="breakOut(<?= $staff['id'] ?>)" class="flex-1 min-w-0 px-2 py-2 text-xs font-medium text-white bg-amber-500 hover:bg-amber-600 rounded-lg transition-colors">
                                    <?= __('staff.attendance.btn_break_out') ?>
                                </button>
                                <button onclick="outsideOut(<?= $staff['id'] ?>)" class="flex-1 min-w-0 px-2 py-2 text-xs font-medium text-white bg-indigo-500 hover:bg-indigo-600 rounded-lg transition-colors">
                                    <?= __('staff.attendance.btn_outside_out') ?>
                                </button>
                                <button onclick="clockOut(<?= $staff['id'] ?>)" class="flex-1 min-w-0 px-2 py-2 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                                    <?= __('staff.attendance.btn_clock_out') ?>
                                </button>
                            <?php elseif ($status === 'break'): ?>
                                <button onclick="breakIn(<?= $staff['id'] ?>)" class="flex-1 px-3 py-2 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors">
                                    <?= __('staff.attendance.btn_break_in') ?>
                                </button>
                                <button onclick="clockOut(<?= $staff['id'] ?>)" class="flex-1 px-3 py-2 text-xs font-medium text-zinc-600 bg-zinc-200 hover:bg-zinc-300 dark:text-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 rounded-lg transition-colors">
                                    <?= __('staff.attendance.btn_clock_out') ?>
                                </button>
                            <?php elseif ($status === 'outside'): ?>
                                <button onclick="outsideIn(<?= $staff['id'] ?>)" class="flex-1 px-3 py-2 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors">
                                    <?= __('staff.attendance.btn_outside_in') ?>
                                </button>
                                <button onclick="clockOut(<?= $staff['id'] ?>)" class="flex-1 px-3 py-2 text-xs font-medium text-zinc-600 bg-zinc-200 hover:bg-zinc-300 dark:text-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 rounded-lg transition-colors">
                                    <?= __('staff.attendance.btn_clock_out') ?>
                                </button>
                            <?php else: ?>
                                <span class="flex-1 px-3 py-2 text-xs text-center text-zinc-400"><?= __('staff.attendance.completed_today') ?></span>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <?php if (empty($staffList)): ?>
                    <div class="col-span-3 text-center py-12 text-zinc-400">
                        <?= __('staff.attendance.no_staff') ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/attendance-js.php'; ?>
</body>
</html>
