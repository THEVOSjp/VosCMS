<?php
/**
 * 관리자 — 서비스 주문 목록
 */
if (!function_exists('__')) require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';

$pageTitle = '서비스 주문 관리 - ' . ($config['app_name'] ?? 'VosCMS') . ' Admin';
$pageHeaderTitle = '서비스 주문';
$pageSubTitle = '서비스 주문 관리';
$pageSubDesc = '서비스 신청 주문 목록 및 구독 상태를 관리합니다.';

$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$pdo = \RzxLib\Core\Database\Connection::getInstance()->getPdo();

require_once BASE_PATH . '/rzxlib/Core/Helpers/Encryption.php';
require_once BASE_PATH . '/rzxlib/Core/Helpers/functions.php';

// AJAX: 일괄 연장
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !empty($_SERVER['HTTP_X_REQUESTED_WITH'])) {
    header('Content-Type: application/json; charset=utf-8');
    $input = json_decode(file_get_contents('php://input'), true);
    $action = $input['action'] ?? '';

    if ($action === 'bulk_extend') {
        $orderIds = $input['order_ids'] ?? [];
        $months = (int)($input['months'] ?? 1);
        if (empty($orderIds) || $months < 1) {
            echo json_encode(['success' => false, 'message' => '주문을 선택하고 연장 기간을 입력하세요.']);
            exit;
        }
        $cnt = 0;
        foreach ($orderIds as $oid) {
            $oid = (int)$oid;
            $pdo->prepare("UPDATE {$prefix}orders SET expires_at = DATE_ADD(expires_at, INTERVAL ? MONTH) WHERE id = ?")->execute([$months, $oid]);
            $pdo->prepare("UPDATE {$prefix}subscriptions SET expires_at = DATE_ADD(expires_at, INTERVAL ? MONTH), next_billing_at = DATE_ADD(next_billing_at, INTERVAL ? MONTH) WHERE order_id = ? AND service_class != 'one_time'")->execute([$months, $months, $oid]);
            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'bulk_extended', ?, 'admin', ?)")
                ->execute([$oid, json_encode(['months' => $months]), $_SESSION['user_id'] ?? '']);
            $cnt++;
        }
        echo json_encode(['success' => true, 'message' => $cnt . '건 연장 완료 (' . $months . '개월)']);
        exit;
    }
    echo json_encode(['success' => false, 'message' => '알 수 없는 액션']);
    exit;
}

// 필터
$filterStatus = $_GET['status'] ?? '';
$filterExpiry = $_GET['expiry'] ?? '';
$searchField = $_GET['sf'] ?? 'all';
$search = $_GET['q'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = (int)($_GET['pp'] ?? 20);
if (!in_array($perPage, [10, 20, 50, 100])) $perPage = 20;

// 쿼리 빌드
$where = [];
$params = [];

// 상태 필터
if ($filterStatus) {
    $where[] = "o.status = ?";
    $params[] = $filterStatus;
}

// 만기 필터
if ($filterExpiry === '15days') {
    $where[] = "o.expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 15 DAY) AND o.status IN ('paid','active')";
} elseif ($filterExpiry === 'expired') {
    $where[] = "o.expires_at < NOW() AND o.status NOT IN ('cancelled','failed')";
} elseif ($filterExpiry === 'dormant') {
    // 휴면: 만료 후 30일 이상 경과 + 미갱신
    $where[] = "o.expires_at < DATE_SUB(NOW(), INTERVAL 30 DAY) AND o.status NOT IN ('cancelled','failed')";
}

// 검색
if ($search) {
    $s = "%{$search}%";
    if ($searchField === 'domain') {
        $where[] = "o.domain LIKE ?";
        $params[] = $s;
    } elseif ($searchField === 'name') {
        $where[] = "(o.applicant_name LIKE ? OR u.name LIKE ?)";
        $params = array_merge($params, [$s, $s]);
    } elseif ($searchField === 'email') {
        $where[] = "(o.applicant_email LIKE ? OR u.email LIKE ?)";
        $params = array_merge($params, [$s, $s]);
    } elseif ($searchField === 'order') {
        $where[] = "o.order_number LIKE ?";
        $params[] = $s;
    } else {
        $where[] = "(o.order_number LIKE ? OR o.domain LIKE ? OR o.applicant_name LIKE ? OR o.applicant_email LIKE ? OR u.name LIKE ?)";
        $params = array_merge($params, [$s, $s, $s, $s, $s]);
    }
}
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// 카운트
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}orders o LEFT JOIN {$prefix}users u ON o.user_id = u.id {$whereSQL}");
$countStmt->execute($params);
$totalCount = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalCount / $perPage));
$offset = ($page - 1) * $perPage;

// 목록 — 서비스명(호스팅 플랜), 업체명, 만료일 포함
$listStmt = $pdo->prepare("SELECT o.*, u.name as user_name, u.email as user_email,
    (SELECT COUNT(*) FROM {$prefix}subscriptions s WHERE s.order_id = o.id) as sub_count,
    (SELECT s2.label FROM {$prefix}subscriptions s2 WHERE s2.order_id = o.id AND s2.type = 'hosting' LIMIT 1) as hosting_label
    FROM {$prefix}orders o
    LEFT JOIN {$prefix}users u ON o.user_id = u.id
    {$whereSQL}
    ORDER BY o.created_at DESC LIMIT {$perPage} OFFSET {$offset}");
$listStmt->execute($params);
$orders = $listStmt->fetchAll(PDO::FETCH_ASSOC);

// 통계
$statsStmt = $pdo->query("SELECT status, COUNT(*) as cnt FROM {$prefix}orders GROUP BY status");
$stats = [];
while ($r = $statsStmt->fetch(PDO::FETCH_ASSOC)) $stats[$r['status']] = (int)$r['cnt'];

$totalOrders = array_sum($stats);
$pendingOneTime = (int)$pdo->query("SELECT COUNT(*) FROM {$prefix}subscriptions WHERE service_class='one_time' AND status='pending'")->fetchColumn();
$expiring15 = (int)$pdo->query("SELECT COUNT(*) FROM {$prefix}orders WHERE expires_at BETWEEN NOW() AND DATE_ADD(NOW(), INTERVAL 15 DAY) AND status IN ('paid','active')")->fetchColumn();
$dormantCount = (int)$pdo->query("SELECT COUNT(*) FROM {$prefix}orders WHERE expires_at < DATE_SUB(NOW(), INTERVAL 30 DAY) AND status NOT IN ('cancelled','failed')")->fetchColumn();

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
    'suspended' => ['정지', 'bg-amber-100 text-amber-700'],
    'failed' => ['실패', 'bg-red-100 text-red-600'],
];

// 쿼리 파라미터 유지 헬퍼
$qp = function($overrides = []) use ($filterStatus, $filterExpiry, $searchField, $search, $perPage, $adminUrl) {
    $p = array_filter(array_merge([
        'status' => $filterStatus, 'expiry' => $filterExpiry,
        'sf' => $searchField !== 'all' ? $searchField : '', 'q' => $search, 'pp' => $perPage !== 20 ? $perPage : '',
    ], $overrides));
    $qs = http_build_query($p);
    return $adminUrl . '/service-orders' . ($qs ? '?' . $qs : '');
};

include BASE_PATH . '/resources/views/admin/reservations/_head.php';
?>

<!-- 서비스 신청 상태 필터 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 p-4 mb-6">
    <p class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3">서비스 신청 상태</p>
    <div class="flex flex-wrap gap-2">
        <a href="<?= $qp(['status'=>'','expiry'=>'','page'=>'']) ?>" class="px-3 py-1.5 text-xs rounded-lg border transition <?= !$filterStatus && !$filterExpiry ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 border-gray-200 dark:border-zinc-600 hover:border-blue-400' ?>">
            전체 <span class="ml-1 font-bold"><?= $totalOrders ?></span>
        </a>
        <?php foreach (['paid'=>'결제완료','pending'=>'대기','active'=>'활성','expired'=>'만료','cancelled'=>'취소','suspended'=>'정지','failed'=>'실패'] as $sk=>$sl): ?>
        <?php if (($stats[$sk] ?? 0) > 0 || $filterStatus === $sk): ?>
        <a href="<?= $qp(['status'=>$sk,'expiry'=>'','page'=>'']) ?>" class="px-3 py-1.5 text-xs rounded-lg border transition <?= $filterStatus===$sk ? 'bg-blue-600 text-white border-blue-600' : 'bg-white dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 border-gray-200 dark:border-zinc-600 hover:border-blue-400' ?>">
            <?= $sl ?> <span class="ml-1 font-bold"><?= $stats[$sk] ?? 0 ?></span>
        </a>
        <?php endif; endforeach; ?>
        <span class="border-l border-zinc-200 dark:border-zinc-600 mx-1"></span>
        <a href="<?= $qp(['status'=>'','expiry'=>'15days','page'=>'']) ?>" class="px-3 py-1.5 text-xs rounded-lg border transition <?= $filterExpiry==='15days' ? 'bg-amber-500 text-white border-amber-500' : 'bg-white dark:bg-zinc-700 text-amber-600 border-gray-200 dark:border-zinc-600 hover:border-amber-400' ?>">
            15일내 만료 <span class="ml-1 font-bold"><?= $expiring15 ?></span>
        </a>
        <a href="<?= $qp(['status'=>'','expiry'=>'dormant','page'=>'']) ?>" class="px-3 py-1.5 text-xs rounded-lg border transition <?= $filterExpiry==='dormant' ? 'bg-zinc-600 text-white border-zinc-600' : 'bg-white dark:bg-zinc-700 text-zinc-500 border-gray-200 dark:border-zinc-600 hover:border-zinc-400' ?>">
            휴면 <span class="ml-1 font-bold"><?= $dormantCount ?></span>
        </a>
        <?php if ($pendingOneTime > 0): ?>
        <span class="px-3 py-1.5 text-xs rounded-lg bg-amber-50 text-amber-600 border border-amber-200">
            1회성 접수 <span class="ml-1 font-bold"><?= $pendingOneTime ?></span>
        </span>
        <?php endif; ?>
    </div>
</div>

<!-- 검색 + 페이지당 건수 -->
<div class="flex flex-wrap items-center gap-2 mb-4">
    <form class="flex gap-2 flex-1" method="GET" action="<?= $adminUrl ?>/service-orders">
        <?php if ($filterStatus): ?><input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>"><?php endif; ?>
        <?php if ($filterExpiry): ?><input type="hidden" name="expiry" value="<?= htmlspecialchars($filterExpiry) ?>"><?php endif; ?>
        <select name="sf" class="px-3 py-2 text-xs border border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white rounded-lg">
            <option value="all" <?= $searchField==='all'?'selected':'' ?>>전체</option>
            <option value="order" <?= $searchField==='order'?'selected':'' ?>>주문번호</option>
            <option value="domain" <?= $searchField==='domain'?'selected':'' ?>>도메인</option>
            <option value="name" <?= $searchField==='name'?'selected':'' ?>>이름</option>
            <option value="email" <?= $searchField==='email'?'selected':'' ?>>이메일</option>
        </select>
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="검색..."
               class="flex-1 min-w-[200px] px-4 py-2 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white rounded-lg">
        <button class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">검색</button>
    </form>
    <div class="flex items-center gap-2">
        <span class="text-xs text-zinc-400">검색결과: <?= $totalCount ?>개</span>
        <select onchange="location.href='<?= $qp(['pp'=>'']) ?>' + (this.value !== '20' ? '&pp=' + this.value : '')"
                class="px-2 py-1.5 text-xs border border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white rounded-lg">
            <?php foreach ([10,20,50,100] as $pp): ?>
            <option value="<?= $pp ?>" <?= $perPage===$pp?'selected':'' ?>><?= $pp ?>개씩</option>
            <?php endforeach; ?>
        </select>
    </div>
</div>

<!-- 일괄 작업 바 -->
<div id="bulkBar" class="hidden mb-3 p-3 bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl flex items-center justify-between">
    <span class="text-sm text-blue-700 dark:text-blue-300"><strong id="bulkCount">0</strong>건 선택됨</span>
    <div class="flex items-center gap-2">
        <select id="bulkMonths" class="px-2 py-1 text-xs border border-blue-300 dark:border-blue-600 rounded-lg">
            <option value="1">1개월</option>
            <option value="3">3개월</option>
            <option value="6">6개월</option>
            <option value="12" selected>12개월</option>
            <option value="24">24개월</option>
        </select>
        <button onclick="bulkExtend()" class="px-3 py-1.5 text-xs font-medium text-white bg-blue-600 rounded-lg hover:bg-blue-700">일괄 연장</button>
    </div>
</div>

<!-- 주문 목록 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-[11px] text-zinc-400 border-b border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800/50">
                <th class="py-3 px-3 text-center w-8"><input type="checkbox" id="checkAll" onchange="toggleCheckAll(this.checked)" class="rounded text-blue-600"></th>
                <th class="py-3 px-3 text-left">신청자 / 업체</th>
                <th class="py-3 px-3 text-left">서비스명</th>
                <th class="py-3 px-3 text-left">대표도메인</th>
                <th class="py-3 px-3 text-center">시작일</th>
                <th class="py-3 px-3 text-center">만료일</th>
                <th class="py-3 px-3 text-center">남은일수</th>
                <th class="py-3 px-3 text-center">상태</th>
                <th class="py-3 px-3 text-right">금액</th>
                <th class="py-3 px-3 text-center w-14"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-zinc-700/50">
            <?php if (empty($orders)): ?>
            <tr><td colspan="10" class="py-12 text-center text-zinc-400">주문이 없습니다.</td></tr>
            <?php endif; ?>
            <?php foreach ($orders as $o):
                $ost = $statusLabels[$o['status']] ?? ['알 수 없음', 'bg-gray-100 text-gray-500'];
                $dispName = decrypt($o['applicant_name'] ?: '') ?: decrypt($o['user_name'] ?: '') ?: '-';
                $company = $o['applicant_company'] ?? '';
                $startDate = $o['started_at'] ? date('Y-m-d', strtotime($o['started_at'])) : '-';
                $expiresDate = $o['expires_at'] ? date('Y-m-d', strtotime($o['expires_at'])) : '-';
                $daysLeft = $o['expires_at'] ? (int)ceil((strtotime($o['expires_at']) - time()) / 86400) : null;
                $daysClass = $daysLeft === null ? 'text-zinc-300' : ($daysLeft <= 0 ? 'text-red-500 font-bold' : ($daysLeft <= 15 ? 'text-amber-500 font-bold' : ($daysLeft <= 30 ? 'text-amber-400' : 'text-zinc-500')));
                $hostingLabel = $o['hosting_label'] ?? ($o['hosting_capacity'] ? '웹 호스팅 ' . $o['hosting_capacity'] : '-');
            ?>
            <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700/30 transition">
                <td class="py-3 px-3 text-center"><input type="checkbox" class="order-check rounded text-blue-600" value="<?= $o['id'] ?>" onchange="updateBulkBar()"></td>
                <td class="py-3 px-3">
                    <a href="<?= $adminUrl ?>/service-orders/<?= htmlspecialchars($o['order_number']) ?>" class="font-medium text-zinc-900 dark:text-white hover:text-blue-600"><?= htmlspecialchars($dispName) ?></a>
                    <?php if ($company): ?>
                    <p class="text-[10px] text-zinc-400"><?= htmlspecialchars($company) ?></p>
                    <?php endif; ?>
                </td>
                <td class="py-3 px-3">
                    <p class="text-xs text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($hostingLabel) ?></p>
                    <p class="text-[10px] text-zinc-400 font-mono"><?= htmlspecialchars($o['order_number']) ?></p>
                </td>
                <td class="py-3 px-3 font-mono text-xs text-zinc-600 dark:text-zinc-300"><?= htmlspecialchars($o['domain'] ?: '-') ?></td>
                <td class="py-3 px-3 text-center text-xs text-zinc-500"><?= $startDate ?></td>
                <td class="py-3 px-3 text-center text-xs text-zinc-500"><?= $expiresDate ?></td>
                <td class="py-3 px-3 text-center text-xs <?= $daysClass ?>"><?= $daysLeft !== null ? ($daysLeft > 0 ? $daysLeft : ($daysLeft === 0 ? 'D-Day' : abs($daysLeft) . '일 초과')) : '-' ?></td>
                <td class="py-3 px-3 text-center"><span class="text-[10px] px-2 py-0.5 rounded-full font-medium <?= $ost[1] ?>"><?= $ost[0] ?></span></td>
                <td class="py-3 px-3 text-right text-xs font-medium"><?= (int)$o['total'] > 0 ? $fmtPrice($o['total'], $o['currency']) : '<span class="text-green-500">무료</span>' ?></td>
                <td class="py-3 px-3 text-center">
                    <a href="<?= $adminUrl ?>/service-orders/<?= htmlspecialchars($o['order_number']) ?>" class="text-xs text-blue-600 hover:underline">관리</a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</div>

<!-- 페이지네이션 -->
<?php if ($totalPages > 1): ?>
<div class="flex justify-center mt-6 gap-1">
    <?php
    $range = 5;
    $start = max(1, $page - $range);
    $end = min($totalPages, $page + $range);
    if ($page > 1): ?>
    <a href="<?= $qp(['page'=>1]) ?>" class="px-2.5 py-1.5 text-xs rounded-lg bg-white dark:bg-zinc-700 text-zinc-500 border border-gray-200 dark:border-zinc-600">&laquo;</a>
    <a href="<?= $qp(['page'=>$page-1]) ?>" class="px-2.5 py-1.5 text-xs rounded-lg bg-white dark:bg-zinc-700 text-zinc-500 border border-gray-200 dark:border-zinc-600">&lsaquo;</a>
    <?php endif; ?>
    <?php for ($i = $start; $i <= $end; $i++): ?>
    <a href="<?= $qp(['page'=>$i]) ?>" class="px-3 py-1.5 text-xs rounded-lg <?= $i === $page ? 'bg-blue-600 text-white' : 'bg-white dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 border border-gray-200 dark:border-zinc-600 hover:bg-gray-50' ?>"><?= $i ?></a>
    <?php endfor; ?>
    <?php if ($page < $totalPages): ?>
    <a href="<?= $qp(['page'=>$page+1]) ?>" class="px-2.5 py-1.5 text-xs rounded-lg bg-white dark:bg-zinc-700 text-zinc-500 border border-gray-200 dark:border-zinc-600">&rsaquo;</a>
    <a href="<?= $qp(['page'=>$totalPages]) ?>" class="px-2.5 py-1.5 text-xs rounded-lg bg-white dark:bg-zinc-700 text-zinc-500 border border-gray-200 dark:border-zinc-600">&raquo;</a>
    <?php endif; ?>
</div>
<?php endif; ?>

<script>
function toggleCheckAll(checked) {
    document.querySelectorAll('.order-check').forEach(function(cb) { cb.checked = checked; });
    updateBulkBar();
}
function updateBulkBar() {
    var checked = document.querySelectorAll('.order-check:checked');
    var bar = document.getElementById('bulkBar');
    document.getElementById('bulkCount').textContent = checked.length;
    bar.classList.toggle('hidden', checked.length === 0);
}
function bulkExtend() {
    var ids = [];
    document.querySelectorAll('.order-check:checked').forEach(function(cb) { ids.push(parseInt(cb.value)); });
    if (!ids.length) return;
    var months = document.getElementById('bulkMonths').value;
    if (!confirm(ids.length + '건을 ' + months + '개월 연장하시겠습니까?')) return;
    fetch(window.location.href, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
        body: JSON.stringify({ action: 'bulk_extend', order_ids: ids, months: parseInt(months) })
    }).then(function(r) { return r.json(); }).then(function(d) {
        alert(d.message || '완료');
        if (d.success) location.reload();
    });
}
</script>

<?php include BASE_PATH . '/resources/views/admin/reservations/_foot.php'; ?>
