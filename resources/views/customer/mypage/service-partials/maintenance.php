<?php
/**
 * 서비스 관리 파셜 — 유지보수
 * $subs: maintenance 타입 구독 배열
 */
$sub = $subs[0];
$st = $statusLabels[$sub['status']] ?? ['알 수 없음', 'bg-gray-100 text-gray-500'];
$sc = $sub['service_class'] ?? 'recurring';
?>

<div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
    <div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700 flex items-center justify-between">
        <div class="flex items-center gap-2">
            <h3 class="text-sm font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($sub['label']) ?></h3>
            <span class="text-[10px] px-2 py-0.5 rounded-full font-medium <?= $st[1] ?>"><?= $st[0] ?></span>
        </div>
        <?php if ($sc === 'recurring' && $sub['status'] === 'active'): ?>
        <div class="flex items-center gap-2">
            <span class="text-xs text-zinc-400">자동연장</span>
            <label class="relative inline-flex items-center cursor-pointer">
                <input type="checkbox" class="sr-only peer" <?= $sub['auto_renew'] ? 'checked' : '' ?>
                       onchange="serviceAction('toggle_auto_renew',{subscription_id:<?= $sub['id'] ?>,auto_renew:this.checked})">
                <div class="w-9 h-5 bg-zinc-200 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
            </label>
        </div>
        <?php elseif ($sc === 'free' && $sub['status'] === 'active'): ?>
        <button onclick="serviceAction('request_renewal',{subscription_id:<?= $sub['id'] ?>}).then(function(d){alert(d.message||'신청 완료')})"
                class="text-xs px-3 py-1.5 bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400 rounded-lg hover:bg-blue-100 transition">연장 신청</button>
        <?php endif; ?>
    </div>
    <div class="p-5">
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-4">
            <div>
                <p class="text-[10px] text-zinc-400 uppercase tracking-wider mb-0.5">기간</p>
                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= date('Y-m-d', strtotime($sub['started_at'])) ?> ~ <?= date('Y-m-d', strtotime($sub['expires_at'])) ?></p>
            </div>
            <div>
                <p class="text-[10px] text-zinc-400 uppercase tracking-wider mb-0.5">월 요금</p>
                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= (int)$sub['unit_price'] > 0 ? $fmtPrice($sub['unit_price'], $sub['currency']) . '/월' : '무료' ?></p>
            </div>
            <div>
                <p class="text-[10px] text-zinc-400 uppercase tracking-wider mb-0.5">청구 금액</p>
                <p class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= (int)$sub['billing_amount'] > 0 ? $fmtPrice($sub['billing_amount'], $sub['currency']) : '무료' ?></p>
            </div>
        </div>

        <div class="pt-3 mt-4 border-t border-gray-100 dark:border-zinc-700 flex flex-wrap gap-2">
            <button disabled class="px-3 py-1.5 text-xs text-zinc-400 border border-zinc-200 dark:border-zinc-600 rounded-lg cursor-not-allowed opacity-50" title="준비중">플랜 변경</button>
        </div>
    </div>
</div>
