<?php
/**
 * GET /api/market/resolve-keys
 *
 * slug 목록을 받아 product_key 매핑을 반환.
 * VosCMS가 구형 플러그인의 product_key를 가져올 때 사용.
 *
 * Request (Query):
 *   vos_key = RZX-XXXX-XXXX-XXXX
 *   slugs[] = vos-salon&slugs[] = vos-shop
 *
 * Response:
 *   {
 *     "ok": true,
 *     "keys": {
 *       "vos-salon": "uuid",
 *       "vos-shop":  "uuid"
 *     }
 *   }
 */
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'GET required']);
    exit;
}

$vosKey = trim($_GET['vos_key'] ?? '');
$slugs  = $_GET['slugs'] ?? [];

if (!$vosKey || empty($slugs) || !is_array($slugs)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'vos_key, slugs[] 필수']);
    exit;
}

$slugs = array_values(array_unique(array_filter(array_map('trim', $slugs))));

if (count($slugs) > 200) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => '최대 200개']);
    exit;
}

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    $pfx = $_ENV['DB_PREFIX'] ?? 'rzx_';

    // vos_key는 식별자 용도로만 필요 (슬러그→키 매핑은 공개성 정보)
    // 필요시 운영자가 로컬 DB의 status='revoked' 레코드만 차단
    $st = $pdo->prepare("SELECT status FROM {$pfx}vos_licenses WHERE license_key = ? LIMIT 1");
    $st->execute([$vosKey]);
    $licStatus = $st->fetchColumn();
    if ($licStatus === 'revoked') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'message' => '차단된 라이선스 키']);
        exit;
    }

    // slug → product_key 조회
    $phs  = implode(',', array_fill(0, count($slugs), '?'));
    $stItems = $pdo->prepare(
        "SELECT slug, product_key FROM {$pfx}mkt_items WHERE slug IN ($phs) AND status = 'active'"
    );
    $stItems->execute($slugs);

    $keys = [];
    foreach ($stItems->fetchAll() as $row) {
        $keys[$row['slug']] = $row['product_key'];
    }

    echo json_encode(['ok' => true, 'keys' => $keys], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Server error']);
}
