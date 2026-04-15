<?php
/**
 * License Manager - 개발자 관리
 */
$pageHeaderTitle = 'License Manager';
include __DIR__ . '/_head.php';

$search = trim($_GET['q'] ?? '');
$where = $search ? "WHERE name LIKE ? OR email LIKE ?" : "";
$params = $search ? ["%{$search}%", "%{$search}%"] : [];

$stmt = $_lmPdo->prepare("SELECT * FROM vcs_developers {$where} ORDER BY created_at DESC LIMIT 50");
$stmt->execute($params);
$developers = $stmt->fetchAll();
?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= __lm('developers') ?></h1>
        <p class="text-sm text-zinc-500 mt-1"><?= count($developers) ?>명</p>
    </div>
    <a href="<?= $adminUrl ?>/license-manager" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline"><?= __lm('back_dashboard') ?></a>
</div>

<!-- 검색 -->
<form method="GET" action="<?= $adminUrl ?>/license-manager/developers" class="mb-6">
    <div class="flex gap-2">
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="<?= __lm('dev_search') ?>"
               class="flex-1 px-3 py-2 bg-white dark:bg-zinc-700 border border-zinc-200 dark:border-zinc-600 rounded-lg text-sm text-zinc-800 dark:text-zinc-200">
        <button type="submit" class="px-4 py-2 bg-indigo-600 text-white text-sm rounded-lg hover:bg-indigo-700">검색</button>
    </div>
</form>

<!-- 목록 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-zinc-50 dark:bg-zinc-700/50 text-zinc-500 dark:text-zinc-400">
            <tr>
                <th class="px-4 py-3 text-left font-medium">Name</th>
                <th class="px-4 py-3 text-left font-medium">Email</th>
                <th class="px-4 py-3 text-center font-medium">Type</th>
                <th class="px-4 py-3 text-center font-medium">Status</th>
                <th class="px-4 py-3 text-right font-medium">Earnings</th>
                <th class="px-4 py-3 text-right font-medium">Pending</th>
                <th class="px-4 py-3 text-center font-medium">Registered</th>
                <th class="px-4 py-3 text-center font-medium">Actions</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
            <?php foreach ($developers as $dev):
                $sc = ['active'=>'green','pending'=>'yellow','suspended'=>'red','banned'=>'zinc'][$dev['status']] ?? 'zinc';
            ?>
            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($dev['name']) ?></td>
                <td class="px-4 py-3 text-zinc-500"><?= htmlspecialchars($dev['email']) ?></td>
                <td class="px-4 py-3 text-center text-xs"><?= ucfirst($dev['type']) ?></td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-0.5 text-xs rounded-full bg-<?= $sc ?>-100 text-<?= $sc ?>-700 dark:bg-<?= $sc ?>-900/30 dark:text-<?= $sc ?>-400"><?= ucfirst($dev['status']) ?></span>
                </td>
                <td class="px-4 py-3 text-right text-zinc-600 dark:text-zinc-400">$<?= number_format((float)$dev['total_earnings'], 2) ?></td>
                <td class="px-4 py-3 text-right text-indigo-600 dark:text-indigo-400">$<?= number_format((float)$dev['pending_balance'], 2) ?></td>
                <td class="px-4 py-3 text-center text-xs text-zinc-400"><?= date('Y-m-d', strtotime($dev['created_at'])) ?></td>
                <td class="px-4 py-3 text-center">
                    <div class="flex justify-center gap-1">
                        <?php if ($dev['status'] === 'active'): ?>
                        <button onclick="devAction(<?= $dev['id'] ?>, 'suspend')" class="text-xs text-yellow-600 hover:underline"><?= __lm('dev_suspend') ?></button>
                        <?php elseif ($dev['status'] === 'suspended'): ?>
                        <button onclick="devAction(<?= $dev['id'] ?>, 'activate')" class="text-xs text-green-600 hover:underline"><?= __lm('dev_activate') ?></button>
                        <?php endif; ?>
                        <?php if ($dev['status'] !== 'banned'): ?>
                        <button onclick="devAction(<?= $dev['id'] ?>, 'ban')" class="text-xs text-red-500 hover:underline"><?= __lm('dev_ban') ?></button>
                        <?php endif; ?>
                    </div>
                </td>
            </tr>
            <?php endforeach; ?>
            <?php if (empty($developers)): ?>
            <tr><td colspan="8" class="px-4 py-12 text-center text-zinc-400"><?= __lm('no_developers') ?></td></tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<script>
function devAction(devId, action) {
    const labels = {suspend:'<?= __lm("dev_suspend") ?>',activate:'<?= __lm("dev_activate") ?>',ban:'<?= __lm("dev_ban") ?>'};
    if (!confirm(labels[action] + ' 하시겠습니까?')) return;
    fetch('<?= $adminUrl ?>/license-manager/api', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=dev_status&developer_id='+devId+'&status='+action
    }).then(r=>r.json()).then(d => { if(d.success) location.reload(); else alert(d.message||'Error'); });
}
</script>

<?php include BASE_PATH . '/resources/views/admin/reservations/_foot.php'; ?>
