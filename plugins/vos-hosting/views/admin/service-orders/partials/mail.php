<?php
/**
 * 관리자 서비스 상세 — 메일 탭
 * $subs: mail 타입 구독 배열
 * 신청서 등록 시 'mail' 타입 subscription 자동 생성됨 (admin_create_order)
 * 어드민이 여기서 메일 계정 추가/삭제/비번 변경 가능 (고객 페이지와 동일 기능)
 */

// 호스팅 sub 가 있으면 활성화 가능 (mail sub 가 비어있어도 추가 가능하게)
$_hSub = $servicesByType['hosting'][0] ?? null;
$_primaryDomain = $order['domain'] ?? '';
$_pendingTitle  = __('services.admin_orders.btn_pending');

// 기본 메일 sub / 비즈니스 메일 sub 분리
$basicSub = null;
$bizSub = null;
foreach ($subs as $sub) {
    if (($sub['type'] ?? '') !== 'mail') continue;
    $isBiz = stripos($sub['label'], '비즈니스') !== false || stripos($sub['label'], 'ビジネス') !== false || stripos($sub['label'], 'Business') !== false;
    if ($isBiz) $bizSub = $sub;
    else        $basicSub = $sub;
}

// 모든 메일 계정 수집 (탭 표시용)
$allAccounts = [];
foreach ($subs as $sub) {
    if (($sub['type'] ?? '') !== 'mail') continue;
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

// 메일서버 정보
$firstMeta = !empty($subs) ? (json_decode($subs[0]['metadata'] ?? '{}', true) ?: []) : [];
$mailServer = $firstMeta['mail_server'] ?? [];
?>

<!-- 메일서버 정보 -->
<div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700">
    <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider mb-2"><?= htmlspecialchars(__('services.detail.mail_settings')) ?></p>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
        <table class="text-xs">
            <tbody class="divide-y divide-gray-100 dark:divide-zinc-700/50">
                <tr><td class="py-1.5 text-zinc-400 w-28"><?= htmlspecialchars(__('services.detail.mail_imap_host')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailServer['imap_host'] ?? 'mail.voscms.com') ?></td></tr>
                <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.detail.mail_imap_port')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailServer['imap_port'] ?? '993') ?></td></tr>
                <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.detail.mail_smtp_host')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailServer['smtp_host'] ?? 'mail.voscms.com') ?></td></tr>
                <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.detail.mail_smtp_port')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailServer['smtp_port'] ?? '587') ?></td></tr>
            </tbody>
        </table>
        <table class="text-xs">
            <tbody class="divide-y divide-gray-100 dark:divide-zinc-700/50">
                <tr><td class="py-1.5 text-zinc-400 w-28"><?= htmlspecialchars(__('services.detail.mail_encryption')) ?></td><td class="py-1.5 text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailServer['encryption'] ?? 'SSL/TLS') ?></td></tr>
                <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.detail.mail_webmail')) ?></td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailServer['webmail_url'] ?? 'https://mail.voscms.com') ?></td></tr>
                <tr><td class="py-1.5 text-zinc-400"><?= htmlspecialchars(__('services.detail.mail_user_account')) ?></td><td class="py-1.5 text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars(__('services.detail.mail_account_format')) ?></td></tr>
                <tr><td class="py-1.5 text-zinc-400">기준 도메인</td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($_primaryDomain ?: '-') ?></td></tr>
            </tbody>
        </table>
    </div>
</div>

<!-- 메일 계정 목록 -->
<div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700">
    <div class="flex items-center justify-between mb-3">
        <p class="text-[10px] font-bold text-zinc-500 uppercase tracking-wider">
            <?= htmlspecialchars(__('services.detail.mail_accounts')) ?> (<?= count($allAccounts) ?>)
        </p>
        <div class="flex items-center gap-2">
            <?php if ($basicSub): ?>
            <button type="button" onclick="openAddMailModal('basic', <?= (int)$basicSub['id'] ?>)" class="text-[10px] px-2 py-1 text-emerald-600 border border-emerald-200 rounded hover:bg-emerald-50">+ 기본 메일</button>
            <?php endif; ?>
            <?php if ($bizSub): ?>
            <button type="button" onclick="openAddMailModal('biz', <?= (int)$bizSub['id'] ?>)" class="text-[10px] px-2 py-1 text-amber-600 border border-amber-200 rounded hover:bg-amber-50">+ 비즈니스 메일</button>
            <?php else: ?>
            <button type="button" onclick="openAddBizMailSubModal()" class="text-[10px] px-2 py-1 text-amber-600 border border-amber-200 rounded hover:bg-amber-50">+ 비즈니스 메일 (구독 추가)</button>
            <?php endif; ?>
        </div>
    </div>
    <div id="mailAccountList" class="space-y-1">
        <?php if (empty($allAccounts)): ?>
        <p class="text-xs text-zinc-400 py-4 text-center">메일 계정이 없습니다. 위 버튼으로 추가하세요.</p>
        <?php else: foreach ($allAccounts as $i => $acc): ?>
        <div class="flex items-center gap-3 px-3 py-2 bg-gray-50 dark:bg-zinc-700/30 rounded text-xs">
            <span class="text-zinc-400 w-4"><?= $i + 1 ?></span>
            <span class="font-mono font-medium text-zinc-800 dark:text-zinc-200 flex-1"><?= htmlspecialchars($acc['address']) ?></span>
            <span class="text-[10px] px-1.5 py-0.5 bg-<?= $acc['type_color'] ?>-50 text-<?= $acc['type_color'] ?>-600 rounded"><?= htmlspecialchars($acc['type']) ?></span>
            <span class="text-zinc-400 w-24"><?= $acc['has_password'] ? htmlspecialchars(__('services.admin_orders.mail_pw_set')) : htmlspecialchars(__('services.admin_orders.mail_pw_unset_short')) ?></span>
            <button type="button" onclick="changeMailPassword(<?= (int)$acc['sub_id'] ?>, '<?= htmlspecialchars($acc['address'], ENT_QUOTES) ?>')" class="text-[10px] px-2 py-1 text-blue-600 border border-blue-200 rounded hover:bg-blue-50"><?= htmlspecialchars(__('services.detail.btn_change_pw')) ?></button>
            <button type="button" onclick="deleteMailAccount(<?= (int)$acc['sub_id'] ?>, '<?= htmlspecialchars($acc['address'], ENT_QUOTES) ?>')" class="text-[10px] px-2 py-1 text-red-600 border border-red-200 rounded hover:bg-red-50"><?= htmlspecialchars(__('services.admin_orders.btn_remove')) ?></button>
        </div>
        <?php endforeach; endif; ?>
    </div>
</div>

<!-- 메일 추가 모달 -->
<div id="addMailModal" class="hidden fixed inset-0 z-50 bg-black/40 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-xl max-w-md w-full p-5">
        <h3 class="text-sm font-bold text-zinc-800 dark:text-white mb-4" id="addMailTitle">메일 계정 추가</h3>
        <div class="space-y-3">
            <div>
                <label class="text-[11px] text-zinc-500 dark:text-zinc-400 block mb-1">이메일 주소 <span class="text-red-500">*</span></label>
                <div class="flex">
                    <input type="text" id="newMailLocal" placeholder="user" class="flex-1 px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-l-lg focus:ring-2 focus:ring-blue-500">
                    <span class="px-3 py-2 text-sm bg-zinc-100 dark:bg-zinc-600 text-zinc-600 dark:text-zinc-300 border border-l-0 border-zinc-300 dark:border-zinc-600 rounded-r-lg" id="newMailDomainLabel">@<?= htmlspecialchars($_primaryDomain) ?></span>
                </div>
            </div>
            <div>
                <label class="text-[11px] text-zinc-500 dark:text-zinc-400 block mb-1">초기 비밀번호 <span class="text-red-500">*</span></label>
                <input type="text" id="newMailPw" placeholder="최소 8자, 대소문자+숫자 권장" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">
                <p class="text-[10px] text-amber-600 mt-1">고객에게 안전한 채널로 별도 전달하세요.</p>
            </div>
        </div>
        <div class="flex justify-end gap-2 mt-5">
            <button type="button" onclick="closeMailModal()" class="px-3 py-1.5 text-xs text-zinc-500 hover:text-zinc-700">취소</button>
            <button type="button" onclick="submitAddMail()" id="addMailSubmitBtn" class="px-4 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">추가</button>
        </div>
    </div>
</div>

<!-- 관리 버튼 -->
<div class="px-5 py-4 flex flex-wrap gap-2">
    <button disabled class="px-3 py-1.5 text-xs text-zinc-400 border border-zinc-200 rounded-lg cursor-not-allowed opacity-50" title="<?= htmlspecialchars($_pendingTitle) ?>"><?= htmlspecialchars(__('services.admin_orders.btn_send_mail_setup')) ?></button>
    <button disabled class="px-3 py-1.5 text-xs text-zinc-400 border border-zinc-200 rounded-lg cursor-not-allowed opacity-50" title="<?= htmlspecialchars($_pendingTitle) ?>"><?= htmlspecialchars(__('services.admin_orders.btn_spam_filter')) ?></button>
</div>

<script>
var _addMailContext = { kind: 'basic', subId: null };
function openAddMailModal(kind, subId) {
    _addMailContext = { kind: kind, subId: subId };
    document.getElementById('addMailTitle').textContent = (kind === 'biz' ? '비즈니스' : '기본') + ' 메일 계정 추가';
    document.getElementById('newMailLocal').value = '';
    document.getElementById('newMailPw').value = '';
    document.getElementById('addMailModal').classList.remove('hidden');
}
function closeMailModal() {
    document.getElementById('addMailModal').classList.add('hidden');
}
function submitAddMail() {
    var local = (document.getElementById('newMailLocal').value || '').trim().toLowerCase();
    var pw = document.getElementById('newMailPw').value || '';
    if (!/^[a-z0-9._-]{2,32}$/.test(local)) { alert('이메일 로컬 부분 형식 확인 (영문 소문자/숫자/._-, 2~32자)'); return; }
    if (pw.length < 8) { alert('비밀번호 최소 8자'); return; }
    var btn = document.getElementById('addMailSubmitBtn');
    btn.disabled = true; btn.textContent = '추가 중…';
    ajaxPost({
        action: 'add_mail_account',
        subscription_id: _addMailContext.subId,
        local: local,
        password: pw,
    }).then(function(d){
        btn.disabled = false; btn.textContent = '추가';
        if (d.success) { closeMailModal(); location.reload(); }
        else alert(d.message || '추가 실패');
    }).catch(function(){
        btn.disabled = false; btn.textContent = '추가';
        alert('네트워크 오류');
    });
}
function deleteMailAccount(subId, address) {
    if (!confirm(address + ' 메일을 삭제하시겠습니까? 메일함 데이터도 함께 정리됩니다.')) return;
    ajaxPost({ action: 'delete_mail_account', subscription_id: subId, address: address })
        .then(function(d){
            alert(d.message || (d.success ? '삭제 완료' : '실패'));
            if (d.success) location.reload();
        });
}
function changeMailPassword(subId, address) {
    var pw = prompt(address + ' 메일의 새 비밀번호 입력 (최소 8자):');
    if (!pw) return;
    if (pw.length < 8) { alert('비밀번호 최소 8자'); return; }
    ajaxPost({ action: 'change_mail_password', subscription_id: subId, address: address, password: pw })
        .then(function(d){
            alert(d.message || (d.success ? '비밀번호 변경 완료' : '실패'));
        });
}
function openAddBizMailSubModal() {
    if (!confirm('비즈니스 메일 구독을 새로 추가하시겠습니까?\n호스팅 만료일까지 동일 주기로 활성화됩니다.')) return;
    ajaxPost({ action: 'admin_add_bizmail_sub', order_id: orderId })
        .then(function(d){
            alert(d.message || (d.success ? '비즈니스 메일 구독 추가 완료' : '실패'));
            if (d.success) location.reload();
        });
}
</script>
