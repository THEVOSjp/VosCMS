<?php
/**
 * Developer API - 아이템 제출
 * POST /api/developer/submit (multipart/form-data)
 *
 * 필수: package (ZIP), name, item_type, version
 * 선택: description, price, currency, screenshots[], icon, category_id, tags, min_voscms, min_php
 */
require_once __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(['success' => false, 'error' => 'method_not_allowed'], 405);

$dev = getAuthDeveloper($pdo);
if (!$dev) respond(['success' => false, 'error' => 'unauthorized', 'message' => 'Login required'], 401);

$price = (float) ($_POST['price'] ?? 0);

// 필수 필드
$itemType = $_POST['item_type'] ?? '';
$name = $_POST['name'] ?? '';       // JSON 문자열: {"ko":"...","en":"..."}
$version = trim($_POST['version'] ?? '');
$description = $_POST['description'] ?? ''; // JSON 문자열
$shortDesc = $_POST['short_description'] ?? '';
$changelog = trim($_POST['changelog'] ?? '');
$currency = strtoupper(trim($_POST['currency'] ?? 'USD'));
$categoryId = (int) ($_POST['category_id'] ?? 0) ?: null;
$tags = $_POST['tags'] ?? '';       // JSON 배열 문자열
$minVoscms = trim($_POST['min_voscms'] ?? '');
$minPhp = trim($_POST['min_php'] ?? '');
$requiresPlugins = $_POST['requires_plugins'] ?? '';

if (!$itemType || !$name || !$version) {
    respond(['success' => false, 'error' => 'missing_fields', 'message' => 'item_type, name, version are required'], 400);
}
if (!in_array($itemType, ['plugin', 'widget', 'theme', 'skin'])) {
    respond(['success' => false, 'error' => 'invalid_type'], 400);
}

// JSON 파싱 검증
$nameArr = json_decode($name, true);
if (!$nameArr || !is_array($nameArr)) {
    respond(['success' => false, 'error' => 'invalid_name', 'message' => 'name must be JSON: {"en":"...","ko":"..."}'], 400);
}

// ── 패키지 파일 업로드 ──
if (empty($_FILES['package']) || $_FILES['package']['error'] !== UPLOAD_ERR_OK) {
    respond(['success' => false, 'error' => 'no_package', 'message' => 'ZIP package file is required'], 400);
}

$file = $_FILES['package'];
$maxSize = 50 * 1024 * 1024; // 50MB
if ($file['size'] > $maxSize) {
    respond(['success' => false, 'error' => 'file_too_large', 'message' => 'Package must be under 50MB'], 400);
}

// ZIP 확인
$finfo = finfo_open(FILEINFO_MIME_TYPE);
$mime = finfo_file($finfo, $file['tmp_name']);
finfo_close($finfo);
if (!in_array($mime, ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'])) {
    respond(['success' => false, 'error' => 'invalid_file', 'message' => 'Only ZIP files allowed'], 400);
}

// 저장
$hash = hash_file('sha256', $file['tmp_name']);
$uploadDir = BASE_PATH . '/storage/uploads/marketplace/packages';
$fileName = $dev['id'] . '_' . time() . '_' . $hash . '.zip';
$destPath = $uploadDir . '/' . $fileName;

if (!move_uploaded_file($file['tmp_name'], $destPath)) {
    respond(['success' => false, 'error' => 'upload_failed'], 500);
}

// ── 자동 검증 ──
$validation = validatePackage($destPath, $itemType);

// ── 스크린샷 업로드 ──
$screenshotUrls = [];
if (!empty($_FILES['screenshots'])) {
    $ssDir = BASE_PATH . '/storage/uploads/marketplace/screenshots';
    $baseUrl = $_ENV['APP_URL'] ?? '';
    foreach ($_FILES['screenshots']['tmp_name'] as $i => $tmp) {
        if ($_FILES['screenshots']['error'][$i] !== UPLOAD_ERR_OK) continue;
        $ext = pathinfo($_FILES['screenshots']['name'][$i], PATHINFO_EXTENSION) ?: 'png';
        $ssName = $dev['id'] . '_' . time() . '_' . $i . '.' . $ext;
        if (move_uploaded_file($tmp, $ssDir . '/' . $ssName)) {
            $screenshotUrls[] = $baseUrl . '/storage/uploads/marketplace/screenshots/' . $ssName;
        }
    }
}

// ── 아이콘 업로드 ──
$iconUrl = null;
if (!empty($_FILES['icon']) && $_FILES['icon']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['icon']['name'], PATHINFO_EXTENSION) ?: 'png';
    $iconName = 'icon_' . $dev['id'] . '_' . time() . '.' . $ext;
    $ssDir = BASE_PATH . '/storage/uploads/marketplace/screenshots';
    if (move_uploaded_file($_FILES['icon']['tmp_name'], $ssDir . '/' . $iconName)) {
        $iconUrl = ($_ENV['APP_URL'] ?? '') . '/storage/uploads/marketplace/screenshots/' . $iconName;
    }
}

// ── DB 등록 (심사 큐) ──
$stmt = $pdo->prepare(
    "INSERT INTO vcs_review_queue
     (developer_id, item_type, name, description, short_description, version, changelog,
      package_path, package_hash, package_size, price, currency,
      screenshots, icon, category_id, tags, min_voscms, min_php, requires_plugins,
      validation_result, status, submitted_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', NOW())"
);
$stmt->execute([
    $dev['id'], $itemType, $name, $description ?: null, $shortDesc ?: null,
    $version, $changelog ?: null,
    $destPath, $hash, $file['size'], $price, $currency,
    !empty($screenshotUrls) ? json_encode($screenshotUrls) : null,
    $iconUrl, $categoryId,
    $tags ?: null, $minVoscms ?: null, $minPhp ?: null, $requiresPlugins ?: null,
    json_encode($validation, JSON_UNESCAPED_UNICODE),
    'pending',
]);
$queueId = (int) $pdo->lastInsertId();

respond([
    'success' => true,
    'queue_id' => $queueId,
    'validation' => $validation,
    'message' => 'Item submitted for review',
]);

// ── 패키지 검증 함수 ──
function validatePackage(string $zipPath, string $itemType): array
{
    $result = ['passed' => true, 'checks' => [], 'warnings' => [], 'errors' => []];

    // 1. ZIP 열기
    $zip = new ZipArchive();
    if ($zip->open($zipPath) !== true) {
        $result['passed'] = false;
        $result['errors'][] = 'Cannot open ZIP file';
        return $result;
    }
    $result['checks'][] = 'ZIP structure: OK';

    // 2. 매니페스트 확인
    $manifestName = $itemType === 'widget' ? 'widget.json' : 'plugin.json';
    $manifestFound = false;
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $entry = $zip->getNameIndex($i);
        if (basename($entry) === $manifestName && substr_count($entry, '/') <= 1) {
            $manifestFound = true;
            $manifestContent = $zip->getFromIndex($i);
            $manifest = json_decode($manifestContent, true);
            if (!$manifest || empty($manifest['id'])) {
                $result['errors'][] = "{$manifestName}: invalid JSON or missing 'id' field";
                $result['passed'] = false;
            } else {
                $result['checks'][] = "{$manifestName}: valid (id: {$manifest['id']})";
            }
            break;
        }
    }
    if (!$manifestFound) {
        $result['errors'][] = "Missing {$manifestName} in package root";
        $result['passed'] = false;
    }

    // 3. 보안 스캔
    $dangerousFunctions = ['eval\s*\(', 'exec\s*\(', 'system\s*\(', 'passthru\s*\(', 'shell_exec\s*\(', 'proc_open\s*\('];
    $dangerFound = [];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $entry = $zip->getNameIndex($i);
        if (!str_ends_with($entry, '.php')) continue;
        $content = $zip->getFromIndex($i);
        foreach ($dangerousFunctions as $pattern) {
            if (preg_match('/' . $pattern . '/i', $content)) {
                $dangerFound[] = basename($entry) . ': ' . explode('\\', $pattern)[0] . '()';
            }
        }
    }
    if (!empty($dangerFound)) {
        $result['warnings'][] = 'Potentially dangerous functions: ' . implode(', ', array_slice($dangerFound, 0, 5));
    } else {
        $result['checks'][] = 'Security scan: clean';
    }

    // 4. 금지 파일
    $forbiddenFiles = ['.env', '.htaccess', 'composer.lock'];
    for ($i = 0; $i < $zip->numFiles; $i++) {
        $entry = basename($zip->getNameIndex($i));
        if (in_array($entry, $forbiddenFiles)) {
            $result['warnings'][] = "Contains forbidden file: {$entry}";
        }
    }

    $zip->close();
    $result['checks'][] = 'File count: ' . $zip->numFiles ?? 'unknown';

    return $result;
}
