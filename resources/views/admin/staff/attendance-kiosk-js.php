<?php
/**
 * RezlyX Admin - 키오스크 JavaScript
 */
$attendanceApiUrl = ($config['app_url'] ?? '') . '/' . ($config['admin_path'] ?? 'admin') . '/staff/attendance';
?>
<script>
(function() {
    'use strict';

    var apiUrl = '<?= $attendanceApiUrl ?>';
    var cardInput = document.getElementById('cardInput');
    var stateIdle = document.getElementById('stateIdle');
    var stateSuccess = document.getElementById('stateSuccess');
    var stateError = document.getElementById('stateError');
    var resetTimer = null;
    var inputBuffer = '';
    var inputTimer = null;

    // 시계 업데이트
    function updateClock() {
        var now = new Date();
        var h = String(now.getHours()).padStart(2, '0');
        var m = String(now.getMinutes()).padStart(2, '0');
        var s = String(now.getSeconds()).padStart(2, '0');
        document.getElementById('currentTime').textContent = h + ':' + m + ':' + s;

        var days = ['<?= __('admin.staff.attendance.kiosk_sun') ?>', '<?= __('admin.staff.attendance.kiosk_mon') ?>', '<?= __('admin.staff.attendance.kiosk_tue') ?>', '<?= __('admin.staff.attendance.kiosk_wed') ?>', '<?= __('admin.staff.attendance.kiosk_thu') ?>', '<?= __('admin.staff.attendance.kiosk_fri') ?>', '<?= __('admin.staff.attendance.kiosk_sat') ?>'];
        var y = now.getFullYear();
        var mo = String(now.getMonth() + 1).padStart(2, '0');
        var d = String(now.getDate()).padStart(2, '0');
        document.getElementById('currentDate').textContent = y + '-' + mo + '-' + d + ' (' + days[now.getDay()] + ')';
    }
    updateClock();
    setInterval(updateClock, 1000);

    // 포커스 유지
    window.focusInput = function() {
        cardInput.focus();
        console.log('[Kiosk] Input focused');
    };
    focusInput();
    setInterval(focusInput, 3000);

    // 상태 전환
    function showState(state) {
        stateIdle.classList.add('hidden');
        stateSuccess.classList.add('hidden');
        stateError.classList.add('hidden');
        if (state === 'idle') { stateIdle.classList.remove('hidden'); stateIdle.style.display = 'flex'; }
        else if (state === 'success') { stateSuccess.classList.remove('hidden'); stateSuccess.style.display = 'flex'; }
        else if (state === 'error') { stateError.classList.remove('hidden'); stateError.style.display = 'flex'; }
    }

    function resetToIdle() {
        showState('idle');
        document.body.className = 'bg-zinc-900 min-h-screen flex flex-col items-center justify-center select-none';
        focusInput();
        console.log('[Kiosk] Reset to idle');
    }

    // 카드 처리
    function processCard(cardNumber) {
        if (!cardNumber || cardNumber.length < 2) return;
        console.log('[Kiosk] Card scanned:', cardNumber);

        var formData = new FormData();
        formData.append('action', 'card_clock');
        formData.append('card_number', cardNumber);

        fetch(apiUrl, {
            method: 'POST',
            body: formData
        }).then(function(r) { return r.json(); })
        .then(function(data) {
            if (data.success) {
                document.getElementById('successName').textContent = data.staff_name || '';
                var typeMsg = data.type === 'clock_in'
                    ? '<?= __('admin.staff.attendance.kiosk_clocked_in') ?>'
                    : '<?= __('admin.staff.attendance.kiosk_clocked_out') ?>';
                document.getElementById('successMsg').textContent = typeMsg;
                var t = data.time ? data.time.substring(11, 16) : '';
                document.getElementById('successTime').textContent = t;
                if (data.work_hours) {
                    document.getElementById('successTime').textContent += ' (' + data.work_hours + 'h)';
                }
                showState('success');
                document.body.classList.add('success-flash');
                console.log('[Kiosk] Success:', data.type, data.staff_name);
            } else {
                document.getElementById('errorMsg').textContent = data.message || 'Error';
                showState('error');
                document.body.classList.add('error-flash');
                console.log('[Kiosk] Error:', data.message);
            }

            // 3초 후 초기화
            clearTimeout(resetTimer);
            resetTimer = setTimeout(resetToIdle, 3000);
        })
        .catch(function(err) {
            console.error('[Kiosk] Request error:', err);
            document.getElementById('errorMsg').textContent = '<?= __('admin.staff.attendance.error.server') ?>';
            showState('error');
            clearTimeout(resetTimer);
            resetTimer = setTimeout(resetToIdle, 3000);
        });
    }

    // HID 카드리더 입력 감지 (키보드 에뮬레이션)
    // 카드리더는 빠르게 문자를 입력하고 마지막에 Enter를 보냄
    document.addEventListener('keydown', function(e) {
        // ESC로 전체화면 토글
        if (e.key === 'Escape') return;

        if (e.key === 'Enter') {
            e.preventDefault();
            var value = inputBuffer.trim();
            inputBuffer = '';
            clearTimeout(inputTimer);
            if (value) {
                processCard(value);
            }
            cardInput.value = '';
            return;
        }

        // 일반 문자 수집
        if (e.key.length === 1) {
            inputBuffer += e.key;
            // 300ms 이내에 다음 입력이 없으면 버퍼 리셋 (수동 타이핑 방지)
            clearTimeout(inputTimer);
            inputTimer = setTimeout(function() {
                console.log('[Kiosk] Input timeout, clearing buffer');
                inputBuffer = '';
            }, 300);
        }
    });

    // 전체화면 진입 (F11 대안)
    document.addEventListener('dblclick', function() {
        if (document.fullscreenElement) {
            document.exitFullscreen();
        } else {
            document.documentElement.requestFullscreen().catch(function(e) {
                console.log('[Kiosk] Fullscreen error:', e);
            });
        }
    });

    console.log('[Kiosk] Initialized. Waiting for card scan...');
})();
</script>
