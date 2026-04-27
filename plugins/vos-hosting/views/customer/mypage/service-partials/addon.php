<?php
/**
 * 마이페이지 서비스 관리 — 부가서비스 탭
 */
?>
<div class="space-y-3">
    <?php foreach ($subs as $sub):
        $st = $statusLabels[$sub['status']] ?? [__('services.mypage.status_unknown'), 'bg-gray-100 text-gray-500'];
        $meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
        $sc = $sub['service_class'] ?? 'recurring';
    ?>
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-5 py-3 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <p class="text-sm font-semibold text-zinc-900 dark:text-white"><?= htmlspecialchars($_localizeLabel($sub)) ?></p>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-medium <?= $st[1] ?>"><?= $st[0] ?></span>
                <?php if ($sc === 'one_time'): ?>
                <span class="text-[10px] px-2 py-0.5 bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400 rounded-full"><?= htmlspecialchars(__('services.detail.b_one_time')) ?></span>
                <?php elseif ($sc === 'free'): ?>
                <span class="text-[10px] px-2 py-0.5 bg-green-50 text-green-600 dark:bg-green-900/30 dark:text-green-400 rounded-full"><?= htmlspecialchars(__('services.order.summary.free')) ?></span>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-2">
                <?php if ($sc === 'one_time'): ?>
                    <?php
                    $currentOneTimeStatus = !empty($sub['completed_at']) ? 'completed' : $sub['status'];
                    $otColors = ['pending'=>'blue','active'=>'amber','suspended'=>'zinc','cancelled'=>'red','completed'=>'green'];
                    $otLabels = [
                        'pending'   => __('services.detail.ot_pending'),
                        'active'    => __('services.detail.ot_active'),
                        'suspended' => __('services.detail.ot_suspended'),
                        'cancelled' => __('services.detail.ot_cancelled'),
                        'completed' => __('services.detail.ot_completed'),
                    ];
                    $otColor = $otColors[$currentOneTimeStatus] ?? 'zinc';
                    $otLabel = $otLabels[$currentOneTimeStatus] ?? __('services.mypage.status_unknown');
                    ?>
                    <span class="text-xs px-2.5 py-1 bg-<?= $otColor ?>-50 text-<?= $otColor ?>-600 dark:bg-<?= $otColor ?>-900/20 dark:text-<?= $otColor ?>-400 rounded-lg"><?= htmlspecialchars($otLabel) ?></span>
                <?php elseif ($sc === 'recurring' && $sub['status'] === 'active'): ?>
                    <span class="text-xs text-zinc-400"><?= htmlspecialchars(__('services.detail.auto_renew')) ?></span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer" <?= $sub['auto_renew'] ? 'checked' : '' ?>
                               onchange="serviceAction('toggle_auto_renew',{subscription_id:<?= $sub['id'] ?>,auto_renew:this.checked})">
                        <div class="w-9 h-5 bg-zinc-200 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                <?php elseif ($sc === 'free' && $sub['status'] === 'active'): ?>
                    <button onclick="serviceAction('request_renewal',{subscription_id:<?= $sub['id'] ?>}).then(function(d){alert(d.message||<?= json_encode(__('services.detail.alert_request_done'), JSON_UNESCAPED_UNICODE) ?>)})"
                            class="text-xs px-3 py-1.5 bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400 rounded-lg hover:bg-blue-100 transition"><?= htmlspecialchars(__('services.detail.btn_renewal')) ?></button>
                <?php endif; ?>
            </div>
        </div>
        <div class="px-5 pb-3 flex items-center gap-3 text-xs text-zinc-400">
            <?php if ($sc !== 'one_time'): ?>
            <span><?= date('Y-m-d', strtotime($sub['started_at'])) ?> ~ <?= date('Y-m-d', strtotime($sub['expires_at'])) ?></span>
            <?php endif; ?>
            <span><?= (int)$sub['billing_amount'] > 0 ? $fmtPrice($sub['billing_amount'], $sub['currency']) : __('services.order.summary.free') ?></span>
            <?php if (!empty($meta['quote_required'])): ?>
            <span class="text-amber-500"><?= htmlspecialchars(__('services.detail.quote_required')) ?></span>
            <?php endif; ?>
            <?php if (!empty($sub['completed_at'])): ?>
            <span class="text-green-600"><?= htmlspecialchars(__('services.detail.f_completed')) ?>: <?= date('Y-m-d', strtotime($sub['completed_at'])) ?></span>
            <?php endif; ?>
        </div>
        <?php if (!empty($meta['install_info'])):
            $info = $meta['install_info'];
        ?>
        <div class="mx-5 mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
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
