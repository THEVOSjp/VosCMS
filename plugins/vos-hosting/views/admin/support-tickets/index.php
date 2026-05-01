<?php
/**
 * 관리자 — 1:1 상담 (호스팅 고객 전용 티켓 시스템)
 * 모든 고객의 메시지 시간순 통합 + 티켓별 답변
 */
if (!function_exists('__')) require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';

$_svcLangFile = BASE_PATH . '/plugins/vos-hosting/lang/' . \RzxLib\Core\I18n\Translator::getLocale() . '/services.php';
if (!file_exists($_svcLangFile)) $_svcLangFile = BASE_PATH . '/plugins/vos-hosting/lang/en/services.php';
if (file_exists($_svcLangFile)) \RzxLib\Core\I18n\Translator::merge('services', require $_svcLangFile);

$pageTitle = __('services.admin_support.page_title') . ' - ' . ($config['app_name'] ?? 'VosCMS') . ' Admin';
$pageHeaderTitle = __('services.admin_support.header_title');
$pageSubTitle = __('services.admin_support.sub_title');
$pageSubDesc = __('services.admin_support.sub_desc');

$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$pdo = \RzxLib\Core\Database\Connection::getInstance()->getPdo();
require_once BASE_PATH . '/rzxlib/Core/Helpers/Encryption.php';

// 통계
$stOpen = (int)$pdo->query("SELECT COUNT(*) FROM {$prefix}support_tickets WHERE status = 'open'")->fetchColumn();
$stUnread = (int)$pdo->query("SELECT COUNT(*) FROM {$prefix}support_tickets WHERE unread_by_admin = 1")->fetchColumn();
$stTotal = (int)$pdo->query("SELECT COUNT(*) FROM {$prefix}support_tickets")->fetchColumn();
$stClosed = (int)$pdo->query("SELECT COUNT(*) FROM {$prefix}support_tickets WHERE status = 'closed'")->fetchColumn();

// 필터
$filterStatus = $_GET['status'] ?? '';
$searchKey = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

$where = [];
$params = [];
if (in_array($filterStatus, ['open','answered','closed'], true)) {
    $where[] = "t.status = ?";
    $params[] = $filterStatus;
}
if ($searchKey !== '') {
    $where[] = "(t.title LIKE ? OR u.email LIKE ?)";
    $params[] = '%' . $searchKey . '%';
    $params[] = '%' . $searchKey . '%';
}
$whereSQL = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$cntSt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}support_tickets t LEFT JOIN {$prefix}users u ON t.user_id = u.id $whereSQL");
$cntSt->execute($params);
$totalCnt = (int)$cntSt->fetchColumn();
$totalPages = max(1, (int)ceil($totalCnt / $perPage));
$offset = ($page - 1) * $perPage;

$listSt = $pdo->prepare("SELECT t.*, u.email AS user_email, u.name AS user_name,
    (SELECT COUNT(*) FROM {$prefix}support_messages m WHERE m.ticket_id = t.id) AS msg_count
    FROM {$prefix}support_tickets t
    LEFT JOIN {$prefix}users u ON t.user_id = u.id
    $whereSQL
    ORDER BY t.last_message_at DESC, t.id DESC LIMIT $perPage OFFSET $offset");
$listSt->execute($params);
$tickets = $listSt->fetchAll(PDO::FETCH_ASSOC);

include BASE_PATH . '/resources/views/admin/reservations/_head.php';
?>

<div class="px-4 sm:px-6 lg:px-8 py-6">
    <!-- 통계 -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-gray-200 dark:border-zinc-700">
            <p class="text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1"><?= htmlspecialchars(__('services.admin_support.stats_total')) ?></p>
            <p class="text-2xl font-bold text-zinc-900 dark:text-white"><?= $stTotal ?></p>
        </div>
        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-xl p-4 border border-amber-200 dark:border-amber-800">
            <p class="text-[10px] font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider mb-1"><?= htmlspecialchars(__('services.admin_support.stats_open')) ?></p>
            <p class="text-2xl font-bold text-amber-900 dark:text-amber-300"><?= $stOpen ?></p>
        </div>
        <div class="bg-red-50 dark:bg-red-900/20 rounded-xl p-4 border border-red-200 dark:border-red-800">
            <p class="text-[10px] font-bold text-red-700 dark:text-red-400 uppercase tracking-wider mb-1"><?= htmlspecialchars(__('services.admin_support.stats_unread')) ?></p>
            <p class="text-2xl font-bold text-red-900 dark:text-red-300"><?= $stUnread ?></p>
        </div>
        <div class="bg-emerald-50 dark:bg-emerald-900/20 rounded-xl p-4 border border-emerald-200 dark:border-emerald-800">
            <p class="text-[10px] font-bold text-emerald-700 dark:text-emerald-400 uppercase tracking-wider mb-1"><?= htmlspecialchars(__('services.admin_support.stats_closed')) ?></p>
            <p class="text-2xl font-bold text-emerald-900 dark:text-emerald-300"><?= $stClosed ?></p>
        </div>
    </div>

    <!-- 필터 -->
    <form method="GET" class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-gray-200 dark:border-zinc-700 mb-4 flex flex-wrap items-center gap-2">
        <select name="status" class="px-3 py-1.5 text-xs border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg">
            <option value=""><?= htmlspecialchars(__('services.admin_support.filter_all')) ?></option>
            <option value="open" <?= $filterStatus === 'open' ? 'selected' : '' ?>><?= htmlspecialchars(__('services.detail.support_st_open')) ?></option>
            <option value="answered" <?= $filterStatus === 'answered' ? 'selected' : '' ?>><?= htmlspecialchars(__('services.detail.support_st_answered')) ?></option>
            <option value="closed" <?= $filterStatus === 'closed' ? 'selected' : '' ?>><?= htmlspecialchars(__('services.detail.support_st_closed')) ?></option>
        </select>
        <input type="text" name="q" value="<?= htmlspecialchars($searchKey) ?>" placeholder="<?= htmlspecialchars(__('services.admin_support.search_placeholder')) ?>" class="flex-1 max-w-md px-3 py-1.5 text-xs border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg">
        <button type="submit" class="px-4 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg"><?= htmlspecialchars(__('services.admin_support.btn_filter')) ?></button>
    </form>

    <!-- 티켓 목록 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700">
            <p class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider"><?= htmlspecialchars(__('services.admin_support.list_title', ['count' => $totalCnt])) ?></p>
        </div>
        <?php if (empty($tickets)): ?>
        <div class="p-12 text-center text-sm text-zinc-400"><?= htmlspecialchars(__('services.admin_support.empty')) ?></div>
        <?php else: ?>
        <div class="divide-y divide-gray-100 dark:divide-zinc-700">
            <?php foreach ($tickets as $t):
                $userName = $t['user_name'] ? (decrypt($t['user_name']) ?: $t['user_name']) : '-';
                $unread = (int)$t['unread_by_admin'] === 1;
                $statusClass = $t['status'] === 'open' ? 'bg-amber-100 text-amber-700' : ($t['status'] === 'answered' ? 'bg-emerald-100 text-emerald-700' : 'bg-gray-100 text-gray-500');
                $statusLabel = $t['status'] === 'open' ? __('services.detail.support_st_open') : ($t['status'] === 'answered' ? __('services.detail.support_st_answered') : __('services.detail.support_st_closed'));
            ?>
            <div class="p-4 hover:bg-gray-50 dark:hover:bg-zinc-700/30 cursor-pointer <?= $unread ? 'bg-red-50/40 dark:bg-red-900/10' : '' ?>" onclick="adminOpenThread(<?= (int)$t['id'] ?>)">
                <div class="flex items-center justify-between gap-3 mb-1">
                    <div class="flex items-center gap-2 flex-1 min-w-0">
                        <p class="text-sm font-medium text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($t['title']) ?></p>
                        <?php if ($unread): ?><span class="inline-block w-1.5 h-1.5 rounded-full bg-red-500 flex-shrink-0"></span><?php endif; ?>
                        <span class="text-[10px] px-2 py-0.5 rounded-full font-medium <?= $statusClass ?> whitespace-nowrap"><?= htmlspecialchars($statusLabel) ?></span>
                    </div>
                    <span class="text-[10px] text-zinc-400 whitespace-nowrap"><?= htmlspecialchars(date('Y-m-d H:i', strtotime($t['last_message_at'] ?? $t['created_at']))) ?></span>
                </div>
                <div class="flex items-center gap-3 text-[11px] text-zinc-500 dark:text-zinc-400">
                    <span><?= htmlspecialchars($userName) ?></span>
                    <span class="text-zinc-300">·</span>
                    <span><?= htmlspecialchars($t['user_email'] ?? '-') ?></span>
                    <span class="text-zinc-300">·</span>
                    <span><?= (int)$t['msg_count'] ?> <?= htmlspecialchars(__('services.admin_support.msg_count_unit')) ?></span>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
        <?php endif; ?>

        <?php if ($totalPages > 1): ?>
        <div class="px-5 py-3 border-t border-gray-100 dark:border-zinc-700 flex items-center justify-center gap-1">
            <?php for ($i = max(1, $page - 3); $i <= min($totalPages, $page + 3); $i++):
                $qs = http_build_query(array_filter(['status' => $filterStatus, 'q' => $searchKey, 'page' => $i]));
            ?>
            <a href="<?= htmlspecialchars($adminUrl) ?>/support-tickets?<?= $qs ?>" class="px-3 py-1.5 text-xs rounded-lg <?= $i === $page ? 'bg-blue-600 text-white' : 'bg-white dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 border border-gray-200 dark:border-zinc-600' ?>"><?= $i ?></a>
            <?php endfor; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<!-- 티켓 상세 (관리자용 thread + 답변 폼) -->
<div id="adminThreadModal" class="hidden fixed inset-0 z-[100] items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="adminCloseThread()"></div>
    <div class="relative bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl w-full max-w-3xl max-h-[90vh] overflow-y-auto">
        <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-700 flex items-center justify-between sticky top-0 bg-white dark:bg-zinc-800 z-10">
            <div class="flex items-center gap-2 flex-1 min-w-0">
                <h3 id="adminThreadTitle" class="text-base font-bold text-zinc-900 dark:text-white truncate"></h3>
                <span id="adminThreadStatus" class="text-[10px] px-2 py-0.5 rounded-full font-medium"></span>
            </div>
            <button type="button" onclick="adminCloseThread()" class="text-zinc-400 hover:text-zinc-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div id="adminThreadInfo" class="px-6 py-3 border-b border-gray-100 dark:border-zinc-700 text-[11px] text-zinc-500 dark:text-zinc-400 flex flex-wrap gap-3"></div>
        <div id="adminThreadMessages" class="p-6 space-y-3 max-h-[55vh] overflow-y-auto"></div>
        <div id="adminReplyArea" class="border-t border-gray-200 dark:border-zinc-700 p-4 bg-gray-50 dark:bg-zinc-700/30 sticky bottom-0">
            <textarea id="adminReplyBody" rows="3" class="w-full px-3 py-2 text-xs border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg" placeholder="<?= htmlspecialchars(__('services.admin_support.reply_placeholder')) ?>"></textarea>
            <div class="mt-2">
                <input type="file" id="adminReplyFiles" multiple class="block w-full text-[11px] text-zinc-600 dark:text-zinc-300 file:mr-2 file:py-1 file:px-2 file:rounded-md file:border-0 file:text-[10px] file:font-medium file:bg-blue-50 file:text-blue-700 hover:file:bg-blue-100 dark:file:bg-blue-900/30 dark:file:text-blue-300">
                <div id="adminReplyFilesList" class="mt-1 space-y-1"></div>
            </div>
            <div class="flex items-center justify-between mt-2 gap-2">
                <button type="button" onclick="adminCloseTicket()" id="adminCloseTicketBtn" class="text-[11px] text-zinc-500 hover:text-red-600 underline"><?= htmlspecialchars(__('services.admin_support.btn_close_ticket')) ?></button>
                <button type="button" onclick="adminSubmitReply()" id="adminReplySubmit" class="px-4 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg disabled:opacity-50"><?= htmlspecialchars(__('services.admin_support.btn_reply')) ?></button>
            </div>
        </div>
    </div>
</div>

<script>
var siteBaseUrl = <?= json_encode($baseUrl) ?>;
var adminCurrentTicket = null;

function adminApi(action, payload) {
    payload = payload || {};
    payload.action = action;
    return fetch(siteBaseUrl + '/plugins/vos-hosting/api/service-manage.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(payload),
    }).then(function(r) { return r.json(); });
}

function fmtDate(s) { try { return new Date(s).toLocaleString(); } catch(e) { return s||''; } }
function fmtSize(b) {
    b = parseInt(b, 10) || 0;
    if (b < 1024) return b + ' B';
    if (b < 1048576) return (b / 1024).toFixed(1) + ' KB';
    return (b / 1048576).toFixed(1) + ' MB';
}
function esc(s) { return String(s||'').replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }

var adminMaxFileSize = 31457280;  // 30 MB
var adminMaxFiles = 3;

function bindAdminFilePreview() {
    var inp = document.getElementById('adminReplyFiles');
    if (!inp || inp.dataset.bound) return;
    inp.dataset.bound = '1';
    inp.addEventListener('change', function() {
        var list = document.getElementById('adminReplyFilesList');
        list.innerHTML = '';
        var files = Array.from(this.files);
        if (files.length > adminMaxFiles) {
            alert(<?= json_encode(__('services.detail.support_attach_too_many', ['max' => 3]), JSON_UNESCAPED_UNICODE) ?>);
            this.value = ''; return;
        }
        for (var i = 0; i < files.length; i++) {
            var f = files[i];
            if (f.size > adminMaxFileSize) {
                alert('"' + f.name + '" ' + <?= json_encode(__('services.detail.support_attach_too_large', ['max' => '30MB']), JSON_UNESCAPED_UNICODE) ?>);
                this.value = ''; list.innerHTML = ''; return;
            }
            var div = document.createElement('div');
            div.className = 'flex items-center gap-2 text-[11px] bg-gray-50 dark:bg-zinc-700/30 px-2 py-1 rounded';
            div.innerHTML = '<svg class="w-3 h-3 text-zinc-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>'
                          + '<span class="truncate flex-1">' + esc(f.name) + '</span>'
                          + '<span class="text-zinc-400">' + fmtSize(f.size) + '</span>';
            list.appendChild(div);
        }
    });
}
bindAdminFilePreview();

function adminUploadAttachments(fileInput) {
    var files = fileInput && fileInput.files ? Array.from(fileInput.files) : [];
    if (files.length === 0) return Promise.resolve([]);
    var fd = new FormData();
    files.forEach(function(f){ fd.append('files[]', f); });
    return fetch(siteBaseUrl + '/plugins/vos-hosting/api/support-attachment.php?action=upload_pending', {
        method: 'POST', body: fd, credentials: 'same-origin'
    }).then(function(r){ return r.json(); }).then(function(d){
        if (!d.success) throw new Error(d.message || 'upload failed');
        return d.attachments || [];
    });
}

function adminOpenThread(ticketId) {
    var m = document.getElementById('adminThreadModal');
    if (m.parentElement !== document.body) document.body.appendChild(m);
    m.classList.remove('hidden'); m.classList.add('flex');
    document.body.style.overflow = 'hidden';
    document.getElementById('adminThreadMessages').innerHTML = '<div class="text-center text-xs text-zinc-400 py-6">...</div>';
    adminApi('support_get_ticket', { ticket_id: ticketId }).then(function(d) {
        if (!d.success) { document.getElementById('adminThreadMessages').innerHTML = '<div class="text-red-500 text-xs p-6">' + (d.message||'error') + '</div>'; return; }
        adminCurrentTicket = d.ticket;
        document.getElementById('adminThreadTitle').textContent = d.ticket.title;

        var statusBadge = document.getElementById('adminThreadStatus');
        if (d.ticket.status === 'open') { statusBadge.textContent = <?= json_encode(__('services.detail.support_st_open'), JSON_UNESCAPED_UNICODE) ?>; statusBadge.className = 'text-[10px] px-2 py-0.5 rounded-full font-medium bg-amber-100 text-amber-700'; }
        else if (d.ticket.status === 'answered') { statusBadge.textContent = <?= json_encode(__('services.detail.support_st_answered'), JSON_UNESCAPED_UNICODE) ?>; statusBadge.className = 'text-[10px] px-2 py-0.5 rounded-full font-medium bg-emerald-100 text-emerald-700'; }
        else { statusBadge.textContent = <?= json_encode(__('services.detail.support_st_closed'), JSON_UNESCAPED_UNICODE) ?>; statusBadge.className = 'text-[10px] px-2 py-0.5 rounded-full font-medium bg-gray-100 text-gray-500'; }

        // 사용자 정보 + 호스팅 sub 링크
        var info = '<span><strong>' + esc(d.ticket.user_name || '') + '</strong></span>'
                 + '<span>· ' + esc(d.ticket.user_email || '') + '</span>'
                 + (d.ticket.host_subscription_id ? '<span>· host_sub#' + d.ticket.host_subscription_id + '</span>' : '')
                 + '<span>· ' + fmtDate(d.ticket.created_at) + '</span>';
        document.getElementById('adminThreadInfo').innerHTML = info;

        var msgHtml = '';
        (d.messages || []).forEach(function(m) {
            var isAdmin = parseInt(m.is_admin, 10) === 1;
            var bgClass = isAdmin ? 'bg-blue-50 dark:bg-blue-900/20 border-blue-200 dark:border-blue-800' : 'bg-gray-50 dark:bg-zinc-700/30 border-gray-200 dark:border-zinc-700';
            var sender = isAdmin ? <?= json_encode(__('services.admin_support.admin_label'), JSON_UNESCAPED_UNICODE) ?> : (m.sender_name || m.sender_email || 'user');
            msgHtml += '<div class="border ' + bgClass + ' rounded-lg p-3">'
                    + '<div class="flex items-center justify-between mb-2 text-[10px] text-zinc-500"><span class="font-medium">' + esc(sender) + '</span><span>' + fmtDate(m.created_at) + '</span></div>'
                    + '<p class="text-sm text-zinc-800 dark:text-zinc-200 whitespace-pre-wrap">' + esc(m.body) + '</p>';
            var atts = m.attachments || [];
            if (atts.length > 0) {
                msgHtml += '<div class="mt-2 pt-2 border-t border-gray-200 dark:border-zinc-700 space-y-1">';
                atts.forEach(function(a, idx) {
                    var isImg = /^(jpg|jpeg|png|gif|webp|svg)$/i.test(a.ext || '');
                    var dlUrl = siteBaseUrl + '/plugins/vos-hosting/api/support-attachment.php?action=download&msg=' + m.id + '&idx=' + idx;
                    var inUrl = siteBaseUrl + '/plugins/vos-hosting/api/support-attachment.php?action=inline&msg=' + m.id + '&idx=' + idx;
                    if (isImg) {
                        msgHtml += '<a href="' + dlUrl + '" target="_blank" class="block">'
                                + '<img src="' + inUrl + '" alt="' + esc(a.name) + '" class="max-h-48 rounded border border-gray-200 dark:border-zinc-600 hover:opacity-80 transition" />'
                                + '<span class="text-[10px] text-zinc-400 mt-0.5 block">' + esc(a.name) + ' (' + fmtSize(a.size) + ')</span>'
                                + '</a>';
                    } else {
                        msgHtml += '<a href="' + dlUrl + '" class="flex items-center gap-2 text-[11px] bg-white dark:bg-zinc-700 hover:bg-blue-50 dark:hover:bg-zinc-600 px-2 py-1.5 rounded border border-gray-200 dark:border-zinc-600 transition">'
                                + '<svg class="w-3.5 h-3.5 text-zinc-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15.172 7l-6.586 6.586a2 2 0 102.828 2.828l6.414-6.586a4 4 0 00-5.656-5.656l-6.415 6.585a6 6 0 108.486 8.486L20.5 13"/></svg>'
                                + '<span class="truncate flex-1 text-zinc-700 dark:text-zinc-200">' + esc(a.name) + '</span>'
                                + '<span class="text-zinc-400">' + fmtSize(a.size) + '</span>'
                                + '</a>';
                    }
                });
                msgHtml += '</div>';
            }
            msgHtml += '</div>';
        });
        document.getElementById('adminThreadMessages').innerHTML = msgHtml || '<div class="text-zinc-400 text-xs text-center">no messages</div>';

        if (d.ticket.status === 'closed') {
            document.getElementById('adminReplyArea').classList.add('hidden');
        } else {
            document.getElementById('adminReplyArea').classList.remove('hidden');
            document.getElementById('adminReplyBody').value = '';
            document.getElementById('adminReplyFiles').value = '';
            document.getElementById('adminReplyFilesList').innerHTML = '';
        }
    }).catch(function(e) { document.getElementById('adminThreadMessages').innerHTML = '<div class="text-red-500 text-xs p-6">' + (e&&e.message) + '</div>'; });
}

function adminCloseThread() {
    var m = document.getElementById('adminThreadModal');
    m.classList.add('hidden'); m.classList.remove('flex');
    document.body.style.overflow = '';
    location.reload();
}

function adminSubmitReply() {
    if (!adminCurrentTicket) return;
    var body = document.getElementById('adminReplyBody').value.trim();
    if (!body) return;
    var btn = document.getElementById('adminReplySubmit');
    btn.disabled = true;
    adminUploadAttachments(document.getElementById('adminReplyFiles')).then(function(atts) {
        return adminApi('support_post_message', { ticket_id: adminCurrentTicket.id, body: body, attachments: atts });
    }).then(function(d) {
        btn.disabled = false;
        if (d.success) { adminOpenThread(adminCurrentTicket.id); }
        else alert(d.message || 'error');
    }).catch(function(e) { btn.disabled = false; alert(e && e.message || 'error'); });
}

function adminCloseTicket() {
    if (!adminCurrentTicket) return;
    if (!confirm(<?= json_encode(__('services.admin_support.confirm_close'), JSON_UNESCAPED_UNICODE) ?>)) return;
    adminApi('support_close_ticket', { ticket_id: adminCurrentTicket.id }).then(function(d) {
        if (d.success) { adminOpenThread(adminCurrentTicket.id); }
        else alert(d.message || 'error');
    });
}

// URL ?ticket=N 자동 오픈
(function() {
    var u = new URL(window.location.href);
    var t = u.searchParams.get('ticket');
    if (t) adminOpenThread(parseInt(t, 10));
})();
</script>

<?php include BASE_PATH . '/resources/views/admin/reservations/_foot.php'; ?>
