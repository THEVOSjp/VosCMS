<?php
/**
 * RezlyX Admin - 스태프 관리 JavaScript
 */
?>
<script>
(function() {
    'use strict';

    var searchTimer = null;

    function showAlert(msg, type) {
        var box = document.getElementById('alertBox');
        box.className = 'mb-6 p-4 rounded-lg border ' +
            (type === 'success'
                ? 'bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-300 border-green-200 dark:border-green-800'
                : 'bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-300 border-red-200 dark:border-red-800');
        box.textContent = msg;
        box.classList.remove('hidden');
        setTimeout(function() { box.classList.add('hidden'); }, 4000);
        console.log('[Staff] Alert:', type, msg);
    }

    function postData(formData) {
        return fetch(window.location.pathname, {
            method: 'POST',
            body: formData
        }).then(function(r) { return r.json(); });
    }

    // ─── 회원 검색 ───
    function initMemberSearch() {
        var input = document.getElementById('memberSearch');
        var results = document.getElementById('memberSearchResults');
        if (!input || !results) return;

        input.addEventListener('input', function() {
            var q = this.value.trim();
            clearTimeout(searchTimer);
            if (q.length < 1) {
                results.classList.add('hidden');
                return;
            }
            searchTimer = setTimeout(function() {
                console.log('[Staff] Searching members:', q);
                var fd = new FormData();
                fd.append('action', 'search_members');
                fd.append('q', q);
                postData(fd).then(function(data) {
                    if (data.success && data.members) {
                        renderMemberResults(data.members);
                    }
                }).catch(function(err) {
                    console.error('[Staff] Member search error:', err);
                });
            }, 300);
        });

        input.addEventListener('focus', function() {
            if (this.value.trim().length >= 1 && results.children.length > 0) {
                results.classList.remove('hidden');
            }
        });

        document.addEventListener('click', function(e) {
            if (!e.target.closest('#memberSearchWrap')) {
                results.classList.add('hidden');
            }
        });
    }

    function renderMemberResults(members) {
        var results = document.getElementById('memberSearchResults');
        results.innerHTML = '';
        if (members.length === 0) {
            results.innerHTML = '<div class="px-3 py-2 text-sm text-zinc-400"><?= __('admin.staff.no_member_found') ?></div>';
            results.classList.remove('hidden');
            return;
        }
        members.forEach(function(m) {
            var div = document.createElement('div');
            div.className = 'px-3 py-2 hover:bg-zinc-100 dark:hover:bg-zinc-600 cursor-pointer flex items-center gap-2';
            div.innerHTML = '<div class="w-7 h-7 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-purple-600 text-xs font-semibold">' +
                (m.name ? m.name.charAt(0) : '?') + '</div>' +
                '<div class="flex-1 min-w-0"><div class="text-sm font-medium text-zinc-900 dark:text-white truncate">' +
                escapeHtml(m.name) + '</div><div class="text-xs text-zinc-500 dark:text-zinc-400 truncate">' +
                escapeHtml(m.email || '') + (m.phone ? ' · ' + escapeHtml(m.phone) : '') + '</div></div>';
            div.addEventListener('click', function() {
                selectMember(m);
            });
            results.appendChild(div);
        });
        results.classList.remove('hidden');
        console.log('[Staff] Found', members.length, 'members');
    }

    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    window.selectMember = function(m) {
        document.getElementById('staffUserId').value = m.id;
        document.getElementById('staffName').value = m.name || '';
        document.getElementById('staffEmail').value = m.email || '';
        document.getElementById('staffPhone').value = m.phone || '';

        var display = document.getElementById('linkedMemberDisplay');
        document.getElementById('linkedMemberName').textContent = m.name + ' (' + (m.email || m.id) + ')';
        display.classList.remove('hidden');
        display.classList.add('flex');
        document.getElementById('memberSearchWrap').classList.add('hidden');
        document.getElementById('memberSearchResults').classList.add('hidden');
        document.getElementById('memberSearch').value = '';
        console.log('[Staff] Member selected:', m.id, m.name);
    };

    window.unlinkMember = function() {
        document.getElementById('staffUserId').value = '';
        document.getElementById('linkedMemberDisplay').classList.add('hidden');
        document.getElementById('linkedMemberDisplay').classList.remove('flex');
        document.getElementById('memberSearchWrap').classList.remove('hidden');
        console.log('[Staff] Member unlinked');
    };

    // ─── 모달 ───
    window.openStaffModal = function() {
        document.getElementById('staffModalTitle').textContent = '<?= __('admin.staff.create') ?>';
        document.getElementById('staffAction').value = 'create_staff';
        document.getElementById('staffId').value = '';
        document.getElementById('staffUserId').value = '';
        document.getElementById('staffName').value = '';
        document.getElementById('staffEmail').value = '';
        document.getElementById('staffPhone').value = '';
        document.getElementById('staffBio').value = '';
        document.getElementById('staffCardNumber').value = '';
        document.getElementById('staffActive').checked = true;
        document.querySelectorAll('.staff-svc-checkbox').forEach(function(cb) { cb.checked = false; });
        // 회원 연동 초기화
        document.getElementById('linkedMemberDisplay').classList.add('hidden');
        document.getElementById('linkedMemberDisplay').classList.remove('flex');
        document.getElementById('memberSearchWrap').classList.remove('hidden');
        document.getElementById('memberSearch').value = '';
        document.getElementById('staffModal').classList.remove('hidden');
        console.log('[Staff] Modal opened (create)');
    };

    window.editStaff = function(st, svcIds) {
        document.getElementById('staffModalTitle').textContent = '<?= __('admin.staff.edit') ?>';
        document.getElementById('staffAction').value = 'update_staff';
        document.getElementById('staffId').value = st.id;
        document.getElementById('staffUserId').value = st.user_id || '';
        document.getElementById('staffName').value = st.name || '';
        document.getElementById('staffEmail').value = st.email || '';
        document.getElementById('staffPhone').value = st.phone || '';
        document.getElementById('staffBio').value = st.bio || '';
        document.getElementById('staffCardNumber').value = st.card_number || '';
        document.getElementById('staffActive').checked = st.is_active == 1;
        document.querySelectorAll('.staff-svc-checkbox').forEach(function(cb) {
            cb.checked = svcIds.indexOf(cb.value) !== -1;
        });
        // 회원 연동 표시
        if (st.user_id) {
            document.getElementById('linkedMemberName').textContent = st.name + ' (' + (st.email || st.user_id) + ')';
            document.getElementById('linkedMemberDisplay').classList.remove('hidden');
            document.getElementById('linkedMemberDisplay').classList.add('flex');
            document.getElementById('memberSearchWrap').classList.add('hidden');
        } else {
            document.getElementById('linkedMemberDisplay').classList.add('hidden');
            document.getElementById('linkedMemberDisplay').classList.remove('flex');
            document.getElementById('memberSearchWrap').classList.remove('hidden');
        }
        document.getElementById('memberSearch').value = '';
        document.getElementById('staffModal').classList.remove('hidden');
        console.log('[Staff] Modal opened (edit):', st.id);
    };

    window.closeStaffModal = function() {
        document.getElementById('staffModal').classList.add('hidden');
        console.log('[Staff] Modal closed');
    };

    window.saveStaff = function() {
        var name = document.getElementById('staffName').value.trim();
        if (!name) {
            document.getElementById('staffName').focus();
            return;
        }

        var form = document.getElementById('staffForm');
        var formData = new FormData(form);
        if (!document.getElementById('staffActive').checked) {
            formData.delete('is_active');
        }
        console.log('[Staff] Saving:', formData.get('action'), name, 'user_id:', formData.get('user_id'));

        postData(formData).then(function(data) {
            if (data.success) {
                showAlert(data.message, 'success');
                closeStaffModal();
                setTimeout(function() { location.reload(); }, 800);
            } else {
                showAlert(data.message || '<?= __('admin.staff.error.generic') ?>', 'error');
            }
        }).catch(function(err) {
            console.error('[Staff] Save error:', err);
            showAlert('<?= __('admin.staff.error.server') ?>', 'error');
        });
    };

    window.deleteStaff = function(id) {
        if (!confirm('<?= __('admin.staff.confirm_delete') ?>')) return;
        console.log('[Staff] Deleting:', id);

        var formData = new FormData();
        formData.append('action', 'delete_staff');
        formData.append('id', id);

        postData(formData).then(function(data) {
            if (data.success) {
                showAlert(data.message, 'success');
                var row = document.getElementById('staff-' + id);
                if (row) row.remove();
            } else {
                showAlert(data.message || 'Error', 'error');
            }
        }).catch(function(err) {
            console.error('[Staff] Delete error:', err);
            showAlert('<?= __('admin.staff.error.server') ?>', 'error');
        });
    };

    window.toggleStaff = function(id) {
        console.log('[Staff] Toggling:', id);
        var formData = new FormData();
        formData.append('action', 'toggle_staff');
        formData.append('id', id);

        postData(formData).then(function(data) {
            if (data.success) {
                var btn = document.getElementById('toggle-' + id);
                if (btn) {
                    var isActive = btn.dataset.active === '1' ? '0' : '1';
                    btn.dataset.active = isActive;
                    var dot = btn.querySelector('span');
                    if (isActive === '1') {
                        btn.classList.remove('bg-zinc-300', 'dark:bg-zinc-600');
                        btn.classList.add('bg-green-500');
                        dot.classList.remove('translate-x-1');
                        dot.classList.add('translate-x-6');
                    } else {
                        btn.classList.remove('bg-green-500');
                        btn.classList.add('bg-zinc-300', 'dark:bg-zinc-600');
                        dot.classList.remove('translate-x-6');
                        dot.classList.add('translate-x-1');
                    }
                }
                console.log('[Staff] Toggle result:', isActive);
            }
        }).catch(function(err) {
            console.error('[Staff] Toggle error:', err);
        });
    };

    // 초기화
    initMemberSearch();
    console.log('[Staff] Page initialized');
})();
</script>
