<script>
console.log('[Reservations] Show page loaded, id=<?= $id ?>');

async function changeStatus(id, action) {
    const msgs = {
        confirm: '<?= __('reservations.confirm_msg') ?>',
        cancel: '<?= __('reservations.cancel_msg') ?>',
        complete: '<?= __('reservations.complete_msg') ?>',
        'no-show': '<?= __('reservations.noshow_msg') ?>'
    };
    const colors = { confirm: 'green', cancel: 'red', complete: 'blue', 'no-show': 'zinc' };
    const msg = msgs[action] || '<?= __('reservations.show_confirm_proceed') ?>';

    const result = await showConfirmModal(msg, colors[action] || 'blue', action === 'cancel');
    if (!result) return;

    let reason = '';
    if (action === 'cancel' && result.reason !== undefined) reason = result.reason;

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
            if (typeof showResultModal === 'function') showResultModal(false, data.message || '<?= __('reservations.show_process_fail') ?>');
            else alert(data.message || '<?= __('reservations.show_process_fail') ?>');
        } else {
            if (typeof showResultModal === 'function') {
                showResultModal(true, '');
                setTimeout(() => location.reload(), 1200);
            } else {
                location.reload();
            }
        }
    } catch (err) {
        console.error('[Show] Error:', err);
        location.reload();
    }
}

function showConfirmModal(message, color, showReason) {
    return new Promise(function(resolve) {
        var colorMap = {
            blue: 'bg-blue-600 hover:bg-blue-700', red: 'bg-red-600 hover:bg-red-700',
            green: 'bg-green-600 hover:bg-green-700', zinc: 'bg-zinc-600 hover:bg-zinc-700'
        };
        var iconMap = {
            blue: 'text-blue-500', red: 'text-red-500', green: 'text-green-500', zinc: 'text-zinc-500'
        };
        var btnClass = colorMap[color] || colorMap.blue;
        var iconClass = iconMap[color] || iconMap.blue;
        var reasonHtml = showReason
            ? '<div class="mt-3"><label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1"><?= __('reservations.show_cancel_reason_prompt') ?? '취소 사유' ?></label><textarea id="showConfirmReason" rows="2" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg" placeholder="<?= __('reservations.show_cancel_reason_default') ?? '관리자에 의한 취소' ?>"></textarea></div>'
            : '';

        var html = '<div id="showConfirmOverlay" class="fixed inset-0 bg-black/50 z-[60] flex items-center justify-center p-4">'
            + '<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-2xl w-full max-w-sm p-6">'
            + '<div class="text-center mb-4">'
            + '<svg class="w-12 h-12 mx-auto mb-3 ' + iconClass + '" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8.228 9c.549-1.165 2.03-2 3.772-2 2.21 0 4 1.343 4 3 0 1.4-1.278 2.575-3.006 2.907-.542.104-.994.54-.994 1.093m0 3h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'
            + '<p class="text-sm text-zinc-700 dark:text-zinc-300">' + message + '</p>'
            + '</div>'
            + reasonHtml
            + '<div class="flex gap-3 mt-4">'
            + '<button id="showConfirmNo" class="flex-1 px-4 py-2.5 text-sm font-medium text-zinc-600 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 rounded-lg hover:bg-zinc-200 dark:hover:bg-zinc-600 transition"><?= __('common.buttons.cancel') ?? '취소' ?></button>'
            + '<button id="showConfirmYes" class="flex-1 px-4 py-2.5 text-sm font-medium text-white ' + btnClass + ' rounded-lg transition"><?= __('common.buttons.confirm') ?? '확인' ?></button>'
            + '</div></div></div>';

        document.body.insertAdjacentHTML('beforeend', html);
        document.getElementById('showConfirmNo').onclick = function() { document.getElementById('showConfirmOverlay').remove(); resolve(false); };
        document.getElementById('showConfirmYes').onclick = function() {
            var r = document.getElementById('showConfirmReason');
            document.getElementById('showConfirmOverlay').remove();
            resolve(showReason ? { reason: r ? r.value : '' } : true);
        };
        document.getElementById('showConfirmOverlay').onclick = function(e) { if (e.target === e.currentTarget) { e.currentTarget.remove(); resolve(false); } };
        console.log('[Show] Confirm modal shown:', message);
    });
}

async function saveMemo(e) {
    e.preventDefault();
    const content = document.getElementById('memoContent').value.trim();
    if (!content) return;
    const btn = document.getElementById('saveMemoBtn');
    btn.textContent = '<?= __('reservations.show_memo_saving') ?>';
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
            btn.textContent = '<?= __('reservations.show_memo_saved') ?>';
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
            setTimeout(() => { btn.textContent = '<?= __('reservations.show_memo_save') ?>'; btn.disabled = false; }, 1500);
        } else {
            alert(data.message || '<?= __('reservations.show_memo_save_fail') ?>');
            btn.textContent = '<?= __('reservations.show_memo_save') ?>';
            btn.disabled = false;
        }
    } catch (err) {
        console.error('[Show] Memo save error:', err);
        alert('<?= __('reservations.show_memo_save_fail') ?>');
        btn.textContent = '<?= __('reservations.show_memo_save') ?>';
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
let staffChangeMode = 'assign'; // 'assign' or 'designate'

async function openStaffChangePanel(mode) {
    staffChangeMode = mode || 'assign';
    const isDesignate = staffChangeMode === 'designate';
    const modeLabel = isDesignate ? '<?= __('reservations.show_designation') ?>' : '<?= __('reservations.show_assignment') ?>';
    const modeColor = isDesignate ? 'violet' : 'emerald';

    console.log('[Show] Opening staff change panel, mode:', staffChangeMode);
    const panel = document.getElementById('staffChangePanel');
    const list = document.getElementById('staffChangeList');
    const panelTitle = document.getElementById('staffPanelTitle');
    if (!panel) return;
    panel.classList.remove('hidden');
    if (panelTitle) panelTitle.textContent = '<?= __('reservations.show_etc_staff') ?> ' + modeLabel;
    list.innerHTML = '<div class="text-center py-3 text-zinc-400 text-xs"><svg class="w-4 h-4 animate-spin mx-auto mb-1" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>';

    try {
        const url = adminUrl + '/reservations/available-staff?date=' + encodeURIComponent(resDate) + '&start_time=' + encodeURIComponent(resStart) + '&end_time=' + encodeURIComponent(resEnd);
        const resp = await fetch(url);
        const data = await resp.json();
        console.log('[Show] Available staff:', data);

        if (!data.success || !data.staff || !data.staff.length) {
            list.innerHTML = '<p class="text-center text-zinc-400 text-xs py-3">' + modeLabel + ' <?= __('reservations.show_staff_no_available', ['mode' => '']) ?></p>';
            return;
        }

        const appUrl = adminUrl.replace(/\/[^/]+$/, '');
        function resolveImg(path) {
            if (!path) return '';
            if (path.startsWith('http')) return path;
            return path.startsWith('/') ? appUrl + path : appUrl + '/storage/' + path;
        }

        // 미배정 옵션 (배정 모드에서만)
        let html = '';
        if (!isDesignate) {
            html += `<label class="flex items-center p-2 rounded-lg border transition cursor-pointer
                ${!currentStaffId ? 'border-zinc-400 bg-zinc-100 dark:bg-zinc-700/50' : 'border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-700/50'}">
                <input type="radio" name="changeStaff" value="" class="mr-2.5 text-zinc-500" ${!currentStaffId ? 'checked' : ''} onchange="onStaffRadioChange()">
                <div class="w-7 h-7 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center flex-shrink-0 mr-2">
                    <svg class="w-3.5 h-3.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M18.364 18.364A9 9 0 005.636 5.636m12.728 12.728A9 9 0 015.636 5.636m12.728 12.728L5.636 5.636"/></svg>
                </div>
                <p class="text-sm font-medium text-zinc-500 dark:text-zinc-400"><?= __('reservations.show_unassigned') ?>${!currentStaffId ? ' <span class="text-xs text-zinc-400"><?= __('reservations.show_staff_current') ?></span>' : ''}</p>
            </label>`;
        }

        const accentCls = isDesignate ? 'text-violet-600' : 'text-emerald-600';
        const activeBorder = isDesignate ? 'border-violet-400 bg-violet-50 dark:bg-violet-900/20' : 'border-emerald-400 bg-emerald-50 dark:bg-emerald-900/20';
        const currentLabel = isDesignate ? '<span class="text-xs text-violet-500"><?= __('reservations.show_staff_current') ?></span>' : '<span class="text-xs text-emerald-500"><?= __('reservations.show_staff_current') ?></span>';

        html += data.staff.map(s => {
            const isCurrent = String(s.id) === String(currentStaffId);
            const busy = !s.available;
            const avatarUrl = resolveImg(s.avatar);
            const feeHtml = isDesignate && s.designation_fee > 0 ? ' <span class="text-xs text-violet-500">+' + Number(s.designation_fee).toLocaleString() + '</span>' : '';
            return `<label class="flex items-center p-2 rounded-lg border transition cursor-pointer
                ${busy ? 'border-zinc-200 dark:border-zinc-700 opacity-40 cursor-not-allowed' : isCurrent ? activeBorder : 'border-zinc-200 dark:border-zinc-700 hover:bg-zinc-100 dark:hover:bg-zinc-700/50'}">
                <input type="radio" name="changeStaff" value="${s.id}" class="mr-2.5 ${accentCls}" ${isCurrent ? 'checked' : ''} ${busy ? 'disabled' : ''} onchange="onStaffRadioChange()">
                <div class="w-7 h-7 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center flex-shrink-0 mr-2 overflow-hidden">
                    ${avatarUrl ? '<img src="' + escapeHtml(avatarUrl) + '" class="w-7 h-7 rounded-full object-cover">' : '<svg class="w-3.5 h-3.5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>'}
                </div>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">${escapeHtml(s.name)}${feeHtml}${busy ? ' <span class="text-xs text-red-400"><?= __('reservations.show_staff_busy') ?></span>' : ''}${isCurrent ? ' ' + currentLabel : ''}</p>
                </div>
            </label>`;
        }).join('');

        list.innerHTML = html;
        const btnColor = isDesignate ? 'bg-violet-600 hover:bg-violet-700' : 'bg-emerald-600 hover:bg-emerald-700';
        list.innerHTML += `<button type="button" id="staffChangeConfirmBtn" onclick="confirmStaffChange()" class="w-full py-2 ${btnColor} text-white rounded-lg text-sm font-bold transition mt-2 disabled:opacity-50" ${currentStaffId ? '' : 'disabled'}>${modeLabel} <?= __('reservations.show_staff_confirm_btn', ['mode' => '']) ?></button>`;
    } catch (err) {
        console.error('[Show] Staff fetch error:', err);
        list.innerHTML = '<p class="text-center text-red-400 text-xs py-3"><?= __('reservations.show_staff_fetch_fail') ?></p>';
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
    const isDesignate = staffChangeMode === 'designate';
    console.log('[Show] Changing staff to:', staffId, 'mode:', staffChangeMode);

    const btn = document.getElementById('staffChangeConfirmBtn');
    btn.textContent = '<?= __('reservations.show_staff_changing') ?>';
    btn.disabled = true;

    try {
        let body = '_token=' + csrfToken + '&staff_id=' + staffId + '&reservation_ids[]=' + resId;
        if (isDesignate && staffId) {
            body += '&designation=1';
        }
        const resp = await fetch(adminUrl + '/reservations/assign-staff', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: body
        });
        const data = await resp.json();
        console.log('[Show] Staff change result:', data);
        if (data.success) {
            location.reload();
        } else {
            alert(data.message || '<?= __('reservations.show_staff_change_fail') ?>');
            btn.textContent = '<?= __('reservations.show_staff_confirm_btn', ['mode' => '']) ?>';
            btn.disabled = false;
        }
    } catch (err) {
        console.error('[Show] Staff change error:', err);
        alert('<?= __('reservations.show_staff_change_fail') ?>');
        btn.textContent = '<?= __('reservations.show_staff_confirm_btn', ['mode' => '']) ?>';
        btn.disabled = false;
    }
}

// ─── 번들 삭제 (포함 서비스 일괄 삭제) ───
async function removeBundle(reservationId) {
    var result = await showConfirmModal('<?= __('reservations.show_remove_bundle_confirm') ?? '번들과 포함된 서비스를 모두 삭제하시겠습니까?' ?>', 'red', false);
    if (!result) return;
    console.log('[Show] Removing bundle for reservation:', reservationId);
    try {
        var resp = await fetch(adminUrl + '/reservations/remove-bundle', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: '_token=' + csrfToken + '&reservation_id=' + encodeURIComponent(reservationId)
        });
        var data = await resp.json();
        if (data.error) {
            if (typeof showResultModal === 'function') showResultModal(false, data.message || 'Error');
            else alert(data.message || 'Error');
        } else {
            if (typeof showResultModal === 'function') { showResultModal(true, ''); setTimeout(function(){ location.reload(); }, 1200); }
            else location.reload();
        }
    } catch (err) {
        console.error('[Show] Remove bundle error:', err);
        alert('Error: ' + err.message);
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
            alert(data.message || '<?= __('reservations.show_delete_fail') ?>');
            return;
        }

        if (data.remaining === 0) {
            // 서비스 모두 삭제 → 페이지 새로고침 (서비스 추가 가능)
            location.reload();
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
        alert('<?= __('reservations.show_delete_fail') ?>');
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
            list.innerHTML = '<p class="text-center text-zinc-400 text-xs py-3"><?= __('reservations.show_add_service_none') ?></p>';
            return;
        }

        // 현재 이미 추가된 서비스 ID 목록
        const existingIds = new Set();
        document.querySelectorAll('[data-svc-row]').forEach(r => existingIds.add(String(r.dataset.svcId)));

        // 번들 서비스
        var bundleHtml = '';
        if (data.bundles && data.bundles.length) {
            bundleHtml = '<div class="mb-3"><p class="text-xs font-semibold text-amber-700 dark:text-amber-400 mb-2 flex items-center gap-1"><svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg><?= __('bundles.recommended') ?? '추천' ?> <?= htmlspecialchars($siteSettings['bundle_display_name'] ?? '') ?></p>'
                + '<div class="grid grid-cols-3 gap-2">'
                + data.bundles.map(b => {
                    var imgUrl = b.image ? (b.image.startsWith('http') ? b.image : adminUrl.replace(/\/[^/]+$/, '') + '/' + b.image) : '';
                    return '<label class="relative rounded-lg border-2 border-amber-200 dark:border-amber-700 hover:border-amber-400 transition cursor-pointer overflow-hidden bg-white dark:bg-zinc-800">'
                        + '<input type="checkbox" name="showAddBundle" value="' + b.id + '" data-services="' + escapeHtml((b.service_ids || []).join(',')) + '" class="absolute top-2 right-2 text-amber-600 rounded z-10" onchange="onShowAddBundleChange(this)">'
                        + (imgUrl ? '<div class="h-20 bg-zinc-100 dark:bg-zinc-700 overflow-hidden"><img src="' + escapeHtml(imgUrl) + '" class="w-full h-full object-cover"></div>' : '<div class="h-20 bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center"><svg class="w-8 h-8 text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg></div>')
                        + '<div class="p-2"><p class="text-xs font-semibold text-zinc-900 dark:text-white truncate">' + escapeHtml(b.name) + '</p>'
                        + '<p class="text-xs text-amber-600 font-bold">' + (b.price_formatted || b.bundle_price) + '</p>'
                        + '<p class="text-[10px] text-zinc-400">' + (b.service_count || 0) + '<?= __('booking.service_count') ?? '개 서비스' ?></p>'
                        + '</div></label>';
                }).join('')
                + '</div></div>';
        }

        // 개별 서비스 카드
        var svcHtml = '<div class="grid grid-cols-4 gap-2">'
            + data.services.map(s => {
                var exists = existingIds.has(String(s.id));
                var imgUrl = s.image ? (s.image.startsWith('http') ? s.image : adminUrl.replace(/\/[^/]+$/, '') + '/' + s.image) : '';
                return '<label class="relative rounded-lg border transition cursor-pointer overflow-hidden '
                    + (exists ? 'border-zinc-200 dark:border-zinc-700 opacity-40' : 'border-zinc-200 dark:border-zinc-700 hover:border-blue-400') + ' bg-white dark:bg-zinc-800">'
                    + '<input type="checkbox" name="showAddSvc" value="' + s.id + '" class="absolute top-2 right-2 text-blue-600 rounded z-10" ' + (exists ? 'disabled' : '') + ' onchange="onShowAddSvcChange()">'
                    + (imgUrl ? '<div class="h-16 bg-zinc-100 dark:bg-zinc-700 overflow-hidden"><img src="' + escapeHtml(imgUrl) + '" class="w-full h-full object-cover"></div>' : '<div class="h-16 bg-zinc-50 dark:bg-zinc-700 flex items-center justify-center"><svg class="w-6 h-6 text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></div>')
                    + '<div class="p-2"><p class="text-xs font-semibold text-zinc-900 dark:text-white truncate">' + escapeHtml(s.name) + (exists ? ' <span class="text-[10px] text-zinc-400"><?= __('reservations.show_add_service_added') ?? '추가됨' ?></span>' : '') + '</p>'
                    + '<p class="text-[10px] text-zinc-500">' + s.duration + '분 · ' + (s.price_formatted || s.price) + '</p>'
                    + '</div></label>';
            }).join('')
            + '</div>';

        list.innerHTML = bundleHtml + svcHtml;

        _showAddServiceLoaded = true;
    } catch (err) {
        console.error('[Show] Load services error:', err);
        list.innerHTML = '<p class="text-center text-red-400 text-xs py-3"><?= __('reservations.show_add_service_load_fail') ?></p>';
    }
}

function onShowAddSvcChange() {
    var checked = document.querySelectorAll('input[name="showAddSvc"]:checked');
    var btn = document.getElementById('showAddServiceBtn');
    if (btn) btn.disabled = checked.length === 0;
}

function onShowAddBundleChange(cb) {
    var svcIds = (cb.dataset.services || '').split(',').filter(Boolean);
    console.log('[Show] Bundle toggled, services:', svcIds, 'checked:', cb.checked);
    // 번들 포함 서비스 자동 체크/해제
    svcIds.forEach(function(id) {
        var svcCb = document.querySelector('input[name="showAddSvc"][value="' + id + '"]');
        if (svcCb && !svcCb.disabled) svcCb.checked = cb.checked;
    });
    onShowAddSvcChange();
}

async function submitShowAddService() {
    const checked = document.querySelectorAll('input[name="showAddSvc"]:checked');
    if (checked.length === 0) return;

    const serviceIds = Array.from(checked).map(c => c.value);
    console.log('[Show] Adding services:', serviceIds);

    const btn = document.getElementById('showAddServiceBtn');
    btn.textContent = '<?= __('reservations.show_add_service_adding') ?>';
    btn.disabled = true;

    // 선택된 번들 ID
    var bundleCb = document.querySelector('input[name="showAddBundle"]:checked');
    var bundleId = bundleCb ? bundleCb.value : '';

    try {
        const body = new URLSearchParams();
        body.append('_token', csrfToken);
        body.append('reservation_id', resId);
        if (bundleId) body.append('bundle_id', bundleId);
        serviceIds.forEach(id => body.append('service_ids[]', id));

        const resp = await fetch(adminUrl + '/reservations/append-service', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest' },
            body: body
        });
        const data = await resp.json();
        console.log('[Show] Add service result:', data);

        if (data.error) {
            alert(data.message || '<?= __('reservations.show_add_service_fail') ?>');
            btn.textContent = '<?= __('reservations.show_add_service_btn') ?>';
            btn.disabled = false;
            return;
        }

        // 성공 → 페이지 새로고침하여 전체 반영 (결제 정보 등 연동)
        location.reload();
    } catch (err) {
        console.error('[Show] Add service error:', err);
        alert('<?= __('reservations.show_add_service_fail') ?>');
        btn.textContent = '<?= __('reservations.show_add_service_btn') ?>';
        btn.disabled = false;
    }
}

// ─── 일시 수정 모달 ───
function openDateTimeEditModal() {
    var html = '<div id="dtEditOverlay" class="fixed inset-0 bg-black/50 z-[60] flex items-center justify-center p-4">'
        + '<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-2xl w-full max-w-sm p-6">'
        + '<h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('reservations.show_edit_datetime') ?? '일시 수정' ?></h3>'
        + '<div class="space-y-3">'
        + '<div><label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1"><?= __('booking.date_label') ?? '날짜' ?></label>'
        + '<input type="date" id="dtEditDate" value="' + resDate + '" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg text-sm"></div>'
        + '<div><label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1"><?= __('booking.time_label') ?? '시간' ?></label>'
        + '<input type="time" id="dtEditTime" value="' + resStart.substring(0,5) + '" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg text-sm"></div>'
        + '</div>'
        + '<div class="flex gap-3 mt-5">'
        + '<button onclick="document.getElementById(\'dtEditOverlay\').remove()" class="flex-1 px-4 py-2.5 text-sm font-medium text-zinc-600 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 rounded-lg hover:bg-zinc-200 dark:hover:bg-zinc-600 transition"><?= __('common.buttons.cancel') ?? '취소' ?></button>'
        + '<button onclick="submitDateTimeEdit()" class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition"><?= __('common.buttons.save') ?? '저장' ?></button>'
        + '</div></div></div>';
    document.body.insertAdjacentHTML('beforeend', html);
    console.log('[Show] DateTime edit modal opened');
}

async function submitDateTimeEdit() {
    var date = document.getElementById('dtEditDate').value;
    var time = document.getElementById('dtEditTime').value;
    if (!date || !time) return;
    console.log('[Show] Updating datetime:', date, time);
    try {
        var resp = await fetch(adminUrl + '/reservations/' + resId + '/update-datetime', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: '_token=' + csrfToken + '&date=' + encodeURIComponent(date) + '&time=' + encodeURIComponent(time)
        });
        var data = await resp.json();
        document.getElementById('dtEditOverlay').remove();
        if (data.success) {
            if (typeof showResultModal === 'function') { showResultModal(true, ''); setTimeout(function(){ location.reload(); }, 1200); }
            else location.reload();
        } else {
            if (typeof showResultModal === 'function') showResultModal(false, data.message || 'Error');
            else alert(data.message || 'Error');
        }
    } catch (err) {
        document.getElementById('dtEditOverlay').remove();
        console.error('[Show] DateTime update error:', err);
        alert('Error: ' + err.message);
    }
}

// ─── 연락처 수정 모달 ───
function openCustomerEditModal() {
    var phone = '<?= addslashes($r['customer_phone'] ?? '') ?>';
    var email = '<?= addslashes($r['customer_email'] ?? '') ?>';
    var html = '<div id="custEditOverlay" class="fixed inset-0 bg-black/50 z-[60] flex items-center justify-center p-4">'
        + '<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-2xl w-full max-w-sm p-6">'
        + '<h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('reservations.show_edit_contact') ?? '연락처 수정' ?></h3>'
        + '<div class="space-y-3">'
        + '<div><label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1"><?= __('reservations.show_customer_phone') ?? '전화번호' ?></label>'
        + '<input type="tel" id="custEditPhone" value="' + phone + '" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg text-sm font-mono"></div>'
        + '<div><label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1"><?= __('reservations.show_customer_email') ?? '이메일' ?></label>'
        + '<input type="email" id="custEditEmail" value="' + email + '" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg text-sm"></div>'
        + '</div>'
        + '<div class="flex gap-3 mt-5">'
        + '<button onclick="document.getElementById(\'custEditOverlay\').remove()" class="flex-1 px-4 py-2.5 text-sm font-medium text-zinc-600 dark:text-zinc-300 bg-zinc-100 dark:bg-zinc-700 rounded-lg hover:bg-zinc-200 dark:hover:bg-zinc-600 transition"><?= __('common.buttons.cancel') ?? '취소' ?></button>'
        + '<button onclick="submitCustomerEdit()" class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition"><?= __('common.buttons.save') ?? '저장' ?></button>'
        + '</div></div></div>';
    document.body.insertAdjacentHTML('beforeend', html);
    console.log('[Show] Customer edit modal opened');
}

async function submitCustomerEdit() {
    var phone = document.getElementById('custEditPhone').value.trim();
    var email = document.getElementById('custEditEmail').value.trim();
    if (!phone) return;
    console.log('[Show] Updating customer contact:', phone, email);
    try {
        var resp = await fetch(adminUrl + '/reservations/' + resId + '/update-contact', {
            method: 'POST',
            headers: { 'Content-Type': 'application/x-www-form-urlencoded', 'X-Requested-With': 'XMLHttpRequest' },
            body: '_token=' + csrfToken + '&phone=' + encodeURIComponent(phone) + '&email=' + encodeURIComponent(email)
        });
        var data = await resp.json();
        document.getElementById('custEditOverlay').remove();
        if (data.success) {
            if (typeof showResultModal === 'function') { showResultModal(true, ''); setTimeout(function(){ location.reload(); }, 1200); }
            else location.reload();
        } else {
            if (typeof showResultModal === 'function') showResultModal(false, data.message || 'Error');
            else alert(data.message || 'Error');
        }
    } catch (err) {
        document.getElementById('custEditOverlay').remove();
        console.error('[Show] Contact update error:', err);
        alert('Error: ' + err.message);
    }
}
</script>
