<?php
/**
 * 예약 목록 페이지
 */
include __DIR__ . '/_init.php';

$pageTitle = __('reservations.list') . ' - ' . ($config['app_name'] ?? 'RezlyX') . ' Admin';

// 필터
$filterDate = $_GET['date'] ?? '';
$filterStatus = $_GET['status'] ?? '';
$filterService = $_GET['service_id'] ?? '';
$filterSearch = $_GET['q'] ?? '';

// 쿼리
$sql = "SELECT * FROM {$prefix}reservations WHERE 1=1";
$params = [];

if ($filterDate) {
    $sql .= " AND reservation_date = ?";
    $params[] = $filterDate;
}
if ($filterStatus) {
    $sql .= " AND status = ?";
    $params[] = $filterStatus;
}
if ($filterService) {
    $sql .= " AND service_id = ?";
    $params[] = $filterService;
}
if ($filterSearch) {
    $sql .= " AND (customer_name LIKE ? OR customer_phone LIKE ? OR reservation_number LIKE ?)";
    $s = "%{$filterSearch}%";
    $params = array_merge($params, [$s, $s, $s]);
}

$sql .= " ORDER BY reservation_date DESC, start_time DESC LIMIT 100";
$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$reservations = $stmt->fetchAll(PDO::FETCH_ASSOC);

$services = getServices($pdo, $prefix);

// 오늘 통계
$todayStmt = $pdo->prepare("SELECT status, COUNT(*) as cnt FROM {$prefix}reservations WHERE reservation_date = CURDATE() GROUP BY status");
$todayStmt->execute();
$todayStats = [];
while ($row = $todayStmt->fetch(PDO::FETCH_ASSOC)) {
    $todayStats[$row['status']] = (int)$row['cnt'];
}
$todayTotal = array_sum($todayStats);

include __DIR__ . '/_head.php';
?>

<!-- 상단 헤더 + 통계 -->
<div class="flex items-center justify-between mb-6">
    <div>
        <h2 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= __('reservations.list') ?></h2>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
            <?= __('reservations.filter.today') ?>: <?= $todayTotal ?>건
            <?php if (!empty($todayStats['pending'])): ?>
                · <span class="text-yellow-600"><?= __('reservations.filter.pending') ?> <?= $todayStats['pending'] ?></span>
            <?php endif; ?>
            <?php if (!empty($todayStats['confirmed'])): ?>
                · <span class="text-blue-600"><?= __('reservations.filter.confirmed') ?> <?= $todayStats['confirmed'] ?></span>
            <?php endif; ?>
        </p>
    </div>
    <div class="flex gap-2">
        <a href="<?= $adminUrl ?>/reservations/calendar"
           class="px-4 py-2 text-zinc-700 dark:text-zinc-300 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 text-sm transition flex items-center">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
            <?= __('reservations.calendar') ?>
        </a>
        <a href="<?= $adminUrl ?>/reservations/create"
           class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm transition flex items-center">
            <svg class="w-4 h-4 mr-1.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
            <?= __('reservations.create') ?>
        </a>
    </div>
</div>

<!-- 필터 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-4 mb-6">
    <form method="GET" class="flex flex-wrap items-end gap-3">
        <div>
            <label class="block text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('reservations.filter.today') ?></label>
            <input type="date" name="date" value="<?= htmlspecialchars($filterDate) ?>"
                   class="px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100">
        </div>
        <div>
            <label class="block text-xs text-zinc-500 dark:text-zinc-400 mb-1">상태</label>
            <select name="status" class="px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100">
                <option value=""><?= __('reservations.filter.all') ?></option>
                <option value="pending" <?= $filterStatus === 'pending' ? 'selected' : '' ?>><?= __('reservations.filter.pending') ?></option>
                <option value="confirmed" <?= $filterStatus === 'confirmed' ? 'selected' : '' ?>><?= __('reservations.filter.confirmed') ?></option>
                <option value="completed" <?= $filterStatus === 'completed' ? 'selected' : '' ?>><?= __('reservations.actions.complete') ?></option>
                <option value="cancelled" <?= $filterStatus === 'cancelled' ? 'selected' : '' ?>><?= __('reservations.actions.cancel') ?></option>
                <option value="no_show" <?= $filterStatus === 'no_show' ? 'selected' : '' ?>><?= __('reservations.actions.no_show') ?></option>
            </select>
        </div>
        <div>
            <label class="block text-xs text-zinc-500 dark:text-zinc-400 mb-1">서비스</label>
            <select name="service_id" class="px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100">
                <option value=""><?= __('reservations.filter.all') ?></option>
                <?php foreach ($services as $svc): ?>
                <option value="<?= $svc['id'] ?>" <?= $filterService === $svc['id'] ? 'selected' : '' ?>><?= htmlspecialchars($svc['name']) ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="block text-xs text-zinc-500 dark:text-zinc-400 mb-1">검색</label>
            <input type="text" name="q" value="<?= htmlspecialchars($filterSearch) ?>" placeholder="이름, 전화번호, 예약번호"
                   class="px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100 placeholder:text-zinc-400 dark:placeholder:text-zinc-500 w-48">
        </div>
        <button type="submit" class="px-4 py-2 bg-zinc-800 dark:bg-zinc-600 text-white rounded-lg text-sm hover:bg-zinc-700 transition">검색</button>
        <?php if ($filterDate || $filterStatus || $filterService || $filterSearch): ?>
        <a href="<?= $adminUrl ?>/reservations" class="px-4 py-2 text-zinc-500 hover:text-zinc-700 text-sm">초기화</a>
        <?php endif; ?>
    </form>
</div>

<!-- 예약 목록 테이블 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
    <?php if (empty($reservations)): ?>
    <div class="text-center py-16 text-zinc-500 dark:text-zinc-400">
        <svg class="w-16 h-16 mx-auto mb-3 opacity-30" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
        <p>예약이 없습니다.</p>
    </div>
    <?php else: ?>
    <div class="overflow-x-auto">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                <tr class="text-left text-xs text-zinc-500 dark:text-zinc-400 uppercase tracking-wider">
                    <th class="px-4 py-3">예약번호</th>
                    <th class="px-4 py-3">고객</th>
                    <th class="px-4 py-3">서비스</th>
                    <th class="px-4 py-3">일시</th>
                    <th class="px-4 py-3">금액</th>
                    <th class="px-4 py-3">상태</th>
                    <th class="px-4 py-3 text-right">액션</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                <?php foreach ($reservations as $r): ?>
                <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition">
                    <td class="px-4 py-3">
                        <a href="<?= $adminUrl ?>/reservations/<?= $r['id'] ?>" class="text-blue-600 dark:text-blue-400 font-mono text-xs hover:underline">
                            <?= htmlspecialchars($r['reservation_number'] ?? '-') ?>
                        </a>
                    </td>
                    <td class="px-4 py-3">
                        <p class="font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($r['customer_name']) ?></p>
                        <p class="text-xs text-zinc-500"><?= htmlspecialchars($r['customer_phone']) ?></p>
                    </td>
                    <td class="px-4 py-3 text-zinc-600 dark:text-zinc-300"><?= htmlspecialchars(getServiceName($pdo, $prefix, $r['service_id'])) ?></td>
                    <td class="px-4 py-3">
                        <p class="text-zinc-900 dark:text-white"><?= $r['reservation_date'] ?></p>
                        <p class="text-xs text-zinc-500"><?= substr($r['start_time'], 0, 5) ?> ~ <?= substr($r['end_time'] ?? '', 0, 5) ?></p>
                    </td>
                    <td class="px-4 py-3 text-zinc-900 dark:text-white"><?= formatPrice((float)($r['final_amount'] ?? 0)) ?></td>
                    <td class="px-4 py-3"><?= statusBadge($r['status']) ?></td>
                    <td class="px-4 py-3 text-right">
                        <div class="flex items-center justify-end gap-1">
                            <a href="<?= $adminUrl ?>/reservations/<?= $r['id'] ?>"
                               class="p-1.5 text-zinc-400 hover:text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/30 rounded transition" title="상세">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M2.458 12C3.732 7.943 7.523 5 12 5c4.478 0 8.268 2.943 9.542 7-1.274 4.057-5.064 7-9.542 7-4.477 0-8.268-2.943-9.542-7z"/></svg>
                            </a>
                            <?php if ($r['status'] === 'pending'): ?>
                            <button onclick="changeStatus('<?= $r['id'] ?>', 'confirm')"
                                    class="p-1.5 text-zinc-400 hover:text-green-600 hover:bg-green-50 dark:hover:bg-green-900/30 rounded transition" title="<?= __('reservations.actions.confirm') ?>">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
                            </button>
                            <?php endif; ?>
                            <?php if (in_array($r['status'], ['pending', 'confirmed'])): ?>
                            <button onclick="changeStatus('<?= $r['id'] ?>', 'cancel')"
                                    class="p-1.5 text-zinc-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/30 rounded transition" title="<?= __('reservations.actions.cancel') ?>">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                            </button>
                            <?php endif; ?>
                        </div>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
    <div class="px-4 py-3 bg-zinc-50 dark:bg-zinc-900/50 text-xs text-zinc-500">
        <?= count($reservations) ?>건 표시 (최대 100건)
    </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/index-js.php'; ?>

<?php include __DIR__ . '/_foot.php'; ?>
