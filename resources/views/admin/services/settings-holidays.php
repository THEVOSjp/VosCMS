<?php
/**
 * 서비스 설정 - 공휴일 관리 탭
 * 공휴일/휴무일 등록 및 관리
 */

// POST 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'];

    try {
        // 테이블 존재 확인 및 생성
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `{$prefix}holidays` (
                `id` INT AUTO_INCREMENT PRIMARY KEY,
                `title` VARCHAR(100) NOT NULL,
                `holiday_date` DATE NOT NULL,
                `repeat_yearly` TINYINT(1) DEFAULT 0,
                `is_active` TINYINT(1) DEFAULT 1,
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                UNIQUE KEY `uk_date` (`holiday_date`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");

        switch ($action) {
            case 'create_holiday':
                $title = trim($_POST['title'] ?? '');
                $date = trim($_POST['holiday_date'] ?? '');
                $repeatYearly = isset($_POST['repeat_yearly']) ? 1 : 0;

                if (empty($title) || empty($date)) {
                    echo json_encode(['success' => false, 'message' => __('admin.services.settings.holidays.required')]);
                    exit;
                }

                $stmt = $pdo->prepare("INSERT INTO {$prefix}holidays (title, holiday_date, repeat_yearly) VALUES (?, ?, ?)");
                $stmt->execute([$title, $date, $repeatYearly]);
                echo json_encode(['success' => true, 'message' => __('admin.services.settings.holidays.created')]);
                exit;

            case 'delete_holiday':
                $id = (int)$_POST['id'];
                $pdo->prepare("DELETE FROM {$prefix}holidays WHERE id = ?")->execute([$id]);
                echo json_encode(['success' => true, 'message' => __('admin.services.settings.holidays.deleted')]);
                exit;

            case 'toggle_holiday':
                $id = (int)$_POST['id'];
                $pdo->prepare("UPDATE {$prefix}holidays SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
                echo json_encode(['success' => true, 'message' => 'OK']);
                exit;
        }
    } catch (PDOException $e) {
        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
        exit;
    }
}

// 공휴일 테이블 생성 (없으면)
try {
    $pdo->exec("
        CREATE TABLE IF NOT EXISTS `{$prefix}holidays` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `title` VARCHAR(100) NOT NULL,
            `holiday_date` DATE NOT NULL,
            `repeat_yearly` TINYINT(1) DEFAULT 0,
            `is_active` TINYINT(1) DEFAULT 1,
            `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uk_date` (`holiday_date`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");
    $holidays = $pdo->query("SELECT * FROM {$prefix}holidays ORDER BY holiday_date ASC")->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $holidays = [];
}

$holidaysApiUrl = $adminUrl . '/services/settings/holidays';
?>

<div id="alertBox" class="hidden mb-6 p-4 rounded-lg border"></div>

<!-- 공휴일 추가 폼 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm mb-6">
    <div class="p-6">
        <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">
            <?= __('admin.services.settings.holidays.add_title') ?>
        </h3>
        <div class="flex flex-col sm:flex-row gap-3">
            <input type="text" id="holidayTitle" placeholder="<?= __('admin.services.settings.holidays.placeholder_title') ?>"
                   class="flex-1 px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 text-sm">
            <input type="date" id="holidayDate"
                   class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white focus:ring-2 focus:ring-blue-500 text-sm">
            <label class="flex items-center gap-2 text-sm text-zinc-700 dark:text-zinc-300 whitespace-nowrap">
                <input type="checkbox" id="holidayRepeat" class="rounded border-zinc-300 dark:border-zinc-600">
                <?= __('admin.services.settings.holidays.repeat_yearly') ?>
            </label>
            <button onclick="addHoliday()"
                    class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors whitespace-nowrap">
                <?= __('admin.common.add') ?>
            </button>
        </div>
    </div>
</div>

<!-- 공휴일 목록 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm overflow-hidden">
    <?php if (empty($holidays)): ?>
    <div class="p-12 text-center text-zinc-500 dark:text-zinc-400">
        <svg class="w-12 h-12 mx-auto mb-3 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
        </svg>
        <p><?= __('admin.services.settings.holidays.empty') ?></p>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-700/50">
                <tr>
                    <th class="text-left px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300"><?= __('admin.services.settings.holidays.name') ?></th>
                    <th class="text-left px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300"><?= __('admin.services.settings.holidays.date') ?></th>
                    <th class="text-center px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300"><?= __('admin.services.settings.holidays.repeat_yearly') ?></th>
                    <th class="text-center px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300"><?= __('admin.services.settings.holidays.status') ?></th>
                    <th class="text-center px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300"><?= __('admin.services.actions') ?></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                <?php foreach ($holidays as $h): ?>
                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                    <td class="px-4 py-3 font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($h['title']) ?></td>
                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400"><?= $h['holiday_date'] ?></td>
                    <td class="px-4 py-3 text-center">
                        <?php if ($h['repeat_yearly']): ?>
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300"><?= __('admin.services.settings.holidays.yearly') ?></span>
                        <?php else: ?>
                        <span class="text-zinc-400">-</span>
                        <?php endif; ?>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <button onclick="toggleHoliday(<?= $h['id'] ?>, this)"
                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors <?= $h['is_active'] ? 'bg-green-500' : 'bg-zinc-300 dark:bg-zinc-600' ?>">
                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform <?= $h['is_active'] ? 'translate-x-6' : 'translate-x-1' ?>"></span>
                        </button>
                    </td>
                    <td class="px-4 py-3 text-center">
                        <button onclick="deleteHoliday(<?= $h['id'] ?>)"
                                class="p-1.5 text-zinc-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                        </button>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<script>
var holidaysApiUrl = '<?= $holidaysApiUrl ?>';

function addHoliday() {
    var title = document.getElementById('holidayTitle').value.trim();
    var date = document.getElementById('holidayDate').value;
    var repeat = document.getElementById('holidayRepeat').checked;

    if (!title || !date) {
        showAlert('<?= __('admin.services.settings.holidays.required') ?>', 'error');
        return;
    }

    var fd = new FormData();
    fd.append('action', 'create_holiday');
    fd.append('title', title);
    fd.append('holiday_date', date);
    if (repeat) fd.append('repeat_yearly', '1');

    fetch(holidaysApiUrl, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showAlert(data.message, 'error');
            }
        });
}

function deleteHoliday(id) {
    if (!confirm('<?= __('admin.services.settings.holidays.confirm_delete') ?>')) return;
    var fd = new FormData();
    fd.append('action', 'delete_holiday');
    fd.append('id', id);
    fetch(holidaysApiUrl, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(() => location.reload(), 800);
            } else {
                showAlert(data.message, 'error');
            }
        });
}

function toggleHoliday(id, btn) {
    var fd = new FormData();
    fd.append('action', 'toggle_holiday');
    fd.append('id', id);
    fetch(holidaysApiUrl, { method: 'POST', body: fd })
        .then(r => r.json())
        .then(data => {
            if (data.success) {
                var isActive = btn.classList.contains('bg-zinc-300') || btn.classList.contains('dark:bg-zinc-600');
                btn.className = 'relative inline-flex h-6 w-11 items-center rounded-full transition-colors ' + (isActive ? 'bg-green-500' : 'bg-zinc-300 dark:bg-zinc-600');
                btn.querySelector('span').className = 'inline-block h-4 w-4 transform rounded-full bg-white transition-transform ' + (isActive ? 'translate-x-6' : 'translate-x-1');
            }
        });
}

function showAlert(msg, type) {
    var box = document.getElementById('alertBox');
    box.className = 'mb-6 p-4 rounded-lg border ' + (type === 'success'
        ? 'bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-300 border-green-200 dark:border-green-800'
        : 'bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-300 border-red-200 dark:border-red-800');
    box.textContent = msg;
    box.classList.remove('hidden');
    setTimeout(() => box.classList.add('hidden'), 5000);
}
</script>
