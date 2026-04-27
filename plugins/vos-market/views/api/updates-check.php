<?php
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'msg'=>'POST required']); exit; }
try {
    $pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $pfx = $_ENV['DB_PREFIX'] ?? 'rzx_';

    $body = json_decode(file_get_contents('php://input'), true) ?: [];
    $installed = $body['plugins'] ?? $_POST['plugins'] ?? [];
    if (!is_array($installed) || empty($installed)) { echo json_encode(['ok'=>true,'updates'=>[]]); exit; }

    $updates = [];
    foreach ($installed as $entry) {
        $slug    = $entry['slug'] ?? '';
        $current = $entry['version'] ?? '0.0.0';
        if (!$slug) continue;
        $st = $pdo->prepare("SELECT latest_version,min_voscms_version,min_php_version FROM {$pfx}mkt_items WHERE slug=? AND status='active'");
        $st->execute([$slug]); $item = $st->fetch();
        if (!$item) continue;
        if (version_compare($item['latest_version'], $current, '>')) {
            $updates[] = [
                'slug'               => $slug,
                'latest_version'     => $item['latest_version'],
                'current_version'    => $current,
                'min_voscms_version' => $item['min_voscms_version'],
                'min_php_version'    => $item['min_php_version'],
            ];
        }
    }
    echo json_encode(['ok'=>true,'updates'=>$updates], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500); echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
