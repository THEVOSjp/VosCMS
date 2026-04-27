<?php
include __DIR__ . '/../_head.php';
$pageHeaderTitle = '정산 관리';
$db  = mkt_pdo();
$pfx = $_mktPrefix;
$adminUrl = $_mktAdmin;

// 미정산 파트너 목록
$pendingPartners = $db->query("
    SELECT p.id, p.display_name, p.email, p.pending_balance, p.total_earnings, p.total_paid
      FROM {$pfx}mkt_partners p
     WHERE p.pending_balance > 0
     ORDER BY p.pending_balance DESC
")->fetchAll();

// 최근 지급 내역
$recentPayouts = $db->query("
    SELECT po.*, p.display_name partner_name, p.email partner_email
      FROM {$pfx}mkt_payouts po
      JOIN {$pfx}mkt_partners p ON p.id = po.partner_id
     ORDER BY po.requested_at DESC
     LIMIT 30
")->fetchAll();

// 통계
$totalGross      = (float)$db->query("SELECT COALESCE(SUM(gross_amount),0) FROM {$pfx}mkt_partner_earnings")->fetchColumn();
$totalCommission = (float)$db->query("SELECT COALESCE(SUM(commission),0)   FROM {$pfx}mkt_partner_earnings")->fetchColumn();
$totalPaid       = (float)$db->query("SELECT COALESCE(SUM(amount),0) FROM {$pfx}mkt_payouts WHERE status='completed'")->fetchColumn();
$totalPending    = (float)$db->query("SELECT COALESCE(SUM(pending_balance),0) FROM {$pfx}mkt_partners")->fetchColumn();

$currency = $db->query("SELECT value FROM {$pfx}mkt_settings WHERE `key`='currency' LIMIT 1")->fetchColumn() ?: 'JPY';
$csrf = $_SESSION['_csrf'] ?? '';
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">정산 관리</h1>
</div>

<!-- 통계 -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
        <p class="text-xs text-zinc-500 uppercase tracking-wider">총 매출</p>
        <p class="text-xl font-bold text-zinc-900 dark:text-white mt-1"><?= number_format($totalGross) ?> <?= $currency ?></p>
    </div>
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
        <p class="text-xs text-indigo-500 uppercase tracking-wider">수수료 수익</p>
        <p class="text-xl font-bold text-indigo-600 mt-1"><?= number_format($totalCommission) ?> <?= $currency ?></p>
    </div>
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
        <p class="text-xs text-green-500 uppercase tracking-wider">총 지급 완료</p>
        <p class="text-xl font-bold text-green-600 mt-1"><?= number_format($totalPaid) ?> <?= $currency ?></p>
    </div>
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
        <p class="text-xs text-amber-500 uppercase tracking-wider">미지급 잔액</p>
        <p class="text-xl font-bold text-amber-600 mt-1"><?= number_format($totalPending) ?> <?= $currency ?></p>
    </div>
</div>

<!-- 미정산 파트너 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
        <h3 class="font-semibold text-zinc-800 dark:text-zinc-200">미지급 파트너</h3>
    </div>
    <?php if (empty($pendingPartners)): ?>
    <p class="px-6 py-10 text-center text-zinc-400 text-sm">미지급 파트너가 없습니다.</p>
    <?php else: ?>
    <table class="w-full text-sm">
        <thead class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-700">
            <tr>
                <th class="text-left px-4 py-3 text-zinc-500 font-medium">파트너</th>
                <th class="text-right px-4 py-3 text-zinc-500 font-medium">총 수익</th>
                <th class="text-right px-4 py-3 text-zinc-500 font-medium">지급 완료</th>
                <th class="text-right px-4 py-3 text-zinc-500 font-medium">미지급 잔액</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
        <?php foreach ($pendingPartners as $p): ?>
        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
            <td class="px-4 py-3">
                <div class="font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($p['display_name']) ?></div>
                <div class="text-xs text-zinc-400"><?= htmlspecialchars($p['email']) ?></div>
            </td>
            <td class="px-4 py-3 text-right text-zinc-600 dark:text-zinc-400"><?= number_format((float)$p['total_earnings']) ?></td>
            <td class="px-4 py-3 text-right text-green-600"><?= number_format((float)$p['total_paid']) ?></td>
            <td class="px-4 py-3 text-right font-bold text-amber-600"><?= number_format((float)$p['pending_balance']) ?> <?= $currency ?></td>
            <td class="px-4 py-3 text-right">
                <button onclick="processPayout(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['display_name'])) ?>', <?= (float)$p['pending_balance'] ?>)"
                        class="px-3 py-1 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition">
                    지급 처리
                </button>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- 지급 이력 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
        <h3 class="font-semibold text-zinc-800 dark:text-zinc-200">지급 이력</h3>
    </div>
    <?php if (empty($recentPayouts)): ?>
    <p class="px-6 py-10 text-center text-zinc-400 text-sm">지급 이력이 없습니다.</p>
    <?php else: ?>
    <table class="w-full text-sm">
        <thead class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-700">
            <tr>
                <th class="text-left px-4 py-3 text-zinc-500 font-medium">파트너</th>
                <th class="text-right px-4 py-3 text-zinc-500 font-medium">금액</th>
                <th class="text-center px-4 py-3 text-zinc-500 font-medium">지급 방법</th>
                <th class="text-center px-4 py-3 text-zinc-500 font-medium">참조번호</th>
                <th class="text-center px-4 py-3 text-zinc-500 font-medium">상태</th>
                <th class="text-center px-4 py-3 text-zinc-500 font-medium">요청일</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
        <?php foreach ($recentPayouts as $po):
            $sc = $po['status'] === 'completed' ? 'bg-green-100 text-green-700' : ($po['status'] === 'failed' ? 'bg-red-100 text-red-700' : 'bg-yellow-100 text-yellow-700');
            $sl = ['pending'=>'대기','processing'=>'처리중','completed'=>'완료','failed'=>'실패'][$po['status']] ?? $po['status'];
        ?>
        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
            <td class="px-4 py-3">
                <div class="font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($po['partner_name']) ?></div>
                <div class="text-xs text-zinc-400"><?= htmlspecialchars($po['partner_email']) ?></div>
            </td>
            <td class="px-4 py-3 text-right font-medium text-zinc-800 dark:text-zinc-200"><?= number_format((float)$po['amount']) ?> <?= htmlspecialchars($po['currency']) ?></td>
            <td class="px-4 py-3 text-center text-xs text-zinc-500"><?= strtoupper(htmlspecialchars($po['method'] ?? '-')) ?></td>
            <td class="px-4 py-3 text-center text-xs font-mono text-zinc-400"><?= htmlspecialchars($po['reference'] ?? '-') ?></td>
            <td class="px-4 py-3 text-center"><span class="px-2 py-0.5 text-xs font-medium rounded <?= $sc ?>"><?= $sl ?></span></td>
            <td class="px-4 py-3 text-center text-xs text-zinc-400"><?= $po['requested_at'] ? substr($po['requested_at'], 0, 10) : '-' ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<script>
const CSRF = <?= json_encode($csrf) ?>;
const API_URL = <?= json_encode($adminUrl . '/market/partners/payouts-api') ?>;

async function processPayout(partnerId, name, amount) {
    const method = prompt(name + '에게 ' + amount.toLocaleString() + ' <?= $currency ?> 지급\n\n지급 방법 (bank / stripe / paypal):');
    if (!method) return;
    const ref = prompt('참조번호 (송금번호 등):') || '';
    const fd = new FormData();
    fd.append('_csrf', CSRF);
    fd.append('action', 'process_payout');
    fd.append('partner_id', partnerId);
    fd.append('method', method);
    fd.append('reference', ref);
    const r = await fetch(API_URL, { method: 'POST', body: fd });
    const d = await r.json();
    if (d.ok) { alert('지급 처리 완료'); location.reload(); }
    else alert('오류: ' + (d.message || 'Unknown error'));
}
</script>

<?php include __DIR__ . '/../_foot.php'; ?>
