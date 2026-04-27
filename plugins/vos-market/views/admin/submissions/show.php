<?php
include __DIR__ . '/../_head.php';
$pageHeaderTitle = '심사 상세';
$db = mkt_pdo(); $pfx = $_mktPrefix;
$id = (int)($_GET['id'] ?? 0);
$st = $db->prepare("SELECT s.*,p.display_name partner_name,p.email partner_email FROM {$pfx}mkt_submissions s LEFT JOIN {$pfx}mkt_partners p ON p.id=s.partner_id WHERE s.id=?");
$st->execute([$id]); $sub = $st->fetch();
if (!$sub) { echo '<p class="text-red-500">심사 항목을 찾을 수 없습니다.</p>'; include __DIR__.'/../_foot.php'; return; }
$data = json_decode($sub['submitted_data']??'{}',true)?:[];
$nameArr = $data['name']??[];
$itemName = is_array($nameArr)?($nameArr[$_mktLocale]??$nameArr['en']??''):($nameArr??'');
$csrf = $_SESSION['_csrf'] ?? '';
$adminUrl = $_mktAdmin;
$typeLabels = ['plugin'=>'플러그인','theme'=>'테마','widget'=>'위젯','skin'=>'스킨'];
$statusMeta = ['pending'=>['대기중','bg-yellow-100 text-yellow-700'],'reviewing'=>['검토중','bg-blue-100 text-blue-700'],'approved'=>['승인','bg-green-100 text-green-700'],'rejected'=>['반려','bg-red-100 text-red-700']];
[$sl,$sc] = $statusMeta[$sub['status']]??['?','bg-zinc-100 text-zinc-500'];
?>
<div class="flex items-center justify-between mb-6">
    <a href="<?= $adminUrl ?>/market/submissions" class="text-sm text-zinc-500 hover:text-zinc-700 dark:hover:text-zinc-300">← 목록</a>
    <span class="px-3 py-1 text-sm rounded font-medium <?= $sc ?>"><?= $sl ?></span>
</div>

<div class="grid grid-cols-1 lg:grid-cols-4 gap-6">
<div class="lg:col-span-3 space-y-6">
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
        <h2 class="text-lg font-bold text-zinc-900 dark:text-white mb-1"><?= htmlspecialchars($itemName ?: $sub['submitted_slug']) ?></h2>
        <div class="flex gap-2 mb-4 flex-wrap">
            <span class="text-xs px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400"><?= $typeLabels[$sub['item_type']]??$sub['item_type'] ?></span>
            <span class="text-xs font-mono px-2 py-0.5 rounded bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400">v<?= htmlspecialchars($sub['submitted_version']??'') ?></span>
            <?php if ($sub['is_update']): ?><span class="text-xs px-2 py-0.5 rounded bg-blue-50 text-blue-600">업데이트</span><?php endif; ?>
        </div>
        <?php if (!empty($data['short_description'])): $sd = is_array($data['short_description'])?($data['short_description'][$_mktLocale]??$data['short_description']['en']??''):$data['short_description']; ?>
        <p class="text-zinc-600 dark:text-zinc-400 text-sm mb-4"><?= htmlspecialchars($sd) ?></p>
        <?php endif; ?>
        <?php if (!empty($data['description'])): $desc = is_array($data['description'])?($data['description'][$_mktLocale]??$data['description']['en']??''):$data['description']; ?>
        <div class="text-sm text-zinc-700 dark:text-zinc-300 border-t border-zinc-200 dark:border-zinc-700 pt-4 whitespace-pre-line"><?= htmlspecialchars($desc) ?></div>
        <?php endif; ?>
    </div>

    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6">
        <h3 class="font-semibold text-zinc-700 dark:text-zinc-300 mb-3">제출 데이터</h3>
        <pre class="text-xs text-zinc-600 dark:text-zinc-400 bg-zinc-50 dark:bg-zinc-900 rounded-lg p-4 overflow-x-auto"><?= htmlspecialchars(json_encode($data, JSON_PRETTY_PRINT|JSON_UNESCAPED_UNICODE)) ?></pre>
    </div>
</div>

<div class="space-y-4">
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5">
        <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-3">파트너</h3>
        <p class="font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($sub['partner_name']??'-') ?></p>
        <p class="text-xs text-zinc-500"><?= htmlspecialchars($sub['partner_email']??'') ?></p>
        <p class="text-xs text-zinc-400 mt-2"><?= htmlspecialchars(substr($sub['submitted_at']??'',0,16)) ?></p>
    </div>

    <?php if ($sub['status'] === 'pending' || $sub['status'] === 'reviewing'): ?>
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-5" x-data="{ rejectMode: false }">
        <h3 class="text-xs font-semibold text-zinc-500 uppercase tracking-wide mb-3">심사 처리</h3>
        <button onclick="approveItem(<?= $id ?>)"
            class="w-full mb-2 px-4 py-2.5 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm font-medium transition">
            ✓ 승인
        </button>
        <button x-on:click="rejectMode=!rejectMode"
            class="w-full px-4 py-2.5 bg-red-50 dark:bg-red-900/20 hover:bg-red-100 text-red-600 dark:text-red-400 rounded-lg text-sm font-medium transition border border-red-200 dark:border-red-800">
            ✕ 반려
        </button>
        <div x-show="rejectMode" x-cloak class="mt-3 space-y-2">
            <textarea id="reject-reason" rows="3" placeholder="반려 사유 (파트너에게 표시)"
                class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm bg-white dark:bg-zinc-700 text-zinc-800 dark:text-zinc-200 resize-none focus:ring-2 focus:ring-red-500"></textarea>
            <button onclick="rejectItem(<?= $id ?>)" class="w-full px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm font-medium transition">반려 확정</button>
        </div>
    </div>
    <?php endif; ?>

    <?php if ($sub['rejection_reason']): ?>
    <div class="bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-800 p-4">
        <p class="text-xs font-semibold text-red-600 dark:text-red-400 mb-1">반려 사유</p>
        <p class="text-sm text-red-700 dark:text-red-300"><?= htmlspecialchars($sub['rejection_reason']) ?></p>
    </div>
    <?php endif; ?>
</div>
</div>

<script>
const CSRF = <?= json_encode($csrf) ?>;
const API_URL = <?= json_encode($adminUrl.'/market/submissions/api') ?>;

async function approveItem(id) {
    if (!confirm('승인하시겠습니까?')) return;
    const fd = new FormData();
    fd.append('action','approve'); fd.append('id',id); fd.append('_token',CSRF);
    const r = await fetch(API_URL, {method:'POST',body:fd});
    const d = await r.json();
    if (d.ok) location.href = <?= json_encode($adminUrl.'/market/submissions') ?>;
    else alert(d.msg);
}
async function rejectItem(id) {
    const reason = document.getElementById('reject-reason').value.trim();
    if (!reason) { alert('반려 사유를 입력하세요'); return; }
    const fd = new FormData();
    fd.append('action','reject'); fd.append('id',id); fd.append('_token',CSRF); fd.append('reason',reason);
    const r = await fetch(API_URL, {method:'POST',body:fd});
    const d = await r.json();
    if (d.ok) location.reload();
    else alert(d.msg);
}
</script>

<?php include __DIR__ . '/../_foot.php'; ?>
