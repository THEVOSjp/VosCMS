<?php
/**
 * 서비스 관리 파셜 — 도메인
 * $subs: domain 타입 구독 배열
 */
$allDomains = [];
foreach ($subs as $sub) {
    $meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
    foreach ($meta['domains'] ?? [] as $dm) {
        $allDomains[] = [
            'name' => $dm,
            'sub_id' => $sub['id'],
            'status' => $sub['status'],
            'service_class' => $sub['service_class'] ?? 'recurring',
            'expires_at' => $sub['expires_at'],
            'is_free' => !empty($meta['free_subdomain']),
            'is_existing' => !empty($meta['existing']),
            'is_primary' => !empty($meta['primary_domain']) && $meta['primary_domain'] === $dm,
        ];
    }
}
// 프라이머리 도메인이 없으면 첫 번째를 프라이머리로
if (!empty($allDomains) && !array_filter($allDomains, function($d) { return $d['is_primary']; })) {
    $allDomains[0]['is_primary'] = true;
}
$firstSub = $subs[0];
$fst = $statusLabels[$firstSub['status']] ?? ['알 수 없음', 'bg-gray-100 text-gray-500'];
$fsc = $firstSub['service_class'] ?? 'recurring';
?>

<div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <h3 class="text-sm font-bold text-zinc-900 dark:text-white">도메인 관리</h3>
            <span class="text-[10px] px-2 py-0.5 rounded-full font-medium <?= $fst[1] ?>"><?= $fst[0] ?></span>
        </div>
        <?php if ($fsc === 'recurring' && $firstSub['status'] === 'active'): ?>
        <div class="flex items-center gap-2">
            <span class="text-xs text-zinc-400">자동연장</span>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" class="sr-only peer" <?= $firstSub['auto_renew'] ? 'checked' : '' ?>
                       onchange="serviceAction('toggle_auto_renew',{subscription_id:<?= $firstSub['id'] ?>,auto_renew:this.checked})">
                <div class="w-9 h-5 bg-zinc-200 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
            </label>
        </div>
        <?php elseif ($fsc === 'free' && $firstSub['status'] === 'active'): ?>
        <button onclick="serviceAction('request_renewal',{subscription_id:<?= $firstSub['id'] ?>}).then(function(d){alert(d.message||'신청 완료')})"
                class="text-xs px-3 py-1.5 bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400 rounded-lg hover:bg-blue-100 transition">연장 신청</button>
        <?php endif; ?>
    </div>

    <div class="p-5 space-y-3">
        <?php foreach ($allDomains as $dm): ?>
        <div class="flex items-center justify-between p-3 rounded-lg <?= $dm['is_primary'] ? 'bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800' : 'bg-gray-50 dark:bg-zinc-700/30' ?>">
            <div class="flex items-center gap-3">
                <?php if ($dm['is_primary']): ?>
                <span class="text-[10px] px-2 py-0.5 bg-blue-600 text-white rounded-full font-medium">메인</span>
                <?php else: ?>
                <button onclick="setPrimaryDomain(<?= $dm['sub_id'] ?>,'<?= htmlspecialchars($dm['name']) ?>')"
                        class="text-[10px] px-2 py-0.5 border border-zinc-300 dark:border-zinc-500 text-zinc-400 rounded-full hover:border-blue-400 hover:text-blue-500 transition">메인 설정</button>
                <?php endif; ?>
                <div>
                    <p class="text-sm font-mono font-semibold text-zinc-900 dark:text-white"><?= htmlspecialchars($dm['name']) ?></p>
                    <div class="flex items-center gap-2 mt-0.5">
                        <?php if ($dm['is_free']): ?>
                        <span class="text-[10px] text-green-600">무료 서브도메인</span>
                        <?php elseif ($dm['is_existing']): ?>
                        <span class="text-[10px] text-zinc-400">보유 도메인</span>
                        <?php else: ?>
                        <span class="text-[10px] text-zinc-400">~<?= date('Y-m-d', strtotime($dm['expires_at'])) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>

        <!-- 관리 기능 -->
        <div class="pt-3 border-t border-gray-100 dark:border-zinc-700 flex flex-wrap gap-2">
            <button disabled class="px-3 py-1.5 text-xs text-zinc-400 border border-zinc-200 dark:border-zinc-600 rounded-lg cursor-not-allowed opacity-50" title="준비중">도메인 추가</button>
            <button disabled class="px-3 py-1.5 text-xs text-zinc-400 border border-zinc-200 dark:border-zinc-600 rounded-lg cursor-not-allowed opacity-50" title="준비중">도메인 변경</button>
            <button disabled class="px-3 py-1.5 text-xs text-zinc-400 border border-zinc-200 dark:border-zinc-600 rounded-lg cursor-not-allowed opacity-50" title="준비중">DNS 설정</button>
        </div>
    </div>
</div>

<script>
function setPrimaryDomain(subId, domain) {
    serviceAction('set_primary_domain', { subscription_id: subId, domain: domain })
        .then(function(d) { if (d.success) location.reload(); else alert(d.message || '실패'); });
}
</script>
