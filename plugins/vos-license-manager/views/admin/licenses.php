<?php
/**
 * License Manager - 라이선스 목록
 */
$pageHeaderTitle = 'License Manager';
include __DIR__ . '/_head.php';

$search = trim($_GET['q'] ?? '');
$statusFilter = $_GET['status'] ?? '';
$planFilter = $_GET['plan'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;
$offset = ($page - 1) * $perPage;

$where = ['1=1'];
$params = [];

if ($search) {
    $where[] = "(domain LIKE ? OR license_key LIKE ?)";
    $params[] = "%{$search}%";
    $params[] = "%{$search}%";
}
if ($statusFilter) {
    $where[] = "status = ?";
    $params[] = $statusFilter;
}
if ($planFilter) {
    $where[] = "plan = ?";
    $params[] = $planFilter;
}

$whereClause = implode(' AND ', $where);
$total = (int)$_lmPdo->prepare("SELECT COUNT(*) FROM vcs_licenses WHERE {$whereClause}")->execute($params) ? $_lmPdo->query("SELECT FOUND_ROWS()")->fetchColumn() : 0;
$countStmt = $_lmPdo->prepare("SELECT COUNT(*) FROM vcs_licenses WHERE {$whereClause}");
$countStmt->execute($params);
$total = (int)$countStmt->fetchColumn();
$totalPages = ceil($total / $perPage);

$stmt = $_lmPdo->prepare("SELECT * FROM vcs_licenses WHERE {$whereClause} ORDER BY registered_at DESC LIMIT {$perPage} OFFSET {$offset}");
$stmt->execute($params);
$licenses = $stmt->fetchAll();

// 상태별 카운트
$statusCounts = $_lmPdo->query("SELECT status, COUNT(*) as cnt FROM vcs_licenses GROUP BY status")->fetchAll(PDO::FETCH_KEY_PAIR);
$allCount = array_sum($statusCounts);
?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= __lm('licenses') ?></h1>
        <p class="text-sm text-zinc-500 mt-1"><?= number_format($total) ?>개</p>
    </div>
    <a href="<?= $adminUrl ?>/license-manager" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline"><?= __lm('back_dashboard') ?></a>
</div>

<!-- 검색 + 필터 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-4 mb-6">
    <form method="GET" action="<?= $adminUrl ?>/license-manager/licenses" class="flex flex-wrap gap-3">
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="<?= __lm('license_search') ?>"
               class="flex-1 min-w-[200px] px-3 py-2 bg-zinc-50 dark:bg-zinc-700 border border-zinc-200 dark:border-zinc-600 rounded-lg text-sm text-zinc-800 dark:text-zinc-200">
        <select name="status" class="px-3 py-2 bg-zinc-50 dark:bg-zinc-700 border border-zinc-200 dark:border-zinc-600 rounded-lg text-sm text-zinc-700 dark:text-zinc-300">
            <option value=""><?= __lm('all_status') ?> (<?= $allCount ?>)</option>
            <option value="active" <?= $statusFilter === 'active' ? 'selected' : '' ?>>Active (<?= $statusCounts['active'] ?? 0 ?>)</option>
            <option value="suspended" <?= $statusFilter === 'suspended' ? 'selected' : '' ?>>Suspended (<?= $statusCounts['suspended'] ?? 0 ?>)</option>
            <option value="revoked" <?= $statusFilter === 'revoked' ? 'selected' : '' ?>>Revoked (<?= $statusCounts['revoked'] ?? 0 ?>)</option>
        </select>
        <select name="plan" class="px-3 py-2 bg-zinc-50 dark:bg-zinc-700 border border-zinc-200 dark:border-zinc-600 rounded-lg text-sm text-zinc-700 dark:text-zinc-300">
            <option value=""><?= __lm('all_plans') ?></option>
            <option value="free" <?= $planFilter === 'free' ? 'selected' : '' ?>>Free</option>
            <option value="standard" <?= $planFilter === 'standard' ? 'selected' : '' ?>>Standard</option>
            <option value="professional" <?= $planFilter === 'professional' ? 'selected' : '' ?>>Professional</option>
            <option value="enterprise" <?= $planFilter === 'enterprise' ? 'selected' : '' ?>>Enterprise</option>
        </select>
        <button type="submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition"><?= __lm('search') ?></button>
    </form>
</div>

<!-- 목록 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-zinc-50 dark:bg-zinc-700/50 text-zinc-500 dark:text-zinc-400">
            <tr>
                <th class="px-4 py-3 text-left font-medium">Domain</th>
                <th class="px-4 py-3 text-left font-medium">License Key</th>
                <th class="px-4 py-3 text-center font-medium">Plan</th>
                <th class="px-4 py-3 text-center font-medium">Status</th>
                <th class="px-4 py-3 text-center font-medium">Plugins</th>
                <th class="px-4 py-3 text-center font-medium">Version</th>
                <th class="px-4 py-3 text-center font-medium">Registered</th>
                <th class="px-4 py-3 text-center font-medium">Last Verified</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
            <?php foreach ($licenses as $lic):
                $sc = ['active'=>'green','suspended'=>'yellow','revoked'=>'red'][$lic['status']] ?? 'zinc';
                $pc = ['free'=>'blue','standard'=>'green','professional'=>'purple','enterprise'=>'amber'][$lic['plan']] ?? 'zinc';
                $pluginCount = (int)$_lmPdo->prepare("SELECT COUNT(*) FROM vcs_license_plugins WHERE license_id = ? AND status = 'active'")->execute([$lic['id']]) ? 0 : 0;
                $pcStmt = $_lmPdo->prepare("SELECT COUNT(*) FROM vcs_license_plugins WHERE license_id = ? AND status = 'active'");
                $pcStmt->execute([$lic['id']]);
                $pluginCount = (int)$pcStmt->fetchColumn();
            ?>
            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                <td class="px-4 py-3">
                    <a href="<?= $adminUrl ?>/license-manager/license?key=<?= urlencode($lic['license_key']) ?>" class="font-medium text-indigo-600 dark:text-indigo-400 hover:underline">
                        <?= htmlspecialchars($lic['domain']) ?>
                    </a>
                </td>
                <td class="px-4 py-3 font-mono text-xs text-zinc-500"><?= htmlspecialchars($lic['license_key']) ?></td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-<?= $pc ?>-100 text-<?= $pc ?>-700 dark:bg-<?= $pc ?>-900/30 dark:text-<?= $pc ?>-400"><?= ucfirst($lic['plan']) ?></span>
                </td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-0.5 text-xs font-medium rounded-full bg-<?= $sc ?>-100 text-<?= $sc ?>-700 dark:bg-<?= $sc ?>-900/30 dark:text-<?= $sc ?>-400"><?= ucfirst($lic['status']) ?></span>
                </td>
                <td class="px-4 py-3 text-center text-zinc-600 dark:text-zinc-400"><?= $pluginCount ?></td>
                <td class="px-4 py-3 text-center text-xs text-zinc-500"><?= htmlspecialchars($lic['voscms_version'] ?? '-') ?></td>
                <td class="px-4 py-3 text-center text-xs text-zinc-400"><?= $lic['registered_at'] ? date('Y-m-d', strtotime($lic['registered_at'])) : '-' ?></td>
                <td class="px-4 py-3 text-center text-xs text-zinc-400"><?= $lic['last_verified_at'] ? date('m-d H:i', strtotime($lic['last_verified_at'])) : '-' ?></td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($licenses)): ?>
            <tr><td colspan="8" class="px-4 py-12 text-center text-zinc-400"><?= __lm('no_licenses') ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- 페이지네이션 -->
<?php if ($totalPages > 1): ?>
<nav class="flex justify-center mt-6 gap-1">
    <?php for ($p = max(1, $page-4); $p <= min($totalPages, $page+4); $p++): ?>
    <a href="<?= $adminUrl ?>/license-manager/licenses?page=<?= $p ?><?= $search ? '&q=' . urlencode($search) : '' ?><?= $statusFilter ? '&status=' . $statusFilter : '' ?><?= $planFilter ? '&plan=' . $planFilter : '' ?>"
       class="w-9 h-9 flex items-center justify-center rounded-lg text-sm <?= $p === $page ? 'bg-indigo-600 text-white' : 'text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-700' ?>"><?= $p ?></a>
    <?php endfor; ?>
</nav>
<?php endif; ?>

<?php include BASE_PATH . '/resources/views/admin/reservations/_foot.php'; ?>
