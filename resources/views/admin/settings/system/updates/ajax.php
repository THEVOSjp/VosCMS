<?php
/**
 * 업데이트 AJAX 핸들러
 * index.php를 통해 include되므로 BASE_PATH, $pdo, AdminAuth 등 이미 초기화된 상태
 */

header('Content-Type: application/json');

// 관리자 인증 확인 (index.php에서 이미 AdminAuth::check() 통과한 상태)
// 직접 접근 방지를 위한 이중 체크
if (!isset($pdo) || !defined('BASE_PATH')) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Direct access not allowed']);
    exit;
}

use RzxLib\Core\Updater\Updater;

$action = $_GET['action'] ?? $_POST['action'] ?? '';

try {
    // $pdo는 index.php에서 이미 초기화됨
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
