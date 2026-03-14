<?php
/**
 * RezlyX Admin - 회원 목록 페이지
 */

if (!function_exists('__')) {
    require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';
}

require_once BASE_PATH . '/rzxlib/Core/Helpers/Encryption.php';
use RzxLib\Core\Helpers\Encryption;

$pageTitle = __('members.list.title') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');

$settings = [];
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'],
        $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

    $stmt = $pdo->query("SELECT `key`, `value` FROM {$prefix}settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['key']] = $row['value'];
    }

    // 등급 목록
    $grades = [];
    $gradeRows = $pdo->query("SELECT id, name, slug, color, is_default FROM {$prefix}member_grades ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($gradeRows as $gr) {
        $grades[$gr['id']] = $gr;
    }

    // 회원가입 입력 항목 설정 로드
    $registerFields = explode(',', $settings['member_register_fields'] ?? 'name,email,password,phone');

    // ─── API 요청 처리 ───
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
        header('Content-Type: application/json; charset=utf-8');
        $action = $_POST['action'];

        // 디버그: 전화번호 관련 POST 데이터 로깅
        error_log('[Members POST] action=' . $action . ', phone=' . ($_POST['phone'] ?? 'NULL') . ', phone_country=' . ($_POST['phone_country'] ?? 'NULL') . ', phone_number=' . ($_POST['phone_number'] ?? 'NULL'));

        // 공통 필드 파싱 헬퍼
        function parseExtraFields(array $post): array {
            return [
                'phone' => trim($post['phone'] ?? '') ?: null,
                'birth_date' => trim($post['birth_date'] ?? '') ?: null,
                'gender' => in_array($post['gender'] ?? '', ['male', 'female', 'other']) ? $post['gender'] : null,
                'company' => trim($post['company'] ?? '') ?: null,
                'blog' => trim($post['blog'] ?? '') ?: null,
            ];
        }

        // 프로필 이미지 업로드 처리
        function handleProfileImage(array $files, string $userId): ?string {
            if (empty($files['profile_image']) || $files['profile_image']['error'] !== UPLOAD_ERR_OK) {
                return null;
            }
            $file = $files['profile_image'];
            $ext = strtolower(pathinfo($file['name'], PATHINFO_EXTENSION));
            if (!in_array($ext, ['jpg', 'jpeg', 'png', 'webp'])) {
                return null;
            }
            $uploadDir = BASE_PATH . '/storage/uploads/profiles/';
            if (!is_dir($uploadDir)) {
                mkdir($uploadDir, 0755, true);
            }
            $fileName = $userId . '_' . time() . '.' . $ext;
            $dest = $uploadDir . $fileName;
            if (move_uploaded_file($file['tmp_name'], $dest)) {
                return '/storage/uploads/profiles/' . $fileName;
            }
            return null;
        }

        try {
            switch ($action) {
                case 'create_member':
                    $name = trim($_POST['name'] ?? '');
                    $email = trim($_POST['email'] ?? '');
                    $password = trim($_POST['password'] ?? '');
                    $gradeId = trim($_POST['grade_id'] ?? '') ?: null;
                    $status = trim($_POST['status'] ?? 'active');
                    $extra = parseExtraFields($_POST);

                    if (empty($name) || empty($email) || empty($password)) {
                        echo json_encode(['success' => false, 'message' => __('members.list.error.required')]);
                        exit;
                    }

                    // 이메일 중복 체크
                    $chk = $pdo->prepare("SELECT id FROM {$prefix}users WHERE email = ?");
                    $chk->execute([$email]);
                    if ($chk->fetch()) {
                        echo json_encode(['success' => false, 'message' => __('members.list.error.email_duplicate')]);
                        exit;
                    }

                    $id = bin2hex(random_bytes(18));
                    $hashed = password_hash($password, PASSWORD_BCRYPT);

                    // 민감한 필드 암호화
                    $encName = Encryption::encrypt($name);
                    $encPhone = $extra['phone'] ? Encryption::encrypt($extra['phone']) : null;

                    // 프로필 이미지 처리
                    $profileImage = handleProfileImage($_FILES, $id);

                    $sql = "INSERT INTO {$prefix}users (id, name, email, password, phone, birth_date, gender, company, blog, grade_id, status, profile_image)
                            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
                    $pdo->prepare($sql)->execute([
                        $id, $encName, $email, $hashed,
                        $encPhone, $extra['birth_date'], $extra['gender'],
                        $extra['company'], $extra['blog'], $gradeId, $status, $profileImage,
                    ]);

                    echo json_encode(['success' => true, 'message' => __('members.list.success.created')]);
                    exit;

                case 'update_member':
                    $id = trim($_POST['id'] ?? '');
                    $name = trim($_POST['name'] ?? '');
                    $email = trim($_POST['email'] ?? '');
                    $gradeId = trim($_POST['grade_id'] ?? '') ?: null;
                    $status = trim($_POST['status'] ?? 'active');
                    $extra = parseExtraFields($_POST);

                    if (empty($name) || empty($email)) {
                        echo json_encode(['success' => false, 'message' => __('members.list.error.required')]);
                        exit;
                    }

                    // 이메일 중복 체크 (자기 제외)
                    $chk = $pdo->prepare("SELECT id FROM {$prefix}users WHERE email = ? AND id != ?");
                    $chk->execute([$email, $id]);
                    if ($chk->fetch()) {
                        echo json_encode(['success' => false, 'message' => __('members.list.error.email_duplicate')]);
                        exit;
                    }

                    // 기존 등급 조회 (StaffSync용)
                    $oldUser = $pdo->prepare("SELECT grade_id FROM {$prefix}users WHERE id = ?");
                    $oldUser->execute([$id]);
                    $oldGradeId = $oldUser->fetchColumn() ?: null;

                    // 민감한 필드 암호화
                    $encName = Encryption::encrypt($name);
                    $encPhone = $extra['phone'] ? Encryption::encrypt($extra['phone']) : null;

                    // 프로필 이미지 처리
                    $profileImage = handleProfileImage($_FILES, $id);
                    $profileSQL = $profileImage ? ', profile_image=?' : '';

                    // 비밀번호 변경 (입력된 경우만)
                    $passwordSQL = '';
                    $params = [$encName, $email, $encPhone, $extra['birth_date'], $extra['gender'], $extra['company'], $extra['blog'], $gradeId, $status];
                    if ($profileImage) {
                        $params[] = $profileImage;
                    }
                    $newPassword = trim($_POST['password'] ?? '');
                    if ($newPassword !== '') {
                        $passwordSQL = ', password=?';
                        $params[] = password_hash($newPassword, PASSWORD_BCRYPT);
                    }
                    $params[] = $id;

                    $stmt = $pdo->prepare("UPDATE {$prefix}users SET name=?, email=?, phone=?, birth_date=?, gender=?, company=?, blog=?, grade_id=?, status=? {$profileSQL} {$passwordSQL} WHERE id=?");
                    $stmt->execute($params);

                    // 등급 변경 시 스태프 자동 동기화
                    if ($gradeId !== $oldGradeId) {
                        require_once BASE_PATH . '/rzxlib/Core/Helpers/StaffSync.php';
                        StaffSync::onGradeChanged($pdo, $prefix, $id, $gradeId, $oldGradeId);
                    }

                    echo json_encode(['success' => true, 'message' => __('members.list.success.updated')]);
                    exit;

                case 'change_status':
                    $id = trim($_POST['id'] ?? '');
                    $status = trim($_POST['status'] ?? '');
                    if (!in_array($status, ['active', 'inactive', 'withdrawn'])) {
                        echo json_encode(['success' => false, 'message' => 'Invalid status']);
                        exit;
                    }
                    $pdo->prepare("UPDATE {$prefix}users SET status = ? WHERE id = ?")->execute([$status, $id]);
                    echo json_encode(['success' => true, 'message' => __('members.list.success.status_changed')]);
                    exit;

                case 'change_grade':
                    $id = trim($_POST['id'] ?? '');
                    $gradeId = trim($_POST['grade_id'] ?? '') ?: null;

                    $oldUser = $pdo->prepare("SELECT grade_id FROM {$prefix}users WHERE id = ?");
                    $oldUser->execute([$id]);
                    $oldGradeId = $oldUser->fetchColumn() ?: null;

                    $pdo->prepare("UPDATE {$prefix}users SET grade_id = ? WHERE id = ?")->execute([$gradeId, $id]);

                    if ($gradeId !== $oldGradeId) {
                        require_once BASE_PATH . '/rzxlib/Core/Helpers/StaffSync.php';
                        StaffSync::onGradeChanged($pdo, $prefix, $id, $gradeId, $oldGradeId);
                    }

                    echo json_encode(['success' => true, 'message' => __('members.list.success.grade_changed')]);
                    exit;

                case 'delete_member':
                    $id = trim($_POST['id'] ?? '');
                    // 관련 스태프 비활성화
                    $pdo->prepare("UPDATE {$prefix}staff SET is_active = 0 WHERE user_id = ?")->execute([$id]);
                    $pdo->prepare("DELETE FROM {$prefix}users WHERE id = ?")->execute([$id]);
                    echo json_encode(['success' => true, 'message' => __('members.list.success.deleted')]);
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

    // ─── 필터/검색/페이징 ───
    $search = trim($_GET['q'] ?? '');
    $filterGrade = trim($_GET['grade'] ?? '');
    $filterStatus = trim($_GET['status'] ?? '');
    $page = max(1, intval($_GET['page'] ?? 1));
    $perPage = 20;
    $offset = ($page - 1) * $perPage;

    $where = [];
    $params = [];

    if ($search !== '') {
        $where[] = "(u.name LIKE ? OR u.email LIKE ? OR u.phone LIKE ?)";
        $like = "%{$search}%";
        $params = array_merge($params, [$like, $like, $like]);
    }
    if ($filterGrade !== '') {
        if ($filterGrade === '_none') {
            $where[] = "u.grade_id IS NULL";
        } else {
            $where[] = "u.grade_id = ?";
            $params[] = $filterGrade;
        }
    }
    if ($filterStatus !== '') {
        $where[] = "u.status = ?";
        $params[] = $filterStatus;
    }

    $whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

    // 총 수
    $countStmt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}users u {$whereSQL}");
    $countStmt->execute($params);
    $totalMembers = (int)$countStmt->fetchColumn();
    $totalPages = max(1, ceil($totalMembers / $perPage));

    // 회원 목록
    $listStmt = $pdo->prepare("SELECT u.* FROM {$prefix}users u {$whereSQL} ORDER BY u.created_at DESC LIMIT {$perPage} OFFSET {$offset}");
    $listStmt->execute($params);
    $members = $listStmt->fetchAll(PDO::FETCH_ASSOC);

    // 암호화된 필드 복호화 (name, phone, furigana)
    $encryptedFields = ['name', 'phone', 'furigana'];
    foreach ($members as &$m) {
        $m = Encryption::decryptFields($m, $encryptedFields);
        // 복호화 실패한 enc: 값은 빈 문자열로 대체 (관리자가 재저장 시 재암호화됨)
        foreach ($encryptedFields as $field) {
            if (!empty($m[$field]) && str_starts_with($m[$field], 'enc:')) {
                $m[$field] = '';
            }
        }
    }
    unset($m);

    // 통계
    $totalAll = $pdo->query("SELECT COUNT(*) FROM {$prefix}users")->fetchColumn();
    $totalActive = $pdo->query("SELECT COUNT(*) FROM {$prefix}users WHERE status = 'active'")->fetchColumn();
    $totalInactive = $pdo->query("SELECT COUNT(*) FROM {$prefix}users WHERE status != 'active'")->fetchColumn();

    $dbConnected = true;
} catch (PDOException $e) {
    $dbConnected = false;
    $dbError = $e->getMessage();
    $members = [];
    $grades = [];
    $totalMembers = $totalAll = $totalActive = $totalInactive = 0;
    $totalPages = 1;
    $page = 1;
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
    <!-- Cropper.js for profile photo editing -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.css">
    <script src="https://cdnjs.cloudflare.com/ajax/libs/cropperjs/1.6.2/cropper.min.js"></script>
    <style>body { font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; }
    .cropper-view-box, .cropper-face { border-radius: 50%; }
    </style>
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
                        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= __('members.list.title') ?></h1>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1"><?= __('members.list.description') ?></p>
                    </div>
                    <button type="button" onclick="openCreateMember()"
                            class="px-4 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors flex items-center gap-1.5">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <?= __('members.list.create') ?>
                    </button>
                </div>

                <!-- 통계 카드 -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4 mb-6">
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-5 border border-zinc-200 dark:border-zinc-700">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('members.list.stat_total') ?></p>
                        <p class="text-2xl font-bold mt-1"><?= $totalAll ?></p>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-5 border border-zinc-200 dark:border-zinc-700">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('members.list.stat_active') ?></p>
                        <p class="text-2xl font-bold mt-1 text-green-600"><?= $totalActive ?></p>
                    </div>
                    <div class="bg-white dark:bg-zinc-800 rounded-xl p-5 border border-zinc-200 dark:border-zinc-700">
                        <p class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('members.list.stat_inactive') ?></p>
                        <p class="text-2xl font-bold mt-1 text-zinc-400"><?= $totalInactive ?></p>
                    </div>
                </div>

                <!-- 검색 + 필터 -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4 mb-4">
                    <form method="GET" class="flex flex-wrap items-center gap-3">
                        <div class="flex-1 min-w-[200px]">
                            <input type="text" name="q" value="<?= htmlspecialchars($search) ?>"
                                   class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm focus:ring-2 focus:ring-blue-500"
                                   placeholder="<?= __('members.list.search_placeholder') ?>">
                        </div>
                        <select name="grade" class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm">
                            <option value=""><?= __('members.list.filter_all_grades') ?></option>
                            <option value="_none" <?= $filterGrade === '_none' ? 'selected' : '' ?>><?= __('members.list.filter_no_grade') ?></option>
                            <?php foreach ($grades as $g): ?>
                            <option value="<?= htmlspecialchars($g['id']) ?>" <?= $filterGrade === $g['id'] ? 'selected' : '' ?>><?= htmlspecialchars($g['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <select name="status" class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white text-sm">
                            <option value=""><?= __('members.list.filter_all_status') ?></option>
                            <option value="active" <?= $filterStatus === 'active' ? 'selected' : '' ?>><?= __('members.list.status_active') ?></option>
                            <option value="inactive" <?= $filterStatus === 'inactive' ? 'selected' : '' ?>><?= __('members.list.status_inactive') ?></option>
                            <option value="withdrawn" <?= $filterStatus === 'withdrawn' ? 'selected' : '' ?>><?= __('members.list.status_withdrawn') ?></option>
                        </select>
                        <button type="submit" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <?= __('members.list.search') ?>
                        </button>
                        <?php if ($search || $filterGrade || $filterStatus): ?>
                        <a href="<?= $adminUrl ?>/members" class="px-3 py-2 text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300"><?= __('members.list.reset') ?></a>
                        <?php endif; ?>
                    </form>
                </div>

                <!-- 회원 목록 테이블 -->
                <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                    <?php if (empty($members)): ?>
                    <div class="p-12 text-center text-zinc-500 dark:text-zinc-400">
                        <svg class="w-12 h-12 mx-auto mb-3 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <p><?= __('members.list.empty') ?></p>
                    </div>
                    <?php else: ?>
                    <div class="overflow-x-auto">
                        <table class="w-full text-sm">
                            <thead class="bg-zinc-50 dark:bg-zinc-700/50">
                                <tr>
                                    <th class="text-left px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300"><?= __('members.list.col_name') ?></th>
                                    <th class="text-left px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300"><?= __('members.list.col_email') ?></th>
                                    <th class="text-left px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300"><?= __('members.list.col_phone') ?></th>
                                    <th class="text-left px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300"><?= __('members.list.col_grade') ?></th>
                                    <th class="text-center px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300"><?= __('members.list.col_status') ?></th>
                                    <th class="text-left px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300"><?= __('members.list.col_joined') ?></th>
                                    <th class="text-center px-4 py-3 font-medium text-zinc-600 dark:text-zinc-300"><?= __('members.list.col_actions') ?></th>
                                </tr>
                            </thead>
                            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                                <?php foreach ($members as $m):
                                    $grade = $grades[$m['grade_id']] ?? null;
                                    $statusColors = [
                                        'active' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-300',
                                        'inactive' => 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400',
                                        'withdrawn' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-300',
                                    ];
                                    $statusClass = $statusColors[$m['status']] ?? $statusColors['active'];
                                    $statusLabel = __('members.list.status_' . $m['status']);
                                ?>
                                <tr id="member-<?= htmlspecialchars($m['id']) ?>" class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition-colors">
                                    <td class="px-4 py-3">
                                        <div class="flex items-center gap-3">
                                            <div class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 font-semibold text-xs">
                                                <?= mb_substr($m['name'], 0, 1) ?>
                                            </div>
                                            <span class="font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($m['name']) ?></span>
                                        </div>
                                    </td>
                                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400"><?= htmlspecialchars($m['email']) ?></td>
                                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400"><?= htmlspecialchars($m['phone'] ?? '-') ?></td>
                                    <td class="px-4 py-3">
                                        <?php if ($grade): ?>
                                        <span class="inline-flex items-center gap-1 px-2 py-0.5 rounded-full text-xs font-medium" style="background-color: <?= htmlspecialchars($grade['color']) ?>20; color: <?= htmlspecialchars($grade['color']) ?>">
                                            <span class="w-1.5 h-1.5 rounded-full" style="background-color: <?= htmlspecialchars($grade['color']) ?>"></span>
                                            <?= htmlspecialchars($grade['name']) ?>
                                        </span>
                                        <?php else: ?>
                                        <span class="text-xs text-zinc-400">-</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="px-4 py-3 text-center">
                                        <span class="inline-flex px-2 py-0.5 rounded-full text-[11px] font-medium <?= $statusClass ?>"><?= $statusLabel ?></span>
                                    </td>
                                    <td class="px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400"><?= date('Y-m-d', strtotime($m['created_at'])) ?></td>
                                    <td class="px-4 py-3 text-center">
                                        <div class="flex items-center justify-center gap-1">
                                            <button onclick='editMember(<?= json_encode($m, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                                    class="p-1.5 text-zinc-500 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded-lg transition-colors" title="<?= __('members.list.edit') ?>">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                                            </button>
                                            <button onclick="deleteMember('<?= htmlspecialchars($m['id']) ?>')"
                                                    class="p-1.5 text-zinc-500 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded-lg transition-colors" title="<?= __('members.list.delete') ?>">
                                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                            </button>
                                        </div>
                                    </td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- 페이징 -->
                    <?php if ($totalPages > 1): ?>
                    <div class="px-4 py-3 bg-zinc-50 dark:bg-zinc-700/30 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                        <div class="text-xs text-zinc-500 dark:text-zinc-400">
                            <?= __('members.list.showing', ['from' => ($offset + 1), 'to' => min($offset + $perPage, $totalMembers), 'total' => $totalMembers]) ?>
                        </div>
                        <div class="flex items-center gap-1">
                            <?php
                            $qp = http_build_query(array_filter(['q' => $search, 'grade' => $filterGrade, 'status' => $filterStatus]));
                            $qp = $qp ? '&' . $qp : '';
                            ?>
                            <?php if ($page > 1): ?>
                            <a href="?page=<?= $page - 1 ?><?= $qp ?>" class="px-3 py-1.5 text-xs border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300">&laquo;</a>
                            <?php endif; ?>
                            <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                            <a href="?page=<?= $i ?><?= $qp ?>" class="px-3 py-1.5 text-xs border rounded-lg <?= $i === $page ? 'bg-blue-600 text-white border-blue-600' : 'border-zinc-300 dark:border-zinc-600 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300' ?>"><?= $i ?></a>
                            <?php endfor; ?>
                            <?php if ($page < $totalPages): ?>
                            <a href="?page=<?= $page + 1 ?><?= $qp ?>" class="px-3 py-1.5 text-xs border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300">&raquo;</a>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/members-form.php'; ?>
    <?php include __DIR__ . '/members-js.php'; ?>
    <?php if (in_array('phone', $registerFields)): ?>
    <script src="<?= htmlspecialchars($baseUrl) ?>/assets/js/phone-input.js"></script>
    <?php endif; ?>
</body>
</html>
