/**
 * VosCMS 서비스 신청 페이지 JS
 */

// ===== 통화 전환 =====
var currentCurrency = 'KRW';
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

        // 무료 플랜 선택 시 기간/용량 숨기고 안내 표시
        var radio = el.querySelector('input[name="hosting_plan"]');
        var isFree = radio && radio.value === 'free';
        var notice = document.getElementById('freePlanNotice');
        var period = document.getElementById('hostingPeriodWrap');
        var storage = document.getElementById('hostingStorageWrap');
        if (notice) notice.classList.toggle('hidden', !isFree);
        if (period) period.classList.toggle('hidden', isFree);
        if (storage) storage.classList.toggle('hidden', isFree);
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

// 초기 표시
document.addEventListener('DOMContentLoaded', function() { updateCurrencyDisplay(); });
