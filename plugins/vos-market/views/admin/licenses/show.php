<?php
include __DIR__ . '/../_head.php';
$pageHeaderTitle = '라이선스 상세';
$db = mkt_pdo(); $pfx = $_mktPrefix;
$id = (int)($_GET['id'] ?? 0);
$st = $db->prepare("SELECT l.*,i.slug item_slug,i.name item_name,i.type item_type FROM {$pfx}mkt_licenses l LEFT JOIN {$pfx}mkt_items i ON i.id=l.item_id WHERE l.id=?");
$st->execute([$id]); $lic = $st->fetch();
if (!$lic) { echo '<p class="text-red-500">라이선스를 찾을 수 없습니다.</p>'; include __DIR__.'/../_foot.php'; return; }
$acts = $db->prepare("SELECT * FROM {$pfx}mkt_license_activations WHERE license_id=? ORDER BY activated_at DESC");
$acts->execute([$id]); $activations = $acts->fetchAll();
$csrf = $_SESSION['_csrf'] ?? '';
$adminUrl = $_mktAdmin;
$statusMeta=['active'=>['활성','bg-green-100 text-green-700'],'expired'=>['만료','bg-zinc-100 text-zinc-500'],'suspended'=>['정지','bg-red-100 text-red-700'],'refunded'=>['환불','bg-yellow-100 text-yellow-700']];
[$sl,$sc] = $statusMeta[$lic['status']]??['?','bg-zinc-100 text-zinc-500'];
$iname = mkt_locale_val($lic['item_name']??null, $_mktLocale) ?: ($lic['item_slug']??'-');
?>
<div class="flex items-center justify-between mb-6">
    <a href="<?= $adminUrl ?>/market/licenses" class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">← 목록</a>
    <span class="px-3 py-1 text-sm rounded font-medium <?= $sc ?>"><?= $sl ?></span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
<div class="lg:col-span-3 space-y-6">
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
        <h2 class="text-sm font-semibold text-zinc-500 uppercase tracking-wide mb-3">라이선스 키</h2>
        <p class="font-mono text-lg font-bold text-zinc-800 dark:text-zinc-200 bg-zinc-50 dark:bg-zinc-900 rounded-lg px-4 py-3 break-all"><?= htmlspecialchars($lic['license_key']) ?></p>
        <div class="grid grid-cols-2 md:grid-cols-4 gap-4 mt-4">
            <div><p class="text-xs text-zinc-500">아이템</p><p class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mt-1"><?= htmlspecialchars($iname) ?></p></div>
            <div><p class="text-xs text-zinc-500">활성화</p><p class="text-sm font-medium text-zinc-700 dark:text-zinc-300 mt-1"><?= (int)$lic['activation_count'] ?>/<?= $lic['max_activations']===null?'∞':(int)$lic['max_activations'] ?></p></div>
            <div><p class="text-xs text-zinc-500">구매일</p><p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1"><?= htmlspecialchars(substr($lic['created_at']??'',0,10)) ?></p></div>
            <div><p class="text-xs text-zinc-500">만료일</p><p class="text-sm text-zinc-600 dark:text-zinc-400 mt-1"><?= $lic['expires_at'] ? htmlspecialchars(substr($lic['expires_at'],0,10)) : '무제한' ?></p></div>
        </div>
    </div>

    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700">
            <h3 class="font-semibold text-zinc-700 dark:text-zinc-300">활성화 내역</h3>
        </div>
        <?php if (empty($activations)): ?>
        <p class="p-6 text-sm text-zinc-400">활성화 내역이 없습니다.</p>
        <?php else: ?>
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50">
                <tr>
                    <th class="text-left px-4 py-3 text-zinc-500 font-medium">도메인</th>
                    <th class="text-left px-4 py-3 text-zinc-500 font-medium">IP</th>
                    <th class="text-left px-4 py-3 text-zinc-500 font-medium">활성화일</th>
                    <th class="text-left px-4 py-3 text-zinc-500 font-medium">상태</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
            <?php foreach ($activations as $act): ?>
            <tr>
                <td class="px-4 py-3 font-mono text-xs text-zinc-600 dark:text-zinc-400"><?= htmlspecialchars($act['domain']??'-') ?></td>
                <td class="px-4 py-3 font-mono text-xs text-zinc-400"><?= htmlspecialchars($act['ip_address']??'-') ?></td>
                <td class="px-4 py-3 text-xs text-zinc-400"><?= htmlspecialchars(substr($act['activated_at']??'',0,16)) ?></td>
                <td class="px-4 py-3"><span class="px-2 py-0.5 text-xs rounded <?= $act['is_active'] ? 'bg-green-100 text-green-700' : 'bg-zinc-100 text-zinc-500' ?>"><?= $act['is_active'] ? '활성' : '비활성' ?></span></td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
        <?php endif; ?>
    </div>
</div>

<div class="space-y-4">
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
        <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-3">사용자 정보</h3>
        <p class="font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($lic['licensee_name']??'-') ?></p>
        <p class="text-xs text-zinc-500"><?= htmlspecialchars($lic['licensee_email']??'') ?></p>
        <?php if ($lic['order_id']): ?>
        <a href="<?= $adminUrl ?>/market/orders/show?id=<?= $lic['order_id'] ?>" class="mt-2 inline-block text-xs text-indigo-600 dark:text-indigo-400 hover:underline">주문 상세 →</a>
        <?php endif; ?>
    </div>
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
        <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-3">상태 변경</h3>
        <div class="space-y-2">
            <?php foreach ($statusMeta as $sv => [$sl2]): ?>
            <?php if ($sv !== $lic['status']): ?>
            <button onclick="changeStatus(<?= $id ?>,'<?= $sv ?>')"
                class="w-full px-3 py-2 text-sm rounded-lg border border-zinc-200 dark:border-zinc-600 hover:bg-zinc-50 dark:hover:bg-zinc-700 text-zinc-700 dark:text-zinc-300 transition text-left">
                → <?= $sl2 ?>로 변경
            </button>
            <?php endif; ?>
            <?php endforeach; ?>
            <button onclick="resetActivations(<?= $id ?>)"
                class="w-full px-3 py-2 text-sm rounded-lg border border-red-200 dark:border-red-800 hover:bg-red-50 dark:hover:bg-red-900/20 text-red-600 dark:text-red-400 transition text-left">
                활성화 초기화
            </button>
        </div>
    </div>
</div>
</div>

<script>
const CSRF = <?= json_encode($csrf) ?>;
const API_URL = <?= json_encode($adminUrl.'/market/licenses/api') ?>;
async function changeStatus(id, status) {
    if (!confirm('상태를 변경하시겠습니까?')) return;
    const fd = new FormData(); fd.append('action','status'); fd.append('id',id); fd.append('status',status); fd.append('_token',CSRF);
    const r = await fetch(API_URL,{method:'POST',body:fd}); const d = await r.json();
    if (d.ok) location.reload(); else alert(d.msg);
}
async function resetActivations(id) {
    if (!confirm('모든 활성화를 초기화하시겠습니까?')) return;
    const fd = new FormData(); fd.append('action','reset_activations'); fd.append('id',id); fd.append('_token',CSRF);
    const r = await fetch(API_URL,{method:'POST',body:fd}); const d = await r.json();
    if (d.ok) location.reload(); else alert(d.msg);
}
</script>
<?php include __DIR__ . '/../_foot.php'; ?>
