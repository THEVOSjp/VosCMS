<script>
// ── 섹션 접기/펼치기 ──
function toggleSection(btn) {
    const section = btn.closest('[data-section]');
    const body = section.querySelector('.section-body');
    const chevron = btn.querySelector('.section-chevron');
    body.classList.toggle('hidden');
    chevron.classList.toggle('rotate-180');
    console.log('[BoardEdit] Section toggled:', section.dataset.section, body.classList.contains('hidden') ? 'collapsed' : 'expanded');
}

// ── 목록 컬럼 관리 ──
function colAdd() {
    const avail = document.getElementById('availColSelect');
    const selected = document.getElementById('selectedColSelect');
    if (!avail || !selected) return;
    [...avail.selectedOptions].forEach(opt => selected.appendChild(opt));
    updateColumnInput();
}
function colRemove() {
    const avail = document.getElementById('availColSelect');
    const selected = document.getElementById('selectedColSelect');
    if (!avail || !selected) return;
    [...selected.selectedOptions].forEach(opt => avail.appendChild(opt));
    updateColumnInput();
}
function colMoveUp() {
    const sel = document.getElementById('selectedColSelect');
    if (!sel) return;
    const opt = sel.selectedOptions[0];
    if (opt && opt.previousElementSibling) {
        sel.insertBefore(opt, opt.previousElementSibling);
        updateColumnInput();
    }
}
function colMoveDown() {
    const sel = document.getElementById('selectedColSelect');
    if (!sel) return;
    const opt = sel.selectedOptions[0];
    if (opt && opt.nextElementSibling) {
        sel.insertBefore(opt.nextElementSibling, opt);
        updateColumnInput();
    }
}
document.getElementById('availColSelect')?.addEventListener('dblclick', colAdd);
document.getElementById('selectedColSelect')?.addEventListener('dblclick', colRemove);

function updateColumnInput() {
    const el = document.getElementById('listColumnsInput');
    if (!el) return;
    const cols = [...document.getElementById('selectedColSelect').options].map(o => o.value);
    el.value = JSON.stringify(cols);
    console.log('[BoardEdit] Columns updated:', cols);
}
</script>
