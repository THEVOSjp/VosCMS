<?php
/**
 * RezlyX Admin - 회원 그룹(등급) 관리 페이지
 */

if (!function_exists('__')) {
    require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';
}

$pageTitle = __('members.groups.title') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';
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
                        echo json_encode(['success' => false, 'message' => __('members.groups.error.name_required')]);
                        exit;
                    }
                    if (empty($slug)) {
                        $slug = preg_replace('/[^a-z0-9_-]/', '', strtolower($name));
                    }

                    // slug 중복 체크
                    $chk = $pdo->prepare("SELECT id FROM {$prefix}member_grades WHERE slug = ?");
                    $chk->execute([$slug]);
                    if ($chk->fetch()) {
                        echo json_encode(['success' => false, 'message' => __('members.groups.error.slug_duplicate')]);
                        exit;
                    }

                    $id = bin2hex(random_bytes(18));
                    $maxSort = $pdo->query("SELECT COALESCE(MAX(sort_order), 0) + 1 FROM {$prefix}member_grades")->fetchColumn();
                    $benefitsJson = $benefits ? json_encode($benefits, JSON_UNESCAPED_UNICODE) : null;
                    $stmt = $pdo->prepare("INSERT INTO {$prefix}member_grades (id, name, slug, color, discount_rate, point_rate, min_reservations, min_spent, benefits, sort_order) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
                    $stmt->execute([$id, $name, $slug, $color, $discountRate, $pointRate, $minReservations, $minSpent, $benefitsJson, $maxSort]);

                    echo json_encode(['success' => true, 'message' => __('members.groups.success.created'), 'id' => $id]);
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
                        echo json_encode(['success' => false, 'message' => __('members.groups.error.name_required')]);
                        exit;
                    }

                    // slug 중복 체크 (자기 제외)
                    $chk = $pdo->prepare("SELECT id FROM {$prefix}member_grades WHERE slug = ? AND id != ?");
                    $chk->execute([$slug, $id]);
                    if ($chk->fetch()) {
                        echo json_encode(['success' => false, 'message' => __('members.groups.error.slug_duplicate')]);
                        exit;
                    }

                    $benefitsJson = $benefits ? json_encode($benefits, JSON_UNESCAPED_UNICODE) : null;
                    $stmt = $pdo->prepare("UPDATE {$prefix}member_grades SET name=?, slug=?, color=?, discount_rate=?, point_rate=?, min_reservations=?, min_spent=?, benefits=? WHERE id=?");
                    $stmt->execute([$name, $slug, $color, $discountRate, $pointRate, $minReservations, $minSpent, $benefitsJson, $id]);

                    echo json_encode(['success' => true, 'message' => __('members.groups.success.updated')]);
                    exit;

                case 'delete_grade':
                    $id = trim($_POST['id'] ?? '');

                    // 기본 등급 삭제 방지
                    $chk = $pdo->prepare("SELECT is_default FROM {$prefix}member_grades WHERE id = ?");
                    $chk->execute([$id]);
                    $grade = $chk->fetch(PDO::FETCH_ASSOC);
                    if ($grade && $grade['is_default']) {
                        echo json_encode(['success' => false, 'message' => __('members.groups.error.cannot_delete_default')]);
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
                    echo json_encode(['success' => true, 'message' => __('members.groups.success.deleted')]);
                    exit;

                case 'reorder':
                    $ids = $_POST['ids'] ?? [];
                    if (is_array($ids) && count($ids) > 0) {
                        $stmt = $pdo->prepare("UPDATE {$prefix}member_grades SET sort_order = ? WHERE id = ?");
                        foreach ($ids as $order => $gid) {
                            $stmt->execute([(int)$order, $gid]);
                        }
                    }
                    echo json_encode(['success' => true, 'message' => __('members.groups.success.reordered')]);
                    exit;

                case 'reset_to_default':
                    // 기본 제공 데이터로 초기화
                    $pdo->beginTransaction();
                    try {
                        // 기존 그룹 전체 삭제
                        $pdo->exec("DELETE FROM {$prefix}member_grades");
                        // 다국어 데이터 삭제
                        $pdo->exec("DELETE FROM {$prefix}translations WHERE lang_key LIKE 'member_grade.%'");

                        // 기본 그룹 데이터
                        $defaults = [
                            ['c62bc9ff-13dc-11f1-8b4f-8447092025d9','일반','normal','#5c8dff',1.00,0.10,0,0.00,1,0],
                            ['c998e8e0b3805ed51bfb11c7bf68c145a987','실버','silver','#bdbdbd',2.00,0.50,1,0.00,0,1],
                            ['0f4ed0fb2b6c6087c2a73c92ffc8c11e05ef','골드','gold','#ffd500',3.00,1.00,1,0.00,0,2],
                            ['5b9ac4fb8281987c5994b3ec40ba1f912b31','플래티넘','platinum','#662eff',4.00,2.00,1,0.00,0,3],
                            ['b53dc7275b348c3526bc1921c84e0b2e9f88','VIP','vip','#ff528e',5.00,4.00,2,0.00,0,4],
                            ['6e3582138e58938eac9cb3d3e7845710bb1e','VVIP','vvip','#ff0033',10.00,5.00,1,0.00,0,5],
                            ['c3df1c9720a647c7d91c0c804e66ac044af9','스태프','staff','#00c203',10.00,5.00,2,0.00,0,6],
                        ];
                        $ins = $pdo->prepare("INSERT INTO {$prefix}member_grades (id,name,slug,color,discount_rate,point_rate,min_reservations,min_spent,is_default,sort_order) VALUES (?,?,?,?,?,?,?,?,?,?)");
                        foreach ($defaults as $d) $ins->execute($d);

                        // 회원 grade_id를 기본(일반)으로 리셋
                        $pdo->exec("UPDATE {$prefix}users SET grade_id = 'c62bc9ff-13dc-11f1-8b4f-8447092025d9'");

                        // 다국어 번역 데이터
                        $trIns = $pdo->prepare("INSERT INTO {$prefix}translations (lang_key, locale, source_locale, content) VALUES (?, ?, 'ko', ?)");
                        $translations = [
                            'c62bc9ff-13dc-11f1-8b4f-8447092025d9' => ['ko'=>'일반','en'=>'Normal','ja'=>'一般','zh_CN'=>'普通','zh_TW'=>'一般','de'=>'Normal','es'=>'Normal','fr'=>'Normal','id'=>'Normal','mn'=>'Энгийн','ru'=>'Обычный','tr'=>'Normal','vi'=>'Thường'],
                            'c998e8e0b3805ed51bfb11c7bf68c145a987' => ['ko'=>'실버','en'=>'Silver','ja'=>'シルバー','zh_CN'=>'银卡','zh_TW'=>'銀卡','de'=>'Silber','es'=>'Plata','fr'=>'Argent','id'=>'Silver','mn'=>'Мөнгөн','ru'=>'Серебро','tr'=>'Gümüş','vi'=>'Bạc'],
                            '0f4ed0fb2b6c6087c2a73c92ffc8c11e05ef' => ['ko'=>'골드','en'=>'Gold','ja'=>'ゴールド','zh_CN'=>'金卡','zh_TW'=>'金卡','de'=>'Gold','es'=>'Oro','fr'=>'Or','id'=>'Gold','mn'=>'Алтан','ru'=>'Золото','tr'=>'Altın','vi'=>'Vàng'],
                            '5b9ac4fb8281987c5994b3ec40ba1f912b31' => ['ko'=>'플래티넘','en'=>'Platinum','ja'=>'プラチナ','zh_CN'=>'铂金','zh_TW'=>'白金','de'=>'Platin','es'=>'Platino','fr'=>'Platine','id'=>'Platinum','mn'=>'Платинум','ru'=>'Платина','tr'=>'Platin','vi'=>'Bạch kim'],
                            'b53dc7275b348c3526bc1921c84e0b2e9f88' => ['ko'=>'VIP','en'=>'VIP','ja'=>'VIP','zh_CN'=>'VIP','zh_TW'=>'VIP','de'=>'VIP','es'=>'VIP','fr'=>'VIP','id'=>'VIP','mn'=>'VIP','ru'=>'VIP','tr'=>'VIP','vi'=>'VIP'],
                            '6e3582138e58938eac9cb3d3e7845710bb1e' => ['ko'=>'VVIP','en'=>'VVIP','ja'=>'VVIP','zh_CN'=>'VVIP','zh_TW'=>'VVIP','de'=>'VVIP','es'=>'VVIP','fr'=>'VVIP','id'=>'VVIP','mn'=>'VVIP','ru'=>'VVIP','tr'=>'VVIP','vi'=>'VVIP'],
                            'c3df1c9720a647c7d91c0c804e66ac044af9' => ['ko'=>'스태프','en'=>'Staff','ja'=>'スタッフ','zh_CN'=>'员工','zh_TW'=>'員工','de'=>'Mitarbeiter','es'=>'Personal','fr'=>'Personnel','id'=>'Staf','mn'=>'Ажилтан','ru'=>'Персонал','tr'=>'Personel','vi'=>'Nhân viên'],
                        ];
                        foreach ($translations as $gid => $langs) {
                            foreach ($langs as $locale => $content) {
                                $trIns->execute(["member_grade.{$gid}.name", $locale, $content]);
                            }
                        }

                        $pdo->commit();
                        echo json_encode(['success' => true, 'message' => __('members.groups.success.reset')]);
                    } catch (\Exception $e) {
                        $pdo->rollBack();
                        echo json_encode(['success' => false, 'message' => $e->getMessage()]);
                    }
                    exit;

                case 'set_default':
                    $id = trim($_POST['id'] ?? '');
                    $pdo->exec("UPDATE {$prefix}member_grades SET is_default = 0");
                    $pdo->prepare("UPDATE {$prefix}member_grades SET is_default = 1 WHERE id = ?")->execute([$id]);
                    echo json_encode(['success' => true, 'message' => __('members.groups.success.default_changed')]);
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

    // 등급 다국어 번역 로드
    $currentLocale = $config['locale'] ?? 'ko';
    $gradeTranslations = [];
    $trStmt = $pdo->prepare("SELECT lang_key, content FROM {$prefix}translations WHERE lang_key LIKE 'member_grade.%' AND locale = ?");
    $trStmt->execute([$currentLocale]);
    while ($tr = $trStmt->fetch(PDO::FETCH_ASSOC)) {
        // member_grade.{id}.{field} → $gradeTranslations[id][field]
        $parts = explode('.', $tr['lang_key'], 3);
        if (count($parts) === 3) {
            $gradeTranslations[$parts[1]][$parts[2]] = $tr['content'];
        }
    }

    $dbConnected = true;
} catch (PDOException $e) {
    $dbConnected = false;
    $dbError = $e->getMessage();
    $grades = [];
    $gradeMemberCounts = [];
}
?>
<?php $pageHeaderTitle = __('members.groups.title'); ?>
<?php $pageSubTitle = __('members.groups.title'); ?>
<?php $pageSubDesc = __('members.groups.description'); ?>
<?php include __DIR__ . '/../reservations/_head.php'; ?>
    <script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
    <style>
        .sortable-ghost { opacity: 0.4; }
        .sortable-chosen { box-shadow: 0 10px 25px rgba(0,0,0,0.15); transform: scale(1.02); }
        .grade-drag-handle:hover { opacity: 1 !important; }
    </style>
                <div id="alertBox" class="hidden mb-6 p-4 rounded-lg border"></div>

                <!-- 액션 버튼 -->
                <div class="flex justify-end mb-6">
                    <div class="flex items-center gap-2">
                        <button onclick="resetGradesToDefault()"
                                class="inline-flex items-center gap-2 px-4 py-2 border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 text-sm font-medium rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
                            <?= __('members.groups.reset_default') ?>
                        </button>
                        <button onclick="openGradeModal()"
                                class="inline-flex items-center gap-2 px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white text-sm font-medium rounded-lg transition-colors">
                            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <?= __('members.groups.create') ?>
                        </button>
                    </div>
                </div>

                <!-- 등급 카드 목록 -->
                <div id="gradeCardGrid" class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                    <?php foreach ($grades as $g): ?>
                    <div id="grade-<?= htmlspecialchars($g['id']) ?>" class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden grade-card" data-id="<?= htmlspecialchars($g['id']) ?>">
                        <!-- 상단 컬러 바 -->
                        <div class="h-1.5" style="background-color: <?= htmlspecialchars($g['color']) ?>"></div>
                        <div class="p-5">
                            <div class="flex items-center justify-between mb-3">
                                <div class="flex items-center gap-2">
                                    <!-- 드래그 핸들 -->
                                    <div class="grade-drag-handle cursor-grab active:cursor-grabbing p-1 -ml-1 text-zinc-300 dark:text-zinc-600 hover:text-zinc-500 dark:hover:text-zinc-400 transition-colors" title="<?= __('members.groups.drag_to_reorder') ?>">
                                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
                                    </div>
                                    <span class="w-3 h-3 rounded-full flex-shrink-0" style="background-color: <?= htmlspecialchars($g['color']) ?>"></span>
                                    <?php $gradeName = $gradeTranslations[$g['id']]['name'] ?? $g['name']; ?>
                                    <h3 class="font-semibold text-zinc-900 dark:text-white"><?= htmlspecialchars($gradeName) ?></h3>
                                    <?php if ($g['is_default']): ?>
                                    <span class="px-1.5 py-0.5 rounded text-[10px] font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-300"><?= __('members.groups.default') ?></span>
                                    <?php endif; ?>
                                </div>
                                <span class="text-xs text-zinc-400"><?= htmlspecialchars($g['slug']) ?></span>
                            </div>

                            <!-- 통계 -->
                            <div class="grid grid-cols-3 gap-2 mb-4">
                                <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-lg p-2.5 text-center">
                                    <div class="text-lg font-bold text-zinc-900 dark:text-white"><?= $gradeMemberCounts[$g['id']] ?? 0 ?></div>
                                    <div class="text-[11px] text-zinc-500 dark:text-zinc-400"><?= __('members.groups.member_count') ?></div>
                                </div>
                                <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-lg p-2.5 text-center">
                                    <div class="text-lg font-bold text-zinc-900 dark:text-white"><?= number_format($g['discount_rate'], 1) ?>%</div>
                                    <div class="text-[11px] text-zinc-500 dark:text-zinc-400"><?= __('members.groups.fields.discount_rate') ?></div>
                                </div>
                                <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-lg p-2.5 text-center">
                                    <div class="text-lg font-bold text-zinc-900 dark:text-white"><?= number_format($g['point_rate'], 1) ?>%</div>
                                    <div class="text-[11px] text-zinc-500 dark:text-zinc-400"><?= __('members.groups.fields.point_rate') ?></div>
                                </div>
                            </div>

                            <!-- 혜택 요약 -->
                            <div class="text-xs text-zinc-500 dark:text-zinc-400 space-y-1 mb-4">
                                <div class="flex justify-between">
                                    <span><?= __('members.groups.fields.min_reservations') ?></span>
                                    <span class="font-medium text-zinc-700 dark:text-zinc-300"><?= $g['min_reservations'] ?></span>
                                </div>
                                <div class="flex justify-between">
                                    <span><?= __('members.groups.fields.min_spent') ?></span>
                                    <span class="font-medium text-zinc-700 dark:text-zinc-300"><?= number_format($g['min_spent']) ?></span>
                                </div>
                            </div>

                            <!-- 버튼 -->
                            <div class="flex items-center gap-2 pt-3 border-t border-zinc-100 dark:border-zinc-700">
                                <?php
                                    $gData = $g;
                                    $gData['_tr_name'] = $gradeTranslations[$g['id']]['name'] ?? $g['name'];
                                    $gData['_tr_benefits'] = $gradeTranslations[$g['id']]['benefits'] ?? ($g['benefits'] ?? '');
                                ?>
                                <button onclick='editGrade(<?= json_encode($gData, JSON_HEX_APOS | JSON_HEX_QUOT | JSON_UNESCAPED_UNICODE) ?>)'
                                        class="flex-1 text-center py-1.5 text-xs font-medium text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition-colors">
                                    <?= __('members.groups.edit') ?>
                                </button>
                                <?php if (!$g['is_default']): ?>
                                <button onclick="setDefault('<?= htmlspecialchars($g['id']) ?>')"
                                        class="flex-1 text-center py-1.5 text-xs font-medium text-purple-600 hover:bg-purple-50 dark:hover:bg-purple-900/20 rounded-lg transition-colors">
                                    <?= __('members.groups.set_default') ?>
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
                        <p><?= __('members.groups.empty') ?></p>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
        </main>
    </div>

    <?php include __DIR__ . '/groups-form.php'; ?>

    <script src="https://code.jquery.com/jquery-3.7.1.min.js"></script>
    <?php include BASE_PATH . '/resources/views/admin/components/multilang-modal.php'; ?>

    <?php include __DIR__ . '/groups-js.php'; ?>
    </div>
    </main>
</div>
</body>
</html>
