<?php
/**
 * 관리자 서비스 상세 — 부가서비스 탭
 * $subs: addon 타입 구독 배열
 */
?>
<div class="divide-y divide-gray-100 dark:divide-zinc-700/50">
    <?php foreach ($subs as $sub):
        $sst = $statusLabels[$sub['status']] ?? [__('services.mypage.status_unknown'), 'bg-gray-100 text-gray-500'];
        $sc = $sub['service_class'] ?? 'recurring';
        $meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
        $isOneTime = $sc === 'one_time';
        $currentOneTimeStatus = !empty($sub['completed_at']) ? 'completed' : $sub['status'];
        $adminMemo = $meta['admin_memo'] ?? '';
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
        <?php if (!$isOneTime): ?>
        <p class="text-[10px] text-zinc-400 mt-1"><?= date('Y-m-d', strtotime($sub['started_at'])) ?> ~ <?= date('Y-m-d', strtotime($sub['expires_at'])) ?></p>
        <?php endif; ?>
        <?php if (!empty($sub['completed_at'])): ?>
        <p class="text-[10px] text-green-600 mt-1"><?= htmlspecialchars(__('services.detail.f_completed')) ?>: <?= date('Y-m-d H:i', strtotime($sub['completed_at'])) ?></p>
        <?php endif; ?>
        <?php if (!empty($meta['quote_required'])): ?>
        <p class="text-[10px] text-amber-500 mt-1"><?= htmlspecialchars(__('services.detail.quote_required')) ?></p>
        <?php endif; ?>

        <?php if (!empty($meta['install_info'])):
            $info = $meta['install_info'];
        ?>
        <!-- 설치 관리자 정보 -->
        <div class="mt-2 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <p class="text-xs font-semibold text-blue-800 dark:text-blue-200 mb-2"><?= htmlspecialchars(__('services.order.addons.install_admin_label')) ?></p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-1.5 text-xs">
                <?php if (!empty($info['admin_id'])): ?>
                <div class="flex gap-2"><span class="text-zinc-500 dark:text-zinc-400 min-w-[7rem]"><?= htmlspecialchars(__('services.order.addons.install_admin_id')) ?>:</span><span class="font-mono text-zinc-800 dark:text-zinc-100"><?= htmlspecialchars($info['admin_id']) ?></span></div>
                <?php endif; ?>
                <?php if (!empty($info['admin_email'])): ?>
                <div class="flex gap-2"><span class="text-zinc-500 dark:text-zinc-400 min-w-[7rem]"><?= htmlspecialchars(__('services.order.addons.install_admin_email')) ?>:</span><span class="font-mono text-zinc-800 dark:text-zinc-100"><?= htmlspecialchars($info['admin_email']) ?></span></div>
                <?php endif; ?>
                <?php if (!empty($info['admin_pw'])): ?>
                <div class="flex items-center gap-2"><span class="text-zinc-500 dark:text-zinc-400 min-w-[7rem]"><?= htmlspecialchars(__('services.order.addons.install_admin_pw')) ?>:</span><span class="install-pw font-mono text-zinc-800 dark:text-zinc-100 select-all" data-real="<?= htmlspecialchars($info['admin_pw']) ?>">••••••••</span><button type="button" onclick="(function(b){var s=b.previousElementSibling;if(s.textContent==='••••••••'){s.textContent=s.dataset.real;b.textContent='🙈';}else{s.textContent='••••••••';b.textContent='👁';}})(this)" class="text-xs hover:opacity-70" title="show/hide">👁</button></div>
                <?php endif; ?>
                <?php if (!empty($info['site_title'])): ?>
                <div class="flex gap-2"><span class="text-zinc-500 dark:text-zinc-400 min-w-[7rem]"><?= htmlspecialchars(__('services.order.addons.install_site_title')) ?>:</span><span class="text-zinc-800 dark:text-zinc-100"><?= htmlspecialchars($info['site_title']) ?></span></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

    </div>
    <?php endforeach; ?>
</div>
