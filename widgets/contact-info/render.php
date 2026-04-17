<?php
/**
 * Contact Info Widget - render.php
 * 연락처 정보 + 이미지 + 소형 지도 (2열 레이아웃)
 */

$sTitle   = htmlspecialchars($renderer->t($config, 'title', ''));
$address  = trim($renderer->t($config, 'address', ''));
$phone    = trim($config['phone'] ?? '');
$email    = trim($config['email'] ?? '');
$hours    = trim($renderer->t($config, 'business_hours', ''));
$image    = trim($config['image'] ?? '');
$showMap  = ($config['show_map'] ?? 1) != 0;
$mapQuery = trim($config['map_query'] ?? '');
$layout   = $config['layout'] ?? 'right';
$cWidth   = $config['content_width'] ?? '5xl';
$bgColor  = $config['bg_color'] ?? 'transparent';
$_locale  = $locale ?? (function_exists('current_locale') ? current_locale() : 'ko');

if ($image && !str_starts_with($image, 'http') && !str_starts_with($image, '/')) {
    $image = $baseUrl . '/storage/' . $image;
} elseif ($image && str_starts_with($image, '/')) {
    $image = $baseUrl . $image;
}

$widthClass = match ($cWidth) {
    '2xl'=>'max-w-2xl','3xl'=>'max-w-3xl','4xl'=>'max-w-4xl',
    '5xl'=>'max-w-5xl','6xl'=>'max-w-6xl','7xl'=>'max-w-7xl',
    'full'=>'max-w-full', default=>'max-w-5xl',
};

// 다국어 레이블
$L = [
    'ko'=>['address'=>'주소','phone'=>'전화','email'=>'이메일','hours'=>'영업시간','directions'=>'길찾기'],
    'en'=>['address'=>'Address','phone'=>'Get in Touch','email'=>'Email','hours'=>'Hours','directions'=>'Get Directions'],
    'ja'=>['address'=>'住所','phone'=>'お電話','email'=>'メール','hours'=>'営業時間','directions'=>'道案内'],
    'zh_CN'=>['address'=>'地址','phone'=>'电话','email'=>'邮箱','hours'=>'营业时间','directions'=>'获取路线'],
    'zh_TW'=>['address'=>'地址','phone'=>'電話','email'=>'信箱','hours'=>'營業時間','directions'=>'取得路線'],
    'de'=>['address'=>'Adresse','phone'=>'Telefon','email'=>'E-Mail','hours'=>'Öffnungszeiten','directions'=>'Route'],
    'es'=>['address'=>'Dirección','phone'=>'Teléfono','email'=>'Correo','hours'=>'Horario','directions'=>'Cómo llegar'],
    'fr'=>['address'=>'Adresse','phone'=>'Téléphone','email'=>'E-mail','hours'=>'Horaires','directions'=>'Itinéraire'],
    'id'=>['address'=>'Alamat','phone'=>'Telepon','email'=>'Email','hours'=>'Jam Kerja','directions'=>'Petunjuk Arah'],
    'mn'=>['address'=>'Хаяг','phone'=>'Утас','email'=>'Имэйл','hours'=>'Ажлын цаг','directions'=>'Чиглэл'],
    'ru'=>['address'=>'Адрес','phone'=>'Телефон','email'=>'Email','hours'=>'Часы работы','directions'=>'Маршрут'],
    'tr'=>['address'=>'Adres','phone'=>'Telefon','email'=>'E-posta','hours'=>'Çalışma Saatleri','directions'=>'Yol Tarifi'],
    'vi'=>['address'=>'Địa chỉ','phone'=>'Điện thoại','email'=>'Email','hours'=>'Giờ làm việc','directions'=>'Chỉ đường'],
];
$t = $L[$_locale] ?? $L['en'];

$sectionStyle = ($bgColor && $bgColor !== 'transparent') ? 'background-color:' . htmlspecialchars($bgColor) . ';' : '';

$mapUrl = $mapQuery ? 'https://www.google.com/maps/embed/v1/place?key=AIzaSyBFw0Qbyq9zTFTd-tUY6dZWTgaQzuU17R8&q=' . urlencode($mapQuery) . '&language=' . $_locale . '&zoom=15' : '';
$directionsUrl = $mapQuery ? 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($mapQuery) : '';

// SVG 아이콘
$icons = [
    'address' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>',
    'phone' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>',
    'email' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>',
    'hours' => '<svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>',
];

// === HTML ===
$html = '<section class="py-12"' . ($sectionStyle ? ' style="' . $sectionStyle . '"' : '') . '>';
$html .= '<div class="' . $widthClass . ' mx-auto px-4 sm:px-6 lg:px-8">';

// 제목
if ($sTitle) {
    $html .= '<h2 class="text-2xl md:text-3xl font-bold text-zinc-900 dark:text-white mb-8 border-b border-zinc-200 dark:border-zinc-700 pb-4">' . $sTitle . '</h2>';
}

// 2열 레이아웃
$orderLeft  = $layout === 'left' ? 'md:order-2' : 'md:order-1';
$orderRight = $layout === 'left' ? 'md:order-1' : 'md:order-2';

$html .= '<div class="grid grid-cols-1 md:grid-cols-5 gap-8 md:gap-10">';

// === 왼쪽: 연락처 정보 (3/5) ===
$html .= '<div class="md:col-span-3 ' . $orderLeft . '">';
$html .= '<div class="space-y-5">';

// 주소
if ($address) {
    $html .= '<div class="flex gap-4">';
    $html .= '<div class="flex items-start gap-3 w-28 shrink-0"><span class="text-zinc-400 dark:text-zinc-500 mt-0.5">' . $icons['address'] . '</span><span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">' . htmlspecialchars($t['address']) . '</span></div>';
    $html .= '<div class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed whitespace-pre-line">' . htmlspecialchars($address) . '</div>';
    $html .= '</div>';
    $html .= '<div class="border-b border-dashed border-zinc-200 dark:border-zinc-700"></div>';
}

// 전화 + 이메일
if ($phone || $email) {
    $html .= '<div class="flex gap-4">';
    $html .= '<div class="flex items-start gap-3 w-28 shrink-0"><span class="text-zinc-400 dark:text-zinc-500 mt-0.5">' . $icons['phone'] . '</span><span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">' . htmlspecialchars($t['phone']) . '</span></div>';
    $html .= '<div class="text-sm text-zinc-600 dark:text-zinc-400 space-y-1">';
    if ($phone) {
        $html .= '<div><a href="tel:' . htmlspecialchars(preg_replace('/[^+0-9]/', '', $phone)) . '" class="hover:text-blue-600 dark:hover:text-blue-400 transition" style="text-decoration:none !important;color:inherit !important">' . htmlspecialchars($phone) . '</a></div>';
    }
    if ($email) {
        $html .= '<div><a href="mailto:' . htmlspecialchars($email) . '" class="hover:text-blue-600 dark:hover:text-blue-400 transition" style="text-decoration:none !important;color:inherit !important">' . htmlspecialchars($email) . '</a></div>';
    }
    $html .= '</div>';
    $html .= '</div>';
    $html .= '<div class="border-b border-dashed border-zinc-200 dark:border-zinc-700"></div>';
}

// 영업시간
if ($hours) {
    $html .= '<div class="flex gap-4">';
    $html .= '<div class="flex items-start gap-3 w-28 shrink-0"><span class="text-zinc-400 dark:text-zinc-500 mt-0.5">' . $icons['hours'] . '</span><span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">' . htmlspecialchars($t['hours']) . '</span></div>';
    $html .= '<div class="text-sm text-zinc-600 dark:text-zinc-400 leading-relaxed whitespace-pre-line">' . htmlspecialchars($hours) . '</div>';
    $html .= '</div>';
}

$html .= '</div>'; // space-y-5
$html .= '</div>'; // col-span-3

// === 오른쪽: 이미지 + 소형 지도 (2/5) ===
$html .= '<div class="md:col-span-2 ' . $orderRight . ' space-y-4">';

// 이미지
if ($image) {
    $html .= '<div class="rounded-xl overflow-hidden border border-zinc-200 dark:border-zinc-700">';
    $html .= '<img src="' . htmlspecialchars($image) . '" alt="" class="w-full h-48 md:h-56 object-cover" loading="lazy">';
    $html .= '</div>';
}

// 소형 지도
if ($showMap && $mapUrl) {
    $html .= '<div class="rounded-xl overflow-hidden border border-zinc-200 dark:border-zinc-700 relative" style="height:200px">';
    $html .= '<iframe src="' . htmlspecialchars($mapUrl) . '" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" class="absolute inset-0"></iframe>';
    $html .= '</div>';
    if ($directionsUrl) {
        $html .= '<a href="' . htmlspecialchars($directionsUrl) . '" target="_blank" rel="noopener" class="inline-flex items-center gap-1.5 text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline" style="text-decoration:none !important">';
        $html .= '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>';
        $html .= htmlspecialchars($t['directions']) . '</a>';
    }
}

$html .= '</div>'; // col-span-2

$html .= '</div>'; // grid
$html .= '</div></section>';

return $html;
