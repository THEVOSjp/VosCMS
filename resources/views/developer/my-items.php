<?php
/**
 * Developer - 내 아이템 목록
 */
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
if (empty($_SESSION['developer_id'])) { header('Location: ' . ($_ENV['APP_URL'] ?? '') . '/developer/login'); exit; }

include __DIR__ . '/partials/_layout_head.php';
$pageTitle = __mp('dev_my_items');

$devId = $_SESSION['developer_id'];
$locale = $_mpLocale ?? 'ko';
try {
    $pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4", $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'], [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
} catch (PDOException $e) { echo '<p class="text-red-500">DB Error</p>'; include __DIR__ . '/partials/_layout_foot.php'; exit; }

$items = $pdo->prepare("SELECT * FROM vcs_review_queue WHERE developer_id = ? ORDER BY submitted_at DESC");
$items->execute([$devId]);
$items = $items->fetchAll(PDO::FETCH_ASSOC);

$statusLabels = [
    'pending' => __mp('status_pending'), 'reviewing' => __mp('status_reviewing'),
    'approved' => __mp('status_approved'), 'rejected' => __mp('status_rejected'), 'revision' => __mp('status_revision'),
];
$statusColors = ['pending' => 'yellow', 'reviewing' => 'blue', 'approved' => 'green', 'rejected' => 'red', 'revision' => 'amber'];
?>

<div class="flex items-center justify-between mb-6">
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= __mp('dev_my_items') ?></h1>
    <a href="<?= $baseUrl ?>/developer/submit" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">+ <?= __mp('dev_new_item') ?></a>
</div>

<?php if (empty($items)): ?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-12 text-center">
    <p class="text-zinc-400 dark:text-zinc-500"><?= __mp('dev_no_items') ?></p>
    <a href="<?= $baseUrl ?>/developer/submit" class="inline-block mt-3 text-indigo-600 dark:text-indigo-400 hover:underline text-sm"><?= __mp('dev_first_item') ?> &rarr;</a>
</div>
<?php else: ?>
<div class="space-y-3">
    <?php foreach ($items as $item):
        $name = json_decode($item['name'], true);
        $itemName = $name[$locale] ?? $name['en'] ?? '(unnamed)';
        $sc = $statusColors[$item['status']] ?? 'zinc';
    ?>
    <div x-data="{ showUpdate: false }" class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-6 py-4 flex items-center justify-between">
            <div class="flex items-center gap-4">
                <span class="px-2 py-0.5 text-xs font-bold rounded bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400 uppercase"><?= $item['item_type'] ?></span>
                <?php if ($item['is_update'] ?? false): ?>
                <span class="px-2 py-0.5 text-[10px] font-bold rounded bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">UPDATE</span>
                <?php endif; ?>
                <div>
                    <h3 class="font-semibold text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($itemName) ?> <span class="text-sm text-zinc-400 font-normal">v<?= htmlspecialchars($item['version']) ?></span></h3>
                    <p class="text-xs text-zinc-400">
                        <?= date('Y-m-d', strtotime($item['submitted_at'])) ?>
                        <?php if ($item['is_update'] ?? false): ?>
                        &middot; <?= $item['previous_version'] ?> &rarr; <?= $item['version'] ?>
                        <?php endif; ?>
                    </p>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <?php if ((float)$item['price'] > 0): ?>
                <span class="text-sm font-medium text-zinc-600 dark:text-zinc-300"><?= number_format((float)$item['price'], 2) ?> <?= $item['currency'] ?></span>
                <?php else: ?>
                <span class="text-sm font-medium text-green-600 dark:text-green-400"><?= __mp('free') ?></span>
                <?php endif; ?>
                <span class="px-2 py-1 text-xs font-medium rounded-full bg-<?= $sc ?>-100 text-<?= $sc ?>-700 dark:bg-<?= $sc ?>-900/30 dark:text-<?= $sc ?>-400">
                    <?= $statusLabels[$item['status']] ?? $item['status'] ?>
                </span>
                <?php if ($item['status'] === 'approved'): ?>
                <button @click="showUpdate = !showUpdate" class="px-3 py-1.5 text-xs font-medium bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded-lg hover:bg-indigo-100 dark:hover:bg-indigo-900/50 transition-colors">
                    <?= __mp('new_version_btn') ?>
                </button>
                <?php endif; ?>
            </div>
        </div>

        <!-- 새 버전 업로드 폼 (승인된 아이템만) -->
        <?php if ($item['status'] === 'approved'): ?>
        <div x-show="showUpdate" x-cloak class="border-t border-zinc-200 dark:border-zinc-700 px-6 py-5 bg-zinc-50 dark:bg-zinc-700/30">
            <h4 class="text-sm font-semibold text-zinc-700 dark:text-zinc-300 mb-3"><?= __mp('new_version_title') ?></h4>
            <form onsubmit="submitUpdate(event, <?= $item['id'] ?>, <?= $item['item_id'] ?? 0 ?>)" enctype="multipart/form-data" class="space-y-3">
                <div class="grid grid-cols-2 gap-3">
                    <div>
                        <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1"><?= __mp('submit_version') ?> *</label>
                        <input type="text" name="version" required placeholder="<?= $item['version'] ?> → ?" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white">
                    </div>
                    <div>
                        <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1"><?= __mp('submit_package') ?> *</label>
                        <input type="file" name="package" accept=".zip" required class="w-full text-xs file:mr-2 file:py-1.5 file:px-3 file:rounded-lg file:border-0 file:bg-indigo-50 file:text-indigo-700 file:font-medium">
                    </div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1"><?= __mp('submit_changelog') ?></label>
                    <textarea name="changelog" rows="2" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm bg-white dark:bg-zinc-700 text-zinc-900 dark:text-white" placeholder="<?= __mp('submit_changelog_hint') ?>"></textarea>
                </div>
                <div class="flex gap-2">
                    <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors"><?= __mp('submit_update_btn') ?></button>
                    <button type="button" @click="showUpdate = false" class="px-4 py-2 text-sm font-medium text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-600 rounded-lg transition-colors"><?= __mp('cancel') ?></button>
                </div>
            </form>
        </div>
        <?php endif; ?>
    </div>
    <?php if ($item['status'] === 'rejected' && $item['rejection_reason']): ?>
    <div class="ml-6 -mt-1 p-3 bg-red-50 dark:bg-red-900/20 border-l-4 border-red-400 rounded-r-lg text-sm text-red-700 dark:text-red-400">
        <strong><?= __mp('rejection_reason') ?>:</strong> <?= nl2br(htmlspecialchars($item['rejection_reason'])) ?>
    </div>
    <?php endif; ?>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
async function submitUpdate(e, queueId, itemId) {
    e.preventDefault();
    const form = e.target;
    const btn = form.querySelector('button[type="submit"]');
    btn.disabled = true;
    btn.textContent = '<?= __mp('submit_submitting') ?>';

    const fd = new FormData(form);
    fd.set('queue_id', queueId);
    if (itemId) fd.set('item_id', itemId);

    try {
        const res = await fetch('<?= $baseUrl ?>/api/developer/update-version', { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            alert(data.message || '<?= __mp('submit_success') ?>');
            location.reload();
        } else {
            alert(data.message || 'Error');
        }
    } catch (err) {
        alert('Network error');
    }
    btn.disabled = false;
    btn.textContent = '<?= __mp('submit_update_btn') ?>';
}
</script>

<?php include __DIR__ . '/partials/_layout_foot.php'; ?>
