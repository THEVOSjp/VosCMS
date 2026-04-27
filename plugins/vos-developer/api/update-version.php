<?php
/**
 * Developer API - 기존 아이템 새 버전 업로드
 * POST /api/developer/update-version (multipart/form-data)
 *
 * 필수: queue_id (기존 승인된 심사 큐 ID) 또는 item_id (rzx_mp_items.id), package (ZIP), version
 * 선택: changelog, min_voscms, min_php
 */
require_once __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(['success' => false, 'error' => 'method_not_allowed'], 405);

$dev = getAuthDeveloper($pdo);
if (!$dev) respond(['success' => false, 'error' => 'unauthorized'], 401);

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

// 어떤 아이템의 업데이트인지 식별
$queueId = (int) ($_POST['queue_id'] ?? 0);
$itemId = (int) ($_POST['item_id'] ?? 0);
$version = trim($_POST['version'] ?? '');
$changelog = trim($_POST['changelog'] ?? '');
$minVoscms = trim($_POST['min_voscms'] ?? '');
$minPhp = trim($_POST['min_php'] ?? '');

if (!$version) {
    respond(['success' => false, 'error' => 'missing_version', 'message' => 'Version number is required'], 400);
}

// 기존 아이템 정보 가져오기
$originalItem = null;
$mpItemId = null;

if ($queueId) {
    // 심사 큐에서 승인된 아이템 찾기
    $stmt = $pdo->prepare("SELECT * FROM vcs_review_queue WHERE id = ? AND developer_id = ? AND status = 'approved'");
    $stmt->execute([$queueId, $dev['id']]);
    $originalItem = $stmt->fetch();
    $mpItemId = $originalItem['item_id'] ?? null;
} elseif ($itemId) {
    // rzx_mp_items에서 직접 찾기
    $stmt = $pdo->prepare("SELECT * FROM {$prefix}mp_items WHERE id = ? AND author_name = ?");
    $stmt->execute([$itemId, $dev['name']]);
    $mpItem = $stmt->fetch();
    if ($mpItem) {
        $mpItemId = (int) $mpItem['id'];
        // 가상 originalItem 구성
        $originalItem = [
            'item_type' => $mpItem['type'],
            'name' => $mpItem['name'],
            'description' => $mpItem['description'],
            'short_description' => $mpItem['short_description'],
            'price' => $mpItem['price'],
            'currency' => $mpItem['currency'],
            'category_id' => $mpItem['category_id'],
            'tags' => $mpItem['tags'],
            'icon' => $mpItem['icon'],
            'screenshots' => $mpItem['screenshots'],
            'min_voscms' => $mpItem['min_voscms_version'],
            'min_php' => $mpItem['min_php_version'],
            'requires_plugins' => $mpItem['requires_plugins'],
        ];
    }
}

if (!$originalItem || !$mpItemId) {
    respond(['success' => false, 'error' => 'item_not_found', 'message' => 'Original approved item not found'], 404);
}

// 버전 중복 확인
$vChk = $pdo->prepare("SELECT id FROM {$prefix}mp_item_versions WHERE item_id = ? AND version = ?");
$vChk->execute([$mpItemId, $version]);
if ($vChk->fetch()) {
    respond(['success' => false, 'error' => 'version_exists', 'message' => "Version {$version} already exists"], 409);
}

// 패키지 파일 업로드
if (empty($_FILES['package']) || $_FILES['package']['error'] !== UPLOAD_ERR_OK) {
    respond(['success' => false, 'error' => 'no_package', 'message' => 'ZIP package file is required'], 400);
}

$file = $_FILES['package'];
if ($file['size'] > 50 * 1024 * 1024) {
    respond(['success' => false, 'error' => 'file_too_large'], 400);
}

$hash = hash_file('sha256', $file['tmp_name']);
$uploadDir = BASE_PATH . '/storage/uploads/marketplace/packages';
$fileName = $dev['id'] . '_update_' . time() . '_' . $hash . '.zip';
$destPath = $uploadDir . '/' . $fileName;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    respond(['success' => false, 'error' => 'upload_failed'], 500);
}

// 자동 검증
require_once __DIR__ . '/submit.php'; // validatePackage 함수 재사용은 별도 분리 필요
// validatePackage가 submit.php에 있으므로 여기서 간단 검증
$validation = ['passed' => true, 'checks' => ['Update package uploaded'], 'warnings' => [], 'errors' => []];

$zip = new ZipArchive();
if ($zip->open($destPath) === true) {
    $manifestName = ($originalItem['item_type'] ?? 'plugin') === 'widget' ? 'widget.json' : 'plugin.json';
    $found = false;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        if (basename($zip->getNameIndex($i)) === $manifestName) { $found = true; break; }
    }
    if (!$found) {
        $validation['passed'] = false;
        $validation['errors'][] = "Missing {$manifestName}";
    } else {
        $validation['checks'][] = "{$manifestName}: found";
    }
    $validation['checks'][] = "Files: {$zip->numFiles}";
    $zip->close();
}

// 이전 버전 확인
$prevStmt = $pdo->prepare("SELECT version FROM {$prefix}mp_item_versions WHERE item_id = ? ORDER BY released_at DESC LIMIT 1");
$prevStmt->execute([$mpItemId]);
$previousVersion = $prevStmt->fetchColumn() ?: ($originalItem['version'] ?? '1.0.0');

// 심사 큐에 업데이트로 등록
$stmt = $pdo->prepare(
    "INSERT INTO vcs_review_queue
     (developer_id, item_id, is_update, previous_version, item_type, name, description, short_description,
      version, changelog, package_path, package_hash, package_size, price, currency,
      screenshots, icon, category_id, tags, min_voscms, min_php, requires_plugins,
      validation_result, status, submitted_at)
     VALUES (?, ?, 1, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())"
);
$stmt->execute([
    $dev['id'], $mpItemId, $previousVersion,
    $originalItem['item_type'] ?? 'plugin',
    $originalItem['name'] ?? '{}',
    $originalItem['description'] ?? null,
    $originalItem['short_description'] ?? null,
    $version, $changelog ?: null,
    $destPath, $hash, $file['size'],
    $originalItem['price'] ?? 0, $originalItem['currency'] ?? 'USD',
    $originalItem['screenshots'] ?? null,
    $originalItem['icon'] ?? null,
    $originalItem['category_id'] ?? null,
    $originalItem['tags'] ?? null,
    $minVoscms ?: ($originalItem['min_voscms'] ?? null),
    $minPhp ?: ($originalItem['min_php'] ?? null),
    $originalItem['requires_plugins'] ?? null,
    json_encode($validation, JSON_UNESCAPED_UNICODE),
]);
$newQueueId = (int) $pdo->lastInsertId();

respond([
    'success' => true,
    'queue_id' => $newQueueId,
    'previous_version' => $previousVersion,
    'new_version' => $version,
    'validation' => $validation,
    'message' => "Version {$version} submitted for review (update from {$previousVersion})",
]);
