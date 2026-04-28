<?php
/**
 * 관리자 서비스 상세 — 메일 탭
 * $subs: mail 타입 구독 배열
 */
if (empty($subs)) {
    echo '<div class="px-5 py-12 text-center text-sm text-zinc-400">' . htmlspecialchars(__('services.admin_orders.empty_mail')) . '</div>';
    return;
}
// 모든 메일 계정 수집
$allAccounts = [];
foreach ($subs as $sub) {
    $meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
    $isBiz = stripos($sub['label'], '비즈니스') !== false || stripos($sub['label'], 'ビジネス') !== false || stripos($sub['label'], 'Business') !== false;
    foreach ($meta['mail_accounts'] ?? [] as $ma) {
        $allAccounts[] = [
            'address' => $ma['address'] ?? '',
            'has_password' => !empty($ma['password']),
            'type' => $isBiz ? __('services.admin_orders.mail_type_biz_short') : __('services.admin_orders.mail_type_default_short'),
            'type_color' => $isBiz ? 'amber' : 'green',
            'sub_id' => $sub['id'],
            'sub_label' => $sub['label'],
        ];
    }
}

// 메일서버 정보 (시스템 설정 또는 주문 metadata)
$firstMeta = json_decode($subs[0]['metadata'] ?? '{}', true) ?: [];
$mailServer = $firstMeta['mail_server'] ?? [];
$_pendingTitle = __('services.admin_orders.btn_pending');
?>

<!-- 메일서버 정보 -->
<div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700">
    <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-2"><?= htmlspecialchars(__('services.detail.mail_settings')) ?></p>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <table class="text-xs">
            <tbody class="divide-y divide-gray-100 dark:divide-zinc-700/50">
                <tr><td class="py-1.5 text-zinc-400 w-28"><?= htmlspecialchars(__('services.detail.mail_imap_host')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailServer['imap_host'] ?? '-') ?></td></tr>
                <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.detail.mail_imap_port')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailServer['imap_port'] ?? '993') ?></td></tr>
                <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.detail.mail_smtp_host')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailServer['smtp_host'] ?? '-') ?></td></tr>
                <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.detail.mail_smtp_port')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailServer['smtp_port'] ?? '587') ?></td></tr>
            </tbody>
        </table>
        <table class="text-xs">
            <tbody class="divide-y divide-gray-100 dark:divide-zinc-700/50">
                <tr><td class="py-1.5 text-zinc-400 w-28"><?= htmlspecialchars(__('services.detail.mail_encryption')) ?></td><td class="py-1.5 text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailServer['encryption'] ?? 'SSL/TLS') ?></td></tr>
                <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.detail.mail_webmail')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailServer['webmail_url'] ?? '-') ?></td></tr>
                <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.detail.mail_user_account')) ?></td><td class="py-1.5 text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars(__('services.detail.mail_account_format')) ?></td></tr>
                <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.detail.mail_password')) ?></td><td class="py-1.5 text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars(__('services.detail.mail_pw_format')) ?></td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- 메일 계정 목록 -->
<div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700">
    <div class="flex items-center justify-between mb-3">
        <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider"><?= htmlspecialchars(__('services.detail.mail_accounts')) ?> (<?= count($allAccounts) ?>)</p>
        <button disabled class="text-[10px] px-2 py-1 text-zinc-400 border border-zinc-200 dark:border-zinc-600 rounded cursor-not-allowed opacity-50" title="<?= htmlspecialchars($_pendingTitle) ?>"><?= htmlspecialchars(__('services.admin_orders.btn_add_account')) ?></button>
    </div>
    <div class="space-y-1">
        <?php foreach ($allAccounts as $i => $acc): ?>
        <div class="flex items-center gap-3 px-3 py-2 bg-gray-50 dark:bg-zinc-700/30 rounded text-xs">
            <span class="text-zinc-400 w-4"><?= $i + 1 ?></span>
            <span class="font-mono font-medium text-zinc-800 dark:text-zinc-200 flex-1"><?= htmlspecialchars($acc['address']) ?></span>
            <span class="text-[10px] px-1.5 py-0.5 bg-<?= $acc['type_color'] ?>-50 text-<?= $acc['type_color'] ?>-600 rounded"><?= htmlspecialchars($acc['type']) ?></span>
            <span class="text-zinc-400 w-24"><?= $acc['has_password'] ? htmlspecialchars(__('services.admin_orders.mail_pw_set')) : htmlspecialchars(__('services.admin_orders.mail_pw_unset_short')) ?></span>
            <button disabled class="text-[10px] px-2 py-1 text-zinc-400 border border-zinc-200 dark:border-zinc-600 rounded cursor-not-allowed opacity-50" title="<?= htmlspecialchars($_pendingTitle) ?>"><?= htmlspecialchars(__('services.detail.btn_change_pw')) ?></button>
            <button disabled class="text-[10px] px-2 py-1 text-zinc-400 border border-zinc-200 dark:border-zinc-600 rounded cursor-not-allowed opacity-50" title="<?= htmlspecialchars($_pendingTitle) ?>"><?= htmlspecialchars(__('services.admin_orders.btn_remove')) ?></button>
        </div>
        <?php endforeach; ?>
    </div>
</div>

<!-- 관리 버튼 -->
<div class="px-5 py-4 flex flex-wrap gap-2">
    <button disabled class="px-3 py-1.5 text-xs text-zinc-400 border border-zinc-200 rounded-lg cursor-not-allowed opacity-50" title="<?= htmlspecialchars($_pendingTitle) ?>"><?= htmlspecialchars(__('services.admin_orders.btn_send_mail_setup')) ?></button>
    <button disabled class="px-3 py-1.5 text-xs text-zinc-400 border border-zinc-200 rounded-lg cursor-not-allowed opacity-50" title="<?= htmlspecialchars($_pendingTitle) ?>"><?= htmlspecialchars(__('services.admin_orders.btn_spam_filter')) ?></button>
</div>
