<?php
/**
 * 마이페이지 서비스 관리 — 메일 탭
 *
 * 호스팅에는 기본 메일 5개가 무료 포함 정책 (mail_default_title)
 * 메일 구독이 없어도 탭은 항상 표시되며, 사용자는 기본 메일을 직접 추가/삭제 가능
 *
 * 외부 변수: $subs (mail 타입 구독 배열, 빈 배열 가능), $statusLabels, $primaryDomain, $order
 */

// 무료 메일 한도 (호스팅 플랜의 free_mail_count, 기본 5)
$_freeMailLimit = 5;
foreach ($_hostingPlansData ?? [] as $_hp) {
    if (($_hp['capacity'] ?? '') === ($order['hosting_capacity'] ?? '')) {
        if (isset($_hp['free_mail_count']) && is_numeric($_hp['free_mail_count'])) {
            $_freeMailLimit = (int)$_hp['free_mail_count'];
        }
        break;
    }
}

// 현재 메일 계정 집계 — 중복 제거 (mail subscription 우선, hosting metadata 의 사본은 무시)
$allAccounts = [];
$basicSub = null;   // 기본 메일을 담는 구독 (없으면 null)
$bizSub = null;     // 비즈니스 메일 구독
$_seenAddresses = [];

// 1) type='mail' 진짜 mail subscription 우선 처리
foreach ($subs as $sub) {
    if (($sub['type'] ?? '') !== 'mail') continue;
    $meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
    $isBiz = stripos($sub['label'], '비즈니스') !== false || stripos($sub['label'], 'ビジネス') !== false || stripos($sub['label'], 'Business') !== false;
    if ($isBiz) {
        $bizSub = $sub;
    } else {
        $basicSub = $sub;
    }
    foreach ($meta['mail_accounts'] ?? [] as $ma) {
        $addr = strtolower($ma['address'] ?? '');
        if (!$addr || isset($_seenAddresses[$addr])) continue;
        $_seenAddresses[$addr] = true;
        $allAccounts[] = [
            'address' => $ma['address'] ?? '',
            'type' => $isBiz ? 'business' : 'basic',
            'type_label' => $isBiz ? __('services.detail.mail_type_biz') : __('services.detail.mail_type_default'),
            'type_color' => $isBiz ? 'amber' : 'green',
            'sub_id' => $sub['id'],
        ];
    }
}
// 2) hosting 가상 변환 sub (옛 데이터 호환) — 위에서 이미 처리한 주소는 skip
foreach ($subs as $sub) {
    if (($sub['type'] ?? '') === 'mail') continue;
    $meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
    foreach ($meta['mail_accounts'] ?? [] as $ma) {
        $addr = strtolower($ma['address'] ?? '');
        if (!$addr || isset($_seenAddresses[$addr])) continue;
        $_seenAddresses[$addr] = true;
        $allAccounts[] = [
            'address' => $ma['address'] ?? '',
            'type' => 'basic',
            'type_label' => __('services.detail.mail_type_default'),
            'type_color' => 'green',
            'sub_id' => $sub['id'],
        ];
    }
}

// 메일서버 정보 (실제 구독이 있을 때 우선 사용, 없으면 기본 메일 정책 안내만)
$_seedSub = $basicSub ?: $bizSub;
$mailServer = [];
// 웹메일 URL 결정 — metadata.mail_server.webmail_url > .env WEBMAIL_URL > 기본값
$_webmailUrl = $_ENV['WEBMAIL_URL'] ?? 'https://mail.voscms.com/';
if ($_seedSub) {
    $_seedMeta = json_decode($_seedSub['metadata'] ?? '{}', true) ?: [];
    $mailServer = $_seedMeta['mail_server'] ?? [];
    if (!empty($mailServer['webmail_url'])) {
        $_webmailUrl = $mailServer['webmail_url'];
    }
}

$basicCount = count(array_filter($allAccounts, fn($a) => $a['type'] === 'basic'));
$bizCount = count(array_filter($allAccounts, fn($a) => $a['type'] === 'business'));
$canAddBasic = $basicCount < $_freeMailLimit;
$mailDomain = $primaryDomain ?: '';
?>

<div class="space-y-4">
    <!-- 메일서버 정보 (관리자 설정 시에만 표시) -->
    <?php if (!empty($mailServer['imap_host']) || !empty($mailServer['smtp_host'])): ?>
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 p-5">
        <p class="text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3"><?= htmlspecialchars(__('services.detail.mail_settings')) ?></p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <table class="text-xs">
                <tbody class="divide-y divide-gray-100 dark:divide-zinc-700/50">
                    <tr><td class="py-1.5 text-zinc-400 w-24"><?= htmlspecialchars(__('services.detail.mail_imap_host')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailServer['imap_host'] ?? '-') ?></td></tr>
                    <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.detail.mail_imap_port')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailServer['imap_port'] ?? '993') ?></td></tr>
                    <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.detail.mail_smtp_host')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailServer['smtp_host'] ?? '-') ?></td></tr>
                    <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.detail.mail_smtp_port')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailServer['smtp_port'] ?? '587') ?></td></tr>
                </tbody>
            </table>
            <table class="text-xs">
                <tbody class="divide-y divide-gray-100 dark:divide-zinc-700/50">
                    <tr><td class="py-1.5 text-zinc-400 w-24"><?= htmlspecialchars(__('services.detail.mail_encryption')) ?></td><td class="py-1.5 text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailServer['encryption'] ?? 'SSL/TLS') ?></td></tr>
                    <?php if (!empty($mailServer['webmail_url'])): ?>
                    <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.detail.mail_webmail')) ?></td><td class="py-1.5"><a href="<?= htmlspecialchars($mailServer['webmail_url']) ?>" target="_blank" class="text-blue-600 hover:underline font-mono"><?= htmlspecialchars($mailServer['webmail_url']) ?></a></td></tr>
                    <?php endif; ?>
                    <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.detail.mail_user_account')) ?></td><td class="py-1.5 text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars(__('services.detail.mail_account_format')) ?></td></tr>
                    <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.detail.mail_password')) ?></td><td class="py-1.5 text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars(__('services.detail.mail_pw_format')) ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- 기본 메일 카드 (호스팅 무료 포함) -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700 flex items-center justify-between flex-wrap gap-2">
            <div class="flex items-center gap-2 min-w-0">
                <h3 class="text-sm font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars(__('services.detail.mail_type_default')) ?></h3>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400"><?= htmlspecialchars(__('services.detail.mail_default_badge')) ?></span>
                <span class="text-xs text-zinc-400"><?= $basicCount ?> / <?= $_freeMailLimit ?></span>
            </div>
            <button type="button" onclick="openMailAddModal()" class="text-xs px-3 py-1.5 rounded-lg font-medium transition <?= $canAddBasic ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-400 cursor-not-allowed' ?>" <?= $canAddBasic ? '' : 'disabled' ?>>
                <?= htmlspecialchars(__('services.detail.btn_add_mail')) ?>
            </button>
        </div>
        <div class="p-5">
            <?php if ($basicCount === 0): ?>
            <!-- 빈 상태 -->
            <div class="text-center py-8">
                <div class="w-14 h-14 rounded-full bg-green-50 dark:bg-green-900/20 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <p class="text-sm font-medium text-zinc-900 dark:text-white mb-1"><?= htmlspecialchars(__('services.detail.mail_empty_title')) ?></p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 max-w-md mx-auto"><?= htmlspecialchars(__('services.detail.mail_empty_desc', ['count' => $_freeMailLimit])) ?></p>
                <button type="button" onclick="openMailAddModal()" class="mt-4 inline-flex items-center gap-1.5 text-xs px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    <?= htmlspecialchars(__('services.detail.btn_add_mail')) ?>
                </button>
            </div>
            <?php else: ?>
            <!-- 기본 메일 계정 목록 -->
            <div class="space-y-2">
                <?php foreach ($allAccounts as $acc): if ($acc['type'] !== 'basic') continue; ?>
                <div class="bg-gray-50 dark:bg-zinc-700/30 rounded-lg overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-3 gap-2">
                        <div class="flex items-center gap-3 min-w-0 flex-1">
                            <div class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-mono font-medium text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($acc['address']) ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0 flex-wrap justify-end">
                            <button type="button" onclick="openWebmail('<?= htmlspecialchars($acc['address'], ENT_QUOTES) ?>')" class="inline-flex items-center gap-1 text-[10px] px-2.5 py-1.5 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-700 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                <?= htmlspecialchars(__('services.detail.btn_webmail')) ?>
                            </button>
                            <button type="button" onclick="togglePwForm(this)" class="text-[10px] px-2.5 py-1.5 text-zinc-500 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-600 rounded-lg hover:border-blue-400 hover:text-blue-600 transition"><?= htmlspecialchars(__('services.detail.btn_change_pw')) ?></button>
                            <button type="button" onclick="toggleAddrForm(this)" class="text-[10px] px-2.5 py-1.5 text-zinc-500 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-600 rounded-lg hover:border-blue-400 hover:text-blue-600 transition"><?= htmlspecialchars(__('services.detail.btn_change_address')) ?></button>
                            <button type="button" onclick="upgradeMailToBiz('<?= htmlspecialchars($acc['address'], ENT_QUOTES) ?>')" class="text-[10px] px-2.5 py-1.5 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-700 rounded-lg hover:bg-amber-50 dark:hover:bg-amber-900/20 transition"><?= htmlspecialchars(__('services.detail.btn_upgrade_to_biz')) ?></button>
                            <button type="button" onclick="confirmDeleteMail('<?= htmlspecialchars($acc['address'], ENT_QUOTES) ?>', <?= $acc['sub_id'] ?>)" class="text-[10px] px-2.5 py-1.5 text-zinc-500 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-600 rounded-lg hover:border-red-400 hover:text-red-600 transition"><?= htmlspecialchars(__('services.detail.btn_delete')) ?></button>
                        </div>
                    </div>
                    <!-- 비밀번호 변경 폼 -->
                    <div class="pw-form hidden px-4 pb-4">
                        <div class="p-3 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                            <div class="flex items-end gap-2 flex-wrap">
                                <div class="flex-1 min-w-[140px]">
                                    <label class="text-[10px] text-zinc-500 block mb-1"><?= htmlspecialchars(__('services.detail.pw_new')) ?></label>
                                    <input type="password" class="pw-new w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg" placeholder="<?= htmlspecialchars(__('services.detail.pw_placeholder_8')) ?>">
                                </div>
                                <div class="flex-1 min-w-[140px]">
                                    <label class="text-[10px] text-zinc-500 block mb-1"><?= htmlspecialchars(__('services.detail.pw_confirm')) ?></label>
                                    <input type="password" class="pw-confirm w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg" placeholder="<?= htmlspecialchars(__('services.detail.pw_placeholder_again')) ?>">
                                </div>
                                <button type="button" onclick="changePw(this,'<?= htmlspecialchars($acc['address'], ENT_QUOTES) ?>',<?= $acc['sub_id'] ?>)"
                                        class="px-4 py-2 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition whitespace-nowrap"><?= htmlspecialchars(__('services.detail.btn_change')) ?></button>
                                <button type="button" onclick="this.closest('.pw-form').classList.add('hidden')"
                                        class="px-3 py-2 text-xs text-zinc-400 hover:text-zinc-600"><?= htmlspecialchars(__('services.detail.btn_cancel')) ?></button>
                            </div>
                        </div>
                    </div>
                    <!-- 이메일 주소 변경 폼 (오타 수정용) -->
                    <div class="addr-form hidden px-4 pb-4">
                        <div class="p-3 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                            <p class="text-[10px] text-amber-600 dark:text-amber-400 mb-2">⚠ <?= htmlspecialchars(__('services.detail.addr_change_warning')) ?></p>
                            <div class="flex items-end gap-2 flex-wrap">
                                <div class="flex-1 min-w-[200px]">
                                    <label class="text-[10px] text-zinc-500 block mb-1"><?= htmlspecialchars(__('services.detail.addr_new')) ?></label>
                                    <div class="flex">
                                        <input type="text" class="addr-new flex-1 px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-l-lg" placeholder="info" value="<?= htmlspecialchars(explode('@', $acc['address'])[0] ?? '') ?>">
                                        <span class="px-3 py-2 text-sm bg-zinc-100 dark:bg-zinc-600 text-zinc-600 dark:text-zinc-300 border border-l-0 border-zinc-300 dark:border-zinc-600 rounded-r-lg">@<?= htmlspecialchars(explode('@', $acc['address'])[1] ?? '') ?></span>
                                    </div>
                                </div>
                                <button type="button" onclick="changeAddr(this,'<?= htmlspecialchars($acc['address'], ENT_QUOTES) ?>',<?= $acc['sub_id'] ?>)"
                                        class="px-4 py-2 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition whitespace-nowrap"><?= htmlspecialchars(__('services.detail.btn_change')) ?></button>
                                <button type="button" onclick="this.closest('.addr-form').classList.add('hidden')"
                                        class="px-3 py-2 text-xs text-zinc-400 hover:text-zinc-600"><?= htmlspecialchars(__('services.detail.btn_cancel')) ?></button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 비즈니스 메일 섹션 (항상 표시) -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700 flex items-center justify-between flex-wrap gap-2">
            <div class="flex items-center gap-2 min-w-0">
                <h3 class="text-sm font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars(__('services.detail.mail_type_biz')) ?></h3>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400"><?= htmlspecialchars(__('services.detail.bizmail_badge_paid')) ?></span>
                <?php if ($bizSub): $bst = $statusLabels[$bizSub['status']] ?? [__('services.mypage.status_unknown'), 'bg-gray-100 text-gray-500']; ?>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-medium <?= $bst[1] ?>"><?= $bst[0] ?></span>
                <?php endif; ?>
                <span class="text-xs text-zinc-400"><?= $bizCount ?><?= htmlspecialchars(__('services.detail.count_unit')) ?></span>
            </div>
            <div class="flex items-center gap-2 flex-wrap">
                <?php if ($bizSub && ($bizSub['service_class'] ?? '') === 'recurring' && $bizSub['status'] === 'active'): ?>
                <div class="flex items-center gap-2">
                    <span class="text-xs text-zinc-400"><?= htmlspecialchars(__('services.detail.auto_renew')) ?></span>
                    <label class="relative inline-flex items-center cursor-pointer">
                        <input type="checkbox" class="sr-only peer" <?= $bizSub['auto_renew'] ? 'checked' : '' ?>
                               onchange="serviceAction('toggle_auto_renew',{subscription_id:<?= $bizSub['id'] ?>,auto_renew:this.checked})">
                        <div class="w-9 h-5 bg-zinc-200 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                    </label>
                </div>
                <?php endif; ?>
                <button type="button" onclick="requestBizmailUpgrade()" class="text-xs px-3 py-1.5 rounded-lg font-medium bg-amber-600 text-white hover:bg-amber-700 transition">
                    <?= htmlspecialchars(__('services.detail.btn_add_mail')) ?>
                </button>
            </div>
        </div>
        <div class="p-5">
            <?php if ($bizCount === 0): ?>
            <!-- 빈 상태 -->
            <div class="text-center py-8">
                <div class="w-14 h-14 rounded-full bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                </div>
                <p class="text-sm font-medium text-zinc-900 dark:text-white mb-1"><?= htmlspecialchars(__('services.detail.bizmail_empty_title')) ?></p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 max-w-md mx-auto"><?= htmlspecialchars(__('services.detail.bizmail_upgrade_desc')) ?></p>
                <button type="button" onclick="requestBizmailUpgrade()" class="mt-4 inline-flex items-center gap-1.5 text-xs px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    <?= htmlspecialchars(__('services.detail.btn_add_mail')) ?>
                </button>
            </div>
            <?php else: ?>
            <!-- 비즈니스 메일 계정 목록 -->
            <div class="space-y-2">
                <?php foreach ($allAccounts as $acc): if ($acc['type'] !== 'business') continue; ?>
                <div class="bg-gray-50 dark:bg-zinc-700/30 rounded-lg overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-3 gap-2">
                        <div class="flex items-center gap-3 min-w-0 flex-1">
                            <div class="w-8 h-8 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-mono font-medium text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($acc['address']) ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0 flex-wrap justify-end">
                            <button type="button" onclick="togglePwForm(this)" class="text-[10px] px-2.5 py-1.5 text-zinc-500 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-600 rounded-lg hover:border-blue-400 hover:text-blue-600 transition"><?= htmlspecialchars(__('services.detail.btn_change_pw')) ?></button>
                            <button type="button" onclick="confirmDeleteMail('<?= htmlspecialchars($acc['address'], ENT_QUOTES) ?>', <?= $acc['sub_id'] ?>)" class="text-[10px] px-2.5 py-1.5 text-zinc-500 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-600 rounded-lg hover:border-red-400 hover:text-red-600 transition"><?= htmlspecialchars(__('services.detail.btn_delete')) ?></button>
                        </div>
                    </div>
                    <div class="pw-form hidden px-4 pb-4">
                        <div class="p-3 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                            <div class="flex items-end gap-2 flex-wrap">
                                <div class="flex-1 min-w-[140px]">
                                    <label class="text-[10px] text-zinc-500 block mb-1"><?= htmlspecialchars(__('services.detail.pw_new')) ?></label>
                                    <input type="password" class="pw-new w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg" placeholder="<?= htmlspecialchars(__('services.detail.pw_placeholder_8')) ?>">
                                </div>
                                <div class="flex-1 min-w-[140px]">
                                    <label class="text-[10px] text-zinc-500 block mb-1"><?= htmlspecialchars(__('services.detail.pw_confirm')) ?></label>
                                    <input type="password" class="pw-confirm w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg" placeholder="<?= htmlspecialchars(__('services.detail.pw_placeholder_again')) ?>">
                                </div>
                                <button type="button" onclick="changePw(this,'<?= htmlspecialchars($acc['address'], ENT_QUOTES) ?>',<?= $acc['sub_id'] ?>)"
                                        class="px-4 py-2 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition whitespace-nowrap"><?= htmlspecialchars(__('services.detail.btn_change')) ?></button>
                                <button type="button" onclick="this.closest('.pw-form').classList.add('hidden')"
                                        class="px-3 py-2 text-xs text-zinc-400 hover:text-zinc-600"><?= htmlspecialchars(__('services.detail.btn_cancel')) ?></button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 설정 가이드 -->
    <?php
    // VosCMS 호스팅 통합 메일 호스트명 (모든 고객 공통)
    $_mailHost = 'mail.voscms.com';
    $_webmailDefault = 'https://mail.voscms.com/';
    $_hasMailServer = !empty($mailServer['imap_host']) || !empty($mailServer['smtp_host']);
    $_imapHost = $mailServer['imap_host'] ?? $_mailHost;
    $_imapPort = $mailServer['imap_port'] ?? '993';
    $_smtpHost = $mailServer['smtp_host'] ?? $_mailHost;
    $_smtpPort = $mailServer['smtp_port'] ?? '587';
    $_encryption = $mailServer['encryption'] ?? 'SSL/TLS';
    $_webmailUrl = $mailServer['webmail_url'] ?? $_webmailDefault;
    ?>
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700 flex items-center gap-2">
            <svg class="w-4 h-4 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6.253v13m0-13C10.832 5.477 9.246 5 7.5 5S4.168 5.477 3 6.253v13C4.168 18.477 5.754 18 7.5 18s3.332.477 4.5 1.253m0-13C13.168 5.477 14.754 5 16.5 5c1.747 0 3.332.477 4.5 1.253v13C19.832 18.477 18.247 18 16.5 18c-1.746 0-3.332.477-4.5 1.253"/></svg>
            <h3 class="text-sm font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars(__('services.detail.guide_section_title')) ?></h3>
        </div>
        <div class="p-5 space-y-5">
            <p class="text-xs text-zinc-600 dark:text-zinc-400 leading-relaxed"><?= htmlspecialchars(__('services.detail.guide_intro')) ?></p>

            <?php if (!$_hasMailServer): ?>
            <div class="text-xs text-amber-700 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/50 rounded-lg px-3 py-2">
                <?= htmlspecialchars(__('services.detail.guide_pending_setup')) ?>
            </div>
            <?php endif; ?>

            <!-- 단계별 안내 -->
            <ol class="space-y-2.5">
                <?php foreach ([1, 2, 3, 4] as $_n): ?>
                <li class="flex gap-3 items-start">
                    <span class="flex-shrink-0 w-6 h-6 rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 flex items-center justify-center text-xs font-bold"><?= $_n ?></span>
                    <p class="text-xs text-zinc-700 dark:text-zinc-300 pt-0.5 leading-relaxed"><?= htmlspecialchars(__('services.detail.guide_step_' . $_n)) ?></p>
                </li>
                <?php endforeach; ?>
            </ol>

            <!-- 받는메일 / 보내는메일 설정값 표 -->
            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                <div class="bg-gray-50 dark:bg-zinc-700/30 rounded-lg p-4">
                    <p class="text-xs font-bold text-zinc-700 dark:text-zinc-200 mb-3 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        <?= htmlspecialchars(__('services.detail.guide_imap_label')) ?>
                    </p>
                    <table class="text-xs w-full">
                        <tbody class="divide-y divide-gray-200 dark:divide-zinc-700/50">
                            <tr><td class="py-1.5 text-zinc-400 w-24"><?= htmlspecialchars(__('services.detail.guide_field_host')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($_imapHost) ?></td></tr>
                            <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.detail.guide_field_port')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($_imapPort) ?></td></tr>
                            <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.detail.guide_field_security')) ?></td><td class="py-1.5 text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($_encryption) ?></td></tr>
                            <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.detail.guide_field_username')) ?></td><td class="py-1.5 text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars(__('services.detail.guide_value_username')) ?></td></tr>
                            <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.detail.guide_field_password')) ?></td><td class="py-1.5 text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars(__('services.detail.guide_value_password')) ?></td></tr>
                        </tbody>
                    </table>
                </div>
                <div class="bg-gray-50 dark:bg-zinc-700/30 rounded-lg p-4">
                    <p class="text-xs font-bold text-zinc-700 dark:text-zinc-200 mb-3 flex items-center gap-1.5">
                        <svg class="w-3.5 h-3.5 text-emerald-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 19l9 2-9-18-9 18 9-2zm0 0v-8"/></svg>
                        <?= htmlspecialchars(__('services.detail.guide_smtp_label')) ?>
                    </p>
                    <table class="text-xs w-full">
                        <tbody class="divide-y divide-gray-200 dark:divide-zinc-700/50">
                            <tr><td class="py-1.5 text-zinc-400 w-24"><?= htmlspecialchars(__('services.detail.guide_field_host')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($_smtpHost) ?></td></tr>
                            <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.detail.guide_field_port')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($_smtpPort) ?></td></tr>
                            <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.detail.guide_field_security')) ?></td><td class="py-1.5 text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($_encryption) ?></td></tr>
                            <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.detail.guide_field_auth')) ?></td><td class="py-1.5 text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars(__('services.detail.guide_value_auth_same')) ?></td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <?php if ($_webmailUrl): ?>
            <!-- 웹메일 안내 -->
            <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/50 rounded-lg px-4 py-3 flex items-start gap-3">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                <div class="flex-1 min-w-0">
                    <p class="text-xs font-bold text-blue-900 dark:text-blue-300 mb-0.5"><?= htmlspecialchars(__('services.detail.guide_webmail_label')) ?></p>
                    <p class="text-xs text-blue-700 dark:text-blue-400 mb-1.5"><?= htmlspecialchars(__('services.detail.guide_webmail_desc')) ?></p>
                    <a href="<?= htmlspecialchars($_webmailUrl) ?>" target="_blank" rel="noopener" class="text-xs font-mono text-blue-700 dark:text-blue-400 hover:underline break-all"><?= htmlspecialchars($_webmailUrl) ?></a>
                </div>
            </div>
            <?php endif; ?>

            <!-- 팁 -->
            <div class="text-xs text-zinc-500 dark:text-zinc-400 bg-zinc-50 dark:bg-zinc-700/30 border border-zinc-200 dark:border-zinc-700 rounded-lg px-3 py-2 flex items-start gap-2">
                <svg class="w-3.5 h-3.5 text-amber-500 flex-shrink-0 mt-0.5" fill="currentColor" viewBox="0 0 20 20"><path d="M11 3a1 1 0 10-2 0v1a1 1 0 102 0V3zM15.657 5.757a1 1 0 00-1.414-1.414l-.707.707a1 1 0 001.414 1.414l.707-.707zM18 10a1 1 0 01-1 1h-1a1 1 0 110-2h1a1 1 0 011 1zM5.05 6.464A1 1 0 106.464 5.05l-.707-.707a1 1 0 00-1.414 1.414l.707.707zM5 10a1 1 0 01-1 1H3a1 1 0 110-2h1a1 1 0 011 1zM8 16v-1h4v1a2 2 0 11-4 0zM12 14c.015-.34.208-.646.477-.859a4 4 0 10-4.954 0c.27.213.462.519.476.859h4.002z"/></svg>
                <span><?= htmlspecialchars(__('services.detail.guide_tip')) ?></span>
            </div>

            <!-- 도메인 설정 상태 안내 (mode 기반 자동 분기) -->
            <?php
            // hosting subscription 의 mail_provision 정보 추출
            $_provisionInfo = null;
            foreach ($servicesByType['hosting'] ?? [] as $_hSub) {
                $_hMeta = json_decode($_hSub['metadata'] ?? '{}', true) ?: [];
                if (!empty($_hMeta['mail_provision'])) {
                    $_provisionInfo = $_hMeta['mail_provision'];
                    break;
                }
            }
            $_provisionMode = $_provisionInfo['mode'] ?? 'pending';
            $_zoneStatus = $_provisionInfo['zone_status'] ?? null;
            $_nameServers = $_provisionInfo['name_servers'] ?? [];
            $_finalDomain = $_provisionInfo['final_domain'] ?? null;
            ?>
            <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4">
                <p class="text-xs font-bold text-zinc-700 dark:text-zinc-200 mb-2 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-violet-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/></svg>
                    <?= htmlspecialchars(__('services.detail.domain_status_title')) ?>
                </p>

                <?php if ($_provisionMode === 'active'): ?>
                <!-- 셋업 완료 — 사용 가능 -->
                <div class="bg-emerald-50 dark:bg-emerald-900/20 border border-emerald-200 dark:border-emerald-800/50 rounded-lg px-4 py-3 flex items-start gap-3">
                    <svg class="w-5 h-5 text-emerald-600 dark:text-emerald-400 flex-shrink-0 mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-bold text-emerald-900 dark:text-emerald-300 mb-0.5"><?= htmlspecialchars(__('services.detail.domain_status_active_title')) ?></p>
                        <p class="text-xs text-emerald-700 dark:text-emerald-400 leading-relaxed"><?= htmlspecialchars(__('services.detail.domain_status_active_desc', ['domain' => $mailDomain])) ?></p>
                    </div>
                </div>

                <?php else: ?>
                <!-- 셋업 진행 중 — 모든 pending 단계 통합 (단계 표시) -->
                <?php
                // 단계 결정
                $_stepLabel = __('services.detail.setup_step_payment');  // 기본
                if ($_provisionMode === 'new_pending') {
                    $_stepLabel = __('services.detail.setup_step_domain_acquiring');
                } elseif ($_provisionMode === 'existing_pending') {
                    $_stepLabel = __('services.detail.setup_step_ns_propagation');
                } elseif ($_provisionMode === 'pending' || $_provisionMode === null) {
                    $_stepLabel = __('services.detail.setup_step_initializing');
                }
                ?>
                <div class="bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800/50 rounded-lg px-4 py-4">
                    <div class="flex items-start gap-3 mb-3">
                        <svg class="w-5 h-5 text-blue-600 dark:text-blue-400 flex-shrink-0 mt-0.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                        <div class="flex-1 min-w-0">
                            <p class="text-xs font-bold text-blue-900 dark:text-blue-300 mb-1"><?= htmlspecialchars(__('services.detail.setup_in_progress_title')) ?></p>
                            <p class="text-xs text-blue-800 dark:text-blue-400 leading-relaxed"><?= htmlspecialchars(__('services.detail.setup_in_progress_desc')) ?></p>
                        </div>
                    </div>
                    <!-- 현재 단계 -->
                    <div class="bg-white dark:bg-zinc-800 rounded px-3 py-2 text-xs">
                        <span class="text-zinc-400"><?= htmlspecialchars(__('services.detail.setup_current_step')) ?>:</span>
                        <span class="text-zinc-800 dark:text-zinc-200 font-medium ml-1"><?= htmlspecialchars($_stepLabel) ?></span>
                    </div>
                    <p class="text-[11px] text-blue-700 dark:text-blue-400 italic mt-2"><?= htmlspecialchars(__('services.detail.setup_completion_notice')) ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- 삭제·복원 정책 안내 -->
            <div class="border-t border-zinc-200 dark:border-zinc-700 pt-4">
                <p class="text-xs font-bold text-zinc-700 dark:text-zinc-200 mb-2 flex items-center gap-1.5">
                    <svg class="w-3.5 h-3.5 text-rose-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6M1 7h22M11 3h2a2 2 0 012 2v2H9V5a2 2 0 012-2z"/></svg>
                    <?= htmlspecialchars(__('services.detail.delete_policy_title')) ?>
                </p>
                <ul class="text-xs text-zinc-600 dark:text-zinc-400 space-y-1.5 leading-relaxed list-disc pl-5">
                    <li><?= htmlspecialchars(__('services.detail.delete_policy_immediate')) ?></li>
                    <li><strong class="text-emerald-600 dark:text-emerald-400"><?= htmlspecialchars(__('services.detail.delete_policy_restore_window')) ?></strong> <?= htmlspecialchars(__('services.detail.delete_policy_restore_desc')) ?></li>
                    <li><strong class="text-rose-600 dark:text-rose-400"><?= htmlspecialchars(__('services.detail.delete_policy_permanent')) ?></strong> <?= htmlspecialchars(__('services.detail.delete_policy_permanent_desc')) ?></li>
                </ul>
            </div>
        </div>
    </div>
</div>

<!-- 메일 추가 모달 -->
<div id="mailAddModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl max-w-md w-full p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars(__('services.detail.mail_add_modal_title')) ?></h3>
            <button type="button" onclick="closeMailAddModal()" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="space-y-3">
            <div>
                <label class="text-xs text-zinc-500 dark:text-zinc-400 block mb-1"><?= htmlspecialchars(__('services.detail.mail_address_label')) ?></label>
                <div class="flex items-center gap-2">
                    <input type="text" id="mailAddrLocal" class="flex-1 px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg font-mono" placeholder="<?= htmlspecialchars(__('services.detail.mail_address_placeholder')) ?>" autocomplete="off">
                    <span class="text-sm text-zinc-500 dark:text-zinc-400 font-mono">@<?= htmlspecialchars($mailDomain) ?></span>
                </div>
            </div>
            <div>
                <label class="text-xs text-zinc-500 dark:text-zinc-400 block mb-1"><?= htmlspecialchars(__('services.detail.mail_password_label')) ?></label>
                <input type="password" id="mailAddPw" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg" placeholder="<?= htmlspecialchars(__('services.detail.pw_placeholder_8')) ?>">
            </div>
            <div>
                <label class="text-xs text-zinc-500 dark:text-zinc-400 block mb-1"><?= htmlspecialchars(__('services.detail.mail_password_confirm_label')) ?></label>
                <input type="password" id="mailAddPwConfirm" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg" placeholder="<?= htmlspecialchars(__('services.detail.pw_placeholder_again')) ?>">
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 mt-5">
            <button type="button" onclick="closeMailAddModal()" class="px-4 py-2 text-xs text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200"><?= htmlspecialchars(__('services.detail.btn_cancel')) ?></button>
            <button type="button" id="mailAddSubmit" onclick="submitMailAdd()" class="px-4 py-2 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition"><?= htmlspecialchars(__('services.detail.btn_add')) ?></button>
        </div>
    </div>
</div>

<script>
var _mailDomain = <?= json_encode($mailDomain, JSON_UNESCAPED_UNICODE) ?>;
var _mailOrderId = <?= (int)$order['id'] ?>;
var _webmailUrl = <?= json_encode($_webmailUrl, JSON_UNESCAPED_UNICODE) ?>;

// 웹메일 새 탭 — Roundcube _user GET 파라미터로 username prefill
function openWebmail(address) {
    var url = _webmailUrl;
    var sep = url.indexOf('?') >= 0 ? '&' : '?';
    var fullUrl = url + sep + '_user=' + encodeURIComponent(address);
    window.open(fullUrl, '_blank', 'noopener,noreferrer');
}

// 이메일 주소 변경 폼 토글 (오타 수정용)
function toggleAddrForm(btn) {
    var card = btn.closest('.bg-gray-50, .dark\\:bg-zinc-700\\/30') || btn.closest('div[class*="rounded-lg"]');
    var form = card ? card.querySelector('.addr-form') : null;
    if (form) { form.classList.toggle('hidden'); if (!form.classList.contains('hidden')) form.querySelector('.addr-new').focus(); }
}

// 이메일 주소 변경 (오타 수정용)
function changeAddr(btn, oldAddress, subId) {
    var form = btn.closest('.addr-form');
    var newLocal = form.querySelector('.addr-new').value.trim().toLowerCase();
    if (!newLocal || !/^[a-z0-9._-]+$/.test(newLocal)) {
        alert(<?= json_encode(__('services.detail.alert_invalid_address'), JSON_UNESCAPED_UNICODE) ?>); return;
    }
    var domain = oldAddress.split('@')[1] || '';
    var newAddress = newLocal + '@' + domain;
    if (newAddress === oldAddress) { form.classList.add('hidden'); return; }
    if (!confirm(<?= json_encode(__('services.detail.confirm_change_address'), JSON_UNESCAPED_UNICODE) ?>.replace(':old', oldAddress).replace(':new', newAddress))) return;
    btn.disabled = true; btn.textContent = <?= json_encode(__('services.detail.btn_processing'), JSON_UNESCAPED_UNICODE) ?>;
    serviceAction('change_mail_address', { subscription_id: subId, old_address: oldAddress, new_address: newAddress })
        .then(function(d) {
            btn.disabled = false; btn.textContent = <?= json_encode(__('services.detail.btn_change'), JSON_UNESCAPED_UNICODE) ?>;
            if (d.success) {
                alert(<?= json_encode(__('services.detail.alert_addr_changed'), JSON_UNESCAPED_UNICODE) ?>);
                location.reload();
            } else alert(d.message || <?= json_encode(__('services.detail.alert_failed'), JSON_UNESCAPED_UNICODE) ?>);
        });
}

function togglePwForm(btn) {
    var card = btn.closest('.bg-gray-50, .dark\\:bg-zinc-700\\/30') || btn.closest('div[class*="rounded-lg"]');
    var form = card ? card.querySelector('.pw-form') : null;
    if (form) { form.classList.toggle('hidden'); if (!form.classList.contains('hidden')) form.querySelector('.pw-new').focus(); }
}

function changePw(btn, address, subId) {
    var form = btn.closest('.pw-form');
    var pw = form.querySelector('.pw-new').value;
    var confirm = form.querySelector('.pw-confirm').value;
    if (!pw || pw.length < 8) { alert(<?= json_encode(__('services.detail.alert_pw_min_8'), JSON_UNESCAPED_UNICODE) ?>); return; }
    if (pw !== confirm) { alert(<?= json_encode(__('services.detail.alert_pw_mismatch'), JSON_UNESCAPED_UNICODE) ?>); return; }
    btn.disabled = true; btn.textContent = <?= json_encode(__('services.detail.btn_processing'), JSON_UNESCAPED_UNICODE) ?>;
    serviceAction('change_mail_password', { subscription_id: subId, address: address, password: pw })
        .then(function(d) {
            btn.disabled = false; btn.textContent = <?= json_encode(__('services.detail.btn_change'), JSON_UNESCAPED_UNICODE) ?>;
            if (d.success) {
                alert(d.applied ? <?= json_encode(__('services.detail.alert_pw_applied'), JSON_UNESCAPED_UNICODE) ?> : <?= json_encode(__('services.detail.alert_request_done'), JSON_UNESCAPED_UNICODE) ?>);
                form.classList.add('hidden');
                form.querySelector('.pw-new').value = '';
                form.querySelector('.pw-confirm').value = '';
            } else alert(d.message || <?= json_encode(__('services.detail.alert_failed'), JSON_UNESCAPED_UNICODE) ?>);
        });
}

function openMailAddModal() {
    var m = document.getElementById('mailAddModal');
    m.classList.remove('hidden');
    m.classList.add('flex');
    document.body.style.overflow = 'hidden';
    setTimeout(function() {
        var el = document.getElementById('mailAddrLocal');
        if (el) el.focus();
    }, 50);
}
function closeMailAddModal() {
    var m = document.getElementById('mailAddModal');
    m.classList.add('hidden');
    m.classList.remove('flex');
    document.body.style.overflow = '';
    document.getElementById('mailAddrLocal').value = '';
    document.getElementById('mailAddPw').value = '';
    document.getElementById('mailAddPwConfirm').value = '';
}
function submitMailAdd() {
    var local = document.getElementById('mailAddrLocal').value.trim();
    var pw = document.getElementById('mailAddPw').value;
    var pwc = document.getElementById('mailAddPwConfirm').value;
    if (!local || !/^[a-zA-Z0-9._-]+$/.test(local)) { alert(<?= json_encode(__('services.detail.alert_invalid_address'), JSON_UNESCAPED_UNICODE) ?>); return; }
    if (!pw || pw.length < 8) { alert(<?= json_encode(__('services.detail.alert_pw_min_8'), JSON_UNESCAPED_UNICODE) ?>); return; }
    if (pw !== pwc) { alert(<?= json_encode(__('services.detail.alert_pw_mismatch'), JSON_UNESCAPED_UNICODE) ?>); return; }
    var btn = document.getElementById('mailAddSubmit');
    btn.disabled = true; btn.textContent = <?= json_encode(__('services.detail.btn_processing'), JSON_UNESCAPED_UNICODE) ?>;
    serviceAction('add_mail_account', {
        order_id: _mailOrderId,
        address: local + '@' + _mailDomain,
        password: pw
    }).then(function(d) {
        btn.disabled = false;
        btn.textContent = <?= json_encode(__('services.detail.btn_add'), JSON_UNESCAPED_UNICODE) ?>;
        if (d.success) {
            alert(<?= json_encode(__('services.detail.alert_mail_added'), JSON_UNESCAPED_UNICODE) ?>);
            location.reload();
        } else {
            alert(d.message || <?= json_encode(__('services.detail.alert_failed'), JSON_UNESCAPED_UNICODE) ?>);
        }
    });
}
function confirmDeleteMail(address, subId) {
    if (!confirm(<?= json_encode(__('services.detail.confirm_delete_mail'), JSON_UNESCAPED_UNICODE) ?>.replace(':address', address))) return;
    serviceAction('delete_mail_account', { subscription_id: subId, address: address })
        .then(function(d) {
            if (d.success) {
                alert(<?= json_encode(__('services.detail.alert_mail_deleted'), JSON_UNESCAPED_UNICODE) ?>);
                location.reload();
            } else alert(d.message || <?= json_encode(__('services.detail.alert_failed'), JSON_UNESCAPED_UNICODE) ?>);
        });
}
function requestBizmailUpgrade(address) {
    var data = { order_id: _mailOrderId };
    if (address) data.address = address;
    serviceAction('request_bizmail_upgrade', data)
        .then(function(d) {
            if (d.success) alert(<?= json_encode(__('services.detail.alert_bizmail_requested'), JSON_UNESCAPED_UNICODE) ?>);
            else alert(d.message || <?= json_encode(__('services.detail.alert_failed'), JSON_UNESCAPED_UNICODE) ?>);
        });
}
function upgradeMailToBiz(address) {
    var msg = <?= json_encode(__('services.detail.confirm_upgrade_to_biz'), JSON_UNESCAPED_UNICODE) ?>.replace(':address', address);
    if (!confirm(msg)) return;
    requestBizmailUpgrade(address);
}
</script>
