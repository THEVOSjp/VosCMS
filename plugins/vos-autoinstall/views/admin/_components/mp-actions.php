<?php
/**
 * 마켓플레이스 공통 액션 컴포넌트
 * 필요 변수: $adminUrl, $_apiUrl, $marketApiBase, $payjpPublicKey
 */
$_mpMarketBase = rtrim($marketApiBase ?? $_ENV['MARKET_API_URL'] ?? 'https://market.21ces.com/api/market', '/');
$_mpApiUrl     = $_apiUrl ?? ($adminUrl . '/autoinstall/api');
$_mpPayjpKey   = $payjpPublicKey ?? '';
?>

<!-- ── 구매 모달 ──────────────────────────────────────────── -->
<div id="mpPurchaseModal" class="fixed inset-0 z-50 hidden flex items-center justify-center p-4">
    <div class="absolute inset-0 bg-black/50" onclick="mpClosePurchase()"></div>
    <div class="relative bg-white dark:bg-zinc-800 rounded-2xl shadow-xl w-full max-w-md p-6 max-h-[90vh] overflow-y-auto">
        <button onclick="mpClosePurchase()" class="absolute top-4 right-4 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-1" id="mpPurchaseTitle">아이템 구매</h3>
        <p class="text-sm text-emerald-600 dark:text-emerald-400 font-bold mb-4" id="mpPurchasePrice"></p>

        <div class="space-y-3">
            <!-- 이메일 -->
            <div>
                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">
                    이메일 <span class="text-zinc-400 font-normal">(영수증·시리얼 키 발송)</span>
                </label>
                <input type="email" id="mpBuyerEmail" placeholder="your@email.com" required
                       class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
            </div>

            <!-- 카드 번호 -->
            <div>
                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">카드 번호</label>
                <div id="mpCardNumber" class="w-full px-3 py-2.5 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white min-h-[42px]"></div>
            </div>

            <!-- 유효기간 + CVC 좌우 배치 -->
            <div class="grid grid-cols-2 gap-3">
                <div>
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">유효기간 <span class="text-zinc-400 font-normal">(MM/YY)</span></label>
                    <div id="mpCardExpiry" class="w-full px-3 py-2.5 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white min-h-[42px]"></div>
                </div>
                <div>
                    <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">보안코드 <span class="text-zinc-400 font-normal">(CVC)</span></label>
                    <div id="mpCardCvc" class="w-full px-3 py-2.5 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white min-h-[42px]"></div>
                </div>
            </div>

            <!-- 할부 -->
            <div>
                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">결제 방식</label>
                <select id="mpInstallment" class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-emerald-500">
                    <option value="0">일시불</option>
                    <?php for ($i = 2; $i <= 12; $i++): ?>
                    <option value="<?= $i ?>"><?= $i ?>회 할부</option>
                    <?php endfor; ?>
                </select>
                <p class="text-[11px] text-zinc-400 mt-1">※ 할부 가능 여부와 수수료는 카드 발급사 정책에 따릅니다.</p>
            </div>

            <!-- 안내 -->
            <div class="flex items-start gap-2 px-3 py-2 rounded-lg bg-zinc-50 dark:bg-zinc-700/50 text-[11px] text-zinc-600 dark:text-zinc-400">
                <svg class="w-3.5 h-3.5 mt-0.5 flex-shrink-0 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z"/></svg>
                <span>카드 정보는 PAY.JP에 안전하게 직접 전송되며, VosCMS 서버에는 저장되지 않습니다.</span>
            </div>
        </div>

        <div id="mpPurchaseError" class="hidden mt-3 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 text-sm text-red-600 dark:text-red-400"></div>
        <div id="mpPurchaseSuccess" class="hidden mt-3 p-3 rounded-lg bg-green-50 dark:bg-green-900/20 text-sm text-green-700 dark:text-green-400"></div>

        <button id="mpPurchaseBtn" onclick="mpSubmitPurchase()"
                class="mt-4 w-full py-3 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-semibold rounded-xl transition-colors text-sm">
            결제하기
        </button>
    </div>
</div>

<script>
// ── 전역 설정 값 ─────────────────────────────────────────────
var _mpMarketBase  = '<?= addslashes($_mpMarketBase) ?>';
var _mpApiUrl      = '<?= addslashes($_mpApiUrl) ?>';
var _mpInstallUrl  = '<?= addslashes($adminUrl . '/autoinstall/install') ?>';
var _mpPayjpKey    = '<?= addslashes($_mpPayjpKey) ?>';
var _mpPayjp        = null;
var _mpCardNumber   = null;
var _mpCardExpiry   = null;
var _mpCardCvc      = null;
var _mpCardMounted  = false;
var _mpSlug         = '';
var _mpAmount       = 0;
var _mpCurrency     = 'JPY';

// 모달이 처음 열릴 때 PAY.JP 엘리먼트 생성 (모달이 hidden 상태에서 미리 mount하면 iframe 0px 문제 발생)
function mpEnsurePayjp() {
    if (_mpCardNumber || !_mpPayjpKey || typeof Payjp === 'undefined') return;
    try {
        _mpPayjp = Payjp(_mpPayjpKey);
        var elements = _mpPayjp.elements();
        var style = {
            base: {
                fontSize: '14px',
                color: '#27272a',
                fontFamily: 'Pretendard, system-ui, sans-serif',
                '::placeholder': { color: '#a1a1aa' }
            }
        };
        _mpCardNumber = elements.create('cardNumber', { style: style, placeholder: '1234 5678 9012 3456' });
        _mpCardExpiry = elements.create('cardExpiry', { style: style });
        _mpCardCvc    = elements.create('cardCvc',    { style: style, placeholder: '3-4 자리' });
    } catch(e) { console.warn('PAY.JP init failed:', e); }
}

// ── 설치 ─────────────────────────────────────────────────────
function mpInstallItem(btn) {
    var slug    = btn.dataset.slug;
    var origTxt = btn.textContent;
    btn.disabled = true;
    btn.textContent = '설치 중…';
    fetch(_mpInstallUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'item_slug=' + encodeURIComponent(slug)
    })
    .then(function(r) { return r.json(); })
    .then(function(d) {
        if (d.success) {
            btn.textContent = '설치됨 ✓';
            btn.style.opacity = '0.6';
            btn.style.cursor  = 'not-allowed';
        } else {
            btn.disabled = false;
            btn.textContent = d.message || '설치 실패';
        }
    })
    .catch(function() {
        btn.disabled = false;
        btn.textContent = origTxt;
        alert('네트워크 오류가 발생했습니다.');
    });
}

// ── 다운로드 ─────────────────────────────────────────────────
function mpDownloadItem(btn) {
    var slug = btn.dataset.slug;
    var key  = btn.dataset.licenseKey || '';
    var url  = _mpMarketBase + '/download?slug=' + encodeURIComponent(slug);
    if (key) url += '&license_key=' + encodeURIComponent(key);
    window.open(url, '_blank');
}

// ── 구매 모달 열기 ───────────────────────────────────────────
function mpOpenPurchase(btn) {
    _mpSlug     = btn.dataset.slug;
    _mpAmount   = parseInt(btn.dataset.price, 10);
    _mpCurrency = btn.dataset.currency || 'JPY';
    document.getElementById('mpPurchaseTitle').textContent = btn.dataset.name + ' 구매';
    document.getElementById('mpPurchasePrice').textContent = btn.dataset.priceLabel;
    var errEl = document.getElementById('mpPurchaseError');
    errEl.classList.add('hidden');
    document.getElementById('mpPurchaseSuccess').classList.add('hidden');
    var pb = document.getElementById('mpPurchaseBtn');
    pb.disabled = false;
    pb.textContent = '결제하기';
    document.getElementById('mpPurchaseModal').classList.remove('hidden');

    // 마켓플레이스에 결제 시스템이 설정되어 있지 않으면 명확히 안내
    if (!_mpPayjpKey) {
        errEl.textContent = '마켓플레이스에 결제 시스템이 설정되지 않았습니다. 운영자에게 문의해주세요.';
        errEl.classList.remove('hidden');
        pb.disabled = true;
        pb.textContent = '결제 불가';
        return;
    }

    mpEnsurePayjp();
    // 다음 페인트 후 mount → iframe 정상 크기로 그려짐
    requestAnimationFrame(function() {
        if (!_mpCardNumber) {
            errEl.textContent = '결제 폼을 초기화할 수 없습니다. 잠시 후 다시 시도해주세요.';
            errEl.classList.remove('hidden');
            pb.disabled = true;
            return;
        }
        try {
            if (_mpCardMounted) {
                try { _mpCardNumber.unmount(); _mpCardExpiry.unmount(); _mpCardCvc.unmount(); } catch(e) {}
                _mpCardMounted = false;
            }
            _mpCardNumber.mount('#mpCardNumber');
            _mpCardExpiry.mount('#mpCardExpiry');
            _mpCardCvc.mount('#mpCardCvc');
            _mpCardMounted = true;
        } catch(e) { console.warn('card mount failed:', e); }
    });
}

// ── 구매 모달 닫기 ───────────────────────────────────────────
function mpClosePurchase() {
    document.getElementById('mpPurchaseModal').classList.add('hidden');
    if (_mpCardMounted) {
        try { _mpCardNumber.unmount(); _mpCardExpiry.unmount(); _mpCardCvc.unmount(); } catch(e) {}
        _mpCardMounted = false;
    }
}

// ── 결제 제출 ────────────────────────────────────────────────
function mpSubmitPurchase() {
    var errEl = document.getElementById('mpPurchaseError');
    var okEl  = document.getElementById('mpPurchaseSuccess');
    var btn   = document.getElementById('mpPurchaseBtn');
    var showErr = function(msg) {
        errEl.textContent = msg;
        errEl.classList.remove('hidden');
        btn.disabled = false; btn.textContent = '결제하기';
    };

    errEl.classList.add('hidden');
    okEl.classList.add('hidden');

    if (!_mpPayjp || !_mpCardNumber) {
        showErr('PAY.JP가 초기화되지 않았습니다. 페이지를 새로고침해주세요.');
        return;
    }
    var email = document.getElementById('mpBuyerEmail').value.trim();
    if (!email || !/^[^@]+@[^@]+\.[^@]+$/.test(email)) {
        showErr('이메일을 정확히 입력해주세요.');
        return;
    }

    btn.disabled = true;
    btn.textContent = '카드 인증 중…';

    _mpPayjp.createToken(_mpCardNumber).then(function(result) {
        if (result.error) {
            showErr(result.error.message || '카드 정보를 다시 확인해주세요.');
            return;
        }
        if (!result.id) {
            showErr('카드 토큰 발급 실패');
            return;
        }
        btn.textContent = '결제 처리 중…';

        var installment = parseInt(document.getElementById('mpInstallment').value || '0', 10);
        var body = new URLSearchParams({
            action:      'purchase_item',
            item_slug:   _mpSlug,
            payjp_token: result.id,
            buyer_email: email,
            installment: installment,
        });

        fetch(_mpApiUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: body.toString()
        })
        .then(function(r) {
            // 응답을 텍스트로 받아 JSON 파싱 실패 시에도 디버그 가능
            return r.text().then(function(txt) {
                try { return { ok: r.ok, status: r.status, data: JSON.parse(txt), raw: null }; }
                catch (e) { return { ok: r.ok, status: r.status, data: null, raw: txt }; }
            });
        })
        .then(function(res) {
            if (!res.data) {
                console.error('Non-JSON response:', res);
                showErr('서버 응답 형식 오류 (HTTP ' + res.status + '). 콘솔을 확인해주세요.');
                return;
            }
            var d = res.data;
            if (d.success) {
                okEl.innerHTML =
                    '구매 완료! 라이선스 키: <strong>' + (d.license_key || '') + '</strong>'
                    + (d.serial_key ? '<br>시리얼: <strong>' + d.serial_key + '</strong>' : '')
                    + '<br><span class="text-xs opacity-70">잠시 후 페이지가 새로고침됩니다…</span>';
                okEl.classList.remove('hidden');
                btn.textContent = '완료';
                setTimeout(function() { window.location.reload(); }, 2500);
            } else {
                showErr(d.message || '결제 실패');
            }
        })
        .catch(function(err) {
            console.error('purchase fetch error:', err);
            showErr('네트워크 오류: ' + (err.message || '요청 실패'));
        });
    }).catch(function(err) {
        console.error('createToken error:', err);
        showErr('카드 토큰 발급 중 오류: ' + (err.message || err));
    });
}
</script>

<?php if ($_mpPayjpKey): ?>
<script src="https://js.pay.jp/v2/pay.js"></script>
<?php endif; ?>
