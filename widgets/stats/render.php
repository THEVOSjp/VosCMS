<?php
/**
 * Stats Widget — render.php
 * 숫자 통계 카드 (아이콘·색상·접미사·카운트업 애니메이션)
 *
 * 사용 가능 변수: $config, $widget, $renderer, $pdo, $baseUrl, $locale, $loader
 */

$stats = $config['items'] ?? [
    ['number' => '10,000', 'suffix' => '+', 'label' => __('home.stats.total_bookings'), 'color' => 'blue'],
    ['number' => '98',     'suffix' => '%', 'label' => __('home.stats.satisfaction'),   'color' => 'green'],
    ['number' => '500',    'suffix' => '+', 'label' => __('home.stats.partners'),       'color' => 'purple'],
    ['number' => '24/7',   'suffix' => '',  'label' => __('home.stats.support'),        'color' => 'orange'],
];
// 애니메이션 기본 on (0/false/빈문자만 off)
$animate = !isset($config['animate']) ? true : !in_array($config['animate'], [0, '0', false, 'false', ''], true);

$colorCls = [
    'blue'   => 'text-blue-600 dark:text-blue-400',
    'green'  => 'text-green-600 dark:text-green-400',
    'purple' => 'text-purple-600 dark:text-purple-400',
    'red'    => 'text-red-600 dark:text-red-400',
    'orange' => 'text-orange-600 dark:text-orange-400',
    'indigo' => 'text-indigo-600 dark:text-indigo-400',
    'pink'   => 'text-pink-600 dark:text-pink-400',
    'teal'   => 'text-teal-600 dark:text-teal-400',
    'amber'  => 'text-amber-600 dark:text-amber-400',
    'zinc'   => 'text-zinc-600 dark:text-zinc-300',
];
$iconSvg = [
    'users'        => '<path d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0zm6 3a2 2 0 11-4 0 2 2 0 014 0zM7 10a2 2 0 11-4 0 2 2 0 014 0z"/>',
    'chart'        => '<path d="M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z"/>',
    'star'         => '<path d="M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z"/>',
    'heart'        => '<path d="M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z"/>',
    'check-circle' => '<path d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    'clock'        => '<path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    'calendar'     => '<path d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>',
    'globe'        => '<path d="M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9m-9 9a9 9 0 019-9"/>',
    'shield'       => '<path d="M9 12l2 2 4-4M12 2l9 4v6c0 5-4 9-9 10-5-1-9-5-9-10V6l9-4z"/>',
    'lightning'    => '<path d="M13 10V3L4 14h7v7l9-11h-7z"/>',
    'trophy'       => '<path d="M19 13a7 7 0 11-14 0m14 0H5m14 0l-2-7H7l-2 7m7 0v8m-4 0h8"/>',
    'cart'         => '<path d="M3 3h2l.4 2M7 13h10l4-8H5.4M7 13L5.4 5M7 13l-2.293 2.293c-.63.63-.184 1.707.707 1.707H17m0 0a2 2 0 100 4 2 2 0 000-4zm-8 2a2 2 0 11-4 0 2 2 0 014 0z"/>',
    'dollar'       => '<path d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>',
    'building'     => '<path d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/>',
    'chat'         => '<path d="M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z"/>',
];

$_loc = $locale ?? ($config['locale'] ?? 'ko');
$_defaultLoc = $_ENV['DEFAULT_LOCALE'] ?? 'ko';

$items = '';
foreach ($stats as $i => $s) {
    $rawNum = (string)($s['number'] ?? '');
    $suffix = htmlspecialchars((string)($s['suffix'] ?? ''));
    $icon   = (string)($s['icon'] ?? '');
    $color  = (string)($s['color'] ?? 'blue');
    $colorCl = $colorCls[$color] ?? $colorCls['blue'];

    // label i18n
    $raw = $s['label'] ?? '';
    if (is_array($raw)) {
        $chain = [$_loc];
        if ($_loc !== 'en') $chain[] = 'en';
        if ($_loc !== $_defaultLoc && $_defaultLoc !== 'en') $chain[] = $_defaultLoc;
        $lbl = '';
        foreach ($chain as $lc) { if (!empty($raw[$lc])) { $lbl = $raw[$lc]; break; } }
    } else { $lbl = (string)$raw; }
    $lbl = htmlspecialchars($lbl);

    // 카운트업 가능 여부 — 순수 숫자(콤마 허용)만 대상, 24/7 같은 값은 그대로
    $numClean = preg_replace('/[^0-9.]/', '', $rawNum);
    $isCountable = $animate && $numClean !== '' && is_numeric($numClean) && strpos($rawNum, '/') === false;
    $displayNum = htmlspecialchars($rawNum);

    $iconHtml = '';
    if ($icon !== '' && isset($iconSvg[$icon])) {
        $iconHtml = '<svg class="w-10 h-10 mx-auto mb-3 ' . $colorCl . '" fill="none" stroke="currentColor" stroke-width="1.8" viewBox="0 0 24 24" stroke-linecap="round" stroke-linejoin="round">' . $iconSvg[$icon] . '</svg>';
    }

    if ($isCountable) {
        $target = htmlspecialchars($numClean);
        $items .= '<div class="rzx-stat-item" data-stat-delay="' . ($i * 100) . '">'
            . $iconHtml
            . '<p class="text-4xl font-bold ' . $colorCl . '"><span class="rzx-stat-num" data-target="' . $target . '" data-raw="' . htmlspecialchars($rawNum) . '">' . htmlspecialchars($rawNum) . '</span><span>' . $suffix . '</span></p>'
            . '<p class="text-sm text-gray-600 dark:text-zinc-400 mt-1">' . $lbl . '</p>'
            . '</div>';
    } else {
        $items .= '<div class="rzx-stat-item" data-stat-delay="' . ($i * 100) . '">'
            . $iconHtml
            . '<p class="text-4xl font-bold ' . $colorCl . '">' . $displayNum . $suffix . '</p>'
            . '<p class="text-sm text-gray-600 dark:text-zinc-400 mt-1">' . $lbl . '</p>'
            . '</div>';
    }
}

$scriptTag = '';
if ($animate) {
    $scriptTag = <<<'JS'
<script>
(function() {
    var root = document.currentScript.previousElementSibling;
    if (!root) return;
    var items = root.querySelectorAll('.rzx-stat-item');
    if (!items.length) return;

    function animateNum(el) {
        var target = parseFloat(el.dataset.target);
        var raw = el.dataset.raw || '';
        if (!isFinite(target)) { el.textContent = raw; return; }
        var hasDecimal = (target % 1) !== 0;
        var hasComma = /,/.test(raw);
        var start = performance.now();
        var duration = 1400;
        function step(now) {
            var p = Math.min(1, (now - start) / duration);
            var eased = 1 - Math.pow(1 - p, 3);
            var v = target * eased;
            var text = hasDecimal ? v.toFixed(1) : Math.floor(v).toString();
            if (hasComma) text = parseFloat(text).toLocaleString('en-US');
            el.textContent = text;
            if (p < 1) requestAnimationFrame(step);
        }
        el.textContent = '0';
        requestAnimationFrame(step);
    }

    if (!('IntersectionObserver' in window)) {
        items.forEach(function(it) { var n = it.querySelector('.rzx-stat-num'); if (n) animateNum(n); });
        return;
    }
    var io = new IntersectionObserver(function(entries) {
        entries.forEach(function(e) {
            if (!e.isIntersecting) return;
            var item = e.target;
            var delay = parseInt(item.dataset.statDelay || '0', 10);
            setTimeout(function() {
                var numEl = item.querySelector('.rzx-stat-num');
                if (numEl) animateNum(numEl);
            }, delay);
            io.unobserve(item);
        });
    }, { threshold: 0.2 });
    items.forEach(function(it) { io.observe(it); });
})();
</script>
JS;
}

return '<section class="py-16 bg-white dark:bg-zinc-800">'
    . '<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">'
    . '<div class="grid grid-cols-2 md:grid-cols-4 gap-8 text-center">' . $items . '</div>'
    . '</div>' . $scriptTag
    . '</section>';
