<?php
/**
 * 업데이트 AJAX 핸들러
 * 관리자 세션 인증 사용
 */

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

header('Content-Type: application/json');

// BASE_PATH 정의
if (!defined('BASE_PATH')) {
    define('BASE_PATH', dirname(__DIR__, 6));
}

// 환경 변수 로드
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile) && empty($_ENV['DB_HOST'])) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (str_contains($line, '=')) {
            [$key, $value] = explode('=', $line, 2);
            $key = trim($key);
            $value = trim($value, " \t\n\r\0\x0B\"'");
            $_ENV[$key] = $value;
            putenv("{$key}={$value}");
        }
    }
}

// Autoload
require_once BASE_PATH . '/vendor/autoload.php';

// 관리자 인증 확인 (세션 기반)
$adminUser = $_SESSION['admin_user'] ?? $_SESSION['admin'] ?? null;
if (!$adminUser) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized - Please login']);
    exit;
}

// CSRF 검증 (선택적 - 보안 강화 시 활성화)
// $csrfToken = $_POST['_token'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? '';
// if (empty($csrfToken) || !isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
//     http_response_code(403);
//     echo json_encode(['success' => false, 'error' => 'Invalid CSRF token']);
//     exit;
// }

use RzxLib\Core\Updater\Updater;
use RzxLib\Core\Database\Connection;

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    $pdo = Connection::getInstance()->getPdo();
    $updater = new Updater($pdo, BASE_PATH);

    switch ($action) {
        case 'check':
            $result = $updater->checkForUpdates();
            echo json_encode(['success' => true, 'data' => $result]);
            break;

        case 'perform':
            $version = $_POST['version'] ?? null;
            $result = $updater->performUpdate($version);
            echo json_encode(['success' => $result['success'], 'data' => $result]);
            break;

        case 'rollback':
            $backupPath = $_POST['backup_path'] ?? null;
            $result = $updater->rollback($backupPath);
            echo json_encode(['success' => $result['success'], 'data' => $result]);
            break;

        case 'backups':
            $backups = $updater->getBackups();
            echo json_encode(['success' => true, 'data' => $backups]);
            break;

        case 'requirements':
            $requirements = $updater->checkRequirements();
            $allMet = !in_array(false, $requirements, true);
            echo json_encode([
                'success' => true,
                'data' => ['requirements' => $requirements, 'all_met' => $allMet]
            ]);
            break;

        case 'version':
            $versionInfo = $updater->getCurrentVersion();
            unset($versionInfo['github']); // GitHub 정보 숨김
            echo json_encode(['success' => true, 'data' => $versionInfo]);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
}
