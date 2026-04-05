<script>
const BUNDLE_URL = window.location.href;
const CURRENCY = '<?= $currency ?>';
const ADMIN_URL = '<?= $adminUrl ?>';
const BASE_URL = '<?= $baseUrl ?>';
const LABELS = {
    create: '<?= __("bundles.create") ?>',
    edit: '<?= __("bundles.edit") ?>',
    manage: '<?= __("bundles.manage") ?>',
    confirmDelete: '<?= __("bundles.confirm_delete") ?>',
    discount: '<?= __("bundles.discount_info") ?>',
    services: '<?= __("bundles.services_count") ?>',
    servicesLabel: '<?= __("bundles.services_label") ?>',
    active: '<?= __("bundles.active") ?>',
    inactive: '<?= __("bundles.inactive") ?>',
    min: '<?= __("bundles.min") ?>',
    eventActive: '<?= __("bundles.event_active") ?>'
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
    const totalDur = parseInt(b.total_duration) || 0;
    const discount = origTotal > 0 && bPrice < origTotal ? Math.round((1 - bPrice/origTotal)*100) : 0;
    const statusCls = parseInt(b.is_active) ? 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400' : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400';
    const statusText = parseInt(b.is_active) ? LABELS.active : LABELS.inactive;

    // 이벤트 할인 체크
    const now = new Date();
    const hasEvent = b.event_price && b.event_start && b.event_end;
    const eventActive = hasEvent && new Date(b.event_start) <= now && new Date(b.event_end) >= now;
    const eventPrice = parseFloat(b.event_price) || 0;
    const displayPrice = eventActive ? eventPrice : bPrice;

    // 서비스 이름 분리
    const svcNames = (b.service_names || '').split(', ').filter(Boolean);

    return `
    <div class="bg-white dark:bg-zinc-800 rounded-xl border dark:border-zinc-700 overflow-hidden hover:shadow-lg transition-shadow group flex flex-col h-full">
        <!-- 이미지 헤더 -->
        <div class="relative h-36 bg-gradient-to-br from-blue-500 to-indigo-600 overflow-hidden">
            ${b.image ? `<img src="${b.image.startsWith('http') ? escHtml(b.image) : BASE_URL + '/' + escHtml(b.image).replace(/^\/+/, '')}" class="w-full h-full object-cover">` : `
            <div class="absolute inset-0 flex items-center justify-center">
                <svg class="w-16 h-16 text-white/30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
            </div>`}
            <!-- 상태 배지 -->
            <div class="absolute top-2 left-2 flex items-center gap-1.5">
                <span class="px-2 py-0.5 rounded-full text-xs font-medium ${statusCls} backdrop-blur-sm">${statusText}</span>
                ${eventActive ? `<span class="px-2 py-0.5 rounded-full text-xs font-medium bg-orange-100 text-orange-700 dark:bg-orange-900/40 dark:text-orange-300 backdrop-blur-sm">${escHtml(b.event_label || LABELS.eventActive)}</span>` : ''}
                ${discount > 0 ? `<span class="px-2 py-0.5 rounded-full text-xs font-bold bg-red-500 text-white">-${discount}%</span>` : ''}
            </div>
            <!-- 관리 버튼 (호버) -->
            <a href="${ADMIN_URL}/bundles/${b.id}" class="absolute top-2 right-2 px-2.5 py-1 bg-white/90 dark:bg-zinc-800/90 text-xs font-medium text-blue-600 dark:text-blue-400 rounded-lg opacity-0 group-hover:opacity-100 transition-opacity backdrop-blur-sm hover:bg-white">
                ${LABELS.manage} →
            </a>
        </div>
        <!-- 본문 -->
        <div class="p-4 flex-1">
            <h3 class="font-semibold text-zinc-900 dark:text-white mb-1 truncate">${escHtml(b.name)}</h3>
            ${b.description ? `<p class="text-sm text-zinc-500 dark:text-zinc-400 mb-3 line-clamp-2">${escHtml(stripHtml(b.description))}</p>` : '<div class="mb-3"></div>'}
            <!-- 가격 -->
            <div class="flex items-baseline gap-2 mb-3">
                <span class="text-xl font-bold ${eventActive ? 'text-orange-600 dark:text-orange-400' : 'text-blue-600 dark:text-blue-400'}">${numberFmt(displayPrice)}</span>
                <span class="text-sm text-zinc-400">${CURRENCY}</span>
                ${(eventActive && eventPrice < bPrice) ? `<span class="text-sm text-zinc-400 line-through ml-1">${numberFmt(bPrice)}</span>` : ''}
                ${(!eventActive && origTotal > bPrice) ? `<span class="text-sm text-zinc-400 line-through ml-1">${numberFmt(origTotal)}</span>` : ''}
            </div>
            <!-- 서비스 태그 -->
            <div class="flex flex-wrap gap-1 mb-3">
                ${svcNames.slice(0, 4).map(n => `<span class="px-2 py-0.5 bg-zinc-100 dark:bg-zinc-700 text-xs text-zinc-600 dark:text-zinc-300 rounded">${escHtml(n)}</span>`).join('')}
                ${svcNames.length > 4 ? `<span class="px-2 py-0.5 bg-zinc-100 dark:bg-zinc-700 text-xs text-zinc-400 rounded">+${svcNames.length - 4}</span>` : ''}
            </div>
            <!-- 정보 -->
            <div class="flex items-center text-xs text-zinc-400 gap-3">
                <span>${b.item_count}${LABELS.services}</span>
                <span>${totalDur}${LABELS.min}</span>
            </div>
        </div>
        <!-- 하단 액션 -->
        <div class="flex items-center border-t dark:border-zinc-700 divide-x dark:divide-zinc-700">
            <a href="${ADMIN_URL}/bundles/${b.id}" class="flex-1 py-2.5 text-center text-sm font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition">${LABELS.manage}</a>
            <button onclick="event.preventDefault();toggleBundle('${b.id}')" class="py-2.5 px-4 text-center text-sm text-zinc-500 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">
                ${parseInt(b.is_active) ? '⏸' : '▶'}
            </button>
            <button onclick="event.preventDefault();deleteBundle('${b.id}','${escHtml(b.name)}')" class="py-2.5 px-4 text-center text-sm text-red-500 hover:bg-red-50 dark:hover:bg-red-900/20 transition">🗑</button>
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
function stripHtml(s) { return s.replace(/<[^>]*>/g, '').trim(); }
function escHtml(s) { const d=document.createElement('div'); d.textContent=s; return d.innerHTML; }
</script>
