<script>
(function() {
    'use strict';

    // === 모달 열기/닫기 ===
    window.openAddModal = function() {
        document.getElementById('addModal').classList.remove('hidden');
        // 초기화
        var sel = document.getElementById('addStaffId');
        if (sel) sel.value = '';
        document.getElementById('addRole').value = 'staff';
        uncheckAll('add');
        console.log('[Admins] Add modal opened');
    };
    window.closeAddModal = function() {
        document.getElementById('addModal').classList.add('hidden');
    };

    window.openEditModal = function(adminId, name, role, perms) {
        document.getElementById('editModal').classList.remove('hidden');
        document.getElementById('editAdminId').value = adminId;
        document.getElementById('editAdminName').textContent = name;
        document.getElementById('editRole').value = role;

        // 체크박스 설정
        uncheckAll('edit');
        if (Array.isArray(perms)) {
            perms.forEach(function(p) {
                var cb = document.querySelector('.perm-cb-edit[data-perm="' + p + '"]');
                if (cb) cb.checked = true;
            });
        }
        updateAllCheckbox('edit');
        console.log('[Admins] Edit modal opened:', adminId, role, perms);
    };
    window.closeEditModal = function() {
        document.getElementById('editModal').classList.add('hidden');
    };

    // === 권한 체크박스 헬퍼 ===
    function uncheckAll(pfx) {
        document.querySelectorAll('.perm-cb-' + pfx).forEach(function(cb) { cb.checked = false; });
        var allCb = document.querySelector('.perm-all-' + pfx);
        if (allCb) allCb.checked = false;
    }

    function getSelectedPerms(pfx) {
        var perms = [];
        document.querySelectorAll('.perm-cb-' + pfx + ':checked').forEach(function(cb) {
            perms.push(cb.value);
        });
        return perms;
    }

    function updateAllCheckbox(pfx) {
        var all = document.querySelectorAll('.perm-cb-' + pfx);
        var checked = document.querySelectorAll('.perm-cb-' + pfx + ':checked');
        var allCb = document.querySelector('.perm-all-' + pfx);
        if (allCb) allCb.checked = (all.length > 0 && all.length === checked.length);
    }

    window.toggleAllPerms = function(pfx) {
        var allCb = document.querySelector('.perm-all-' + pfx);
        var checked = allCb ? allCb.checked : false;
        document.querySelectorAll('.perm-cb-' + pfx).forEach(function(cb) { cb.checked = checked; });
        console.log('[Admins] Toggle all perms:', pfx, checked);
    };

    // 개별 체크박스 변경 시 전체선택 동기화
    document.querySelectorAll('.perm-cb-add, .perm-cb-edit').forEach(function(cb) {
        cb.addEventListener('change', function() {
            var pfx = cb.classList.contains('perm-cb-add') ? 'add' : 'edit';
            updateAllCheckbox(pfx);
        });
    });

    // 역할 변경 시 매니저는 기본 권한 자동선택
    window.onRoleChange = function(pfx) {
        var role = document.getElementById(pfx === 'add' ? 'addRole' : 'editRole').value;
        if (role === 'manager') {
            // 매니저: 대부분 권한 자동 선택
            var defaults = ['dashboard','reservations','counter','services','staff','staff.schedule','staff.attendance','members','site','site.pages'];
            uncheckAll(pfx);
            defaults.forEach(function(p) {
                var cb = document.querySelector('.perm-cb-' + pfx + '[data-perm="' + p + '"]');
                if (cb) cb.checked = true;
            });
            updateAllCheckbox(pfx);
            console.log('[Admins] Role changed to manager, default perms applied');
        }
    };

    // === AJAX 요청 ===
    function postAction(data, onSuccess) {
        var fd = new FormData();
        for (var k in data) fd.append(k, data[k]);

        fetch(window.location.href, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(res) {
                if (res.error) {
                    alert(res.error);
                    console.log('[Admins] Error:', res.error);
                } else {
                    console.log('[Admins] Success:', res.message || 'OK');
                    if (onSuccess) onSuccess(res);
                    else location.reload();
                }
            })
            .catch(function(e) {
                alert('요청 실패: ' + e.message);
                console.error('[Admins] Fetch error:', e);
            });
    }

    // === 관리자 추가 ===
    window.submitAddAdmin = function() {
        var staffId = document.getElementById('addStaffId');
        if (!staffId || !staffId.value) {
            alert('스태프를 선택해주세요.');
            return;
        }
        var role = document.getElementById('addRole').value;
        var perms = getSelectedPerms('add');

        postAction({
            action: 'add_admin',
            staff_id: staffId.value,
            role: role,
            permissions: JSON.stringify(perms)
        });
    };

    // === 권한 편집 저장 ===
    window.submitEditAdmin = function() {
        var adminId = document.getElementById('editAdminId').value;
        var role = document.getElementById('editRole').value;
        var perms = getSelectedPerms('edit');

        postAction({
            action: 'update_permissions',
            admin_id: adminId,
            role: role,
            permissions: JSON.stringify(perms)
        });
    };

    // === 상태 토글 ===
    window.toggleAdminStatus = function(adminId, newStatus) {
        var msg = newStatus === 'inactive' ? '이 관리자를 비활성화하시겠습니까?' : '이 관리자를 활성화하시겠습니까?';
        if (!confirm(msg)) return;

        postAction({
            action: 'toggle_status',
            admin_id: adminId,
            status: newStatus
        });
    };

    // === 관리자 해제 ===
    window.removeAdmin = function(adminId, name) {
        if (!confirm(name + '님의 관리자 권한을 해제하시겠습니까?\n이 스태프는 더 이상 관리자 페이지에 접근할 수 없습니다.')) return;

        postAction({
            action: 'remove_admin',
            admin_id: adminId
        });
    };

    console.log('[Admins] Admin management page ready');
})();
</script>
