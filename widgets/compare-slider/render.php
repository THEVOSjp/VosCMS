<?php
/**
 * 비교 슬라이더 위젯
 * 범용 — 두 이미지를 clip-path 방식으로 드래그 비교
 * 수동 입력 전용 (위젯 설정에서 이미지 등록)
 */
ob_start();

$currentLocale = $locale ?? ($currentLocale ?? 'ko');
$baseUrl = $baseUrl ?? '';
$_wCfg = $config ?? [];

$_wLang = @include(__DIR__ . '/lang/' . $currentLocale . '.php');
if (!is_array($_wLang)) $_wLang = @include(__DIR__ . '/lang/ko.php');
if (!is_array($_wLang)) $_wLang = [];
$_wt = function($key, $default = '') use ($_wLang) { return $_wLang[$key] ?? $default; };

$_items = $_wCfg['items'] ?? [];
$_columns = (int)($_wCfg['columns'] ?? 2);
$_ratio = $_wCfg['ratio'] ?? '1/1';
$_startPos = (int)($_wCfg['start_position'] ?? 50);

$_colClass = match($_columns) {
    1 => 'grid-cols-1',
    3 => 'grid-cols-1 sm:grid-cols-2 lg:grid-cols-3',
    default => 'grid-cols-1 sm:grid-cols-2',
};

// 샘플 데이터
if (empty($_items)) {
    $sampleImgs = glob(__DIR__ . '/../stylebook/samples/*.jpg');
    if (count($sampleImgs) >= 4) {
        $_items = [
            ['image_a' => 'widgets/stylebook/samples/' . basename($sampleImgs[0]), 'image_b' => 'widgets/stylebook/samples/' . basename($sampleImgs[1]), 'label_a' => 'Before', 'label_b' => 'After', 'caption' => 'Sample 1'],
            ['image_a' => 'widgets/stylebook/samples/' . basename($sampleImgs[2]), 'image_b' => 'widgets/stylebook/samples/' . basename($sampleImgs[3]), 'label_a' => 'Before', 'label_b' => 'After', 'caption' => 'Sample 2'],
        ];
    }
}

$_uid = 'cs_' . substr(md5(uniqid()), 0, 6);
?>

<style>
.cs-box { position: relative; overflow: hidden; }
.cs-box .cs-img-a, .cs-box .cs-img-b { position: absolute; top: 0; left: 0; width: 100%; height: 100%; object-fit: cover; }
.cs-box .cs-img-b { z-index: 2; }
.cs-box .cs-divider { position: absolute; top: 0; bottom: 0; width: 3px; background: #fff; box-shadow: 0 0 10px rgba(0,0,0,0.4); cursor: ew-resize; z-index: 10; transform: translateX(-50%); }
.cs-box .cs-knob { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); width: 40px; height: 40px; background: #fff; border-radius: 50%; box-shadow: 0 2px 10px rgba(0,0,0,0.25); display: flex; align-items: center; justify-content: center; }
.cs-box .cs-label { position: absolute; top: 8px; padding: 2px 10px; font-size: 11px; background: rgba(0,0,0,0.5); color: #fff; border-radius: 20px; z-index: 20; }
.cs-box .cs-label-a { left: 8px; }
.cs-box .cs-label-b { right: 8px; }
</style>

<?php if (empty($_items)): ?>
<p class="text-sm text-zinc-400 text-center py-8"><?= $_wt('empty', '비교할 이미지가 없습니다.') ?></p>
<?php else: ?>
<div class="grid <?= $_colClass ?> gap-4">
    <?php foreach ($_items as $i => $item):
        $imgA = $item['image_a'] ?? '';
        $imgB = $item['image_b'] ?? '';
        if (!$imgA || !$imgB) continue;
        $labelA = $item['label_a'] ?? 'A';
        $labelB = $item['label_b'] ?? 'B';
        $caption = is_array($item['caption'] ?? '') ? ($item['caption'][$currentLocale] ?? $item['caption']['ko'] ?? '') : ($item['caption'] ?? '');
    ?>
    <div class="bg-white dark:bg-zinc-800 rounded-xl overflow-hidden shadow-sm border border-zinc-100 dark:border-zinc-700">
        <div class="cs-box" style="aspect-ratio:<?= $_ratio ?>" id="<?= $_uid ?>_<?= $i ?>">
            <img src="<?= $baseUrl . '/' . ltrim($imgA, '/') ?>" class="cs-img-a" alt="<?= htmlspecialchars($labelA) ?>">
            <img src="<?= $baseUrl . '/' . ltrim($imgB, '/') ?>" class="cs-img-b" alt="<?= htmlspecialchars($labelB) ?>" style="clip-path: inset(0 0 0 <?= $_startPos ?>%)">
            <span class="cs-label cs-label-a"><?= htmlspecialchars($labelA) ?></span>
            <span class="cs-label cs-label-b"><?= htmlspecialchars($labelB) ?></span>
            <div class="cs-divider" style="left:<?= $_startPos ?>%">
                <div class="cs-knob">
                    <svg class="w-5 h-5 text-zinc-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7h12m0 0l-4-4m4 4l-4 4m0 6H4m0 0l4 4m-4-4l4-4"/></svg>
                </div>
            </div>
        </div>
        <?php if ($caption): ?>
        <p class="px-3 py-2 text-sm text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($caption) ?></p>
        <?php endif; ?>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>

<script>
(function() {
    document.querySelectorAll('.cs-box').forEach(function(el) {
        var divider = el.querySelector('.cs-divider');
        var imgB = el.querySelector('.cs-img-b');
        if (!divider || !imgB) return;
        var dragging = false;
        function move(x) {
            var rect = el.getBoundingClientRect();
            var pct = Math.max(0, Math.min(1, (x - rect.left) / rect.width));
            imgB.style.clipPath = 'inset(0 0 0 ' + (pct * 100) + '%)';
            divider.style.left = (pct * 100) + '%';
        }
        divider.addEventListener('mousedown', function(e) { dragging = true; e.preventDefault(); });
        divider.addEventListener('touchstart', function() { dragging = true; }, {passive: true});
        el.addEventListener('mousemove', function(e) { if (dragging) move(e.clientX); });
        el.addEventListener('touchmove', function(e) { if (dragging) move(e.touches[0].clientX); }, {passive: true});
        document.addEventListener('mouseup', function() { dragging = false; });
        document.addEventListener('touchend', function() { dragging = false; });
        el.addEventListener('click', function(e) { if (e.target !== divider && !divider.contains(e.target)) move(e.clientX); });
    });
})();
</script>

<?php return ob_get_clean(); ?>
