<?php
$pageHeaderTitle = '설치 현황 추적 - 상세';
include __DIR__ . '/../_head.php';
$db = mkt_pdo(); $pfx = $_mktPrefix;

$domain = trim($_GET['domain']  ?? '');
$vosKey = trim($_GET['vos_key'] ?? '');

if (!$domain || !$vosKey) {
    echo '<p class="text-red-500">domain과 vos_key가 필요합니다.</p>';
    include __DIR__ . '/../_foot.php';
    exit;
}

// 해당 도메인의 아이템 목록
$stRows = $db->prepare("
    SELECT r.*, i.name AS item_name, i.slug AS item_slug_db, i.type AS item_type
      FROM {$pfx}mkt_sync_reports r
      LEFT JOIN {$pfx}mkt_items i ON i.id = r.item_id
     WHERE r.domain = ? AND r.vos_key = ?
     ORDER BY r.status ASC, r.slug ASC
");
$stRows->execute([$domain, $vosKey]);
$rows = $stRows->fetchAll();

// 도메인 요약
$summary = [
    'total'       => count($rows),
    'licensed'    => 0,
    'unlicensed'  => 0,
    'unknown'     => 0,
    'first_seen'  => null,
    'last_seen'   => null,
];
foreach ($rows as $r) {
    if ($r['status'] === 'licensed')        $summary['licensed']++;
    elseif ($r['status'] === 'unlicensed')  $summary['unlicensed']++;
    elseif ($r['status'] === 'unknown_product') $summary['unknown']++;
    if (!$summary['first_seen'] || $r['first_seen_at'] < $summary['first_seen']) $summary['first_seen'] = $r['first_seen_at'];
    if (!$summary['last_seen']  || $r['last_seen_at']  > $summary['last_seen'])  $summary['last_seen']  = $r['last_seen_at'];
}

$adminUrl = $_mktAdmin;

$statusMeta = [
    'licensed'        => ['인증됨',    'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'],
    'unlicensed'      => ['미인증',    'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'],
    'unknown_product' => ['알 수 없음','bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'],
];

$typeLabels = ['plugin' => '플러그인', 'widget' => '위젯', 'layout' => '레이아웃', 'theme' => '테마', 'skin' => '스킨'];
?>

<!-- 브레드크럼 + 헤더 -->
<div class="mb-5">
    <a href="<?= $adminUrl ?>/market/sync" class="text-sm text-zinc-500 hover:text-indigo-600 dark:text-zinc-400">← 도메인 목록</a>
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white mt-2"><?= htmlspecialchars($domain) ?></h1>
    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1 font-mono"><?= htmlspecialchars($vosKey) ?></p>
</div>

<!-- 요약 카드 -->
<div class="grid grid-cols-2 md:grid-cols-5 gap-3 mb-6">
    <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl p-4">
        <div class="text-xs text-zinc-500">전체 아이템</div>
        <div class="text-2xl font-bold text-zinc-800 dark:text-zinc-200 mt-1"><?= number_format($summary['total']) ?></div>
    </div>
    <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl p-4">
        <div class="text-xs text-zinc-500">인증됨</div>
        <div class="text-2xl font-bold text-green-600 dark:text-green-400 mt-1"><?= number_format($summary['licensed']) ?></div>
    </div>
    <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl p-4">
        <div class="text-xs text-zinc-500">미인증</div>
        <div class="text-2xl font-bold text-red-600 dark:text-red-400 mt-1"><?= number_format($summary['unlicensed']) ?></div>
    </div>
    <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl p-4">
        <div class="text-xs text-zinc-500">알 수 없음</div>
        <div class="text-2xl font-bold text-yellow-600 dark:text-yellow-400 mt-1"><?= number_format($summary['unknown']) ?></div>
    </div>
    <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl p-4">
        <div class="text-xs text-zinc-500">마지막 확인</div>
        <div class="text-sm font-mono text-zinc-700 dark:text-zinc-300 mt-2"><?= htmlspecialchars(substr($summary['last_seen'] ?? '-', 0, 16)) ?></div>
    </div>
</div>

<!-- 아이템 목록 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
    <table class="w-full text-sm">
        <thead class="bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-700">
            <tr>
                <th class="text-left px-4 py-3 text-zinc-500 dark:text-zinc-400 font-medium">아이템</th>
                <th class="text-left px-4 py-3 text-zinc-500 dark:text-zinc-400 font-medium">타입</th>
                <th class="text-left px-4 py-3 text-zinc-500 dark:text-zinc-400 font-medium">버전</th>
                <th class="text-left px-4 py-3 text-zinc-500 dark:text-zinc-400 font-medium">상태</th>
                <th class="text-left px-4 py-3 text-zinc-500 dark:text-zinc-400 font-medium">마지막 확인</th>
                <th class="px-4 py-3"></th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
        <?php foreach ($rows as $row):
            [$statusLabel, $statusClass] = $statusMeta[$row['status']] ?? ['?', 'bg-zinc-100 text-zinc-500'];
            $displayName = mkt_locale_val($row['item_name'] ?? null, $_mktLocale) ?: ($row['item_slug_db'] ?? $row['slug'] ?? '-');
            $displayType = $row['item_type'] ?? '-';
        ?>
        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
            <td class="px-4 py-3">
                <div class="font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($displayName) ?></div>
                <div class="text-[11px] font-mono text-zinc-400 dark:text-zinc-500 mt-0.5"><?= htmlspecialchars($row['slug']) ?></div>
            </td>
            <td class="px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400">
                <?= htmlspecialchars($typeLabels[$displayType] ?? $displayType) ?>
            </td>
            <td class="px-4 py-3 text-xs text-zinc-500 dark:text-zinc-400 font-mono">
                <?= htmlspecialchars($row['version'] ?? '-') ?>
            </td>
            <td class="px-4 py-3">
                <span class="px-2 py-0.5 text-[11px] font-medium rounded <?= $statusClass ?>"><?= $statusLabel ?></span>
            </td>
            <td class="px-4 py-3 text-xs text-zinc-400 dark:text-zinc-500 whitespace-nowrap">
                <?= htmlspecialchars(substr($row['last_seen_at'], 0, 16)) ?>
            </td>
            <td class="px-4 py-3 text-right whitespace-nowrap">
                <?php if ($row['status'] === 'unlicensed' && $row['item_id']): ?>
                <button onclick="issueLicense(<?= $row['id'] ?>, <?= (int)$row['item_id'] ?>)"
                        class="px-2.5 py-1 bg-indigo-600 hover:bg-indigo-700 text-white text-[11px] font-medium rounded">
                    라이선스 발급
                </button>
                <?php elseif ($row['status'] === 'unknown_product'): ?>
                <button onclick="registerToCatalog(<?= $row['id'] ?>, '<?= htmlspecialchars($row['slug']) ?>', '<?= htmlspecialchars($displayType) ?>')"
                        class="px-2.5 py-1 bg-yellow-500 hover:bg-yellow-600 text-white text-[11px] font-medium rounded">
                    카탈로그 등록
                </button>
                <button onclick="dismissReport(<?= $row['id'] ?>)"
                        class="ml-1 px-2 py-1 border border-zinc-300 dark:border-zinc-600 text-zinc-500 hover:bg-zinc-100 dark:hover:bg-zinc-700 text-[11px] rounded">
                    무시
                </button>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        <?php if (empty($rows)): ?>
        <tr><td colspan="6" class="px-4 py-12 text-center text-zinc-400">데이터가 없습니다.</td></tr>
        <?php endif; ?>
        </tbody>
    </table>
</div>

<p class="text-xs text-zinc-500 dark:text-zinc-400 mt-4">
    최초 감지: <?= htmlspecialchars(substr($summary['first_seen'] ?? '-', 0, 16)) ?>
</p>

<!-- 카탈로그 등록 모달 -->
<div id="registerModal" class="fixed inset-0 bg-black/50 hidden items-center justify-center z-50">
    <div class="bg-white dark:bg-zinc-800 rounded-xl p-6 max-w-md w-full mx-4">
        <h3 class="text-lg font-bold text-zinc-900 dark:text-white mb-4">마켓 카탈로그에 등록</h3>
        <div class="space-y-3">
            <div>
                <label class="block text-sm text-zinc-600 dark:text-zinc-400 mb-1">슬러그</label>
                <input type="text" id="regSlug" readonly class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 bg-zinc-100 dark:bg-zinc-700 rounded text-sm font-mono">
            </div>
            <div>
                <label class="block text-sm text-zinc-600 dark:text-zinc-400 mb-1">타입</label>
                <select id="regType" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 rounded text-sm">
                    <option value="plugin">플러그인</option>
                    <option value="widget">위젯</option>
                    <option value="layout">레이아웃</option>
                    <option value="theme">테마</option>
                    <option value="skin">스킨</option>
                </select>
            </div>
            <div>
                <label class="block text-sm text-zinc-600 dark:text-zinc-400 mb-1">이름 (ko)</label>
                <input type="text" id="regName" placeholder="표시 이름" class="w-full px-3 py-2 border border-zinc-300 dark:border-zinc-600 bg-white dark:bg-zinc-700 rounded text-sm">
            </div>
            <label class="flex items-center gap-2 text-sm text-zinc-600 dark:text-zinc-400">
                <input type="checkbox" id="regIssue" checked>
                등록 후 이 도메인에 라이선스 자동 발급
            </label>
        </div>
        <div class="mt-5 flex gap-2 justify-end">
            <button onclick="closeModal()" class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-400 rounded-lg text-sm hover:bg-zinc-100 dark:hover:bg-zinc-700">취소</button>
            <button onclick="confirmRegister()" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium">등록</button>
        </div>
    </div>
</div>

<script>
const CSRF      = <?= json_encode($_SESSION['_csrf'] ?? '') ?>;
const ADMIN_URL = <?= json_encode($adminUrl) ?>;
const DOMAIN    = <?= json_encode($domain) ?>;
const VOS_KEY   = <?= json_encode($vosKey) ?>;

let pendingReportId = 0;

async function postApi(action, data) {
    const fd = new FormData();
    fd.append('_csrf', CSRF);
    fd.append('action', action);
    for (const [k, v] of Object.entries(data)) fd.append(k, v);
    const r = await fetch(ADMIN_URL + '/market/sync/api', { method: 'POST', body: fd });
    return r.json();
}

async function issueLicense(reportId, itemId) {
    if (!confirm('이 도메인에 라이선스를 발급하시겠습니까?')) return;
    const d = await postApi('issue_from_sync', { report_id: reportId, domain: DOMAIN, vos_key: VOS_KEY, item_id: itemId });
    if (d.ok) { alert('라이선스 발급: ' + d.license_key); location.reload(); }
    else alert('오류: ' + (d.message || 'Unknown'));
}

function registerToCatalog(reportId, slug, type) {
    pendingReportId = reportId;
    document.getElementById('regSlug').value = slug;
    document.getElementById('regType').value = ['plugin','widget','layout','theme','skin'].includes(type) ? type : 'plugin';
    document.getElementById('regName').value = slug;
    document.getElementById('regIssue').checked = true;
    document.getElementById('registerModal').classList.remove('hidden');
    document.getElementById('registerModal').classList.add('flex');
}
function closeModal() {
    document.getElementById('registerModal').classList.add('hidden');
    document.getElementById('registerModal').classList.remove('flex');
}
async function confirmRegister() {
    const slug = document.getElementById('regSlug').value;
    const type = document.getElementById('regType').value;
    const name = document.getElementById('regName').value.trim() || slug;
    const issue = document.getElementById('regIssue').checked ? '1' : '0';
    const d = await postApi('register_from_sync', {
        report_id: pendingReportId, domain: DOMAIN, vos_key: VOS_KEY,
        slug, type, name, issue_license: issue,
    });
    if (d.ok) { alert('등록 완료' + (d.license_key ? '\n라이선스: ' + d.license_key : '')); location.reload(); }
    else alert('오류: ' + (d.message || 'Unknown'));
}
async function dismissReport(reportId) {
    if (!confirm('이 항목을 목록에서 제거하시겠습니까?')) return;
    const d = await postApi('dismiss_report', { report_id: reportId });
    if (d.ok) location.reload();
    else alert('오류: ' + (d.message || 'Unknown'));
}
</script>

<?php include __DIR__ . '/../_foot.php'; ?>
