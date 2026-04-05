<?php
/**
 * RezlyX Admin - 스태프 스케줄 관리 JS
 */
?>
<script>
(function() {
    'use strict';
    var SCHEDULE_URL = '<?= $adminUrl ?>/staff/schedule';
    var selectedStaffId = null;
    var dayNames = <?= json_encode($dayNames) ?>;

    // === 스태프 선택 ===
    document.getElementById('staffSelect').addEventListener('change', function() {
        selectedStaffId = this.value ? parseInt(this.value) : null;
        console.log('[Schedule] Staff selected:', selectedStaffId);
        if (selectedStaffId) {
            document.getElementById('scheduleArea').classList.remove('hidden');
            loadSchedule(selectedStaffId);
        } else {
            document.getElementById('scheduleArea').classList.add('hidden');
        }
    });

    // === 스케줄 로드 ===
    function loadSchedule(staffId) {
        console.log('[Schedule] Loading schedule for staff:', staffId);
        var fd = new FormData();
        fd.append('action', 'get_schedule');
        fd.append('staff_id', staffId);

        fetch(SCHEDULE_URL, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                console.log('[Schedule] Data loaded:', data);
                if (data.success) {
                    populateWeekly(data.weekly, data.business_hours);
                    populateOverrides(data.overrides);
                } else {
                    showAlert(data.message || 'Error', 'error');
                }
            })
            .catch(function(err) {
                console.error('[Schedule] Load error:', err);
                showAlert('Error loading schedule', 'error');
            });
    }

    // === 주간 스케줄 채우기 ===
    function populateWeekly(weekly, businessHours) {
        for (var d = 0; d < 7; d++) {
            var working = document.querySelector('.weekly-working[data-dow="' + d + '"]');
            var start = document.querySelector('.weekly-start[data-dow="' + d + '"]');
            var end = document.querySelector('.weekly-end[data-dow="' + d + '"]');
            var bstart = document.querySelector('.weekly-bstart[data-dow="' + d + '"]');
            var bend = document.querySelector('.weekly-bend[data-dow="' + d + '"]');

            if (weekly[d]) {
                // 스태프 개별 스케줄 있음
                working.checked = !!parseInt(weekly[d].is_working);
                start.value = weekly[d].start_time || '';
                end.value = weekly[d].end_time || '';
                bstart.value = weekly[d].break_start || '';
                bend.value = weekly[d].break_end || '';
            } else if (businessHours[d]) {
                // 기본 영업시간 폴백
                working.checked = !!parseInt(businessHours[d].is_open);
                start.value = businessHours[d].open_time || '';
                end.value = businessHours[d].close_time || '';
                bstart.value = businessHours[d].break_start || '';
                bend.value = businessHours[d].break_end || '';
            } else {
                working.checked = false;
                start.value = '';
                end.value = '';
                bstart.value = '';
                bend.value = '';
            }
            updateRowState(d);
        }
        console.log('[Schedule] Weekly populated');
    }

    // 근무 토글에 따라 시간 입력 활성/비활성
    function updateRowState(dow) {
        var working = document.querySelector('.weekly-working[data-dow="' + dow + '"]');
        var inputs = document.querySelectorAll('.weekly-start[data-dow="' + dow + '"], .weekly-end[data-dow="' + dow + '"], .weekly-bstart[data-dow="' + dow + '"], .weekly-bend[data-dow="' + dow + '"]');
        inputs.forEach(function(inp) {
            inp.disabled = !working.checked;
            if (!working.checked) {
                inp.classList.add('opacity-30');
            } else {
                inp.classList.remove('opacity-30');
            }
        });
    }

    document.querySelectorAll('.weekly-working').forEach(function(cb) {
        cb.addEventListener('change', function() {
            updateRowState(this.dataset.dow);
            console.log('[Schedule] Day', this.dataset.dow, 'working:', this.checked);
        });
    });

    // === 주간 스케줄 저장 ===
    document.getElementById('btnSaveWeekly').addEventListener('click', function() {
        if (!selectedStaffId) return;
        console.log('[Schedule] Saving weekly schedule...');

        var days = [];
        for (var d = 0; d < 7; d++) {
            days.push({
                day_of_week: d,
                is_working: document.querySelector('.weekly-working[data-dow="' + d + '"]').checked ? 1 : 0,
                start_time: document.querySelector('.weekly-start[data-dow="' + d + '"]').value || '',
                end_time: document.querySelector('.weekly-end[data-dow="' + d + '"]').value || '',
                break_start: document.querySelector('.weekly-bstart[data-dow="' + d + '"]').value || '',
                break_end: document.querySelector('.weekly-bend[data-dow="' + d + '"]').value || '',
            });
        }

        var fd = new FormData();
        fd.append('action', 'save_weekly');
        fd.append('staff_id', selectedStaffId);
        fd.append('days', JSON.stringify(days));

        var btn = this;
        btn.disabled = true;
        btn.textContent = '...';

        fetch(SCHEDULE_URL, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                console.log('[Schedule] Save weekly result:', data);
                showAlert(data.message, data.success ? 'success' : 'error');
            })
            .catch(function(err) {
                console.error('[Schedule] Save weekly error:', err);
                showAlert('Error', 'error');
            })
            .finally(function() {
                btn.disabled = false;
                btn.textContent = '<?= __('staff.schedule.save_weekly') ?>';
            });
    });

    // === 오버라이드 목록 채우기 ===
    function populateOverrides(overrides) {
        var list = document.getElementById('overrideList');
        if (!overrides || overrides.length === 0) {
            list.innerHTML = '<div class="px-6 py-8 text-center text-sm text-zinc-400"><?= __('staff.schedule.no_overrides') ?></div>';
            return;
        }

        var html = '';
        overrides.forEach(function(ov) {
            var statusBadge = parseInt(ov.is_working)
                ? '<span class="px-2 py-0.5 text-[10px] font-medium rounded bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400"><?= __('staff.schedule.working') ?></span>'
                : '<span class="px-2 py-0.5 text-[10px] font-medium rounded bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400"><?= __('staff.schedule.day_off') ?></span>';

            var timeStr = '';
            if (parseInt(ov.is_working) && ov.start_time && ov.end_time) {
                timeStr = ov.start_time.substring(0, 5) + ' ~ ' + ov.end_time.substring(0, 5);
                if (ov.break_start && ov.break_end) {
                    timeStr += ' (<?= __('staff.schedule.break_start') ?> ' + ov.break_start.substring(0, 5) + '~' + ov.break_end.substring(0, 5) + ')';
                }
            }

            html += '<div class="px-6 py-3 flex items-center justify-between hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition">';
            html += '<div class="flex items-center gap-3">';
            html += '<span class="text-sm font-medium text-zinc-900 dark:text-white">' + ov.override_date + '</span>';
            html += statusBadge;
            if (timeStr) html += '<span class="text-xs text-zinc-500 dark:text-zinc-400">' + timeStr + '</span>';
            if (ov.memo) html += '<span class="text-xs text-zinc-400 italic">' + escapeHtml(ov.memo) + '</span>';
            html += '</div>';
            html += '<button type="button" onclick="deleteOverride(' + ov.id + ')" class="p-1 text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition">';
            html += '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>';
            html += '</button>';
            html += '</div>';
        });
        list.innerHTML = html;
        console.log('[Schedule] Overrides populated:', overrides.length);
    }

    // === 오버라이드 추가 토글 ===
    document.getElementById('btnAddOverride').addEventListener('click', function() {
        var form = document.getElementById('overrideForm');
        form.classList.toggle('hidden');
        console.log('[Schedule] Override form toggled');
    });

    document.getElementById('btnCancelOverride').addEventListener('click', function() {
        document.getElementById('overrideForm').classList.add('hidden');
    });

    // === 오버라이드 저장 ===
    document.getElementById('btnSaveOverride').addEventListener('click', function() {
        if (!selectedStaffId) return;
        var ovDate = document.getElementById('ovDate').value;
        if (!ovDate) {
            showAlert('날짜를 선택해주세요.', 'error');
            return;
        }

        console.log('[Schedule] Saving override:', ovDate);
        var fd = new FormData();
        fd.append('action', 'save_override');
        fd.append('staff_id', selectedStaffId);
        fd.append('override_date', ovDate);
        fd.append('is_working', document.getElementById('ovWorking').value);
        fd.append('start_time', document.getElementById('ovStart').value);
        fd.append('end_time', document.getElementById('ovEnd').value);
        fd.append('break_start', document.getElementById('ovBStart').value);
        fd.append('break_end', document.getElementById('ovBEnd').value);
        fd.append('memo', document.getElementById('ovMemo').value);

        fetch(SCHEDULE_URL, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                console.log('[Schedule] Save override result:', data);
                if (data.success) {
                    showAlert(data.message, 'success');
                    document.getElementById('overrideForm').classList.add('hidden');
                    // 폼 초기화
                    document.getElementById('ovDate').value = '';
                    document.getElementById('ovWorking').value = '0';
                    document.getElementById('ovStart').value = '';
                    document.getElementById('ovEnd').value = '';
                    document.getElementById('ovBStart').value = '';
                    document.getElementById('ovBEnd').value = '';
                    document.getElementById('ovMemo').value = '';
                    // 다시 로드
                    loadSchedule(selectedStaffId);
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(function(err) {
                console.error('[Schedule] Save override error:', err);
                showAlert('Error', 'error');
            });
    });

    // === 오버라이드 삭제 ===
    window.deleteOverride = function(id) {
        if (!confirm('<?= __('staff.schedule.confirm_delete_override') ?>')) return;
        console.log('[Schedule] Deleting override:', id);

        var fd = new FormData();
        fd.append('action', 'delete_override');
        fd.append('id', id);

        fetch(SCHEDULE_URL, { method: 'POST', body: fd })
            .then(function(r) { return r.json(); })
            .then(function(data) {
                console.log('[Schedule] Delete override result:', data);
                if (data.success) {
                    showAlert(data.message, 'success');
                    loadSchedule(selectedStaffId);
                } else {
                    showAlert(data.message, 'error');
                }
            })
            .catch(function(err) {
                console.error('[Schedule] Delete override error:', err);
            });
    };

    // === 유틸 ===
    function escapeHtml(str) {
        var div = document.createElement('div');
        div.textContent = str;
        return div.innerHTML;
    }

    function showAlert(msg, type) {
        var box = document.getElementById('alertBox');
        box.className = 'mb-6 p-4 rounded-lg border ' + (type === 'success'
            ? 'bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-300 border-green-200 dark:border-green-800'
            : 'bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-300 border-red-200 dark:border-red-800');
        box.textContent = msg;
        box.classList.remove('hidden');
        setTimeout(function() { box.classList.add('hidden'); }, 4000);
    }

    console.log('[Schedule] Module initialized');
})();
</script>
