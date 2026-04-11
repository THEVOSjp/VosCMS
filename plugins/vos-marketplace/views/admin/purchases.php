<?php
/**
 * VosCMS Marketplace - 구매 내역 페이지
 */
include __DIR__ . '/_head.php';
$pageHeaderTitle = __mp('title');
$pageSubTitle = __mp('my_purchases');

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$adminId = $_SESSION['admin_id'] ?? '';

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    echo '<div class="p-4 bg-red-50 dark:bg-red-900/20 text-red-600 rounded-lg">DB Error</div>';
    include __DIR__ . '/_foot.php';
    return;
}

$stmt = $pdo->prepare(
    "SELECT o.*, GROUP_CONCAT(oi.item_name SEPARATOR ', ') as items_summary
     FROM {$prefix}mp_orders o
     LEFT JOIN {$prefix}mp_order_items oi ON oi.order_id = o.id
     WHERE o.admin_id = ?
     GROUP BY o.id
     ORDER BY o.created_at DESC"
);
$stmt->execute([$adminId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statusLabels = [
    'pending' => __mp('status_pending'),
    'paid' => __mp('status_paid'),
    'refunded' => __mp('status_refunded'),
    'cancelled' => __mp('status_cancelled'),
];
$statusColors = [
    'pending' => 'yellow',
    'paid' => 'green',
    'refunded' => 'blue',
    'cancelled' => 'red',
];
?>

<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200"><?= __mp('my_purchases') ?></h3>
    </div>

    <?php if (empty($orders)): ?>
    <div class="px-6 py-12 text-center text-zinc-400">
        <svg class="w-12 h-12 mx-auto mb-3 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 100 4 2 2 0 000-4z"/>
        </svg>
        <p><?= __mp('no_purchases') ?></p>
        <a href="<?= $adminUrl ?>/marketplace" class="inline-block mt-3 text-sm text-indigo-600 dark:text-indigo-400 hover:underline"><?= __mp('browse') ?> &rarr;</a>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-700/50 text-zinc-500 dark:text-zinc-400">
                <tr>
                    <th class="px-6 py-3 text-left font-medium"><?= __mp('order_number') ?></th>
                    <th class="px-6 py-3 text-left font-medium">Items</th>
                    <th class="px-6 py-3 text-left font-medium"><?= __mp('order_total') ?></th>
                    <th class="px-6 py-3 text-left font-medium"><?= __mp('order_status') ?></th>
                    <th class="px-6 py-3 text-left font-medium"><?= __mp('order_date') ?></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                <?php foreach ($orders as $o):
                    $color = $statusColors[$o['status']] ?? 'zinc';
                ?>
                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                    <td class="px-6 py-4 font-mono text-xs text-zinc-600 dark:text-zinc-400"><?= htmlspecialchars($o['order_number']) ?></td>
                    <td class="px-6 py-4 text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars(mb_substr($o['items_summary'] ?? '', 0, 60)) ?></td>
                    <td class="px-6 py-4">
                        <?php if ((float)$o['total'] <= 0): ?>
                        <span class="text-green-600 dark:text-green-400 font-medium"><?= __mp('free') ?></span>
                        <?php else: ?>
                        <span class="text-zinc-800 dark:text-zinc-200 font-medium"><?= number_format((float)$o['total'], 2) ?> <?= $o['currency'] ?></span>
                        <?php endif; ?>
                    </td>
                    <td class="px-6 py-4">
                        <span class="px-2 py-1 text-xs font-medium rounded-full bg-<?= $color ?>-100 text-<?= $color ?>-700 dark:bg-<?= $color ?>-900/30 dark:text-<?= $color ?>-400">
                            <?= $statusLabels[$o['status']] ?? $o['status'] ?>
                        </span>
                    </td>
                    <td class="px-6 py-4 text-zinc-500"><?= $o['created_at'] ? date('Y-m-d H:i', strtotime($o['created_at'])) : '-' ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/_foot.php'; ?>
