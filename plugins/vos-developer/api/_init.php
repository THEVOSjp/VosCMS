<?php
/**
 * VosCMS Developer API - 초기화
 */

header('Content-Type: application/json; charset=utf-8');

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database unavailable']);
    exit;
}

function getInput(): array
{
    $raw = file_get_contents('php://input');
    $json = json_decode($raw, true);
    return is_array($json) ? $json : $_POST;
}

function respond(array $data, int $status = 200): never
{
    http_response_code($status);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

/**
 * 현재 로그인된 개발자 확인
 */
function getAuthDeveloper(PDO $pdo): ?array
{
    if (session_status() !== PHP_SESSION_ACTIVE) session_start();
    $devId = $_SESSION['developer_id'] ?? null;
    if (!$devId) return null;

    $stmt = $pdo->prepare("SELECT * FROM vcs_developers WHERE id = ? AND status = 'active'");
    $stmt->execute([$devId]);
    return $stmt->fetch() ?: null;
}
