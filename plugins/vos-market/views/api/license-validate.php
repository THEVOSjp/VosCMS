<?php
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'msg'=>'POST required']); exit; }
try {
    $pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $pfx = $_ENV['DB_PREFIX'] ?? 'rzx_';

    $key    = trim($_POST['license_key'] ?? '');
    $slug   = trim($_POST['slug'] ?? '');
    $domain = trim($_POST['domain'] ?? '');

    // domain + vos_key 가 메인 키 — 셋 다 필수
    if (!$key || !$slug || !$domain) {
        http_response_code(400);
        echo json_encode(['ok'=>false,'msg'=>'license_key, slug, domain 모두 필수']);
        exit;
    }

    // domain 정규화 (https:// 등 제거)
    $domain = strtolower(preg_replace('#^https?://#', '', rtrim($domain, '/')));
    $domain = preg_replace('#^www\.#', '', $domain);
    $domain = explode('/', $domain)[0];
    $domain = explode('?', $domain)[0];

    // license_key + slug + domain 세 조건 모두 일치해야 유효
    $st = $pdo->prepare("
        SELECT l.*, i.slug item_slug
          FROM {$pfx}mkt_licenses l
          JOIN {$pfx}mkt_items i ON i.id = l.item_id
         WHERE l.license_key = ?
           AND i.slug = ?
           AND l.domain = ?
         LIMIT 1
    ");
    $st->execute([$key, $slug, $domain]);
    $lic = $st->fetch();

    if (!$lic) {
        echo json_encode(['ok'=>false,'valid'=>false,'msg'=>'라이선스가 유효하지 않거나 도메인이 일치하지 않습니다']);
        exit;
    }
    if ($lic['status'] !== 'active') {
        echo json_encode(['ok'=>false,'valid'=>false,'msg'=>'라이선스가 활성 상태가 아닙니다','status'=>$lic['status']]);
        exit;
    }
    if ($lic['expires_at'] && strtotime($lic['expires_at']) < time()) {
        $pdo->prepare("UPDATE {$pfx}mkt_licenses SET status='expired' WHERE id=?")->execute([$lic['id']]);
        echo json_encode(['ok'=>false,'valid'=>false,'msg'=>'라이선스가 만료되었습니다']);
        exit;
    }

    // 활성화 레코드 last_check_at 갱신
    $pdo->prepare("
        UPDATE {$pfx}mkt_license_activations
           SET last_check_at = NOW()
         WHERE license_id = ? AND domain = ?
    ")->execute([$lic['id'], $domain]);

    echo json_encode([
        'ok'      => true,
        'valid'   => true,
        'license' => [
            'key'         => $lic['license_key'],
            'domain'      => $lic['domain'],
            'expires_at'  => $lic['expires_at'],
            'activations' => $lic['activation_count'],
            'max'         => $lic['max_activations'],
        ],
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500); echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
