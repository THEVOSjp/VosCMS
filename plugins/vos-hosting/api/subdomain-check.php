<?php
/**
 * 서브도메인 가용성 확인 API
 *
 * GET /plugins/vos-hosting/api/subdomain-check.php?subdomain=thevos&zone=21ces.com
 *   → DB (reserved_subdomains) 우선 조회 → Cloudflare API fallback
 *
 * 응답: { success: true, available: true|false, fqdn, source: 'db'|'cloudflare', conflicts?: [...] }
 */
if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');

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

$subdomain = strtolower(trim($_GET['subdomain'] ?? ''));
$zone = strtolower(trim($_GET['zone'] ?? '21ces.com'));

// 입력 검증
if (!$subdomain || strlen($subdomain) < 2 || !preg_match('/^[a-z0-9]([a-z0-9-]*[a-z0-9])?$/', $subdomain)) {
    echo json_encode(['success' => false, 'message' => '올바른 서브도메인을 입력하세요 (영문, 숫자, 하이픈, 2자 이상).']);
    exit;
}
if (!preg_match('/^[a-z0-9.-]+\.[a-z]{2,}$/', $zone)) {
    echo json_encode(['success' => false, 'message' => '잘못된 zone 입니다.']);
    exit;
}

// DB 연결
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (\Throwable $e) {
    echo json_encode(['success' => false, 'message' => 'DB 연결 실패']);
    exit;
}

// Cloudflare API token 로드
$cfToken = $_ENV['CLOUDFLARE_API_TOKEN'] ?? '';

try {
    $cf = new \RzxLib\Core\Dns\CloudflareDns($cfToken);
    $result = $cf->checkSubdomainAvailability($zone, $subdomain, $pdo);
    $result['success'] = true;
    echo json_encode($result, JSON_UNESCAPED_UNICODE);
} catch (\Throwable $e) {
    error_log('[subdomain-check] error: ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => '확인 중 오류가 발생했습니다: ' . $e->getMessage()]);
}
