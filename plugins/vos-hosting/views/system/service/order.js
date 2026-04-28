/**
 * VosCMS 서비스 신청 페이지 JS
 */

// ===== 무료 도메인 헬퍼 =====
function getFreeDomainSuffix() {
    var sel = document.getElementById('freeDomainSelect');
    if (sel) return sel.value;
    return (typeof svcDefaultFreeDomain !== 'undefined') ? svcDefaultFreeDomain : '21ces.net';
}

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

// 이미 표시 통화 단위인 금액에 심볼만 붙이기
function displayPrice(amount) {
    var sym = currencySymbols[currentCurrency] || '';
    var dec = currencyDecimals[currentCurrency] ?? 0;
    var pre = currencyPrefix[currentCurrency] ?? false;
    var neg = amount < 0;
    var abs = Math.abs(amount);
    var formatted = dec > 0 ? abs.toFixed(dec) : Math.round(abs).toLocaleString();
    var result = pre ? (sym + formatted) : (formatted + sym);
    return neg ? '-' + result : result;
}

function updateCurrencyDisplay() {
    document.querySelectorAll('.cur-price').forEach(function(el) {
        el.textContent = formatCurrency(parseFloat(el.dataset.krw) || 0);
    });
    // select option 텍스트 업데이트 (option에는 HTML 불가)
    document.querySelectorAll('select option[data-krw]').forEach(function(opt) {
        var label = opt.dataset.label || '';
        var krw = parseFloat(opt.dataset.krw) || 0;
        opt.textContent = label + ' (' + formatCurrency(krw) + t('hosting.price_per_month') + ')';
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
        var isFree = radio && (parseInt(radio.dataset.price) === 0);
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

// ===== 도메인 검색 (WHOIS) =====
var selectedDomains = {};

function searchDomain() {
    var input = document.getElementById('domainInput').value.trim().toLowerCase().replace(/\.[a-z.]+$/i, '');
    if (!input || input.length < 2) { alert(t('domain.alert_min_2chars')); return; }

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

    fetch(siteBaseUrl + '/plugins/vos-hosting/api/domain-check.php?domain=' + encodeURIComponent(input))
        .then(function(r) { return r.json(); })
        .then(function(data) {
            loading.classList.add('hidden');
            if (!data.success) { list.innerHTML = '<p class="text-sm text-red-500 p-3">' + (data.message || t('domain.search_failed')) + '</p>'; return; }

            data.results.forEach(function(d) {
                var basePrice = (typeof isLoggedIn !== 'undefined' && isLoggedIn && d.vip_price) ? d.vip_price : (d.price || 0);
                var discount = d.discount || 0;
                var finalPrice = discount > 0 ? Math.round(basePrice * (100 - discount) / 100) : basePrice;
                if (d.available === true) {
                    var priceHtml = '';
                    if (discount > 0) {
                        priceHtml = '<div class="text-right">'
                            + '<span class="text-[10px] px-1.5 py-0.5 bg-red-100 dark:bg-red-900/40 text-red-600 dark:text-red-400 rounded-full font-bold mr-1">EVENT -' + discount + '%</span>'
                            + '<span class="text-xs text-gray-400 line-through">' + displayPrice(basePrice) + '</span>'
                            + '<p class="text-sm font-bold text-red-600 dark:text-red-400">' + displayPrice(finalPrice) + '<span class="text-xs font-normal text-gray-400 dark:text-zinc-500">' + t('domain.price_per_year') + '</span></p>'
                            + '<span class="text-[10px] text-gray-400 dark:text-zinc-500">' + t('domain.tax_label') + '</span></div>';
                    } else {
                        priceHtml = '<div class="text-right">'
                            + '<p class="text-sm font-bold text-blue-600">' + displayPrice(basePrice) + '<span class="text-xs font-normal text-gray-400 dark:text-zinc-500">' + t('domain.price_per_year') + '</span></p>'
                            + '<span class="text-[10px] text-gray-400 dark:text-zinc-500">' + t('domain.tax_label') + '</span></div>';
                    }
                    list.innerHTML += '<label class="domain-result flex items-center justify-between p-3 border border-gray-200 dark:border-zinc-600 rounded-xl cursor-pointer transition hover:border-blue-400 dark:hover:border-blue-500 hover:bg-blue-50/50 dark:hover:bg-blue-900/20" data-domain="' + d.fqdn + '" data-price="' + finalPrice + '">'
                        + '<div class="flex items-center gap-3"><input type="checkbox" class="domain-check w-4 h-4 text-blue-600 rounded border-gray-300 dark:border-zinc-500 focus:ring-blue-500" onchange="toggleDomain(\'' + d.fqdn + '\',' + finalPrice + ',this)">'
                        + '<div><p class="text-sm font-semibold text-gray-900 dark:text-white">' + d.fqdn + '</p><p class="text-xs text-green-600">' + t('domain.status_available') + '</p></div></div>'
                        + priceHtml + '</label>';
                } else if (d.available === false) {
                    list.innerHTML += '<div class="flex items-center justify-between p-3 border border-gray-100 dark:border-zinc-700 rounded-xl opacity-40">'
                        + '<div class="flex items-center gap-3"><span class="w-4 h-4 flex items-center justify-center"><svg class="w-3.5 h-3.5 text-red-400" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M4.293 4.293a1 1 0 011.414 0L10 8.586l4.293-4.293a1 1 0 111.414 1.414L11.414 10l4.293 4.293a1 1 0 01-1.414 1.414L10 11.414l-4.293 4.293a1 1 0 01-1.414-1.414L8.586 10 4.293 5.707a1 1 0 010-1.414z"/></svg></span>'
                        + '<div><p class="text-sm text-gray-400 dark:text-zinc-500 line-through">' + d.fqdn + '</p><p class="text-xs text-red-400">' + t('domain.status_taken') + '</p></div></div>'
                        + '<p class="text-xs text-gray-300 dark:text-zinc-600">' + t('domain.status_unavailable') + '</p></div>';
                } else {
                    list.innerHTML += '<div class="flex items-center justify-between p-3 border border-gray-100 dark:border-zinc-700 rounded-xl opacity-30">'
                        + '<div class="flex items-center gap-3"><span class="w-4 h-4"></span>'
                        + '<div><p class="text-sm text-gray-400 dark:text-zinc-500">' + d.fqdn + '</p><p class="text-xs text-zinc-400">' + t('domain.status_unknown') + '</p></div></div>'
                        + '<p class="text-xs text-gray-300 dark:text-zinc-600">-</p></div>';
                }
            });
        })
        .catch(function(err) {
            loading.classList.add('hidden');
            list.innerHTML = '<p class="text-sm text-red-500 p-3">' + t('domain.search_error', {msg: err.message}) + '</p>';
        });
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
    if (typeof updateOrderSummary === 'function') updateOrderSummary();
}

function updateDomainSummary() {
    var count = Object.keys(selectedDomains).length;
    var total = Object.values(selectedDomains).reduce(function(s, p) { return s + p; }, 0);
    var wrap = document.getElementById('domainConfirmWrap');
    if (!wrap) return;
    document.getElementById('domainSelectedCount').textContent = count;
    document.getElementById('domainSelectedTotal').textContent = displayPrice(total);
    if (count > 0) wrap.classList.remove('hidden'); else wrap.classList.add('hidden');
}

function confirmDomains() {
    var list = document.getElementById('domainList');
    var confirmWrap = document.getElementById('domainConfirmWrap');
    var confirmed = document.getElementById('domainConfirmed');
    list.classList.add('hidden');
    confirmWrap.classList.add('hidden');
    confirmed.classList.remove('hidden');
    var h = '<div class="flex items-center justify-between mb-2"><p class="text-xs font-medium text-green-700 dark:text-green-400 uppercase tracking-wider">' + t('domain.selected_label') + '</p>'
        + '<button onclick="resetDomainSearch()" class="text-xs text-blue-600 hover:underline">' + t('domain.search_again') + '</button></div>';
    Object.keys(selectedDomains).forEach(function(name) {
        h += '<div class="flex items-center justify-between p-3 bg-green-50 dark:bg-green-900/20 border border-green-200 dark:border-green-800 rounded-xl">'
            + '<div class="flex items-center gap-2"><svg class="w-4 h-4 text-green-600" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>'
            + '<p class="text-sm font-semibold text-gray-900 dark:text-white">' + name + '</p></div>'
            + '<div class="text-right"><p class="text-sm font-bold text-green-700 dark:text-green-400">' + displayPrice(selectedDomains[name]) + t('domain.price_per_year') + '</p><p class="text-[10px] text-green-600/60">' + t('domain.tax_label') + '</p></div></div>';
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
    if (typeof updateOrderSummary === 'function') updateOrderSummary();
}

// ===== 도메인 옵션 토글 =====
function toggleDomainOption(type) {
    ['domainFree', 'domainSearch', 'domainExisting'].forEach(function(id) {
        var el = document.getElementById(id);
        if (el) el.classList.add('hidden');
    });
    var map = { free: 'domainFree', 'new': 'domainSearch', existing: 'domainExisting' };
    var target = document.getElementById(map[type]);
    if (target) target.classList.remove('hidden');

    // 도메인 전환 시 메일 도메인 리셋
    updateMailDomain(t('domain.select_domain'));
}

// 서브도메인 사용 가능 확인
function checkSubdomain() {
    var input = document.getElementById('freeSubdomain');
    var result = document.getElementById('subdomainResult');
    var val = (input?.value || '').trim().toLowerCase().replace(/[^a-z0-9-]/g, '');
    if (!val || val.length < 2) { result.innerHTML = '<p class="text-xs text-red-500">' + t('domain.subdomain_min_2chars') + '</p>'; result.classList.remove('hidden'); return; }
    input.value = val;
    // 등록 불가 서브도메인 검증
    // 패턴: test* (접두사), *admin (접미사), ns[n] (문자+숫자)
    var blocked = (typeof svcBlockedSubs !== 'undefined') ? svcBlockedSubs : [];
    var isBlocked = blocked.some(function(pattern) {
        if (pattern.includes('[n]')) {
            var prefix = pattern.replace('[n]', '');
            return new RegExp('^' + prefix.replace(/[.*+?^${}()|[\]\\]/g, '\\$&') + '\\d+$').test(val);
        }
        if (pattern.endsWith('*')) return val.startsWith(pattern.slice(0, -1));
        if (pattern.startsWith('*')) return val.endsWith(pattern.slice(1));
        return val === pattern;
    });
    if (isBlocked) {
        result.innerHTML = '<p class="text-xs text-red-500">' + t('domain.subdomain_blocked', {name: '<strong>' + val + '</strong>'}) + '</p>';
        result.classList.remove('hidden');
        return;
    }

    var freeSuffix = getFreeDomainSuffix();
    var fqdn = val + '.' + freeSuffix;
    // 확인 중 표시
    result.innerHTML = '<p class="text-xs text-zinc-500"><svg class="inline w-3.5 h-3.5 animate-spin" fill="none" stroke="currentColor" viewBox="0 0 24 24"><circle cx="12" cy="12" r="10" stroke-width="2" stroke-opacity="0.3"></circle><path d="M12 2a10 10 0 0110 10" stroke-width="2"></path></svg> ' + t('domain.checking') + '</p>';
    result.classList.remove('hidden');

    // 서버 API 호출 — DB(reserved_subdomains) → Cloudflare API
    fetch('/plugins/vos-hosting/api/subdomain-check.php?subdomain=' + encodeURIComponent(val) + '&zone=' + encodeURIComponent(freeSuffix))
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (!d.success) {
                result.innerHTML = '<p class="text-xs text-red-500">' + (d.message || '확인 실패') + '</p>';
                return;
            }
            if (d.available) {
                result.innerHTML = '<p class="text-xs text-green-600 flex items-center gap-1"><svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>' + t('domain.subdomain_available', {fqdn: '<strong>' + fqdn + '</strong>'}) + '</p>';
                // 메일 도메인 업데이트 + 결제 요약 업데이트
                updateMailDomain(fqdn);
                updateOrderSummary();
            } else {
                result.innerHTML = '<p class="text-xs text-red-500">' + t('domain.subdomain_unavailable', {fqdn: '<strong>' + fqdn + '</strong>'}) + '</p>';
            }
        })
        .catch(function(e) {
            result.innerHTML = '<p class="text-xs text-red-500">' + (e.message || '확인 실패') + '</p>';
        });
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
    if (mailAccountCount >= 5) { alert(t('addons.alert_max_mail')); return; }
    mailAccountCount++;
    var wrap = document.getElementById('mailAccountsWrap');
    var suffix = document.getElementById('mailDomainSuffix')?.textContent || '@yourdomain.com';
    var row = document.createElement('div');
    row.className = 'mail-account-row flex items-center gap-2';
    row.innerHTML = '<div class="flex-1 flex items-center border border-gray-300 dark:border-zinc-600 rounded-lg overflow-hidden">'
        + '<input type="text" name="mail_id[]" placeholder="user' + mailAccountCount + '" class="flex-1 px-3 py-2 text-sm bg-white dark:bg-zinc-700 dark:text-white border-0 focus:ring-0 min-w-0">'
        + '<span class="px-3 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 bg-gray-100 dark:bg-zinc-600 border-l border-gray-300 dark:border-zinc-500 whitespace-nowrap mail-domain-suffix">' + suffix + '</span>'
        + '</div>'
        + '<div class="relative w-36">'
        + '<input type="password" name="mail_pw[]" placeholder="' + t('addons.pw_placeholder') + '" class="w-full px-3 py-2 pr-9 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500">'
        + '<button type="button" onclick="togglePw(this)" class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200" title="show/hide">👁</button>'
        + '</div>'
        + '<button type="button" onclick="this.parentElement.remove();mailAccountCount--" class="p-1 text-red-400 hover:text-red-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>';
    wrap.appendChild(row);
}

// ===== 비즈니스 메일 =====
// ===== 설치 지원 폼 토글 =====
function toggleInstallForm(checked) {
    var wrap = document.getElementById('installAdminFormWrap');
    if (wrap) wrap.classList.toggle('hidden', !checked);
}

// ===== 비밀번호 표시/숨김 토글 =====
function togglePw(btn) {
    var input = btn.parentElement.querySelector('input');
    if (!input) return;
    if (input.type === 'password') {
        input.type = 'text';
        btn.textContent = '🙈';
    } else {
        input.type = 'password';
        btn.textContent = '👁';
    }
}

function toggleBizMail(checked) {
    var wrap = document.getElementById('bizMailAccountsWrap');
    if (wrap) wrap.classList.toggle('hidden', !checked);
    // 도메인 동기화
    if (checked) {
        var suffix = document.getElementById('mailDomainSuffix')?.textContent || ('@' + t('domain.select_domain'));
        document.querySelectorAll('.bizmail-domain-suffix').forEach(function(el) { el.textContent = suffix; });
    }
    if (typeof updateOrderSummary === 'function') updateOrderSummary();
}

var bizMailAccountCount = 1;
function addBizMailAccount() {
    if (bizMailAccountCount >= 20) { alert(t('addons.alert_max_bizmail')); return; }
    bizMailAccountCount++;
    var wrap = document.getElementById('bizMailAccountsWrap');
    var suffix = document.querySelector('.bizmail-domain-suffix')?.textContent || ('@' + t('domain.select_domain'));
    var row = document.createElement('div');
    row.className = 'bizmail-account-row flex items-center gap-2';
    row.innerHTML = '<div class="flex-1 flex items-center border border-gray-300 dark:border-zinc-600 rounded-lg overflow-hidden">'
        + '<input type="text" name="bizmail_id[]" placeholder="user' + bizMailAccountCount + '" class="flex-1 px-3 py-2 text-sm bg-white dark:bg-zinc-700 dark:text-white border-0 focus:ring-0 min-w-0">'
        + '<span class="px-3 py-2 text-sm font-medium text-amber-600 dark:text-amber-400 bg-amber-50 dark:bg-amber-900/30 border-l border-gray-300 dark:border-zinc-500 whitespace-nowrap bizmail-domain-suffix">' + suffix + '</span>'
        + '</div>'
        + '<div class="relative w-36">'
        + '<input type="password" name="bizmail_pw[]" placeholder="' + t('addons.pw_placeholder') + '" class="w-full px-3 py-2 pr-9 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-amber-500">'
        + '<button type="button" onclick="togglePw(this)" class="absolute right-2 top-1/2 -translate-y-1/2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200" title="show/hide">👁</button>'
        + '</div>'
        + '<button type="button" onclick="this.parentElement.remove();bizMailAccountCount--;if(typeof updateOrderSummary===\'function\')updateOrderSummary()" class="p-1 text-red-400 hover:text-red-600"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>';
    wrap.insertBefore(row, wrap.querySelector('button[onclick*="addBizMailAccount"]'));
    if (typeof updateOrderSummary === 'function') updateOrderSummary();
}

// ===== 주문 요약 실시간 업데이트 =====
function _getPlanInfo(value) {
    if (typeof svcHostingPlans === 'undefined') return null;
    for (var i = 0; i < svcHostingPlans.length; i++) {
        var p = svcHostingPlans[i];
        var val = (p.capacity || '').replace(/\s/g, '').toLowerCase();
        if (val === value || (parseInt(p.price) === 0 && value === 'free')) return p;
    }
    return null;
}

function _getPeriodDiscount(months) {
    if (typeof svcHostingPeriods === 'undefined') return 0;
    for (var i = 0; i < svcHostingPeriods.length; i++) {
        if (parseInt(svcHostingPeriods[i].months) === parseInt(months)) return parseInt(svcHostingPeriods[i].discount) || 0;
    }
    return 0;
}

function _getPeriodLabel(months) {
    var m = parseInt(months);
    return m >= 12 ? (m / 12) + t('hosting.unit_year') : m + t('hosting.unit_month');
}

function updateOrderSummary() {
    var rows = []; // { label, qty, unit, amount, cls, group }
    var total = 0;

    // 계약 기간 정보
    var period = document.querySelector('input[name="hosting_period"]:checked')?.value || '12';
    var months = parseInt(period) || 1;
    var discount = _getPeriodDiscount(months);
    var periodLabel = _getPeriodLabel(period);

    // 도메인 — 계약 기간 1년 이하면 1년, 그 이상이면 해당 년수
    var domainYears = Math.max(1, Math.ceil(months / 12));
    var domainKeys = Object.keys(selectedDomains);
    if (domainKeys.length > 0) {
        var domainPerYear = Object.values(selectedDomains).reduce(function(s, p) { return s + p; }, 0);
        var domainTotal = domainPerYear * domainYears;
        var qtyLabel = t('summary.domain_qty', { count: domainKeys.length, years: domainYears });
        rows.push({ label: t('summary.domain_label'), qty: qtyLabel, unit: '', amount: domainTotal, group: 'domain' });
        total += domainTotal;
    }
    var freeSubVal = document.getElementById('freeSubdomain')?.value;
    var domainOpt = document.querySelector('input[name="domain_option"]:checked')?.value;
    if (domainOpt === 'free' && freeSubVal) {
        rows.push({ label: freeSubVal + '.' + getFreeDomainSuffix(), qty: '', unit: '', amount: 0, free: true, group: 'domain' });
    }

    // 호스팅
    var planEl = document.querySelector('input[name="hosting_plan"]:checked');
    if (planEl) {
        var planValue = planEl.value;
        var planInfo = _getPlanInfo(planValue);
        var monthlyPrice = planInfo ? parseInt(planInfo.price) || 0 : (parseInt(planEl.dataset.price) || 0);
        var planLabel = planInfo ? (planInfo.capacity || planValue) : planValue;

        if (monthlyPrice === 0) {
            rows.push({ label: t('summary.hosting_label_prefix') + planLabel, qty: 1, unit: t('hosting.unit_month'), amount: 0, free: true, group: 'hosting' });
        } else {
            var hostingTotal = monthlyPrice * months;
            rows.push({ label: t('summary.hosting_label_prefix') + planLabel, unit_price: monthlyPrice, qty: months, unit: t('hosting.unit_month'), amount: hostingTotal, group: 'hosting' });
            if (discount > 0) {
                var discountAmount = Math.floor(hostingTotal * discount / 100);
                rows.push({ label: t('summary.discount_long', { pct: discount }), qty: '', unit: '', amount: -discountAmount, cls: 'text-green-600 dark:text-green-400', group: 'hosting' });
                total += hostingTotal - discountAmount;
            } else {
                total += hostingTotal;
            }
        }
    }

    // 추가 용량
    var storageSelect = document.querySelector('select[name="hosting_storage"]');
    if (storageSelect && storageSelect.value && storageSelect.value !== '0') {
        var stOpt = storageSelect.options[storageSelect.selectedIndex];
        var stPrice = parseInt(stOpt?.dataset?.price) || 0;
        var stLabel = stOpt?.dataset?.label || '';
        if (stPrice > 0) {
            var stTotal = stPrice * months;
            var stDiscount = discount > 0 ? Math.floor(stTotal * discount / 100) : 0;
            rows.push({ label: t('summary.storage_label_prefix') + stLabel, unit_price: stPrice, qty: months, unit: t('hosting.unit_month'), amount: stTotal, group: 'hosting' });
            if (stDiscount > 0) {
                rows.push({ label: t('summary.discount', { pct: discount }), qty: '', unit: '', amount: -stDiscount, cls: 'text-green-600 dark:text-green-400', group: 'hosting' });
                total += stTotal - stDiscount;
            } else {
                total += stTotal;
            }
        }
    }

    // 부가 서비스
    if (typeof svcAddons !== 'undefined') {
        var checkedAddons = document.querySelectorAll('input[type="checkbox"][name^="addon_"]:checked');
        checkedAddons.forEach(function(cb) {
            var container = cb.closest('label') || cb.closest('div');
            var labelEl = container?.querySelector('.font-semibold, p.font-semibold');
            if (!labelEl) return;
            var labelText = labelEl.textContent.trim();

            var matched = null;
            for (var i = 0; i < svcAddons.length; i++) {
                if (svcAddons[i].label === labelText) { matched = svcAddons[i]; break; }
            }
            if (!matched) return;

            var price = parseInt(matched.price) || 0;
            var unit = (matched.unit || '').trim();
            var isBizmail = labelText.indexOf('비즈니스 메일') !== -1 || labelText.indexOf('ビジネスメール') !== -1 || cb.name === 'addon_bizmail';
            var isMonthly = unit.indexOf('/월') !== -1 || unit.indexOf('/계정/월') !== -1;
            var isYearly = unit.indexOf('/년') !== -1;

            if (isBizmail) {
                var bizCount = document.querySelectorAll('.bizmail-account-row').length || 1;
                var bizTotal = price * bizCount * months;
                var bizDiscount = discount > 0 ? Math.floor(bizTotal * discount / 100) : 0;
                rows.push({ label: labelText, unit_price: price, qty: t('summary.bizmail_qty', { count: bizCount, months: months }), unit: '', amount: bizTotal, group: 'addon' });
                if (bizDiscount > 0) {
                    rows.push({ label: t('summary.discount', { pct: discount }), qty: '', unit: '', amount: -bizDiscount, cls: 'text-green-600 dark:text-green-400', group: 'addon' });
                    total += bizTotal - bizDiscount;
                } else {
                    total += bizTotal;
                }
            } else if (price > 0 && isMonthly) {
                // 월 서비스 → 계약 기간 적용
                var monthTotal = price * months;
                var monthDiscount = discount > 0 ? Math.floor(monthTotal * discount / 100) : 0;
                rows.push({ label: labelText, unit_price: price, qty: months, unit: t('hosting.unit_month'), amount: monthTotal, group: 'addon' });
                if (monthDiscount > 0) {
                    rows.push({ label: t('summary.discount', { pct: discount }), qty: '', unit: '', amount: -monthDiscount, cls: 'text-green-600 dark:text-green-400', group: 'addon' });
                    total += monthTotal - monthDiscount;
                } else {
                    total += monthTotal;
                }
            } else if (price > 0 && isYearly) {
                // 연 서비스 → 계약 기간(년) 적용
                var years = Math.max(1, Math.ceil(months / 12));
                var yearTotal = price * years;
                rows.push({ label: labelText, unit_price: price, qty: years, unit: t('hosting.unit_year'), amount: yearTotal, group: 'addon' });
                total += yearTotal;
            } else if (price > 0) {
                rows.push({ label: labelText, qty: 1, unit: '', amount: price, group: 'addon' });
                total += price;
            } else if (unit === '별도 견적') {
                rows.push({ label: labelText, qty: '', unit: '', amount: 0, note: t('summary.price_quote_note'), group: 'addon' });
            } else {
                rows.push({ label: labelText, qty: '', unit: '', amount: 0, free: true, group: 'addon' });
            }
        });
    }

    // 유지보수 — 월 서비스 → 계약 기간 적용
    var maint = document.querySelector('input[name="maintenance"]:checked')?.value;
    if (maint && maint !== '0' && typeof svcMaintenance !== 'undefined') {
        for (var i = 0; i < svcMaintenance.length; i++) {
            if (String(svcMaintenance[i].price) === maint) {
                var mp = parseInt(svcMaintenance[i].price);
                var maintTotal = mp * months;
                var maintDiscount = discount > 0 ? Math.floor(maintTotal * discount / 100) : 0;
                rows.push({ label: t('summary.maint_label_prefix') + svcMaintenance[i].label, unit_price: mp, qty: months, unit: t('hosting.unit_month'), amount: maintTotal, group: 'addon' });
                if (maintDiscount > 0) {
                    rows.push({ label: t('summary.discount', { pct: discount }), qty: '', unit: '', amount: -maintDiscount, cls: 'text-green-600 dark:text-green-400', group: 'addon' });
                    total += maintTotal - maintDiscount;
                } else {
                    total += maintTotal;
                }
                break;
            }
        }
    }

    // === 렌더링 ===
    var summaryItems = document.getElementById('summaryItems');
    var summaryEmpty = document.getElementById('summaryEmpty');
    var summaryTotal = document.getElementById('summaryTotal');
    var summaryTotalAmount = document.getElementById('summaryTotalAmount');
    var submitBtn = document.getElementById('btnSubmitOrder');

    if (rows.length === 0) {
        summaryEmpty.classList.remove('hidden');
        summaryItems.classList.add('hidden');
        summaryTotal.classList.add('hidden');
        if (submitBtn) submitBtn.disabled = true;
        return;
    }

    summaryEmpty.classList.add('hidden');
    summaryItems.classList.remove('hidden');
    summaryTotal.classList.remove('hidden');

    // 헤더
    var h = '<div class="grid grid-cols-12 gap-1 text-[10px] text-zinc-500 pb-1 border-b border-zinc-700 mb-1">'
        + '<div class="col-span-4">' + t('summary.col_product') + '</div><div class="col-span-4 text-right">' + t('summary.col_qty') + '</div><div class="col-span-4 text-right">' + t('summary.col_amount') + '</div></div>';

    var prevGroup = '';
    rows.forEach(function(row) {
        if (prevGroup && row.group !== prevGroup) {
            h += '<div class="border-t border-zinc-700/50 my-1.5"></div>';
        }
        prevGroup = row.group || '';

        h += '<div class="grid grid-cols-12 gap-1 py-0.5 text-sm">';
        // 상품
        h += '<div class="col-span-4 ' + (row.cls || 'text-zinc-400') + ' truncate">' + row.label + '</div>';
        // 단가 × 수량+단위
        h += '<div class="col-span-4 text-right text-zinc-500 text-xs">';
        if (row.qty) {
            if (row.unit_price) {
                h += displayPrice(row.unit_price) + ' × ';
            }
            h += row.qty + (row.unit ? '<span class="ml-0.5">' + row.unit + '</span>' : '');
        }
        h += '</div>';
        // 금액
        h += '<div class="col-span-4 text-right">';
        if (row.free) {
            h += '<span class="text-green-600 dark:text-green-400">' + t('summary.free') + '</span>';
        } else if (row.note) {
            h += '<span class="text-zinc-500">' + row.note + '</span>';
        } else if (row.amount < 0) {
            h += '<span class="' + (row.cls || 'text-green-600 dark:text-green-400') + '">' + displayPrice(row.amount) + '</span>';
        } else if (row.amount > 0) {
            h += '<span class="text-white">' + displayPrice(row.amount) + '</span>';
        }
        h += '</div></div>';
    });

    // 소계
    h += '<div class="border-t border-zinc-700 my-2"></div>';
    h += '<div class="flex justify-between text-sm"><span class="text-zinc-300">' + t('summary.subtotal') + '</span><span class="text-zinc-300">' + displayPrice(total) + '</span></div>';

    // 부가세 (10%)
    var tax = Math.round(total * 0.1);
    h += '<div class="flex justify-between text-sm mt-1"><span class="text-zinc-400">' + t('summary.vat') + '</span><span class="text-zinc-400">' + displayPrice(tax) + '</span></div>';

    // 합계 + 최종 결제 금액 (반올림 적용)
    var sum = total + tax;
    var roundUnit = parseFloat((typeof svcRounding !== 'undefined' && svcRounding[currentCurrency]) ? svcRounding[currentCurrency] : 1) || 1;
    var grandTotal = sum;

    h += '<div class="border-t border-zinc-700 my-2"></div>';
    h += '<div class="flex justify-between text-sm"><span class="text-zinc-300">' + t('summary.total') + '</span><span class="text-zinc-300">' + displayPrice(sum) + '</span></div>';
    h += '<div class="border-t border-zinc-700 my-2"></div>';
    h += '<div class="flex justify-between items-center"><div><span class="text-lg font-bold text-white">' + t('summary.final_amount') + '</span><span class="text-xs text-zinc-500 ml-2">' + t('summary.final_amount_note') + '</span></div><span class="text-2xl font-bold text-blue-400">' + displayPrice(grandTotal) + '</span></div>';

    summaryItems.innerHTML = h;
    summaryTotal.classList.add('hidden');

    // 0원이면 버튼 텍스트 + 결제 섹션 변경
    var submitBtn = document.getElementById('btnSubmitOrder');
    var paymentSection = document.querySelector('input[name="payment"]')?.closest('section');
    if (grandTotal <= 0) {
        if (submitBtn) submitBtn.textContent = t('checkout.btn_submit');
        if (paymentSection) paymentSection.classList.add('hidden');
    } else {
        if (submitBtn && submitBtn.textContent === t('checkout.btn_submit')) submitBtn.textContent = t('checkout.btn_pay');
        if (paymentSection) paymentSection.classList.remove('hidden');
    }

    if (typeof updateSubmitButton === 'function') updateSubmitButton();
}

// 모든 입력 변경 시 주문 요약 업데이트
document.addEventListener('change', function(e) {
    var el = e.target;
    if (el.name === 'hosting_plan' || el.name === 'hosting_period' || el.name === 'maintenance' ||
        el.name === 'domain_option' || el.name === 'hosting_storage' || el.name === 'existing_domain' ||
        el.id === 'freeSubdomain' || el.id === 'freeDomainSelect' ||
        (el.name && el.name.startsWith('addon_'))) {
        updateOrderSummary();
    }
});

// ===== 결제하기 (주문 제출) =====
function submitOrder() {
    var submitBtn = document.getElementById('btnSubmitOrder');
    if (!submitBtn || submitBtn.disabled) return;

    // 약관 동의 확인
    var agreeCheck = document.getElementById('agreeTerms');
    if (agreeCheck && !agreeCheck.checked) {
        alert(t('checkout.alert_terms'));
        return;
    }

    var paymentMethod = document.querySelector('input[name="payment"]:checked')?.value || 'card';

    // 주문 데이터 수집
    var orderData = {
        payment_method: paymentMethod,
        domain_option: document.querySelector('input[name="domain_option"]:checked')?.value || 'free',
        domain: '',
        hosting_plan: document.querySelector('input[name="hosting_plan"]:checked')?.value || '',
        contract_months: parseInt(document.querySelector('input[name="hosting_period"]:checked')?.value || '12'),
        domains: selectedDomains,
        addons: [],
        maintenance: '',
        bizmail_count: 0,
        applicant: {
            name: document.querySelector('[name="name"]')?.value || '',
            email: document.querySelector('[name="email"]')?.value || '',
            phone: document.querySelector('[name="phone"]')?.value || '',
            company: document.querySelector('[name="company"]')?.value || '',
            category: document.querySelector('[name="site_category"]')?.value || '',
        }
    };

    // 도메인
    if (orderData.domain_option === 'free') {
        orderData.domain = (document.getElementById('freeSubdomain')?.value || '') + '.' + getFreeDomainSuffix();
    } else if (orderData.domain_option === 'existing') {
        orderData.domain = document.querySelector('[name="existing_domain"]')?.value || '';
    } else if (orderData.domain_option === 'new' && Object.keys(selectedDomains).length > 0) {
        orderData.domain = Object.keys(selectedDomains)[0];
    }

    // 선택된 부가서비스
    document.querySelectorAll('input[type="checkbox"][name^="addon_"]:checked').forEach(function(cb) {
        var container = cb.closest('label') || cb.closest('div');
        var labelEl = container?.querySelector('.font-semibold');
        if (labelEl) orderData.addons.push(labelEl.textContent.trim());
    });

    // 유지보수
    var maint = document.querySelector('input[name="maintenance"]:checked');
    if (maint && maint.value !== '0') {
        // label 텍스트 찾기
        var maintLabel = maint.closest('label')?.querySelector('.font-medium');
        if (maintLabel) orderData.maintenance = maintLabel.textContent.trim();
    }

    // 기본 메일 계정 수집
    var mailAccounts = [];
    document.querySelectorAll('.mail-account-row').forEach(function(row) {
        var id = row.querySelector('[name="mail_id[]"]')?.value || '';
        var pw = row.querySelector('[name="mail_pw[]"]')?.value || '';
        var domain = row.querySelector('.mail-domain-suffix')?.textContent || '';
        if (id) mailAccounts.push({ address: id + domain, password: pw });
    });
    orderData.mail_accounts = mailAccounts;

    // 비즈니스 메일 계정 수집
    var bizmailAccounts = [];
    document.querySelectorAll('.bizmail-account-row').forEach(function(row) {
        var id = row.querySelector('[name="bizmail_id[]"]')?.value || '';
        var pw = row.querySelector('[name="bizmail_pw[]"]')?.value || '';
        var domain = row.querySelector('.bizmail-domain-suffix')?.textContent || '';
        if (id) bizmailAccounts.push({ address: id + domain, password: pw });
    });
    orderData.bizmail_count = bizmailAccounts.length;
    orderData.bizmail_accounts = bizmailAccounts;

    // 설치 지원 관리자 정보 수집
    var iCheck = document.querySelector('input[name="addon_install"]');
    if (iCheck && iCheck.checked) {
        orderData.install_info = {
            admin_id: (document.querySelector('[name="install_admin_id"]')?.value || '').trim(),
            admin_email: (document.querySelector('[name="install_admin_email"]')?.value || '').trim(),
            admin_pw: (document.querySelector('[name="install_admin_pw"]')?.value || '').trim(),
            site_title: (document.querySelector('[name="install_site_title"]')?.value || '').trim(),
        };
    }

    submitBtn.disabled = true;
    submitBtn.textContent = t('checkout.btn_processing');

    // 무료 주문: 결제 없이 바로 제출
    var paymentSection = document.querySelector('input[name="payment"]')?.closest('section');
    var isFree = paymentSection && paymentSection.classList.contains('hidden');
    if (isFree) {
        orderData.payment_method = 'free';
        sendOrder(orderData);
        return;
    }

    // 카드 결제: 토큰 생성 후 API 호출
    if (paymentMethod === 'card') {
        var tokenPromise;
        if (typeof createPayjpToken === 'function') {
            tokenPromise = createPayjpToken();
        } else if (typeof createStripeToken === 'function') {
            tokenPromise = createStripeToken();
        } else {
            alert(t('checkout.alert_no_payment_init'));
            submitBtn.disabled = false;
            submitBtn.textContent = t('checkout.btn_pay');
            return;
        }

        tokenPromise.then(function(token) {
            if (!token || token.length < 10) {
                throw new Error(t('checkout.alert_card_required'));
            }
            orderData.payment_token = token;
            return sendOrder(orderData);
        }).catch(function(err) {
            alert(t('checkout.alert_card_error', { msg: err.message }));
            submitBtn.disabled = false;
            submitBtn.textContent = t('checkout.btn_pay');
        });
    } else {
        // 계좌이체
        sendOrder(orderData);
    }
}

function sendOrder(orderData) {
    return fetch(siteBaseUrl + '/plugins/vos-hosting/api/service-order.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(orderData)
    })
    .then(function(r) { return r.json(); })
    .then(function(data) {
        var submitBtn = document.getElementById('btnSubmitOrder');
        if (data.success) {
            // 모달 DOM 제거 + body 정리 (보험)
            var modal = document.getElementById('orderConfirmModal');
            if (modal && modal.parentNode) modal.parentNode.removeChild(modal);
            document.body.style.overflow = '';
            document.documentElement.style.overflow = '';
            // 완료 페이지로 이동
            window.location.href = siteBaseUrl + '/service/order/complete?order=' + encodeURIComponent(data.order_number);
        } else {
            alert(data.message || t('checkout.alert_order_failed'));
            submitBtn.disabled = false;
            submitBtn.textContent = t('checkout.btn_pay');
        }
    })
    .catch(function(err) {
        alert(t('checkout.alert_order_request_failed', { msg: err.message }));
        var submitBtn = document.getElementById('btnSubmitOrder');
        submitBtn.disabled = false;
        submitBtn.textContent = t('checkout.btn_pay');
    });
}

// ===== 주문 확인 모달 =====
function showOrderConfirmModal() {
    // 약관 동의 체크
    var agreeCheck = document.getElementById('agreeTerms');
    if (agreeCheck && !agreeCheck.checked) {
        alert(t('checkout.alert_terms'));
        return;
    }
    // 호스팅 플랜 선택 체크
    var planEl = document.querySelector('input[name="hosting_plan"]:checked');
    if (!planEl) return;

    // 도메인 검증
    var domainOpt = document.querySelector('input[name="domain_option"]:checked')?.value;
    if (domainOpt === 'free') {
        var freeSub = (document.getElementById('freeSubdomain')?.value || '').trim();
        if (!freeSub) { alert(t('checkout.alert_subdomain_empty')); return; }
    } else if (domainOpt === 'existing') {
        var existDomain = (document.querySelector('[name="existing_domain"]')?.value || '').trim();
        if (!existDomain) { alert(t('checkout.alert_domain_empty')); return; }
    } else if (domainOpt === 'new') {
        // 도메인 구입은 선택 안 해도 통과 (호스팅만 신청 가능 케이스 허용)
    }

    // 신청자 정보 검증 (비회원: 이름/이메일/비번/연락처, 회원: 연락처)
    var phoneEl = document.querySelector('[name="phone"]');
    var phoneVal = phoneEl ? phoneEl.value.trim() : '';
    if (!isLoggedIn) {
        var nameVal = (document.querySelector('[name="name"]')?.value || '').trim();
        var emailVal = (document.querySelector('[name="email"]')?.value || '').trim();
        var pwVal = (document.querySelector('[name="password"]')?.value || '').trim();
        if (!nameVal || !emailVal || !pwVal || !phoneVal) {
            alert(t('checkout.alert_applicant_required'));
            return;
        }
    } else {
        if (!phoneVal) {
            alert(t('checkout.alert_applicant_required'));
            return;
        }
    }

    // 설치 지원 검증 (체크 시 관리자 ID/비번/이메일 필수)
    var installCheck = document.querySelector('input[name="addon_install"]');
    if (installCheck && installCheck.checked) {
        var iId = (document.querySelector('[name="install_admin_id"]')?.value || '').trim();
        var iEmail = (document.querySelector('[name="install_admin_email"]')?.value || '').trim();
        var iPw = (document.querySelector('[name="install_admin_pw"]')?.value || '').trim();
        if (!iId || !iEmail || !iPw) {
            alert(t('checkout.alert_install_required'));
            return;
        }
    }

    // 표시 전 inline display 초기화 (close 시 강제 적용된 'none' 해제)
    var modalEl = document.getElementById('orderConfirmModal');
    if (modalEl) modalEl.style.display = '';

    // 신청 서비스 (주문 요약 패널 복제 + 다크 → 라이트 색상 변환)
    var summaryItems = document.getElementById('summaryItems');
    var confirmServices = document.getElementById('confirmServices');
    if (summaryItems && confirmServices) {
        var html = summaryItems.innerHTML;
        // 다크 테마 색상 → 라이트 테마 색상 변환 (모달 가독성)
        html = html
            .replace(/\btext-white\b/g, 'text-gray-900')
            .replace(/\btext-zinc-300\b/g, 'text-gray-800')
            .replace(/\btext-zinc-400\b/g, 'text-gray-600')
            .replace(/\btext-zinc-500\b/g, 'text-gray-500')
            .replace(/\btext-zinc-600\b/g, 'text-gray-500')
            .replace(/\btext-blue-400\b/g, 'text-blue-600')
            .replace(/\bborder-zinc-700(\/\d+)?\b/g, 'border-gray-300');
        confirmServices.innerHTML = html;
    }

    // 신청자 정보
    var nameEl = document.querySelector('[name="name"]');
    var emailEl = document.querySelector('[name="email"]');
    var phoneEl = document.querySelector('[name="phone"]');
    var applicantHtml = '';
    function row(label, value) {
        if (!value) return '';
        return '<div class="flex justify-between"><span class="text-xs text-gray-500 dark:text-zinc-400">' + label + '</span><span class="font-medium">' + value + '</span></div>';
    }
    var nameVal = nameEl ? nameEl.value.trim() : '';
    var emailVal = emailEl ? emailEl.value.trim() : '';
    var phoneVal = phoneEl ? phoneEl.value.trim() : '';
    applicantHtml += row(t('applicant.name_label'), nameVal);
    applicantHtml += row(t('applicant.email_label'), emailVal);
    applicantHtml += row(t('applicant.phone_label'), phoneVal);
    document.getElementById('confirmApplicant').innerHTML = applicantHtml || '<span class="text-xs text-gray-400">-</span>';

    // 결제 정보
    var paymentSection = document.querySelector('input[name="payment"]')?.closest('section');
    var isFree = paymentSection && paymentSection.classList.contains('hidden');
    var payHtml = '';
    if (isFree) {
        payHtml = '<div class="flex justify-between"><span class="text-xs text-gray-500 dark:text-zinc-400">' + t('payment.title') + '</span><span class="font-semibold text-green-600">' + t('confirm.free_order') + '</span></div>';
    } else {
        var payMethod = document.querySelector('input[name="payment"]:checked')?.value || 'card';
        var paymentLabel = payMethod === 'card' ? t('payment.method_card') : t('payment.method_bank');
        payHtml = '<div class="flex justify-between"><span class="text-xs text-gray-500 dark:text-zinc-400">' + t('payment.title') + '</span><span class="font-semibold">' + paymentLabel + '</span></div>';
    }
    document.getElementById('confirmPayment').innerHTML = payHtml;

    // 모달 표시
    document.getElementById('orderConfirmModal').classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeOrderConfirmModal() {
    var modal = document.getElementById('orderConfirmModal');
    if (modal) {
        modal.classList.add('hidden');
        modal.style.display = 'none';
    }
    document.body.style.overflow = '';
    document.documentElement.style.overflow = '';
}

function confirmAndPay() {
    closeOrderConfirmModal();
    submitOrder();
}

// 초기 표시
document.addEventListener('DOMContentLoaded', function() {
    setCurrency(currentCurrency);
    updateOrderSummary();

    // 결제하기 버튼 연결 — 확인 모달 표시
    var submitBtn = document.getElementById('btnSubmitOrder');
    if (submitBtn) {
        submitBtn.addEventListener('click', function(e) {
            e.preventDefault();
            showOrderConfirmModal();
        });
    }

    // ESC 키로 모달 닫기
    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            var modal = document.getElementById('orderConfirmModal');
            if (modal && !modal.classList.contains('hidden')) closeOrderConfirmModal();
        }
    });
});
