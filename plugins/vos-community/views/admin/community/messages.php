<?php
/**
 * 어드민 - 메시지 센터
 * /{ADMIN_PATH}/community/messages
 *
 * 모든 사용자 간 1:1 메시지 열람. 조회 시 audit log 기록.
 */
if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__, 4));
require_once BASE_PATH . '/plugins/vos-community/_init.php';
require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
use RzxLib\Core\Auth\Auth;
if (!Auth::check() || !in_array(Auth::user()['role'] ?? '', ['admin','supervisor','owner'], true)) {
    http_response_code(403); echo '권한 없음'; return;
}
$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
$basePath = parse_url($baseUrl, PHP_URL_PATH) ?: '';
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$pageTitle = __('community.messages.title') . ' - ' . ($config['app_name'] ?? 'VosCMS') . ' Admin';
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
        $pageHeaderTitle = __('community.messages.title');
        include BASE_PATH . '/resources/views/admin/partials/admin-topbar.php';
        ?>
<div class="p-6">
    <div class="mb-4 flex items-center justify-between">
        <div>
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars(__('community.messages.title')) ?></h1>
            <p class="text-sm text-zinc-500 mt-1"><?= htmlspecialchars(__('community.messages.description')) ?></p>
        </div>
    </div>

    <!-- 통계 -->
    <div id="msgStats" class="grid grid-cols-2 sm:grid-cols-5 gap-4 mb-4"></div>

    <!-- 개인정보 안내 -->
    <div class="mb-4 p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800/50 rounded-lg text-xs text-amber-800 dark:text-amber-300 flex items-start gap-2">
        <svg class="w-4 h-4 mt-0.5 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5 19h14a2 2 0 001.84-2.75L13.74 4a2 2 0 00-3.48 0L3.16 16.25A2 2 0 005 19z"/></svg>
        <p><?= htmlspecialchars(__('community.messages.privacy_notice')) ?></p>
    </div>

    <!-- 본 영역: 좌(목록) + 우(스레드) -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden flex" style="height: calc(100vh - 280px); min-height: 500px;">
        <!-- 좌측: 대화 목록 -->
        <div class="w-96 border-r border-zinc-200 dark:border-zinc-700 flex flex-col">
            <div class="p-3 border-b border-zinc-200 dark:border-zinc-700">
                <input type="text" id="searchInput" placeholder="<?= htmlspecialchars(__('community.messages.search_placeholder')) ?>" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 dark:text-white">
                <p id="convCount" class="text-[11px] text-zinc-400 mt-1">-</p>
            </div>
            <div id="convList" class="flex-1 overflow-y-auto"></div>
            <div id="convPagination" class="border-t border-zinc-200 dark:border-zinc-700 p-2 flex items-center justify-between text-xs"></div>
        </div>

        <!-- 우측: 스레드 -->
        <div class="flex-1 flex flex-col">
            <div id="threadHeader" class="hidden border-b border-zinc-200 dark:border-zinc-700 p-3"></div>
            <div id="threadMessages" class="flex-1 overflow-y-auto p-4 bg-zinc-50 dark:bg-zinc-900/40 space-y-2"></div>
            <div id="threadEmpty" class="flex-1 flex items-center justify-center text-sm text-zinc-400">
                <?= htmlspecialchars(__('community.messages.select_conversation')) ?>
            </div>
        </div>
    </div>
</div>

<script>
(function(){
    var BASE = <?= json_encode($baseUrl) ?>;
    var I18N = <?= json_encode([
        'today' => __('community.messages.stat_today'),
        'week' => __('community.messages.stat_week'),
        'total_messages' => __('community.messages.stat_total_messages'),
        'total_conversations' => __('community.messages.stat_total_conversations'),
        'active_senders' => __('community.messages.stat_active_senders'),
        'count_unit' => __('community.messages.count_unit'),
        'message_count' => __('community.messages.message_count'),
        'reports' => __('community.messages.reports_label'),
        'paused' => __('community.messages.paused'),
        'inactive' => __('community.messages.inactive'),
        'admin_role' => __('community.messages.admin_role'),
        'deleted_by_sender' => __('community.messages.deleted_by_sender'),
        'deleted_by_recipient' => __('community.messages.deleted_by_recipient'),
        'unread_label' => __('community.messages.unread_label'),
        'loading' => __('community.messages.loading'),
        'empty_search' => __('community.messages.empty_search'),
        'empty' => __('community.messages.empty'),
        'select_conversation' => __('community.messages.select_conversation'),
        'no_messages' => __('community.messages.no_messages'),
        'prev' => __('community.messages.prev'),
        'next' => __('community.messages.next'),
    ], JSON_UNESCAPED_UNICODE) ?>;

    var currentOffset = 0;
    var currentTotal = 0;
    var currentLimit = 30;
    var currentQuery = '';
    var currentConvId = 0;
    var searchTimer = null;

    function escHtml(s) { return (s || '').replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
    function initialOf(s) { return (s || '?').charAt(0).toUpperCase(); }

    function loadStats() {
        fetch(BASE + '/api/messages.php?action=admin_message_stats', {credentials:'same-origin'})
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (!d.success) return;
                var s = d.stats;
                document.getElementById('msgStats').innerHTML =
                  card(s.today_messages || 0, I18N.today, 'red')
                + card(s.week_messages || 0, I18N.week, 'amber')
                + card(s.total_messages || 0, I18N.total_messages, 'blue')
                + card(s.total_conversations || 0, I18N.total_conversations, 'emerald')
                + card(s.active_senders_7d || 0, I18N.active_senders, 'violet');
            });
    }
    function card(num, label, color) {
        return '<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-4">'
            +   '<p class="text-xs text-zinc-500">' + escHtml(label) + '</p>'
            +   '<p class="text-2xl font-bold text-' + color + '-600 dark:text-' + color + '-400 mt-1">' + (num).toLocaleString() + '</p>'
            + '</div>';
    }

    function loadConversations() {
        var list = document.getElementById('convList');
        list.innerHTML = '<div class="p-12 text-center text-sm text-zinc-400">' + escHtml(I18N.loading) + '</div>';
        var url = BASE + '/api/messages.php?action=admin_list_conversations&offset=' + currentOffset + (currentQuery ? '&q=' + encodeURIComponent(currentQuery) : '');
        fetch(url, {credentials:'same-origin'})
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (!d.success) {
                    list.innerHTML = '<div class="p-12 text-center text-sm text-red-500">' + escHtml(d.message || '') + '</div>';
                    return;
                }
                currentTotal = d.total;
                currentLimit = d.limit;
                document.getElementById('convCount').textContent = d.total + ' ' + I18N.count_unit;
                if (!d.conversations.length) {
                    list.innerHTML = '<div class="p-12 text-center text-sm text-zinc-400">' + escHtml(currentQuery ? I18N.empty_search : I18N.empty) + '</div>';
                    document.getElementById('convPagination').innerHTML = '';
                    return;
                }
                list.innerHTML = d.conversations.map(function(c){
                    var when = (c.last_message_at || '').slice(0, 16).replace('T', ' ');
                    var unread = (parseInt(c.user1_unread,10) + parseInt(c.user2_unread,10));
                    var unreadBadge = unread > 0 ? '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400 ml-1">' + unread + '</span>' : '';
                    var reportBadge = (parseInt(c.report_count,10) > 0)
                        ? '<span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 ml-1">' + I18N.reports + ' ' + c.report_count + '</span>'
                        : '';
                    var active = c.id == currentConvId ? 'bg-blue-50 dark:bg-blue-900/20' : 'hover:bg-zinc-50 dark:hover:bg-zinc-700/30';
                    return '<button type="button" onclick="openConv(' + c.id + ')" data-cid="' + c.id + '" class="w-full text-left px-3 py-3 border-b border-zinc-100 dark:border-zinc-700/50 transition ' + active + '">'
                        + '<div class="flex items-center gap-2 mb-1">'
                        +   '<span class="text-sm font-semibold text-zinc-900 dark:text-white truncate">' + escHtml(c.u1_display) + '</span>'
                        +   '<svg class="w-3 h-3 text-zinc-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>'
                        +   '<span class="text-sm font-semibold text-zinc-900 dark:text-white truncate">' + escHtml(c.u2_display) + '</span>'
                        +   reportBadge + unreadBadge
                        + '</div>'
                        + '<p class="text-xs text-zinc-500 dark:text-zinc-400 truncate">' + escHtml(c.last_preview || '') + '</p>'
                        + '<div class="flex items-center justify-between mt-1 text-[10px] text-zinc-400">'
                        +   '<span>' + when + '</span>'
                        +   '<span>' + I18N.message_count + ' ' + c.message_count + '</span>'
                        + '</div>'
                        + '</button>';
                }).join('');

                // 페이지네이션
                var prevDisabled = currentOffset === 0;
                var nextDisabled = currentOffset + currentLimit >= currentTotal;
                document.getElementById('convPagination').innerHTML =
                    '<button onclick="goPage(-1)" ' + (prevDisabled ? 'disabled' : '') + ' class="px-3 py-1 rounded ' + (prevDisabled ? 'text-zinc-300' : 'text-blue-600 hover:bg-zinc-100 dark:hover:bg-zinc-700') + '">' + escHtml(I18N.prev) + '</button>'
                    + '<span class="text-zinc-400">' + (currentOffset + 1) + '–' + Math.min(currentOffset + currentLimit, currentTotal) + ' / ' + currentTotal + '</span>'
                    + '<button onclick="goPage(1)" ' + (nextDisabled ? 'disabled' : '') + ' class="px-3 py-1 rounded ' + (nextDisabled ? 'text-zinc-300' : 'text-blue-600 hover:bg-zinc-100 dark:hover:bg-zinc-700') + '">' + escHtml(I18N.next) + '</button>';
            });
    }

    window.goPage = function(dir) {
        currentOffset = Math.max(0, currentOffset + dir * currentLimit);
        loadConversations();
    };

    window.openConv = function(cid) {
        currentConvId = cid;
        document.querySelectorAll('#convList button').forEach(function(b){
            b.classList.remove('bg-blue-50','dark:bg-blue-900/20');
            if (parseInt(b.dataset.cid, 10) === cid) {
                b.classList.add('bg-blue-50','dark:bg-blue-900/20');
            }
        });
        loadThread(cid);
    };

    function loadThread(cid) {
        var head = document.getElementById('threadHeader');
        var msgs = document.getElementById('threadMessages');
        var empty = document.getElementById('threadEmpty');
        head.classList.add('hidden');
        empty.classList.add('hidden');
        msgs.innerHTML = '<div class="text-center text-sm text-zinc-400 py-12">' + escHtml(I18N.loading) + '</div>';

        fetch(BASE + '/api/messages.php?action=admin_view_conversation&conversation_id=' + cid, {credentials:'same-origin'})
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (!d.success) {
                    msgs.innerHTML = '<div class="text-center text-sm text-red-500 py-12">' + escHtml(d.message || '') + '</div>';
                    return;
                }
                renderHeader(d.users);
                renderMessages(d.messages, d.users);
                head.classList.remove('hidden');
                head.classList.add('block');
            });
    }

    function userBadge(u, color) {
        var av = u.avatar_url
            ? '<img src="' + escHtml(u.avatar_url) + '" class="w-9 h-9 rounded-full object-cover flex-shrink-0">'
            : '<div class="w-9 h-9 rounded-full bg-' + color + '-100 dark:bg-' + color + '-900/40 text-' + color + '-600 dark:text-' + color + '-400 flex items-center justify-center text-sm font-bold flex-shrink-0">' + escHtml(initialOf(u.display_name)) + '</div>';
        var paused = u.messages_paused_until && new Date(u.messages_paused_until.replace(' ', 'T') + 'Z') > new Date()
            ? '<span class="px-1.5 py-0.5 text-[10px] bg-amber-100 text-amber-700 rounded">' + escHtml(I18N.paused) + '</span>'
            : '';
        var inactive = parseInt(u.is_active, 10) === 0 ? '<span class="px-1.5 py-0.5 text-[10px] bg-red-100 text-red-700 rounded">' + escHtml(I18N.inactive) + '</span>' : '';
        var roleBadge = u.role && ['admin','supervisor','owner'].indexOf(u.role) >= 0
            ? '<span class="px-1.5 py-0.5 text-[10px] bg-violet-100 text-violet-700 rounded">' + escHtml(I18N.admin_role) + '</span>'
            : '';
        return '<div class="flex items-center gap-2 flex-1 min-w-0">'
            + av
            + '<div class="min-w-0 flex-1">'
            +   '<div class="flex items-center gap-1.5 flex-wrap">'
            +     '<span class="text-sm font-semibold text-zinc-900 dark:text-white truncate">' + escHtml(u.display_name) + '</span>'
            +     roleBadge + paused + inactive
            +   '</div>'
            +   '<p class="text-[10px] text-zinc-400 truncate">' + escHtml(u.email || '') + ' · #' + (u.id || '').slice(0,8) + '</p>'
            + '</div></div>';
    }

    function renderHeader(users) {
        var ids = Object.keys(users);
        if (ids.length < 2) return;
        document.getElementById('threadHeader').innerHTML =
            '<div class="flex items-center gap-3">'
            + userBadge(users[ids[0]], 'blue')
            + '<svg class="w-4 h-4 text-zinc-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>'
            + userBadge(users[ids[1]], 'emerald')
            + '</div>';
    }

    function renderMessages(messages, users) {
        var msgs = document.getElementById('threadMessages');
        if (!messages.length) {
            msgs.innerHTML = '<div class="text-center text-sm text-zinc-400 py-12">' + escHtml(I18N.no_messages) + '</div>';
            return;
        }
        var html = '';
        var lastDate = '';
        var prevSender = null;
        var ids = Object.keys(users);
        var firstId = ids[0];
        messages.forEach(function(m){
            var dt = (m.sent_at || '').slice(0, 10);
            if (dt && dt !== lastDate) {
                html += '<div class="flex justify-center my-2"><span class="text-[10px] text-zinc-400 px-2 py-0.5 bg-white dark:bg-zinc-800 rounded-full">' + dt + '</span></div>';
                lastDate = dt;
                prevSender = null;
            }
            var u = users[m.sender_id] || {};
            var isFirst = m.sender_id === firstId;
            var bubble = isFirst
                ? 'bg-blue-100 dark:bg-blue-900/30 text-zinc-900 dark:text-zinc-100'
                : 'bg-emerald-100 dark:bg-emerald-900/30 text-zinc-900 dark:text-zinc-100';
            var time = (m.sent_at || '').slice(11, 16);
            var showName = m.sender_id !== prevSender;
            var nameLabel = showName
                ? '<p class="text-[11px] font-medium ' + (isFirst ? 'text-blue-700 dark:text-blue-300 ml-1' : 'text-emerald-700 dark:text-emerald-300 ml-1') + ' mb-0.5">' + escHtml(u.display_name || '?') + '</p>'
                : '';
            var spacingTop = showName ? 'mt-3' : 'mt-1';
            var deleteBadges = '';
            if (parseInt(m.sender_deleted, 10) === 1) deleteBadges += '<span class="ml-1 px-1 py-0.5 text-[9px] bg-zinc-200 dark:bg-zinc-700 text-zinc-500 rounded">' + escHtml(I18N.deleted_by_sender) + '</span>';
            if (parseInt(m.recipient_deleted, 10) === 1) deleteBadges += '<span class="ml-1 px-1 py-0.5 text-[9px] bg-zinc-200 dark:bg-zinc-700 text-zinc-500 rounded">' + escHtml(I18N.deleted_by_recipient) + '</span>';
            var unreadBadge = parseInt(m.is_read, 10) === 0 ? '<span class="ml-1 text-[9px] text-blue-500">' + escHtml(I18N.unread_label) + '</span>' : '';
            html += '<div class="flex ' + (isFirst ? 'justify-start' : 'justify-end') + ' ' + spacingTop + '">'
                + '<div class="max-w-[70%]">'
                +   nameLabel
                +   '<div class="px-3 py-2 rounded-2xl ' + bubble + ' shadow-sm">'
                +     '<p class="text-sm whitespace-pre-wrap break-words">' + escHtml(m.body) + '</p>'
                +     '<p class="text-[10px] mt-1 text-zinc-500">' + time + unreadBadge + deleteBadges + '</p>'
                +   '</div>'
                + '</div></div>';
            prevSender = m.sender_id;
        });
        msgs.innerHTML = html;
        msgs.scrollTop = msgs.scrollHeight;
    }

    document.getElementById('searchInput').addEventListener('input', function(e){
        clearTimeout(searchTimer);
        searchTimer = setTimeout(function(){
            currentQuery = e.target.value.trim();
            currentOffset = 0;
            loadConversations();
        }, 300);
    });

    loadStats();
    loadConversations();
})();
</script>
    </main>
</div>
<?php include BASE_PATH . '/resources/views/admin/partials/pwa-scripts.php'; ?>
</body>
</html>
