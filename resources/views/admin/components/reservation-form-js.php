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
        // 지명료 추가
        var designFeeEl = document.getElementById(fId + '_designationFee');
        var designFee = designFeeEl ? parseFloat(designFeeEl.value || 0) : 0;
        var totalWithFee = price + designFee;

        countEl.textContent = cnt + '<?= __('common.unit.items') ?? '개' ?> <?= __('common.selected') ?? '선택' ?>';
        var listEl = document.getElementById(fId + '_selectedList');
        if (cnt > 0) {
            summaryEl.classList.remove('hidden');
            durationEl.textContent = dur + '<?= __('common.unit.minutes') ?? '분' ?>';
            priceEl.textContent = fmtCurrency(totalWithFee);
            submitBtn.disabled = false;
            calcEnd(dur);
            // 선택 항목 리스트 업데이트
            if (listEl) {
                var html = '';
                checks.forEach(function(cb) {
                    if (cb.checked) {
                        var card = cb.closest('.rf-card');
                        var name = card ? (card.querySelector('.text-sm.font-bold')?.textContent || cb.value) : cb.value;
                        var p = parseFloat(cb.dataset.price || 0);
                        var d = parseInt(cb.dataset.duration || 0);
                        html += '<div class="flex items-center justify-between text-xs py-1">' +
                            '<span class="text-zinc-700 dark:text-zinc-300 truncate flex-1">' + name + ' <span class="text-zinc-400">' + d + '<?= __('common.unit.minutes') ?? '분' ?></span></span>' +
                            '<span class="text-zinc-600 dark:text-zinc-400 font-medium ml-2">' + fmtCurrency(p) + '</span></div>';
                    }
                });
                // 지명료 행 추가
                if (designFee > 0) {
                    var staffNameEl = document.getElementById(fId + '_staffName');
                    var staffName = staffNameEl ? staffNameEl.textContent : '';
                    html += '<div class="flex items-center justify-between text-xs py-1 text-violet-600 dark:text-violet-400">' +
                        '<span class="truncate flex-1">🏷 <?= __('reservations.designation_fee') ?? '지명료' ?>' + (staffName ? ' (' + staffName + ')' : '') + '</span>' +
                        '<span class="font-medium ml-2">+' + fmtCurrency(designFee) + '</span></div>';
                }
                listEl.innerHTML = html;
            }
            // 번들 할인 표시
            var discountEl = document.getElementById(fId + '_bundleDiscount');
            var activeBundle = window['_rfActiveBundle_' + fId];
            if (discountEl && activeBundle && activeBundle.price > 0 && activeBundle.price < price) {
                discountEl.classList.remove('hidden');
                document.getElementById(fId + '_bundleName').textContent = activeBundle.name;
                document.getElementById(fId + '_bundleOriginal').textContent = fmtCurrency(totalWithFee);
                document.getElementById(fId + '_bundlePrice').textContent = fmtCurrency(activeBundle.price + designFee);
            } else if (discountEl) {
                discountEl.classList.add('hidden');
            }
        } else {
            summaryEl.classList.add('hidden');
            if (listEl) listEl.innerHTML = '';
            submitBtn.disabled = true;
            var discountEl2 = document.getElementById(fId + '_bundleDiscount');
            if (discountEl2) discountEl2.classList.add('hidden');
            window['_rfActiveBundle_' + fId] = null;
        }
    }

    function calcEnd(duration) {
        const start = startInput.value;
        if (!start) return;
        const [h, m] = start.split(':').map(Number);
        const endMin = Math.min(h * 60 + m + duration, 23 * 60 + 59); // 23:59 상한
        endInput.value = String(Math.floor(endMin / 60)).padStart(2, '0') + ':' +
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

    // 번들(패키지) 선택
    document.querySelectorAll('.rf-bundle-card[data-form-id="' + fId + '"]').forEach(function(bCard) {
        bCard.addEventListener('click', function() {
            var svcStr = this.dataset.services || '';
            var svcIds = svcStr.split(',').filter(Boolean);
            if (!svcIds.length) return;
            console.log('[ResForm] Bundle selected:', svcIds);

            // 기존 선택 해제
            checks.forEach(cb => {
                cb.checked = false;
                styleCard(cb.closest('.rf-card'), false);
            });

            // 번들에 포함된 서비스 선택
            svcIds.forEach(id => {
                var cb = form.querySelector('.rf-check[value="' + id + '"]');
                if (cb) {
                    cb.checked = true;
                    styleCard(cb.closest('.rf-card'), true);
                }
            });

            // 번들 카드 하이라이트
            document.querySelectorAll('.rf-bundle-card[data-form-id="' + fId + '"]').forEach(c => {
                c.classList.remove('border-blue-500', 'ring-2', 'ring-blue-200');
            });
            this.classList.add('border-blue-500', 'ring-2', 'ring-blue-200');

            // 번들 할인 표시
            var bundlePrice = parseFloat(this.dataset.bundlePrice || 0);
            var bundleName = this.dataset.bundleName || '';
            window['_rfActiveBundle_' + fId] = { price: bundlePrice, name: bundleName };

            recalc();
        });
    });

    // 폼 제출 검증 + AJAX 제출 (모달 모드)
    var formMode = '<?= $resForm['mode'] ?? 'page' ?>';
    form.addEventListener('submit', function(e) {
        e.preventDefault();

        var checked = form.querySelectorAll('.rf-check:checked');
        if (checked.length === 0) {
            if (typeof showResultModal === 'function') showResultModal(false, '<?= __('reservations.error_no_service') ?? '서비스를 1개 이상 선택해주세요.' ?>');
            else alert('<?= __('reservations.error_no_service') ?? '서비스를 1개 이상 선택해주세요.' ?>');
            return;
        }

        var nameVal = document.getElementById(fId + '_name')?.value?.trim();
        var phoneEl = document.getElementById(fId + '_phone_number') || document.getElementById(fId + '_phone');
        var phoneVal = phoneEl?.value?.trim();
        if (!nameVal || !phoneVal) {
            if (typeof showResultModal === 'function') showResultModal(false, '<?= __('reservations.error_required') ?? '필수 항목을 모두 입력해주세요.' ?>');
            else alert('<?= __('reservations.error_required') ?? '필수 항목을 모두 입력해주세요.' ?>');
            return;
        }

        // 더블 클릭 방지
        submitBtn.disabled = true;
        submitBtn.textContent = '<?= __('common.processing') ?? '처리중...' ?>';
        console.log('[ResForm] Submit:', fId, 'services:', checked.length, 'mode:', formMode);

        // phone-input 컴포넌트: 국가코드 + 번호 조합
        var phoneCountryEl = document.getElementById(fId + '_phone_country');
        if (phoneCountryEl && phoneEl) {
            var fullPhone = (phoneCountryEl.value || '') + phoneVal.replace(/^0+/, '');
            var hiddenPhoneEl = document.getElementById(fId + '_phone');
            if (hiddenPhoneEl) hiddenPhoneEl.value = fullPhone;
        }

        if (formMode === 'modal') {
            // AJAX 제출
            var fd = new FormData(form);
            fetch(form.action, { method: 'POST', body: fd, headers: { 'X-Requested-With': 'XMLHttpRequest' } })
            .then(r => r.json())
            .then(data => {
                if (data.success) {
                    if (typeof showResultModal === 'function') showResultModal(true, data.message || '');
                    // 모달 닫기 + 캘린더 새로고침
                    setTimeout(function() {
                        if (typeof rzxCalCloseAdd === 'function') rzxCalCloseAdd();
                        location.reload();
                    }, 1500);
                } else {
                    if (typeof showResultModal === 'function') showResultModal(false, data.message || '<?= __('common.msg.error') ?? '오류가 발생했습니다.' ?>');
                    else alert(data.message || '<?= __('common.msg.error') ?? '오류가 발생했습니다.' ?>');
                    submitBtn.disabled = false;
                    submitBtn.textContent = '<?= __('reservations.form_submit') ?? '등록' ?>';
                }
            })
            .catch(err => {
                console.error('[ResForm] Submit error:', err);
                submitBtn.disabled = false;
                submitBtn.textContent = '<?= __('reservations.form_submit') ?? '등록' ?>';
            });
        } else {
            // 일반 폼 제출 (페이지 모드)
            form.submit();
        }
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

    // 외부에서 금액 재계산 호출용
    window['recalcResForm_' + fId] = recalc;

    // 외부에서 폼 리셋 시 사용할 전역 함수
    window['resetResForm_' + fId] = function(date, time) {
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
        if (time) {
            startInput.value = time;
            // 종료 시간: 시작 +1시간 (최대 23:59)
            const [hh, mm] = time.split(':').map(Number);
            const eh = Math.min(hh + 1, 23);
            endInput.value = String(eh).padStart(2, '0') + ':' + String(mm).padStart(2, '0');
        } else {
            startInput.value = '09:00';
            endInput.value = '10:00';
        }
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
        console.log('[ResForm] Reset:', fId, 'date:', date, 'time:', time);
    };
    // 폼 리셋 시 스태프도 초기화
    const origReset = window['resetResForm_' + fId];
    window['resetResForm_' + fId] = function(date, time) {
        origReset(date, time);
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
