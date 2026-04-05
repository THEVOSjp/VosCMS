<script>
console.log('[Reservations] Index page loaded');

async function changeStatus(id, action) {
    const msgs = {
        confirm: '<?= __('reservations.confirm_msg') ?>',
        cancel: '<?= __('reservations.cancel_msg') ?>',
        complete: '<?= __('reservations.complete_msg') ?>',
        'no-show': '<?= __('reservations.noshow_msg') ?>'
    };
    if (!confirm(msgs[action] || '진행하시겠습니까?')) return;

    const csrfToken = '<?= $csrfToken ?>';
    let reason = '';
    if (action === 'cancel') {
        reason = prompt('취소 사유를 입력하세요:', '관리자에 의한 취소') || '';
    }

    try {
        const resp = await fetch(`<?= $adminUrl ?>/reservations/${id}/${action}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: `_token=${encodeURIComponent(csrfToken)}&reason=${encodeURIComponent(reason)}`
        });
        const data = await resp.json();
        console.log('[Reservations] Status change result:', data);

        if (data.error) {
            alert(data.message || '처리에 실패했습니다.');
        } else {
            location.reload();
        }
    } catch (err) {
        console.error('[Reservations] Error:', err);
        // JSON이 아닌 응답 (리다이렉트)인 경우 새로고침
        location.reload();
    }
}
</script>
