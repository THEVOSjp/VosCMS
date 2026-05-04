<?php
/**
 * VosCMS 마이페이지 알림 API (인증 필요)
 *
 * GET  ?action=unread_summary  → { success, unread, recent[5] }
 * POST action=mark_read&id=N
 * POST action=mark_all_read
 * POST action=delete&id=N
 */

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));

require_once BASE_PATH . '/vendor/autoload.php';

// .env 로드 (이미 index 통해 들어왔을 수도 있지만 직접 호출 대비)
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
$action = $method === 'POST' ? ($_POST['action'] ?? '') : ($_GET['action'] ?? 'unread_summary');

try {
    switch ($action) {
        case 'unread_summary':
            $cnt = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}notifications WHERE user_id = ? AND is_read = 0");
            $cnt->execute([$userId]);
            $unread = (int)$cnt->fetchColumn();

            $rec = $pdo->prepare("SELECT id, type, category, title, body, link, icon, is_read, created_at
                FROM {$prefix}notifications WHERE user_id = ?
                ORDER BY is_read ASC, created_at DESC LIMIT 5");
            $rec->execute([$userId]);
            echo json_encode(['success' => true, 'unread' => $unread, 'recent' => $rec->fetchAll()]);
            break;

        case 'mark_read':
            if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false]); exit; }
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) { echo json_encode(['success'=>false,'message'=>'id required']); exit; }
            $pdo->prepare("UPDATE {$prefix}notifications SET is_read = 1, read_at = NOW() WHERE id = ? AND user_id = ?")
                ->execute([$id, $userId]);
            echo json_encode(['success' => true]);
            break;

        case 'mark_all_read':
            if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false]); exit; }
            $pdo->prepare("UPDATE {$prefix}notifications SET is_read = 1, read_at = NOW() WHERE user_id = ? AND is_read = 0")
                ->execute([$userId]);
            echo json_encode(['success' => true]);
            break;

        case 'delete':
            if ($method !== 'POST') { http_response_code(405); echo json_encode(['success'=>false]); exit; }
            $id = (int)($_POST['id'] ?? 0);
            if ($id <= 0) { echo json_encode(['success'=>false,'message'=>'id required']); exit; }
            $pdo->prepare("DELETE FROM {$prefix}notifications WHERE id = ? AND user_id = ?")
                ->execute([$id, $userId]);
            echo json_encode(['success' => true]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'unknown action']);
    }
} catch (\Throwable $e) {
    error_log('[api/notifications] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'server error']);
}
