<?php
/**
 * Shop Map Widget - render.php
 * 사용자 위치 기반 주변 매장 지도 + 목록
 * Leaflet.js + OpenStreetMap (무료)
 */

// 위젯 빌더 미리보기 모드 → 플레이스홀더 표시
if (!empty($GLOBALS['_rzx_widget_preview'])) {
    return '<div style="background:#e8f4f8;border:2px dashed #60a5fa;border-radius:12px;padding:40px;text-align:center;color:#3b82f6">'
         . '<svg style="width:48px;height:48px;margin:0 auto 12px" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>'
         . '<p style="font-size:16px;font-weight:bold;margin-bottom:4px">📍 ' . htmlspecialchars($renderer->t($config, 'title', 'Nearby Shops Map')) . '</p>'
         . '<p style="font-size:12px;color:#64748b">Map preview is available on the live page</p>'
         . '</div>';
}

$sTitle    = htmlspecialchars($renderer->t($config, 'title', __('shop.map.title') ?? '내 주변 매장'));
$category  = $config['category'] ?? '';
$count     = max(1, (int)($config['count'] ?? 20));
$heightCfg = $config['height'] ?? 'md';
$showList  = ($config['show_list'] ?? 1) != 0;
$defaultLat = (float)($config['default_lat'] ?? 35.6762);
$defaultLng = (float)($config['default_lng'] ?? 139.6503);
$defaultZoom = (int)($config['default_zoom'] ?? 13);

$heightMap = ['sm' => '250px', 'md' => '350px', 'lg' => '450px'];
$mapH = $heightMap[$heightCfg] ?? '350px';

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$currentLocale = $locale ?? 'ko';

// 매장 데이터 로드 (위치 있는 매장만)
$where = ["s.status = 'active'", "s.latitude IS NOT NULL", "s.longitude IS NOT NULL"];
$params = [];
if ($category) {
    $where[] = "c.slug = ?";
    $params[] = $category;
}
$whereSQL = implode(' AND ', $where);

try {
    $stmt = $pdo->prepare("
        SELECT s.id, s.name, s.slug, s.address, s.latitude, s.longitude,
               s.cover_image, s.images, s.rating_avg, s.review_count, s.favorite_count, s.phone,
               c.slug as category_slug, c.name as category_name
        FROM {$prefix}shops s
        LEFT JOIN {$prefix}shop_categories c ON s.category_id = c.id
        WHERE {$whereSQL}
        ORDER BY s.rating_avg DESC, s.review_count DESC
        LIMIT " . (int)$count
    );
    $stmt->execute($params);
    $shops = $stmt->fetchAll(\PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $shops = [];
}

// 매장 데이터를 JS용 JSON으로 변환
$shopMarkers = [];
foreach ($shops as $s) {
    $coverImg = $s['cover_image'] ?: '';
    if (!$coverImg) {
        $imgs = json_decode($s['images'] ?? '[]', true) ?: [];
        $coverImg = $imgs[0] ?? '';
    }
    if ($coverImg && !str_starts_with($coverImg, 'http')) $coverImg = $baseUrl . $coverImg;

    $catNames = json_decode($s['category_name'] ?? '{}', true);
    $catLabel = $catNames[$currentLocale] ?? $catNames['en'] ?? $catNames['ko'] ?? '';

    $shopMarkers[] = [
        'id' => $s['id'],
        'name' => $s['name'],
        'slug' => $s['slug'],
        'lat' => (float)$s['latitude'],
        'lng' => (float)$s['longitude'],
        'address' => $s['address'] ?? '',
        'image' => $coverImg,
        'rating' => (float)$s['rating_avg'],
        'reviews' => (int)$s['review_count'],
        'favorites' => (int)$s['favorite_count'],
        'phone' => $s['phone'] ?? '',
        'category' => $catLabel,
        'url' => $baseUrl . '/shop/' . $s['slug'],
    ];
}

$uid = 'smap_' . substr(md5(uniqid()), 0, 8);
$shopsJson = json_encode($shopMarkers, JSON_UNESCAPED_UNICODE);

// 다국어
$txtNearby = __('shop.map.nearby') ?? '주변 매장';
$txtNoShops = __('shop.map.no_shops') ?? '주변에 매장이 없습니다.';
$txtMyLocation = __('shop.map.my_location') ?? '내 위치';
$txtMore = __('common.nav.more') ?? '더보기';
$txtReviews = __('shop.detail.reviews') ?? '리뷰';
$txtRegister = __('shop.list.register_cta') ?? '매장 등록하기';
?>

<?php $hasShops = !empty($shopMarkers); ?>
<section class="<?= $hasShops ? 'py-6' : 'py-4' ?>">
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8">

    <?php if ($sTitle): ?>
    <h2 class="text-lg font-bold text-zinc-900 dark:text-white mb-3"><?= $sTitle ?></h2>
    <?php endif; ?>

    <div class="<?= ($showList && $hasShops) ? 'grid grid-cols-1 lg:grid-cols-[1fr_300px] gap-3' : '' ?>">
        <!-- 지도 -->
        <div class="relative rounded-xl overflow-hidden border border-zinc-200 dark:border-zinc-700" style="height:<?= $hasShops ? $mapH : '200px' ?>">
            <div id="<?= $uid ?>" class="w-full h-full"></div>
            <!-- 내 위치 버튼 -->
            <button id="<?= $uid ?>_locate" class="absolute bottom-4 right-4 z-[1000] w-10 h-10 bg-white dark:bg-zinc-800 rounded-full shadow-lg border border-zinc-200 dark:border-zinc-600 flex items-center justify-center hover:bg-zinc-50 dark:hover:bg-zinc-700 transition" title="<?= $txtMyLocation ?>">
                <svg class="w-5 h-5 text-blue-600" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17.657 16.657L13.414 20.9a1.998 1.998 0 01-2.827 0l-4.244-4.243a8 8 0 1111.314 0z"/><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 11a3 3 0 11-6 0 3 3 0 016 0z"/></svg>
            </button>
        </div>

        <?php if ($showList && $hasShops): ?>
        <!-- 사이드 목록 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-200 dark:border-zinc-700 overflow-hidden flex flex-col lg:max-h-[<?= $mapH ?>]">
            <div class="px-4 py-2.5 border-b border-zinc-100 dark:border-zinc-700 flex items-center justify-between">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white" id="<?= $uid ?>_title">📍 <?= $txtNearby ?></h3>
                <div class="flex items-center gap-2">
                    <a href="<?= $baseUrl ?>/shop/register" class="text-[10px] px-2 py-0.5 bg-blue-600 text-white rounded-full hover:bg-blue-700 transition"><?= $txtRegister ?></a>
                    <a href="<?= $baseUrl ?>/shops<?= $category ? '/' . htmlspecialchars($category) : '' ?>" class="text-xs text-blue-600 dark:text-blue-400 hover:underline"><?= $txtMore ?> →</a>
                </div>
            </div>
            <div id="<?= $uid ?>_list" class="flex-1 overflow-y-auto">
                <!-- JS로 동적 생성 -->
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>
</section>

<!-- Leaflet -->
<link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js"></script>
<script>
(function(){
    var shops = <?= $shopsJson ?>;
    var mapEl = document.getElementById('<?= $uid ?>');
    var listEl = document.getElementById('<?= $uid ?>_list');
    var titleEl = document.getElementById('<?= $uid ?>_title');
    var locateBtn = document.getElementById('<?= $uid ?>_locate');
    if (!mapEl) return;

    var defaultLat = <?= $defaultLat ?>, defaultLng = <?= $defaultLng ?>;
    var map = L.map(mapEl).setView([defaultLat, defaultLng], <?= $defaultZoom ?>);
    L.tileLayer('https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '© OpenStreetMap', maxZoom: 19
    }).addTo(map);

    var markers = [];
    var userMarker = null;

    // 매장 마커 생성
    shops.forEach(function(s) {
        var popup = '<div style="min-width:180px">'
            + (s.image ? '<img src="' + s.image + '" style="width:100%;height:80px;object-fit:cover;border-radius:6px;margin-bottom:6px">' : '')
            + '<strong style="font-size:13px">' + s.name + '</strong>'
            + (s.rating > 0 ? '<br><span style="color:#f59e0b">⭐' + s.rating.toFixed(1) + '</span> <span style="font-size:11px;color:#999"><?= $txtReviews ?> ' + s.reviews + '</span>' : '')
            + (s.address ? '<br><span style="font-size:11px;color:#888">' + s.address + '</span>' : '')
            + '<br><a href="' + s.url + '" style="font-size:12px;color:#3b82f6;text-decoration:underline"><?= __('shop.map.view_detail') ?? '상세 보기' ?> →</a>'
            + '</div>';

        var marker = L.marker([s.lat, s.lng]).addTo(map).bindPopup(popup);
        marker._shopData = s;
        markers.push(marker);
    });

    // 사이드 목록 렌더링
    function renderList(sortedShops) {
        if (!listEl) return;
        if (sortedShops.length === 0) {
            listEl.innerHTML = '<div class="flex flex-col items-center justify-center h-full text-zinc-400 dark:text-zinc-500 text-sm"><p><?= $txtNoShops ?></p></div>';
            return;
        }
        var html = '';
        sortedShops.forEach(function(s, i) {
            var distText = s._dist ? (s._dist < 1 ? Math.round(s._dist * 1000) + 'm' : s._dist.toFixed(1) + 'km') : '';
            html += '<a href="' + s.url + '" class="flex gap-3 p-3 hover:bg-zinc-50 dark:hover:bg-zinc-700/50 transition border-b border-zinc-100 dark:border-zinc-700 last:border-0" data-idx="' + i + '">'
                + (s.image ? '<img src="' + s.image + '" class="w-14 h-14 rounded-lg object-cover flex-shrink-0">' : '<div class="w-14 h-14 rounded-lg bg-zinc-100 dark:bg-zinc-700 flex-shrink-0 flex items-center justify-center"><svg class="w-6 h-6 text-zinc-300" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5"/></svg></div>')
                + '<div class="min-w-0 flex-1">'
                + '<p class="text-sm font-semibold text-zinc-900 dark:text-white truncate">' + s.name + '</p>'
                + '<div class="flex items-center gap-1.5 text-[10px] text-zinc-400 mt-0.5">'
                + (s.rating > 0 ? '<span class="text-amber-500">⭐' + s.rating.toFixed(1) + '</span>' : '')
                + (s.reviews > 0 ? '<span>' + s.reviews + '<?= $txtReviews ?></span>' : '')
                + '<span>♡' + s.favorites + '</span>'
                + (distText ? '<span class="text-blue-500 font-medium">' + distText + '</span>' : '')
                + '</div>'
                + (s.address ? '<p class="text-[10px] text-zinc-400 truncate mt-0.5">' + s.address + '</p>' : '')
                + '</div></a>';
        });
        listEl.innerHTML = html;

        // 목록 호버 → 마커 하이라이트
        listEl.querySelectorAll('a[data-idx]').forEach(function(el) {
            el.addEventListener('mouseenter', function() {
                var idx = parseInt(el.dataset.idx);
                if (markers[idx]) markers[idx].openPopup();
            });
        });
    }

    // 거리 계산 (Haversine)
    function calcDist(lat1, lng1, lat2, lng2) {
        var R = 6371;
        var dLat = (lat2 - lat1) * Math.PI / 180;
        var dLng = (lng2 - lng1) * Math.PI / 180;
        var a = Math.sin(dLat/2) * Math.sin(dLat/2) + Math.cos(lat1*Math.PI/180) * Math.cos(lat2*Math.PI/180) * Math.sin(dLng/2) * Math.sin(dLng/2);
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    }

    // 위치 기반 정렬
    function sortByDistance(lat, lng) {
        shops.forEach(function(s) { s._dist = calcDist(lat, lng, s.lat, s.lng); });
        shops.sort(function(a, b) { return a._dist - b._dist; });
        renderList(shops);
        if (titleEl) titleEl.innerHTML = '📍 <?= $txtNearby ?> (' + shops.length + ')';
    }

    // 내 위치 감지
    function locateMe() {
        if (!navigator.geolocation) return;
        navigator.geolocation.getCurrentPosition(function(pos) {
            var lat = pos.coords.latitude, lng = pos.coords.longitude;
            map.setView([lat, lng], 15);
            if (userMarker) map.removeLayer(userMarker);
            userMarker = L.circleMarker([lat, lng], {radius: 8, color: '#3b82f6', fillColor: '#3b82f6', fillOpacity: 0.8, weight: 2}).addTo(map).bindPopup('<?= $txtMyLocation ?>');
            sortByDistance(lat, lng);
        }, function() {
            // 위치 거부 시 기본 목록
            renderList(shops);
        });
    }

    // 내 위치 버튼
    if (locateBtn) locateBtn.addEventListener('click', locateMe);

    // 첫 로드: 브라우저 위치 → 실패 시 IP 기반 위치
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(pos) {
            var lat = pos.coords.latitude, lng = pos.coords.longitude;
            map.setView([lat, lng], 15);
            if (userMarker) map.removeLayer(userMarker);
            userMarker = L.circleMarker([lat, lng], {radius: 8, color: '#3b82f6', fillColor: '#3b82f6', fillOpacity: 0.8, weight: 2}).addTo(map).bindPopup('<?= $txtMyLocation ?>');
            sortByDistance(lat, lng);
        }, function() {
            // 위치 거부 → IP 기반 폴백
            fetch('https://ipapi.co/json/').then(function(r){return r.json()}).then(function(d) {
                if (d.latitude && d.longitude) {
                    map.setView([d.latitude, d.longitude], 15);
                    sortByDistance(d.latitude, d.longitude);
                } else {
                    renderList(shops);
                }
            }).catch(function() { renderList(shops); });
        }, {timeout: 5000});
    } else {
        // geolocation 미지원 → IP 기반
        fetch('https://ipapi.co/json/').then(function(r){return r.json()}).then(function(d) {
            if (d.latitude && d.longitude) {
                map.setView([d.latitude, d.longitude], 15);
                sortByDistance(d.latitude, d.longitude);
            } else {
                renderList(shops);
            }
        }).catch(function() { renderList(shops); });
    }
})();
</script>
