<?php
/**
 * VosCMS Admin - 마켓플레이스 심사 큐
 * 개발자가 제출한 플러그인/위젯을 심사 (설치 테스트 → 승인/반려)
 */

$pageTitle = '심사 큐 - ' . ($config['app_name'] ?? 'VosCMS') . ' Admin';
$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('DB Error');
}

$locale = $config['locale'] ?? 'ko';

// ── POST 처리 (승인/반려) ──
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');
    $action = $_POST['action'] ?? '';
    $queueId = (int) ($_POST['queue_id'] ?? 0);
    $reviewerNotes = trim($_POST['reviewer_notes'] ?? '');
    $rejectionReason = trim($_POST['rejection_reason'] ?? '');

    if (!$queueId) {
        echo json_encode(['success' => false, 'message' => 'Invalid queue ID']);
        exit;
    }

    $q = $pdo->prepare("SELECT * FROM vcs_review_queue WHERE id = ?");
    $q->execute([$queueId]);
    $item = $q->fetch(PDO::FETCH_ASSOC);

    if (!$item) {
        echo json_encode(['success' => false, 'message' => 'Item not found']);
        exit;
    }

    if ($action === 'approve') {
        $isUpdate = !empty($item['is_update']) && !empty($item['item_id']);

        if ($isUpdate) {
            // ── 기존 아이템 버전 업데이트 ──
            $mpItemId = (int) $item['item_id'];

            // 새 버전 등록
            $pdo->prepare(
                "INSERT INTO {$prefix}mp_item_versions (item_id, version, changelog, download_url, file_hash, file_size, min_voscms_version, min_php_version, status, released_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'active', NOW())"
            )->execute([$mpItemId, $item['version'], $item['changelog'], $item['package_path'], $item['package_hash'], $item['package_size'], $item['min_voscms'], $item['min_php']]);

            // mp_items의 latest_version 갱신
            $pdo->prepare(
                "UPDATE {$prefix}mp_items SET latest_version = ?, min_voscms_version = COALESCE(?, min_voscms_version), min_php_version = COALESCE(?, min_php_version), updated_at = NOW() WHERE id = ?"
            )->execute([$item['version'], $item['min_voscms'], $item['min_php'], $mpItemId]);

            // 심사 큐 업데이트
            $pdo->prepare(
                "UPDATE vcs_review_queue SET status = 'approved', reviewer_notes = ?, reviewed_at = NOW() WHERE id = ?"
            )->execute([$reviewerNotes ?: null, $queueId]);

            echo json_encode(['success' => true, 'message' => "Version {$item['version']} approved (update)", 'item_id' => $mpItemId]);
            exit;

        } else {
            // ── 신규 아이템 등록 ──
            $slug = strtolower(trim(json_decode($item['name'], true)['en'] ?? 'item-' . $queueId));
            $slug = preg_replace('/[^a-z0-9]+/', '-', $slug);
            $slug = trim($slug, '-') ?: 'item-' . $queueId;

            $slugBase = $slug;
            $slugCount = 1;
            while (true) {
                $chk = $pdo->prepare("SELECT id FROM {$prefix}mp_items WHERE slug = ?");
                $chk->execute([$slug]);
                if (!$chk->fetch()) break;
                $slug = $slugBase . '-' . $slugCount++;
            }

            $devStmt = $pdo->prepare("SELECT name, website FROM vcs_developers WHERE id = ?");
            $devStmt->execute([$item['developer_id']]);
            $dev = $devStmt->fetch(PDO::FETCH_ASSOC);

            $insertStmt = $pdo->prepare(
                "INSERT INTO {$prefix}mp_items
                 (slug, type, name, description, short_description, author_name, author_url,
                  category_id, tags, icon, screenshots, price, currency,
                  latest_version, min_voscms_version, min_php_version, requires_plugins,
                  is_verified, status, created_at, updated_at)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 1, 'active', NOW(), NOW())"
            );
            $insertStmt->execute([
                $slug, $item['item_type'], $item['name'], $item['description'], $item['short_description'],
                $dev['name'] ?? '', $dev['website'] ?? '',
                $item['category_id'], $item['tags'], $item['icon'], $item['screenshots'],
                $item['price'], $item['currency'],
                $item['version'], $item['min_voscms'], $item['min_php'], $item['requires_plugins'],
            ]);
            $mpItemId = (int) $pdo->lastInsertId();

            // 버전 등록
            $pdo->prepare(
                "INSERT INTO {$prefix}mp_item_versions (item_id, version, changelog, download_url, file_hash, file_size, status, released_at)
                 VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())"
            )->execute([$mpItemId, $item['version'], $item['changelog'], $item['package_path'], $item['package_hash'], $item['package_size']]);

            // 심사 큐 업데이트
            $pdo->prepare(
                "UPDATE vcs_review_queue SET status = 'approved', item_id = ?, reviewer_notes = ?, reviewed_at = NOW() WHERE id = ?"
            )->execute([$mpItemId, $reviewerNotes ?: null, $queueId]);

            echo json_encode(['success' => true, 'message' => 'Approved and published', 'item_id' => $mpItemId]);
            exit;
        }

    } elseif ($action === 'reject') {
        if (!$rejectionReason) {
            echo json_encode(['success' => false, 'message' => 'Rejection reason is required']);
            exit;
        }

        $pdo->prepare(
            "UPDATE vcs_review_queue SET status = 'rejected', rejection_reason = ?, reviewer_notes = ?, reviewed_at = NOW() WHERE id = ?"
        )->execute([$rejectionReason, $reviewerNotes ?: null, $queueId]);

        echo json_encode(['success' => true, 'message' => 'Rejected']);
        exit;
    }

    echo json_encode(['success' => false, 'message' => 'Unknown action']);
    exit;
}

// ── 큐 목록 조회 ──
$statusFilter = $_GET['status'] ?? 'pending';
$where = $statusFilter === 'all' ? '1=1' : "rq.status = " . $pdo->quote($statusFilter);

$items = $pdo->query(
    "SELECT rq.*, d.name as dev_name, d.email as dev_email, d.type as dev_type
     FROM vcs_review_queue rq
     JOIN vcs_developers d ON d.id = rq.developer_id
     WHERE {$where}
     ORDER BY rq.submitted_at DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$counts = $pdo->query(
    "SELECT status, COUNT(*) as cnt FROM vcs_review_queue GROUP BY status"
)->fetchAll(PDO::FETCH_KEY_PAIR);

include BASE_PATH . '/resources/views/admin/reservations/_head.php';
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">Review Queue</h1>
    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">개발자가 제출한 아이템을 심사합니다. 설치 테스트 후 승인 또는 반려하세요.</p>
</div>

<!-- 상태 탭 -->
<div class="flex gap-2 mb-6">
    <?php
    $tabs = [
        'pending' => ['대기', 'yellow'], 'reviewing' => ['심사중', 'blue'],
        'approved' => ['승인', 'green'], 'rejected' => ['반려', 'red'], 'all' => ['전체', 'zinc']
    ];
    foreach ($tabs as $s => $t):
        $cnt = ($s === 'all') ? array_sum($counts) : ($counts[$s] ?? 0);
        $active = $statusFilter === $s;
    ?>
    <a href="<?= $adminUrl ?>/review-queue?status=<?= $s ?>"
       class="px-3 py-1.5 text-sm font-medium rounded-lg <?= $active ? "bg-{$t[1]}-100 text-{$t[1]}-700 dark:bg-{$t[1]}-900/30 dark:text-{$t[1]}-400" : 'text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-700' ?>">
        <?= $t[0] ?> <span class="ml-1 text-xs">(<?= $cnt ?>)</span>
    </a>
    <?php endforeach; ?>
</div>

<?php if (empty($items)): ?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-12 text-center text-zinc-400">
    <p>심사 대기 아이템이 없습니다.</p>
</div>
<?php else: ?>
<div class="space-y-4">
    <?php foreach ($items as $item):
        $name = json_decode($item['name'], true);
        $itemName = $name[$locale] ?? $name['en'] ?? '(unnamed)';
        $validation = json_decode($item['validation_result'] ?? '{}', true);
        $typeColors = ['plugin' => 'indigo', 'widget' => 'emerald', 'theme' => 'purple', 'skin' => 'orange'];
        $tc = $typeColors[$item['item_type']] ?? 'zinc';
        $statusColors = ['pending' => 'yellow', 'reviewing' => 'blue', 'approved' => 'green', 'rejected' => 'red', 'revision' => 'amber'];
        $sc = $statusColors[$item['status']] ?? 'zinc';
    ?>
    <div x-data="{ open: false, rejecting: false }" class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <!-- 헤더 -->
        <div class="px-6 py-4 flex items-center justify-between cursor-pointer" @click="open = !open">
            <div class="flex items-center gap-4">
                <div class="flex gap-2">
                    <span class="px-2 py-0.5 text-xs font-bold rounded bg-<?= $tc ?>-100 text-<?= $tc ?>-700 dark:bg-<?= $tc ?>-900/30 dark:text-<?= $tc ?>-400"><?= strtoupper($item['item_type']) ?></span>
                    <?php if (!empty($item['is_update'])): ?>
                    <span class="px-2 py-0.5 text-xs font-bold rounded bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">UPDATE <?= htmlspecialchars($item['previous_version'] ?? '') ?> → <?= htmlspecialchars($item['version']) ?></span>
                    <?php endif; ?>
                    <span class="px-2 py-0.5 text-xs font-bold rounded bg-<?= $sc ?>-100 text-<?= $sc ?>-700 dark:bg-<?= $sc ?>-900/30 dark:text-<?= $sc ?>-400"><?= strtoupper($item['status']) ?></span>
                </div>
                <div>
                    <h3 class="font-semibold text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($itemName) ?> <span class="text-sm text-zinc-400 font-normal">v<?= htmlspecialchars($item['version']) ?></span></h3>
                    <p class="text-xs text-zinc-400 mt-0.5">by <?= htmlspecialchars($item['dev_name']) ?> (<?= htmlspecialchars($item['dev_email']) ?>) &middot; <?= date('Y-m-d H:i', strtotime($item['submitted_at'])) ?></p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <?php if ((float)$item['price'] > 0): ?>
                <span class="text-sm font-bold text-zinc-700 dark:text-zinc-300"><?= number_format((float)$item['price'], 2) ?> <?= $item['currency'] ?></span>
                <?php else: ?>
                <span class="text-sm font-bold text-green-600">FREE</span>
                <?php endif; ?>
                <svg class="w-5 h-5 text-zinc-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>

        <!-- 상세 (펼치기) -->
        <div x-show="open" x-cloak class="border-t border-zinc-200 dark:border-zinc-700 px-6 py-5">
            <div class="grid grid-cols-2 gap-6">
                <!-- 좌측: 정보 -->
                <div class="space-y-3 text-sm">
                    <div><span class="text-zinc-400">Package:</span> <span class="text-zinc-600 dark:text-zinc-300"><?= number_format($item['package_size'] / 1024, 1) ?> KB &middot; SHA-256: <code class="text-xs"><?= substr($item['package_hash'], 0, 16) ?>...</code></span></div>
                    <?php if ($item['min_voscms']): ?><div><span class="text-zinc-400">Min VosCMS:</span> <span class="text-zinc-600 dark:text-zinc-300"><?= htmlspecialchars($item['min_voscms']) ?></span></div><?php endif; ?>
                    <?php if ($item['min_php']): ?><div><span class="text-zinc-400">Min PHP:</span> <span class="text-zinc-600 dark:text-zinc-300"><?= htmlspecialchars($item['min_php']) ?></span></div><?php endif; ?>
                    <?php if ($item['changelog']): ?><div><span class="text-zinc-400">Changelog:</span> <p class="text-zinc-600 dark:text-zinc-300 mt-1"><?= nl2br(htmlspecialchars($item['changelog'])) ?></p></div><?php endif; ?>
                    <?php if ($item['rejection_reason']): ?>
                    <div class="p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                        <span class="text-red-600 dark:text-red-400 font-medium text-xs">반려 사유:</span>
                        <p class="text-red-700 dark:text-red-300 mt-1"><?= nl2br(htmlspecialchars($item['rejection_reason'])) ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- 우측: 검증 결과 -->
                <div>
                    <h4 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 mb-2">Auto Validation</h4>
                    <?php if (!empty($validation['checks'])): ?>
                    <div class="space-y-1">
                        <?php foreach ($validation['checks'] as $check): ?>
                        <p class="text-xs text-green-600 dark:text-green-400 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z" clip-rule="evenodd"/></svg>
                            <?= htmlspecialchars($check) ?>
                        </p>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($validation['warnings'])): ?>
                    <div class="mt-2 space-y-1">
                        <?php foreach ($validation['warnings'] as $warn): ?>
                        <p class="text-xs text-amber-600 dark:text-amber-400">⚠ <?= htmlspecialchars($warn) ?></p>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                    <?php if (!empty($validation['errors'])): ?>
                    <div class="mt-2 space-y-1">
                        <?php foreach ($validation['errors'] as $err): ?>
                        <p class="text-xs text-red-600 dark:text-red-400">✗ <?= htmlspecialchars($err) ?></p>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 심사 액션 (pending/reviewing만) -->
            <?php if (in_array($item['status'], ['pending', 'reviewing'])): ?>
            <div class="mt-5 pt-5 border-t border-zinc-200 dark:border-zinc-700">
                <div class="mb-3">
                    <label class="block text-sm font-medium text-zinc-600 dark:text-zinc-400 mb-1">심사 메모 (내부용)</label>
                    <textarea id="notes-<?= $item['id'] ?>" rows="2" class="w-full px-3 py-2 text-sm bg-zinc-50 dark:bg-zinc-700 border border-zinc-200 dark:border-zinc-600 rounded-lg" placeholder="내부 메모 (개발자에게 보이지 않음)"></textarea>
                </div>

                <!-- 반려 사유 (반려 시만 표시) -->
                <div x-show="rejecting" x-cloak class="mb-3">
                    <label class="block text-sm font-medium text-red-600 dark:text-red-400 mb-1">반려 사유 (개발자에게 전달) *</label>
                    <textarea id="reason-<?= $item['id'] ?>" rows="3" class="w-full px-3 py-2 text-sm bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-700 rounded-lg" placeholder="구체적인 반려 사유를 작성해주세요..."></textarea>
                </div>

                <div class="flex gap-2">
                    <button onclick="reviewAction(<?= $item['id'] ?>, 'approve')"
                            class="px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors">
                        승인 (Approve)
                    </button>
                    <template x-if="!rejecting">
                        <button @click="rejecting = true"
                                class="px-4 py-2 text-sm font-medium text-red-600 bg-red-50 hover:bg-red-100 dark:bg-red-900/20 dark:hover:bg-red-900/30 rounded-lg transition-colors">
                            반려 (Reject)
                        </button>
                    </template>
                    <template x-if="rejecting">
                        <button onclick="reviewAction(<?= $item['id'] ?>, 'reject')"
                                class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition-colors">
                            반려 확정
                        </button>
                    </template>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
function reviewAction(queueId, action) {
    const notes = document.getElementById('notes-' + queueId)?.value || '';
    const reason = document.getElementById('reason-' + queueId)?.value || '';

    if (action === 'reject' && !reason.trim()) {
        alert('반려 사유를 작성해주세요.');
        return;
    }
    if (action === 'approve' && !confirm('이 아이템을 승인하고 마켓플레이스에 공개하시겠습니까?')) return;
    if (action === 'reject' && !confirm('이 아이템을 반려하시겠습니까?')) return;

    const body = new URLSearchParams({ action, queue_id: queueId, reviewer_notes: notes, rejection_reason: reason });

    fetch('<?= $adminUrl ?>/review-queue', { method: 'POST', body })
        .then(r => r.json())
        .then(data => {
            alert(data.message || (data.success ? 'Done' : 'Error'));
            if (data.success) location.reload();
        });
}
</script>

<?php include BASE_PATH . '/resources/views/admin/reservations/_foot.php'; ?>
