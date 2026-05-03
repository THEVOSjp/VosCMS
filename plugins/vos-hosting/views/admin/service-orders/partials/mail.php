<?php
/**
 * 관리자 서비스 상세 — 메일 탭 (고객 마이페이지 디자인 통합)
 *
 * 고객 페이지(customer/mypage/service-partials/mail.php)의 디자인을 통째로 가져옴.
 * 차이:
 *  - serviceAction(name, data) → ajaxPost({action: name, ...data}) 로 라우팅 (detail.php POST 처리)
 *  - 비즈니스 메일 "업그레이드 요청" 대신 어드민이 직접 결제 처리 (admin_add_bizmail_sub 모달)
 *  - 권한 체크는 detail.php 진입 시 admin role 검증돼서 통과 후 사용
 *
 * 외부 변수 (detail.php 에서 주입):
 *   $subs (mail 타입 sub 배열, 비어있을 수 있음), $servicesByType, $order, $statusLabels
 */

// 무료 메일 한도 — 호스팅 플랜의 free_mail_count, 기본 5
$_freeMailLimit = 5;
$_hpdataJson = $pdo->query("SELECT `value` FROM {$prefix}settings WHERE `key` = 'service_hosting_plans' LIMIT 1")->fetchColumn() ?: '[]';
$_hostingPlansData = json_decode($_hpdataJson, true) ?: [];
foreach ($_hostingPlansData as $_hp) {
    if (($_hp['capacity'] ?? '') === ($order['hosting_capacity'] ?? '')) {
        if (isset($_hp['free_mail_count']) && is_numeric($_hp['free_mail_count'])) {
            $_freeMailLimit = (int)$_hp['free_mail_count'];
        }
        break;
    }
}

// 메일 계정 집계
$basicSub = null;
$bizSub = null;
$allAccounts = [];
$_seenAddresses = [];
foreach ($subs as $sub) {
    if (($sub['type'] ?? '') !== 'mail') continue;
    $meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
    $isBiz = stripos($sub['label'], '비즈니스') !== false || stripos($sub['label'], 'ビジネス') !== false || stripos($sub['label'], 'Business') !== false;
    if ($isBiz) $bizSub = $sub;
    else        $basicSub = $sub;
    foreach ($meta['mail_accounts'] ?? [] as $ma) {
        $addr = strtolower($ma['address'] ?? '');
        if (!$addr || isset($_seenAddresses[$addr])) continue;
        $_seenAddresses[$addr] = true;
        $allAccounts[] = [
            'address' => $ma['address'] ?? '',
            'type' => $isBiz ? 'business' : 'basic',
            'sub_id' => $sub['id'],
        ];
    }
}

// 메일서버 정보
$_seedSub = $basicSub ?: $bizSub;
$mailServer = [];
$_webmailUrl = $_ENV['WEBMAIL_URL'] ?? 'https://mail.voscms.com/';
if ($_seedSub) {
    $_seedMeta = json_decode($_seedSub['metadata'] ?? '{}', true) ?: [];
    $mailServer = $_seedMeta['mail_server'] ?? [];
    if (!empty($mailServer['webmail_url'])) $_webmailUrl = $mailServer['webmail_url'];
}

$basicCount = count(array_filter($allAccounts, fn($a) => $a['type'] === 'basic'));
$bizCount = count(array_filter($allAccounts, fn($a) => $a['type'] === 'business'));
$canAddBasic = $basicCount < $_freeMailLimit;
$mailDomain = $order['domain'] ?? '';
?>

<div class="space-y-4 p-5">
    <!-- 메일서버 정보 -->
    <?php if (!empty($mailServer['imap_host']) || !empty($mailServer['smtp_host'])): ?>
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 p-5">
        <p class="text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3">메일 서버 설정</p>
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <table class="text-xs">
                <tbody class="divide-y divide-gray-100 dark:divide-zinc-700/50">
                    <tr><td class="py-1.5 text-zinc-400 w-24">IMAP host</td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailServer['imap_host'] ?? 'mail.voscms.com') ?></td></tr>
                    <tr><td class="py-1.5 text-zinc-400">IMAP port</td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailServer['imap_port'] ?? '993') ?></td></tr>
                    <tr><td class="py-1.5 text-zinc-400">SMTP host</td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailServer['smtp_host'] ?? 'mail.voscms.com') ?></td></tr>
                    <tr><td class="py-1.5 text-zinc-400">SMTP port</td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailServer['smtp_port'] ?? '587') ?></td></tr>
                </tbody>
            </table>
            <table class="text-xs">
                <tbody class="divide-y divide-gray-100 dark:divide-zinc-700/50">
                    <tr><td class="py-1.5 text-zinc-400 w-24">암호화</td><td class="py-1.5 text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailServer['encryption'] ?? 'SSL/TLS') ?></td></tr>
                    <tr><td class="py-1.5 text-zinc-400">웹메일</td><td class="py-1.5"><a href="<?= htmlspecialchars($_webmailUrl) ?>" target="_blank" class="text-blue-600 hover:underline font-mono"><?= htmlspecialchars($_webmailUrl) ?></a></td></tr>
                    <tr><td class="py-1.5 text-zinc-400">기준 도메인</td><td class="py-1.5 font-mono text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($mailDomain ?: '-') ?></td></tr>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <!-- 기본 메일 카드 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700 flex items-center justify-between flex-wrap gap-2">
            <div class="flex items-center gap-2 min-w-0">
                <h3 class="text-sm font-bold text-zinc-900 dark:text-white">기본 메일</h3>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">호스팅 무료</span>
                <span class="text-xs text-zinc-400"><?= $basicCount ?> / <?= $_freeMailLimit ?></span>
            </div>
            <button type="button" onclick="openMailAddModal()" class="text-xs px-3 py-1.5 rounded-lg font-medium transition <?= $canAddBasic ? 'bg-blue-600 text-white hover:bg-blue-700' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-400 cursor-not-allowed' ?>" <?= $canAddBasic ? '' : 'disabled' ?>>
                + 메일 추가
            </button>
        </div>
        <div class="p-5">
            <?php if ($basicCount === 0): ?>
            <div class="text-center py-8">
                <div class="w-14 h-14 rounded-full bg-green-50 dark:bg-green-900/20 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                </div>
                <p class="text-sm font-medium text-zinc-900 dark:text-white mb-1">메일 계정이 없습니다</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 max-w-md mx-auto">호스팅에 무료로 <?= $_freeMailLimit ?>개의 기본 메일을 사용할 수 있습니다.</p>
                <button type="button" onclick="openMailAddModal()" class="mt-4 inline-flex items-center gap-1.5 text-xs px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    + 메일 추가
                </button>
            </div>
            <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($allAccounts as $acc): if ($acc['type'] !== 'basic') continue; ?>
                <div class="bg-gray-50 dark:bg-zinc-700/30 rounded-lg overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-3 gap-2">
                        <div class="flex items-center gap-3 min-w-0 flex-1">
                            <div class="w-8 h-8 rounded-full bg-green-100 dark:bg-green-900/30 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-mono font-medium text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($acc['address']) ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0 flex-wrap justify-end">
                            <button type="button" onclick="openWebmail('<?= htmlspecialchars($acc['address'], ENT_QUOTES) ?>')" class="inline-flex items-center gap-1 text-[10px] px-2.5 py-1.5 text-blue-600 dark:text-blue-400 border border-blue-200 dark:border-blue-700 rounded-lg hover:bg-blue-50 dark:hover:bg-blue-900/20 transition">
                                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                                웹메일
                            </button>
                            <button type="button" onclick="togglePwForm(this)" class="text-[10px] px-2.5 py-1.5 text-zinc-500 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-600 rounded-lg hover:border-blue-400 hover:text-blue-600 transition">비번 변경</button>
                            <button type="button" onclick="upgradeMailToBiz('<?= htmlspecialchars($acc['address'], ENT_QUOTES) ?>')" class="text-[10px] px-2.5 py-1.5 text-amber-600 dark:text-amber-400 border border-amber-200 dark:border-amber-700 rounded-lg hover:bg-amber-50 dark:hover:bg-amber-900/20 transition">비즈로 전환</button>
                            <button type="button" onclick="confirmDeleteMail('<?= htmlspecialchars($acc['address'], ENT_QUOTES) ?>', <?= $acc['sub_id'] ?>)" class="text-[10px] px-2.5 py-1.5 text-zinc-500 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-600 rounded-lg hover:border-red-400 hover:text-red-600 transition">삭제</button>
                        </div>
                    </div>
                    <div class="pw-form hidden px-4 pb-4">
                        <div class="p-3 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                            <div class="flex items-end gap-2 flex-wrap">
                                <div class="flex-1 min-w-[140px]">
                                    <label class="text-[10px] text-zinc-500 block mb-1">새 비밀번호</label>
                                    <input type="password" class="pw-new w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg" placeholder="최소 8자">
                                </div>
                                <div class="flex-1 min-w-[140px]">
                                    <label class="text-[10px] text-zinc-500 block mb-1">확인</label>
                                    <input type="password" class="pw-confirm w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg" placeholder="다시 입력">
                                </div>
                                <button type="button" onclick="changePw(this,'<?= htmlspecialchars($acc['address'], ENT_QUOTES) ?>',<?= $acc['sub_id'] ?>)" class="px-4 py-2 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition whitespace-nowrap">변경</button>
                                <button type="button" onclick="this.closest('.pw-form').classList.add('hidden')" class="px-3 py-2 text-xs text-zinc-400 hover:text-zinc-600">취소</button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 비즈니스 메일 카드 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700 flex items-center justify-between flex-wrap gap-2">
            <div class="flex items-center gap-2 min-w-0">
                <h3 class="text-sm font-bold text-zinc-900 dark:text-white">비즈니스 메일</h3>
                <span class="text-[10px] px-2 py-0.5 rounded-full font-medium bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400">유료</span>
                <span class="text-xs text-zinc-400"><?= $bizCount ?>개</span>
            </div>
            <button type="button" onclick="openAddBizMailSubModal()" class="text-xs px-3 py-1.5 rounded-lg font-medium bg-amber-600 text-white hover:bg-amber-700 transition">
                + <?= $bizSub ? '비즈 메일 추가' : '구독 추가 (결제)' ?>
            </button>
        </div>
        <div class="p-5">
            <?php if ($bizCount === 0): ?>
            <div class="text-center py-8">
                <div class="w-14 h-14 rounded-full bg-amber-50 dark:bg-amber-900/20 flex items-center justify-center mx-auto mb-4">
                    <svg class="w-7 h-7 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z"/></svg>
                </div>
                <p class="text-sm font-medium text-zinc-900 dark:text-white mb-1">비즈니스 메일 미구독</p>
                <p class="text-xs text-zinc-500 dark:text-zinc-400 max-w-md mx-auto">대용량 메일함, 광고 차단, 도메인 인증 등 추가 기능 제공.<br>¥5,000 / 계정 / 월</p>
                <button type="button" onclick="openAddBizMailSubModal()" class="mt-4 inline-flex items-center gap-1.5 text-xs px-4 py-2 bg-amber-600 text-white rounded-lg hover:bg-amber-700 transition">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/></svg>
                    구독 추가 + 결제
                </button>
            </div>
            <?php else: ?>
            <div class="space-y-2">
                <?php foreach ($allAccounts as $acc): if ($acc['type'] !== 'business') continue; ?>
                <div class="bg-gray-50 dark:bg-zinc-700/30 rounded-lg overflow-hidden">
                    <div class="flex items-center justify-between px-4 py-3 gap-2">
                        <div class="flex items-center gap-3 min-w-0 flex-1">
                            <div class="w-8 h-8 rounded-full bg-amber-100 dark:bg-amber-900/30 flex items-center justify-center shrink-0">
                                <svg class="w-4 h-4 text-amber-600 dark:text-amber-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            </div>
                            <div class="min-w-0">
                                <p class="text-sm font-mono font-medium text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($acc['address']) ?></p>
                            </div>
                        </div>
                        <div class="flex items-center gap-1.5 shrink-0 flex-wrap justify-end">
                            <button type="button" onclick="togglePwForm(this)" class="text-[10px] px-2.5 py-1.5 text-zinc-500 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-600 rounded-lg hover:border-blue-400 hover:text-blue-600 transition">비번 변경</button>
                            <button type="button" onclick="confirmDeleteMail('<?= htmlspecialchars($acc['address'], ENT_QUOTES) ?>', <?= $acc['sub_id'] ?>)" class="text-[10px] px-2.5 py-1.5 text-zinc-500 dark:text-zinc-400 border border-zinc-200 dark:border-zinc-600 rounded-lg hover:border-red-400 hover:text-red-600 transition">삭제</button>
                        </div>
                    </div>
                    <div class="pw-form hidden px-4 pb-4">
                        <div class="p-3 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                            <div class="flex items-end gap-2 flex-wrap">
                                <div class="flex-1 min-w-[140px]">
                                    <label class="text-[10px] text-zinc-500 block mb-1">새 비밀번호</label>
                                    <input type="password" class="pw-new w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg" placeholder="최소 8자">
                                </div>
                                <div class="flex-1 min-w-[140px]">
                                    <label class="text-[10px] text-zinc-500 block mb-1">확인</label>
                                    <input type="password" class="pw-confirm w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg" placeholder="다시 입력">
                                </div>
                                <button type="button" onclick="changePw(this,'<?= htmlspecialchars($acc['address'], ENT_QUOTES) ?>',<?= $acc['sub_id'] ?>)" class="px-4 py-2 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition whitespace-nowrap">변경</button>
                                <button type="button" onclick="this.closest('.pw-form').classList.add('hidden')" class="px-3 py-2 text-xs text-zinc-400 hover:text-zinc-600">취소</button>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- 메일 추가 모달 (기본 메일) -->
<div id="mailAddModal" class="fixed inset-0 z-50 hidden items-center justify-center bg-black/50 p-4">
    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl max-w-md w-full p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-zinc-900 dark:text-white">메일 계정 추가</h3>
            <button type="button" onclick="closeMailAddModal()" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="space-y-3">
            <div>
                <label class="text-xs text-zinc-500 dark:text-zinc-400 block mb-1">메일 주소</label>
                <div class="flex items-center gap-2">
                    <input type="text" id="mailAddrLocal" class="flex-1 px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg font-mono" placeholder="info" autocomplete="off">
                    <span class="text-sm text-zinc-500 dark:text-zinc-400 font-mono">@<?= htmlspecialchars($mailDomain) ?></span>
                </div>
            </div>
            <div>
                <label class="text-xs text-zinc-500 dark:text-zinc-400 block mb-1">비밀번호</label>
                <input type="password" id="mailAddPw" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg" placeholder="최소 8자">
            </div>
            <div>
                <label class="text-xs text-zinc-500 dark:text-zinc-400 block mb-1">비밀번호 확인</label>
                <input type="password" id="mailAddPwConfirm" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg" placeholder="다시 입력">
            </div>
        </div>
        <div class="flex items-center justify-end gap-2 mt-5">
            <button type="button" onclick="closeMailAddModal()" class="px-4 py-2 text-xs text-zinc-600 dark:text-zinc-400 hover:text-zinc-900 dark:hover:text-zinc-200">취소</button>
            <button type="button" id="mailAddSubmit" onclick="submitMailAdd()" class="px-4 py-2 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700 transition">추가</button>
        </div>
    </div>
</div>

<!-- 비즈니스 메일 구독 추가 + 결제 모달 (어드민 전용) -->
<div id="addBizSubModal" class="hidden fixed inset-0 z-50 bg-black/50 flex items-center justify-center p-4">
    <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl max-w-lg w-full p-6">
        <div class="flex items-center justify-between mb-4">
            <h3 class="text-base font-bold text-zinc-900 dark:text-white">비즈니스 메일 구독 추가 <span class="text-xs text-amber-600">¥5,000/계정/월</span></h3>
            <button type="button" onclick="closeBizSubModal()" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
            </button>
        </div>
        <div class="space-y-3">
            <div>
                <label class="text-[11px] text-zinc-500 dark:text-zinc-400 block mb-1">계정 수 <span class="text-red-500">*</span></label>
                <input type="number" id="bizAccounts" min="1" max="100" value="1" onchange="recalcBizQuote()" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg">
            </div>
            <div class="bg-zinc-50 dark:bg-zinc-700/50 rounded-lg p-3 text-xs space-y-1">
                <div class="flex justify-between"><span class="text-zinc-500">단가</span><span id="bizQuoteUnit" class="font-mono tabular-nums text-zinc-800 dark:text-zinc-200">¥0</span></div>
                <div class="flex justify-between"><span class="text-zinc-500">호스팅 만료까지</span><span id="bizQuoteMonths" class="font-mono tabular-nums text-zinc-800 dark:text-zinc-200">0개월</span></div>
                <div class="flex justify-between"><span class="text-zinc-500">소계</span><span id="bizQuoteSub" class="font-mono tabular-nums text-zinc-800 dark:text-zinc-200">¥0</span></div>
                <div class="flex justify-between"><span class="text-zinc-500">소비세 (10%)</span><span id="bizQuoteTax" class="font-mono tabular-nums text-zinc-800 dark:text-zinc-200">¥0</span></div>
                <div class="flex justify-between font-bold pt-1 border-t border-zinc-200 dark:border-zinc-600 mt-1"><span class="text-zinc-700 dark:text-zinc-200">합계</span><span id="bizQuoteTotal" class="font-mono tabular-nums text-zinc-900 dark:text-white text-sm">¥0</span></div>
            </div>
            <div>
                <label class="text-[11px] text-zinc-500 dark:text-zinc-400 block mb-1">결제 방식 <span class="text-red-500">*</span></label>
                <div class="flex gap-2">
                    <label class="flex-1 flex items-center gap-1 px-3 py-2 border border-zinc-200 dark:border-zinc-600 rounded-lg cursor-pointer text-xs has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-900/20">
                        <input type="radio" name="bizPayMethod" value="cash" checked onchange="onBizPayMethodChange()" class="sr-only"><span>💴 현금</span>
                    </label>
                    <label class="flex-1 flex items-center gap-1 px-3 py-2 border border-zinc-200 dark:border-zinc-600 rounded-lg cursor-pointer text-xs has-[:checked]:border-blue-500 has-[:checked]:bg-blue-50 dark:has-[:checked]:bg-blue-900/20">
                        <input type="radio" name="bizPayMethod" value="free" onchange="onBizPayMethodChange()" class="sr-only"><span>🎁 무료</span>
                    </label>
                </div>
                <p class="text-[10px] text-zinc-400 mt-1">카드 결제는 별도 흐름 (추후 추가)</p>
            </div>
            <div id="bizCashBox">
                <label class="text-[11px] text-zinc-500 dark:text-zinc-400 block mb-1">받은 금액 (현금) <span class="text-red-500">*</span></label>
                <input type="number" id="bizCashReceived" min="0" placeholder="합계 이상" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg">
            </div>
            <div id="bizFreeBox" class="hidden">
                <label class="text-[11px] text-zinc-500 dark:text-zinc-400 block mb-1">무료 처리 사유 <span class="text-red-500">*</span></label>
                <input type="text" id="bizFreeReason" placeholder="예: 사회적기업 후원" class="w-full px-3 py-2 text-sm border border-zinc-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg">
            </div>
        </div>
        <div class="flex justify-end gap-2 mt-5">
            <button type="button" onclick="closeBizSubModal()" class="px-3 py-1.5 text-xs text-zinc-500 hover:text-zinc-700">취소</button>
            <button type="button" onclick="submitAddBizSub()" id="bizSubBtn" class="px-4 py-1.5 text-xs font-medium text-white bg-amber-600 rounded-lg hover:bg-amber-700">구독 추가 + 결제</button>
        </div>
    </div>
</div>

<script>
var _mailDomain = <?= json_encode($mailDomain, JSON_UNESCAPED_UNICODE) ?>;
var _webmailUrl = <?= json_encode($_webmailUrl, JSON_UNESCAPED_UNICODE) ?>;
var _bizSubExists = <?= $bizSub ? 'true' : 'false' ?>;
var _bizSubId = <?= $bizSub ? (int)$bizSub['id'] : 'null' ?>;

function openWebmail(address) {
    var sep = _webmailUrl.indexOf('?') >= 0 ? '&' : '?';
    window.open(_webmailUrl + sep + '_user=' + encodeURIComponent(address), '_blank', 'noopener,noreferrer');
}

function togglePwForm(btn) {
    var card = btn.closest('.bg-gray-50, .dark\\:bg-zinc-700\\/30') || btn.closest('div[class*="rounded-lg"]');
    var form = card ? card.querySelector('.pw-form') : null;
    if (form) { form.classList.toggle('hidden'); if (!form.classList.contains('hidden')) form.querySelector('.pw-new').focus(); }
}

function changePw(btn, address, subId) {
    var form = btn.closest('.pw-form');
    var pw = form.querySelector('.pw-new').value;
    var pwc = form.querySelector('.pw-confirm').value;
    if (!pw || pw.length < 8) { alert('비밀번호 최소 8자'); return; }
    if (pw !== pwc) { alert('비밀번호 확인이 일치하지 않습니다.'); return; }
    btn.disabled = true; btn.textContent = '처리 중…';
    ajaxPost({ action: 'change_mail_password', subscription_id: subId, address: address, password: pw })
        .then(function(d) {
            btn.disabled = false; btn.textContent = '변경';
            if (d.success) {
                alert('비밀번호가 변경되었습니다.');
                form.classList.add('hidden');
                form.querySelector('.pw-new').value = '';
                form.querySelector('.pw-confirm').value = '';
            } else alert(d.message || '실패');
        });
}

function openMailAddModal() {
    var m = document.getElementById('mailAddModal');
    m.classList.remove('hidden'); m.classList.add('flex');
    document.body.style.overflow = 'hidden';
    setTimeout(function() { var el = document.getElementById('mailAddrLocal'); if (el) el.focus(); }, 50);
}
function closeMailAddModal() {
    var m = document.getElementById('mailAddModal');
    m.classList.add('hidden'); m.classList.remove('flex');
    document.body.style.overflow = '';
    document.getElementById('mailAddrLocal').value = '';
    document.getElementById('mailAddPw').value = '';
    document.getElementById('mailAddPwConfirm').value = '';
}
function submitMailAdd() {
    var local = document.getElementById('mailAddrLocal').value.trim().toLowerCase();
    var pw = document.getElementById('mailAddPw').value;
    var pwc = document.getElementById('mailAddPwConfirm').value;
    if (!/^[a-z0-9._-]{2,32}$/.test(local)) { alert('이메일 로컬 형식 오류 (영문 소문자/숫자/._-, 2~32자)'); return; }
    if (!pw || pw.length < 8) { alert('비밀번호 최소 8자'); return; }
    if (pw !== pwc) { alert('비밀번호 확인이 일치하지 않습니다.'); return; }
    var btn = document.getElementById('mailAddSubmit');
    btn.disabled = true; btn.textContent = '처리 중…';
    // 어드민 전용: subscription_id (basic mail sub) 와 함께 보냄
    var basicSubId = <?= $basicSub ? (int)$basicSub['id'] : 'null' ?>;
    if (!basicSubId) { alert('기본 메일 구독이 없습니다.'); btn.disabled = false; btn.textContent = '추가'; return; }
    ajaxPost({ action: 'add_mail_account', subscription_id: basicSubId, local: local, password: pw })
        .then(function(d) {
            btn.disabled = false; btn.textContent = '추가';
            if (d.success) { alert('메일 계정 추가 완료'); location.reload(); }
            else alert(d.message || '실패');
        });
}
function confirmDeleteMail(address, subId) {
    if (!confirm(address + ' 메일을 삭제하시겠습니까? 메일함 데이터도 함께 정리됩니다.')) return;
    ajaxPost({ action: 'delete_mail_account', subscription_id: subId, address: address })
        .then(function(d) {
            alert(d.message || (d.success ? '삭제 완료' : '실패'));
            if (d.success) location.reload();
        });
}
function upgradeMailToBiz(address) {
    if (!confirm(address + ' 을(를) 비즈니스 메일로 전환합니다.\n비즈니스 메일 구독이 없으면 결제 모달이 열립니다.')) return;
    if (!_bizSubExists) {
        openAddBizMailSubModal();
        alert('먼저 비즈 메일 구독을 추가하세요. 추가 후 다시 시도하세요.');
    } else {
        alert('비즈로 전환 — 추후 구현. 현재는 새 비즈 메일을 추가해 주세요.');
    }
}

// 비즈 메일 구독 추가 (어드민 결제 모달)
function openAddBizMailSubModal() {
    document.getElementById('bizAccounts').value = 1;
    document.getElementById('bizCashReceived').value = '';
    document.getElementById('bizFreeReason').value = '';
    document.querySelectorAll('input[name="bizPayMethod"]').forEach(function(r){ r.checked = (r.value === 'cash'); });
    onBizPayMethodChange();
    document.getElementById('addBizSubModal').classList.remove('hidden');
    recalcBizQuote();
}
function closeBizSubModal() { document.getElementById('addBizSubModal').classList.add('hidden'); }
function onBizPayMethodChange() {
    var m = document.querySelector('input[name="bizPayMethod"]:checked').value;
    document.getElementById('bizCashBox').classList.toggle('hidden', m !== 'cash');
    document.getElementById('bizFreeBox').classList.toggle('hidden', m !== 'free');
}
function fmtYen(n) { return '¥' + Math.round(n).toLocaleString(); }
function recalcBizQuote() {
    var n = parseInt(document.getElementById('bizAccounts').value, 10) || 1;
    ajaxPost({ action: 'admin_bizmail_quote', order_id: orderId, accounts: n }).then(function(d) {
        if (!d.success) return;
        document.getElementById('bizQuoteUnit').textContent   = fmtYen(d.unit_price) + ' / 계정 / 월';
        document.getElementById('bizQuoteMonths').textContent = d.months + '개월';
        document.getElementById('bizQuoteSub').textContent    = fmtYen(d.subtotal);
        document.getElementById('bizQuoteTax').textContent    = fmtYen(d.tax);
        document.getElementById('bizQuoteTotal').textContent  = fmtYen(d.total);
    });
}
function submitAddBizSub() {
    var n = parseInt(document.getElementById('bizAccounts').value, 10) || 1;
    var method = document.querySelector('input[name="bizPayMethod"]:checked').value;
    var body = { action: 'admin_add_bizmail_sub', order_id: orderId, accounts: n, payment_method: method };
    if (method === 'cash') {
        var c = parseInt(document.getElementById('bizCashReceived').value, 10) || 0;
        if (c <= 0) { alert('받은 금액 입력 필요'); return; }
        body.cash_received = c;
    } else if (method === 'free') {
        var r = (document.getElementById('bizFreeReason').value || '').trim();
        if (!r) { alert('무료 처리 사유 필요'); return; }
        body.free_reason = r;
    }
    var btn = document.getElementById('bizSubBtn');
    btn.disabled = true; btn.textContent = '처리 중…';
    ajaxPost(body).then(function(d){
        btn.disabled = false; btn.textContent = '구독 추가 + 결제';
        alert(d.message || (d.success ? '완료' : '실패'));
        if (d.success) { closeBizSubModal(); location.reload(); }
    });
}
</script>
