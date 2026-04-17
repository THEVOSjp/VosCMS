<?php
/**
 * Location Map Widget - render.php
 * 가로형 지도 + 위치·연락처 정보 카드
 */

$mapQuery    = trim($config['map_query'] ?? 'Fukuoka, Hakata');
$mapWidth    = $config['map_width'] ?? '5xl';
$mapHeight   = (int)($config['map_height'] ?? 400);

$widthClass  = match ($mapWidth) {
    '2xl' => 'max-w-2xl', '3xl' => 'max-w-3xl', '4xl' => 'max-w-4xl',
    '5xl' => 'max-w-5xl', '6xl' => 'max-w-6xl', '7xl' => 'max-w-7xl',
    'full' => 'max-w-full', default => 'max-w-5xl',
};
$companyName = trim($renderer->t($config, 'company_name', ''));
$address     = trim($renderer->t($config, 'address', ''));
$phone       = trim($config['phone'] ?? '');
$email       = trim($config['email'] ?? '');
$hours       = trim($renderer->t($config, 'business_hours', ''));
$bgColor     = $config['bg_color'] ?? 'transparent';
$_locale     = $locale ?? (function_exists('current_locale') ? current_locale() : 'ko');

$uid = 'locmap-' . mt_rand(1000, 9999);

// 다국어 레이블
$L = [
    'ko' => ['location'=>'위치','hours'=>'영업시간','contact'=>'연락처','directions'=>'길찾기'],
    'en' => ['location'=>'Location','hours'=>'Business Hours','contact'=>'Contact','directions'=>'Get Directions'],
    'ja' => ['location'=>'所在地','hours'=>'営業時間','contact'=>'お問い合わせ','directions'=>'道案内'],
    'zh_CN' => ['location'=>'位置','hours'=>'营业时间','contact'=>'联系方式','directions'=>'获取路线'],
    'zh_TW' => ['location'=>'位置','hours'=>'營業時間','contact'=>'聯繫方式','directions'=>'取得路線'],
    'de' => ['location'=>'Standort','hours'=>'Öffnungszeiten','contact'=>'Kontakt','directions'=>'Route planen'],
    'es' => ['location'=>'Ubicación','hours'=>'Horario','contact'=>'Contacto','directions'=>'Cómo llegar'],
    'fr' => ['location'=>'Emplacement','hours'=>'Horaires','contact'=>'Contact','directions'=>'Itinéraire'],
    'id' => ['location'=>'Lokasi','hours'=>'Jam Kerja','contact'=>'Kontak','directions'=>'Petunjuk Arah'],
    'mn' => ['location'=>'Байршил','hours'=>'Ажлын цаг','contact'=>'Холбоо барих','directions'=>'Чиглэл'],
    'ru' => ['location'=>'Расположение','hours'=>'Часы работы','contact'=>'Контакты','directions'=>'Маршрут'],
    'tr' => ['location'=>'Konum','hours'=>'Çalışma Saatleri','contact'=>'İletişim','directions'=>'Yol Tarifi'],
    'vi' => ['location'=>'Vị trí','hours'=>'Giờ làm việc','contact'=>'Liên hệ','directions'=>'Chỉ đường'],
];
$t = $L[$_locale] ?? $L['en'];

// 정보 카드가 있는지 판별
$hasInfo = $companyName || $address || $phone || $email || $hours;

// Google Maps embed URL
$mapUrl = 'https://www.google.com/maps/embed/v1/place?key=AIzaSyBFw0Qbyq9zTFTd-tUY6dZWTgaQzuU17R8&q=' . urlencode($mapQuery) . '&language=' . $_locale;
// Google Maps 길찾기 URL
$directionsUrl = 'https://www.google.com/maps/dir/?api=1&destination=' . urlencode($mapQuery);

$sectionStyle = ($bgColor && $bgColor !== 'transparent') ? 'background-color:' . htmlspecialchars($bgColor) . ';' : '';

$html = '<section class="py-12"' . ($sectionStyle ? ' style="' . $sectionStyle . '"' : '') . '>';
$html .= '<div class="' . $widthClass . ' mx-auto px-4 sm:px-6 lg:px-8">';

// 지도 + 정보 카드 레이아웃
$html .= '<div class="rounded-2xl overflow-hidden border border-zinc-200 dark:border-zinc-700 shadow-sm bg-white dark:bg-zinc-800">';

// 지도 (가로 전체폭)
$html .= '<div class="relative" style="height:' . $mapHeight . 'px">';
$html .= '<iframe src="' . htmlspecialchars($mapUrl) . '" width="100%" height="100%" style="border:0;" allowfullscreen="" loading="lazy" referrerpolicy="no-referrer-when-downgrade" class="absolute inset-0"></iframe>';
$html .= '</div>';

// 하단 정보 카드
if ($hasInfo) {
    $html .= '<div class="p-6 sm:p-8">';
    $html .= '<div class="grid grid-cols-1 md:grid-cols-3 gap-6 md:gap-8">';

    // 1. 위치
    if ($companyName || $address) {
        $html .= '<div>';
        $html .= '<div class="flex items-center gap-2 mb-3">';
        $html .= '<div class="w-8 h-8 bg-blue-100 dark:bg-blue-900/30 rounded-lg flex items-center justify-center flex-shrink-0"><svg class="w-4 h-4 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg></div>';
        $html .= '<h3 class="text-sm font-bold text-zinc-900 dark:text-white">' . htmlspecialchars($t['location']) . '</h3>';
        $html .= '</div>';
        if ($companyName) {
            $html .= '<div class="text-sm font-semibold text-zinc-800 dark:text-zinc-200 mb-1">' . htmlspecialchars($companyName) . '</div>';
        }
        if ($address) {
            $html .= '<div class="text-sm text-zinc-500 dark:text-zinc-400 leading-relaxed whitespace-pre-line">' . htmlspecialchars($address) . '</div>';
        }
        $html .= '<a href="' . htmlspecialchars($directionsUrl) . '" target="_blank" rel="noopener" class="inline-flex items-center gap-1 mt-3 text-xs font-medium text-blue-600 dark:text-blue-400 hover:underline" style="text-decoration:none !important">';
        $html .= '<svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 20l-5.447-2.724A1 1 0 013 16.382V5.618a1 1 0 011.447-.894L9 7m0 13l6-3m-6 3V7m6 10l4.553 2.276A1 1 0 0021 18.382V7.618a1 1 0 00-.553-.894L15 4m0 13V4m0 0L9 7"/></svg>';
        $html .= htmlspecialchars($t['directions']) . '</a>';
        $html .= '</div>';
    }

    // 2. 영업시간
    if ($hours) {
        $html .= '<div>';
        $html .= '<div class="flex items-center gap-2 mb-3">';
        $html .= '<div class="w-8 h-8 bg-emerald-100 dark:bg-emerald-900/30 rounded-lg flex items-center justify-center flex-shrink-0"><svg class="w-4 h-4 text-emerald-600 dark:text-emerald-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg></div>';
        $html .= '<h3 class="text-sm font-bold text-zinc-900 dark:text-white">' . htmlspecialchars($t['hours']) . '</h3>';
        $html .= '</div>';
        $html .= '<div class="text-sm text-zinc-500 dark:text-zinc-400 leading-relaxed whitespace-pre-line">' . htmlspecialchars($hours) . '</div>';
        $html .= '</div>';
    }

    // 3. 연락처
    if ($phone || $email) {
        $html .= '<div>';
        $html .= '<div class="flex items-center gap-2 mb-3">';
        $html .= '<div class="w-8 h-8 bg-purple-100 dark:bg-purple-900/30 rounded-lg flex items-center justify-center flex-shrink-0"><svg class="w-4 h-4 text-purple-600 dark:text-purple-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg></div>';
        $html .= '<h3 class="text-sm font-bold text-zinc-900 dark:text-white">' . htmlspecialchars($t['contact']) . '</h3>';
        $html .= '</div>';
        if ($phone) {
            $html .= '<div class="flex items-center gap-2 mb-2">';
            $html .= '<svg class="w-3.5 h-3.5 text-zinc-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>';
            $html .= '<a href="tel:' . htmlspecialchars(preg_replace('/[^+0-9]/', '', $phone)) . '" class="text-sm text-zinc-600 dark:text-zinc-300 hover:text-blue-600 dark:hover:text-blue-400" style="text-decoration:none !important">' . htmlspecialchars($phone) . '</a>';
            $html .= '</div>';
        }
        if ($email) {
            $html .= '<div class="flex items-center gap-2">';
            $html .= '<svg class="w-3.5 h-3.5 text-zinc-400 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>';
            $html .= '<a href="mailto:' . htmlspecialchars($email) . '" class="text-sm text-zinc-600 dark:text-zinc-300 hover:text-blue-600 dark:hover:text-blue-400" style="text-decoration:none !important">' . htmlspecialchars($email) . '</a>';
            $html .= '</div>';
        }
        $html .= '</div>';
    }

    $html .= '</div>'; // grid
    $html .= '</div>'; // p-6
}

$html .= '</div>'; // rounded-2xl
$html .= '</div></section>';

return $html;
