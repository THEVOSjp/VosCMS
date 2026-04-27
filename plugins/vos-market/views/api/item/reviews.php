<?php
/**
 * GET /api/market/item/reviews?slug=...&limit=20
 * 승인된 리뷰만 반환
 */
header('Content-Type: application/json; charset=utf-8');
try {
    $pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $pfx = $_ENV['DB_PREFIX'] ?? 'rzx_';

    $slug  = trim($_GET['slug'] ?? '');
    $limit = min(50, max(1, (int)($_GET['limit'] ?? 20)));
    if (!$slug) { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'slug required']); exit; }

    $st = $pdo->prepare(
        "SELECT r.id, r.rating, r.body, r.reviewer_name, r.reviewer_domain, r.is_verified, r.created_at
           FROM {$pfx}mkt_reviews r
           JOIN {$pfx}mkt_items i ON i.id = r.item_id
          WHERE i.slug = ? AND r.status = 'approved'
          ORDER BY r.created_at DESC
          LIMIT $limit"
    );
    $st->execute([$slug]);
    $reviews = $st->fetchAll();

    // 도메인 마스킹 (개인정보 보호 — 일부만 공개)
    foreach ($reviews as &$r) {
        if (!empty($r['reviewer_domain'])) {
            $parts = explode('.', $r['reviewer_domain']);
            if (count($parts) >= 2) {
                $first = $parts[0];
                $masked = mb_substr($first, 0, 2) . str_repeat('*', max(1, mb_strlen($first) - 2));
                $r['reviewer_domain'] = $masked . '.' . implode('.', array_slice($parts, 1));
            }
        }
    }
    unset($r);

    echo json_encode(['ok'=>true, 'data'=>$reviews], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500); echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
