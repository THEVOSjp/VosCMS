<?php
/**
 * RezlyX 게시판 - 파일 API
 * GET: download
 * POST: delete
 */

require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
use RzxLib\Core\Auth\Auth;

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$action = $_GET['action'] ?? $_POST['action'] ?? '';
$currentUser = Auth::check() ? Auth::user() : null;

// === DOWNLOAD ===
if ($action === 'download' && $_SERVER['REQUEST_METHOD'] === 'GET') {
    $fileId = (int)($_GET['id'] ?? 0);
    if (!$fileId) { http_response_code(404); echo 'File not found'; exit; }

    $file = $pdo->prepare("SELECT * FROM {$prefix}board_files WHERE id = ?");
    $file->execute([$fileId]);
    $file = $file->fetch(PDO::FETCH_ASSOC);
    if (!$file) { http_response_code(404); echo 'File not found'; exit; }

    $filePath = BASE_PATH . $file['file_path'];
    if (!file_exists($filePath)) { http_response_code(404); echo 'File missing'; exit; }

    // 다운로드 수 증가
    $pdo->prepare("UPDATE {$prefix}board_files SET download_count = download_count + 1 WHERE id = ?")->execute([$fileId]);

    // 파일 전송
    $mimeType = $file['mime_type'] ?: 'application/octet-stream';
    header('Content-Type: ' . $mimeType);
    header('Content-Disposition: attachment; filename="' . rawurlencode($file['original_name']) . '"');
    header('Content-Length: ' . filesize($filePath));
    readfile($filePath);
    exit;
}

// === UPLOAD IMAGE (에디터 본문 이미지 삽입) ===
if ($action === 'upload_image' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    if (empty($_FILES['file'])) {
        echo json_encode(['success' => false, 'message' => '파일이 없습니다.']);
        exit;
    }

    $file = $_FILES['file'];
    $allowedTypes = ['image/jpeg', 'image/png', 'image/gif', 'image/webp', 'image/svg+xml'];
    if (!in_array($file['type'], $allowedTypes)) {
        echo json_encode(['success' => false, 'message' => '이미지 파일만 업로드 가능합니다.']);
        exit;
    }

    // 업로드 디렉토리
    $uploadDir = '/storage/board/images/' . date('Y/m');
    $uploadPath = BASE_PATH . $uploadDir;
    if (!is_dir($uploadPath)) mkdir($uploadPath, 0755, true);

    // 파일명 생성
    $ext = pathinfo($file['name'], PATHINFO_EXTENSION) ?: 'jpg';
    $newName = 'img_' . time() . '_' . bin2hex(random_bytes(4)) . '.' . $ext;
    $destPath = $uploadPath . '/' . $newName;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        echo json_encode(['success' => false, 'message' => '파일 저장에 실패했습니다.']);
        exit;
    }

    $url = ($config['app_url'] ?? '') . $uploadDir . '/' . $newName;
    echo json_encode(['success' => true, 'url' => $url]);
    exit;
}

// === DELETE ===
if ($action === 'delete' && $_SERVER['REQUEST_METHOD'] === 'POST') {
    header('Content-Type: application/json; charset=utf-8');

    $fileId = (int)($_GET['id'] ?? $_POST['id'] ?? 0);
    if (!$fileId) { echo json_encode(['success' => false, 'message' => '파일 ID가 필요합니다.']); exit; }

    $file = $pdo->prepare("SELECT f.*, p.user_id AS post_user_id FROM {$prefix}board_files f JOIN {$prefix}board_posts p ON f.post_id = p.id WHERE f.id = ?");
    $file->execute([$fileId]);
    $file = $file->fetch(PDO::FETCH_ASSOC);
    if (!$file) { echo json_encode(['success' => false, 'message' => '파일을 찾을 수 없습니다.']); exit; }

    if (!$currentUser || ($currentUser['id'] != $file['post_user_id'] && ($currentUser['role'] ?? '') !== 'admin')) {
        echo json_encode(['success' => false, 'message' => '삭제 권한이 없습니다.']);
        exit;
    }

    // 물리 파일 삭제
    $filePath = BASE_PATH . $file['file_path'];
    if (file_exists($filePath)) unlink($filePath);

    // DB 삭제
    $pdo->prepare("DELETE FROM {$prefix}board_files WHERE id = ?")->execute([$fileId]);

    // file_count 갱신
    $fc = $pdo->prepare("SELECT COUNT(*) FROM {$prefix}board_files WHERE post_id = ?");
    $fc->execute([$file['post_id']]);
    $pdo->prepare("UPDATE {$prefix}board_posts SET file_count = ? WHERE id = ?")->execute([(int)$fc->fetchColumn(), $file['post_id']]);

    echo json_encode(['success' => true, 'message' => '파일이 삭제되었습니다.']);
    exit;
}

http_response_code(400);
echo 'Bad request';
