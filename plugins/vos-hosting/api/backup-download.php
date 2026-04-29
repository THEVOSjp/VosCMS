<?php
/**
 * 사이트 백업 다운로드 (HMAC 서명 검증).
 *
 * GET /plugins/vos-hosting/api/backup-download.php?o=<order>&f=<filename>&e=<expires>&u=<user_id>&s=<sig>
 *
 * service-manage.php?action=request_backup 가 발급한 서명 URL 만 유효.
 * 만료(10분), 소유자 확인, 파일명 sanitize.
 */

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__, 3));

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

$order = $_GET['o'] ?? '';
$filename = $_GET['f'] ?? '';
$expires = (int)($_GET['e'] ?? 0);
$userId = (int)($_GET['u'] ?? 0);
$sig = $_GET['s'] ?? '';

if (!$order || !$filename || !$expires || !$userId || !$sig) {
    http_response_code(400);
    exit('Bad Request');
}

// 만료 검증
if (time() > $expires) {
    http_response_code(410);
    exit('Link expired (10 minutes max)');
}

// 파일명 sanitize — 경로 escape 차단
if (str_contains($filename, '/') || str_contains($filename, '..') || !preg_match('/^[A-Za-z0-9._-]+\.zip$/', $filename)) {
    http_response_code(400);
    exit('Invalid filename');
}
if (!preg_match('/^[A-Za-z0-9_-]+$/', $order)) {
    http_response_code(400);
    exit('Invalid order');
}

// HMAC 검증
$secret = $_ENV['APP_KEY'] ?? 'voscms-default-secret';
$expected = hash_hmac('sha256', "{$order}|{$filename}|{$expires}|{$userId}", $secret);
if (!hash_equals($expected, $sig)) {
    http_response_code(403);
    exit('Invalid signature');
}

// 파일 존재 확인
$filePath = "/var/www/customers/{$order}/backups/{$filename}";
if (!file_exists($filePath)) {
    http_response_code(404);
    exit('File not found');
}

// 다운로드 헤더 + 파일 스트림
$size = filesize($filePath);
header('Content-Type: application/zip');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Content-Length: ' . $size);
header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0');
header('Pragma: no-cache');
header('X-Robots-Tag: noindex, nofollow');

while (ob_get_level()) ob_end_clean();
readfile($filePath);
exit;
