<?php
/**
 * RezlyX Admin - 관리자 권한 관리 페이지
 * 슈퍼바이저(master)가 스태프에게 관리자 권한을 부여/관리
 */

if (!function_exists('__')) {
    require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';
}

$pageTitle = '관리자 권한 관리 - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');

// 현재 로그인한 관리자 정보
$currentAdmin = \RzxLib\Core\Auth\AdminAuth::current();
$isMaster = \RzxLib\Core\Auth\AdminAuth::isMaster();

// master만 접근 가능
if (!$isMaster) {
    http_response_code(403);
    include BASE_PATH . '/resources/views/admin/403.php';
    exit;
}

// DB 연결
$adminList = [];
$staffList = [];

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

    // POST 처리 (AJAX)
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        header('Content-Type: application/json; charset=utf-8');

        try {
        $action = $_POST['action'] ?? '';

        // master만 관리자 관리 가능
        if (!$isMaster) {
            echo json_encode(['error' => '슈퍼바이저만 관리자 권한을 관리할 수 있습니다.']);
            exit;
        }

        // 관리자 추가 (스태프 → 관리자 승격)
        if ($action === 'add_admin') {
            $staffId = (int)($_POST['staff_id'] ?? 0);
            $role = $_POST['role'] ?? 'staff';
            $perms = $_POST['permissions'] ?? '[]';

            if (!$staffId) {
                echo json_encode(['error' => '스태프를 선택해주세요.']);
                exit;
            }
            if (!in_array($role, ['manager', 'staff'])) $role = 'staff';

            // 스태프 정보 조회
            $stmt = $pdo->prepare("SELECT s.id, s.user_id, s.name, s.email, u.password FROM {$prefix}staff s LEFT JOIN {$prefix}users u ON s.user_id = u.id WHERE s.id = ?");
            $stmt->execute([$staffId]);
            $staff = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$staff) {
                echo json_encode(['error' => '스태프를 찾을 수 없습니다.']);
                exit;
            }
            if (!$staff['user_id']) {
                echo json_encode(['error' => '이 스태프는 회원 연동이 되어있지 않습니다.']);
                exit;
            }

            // 이미 관리자인지 확인
            $stmt = $pdo->prepare("SELECT id FROM {$prefix}admins WHERE staff_id = ?");
            $stmt->execute([$staffId]);
            if ($stmt->fetch()) {
                echo json_encode(['error' => '이미 관리자로 등록된 스태프입니다.']);
                exit;
            }

            // UUID 생성
            $data = random_bytes(16);
            $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
            $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
            $adminId = vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));

            $stmt = $pdo->prepare("INSERT INTO {$prefix}admins (id, user_id, staff_id, email, password, name, role, permissions, status) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active')");
            $stmt->execute([
                $adminId, $staff['user_id'], $staffId,
                $staff['email'] ?? '', $staff['password'] ?? '',
                $staff['name'], $role, $perms
            ]);

            echo json_encode(['success' => true, 'message' => '관리자가 추가되었습니다.']);
            exit;
        }

        // 권한 수정
        if ($action === 'update_permissions') {
            $adminId = $_POST['admin_id'] ?? '';
            $role = $_POST['role'] ?? 'staff';
            $perms = $_POST['permissions'] ?? '[]';

            if (!$adminId) {
                echo json_encode(['error' => '관리자 ID가 필요합니다.']);
                exit;
            }

            // 슈퍼바이저 보호
            if (\RzxLib\Core\Auth\AdminAuth::isSupervisorAdmin($adminId)) {
                echo json_encode(['error' => '슈퍼바이저의 권한은 변경할 수 없습니다.']);
                exit;
            }

            if (!in_array($role, ['manager', 'staff'])) $role = 'staff';

            $stmt = $pdo->prepare("UPDATE {$prefix}admins SET role = ?, permissions = ? WHERE id = ?");
            $stmt->execute([$role, $perms, $adminId]);

            echo json_encode(['success' => true, 'message' => '권한이 수정되었습니다.']);
            exit;
        }

        // 관리자 삭제 (권한 해제)
        if ($action === 'remove_admin') {
            $adminId = $_POST['admin_id'] ?? '';

            if (\RzxLib\Core\Auth\AdminAuth::isSupervisorAdmin($adminId)) {
                echo json_encode(['error' => '슈퍼바이저는 삭제할 수 없습니다.']);
                exit;
            }

            $stmt = $pdo->prepare("DELETE FROM {$prefix}admins WHERE id = ? AND role != 'master'");
            $stmt->execute([$adminId]);

            echo json_encode(['success' => true, 'message' => '관리자 권한이 해제되었습니다.']);
            exit;
        }

        // 관리자 활성/비활성
        if ($action === 'toggle_status') {
            $adminId = $_POST['admin_id'] ?? '';
            $status = $_POST['status'] ?? 'inactive';

            if (\RzxLib\Core\Auth\AdminAuth::isSupervisorAdmin($adminId)) {
                echo json_encode(['error' => '슈퍼바이저의 상태는 변경할 수 없습니다.']);
                exit;
            }

            $stmt = $pdo->prepare("UPDATE {$prefix}admins SET status = ? WHERE id = ? AND role != 'master'");
            $stmt->execute([$status, $adminId]);

            echo json_encode(['success' => true]);
            exit;
        }

        echo json_encode(['error' => '알 수 없는 요청입니다.']);
        exit;

        } catch (\Throwable $e) {
            echo json_encode(['error' => 'DB 오류: ' . $e->getMessage()]);
            exit;
        }
    }

    // 관리자 목록 로드 (스태프 이름/아바타 포함)
    $adminList = $pdo->query("
        SELECT a.*, s.avatar, s.name as staff_name
        FROM {$prefix}admins a
        LEFT JOIN {$prefix}staff s ON a.staff_id = s.id
        ORDER BY FIELD(a.role, 'master', 'manager', 'staff'), a.name
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 관리자가 아닌 스태프 목록 (추가용)
    $staffList = $pdo->query("
        SELECT s.id, s.name, s.email, s.avatar, s.user_id
        FROM {$prefix}staff s
        WHERE s.is_active = 1
          AND s.id NOT IN (SELECT staff_id FROM {$prefix}admins WHERE staff_id IS NOT NULL)
          AND s.user_id IS NOT NULL
        ORDER BY s.name
    ")->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {
    error_log('[Admin] Admin management error: ' . $e->getMessage());
}

// 권한 목록 정의
$permissionGroups = [
    '기본' => [
        'dashboard' => '대시보드',
        'reservations' => '예약 관리',
        'counter' => '카운터 (정산)',
    ],
    '서비스' => [
        'services' => '서비스 관리',
    ],
    '스태프' => [
        'staff' => '스태프 관리',
        'staff.schedule' => '스케줄 관리',
        'staff.attendance' => '근태 관리',
    ],
    '회원' => [
        'members' => '회원 관리',
    ],
    '사이트' => [
        'site' => '사이트 관리',
        'site.pages' => '페이지 관리',
        'site.widgets' => '위젯 관리',
        'site.design' => '디자인 관리',
        'site.menus' => '메뉴 관리',
    ],
    '설정' => [
        'settings' => '사이트 설정',
    ],
];

// 역할 라벨
$roleLabels = [
    'master' => '슈퍼바이저',
    'manager' => '매니저',
    'staff' => '스태프',
];
$roleColors = [
    'master' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    'manager' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    'staff' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400',
];
$pageHeaderTitle = __('staff.title');
$pageSubTitle = __('staff.admins.title');
$pageSubDesc = __('staff.admins.description');
?>
<?php include __DIR__ . '/../reservations/_head.php'; ?>
        <div class="max-w-6xl">
            <!-- 액션 버튼 -->
            <?php if ($isMaster): ?>
            <div class="flex justify-end mb-6">
                <button onclick="openAddModal()" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 transition flex items-center">
                    <svg class="w-4 h-4 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                    관리자 추가
                </button>
            </div>
            <?php endif; ?>

            <!-- 관리자 목록 -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm overflow-hidden">
                <table class="w-full">
                    <thead class="bg-zinc-50 dark:bg-zinc-700/50">
                        <tr>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">관리자</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">역할</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">권한</th>
                            <th class="px-6 py-3 text-left text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">상태</th>
                            <th class="px-6 py-3 text-right text-xs font-medium text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">관리</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                    <?php if (empty($adminList)): ?>
                        <tr><td colspan="5" class="px-6 py-12 text-center text-zinc-400">등록된 관리자가 없습니다.</td></tr>
                    <?php else: foreach ($adminList as $admin):
                        $isSupervisor = ($admin['role'] === 'master');
                        $perms = json_decode($admin['permissions'] ?? '[]', true) ?: [];
                        $permCount = $isSupervisor ? '전체' : count($perms) . '개';
                    ?>
                        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <?php if (!empty($admin['avatar'])): ?>
                                    <img src="<?= htmlspecialchars($admin['avatar']) ?>" class="w-10 h-10 rounded-full object-cover mr-3">
                                    <?php else: ?>
                                    <div class="w-10 h-10 rounded-full bg-zinc-200 dark:bg-zinc-600 flex items-center justify-center mr-3">
                                        <span class="text-sm font-bold text-zinc-500 dark:text-zinc-300"><?= mb_substr($admin['name'], 0, 1) ?></span>
                                    </div>
                                    <?php endif; ?>
                                    <div>
                                        <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($admin['name']) ?>
                                        <?php if ($isSupervisor): ?>
                                            <svg class="inline w-4 h-4 text-red-500 ml-1" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M2.166 4.999A11.954 11.954 0 0010 1.944 11.954 11.954 0 0017.834 5c.11.65.166 1.32.166 2.001 0 5.225-3.34 9.67-8 11.317C5.34 16.67 2 12.225 2 7c0-.682.057-1.35.166-2.001zm11.541 3.708a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                                        <?php endif; ?>
                                        </p>
                                        <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= htmlspecialchars($admin['email']) ?></p>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                <span class="px-2.5 py-1 text-xs font-medium rounded-full <?= $roleColors[$admin['role']] ?? $roleColors['staff'] ?>">
                                    <?= $roleLabels[$admin['role']] ?? $admin['role'] ?>
                                </span>
                            </td>
                            <td class="px-6 py-4">
                                <span class="text-sm text-zinc-600 dark:text-zinc-300"><?= $permCount ?></span>
                                <?php if (!$isSupervisor && !empty($perms)): ?>
                                <div class="flex flex-wrap gap-1 mt-1">
                                    <?php foreach (array_slice($perms, 0, 3) as $p): ?>
                                    <span class="px-1.5 py-0.5 text-[10px] bg-zinc-100 dark:bg-zinc-700 text-zinc-500 dark:text-zinc-400 rounded"><?= htmlspecialchars($p) ?></span>
                                    <?php endforeach; ?>
                                    <?php if (count($perms) > 3): ?>
                                    <span class="px-1.5 py-0.5 text-[10px] text-zinc-400">+<?= count($perms) - 3 ?></span>
                                    <?php endif; ?>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4">
                                <?php if ($admin['status'] === 'active'): ?>
                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium text-green-700 bg-green-100 dark:bg-green-900/30 dark:text-green-400 rounded-full">활성</span>
                                <?php else: ?>
                                <span class="inline-flex items-center px-2 py-0.5 text-xs font-medium text-zinc-500 bg-zinc-100 dark:bg-zinc-700 dark:text-zinc-400 rounded-full">비활성</span>
                                <?php endif; ?>
                            </td>
                            <td class="px-6 py-4 text-right">
                                <?php if (!$isSupervisor && $isMaster): ?>
                                <div class="flex items-center justify-end gap-1">
                                    <button onclick="openEditModal('<?= $admin['id'] ?>', '<?= htmlspecialchars($admin['name']) ?>', '<?= $admin['role'] ?>', <?= htmlspecialchars($admin['permissions'] ?? '[]') ?>)"
                                            class="p-1.5 text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition" title="권한 편집">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                    </button>
                                    <button onclick="toggleAdminStatus('<?= $admin['id'] ?>', '<?= $admin['status'] === 'active' ? 'inactive' : 'active' ?>')"
                                            class="p-1.5 <?= $admin['status'] === 'active' ? 'text-yellow-600 hover:bg-yellow-50 dark:hover:bg-yellow-900/20' : 'text-green-600 hover:bg-green-50 dark:hover:bg-green-900/20' ?> rounded-lg transition"
                                            title="<?= $admin['status'] === 'active' ? '비활성화' : '활성화' ?>">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $admin['status'] === 'active' ? 'M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636' : 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' ?>"/></svg>
                                    </button>
                                    <button onclick="removeAdmin('<?= $admin['id'] ?>', '<?= htmlspecialchars($admin['name']) ?>')"
                                            class="p-1.5 text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition" title="관리자 해제">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                    </button>
                                </div>
                                <?php elseif ($isSupervisor): ?>
                                <span class="text-xs text-zinc-400">보호됨</span>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; endif; ?>
                    </tbody>
                </table>
            </div>

            <!-- 안내 -->
            <div class="mt-4 p-4 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
                <div class="flex items-start">
                    <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 mt-0.5 mr-3 flex-shrink-0" fill="currentColor" viewBox="0 0 20 20">
                        <path fill-rule="evenodd" d="M18 10a8 8 0 11-16 0 8 8 0 0116 0zm-7-4a1 1 0 11-2 0 1 1 0 012 0zM9 9a1 1 0 000 2v3a1 1 0 001 1h1a1 1 0 100-2v-3a1 1 0 00-1-1H9z" clip-rule="evenodd"/>
                    </svg>
                    <div class="text-sm text-blue-800 dark:text-blue-300">
                        <p class="font-semibold mb-1">권한 안내</p>
                        <ul class="space-y-0.5 text-xs text-blue-700 dark:text-blue-400">
                            <li>• <strong>슈퍼바이저</strong>: 모든 메뉴에 접근 가능하며 삭제/비활성화가 불가합니다.</li>
                            <li>• <strong>매니저</strong>: 지정된 권한 범위 내에서 관리 가능합니다.</li>
                            <li>• <strong>스태프</strong>: 개별적으로 부여된 권한만 사용할 수 있습니다.</li>
                            <li>• 관리자로 추가하려면 해당 스태프가 <strong>회원 연동</strong>이 되어있어야 합니다.</li>
                        </ul>
                    </div>
                </div>
            </div>
        </div>

    <!-- 관리자 추가 모달 -->
    <div id="addModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50" onclick="closeAddModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto relative">
                <div class="sticky top-0 bg-white dark:bg-zinc-800 px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-zinc-900 dark:text-white">관리자 추가</h3>
                    <button onclick="closeAddModal()" class="p-1 text-zinc-400 hover:text-zinc-600 rounded">&times;</button>
                </div>
                <div class="p-6 space-y-4">
                    <!-- 스태프 선택 -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">스태프 선택</label>
                        <?php if (empty($staffList)): ?>
                        <p class="text-sm text-zinc-400">추가 가능한 스태프가 없습니다. (회원 연동 필요)</p>
                        <?php else: ?>
                        <select id="addStaffId" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm">
                            <option value="">-- 선택 --</option>
                            <?php foreach ($staffList as $s): ?>
                            <option value="<?= $s['id'] ?>"><?= htmlspecialchars($s['name']) ?> (<?= htmlspecialchars($s['email'] ?? 'N/A') ?>)</option>
                            <?php endforeach; ?>
                        </select>
                        <?php endif; ?>
                    </div>

                    <!-- 역할 선택 -->
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">역할</label>
                        <select id="addRole" onchange="onRoleChange('add')" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm">
                            <option value="staff">스태프</option>
                            <option value="manager">매니저</option>
                        </select>
                    </div>

                    <!-- 권한 선택 -->
                    <div id="addPermSection">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">접근 권한</label>
                        <?php include __DIR__ . '/admins-perm-checkboxes.php'; ?>
                    </div>
                </div>
                <div class="sticky bottom-0 bg-white dark:bg-zinc-800 px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex justify-end gap-2">
                    <button onclick="closeAddModal()" class="px-4 py-2 text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg">취소</button>
                    <button onclick="submitAddAdmin()" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">추가</button>
                </div>
            </div>
        </div>
    </div>

    <!-- 권한 편집 모달 -->
    <div id="editModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50" onclick="closeEditModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl w-full max-w-lg max-h-[90vh] overflow-y-auto relative">
                <div class="sticky top-0 bg-white dark:bg-zinc-800 px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                    <h3 class="text-lg font-bold text-zinc-900 dark:text-white">권한 편집 - <span id="editAdminName"></span></h3>
                    <button onclick="closeEditModal()" class="p-1 text-zinc-400 hover:text-zinc-600 rounded">&times;</button>
                </div>
                <div class="p-6 space-y-4">
                    <input type="hidden" id="editAdminId">
                    <div>
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1">역할</label>
                        <select id="editRole" onchange="onRoleChange('edit')" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm">
                            <option value="staff">스태프</option>
                            <option value="manager">매니저</option>
                        </select>
                    </div>
                    <div id="editPermSection">
                        <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2">접근 권한</label>
                        <?php $checkboxPrefix = 'edit'; include __DIR__ . '/admins-perm-checkboxes.php'; ?>
                    </div>
                </div>
                <div class="sticky bottom-0 bg-white dark:bg-zinc-800 px-6 py-4 border-t border-zinc-200 dark:border-zinc-700 flex justify-end gap-2">
                    <button onclick="closeEditModal()" class="px-4 py-2 text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg">취소</button>
                    <button onclick="submitEditAdmin()" class="px-4 py-2 text-sm bg-blue-600 text-white rounded-lg hover:bg-blue-700">저장</button>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/admins-js.php'; ?>
</div>
</main>
</div>
</body>
</html>
