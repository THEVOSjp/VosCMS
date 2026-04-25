<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<script>
console.log('[ExtraVars] 확장 변수 탭 로드됨');
const evAdminUrl = '<?= $adminUrl ?>';
const evBoardId = <?= $boardId ?>;

function getEvLangKey(field) {
    const evId = document.getElementById('evEditId').value;
    const varName = document.getElementById('evVarName').value || 'new';
    return 'board_ev.' + evBoardId + '.' + varName + '.' + field;
}

function toggleEvOptions() {
    const type = document.getElementById('evVarType').value;
    const show = ['select', 'radio', 'checkbox'].includes(type);
    document.getElementById('evOptionsWrap').classList.toggle('hidden', !show);
}

async function openEvModal(evId) {
    console.log('[ExtraVars] openEvModal:', evId);
    document.getElementById('evEditId').value = evId;

    // 초기화
    document.getElementById('evVarName').value = '';
    document.getElementById('evTitle').value = '';
    document.getElementById('evVarType').value = 'text';
    document.getElementById('evDesc').value = '';
    document.getElementById('evOptions').value = '';
    document.getElementById('evDefault').value = '';
    document.getElementById('evRequired').checked = false;
    document.getElementById('evSearchable').checked = false;
    document.getElementById('evShownInList').checked = false;
    document.getElementById('evPermission').value = 'all';
    document.getElementById('evVarName').removeAttribute('readonly');
    toggleEvOptions();

    if (evId) {
        try {
            const resp = await fetch(evAdminUrl + '/site/boards/api?action=extra_var_get&board_id=' + evBoardId + '&ev_id=' + evId, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await resp.json();
            if (data.success && data.extra_var) {
                const ev = data.extra_var;
                // 현재 로케일 번역이 있으면 우선 표시 (없으면 원본)
                const L = data.localized || {};
                document.getElementById('evVarName').value = ev.var_name || '';
                document.getElementById('evVarName').setAttribute('readonly', 'readonly');
                document.getElementById('evTitle').value = L.title || ev.title || '';
                document.getElementById('evVarType').value = ev.var_type || 'text';
                document.getElementById('evDesc').value = L.description || ev.description || '';
                // 옵션: DB에 JSON 배열로 저장되어 있으면 줄바꿈 형식으로 변환해 표시
                let optsStr = L.options || ev.options || '';
                if (optsStr) {
                    try {
                        const parsed = JSON.parse(optsStr);
                        if (Array.isArray(parsed)) optsStr = parsed.join('\n');
                    } catch (e) { /* 줄바꿈 형식이면 그대로 사용 */ }
                }
                document.getElementById('evOptions').value = optsStr;
                document.getElementById('evDefault').value = L.default_value || ev.default_value || '';
                document.getElementById('evRequired').checked = ev.is_required == 1;
                document.getElementById('evSearchable').checked = ev.is_searchable == 1;
                document.getElementById('evShownInList').checked = ev.is_shown_in_list == 1;
                document.getElementById('evPermission').value = ev.permission || 'all';
                toggleEvOptions();
            }
        } catch (err) { console.error('[ExtraVars] load error:', err); }
    }

    document.getElementById('evModal').classList.remove('hidden');
}

function closeEvModal() {
    document.getElementById('evModal').classList.add('hidden');
}

async function saveEv() {
    const evId = document.getElementById('evEditId').value;
    const varName = document.getElementById('evVarName').value.trim();
    const title = document.getElementById('evTitle').value.trim();
    if (!varName || !title) { alert('변수 이름과 표시 이름은 필수입니다.'); return; }

    const params = {
        action: evId != '0' ? 'extra_var_update' : 'extra_var_add',
        board_id: evBoardId,
        var_name: varName,
        var_type: document.getElementById('evVarType').value,
        title: title,
        description: document.getElementById('evDesc').value,
        options: document.getElementById('evOptions').value,
        default_value: document.getElementById('evDefault').value,
        is_required: document.getElementById('evRequired').checked ? 1 : 0,
        is_searchable: document.getElementById('evSearchable').checked ? 1 : 0,
        is_shown_in_list: document.getElementById('evShownInList').checked ? 1 : 0,
        permission: document.getElementById('evPermission').value || 'all',
    };
    if (evId != '0') params.ev_id = evId;

    console.log('[ExtraVars] save:', params);
    try {
        const resp = await fetch(evAdminUrl + '/site/boards/api', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(params)
        });
        const data = await resp.json();
        console.log('[ExtraVars] save result:', data);
        if (data.success) { closeEvModal(); location.reload(); }
        else alert(data.message || 'Error');
    } catch (err) { console.error(err); alert('Error: ' + err.message); }
}

async function deleteEv(evId) {
    if (!confirm('<?= __('site.boards.ev_delete_confirm') ?>')) return;
    try {
        const resp = await fetch(evAdminUrl + '/site/boards/api', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'extra_var_delete', board_id: evBoardId, ev_id: evId })
        });
        const data = await resp.json();
        if (data.success) location.reload();
        else alert(data.message || 'Error');
    } catch (err) { console.error(err); alert('Error: ' + err.message); }
}

// 드래그 정렬
if (document.getElementById('evSortList') && typeof Sortable !== 'undefined') {
    new Sortable(document.getElementById('evSortList'), {
        handle: '.ev-drag-handle',
        animation: 200,
        ghostClass: 'sortable-ghost',
        onEnd: function() {
            const order = [...document.querySelectorAll('#evSortList .ev-item')].map((el, i) => ({ id: parseInt(el.dataset.id), sort_order: i }));
            console.log('[ExtraVars] reorder:', order);
            fetch(evAdminUrl + '/site/boards/api', {
                method: 'POST',
                headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'extra_var_reorder', board_id: evBoardId, order })
            }).then(r => r.json()).then(d => console.log('[ExtraVars] reorder result:', d));
        }
    });
}

document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !document.getElementById('evModal').classList.contains('hidden')) closeEvModal();
});
</script>
