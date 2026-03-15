<script>
(function() {
    'use strict';

    const CONFIG = {
        staffId: <?= $staffId ?>,
        baseUrl: '<?= $baseUrl ?>',
        ajaxUrl: '<?= $baseUrl ?>/staff/<?= $staffId ?>',
        locale: '<?= $currentLocale ?>',
        designationFee: <?= (float)($staff['designation_fee'] ?? 0) ?>,
        dayLabels: <?= json_encode($days) ?>,
        monthNames: {
            ko: ['1월','2월','3월','4월','5월','6월','7월','8월','9월','10월','11월','12월'],
            ja: ['1月','2月','3月','4月','5月','6月','7月','8月','9月','10月','11月','12月'],
            en: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']
        },
        labels: {
            dayOff: '<?= addslashes(__('staff_page.day_off')) ?>',
            noSlots: '<?= addslashes(__('staff_page.no_available_slots')) ?>',
            selectedDate: '<?= addslashes(__('staff_page.selected_date_slots')) ?>',
            minutes: '<?= addslashes(__('common.minutes')) ?>',
            submitting: '<?= addslashes(__('booking.submitting')) ?>'
        }
    };

    let currentYear = new Date().getFullYear();
    let currentMonth = new Date().getMonth() + 1;
    let selectedDate = null;
    let selectedTime = null;
    let monthData = null;
    let selectedBundle = null; // { id, name, price, duration, serviceIds[] }

    // DOM refs
    const calendarBody = document.getElementById('calendarBody');
    const calendarTitle = document.getElementById('calendarTitle');
    const slotsSection = document.getElementById('slotsSection');
    const slotsGrid = document.getElementById('slotsGrid');
    const slotsTitle = document.getElementById('slotsTitle');
    const slotsEmpty = document.getElementById('slotsEmpty');
    const slotsLoading = document.getElementById('slotsLoading');

    // Bottom summary refs
    const bookingSummary = document.getElementById('sdBookingSummary');
    const selectedList = document.getElementById('sdSelectedList');
    const sumDuration = document.getElementById('sdSumDuration');
    const dateTimeRow = document.getElementById('sdDateTimeRow');
    const dateTimeLabel = document.getElementById('sdDateTimeLabel');
    const grandTotal = document.getElementById('sdGrandTotal');
    const bookBtn = document.getElementById('sdBookBtn');
    const bookHint = document.getElementById('sdBookHint');
    const bookingSuccess = document.getElementById('sdBookingSuccess');

    console.log('[StaffDetail] Init: staffId=' + CONFIG.staffId);

    // ---- Calendar Navigation ----
    document.getElementById('btnPrevMonth').addEventListener('click', function() {
        console.log('[StaffDetail] Previous month');
        currentMonth--;
        if (currentMonth < 1) { currentMonth = 12; currentYear--; }
        var now = new Date();
        if (currentYear < now.getFullYear() || (currentYear === now.getFullYear() && currentMonth < now.getMonth() + 1)) {
            currentMonth = now.getMonth() + 1; currentYear = now.getFullYear(); return;
        }
        loadMonth();
    });

    document.getElementById('btnNextMonth').addEventListener('click', function() {
        console.log('[StaffDetail] Next month');
        currentMonth++;
        if (currentMonth > 12) { currentMonth = 1; currentYear++; }
        var now = new Date();
        var maxMonth = now.getMonth() + 4;
        var maxYear = now.getFullYear() + Math.floor((maxMonth - 1) / 12);
        var maxM = ((maxMonth - 1) % 12) + 1;
        if (currentYear > maxYear || (currentYear === maxYear && currentMonth > maxM)) {
            currentYear = maxYear; currentMonth = maxM; return;
        }
        loadMonth();
    });

    // ---- Load Month ----
    function loadMonth() {
        console.log('[StaffDetail] Loading month: ' + currentYear + '-' + currentMonth);
        var mNames = CONFIG.monthNames[CONFIG.locale] || CONFIG.monthNames['en'];
        calendarTitle.textContent = currentYear + ' ' + mNames[currentMonth - 1];
        calendarBody.innerHTML = '<div class="col-span-7 py-6 text-center text-sm text-gray-400"><svg class="animate-spin h-5 w-5 mx-auto mb-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>';
        slotsSection.classList.add('hidden');
        selectedDate = null;
        selectedTime = null;
        updateSummary();

        fetch(CONFIG.ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'get_month_schedule', year: currentYear, month: currentMonth })
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            console.log('[StaffDetail] Month data:', data.days ? data.days.length + ' days' : 'error');
            if (!data.success) return;
            monthData = data.days;
            renderCalendar(data.days);
        })
        .catch(function(err) {
            console.error('[StaffDetail] Month load error:', err);
            calendarBody.innerHTML = '<div class="col-span-7 py-6 text-center text-sm text-red-500">Error</div>';
        });
    }

    // ---- Render Calendar ----
    function renderCalendar(days) {
        calendarBody.innerHTML = '';
        if (!days || days.length === 0) return;
        var firstDate = new Date(days[0].date);
        var startDow = firstDate.getDay();
        for (var i = 0; i < startDow; i++) {
            var empty = document.createElement('div');
            empty.className = 'py-3 border-b border-r border-gray-100 dark:border-zinc-700/50';
            calendarBody.appendChild(empty);
        }
        var today = new Date().toISOString().slice(0, 10);
        days.forEach(function(day) {
            var cell = document.createElement('div');
            var dateNum = parseInt(day.date.slice(8, 10));
            var dow = new Date(day.date).getDay();
            var isToday = day.date === today;
            cell.className = 'py-2 px-1 border-b border-r border-gray-100 dark:border-zinc-700/50 cursor-pointer transition-colors min-h-[3rem] relative';
            if (day.past) {
                cell.className += ' opacity-30 cursor-default';
                cell.innerHTML = '<div class="text-xs text-gray-400 dark:text-zinc-500">' + dateNum + '</div>';
            } else if (day.holiday) {
                cell.className += ' bg-red-50 dark:bg-red-900/10';
                cell.innerHTML = '<div class="text-xs text-red-400">' + dateNum + '</div><div class="text-[9px] text-red-300 mt-0.5">holiday</div>';
            } else if (!day.working) {
                cell.className += ' bg-gray-50 dark:bg-zinc-800/50';
                cell.innerHTML = '<div class="text-xs ' + (dow === 0 ? 'text-red-400' : dow === 6 ? 'text-blue-400' : 'text-gray-400 dark:text-zinc-500') + '">' + dateNum + '</div><div class="text-[9px] text-gray-300 dark:text-zinc-600 mt-0.5">' + CONFIG.labels.dayOff + '</div>';
            } else {
                var textColor = dow === 0 ? 'text-red-600 dark:text-red-400' : dow === 6 ? 'text-blue-600 dark:text-blue-400' : 'text-gray-900 dark:text-white';
                cell.className += ' hover:bg-blue-50 dark:hover:bg-blue-900/20';
                cell.innerHTML = '<div class="text-xs font-medium ' + textColor + '">' + dateNum + '</div>' +
                    (day.hours ? '<div class="text-[9px] text-gray-400 dark:text-zinc-500 mt-0.5">' + day.hours + '</div>' : '');
                cell.setAttribute('data-date', day.date);
                cell.addEventListener('click', function() {
                    console.log('[StaffDetail] Date clicked: ' + day.date);
                    selectDate(day.date);
                });
            }
            if (isToday) {
                cell.innerHTML = '<div class="absolute top-0.5 right-0.5 w-1.5 h-1.5 bg-blue-500 rounded-full"></div>' + cell.innerHTML;
            }
            calendarBody.appendChild(cell);
        });
    }

    // ---- Select Date & Load Slots ----
    function selectDate(date) {
        calendarBody.querySelectorAll('[data-date]').forEach(function(el) {
            el.classList.remove('ring-2', 'ring-blue-500', 'bg-blue-50', 'dark:bg-blue-900/30');
        });
        var target = calendarBody.querySelector('[data-date="' + date + '"]');
        if (target) target.classList.add('ring-2', 'ring-blue-500', 'bg-blue-50', 'dark:bg-blue-900/30');

        selectedDate = date;
        selectedTime = null;
        slotsSection.classList.remove('hidden');
        slotsGrid.innerHTML = '';
        slotsEmpty.classList.add('hidden');
        slotsLoading.classList.remove('hidden');

        var dp = date.split('-');
        slotsTitle.textContent = CONFIG.labels.selectedDate.replace(':date', dp[1] + '/' + dp[2]);

        // 선택된 서비스 duration 합산
        var dur = getSelectedDuration();
        var duration = dur > 0 ? dur : 60;

        fetch(CONFIG.ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'get_day_slots', date: date, duration: duration })
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            console.log('[StaffDetail] Slots for ' + date + ':', data.slots ? data.slots.length : 0);
            slotsLoading.classList.add('hidden');
            if (!data.success || !data.slots || data.slots.length === 0) {
                slotsEmpty.classList.remove('hidden');
                updateSummary();
                return;
            }
            renderSlots(data.slots);
            updateSummary();
        })
        .catch(function(err) {
            console.error('[StaffDetail] Slots error:', err);
            slotsLoading.classList.add('hidden');
            slotsEmpty.classList.remove('hidden');
        });
    }

    // ---- Render Slots as Selectable Buttons ----
    function renderSlots(slots) {
        slotsGrid.innerHTML = '';
        slots.forEach(function(slot) {
            var btn = document.createElement('button');
            btn.type = 'button';
            btn.setAttribute('data-time', slot);
            btn.className = 'sd-slot-btn px-4 py-2 text-sm font-medium bg-white dark:bg-zinc-700 border border-gray-200 dark:border-zinc-600 rounded-lg hover:bg-blue-50 hover:border-blue-300 dark:hover:bg-blue-900/30 dark:hover:border-blue-500 text-gray-900 dark:text-white transition-colors';
            btn.textContent = slot;
            btn.addEventListener('click', function() {
                console.log('[StaffDetail] Time selected: ' + slot);
                slotsGrid.querySelectorAll('.sd-slot-btn').forEach(function(b) {
                    b.classList.remove('bg-blue-600', 'text-white', 'border-blue-600');
                    b.classList.add('bg-white', 'dark:bg-zinc-700', 'border-gray-200', 'dark:border-zinc-600', 'text-gray-900', 'dark:text-white');
                });
                btn.classList.remove('bg-white', 'dark:bg-zinc-700', 'border-gray-200', 'dark:border-zinc-600', 'text-gray-900', 'dark:text-white');
                btn.classList.add('bg-blue-600', 'text-white', 'border-blue-600');
                selectedTime = slot;
                updateSummary();
            });
            slotsGrid.appendChild(btn);
        });
    }

    // ---- Service Multi-Select ----
    var sdCards = document.querySelectorAll('.sd-svc-card');
    var sdChecks = document.querySelectorAll('.sd-svc-check');
    var sdCountEl = document.getElementById('sdSvcCount');

    sdCards.forEach(function(card) {
        card.addEventListener('click', function(e) {
            if (e.target.tagName === 'INPUT') return;
            var cb = this.querySelector('.sd-svc-check');
            cb.checked = !cb.checked;
            sdStyleCard(this, cb.checked);
            onServiceChange();
            console.log('[StaffDetail] Service toggled:', cb.value, cb.checked);
        });
    });

    function sdStyleCard(card, on) {
        var inner = card.querySelector('.sd-card-inner');
        var circle = card.querySelector('.sd-circle');
        var icon = card.querySelector('.sd-check-icon');
        var overlay = card.querySelector('.sd-overlay');
        if (on) {
            inner.classList.remove('border-gray-200', 'dark:border-zinc-700');
            inner.classList.add('border-blue-500', 'ring-2', 'ring-blue-500/30');
            if (circle) { circle.classList.remove('border-white/70', 'bg-black/20'); circle.classList.add('border-blue-500', 'bg-blue-500'); }
            if (icon) icon.classList.remove('hidden');
            if (overlay) overlay.classList.remove('hidden');
        } else {
            inner.classList.remove('border-blue-500', 'ring-2', 'ring-blue-500/30');
            inner.classList.add('border-gray-200', 'dark:border-zinc-700');
            if (circle) { circle.classList.add('border-white/70', 'bg-black/20'); circle.classList.remove('border-blue-500', 'bg-blue-500'); }
            if (icon) icon.classList.add('hidden');
            if (overlay) overlay.classList.add('hidden');
        }
    }

    function getSelectedServices() {
        var items = [];
        sdChecks.forEach(function(cb) {
            if (cb.checked) {
                items.push({ id: cb.value, name: cb.dataset.name, price: parseFloat(cb.dataset.price || 0), duration: parseInt(cb.dataset.duration || 0) });
            }
        });
        return items;
    }

    function getSelectedDuration() {
        var dur = 0;
        sdChecks.forEach(function(cb) { if (cb.checked) dur += parseInt(cb.dataset.duration || 0); });
        return dur;
    }

    function onServiceChange() {
        var items = getSelectedServices();
        // 카운트 표시
        if (sdCountEl) {
            if (items.length > 0) {
                sdCountEl.textContent = items.length + '<?= __('booking.items_selected') ?>';
                sdCountEl.classList.remove('hidden');
            } else {
                sdCountEl.classList.add('hidden');
            }
        }
        // 슬롯 재조회 (날짜 선택 상태일 때)
        if (selectedDate && items.length > 0) {
            selectedTime = null;
            selectDate(selectedDate);
        }
        updateSummary();
    }

    // ---- Update Bottom Summary ----
    function updateSummary() {
        var items = getSelectedServices();
        if (!bookingSummary) return;

        if (items.length === 0) {
            bookingSummary.classList.add('hidden');
            return;
        }
        bookingSummary.classList.remove('hidden');

        var html = '';
        var totalPrice = 0;
        var totalDur = 0;

        // 번들이 선택된 경우
        if (selectedBundle) {
            var bundledIds = selectedBundle.serviceIds;
            var extraItems = [];

            // 번들 헤더
            html += '<div class="px-4 py-2.5 bg-blue-50 dark:bg-blue-900/20">';
            html += '<div class="flex items-center justify-between">';
            html += '<div class="flex items-center gap-2">';
            html += '<svg class="w-4 h-4 text-blue-600 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>';
            html += '<span class="text-sm font-semibold text-blue-700 dark:text-blue-300">' + selectedBundle.name + '</span>';
            html += '</div>';
            html += '<span class="text-sm font-bold text-blue-700 dark:text-blue-300">&yen;' + Number(selectedBundle.price).toLocaleString() + '</span>';
            html += '</div>';
            // 번들 포함 서비스 (회색으로 표시)
            items.forEach(function(s) {
                if (bundledIds.includes(String(s.id))) {
                    totalDur += s.duration;
                    html += '<div class="flex items-center gap-2 mt-1 ml-6">';
                    html += '<span class="text-xs text-gray-400 dark:text-zinc-500">· ' + s.name + ' (' + s.duration + CONFIG.labels.minutes + ')</span>';
                    html += '</div>';
                }
            });
            html += '</div>';
            totalPrice += selectedBundle.price;

            // 번들 외 추가 서비스
            items.forEach(function(s) {
                if (!bundledIds.includes(String(s.id))) {
                    totalPrice += s.price;
                    totalDur += s.duration;
                    html += '<div class="flex items-center justify-between px-4 py-2.5">';
                    html += '<div class="flex items-center gap-2">';
                    html += '<svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
                    html += '<span class="text-sm text-gray-900 dark:text-white">' + s.name + '</span>';
                    html += '<span class="text-xs text-gray-400 dark:text-zinc-500">(' + s.duration + CONFIG.labels.minutes + ')</span>';
                    html += '</div>';
                    html += '<span class="text-sm font-medium text-gray-900 dark:text-white">&yen;' + Number(s.price).toLocaleString() + '</span>';
                    html += '</div>';
                }
            });
        } else {
            // 개별 서비스만
            items.forEach(function(s) {
                totalPrice += s.price;
                totalDur += s.duration;
                html += '<div class="flex items-center justify-between px-4 py-2.5">';
                html += '<div class="flex items-center gap-2">';
                html += '<svg class="w-4 h-4 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>';
                html += '<span class="text-sm text-gray-900 dark:text-white">' + s.name + '</span>';
                html += '<span class="text-xs text-gray-400 dark:text-zinc-500">(' + s.duration + CONFIG.labels.minutes + ')</span>';
                html += '</div>';
                html += '<span class="text-sm font-medium text-gray-900 dark:text-white">&yen;' + Number(s.price).toLocaleString() + '</span>';
                html += '</div>';
            });
        }
        if (selectedList) selectedList.innerHTML = html;

        // 소요시간
        if (sumDuration) sumDuration.textContent = totalDur + CONFIG.labels.minutes;

        // 일시 표시
        if (dateTimeRow && dateTimeLabel) {
            if (selectedDate && selectedTime) {
                var dp = selectedDate.split('-');
                dateTimeRow.classList.remove('hidden');
                dateTimeLabel.textContent = dp[0] + '/' + dp[1] + '/' + dp[2] + ' ' + selectedTime;
            } else {
                dateTimeRow.classList.add('hidden');
            }
        }

        // 합계 (서비스/번들 + 지명비)
        var total = totalPrice + CONFIG.designationFee;
        if (grandTotal) grandTotal.textContent = '¥' + Number(total).toLocaleString();

        // 버튼 상태
        updateBookBtn();
    }

    function updateBookBtn() {
        if (!bookBtn) return;
        var items = getSelectedServices();
        var ready = items.length > 0 && selectedDate && selectedTime;
        if (ready) {
            bookBtn.disabled = false;
            bookBtn.className = 'w-full py-3 text-white font-semibold rounded-lg transition shadow-lg shadow-blue-500/30 bg-blue-600 hover:bg-blue-700 cursor-pointer';
            if (bookHint) bookHint.classList.add('hidden');
        } else {
            bookBtn.disabled = true;
            bookBtn.className = 'w-full py-3 text-white font-semibold rounded-lg transition shadow-lg bg-gray-300 dark:bg-zinc-600 text-gray-500 dark:text-zinc-400 cursor-not-allowed';
            if (bookHint) bookHint.classList.remove('hidden');
        }
    }

    // ---- Submit Reservation ----
    if (bookBtn) {
        bookBtn.addEventListener('click', function() {
            if (this.disabled) return;
            var items = getSelectedServices();
            var name = (document.getElementById('sdCustName') || {}).value || '';
            var phone = (document.getElementById('sdCustPhone') || {}).value || '';
            var email = (document.getElementById('sdCustEmail') || {}).value || '';
            var notes = (document.getElementById('sdCustNotes') || {}).value || '';

            if (!name.trim() || !phone.trim()) {
                alert('<?= addslashes(__('booking.error.required_fields')) ?>');
                return;
            }

            console.log('[StaffDetail] Submitting reservation:', { services: items.length, date: selectedDate, time: selectedTime });

            bookBtn.disabled = true;
            bookBtn.textContent = CONFIG.labels.submitting;

            fetch(CONFIG.ajaxUrl, {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'create_reservation',
                    service_ids: items.map(function(s) { return s.id; }),
                    bundle_id: selectedBundle ? selectedBundle.id : null,
                    date: selectedDate,
                    time: selectedTime,
                    customer_name: name.trim(),
                    customer_phone: phone.trim(),
                    customer_email: email.trim(),
                    notes: notes.trim()
                })
            })
            .then(function(res) { return res.json(); })
            .then(function(data) {
                console.log('[StaffDetail] Reservation result:', data);
                if (data.success) {
                    // 성공: 폼 숨기고 성공 메시지 표시
                    bookingSummary.querySelector('.bg-white, .dark\\:bg-zinc-800').style.display = 'none';
                    if (bookingSuccess) {
                        bookingSuccess.classList.remove('hidden');
                        var numEl = document.getElementById('sdReservationNumber');
                        if (numEl) numEl.textContent = data.reservation_number || '';
                    }
                } else {
                    alert(data.message || 'Error');
                    bookBtn.disabled = false;
                    bookBtn.textContent = '<?= addslashes(__('staff_page.book_selected')) ?>';
                }
            })
            .catch(function(err) {
                console.error('[StaffDetail] Reservation error:', err);
                alert('Error: ' + err.message);
                bookBtn.disabled = false;
                bookBtn.textContent = '<?= addslashes(__('staff_page.book_selected')) ?>';
            });
        });
    }

    // ---- Category Filter ----
    var sdActiveCat = '';
    var sdCatFilter = document.getElementById('sdCatFilter');
    if (sdCatFilter) {
        sdCatFilter.querySelectorAll('.sd-cat-btn').forEach(function(btn) {
            btn.addEventListener('click', function() {
                sdActiveCat = this.dataset.cat || '';
                sdCatFilter.querySelectorAll('.sd-cat-btn').forEach(function(b) {
                    b.classList.remove('bg-blue-600', 'text-white');
                    b.classList.add('bg-gray-100', 'dark:bg-zinc-700', 'text-gray-600', 'dark:text-zinc-300');
                });
                this.classList.add('bg-blue-600', 'text-white');
                this.classList.remove('bg-gray-100', 'dark:bg-zinc-700', 'text-gray-600', 'dark:text-zinc-300');
                sdCards.forEach(function(card) {
                    var matchCat = !sdActiveCat || (card.dataset.cat || '') === sdActiveCat;
                    card.style.display = matchCat ? '' : 'none';
                });
                console.log('[StaffDetail] Category filter:', sdActiveCat || 'all');
            });
        });
    }

    // ---- Bundle Selection ----
    var bundleCards = document.querySelectorAll('.sd-bundle-card');
    bundleCards.forEach(function(card) {
        card.addEventListener('click', function() {
            var bundleId = this.dataset.bundleId;
            var bundleName = this.dataset.name;
            var bundlePrice = parseFloat(this.dataset.price || 0);
            var bundleDuration = parseInt(this.dataset.duration || 0);
            var svcIdStr = this.dataset.services || '';
            var svcIds = svcIdStr.split(',').filter(Boolean);

            console.log('[StaffDetail] Bundle clicked:', bundleId, svcIds);

            // 같은 번들 재클릭 → 해제
            if (selectedBundle && selectedBundle.id === bundleId) {
                selectedBundle = null;
                bundleCards.forEach(function(c) { sdStyleBundleCard(c, false); });
                // 서비스 체크 해제 (번들에 의해 체크된 것만)
                sdChecks.forEach(function(cb) {
                    if (svcIds.includes(cb.value)) {
                        cb.checked = false;
                        cb.disabled = false;
                        sdStyleCard(cb.closest('.sd-svc-card'), false);
                    }
                });
                console.log('[StaffDetail] Bundle deselected');
            } else {
                // 이전 번들 해제
                if (selectedBundle) {
                    var prevIds = selectedBundle.serviceIds;
                    sdChecks.forEach(function(cb) {
                        if (prevIds.includes(cb.value)) {
                            cb.checked = false;
                            cb.disabled = false;
                            sdStyleCard(cb.closest('.sd-svc-card'), false);
                        }
                    });
                }
                // 새 번들 선택
                selectedBundle = { id: bundleId, name: bundleName, price: bundlePrice, duration: bundleDuration, serviceIds: svcIds };
                bundleCards.forEach(function(c) { sdStyleBundleCard(c, c.dataset.bundleId === bundleId); });
                // 포함 서비스 자동 체크 + 잠금
                sdChecks.forEach(function(cb) {
                    if (svcIds.includes(cb.value)) {
                        cb.checked = true;
                        cb.disabled = true;
                        sdStyleCard(cb.closest('.sd-svc-card'), true);
                    }
                });
                console.log('[StaffDetail] Bundle selected:', bundleName);
            }
            onServiceChange();
        });
    });

    function sdStyleBundleCard(card, on) {
        var inner = card.querySelector('.sd-bundle-inner');
        var circle = card.querySelector('.sd-bundle-circle');
        var icon = card.querySelector('.sd-bundle-check-icon');
        if (on) {
            inner.classList.remove('border-gray-200', 'dark:border-zinc-700');
            inner.classList.add('border-blue-500', 'ring-2', 'ring-blue-500/30', 'bg-blue-50', 'dark:bg-blue-900/20');
            if (circle) { circle.classList.remove('border-gray-300', 'dark:border-zinc-600'); circle.classList.add('border-blue-500', 'bg-blue-500'); }
            if (icon) icon.classList.remove('hidden');
        } else {
            inner.classList.remove('border-blue-500', 'ring-2', 'ring-blue-500/30', 'bg-blue-50', 'dark:bg-blue-900/20');
            inner.classList.add('border-gray-200', 'dark:border-zinc-700');
            if (circle) { circle.classList.add('border-gray-300', 'dark:border-zinc-600'); circle.classList.remove('border-blue-500', 'bg-blue-500'); }
            if (icon) icon.classList.add('hidden');
        }
    }

    // ---- Init ----
    loadMonth();

})();
</script>
