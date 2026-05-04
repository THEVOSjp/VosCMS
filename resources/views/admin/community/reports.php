<?php
/**
 * 어드민 - 신고 검토 페이지
 * /{ADMIN_PATH}/community/reports
 *
 * 입력 변수: $pdo, $config, $baseUrl, $adminPath
 */

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__, 4));
require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
use RzxLib\Core\Auth\Auth;
if (!Auth::check() || !in_array(Auth::user()['role'] ?? '', ['admin','supervisor','owner'], true)) {
    http_response_code(403); echo '권한 없음'; return;
}
$adminUser = Auth::user();
$baseUrl = $config['app_url'] ?? '';
$adminPath = $config['admin_path'] ?? 'theadmin';
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

// 통계
$statsSt = $pdo->query("SELECT
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) AS pending,
    SUM(CASE WHEN status = 'reviewed' THEN 1 ELSE 0 END) AS reviewed,
    SUM(CASE WHEN status = 'dismissed' THEN 1 ELSE 0 END) AS dismissed,
    SUM(CASE WHEN status = 'actioned' THEN 1 ELSE 0 END) AS actioned,
    COUNT(*) AS total
    FROM {$prefix}message_reports");
$stats = $statsSt->fetch(PDO::FETCH_ASSOC) ?: [];
?>
<div class="p-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">신고 검토</h1>
            <p class="text-sm text-zinc-500 mt-1">사용자 간 신고 내역을 검토하고 조치합니다.</p>
        </div>
    </div>

    <!-- 통계 카드 -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-6">
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
            <p class="text-xs text-zinc-500">미처리</p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1"><?= (int)($stats['pending'] ?? 0) ?></p>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
            <p class="text-xs text-zinc-500">검토됨</p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1"><?= (int)($stats['reviewed'] ?? 0) ?></p>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
            <p class="text-xs text-zinc-500">기각</p>
            <p class="text-2xl font-bold text-zinc-600 dark:text-zinc-400 mt-1"><?= (int)($stats['dismissed'] ?? 0) ?></p>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
            <p class="text-xs text-zinc-500">조치 완료</p>
            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1"><?= (int)($stats['actioned'] ?? 0) ?></p>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
            <p class="text-xs text-zinc-500">전체</p>
            <p class="text-2xl font-bold text-zinc-900 dark:text-white mt-1"><?= (int)($stats['total'] ?? 0) ?></p>
        </div>
    </div>

    <!-- 필터 탭 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="border-b border-zinc-200 dark:border-zinc-700 flex">
            <?php foreach ([
                'pending' => '미처리',
                'reviewed' => '검토됨',
                'dismissed' => '기각',
                'actioned' => '조치 완료',
                'all' => '전체',
            ] as $k => $label): ?>
            <button type="button" onclick="setFilter('<?= $k ?>')" data-filter="<?= $k ?>"
                class="filter-tab px-5 py-3 text-sm font-medium border-b-2 transition <?= $k === 'pending' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-zinc-500 hover:text-zinc-700' ?>">
                <?= $label ?>
            </button>
            <?php endforeach; ?>
        </div>
        <div id="reportList" class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
            <div class="p-12 text-center text-sm text-zinc-400">로딩 중...</div>
        </div>
    </div>
</div>

<!-- 처리 모달 -->
<div id="resolveModal" class="hidden fixed inset-0 z-[9000] flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl w-full max-w-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-zinc-900 dark:text-white">신고 처리</h3>
            <button type="button" onclick="closeResolveModal()" class="text-zinc-400 hover:text-zinc-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div id="resolveSummary" class="bg-zinc-50 dark:bg-zinc-700/30 rounded-lg p-3 mb-4 text-xs"></div>

        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-2">처리 결과</label>
        <div class="space-y-2 mb-4">
            <label class="flex items-start gap-2 p-2 border rounded-lg cursor-pointer has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-900/20 border-zinc-200 dark:border-zinc-700">
                <input type="radio" name="resolveStatus" value="dismissed" class="mt-1">
                <div>
                    <p class="text-sm font-medium">기각 (문제 없음)</p>
                    <p class="text-[11px] text-zinc-500">신고가 부적절하거나 무근거.</p>
                </div>
            </label>
            <label class="flex items-start gap-2 p-2 border rounded-lg cursor-pointer has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-900/20 border-zinc-200 dark:border-zinc-700">
                <input type="radio" name="resolveStatus" value="reviewed" class="mt-1">
                <div>
                    <p class="text-sm font-medium">검토됨 (지속 관찰)</p>
                    <p class="text-[11px] text-zinc-500">기록만, 별도 조치 없음.</p>
                </div>
            </label>
            <label class="flex items-start gap-2 p-2 border rounded-lg cursor-pointer has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50 dark:has-[:checked]:bg-amber-900/20 border-zinc-200 dark:border-zinc-700">
                <input type="radio" name="resolveStatus" value="actioned" class="mt-1">
                <div>
                    <p class="text-sm font-medium">조치 (제재 적용)</p>
                    <div class="mt-2 space-y-1.5">
                        <label class="flex items-center gap-2 text-xs">
                            <input type="radio" name="actionTaken" value="">
                            <span>로그만</span>
                        </label>
                        <label class="flex items-center gap-2 text-xs">
                            <input type="radio" name="actionTaken" value="pause_messages">
                            <span class="text-amber-700 dark:text-amber-400">대상 사용자 메시지 24시간 차단</span>
                        </label>
                        <label class="flex items-center gap-2 text-xs">
                            <input type="radio" name="actionTaken" value="suspend_user">
                            <span class="text-red-700 dark:text-red-400">대상 계정 비활성화 (강제 로그아웃)</span>
                        </label>
                    </div>
                </div>
            </label>
        </div>

        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-2">어드민 메모</label>
        <textarea id="resolveNote" rows="3" maxlength="2000" placeholder="처리 사유, 관련 사실 등을 기록..." class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 dark:text-white resize-none mb-4"></textarea>

        <div class="flex justify-end gap-2">
            <button type="button" onclick="closeResolveModal()" class="px-4 py-2 text-xs text-zinc-500 hover:text-zinc-700">취소</button>
            <button type="button" id="resolveSubmitBtn" onclick="submitResolve()" class="px-4 py-2 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">처리</button>
        </div>
    </div>
</div>

<script>
(function(){
    var BASE = <?= json_encode($baseUrl) ?>;
    var currentFilter = 'pending';
    var currentReports = [];
    var currentResolveId = null;

    function escHtml(s) { return (s || '').replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }

    var REASON_LABEL = {
        spam: '스팸/도배', harassment: '괴롭힘/혐오', inappropriate: '부적절', other: '기타'
    };
    var STATUS_LABEL = {
        pending: ['미처리', 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'],
        reviewed: ['검토됨', 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'],
        dismissed: ['기각', 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300'],
        actioned: ['조치', 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'],
    };

    function loadReports() {
        var list = document.getElementById('reportList');
        list.innerHTML = '<div class="p-12 text-center text-sm text-zinc-400">로딩 중...</div>';
        fetch(BASE + '/api/blocks.php?action=admin_list_reports&status=' + currentFilter, {credentials:'same-origin'})
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (!d || !d.success) {
                    list.innerHTML = '<div class="p-12 text-center text-sm text-red-500">' + (d.message || '로드 실패') + '</div>';
                    return;
                }
                currentReports = d.reports || [];
                if (!currentReports.length) {
                    list.innerHTML = '<div class="p-12 text-center text-sm text-zinc-400">신고 내역이 없습니다.</div>';
                    return;
                }
                list.innerHTML = currentReports.map(function(r){
                    var st = STATUS_LABEL[r.status] || ['?', 'bg-zinc-100'];
                    var when = (r.created_at || '').slice(0, 16).replace('T',' ');
                    var reviewWhen = r.reviewed_at ? (r.reviewed_at.slice(0,16).replace('T',' ')) : '';
                    var totalLabel = (parseInt(r.target_total_reports,10) > 1)
                        ? '<span class="ml-2 px-1.5 py-0.5 text-[10px] bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded">동일 대상 ' + r.target_total_reports + '건</span>'
                        : '';
                    var blockLabel = (parseInt(r.target_total_blocks,10) > 0)
                        ? '<span class="ml-1 px-1.5 py-0.5 text-[10px] bg-amber-100 text-amber-700 rounded">차단 ' + r.target_total_blocks + '건</span>'
                        : '';
                    return '<div class="p-4">'
                        + '<div class="flex items-start justify-between gap-3 mb-2">'
                        +   '<div class="flex items-center gap-2 flex-wrap">'
                        +     '<span class="text-[10px] px-2 py-0.5 rounded ' + st[1] + '">' + st[0] + '</span>'
                        +     '<span class="text-xs text-zinc-700 dark:text-zinc-300 font-semibold">' + escHtml(REASON_LABEL[r.reason] || r.reason) + '</span>'
                        +     '<span class="text-[11px] text-zinc-400">' + when + '</span>'
                        +   '</div>'
                        +   (r.status === 'pending'
                            ? '<button type="button" onclick="openResolveModal(' + r.id + ')" class="px-3 py-1 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded">처리</button>'
                            : '<span class="text-[10px] text-zinc-400">' + reviewWhen + '</span>')
                        + '</div>'
                        + '<div class="grid grid-cols-2 gap-3 text-xs">'
                        +   '<div class="bg-zinc-50 dark:bg-zinc-700/30 rounded p-2">'
                        +     '<p class="text-[10px] text-zinc-400 mb-1">신고자</p>'
                        +     '<p class="text-zinc-900 dark:text-white font-medium">' + escHtml(r.reporter_display || '-') + '</p>'
                        +     '<p class="text-[10px] text-zinc-400 font-mono">#' + (r.reporter_id || '').slice(0,8) + '</p>'
                        +   '</div>'
                        +   '<div class="bg-red-50 dark:bg-red-900/10 rounded p-2 border border-red-100 dark:border-red-900/30">'
                        +     '<p class="text-[10px] text-zinc-400 mb-1">신고 대상' + totalLabel + blockLabel + '</p>'
                        +     '<p class="text-zinc-900 dark:text-white font-medium">' + escHtml(r.target_display || '-') + '</p>'
                        +     '<p class="text-[10px] text-zinc-400 font-mono">#' + (r.target_user_id || '').slice(0,8) + '</p>'
                        +   '</div>'
                        + '</div>'
                        + (r.detail
                            ? '<div class="mt-2 p-3 bg-amber-50 dark:bg-amber-900/10 rounded text-xs text-zinc-700 dark:text-zinc-300 leading-relaxed whitespace-pre-wrap border border-amber-200/40 dark:border-amber-900/30">' + escHtml(r.detail) + '</div>'
                            : '')
                        + (r.admin_note
                            ? '<div class="mt-2 p-3 bg-blue-50 dark:bg-blue-900/10 rounded text-xs text-blue-800 dark:text-blue-300"><span class="font-bold">어드민 메모:</span> ' + escHtml(r.admin_note) + '</div>'
                            : '')
                        + '</div>';
                }).join('');
            }).catch(function(){
                list.innerHTML = '<div class="p-12 text-center text-sm text-red-500">네트워크 오류</div>';
            });
    }

    window.setFilter = function(f) {
        currentFilter = f;
        document.querySelectorAll('.filter-tab').forEach(function(b){
            b.classList.remove('border-blue-600','text-blue-600','dark:text-blue-400');
            b.classList.add('border-transparent','text-zinc-500');
        });
        var act = document.querySelector('.filter-tab[data-filter="' + f + '"]');
        if (act) {
            act.classList.add('border-blue-600','text-blue-600','dark:text-blue-400');
            act.classList.remove('border-transparent','text-zinc-500');
        }
        loadReports();
    };

    window.openResolveModal = function(reportId) {
        currentResolveId = reportId;
        var r = currentReports.find(function(x){ return x.id == reportId; });
        if (!r) return;
        document.getElementById('resolveSummary').innerHTML =
            '<p><b>대상:</b> ' + escHtml(r.target_display) + ' (#' + (r.target_user_id||'').slice(0,8) + ')</p>'
          + '<p class="mt-1"><b>사유:</b> ' + escHtml(REASON_LABEL[r.reason] || r.reason) + '</p>'
          + (r.detail ? '<p class="mt-1 text-zinc-500">' + escHtml(r.detail) + '</p>' : '');
        document.querySelectorAll('input[name="resolveStatus"]').forEach(function(i){ i.checked = false; });
        document.querySelectorAll('input[name="actionTaken"]').forEach(function(i){ i.checked = false; });
        document.getElementById('resolveNote').value = '';
        document.getElementById('resolveModal').classList.remove('hidden');
    };
    window.closeResolveModal = function() {
        document.getElementById('resolveModal').classList.add('hidden');
        currentResolveId = null;
    };
    window.submitResolve = function() {
        if (!currentResolveId) return;
        var status = document.querySelector('input[name="resolveStatus"]:checked');
        if (!status) { alert('처리 결과를 선택하세요'); return; }
        var actionTaken = document.querySelector('input[name="actionTaken"]:checked');
        var note = document.getElementById('resolveNote').value.trim();
        var btn = document.getElementById('resolveSubmitBtn');
        btn.disabled = true;
        var fd = new FormData();
        fd.append('action', 'admin_resolve_report');
        fd.append('report_id', currentResolveId);
        fd.append('status', status.value);
        if (actionTaken && actionTaken.value) fd.append('action_taken', actionTaken.value);
        if (note) fd.append('admin_note', note);
        fetch(BASE + '/api/blocks.php', {method:'POST', body: fd, credentials:'same-origin'})
            .then(function(r){ return r.json(); })
            .then(function(d){
                btn.disabled = false;
                if (!d.success) { alert(d.message || '처리 실패'); return; }
                closeResolveModal();
                loadReports();
            }).catch(function(){
                btn.disabled = false;
                alert('네트워크 오류');
            });
    };

    loadReports();
})();
</script>
