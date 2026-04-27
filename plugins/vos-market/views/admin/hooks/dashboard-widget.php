<?php
// admin.dashboard.render hook — injects marketplace stats card
$pfx = $_ENV['DB_PREFIX'] ?? 'rzx_';
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
    );
    $items    = (int)$pdo->query("SELECT COUNT(*) FROM {$pfx}mkt_items WHERE status='active'")->fetchColumn();
    $pending  = (int)$pdo->query("SELECT COUNT(*) FROM {$pfx}mkt_submissions WHERE status='pending'")->fetchColumn();
    $partners = (int)$pdo->query("SELECT COUNT(*) FROM {$pfx}mkt_partners WHERE status='active'")->fetchColumn();
    $orders   = (int)$pdo->query("SELECT COUNT(*) FROM {$pfx}mkt_orders WHERE DATE(created_at)=CURDATE()")->fetchColumn();
    $adminPath = '/' . ($config['admin_path'] ?? 'theadmin');
} catch (Throwable $e) {
    return;
}
?>
<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5 col-span-full">
    <div class="flex items-center justify-between mb-4">
        <h3 class="font-semibold text-zinc-700 dark:text-zinc-300">마켓플레이스</h3>
        <a href="<?= $adminPath ?>/market/items" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">관리 →</a>
    </div>
    <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
        <div class="text-center p-3 bg-zinc-50 dark:bg-zinc-900/50 rounded-lg">
            <p class="text-2xl font-bold text-zinc-800 dark:text-zinc-200"><?= number_format($items) ?></p>
            <p class="text-xs text-zinc-500 mt-1">활성 아이템</p>
        </div>
        <div class="text-center p-3 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg">
            <p class="text-2xl font-bold text-yellow-700 dark:text-yellow-400"><?= number_format($pending) ?></p>
            <p class="text-xs text-zinc-500 mt-1">심사 대기</p>
            <?php if ($pending > 0): ?><a href="<?= $adminPath ?>/market/submissions" class="text-[10px] text-yellow-600 dark:text-yellow-400 hover:underline">확인하기</a><?php endif; ?>
        </div>
        <div class="text-center p-3 bg-zinc-50 dark:bg-zinc-900/50 rounded-lg">
            <p class="text-2xl font-bold text-zinc-800 dark:text-zinc-200"><?= number_format($partners) ?></p>
            <p class="text-xs text-zinc-500 mt-1">활성 파트너</p>
        </div>
        <div class="text-center p-3 bg-zinc-50 dark:bg-zinc-900/50 rounded-lg">
            <p class="text-2xl font-bold text-zinc-800 dark:text-zinc-200"><?= number_format($orders) ?></p>
            <p class="text-xs text-zinc-500 mt-1">오늘 주문</p>
        </div>
    </div>
</div>
