<?php
/**
 * 관리자 서비스 상세 — 부가서비스 탭 (마이페이지 디자인 통합)
 *
 * 마이페이지의 customer/mypage/service-partials/addon.php 디자인 패턴을 베이스로
 * 어드민 전용 기능 (one_time 상태 클릭 변경, VosCMS 자동 설치 버튼, 취소, 어드민 메모) 보존.
 *
 * 외부 변수 (detail.php 에서 주입):
 *   $subs (addon 타입 sub 배열), $order, $servicesByType, $statusLabels, $fmtPrice,
 *   $_localizeLabel (라벨 다국어 변환 closure), $pdo, $prefix, $baseUrl
 */

// 호스팅 구독 (부모)
$_hostSub = $servicesByType['hosting'][0] ?? null;
$_hostSubId = $_hostSub['id'] ?? 0;
$_curr = $_hostSub['currency'] ?? ($order['currency'] ?? 'JPY');

// 호스팅 사이트 URL
$_hostMeta = $_hostSub ? (json_decode($_hostSub['metadata'] ?? '{}', true) ?: []) : [];
$_primaryDomain = $_hostMeta['primary_domain'] ?? ($_hostMeta['domains'][0] ?? '');
if (!$_primaryDomain && !empty($order['domain'])) $_primaryDomain = $order['domain'];
$_siteUrl = $_primaryDomain ? ('https://' . $_primaryDomain) : '';

// 추가 용량 옵션
$_storageOptions = [];
$_addonsAll = [];
try {
    $_stSt = $pdo->prepare("SELECT `value` FROM {$prefix}settings WHERE `key` IN ('service_hosting_storage','service_addons')");
    $_stSt->execute();
    while ($_r = $_stSt->fetch(PDO::FETCH_ASSOC)) {
        if ($_r['key'] === 'service_hosting_storage') $_storageOptions = json_decode($_r['value'] ?: '[]', true) ?: [];
        if ($_r['key'] === 'service_addons')          $_addonsAll = json_decode($_r['value'] ?: '[]', true) ?: [];
    }
} catch (\Throwable $e) { /* silent */ }

// 신청 가능 부가서비스 (비즈메일 제외)
$_addonsAvail = array_filter($_addonsAll, function($a) {
    $id = strtolower($a['_id'] ?? '');
    $lbl = $a['label'] ?? '';
    if ($id === 'bizmail') return false;
    if (stripos($lbl, '비즈니스 메일') !== false || stripos($lbl, 'business mail') !== false || stripos($lbl, 'ビジネスメール') !== false) return false;
    return true;
});

// 이미 활성화된 addon 라벨 (중복 신청 방지)
$_activeAddonLabels = [];
foreach ($subs as $_s) $_activeAddonLabels[] = trim((string)($_s['label'] ?? ''));
?>

<div class="space-y-3 p-5">

    <!-- 용량 추가 카드 (호스팅 활성 시) -->
    <?php if ($_hostSubId && !empty($_storageOptions)): ?>
    <div class="flex items-center justify-between bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 px-5 py-4">
        <div class="flex items-center gap-3">
            <div class="w-10 h-10 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center flex-shrink-0">
                <svg class="w-5 h-5 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 8h14M5 8a2 2 0 110-4h14a2 2 0 110 4M5 8v10a2 2 0 002 2h10a2 2 0 002-2V8m-9 4h4"/></svg>
            </div>
            <div>
                <p class="text-sm font-bold text-zinc-900 dark:text-white">웹 용량 추가</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400">호스팅 만료일까지 동기화. 관리자 즉시 결제로 부여.</p>
            </div>
        </div>
        <button type="button" onclick="openAdminStorageModal()" class="px-4 py-2 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition flex-shrink-0">
            + 용량 추가
        </button>
    </div>
    <?php endif; ?>

    <!-- 활성 부가서비스 목록 -->
    <?php if (!empty($subs)): foreach ($subs as $sub):
        $st = $statusLabels[$sub['status']] ?? [__('services.mypage.status_unknown'), 'bg-gray-100 text-gray-500'];
        $meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
        $sc = $sub['service_class'] ?? 'recurring';
        $isOneTime = $sc === 'one_time';
        $currentOneTimeStatus = !empty($sub['completed_at']) ? 'completed' : $sub['status'];
        $adminMemo = $meta['admin_memo'] ?? '';
        $_isInstallAddon = !empty($meta['install_info']);
        $_installCompleted = !empty($meta['install_completed_at']);
    ?>
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden" id="sub_<?= $sub['id'] ?>">
        <div class="px-5 py-3 flex items-center justify-between gap-3 flex-wrap">
            <div class="flex items-center gap-2 min-w-0">
                <p class="text-sm font-semibold text-zinc-900 dark:text-white"><?= htmlspecialchars($_localizeLabel($sub)) ?></p>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-medium <?= $st[1] ?>"><?= $st[0] ?></span>
                <?php if ($isOneTime): ?>
                <span class="text-[10px] px-2 py-0.5 bg-amber-50 text-amber-600 dark:bg-amber-900/30 dark:text-amber-400 rounded-full"><?= htmlspecialchars(__('services.detail.b_one_time')) ?></span>
                <?php elseif ($sc === 'free'): ?>
                <span class="text-[10px] px-2 py-0.5 bg-green-50 text-green-600 dark:bg-green-900/30 dark:text-green-400 rounded-full"><?= htmlspecialchars(__('services.order.summary.free')) ?></span>
                <?php endif; ?>
            </div>
            <div class="flex items-center gap-2 shrink-0 flex-wrap">
                <?php if ($_isInstallAddon && !$_installCompleted): ?>
                <button type="button" onclick="adminRunVoscmsInstall(<?= (int)$sub['id'] ?>)" class="text-xs px-2.5 py-1 bg-violet-50 text-violet-700 dark:bg-violet-900/20 dark:text-violet-400 rounded-lg hover:ring-2 hover:ring-violet-300 transition inline-flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4"/></svg>
                    VosCMS 자동 설치
                </button>
                <?php elseif ($_isInstallAddon && $_installCompleted): ?>
                <a href="<?= htmlspecialchars($meta['install_admin_url'] ?? '#') ?>" target="_blank" class="text-xs px-2.5 py-1 bg-green-50 text-green-700 dark:bg-green-900/20 dark:text-green-400 rounded-lg inline-flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    관리자 페이지 열기
                </a>
                <?php endif; ?>

                <?php if ($isOneTime):
                    $otColors = ['pending'=>'blue','active'=>'amber','suspended'=>'zinc','cancelled'=>'red','completed'=>'green'];
                    $otLabels = [
                        'pending'   => __('services.detail.ot_pending'),
                        'active'    => __('services.detail.ot_active'),
                        'suspended' => __('services.detail.ot_suspended'),
                        'cancelled' => __('services.detail.ot_cancelled'),
                        'completed' => __('services.detail.ot_completed'),
                    ];
                    $otColor = $otColors[$currentOneTimeStatus] ?? 'zinc';
                    $otLabel = $otLabels[$currentOneTimeStatus] ?? __('services.mypage.status_unknown');
                ?>
                <button onclick="openStatusModal(<?= $sub['id'] ?>, '<?= $currentOneTimeStatus ?>', '<?= htmlspecialchars($sub['label']) ?>')" class="text-xs px-2.5 py-1 bg-<?= $otColor ?>-50 text-<?= $otColor ?>-600 dark:bg-<?= $otColor ?>-900/20 dark:text-<?= $otColor ?>-400 rounded-lg hover:ring-2 hover:ring-<?= $otColor ?>-300 transition cursor-pointer"><?= htmlspecialchars($otLabel) ?></button>
                <?php elseif ($sc === 'recurring' && $sub['status'] === 'active'): ?>
                <span class="text-xs text-zinc-400"><?= htmlspecialchars(__('services.detail.auto_renew')) ?></span>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" class="sr-only peer" <?= $sub['auto_renew'] ? 'checked' : '' ?>
                           onchange="ajaxPost({action:'toggle_auto_renew',subscription_id:<?= $sub['id'] ?>,auto_renew:this.checked})">
                    <div class="w-9 h-5 bg-zinc-200 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                </label>
                <?php endif; ?>

                <?php if ($sub['status'] !== 'cancelled'): ?>
                <button type="button" onclick="adminCancelAddon(<?= (int)$sub['id'] ?>, '<?= htmlspecialchars($_localizeLabel($sub), ENT_QUOTES) ?>')" class="px-2 py-1 text-[11px] font-medium text-red-700 dark:text-red-400 border border-red-200 dark:border-red-800 hover:bg-red-50 dark:hover:bg-red-900/20 rounded transition" title="부가서비스 취소">취소</button>
                <?php endif; ?>
            </div>
        </div>

        <!-- 메타 정보 -->
        <div class="px-5 pb-3 flex items-center justify-between gap-3 text-xs text-zinc-400 flex-wrap">
            <div class="flex items-center gap-3 flex-wrap">
                <?php if (!$isOneTime): ?>
                <span><?= date('Y-m-d', strtotime($sub['started_at'])) ?> ~ <?= date('Y-m-d', strtotime($sub['expires_at'])) ?></span>
                <?php endif; ?>
                <span><?= (int)$sub['billing_amount'] > 0 ? $fmtPrice($sub['billing_amount'], $sub['currency']) : __('services.order.summary.free') ?></span>
                <?php if (!empty($meta['quote_required'])): ?>
                <span class="text-amber-500"><?= htmlspecialchars(__('services.detail.quote_required')) ?></span>
                <?php endif; ?>
                <?php if (!empty($sub['completed_at'])): ?>
                <span class="text-green-600">완료: <?= date('Y-m-d', strtotime($sub['completed_at'])) ?></span>
                <?php endif; ?>
            </div>
            <?php if ($_isInstallAddon && $_siteUrl): ?>
            <div class="flex items-center gap-2">
                <a href="<?= htmlspecialchars($_siteUrl) ?>/admin" target="_blank" class="inline-flex items-center gap-1 px-3 py-1 text-[11px] font-medium text-violet-700 dark:text-violet-300 bg-violet-50 dark:bg-violet-900/20 border border-violet-200 dark:border-violet-800 rounded hover:bg-violet-100 dark:hover:bg-violet-900/40 transition">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                    관리자
                </a>
                <a href="<?= htmlspecialchars($_siteUrl) ?>/" target="_blank" class="inline-flex items-center gap-1 px-3 py-1 text-[11px] font-medium text-blue-700 dark:text-blue-300 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded hover:bg-blue-100 dark:hover:bg-blue-900/40 transition">
                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                    사이트
                </a>
            </div>
            <?php endif; ?>
        </div>

        <!-- 설치 관리자 정보 (install addon 전용) -->
        <?php if (!empty($meta['install_info'])): $info = $meta['install_info']; ?>
        <div class="mx-5 mb-4 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-lg">
            <p class="text-xs font-semibold text-blue-800 dark:text-blue-200 mb-2">설치 관리자 정보</p>
            <div class="grid grid-cols-1 md:grid-cols-2 gap-x-4 gap-y-1.5 text-xs">
                <?php if (!empty($info['admin_id'])): ?>
                <div class="flex gap-2"><span class="text-zinc-500 dark:text-zinc-400 min-w-[7rem]">관리자 ID:</span><span class="font-mono text-zinc-800 dark:text-zinc-100"><?= htmlspecialchars($info['admin_id']) ?></span></div>
                <?php endif; ?>
                <?php if (!empty($info['admin_email'])): ?>
                <div class="flex gap-2"><span class="text-zinc-500 dark:text-zinc-400 min-w-[7rem]">관리자 이메일:</span><span class="font-mono text-zinc-800 dark:text-zinc-100"><?= htmlspecialchars($info['admin_email']) ?></span></div>
                <?php endif; ?>
                <?php if (!empty($info['admin_pw'])): ?>
                <div class="flex items-center gap-2"><span class="text-zinc-500 dark:text-zinc-400 min-w-[7rem]">관리자 비번:</span><span class="install-pw font-mono text-zinc-800 dark:text-zinc-100 select-all" data-real="<?= htmlspecialchars($info['admin_pw']) ?>">••••••••</span><button type="button" onclick="(function(b){var s=b.previousElementSibling;if(s.textContent==='••••••••'){s.textContent=s.dataset.real;b.textContent='🙈';}else{s.textContent='••••••••';b.textContent='👁';}})(this)" class="text-xs hover:opacity-70" title="show/hide">👁</button></div>
                <?php endif; ?>
                <?php if (!empty($info['site_title'])): ?>
                <div class="flex gap-2"><span class="text-zinc-500 dark:text-zinc-400 min-w-[7rem]">사이트 제목:</span><span class="text-zinc-800 dark:text-zinc-100"><?= htmlspecialchars($info['site_title']) ?></span></div>
                <?php endif; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- 어드민 메모 (인라인) -->
        <div class="mx-5 mb-4">
            <details class="text-xs">
                <summary class="cursor-pointer text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">관리자 메모 <?= $adminMemo ? '<span class="text-blue-500">●</span>' : '' ?></summary>
                <div class="mt-2 flex gap-2">
                    <textarea id="memo_<?= $sub['id'] ?>" rows="2" class="flex-1 px-2 py-1.5 text-xs border border-zinc-200 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded resize-none" placeholder="내부 메모"><?= htmlspecialchars($adminMemo) ?></textarea>
                    <button type="button" onclick="adminSaveAddonMemo(<?= $sub['id'] ?>)" class="px-3 py-1 text-xs text-white bg-zinc-700 hover:bg-zinc-800 rounded">저장</button>
                </div>
            </details>
        </div>
    </div>
    <?php endforeach; endif; ?>

    <!-- 추가 신청 가능 부가서비스 (호스팅 활성 시) -->
    <?php if (!empty($_addonsAvail) && $_hostSub): ?>
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700">
            <p class="text-xs font-bold text-zinc-700 dark:text-zinc-200">추가 신청 가능 부가서비스</p>
            <p class="text-[10px] text-zinc-400 mt-0.5">관리자가 즉시 추가 + 결제 처리 (현금 / 무료)</p>
        </div>
        <div class="divide-y divide-gray-100 dark:divide-zinc-700/50">
            <?php foreach ($_addonsAvail as $_a):
                $_aId = $_a['_id'] ?? '';
                $_aLabel = $_a['label'] ?? '';
                $_aPrice = (int)($_a['price'] ?? 0);
                $_aUnit = $_a['unit'] ?? '';
                $_aOneTime = !empty($_a['one_time']);
                $_aIsQuote = ($_aPrice <= 0 && (stripos($_aUnit, '견적') !== false || stripos($_aUnit, 'quote') !== false || stripos($_aUnit, '見積') !== false));
                $_aIsFree = $_aPrice <= 0 && !$_aIsQuote;
                $_aAlreadyActive = in_array(trim($_aLabel), $_activeAddonLabels, true);
            ?>
            <div class="px-5 py-3 flex items-center justify-between gap-3 flex-wrap">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 flex-wrap">
                        <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($_aLabel) ?></p>
                        <?php if ($_aOneTime): ?>
                        <span class="text-[10px] px-1.5 py-0.5 bg-amber-50 text-amber-600 rounded">1회 결제</span>
                        <?php endif; ?>
                        <?php if ($_aAlreadyActive): ?>
                        <span class="text-[10px] px-1.5 py-0.5 bg-zinc-100 text-zinc-500 rounded">이미 신청됨</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5">
                        <?php if ($_aIsFree): ?>
                        <span class="text-green-600">무료</span>
                        <?php elseif ($_aIsQuote): ?>
                        <span class="text-amber-600">견적</span>
                        <?php else: ?>
                        <?= $fmtPrice($_aPrice, $_curr) ?><?= htmlspecialchars($_aUnit) ?>
                        <?php endif; ?>
                    </p>
                </div>
                <?php if ($_aAlreadyActive): ?>
                <button type="button" disabled class="px-4 py-1.5 text-xs font-medium text-zinc-400 bg-zinc-100 rounded-lg cursor-not-allowed">신청됨</button>
                <?php elseif ($_aIsQuote): ?>
                <span class="text-[11px] text-amber-600">견적은 제작 프로젝트로 처리</span>
                <?php else: ?>
                <button type="button" onclick="openAdminAddonAddModal('<?= htmlspecialchars($_aId, ENT_QUOTES) ?>', '<?= htmlspecialchars(addslashes($_aLabel)) ?>', <?= $_aPrice ?>, '<?= htmlspecialchars(addslashes($_aUnit)) ?>', <?= $_aOneTime ? 'true' : 'false' ?>)" class="px-4 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg">+ 추가</button>
                <?php endif; ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>

</div>

<!-- 용량 추가 모달 -->
<?php if ($_hostSubId && !empty($_storageOptions)): ?>
<div id="adminStorageModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl max-w-md w-full p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-zinc-900 dark:text-white">웹 용량 추가</h3>
            <button type="button" onclick="closeAdminStorageModal()" class="text-zinc-400 hover:text-zinc-600">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-3">관리자 즉시 부여 (참고 단가 — 결제 별도).</p>
        <div class="space-y-2 mb-4">
            <?php foreach ($_storageOptions as $_so):
                $_cap = $_so['capacity'] ?? '?';
                $_unitPrice = (int)($_so['price'] ?? 0);
            ?>
            <button type="button" onclick="adminAddStorageAddon('<?= htmlspecialchars($_cap, ENT_QUOTES) ?>', <?= $_unitPrice ?>)" class="w-full flex items-center justify-between px-4 py-3 border border-zinc-200 dark:border-zinc-700 rounded-lg hover:border-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 transition text-left">
                <div class="flex items-center gap-3">
                    <span class="w-8 h-8 rounded-full bg-blue-100 dark:bg-blue-900/30 flex items-center justify-center"><svg class="w-4 h-4 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg></span>
                    <div>
                        <p class="text-base font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($_cap) ?></p>
                        <p class="text-[10px] text-zinc-400">참고 단가: <?= $fmtPrice($_unitPrice, $_curr) ?> / 월</p>
                    </div>
                </div>
                <span class="text-[10px] font-medium text-violet-500">관리자 부여</span>
            </button>
            <?php endforeach; ?>
        </div>
        <p class="text-[11px] text-amber-600 italic">관리자가 직접 부여하므로 결제 처리는 별도로 진행하세요.</p>
    </div>
</div>
<?php endif; ?>

<!-- 부가서비스 추가 + 결제 모달 (어드민 전용) -->
<div id="adminAddonAddModal" class="hidden fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl max-w-lg w-full p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-zinc-900 dark:text-white" id="addonAddTitle">부가서비스 추가</h3>
            <button type="button" onclick="closeAdminAddonAddModal()" class="text-zinc-400 hover:text-zinc-600"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg></button>
        </div>
        <div class="space-y-3">
            <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-lg p-3 text-xs space-y-1">
                <div class="flex justify-between"><span class="text-zinc-500">단가</span><span id="addonQuoteUnit" class="font-mono tabular-nums text-zinc-800 dark:text-zinc-200">¥0</span></div>
                <div class="flex justify-between" id="addonQuoteMonthsRow"><span class="text-zinc-500">호스팅 만료까지</span><span id="addonQuoteMonths" class="font-mono tabular-nums text-zinc-800 dark:text-zinc-200">-</span></div>
                <div class="flex justify-between"><span class="text-zinc-500">소계</span><span id="addonQuoteSub" class="font-mono tabular-nums text-zinc-800 dark:text-zinc-200">¥0</span></div>
                <div class="flex justify-between"><span class="text-zinc-500">소비세 (10%)</span><span id="addonQuoteTax" class="font-mono tabular-nums text-zinc-800 dark:text-zinc-200">¥0</span></div>
                <div class="flex justify-between font-bold pt-1 border-t border-zinc-200 dark:border-zinc-600 mt-1"><span class="text-zinc-700 dark:text-zinc-200">합계</span><span id="addonQuoteTotal" class="font-mono tabular-nums text-zinc-900 dark:text-white text-sm">¥0</span></div>
            </div>
            <div>
                <label class="text-[11px] text-zinc-500 dark:text-zinc-400 block mb-1">결제 방식 <span class="text-red-500">*</span></label>
                <div class="flex gap-2">
                    <label class="flex-1 flex items-center gap-1 px-3 py-2 border border-zinc-200 dark:border-zinc-600 rounded-lg cursor-pointer text-xs has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-900/20">
                        <input type="radio" name="addonPayMethod" value="cash" checked onchange="onAddonPayMethodChange()" class="sr-only"><span>💴 현금</span>
                    </label>
                    <label class="flex-1 flex items-center gap-1 px-3 py-2 border border-zinc-200 dark:border-zinc-600 rounded-lg cursor-pointer text-xs has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-900/20">
                        <input type="radio" name="addonPayMethod" value="free" onchange="onAddonPayMethodChange()" class="sr-only"><span>🎁 무료</span>
                    </label>
                </div>
            </div>
            <div id="addonCashBox">
                <label class="text-[11px] text-zinc-500 dark:text-zinc-400 block mb-1">받은 금액 (현금) <span class="text-red-500">*</span></label>
                <input type="number" id="addonCashReceived" min="0" placeholder="합계 이상" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg">
            </div>
            <div id="addonFreeBox" class="hidden">
                <label class="text-[11px] text-zinc-500 dark:text-zinc-400 block mb-1">무료 처리 사유 <span class="text-red-500">*</span></label>
                <input type="text" id="addonFreeReason" placeholder="예: 사회적기업 후원" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg">
            </div>
        </div>
        <div class="flex justify-end gap-2 mt-5">
            <button type="button" onclick="closeAdminAddonAddModal()" class="px-3 py-1.5 text-xs text-zinc-500 hover:text-zinc-700">취소</button>
            <button type="button" onclick="submitAdminAddonAdd()" id="addonAddSubmitBtn" class="px-4 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">추가 + 결제</button>
        </div>
    </div>
</div>

<script>
var _addonContext = { id: '', label: '', unitPrice: 0, unit: '', oneTime: false };

// === 용량 추가 모달 ===
function openAdminStorageModal() {
    var m = document.getElementById('adminStorageModal');
    if (!m) return;
    m.classList.remove('hidden'); m.classList.add('flex');
    document.body.style.overflow = 'hidden';
}
function closeAdminStorageModal() {
    var m = document.getElementById('adminStorageModal');
    if (!m) return;
    m.classList.add('hidden'); m.classList.remove('flex');
    document.body.style.overflow = '';
}
function adminAddStorageAddon(capacity, unitPrice) {
    if (!confirm(capacity + ' 용량을 즉시 부여합니까? (결제 별도)')) return;
    ajaxPost({ action: 'admin_add_storage_addon', subscription_id: <?= (int)$_hostSubId ?>, capacity: capacity, unit_price: unitPrice })
        .then(function(d) {
            if (d.success) { alert(d.message || '용량 추가 완료'); location.reload(); }
            else alert(d.message || '실패');
        });
}

// === 부가서비스 추가 + 결제 모달 ===
function openAdminAddonAddModal(addonId, label, unitPrice, unit, oneTime) {
    _addonContext = { id: addonId, label: label, unitPrice: unitPrice, unit: unit, oneTime: oneTime };
    document.getElementById('addonAddTitle').textContent = label + ' 추가';
    document.getElementById('addonCashReceived').value = '';
    document.getElementById('addonFreeReason').value = '';
    document.querySelectorAll('input[name="addonPayMethod"]').forEach(function(r){ r.checked = (r.value === 'cash'); });
    onAddonPayMethodChange();
    document.getElementById('adminAddonAddModal').classList.remove('hidden');
    recalcAddonQuote();
}
function closeAdminAddonAddModal() { document.getElementById('adminAddonAddModal').classList.add('hidden'); }
function onAddonPayMethodChange() {
    var m = document.querySelector('input[name="addonPayMethod"]:checked').value;
    document.getElementById('addonCashBox').classList.toggle('hidden', m !== 'cash');
    document.getElementById('addonFreeBox').classList.toggle('hidden', m !== 'free');
}
function fmtYen(n) { return '¥' + Math.round(n).toLocaleString(); }
function recalcAddonQuote() {
    ajaxPost({ action: 'admin_addon_quote', order_id: orderId, addon_id: _addonContext.id }).then(function(d) {
        if (!d.success) return;
        document.getElementById('addonQuoteUnit').textContent   = fmtYen(d.unit_price) + ' ' + (d.unit || '');
        if (d.one_time) {
            document.getElementById('addonQuoteMonthsRow').classList.add('hidden');
        } else {
            document.getElementById('addonQuoteMonthsRow').classList.remove('hidden');
            document.getElementById('addonQuoteMonths').textContent = d.months + '개월';
        }
        document.getElementById('addonQuoteSub').textContent    = fmtYen(d.subtotal);
        document.getElementById('addonQuoteTax').textContent    = fmtYen(d.tax);
        document.getElementById('addonQuoteTotal').textContent  = fmtYen(d.total);
    });
}
function submitAdminAddonAdd() {
    var method = document.querySelector('input[name="addonPayMethod"]:checked').value;
    var body = {
        action: 'admin_add_addon',
        order_id: orderId,
        addon_id: _addonContext.id,
        payment_method: method,
    };
    if (method === 'cash') {
        var c = parseInt(document.getElementById('addonCashReceived').value, 10) || 0;
        if (c <= 0) { alert('받은 금액 입력 필요'); return; }
        body.cash_received = c;
    } else if (method === 'free') {
        var r = (document.getElementById('addonFreeReason').value || '').trim();
        if (!r) { alert('무료 처리 사유 필요'); return; }
        body.free_reason = r;
    }
    var btn = document.getElementById('addonAddSubmitBtn');
    btn.disabled = true; btn.textContent = '처리 중…';
    ajaxPost(body).then(function(d){
        btn.disabled = false; btn.textContent = '추가 + 결제';
        alert(d.message || (d.success ? '완료' : '실패'));
        if (d.success) { closeAdminAddonAddModal(); location.reload(); }
    });
}

// === 어드민 전용 액션 ===
function adminRunVoscmsInstall(subId) {
    if (!confirm('VosCMS 자동 설치를 실행합니까? (1~2분 소요)')) return;
    var btns = document.querySelectorAll('button[onclick^="adminRunVoscmsInstall"]');
    btns.forEach(function(b){ b.disabled = true; b.textContent = '설치 중…'; });
    ajaxPost({ action: 'admin_run_voscms_install', subscription_id: subId })
        .then(function(d) {
            if (d.success) { alert((d.message || '설치 완료') + (d.admin_url ? '\n관리자: ' + d.admin_url : '')); location.reload(); }
            else { alert(d.message || '실패'); btns.forEach(function(b){ b.disabled = false; b.textContent = 'VosCMS 자동 설치'; }); }
        });
}
function adminCancelAddon(subId, label) {
    if (!confirm(label + ' 부가서비스를 취소합니까?')) return;
    ajaxPost({ action: 'admin_cancel_addon', subscription_id: subId })
        .then(function(d) {
            if (d.success) {
                var msg = d.message || '취소 완료';
                if (d.refunded) msg += ' (환불 ¥' + Number(d.refunded).toLocaleString() + ')';
                alert(msg); location.reload();
            } else alert(d.message || '실패');
        });
}
function adminSaveAddonMemo(subId) {
    var memo = document.getElementById('memo_' + subId).value;
    ajaxPost({ action: 'update_addon_memo', subscription_id: subId, memo: memo })
        .then(function(d){ alert(d.success ? '메모 저장됨' : (d.message || '실패')); });
}
</script>
