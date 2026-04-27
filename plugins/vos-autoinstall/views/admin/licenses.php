<?php
/**
 * VosCMS Marketplace - 라이선스 관리 페이지
 */
include __DIR__ . '/_head.php';
$pageHeaderTitle = __('autoinstall.title');
$pageSubTitle = __('autoinstall.licenses');
include __DIR__ . '/_components/page-title.php';

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
    "SELECT l.*, i.name as item_name, i.slug as item_slug, i.type as item_type
     FROM {$prefix}mp_licenses l
     JOIN {$prefix}mp_items i ON i.id = l.item_id
     WHERE l.admin_id = ?
     ORDER BY l.created_at DESC"
);
$stmt->execute([$adminId]);
$licenses = $stmt->fetchAll(PDO::FETCH_ASSOC);

$statusLabels = [
    'active' => __('autoinstall.license_active'),
    'expired' => __('autoinstall.license_expired'),
    'revoked' => __('autoinstall.license_revoked'),
    'suspended' => __('autoinstall.license_suspended'),
];
$statusColors = [
    'active' => 'green',
    'expired' => 'red',
    'revoked' => 'zinc',
    'suspended' => 'yellow',
];
$typeLabels = [
    'single' => __('autoinstall.type_single'),
    'unlimited' => __('autoinstall.type_unlimited'),
    'subscription' => __('autoinstall.type_subscription'),
];
?>

<div class="space-y-4">
    <?php if (empty($licenses)): ?>
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 px-6 py-12 text-center text-zinc-400">
        <svg class="w-12 h-12 mx-auto mb-3 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
        </svg>
        <p><?= __('autoinstall.no_licenses') ?></p>
    </div>
    <?php else: ?>
    <?php foreach ($licenses as $lic):
        $itemName = json_decode($lic['item_name'], true);
        $licItemName = $itemName[$locale] ?? $itemName['en'] ?? $lic['item_slug'];
        $color = $statusColors[$lic['status']] ?? 'zinc';

        // 활성화 정보
        $actStmt = $pdo->prepare("SELECT * FROM {$prefix}mp_license_activations WHERE license_id = ? ORDER BY activated_at DESC");
        $actStmt->execute([$lic['id']]);
        $activations = $actStmt->fetchAll(PDO::FETCH_ASSOC);
        $activeCount = count(array_filter($activations, fn($a) => $a['is_active']));
    ?>
    <div x-data="{ open: false }" class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-6 py-4 flex items-center justify-between cursor-pointer" @click="open = !open">
            <div class="flex items-center gap-4">
                <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                    <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 7a2 2 0 012 2m4 0a6 6 0 01-7.743 5.743L11 17H9v2H7v2H4a1 1 0 01-1-1v-2.586a1 1 0 01.293-.707l5.964-5.964A6 6 0 1121 9z"/>
                    </svg>
                </div>
                <div>
                    <h4 class="font-semibold text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($licItemName) ?></h4>
                    <div class="flex items-center gap-3 mt-1 text-xs text-zinc-400">
                        <span class="font-mono"><?= htmlspecialchars(substr($lic['license_key'], 0, 8)) ?>...<?= htmlspecialchars(substr($lic['license_key'], -4)) ?></span>
                        <span><?= $typeLabels[$lic['type']] ?? $lic['type'] ?></span>
                        <span><?= __('autoinstall.license_activations') ?>: <?= $activeCount ?>/<?= $lic['max_activations'] ?></span>
                    </div>
                </div>
            </div>
            <div class="flex items-center gap-3">
                <span class="px-2 py-1 text-xs font-medium rounded-full bg-<?= $color ?>-100 text-<?= $color ?>-700 dark:bg-<?= $color ?>-900/30 dark:text-<?= $color ?>-400">
                    <?= $statusLabels[$lic['status']] ?? $lic['status'] ?>
                </span>
                <svg class="w-5 h-5 text-zinc-400 transition-transform" :class="open && 'rotate-180'" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </div>
        </div>

        <div x-show="open" x-cloak class="border-t border-zinc-200 dark:border-zinc-700 px-6 py-4">
            <!-- 라이선스 키 -->
            <div class="flex items-center gap-2 mb-4 p-3 bg-zinc-50 dark:bg-zinc-700/50 rounded-lg" x-data="{ copied: false }">
                <code class="flex-1 text-sm font-mono text-zinc-600 dark:text-zinc-300 select-all"><?= htmlspecialchars($lic['license_key']) ?></code>
                <button @click="navigator.clipboard.writeText('<?= htmlspecialchars($lic['license_key']) ?>'); copied = true; setTimeout(() => copied = false, 2000)"
                        class="px-3 py-1 text-xs font-medium bg-indigo-100 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 rounded hover:bg-indigo-200 dark:hover:bg-indigo-900/50 transition-colors">
                    <span x-show="!copied"><?= __('autoinstall.copy_key') ?></span>
                    <span x-show="copied" x-cloak><?= __('autoinstall.copied') ?></span>
                </button>
            </div>

            <div class="grid grid-cols-2 gap-4 text-sm mb-4">
                <div>
                    <span class="text-zinc-500"><?= __('autoinstall.license_expires') ?>:</span>
                    <span class="text-zinc-800 dark:text-zinc-200 ml-1"><?= $lic['expires_at'] ? date('Y-m-d', strtotime($lic['expires_at'])) : __('autoinstall.perpetual') ?></span>
                </div>
                <div>
                    <span class="text-zinc-500"><?= __('autoinstall.order_date') ?>:</span>
                    <span class="text-zinc-800 dark:text-zinc-200 ml-1"><?= $lic['created_at'] ? date('Y-m-d', strtotime($lic['created_at'])) : '-' ?></span>
                </div>
            </div>

            <!-- 활성화 도메인 -->
            <h5 class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-2"><?= __('autoinstall.license_activations') ?></h5>
            <?php if (empty($activations)): ?>
            <p class="text-sm text-zinc-400"><?= __('autoinstall.no_activations') ?></p>
            <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($activations as $act): ?>
                <div class="flex items-center justify-between text-sm p-2 rounded bg-zinc-50 dark:bg-zinc-700/30">
                    <div>
                        <span class="font-medium text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($act['domain']) ?></span>
                        <span class="text-xs text-zinc-400 ml-2"><?= $act['activated_at'] ? date('Y-m-d', strtotime($act['activated_at'])) : '' ?></span>
                    </div>
                    <div class="flex items-center gap-2">
                        <?php if ($act['is_active']): ?>
                        <span class="w-2 h-2 rounded-full bg-green-500"></span>
                        <button onclick="deactivateDomain(<?= $lic['id'] ?>, '<?= htmlspecialchars($act['domain']) ?>')"
                                class="text-xs text-red-500 hover:text-red-600"><?= __('autoinstall.deactivate') ?></button>
                        <?php else: ?>
                        <span class="w-2 h-2 rounded-full bg-zinc-300 dark:bg-zinc-600"></span>
                        <span class="text-xs text-zinc-400">Inactive</span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
    <?php endif; ?>
</div>

<script>
function deactivateDomain(licenseId, domain) {
    if (!confirm('<?= __('autoinstall.deactivate') ?>: ' + domain + '?')) return;
    fetch('<?= $adminUrl ?>/autoinstall/api', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=deactivate_domain&license_id=' + licenseId + '&domain=' + encodeURIComponent(domain)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) window.location.reload();
        else alert(data.message || 'Error');
    });
}
</script>

<?php include __DIR__ . '/_foot.php'; ?>
