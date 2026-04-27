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
            'expires_at' => $sub['expires_at'],
        ];
    }
}
if (!empty($allDomains) && !array_filter($allDomains, fn($d) => $d['is_primary'])) {
    $allDomains[0]['is_primary'] = true;
}
$firstSub = $subs[0];
$fst = $statusLabels[$firstSub['status']] ?? [__('services.mypage.status_unknown'), 'bg-gray-100 text-gray-500'];
$fsc = $firstSub['service_class'] ?? 'recurring';

// 네임서버 정보
$firstMeta = json_decode($firstSub['metadata'] ?? '{}', true) ?: [];
$nameservers = $firstMeta['nameservers'] ?? [];
?>

<div class="space-y-4">
    <!-- 도메인 목록 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars(__('services.detail.domain_mgmt')) ?></h3>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-medium <?= $fst[1] ?>"><?= $fst[0] ?></span>
            </div>
            <?php if ($fsc === 'recurring' && $firstSub['status'] === 'active'): ?>
            <div class="flex items-center gap-2">
                <span class="text-xs text-zinc-400"><?= htmlspecialchars(__('services.detail.auto_renew')) ?></span>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" class="sr-only peer" <?= $firstSub['auto_renew'] ? 'checked' : '' ?>
                           onchange="serviceAction('toggle_auto_renew',{subscription_id:<?= $firstSub['id'] ?>,auto_renew:this.checked})">
                    <div class="w-9 h-5 bg-zinc-200 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                </label>
            </div>
            <?php elseif ($fsc === 'free' && $firstSub['status'] === 'active'): ?>
            <button onclick="serviceAction('request_renewal',{subscription_id:<?= $firstSub['id'] ?>}).then(function(d){alert(d.message||<?= json_encode(__('services.detail.alert_request_done'), JSON_UNESCAPED_UNICODE) ?>)})"
                    class="text-xs px-3 py-1.5 bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400 rounded-lg hover:bg-blue-100 transition"><?= htmlspecialchars(__('services.detail.btn_renewal')) ?></button>
            <?php endif; ?>
        </div>
        <div class="p-5 space-y-2">
            <?php foreach ($allDomains as $dm): ?>
            <div class="flex items-center justify-between p-3 rounded-lg <?= $dm['is_primary'] ? 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800' : 'bg-gray-50 dark:bg-zinc-700/30' ?>">
                <div class="flex items-center gap-3">
                    <?php if ($dm['is_primary']): ?>
                    <span class="text-[10px] px-2 py-0.5 bg-blue-600 text-white rounded-full font-medium"><?= htmlspecialchars(__('services.detail.b_main')) ?></span>
                    <?php else: ?>
                    <button onclick="setPrimaryDomain(<?= $dm['sub_id'] ?>,'<?= htmlspecialchars($dm['name']) ?>')"
                            class="text-[10px] px-2 py-0.5 border border-zinc-300 dark:border-zinc-500 text-zinc-400 rounded-full hover:border-blue-400 hover:text-blue-500 transition"><?= htmlspecialchars(__('services.detail.btn_set_main')) ?></button>
                    <?php endif; ?>
                    <div>
                        <p class="text-sm font-mono font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($dm['name']) ?></p>
                        <p class="text-[10px] <?= $dm['is_free'] ? 'text-green-600' : ($dm['is_existing'] ? 'text-zinc-400' : 'text-zinc-400') ?>">
                            <?= $dm['is_free'] ? htmlspecialchars(__('services.detail.dom_free_sub')) : ($dm['is_existing'] ? htmlspecialchars(__('services.detail.dom_existing')) : '~' . date('Y-m-d', strtotime($dm['expires_at']))) ?>
                        </p>
                    </div>
                </div>
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
</script>
