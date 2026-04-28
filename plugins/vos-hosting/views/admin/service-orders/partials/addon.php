<?php
/**
 * 관리자 서비스 상세 — 부가서비스 탭
 * $subs: addon 타입 구독 배열
 * $order, $servicesByType, $pdo, $prefix 사용
 */

// 호스팅 구독 ID (용량 추가 시 부모로 연결)
$_hostSub = $servicesByType['hosting'][0] ?? null;
$_hostSubId = $_hostSub['id'] ?? 0;

// 추가 용량 옵션 (rzx_settings 의 service_hosting_storage)
$_storageOptions = [];
try {
    $_stSt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` = 'service_hosting_storage' LIMIT 1");
    $_stSt->execute();
    $_storageOptions = json_decode($_stSt->fetchColumn() ?: '[]', true) ?: [];
} catch (\Throwable $e) { /* silent */ }
?>

<!-- 헤더: 용량 추가 버튼 -->
<div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700 flex items-center justify-between">
    <p class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider"><?= htmlspecialchars(__('services.mypage.type_addon')) ?></p>
    <?php if ($_hostSubId && !empty($_storageOptions)): ?>
    <button type="button" onclick="openAdminAddonAddModal()"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition">
        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
        <?= htmlspecialchars(__('services.admin_orders.btn_add_storage_addon')) ?>
    </button>
    <?php endif; ?>
</div>

<?php if (empty($subs)): ?>
<div class="px-5 py-12 text-center text-sm text-zinc-400"><?= htmlspecialchars(__('services.admin_orders.empty_addon')) ?></div>
<?php else: ?>

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
                <?php if ($sub['status'] !== 'cancelled'): ?>
                <button type="button" onclick="adminDeleteAddon(<?= (int)$sub['id'] ?>, '<?= htmlspecialchars($_localizeLabel($sub), ENT_QUOTES) ?>')"
                        class="ml-1 p-1 text-red-500 hover:text-red-700 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition" title="<?= htmlspecialchars(__('services.admin_orders.btn_delete_addon')) ?>">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M9 7V4a1 1 0 011-1h4a1 1 0 011 1v3"/></svg>
                </button>
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

<?php endif; /* end empty subs */ ?>

<?php if ($_hostSubId && !empty($_storageOptions)): ?>
<!-- 용량 추가 모달 -->
<div id="adminAddonAddModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl max-w-md w-full p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars(__('services.admin_orders.modal_add_storage_title')) ?></h3>
            <button type="button" onclick="closeAdminAddonAddModal()" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3 leading-relaxed">
            <?= htmlspecialchars(__('services.admin_orders.modal_add_storage_desc')) ?>
        </p>
        <div class="space-y-2 mb-4">
            <?php foreach ($_storageOptions as $_so):
                $_cap = $_so['capacity'] ?? '?';
                $_unitPrice = (int)($_so['price'] ?? 0);
            ?>
            <button type="button"
                    onclick="adminAddStorageAddon('<?= htmlspecialchars($_cap, ENT_QUOTES) ?>', <?= $_unitPrice ?>)"
                    class="w-full flex items-center justify-between px-4 py-3 border border-zinc-200 dark:border-zinc-700 rounded-lg hover:border-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition text-left">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                        <svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    </span>
                    <div>
                        <p class="text-base font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($_cap) ?></p>
                        <p class="text-[10px] text-zinc-400"><?= htmlspecialchars(__('services.admin_orders.modal_add_storage_ref_price', ['price' => number_format($_unitPrice)])) ?></p>
                    </div>
                </div>
                <span class="text-[10px] font-medium text-violet-500"><?= htmlspecialchars(__('services.admin_orders.modal_add_storage_admin_grant')) ?></span>
            </button>
            <?php endforeach; ?>
        </div>
        <p class="text-[11px] text-amber-600 italic"><?= htmlspecialchars(__('services.admin_orders.modal_add_storage_warning')) ?></p>
    </div>
</div>

<script>
function openAdminAddonAddModal() {
    var m = document.getElementById('adminAddonAddModal');
    m.classList.remove('hidden'); m.classList.add('flex');
    document.body.style.overflow = 'hidden';
}
function closeAdminAddonAddModal() {
    var m = document.getElementById('adminAddonAddModal');
    m.classList.add('hidden'); m.classList.remove('flex');
    document.body.style.overflow = '';
}
function adminAddStorageAddon(capacity, unitPrice) {
    var msg = <?= json_encode(__('services.admin_orders.confirm_admin_add_storage'), JSON_UNESCAPED_UNICODE) ?>.replace(':capacity', capacity);
    if (!confirm(msg)) return;
    ajaxPost({ action: 'admin_add_storage_addon', subscription_id: <?= (int)$_hostSubId ?>, capacity: capacity, unit_price: unitPrice })
        .then(function(d) {
            if (d.success) { alert(d.message || <?= json_encode(__('services.admin_orders.alert_addon_added'), JSON_UNESCAPED_UNICODE) ?>); location.reload(); }
            else { alert(d.message || <?= json_encode(__('services.detail.alert_failed'), JSON_UNESCAPED_UNICODE) ?>); }
        }).catch(function(e) { alert(e.message); });
}
function adminDeleteAddon(subId, label) {
    var msg = <?= json_encode(__('services.admin_orders.confirm_delete_addon'), JSON_UNESCAPED_UNICODE) ?>.replace(':label', label);
    if (!confirm(msg)) return;
    ajaxPost({ action: 'admin_delete_addon', subscription_id: subId })
        .then(function(d) {
            if (d.success) { alert(d.message || <?= json_encode(__('services.admin_orders.alert_addon_deleted'), JSON_UNESCAPED_UNICODE) ?>); location.reload(); }
            else { alert(d.message || <?= json_encode(__('services.detail.alert_failed'), JSON_UNESCAPED_UNICODE) ?>); }
        }).catch(function(e) { alert(e.message); });
}
</script>
<?php endif; ?>
