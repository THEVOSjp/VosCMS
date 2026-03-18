<!-- SortableJS CDN -->
<script src="https://cdn.jsdelivr.net/npm/sortablejs@1.15.6/Sortable.min.js"></script>
<style>
.sortable-ghost { opacity: 0.4; }
.sortable-drag { box-shadow: 0 4px 12px rgba(0,0,0,0.15); border-radius: 0.5rem; }
.sortable-chosen .cat-row { border-color: #3b82f6; }
</style>
<script>
console.log('[BoardCategories] 분류 관리 탭 로드됨');
const catAdminUrl = '<?= $adminUrl ?>';
const catBoardId = <?= $boardId ?>;

// 색상 입력 동기화
document.getElementById('catEditFontColor')?.addEventListener('input', function() {
    document.getElementById('catEditFontColorText').value = this.value;
});
document.getElementById('catEditFontColorText')?.addEventListener('input', function() {
    const v = this.value.trim();
    if (/^#[0-9a-fA-F]{6}$/.test(v)) document.getElementById('catEditFontColor').value = v;
});

// 다국어 키 생성
function getCatLangKey(field) {
    const catId = document.getElementById('catEditId').value;
    return 'board_category.' + (catId || 'new') + '.' + field;
}

// 모달 열기 (catId=0이면 새 분류, parentId로 부모 지정)
async function openCatModal(catId, parentId) {
    console.log('[BoardCategories] openCatModal:', catId, 'parent:', parentId);
    document.getElementById('catEditId').value = catId;
    document.getElementById('catEditParent').value = parentId;
    document.getElementById('catModalTitle').textContent = catId
        ? '<?= __('site.boards.cat_edit_title') ?>'
        : (parentId ? '<?= __('site.boards.cat_add_sub') ?>' : '<?= __('site.boards.cat_add') ?>');

    // 초기화
    document.getElementById('catEditName').value = '';
    document.getElementById('catEditFontColor').value = '#000000';
    document.getElementById('catEditFontColorText').value = '';
    document.getElementById('catEditDesc').value = '';
    document.getElementById('catEditExpanded').checked = false;
    document.getElementById('catEditDefault').checked = false;
    document.querySelectorAll('.cat-group-cb').forEach(cb => cb.checked = false);

    // 수정 모드: 기존 데이터 로드
    if (catId) {
        try {
            const resp = await fetch(catAdminUrl + '/site/boards/api?action=category_get&board_id=' + catBoardId + '&category_id=' + catId, {
                headers: { 'X-Requested-With': 'XMLHttpRequest' }
            });
            const data = await resp.json();
            console.log('[BoardCategories] category data:', data);
            if (data.success && data.category) {
                const c = data.category;
                document.getElementById('catEditName').value = c.name || '';
                document.getElementById('catEditDesc').value = c.description || '';
                document.getElementById('catEditExpanded').checked = c.is_expanded == 1;
                document.getElementById('catEditDefault').checked = c.is_default == 1;
                if (c.font_color) {
                    document.getElementById('catEditFontColorText').value = c.font_color;
                    if (/^#[0-9a-fA-F]{6}$/.test(c.font_color)) {
                        document.getElementById('catEditFontColor').value = c.font_color;
                    }
                }
                if (c.allowed_groups) {
                    const groups = c.allowed_groups.split(',');
                    document.querySelectorAll('.cat-group-cb').forEach(cb => {
                        cb.checked = groups.includes(cb.value);
                    });
                }
            }
        } catch (err) {
            console.error('[BoardCategories] load error:', err);
        }
    }

    document.getElementById('catEditModal').classList.remove('hidden');
}

function closeCatModal() {
    document.getElementById('catEditModal').classList.add('hidden');
}

// 저장
async function saveCatModal() {
    const catId = document.getElementById('catEditId').value;
    const name = document.getElementById('catEditName').value.trim();
    if (!name) { alert('<?= __('site.boards.cat_name_required') ?>'); return; }

    const groups = [...document.querySelectorAll('.cat-group-cb:checked')].map(cb => cb.value).join(',');

    const params = {
        action: catId != '0' ? 'category_update' : 'category_add',
        board_id: catBoardId,
        name: name,
        parent_id: document.getElementById('catEditParent').value,
        font_color: document.getElementById('catEditFontColorText').value || document.getElementById('catEditFontColor').value,
        description: document.getElementById('catEditDesc').value,
        allowed_groups: groups,
        is_expanded: document.getElementById('catEditExpanded').checked ? 1 : 0,
        is_default: document.getElementById('catEditDefault').checked ? 1 : 0,
    };
    if (catId != '0') params.category_id = catId;

    console.log('[BoardCategories] save:', params);

    try {
        const resp = await fetch(catAdminUrl + '/site/boards/api', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(params)
        });
        const data = await resp.json();
        console.log('[BoardCategories] save response:', data);
        if (data.success) {
            closeCatModal();
            location.reload();
        } else {
            alert(data.message || 'Error');
        }
    } catch (err) {
        console.error('[BoardCategories] save error:', err);
        alert('Error: ' + err.message);
    }
}

// 삭제
async function deleteCat(catId) {
    if (!confirm('<?= __('site.boards.cat_delete_confirm') ?>')) return;
    console.log('[BoardCategories] delete:', catId);
    try {
        const resp = await fetch(catAdminUrl + '/site/boards/api', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams({ action: 'category_delete', board_id: catBoardId, category_id: catId })
        });
        const data = await resp.json();
        if (data.success) location.reload();
        else alert(data.message || 'Error');
    } catch (err) {
        console.error(err);
        alert('Error: ' + err.message);
    }
}

// 게시판 필드 저장 (분류 설정)
async function saveBoardField(field, value) {
    console.log('[BoardCategories] saveBoardField:', field, value);
    const params = { action: 'update', board_id: catBoardId };
    params[field] = value;
    try {
        await fetch(catAdminUrl + '/site/boards/api', {
            method: 'POST',
            headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/x-www-form-urlencoded' },
            body: new URLSearchParams(params)
        });
    } catch (err) { console.error(err); }
}

// ESC로 모달 닫기
document.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' && !document.getElementById('catEditModal').classList.contains('hidden')) {
        closeCatModal();
    }
});

// ── SortableJS 중첩 드래그&드롭 ──
function initCatSortable() {
    if (typeof Sortable === 'undefined') {
        console.log('[BoardCategories] Waiting for SortableJS...');
        setTimeout(initCatSortable, 100);
        return;
    }

    document.querySelectorAll('.cat-sortable-list').forEach(list => {
        new Sortable(list, {
            group: 'categories',
            handle: '.cat-drag-handle',
            animation: 200,
            fallbackOnBody: true,
            swapThreshold: 0.65,
            ghostClass: 'sortable-ghost',
            chosenClass: 'sortable-chosen',
            dragClass: 'sortable-drag',
            onEnd: function() {
                saveCatOrder();
            }
        });
    });
    console.log('[BoardCategories] SortableJS initialized');
}

// 순서 저장
function saveCatOrder() {
    const order = [];
    function traverse(container, parentId) {
        const items = container.querySelectorAll(':scope > .category-item');
        items.forEach((item, index) => {
            const id = parseInt(item.dataset.id);
            order.push({ id, parent_id: parentId, sort_order: index });
            const childList = item.querySelector(':scope > .cat-sortable-list');
            if (childList) traverse(childList, id);
        });
    }

    const root = document.getElementById('catSortRoot');
    if (root) traverse(root, 0);

    console.log('[BoardCategories] Save order:', order);

    fetch(catAdminUrl + '/site/boards/api', {
        method: 'POST',
        headers: { 'X-Requested-With': 'XMLHttpRequest', 'Content-Type': 'application/json' },
        body: JSON.stringify({ action: 'category_reorder', board_id: catBoardId, order: order })
    })
    .then(r => r.json())
    .then(data => console.log('[BoardCategories] Reorder result:', data))
    .catch(err => console.error('[BoardCategories] Reorder error:', err));
}

initCatSortable();
</script>
