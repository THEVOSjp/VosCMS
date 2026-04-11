<?php
/**
 * Services Widget - render.php
 * DB 서비스 (vos-salon 플러그인) 또는 config 정적 데이터로 렌더링
 *
 * 사용 변수: $config, $renderer, $baseUrl, $locale, $pdo
 */

$limit      = (int)($config['count'] ?? 6);
$showPrice  = ($config['show_price'] ?? 1) != 0;
$showDur    = ($config['show_duration'] ?? 1) != 0;
$columns    = $config['columns'] ?? '3';
$bgColor    = $config['bg_color'] ?? '';
$style      = $config['style'] ?? 'card'; // card, minimal, overlay

// 다국어 제목/부제
$sTitle = htmlspecialchars($renderer->t($config, 'title', __('home.services.title') ?? 'Popular Services'));
$sSub   = htmlspecialchars($renderer->t($config, 'subtitle', __('home.services.subtitle') ?? 'Check out our most popular services'));

// 통화 설정
$currency = $config['currency'] ?? '¥';

// DB 서비스 로드 시도 (vos-salon 플러그인)
$services = [];
try {
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
    $stmt = $pdo->prepare("SELECT * FROM {$prefix}services WHERE is_active = 1 ORDER BY sort_order ASC LIMIT ?");
    $stmt->execute([$limit]);
    $services = $stmt->fetchAll(\PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    // 테이블 없음 (코어 전용) → config 정적 데이터 사용
}

// config 정적 서비스 아이템 (DB 없을 때 또는 비어있을 때)
if (empty($services) && !empty($config['service_items'])) {
    $svcItems = is_array($config['service_items']) ? array_values($config['service_items']) : [];
    foreach ($svcItems as $item) {
        if (!is_array($item)) continue;
        // name/description이 i18n 객체일 수 있음 → 로케일 폴백
        $itemName = $item['name'] ?? 'Service';
        if (is_array($itemName)) {
            $itemName = $itemName[$locale] ?? $itemName['en'] ?? $itemName['ko'] ?? reset($itemName) ?: 'Service';
        }
        $itemDesc = $item['description'] ?? '';
        if (is_array($itemDesc)) {
            $itemDesc = $itemDesc[$locale] ?? $itemDesc['en'] ?? $itemDesc['ko'] ?? reset($itemDesc) ?: '';
        }
        $services[] = [
            'name' => $itemName,
            'description' => $itemDesc,
            'price' => $item['price'] ?? 0,
            'duration' => $item['duration'] ?? 0,
            'image' => $item['image'] ?? '',
            'icon' => $item['icon'] ?? '',
            '_static' => true,
        ];
    }
}

// 데모 데이터 (서비스 없을 때)
if (empty($services)) {
    $demoServices = [
        ['name' => 'Cut & Style', 'description' => 'Professional haircut and styling tailored to your preferences.', 'price' => 5000, 'duration' => 60, 'icon' => 'M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z'],
        ['name' => 'Color Treatment', 'description' => 'Premium hair coloring with top-quality products for vibrant results.', 'price' => 12000, 'duration' => 120, 'icon' => 'M7 21a4 4 0 01-4-4V5a2 2 0 012-2h4a2 2 0 012 2v12a4 4 0 01-4 4zm0 0h12a2 2 0 002-2v-4a2 2 0 00-2-2h-2.343M11 7.343l1.657-1.657a2 2 0 012.828 0l2.829 2.829a2 2 0 010 2.828l-8.486 8.485M7 17h.01'],
        ['name' => 'Head Spa', 'description' => 'Relaxing scalp treatment that promotes healthy hair growth.', 'price' => 8000, 'duration' => 90, 'icon' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z'],
        ['name' => 'Perm', 'description' => 'Natural-looking waves and curls that last for months.', 'price' => 15000, 'duration' => 150, 'icon' => 'M13 10V3L4 14h7v7l9-11h-7z'],
        ['name' => 'Treatment', 'description' => 'Deep conditioning treatment to restore damaged hair.', 'price' => 6000, 'duration' => 45, 'icon' => 'M9.663 17h4.673M12 3v1m6.364 1.636l-.707.707M21 12h-1M4 12H3m3.343-5.657l-.707-.707m2.828 9.9a5 5 0 117.072 0l-.548.547A3.374 3.374 0 0014 18.469V19a2 2 0 11-4 0v-.531c0-.895-.356-1.754-.988-2.386l-.548-.547z'],
        ['name' => 'Shampoo & Blow', 'description' => 'Professional shampoo and blow-dry for a refreshed look.', 'price' => 3000, 'duration' => 30, 'icon' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z'],
    ];
    $services = array_slice($demoServices, 0, $limit);
}

// 다국어 이름 로드 (DB 서비스인 경우)
$_svcNameTr = [];
if (!empty($services) && empty($services[0]['_static'] ?? false) && empty($services[0]['icon'] ?? '')) {
    $_svcIds = array_column($services, 'id');
    if (!empty($_svcIds)) {
        $_trKeys = array_map(fn($id) => "service.{$id}.name", $_svcIds);
        $_descKeys = array_map(fn($id) => "service.{$id}.description", $_svcIds);
        $_allKeys = array_merge($_trKeys, $_descKeys);
        $_ph = implode(',', array_fill(0, count($_allKeys), '?'));
        try {
            $_trStmt = $pdo->prepare("SELECT lang_key, locale, content FROM {$prefix}translations WHERE lang_key IN ({$_ph})");
            $_trStmt->execute($_allKeys);
            while ($_tr = $_trStmt->fetch(\PDO::FETCH_ASSOC)) {
                $_svcNameTr[$_tr['lang_key']][$_tr['locale']] = $_tr['content'];
            }
        } catch (\Throwable $e) {}
    }
}

// 아이콘 SVG 맵 (config에서 icon 키로 사용)
$iconSvgMap = [
    'scissors' => 'M14.121 14.121a3 3 0 01-4.242 0l-1.172-1.172a3 3 0 010-4.242m5.414 5.414L19 19m-4.879-4.879L19 9.12m-9.364 9.364L5 14m4.636 4.485L5 14m4.636-4.879L5 4.757',
    'heart' => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
    'star' => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
    'lightning' => 'M13 10V3L4 14h7v7l9-11h-7z',
    'globe' => 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9',
    'sparkles' => 'M5 3v4M3 5h4M6 17v4m-2-2h4m5-16l2.286 6.857L21 12l-5.714 2.143L13 21l-2.286-6.857L5 12l5.714-2.143L13 3z',
    'shield' => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
    'clock' => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    'users' => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
    'camera' => 'M3 9a2 2 0 012-2h.93a2 2 0 001.664-.89l.812-1.22A2 2 0 0110.07 4h3.86a2 2 0 011.664.89l.812 1.22A2 2 0 0018.07 7H19a2 2 0 012 2v9a2 2 0 01-2 2H5a2 2 0 01-2-2V9z',
    'music' => 'M9 19V6l12-3v13M9 19c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zm12-3c0 1.105-1.343 2-3 2s-3-.895-3-2 1.343-2 3-2 3 .895 3 2zM9 10l12-3',
    'gift' => 'M12 8v13m0-13V6a2 2 0 112 2h-2zm0 0V5.5A2.5 2.5 0 109.5 8H12zm-7 4h14M5 12a2 2 0 110-4h14a2 2 0 110 4M5 12v7a2 2 0 002 2h10a2 2 0 002-2v-7',
];

// 그리드 컬럼 클래스
$colMap = ['2' => 'md:grid-cols-2', '3' => 'md:grid-cols-3', '4' => 'md:grid-cols-2 lg:grid-cols-4'];
$gridClass = $colMap[$columns] ?? $colMap['3'];

// 배경 스타일
$sectionBg = '';
$bgStyle = '';
if (!$bgColor || $bgColor === 'transparent') {
    // 투명 (기본)
} elseif ($bgColor === '#f9fafb') {
    $sectionBg = 'bg-gray-50 dark:bg-zinc-900';
} else {
    $bgStyle = "background-color:{$bgColor};";
}

// 카드 렌더링
$cards = '';
foreach ($services as $i => $svc) {
    $isStatic = !empty($svc['_static'] ?? false);
    $hasIcon = !empty($iconPath ?? '');
    $isDemo = $hasIcon && !$isStatic;
    // icon 키를 SVG path로 변환
    $iconPath = $iconPath ?? '';
    if ($hasIcon && isset($iconSvgMap[$iconPath])) {
        $iconPath = $iconSvgMap[$iconPath];
    }

    // 다국어 이름/설명 (DB 서비스)
    if (!$isStatic && !$isDemo && !empty($svc['id'])) {
        $_nk = "service.{$svc['id']}.name";
        $_dk = "service.{$svc['id']}.description";
        foreach ([$locale, 'en'] as $_l) {
            if (!empty($_svcNameTr[$_nk][$_l])) { $svc['name'] = $_svcNameTr[$_nk][$_l]; break; }
        }
        foreach ([$locale, 'en'] as $_l) {
            if (!empty($_svcNameTr[$_dk][$_l])) { $svc['description'] = $_svcNameTr[$_dk][$_l]; break; }
        }
    }

    $name = htmlspecialchars($svc['name'] ?? '');
    $desc = htmlspecialchars(mb_strimwidth($svc['description'] ?? '', 0, 80, '...'));
    $price = (int)($svc['price'] ?? 0);
    $dur = (int)($svc['duration'] ?? 0);
    $image = $svc['image'] ?? '';
    if ($image && !str_starts_with($image, 'http') && !str_starts_with($image, '/')) {
        $image = $baseUrl . '/storage/' . $image;
    } elseif ($image && str_starts_with($image, '/')) {
        $image = $baseUrl . $image;
    }

    // 색상 로테이션
    $colors = ['blue', 'purple', 'emerald', 'amber', 'rose', 'indigo'];
    $c = $colors[$i % count($colors)];

    if ($style === 'overlay' && $image) {
        // 오버레이 스타일
        $cards .= '<div class="group relative rounded-2xl overflow-hidden shadow-sm hover:shadow-xl transition-all duration-300">'
            . '<img src="' . htmlspecialchars($image) . '" alt="' . $name . '" class="w-full h-64 object-cover group-hover:scale-105 transition-transform duration-500">'
            . '<div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>'
            . '<div class="absolute bottom-0 left-0 right-0 p-5 text-white">'
            . '<h3 class="text-lg font-bold mb-1">' . $name . '</h3>'
            . ($desc ? '<p class="text-sm text-white/70 mb-2">' . $desc . '</p>' : '')
            . '<div class="flex items-center gap-3 text-sm">'
            . ($showPrice && $price ? '<span class="font-bold">' . $currency . number_format($price) . '</span>' : '')
            . ($showDur && $dur ? '<span class="text-white/60">' . $dur . 'min</span>' : '')
            . '</div></div></div>';
    } elseif ($style === 'minimal') {
        // 미니멀 스타일
        $cards .= '<div class="flex items-center gap-4 p-4 rounded-xl hover:bg-white dark:hover:bg-zinc-800 hover:shadow-md transition-all duration-200">'
            . '<div class="w-14 h-14 flex-shrink-0 bg-' . $c . '-100 dark:bg-' . $c . '-900/30 rounded-xl flex items-center justify-center">'
            . ($image ? '<img src="' . htmlspecialchars($image) . '" alt="" class="w-10 h-10 rounded-lg object-cover">'
                : ($isDemo ? '<svg class="w-6 h-6 text-' . $c . '-600 dark:text-' . $c . '-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="' . $iconPath . '"/></svg>'
                    : '<svg class="w-6 h-6 text-' . $c . '-600 dark:text-' . $c . '-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14.828 14.828a4 4 0 01-5.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>'))
            . '</div>'
            . '<div class="flex-1 min-w-0">'
            . '<h3 class="font-semibold text-zinc-900 dark:text-white truncate">' . $name . '</h3>'
            . '<div class="flex items-center gap-2 text-sm text-zinc-500 dark:text-zinc-400">'
            . ($showPrice && $price ? '<span class="text-' . $c . '-600 dark:text-' . $c . '-400 font-medium">' . $currency . number_format($price) . '</span>' : '')
            . ($showDur && $dur ? '<span>·</span><span>' . $dur . 'min</span>' : '')
            . '</div></div></div>';
    } else {
        // 카드 스타일 (기본)
        $imgHtml = '';
        if ($image) {
            $imgHtml = '<div class="relative overflow-hidden rounded-t-2xl">'
                . '<img src="' . htmlspecialchars($image) . '" alt="' . $name . '" class="w-full h-48 object-cover group-hover:scale-105 transition-transform duration-500">'
                . ($showPrice && $price ? '<div class="absolute top-3 right-3 px-3 py-1 bg-white/90 dark:bg-zinc-800/90 backdrop-blur-sm rounded-full text-sm font-bold text-' . $c . '-600 dark:text-' . $c . '-400">' . $currency . number_format($price) . '</div>' : '')
                . '</div>';
        } elseif ($isDemo) {
            $imgHtml = '<div class="flex items-center justify-center h-32 bg-' . $c . '-50 dark:bg-' . $c . '-900/20 rounded-t-2xl">'
                . '<svg class="w-12 h-12 text-' . $c . '-400 dark:text-' . $c . '-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="' . $iconPath . '"/></svg>'
                . '</div>';
        }

        $cards .= '<div class="group bg-white dark:bg-zinc-800 rounded-2xl shadow-sm hover:shadow-xl border border-zinc-100 dark:border-zinc-700 overflow-hidden transition-all duration-300 hover:-translate-y-1">'
            . $imgHtml
            . '<div class="p-5">'
            . '<h3 class="text-lg font-bold text-zinc-900 dark:text-white mb-2 group-hover:text-' . $c . '-600 dark:group-hover:text-' . $c . '-400 transition-colors">' . $name . '</h3>'
            . ($desc ? '<p class="text-sm text-zinc-500 dark:text-zinc-400 mb-4 line-clamp-2">' . $desc . '</p>' : '')
            . '<div class="flex items-center justify-between">'
            . '<div class="flex items-center gap-2">'
            . ($showPrice && $price && $image ? '' : ($showPrice && $price ? '<span class="text-lg font-bold text-' . $c . '-600 dark:text-' . $c . '-400">' . $currency . number_format($price) . '</span>' : ''))
            . ($showDur && $dur ? '<span class="text-xs text-zinc-400 bg-zinc-100 dark:bg-zinc-700 px-2 py-0.5 rounded-full">' . $dur . 'min</span>' : '')
            . '</div>'
            . '<svg class="w-5 h-5 text-zinc-300 dark:text-zinc-600 group-hover:text-' . $c . '-500 group-hover:translate-x-1 transition-all" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>'
            . '</div></div></div>';
    }
}

return '<section class="py-20 ' . $sectionBg . '"' . ($bgStyle ? ' style="' . $bgStyle . '"' : '') . '>'
    . '<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">'
    . '<div class="text-center mb-14">'
    . '<h2 class="text-3xl md:text-4xl font-bold text-zinc-900 dark:text-white mb-4">' . $sTitle . '</h2>'
    . '<p class="text-lg text-zinc-500 dark:text-zinc-400 max-w-2xl mx-auto">' . $sSub . '</p>'
    . '</div>'
    . '<div class="grid grid-cols-1 ' . $gridClass . ' gap-6">' . $cards . '</div>'
    . '</div></section>';
