<?php
include __DIR__ . '/../_head.php';
$pageHeaderTitle = '주문 관리';
$db = mkt_pdo(); $pfx = $_mktPrefix;
$q      = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$page   = max(1,(int)($_GET['page']??1));
$perPage = 25; $offset = ($page-1)*$perPage;
$where=[]; $params=[];
if ($status) { $where[]="o.status=?"; $params[]=$status; }
if ($q) { $where[]="(o.order_number LIKE ? OR o.buyer_email LIKE ?)"; $params[]="%$q%"; $params[]="%$q%"; }
$ws = $where ? 'WHERE '.implode(' AND ',$where) : '';
$st=$db->prepare("SELECT COUNT(*) FROM {$pfx}mkt_orders o $ws"); $st->execute($params); $total=(int)$st->fetchColumn();
$st2=$db->prepare("SELECT o.* FROM {$pfx}mkt_orders o $ws ORDER BY o.created_at DESC LIMIT $perPage OFFSET $offset"); $st2->execute($params); $orders=$st2->fetchAll();
$totalPages=(int)ceil($total/$perPage);
$adminUrl = $_mktAdmin;
$statusMeta=['pending'=>['대기','bg-yellow-100 text-yellow-700'],'paid'=>['결제완료','bg-green-100 text-green-700'],'cancelled'=>['취소','bg-zinc-100 text-zinc-500'],'refunded'=>['환불','bg-red-100 text-red-700']];
?>
<div class="flex items-center justify-between mb-6">
    <div><h1 class="text-2xl font-bold text-zinc-900 dark:text-white">주문 관리</h1><p class="text-sm text-zinc-500 mt-0.5">전체 <?= number_format($total) ?>건</p></div>
</div>
<form method="GET" class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl p-4 mb-6 flex gap-3 flex-wrap items-end">
    <div class="flex-1 min-w-[200px]"><input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="주문번호·이메일" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200"></div>
    <select name="status" class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm bg-white dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300">
        <option value="">모든 상태</option>
        <?php foreach ($statusMeta as $v=>[$l]): ?><option value="<?= $v ?>" <?= $status===$v?'selected':'' ?>><?= $l ?></option><?php endforeach; ?>
    </select>
    <button class="px-4 py-2 bg-zinc-800 dark:bg-zinc-600 text-white rounded-lg text-sm">검색</button>
    <?php if ($status||$q): ?><a href="<?= $adminUrl ?>/market/orders" class="px-3 py-2 text-sm text-zinc-500 hover:text-zinc-700">초기화</a><?php endif; ?>
</form>
<?php if (empty($orders)): ?>
<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-16 text-center"><p class="text-zinc-400">주문이 없습니다.</p></div>
<?php else: ?>
<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-700">
            <tr>
                <th class="text-left px-4 py-3 text-zinc-500 font-medium">주문번호</th>
                <th class="text-left px-4 py-3 text-zinc-500 font-medium">구매자</th>
                <th class="text-left px-4 py-3 text-zinc-500 font-medium">금액</th>
                <th class="text-left px-4 py-3 text-zinc-500 font-medium">결제수단</th>
                <th class="text-left px-4 py-3 text-zinc-500 font-medium">상태</th>
                <th class="text-left px-4 py-3 text-zinc-500 font-medium">주문일</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
        <?php foreach ($orders as $ord):
            [$sl,$sc] = $statusMeta[$ord['status']]??['?','bg-zinc-100 text-zinc-500'];
        ?>
        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
            <td class="px-4 py-3 font-mono text-xs text-zinc-600 dark:text-zinc-400"><?= htmlspecialchars($ord['order_number']) ?></td>
            <td class="px-4 py-3">
                <div class="text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($ord['buyer_name']??'-') ?></div>
                <div class="text-xs text-zinc-400"><?= htmlspecialchars($ord['buyer_email']??'') ?></div>
            </td>
            <td class="px-4 py-3 font-mono font-medium text-zinc-700 dark:text-zinc-300"><?= number_format((float)$ord['total_amount']) ?> <span class="text-xs text-zinc-400"><?= htmlspecialchars($ord['currency']??'JPY') ?></span></td>
            <td class="px-4 py-3 text-xs text-zinc-500"><?= htmlspecialchars($ord['payment_method']??'-') ?></td>
            <td class="px-4 py-3"><span class="px-2 py-0.5 text-xs rounded font-medium <?= $sc ?>"><?= $sl ?></span></td>
            <td class="px-4 py-3 text-zinc-400 text-xs"><?= htmlspecialchars(substr($ord['created_at']??'',0,10)) ?></td>
            <td class="px-4 py-3"><a href="<?= $adminUrl ?>/market/orders/show?id=<?= $ord['id'] ?>" class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs">상세 →</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php if ($totalPages>1): ?>
<div class="mt-6 flex justify-center gap-1">
    <?php for ($p=max(1,$page-2);$p<=min($totalPages,$page+2);$p++): ?>
    <a href="?<?= http_build_query(array_merge($_GET,['page'=>$p])) ?>" class="px-3 py-2 rounded-lg border text-sm <?= $p===$page?'bg-indigo-600 border-indigo-600 text-white':'border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100' ?>"><?= $p ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>
<?php endif; ?>
<?php include __DIR__ . '/../_foot.php'; ?>
