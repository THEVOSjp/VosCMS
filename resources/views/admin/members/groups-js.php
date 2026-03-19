<?php
/**
 * RezlyX Admin - 회원 그룹 관리 JavaScript
 */
?>
<script>
(function() {
    'use strict';

    function showAlert(msg, type) {
        var box = document.getElementById('alertBox');
        box.className = 'mb-6 p-4 rounded-lg border ' +
            (type === 'success'
                ? 'bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-300 border-green-200 dark:border-green-800'
                : 'bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-300 border-red-200 dark:border-red-800');
        box.textContent = msg;
        box.classList.remove('hidden');
        setTimeout(function() { box.classList.add('hidden'); }, 4000);
        console.log('[Groups] Alert:', type, msg);
    }

    function postData(formData) {
        return fetch(window.location.pathname, {
            method: 'POST',
            body: formData
        }).then(function(r) { return r.json(); });
    }

    // 이름 → slug 자동 생성
    var nameInput = document.getElementById('gradeName');
    var slugInput = document.getElementById('gradeSlug');
    var slugManual = false;

    if (slugInput) {
        slugInput.addEventListener('input', function() {
            slugManual = this.value.trim().length > 0;
        });
    }
    if (nameInput) {
        nameInput.addEventListener('input', function() {
            if (!slugManual || slugInput.value.trim() === '') {
                slugInput.value = this.value.trim().toLowerCase()
                    .replace(/[^a-z0-9\s-]/g, '')
                    .replace(/\s+/g, '_')
                    .replace(/-+/g, '_');
                slugManual = false;
            }
        });
    }

    window.openGradeModal = function() {
        document.getElementById('gradeModalTitle').textContent = '<?= __('members.groups.create') ?>';
        document.getElementById('gradeAction').value = 'create_grade';
        document.getElementById('gradeId').value = '';
        document.getElementById('gradeName').value = '';
        document.getElementById('gradeSlug').value = '';
        document.getElementById('gradeColor').value = '#6B7280';
        document.getElementById('gradeDiscount').value = '0';
        document.getElementById('gradePoint').value = '0';
        document.getElementById('gradeMinRes').value = '0';
        document.getElementById('gradeMinSpent').value = '0';
        document.getElementById('gradeBenefits').value = '';
        slugManual = false;
        updateGradeMultilangKeys('new');
        document.getElementById('gradeModal').classList.remove('hidden');
        console.log('[Groups] Modal opened (create)');
    };

    window.editGrade = function(g) {
        document.getElementById('gradeModalTitle').textContent = '<?= __('members.groups.edit') ?>';
        document.getElementById('gradeAction').value = 'update_grade';
        document.getElementById('gradeId').value = g.id;
        document.getElementById('gradeName').value = g._tr_name || g.name || '';
        document.getElementById('gradeSlug').value = g.slug || '';
        document.getElementById('gradeColor').value = g.color || '#6B7280';
        document.getElementById('gradeDiscount').value = g.discount_rate || '0';
        document.getElementById('gradePoint').value = g.point_rate || '0';
        document.getElementById('gradeMinRes').value = g.min_reservations || '0';
        document.getElementById('gradeMinSpent').value = g.min_spent || '0';
        var ben = g._tr_benefits || g.benefits || '';
        try { ben = JSON.parse(ben); } catch(e) {}
        document.getElementById('gradeBenefits').value = ben;
        slugManual = true;
        // 다국어 버튼의 langKey를 실제 그룹 ID로 업데이트
        updateGradeMultilangKeys(g.id);
        document.getElementById('gradeModal').classList.remove('hidden');
        console.log('[Groups] Modal opened (edit):', g.id);
    };

    window.closeGradeModal = function() {
        document.getElementById('gradeModal').classList.add('hidden');
        console.log('[Groups] Modal closed');
    };

    window.saveGrade = function() {
        var name = document.getElementById('gradeName').value.trim();
        if (!name) {
            document.getElementById('gradeName').focus();
            return;
        }

        var form = document.getElementById('gradeForm');
        var formData = new FormData(form);
        console.log('[Groups] Saving:', formData.get('action'), name);

        postData(formData).then(function(data) {
            if (data.success) {
                showAlert(data.message, 'success');
                closeGradeModal();
                setTimeout(function() { location.reload(); }, 800);
            } else {
                showAlert(data.message || '<?= __('members.groups.error.generic') ?>', 'error');
            }
        }).catch(function(err) {
            console.error('[Groups] Save error:', err);
            showAlert('<?= __('members.groups.error.server') ?>', 'error');
        });
    };

    window.deleteGrade = function(id) {
        if (!confirm('<?= __('members.groups.confirm_delete') ?>')) return;
        console.log('[Groups] Deleting:', id);

        var formData = new FormData();
        formData.append('action', 'delete_grade');
        formData.append('id', id);

        postData(formData).then(function(data) {
            if (data.success) {
                showAlert(data.message, 'success');
                var card = document.getElementById('grade-' + id);
                if (card) card.remove();
            } else {
                showAlert(data.message || 'Error', 'error');
            }
        }).catch(function(err) {
            console.error('[Groups] Delete error:', err);
            showAlert('<?= __('members.groups.error.server') ?>', 'error');
        });
    };

    window.setDefault = function(id) {
        console.log('[Groups] Setting default:', id);
        var formData = new FormData();
        formData.append('action', 'set_default');
        formData.append('id', id);

        postData(formData).then(function(data) {
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(function() { location.reload(); }, 800);
            } else {
                showAlert(data.message || 'Error', 'error');
            }
        }).catch(function(err) {
            console.error('[Groups] Set default error:', err);
        });
    };

    // 그룹 초기화
    window.resetGradesToDefault = function() {
        if (!confirm('<?= __('members.groups.confirm_reset') ?>')) return;
        console.log('[Groups] Resetting to default...');

        var formData = new FormData();
        formData.append('action', 'reset_to_default');

        postData(formData).then(function(data) {
            if (data.success) {
                showAlert(data.message, 'success');
                setTimeout(function() { location.reload(); }, 800);
            } else {
                showAlert(data.message || 'Error', 'error');
            }
        }).catch(function(err) {
            console.error('[Groups] Reset error:', err);
            showAlert('<?= __('members.groups.error.server') ?>', 'error');
        });
    };

    // 다국어 버튼의 langKey 업데이트 (rzx_multilang_input의 onclick 속성 갱신)
    function updateGradeMultilangKeys(gradeId) {
        var modal = document.getElementById('gradeModal');
        if (!modal) return;
        modal.querySelectorAll('button[onclick*="openMultilangModal"]').forEach(function(btn) {
            var onclick = btn.getAttribute('onclick');
            // member_grade.xxx.field → member_grade.{gradeId}.field
            var updated = onclick.replace(/member_grade\.[^.]+\./, 'member_grade.' + gradeId + '.');
            btn.setAttribute('onclick', updated);
        });
        console.log('[Groups] Multilang keys updated to:', gradeId);
    }

    // SortableJS 드래그앤드롭 순서 변경
    var grid = document.getElementById('gradeCardGrid');
    if (grid && typeof Sortable !== 'undefined') {
        Sortable.create(grid, {
            handle: '.grade-drag-handle',
            animation: 200,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            draggable: '.grade-card',
            onEnd: function() {
                var cards = grid.querySelectorAll('.grade-card');
                var ids = [];
                cards.forEach(function(card) { ids.push(card.dataset.id); });
                console.log('[Groups] Reorder:', ids);

                var formData = new FormData();
                formData.append('action', 'reorder');
                ids.forEach(function(id, i) { formData.append('ids[' + i + ']', id); });

                postData(formData).then(function(data) {
                    if (data.success) {
                        showAlert(data.message, 'success');
                    } else {
                        showAlert(data.message || 'Error', 'error');
                    }
                }).catch(function(err) {
                    console.error('[Groups] Reorder error:', err);
                });
            }
        });
        console.log('[Groups] Sortable initialized');
    }

    console.log('[Groups] Page initialized');
})();
</script>
