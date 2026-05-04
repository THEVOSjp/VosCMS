<?php
/**
 * VosCMS 1:1 메시지 API (인증 필요)
 *
 * GET  ?action=conversations              → 대화 목록
 * GET  ?action=messages&conversation_id=N → 대화 1개의 메시지 목록 + 자동 read
 * POST action=send (recipient_id|recipient_email|recipient_nickname, body)
 * POST action=delete_conversation (conversation_id)
 * GET  ?action=search_user&q=foo          → 닉네임/이메일 검색 (수신자 자동완성)
 */

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/vendor/autoload.php';

if (empty($_ENV['DB_HOST'])) {
    foreach (file(BASE_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        if (!str_contains($line, '=') || str_starts_with(trim($line), '#')) continue;
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v, " \t\"'");
    }
}

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

session_start();
require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
if (!\RzxLib\Core\Auth\Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'auth required']); exit;
}
$user = \RzxLib\Core\Auth\Auth::user();
$userId = $user['id'];

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'db error']); exit;
}

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $method === 'POST' ? ($_POST['action'] ?? '') : ($_GET['action'] ?? 'conversations');

/**
 * 대화 ID 정규화: user1 = min(idA, idB), user2 = max(idA, idB)
 */
function normalizePair(string $a, string $b): array {
    return strcmp($a, $b) < 0 ? [$a, $b] : [$b, $a];
}

/**
 * 대화 찾기 또는 생성
 */
function getOrCreateConversation(\PDO $pdo, string $prefix, string $a, string $b): int {
    [$u1, $u2] = normalizePair($a, $b);
    $st = $pdo->prepare("SELECT id FROM {$prefix}conversations WHERE user1_id = ? AND user2_id = ? LIMIT 1");
    $st->execute([$u1, $u2]);
    $id = (int)$st->fetchColumn();
    if ($id) return $id;
    $pdo->prepare("INSERT INTO {$prefix}conversations (user1_id, user2_id, created_at) VALUES (?, ?, NOW())")
        ->execute([$u1, $u2]);
    return (int)$pdo->lastInsertId();
}

try {
    switch ($action) {
        // ─── 대화 목록 ───
        case 'conversations':
            $sql = "SELECT c.id, c.user1_id, c.user2_id, c.last_message_at, c.last_preview,
                    CASE WHEN c.user1_id = :uid THEN c.user1_unread ELSE c.user2_unread END AS unread,
                    CASE WHEN c.user1_id = :uid THEN c.user2_id ELSE c.user1_id END AS other_id,
                    u.email, u.name, u.nick_name, u.profile_image, u.avatar
                    FROM {$prefix}conversations c
                    JOIN {$prefix}users u ON u.id = (CASE WHEN c.user1_id = :uid THEN c.user2_id ELSE c.user1_id END)
                    WHERE (c.user1_id = :uid AND c.user1_deleted = 0)
                       OR (c.user2_id = :uid AND c.user2_deleted = 0)
                    ORDER BY c.last_message_at DESC LIMIT 50";
            $st = $pdo->prepare($sql);
            $st->execute(['uid' => $userId]);
            $rows = $st->fetchAll();
            foreach ($rows as &$r) {
                $r['display_name'] = $r['nick_name'] ?: ($r['name'] ?: explode('@', $r['email'] ?? '')[0]);
                $r['avatar_url'] = $r['profile_image'] ?: $r['avatar'] ?: '';
                unset($r['email'], $r['name'], $r['nick_name'], $r['profile_image'], $r['avatar']);
            }
            echo json_encode(['success' => true, 'conversations' => $rows]);
            break;

        // ─── 메시지 목록 (대화 1개) ───
        case 'messages':
            $convId = (int)($_GET['conversation_id'] ?? 0);
            if (!$convId) { echo json_encode(['success'=>false,'message'=>'conv id required']); exit; }
            // 권한 확인
            $cs = $pdo->prepare("SELECT user1_id, user2_id FROM {$prefix}conversations WHERE id = ?");
            $cs->execute([$convId]);
            $c = $cs->fetch();
            if (!$c || ($c['user1_id'] !== $userId && $c['user2_id'] !== $userId)) {
                http_response_code(403);
                echo json_encode(['success'=>false,'message'=>'no access']); exit;
            }
            // 메시지 가져오기 (소프트 삭제 제외)
            $deletedField = $c['user1_id'] === $userId ? 'sender_deleted' : 'recipient_deleted';
            // 단순화: 본인 입장에서 sender 일 때는 sender_deleted, recipient 일 때는 recipient_deleted 가 0이어야
            $sql = "SELECT m.id, m.sender_id, m.body, m.is_read, m.read_at, m.sent_at,
                    CASE WHEN m.sender_id = :uid THEN m.sender_deleted ELSE m.recipient_deleted END AS my_deleted
                    FROM {$prefix}messages m
                    WHERE m.conversation_id = :cid
                      AND ((m.sender_id = :uid AND m.sender_deleted = 0)
                        OR (m.sender_id <> :uid AND m.recipient_deleted = 0))
                    ORDER BY m.sent_at ASC LIMIT 200";
            $ms = $pdo->prepare($sql);
            $ms->execute(['cid' => $convId, 'uid' => $userId]);
            $messages = $ms->fetchAll();

            // 자동 읽음 처리 (내가 받은 메시지만)
            $pdo->prepare("UPDATE {$prefix}messages SET is_read = 1, read_at = NOW()
                WHERE conversation_id = ? AND sender_id <> ? AND is_read = 0")
                ->execute([$convId, $userId]);
            // 대화별 unread 카운트 0으로
            $unreadCol = $c['user1_id'] === $userId ? 'user1_unread' : 'user2_unread';
            $pdo->prepare("UPDATE {$prefix}conversations SET {$unreadCol} = 0 WHERE id = ?")
                ->execute([$convId]);

            // 상대방 정보
            $otherId = $c['user1_id'] === $userId ? $c['user2_id'] : $c['user1_id'];
            $os = $pdo->prepare("SELECT id, email, name, nick_name, profile_image, avatar, bio FROM {$prefix}users WHERE id = ?");
            $os->execute([$otherId]);
            $other = $os->fetch();
            if ($other) {
                $other['display_name'] = $other['nick_name'] ?: ($other['name'] ?: explode('@', $other['email'] ?? '')[0]);
                $other['avatar_url'] = $other['profile_image'] ?: $other['avatar'] ?: '';
            }
            echo json_encode(['success' => true, 'messages' => $messages, 'other' => $other, 'my_id' => $userId]);
            break;

        // ─── 메시지 전송 ───
        case 'send':
            if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false]); exit; }
            $body = trim($_POST['body'] ?? '');
            if ($body === '') { echo json_encode(['success'=>false,'message'=>'본문 필수']); exit; }
            if (mb_strlen($body) > 5000) { echo json_encode(['success'=>false,'message'=>'본문 5000자 초과']); exit; }

            // 수신자 확인 — recipient_id / recipient_email / recipient_nickname / conversation_id 중 하나
            $recipientId = trim($_POST['recipient_id'] ?? '');
            $convId = (int)($_POST['conversation_id'] ?? 0);

            if (!$recipientId && $convId) {
                // 기존 대화로부터 추출
                $cs = $pdo->prepare("SELECT user1_id, user2_id FROM {$prefix}conversations WHERE id = ?");
                $cs->execute([$convId]);
                $c = $cs->fetch();
                if (!$c) { echo json_encode(['success'=>false,'message'=>'대화 없음']); exit; }
                if ($c['user1_id'] !== $userId && $c['user2_id'] !== $userId) {
                    http_response_code(403); echo json_encode(['success'=>false,'message'=>'no access']); exit;
                }
                $recipientId = $c['user1_id'] === $userId ? $c['user2_id'] : $c['user1_id'];
            } elseif (!$recipientId) {
                $email = trim($_POST['recipient_email'] ?? '');
                $nick = trim($_POST['recipient_nickname'] ?? '');
                if ($email !== '') {
                    $rs = $pdo->prepare("SELECT id FROM {$prefix}users WHERE email = ? LIMIT 1");
                    $rs->execute([$email]);
                    $recipientId = $rs->fetchColumn() ?: '';
                } elseif ($nick !== '') {
                    $rs = $pdo->prepare("SELECT id FROM {$prefix}users WHERE nick_name = ? LIMIT 1");
                    $rs->execute([$nick]);
                    $recipientId = $rs->fetchColumn() ?: '';
                }
            }
            if (!$recipientId) { echo json_encode(['success'=>false,'message'=>'수신자를 찾을 수 없습니다']); exit; }
            if ($recipientId === $userId) { echo json_encode(['success'=>false,'message'=>'본인에게는 보낼 수 없습니다']); exit; }

            // 차단 확인 — 수신자가 발신자를 차단했는지
            $bk = $pdo->prepare("SELECT 1 FROM {$prefix}message_blocks WHERE blocker_id = ? AND blocked_id = ?");
            $bk->execute([$recipientId, $userId]);
            if ($bk->fetchColumn()) { echo json_encode(['success'=>false,'message'=>'메시지를 보낼 수 없습니다 (차단됨)']); exit; }

            // 수신자의 수신 설정 확인
            $rs = $pdo->prepare("SELECT allow_messages_from FROM {$prefix}users WHERE id = ?");
            $rs->execute([$recipientId]);
            $allowSetting = $rs->fetchColumn();
            if ($allowSetting === 'none') {
                echo json_encode(['success'=>false,'message'=>'수신자가 메시지를 받지 않도록 설정했습니다']); exit;
            }
            if ($allowSetting === 'followers') {
                // 수신자가 발신자를 팔로우하고 있는지 (수신자의 팔로워 = follower_id 가 발신자)
                $fl = $pdo->prepare("SELECT 1 FROM {$prefix}user_follows WHERE follower_id = ? AND following_id = ?");
                $fl->execute([$recipientId, $userId]);
                if (!$fl->fetchColumn()) {
                    echo json_encode(['success'=>false,'message'=>'수신자는 본인이 팔로우하는 사용자에게만 메시지를 받습니다']); exit;
                }
            }

            // rate limit — 1분에 5건, 1일 50건
            $rl = $pdo->prepare("SELECT
                SUM(CASE WHEN sent_at > DATE_SUB(NOW(), INTERVAL 1 MINUTE) THEN 1 ELSE 0 END) AS m1,
                SUM(CASE WHEN sent_at > DATE_SUB(NOW(), INTERVAL 1 DAY) THEN 1 ELSE 0 END) AS d1
                FROM {$prefix}messages WHERE sender_id = ?");
            $rl->execute([$userId]);
            $rates = $rl->fetch();
            if ((int)($rates['m1'] ?? 0) >= 5) { echo json_encode(['success'=>false,'message'=>'1분에 최대 5건까지 발송 가능합니다']); exit; }
            if ((int)($rates['d1'] ?? 0) >= 50) { echo json_encode(['success'=>false,'message'=>'1일 최대 50건 한도 초과']); exit; }

            // 트랜잭션
            $pdo->beginTransaction();
            try {
                $cidInsert = $convId ?: getOrCreateConversation($pdo, $prefix, $userId, $recipientId);
                $pdo->prepare("INSERT INTO {$prefix}messages (conversation_id, sender_id, body, sent_at) VALUES (?, ?, ?, NOW())")
                    ->execute([$cidInsert, $userId, $body]);
                $msgId = (int)$pdo->lastInsertId();

                // 대화 갱신: last_message + unread 증가 (수신자만)
                $preview = mb_substr($body, 0, 200);
                [$u1, $u2] = normalizePair($userId, $recipientId);
                $unreadCol = $recipientId === $u1 ? 'user1_unread' : 'user2_unread';
                $pdo->prepare("UPDATE {$prefix}conversations SET
                    last_message_id = ?, last_message_at = NOW(), last_preview = ?,
                    {$unreadCol} = {$unreadCol} + 1,
                    user1_deleted = CASE WHEN user1_id = ? THEN user1_deleted ELSE 0 END,
                    user2_deleted = CASE WHEN user2_id = ? THEN user2_deleted ELSE 0 END
                    WHERE id = ?")
                    ->execute([$msgId, $preview, $userId, $userId, $cidInsert]);

                // 수신자 알림 적재
                $senderDisplay = $user['nick_name'] ?? $user['name'] ?? explode('@', $user['email'] ?? '')[0];
                $pdo->prepare("INSERT INTO {$prefix}notifications
                    (user_id, type, category, title, body, link, icon, expires_at, meta)
                    VALUES (?, 'message', 'new_message', ?, ?, ?, 'message', DATE_ADD(NOW(), INTERVAL 90 DAY), ?)")
                    ->execute([
                        $recipientId,
                        "{$senderDisplay} 님의 새 메시지",
                        $preview,
                        '/mypage/messages?c=' . $cidInsert,
                        json_encode(['conversation_id' => $cidInsert, 'message_id' => $msgId, 'sender_id' => $userId], JSON_UNESCAPED_UNICODE),
                    ]);

                $pdo->commit();
                echo json_encode([
                    'success' => true,
                    'message_id' => $msgId,
                    'conversation_id' => $cidInsert,
                ]);
            } catch (\Throwable $e) {
                $pdo->rollBack();
                error_log('[api/messages] send: ' . $e->getMessage());
                echo json_encode(['success' => false, 'message' => 'send failed']);
            }
            break;

        // ─── 대화 삭제 (소프트 — 본인만) ───
        case 'delete_conversation':
            if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false]); exit; }
            $convId = (int)($_POST['conversation_id'] ?? 0);
            if (!$convId) { echo json_encode(['success'=>false,'message'=>'conv id required']); exit; }
            $cs = $pdo->prepare("SELECT user1_id, user2_id FROM {$prefix}conversations WHERE id = ?");
            $cs->execute([$convId]);
            $c = $cs->fetch();
            if (!$c) { echo json_encode(['success'=>false,'message'=>'대화 없음']); exit; }
            $col = $c['user1_id'] === $userId ? 'user1_deleted' : ($c['user2_id'] === $userId ? 'user2_deleted' : null);
            if (!$col) { http_response_code(403); echo json_encode(['success'=>false]); exit; }
            $pdo->prepare("UPDATE {$prefix}conversations SET {$col} = 1 WHERE id = ?")
                ->execute([$convId]);
            echo json_encode(['success' => true]);
            break;

        // ─── 사용자 검색 (수신자 자동완성) ───
        case 'search_user':
            $q = trim($_GET['q'] ?? '');
            if (mb_strlen($q) < 2) { echo json_encode(['success'=>true,'users'=>[]]); exit; }
            $like = '%' . str_replace(['%','_'], ['\%','\_'], $q) . '%';
            // 닉네임 또는 이메일 매칭, 본인 제외, 차단된 사용자 제외
            $sql = "SELECT u.id, u.nick_name, u.email, u.name, u.profile_image, u.avatar
                    FROM {$prefix}users u
                    WHERE u.id <> :me
                      AND u.is_active = 1
                      AND (u.nick_name LIKE :q OR u.email LIKE :q OR u.name LIKE :q)
                      AND NOT EXISTS (SELECT 1 FROM {$prefix}message_blocks b WHERE b.blocker_id = u.id AND b.blocked_id = :me)
                    ORDER BY (u.nick_name LIKE :prefix) DESC, u.last_login_at DESC LIMIT 10";
            $st = $pdo->prepare($sql);
            $st->execute(['me' => $userId, 'q' => $like, 'prefix' => $q . '%']);
            $rows = $st->fetchAll();
            foreach ($rows as &$r) {
                $r['display_name'] = $r['nick_name'] ?: ($r['name'] ?: explode('@', $r['email'])[0]);
                $r['avatar_url'] = $r['profile_image'] ?: $r['avatar'] ?: '';
                // email 은 선두 일부만 노출 (프라이버시)
                $em = $r['email'] ?? '';
                $r['email_masked'] = $em ? substr($em, 0, 2) . '***' . substr(strrchr($em, '@'), 0) : '';
                unset($r['email'], $r['name'], $r['profile_image'], $r['avatar']);
            }
            echo json_encode(['success' => true, 'users' => $rows]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'unknown action']);
    }
} catch (\Throwable $e) {
    error_log('[api/messages] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'server error']);
}
