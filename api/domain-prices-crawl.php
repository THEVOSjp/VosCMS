<?php
/**
 * xdomain.ne.jp 도메인 가격 크롤링
 *
 * GET  /api/domain-prices-crawl.php           → 캐시된 가격 반환
 * GET  /api/domain-prices-crawl.php?refresh=1  → 강제 갱신
 *
 * 캐시: storage/cache/xdomain_prices.json (24시간 TTL)
 * 소스: https://www.xdomain.ne.jp/domain/price.php
 */
if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));
$cacheFile = BASE_PATH . '/storage/cache/xdomain_prices.json';
$cacheTTL = 86400; // 24시간
$forceRefresh = isset($_GET['refresh']);

// 캐시 확인
if (!$forceRefresh && file_exists($cacheFile)) {
    $cached = json_decode(file_get_contents($cacheFile), true);
    if ($cached && isset($cached['fetched_at']) && (time() - $cached['fetched_at']) < $cacheTTL) {
        $cached['source'] = 'cache';
        echo json_encode($cached, JSON_UNESCAPED_UNICODE);
        exit;
    }
}

// HTML 가져오기
$url = 'https://www.xdomain.ne.jp/domain/price.php';
$html = false;

if (function_exists('curl_init')) {
    $ch = curl_init($url);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_USERAGENT => 'Mozilla/5.0 (compatible; VosCMS/2.1)',
        CURLOPT_HTTPHEADER => ['Accept-Language: ja'],
    ]);
    $html = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
} else {
    $ctx = stream_context_create(['http' => [
        'timeout' => 15,
        'header' => "User-Agent: Mozilla/5.0 (compatible; VosCMS/2.1)\r\nAccept-Language: ja\r\n",
    ]]);
    $html = @file_get_contents($url, false, $ctx);
    $error = $html === false ? 'file_get_contents failed' : '';
}

if (!$html) {
    // 실패 시 캐시 반환
    if (file_exists($cacheFile)) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        $cached['source'] = 'stale_cache';
        $cached['error'] = $error;
        echo json_encode($cached, JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'message' => '크롤링 실패: ' . $error], JSON_UNESCAPED_UNICODE);
    }
    exit;
}

// HTML 파싱 — data 속성에서 가격 추출
// <tr class="price-list__body" data-century=".com" data-get="1" data-move="1721" data-update="1721">
$prices = [];

if (preg_match_all(
    '/data-century="(\.[^"]+)"\s+data-get="([^"]*)"\s+data-second="[^"]*"\s+data-move="([^"]*)"\s+data-update="([^"]*)"/i',
    $html, $matches, PREG_SET_ORDER
)) {
    foreach ($matches as $m) {
        $tld = $m[1];
        $get = is_numeric($m[2]) ? (int)$m[2] : null;
        $move = ($m[3] !== '' && $m[3] !== '-1') ? (int)$m[3] : null;
        $update = is_numeric($m[4]) ? (int)$m[4] : null;

        $prices[] = [
            'tld' => $tld,
            'registration' => $get,        // 取得 (신규 등록, 캠페인가 포함)
            'renewal' => $update,           // 更新 (갱신 = 실질 연간 가격)
            'transfer' => $move,            // 移管 (이관, -1이면 불가 → null)
            'currency' => 'JPY',
        ];
    }
}

if (empty($prices)) {
    echo json_encode(['success' => false, 'message' => '가격 데이터를 파싱할 수 없습니다.'], JSON_UNESCAPED_UNICODE);
    exit;
}

$result = [
    'success' => true,
    'source_url' => $url,
    'count' => count($prices),
    'currency' => 'JPY',
    'prices' => $prices,
    'fetched_at' => time(),
    'source' => 'crawl',
];

// 캐시 저장
$cacheDir = dirname($cacheFile);
if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);
file_put_contents($cacheFile, json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo json_encode($result, JSON_UNESCAPED_UNICODE);
