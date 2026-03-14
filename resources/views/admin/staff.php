<?php
/**
 * RezlyX Admin - 스태프(디자이너) 관리 페이지
 */

if (!function_exists('__')) {
    require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';
}

$pageTitle = __('staff.title') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');

// DB 연결
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

    // ─── API 요청 처리 ───
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        header('Content-Type: application/json; charset=utf-8');
        $action = $_POST['action'];

        try {
            switch ($action) {
                case 'search_members':
                    $q = trim($_POST['q'] ?? '');
                    if (mb_strlen($q) < 1) {
                        echo json_encode(['success' => true, 'members' => []]);
                        exit;
                    }
                    $like = "%{$q}%";
                    $stmt = $pdo->prepare("SELECT id, name, email, phone FROM {$prefix}users WHERE (name LIKE ? OR email LIKE ? OR phone LIKE ?) AND status = 'active' ORDER BY name ASC LIMIT 10");
                    $stmt->execute([$like, $like, $like]);
                    $members = $stmt->fetchAll(PDO::FETCH_ASSOC);
                    echo json_encode(['success' => true, 'members' => $members]);
                    exit;

                case 'create_staff':
                    $name = trim($_POST['name'] ?? '');
                    $email = trim($_POST['email'] ?? '');
                    $phone = trim($_POST['phone'] ?? '');
                    $bio = trim($_POST['bio'] ?? '');
                    $isActive = isset($_POST['is_active']) ? 1 : 0;
                    $userId = trim($_POST['user_id'] ?? '') ?: null;

                    if (empty($name)) {
                        echo json_encode(['success' => false, 'message' => __('staff.error.name_required')]);
                        exit;
                    }

                    // 중복 연동 체크
                    if ($userId) {
                        $chk = $pdo->prepare("SELECT id FROM {$prefix}staff WHERE user_id = ?");
                        $chk->execute([$userId]);
                        if ($chk->fetch()) {
                            echo json_encode(['success' => false, 'message' => __('staff.error.already_linked')]);
                            exit;
                        }
                    }

                    $cardNumber = trim($_POST['card_number'] ?? '') ?: null;

                    $maxSort = $pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM {$prefix}staff")->fetchColumn();
                    $stmt = $pdo->prepare("INSERT INTO {$prefix}staff (user_id, card_number, name, email, phone, bio, is_active, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$userId, $cardNumber, $name, $email ?: null, $phone ?: null, $bio ?: null, $isActive, $maxSort]);
                    $newId = $pdo->lastInsertId();

                    // 담당 서비스 저장
                    if (!empty($_POST['service_ids'])) {
                        $svcStmt = $pdo->prepare("INSERT INTO {$prefix}staff_services (staff_id, service_id) VALUES (?, ?)");
                        foreach ($_POST['service_ids'] as $svcId) {
                            $svcStmt->execute([$newId, $svcId]);
                        }
                    }

                    echo json_encode(['success' => true, 'message' => __('staff.success.created'), 'id' => $newId]);
                    exit;

                case 'update_staff':
                    $id = (int)$_POST['id'];
                    $name = trim($_POST['name'] ?? '');
                    $email = trim($_POST['email'] ?? '');
                    $phone = trim($_POST['phone'] ?? '');
                    $bio = trim($_POST['bio'] ?? '');
                    $isActive = isset($_POST['is_active']) ? 1 : 0;
                    $userId = trim($_POST['user_id'] ?? '') ?: null;

                    if (empty($name)) {
                        echo json_encode(['success' => false, 'message' => __('staff.error.name_required')]);
                        exit;
                    }

                    // 중복 연동 체크 (자기 자신 제외)
                    if ($userId) {
                        $chk = $pdo->prepare("SELECT id FROM {$prefix}staff WHERE user_id = ? AND id != ?");
                        $chk->execute([$userId, $id]);
                        if ($chk->fetch()) {
                            echo json_encode(['success' => false, 'message' => __('staff.error.already_linked')]);
                            exit;
                        }
                    }

                    $cardNumber = trim($_POST['card_number'] ?? '') ?: null;

                    $stmt = $pdo->prepare("UPDATE {$prefix}staff SET user_id=?, card_number=?, name=?, email=?, phone=?, bio=?, is_active=? WHERE id=?");
                    $stmt->execute([$userId, $cardNumber, $name, $email ?: null, $phone ?: null, $bio ?: null, $isActive, $id]);

                    // 담당 서비스 갱신
                    $pdo->prepare("DELETE FROM {$prefix}staff_services WHERE staff_id = ?")->execute([$id]);
                    if (!empty($_POST['service_ids'])) {
                        $svcStmt = $pdo->prepare("INSERT INTO {$prefix}staff_services (staff_id, service_id) VALUES (?, ?)");
                        foreach ($_POST['service_ids'] as $svcId) {
                            $svcStmt->execute([$id, $svcId]);
                        }
                    }

                    echo json_encode(['success' => true, 'message' => __('staff.success.updated')]);
                    exit;

                case 'delete_staff':
                    $id = (int)$_POST['id'];
                    $pdo->prepare("DELETE FROM {$prefix}staff_services WHERE staff_id = ?")->execute([$id]);
                    $pdo->prepare("DELETE FROM {$prefix}staff WHERE id = ?")->execute([$id]);
                    echo json_encode(['success' => true, 'message' => __('staff.success.deleted')]);
                    exit;

                case 'toggle_staff':
                    $id = (int)$_POST['id'];
                    $pdo->prepare("UPDATE {$prefix}staff SET is_active = NOT is_active WHERE id = ?")->execute([$id]);
                    echo json_encode(['success' => true]);
                    exit;

                default:
                    echo json_encode(['success' => false, 'message' => 'Unknown action']);
                    exit;
            }
        } catch (PDOException $e) {
            echo json_encode(['success' => false, 'message' => $e->getMessage()]);
            exit;
        }
    }

    // ─── 데이터 로드 ───
    $staffList = $pdo->query("SELECT * FROM {$prefix}staff ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
    $allServices = $pdo->query("SELECT id, name FROM {$prefix}services WHERE is_active = 1 ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);

    // 스태프별 담당 서비스 로드
    $staffServices = [];
    $ssRows = $pdo->query("SELECT staff_id, service_id FROM {$prefix}staff_services")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($ssRows as $r) {
        $staffServices[$r['staff_id']][] = $r['service_id'];
    }

    $totalStaff = count($staffList);
    $activeStaff = 0;
    foreach ($staffList as $s) { if ($s['is_active']) $activeStaff++; }

    $dbConnected = true;
} catch (PDOException $e) {
    $dbConnected = false;
    $dbError = $e->getMessage();
    $staffList = [];
    $allServices = [];
    $staffServices = [];
    $totalStaff = $activeStaff = 0;
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
        <?php include __DIR__ . '/partials/admin-sidebar.php'; ?>

        <main class="flex-1 ml-64">
            <?php include __DIR__ . '/partials/admin-topbar.php'; ?>

            <div class="p-8">
                <div id="alertBox" class="hidden mb-6 p-4 rounded-lg border"></div>

                <!-- 헤더 -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= __('staff.title') ?></h1>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1"><?= __('staff.description') ?></p>
                    </div>
                </div>

                <!-- 통계 카드 -->
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 mb-6">
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-5 border border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('staff.total') ?></p>
                                <p class="text-2xl font-bold mt-1"><?= $totalStaff ?></p>
                            </div>
                            <div class="w-12 h-12 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            </div>
                        </div>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-5 border border-zinc-200 dark:border-zinc-700">
                        <div class="flex items-center justify-between">
                            <div>
                                <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('staff.active') ?></p>
                                <p class="text-2xl font-bold mt-1"><?= $activeStaff ?></p>
                            </div>
                            <div class="w-12 h-12 bg-green-100 dark:bg-green-900/30 rounded-xl flex items-center justify-center">
                                <svg class="w-6 h-6 text-green-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 추가 버튼 + 목록 -->
                <div class="flex items-center justify-end mb-4">
                    <button onclick="openStaffModal()"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <?= __('staff.create') ?>
                    </button>
                </div>

                <!-- 스태프 목록 -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <?php if (empty($staffList)): ?>
                    <div class="p-12 text-center text-zinc-500 dark:text-zinc-400">
                        <svg class="w-12 h-12 mx-auto mb-3 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <p><?= __('staff.empty') ?></p>
                        <button onclick="openStaffModal()" class="mt-3 text-blue-600 hover:text-blue-700 text-sm font-medium">+ <?= __('staff.create') ?></button>
                    </div>
                    <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-zinc-50 dark:bg-zinc-700/50">
                                <tr>
                                    <th class="text-left px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300"><?= __('staff.fields.name') ?></th>
                                    <th class="text-left px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300"><?= __('staff.fields.contact') ?></th>
                                    <th class="text-left px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300"><?= __('staff.fields.services') ?></th>
                                    <th class="text-center px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300"><?= __('staff.fields.is_active') ?></th>
                                    <th class="text-center px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300"><?= __('staff.actions') ?></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                <?php foreach ($staffList as $st): ?>
                                <tr id="staff-<?= $st['id'] ?>" class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-9 h-9 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 font-semibold text-sm">
                                                <?= mb_substr($st['name'], 0, 1) ?>
                                            </div>
                                            <div>
                                                <div class="font-medium text-zinc-900 dark:text-white">
                                                    <?= htmlspecialchars($st['name']) ?>
                                                    <?php if (!empty($st['user_id'])): ?>
                                                    <span class="ml-1 inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-medium bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-300" title="<?= __('staff.linked_member') ?>"><?= __('staff.linked') ?></span>
                                                    <?php endif; ?>
                                                </div>
                                                <?php if (!empty($st['bio'])): ?>
                                                <div class="text-xs text-zinc-500 dark:text-zinc-400 truncate max-w-xs"><?= htmlspecialchars(mb_substr($st['bio'], 0, 40)) ?></div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400">
                                        <?php if ($st['email']): ?><div class="text-xs"><?= htmlspecialchars($st['email']) ?></div><?php endif; ?>
                                        <?php if ($st['phone']): ?><div class="text-xs"><?= htmlspecialchars($st['phone']) ?></div><?php endif; ?>
                                        <?php if (!$st['email'] && !$st['phone']): ?><span class="text-zinc-400">-</span><?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3">
                                        <?php
                                        $svcIds = $staffServices[$st['id']] ?? [];
                                        if (!empty($svcIds)):
                                            $svcNames = [];
                                            foreach ($allServices as $svc) {
                                                if (in_array($svc['id'], $svcIds)) $svcNames[] = $svc['name'];
                                            }
                                        ?>
                                        <div class="flex flex-wrap gap-1">
                                            <?php foreach (array_slice($svcNames, 0, 3) as $sn): ?>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-800 dark:text-blue-300"><?= htmlspecialchars($sn) ?></span>
                                            <?php endforeach; ?>
                                            <?php if (count($svcNames) > 3): ?>
                                            <span class="text-xs text-zinc-400">+<?= count($svcNames) - 3 ?></span>
                                            <?php endif; ?>
                                        </div>
                                        <?php else: ?>
                                        <span class="text-zinc-400 text-xs">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <button onclick="toggleStaff(<?= $st['id'] ?>)"
                                                class="relative inline-flex h-6 w-11 items-center rounded-full transition-colors <?= $st['is_active'] ? 'bg-green-500' : 'bg-zinc-300 dark:bg-zinc-600' ?>"
                                                data-active="<?= $st['is_active'] ?>" id="toggle-<?= $st['id'] ?>">
                                            <span class="inline-block h-4 w-4 transform rounded-full bg-white transition-transform <?= $st['is_active'] ? 'translate-x-6' : 'translate-x-1' ?>"></span>
                                        </button>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <button onclick='editStaff(<?= json_encode($st, JSON_HEX_APOS | JSON_HEX_QUOT) ?>, <?= json_encode($staffServices[$st['id']] ?? []) ?>)'
                                                    class="p-1.5 text-zinc-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors" title="<?= __('staff.edit') ?>">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>
                                            <button onclick="deleteStaff(<?= $st['id'] ?>)"
                                                    class="p-1.5 text-zinc-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors" title="<?= __('staff.delete') ?>">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
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
        </main>
    </div>

    <?php include __DIR__ . '/staff-form.php'; ?>
    <?php include __DIR__ . '/staff-js.php'; ?>
</body>
</html>
