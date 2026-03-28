(function() {
    'use strict';

    const CFG = window.__bwConfig || {};
    const CS = CFG.currencySymbol || '';
    const PD = CFG.priceDisplay || 'show';
    const LB = CFG.labels || {};

    const stepOrder = ['bwStepService', 'bwStepDatetime', 'bwStepInfo', 'bwStepConfirm'];
    const stepLabels = [LB.selectService, LB.selectDatetime, LB.enterInfo, LB.confirmInfo];
    let currentStep = 0;

    const selected = {
        services: [], date: '', time: '',
        name: '', phone: '', email: '', notes: '',
        bundlePrice: 0, bundleName: ''
    };

    // === Progress Bar ===
    function renderProgressBar() {
        const bar = document.getElementById('bwProgressBar');
        if (!bar) return;
        let html = '';
        for (let i = 0; i < stepOrder.length; i++) {
            const cls = i < currentStep ? 'bw-step-completed' : (i === currentStep ? 'bw-step-active' : 'bw-step-inactive');
            html += '<div class="flex items-center">';
            html += '<div class="w-8 h-8 rounded-full flex items-center justify-center text-sm font-bold ' + cls + '">';
            if (i < currentStep) {
                html += '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
            } else {
                html += (i + 1);
            }
            html += '</div>';
            html += '<span class="ml-1 text-xs hidden sm:inline ' + (i === currentStep ? 'text-blue-600 dark:text-blue-400 font-semibold' : 'text-gray-500 dark:text-zinc-400') + '">' + (stepLabels[i] || '') + '</span>';
            if (i < stepOrder.length - 1) {
                html += '<div class="w-6 sm:w-10 h-0.5 mx-1 ' + (i < currentStep ? 'bg-green-500' : 'bg-gray-300 dark:bg-zinc-600') + '"></div>';
            }
            html += '</div>';
        }
        bar.innerHTML = html;
    }

    // === Step navigation ===
    function showStep(idx) {
        console.log('[BW] showStep:', idx, stepOrder[idx]);
        document.querySelectorAll('.bw-step-panel').forEach(function(el) { el.classList.add('hidden'); });
        var panel = document.getElementById(stepOrder[idx]);
        if (panel) panel.classList.remove('hidden');
        currentStep = idx;
        renderProgressBar();
        window.scrollTo({ top: 0, behavior: 'smooth' });
    }

    function nextStep() {
        console.log('[BW] nextStep from:', currentStep);
        if (stepOrder[currentStep] === 'bwStepService' && selected.services.length === 0) return;
        if (stepOrder[currentStep] === 'bwStepDatetime' && (!selected.date || !selected.time)) return;

        if (stepOrder[currentStep] === 'bwStepInfo') {
            selected.name = document.getElementById('bwCustName').value.trim();
            selected.phone = document.getElementById('bwCustPhone').value.trim();
            selected.email = document.getElementById('bwCustEmail').value.trim();
            selected.notes = document.getElementById('bwCustMemo').value.trim();
            if (!selected.name || !selected.phone) {
                alert(LB.requiredFields || 'Required fields missing');
                return;
            }
            populateConfirmation();
        }

        if (currentStep < stepOrder.length - 1) {
            showStep(currentStep + 1);
            if (stepOrder[currentStep] === 'bwStepDatetime' && selected.date) {
                selected.time = '';
                fetchSlots();
                updateDatetimeBtn();
            }
        }
    }

    function prevStep() {
        console.log('[BW] prevStep from:', currentStep);
        if (currentStep > 0) showStep(currentStep - 1);
    }

    // === Service selection ===
    function updateServiceSelection() {
        selected.services = [];
        // 번들이 아닌 직접 선택이면 번들 정보 초기화
        var hasBundleSelection = document.querySelector('.bw-bundle-card.border-blue-500');
        if (!hasBundleSelection) {
            selected.bundlePrice = 0;
            selected.bundleName = '';
        }
        document.querySelectorAll('.bw-svc-card input[name="bw_service[]"]:checked').forEach(function(cb) {
            selected.services.push({
                id: cb.value,
                name: cb.dataset.name || '',
                price: parseFloat(cb.dataset.price) || 0,
                duration: parseInt(cb.dataset.duration) || 60
            });
        });

        document.querySelectorAll('.bw-svc-card').forEach(function(card) {
            var cb = card.querySelector('input[name="bw_service[]"]');
            var div = card.querySelector('.bw-card-inner');
            if (!cb || !div) return;
            var circle = div.querySelector('.bw-circle');
            var icon = div.querySelector('.bw-check-icon');
            var overlay = div.querySelector('.bw-overlay');
            if (cb.checked) {
                div.classList.remove('border-gray-200', 'dark:border-zinc-700');
                div.classList.add('border-blue-500', 'ring-2', 'ring-blue-500/30');
                if (circle) { circle.classList.remove('border-white/70', 'bg-black/20'); circle.classList.add('border-blue-500', 'bg-blue-500'); }
                if (icon) icon.classList.remove('hidden');
                if (overlay) overlay.classList.remove('hidden');
            } else {
                div.classList.remove('border-blue-500', 'ring-2', 'ring-blue-500/30');
                div.classList.add('border-gray-200', 'dark:border-zinc-700');
                if (circle) { circle.classList.add('border-white/70', 'bg-black/20'); circle.classList.remove('border-blue-500', 'bg-blue-500'); }
                if (icon) icon.classList.add('hidden');
                if (overlay) overlay.classList.add('hidden');
            }
        });

        var summary = document.getElementById('bwSelectedSummary');
        var countEl = document.getElementById('bwSelectedCount');
        var durEl = document.getElementById('bwTotalDuration');
        var priceEl = document.getElementById('bwTotalPrice');
        if (summary && selected.services.length > 0) {
            summary.classList.remove('hidden');
            summary.classList.add('flex');
            if (countEl) countEl.textContent = selected.services.length + (LB.itemsSelected || '');
            if (durEl) durEl.textContent = getTotalDuration() + (LB.minutes || '');
            if (priceEl) priceEl.textContent = CS + Number(getTotalPrice()).toLocaleString();
        } else if (summary) {
            summary.classList.add('hidden');
            summary.classList.remove('flex');
        }

        var btn = document.getElementById('bwBtnServiceNext');
        if (btn) btn.disabled = selected.services.length === 0;
        console.log('[BW] Services selected:', selected.services.length);
    }

    document.querySelectorAll('.bw-svc-card input[name="bw_service[]"]').forEach(function(cb) {
        cb.addEventListener('change', updateServiceSelection);
    });

    // === Category filter ===
    var activeCat = '';
    var catFilter = document.getElementById('bwCatFilter');
    if (catFilter) {
        catFilter.querySelectorAll('.bw-cat-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                activeCat = this.dataset.cat || '';
                catFilter.querySelectorAll('.bw-cat-btn').forEach(function(b) {
                    b.classList.remove('bg-blue-600', 'text-white');
                    b.classList.add('bg-gray-100', 'dark:bg-zinc-700', 'text-gray-600', 'dark:text-zinc-300');
                });
                this.classList.add('bg-blue-600', 'text-white');
                this.classList.remove('bg-gray-100', 'dark:bg-zinc-700', 'text-gray-600', 'dark:text-zinc-300');
                document.querySelectorAll('.bw-svc-card').forEach(function(card) {
                    card.style.display = (!activeCat || (card.dataset.cat || '') === activeCat) ? '' : 'none';
                });
                console.log('[BW] Category filter:', activeCat || 'all');
            });
        });
    }

    // === Date/Time ===
    var dateInput = document.getElementById('bwBookingDate');
    if (dateInput) {
        dateInput.addEventListener('change', function() {
            console.log('[BW] Date selected:', this.value);
            selected.date = this.value;
            selected.time = '';
            fetchSlots();
            updateDatetimeBtn();
        });
    }

    function fetchSlots() {
        var container = document.getElementById('bwTimeSlots');
        if (!container) return;
        container.innerHTML = '<div class="col-span-full text-center text-sm text-gray-400 py-4">' + (LB.loadingSlots || 'Loading...') + '</div>';

        var payload = {
            action: 'get_available_slots',
            date: selected.date,
            staff_id: null,
            total_duration: getTotalDuration()
        };
        console.log('[BW] Fetching slots:', payload);

        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            console.log('[BW] Slots response:', data);
            container.innerHTML = '';
            if (!data.success || !data.slots || data.slots.length === 0) {
                container.innerHTML = '<div class="col-span-full text-center text-sm text-gray-400 py-4">' + (LB.noSlots || 'No slots') + '</div>';
                return;
            }
            data.slots.forEach(function(timeStr) {
                var btn = document.createElement('button');
                btn.type = 'button';
                btn.className = 'bw-time-slot px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 hover:border-blue-500 transition text-gray-700 dark:text-zinc-300';
                btn.textContent = timeStr;
                btn.addEventListener('click', function() {
                    console.log('[BW] Time selected:', timeStr);
                    document.querySelectorAll('.bw-time-slot').forEach(function(b) {
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
        .catch(function(err) {
            console.error('[BW] Fetch slots error:', err);
            container.innerHTML = '<div class="col-span-full text-center text-sm text-red-400 py-4">' + (LB.error || 'Error') + '</div>';
        });
    }

    function updateDatetimeBtn() {
        var btn = document.getElementById('bwBtnDatetimeNext');
        if (btn) btn.disabled = !(selected.date && selected.time);
    }

    // === Helpers ===
    function getTotalPrice() {
        // 번들이 선택된 경우 번들 가격 반환, 아니면 서비스 가격 합산
        if (selected.bundlePrice > 0) {
            return selected.bundlePrice;
        }
        return selected.services.reduce(function(s, v) { return s + v.price; }, 0);
    }
    function getTotalDuration() { return selected.services.reduce(function(s, v) { return s + v.duration; }, 0); }

    // === Confirmation ===
    function populateConfirmation() {
        console.log('[BW] Populating confirmation...');
        var cs = document.getElementById('bwConfirmService');
        if (cs) {
            var html = '';
            // 번들이 선택된 경우 번들 정보만 표시
            if (selected.bundlePrice > 0 && selected.bundleName) {
                var priceStr = '';
                if (PD === 'show') priceStr = ' <span class="text-blue-600 dark:text-blue-400">' + CS + Number(selected.bundlePrice).toLocaleString() + '</span>';
                html = '<div class="flex justify-between items-center text-sm"><span class="font-semibold text-gray-900 dark:text-white">' + selected.bundleName + ' (クーポン)</span>' + priceStr + '</div>';
            } else {
                // 일반 서비스 목록 표시
                selected.services.forEach(function(s) {
                    var priceStr = '';
                    if (PD === 'show') priceStr = ' <span class="text-blue-600 dark:text-blue-400">' + CS + Number(s.price).toLocaleString() + '</span>';
                    html += '<div class="flex justify-between items-center text-sm"><span class="font-semibold text-gray-900 dark:text-white">' + s.name + ' <span class="text-gray-400 font-normal">(' + s.duration + (LB.minutes || '') + ')</span></span>' + priceStr + '</div>';
                });
            }
            cs.innerHTML = html;
        }
        var cd = document.getElementById('bwConfirmDate'); if (cd) cd.textContent = selected.date;
        var ct = document.getElementById('bwConfirmTime'); if (ct) ct.textContent = selected.time + ' (' + getTotalDuration() + (LB.minutes || '') + ')';
        var cn = document.getElementById('bwConfirmName'); if (cn) cn.textContent = selected.name;
        var cp = document.getElementById('bwConfirmPhone');
        if (cp) {
            var custPhoneCountry = document.getElementById('bwCustPhone_country');
            var custPhoneNumber = document.getElementById('bwCustPhone_number');
            if (custPhoneCountry && custPhoneNumber && custPhoneNumber.value) {
                cp.textContent = custPhoneCountry.value + ' ' + custPhoneNumber.value;
            } else {
                cp.textContent = selected.phone;
            }
        }
        var cprice = document.getElementById('bwConfirmPrice');
        if (cprice) {
            if (PD === 'show') cprice.textContent = CS + Number(getTotalPrice()).toLocaleString();
            else if (PD === 'contact') cprice.textContent = LB.priceContact || '';
            else cprice.textContent = '';
        }
    }

    // === Submit ===
    function submitBooking() {
        console.log('[BW] Submitting booking...');
        var btn = document.getElementById('bwSubmitBtn');
        if (btn) {
            btn.disabled = true;
            btn.innerHTML = '<svg class="animate-spin w-5 h-5 inline mr-2" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>' + (LB.submitting || 'Submitting...');
        }

        var payload = {
            service_ids: selected.services.map(function(s) { return s.id; }),
            date: selected.date,
            time: selected.time,
            customer_name: selected.name,
            customer_phone: selected.phone,
            customer_email: selected.email,
            notes: selected.notes
        };
        console.log('[BW] Payload:', JSON.stringify(payload));

        fetch(window.location.href, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
        .then(function(r) { return r.json(); })
        .then(function(data) {
            console.log('[BW] Response:', data);
            if (data.success) {
                document.querySelectorAll('.bw-step-panel').forEach(function(el) { el.classList.add('hidden'); });
                var done = document.getElementById('bwStepDone'); if (done) done.classList.remove('hidden');

                // 예약 정보 입력
                var code = document.getElementById('bwDoneCode'); if (code) code.textContent = data.reservation_number || '';

                // 서비스 목록
                var svcDiv = document.getElementById('bwDoneService');
                if (svcDiv) {
                    svcDiv.innerHTML = '';
                    // 번들이 선택된 경우 번들 정보만 표시
                    if (selected.bundlePrice > 0 && selected.bundleName) {
                        var bundleContainer = document.createElement('div');
                        bundleContainer.className = 'flex justify-between items-center';

                        var bundleNameSpan = document.createElement('span');
                        bundleNameSpan.className = 'text-gray-900 dark:text-white';
                        bundleNameSpan.textContent = selected.bundleName + ' (クーポン)';
                        bundleContainer.appendChild(bundleNameSpan);

                        var bundlePriceSpan = document.createElement('span');
                        bundlePriceSpan.className = 'text-gray-600 dark:text-zinc-400 text-sm';
                        bundlePriceSpan.textContent = CS + Number(selected.bundlePrice).toLocaleString();
                        bundleContainer.appendChild(bundlePriceSpan);

                        svcDiv.appendChild(bundleContainer);
                    } else {
                        // 일반 서비스 목록
                        var checkedSvcs = document.querySelectorAll('input[name="bw_service[]"]:checked');
                        checkedSvcs.forEach(function(inp) {
                            var svcName = inp.dataset.name || '';
                            var svcPrice = Number(inp.dataset.price || 0);
                            var container = document.createElement('div');
                            container.className = 'flex justify-between items-center';

                            var nameSpan = document.createElement('span');
                            nameSpan.className = 'text-gray-900 dark:text-white';
                            nameSpan.textContent = svcName;
                            container.appendChild(nameSpan);

                            if (svcPrice > 0) {
                                var priceSpan = document.createElement('span');
                                priceSpan.className = 'text-gray-600 dark:text-zinc-400 text-sm';
                                priceSpan.textContent = CS + svcPrice.toLocaleString();
                                container.appendChild(priceSpan);
                            }

                            svcDiv.appendChild(container);
                        });
                    }
                }

                // 날짜 & 시간
                var dateEl = document.getElementById('bwDoneDate');
                if (dateEl) {
                    var dateVal = selected.date || '';
                    if (dateVal) dateEl.textContent = dateVal;
                }

                var timeEl = document.getElementById('bwDoneTime');
                if (timeEl) {
                    var timeVal = selected.time || '';
                    if (timeVal) timeEl.textContent = timeVal;
                }

                // 고객 정보
                var nameEl = document.getElementById('bwDoneName');
                if (nameEl) {
                    var custName = document.getElementById('bwCustName');
                    if (custName) nameEl.textContent = custName.value;
                }

                var phoneEl = document.getElementById('bwDonePhone');
                if (phoneEl) {
                    var custPhoneCountry = document.getElementById('bwCustPhone_country');
                    var custPhoneNumber = document.getElementById('bwCustPhone_number');
                    if (custPhoneCountry && custPhoneNumber) {
                        phoneEl.textContent = custPhoneCountry.value + ' ' + custPhoneNumber.value;
                    } else {
                        var custPhone = document.getElementById('bwCustPhone');
                        if (custPhone) phoneEl.textContent = custPhone.value;
                    }
                }

                // 가격
                var priceEl = document.getElementById('bwDonePrice');
                if (priceEl) {
                    var priceDisplay = document.getElementById('bwTotalPrice');
                    if (priceDisplay && priceDisplay.textContent) {
                        priceEl.textContent = priceDisplay.textContent;
                    }
                }

                var pb = document.getElementById('bwProgressBar'); if (pb) pb.style.display = 'none';
            } else {
                alert(data.message || (LB.error || 'Error'));
                if (btn) { btn.disabled = false; btn.innerHTML = LB.completeBooking || 'Submit'; }
            }
        })
        .catch(function(err) {
            console.error('[BW] Error:', err);
            alert(LB.error || 'Error');
            if (btn) { btn.disabled = false; btn.innerHTML = LB.completeBooking || 'Submit'; }
        });
    }

    // === Button bindings ===
    var svcNextBtn = document.getElementById('bwBtnServiceNext');
    if (svcNextBtn) svcNextBtn.addEventListener('click', nextStep);

    document.querySelectorAll('.bw-next-btn').forEach(function(btn) { btn.addEventListener('click', nextStep); });
    document.querySelectorAll('.bw-prev-btn').forEach(function(btn) { btn.addEventListener('click', prevStep); });

    var submitBtn = document.getElementById('bwSubmitBtn');
    if (submitBtn) submitBtn.addEventListener('click', submitBooking);

    // === URL preselection ===
    function handleUrlPreselection() {
        var params = new URLSearchParams(window.location.search);
        var preService = params.get('service');
        var preDate = params.get('date');
        var preTime = params.get('time');
        if (!preService && !preDate) return;
        console.log('[BW] URL preselection - service:', preService);

        if (preService) {
            var cb = document.querySelector('.bw-svc-card input[name="bw_service[]"][value="' + preService + '"]');
            if (cb) { cb.checked = true; updateServiceSelection(); }
        }
        if (selected.services.length > 0) showStep(stepOrder.indexOf('bwStepDatetime'));
        if (preDate) {
            setTimeout(function() {
                var di = document.getElementById('bwBookingDate');
                if (di) { di.value = preDate; selected.date = preDate; fetchSlots(); }
                if (preTime) {
                    setTimeout(function() {
                        document.querySelectorAll('.bw-time-slot').forEach(function(b) { if (b.textContent === preTime) b.click(); });
                    }, 1000);
                }
            }, 300);
        }
    }

    // === Init ===
    renderProgressBar();
    // 번들(패키지) 선택 - 이벤트 바인딩
    document.querySelectorAll('[data-bundle-select]').forEach(function(card) {
        card.addEventListener('click', function() { selectBundleHandler(card); });
    });

    function selectBundleHandler(card) {
        var svcStr = card.dataset.services || '';
        var svcIds = svcStr.split(',').filter(Boolean);
        if (!svcIds.length) return;
        console.log('[BW] Bundle selected, services:', svcIds);

        // 번들 카드 하이라이트 (먼저 클래스 추가 - updateServiceSelection이 이를 확인함)
        document.querySelectorAll('.bw-bundle-card').forEach(function(c) { c.classList.remove('border-blue-500', 'ring-2', 'ring-blue-200'); });
        card.classList.add('border-blue-500', 'ring-2', 'ring-blue-200');

        // 번들 가격 정보 저장
        selected.bundlePrice = parseFloat(card.dataset.bundlePrice) || 0;
        selected.bundleName = card.dataset.bundleName || '';
        console.log('[BW] Bundle price:', selected.bundlePrice, 'Bundle name:', selected.bundleName);

        // 기존 선택 해제
        document.querySelectorAll('.bw-svc-card input[name="bw_service[]"]').forEach(function(cb) {
            cb.checked = false;
            var inner = cb.closest('.bw-svc-card').querySelector('.bw-card-inner');
            if (inner) {
                inner.classList.remove('border-blue-500', 'ring-2', 'ring-blue-200');
                var overlay = inner.querySelector('.bw-overlay');
                var checkIcon = inner.querySelector('.bw-check-icon');
                var circle = inner.querySelector('.bw-circle');
                if (overlay) overlay.classList.add('hidden');
                if (checkIcon) checkIcon.classList.add('hidden');
                if (circle) { circle.classList.remove('bg-blue-500', 'border-blue-500'); circle.classList.add('bg-black/20', 'border-white/70'); }
            }
        });

        // 번들에 포함된 서비스 선택
        svcIds.forEach(function(id) {
            var cb = document.querySelector('.bw-svc-card input[name="bw_service[]"][value="' + id + '"]');
            if (cb) {
                cb.checked = true;
                cb.dispatchEvent(new Event('change', { bubbles: true }));
            }
        });
    }

    showStep(0);
    handleUrlPreselection();
    console.log('[BW] Booking widget initialized.');

})();
