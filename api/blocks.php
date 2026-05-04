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

        // ─── 어드민: 신고 목록 조회 ───
        case 'admin_list_reports':
            if (!in_array($user['role'] ?? '', ['admin','supervisor','owner'], true)) {
                http_response_code(403); echo json_encode(['success'=>false,'message'=>'forbidden']); exit;
            }
            $statusFilter = trim($_GET['status'] ?? 'pending');
            $allowedStatus = ['pending','reviewed','dismissed','actioned','all'];
            if (!in_array($statusFilter, $allowedStatus, true)) $statusFilter = 'pending';
            $where = $statusFilter === 'all' ? '1=1' : 'r.status = ' . $pdo->quote($statusFilter);

            $st = $pdo->prepare("SELECT r.id, r.reporter_id, r.target_user_id, r.message_id, r.reason, r.detail,
                r.status, r.admin_note, r.reviewed_at, r.reviewed_by, r.created_at,
                rep.email AS reporter_email, rep.name AS reporter_name, rep.nick_name AS reporter_nick,
                tgt.email AS target_email, tgt.name AS target_name, tgt.nick_name AS target_nick,
                (SELECT COUNT(*) FROM {$prefix}message_reports r2 WHERE r2.target_user_id = r.target_user_id) AS target_total_reports,
                (SELECT COUNT(*) FROM {$prefix}message_blocks b WHERE b.blocked_id = r.target_user_id) AS target_total_blocks
                FROM {$prefix}message_reports r
                LEFT JOIN {$prefix}users rep ON rep.id = r.reporter_id
                LEFT JOIN {$prefix}users tgt ON tgt.id = r.target_user_id
                WHERE {$where}
                ORDER BY r.created_at DESC LIMIT 200");
            $st->execute();
            $rows = $st->fetchAll();
            foreach ($rows as &$r) {
                $repName = decrypt($r['reporter_name'] ?? '');
                $tgtName = decrypt($r['target_name'] ?? '');
                $r['reporter_display'] = $r['reporter_nick'] ?: ($repName ?: explode('@', $r['reporter_email'] ?? '')[0]);
                $r['target_display']   = $r['target_nick']   ?: ($tgtName ?: explode('@', $r['target_email'] ?? '')[0]);
                unset($r['reporter_name'], $r['target_name']);
            }
            echo json_encode(['success'=>true, 'reports'=>$rows]);
            break;

        // ─── 어드민: 신고 처리 ───
        case 'admin_resolve_report':
            if (!in_array($user['role'] ?? '', ['admin','supervisor','owner'], true)) {
                http_response_code(403); echo json_encode(['success'=>false,'message'=>'forbidden']); exit;
            }
            $reportId = (int)($_POST['report_id'] ?? 0);
            $newStatus = trim($_POST['status'] ?? '');
            $note = trim($_POST['admin_note'] ?? '');
            $action = trim($_POST['action_taken'] ?? ''); // pause_messages | suspend_user (옵션)
            if (!$reportId || !in_array($newStatus, ['reviewed','dismissed','actioned'], true)) {
                echo json_encode(['success'=>false,'message'=>'invalid input']); exit;
            }
            // 신고 대상 가져오기
            $rs = $pdo->prepare("SELECT target_user_id FROM {$prefix}message_reports WHERE id = ?");
            $rs->execute([$reportId]);
            $targetId = $rs->fetchColumn();
            if (!$targetId) { echo json_encode(['success'=>false,'message'=>'report not found']); exit; }

            $pdo->beginTransaction();
            try {
                $pdo->prepare("UPDATE {$prefix}message_reports
                    SET status = ?, admin_note = ?, reviewed_at = NOW(), reviewed_by = ? WHERE id = ?")
                    ->execute([$newStatus, $note ?: null, $userId, $reportId]);

                // 조치 (action_taken)
                if ($newStatus === 'actioned' && $action) {
                    if ($action === 'pause_messages') {
                        // 24시간 메시지 일시 차단
                        $pdo->prepare("UPDATE {$prefix}users SET messages_paused_until = DATE_ADD(NOW(), INTERVAL 24 HOUR) WHERE id = ?")
                            ->execute([$targetId]);
                    } elseif ($action === 'suspend_user') {
                        // 계정 비활성화
                        $pdo->prepare("UPDATE {$prefix}users SET is_active = 0 WHERE id = ?")
                            ->execute([$targetId]);
                    }
                }
                $pdo->commit();
            } catch (\Throwable $e) {
                $pdo->rollBack();
                error_log('[api/blocks] resolve_report: ' . $e->getMessage());
                echo json_encode(['success'=>false,'message'=>'server error']); exit;
            }
            echo json_encode(['success'=>true]);
            break;

        // ─── 어드민: 통계 ───
        case 'admin_stats':
            if (!in_array($user['role'] ?? '', ['admin','supervisor','owner'], true)) {
                http_response_code(403); echo json_encode(['success'=>false]); exit;
            }
            $st = $pdo->query("SELECT
                (SELECT COUNT(*) FROM {$prefix}message_reports WHERE status = 'pending') AS pending_reports,
                (SELECT COUNT(*) FROM {$prefix}message_reports) AS total_reports,
                (SELECT COUNT(*) FROM {$prefix}message_blocks) AS total_blocks,
                (SELECT COUNT(*) FROM {$prefix}messages WHERE sent_at > DATE_SUB(NOW(), INTERVAL 7 DAY)) AS messages_7d,
                (SELECT COUNT(DISTINCT sender_id) FROM {$prefix}messages WHERE sent_at > DATE_SUB(NOW(), INTERVAL 7 DAY)) AS active_senders_7d,
                (SELECT COUNT(*) FROM {$prefix}push_subscriptions) AS push_subscriptions");
            echo json_encode(['success'=>true, 'stats'=>$st->fetch()]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success'=>false,'message'=>'unknown action']);
    }
} catch (\Throwable $e) {
    error_log('[api/blocks] ' . $e->getMessage());
    echo json_encode(['success'=>false,'message'=>'server error']);
}
