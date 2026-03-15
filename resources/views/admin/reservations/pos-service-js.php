<script>
/**
 * POS 서비스 상세 모달 JS (pos-js.php에서 분리)
 * POS 객체에 서비스 관련 메서드 확장
 */
Object.assign(POS, {
    // ─── 서비스 상세 모달 ───
    _svcCustomer: null,

    async showServices(r) {
        console.log('[POS] Show services for:', r.customer_name, r.customer_phone);
        this._svcCustomer = { name: r.customer_name, phone: r.customer_phone, email: r.customer_email || '', date: r.reservation_date, source: r.source || 'walk_in' };

        document.getElementById('posServiceTitle').innerHTML = `
            <svg class="w-5 h-5 mr-2 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2"/></svg>
            ${this.escHtml(r.customer_name)}`;
        document.getElementById('posServiceCustomer').innerHTML = `
            <div class="flex items-center justify-between text-sm">
                <span class="text-zinc-500 dark:text-zinc-400">${this.escHtml(r.customer_phone)}</span>
                <span class="text-xs text-zinc-400">${this.escHtml(r.reservation_date)}</span>
            </div>`;

        document.getElementById('posServiceList').innerHTML = '<div class="text-center py-4 text-zinc-400"><svg class="w-5 h-5 animate-spin mx-auto mb-1" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>';
        document.getElementById('posServiceTotal').innerHTML = '';
        document.getElementById('posAddServiceArea').classList.add('hidden');
        document.getElementById('posServiceModal').classList.remove('hidden');

        try {
            const resp = await fetch(`${this.adminUrl}/reservations/customer-services?name=${encodeURIComponent(r.customer_name)}&phone=${encodeURIComponent(r.customer_phone)}&date=${encodeURIComponent(r.reservation_date)}`);
            const data = await resp.json();
            console.log('[POS] Customer services:', data);
            if (data.success) {
                this.renderServiceList(data.data);
            } else {
                document.getElementById('posServiceList').innerHTML = '<p class="text-center text-red-500 text-sm py-4">' + (data.message || 'Error') + '</p>';
            }
        } catch (err) {
            console.error('[POS] Fetch services error:', err);
            document.getElementById('posServiceList').innerHTML = '<p class="text-center text-red-500 text-sm py-4">오류 발생</p>';
        }
    },

    renderServiceList(services) {
        const statusCls = {
            pending: 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400',
            confirmed: 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
            completed: 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
        };
        const statusLabel = {
            pending: '<?= __('reservations.pos_waiting') ?>',
            confirmed: '<?= __('reservations.pos_in_service') ?>',
            completed: '<?= __('reservations.actions.complete') ?>',
        };

        let totalAmount = 0, paidAmount = 0;
        const html = services.map(s => {
            totalAmount += parseFloat(s.final_amount || 0);
            paidAmount += parseFloat(s.paid_amount || 0);
            const badge = statusCls[s.status] || 'bg-zinc-100 text-zinc-700';
            const label = statusLabel[s.status] || s.status;
            const startT = (s.start_time || '').substring(0, 5);
            const endT = (s.end_time || '').substring(0, 5);
            return `<div class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                <div class="flex-1">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-sm font-semibold text-zinc-900 dark:text-white">${this.escHtml(s.service_name || '-')}</span>
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-medium ${badge}">${label}</span>
                    </div>
                    <div class="text-xs text-zinc-500">${startT}${endT ? ' ~ ' + endT : ''} · ${s.service_duration || '-'}<?= __('reservations.pos_min') ?></div>
                </div>
                <span class="text-sm font-bold text-zinc-900 dark:text-white">${this.fmtCurrency(s.final_amount || 0)}</span>
            </div>`;
        }).join('');

        document.getElementById('posServiceList').innerHTML = html || '<p class="text-center text-zinc-400 text-sm py-4"><?= __('reservations.pos_no_services') ?></p>';

        const remaining = totalAmount - paidAmount;
        document.getElementById('posServiceTotal').innerHTML = `
            <div class="flex items-center justify-between py-2 text-sm">
                <span class="text-zinc-500"><?= __('reservations.pos_pay_total') ?> (${services.length}<?= __('reservations.pos_service_count') ?>)</span>
                <span class="font-bold text-zinc-900 dark:text-white">${this.fmtCurrency(totalAmount)}</span>
            </div>
            ${paidAmount > 0 ? `<div class="flex items-center justify-between pb-2 text-sm">
                <span class="text-zinc-500"><?= __('reservations.pos_pay_paid') ?></span>
                <span class="text-emerald-600">${this.fmtCurrency(paidAmount)}</span>
            </div>` : ''}
            ${remaining > 0 ? `<div class="flex items-center justify-between pb-2 text-sm">
                <span class="font-medium text-zinc-700 dark:text-zinc-300"><?= __('reservations.pos_pay_remaining') ?></span>
                <span class="font-bold text-violet-600">${this.fmtCurrency(remaining)}</span>
            </div>` : ''}`;

        this._existingServiceIds = services.map(s => String(s.service_id));
    },

    _existingServiceIds: [],

    toggleAddService() {
        const area = document.getElementById('posAddServiceArea');
        const isHidden = area.classList.contains('hidden');
        if (isHidden) {
            area.classList.remove('hidden');
            const html = posAllServices.map(s => {
                const already = this._existingServiceIds.includes(String(s.id));
                return `<label class="flex items-center p-2.5 rounded-lg border border-zinc-200 dark:border-zinc-700 ${already ? 'opacity-40' : 'cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-700/50'}">
                    <input type="checkbox" value="${s.id}" class="pos-add-svc-check mr-3 rounded text-blue-600" ${already ? 'disabled' : ''} onchange="POS.onAddServiceCheck()">
                    <div class="flex-1">
                        <p class="text-sm font-medium text-zinc-900 dark:text-white">${this.escHtml(s.name)}</p>
                        <p class="text-xs text-zinc-500">${s.duration}<?= __('reservations.pos_min') ?></p>
                    </div>
                    <span class="text-sm font-bold text-zinc-700 dark:text-zinc-300">${this.fmtCurrency(s.price)}</span>
                </label>`;
            }).join('');
            document.getElementById('posAddServiceList').innerHTML = html;
            document.getElementById('posAddServiceBtn').disabled = true;
        } else {
            area.classList.add('hidden');
        }
    },

    onAddServiceCheck() {
        const checked = document.querySelectorAll('.pos-add-svc-check:checked');
        document.getElementById('posAddServiceBtn').disabled = checked.length === 0;
        console.log('[POS] Add service checked:', checked.length);
    },

    async submitAddService() {
        const checked = document.querySelectorAll('.pos-add-svc-check:checked');
        if (checked.length === 0) return;
        const btn = document.getElementById('posAddServiceBtn');
        btn.disabled = true;
        btn.textContent = '<?= __('admin.messages.processing') ?>';

        const c = this._svcCustomer;
        const body = new URLSearchParams();
        body.append('_token', this.csrfToken);
        body.append('customer_name', c.name);
        body.append('customer_phone', c.phone);
        body.append('customer_email', c.email);
        body.append('reservation_date', c.date);
        body.append('source', c.source);
        checked.forEach(cb => body.append('service_ids[]', cb.value));

        console.log('[POS] Adding services:', [...checked].map(cb => cb.value));
        try {
            const resp = await fetch(`${this.adminUrl}/reservations/add-service`, { method: 'POST', body });
            const data = await resp.json();
            console.log('[POS] Add service result:', data);
            if (data.success) {
                location.reload();
            } else {
                alert(data.message || '서비스 추가 실패');
                btn.disabled = false;
                btn.textContent = '<?= __('reservations.pos_add_service_submit') ?>';
            }
        } catch (err) {
            console.error('[POS] Add service error:', err);
            alert('오류가 발생했습니다.');
            btn.disabled = false;
            btn.textContent = '<?= __('reservations.pos_add_service_submit') ?>';
        }
    },

    closeServiceModal(e) {
        if (e && e.target !== e.currentTarget) return;
        document.getElementById('posServiceModal').classList.add('hidden');
    },
});
</script>
