<?php
/**
 * vos-community — 어드민 공지 일괄 발송
 * dispatchToPlugin 으로 진입.
 *
 * POST action=broadcast (title, body, link?, audience, send_push?)
 *   audience: all | hosting | role_admin | role_member
 */

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__, 3));
require_once BASE_PATH . '/api/_session-bootstrap.php';
require_once BASE_PATH . '/vendor/autoload.php';

header('Content-Type: application/json; charset=utf-8');
header('Cache-Control: no-store');

require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
if (!\RzxLib\Core\Auth\Auth::check()) {
    http_response_code(401); echo json_encode(['success'=>false,'message'=>'auth required']); exit;
}
$user = \RzxLib\Core\Auth\Auth::user();
if (!in_array($user['role'] ?? '', ['admin','supervisor','owner'], true)) {
    http_response_code(403); echo json_encode(['success'=>false,'message'=>'forbidden']); exit;
}

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

$title = trim($_POST['title'] ?? '');
$body  = trim($_POST['body'] ?? '');
$link  = trim($_POST['link'] ?? '') ?: null;
$audience = trim($_POST['audience'] ?? 'all');
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
    echo json_encode(['success'=>true,'inserted'=>0,'pushed'=>0,'message'=>'no recipients']); exit;
}

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
    error_log('[community.broadcast] insert: ' . $e->getMessage());
    echo json_encode(['success'=>false,'message'=>'insert failed']); exit;
}

$pushed = 0;
if ($sendPush) {
    try {
        require_once BASE_PATH . '/rzxlib/Core/Notification/PushSender.php';
        $sender = new \RzxLib\Core\Notification\PushSender($pdo, $prefix);
        foreach (array_chunk($targetIds, 100) as $batch) {
            $r = $sender->sendToUsers($batch, [
                'title' => $title,
                'body'  => $body,
                'link'  => $link,
                'tag'   => 'admin-broadcast',
            ]);
            $pushed += (int)($r['sent'] ?? 0);
        }
    } catch (\Throwable $e) { error_log('[community.broadcast] push: ' . $e->getMessage()); }
}

echo json_encode(['success'=>true,'inserted'=>$inserted,'pushed'=>$pushed,'targets'=>count($targetIds)]);
