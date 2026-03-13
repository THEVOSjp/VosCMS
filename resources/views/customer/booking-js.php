<script>
(function() {
    'use strict';

    const STAFF_ENABLED = <?= $staffEnabled ? 'true' : 'false' ?>;
    const TOTAL_STEPS = <?= $totalSteps ?>;
    const CURRENCY_SYMBOL = '<?= $currencySymbol ?>';
    const PRICE_DISPLAY = '<?= $priceDisplay ?>';
    const BASE_URL = '<?= $baseUrl ?>';
    const SCHEDULE_ENABLED = <?= ($scheduleEnabled ?? false) ? 'true' : 'false' ?>;
    const DESIGNATION_FEE_ENABLED = <?= ($designationFeeEnabled ?? false) ? 'true' : 'false' ?>;
    const SLOT_INTERVAL = <?= $slotInterval ?? 30 ?>;

    // 스텝 순서 정의
    const stepOrder = ['stepService'];
    if (STAFF_ENABLED) stepOrder.push('stepStaff');
    stepOrder.push('stepDatetime', 'stepInfo', 'stepConfirm');

    const stepLabels = [
        '<?= __('booking.select_service') ?>',
        <?php if ($staffEnabled): ?>'<?= __('booking.select_staff') ?>',<?php endif; ?>
        '<?= __('booking.select_datetime') ?>',
        '<?= __('booking.enter_info') ?>',
        '<?= __('booking.confirm_info') ?>'
    ];

    let currentStep = 0;

    // 선택된 값 저장 (다중 서비스)
    const selected = {
        services: [],  // [{id, name, price, duration}, ...]
        staffId: null, staffName: '',
        designationFee: 0,
        date: '', time: '',
        name: '', phone: '', email: '', notes: ''
    };

    // === Progress Bar 렌더링 ===
    function renderProgressBar() {
        const bar = document.getElementById('progressBar');
        if (!bar) return;
        let html = '';
        for (let i = 0; i < stepOrder.length; i++) {
            const cls = i < currentStep ? 'step-completed' : (i === currentStep ? 'step-active' : 'step-inactive');
            html += '<div class="flex items-center">';
            html += '<div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold ' + cls + '">';
            if (i < currentStep) {
                html += '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
            } else {
                html += (i + 1);
            }
            html += '</div>';
            html += '<span class="ml-1 text-xs hidden sm:inline ' + (i === currentStep ? 'text-blue-600 dark:text-blue-400 font-semibold' : 'text-gray-500 dark:text-zinc-400') + '">' + stepLabels[i] + '</span>';
            if (i < stepOrder.length - 1) {
                html += '<div class="w-6 sm:w-10 h-0.5 mx-1 ' + (i < currentStep ? 'bg-green-500' : 'bg-gray-300 dark:bg-zinc-600') + '"></div>';
            }
            html += '</div>';
        }
        bar.innerHTML = html;
    }

    // === 스텝 표시/숨김 ===
    function showStep(idx) {
        console.log('[Booking] showStep:', idx, stepOrder[idx]);
        document.querySelectorAll('.step-panel').forEach(el => el.classList.add('hidden'));
        const panel = document.getElementById(stepOrder[idx]);
        if (panel) panel.classList.remove('hidden');
        currentStep = idx;
        renderProgressBar();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    // === nextStep / prevStep ===
    window.nextStep = function() {
        console.log('[Booking] nextStep from:', currentStep);

        // 유효성 검사
        if (stepOrder[currentStep] === 'stepService' && selected.services.length === 0) return;
        if (stepOrder[currentStep] === 'stepStaff') {
            const staffRadio = document.querySelector('input[name="staff"]:checked');
            if (!staffRadio) return;
        }
        if (stepOrder[currentStep] === 'stepDatetime' && (!selected.date || !selected.time)) return;

        // 고객정보 스텝에서 다음 → 확인 페이지 업데이트
        if (stepOrder[currentStep] === 'stepInfo') {
            selected.name = document.getElementById('customerName').value.trim();
            selected.phone = document.getElementById('customerPhone').value.trim();
            selected.email = document.getElementById('customerEmail').value.trim();
            selected.notes = document.getElementById('customerMemo').value.trim();

            if (!selected.name || !selected.phone) {
                alert('<?= __('booking.error.required_fields') ?>');
                return;
            }
            populateConfirmation();
        }

        if (currentStep < stepOrder.length - 1) {
            showStep(currentStep + 1);

            // 스태프 스텝 진입 시 서비스 기반 필터링
            if (stepOrder[currentStep] === 'stepStaff') {
                filterStaffByService();
            }
            // 날짜/시간 스텝 진입 시 이미 날짜 선택된 경우 슬롯 리로드
            if (stepOrder[currentStep] === 'stepDatetime' && selected.date) {
                selected.time = '';
                fetchAvailableSlots();
                updateDatetimeBtn();
            }
        }
    };

    window.prevStep = function() {
        console.log('[Booking] prevStep from:', currentStep);
        if (currentStep > 0) showStep(currentStep - 1);
    };

    // === 서비스 선택 (다중) ===
    function updateServiceSelection() {
        selected.services = [];
        document.querySelectorAll('.service-card input[name="service[]"]:checked').forEach(cb => {
            selected.services.push({
                id: cb.value,
                name: cb.dataset.name || '',
                price: parseFloat(cb.dataset.price) || 0,
                duration: parseInt(cb.dataset.duration) || 60
            });
        });

        // 하이라이트
        document.querySelectorAll('.service-card').forEach(card => {
            const cb = card.querySelector('input[name="service[]"]');
            const div = card.querySelector(':scope > div');
            if (!cb || !div) return;
            if (cb.checked) {
                div.classList.remove('border-gray-200', 'dark:border-zinc-700');
                div.classList.add('border-blue-500', 'dark:border-blue-400', 'bg-blue-50', 'dark:bg-blue-900/20');
            } else {
                div.classList.remove('border-blue-500', 'dark:border-blue-400', 'bg-blue-50', 'dark:bg-blue-900/20');
                div.classList.add('border-gray-200', 'dark:border-zinc-700');
            }
        });

        const btn = document.getElementById('btnServiceNext');
        if (btn) btn.disabled = selected.services.length === 0;

        console.log('[Booking] Services selected:', selected.services.length, selected.services.map(s => s.name));
    }

    document.querySelectorAll('.service-card input[name="service[]"]').forEach(cb => {
        cb.addEventListener('change', updateServiceSelection);
    });

    // === 스태프 선택 ===
    document.querySelectorAll('.staff-card input[name="staff"]').forEach(radio => {
        radio.addEventListener('change', function() {
            console.log('[Booking] Staff selected:', this.value || '(no preference)');

            document.querySelectorAll('.staff-card > div').forEach(d => {
                d.classList.remove('border-blue-500', 'dark:border-blue-400', 'bg-blue-50', 'dark:bg-blue-900/20');
                d.classList.add('border-gray-200', 'dark:border-zinc-700');
            });
            this.closest('.staff-card').querySelector('div').classList.remove('border-gray-200', 'dark:border-zinc-700');
            this.closest('.staff-card').querySelector('div').classList.add('border-blue-500', 'dark:border-blue-400', 'bg-blue-50', 'dark:bg-blue-900/20');

            selected.staffId = this.value || null;
            selected.staffName = this.dataset.name || '';
            selected.designationFee = DESIGNATION_FEE_ENABLED ? (parseFloat(this.dataset.fee) || 0) : 0;
            console.log('[Booking] Designation fee:', selected.designationFee);

            const btn = document.getElementById('btnStaffNext');
            if (btn) btn.disabled = false;
        });
    });

    // === 서비스 기반 스태프 필터링 (선택된 모든 서비스를 담당하는 스태프만 표시) ===
    function filterStaffByService() {
        if (!STAFF_ENABLED || selected.services.length === 0) return;
        const selectedIds = selected.services.map(s => s.id);
        console.log('[Booking] Filtering staff by services:', selectedIds);

        document.querySelectorAll('.staff-card').forEach(card => {
            const servicesAttr = card.getAttribute('data-services');
            if (!servicesAttr) {
                // "지정 안함" 옵션은 항상 표시
                card.style.display = '';
                return;
            }
            try {
                const staffServices = JSON.parse(servicesAttr);
                // 선택된 서비스 중 하나라도 담당하면 표시
                const hasAny = selectedIds.some(id => staffServices.includes(id));
                card.style.display = hasAny ? '' : 'none';
            } catch (e) {
                card.style.display = '';
            }
        });

        // 스태프 선택 리셋
        document.querySelectorAll('.staff-card input[name="staff"]').forEach(r => r.checked = false);
        document.querySelectorAll('.staff-card > div').forEach(d => {
            d.classList.remove('border-blue-500', 'dark:border-blue-400', 'bg-blue-50', 'dark:bg-blue-900/20');
            d.classList.add('border-gray-200', 'dark:border-zinc-700');
        });
        selected.staffId = null;
        selected.staffName = '';
        selected.designationFee = 0;
        const btn = document.getElementById('btnStaffNext');
        if (btn) btn.disabled = true;
    }

    // === 날짜 선택 → 시간 슬롯 로드 ===
    const dateInput = document.getElementById('bookingDate');
    if (dateInput) {
        dateInput.addEventListener('change', function() {
            console.log('[Booking] Date selected:', this.value);
            selected.date = this.value;
            selected.time = '';
            fetchAvailableSlots();
            updateDatetimeBtn();
        });
    }

    function fetchAvailableSlots() {
        const container = document.getElementById('timeSlots');
        if (!container) return;
        container.innerHTML = '<div class="col-span-full text-center text-sm text-gray-400 py-4"><?= __('booking.loading_slots') ?></div>';

        const totalDur = getTotalDuration();
        const payload = {
            action: 'get_available_slots',
            date: selected.date,
            staff_id: selected.staffId,
            total_duration: totalDur
        };

        console.log('[Booking] Fetching slots:', payload);

        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            console.log('[Booking] Slots response:', data);
            container.innerHTML = '';
            if (!data.success || !data.slots || data.slots.length === 0) {
                container.innerHTML = '<div class="col-span-full text-center text-sm text-gray-400 py-4"><?= __('booking.no_available_slots') ?></div>';
                return;
            }
            data.slots.forEach(timeStr => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'time-slot px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:border-blue-500 transition text-gray-700 dark:text-zinc-300';
                btn.textContent = timeStr;
                btn.addEventListener('click', function() {
                    console.log('[Booking] Time selected:', timeStr);
                    document.querySelectorAll('.time-slot').forEach(b => {
                        b.classList.remove('bg-blue-600', 'text-white', 'border-blue-600');
                        b.classList.add('border-gray-300', 'dark:border-zinc-600', 'text-gray-700', 'dark:text-zinc-300');
                    });
                    this.classList.remove('border-gray-300', 'dark:border-zinc-600', 'text-gray-700', 'dark:text-zinc-300');
                    this.classList.add('bg-blue-600', 'text-white', 'border-blue-600');
                    selected.time = timeStr;
                    updateDatetimeBtn();
                });
                container.appendChild(btn);
            });
        })
        .catch(err => {
            console.error('[Booking] Fetch slots error:', err);
            container.innerHTML = '<div class="col-span-full text-center text-sm text-red-400 py-4"><?= __('common.error') ?></div>';
        });
    }

    function updateDatetimeBtn() {
        const btn = document.getElementById('btnDatetimeNext');
        if (btn) btn.disabled = !(selected.date && selected.time);
    }

    // === 합산 헬퍼 ===
    function getServicePrice() {
        return selected.services.reduce((sum, s) => sum + s.price, 0);
    }
    function getTotalPrice() {
        return getServicePrice() + selected.designationFee;
    }
    function getTotalDuration() {
        return selected.services.reduce((sum, s) => sum + s.duration, 0);
    }

    // === 확인 페이지 데이터 채우기 ===
    function populateConfirmation() {
        console.log('[Booking] Populating confirmation...');

        // 서비스 목록
        const cs = document.getElementById('confirmService');
        if (cs) {
            let html = '';
            selected.services.forEach(s => {
                let priceStr = '';
                if (PRICE_DISPLAY === 'show') priceStr = ' <span class="text-blue-600 dark:text-blue-400">' + CURRENCY_SYMBOL + Number(s.price).toLocaleString() + '</span>';
                html += '<div class="flex justify-between items-center text-sm">'
                    + '<span class="font-semibold text-gray-900 dark:text-white">' + s.name + ' <span class="text-gray-400 font-normal">(' + s.duration + '<?= __('common.minutes') ?>)</span></span>'
                    + priceStr + '</div>';
            });
            cs.innerHTML = html;
        }

        if (STAFF_ENABLED) {
            const cst = document.getElementById('confirmStaff');
            if (cst) cst.textContent = selected.staffName || '<?= __('booking.no_preference') ?>';
        }

        const cd = document.getElementById('confirmDate');
        if (cd) cd.textContent = selected.date;

        const ct = document.getElementById('confirmTime');
        if (ct) {
            const dur = getTotalDuration();
            ct.textContent = selected.time + ' (' + dur + '<?= __('common.minutes') ?>)';
        }

        const cn = document.getElementById('confirmName');
        if (cn) cn.textContent = selected.name;

        const cp = document.getElementById('confirmPhone');
        if (cp) cp.textContent = selected.phone;

        // 지명비 표시
        const dfRow = document.getElementById('confirmDesignationFeeRow');
        const dfVal = document.getElementById('confirmDesignationFee');
        if (dfRow && dfVal) {
            if (DESIGNATION_FEE_ENABLED && selected.designationFee > 0 && PRICE_DISPLAY === 'show') {
                dfRow.classList.remove('hidden');
                dfRow.classList.add('flex');
                dfVal.textContent = CURRENCY_SYMBOL + Number(selected.designationFee).toLocaleString();
            } else {
                dfRow.classList.add('hidden');
                dfRow.classList.remove('flex');
            }
        }

        const cprice = document.getElementById('confirmPrice');
        if (cprice) {
            if (PRICE_DISPLAY === 'show') {
                cprice.textContent = CURRENCY_SYMBOL + Number(getTotalPrice()).toLocaleString();
            } else if (PRICE_DISPLAY === 'contact') {
                cprice.textContent = '<?= __('admin.services.settings.general.price_contact') ?>';
            } else {
                cprice.textContent = '';
            }
        }
    }

    // === 예약 제출 ===
    window.submitBooking = function() {
        console.log('[Booking] Submitting booking...');
        const btn = document.getElementById('submitBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<svg class="animate-spin w-5 h-5 inline mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg><?= __('booking.submitting') ?>';
        }

        const payload = {
            service_ids: selected.services.map(s => s.id),
            staff_id: selected.staffId,
            designation_fee: selected.designationFee,
            date: selected.date,
            time: selected.time,
            customer_name: selected.name,
            customer_phone: selected.phone,
            customer_email: selected.email,
            notes: selected.notes
        };

        console.log('[Booking] Payload:', JSON.stringify(payload));

        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(r => r.json())
        .then(data => {
            console.log('[Booking] Response:', data);
            if (data.success) {
                document.querySelectorAll('.step-panel').forEach(el => el.classList.add('hidden'));
                const done = document.getElementById('stepDone');
                if (done) done.classList.remove('hidden');
                const code = document.getElementById('doneBookingCode');
                if (code) code.textContent = data.reservation_number || '';
                const pb = document.getElementById('progressBar');
                if (pb) pb.style.display = 'none';
            } else {
                alert(data.message || '<?= __('common.error') ?>');
                if (btn) {
                    btn.disabled = false;
                    btn.innerHTML = '<?= __('booking.complete_booking') ?>';
                }
            }
        })
        .catch(err => {
            console.error('[Booking] Error:', err);
            alert('<?= __('common.error') ?>');
            if (btn) {
                btn.disabled = false;
                btn.innerHTML = '<?= __('booking.complete_booking') ?>';
            }
        });
    };

    // === URL 파라미터 기반 사전 선택 ===
    function handleUrlPreselection() {
        const params = new URLSearchParams(window.location.search);
        const preStaff = params.get('staff');
        const preService = params.get('service');
        const preDate = params.get('date');
        const preTime = params.get('time');

        if (!preStaff && !preService && !preDate) return;
        console.log('[Booking] URL preselection - staff:', preStaff, 'service:', preService);

        // 1) 서비스 사전 선택
        if (preService) {
            const cb = document.querySelector('.service-card input[name="service[]"][value="' + preService + '"]');
            if (cb) {
                cb.checked = true;
                updateServiceSelection();
                console.log('[Booking] Pre-selected service:', preService);
            }
        }

        // 2) 스태프 사전 선택
        if (preStaff && STAFF_ENABLED) {
            const radio = document.querySelector('.staff-card input[name="staff"][value="' + preStaff + '"]');
            if (radio) {
                radio.checked = true;
                radio.dispatchEvent(new Event('change'));
                console.log('[Booking] Pre-selected staff:', preStaff);
            }
        }

        // 3) 자동 스텝 이동
        if (selected.services.length > 0) {
            if (STAFF_ENABLED && selected.staffId) {
                // 서비스 + 스태프 모두 선택됨 → 날짜/시간 스텝으로
                filterStaffByService();
                // 필터 후 스태프 다시 선택 (필터가 리셋하므로)
                const radio = document.querySelector('.staff-card input[name="staff"][value="' + preStaff + '"]');
                if (radio && radio.closest('.staff-card').style.display !== 'none') {
                    radio.checked = true;
                    radio.dispatchEvent(new Event('change'));
                    showStep(stepOrder.indexOf('stepDatetime'));
                } else {
                    // 스태프가 해당 서비스 미담당이면 스태프 선택 스텝으로
                    showStep(stepOrder.indexOf('stepStaff'));
                }
            } else if (STAFF_ENABLED) {
                // 서비스만 선택됨 → 스태프 선택 스텝으로
                filterStaffByService();
                showStep(stepOrder.indexOf('stepStaff'));
            } else {
                // 스태프 비활성 → 날짜/시간 스텝으로
                showStep(stepOrder.indexOf('stepDatetime'));
            }
        } else if (preStaff && STAFF_ENABLED) {
            // 서비스 미선택, 스태프만 → 서비스 선택 스텝 (기본)
            showStep(0);
        }

        // 4) 날짜/시간 사전 선택 (date/time URL 파라미터)
        if (preDate) {
            setTimeout(function() {
                const di = document.getElementById('bookingDate');
                if (di) {
                    di.value = preDate;
                    selected.date = preDate;
                    console.log('[Booking] Pre-selected date:', preDate);
                    fetchAvailableSlots();

                    if (preTime) {
                        // 슬롯 로드 후 시간 자동 선택
                        setTimeout(function() {
                            const slots = document.querySelectorAll('.time-slot');
                            slots.forEach(function(btn) {
                                if (btn.textContent === preTime) {
                                    btn.click();
                                    console.log('[Booking] Pre-selected time:', preTime);
                                }
                            });
                        }, 1000);
                    }
                }
            }, 300);
        }
    }

    // === 초기화 ===
    renderProgressBar();
    showStep(0);
    handleUrlPreselection();
    console.log('[Booking] Initialized. Staff enabled:', STAFF_ENABLED, 'Total steps:', TOTAL_STEPS);

})();
</script>
