<?php
/**
 * License Manager - 대시보드
 */
include __DIR__ . '/_head.php';

// 통계
$totalLicenses = (int)$_lmPdo->query("SELECT COUNT(*) FROM vcs_licenses")->fetchColumn();
$activeLicenses = (int)$_lmPdo->query("SELECT COUNT(*) FROM vcs_licenses WHERE status = 'active'")->fetchColumn();
$totalPluginSales = (int)$_lmPdo->query("SELECT COUNT(*) FROM vcs_license_plugins WHERE status = 'active'")->fetchColumn();
$totalDevelopers = 0;
try { $totalDevelopers = (int)$_lmPdo->query("SELECT COUNT(*) FROM vcs_developers WHERE status = 'active'")->fetchColumn(); } catch(PDOException $e) {}
$pendingReviews = 0;
try { $pendingReviews = (int)$_lmPdo->query("SELECT COUNT(*) FROM vcs_review_queue WHERE status = 'pending'")->fetchColumn(); } catch(PDOException $e) {}
$totalEarnings = 0;
try { $totalEarnings = (float)$_lmPdo->query("SELECT COALESCE(SUM(gross_amount), 0) FROM vcs_developer_earnings")->fetchColumn(); } catch(PDOException $e) {}

$plans = $_lmPdo->query("SELECT plan, COUNT(*) as cnt FROM vcs_licenses WHERE status = 'active' GROUP BY plan")->fetchAll();
$planStats = [];
foreach ($plans as $p) $planStats[$p['plan']] = (int)$p['cnt'];

$recentLicenses = $_lmPdo->query("SELECT license_key, domain, plan, status, registered_at FROM vcs_licenses ORDER BY registered_at DESC LIMIT 10")->fetchAll();
$recentLogs = $_lmPdo->query("SELECT ll.action, ll.domain, ll.ip_address, ll.created_at, l.license_key FROM vcs_license_logs ll LEFT JOIN vcs_licenses l ON l.id = ll.license_id ORDER BY ll.created_at DESC LIMIT 10")->fetchAll();
?>

<div class="mb-6">
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= __lm('title') ?></h1>
    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1"><?= __lm('subtitle') ?></p>
</div>

<!-- 통계 카드 -->
<div class="grid grid-cols-2 md:grid-cols-3 lg:grid-cols-6 gap-4 mb-8">
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-5">
        <p class="text-xs text-zinc-500 dark:text-zinc-400 uppercase tracking-wider"><?= __lm('stat_total') ?></p>
        <p class="text-2xl font-bold text-zinc-900 dark:text-white mt-1"><?= number_format($totalLicenses) ?></p>
    </div>
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-5">
        <p class="text-xs text-green-600 uppercase tracking-wider"><?= __lm('stat_active') ?></p>
        <p class="text-2xl font-bold text-zinc-900 dark:text-white mt-1"><?= number_format($activeLicenses) ?></p>
    </div>
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-5">
        <p class="text-xs text-indigo-600 uppercase tracking-wider"><?= __lm('stat_plugins') ?></p>
        <p class="text-2xl font-bold text-zinc-900 dark:text-white mt-1"><?= number_format($totalPluginSales) ?></p>
    </div>
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-5">
        <p class="text-xs text-purple-600 uppercase tracking-wider"><?= __lm('stat_developers') ?></p>
        <p class="text-2xl font-bold text-zinc-900 dark:text-white mt-1"><?= number_format($totalDevelopers) ?></p>
    </div>
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-5">
        <p class="text-xs text-yellow-600 uppercase tracking-wider"><?= __lm('stat_pending_review') ?></p>
        <p class="text-2xl font-bold text-zinc-900 dark:text-white mt-1"><?= $pendingReviews ?></p>
    </div>
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-5">
        <p class="text-xs text-zinc-500 uppercase tracking-wider"><?= __lm('stat_revenue') ?></p>
        <p class="text-2xl font-bold text-zinc-900 dark:text-white mt-1">$<?= number_format($totalEarnings, 2) ?></p>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-2 gap-6">
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
            <h3 class="font-semibold text-zinc-800 dark:text-zinc-200"><?= __lm('recent_registered') ?></h3>
            <a href="<?= $adminUrl ?>/license-manager/licenses" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline"><?= __lm('view_all') ?></a>
        </div>
        <div class="divide-y divide-zinc-100 dark:divide-zinc-700">
            <?php foreach ($recentLicenses as $lic): ?>
            <a href="<?= $adminUrl ?>/license-manager/license?key=<?= urlencode($lic['license_key']) ?>" class="flex items-center justify-between px-6 py-3 hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition">
                <div>
                    <p class="text-sm font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($lic['domain']) ?></p>
                    <p class="text-xs text-zinc-400 font-mono"><?= htmlspecialchars(substr($lic['license_key'], 0, 8)) ?>...<?= htmlspecialchars(substr($lic['license_key'], -4)) ?></p>
                </div>
                <div class="text-right">
                    <?php $sc = ['active'=>'green','suspended'=>'yellow','revoked'=>'red'][$lic['status']] ?? 'zinc'; ?>
                    <span class="px-2 py-0.5 text-xs rounded-full bg-<?= $sc ?>-100 text-<?= $sc ?>-700 dark:bg-<?= $sc ?>-900/30 dark:text-<?= $sc ?>-400"><?= strtoupper($lic['status']) ?></span>
                    <p class="text-xs text-zinc-400 mt-0.5"><?= $lic['registered_at'] ? date('Y-m-d', strtotime($lic['registered_at'])) : '' ?></p>
                </div>
            </a>
            <?php endforeach; ?>
            <?php if (empty($recentLicenses)): ?>
            <p class="px-6 py-8 text-center text-zinc-400 text-sm"><?= __lm('no_licenses') ?></p>
            <?php endif; ?>
        </div>
    </div>

    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <h3 class="font-semibold text-zinc-800 dark:text-zinc-200"><?= __lm('recent_activity') ?></h3>
        </div>
        <div class="divide-y divide-zinc-100 dark:divide-zinc-700">
            <?php foreach ($recentLogs as $log):
                $ac = ['register'=>'green','verify'=>'blue','reinstall'=>'cyan','plugin_purchase'=>'purple','register_rejected'=>'red','suspend'=>'yellow','revoke'=>'red'][$log['action']] ?? 'zinc';
            ?>
            <div class="px-6 py-3">
                <div class="flex items-center gap-2">
                    <span class="px-1.5 py-0.5 text-[10px] font-bold rounded bg-<?= $ac ?>-100 text-<?= $ac ?>-700 dark:bg-<?= $ac ?>-900/30 dark:text-<?= $ac ?>-400 uppercase"><?= htmlspecialchars($log['action']) ?></span>
                    <span class="text-sm text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($log['domain'] ?? '-') ?></span>
                    <span class="text-xs text-zinc-400 ml-auto"><?= $log['created_at'] ? date('m-d H:i', strtotime($log['created_at'])) : '' ?></span>
                </div>
                <?php if ($log['license_key']): ?>
                <p class="text-xs text-zinc-400 font-mono mt-0.5"><?= htmlspecialchars(substr($log['license_key'], 0, 8)) ?>...</p>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
            <?php if (empty($recentLogs)): ?>
            <p class="px-6 py-8 text-center text-zinc-400 text-sm"><?= __lm('no_logs') ?></p>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php if (!empty($planStats)): ?>
<div class="mt-6 bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
    <h3 class="font-semibold text-zinc-800 dark:text-zinc-200 mb-4"><?= __lm('plan_distribution') ?></h3>
    <div class="flex gap-4">
        <?php
        $planLabels = ['free'=>'Free','standard'=>'Standard','professional'=>'Professional','enterprise'=>'Enterprise'];
        $planColors = ['free'=>'blue','standard'=>'green','professional'=>'purple','enterprise'=>'amber'];
        foreach ($planLabels as $pk => $pl):
            $cnt = $planStats[$pk] ?? 0;
            $pc = $planColors[$pk] ?? 'zinc';
        ?>
        <div class="flex-1 text-center p-3 rounded-lg bg-<?= $pc ?>-50 dark:bg-<?= $pc ?>-900/20">
            <p class="text-2xl font-bold text-<?= $pc ?>-600 dark:text-<?= $pc ?>-400"><?= $cnt ?></p>
            <p class="text-xs text-zinc-500 mt-1"><?= $pl ?></p>
        </div>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<?php include BASE_PATH . '/resources/views/admin/reservations/_foot.php'; ?>
