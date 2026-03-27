<script>
const BUNDLE_ID = '<?= $bundleId ?>';
const PAGE_URL = window.location.href;
const ADMIN_URL = '<?= $adminUrl ?>';
const BASE_URL = '<?= $baseUrl ?>';
const CURRENCY = '<?= $currency ?>';

document.addEventListener('DOMContentLoaded', () => {
    console.log('[BundleEdit] init', BUNDLE_ID);

    // 이미지 업로드
    document.getElementById('imageInput').addEventListener('change', uploadImage);

    // 이벤트 토글
    document.getElementById('eventToggle').addEventListener('change', function() {
        document.getElementById('eventFields').classList.toggle('hidden', !this.checked);
        console.log('[BundleEdit] event toggle', this.checked);
    });

    // 이벤트 할인율 계산
    document.getElementById('fmEventPrice').addEventListener('input', calcEventDiscount);
    document.getElementById('fmPrice').addEventListener('input', calcEventDiscount);
    calcEventDiscount();

    // 스태프 체크박스 스타일
    document.querySelectorAll('.staff-check').forEach(cb => {
        cb.addEventListener('change', function() {
            const label = this.closest('label');
            if (this.checked) {
                label.classList.add('border-blue-400','bg-blue-50','dark:bg-blue-900/20','dark:border-blue-600');
                label.classList.remove('border-zinc-200','dark:border-zinc-700');
            } else {
                label.classList.remove('border-blue-400','bg-blue-50','dark:bg-blue-900/20','dark:border-blue-600');
                label.classList.add('border-zinc-200','dark:border-zinc-700');
            }
        });
    });

    // 서비스 검색
    document.getElementById('svcSearch').addEventListener('input', function() {
        const q = this.value.toLowerCase();
        document.querySelectorAll('.svc-picker-item').forEach(el => {
            el.style.display = el.dataset.name.includes(q) ? '' : 'none';
        });
    });
});

// 이미지 업로드
async function uploadImage(e) {
    const file = e.target.files[0];
    if (!file) return;
    console.log('[BundleEdit] uploading image', file.name);

    const fd = new FormData();
    fd.append('bundle_image', file);

    try {
        const res = await fetch(PAGE_URL, { method: 'POST', body: fd });
        const data = await res.json();
        if (data.success) {
            const img = document.getElementById('previewImg');
            img.src = data.url;
            img.classList.remove('hidden');
            const placeholder = document.getElementById('noImagePlaceholder');
            if (placeholder) placeholder.classList.add('hidden');
            showResultModal(true, '<?= __("bundles.image_uploaded") ?>');
        } else {
            showResultModal(false, data.message);
        }
    } catch(err) {
        console.error('[BundleEdit] upload error', err);
    }
}

// 이미지 삭제
async function removeImage() {
    if (!confirm('<?= __("bundles.remove_image_confirm") ?>')) return;
    const res = await fetch(PAGE_URL, {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
        body: JSON.stringify({ action: 'remove_image' })
    });
    const data = await res.json();
    if (data.success) location.reload();
}

// 이벤트 할인율 표시
function calcEventDiscount() {
    const basePrice = parseFloat(document.getElementById('fmPrice').value) || 0;
    const eventPrice = parseFloat(document.getElementById('fmEventPrice').value) || 0;
    const el = document.getElementById('eventDiscountInfo');
    if (basePrice > 0 && eventPrice > 0 && eventPrice < basePrice) {
        const pct = Math.round((1 - eventPrice / basePrice) * 100);
        const diff = basePrice - eventPrice;
        el.textContent = `${pct}% <?= __("bundles.discount_label") ?> (-${numberFmt(diff)} ${CURRENCY})`;
        el.classList.remove('hidden');
    } else {
        el.textContent = '';
    }
}

// 서비스 추가 모달
function openServicePicker() {
    const existingIds = [...document.querySelectorAll('.svc-item')].map(el => el.dataset.id);
    document.querySelectorAll('.svc-picker-check').forEach(cb => {
        cb.checked = false;
        cb.closest('.svc-picker-item').style.display = existingIds.includes(cb.value) ? 'none' : '';
    });
    document.getElementById('svcSearch').value = '';
    document.getElementById('svcPickerModal').classList.remove('hidden');
    console.log('[BundleEdit] service picker opened');
}

function closeServicePicker() {
    document.getElementById('svcPickerModal').classList.add('hidden');
}

function addSelectedServices() {
    const list = document.getElementById('serviceList');
    const emptyMsg = list.querySelector('.text-center');
    if (emptyMsg) emptyMsg.remove();

    document.querySelectorAll('.svc-picker-check:checked').forEach(cb => {
        const imgSrc = cb.dataset.image ? `${BASE_URL}/${cb.dataset.image.replace(/^\/+/, '')}` : '';
        const html = `
        <div class="flex items-center gap-3 p-3 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 svc-item" data-id="${cb.value}">
            <svg class="w-5 h-5 text-zinc-300 cursor-grab drag-handle" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 8h16M4 16h16"/></svg>
            ${imgSrc ? `<img src="${imgSrc}" class="w-10 h-10 rounded object-cover flex-shrink-0">` :
            `<div class="w-10 h-10 rounded bg-zinc-100 dark:bg-zinc-700 flex items-center justify-center flex-shrink-0"><svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M13 10V3L4 14h7v7l9-11h-7z"/></svg></div>`}
            <div class="flex-1 min-w-0">
                <p class="text-sm font-medium text-zinc-900 dark:text-white truncate">${escHtml(cb.dataset.name)}</p>
                <p class="text-xs text-zinc-500">${numberFmt(cb.dataset.price)} ${CURRENCY} · ${cb.dataset.duration}<?= __("bundles.min") ?></p>
            </div>
            <button onclick="removeService('${cb.value}')" class="p-1 text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>`;
        list.insertAdjacentHTML('beforeend', html);
    });

    updateSvcCount();
    closeServicePicker();
    updateOrigPrice();
    console.log('[BundleEdit] services added');
}

function removeService(id) {
    const el = document.querySelector(`.svc-item[data-id="${id}"]`);
    if (el) el.remove();
    updateSvcCount();
    updateOrigPrice();
    console.log('[BundleEdit] service removed', id);
}

function updateSvcCount() {
    document.getElementById('svcCount').textContent = document.querySelectorAll('.svc-item').length;
}

function updateOrigPrice() {
    // 서비스 picker 데이터로 계산
    const svcIds = [...document.querySelectorAll('.svc-item')].map(el => el.dataset.id);
    let total = 0;
    document.querySelectorAll('.svc-picker-check').forEach(cb => {
        if (svcIds.includes(cb.value)) total += parseFloat(cb.dataset.price) || 0;
    });
    document.getElementById('origPriceDisplay').textContent = numberFmt(total) + ' ' + CURRENCY;
}

// 전체 저장
async function saveAll() {
    const serviceIds = [...document.querySelectorAll('.svc-item')].map(el => el.dataset.id);
    const staffIds = [...document.querySelectorAll('.staff-check:checked')].map(cb => cb.value);
    const eventEnabled = document.getElementById('eventToggle').checked;

    const payload = {
        action: 'save',
        name: document.getElementById('fmName').value.trim(),
        description: document.getElementById('fmDesc').value.trim(),
        bundle_price: parseFloat(document.getElementById('fmPrice').value) || 0,
        display_order: parseInt(document.getElementById('fmOrder').value) || 0,
        is_active: document.getElementById('fmActive').checked ? 1 : 0,
        service_ids: serviceIds,
        staff_ids: staffIds,
        event_enabled: eventEnabled,
        event_price: eventEnabled ? parseFloat(document.getElementById('fmEventPrice').value) || 0 : null,
        event_start: eventEnabled ? document.getElementById('fmEventStart').value : null,
        event_end: eventEnabled ? document.getElementById('fmEventEnd').value : null,
        event_label: eventEnabled ? document.getElementById('fmEventLabel').value.trim() : null
    };

    console.log('[BundleEdit] saving', payload);
    const btn = document.getElementById('btnSaveAll');
    btn.disabled = true;
    btn.textContent = '<?= __("bundles.saving") ?>...';

    try {
        const res = await fetch(PAGE_URL, {
            method: 'POST',
            headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
            body: JSON.stringify(payload)
        });
        const data = await res.json();
        if (data.success) {
            showResultModal(true, data.message);
        } else {
            showResultModal(false, data.message || 'Error');
        }
    } catch(e) {
        console.error('[BundleEdit] save error', e);
        showResultModal(false, e.message);
    } finally {
        btn.disabled = false;
        btn.innerHTML = '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg> <?= __("bundles.save") ?>';
    }
}

// 번들 삭제
async function deleteBundle() {
    if (!confirm('<?= __("bundles.confirm_delete_page") ?>')) return;
    console.log('[BundleEdit] deleting bundle');
    const res = await fetch(PAGE_URL, {
        method: 'POST',
        headers: {'Content-Type':'application/json','X-Requested-With':'XMLHttpRequest'},
        body: JSON.stringify({ action: 'delete' })
    });
    const data = await res.json();
    if (data.success && data.redirect) {
        window.location.href = ADMIN_URL + '/bundles';
    }
}

// 메시지 표시
function showMsg(type, msg) {
    const area = document.getElementById('msgArea');
    const cls = type === 'success'
        ? 'bg-green-50 dark:bg-green-900/30 text-green-800 dark:text-green-300 border-green-200 dark:border-green-800'
        : 'bg-red-50 dark:bg-red-900/30 text-red-800 dark:text-red-300 border-red-200 dark:border-red-800';
    area.innerHTML = `<div class="mb-4 p-3 rounded-lg border ${cls} text-sm">${escHtml(msg)}</div>`;
    setTimeout(() => area.innerHTML = '', 4000);
}

function numberFmt(n) { return Number(n).toLocaleString(); }
function escHtml(s) { const d = document.createElement('div'); d.textContent = s; return d.innerHTML; }
</script>
