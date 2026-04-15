<?php
/**
 * License Manager - 정산 관리
 */
$pageHeaderTitle = 'License Manager';
include __DIR__ . '/_head.php';

// 미정산 개발자 목록
$pendingDevs = $_lmPdo->query("SELECT * FROM vcs_developers WHERE pending_balance > 0 ORDER BY pending_balance DESC")->fetchAll();

// 최근 지급 내역
$recentPayouts = $_lmPdo->query(
    "SELECT p.*, d.name as dev_name, d.email as dev_email
     FROM vcs_developer_payouts p
     JOIN vcs_developers d ON d.id = p.developer_id
     ORDER BY p.created_at DESC LIMIT 30"
)->fetchAll();

// 총 매출/수수료 통계
$totalGross = (float)$_lmPdo->query("SELECT COALESCE(SUM(gross_amount), 0) FROM vcs_developer_earnings")->fetchColumn();
$totalCommission = (float)$_lmPdo->query("SELECT COALESCE(SUM(commission), 0) FROM vcs_developer_earnings")->fetchColumn();
$totalPaid = (float)$_lmPdo->query("SELECT COALESCE(SUM(amount), 0) FROM vcs_developer_payouts WHERE status = 'completed'")->fetchColumn();
$totalPending = (float)$_lmPdo->query("SELECT COALESCE(SUM(pending_balance), 0) FROM vcs_developers")->fetchColumn();
?>

<div class="mb-6 flex items-center justify-between">
    <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= __lm('payouts') ?></h1>
    </div>
    <a href="<?= $adminUrl ?>/license-manager" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline"><?= __lm('back_dashboard') ?></a>
</div>

<!-- 통계 -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-5">
        <p class="text-xs text-zinc-500 uppercase"><?= __lm('total_sales') ?></p>
        <p class="text-xl font-bold text-zinc-900 dark:text-white mt-1">$<?= number_format($totalGross, 2) ?></p>
    </div>
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-5">
        <p class="text-xs text-indigo-600 uppercase"><?= __lm('commission_revenue') ?></p>
        <p class="text-xl font-bold text-indigo-600 mt-1">$<?= number_format($totalCommission, 2) ?></p>
    </div>
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-5">
        <p class="text-xs text-green-600 uppercase"><?= __lm('total_paid') ?></p>
        <p class="text-xl font-bold text-green-600 mt-1">$<?= number_format($totalPaid, 2) ?></p>
    </div>
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-5">
        <p class="text-xs text-amber-600 uppercase"><?= __lm('pending_balance') ?></p>
        <p class="text-xl font-bold text-amber-600 mt-1">$<?= number_format($totalPending, 2) ?></p>
    </div>
</div>

<!-- 미정산 개발자 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
        <h3 class="font-semibold text-zinc-800 dark:text-zinc-200"><?= __lm('pending_developers') ?></h3>
    </div>
    <?php if (empty($pendingDevs)): ?>
    <p class="px-6 py-8 text-center text-zinc-400 text-sm"><?= __lm('no_pending') ?></p>
    <?php else: ?>
    <table class="w-full text-sm">
        <thead class="bg-zinc-50 dark:bg-zinc-700/50 text-zinc-500">
            <tr>
                <th class="px-4 py-3 text-left font-medium">Developer</th>
                <th class="px-4 py-3 text-left font-medium">Email</th>
                <th class="px-4 py-3 text-right font-medium">Total Earned</th>
                <th class="px-4 py-3 text-right font-medium">Total Paid</th>
                <th class="px-4 py-3 text-right font-medium">Pending</th>
                <th class="px-4 py-3 text-center font-medium">Action</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
            <?php foreach ($pendingDevs as $dev): ?>
            <tr>
                <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($dev['name']) ?></td>
                <td class="px-4 py-3 text-zinc-500"><?= htmlspecialchars($dev['email']) ?></td>
                <td class="px-4 py-3 text-right">$<?= number_format((float)$dev['total_earnings'], 2) ?></td>
                <td class="px-4 py-3 text-right text-green-600">$<?= number_format((float)$dev['total_paid'], 2) ?></td>
                <td class="px-4 py-3 text-right font-bold text-amber-600">$<?= number_format((float)$dev['pending_balance'], 2) ?></td>
                <td class="px-4 py-3 text-center">
                    <button onclick="processPayout(<?= $dev['id'] ?>, '<?= htmlspecialchars($dev['name']) ?>', <?= (float)$dev['pending_balance'] ?>)"
                            class="px-3 py-1 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition"><?= __lm('payout_btn') ?></button>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- 지급 이력 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
        <h3 class="font-semibold text-zinc-800 dark:text-zinc-200"><?= __lm('payout_history') ?></h3>
    </div>
    <?php if (empty($recentPayouts)): ?>
    <p class="px-6 py-8 text-center text-zinc-400 text-sm"><?= __lm('no_payouts') ?></p>
    <?php else: ?>
    <table class="w-full text-sm">
        <thead class="bg-zinc-50 dark:bg-zinc-700/50 text-zinc-500">
            <tr>
                <th class="px-4 py-3 text-left font-medium">Developer</th>
                <th class="px-4 py-3 text-right font-medium">Amount</th>
                <th class="px-4 py-3 text-center font-medium">Method</th>
                <th class="px-4 py-3 text-center font-medium">Period</th>
                <th class="px-4 py-3 text-center font-medium">Status</th>
                <th class="px-4 py-3 text-center font-medium">Date</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700">
            <?php foreach ($recentPayouts as $po): ?>
            <tr>
                <td class="px-4 py-3 text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($po['dev_name']) ?></td>
                <td class="px-4 py-3 text-right font-medium">$<?= number_format((float)$po['amount'], 2) ?></td>
                <td class="px-4 py-3 text-center text-xs"><?= strtoupper($po['method']) ?></td>
                <td class="px-4 py-3 text-center text-xs text-zinc-400"><?= $po['period_start'] ?> ~ <?= $po['period_end'] ?></td>
                <td class="px-4 py-3 text-center">
                    <span class="px-2 py-0.5 text-xs rounded-full bg-<?= $po['status'] === 'completed' ? 'green' : 'yellow' ?>-100 text-<?= $po['status'] === 'completed' ? 'green' : 'yellow' ?>-700"><?= ucfirst($po['status']) ?></span>
                </td>
                <td class="px-4 py-3 text-center text-xs text-zinc-400"><?= $po['created_at'] ? date('Y-m-d', strtotime($po['created_at'])) : '-' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<script>
function processPayout(devId, name, amount) {
    const method = prompt(name + '에게 $' + amount.toFixed(2) + ' 지급\n\n지급 방법을 입력하세요 (bank / stripe / paypal):');
    if (!method) return;
    const ref = prompt('참조번호 (송금번호 등):') || '';
    fetch('<?= $adminUrl ?>/license-manager/api', { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=process_payout&developer_id='+devId+'&method='+encodeURIComponent(method)+'&reference='+encodeURIComponent(ref)
    }).then(r=>r.json()).then(d => { if(d.success) { alert('<?= __lm("payout_complete") ?>'); location.reload(); } else alert(d.message||'Error'); });
}
</script>

<?php include BASE_PATH . '/resources/views/admin/reservations/_foot.php'; ?>
