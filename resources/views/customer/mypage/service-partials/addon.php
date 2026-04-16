<?php
/**
 * 마이페이지 서비스 관리 — 부가서비스 탭
 */
?>
<div class="space-y-3">
    <?php foreach ($subs as $sub):
        $st = $statusLabels[$sub['status']] ?? ['알 수 없음', 'bg-gray-100 text-gray-500'];
        $meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
        $sc = $sub['service_class'] ?? 'recurring';
    ?>
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-5 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <p class="text-sm font-semibold text-zinc-900 dark:text-white"><?= htmlspecialchars($sub['label']) ?></p>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-medium <?= $st[1] ?>"><?= $st[0] ?></span>
                <?php if ($sc === 'one_time'): ?>
                <span class="text-[10px] px-2 py-0.5 bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400 rounded-full">1회성</span>
                <?php elseif ($sc === 'free'): ?>
                <span class="text-[10px] px-2 py-0.5 bg-green-50 text-green-600 dark:bg-green-900/30 dark:text-green-400 rounded-full">무료</span>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-2">
                <?php if ($sc === 'one_time'): ?>
                    <?php
                    $currentOneTimeStatus = !empty($sub['completed_at']) ? 'completed' : $sub['status'];
                    $otColors = ['pending'=>'blue','active'=>'amber','suspended'=>'zinc','cancelled'=>'red','completed'=>'green'];
                    $otLabels = ['pending'=>'접수','active'=>'진행','suspended'=>'보류','cancelled'=>'취소','completed'=>'완료'];
                    $otColor = $otColors[$currentOneTimeStatus] ?? 'zinc';
                    $otLabel = $otLabels[$currentOneTimeStatus] ?? '알 수 없음';
                    ?>
                    <span class="text-xs px-2.5 py-1 bg-<?= $otColor ?>-50 text-<?= $otColor ?>-600 dark:bg-<?= $otColor ?>-900/20 dark:text-<?= $otColor ?>-400 rounded-lg"><?= $otLabel ?></span>
                <?php elseif ($sc === 'recurring' && $sub['status'] === 'active'): ?>
                    <span class="text-xs text-zinc-400">자동연장</span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer" <?= $sub['auto_renew'] ? 'checked' : '' ?>
                               onchange="serviceAction('toggle_auto_renew',{subscription_id:<?= $sub['id'] ?>,auto_renew:this.checked})">
                        <div class="w-9 h-5 bg-zinc-200 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                <?php elseif ($sc === 'free' && $sub['status'] === 'active'): ?>
                    <button onclick="serviceAction('request_renewal',{subscription_id:<?= $sub['id'] ?>}).then(function(d){alert(d.message||'신청 완료')})"
                            class="text-xs px-3 py-1.5 bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400 rounded-lg hover:bg-blue-100 transition">연장 신청</button>
                <?php endif; ?>
            </div>
        </div>
        <div class="px-5 pb-3 flex items-center gap-3 text-xs text-zinc-400">
            <?php if ($sc !== 'one_time'): ?>
            <span><?= date('Y-m-d', strtotime($sub['started_at'])) ?> ~ <?= date('Y-m-d', strtotime($sub['expires_at'])) ?></span>
            <?php endif; ?>
            <span><?= (int)$sub['billing_amount'] > 0 ? $fmtPrice($sub['billing_amount'], $sub['currency']) : '무료' ?></span>
            <?php if (!empty($meta['quote_required'])): ?>
            <span class="text-amber-500">별도 견적</span>
            <?php endif; ?>
            <?php if (!empty($sub['completed_at'])): ?>
            <span class="text-green-600">완료: <?= date('Y-m-d', strtotime($sub['completed_at'])) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
