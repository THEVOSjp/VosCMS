<?php
// Serve a package file after license/ownership check
try {
    $pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $pfx = $_ENV['DB_PREFIX'] ?? 'rzx_';

    $slug       = trim($_GET['slug'] ?? '');
    $version    = trim($_GET['version'] ?? '');
    $licenseKey = trim($_GET['license_key'] ?? $_POST['license_key'] ?? '');
    if (!$slug) { http_response_code(400); echo 'slug required'; exit; }

    $item = $pdo->prepare("SELECT i.id,i.price,i.latest_version FROM {$pfx}mkt_items i WHERE i.slug=? AND i.status='active'");
    $item->execute([$slug]); $item = $item->fetch();
    if (!$item) { http_response_code(404); echo 'Item not found'; exit; }

    if ((float)$item['price'] > 0) {
        if (!$licenseKey) { http_response_code(403); echo 'License key required for paid items'; exit; }
        $lic = $pdo->prepare("SELECT status,expires_at FROM {$pfx}mkt_licenses WHERE item_id=? AND license_key=? LIMIT 1");
        $lic->execute([$item['id'],$licenseKey]); $lic = $lic->fetch();
        if (!$lic || $lic['status'] !== 'active') { http_response_code(403); echo 'Invalid or inactive license'; exit; }
        if ($lic['expires_at'] && strtotime($lic['expires_at']) < time()) { http_response_code(403); echo 'License expired'; exit; }
    }

    $ver = $version ?: $item['latest_version'];
    $st = $pdo->prepare("SELECT file_path,file_hash,file_size FROM {$pfx}mkt_item_versions WHERE item_id=? AND version=? AND status='active' LIMIT 1");
    $st->execute([$item['id'],$ver]); $vrow = $st->fetch();
    if (!$vrow || !$vrow['file_path']) { http_response_code(404); echo 'Package not available'; exit; }

    // file_path 예: "/storage/uploads/packages/xxx/xxx.zip" (앞에 /가 있을 수도, 없을 수도)
    $rel = ltrim($vrow['file_path'], '/');
    $filePath = BASE_PATH . '/' . $rel;
    if (!file_exists($filePath)) { http_response_code(404); echo 'File not found on server'; exit; }

    $pdo->prepare("UPDATE {$pfx}mkt_items SET download_count=download_count+1 WHERE id=?")->execute([$item['id']]);

    header('Content-Type: application/zip');
    header('Content-Disposition: attachment; filename="' . $slug . '-' . $ver . '.zip"');
    header('Content-Length: ' . filesize($filePath));
    if ($vrow['file_hash']) header('X-File-Hash: ' . $vrow['file_hash']);
    readfile($filePath);
} catch (Throwable $e) {
    http_response_code(500); echo 'Server error';
}
