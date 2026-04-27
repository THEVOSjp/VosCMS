<?php
/**
 * VosCMS Marketplace - 설치 내역
 */
include __DIR__ . '/_head.php';
$pageHeaderTitle = __('autoinstall.title');
$pageSubTitle    = __('autoinstall.my_purchases');

$prefix  = $_ENV['DB_PREFIX'] ?? 'rzx_';
$locale  = $_SESSION['locale'] ?? 'ko';

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    echo '<div class="p-4 bg-red-50 dark:bg-red-900/20 text-red-600 rounded-lg">DB Error</div>';
    include __DIR__ . '/_foot.php';
    return;
}

// ── 라이선스 전체 조회 (slug → key, status) ─────────────────────
$licRows = $pdo->query(
    "SELECT plugin_id, setting_key, setting_value FROM {$prefix}plugin_settings
     WHERE setting_key IN ('market_license_key','market_license_status')"
)->fetchAll(PDO::FETCH_ASSOC);

$licMap = [];
foreach ($licRows as $r) {
    $licMap[$r['plugin_id']][$r['setting_key']] = $r['setting_value'];
}

// ── 공용: JSON 읽기 헬퍼 ─────────────────────────────────────────
$readName = function(array $meta) use ($locale): string {
    $n = $meta['title'] ?? $meta['name'] ?? null;
    if (!$n) return $meta['slug'] ?? '?';
    if (is_array($n)) return $n[$locale] ?? $n['en'] ?? reset($n);
    return $n;
};

// ════════════════════════════════════════════════════════════════
// 1. 플러그인
// ════════════════════════════════════════════════════════════════
$plugins = [];
$pluginRows = $pdo->query(
    "SELECT plugin_id FROM {$prefix}plugins WHERE is_active = 1 ORDER BY plugin_id"
)->fetchAll(PDO::FETCH_COLUMN);

foreach ($pluginRows as $slug) {
    $jsonFile = BASE_PATH . "/plugins/{$slug}/plugin.json";
    if (!file_exists($jsonFile)) continue;
    $meta = json_decode(file_get_contents($jsonFile), true) ?: [];
    $plugins[] = [
        'slug'      => $slug,
        'name'      => $readName($meta),
        'version'   => $meta['version'] ?? '-',
        'licKey'    => $licMap[$slug]['market_license_key'] ?? null,
        'licStatus' => $licMap[$slug]['market_license_status'] ?? null,
    ];
}

// ════════════════════════════════════════════════════════════════
// 2. 위젯
// ════════════════════════════════════════════════════════════════
$widgets = [];
$widgetRows = $pdo->query(
    "SELECT slug, name, version FROM {$prefix}widgets WHERE is_active = 1 ORDER BY slug"
)->fetchAll(PDO::FETCH_ASSOC);

foreach ($widgetRows as $row) {
    $slug     = $row['slug'];
    $jsonFile = BASE_PATH . "/widgets/{$slug}/widget.json";
    $name     = $row['name'];
    $version  = $row['version'] ?? '-';

    if (file_exists($jsonFile)) {
        $meta    = json_decode(file_get_contents($jsonFile), true) ?: [];
        $name    = $readName($meta);
        $version = $meta['version'] ?? $version;
    }

    $id = 'widget:' . $slug;
    $widgets[] = [
        'slug'      => $slug,
        'id'        => $id,
        'name'      => $name,
        'version'   => $version,
        'licKey'    => $licMap[$id]['market_license_key'] ?? null,
        'licStatus' => $licMap[$id]['market_license_status'] ?? null,
    ];
}

// ════════════════════════════════════════════════════════════════
// 3. 스킨 스캔 헬퍼
// ════════════════════════════════════════════════════════════════
$scanSkins = function(string $dir, string $jsonFileName, string $prefix_) use ($readName, $licMap): array {
    $results = [];
    if (!is_dir($dir)) return $results;

    foreach (scandir($dir) as $slug) {
        if ($slug === '.' || $slug === '..') continue;
        $jsonFile = "{$dir}/{$slug}/{$jsonFileName}";
        if (!file_exists($jsonFile)) continue;
        $meta    = json_decode(file_get_contents($jsonFile), true) ?: [];
        $id      = $prefix_ . $slug;
        $results[] = [
            'slug'      => $slug,
            'id'        => $id,
            'name'      => $readName($meta),
            'version'   => $meta['version'] ?? '-',
            'licKey'    => $licMap[$id]['market_license_key'] ?? null,
            'licStatus' => $licMap[$id]['market_license_status'] ?? null,
        ];
    }
    return $results;
};

$layoutSkins = $scanSkins(BASE_PATH . '/skins/layouts', 'layout.json', 'layout:');
$boardSkins  = $scanSkins(BASE_PATH . '/skins/board',   'skin.json',   'board-skin:');
$pageSkins   = $scanSkins(BASE_PATH . '/skins/page',    'skin.json',   'page-skin:');

// ════════════════════════════════════════════════════════════════
// 통계
// ════════════════════════════════════════════════════════════════
$totalCount = count($plugins) + count($widgets) + count($layoutSkins) + count($boardSkins) + count($pageSkins);

// ── 라이선스 상태 뱃지 렌더 ─────────────────────────────────────
function licBadge(?string $key, ?string $status): string {
    if (!$key) return '<span class="px-2 py-0.5 text-xs rounded-full whitespace-nowrap bg-zinc-100 text-zinc-400 dark:bg-zinc-700 dark:text-zinc-500">미등록</span>';
    if ($status === 'valid')   return '<span class="px-2 py-0.5 text-xs rounded-full whitespace-nowrap bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">유효</span>';
    if ($status === 'invalid') return '<span class="px-2 py-0.5 text-xs rounded-full whitespace-nowrap bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">무효</span>';
    return '<span class="px-2 py-0.5 text-xs rounded-full whitespace-nowrap bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-400">미확인</span>';
}

function keyPreview(?string $key): string {
    if (!$key) return '<span class="text-zinc-300 dark:text-zinc-600">—</span>';
    return htmlspecialchars(substr($key, 0, 8) . '...' . substr($key, -4));
}
?>

<?php
ob_start(); ?>
<div class="flex items-center gap-3">
    <p class="text-sm text-zinc-500 dark:text-zinc-400">총 <strong class="text-zinc-700 dark:text-zinc-300"><?= $totalCount ?></strong>개 설치됨</p>
    <button onclick="validateAll()"
            class="inline-flex items-center gap-1.5 px-3 py-1.5 text-xs font-medium rounded-lg border border-zinc-200 dark:border-zinc-700 bg-white dark:bg-zinc-800 text-zinc-600 dark:text-zinc-300 hover:bg-zinc-50 dark:hover:bg-zinc-700 transition-colors">
        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15"/></svg>
        <span>전체 라이선스 갱신</span>
    </button>
</div>
<?php
$pageTitleAction = ob_get_clean();
include __DIR__ . '/_components/page-title.php';
?>

<?php
// 섹션 렌더 헬퍼
function renderSection(string $title, string $icon, array $items, string $adminUrl): void {
    if (empty($items)) return;
?>
<div class="mb-6">
    <div class="flex items-center gap-2 mb-2">
        <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24">
            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="<?= $icon ?>"/>
        </svg>
        <h3 class="text-sm font-semibold text-zinc-600 dark:text-zinc-400 uppercase tracking-wide"><?= $title ?> <span class="font-normal text-zinc-400">(<?= count($items) ?>)</span></h3>
    </div>
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
        <table class="w-full text-sm">
            <thead class="bg-zinc-50 dark:bg-zinc-700/50 text-zinc-500 dark:text-zinc-400 text-xs">
                <tr>
                    <th class="px-5 py-3 text-left font-medium">이름</th>
                    <th class="px-5 py-3 text-left font-medium w-16">버전</th>
                    <th class="px-5 py-3 text-left font-medium w-48">라이선스 키</th>
                    <th class="px-5 py-3 text-left font-medium w-20 whitespace-nowrap">상태</th>
                    <th class="px-5 py-3 text-right font-medium w-20 whitespace-nowrap">작업</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-zinc-200 dark:divide-zinc-700">
                <?php foreach ($items as $item):
                    $id = $item['id'] ?? $item['slug'];
                    $jsId = htmlspecialchars($id, ENT_QUOTES);
                    $jsSlug = htmlspecialchars($item['slug'], ENT_QUOTES);
                ?>
                <tr id="row-<?= $jsId ?>" class="hover:bg-zinc-50 dark:hover:bg-zinc-700/30">
                    <td class="px-5 py-3">
                        <p class="font-medium text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($item['name']) ?></p>
                        <p class="text-xs text-zinc-400 font-mono mt-0.5"><?= htmlspecialchars($item['slug']) ?></p>
                    </td>
                    <td class="px-5 py-3 text-xs font-mono text-zinc-400"><?= htmlspecialchars($item['version']) ?></td>
                    <td class="px-5 py-3 text-xs font-mono text-zinc-500" id="key-<?= $jsId ?>"><?= keyPreview($item['licKey']) ?></td>
                    <td class="px-5 py-3" id="status-<?= $jsId ?>"><?= licBadge($item['licKey'], $item['licStatus']) ?></td>
                    <td class="px-5 py-3 text-right">
                        <?php if (!$item['licKey']): ?>
                        <button onclick="registerLicense('<?= $jsId ?>', '<?= $jsSlug ?>', this)"
                                data-auto-register data-id="<?= $jsId ?>" data-slug="<?= $jsSlug ?>"
                                class="px-3 py-1 text-xs font-medium bg-indigo-600 hover:bg-indigo-700 text-white rounded-lg transition-colors whitespace-nowrap">
                            등록
                        </button>
                        <?php else: ?>
                        <button onclick="validateLicense('<?= $jsId ?>', '<?= $jsSlug ?>', this)"
                                class="px-3 py-1 text-xs font-medium bg-zinc-100 dark:bg-zinc-700 hover:bg-zinc-200 dark:hover:bg-zinc-600 text-zinc-600 dark:text-zinc-300 rounded-lg transition-colors whitespace-nowrap">
                            검증
                        </button>
                        <?php endif; ?>
                    </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    </div>
</div>
<?php
}
?>

<?php renderSection('플러그인', 'M19.428 15.428a2 2 0 00-1.022-.547l-2.387-.477a6 6 0 00-3.86.517l-.318.158a6 6 0 01-3.86.517L6.05 15.21a2 2 0 00-1.806.547M8 4h8l-1 1v5.172a2 2 0 00.586 1.414l5 5c1.26 1.26.367 3.414-1.415 3.414H4.828c-1.782 0-2.674-2.154-1.414-3.414l5-5A2 2 0 009 10.172V5L8 4z', $plugins, $adminUrl); ?>
<?php renderSection('위젯', 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z', $widgets, $adminUrl); ?>
<?php renderSection('레이아웃 스킨', 'M4 5a1 1 0 011-1h14a1 1 0 011 1v2a1 1 0 01-1 1H5a1 1 0 01-1-1V5zM4 13a1 1 0 011-1h6a1 1 0 011 1v6a1 1 0 01-1 1H5a1 1 0 01-1-1v-6zM16 13a1 1 0 011-1h2a1 1 0 011 1v6a1 1 0 01-1 1h-2a1 1 0 01-1-1v-6z', $layoutSkins, $adminUrl); ?>
<?php renderSection('게시판 스킨', 'M9 5H7a2 2 0 00-2 2v12a2 2 0 002 2h10a2 2 0 002-2V7a2 2 0 00-2-2h-2M9 5a2 2 0 002 2h2a2 2 0 002-2M9 5a2 2 0 012-2h2a2 2 0 012 2', $boardSkins, $adminUrl); ?>
<?php renderSection('페이지 스킨', 'M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z', $pageSkins, $adminUrl); ?>

<?php if ($totalCount === 0): ?>
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 px-6 py-12 text-center text-zinc-400">
    <p>설치된 항목이 없습니다.</p>
    <a href="<?= $adminUrl ?>/autoinstall" class="inline-block mt-3 text-sm text-indigo-600 dark:text-indigo-400 hover:underline"><?= __('autoinstall.title') ?> →</a>
</div>
<?php endif; ?>

<script>
var _apiUrl = '<?= $adminUrl ?>/autoinstall/api';

// 페이지 로드 시 미등록 아이템 자동 등록 — 순차 처리 (market API 워커 고갈 방지)
document.addEventListener('DOMContentLoaded', function() {
    var queue = Array.from(document.querySelectorAll('button[data-auto-register]'));
    if (!queue.length) return;

    function processNext() {
        var btn = queue.shift();
        if (!btn) return;
        var id   = btn.dataset.id;
        var slug = btn.dataset.slug;
        fetch(_apiUrl, {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'action=register_license&slug=' + encodeURIComponent(slug) + '&id=' + encodeURIComponent(id),
            keepalive: true
        })
        .then(r => r.json())
        .then(function(data) {
            if (data.success) {
                var statusEl = document.getElementById('status-' + id);
                var keyEl    = document.getElementById('key-' + id);
                if (statusEl) statusEl.innerHTML = '<span class="px-2 py-0.5 text-xs rounded-full whitespace-nowrap bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">유효</span>';
                if (keyEl)    keyEl.textContent = data.key_preview || '등록됨';
                btn.textContent = '검증';
                btn.removeAttribute('data-auto-register');
                btn.onclick = function() { validateLicense(id, slug, this); };
                btn.disabled = false;
            }
        })
        .catch(function() {})
        .finally(function() {
            processNext(); // 응답 후 다음 아이템 처리
        });
    }

    processNext(); // 큐 시작
});

function registerLicense(id, slug, btn) {
    btn.disabled = true;
    btn.textContent = '등록 중…';
    fetch(_apiUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=register_license&slug=' + encodeURIComponent(slug) + '&id=' + encodeURIComponent(id)
    })
    .then(r => r.json())
    .then(data => {
        if (data.success) {
            document.getElementById('status-' + id).innerHTML = '<span class="px-2 py-0.5 text-xs rounded-full whitespace-nowrap bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">유효</span>';
            document.getElementById('key-' + id).textContent = data.key_preview || '등록됨';
            btn.textContent = '검증';
            btn.onclick = function() { validateLicense(id, slug, this); };
        } else {
            alert('라이선스 등록 실패: ' + (data.message || '알 수 없는 오류'));
            btn.disabled = false;
            btn.textContent = '등록';
        }
    })
    .catch(() => { alert('요청 실패'); btn.disabled = false; btn.textContent = '등록'; });
}

function validateLicense(id, slug, btn) {
    btn.disabled = true;
    btn.textContent = '확인 중…';
    fetch(_apiUrl, {
        method: 'POST',
        headers: {'Content-Type': 'application/x-www-form-urlencoded'},
        body: 'action=validate_license&slug=' + encodeURIComponent(slug) + '&id=' + encodeURIComponent(id)
    })
    .then(r => r.json())
    .then(data => {
        var el = document.getElementById('status-' + id);
        el.innerHTML = data.valid
            ? '<span class="px-2 py-0.5 text-xs rounded-full whitespace-nowrap bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">유효</span>'
            : '<span class="px-2 py-0.5 text-xs rounded-full whitespace-nowrap bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-400">무효</span>';
        btn.disabled = false;
        btn.textContent = '검증';
    })
    .catch(() => { btn.disabled = false; btn.textContent = '검증'; });
}

function validateAll() {
    document.querySelectorAll('button[onclick*="validateLicense"]').forEach(btn => {
        btn.click();
    });
}
</script>

<?php include __DIR__ . '/_foot.php'; ?>
