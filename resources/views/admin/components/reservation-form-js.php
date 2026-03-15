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
        const icon = circle.querySelector('svg');
        if (on) {
            card.classList.remove('border-zinc-200', 'dark:border-zinc-700');
            card.classList.add('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
            circle.classList.remove('border-zinc-300', 'dark:border-zinc-600');
            circle.classList.add('border-blue-500', 'bg-blue-500');
            icon.classList.remove('hidden');
        } else {
            card.classList.add('border-zinc-200', 'dark:border-zinc-700');
            card.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
            circle.classList.add('border-zinc-300', 'dark:border-zinc-600');
            circle.classList.remove('border-blue-500', 'bg-blue-500');
            icon.classList.add('hidden');
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

    // 검색
    if (searchInput) {
        searchInput.addEventListener('input', function() {
            const q = this.value.toLowerCase().trim();
            cards.forEach(card => {
                card.style.display = (!q || (card.dataset.name || '').includes(q)) ? '' : 'none';
            });
        });
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
        startInput.value = '09:00';
        endInput.value = '10:00';
        form.querySelectorAll('textarea').forEach(t => t.value = '');
        form.querySelectorAll('input[type="email"]').forEach(t => t.value = '');
        if (searchInput) searchInput.value = '';
        cards.forEach(c => c.style.display = '');
        recalc();
        console.log('[ResForm] Reset:', fId, 'date:', date);
    };
})();
</script>
