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
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
$basePath = parse_url($baseUrl, PHP_URL_PATH) ?: '';
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$pageTitle = __('community.reports.title') . ' - ' . ($config['app_name'] ?? 'VosCMS') . ' Admin';

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
<!DOCTYPE html>
<html lang="<?= htmlspecialchars($config['locale'] ?? 'ko') ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($pageTitle) ?></title>
    <?php include BASE_PATH . '/resources/views/admin/partials/pwa-head.php'; ?>
    <script src="https://cdn.tailwindcss.com"></script>
    <script>tailwind.config = { darkMode: 'class' }</script>
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">
    <style>body { font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; }</style>
    <script>
        if (localStorage.getItem('darkMode') === 'true' ||
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>
</head>
<body class="bg-zinc-100 dark:bg-zinc-900 min-h-screen transition-colors">
<div class="flex">
    <?php include BASE_PATH . '/resources/views/admin/partials/admin-sidebar.php'; ?>
    <main class="flex-1 ml-64">
        <?php
        $pageHeaderTitle = __('community.reports.title');
        include BASE_PATH . '/resources/views/admin/partials/admin-topbar.php';
        ?>
<div class="p-6">
    <div class="mb-6 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars(__('community.reports.title')) ?></h1>
            <p class="text-sm text-zinc-500 mt-1"><?= htmlspecialchars(__('community.reports.description')) ?></p>
        </div>
    </div>

    <!-- 통계 카드 -->
    <div class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-6">
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
            <p class="text-xs text-zinc-500"><?= htmlspecialchars(__('community.reports.stat_pending')) ?></p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1"><?= (int)($stats['pending'] ?? 0) ?></p>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
            <p class="text-xs text-zinc-500"><?= htmlspecialchars(__('community.reports.stat_reviewed')) ?></p>
            <p class="text-2xl font-bold text-blue-600 dark:text-blue-400 mt-1"><?= (int)($stats['reviewed'] ?? 0) ?></p>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
            <p class="text-xs text-zinc-500"><?= htmlspecialchars(__('community.reports.stat_dismissed')) ?></p>
            <p class="text-2xl font-bold text-zinc-600 dark:text-zinc-400 mt-1"><?= (int)($stats['dismissed'] ?? 0) ?></p>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
            <p class="text-xs text-zinc-500"><?= htmlspecialchars(__('community.reports.stat_actioned')) ?></p>
            <p class="text-2xl font-bold text-amber-600 dark:text-amber-400 mt-1"><?= (int)($stats['actioned'] ?? 0) ?></p>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">
            <p class="text-xs text-zinc-500"><?= htmlspecialchars(__('community.reports.stat_total')) ?></p>
            <p class="text-2xl font-bold text-zinc-900 dark:text-white mt-1"><?= (int)($stats['total'] ?? 0) ?></p>
        </div>
    </div>

    <!-- 필터 탭 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="border-b border-zinc-200 dark:border-zinc-700 flex">
            <?php foreach ([
                'pending'   => __('community.reports.tab_pending'),
                'reviewed'  => __('community.reports.tab_reviewed'),
                'dismissed' => __('community.reports.tab_dismissed'),
                'actioned'  => __('community.reports.tab_actioned'),
                'all'       => __('community.reports.tab_all'),
            ] as $k => $label): ?>
            <button type="button" onclick="setFilter('<?= $k ?>')" data-filter="<?= $k ?>"
                class="filter-tab px-5 py-3 text-sm font-medium border-b-2 transition <?= $k === 'pending' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-zinc-500 hover:text-zinc-700' ?>">
                <?= htmlspecialchars($label) ?>
            </button>
            <?php endforeach; ?>
        </div>
        <div id="reportList" class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
            <div class="p-12 text-center text-sm text-zinc-400"><?= htmlspecialchars(__('community.reports.loading')) ?></div>
        </div>
    </div>
</div>

<!-- 처리 모달 -->
<div id="resolveModal" class="hidden fixed inset-0 z-[9000] flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl w-full max-w-lg p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars(__('community.reports.modal_title')) ?></h3>
            <button type="button" onclick="closeResolveModal()" class="text-zinc-400 hover:text-zinc-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div id="resolveSummary" class="bg-zinc-50 dark:bg-zinc-700/30 rounded-lg p-3 mb-4 text-xs"></div>

        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-2"><?= htmlspecialchars(__('community.reports.modal_status_label')) ?></label>
        <div class="space-y-2 mb-4">
            <label class="flex items-start gap-2 p-2 border rounded-lg cursor-pointer has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-900/20 border-zinc-200 dark:border-zinc-700">
                <input type="radio" name="resolveStatus" value="dismissed" class="mt-1">
                <div>
                    <p class="text-sm font-medium"><?= htmlspecialchars(__('community.reports.opt_dismissed_title')) ?></p>
                    <p class="text-[11px] text-zinc-500"><?= htmlspecialchars(__('community.reports.opt_dismissed_desc')) ?></p>
                </div>
            </label>
            <label class="flex items-start gap-2 p-2 border rounded-lg cursor-pointer has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-900/20 border-zinc-200 dark:border-zinc-700">
                <input type="radio" name="resolveStatus" value="reviewed" class="mt-1">
                <div>
                    <p class="text-sm font-medium"><?= htmlspecialchars(__('community.reports.opt_reviewed_title')) ?></p>
                    <p class="text-[11px] text-zinc-500"><?= htmlspecialchars(__('community.reports.opt_reviewed_desc')) ?></p>
                </div>
            </label>
            <label class="flex items-start gap-2 p-2 border rounded-lg cursor-pointer has-[:checked]:border-amber-500 has-[:checked]:bg-amber-50 dark:has-[:checked]:bg-amber-900/20 border-zinc-200 dark:border-zinc-700">
                <input type="radio" name="resolveStatus" value="actioned" class="mt-1">
                <div>
                    <p class="text-sm font-medium"><?= htmlspecialchars(__('community.reports.opt_actioned_title')) ?></p>
                    <div class="mt-2 space-y-1.5">
                        <label class="flex items-center gap-2 text-xs">
                            <input type="radio" name="actionTaken" value="">
                            <span><?= htmlspecialchars(__('community.reports.action_none')) ?></span>
                        </label>
                        <label class="flex items-center gap-2 text-xs">
                            <input type="radio" name="actionTaken" value="pause_messages">
                            <span class="text-amber-700 dark:text-amber-400"><?= htmlspecialchars(__('community.reports.action_pause')) ?></span>
                        </label>
                        <label class="flex items-center gap-2 text-xs">
                            <input type="radio" name="actionTaken" value="suspend_user">
                            <span class="text-red-700 dark:text-red-400"><?= htmlspecialchars(__('community.reports.action_suspend')) ?></span>
                        </label>
                    </div>
                </div>
            </label>
        </div>

        <label class="block text-xs font-bold text-zinc-700 dark:text-zinc-300 mb-2"><?= htmlspecialchars(__('community.reports.admin_note_label')) ?></label>
        <textarea id="resolveNote" rows="3" maxlength="2000" placeholder="<?= htmlspecialchars(__('community.reports.note_placeholder')) ?>" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 dark:text-white resize-none mb-4"></textarea>

        <div class="flex justify-end gap-2">
            <button type="button" onclick="closeResolveModal()" class="px-4 py-2 text-xs text-zinc-500 hover:text-zinc-700"><?= htmlspecialchars(__('community.reports.btn_cancel')) ?></button>
            <button type="button" id="resolveSubmitBtn" onclick="submitResolve()" class="px-4 py-2 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700"><?= htmlspecialchars(__('community.reports.btn_submit')) ?></button>
        </div>
    </div>
</div>

<script>
(function(){
    var BASE = <?= json_encode($baseUrl) ?>;
    var currentFilter = 'pending';
    var currentReports = [];
    var currentResolveId = null;

    var I18N = <?= json_encode([
        'reason_spam' => __('community.reports.reason_spam'),
        'reason_harassment' => __('community.reports.reason_harassment'),
        'reason_inappropriate' => __('community.reports.reason_inappropriate'),
        'reason_other' => __('community.reports.reason_other'),
        'status_pending' => __('community.reports.status_pending'),
        'status_reviewed' => __('community.reports.status_reviewed'),
        'status_dismissed' => __('community.reports.status_dismissed'),
        'status_actioned' => __('community.reports.status_actioned'),
        'btn_resolve' => __('community.reports.btn_resolve'),
        'empty' => __('community.reports.empty'),
        'loading' => __('community.reports.loading'),
        'load_failed' => __('community.reports.load_failed'),
        'network_error' => __('community.reports.network_error'),
        'modal_target' => __('community.reports.modal_target'),
        'modal_reason' => __('community.reports.modal_reason'),
        'admin_note_prefix' => __('community.reports.admin_note_prefix'),
        'alert_select_status' => __('community.reports.alert_select_status'),
        'alert_resolve_failed' => __('community.reports.alert_resolve_failed'),
        'target_total_reports' => __('community.reports.target_total_reports', ['count' => '__N__']),
        'target_total_blocks' => __('community.reports.target_total_blocks', ['count' => '__N__']),
    ], JSON_UNESCAPED_UNICODE) ?>;

    function escHtml(s) { return (s || '').replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }

    var REASON_LABEL = {
        spam: I18N.reason_spam,
        harassment: I18N.reason_harassment,
        inappropriate: I18N.reason_inappropriate,
        other: I18N.reason_other,
    };
    var STATUS_LABEL = {
        pending:   [I18N.status_pending,   'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'],
        reviewed:  [I18N.status_reviewed,  'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'],
        dismissed: [I18N.status_dismissed, 'bg-zinc-100 text-zinc-700 dark:bg-zinc-700 dark:text-zinc-300'],
        actioned:  [I18N.status_actioned,  'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'],
    };

    function loadReports() {
        var list = document.getElementById('reportList');
        list.innerHTML = '<div class="p-12 text-center text-sm text-zinc-400">' + escHtml(I18N.loading) + '</div>';
        fetch(BASE + '/api/blocks.php?action=admin_list_reports&status=' + currentFilter, {credentials:'same-origin'})
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (!d || !d.success) {
                    list.innerHTML = '<div class="p-12 text-center text-sm text-red-500">' + escHtml(d.message || I18N.load_failed) + '</div>';
                    return;
                }
                currentReports = d.reports || [];
                if (!currentReports.length) {
                    list.innerHTML = '<div class="p-12 text-center text-sm text-zinc-400">' + escHtml(I18N.empty) + '</div>';
                    return;
                }
                list.innerHTML = currentReports.map(function(r){
                    var st = STATUS_LABEL[r.status] || ['?', 'bg-zinc-100'];
                    var when = (r.created_at || '').slice(0, 16).replace('T',' ');
                    var reviewWhen = r.reviewed_at ? (r.reviewed_at.slice(0,16).replace('T',' ')) : '';
                    var totalLabel = (parseInt(r.target_total_reports,10) > 1)
                        ? '<span class="ml-2 px-1.5 py-0.5 text-[10px] bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 rounded">' + escHtml(I18N.target_total_reports.replace('__N__', r.target_total_reports)) + '</span>'
                        : '';
                    var blockLabel = (parseInt(r.target_total_blocks,10) > 0)
                        ? '<span class="ml-1 px-1.5 py-0.5 text-[10px] bg-amber-100 text-amber-700 rounded">' + escHtml(I18N.target_total_blocks.replace('__N__', r.target_total_blocks)) + '</span>'
                        : '';
                    return '<div class="p-4">'
                        + '<div class="flex items-start justify-between gap-3 mb-2">'
                        +   '<div class="flex items-center gap-2 flex-wrap">'
                        +     '<span class="text-[10px] px-2 py-0.5 rounded ' + st[1] + '">' + escHtml(st[0]) + '</span>'
                        +     '<span class="text-xs text-zinc-700 dark:text-zinc-300 font-semibold">' + escHtml(REASON_LABEL[r.reason] || r.reason) + '</span>'
                        +     '<span class="text-[11px] text-zinc-400">' + when + '</span>'
                        +   '</div>'
                        +   (r.status === 'pending'
                            ? '<button type="button" onclick="openResolveModal(' + r.id + ')" class="px-3 py-1 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded">' + escHtml(I18N.btn_resolve) + '</button>'
                            : '<span class="text-[10px] text-zinc-400">' + reviewWhen + '</span>')
                        + '</div>'
                        + '<div class="grid grid-cols-2 gap-3 text-xs">'
                        +   '<div class="bg-zinc-50 dark:bg-zinc-700/30 rounded p-2">'
                        +     '<p class="text-[10px] text-zinc-400 mb-1">' + escHtml(<?= json_encode(__('community.reports.reporter')) ?>) + '</p>'
                        +     '<p class="text-zinc-900 dark:text-white font-medium">' + escHtml(r.reporter_display || '-') + '</p>'
                        +     '<p class="text-[10px] text-zinc-400 font-mono">#' + (r.reporter_id || '').slice(0,8) + '</p>'
                        +   '</div>'
                        +   '<div class="bg-red-50 dark:bg-red-900/10 rounded p-2 border border-red-100 dark:border-red-900/30">'
                        +     '<p class="text-[10px] text-zinc-400 mb-1">' + escHtml(<?= json_encode(__('community.reports.target')) ?>) + totalLabel + blockLabel + '</p>'
                        +     '<p class="text-zinc-900 dark:text-white font-medium">' + escHtml(r.target_display || '-') + '</p>'
                        +     '<p class="text-[10px] text-zinc-400 font-mono">#' + (r.target_user_id || '').slice(0,8) + '</p>'
                        +   '</div>'
                        + '</div>'
                        + (r.detail
                            ? '<div class="mt-2 p-3 bg-amber-50 dark:bg-amber-900/10 rounded text-xs text-zinc-700 dark:text-zinc-300 leading-relaxed whitespace-pre-wrap border border-amber-200/40 dark:border-amber-900/30">' + escHtml(r.detail) + '</div>'
                            : '')
                        + (r.admin_note
                            ? '<div class="mt-2 p-3 bg-blue-50 dark:bg-blue-900/10 rounded text-xs text-blue-800 dark:text-blue-300"><span class="font-bold">' + escHtml(I18N.admin_note_prefix) + '</span> ' + escHtml(r.admin_note) + '</div>'
                            : '')
                        + '</div>';
                }).join('');
            }).catch(function(){
                list.innerHTML = '<div class="p-12 text-center text-sm text-red-500">' + escHtml(I18N.network_error) + '</div>';
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
            '<p><b>' + escHtml(I18N.modal_target) + ':</b> ' + escHtml(r.target_display) + ' (#' + (r.target_user_id||'').slice(0,8) + ')</p>'
          + '<p class="mt-1"><b>' + escHtml(I18N.modal_reason) + ':</b> ' + escHtml(REASON_LABEL[r.reason] || r.reason) + '</p>'
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
        if (!status) { alert(I18N.alert_select_status); return; }
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
                if (!d.success) { alert(d.message || I18N.alert_resolve_failed); return; }
                closeResolveModal();
                loadReports();
            }).catch(function(){
                btn.disabled = false;
                alert(I18N.network_error);
            });
    };

    loadReports();
})();
</script>
    </main>
</div>
<?php include BASE_PATH . '/resources/views/admin/partials/pwa-scripts.php'; ?>
</body>
</html>
