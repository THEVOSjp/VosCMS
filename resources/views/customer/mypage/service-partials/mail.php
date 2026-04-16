<?php
/**
 * 마이페이지 서비스 관리 — 메일 탭
 */
$allAccounts = [];
foreach ($subs as $sub) {
    $meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
    $isBiz = stripos($sub['label'], '비즈니스') !== false || stripos($sub['label'], 'ビジネス') !== false;
    foreach ($meta['mail_accounts'] ?? [] as $ma) {
        $allAccounts[] = [
            'address' => $ma['address'] ?? '',
            'type' => $isBiz ? 'business' : 'basic',
            'type_label' => $isBiz ? '비즈니스 메일' : '기본 메일',
            'type_color' => $isBiz ? 'amber' : 'green',
            'sub_id' => $sub['id'],
        ];
    }
}
$firstSub = $subs[0];
$fst = $statusLabels[$firstSub['status']] ?? ['알 수 없음', 'bg-gray-100 text-gray-500'];
$fsc = $firstSub['service_class'] ?? 'recurring';

// 메일서버 정보
$firstMeta = json_decode($firstSub['metadata'] ?? '{}', true) ?: [];
$mailServer = $firstMeta['mail_server'] ?? [];
?>

<div class="space-y-4">
    <!-- 메일서버 정보 (관리자 설정 시에만 표시) -->
    <?php if (!empty($mailServer['imap_host']) || !empty($mailServer['smtp_host'])): ?>
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 p-5">
        <p class="text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3">메일 설정 정보</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <table class="text-xs">
                <tbody class="divide-y divide-gray-100 dark:divide-zinc-700/50">
                    <tr><td class="py-1.5 text-zinc-400 w-24">받는메일 서버</td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailServer['imap_host'] ?? '-') ?></td></tr>
                    <tr><td class="py-1.5 text-zinc-400">IMAP 포트</td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailServer['imap_port'] ?? '993') ?></td></tr>
                    <tr><td class="py-1.5 text-zinc-400">보내는메일 서버</td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailServer['smtp_host'] ?? '-') ?></td></tr>
                    <tr><td class="py-1.5 text-zinc-400">SMTP 포트</td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailServer['smtp_port'] ?? '587') ?></td></tr>
                </tbody>
            </table>
            <table class="text-xs">
                <tbody class="divide-y divide-gray-100 dark:divide-zinc-700/50">
                    <tr><td class="py-1.5 text-zinc-400 w-24">보안 연결</td><td class="py-1.5 text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailServer['encryption'] ?? 'SSL/TLS') ?></td></tr>
                    <?php if (!empty($mailServer['webmail_url'])): ?>
                    <tr><td class="py-1.5 text-zinc-400">웹메일</td><td class="py-1.5"><a href="<?= htmlspecialchars($mailServer['webmail_url']) ?>" target="_blank" class="text-blue-600 hover:underline font-mono"><?= htmlspecialchars($mailServer['webmail_url']) ?></a></td></tr>
                    <?php endif; ?>
                    <tr><td class="py-1.5 text-zinc-400">사용자 계정</td><td class="py-1.5 text-zinc-800 dark:text-zinc-200">아이디@도메인</td></tr>
                    <tr><td class="py-1.5 text-zinc-400">비밀번호</td><td class="py-1.5 text-zinc-800 dark:text-zinc-200">설정한 비밀번호</td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- 메일 계정 목록 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700 flex items-center justify-between">
            <div class="flex items-center gap-2">
                <h3 class="text-sm font-bold text-zinc-900 dark:text-white">메일 계정</h3>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-medium <?= $fst[1] ?>"><?= $fst[0] ?></span>
                <span class="text-xs text-zinc-400"><?= count($allAccounts) ?>개</span>
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
            <?php endif; ?>
        </div>
        <div class="p-5 space-y-2">
            <?php foreach ($allAccounts as $acc): ?>
            <div class="bg-gray-50 dark:bg-zinc-700/30 rounded-lg overflow-hidden">
                <div class="flex items-center justify-between px-4 py-3">
                    <div class="flex items-center gap-3 min-w-0">
                        <div class="w-8 h-8 rounded-full bg-<?= $acc['type_color'] ?>-100 dark:bg-<?= $acc['type_color'] ?>-900/30 flex items-center justify-center shrink-0">
                            <svg class="w-4 h-4 text-<?= $acc['type_color'] ?>-600 dark:text-<?= $acc['type_color'] ?>-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                        </div>
                        <div class="min-w-0">
                            <p class="text-sm font-mono font-medium text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($acc['address']) ?></p>
                            <p class="text-[10px] text-<?= $acc['type_color'] ?>-600 dark:text-<?= $acc['type_color'] ?>-400"><?= $acc['type_label'] ?></p>
                        </div>
                    </div>
                    <button onclick="togglePwForm(this)" class="text-[10px] px-2.5 py-1.5 text-zinc-500 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-600 rounded-lg hover:border-blue-400 hover:text-blue-600 transition">비밀번호 변경</button>
                </div>
                <!-- 비밀번호 변경 폼 -->
                <div class="pw-form hidden px-4 pb-4">
                    <div class="p-3 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                        <div class="flex items-end gap-2">
                            <div class="flex-1">
                                <label class="text-[10px] text-zinc-500 block mb-1">새 비밀번호</label>
                                <input type="password" class="pw-new w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg" placeholder="8자 이상">
                            </div>
                            <div class="flex-1">
                                <label class="text-[10px] text-zinc-500 block mb-1">비밀번호 확인</label>
                                <input type="password" class="pw-confirm w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg" placeholder="다시 입력">
                            </div>
                            <button onclick="changePw(this,'<?= htmlspecialchars($acc['address']) ?>',<?= $acc['sub_id'] ?>)"
                                    class="px-4 py-2 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition whitespace-nowrap">변경</button>
                            <button onclick="this.closest('.pw-form').classList.add('hidden')"
                                    class="px-3 py-2 text-xs text-zinc-400 hover:text-zinc-600">취소</button>
                        </div>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>

<script>
function togglePwForm(btn) {
    var form = btn.closest('.bg-gray-50, .dark\\:bg-zinc-700\\/30').querySelector('.pw-form')
             || btn.closest('div[class*="rounded-lg"]').querySelector('.pw-form');
    if (form) { form.classList.toggle('hidden'); if (!form.classList.contains('hidden')) form.querySelector('.pw-new').focus(); }
}
function changePw(btn, address, subId) {
    var form = btn.closest('.pw-form');
    var pw = form.querySelector('.pw-new').value;
    var confirm = form.querySelector('.pw-confirm').value;
    if (!pw || pw.length < 8) { alert('비밀번호는 8자 이상이어야 합니다.'); return; }
    if (pw !== confirm) { alert('비밀번호가 일치하지 않습니다.'); return; }
    btn.disabled = true; btn.textContent = '처리중...';
    serviceAction('change_mail_password', { subscription_id: subId, address: address, password: pw })
        .then(function(d) {
            btn.disabled = false; btn.textContent = '변경';
            if (d.success) { alert('비밀번호가 변경되었습니다.'); form.classList.add('hidden'); form.querySelector('.pw-new').value = ''; form.querySelector('.pw-confirm').value = ''; }
            else alert(d.message || '변경에 실패했습니다.');
        });
}
</script>
