<?php
include __DIR__ . '/../_head.php';
$pageHeaderTitle = __('marketplace.admin_submissions') ?: '심사 대기';
$db = mkt_pdo(); $pfx = $_mktPrefix;

$status = $_GET['status'] ?? 'pending';
$page   = max(1,(int)($_GET['page']??1));
$perPage = 20; $offset = ($page-1)*$perPage;

$validStatuses = ['pending','reviewing','approved','rejected','all'];
if (!in_array($status, $validStatuses)) $status = 'pending';

$where = $status !== 'all' ? "WHERE s.status = '$status'" : '';
$st = $db->prepare("SELECT COUNT(*) FROM {$pfx}mkt_submissions s $where");
$st->execute(); $total = (int)$st->fetchColumn();

$subs = $db->prepare("
    SELECT s.*, p.display_name AS partner_name, p.email AS partner_email
    FROM {$pfx}mkt_submissions s
    LEFT JOIN {$pfx}mkt_partners p ON p.id = s.partner_id
    $where ORDER BY s.submitted_at DESC LIMIT $perPage OFFSET $offset
");
$subs->execute(); $subs = $subs->fetchAll();
$totalPages = (int)ceil($total/$perPage);
$adminUrl = $_mktAdmin;

$statusCounts = [];
foreach (['pending','reviewing','approved','rejected'] as $st_) {
    $r = $db->prepare("SELECT COUNT(*) FROM {$pfx}mkt_submissions WHERE status=?");
    $r->execute([$st_]); $statusCounts[$st_] = (int)$r->fetchColumn();
}

// ZIP 미첨부로 pending 상태인 아이템 목록 (심사 대기와 통합 표시)
$pendingItems = $db->query("
    SELECT i.id, i.slug, i.type, i.name, i.latest_version, i.created_at,
           (SELECT COUNT(*) FROM {$pfx}mkt_item_versions v
             WHERE v.item_id = i.id AND v.file_path IS NOT NULL AND v.file_path != '') AS has_file
      FROM {$pfx}mkt_items i
     WHERE i.status = 'pending'
     ORDER BY i.created_at DESC
")->fetchAll();
$statusMeta = [
    'pending'   => ['대기중',   'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'],
    'reviewing' => ['검토중',   'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400'],
    'approved'  => ['승인',     'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'],
    'rejected'  => ['반려',     'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'],
];
$typeLabels = ['plugin'=>'플러그인','theme'=>'테마','widget'=>'위젯','skin'=>'스킨'];
?>
<div class="flex items-center justify-between mb-6">
    <div>
        <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">심사 대기</h1>
        <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-0.5">파트너 아이템 심사 · ZIP 미업로드 아이템</p>
    </div>
</div>

<!-- ── 패키지 업로드 대기 아이템 ─────────────────────── -->
<?php if (!empty($pendingItems)): ?>
<div class="bg-yellow-50 dark:bg-yellow-900/10 border border-yellow-300 dark:border-yellow-800 rounded-xl overflow-hidden mb-6">
    <div class="px-4 py-3 bg-yellow-100 dark:bg-yellow-900/30 border-b border-yellow-200 dark:border-yellow-800 flex items-center justify-between">
        <div>
            <h2 class="text-sm font-bold text-yellow-900 dark:text-yellow-200">📦 ZIP 패키지 업로드 대기</h2>
            <p class="text-[11px] text-yellow-700 dark:text-yellow-400 mt-0.5">아이템 편집 화면에서 ZIP을 업로드하면 'active' 상태로 전환됩니다.</p>
        </div>
        <span class="text-xs font-bold text-yellow-900 dark:text-yellow-200"><?= count($pendingItems) ?>개</span>
    </div>
    <table class="w-full text-sm">
        <thead class="bg-yellow-50/50 dark:bg-yellow-900/20 text-xs text-yellow-700 dark:text-yellow-400 border-b border-yellow-200 dark:border-yellow-800">
            <tr>
                <th class="text-left px-4 py-2 font-medium">이름</th>
                <th class="text-left px-4 py-2 font-medium">타입</th>
                <th class="text-left px-4 py-2 font-medium">버전</th>
                <th class="text-left px-4 py-2 font-medium">생성일</th>
                <th class="px-4 py-2"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-yellow-100 dark:divide-yellow-900/30 bg-white dark:bg-zinc-800">
        <?php foreach ($pendingItems as $pi):
            $nmArr = json_decode($pi['name'] ?? '{}', true) ?: [];
            $piName = $nmArr[$_mktLocale] ?? $nmArr['en'] ?? $pi['slug'];
            $tl = ['plugin'=>'플러그인','widget'=>'위젯','theme'=>'테마','skin'=>'스킨/레이아웃'][$pi['type']] ?? $pi['type'];
        ?>
        <tr class="hover:bg-yellow-50/30 dark:hover:bg-yellow-900/10">
            <td class="px-4 py-2.5">
                <div class="font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($piName) ?></div>
                <div class="text-[10px] font-mono text-zinc-400"><?= htmlspecialchars($pi['slug']) ?></div>
            </td>
            <td class="px-4 py-2.5 text-xs text-zinc-500"><?= htmlspecialchars($tl) ?></td>
            <td class="px-4 py-2.5 text-xs text-zinc-500 font-mono">v<?= htmlspecialchars($pi['latest_version'] ?? '-') ?></td>
            <td class="px-4 py-2.5 text-xs text-zinc-400"><?= htmlspecialchars(substr($pi['created_at'], 0, 10)) ?></td>
            <td class="px-4 py-2.5 text-right">
                <a href="<?= $adminUrl ?>/market/items/edit?id=<?= $pi['id'] ?>"
                   class="inline-block px-2.5 py-1 bg-indigo-600 hover:bg-indigo-700 text-white text-[11px] font-medium rounded">
                    ZIP 업로드 →
                </a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>

<h2 class="text-sm font-bold text-zinc-600 dark:text-zinc-400 uppercase mb-3">파트너 제출 심사</h2>

<div class="flex gap-2 mb-6 flex-wrap">
    <?php foreach (['pending'=>'대기중','reviewing'=>'검토중','approved'=>'승인','rejected'=>'반려','all'=>'전체'] as $st_=>$label): ?>
    <a href="?status=<?= $st_ ?>" class="px-4 py-2 rounded-lg text-sm font-medium transition <?= $status===$st_?'bg-indigo-600 text-white':'bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 text-zinc-700 dark:text-zinc-300 hover:border-indigo-400' ?>">
        <?= $label ?>
        <?php if ($st_!=='all' && isset($statusCounts[$st_]) && $statusCounts[$st_]>0): ?>
        <span class="ml-1.5 px-1.5 py-0.5 text-[10px] rounded-full <?= $status===$st_?'bg-white/20 text-white':'bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400' ?>"><?= $statusCounts[$st_] ?></span>
        <?php endif; ?>
    </a>
    <?php endforeach; ?>
</div>

<?php if (empty($subs)): ?>
<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-16 text-center">
    <p class="text-zinc-400 dark:text-zinc-500">심사 항목이 없습니다.</p>
</div>
<?php else: ?>
<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-700">
            <tr>
                <th class="text-left px-4 py-3 text-zinc-500 dark:text-zinc-400 font-medium">아이템</th>
                <th class="text-left px-4 py-3 text-zinc-500 dark:text-zinc-400 font-medium">파트너</th>
                <th class="text-left px-4 py-3 text-zinc-500 dark:text-zinc-400 font-medium">타입</th>
                <th class="text-left px-4 py-3 text-zinc-500 dark:text-zinc-400 font-medium">버전</th>
                <th class="text-left px-4 py-3 text-zinc-500 dark:text-zinc-400 font-medium">상태</th>
                <th class="text-left px-4 py-3 text-zinc-500 dark:text-zinc-400 font-medium">제출일</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
        <?php foreach ($subs as $sub):
            $data = json_decode($sub['submitted_data']??'{}', true) ?: [];
            $nameArr = $data['name'] ?? [];
            $itemName = (is_array($nameArr) ? ($nameArr[$_mktLocale] ?? $nameArr['en'] ?? null) : $nameArr) ?: $sub['submitted_slug'] ?? '-';
            [$sl,$sc] = $statusMeta[$sub['status']] ?? ['?','bg-zinc-100 text-zinc-500'];
        ?>
        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30 transition">
            <td class="px-4 py-3">
                <div class="font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($itemName) ?></div>
                <div class="text-xs text-zinc-400 font-mono"><?= htmlspecialchars($sub['submitted_slug'] ?? '') ?></div>
                <?php if ($sub['is_update']): ?><span class="text-[10px] bg-blue-50 text-blue-600 px-1.5 py-0.5 rounded">업데이트</span><?php endif; ?>
            </td>
            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400"><?= htmlspecialchars($sub['partner_name'] ?? '-') ?></td>
            <td class="px-4 py-3"><span class="text-xs px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400"><?= $typeLabels[$sub['item_type']] ?? $sub['item_type'] ?></span></td>
            <td class="px-4 py-3 font-mono text-zinc-600 dark:text-zinc-400">v<?= htmlspecialchars($sub['submitted_version'] ?? '-') ?></td>
            <td class="px-4 py-3"><span class="px-2 py-0.5 text-xs rounded font-medium <?= $sc ?>"><?= $sl ?></span></td>
            <td class="px-4 py-3 text-zinc-500 dark:text-zinc-400"><?= htmlspecialchars(substr($sub['submitted_at']??'',0,10)) ?></td>
            <td class="px-4 py-3">
                <a href="<?= $adminUrl ?>/market/submissions/show?id=<?= $sub['id'] ?>" class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs font-medium">상세 →</a>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php if ($totalPages>1): ?>
<div class="mt-6 flex justify-center gap-1">
    <?php for ($p=max(1,$page-2);$p<=min($totalPages,$page+2);$p++): ?>
    <a href="?<?= http_build_query(array_merge($_GET,['page'=>$p])) ?>" class="px-3 py-2 rounded-lg border text-sm <?= $p===$page?'bg-indigo-600 border-indigo-600 text-white':'border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-400 hover:bg-zinc-100' ?>"><?= $p ?></a>
    <?php endfor; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../_foot.php'; ?>
