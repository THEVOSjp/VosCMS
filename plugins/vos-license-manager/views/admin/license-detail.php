<?php
/**
 * License Manager - 라이선스 상세
 */
$pageHeaderTitle = 'License Manager';
include __DIR__ . '/_head.php';

$key = trim($_GET['key'] ?? '');
if (!$key) { header("Location: {$adminUrl}/license-manager/licenses"); exit; }

$lic = $_lmPdo->prepare("SELECT * FROM vcs_licenses WHERE license_key = ?");
$lic->execute([$key]);
$license = $lic->fetch();

if (!$license) { echo '<div class="p-4 text-red-500">License not found</div>'; include BASE_PATH . '/resources/views/admin/reservations/_foot.php'; return; }

$licId = (int)$license['id'];

// 플러그인 목록
$plugins = $_lmPdo->prepare("SELECT * FROM vcs_license_plugins WHERE license_id = ? ORDER BY purchased_at DESC");
$plugins->execute([$licId]);
$pluginList = $plugins->fetchAll();

// 로그
$logs = $_lmPdo->prepare("SELECT * FROM vcs_license_logs WHERE license_id = ? ORDER BY created_at DESC LIMIT 30");
$logs->execute([$licId]);
$logList = $logs->fetchAll();

$sc = ['active'=>'green','suspended'=>'yellow','revoked'=>'red'][$license['status']] ?? 'zinc';
$pc = ['free'=>'blue','standard'=>'green','professional'=>'purple','enterprise'=>'amber'][$license['plan']] ?? 'zinc';
?>

<div class="mb-6">
    <a href="<?= $adminUrl ?>/license-manager/licenses" class="text-sm text-indigo-600 dark:text-indigo-400 hover:underline flex items-center gap-1 mb-3">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        <?= __lm('licenses') ?>
    </a>
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($license['domain']) ?></h1>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- 좌측: 상세 정보 -->
    <div class="lg:col-span-2 space-y-6">
        <!-- 기본 정보 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-4"><?= __lm('license_info') ?></h3>
            <div class="grid grid-cols-2 gap-4 text-sm">
                <div>
                    <span class="text-zinc-500">License Key</span>
                    <p class="font-mono text-zinc-800 dark:text-zinc-200 mt-1 select-all"><?= htmlspecialchars($license['license_key']) ?></p>
                </div>
                <div>
                    <span class="text-zinc-500">Domain</span>
                    <p class="text-zinc-800 dark:text-zinc-200 mt-1"><?= htmlspecialchars($license['domain']) ?></p>
                </div>
                <div>
                    <span class="text-zinc-500">Plan</span>
                    <p class="mt-1"><span class="px-2 py-0.5 text-xs font-medium rounded-full bg-<?= $pc ?>-100 text-<?= $pc ?>-700"><?= ucfirst($license['plan']) ?></span></p>
                </div>
                <div>
                    <span class="text-zinc-500">Status</span>
                    <p class="mt-1"><span class="px-2 py-0.5 text-xs font-medium rounded-full bg-<?= $sc ?>-100 text-<?= $sc ?>-700"><?= ucfirst($license['status']) ?></span></p>
                </div>
                <div>
                    <span class="text-zinc-500">VosCMS Version</span>
                    <p class="text-zinc-800 dark:text-zinc-200 mt-1"><?= htmlspecialchars($license['voscms_version'] ?? '-') ?></p>
                </div>
                <div>
                    <span class="text-zinc-500">PHP Version</span>
                    <p class="text-zinc-800 dark:text-zinc-200 mt-1"><?= htmlspecialchars($license['php_version'] ?? '-') ?></p>
                </div>
                <div>
                    <span class="text-zinc-500">Server IP</span>
                    <p class="text-zinc-800 dark:text-zinc-200 mt-1"><?= htmlspecialchars($license['server_ip'] ?? '-') ?></p>
                </div>
                <div>
                    <span class="text-zinc-500">Registered</span>
                    <p class="text-zinc-800 dark:text-zinc-200 mt-1"><?= $license['registered_at'] ? date('Y-m-d H:i', strtotime($license['registered_at'])) : '-' ?></p>
                </div>
                <div>
                    <span class="text-zinc-500">Last Verified</span>
                    <p class="text-zinc-800 dark:text-zinc-200 mt-1"><?= $license['last_verified_at'] ? date('Y-m-d H:i', strtotime($license['last_verified_at'])) : '-' ?></p>
                </div>
                <div>
                    <span class="text-zinc-500">Expires</span>
                    <p class="text-zinc-800 dark:text-zinc-200 mt-1"><?= $license['expires_at'] ? date('Y-m-d', strtotime($license['expires_at'])) : 'Perpetual' ?></p>
                </div>
            </div>
        </div>

        <!-- 허용 플러그인 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
                <h3 class="font-semibold text-zinc-800 dark:text-zinc-200"><?= __lm('allowed_plugins') ?> (<?= count($pluginList) ?>)</h3>
                <button onclick="document.getElementById('addPluginForm').classList.toggle('hidden')" class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline">+ <?= __lm('add') ?></button>
            </div>
            <!-- 플러그인 추가 폼 -->
            <div id="addPluginForm" class="hidden px-6 py-3 bg-zinc-50 dark:bg-zinc-700/30 border-b border-zinc-200 dark:border-zinc-700">
                <form onsubmit="addPlugin(event)" class="flex gap-2">
                    <input type="text" id="newPluginId" placeholder="<?= __lm('add_plugin_placeholder') ?>" class="flex-1 px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200">
                    <button type="submit" class="px-4 py-2 text-sm bg-indigo-600 text-white rounded-lg hover:bg-indigo-700"><?= __lm('add') ?></button>
                </form>
            </div>
            <?php if (empty($pluginList)): ?>
            <p class="px-6 py-8 text-center text-zinc-400 text-sm"><?= __lm('no_plugins') ?></p>
            <?php else: ?>
            <div class="divide-y divide-zinc-100 dark:divide-zinc-700">
                <?php foreach ($pluginList as $pl): ?>
                <div class="px-6 py-3 flex items-center justify-between">
                    <div>
                        <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($pl['plugin_id']) ?></span>
                        <span class="text-xs text-zinc-400 ml-2"><?= $pl['purchased_at'] ? date('Y-m-d', strtotime($pl['purchased_at'])) : '' ?></span>
                        <?php if ($pl['order_id']): ?><span class="text-xs text-zinc-400 ml-1">(<?= htmlspecialchars($pl['order_id']) ?>)</span><?php endif; ?>
                    </div>
                    <div class="flex items-center gap-2">
                        <span class="px-2 py-0.5 text-xs rounded-full bg-<?= $pl['status'] === 'active' ? 'green' : 'red' ?>-100 text-<?= $pl['status'] === 'active' ? 'green' : 'red' ?>-700"><?= $pl['status'] ?></span>
                        <button onclick="revokePlugin(<?= $pl['id'] ?>, '<?= htmlspecialchars($pl['plugin_id']) ?>')" class="text-xs text-red-500 hover:text-red-600"><?= __lm('revoke') ?></button>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>

        <!-- <?= __lm('activity_log') ?> -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
                <h3 class="font-semibold text-zinc-800 dark:text-zinc-200"><?= __lm('activity_log') ?></h3>
            </div>
            <div class="divide-y divide-zinc-100 dark:divide-zinc-700 max-h-96 overflow-y-auto">
                <?php foreach ($logList as $log):
                    $ac = ['register'=>'green','verify'=>'blue','reinstall'=>'cyan','plugin_purchase'=>'purple','register_rejected'=>'red','suspend'=>'yellow','revoke'=>'red','domain_change'=>'orange'][$log['action']] ?? 'zinc';
                    $details = json_decode($log['details'] ?? '{}', true);
                ?>
                <div class="px-6 py-3">
                    <div class="flex items-center gap-2">
                        <span class="px-1.5 py-0.5 text-[10px] font-bold rounded bg-<?= $ac ?>-100 text-<?= $ac ?>-700 dark:bg-<?= $ac ?>-900/30 dark:text-<?= $ac ?>-400 uppercase"><?= htmlspecialchars($log['action']) ?></span>
                        <span class="text-xs text-zinc-400"><?= $log['ip_address'] ?? '' ?></span>
                        <span class="text-xs text-zinc-400 ml-auto"><?= $log['created_at'] ? date('Y-m-d H:i:s', strtotime($log['created_at'])) : '' ?></span>
                    </div>
                    <?php if (!empty($details)): ?>
                    <p class="text-xs text-zinc-500 mt-1 font-mono"><?= htmlspecialchars(json_encode($details, JSON_UNESCAPED_UNICODE)) ?></p>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php if (empty($logList)): ?>
                <p class="px-6 py-6 text-center text-zinc-400 text-sm"><?= __lm('no_logs') ?></p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- 우측: 액션 -->
    <div class="space-y-4">
        <!-- <?= __lm('status_change') ?> -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-5">
            <h4 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider mb-3"><?= __lm('status_change') ?></h4>
            <div class="space-y-2">
                <?php if ($license['status'] !== 'active'): ?>
                <button onclick="changeStatus('active')" class="w-full px-4 py-2 text-sm font-medium text-white bg-green-600 hover:bg-green-700 rounded-lg transition"><?= __lm('set_active') ?></button>
                <?php endif; ?>
                <?php if ($license['status'] !== 'suspended'): ?>
                <button onclick="changeStatus('suspended')" class="w-full px-4 py-2 text-sm font-medium text-yellow-700 bg-yellow-100 hover:bg-yellow-200 dark:bg-yellow-900/30 dark:hover:bg-yellow-900/50 rounded-lg transition"><?= __lm('set_suspend') ?></button>
                <?php endif; ?>
                <?php if ($license['status'] !== 'revoked'): ?>
                <button onclick="changeStatus('revoked')" class="w-full px-4 py-2 text-sm font-medium text-red-700 bg-red-100 hover:bg-red-200 dark:bg-red-900/30 dark:hover:bg-red-900/50 rounded-lg transition"><?= __lm('set_revoke') ?></button>
                <?php endif; ?>
            </div>
        </div>

        <!-- 플랜 변경 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-5">
            <h4 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider mb-3"><?= __lm('plan_change') ?></h4>
            <select id="planSelect" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 mb-2">
                <option value="free" <?= $license['plan'] === 'free' ? 'selected' : '' ?>>Free</option>
                <option value="standard" <?= $license['plan'] === 'standard' ? 'selected' : '' ?>>Standard</option>
                <option value="professional" <?= $license['plan'] === 'professional' ? 'selected' : '' ?>>Professional</option>
                <option value="enterprise" <?= $license['plan'] === 'enterprise' ? 'selected' : '' ?>>Enterprise</option>
            </select>
            <button onclick="changePlan()" class="w-full px-4 py-2 text-sm font-medium text-indigo-600 bg-indigo-50 hover:bg-indigo-100 dark:bg-indigo-900/30 dark:hover:bg-indigo-900/50 rounded-lg transition"><?= __lm('plan_change') ?></button>
        </div>

        <!-- 도메인 변경 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-5">
            <h4 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wider mb-3"><?= __lm('domain_change') ?></h4>
            <input type="text" id="newDomain" placeholder="<?= __lm('domain_new_placeholder') ?>" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 mb-2">
            <button onclick="changeDomain()" class="w-full px-4 py-2 text-sm font-medium text-orange-600 bg-orange-50 hover:bg-orange-100 dark:bg-orange-900/30 dark:hover:bg-orange-900/50 rounded-lg transition"><?= __lm('domain_change_btn') ?></button>
            <p class="text-xs text-zinc-400 mt-2"><?= __lm('domain_change_note') ?></p>
        </div>
    </div>
</div>

<script>
const API = '<?= $adminUrl ?>/license-manager/api';
const licId = <?= $licId ?>;

function changeStatus(status) {
    if (!confirm(status + ' + '<?= __lm("confirm_status") ?>')) return;
    fetch(API, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=change_status&license_id='+licId+'&status='+status
    }).then(r=>r.json()).then(d => { if(d.success) location.reload(); else alert(d.message||'Error'); });
}

function changePlan() {
    const plan = document.getElementById('planSelect').value;
    if (!confirm(plan + '<?= __lm("confirm_plan") ?>' + plan + '?')) return;
    fetch(API, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=change_plan&license_id='+licId+'&plan='+plan
    }).then(r=>r.json()).then(d => { if(d.success) location.reload(); else alert(d.message||'Error'); });
}

function changeDomain() {
    const domain = document.getElementById('newDomain').value.trim();
    if (!domain) { alert('도메인을 입력하세요.'); return; }
    if (!confirm(domain + '으로 도메인을 변경하시겠습니까?\n기존 도메인은 즉시 무효화됩니다.')) return;
    fetch(API, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=change_domain&license_id='+licId+'&domain='+encodeURIComponent(domain)
    }).then(r=>r.json()).then(d => { if(d.success) location.reload(); else alert(d.message||'Error'); });
}

function addPlugin(e) {
    e.preventDefault();
    const pluginId = document.getElementById('newPluginId').value.trim();
    if (!pluginId) return;
    fetch(API, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=add_plugin&license_id='+licId+'&plugin_id='+encodeURIComponent(pluginId)
    }).then(r=>r.json()).then(d => { if(d.success) location.reload(); else alert(d.message||'Error'); });
}

function revokePlugin(pluginLicId, pluginId) {
    if (!confirm(pluginId + ' 플러그인을 취소하시겠습니까?')) return;
    fetch(API, { method:'POST', headers:{'Content-Type':'application/x-www-form-urlencoded'},
        body:'action=revoke_plugin&plugin_license_id='+pluginLicId
    }).then(r=>r.json()).then(d => { if(d.success) location.reload(); else alert(d.message||'Error'); });
}
</script>

<?php include BASE_PATH . '/resources/views/admin/reservations/_foot.php'; ?>
