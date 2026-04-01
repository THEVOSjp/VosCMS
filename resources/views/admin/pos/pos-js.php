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
                ${r.status === 'cancelled' && (r.cancel_reason || r.cancelled_at) ? `<div class="p-3 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg space-y-1">
                    <p class="text-xs font-medium text-red-700 dark:text-red-400"><?= __('reservations.show_cancel_reason') ?></p>
                    ${r.cancel_reason ? '<p class="text-xs text-red-600 dark:text-red-300">' + this.escHtml(r.cancel_reason) + '</p>' : '<p class="text-xs text-zinc-400"><?= __('reservations.no_reason') ?? '사유 없음' ?></p>'}
                    ${r.cancelled_at ? '<p class="text-[10px] text-red-400">' + this.escHtml(r.cancelled_at) + '</p>' : ''}
                </div>` : ''}
                ${(r.status === 'cancelled' || r.status === 'no_show')
                    ? (r.staff_name ? `<div class="text-xs text-zinc-500"><span class="text-zinc-400"><?= __('reservations.pos_staff') ?>:</span> ${this.escHtml(r.staff_name)}</div>` : '')
                    : (!r.staff_id ? `<div class="p-3 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
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
                </div>` : `<div class="text-xs text-zinc-500"><span class="text-zinc-400"><?= __('reservations.pos_staff') ?>:</span> ${this.escHtml(r.staff_name || '-')}</div>`)}
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

    // ─── 카드에서 결제 버튼 클릭 시 통합 결제 모달 ───
    openUnifiedPayFromCard(group) {
        console.log('[POS] Open unified pay from card:', group);
        const finalAmount = parseFloat(group.final_amount || 0);
        const paidAmount = parseFloat(group.paid_amount || 0);
        const remaining = Math.max(0, finalAmount - paidAmount);
        const resId = (group.reservation_ids || [group.id])[0];
        // 고객 정보 저장 (적립금 조회용)
        this._svcCustomer = { name: group.customer_name, phone: group.customer_phone, email: group.customer_email || '', user_id: group.user_id || '' };
        this.openUnifiedPay(resId, remaining, finalAmount, paidAmount);
    },

    // ─── 통합 결제 모달 (적립금 + 현금 + 카드 + 할부) ───
    _uPay: { resId: '', total: 0, points: 0, pointsBal: 0, cash: 0, card: 0, installment: 1 },

    openUnifiedPay(resId, amount, finalTotal, totalPaid) {
        console.log('[POS] Open unified pay:', resId, amount, 'final:', finalTotal, 'paid:', totalPaid);
        finalTotal = finalTotal || amount;
        totalPaid = totalPaid || 0;
        // total을 전체 금액(finalTotal)으로 설정 — 현금/카드란이 전체 금액을 배분
        this._uPay = { resId, total: finalTotal, finalTotal, totalPaid, remaining: amount, points: 0, pointsBal: 0, cash: 0, card: 0, installment: 1 };
        const sym = '<?= $currencySymbol ?? '¥' ?>';
        const html = `<div id="posPayOverlay" class="fixed inset-0 bg-black/50 z-[60] flex items-center justify-center p-4">
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-2xl w-full max-w-md p-6">
                <div class="flex items-center justify-between mb-4">
                    <h3 class="text-lg font-semibold text-zinc-900 dark:text-white flex items-center gap-2">
                        <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                        <?= __('reservations.pos_pay_proceed') ?? '결제 진행' ?>
                    </h3>
                    <button onclick="document.getElementById('posPayOverlay').remove()" class="p-1 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg">
                        <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>

                <div class="space-y-3">
                    <!-- 결제 금액 -->
                    <div class="flex justify-between items-center bg-zinc-50 dark:bg-zinc-900 rounded-lg p-3">
                        <span class="text-sm text-zinc-500 font-medium"><?= __('reservations.show_final_amount') ?? '최종 결제 금액' ?></span>
                        <span class="font-bold text-xl text-zinc-900 dark:text-white">${sym}${Number(finalTotal).toLocaleString()}</span>
                    </div>

                    <!-- ① 적립금 -->
                    <div id="uPayPointsRow" class="hidden border border-yellow-200 dark:border-yellow-800 bg-yellow-50 dark:bg-yellow-900/20 rounded-lg p-3 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="text-xs font-semibold text-yellow-700 dark:text-yellow-400 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/></svg>
                                <?= get_points_name() ?>
                            </span>
                            <span id="uPayPointsBal" class="text-[10px] text-yellow-600 dark:text-yellow-400"></span>
                        </div>
                        <div class="flex items-center gap-2">
                            <input type="number" id="uPayPointsInput" value="0" min="0" max="0"
                                   class="flex-1 px-2 py-1.5 border border-yellow-300 dark:border-yellow-700 dark:bg-zinc-700 dark:text-white rounded text-sm font-mono text-right"
                                   oninput="POS._uPayRecalc('points')">
                            <button type="button" onclick="document.getElementById('uPayPointsInput').value=document.getElementById('uPayPointsInput').max;POS._uPayRecalc('points')"
                                class="px-2 py-1.5 text-[10px] font-medium text-yellow-700 bg-yellow-200 hover:bg-yellow-300 dark:bg-yellow-800 dark:text-yellow-300 rounded transition"><?= __('reservations.pos_pay_use_all') ?? '전액' ?></button>
                        </div>
                    </div>

                    <!-- ② 현금 -->
                    <div class="border border-green-200 dark:border-green-800 rounded-lg p-3 space-y-2">
                        <div class="flex items-center justify-between">
                            <label class="text-xs font-semibold text-green-700 dark:text-green-400 flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 9V7a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2m2 4h10a2 2 0 002-2v-6a2 2 0 00-2-2H9a2 2 0 00-2 2v6a2 2 0 002 2zm7-5a2 2 0 11-4 0 2 2 0 014 0z"/></svg>
                                <?= __('reservations.pay_cash') ?? '현금' ?>
                            </label>
                            <button type="button" onclick="POS._uPayFillCash()" class="px-2 py-0.5 text-[10px] font-medium text-green-600 bg-green-100 hover:bg-green-200 dark:bg-green-900/30 dark:text-green-400 rounded transition"><?= __('reservations.pos_pay_use_all') ?? '전액' ?></button>
                        </div>
                        <input type="number" id="uPayCashInput" value="0" min="0"
                               class="w-full px-2 py-1.5 border border-green-300 dark:border-green-700 dark:bg-zinc-700 dark:text-white rounded text-sm font-mono text-right"
                               oninput="POS._uPayRecalc('cash')">
                    </div>

                    <!-- ③ 카드 -->
                    <div class="border border-blue-200 dark:border-blue-800 rounded-lg p-3 space-y-2">
                        <label class="text-xs font-semibold text-blue-700 dark:text-blue-400 flex items-center gap-1">
                            <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z"/></svg>
                            <?= __('reservations.pay_card') ?? '카드' ?>
                        </label>
                        <input type="number" id="uPayCardInput" value="0" min="0"
                               class="w-full px-2 py-1.5 border border-blue-300 dark:border-blue-700 dark:bg-zinc-700 dark:text-white rounded text-sm font-mono text-right"
                               oninput="POS._uPayRecalc('card')">
                        <!-- 할부 -->
                        <div class="flex items-center gap-2 mt-1">
                            <label class="text-[10px] text-zinc-500 dark:text-zinc-400 whitespace-nowrap"><?= __('reservations.pos_pay_installment') ?? '할부' ?></label>
                            <select id="uPayInstallment" class="flex-1 px-2 py-1 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded text-xs">
                                <option value="1"><?= __('reservations.pos_pay_lump_sum') ?? '일시불' ?></option>
                                <option value="2">2<?= __('reservations.pos_pay_months') ?? '개월' ?></option>
                                <option value="3">3<?= __('reservations.pos_pay_months') ?? '개월' ?></option>
                                <option value="4">4<?= __('reservations.pos_pay_months') ?? '개월' ?></option>
                                <option value="5">5<?= __('reservations.pos_pay_months') ?? '개월' ?></option>
                                <option value="6">6<?= __('reservations.pos_pay_months') ?? '개월' ?></option>
                                <option value="10">10<?= __('reservations.pos_pay_months') ?? '개월' ?></option>
                                <option value="12">12<?= __('reservations.pos_pay_months') ?? '개월' ?></option>
                            </select>
                        </div>
                    </div>

                    <!-- 정산 요약 -->
                    <div id="uPaySummary" class="bg-zinc-50 dark:bg-zinc-900 rounded-lg p-3 space-y-1 text-sm"></div>

                    <!-- 거스름돈/부족 -->
                    <div id="uPayChangeRow" class="hidden flex justify-between items-center p-3 bg-blue-50 dark:bg-blue-900/20 rounded-lg">
                        <span class="text-sm text-blue-700 dark:text-blue-300"><?= __('reservations.pay_change') ?? '거스름돈' ?></span>
                        <span id="uPayChangeAmt" class="text-lg font-bold text-blue-600 dark:text-blue-400"></span>
                    </div>
                    <div id="uPayShortRow" class="hidden flex justify-between items-center p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                        <span class="text-sm text-red-700 dark:text-red-300"><?= __('reservations.pos_pay_short') ?? '부족' ?></span>
                        <span id="uPayShortAmt" class="text-lg font-bold text-red-600 dark:text-red-400"></span>
                    </div>
                </div>

                <div class="flex gap-3 mt-5">
                    <button onclick="document.getElementById('posPayOverlay').remove()"
                        class="flex-1 px-4 py-2.5 text-sm font-medium text-zinc-600 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 rounded-lg hover:bg-zinc-200 transition">
                        <?= __('common.buttons.cancel') ?? '취소' ?>
                    </button>
                    <button id="uPaySubmitBtn" onclick="POS._uPaySubmit()" disabled
                        class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition disabled:opacity-50">
                        <?= __('reservations.pay_confirm') ?? '결제 완료' ?>
                    </button>
                </div>
            </div>
        </div>`;
        document.body.insertAdjacentHTML('beforeend', html);
        this._uPayInitPoints();
    },

    _uPaySetDefaults() {
        if (this._uPay.totalPaid > 0) {
            document.getElementById('uPayCashInput').value = this._uPay.totalPaid;
            document.getElementById('uPayCardInput').value = this._uPay.remaining;
        } else {
            document.getElementById('uPayCardInput').value = this._uPay.total;
        }
    },

    async _uPayInitPoints() {
        const userId = this._svcCustomer?.user_id || '';
        if (!userId) { this._uPaySetDefaults(); this._uPayRecalc(); return; }
        try {
            const resp = await fetch(`${this.adminUrl}/reservations/user-points?user_id=${encodeURIComponent(userId)}`);
            const data = await resp.json();
            if (data.success && data.points_balance > 0) {
                this._uPay.pointsBal = parseFloat(data.points_balance);
                const maxPts = Math.min(this._uPay.pointsBal, this._uPay.total);
                const sym = '<?= $currencySymbol ?? '¥' ?>';
                document.getElementById('uPayPointsBal').textContent = '<?= __('booking.points_balance') ?? '잔액' ?>: ' + sym + Number(this._uPay.pointsBal).toLocaleString();
                document.getElementById('uPayPointsInput').max = maxPts;
                document.getElementById('uPayPointsRow').classList.remove('hidden');
            }
        } catch (e) { console.error('[POS] Load points error:', e); }

        this._uPaySetDefaults();
        this._uPayRecalc();
    },

    _uPayFillCash() {
        const pts = Math.max(0, Math.min(parseInt(document.getElementById('uPayPointsInput')?.value) || 0, parseInt(document.getElementById('uPayPointsInput')?.max) || 0));
        const total = this._uPay.total;
        document.getElementById('uPayCashInput').value = Math.max(0, total - pts);
        this._uPayRecalc('cash');
    },

    _uPayRecalc(source) {
        const sym = '<?= $currencySymbol ?? '¥' ?>';
        const total = this._uPay.total;

        // 적립금
        let pts = Math.max(0, parseInt(document.getElementById('uPayPointsInput')?.value) || 0);
        const maxPts = Math.min(this._uPay.pointsBal, total);
        if (pts > maxPts) { pts = maxPts; if (document.getElementById('uPayPointsInput')) document.getElementById('uPayPointsInput').value = pts; }
        this._uPay.points = pts;

        // 현금
        const cash = Math.max(0, parseFloat(document.getElementById('uPayCashInput').value) || 0);
        this._uPay.cash = cash;

        // 현금 또는 적립금 수정 시 → 카드 금액 자동 연동
        if (source === 'cash' || source === 'points') {
            const autoCard = Math.max(0, total - pts - cash);
            document.getElementById('uPayCardInput').value = autoCard;
        }

        // 카드
        const card = Math.max(0, parseFloat(document.getElementById('uPayCardInput').value) || 0);
        this._uPay.card = card;

        // 할부
        this._uPay.installment = parseInt(document.getElementById('uPayInstallment')?.value) || 1;

        const paid = pts + cash + card;
        const diff = paid - total;

        // 요약
        let summary = '';
        if (pts > 0) summary += `<div class="flex justify-between"><span class="text-yellow-600"><?= get_points_name() ?></span><span class="text-yellow-600">-${sym}${Number(pts).toLocaleString()}</span></div>`;
        if (cash > 0) summary += `<div class="flex justify-between"><span class="text-green-600"><?= __('reservations.pay_cash') ?? '현금' ?></span><span class="text-green-600">${sym}${Number(cash).toLocaleString()}</span></div>`;
        if (card > 0) {
            let cardLabel = '<?= __('reservations.pay_card') ?? '카드' ?>';
            if (this._uPay.installment > 1) cardLabel += ` (${this._uPay.installment}<?= __('reservations.pos_pay_months') ?? '개월' ?>)`;
            summary += `<div class="flex justify-between"><span class="text-blue-600">${cardLabel}</span><span class="text-blue-600">${sym}${Number(card).toLocaleString()}</span></div>`;
        }
        if (summary) {
            summary += `<div class="flex justify-between pt-1 mt-1 border-t border-zinc-200 dark:border-zinc-700 font-bold"><span><?= __('reservations.pos_pay_total_pay') ?? '합계' ?></span><span>${sym}${Number(paid).toLocaleString()}</span></div>`;
        }
        document.getElementById('uPaySummary').innerHTML = summary;

        // 거스름돈/부족
        const changeRow = document.getElementById('uPayChangeRow');
        const shortRow = document.getElementById('uPayShortRow');
        const btn = document.getElementById('uPaySubmitBtn');

        if (diff >= 0 && paid > 0) {
            if (diff > 0 && cash > 0) {
                document.getElementById('uPayChangeAmt').textContent = sym + Number(diff).toLocaleString();
                changeRow.classList.remove('hidden');
            } else {
                changeRow.classList.add('hidden');
            }
            shortRow.classList.add('hidden');
            btn.disabled = false;
        } else if (paid > 0 && diff < 0) {
            document.getElementById('uPayShortAmt').textContent = sym + Number(Math.abs(diff)).toLocaleString();
            shortRow.classList.remove('hidden');
            changeRow.classList.add('hidden');
            btn.disabled = true;
        } else {
            changeRow.classList.add('hidden');
            shortRow.classList.add('hidden');
            btn.disabled = true;
        }
    },

    async _uPaySubmit() {
        const u = this._uPay;
        const btn = document.getElementById('uPaySubmitBtn');
        btn.disabled = true; btn.textContent = '<?= __('admin.messages.processing') ?? '처리중...' ?>';

        // 실제 결제할 금액 = 입력 금액 - 기결제 금액 (차액만 결제)
        const actualCash = Math.max(0, u.cash - u.totalPaid);  // 현금 추가분
        const actualCard = u.card;  // 카드는 전액 신규
        const actualAmount = actualCash + actualCard;
        console.log('[POS] Unified payment:', u, 'actual cash:', actualCash, 'actual card:', actualCard);

        // 이미 전액 결제된 경우
        if (actualAmount <= 0 && u.points <= 0) {
            document.getElementById('posPayOverlay')?.remove();
            location.reload();
            return;
        }

        // 결제 method 결정
        let method = 'cash';
        if (actualCash > 0 && actualCard > 0) method = 'mixed';
        else if (actualCard > 0) method = 'card';
        else if (actualCash > 0) method = 'cash';
        else if (u.points > 0) method = 'points';

        // 카드 결제가 있고 Stripe 활성화 → Stripe checkout
        if (actualCard > 0 && typeof posStripeEnabled !== 'undefined' && posStripeEnabled) {
            document.getElementById('posPayOverlay')?.remove();
            const appUrl = this.adminUrl.replace(/\/[^/]+$/, '');
            const checkoutUrl = appUrl + '/payment/checkout?reservation_id=' + encodeURIComponent(u.resId) + '&admin=1&amount=' + actualCard + '&points_used=0&installment=' + u.installment + '&no_layout=1';
            this._pendingCashAfterStripe = { resId: u.resId, cash: actualCash, points: u.points, userId: this._svcCustomer?.user_id || '' };
            this._openStripeModal(checkoutUrl);
            return;
        }

        // Stripe 없음 → API로 직접 처리
        try {
            const body = `_token=${encodeURIComponent(this.csrfToken)}&amount=${actualAmount}&method=${method}&cash_amount=${actualCash}&card_amount=${actualCard}&installment=${u.installment}&points_used=${u.points}&user_id=${encodeURIComponent(this._svcCustomer?.user_id || '')}`;
            const resp = await fetch(`${this.adminUrl}/reservations/${u.resId}/payment`, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                body
            });
            const data = await resp.json();
            console.log('[POS] Payment result:', data);
            document.getElementById('posPayOverlay')?.remove();
            if (data.error) { alert(data.message || '결제 처리 실패'); }
            else { location.reload(); }
        } catch (err) {
            console.error('[POS] Payment error:', err);
            document.getElementById('posPayOverlay')?.remove();
            alert('오류가 발생했습니다.');
        }
    },

    _openStripeModal(url) {
        console.log('[POS] Stripe modal:', url);
        const html = `<div id="posStripeOverlay" class="fixed inset-0 bg-black/50 z-[70] flex items-center justify-center p-4">
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-2xl overflow-hidden" style="width:1030px;max-width:95vw;max-height:90vh;">
                <div class="flex items-center justify-between px-4 py-3 border-b border-zinc-200 dark:border-zinc-700">
                    <span class="text-sm font-semibold text-zinc-900 dark:text-white"><?= __('reservations.pay_card') ?? '카드 결제' ?></span>
                    <button onclick="document.getElementById('posStripeOverlay').remove()" class="p-1 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg">
                        <svg class="w-5 h-5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <iframe src="${url}" class="w-full" style="height:800px;border:none;"></iframe>
            </div>
        </div>`;
        document.body.insertAdjacentHTML('beforeend', html);
        // 결제 완료 메시지 감지
        window.addEventListener('message', async function _posStripeHandler(e) {
            if (e.data === 'payment_complete' || e.data === 'payment_success' || e.data?.type === 'payment_success') {
                console.log('[POS] Stripe payment success');
                document.getElementById('posStripeOverlay')?.remove();
                window.removeEventListener('message', _posStripeHandler);
                // Stripe 성공 후 현금/적립금 처리
                const pending = POS._pendingCashAfterStripe;
                if (pending && (pending.cash > 0 || pending.points > 0)) {
                    try {
                        await fetch(`${POS.adminUrl}/reservations/${pending.resId}/payment`, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
                            body: `_token=${encodeURIComponent(POS.csrfToken)}&amount=${pending.cash}&method=cash&points_used=${pending.points}&user_id=${encodeURIComponent(pending.userId)}`
                        });
                    } catch (e) { console.error('[POS] Post-stripe cash/points error:', e); }
                    POS._pendingCashAfterStripe = null;
                }
                location.reload();
            }
        });
    },

    _pendingCashAfterStripe: null,

    // ─── 영수증 인쇄 (현재 모달 화면 그대로 새 창에 복제) ───
    printReceipt() {
        console.log('[POS] Print service detail modal');
        const modalContent = document.querySelector('#posServiceModal > div');
        if (!modalContent) { window.print(); return; }

        // 모달 내용 복제
        const clone = modalContent.cloneNode(true);

        // 불필요 요소 제거
        clone.querySelectorAll('#posAddServiceToggle, #posAssignStaffToggle, #posAddServiceArea, #posAssignStaffArea, #posMemoArea, #posReceiptArea, #posCustomerDetail').forEach(el => el.remove());
        clone.querySelectorAll('button[onclick*="closeServiceModal"], button[onclick*="removeService"], button[onclick*="openUnifiedPay"]').forEach(el => el.remove());

        // 현재 페이지의 모든 스타일시트 수집
        let styles = '';
        document.querySelectorAll('style, link[rel="stylesheet"]').forEach(el => {
            styles += el.outerHTML;
        });

        const printWin = window.open('', '_blank', 'width=900,height=1000');
        printWin.document.write(`<!DOCTYPE html>
<html class="${document.documentElement.className}">
<head>
    <meta charset="utf-8">
    <title><?= __('reservations.show_print') ?? '인쇄' ?></title>
    ${styles}
    <style>
        body { margin: 0; padding: 0; background: white; }
        .print-wrap {
            max-width: 800px;
            margin: 0 auto;
            background: white;
            overflow: visible;
            max-height: none;
            border-radius: 0;
            box-shadow: none;
        }
        .print-wrap .overflow-y-auto,
        .print-wrap .overflow-hidden { overflow: visible !important; max-height: none !important; }
        .print-wrap .max-h-\\[90vh\\] { max-height: none !important; }
        @media print {
            * { -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
            body { padding: 0; }
        }
    </style>
</head>
<body>
    <div class="print-wrap">${clone.innerHTML}</div>
</body>
</html>`);
        printWin.document.close();
        printWin.onload = () => { setTimeout(() => { printWin.print(); }, 500); };
    },

    // ─── 영수증 출력 (영수증 형식) ───
    printReceiptFormatted() {
        console.log('[POS] Print receipt formatted');
        const svcData = this._serviceData || (typeof POS._serviceData !== 'undefined' ? POS._serviceData : null);
        const customer = this._svcCustomer;
        if (!svcData || svcData.length === 0) { alert('서비스 데이터가 없습니다.'); return; }

        const sym = this.currency.symbol;
        const fmt = (n) => sym + Number(n).toLocaleString();
        const now = new Date();
        const dateStr = now.getFullYear() + '-' + String(now.getMonth()+1).padStart(2,'0') + '-' + String(now.getDate()).padStart(2,'0');
        const timeStr = String(now.getHours()).padStart(2,'0') + ':' + String(now.getMinutes()).padStart(2,'0');

        const storeName = '<?= htmlspecialchars($config['app_name'] ?? 'RezlyX') ?>';
        const resNum = svcData[0].reservation_number || '';
        const staffName = svcData[0].staff_name || '';

        // 서비스 항목
        let svcRows = '';
        let subtotal = 0;
        svcData.forEach(s => {
            const price = parseFloat(s.price) || 0;
            subtotal += price;
            svcRows += `<tr>
                <td style="padding:4px 0;border-bottom:1px dotted #ddd;">${this.escHtml(s.service_name)}</td>
                <td style="padding:4px 0;border-bottom:1px dotted #ddd;text-align:right;white-space:nowrap;">${fmt(price)}</td>
            </tr>`;
        });

        // 금액 계산
        const designationFee = parseFloat(svcData[0].designation_fee) || 0;
        const finalAmount = parseFloat(svcData[0].final_amount) || subtotal + designationFee;
        const paidAmount = parseFloat(svcData[0].reservation_paid) || 0;
        const remaining = finalAmount - paidAmount;

        const printWin = window.open('', '_blank', 'width=400,height=700');
        printWin.document.write(`<!DOCTYPE html>
<html><head><meta charset="utf-8"><title><?= __('reservations.show_receipt') ?? '영수증' ?></title>
<style>
    * { margin:0; padding:0; box-sizing:border-box; }
    body { font-family:'Courier New',monospace; width:300px; margin:0 auto; padding:20px 10px; color:#000; font-size:12px; }
    .center { text-align:center; }
    .right { text-align:right; }
    .bold { font-weight:bold; }
    .store-name { font-size:18px; font-weight:bold; margin-bottom:4px; }
    .divider { border-top:1px dashed #000; margin:8px 0; }
    .double-divider { border-top:2px solid #000; margin:8px 0; }
    table { width:100%; border-collapse:collapse; }
    td { padding:3px 0; vertical-align:top; }
    .label { color:#555; }
    .total-row td { padding:6px 0; font-weight:bold; font-size:18px; }
    .footer { margin-top:15px; font-size:10px; color:#888; }
    @media print {
        body { width:100%; padding:5px; }
        @page { margin:5mm; }
    }
</style>
</head><body>

<div class="center">
    <div class="store-name">${this.escHtml(storeName)}</div>
    <div style="font-size:10px;color:#888;"><?= __('reservations.show_receipt') ?? '영수증' ?></div>
</div>

<div class="double-divider"></div>

<table>
    <tr><td class="label"><?= __('reservations.receipt_number') ?? '번호' ?></td><td class="right" style="font-size:11px;">${this.escHtml(resNum)}</td></tr>
    <tr><td class="label"><?= __('reservations.receipt_date') ?? '일시' ?></td><td class="right">${dateStr} ${timeStr}</td></tr>
    <tr><td class="label"><?= __('reservations.receipt_customer') ?? '고객' ?></td><td class="right">${this.escHtml(customer?.name || '')}</td></tr>
    ${staffName ? `<tr><td class="label"><?= __('reservations.receipt_staff') ?? '담당' ?></td><td class="right">${this.escHtml(staffName)}</td></tr>` : ''}
</table>

<div class="double-divider"></div>

<table>${svcRows}</table>

<div class="divider"></div>

<table>
    <tr><td class="label"><?= __('reservations.pos_pay_total') ?? '합계' ?></td><td class="right">${fmt(subtotal)}</td></tr>
    ${designationFee > 0 ? `<tr><td class="label"><?= __('reservations.pos_pay_designation') ?? '지명비' ?></td><td class="right">+${fmt(designationFee)}</td></tr>` : ''}
</table>

<div class="double-divider"></div>

<table>
    <tr class="total-row"><td><?= __('reservations.show_final_amount') ?? '최종 금액' ?></td><td class="right">${fmt(finalAmount)}</td></tr>
    ${paidAmount > 0 ? `<tr class="total-row"><td><?= __('reservations.pos_pay_paid') ?? '결제 완료' ?></td><td class="right">${fmt(paidAmount)}</td></tr>` : ''}
    ${remaining > 0 ? `<tr class="total-row"><td style="color:red;"><?= __('reservations.pos_pay_remaining') ?? '잔액' ?></td><td class="right" style="color:red;">${fmt(remaining)}</td></tr>` : ''}
    ${remaining <= 0 && paidAmount > 0 ? `<tr class="total-row"><td style="color:green;"><?= __('reservations.pos_pay_paid') ?? '결제 완료' ?></td><td class="right" style="color:green;">✓</td></tr>` : ''}
</table>

<div class="double-divider"></div>

<div class="center footer">
    <p><?= __('reservations.receipt_thanks') ?? '이용해 주셔서 감사합니다' ?></p>
    <p style="margin-top:4px;">${this.escHtml(storeName)}</p>
</div>

</body></html>`);
        printWin.document.close();
        printWin.onload = () => { setTimeout(() => { printWin.print(); }, 300); };
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
