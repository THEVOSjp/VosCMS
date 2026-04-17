<?php
/**
 * Marketplace Showcase Widget - render.php
 * 마켓플레이스 최신 아이템을 가로 슬라이드 카드로 표시
 */

$count      = max(1, min(20, (int)($config['count'] ?? 10)));
$typeFilter = $config['type_filter'] ?? 'all';
$sort       = $config['sort'] ?? 'newest';
$showMore   = ($config['show_more_link'] ?? 1) != 0;
$bgColor    = $config['bg_color'] ?? 'transparent';

$sTitle   = htmlspecialchars($renderer->t($config, 'title', ''));
$moreText = __('common.nav.more') ?? __('common.more') ?? '더보기';
$_locale  = $locale ?? (function_exists('current_locale') ? current_locale() : 'ko');

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

// 타입 라벨/배지 색상 (13개 언어)
$typeLabels = [
    'plugin' => ['ko'=>'모듈','en'=>'Plugin','ja'=>'モジュール','zh_CN'=>'插件','zh_TW'=>'外掛','de'=>'Plugin','es'=>'Plugin','fr'=>'Plugin','id'=>'Plugin','mn'=>'Плагин','ru'=>'Плагин','tr'=>'Eklenti','vi'=>'Plugin'],
    'theme'  => ['ko'=>'테마','en'=>'Theme','ja'=>'テーマ','zh_CN'=>'主题','zh_TW'=>'佈景','de'=>'Theme','es'=>'Tema','fr'=>'Thème','id'=>'Tema','mn'=>'Загвар','ru'=>'Тема','tr'=>'Tema','vi'=>'Giao diện'],
    'widget' => ['ko'=>'위젯','en'=>'Widget','ja'=>'ウィジェット','zh_CN'=>'小部件','zh_TW'=>'小工具','de'=>'Widget','es'=>'Widget','fr'=>'Widget','id'=>'Widget','mn'=>'Виджет','ru'=>'Виджет','tr'=>'Widget','vi'=>'Widget'],
    'skin'   => ['ko'=>'스킨','en'=>'Skin','ja'=>'スキン','zh_CN'=>'皮肤','zh_TW'=>'面板','de'=>'Skin','es'=>'Skin','fr'=>'Skin','id'=>'Skin','mn'=>'Скин','ru'=>'Скин','tr'=>'Skin','vi'=>'Giao diện'],
];
$typeColors = [
    'plugin' => 'bg-blue-100 text-blue-700 dark:bg-blue-900/40 dark:text-blue-300',
    'theme'  => 'bg-purple-100 text-purple-700 dark:bg-purple-900/40 dark:text-purple-300',
    'widget' => 'bg-emerald-100 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300',
    'skin'   => 'bg-amber-100 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300',
];

// 정렬
$orderBy = match ($sort) {
    'popular' => 'download_count DESC',
    'rating'  => 'rating_avg DESC, rating_count DESC',
    default   => 'created_at DESC',
};

// 쿼리
$sql = "SELECT * FROM {$prefix}mp_items WHERE status = 'active'";
$params = [];
if ($typeFilter !== 'all') {
    $sql .= " AND type = ?";
    $params[] = $typeFilter;
}
$sql .= " ORDER BY {$orderBy} LIMIT " . (int)$count;

$items = [];
try {
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    $items = $stmt->fetchAll(\PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    // 테이블이 없을 수 있음 (마켓플레이스 플러그인 미설치)
}

// JSON 다국어 필드 추출 헬퍼
$loc = function($json, $fallback = '') use ($_locale) {
    if (!$json) return $fallback;
    $data = is_string($json) ? json_decode($json, true) : $json;
    if (!is_array($data)) return $json ?: $fallback;
    return $data[$_locale] ?? $data['en'] ?? $data['ko'] ?? reset($data) ?: $fallback;
};

// 가격 표시
$fmtPrice = function($item) use ($_locale) {
    $price = (float)($item['price'] ?? 0);
    $salePrice = $item['sale_price'] ? (float)$item['sale_price'] : null;
    $saleEnds = $item['sale_ends_at'] ?? null;
    $isOnSale = $salePrice !== null && $salePrice < $price && (!$saleEnds || strtotime($saleEnds) > time());
    if ($price <= 0) {
        return ['label' => __('common.free') ?? 'Free', 'class' => 'text-green-600 dark:text-green-400', 'original' => ''];
    }
    $currency = $item['currency'] ?? 'USD';
    $symbol = match ($currency) { 'KRW' => '₩', 'JPY' => '¥', 'EUR' => '€', default => '$' };
    $decimals = in_array($currency, ['KRW', 'JPY']) ? 0 : 2;
    if ($isOnSale) {
        return [
            'label' => $symbol . number_format($salePrice, $decimals),
            'class' => 'text-red-600 dark:text-red-400',
            'original' => $symbol . number_format($price, $decimals),
        ];
    }
    return ['label' => $symbol . number_format($price, $decimals), 'class' => 'text-zinc-800 dark:text-zinc-200', 'original' => ''];
};

$uid = 'mp-showcase-' . mt_rand(1000, 9999);
$mpUrl = $baseUrl . '/marketplace';

// 배경 스타일
$sectionStyle = '';
if ($bgColor && $bgColor !== 'transparent') {
    $sectionStyle = 'background-color:' . htmlspecialchars($bgColor) . ';';
}

// === CSS ===
$css = <<<CSS
<style>
#{$uid} .mp-scroll-container {
    display: flex;
    gap: 1rem;
    overflow-x: auto;
    scroll-snap-type: x mandatory;
    -webkit-overflow-scrolling: touch;
    scrollbar-width: none;
    padding-bottom: 4px;
}
#{$uid} .mp-scroll-container::-webkit-scrollbar { display: none; }
#{$uid} .mp-card {
    scroll-snap-align: start;
    flex: 0 0 220px;
    min-width: 220px;
    max-width: 220px;
}
@media (min-width: 640px) {
    #{$uid} .mp-card { flex: 0 0 240px; min-width: 240px; max-width: 240px; }
}
#{$uid} .mp-nav-btn {
    position: absolute;
    top: 50%;
    transform: translateY(-50%);
    z-index: 10;
    width: 36px;
    height: 36px;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    background: rgba(255,255,255,0.9);
    border: 1px solid #e4e4e7;
    box-shadow: 0 2px 8px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: opacity 0.2s, background 0.2s;
}
#{$uid} .mp-nav-btn:hover { background: #fff; }
.dark #{$uid} .mp-nav-btn {
    background: rgba(39,39,42,0.9);
    border-color: #52525b;
    color: #a1a1aa;
}
.dark #{$uid} .mp-nav-btn:hover { background: #3f3f46; color: #fff; }
#{$uid} .mp-nav-prev { left: -12px; }
#{$uid} .mp-nav-next { right: -12px; }
#{$uid} .mp-nav-btn.hidden { opacity: 0; pointer-events: none; }
</style>
CSS;

// === HTML ===
$html = $css;
$html .= '<section class="py-12"' . ($sectionStyle ? ' style="' . $sectionStyle . '"' : '') . '>';
$html .= '<div id="' . $uid . '" class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">';

// 헤더
if ($sTitle || $showMore) {
    $html .= '<div class="flex items-center justify-between mb-6">';
    if ($sTitle) {
        $html .= '<h2 class="text-xl font-bold text-zinc-900 dark:text-white border-l-4 border-blue-500 pl-3">' . $sTitle . '</h2>';
    }
    if ($showMore) {
        $html .= '<a href="' . htmlspecialchars($mpUrl) . '" class="text-sm text-zinc-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition flex items-center">'
            . htmlspecialchars($moreText)
            . '<svg class="w-3.5 h-3.5 ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></a>';
    }
    $html .= '</div>';
}

if (empty($items)) {
    $_noDataMsg = [
        'ko'=>'등록된 항목이 없습니다.','en'=>'No items yet.','ja'=>'アイテムがありません。',
        'zh_CN'=>'暂无项目。','zh_TW'=>'暫無項目。','de'=>'Noch keine Einträge.',
        'es'=>'No hay elementos.','fr'=>'Aucun élément pour le moment.',
        'id'=>'Belum ada item.','mn'=>'Бүтээгдэхүүн байхгүй байна.','ru'=>'Записей пока нет.',
        'tr'=>'Henüz öğe yok.','vi'=>'Chưa có mục nào.',
    ];
    $html .= '<p class="text-sm text-zinc-400 dark:text-zinc-500 py-8 text-center">' . ($_noDataMsg[$_locale] ?? $_noDataMsg['en']) . '</p>';
} else {
    // 슬라이드 컨테이너 + 네비게이션
    $html .= '<div class="relative">';
    $html .= '<button class="mp-nav-btn mp-nav-prev hidden" data-dir="prev" aria-label="Previous"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg></button>';
    $html .= '<button class="mp-nav-btn mp-nav-next" data-dir="next" aria-label="Next"><svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg></button>';
    $html .= '<div class="mp-scroll-container">';

    foreach ($items as $item) {
        $name     = htmlspecialchars($loc($item['name'] ?? '', $item['slug']));
        $desc     = htmlspecialchars(mb_strimwidth($loc($item['short_description'] ?? $item['description'] ?? ''), 0, 60, '...'));
        $type     = $item['type'] ?? 'plugin';
        $typeLbl  = $typeLabels[$type][$_locale] ?? $typeLabels[$type]['en'] ?? ucfirst($type);
        $typeCls  = $typeColors[$type] ?? $typeColors['plugin'];
        $author   = htmlspecialchars($item['author_name'] ?? '');
        $downloads = (int)($item['download_count'] ?? 0);
        $rating   = (float)($item['rating_avg'] ?? 0);
        $icon     = $item['icon'] ?? '';
        $banner   = $item['banner_image'] ?? '';
        $thumb    = $banner ?: $icon ?: '';
        $price    = $fmtPrice($item);
        $itemUrl  = $mpUrl . '/' . htmlspecialchars($item['slug']);

        // 판매 상태 배지
        $priceBadge = '';
        if ((float)($item['price'] ?? 0) <= 0) {
            $priceBadge = '<span class="text-[10px] px-1.5 py-0.5 rounded bg-green-100 text-green-700 dark:bg-green-900/40 dark:text-green-300">' . (__('common.free') ?? 'Free') . '</span>';
        } else {
            $priceBadge = '<span class="text-[10px] px-1.5 py-0.5 rounded bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-300">' . (__('shop.selling') ?? '판매중') . '</span>';
        }

        $html .= '<a href="' . $itemUrl . '" class="mp-card group block bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden hover:shadow-lg hover:border-zinc-300 dark:hover:border-zinc-600 transition-all">';

        // 썸네일
        $html .= '<div class="aspect-[16/10] bg-zinc-100 dark:bg-zinc-700 overflow-hidden relative">';
        if ($thumb) {
            if (!str_starts_with($thumb, 'http')) $thumb = $baseUrl . '/' . ltrim($thumb, '/');
            $html .= '<img src="' . htmlspecialchars($thumb) . '" alt="" class="w-full h-full object-cover group-hover:scale-105 transition-transform duration-300">';
        } else {
            // 플레이스홀더
            $html .= '<div class="w-full h-full flex items-center justify-center">';
            $html .= '<svg class="w-10 h-10 text-zinc-300 dark:text-zinc-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>';
            $html .= '</div>';
        }
        $html .= '</div>';

        // 카드 본문
        $html .= '<div class="p-3">';

        // 배지 행
        $html .= '<div class="flex items-center gap-1.5 mb-1.5">';
        $html .= '<span class="text-[10px] font-medium px-1.5 py-0.5 rounded ' . $typeCls . '">' . htmlspecialchars($typeLbl) . '</span>';
        $html .= $priceBadge;
        $html .= '</div>';

        // 제목
        $html .= '<h3 class="text-sm font-semibold text-zinc-900 dark:text-white truncate group-hover:text-blue-600 dark:group-hover:text-blue-400 transition">' . $name . '</h3>';

        // 설명
        if ($desc) {
            $html .= '<p class="text-[11px] text-zinc-500 dark:text-zinc-400 mt-0.5 line-clamp-2 leading-relaxed">' . $desc . '</p>';
        }

        // 하단: 저자 + 다운로드
        $html .= '<div class="flex items-center justify-between mt-2.5 pt-2 border-t border-zinc-100 dark:border-zinc-700">';
        $html .= '<span class="text-[10px] text-zinc-400 dark:text-zinc-500 truncate">' . $author . '</span>';
        $html .= '<div class="flex items-center gap-2 flex-shrink-0">';
        if ($rating > 0) {
            $html .= '<span class="flex items-center text-[10px] text-amber-500"><svg class="w-3 h-3 mr-0.5" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>' . number_format($rating, 1) . '</span>';
        }
        $html .= '<span class="flex items-center text-[10px] text-zinc-400 dark:text-zinc-500"><svg class="w-3 h-3 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4"/></svg>' . number_format($downloads) . '</span>';
        $html .= '</div>';
        $html .= '</div>';

        $html .= '</div>';
        $html .= '</a>';
    }

    $html .= '</div>'; // mp-scroll-container
    $html .= '</div>'; // relative
}

$html .= '</div></section>';

// === JS: 좌우 스크롤 네비게이션 ===
$html .= <<<JS
<script>
(function() {
    var root = document.getElementById('{$uid}');
    if (!root) return;
    var container = root.querySelector('.mp-scroll-container');
    var prev = root.querySelector('.mp-nav-prev');
    var next = root.querySelector('.mp-nav-next');
    if (!container || !prev || !next) return;

    function updateNav() {
        var sl = container.scrollLeft, sw = container.scrollWidth, cw = container.clientWidth;
        prev.classList.toggle('hidden', sl < 10);
        next.classList.toggle('hidden', sl + cw >= sw - 10);
    }

    prev.addEventListener('click', function() {
        container.scrollBy({ left: -260, behavior: 'smooth' });
    });
    next.addEventListener('click', function() {
        container.scrollBy({ left: 260, behavior: 'smooth' });
    });
    container.addEventListener('scroll', updateNav);
    updateNav();
    setTimeout(updateNav, 300);
})();
</script>
JS;

return $html;
