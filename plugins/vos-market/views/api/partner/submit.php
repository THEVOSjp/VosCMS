<?php
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'msg'=>'POST required']); exit; }
require_once __DIR__ . '/_auth.php';
try {
    $pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $pfx = $_ENV['DB_PREFIX'] ?? 'rzx_';
    $partnerId = mkt_partner_auth($pdo, $pfx);

    $slug    = trim($_POST['slug'] ?? '');
    $version = trim($_POST['version'] ?? '');
    $type    = trim($_POST['type'] ?? 'plugin');
    if (!$slug || !preg_match('/^[a-z0-9\-_]+$/', $slug)) { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'유효하지 않은 슬러그']); exit; }
    if (!$version || !preg_match('/^\d+\.\d+(\.\d+)?$/', $version)) { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'유효하지 않은 버전 (예: 1.0.0)']); exit; }

    $existingItem = $pdo->prepare("SELECT id FROM {$pfx}mkt_items WHERE slug=? AND partner_id=?");
    $existingItem->execute([$slug,$partnerId]); $itemId = (int)($existingItem->fetchColumn() ?: 0);
    $isUpdate = $itemId > 0;

    // Package upload
    $packagePath = null; $packageHash = null; $packageSize = null;
    if (!empty($_FILES['package']['tmp_name'])) {
        $maxMb = 50;
        $settingMb = $pdo->prepare("SELECT value FROM {$pfx}mkt_settings WHERE `key`='max_upload_mb'");
        $settingMb->execute(); $settingMb = $settingMb->fetchColumn();
        if ($settingMb) $maxMb = (int)$settingMb;
        if ($_FILES['package']['size'] > $maxMb * 1024 * 1024) {
            http_response_code(400); echo json_encode(['ok'=>false,'msg'=>"파일 크기는 {$maxMb}MB를 초과할 수 없습니다"]); exit;
        }
        $ext = strtolower(pathinfo($_FILES['package']['name'], PATHINFO_EXTENSION));
        if ($ext !== 'zip') { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'ZIP 파일만 허용됩니다']); exit; }
        $dir = BASE_PATH . "/storage/uploads/packages/{$slug}/";
        if (!is_dir($dir)) mkdir($dir, 0755, true);
        $filename = "{$slug}-{$version}.zip";
        move_uploaded_file($_FILES['package']['tmp_name'], $dir . $filename);
        $packagePath  = "packages/{$slug}/{$filename}";
        $packageHash  = hash_file('sha256', $dir . $filename);
        $packageSize  = filesize($dir . $filename);
    }

    $submittedData = [];
    foreach (['name','description','short_description','changelog','author_name','repo_url','demo_url','icon_url','banner_url','category_id','tags','price','currency','license','min_voscms_version','min_php_version','screenshot_urls'] as $k) {
        if (isset($_POST[$k])) $submittedData[$k] = $_POST[$k];
    }

    $pdo->prepare("INSERT INTO {$pfx}mkt_submissions (partner_id,item_id,is_update,item_type,submitted_slug,submitted_version,submitted_data,package_path,package_hash,package_size,status) VALUES (?,?,?,?,?,?,?,?,?,?,'pending')")
        ->execute([$partnerId,$itemId?:null,$isUpdate?1:0,$type,$slug,$version,json_encode($submittedData,JSON_UNESCAPED_UNICODE),$packagePath,$packageHash,$packageSize]);

    echo json_encode(['ok'=>true,'msg'=>'제출 완료. 심사 후 게시됩니다.','is_update'=>$isUpdate], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500); echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
