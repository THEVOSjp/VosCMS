<script>
/**
 * POS 서비스 상세 모달 JS (pos-js.php에서 분리)
 * POS 객체에 서비스 관련 메서드 확장
 */
Object.assign(POS, {
    // ─── 이미지 URL 헬퍼 ───
    _resolveImgUrl(path) {
        if (!path) return '';
        if (path.startsWith('http')) return path;
        // adminUrl에서 /theadmin 또는 /admin 등 마지막 경로 세그먼트 제거
        const appUrl = this.adminUrl.replace(/\/[^/]+$/, '');
        return path.startsWith('/') ? appUrl + path : appUrl + '/storage/' + path;
    },

    // ─── 서비스 상세 모달 ───
    _svcCustomer: null,

    async showServices(r) {
        console.log('[POS] Show services for:', r.customer_name, r.customer_phone);
        this._svcCustomer = { name: r.customer_name, phone: r.customer_phone, email: r.customer_email || '', date: r.reservation_date, source: r.source || 'walk_in', user_id: r.user_id || '', reservation_ids: r.reservation_ids || [] };

        // 헤더: 고객 프로필 (왼쪽) + 스태프 (오른쪽, API 후 갱신)
        document.getElementById('posServiceTitle').innerHTML = '<?= __('reservations.pos_service_detail') ?>';
        document.getElementById('posServiceCustomer').innerHTML = `
            <div class="px-5 py-3 border-b border-zinc-100 dark:border-zinc-700">
                <div class="flex items-center justify-between">
                    <!-- 왼쪽: 고객 프로필 -->
                    <div class="flex items-center gap-3 flex-1 min-w-0">
                        <div id="posProfileImg" class="w-20 h-20 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center flex-shrink-0 border-2 border-zinc-300 dark:border-zinc-600">
                            <svg class="w-8 h-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="flex items-center gap-2">
                                <span class="text-base font-bold text-zinc-900 dark:text-white">${this.escHtml(r.customer_name)}</span>
                                <span id="posCustBadges" class="flex items-center gap-1"></span>
                            </div>
                            <div class="flex items-center gap-3 text-xs text-zinc-500 dark:text-zinc-400 mt-1">
                                <span>${this.escHtml(r.customer_phone)}</span>
                                <span>${this.escHtml(r.reservation_date)}</span>
                            </div>
                            <div id="posCustStats" class="text-xs text-zinc-500 dark:text-zinc-400 mt-0.5"></div>
                        </div>
                    </div>
                    <!-- 오른쪽: 배정/지명 스태프 (API 후 갱신) -->
                    <div id="posStaffHeader" class="flex-shrink-0 ml-4 hidden">
                    </div>
                </div>
            </div>`;

        document.getElementById('posServiceList').innerHTML = '<div class="text-center py-6 text-zinc-400"><svg class="w-5 h-5 animate-spin mx-auto mb-1" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>';
        document.getElementById('posServiceTotal').innerHTML = '';
        document.getElementById('posCustomerDetail').innerHTML = '';
        document.getElementById('posAddServiceArea').classList.add('hidden');
        document.getElementById('posAssignStaffArea').classList.add('hidden');
        document.getElementById('posMemoArea').classList.add('hidden');
        document.getElementById('posServiceModal').classList.remove('hidden');

        try {
            const ids = (r.reservation_ids || []).join(',');
            const resp = await fetch(`${this.adminUrl}/reservations/customer-services?ids=${encodeURIComponent(ids)}`);
            const data = await resp.json();
            console.log('[POS] Customer services:', data);
            if (data.success) {
                this.renderCustomerProfile(data.customer || {}, data.memos || []);
                this.renderStaffHeader(data.data, data.customer || {});
                this.renderServiceList(data.data);
            } else {
                document.getElementById('posServiceList').innerHTML = '<p class="text-center text-red-500 text-sm py-4">' + (data.message || 'Error') + '</p>';
            }
        } catch (err) {
            console.error('[POS] Fetch services error:', err);
            document.getElementById('posServiceList').innerHTML = '<p class="text-center text-red-500 text-sm py-4">오류 발생</p>';
        }
    },

    renderStaffHeader(services, customer) {
        const el = document.getElementById('posStaffHeader');
        if (!services || services.length === 0) { el.classList.add('hidden'); return; }

        // 첫 번째 서비스의 스태프 정보 사용
        const s = services[0];
        if (!s.staff_id || !s.staff_name) { el.classList.add('hidden'); return; }

        const isDesignation = (customer.designation_fee || 0) > 0;
        const typeBadge = isDesignation
            ? '<span class="px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400"><?= __('reservations.pos_designation') ?></span>'
            : '<span class="px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400"><?= __('reservations.pos_assignment') ?></span>';

        let avatarHtml = `<svg class="w-8 h-8 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>`;
        if (s.staff_avatar) {
            avatarHtml = `<img src="${this.escHtml(this._resolveImgUrl(s.staff_avatar))}" class="w-16 h-16 rounded-full object-cover">`;
        }

        const borderColor = isDesignation ? 'border-violet-300 dark:border-violet-600' : 'border-emerald-300 dark:border-emerald-600';
        const changeBtn = !isDesignation
            ? `<button type="button" onclick="POS.toggleAssignStaff()" class="mt-1.5 px-2 py-1 text-[10px] font-medium text-emerald-600 hover:bg-emerald-100 dark:hover:bg-emerald-900/20 rounded border border-emerald-300 dark:border-emerald-700 transition">변경</button>`
            : '';

        el.innerHTML = `
            <div class="flex flex-col items-center text-center">
                <div class="w-16 h-16 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center border-2 ${borderColor} mb-1.5">
                    ${avatarHtml}
                </div>
                <span class="text-sm font-semibold text-zinc-900 dark:text-white">${this.escHtml(s.staff_name)}</span>
                <div class="mt-1">${typeBadge}</div>
                ${isDesignation ? `<span class="text-[10px] text-violet-500 mt-0.5">${this.fmtCurrency(customer.designation_fee)}</span>` : ''}
                ${changeBtn}
            </div>`;
        el.classList.remove('hidden');
        console.log('[POS] Staff header rendered:', s.staff_name, isDesignation ? 'designation' : 'assignment');
    },

    renderCustomerProfile(c, memos) {
        const genderLabel = { male: '남', female: '여', other: '기타' };

        // 프로필 이미지 업데이트 (w-20 h-20)
        if (c.profile_image) {
            document.getElementById('posProfileImg').innerHTML = `<img src="${this.escHtml(this._resolveImgUrl(c.profile_image))}" class="w-20 h-20 rounded-full object-cover">`;
        }

        // 뱃지: 회원/비회원, 등급
        let badges = '';
        if (c.is_member) {
            badges += `<span class="px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">회원</span>`;
            if (c.grade_name) {
                badges += `<span class="px-1.5 py-0.5 rounded-full text-[10px] font-semibold text-white" style="background:${c.grade_color || '#6B7280'}">${this.escHtml(c.grade_name)}</span>`;
            }
        } else {
            badges += `<span class="px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400">비회원</span>`;
        }
        document.getElementById('posCustBadges').innerHTML = badges;

        // 통계: 방문횟수
        let stats = '';
        if (c.is_member) {
            stats = `방문 <b class="text-zinc-700 dark:text-zinc-300">${c.visit_completed}</b>회`;
            if (c.visit_no_show > 0) stats += ` · <span class="text-red-400">노쇼 ${c.visit_no_show}</span>`;
        }
        document.getElementById('posCustStats').innerHTML = stats;

        // 오른쪽 패널: 고객 상세 정보
        let detailHtml = '';

        // 고객 정보 테이블
        if (c.is_member) {
            let age = '';
            if (c.birth_date) age = (new Date().getFullYear() - parseInt(c.birth_date.substring(0, 4))) + '세';
            detailHtml += `<div>
                <p class="text-[10px] font-semibold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1.5">고객 정보</p>
                <div class="space-y-1 text-xs">
                    ${age ? `<div class="flex justify-between"><span class="text-zinc-500">나이</span><span class="text-zinc-900 dark:text-white">${age}</span></div>` : ''}
                    ${c.gender ? `<div class="flex justify-between"><span class="text-zinc-500">성별</span><span class="text-zinc-900 dark:text-white">${genderLabel[c.gender] || ''}</span></div>` : ''}
                    ${c.discount_rate > 0 ? `<div class="flex justify-between"><span class="text-zinc-500">할인율</span><span class="text-red-500">${c.discount_rate}%</span></div>` : ''}
                    ${c.points_balance > 0 ? `<div class="flex justify-between"><span class="text-zinc-500"><?= get_points_name() ?></span><span class="text-emerald-600">${this.fmtCurrency(c.points_balance)}</span></div>` : ''}
                    ${c.member_since ? `<div class="flex justify-between"><span class="text-zinc-500">가입</span><span class="text-zinc-700 dark:text-zinc-300">${c.member_since.substring(0,10)}</span></div>` : ''}
                </div>
            </div>`;
        }

        // 고객 요구사항
        if (c.notes) {
            detailHtml += `<div class="p-2.5 bg-amber-50 dark:bg-amber-900/10 rounded-lg border border-amber-200 dark:border-amber-800/30">
                <p class="text-[10px] font-semibold text-amber-600 dark:text-amber-400 mb-0.5 flex items-center gap-1">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                    고객 요구사항</p>
                <p class="text-xs text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap">${this.escHtml(c.notes)}</p>
            </div>`;
        }

        // 관리자 메모 (admin_notes)
        if (c.admin_notes) {
            detailHtml += `<div class="p-2.5 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700">
                <p class="text-[10px] font-semibold text-zinc-500 dark:text-zinc-400 mb-0.5">관리자 메모 (예약건)</p>
                <p class="text-xs text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap">${this.escHtml(c.admin_notes)}</p>
            </div>`;
        }

        document.getElementById('posCustomerDetail').innerHTML = detailHtml;

        // 메모 영역 (회원만)
        if (c.is_member) {
            document.getElementById('posMemoArea').classList.remove('hidden');
            const memoHtml = memos.length > 0
                ? memos.map(m => `<div class="border-l-2 border-zinc-300 dark:border-zinc-600 pl-2 py-0.5">
                    <p class="text-xs text-zinc-700 dark:text-zinc-300">${this.escHtml(m.content)}</p>
                    <p class="text-[10px] text-zinc-400">${(m.created_at || '').substring(0,16)} · ${this.escHtml(m.admin_name || '')}</p>
                </div>`).join('')
                : '<p class="text-xs text-zinc-400 text-center">메모 없음</p>';
            document.getElementById('posMemoList').innerHTML = memoHtml;
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

        let totalAmount = 0, totalDuration = 0;
        const seenReservations = {};
        let totalPaid = 0;

        const html = services.map(s => {
            const price = parseFloat(s.price || 0);
            const dur = parseInt(s.service_duration || 0);
            totalAmount += price;
            totalDuration += dur;

            if (!seenReservations[s.reservation_id]) {
                seenReservations[s.reservation_id] = true;
                totalPaid += parseFloat(s.reservation_paid || 0);
            }

            const badge = statusCls[s.status] || 'bg-zinc-100 text-zinc-700';
            const label = statusLabel[s.status] || s.status;
            const startT = (s.start_time || '').substring(0, 5);
            const endT = (s.end_time || '').substring(0, 5);

            return `<div class="flex items-center justify-between p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1">
                        <span class="text-sm font-semibold text-zinc-900 dark:text-white truncate">${this.escHtml(s.service_name || '-')}</span>
                        <span class="px-1.5 py-0.5 rounded text-[10px] font-medium flex-shrink-0 ${badge}">${label}</span>
                    </div>
                    <div class="text-xs text-zinc-500">${startT}${endT ? ' ~ ' + endT : ''} · ${dur}<?= __('reservations.pos_min') ?>${s.staff_name ? ' · <span class="text-violet-600 dark:text-violet-400">' + this.escHtml(s.staff_name) + '</span>' : ''}</div>
                </div>
                <span class="text-sm font-bold text-zinc-900 dark:text-white flex-shrink-0 ml-2">${this.fmtCurrency(price)}</span>
            </div>`;
        }).join('');

        document.getElementById('posServiceList').innerHTML = html || '<p class="text-center text-zinc-400 text-sm py-4"><?= __('reservations.pos_no_services') ?></p>';

        const remaining = totalAmount - totalPaid;
        document.getElementById('posServiceTotal').innerHTML = `
            <div class="flex items-center justify-between py-2 text-sm">
                <span class="text-zinc-500"><?= __('reservations.pos_pay_total') ?> (${services.length}<?= __('reservations.pos_service_count') ?>) · ${totalDuration}<?= __('reservations.pos_min') ?></span>
                <span class="font-bold text-zinc-900 dark:text-white">${this.fmtCurrency(totalAmount)}</span>
            </div>
            ${totalPaid > 0 ? `<div class="flex items-center justify-between pb-2 text-sm">
                <span class="text-zinc-500"><?= __('reservations.pos_pay_paid') ?></span>
                <span class="text-emerald-600">${this.fmtCurrency(totalPaid)}</span>
            </div>` : ''}
            ${remaining > 0 ? `<div class="flex items-center justify-between pb-2 text-sm">
                <span class="font-medium text-zinc-700 dark:text-zinc-300"><?= __('reservations.pos_pay_remaining') ?></span>
                <span class="font-bold text-violet-600">${this.fmtCurrency(remaining)}</span>
            </div>` : ''}`;

        this._existingServiceIds = services.map(s => String(s.service_id));
        this._currentStaffId = services.length > 0 ? String(services[0].staff_id || '') : '';
    },

    _existingServiceIds: [],
    _currentStaffId: '',

    toggleAddService() {
        const area = document.getElementById('posAddServiceArea');
        const isHidden = area.classList.contains('hidden');
        if (isHidden) {
            area.classList.remove('hidden');
            document.getElementById('posAssignStaffArea').classList.add('hidden');
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
        if (c.user_id) body.append('user_id', c.user_id);
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

    // ─── 스태프 배정 ───
    toggleAssignStaff() {
        const area = document.getElementById('posAssignStaffArea');
        const isHidden = area.classList.contains('hidden');
        if (isHidden) {
            area.classList.remove('hidden');
            document.getElementById('posAddServiceArea').classList.add('hidden');
            const currentId = this._currentStaffId;
            const html = posAllStaff.map(s => {
                const isCurrent = String(s.id) === currentId;
                const fee = parseFloat(s.designation_fee || 0);
                return `<label class="flex items-center p-2.5 rounded-lg border ${isCurrent ? 'border-violet-400 bg-violet-50 dark:bg-violet-900/20' : 'border-zinc-200 dark:border-zinc-700'} cursor-pointer hover:bg-violet-50 dark:hover:bg-violet-900/10">
                    <input type="radio" name="pos_assign_staff" value="${s.id}" class="pos-staff-radio mr-3 text-violet-600" ${isCurrent ? 'checked' : ''} onchange="POS.onStaffRadioChange()">
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-zinc-900 dark:text-white">${this.escHtml(s.name)}${isCurrent ? ' <span class="text-xs text-violet-500">(<?= __('reservations.pos_current_staff') ?>)</span>' : ''}</p>
                        ${fee > 0 ? '<p class="text-xs text-zinc-500"><?= __('reservations.pos_designation_fee') ?> ' + this.fmtCurrency(fee) + '</p>' : ''}
                    </div>
                </label>`;
            }).join('');
            document.getElementById('posAssignStaffList').innerHTML = html || '<p class="text-center text-zinc-400 text-sm py-4"><?= __('reservations.pos_no_staff') ?></p>';
            document.getElementById('posAssignStaffBtn').disabled = !currentId;
            console.log('[POS] Toggle assign staff, current:', currentId);
        } else {
            area.classList.add('hidden');
        }
    },

    onStaffRadioChange() {
        const selected = document.querySelector('.pos-staff-radio:checked');
        document.getElementById('posAssignStaffBtn').disabled = !selected;
        console.log('[POS] Staff selected:', selected?.value);
    },

    async submitAssignStaff() {
        const selected = document.querySelector('.pos-staff-radio:checked');
        if (!selected) return;
        const btn = document.getElementById('posAssignStaffBtn');
        btn.disabled = true;
        btn.textContent = '<?= __('admin.messages.processing') ?>';

        const c = this._svcCustomer;
        const body = new URLSearchParams();
        body.append('_token', this.csrfToken);
        (c.reservation_ids || []).forEach(id => body.append('reservation_ids[]', id));
        body.append('staff_id', selected.value);

        console.log('[POS] Assigning staff:', selected.value, 'to reservations:', c.reservation_ids);
        try {
            const resp = await fetch(`${this.adminUrl}/reservations/assign-staff`, { method: 'POST', body });
            const data = await resp.json();
            console.log('[POS] Assign staff result:', data);
            if (data.success) {
                console.log('[POS] Staff assigned, updating header directly...');
                document.getElementById('posAssignStaffArea').classList.add('hidden');

                // posAllStaff에서 선택된 스태프 찾아서 직접 헤더 갱신
                const newStaffId = selected.value;
                const staffInfo = posAllStaff.find(s => String(s.id) === String(newStaffId));
                if (staffInfo) {
                    // renderStaffHeader에 필요한 형식으로 가짜 services 배열 생성
                    const fakeServices = [{ staff_id: staffInfo.id, staff_name: staffInfo.name, staff_avatar: staffInfo.avatar || staffInfo.profile_image || '' }];
                    this.renderStaffHeader(fakeServices, { designation_fee: 0 });
                    console.log('[POS] Header updated to:', staffInfo.name);
                }
                this._currentStaffId = newStaffId;

                btn.textContent = '<?= __('reservations.pos_assign_staff_submit') ?>';
                btn.disabled = false;
            } else {
                alert(data.message || '스태프 배정 실패');
                btn.disabled = false;
                btn.textContent = '<?= __('reservations.pos_assign_staff_submit') ?>';
            }
        } catch (err) {
            console.error('[POS] Assign staff error:', err);
            alert('오류가 발생했습니다.');
            btn.disabled = false;
            btn.textContent = '<?= __('reservations.pos_assign_staff_submit') ?>';
        }
    },

    // ─── 메모 저장 (POS 모달) ───
    async submitMemo() {
        const input = document.getElementById('posMemoInput');
        const content = (input.value || '').trim();
        if (!content) return;
        const c = this._svcCustomer;
        if (!c.user_id) { alert('비회원은 메모를 저장할 수 없습니다.'); return; }

        const rIds = c.reservation_ids || [];
        const body = new URLSearchParams();
        body.append('_token', this.csrfToken);
        body.append('user_id', c.user_id);
        body.append('content', content);
        if (rIds.length > 0) body.append('reservation_id', rIds[0]);

        console.log('[POS] Saving memo for user:', c.user_id);
        try {
            const resp = await fetch(`${this.adminUrl}/reservations/save-memo`, { method: 'POST', body });
            const data = await resp.json();
            console.log('[POS] Save memo result:', data);
            if (data.success) {
                input.value = '';
                const list = document.getElementById('posMemoList');
                const newHtml = `<div class="border-l-2 border-blue-400 pl-2 py-0.5">
                    <p class="text-xs text-zinc-700 dark:text-zinc-300">${this.escHtml(content)}</p>
                    <p class="text-[10px] text-zinc-400">방금 · ${this.escHtml(data.admin_name || 'Admin')}</p>
                </div>`;
                list.innerHTML = newHtml + list.innerHTML.replace('메모 없음', '');
            } else {
                alert(data.message || '메모 저장 실패');
            }
        } catch (err) {
            console.error('[POS] Save memo error:', err);
        }
    },

    closeServiceModal(e) {
        if (e && e.target !== e.currentTarget) return;
        document.getElementById('posServiceModal').classList.add('hidden');
    },
});
</script>
