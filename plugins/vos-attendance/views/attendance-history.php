<?php
/**
 * RezlyX Admin - 근태 기록 조회 페이지
 */

if (!function_exists('__')) {
    require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';
}

$pageTitle = __('staff.attendance.history_title') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');

// 필터
$filterStaff = (int)($_GET['staff_id'] ?? 0);
$filterDateFrom = $_GET['date_from'] ?? date('Y-m-01');
$filterDateTo = $_GET['date_to'] ?? date('Y-m-d');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

$records = [];
$staffList = [];
$totalRecords = 0;

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

    // POST API (수정/삭제)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=utf-8');
        $action = $_POST['action'] ?? '';

        if ($action === 'update_record') {
            $id = trim($_POST['id'] ?? '');
            $clockIn = trim($_POST['clock_in'] ?? '');
            $clockOut = trim($_POST['clock_out'] ?? '') ?: null;
            $status = trim($_POST['status'] ?? 'working');
            $memo = trim($_POST['memo'] ?? '');

            $workHours = null;
            if ($clockIn && $clockOut) {
                $d1 = new DateTime($clockIn);
                $d2 = new DateTime($clockOut);
                $diff = $d1->diff($d2);
                $workHours = round($diff->h + ($diff->i / 60), 2);
            }

            $stmt = $pdo->prepare("UPDATE {$prefix}attendance SET clock_in = ?, clock_out = ?, work_hours = ?, status = ?, memo = ? WHERE id = ?");
            $stmt->execute([$clockIn, $clockOut, $workHours, $status, $memo, $id]);
            echo json_encode(['success' => true, 'message' => __('staff.attendance.success.updated')]);
            exit;
        }

        if ($action === 'delete_record') {
            $id = trim($_POST['id'] ?? '');
            $stmt = $pdo->prepare("DELETE FROM {$prefix}attendance WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => __('staff.attendance.success.deleted')]);
            exit;
        }

        if ($action === 'add_record') {
            $staffId = (int)($_POST['staff_id'] ?? 0);
            $clockIn = trim($_POST['clock_in'] ?? '');
            $clockOut = trim($_POST['clock_out'] ?? '') ?: null;
            $status = trim($_POST['status'] ?? 'completed');
            $memo = trim($_POST['memo'] ?? '');
            $source = 'manual';

            $workHours = null;
            if ($clockIn && $clockOut) {
                $d1 = new DateTime($clockIn);
                $d2 = new DateTime($clockOut);
                $diff = $d1->diff($d2);
                $workHours = round($diff->h + ($diff->i / 60), 2);
            }

            $id = bin2hex(random_bytes(18));
            $stmt = $pdo->prepare("INSERT INTO {$prefix}attendance (id, staff_id, clock_in, clock_out, work_hours, status, source, memo, created_at) VALUES (?, ?, ?, ?, ?, ?, ?, ?, NOW())");
            $stmt->execute([$id, $staffId, $clockIn, $clockOut, $workHours, $status, $source, $memo]);
            echo json_encode(['success' => true, 'message' => __('staff.attendance.success.added')]);
            exit;
        }
    }

    // 스태프 목록
    $staffList = $pdo->query("SELECT id, name FROM {$prefix}staff ORDER BY sort_order ASC, name ASC")->fetchAll(PDO::FETCH_ASSOC);

    // 기록 조회 (필터 적용)
    $where = "WHERE DATE(a.clock_in) BETWEEN ? AND ?";
    $params = [$filterDateFrom, $filterDateTo];
    if ($filterStaff) {
        $where .= " AND a.staff_id = ?";
        $params[] = $filterStaff;
    }

    // 총 개수
    $countSql = "SELECT COUNT(*) FROM {$prefix}attendance a {$where}";
    $stmt = $pdo->prepare($countSql);
    $stmt->execute($params);
    $totalRecords = (int)$stmt->fetchColumn();
    $totalPages = max(1, ceil($totalRecords / $perPage));
    $offset = ($page - 1) * $perPage;

    // 데이터
    $sql = "SELECT a.*, s.name as staff_name FROM {$prefix}attendance a JOIN {$prefix}staff s ON a.staff_id = s.id {$where} ORDER BY a.clock_in DESC LIMIT {$perPage} OFFSET {$offset}";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $records = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $dbConnected = true;
} catch (PDOException $e) {
    $dbConnected = false;
    $dbError = $e->getMessage();
}

$statusLabels = [
    'working'     => __('staff.attendance.status.working'),
    'completed'   => __('staff.attendance.status.completed'),
    'absent'      => __('staff.attendance.status.absent'),
    'late'        => __('staff.attendance.status.late'),
    'early_leave' => __('staff.attendance.status.early_leave'),
    'break'       => __('staff.attendance.status.break'),
    'outside'     => __('staff.attendance.status.outside'),
];
$pageHeaderTitle = __('staff.attendance.history_title');
$pageSubTitle = __('staff.attendance.history_title');
$pageSubDesc = __('staff.attendance.history_desc');
?>
<?php include BASE_PATH . '/resources/views/admin/reservations/_head.php'; ?>
                <div id="alertBox" class="mb-6 p-4 rounded-lg border hidden"></div>

                <!-- 탭/액션 버튼 -->
                <div class="flex justify-end gap-2 mb-6">
                    <a href="<?= $adminUrl ?>/staff/attendance" class="px-4 py-2 text-sm font-medium text-zinc-700 dark:text-zinc-300 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-lg transition-colors">
                        <?= __('staff.attendance.tab_today') ?>
                    </a>
                    <button onclick="openAddModal()" class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                        + <?= __('staff.attendance.add_record') ?>
                    </button>
                </div>

                <!-- 필터 -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 shadow-sm mb-6">
                    <form method="GET" class="flex items-end gap-4">
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
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-zinc-700 hover:bg-zinc-800 dark:bg-zinc-600 dark:hover:bg-zinc-500 rounded-lg transition-colors">
                            <?= __('staff.attendance.search') ?>
                        </button>
                    </form>
                </div>

                <!-- 테이블 -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm overflow-hidden">
                    <table class="w-full text-sm">
                        <thead class="bg-zinc-50 dark:bg-zinc-700/50">
                            <tr>
                                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400"><?= __('staff.attendance.col_date') ?></th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400"><?= __('staff.attendance.col_staff') ?></th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400"><?= __('staff.attendance.clock_in') ?></th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400"><?= __('staff.attendance.clock_out') ?></th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400"><?= __('staff.attendance.work_hours') ?></th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400"><?= __('staff.attendance.col_status') ?></th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400"><?= __('staff.attendance.col_source') ?></th>
                                <th class="px-4 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400"><?= __('staff.attendance.col_memo') ?></th>
                                <th class="px-4 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400"><?= __('staff.attendance.col_actions') ?></th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
                            <?php foreach ($records as $r): ?>
                            <tr id="record-<?= $r['id'] ?>" class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                                <td class="px-4 py-3 text-zinc-900 dark:text-white font-mono text-xs"><?= date('Y-m-d', strtotime($r['clock_in'])) ?></td>
                                <td class="px-4 py-3 text-zinc-900 dark:text-white"><?= htmlspecialchars($r['staff_name']) ?></td>
                                <td class="px-4 py-3 font-mono text-xs"><?= date('H:i', strtotime($r['clock_in'])) ?></td>
                                <td class="px-4 py-3 font-mono text-xs"><?= $r['clock_out'] ? date('H:i', strtotime($r['clock_out'])) : '-' ?></td>
                                <td class="px-4 py-3 font-mono text-xs"><?= $r['work_hours'] ? $r['work_hours'] . 'h' : '-' ?></td>
                                <td class="px-4 py-3">
                                    <?php
                                    $sc = ['working'=>'bg-green-100 text-green-700','completed'=>'bg-zinc-100 text-zinc-600','absent'=>'bg-red-100 text-red-700','late'=>'bg-orange-100 text-orange-700','early_leave'=>'bg-yellow-100 text-yellow-700','break'=>'bg-amber-100 text-amber-700','outside'=>'bg-indigo-100 text-indigo-700'];
                                    $cls = $sc[$r['status']] ?? $sc['absent'];
                                    ?>
                                    <span class="px-2 py-0.5 text-xs font-medium rounded-full <?= $cls ?>"><?= $statusLabels[$r['status']] ?? $r['status'] ?></span>
                                </td>
                                <td class="px-4 py-3 text-xs"><?= $r['source'] === 'card' ? '🪪' : '✏️' ?></td>
                                <td class="px-4 py-3 text-xs text-zinc-500 max-w-[150px] truncate"><?= htmlspecialchars($r['memo'] ?? '') ?></td>
                                <td class="px-4 py-3 text-right">
                                    <button onclick='editRecord(<?= json_encode($r) ?>)' class="text-blue-600 hover:text-blue-800 text-xs mr-2"><?= __('common.buttons.edit') ?></button>
                                    <button onclick="deleteRecord('<?= $r['id'] ?>')" class="text-red-600 hover:text-red-800 text-xs"><?= __('common.buttons.delete') ?></button>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                            <?php if (empty($records)): ?>
                            <tr><td colspan="9" class="px-4 py-8 text-center text-zinc-400"><?= __('staff.attendance.no_records') ?></td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>

                    <!-- 페이지네이션 -->
                    <?php if ($totalPages > 1): ?>
                    <div class="px-4 py-3 border-t border-zinc-100 dark:border-zinc-700 flex items-center justify-between">
                        <p class="text-xs text-zinc-500"><?= __('staff.attendance.total_records', ['count' => $totalRecords]) ?></p>
                        <div class="flex gap-1">
                            <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                            <a href="?staff_id=<?= $filterStaff ?>&date_from=<?= $filterDateFrom ?>&date_to=<?= $filterDateTo ?>&page=<?= $i ?>"
                               class="px-3 py-1 text-xs rounded <?= $i === $page ? 'bg-blue-600 text-white' : 'text-zinc-600 hover:bg-zinc-100 dark:text-zinc-400 dark:hover:bg-zinc-700' ?>"><?= $i ?></a>
                            <?php endfor; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

    <!-- 추가/수정 모달 -->
    <div id="recordModal" class="fixed inset-0 z-50 hidden">
        <div class="fixed inset-0 bg-black/50 backdrop-blur-sm" onclick="closeRecordModal()"></div>
        <div class="fixed inset-0 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl w-full max-w-md relative">
                <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                    <h2 id="recordModalTitle" class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('staff.attendance.add_record') ?></h2>
                    <button onclick="closeRecordModal()" class="p-1.5 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700">
                        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <form id="recordForm" class="p-6 space-y-4">
                    <input type="hidden" id="recordId" name="id" value="">
                    <input type="hidden" id="recordAction" name="action" value="add_record">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('staff.attendance.col_staff') ?> *</label>
                        <select id="recordStaff" name="staff_id" required class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm">
                            <?php foreach ($staffList as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="grid grid-cols-2 gap-3">
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('staff.attendance.clock_in') ?> *</label>
                            <input type="datetime-local" id="recordClockIn" name="clock_in" required class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm">
                        </div>
                        <div>
                            <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('staff.attendance.clock_out') ?></label>
                            <input type="datetime-local" id="recordClockOut" name="clock_out" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm">
                        </div>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('staff.attendance.col_status') ?></label>
                        <select id="recordStatus" name="status" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm">
                            <?php foreach ($statusLabels as $k => $v): ?>
                            <option value="<?= $k ?>"><?= $v ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('staff.attendance.col_memo') ?></label>
                        <input type="text" id="recordMemo" name="memo" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm">
                    </div>
                </form>
                <div class="px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex justify-end gap-3">
                    <button onclick="closeRecordModal()" class="px-4 py-2 text-sm text-zinc-700 dark:text-zinc-300 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg"><?= __('common.buttons.cancel') ?></button>
                    <button onclick="saveRecord()" class="px-4 py-2 text-sm text-white bg-blue-600 hover:bg-blue-700 rounded-lg"><?= __('common.buttons.save') ?></button>
                </div>
            </div>
        </div>
    </div>

    <script>
    (function() {
        'use strict';
        function showAlert(msg, type) {
            var box = document.getElementById('alertBox');
            box.className = 'mb-6 p-4 rounded-lg border ' + (type === 'success' ? 'bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-300 border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-300 border-red-200 dark:border-red-800');
            box.textContent = msg; box.classList.remove('hidden');
            setTimeout(function() { box.classList.add('hidden'); }, 4000);
            console.log('[AttendanceHistory] Alert:', type, msg);
        }
        function postData(fd) { return fetch(window.location.pathname, { method: 'POST', body: fd }).then(function(r) { return r.json(); }); }

        window.openAddModal = function() {
            document.getElementById('recordId').value = '';
            document.getElementById('recordAction').value = 'add_record';
            document.getElementById('recordModalTitle').textContent = '<?= __('staff.attendance.add_record') ?>';
            document.getElementById('recordForm').reset();
            document.getElementById('recordModal').classList.remove('hidden');
            console.log('[AttendanceHistory] Add modal opened');
        };
        window.editRecord = function(r) {
            document.getElementById('recordId').value = r.id;
            document.getElementById('recordAction').value = 'update_record';
            document.getElementById('recordModalTitle').textContent = '<?= __('staff.attendance.edit_record') ?>';
            document.getElementById('recordStaff').value = r.staff_id;
            document.getElementById('recordClockIn').value = r.clock_in ? r.clock_in.replace(' ', 'T').substring(0, 16) : '';
            document.getElementById('recordClockOut').value = r.clock_out ? r.clock_out.replace(' ', 'T').substring(0, 16) : '';
            document.getElementById('recordStatus').value = r.status;
            document.getElementById('recordMemo').value = r.memo || '';
            document.getElementById('recordModal').classList.remove('hidden');
            console.log('[AttendanceHistory] Edit modal:', r.id);
        };
        window.closeRecordModal = function() {
            document.getElementById('recordModal').classList.add('hidden');
            console.log('[AttendanceHistory] Modal closed');
        };
        window.saveRecord = function() {
            var form = document.getElementById('recordForm');
            var fd = new FormData(form);
            console.log('[AttendanceHistory] Saving:', fd.get('action'), fd.get('id'));
            postData(fd).then(function(data) {
                if (data.success) { showAlert(data.message, 'success'); closeRecordModal(); setTimeout(function() { location.reload(); }, 800); }
                else { showAlert(data.message || 'Error', 'error'); }
            }).catch(function(e) { console.error('[AttendanceHistory] Save error:', e); showAlert('<?= __('staff.attendance.error.server') ?>', 'error'); });
        };
        window.deleteRecord = function(id) {
            if (!confirm('<?= __('staff.attendance.confirm_delete') ?>')) return;
            console.log('[AttendanceHistory] Deleting:', id);
            var fd = new FormData(); fd.append('action', 'delete_record'); fd.append('id', id);
            postData(fd).then(function(data) {
                if (data.success) { showAlert(data.message, 'success'); var row = document.getElementById('record-' + id); if (row) row.remove(); }
                else { showAlert(data.message || 'Error', 'error'); }
            }).catch(function(e) { console.error('[AttendanceHistory] Delete error:', e); showAlert('<?= __('staff.attendance.error.server') ?>', 'error'); });
        };
        console.log('[AttendanceHistory] Page initialized');
    })();
    </script>
</main>
</div>
</body>
</html>
