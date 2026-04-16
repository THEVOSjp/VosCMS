<?php
/**
 * 관리자 — 서비스 주문 상세 관리
 * 1회성 상태 변경, 구독 상태 관리
 */
if (!function_exists('__')) require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';

$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$pdo = \RzxLib\Core\Database\Connection::getInstance()->getPdo();

// AJAX 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json; charset=utf-8');
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';
    $subId = (int)($input['subscription_id'] ?? 0);

    switch ($action) {
        case 'update_onetime_status':
            $newStatus = $input['status'] ?? '';
            $allowed = ['pending', 'active', 'suspended', 'cancelled'];
            if (!in_array($newStatus, $allowed) && $newStatus !== 'completed') {
                echo json_encode(['success' => false, 'message' => '유효하지 않은 상태입니다.']);
                exit;
            }
            $sub = $pdo->prepare("SELECT * FROM {$prefix}subscriptions WHERE id = ?");
            $sub->execute([$subId]);
            $sub = $sub->fetch(PDO::FETCH_ASSOC);
            if (!$sub || ($sub['service_class'] ?? '') !== 'one_time') {
                echo json_encode(['success' => false, 'message' => '1회성 서비스를 찾을 수 없습니다.']);
                exit;
            }
            if ($newStatus === 'completed') {
                $pdo->prepare("UPDATE {$prefix}subscriptions SET status='active', completed_at=NOW() WHERE id=?")->execute([$subId]);
            } else {
                $pdo->prepare("UPDATE {$prefix}subscriptions SET status=?, completed_at=NULL WHERE id=?")->execute([$newStatus, $subId]);
            }
            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'status_change', ?, 'admin', ?)")
                ->execute([$sub['order_id'], json_encode(['subscription_id' => $subId, 'label' => $sub['label'], 'new_status' => $newStatus]), $_SESSION['user_id'] ?? '']);
            echo json_encode(['success' => true]);
            exit;

        case 'update_order_status':
            $newStatus = $input['status'] ?? '';
            $orderId = (int)($input['order_id'] ?? 0);
            $allowed = ['pending', 'paid', 'active', 'expired', 'cancelled', 'failed'];
            if (!in_array($newStatus, $allowed)) {
                echo json_encode(['success' => false, 'message' => '유효하지 않은 상태입니다.']);
                exit;
            }
            $pdo->prepare("UPDATE {$prefix}orders SET status=? WHERE id=?")->execute([$newStatus, $orderId]);
            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'status_change', ?, 'admin', ?)")
                ->execute([$orderId, json_encode(['new_status' => $newStatus]), $_SESSION['user_id'] ?? '']);
            echo json_encode(['success' => true]);
            exit;
        case 'update_server_info':
            $serverData = $input['server'] ?? [];
            $stmt = $pdo->prepare("SELECT * FROM {$prefix}subscriptions WHERE id = ?");
            $stmt->execute([$subId]);
            $sub = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$sub) { echo json_encode(['success' => false, 'message' => '구독을 찾을 수 없습니다.']); exit; }
            $meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
            $meta['server'] = $serverData;
            $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
                ->execute([json_encode($meta, JSON_UNESCAPED_UNICODE), $subId]);
            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'server_info_updated', ?, 'admin', ?)")
                ->execute([$sub['order_id'], json_encode(['subscription_id' => $subId]), $_SESSION['user_id'] ?? '']);
            echo json_encode(['success' => true]);
            exit;

        case 'update_addon_memo':
            $memo = $input['memo'] ?? '';
            $stmt = $pdo->prepare("SELECT * FROM {$prefix}subscriptions WHERE id = ?");
            $stmt->execute([$subId]);
            $sub = $stmt->fetch(PDO::FETCH_ASSOC);
            if (!$sub) { echo json_encode(['success' => false, 'message' => '구독을 찾을 수 없습니다.']); exit; }
            $meta = json_decode($sub['metadata'] ?? '{}', true) ?: [];
            $meta['admin_memo'] = $memo;
            $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
                ->execute([json_encode($meta, JSON_UNESCAPED_UNICODE), $subId]);
            echo json_encode(['success' => true]);
            exit;

        case 'send_setup_email':
            $oid = (int)($input['order_id'] ?? 0);
            $orderStmt = $pdo->prepare("SELECT * FROM {$prefix}orders WHERE id = ?");
            $orderStmt->execute([$oid]);
            $ord = $orderStmt->fetch(PDO::FETCH_ASSOC);
            if (!$ord) { echo json_encode(['success' => false, 'message' => '주문을 찾을 수 없습니다.']); exit; }
            // TODO: 실제 이메일 발송 구현 (메일 템플릿 + SMTP)
            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'setup_email_sent', ?, 'admin', ?)")
                ->execute([$oid, json_encode(['email' => $ord['applicant_email']]), $_SESSION['user_id'] ?? '']);
            echo json_encode(['success' => true, 'message' => '셋팅 이메일 발송이 요청되었습니다. (이메일 발송 기능은 준비중)']);
            exit;
    }
    echo json_encode(['success' => false, 'message' => '알 수 없는 액션']);
    exit;
}

require_once BASE_PATH . '/rzxlib/Core/Helpers/Encryption.php';
require_once BASE_PATH . '/rzxlib/Core/Helpers/functions.php';

// 주문 로드
$orderStmt = $pdo->prepare("SELECT o.*, u.name as user_name, u.email as user_email FROM {$prefix}orders o LEFT JOIN {$prefix}users u ON o.user_id = u.id WHERE o.order_number = ?");
$orderStmt->execute([$adminOrderNumber ?? '']);
$order = $orderStmt->fetch(PDO::FETCH_ASSOC);

if (!$order) {
    $pageTitle = '주문 없음'; $pageHeaderTitle = '서비스 주문';
    include BASE_PATH . '/resources/views/admin/reservations/_head.php';
    echo '<div class="text-center py-16"><p class="text-zinc-400">주문을 찾을 수 없습니다.</p><a href="'.$adminUrl.'/service-orders" class="text-blue-600 hover:underline text-sm mt-4 inline-block">목록으로</a></div>';
    include BASE_PATH . '/resources/views/admin/reservations/_foot.php';
    return;
}

// 구독 목록
$subsStmt = $pdo->prepare("SELECT * FROM {$prefix}subscriptions WHERE order_id = ? ORDER BY FIELD(type,'hosting','domain','mail','maintenance','addon'), id");
$subsStmt->execute([$order['id']]);
$subscriptions = $subsStmt->fetchAll(PDO::FETCH_ASSOC);

// 주문 로그
$logsStmt = $pdo->prepare("SELECT * FROM {$prefix}order_logs WHERE order_id = ? ORDER BY created_at DESC LIMIT 30");
$logsStmt->execute([$order['id']]);
$orderLogs = $logsStmt->fetchAll(PDO::FETCH_ASSOC);

// 타입별 그룹
$servicesByType = [];
foreach ($subscriptions as $sub) {
    $servicesByType[$sub['type']][] = $sub;
}
$tabTypes = array_keys($servicesByType);
$firstTab = $tabTypes[0] ?? '';

$pageTitle = $order['order_number'] . ' - 서비스 주문 관리';
$pageHeaderTitle = '서비스 주문';
$pageSubTitle = '';

$_dispSymbols = ['KRW'=>'₩','USD'=>'$','JPY'=>'¥','CNY'=>'¥','EUR'=>'€'];
$fmtPrice = function($amount, $currency = 'JPY') use ($_dispSymbols) {
    $sym = $_dispSymbols[$currency] ?? $currency;
    $pre = in_array($currency, ['USD','JPY','CNY','EUR']);
    return $pre ? $sym . number_format((int)$amount) : number_format((int)$amount) . $sym;
};

$statusLabels = [
    'pending' => ['대기', 'bg-blue-100 text-blue-700'],
    'paid' => ['결제완료', 'bg-green-100 text-green-700'],
    'active' => ['활성', 'bg-green-100 text-green-700'],
    'expired' => ['만료', 'bg-gray-100 text-gray-500'],
    'cancelled' => ['취소', 'bg-red-100 text-red-600'],
    'suspended' => ['보류', 'bg-amber-100 text-amber-700'],
    'failed' => ['실패', 'bg-red-100 text-red-600'],
];
$typeLabels = ['hosting'=>'웹 호스팅','domain'=>'도메인','mail'=>'메일','maintenance'=>'유지보수','addon'=>'부가서비스'];
$typeIcons = [
    'hosting' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 12h14M5 12a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v4a2 2 0 01-2 2M5 12a2 2 0 00-2 2v4a2 2 0 002 2h14a2 2 0 002-2v-4a2 2 0 00-2-2m-2-4h.01M17 16h.01"/>',
    'domain' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>',
    'maintenance' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/>',
    'mail' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/>',
    'addon' => '<path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 6v6m0 0v6m0-6h6m-6 0H6"/>',
];
$ost = $statusLabels[$order['status']] ?? ['알 수 없음', 'bg-gray-100 text-gray-500'];

$oneTimeStatusOptions = [
    'pending' => ['접수', 'blue'],
    'active' => ['진행', 'amber'],
    'suspended' => ['보류', 'zinc'],
    'cancelled' => ['취소', 'red'],
    'completed' => ['완료', 'green'],
];

include BASE_PATH . '/resources/views/admin/reservations/_head.php';
?>

<!-- 헤더 -->
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="<?= $adminUrl ?>/service-orders" class="text-zinc-400 hover:text-zinc-600">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h1 class="text-lg font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($order['order_number']) ?></h1>
            <p class="text-xs text-zinc-400"><?= htmlspecialchars($order['domain'] ?: '-') ?> · <?= htmlspecialchars(decrypt($order['applicant_name'] ?: '') ?: decrypt($order['user_name'] ?: '') ?: '-') ?></p>
        </div>
    </div>
    <div class="flex items-center gap-2">
        <span class="text-[10px] px-2 py-0.5 rounded-full font-medium <?= $ost[1] ?>"><?= $ost[0] ?></span>
        <select id="orderStatusSelect" onchange="updateOrderStatus(this.value)"
                class="text-xs border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg px-2 py-1">
            <?php foreach (['pending','paid','active','expired','cancelled','failed'] as $sv): ?>
            <option value="<?= $sv ?>" <?= $order['status'] === $sv ? 'selected' : '' ?>><?= $statusLabels[$sv][0] ?? $sv ?></option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-5 gap-6">
    <!-- 좌측: 주문 정보 + 구독 (4/5) -->
    <div class="lg:col-span-4 space-y-6">

        <!-- 주문 정보 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
            <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700">
                <h2 class="text-sm font-bold text-zinc-900 dark:text-white">주문 정보 : <?= htmlspecialchars($order['domain'] ?: '-') ?></h2>
            </div>
            <div class="p-5">
                <div class="grid grid-cols-2 sm:grid-cols-4 gap-4 text-sm">
                    <div>
                        <p class="text-[10px] text-zinc-400 uppercase mb-0.5">계약 기간</p>
                        <p class="font-medium text-zinc-900 dark:text-white"><?= $order['contract_months'] ?>개월</p>
                        <p class="text-xs text-zinc-400"><?= $order['started_at'] ? date('Y-m-d', strtotime($order['started_at'])) : '-' ?> ~ <?= $order['expires_at'] ? date('Y-m-d', strtotime($order['expires_at'])) : '-' ?></p>
                    </div>
                    <div>
                        <p class="text-[10px] text-zinc-400 uppercase mb-0.5">결제</p>
                        <p class="font-medium text-zinc-900 dark:text-white"><?= (int)$order['total'] > 0 ? $fmtPrice($order['total'], $order['currency']) : '무료' ?></p>
                        <p class="text-xs text-zinc-400"><?= $order['payment_method'] === 'free' ? '무료' : ($order['payment_method'] === 'bank' ? '계좌이체' : '카드') ?></p>
                    </div>
                    <div>
                        <p class="text-[10px] text-zinc-400 uppercase mb-0.5">신청자</p>
                        <p class="font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars(decrypt($order['applicant_name'] ?: '') ?: '-') ?></p>
                        <p class="text-xs text-zinc-400"><?= htmlspecialchars($order['applicant_email'] ?: '') ?></p>
                    </div>
                    <div>
                        <p class="text-[10px] text-zinc-400 uppercase mb-0.5">연락처</p>
                        <p class="font-medium text-zinc-900 dark:text-white"><?php
                            $phoneDisplayConfig = ['value' => $order['applicant_phone'] ?? '', 'id' => 'adminPhone'];
                            include BASE_PATH . '/resources/views/components/phone-display.php';
                        ?></p>
                        <p class="text-xs text-zinc-400"><?= htmlspecialchars($order['applicant_company'] ?: '') ?></p>
                    </div>
                </div>
            </div>
        </div>

        <!-- 서비스 탭 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
            <?php if (count($tabTypes) > 1): ?>
            <div class="border-b border-gray-200 dark:border-zinc-700">
                <nav class="flex gap-1 px-4 -mb-px overflow-x-auto">
                    <?php foreach ($tabTypes as $i => $type): ?>
                    <button onclick="showTab('<?= $type ?>')" id="atab_<?= $type ?>"
                            class="admin-tab flex items-center gap-1.5 px-4 py-2.5 text-sm font-medium border-b-2 whitespace-nowrap transition <?= $i === 0 ? 'border-blue-600 text-blue-600' : 'border-transparent text-zinc-400 hover:text-zinc-600' ?>">
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

            <?php foreach ($servicesByType as $type => $typeSubs): ?>
            <div id="apanel_<?= $type ?>" class="admin-panel <?= $type !== $firstTab ? 'hidden' : '' ?>">
                <?php
                $subs = $typeSubs;
                $partialFile = __DIR__ . '/partials/' . $type . '.php';
                if (file_exists($partialFile)) {
                    include $partialFile;
                } else {
                    include __DIR__ . '/partials/_generic.php';
                }
                ?>
            </div>
            <?php endforeach; ?>
        </div>
    </div>

    <!-- 우측: 활동 로그 -->
    <div>
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
            <div class="px-5 py-4 border-b border-gray-100 dark:border-zinc-700">
                <h2 class="text-sm font-bold text-zinc-900 dark:text-white">활동 로그</h2>
            </div>
            <div class="p-4 space-y-3 max-h-[600px] overflow-y-auto">
                <?php if (empty($orderLogs)): ?>
                <p class="text-xs text-zinc-400 text-center py-4">로그가 없습니다.</p>
                <?php endif; ?>
                <?php foreach ($orderLogs as $log):
                    $actionLabels = [
                        'created' => ['주문 생성', 'blue'],
                        'paid' => ['결제 완료', 'green'],
                        'bank_pending' => ['계좌이체 대기', 'amber'],
                        'failed' => ['결제 실패', 'red'],
                        'status_change' => ['상태 변경', 'purple'],
                        'renewal_request' => ['연장 신청', 'blue'],
                        'service_completed' => ['서비스 완료', 'green'],
                        'mail_password_changed' => ['메일 비밀번호 변경', 'zinc'],
                    ];
                    $al = $actionLabels[$log['action']] ?? [$log['action'], 'zinc'];
                    $detail = json_decode($log['detail'] ?? '{}', true) ?: [];
                ?>
                <div class="flex gap-3">
                    <div class="w-2 h-2 rounded-full bg-<?= $al[1] ?>-400 mt-1.5 shrink-0"></div>
                    <div class="flex-1 min-w-0">
                        <p class="text-xs font-medium text-zinc-700 dark:text-zinc-300"><?= $al[0] ?></p>
                        <?php if (!empty($detail['label'])): ?>
                        <p class="text-[10px] text-zinc-400"><?= htmlspecialchars($detail['label'] ?? '') ?></p>
                        <?php endif; ?>
                        <?php if (!empty($detail['new_status'])): ?>
                        <p class="text-[10px] text-zinc-400">→ <?= htmlspecialchars($detail['new_status']) ?></p>
                        <?php endif; ?>
                        <p class="text-[10px] text-zinc-300 dark:text-zinc-600"><?= date('m-d H:i', strtotime($log['created_at'])) ?> · <?= $log['actor_type'] ?></p>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>

<script>
var adminUrl = '<?= $adminUrl ?>';
var orderId = <?= (int)$order['id'] ?>;

function ajaxPost(data) {
    return fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify(data)
    }).then(function(r) { return r.json(); });
}

function showTab(type) {
    document.querySelectorAll('.admin-tab').forEach(function(t) {
        t.classList.remove('border-blue-600', 'text-blue-600');
        t.classList.add('border-transparent', 'text-zinc-400');
    });
    document.querySelectorAll('.admin-panel').forEach(function(p) { p.classList.add('hidden'); });
    var tab = document.getElementById('atab_' + type);
    var panel = document.getElementById('apanel_' + type);
    if (tab) { tab.classList.add('border-blue-600', 'text-blue-600'); tab.classList.remove('border-transparent', 'text-zinc-400'); }
    if (panel) panel.classList.remove('hidden');
}

// 1회성 상태 변경 모달
var _otSubId = 0;
var _otStatuses = [
    { value: 'pending', label: '접수', color: 'blue', desc: '신청이 접수되었습니다.' },
    { value: 'active', label: '진행', color: 'amber', desc: '작업을 진행하고 있습니다.' },
    { value: 'suspended', label: '보류', color: 'zinc', desc: '작업이 보류되었습니다.' },
    { value: 'cancelled', label: '취소', color: 'red', desc: '작업이 취소되었습니다.' },
    { value: 'completed', label: '완료', color: 'green', desc: '작업이 완료되었습니다.' }
];

function openStatusModal(subId, current, label) {
    _otSubId = subId;
    var html = '<div class="text-center mb-5"><p class="text-sm font-bold text-zinc-900 dark:text-white">' + label + '</p><p class="text-xs text-zinc-400 mt-1">상태를 선택하세요</p></div>'
        + '<div class="grid grid-cols-5 gap-2">';
    _otStatuses.forEach(function(s) {
        var isActive = s.value === current;
        html += '<button onclick="confirmStatusChange(\'' + s.value + '\')" class="flex flex-col items-center gap-1.5 p-3 rounded-xl border-2 transition '
            + (isActive ? 'border-' + s.color + '-500 bg-' + s.color + '-50 dark:bg-' + s.color + '-900/20' : 'border-gray-200 dark:border-zinc-600 hover:border-' + s.color + '-300')
            + '">'
            + '<span class="w-8 h-8 rounded-full bg-' + s.color + '-100 dark:bg-' + s.color + '-900/30 flex items-center justify-center">'
            + '<span class="w-3 h-3 rounded-full bg-' + s.color + '-500"></span></span>'
            + '<span class="text-xs font-medium ' + (isActive ? 'text-' + s.color + '-600' : 'text-zinc-600 dark:text-zinc-300') + '">' + s.label + '</span>'
            + '</button>';
    });
    html += '</div>';
    document.getElementById('statusModalBody').innerHTML = html;
    document.getElementById('statusModal').classList.remove('hidden');
}

function confirmStatusChange(status) {
    ajaxPost({ action: 'update_onetime_status', subscription_id: _otSubId, status: status })
        .then(function(d) {
            if (d.success) location.reload();
            else alert(d.message || '변경 실패');
        });
}

function closeStatusModal() {
    document.getElementById('statusModal').classList.add('hidden');
}

function updateOrderStatus(status) {
    ajaxPost({ action: 'update_order_status', order_id: orderId, status: status })
        .then(function(d) {
            if (d.success) location.reload();
            else alert(d.message || '변경 실패');
        });
}
</script>

<!-- 상태 변경 모달 -->
<div id="statusModal" class="hidden fixed inset-0 z-50 flex items-center justify-center">
    <div class="absolute inset-0 bg-black/50" onclick="closeStatusModal()"></div>
    <div class="relative bg-white dark:bg-zinc-800 rounded-2xl shadow-2xl p-6 w-full max-w-md mx-4">
        <button onclick="closeStatusModal()" class="absolute top-3 right-3 text-zinc-400 hover:text-zinc-600 p-1">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
        </button>
        <div id="statusModalBody"></div>
    </div>
</div>

<?php include BASE_PATH . '/resources/views/admin/reservations/_foot.php'; ?>
