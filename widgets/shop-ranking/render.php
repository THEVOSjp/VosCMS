<?php
/**
 * 인기 사업장 랭킹 위젯
 * 메인(70%): 인기 사업장 TOP N + 사이드바(30%): 쿠폰, 리뷰, 태그
 */
ob_start();

$currentLocale = $locale ?? ($currentLocale ?? 'ko');
$baseUrl = $baseUrl ?? '';
$_wCfg = $config ?? [];

// 위젯 자체 번역 로드
$_wLang = @include(__DIR__ . '/lang/' . $currentLocale . '.php');
if (!is_array($_wLang)) $_wLang = @include(__DIR__ . '/lang/ko.php');
if (!is_array($_wLang)) $_wLang = [];
$_wt = function($key, $default = '') use ($_wLang) { return $_wLang[$key] ?? $default; };

$_wTitle = $_wCfg['title'] ?? [];
$_title = is_array($_wTitle) ? ($_wTitle[$currentLocale] ?? $_wTitle['ko'] ?? $_wt('title')) : ($_wTitle ?: $_wt('title'));
$_limit = (int)($_wCfg['limit'] ?? 10);
$_showCoupons = ($_wCfg['show_coupons'] ?? true) !== false;
$_showReviews = ($_wCfg['show_reviews'] ?? true) !== false;
$_showTags = ($_wCfg['show_tags'] ?? true) !== false;

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

// 인기 사업장 로드 (찜 + 평점 + 리뷰 종합)
$_shops = [];
$_coupons = [];
$_reviews = [];
$_tags = [];

try {
    if (!isset($pdo)) {
        $pdo = new PDO('mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx') . ';charset=utf8mb4',
            $_ENV['DB_USERNAME'] ?? 'root', $_ENV['DB_PASSWORD'] ?? '', [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]);
    }

    // 활성 사업장 전체 (JS에서 위치 기반 필터링)
    $_shops = $pdo->query("
        SELECT s.*, c.name as category_name, c.slug as category_slug,
               (s.favorite_count * 3 + s.rating_avg * 20 + s.review_count * 2 + s.view_count * 0.01) as score
        FROM {$prefix}shops s
        LEFT JOIN {$prefix}shop_categories c ON s.category_id = c.id
        WHERE s.status = 'active'
        ORDER BY score DESC
        LIMIT 50
    ")->fetchAll(PDO::FETCH_ASSOC);

    // 오늘의 쿠폰
    if ($_showCoupons) {
        $_coupons = $pdo->query("
            SELECT cp.*, s.name as shop_name, s.slug as shop_slug
            FROM {$prefix}coupons cp
            JOIN {$prefix}shops s ON cp.shop_id = s.id
            WHERE cp.is_active = 1 AND (cp.end_date IS NULL OR cp.end_date > NOW())
            ORDER BY cp.created_at DESC LIMIT 5
        ")->fetchAll(PDO::FETCH_ASSOC);
    }

    // 최근 리뷰
    if ($_showReviews) {
        $_reviews = $pdo->query("
            SELECT r.*, s.name as shop_name, s.slug as shop_slug, u.name as user_name
            FROM {$prefix}shop_reviews r
            JOIN {$prefix}shops s ON r.shop_id = s.id
            LEFT JOIN {$prefix}users u ON r.user_id = u.id
            WHERE r.status = 'active'
            ORDER BY r.created_at DESC LIMIT 3
        ")->fetchAll(PDO::FETCH_ASSOC);
        // 이름 복호화
        foreach ($_reviews as &$_rv) {
            if (isset($_rv['user_name']) && str_starts_with($_rv['user_name'], 'enc:') && class_exists('\RzxLib\Core\Helpers\Encryption')) {
                try { $_rv['user_name'] = \RzxLib\Core\Helpers\Encryption::decrypt($_rv['user_name']); } catch (\Throwable $e) {}
            }
        }
        unset($_rv);
    }

    // 인기 태그
    if ($_showTags) {
        $_tags = $pdo->query("SELECT slug, name FROM {$prefix}style_tags WHERE is_active = 1 ORDER BY sort_order LIMIT 12")->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (\Throwable $e) {}

// 샘플 데이터 (DB 비어있을 때)
if (empty($_shops)) {
    $_shops = [
        ['id' => 1, 'name' => 'Sample Salon A', 'slug' => '', 'cover_image' => '', 'rating_avg' => 4.9, 'review_count' => 58, 'favorite_count' => 320, 'address' => 'Tokyo Shibuya', 'category_name' => '{"ko":"미용실"}'],
        ['id' => 2, 'name' => 'Sample Salon B', 'slug' => '', 'cover_image' => '', 'rating_avg' => 4.7, 'review_count' => 35, 'favorite_count' => 210, 'address' => 'Seoul Gangnam', 'category_name' => '{"ko":"네일"}'],
        ['id' => 3, 'name' => 'Sample Salon C', 'slug' => '', 'cover_image' => '', 'rating_avg' => 4.5, 'review_count' => 22, 'favorite_count' => 150, 'address' => 'Osaka Namba', 'category_name' => '{"ko":"스파"}'],
    ];
}

$_rankColors = ['', 'text-amber-500', 'text-zinc-400', 'text-amber-700'];
?>

<div class="shop-ranking-widget">
    <div class="flex flex-col lg:flex-row gap-6">
        <!-- 메인: 랭킹 -->
        <div class="flex-1 lg:w-[70%]">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-xl font-bold text-zinc-900 dark:text-white">🏆 <?= htmlspecialchars($_title) ?></h2>
                <div class="flex items-center gap-2">
                    <span id="rankLocation" class="text-xs text-blue-600 font-medium"></span>
                    <select id="rankFilter" class="text-xs px-2 py-1 border border-zinc-200 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-zinc-600 dark:text-zinc-300" onchange="applyFilter()">
                        <option value="city" selected><?= $_wt('filter_city', '내 지역') ?></option>
                        <option value="5">5km</option>
                        <option value="10">10km</option>
                        <option value="30">30km</option>
                        <option value="0"><?= $_wt('view_all', '전체') ?></option>
                    </select>
                </div>
            </div>

            <div id="rankList" class="space-y-3">
                <p class="text-sm text-zinc-400 text-center py-8"><?= $_wt('no_shops', '등록된 사업장이 없습니다.') ?></p>
            </div>
        </div>

        <!-- 사이드바 -->
        <div class="lg:w-[30%] space-y-6">
            <!-- 오늘의 쿠폰 -->
            <?php if ($_showCoupons): ?>
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-100 dark:border-zinc-700 p-4">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-3">🎫 <?= $_wt('sidebar_coupons') ?></h3>
                <?php if (empty($_coupons)): ?>
                <p class="text-xs text-zinc-400 py-3 text-center"><?= $_wt('no_coupons') ?></p>
                <?php else: ?>
                <div class="space-y-2">
                    <?php foreach ($_coupons as $cp): ?>
                    <a href="<?= $baseUrl ?>/shop/<?= htmlspecialchars($cp['shop_slug']) ?>" class="block p-2.5 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 border-dashed rounded-lg hover:bg-red-100 dark:hover:bg-red-900/30 transition">
                        <p class="text-xs font-semibold text-red-700 dark:text-red-300"><?= htmlspecialchars($cp['title']) ?></p>
                        <p class="text-[10px] text-red-500 mt-0.5">
                            <?= $cp['discount_type'] === 'percent' ? $cp['discount_value'] . '%' : number_format($cp['discount_value']) ?> OFF
                            · <?= htmlspecialchars($cp['shop_name']) ?>
                        </p>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- 최근 리뷰 -->
            <?php if ($_showReviews): ?>
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-100 dark:border-zinc-700 p-4">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-3">💬 <?= $_wt('sidebar_reviews') ?></h3>
                <?php if (empty($_reviews)): ?>
                <p class="text-xs text-zinc-400 py-3 text-center"><?= $_wt('no_reviews') ?></p>
                <?php else: ?>
                <div class="space-y-3">
                    <?php foreach ($_reviews as $rv): ?>
                    <a href="<?= $baseUrl ?>/shop/<?= htmlspecialchars($rv['shop_slug']) ?>" class="block hover:bg-zinc-50 dark:hover:bg-zinc-700/50 rounded-lg p-2 -m-2 transition">
                        <div class="flex items-center gap-1 mb-1">
                            <?php for ($star = 1; $star <= 5; $star++): ?>
                            <svg class="w-3 h-3 <?= $star <= ($rv['rating'] ?? 5) ? 'text-amber-400' : 'text-zinc-200' ?>" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>
                            <?php endfor; ?>
                            <span class="text-[10px] text-zinc-400 ml-1"><?= htmlspecialchars($rv['user_name'] ?? '') ?></span>
                        </div>
                        <p class="text-xs text-zinc-600 dark:text-zinc-400 line-clamp-2"><?= htmlspecialchars(mb_substr($rv['content'] ?? '', 0, 60)) ?></p>
                        <p class="text-[10px] text-zinc-400 mt-0.5"><?= htmlspecialchars($rv['shop_name']) ?></p>
                    </a>
                    <?php endforeach; ?>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- 인기 태그 -->
            <?php if ($_showTags && !empty($_tags)): ?>
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-zinc-100 dark:border-zinc-700 p-4">
                <h3 class="text-sm font-semibold text-zinc-900 dark:text-white mb-3">🔥 <?= $_wt('sidebar_tags') ?></h3>
                <div class="flex flex-wrap gap-1.5">
                    <?php foreach ($_tags as $t):
                        $tn = json_decode($t['name'], true) ?: [];
                        $tLabel = $tn[$currentLocale] ?? $tn['ko'] ?? $t['slug'];
                    ?>
                    <a href="<?= $baseUrl ?>/styles?tag=<?= urlencode($t['slug']) ?>" class="px-2.5 py-1 text-xs border border-zinc-200 dark:border-zinc-600 rounded-full text-zinc-600 dark:text-zinc-400 hover:bg-blue-50 hover:border-blue-300 hover:text-blue-600 dark:hover:bg-blue-900/20 transition">#<?= htmlspecialchars($tLabel) ?></a>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<script>
(function() {
    var baseUrl = '<?= $baseUrl ?>';
    var limit = <?= $_limit ?>;
    var rankColors = {1:'text-amber-500', 2:'text-zinc-400', 3:'text-amber-700'};
    var allShops = <?= json_encode(array_map(function($s) {
        $img = $s['cover_image'] ?? '';
        $images = json_decode($s['images'] ?? '[]', true) ?: [];
        if (!$img && !empty($images)) $img = $images[0];
        return [
            'id' => $s['id'], 'name' => $s['name'], 'slug' => $s['slug'],
            'lat' => (float)($s['latitude'] ?? 0), 'lng' => (float)($s['longitude'] ?? 0),
            'rating' => (float)($s['rating_avg'] ?? 0), 'reviews' => (int)($s['review_count'] ?? 0),
            'favorites' => (int)($s['favorite_count'] ?? 0), 'views' => (int)($s['view_count'] ?? 0),
            'address' => $s['address'] ?? '', 'image' => $img, 'score' => (float)($s['score'] ?? 0),
        ];
    }, $_shops), JSON_UNESCAPED_UNICODE) ?>;

    var userLat = 0, userLng = 0, userCity = '';

    function haversine(lat1, lon1, lat2, lon2) {
        var R = 6371;
        var dLat = (lat2-lat1)*Math.PI/180, dLon = (lon2-lon1)*Math.PI/180;
        var a = Math.sin(dLat/2)*Math.sin(dLat/2) + Math.cos(lat1*Math.PI/180)*Math.cos(lat2*Math.PI/180)*Math.sin(dLon/2)*Math.sin(dLon/2);
        return R * 2 * Math.atan2(Math.sqrt(a), Math.sqrt(1-a));
    }

    function renderRankList(shops) {
        var el = document.getElementById('rankList');
        if (!shops.length) { el.innerHTML = '<p class="text-sm text-zinc-400 text-center py-8"><?= $_wt('no_shops') ?></p>'; return; }
        var html = '';
        shops.forEach(function(s, i) {
            var rank = i + 1;
            var colorCls = rankColors[rank] || 'text-zinc-400';
            var distStr = s.dist !== undefined ? ' · ' + s.dist.toFixed(1) + 'km' : '';
            html += '<a href="' + (s.slug ? baseUrl+'/shop/'+s.slug : '#') + '" class="flex items-center gap-4 p-3 bg-white dark:bg-zinc-800 rounded-xl border border-zinc-100 dark:border-zinc-700 hover:shadow-md transition group">';
            html += '<div class="w-8 text-center flex-shrink-0"><span class="text-lg font-bold ' + colorCls + '">' + rank + '</span></div>';
            html += '<div class="w-16 h-16 rounded-lg overflow-hidden bg-zinc-100 dark:bg-zinc-700 flex-shrink-0">';
            if (s.image) { html += '<img src="' + baseUrl + '/' + s.image.replace(/^\//, '') + '" class="w-full h-full object-cover">'; }
            else { html += '<div class="w-full h-full flex items-center justify-center text-zinc-300"><svg class="w-6 h-6" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M19 21V5a2 2 0 00-2-2H7a2 2 0 00-2 2v16m14 0h2m-2 0h-5m-9 0H3m2 0h5M9 7h1m-1 4h1m4-4h1m-1 4h1m-5 10v-5a1 1 0 011-1h2a1 1 0 011 1v5m-4 0h4"/></svg></div>'; }
            html += '</div>';
            html += '<div class="flex-1 min-w-0"><p class="text-sm font-semibold text-zinc-900 dark:text-white group-hover:text-blue-600 truncate">' + s.name + '</p>';
            html += '<div class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400 mt-0.5">';
            html += '<span class="flex items-center gap-0.5"><svg class="w-3 h-3 text-amber-400" fill="currentColor" viewBox="0 0 20 20"><path d="M9.049 2.927c.3-.921 1.603-.921 1.902 0l1.07 3.292a1 1 0 00.95.69h3.462c.969 0 1.371 1.24.588 1.81l-2.8 2.034a1 1 0 00-.364 1.118l1.07 3.292c.3.921-.755 1.688-1.54 1.118l-2.8-2.034a1 1 0 00-1.175 0l-2.8 2.034c-.784.57-1.838-.197-1.539-1.118l1.07-3.292a1 1 0 00-.364-1.118L2.98 8.72c-.783-.57-.38-1.81.588-1.81h3.461a1 1 0 00.951-.69l1.07-3.292z"/></svg>' + s.rating.toFixed(1) + '</span>';
            html += '<span>· <?= $_wt('reviews') ?> ' + s.reviews + '</span>';
            html += '<span>· ♡ ' + s.favorites + '</span>';
            html += '</div>';
            html += '<p class="text-[11px] text-zinc-400 mt-0.5 truncate">' + s.address + distStr + '</p>';
            html += '</div></a>';
        });
        el.innerHTML = html;
    }

    window.applyFilter = function() {
        var val = document.getElementById('rankFilter').value;
        var filtered;

        if (val === '0' || !userLat) {
            // 전체
            filtered = allShops.slice(0, limit);
        } else if (val === 'city') {
            // 시/구 단위 매칭
            if (userCity) {
                filtered = allShops.filter(function(s) {
                    return s.address && s.address.indexOf(userCity) !== -1;
                });
                // 시 단위 매칭 결과 없으면 반경 30km 폴백
                if (!filtered.length && userLat) {
                    filtered = allShops.filter(function(s) {
                        if (!s.lat || !s.lng) return false;
                        s.dist = haversine(userLat, userLng, s.lat, s.lng);
                        return s.dist <= 30;
                    });
                }
            } else if (userLat) {
                filtered = allShops.filter(function(s) {
                    if (!s.lat || !s.lng) return false;
                    s.dist = haversine(userLat, userLng, s.lat, s.lng);
                    return s.dist <= 10;
                });
            } else {
                filtered = allShops;
            }
            filtered = filtered.sort(function(a, b) { return b.score - a.score; }).slice(0, limit);
        } else {
            // 반경 N km
            var radius = parseInt(val);
            filtered = allShops.filter(function(s) {
                if (!s.lat || !s.lng) return false;
                s.dist = haversine(userLat, userLng, s.lat, s.lng);
                return s.dist <= radius;
            }).sort(function(a, b) { return b.score - a.score; }).slice(0, limit);
        }
        renderRankList(filtered);
    };

    // 도시명 가져오기 (역 지오코딩)
    function getCityFromCoords(lat, lng, callback) {
        fetch('https://ipapi.co/json/').then(function(r){return r.json()}).then(function(d) {
            callback(d.city || '', d.region || '');
        }).catch(function() { callback('', ''); });
    }

    function setLocation(lat, lng, city, region) {
        userLat = lat; userLng = lng; userCity = city;
        var locText = city ? '📍 ' + city + (region ? ', ' + region : '') : '📍 ' + lat.toFixed(2) + ', ' + lng.toFixed(2);
        document.getElementById('rankLocation').textContent = locText;
        applyFilter();
    }

    // 위치 감지
    if (navigator.geolocation) {
        navigator.geolocation.getCurrentPosition(function(pos) {
            var lat = pos.coords.latitude, lng = pos.coords.longitude;
            getCityFromCoords(lat, lng, function(city, region) {
                setLocation(lat, lng, city, region);
            });
        }, function() {
            fetch('https://ipapi.co/json/').then(function(r){return r.json()}).then(function(d) {
                if (d.latitude && d.longitude) {
                    setLocation(d.latitude, d.longitude, d.city || '', d.region || '');
                } else {
                    renderRankList(allShops.slice(0, limit));
                }
            }).catch(function() { renderRankList(allShops.slice(0, limit)); });
        }, {timeout: 5000});
    } else {
        renderRankList(allShops.slice(0, limit));
    }
})();
</script>

<?php return ob_get_clean(); ?>
