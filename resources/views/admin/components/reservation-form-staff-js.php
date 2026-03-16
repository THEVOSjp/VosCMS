<script>
/**
 * 스태프 지명/배정 JS (reservation-form-js.php에서 분리)
 * ResFormStaff 전역 객체 - 예약 폼에서 스태프 선택 기능
 */
window.ResFormStaff = {
    _mode: {},   // formId → 'designation' | 'assignment'

    _resolveImg(path) {
        if (!path) return '';
        if (path.startsWith('http')) return path;
        const base = location.pathname.replace(/\/[^/]+\/reservations.*$/, '');
        return location.origin + base + (path.startsWith('/') ? path : '/storage/' + path);
    },

    async open(fId, mode) {
        console.log('[ResFormStaff] Open:', fId, mode);
        this._mode[fId] = mode;
        const listEl = document.getElementById(fId + '_staffList');

        // 폼에서 날짜/시간 가져오기
        const dateVal = document.getElementById(fId + '_date')?.value || '';
        const startVal = document.getElementById(fId + '_startTime')?.value || '';
        const endVal = document.getElementById(fId + '_endTime')?.value || '';

        if (!dateVal || !startVal) {
            alert('날짜와 시작 시간을 먼저 선택해주세요.');
            return;
        }

        // 로딩 표시
        listEl.innerHTML = '<div class="text-center py-4 text-zinc-400"><svg class="w-5 h-5 animate-spin mx-auto mb-1" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>';
        listEl.classList.remove('hidden');

        try {
            const adminUrl = '<?= $resForm['adminUrl'] ?>';
            const url = adminUrl + '/reservations/available-staff?date=' + encodeURIComponent(dateVal) + '&start_time=' + encodeURIComponent(startVal) + '&end_time=' + encodeURIComponent(endVal);
            const resp = await fetch(url);
            const data = await resp.json();
            console.log('[ResFormStaff] Available staff:', data);

            if (!data.success || !data.staff || !data.staff.length) {
                listEl.innerHTML = '<p class="text-center text-zinc-400 text-sm py-3"><?= __('reservations.pos_no_staff') ?></p>';
                return;
            }

            const staffData = data.staff;
            const isDesignation = mode === 'designation';
            const currentId = document.getElementById(fId + '_staffId').value;
            const sym = '<?= $resForm['currencySymbol'] ?>';
            const pos = '<?= $resForm['currencyPosition'] ?>';
            function fmtC(amt) {
                const f = Number(amt).toLocaleString();
                return pos === 'suffix' ? f + sym : sym + f;
            }

            listEl.innerHTML = staffData.map(s => {
                const fee = parseFloat(s.designation_fee || 0);
                const isCurrent = String(s.id) === String(currentId);
                const avatarUrl = s.avatar ? ResFormStaff._resolveImg(s.avatar) : '';
                const esc = str => { const d = document.createElement('div'); d.textContent = str || ''; return d.innerHTML; };
                const busy = !s.available;

                return `<label class="flex items-center p-2.5 rounded-lg border transition
                    ${busy ? 'border-zinc-200 dark:border-zinc-700 opacity-40 cursor-not-allowed' : isCurrent ? 'border-violet-400 bg-violet-50 dark:bg-violet-900/20 cursor-pointer' : 'border-zinc-200 dark:border-zinc-700 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 cursor-pointer'}">
                    <input type="radio" name="rf_staff_${fId}" value="${s.id}" data-name="${esc(s.name)}" data-fee="${fee}" data-avatar="${esc(avatarUrl)}"
                           class="rf-staff-radio mr-3 text-violet-600" ${isCurrent ? 'checked' : ''} ${busy ? 'disabled' : ''}
                           onchange="ResFormStaff.onSelect('${fId}')">
                    <div class="w-8 h-8 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center flex-shrink-0 mr-2 overflow-hidden">
                        ${avatarUrl ? '<img src="' + esc(avatarUrl) + '" class="w-8 h-8 rounded-full object-cover">' : '<svg class="w-4 h-4 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>'}
                    </div>
                    <div class="flex-1 min-w-0">
                        <p class="text-sm font-medium text-zinc-900 dark:text-white">${esc(s.name)}${busy ? ' <span class="text-xs text-red-400">(<?= __('reservations.pos_staff_busy') ?? '예약있음' ?>)</span>' : ''}</p>
                        ${isDesignation && fee > 0 ? '<p class="text-xs text-violet-500"><?= __('reservations.pos_designation_fee') ?> ' + fmtC(fee) + '</p>' : ''}
                    </div>
                </label>`;
            }).join('');

            // 선택 버튼
            listEl.innerHTML += `<button type="button" onclick="ResFormStaff.confirm('${fId}')" id="${fId}_staffConfirmBtn"
                class="w-full py-2.5 bg-violet-600 hover:bg-violet-700 text-white rounded-lg text-sm font-bold transition mt-1 disabled:opacity-50"
                ${currentId ? '' : 'disabled'}>
                ${isDesignation ? '<?= __('reservations.pos_designation') ?>' : '<?= __('reservations.pos_assignment') ?>'} 확인
            </button>`;
        } catch (err) {
            console.error('[ResFormStaff] Fetch error:', err);
            listEl.innerHTML = '<p class="text-center text-red-400 text-sm py-3">스태프 조회 실패</p>';
        }
    },

    onSelect(fId) {
        const btn = document.getElementById(fId + '_staffConfirmBtn');
        if (btn) btn.disabled = false;
        console.log('[ResFormStaff] Staff selected for:', fId);
    },

    confirm(fId) {
        const radio = document.querySelector(`input[name="rf_staff_${fId}"]:checked`);
        if (!radio) return;

        const mode = this._mode[fId] || 'assignment';
        const staffId = radio.value;
        const staffName = radio.dataset.name || '';
        const staffFee = mode === 'designation' ? parseFloat(radio.dataset.fee || 0) : 0;
        const staffAvatar = radio.dataset.avatar || '';

        document.getElementById(fId + '_staffId').value = staffId;
        document.getElementById(fId + '_designationFee').value = staffFee;

        const selectedEl = document.getElementById(fId + '_staffSelected');
        const avatarEl = document.getElementById(fId + '_staffAvatar');
        const nameEl = document.getElementById(fId + '_staffName');
        const typeEl = document.getElementById(fId + '_staffType');

        if (staffAvatar) {
            avatarEl.innerHTML = `<img src="${staffAvatar}" class="w-10 h-10 rounded-full object-cover">`;
        }
        nameEl.textContent = staffName;
        typeEl.textContent = mode === 'designation'
            ? '<?= __('reservations.pos_designation') ?>' + (staffFee > 0 ? ' · ' + staffFee.toLocaleString() : '')
            : '<?= __('reservations.pos_assignment') ?>';

        selectedEl.classList.remove('hidden');
        document.getElementById(fId + '_staffList').classList.add('hidden');
        console.log('[ResFormStaff] Confirmed:', staffName, mode, 'fee:', staffFee);
    },

    clear(fId) {
        document.getElementById(fId + '_staffId').value = '';
        document.getElementById(fId + '_designationFee').value = '0';
        document.getElementById(fId + '_staffSelected').classList.add('hidden');
        document.getElementById(fId + '_staffList').classList.add('hidden');
        document.getElementById(fId + '_staffAvatar').innerHTML = '<svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>';
        console.log('[ResFormStaff] Cleared:', fId);
    }
};
</script>
