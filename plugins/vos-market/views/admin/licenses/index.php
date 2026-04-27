<?php
include __DIR__ . '/../_head.php';
$pageHeaderTitle = '라이선스 관리';
$db = mkt_pdo(); $pfx = $_mktPrefix;
$q     = trim($_GET['q'] ?? '');
$status = $_GET['status'] ?? '';
$page  = max(1,(int)($_GET['page']??1));
$perPage = 25; $offset = ($page-1)*$perPage;
$where=[]; $params=[];
if ($status) { $where[]="l.status=?"; $params[]=$status; }
if ($q) { $where[]="(l.license_key LIKE ? OR l.licensee_email LIKE ?)"; $params[]="%$q%"; $params[]="%$q%"; }
$ws = $where ? 'WHERE '.implode(' AND ',$where) : '';
$st=$db->prepare("SELECT COUNT(*) FROM {$pfx}mkt_licenses l $ws"); $st->execute($params); $total=(int)$st->fetchColumn();
$st2=$db->prepare("SELECT l.*,i.slug item_slug,i.name item_name FROM {$pfx}mkt_licenses l LEFT JOIN {$pfx}mkt_items i ON i.id=l.item_id $ws ORDER BY l.created_at DESC LIMIT $perPage OFFSET $offset"); $st2->execute($params); $licenses=$st2->fetchAll();
$totalPages=(int)ceil($total/$perPage);
$adminUrl = $_mktAdmin;
$statusMeta=['active'=>['활성','bg-green-100 text-green-700'],'expired'=>['만료','bg-zinc-100 text-zinc-500'],'suspended'=>['정지','bg-red-100 text-red-700'],'refunded'=>['환불','bg-yellow-100 text-yellow-700']];
?>
<div class="flex items-center justify-between mb-6">
    <div><h1 class="text-2xl font-bold text-zinc-900 dark:text-white">라이선스 관리</h1><p class="text-sm text-zinc-500 mt-0.5">전체 <?= number_format($total) ?>개</p></div>
</div>
<form method="GET" class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl p-4 mb-6 flex gap-3 flex-wrap items-end">
    <div class="flex-1 min-w-[200px]"><input type="text" name="q" value="<?= htmlspecialchars($q) ?>" placeholder="라이선스 키·이메일" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200"></div>
    <select name="status" class="px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm bg-white dark:bg-zinc-700 text-zinc-700 dark:text-zinc-300">
        <option value="">모든 상태</option>
        <?php foreach ($statusMeta as $v=>[$l]): ?><option value="<?= $v ?>" <?= $status===$v?'selected':'' ?>><?= $l ?></option><?php endforeach; ?>
    </select>
    <button class="px-4 py-2 bg-zinc-800 dark:bg-zinc-600 text-white rounded-lg text-sm">검색</button>
    <?php if ($status||$q): ?><a href="<?= $adminUrl ?>/market/licenses" class="px-3 py-2 text-sm text-zinc-500 hover:text-zinc-700">초기화</a><?php endif; ?>
</form>
<?php if (empty($licenses)): ?>
<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-16 text-center"><p class="text-zinc-400">라이선스가 없습니다.</p></div>
<?php else: ?>
<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-700">
            <tr>
                <th class="text-left px-4 py-3 text-zinc-500 font-medium">라이선스 키</th>
                <th class="text-left px-4 py-3 text-zinc-500 font-medium">아이템</th>
                <th class="text-left px-4 py-3 text-zinc-500 font-medium">사용자</th>
                <th class="text-left px-4 py-3 text-zinc-500 font-medium">활성화</th>
                <th class="text-left px-4 py-3 text-zinc-500 font-medium">만료일</th>
                <th class="text-left px-4 py-3 text-zinc-500 font-medium">상태</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
        <?php foreach ($licenses as $lic):
            [$sl,$sc] = $statusMeta[$lic['status']]??['?','bg-zinc-100 text-zinc-500'];
            $iname = mkt_locale_val($lic['item_name']??null, $_mktLocale) ?: ($lic['item_slug']??'');
        ?>
        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
            <td class="px-4 py-3 font-mono text-xs text-zinc-600 dark:text-zinc-400"><?= htmlspecialchars($lic['license_key']) ?></td>
            <td class="px-4 py-3 text-zinc-600 dark:text-zinc-400 text-xs"><?= htmlspecialchars($iname) ?></td>
            <td class="px-4 py-3">
                <div class="text-xs text-zinc-600 dark:text-zinc-400"><?= htmlspecialchars($lic['licensee_email']??'-') ?></div>
            </td>
            <td class="px-4 py-3 text-zinc-500 text-xs"><?= (int)$lic['activation_count'] ?>/<?= $lic['max_activations']===null?'∞':(int)$lic['max_activations'] ?></td>
            <td class="px-4 py-3 text-zinc-400 text-xs"><?= $lic['expires_at'] ? htmlspecialchars(substr($lic['expires_at'],0,10)) : '무제한' ?></td>
            <td class="px-4 py-3"><span class="px-2 py-0.5 text-xs rounded font-medium <?= $sc ?>"><?= $sl ?></span></td>
            <td class="px-4 py-3"><a href="<?= $adminUrl ?>/market/licenses/show?id=<?= $lic['id'] ?>" class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs">상세 →</a></td>
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
