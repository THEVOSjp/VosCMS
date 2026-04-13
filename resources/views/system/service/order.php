<?php
/**
 * VosCMS 서비스 신청 — 시스템 페이지
 * 도메인 + 호스팅 + 부가서비스 원스톱 신청
 */
require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
$isLoggedIn = \RzxLib\Core\Auth\Auth::check();
$currentUser = $isLoggedIn ? \RzxLib\Core\Auth\Auth::user() : null;
$baseUrl = rtrim($config['app_url'] ?? '', '/');
$isAdmin = !empty($_SESSION['admin_id']);

// 서비스 설정 (DB에서 로드, 없으면 기본값)
$serviceSettings = [];
try {
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
    $sStmt = $pdo->prepare("SELECT `key`, `value` FROM {$prefix}settings WHERE `key` LIKE 'service_%'");
    $sStmt->execute();
    while ($r = $sStmt->fetch(PDO::FETCH_ASSOC)) $serviceSettings[$r['key']] = $r['value'];
} catch (\Throwable $e) {}

$pageWidth = $serviceSettings['service_page_width'] ?? '4xl';
$widthClass = match($pageWidth) {
    'lg' => 'max-w-lg', 'xl' => 'max-w-xl', '2xl' => 'max-w-2xl',
    '3xl' => 'max-w-3xl', '5xl' => 'max-w-5xl', '6xl' => 'max-w-6xl',
    '7xl' => 'max-w-7xl', 'full' => 'max-w-full px-8',
    default => 'max-w-4xl',
};

$pageTitle = __('service.order.title') ?? 'VosCMS 서비스 신청';
include BASE_PATH . '/skins/layouts/' . ($siteSettings['site_layout'] ?? 'modern') . '/header.php';
?>

<!-- 헤더 -->
<div class="bg-gradient-to-r from-blue-600 to-blue-800 dark:from-blue-900 dark:to-zinc-900 text-white py-12">
    <div class="<?= $widthClass ?> mx-auto px-4 text-center relative">
        <h1 class="text-3xl font-bold mb-2"><?= $pageTitle ?></h1>
        <p class="text-blue-100 dark:text-blue-200">원스톱 서비스로 홈페이지를 시작하세요.</p>
        <?php if ($isAdmin): ?>
        <button onclick="document.getElementById('adminSettingsModal').classList.remove('hidden')" class="absolute right-4 top-1/2 -translate-y-1/2 p-2 text-white/60 hover:text-white hover:bg-white/10 rounded-lg transition" title="환경 설정">
            <svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
        </button>
        <?php endif; ?>
    </div>
</div>

<div class="<?= $widthClass ?> mx-auto px-4 py-8 space-y-8">

    <!-- ① 도메인 -->
    <section class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-gray-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 dark:bg-zinc-800/50 border-b border-gray-200 dark:border-zinc-700">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-bold">1</span>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">도메인</h2>
                <span class="text-xs text-gray-400 dark:text-zinc-500 ml-1">선택사항</span>
            </div>
        </div>
        <div class="p-6">
            <div class="flex items-center gap-3 mb-4">
                <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="domain_option" value="new" class="text-blue-600" checked><span class="text-sm font-medium text-gray-700 dark:text-zinc-300">신규 도메인 등록</span></label>
                <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="domain_option" value="existing" class="text-blue-600"><span class="text-sm font-medium text-gray-700 dark:text-zinc-300">기존 도메인 사용</span></label>
                <label class="flex items-center gap-2 cursor-pointer"><input type="radio" name="domain_option" value="none" class="text-blue-600"><span class="text-sm font-medium text-gray-700 dark:text-zinc-300">나중에</span></label>
            </div>
            <div id="domainSearch">
                <div class="flex gap-2">
                    <input type="text" id="domainInput" placeholder="원하는 도메인명을 입력하세요 (예: mycompany)" class="flex-1 px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 text-sm" onkeydown="if(event.key==='Enter')searchDomain()">
                    <button onclick="searchDomain()" class="px-6 py-3 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition text-sm whitespace-nowrap">검색</button>
                </div>
                <p class="text-xs text-gray-400 dark:text-zinc-500 mt-2">* .com, .net 등 확장자 없이 도메인명만 입력하세요.</p>
                <div id="domainResults" class="hidden mt-4">
                    <div id="domainLoading" class="hidden text-center py-6">
                        <div class="inline-flex items-center gap-2 text-sm text-gray-500 dark:text-zinc-400">
                            <svg class="w-5 h-5 animate-spin text-blue-600" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"/><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8v4a4 4 0 00-4 4H4z"/></svg>
                            도메인을 검색 중입니다...
                        </div>
                    </div>
                    <div id="domainList" class="space-y-2"></div>
                    <div id="domainConfirmWrap" class="hidden mt-4 flex items-center justify-between p-4 bg-blue-50 dark:bg-blue-900/30 rounded-xl border border-blue-200 dark:border-blue-800">
                        <p class="text-sm text-blue-800 dark:text-blue-200"><span id="domainSelectedCount" class="font-bold">0</span>개 도메인 선택됨 · 합계 <span id="domainSelectedTotal" class="font-bold">0원</span></p>
                        <button onclick="confirmDomains()" class="px-6 py-2.5 bg-blue-600 text-white font-semibold rounded-lg hover:bg-blue-700 transition text-sm">확인</button>
                    </div>
                    <div id="domainConfirmed" class="hidden mt-4 space-y-2"></div>
                </div>
            </div>
        </div>
    </section>

    <!-- ② 웹 호스팅 -->
    <section class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-gray-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-6 py-4 bg-gray-50 dark:bg-zinc-800/50 border-b border-gray-200 dark:border-zinc-700">
            <div class="flex items-center gap-2">
                <span class="w-7 h-7 bg-blue-600 text-white rounded-full flex items-center justify-center text-sm font-bold">2</span>
                <h2 class="text-lg font-bold text-gray-900 dark:text-white">웹 호스팅</h2>
                <span class="text-xs text-blue-600 font-medium ml-1">필수</span>
            </div>
        </div>
        <div class="p-6">
            <div class="grid grid-cols-3 md:grid-cols-5 gap-3 mb-6">
                <label class="hosting-option cursor-pointer border-2 border-gray-200 dark:border-zinc-600 rounded-xl p-4 text-center hover:border-green-400 transition">
                    <input type="radio" name="hosting_plan" value="free" class="hidden">
                    <p class="text-xs text-green-600 font-semibold mb-1">무료</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">50MB</p>
                    <p class="text-green-600 font-bold mt-1">0원</p>
                    <p class="text-[10px] text-gray-400 dark:text-zinc-500 mt-0.5">광고 포함</p>
                </label>
                <label class="hosting-option cursor-pointer border-2 border-gray-200 dark:border-zinc-600 rounded-xl p-4 text-center hover:border-blue-400 transition">
                    <input type="radio" name="hosting_plan" value="500mb" class="hidden">
                    <p class="text-xs text-gray-400 dark:text-zinc-500 mb-1">입문</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">500MB</p>
                    <p class="text-blue-600 font-bold mt-1">3,000<span class="text-xs font-normal text-gray-400">/월</span></p>
                </label>
                <label class="hosting-option selected cursor-pointer border-2 border-blue-500 rounded-xl p-4 text-center bg-blue-50 dark:bg-blue-900/30">
                    <input type="radio" name="hosting_plan" value="1g" class="hidden" checked>
                    <p class="text-xs text-blue-600 font-semibold mb-1">추천</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">1GB</p>
                    <p class="text-blue-600 font-bold mt-1">5,000<span class="text-xs font-normal text-gray-400">/월</span></p>
                </label>
                <label class="hosting-option cursor-pointer border-2 border-gray-200 dark:border-zinc-600 rounded-xl p-4 text-center hover:border-blue-400 transition">
                    <input type="radio" name="hosting_plan" value="3g" class="hidden">
                    <p class="text-xs text-gray-400 dark:text-zinc-500 mb-1">비즈니스</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">3GB</p>
                    <p class="text-blue-600 font-bold mt-1">10,000<span class="text-xs font-normal text-gray-400">/월</span></p>
                </label>
                <label class="hosting-option cursor-pointer border-2 border-gray-200 dark:border-zinc-600 rounded-xl p-4 text-center hover:border-blue-400 transition">
                    <input type="radio" name="hosting_plan" value="5g" class="hidden">
                    <p class="text-xs text-gray-400 dark:text-zinc-500 mb-1">프로</p>
                    <p class="text-lg font-bold text-gray-900 dark:text-white">5GB</p>
                    <p class="text-blue-600 font-bold mt-1">18,000<span class="text-xs font-normal text-gray-400">/월</span></p>
                </label>
            </div>
            <details class="mb-6">
                <summary class="text-xs text-blue-600 cursor-pointer hover:underline">더 큰 플랜 보기</summary>
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 mt-3">
                    <label class="hosting-option cursor-pointer border-2 border-gray-200 dark:border-zinc-600 rounded-xl p-4 text-center hover:border-blue-400 transition">
                        <input type="radio" name="hosting_plan" value="10g" class="hidden"><p class="text-xs text-gray-400 mb-1">엔터프라이즈</p><p class="text-lg font-bold text-gray-900 dark:text-white">10GB</p><p class="text-blue-600 font-bold mt-1">30,000<span class="text-xs font-normal text-gray-400">/월</span></p></label>
                    <label class="hosting-option cursor-pointer border-2 border-gray-200 dark:border-zinc-600 rounded-xl p-4 text-center hover:border-blue-400 transition">
                        <input type="radio" name="hosting_plan" value="15g" class="hidden"><p class="text-xs text-gray-400 mb-1">대용량</p><p class="text-lg font-bold text-gray-900 dark:text-white">15GB</p><p class="text-blue-600 font-bold mt-1">45,000<span class="text-xs font-normal text-gray-400">/월</span></p></label>
                    <label class="hosting-option cursor-pointer border-2 border-gray-200 dark:border-zinc-600 rounded-xl p-4 text-center hover:border-blue-400 transition">
                        <input type="radio" name="hosting_plan" value="20g" class="hidden"><p class="text-xs text-gray-400 mb-1">프리미엄</p><p class="text-lg font-bold text-gray-900 dark:text-white">20GB</p><p class="text-blue-600 font-bold mt-1">55,000<span class="text-xs font-normal text-gray-400">/월</span></p></label>
                    <label class="hosting-option cursor-pointer border-2 border-gray-200 dark:border-zinc-600 rounded-xl p-4 text-center hover:border-blue-400 transition">
                        <input type="radio" name="hosting_plan" value="30g" class="hidden"><p class="text-xs text-gray-400 mb-1">맥스</p><p class="text-lg font-bold text-gray-900 dark:text-white">30GB</p><p class="text-blue-600 font-bold mt-1">80,000<span class="text-xs font-normal text-gray-400">/월</span></p></label>
                </div>
            </details>

            <!-- 계약 기간 -->
            <div class="flex flex-wrap items-center gap-3 p-4 bg-gray-50 dark:bg-zinc-700/50 rounded-xl mb-4">
                <span class="text-sm font-medium text-gray-600 dark:text-zinc-300">계약 기간:</span>
                <label class="flex items-center gap-1.5 cursor-pointer"><input type="radio" name="hosting_period" value="1" class="text-blue-600"><span class="text-sm text-gray-700 dark:text-zinc-300">1개월</span></label>
                <label class="flex items-center gap-1.5 cursor-pointer"><input type="radio" name="hosting_period" value="6" class="text-blue-600"><span class="text-sm text-gray-700 dark:text-zinc-300">6개월 <span class="text-blue-600 text-xs">-5%</span></span></label>
                <label class="flex items-center gap-1.5 cursor-pointer"><input type="radio" name="hosting_period" value="12" class="text-blue-600" checked><span class="text-sm text-gray-700 dark:text-zinc-300">1년 <span class="text-blue-600 text-xs font-semibold">-10%</span></span></label>
                <label class="flex items-center gap-1.5 cursor-pointer"><input type="radio" name="hosting_period" value="24" class="text-blue-600"><span class="text-sm text-gray-700 dark:text-zinc-300">2년 <span class="text-blue-600 text-xs font-semibold">-15%</span></span></label>
                <label class="flex items-center gap-1.5 cursor-pointer"><input type="radio" name="hosting_period" value="36" class="text-blue-600"><span class="text-sm text-gray-700 dark:text-zinc-300">3년 <span class="text-blue-600 text-xs font-semibold">-20%</span></span></label>
                <label class="flex items-center gap-1.5 cursor-pointer"><input type="radio" name="hosting_period" value="60" class="text-blue-600"><span class="text-sm text-gray-700 dark:text-zinc-300">5년 <span class="text-red-500 text-xs font-semibold">-30%</span></span></label>
            </div>

            <!-- 추가 용량 -->
            <div class="flex items-center gap-3 p-4 bg-gray-50 dark:bg-zinc-700/50 rounded-xl mb-4">
                <span class="text-sm font-medium text-gray-600 dark:text-zinc-300">추가 용량:</span>
                <select class="px-3 py-2 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg text-sm">
                    <option value="0">추가 없음</option>
                    <option>+1GB (2,000원/월)</option><option>+3GB (5,000원/월)</option><option>+5GB (8,000원/월)</option>
                    <option>+10GB (14,000원/월)</option><option>+20GB (25,000원/월)</option><option>+50GB (50,000원/월)</option>
                </select>
            </div>

            <div class="grid grid-cols-2 md:grid-cols-4 gap-2 text-xs text-gray-500 dark:text-zinc-400">
                <span class="flex items-center gap-1"><svg class="w-3.5 h-3.5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>SSL 인증서 무료</span>
                <span class="flex items-center gap-1"><svg class="w-3.5 h-3.5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>일일 백업</span>
                <span class="flex items-center gap-1"><svg class="w-3.5 h-3.5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>PHP 8.3</span>
                <span class="flex items-center gap-1"><svg class="w-3.5 h-3.5 text-green-500" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M16.707 5.293a1 1 0 010 1.414l-8 8a1 1 0 01-1.414 0l-4-4a1 1 0 011.414-1.414L8 12.586l7.293-7.293a1 1 0 011.414 0z"/></svg>기본 메일 5개</span>
            </div>
        </div>
    </section>

    <!-- ③ 부가 서비스 -->
    <?php include __DIR__ . '/_addons.php'; ?>

    <!-- ④ 신청자 정보 -->
    <?php include __DIR__ . '/_applicant.php'; ?>

    <!-- ⑤ 결제 방법 -->
    <?php include __DIR__ . '/_payment.php'; ?>

    <!-- ⑥ 주문 요약 -->
    <?php include __DIR__ . '/_summary.php'; ?>

</div>

<!-- 관리자 설정 모달 -->
<?php if ($isAdmin) include __DIR__ . '/_admin_settings.php'; ?>

<!-- JS -->
<script src="<?= $baseUrl ?>/resources/views/system/service/order.js"></script>

<?php
include BASE_PATH . '/skins/layouts/' . ($siteSettings['site_layout'] ?? 'modern') . '/footer.php';
?>
