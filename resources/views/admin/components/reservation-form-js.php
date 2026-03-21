<script>
/**
 * 예약 생성 폼 공용 JS
 * $resForm['formId']를 기반으로 모든 요소 ID에 접두사 사용
 */
(function() {
    const fId = '<?= $resForm['formId'] ?>';
    const CURRENCY = { symbol: '<?= $resForm['currencySymbol'] ?>', position: '<?= $resForm['currencyPosition'] ?>' };

    function fmtCurrency(amount) {
        const f = Number(amount).toLocaleString();
        return CURRENCY.position === 'suffix' ? f + CURRENCY.symbol : CURRENCY.symbol + f;
    }

    const form = document.getElementById(fId);
    if (!form) return;

    const cards = form.querySelectorAll('.rf-card');
    const checks = form.querySelectorAll('.rf-check');
    const searchInput = document.getElementById(fId + '_svcSearch');
    const countEl = document.getElementById(fId + '_selectedCount');
    const summaryEl = document.getElementById(fId + '_summary');
    const durationEl = document.getElementById(fId + '_totalDuration');
    const priceEl = document.getElementById(fId + '_totalPrice');
    const startInput = document.getElementById(fId + '_startTime');
    const endInput = document.getElementById(fId + '_endTime');
    const submitBtn = document.getElementById(fId + '_submitBtn');

    console.log('[ResForm] Initialized:', fId, 'services:', cards.length);

    // 카드 클릭 → 체크 토글
    cards.forEach(card => {
        card.addEventListener('click', function(e) {
            if (e.target.tagName === 'INPUT') return;
            const cb = this.querySelector('.rf-check');
            cb.checked = !cb.checked;
            styleCard(this, cb.checked);
            recalc();
            console.log('[ResForm] Toggled:', cb.value, cb.checked);
        });
    });

    checks.forEach(cb => {
        cb.addEventListener('change', function() {
            styleCard(this.closest('.rf-card'), this.checked);
            recalc();
        });
    });

    function styleCard(card, on) {
        const circle = card.querySelector('.rounded-full');
        const icon = circle ? circle.querySelector('svg') : null;
        const overlay = card.querySelector('.rf-overlay');
        if (on) {
            card.classList.remove('border-zinc-200', 'dark:border-zinc-700');
            card.classList.add('border-blue-500', 'ring-2', 'ring-blue-500/30');
            if (circle) {
                circle.classList.remove('border-white/70', 'bg-black/20');
                circle.classList.add('border-blue-500', 'bg-blue-500');
            }
            if (icon) icon.classList.remove('hidden');
            if (overlay) overlay.classList.remove('hidden');
        } else {
            card.classList.add('border-zinc-200', 'dark:border-zinc-700');
            card.classList.remove('border-blue-500', 'ring-2', 'ring-blue-500/30');
            if (circle) {
                circle.classList.add('border-white/70', 'bg-black/20');
                circle.classList.remove('border-blue-500', 'bg-blue-500');
            }
            if (icon) icon.classList.add('hidden');
            if (overlay) overlay.classList.add('hidden');
        }
    }

    function recalc() {
        let price = 0, dur = 0, cnt = 0;
        checks.forEach(cb => {
            if (cb.checked) {
                price += parseFloat(cb.dataset.price || 0);
                dur += parseInt(cb.dataset.duration || 0);
                cnt++;
            }
        });
        countEl.textContent = cnt + '개 선택';
        if (cnt > 0) {
            summaryEl.classList.remove('hidden');
            durationEl.textContent = dur + '분';
            priceEl.textContent = fmtCurrency(price);
            submitBtn.disabled = false;
            calcEnd(dur);
        } else {
            summaryEl.classList.add('hidden');
            submitBtn.disabled = true;
        }
    }

    function calcEnd(duration) {
        const start = startInput.value;
        if (!start) return;
        const [h, m] = start.split(':').map(Number);
        const endMin = h * 60 + m + duration;
        endInput.value = String(Math.floor(endMin / 60) % 24).padStart(2, '0') + ':' +
                         String(endMin % 60).padStart(2, '0');
    }

    startInput.addEventListener('change', function() {
        let dur = 0;
        checks.forEach(cb => { if (cb.checked) dur += parseInt(cb.dataset.duration || 0); });
        if (dur > 0) calcEnd(dur);
    });

    // 카테고리 필터
    let activeCat = '';
    const catFilter = document.getElementById(fId + '_catFilter');
    if (catFilter) {
        catFilter.querySelectorAll('.rf-cat-btn').forEach(btn => {
            btn.addEventListener('click', function() {
                activeCat = this.dataset.cat || '';
                // 버튼 스타일 토글
                catFilter.querySelectorAll('.rf-cat-btn').forEach(b => {
                    b.classList.remove('bg-blue-600', 'text-white');
                    b.classList.add('bg-zinc-100', 'dark:bg-zinc-700', 'text-zinc-600', 'dark:text-zinc-300');
                });
                this.classList.add('bg-blue-600', 'text-white');
                this.classList.remove('bg-zinc-100', 'dark:bg-zinc-700', 'text-zinc-600', 'dark:text-zinc-300');
                filterCards();
                console.log('[ResForm] Category filter:', activeCat || 'all');
            });
        });
    }

    // 검색 + 카테고리 통합 필터
    function filterCards() {
        const q = searchInput ? searchInput.value.toLowerCase().trim() : '';
        cards.forEach(card => {
            const matchCat = !activeCat || (card.dataset.cat || '') === activeCat;
            const matchSearch = !q || (card.dataset.name || '').includes(q);
            card.style.display = (matchCat && matchSearch) ? '' : 'none';
        });
    }

    if (searchInput) {
        searchInput.addEventListener('input', filterCards);
    }

    // 폼 제출 검증 + 더블클릭 방지
    form.addEventListener('submit', function(e) {
        const checked = form.querySelectorAll('.rf-check:checked');
        if (checked.length === 0) {
            e.preventDefault();
            alert('서비스를 1개 이상 선택해주세요.');
            return;
        }
        // 더블 클릭 방지
        submitBtn.disabled = true;
        submitBtn.textContent = '처리중...';
        console.log('[ResForm] Submit:', fId, 'services:', checked.length);
    });

    // ─── 고객 검색 자동완성 ───
    const nameInput = document.getElementById(fId + '_name');
    const nameDropdown = document.getElementById(fId + '_nameDropdown');
    const phoneInput = document.getElementById(fId + '_phone');
    const emailInput = form.querySelector('input[name="customer_email"]');
    const userIdInput = document.getElementById(fId + '_userId');
    let searchTimer = null;
    const SEARCH_URL = '<?= $resForm['adminUrl'] ?>/reservations/search-customers';

    if (nameInput && nameDropdown) {
        nameInput.addEventListener('input', function() {
            clearTimeout(searchTimer);
            // 수동 입력 시 user_id 초기화 (검색 선택 시에만 설정됨)
            if (userIdInput) userIdInput.value = '';
            const q = this.value.trim();
            if (q.length < 1) { nameDropdown.classList.add('hidden'); return; }
            searchTimer = setTimeout(async () => {
                try {
                    console.log('[ResForm] Customer search:', q);
                    const resp = await fetch(SEARCH_URL + '?q=' + encodeURIComponent(q));
                    const data = await resp.json();
                    if (!data.success || !data.customers.length) {
                        nameDropdown.classList.add('hidden');
                        return;
                    }
                    nameDropdown.innerHTML = data.customers.map(c => {
                        const ph = c.phone || '';
                        const em = c.email || '';
                        const uid = c.id || '';
                        return `<div class="rf-ac-item px-3 py-2 cursor-pointer hover:bg-blue-50 dark:hover:bg-blue-900/20 transition text-sm" data-name="${escAttr(c.name)}" data-phone="${escAttr(ph)}" data-email="${escAttr(em)}" data-uid="${escAttr(uid)}">
                            <span class="font-medium text-zinc-900 dark:text-white">${escHtml(c.name)}</span>
                            ${ph ? '<span class="ml-2 text-zinc-400 text-xs">' + escHtml(ph) + '</span>' : ''}
                            ${em ? '<span class="ml-2 text-zinc-400 text-xs">' + escHtml(em) + '</span>' : ''}
                        </div>`;
                    }).join('');
                    nameDropdown.classList.remove('hidden');
                    // 클릭 바인딩
                    nameDropdown.querySelectorAll('.rf-ac-item').forEach(item => {
                        item.addEventListener('click', function() {
                            nameInput.value = this.dataset.name || '';
                            if (phoneInput) phoneInput.value = this.dataset.phone || '';
                            if (emailInput) emailInput.value = this.dataset.email || '';
                            if (userIdInput) userIdInput.value = this.dataset.uid || '';
                            nameDropdown.classList.add('hidden');
                            console.log('[ResForm] Customer selected:', this.dataset.name, 'userId:', this.dataset.uid);
                        });
                    });
                } catch (e) {
                    console.error('[ResForm] Customer search error:', e);
                    nameDropdown.classList.add('hidden');
                }
            }, 300);
        });

        // 외부 클릭 시 드롭다운 닫기
        document.addEventListener('click', function(e) {
            if (!nameInput.contains(e.target) && !nameDropdown.contains(e.target)) {
                nameDropdown.classList.add('hidden');
            }
        });
    }

    function escHtml(s) { const d = document.createElement('div'); d.textContent = s || ''; return d.innerHTML; }
    function escAttr(s) { return (s || '').replace(/"/g, '&quot;').replace(/</g, '&lt;').replace(/>/g, '&gt;'); }

    // 초기화
    recalc();

    // 외부에서 폼 리셋 시 사용할 전역 함수
    window['resetResForm_' + fId] = function(date) {
        checks.forEach(cb => {
            cb.checked = false;
            styleCard(cb.closest('.rf-card'), false);
        });
        const dateInput = document.getElementById(fId + '_date');
        if (dateInput && date) dateInput.value = date;
        const nameInput = document.getElementById(fId + '_name');
        const phoneInput = document.getElementById(fId + '_phone');
        if (nameInput) nameInput.value = '';
        if (phoneInput) phoneInput.value = '';
        if (userIdInput) userIdInput.value = '';
        startInput.value = '09:00';
        endInput.value = '10:00';
        form.querySelectorAll('textarea').forEach(t => t.value = '');
        form.querySelectorAll('input[type="email"]').forEach(t => t.value = '');
        if (searchInput) searchInput.value = '';
        activeCat = '';
        if (catFilter) {
            catFilter.querySelectorAll('.rf-cat-btn').forEach((b, i) => {
                if (i === 0) { b.classList.add('bg-blue-600', 'text-white'); b.classList.remove('bg-zinc-100', 'dark:bg-zinc-700', 'text-zinc-600', 'dark:text-zinc-300'); }
                else { b.classList.remove('bg-blue-600', 'text-white'); b.classList.add('bg-zinc-100', 'dark:bg-zinc-700', 'text-zinc-600', 'dark:text-zinc-300'); }
            });
        }
        cards.forEach(c => c.style.display = '');
        recalc();
        console.log('[ResForm] Reset:', fId, 'date:', date);
    };
    // 폼 리셋 시 스태프도 초기화
    const origReset = window['resetResForm_' + fId];
    window['resetResForm_' + fId] = function(date) {
        origReset(date);
        if (window.ResFormStaff) ResFormStaff.clear(fId);
    };
})();

// 예약 경로 선택
function ResFormSource(formId, source) {
    document.getElementById(formId + '_source').value = source;
    var colors = { phone: 'blue', walk_in: 'emerald', online: 'violet' };
    ['phone', 'walk_in', 'online'].forEach(function(k) {
        var btn = document.getElementById(formId + '_src_' + k);
        if (!btn) return;
        var c = colors[k];
        if (k === source) {
            btn.className = btn.className.replace(/border-zinc-200 dark:border-zinc-700 text-zinc-500 dark:text-zinc-400 hover:border-zinc-300 dark:hover:border-zinc-600/, '');
            btn.classList.add('border-' + c + '-500', 'bg-' + c + '-50', 'text-' + c + '-700', 'dark:bg-' + c + '-900/20', 'dark:text-' + c + '-400');
            btn.classList.remove('border-zinc-200', 'dark:border-zinc-700', 'text-zinc-500', 'dark:text-zinc-400', 'hover:border-zinc-300', 'dark:hover:border-zinc-600');
        } else {
            btn.classList.remove('border-' + c + '-500', 'bg-' + c + '-50', 'text-' + c + '-700', 'dark:bg-' + c + '-900/20', 'dark:text-' + c + '-400');
            btn.classList.add('border-zinc-200', 'dark:border-zinc-700', 'text-zinc-500', 'dark:text-zinc-400', 'hover:border-zinc-300', 'dark:hover:border-zinc-600');
        }
    });
    console.log('[ResForm] source changed:', source);
}
</script>
<?php include __DIR__ . '/reservation-form-staff-js.php'; ?>
