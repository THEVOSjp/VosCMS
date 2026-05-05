<?php
/**
 * VosCMS 마이페이지 메시지 — 알림 + 대화 (양방향) 통합 inbox
 * /mypage/messages[?c={conversation_id}]
 */

require_once BASE_PATH . '/plugins/vos-community/_init.php';
require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
use RzxLib\Core\Auth\Auth;

if (!Auth::check()) {
    header('Location: ' . ($config['app_url'] ?? '') . '/login');
    exit;
}

$user = Auth::user();
$pageTitle = ($config['app_name'] ?? 'VosCMS') . ' - ' . __('auth.mypage.menu.messages');
$baseUrl = $config['app_url'] ?? '';
$isLoggedIn = true;
$currentUser = $user;
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

// 초기 데이터 — 안 읽은 알림 + 안 읽은 대화 카운트 + 알림 첫 페이지
$unreadNotif = 0;
$unreadConv = 0;
$initialNotifs = [];
try {
    $s1 = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}notifications WHERE user_id = ? AND is_read = 0");
    $s1->execute([$user['id']]);
    $unreadNotif = (int)$s1->fetchColumn();

    $s2 = $pdo->prepare("SELECT
        SUM(CASE WHEN user1_id = :uid THEN user1_unread ELSE user2_unread END)
        FROM {$prefix}conversations
        WHERE (user1_id = :uid AND user1_deleted = 0) OR (user2_id = :uid AND user2_deleted = 0)");
    $s2->execute(['uid' => $user['id']]);
    $unreadConv = (int)($s2->fetchColumn() ?: 0);

    // 알림 첫 20개 (서버 사이드 렌더링 — JS fetch 실패해도 보이도록)
    $s3 = $pdo->prepare("SELECT id, type, category, title, body, link, icon, is_read, created_at
        FROM {$prefix}notifications WHERE user_id = ?
        ORDER BY is_read ASC, created_at DESC LIMIT 20");
    $s3->execute([$user['id']]);
    $initialNotifs = $s3->fetchAll();
} catch (\Throwable $e) { /* silent */ }

// 초기 탭: ?c=N 있으면 conversations, 그 외 notifications
$initialConvId = (int)($_GET['c'] ?? 0);
$initialTab = $initialConvId > 0 ? 'conversations' : 'notifications';
?>
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="lg:flex lg:gap-8">
        <!-- 사이드바 -->
        <?php
        $profileImgUrl = '';
        if (!empty($user['profile_image'])) {
            $profileImgUrl = str_starts_with($user['profile_image'], 'http')
                ? $user['profile_image']
                : $baseUrl . $user['profile_image'];
        }
        $sidebarActive = 'messages';
        include BASE_PATH . '/resources/views/components/mypage-sidebar.php';
        ?>

        <!-- 메인 -->
        <div class="flex-1 min-w-0">
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg overflow-hidden">
                <!-- 헤더 -->
                <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-700">
                    <h1 class="text-xl font-bold text-gray-900 dark:text-white"><?= htmlspecialchars(__('auth.mypage.menu.messages')) ?></h1>
                </div>

                <!-- 탭 -->
                <div class="border-b border-gray-200 dark:border-zinc-700 flex">
                    <button id="tabNotif" onclick="switchTab('notifications')" class="flex-1 px-6 py-3 text-sm font-medium border-b-2 transition <?= $initialTab === 'notifications' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-zinc-500 hover:text-zinc-700' ?>">
                        <?= htmlspecialchars(__('auth.mypage.messages.tab_notifications')) ?>
                        <?php if ($unreadNotif > 0): ?>
                        <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded-full bg-red-500 text-white text-[10px] font-bold"><?= $unreadNotif > 99 ? '99+' : $unreadNotif ?></span>
                        <?php endif; ?>
                    </button>
                    <button id="tabConv" onclick="switchTab('conversations')" class="flex-1 px-6 py-3 text-sm font-medium border-b-2 transition <?= $initialTab === 'conversations' ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-zinc-500 hover:text-zinc-700' ?>">
                        <?= htmlspecialchars(__('auth.mypage.messages.tab_conversations')) ?>
                        <?php if ($unreadConv > 0): ?>
                        <span class="ml-1.5 inline-flex items-center px-1.5 py-0.5 rounded-full bg-red-500 text-white text-[10px] font-bold"><?= $unreadConv > 99 ? '99+' : $unreadConv ?></span>
                        <?php endif; ?>
                    </button>
                </div>

                <!-- 탭 콘텐츠: 알림 -->
                <div id="paneNotifications" class="<?= $initialTab !== 'notifications' ? 'hidden' : '' ?>">
                    <div class="px-6 py-3 border-b dark:border-zinc-700 flex items-center justify-between">
                        <span class="text-xs text-zinc-500" id="notifSummary">-</span>
                        <button type="button" onclick="markAllNotifRead()" class="text-xs text-blue-600 hover:underline" id="btnMarkAllRead"><?= htmlspecialchars(__('auth.mypage.messages.mark_all_read')) ?></button>
                    </div>
                    <div id="notifList" class="divide-y divide-gray-200 dark:divide-zinc-700">
<?php
// 서버 사이드 첫 렌더링 (JS fetch 실패해도 보이도록)
if (empty($initialNotifs)) {
    echo '<!-- empty placeholder, see notifEmpty -->';
} else {
    foreach ($initialNotifs as $n) {
        $bg = $n['is_read'] ? '' : 'bg-blue-50/50 dark:bg-blue-900/10';
        $iconColor = $n['is_read'] ? 'bg-gray-100 dark:bg-zinc-700 text-gray-500' : 'bg-blue-100 dark:bg-blue-900/30 text-blue-600';
        $isWarn = in_array($n['icon'] ?? '', ['warning','error']);
        $iconSvg = $isWarn
            ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5 19h14a2 2 0 001.84-2.75L13.74 4a2 2 0 00-3.48 0L3.16 16.25A2 2 0 005 19z"/>'
            : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>';
        $unreadDot = $n['is_read'] ? '' : '<span class="w-2 h-2 bg-blue-500 rounded-full inline-block mr-1.5 align-middle"></span>';
        $link = htmlspecialchars($n['link'] ?: '#');
        $title = htmlspecialchars($n['title']);
        $bodyTxt = $n['body'] ? htmlspecialchars($n['body']) : '';
        $created = htmlspecialchars($n['created_at']);
        echo "<a href=\"{$link}\" data-nid=\"{$n['id']}\" class=\"block px-6 py-3 hover:bg-gray-50 dark:hover:bg-zinc-700/30 {$bg}\">"
            . '<div class="flex gap-3">'
            . "<div class=\"flex-shrink-0 w-9 h-9 rounded-full flex items-center justify-center {$iconColor}\">"
            . "<svg class=\"w-4 h-4\" fill=\"none\" stroke=\"currentColor\" viewBox=\"0 0 24 24\">{$iconSvg}</svg>"
            . '</div>'
            . '<div class="flex-1 min-w-0">'
            . "<p class=\"text-sm font-semibold text-gray-900 dark:text-white truncate\">{$unreadDot}{$title}</p>"
            . ($bodyTxt ? "<p class=\"text-xs text-gray-500 dark:text-zinc-400 line-clamp-2 mt-0.5\">{$bodyTxt}</p>" : '')
            . "<p class=\"text-[10px] text-gray-400 mt-1\">{$created}</p>"
            . '</div></div></a>';
    }
}
?>
                    </div>
                    <div id="notifEmpty" class="<?= empty($initialNotifs) ? '' : 'hidden' ?> p-12 text-center text-sm text-zinc-400"><?= htmlspecialchars(__('auth.mypage.messages.empty')) ?></div>
                </div>

                <!-- 탭 콘텐츠: 대화 -->
                <div id="paneConversations" class="<?= $initialTab !== 'conversations' ? 'hidden' : '' ?>">
                    <div class="flex h-[600px]">
                        <!-- 좌측: 대화 목록 -->
                        <div class="w-72 border-r border-gray-200 dark:border-zinc-700 flex flex-col">
                            <div class="px-4 py-3 border-b dark:border-zinc-700 flex items-center justify-between">
                                <span class="text-xs font-bold text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars(__('auth.mypage.messages.conversations')) ?></span>
                                <button type="button" onclick="openNewMessageModal()" class="text-xs px-2 py-1 bg-blue-600 text-white rounded hover:bg-blue-700">
                                    + <?= htmlspecialchars(__('auth.mypage.messages.new')) ?>
                                </button>
                            </div>
                            <div id="convList" class="flex-1 overflow-y-auto"></div>
                            <div id="convEmpty" class="hidden p-8 text-center text-xs text-zinc-400"><?= htmlspecialchars(__('auth.mypage.messages.no_conversations')) ?></div>
                        </div>

                        <!-- 우측: 스레드 -->
                        <div class="flex-1 flex flex-col">
                            <div id="threadHeader" class="hidden px-4 py-3 border-b dark:border-zinc-700 flex items-center gap-3">
                                <div id="threadAvatar" class="w-9 h-9 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center text-sm font-bold"></div>
                                <div class="flex-1 min-w-0">
                                    <p id="threadName" class="text-sm font-semibold text-zinc-900 dark:text-white truncate"></p>
                                    <p id="threadStatus" class="text-[10px] text-zinc-400">-</p>
                                </div>
                            </div>
                            <div id="threadMessages" class="flex-1 overflow-y-auto p-4 space-y-2 bg-zinc-50 dark:bg-zinc-900/40"></div>
                            <div id="threadComposer" class="hidden p-3 border-t dark:border-zinc-700 flex gap-2">
                                <textarea id="composerBody" rows="2" placeholder="<?= htmlspecialchars(__('auth.mypage.messages.placeholder_message')) ?>" class="flex-1 px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 dark:text-white resize-none"></textarea>
                                <button type="button" onclick="sendMessage()" id="btnSendMsg" class="px-4 py-2 bg-blue-600 text-white text-sm font-medium rounded-lg hover:bg-blue-700 disabled:opacity-50"><?= htmlspecialchars(__('auth.mypage.messages.send')) ?></button>
                            </div>
                            <div id="threadEmpty" class="flex-1 flex items-center justify-center text-sm text-zinc-400"><?= htmlspecialchars(__('auth.mypage.messages.select_conversation')) ?></div>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
</div>

<!-- 새 메시지 모달 -->
<div id="newMessageModal" class="hidden fixed inset-0 z-[9000] flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars(__('auth.mypage.messages.new_message')) ?></h3>
            <button type="button" onclick="closeNewMessageModal()" class="text-zinc-400 hover:text-zinc-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <label class="block text-xs text-zinc-500 mb-1"><?= htmlspecialchars(__('auth.mypage.messages.recipient')) ?></label>
        <input type="text" id="newMsgRecipient" oninput="searchRecipient()" placeholder="<?= htmlspecialchars(__('auth.mypage.messages.recipient_placeholder')) ?>" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 dark:text-white mb-2" autocomplete="off">
        <div id="recipientResults" class="hidden border border-zinc-200 dark:border-zinc-600 rounded-lg max-h-48 overflow-y-auto mb-3"></div>
        <div id="recipientSelected" class="hidden mb-3 px-3 py-2 bg-blue-50 dark:bg-blue-900/20 rounded-lg text-sm flex items-center justify-between">
            <span id="recipientSelectedName" class="font-semibold text-blue-700 dark:text-blue-300"></span>
            <button type="button" onclick="clearRecipient()" class="text-blue-400 hover:text-red-500">&times;</button>
        </div>
        <label class="block text-xs text-zinc-500 mb-1"><?= htmlspecialchars(__('auth.mypage.messages.body')) ?></label>
        <textarea id="newMsgBody" rows="5" maxlength="5000" placeholder="<?= htmlspecialchars(__('auth.mypage.messages.placeholder_message')) ?>" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 dark:text-white resize-none mb-3"></textarea>
        <div class="flex justify-end gap-2">
            <button type="button" onclick="closeNewMessageModal()" class="px-4 py-2 text-xs text-zinc-500 hover:text-zinc-700"><?= htmlspecialchars(__('common.buttons.cancel')) ?></button>
            <button type="button" id="btnSendNewMsg" onclick="sendNewMessage()" disabled class="px-4 py-2 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 disabled:opacity-50"><?= htmlspecialchars(__('auth.mypage.messages.send')) ?></button>
        </div>
    </div>
</div>

<script>
(function(){
    var BASE = <?= json_encode($baseUrl) ?>;
    var MY_ID = <?= json_encode($user['id']) ?>;
    var INITIAL_CONV = <?= (int)$initialConvId ?>;
    var currentTab = '<?= $initialTab ?>';
    var currentConvId = 0;
    var selectedRecipientId = null;
    var convPollTimer = null;

    function escHtml(s) { return (s || '').replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
    function relTime(iso) {
        if (!iso) return '';
        var d = new Date(iso.replace(' ', 'T') + 'Z');
        var s = Math.floor((Date.now() - d.getTime()) / 1000);
        if (s < 60) return '방금';
        if (s < 3600) return Math.floor(s / 60) + '분';
        if (s < 86400) return Math.floor(s / 3600) + '시간';
        if (s < 604800) return Math.floor(s / 86400) + '일';
        return d.toISOString().slice(5, 10);
    }
    function initialOf(name) { return (name || '?').charAt(0).toUpperCase(); }

    // ─── 탭 전환 ───
    window.switchTab = function(tab) {
        currentTab = tab;
        document.getElementById('paneNotifications').classList.toggle('hidden', tab !== 'notifications');
        document.getElementById('paneConversations').classList.toggle('hidden', tab !== 'conversations');
        ['tabNotif','tabConv'].forEach(function(id){
            var el = document.getElementById(id);
            el.classList.remove('border-blue-600','text-blue-600','dark:text-blue-400');
            el.classList.add('border-transparent','text-zinc-500');
        });
        var activeBtn = tab === 'notifications' ? 'tabNotif' : 'tabConv';
        document.getElementById(activeBtn).classList.add('border-blue-600','text-blue-600','dark:text-blue-400');
        document.getElementById(activeBtn).classList.remove('border-transparent','text-zinc-500');
        if (tab === 'notifications') loadNotifs();
        else loadConvs();
    };

    // ─── 알림 ───
    function loadNotifs() {
        fetch(BASE + '/api/notifications.php?action=unread_summary&_t=' + Date.now(), {credentials:'same-origin'})
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (!d || !d.success) return;
                renderNotifs(d.recent || [], d.unread || 0);
            }).catch(function(){});
    }
    function renderNotifs(items, unread) {
        var list = document.getElementById('notifList');
        var empty = document.getElementById('notifEmpty');
        var summary = document.getElementById('notifSummary');
        summary.textContent = (items.length > 0)
            ? (items.length + '건' + (unread > 0 ? ' (안 읽음 ' + unread + ')' : ''))
            : '';
        if (!items.length) {
            list.innerHTML = '';
            empty.classList.remove('hidden');
            return;
        }
        empty.classList.add('hidden');
        list.innerHTML = items.map(function(n){
            var bg = n.is_read ? '' : 'bg-blue-50/50 dark:bg-blue-900/10';
            var unreadDot = n.is_read ? '' : '<span class="w-2 h-2 bg-blue-500 rounded-full inline-block mr-1.5 align-middle"></span>';
            var iconColor = n.is_read ? 'bg-gray-100 dark:bg-zinc-700 text-gray-500' : 'bg-blue-100 dark:bg-blue-900/30 text-blue-600';
            var iconSvg = (n.icon === 'warning' || n.icon === 'error')
                ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5 19h14a2 2 0 001.84-2.75L13.74 4a2 2 0 00-3.48 0L3.16 16.25A2 2 0 005 19z"/>'
                : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>';
            return '<a href="' + escHtml(n.link || '#') + '" data-nid="' + n.id + '" class="block px-6 py-3 hover:bg-gray-50 dark:hover:bg-zinc-700/30 ' + bg + '">'
                + '<div class="flex gap-3">'
                +   '<div class="flex-shrink-0 w-9 h-9 rounded-full flex items-center justify-center ' + iconColor + '">'
                +     '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">' + iconSvg + '</svg>'
                +   '</div>'
                +   '<div class="flex-1 min-w-0">'
                +     '<p class="text-sm font-semibold text-gray-900 dark:text-white truncate">' + unreadDot + escHtml(n.title) + '</p>'
                +     (n.body ? '<p class="text-xs text-gray-500 dark:text-zinc-400 line-clamp-2 mt-0.5">' + escHtml(n.body) + '</p>' : '')
                +     '<p class="text-[10px] text-gray-400 mt-1">' + relTime(n.created_at) + ' 전</p>'
                +   '</div>'
                + '</div></a>';
        }).join('');
    }
    window.markAllNotifRead = function() {
        var fd = new FormData(); fd.append('action', 'mark_all_read');
        fetch(BASE + '/api/notifications.php', {method:'POST', body: fd, credentials:'same-origin'})
            .then(function(r){ return r.json(); })
            .then(function(d){ if (d && d.success) loadNotifs(); });
    };

    // ─── 대화 목록 ───
    function loadConvs() {
        fetch(BASE + '/api/messages.php?action=conversations&_t=' + Date.now(), {credentials:'same-origin'})
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (!d || !d.success) return;
                renderConvs(d.conversations || []);
                if (INITIAL_CONV && !currentConvId) {
                    INITIAL_CONV = 0;
                    openConv(parseInt(<?= json_encode($initialConvId) ?>, 10));
                }
            }).catch(function(){});
    }
    function renderConvs(items) {
        var list = document.getElementById('convList');
        var empty = document.getElementById('convEmpty');
        if (!items.length) {
            list.innerHTML = '';
            empty.classList.remove('hidden');
            return;
        }
        empty.classList.add('hidden');
        list.innerHTML = items.map(function(c){
            var avatar = c.avatar_url
                ? '<img src="' + escHtml(c.avatar_url) + '" class="w-9 h-9 rounded-full object-cover">'
                : '<div class="w-9 h-9 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center text-sm font-bold text-zinc-600 dark:text-zinc-300">' + escHtml(initialOf(c.display_name)) + '</div>';
            var unread = parseInt(c.unread || 0, 10);
            var unreadBadge = unread > 0 ? '<span class="ml-auto inline-flex items-center px-1.5 py-0.5 rounded-full bg-red-500 text-white text-[10px] font-bold">' + (unread > 99 ? '99+' : unread) + '</span>' : '';
            var active = c.id == currentConvId ? 'bg-blue-50 dark:bg-blue-900/20' : 'hover:bg-zinc-50 dark:hover:bg-zinc-700/30';
            return '<button type="button" data-cid="' + c.id + '" onclick="openConv(' + c.id + ')" class="w-full px-3 py-2.5 flex items-start gap-2.5 border-b border-zinc-100 dark:border-zinc-700/50 transition text-left ' + active + '">'
                + avatar
                + '<div class="flex-1 min-w-0">'
                +   '<div class="flex items-center gap-1.5">'
                +     '<p class="text-sm font-semibold text-zinc-900 dark:text-white truncate">' + escHtml(c.display_name) + '</p>'
                +     unreadBadge
                +   '</div>'
                +   '<p class="text-xs text-zinc-500 dark:text-zinc-400 truncate mt-0.5">' + escHtml(c.last_preview || '') + '</p>'
                +   '<p class="text-[10px] text-zinc-400 mt-0.5">' + relTime(c.last_message_at) + '</p>'
                + '</div>'
                + '</button>';
        }).join('');
    }

    // ─── 스레드 열기 ───
    window.openConv = function(cid) {
        currentConvId = cid;
        // 좌측 active 갱신
        document.querySelectorAll('#convList button').forEach(function(b){
            b.classList.remove('bg-blue-50','dark:bg-blue-900/20');
            if (parseInt(b.dataset.cid, 10) === cid) {
                b.classList.add('bg-blue-50','dark:bg-blue-900/20');
            }
        });
        loadThread(cid);
    };

    function loadThread(cid) {
        fetch(BASE + '/api/messages.php?action=messages&conversation_id=' + cid + '&_t=' + Date.now(), {credentials:'same-origin'})
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (!d || !d.success) return;
                renderThread(d);
                // 안 읽음 카운트 갱신 (좌측 목록)
                var item = document.querySelector('#convList [data-cid="' + cid + '"]');
                if (item) {
                    var badge = item.querySelector('.bg-red-500');
                    if (badge) badge.remove();
                }
                // 헤더 종 알림 카운트 즉시 갱신 (관련 notification 도 읽음 처리됐으므로)
                if (typeof window.refreshNotifBell === 'function') window.refreshNotifBell();
                // 대화 탭 카운트 갱신
                refreshTabBadges();
            }).catch(function(){});
    }
    function refreshTabBadges() {
        // 알림 탭 + 대화 탭 헤더의 빨간 배지 재계산
        fetch(BASE + '/api/notifications.php?action=unread_summary', {credentials:'same-origin'})
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (!d || !d.success) return;
                var notifBadge = document.querySelector('#tabNotif .bg-red-500');
                if (d.unread > 0) {
                    if (notifBadge) notifBadge.textContent = d.unread > 99 ? '99+' : d.unread;
                } else if (notifBadge) {
                    notifBadge.remove();
                }
            }).catch(function(){});
    }

    function renderThread(d) {
        var head = document.getElementById('threadHeader');
        var name = document.getElementById('threadName');
        var avatar = document.getElementById('threadAvatar');
        var status = document.getElementById('threadStatus');
        var msgs = document.getElementById('threadMessages');
        var composer = document.getElementById('threadComposer');
        var empty = document.getElementById('threadEmpty');

        head.classList.remove('hidden'); head.classList.add('flex');
        composer.classList.remove('hidden'); composer.classList.add('flex');
        empty.classList.add('hidden');

        var other = d.other || {};
        name.textContent = other.display_name || '';
        status.textContent = '#' + (other.id || '').slice(0, 8);
        if (other.avatar_url) {
            avatar.innerHTML = '<img src="' + escHtml(other.avatar_url) + '" class="w-9 h-9 rounded-full object-cover">';
        } else {
            avatar.innerHTML = '<span class="text-sm font-bold text-zinc-600 dark:text-zinc-300">' + escHtml(initialOf(other.display_name)) + '</span>';
        }

        var html = '';
        var lastDate = '';
        var prevSender = null;
        var otherName = (other && other.display_name) || '';
        var myName = '나';
        (d.messages || []).forEach(function(m){
            var mine = m.sender_id === MY_ID;
            var dt = (m.sent_at || '').slice(0, 10);
            if (dt && dt !== lastDate) {
                html += '<div class="flex justify-center my-2"><span class="text-[10px] text-zinc-400 px-2 py-0.5 bg-white dark:bg-zinc-800 rounded-full">' + dt + '</span></div>';
                lastDate = dt;
                prevSender = null; // 날짜 바뀌면 sender 라벨 다시
            }
            var bubble = mine
                ? 'bg-blue-600 text-white'
                : 'bg-white dark:bg-zinc-700 text-zinc-900 dark:text-zinc-100';
            var time = (m.sent_at || '').slice(11, 16);
            var readMark = (mine && m.is_read) ? '<span class="text-[10px] text-blue-200 ml-1">✓✓</span>' : (mine ? '<span class="text-[10px] text-blue-300 ml-1">✓</span>' : '');
            // 동일 작성자 연속 메시지면 이름 생략
            var showName = m.sender_id !== prevSender;
            var senderName = mine ? myName : otherName;
            var nameLabel = showName
                ? '<p class="text-[11px] font-medium ' + (mine ? 'text-right text-blue-600 dark:text-blue-400 mr-1' : 'text-left text-zinc-600 dark:text-zinc-300 ml-1') + ' mb-0.5">' + escHtml(senderName) + '</p>'
                : '';
            var spacingTop = showName ? 'mt-3' : 'mt-1';
            html += '<div class="flex ' + (mine ? 'justify-end' : 'justify-start') + ' ' + spacingTop + '">'
                + '<div class="max-w-[70%]">'
                +   nameLabel
                +   '<div class="px-3 py-2 rounded-2xl ' + bubble + ' shadow-sm">'
                +     '<p class="text-sm whitespace-pre-wrap break-words">' + escHtml(m.body) + '</p>'
                +     '<p class="text-[10px] mt-1 ' + (mine ? 'text-blue-100' : 'text-zinc-400') + '">' + time + readMark + '</p>'
                +   '</div>'
                + '</div></div>';
            prevSender = m.sender_id;
        });
        msgs.innerHTML = html || '<div class="flex items-center justify-center h-full text-xs text-zinc-400">메시지가 없습니다</div>';
        msgs.scrollTop = msgs.scrollHeight;
    }

    // ─── 메시지 전송 ───
    window.sendMessage = function() {
        var ta = document.getElementById('composerBody');
        var body = ta.value.trim();
        if (!body || !currentConvId) return;
        var btn = document.getElementById('btnSendMsg');
        btn.disabled = true;
        var fd = new FormData();
        fd.append('action', 'send');
        fd.append('conversation_id', currentConvId);
        fd.append('body', body);
        fetch(BASE + '/api/messages.php', {method:'POST', body: fd, credentials:'same-origin'})
            .then(function(r){ return r.json(); })
            .then(function(d){
                btn.disabled = false;
                if (!d.success) { alert(d.message || '전송 실패'); return; }
                ta.value = '';
                loadThread(currentConvId);
                loadConvs(); // 좌측 목록 미리보기 갱신
            }).catch(function(){ btn.disabled = false; alert('전송 실패'); });
    };

    // ─── 새 메시지 모달 ───
    window.openNewMessageModal = function() {
        document.getElementById('newMessageModal').classList.remove('hidden');
        document.getElementById('newMsgRecipient').value = '';
        document.getElementById('newMsgBody').value = '';
        document.getElementById('recipientSelected').classList.add('hidden');
        document.getElementById('recipientResults').classList.add('hidden');
        selectedRecipientId = null;
        updateNewMsgBtn();
    };
    window.closeNewMessageModal = function() {
        document.getElementById('newMessageModal').classList.add('hidden');
    };
    var searchTimer = null;
    window.searchRecipient = function() {
        clearTimeout(searchTimer);
        var q = document.getElementById('newMsgRecipient').value.trim();
        if (q.length < 2) {
            document.getElementById('recipientResults').classList.add('hidden');
            return;
        }
        searchTimer = setTimeout(function(){
            fetch(BASE + '/api/messages.php?action=search_user&q=' + encodeURIComponent(q), {credentials:'same-origin'})
                .then(function(r){ return r.json(); })
                .then(function(d){
                    if (!d.success) return;
                    var box = document.getElementById('recipientResults');
                    if (!d.users.length) {
                        box.innerHTML = '<div class="px-3 py-3 text-xs text-zinc-400">검색 결과 없음</div>';
                    } else {
                        box.innerHTML = d.users.map(function(u){
                            var av = u.avatar_url
                                ? '<img src="' + escHtml(u.avatar_url) + '" class="w-7 h-7 rounded-full object-cover">'
                                : '<div class="w-7 h-7 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center text-xs font-bold">' + escHtml(initialOf(u.display_name)) + '</div>';
                            return '<button type="button" onclick="selectRecipient(\'' + escHtml(u.id) + '\', \'' + escHtml(u.display_name).replace(/'/g, "\\'") + '\')" class="w-full px-3 py-2 flex items-center gap-2 hover:bg-zinc-50 dark:hover:bg-zinc-700 text-left">'
                                + av
                                + '<div class="flex-1 min-w-0">'
                                +   '<p class="text-sm font-semibold text-zinc-900 dark:text-white truncate">' + escHtml(u.display_name) + '</p>'
                                +   '<p class="text-[10px] text-zinc-400 truncate">' + escHtml(u.email_masked || '') + '</p>'
                                + '</div></button>';
                        }).join('');
                    }
                    box.classList.remove('hidden');
                });
        }, 300);
    };
    window.selectRecipient = function(uid, name) {
        selectedRecipientId = uid;
        document.getElementById('recipientSelectedName').textContent = name;
        document.getElementById('recipientSelected').classList.remove('hidden');
        document.getElementById('recipientResults').classList.add('hidden');
        document.getElementById('newMsgRecipient').value = '';
        updateNewMsgBtn();
    };
    window.clearRecipient = function() {
        selectedRecipientId = null;
        document.getElementById('recipientSelected').classList.add('hidden');
        updateNewMsgBtn();
    };
    function updateNewMsgBtn() {
        var body = document.getElementById('newMsgBody').value.trim();
        document.getElementById('btnSendNewMsg').disabled = !(selectedRecipientId && body);
    }
    document.getElementById('newMsgBody').addEventListener('input', updateNewMsgBtn);
    window.sendNewMessage = function() {
        if (!selectedRecipientId) return;
        var body = document.getElementById('newMsgBody').value.trim();
        if (!body) return;
        var btn = document.getElementById('btnSendNewMsg');
        btn.disabled = true;
        var fd = new FormData();
        fd.append('action', 'send');
        fd.append('recipient_id', selectedRecipientId);
        fd.append('body', body);
        fetch(BASE + '/api/messages.php', {method:'POST', body: fd, credentials:'same-origin'})
            .then(function(r){ return r.json(); })
            .then(function(d){
                btn.disabled = false;
                if (!d.success) { alert(d.message || '전송 실패'); return; }
                closeNewMessageModal();
                switchTab('conversations');
                setTimeout(function(){ openConv(d.conversation_id); }, 300);
            }).catch(function(){ btn.disabled = false; alert('전송 실패'); });
    };

    // ─── 키보드 ───
    document.getElementById('composerBody').addEventListener('keydown', function(e){
        if (e.key === 'Enter' && (e.ctrlKey || e.metaKey)) {
            e.preventDefault();
            sendMessage();
        }
    });

    // 초기 로드 — 알림 탭은 PHP 측에서 이미 렌더링했으므로 첫 fetch 생략
    // (대화 탭은 서버 렌더링 안 했으므로 fetch 필요)
    if (currentTab === 'conversations') loadConvs();
    // 첫 노출 시 summary 만 갱신 (카운트)
    if (currentTab === 'notifications') {
        var summary = document.getElementById('notifSummary');
        var count = document.querySelectorAll('#notifList a[data-nid]').length;
        if (summary && count > 0) summary.textContent = count + '건';
        // 알림 페이지 진입 = 봤다는 의미 → 자동 모두 읽음 (1초 후)
        setTimeout(function(){
            var unreadEls = document.querySelectorAll('#notifList a[data-nid] .bg-blue-500');
            if (unreadEls.length === 0) return;
            var fd = new FormData(); fd.append('action', 'mark_all_read');
            fetch(BASE + '/api/notifications.php', {method:'POST', body: fd, credentials:'same-origin'})
                .then(function(r){ return r.json(); })
                .then(function(d){
                    if (!d || !d.success) return;
                    if (typeof window.refreshNotifBell === 'function') window.refreshNotifBell();
                    refreshTabBadges();
                });
        }, 1000);
    }
})();
</script>
