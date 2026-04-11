<?php
/**
 * Hero Slider Widget - render.php
 * 이미지 슬라이드 + 사이드 네비게이션
 */

$layout   = $config['layout'] ?? 'slide-nav';
$heightCfg = $config['height'] ?? 'lg';
$autoplay = ($config['autoplay'] ?? 1) != 0;
$interval = max(1000, (int)($config['interval'] ?? 5000));
$showDots = ($config['show_dots'] ?? 1) != 0;
$showArrows = ($config['show_arrows'] ?? 1) != 0;
$overlay  = (int)($config['overlay'] ?? 20);
$navColor = $config['nav_color'] ?? '#ffffff';

$heightMap = ['sm' => '300px', 'md' => '450px', 'lg' => '600px', 'xl' => '700px'];
$h = $heightMap[$heightCfg] ?? '600px';

// i18n 헬퍼
$loc = function($val) use ($locale) {
    if (!$val) return '';
    if (is_string($val)) return $val;
    if (is_array($val)) return $val[$locale] ?? $val['en'] ?? $val['ko'] ?? reset($val) ?: '';
    return '';
};

// v1.1 통합 구조: items[] → slides[] + navItems[] 분리
$items = $config['items'] ?? [];
if (is_object($items)) $items = (array)$items;
$items = array_values($items);

// 하위 호환: 기존 slides[] + nav_items[] 구조
if (empty($items)) {
    $oldSlides = $config['slides'] ?? [];
    $oldNavs = $config['nav_items'] ?? [];
    if (is_object($oldSlides)) $oldSlides = (array)$oldSlides;
    if (is_object($oldNavs)) $oldNavs = (array)$oldNavs;
    $oldSlides = array_values($oldSlides);
    $oldNavs = array_values($oldNavs);
    $max = max(count($oldSlides), count($oldNavs));
    for ($j = 0; $j < $max; $j++) {
        $os = $oldSlides[$j] ?? [];
        $on = $oldNavs[$j] ?? [];
        $items[] = [
            'image' => $os['image'] ?? '', 'video' => $os['video'] ?? '',
            'title' => $os['title'] ?? [], 'subtitle' => $os['subtitle'] ?? [],
            'btn_text' => $os['btn_text'] ?? [], 'btn_url' => $os['btn_url'] ?? '',
            'nav_title' => $on['title'] ?? [], 'nav_subtitle' => $on['subtitle'] ?? [], 'nav_url' => $on['url'] ?? '',
        ];
    }
}

// items에서 slides + navItems 분리
$slides = [];
$navItems = [];
foreach ($items as $item) {
    $slides[] = [
        'image' => $item['image'] ?? '', 'video' => $item['video'] ?? '',
        'title' => $item['title'] ?? [], 'subtitle' => $item['subtitle'] ?? [],
        'btn_text' => $item['btn_text'] ?? [], 'btn_url' => $item['btn_url'] ?? '',
    ];
    $navItems[] = [
        'title' => $item['nav_title'] ?? [], 'subtitle' => $item['nav_subtitle'] ?? [],
        'url' => $item['nav_url'] ?? '#',
    ];
}

// 데모 데이터 (없을 때)
if (empty($slides)) {
    $slides = [
        ['image' => '', 'video' => '', 'title' => ['ko' => '예약을 넘어, 세계로', 'en' => 'Beyond Reservations', 'ja' => '予約を超えて、世界へ'], 'subtitle' => ['ko' => '13개국어 지원 올인원 예약 솔루션', 'en' => 'All-in-one booking solution with 13 languages', 'ja' => '13カ国語対応オールインワン予約ソリューション'], 'btn_text' => ['ko' => '자세히 보기', 'en' => 'Learn More', 'ja' => '詳しく見る'], 'btn_url' => '#'],
        ['image' => '', 'video' => '', 'title' => ['ko' => '어떤 업종이든', 'en' => 'Any Industry', 'ja' => 'どんな業種でも'], 'subtitle' => ['ko' => '미용실, 클리닉, 피트니스, 레스토랑까지', 'en' => 'Salons, clinics, fitness, restaurants and more', 'ja' => '美容室、クリニック、フィットネス、レストランまで'], 'btn_text' => [], 'btn_url' => ''],
        ['image' => '', 'video' => '', 'title' => ['ko' => '지금 시작하세요', 'en' => 'Start Now', 'ja' => '今すぐ始めましょう'], 'subtitle' => ['ko' => '무료 설치 · 무제한 커스터마이징', 'en' => 'Free installation · Unlimited customization', 'ja' => '無料インストール · 無制限カスタマイズ'], 'btn_text' => ['ko' => '무료 체험', 'en' => 'Free Trial', 'ja' => '無料体験'], 'btn_url' => '#'],
    ];
    $navItems = [
        ['title' => ['ko' => '헤어살롱 예약', 'en' => 'Hair Salon', 'ja' => 'ヘアサロン予約'], 'subtitle' => [], 'url' => '#'],
        ['title' => ['ko' => '네일 · 에스테틱', 'en' => 'Nail & Aesthetic', 'ja' => 'ネイル・エステ'], 'subtitle' => [], 'url' => '#'],
        ['title' => ['ko' => '클리닉 예약', 'en' => 'Clinic Booking', 'ja' => 'クリニック予約'], 'subtitle' => ['ko' => '피부과 · 성형외과', 'en' => 'Dermatology · Plastic Surgery', 'ja' => '皮膚科・形成外科'], 'url' => '#'],
    ];
}

$uid = 'hs_' . substr(md5(uniqid()), 0, 8);
$slideCount = count($slides);

// 그라데이션 배경 색상 (이미지 없을 때)
$gradients = [
    'linear-gradient(135deg, #667eea 0%, #764ba2 100%)',
    'linear-gradient(135deg, #f093fb 0%, #f5576c 100%)',
    'linear-gradient(135deg, #4facfe 0%, #00f2fe 100%)',
    'linear-gradient(135deg, #43e97b 0%, #38f9d7 100%)',
    'linear-gradient(135deg, #fa709a 0%, #fee140 100%)',
];

// === HTML 생성 ===
$hasNav = ($layout === 'slide-nav' && !empty($navItems));
$slideWidth = $hasNav ? 'flex-1' : 'w-full';

$html = '<section class="relative overflow-hidden bg-zinc-900" style="height:' . $h . '">';
$html .= '<div class="flex h-full">';

// 슬라이드 영역
$html .= '<div class="' . $slideWidth . ' relative overflow-hidden" id="' . $uid . '">';

// 슬라이드들
$html .= '<div class="hs-track flex h-full transition-transform duration-700 ease-in-out">';
foreach ($slides as $i => $s) {
    $img = $s['image'] ?? '';
    $video = $s['video'] ?? '';
    if ($img && !str_starts_with($img, 'http') && !str_starts_with($img, '/')) $img = $baseUrl . '/storage/' . $img;
    elseif ($img && str_starts_with($img, '/')) $img = $baseUrl . $img;
    if ($video && !str_starts_with($video, 'http') && !str_starts_with($video, '//')) {
        if (!str_starts_with($video, '/')) $video = $baseUrl . '/storage/' . $video;
        else $video = $baseUrl . $video;
    }

    $title = htmlspecialchars($loc($s['title'] ?? ''));
    $sub   = htmlspecialchars($loc($s['subtitle'] ?? ''));
    $btnText = $loc($s['btn_text'] ?? '');
    $btnUrl  = $s['btn_url'] ?? '';

    // YouTube/Vimeo ID 추출
    $ytId = $renderer->extractYouTubeId($video ?: '');
    $vimeoId = $renderer->extractVimeoId($video ?: '');

    if ($video) {
        // 비디오 슬라이드 — 그라데이션 배경 + 비디오 오버레이
        $bgStyle = 'background:' . $gradients[$i % count($gradients)] . ';';
    } elseif ($img) {
        $bgStyle = "background-image:url('" . htmlspecialchars($img) . "');background-size:cover;background-position:center;";
    } else {
        $bgStyle = 'background:' . $gradients[$i % count($gradients)] . ';';
    }

    $html .= '<div class="hs-slide flex-shrink-0 w-full h-full relative" style="' . $bgStyle . '">';

    // 비디오 배경 렌더링
    if ($video) {
        $iframeStyle = 'pointer-events:none;position:absolute;top:50%;left:50%;width:max(100%,177.78vh);height:max(100%,56.25vw);transform:translate(-50%,-50%);';
        if ($ytId) {
            $html .= '<div class="absolute inset-0 overflow-hidden z-[1]"><iframe src="https://www.youtube.com/embed/' . htmlspecialchars($ytId) . '?autoplay=1&mute=1&loop=1&playlist=' . htmlspecialchars($ytId) . '&controls=0&showinfo=0&rel=0&modestbranding=1&playsinline=1" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen style="' . $iframeStyle . '"></iframe></div>';
        } elseif ($vimeoId) {
            $html .= '<div class="absolute inset-0 overflow-hidden z-[1]"><iframe src="https://player.vimeo.com/video/' . htmlspecialchars($vimeoId) . '?autoplay=1&muted=1&loop=1&background=1" frameborder="0" allow="autoplay" allowfullscreen style="' . $iframeStyle . '"></iframe></div>';
        } else {
            $html .= '<video class="absolute inset-0 w-full h-full object-cover z-[1]" autoplay muted loop playsinline><source src="' . htmlspecialchars($video) . '"></video>';
        }
    }

    if ($overlay > 0) {
        $html .= '<div class="absolute inset-0 bg-black z-[5]" style="opacity:' . ($overlay / 100) . '"></div>';
    }
    $html .= '<div class="relative z-10 flex flex-col justify-center h-full px-8 md:px-16 max-w-2xl">';
    if ($title) $html .= '<h2 class="text-3xl md:text-5xl font-bold text-white mb-4 leading-tight">' . $title . '</h2>';
    if ($sub) $html .= '<p class="text-lg md:text-xl text-white/80 mb-6">' . $sub . '</p>';
    if ($btnText && $btnUrl) {
        $html .= '<div><a href="' . htmlspecialchars($btnUrl) . '" class="inline-flex items-center px-6 py-3 bg-white text-zinc-900 font-semibold rounded-lg hover:bg-zinc-100 transition shadow-lg">' . htmlspecialchars($btnText) . '<svg class="w-4 h-4 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a></div>';
    }
    $html .= '</div></div>';
}
$html .= '</div>';

// 화살표
if ($showArrows && $slideCount > 1) {
    $html .= '<button class="hs-prev absolute left-3 top-1/2 -translate-y-1/2 z-20 w-10 h-10 bg-black/30 hover:bg-black/60 text-white rounded-full flex items-center justify-center backdrop-blur-sm transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>';
    $html .= '<button class="hs-next absolute right-3 top-1/2 -translate-y-1/2 z-20 w-10 h-10 bg-black/30 hover:bg-black/60 text-white rounded-full flex items-center justify-center backdrop-blur-sm transition"><svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>';
}

// 도트
if ($showDots && $slideCount > 1) {
    $html .= '<div class="absolute bottom-4 left-1/2 -translate-x-1/2 z-20 flex gap-2">';
    for ($i = 0; $i < $slideCount; $i++) {
        $active = $i === 0 ? 'bg-white w-6' : 'bg-white/40 w-2';
        $html .= '<button class="hs-dot h-2 rounded-full transition-all duration-300 hover:bg-white/80 ' . $active . '" data-idx="' . $i . '"></button>';
    }
    $html .= '</div>';
}

$html .= '</div>'; // slide area end

// 사이드 네비게이션
if ($hasNav) {
    // 사이드바 색상 → 밝기 판정 (텍스트 색상 자동 결정)
    $_navR = hexdec(substr($navColor, 1, 2)); $_navG = hexdec(substr($navColor, 3, 2)); $_navB = hexdec(substr($navColor, 5, 2));
    $_navLum = (0.299 * $_navR + 0.587 * $_navG + 0.114 * $_navB) / 255;
    $_navDark = $_navLum < 0.5; // 어두운 배경이면 true
    $_navTextClass = $_navDark ? 'text-white' : 'text-zinc-800';
    $_navSubClass = $_navDark ? 'text-white/60' : 'text-zinc-400';
    $_navBorderClass = $_navDark ? 'border-white/10' : 'border-zinc-100';
    $_navHoverClass = $_navDark ? 'hover:bg-white/10' : 'hover:bg-zinc-50';
    $_navActiveClass = $_navDark ? 'bg-white/10 border-l-2 border-white' : 'bg-zinc-50 border-l-2 border-blue-500';
    $_navArrowClass = $_navDark ? 'text-white/30 group-hover:text-white/70' : 'text-zinc-300 group-hover:text-blue-500';

    $html .= '<div class="hidden lg:flex flex-col w-72 xl:w-80 overflow-y-auto" id="' . $uid . '_nav" style="background-color:' . htmlspecialchars($navColor) . '">';
    foreach ($navItems as $navIdx => $ni) {
        $nTitle = htmlspecialchars($loc($ni['title'] ?? ''));
        $nSub   = htmlspecialchars($loc($ni['subtitle'] ?? ''));
        $nUrl   = $ni['url'] ?? '#';
        $nImg   = $ni['image'] ?? '';
        if ($nImg && !str_starts_with($nImg, 'http') && !str_starts_with($nImg, '/')) $nImg = $baseUrl . '/storage/' . $nImg;
        $activeClass = $navIdx === 0 ? ' ' . $_navActiveClass : '';

        $html .= '<a href="' . htmlspecialchars($nUrl) . '" class="hs-nav-item group flex items-center justify-between px-5 py-4 border-b ' . $_navBorderClass . ' ' . $_navHoverClass . ' transition' . $activeClass . '" data-slide="' . $navIdx . '" data-active-class="' . htmlspecialchars($_navActiveClass) . '">';
        $html .= '<div class="flex items-center gap-3 min-w-0">';
        if ($nImg) {
            $html .= '<img src="' . htmlspecialchars($nImg) . '" class="w-10 h-10 rounded-lg object-cover flex-shrink-0">';
        }
        $html .= '<div class="min-w-0">';
        $html .= '<p class="font-semibold text-sm ' . $_navTextClass . ' truncate transition">' . $nTitle . '</p>';
        if ($nSub) $html .= '<p class="text-[11px] ' . $_navSubClass . ' truncate mt-0.5">' . $nSub . '</p>';
        $html .= '</div></div>';
        $html .= '<svg class="w-4 h-4 ' . $_navArrowClass . ' group-hover:translate-x-0.5 transition-all flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>';
        $html .= '</a>';
    }
    $html .= '</div>';
}

$html .= '</div>'; // flex end
$html .= '</section>';

// === JS (슬라이드 제어) ===
$html .= '<script>
(function(){
    var el = document.getElementById("' . $uid . '");
    if (!el) return;
    var track = el.querySelector(".hs-track");
    var slides = el.querySelectorAll(".hs-slide");
    var dots = el.querySelectorAll(".hs-dot");
    var navPanel = document.getElementById("' . $uid . '_nav");
    var navItems = navPanel ? navPanel.querySelectorAll(".hs-nav-item") : [];
    var total = slides.length;
    if (total <= 1) return;
    var cur = 0, timer = null, paused = false;

    function go(idx) {
        cur = ((idx % total) + total) % total;
        track.style.transform = "translateX(-" + (cur * 100) + "%)";
        // 도트 업데이트
        dots.forEach(function(d, i) {
            d.classList.toggle("bg-white", i === cur);
            d.classList.toggle("w-6", i === cur);
            d.classList.toggle("bg-white\\/40", i !== cur);
            d.classList.toggle("w-2", i !== cur);
        });
        // 네비게이션 활성 표시 (data-active-class 사용)
        navItems.forEach(function(n, i) {
            var ac = (n.dataset.activeClass || "").split(" ").filter(Boolean);
            if (i === cur) { ac.forEach(function(c){ n.classList.add(c); }); }
            else { ac.forEach(function(c){ n.classList.remove(c); }); }
        });
    }

    // 화살표
    var prev = el.querySelector(".hs-prev");
    var next = el.querySelector(".hs-next");
    if (prev) prev.addEventListener("click", function() { go(cur - 1); resetTimer(); });
    if (next) next.addEventListener("click", function() { go(cur + 1); resetTimer(); });

    // 도트 클릭
    dots.forEach(function(d) { d.addEventListener("click", function() { go(parseInt(d.dataset.idx)); resetTimer(); }); });

    // 카테고리 호버 → 해당 슬라이드로 이동
    navItems.forEach(function(n) {
        n.addEventListener("mouseenter", function() {
            var idx = parseInt(n.dataset.slide);
            if (!isNaN(idx) && idx < total) {
                go(idx);
                pauseTimer();
            }
        });
        n.addEventListener("mouseleave", function() {
            resumeTimer();
        });
    });

    // 슬라이드 이미지 호버 → 일시정지
    el.addEventListener("mouseenter", function() { pauseTimer(); });
    el.addEventListener("mouseleave", function() { resumeTimer(); });

    // Touch/swipe
    var startX = 0;
    el.addEventListener("touchstart", function(e) { startX = e.touches[0].clientX; }, {passive:true});
    el.addEventListener("touchend", function(e) {
        var diff = startX - e.changedTouches[0].clientX;
        if (Math.abs(diff) > 50) { go(diff > 0 ? cur + 1 : cur - 1); resetTimer(); }
    });

    function pauseTimer() { paused = true; if (timer) { clearInterval(timer); timer = null; } }
    function resumeTimer() { paused = false; resetTimer(); }
    function resetTimer() {
        if (timer) clearInterval(timer);
        if (paused) return;
        ' . ($autoplay ? 'timer = setInterval(function() { go(cur + 1); }, ' . $interval . ');' : '') . '
    }
    resetTimer();
})();
</script>';

return $html;
