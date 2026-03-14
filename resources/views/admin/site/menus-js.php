<script>
// ─── 상태 ───
var selectedType = null;   // 'sitemap' | 'menuItem'
var selectedId = null;
var selectedSitemapId = null;
var selectedIsHome = false;
var selectedMenuType = null;
var addMode = null; // 'menu' | 'sub'
var csrfToken = '<?= $_SESSION['csrf_token'] ?? '' ?>';
var apiUrl = '<?= $adminUrl ?>/site/menus-api';

// ─── 클립보드 (복사/잘라내기/붙여넣기) ───
var clipboard = { id: null, sitemapId: null, mode: null }; // mode: 'copy' | 'cut'

// ─── 패널 제어 ───
function showPanel(num) {
    document.getElementById('panel' + num).classList.remove('hidden');
    console.log('[Menu] Show panel', num);
}
function closePanel(num) {
    for (var i = num; i <= 4; i++) {
        document.getElementById('panel' + i).classList.add('hidden');
    }
    if (num <= 2) {
        clearSelection();
        selectedType = null;
        selectedId = null;
    }
    clearActiveCtx();
    console.log('[Menu] Close panels from', num);
}
function clearActiveCtx() {
    document.querySelectorAll('.ctx-btn.active').forEach(function(el) {
        el.classList.remove('active');
    });
}

// ─── 트리 선택 ───
function clearSelection() {
    document.querySelectorAll('.tree-item.selected').forEach(function(el) {
        el.classList.remove('selected');
    });
}

function selectSitemap(id, title) {
    clearSelection();
    closePanel(3);
    selectedType = 'sitemap';
    selectedId = id;
    selectedSitemapId = id;
    selectedIsHome = false;

    var el = document.querySelector('[data-type="sitemap"][data-id="' + id + '"]');
    if (el) el.classList.add('selected');

    document.getElementById('panel2Title').textContent = title;
    document.getElementById('sitemapCtx').classList.remove('hidden');
    document.getElementById('menuItemCtx').classList.add('hidden');
    // 붙여넣기 버튼 상태
    updatePasteBtn();
    showPanel(2);
    console.log('[Menu] Selected sitemap:', id, title);
}

function selectMenuItem(id, title, sitemapId, isHome) {
    clearSelection();
    closePanel(3);
    selectedType = 'menuItem';
    selectedId = id;
    selectedSitemapId = sitemapId;
    selectedIsHome = !!isHome;

    var el = document.querySelector('[data-type="menuItem"][data-id="' + id + '"]');
    if (el) el.classList.add('selected');

    document.getElementById('panel2Title').textContent = title;
    document.getElementById('sitemapCtx').classList.add('hidden');
    document.getElementById('menuItemCtx').classList.remove('hidden');
    showPanel(2);

    var homeText = document.getElementById('homeToggleText');
    homeText.textContent = isHome ? '<?= __('site.menus.unset_home') ?>' : '<?= __('site.menus.set_home') ?>';
    console.log('[Menu] Selected menu item:', id, title, 'isHome:', isHome);
}

// ─── 트리 접기/펼치기 ───
function toggleTreeChildren(btn, e) {
    e.stopPropagation();
    var group = btn.closest('.sitemap-group') || btn.closest('.tree-item-wrap');
    var children = btn.parentElement.nextElementSibling;
    if (!children || !children.classList.contains('tree-children')) return;

    var isHidden = children.classList.toggle('hidden');
    btn.classList.toggle('collapsed', isHidden);
    console.log('[Menu] Toggle tree:', isHidden ? 'collapsed' : 'expanded');
}

// ─── 사이트맵 액션 ───
function addSitemap() {
    var title = prompt('<?= __('site.menus.enter_sitemap_name') ?>');
    if (title && title.trim()) {
        apiCall('add_sitemap', { title: title.trim() });
    }
}

function renameSitemap() {
    var title = prompt('<?= __('site.menus.enter_new_name') ?>', document.getElementById('panel2Title').textContent);
    if (title && title.trim()) {
        apiCall('rename_sitemap', { id: selectedId, title: title.trim() });
    }
}

function deleteSitemap() {
    if (confirm('<?= __('site.menus.confirm_delete_sitemap') ?>')) {
        apiCall('delete_sitemap', { id: selectedId });
    }
}

function editSitemapItems() {
    clearActiveCtx();
    var btn = document.querySelector('[data-ctx="edit_sitemap"]');
    if (btn) btn.classList.add('active');

    // 패널3에 사이트맵 편집 UI 표시 (제목 변경)
    closePanel(4);
    var p3 = document.getElementById('panel3');
    document.getElementById('panel3Title').textContent = '<?= __('site.menus.edit_sitemap') ?>';
    // 패널3 내용을 사이트맵 편집으로 교체
    document.getElementById('menuTypeList').classList.add('hidden');
    document.getElementById('sitemapEditPanel').classList.remove('hidden');
    document.querySelector('#panel3 .border-t').classList.add('hidden');
    // 사이트맵 타이틀 input에 현재 값 설정
    var titleEl = document.getElementById('panel2Title');
    document.getElementById('editSitemapTitle').value = titleEl.textContent;
    showPanel(3);
    console.log('[Menu] Edit sitemap:', selectedSitemapId);
}

function saveSitemapEdit() {
    var title = document.getElementById('editSitemapTitle').value.trim();
    if (!title) return;
    apiCall('rename_sitemap', { id: selectedSitemapId, title: title });
}

function restorePanel3() {
    document.getElementById('menuTypeList').classList.remove('hidden');
    document.getElementById('sitemapEditPanel').classList.add('hidden');
    document.querySelector('#panel3 .border-t').classList.remove('hidden');
}

function applyDesignBulk() {
    clearActiveCtx();
    var btn = document.querySelector('[data-ctx="design_bulk"]');
    if (btn) btn.classList.add('active');

    // 패널3에 디자인 설정 UI 표시
    closePanel(4);
    document.getElementById('panel3Title').textContent = '<?= __('site.menus.design_bulk') ?>';
    document.getElementById('menuTypeList').classList.add('hidden');
    document.getElementById('sitemapEditPanel').classList.add('hidden');
    document.getElementById('designBulkPanel').classList.remove('hidden');
    document.querySelector('#panel3 .border-t').classList.add('hidden');
    showPanel(3);
    console.log('[Menu] Design bulk for sitemap:', selectedSitemapId);
}

// ─── 클립보드: 복사/잘라내기/붙여넣기 ───
function copyMenuItem() {
    clipboard = { id: selectedId, sitemapId: selectedSitemapId, mode: 'copy' };
    console.log('[Menu] Copied:', clipboard);
    // 시각적 피드백
    showToast('<?= __('site.menus.copied') ?>');
}

function cutMenuItem() {
    clipboard = { id: selectedId, sitemapId: selectedSitemapId, mode: 'cut' };
    // 잘라내기 대상 시각적 표시
    document.querySelectorAll('.tree-item.cut-item').forEach(function(el) {
        el.classList.remove('cut-item');
    });
    var el = document.querySelector('[data-type="menuItem"][data-id="' + selectedId + '"]');
    if (el) el.classList.add('cut-item');
    console.log('[Menu] Cut:', clipboard);
    showToast('<?= __('site.menus.cut') ?>');
}

function pasteSitemap() {
    if (!clipboard.id) {
        console.log('[Menu] Nothing to paste');
        return;
    }
    console.log('[Menu] Paste:', clipboard, 'into sitemap:', selectedSitemapId);

    if (clipboard.mode === 'copy') {
        apiCall('copy_menu_item', {
            id: clipboard.id,
            target_sitemap_id: selectedSitemapId,
            target_parent_id: null
        });
    } else if (clipboard.mode === 'cut') {
        apiCall('move_menu_item', {
            id: clipboard.id,
            sitemap_id: selectedSitemapId,
            parent_id: null,
            sort_order: 999
        });
        clipboard = { id: null, sitemapId: null, mode: null };
    }
}

function pasteAsChild() {
    if (!clipboard.id) return;
    console.log('[Menu] Paste as child of:', selectedId);

    if (clipboard.mode === 'copy') {
        apiCall('copy_menu_item', {
            id: clipboard.id,
            target_sitemap_id: selectedSitemapId,
            target_parent_id: selectedId
        });
    } else if (clipboard.mode === 'cut') {
        apiCall('move_menu_item', {
            id: clipboard.id,
            sitemap_id: selectedSitemapId,
            parent_id: selectedId,
            sort_order: 999
        });
        clipboard = { id: null, sitemapId: null, mode: null };
    }
}

function updatePasteBtn() {
    var pasteBtn = document.getElementById('pasteSitemapBtn');
    if (pasteBtn) {
        pasteBtn.style.opacity = clipboard.id ? '1' : '0.4';
        pasteBtn.style.pointerEvents = clipboard.id ? '' : 'none';
    }
}

function showToast(msg) {
    var t = document.getElementById('menuToast');
    if (!t) return;
    t.textContent = msg;
    t.classList.remove('hidden');
    setTimeout(function() { t.classList.add('hidden'); }, 1500);
}

// ─── 메뉴 추가 (패널3 열기) ───
function openAddMenu() {
    addMode = 'menu';
    clearActiveCtx();
    restorePanel3();
    var btn = document.querySelector('[data-ctx="add_menu"]');
    if (btn) btn.classList.add('active');
    closePanel(4);
    document.getElementById('panel3Title').textContent = '<?= __('site.menus.add_menu') ?>';
    clearActiveMenuType();
    showPanel(3);
    console.log('[Menu] Open add menu panel');
}

function openAddSubMenu() {
    addMode = 'sub';
    clearActiveCtx();
    restorePanel3();
    var btn = document.querySelector('[data-ctx="add_sub"]');
    if (btn) btn.classList.add('active');
    closePanel(4);
    document.getElementById('panel3Title').textContent = '<?= __('site.menus.add_sub_menu') ?>';
    clearActiveMenuType();
    showPanel(3);
    console.log('[Menu] Open add sub-menu panel');
}

function clearActiveMenuType() {
    document.querySelectorAll('#menuTypeList .ctx-btn').forEach(function(el) {
        el.classList.remove('active');
    });
}

// ─── 메뉴 타입 선택 (패널4 열기) ───
var menuTypeInfo = {
    page:     { title: '<?= __('site.menus.type_page') ?>',     desc: '<?= __('site.menus.desc_page') ?>' },
    widget:   { title: '<?= __('site.menus.type_widget') ?>',   desc: '<?= __('site.menus.desc_widget') ?>' },
    external: { title: '<?= __('site.menus.type_external') ?>', desc: '<?= __('site.menus.desc_external') ?>' },
    board:    { title: '<?= __('site.menus.type_board') ?>',    desc: '<?= __('site.menus.desc_board') ?>' },
    member:   { title: '<?= __('site.menus.type_member') ?>',   desc: '<?= __('site.menus.desc_member') ?>' },
    shortcut: { title: '<?= __('site.menus.type_shortcut') ?>', desc: '<?= __('site.menus.desc_shortcut') ?>' }
};

function selectMenuType(type) {
    selectedMenuType = type;
    menuMultilangTempKey = null;
    clearActiveMenuType();
    var btn = document.querySelector('[data-mtype="' + type + '"]');
    if (btn) btn.classList.add('active');

    var info = menuTypeInfo[type] || { title: type, desc: '' };
    document.getElementById('panel4Title').textContent = info.title;
    document.getElementById('panel4Desc').textContent = info.desc;

    // 폼 초기화
    document.getElementById('formAction').value = 'add_menu_item';
    document.getElementById('formId').value = '';
    document.getElementById('formSitemapId').value = selectedSitemapId;
    document.getElementById('formParentId').value = (addMode === 'sub') ? selectedId : '';
    document.getElementById('formMenuType').value = type;
    document.getElementById('formTitle').value = '';
    document.getElementById('formIcon').value = '';
    document.getElementById('formCssClass').value = '';
    document.getElementById('formDesc').value = '';
    document.getElementById('formUrl').value = '';
    document.getElementById('formTarget').value = '_self';
    var formExpand = document.getElementById('formExpand');
    if (formExpand) formExpand.checked = false;

    // 바로가기: 탭 UI 표시, 메뉴 ID 숨기기
    var isShortcut = (type === 'shortcut');
    var isExt = (type === 'external' || type === 'shortcut');
    document.getElementById('formMenuIdWrap').style.display = isShortcut ? 'none' : '';
    document.getElementById('formShortcutWrap').classList.toggle('hidden', !isShortcut);
    document.getElementById('formTargetWrap').style.display = isExt ? '' : 'none';
    document.getElementById('formExpandWrap').style.display = '';

    // 바로가기 탭 초기화
    if (isShortcut) {
        document.getElementById('formShortcutUrl').value = '';
        switchShortcutTab('url');
    }

    document.querySelectorAll('.field-help').forEach(function(el) { el.classList.add('hidden'); });

    showPanel(4);
    console.log('[Menu] Selected menu type:', type);
}

// ─── 메뉴 항목 편집 (패널4 열기) ───
function editMenuItem() {
    clearActiveCtx();
    menuMultilangTempKey = null; // 기존 메뉴 편집 시 임시 키 리셋
    var el = document.querySelector('[data-type="menuItem"][data-id="' + selectedId + '"]');
    if (!el) return;

    document.getElementById('panel4Title').textContent = '<?= __('site.menus.edit_item') ?>';
    document.getElementById('panel4Desc').textContent = '';

    document.getElementById('formAction').value = 'update_menu_item';
    document.getElementById('formId').value = selectedId;
    document.getElementById('formSitemapId').value = selectedSitemapId;
    document.getElementById('formParentId').value = '';
    document.getElementById('formMenuType').value = '';
    document.getElementById('formTitle').value = el.dataset.title || '';
    document.getElementById('formIcon').value = el.dataset.icon || '';
    document.getElementById('formCssClass').value = el.dataset.cssClass || '';
    document.getElementById('formDesc').value = el.dataset.description || '';
    document.getElementById('formUrl').value = el.dataset.url || '';
    document.getElementById('formTarget').value = el.dataset.target || '_self';
    var formExpand = document.getElementById('formExpand');
    if (formExpand) formExpand.checked = (el.dataset.expand === '1');

    // 편집 모드: 메뉴 ID 표시, 바로가기 탭 숨기기
    document.getElementById('formMenuIdWrap').style.display = '';
    document.getElementById('formShortcutWrap').classList.add('hidden');
    document.getElementById('formTargetWrap').style.display = '';
    document.getElementById('formExpandWrap').style.display = '';

    document.querySelectorAll('.field-help').forEach(function(el) { el.classList.add('hidden'); });

    closePanel(3);
    showPanel(4);
    console.log('[Menu] Edit menu item:', selectedId);
}

// ─── 메뉴 항목 기타 액션 ───
function renameMenuItem() {
    var el = document.querySelector('[data-type="menuItem"][data-id="' + selectedId + '"]');
    var currentTitle = el ? el.dataset.title : '';
    var title = prompt('<?= __('site.menus.enter_new_name') ?>', currentTitle);
    if (title && title.trim()) {
        apiCall('rename_menu_item', { id: selectedId, title: title.trim() });
    }
}

function deleteMenuItem() {
    if (selectedIsHome) {
        alert('<?= __('site.menus.cannot_delete_home') ?>');
        return;
    }
    if (confirm('<?= __('site.menus.confirm_delete_item') ?>')) {
        apiCall('delete_menu_item', { id: selectedId });
    }
}

function toggleHomeMenu() {
    apiCall('toggle_home', { id: selectedId, sitemap_id: selectedSitemapId });
}

function installMenuType() {
    console.log('[Menu] Install menu type');
    alert('<?= __('site.menus.install_coming_soon') ?>');
}

// ─── 폼 저장 ───
function saveForm() {
    var action = document.getElementById('formAction').value;
    var title = document.getElementById('formTitle').value.trim();
    if (!title) {
        alert('<?= __('site.menus.title_required') ?>');
        document.getElementById('formTitle').focus();
        return;
    }
    // 바로가기 URL 탭인 경우 formShortcutUrl 값을 formUrl에 반영
    var menuType = document.getElementById('formMenuType').value;
    if (menuType === 'shortcut' && currentShortcutTab === 'url') {
        var shortcutUrl = document.getElementById('formShortcutUrl').value.trim();
        document.getElementById('formUrl').value = shortcutUrl;
    }

    var formExpand = document.getElementById('formExpand');
    var data = {
        id: document.getElementById('formId').value,
        sitemap_id: document.getElementById('formSitemapId').value,
        parent_id: document.getElementById('formParentId').value,
        menu_type: menuType,
        title: title,
        url: document.getElementById('formUrl').value.trim(),
        target: document.getElementById('formTarget').value,
        icon: document.getElementById('formIcon').value.trim(),
        css_class: document.getElementById('formCssClass').value.trim(),
        description: document.getElementById('formDesc').value.trim(),
        expand: formExpand ? (formExpand.checked ? 1 : 0) : 0
    };
    console.log('[Menu] Save form:', action, data);
    apiCall(action, data);
}

// ─── API 호출 ───
function apiCall(action, data, callback) {
    data.action = action;
    data.csrf_token = csrfToken;
    console.log('[Menu API] Call:', action, data);

    fetch(apiUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(data)
    })
    .then(function(r) { return r.json(); })
    .then(function(result) {
        console.log('[Menu API] Response:', result);
        if (result.success) {
            // 신규 메뉴 생성 시 임시 다국어 키를 실제 ID 키로 마이그레이션
            if ((action === 'add_menu_item') && result.id && menuMultilangTempKey) {
                migrateMultilangKeys(menuMultilangTempKey, 'menu_item.' + result.id);
                menuMultilangTempKey = null;
            }
            if (callback) callback(result);
            else location.reload();
        } else {
            alert(result.message || 'Error');
        }
    })
    .catch(function(err) {
        console.error('[Menu API] Error:', err);
        alert('<?= __('site.menus.server_error') ?>');
    });
}

// ─── 검색 ───
function searchMenu() {
    var query = document.getElementById('menuSearch').value.toLowerCase().trim();
    document.querySelectorAll('.tree-item').forEach(function(el) {
        var text = el.textContent.toLowerCase();
        if (!query || text.includes(query)) {
            el.style.display = '';
        } else {
            el.style.display = 'none';
        }
    });
    // 검색 시 모든 tree-children 열기
    if (query) {
        document.querySelectorAll('.tree-children').forEach(function(el) {
            el.classList.remove('hidden');
        });
    }
    console.log('[Menu] Search:', query);
}
document.getElementById('menuSearch').addEventListener('keyup', function(e) {
    if (e.key === 'Enter') searchMenu();
});

// ─── 도움말 토글 ───
function toggleHelp(fieldId) {
    var el = document.getElementById('help-' + fieldId);
    if (el) {
        el.classList.toggle('hidden');
        console.log('[Menu] Toggle help:', fieldId);
    }
}
function closeHelp(fieldId) {
    var el = document.getElementById('help-' + fieldId);
    if (el) el.classList.add('hidden');
}

// ─── 다국어 모달 연결 ───
var menuMultilangTempKey = null; // 신규 메뉴용 임시 키

function getMenuLangKey(field) {
    var menuId = document.getElementById('formId').value;
    if (menuId) {
        return 'menu_item.' + menuId + '.' + field;
    }
    // 신규 메뉴: 임시 키 생성 (세션 내 유지)
    if (!menuMultilangTempKey) {
        menuMultilangTempKey = 'menu_item.tmp_' + Date.now() + '_' + Math.random().toString(36).substr(2, 6);
    }
    return menuMultilangTempKey + '.' + field;
}

function migrateMultilangKeys(oldPrefix, newPrefix) {
    var fields = ['title', 'description'];
    fields.forEach(function(field) {
        var oldKey = oldPrefix + '.' + field;
        var newKey = newPrefix + '.' + field;
        console.log('[Menu] Migrate multilang key:', oldKey, '→', newKey);
        fetch('<?= $adminUrl ?>/api/translations?action=rename', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ old_key: oldKey, new_key: newKey })
        }).then(function(r) { return r.json(); })
          .then(function(res) { console.log('[Menu] Migrate result:', res); })
          .catch(function(err) { console.error('[Menu] Migrate error:', err); });
    });
}

function openMenuMultilang(field) {
    var langKey = getMenuLangKey(field);
    var inputId = (field === 'title') ? 'formTitle' : 'formDesc';
    var type = (field === 'description') ? 'editor' : 'text';
    console.log('[Menu] Open multilang:', langKey, inputId, type);
    openMultilangModal(langKey, inputId, type);
}

// ─── 바로가기 탭 전환 ───
var currentShortcutTab = 'url';

function switchShortcutTab(tab) {
    currentShortcutTab = tab;
    var tabUrl = document.getElementById('tabUrlLink');
    var tabMenu = document.getElementById('tabMenuLink');
    var panelUrl = document.getElementById('shortcutUrlPanel');
    var panelMenu = document.getElementById('shortcutMenuPanel');

    var activeClass = 'text-blue-600 dark:text-blue-400 border-blue-600 dark:border-blue-400';
    var inactiveClass = 'text-zinc-400 dark:text-zinc-500 border-transparent hover:text-zinc-600 dark:hover:text-zinc-300';

    if (tab === 'url') {
        tabUrl.className = 'px-3 py-1.5 text-xs font-medium border-b-2 transition ' + activeClass;
        tabMenu.className = 'px-3 py-1.5 text-xs font-medium border-b-2 transition ' + inactiveClass;
        panelUrl.classList.remove('hidden');
        panelMenu.classList.add('hidden');
    } else {
        tabUrl.className = 'px-3 py-1.5 text-xs font-medium border-b-2 transition ' + inactiveClass;
        tabMenu.className = 'px-3 py-1.5 text-xs font-medium border-b-2 transition ' + activeClass;
        panelUrl.classList.add('hidden');
        panelMenu.classList.remove('hidden');
    }
    console.log('[Menu] Shortcut tab:', tab);
}

function selectShortcutMenu(menuId, menuTitle, menuUrl) {
    var display = document.getElementById('shortcutSelectedMenu');
    display.textContent = menuTitle;
    display.classList.remove('text-zinc-500', 'dark:text-zinc-400');
    display.classList.add('text-blue-600', 'dark:text-blue-400', 'font-medium');

    // URL에 메뉴 링크 형식으로 설정
    document.getElementById('formUrl').value = '#menu_' + menuId;

    // 선택 하이라이트
    document.querySelectorAll('.shortcut-menu-item').forEach(function(el) {
        el.classList.remove('bg-blue-50', 'dark:bg-blue-900/30', 'font-bold');
    });
    event.currentTarget.classList.add('bg-blue-50', 'dark:bg-blue-900/30', 'font-bold');

    console.log('[Menu] Selected shortcut menu:', menuId, menuTitle);
}
</script>
<?php include __DIR__ . '/menus-dnd.php'; ?>
