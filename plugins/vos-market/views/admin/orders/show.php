<?php
include __DIR__ . '/../_head.php';
$pageHeaderTitle = '주문 상세';
$db = mkt_pdo(); $pfx = $_mktPrefix;
$id = (int)($_GET['id'] ?? 0);
$st = $db->prepare("SELECT * FROM {$pfx}mkt_orders WHERE id=?");
$st->execute([$id]); $order = $st->fetch();
if (!$order) { echo '<p class="text-red-500">주문을 찾을 수 없습니다.</p>'; include __DIR__.'/../_foot.php'; return; }
$items = $db->prepare("SELECT oi.*,i.slug,i.name FROM {$pfx}mkt_order_items oi LEFT JOIN {$pfx}mkt_items i ON i.id=oi.item_id WHERE oi.order_id=?");
$items->execute([$id]); $orderItems = $items->fetchAll();
$adminUrl = $_mktAdmin;
$statusMeta=['pending'=>['대기','bg-yellow-100 text-yellow-700'],'paid'=>['결제완료','bg-green-100 text-green-700'],'cancelled'=>['취소','bg-zinc-100 text-zinc-500'],'refunded'=>['환불','bg-red-100 text-red-700']];
[$sl,$sc] = $statusMeta[$order['status']]??['?','bg-zinc-100 text-zinc-500'];
?>
<div class="flex items-center justify-between mb-6">
    <a href="<?= $adminUrl ?>/market/orders" class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">← 목록</a>
    <span class="px-3 py-1 text-sm rounded font-medium <?= $sc ?>"><?= $sl ?></span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
<div class="lg:col-span-3 space-y-6">
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
        <div class="flex items-center justify-between mb-4">
            <h2 class="font-bold text-zinc-800 dark:text-zinc-200">주문번호: <span class="font-mono"><?= htmlspecialchars($order['order_number']) ?></span></h2>
            <p class="text-2xl font-bold text-zinc-900 dark:text-white"><?= number_format((float)$order['total_amount']) ?> <span class="text-sm font-normal text-zinc-400"><?= htmlspecialchars($order['currency']??'JPY') ?></span></p>
        </div>
        <dl class="grid grid-cols-2 gap-x-6 gap-y-2 text-sm">
            <div class="flex gap-2"><dt class="text-zinc-500">결제수단</dt><dd class="text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($order['payment_method']??'-') ?></dd></div>
            <div class="flex gap-2"><dt class="text-zinc-500">결제 ID</dt><dd class="font-mono text-xs text-zinc-500"><?= htmlspecialchars($order['payment_id']??'-') ?></dd></div>
            <div class="flex gap-2"><dt class="text-zinc-500">주문일</dt><dd class="text-zinc-600 dark:text-zinc-400"><?= htmlspecialchars(substr($order['created_at']??'',0,16)) ?></dd></div>
            <div class="flex gap-2"><dt class="text-zinc-500">결제일</dt><dd class="text-zinc-600 dark:text-zinc-400"><?= $order['paid_at'] ? htmlspecialchars(substr($order['paid_at'],0,16)) : '-' ?></dd></div>
        </dl>
    </div>

    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700"><h3 class="font-semibold text-zinc-700 dark:text-zinc-300">주문 항목</h3></div>
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                <tr>
                    <th class="text-left px-4 py-3 text-zinc-500 font-medium">아이템</th>
                    <th class="text-left px-4 py-3 text-zinc-500 font-medium">버전</th>
                    <th class="text-right px-4 py-3 text-zinc-500 font-medium">금액</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
            <?php foreach ($orderItems as $oi):
                $iname = mkt_locale_val($oi['name']??null, $_mktLocale) ?: ($oi['slug']??'-');
            ?>
            <tr>
                <td class="px-4 py-3">
                    <div class="font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($iname) ?></div>
                    <div class="text-xs font-mono text-zinc-400"><?= htmlspecialchars($oi['slug']??'') ?></div>
                </td>
                <td class="px-4 py-3 text-zinc-500 text-xs font-mono">v<?= htmlspecialchars($oi['version_at_purchase']??'') ?></td>
                <td class="px-4 py-3 text-right font-mono text-zinc-700 dark:text-zinc-300"><?= number_format((float)$oi['price']) ?></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>

<div class="space-y-4">
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
        <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-3">구매자 정보</h3>
        <p class="font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($order['buyer_name']??'-') ?></p>
        <p class="text-xs text-zinc-500"><?= htmlspecialchars($order['buyer_email']??'') ?></p>
        <?php if ($order['buyer_country']): ?><p class="text-xs text-zinc-400 mt-1"><?= htmlspecialchars($order['buyer_country']) ?></p><?php endif; ?>
    </div>

    <?php if (!empty($order['meta'])): ?>
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
        <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-3">메타 데이터</h3>
        <pre class="text-xs text-zinc-600 dark:text-zinc-400 bg-zinc-50 dark:bg-zinc-900 rounded p-3 overflow-x-auto"><?= htmlspecialchars(json_encode(json_decode($order['meta']),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) ?></pre>
    </div>
    <?php endif; ?>
</div>
</div>
<?php include __DIR__ . '/../_foot.php'; ?>
