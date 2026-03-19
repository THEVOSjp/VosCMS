<script>
const PT_URL = window.location.href.split('?')[0];
const PT_TAB = '<?= $currentTab ?>';
const MAX_LEVEL = <?= (int)($settings['point_max_level'] ?? 30) ?>;
const GROUPS_JSON = <?= json_encode($groups ?? []) ?>;

document.addEventListener('DOMContentLoaded', () => {
    console.log('[Points] init tab:', PT_TAB);
    if (PT_TAB === 'members') loadMembers(1);

    // 최고 레벨 변경 시 레벨 테이블 동적 조정
    const maxLevelInput = document.getElementById('cfgMaxLevel');
    if (maxLevelInput) {
        maxLevelInput.addEventListener('change', function() {
            const newMax = Math.max(1, Math.min(1000, parseInt(this.value) || 30));
            this.value = newMax;
            rebuildLevelTable(newMax);
            console.log('[Points] max level changed to', newMax);
        });
    }
});

// 섹션 토글
function toggleSection(id) {
    const el = document.getElementById(id);
    if (!el) return;
    const isHidden = el.classList.toggle('hidden');
    const arrow = document.querySelector(`.sec-arrow[data-section="${id}"]`);
    if (arrow) {
        arrow.style.transform = isHidden ? 'rotate(-90deg)' : '';
    }
    console.log('[Points] toggle section', id, isHidden ? 'collapsed' : 'expanded');
}

// AJAX 헬퍼
async function ptFetch(payload) {
    const res = await fetch(PT_URL, {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
        body: JSON.stringify(payload)
    });
    const text = await res.text();
    console.log('[Points] raw response:', text.substring(0, 200));
    try {
        return JSON.parse(text);
    } catch(e) {
        console.error('[Points] JSON parse error:', e, 'response:', text.substring(0, 500));
        throw new Error('서버 응답이 JSON이 아닙니다 (HTTP ' + res.status + ')');
    }
}

function showMsg(type, msg, btnEl) {
    // 버튼 근처의 .msg-area에 표시, 없으면 상단 msgArea에 표시
    let area = null;
    if (btnEl) {
        area = btnEl.closest('.flex')?.querySelector('.msg-area') || btnEl.parentElement?.querySelector('.msg-area');
    }
    if (!area) area = document.getElementById('msgArea');
    if (!area) return;
    const color = type === 'success' ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400';
    area.innerHTML = `<span class="${color}">${msg}</span>`;
    setTimeout(() => area.innerHTML = '', 4000);
}

// === 기본 설정 저장 ===
async function saveBasic(btnEl) {
    console.log('[Points] saving basic');
    const data = await ptFetch({
        action: 'save_basic',
        enabled: document.getElementById('cfgEnabled')?.checked,
        name: document.getElementById('cfgName')?.value,
        max_level: parseInt(document.getElementById('cfgMaxLevel')?.value) || 30,
        level_icon: document.getElementById('cfgLevelIcon')?.value,
        disable_download: document.getElementById('cfgDisableDownload')?.checked,
        disable_read: document.getElementById('cfgDisableRead')?.checked,
        exchange_enabled: document.getElementById('cfgExchangeEnabled')?.checked,
        exchange_rate: parseFloat(document.getElementById('cfgExchangeRate')?.value) || 1,
        exchange_unit: parseInt(document.getElementById('cfgExchangeUnit')?.value) || 1000,
        exchange_min: parseInt(document.getElementById('cfgExchangeMin')?.value) || 1000,
        weight_payment: parseInt(document.getElementById('cfgWeightPayment')?.value) || 3,
        weight_activity: parseInt(document.getElementById('cfgWeightActivity')?.value) || 1
    });
    showMsg(data.success ? 'success' : 'error', data.message, btnEl);
}

// === 포인트 부여/차감 저장 ===
async function saveActions(btnEl) {
    console.log('[Points] saving actions');
    const actions = {};
    const keys = ['signup','login','insert_document','insert_comment','upload_file','download_file',
        'read_document','voter','blamer','voter_comment','blamer_comment',
        'download_file_author','read_document_author','voted','blamed','voted_comment','blamed_comment'];

    keys.forEach(k => {
        const obj = { value: parseInt(document.getElementById('act_' + k)?.value) || 0 };
        const rv = document.getElementById('rv_' + k);
        if (rv) obj.revert = rv.checked;
        const lm = document.getElementById('lm_' + k);
        if (lm) obj.limit = parseInt(lm.value) || 0;
        const en = document.getElementById('en_' + k);
        if (en) obj.except_notice = en.checked;
        actions[k] = obj;
    });

    const data = await ptFetch({ action: 'save_actions', actions });
    showMsg(data.success ? 'success' : 'error', data.message, btnEl);
}

// === 그룹 연동 저장 ===
async function saveGroup(btnEl) {
    console.log('[Points] saving group');
    const groupLevels = {};
    GROUPS_JSON.forEach(g => {
        const el = document.getElementById('grp_' + g.id);
        if (el) groupLevels[g.id] = parseInt(el.value) || 0;
    });
    const data = await ptFetch({
        action: 'save_group',
        group_reset: document.getElementById('cfgGroupReset')?.value,
        group_ratchet: document.getElementById('cfgGroupRatchet')?.value,
        group_levels: groupLevels
    });
    showMsg(data.success ? 'success' : 'error', data.message, btnEl);
}

// === 레벨 포인트 ===
function calcLevels() {
    const expr = document.getElementById('cfgExpression')?.value || 'Math.pow(l, 2) * 90';
    const inputs = document.querySelectorAll('.lvl-point');
    console.log('[Points] calculating levels with:', expr);
    inputs.forEach(inp => {
        const l = parseInt(inp.dataset.level);
        try {
            inp.value = Math.round(eval(expr));
        } catch(e) {
            console.error('[Points] calc error for level', l, e);
        }
    });
}

function resetLevels() {
    document.querySelectorAll('.lvl-point').forEach(inp => inp.value = 0);
    document.querySelectorAll('.lvl-group').forEach(sel => sel.value = '');
    console.log('[Points] levels reset');
}

async function saveLevels(btnEl) {
    console.log('[Points] saving levels');
    const levels = [];
    document.querySelectorAll('.lvl-point').forEach(inp => {
        const lvl = parseInt(inp.dataset.level);
        const grpSel = document.querySelector(`.lvl-group[data-level="${lvl}"]`);
        levels.push({
            level: lvl,
            point: parseInt(inp.value) || 0,
            group_id: grpSel?.value || null
        });
    });
    const data = await ptFetch({
        action: 'save_levels',
        levels,
        expression: document.getElementById('cfgExpression')?.value
    });
    showMsg(data.success ? 'success' : 'error', data.message, btnEl);
}

// 최고 레벨 변경 → 테이블 행 동적 추가/제거
function rebuildLevelTable(newMax) {
    const tbody = document.getElementById('levelTableBody');
    if (!tbody) return;
    const existing = tbody.querySelectorAll('.level-row');
    const currentMax = existing.length;

    if (newMax > currentMax) {
        // 추가
        for (let i = currentMax + 1; i <= newMax; i++) {
            const tr = document.createElement('tr');
            tr.className = 'level-row hover:bg-zinc-50 dark:hover:bg-zinc-700/30';
            const defaultGrp = GROUPS_JSON.find(g => g.slug === 'normal');
            let opts = '<option value=""></option>';
            GROUPS_JSON.forEach(g => {
                if (g.slug === 'staff') return; // 스태프 제외
                const sel = (defaultGrp && g.id === defaultGrp.id) ? ' selected' : '';
                opts += `<option value="${g.id}"${sel}>${g.name}</option>`;
            });
            tr.innerHTML = `
                <td class="py-2 text-zinc-800 dark:text-zinc-200 font-medium">${i}</td>
                <td class="py-2"><span class="inline-block w-6 h-6 bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded text-xs font-bold flex items-center justify-center">${i}</span></td>
                <td class="py-2"><div class="flex items-center gap-1"><input type="number" class="lvl-point w-28 px-2 py-1 border rounded dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm text-right" data-level="${i}" value="0"><span class="text-xs text-zinc-400">point</span></div></td>
                <td class="py-2"><select class="lvl-group px-2 py-1 border rounded dark:bg-zinc-700 dark:border-zinc-600 dark:text-white text-sm" data-level="${i}">${opts}</select></td>`;
            tbody.appendChild(tr);
        }
    } else if (newMax < currentMax) {
        // 제거
        for (let i = currentMax; i > newMax; i--) {
            const last = tbody.querySelector('.level-row:last-child');
            if (last) last.remove();
        }
    }
    console.log('[Points] level table rebuilt:', currentMax, '->', newMax);
}

// === 포인트 초기화 ===
async function recalcPoints() {
    if (!confirm('<?= __("points.recalc_confirm") ?>')) return;
    console.log('[Points] recalculating');
    const data = await ptFetch({ action: 'recalc' });
    showMsg(data.success ? 'success' : 'error', data.message);
}

async function resetSettingsToDefault() {
    if (!confirm('<?= __("points.reset_settings_confirm") ?>')) return;
    console.log('[Points] resetting settings to default');
    const data = await ptFetch({ action: 'reset_settings' });
    showMsg(data.success ? 'success' : 'error', data.message);
    if (data.success) setTimeout(() => location.reload(), 1500);
}

async function resetAllPoints() {
    if (!confirm('<?= __("points.reset_all_confirm") ?>')) return;
    console.log('[Points] resetting all');
    const data = await ptFetch({ action: 'reset_all' });
    showMsg(data.success ? 'success' : 'error', data.message);
    if (PT_TAB === 'members') loadMembers(1);
}

// === 회원 포인트 목록 ===
async function loadMembers(page) {
    console.log('[Points] loading members page', page);
    const search = document.getElementById('memberSearch')?.value || '';
    let data;
    try {
        data = await ptFetch({ action: 'member_list', page, search });
        console.log('[Points] member_list response:', data);
    } catch(e) {
        console.error('[Points] member_list fetch error:', e);
        document.getElementById('memberListBody').innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-red-500">API 오류: ' + e.message + '</td></tr>';
        return;
    }
    if (!data || !data.success) {
        console.error('[Points] member_list failed:', data);
        document.getElementById('memberListBody').innerHTML = '<tr><td colspan="5" class="px-4 py-8 text-center text-red-500">' + (data?.message || 'Error') + '</td></tr>';
        return;
    }

    const tbody = document.getElementById('memberListBody');
    const badge = document.getElementById('memberTotalBadge');
    if (badge) badge.textContent = `(${data.total}<?= __("points.member_count_unit") ?>)`;

    if (!data.list.length) {
        tbody.innerHTML = '<tr><td colspan="7" class="px-4 py-8 text-center text-zinc-400"><?= __("points.no_members") ?></td></tr>';
        document.getElementById('memberPagination').innerHTML = '';
        return;
    }

    tbody.innerHTML = data.list.map(m => {
        const pt = Number(m.point || 0);
        const bal = Number(m.balance || 0);
        const acc = Number(m.total_accumulated || 0);
        const lv = Number(m.level || 1);
        const ptColor = pt > 0 ? 'text-blue-600 dark:text-blue-400' : pt < 0 ? 'text-red-500' : 'text-zinc-400';
        const balColor = bal > 0 ? 'text-green-600 dark:text-green-400' : 'text-zinc-400';
        const accColor = acc > 0 ? 'text-indigo-600 dark:text-indigo-400' : 'text-zinc-400';
        return `
        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
            <td class="px-4 py-3 text-zinc-800 dark:text-zinc-200 font-medium">${escHtml(m.name)}</td>
            <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400 text-sm">${escHtml(m.email)}</td>
            <td class="px-4 py-3 text-right font-mono font-medium ${ptColor}">${pt.toLocaleString()}</td>
            <td class="px-4 py-3 text-right font-mono font-medium ${balColor}">${bal.toLocaleString()}</td>
            <td class="px-4 py-3 text-right font-mono font-medium ${accColor}">${acc.toLocaleString()}</td>
            <td class="px-4 py-3 text-center"><span class="px-2 py-0.5 bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 text-xs rounded-full font-medium">Lv.${lv}</span></td>
            <td class="px-4 py-3 text-center"><button onclick="openPointEdit('${m.user_id}','${escHtml(m.name)}',${pt},${bal})" class="text-blue-600 hover:text-blue-800 text-sm"><?= __("points.edit") ?></button></td>
        </tr>`;
    }).join('');

    // 페이지네이션
    let pgHtml = '';
    for (let p = 1; p <= data.total_pages; p++) {
        pgHtml += `<button onclick="loadMembers(${p})" class="px-3 py-1 rounded text-sm ${p === data.page ? 'bg-blue-600 text-white' : 'border dark:border-zinc-600 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700'}">${p}</button>`;
    }
    document.getElementById('memberPagination').innerHTML = pgHtml;
}

// 포인트 수정 모달
function openPointEdit(userId, name, currentPoint) {
    document.getElementById('peUserId').value = userId;
    document.getElementById('peUserName').textContent = name + ' (' + Number(currentPoint).toLocaleString() + ' point)';
    document.getElementById('pePointValue').value = '';
    document.getElementById('pointEditModal').classList.remove('hidden');
}
function closePointEdit() { document.getElementById('pointEditModal').classList.add('hidden'); }

async function submitPointEdit() {
    const userId = document.getElementById('peUserId').value;
    const raw = document.getElementById('pePointValue').value.trim();
    let mode = 'set', point = parseInt(raw);
    if (raw.startsWith('+')) { mode = 'add'; point = parseInt(raw.slice(1)); }
    else if (raw.startsWith('-')) { mode = 'minus'; point = Math.abs(parseInt(raw.slice(1))); }
    if (isNaN(point)) return;

    console.log('[Points] updating member point', userId, mode, point);
    const data = await ptFetch({ action: 'update_member_point', user_id: userId, point, mode });
    if (data.success) {
        closePointEdit();
        loadMembers(1);
    }
    showMsg(data.success ? 'success' : 'error', data.message);
}

// 모듈별 설정 저장
async function saveModuleConfig() {
    console.log('[Points] saving module config');
    const boards = {};
    document.querySelectorAll('.brd-pt').forEach(inp => {
        const bid = inp.dataset.board;
        const type = inp.dataset.type;
        if (!boards[bid]) boards[bid] = {};
        const val = inp.value.trim();
        if (val !== '') boards[bid][type] = parseInt(val);
    });
    const data = await ptFetch({ action: 'save_module_config', boards });
    showMsg(data.success ? 'success' : 'error', data.message);
}

function escHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
</script>
