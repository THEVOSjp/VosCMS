<?php
/**
 * URL → 썸네일/스크린샷 프록시 엔드포인트
 * GET /board/api/url-capture?type=thumbnail|screenshot&url={url}
 *
 *  - type=thumbnail : 첫 페이지(viewport) 캡처 — 1280×800
 *  - type=screenshot: 전체 스크롤 페이지 캡처 — 1280×최대 9000 (긴 페이지 전체)
 *
 * 응답: 이미지 바이너리 (image/png)
 * 실패 시 JSON 에러
 */
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

$type = $_GET['type'] ?? 'thumbnail';
$url  = trim($_GET['url'] ?? '');

if ($url === '' || !preg_match('#^https?://#i', $url)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'url required']);
    exit;
}

if (!in_array($type, ['thumbnail', 'screenshot'], true)) {
    http_response_code(400);
    echo json_encode(['success' => false, 'message' => 'invalid type (thumbnail|screenshot)']);
    exit;
}

$apiUrl = $_ENV['SCREENSHOT_API_URL'] ?? 'https://api.21ces.com/v1/screenshot';
$apiKey = $_ENV['SCREENSHOT_API_KEY'] ?? '';
if ($apiKey === '') {
    http_response_code(500);
    echo json_encode(['success' => false, 'message' => 'SCREENSHOT_API_KEY not configured']);
    exit;
}

// 썸네일 = 첫 화면(뷰포트) / 스크린샷 = 전체 페이지
// upstream Chrome 타임아웃(viewport 25s, full_page 50s)보다 반드시 길게 유지.
// 데스크톱 레이아웃(1280×960)으로 렌더한 뒤 출력만 0.625 스케일로 축소 → 800×600.
// 반응형 브레이크포인트는 1280 로 유지되므로 브라우저 "Ctrl + -" 로 축소한 것과 동일.
if ($type === 'thumbnail') {
    $payload = [
        'url'    => $url,
        'width'  => 1280,
        'height' => 960,
        'scale'  => 0.625,
        'wait'   => 1500,
        'format' => 'png',
    ];
    $timeout = 55;
} else {
    $payload = [
        'url'       => $url,
        'width'     => 1280,
        'full_page' => true,
        'scale'     => 0.625,
        'wait'      => 3000,
        'format'    => 'png',
    ];
    $timeout = 110;
}

$ch = curl_init($apiUrl);
curl_setopt_array($ch, [
    CURLOPT_POST           => true,
    CURLOPT_POSTFIELDS     => json_encode($payload),
    CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'X-Api-Key: ' . $apiKey],
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_TIMEOUT        => $timeout,
    CURLOPT_CONNECTTIMEOUT => 10,
    // HTTP/2 stream not closed cleanly 오류 회피 — upstream 이 스트림을
    // 급히 닫으면 curl 이 INTERNAL_ERROR 로 거절한다. HTTP/1.1 강제.
    CURLOPT_HTTP_VERSION   => CURL_HTTP_VERSION_1_1,
]);
$png  = curl_exec($ch);
$code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$err  = curl_error($ch);
curl_close($ch);

if ($code !== 200 || !$png || strlen($png) < 500) {
    // 업스트림 실패 본문에 진단용 메시지가 들어있으면 추출
    $upstreamMsg = '';
    if ($png) {
        $j = json_decode($png, true);
        if (is_array($j)) $upstreamMsg = (string)($j['message'] ?? $j['error'] ?? '');
    }
    error_log(sprintf(
        "[url-capture] FAIL type=%s code=%d err=%s upstream=%s url=%s",
        $type, $code, $err, substr($upstreamMsg, 0, 200), $url
    ));

    http_response_code(502);
    echo json_encode([
        'success'  => false,
        'message'  => $upstreamMsg !== '' ? $upstreamMsg : 'capture failed',
        'upstream' => $code,
        'error'    => $err,
    ]);
    exit;
}

header_remove('Content-Type');
header('Content-Type: image/png');
$fname = ($type === 'thumbnail' ? 'thumbnail' : 'fullpage') . '.png';
header('Content-Disposition: inline; filename="' . $fname . '"');
echo $png;
