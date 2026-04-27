<?php
include __DIR__ . '/../_head.php';
$pageHeaderTitle = '파트너 관리';
$db = mkt_pdo(); $pfx = $_mktPrefix;
$status = $_GET['status'] ?? '';
$q      = trim($_GET['q'] ?? '');
$page   = max(1,(int)($_GET['page']??1));
$perPage = 20; $offset = ($page-1)*$perPage;
$where=[]; $params=[];
if ($status) { $where[]="status=?"; $params[]=$status; }
if ($q) { $where[]="(email LIKE ? OR display_name LIKE ?)"; $params[]="%$q%"; $params[]="%$q%"; }
$ws = $where?'WHERE '.implode(' AND ',$where):'';
$st=$db->prepare("SELECT COUNT(*) FROM {$pfx}mkt_partners $ws"); $st->execute($params); $total=(int)$st->fetchColumn();
$st2=$db->prepare("SELECT * FROM {$pfx}mkt_partners $ws ORDER BY created_at DESC LIMIT $perPage OFFSET $offset"); $st2->execute($params); $partners=$st2->fetchAll();
$totalPages=(int)ceil($total/$perPage);
$statusMeta=['pending'=>['심사중','bg-yellow-100 text-yellow-700'],'active'=>['활성','bg-green-100 text-green-700'],'suspended'=>['정지','bg-red-100 text-red-700'],'rejected'=>['반려','bg-zinc-100 text-zinc-500']];
$adminUrl=$_mktAdmin;
?>
<div class="flex items-center justify-between mb-6">
    <div><h1 class="text-2xl font-bold text-zinc-900 dark:text-white">파트너 관리</h1><p class="text-sm text-zinc-500 mt-0.5">전체 <?= number_format($total) ?>명</p></div>
</div>
<form method="GET" class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl p-4 mb-6 flex gap-3 flex-wrap items-end">
    <div class="flex-1 min-w-[200px]"><input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="이름·이메일 검색" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200"></div>
    <select name="status" class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm bg-white dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300">
        <option value="">모든 상태</option>
        <?php foreach ($statusMeta as $v=>[$l]): ?><option value="<?= $v ?>" <?= $status===$v?'selected':'' ?>><?= $l ?></option><?php endforeach; ?>
    </select>
    <button class="px-4 py-2 bg-zinc-800 dark:bg-zinc-600 text-white rounded-lg text-sm">검색</button>
    <?php if ($status||$q): ?><a href="<?= $adminUrl ?>/market/partners" class="px-3 py-2 text-sm text-zinc-500 hover:text-zinc-700">초기화</a><?php endif; ?>
</form>
<?php if (empty($partners)): ?>
<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-16 text-center"><p class="text-zinc-400">파트너가 없습니다.</p></div>
<?php else: ?>
<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-700">
            <tr>
                <th class="text-left px-4 py-3 text-zinc-500 font-medium">파트너</th>
                <th class="text-left px-4 py-3 text-zinc-500 font-medium">타입</th>
                <th class="text-left px-4 py-3 text-zinc-500 font-medium">아이템</th>
                <th class="text-left px-4 py-3 text-zinc-500 font-medium">총수익</th>
                <th class="text-left px-4 py-3 text-zinc-500 font-medium">상태</th>
                <th class="text-left px-4 py-3 text-zinc-500 font-medium">가입일</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
        <?php foreach ($partners as $p_):
            [$sl,$sc] = $statusMeta[$p_['status']]??['?','bg-zinc-100 text-zinc-500'];
            $typeLabel = ['general'=>'일반','verified'=>'인증','partner'=>'파트너'][$p_['type']]??$p_['type'];
        ?>
        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
            <td class="px-4 py-3">
                <div class="font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($p_['display_name']) ?></div>
                <div class="text-xs text-zinc-400"><?= htmlspecialchars($p_['email']) ?></div>
            </td>
            <td class="px-4 py-3"><span class="text-xs px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400"><?= $typeLabel ?></span></td>
            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400"><?= number_format((int)$p_['item_count']) ?></td>
            <td class="px-4 py-3 font-mono text-zinc-600 dark:text-zinc-400"><?= number_format((float)$p_['total_earnings']) ?></td>
            <td class="px-4 py-3"><span class="px-2 py-0.5 text-xs rounded font-medium <?= $sc ?>"><?= $sl ?></span></td>
            <td class="px-4 py-3 text-zinc-400 text-xs"><?= htmlspecialchars(substr($p_['created_at']??'',0,10)) ?></td>
            <td class="px-4 py-3"><a href="<?= $adminUrl ?>/market/partners/show?id=<?= $p_['id'] ?>" class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs">상세 →</a></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<?php endif; ?>
<?php include __DIR__ . '/../_foot.php'; ?>
