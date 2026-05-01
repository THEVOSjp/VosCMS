<?php
/**
 * 관리자 — 회계 관리 (rzx_payments 기반)
 * 매출/환불/부가세 집계 + 거래 원장 (전표) 표시.
 */
if (!function_exists('__')) require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';

$_svcLangFile = BASE_PATH . '/plugins/vos-hosting/lang/' . \RzxLib\Core\I18n\Translator::getLocale() . '/services.php';
if (!file_exists($_svcLangFile)) $_svcLangFile = BASE_PATH . '/plugins/vos-hosting/lang/en/services.php';
if (file_exists($_svcLangFile)) \RzxLib\Core\I18n\Translator::merge('services', require $_svcLangFile);

$pageTitle = __('services.admin_accounting.page_title') . ' - ' . ($config['app_name'] ?? 'VosCMS') . ' Admin';
$pageHeaderTitle = __('services.admin_accounting.header_title');
$pageSubTitle = __('services.admin_accounting.sub_title');
$pageSubDesc = __('services.admin_accounting.sub_desc');

$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$pdo = \RzxLib\Core\Database\Connection::getInstance()->getPdo();
require_once BASE_PATH . '/rzxlib/Core/Helpers/Encryption.php';

$filterStatus = $_GET['status'] ?? '';
$filterMonth = $_GET['month'] ?? date('Y-m');
$filterFrom = $_GET['from'] ?? '';
$filterTo = $_GET['to'] ?? '';
$searchKey = trim($_GET['q'] ?? '');
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;

$where = [];
$params = [];
if ($filterStatus) { $where[] = "p.status = ?"; $params[] = $filterStatus; }
// paid 는 paid_at, refund 는 cancelled_at 기준이라 COALESCE
if ($filterFrom) { $where[] = "DATE(COALESCE(p.paid_at, p.cancelled_at)) >= ?"; $params[] = $filterFrom; }
if ($filterTo) { $where[] = "DATE(COALESCE(p.paid_at, p.cancelled_at)) <= ?"; $params[] = $filterTo; }
if (!$filterFrom && !$filterTo && $filterMonth) { $where[] = "DATE_FORMAT(COALESCE(p.paid_at, p.cancelled_at), '%Y-%m') = ?"; $params[] = $filterMonth; }
if ($searchKey !== '') {
    $where[] = "(p.payment_key LIKE ? OR p.order_id LIKE ? OR u.email LIKE ?)";
    $params[] = '%' . $searchKey . '%'; $params[] = '%' . $searchKey . '%'; $params[] = '%' . $searchKey . '%';
}
$whereSQL = $where ? ('WHERE ' . implode(' AND ', $where)) : '';

$cntStmt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}payments p LEFT JOIN {$prefix}users u ON p.user_id = u.id $whereSQL");
$cntStmt->execute($params);
$totalCnt = (int)$cntStmt->fetchColumn();
$totalPages = max(1, (int)ceil($totalCnt / $perPage));
$offset = ($page - 1) * $perPage;

$listStmt = $pdo->prepare("SELECT p.*, u.email AS user_email, u.name AS user_name,
    COALESCE(p.paid_at, p.cancelled_at) AS event_at
    FROM {$prefix}payments p LEFT JOIN {$prefix}users u ON p.user_id = u.id
    $whereSQL ORDER BY COALESCE(p.paid_at, p.cancelled_at) DESC, p.id DESC LIMIT $perPage OFFSET $offset");
$listStmt->execute($params);
$payments = $listStmt->fetchAll(PDO::FETCH_ASSOC);

// 회계 집계: paid row + refund row 별도 전표
//   매출 = status='paid' (원거래)
//   환불 = status IN ('refund','refunded')  (refund=신규 패턴, refunded=레거시 mutate)
$summarySql = "SELECT
    SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) AS total_paid,
    SUM(CASE WHEN status IN ('refund','refunded') THEN amount ELSE 0 END) AS total_refunded,
    SUM(CASE WHEN status = 'paid' THEN 1 ELSE 0 END) AS cnt_paid,
    SUM(CASE WHEN status IN ('refund','refunded') THEN 1 ELSE 0 END) AS cnt_refunded
    FROM {$prefix}payments p $whereSQL";
$sumStmt = $pdo->prepare($summarySql);
$sumStmt->execute($params);
$summary = $sumStmt->fetch(PDO::FETCH_ASSOC) ?: [];
$totalPaid = (int)($summary['total_paid'] ?? 0);
$totalRefunded = (int)($summary['total_refunded'] ?? 0);
$net = $totalPaid - $totalRefunded;
$netBody = (int)round($net * 100 / 110);
$netVat = $net - $netBody;

// trend: paid 는 paid_at 기준, refund 는 cancelled_at 기준이라 COALESCE
$trendStmt = $pdo->query("SELECT DATE_FORMAT(COALESCE(paid_at, cancelled_at), '%Y-%m') AS m,
    SUM(CASE WHEN status = 'paid' THEN amount ELSE 0 END) AS paid,
    SUM(CASE WHEN status IN ('refund','refunded') THEN amount ELSE 0 END) AS refunded
    FROM {$prefix}payments
    WHERE COALESCE(paid_at, cancelled_at) >= DATE_SUB(NOW(), INTERVAL 12 MONTH)
    GROUP BY DATE_FORMAT(COALESCE(paid_at, cancelled_at), '%Y-%m') ORDER BY m DESC");
$trend = $trendStmt->fetchAll(PDO::FETCH_ASSOC);

$fmtJpy = function($n) { return '¥' . number_format((int)$n); };
$statusLabels = [
    'paid' => [__('services.admin_accounting.st_paid'), 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400'],
    'refund' => [__('services.admin_accounting.st_refunded'), 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'],
    'refunded' => [__('services.admin_accounting.st_refunded'), 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'],
    'failed' => [__('services.admin_accounting.st_failed'), 'bg-gray-100 text-gray-500 dark:bg-zinc-700 dark:text-zinc-400'],
    'pending' => [__('services.admin_accounting.st_pending'), 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'],
];

include BASE_PATH . '/resources/views/admin/reservations/_head.php';
?>

<div class="px-4 sm:px-6 lg:px-8 py-6">
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-3 mb-5">
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-gray-200 dark:border-zinc-700">
            <p class="text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1"><?= htmlspecialchars(__('services.admin_accounting.stats_paid')) ?></p>
            <p class="text-2xl font-bold text-emerald-600 dark:text-emerald-400"><?= $fmtJpy($totalPaid) ?></p>
            <p class="text-[10px] text-zinc-400 mt-1"><?= (int)($summary['cnt_paid'] ?? 0) ?> <?= htmlspecialchars(__('services.admin_accounting.cases')) ?></p>
        </div>
        <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-gray-200 dark:border-zinc-700">
            <p class="text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-1"><?= htmlspecialchars(__('services.admin_accounting.stats_refunded')) ?></p>
            <p class="text-2xl font-bold text-red-600 dark:text-red-400"><?= $fmtJpy($totalRefunded) ?></p>
            <p class="text-[10px] text-zinc-400 mt-1"><?= (int)($summary['cnt_refunded'] ?? 0) ?> <?= htmlspecialchars(__('services.admin_accounting.cases')) ?></p>
        </div>
        <div class="bg-blue-50 dark:bg-blue-900/20 rounded-xl p-4 border border-blue-200 dark:border-blue-800">
            <p class="text-[10px] font-bold text-blue-700 dark:text-blue-400 uppercase tracking-wider mb-1"><?= htmlspecialchars(__('services.admin_accounting.stats_net')) ?></p>
            <p class="text-2xl font-bold text-blue-700 dark:text-blue-300"><?= $fmtJpy($net) ?></p>
            <p class="text-[10px] text-blue-600 dark:text-blue-400 mt-1"><?= htmlspecialchars(__('services.admin_accounting.stats_net_hint')) ?></p>
        </div>
        <div class="bg-amber-50 dark:bg-amber-900/20 rounded-xl p-4 border border-amber-200 dark:border-amber-800">
            <p class="text-[10px] font-bold text-amber-700 dark:text-amber-400 uppercase tracking-wider mb-1"><?= htmlspecialchars(__('services.admin_accounting.stats_vat')) ?></p>
            <p class="text-2xl font-bold text-amber-700 dark:text-amber-300"><?= $fmtJpy($netVat) ?></p>
            <p class="text-[10px] text-amber-600 dark:text-amber-400 mt-1"><?= htmlspecialchars(__('services.admin_accounting.stats_vat_hint')) ?> (<?= $fmtJpy($netBody) ?>)</p>
        </div>
    </div>

    <?php if (!empty($trend)): ?>
    <div class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-gray-200 dark:border-zinc-700 mb-5">
        <p class="text-[10px] font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider mb-3"><?= htmlspecialchars(__('services.admin_accounting.trend_title')) ?></p>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead>
                    <tr class="text-[10px] uppercase tracking-wider text-zinc-500 dark:text-zinc-400 border-b border-gray-200 dark:border-zinc-700">
                        <th class="px-3 py-2 text-left"><?= htmlspecialchars(__('services.admin_accounting.col_month')) ?></th>
                        <th class="px-3 py-2 text-right"><?= htmlspecialchars(__('services.admin_accounting.col_paid')) ?></th>
                        <th class="px-3 py-2 text-right"><?= htmlspecialchars(__('services.admin_accounting.col_refunded')) ?></th>
                        <th class="px-3 py-2 text-right"><?= htmlspecialchars(__('services.admin_accounting.col_net')) ?></th>
                        <th class="px-3 py-2 text-right"><?= htmlspecialchars(__('services.admin_accounting.col_vat')) ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-zinc-700/50">
                    <?php foreach ($trend as $t):
                        $tnet = (int)$t['paid'] - (int)$t['refunded'];
                        $tvat = (int)round($tnet * 10 / 110);
                    ?>
                    <tr>
                        <td class="px-3 py-2 font-medium text-zinc-700 dark:text-zinc-200"><?= htmlspecialchars($t['m']) ?></td>
                        <td class="px-3 py-2 text-right text-emerald-700 dark:text-emerald-400"><?= $fmtJpy($t['paid']) ?></td>
                        <td class="px-3 py-2 text-right text-red-600 dark:text-red-400"><?= (int)$t['refunded'] > 0 ? '-' . $fmtJpy($t['refunded']) : '¥0' ?></td>
                        <td class="px-3 py-2 text-right font-bold text-blue-700 dark:text-blue-300"><?= $fmtJpy($tnet) ?></td>
                        <td class="px-3 py-2 text-right text-amber-700 dark:text-amber-400"><?= $fmtJpy($tvat) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
    </div>
    <?php endif; ?>

    <form method="GET" class="bg-white dark:bg-zinc-800 rounded-xl p-4 border border-gray-200 dark:border-zinc-700 mb-4 flex flex-wrap items-end gap-2">
        <div>
            <label class="block text-[10px] text-zinc-500 mb-1"><?= htmlspecialchars(__('services.admin_accounting.f_month')) ?></label>
            <input type="month" name="month" value="<?= htmlspecialchars($filterMonth) ?>" class="px-3 py-1.5 text-xs border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg">
        </div>
        <div>
            <label class="block text-[10px] text-zinc-500 mb-1"><?= htmlspecialchars(__('services.admin_accounting.f_from')) ?></label>
            <input type="date" name="from" value="<?= htmlspecialchars($filterFrom) ?>" class="px-3 py-1.5 text-xs border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg">
        </div>
        <div>
            <label class="block text-[10px] text-zinc-500 mb-1"><?= htmlspecialchars(__('services.admin_accounting.f_to')) ?></label>
            <input type="date" name="to" value="<?= htmlspecialchars($filterTo) ?>" class="px-3 py-1.5 text-xs border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg">
        </div>
        <div>
            <label class="block text-[10px] text-zinc-500 mb-1"><?= htmlspecialchars(__('services.admin_accounting.f_status')) ?></label>
            <select name="status" class="px-3 py-1.5 text-xs border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg">
                <option value=""><?= htmlspecialchars(__('services.admin_accounting.f_status_all')) ?></option>
                <option value="paid" <?= $filterStatus === 'paid' ? 'selected' : '' ?>><?= htmlspecialchars(__('services.admin_accounting.st_paid')) ?></option>
                <option value="refund" <?= $filterStatus === 'refund' ? 'selected' : '' ?>><?= htmlspecialchars(__('services.admin_accounting.st_refunded')) ?></option>
                <option value="failed" <?= $filterStatus === 'failed' ? 'selected' : '' ?>><?= htmlspecialchars(__('services.admin_accounting.st_failed')) ?></option>
            </select>
        </div>
        <div class="flex-1 min-w-[200px]">
            <label class="block text-[10px] text-zinc-500 mb-1"><?= htmlspecialchars(__('services.admin_accounting.f_search')) ?></label>
            <input type="text" name="q" value="<?= htmlspecialchars($searchKey) ?>" placeholder="<?= htmlspecialchars(__('services.admin_accounting.search_placeholder')) ?>" class="w-full px-3 py-1.5 text-xs border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg">
        </div>
        <button type="submit" class="px-4 py-1.5 text-xs font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg"><?= htmlspecialchars(__('services.admin_accounting.btn_filter')) ?></button>
    </form>

    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-5 py-3 border-b border-gray-100 dark:border-zinc-700">
            <p class="text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wider"><?= htmlspecialchars(__('services.admin_accounting.list_title', ['count' => $totalCnt])) ?></p>
        </div>
        <?php if (empty($payments)): ?>
        <div class="p-12 text-center text-sm text-zinc-400"><?= htmlspecialchars(__('services.admin_accounting.empty')) ?></div>
        <?php else: ?>
        <div class="overflow-x-auto">
            <table class="w-full text-xs">
                <thead class="bg-gray-50 dark:bg-zinc-700/50">
                    <tr class="text-[10px] uppercase tracking-wider text-zinc-500 dark:text-zinc-400">
                        <th class="px-3 py-2.5 text-left"><?= htmlspecialchars(__('services.admin_accounting.col_paid_at')) ?></th>
                        <th class="px-3 py-2.5 text-left"><?= htmlspecialchars(__('services.admin_accounting.col_user')) ?></th>
                        <th class="px-3 py-2.5 text-left"><?= htmlspecialchars(__('services.admin_accounting.col_order')) ?></th>
                        <th class="px-3 py-2.5 text-left"><?= htmlspecialchars(__('services.admin_accounting.col_charge')) ?></th>
                        <th class="px-3 py-2.5 text-right"><?= htmlspecialchars(__('services.admin_accounting.col_amount')) ?></th>
                        <th class="px-3 py-2.5 text-left"><?= htmlspecialchars(__('services.admin_accounting.col_status_short')) ?></th>
                        <th class="px-3 py-2.5 text-left"><?= htmlspecialchars(__('services.admin_accounting.col_method')) ?></th>
                        <th class="px-3 py-2.5 text-left"><?= htmlspecialchars(__('services.admin_accounting.col_memo')) ?></th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-100 dark:divide-zinc-700">
                    <?php foreach ($payments as $p):
                        $st = $statusLabels[$p['status']] ?? [$p['status'], 'bg-gray-100 text-gray-500'];
                        $methDetail = json_decode($p['method_detail'] ?? '{}', true) ?: [];
                        $cardLast = $methDetail['card_last4'] ?? '';
                        $meta = json_decode($p['metadata'] ?? '{}', true) ?: [];
                        $memo = '';
                        if (!empty($meta['source'])) $memo .= ($meta['source'] === 'mypage_add_domain' ? __('services.admin_accounting.memo_add_domain') : $meta['source']);
                        if (!empty($meta['domain'])) $memo .= ' · ' . $meta['domain'];
                        if (!empty($p['cancel_reason'])) $memo .= ' / ' . $p['cancel_reason'];
                        $userName = $p['user_name'] ? (decrypt($p['user_name']) ?: $p['user_name']) : '-';
                        $isRefund = in_array($p['status'], ['refund','refunded'], true);
                        $eventAt = $p['event_at'] ?? $p['paid_at'] ?? $p['cancelled_at'] ?? null;
                    ?>
                    <tr>
                        <td class="px-3 py-2.5 text-[11px] text-zinc-500"><?= $eventAt ? date('Y-m-d H:i', strtotime($eventAt)) : '-' ?></td>
                        <td class="px-3 py-2.5">
                            <p class="font-medium text-zinc-900 dark:text-white truncate max-w-[140px]" title="<?= htmlspecialchars($userName) ?>"><?= htmlspecialchars($userName) ?></p>
                            <p class="text-[10px] text-zinc-400 truncate max-w-[140px]"><?= htmlspecialchars($p['user_email'] ?? '-') ?></p>
                        </td>
                        <td class="px-3 py-2.5">
                            <a href="<?= htmlspecialchars($adminUrl) ?>/service-orders/<?= htmlspecialchars($p['order_id']) ?>" class="font-mono text-[11px] text-blue-600 hover:underline"><?= htmlspecialchars($p['order_id'] ?? '-') ?></a>
                        </td>
                        <td class="px-3 py-2.5 font-mono text-[10px] text-zinc-500" title="<?= htmlspecialchars($p['payment_key']) ?>"><?= htmlspecialchars(substr($p['payment_key'] ?? '', 0, 18)) ?>...</td>
                        <td class="px-3 py-2.5 text-right font-bold <?= $isRefund ? 'text-red-600 dark:text-red-400' : 'text-emerald-600 dark:text-emerald-400' ?>">
                            <?= $isRefund ? '-' : '' ?><?= $fmtJpy($p['amount']) ?>
                        </td>
                        <td class="px-3 py-2.5">
                            <span class="px-2 py-0.5 text-[10px] font-medium rounded <?= $st[1] ?>"><?= htmlspecialchars($st[0]) ?></span>
                        </td>
                        <td class="px-3 py-2.5 text-[11px] text-zinc-500">
                            <?= htmlspecialchars(strtoupper($p['gateway'] ?? '-')) ?>
                            <?php if ($cardLast): ?><span class="text-[10px] text-zinc-400">(<?= htmlspecialchars($cardLast) ?>)</span><?php endif; ?>
                        </td>
                        <td class="px-3 py-2.5 text-[11px] text-zinc-500 truncate max-w-[200px]" title="<?= htmlspecialchars($memo) ?>"><?= htmlspecialchars($memo) ?></td>
                    </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php endif; ?>

        <?php if ($totalPages > 1): ?>
        <div class="px-5 py-3 border-t border-gray-100 dark:border-zinc-700 flex items-center justify-center gap-1">
            <?php
            $qp = function($override) use ($filterStatus, $filterMonth, $filterFrom, $filterTo, $searchKey, $page, $adminUrl) {
                $q = array_filter([
                    'status' => $filterStatus, 'month' => $filterMonth, 'from' => $filterFrom, 'to' => $filterTo, 'q' => $searchKey, 'page' => $page,
                ] + $override);
                return $adminUrl . '/accounting?' . http_build_query($q);
            };
            $start = max(1, $page - 3);
            $end = min($totalPages, $page + 3);
            ?>
            <?php if ($page > 1): ?><a href="<?= $qp(['page' => $page - 1]) ?>" class="px-2.5 py-1.5 text-xs rounded-lg bg-white dark:bg-zinc-700 text-zinc-500 border border-gray-200 dark:border-zinc-600">&laquo;</a><?php endif; ?>
            <?php for ($i = $start; $i <= $end; $i++): ?>
            <a href="<?= $qp(['page' => $i]) ?>" class="px-3 py-1.5 text-xs rounded-lg <?= $i === $page ? 'bg-blue-600 text-white' : 'bg-white dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300 border border-gray-200 dark:border-zinc-600' ?>"><?= $i ?></a>
            <?php endfor; ?>
            <?php if ($page < $totalPages): ?><a href="<?= $qp(['page' => $page + 1]) ?>" class="px-2.5 py-1.5 text-xs rounded-lg bg-white dark:bg-zinc-700 text-zinc-500 border border-gray-200 dark:border-zinc-600">&raquo;</a><?php endif; ?>
        </div>
        <?php endif; ?>
    </div>
</div>

<?php include BASE_PATH . '/resources/views/admin/reservations/_foot.php'; ?>
