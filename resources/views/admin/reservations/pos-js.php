<script>
console.log('[POS] Page loaded');

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
                        <p class="text-zinc-900 dark:text-white">${this.escHtml(r.customer_phone || '-')}</p>
                    </div>
                </div>
                ${r.notes ? '<div class="text-xs text-zinc-500 dark:text-zinc-400 bg-zinc-50 dark:bg-zinc-900 p-2 rounded-lg">' + this.escHtml(r.notes) + '</div>' : ''}
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
        const ids = group.service_ids || [];
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
        const ids = group.service_ids || [];
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
        const ids = group.service_ids || [];
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

    // ─── 그룹 단위 결제 (첫 번째 미결제 건에 전액 적용) ───
    async openGroupPayment(group, totalAmount, paidAmount) {
        // 그룹 내 미결제 서비스 ID 목록 저장
        this._payGroupIds = group.service_ids || [];
        this.openPayment(this._payGroupIds[0], totalAmount, paidAmount);

        // 서비스 상세 내역 로드
        const detailEl = document.getElementById('payServiceDetails');
        detailEl.innerHTML = '<p class="text-xs text-zinc-400 text-center py-1"><?= __('admin.messages.processing') ?></p>';
        try {
            const resp = await fetch(`${this.adminUrl}/reservations/customer-services?name=${encodeURIComponent(group.customer_name)}&phone=${encodeURIComponent(group.customer_phone)}&date=${encodeURIComponent(group.reservation_date)}`);
            const data = await resp.json();
            if (data.success && data.data.length > 0) {
                detailEl.innerHTML = data.data.map(s => `
                    <div class="flex items-center justify-between py-1.5 px-2 bg-zinc-50 dark:bg-zinc-900 rounded text-sm">
                        <span class="text-zinc-700 dark:text-zinc-300 truncate mr-2">${this.escHtml(s.service_name || '-')}</span>
                        <span class="font-medium text-zinc-900 dark:text-white whitespace-nowrap">${this.fmtCurrency(s.final_amount || 0)}</span>
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

    // ─── 결제 모달 ───
    openPayment(id, totalAmount, paidAmount) {
        console.log('[POS] Open payment:', id, totalAmount, paidAmount);
        const remaining = totalAmount - paidAmount;
        document.getElementById('payReservationId').value = id;
        document.getElementById('payServiceDetails').innerHTML = '';
        document.getElementById('payTotalAmount').textContent = this.fmtCurrency(totalAmount);
        document.getElementById('payPaidAmount').textContent = this.fmtCurrency(paidAmount);
        document.getElementById('payRemaining').textContent = this.fmtCurrency(remaining);
        document.getElementById('payAmount').value = remaining;
        document.getElementById('payAmount').max = remaining;
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
                body: `_token=${encodeURIComponent(this.csrfToken)}&amount=${encodeURIComponent(amount)}&method=${encodeURIComponent(method)}`
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
