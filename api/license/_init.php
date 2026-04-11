<?php
/**
 * VosCMS License Server - API 초기화
 * 모든 라이선스 API 엔드포인트에서 공통으로 사용
 */

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: POST, GET, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization');

// CORS preflight
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(204);
    exit;
}

// 도메인 해시 비밀키 (ionCube로 보호됨)
define('LICENSE_HASH_SECRET', 'VosCMS_2026_LicenseServer_!@#SecretKey');

// DB 연결
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'server_error', 'message' => 'Database unavailable']);
    exit;
}

/**
 * JSON 입력 파싱
 */
function getInput(): array
{
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    return is_array($json) ? $json : $_POST;
}

/**
 * 도메인 정규화
 */
function normalizeDomain(string $domain): string
{
    $domain = strtolower(trim($domain));
    $domain = preg_replace('#^https?://#', '', $domain);
    $domain = preg_replace('#^www\.#', '', $domain);
    $domain = preg_replace('#:\d+$#', '', $domain);
    return rtrim($domain, '/');
}

/**
 * 도메인 해시 생성
 */
function domainHash(string $domain): string
{
    return hash('sha256', normalizeDomain($domain) . LICENSE_HASH_SECRET);
}

/**
 * 라이선스 키 형식 검증
 */
function isValidKeyFormat(string $key): bool
{
    return (bool) preg_match('/^RZX-[A-Z2-9]{4}-[A-Z2-9]{4}-[A-Z2-9]{4}$/', $key);
}

/**
 * 로그 기록
 */
function logAction(PDO $pdo, ?int $licenseId, string $action, ?string $domain, array $details = []): void
{
    try {
        $pdo->prepare(
            "INSERT INTO vcs_license_logs (license_id, action, domain, ip_address, details, created_at)
             VALUES (?, ?, ?, ?, ?, NOW())"
        )->execute([
            $licenseId,
            $action,
            $domain,
            $_SERVER['REMOTE_ADDR'] ?? null,
            !empty($details) ? json_encode($details, JSON_UNESCAPED_UNICODE) : null,
        ]);
    } catch (PDOException $e) {
        // 로그 실패는 무시
    }
}

/**
 * JSON 응답
 */
function respond(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}
