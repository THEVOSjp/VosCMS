<?php
/**
 * 어드민 - 공지 발송 페이지
 * /{ADMIN_PATH}/community/broadcast
 */
if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__, 4));
require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
use RzxLib\Core\Auth\Auth;
if (!Auth::check() || !in_array(Auth::user()['role'] ?? '', ['admin','supervisor','owner'], true)) {
    http_response_code(403); echo '권한 없음'; return;
}
$baseUrl = $config['app_url'] ?? '';
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

// 대상별 카운트 미리보기
$counts = [];
try {
    $counts['all']         = (int)$pdo->query("SELECT COUNT(*) FROM {$prefix}users WHERE is_active = 1")->fetchColumn();
    $counts['hosting']     = (int)$pdo->query("SELECT COUNT(DISTINCT user_id) FROM {$prefix}subscriptions WHERE type='hosting' AND status='active' AND user_id IS NOT NULL")->fetchColumn();
    $counts['role_admin']  = (int)$pdo->query("SELECT COUNT(*) FROM {$prefix}users WHERE is_active=1 AND role IN ('admin','supervisor','owner')")->fetchColumn();
    $counts['role_member'] = $counts['all'] - $counts['role_admin'];
} catch (\Throwable $e) { /* silent */ }
?>
<div class="p-6 max-w-3xl mx-auto">
    <div class="mb-6">
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">공지 발송</h1>
        <p class="text-sm text-zinc-500 mt-1">메시지함 알림 + (옵션) 브라우저 푸시로 일괄 발송합니다.</p>
    </div>

    <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700 p-6">
        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-2">대상</label>
        <div class="space-y-2 mb-4">
            <label class="flex items-center justify-between p-3 border border-zinc-200 dark:border-zinc-700 rounded-lg cursor-pointer has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-900/20">
                <div class="flex items-center gap-2">
                    <input type="radio" name="audience" value="all" checked>
                    <span class="text-sm font-medium">전체 회원</span>
                </div>
                <span class="text-xs text-zinc-500"><?= number_format($counts['all'] ?? 0) ?>명</span>
            </label>
            <label class="flex items-center justify-between p-3 border border-zinc-200 dark:border-zinc-700 rounded-lg cursor-pointer has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-900/20">
                <div class="flex items-center gap-2">
                    <input type="radio" name="audience" value="hosting">
                    <span class="text-sm font-medium">호스팅 활성 고객</span>
                </div>
                <span class="text-xs text-zinc-500"><?= number_format($counts['hosting'] ?? 0) ?>명</span>
            </label>
            <label class="flex items-center justify-between p-3 border border-zinc-200 dark:border-zinc-700 rounded-lg cursor-pointer has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-900/20">
                <div class="flex items-center gap-2">
                    <input type="radio" name="audience" value="role_member">
                    <span class="text-sm font-medium">일반 회원만</span>
                </div>
                <span class="text-xs text-zinc-500"><?= number_format($counts['role_member'] ?? 0) ?>명</span>
            </label>
            <label class="flex items-center justify-between p-3 border border-zinc-200 dark:border-zinc-700 rounded-lg cursor-pointer has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-900/20">
                <div class="flex items-center gap-2">
                    <input type="radio" name="audience" value="role_admin">
                    <span class="text-sm font-medium">관리자만</span>
                </div>
                <span class="text-xs text-zinc-500"><?= number_format($counts['role_admin'] ?? 0) ?>명</span>
            </label>
        </div>

        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-2">제목 <span class="text-red-500">*</span></label>
        <input type="text" id="bcTitle" maxlength="255" placeholder="[VosCMS] 점검 안내" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 dark:text-white mb-4">

        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-2">본문 <span class="text-red-500">*</span></label>
        <textarea id="bcBody" rows="6" maxlength="2000" placeholder="공지 내용을 입력하세요. 줄바꿈 그대로 표시됩니다." class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 dark:text-white resize-none mb-4"></textarea>

        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-2">링크 (선택)</label>
        <input type="text" id="bcLink" placeholder="/board/notice/123 (클릭 시 이동할 경로)" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 dark:text-white mb-4">

        <label class="flex items-center gap-2 mb-6 p-3 bg-zinc-50 dark:bg-zinc-700/30 rounded-lg cursor-pointer">
            <input type="checkbox" id="bcSendPush" checked>
            <div>
                <p class="text-sm font-medium text-zinc-900 dark:text-white">브라우저 푸시도 함께 발송</p>
                <p class="text-[11px] text-zinc-500">푸시 구독한 사용자에게만 즉시 OS 알림 표시. 메시지함은 모두 수신.</p>
            </div>
        </label>

        <div class="flex gap-2 justify-end">
            <button type="button" onclick="window.history.back()" class="px-4 py-2 text-xs text-zinc-500 hover:text-zinc-700">취소</button>
            <button type="button" id="btnSend" onclick="sendBroadcast()" class="px-6 py-2 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg">발송</button>
        </div>
    </div>

    <div id="bcResult" class="hidden mt-4 p-4 rounded-lg"></div>
</div>

<script>
(function(){
    var BASE = <?= json_encode($baseUrl) ?>;

    window.sendBroadcast = function() {
        var title = document.getElementById('bcTitle').value.trim();
        var body  = document.getElementById('bcBody').value.trim();
        var link  = document.getElementById('bcLink').value.trim();
        var audience = document.querySelector('input[name="audience"]:checked').value;
        var sendPush = document.getElementById('bcSendPush').checked;

        if (!title || !body) { alert('제목과 본문을 입력하세요.'); return; }

        var audienceLabel = {all: '전체 회원', hosting: '호스팅 활성 고객', role_member: '일반 회원', role_admin: '관리자'}[audience];
        if (!confirm(audienceLabel + '에게 공지를 발송하시겠습니까?\n\n제목: ' + title + '\n\n취소할 수 없습니다.')) return;

        var btn = document.getElementById('btnSend');
        btn.disabled = true; btn.textContent = '발송 중...';

        var fd = new FormData();
        fd.append('action', 'admin_broadcast');
        fd.append('title', title);
        fd.append('body', body);
        if (link) fd.append('link', link);
        fd.append('audience', audience);
        if (sendPush) fd.append('send_push', '1');

        fetch(BASE + '/api/push.php', {method:'POST', body: fd, credentials:'same-origin'})
            .then(function(r){ return r.json(); })
            .then(function(d){
                btn.disabled = false; btn.textContent = '발송';
                var resultEl = document.getElementById('bcResult');
                resultEl.classList.remove('hidden');
                if (!d.success) {
                    resultEl.className = 'mt-4 p-4 rounded-lg bg-red-50 dark:bg-red-900/20 text-red-700 dark:text-red-300 text-sm';
                    resultEl.textContent = '발송 실패: ' + (d.message || '알 수 없는 오류');
                    return;
                }
                resultEl.className = 'mt-4 p-4 rounded-lg bg-green-50 dark:bg-green-900/20 text-green-700 dark:text-green-300 text-sm';
                resultEl.innerHTML = '✓ 발송 완료 — 대상 ' + (d.targets || d.inserted) + '명 / 메시지함 적재 ' + d.inserted + '건' + (d.pushed > 0 ? ' / 푸시 ' + d.pushed + '건' : '');
                document.getElementById('bcTitle').value = '';
                document.getElementById('bcBody').value = '';
                document.getElementById('bcLink').value = '';
            }).catch(function(e){
                btn.disabled = false; btn.textContent = '발송';
                alert('네트워크 오류: ' + e.message);
            });
    };
})();
</script>
