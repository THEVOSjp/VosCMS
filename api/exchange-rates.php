<?php
/**
 * 환율 API — Frankfurter API 프록시 + 캐시
 *
 * GET  /api/exchange-rates.php          → 캐시된 환율 반환 (없으면 자동 fetch)
 * GET  /api/exchange-rates.php?refresh=1 → 강제 갱신
 *
 * 캐시: storage/cache/exchange_rates.json (24시간 TTL)
 * 소스: https://api.frankfurter.dev/v2/rates?base=USD&quotes=KRW,JPY,CNY,EUR
 */
if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));
$cacheFile = BASE_PATH . '/storage/cache/exchange_rates.json';
$cacheTTL = 86400; // 24시간
$forceRefresh = isset($_GET['refresh']);

// 캐시 확인
if (!$forceRefresh && file_exists($cacheFile)) {
    $cached = json_decode(file_get_contents($cacheFile), true);
    if ($cached && isset($cached['fetched_at']) && (time() - $cached['fetched_at']) < $cacheTTL) {
        $cached['source'] = 'cache';
        echo json_encode($cached);
        exit;
    }
}

// Frankfurter API 호출
$apiUrl = 'https://api.frankfurter.dev/v2/rates?base=USD&quotes=KRW,JPY,CNY,EUR';
$response = false;
$httpCode = 0;
$error = '';

if (function_exists('curl_init')) {
    $ch = curl_init($apiUrl);
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_TIMEOUT => 10,
        CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_HTTPHEADER => ['Accept: application/json'],
    ]);
    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $error = curl_error($ch);
    curl_close($ch);
} else {
    // curl 미설치 시 file_get_contents 폴백
    $ctx = stream_context_create(['http' => [
        'timeout' => 10,
        'header' => "Accept: application/json\r\n",
    ]]);
    $response = @file_get_contents($apiUrl, false, $ctx);
    $httpCode = $response !== false ? 200 : 0;
    if (!$response) $error = 'file_get_contents failed';
}

if ($httpCode !== 200 || !$response) {
    // API 실패 시 캐시 반환
    if (file_exists($cacheFile)) {
        $cached = json_decode(file_get_contents($cacheFile), true);
        $cached['source'] = 'stale_cache';
        $cached['api_error'] = $error ?: "HTTP {$httpCode}";
        echo json_encode($cached);
    } else {
        echo json_encode(['success' => false, 'message' => 'API 요청 실패: ' . ($error ?: "HTTP {$httpCode}")]);
    }
    exit;
}

$data = json_decode($response, true);
if (!$data || !is_array($data)) {
    echo json_encode(['success' => false, 'message' => '응답 파싱 실패']);
    exit;
}

// Frankfurter v2 응답 형식: [{date, base, quote, rate}, ...]
$rates = [];
$date = '';
foreach ($data as $item) {
    if (isset($item['quote'], $item['rate'])) {
        $rates[$item['quote']] = $item['rate'];
        if (!$date && isset($item['date'])) $date = $item['date'];
    }
}

if (empty($rates)) {
    echo json_encode(['success' => false, 'message' => '환율 데이터 없음']);
    exit;
}

$result = [
    'success' => true,
    'date' => $date,
    'base' => 'USD',
    'rates' => $rates,
    'fetched_at' => time(),
    'source' => 'api',
];

// 캐시 디렉토리 확인
$cacheDir = dirname($cacheFile);
if (!is_dir($cacheDir)) mkdir($cacheDir, 0755, true);

// 캐시 저장
file_put_contents($cacheFile, json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));

echo json_encode($result);
