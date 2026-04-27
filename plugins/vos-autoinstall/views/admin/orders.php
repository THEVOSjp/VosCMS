<?php
/**
 * VosCMS Autoinstall — 구매 내역
 * mp_purchases에 저장된 결제 정보 표시
 */
include __DIR__ . '/_head.php';
$pageHeaderTitle = __('autoinstall.title');
$pageSubTitle    = __('autoinstall.orders');
include __DIR__ . '/_components/page-title.php';

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$locale = $_SESSION['locale'] ?? 'ko';

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

$rows = $pdo->query(
    "SELECT * FROM {$prefix}mp_purchases ORDER BY purchased_at DESC, id DESC"
)->fetchAll(PDO::FETCH_ASSOC);

$totalAmount = 0.0;
$currencyTotals = [];
foreach ($rows as $r) {
    $cur = $r['currency'] ?: 'JPY';
    $currencyTotals[$cur] = ($currencyTotals[$cur] ?? 0) + (float)$r['amount'];
}

$keyPreview = function (?string $key): string {
    if (!$key) return '<span class="text-zinc-300 dark:text-zinc-600">—</span>';
    return htmlspecialchars(mb_substr($key, 0, 8) . '...' . mb_substr($key, -4));
};
?>

<div class="mb-5 flex items-center justify-between flex-wrap gap-3">
    <div class="flex items-center gap-4">
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            총 <strong class="text-zinc-700 dark:text-zinc-300"><?= count($rows) ?></strong>건
        </p>
        <?php foreach ($currencyTotals as $cur => $tot): ?>
        <p class="text-sm text-zinc-500 dark:text-zinc-400">
            <?= htmlspecialchars($cur) ?>:
            <strong class="text-emerald-600 dark:text-emerald-400 font-mono"><?= number_format($tot, in_array($cur, ['JPY','KRW']) ? 0 : 2) ?></strong>
        </p>
        <?php endforeach; ?>
    </div>
    <a href="<?= $adminUrl ?>/autoinstall" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
        ← <?= __('autoinstall.easy_install') ?>
    </a>
</div>

<?php if (empty($rows)): ?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 px-6 py-12 text-center text-zinc-400">
    <svg class="w-12 h-12 mx-auto mb-3 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 11V7a4 4 0 00-8 0v4M5 9h14l1 12H4L5 9z"/></svg>
    <p><?= __('autoinstall.no_orders') ?></p>
    <a href="<?= $adminUrl ?>/autoinstall" class="inline-block mt-3 text-sm text-indigo-600 dark:text-indigo-400 hover:underline">
        <?= __('autoinstall.easy_install') ?> →
    </a>
</div>
<?php else: ?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-zinc-50 dark:bg-zinc-700/50 text-zinc-500 dark:text-zinc-400 text-xs">
            <tr>
                <th class="px-5 py-3 text-left font-medium"><?= __('autoinstall.order_item') ?></th>
                <th class="px-5 py-3 text-left font-medium w-32"><?= __('autoinstall.order_amount') ?></th>
                <th class="px-5 py-3 text-left font-medium w-44"><?= __('autoinstall.order_number') ?></th>
                <th class="px-5 py-3 text-left font-medium w-40"><?= __('autoinstall.license_key') ?></th>
                <th class="px-5 py-3 text-left font-medium w-40"><?= __('autoinstall.serial_key') ?></th>
                <th class="px-5 py-3 text-left font-medium w-36 whitespace-nowrap"><?= __('autoinstall.purchased_at') ?></th>
                <th class="px-5 py-3 text-right font-medium w-28 whitespace-nowrap"><?= __('autoinstall.actions') ?></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
            <?php foreach ($rows as $r): ?>
            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                <td class="px-5 py-3">
                    <p class="font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($r['item_name'] ?: $r['item_slug']) ?></p>
                    <p class="text-xs text-zinc-400 font-mono mt-0.5"><?= htmlspecialchars($r['item_slug']) ?></p>
                </td>
                <td class="px-5 py-3">
                    <span class="font-mono text-emerald-600 dark:text-emerald-400 font-semibold">
                        <?= number_format((float)$r['amount'], in_array($r['currency'], ['JPY','KRW']) ? 0 : 2) ?>
                    </span>
                    <span class="text-xs text-zinc-400 ml-1"><?= htmlspecialchars($r['currency']) ?></span>
                    <?php if ((int)$r['installment'] >= 2): ?>
                    <span class="block text-[10px] text-zinc-400 mt-0.5"><?= (int)$r['installment'] ?>회 할부</span>
                    <?php endif; ?>
                </td>
                <td class="px-5 py-3 text-xs font-mono text-zinc-500"><?= htmlspecialchars($r['order_number'] ?: '—') ?></td>
                <td class="px-5 py-3 text-xs font-mono text-zinc-500"><?= $keyPreview($r['license_key']) ?></td>
                <td class="px-5 py-3 text-xs font-mono text-zinc-500"><?= $keyPreview($r['serial_key']) ?></td>
                <td class="px-5 py-3 text-xs text-zinc-500 whitespace-nowrap">
                    <?= htmlspecialchars(date('Y-m-d H:i', strtotime($r['purchased_at']))) ?>
                </td>
                <td class="px-5 py-3 text-right">
                    <a href="<?= $adminUrl ?>/autoinstall/item?slug=<?= urlencode($r['item_slug']) ?>"
                       class="inline-block px-3 py-1 text-xs font-medium bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors whitespace-nowrap">
                        <?= __('autoinstall.go_detail') ?>
                    </a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<?php include __DIR__ . '/_foot.php'; ?>
