<?php
/**
 * VosCMS 팔로우 API (단방향, Twitter 방식)
 *
 * POST action=follow (target_id)
 * POST action=unfollow (target_id)
 * GET  ?action=status&target_id=X        → { is_following, follows_me }
 * GET  ?action=followers&user_id=X       → 팔로워 목록 (X 기준)
 * GET  ?action=following&user_id=X       → 팔로잉 목록 (X 가 팔로우하는 사람)
 * GET  ?action=counts&user_id=X          → { followers, following }
 */

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));
require_once BASE_PATH . '/api/_session-bootstrap.php';
require_once BASE_PATH . '/vendor/autoload.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
$isLoggedIn = \RzxLib\Core\Auth\Auth::check();
$user = $isLoggedIn ? \RzxLib\Core\Auth\Auth::user() : null;
$userId = $user['id'] ?? '';

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
$action = $method === 'POST' ? ($_POST['action'] ?? '') : ($_GET['action'] ?? '');

// counts / followers / following / status 는 비로그인도 일부 조회 가능 (프로필 공개 시)
$writeActions = ['follow','unfollow'];
if (in_array($action, $writeActions, true) && !$isLoggedIn) {
    http_response_code(401);
    echo json_encode(['success' => false, 'message' => 'auth required']); exit;
}

try {
    switch ($action) {
        case 'follow':
            $target = trim($_POST['target_id'] ?? '');
            if (!$target || $target === $userId) { echo json_encode(['success'=>false,'message'=>'invalid target']); exit; }
            // 대상 사용자 존재 확인
            $ck = $pdo->prepare("SELECT 1 FROM {$prefix}users WHERE id = ? AND is_active = 1");
            $ck->execute([$target]);
            if (!$ck->fetchColumn()) { echo json_encode(['success'=>false,'message'=>'user not found']); exit; }

            // INSERT IGNORE 로 중복 방지
            $pdo->prepare("INSERT IGNORE INTO {$prefix}user_follows (follower_id, following_id, created_at) VALUES (?, ?, NOW())")
                ->execute([$userId, $target]);

            // 알림 적재 (대상에게)
            $senderDisplay = $user['nick_name'] ?? $user['name'] ?? explode('@', $user['email'] ?? '')[0];
            $pdo->prepare("INSERT INTO {$prefix}notifications
                (user_id, type, category, title, body, link, icon, expires_at, meta)
                VALUES (?, 'follow', 'new_follower', ?, ?, ?, 'bell', DATE_ADD(NOW(), INTERVAL 90 DAY), ?)")
                ->execute([
                    $target,
                    "{$senderDisplay} 님이 회원님을 팔로우합니다",
                    "프로필을 확인해 보세요.",
                    '/profile/' . $userId,
                    json_encode(['follower_id' => $userId], JSON_UNESCAPED_UNICODE),
                ]);

            // 카운트 반환
            $cnt = $pdo->prepare("SELECT
                (SELECT COUNT(*) FROM {$prefix}user_follows WHERE following_id = ?) AS followers,
                (SELECT COUNT(*) FROM {$prefix}user_follows WHERE follower_id = ?) AS following");
            $cnt->execute([$target, $target]);
            $c = $cnt->fetch();
            echo json_encode(['success' => true, 'is_following' => true, 'counts' => $c]);
            break;

        case 'unfollow':
            $target = trim($_POST['target_id'] ?? '');
            if (!$target) { echo json_encode(['success'=>false,'message'=>'target required']); exit; }
            $pdo->prepare("DELETE FROM {$prefix}user_follows WHERE follower_id = ? AND following_id = ?")
                ->execute([$userId, $target]);
            $cnt = $pdo->prepare("SELECT
                (SELECT COUNT(*) FROM {$prefix}user_follows WHERE following_id = ?) AS followers,
                (SELECT COUNT(*) FROM {$prefix}user_follows WHERE follower_id = ?) AS following");
            $cnt->execute([$target, $target]);
            echo json_encode(['success' => true, 'is_following' => false, 'counts' => $cnt->fetch()]);
            break;

        case 'status':
            $target = trim($_GET['target_id'] ?? '');
            if (!$target) { echo json_encode(['success'=>false,'message'=>'target required']); exit; }
            $isFollowing = false; $followsMe = false;
            if ($isLoggedIn && $userId !== $target) {
                $st = $pdo->prepare("SELECT
                    EXISTS(SELECT 1 FROM {$prefix}user_follows WHERE follower_id = ? AND following_id = ?) AS is_following,
                    EXISTS(SELECT 1 FROM {$prefix}user_follows WHERE follower_id = ? AND following_id = ?) AS follows_me");
                $st->execute([$userId, $target, $target, $userId]);
                $r = $st->fetch();
                $isFollowing = (bool)$r['is_following'];
                $followsMe = (bool)$r['follows_me'];
            }
            echo json_encode(['success' => true, 'is_following' => $isFollowing, 'follows_me' => $followsMe]);
            break;

        case 'counts':
            $target = trim($_GET['user_id'] ?? '');
            if (!$target) { echo json_encode(['success'=>false]); exit; }
            $cnt = $pdo->prepare("SELECT
                (SELECT COUNT(*) FROM {$prefix}user_follows WHERE following_id = ?) AS followers,
                (SELECT COUNT(*) FROM {$prefix}user_follows WHERE follower_id = ?) AS following");
            $cnt->execute([$target, $target]);
            echo json_encode(['success' => true, 'counts' => $cnt->fetch()]);
            break;

        case 'followers':
        case 'following':
            $target = trim($_GET['user_id'] ?? '');
            if (!$target) { echo json_encode(['success'=>false]); exit; }
            $sql = $action === 'followers'
                ? "SELECT u.id, u.nick_name, u.name, u.email, u.profile_image, u.avatar, u.bio, f.created_at
                   FROM {$prefix}user_follows f JOIN {$prefix}users u ON u.id = f.follower_id
                   WHERE f.following_id = ? AND u.is_active = 1
                   ORDER BY f.created_at DESC LIMIT 100"
                : "SELECT u.id, u.nick_name, u.name, u.email, u.profile_image, u.avatar, u.bio, f.created_at
                   FROM {$prefix}user_follows f JOIN {$prefix}users u ON u.id = f.following_id
                   WHERE f.follower_id = ? AND u.is_active = 1
                   ORDER BY f.created_at DESC LIMIT 100";
            $st = $pdo->prepare($sql);
            $st->execute([$target]);
            $rows = $st->fetchAll();
            foreach ($rows as &$r) {
                $r['display_name'] = $r['nick_name'] ?: ($r['name'] ?: explode('@', $r['email'])[0]);
                $r['avatar_url'] = $r['profile_image'] ?: $r['avatar'] ?: '';
                unset($r['email'], $r['profile_image'], $r['avatar']);
            }
            echo json_encode(['success' => true, 'users' => $rows]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'unknown action']);
    }
} catch (\Throwable $e) {
    error_log('[api/follows] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'server error']);
}
