<?php
/**
 * 배포 디렉토리 스캔
 *
 * /var/www/market/packages/{type}[/subtype]/{slug}/ 구조를 스캔하여
 * 아이템을 자동 등록/갱신하고 ZIP 패키지를 생성한다.
 *
 * GET  → 스캔 결과 + 체크박스 선택 UI
 * POST → 선택된 항목만 실제 등록/ZIP 생성 실행
 */
include __DIR__ . '/../_head.php';
$pageHeaderTitle = '배포 스캔';
$db = mkt_pdo(); $pfx = $_mktPrefix;

$adminUrl = $_mktAdmin;
$basePath = BASE_PATH;
$pkgRoot  = $basePath . '/packages';

// 스캔 대상: [db_type, 서브타입(skin only), glob 패턴, manifest 파일]
$scanSpecs = [
    ['plugin', null,     $pkgRoot . '/plugin/*',         ['plugin.json']],
    ['widget', null,     $pkgRoot . '/widget/*',         ['widget.json']],
    ['theme',  null,     $pkgRoot . '/theme/*',          ['theme.json']],
    ['skin',   'layout', $pkgRoot . '/skin/layout/*',    ['layout.json', 'skin.json']],
    ['skin',   'board',  $pkgRoot . '/skin/board/*',     ['skin.json']],
    ['skin',   'mypage', $pkgRoot . '/skin/mypage/*',    ['skin.json']],
];

/**
 * 디렉토리에서 manifest 읽기 (여러 후보 중 첫 번째 매치)
 */
function mkt_read_manifest(string $dir, array $candidates): ?array {
    foreach ($candidates as $fname) {
        $p = $dir . '/' . $fname;
        if (file_exists($p)) {
            $data = @json_decode(@file_get_contents($p), true);
            if (is_array($data)) return ['data' => $data, 'manifest' => $fname];
        }
    }
    return null;
}

// ── 스캔 ─────────────────────────────────────────────────────
$scanned = [];
foreach ($scanSpecs as [$type, $subtype, $pattern, $manifests]) {
    foreach (glob($pattern, GLOB_ONLYDIR) ?: [] as $dir) {
        $slug   = basename($dir);
        $mf     = mkt_read_manifest($dir, $manifests);
        $data   = $mf['data'] ?? [];
        // manifest에 slug 명시되어 있으면 그 값 우선 (id 또는 slug 키)
        $actual = $data['id'] ?? $data['slug'] ?? $slug;

        $scanned[] = [
            'type'          => $type,
            'subtype'       => $subtype,
            'slug'          => $actual,
            'dir_slug'      => $slug,
            'source_path'   => $dir,
            'manifest_file' => $mf['manifest'] ?? null,
            'manifest_data' => $data,
            'version'       => $data['version'] ?? '1.0.0',
            'name'          => $data['name'] ?? $actual,
            'description'   => $data['description'] ?? null,
            'short_description' => $data['short_description'] ?? null,
            'author_name'   => is_array($data['author'] ?? null) ? ($data['author']['name'] ?? null) : ($data['author'] ?? null),
            'has_manifest'  => $mf !== null,
        ];
    }
}

// 기존 DB 아이템 조회 (slug → row)
$byDbSlug = [];
if ($scanned) {
    $slugs = array_values(array_unique(array_map(fn($s) => $s['slug'], $scanned)));
    $phs   = implode(',', array_fill(0, count($slugs), '?'));
    $st    = $db->prepare("SELECT id, slug, type, status, latest_version, product_key FROM {$pfx}mkt_items WHERE slug IN ($phs)");
    $st->execute($slugs);
    foreach ($st->fetchAll() as $r) $byDbSlug[$r['slug']] = $r;
}

// 상태 분류: new / update / same
foreach ($scanned as &$s) {
    $db_row = $byDbSlug[$s['slug']] ?? null;
    if (!$db_row) {
        $s['action'] = 'new';
        $s['action_label'] = '신규 등록';
    } elseif ($db_row['latest_version'] !== $s['version']) {
        $s['action'] = 'update';
        $s['action_label'] = '버전 갱신 (' . ($db_row['latest_version'] ?? '-') . ' → ' . $s['version'] . ')';
        $s['db_id']        = (int)$db_row['id'];
        $s['db_status']    = $db_row['status'];
    } else {
        $s['action'] = 'same';
        $s['action_label'] = 'ZIP만 재생성';
        $s['db_id']        = (int)$db_row['id'];
        $s['db_status']    = $db_row['status'];
    }
    if (!$s['has_manifest']) {
        $s['action'] = 'no_manifest';
        $s['action_label'] = '⚠ manifest 없음';
    }
}
unset($s);

$csrf = $_SESSION['_csrf'] ?? '';

// ── POST: 실행 ──────────────────────────────────────────────
$results = [];
$executed = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST'
    && hash_equals($_SESSION['_csrf'] ?? '', $_POST['_csrf'] ?? '')) {

    $executed  = true;
    $selKeys   = $_POST['keys'] ?? []; // 각 행의 고유키 (type|subtype|slug)

    // 선택된 것만 처리
    $byKey = [];
    foreach ($scanned as $s) {
        $k = $s['type'] . '|' . ($s['subtype'] ?? '') . '|' . $s['slug'];
        $byKey[$k] = $s;
    }

    foreach ($selKeys as $k) {
        if (!isset($byKey[$k])) {
            $results[] = ['slug' => $k, 'ok' => false, 'msg' => '항목 없음'];
            continue;
        }
        $s = $byKey[$k];
        if ($s['action'] === 'no_manifest') {
            $results[] = ['slug' => $s['slug'], 'ok' => false, 'msg' => 'manifest 파일 없음 (등록 불가)'];
            continue;
        }

        try {
            $db->beginTransaction();

            // 1. 아이템 생성/갱신
            $existing = $byDbSlug[$s['slug']] ?? null;

            // 다국어 name/description JSON 정규화
            $nameJson = is_array($s['name'])
                ? json_encode($s['name'], JSON_UNESCAPED_UNICODE)
                : json_encode(['ko' => (string)$s['name'], 'en' => (string)$s['name']], JSON_UNESCAPED_UNICODE);
            $descJson = is_array($s['description'])
                ? json_encode($s['description'], JSON_UNESCAPED_UNICODE)
                : ($s['description'] ? json_encode(['ko' => (string)$s['description']], JSON_UNESCAPED_UNICODE) : null);
            $shortJson = is_array($s['short_description'])
                ? json_encode($s['short_description'], JSON_UNESCAPED_UNICODE)
                : ($s['short_description'] ? json_encode(['ko' => (string)$s['short_description']], JSON_UNESCAPED_UNICODE) : null);

            // tags에 subtype 저장 (skin 하위 구분)
            $tags = [];
            if ($s['subtype']) $tags[] = 'skin-' . $s['subtype'];

            if (!$existing) {
                $productKey = sprintf('%08x-%04x-4%03x-%04x-%012x',
                    random_int(0, 0xffffffff), random_int(0, 0xffff),
                    random_int(0, 0xfff), random_int(0x8000, 0xbfff),
                    random_int(0, 0xffffffffffff)
                );
                $db->prepare("
                    INSERT INTO {$pfx}mkt_items
                        (product_key, slug, type, name, description, short_description,
                         author_name, tags, latest_version, price, currency, status,
                         created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 0, 'JPY', 'active', NOW(), NOW())
                ")->execute([
                    $productKey, $s['slug'], $s['type'],
                    $nameJson, $descJson, $shortJson,
                    $s['author_name'], json_encode($tags), $s['version'],
                ]);
                $itemId = (int)$db->lastInsertId();
            } else {
                $itemId = (int)$existing['id'];
                $db->prepare("
                    UPDATE {$pfx}mkt_items
                       SET type=?, name=?, description=?, short_description=?,
                           author_name=?, tags=?, latest_version=?,
                           status='active', updated_at=NOW()
                     WHERE id=?
                ")->execute([
                    $s['type'], $nameJson, $descJson, $shortJson,
                    $s['author_name'], json_encode($tags), $s['version'], $itemId,
                ]);
            }

            // 2. ZIP 생성
            $storeDir = $basePath . '/storage/uploads/packages/' . $s['slug'];
            if (!is_dir($storeDir)) @mkdir($storeDir, 0775, true);
            $zipName = $s['slug'] . '-' . $s['version'] . '.zip';
            $zipPath = $storeDir . '/' . $zipName;
            $relPath = '/storage/uploads/packages/' . $s['slug'] . '/' . $zipName;

            $zip = new ZipArchive();
            if ($zip->open($zipPath, ZipArchive::CREATE | ZipArchive::OVERWRITE) !== true) {
                throw new RuntimeException('ZIP 열기 실패: ' . $zipPath);
            }
            $it = new RecursiveIteratorIterator(
                new RecursiveDirectoryIterator($s['source_path'], FilesystemIterator::SKIP_DOTS)
            );
            foreach ($it as $file) {
                if ($file->isDir()) continue;
                $rel = substr($file->getPathname(), strlen($s['source_path']) + 1);
                $zip->addFile($file->getPathname(), $s['slug'] . '/' . $rel);
            }
            $zip->close();

            if (!file_exists($zipPath)) throw new RuntimeException('ZIP 생성 실패');

            $size = filesize($zipPath);
            $hash = hash_file('sha256', $zipPath);

            // 3. 버전 레코드 UPSERT
            $stV = $db->prepare("SELECT id FROM {$pfx}mkt_item_versions WHERE item_id=? AND version=?");
            $stV->execute([$itemId, $s['version']]);
            $vid = $stV->fetchColumn();
            if ($vid) {
                $db->prepare("UPDATE {$pfx}mkt_item_versions SET file_path=?, file_size=?, file_hash=?, status='active', released_at=NOW() WHERE id=?")
                   ->execute([$relPath, $size, $hash, $vid]);
            } else {
                $db->prepare("INSERT INTO {$pfx}mkt_item_versions (item_id, version, file_path, file_size, file_hash, status, released_at) VALUES (?,?,?,?,?, 'active', NOW())")
                   ->execute([$itemId, $s['version'], $relPath, $size, $hash]);
            }

            $db->commit();
            $results[] = [
                'slug'    => $s['slug'],
                'ok'      => true,
                'action'  => $s['action'],
                'version' => $s['version'],
                'size'    => $size,
            ];
        } catch (Throwable $e) {
            if ($db->inTransaction()) $db->rollBack();
            $results[] = ['slug' => $s['slug'], 'ok' => false, 'msg' => $e->getMessage()];
        }
    }
}

$actionColors = [
    'new'         => 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400',
    'update'      => 'bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400',
    'same'        => 'bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400',
    'no_manifest' => 'bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400',
];
$typeLabels = ['plugin'=>'플러그인','widget'=>'위젯','theme'=>'테마','skin'=>'스킨'];
?>

<div class="mb-5">
    <a href="<?= $adminUrl ?>/market/items" class="text-sm text-zinc-500 hover:text-indigo-600">← 아이템 관리</a>
    <h1 class="text-2xl font-bold text-zinc-900 dark:text-white mt-2">배포 스캔</h1>
    <p class="text-sm text-zinc-500 dark:text-zinc-400 mt-1">
        <code class="text-[11px] bg-zinc-100 dark:bg-zinc-700 px-1.5 py-0.5 rounded">/var/www/market/packages/</code>
        하위의 아이템 디렉토리를 스캔하여 자동 등록·갱신합니다.
    </p>
    <details class="mt-3 text-xs text-zinc-500">
        <summary class="cursor-pointer hover:text-indigo-600">📂 배포 디렉토리 구조 보기</summary>
        <pre class="mt-2 p-3 bg-zinc-900 text-zinc-300 rounded-lg text-[11px] leading-relaxed"><code>packages/
├── plugin/{slug}/           (plugin.json)
├── widget/{slug}/           (widget.json)
├── theme/{slug}/            (theme.json)
└── skin/
    ├── layout/{slug}/       (layout.json)
    ├── board/{slug}/        (skin.json)
    └── mypage/{slug}/       (skin.json)</code></pre>
    </details>
</div>

<?php if ($executed): ?>
<div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden mb-5">
    <div class="px-4 py-3 bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-700">
        <h2 class="text-sm font-bold text-zinc-800 dark:text-zinc-200">실행 결과 (<?= count($results) ?>건)</h2>
    </div>
    <table class="w-full text-sm">
        <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
        <?php foreach ($results as $r): ?>
        <tr>
            <td class="px-4 py-2 font-mono text-xs w-56"><?= htmlspecialchars($r['slug'] ?: '-') ?></td>
            <td class="px-4 py-2">
                <?php if (!empty($r['ok'])): ?>
                <span class="text-green-600 text-xs">✓ <?= htmlspecialchars($r['action'] ?? '') ?></span>
                <span class="text-zinc-400 text-[11px] ml-2">v<?= htmlspecialchars($r['version'] ?? '') ?> · <?= number_format($r['size'] ?? 0) ?> bytes</span>
                <?php else: ?>
                <span class="text-red-600 text-xs">✗ <?= htmlspecialchars($r['msg'] ?? '') ?></span>
                <?php endif; ?>
            </td>
        </tr>
        <?php endforeach; ?>
        </tbody>
    </table>
</div>
<div class="flex gap-2">
    <a href="<?= $adminUrl ?>/market/items/bulk-pack" class="px-4 py-2 bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg text-sm font-medium">다시 스캔</a>
    <a href="<?= $adminUrl ?>/market/items" class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg text-sm hover:bg-zinc-50 dark:hover:bg-zinc-700">아이템 목록</a>
</div>

<?php else: ?>

<?php if (empty($scanned)): ?>
<div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl p-12 text-center">
    <p class="text-sm text-zinc-500">배포 디렉토리에 아이템이 없습니다.</p>
    <p class="text-xs text-zinc-400 mt-2">
        <code class="bg-zinc-100 dark:bg-zinc-700 px-1.5 py-0.5 rounded">/var/www/market/packages/{type}/{slug}/</code>
        경로에 디렉토리를 복사한 후 다시 스캔하세요.
    </p>
</div>

<?php else: ?>
<form method="POST" id="bulkForm">
    <input type="hidden" name="_csrf" value="<?= htmlspecialchars($csrf) ?>">

    <div class="bg-white dark:bg-zinc-800 border border-zinc-200 dark:border-zinc-700 rounded-xl overflow-hidden mb-5">
        <div class="px-4 py-3 bg-zinc-50 dark:bg-zinc-900/50 border-b border-zinc-200 dark:border-zinc-700 flex items-center justify-between">
            <div class="flex items-center gap-3">
                <label class="inline-flex items-center gap-2 text-sm">
                    <input type="checkbox" id="checkAll" class="w-4 h-4 rounded">
                    <span class="font-medium text-zinc-700 dark:text-zinc-300">전체 선택</span>
                </label>
                <span class="text-xs text-zinc-400">(<span id="selectedCount">0</span>/<?= count($scanned) ?>)</span>
            </div>
            <div class="flex gap-1.5 text-[10px]">
                <span class="px-1.5 py-0.5 rounded <?= $actionColors['new'] ?>">신규</span>
                <span class="px-1.5 py-0.5 rounded <?= $actionColors['update'] ?>">갱신</span>
                <span class="px-1.5 py-0.5 rounded <?= $actionColors['same'] ?>">동일</span>
            </div>
        </div>

        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-900/50 text-xs text-zinc-500">
                <tr>
                    <th class="px-4 py-2 w-10"></th>
                    <th class="px-4 py-2 text-left font-medium">slug</th>
                    <th class="px-4 py-2 text-left font-medium">타입</th>
                    <th class="px-4 py-2 text-left font-medium">버전</th>
                    <th class="px-4 py-2 text-left font-medium">동작</th>
                    <th class="px-4 py-2 text-left font-medium">소스</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-100 dark:divide-zinc-700/50">
            <?php foreach ($scanned as $s):
                $key = $s['type'] . '|' . ($s['subtype'] ?? '') . '|' . $s['slug'];
                $typeDisp = $typeLabels[$s['type']] ?? $s['type'];
                if ($s['subtype']) $typeDisp .= ' · ' . $s['subtype'];
                $disabled = ($s['action'] === 'no_manifest');
            ?>
            <tr class="<?= $disabled ? 'opacity-50' : 'hover:bg-zinc-50 dark:hover:bg-zinc-700/30 cursor-pointer' ?>" <?= !$disabled ? 'onclick="toggleRow(this)"' : '' ?>>
                <td class="px-4 py-2 text-center">
                    <input type="checkbox" name="keys[]" value="<?= htmlspecialchars($key) ?>"
                           class="row-check w-4 h-4 rounded"
                           <?= $disabled ? 'disabled' : '' ?>
                           onclick="event.stopPropagation()">
                </td>
                <td class="px-4 py-2">
                    <div class="font-mono text-xs text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($s['slug']) ?></div>
                    <?php if (is_array($s['name']) && !empty($s['name'][$_mktLocale])): ?>
                    <div class="text-[11px] text-zinc-500 mt-0.5"><?= htmlspecialchars($s['name'][$_mktLocale]) ?></div>
                    <?php elseif (is_string($s['name']) && $s['name'] !== $s['slug']): ?>
                    <div class="text-[11px] text-zinc-500 mt-0.5"><?= htmlspecialchars($s['name']) ?></div>
                    <?php endif; ?>
                </td>
                <td class="px-4 py-2 text-xs text-zinc-500"><?= htmlspecialchars($typeDisp) ?></td>
                <td class="px-4 py-2 text-xs font-mono text-zinc-500">v<?= htmlspecialchars($s['version']) ?></td>
                <td class="px-4 py-2">
                    <span class="inline-block text-[11px] px-2 py-0.5 rounded font-medium <?= $actionColors[$s['action']] ?>">
                        <?= htmlspecialchars($s['action_label']) ?>
                    </span>
                </td>
                <td class="px-4 py-2 text-[11px] font-mono text-zinc-400">
                    <?= htmlspecialchars(str_replace($basePath, '', $s['source_path'])) ?>
                </td>
            </tr>
            <?php endforeach; ?>
            </tbody>
        </table>
    </div>

    <div class="flex items-center gap-2">
        <button type="submit" id="runBtn" disabled
                class="px-5 py-2.5 bg-indigo-600 hover:bg-indigo-700 disabled:bg-zinc-300 disabled:cursor-not-allowed text-white rounded-lg text-sm font-medium transition">
            ▶ 선택한 <span id="runCount">0</span>개 실행
        </button>
        <a href="<?= $adminUrl ?>/market/items" class="px-4 py-2.5 border border-zinc-300 dark:border-zinc-600 text-zinc-600 dark:text-zinc-400 rounded-lg text-sm hover:bg-zinc-50 dark:hover:bg-zinc-700">취소</a>
    </div>
</form>

<script>
(function(){
    var form        = document.getElementById('bulkForm');
    var all         = document.getElementById('checkAll');
    var rows        = document.querySelectorAll('.row-check:not([disabled])');
    var runBtn      = document.getElementById('runBtn');
    var selCountEl  = document.getElementById('selectedCount');
    var runCountEl  = document.getElementById('runCount');

    function updateCount() {
        var checked = Array.from(rows).filter(function(c){ return c.checked; }).length;
        if (selCountEl) selCountEl.textContent = checked;
        if (runCountEl) runCountEl.textContent = checked;
        if (runBtn)     runBtn.disabled = (checked === 0);
        if (all)        all.checked = (checked > 0 && checked === rows.length);
    }
    if (all) all.addEventListener('change', function(){
        rows.forEach(function(c){ c.checked = all.checked; });
        updateCount();
    });
    rows.forEach(function(c){ c.addEventListener('change', updateCount); });

    if (form) form.addEventListener('submit', function(e){
        var checked = Array.from(rows).filter(function(c){ return c.checked; }).length;
        if (checked === 0) { e.preventDefault(); return; }
        if (!confirm(checked + '개 아이템을 처리합니다. 계속하시겠습니까?')) {
            e.preventDefault();
        }
    });
    updateCount();
})();

function toggleRow(tr) {
    var cb = tr.querySelector('.row-check:not([disabled])');
    if (!cb) return;
    cb.checked = !cb.checked;
    cb.dispatchEvent(new Event('change'));
}
</script>
<?php endif; ?>
<?php endif; ?>

<?php include __DIR__ . '/../_foot.php'; ?>
