<?php
include __DIR__ . '/../_head.php';
$pageHeaderTitle = '파트너 상세';
$db = mkt_pdo(); $pfx = $_mktPrefix;
$id = (int)($_GET['id'] ?? 0);
$st = $db->prepare("SELECT * FROM {$pfx}mkt_partners WHERE id=?");
$st->execute([$id]); $partner = $st->fetch();
if (!$partner) { echo '<p class="text-red-500">파트너를 찾을 수 없습니다.</p>'; include __DIR__.'/../_foot.php'; return; }
$csrf = $_SESSION['_csrf'] ?? '';
$adminUrl = $_mktAdmin;
$statusMeta = ['pending'=>['심사중','bg-yellow-100 text-yellow-700'],'active'=>['활성','bg-green-100 text-green-700'],'suspended'=>['정지','bg-red-100 text-red-700'],'rejected'=>['반려','bg-zinc-100 text-zinc-500']];
[$sl,$sc] = $statusMeta[$partner['status']] ?? ['?','bg-zinc-100 text-zinc-500'];
$typeLabel = ['general'=>'일반','verified'=>'인증','partner'=>'파트너'][$partner['type']] ?? $partner['type'];

$items = $db->prepare("SELECT id,slug,type,name,status,latest_version,created_at FROM {$pfx}mkt_items WHERE partner_id=? ORDER BY created_at DESC LIMIT 20");
$items->execute([$id]); $items = $items->fetchAll();

$earnings = $db->prepare("SELECT SUM(amount) total, SUM(CASE WHEN status='paid' THEN amount ELSE 0 END) paid FROM {$pfx}mkt_partner_earnings WHERE partner_id=?");
$earnings->execute([$id]); $earnRow = $earnings->fetch();
?>
<div class="flex items-center justify-between mb-6">
    <a href="<?= $adminUrl ?>/market/partners" class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">← 목록</a>
    <span class="px-3 py-1 text-sm rounded font-medium <?= $sc ?>"><?= $sl ?></span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
<div class="lg:col-span-3 space-y-6">
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
        <div class="flex items-start justify-between mb-4">
            <div>
                <h2 class="text-xl font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($partner['display_name']) ?></h2>
                <p class="text-sm text-zinc-500"><?= htmlspecialchars($partner['email']) ?></p>
                <?php if ($partner['slug']): ?><p class="text-xs font-mono text-zinc-400 mt-1">@<?= htmlspecialchars($partner['slug']) ?></p><?php endif; ?>
            </div>
            <span class="text-xs px-2 py-1 rounded bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400"><?= $typeLabel ?></span>
        </div>
        <div class="grid grid-cols-3 gap-4 pt-4 border-t border-zinc-200 dark:border-zinc-700">
            <div class="text-center">
                <p class="text-2xl font-bold text-zinc-800 dark:text-zinc-200"><?= number_format((int)$partner['item_count']) ?></p>
                <p class="text-xs text-zinc-500 mt-1">아이템</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-zinc-800 dark:text-zinc-200"><?= number_format((float)($earnRow['total']??0)) ?></p>
                <p class="text-xs text-zinc-500 mt-1">총수익</p>
            </div>
            <div class="text-center">
                <p class="text-2xl font-bold text-zinc-800 dark:text-zinc-200"><?= number_format((float)$partner['pending_balance']) ?></p>
                <p class="text-xs text-zinc-500 mt-1">미지급 잔액</p>
            </div>
        </div>
    </div>

    <?php if ($partner['bio']): ?>
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
        <h3 class="font-semibold text-zinc-700 dark:text-zinc-300 mb-2">소개</h3>
        <p class="text-sm text-zinc-600 dark:text-zinc-400 whitespace-pre-line"><?= htmlspecialchars($partner['bio']) ?></p>
    </div>
    <?php endif; ?>

    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <h3 class="font-semibold text-zinc-700 dark:text-zinc-300">아이템 목록</h3>
        </div>
        <?php if (empty($items)): ?>
        <p class="p-6 text-sm text-zinc-400">등록된 아이템이 없습니다.</p>
        <?php else: ?>
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                <tr>
                    <th class="text-left px-4 py-3 text-zinc-500 font-medium">아이템</th>
                    <th class="text-left px-4 py-3 text-zinc-500 font-medium">타입</th>
                    <th class="text-left px-4 py-3 text-zinc-500 font-medium">버전</th>
                    <th class="text-left px-4 py-3 text-zinc-500 font-medium">상태</th>
                    <th class="px-4 py-3"></th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
            <?php foreach ($items as $item):
                $iname = mkt_locale_val($item['name'], $_mktLocale);
                $istatus = ['active'=>['활성','bg-green-100 text-green-700'],'inactive'=>['비활성','bg-zinc-100 text-zinc-500'],'draft'=>['임시','bg-yellow-100 text-yellow-700']][$item['status']]??['?','bg-zinc-100'];
            ?>
            <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                <td class="px-4 py-3">
                    <div class="font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($iname ?: $item['slug']) ?></div>
                    <div class="text-xs text-zinc-400 font-mono"><?= htmlspecialchars($item['slug']) ?></div>
                </td>
                <td class="px-4 py-3 text-zinc-500 text-xs"><?= $item['type'] ?></td>
                <td class="px-4 py-3 font-mono text-zinc-500 text-xs">v<?= htmlspecialchars($item['latest_version']??'') ?></td>
                <td class="px-4 py-3"><span class="px-2 py-0.5 text-xs rounded <?= $istatus[1] ?>"><?= $istatus[0] ?></span></td>
                <td class="px-4 py-3"><a href="<?= $adminUrl ?>/market/items/show?id=<?= $item['id'] ?>" class="text-indigo-600 dark:text-indigo-400 hover:underline text-xs">상세 →</a></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<div class="space-y-4">
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
        <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-3">계정 정보</h3>
        <dl class="space-y-2 text-sm">
            <div class="flex justify-between"><dt class="text-zinc-500">수수료율</dt><dd class="font-medium text-zinc-800 dark:text-zinc-200"><?= $partner['commission_rate'] ?>%</dd></div>
            <div class="flex justify-between"><dt class="text-zinc-500">가입일</dt><dd class="text-zinc-600 dark:text-zinc-400"><?= htmlspecialchars(substr($partner['created_at']??'',0,10)) ?></dd></div>
            <?php if ($partner['website_url']): ?>
            <div class="flex justify-between"><dt class="text-zinc-500">웹사이트</dt><dd><a href="<?= htmlspecialchars($partner['website_url']) ?>" target="_blank" class="text-indigo-600 text-xs hover:underline">방문 →</a></dd></div>
            <?php endif; ?>
        </dl>
    </div>

    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5" x-data="{}">
        <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-3">상태 변경</h3>
        <div class="space-y-2">
            <?php foreach ($statusMeta as $sv => [$sl2, $sc2]): ?>
            <?php if ($sv !== $partner['status']): ?>
            <button onclick="changeStatus(<?= $id ?>, '<?= $sv ?>')"
                class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 transition text-left">
                → <?= $sl2 ?>로 변경
            </button>
            <?php endif; ?>
            <?php endforeach; ?>
        </div>
    </div>

    <?php if ($partner['bank_info'] || $partner['tax_info']): ?>
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
        <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-3">정산 정보</h3>
        <?php if ($partner['bank_info']): ?>
        <pre class="text-xs text-zinc-600 dark:text-zinc-400 bg-zinc-50 dark:bg-zinc-900 rounded p-3 overflow-x-auto"><?= htmlspecialchars(json_encode(json_decode($partner['bank_info']),JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) ?></pre>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>
</div>

<script>
const CSRF = <?= json_encode($csrf) ?>;
const API_URL = <?= json_encode($adminUrl.'/market/partners/api') ?>;
async function changeStatus(id, status) {
    if (!confirm('상태를 변경하시겠습니까?')) return;
    const fd = new FormData();
    fd.append('action','status'); fd.append('id',id); fd.append('status',status); fd.append('_token',CSRF);
    const r = await fetch(API_URL, {method:'POST',body:fd});
    const d = await r.json();
    if (d.ok) location.reload(); else alert(d.msg);
}
</script>
<?php include __DIR__ . '/../_foot.php'; ?>
