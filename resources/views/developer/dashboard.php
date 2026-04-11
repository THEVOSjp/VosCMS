<?php
/**
 * Developer Dashboard - 메인
 */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['developer_id'])) { header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/developer/login'); exit; }

include __DIR__ . '/partials/_layout_head.php';
$pageTitle = __mp('dev_dashboard');

$devId = $_SESSION['developer_id'];
try {
    $pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4", $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) { echo '<p class="text-red-500">DB Error</p>'; include __DIR__ . '/partials/_layout_foot.php'; exit; }

$dev = $pdo->prepare("SELECT * FROM vcs_developers WHERE id = ?");
$dev->execute([$devId]);
$dev = $dev->fetch(PDO::FETCH_ASSOC);

$queueStmt = $pdo->prepare("SELECT COUNT(*) FROM vcs_review_queue WHERE developer_id = ?"); $queueStmt->execute([$devId]); $queueCount = (int)$queueStmt->fetchColumn();
$s = $pdo->prepare("SELECT COUNT(*) FROM vcs_review_queue WHERE developer_id = ? AND status = 'pending'"); $s->execute([$devId]); $pendingCount = (int)$s->fetchColumn();
$s = $pdo->prepare("SELECT COUNT(*) FROM vcs_review_queue WHERE developer_id = ? AND status = 'approved'"); $s->execute([$devId]); $approvedCount = (int)$s->fetchColumn();
$s = $pdo->prepare("SELECT COUNT(*) FROM vcs_review_queue WHERE developer_id = ? AND status = 'rejected'"); $s->execute([$devId]); $rejectedCount = (int)$s->fetchColumn();

$_devTypeLabels = ['general' => __mp('dev_type_general'), 'verified' => __mp('dev_type_verified'), 'partner' => __mp('dev_type_partner')];
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= __mp('dev_welcome') ?>, <?= htmlspecialchars($dev['name'] ?? '') ?></h1>
    <p class="text-sm text-zinc-500 mt-1"><?= __mp('dev_motivate') ?></p>
</div>

<!-- 통계 카드 -->
<div class="grid grid-cols-2 md:grid-cols-4 gap-4 mb-8">
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-5">
        <p class="text-sm text-zinc-500"><?= __mp('dev_total_submitted') ?></p>
        <p class="text-2xl font-bold text-zinc-900 dark:text-white mt-1"><?= $queueCount ?></p>
    </div>
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-5">
        <p class="text-sm text-yellow-600"><?= __mp('dev_pending_review') ?></p>
        <p class="text-2xl font-bold text-zinc-900 dark:text-white mt-1"><?= $pendingCount ?></p>
    </div>
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-5">
        <p class="text-sm text-green-600"><?= __mp('dev_approved') ?></p>
        <p class="text-2xl font-bold text-zinc-900 dark:text-white mt-1"><?= $approvedCount ?></p>
    </div>
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-5">
        <p class="text-sm text-zinc-500"><?= __mp('dev_total_earnings') ?></p>
        <p class="text-2xl font-bold text-zinc-900 dark:text-white mt-1">$<?= number_format((float)($dev['total_earnings'] ?? 0), 2) ?></p>
    </div>
</div>

<!-- 빠른 액션 -->
<div class="grid grid-cols-1 md:grid-cols-2 gap-6">
    <a href="<?= $baseUrl ?>/developer/submit" class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6 hover:border-indigo-300 dark:hover:border-indigo-600 transition-colors group">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            </div>
            <div>
                <h3 class="font-semibold text-zinc-800 dark:text-zinc-200 group-hover:text-indigo-600"><?= __mp('dev_new_item') ?></h3>
                <p class="text-sm text-zinc-500 mt-0.5"><?= __mp('dev_new_item_desc') ?></p>
            </div>
        </div>
    </a>
    <a href="<?= $baseUrl ?>/developer/my-items" class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6 hover:border-indigo-300 dark:hover:border-indigo-600 transition-colors group">
        <div class="flex items-center gap-4">
            <div class="w-12 h-12 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center">
                <svg class="w-6 h-6 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>
            <div>
                <h3 class="font-semibold text-zinc-800 dark:text-zinc-200 group-hover:text-emerald-600"><?= __mp('dev_view_items') ?></h3>
                <p class="text-sm text-zinc-500 mt-0.5"><?= __mp('dev_view_items_desc') ?></p>
            </div>
        </div>
    </a>
</div>

<?php if ($rejectedCount > 0): ?>
<div class="mt-6 p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg">
    <p class="text-sm text-red-700 dark:text-red-400 font-medium"><?= $rejectedCount ?><?= __mp('dev_rejected_notice') ?> <a href="<?= $baseUrl ?>/developer/my-items" class="underline"><?= __mp('dev_check') ?></a></p>
</div>
<?php endif; ?>

<?php include __DIR__ . '/partials/_layout_foot.php'; ?>
