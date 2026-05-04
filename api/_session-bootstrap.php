<?php
/**
 * API 세션 부트스트랩 — index.php 와 동일한 세션 설정 적용.
 * /api/*.php 진입 시 session_start() 보다 앞서 require.
 *
 * 메인 페이지가 storage/sessions/ 에 저장하므로
 * API 도 동일 경로로 맞춰야 $_SESSION['user_id'] 를 읽을 수 있다.
 */

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__));

// .env 로드 (SESSION_LIFETIME 사용)
if (empty($_ENV['DB_HOST'])) {
    foreach (file(BASE_PATH . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) ?: [] as $line) {
        if (!str_contains($line, '=') || str_starts_with(trim($line), '#')) continue;
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v, " \t\"'");
    }
}

if (session_status() === PHP_SESSION_NONE) {
    $_sessionLifetime = ((int)($_ENV['SESSION_LIFETIME'] ?? 0) ?: 10080) * 60;
    ini_set('session.gc_maxlifetime', (string)$_sessionLifetime);

    $_sessionDir = BASE_PATH . '/storage/sessions';
    if (is_dir($_sessionDir)) {
        ini_set('session.save_path', $_sessionDir);
    }
    ini_set('session.gc_probability', '1');
    ini_set('session.gc_divisor', '100');

    session_set_cookie_params([
        'lifetime' => $_sessionLifetime,
        'path'     => '/',
        'secure'   => !empty($_SERVER['HTTPS']),
        'httponly' => true,
        'samesite' => 'Lax',
    ]);
    session_start();
}
