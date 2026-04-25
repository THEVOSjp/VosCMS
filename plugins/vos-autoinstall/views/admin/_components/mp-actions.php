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
    <div class="relative bg-white dark:bg-zinc-800 rounded-2xl shadow-xl w-full max-w-md p-6">
        <button onclick="mpClosePurchase()" class="absolute top-4 right-4 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <h3 class="text-lg font-semibold text-zinc-800 dark:text-zinc-200 mb-1" id="mpPurchaseTitle">아이템 구매</h3>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4" id="mpPurchasePrice"></p>
        <div class="space-y-3">
            <div>
                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">이메일 (영수증·시리얼 키 발송)</label>
                <input type="email" id="mpBuyerEmail" placeholder="your@email.com"
                       class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
            <div>
                <label class="block text-xs font-medium text-zinc-600 dark:text-zinc-400 mb-1">카드 정보</label>
                <div id="mpCardForm" class="w-full px-3 py-3 rounded-lg border border-zinc-300 dark:border-zinc-600 bg-white min-h-[48px]"></div>
            </div>
        </div>
        <div id="mpPurchaseError" class="hidden mt-3 p-3 rounded-lg bg-red-50 dark:bg-red-900/20 text-sm text-red-600 dark:text-red-400"></div>
        <div id="mpPurchaseSuccess" class="hidden mt-3 p-3 rounded-lg bg-green-50 dark:bg-green-900/20 text-sm text-green-700 dark:text-green-400"></div>
        <button id="mpPurchaseBtn" onclick="mpSubmitPurchase()"
                class="mt-4 w-full py-2.5 px-4 bg-emerald-600 hover:bg-emerald-700 text-white font-medium rounded-xl transition-colors text-sm">
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
var _mpCardEl       = null;
var _mpCardMounted  = false;
var _mpPayjpKey     = '<?= addslashes($_mpPayjpKey) ?>';
var _mpSlug         = '';
var _mpAmount       = 0;
var _mpCurrency     = 'JPY';

// 모달이 처음 열릴 때 PAY.JP 엘리먼트 생성 (모달이 hidden 상태에서 미리 mount하면 iframe 0px 문제 발생)
function mpEnsurePayjp() {
    if (_mpCardEl || !_mpPayjpKey || typeof Payjp === 'undefined') return;
    try {
        _mpPayjp  = Payjp(_mpPayjpKey);
        _mpCardEl = _mpPayjp.elements().create('card');
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
    document.getElementById('mpPurchaseError').classList.add('hidden');
    document.getElementById('mpPurchaseSuccess').classList.add('hidden');
    var pb = document.getElementById('mpPurchaseBtn');
    pb.disabled = false;
    pb.textContent = '결제하기';
    // 모달부터 표시 (PAY.JP iframe 렌더링을 위해 컨테이너가 화면에 있어야 함)
    document.getElementById('mpPurchaseModal').classList.remove('hidden');
    mpEnsurePayjp();
    // 다음 페인트 후 mount → iframe 정상 크기로 그려짐
    requestAnimationFrame(function() {
        if (!_mpCardEl) return;
        try {
            if (_mpCardMounted) { try { _mpCardEl.unmount(); } catch(e) {} _mpCardMounted = false; }
            _mpCardEl.mount('#mpCardForm');
            _mpCardMounted = true;
        } catch(e) { console.warn('card mount failed:', e); }
    });
}

// ── 구매 모달 닫기 ───────────────────────────────────────────
function mpClosePurchase() {
    document.getElementById('mpPurchaseModal').classList.add('hidden');
    if (_mpCardEl && _mpCardMounted) {
        try { _mpCardEl.unmount(); } catch(e) {}
        _mpCardMounted = false;
    }
}

// ── 결제 제출 ────────────────────────────────────────────────
function mpSubmitPurchase() {
    if (!_mpPayjp || !_mpCardEl) {
        document.getElementById('mpPurchaseError').textContent = 'PAY.JP가 초기화되지 않았습니다.';
        document.getElementById('mpPurchaseError').classList.remove('hidden');
        return;
    }
    var btn = document.getElementById('mpPurchaseBtn');
    btn.disabled = true;
    btn.textContent = '처리 중…';
    document.getElementById('mpPurchaseError').classList.add('hidden');

    _mpPayjp.createToken(_mpCardEl).then(function(result) {
        if (result.error) {
            document.getElementById('mpPurchaseError').textContent = result.error.message;
            document.getElementById('mpPurchaseError').classList.remove('hidden');
            btn.disabled = false; btn.textContent = '결제하기';
            return;
        }
        fetch(_mpApiUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=purchase_item'
                + '&item_slug=' + encodeURIComponent(_mpSlug)
                + '&payjp_token=' + encodeURIComponent(result.token.id)
                + '&buyer_email=' + encodeURIComponent(document.getElementById('mpBuyerEmail').value)
        })
        .then(function(r) { return r.json(); })
        .then(function(d) {
            if (d.success) {
                document.getElementById('mpPurchaseSuccess').innerHTML =
                    '구매 완료! 라이선스 키: <strong>' + (d.license_key || '') + '</strong>'
                    + (d.serial_key ? '<br>시리얼: <strong>' + d.serial_key + '</strong>' : '');
                document.getElementById('mpPurchaseSuccess').classList.remove('hidden');
                btn.textContent = '완료';
            } else {
                document.getElementById('mpPurchaseError').textContent = d.message || '결제 실패';
                document.getElementById('mpPurchaseError').classList.remove('hidden');
                btn.disabled = false; btn.textContent = '결제하기';
            }
        })
        .catch(function() {
            document.getElementById('mpPurchaseError').textContent = '네트워크 오류';
            document.getElementById('mpPurchaseError').classList.remove('hidden');
            btn.disabled = false; btn.textContent = '결제하기';
        });
    });
}
</script>

<?php if ($_mpPayjpKey): ?>
<script src="https://js.pay.jp/v2/pay.js"></script>
<?php endif; ?>
