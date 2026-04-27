<?php
/**
 * 마이페이지 — 서비스 상세 관리
 * /mypage/services/{order_number}
 */
use RzxLib\Core\Auth\Auth;

if (!$isLoggedIn) { header("Location: {$baseUrl}/login"); exit; }

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

// 서비스 설정 로드 (라벨 → _id 매핑용, db_trans 호출 시 사용)
$_svcSettings = [];
try {
    $_sStmt = $pdo->prepare("SELECT `key`, `value` FROM {$prefix}settings WHERE `key` IN ('service_addons','service_maintenance','service_hosting_plans')");
    $_sStmt->execute();
    while ($_sr = $_sStmt->fetch(PDO::FETCH_ASSOC)) $_svcSettings[$_sr['key']] = $_sr['value'];
} catch (\Throwable $e) {}

// 라벨 → _id 매핑
$_addonIdByLabel = [];
foreach ((json_decode($_svcSettings['service_addons'] ?? '[]', true) ?: []) as $_a) {
    if (!empty($_a['_id']) && !empty($_a['label'])) $_addonIdByLabel[$_a['label']] = $_a['_id'];
}
$_maintIdByLabel = [];
foreach ((json_decode($_svcSettings['service_maintenance'] ?? '[]', true) ?: []) as $_m) {
    if (!empty($_m['_id']) && !empty($_m['label'])) $_maintIdByLabel[$_m['label']] = $_m['_id'];
}
$_hostingPlansData = json_decode($_svcSettings['service_hosting_plans'] ?? '[]', true) ?: [];

// 라벨 다국어 변환 헬퍼
$_localizeLabel = function($sub) use ($_addonIdByLabel, $_maintIdByLabel, $_hostingPlansData, $order) {
    $label = $sub['label'] ?? '';
    $type = $sub['type'] ?? '';
    if ($type === 'hosting') {
        $meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
        $cap = $meta['capacity'] ?? $order['hosting_capacity'] ?? '';
        // 어드민 설정의 plan 다국어 라벨 활용
        $planLabelDisplay = '';
        foreach ($_hostingPlansData as $p) {
            if (($p['capacity'] ?? '') === $cap && !empty($p['_id'])) {
                $planLabelDisplay = db_trans("service.hosting.plan.{$p['_id']}.label", null, $p['label'] ?? '');
                break;
            }
        }
        $prefix = rtrim(__('services.order.summary.hosting_label_prefix'));
        $parts = array_filter([$prefix, $planLabelDisplay, $cap], fn($v) => $v !== '' && $v !== null);
        return implode(' ', $parts);
    }
    if ($type === 'addon' && isset($_addonIdByLabel[$label])) {
        return db_trans("service.addon.{$_addonIdByLabel[$label]}.label", null, $label);
    }
    if ($type === 'maintenance') {
        // '유지보수 ' prefix + plan label
        $stripped = preg_replace('/^유지보수\s*/u', '', $label);
        $matchedId = $_maintIdByLabel[$stripped] ?? $_maintIdByLabel[$label] ?? null;
        $name = $matchedId ? db_trans("service.maintenance.{$matchedId}.label", null, $stripped) : $stripped;
        return __('services.order.summary.maint_label_prefix') . $name;
    }
    return $label;
};

// 주문 로드 + 소유자 확인
$orderStmt = $pdo->prepare("SELECT * FROM {$prefix}orders WHERE order_number = ? AND user_id = ?");
$orderStmt->execute([$serviceOrderNumber ?? '', $currentUser['id']]);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    http_response_code(404);
    echo '<div class="max-w-7xl mx-auto px-4 py-16 text-center"><p class="text-zinc-400">' . htmlspecialchars(__('services.detail.not_found')) . '</p><a href="' . $baseUrl . '/mypage/services" class="text-blue-600 hover:underline text-sm mt-4 inline-block">' . htmlspecialchars(__('services.detail.back_to_list')) . '</a></div>';
    return;
}

// 구독 목록
$subsStmt = $pdo->prepare("SELECT * FROM {$prefix}subscriptions WHERE order_id = ? ORDER BY FIELD(type,'hosting','domain','mail','maintenance','addon','support'), id");
$subsStmt->execute([$order['id']]);
$subscriptions = $subsStmt->fetchAll(PDO::FETCH_ASSOC);

// 주문 로그
$logsStmt = $pdo->prepare("SELECT * FROM {$prefix}order_logs WHERE order_id = ? ORDER BY created_at DESC LIMIT 20");
$logsStmt->execute([$order['id']]);
$orderLogs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);

// 타입별 그룹
$servicesByType = [];
foreach ($subscriptions as $sub) {
    $servicesByType[$sub['type']][] = $sub;
}

// 자동연장 대상 (recurring) 구독이 있는지, 전체 ON인지 판단
$renewableSubs = array_filter($subscriptions, fn($s) => ($s['service_class'] ?? '') === 'recurring' && $s['status'] === 'active');
$hasRenewable = !empty($renewableSubs);
$allAutoRenew = $hasRenewable && !array_filter($renewableSubs, fn($s) => !$s['auto_renew']);
// 무료 서비스만 있는지
$hasFreeOnly = !$hasRenewable && !empty(array_filter($subscriptions, fn($s) => ($s['service_class'] ?? '') === 'free' && $s['status'] === 'active'));

// 통화 포맷
$_dispSymbols = ['KRW'=>'₩','USD'=>'$','JPY'=>'¥','CNY'=>'¥','EUR'=>'€'];
$fmtPrice = function($amount, $currency = 'JPY') use ($_dispSymbols) {
    $sym = $_dispSymbols[$currency] ?? $currency;
    $pre = in_array($currency, ['USD','JPY','CNY','EUR']);
    $f = number_format((int)$amount);
    return $pre ? $sym . $f : $f . $sym;
};

$typeLabels = [
    'hosting' => __('services.mypage.type_hosting'),
    'domain' => __('services.mypage.type_domain'),
    'maintenance' => __('services.mypage.type_maintenance'),
    'mail' => __('services.mypage.type_mail'),
    'addon' => __('services.mypage.type_addon'),
    'support' => __('services.mypage.type_support'),
];
$typeIcons = [
    'hosting' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>',
    'domain' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>',
    'maintenance' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
    'mail' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
    'addon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>',
];
$statusLabels = [
    'active' => [__('services.mypage.status_active'), 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'],
    'expired' => [__('services.mypage.status_expired'), 'bg-gray-100 text-gray-500 dark:bg-zinc-700 dark:text-zinc-400'],
    'cancelled' => [__('services.mypage.status_cancelled'), 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400'],
    'suspended' => [__('services.mypage.status_suspended'), 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'],
    'pending' => [__('services.mypage.status_pending'), 'bg-blue-100 text-blue-600 dark:bg-blue-900/30 dark:text-blue-400'],
    'paid' => [__('services.detail.s_paid'), 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'],
    'failed' => [__('services.detail.s_failed'), 'bg-red-100 text-red-600 dark:bg-red-900/30 dark:text-red-400'],
];
$ost = $statusLabels[$order['status']] ?? [__('services.mypage.status_unknown'), 'bg-gray-100 text-gray-500'];

// 주 도메인
$primaryDomain = $order['domain'] ?? '';
$tabTypes = array_keys($servicesByType);
$firstTab = $tabTypes[0] ?? '';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="lg:flex lg:gap-8">
        <?php $sidebarActive = 'services'; include BASE_PATH . '/resources/views/components/mypage-sidebar.php'; ?>

        <div class="flex-1 min-w-0">

    <!-- 헤더 -->
    <div class="flex items-center justify-between mb-6">
        <div class="flex items-center gap-3">
            <a href="<?= $baseUrl ?>/mypage/services" class="text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
            </a>
            <div>
                <div class="flex items-center gap-2">
                    <h1 class="text-lg font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($primaryDomain ?: $order['order_number']) ?></h1>
                    <span class="text-[10px] px-2 py-0.5 rounded-full font-medium <?= $ost[1] ?>"><?= $ost[0] ?></span>
                </div>
                <p class="text-xs text-zinc-400 font-mono">#<?= htmlspecialchars($order['order_number']) ?></p>
            </div>
        </div>
    </div>

    <!-- 신청 정보 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden mb-6">
        <div class="px-4 py-3 border-b border-gray-100 dark:border-zinc-700 flex items-center justify-between">
            <h2 class="text-sm font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars(__('services.detail.order_info')) ?></h2>
            <?php if ($hasRenewable): ?>
            <div class="flex items-center gap-2">
                <span class="text-xs text-zinc-400"><?= htmlspecialchars(__('services.detail.auto_renew')) ?></span>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox" id="masterAutoRenew" class="sr-only peer" <?= $allAutoRenew ? 'checked' : '' ?>
                           onchange="toggleAllAutoRenew(this.checked)">
                    <div class="w-9 h-5 bg-zinc-200 rounded-full peer dark:bg-zinc-600 peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:rounded-full after:h-4 after:w-4 after:transition-all peer-checked:bg-blue-600"></div>
                </label>
            </div>
            <?php elseif ($hasFreeOnly): ?>
            <button onclick="requestAllRenewal()" class="text-xs px-3 py-1.5 bg-blue-50 text-blue-600 dark:bg-blue-900/20 dark:text-blue-400 rounded-lg hover:bg-blue-100 transition"><?= htmlspecialchars(__('services.detail.btn_renewal')) ?></button>
            <?php endif; ?>
        </div>
        <div class="p-4">
        <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
            <div>
                <p class="text-[10px] text-zinc-400 uppercase tracking-wider mb-0.5"><?= htmlspecialchars(__('services.detail.f_contract_period')) ?></p>
                <p class="font-medium text-zinc-900 dark:text-white"><?= $order['contract_months'] ?><?= htmlspecialchars(__('services.order.hosting.unit_month')) ?></p>
                <p class="text-xs text-zinc-400"><?= $order['started_at'] ? date('Y-m-d', strtotime($order['started_at'])) : '-' ?> ~ <?= $order['expires_at'] ? date('Y-m-d', strtotime($order['expires_at'])) : '-' ?></p>
            </div>
            <div>
                <p class="text-[10px] text-zinc-400 uppercase tracking-wider mb-0.5"><?= htmlspecialchars(__('services.detail.f_payment_amount')) ?></p>
                <p class="font-medium text-zinc-900 dark:text-white"><?= (int)$order['total'] > 0 ? $fmtPrice($order['total'], $order['currency']) : __('services.order.summary.free') ?></p>
                <p class="text-xs text-zinc-400"><?= $order['payment_method'] === 'free' ? __('services.order.summary.free') : ($order['payment_method'] === 'bank' ? __('services.order.payment.method_bank') : __('services.detail.pay_card')) ?></p>
            </div>
            <div>
                <p class="text-[10px] text-zinc-400 uppercase tracking-wider mb-0.5"><?= htmlspecialchars(__('services.detail.f_hosting')) ?></p>
                <p class="font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($order['hosting_capacity'] ?: '-') ?></p>
            </div>
            <div>
                <p class="text-[10px] text-zinc-400 uppercase tracking-wider mb-0.5"><?= htmlspecialchars(__('services.detail.f_domain')) ?></p>
                <p class="font-medium text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($primaryDomain ?: '-') ?></p>
            </div>
        </div>
        </div>
    </div>

    <!-- 탭 네비게이션 -->
    <?php if (count($tabTypes) > 1): ?>
    <div class="border-b border-zinc-200 dark:border-zinc-700 mb-6">
        <nav class="flex gap-1 -mb-px overflow-x-auto">
            <?php foreach ($tabTypes as $i => $type): ?>
            <button onclick="showServiceTab('<?= $type ?>')" id="tab_<?= $type ?>"
                    class="service-tab flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium border-b-2 whitespace-nowrap transition <?= $i === 0 ? 'border-blue-600 text-blue-600 dark:text-blue-400' : 'border-transparent text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-300' ?>">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><?= $typeIcons[$type] ?? $typeIcons['addon'] ?></svg>
                <?= $typeLabels[$type] ?? $type ?>
                <span class="text-[10px] px-1.5 py-0.5 bg-zinc-100 dark:bg-zinc-700 text-zinc-500 rounded-full"><?php
                    if ($type === 'mail') {
                        $mc = 0;
                        foreach ($servicesByType[$type] as $_ms) {
                            $_mm = json_decode($_ms['metadata'] ?? '{}', true) ?: [];
                            $mc += count($_mm['mail_accounts'] ?? []);
                        }
                        echo $mc;
                    } else {
                        echo count($servicesByType[$type]);
                    }
                ?></span>
            </button>
            <?php endforeach; ?>
        </nav>
    </div>
    <?php endif; ?>

    <!-- 탭 콘텐츠 -->
    <?php foreach ($servicesByType as $type => $subs): ?>
    <div id="panel_<?= $type ?>" class="service-panel <?= $type !== $firstTab ? 'hidden' : '' ?>">
        <?php
        $partialFile = __DIR__ . '/service-partials/' . $type . '.php';
        if (file_exists($partialFile)) {
            include $partialFile;
        } else {
            // 제네릭 폴백
            include __DIR__ . '/service-partials/_generic.php';
        }
        ?>
    </div>
    <?php endforeach; ?>


</div>
    </div>
</div>

<script>
var siteBaseUrl = '<?= $baseUrl ?>';

function showServiceTab(type) {
    document.querySelectorAll('.service-tab').forEach(function(t) {
        t.classList.remove('border-blue-600', 'text-blue-600', 'dark:text-blue-400');
        t.classList.add('border-transparent', 'text-zinc-400');
    });
    document.querySelectorAll('.service-panel').forEach(function(p) { p.classList.add('hidden'); });
    var tab = document.getElementById('tab_' + type);
    var panel = document.getElementById('panel_' + type);
    if (tab) { tab.classList.add('border-blue-600', 'text-blue-600', 'dark:text-blue-400'); tab.classList.remove('border-transparent', 'text-zinc-400'); }
    if (panel) panel.classList.remove('hidden');
}

function serviceAction(action, data) {
    data.action = action;
    return fetch(siteBaseUrl + '/plugins/vos-hosting/api/service-manage.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(data)
    }).then(function(r) { return r.json(); });
}

// 전체 자동연장 토글 (recurring 구독 일괄)
var renewableSubIds = <?= json_encode(array_values(array_map(fn($s) => (int)$s['id'], $renewableSubs))) ?>;
function toggleAllAutoRenew(checked) {
    Promise.all(renewableSubIds.map(function(id) {
        return serviceAction('toggle_auto_renew', { subscription_id: id, auto_renew: checked });
    })).then(function() {
        // 개별 토글도 동기화
        document.querySelectorAll('.sub-auto-renew').forEach(function(el) { el.checked = checked; });
    });
}

// 무료 서비스 전체 연장 신청
var freeSubIds = <?= json_encode(array_values(array_map(fn($s) => (int)$s['id'], array_filter($subscriptions, fn($s) => ($s['service_class'] ?? '') === 'free' && $s['status'] === 'active')))) ?>;
function requestAllRenewal() {
    Promise.all(freeSubIds.map(function(id) {
        return serviceAction('request_renewal', { subscription_id: id });
    })).then(function() { alert(<?= json_encode(__('services.detail.alert_renewal_done'), JSON_UNESCAPED_UNICODE) ?>); });
}
</script>
