<?php
/**
 * OG 링크 프리뷰 API
 * POST: url → OG 메타데이터 반환
 */
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST only']);
    exit;
}

$url = trim($_POST['url'] ?? '');
if (!$url || !filter_var($url, FILTER_VALIDATE_URL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid URL']);
    exit;
}

require_once BASE_PATH . '/rzxlib/Core/Modules/OgFetcher.php';
$og = \RzxLib\Core\Modules\OgFetcher::fetch($url);

echo json_encode(['success' => true, 'og' => $og], JSON_UNESCAPED_UNICODE);
