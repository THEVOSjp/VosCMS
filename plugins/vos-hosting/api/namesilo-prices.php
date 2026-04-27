<?php
/**
 * NameSilo 도메인 가격 API
 *
 * GET  /api/namesilo-prices.php           → 캐시된 가격 반환
 * GET  /api/namesilo-prices.php?refresh=1  → 강제 갱신
 *
 * 캐시: storage/cache/namesilo_prices.json (24시간 TTL)
 * 소스: NameSilo getPrices API
 */
if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__, 3));

// .env 로드
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') !== false) {
            [$k, $v] = explode('=', $line, 2);
            $_ENV[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
        }
    }
}

$apiKey = $_ENV['NAMESILO_API_KEY'] ?? '';
if (!$apiKey) {
    echo json_encode(['success' => false, 'message' => 'NAMESILO_API_KEY not configured']);
    exit;
}

$cacheFile = BASE_PATH . '/storage/cache/namesilo_prices.json';
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

// NameSilo API 호출
$apiUrl = "https://www.namesilo.com/api/getPrices?version=1&type=xml&key={$apiKey}";
$response = false;

if (function_exists('curl_init')) {
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 15,
        CURLOPT_FOLLOWLOCATION => true,
    ]);
    $response = curl_exec($ch);
    $error = curl_error($ch);
    curl_close($ch);
} else {
    $ctx = stream_context_create(['http' => ['timeout' => 15]]);
    $response = @file_get_contents($apiUrl, false, $ctx);
    $error = $response === false ? 'file_get_contents failed' : '';
}

if (!$response) {
    if (file_exists($cacheFile)) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        $cached['source'] = 'stale_cache';
        $cached['error'] = $error;
        echo json_encode($cached, JSON_UNESCAPED_UNICODE);
    } else {
        echo json_encode(['success' => false, 'message' => 'API 요청 실패: ' . $error]);
    }
    exit;
}

// XML 파싱 — 정규식 (simplexml 불필요)
$prices = [];
if (preg_match_all('#<(\w+)><registration>([\d.]+)</registration><renew>([\d.]+)</renew><transfer>([\d.]+)</transfer></\1>#', $response, $matches, PREG_SET_ORDER)) {
    foreach ($matches as $m) {
        $prices[] = [
            'tld' => '.' . $m[1],
            'registration' => (float)$m[2],
            'renew' => (float)$m[3],
            'transfer' => (float)$m[4],
            'currency' => 'USD',
        ];
    }
}

if (empty($prices)) {
    echo json_encode(['success' => false, 'message' => '가격 데이터 없음']);
    exit;
}

// TLD 이름순 정렬
usort($prices, function($a, $b) { return strcmp($a['tld'], $b['tld']); });

$result = [
    'success' => true,
    'count' => count($prices),
    'currency' => 'USD',
    'prices' => $prices,
    'fetched_at' => time(),
    'source' => 'api',
];

$cacheDir = dirname($cacheFile);
if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);
file_put_contents($cacheFile, json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo json_encode($result, JSON_UNESCAPED_UNICODE);
