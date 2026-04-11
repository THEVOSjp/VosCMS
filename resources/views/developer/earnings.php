<?php
/**
 * Developer - 매출/정산
 */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['developer_id'])) { header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/developer/login'); exit; }

include __DIR__ . '/partials/_layout_head.php';
$pageTitle = __mp('dev_earnings');

$devId = $_SESSION['developer_id'];
try {
    $pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4", $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) { echo '<p class="text-red-500">DB Error</p>'; include __DIR__ . '/partials/_layout_foot.php'; exit; }

$dev = $pdo->prepare("SELECT * FROM vcs_developers WHERE id = ?"); $dev->execute([$devId]); $dev = $dev->fetch(PDO::FETCH_ASSOC);
$earnings = $pdo->prepare("SELECT * FROM vcs_developer_earnings WHERE developer_id = ? ORDER BY created_at DESC LIMIT 50"); $earnings->execute([$devId]); $earnings = $earnings->fetchAll(PDO::FETCH_ASSOC);
$payouts = $pdo->prepare("SELECT * FROM vcs_developer_payouts WHERE developer_id = ? ORDER BY created_at DESC LIMIT 20"); $payouts->execute([$devId]); $payouts = $payouts->fetchAll(PDO::FETCH_ASSOC);
?>

<h1 class="text-2xl font-bold text-zinc-900 dark:text-white mb-6"><?= __mp('earnings_title') ?></h1>

<!-- 요약 -->
<div class="grid grid-cols-3 gap-4 mb-8">
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-5">
        <p class="text-sm text-zinc-500"><?= __mp('earnings_total') ?></p>
        <p class="text-2xl font-bold text-zinc-900 dark:text-white mt-1">$<?= number_format((float)($dev['total_earnings'] ?? 0), 2) ?></p>
    </div>
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-5">
        <p class="text-sm text-zinc-500"><?= __mp('earnings_paid') ?></p>
        <p class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1">$<?= number_format((float)($dev['total_paid'] ?? 0), 2) ?></p>
    </div>
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-5">
        <p class="text-sm text-zinc-500"><?= __mp('earnings_pending') ?></p>
        <p class="text-2xl font-bold text-indigo-600 dark:text-indigo-400 mt-1">$<?= number_format((float)($dev['pending_balance'] ?? 0), 2) ?></p>
    </div>
</div>

<!-- 매출 내역 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden mb-6">
    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
        <h2 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200"><?= __mp('earnings_history') ?></h2>
    </div>
    <?php if (empty($earnings)): ?>
    <div class="p-8 text-center text-zinc-400 dark:text-zinc-500 text-sm"><?= __mp('earnings_no_data') ?></div>
    <?php else: ?>
    <table class="w-full text-sm">
        <thead class="bg-zinc-50 dark:bg-zinc-700/50 text-zinc-500 dark:text-zinc-400">
            <tr>
                <th class="px-6 py-3 text-left"><?= __mp('earnings_item') ?></th>
                <th class="px-6 py-3 text-left"><?= __mp('earnings_buyer') ?></th>
                <th class="px-6 py-3 text-right"><?= __mp('earnings_gross') ?></th>
                <th class="px-6 py-3 text-right"><?= __mp('earnings_commission') ?></th>
                <th class="px-6 py-3 text-right"><?= __mp('earnings_net') ?></th>
                <th class="px-6 py-3 text-left"><?= __mp('earnings_date') ?></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
            <?php foreach ($earnings as $e): ?>
            <tr>
                <td class="px-6 py-3 text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($e['item_name'] ?? '-') ?></td>
                <td class="px-6 py-3 text-zinc-500 text-xs"><?= htmlspecialchars($e['buyer_domain'] ?? '-') ?></td>
                <td class="px-6 py-3 text-right"><?= number_format((float)$e['gross_amount'], 2) ?></td>
                <td class="px-6 py-3 text-right text-red-500">-<?= number_format((float)$e['commission'], 2) ?></td>
                <td class="px-6 py-3 text-right font-medium text-green-600 dark:text-green-400">$<?= number_format((float)$e['net_amount'], 2) ?></td>
                <td class="px-6 py-3 text-zinc-400"><?= date('Y-m-d', strtotime($e['created_at'])) ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<!-- 지급 내역 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
        <h2 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200"><?= __mp('payouts_title') ?></h2>
    </div>
    <?php if (empty($payouts)): ?>
    <div class="p-8 text-center text-zinc-400 dark:text-zinc-500 text-sm"><?= __mp('payouts_no_data') ?></div>
    <?php else: ?>
    <table class="w-full text-sm">
        <thead class="bg-zinc-50 dark:bg-zinc-700/50 text-zinc-500 dark:text-zinc-400">
            <tr>
                <th class="px-6 py-3 text-left"><?= __mp('payouts_period') ?></th>
                <th class="px-6 py-3 text-right"><?= __mp('payouts_amount') ?></th>
                <th class="px-6 py-3 text-left"><?= __mp('payouts_method') ?></th>
                <th class="px-6 py-3 text-left"><?= __mp('payouts_status') ?></th>
                <th class="px-6 py-3 text-left"><?= __mp('payouts_processed') ?></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
            <?php foreach ($payouts as $p): ?>
            <tr>
                <td class="px-6 py-3 text-zinc-700 dark:text-zinc-300"><?= $p['period_start'] ?> ~ <?= $p['period_end'] ?></td>
                <td class="px-6 py-3 text-right font-medium text-zinc-800 dark:text-zinc-200">$<?= number_format((float)$p['amount'], 2) ?></td>
                <td class="px-6 py-3 text-zinc-600 dark:text-zinc-400"><?= strtoupper($p['method']) ?></td>
                <td class="px-6 py-3"><span class="px-2 py-0.5 text-xs rounded-full bg-<?= $p['status'] === 'completed' ? 'green' : 'yellow' ?>-100 text-<?= $p['status'] === 'completed' ? 'green' : 'yellow' ?>-700 dark:bg-<?= $p['status'] === 'completed' ? 'green' : 'yellow' ?>-900/30 dark:text-<?= $p['status'] === 'completed' ? 'green' : 'yellow' ?>-400"><?= ucfirst($p['status']) ?></span></td>
                <td class="px-6 py-3 text-zinc-400"><?= $p['processed_at'] ? date('Y-m-d', strtotime($p['processed_at'])) : '-' ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/partials/_layout_foot.php'; ?>
