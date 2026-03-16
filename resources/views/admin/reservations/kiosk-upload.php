<?php
/**
 * 키오스크 배경 파일 업로드 API
 */
header('Content-Type: application/json; charset=utf-8');

$csrfToken = $_SESSION['csrf_token'] ?? '';
if (($_POST['_token'] ?? '') !== $csrfToken) {
    echo json_encode(['success' => false, 'message' => 'CSRF token mismatch']);
    exit;
}

$file = $_FILES['file'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    echo json_encode(['success' => false, 'message' => '파일 업로드 실패']);
    exit;
}

// 허용 MIME 타입
$allowedTypes = [
    'image/jpeg', 'image/png', 'image/webp', 'image/gif',
    'video/mp4', 'video/webm',
];
$mime = mime_content_type($file['tmp_name']);
if (!in_array($mime, $allowedTypes)) {
    echo json_encode(['success' => false, 'message' => '허용되지 않는 파일 형식입니다. (JPG, PNG, WebP, GIF, MP4, WebM)']);
    exit;
}

// 파일 크기 제한 (이미지: 10MB, 동영상: 100MB)
$isVideo = str_starts_with($mime, 'video/');
$maxSize = $isVideo ? 100 * 1024 * 1024 : 10 * 1024 * 1024;
if ($file['size'] > $maxSize) {
    $limitMb = $maxSize / 1024 / 1024;
    echo json_encode(['success' => false, 'message' => "파일 크기가 {$limitMb}MB를 초과합니다."]);
    exit;
}

// 저장 경로
$ext = match($mime) {
    'image/jpeg' => 'jpg',
    'image/png' => 'png',
    'image/webp' => 'webp',
    'image/gif' => 'gif',
    'video/mp4' => 'mp4',
    'video/webm' => 'webm',
    default => 'bin',
};
$filename = 'kiosk_bg_' . date('Ymd_His') . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
$uploadDir = BASE_PATH . '/storage/uploads/kiosk';

if (!is_dir($uploadDir)) {
    mkdir($uploadDir, 0755, true);
}

$destPath = $uploadDir . '/' . $filename;
if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    echo json_encode(['success' => false, 'message' => '파일 저장 실패']);
    exit;
}

$baseUrl = $config['app_url'] ?? '';
$url = $baseUrl . '/storage/uploads/kiosk/' . $filename;

echo json_encode(['success' => true, 'url' => $url, 'filename' => $filename]);
