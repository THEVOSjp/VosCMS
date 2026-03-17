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

async function saveMemo(e) {
    e.preventDefault();
    const content = document.getElementById('memoContent').value.trim();
    if (!content) return;
    const btn = document.getElementById('saveMemoBtn');
    btn.textContent = '저장 중...';
    btn.disabled = true;
    try {
        console.log('[Show] Saving admin memo');
        const resp = await fetch(`<?= $adminUrl ?>/reservations/save-memo`, {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: `_token=<?= $csrfToken ?>&user_id=<?= urlencode($r['user_id'] ?? '') ?>&reservation_id=<?= urlencode($id) ?>&reservation_number=<?= urlencode($r['reservation_number'] ?? '') ?>&content=${encodeURIComponent(content)}`
        });
        const data = await resp.json();
        console.log('[Show] Memo save result:', data);
        if (data.success) {
            btn.textContent = '저장됨 ✓';
            document.getElementById('memoContent').value = '';
            // 새 메모를 목록 맨 위에 추가
            const list = document.getElementById('memoList');
            const noMsg = document.getElementById('noMemoMsg');
            if (noMsg) noMsg.remove();
            const m = data.memo;
            const div = document.createElement('div');
            div.className = 'border-l-2 border-blue-400 pl-3 py-1';
            div.innerHTML = `<p class="text-sm text-zinc-800 dark:text-zinc-200 whitespace-pre-wrap">${escapeHtml(m.content)}</p>
                <div class="flex items-center gap-2 mt-1 text-xs text-zinc-400 dark:text-zinc-500">
                    <span>${m.created_at.substring(0,16).replace('T',' ')}</span>
                    <span>&middot;</span>
                    <span>${escapeHtml(m.admin_name)}</span>
                    ${m.reservation_number ? `<span>&middot;</span><span class="font-mono">${escapeHtml(m.reservation_number)}</span>` : ''}
                </div>`;
            list.insertBefore(div, list.firstChild);
            setTimeout(() => { btn.textContent = '저장'; btn.disabled = false; }, 1500);
        } else {
            alert(data.message || '저장 실패');
            btn.textContent = '저장';
            btn.disabled = false;
        }
    } catch (err) {
        console.error('[Show] Memo save error:', err);
        alert('저장 실패');
        btn.textContent = '저장';
        btn.disabled = false;
    }
}

function escapeHtml(str) {
    const d = document.createElement('div');
    d.textContent = str;
    return d.innerHTML;
}

// ─── 스태프 배정 변경 ───
const adminUrl = '<?= $adminUrl ?>';
const resId = '<?= $id ?>';
const csrfToken = '<?= $csrfToken ?>';
const resDate = '<?= $r['reservation_date'] ?? '' ?>';
const resStart = '<?= $r['start_time'] ?? '' ?>';
const resEnd = '<?= $r['end_time'] ?? '' ?>';
const currentStaffId = '<?= $r['staff_id'] ?? '' ?>';

async function openStaffChangePanel() {
    console.log('[Show] Opening staff change panel');
    const panel = document.getElementById('staffChangePanel');
    const list = document.getElementById('staffChangeList');
    if (!panel) return;
    panel.classList.remove('hidden');
    list.innerHTML = '<div class="text-center py-3 text-zinc-400 text-xs"><svg class="w-4 h-4 animate-spin mx-auto mb-1" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>';

    try {
        const url = adminUrl + '/reservations/available-staff?date=' + encodeURIComponent(resDate) + '&start_time=' + encodeURIComponent(resStart) + '&end_time=' + encodeURIComponent(resEnd);
        const resp = await fetch(url);
        const data = await resp.json();
        console.log('[Show] Available staff:', data);

        if (!data.success || !data.staff || !data.staff.length) {
            list.innerHTML = '<p class="text-center text-zinc-400 text-xs py-3">배정 가능한 스태프가 없습니다.</p>';
            return;
        }

        const appUrl = adminUrl.replace(/\/[^/]+$/, '');
        function resolveImg(path) {
            if (!path) return '';
            if (path.startsWith('http')) return path;
            return path.startsWith('/') ? appUrl + path : appUrl + '/storage/' + path;
        }

        list.innerHTML = data.staff.map(s => {
            const isCurrent = String(s.id) === String(currentStaffId);
            const busy = !s.available;
            const avatarUrl = resolveImg(s.avatar);
            return `<label class="flex items-center p-2 rounded-lg border transition cursor-pointer
                ${busy ? 'border-zinc-200 dark:border-zinc-700 opacity-40 cursor-not-allowed' : isCurrent ? 'border-emerald-400 bg-emerald-50 dark:bg-emerald-900/20' : 'border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-700/50'}">
                <input type="radio" name="changeStaff" value="${s.id}" class="mr-2.5 text-emerald-600" ${isCurrent ? 'checked' : ''} ${busy ? 'disabled' : ''} onchange="onStaffRadioChange()">
                <div class="w-7 h-7 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center flex-shrink-0 mr-2 overflow-hidden">
                    ${avatarUrl ? '<img src="' + escapeHtml(avatarUrl) + '" class="w-7 h-7 rounded-full object-cover">' : '<svg class="w-3.5 h-3.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>'}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">${escapeHtml(s.name)}${busy ? ' <span class="text-xs text-red-400">(예약있음)</span>' : ''}${isCurrent ? ' <span class="text-xs text-emerald-500">(현재)</span>' : ''}</p>
                </div>
            </label>`;
        }).join('');

        // 확인 버튼
        list.innerHTML += `<button type="button" id="staffChangeConfirmBtn" onclick="confirmStaffChange()" class="w-full py-2 bg-emerald-600 hover:bg-emerald-700 text-white rounded-lg text-sm font-bold transition mt-2 disabled:opacity-50" ${currentStaffId ? '' : 'disabled'}>배정 확인</button>`;
    } catch (err) {
        console.error('[Show] Staff fetch error:', err);
        list.innerHTML = '<p class="text-center text-red-400 text-xs py-3">스태프 조회 실패</p>';
    }
}

function closeStaffChangePanel() {
    const panel = document.getElementById('staffChangePanel');
    if (panel) panel.classList.add('hidden');
}

function onStaffRadioChange() {
    const btn = document.getElementById('staffChangeConfirmBtn');
    if (btn) btn.disabled = false;
    console.log('[Show] Staff radio changed');
}

async function confirmStaffChange() {
    const radio = document.querySelector('input[name="changeStaff"]:checked');
    if (!radio) return;
    const staffId = radio.value;
    console.log('[Show] Changing staff to:', staffId);

    const btn = document.getElementById('staffChangeConfirmBtn');
    btn.textContent = '변경 중...';
    btn.disabled = true;

    try {
        const resp = await fetch(adminUrl + '/reservations/assign-staff', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: '_token=' + csrfToken + '&staff_id=' + staffId + '&reservation_ids[]=' + resId
        });
        const data = await resp.json();
        console.log('[Show] Staff change result:', data);
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || '변경 실패');
            btn.textContent = '배정 확인';
            btn.disabled = false;
        }
    } catch (err) {
        console.error('[Show] Staff change error:', err);
        alert('변경 실패');
        btn.textContent = '배정 확인';
        btn.disabled = false;
    }
}

// ─── 서비스 삭제 (상세 페이지) ───
async function removeShowService(reservationId, serviceId, btnEl) {
    if (!confirm('<?= __('reservations.pos_remove_service_confirm') ?>')) return;
    console.log('[Show] Removing service:', reservationId, serviceId);

    try {
        const resp = await fetch(adminUrl + '/reservations/remove-service', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: '_token=' + csrfToken + '&reservation_id=' + encodeURIComponent(reservationId) + '&service_id=' + encodeURIComponent(serviceId)
        });
        const data = await resp.json();
        console.log('[Show] Remove result:', data);

        if (data.error) {
            alert(data.message || '삭제 실패');
            return;
        }

        if (data.remaining === 0) {
            // 예약 자체가 삭제됨 → 목록으로 이동
            alert('모든 서비스가 삭제되어 예약이 삭제되었습니다.');
            location.href = adminUrl + '/reservations';
            return;
        }

        // DOM에서 해당 서비스 행 제거
        const row = btnEl.closest('[data-svc-row]');
        if (row) row.remove();

        // 합계 재계산
        updateShowServiceSummary();

        // 우측 결제 정보도 새로고침 (간단히 reload 대신 DOM 업데이트)
        location.reload();
    } catch (err) {
        console.error('[Show] Remove service error:', err);
        alert('삭제 실패');
    }
}

function updateShowServiceSummary() {
    const rows = document.querySelectorAll('[data-svc-row]');
    let totalPrice = 0, totalDur = 0;
    rows.forEach(r => {
        totalPrice += parseFloat(r.dataset.svcPrice || 0);
        totalDur += parseInt(r.dataset.svcDuration || 0);
    });
    const summary = document.getElementById('showSvcSummary');
    if (summary) summary.textContent = rows.length + '건 · ' + totalDur + '분';
}

// ─── 서비스 추가 (상세 페이지) ───
let _showAddServiceLoaded = false;

function toggleShowAddService() {
    const area = document.getElementById('showAddServiceArea');
    const btn = document.getElementById('showAddServiceToggleBtn');
    if (!area) return;
    const isHidden = area.classList.contains('hidden');
    if (isHidden) {
        area.classList.remove('hidden');
        if (btn) btn.classList.add('hidden');
        if (!_showAddServiceLoaded) loadShowAddServiceList();
        // 추가 영역으로 스크롤
        area.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
    } else {
        area.classList.add('hidden');
        if (btn) btn.classList.remove('hidden');
    }
}

async function loadShowAddServiceList() {
    console.log('[Show] Loading service list for add');
    const list = document.getElementById('showAddServiceList');

    try {
        // 서비스 목록을 가져옴 (기존 customer-services가 아닌 서비스 카테고리 API)
        const resp = await fetch(adminUrl + '/services/list-json?_t=' + Date.now(), { cache: 'no-store' });
        const data = await resp.json();
        console.log('[Show] Services loaded:', data);

        if (!data.services || !data.services.length) {
            list.innerHTML = '<p class="text-center text-zinc-400 text-xs py-3">등록된 서비스가 없습니다.</p>';
            return;
        }

        // 현재 이미 추가된 서비스 ID 목록
        const existingIds = new Set();
        document.querySelectorAll('[data-svc-row]').forEach(r => existingIds.add(String(r.dataset.svcId)));

        list.innerHTML = data.services.map(s => {
            const exists = existingIds.has(String(s.id));
            return `<label class="flex items-center p-2 rounded-lg border transition cursor-pointer
                ${exists ? 'border-zinc-200 dark:border-zinc-700 opacity-40' : 'border-zinc-200 dark:border-zinc-700 hover:bg-blue-50 dark:hover:bg-blue-900/20'}">
                <input type="checkbox" name="showAddSvc" value="${s.id}" class="mr-2.5 text-blue-600 rounded" ${exists ? 'disabled' : ''} onchange="onShowAddSvcChange()">
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">${escapeHtml(s.name)}${exists ? ' <span class=&quot;text-xs text-zinc-400&quot;>(추가됨)</span>' : ''}</p>
                    <p class="text-xs text-zinc-500">${s.duration}분 · ${s.price_formatted || s.price}</p>
                </div>
            </label>`;
        }).join('');

        _showAddServiceLoaded = true;
    } catch (err) {
        console.error('[Show] Load services error:', err);
        list.innerHTML = '<p class="text-center text-red-400 text-xs py-3">서비스 목록 로드 실패</p>';
    }
}

function onShowAddSvcChange() {
    const checked = document.querySelectorAll('input[name="showAddSvc"]:checked');
    const btn = document.getElementById('showAddServiceBtn');
    if (btn) btn.disabled = checked.length === 0;
}

async function submitShowAddService() {
    const checked = document.querySelectorAll('input[name="showAddSvc"]:checked');
    if (checked.length === 0) return;

    const serviceIds = Array.from(checked).map(c => c.value);
    console.log('[Show] Adding services:', serviceIds);

    const btn = document.getElementById('showAddServiceBtn');
    btn.textContent = '추가 중...';
    btn.disabled = true;

    try {
        const body = new URLSearchParams();
        body.append('_token', csrfToken);
        body.append('reservation_id', resId);
        serviceIds.forEach(id => body.append('service_ids[]', id));

        const resp = await fetch(adminUrl + '/reservations/append-service', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: body
        });
        const data = await resp.json();
        console.log('[Show] Add service result:', data);

        if (data.error) {
            alert(data.message || '추가 실패');
            btn.textContent = '추가';
            btn.disabled = false;
            return;
        }

        // 성공 → 페이지 새로고침하여 전체 반영 (결제 정보 등 연동)
        location.reload();
    } catch (err) {
        console.error('[Show] Add service error:', err);
        alert('추가 실패');
        btn.textContent = '추가';
        btn.disabled = false;
    }
}
</script>
