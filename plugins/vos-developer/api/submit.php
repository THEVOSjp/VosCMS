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
$isDraft = ($_POST['save_draft'] ?? '') === '1';

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
// 신규 필드
$license = trim($_POST['license'] ?? '');
$repoUrl = trim($_POST['repo_url'] ?? '');
$demoUrl = trim($_POST['demo_url'] ?? '');
$salePrice = !empty($_POST['sale_price']) ? (float)$_POST['sale_price'] : null;
$saleEnds = $_POST['sale_ends_at'] ?? null;

if (!$isDraft && (!$itemType || !$name || !$version)) {
    respond(['success' => false, 'error' => 'missing_fields', 'message' => 'item_type, name, version are required'], 400);
}
if ($isDraft && !$name) {
    respond(['success' => false, 'error' => 'missing_fields', 'message' => 'name is required even for drafts'], 400);
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
$hasPackage = !empty($_FILES['package']) && $_FILES['package']['error'] === UPLOAD_ERR_OK;
if (!$isDraft && !$hasPackage) {
    respond(['success' => false, 'error' => 'no_package', 'message' => 'ZIP package file is required'], 400);
}

$destPath = '';
$hash = '';
$fileSize = 0;
$validation = ['passed' => true, 'checks' => [], 'warnings' => [], 'errors' => []];

if ($hasPackage) {
    $file = $_FILES['package'];
    $maxSize = 50 * 1024 * 1024;
    if ($file['size'] > $maxSize) {
        respond(['success' => false, 'error' => 'file_too_large', 'message' => 'Package must be under 50MB'], 400);
    }
    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    $mime = finfo_file($finfo, $file['tmp_name']);
    finfo_close($finfo);
    if (!in_array($mime, ['application/zip', 'application/x-zip-compressed', 'application/octet-stream'])) {
        respond(['success' => false, 'error' => 'invalid_file', 'message' => 'Only ZIP files allowed'], 400);
    }
    $hash = hash_file('sha256', $file['tmp_name']);
    $uploadDir = BASE_PATH . '/storage/uploads/marketplace/packages';
    if (!is_dir($uploadDir)) @mkdir($uploadDir, 0775, true);
    $fileName = $dev['id'] . '_' . time() . '_' . $hash . '.zip';
    $destPath = $uploadDir . '/' . $fileName;
    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        respond(['success' => false, 'error' => 'upload_failed'], 500);
    }
    $fileSize = $file['size'];
    $validation = validatePackage($destPath, $itemType);
}

// ── 배너 이미지 업로드 ──
$bannerUrl = null;
if (!empty($_FILES['banner']) && $_FILES['banner']['error'] === UPLOAD_ERR_OK) {
    $ext = pathinfo($_FILES['banner']['name'], PATHINFO_EXTENSION) ?: 'jpg';
    $bannerName = 'banner_' . $dev['id'] . '_' . time() . '.' . $ext;
    $ssDir = BASE_PATH . '/storage/uploads/marketplace/screenshots';
    if (!is_dir($ssDir)) @mkdir($ssDir, 0775, true);
    if (move_uploaded_file($_FILES['banner']['tmp_name'], $ssDir . '/' . $bannerName)) {
        $bannerUrl = ($_ENV['APP_URL'] ?? '') . '/storage/uploads/marketplace/screenshots/' . $bannerName;
    }
}

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
$status = $isDraft ? 'draft' : 'pending';
$stmt = $pdo->prepare(
    "INSERT INTO vcs_review_queue
     (developer_id, item_type, name, description, short_description, version, changelog,
      package_path, package_hash, package_size, price, currency,
      screenshots, icon, banner, category_id, tags, min_voscms, min_php, requires_plugins,
      license, repo_url, demo_url,
      validation_result, status, submitted_at)
     VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())"
);
$stmt->execute([
    $dev['id'], $itemType ?: 'plugin', $name, $description ?: null, $shortDesc ?: null,
    $version ?: '0.0.0', $changelog ?: null,
    $destPath ?: null, $hash ?: null, $fileSize, $price, $currency,
    !empty($screenshotUrls) ? json_encode($screenshotUrls) : null,
    $iconUrl, $bannerUrl, $categoryId,
    $tags ?: null, $minVoscms ?: null, $minPhp ?: null, $requiresPlugins ?: null,
    $license ?: null, $repoUrl ?: null, $demoUrl ?: null,
    json_encode($validation, JSON_UNESCAPED_UNICODE),
]);
$queueId = (int) $pdo->lastInsertId();

$msg = $isDraft ? 'Draft saved' : 'Item submitted for review';
respond([
    'success' => true,
    'queue_id' => $queueId,
    'validation' => $validation,
    'is_draft' => $isDraft,
    'message' => $msg,
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
