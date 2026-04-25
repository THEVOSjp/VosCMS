<?php
/**
 * VosCMS Marketplace - 설정 페이지
 */
include __DIR__ . '/_head.php';
$pageHeaderTitle = __('autoinstall.title');
$pageSubTitle = __('autoinstall.settings');

$pm = $pluginManager ?? \RzxLib\Core\Plugin\PluginManager::getInstance();
$apiUrl = $pm ? $pm->getSetting('vos-autoinstall', 'market_api_url', 'https://market.21ces.com/api/market') : '';
$autoUpdate = $pm ? $pm->getSetting('vos-autoinstall', 'auto_update_check', '1') : '1';
$updateInterval = $pm ? $pm->getSetting('vos-autoinstall', 'update_check_interval', '86400') : '86400';
$cacheTtl = $pm ? $pm->getSetting('vos-autoinstall', 'cache_ttl', '300') : '300';

$successMsg = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && ($_POST['_action'] ?? '') === 'save_settings') {
    if ($pm) {
        $pm->setSetting('vos-autoinstall', 'market_api_url', trim($_POST['api_url'] ?? ''));
        $pm->setSetting('vos-autoinstall', 'auto_update_check', isset($_POST['auto_update']) ? '1' : '0');
        $pm->setSetting('vos-autoinstall', 'update_check_interval', (string)(int)($_POST['update_interval'] ?? 86400));
        $pm->setSetting('vos-autoinstall', 'cache_ttl', (string)(int)($_POST['cache_ttl'] ?? 300));
        $apiUrl = trim($_POST['api_url'] ?? '');
        $autoUpdate = isset($_POST['auto_update']) ? '1' : '0';
        $updateInterval = (string)(int)($_POST['update_interval'] ?? 86400);
        $cacheTtl = (string)(int)($_POST['cache_ttl'] ?? 300);
        // 캐시 TTL/API URL 변경 시 기존 캐시 파일 즉시 무효화
        foreach (glob(BASE_PATH . '/storage/cache/mp_api_*.json') ?: [] as $f) { @unlink($f); }
        $successMsg = __('autoinstall.settings_saved');
    }
}
?>

<div class="max-w-2xl space-y-6">
    <?php if ($successMsg): ?>
    <div class="p-4 bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-400 rounded-lg text-sm font-medium">
        <?= htmlspecialchars($successMsg) ?>
    </div>
    <?php endif; ?>

    <form method="POST" class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <input type="hidden" name="_action" value="save_settings">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200"><?= __('autoinstall.settings') ?></h3>
        </div>

        <div class="px-6 py-5 space-y-5">
            <!-- API URL -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('autoinstall.api_url') ?></label>
                <input type="url" name="api_url" value="<?= htmlspecialchars($apiUrl) ?>"
                       class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-700 border border-zinc-200 dark:border-zinc-600 rounded-lg text-sm text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-indigo-500 focus:border-transparent">
                <p class="text-xs text-zinc-400 mt-1"><?= __('autoinstall.api_url_desc') ?></p>
            </div>

            <!-- 자동 업데이트 -->
            <div class="flex items-center justify-between">
                <div>
                    <label class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= __('autoinstall.auto_update_check') ?></label>
                    <p class="text-xs text-zinc-400 mt-0.5"><?= __('autoinstall.auto_update_check_desc') ?></p>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" name="auto_update" value="1" <?= $autoUpdate === '1' ? 'checked' : '' ?> class="sr-only peer">
                    <div class="w-11 h-6 bg-zinc-200 peer-focus:ring-2 peer-focus:ring-indigo-300 dark:peer-focus:ring-indigo-800 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-zinc-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all dark:border-zinc-600 peer-checked:bg-indigo-600"></div>
                </label>
            </div>

            <!-- 업데이트 주기 -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('autoinstall.update_interval') ?></label>
                <select name="update_interval" class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-700 border border-zinc-200 dark:border-zinc-600 rounded-lg text-sm text-zinc-800 dark:text-zinc-200">
                    <option value="3600" <?= $updateInterval === '3600' ? 'selected' : '' ?>>1 hour</option>
                    <option value="21600" <?= $updateInterval === '21600' ? 'selected' : '' ?>>6 hours</option>
                    <option value="43200" <?= $updateInterval === '43200' ? 'selected' : '' ?>>12 hours</option>
                    <option value="86400" <?= $updateInterval === '86400' ? 'selected' : '' ?>>24 hours</option>
                    <option value="604800" <?= $updateInterval === '604800' ? 'selected' : '' ?>>7 days</option>
                </select>
                <p class="text-xs text-zinc-400 mt-1"><?= __('autoinstall.update_interval_desc') ?></p>
            </div>

            <!-- 캐시 유지 시간 -->
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('autoinstall.cache_ttl') ?></label>
                <select name="cache_ttl" class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-700 border border-zinc-200 dark:border-zinc-600 rounded-lg text-sm text-zinc-800 dark:text-zinc-200">
                    <option value="60"   <?= $cacheTtl === '60'   ? 'selected' : '' ?>><?= __('autoinstall.cache_ttl_60') ?></option>
                    <option value="300"  <?= $cacheTtl === '300'  ? 'selected' : '' ?>><?= __('autoinstall.cache_ttl_300') ?></option>
                    <option value="600"  <?= $cacheTtl === '600'  ? 'selected' : '' ?>><?= __('autoinstall.cache_ttl_600') ?></option>
                    <option value="1800" <?= $cacheTtl === '1800' ? 'selected' : '' ?>><?= __('autoinstall.cache_ttl_1800') ?></option>
                    <option value="3600" <?= $cacheTtl === '3600' ? 'selected' : '' ?>><?= __('autoinstall.cache_ttl_3600') ?></option>
                </select>
                <p class="text-xs text-zinc-400 mt-1"><?= __('autoinstall.cache_ttl_desc') ?></p>
            </div>
        </div>

        <div class="px-6 py-4 bg-zinc-50 dark:bg-zinc-700/30 border-t border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
            <button type="submit" class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors">
                <?= __('autoinstall.save_settings') ?>
            </button>
        </div>
    </form>

    <!-- 카탈로그 동기화 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <h3 class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 mb-2"><?= __('autoinstall.sync_catalog') ?></h3>
        <p class="text-xs text-zinc-400 mb-4"><?= __('autoinstall.sync_catalog_desc') ?></p>
        <button onclick="syncCatalog(this)" class="px-4 py-2 bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 text-zinc-700 dark:text-zinc-300 text-sm font-medium rounded-lg transition-colors">
            <?= __('autoinstall.sync_now') ?>
        </button>
    </div>
</div>

<script>
function syncCatalog(btn) {
    btn.disabled = true;
    btn.textContent = 'Syncing...';
    fetch('<?= $adminUrl ?>/autoinstall/api', {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=sync_catalog'
    })
    .then(r => r.json())
    .then(data => {
        alert(data.message || (data.success ? 'Synced: ' + (data.synced || 0) + ' items' : 'Sync failed'));
        btn.disabled = false;
        btn.textContent = '<?= __('autoinstall.sync_now') ?>';
    });
}
</script>

<?php include __DIR__ . '/_foot.php'; ?>
