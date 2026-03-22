<?php
/**
 * RezlyX Admin - 스태프 스케줄 관리 페이지
 */

if (!function_exists('__')) {
    require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';
}

$pageTitle = __('staff.schedule.title') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');

// DB 연결
$staffList = [];
$settings = [];

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

    // 설정 로드
    $stmt = $pdo->query("SELECT `key`, `value` FROM {$prefix}settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['key']] = $row['value'];
    }

    // 스케줄 기능 비활성 → 리다이렉트
    if (($settings['staff_schedule_enabled'] ?? '0') !== '1') {
        header('Location: ' . $adminUrl . '/staff/settings');
        exit;
    }

    // 스태프 목록
    $staffList = $pdo->query("SELECT id, name, name_i18n FROM {$prefix}staff WHERE is_active = 1 ORDER BY sort_order ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);

    // POST 처리 (AJAX)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=utf-8');
        $action = $_POST['action'] ?? '';

        // 스태프 스케줄 조회
        if ($action === 'get_schedule') {
            $staffId = (int)($_POST['staff_id'] ?? 0);
            if (!$staffId) {
                echo json_encode(['success' => false, 'message' => 'Invalid staff ID']);
                exit;
            }

            // 주간 스케줄
            $stmt = $pdo->prepare("SELECT day_of_week, is_working, start_time, end_time, break_start, break_end FROM {$prefix}staff_schedules WHERE staff_id = ? ORDER BY day_of_week ASC");
            $stmt->execute([$staffId]);
            $weekly = [];
            while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
                $weekly[$row['day_of_week']] = $row;
            }

            // 오버라이드
            $stmt = $pdo->prepare("SELECT id, override_date, is_working, start_time, end_time, break_start, break_end, memo FROM {$prefix}staff_schedule_overrides WHERE staff_id = ? ORDER BY override_date ASC");
            $stmt->execute([$staffId]);
            $overrides = $stmt->fetchAll(PDO::FETCH_ASSOC);

            // 기본 영업시간 (폴백 표시용)
            $bhStmt = $pdo->query("SELECT day_of_week, is_open, open_time, close_time, break_start, break_end FROM {$prefix}business_hours ORDER BY day_of_week ASC");
            $businessHours = [];
            while ($bh = $bhStmt->fetch(PDO::FETCH_ASSOC)) {
                $businessHours[$bh['day_of_week']] = $bh;
            }

            echo json_encode(['success' => true, 'weekly' => $weekly, 'overrides' => $overrides, 'business_hours' => $businessHours]);
            exit;
        }

        // 주간 스케줄 저장
        if ($action === 'save_weekly') {
            $staffId = (int)($_POST['staff_id'] ?? 0);
            if (!$staffId) {
                echo json_encode(['success' => false, 'message' => 'Invalid staff ID']);
                exit;
            }

            $days = json_decode($_POST['days'] ?? '[]', true);
            if (!is_array($days)) {
                echo json_encode(['success' => false, 'message' => 'Invalid data']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO {$prefix}staff_schedules (staff_id, day_of_week, is_working, start_time, end_time, break_start, break_end)
                VALUES (?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE is_working = VALUES(is_working), start_time = VALUES(start_time), end_time = VALUES(end_time), break_start = VALUES(break_start), break_end = VALUES(break_end)");

            foreach ($days as $day) {
                $dow = (int)($day['day_of_week'] ?? -1);
                if ($dow < 0 || $dow > 6) continue;
                $stmt->execute([
                    $staffId,
                    $dow,
                    $day['is_working'] ? 1 : 0,
                    $day['start_time'] ?: null,
                    $day['end_time'] ?: null,
                    $day['break_start'] ?: null,
                    $day['break_end'] ?: null,
                ]);
            }

            echo json_encode(['success' => true, 'message' => __('staff.schedule.saved')]);
            exit;
        }

        // 오버라이드 저장
        if ($action === 'save_override') {
            $staffId = (int)($_POST['staff_id'] ?? 0);
            $overrideDate = trim($_POST['override_date'] ?? '');
            if (!$staffId || !$overrideDate) {
                echo json_encode(['success' => false, 'message' => 'Invalid data']);
                exit;
            }

            $stmt = $pdo->prepare("INSERT INTO {$prefix}staff_schedule_overrides (staff_id, override_date, is_working, start_time, end_time, break_start, break_end, memo)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?)
                ON DUPLICATE KEY UPDATE is_working = VALUES(is_working), start_time = VALUES(start_time), end_time = VALUES(end_time), break_start = VALUES(break_start), break_end = VALUES(break_end), memo = VALUES(memo)");

            $stmt->execute([
                $staffId,
                $overrideDate,
                ($_POST['is_working'] ?? '0') ? 1 : 0,
                trim($_POST['start_time'] ?? '') ?: null,
                trim($_POST['end_time'] ?? '') ?: null,
                trim($_POST['break_start'] ?? '') ?: null,
                trim($_POST['break_end'] ?? '') ?: null,
                trim($_POST['memo'] ?? '') ?: null,
            ]);

            echo json_encode(['success' => true, 'message' => __('staff.schedule.override_saved')]);
            exit;
        }

        // 오버라이드 삭제
        if ($action === 'delete_override') {
            $id = (int)($_POST['id'] ?? 0);
            if ($id) {
                $pdo->prepare("DELETE FROM {$prefix}staff_schedule_overrides WHERE id = ?")->execute([$id]);
                echo json_encode(['success' => true, 'message' => __('staff.schedule.override_deleted')]);
            } else {
                echo json_encode(['success' => false, 'message' => 'Invalid ID']);
            }
            exit;
        }

        echo json_encode(['success' => false, 'message' => 'Unknown action']);
        exit;
    }

    $dbConnected = true;
} catch (PDOException $e) {
    $dbConnected = false;
    $dbError = $e->getMessage();
}

$dayNames = [
    __('staff.schedule.day_sun'),
    __('staff.schedule.day_mon'),
    __('staff.schedule.day_tue'),
    __('staff.schedule.day_wed'),
    __('staff.schedule.day_thu'),
    __('staff.schedule.day_fri'),
    __('staff.schedule.day_sat'),
];
$pageHeaderTitle = __('staff.schedule.title');
$pageSubTitle = __('staff.schedule.title');
$pageSubDesc = __('staff.schedule.description');
?>
<?php include __DIR__ . '/../reservations/_head.php'; ?>
                <!-- 알림 -->
                <div id="alertBox" class="mb-6 p-4 rounded-lg border hidden"></div>


                <!-- 스태프 선택 -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-6 mb-6">
                    <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('staff.schedule.select_staff') ?></label>
                    <select id="staffSelect" class="w-full max-w-sm px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500">
                        <option value="">-- <?= __('staff.schedule.select_staff') ?> --</option>
                        <?php foreach ($staffList as $st): ?>
                        <option value="<?= $st['id'] ?>"><?= htmlspecialchars($st['name']) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- 스케줄 영역 (스태프 선택 후 표시) -->
                <div id="scheduleArea" class="hidden space-y-6">

                    <!-- 주간 근무 스케줄 -->
                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('staff.schedule.weekly_title') ?></h2>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-zinc-50 dark:bg-zinc-700/50 text-zinc-600 dark:text-zinc-300">
                                        <th class="px-4 py-3 text-left font-medium w-24"><?= __('booking.date_label') ?></th>
                                        <th class="px-4 py-3 text-center font-medium w-20"><?= __('staff.schedule.working') ?></th>
                                        <th class="px-4 py-3 text-center font-medium"><?= __('staff.schedule.start_time') ?></th>
                                        <th class="px-4 py-3 text-center font-medium"><?= __('staff.schedule.end_time') ?></th>
                                        <th class="px-4 py-3 text-center font-medium"><?= __('staff.schedule.break_start') ?></th>
                                        <th class="px-4 py-3 text-center font-medium"><?= __('staff.schedule.break_end') ?></th>
                                    </tr>
                                </thead>
                                <tbody id="weeklyBody">
                                    <?php for ($d = 0; $d < 7; $d++): ?>
                                    <tr class="border-t border-zinc-100 dark:border-zinc-700" data-dow="<?= $d ?>">
                                        <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white"><?= $dayNames[$d] ?></td>
                                        <td class="px-4 py-3 text-center">
                                            <label class="relative inline-flex items-center cursor-pointer">
                                                <input type="checkbox" class="sr-only peer weekly-working" data-dow="<?= $d ?>">
                                                <div class="w-9 h-5 bg-zinc-200 peer-focus:ring-2 peer-focus:ring-blue-500 dark:bg-zinc-600 rounded-full peer peer-checked:after:translate-x-full peer-checked:bg-green-500 after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all"></div>
                                            </label>
                                        </td>
                                        <td class="px-4 py-3 text-center"><input type="time" class="weekly-start px-2 py-1 border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-xs" data-dow="<?= $d ?>"></td>
                                        <td class="px-4 py-3 text-center"><input type="time" class="weekly-end px-2 py-1 border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-xs" data-dow="<?= $d ?>"></td>
                                        <td class="px-4 py-3 text-center"><input type="time" class="weekly-bstart px-2 py-1 border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-xs" data-dow="<?= $d ?>"></td>
                                        <td class="px-4 py-3 text-center"><input type="time" class="weekly-bend px-2 py-1 border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-xs" data-dow="<?= $d ?>"></td>
                                    </tr>
                                    <?php endfor; ?>
                                </tbody>
                            </table>
                        </div>
                        <div class="px-6 py-4 bg-zinc-50 dark:bg-zinc-700/30 border-t border-zinc-200 dark:border-zinc-700 flex justify-end">
                            <button type="button" id="btnSaveWeekly" class="px-5 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition">
                                <?= __('staff.schedule.save_weekly') ?>
                            </button>
                        </div>
                    </div>

                    <!-- 날짜별 특별 설정 -->
                    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm overflow-hidden">
                        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                            <h2 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('staff.schedule.override_title') ?></h2>
                            <button type="button" id="btnAddOverride" class="px-3 py-1.5 text-xs font-medium text-blue-600 border border-blue-300 hover:bg-blue-50 dark:border-blue-700 dark:hover:bg-blue-900/20 rounded-lg transition flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                                <?= __('staff.schedule.add_override') ?>
                            </button>
                        </div>

                        <!-- 오버라이드 추가 폼 (숨김) -->
                        <div id="overrideForm" class="hidden px-6 py-4 bg-blue-50 dark:bg-blue-900/10 border-b border-blue-200 dark:border-blue-800">
                            <div class="grid grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3 items-end">
                                <div>
                                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1"><?= __('staff.schedule.override_date') ?></label>
                                    <input type="date" id="ovDate" class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-xs">
                                </div>
                                <div>
                                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1"><?= __('staff.schedule.override_status') ?></label>
                                    <select id="ovWorking" class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-xs">
                                        <option value="0"><?= __('staff.schedule.day_off') ?></option>
                                        <option value="1"><?= __('staff.schedule.working') ?></option>
                                    </select>
                                </div>
                                <div><label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1"><?= __('staff.schedule.start_time') ?></label><input type="time" id="ovStart" class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-xs"></div>
                                <div><label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1"><?= __('staff.schedule.end_time') ?></label><input type="time" id="ovEnd" class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-xs"></div>
                                <div><label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1"><?= __('staff.schedule.break_start') ?></label><input type="time" id="ovBStart" class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-xs"></div>
                                <div><label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1"><?= __('staff.schedule.break_end') ?></label><input type="time" id="ovBEnd" class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-xs"></div>
                                <div class="col-span-2 md:col-span-1">
                                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1"><?= __('staff.schedule.override_memo') ?></label>
                                    <input type="text" id="ovMemo" class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 rounded bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-xs" placeholder="<?= __('staff.schedule.override_memo') ?>">
                                </div>
                            </div>
                            <div class="mt-3 flex gap-2">
                                <button type="button" id="btnSaveOverride" class="px-4 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition"><?= __('admin.common.save') ?></button>
                                <button type="button" id="btnCancelOverride" class="px-4 py-1.5 text-xs font-medium text-zinc-600 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 rounded-lg transition"><?= __('common.buttons.cancel') ?></button>
                            </div>
                        </div>

                        <!-- 오버라이드 목록 -->
                        <div id="overrideList" class="divide-y divide-zinc-100 dark:divide-zinc-700">
                            <div class="px-6 py-8 text-center text-sm text-zinc-400"><?= __('staff.schedule.no_overrides') ?></div>
                        </div>
                    </div>
                </div>
            </div>

            <?php include __DIR__ . '/schedule-js.php'; ?>
    </main>
</div>
</body>
</html>
