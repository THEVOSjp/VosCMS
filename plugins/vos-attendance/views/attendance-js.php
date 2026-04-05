<?php
/**
 * RezlyX Admin - 근태 관리 JavaScript
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
        console.log('[Attendance] Alert:', type, msg);
    }

    function postData(formData) {
        return fetch(window.location.pathname, {
            method: 'POST',
            body: formData
        }).then(function(r) { return r.json(); });
    }

    function doAction(action, staffId, extraData) {
        console.log('[Attendance]', action, 'request:', staffId);
        var formData = new FormData();
        formData.append('action', action);
        formData.append('staff_id', staffId);
        if (extraData) {
            Object.keys(extraData).forEach(function(k) { formData.append(k, extraData[k]); });
        }

        postData(formData).then(function(data) {
            if (data.success) {
                showAlert(data.message, 'success');
                console.log('[Attendance]', action, 'success:', data);
                setTimeout(function() { location.reload(); }, 800);
            } else {
                showAlert(data.message || 'Error', 'error');
                console.log('[Attendance]', action, 'failed:', data.message);
            }
        }).catch(function(err) {
            console.error('[Attendance]', action, 'error:', err);
            showAlert('<?= __('staff.attendance.error.server') ?>', 'error');
        });
    }

    window.clockIn = function(staffId) { doAction('clock_in', staffId); };
    window.clockOut = function(staffId) { doAction('clock_out', staffId); };
    window.breakOut = function(staffId) { doAction('break_out', staffId); };
    window.breakIn = function(staffId) { doAction('break_in', staffId); };
    window.outsideOut = function(staffId) { doAction('outside_out', staffId); };
    window.outsideIn = function(staffId) { doAction('outside_in', staffId); };

    console.log('[Attendance] Page initialized');
})();
</script>
