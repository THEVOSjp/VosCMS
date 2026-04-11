<?php
/**
 * Before / After 위젯
 * 정사각형 2열 나란히 표시 → 비교 버튼 클릭 → 드래그 비교 모드
 */
ob_start();

$currentLocale = $locale ?? ($currentLocale ?? 'ko');
$baseUrl = $baseUrl ?? '';
$_wCfg = $config ?? [];

$_wLang = @include(__DIR__ . '/lang/' . $currentLocale . '.php');
if (!is_array($_wLang)) $_wLang = @include(__DIR__ . '/lang/ko.php');
if (!is_array($_wLang)) $_wLang = [];
$_wt = function($key, $default = '') use ($_wLang) { return $_wLang[$key] ?? $default; };

$_wTitle = $_wCfg['title'] ?? [];
$_title = is_array($_wTitle) ? ($_wTitle[$currentLocale] ?? $_wTitle['ko'] ?? $_wt('title')) : ($_wTitle ?: $_wt('title'));
$_source = $_wCfg['source'] ?? 'stylebook';
$_autoLimit = (int)($_wCfg['auto_limit'] ?? 5);

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$_items = [];

try {
    if (!isset($pdo)) {
        $pdo = new PDO('mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx') . ';charset=utf8mb4',
            $_ENV['DB_USERNAME'] ?? 'root', $_ENV['DB_PASSWORD'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }

    if ($_source === 'stylebook') {
        // rzx_before_afters 테이블에서 로드
        $stmt = $pdo->query("
            SELECT ba.*, s.name as shop_name, s.slug as shop_slug
            FROM {$prefix}before_afters ba
            LEFT JOIN {$prefix}shops s ON ba.shop_id = s.id
            WHERE ba.status = 'active'
            ORDER BY ba.like_count DESC, ba.created_at DESC
            LIMIT {$_autoLimit}
        ");
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $_items[] = [
                'before' => $row['before_image'], 'after' => $row['after_image'],
                'title' => $row['content'] ?? '',
                'description' => ($row['staff_name'] ?? '') . ($row['shop_name'] ? ' · ' . $row['shop_name'] : ''),
                'link' => $row['shop_slug'] ? $baseUrl . '/shop/' . $row['shop_slug'] : '',
            ];
        }
    } elseif ($_source === 'manual') {
        foreach ($_wCfg['items'] ?? [] as $mi) {
            if (!empty($mi['before_image']) && !empty($mi['after_image'])) {
                $miTitle = is_array($mi['title'] ?? '') ? ($mi['title'][$currentLocale] ?? $mi['title']['ko'] ?? '') : ($mi['title'] ?? '');
                $miDesc = is_array($mi['description'] ?? '') ? ($mi['description'][$currentLocale] ?? $mi['description']['ko'] ?? '') : ($mi['description'] ?? '');
                $_items[] = ['before' => $mi['before_image'], 'after' => $mi['after_image'], 'title' => $miTitle, 'description' => $miDesc, 'link' => $mi['link'] ?? ''];
            }
        }
    }
} catch (\Throwable $e) {}

// 샘플
if (empty($_items)) {
    $sampleImgs = glob(__DIR__ . '/../stylebook/samples/*.jpg');
    if (count($sampleImgs) >= 4) {
        for ($i = 0; $i < min(3, floor(count($sampleImgs) / 2)); $i++) {
            $_items[] = ['before' => 'widgets/stylebook/samples/' . basename($sampleImgs[$i * 2]), 'after' => 'widgets/stylebook/samples/' . basename($sampleImgs[$i * 2 + 1]), 'title' => 'Sample Style ' . ($i + 1), 'description' => 'Sample Salon', 'link' => ''];
        }
    }
}

$_uid = 'ba_' . substr(md5(uniqid()), 0, 6);
?>

<style>
.ba-card { transition: all 0.3s ease; }
.ba-box { position: relative; aspect-ratio: 1/1; overflow: hidden; }
/* 나란히 모드 */
.ba-side-view { display: grid; grid-template-columns: 1fr 1fr; gap: 2px; position: absolute; inset: 0; }
.ba-side-view img { width: 100%; height: 100%; object-fit: cover; }
.ba-side-view.hidden { display: none; }
/* 비교 모드: clip 방식 — 이미지 크기 불변, 보이는 영역만 변경 */
.ba-drag-view { position: absolute; inset: 0; display: none; }
.ba-drag-view.active { display: block; }
.ba-drag-view .ba-img-before,
.ba-drag-view .ba-img-after { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; }
.ba-drag-view .ba-img-after { z-index: 2; clip-path: inset(0 0 0 50%); }
.ba-drag-view .ba-divider { position: absolute; top: 0; bottom: 0; width: 3px; background: #fff; box-shadow: 0 0 10px rgba(0,0,0,0.4); cursor: ew-resize; z-index: 10; left: 50%; transform: translateX(-50%); }
.ba-drag-view .ba-divider-knob { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 36px; height: 36px; background: #fff; border-radius: 50%; box-shadow: 0 2px 8px rgba(0,0,0,0.25); display: flex; align-items: center; justify-content: center; }
</style>

<div class="ba-widget" id="<?= $_uid ?>">
    <div class="flex items-center justify-between mb-4">
        <h2 class="text-xl font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($_title) ?></h2>
        <div class="flex items-center gap-3">
            <a href="<?= $baseUrl ?>/styles/before-after/create" class="inline-flex items-center gap-1.5 px-3 py-1.5 bg-blue-600 text-white text-xs font-medium rounded-lg hover:bg-blue-700 transition">
                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <?= $_wt('create', '등록') ?>
            </a>
            <a href="<?= $baseUrl ?>/styles/before-after" class="text-sm text-blue-600 hover:underline"><?= $_wt('view_more', '더보기') ?> &rarr;</a>
        </div>
    </div>

    <?php if (empty($_items)): ?>
    <p class="text-sm text-zinc-400 text-center py-8"><?= $_wt('empty') ?></p>
    <?php else: ?>

    <!-- 2열 아이템 그리드 -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4">
        <?php foreach ($_items as $i => $item): ?>
        <div class="ba-card bg-white dark:bg-zinc-800 rounded-xl overflow-hidden shadow-sm border border-zinc-100 dark:border-zinc-700">
            <div class="ba-box">
                <!-- 나란히 모드 (기본) -->
                <div class="ba-side-view" id="<?= $_uid ?>_side_<?= $i ?>">
                    <div class="relative">
                        <img src="<?= $baseUrl . '/' . ltrim($item['before'], '/') ?>" alt="Before">
                        <span class="absolute top-2 left-2 text-[10px] px-2 py-0.5 bg-black/50 text-white rounded-full"><?= $_wt('before') ?></span>
                    </div>
                    <div class="relative">
                        <img src="<?= $baseUrl . '/' . ltrim($item['after'], '/') ?>" alt="After">
                        <span class="absolute top-2 right-2 text-[10px] px-2 py-0.5 bg-black/50 text-white rounded-full"><?= $_wt('after') ?></span>
                    </div>
                </div>

                <!-- 드래그 비교 모드: clip-path 방식 -->
                <div class="ba-drag-view" id="<?= $_uid ?>_drag_<?= $i ?>">
                    <img src="<?= $baseUrl . '/' . ltrim($item['before'], '/') ?>" class="ba-img-before" alt="Before">
                    <img src="<?= $baseUrl . '/' . ltrim($item['after'], '/') ?>" class="ba-img-after" alt="After">
                    <span class="absolute top-2 left-2 text-[10px] px-2 py-0.5 bg-black/50 text-white rounded-full z-20"><?= $_wt('before') ?></span>
                    <span class="absolute top-2 right-2 text-[10px] px-2 py-0.5 bg-black/50 text-white rounded-full z-20"><?= $_wt('after') ?></span>
                    <div class="ba-divider">
                        <div class="ba-divider-knob">
                            <svg class="w-4 h-4 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        </div>
                    </div>
                </div>
            </div>

            <!-- 하단: 정보 + 토글 버튼 -->
            <div class="p-3 flex items-center justify-between">
                <div class="flex-1 min-w-0 mr-2">
                    <?php if ($item['title']): ?><p class="text-sm font-medium text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($item['title']) ?></p><?php endif; ?>
                    <?php if ($item['description']): ?><p class="text-[11px] text-zinc-500 truncate"><?= htmlspecialchars($item['description']) ?></p><?php endif; ?>
                </div>
                <div class="flex items-center gap-2 flex-shrink-0">
                    <button onclick="baToggle('<?= $_uid ?>', <?= $i ?>)" class="ba-toggle-btn px-3 py-1.5 text-[11px] font-medium border border-zinc-300 dark:border-zinc-600 rounded-full hover:bg-zinc-50 dark:hover:bg-zinc-700 transition flex items-center gap-1">
                        <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                        <span class="ba-toggle-label"><?= $_wt('compare', '비교') ?></span>
                    </button>
                    <?php if ($item['link']): ?>
                    <a href="<?= htmlspecialchars($item['link']) ?>" class="px-3 py-1.5 text-[11px] border border-zinc-300 dark:border-zinc-600 rounded-full hover:bg-zinc-50 dark:hover:bg-zinc-700 transition"><?= $_wt('view_shop') ?></a>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>
    <?php endif; ?>
</div>

<script>
(function() {
    var compareLabel = '<?= $_wt('compare', '비교') ?>';
    var sideLabel = '<?= $_wt('side_by_side', '나란히') ?>';

    window.baToggle = function(uid, idx) {
        var side = document.getElementById(uid + '_side_' + idx);
        var drag = document.getElementById(uid + '_drag_' + idx);
        var btn = side.parentElement.querySelector('.ba-toggle-label');
        if (drag.classList.contains('active')) {
            drag.classList.remove('active');
            side.classList.remove('hidden');
            if (btn) btn.textContent = compareLabel;
        } else {
            side.classList.add('hidden');
            drag.classList.add('active');
            if (btn) btn.textContent = sideLabel;
            initDrag(drag);
        }
    };

    function initDrag(el) {
        if (el._initialized) return;
        el._initialized = true;
        var divider = el.querySelector('.ba-divider');
        var afterImg = el.querySelector('.ba-img-after');
        var dragging = false;

        function move(x) {
            var rect = el.getBoundingClientRect();
            var pct = Math.max(0, Math.min(1, (x - rect.left) / rect.width));
            // After 이미지: 왼쪽 pct% 부터 보임 (Before가 pct%까지 보임)
            afterImg.style.clipPath = 'inset(0 0 0 ' + (pct * 100) + '%)';
            divider.style.left = (pct * 100) + '%';
        }

        divider.addEventListener('mousedown', function(e) { dragging = true; e.preventDefault(); });
        divider.addEventListener('touchstart', function() { dragging = true; }, {passive: true});
        el.addEventListener('mousemove', function(e) { if (dragging) move(e.clientX); });
        el.addEventListener('touchmove', function(e) { if (dragging) move(e.touches[0].clientX); }, {passive: true});
        document.addEventListener('mouseup', function() { dragging = false; });
        document.addEventListener('touchend', function() { dragging = false; });
        el.addEventListener('click', function(e) { if (e.target !== divider && !divider.contains(e.target)) move(e.clientX); });
    }
})();
</script>

<?php return ob_get_clean(); ?>
