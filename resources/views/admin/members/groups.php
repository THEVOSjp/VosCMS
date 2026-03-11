<?php
/**
 * RezlyX Admin - 회원 그룹(등급) 관리 페이지
 */

if (!function_exists('__')) {
    require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';
}

$pageTitle = __('admin.members.groups.title') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
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
                case 'create_grade':
                    $name = trim($_POST['name'] ?? '');
                    $slug = trim($_POST['slug'] ?? '');
                    $color = trim($_POST['color'] ?? '#6B7280');
                    $discountRate = floatval($_POST['discount_rate'] ?? 0);
                    $pointRate = floatval($_POST['point_rate'] ?? 0);
                    $minReservations = intval($_POST['min_reservations'] ?? 0);
                    $minSpent = floatval($_POST['min_spent'] ?? 0);
                    $benefits = trim($_POST['benefits'] ?? '');

                    if (empty($name)) {
                        echo json_encode(['success' => false, 'message' => __('admin.members.groups.error.name_required')]);
                        exit;
                    }
                    if (empty($slug)) {
                        $slug = preg_replace('/[^a-z0-9_-]/', '', strtolower($name));
                    }

                    // slug 중복 체크
                    $chk = $pdo->prepare("SELECT id FROM {$prefix}member_grades WHERE slug = ?");
                    $chk->execute([$slug]);
                    if ($chk->fetch()) {
                        echo json_encode(['success' => false, 'message' => __('admin.members.groups.error.slug_duplicate')]);
                        exit;
                    }

                    $id = bin2hex(random_bytes(18));
                    $maxSort = $pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM {$prefix}member_grades")->fetchColumn();
                    $benefitsJson = $benefits ? json_encode($benefits, JSON_UNESCAPED_UNICODE) : null;
                    $stmt = $pdo->prepare("INSERT INTO {$prefix}member_grades (id, name, slug, color, discount_rate, point_rate, min_reservations, min_spent, benefits, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$id, $name, $slug, $color, $discountRate, $pointRate, $minReservations, $minSpent, $benefitsJson, $maxSort]);

                    echo json_encode(['success' => true, 'message' => __('admin.members.groups.success.created'), 'id' => $id]);
                    exit;

                case 'update_grade':
                    $id = trim($_POST['id'] ?? '');
                    $name = trim($_POST['name'] ?? '');
                    $slug = trim($_POST['slug'] ?? '');
                    $color = trim($_POST['color'] ?? '#6B7280');
                    $discountRate = floatval($_POST['discount_rate'] ?? 0);
                    $pointRate = floatval($_POST['point_rate'] ?? 0);
                    $minReservations = intval($_POST['min_reservations'] ?? 0);
                    $minSpent = floatval($_POST['min_spent'] ?? 0);
                    $benefits = trim($_POST['benefits'] ?? '');

                    if (empty($name)) {
                        echo json_encode(['success' => false, 'message' => __('admin.members.groups.error.name_required')]);
                        exit;
                    }

                    // slug 중복 체크 (자기 제외)
                    $chk = $pdo->prepare("SELECT id FROM {$prefix}member_grades WHERE slug = ? AND id != ?");
                    $chk->execute([$slug, $id]);
                    if ($chk->fetch()) {
                        echo json_encode(['success' => false, 'message' => __('admin.members.groups.error.slug_duplicate')]);
                        exit;
                    }

                    $benefitsJson = $benefits ? json_encode($benefits, JSON_UNESCAPED_UNICODE) : null;
                    $stmt = $pdo->prepare("UPDATE {$prefix}member_grades SET name=?, slug=?, color=?, discount_rate=?, point_rate=?, min_reservations=?, min_spent=?, benefits=? WHERE id=?");
                    $stmt->execute([$name, $slug, $color, $discountRate, $pointRate, $minReservations, $minSpent, $benefitsJson, $id]);

                    echo json_encode(['success' => true, 'message' => __('admin.members.groups.success.updated')]);
                    exit;

                case 'delete_grade':
                    $id = trim($_POST['id'] ?? '');

                    // 기본 등급 삭제 방지
                    $chk = $pdo->prepare("SELECT is_default FROM {$prefix}member_grades WHERE id = ?");
                    $chk->execute([$id]);
                    $grade = $chk->fetch(PDO::FETCH_ASSOC);
                    if ($grade && $grade['is_default']) {
                        echo json_encode(['success' => false, 'message' => __('admin.members.groups.error.cannot_delete_default')]);
                        exit;
                    }

                    // 해당 등급 사용 회원 수 확인
                    $cnt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}users WHERE grade_id = ?");
                    $cnt->execute([$id]);
                    $memberCount = $cnt->fetchColumn();
                    if ($memberCount > 0) {
                        // 기본 등급으로 이동
                        $defaultGrade = $pdo->query("SELECT id FROM {$prefix}member_grades WHERE is_default = 1 LIMIT 1")->fetchColumn();
                        if ($defaultGrade) {
                            $pdo->prepare("UPDATE {$prefix}users SET grade_id = ? WHERE grade_id = ?")->execute([$defaultGrade, $id]);
                        }
                    }

                    $pdo->prepare("DELETE FROM {$prefix}member_grades WHERE id = ?")->execute([$id]);
                    echo json_encode(['success' => true, 'message' => __('admin.members.groups.success.deleted')]);
                    exit;

                case 'set_default':
                    $id = trim($_POST['id'] ?? '');
                    $pdo->exec("UPDATE {$prefix}member_grades SET is_default = 0");
                    $pdo->prepare("UPDATE {$prefix}member_grades SET is_default = 1 WHERE id = ?")->execute([$id]);
                    echo json_encode(['success' => true, 'message' => __('admin.members.groups.success.default_changed')]);
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
    $grades = $pdo->query("SELECT * FROM {$prefix}member_grades ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);

    // 등급별 회원 수
    $gradeMemberCounts = [];
    $cntRows = $pdo->query("SELECT grade_id, COUNT(*) as cnt FROM {$prefix}users GROUP BY grade_id")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($cntRows as $r) {
        $gradeMemberCounts[$r['grade_id']] = (int)$r['cnt'];
    }

    $dbConnected = true;
} catch (PDOException $e) {
    $dbConnected = false;
    $dbError = $e->getMessage();
    $grades = [];
    $gradeMemberCounts = [];
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
                <div id="alertBox" class="hidden mb-6 p-4 rounded-lg border"></div>

                <!-- 헤더 -->
                <div class="flex items-center justify-between mb-6">
                    <div>
                        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= __('admin.members.groups.title') ?></h1>
                        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1"><?= __('admin.members.groups.description') ?></p>
                    </div>
                    <button onclick="openGradeModal()"
                            class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                        <?= __('admin.members.groups.create') ?>
                    </button>
                </div>

                <!-- 등급 카드 목록 -->
                <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($grades as $g): ?>
                    <div id="grade-<?= htmlspecialchars($g['id']) ?>" class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
                        <!-- 상단 컬러 바 -->
                        <div class="h-1.5" style="background-color: <?= htmlspecialchars($g['color']) ?>"></div>
                        <div class="p-5">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: <?= htmlspecialchars($g['color']) ?>"></span>
                                    <h3 class="font-semibold text-zinc-900 dark:text-white"><?= htmlspecialchars($g['name']) ?></h3>
                                    <?php if ($g['is_default']): ?>
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300"><?= __('admin.members.groups.default') ?></span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-xs text-zinc-400"><?= htmlspecialchars($g['slug']) ?></span>
                            </div>

                            <!-- 통계 -->
                            <div class="grid grid-cols-3 gap-2 mb-4">
                                <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-lg p-2.5 text-center">
                                    <div class="text-lg font-bold text-zinc-900 dark:text-white"><?= $gradeMemberCounts[$g['id']] ?? 0 ?></div>
                                    <div class="text-[11px] text-zinc-500 dark:text-zinc-400"><?= __('admin.members.groups.member_count') ?></div>
                                </div>
                                <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-lg p-2.5 text-center">
                                    <div class="text-lg font-bold text-zinc-900 dark:text-white"><?= number_format($g['discount_rate'], 1) ?>%</div>
                                    <div class="text-[11px] text-zinc-500 dark:text-zinc-400"><?= __('admin.members.groups.fields.discount_rate') ?></div>
                                </div>
                                <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-lg p-2.5 text-center">
                                    <div class="text-lg font-bold text-zinc-900 dark:text-white"><?= number_format($g['point_rate'], 1) ?>%</div>
                                    <div class="text-[11px] text-zinc-500 dark:text-zinc-400"><?= __('admin.members.groups.fields.point_rate') ?></div>
                                </div>
                            </div>

                            <!-- 혜택 요약 -->
                            <div class="text-xs text-zinc-500 dark:text-zinc-400 space-y-1 mb-4">
                                <div class="flex justify-between">
                                    <span><?= __('admin.members.groups.fields.min_reservations') ?></span>
                                    <span class="font-medium text-zinc-700 dark:text-zinc-300"><?= $g['min_reservations'] ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span><?= __('admin.members.groups.fields.min_spent') ?></span>
                                    <span class="font-medium text-zinc-700 dark:text-zinc-300"><?= number_format($g['min_spent']) ?></span>
                                </div>
                            </div>

                            <!-- 버튼 -->
                            <div class="flex items-center gap-2 pt-3 border-t border-zinc-100 dark:border-zinc-700">
                                <button onclick='editGrade(<?= json_encode($g, JSON_HEX_APOS | JSON_HEX_QUOT) ?>)'
                                        class="flex-1 text-center py-1.5 text-xs font-medium text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors">
                                    <?= __('admin.members.groups.edit') ?>
                                </button>
                                <?php if (!$g['is_default']): ?>
                                <button onclick="setDefault('<?= htmlspecialchars($g['id']) ?>')"
                                        class="flex-1 text-center py-1.5 text-xs font-medium text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition-colors">
                                    <?= __('admin.members.groups.set_default') ?>
                                </button>
                                <button onclick="deleteGrade('<?= htmlspecialchars($g['id']) ?>')"
                                        class="py-1.5 px-2 text-xs font-medium text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                                </button>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>

                    <?php if (empty($grades)): ?>
                    <div class="col-span-full p-12 text-center text-zinc-500 dark:text-zinc-400 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700">
                        <svg class="w-12 h-12 mx-auto mb-3 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
                        <p><?= __('admin.members.groups.empty') ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/groups-form.php'; ?>
    <?php include __DIR__ . '/groups-js.php'; ?>
</body>
</html>
