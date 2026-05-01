<?php
/**
 * 관리자 서비스 상세 — 도메인 탭
 * $subs: domain 타입 구독 배열
 * $servicesByType: 전체 구독 그룹 (hosting subscription metadata 의 mail_provision 사용)
 * $order: 주문 정보 (domain, domain_option)
 */
// 호스팅 sub 의 added_domains 도 합쳐서 표시 (마이페이지 모달 또는 admin 병합으로 추가된 도메인)
$_addedDomains = [];
foreach ($servicesByType['hosting'] ?? [] as $_hSub) {
    $_hMeta = json_decode($_hSub['metadata'] ?? '{}', true) ?: [];
    foreach ($_hMeta['added_domains'] ?? [] as $_ad) {
        if (empty($_ad['domain'])) continue;
        $_addedDomains[] = [
            'name' => $_ad['domain'],
            'sub_id' => $_hSub['id'],
            'is_free' => ($_ad['option'] ?? '') === 'free',
            'is_existing' => ($_ad['option'] ?? '') === 'existing',
            'is_primary' => false,
            'expires_at' => $_ad['expires_at'] ?? '',
            'is_added' => true,
            'registrar_pending' => !empty($_ad['registrar_pending']),
            'manual_attach_pending' => !empty($_ad['manual_attach_pending']),
        ];
    }
}

if (empty($subs) && empty($_addedDomains)) {
    echo '<div class="px-5 py-12 text-center text-sm text-zinc-400">' . htmlspecialchars(__('services.admin_orders.empty_domain')) . '</div>';
    return;
}

// hosting subscription 의 mail_provision 정보 추출 (자동 프로비저닝 상태)
$_provisionInfo = null;
foreach ($servicesByType['hosting'] ?? [] as $_hSub) {
    $_hMeta = json_decode($_hSub['metadata'] ?? '{}', true) ?: [];
    if (!empty($_hMeta['mail_provision'])) {
        $_provisionInfo = $_hMeta['mail_provision'];
        break;
    }
}
$_provisionMode = $_provisionInfo['mode'] ?? null;
$_provisionedAt = $_provisionInfo['provisioned_at'] ?? null;
$_finalDomain = $_provisionInfo['final_domain'] ?? null;
$_zoneStatus = $_provisionInfo['zone_status'] ?? null;
$_nameServers = $_provisionInfo['name_servers'] ?? [];

$allDomains = [];
foreach ($subs as $sub) {
    $meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
    foreach ($meta['domains'] ?? [] as $dm) {
        $allDomains[] = [
            'name' => $dm,
            'sub_id' => $sub['id'],
            'is_free' => !empty($meta['free_subdomain']),
            'is_existing' => !empty($meta['existing']),
            'is_primary' => !empty($meta['primary_domain']) && $meta['primary_domain'] === $dm,
            'expires_at' => $sub['expires_at'],
            'is_added' => false,
            'registrar_pending' => !empty($meta['registrar_pending']),
            'manual_attach_pending' => !empty($meta['manual_attach_pending']),
        ];
    }
}
$allDomains = array_merge($allDomains, $_addedDomains);
if (!empty($allDomains) && !array_filter($allDomains, fn($d) => $d['is_primary'])) {
    $allDomains[0]['is_primary'] = true;
}
$firstSub = $subs[0] ?? ($servicesByType['hosting'][0] ?? null);
$fst = $firstSub ? ($statusLabels[$firstSub['status']] ?? [__('services.mypage.status_unknown'), 'bg-gray-100 text-gray-500']) : ['-', 'bg-gray-100 text-gray-500'];

// 네임서버 정보 (주문 metadata 또는 시스템 설정)
$orderMeta = $firstSub ? (json_decode($firstSub['metadata'] ?? '{}', true) ?: []) : [];
$nameservers = $orderMeta['nameservers'] ?? [];
$_pendingTitle = __('services.admin_orders.btn_pending');
?>

<!-- 메일 도메인 자동 프로비저닝 상태 (mode 기반) -->
<?php if ($_provisionMode): ?>
<div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700">
    <div class="flex items-center justify-between mb-3">
        <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">
            <?= htmlspecialchars(__('services.admin_orders.mail_provision_section')) ?>
        </p>
        <?php if ($_provisionedAt): ?>
        <span class="text-[10px] text-zinc-400"><?= htmlspecialchars(date('Y-m-d H:i', strtotime($_provisionedAt))) ?></span>
        <?php endif; ?>
    </div>

    <?php if ($_provisionMode === 'new_pending'): ?>
    <!-- 신규 구매 대기 — NameSilo 구매 + NS Cloudflare 변경 + 「등록 완료」 클릭 -->
    <div class="bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/50 rounded-lg px-4 py-3">
        <div class="flex items-start gap-3 mb-3">
            <svg class="w-5 h-5 text-amber-600 dark:text-amber-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-amber-900 dark:text-amber-300 mb-1"><?= htmlspecialchars(__('services.admin_orders.provision_new_temp_title')) ?></p>
                <p class="text-xs text-amber-800 dark:text-amber-400 leading-relaxed"><?= htmlspecialchars(__('services.admin_orders.provision_new_temp_desc')) ?></p>
            </div>
        </div>
        <div class="bg-white/50 dark:bg-zinc-800/50 rounded px-3 py-2 mb-3 text-[11px] font-mono">
            <span class="text-zinc-400"><?= htmlspecialchars(__('services.admin_orders.provision_final_domain')) ?>:</span>
            <span class="text-zinc-800 dark:text-zinc-200 font-bold ml-1"><?= htmlspecialchars($_provisionInfo['domain'] ?? '?') ?></span>
        </div>
        <ol class="text-[11px] text-amber-700 dark:text-amber-400 space-y-1 list-decimal pl-5 mb-3">
            <li><?= htmlspecialchars(__('services.admin_orders.provision_step1', ['domain' => $_provisionInfo['domain'] ?? '?'])) ?></li>
            <li><?= htmlspecialchars(__('services.admin_orders.provision_step2')) ?></li>
            <li><?= htmlspecialchars(__('services.admin_orders.provision_step3')) ?></li>
        </ol>
        <button type="button" onclick="completeNewDomainAcquisition(<?= (int)$order['id'] ?>)"
                class="px-4 py-2 text-xs font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700 transition">
            <?= htmlspecialchars(__('services.admin_orders.btn_complete_acquisition')) ?>
        </button>
    </div>

    <?php elseif ($_provisionMode === 'existing_pending'): ?>
    <!-- 보유 도메인 — Cloudflare zone 추가 + NS 변경 대기 -->
    <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/50 rounded-lg px-4 py-3">
        <div class="flex items-start gap-3 mb-3">
            <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 16h-1v-4h-1m1-4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
            <div class="flex-1 min-w-0">
                <p class="text-xs font-bold text-blue-900 dark:text-blue-300 mb-1"><?= htmlspecialchars(__('services.admin_orders.provision_existing_pending_title')) ?></p>
                <p class="text-xs text-blue-800 dark:text-blue-400 leading-relaxed"><?= htmlspecialchars(__('services.admin_orders.provision_existing_pending_desc')) ?></p>
            </div>
        </div>
        <?php if (!empty($_nameServers)): ?>
        <div class="bg-white dark:bg-zinc-800 rounded px-3 py-2 mb-3">
            <p class="text-[11px] font-bold text-zinc-700 dark:text-zinc-200 mb-1"><?= htmlspecialchars(__('services.admin_orders.provision_ns_label')) ?></p>
            <ul class="text-xs font-mono space-y-0.5">
                <?php foreach ($_nameServers as $_ns): ?>
                <li class="text-zinc-800 dark:text-zinc-200">• <?= htmlspecialchars($_ns) ?></li>
                <?php endforeach; ?>
            </ul>
        </div>
        <?php endif; ?>
        <button type="button" onclick="checkExistingDomainActivation(<?= (int)$order['id'] ?>)"
                class="px-4 py-2 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">
            <?= htmlspecialchars(__('services.admin_orders.btn_check_ns_active')) ?>
        </button>
    </div>

    <?php elseif ($_provisionMode === 'active'): ?>
    <!-- 셋업 완료 — 모든 케이스 (free/new/existing) 통합 -->
    <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/50 rounded-lg px-4 py-3 flex items-start gap-3">
        <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <div class="flex-1 min-w-0">
            <p class="text-xs font-bold text-emerald-900 dark:text-emerald-300 mb-0.5"><?= htmlspecialchars(__('services.admin_orders.provision_active_title')) ?></p>
            <p class="text-xs text-emerald-700 dark:text-emerald-400 font-mono"><?= htmlspecialchars($order['domain'] ?? '') ?></p>
        </div>
    </div>

    <?php else: ?>
    <!-- pending 또는 알 수 없는 상태 -->
    <div class="bg-zinc-50 dark:bg-zinc-700/30 border border-zinc-200 dark:border-zinc-700 rounded-lg px-4 py-3 flex items-start gap-3">
        <svg class="w-5 h-5 text-zinc-500 flex-shrink-0 mt-0.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
        <p class="text-xs text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars(__('services.admin_orders.provision_pending_generic', ['mode' => $_provisionMode ?? 'pending'])) ?></p>
    </div>
    <?php endif; ?>
</div>

<script>
function completeNewDomainAcquisition(orderId) {
    if (!confirm(<?= json_encode(__('services.admin_orders.confirm_complete_acquisition'), JSON_UNESCAPED_UNICODE) ?>)) return;
    fetch(<?= json_encode($baseUrl . '/plugins/vos-hosting/api/service-manage.php') ?>, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ action: 'admin_migrate_new_domain', order_id: orderId })
    }).then(r => r.json()).then(d => {
        if (d.success) { alert(<?= json_encode(__('services.admin_orders.alert_acquisition_done'), JSON_UNESCAPED_UNICODE) ?>); location.reload(); }
        else alert(d.message || 'Failed');
    });
}
function checkExistingDomainActivation(orderId) {
    fetch(<?= json_encode($baseUrl . '/plugins/vos-hosting/api/service-manage.php') ?>, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ action: 'admin_activate_existing_domain', order_id: orderId })
    }).then(r => r.json()).then(d => {
        if (d.success) { alert(<?= json_encode(__('services.admin_orders.alert_ns_activated'), JSON_UNESCAPED_UNICODE) ?>); location.reload(); }
        else alert(d.message || <?= json_encode(__('services.admin_orders.alert_ns_pending'), JSON_UNESCAPED_UNICODE) ?>);
    });
}
</script>
<?php endif; /* mail_provision 정보 있음 */ ?>

<!-- 도메인 연결 정보 -->
<div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700">
    <div class="flex items-center justify-between mb-3">
        <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider"><?= htmlspecialchars(__('services.admin_orders.domain_connect_section')) ?></p>
        <span class="text-xs text-zinc-400"><?= htmlspecialchars(__('services.admin_orders.total_count', ['count' => count($allDomains)])) ?></span>
    </div>
    <div class="space-y-2">
        <?php foreach ($allDomains as $dm): ?>
        <div class="flex items-center justify-between px-3 py-2 <?= $dm['is_primary'] ? 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800' : 'bg-gray-50 dark:bg-zinc-700/30' ?> rounded-lg text-xs">
            <div class="flex items-center gap-3">
                <?php if ($dm['is_primary']): ?>
                <span class="text-[10px] px-1.5 py-0.5 bg-blue-600 text-white rounded font-medium"><?= htmlspecialchars(__('services.admin_orders.b_primary')) ?></span>
                <?php endif; ?>
                <span class="font-mono font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($dm['name']) ?></span>
                <?php if (!empty($dm['is_added'])): ?>
                <span class="text-[10px] text-violet-600 dark:text-violet-400"><?= htmlspecialchars(__('services.detail.dom_added')) ?></span>
                <?php endif; ?>
                <?php if ($dm['is_free']): ?>
                <span class="text-[10px] text-green-600"><?= htmlspecialchars(__('services.admin_orders.b_free_short')) ?></span>
                <?php elseif ($dm['is_existing']): ?>
                <span class="text-[10px] text-zinc-400"><?= htmlspecialchars(__('services.admin_orders.b_owned_short')) ?></span>
                <?php elseif (!empty($dm['expires_at'])): ?>
                <span class="text-[10px] text-zinc-400">~<?= date('Y-m-d', strtotime($dm['expires_at'])) ?></span>
                <?php endif; ?>
                <?php if (!empty($dm['registrar_pending'])): ?>
                <span class="text-[10px] text-amber-600"><?= htmlspecialchars(__('services.detail.dom_registrar_pending')) ?></span>
                <?php endif; ?>
                <?php if (!empty($dm['manual_attach_pending'])): ?>
                <span class="text-[10px] text-amber-600"><?= htmlspecialchars(__('services.detail.dom_attach_pending')) ?></span>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-1.5">
                <?php if (!$dm['is_primary']): ?>
                <button disabled class="text-[10px] px-2 py-1 text-zinc-400 border border-zinc-200 dark:border-zinc-600 rounded cursor-not-allowed opacity-50" title="<?= htmlspecialchars($_pendingTitle) ?>"><?= htmlspecialchars(__('services.admin_orders.btn_set_primary')) ?></button>
                <?php endif; ?>
                <button disabled class="text-[10px] px-2 py-1 text-zinc-400 border border-zinc-200 dark:border-zinc-600 rounded cursor-not-allowed opacity-50" title="<?= htmlspecialchars($_pendingTitle) ?>"><?= htmlspecialchars(__('services.admin_orders.btn_dns_setting')) ?></button>
                <button disabled class="text-[10px] px-2 py-1 text-zinc-400 border border-zinc-200 dark:border-zinc-600 rounded cursor-not-allowed opacity-50" title="<?= htmlspecialchars($_pendingTitle) ?>"><?= htmlspecialchars(__('services.admin_orders.btn_check_domain')) ?></button>
                <button type="button" onclick="adminMergeDomainSub(<?= (int)$dm['sub_id'] ?>, '<?= htmlspecialchars($dm['name']) ?>')" class="text-[10px] px-2 py-1 text-amber-700 dark:text-amber-300 border border-amber-300 dark:border-amber-700 rounded hover:bg-amber-50 dark:hover:bg-amber-900/20" title="<?= htmlspecialchars(__('services.admin_orders.btn_merge_to_host_title')) ?>"><?= htmlspecialchars(__('services.admin_orders.btn_merge_to_host')) ?></button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<script>
function adminMergeDomainSub(domainSubId, domainName) {
    var hostId = prompt(<?= json_encode(__('services.admin_orders.merge_prompt'), JSON_UNESCAPED_UNICODE) ?>.replace(':domain', domainName));
    if (!hostId) return;
    hostId = parseInt(hostId, 10);
    if (!hostId) { alert(<?= json_encode(__('services.admin_orders.merge_invalid_id'), JSON_UNESCAPED_UNICODE) ?>); return; }
    if (!confirm(<?= json_encode(__('services.admin_orders.merge_confirm'), JSON_UNESCAPED_UNICODE) ?>.replace(':sub', domainSubId).replace(':host', hostId))) return;
    fetch(<?= json_encode($baseUrl . '/plugins/vos-hosting/api/service-manage.php') ?>, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ action: 'admin_merge_domain_sub', domain_sub_id: domainSubId, target_host_sub_id: hostId })
    }).then(r => r.json()).then(d => {
        if (d.success) {
            alert(<?= json_encode(__('services.admin_orders.merge_done'), JSON_UNESCAPED_UNICODE) ?>.replace(':count', (d.moved_domains || []).length).replace(':host', d.host_sub_id));
            location.reload();
        } else {
            alert(d.message || 'Failed');
        }
    });
}
</script>

<!-- 도메인 연결 추가 -->
<div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700">
    <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-2"><?= htmlspecialchars(__('services.admin_orders.domain_connect')) ?></p>
    <div class="flex items-center gap-2">
        <input type="text" id="newDomainInput" placeholder="example.com" class="flex-1 max-w-xs px-3 py-1.5 text-xs font-mono border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg">
        <button disabled class="px-3 py-1.5 text-xs text-zinc-400 border border-zinc-200 rounded-lg cursor-not-allowed opacity-50" title="<?= htmlspecialchars($_pendingTitle) ?>"><?= htmlspecialchars(__('services.admin_orders.btn_connect')) ?></button>
    </div>
    <p class="text-[10px] text-zinc-400 mt-1.5"><?= htmlspecialchars(__('services.admin_orders.domain_input_hint')) ?></p>
</div>

<!-- 네임서버 정보 -->
<div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700">
    <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-2"><?= htmlspecialchars(__('services.detail.ns_info')) ?></p>
    <p class="text-[10px] text-zinc-400 mb-3"><?= htmlspecialchars(__('services.detail.ns_desc')) ?></p>
    <table class="w-full text-xs">
        <thead>
            <tr class="text-[10px] text-zinc-400 border-b border-gray-200 dark:border-zinc-700">
                <th class="py-1.5 text-left w-16"><?= htmlspecialchars(__('services.detail.ns_col_index')) ?></th>
                <th class="py-1.5 text-left"><?= htmlspecialchars(__('services.detail.ns_col_hostname')) ?></th>
                <th class="py-1.5 text-left">IP</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-zinc-700/50">
            <?php foreach ($nameservers as $i => $ns): ?>
            <tr>
                <td class="py-1.5 text-zinc-400"><?= ($i + 1) ?><?= htmlspecialchars(__('services.detail.ns_index_suffix')) ?></td>
                <td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($ns['host'] ?? '-') ?></td>
                <td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($ns['ip'] ?? '-') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- 관리 버튼 -->
<div class="px-5 py-4 flex flex-wrap gap-2">
    <button disabled class="px-3 py-1.5 text-xs text-zinc-400 border border-zinc-200 rounded-lg cursor-not-allowed opacity-50" title="<?= htmlspecialchars($_pendingTitle) ?>"><?= htmlspecialchars(__('services.admin_orders.btn_buy_domain')) ?></button>
    <button disabled class="px-3 py-1.5 text-xs text-zinc-400 border border-zinc-200 rounded-lg cursor-not-allowed opacity-50" title="<?= htmlspecialchars($_pendingTitle) ?>"><?= htmlspecialchars(__('services.admin_orders.btn_transfer_domain')) ?></button>
</div>
