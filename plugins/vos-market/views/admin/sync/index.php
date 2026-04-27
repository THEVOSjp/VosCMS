<?php
$pageHeaderTitle = '설치 현황 추적';
include __DIR__ . '/../_head.php';
$db = mkt_pdo(); $pfx = $_mktPrefix;

$q       = trim($_GET['q'] ?? '');
$page    = max(1, (int)($_GET['page'] ?? 1));
$perPage = 30;
$offset  = ($page - 1) * $perPage;

// 도메인별 집계
$where = []; $params = [];
if ($q) {
    $where[] = "(r.domain LIKE ? OR r.vos_key LIKE ?)";
    $params[] = "%$q%"; $params[] = "%$q%";
}
$ws = $where ? 'WHERE ' . implode(' AND ', $where) : '';

$totalQ = $db->prepare("SELECT COUNT(DISTINCT domain, vos_key) FROM {$pfx}mkt_sync_reports r $ws");
$totalQ->execute($params);
$total = (int)$totalQ->fetchColumn();

$stRows = $db->prepare("
    SELECT
        r.domain, r.vos_key,
        COUNT(*)                                                         AS total_items,
        SUM(CASE WHEN r.status = 'licensed'        THEN 1 ELSE 0 END)    AS licensed,
        SUM(CASE WHEN r.status = 'unlicensed'      THEN 1 ELSE 0 END)    AS unlicensed,
        SUM(CASE WHEN r.status = 'unknown_product' THEN 1 ELSE 0 END)    AS unknown_cnt,
        MIN(r.first_seen_at)                                             AS first_seen_at,
        MAX(r.last_seen_at)                                              AS last_seen_at
      FROM {$pfx}mkt_sync_reports r
    $ws
     GROUP BY r.domain, r.vos_key
     ORDER BY MAX(r.last_seen_at) DESC
     LIMIT $perPage OFFSET $offset
");
$stRows->execute($params);
$rows = $stRows->fetchAll();
$totalPages = (int)ceil($total / $perPage);

$adminUrl = $_mktAdmin;
?>

<div class="flex items-center justify-between mb-5">
    <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">설치 현황 추적</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">VosCMS 사이트(도메인)별 설치 현황을 관리합니다</p>
    </div>
</div>

<!-- 검색 -->
<form method="GET" class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl p-4 mb-5 flex gap-3 flex-wrap items-center">
    <div class="flex-1 min-w-[200px]">
        <input type="text" name="q" value="<?= htmlspecialchars($q) ?>"
               placeholder="도메인 · VosCMS 키 검색"
               class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 focus:ring-2 focus:ring-indigo-500">
    </div>
    <button class="px-4 py-2 bg-zinc-800 dark:bg-zinc-600 hover:bg-zinc-900 text-white rounded-lg text-sm font-medium">검색</button>
    <?php if ($q): ?>
    <a href="<?= $adminUrl ?>/market/sync" class="px-3 py-2 text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">초기화</a>
    <?php endif; ?>
</form>

<p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4">
    전체 <?= number_format($total) ?>개 도메인
    <?php if ($totalPages > 1): ?>(<?= $page ?> / <?= $totalPages ?> 페이지)<?php endif; ?>
</p>

<?php if (empty($rows)): ?>
<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-16 text-center">
    <p class="text-zinc-400 dark:text-zinc-500">수집된 설치 보고가 없습니다.</p>
</div>
<?php else: ?>
<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-700">
            <tr>
                <th class="text-left px-4 py-3 text-zinc-500 dark:text-zinc-400 font-medium">도메인</th>
                <th class="text-left px-4 py-3 text-zinc-500 dark:text-zinc-400 font-medium">VosCMS 키</th>
                <th class="text-center px-4 py-3 text-zinc-500 dark:text-zinc-400 font-medium">전체</th>
                <th class="text-center px-4 py-3 text-zinc-500 dark:text-zinc-400 font-medium">인증됨</th>
                <th class="text-center px-4 py-3 text-zinc-500 dark:text-zinc-400 font-medium">미인증</th>
                <th class="text-center px-4 py-3 text-zinc-500 dark:text-zinc-400 font-medium">알 수 없음</th>
                <th class="text-left px-4 py-3 text-zinc-500 dark:text-zinc-400 font-medium">마지막 확인</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
        <?php foreach ($rows as $row):
            $showUrl = $adminUrl . '/market/sync/show?domain=' . urlencode($row['domain']) . '&vos_key=' . urlencode($row['vos_key']);
            $hasIssue = ((int)$row['unlicensed'] + (int)$row['unknown_cnt']) > 0;
        ?>
        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
            <td class="px-4 py-3 font-medium text-zinc-800 dark:text-zinc-200">
                <a href="<?= htmlspecialchars($showUrl) ?>" class="hover:text-indigo-600"><?= htmlspecialchars($row['domain']) ?></a>
                <?php if ($hasIssue): ?><span class="ml-1 inline-block w-1.5 h-1.5 rounded-full bg-red-500" title="관리 필요"></span><?php endif; ?>
            </td>
            <td class="px-4 py-3 font-mono text-xs text-zinc-400 dark:text-zinc-500"><?= htmlspecialchars($row['vos_key']) ?></td>
            <td class="px-4 py-3 text-center text-zinc-700 dark:text-zinc-300 font-medium"><?= number_format($row['total_items']) ?></td>
            <td class="px-4 py-3 text-center">
                <?php if ((int)$row['licensed'] > 0): ?>
                <span class="inline-block px-2 py-0.5 text-xs font-medium rounded bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400"><?= (int)$row['licensed'] ?></span>
                <?php else: ?><span class="text-zinc-300 dark:text-zinc-600">0</span><?php endif; ?>
            </td>
            <td class="px-4 py-3 text-center">
                <?php if ((int)$row['unlicensed'] > 0): ?>
                <span class="inline-block px-2 py-0.5 text-xs font-medium rounded bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400"><?= (int)$row['unlicensed'] ?></span>
                <?php else: ?><span class="text-zinc-300 dark:text-zinc-600">0</span><?php endif; ?>
            </td>
            <td class="px-4 py-3 text-center">
                <?php if ((int)$row['unknown_cnt'] > 0): ?>
                <span class="inline-block px-2 py-0.5 text-xs font-medium rounded bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400"><?= (int)$row['unknown_cnt'] ?></span>
                <?php else: ?><span class="text-zinc-300 dark:text-zinc-600">0</span><?php endif; ?>
            </td>
            <td class="px-4 py-3 text-xs text-zinc-400 dark:text-zinc-500 whitespace-nowrap">
                <?= htmlspecialchars(substr($row['last_seen_at'], 0, 16)) ?>
            </td>
            <td class="px-4 py-3 text-right">
                <a href="<?= htmlspecialchars($showUrl) ?>"
                   class="inline-block px-3 py-1 bg-indigo-600 hover:bg-indigo-700 text-white text-xs rounded font-medium transition">
                    관리 →
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>

<?php if ($totalPages > 1): ?>
<div class="mt-6 flex justify-center items-center gap-1">
    <?php if ($page > 1): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page-1])) ?>"
       class="px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">‹</a>
    <?php endif; ?>
    <?php for ($p = max(1,$page-2); $p <= min($totalPages,$page+2); $p++): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"
       class="px-3 py-2 rounded-lg border text-sm font-medium <?= $p===$page ? 'bg-indigo-600 border-indigo-600 text-white' : 'border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700' ?>">
        <?= $p ?>
    </a>
    <?php endfor; ?>
    <?php if ($page < $totalPages): ?>
    <a href="?<?= http_build_query(array_merge($_GET, ['page' => $page+1])) ?>"
       class="px-3 py-2 rounded-lg border border-zinc-300 dark:border-zinc-600 text-sm text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100 dark:hover:bg-zinc-700">›</a>
    <?php endif; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../_foot.php'; ?>
