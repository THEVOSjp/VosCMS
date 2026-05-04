<?php
/**
 * 사용자 멘션 컨텍스트 메뉴 (글로벌)
 *
 * 사용법: 어떤 element 에든 다음 속성 부여:
 *   data-user-mention data-user-id="UUID" data-user-name="이름"
 * 그 element 를 좌클릭/우클릭/길게누르기 하면 메뉴가 뜸.
 *
 * 본 파일은 layout footer 1회만 include. 자동으로 document 레벨 listener 부착.
 *
 * 필요 변수: $baseUrl
 * 인증: 일부 액션 (팔로우, 메시지) 은 비로그인 시 로그인 페이지로.
 */
if (!isset($baseUrl)) $baseUrl = '';
require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
$_umIsLoggedIn = \RzxLib\Core\Auth\Auth::check();
$_umMyId = $_umIsLoggedIn ? (\RzxLib\Core\Auth\Auth::user()['id'] ?? '') : '';
?>
<!-- 사용자 멘션 컨텍스트 메뉴 -->
<div id="userMentionMenu" class="hidden fixed z-[10000] min-w-[180px] bg-white dark:bg-zinc-800 rounded-lg shadow-2xl border border-zinc-200 dark:border-zinc-700 overflow-hidden text-sm" role="menu">
    <div id="umHeader" class="px-3 py-2 border-b dark:border-zinc-700 bg-zinc-50 dark:bg-zinc-900/50 flex items-center gap-2">
        <div id="umAvatar" class="w-7 h-7 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center text-xs font-bold"></div>
        <span id="umName" class="text-sm font-semibold text-zinc-900 dark:text-white truncate flex-1"></span>
    </div>
    <button type="button" data-um-action="profile" class="w-full px-3 py-2 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-700 flex items-center gap-2">
        <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
        프로필 보기
    </button>
    <button type="button" data-um-action="follow" class="w-full px-3 py-2 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-700 flex items-center gap-2">
        <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18 9v3m0 0v3m0-3h3m-3 0h-3m-2-5a4 4 0 11-8 0 4 4 0 018 0zM3 20a6 6 0 0112 0v1H3v-1z"/></svg>
        <span id="umFollowLabel">+ 팔로우</span>
    </button>
    <button type="button" data-um-action="message" class="w-full px-3 py-2 text-left text-sm hover:bg-zinc-100 dark:hover:bg-zinc-700 flex items-center gap-2">
        <svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/></svg>
        메시지 보내기
    </button>
</div>

<!-- 메시지 작성 모달 (글로벌 재사용) -->
<div id="umMessageModal" class="hidden fixed inset-0 z-[10001] flex items-center justify-center bg-black/50 p-4">
    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl w-full max-w-md p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-zinc-900 dark:text-white"><span id="umMsgTo"></span> 님에게 메시지</h3>
            <button type="button" data-um-action="close-msg" class="text-zinc-400 hover:text-zinc-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <textarea id="umMsgBody" rows="6" maxlength="5000" placeholder="메시지를 입력하세요..." class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 dark:text-white resize-none mb-3"></textarea>
        <div class="flex justify-end gap-2">
            <button type="button" data-um-action="close-msg" class="px-4 py-2 text-xs text-zinc-500 hover:text-zinc-700">취소</button>
            <button type="button" data-um-action="send-msg" id="umMsgSendBtn" class="px-4 py-2 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">보내기</button>
        </div>
    </div>
</div>

<script>
(function(){
    if (window.__userMentionMenuInit) return; window.__userMentionMenuInit = true;
    var BASE = <?= json_encode($baseUrl) ?>;
    var IS_LOGGED_IN = <?= $_umIsLoggedIn ? 'true' : 'false' ?>;
    var MY_ID = <?= json_encode($_umMyId) ?>;

    var menu = document.getElementById('userMentionMenu');
    var msgModal = document.getElementById('umMessageModal');
    var headerName = document.getElementById('umName');
    var headerAvatar = document.getElementById('umAvatar');
    var followLabel = document.getElementById('umFollowLabel');

    var ctx = { userId: '', userName: '', avatar: '', isFollowing: false };
    var longPressTimer = null;

    function escHtml(s) { return (s || '').replace(/[&<>"']/g, function(c){ return {'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]; }); }

    function showMenu(target, x, y) {
        ctx.userId = target.dataset.userId || '';
        ctx.userName = target.dataset.userName || '';
        ctx.avatar = target.dataset.userAvatar || '';
        if (!ctx.userId) return;

        // 본인 자신이면 메뉴 의미 없음
        if (MY_ID && MY_ID === ctx.userId) return;

        headerName.textContent = ctx.userName || ctx.userId.slice(0, 8);
        if (ctx.avatar) {
            headerAvatar.innerHTML = '<img src="' + escHtml(ctx.avatar) + '" class="w-7 h-7 rounded-full object-cover">';
        } else {
            headerAvatar.innerHTML = '<span class="text-zinc-600 dark:text-zinc-300">' + escHtml((ctx.userName || '?').charAt(0).toUpperCase()) + '</span>';
        }

        // 위치 조정 — 화면 밖으로 안 나가게
        var w = 200, h = 160;
        var vw = window.innerWidth, vh = window.innerHeight;
        if (x + w > vw) x = vw - w - 10;
        if (y + h > vh) y = vh - h - 10;
        menu.style.left = x + 'px';
        menu.style.top = y + 'px';
        menu.classList.remove('hidden');

        // 팔로우 상태 비동기 로드
        followLabel.textContent = '...';
        if (IS_LOGGED_IN) {
            fetch(BASE + '/api/follows.php?action=status&target_id=' + encodeURIComponent(ctx.userId), {credentials:'same-origin'})
                .then(function(r){ return r.json(); })
                .then(function(d){
                    if (!d.success) { followLabel.textContent = '+ 팔로우'; return; }
                    ctx.isFollowing = !!d.is_following;
                    followLabel.textContent = ctx.isFollowing ? '✓ 팔로잉' : '+ 팔로우';
                })
                .catch(function(){ followLabel.textContent = '+ 팔로우'; });
        } else {
            followLabel.textContent = '+ 팔로우';
        }
    }

    function hideMenu() {
        menu.classList.add('hidden');
    }

    // 우클릭 (contextmenu)
    document.addEventListener('contextmenu', function(e){
        var t = e.target.closest('[data-user-mention]');
        if (!t) return;
        e.preventDefault();
        showMenu(t, e.clientX, e.clientY);
    });

    // 좌클릭
    document.addEventListener('click', function(e){
        // 메뉴 자체 클릭이면 하위 핸들러로 위임
        if (e.target.closest('#userMentionMenu')) return;
        // 멘션 element 클릭
        var t = e.target.closest('[data-user-mention]');
        if (t) {
            e.preventDefault();
            var rect = t.getBoundingClientRect();
            showMenu(t, rect.left, rect.bottom + 4);
            return;
        }
        // 외부 클릭 → 메뉴 닫기
        hideMenu();
    });

    // 길게누르기 (모바일)
    document.addEventListener('touchstart', function(e){
        var t = e.target.closest('[data-user-mention]');
        if (!t) return;
        clearTimeout(longPressTimer);
        var touch = e.touches[0];
        longPressTimer = setTimeout(function(){
            e.preventDefault();
            showMenu(t, touch.clientX, touch.clientY);
        }, 500);
    }, {passive: true});
    document.addEventListener('touchend', function(){
        clearTimeout(longPressTimer);
    }, {passive: true});
    document.addEventListener('touchmove', function(){
        clearTimeout(longPressTimer);
    }, {passive: true});

    // ESC
    document.addEventListener('keydown', function(e){
        if (e.key === 'Escape') hideMenu();
    });

    // 메뉴 액션
    menu.addEventListener('click', function(e){
        var btn = e.target.closest('[data-um-action]');
        if (!btn) return;
        var act = btn.dataset.umAction;
        if (act === 'profile') {
            location.href = BASE + '/profile/' + ctx.userId;
            hideMenu();
        } else if (act === 'follow') {
            if (!IS_LOGGED_IN) { location.href = BASE + '/login'; return; }
            doFollow();
        } else if (act === 'message') {
            if (!IS_LOGGED_IN) { location.href = BASE + '/login'; return; }
            openMsgModal();
        }
    });

    function doFollow() {
        var fd = new FormData();
        fd.append('action', ctx.isFollowing ? 'unfollow' : 'follow');
        fd.append('target_id', ctx.userId);
        followLabel.textContent = '...';
        fetch(BASE + '/api/follows.php', {method:'POST', body: fd, credentials:'same-origin'})
            .then(function(r){ return r.json(); })
            .then(function(d){
                if (!d.success) { alert(d.message || '실패'); return; }
                ctx.isFollowing = !!d.is_following;
                followLabel.textContent = ctx.isFollowing ? '✓ 팔로잉' : '+ 팔로우';
            }).catch(function(){ alert('네트워크 오류'); });
    }

    function openMsgModal() {
        document.getElementById('umMsgTo').textContent = ctx.userName;
        document.getElementById('umMsgBody').value = '';
        msgModal.classList.remove('hidden');
        hideMenu();
        setTimeout(function(){ document.getElementById('umMsgBody').focus(); }, 50);
    }
    function closeMsgModal() {
        msgModal.classList.add('hidden');
    }

    msgModal.addEventListener('click', function(e){
        var btn = e.target.closest('[data-um-action]');
        if (!btn) {
            if (e.target === msgModal) closeMsgModal();
            return;
        }
        var act = btn.dataset.umAction;
        if (act === 'close-msg') closeMsgModal();
        else if (act === 'send-msg') sendMsg();
    });

    function sendMsg() {
        var body = document.getElementById('umMsgBody').value.trim();
        if (!body) return;
        var btn = document.getElementById('umMsgSendBtn');
        btn.disabled = true;
        var fd = new FormData();
        fd.append('action', 'send');
        fd.append('recipient_id', ctx.userId);
        fd.append('body', body);
        fetch(BASE + '/api/messages.php', {method:'POST', body: fd, credentials:'same-origin'})
            .then(function(r){ return r.json(); })
            .then(function(d){
                btn.disabled = false;
                if (!d.success) { alert(d.message || '전송 실패'); return; }
                alert('메시지를 보냈습니다');
                closeMsgModal();
            }).catch(function(){ btn.disabled = false; alert('네트워크 오류'); });
    }

    // 멘션 element 들에 cursor 표시 + 호버 underline
    var style = document.createElement('style');
    style.textContent = '[data-user-mention]{cursor:pointer;text-decoration:underline;text-decoration-style:dotted;text-decoration-color:#9ca3af;text-underline-offset:3px}[data-user-mention]:hover{color:#2563eb}';
    document.head.appendChild(style);
})();
</script>
