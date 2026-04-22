<?php
/**
 * VosCMS Marketplace - 아이템 등록 페이지
 *
 * 2026-04-19 기준으로 이 페이지는 market.21ces.com (프로덕션: market.voscms.com) 로 이관되었다.
 * 이 페이지에 들어오면 market 의 /admin/items/create 로 안내한다.
 * (자동 리디렉션은 하지 않는다 — market 관리자 세션이 별도이므로 SSO 가 생기기 전까지 수동 이동)
 */
include __DIR__ . '/_head.php';

// 환경별 market URL 분기 — voscms 의 .env 에 MARKET_ADMIN_URL 이 있으면 그 값, 없으면 기본값.
$marketAdminUrl = $_ENV['MARKET_ADMIN_URL'] ?? 'https://market.21ces.com/admin/items/create';
?>

<div class="max-w-3xl mx-auto">
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-8">
        <div class="flex items-start gap-4">
            <div class="w-12 h-12 bg-indigo-100 dark:bg-indigo-900/30 rounded-lg flex items-center justify-center flex-shrink-0">
                <svg class="w-6 h-6 text-indigo-600 dark:text-indigo-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                </svg>
            </div>
            <div class="flex-1">
                <h1 class="text-xl font-bold text-zinc-900 dark:text-white mb-2">아이템 등록 페이지 이전 안내</h1>
                <p class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed mb-4">
                    VosCMS 마켓플레이스 아이템 등록 기능은 독립 제품인 <strong>market.21ces.com</strong> 으로 이관되었습니다.
                    본사 운영자 계정으로 market 관리자 포털에 로그인하여 아이템을 등록·관리해 주세요.
                </p>
                <p class="text-xs text-zinc-500 dark:text-zinc-500 mb-6">
                    · 개발 환경: <code class="px-1.5 py-0.5 bg-zinc-100 dark:bg-zinc-700 rounded">https://market.21ces.com</code><br>
                    · 프로덕션: <code class="px-1.5 py-0.5 bg-zinc-100 dark:bg-zinc-700 rounded">https://market.voscms.com</code> (배포 예정)
                </p>
                <div class="flex items-center gap-3">
                    <a href="<?= htmlspecialchars($marketAdminUrl) ?>" target="_blank" rel="noopener"
                       class="inline-flex items-center gap-2 px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 text-white text-sm font-medium rounded-lg transition-colors shadow-sm">
                        market 관리자 포털로 이동
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 6H6a2 2 0 00-2 2v10a2 2 0 002 2h10a2 2 0 002-2v-4M14 4h6m0 0v6m0-6L10 14"/></svg>
                    </a>
                    <a href="<?= $adminUrl ?>/marketplace" class="px-5 py-2.5 text-sm text-zinc-600 dark:text-zinc-400 hover:text-zinc-800 dark:hover:text-zinc-200">
                        &larr; 마켓플레이스 돌아가기
                    </a>
                </div>
            </div>
        </div>
    </div>

    <div class="mt-6 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-lg">
        <p class="text-xs text-amber-700 dark:text-amber-300 leading-relaxed">
            <strong>참고:</strong> 이 페이지는 레거시 URL 유지를 위해 안내 페이지로 남겨둔 것입니다. 향후 market 포털에 SSO 가 적용되면 자동 이동으로 변경됩니다.
        </p>
    </div>
</div>

<?php include __DIR__ . '/_foot.php'; ?>
