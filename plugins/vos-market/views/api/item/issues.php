<?php
/**
 * GET /api/market/item/issues?slug=...&type=issue|qna&with_replies=1
 * type 미지정 시 둘 다 반환
 */
header('Content-Type: application/json; charset=utf-8');
try {
    $pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $pfx = $_ENV['DB_PREFIX'] ?? 'rzx_';

    $slug         = trim($_GET['slug'] ?? '');
    $type         = $_GET['type'] ?? '';
    $withReplies  = !empty($_GET['with_replies']);
    if (!$slug) { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'slug required']); exit; }

    $sql = "SELECT i.*, m.slug item_slug
              FROM {$pfx}mkt_issues i
              JOIN {$pfx}mkt_items m ON m.id = i.item_id
             WHERE m.slug = ?";
    $params = [$slug];
    if ($type === 'issue' || $type === 'qna') {
        $sql .= " AND i.type = ?";
        $params[] = $type;
    }
    $sql .= " ORDER BY i.created_at DESC, i.id DESC LIMIT 200";

    $st = $pdo->prepare($sql);
    $st->execute($params);
    $rows = $st->fetchAll();

    // 도메인 부분 마스킹
    $maskDomain = function(?string $d): string {
        if (!$d) return '';
        $parts = explode('.', $d);
        if (count($parts) < 2) return $d;
        $first = $parts[0];
        $masked = mb_substr($first, 0, 2) . str_repeat('*', max(1, mb_strlen($first) - 2));
        return $masked . '.' . implode('.', array_slice($parts, 1));
    };
    foreach ($rows as &$r) {
        $r['author_domain'] = $maskDomain($r['author_domain'] ?? null);
    }
    unset($r);

    if ($withReplies && $rows) {
        $ids = array_column($rows, 'id');
        $in  = implode(',', array_map('intval', $ids));
        $rs  = $pdo->query(
            "SELECT id, issue_id, body, author_name, author_domain, is_partner_reply, is_verified, created_at
               FROM {$pfx}mkt_issue_replies
              WHERE issue_id IN ($in)
              ORDER BY created_at ASC, id ASC"
        )->fetchAll();
        $byIssue = [];
        foreach ($rs as $rep) {
            $rep['author_domain'] = $maskDomain($rep['author_domain'] ?? null);
            $byIssue[$rep['issue_id']][] = $rep;
        }
        foreach ($rows as &$r) {
            $r['replies'] = $byIssue[$r['id']] ?? [];
        }
        unset($r);
    }

    echo json_encode(['ok'=>true, 'data'=>$rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500); echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
