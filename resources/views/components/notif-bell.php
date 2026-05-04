<?php
/**
 * 알림 종 아이콘 + 안 읽은 카운트 배지 + 드롭다운
 * 헤더에 include — 로그인 상태일 때만 호출
 *
 * 필요 변수: $baseUrl
 */
if (!isset($isLoggedIn) || !$isLoggedIn) return;
?>
<div class="relative">
    <button id="notifBellBtn" type="button" class="relative p-2 text-gray-700 dark:text-zinc-300 hover:text-blue-600 dark:hover:text-blue-400 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-700" aria-label="알림">
        <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/></svg>
        <span id="notifBellBadge" class="hidden absolute -top-0.5 -right-0.5 min-w-[16px] h-4 px-1 bg-red-500 text-white text-[10px] font-bold rounded-full flex items-center justify-center leading-none">0</span>
    </button>
    <div id="notifBellDropdown" class="hidden absolute right-0 mt-2 w-80 bg-white dark:bg-zinc-800 rounded-lg shadow-xl border dark:border-zinc-700 overflow-hidden z-[60]">
        <div class="px-4 py-3 border-b dark:border-zinc-700 flex items-center justify-between">
            <p class="text-sm font-bold text-gray-900 dark:text-white"><?= __('auth.mypage.menu.messages') ?></p>
            <a href="<?= $baseUrl ?>/mypage/messages" class="text-xs text-blue-600 dark:text-blue-400 hover:underline"><?= __('common.buttons.view_all') ?></a>
        </div>
        <div id="notifBellList" class="max-h-96 overflow-y-auto"></div>
        <div id="notifBellEmpty" class="hidden p-8 text-center text-xs text-gray-400">알림이 없습니다</div>
    </div>
</div>
<script>
(function(){
    if (window.__notifBellInit) return; window.__notifBellInit = true;
    var btn = document.getElementById('notifBellBtn');
    var dd  = document.getElementById('notifBellDropdown');
    var badge = document.getElementById('notifBellBadge');
    var list = document.getElementById('notifBellList');
    var empty = document.getElementById('notifBellEmpty');
    if (!btn || !dd) return;
    var BASE = <?= json_encode($baseUrl) ?>;
    var POLL_MS = 60000;
    var lastFetch = 0;
    var dropdownOpen = false;

    function escHtml(s) { return (s || '').replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }
    function relTime(iso) {
        if (!iso) return '';
        var d = new Date(iso.replace(' ', 'T') + 'Z');
        var s = Math.floor((Date.now() - d.getTime()) / 1000);
        if (s < 60) return '방금 전';
        if (s < 3600) return Math.floor(s / 60) + '분 전';
        if (s < 86400) return Math.floor(s / 3600) + '시간 전';
        if (s < 604800) return Math.floor(s / 86400) + '일 전';
        return d.toISOString().slice(0, 10);
    }
    function renderItems(items) {
        if (!items || !items.length) {
            list.innerHTML = '';
            empty.classList.remove('hidden');
            return;
        }
        empty.classList.add('hidden');
        list.innerHTML = items.map(function(n){
            var iconColor = n.is_read ? 'bg-gray-100 dark:bg-zinc-700 text-gray-500' : 'bg-blue-100 dark:bg-blue-900/30 text-blue-600';
            var iconSvg = (n.icon === 'warning' || n.icon === 'error')
                ? '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01M5 19h14a2 2 0 001.84-2.75L13.74 4a2 2 0 00-3.48 0L3.16 16.25A2 2 0 005 19z"/>'
                : '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 17h5l-1.405-1.405A2.032 2.032 0 0118 14.158V11a6.002 6.002 0 00-4-5.659V5a2 2 0 10-4 0v.341C7.67 6.165 6 8.388 6 11v3.159c0 .538-.214 1.055-.595 1.436L4 17h5m6 0v1a3 3 0 11-6 0v-1m6 0H9"/>';
            var unreadDot = n.is_read ? '' : '<span class="w-1.5 h-1.5 bg-blue-500 rounded-full inline-block mr-1 align-middle"></span>';
            var bg = n.is_read ? '' : 'bg-blue-50/50 dark:bg-blue-900/10';
            return '<a href="' + escHtml(n.link || (BASE + '/mypage/messages')) + '" class="block px-4 py-3 hover:bg-gray-50 dark:hover:bg-zinc-700/50 border-b border-gray-100 dark:border-zinc-700/50 ' + bg + '" data-nid="' + n.id + '">'
                + '<div class="flex gap-3">'
                +   '<div class="flex-shrink-0 w-8 h-8 rounded-full flex items-center justify-center ' + iconColor + '">'
                +     '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">' + iconSvg + '</svg>'
                +   '</div>'
                +   '<div class="flex-1 min-w-0">'
                +     '<p class="text-xs font-semibold text-gray-900 dark:text-white truncate">' + unreadDot + escHtml(n.title) + '</p>'
                +     (n.body ? '<p class="text-[11px] text-gray-500 dark:text-zinc-400 line-clamp-2 mt-0.5">' + escHtml(n.body) + '</p>' : '')
                +     '<p class="text-[10px] text-gray-400 mt-1">' + relTime(n.created_at) + '</p>'
                +   '</div>'
                + '</div>'
                + '</a>';
        }).join('');
    }
    function fetchNotifs() {
        if (Date.now() - lastFetch < 5000) return;
        lastFetch = Date.now();
        fetch(BASE + '/api/notifications.php?action=unread_summary', {credentials: 'same-origin'})
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (!d || !d.success) return;
                if (d.unread > 0) {
                    badge.textContent = d.unread > 99 ? '99+' : d.unread;
                    badge.classList.remove('hidden');
                } else {
                    badge.classList.add('hidden');
                }
                if (dropdownOpen) renderItems(d.recent || []);
            })
            .catch(function(){});
    }
    btn.addEventListener('click', function(e){
        e.stopPropagation();
        dropdownOpen = !dropdownOpen;
        dd.classList.toggle('hidden', !dropdownOpen);
        if (dropdownOpen) fetchNotifs();
    });
    document.addEventListener('click', function(e){
        if (dropdownOpen && !dd.contains(e.target) && !btn.contains(e.target)) {
            dropdownOpen = false;
            dd.classList.add('hidden');
        }
    });
    fetchNotifs();
    setInterval(fetchNotifs, POLL_MS);

    // 외부에서 강제 갱신 가능 (메시지 읽음 처리 후 등)
    window.refreshNotifBell = function() {
        lastFetch = 0; // cooldown 무시
        fetchNotifs();
    };
})();
</script>
