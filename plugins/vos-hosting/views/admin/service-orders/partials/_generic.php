<?php
/**
 * 관리자 서비스 상세 — 제네릭 파셜
 * $subs, $statusLabels, $fmtPrice, $oneTimeStatusOptions 사용
 */
?>
<div class="divide-y divide-gray-100 dark:divide-zinc-700/50">
    <?php foreach ($subs as $sub):
        $sst = $statusLabels[$sub['status']] ?? [__('services.mypage.status_unknown'), 'bg-gray-100 text-gray-500'];
        $sc = $sub['service_class'] ?? 'recurring';
        $meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
        $isOneTime = $sc === 'one_time';
        $currentOneTimeStatus = !empty($sub['completed_at']) ? 'completed' : $sub['status'];
    ?>
    <div class="px-5 py-4" id="sub_<?= $sub['id'] ?>">
        <div class="flex items-center justify-between gap-3">
            <div class="flex items-center gap-2 flex-1 min-w-0">
                <p class="text-sm font-semibold text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($_localizeLabel($sub)) ?></p>
                <span class="text-[10px] px-1.5 py-0.5 rounded-full font-medium <?= $sst[1] ?>"><?= htmlspecialchars($sst[0]) ?></span>
                <?php if ($isOneTime): ?>
                <span class="text-[10px] px-1.5 py-0.5 bg-amber-50 text-amber-600 rounded-full"><?= htmlspecialchars(__('services.detail.b_one_time')) ?></span>
                <?php elseif ($sc === 'free'): ?>
                <span class="text-[10px] px-1.5 py-0.5 bg-green-50 text-green-600 rounded-full"><?= htmlspecialchars(__('services.order.summary.free')) ?></span>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-2 shrink-0">
                <?php if ($isOneTime): ?>
                <?php
                    $otColors = ['pending'=>'blue','active'=>'amber','suspended'=>'zinc','cancelled'=>'red','completed'=>'green'];
                    $otLabels = [
                        'pending' => __('services.detail.ot_pending'),
                        'active' => __('services.detail.ot_active'),
                        'suspended' => __('services.detail.ot_suspended'),
                        'cancelled' => __('services.detail.ot_cancelled'),
                        'completed' => __('services.detail.ot_completed'),
                    ];
                    $otColor = $otColors[$currentOneTimeStatus] ?? 'zinc';
                    $otLabel = $otLabels[$currentOneTimeStatus] ?? __('services.mypage.status_unknown');
                ?>
                <button onclick="openStatusModal(<?= $sub['id'] ?>, '<?= $currentOneTimeStatus ?>', '<?= htmlspecialchars($sub['label']) ?>')"
                        class="text-xs px-2.5 py-1 bg-<?= $otColor ?>-50 text-<?= $otColor ?>-600 dark:bg-<?= $otColor ?>-900/20 dark:text-<?= $otColor ?>-400 rounded-lg hover:ring-2 hover:ring-<?= $otColor ?>-300 transition cursor-pointer"><?= htmlspecialchars($otLabel) ?></button>
                <?php else: ?>
                <span class="text-xs text-zinc-400"><?= (int)$sub['billing_amount'] > 0 ? $fmtPrice($sub['billing_amount'], $sub['currency']) : __('services.order.summary.free') ?></span>
                <?php if ($sub['auto_renew']): ?>
                <span class="text-[10px] px-1.5 py-0.5 bg-blue-50 text-blue-600 rounded-full"><?= htmlspecialchars(__('services.detail.auto_renew')) ?></span>
                <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
        <?php if (!empty($meta['domains'])): ?>
        <div class="flex flex-wrap gap-1 mt-1.5">
            <?php foreach ($meta['domains'] as $dm): ?>
            <span class="text-xs px-2 py-0.5 bg-blue-50 dark:bg-blue-900/20 text-blue-600 rounded font-mono"><?= htmlspecialchars($dm) ?></span>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <?php if (!empty($meta['mail_accounts']) && $sub['type'] !== 'hosting'): ?>
        <div class="mt-2 space-y-1">
            <?php foreach ($meta['mail_accounts'] as $mi => $ma): ?>
            <div class="flex items-center gap-3 px-3 py-1.5 bg-gray-50 dark:bg-zinc-700/30 rounded text-xs">
                <span class="text-zinc-400 w-4"><?= $mi + 1 ?></span>
                <span class="font-mono font-medium text-zinc-800 dark:text-zinc-200 flex-1"><?= htmlspecialchars($ma['address'] ?? '') ?></span>
                <span class="text-zinc-400"><?= !empty($ma['password']) ? htmlspecialchars(__('services.admin_orders.mail_pw_set')) : htmlspecialchars(__('services.admin_orders.mail_pw_unset')) ?></span>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>
        <div class="flex items-center gap-3 mt-1.5 text-[10px] text-zinc-400">
            <?php if (!$isOneTime): ?>
            <span><?= date('Y-m-d', strtotime($sub['started_at'])) ?> ~ <?= date('Y-m-d', strtotime($sub['expires_at'])) ?></span>
            <?php endif; ?>
            <?php if (!empty($sub['completed_at'])): ?>
            <span class="text-green-600"><?= htmlspecialchars(__('services.detail.f_completed')) ?>: <?= date('Y-m-d H:i', strtotime($sub['completed_at'])) ?></span>
            <?php endif; ?>
        </div>
    </div>
    <?php endforeach; ?>
</div>
