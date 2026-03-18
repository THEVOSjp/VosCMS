<?php
/**
 * 공용 사용자 검색 컴포넌트
 *
 * 사용법:
 *   $userSearchId = 'boardAdmin';  // 고유 ID (같은 페이지에 여러 개 사용 가능)
 *   $userSearchPlaceholder = '이름 또는 이메일로 검색';
 *   $userSearchOnSelect = 'onAdminSelected(user)'; // 선택 시 콜백 JS 함수명
 *   include '.../components/user-search.php';
 *
 * 콜백 함수는 { id, name, email, phone, avatar } 객체를 받습니다.
 */
$_usId = $userSearchId ?? 'userSearch';
$_usPlaceholder = $userSearchPlaceholder ?? __('admin.common.search_user') ?? '이름 또는 이메일로 검색';
$_usCallback = $userSearchOnSelect ?? '';
$_usInp = 'w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200';
?>
<div id="<?= $_usId ?>Wrap" class="relative">
    <input type="text" id="<?= $_usId ?>Input"
           class="<?= $_usInp ?>" autocomplete="off"
           placeholder="<?= htmlspecialchars($_usPlaceholder) ?>">
    <div id="<?= $_usId ?>Results"
         class="hidden absolute z-20 w-full mt-1 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-600 rounded-lg shadow-lg max-h-48 overflow-y-auto"></div>
</div>

<script>
(function() {
    const ID = '<?= $_usId ?>';
    const CALLBACK = '<?= $_usCallback ?>';
    const API_URL = '<?= ($adminUrl ?? '') ?>/api/search-users';
    let cache = null;
    let timer = null;
    let bound = false;

    function loadAll(cb) {
        if (cache) { cb(cache); return; }
        console.log('[UserSearch:' + ID + '] Loading all users...');
        const fd = new FormData();
        fd.append('action', 'search_members');
        fd.append('q', '*');
        // 스태프 API에서 전체 회원 로드 (암호화 복호화 포함)
        fetch('<?= ($adminUrl ?? '') ?>/staff', { method: 'POST', body: fd })
            .then(r => r.json())
            .then(data => {
                if (data.success && data.members) {
                    cache = data.members;
                    console.log('[UserSearch:' + ID + '] Loaded', cache.length, 'users');
                    cb(cache);
                }
            })
            .catch(err => console.error('[UserSearch:' + ID + '] Load error:', err));
    }

    function filter(q) {
        if (!cache) return [];
        if (!q) return cache.slice(0, 20);
        q = q.toLowerCase();
        return cache.filter(m =>
            (m.name && m.name.toLowerCase().includes(q)) ||
            (m.email && m.email.toLowerCase().includes(q)) ||
            (m.phone && m.phone.toLowerCase().includes(q))
        ).slice(0, 20);
    }

    function escHtml(s) {
        const d = document.createElement('div');
        d.textContent = s || '';
        return d.innerHTML;
    }

    function highlight(text, q) {
        if (!q || !text) return escHtml(text);
        const e = escHtml(text);
        const eq = escHtml(q);
        const re = new RegExp('(' + eq.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + ')', 'gi');
        return e.replace(re, '<mark class="bg-yellow-200 dark:bg-yellow-700 rounded px-0.5">$1</mark>');
    }

    function render(users, q) {
        const el = document.getElementById(ID + 'Results');
        el.innerHTML = '';
        if (!users.length) {
            el.innerHTML = '<div class="px-3 py-2 text-sm text-zinc-400">검색 결과 없음</div>';
            el.classList.remove('hidden');
            return;
        }
        users.forEach(u => {
            const div = document.createElement('div');
            div.className = 'px-3 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-700 cursor-pointer flex items-center gap-2';
            const initial = u.name ? u.name.charAt(0) : '?';
            const avatar = u.avatar
                ? `<img src="${u.avatar}" class="w-7 h-7 rounded-full object-cover shrink-0" onerror="this.style.display='none'">`
                : `<div class="w-7 h-7 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center text-blue-600 text-xs font-semibold shrink-0">${escHtml(initial)}</div>`;
            div.innerHTML = avatar +
                `<div class="flex-1 min-w-0">
                    <div class="text-sm font-medium text-zinc-900 dark:text-white truncate">${highlight(u.name, q)}</div>
                    <div class="text-xs text-zinc-500 dark:text-zinc-400 truncate">${highlight(u.email, q)}${u.phone ? ' · ' + escHtml(u.phone) : ''}</div>
                </div>`;
            div.addEventListener('click', () => {
                document.getElementById(ID + 'Input').value = u.name || u.email;
                document.getElementById(ID + 'Results').classList.add('hidden');
                if (CALLBACK && typeof window[CALLBACK] === 'function') {
                    window[CALLBACK](u);
                }
                console.log('[UserSearch:' + ID + '] Selected:', u.id, u.name);
            });
            el.appendChild(div);
        });
        el.classList.remove('hidden');
    }

    function init() {
        if (bound) return;
        const input = document.getElementById(ID + 'Input');
        const results = document.getElementById(ID + 'Results');
        if (!input || !results) return;
        bound = true;

        input.addEventListener('input', function() {
            const q = this.value.trim();
            clearTimeout(timer);
            timer = setTimeout(() => {
                const filtered = filter(q);
                render(filtered, q);
            }, 100);
        });

        input.addEventListener('focus', function() {
            const self = this;
            loadAll(() => {
                render(filter(self.value.trim()), self.value.trim());
            });
        });

        document.addEventListener('click', e => {
            if (!e.target.closest('#' + ID + 'Wrap')) {
                results.classList.add('hidden');
            }
        });

        input.addEventListener('keydown', e => {
            if (e.key === 'Escape') results.classList.add('hidden');
        });
    }

    // DOM 로드 후 초기화
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', init);
    } else {
        init();
    }
})();
</script>
