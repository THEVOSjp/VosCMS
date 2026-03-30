<script>
console.log('[POS] Page loaded');

// 국제전화 포맷 함수
function fmtPhone(phone) {
    if (!phone) return '-';
    var d = phone.replace(/\D/g, '');
    if (d.startsWith('0')) {
        var l = d.substring(1);
        var m = l.match(/^(10|11|16|17|18|19)(\d{4})(\d{4})$/);
        if (m) return '+82 ' + m[1] + '-' + m[2] + '-' + m[3];
        m = l.match(/^(2)(\d{3,4})(\d{4})$/);
        if (m) return '+82 ' + m[1] + '-' + m[2] + '-' + m[3];
        m = l.match(/^(\d{2})(\d{3,4})(\d{4})$/);
        if (m) return '+82 ' + m[1] + '-' + m[2] + '-' + m[3];
        return '+82 ' + l;
    }
    if (d.startsWith('82')) {
        var l = d.substring(2);
        if (l.startsWith('0')) l = l.substring(1);
        var m = l.match(/^(10|11|16|17|18|19)(\d{4})(\d{4})$/);
        if (m) return '+82 ' + m[1] + '-' + m[2] + '-' + m[3];
        return '+82 ' + l;
    }
    if (d.startsWith('81')) {
        var l = d.substring(2);
        if (l.startsWith('0')) l = l.substring(1);
        var m = l.match(/^(\d{2,3})(\d{4})(\d{4})$/);
        if (m) return '+81 ' + m[1] + '-' + m[2] + '-' + m[3];
        return '+81 ' + l;
    }
    return '+' + d;
}
// 기본 탭 설정 적용
if (typeof posConfig !== 'undefined' && posConfig.defaultTab && posConfig.defaultTab !== 'cards') {
    const tabMap = { 'waiting': 'waiting', 'reservations': 'reservations' };
    if (tabMap[posConfig.defaultTab]) {
        setTimeout(() => POS.switchTab(posConfig.defaultTab), 100);
    }
}

const POS = {
    adminUrl: '<?= $adminUrl ?>',
    csrfToken: '<?= $csrfToken ?>',
    currency: { symbol: '<?= $currencySymbol ?>', position: '<?= $currencyPosition ?>' },

    fmtCurrency(amount) {
        const f = Number(amount).toLocaleString();
        return this.currency.position === 'suffix' ? f + this.currency.symbol : this.currency.symbol + f;
    },

    escHtml(str) {
        const d = document.createElement('div');
        d.textContent = str || '';
        return d.innerHTML;
    },

    // ─── 탭 전환 ───
    switchTab(tab) {
        console.log('[POS] Switch tab:', tab);
        document.querySelectorAll('.pos-tab').forEach(btn => {
            if (btn.dataset.tab === tab) {
                btn.classList.add('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
                btn.classList.remove('border-transparent', 'text-zinc-400');
            } else {
                btn.classList.remove('border-blue-500', 'text-blue-600', 'dark:text-blue-400');
                btn.classList.add('border-transparent', 'text-zinc-400');
            }
        });
        document.querySelectorAll('.pos-tab-pane').forEach(pane => pane.classList.add('hidden'));
        const target = document.getElementById('posTab' + tab.charAt(0).toUpperCase() + tab.slice(1));
        if (target) target.classList.remove('hidden');
    },

    // ─── 상세 모달 ───
    showDetail(r) {
        console.log('[POS] Show detail:', r.id);
        document.getElementById('posDetailTitle').textContent = (r.customer_name || '') + ' · ' + (r.service_name || '');

        const startT = (r.start_time || '').substring(0, 5);
        const endT = (r.end_time || '').substring(0, 5);
        const amount = Number(r.final_amount || r.total_amount || 0);

        const statusLabels = {
            pending: '<?= __('reservations.filter.pending') ?>',
            confirmed: '<?= __('reservations.filter.confirmed') ?>',
            completed: '<?= __('reservations.actions.complete') ?>',
            cancelled: '<?= __('reservations.actions.cancel') ?>',
            no_show: '<?= __('reservations.actions.no_show') ?>'
        };
        const statusBadgeCls = {
            pending: 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
            confirmed: 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
            completed: 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
            cancelled: 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
            no_show: 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-300'
        };
        const badge = statusBadgeCls[r.status] || 'bg-zinc-100 text-zinc-800';
        const label = statusLabels[r.status] || r.status;

        document.getElementById('posDetailBody').innerHTML = `
            <div class="space-y-3">
                <div class="flex items-center justify-between">
                    <span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ${badge}">${this.escHtml(label)}</span>
                    <span class="text-sm font-bold text-zinc-900 dark:text-white">${this.fmtCurrency(amount)}</span>
                </div>
                <div class="grid grid-cols-2 gap-3 text-sm">
                    <div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= __('reservations.pos_start_time') ?></p>
                        <p class="text-zinc-900 dark:text-white">${startT}${endT ? ' ~ ' + endT : ''}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= __('reservations.pos_service') ?></p>
                        <p class="text-zinc-900 dark:text-white">${this.escHtml(r.service_name || '-')}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= __('reservations.pos_customer_name') ?></p>
                        <p class="text-zinc-900 dark:text-white">${this.escHtml(r.customer_name)}</p>
                    </div>
                    <div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= __('reservations.pos_phone') ?></p>
                        <p class="text-zinc-900 dark:text-white font-mono">${fmtPhone(r.customer_phone)}</p>
                    </div>
                </div>
                ${r.notes ? '<div class="text-xs text-zinc-500 dark:text-zinc-400 bg-zinc-50 dark:bg-zinc-900 p-2 rounded-lg">' + this.escHtml(r.notes) + '</div>' : ''}
                ${!r.staff_id ? `<div class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
                    <p class="text-xs font-medium text-amber-700 dark:text-amber-300 mb-2"><?= __('reservations.pos_assign_staff') ?></p>
                    <div class="flex gap-2">
                        <select id="assignStaffSelect" class="flex-1 px-2 py-1.5 text-xs bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200">
                            <option value=""><?= __('reservations.pos_select_staff') ?></option>
                            <?php foreach ($posStaffList as $_ps): ?>
                            <option value="<?= $_ps['id'] ?>"><?= htmlspecialchars($_ps['name']) ?></option>
                            <?php endforeach; ?>
                        </select>
                        <button onclick="POS.assignStaff('${r.id}')" class="px-3 py-1.5 bg-amber-600 hover:bg-amber-700 text-white rounded-lg text-xs transition"><?= __('reservations.pos_assign') ?></button>
                    </div>
                </div>` : `<div class="text-xs text-zinc-500"><span class="text-zinc-400"><?= __('reservations.pos_staff') ?>:</span> ${this.escHtml(r.staff_name || '-')}</div>`}
            </div>`;

        this._detailData = r;
        let actions = `<button onclick="POS.closeDetail();POS.showServices(POS._detailData)" class="flex-1 text-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm transition"><?= __('reservations.detail') ?></button>`;
        if (r.status === 'pending') {
            actions += `<button onclick="POS.changeStatus('${r.id}','confirm')" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm transition"><?= __('reservations.actions.confirm') ?></button>`;
            actions += `<button onclick="POS.changeStatus('${r.id}','cancel')" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg text-sm transition"><?= __('reservations.actions.cancel') ?></button>`;
        } else if (r.status === 'confirmed') {
            actions += `<button onclick="POS.changeStatus('${r.id}','complete')" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm transition"><?= __('reservations.actions.complete') ?></button>`;
            actions += `<button onclick="POS.changeStatus('${r.id}','cancel')" class="px-4 py-2 bg-red-500 hover:bg-red-600 text-white rounded-lg text-sm transition"><?= __('reservations.actions.cancel') ?></button>`;
            actions += `<button onclick="POS.changeStatus('${r.id}','no-show')" class="px-4 py-2 bg-zinc-500 hover:bg-zinc-600 text-white rounded-lg text-sm transition"><?= __('reservations.actions.no_show') ?></button>`;
        }
        document.getElementById('posDetailActions').innerHTML = actions;
        document.getElementById('posDetailModal').classList.remove('hidden');
    },

    closeDetail(e) {
        if (e && e.target !== e.currentTarget) return;
        document.getElementById('posDetailModal').classList.add('hidden');
    },

    // ─── 당일 접수 모달 ───
    openCheckinModal() {
        console.log('[POS] Open checkin modal');
        document.getElementById('posCheckinModal').classList.remove('hidden');
    },

    closeCheckinModal(e) {
        if (e && e.target !== e.currentTarget) return;
        console.log('[POS] Close checkin modal');
        document.getElementById('posCheckinModal').classList.add('hidden');
        // 폼 리셋
        if (typeof window['resetResForm_posCheckinForm'] === 'function') {
            window['resetResForm_posCheckinForm']();
        }
    },

    // ─── 스태프 배정 ───
    async assignStaff(reservationId) {
        const staffId = document.getElementById('assignStaffSelect')?.value;
        if (!staffId) { alert('<?= __('reservations.pos_select_staff') ?>'); return; }
        console.log('[POS] Assign staff:', reservationId, staffId);
        try {
            const body = new URLSearchParams();
            body.append('reservation_ids[]', reservationId);
            body.append('staff_id', staffId);
            const resp = await fetch(`${this.adminUrl}/reservations/assign-staff`, { method: 'POST', body });
            const data = await resp.json();
            console.log('[POS] Assign result:', data);
            if (data.success) {
                this.closeDetail();
                location.reload();
            } else {
                alert(data.message || 'Error');
            }
        } catch (err) { console.error('[POS] Assign error:', err); alert('Error'); }
    },

    // ─── 서비스 시작 (대기 → 이용중) ───
    async startService(id) {
        console.log('[POS] Start service:', id);
        try {
            const resp = await fetch(`${this.adminUrl}/reservations/${id}/start-service`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: `_token=${encodeURIComponent(this.csrfToken)}`
            });
            const data = await resp.json();
            console.log('[POS] Start service result:', data);
            if (data.error) { alert(data.message || '처리 실패'); }
            else { location.reload(); }
        } catch (err) {
            console.error('[POS] Start service error:', err);
            location.reload();
        }
    },

    // ─── 그룹(고객) 단위 일괄 시작 ───
    async startAllServices(group) {
        const ids = group.reservation_ids || [];
        console.log('[POS] Start all services:', ids);
        for (const id of ids) {
            try {
                await fetch(`${this.adminUrl}/reservations/${id}/start-service`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                    body: `_token=${encodeURIComponent(this.csrfToken)}`
                });
            } catch (e) { console.error('[POS] Start error:', id, e); }
        }
        location.reload();
    },

    // ─── 그룹 단위 일괄 취소 ───
    async cancelAllServices(group) {
        if (!confirm('<?= __('reservations.cancel_msg') ?>')) return;
        const ids = group.reservation_ids || [];
        const reason = prompt('<?= __('reservations.cancel_reason') ?>:', '') || '';
        for (const id of ids) {
            try {
                await fetch(`${this.adminUrl}/reservations/${id}/cancel`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                    body: `_token=${encodeURIComponent(this.csrfToken)}&reason=${encodeURIComponent(reason)}`
                });
            } catch (e) { console.error('[POS] Cancel error:', id, e); }
        }
        location.reload();
    },

    // ─── 그룹 단위 일괄 완료 ───
    async completeAllServices(group) {
        if (!confirm('<?= __('reservations.complete_msg') ?>')) return;
        const ids = group.reservation_ids || [];
        for (const id of ids) {
            try {
                await fetch(`${this.adminUrl}/reservations/${id}/complete`, {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                    body: `_token=${encodeURIComponent(this.csrfToken)}`
                });
            } catch (e) { console.error('[POS] Complete error:', id, e); }
        }
        location.reload();
    },

    // ─── 그룹 단위 결제 ───
    _payBaseRemaining: 0,
    _payPointsBalance: 0,
    _payUserId: null,

    async openGroupPayment(group) {
        console.log('[POS] Open group payment:', group);
        this._payGroupIds = group.reservation_ids || [];
        const firstId = this._payGroupIds[0];
        const totalAmount = parseFloat(group.total_amount || 0);
        const designationFee = parseFloat(group.designation_fee || 0);
        const discountRate = parseFloat(group.discount_rate || 0);
        const discountAmount = parseFloat(group.discount_amount || 0);
        const finalAmount = parseFloat(group.final_amount || 0);
        const paidAmount = parseFloat(group.paid_amount || 0);
        const remaining = finalAmount - paidAmount;

        this._payBaseRemaining = remaining;
        this._payUserId = group.user_id || null;

        document.getElementById('payReservationId').value = firstId;
        document.getElementById('payRemaining').textContent = this.fmtCurrency(remaining);
        document.getElementById('payAmount').value = remaining;
        document.getElementById('payAmount').max = remaining;

        // 적립금 UI 초기화
        this._initPayPoints(group.user_id, remaining);

        // 서비스 상세 내역 로드
        const detailEl = document.getElementById('payServiceDetails');
        detailEl.innerHTML = '<p class="text-xs text-zinc-400 text-center py-1"><?= __('admin.messages.processing') ?></p>';

        // 금액 내역 렌더링
        let breakdownHtml = `<div class="flex justify-between"><span class="text-zinc-500"><?= __('reservations.pos_pay_subtotal') ?></span><span class="font-semibold text-zinc-900 dark:text-white">${this.fmtCurrency(totalAmount)}</span></div>`;
        if (designationFee > 0) {
            breakdownHtml += `<div class="flex justify-between"><span class="text-zinc-500"><?= __('reservations.pos_pay_designation') ?></span><span class="font-medium text-zinc-900 dark:text-white">${this.fmtCurrency(designationFee)}</span></div>`;
        }
        if (discountAmount > 0) {
            breakdownHtml += `<div class="flex justify-between"><span class="text-zinc-500"><?= __('reservations.pos_pay_discount') ?> (${discountRate}%)</span><span class="font-medium text-red-500">-${this.fmtCurrency(discountAmount)}</span></div>`;
        }
        if (paidAmount > 0) {
            breakdownHtml += `<div class="flex justify-between"><span class="text-zinc-500"><?= __('reservations.pos_pay_deposit') ?></span><span class="font-medium text-red-500">-${this.fmtCurrency(paidAmount)}</span></div>`;
        }
        document.getElementById('payBreakdown').innerHTML = breakdownHtml;

        document.getElementById('posPaymentModal').classList.remove('hidden');

        // 서비스 목록 비동기 로드
        try {
            const ids = (group.reservation_ids || []).join(',');
            const resp = await fetch(`${this.adminUrl}/reservations/customer-services?ids=${encodeURIComponent(ids)}`);
            const data = await resp.json();
            console.log('[POS] Payment services:', data);
            if (data.success && data.data.length > 0) {
                detailEl.innerHTML = data.data.map(s => `
                    <div class="flex items-center justify-between py-1.5 px-2 bg-zinc-50 dark:bg-zinc-900 rounded text-sm">
                        <span class="text-zinc-700 dark:text-zinc-300 truncate mr-2">${this.escHtml(s.service_name || '-')}</span>
                        <span class="font-medium text-zinc-900 dark:text-white whitespace-nowrap">${this.fmtCurrency(parseFloat(s.price || 0))}</span>
                    </div>`).join('');
            } else {
                detailEl.innerHTML = '';
            }
        } catch (e) {
            console.error('[POS] Load payment services error:', e);
            detailEl.innerHTML = '';
        }
    },

    _payGroupIds: [],

    // ─── 적립금 헬퍼 ───
    async _initPayPoints(userId, remaining) {
        const row = document.getElementById('payPointsRow');
        const input = document.getElementById('payPointsInput');
        if (!row || !input) return;
        input.value = 0;
        this._payPointsBalance = 0;

        if (!userId) { row.classList.add('hidden'); return; }

        try {
            const resp = await fetch(`${this.adminUrl}/reservations/user-points?user_id=${encodeURIComponent(userId)}`);
            const data = await resp.json();
            console.log('[POS] User points:', data);
            if (data.success && data.points_balance > 0) {
                this._payPointsBalance = parseFloat(data.points_balance);
                const maxPts = Math.min(this._payPointsBalance, remaining);
                input.max = maxPts;
                document.getElementById('payPointsBalance').textContent = '<?= get_points_name() ?> <?= __('booking.points_balance') ?>: ' + this.fmtCurrency(this._payPointsBalance);
                row.classList.remove('hidden');
            } else {
                row.classList.add('hidden');
            }
        } catch (e) {
            console.error('[POS] Load points error:', e);
            row.classList.add('hidden');
        }
    },

    recalcPayment() {
        const input = document.getElementById('payPointsInput');
        const pts = Math.max(0, parseInt(input ? input.value : 0) || 0);
        const maxPts = parseInt(input ? input.max : 0) || 0;
        const used = Math.min(pts, maxPts);
        if (input && pts !== used) input.value = used;

        const newRemaining = this._payBaseRemaining - used;
        document.getElementById('payRemaining').textContent = this.fmtCurrency(newRemaining);
        document.getElementById('payAmount').value = newRemaining;
        document.getElementById('payAmount').max = newRemaining;
        console.log('[POS] Recalc payment: points=' + used + ', remaining=' + newRemaining);
    },

    useAllPoints() {
        const input = document.getElementById('payPointsInput');
        if (input) { input.value = input.max; this.recalcPayment(); }
    },

    // ─── 개별 결제 모달 (단건) ───
    openPayment(id, totalAmount, paidAmount) {
        console.log('[POS] Open payment:', id, totalAmount, paidAmount);
        const remaining = totalAmount - paidAmount;
        this._payBaseRemaining = remaining;
        document.getElementById('payReservationId').value = id;
        document.getElementById('payServiceDetails').innerHTML = '';
        document.getElementById('payBreakdown').innerHTML = `
            <div class="flex justify-between"><span class="text-zinc-500"><?= __('reservations.pos_pay_total') ?></span><span class="font-semibold text-zinc-900 dark:text-white">${this.fmtCurrency(totalAmount)}</span></div>
            ${paidAmount > 0 ? `<div class="flex justify-between"><span class="text-zinc-500"><?= __('reservations.pos_pay_paid') ?></span><span class="font-medium text-emerald-600">${this.fmtCurrency(paidAmount)}</span></div>` : ''}`;
        document.getElementById('payRemaining').textContent = this.fmtCurrency(remaining);
        document.getElementById('payAmount').value = remaining;
        document.getElementById('payAmount').max = remaining;
        // 적립금 숨김 (단건은 user_id 없음)
        const ptsRow = document.getElementById('payPointsRow');
        if (ptsRow) ptsRow.classList.add('hidden');
        document.getElementById('posPaymentModal').classList.remove('hidden');
    },

    closePayment(e) {
        if (e && e.target !== e.currentTarget) return;
        document.getElementById('posPaymentModal').classList.add('hidden');
    },

    async submitPayment() {
        const id = document.getElementById('payReservationId').value;
        const amount = document.getElementById('payAmount').value;
        const method = document.getElementById('payMethod').value;
        console.log('[POS] Submit payment:', id, amount, method);
        try {
            const resp = await fetch(`${this.adminUrl}/reservations/${id}/payment`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: `_token=${encodeURIComponent(this.csrfToken)}&amount=${encodeURIComponent(amount)}&method=${encodeURIComponent(method)}&points_used=${encodeURIComponent(document.getElementById('payPointsInput')?.value || 0)}&user_id=${encodeURIComponent(this._payUserId || '')}`
            });
            const data = await resp.json();
            console.log('[POS] Payment result:', data);
            if (data.error) { alert(data.message || '결제 처리 실패'); }
            else { location.reload(); }
        } catch (err) {
            console.error('[POS] Payment error:', err);
            alert('결제 처리 중 오류가 발생했습니다.');
        }
    },

    // ─── 상태 변경 ───
    async changeStatus(id, action) {
        const msgs = {
            confirm: '<?= __('reservations.confirm_msg') ?>',
            cancel: '<?= __('reservations.cancel_msg') ?>',
            complete: '<?= __('reservations.complete_msg') ?>',
            'no-show': '<?= __('reservations.noshow_msg') ?>'
        };
        if (!confirm(msgs[action] || '진행하시겠습니까?')) return;

        let reason = '';
        if (action === 'cancel') {
            reason = prompt('취소 사유:', '') || '';
        }
        try {
            console.log('[POS] Changing status:', id, action);
            const resp = await fetch(`${this.adminUrl}/reservations/${id}/${action}`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body: `_token=${encodeURIComponent(this.csrfToken)}&reason=${encodeURIComponent(reason)}`
            });
            const data = await resp.json();
            console.log('[POS] Status result:', data);
            if (data.error) { alert(data.message || '처리 실패'); }
            else { location.reload(); }
        } catch (err) {
            console.error('[POS] Error:', err);
            location.reload();
        }
    }
};

// ─── reservation-form 취소 버튼 호환 ───
function rzxCalCloseAdd() {
    console.log('[POS] rzxCalCloseAdd → closeCheckinModal');
    POS.closeCheckinModal();
}

// ─── ESC 닫기 ───
document.addEventListener('keydown', e => {
    if (e.key === 'Escape') {
        // 서비스 상세 모달
        const svcModal = document.getElementById('posServiceModal');
        if (svcModal && !svcModal.classList.contains('hidden')) {
            POS.closeServiceModal();
            return;
        }
        // 결제 모달이 열려있으면 먼저 닫기
        const payModal = document.getElementById('posPaymentModal');
        if (payModal && !payModal.classList.contains('hidden')) {
            POS.closePayment();
            return;
        }
        // 접수 모달이 열려있으면 먼저 닫기
        const checkinModal = document.getElementById('posCheckinModal');
        if (checkinModal && !checkinModal.classList.contains('hidden')) {
            POS.closeCheckinModal();
            return;
        }
        POS.closeDetail();
    }
});

// ─── 실시간 시계 ───
setInterval(() => {
    const el = document.getElementById('posClock');
    if (el) el.textContent = new Date().toTimeString().substring(0, 8);
}, 1000);

// ─── 60초 자동 새로고침 (포커스 시) ───
let posAutoRefresh = null;
function startAutoRefresh() {
    if (posAutoRefresh) return;
    posAutoRefresh = setInterval(() => {
        const detailOpen = !document.getElementById('posDetailModal').classList.contains('hidden');
        const checkinOpen = !document.getElementById('posCheckinModal').classList.contains('hidden');
        const payOpen = !document.getElementById('posPaymentModal').classList.contains('hidden');
        const svcOpen = !document.getElementById('posServiceModal').classList.contains('hidden');
        if (document.visibilityState === 'visible' && !detailOpen && !checkinOpen && !payOpen && !svcOpen) {
            console.log('[POS] Auto refresh');
            location.reload();
        }
    }, 60000);
}
startAutoRefresh();
</script>
