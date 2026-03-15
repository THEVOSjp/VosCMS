<script>
const BUNDLE_URL = window.location.href;
const CURRENCY = '<?= $currency ?>';
const LABELS = {
    create: '<?= __("bundles.create") ?>',
    edit: '<?= __("bundles.edit") ?>',
    confirmDelete: '<?= __("bundles.confirm_delete") ?>',
    discount: '<?= __("bundles.discount_info") ?>',
    services: '<?= __("bundles.services_count") ?>',
    active: '<?= __("bundles.active") ?>',
    inactive: '<?= __("bundles.inactive") ?>'
};

// DOM
const bundleList = document.getElementById('bundleList');
const emptyState = document.getElementById('emptyState');
const modal = document.getElementById('bundleModal');
const modalTitle = document.getElementById('modalTitle');
const checks = document.querySelectorAll('.svc-check');

// 초기 로드
document.addEventListener('DOMContentLoaded', () => {
    console.log('[Bundles] init');
    loadBundles();
    checks.forEach(c => c.addEventListener('change', calcTotals));
    document.getElementById('fmPrice').addEventListener('input', calcDiscount);
});

// 번들 목록 로드
async function loadBundles() {
    console.log('[Bundles] loading list');
    try {
        const res = await fetch(BUNDLE_URL, {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
            body: JSON.stringify({action:'list'})
        });
        const data = await res.json();
        if (!data.success) return;

        if (!data.bundles.length) {
            bundleList.classList.add('hidden');
            emptyState.classList.remove('hidden');
            return;
        }

        emptyState.classList.add('hidden');
        bundleList.classList.remove('hidden');
        bundleList.innerHTML = data.bundles.map(b => renderCard(b)).join('');
        console.log('[Bundles] loaded', data.bundles.length, 'bundles');
    } catch(e) {
        console.error('[Bundles] load error', e);
    }
}

function renderCard(b) {
    const origTotal = parseFloat(b.original_total) || 0;
    const bPrice = parseFloat(b.bundle_price) || 0;
    const discount = origTotal > 0 && bPrice < origTotal ? Math.round((1 - bPrice/origTotal)*100) : 0;
    const statusCls = parseInt(b.is_active) ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400';
    const statusText = parseInt(b.is_active) ? LABELS.active : LABELS.inactive;

    return `
    <div class="bg-white dark:bg-zinc-800 rounded-xl border dark:border-zinc-700 p-4 hover:shadow-md transition-shadow">
        <div class="flex items-start justify-between">
            <div class="flex-1 min-w-0">
                <div class="flex items-center gap-2 mb-1">
                    <h3 class="font-semibold text-zinc-900 dark:text-white truncate">${escHtml(b.name)}</h3>
                    <span class="px-2 py-0.5 rounded-full text-xs font-medium ${statusCls}">${statusText}</span>
                    ${discount > 0 ? `<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">-${discount}%</span>` : ''}
                </div>
                ${b.description ? `<p class="text-sm text-zinc-500 dark:text-zinc-400 mb-2">${escHtml(b.description)}</p>` : ''}
                <p class="text-xs text-zinc-400 dark:text-zinc-500">${b.service_names || '-'}</p>
            </div>
            <div class="text-right ml-4 flex-shrink-0">
                <div class="text-lg font-bold text-blue-600 dark:text-blue-400">${numberFmt(bPrice)} ${CURRENCY}</div>
                ${origTotal > bPrice ? `<div class="text-xs text-zinc-400 line-through">${numberFmt(origTotal)} ${CURRENCY}</div>` : ''}
                <div class="text-xs text-zinc-400 mt-1">${b.item_count}${LABELS.services}</div>
            </div>
        </div>
        <div class="flex items-center gap-2 mt-3 pt-3 border-t dark:border-zinc-700">
            <button onclick="editBundle('${b.id}')" class="px-3 py-1.5 text-sm text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg">${LABELS.edit}</button>
            <button onclick="toggleBundle('${b.id}')" class="px-3 py-1.5 text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700 rounded-lg">
                ${parseInt(b.is_active) ? '⏸ ' + LABELS.inactive : '▶ ' + LABELS.active}
            </button>
            <button onclick="deleteBundle('${b.id}','${escHtml(b.name)}')" class="px-3 py-1.5 text-sm text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg ml-auto">🗑</button>
        </div>
    </div>`;
}

// 폼 열기/닫기
function openForm(id) {
    document.getElementById('fmId').value = '';
    document.getElementById('fmName').value = '';
    document.getElementById('fmDesc').value = '';
    document.getElementById('fmPrice').value = '';
    document.getElementById('fmOrder').value = '0';
    document.getElementById('fmActive').value = '1';
    checks.forEach(c => c.checked = false);
    calcTotals();
    modalTitle.textContent = LABELS.create;
    modal.classList.remove('hidden');
    console.log('[Bundles] form opened', id ? 'edit' : 'create');
}

function closeForm() {
    modal.classList.add('hidden');
}

// 번들 수정
async function editBundle(id) {
    console.log('[Bundles] edit', id);
    openForm(id);
    modalTitle.textContent = LABELS.edit;

    const res = await fetch(BUNDLE_URL, {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
        body: JSON.stringify({action:'get', id})
    });
    const data = await res.json();
    if (!data.success) return;

    const b = data.bundle;
    document.getElementById('fmId').value = b.id;
    document.getElementById('fmName').value = b.name;
    document.getElementById('fmDesc').value = b.description || '';
    document.getElementById('fmPrice').value = b.bundle_price;
    document.getElementById('fmOrder').value = b.display_order;
    document.getElementById('fmActive').value = b.is_active;

    const itemIds = b.items.map(i => String(i.service_id));
    checks.forEach(c => c.checked = itemIds.includes(c.value));
    calcTotals();
    calcDiscount();
}

// 저장
async function saveBundle() {
    const serviceIds = [...checks].filter(c => c.checked).map(c => c.value);
    const payload = {
        action: 'save',
        id: document.getElementById('fmId').value,
        name: document.getElementById('fmName').value.trim(),
        description: document.getElementById('fmDesc').value.trim(),
        bundle_price: parseFloat(document.getElementById('fmPrice').value) || 0,
        display_order: parseInt(document.getElementById('fmOrder').value) || 0,
        is_active: parseInt(document.getElementById('fmActive').value),
        service_ids: serviceIds
    };
    console.log('[Bundles] save', payload);

    const res = await fetch(BUNDLE_URL, {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
        body: JSON.stringify(payload)
    });
    const data = await res.json();
    if (data.success) {
        closeForm();
        loadBundles();
    } else {
        alert(data.message || 'Error');
    }
}

// 삭제
async function deleteBundle(id, name) {
    if (!confirm(LABELS.confirmDelete.replace(':name', name))) return;
    console.log('[Bundles] delete', id);
    await fetch(BUNDLE_URL, {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
        body: JSON.stringify({action:'delete', id})
    });
    loadBundles();
}

// 토글
async function toggleBundle(id) {
    console.log('[Bundles] toggle', id);
    await fetch(BUNDLE_URL, {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
        body: JSON.stringify({action:'toggle', id})
    });
    loadBundles();
}

// 서비스 체크 합계
function calcTotals() {
    let price = 0, dur = 0;
    checks.forEach(c => {
        if (c.checked) {
            price += parseFloat(c.dataset.price) || 0;
            dur += parseInt(c.dataset.duration) || 0;
        }
    });
    document.getElementById('fmOrigPrice').textContent = numberFmt(price);
    document.getElementById('fmTotalDur').textContent = dur;
    calcDiscount();
}

function calcDiscount() {
    const origPrice = parseFloat(document.getElementById('fmOrigPrice').textContent.replace(/,/g,'')) || 0;
    const bundlePrice = parseFloat(document.getElementById('fmPrice').value) || 0;
    const el = document.getElementById('fmDiscount');
    if (origPrice > 0 && bundlePrice > 0 && bundlePrice < origPrice) {
        const pct = Math.round((1 - bundlePrice/origPrice)*100);
        el.textContent = LABELS.discount.replace(':percent', pct);
        el.classList.remove('hidden');
    } else {
        el.classList.add('hidden');
    }
}

function numberFmt(n) { return Number(n).toLocaleString(); }
function escHtml(s) { const d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
</script>
