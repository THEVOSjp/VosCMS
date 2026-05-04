<?php
/**
 * VosCMS 차단·신고 API (인증 필요)
 *
 * POST action=block (target_id, reason?)
 * POST action=unblock (target_id)
 * GET  ?action=status&target_id=X      → { is_blocked }
 * GET  ?action=list                    → 본인이 차단한 사용자 목록
 * POST action=report (target_user_id, reason, detail?, message_id?)
 */

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/api/_session-bootstrap.php';
require_once BASE_PATH . '/vendor/autoload.php';
require_once BASE_PATH . '/rzxlib/Core/Helpers/functions.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
if (!\RzxLib\Core\Auth\Auth::check()) {
    http_response_code(401); echo json_encode(['success'=>false,'message'=>'auth required']); exit;
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

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$action = $method === 'POST' ? ($_POST['action'] ?? '') : ($_GET['action'] ?? '');

try {
    switch ($action) {
        case 'block':
            $target = trim($_POST['target_id'] ?? '');
            $reason = trim($_POST['reason'] ?? '');
            if (!$target || $target === $userId) { echo json_encode(['success'=>false,'message'=>'invalid target']); exit; }
            $pdo->prepare("INSERT IGNORE INTO {$prefix}message_blocks (blocker_id, blocked_id, reason, created_at) VALUES (?, ?, ?, NOW())")
                ->execute([$userId, $target, $reason ?: null]);
            // 팔로우 관계 자동 정리 — 양방향 모두 해제
            $pdo->prepare("DELETE FROM {$prefix}user_follows WHERE (follower_id = ? AND following_id = ?) OR (follower_id = ? AND following_id = ?)")
                ->execute([$userId, $target, $target, $userId]);
            echo json_encode(['success'=>true, 'is_blocked'=>true]);
            break;

        case 'unblock':
            $target = trim($_POST['target_id'] ?? '');
            if (!$target) { echo json_encode(['success'=>false,'message'=>'target required']); exit; }
            $pdo->prepare("DELETE FROM {$prefix}message_blocks WHERE blocker_id = ? AND blocked_id = ?")
                ->execute([$userId, $target]);
            echo json_encode(['success'=>true, 'is_blocked'=>false]);
            break;

        case 'status':
            $target = trim($_GET['target_id'] ?? '');
            if (!$target) { echo json_encode(['success'=>false]); exit; }
            $st = $pdo->prepare("SELECT 1 FROM {$prefix}message_blocks WHERE blocker_id = ? AND blocked_id = ?");
            $st->execute([$userId, $target]);
            echo json_encode(['success'=>true, 'is_blocked'=> (bool)$st->fetchColumn()]);
            break;

        case 'list':
            $st = $pdo->prepare("SELECT b.blocked_id, b.reason, b.created_at,
                u.nick_name, u.name, u.email, u.profile_image, u.avatar
                FROM {$prefix}message_blocks b
                JOIN {$prefix}users u ON u.id = b.blocked_id
                WHERE b.blocker_id = ?
                ORDER BY b.created_at DESC LIMIT 200");
            $st->execute([$userId]);
            $rows = $st->fetchAll();
            foreach ($rows as &$r) {
                $nameDec = decrypt($r['name'] ?? '');
                $r['display_name'] = $r['nick_name'] ?: ($nameDec ?: explode('@', $r['email'] ?? '')[0]);
                $r['avatar_url'] = $r['profile_image'] ?: $r['avatar'] ?: '';
                unset($r['name'], $r['email'], $r['profile_image'], $r['avatar']);
            }
            echo json_encode(['success'=>true, 'blocks'=>$rows]);
            break;

        case 'report':
            if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false]); exit; }
            $target = trim($_POST['target_user_id'] ?? '');
            $reason = trim($_POST['reason'] ?? '');
            $detail = trim($_POST['detail'] ?? '');
            $msgId = (int)($_POST['message_id'] ?? 0) ?: null;
            $allowed = ['spam','harassment','inappropriate','other'];
            if (!$target || !in_array($reason, $allowed, true)) {
                echo json_encode(['success'=>false,'message'=>'invalid target/reason']); exit;
            }
            // 동일 사용자에 대한 24h 내 중복 신고 방지
            $dup = $pdo->prepare("SELECT 1 FROM {$prefix}message_reports
                WHERE reporter_id = ? AND target_user_id = ? AND created_at > DATE_SUB(NOW(), INTERVAL 24 HOUR)");
            $dup->execute([$userId, $target]);
            if ($dup->fetchColumn()) {
                echo json_encode(['success'=>false,'message'=>'24시간 내 중복 신고는 처리되지 않습니다.']); exit;
            }
            $pdo->prepare("INSERT INTO {$prefix}message_reports
                (reporter_id, target_user_id, message_id, reason, detail, status, created_at)
                VALUES (?, ?, ?, ?, ?, 'pending', NOW())")
                ->execute([$userId, $target, $msgId, $reason, $detail ?: null]);
            echo json_encode(['success'=>true]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success'=>false,'message'=>'unknown action']);
    }
} catch (\Throwable $e) {
    error_log('[api/blocks] ' . $e->getMessage());
    echo json_encode(['success'=>false,'message'=>'server error']);
}
