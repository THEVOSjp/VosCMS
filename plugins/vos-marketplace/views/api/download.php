<?php
/**
 * Marketplace API - 패키지 다운로드
 * GET /api/marketplace/download?license_key=xxx&item_slug=xxx
 */
$licenseKey = trim($_GET['license_key'] ?? '');
$itemSlug = trim($_GET['item_slug'] ?? '');

if (!$licenseKey || !$itemSlug) {
    http_response_code(400);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'license_key and item_slug required']);
    return;
}

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    http_response_code(500);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Server error']);
    return;
}

// 라이선스 확인
$stmt = $pdo->prepare(
    "SELECT l.*, i.slug, i.latest_version
     FROM {$prefix}mp_licenses l
     JOIN {$prefix}mp_items i ON i.id = l.item_id
     WHERE l.license_key = ? AND i.slug = ? AND l.status = 'active'"
);
$stmt->execute([$licenseKey, $itemSlug]);
$license = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$license) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'Invalid license or item']);
    return;
}

// 만료 확인
if ($license['expires_at'] && strtotime($license['expires_at']) < time()) {
    http_response_code(403);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'License expired']);
    return;
}

// 최신 버전 다운로드 URL
$vStmt = $pdo->prepare(
    "SELECT * FROM {$prefix}mp_item_versions WHERE item_id = ? AND status = 'active' ORDER BY released_at DESC LIMIT 1"
);
$vStmt->execute([$license['item_id']]);
$version = $vStmt->fetch(PDO::FETCH_ASSOC);

if (!$version || !$version['download_url']) {
    http_response_code(404);
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No downloadable version']);
    return;
}

// 다운로드 카운트 증가
$pdo->prepare("UPDATE {$prefix}mp_items SET download_count = download_count + 1 WHERE id = ?")
    ->execute([$license['item_id']]);

// 리다이렉트 또는 프록시 다운로드
if (str_starts_with($version['download_url'], 'http')) {
    header('Location: ' . $version['download_url']);
    exit;
} else {
    // 로컬 파일
    $filePath = BASE_PATH . '/' . ltrim($version['download_url'], '/');
    if (!file_exists($filePath)) {
        http_response_code(404);
        header('Content-Type: application/json');
        echo json_encode(['error' => 'File not found']);
        return;
    }
    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $itemSlug . '-' . $version['version'] . '.zip"');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
}
