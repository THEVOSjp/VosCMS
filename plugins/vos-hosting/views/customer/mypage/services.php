<?php
/**
 * 마이페이지 — 서비스 관리
 * 주문번호별 요약 카드 + 상세 관리 링크
 */
use RzxLib\Core\Auth\Auth;

if (!$isLoggedIn) {
    header("Location: {$baseUrl}/login");
    exit;
}

// plugin lang (services) 로드
$_svcLangFile = BASE_PATH . '/plugins/vos-hosting/lang/' . \RzxLib\Core\I18n\Translator::getLocale() . '/services.php';
if (!file_exists($_svcLangFile)) {
    $_svcLangFile = BASE_PATH . '/plugins/vos-hosting/lang/en/services.php';
}
if (file_exists($_svcLangFile)) {
    \RzxLib\Core\I18n\Translator::merge('services', require $_svcLangFile);
}

$user = Auth::user();
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

// 내 구독 목록
$subsStmt = $pdo->prepare("SELECT s.*, o.order_number FROM {$prefix}subscriptions s JOIN {$prefix}orders o ON s.order_id = o.id WHERE s.user_id = ? ORDER BY s.expires_at ASC");
$subsStmt->execute([$currentUser['id']]);
$subscriptions = $subsStmt->fetchAll(PDO::FETCH_ASSOC);

// 통화 포맷
$_dispSymbols = ['KRW'=>'₩','USD'=>'$','JPY'=>'¥','CNY'=>'¥','EUR'=>'€'];
$fmtPrice = function($amount, $currency = 'JPY') use ($_dispSymbols) {
    $sym = $_dispSymbols[$currency] ?? $currency;
    $pre = in_array($currency, ['USD','JPY','CNY','EUR']);
    $f = number_format((int)$amount);
    return $pre ? $sym . $f : $f . $sym;
};

// 주문번호별 그룹화
$orderGroups = [];
foreach ($subscriptions as $sub) {
    $on = $sub['order_number'];
    if (!isset($orderGroups[$on])) $orderGroups[$on] = [];
    $orderGroups[$on][] = $sub;
}

// 만기 7일 이내 구독
$soonExpiring = array_filter($subscriptions, function($s) {
    return $s['status'] === 'active' && strtotime($s['expires_at']) <= strtotime('+7 days');
});

$typeLabels = [
    'hosting' => __('services.mypage.type_hosting'),
    'domain' => __('services.mypage.type_domain'),
    'maintenance' => __('services.mypage.type_maintenance'),
    'mail' => __('services.mypage.type_mail'),
    'addon' => __('services.mypage.type_addon'),
    'support' => __('services.mypage.type_support'),
];
$statusLabels = [
    'active' => [__('services.mypage.status_active'), 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'],
    'expired' => [__('services.mypage.status_expired'), 'bg-gray-100 text-gray-500 dark:bg-zinc-700 dark:text-zinc-400'],
    'cancelled' => [__('services.mypage.status_cancelled'), 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400'],
    'suspended' => [__('services.mypage.status_suspended'), 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'],
    'pending' => [__('services.mypage.status_pending'), 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'],
];
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="lg:flex lg:gap-8">
        <?php $sidebarActive = 'services'; include BASE_PATH . '/resources/views/components/mypage-sidebar.php'; ?>

        <div class="flex-1 min-w-0">
    <div class="flex items-center justify-between mb-6">
        <h1 class="text-xl font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars(__('services.mypage.title')) ?></h1>
        <a href="<?= $baseUrl ?>/service/order" class="px-4 py-2 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-xs font-medium"><?= htmlspecialchars(__('services.mypage.btn_new_service')) ?></a>
    </div>

    <?php if (!empty($soonExpiring)): ?>
    <div class="mb-6 p-4 bg-amber-50 dark:bg-amber-900/20 border border-amber-200 dark:border-amber-800 rounded-xl">
        <div class="flex items-start gap-3">
            <svg class="w-5 h-5 text-amber-600 mt-0.5 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-2.5L13.732 4c-.77-.833-1.964-.833-2.732 0L4.082 16.5c-.77.833.192 2.5 1.732 2.5z"/></svg>
            <div>
                <p class="text-sm font-medium text-amber-800 dark:text-amber-200"><?= htmlspecialchars(__('services.mypage.expiring_alert')) ?></p>
                <?php foreach ($soonExpiring as $s): ?>
                <p class="text-xs text-amber-600 dark:text-amber-300 mt-1"><?= htmlspecialchars($s['label']) ?> — <?= date('Y-m-d', strtotime($s['expires_at'])) ?></p>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <?php if (empty($subscriptions)): ?>
    <div class="text-center py-16">
        <svg class="w-16 h-16 text-zinc-300 dark:text-zinc-600 mx-auto mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
        <p class="text-zinc-400 dark:text-zinc-500 mb-4"><?= htmlspecialchars(__('services.mypage.empty_msg')) ?></p>
        <a href="<?= $baseUrl ?>/service/order" class="px-6 py-2.5 bg-blue-600 text-white rounded-lg hover:bg-blue-700 text-sm font-medium"><?= htmlspecialchars(__('services.mypage.btn_apply')) ?></a>
    </div>
    <?php else: ?>
    <div class="space-y-3">
        <?php foreach ($orderGroups as $orderNum => $subs):
            $groupStatuses = array_unique(array_column($subs, 'status'));
            $groupStatus = in_array('active', $groupStatuses) ? 'active' : (in_array('pending', $groupStatuses) ? 'pending' : ($groupStatuses[0] ?? 'expired'));
            $gst = $statusLabels[$groupStatus] ?? [__('services.mypage.status_unknown'), 'bg-gray-100 text-gray-500'];
            $groupExpiringSoon = !empty(array_filter($subs, function($s) {
                return $s['status'] === 'active' && strtotime($s['expires_at']) <= strtotime('+7 days');
            }));
            $groupStart = min(array_column($subs, 'started_at'));
            $groupEnd = max(array_column($subs, 'expires_at'));
            $groupTotal = array_sum(array_column($subs, 'billing_amount'));
            $groupCurrency = $subs[0]['currency'] ?? 'JPY';
            $serviceTags = array_unique(array_map(function($s) use ($typeLabels) {
                return $typeLabels[$s['type']] ?? $s['type'];
            }, $subs));
            $primaryDomain = '';
            foreach ($subs as $s) {
                $m = json_decode($s['metadata'] ?? '{}', true) ?: [];
                if ($s['type'] === 'domain' && !empty($m['domains'])) { $primaryDomain = $m['domains'][0]; break; }
            }
        ?>
        <a href="<?= $baseUrl ?>/mypage/services/<?= htmlspecialchars($orderNum) ?>"
           class="block bg-white dark:bg-zinc-800 rounded-xl border <?= $groupExpiringSoon ? 'border-amber-300 dark:border-amber-700' : 'border-gray-200 dark:border-zinc-700' ?> hover:border-blue-300 dark:hover:border-blue-700 transition group">
            <div class="px-4 py-4 flex items-center justify-between gap-4">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-1.5">
                        <span class="text-[10px] px-2 py-0.5 rounded-full font-medium <?= $gst[1] ?>"><?= $gst[0] ?></span>
                        <span class="text-[10px] font-mono text-zinc-400">#<?= htmlspecialchars($orderNum) ?></span>
                        <?php if ($groupExpiringSoon): ?>
                        <span class="text-[10px] px-1.5 py-0.5 bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400 rounded-full font-medium"><?= htmlspecialchars(__('services.mypage.expiring_badge')) ?></span>
                        <?php endif; ?>
                    </div>
                    <?php if ($primaryDomain): ?>
                    <p class="text-sm font-semibold text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($primaryDomain) ?></p>
                    <?php endif; ?>
                    <div class="flex flex-wrap items-center gap-1.5 mt-1">
                        <?php foreach ($serviceTags as $tag): ?>
                        <span class="text-[10px] px-2 py-0.5 bg-gray-100 dark:bg-zinc-700 text-zinc-500 dark:text-zinc-400 rounded-full"><?= htmlspecialchars($tag) ?></span>
                        <?php endforeach; ?>
                    </div>
                    <div class="flex items-center gap-3 mt-2 text-xs text-zinc-400">
                        <span><?= date('Y-m-d', strtotime($groupStart)) ?> ~ <?= date('Y-m-d', strtotime($groupEnd)) ?></span>
                        <span class="font-medium <?= $groupTotal > 0 ? 'text-zinc-600 dark:text-zinc-300' : 'text-green-600 dark:text-green-400' ?>"><?= $groupTotal > 0 ? $fmtPrice($groupTotal, $groupCurrency) : __('services.order.summary.free') ?></span>
                        <span class="text-zinc-300 dark:text-zinc-600"><?= __('services.mypage.count_services', [':count' => count($subs)]) ?></span>
                    </div>
                </div>
                <svg class="w-5 h-5 text-zinc-300 dark:text-zinc-600 group-hover:text-blue-500 transition shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
            </div>
        </a>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>
    </div>
</div>
