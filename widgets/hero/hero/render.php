<?php
/**
 * Hero Banner Widget - render.php
 *
 * 사용 가능한 변수:
 *   $config   - 위젯 설정 (mergedConfig)
 *   $widget   - 위젯 DB 행
 *   $renderer - WidgetRenderer 인스턴스 (t(), tBtn(), extractYouTubeId() 등)
 *   $baseUrl  - 사이트 기본 URL
 *   $locale   - 현재 로케일
 */

$title    = htmlspecialchars($renderer->t($config, 'title', __('home.hero.title_1')));
$subtitle = nl2br(htmlspecialchars($renderer->t($config, 'subtitle', __('home.hero.subtitle'))));
$layout   = $config['layout'] ?? 'center';
$height   = $config['height'] ?? 'medium';
$bgType   = $config['bg_type'] ?? 'gradient';

// 높이 클래스
$heightMap = ['small' => 'py-16', 'medium' => 'py-24', 'large' => 'py-32', 'full' => 'py-24 min-h-screen flex items-center'];
$hClass = $heightMap[$height] ?? $heightMap['medium'];

// 배경 스타일
$bgStyle = '';
$overlayHtml = '';
$bgVideoHtml = '';

if ($bgType === 'video' && !empty($config['bg_video'])) {
    $rawVideo = $config['bg_video'];
    if (!str_starts_with($rawVideo, 'http') && !str_starts_with($rawVideo, '//')) {
        $rawVideo = $baseUrl . '/' . ltrim($rawVideo, '/');
    }
    $from = htmlspecialchars($config['bg_gradient_from'] ?? '#2563eb');
    $to   = htmlspecialchars($config['bg_gradient_to'] ?? '#1e40af');
    $bgStyle = "background:linear-gradient(135deg,{$from},{$to});";
    $opacity = ($config['bg_overlay'] ?? 50) / 100;
    $overlayHtml = '<div class="absolute inset-0 bg-black z-[5]" style="opacity:' . $opacity . '"></div>';

    $ytId    = $renderer->extractYouTubeId($rawVideo);
    $vimeoId = $renderer->extractVimeoId($rawVideo);
    $iframeStyle = 'pointer-events:none;position:absolute;top:50%;left:50%;width:max(100%,177.78vh);height:max(100%,56.25vw);transform:translate(-50%,-50%);';

    if ($ytId) {
        $bgVideoHtml = '<div class="absolute inset-0 overflow-hidden z-[1]"><iframe src="https://www.youtube.com/embed/' . htmlspecialchars($ytId) . '?autoplay=1&mute=1&loop=1&playlist=' . htmlspecialchars($ytId) . '&controls=0&showinfo=0&rel=0&modestbranding=1&playsinline=1" frameborder="0" allow="autoplay; encrypted-media" allowfullscreen style="' . $iframeStyle . '"></iframe></div>';
    } elseif ($vimeoId) {
        $bgVideoHtml = '<div class="absolute inset-0 overflow-hidden z-[1]"><iframe src="https://player.vimeo.com/video/' . htmlspecialchars($vimeoId) . '?autoplay=1&muted=1&loop=1&background=1" frameborder="0" allow="autoplay" allowfullscreen style="' . $iframeStyle . '"></iframe></div>';
    } else {
        $bgVideo = htmlspecialchars($rawVideo);
        $bgVideoHtml = '<video class="absolute inset-0 w-full h-full object-cover z-[1]" autoplay muted loop playsinline><source src="' . $bgVideo . '"></video>';
    }
} elseif ($bgType === 'image' && !empty($config['bg_image'])) {
    $bgImg = htmlspecialchars($config['bg_image']);
    $bgStyle = "background-image:url('{$bgImg}');background-size:cover;background-position:center;";
    $opacity = ($config['bg_overlay'] ?? 50) / 100;
    $overlayHtml = '<div class="absolute inset-0 bg-black z-[5]" style="opacity:' . $opacity . '"></div>';
} elseif ($bgType === 'solid') {
    $bgColor = htmlspecialchars($config['bg_color'] ?? '#2563eb');
    $bgStyle = "background-color:{$bgColor};";
} else {
    $from = htmlspecialchars($config['bg_gradient_from'] ?? '#2563eb');
    $to   = htmlspecialchars($config['bg_gradient_to'] ?? '#1e40af');
    $bgStyle = "background:linear-gradient(135deg,{$from},{$to});";
}

// 버튼 렌더링
$buttons = $config['buttons'] ?? [];
if (empty($buttons)) {
    $btnText = htmlspecialchars($renderer->t($config, 'cta_text', __('home.hero.cta_booking')));
    $btnUrl  = htmlspecialchars($config['cta_url'] ?? $baseUrl . '/booking');
    $buttonsHtml = '<a data-widget-field="cta_text" href="' . $btnUrl . '" class="inline-flex items-center px-8 py-4 bg-white text-blue-600 font-semibold rounded-xl hover:bg-blue-50 transition shadow-lg">' . $btnText . '<svg class="w-5 h-5 ml-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a>';
} else {
    $buttonsHtml = '<div class="flex flex-wrap gap-4 justify-center">';
    foreach ($buttons as $btn) {
        $bText  = htmlspecialchars($renderer->tBtn($btn, 'text', 'Button'));
        $bUrl   = htmlspecialchars($btn['url'] ?? '#');
        $bStyle = $btn['style'] ?? 'primary';
        $bClass = $bStyle === 'secondary'
            ? 'px-8 py-4 border-2 border-white text-white font-semibold rounded-xl hover:bg-white/10 transition'
            : ($bStyle === 'outline'
                ? 'px-8 py-4 border border-white/50 text-white font-medium rounded-xl hover:bg-white/10 transition'
                : 'px-8 py-4 bg-white text-blue-600 font-semibold rounded-xl hover:bg-blue-50 transition shadow-lg');
        $buttonsHtml .= '<a href="' . $bUrl . '" class="inline-flex items-center ' . $bClass . '">' . $bText . '</a>';
    }
    $buttonsHtml .= '</div>';
}

// 히어로 이미지(들)
$heroImgHtml = '';
$heroImagesOverlay = '';
$heroImages = $config['hero_images'] ?? [];
if (empty($heroImages) && !empty($config['hero_image'])) {
    $heroImages = [['url' => $config['hero_image'], 'position' => 'center', 'layer' => 1, 'size' => 'medium']];
}
if (!empty($heroImages)) {
    if (($layout === 'left-image' || $layout === 'right-image') && !empty($heroImages[0]['url'])) {
        $firstImg = htmlspecialchars($heroImages[0]['url']);
        $heroImgHtml = '<div class="flex-shrink-0"><img src="' . $firstImg . '" alt="" class="w-full max-w-md rounded-2xl shadow-2xl"></div>';
        array_shift($heroImages);
    }
    foreach ($heroImages as $hi) {
        if (empty($hi['url'])) continue;
        $hiUrl   = htmlspecialchars($hi['url']);
        $hiPos   = $hi['position'] ?? 'center';
        $hiLayer = intval($hi['layer'] ?? 1);
        $hiSize  = $hi['size'] ?? 'medium';
        $sizeMap = ['small' => 'w-24 md:w-32', 'medium' => 'w-40 md:w-56', 'large' => 'w-56 md:w-80', 'full' => 'w-full'];
        $sizeClass = $sizeMap[$hiSize] ?? $sizeMap['medium'];
        $posStyle  = $renderer->heroImagePosition($hiPos);
        $heroImagesOverlay .= '<img src="' . $hiUrl . '" alt="" class="absolute ' . $sizeClass . ' object-contain pointer-events-none" style="' . $posStyle . 'z-index:' . ($hiLayer + 5) . ';">';
    }
}

// 레이아웃별 렌더링
$inner = '';
if ($layout === 'left-image' || $layout === 'right-image') {
    $textBlock = '<div class="flex-1 ' . ($layout === 'right-image' ? 'text-left' : 'text-right') . '">'
        . '<h1 data-widget-field="title" class="text-4xl md:text-5xl font-bold mb-6">' . $title . '</h1>'
        . '<p data-widget-field="subtitle" class="text-xl text-white/80 mb-8">' . $subtitle . '</p>'
        . $buttonsHtml . '</div>';
    $imgBlock = $heroImgHtml ?: '<div class="flex-1"></div>';
    $flexDir  = $layout === 'left-image' ? 'flex-row-reverse' : '';
    $inner = '<div class="w-full ' . $hClass . '"><div class="w-full flex flex-col md:flex-row items-center gap-12 px-8 ' . $flexDir . '">' . $textBlock . $imgBlock . '</div></div>';
} elseif ($layout === 'fullscreen') {
    $inner = '<div class="w-full ' . $hClass . '"><div class="w-full text-center">'
        . '<h1 data-widget-field="title" class="text-5xl md:text-6xl font-bold mb-6">' . $title . '</h1>'
        . '<p data-widget-field="subtitle" class="text-xl text-white/80 mb-8 max-w-2xl mx-auto">' . $subtitle . '</p>'
        . $buttonsHtml . '</div></div>';
} else {
    $titleStyle    = $renderer->elementPositionStyle($config, 'title');
    $subtitleStyle = $renderer->elementPositionStyle($config, 'subtitle');
    $inner = '<div class="w-full ' . $hClass . '"><div class="w-full text-center' . (!empty($config['element_positions']) ? ' relative' : '') . '">'
        . '<h1 data-widget-field="title" class="text-4xl md:text-5xl font-bold mb-6"' . ($titleStyle ? ' style="' . $titleStyle . '"' : '') . '>' . $title . '</h1>'
        . '<p data-widget-field="subtitle" class="text-xl text-white/80 mb-8 max-w-2xl mx-auto"' . ($subtitleStyle ? ' style="' . $subtitleStyle . '"' : '') . '>' . $subtitle . '</p>'
        . $buttonsHtml . '</div></div>';
}

$sectionClass = 'relative text-white overflow-hidden' . ($height === 'full' ? ' min-h-screen' : '');
return '<section class="' . $sectionClass . '" style="' . $bgStyle . '">'
    . $bgVideoHtml
    . $overlayHtml
    . $heroImagesOverlay
    . '<div class="relative z-20 w-full">' . $inner . '</div>'
    . '</section>';
