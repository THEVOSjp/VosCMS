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

// 필터
$filterStatus = $_GET['status'] ?? '';
$search = $_GET['q'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 20;

// 쿼리 빌드
$where = [];
$params = [];
if ($filterStatus) {
    $where[] = "o.status = ?";
    $params[] = $filterStatus;
}
if ($search) {
    $where[] = "(o.order_number LIKE ? OR o.domain LIKE ? OR o.applicant_name LIKE ? OR o.applicant_email LIKE ?)";
    $s = "%{$search}%";
    $params = array_merge($params, [$s, $s, $s, $s]);
}
$whereSQL = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// 카운트
$countStmt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}orders o {$whereSQL}");
$countStmt->execute($params);
$totalCount = (int)$countStmt->fetchColumn();
$totalPages = max(1, ceil($totalCount / $perPage));
$offset = ($page - 1) * $perPage;

// 목록
$listStmt = $pdo->prepare("SELECT o.*, u.name as user_name, u.email as user_email,
    (SELECT COUNT(*) FROM {$prefix}subscriptions s WHERE s.order_id = o.id) as sub_count
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

// 1회성 접수 대기 건수
$pendingOneTime = (int)$pdo->query("SELECT COUNT(*) FROM {$prefix}subscriptions WHERE service_class='one_time' AND status='pending'")->fetchColumn();

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
    'failed' => ['실패', 'bg-red-100 text-red-600'],
];

include BASE_PATH . '/resources/views/admin/reservations/_head.php';
?>

<!-- 통계 카드 -->
<div class="grid grid-cols-2 sm:grid-cols-4 lg:grid-cols-6 gap-3 mb-6">
    <a href="<?= $adminUrl ?>/service-orders" class="p-3 bg-white dark:bg-zinc-800 rounded-xl border <?= !$filterStatus ? 'border-blue-500 ring-1 ring-blue-200' : 'border-gray-200 dark:border-zinc-700' ?>">
        <p class="text-2xl font-bold text-zinc-900 dark:text-white"><?= $totalCount ?></p>
        <p class="text-xs text-zinc-400">전체</p>
    </a>
    <?php foreach (['paid'=>'결제완료','pending'=>'대기','failed'=>'실패','cancelled'=>'취소'] as $sk=>$sl): ?>
    <a href="<?= $adminUrl ?>/service-orders?status=<?= $sk ?>" class="p-3 bg-white dark:bg-zinc-800 rounded-xl border <?= $filterStatus===$sk ? 'border-blue-500 ring-1 ring-blue-200' : 'border-gray-200 dark:border-zinc-700' ?>">
        <p class="text-2xl font-bold text-zinc-900 dark:text-white"><?= $stats[$sk] ?? 0 ?></p>
        <p class="text-xs text-zinc-400"><?= $sl ?></p>
    </a>
    <?php endforeach; ?>
    <?php if ($pendingOneTime > 0): ?>
    <div class="p-3 bg-amber-50 dark:bg-amber-900/20 rounded-xl border border-amber-300 dark:border-amber-700">
        <p class="text-2xl font-bold text-amber-600"><?= $pendingOneTime ?></p>
        <p class="text-xs text-amber-600">1회성 접수</p>
    </div>
    <?php endif; ?>
</div>

<!-- 검색 -->
<div class="mb-4">
    <form class="flex gap-2" method="GET" action="<?= $adminUrl ?>/service-orders">
        <?php if ($filterStatus): ?><input type="hidden" name="status" value="<?= htmlspecialchars($filterStatus) ?>"><?php endif; ?>
        <input type="text" name="q" value="<?= htmlspecialchars($search) ?>" placeholder="주문번호, 도메인, 이름, 이메일 검색..."
               class="flex-1 px-4 py-2 text-sm border border-gray-300 dark:border-zinc-600 dark:bg-zinc-800 dark:text-white rounded-lg">
        <button class="px-4 py-2 bg-blue-600 text-white text-sm rounded-lg hover:bg-blue-700">검색</button>
    </form>
</div>

<!-- 주문 목록 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
    <table class="w-full text-sm">
        <thead>
            <tr class="text-[11px] text-zinc-400 border-b border-gray-200 dark:border-zinc-700 bg-gray-50 dark:bg-zinc-800/50">
                <th class="py-3 px-4 text-left">주문번호</th>
                <th class="py-3 px-3 text-left">신청자</th>
                <th class="py-3 px-3 text-left">도메인</th>
                <th class="py-3 px-3 text-center">서비스</th>
                <th class="py-3 px-3 text-right">금액</th>
                <th class="py-3 px-3 text-center">상태</th>
                <th class="py-3 px-3 text-center">결제</th>
                <th class="py-3 px-3 text-left">신청일</th>
                <th class="py-3 px-3 text-center"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-gray-100 dark:divide-zinc-700/50">
            <?php if (empty($orders)): ?>
            <tr><td colspan="9" class="py-12 text-center text-zinc-400">주문이 없습니다.</td></tr>
            <?php endif; ?>
            <?php foreach ($orders as $o):
                $ost = $statusLabels[$o['status']] ?? ['알 수 없음', 'bg-gray-100 text-gray-500'];
            ?>
            <tr class="hover:bg-gray-50 dark:hover:bg-zinc-700/30 transition">
                <td class="py-3 px-4">
                    <a href="<?= $adminUrl ?>/service-orders/<?= htmlspecialchars($o['order_number']) ?>" class="font-mono text-blue-600 hover:underline"><?= htmlspecialchars($o['order_number']) ?></a>
                </td>
                <td class="py-3 px-3">
                    <?php $dispName = decrypt($o['applicant_name'] ?: '') ?: decrypt($o['user_name'] ?: '') ?: '-'; ?>
                    <p class="font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($dispName) ?></p>
                    <p class="text-[10px] text-zinc-400"><?= htmlspecialchars($o['applicant_email'] ?: $o['user_email'] ?: '') ?></p>
                </td>
                <td class="py-3 px-3 font-mono text-xs text-zinc-600 dark:text-zinc-300"><?= htmlspecialchars($o['domain'] ?: '-') ?></td>
                <td class="py-3 px-3 text-center"><span class="text-xs text-zinc-500"><?= (int)$o['sub_count'] ?>개</span></td>
                <td class="py-3 px-3 text-right font-medium"><?= (int)$o['total'] > 0 ? $fmtPrice($o['total'], $o['currency']) : '<span class="text-green-500">무료</span>' ?></td>
                <td class="py-3 px-3 text-center"><span class="text-[10px] px-2 py-0.5 rounded-full font-medium <?= $ost[1] ?>"><?= $ost[0] ?></span></td>
                <td class="py-3 px-3 text-center text-xs text-zinc-400"><?= $o['payment_method'] === 'free' ? '무료' : ($o['payment_method'] === 'bank' ? '계좌이체' : '카드') ?></td>
                <td class="py-3 px-3 text-xs text-zinc-400"><?= date('Y-m-d H:i', strtotime($o['created_at'])) ?></td>
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
    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
    <a href="<?= $adminUrl ?>/service-orders?page=<?= $i ?><?= $filterStatus ? '&status='.$filterStatus : '' ?><?= $search ? '&q='.urlencode($search) : '' ?>"
       class="px-3 py-1.5 text-xs rounded-lg <?= $i === $page ? 'bg-blue-600 text-white' : 'bg-white dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300 border border-gray-200 dark:border-zinc-600 hover:bg-gray-50' ?>"><?= $i ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>

<?php include BASE_PATH . '/resources/views/admin/reservations/_foot.php'; ?>
