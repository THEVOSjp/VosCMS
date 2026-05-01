<?php
/**
 * 마이페이지 서비스 관리 — 도메인 탭
 */
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
            'started_at' => $sub['started_at'],
            'expires_at' => $sub['expires_at'],
            'auto_renew' => !empty($sub['auto_renew']),
            'service_class' => $sub['service_class'] ?? 'recurring',
            'status' => $sub['status'],
            'is_added' => false,
            'registrar_pending' => false,
            'manual_attach_pending' => false,
        ];
    }
}
// 호스팅 sub의 added_domains (마이페이지 모달로 추가된 도메인)
foreach ($servicesByType['hosting'] ?? [] as $hs) {
    $hMeta = json_decode($hs['metadata'] ?? '{}', true) ?: [];
    foreach ($hMeta['added_domains'] ?? [] as $ad) {
        if (empty($ad['domain'])) continue;
        $allDomains[] = [
            'name' => $ad['domain'],
            'sub_id' => $hs['id'],
            'is_free' => ($ad['option'] ?? '') === 'free',
            'is_existing' => ($ad['option'] ?? '') === 'existing',
            'is_primary' => false,
            'started_at' => $ad['started_at'] ?? '',
            'expires_at' => $ad['expires_at'] ?? '',
            'auto_renew' => !empty($ad['auto_renew']),
            'service_class' => 'recurring',
            'status' => 'active',
            'is_added' => true,
            'registrar_pending' => !empty($ad['registrar_pending']),
            'manual_attach_pending' => !empty($ad['manual_attach_pending']),
        ];
    }
}
if (!empty($allDomains) && !array_filter($allDomains, fn($d) => $d['is_primary'])) {
    $allDomains[0]['is_primary'] = true;
}

$firstSub = $subs[0];
$firstMeta = json_decode($firstSub['metadata'] ?? '{}', true) ?: [];
$nameservers = $firstMeta['nameservers'] ?? [];
?>

<div class="space-y-4">
    <!-- 도메인 목록 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700 flex items-center justify-between gap-3 flex-wrap">
            <h3 class="text-sm font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars(__('services.detail.domain_mgmt')) ?></h3>
            <button type="button" onclick="openDomainAddModal()"
                    class="inline-flex items-center gap-1 px-3 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition">
                + <?= htmlspecialchars(__('services.detail.btn_add_domain')) ?>
            </button>
        </div>
        <div class="p-5 space-y-2">
            <?php foreach ($allDomains as $dm):
                $isActive = $dm['status'] === 'active';
                $isRecurring = $dm['service_class'] === 'recurring';
                $isFree = $dm['service_class'] === 'free';
            ?>
            <div class="p-3 rounded-lg <?= $dm['is_primary'] ? 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800' : 'bg-gray-50 dark:bg-zinc-700/30' ?>">
                <div class="flex items-center justify-between gap-3 flex-wrap">
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <?php if ($dm['is_primary']): ?>
                        <span class="text-[10px] px-2 py-0.5 bg-blue-600 text-white rounded-full font-medium whitespace-nowrap"><?= htmlspecialchars(__('services.detail.b_main')) ?></span>
                        <?php else: ?>
                        <button type="button" onclick="setPrimaryDomain(<?= (int)$dm['sub_id'] ?>,'<?= htmlspecialchars($dm['name']) ?>')"
                                class="text-[10px] px-2 py-0.5 border border-zinc-300 dark:border-zinc-500 text-zinc-400 rounded-full hover:border-blue-400 hover:text-blue-500 transition whitespace-nowrap"><?= htmlspecialchars(__('services.detail.btn_set_main')) ?></button>
                        <?php endif; ?>
                        <div class="min-w-0">
                            <p class="text-sm font-mono font-medium text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($dm['name']) ?></p>
                            <p class="text-[10px] text-zinc-400">
                                <?= $dm['started_at'] ? date('Y-m-d', strtotime($dm['started_at'])) : '-' ?> ~ <?= $dm['expires_at'] ? date('Y-m-d', strtotime($dm['expires_at'])) : '-' ?>
                                <?php if (!empty($dm['is_added'])): ?> · <span class="text-violet-600 dark:text-violet-400"><?= htmlspecialchars(__('services.detail.dom_added')) ?></span><?php endif; ?>
                                <?php if ($dm['is_free']): ?> · <span class="text-green-600 dark:text-green-400"><?= htmlspecialchars(__('services.detail.dom_free_sub')) ?></span><?php endif; ?>
                                <?php if ($dm['is_existing']): ?> · <span><?= htmlspecialchars(__('services.detail.dom_existing')) ?></span><?php endif; ?>
                                <?php if (!empty($dm['registrar_pending'])): ?> · <span class="text-amber-600"><?= htmlspecialchars(__('services.detail.dom_registrar_pending')) ?></span><?php endif; ?>
                                <?php if (!empty($dm['manual_attach_pending'])): ?> · <span class="text-amber-600"><?= htmlspecialchars(__('services.detail.dom_attach_pending')) ?></span><?php endif; ?>
                            </p>
                        </div>
                    </div>
                    <div class="flex items-center gap-2 flex-wrap">
                        <?php if (!empty($dm['registrar_pending']) || !empty($dm['manual_attach_pending'])): ?>
                        <span class="text-[11px] px-3 py-1.5 bg-amber-50 dark:bg-amber-900/20 text-amber-700 dark:text-amber-400 border border-amber-200 dark:border-amber-800 rounded-lg"><?= htmlspecialchars(__('services.detail.dom_preparing')) ?></span>
                        <?php else: ?>
                        <button type="button" onclick="checkDomainDns('<?= htmlspecialchars($dm['name']) ?>', this)"
                                class="text-[11px] px-3 py-1.5 bg-zinc-100 hover:bg-zinc-200 dark:bg-zinc-700 dark:hover:bg-zinc-600 text-zinc-700 dark:text-zinc-200 rounded-lg transition">
                            <?= htmlspecialchars(__('services.detail.btn_dns_check')) ?>
                        </button>
                        <?php endif; ?>
                        <?php if ($isRecurring && $isActive): ?>
                            <span class="text-[11px] text-zinc-400"><?= htmlspecialchars(__('services.detail.auto_renew')) ?></span>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" class="sr-only peer" <?= $dm['auto_renew'] ? 'checked' : '' ?>
                                       onchange="serviceAction('toggle_auto_renew',{subscription_id:<?= (int)$dm['sub_id'] ?>,auto_renew:this.checked})">
                                <div class="w-9 h-5 bg-zinc-200 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                            </label>
                            <?php if (!$dm['auto_renew']): ?>
                            <button type="button" onclick="serviceAction('request_renewal',{subscription_id:<?= (int)$dm['sub_id'] ?>}).then(function(d){alert(d.message||<?= json_encode(__('services.detail.alert_request_done'), JSON_UNESCAPED_UNICODE) ?>)})"
                                    class="text-[11px] px-3 py-1.5 bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400 rounded-lg hover:bg-blue-100 transition"><?= htmlspecialchars(__('services.detail.btn_renewal')) ?></button>
                            <?php endif; ?>
                        <?php elseif ($isFree && $isActive): ?>
                            <button type="button" onclick="serviceAction('request_renewal',{subscription_id:<?= (int)$dm['sub_id'] ?>}).then(function(d){alert(d.message||<?= json_encode(__('services.detail.alert_request_done'), JSON_UNESCAPED_UNICODE) ?>)})"
                                    class="text-[11px] px-3 py-1.5 bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400 rounded-lg hover:bg-blue-100 transition"><?= htmlspecialchars(__('services.detail.btn_renewal')) ?></button>
                        <?php endif; ?>
                    </div>
                </div>
                <div class="dns-check-result text-[11px] mt-2 hidden"></div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 네임서버 정보 (관리자 설정 시에만 표시) -->
    <?php if (!empty($nameservers)): ?>
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 p-5">
        <p class="text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-2"><?= htmlspecialchars(__('services.detail.ns_info')) ?></p>
        <p class="text-[10px] text-zinc-400 mb-3"><?= htmlspecialchars(__('services.detail.ns_desc')) ?></p>
        <table class="w-full text-xs">
            <thead>
                <tr class="text-[10px] text-zinc-400 border-b border-gray-200 dark:border-zinc-700">
                    <th class="py-1.5 text-left w-12"><?= htmlspecialchars(__('services.detail.ns_col_index')) ?></th>
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
    <?php endif; ?>
</div>

<script>
function setPrimaryDomain(subId, domain) {
    serviceAction('set_primary_domain', { subscription_id: subId, domain: domain })
        .then(function(d) { if (d.success) location.reload(); else alert(d.message || <?= json_encode(__('services.detail.alert_failed'), JSON_UNESCAPED_UNICODE) ?>); });
}

function checkDomainDns(domain, btn) {
    var orig = btn.textContent;
    btn.disabled = true;
    btn.textContent = '...';
    var resultEl = btn.closest('.p-3').querySelector('.dns-check-result');
    if (resultEl) { resultEl.classList.add('hidden'); resultEl.textContent = ''; }
    serviceAction('check_domain_dns', { domain: domain })
        .then(function(d) {
            if (!d.success) {
                if (resultEl) {
                    resultEl.textContent = '✗ ' + (d.message || <?= json_encode(__('services.detail.alert_failed'), JSON_UNESCAPED_UNICODE) ?>);
                    resultEl.className = 'dns-check-result text-[11px] mt-2 text-red-600 dark:text-red-400';
                }
                return;
            }
            if (resultEl) {
                if (d.points_to_us) {
                    resultEl.textContent = '✓ ' + <?= json_encode(__('services.detail.alert_dns_ok'), JSON_UNESCAPED_UNICODE) ?> + ' (' + (d.matched || '') + ')';
                    resultEl.className = 'dns-check-result text-[11px] mt-2 text-emerald-600 dark:text-emerald-400';
                } else {
                    var detail = (d.records && d.records.length) ? ' [' + d.records.join(', ') + ']' : '';
                    resultEl.textContent = '✗ ' + <?= json_encode(__('services.detail.alert_dns_fail'), JSON_UNESCAPED_UNICODE) ?> + detail;
                    resultEl.className = 'dns-check-result text-[11px] mt-2 text-red-600 dark:text-red-400';
                }
            }
        })
        .catch(function(e) {
            if (resultEl) {
                resultEl.textContent = '✗ ' + (e.message || 'error');
                resultEl.className = 'dns-check-result text-[11px] mt-2 text-red-600 dark:text-red-400';
            }
        })
        .finally(function() { btn.disabled = false; btn.textContent = orig; });
}
</script>

<?php include __DIR__ . '/_domain-add-modal.php'; ?>
