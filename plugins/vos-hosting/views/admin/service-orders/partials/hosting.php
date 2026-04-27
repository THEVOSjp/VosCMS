<?php
/**
 * 관리자 서비스 상세 — 웹 호스팅 탭
 * $subs: hosting 타입 구독 배열
 * $order, $statusLabels, $fmtPrice, $adminUrl, $pdo, $prefix 사용
 */
$sub = $subs[0];
$sst = $statusLabels[$sub['status']] ?? [__('services.mypage.status_unknown'), 'bg-gray-100 text-gray-500'];
$_pendingTitle = __('services.admin_orders.btn_pending');
$sc = $sub['service_class'] ?? 'recurring';
$meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
$capacity = $meta['capacity'] ?? $order['hosting_capacity'] ?? '-';

// 서버 접속정보 (metadata.server에 저장)
$server = $meta['server'] ?? [];
$ftp = $server['ftp'] ?? [];
$db = $server['db'] ?? [];
$env = $server['env'] ?? [];
$usage = $server['usage'] ?? [];
?>

<!-- 호스팅 정보 요약 -->
<div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700">
    <div class="flex items-center justify-between mb-3">
        <div class="flex items-center gap-2">
            <p class="text-sm font-semibold text-zinc-900 dark:text-white"><?= htmlspecialchars($_localizeLabel($sub)) ?></p>
            <span class="text-[10px] px-1.5 py-0.5 rounded-full font-medium <?= $sst[1] ?>"><?= htmlspecialchars($sst[0]) ?></span>
        </div>
        <div class="flex items-center gap-2">
            <span class="text-xs text-zinc-400"><?= (int)$sub['billing_amount'] > 0 ? $fmtPrice($sub['billing_amount'], $sub['currency']) : __('services.order.summary.free') ?></span>
            <?php if ($sub['auto_renew']): ?>
            <span class="text-[10px] px-1.5 py-0.5 bg-blue-50 text-blue-600 rounded-full"><?= htmlspecialchars(__('services.detail.auto_renew')) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <div class="grid grid-cols-2 sm:grid-cols-4 gap-3 text-xs">
        <div>
            <p class="text-[10px] text-zinc-400 mb-0.5"><?= htmlspecialchars(__('services.detail.f_capacity')) ?></p>
            <p class="font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($capacity) ?></p>
        </div>
        <div>
            <p class="text-[10px] text-zinc-400 mb-0.5"><?= htmlspecialchars(__('services.detail.f_period')) ?></p>
            <p class="font-medium text-zinc-800 dark:text-zinc-200"><?= date('Y-m-d', strtotime($sub['started_at'])) ?> ~ <?= date('Y-m-d', strtotime($sub['expires_at'])) ?></p>
        </div>
        <div>
            <p class="text-[10px] text-zinc-400 mb-0.5"><?= htmlspecialchars(__('services.detail.f_server_env')) ?></p>
            <p class="font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($env['php'] ?? '-') ?> / <?= htmlspecialchars($env['mysql'] ?? '-') ?></p>
        </div>
        <div>
            <p class="text-[10px] text-zinc-400 mb-0.5"><?= htmlspecialchars(__('services.detail.f_id')) ?></p>
            <p class="font-medium text-zinc-800 dark:text-zinc-200 font-mono"><?= htmlspecialchars($ftp['user'] ?? '-') ?></p>
        </div>
    </div>
</div>

<!-- 사용량 -->
<?php if (!empty($usage['hdd_used']) || !empty($usage['traffic_used'])): ?>
<div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700">
    <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-3"><?= htmlspecialchars(__('services.admin_orders.usage_section_admin')) ?></p>
    <div class="grid grid-cols-2 gap-4">
        <?php if (!empty($usage['hdd_total'])): ?>
        <div>
            <div class="flex items-center justify-between text-xs mb-1">
                <span class="text-zinc-500"><?= htmlspecialchars(__('services.detail.f_hdd_capacity')) ?></span>
                <span class="font-medium"><?= htmlspecialchars($usage['hdd_used'] ?? '0') ?> / <?= htmlspecialchars($usage['hdd_total']) ?></span>
            </div>
            <?php $hddPct = $usage['hdd_total'] ? round(((float)$usage['hdd_used'] / (float)$usage['hdd_total']) * 100, 1) : 0; ?>
            <div class="w-full bg-gray-200 dark:bg-zinc-600 rounded-full h-2">
                <div class="bg-blue-500 h-2 rounded-full" style="width: <?= min(100, $hddPct) ?>%"></div>
            </div>
            <p class="text-[10px] text-zinc-400 mt-0.5 text-right"><?= $hddPct ?>%</p>
        </div>
        <?php endif; ?>
        <?php if (!empty($usage['traffic_total'])): ?>
        <div>
            <div class="flex items-center justify-between text-xs mb-1">
                <span class="text-zinc-500"><?= htmlspecialchars(__('services.admin_orders.f_traffic_capacity')) ?></span>
                <span class="font-medium"><?= htmlspecialchars($usage['traffic_used'] ?? '0') ?> / <?= htmlspecialchars($usage['traffic_total']) ?></span>
            </div>
            <?php $trafPct = $usage['traffic_total'] ? round(((float)$usage['traffic_used'] / (float)$usage['traffic_total']) * 100, 1) : 0; ?>
            <div class="w-full bg-gray-200 dark:bg-zinc-600 rounded-full h-2">
                <div class="bg-green-500 h-2 rounded-full" style="width: <?= min(100, $trafPct) ?>%"></div>
            </div>
            <p class="text-[10px] text-zinc-400 mt-0.5 text-right"><?= $trafPct ?>%</p>
        </div>
        <?php endif; ?>
    </div>
</div>
<?php endif; ?>

<!-- FTP 접속정보 + DB 정보 -->
<div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700">
    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
        <!-- FTP -->
        <div>
            <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-2"><?= htmlspecialchars(__('services.admin_orders.ftp_section_title')) ?></p>
            <table class="w-full text-xs">
                <tbody class="divide-y divide-gray-100 dark:divide-zinc-700/50">
                    <tr><td class="py-1.5 text-zinc-400 w-24"><?= htmlspecialchars(__('services.admin_orders.f_ftp_host')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($ftp['host'] ?? $order['domain'] ?? '-') ?></td></tr>
                    <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.admin_orders.f_ftp_ip')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($ftp['ip'] ?? '-') ?></td></tr>
                    <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.admin_orders.f_ftp_id')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($ftp['user'] ?? '-') ?></td></tr>
                    <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.admin_orders.f_port_num')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($ftp['port'] ?? '21') ?></td></tr>
                </tbody>
            </table>
        </div>
        <!-- DB -->
        <div>
            <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-2"><?= htmlspecialchars(__('services.admin_orders.db_section_title')) ?></p>
            <table class="w-full text-xs">
                <tbody class="divide-y divide-gray-100 dark:divide-zinc-700/50">
                    <tr><td class="py-1.5 text-zinc-400 w-24"><?= htmlspecialchars(__('services.admin_orders.f_db_host')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($db['host'] ?? 'localhost') ?></td></tr>
                    <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.detail.f_db_name')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($db['name'] ?? '-') ?></td></tr>
                    <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.admin_orders.f_db_id')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($db['user'] ?? '-') ?></td></tr>
                    <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.admin_orders.f_db_size')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($db['size'] ?? __('services.detail.db_unlimited')) ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<!-- 서버 접속정보 편집 -->
<div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700">
    <div class="flex items-center justify-between mb-3">
        <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider"><?= htmlspecialchars(__('services.admin_orders.server_setup_section')) ?></p>
        <button onclick="toggleServerEdit()" id="btnServerEdit" class="text-xs text-blue-600 hover:underline"><?= htmlspecialchars(__('services.admin_orders.btn_edit')) ?></button>
    </div>
    <div id="serverEditForm" class="hidden space-y-4">
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
            <div><label class="text-[10px] text-zinc-400 block mb-1"><?= htmlspecialchars(__('services.admin_orders.f_ftp_host')) ?></label><input id="sf_ftp_host" value="<?= htmlspecialchars($ftp['host'] ?? '') ?>" class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded font-mono text-xs" placeholder="<?= htmlspecialchars($order['domain'] ?? '') ?>"></div>
            <div><label class="text-[10px] text-zinc-400 block mb-1"><?= htmlspecialchars(__('services.admin_orders.f_ftp_ip')) ?></label><input id="sf_ftp_ip" value="<?= htmlspecialchars($ftp['ip'] ?? '') ?>" class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded font-mono text-xs" placeholder="0.0.0.0"></div>
            <div><label class="text-[10px] text-zinc-400 block mb-1"><?= htmlspecialchars(__('services.admin_orders.f_ftp_id')) ?></label><input id="sf_ftp_user" value="<?= htmlspecialchars($ftp['user'] ?? '') ?>" class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded font-mono text-xs"></div>
            <div><label class="text-[10px] text-zinc-400 block mb-1"><?= htmlspecialchars(__('services.detail.f_port')) ?></label><input id="sf_ftp_port" value="<?= htmlspecialchars($ftp['port'] ?? '21') ?>" class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded font-mono text-xs"></div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
            <div><label class="text-[10px] text-zinc-400 block mb-1"><?= htmlspecialchars(__('services.admin_orders.f_db_host')) ?></label><input id="sf_db_host" value="<?= htmlspecialchars($db['host'] ?? 'localhost') ?>" class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded font-mono text-xs"></div>
            <div><label class="text-[10px] text-zinc-400 block mb-1"><?= htmlspecialchars(__('services.detail.f_db_name')) ?></label><input id="sf_db_name" value="<?= htmlspecialchars($db['name'] ?? '') ?>" class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded font-mono text-xs"></div>
            <div><label class="text-[10px] text-zinc-400 block mb-1"><?= htmlspecialchars(__('services.admin_orders.f_db_id')) ?></label><input id="sf_db_user" value="<?= htmlspecialchars($db['user'] ?? '') ?>" class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded font-mono text-xs"></div>
            <div><label class="text-[10px] text-zinc-400 block mb-1"><?= htmlspecialchars(__('services.admin_orders.f_db_size')) ?></label><input id="sf_db_size" value="<?= htmlspecialchars($db['size'] ?? __('services.detail.db_unlimited')) ?>" class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded font-mono text-xs"></div>
        </div>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-3 text-xs">
            <div><label class="text-[10px] text-zinc-400 block mb-1"><?= htmlspecialchars(__('services.admin_orders.f_php_ver')) ?></label><input id="sf_env_php" value="<?= htmlspecialchars($env['php'] ?? 'PHP 8.3') ?>" class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded text-xs"></div>
            <div><label class="text-[10px] text-zinc-400 block mb-1"><?= htmlspecialchars(__('services.admin_orders.f_mysql_ver')) ?></label><input id="sf_env_mysql" value="<?= htmlspecialchars($env['mysql'] ?? 'MySQL 8.0') ?>" class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded text-xs"></div>
            <div><label class="text-[10px] text-zinc-400 block mb-1"><?= htmlspecialchars(__('services.admin_orders.f_hdd_total')) ?></label><input id="sf_hdd_total" value="<?= htmlspecialchars($usage['hdd_total'] ?? '') ?>" class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded text-xs" placeholder="1GB"></div>
            <div><label class="text-[10px] text-zinc-400 block mb-1"><?= htmlspecialchars(__('services.admin_orders.f_traffic_total')) ?></label><input id="sf_traf_total" value="<?= htmlspecialchars($usage['traffic_total'] ?? '') ?>" class="w-full px-2 py-1.5 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded text-xs" placeholder="10GB"></div>
        </div>
        <div class="flex justify-end gap-2">
            <button onclick="toggleServerEdit()" class="px-3 py-1.5 text-xs text-zinc-500 hover:text-zinc-700"><?= htmlspecialchars(__('services.admin_orders.btn_cancel') ?: __('services.detail.btn_cancel')) ?></button>
            <button onclick="saveServerInfo(<?= $sub['id'] ?>)" class="px-4 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700"><?= htmlspecialchars(__('services.admin_orders.btn_save')) ?></button>
        </div>
    </div>
</div>

<!-- 관리 버튼 -->
<div class="px-5 py-4 flex flex-wrap gap-2">
    <button onclick="sendSetupEmail(<?= (int)$order['id'] ?>)" class="px-3 py-1.5 text-xs font-medium text-blue-600 border border-blue-200 rounded-lg hover:bg-blue-50 transition"><?= htmlspecialchars(__('services.admin_orders.btn_setup_email')) ?></button>
    <button disabled class="px-3 py-1.5 text-xs text-zinc-400 border border-zinc-200 rounded-lg cursor-not-allowed opacity-50" title="<?= htmlspecialchars($_pendingTitle) ?>"><?= htmlspecialchars(__('services.admin_orders.btn_force_block')) ?></button>
    <button disabled class="px-3 py-1.5 text-xs text-zinc-400 border border-zinc-200 rounded-lg cursor-not-allowed opacity-50" title="<?= htmlspecialchars($_pendingTitle) ?>"><?= htmlspecialchars(__('services.admin_orders.btn_change_env')) ?></button>
    <button disabled class="px-3 py-1.5 text-xs text-zinc-400 border border-zinc-200 rounded-lg cursor-not-allowed opacity-50" title="<?= htmlspecialchars($_pendingTitle) ?>"><?= htmlspecialchars(__('services.detail.btn_change_pw')) ?></button>
</div>

<script>
function toggleServerEdit() {
    var form = document.getElementById('serverEditForm');
    form.classList.toggle('hidden');
    document.getElementById('btnServerEdit').textContent = form.classList.contains('hidden') ? <?= json_encode(__('services.admin_orders.btn_edit'), JSON_UNESCAPED_UNICODE) ?> : <?= json_encode(__('services.admin_orders.btn_collapse'), JSON_UNESCAPED_UNICODE) ?>;
}

function saveServerInfo(subId) {
    var data = {
        action: 'update_server_info',
        subscription_id: subId,
        server: {
            ftp: { host: document.getElementById('sf_ftp_host').value, ip: document.getElementById('sf_ftp_ip').value, user: document.getElementById('sf_ftp_user').value, port: document.getElementById('sf_ftp_port').value },
            db: { host: document.getElementById('sf_db_host').value, name: document.getElementById('sf_db_name').value, user: document.getElementById('sf_db_user').value, size: document.getElementById('sf_db_size').value },
            env: { php: document.getElementById('sf_env_php').value, mysql: document.getElementById('sf_env_mysql').value },
            usage: { hdd_total: document.getElementById('sf_hdd_total').value, traffic_total: document.getElementById('sf_traf_total').value }
        }
    };
    ajaxPost(data).then(function(d) {
        if (d.success) location.reload();
        else alert(d.message || <?= json_encode(__('services.admin_orders.alert_save_failed'), JSON_UNESCAPED_UNICODE) ?>);
    });
}

function sendSetupEmail(orderId) {
    if (!confirm(<?= json_encode(__('services.admin_orders.confirm_send_setup_email'), JSON_UNESCAPED_UNICODE) ?>)) return;
    ajaxPost({ action: 'send_setup_email', order_id: orderId }).then(function(d) {
        alert(d.message || (d.success ? <?= json_encode(__('services.admin_orders.alert_send_done'), JSON_UNESCAPED_UNICODE) ?> : <?= json_encode(__('services.admin_orders.alert_send_failed'), JSON_UNESCAPED_UNICODE) ?>));
    });
}
</script>
