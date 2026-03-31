<?php
/**
 * 외부 URL 프록시 API
 * 외부 페이지 콘텐츠를 가져와서 반환 (CORS 우회)
 */
header('Content-Type: application/json; charset=utf-8');

$url = $_GET['url'] ?? '';
if (empty($url) || !preg_match('#^https?://#', $url)) {
    echo json_encode(['success' => false, 'error' => 'Invalid URL']);
    exit;
}

// 허용 도메인 체크 (보안: 무분별한 프록시 방지)
// 필요 시 화이트리스트 추가

$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_FOLLOWLOCATION => true,
    CURLOPT_MAXREDIRS => 3,
    CURLOPT_TIMEOUT => 10,
    CURLOPT_SSL_VERIFYPEER => false,
    CURLOPT_USERAGENT => 'RezlyX Page Proxy/1.0',
    CURLOPT_ENCODING => 'gzip, deflate',
]);

$html = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);
curl_close($ch);

if ($html === false || $httpCode >= 400) {
    echo json_encode(['success' => false, 'error' => $error ?: "HTTP $httpCode"]);
    exit;
}

// base 태그 삽입 (상대 경로 해결)
$parsed = parse_url($url);
$base = $parsed['scheme'] . '://' . $parsed['host'];
if (!empty($parsed['port'])) $base .= ':' . $parsed['port'];

// <head> 안에 <base> 태그 삽입
if (stripos($html, '<head') !== false) {
    $html = preg_replace('#(<head[^>]*>)#i', '$1<base href="' . htmlspecialchars($base) . '/" target="_blank">', $html, 1);
}

echo json_encode(['success' => true, 'html' => $html]);
