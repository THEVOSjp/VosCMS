<?php
/**
 * POST /api/market/item/review
 * Body: { vos_key, domain, slug, rating(1-5), body, reviewer_name? }
 * 응답: { ok, status: 'pending' }
 *
 * VosCMS 라이선스 키 + 도메인으로 발신자 식별. 동일 슬러그/도메인 1회만.
 * 구매자(mkt_licenses 매칭)면 is_verified=1 자동 설정.
 */
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'msg'=>'POST required']); exit; }

try {
    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true);
    if (!is_array($body)) $body = $_POST;

    $vosKey   = trim($body['vos_key']       ?? '');
    $domain   = trim($body['domain']        ?? '');
    $slug     = trim($body['slug']          ?? '');
    $rating   = (int)($body['rating']       ?? 0);
    $bodyTxt  = trim($body['body']          ?? '');
    $name     = trim($body['reviewer_name'] ?? '');

    if (!$vosKey || !$domain || !$slug || $rating < 1 || $rating > 5) {
        http_response_code(400);
        echo json_encode(['ok'=>false,'msg'=>'vos_key, domain, slug, rating(1-5) 필수']); exit;
    }
    if (mb_strlen($bodyTxt) > 2000) { $bodyTxt = mb_substr($bodyTxt, 0, 2000); }
    if (mb_strlen($name) > 100)     { $name    = mb_substr($name, 0, 100); }

    // 도메인 정규화
    $domain = strtolower(preg_replace('#^https?://#', '', rtrim($domain, '/')));
    $domain = preg_replace('#^www\.#', '', $domain);
    $domain = explode('/', $domain)[0];
    $domain = explode('?', $domain)[0];

    $pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $pfx = $_ENV['DB_PREFIX'] ?? 'rzx_';

    // VosCMS 라이선스 키 + 도메인 검증
    $st = $pdo->prepare("SELECT id FROM {$pfx}vos_licenses WHERE license_key = ? AND domain = ? AND status = 'active' LIMIT 1");
    $st->execute([$vosKey, $domain]);
    if (!$st->fetch()) { http_response_code(403); echo json_encode(['ok'=>false,'msg'=>'invalid vos_key/domain']); exit; }

    // 아이템 조회
    $st = $pdo->prepare("SELECT id FROM {$pfx}mkt_items WHERE slug = ? AND status = 'active' LIMIT 1");
    $st->execute([$slug]);
    $item = $st->fetch();
    if (!$item) { http_response_code(404); echo json_encode(['ok'=>false,'msg'=>'item not found']); exit; }
    $itemId = (int)$item['id'];

    // 동일 도메인 + 동일 아이템 1회만
    $st = $pdo->prepare("SELECT id FROM {$pfx}mkt_reviews WHERE item_id = ? AND reviewer_domain = ? LIMIT 1");
    $st->execute([$itemId, $domain]);
    if ($st->fetch()) { http_response_code(409); echo json_encode(['ok'=>false,'msg'=>'이미 리뷰를 작성하셨습니다']); exit; }

    // 구매 여부 → is_verified
    $st = $pdo->prepare(
        "SELECT 1 FROM {$pfx}mkt_licenses l
          WHERE l.item_id = ? AND l.vos_license_key = ? AND l.domain = ? AND l.status = 'active' LIMIT 1"
    );
    $st->execute([$itemId, $vosKey, $domain]);
    $isVerified = $st->fetch() ? 1 : 0;

    // INSERT (status=approved, 검토 없이 즉시 공개)
    $ins = $pdo->prepare(
        "INSERT INTO {$pfx}mkt_reviews (item_id, reviewer_name, reviewer_domain, rating, body, is_verified, status)
         VALUES (?, ?, ?, ?, ?, ?, 'approved')"
    );
    $ins->execute([$itemId, $name ?: null, $domain, $rating, $bodyTxt ?: null, $isVerified]);

    // 아이템 평점·리뷰수 갱신
    $pdo->prepare(
        "UPDATE {$pfx}mkt_items
            SET rating_avg   = (SELECT AVG(rating)   FROM {$pfx}mkt_reviews WHERE item_id = ? AND status = 'approved'),
                rating_count = (SELECT COUNT(*)      FROM {$pfx}mkt_reviews WHERE item_id = ? AND status = 'approved')
          WHERE id = ?"
    )->execute([$itemId, $itemId, $itemId]);

    echo json_encode(['ok'=>true, 'status'=>'approved', 'is_verified'=>(bool)$isVerified]);
} catch (Throwable $e) {
    http_response_code(500); echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
