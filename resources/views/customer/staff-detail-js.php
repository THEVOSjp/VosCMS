<script>
(function() {
    'use strict';

    const CONFIG = {
        staffId: <?= $staffId ?>,
        baseUrl: '<?= $baseUrl ?>',
        ajaxUrl: '<?= $baseUrl ?>/staff/<?= $staffId ?>',
        locale: '<?= $currentLocale ?>',
        dayLabels: <?= json_encode($days) ?>,
        monthNames: {
            ko: ['1월','2월','3월','4월','5월','6월','7월','8월','9월','10월','11월','12월'],
            ja: ['1月','2月','3月','4月','5月','6月','7月','8月','9月','10月','11月','12月'],
            en: ['Jan','Feb','Mar','Apr','May','Jun','Jul','Aug','Sep','Oct','Nov','Dec']
        },
        labels: {
            dayOff: '<?= addslashes(__('staff_page.day_off')) ?>',
            noSlots: '<?= addslashes(__('staff_page.no_available_slots')) ?>',
            selectedDate: '<?= addslashes(__('staff_page.selected_date_slots')) ?>'
        }
    };

    let currentYear = new Date().getFullYear();
    let currentMonth = new Date().getMonth() + 1; // 1-based
    let selectedDate = null;
    let monthData = null;

    const calendarBody = document.getElementById('calendarBody');
    const calendarTitle = document.getElementById('calendarTitle');
    const slotsSection = document.getElementById('slotsSection');
    const slotsGrid = document.getElementById('slotsGrid');
    const slotsTitle = document.getElementById('slotsTitle');
    const slotsEmpty = document.getElementById('slotsEmpty');
    const slotsLoading = document.getElementById('slotsLoading');

    console.log('[StaffDetail] Calendar init: staffId=' + CONFIG.staffId);

    // ---- Calendar Navigation ----
    document.getElementById('btnPrevMonth').addEventListener('click', function() {
        console.log('[StaffDetail] Previous month clicked');
        currentMonth--;
        if (currentMonth < 1) { currentMonth = 12; currentYear--; }
        // 과거 월 방지 (이번 달 이전 X)
        const now = new Date();
        if (currentYear < now.getFullYear() || (currentYear === now.getFullYear() && currentMonth < now.getMonth() + 1)) {
            currentMonth = now.getMonth() + 1;
            currentYear = now.getFullYear();
            return;
        }
        loadMonth();
    });

    document.getElementById('btnNextMonth').addEventListener('click', function() {
        console.log('[StaffDetail] Next month clicked');
        currentMonth++;
        if (currentMonth > 12) { currentMonth = 1; currentYear++; }
        // 최대 3개월 앞까지
        const now = new Date();
        const maxMonth = now.getMonth() + 4; // 현재 + 3개월
        const maxYear = now.getFullYear() + Math.floor((maxMonth - 1) / 12);
        const maxM = ((maxMonth - 1) % 12) + 1;
        if (currentYear > maxYear || (currentYear === maxYear && currentMonth > maxM)) {
            currentYear = maxYear;
            currentMonth = maxM;
            return;
        }
        loadMonth();
    });

    // ---- Load Month Data ----
    function loadMonth() {
        console.log('[StaffDetail] Loading month: ' + currentYear + '-' + currentMonth);

        const mNames = CONFIG.monthNames[CONFIG.locale] || CONFIG.monthNames['en'];
        calendarTitle.textContent = currentYear + ' ' + mNames[currentMonth - 1];

        // Clear
        calendarBody.innerHTML = '<div class="col-span-7 py-6 text-center text-sm text-gray-400"><svg class="animate-spin h-5 w-5 mx-auto mb-1" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg></div>';
        slotsSection.classList.add('hidden');
        selectedDate = null;

        fetch(CONFIG.ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'get_month_schedule', year: currentYear, month: currentMonth })
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            console.log('[StaffDetail] Month data received:', data.days ? data.days.length + ' days' : 'error');
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

        // 첫째 날 요일
        var firstDate = new Date(days[0].date);
        var startDow = firstDate.getDay(); // 0=Sun

        // 빈 셀 채우기
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
        // Highlight selected
        calendarBody.querySelectorAll('[data-date]').forEach(function(el) {
            el.classList.remove('ring-2', 'ring-blue-500', 'bg-blue-50', 'dark:bg-blue-900/30');
        });
        var target = calendarBody.querySelector('[data-date="' + date + '"]');
        if (target) {
            target.classList.add('ring-2', 'ring-blue-500', 'bg-blue-50', 'dark:bg-blue-900/30');
        }

        selectedDate = date;
        slotsSection.classList.remove('hidden');
        slotsGrid.innerHTML = '';
        slotsEmpty.classList.add('hidden');
        slotsLoading.classList.remove('hidden');

        // Format date for title
        var dp = date.split('-');
        slotsTitle.textContent = CONFIG.labels.selectedDate.replace(':date', dp[1] + '/' + dp[2]);

        fetch(CONFIG.ajaxUrl, {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'get_day_slots', date: date, duration: 60 })
        })
        .then(function(res) { return res.json(); })
        .then(function(data) {
            console.log('[StaffDetail] Slots received for ' + date + ':', data.slots ? data.slots.length : 0);
            slotsLoading.classList.add('hidden');

            if (!data.success || !data.slots || data.slots.length === 0) {
                slotsEmpty.classList.remove('hidden');
                return;
            }

            data.slots.forEach(function(slot) {
                var btn = document.createElement('a');
                btn.href = CONFIG.baseUrl + '/booking?staff=' + CONFIG.staffId + '&date=' + date + '&time=' + slot;
                btn.className = 'px-4 py-2 text-sm font-medium bg-white dark:bg-zinc-700 border border-gray-200 dark:border-zinc-600 rounded-lg hover:bg-blue-50 hover:border-blue-300 dark:hover:bg-blue-900/30 dark:hover:border-blue-500 text-gray-900 dark:text-white transition-colors';
                btn.textContent = slot;
                btn.addEventListener('click', function() {
                    console.log('[StaffDetail] Slot selected: ' + date + ' ' + slot);
                });
                slotsGrid.appendChild(btn);
            });
        })
        .catch(function(err) {
            console.error('[StaffDetail] Slots load error:', err);
            slotsLoading.classList.add('hidden');
            slotsEmpty.classList.remove('hidden');
        });
    }

    // ---- Init ----
    loadMonth();

})();
</script>
