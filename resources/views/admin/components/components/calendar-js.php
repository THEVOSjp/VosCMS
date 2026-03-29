<script>
console.log('[RzxCal] Calendar component loaded');

const RzxCal = {
    adminUrl: '<?= $cal['adminUrl'] ?>',
    csrfToken: '<?= $cal['csrfToken'] ?>',
    currency: { symbol: '<?= $cal['currencySymbol'] ?>', position: '<?= $cal['currencyPosition'] ?>' }
};

function rzxCalFormatCurrency(amount) {
    const f = Number(amount).toLocaleString();
    return RzxCal.currency.position === 'suffix' ? f + RzxCal.currency.symbol : RzxCal.currency.symbol + f;
}

const rzxCalStatusLabels = {
    pending: '<?= __('reservations.cal_status_pending') ?>', confirmed: '<?= __('reservations.cal_status_confirmed') ?>', completed: '<?= __('reservations.cal_status_completed') ?>',
    cancelled: '<?= __('reservations.cal_status_cancelled') ?>', no_show: '<?= __('reservations.cal_status_noshow') ?>'
};
const rzxCalStatusBadge = {
    pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    confirmed: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    completed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    no_show: 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-300'
};

function rzxCalEsc(str) {
    const d = document.createElement('div');
    d.textContent = str || '';
    return d.innerHTML;
}

// ─── 상세 모달 ───

function rzxCalShowDetail(r) {
    console.log('[RzxCal] Show detail:', r.id);
    document.getElementById('rzxCalDetailTitle').textContent =
        (r.reservation_number || '') + ' · ' + (r.customer_name || '');

    const startTime = (r.start_time || '').substring(0, 5);
    const endTime = (r.end_time || '').substring(0, 5);
    const badge = rzxCalStatusBadge[r.status] || 'bg-zinc-100 text-zinc-800';
    const label = rzxCalStatusLabels[r.status] || r.status;
    const amount = Number(r.final_amount || 0);

    document.getElementById('rzxCalDetailBody').innerHTML = `
        <div class="space-y-3">
            <div class="flex items-center justify-between">
                <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${badge}">${rzxCalEsc(label)}</span>
                <span class="text-sm font-bold text-zinc-900 dark:text-white">${rzxCalFormatCurrency(amount)}</span>
            </div>
            <div class="grid grid-cols-2 gap-3 text-sm">
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= __('reservations.cal_detail_datetime') ?></p>
                    <p class="text-zinc-900 dark:text-white">${rzxCalEsc(r.reservation_date)} ${startTime}${endTime ? ' ~ ' + endTime : ''}</p>
                </div>
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= __('reservations.cal_detail_service') ?></p>
                    <p class="text-zinc-900 dark:text-white">${rzxCalEsc(r.service_name || '-')}</p>
                </div>
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= __('reservations.cal_detail_customer') ?></p>
                    <p class="text-zinc-900 dark:text-white">${rzxCalEsc(r.customer_name)}</p>
                </div>
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= __('reservations.cal_detail_phone') ?></p>
                    <p class="text-zinc-900 dark:text-white">${rzxCalEsc(r.customer_phone || '-')}</p>
                </div>
            </div>
            ${r.notes ? '<div class="text-xs text-zinc-500 dark:text-zinc-400 bg-zinc-50 dark:bg-zinc-900 p-2 rounded-lg">' + rzxCalEsc(r.notes) + '</div>' : ''}
        </div>`;

    let actionsHtml = `<a href="${RzxCal.adminUrl}/reservations/${r.id}" class="flex-1 text-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm transition"><?= __('reservations.cal_detail_view') ?></a>`;

    if (r.status === 'pending') {
        actionsHtml += `<button onclick="rzxCalChangeStatus('${r.id}', 'confirm')" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm transition"><?= __('reservations.cal_btn_confirm') ?></button>`;
        actionsHtml += `<button onclick="rzxCalChangeStatus('${r.id}', 'cancel')" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg text-sm transition"><?= __('reservations.cal_btn_cancel') ?></button>`;
        actionsHtml += `<button onclick="rzxCalChangeStatus('${r.id}', 'no-show')" class="px-4 py-2 bg-zinc-500 hover:bg-zinc-600 text-white rounded-lg text-sm transition"><?= __('reservations.cal_btn_noshow') ?></button>`;
    } else if (r.status === 'confirmed') {
        actionsHtml += `<button onclick="rzxCalChangeStatus('${r.id}', 'complete')" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm transition"><?= __('reservations.cal_btn_complete') ?></button>`;
        actionsHtml += `<button onclick="rzxCalChangeStatus('${r.id}', 'cancel')" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg text-sm transition"><?= __('reservations.cal_btn_cancel') ?></button>`;
        actionsHtml += `<button onclick="rzxCalChangeStatus('${r.id}', 'no-show')" class="px-4 py-2 bg-zinc-500 hover:bg-zinc-600 text-white rounded-lg text-sm transition"><?= __('reservations.cal_btn_noshow') ?></button>`;
    }

    document.getElementById('rzxCalDetailActions').innerHTML = actionsHtml;
    document.getElementById('rzxCalDetailModal').classList.remove('hidden');
}

function rzxCalCloseDetail(e) {
    if (e && e.target !== e.currentTarget) return;
    document.getElementById('rzxCalDetailModal').classList.add('hidden');
}

// ─── 상태 변경 ───

async function rzxCalChangeStatus(id, action) {
    const msgs = {
        confirm: '<?= __('reservations.cal_confirm_confirm') ?>',
        cancel: '<?= __('reservations.cal_confirm_cancel') ?>',
        complete: '<?= __('reservations.cal_confirm_complete') ?>',
        'no-show': '<?= __('reservations.cal_confirm_noshow') ?>'
    };
    const colors = { confirm: 'blue', cancel: 'red', complete: 'green', 'no-show': 'zinc' };
    const msg = msgs[action] || '<?= __('reservations.cal_confirm_default') ?>';

    // 커스텀 확인 모달
    const confirmed = await rzxCalConfirmModal(msg, colors[action] || 'blue', action === 'cancel');
    if (!confirmed) return;

    let reason = '';
    if (action === 'cancel' && confirmed.reason !== undefined) {
        reason = confirmed.reason;
    }

    try {
        console.log('[RzxCal] Changing status:', id, action);
        const resp = await fetch(`${RzxCal.adminUrl}/reservations/${id}/${action}`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: `_token=${encodeURIComponent(RzxCal.csrfToken)}&reason=${encodeURIComponent(reason)}`
        });
        const data = await resp.json();
        console.log('[RzxCal] Status result:', data);
        if (data.error) {
            if (typeof showResultModal === 'function') showResultModal(false, data.message || '<?= __('reservations.cal_error') ?>');
            else alert(data.message || '<?= __('reservations.cal_error') ?>');
        } else {
            if (typeof showResultModal === 'function') {
                showResultModal(true, '');
                setTimeout(() => location.reload(), 1200);
            } else {
                location.reload();
            }
        }
    } catch (err) {
        console.error('[RzxCal] Status change error:', err);
        location.reload();
    }
}

// ─── 확인 모달 ───

function rzxCalConfirmModal(message, color, showReason) {
    return new Promise(function(resolve) {
        var colorMap = {
            blue: 'bg-blue-600 hover:bg-blue-700',
            red: 'bg-red-600 hover:bg-red-700',
            green: 'bg-green-600 hover:bg-green-700',
            zinc: 'bg-zinc-600 hover:bg-zinc-700'
        };
        var btnClass = colorMap[color] || colorMap.blue;
        var reasonHtml = showReason
            ? '<div class="mt-3"><label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1"><?= __('reservations.cal_cancel_reason') ?? '취소 사유' ?></label><textarea id="rzxCalConfirmReason" rows="2" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg" placeholder="<?= __('reservations.cal_cancel_reason_default') ?? '관리자에 의한 취소' ?>"></textarea></div>'
            : '';

        var html = '<div id="rzxCalConfirmOverlay" class="fixed inset-0 bg-black/50 z-[60] flex items-center justify-center p-4">'
            + '<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-2xl w-full max-w-sm p-6">'
            + '<div class="text-center mb-4">'
            + '<svg class="w-12 h-12 mx-auto mb-3 text-' + color + '-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
            + '<p class="text-sm text-zinc-700 dark:text-zinc-300">' + message + '</p>'
            + '</div>'
            + reasonHtml
            + '<div class="flex gap-3 mt-4">'
            + '<button id="rzxCalConfirmNo" class="flex-1 px-4 py-2.5 text-sm font-medium text-zinc-600 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 rounded-lg hover:bg-zinc-200 dark:hover:bg-zinc-600 transition"><?= __('common.buttons.cancel') ?? '취소' ?></button>'
            + '<button id="rzxCalConfirmYes" class="flex-1 px-4 py-2.5 text-sm font-medium text-white ' + btnClass + ' rounded-lg transition"><?= __('common.buttons.confirm') ?? '확인' ?></button>'
            + '</div></div></div>';

        document.body.insertAdjacentHTML('beforeend', html);

        document.getElementById('rzxCalConfirmNo').onclick = function() {
            document.getElementById('rzxCalConfirmOverlay').remove();
            resolve(false);
        };
        document.getElementById('rzxCalConfirmYes').onclick = function() {
            var reason = document.getElementById('rzxCalConfirmReason');
            document.getElementById('rzxCalConfirmOverlay').remove();
            resolve(showReason ? { reason: reason ? reason.value : '' } : true);
        };
        document.getElementById('rzxCalConfirmOverlay').onclick = function(e) {
            if (e.target === e.currentTarget) {
                e.currentTarget.remove();
                resolve(false);
            }
        };
        console.log('[RzxCal] Confirm modal shown:', message);
    });
}

// ─── 예약 추가 모달 ───

function rzxCalQuickAdd(dateStr, e) {
    console.log('[RzxCal] Quick add for date:', dateStr);
    // 공용 폼 리셋 함수 호출 (reservation-form-js.php에서 등록)
    if (window['resetResForm_rzxCalAddForm']) {
        window['resetResForm_rzxCalAddForm'](dateStr);
    }
    document.getElementById('rzxCalAddModal').classList.remove('hidden');
}

function rzxCalCloseAdd(e) {
    if (e && e.target !== e.currentTarget) return;
    document.getElementById('rzxCalAddModal').classList.add('hidden');
}

// ─── ESC 닫기 ───
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        rzxCalCloseDetail();
        rzxCalCloseAdd();
    }
});
</script>
