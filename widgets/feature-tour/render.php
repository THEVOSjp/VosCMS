<?php
/**
 * Feature Tour 위젯
 * 좌측 스크린샷 슬라이드 + 우측 다크 패널 (기능 소개)
 * shopmodule.io/introduction 스타일
 */
ob_start();

$currentLocale = $locale ?? ($currentLocale ?? 'ko');
$baseUrl = $baseUrl ?? '';
$_wCfg = $config ?? [];

// i18n 헬퍼
$_loc = function($val) use ($currentLocale) {
    if (!$val) return '';
    if (is_string($val)) return $val;
    if (is_array($val)) return $val[$currentLocale] ?? $val['ko'] ?? $val['en'] ?? reset($val) ?: '';
    return '';
};

$_badge = $_loc($_wCfg['badge'] ?? 'PRODUCT TOUR');
$_title = $_loc($_wCfg['title'] ?? '');
$_panelSubtitle = $_loc($_wCfg['panel_subtitle'] ?? '');
$_panelTitle = $_loc($_wCfg['panel_title'] ?? '');
$_panelDesc = $_loc($_wCfg['panel_desc'] ?? '');
$_panelBullets = $_loc($_wCfg['panel_bullets'] ?? '');
$_bullets = array_filter(array_map('trim', explode("\n", $_panelBullets)));

$_rawItems = $_wCfg['items'] ?? [];
if (is_object($_rawItems)) $_rawItems = (array)$_rawItems;
$_rawItems = array_values($_rawItems);

// slider_items 구조 (image/video/title/description) → 통합
$_items = [];
foreach ($_rawItems as $i => $it) {
    $img = $it['image'] ?? '';
    $vid = $it['video'] ?? '';
    if (!$img && !$vid) continue;
    $_items[] = [
        'badge' => 'FEATURE ' . str_pad($i + 1, 2, '0', STR_PAD_LEFT),
        'image' => $img,
        'video' => $vid,
        'name' => $it['title'] ?? $it['name'] ?? '',
        'desc' => $it['description'] ?? $it['desc'] ?? '',
    ];
}
$_total = count($_items);

if ($_total === 0) {
    $_items = [
        ['badge' => 'FEATURE 01', 'image' => '', 'video' => '', 'name' => ['ko' => '슬라이드를 추가하세요', 'en' => 'Add slides', 'ja' => 'スライドを追加してください'], 'desc' => ''],
    ];
    $_total = 1;
}

$_uid = 'ft_' . substr(md5(uniqid()), 0, 6);
?>

<style>
.ft-slide { position: absolute; inset: 0; opacity: 0; transition: opacity 0.5s ease; pointer-events: none; }
.ft-slide.active { opacity: 1; pointer-events: auto; }
.ft-progress-bar { transition: width 0.5s ease; }
.ft-dot { transition: all 0.3s ease; }
.ft-dot.active { width: 2.5rem; border-radius: 0.5rem; }
</style>

<section class="py-16 bg-gradient-to-b from-blue-50/50 via-white to-white dark:from-zinc-900 dark:via-zinc-900 dark:to-zinc-900">
    <div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

        <!-- 섹션 헤더 -->
        <?php if ($_title): ?>
        <div class="mb-10">
            <h2 class="text-3xl font-black text-zinc-900 dark:text-white"><?= htmlspecialchars($_title) ?></h2>
        </div>
        <?php endif; ?>

        <!-- 메인 콘텐츠: 좌측 스크린샷 + 우측 패널 -->
        <div class="grid grid-cols-1 lg:grid-cols-5 gap-6 items-stretch" id="<?= $_uid ?>">

            <!-- 좌측: 스크린샷 슬라이드 -->
            <div class="lg:col-span-3">
                <div class="bg-white dark:bg-zinc-800 rounded-2xl border border-zinc-200 dark:border-zinc-700 shadow-sm p-6 h-full flex flex-col">
                    <!-- 피처 뱃지 -->
                    <div class="mb-4">
                        <span class="ft-feature-badge inline-block px-4 py-1.5 text-xs font-bold tracking-widest text-blue-600 dark:text-blue-400 border-2 border-blue-200 dark:border-blue-800 rounded-full uppercase"><?= htmlspecialchars($_items[0]['badge'] ?? 'FEATURE 01') ?></span>
                    </div>

                    <!-- 스크린샷 영역 -->
                    <div class="relative flex-1 min-h-[300px] lg:min-h-[400px] rounded-xl overflow-hidden bg-zinc-100 dark:bg-zinc-700 border border-zinc-200 dark:border-zinc-600">
                        <?php foreach ($_items as $i => $item):
                            $imgUrl = $item['image'] ?? '';
                            $vidUrl = $item['video'] ?? '';
                            if ($imgUrl && !str_starts_with($imgUrl, 'http')) $imgUrl = $baseUrl . '/' . ltrim($imgUrl, '/');
                            if ($vidUrl && !str_starts_with($vidUrl, 'http')) $vidUrl = $baseUrl . '/' . ltrim($vidUrl, '/');
                        ?>
                        <div class="ft-slide <?= $i === 0 ? 'active' : '' ?>" data-index="<?= $i ?>">
                            <?php if ($vidUrl): ?>
                            <video src="<?= htmlspecialchars($vidUrl) ?>" class="w-full h-full object-contain object-center" muted loop playsinline <?= $i === 0 ? 'autoplay' : '' ?>></video>
                            <?php else: ?>
                            <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($_loc($item['name'] ?? '')) ?>" class="w-full h-full object-contain object-center">
                            <?php endif; ?>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- 기능명 + 설명 -->
                    <div class="mt-4">
                        <h3 class="ft-feature-name text-xl font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($_loc($_items[0]['name'] ?? '')) ?></h3>
                        <p class="ft-feature-desc text-sm text-zinc-500 dark:text-zinc-400 mt-1"><?= htmlspecialchars($_loc($_items[0]['desc'] ?? '')) ?></p>
                    </div>
                </div>
            </div>

            <!-- 우측: 다크 패널 -->
            <div class="lg:col-span-2">
                <div class="bg-gradient-to-br from-zinc-800 via-zinc-900 to-teal-900 dark:from-zinc-800 dark:via-zinc-900 dark:to-teal-900 rounded-2xl p-8 h-full flex flex-col text-white">
                    <!-- 서브타이틀 -->
                    <?php if ($_panelSubtitle): ?>
                    <p class="text-xs font-bold tracking-widest text-emerald-400 uppercase mb-4"><?= htmlspecialchars($_panelSubtitle) ?></p>
                    <?php endif; ?>

                    <!-- 메인 카피 -->
                    <?php if ($_panelTitle): ?>
                    <h3 class="text-2xl lg:text-3xl font-black leading-tight mb-4"><?= nl2br(htmlspecialchars($_panelTitle)) ?></h3>
                    <?php endif; ?>

                    <!-- 설명 -->
                    <?php if ($_panelDesc): ?>
                    <p class="text-sm text-zinc-300 leading-relaxed mb-6"><?= htmlspecialchars($_panelDesc) ?></p>
                    <?php endif; ?>

                    <!-- 슬라이드 프로그레스 -->
                    <div class="flex items-center gap-4 mb-4">
                        <span class="ft-current-num text-2xl font-black tabular-nums">01</span>
                        <div class="flex-1 h-0.5 bg-zinc-700 rounded-full overflow-hidden">
                            <div class="ft-progress-bar h-full bg-gradient-to-r from-emerald-400 to-teal-400 rounded-full" style="width: <?= round(1 / $_total * 100, 1) ?>%"></div>
                        </div>
                        <span class="text-2xl font-black text-zinc-500 tabular-nums"><?= str_pad($_total, 2, '0', STR_PAD_LEFT) ?></span>
                    </div>

                    <!-- 네비게이션 버튼 -->
                    <div class="flex items-center gap-3 mb-6">
                        <button class="ft-prev w-11 h-11 rounded-full border border-zinc-600 hover:border-zinc-400 flex items-center justify-center transition hover:bg-zinc-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                        </button>
                        <button class="ft-next w-11 h-11 rounded-full border border-zinc-600 hover:border-zinc-400 flex items-center justify-center transition hover:bg-zinc-800">
                            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                        </button>
                    </div>

                    <!-- 도트 인디케이터 -->
                    <div class="flex items-center gap-2 mb-8">
                        <?php for ($d = 0; $d < $_total; $d++): ?>
                        <button class="ft-dot w-8 h-2 rounded-full <?= $d === 0 ? 'active bg-gradient-to-r from-emerald-400 to-teal-400' : 'bg-zinc-600' ?>" data-index="<?= $d ?>"></button>
                        <?php endfor; ?>
                    </div>

                    <!-- 불릿 포인트 -->
                    <?php if (!empty($_bullets)): ?>
                    <div class="mt-auto space-y-3">
                        <?php foreach ($_bullets as $bullet): ?>
                        <div class="flex items-center gap-3">
                            <span class="w-2.5 h-2.5 rounded-full bg-emerald-400 shrink-0"></span>
                            <span class="text-sm text-zinc-200"><?= htmlspecialchars($bullet) ?></span>
                        </div>
                        <?php endforeach; ?>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

        </div>
    </div>
</section>

<script>
(function() {
    var container = document.getElementById('<?= $_uid ?>');
    if (!container) return;

    var slides = container.querySelectorAll('.ft-slide');
    var dots = container.querySelectorAll('.ft-dot');
    var total = <?= $_total ?>;
    var current = 0;

    // 슬라이드 데이터
    var slideData = <?= json_encode(array_map(function($item) use ($_loc) {
        return [
            'badge' => $item['badge'] ?? '',
            'name' => $_loc($item['name'] ?? ''),
            'desc' => $_loc($item['desc'] ?? ''),
        ];
    }, $_items), JSON_UNESCAPED_UNICODE) ?>;

    function goTo(idx) {
        if (idx < 0) idx = total - 1;
        if (idx >= total) idx = 0;
        current = idx;

        // 이전 슬라이드 동영상 정지
        var prevVid = slides[current === idx ? 0 : current]?.querySelector('video');
        if (prevVid) { prevVid.pause(); prevVid.currentTime = 0; }

        // 슬라이드 전환
        slides.forEach(function(s, i) {
            s.classList.toggle('active', i === idx);
        });

        // 도트
        dots.forEach(function(d, i) {
            d.classList.toggle('active', i === idx);
            d.classList.toggle('bg-gradient-to-r', i === idx);
            d.classList.toggle('from-emerald-400', i === idx);
            d.classList.toggle('to-teal-400', i === idx);
            d.classList.toggle('bg-zinc-600', i !== idx);
        });

        // 프로그레스
        var pct = ((idx + 1) / total * 100).toFixed(1);
        container.querySelector('.ft-progress-bar').style.width = pct + '%';
        container.querySelector('.ft-current-num').textContent = String(idx + 1).padStart(2, '0');

        // 피처 뱃지 + 이름 + 설명
        var data = slideData[idx];
        container.querySelector('.ft-feature-badge').textContent = data.badge || ('FEATURE ' + String(idx + 1).padStart(2, '0'));
        container.querySelector('.ft-feature-name').textContent = data.name;
        container.querySelector('.ft-feature-desc').textContent = data.desc;

        // 새 슬라이드 동영상 자동재생
        var newVid = slides[idx].querySelector('video');
        if (newVid) { newVid.currentTime = 0; newVid.play().catch(function(){}); }
    }

    // 버튼 이벤트
    container.querySelector('.ft-prev').addEventListener('click', function() { goTo(current - 1); });
    container.querySelector('.ft-next').addEventListener('click', function() { goTo(current + 1); });

    // 도트 클릭
    dots.forEach(function(d) {
        d.addEventListener('click', function() { goTo(parseInt(this.dataset.index)); });
    });

    // 자동 슬라이드 (5초)
    var autoTimer = setInterval(function() { goTo(current + 1); }, 5000);

    // 호버 시 자동 슬라이드 정지
    container.addEventListener('mouseenter', function() { clearInterval(autoTimer); });
    container.addEventListener('mouseleave', function() {
        autoTimer = setInterval(function() { goTo(current + 1); }, 5000);
    });

    // 스와이프 (모바일)
    var startX = 0;
    var leftPanel = container.querySelector('.lg\\:col-span-3');
    if (leftPanel) {
        leftPanel.addEventListener('touchstart', function(e) { startX = e.touches[0].clientX; }, {passive: true});
        leftPanel.addEventListener('touchend', function(e) {
            var dx = e.changedTouches[0].clientX - startX;
            if (Math.abs(dx) > 50) goTo(current + (dx < 0 ? 1 : -1));
        }, {passive: true});
    }
})();
</script>

<?php return ob_get_clean(); ?>
