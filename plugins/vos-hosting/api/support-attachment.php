<?php
/**
 * 1:1 상담 첨부파일 API
 *
 * Actions:
 *   POST ?action=upload_pending   — multipart files[] → /uploads/support/_pending/{uuid}.{ext}
 *                                  returns: { success, attachments: [{uuid,name,size,mime,ext}, ...] }
 *   GET  ?action=download&msg=N&idx=I — 권한 검증 후 파일 스트리밍 (attachment)
 *   GET  ?action=inline&msg=N&idx=I   — 동일하나 inline (이미지 미리보기)
 */
if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__, 3));

if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') !== false) {
            [$k, $v] = explode('=', $line, 2);
            $_ENV[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
        }
    }
}

if (session_status() === PHP_SESSION_NONE) {
    $_sessionDir = BASE_PATH . '/storage/sessions';
    if (is_dir($_sessionDir)) ini_set('session.save_path', $_sessionDir);
    session_start();
}

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (\PDOException $e) {
    http_response_code(503);
    echo 'db error';
    exit;
}

$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    http_response_code(401);
    echo 'auth required';
    exit;
}

$action = $_GET['action'] ?? $_POST['action'] ?? '';

const SUPPORT_MAX_FILE_SIZE = 31457280; // 30 MB
const SUPPORT_MAX_FILES_PER_MSG = 3;
const SUPPORT_PENDING_TTL = 3600; // 1 hour
$SUPPORT_ALLOWED_EXTS = [
    'jpg','jpeg','png','gif','webp','svg',
    'pdf','doc','docx','xls','xlsx','ppt','pptx','csv','txt','md',
    'zip','rar','tar','gz','7z',
    'log','json','xml','sql',
];

// ============================================================
// upload_pending — 임시 폴더에 파일 저장 후 메타 반환
// ============================================================
if ($action === 'upload_pending') {
    header('Content-Type: application/json; charset=utf-8');
    if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
        echo json_encode(['success' => false, 'message' => 'POST only']); exit;
    }
    if (empty($_FILES['files'])) {
        echo json_encode(['success' => false, 'message' => 'no files']); exit;
    }
    $files = $_FILES['files'];

    // 단일 파일도 다중 형식으로 정규화
    if (!is_array($files['name'])) {
        $files = [
            'name' => [$files['name']],
            'type' => [$files['type']],
            'size' => [$files['size']],
            'tmp_name' => [$files['tmp_name']],
            'error' => [$files['error']],
        ];
    }

    $count = count($files['name']);
    if ($count > SUPPORT_MAX_FILES_PER_MSG) {
        echo json_encode(['success' => false, 'message' => 'too many files (max ' . SUPPORT_MAX_FILES_PER_MSG . ')']); exit;
    }

    $tempDir = BASE_PATH . '/uploads/support/_pending';
    if (!is_dir($tempDir)) {
        @mkdir($tempDir, 0775, true);
    }
    if (!is_writable($tempDir)) {
        echo json_encode(['success' => false, 'message' => 'pending dir not writable']); exit;
    }

    // 1시간 이상 된 pending 파일 정리 (lazy GC)
    foreach (glob($tempDir . '/*') ?: [] as $f) {
        if (is_file($f) && (time() - filemtime($f)) > SUPPORT_PENDING_TTL) @unlink($f);
    }

    $result = [];
    for ($i = 0; $i < $count; $i++) {
        if ($files['error'][$i] !== UPLOAD_ERR_OK) {
            echo json_encode(['success' => false, 'message' => 'upload error code ' . $files['error'][$i]]); exit;
        }
        if ((int)$files['size'][$i] > SUPPORT_MAX_FILE_SIZE) {
            echo json_encode(['success' => false, 'message' => 'file too large (max 30MB)']); exit;
        }
        $name = (string)$files['name'][$i];
        $ext = strtolower(pathinfo($name, PATHINFO_EXTENSION));
        if ($ext === '' || !in_array($ext, $SUPPORT_ALLOWED_EXTS, true)) {
            echo json_encode(['success' => false, 'message' => 'extension not allowed: ' . $ext]); exit;
        }

        // UUID 파일명 — 사용자 user_id 를 prefix 로 (다른 사용자 파일 충돌 방지 + 권한 추적)
        $uuid = bin2hex(random_bytes(16));
        $dst = $tempDir . '/' . $userId . '_' . $uuid . '.' . $ext;
        if (!move_uploaded_file($files['tmp_name'][$i], $dst)) {
            echo json_encode(['success' => false, 'message' => 'move failed']); exit;
        }
        @chmod($dst, 0644);

        $mime = $files['type'][$i];
        if (function_exists('mime_content_type')) {
            $detected = @mime_content_type($dst);
            if ($detected) $mime = $detected;
        }

        $result[] = [
            'uuid' => $uuid,
            'name' => $name,
            'size' => (int)$files['size'][$i],
            'mime' => $mime,
            'ext' => $ext,
        ];
    }
    echo json_encode(['success' => true, 'attachments' => $result]);
    exit;
}

// ============================================================
// download / inline — 메시지 첨부 파일 스트리밍
// ============================================================
if ($action === 'download' || $action === 'inline') {
    $msgId = (int)($_GET['msg'] ?? 0);
    $idx = (int)($_GET['idx'] ?? 0);
    if (!$msgId) { http_response_code(400); echo 'msg required'; exit; }

    // 메시지 + 티켓 조회
    $st = $pdo->prepare("SELECT m.id, m.ticket_id, m.attachments, t.user_id AS ticket_user_id
                         FROM {$prefix}support_messages m
                         JOIN {$prefix}support_tickets t ON m.ticket_id = t.id
                         WHERE m.id = ?");
    $st->execute([$msgId]);
    $msg = $st->fetch(PDO::FETCH_ASSOC);
    if (!$msg) { http_response_code(404); echo 'message not found'; exit; }

    // 권한: 티켓 소유자 또는 관리자
    $rSt = $pdo->prepare("SELECT role FROM {$prefix}users WHERE id = ?");
    $rSt->execute([$userId]);
    $isAdmin = in_array($rSt->fetchColumn(), ['admin','supervisor'], true);
    if (!$isAdmin && $msg['ticket_user_id'] !== $userId) {
        http_response_code(403); echo 'forbidden'; exit;
    }

    $atts = json_decode($msg['attachments'] ?? '[]', true) ?: [];
    if (!isset($atts[$idx])) { http_response_code(404); echo 'attachment index not found'; exit; }
    $att = $atts[$idx];

    $ticketId = (int)$msg['ticket_id'];
    $path = BASE_PATH . '/uploads/support/' . $ticketId . '/' . $att['uuid'] . '.' . $att['ext'];
    if (!is_file($path)) { http_response_code(410); echo 'file gone'; exit; }

    $disposition = ($action === 'inline') ? 'inline' : 'attachment';
    $mime = $att['mime'] ?? 'application/octet-stream';

    // 다운로드 모드에서도 PDF 는 inline 으로 (브라우저 미리보기)
    if ($action === 'download' && $mime === 'application/pdf') {
        $disposition = 'inline';
    }

    header('Content-Type: ' . $mime);
    header('Content-Length: ' . filesize($path));
    // RFC 5987 — UTF-8 파일명 (한글/일본어 지원)
    $safeAscii = preg_replace('/[^A-Za-z0-9._\-]/', '_', $att['name']);
    $utf8Encoded = rawurlencode($att['name']);
    header(sprintf('Content-Disposition: %s; filename="%s"; filename*=UTF-8\'\'%s',
        $disposition, $safeAscii, $utf8Encoded));
    header('X-Content-Type-Options: nosniff');
    header('Cache-Control: private, max-age=300');

    readfile($path);
    exit;
}

http_response_code(400);
echo 'unknown action';
