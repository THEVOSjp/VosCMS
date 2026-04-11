<?php
/**
 * VosCMS - 플러그인 관리 페이지
 */
$pageHeaderTitle = __('admin.nav.plugins');
$pageSubTitle = __('admin.nav.plugins');
$pageSubDesc = __('plugins.description');

if (!isset($adminUrl)) {
    $adminUrl = '/' . ($config['admin_path'] ?? 'admin');
}

$pm = $pluginManager ?? \RzxLib\Core\Plugin\PluginManager::getInstance();
$installed = $pm ? $pm->getInstalled() : [];
$available = $pm ? $pm->getAvailable() : [];

// 설치되지 않은 플러그인만 필터
$installedIds = array_column($installed, 'plugin_id');
$notInstalled = array_filter($available, fn($p) => !in_array($p['id'], $installedIds));

// 업데이트 가능한 플러그인 체크
$_pluginUpdates = [];
try {
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
    $_updatePdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4", $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
    foreach ($installed as $_ip) {
        $_uStmt = $_updatePdo->prepare("SELECT latest_version FROM {$prefix}mp_items WHERE slug = ? AND status = 'active'");
        $_uStmt->execute([$_ip['plugin_id']]);
        $_uRow = $_uStmt->fetch(PDO::FETCH_ASSOC);
        if ($_uRow && version_compare($_uRow['latest_version'], $_ip['version'], '>')) {
            $_pluginUpdates[$_ip['plugin_id']] = $_uRow['latest_version'];
        }
    }
} catch (\PDOException $e) {}

$locale = current_locale();
?>
<?php include __DIR__ . '/reservations/_head.php'; ?>

<div class="space-y-6">
    <!-- 설치된 플러그인 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
            <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200"><?= __('plugins.installed') ?></h3>
            <span class="text-sm text-zinc-500"><?= count($installed) ?><?= __('plugins.count_unit') ?></span>
        </div>

        <?php if (empty($installed)): ?>
        <div class="px-6 py-12 text-center text-zinc-400">
            <svg class="w-12 h-12 mx-auto mb-3 text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 7v10c0 2.21 3.582 4 8 4s8-1.79 8-4V7M4 7c0 2.21 3.582 4 8 4s8-1.79 8-4M4 7c0-2.21 3.582-4 8-4s8 1.79 8 4"/>
            </svg>
            <p><?= __('plugins.no_installed') ?></p>
        </div>
        <?php else: ?>
        <div class="divide-y divide-zinc-200 dark:divide-zinc-700">
            <?php foreach ($installed as $p):
                $_manifest = $pm->getManifest($p['plugin_id']);
                $_icon = $_manifest['menus']['admin'][0]['items'][0]['icon'] ?? 'M11 4a2 2 0 114 0v1a1 1 0 001 1h3a1 1 0 011 1v3a1 1 0 01-1 1h-1a2 2 0 100 4h1a1 1 0 011 1v3a1 1 0 01-1 1h-3a1 1 0 01-1-1v-1a2 2 0 10-4 0v1a1 1 0 01-1 1H7a1 1 0 01-1-1v-3a1 1 0 00-1-1H4a2 2 0 110-4h1a1 1 0 001-1V7a1 1 0 011-1h3a1 1 0 001-1V4z';
            ?>
            <div class="px-6 py-4 flex items-center justify-between">
                <div class="flex items-center gap-4">
                    <div class="w-10 h-10 rounded-lg bg-indigo-100 dark:bg-indigo-900/30 flex items-center justify-center">
                        <svg class="w-5 h-5 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $_icon ?>"/>
                        </svg>
                    </div>
                    <div>
                        <h4 class="font-semibold text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($p['title']) ?></h4>
                        <p class="text-sm text-zinc-500"><?= htmlspecialchars($p['description'] ?? '') ?></p>
                        <div class="flex items-center gap-3 mt-1 text-xs text-zinc-400">
                            <span>v<?= htmlspecialchars($p['version']) ?></span>
                            <?php if (!empty($_pluginUpdates[$p['plugin_id']])): ?>
                            <span class="px-1.5 py-0.5 bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400 rounded font-medium">
                                v<?= htmlspecialchars($_pluginUpdates[$p['plugin_id']]) ?> available
                            </span>
                            <?php endif; ?>
                            <?php if ($p['author']): ?><span><?= htmlspecialchars($p['author']) ?></span><?php endif; ?>
                            <span><?= $p['installed_at'] ? date('Y-m-d', strtotime($p['installed_at'])) : '' ?></span>
                        </div>
                    </div>
                </div>
                <div class="flex items-center gap-2">
                    <?php if ($p['is_active']): ?>
                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400"><?= __('plugins.active') ?></span>
                    <button onclick="pluginAction('deactivate', '<?= $p['plugin_id'] ?>')"
                            class="px-3 py-1.5 text-xs font-medium text-zinc-600 bg-zinc-100 hover:bg-zinc-200 dark:text-zinc-300 dark:bg-zinc-700 dark:hover:bg-zinc-600 rounded-lg transition-colors">
                        <?= __('plugins.deactivate') ?>
                    </button>
                    <?php else: ?>
                    <span class="px-2 py-1 text-xs font-medium rounded-full bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400"><?= __('plugins.inactive') ?></span>
                    <button onclick="pluginAction('activate', '<?= $p['plugin_id'] ?>')"
                            class="px-3 py-1.5 text-xs font-medium text-white bg-indigo-600 hover:bg-indigo-700 rounded-lg transition-colors">
                        <?= __('plugins.activate') ?>
                    </button>
                    <?php endif; ?>
                    <?php if (!empty($_pluginUpdates[$p['plugin_id']])): ?>
                    <button onclick="updatePlugin('<?= $p['plugin_id'] ?>')"
                            class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition-colors">
                        Update
                    </button>
                    <?php endif; ?>
                    <button onclick="pluginAction('uninstall', '<?= $p['plugin_id'] ?>')"
                            class="px-3 py-1.5 text-xs font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition-colors">
                        <?= __('plugins.uninstall') ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
    </div>

    <!-- 사용 가능한 플러그인 (미설치) -->
    <?php if (!empty($notInstalled)): ?>
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200"><?= __('plugins.available') ?></h3>
        </div>
        <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 p-6">
            <?php foreach ($notInstalled as $p):
                $pTitle = is_array($p['name']) ? ($p['name'][$locale] ?? $p['name']['en'] ?? $p['id']) : $p['name'];
                $pDesc = is_array($p['description'] ?? '') ? ($p['description'][$locale] ?? $p['description']['en'] ?? '') : ($p['description'] ?? '');
            ?>
            <div class="border border-zinc-200 dark:border-zinc-700 rounded-xl p-4">
                <h4 class="font-semibold text-zinc-800 dark:text-zinc-200 mb-1"><?= htmlspecialchars($pTitle) ?></h4>
                <p class="text-sm text-zinc-500 mb-3"><?= htmlspecialchars($pDesc) ?></p>
                <div class="flex items-center justify-between">
                    <span class="text-xs text-zinc-400">v<?= htmlspecialchars($p['version'] ?? '1.0.0') ?></span>
                    <button onclick="pluginAction('install', '<?= $p['id'] ?>')"
                            class="px-3 py-1.5 text-xs font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition-colors">
                        <?= __('plugins.install') ?>
                    </button>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<script>
async function updatePlugin(pluginId) {
    if (!confirm('Update ' + pluginId + '?')) return;
    const btn = event.target;
    btn.disabled = true;
    btn.textContent = 'Updating...';
    try {
        const res = await fetch('<?= $adminUrl ?>/marketplace/install', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'item_slug=' + pluginId + '&action=update'
        });
        const data = await res.json();
        if (data.success) {
            alert('Update complete!');
            location.reload();
        } else {
            alert(data.message || 'Update failed');
            btn.disabled = false;
            btn.textContent = 'Update';
        }
    } catch (err) {
        alert('Network error');
        btn.disabled = false;
        btn.textContent = 'Update';
    }
}

async function pluginAction(action, pluginId) {
    if (action === 'uninstall' && !confirm('<?= __('plugins.confirm_uninstall') ?>')) return;

    const resp = await fetch('<?= $adminUrl ?>/plugins/api', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ action, plugin_id: pluginId })
    });
    const data = await resp.json();
    alert(data.message || (data.success ? 'OK' : 'Error'));
    if (data.success) location.reload();
}
</script>
