<?php
/**
 * POST /api/market/item/issue/reply
 * Body: { vos_key, domain, issue_id, body, author_name }
 */
header('Content-Type: application/json; charset=utf-8');
if ($_SERVER['REQUEST_METHOD'] !== 'POST') { http_response_code(405); echo json_encode(['ok'=>false,'msg'=>'POST required']); exit; }

try {
    $raw  = file_get_contents('php://input');
    $body = json_decode($raw, true);
    if (!is_array($body)) $body = $_POST;

    $vosKey  = trim($body['vos_key']     ?? '');
    $domain  = trim($body['domain']      ?? '');
    $issueId = (int)($body['issue_id']   ?? 0);
    $bodyTxt = trim($body['body']        ?? '');
    $name    = trim($body['author_name'] ?? '');

    if (!$vosKey || !$domain || !$issueId || $bodyTxt === '') {
        http_response_code(400);
        echo json_encode(['ok'=>false,'msg'=>'vos_key, domain, issue_id, body 필수']); exit;
    }
    if (mb_strlen($bodyTxt) > 5000) $bodyTxt = mb_substr($bodyTxt, 0, 5000);
    if (mb_strlen($name)    > 100)  $name    = mb_substr($name, 0, 100);

    $domain = strtolower(preg_replace('#^https?://#', '', rtrim($domain, '/')));
    $domain = preg_replace('#^www\.#', '', $domain);
    $domain = explode('/', $domain)[0];
    $domain = explode('?', $domain)[0];

    $pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $pfx = $_ENV['DB_PREFIX'] ?? 'rzx_';

    $st = $pdo->prepare("SELECT id FROM {$pfx}vos_licenses WHERE license_key = ? AND domain = ? AND status = 'active' LIMIT 1");
    $st->execute([$vosKey, $domain]);
    if (!$st->fetch()) { http_response_code(403); echo json_encode(['ok'=>false,'msg'=>'invalid vos_key/domain']); exit; }

    // 이슈 존재 확인 + item_id 가져오기
    $st = $pdo->prepare("SELECT item_id FROM {$pfx}mkt_issues WHERE id = ? LIMIT 1");
    $st->execute([$issueId]);
    $row = $st->fetch();
    if (!$row) { http_response_code(404); echo json_encode(['ok'=>false,'msg'=>'issue not found']); exit; }
    $itemId = (int)$row['item_id'];

    // 구매 인증
    $st = $pdo->prepare(
        "SELECT 1 FROM {$pfx}mkt_licenses
          WHERE item_id = ? AND vos_license_key = ? AND domain = ? AND status = 'active' LIMIT 1"
    );
    $st->execute([$itemId, $vosKey, $domain]);
    $isVerified = $st->fetch() ? 1 : 0;

    // (향후) 파트너가 작성한 답변 여부 — 현재는 0
    $isPartner = 0;

    $ins = $pdo->prepare(
        "INSERT INTO {$pfx}mkt_issue_replies (issue_id, body, author_name, author_domain, is_partner_reply, is_verified)
         VALUES (?, ?, ?, ?, ?, ?)"
    );
    $ins->execute([$issueId, $bodyTxt, $name ?: null, $domain, $isPartner, $isVerified]);

    // reply_count + updated_at 갱신
    $pdo->prepare("UPDATE {$pfx}mkt_issues SET reply_count = reply_count + 1, updated_at = NOW() WHERE id = ?")
        ->execute([$issueId]);

    echo json_encode([
        'ok' => true,
        'id' => (int)$pdo->lastInsertId(),
        'is_verified' => (bool)$isVerified,
    ]);
} catch (Throwable $e) {
    http_response_code(500); echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
