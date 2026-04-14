/**
 * VosCMS 서비스 신청 페이지 JS
 */

// ===== 통화 전환 =====
var currentCurrency = (typeof siteCurrency !== 'undefined') ? siteCurrency : 'KRW';
var exchangeRates = { KRW: 1, USD: 1/1380, JPY: 1/9.2, CNY: 1/190, EUR: 1/1500 };
var currencySymbols = { KRW: '원', USD: '$', JPY: '¥', CNY: '¥', EUR: '€' };
var currencyDecimals = { KRW: 0, USD: 2, JPY: 0, CNY: 2, EUR: 2 };
var currencyPrefix = { KRW: false, USD: true, JPY: true, CNY: true, EUR: true };

function setCurrency(code) {
    currentCurrency = code;
    document.querySelectorAll('.cur-btn').forEach(function(btn) {
        btn.classList.remove('bg-white/20', 'text-white');
        btn.classList.add('text-zinc-400');
    });
    var active = document.getElementById('cur_' + code);
    if (active) { active.classList.add('bg-white/20', 'text-white'); active.classList.remove('text-zinc-400'); }
    updateCurrencyDisplay();
}

function formatCurrency(krw) {
    var rate = exchangeRates[currentCurrency] || 1;
    var sym = currencySymbols[currentCurrency] || '';
    var dec = currencyDecimals[currentCurrency] ?? 0;
    var pre = currencyPrefix[currentCurrency] ?? false;
    var converted = krw * rate;
    var formatted = dec > 0 ? converted.toFixed(dec) : Math.round(converted).toLocaleString();
    return pre ? (sym + formatted) : (formatted + sym);
}

function updateCurrencyDisplay() {
    document.querySelectorAll('.cur-price').forEach(function(el) {
        el.textContent = formatCurrency(parseFloat(el.dataset.krw) || 0);
    });
}

// ===== 호스팅/결제 선택 UI =====
document.querySelectorAll('.hosting-option').forEach(function(el) {
    el.addEventListener('click', function() {
        var grid = el.closest('.grid');
        if (!grid) return;
        grid.querySelectorAll('.hosting-option').forEach(function(s) {
            s.classList.remove('selected', 'border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/30');
            s.classList.add('border-gray-200', 'dark:border-zinc-600');
        });
        el.classList.add('selected', 'border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/30');
        el.classList.remove('border-gray-200', 'dark:border-zinc-600');

        // 무료 플랜 선택 시 기간 1개월 강제 + 추가용량 비활성
        var radio = el.querySelector('input[name="hosting_plan"]');
        var isFree = radio && radio.value === 'free';
        var notice = document.getElementById('freePlanNotice');
        var periodWrap = document.getElementById('hostingPeriodWrap');
        var storageWrap = document.getElementById('hostingStorageWrap');

        if (notice) notice.classList.toggle('hidden', !isFree);

        if (periodWrap) {
            periodWrap.querySelectorAll('input[name="hosting_period"]').forEach(function(r) {
                if (isFree) {
                    r.disabled = (r.value !== '1');
                    if (r.value === '1') r.checked = true;
                    r.closest('label').classList.toggle('opacity-40', r.value !== '1');
                    r.closest('label').classList.toggle('cursor-not-allowed', r.value !== '1');
                    r.closest('label').classList.toggle('cursor-pointer', r.value === '1');
                } else {
                    r.disabled = false;
                    r.closest('label').classList.remove('opacity-40', 'cursor-not-allowed');
                    r.closest('label').classList.add('cursor-pointer');
                }
            });
        }

        if (storageWrap) {
            var storageSelect = storageWrap.querySelector('select');
            if (storageSelect) {
                storageSelect.disabled = isFree;
                if (isFree) storageSelect.value = '0';
            }
            storageWrap.classList.toggle('opacity-40', isFree);
        }

        // 메일 입력 비활성
        var mailWrap = document.getElementById('mailAccountsWrap');
        var mailAddBtn = mailWrap?.parentElement?.querySelector('button[onclick*="addMailAccount"]');
        if (mailWrap) {
            mailWrap.querySelectorAll('input').forEach(function(inp) { inp.disabled = isFree; });
            mailWrap.classList.toggle('opacity-40', isFree);
        }
        if (mailAddBtn) { mailAddBtn.disabled = isFree; mailAddBtn.classList.toggle('opacity-40', isFree); }
    });
});

// ===== 도메인 검색 =====
var selectedDomains = {};
var domainPricing = {
    '.com': 20000, '.net': 22000, '.org': 18000, '.io': 55000,
    '.co': 20000, '.dev': 22000, '.shop': 10000, '.store': 12000,
    '.site': 10000, '.online': 10000, '.biz': 15000, '.info': 15000,
    '.xyz': 8000, '.app': 25000
};
var searchTLDs = ['.com','.net','.org','.io','.co','.dev','.shop','.store','.site','.online','.biz','.info','.xyz','.app'];

function searchDomain() {
    var input = document.getElementById('domainInput').value.trim().toLowerCase().replace(/\.[a-z.]+$/i, '');
    if (!input || input.length < 2) { alert('2자 이상 입력하세요.'); return; }

    var results = document.getElementById('domainResults');
    var loading = document.getElementById('domainLoading');
    var list = document.getElementById('domainList');
    var confirmWrap = document.getElementById('domainConfirmWrap');
    var confirmed = document.getElementById('domainConfirmed');

    results.classList.remove('hidden');
    loading.classList.remove('hidden');
    list.innerHTML = '';
    if (confirmWrap) confirmWrap.classList.add('hidden');
    if (confirmed) confirmed.classList.add('hidden');
    selectedDomains = {};

    // TODO: 실제 구현 시 AJAX → /api/domain/check 로 NameSilo API 호출
    setTimeout(function() {
        loading.classList.add('hidden');
        var domains = searchTLDs.map(function(tld) {
            return { name: input + tld, tld: tld, available: Math.random() > 0.3 };
        });
        domains.sort(function(a, b) { return a.available === b.available ? 0 : a.available ? -1 : 1; });

        domains.forEach(function(d) {
            var price = domainPricing[d.tld] || 15000;
            if (d.available) {
                list.innerHTML += '<label class="domain-result flex items-center justify-between p-3 border border-gray-200 dark:border-zinc-600 rounded-xl cursor-pointer transition hover:border-blue-400 dark:hover:border-blue-500 hover:bg-blue-50/50 dark:hover:bg-blue-900/20" data-domain="' + d.name + '" data-price="' + price + '">'
                    + '<div class="flex items-center gap-3"><input type="checkbox" class="domain-check w-4 h-4 text-blue-600 rounded border-gray-300 dark:border-zinc-500 focus:ring-blue-500" onchange="toggleDomain(\'' + d.name + '\',' + price + ',this)">'
                    + '<div><p class="text-sm font-semibold text-gray-900 dark:text-white">' + d.name + '</p><p class="text-xs text-green-600">등록 가능</p></div></div>'
                    + '<p class="text-sm font-bold text-blue-600">' + price.toLocaleString() + '원<span class="text-xs font-normal text-gray-400 dark:text-zinc-500">/년</span></p></label>';
            } else {
                list.innerHTML += '<div class="flex items-center justify-between p-3 border border-gray-100 dark:border-zinc-700 rounded-xl opacity-40">'
                    + '<div class="flex items-center gap-3"><span class="w-4 h-4 flex items-center justify-center"><svg class="w-3.5 h-3.5 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"/></svg></span>'
                    + '<div><p class="text-sm text-gray-400 dark:text-zinc-500 line-through">' + d.name + '</p><p class="text-xs text-red-400">이미 등록됨</p></div></div>'
                    + '<p class="text-xs text-gray-300 dark:text-zinc-600">등록 불가</p></div>';
            }
        });
    }, 800);
}

function toggleDomain(name, price, checkbox) {
    var label = checkbox.closest('.domain-result');
    if (checkbox.checked) {
        selectedDomains[name] = price;
        label.classList.add('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
        label.classList.remove('border-gray-200', 'dark:border-zinc-600');
    } else {
        delete selectedDomains[name];
        label.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
        label.classList.add('border-gray-200', 'dark:border-zinc-600');
    }
    updateDomainSummary();
}

function updateDomainSummary() {
    var count = Object.keys(selectedDomains).length;
    var total = Object.values(selectedDomains).reduce(function(s, p) { return s + p; }, 0);
    var wrap = document.getElementById('domainConfirmWrap');
    if (!wrap) return;
    document.getElementById('domainSelectedCount').textContent = count;
    document.getElementById('domainSelectedTotal').textContent = total.toLocaleString() + '원';
    if (count > 0) wrap.classList.remove('hidden'); else wrap.classList.add('hidden');
}

function confirmDomains() {
    var list = document.getElementById('domainList');
    var confirmWrap = document.getElementById('domainConfirmWrap');
    var confirmed = document.getElementById('domainConfirmed');
    list.classList.add('hidden');
    confirmWrap.classList.add('hidden');
    confirmed.classList.remove('hidden');
    var h = '<div class="flex items-center justify-between mb-2"><p class="text-xs font-medium text-green-700 dark:text-green-400 uppercase tracking-wider">선택된 도메인</p>'
        + '<button onclick="resetDomainSearch()" class="text-xs text-blue-600 hover:underline">다시 검색</button></div>';
    Object.keys(selectedDomains).forEach(function(name) {
        h += '<div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl">'
            + '<div class="flex items-center gap-2"><svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>'
            + '<p class="text-sm font-semibold text-gray-900 dark:text-white">' + name + '</p></div>'
            + '<p class="text-sm font-bold text-green-700 dark:text-green-400">' + selectedDomains[name].toLocaleString() + '원/년</p></div>';
    });
    confirmed.innerHTML = h;

    // 첫 번째 도메인으로 메일 도메인 업데이트
    var firstDomain = Object.keys(selectedDomains)[0];
    if (firstDomain) updateMailDomain(firstDomain);
    updateOrderSummary();
}

function resetDomainSearch() {
    document.getElementById('domainList').classList.remove('hidden');
    document.getElementById('domainConfirmed').classList.add('hidden');
    selectedDomains = {};
    document.querySelectorAll('.domain-check').forEach(function(cb) { cb.checked = false; });
    document.querySelectorAll('.domain-result').forEach(function(el) {
        el.classList.remove('border-blue-500', 'bg-blue-50', 'dark:bg-blue-900/20');
        el.classList.add('border-gray-200', 'dark:border-zinc-600');
    });
    updateDomainSummary();
}

// ===== 도메인 옵션 토글 =====
function toggleDomainOption(type) {
    ['domainFree', 'domainSearch', 'domainExisting', 'domainNone'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.classList.add('hidden');
    });
    var map = { free: 'domainFree', 'new': 'domainSearch', existing: 'domainExisting', none: 'domainNone' };
    var target = document.getElementById(map[type]);
    if (target) target.classList.remove('hidden');

    // 도메인 전환 시 메일 도메인 리셋
    updateMailDomain('도메인을 선택하세요');
}

// 서브도메인 사용 가능 확인
function checkSubdomain() {
    var input = document.getElementById('freeSubdomain');
    var result = document.getElementById('subdomainResult');
    var val = (input?.value || '').trim().toLowerCase().replace(/[^a-z0-9-]/g, '');
    if (!val || val.length < 2) { result.innerHTML = '<p class="text-xs text-red-500">2자 이상 영문 소문자/숫자로 입력하세요.</p>'; result.classList.remove('hidden'); return; }
    input.value = val;
    // TODO: 실제 구현 시 서버 API로 중복 확인
    result.innerHTML = '<p class="text-xs text-green-600 flex items-center gap-1"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg><strong>' + val + '.21ces.net</strong> 사용 가능합니다.</p>';
    result.classList.remove('hidden');

    // 메일 도메인 업데이트
    updateMailDomain(val + '.21ces.net');
    updateOrderSummary();
}

// 메일 도메인 접미사 업데이트
function updateMailDomain(domain) {
    document.querySelectorAll('.mail-domain-suffix').forEach(function(el) {
        el.textContent = '@' + domain;
    });
    document.querySelectorAll('.bizmail-domain-suffix').forEach(function(el) {
        el.textContent = '@' + domain;
    });
}

// 도메인 확정 시 메일 도메인도 업데이트
var _origConfirmDomains = typeof confirmDomains === 'function' ? confirmDomains : null;

// ===== 메일 계정 추가 =====
var mailAccountCount = 1;
function addMailAccount() {
    if (mailAccountCount >= 5) { alert('최대 5개까지 추가할 수 있습니다.'); return; }
    mailAccountCount++;
    var wrap = document.getElementById('mailAccountsWrap');
    var suffix = document.getElementById('mailDomainSuffix')?.textContent || '@yourdomain.com';
    var row = document.createElement('div');
    row.className = 'mail-account-row flex items-center gap-2';
    row.innerHTML = '<div class="flex-1 flex items-center border border-gray-300 dark:border-zinc-600 rounded-lg overflow-hidden">'
        + '<input type="text" name="mail_id[]" placeholder="user' + mailAccountCount + '" class="flex-1 px-3 py-2 text-sm bg-white dark:bg-zinc-700 dark:text-white border-0 focus:ring-0 min-w-0">'
        + '<span class="px-3 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 bg-gray-100 dark:bg-zinc-600 border-l border-gray-300 dark:border-zinc-500 whitespace-nowrap mail-domain-suffix">' + suffix + '</span>'
        + '</div>'
        + '<input type="password" name="mail_pw[]" placeholder="비밀번호" class="w-36 px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">'
        + '<button type="button" onclick="this.parentElement.remove();mailAccountCount--" class="p-1 text-red-400 hover:text-red-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>';
    wrap.appendChild(row);
}

// ===== 비즈니스 메일 =====
function toggleBizMail(checked) {
    var wrap = document.getElementById('bizMailAccountsWrap');
    if (wrap) wrap.classList.toggle('hidden', !checked);
    // 도메인 동기화
    if (checked) {
        var suffix = document.getElementById('mailDomainSuffix')?.textContent || '@도메인을 선택하세요';
        document.querySelectorAll('.bizmail-domain-suffix').forEach(function(el) { el.textContent = suffix; });
    }
}

var bizMailAccountCount = 1;
function addBizMailAccount() {
    if (bizMailAccountCount >= 20) { alert('최대 20개까지 추가할 수 있습니다.'); return; }
    bizMailAccountCount++;
    var wrap = document.getElementById('bizMailAccountsWrap');
    var suffix = document.querySelector('.bizmail-domain-suffix')?.textContent || '@도메인을 선택하세요';
    var row = document.createElement('div');
    row.className = 'bizmail-account-row flex items-center gap-2';
    row.innerHTML = '<div class="flex-1 flex items-center border border-gray-300 dark:border-zinc-600 rounded-lg overflow-hidden">'
        + '<input type="text" name="bizmail_id[]" placeholder="user' + bizMailAccountCount + '" class="flex-1 px-3 py-2 text-sm bg-white dark:bg-zinc-700 dark:text-white border-0 focus:ring-0 min-w-0">'
        + '<span class="px-3 py-2 text-sm font-medium text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 border-l border-gray-300 dark:border-zinc-500 whitespace-nowrap bizmail-domain-suffix">' + suffix + '</span>'
        + '</div>'
        + '<input type="password" name="bizmail_pw[]" placeholder="비밀번호" class="w-36 px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-amber-500">'
        + '<button type="button" onclick="this.parentElement.remove();bizMailAccountCount--" class="p-1 text-red-400 hover:text-red-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>';
    wrap.insertBefore(row, wrap.querySelector('button'));
}

// ===== 주문 요약 실시간 업데이트 =====
var hostingPrices = { free: 0, '500mb': 3000, '1g': 5000, '3g': 10000, '5g': 18000, '10g': 30000, '15g': 45000, '20g': 55000, '30g': 80000 };
var hostingLabels = { free: '무료 50MB', '500mb': '500MB', '1g': '1GB', '3g': '3GB', '5g': '5GB', '10g': '10GB', '15g': '15GB', '20g': '20GB', '30g': '30GB' };
var periodDiscounts = { '1': 0, '6': 5, '12': 10, '24': 15, '36': 20, '60': 30 };
var periodLabels = { '1': '1개월', '6': '6개월', '12': '1년', '24': '2년', '36': '3년', '60': '5년' };
var maintenancePrices = { '10000': { label: 'Basic', price: 10000 }, '20000': { label: 'Standard', price: 20000 }, '30000': { label: 'Pro', price: 30000 }, '50000': { label: 'Enterprise', price: 50000 } };

function updateOrderSummary() {
    var items = [];
    var total = 0;

    // 도메인
    var domainTotal = Object.values(selectedDomains).reduce(function(s, p) { return s + p; }, 0);
    if (domainTotal > 0) {
        items.push({ label: '도메인 (' + Object.keys(selectedDomains).length + '개)', amount: domainTotal, note: '/년' });
        total += domainTotal;
    }
    // 무료 서브도메인
    var freeSubVal = document.getElementById('freeSubdomain')?.value;
    var domainOpt = document.querySelector('input[name="domain_option"]:checked')?.value;
    if (domainOpt === 'free' && freeSubVal) {
        items.push({ label: freeSubVal + '.21ces.net', amount: 0, note: '무료' });
    }

    // 호스팅
    var plan = document.querySelector('input[name="hosting_plan"]:checked')?.value || '';
    var period = document.querySelector('input[name="hosting_period"]:checked')?.value || '12';
    if (plan) {
        var monthlyPrice = hostingPrices[plan] || 0;
        var months = parseInt(period) || 1;
        var discount = periodDiscounts[period] || 0;
        var hostingTotal = monthlyPrice * months;
        var discountAmount = Math.floor(hostingTotal * discount / 100);
        var hostingFinal = hostingTotal - discountAmount;

        if (plan === 'free') {
            items.push({ label: '웹 호스팅 (무료 50MB)', amount: 0, note: '1개월' });
        } else {
            items.push({ label: '웹 호스팅 ' + (hostingLabels[plan] || plan) + ' × ' + (periodLabels[period] || period), amount: hostingFinal });
            if (discountAmount > 0) {
                items.push({ label: '장기 할인 (' + discount + '%)', amount: -discountAmount, cls: 'text-green-400' });
            }
        }
        total += hostingFinal;
    }

    // 설치 지원 (무료)
    if (document.querySelector('input[name="addon_install"]:checked')) {
        items.push({ label: '설치 지원', amount: 0, note: '무료' });
    }

    // 기술 지원
    if (document.querySelector('input[name="addon_support"]:checked')) {
        items.push({ label: '기술 지원 (1년)', amount: 120000 });
        total += 120000;
    }

    // 유지보수
    var maint = document.querySelector('input[name="maintenance"]:checked')?.value;
    if (maint && maint !== '0' && maintenancePrices[maint]) {
        var mp = maintenancePrices[maint];
        items.push({ label: '유지보수 ' + mp.label, amount: mp.price, note: '/월' });
        total += mp.price;
    }

    // 비즈니스 메일
    if (document.querySelector('input[name="addon_bizmail"]:checked')) {
        var bizCount = document.querySelectorAll('.bizmail-account-row').length || 1;
        items.push({ label: '비즈니스 메일 × ' + bizCount, amount: 5000 * bizCount, note: '/월' });
        total += 5000 * bizCount;
    }

    // 렌더링
    var summaryItems = document.getElementById('summaryItems');
    var summaryEmpty = document.getElementById('summaryEmpty');
    var summaryTotal = document.getElementById('summaryTotal');
    var summaryTotalAmount = document.getElementById('summaryTotalAmount');
    var submitBtn = document.getElementById('btnSubmitOrder');

    if (items.length === 0) {
        summaryEmpty.classList.remove('hidden');
        summaryItems.classList.add('hidden');
        summaryTotal.classList.add('hidden');
        if (submitBtn) submitBtn.disabled = true;
        return;
    }

    summaryEmpty.classList.add('hidden');
    summaryItems.classList.remove('hidden');
    summaryTotal.classList.remove('hidden');

    var h = '';
    items.forEach(function(item) {
        h += '<div class="flex justify-between">';
        h += '<span class="text-zinc-400">' + item.label + '</span>';
        if (item.amount === 0) {
            h += '<span class="text-green-400">' + (item.note || '무료') + '</span>';
        } else if (item.amount < 0) {
            h += '<span class="' + (item.cls || 'text-green-400') + '">' + formatCurrency(item.amount) + '</span>';
        } else {
            h += '<span>' + formatCurrency(item.amount) + (item.note ? '<span class="text-zinc-500 text-xs">' + item.note + '</span>' : '') + '</span>';
        }
        h += '</div>';
    });
    summaryItems.innerHTML = h;
    summaryTotalAmount.textContent = formatCurrency(total);
    if (submitBtn) submitBtn.disabled = false;
}

// 모든 입력 변경 시 주문 요약 업데이트
document.addEventListener('change', function(e) {
    var t = e.target;
    if (t.name === 'hosting_plan' || t.name === 'hosting_period' || t.name === 'maintenance' ||
        t.name === 'addon_install' || t.name === 'addon_support' || t.name === 'addon_bizmail' || t.name === 'addon_custom') {
        updateOrderSummary();
    }
});

// 초기 표시
document.addEventListener('DOMContentLoaded', function() {
    setCurrency(currentCurrency);
    updateOrderSummary();
});
