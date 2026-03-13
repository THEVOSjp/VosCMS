<?php
/**
 * Features Widget - render.php
 * config 기반 동적 렌더링 (feature_items 배열)
 */

// 아이콘 매핑 (key → SVG path)
$iconMap = [
    'mobile'       => 'M12 18h.01M8 21h8a2 2 0 002-2V5a2 2 0 00-2-2H8a2 2 0 00-2 2v14a2 2 0 002 2z',
    'check-circle' => 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z',
    'credit-card'  => 'M3 10h18M7 15h1m4 0h1m-7 4h12a3 3 0 003-3V8a3 3 0 00-3-3H6a3 3 0 00-3 3v8a3 3 0 003 3z',
    'calendar'     => 'M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z',
    'clock'        => 'M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z',
    'users'        => 'M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z',
    'shield'       => 'M9 12l2 2 4-4m5.618-4.016A11.955 11.955 0 0112 2.944a11.955 11.955 0 01-8.618 3.04A12.02 12.02 0 003 9c0 5.591 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.042-.133-2.052-.382-3.016z',
    'star'         => 'M11.049 2.927c.3-.921 1.603-.921 1.902 0l1.519 4.674a1 1 0 00.95.69h4.915c.969 0 1.371 1.24.588 1.81l-3.976 2.888a1 1 0 00-.363 1.118l1.518 4.674c.3.922-.755 1.688-1.538 1.118l-3.976-2.888a1 1 0 00-1.176 0l-3.976 2.888c-.783.57-1.838-.197-1.538-1.118l1.518-4.674a1 1 0 00-.363-1.118l-3.976-2.888c-.784-.57-.38-1.81.588-1.81h4.914a1 1 0 00.951-.69l1.519-4.674z',
    'globe'        => 'M21 12a9 9 0 01-9 9m9-9a9 9 0 00-9-9m9 9H3m9 9a9 9 0 01-9-9m9 9c1.657 0 3-4.03 3-9s-1.343-9-3-9m0 18c-1.657 0-3-4.03-3-9s1.343-9 3-9',
    'chart'        => 'M9 19v-6a2 2 0 00-2-2H5a2 2 0 00-2 2v6a2 2 0 002 2h2a2 2 0 002-2zm0 0V9a2 2 0 012-2h2a2 2 0 012 2v10m-6 0a2 2 0 002 2h2a2 2 0 002-2m0 0V5a2 2 0 012-2h2a2 2 0 012 2v14a2 2 0 01-2 2h-2a2 2 0 01-2-2z',
    'cog'          => 'M10.325 4.317c.426-1.756 2.924-1.756 3.35 0a1.724 1.724 0 002.573 1.066c1.543-.94 3.31.826 2.37 2.37a1.724 1.724 0 001.066 2.573c1.756.426 1.756 2.924 0 3.35a1.724 1.724 0 00-1.066 2.573c.94 1.543-.826 3.31-2.37 2.37a1.724 1.724 0 00-2.573 1.066c-.426 1.756-2.924 1.756-3.35 0a1.724 1.724 0 00-2.573-1.066c-1.543.94-3.31-.826-2.37-2.37a1.724 1.724 0 00-1.066-2.573c-1.756-.426-1.756-2.924 0-3.35a1.724 1.724 0 001.066-2.573c-.94-1.543.826-3.31 2.37-2.37.996.608 2.296.07 2.572-1.065z',
    'lightning'    => 'M13 10V3L4 14h7v7l9-11h-7z',
    'chat'         => 'M8 12h.01M12 12h.01M16 12h.01M21 12c0 4.418-4.03 8-9 8a9.863 9.863 0 01-4.255-.949L3 20l1.395-3.72C3.512 15.042 3 13.574 3 12c0-4.418 4.03-8 9-8s9 3.582 9 8z',
    'mail'         => 'M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z',
    'heart'        => 'M4.318 6.318a4.5 4.5 0 000 6.364L12 20.364l7.682-7.682a4.5 4.5 0 00-6.364-6.364L12 7.636l-1.318-1.318a4.5 4.5 0 00-6.364 0z',
    'cube'         => 'M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4',
];

$colorList = ['blue','green','purple','red','orange','indigo','pink','teal'];

// config에서 값 추출
$featureItems = $config['feature_items'] ?? [];
$columns = $config['columns'] ?? '3';
$bgColor = $config['bg_color'] ?? '#ffffff';

// i18n 헬퍼
$loc = function($val) use ($locale) {
    if (is_string($val)) return $val;
    if (is_array($val)) return $val[$locale] ?? $val['en'] ?? $val['ko'] ?? reset($val) ?: '';
    return '';
};

// 섹션 제목
$sTitle = htmlspecialchars($loc($config['title'] ?? '') ?: __('home.features.title'));
$sSub   = htmlspecialchars($loc($config['subtitle'] ?? '') ?: __('home.features.subtitle'));

// feature_items가 비어있으면 기본 카드 3개 사용
if (empty($featureItems)) {
    $defaultIcons = ['mobile', 'check-circle', 'credit-card'];
    $defaultColors = ['blue', 'green', 'purple'];
    $defaultKeys = ['mobile', 'realtime', 'easy_payment'];
    $featureItems = [];
    for ($i = 0; $i < 3; $i++) {
        $featureItems[] = [
            'icon'  => $defaultIcons[$i],
            'color' => $defaultColors[$i],
            'title' => __('home.features.' . $defaultKeys[$i] . '.title'),
            'description' => __('home.features.' . $defaultKeys[$i] . '.desc'),
        ];
    }
}

// 컬럼 수 매핑
$colClass = match($columns) {
    '2' => 'md:grid-cols-2',
    '4' => 'md:grid-cols-2 lg:grid-cols-4',
    default => 'md:grid-cols-3',
};

// 배경색 스타일
$bgStyle = '';
if ($bgColor && $bgColor !== '#ffffff' && $bgColor !== '#FFFFFF') {
    $bgStyle = ' style="background-color:' . htmlspecialchars($bgColor) . '"';
}

// 카드 HTML 생성
$cards = '';
foreach ($featureItems as $item) {
    $ic = $item['icon'] ?? 'cube';
    $fc = $item['color'] ?? 'blue';
    $iTitle = htmlspecialchars($loc($item['title'] ?? ''));
    $iDesc  = htmlspecialchars($loc($item['description'] ?? ''));
    $svgPath = $iconMap[$ic] ?? $iconMap['cube'];

    $cards .= '<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm p-8 text-center hover:shadow-lg transition">'
        . '<div class="w-16 h-16 bg-' . $fc . '-100 dark:bg-' . $fc . '-900/50 rounded-xl flex items-center justify-center mx-auto mb-6">'
        . '<svg class="w-8 h-8 text-' . $fc . '-600 dark:text-' . $fc . '-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="' . $svgPath . '"/></svg>'
        . '</div>'
        . '<h3 class="text-xl font-semibold text-gray-900 dark:text-white mb-3">' . $iTitle . '</h3>'
        . '<p class="text-gray-600 dark:text-zinc-400">' . $iDesc . '</p>'
        . '</div>';
}

return '<section class="py-20"' . $bgStyle . '>'
    . '<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">'
    . '<div class="text-center mb-16">'
    . '<h2 class="text-3xl font-bold text-gray-900 dark:text-white mb-4">' . $sTitle . '</h2>'
    . '<p class="text-gray-600 dark:text-zinc-400">' . $sSub . '</p>'
    . '</div>'
    . '<div class="grid grid-cols-1 ' . $colClass . ' gap-8">' . $cards . '</div>'
    . '</div></section>';
