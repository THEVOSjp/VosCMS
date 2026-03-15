<script>
console.log('[Reservations] Show page loaded, id=<?= $id ?>');

async function changeStatus(id, action) {
    const msgs = {
        confirm: '<?= __('reservations.confirm_msg') ?>',
        cancel: '<?= __('reservations.cancel_msg') ?>',
        complete: '<?= __('reservations.complete_msg') ?>',
        'no-show': '<?= __('reservations.noshow_msg') ?>'
    };
    if (!confirm(msgs[action] || '진행하시겠습니까?')) return;
    let reason = '';
    if (action === 'cancel') reason = prompt('취소 사유:', '관리자에 의한 취소') || '';
    try {
        console.log('[Show] Changing status:', id, action);
        const resp = await fetch(`<?= $adminUrl ?>/reservations/${id}/${action}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: `_token=<?= $csrfToken ?>&reason=${encodeURIComponent(reason)}`
        });
        const data = await resp.json();
        console.log('[Show] Status result:', data);
        if (data.error) {
            alert(data.message || '처리에 실패했습니다.');
        } else {
            location.reload();
        }
    } catch (err) {
        console.error('[Show] Error:', err);
        location.reload();
    }
}

async function saveAdminNote(e) {
    e.preventDefault();
    const notes = document.getElementById('adminNotes').value;
    const btn = document.getElementById('saveNoteBtn');
    btn.textContent = '저장 중...';
    btn.disabled = true;
    try {
        console.log('[Show] Saving admin note');
        const resp = await fetch(`<?= $adminUrl ?>/reservations/<?= $id ?>`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: `_token=<?= $csrfToken ?>&_method=PUT&admin_notes=${encodeURIComponent(notes)}`
        });
        const data = await resp.json();
        console.log('[Show] Note save result:', data);
        if (data.success) {
            btn.textContent = '저장됨 ✓';
            setTimeout(() => { btn.textContent = '저장'; btn.disabled = false; }, 1500);
        } else {
            alert(data.message || '저장 실패');
            btn.textContent = '저장';
            btn.disabled = false;
        }
    } catch (err) {
        console.error('[Show] Note save error:', err);
        alert('저장 실패');
        btn.textContent = '저장';
        btn.disabled = false;
    }
}
</script>
