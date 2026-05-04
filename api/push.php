<?php
/**
 * VosCMS Web Push API (인증 필요)
 *
 * GET  ?action=public_key              → VAPID 공개 키 (구독 등록용)
 * POST action=subscribe (endpoint, p256dh, auth, user_agent?)
 * POST action=unsubscribe (endpoint)
 * POST action=test                     → 본인에게 테스트 푸시
 */

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/api/_session-bootstrap.php';
require_once BASE_PATH . '/vendor/autoload.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
$action = ($_SERVER['REQUEST_METHOD'] === 'POST')
    ? ($_POST['action'] ?? '')
    : ($_GET['action'] ?? 'public_key');

// public_key 만 비로그인 허용 (Service Worker 등록 시 필요)
if ($action !== 'public_key' && !\RzxLib\Core\Auth\Auth::check()) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'auth required']); exit;
}

// public_key 응답
if ($action === 'public_key') {
    $key = $_ENV['VAPID_PUBLIC_KEY'] ?? '';
    echo json_encode(['success' => true, 'public_key' => $key]);
    exit;
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
    echo json_encode(['success'=>false,'message'=>'db error']); exit;
}

try {
    switch ($action) {
        case 'subscribe':
            $endpoint = trim($_POST['endpoint'] ?? '');
            $p256dh   = trim($_POST['p256dh'] ?? '');
            $auth     = trim($_POST['auth'] ?? '');
            $ua       = trim($_POST['user_agent'] ?? ($_SERVER['HTTP_USER_AGENT'] ?? ''));
            if (!$endpoint || !$p256dh || !$auth) {
                echo json_encode(['success'=>false,'message'=>'endpoint/p256dh/auth required']); exit;
            }
            // UPSERT — 동일 endpoint 가 있으면 user 와 키 업데이트
            $pdo->prepare("INSERT INTO {$prefix}push_subscriptions
                (user_id, endpoint, p256dh, auth, user_agent, created_at, last_used_at)
                VALUES (?, ?, ?, ?, ?, NOW(), NOW())
                ON DUPLICATE KEY UPDATE user_id = VALUES(user_id), p256dh = VALUES(p256dh), auth = VALUES(auth), user_agent = VALUES(user_agent), last_used_at = NOW()")
                ->execute([$userId, $endpoint, $p256dh, $auth, $ua]);
            echo json_encode(['success'=>true]);
            break;

        case 'unsubscribe':
            $endpoint = trim($_POST['endpoint'] ?? '');
            if (!$endpoint) { echo json_encode(['success'=>false,'message'=>'endpoint required']); exit; }
            $pdo->prepare("DELETE FROM {$prefix}push_subscriptions WHERE user_id = ? AND endpoint = ?")
                ->execute([$userId, $endpoint]);
            echo json_encode(['success'=>true]);
            break;

        case 'test':
            require_once BASE_PATH . '/rzxlib/Core/Notification/PushSender.php';
            $sender = new \RzxLib\Core\Notification\PushSender($pdo, $prefix);
            $payload = [
                'title' => 'VosCMS 푸시 테스트',
                'body'  => '알림이 정상 작동합니다.',
                'link'  => '/mypage/messages',
                'icon'  => '/manifest/icon-192.png',
            ];
            $r = $sender->sendToUser($userId, $payload);
            echo json_encode(['success'=>true, 'sent' => $r['sent'] ?? 0, 'failed' => $r['failed'] ?? 0]);
            break;

        // ─── 어드민 공지 발송 ───
        case 'admin_broadcast':
            if (!in_array($user['role'] ?? '', ['admin','supervisor','owner'], true)) {
                http_response_code(403); echo json_encode(['success'=>false,'message'=>'forbidden']); exit;
            }
            $title = trim($_POST['title'] ?? '');
            $body  = trim($_POST['body'] ?? '');
            $link  = trim($_POST['link'] ?? '') ?: null;
            $audience = trim($_POST['audience'] ?? 'all'); // all | hosting | role_admin | role_member
            $sendPush = !empty($_POST['send_push']);
            if ($title === '' || $body === '') {
                echo json_encode(['success'=>false,'message'=>'title/body required']); exit;
            }

            // 대상 사용자 ID 수집
            $targetIds = [];
            try {
                if ($audience === 'all') {
                    $st = $pdo->query("SELECT id FROM {$prefix}users WHERE is_active = 1");
                    $targetIds = $st->fetchAll(\PDO::FETCH_COLUMN);
                } elseif ($audience === 'hosting') {
                    $st = $pdo->query("SELECT DISTINCT user_id FROM {$prefix}subscriptions WHERE type = 'hosting' AND status = 'active' AND user_id IS NOT NULL");
                    $targetIds = $st->fetchAll(\PDO::FETCH_COLUMN);
                } elseif ($audience === 'role_admin') {
                    $st = $pdo->query("SELECT id FROM {$prefix}users WHERE is_active = 1 AND role IN ('admin','supervisor','owner')");
                    $targetIds = $st->fetchAll(\PDO::FETCH_COLUMN);
                } elseif ($audience === 'role_member') {
                    $st = $pdo->query("SELECT id FROM {$prefix}users WHERE is_active = 1 AND (role IS NULL OR role NOT IN ('admin','supervisor','owner'))");
                    $targetIds = $st->fetchAll(\PDO::FETCH_COLUMN);
                }
            } catch (\Throwable $e) {
                echo json_encode(['success'=>false,'message'=>'target query failed']); exit;
            }

            if (empty($targetIds)) {
                echo json_encode(['success'=>true, 'inserted'=>0, 'pushed'=>0, 'message'=>'대상 사용자가 없습니다']); exit;
            }

            // notifications 일괄 INSERT
            $insStmt = $pdo->prepare("INSERT INTO {$prefix}notifications
                (user_id, type, category, title, body, link, icon, expires_at)
                VALUES (?, 'admin', 'broadcast', ?, ?, ?, 'bell', DATE_ADD(NOW(), INTERVAL 90 DAY))");
            $inserted = 0;
            $pdo->beginTransaction();
            try {
                foreach ($targetIds as $tid) {
                    $insStmt->execute([$tid, $title, $body, $link]);
                    $inserted++;
                }
                $pdo->commit();
            } catch (\Throwable $e) {
                $pdo->rollBack();
                error_log('[push.broadcast] insert: ' . $e->getMessage());
                echo json_encode(['success'=>false,'message'=>'insert failed']); exit;
            }

            // 푸시 발송
            $pushed = 0;
            if ($sendPush) {
                try {
                    require_once BASE_PATH . '/rzxlib/Core/Notification/PushSender.php';
                    $sender = new \RzxLib\Core\Notification\PushSender($pdo, $prefix);
                    // 100명씩 배치
                    foreach (array_chunk($targetIds, 100) as $batch) {
                        $r = $sender->sendToUsers($batch, [
                            'title' => $title,
                            'body'  => $body,
                            'link'  => $link,
                            'tag'   => 'admin-broadcast',
                        ]);
                        $pushed += (int)($r['sent'] ?? 0);
                    }
                } catch (\Throwable $e) { error_log('[push.broadcast] push: ' . $e->getMessage()); }
            }

            echo json_encode(['success'=>true, 'inserted'=>$inserted, 'pushed'=>$pushed, 'targets'=>count($targetIds)]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success'=>false,'message'=>'unknown action']);
    }
} catch (\Throwable $e) {
    error_log('[api/push] ' . $e->getMessage());
    echo json_encode(['success'=>false,'message'=>'server error']);
}
