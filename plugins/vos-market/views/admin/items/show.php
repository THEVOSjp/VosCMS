<?php
include __DIR__ . '/../_head.php';
$pageHeaderTitle = '아이템 상세';
$db = mkt_pdo(); $pfx = $_mktPrefix;

$id = (int)($_GET['id'] ?? 0);
$st = $db->prepare("
    SELECT i.*, p.display_name AS partner_name, p.email AS partner_email
      FROM {$pfx}mkt_items i
      LEFT JOIN {$pfx}mkt_partners p ON p.id = i.partner_id
     WHERE i.id = ?
");
$st->execute([$id]);
$item = $st->fetch();
if (!$item) {
    echo '<p class="text-red-500">아이템을 찾을 수 없습니다.</p>';
    include __DIR__ . '/../_foot.php'; return;
}

$versions = $db->prepare("SELECT * FROM {$pfx}mkt_item_versions WHERE item_id=? ORDER BY released_at DESC, id DESC");
$versions->execute([$id]);
$versions = $versions->fetchAll();
$latestVer = $versions[0] ?? null;

// 리뷰 (최신순)
$reviewsSt = $db->prepare("SELECT * FROM {$pfx}mkt_reviews WHERE item_id=? ORDER BY created_at DESC, id DESC");
$reviewsSt->execute([$id]);
$reviews = $reviewsSt->fetchAll();

// 이슈 / Q&A + 답변 일괄 로드
$issuesSt = $db->prepare("SELECT * FROM {$pfx}mkt_issues WHERE item_id=? ORDER BY created_at DESC, id DESC");
$issuesSt->execute([$id]);
$allIssues = $issuesSt->fetchAll();
$issues = array_values(array_filter($allIssues, fn($x) => $x['type'] === 'issue'));
$qnas   = array_values(array_filter($allIssues, fn($x) => $x['type'] === 'qna'));

// 답변 묶기
$replyMap = [];
if ($allIssues) {
    $issueIds = array_column($allIssues, 'id');
    $in = implode(',', array_map('intval', $issueIds));
    $reps = $db->query("SELECT * FROM {$pfx}mkt_issue_replies WHERE issue_id IN ($in) ORDER BY created_at ASC, id ASC")->fetchAll();
    foreach ($reps as $rep) $replyMap[(int)$rep['issue_id']][] = $rep;
}

$csrf     = $_SESSION['_csrf'] ?? '';
$adminUrl = $_mktAdmin;
$iname    = mkt_locale_val($item['name'], $_mktLocale);
$idesc    = mkt_locale_val($item['description'], $_mktLocale);
$ishort   = mkt_locale_val($item['short_description'], $_mktLocale);

$tagsRaw = json_decode($item['tags'] ?? '[]', true) ?: [];
if (isset($tagsRaw[$_mktLocale]) && is_array($tagsRaw[$_mktLocale])) $tags = $tagsRaw[$_mktLocale];
elseif (isset($tagsRaw['en']) && is_array($tagsRaw['en']))           $tags = $tagsRaw['en'];
elseif (array_is_list($tagsRaw))                                     $tags = $tagsRaw;
else                                                                  $tags = [];

$screenshots = json_decode($item['screenshots'] ?? '[]', true) ?: [];

$typeLabels = ['plugin'=>'플러그인', 'theme'=>'테마', 'widget'=>'위젯', 'skin'=>'스킨/레이아웃'];
$statusMeta = [
    'active'    => ['활성',    'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'],
    'pending'   => ['심사대기','bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'],
    'draft'     => ['임시저장','bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400'],
    'suspended' => ['정지',    'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'],
    'archived'  => ['보관',    'bg-zinc-200 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-500'],
];
[$sLabel, $sCls] = $statusMeta[$item['status']] ?? ['?', 'bg-zinc-100 text-zinc-500'];

if (!function_exists('mkt_show_asset_url')) {
    function mkt_show_asset_url(string $path): string {
        if (preg_match('#^https?://#i', $path)) return $path;
        return '/' . ltrim($path, '/');
    }
}
if (!function_exists('mkt_asset_exists')) {
    function mkt_asset_exists(?string $path): bool {
        if (!$path) return false;
        if (preg_match('#^https?://#i', $path)) return true;
        $fs = BASE_PATH . '/' . ltrim($path, '/');
        return file_exists($fs);
    }
}

$iconOk   = mkt_asset_exists($item['icon']         ?? null);
$bannerOk = mkt_asset_exists($item['banner_image'] ?? null);

// description HTML 정제
$safeDesc = '';
if ($idesc) {
    $safeDesc = preg_replace('#<(script|iframe|object|embed|style)[^>]*>.*?</\1>#isu', '', $idesc);
    $safeDesc = preg_replace('#\son\w+\s*=\s*(["\']).*?\1#isu', '', $safeDesc);
}

$priceLabel = (float)$item['price'] > 0
    ? number_format((float)$item['price']) . ' ' . htmlspecialchars($item['currency'] ?? 'JPY')
    : '무료';

$fileSize   = $latestVer && !empty($latestVer['file_size'])
    ? number_format($latestVer['file_size'] / 1024, 1) . ' KB'
    : '-';
$releaseDate = $latestVer
    ? substr($latestVer['released_at'] ?? $latestVer['created_at'] ?? '', 0, 10)
    : '-';
?>

<!-- 상단: 뒤로가기 + 액션 -->
<div class="flex items-center justify-between mb-4">
    <a href="<?= $adminUrl ?>/market/items" class="text-sm text-zinc-500 hover:text-indigo-600 dark:text-zinc-400">← 목록</a>
    <div class="flex items-center gap-2">
        <!-- 상태 드롭다운 -->
        <div class="relative" id="statusDropdown">
            <button type="button" id="statusBtn"
                    onclick="document.getElementById('statusMenu').classList.toggle('hidden')"
                    class="inline-flex items-center gap-1.5 px-3 py-2 rounded-lg text-xs font-semibold border <?= $sCls ?> border-transparent hover:opacity-90 transition">
                <?= $sLabel ?>
                <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 9l-7 7-7-7"/></svg>
            </button>
            <div id="statusMenu" class="hidden absolute right-0 mt-1 w-44 bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-lg shadow-lg z-50 overflow-hidden">
                <?php foreach (['active'=>'활성','pending'=>'심사대기','draft'=>'임시저장','suspended'=>'정지','archived'=>'보관'] as $sv => $sl2):
                    if ($sv === $item['status']) continue;
                    [, $svCls] = $statusMeta[$sv];
                ?>
                <button onclick="changeStatus(<?= $id ?>, '<?= $sv ?>')"
                        class="w-full flex items-center gap-2 px-3 py-2 text-sm text-left text-zinc-700 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition">
                    <span class="w-2 h-2 rounded-full <?= explode(' ', $svCls)[0] ?>"></span>
                    → <?= $sl2 ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        <a href="<?= $adminUrl ?>/market/items/edit?id=<?= $id ?>"
           class="inline-flex items-center gap-1.5 px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium shadow-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
            수정
        </a>
    </div>
</div>
<script>
document.addEventListener('click', function(e) {
    var dd = document.getElementById('statusDropdown');
    var menu = document.getElementById('statusMenu');
    if (dd && menu && !dd.contains(e.target)) menu.classList.add('hidden');
});
</script>

<!-- 타이틀 헤더 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden mb-5">
    <div class="flex gap-5 p-6 items-start">
        <?php if ($iconOk): ?>
        <img src="<?= htmlspecialchars(mkt_show_asset_url($item['icon'])) ?>" alt="" class="w-24 h-24 rounded-xl object-cover border border-zinc-200 dark:border-zinc-700 flex-shrink-0">
        <?php else: ?>
        <div class="w-24 h-24 rounded-xl bg-gradient-to-br from-indigo-100 to-indigo-200 dark:from-indigo-900/50 dark:to-indigo-800/50 flex items-center justify-center flex-shrink-0">
            <span class="text-3xl font-bold text-indigo-600 dark:text-indigo-400"><?= htmlspecialchars(mb_substr($iname ?: $item['slug'], 0, 1)) ?></span>
        </div>
        <?php endif; ?>
        <div class="flex-1 min-w-0">
            <h1 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($iname ?: $item['slug']) ?></h1>
            <?php if ($ishort): ?>
            <p class="text-sm text-zinc-600 dark:text-zinc-400 mt-2"><?= htmlspecialchars($ishort) ?></p>
            <?php endif; ?>
            <div class="flex items-center gap-4 mt-3 text-xs text-zinc-500 dark:text-zinc-400">
                <span class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    <?= htmlspecialchars($item['partner_name'] ?? '운영자') ?>
                </span>
                <span><?= htmlspecialchars(substr($item['created_at'] ?? '', 0, 10)) ?></span>
                <span class="flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                    <?= number_format((int)$item['download_count']) ?> 다운로드
                </span>
            </div>
        </div>
    </div>
</div>

<!-- 메타 정보 테이블 (탭 위 — 항상 표시) -->
<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden mb-5">
    <table class="w-full text-sm">
        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
            <tr>
                <th class="w-36 bg-zinc-50 dark:bg-zinc-900/50 px-5 py-3 text-left text-zinc-500 dark:text-zinc-400 font-medium">자료 유형</th>
                <td class="px-5 py-3 text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($typeLabels[$item['type']] ?? $item['type']) ?></td>
            </tr>
            <tr>
                <th class="bg-zinc-50 dark:bg-zinc-900/50 px-5 py-3 text-left text-zinc-500 dark:text-zinc-400 font-medium">간단한 소개</th>
                <td class="px-5 py-3 text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($ishort ?: '-') ?></td>
            </tr>
            <tr>
                <th class="bg-zinc-50 dark:bg-zinc-900/50 px-5 py-3 text-left text-zinc-500 dark:text-zinc-400 font-medium">슬러그</th>
                <td class="px-5 py-3 text-zinc-700 dark:text-zinc-300 font-mono text-xs"><?= htmlspecialchars($item['slug']) ?></td>
            </tr>
            <tr>
                <th class="bg-zinc-50 dark:bg-zinc-900/50 px-5 py-3 text-left text-zinc-500 dark:text-zinc-400 font-medium">product_key</th>
                <td class="px-5 py-3 text-zinc-700 dark:text-zinc-300 font-mono text-[11px]">
                    <?= htmlspecialchars($item['product_key'] ?? '-') ?>
                </td>
            </tr>
            <?php if ($item['min_voscms_version']): ?>
            <tr>
                <th class="bg-zinc-50 dark:bg-zinc-900/50 px-5 py-3 text-left text-zinc-500 dark:text-zinc-400 font-medium">최소 VosCMS 버전</th>
                <td class="px-5 py-3 text-zinc-700 dark:text-zinc-300 font-mono text-xs"><?= htmlspecialchars($item['min_voscms_version']) ?></td>
            </tr>
            <?php endif; ?>
            <?php if ($item['min_php_version']): ?>
            <tr>
                <th class="bg-zinc-50 dark:bg-zinc-900/50 px-5 py-3 text-left text-zinc-500 dark:text-zinc-400 font-medium">최소 PHP 버전</th>
                <td class="px-5 py-3 text-zinc-700 dark:text-zinc-300 font-mono text-xs"><?= htmlspecialchars($item['min_php_version']) ?></td>
            </tr>
            <?php endif; ?>
            <tr>
                <th class="bg-zinc-50 dark:bg-zinc-900/50 px-5 py-3 text-left text-zinc-500 dark:text-zinc-400 font-medium">파트너</th>
                <td class="px-5 py-3 text-zinc-700 dark:text-zinc-300">
                    <?php if ($item['partner_name']): ?>
                    <?= htmlspecialchars($item['partner_name']) ?>
                    <span class="text-zinc-400 ml-2 text-xs"><?= htmlspecialchars($item['partner_email'] ?? '') ?></span>
                    <?php if ($item['partner_id']): ?>
                    <a href="<?= $adminUrl ?>/market/partners/show?id=<?= $item['partner_id'] ?>" class="ml-2 text-xs text-indigo-600 dark:text-indigo-400 hover:underline">상세 →</a>
                    <?php endif; ?>
                    <?php else: ?>
                    <span class="text-zinc-400">(운영자 직접 등록)</span>
                    <?php endif; ?>
                </td>
            </tr>
            <tr>
                <th class="bg-zinc-50 dark:bg-zinc-900/50 px-5 py-3 text-left text-zinc-500 dark:text-zinc-400 font-medium">가격</th>
                <td class="px-5 py-3 text-zinc-700 dark:text-zinc-300 font-medium"><?= $priceLabel ?></td>
            </tr>
            <?php if ($item['license']): ?>
            <tr>
                <th class="bg-zinc-50 dark:bg-zinc-900/50 px-5 py-3 text-left text-zinc-500 dark:text-zinc-400 font-medium">라이선스</th>
                <td class="px-5 py-3 text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($item['license']) ?></td>
            </tr>
            <?php endif; ?>
        </tbody>
    </table>
</div>

<!-- ── 탭 ─────────────────────────────────────────────── -->
<div x-data="{ tab: 'info' }" class="space-y-5">
    <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <div class="border-b border-zinc-200 dark:border-zinc-700 flex flex-wrap">
            <button @click="tab='info'" :class="tab==='info' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'" class="px-5 py-3 text-sm font-medium border-b-2 transition-colors">정보</button>
            <button @click="tab='media'" :class="tab==='media' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'" class="px-5 py-3 text-sm font-medium border-b-2 transition-colors">미디어</button>
            <button @click="tab='versions'" :class="tab==='versions' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'" class="px-5 py-3 text-sm font-medium border-b-2 transition-colors">버전 (<?= count($versions) ?>)</button>
            <button @click="tab='reviews'" :class="tab==='reviews' ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'" class="px-5 py-3 text-sm font-medium border-b-2 transition-colors">리뷰 (<?= count($reviews) ?>)</button>
            <button @click="tab='issues'"  :class="tab==='issues'  ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'" class="px-5 py-3 text-sm font-medium border-b-2 transition-colors">이슈 (<?= count($issues) ?>)</button>
            <button @click="tab='qna'"     :class="tab==='qna'     ? 'border-indigo-500 text-indigo-600 dark:text-indigo-400' : 'border-transparent text-zinc-500 dark:text-zinc-400 hover:text-zinc-700 dark:hover:text-zinc-200'" class="px-5 py-3 text-sm font-medium border-b-2 transition-colors">Q&A (<?= count($qnas) ?>)</button>
        </div>
    </div>

    <!-- ── 정보 탭 ─────────────────────────────────────── -->
    <div x-show="tab==='info'" x-cloak class="space-y-5">

<!-- 정보 테이블 2: 최신 버전 요약 (수평형) -->
<?php if ($latestVer): ?>
<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden mb-5">
    <table class="w-full text-sm">
        <thead class="bg-zinc-50 dark:bg-zinc-900/50 text-xs text-zinc-500 dark:text-zinc-400">
            <tr>
                <th class="px-5 py-3 font-medium text-center">버전</th>
                <th class="px-5 py-3 font-medium text-center">날짜</th>
                <th class="px-5 py-3 font-medium text-center">용량</th>
                <th class="px-5 py-3 font-medium text-center">다운로드 수</th>
            </tr>
        </thead>
        <tbody>
            <tr class="text-zinc-800 dark:text-zinc-200">
                <td class="px-5 py-4 text-center">
                    <span class="inline-flex items-center gap-1.5 px-3 py-1 rounded-full bg-indigo-50 dark:bg-indigo-900/30 text-indigo-600 dark:text-indigo-400 font-mono text-sm font-semibold">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>
                        v<?= htmlspecialchars($latestVer['version']) ?>
                    </span>
                </td>
                <td class="px-5 py-4 text-center text-sm"><?= htmlspecialchars($releaseDate) ?></td>
                <td class="px-5 py-4 text-center text-sm font-mono"><?= htmlspecialchars($fileSize) ?></td>
                <td class="px-5 py-4 text-center text-sm font-medium"><?= number_format((int)$item['download_count']) ?></td>
            </tr>
        </tbody>
    </table>
</div>
<?php endif; ?>

<!-- 상세 설명 -->
<?php if ($safeDesc): ?>
<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6 mb-5">
    <div class="prose prose-sm dark:prose-invert max-w-none text-zinc-700 dark:text-zinc-300 [&_img]:max-w-full [&_img]:h-auto [&_img]:rounded-lg [&_img]:my-3 [&_p]:leading-relaxed">
        <?= $safeDesc ?>
    </div>
</div>
<?php endif; ?>

<!-- 태그 (정보 탭) -->
<?php if ($tags): ?>
<div class="mb-5 flex flex-wrap gap-1.5">
    <?php foreach ($tags as $tag): ?>
    <span class="text-xs px-3 py-1 rounded-full bg-zinc-100 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400">#<?= htmlspecialchars($tag) ?></span>
    <?php endforeach; ?>
</div>
<?php endif; ?>

    </div>
    <!-- ── /정보 탭 ─────────────────────────────────────── -->

    <!-- ── 미디어 탭 ────────────────────────────────────── -->
    <div x-show="tab==='media'" x-cloak class="space-y-5">

<!-- 스크린샷 -->
<?php
$validShots = array_values(array_filter($screenshots, fn($s) => mkt_asset_exists($s)));
if (!empty($validShots)): ?>
<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6 mb-5">
    <h3 class="text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-4">스크린샷 (<?= count($validShots) ?>)</h3>
    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
        <?php foreach ($validShots as $ss): ?>
        <a href="<?= htmlspecialchars(mkt_show_asset_url($ss)) ?>" target="_blank" class="block">
            <img src="<?= htmlspecialchars(mkt_show_asset_url($ss)) ?>" alt="" class="w-full rounded-lg border border-zinc-200 dark:border-zinc-700 hover:opacity-90 transition">
        </a>
        <?php endforeach; ?>
    </div>
</div>
<?php endif; ?>

<!-- 배너 이미지 (있을 때만) -->
<?php if ($bannerOk): ?>
<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 p-6 mb-5">
    <h3 class="text-sm font-bold text-zinc-700 dark:text-zinc-300 mb-4">배너 이미지</h3>
    <img src="<?= htmlspecialchars(mkt_show_asset_url($item['banner_image'])) ?>" alt="" class="w-full rounded-lg border border-zinc-200 dark:border-zinc-700">
</div>
<?php endif; ?>

    </div>
    <!-- ── /미디어 탭 ───────────────────────────────────── -->

    <!-- ── 버전 탭 ──────────────────────────────────────── -->
    <div x-show="tab==='versions'" x-cloak class="space-y-5">

<!-- 버전 내역 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden mb-5">
    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
        <h3 class="text-sm font-bold text-zinc-700 dark:text-zinc-300">버전 내역</h3>
        <span class="text-xs text-zinc-400"><?= count($versions) ?>개</span>
    </div>
    <?php if (empty($versions)): ?>
    <p class="p-8 text-center text-sm text-zinc-400">등록된 버전이 없습니다.</p>
    <?php else: ?>
    <table class="w-full text-sm">
        <thead class="bg-zinc-50 dark:bg-zinc-900/50 text-xs text-zinc-500 dark:text-zinc-400">
            <tr>
                <th class="px-5 py-3 text-left font-medium">버전</th>
                <th class="px-5 py-3 text-left font-medium">패키지</th>
                <th class="px-5 py-3 text-left font-medium">크기</th>
                <th class="px-5 py-3 text-left font-medium">상태</th>
                <th class="px-5 py-3 text-left font-medium">출시일</th>
            </tr>
        </thead>
        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
        <?php foreach ($versions as $ver):
            $hasFile = !empty($ver['file_path']);
            $sizeStr = $ver['file_size'] ? number_format($ver['file_size']/1024, 1) . ' KB' : '-';
        ?>
        <tr class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
            <td class="px-5 py-3 font-mono text-sm font-bold text-zinc-700 dark:text-zinc-300">v<?= htmlspecialchars($ver['version']) ?></td>
            <td class="px-5 py-3 text-xs">
                <?php if ($hasFile): ?>
                <span class="text-green-600 dark:text-green-400">✓</span>
                <span class="text-zinc-500 font-mono text-[11px] ml-1 truncate" title="<?= htmlspecialchars($ver['file_path']) ?>"><?= htmlspecialchars(basename($ver['file_path'])) ?></span>
                <?php else: ?>
                <span class="text-zinc-400">- 없음</span>
                <?php endif; ?>
            </td>
            <td class="px-5 py-3 text-xs text-zinc-500 font-mono"><?= $sizeStr ?></td>
            <td class="px-5 py-3">
                <span class="text-xs px-2 py-0.5 rounded <?= $ver['status']==='active'?'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400':'bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400' ?>">
                    <?= $ver['status']==='active' ? '활성' : ($ver['status']==='yanked' ? '회수' : '초안') ?>
                </span>
            </td>
            <td class="px-5 py-3 text-xs text-zinc-400"><?= htmlspecialchars(substr($ver['released_at'] ?? $ver['created_at'] ?? '', 0, 10)) ?></td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
    <?php endif; ?>
</div>

    </div>
    <!-- ── /버전 탭 ─────────────────────────────────────── -->

    <!-- ── 리뷰 탭 ──────────────────────────────────────── -->
    <div x-show="tab==='reviews'" x-cloak class="space-y-5">

<!-- 리뷰 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden mb-5">
    <div class="px-6 py-4 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
        <h3 class="text-sm font-bold text-zinc-700 dark:text-zinc-300 flex items-center gap-2">
            리뷰
            <?php if ((int)$item['rating_count'] > 0): ?>
            <span class="inline-flex items-center gap-1 text-xs font-medium px-2 py-0.5 rounded-full bg-yellow-50 dark:bg-yellow-900/20 text-yellow-700 dark:text-yellow-400">
                <svg class="w-3.5 h-3.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                <?= number_format((float)$item['rating_avg'], 1) ?>
            </span>
            <?php endif; ?>
        </h3>
        <span class="text-xs text-zinc-400"><?= count($reviews) ?>개</span>
    </div>
    <?php if (empty($reviews)): ?>
    <p class="p-8 text-center text-sm text-zinc-400">아직 리뷰가 없습니다.</p>
    <?php else: ?>
    <div class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
        <?php foreach ($reviews as $rv):
            $statusBadge = [
                'approved' => ['공개',  'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'],
                'pending'  => ['대기',  'bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400'],
                'rejected' => ['거절',  'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400'],
            ][$rv['status']] ?? ['-', 'bg-zinc-100 text-zinc-500'];
        ?>
        <div class="p-5">
            <div class="flex items-start gap-3 mb-2 flex-wrap">
                <div class="flex">
                    <?php for ($i = 1; $i <= 5; $i++): ?>
                    <svg class="w-4 h-4 <?= $i <= (int)$rv['rating'] ? 'text-yellow-400' : 'text-zinc-200 dark:text-zinc-600' ?>" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                    <?php endfor; ?>
                </div>
                <?php if (!empty($rv['reviewer_name'])): ?>
                <span class="text-sm font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($rv['reviewer_name']) ?></span>
                <?php endif; ?>
                <?php if (!empty($rv['reviewer_domain'])): ?>
                <span class="text-xs text-zinc-500 dark:text-zinc-400 font-mono"><?= htmlspecialchars($rv['reviewer_domain']) ?></span>
                <?php endif; ?>
                <?php if (!empty($rv['is_verified'])): ?>
                <span class="inline-flex items-center gap-1 text-[11px] font-medium px-1.5 py-0.5 rounded bg-emerald-50 dark:bg-emerald-900/20 text-emerald-700 dark:text-emerald-400">
                    <svg class="w-3 h-3" fill="currentColor" viewBox="0 0 20 20"><path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/></svg>
                    구매 인증
                </span>
                <?php endif; ?>
                <span class="text-[11px] font-medium px-1.5 py-0.5 rounded <?= $statusBadge[1] ?>"><?= $statusBadge[0] ?></span>
                <span class="text-xs text-zinc-400 ml-auto"><?= !empty($rv['created_at']) ? htmlspecialchars(substr($rv['created_at'], 0, 16)) : '' ?></span>
            </div>
            <?php if (!empty($rv['body'])): ?>
            <p class="text-sm text-zinc-700 dark:text-zinc-300 whitespace-pre-line"><?= htmlspecialchars($rv['body']) ?></p>
            <?php endif; ?>
            <div class="mt-3 flex gap-2">
                <?php if ($rv['status'] !== 'approved'): ?>
                <button type="button" onclick="reviewStatus(<?= (int)$rv['id'] ?>, 'approved')" class="text-xs px-2.5 py-1 rounded border border-green-300 dark:border-green-700 text-green-700 dark:text-green-400 hover:bg-green-50 dark:hover:bg-green-900/20">공개</button>
                <?php endif; ?>
                <?php if ($rv['status'] !== 'rejected'): ?>
                <button type="button" onclick="reviewStatus(<?= (int)$rv['id'] ?>, 'rejected')" class="text-xs px-2.5 py-1 rounded border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700">숨김</button>
                <?php endif; ?>
                <button type="button" onclick="reviewDelete(<?= (int)$rv['id'] ?>)" class="text-xs px-2.5 py-1 rounded border border-red-300 dark:border-red-700 text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20">삭제</button>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

    </div>
    <!-- ── /리뷰 탭 ─────────────────────────────────────── -->

    <!-- ── 이슈 탭 ──────────────────────────────────────── -->
    <div x-show="tab==='issues'" x-cloak class="space-y-3">
        <?php $_threadList = $issues; $_threadEmpty = '등록된 이슈가 없습니다.'; include __DIR__ . '/_issue_list.php'; ?>
    </div>

    <!-- ── Q&A 탭 ──────────────────────────────────────── -->
    <div x-show="tab==='qna'" x-cloak class="space-y-3">
        <?php $_threadList = $qnas; $_threadEmpty = '등록된 질문이 없습니다.'; include __DIR__ . '/_issue_list.php'; ?>
    </div>
</div>
<!-- ── /탭 ──────────────────────────────────────────────── -->

<script>
const CSRF    = <?= json_encode($csrf) ?>;
const API_URL = <?= json_encode($adminUrl . '/market/items/api') ?>;

async function changeStatus(id, status) {
    if (!confirm('상태를 변경하시겠습니까?')) return;
    const fd = new FormData();
    fd.append('action', 'status');
    fd.append('id', id);
    fd.append('status', status);
    fd.append('_token', CSRF);
    const r = await fetch(API_URL, { method: 'POST', body: fd });
    const d = await r.json();
    if (d.ok || d.success) location.reload();
    else alert(d.msg || d.message || '오류');
}

async function reviewStatus(id, status) {
    const fd = new FormData();
    fd.append('action', 'review_status');
    fd.append('id', id);
    fd.append('status', status);
    fd.append('_token', CSRF);
    const r = await fetch(API_URL, { method: 'POST', body: fd });
    const d = await r.json();
    if (d.ok || d.success) location.reload();
    else alert(d.msg || d.message || '오류');
}

async function reviewDelete(id) {
    if (!confirm('이 리뷰를 삭제하시겠습니까?')) return;
    const fd = new FormData();
    fd.append('action', 'review_delete');
    fd.append('id', id);
    fd.append('_token', CSRF);
    const r = await fetch(API_URL, { method: 'POST', body: fd });
    const d = await r.json();
    if (d.ok || d.success) location.reload();
    else alert(d.msg || d.message || '오류');
}

// ── 이슈 / Q&A 운영자 액션 ────────────────────────────────
async function issueStatus(id, status) {
    const fd = new FormData();
    fd.append('action', 'issue_status'); fd.append('id', id); fd.append('status', status); fd.append('_token', CSRF);
    const r = await fetch(API_URL, { method: 'POST', body: fd });
    const d = await r.json();
    if (d.ok || d.success) location.reload();
    else alert(d.msg || d.message || '오류');
}
async function issueDelete(id) {
    if (!confirm('이 항목을 삭제하시겠습니까? 답변도 함께 삭제됩니다.')) return;
    const fd = new FormData();
    fd.append('action', 'issue_delete'); fd.append('id', id); fd.append('_token', CSRF);
    const r = await fetch(API_URL, { method: 'POST', body: fd });
    const d = await r.json();
    if (d.ok || d.success) location.reload();
    else alert(d.msg || d.message || '오류');
}
async function issueReplyAdd(issueId) {
    const txt = document.getElementById('rep_' + issueId).value.trim();
    if (!txt) { alert('답변 내용을 입력하세요.'); return; }
    const fd = new FormData();
    fd.append('action', 'issue_reply_add');
    fd.append('issue_id', issueId);
    fd.append('body', txt);
    fd.append('_token', CSRF);
    const r = await fetch(API_URL, { method: 'POST', body: fd });
    const d = await r.json();
    if (d.ok || d.success) location.reload();
    else alert(d.msg || d.message || '오류');
}
async function issueReplyDelete(rid) {
    if (!confirm('이 답변을 삭제하시겠습니까?')) return;
    const fd = new FormData();
    fd.append('action', 'issue_reply_delete'); fd.append('id', rid); fd.append('_token', CSRF);
    const r = await fetch(API_URL, { method: 'POST', body: fd });
    const d = await r.json();
    if (d.ok || d.success) location.reload();
    else alert(d.msg || d.message || '오류');
}
</script>

<?php include __DIR__ . '/../_foot.php'; ?>
