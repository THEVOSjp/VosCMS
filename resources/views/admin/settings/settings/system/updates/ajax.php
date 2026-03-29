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

// 글로벌 $pdo를 새 버퍼링된 연결로 교체 (index.php의 unbuffered 쿼리 충돌 완전 해결)
$pdo = null; // 기존 연결 해제

/**
 * 별도 PDO 연결 생성 (index.php의 unbuffered 쿼리 충돌 방지)
 */
function createFreshPdo(): \PDO
{
    $envPath = BASE_PATH . '/.env';
    $envVars = [];
    if (file_exists($envPath)) {
        foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (str_starts_with(trim($line), '#')) continue;
            if (strpos($line, '=') !== false) {
                [$k, $v] = explode('=', $line, 2);
                $envVars[trim($k)] = trim($v, " \"'");
            }
        }
    }
    return new \PDO(
        'mysql:host=' . ($envVars['DB_HOST'] ?? '127.0.0.1') . ';port=' . ($envVars['DB_PORT'] ?? '3306') . ';dbname=' . ($envVars['DB_DATABASE'] ?? 'rezlyx') . ';charset=utf8mb4',
        $envVars['DB_USERNAME'] ?? 'root',
        $envVars['DB_PASSWORD'] ?? '',
        [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true]
    );
}

// diagnose는 별도 PDO 연결로 독립 실행
if ($action === 'diagnose') {
    try {
        $diag = [];
        // 1. version.json
        $vf = BASE_PATH . '/version.json';
        $vi = file_exists($vf) ? json_decode(file_get_contents($vf), true) : null;
        $diag['version_json'] = $vi ? 'OK (v' . ($vi['version'] ?? '?') . ')' : 'MISSING';
        $diag['github_owner'] = $vi['github']['owner'] ?? 'NOT SET';
        $diag['github_repo'] = $vi['github']['repo'] ?? 'NOT SET';

        // 2. DB 토큰
        $diagPdo = createFreshPdo();
        $stmt = $diagPdo->prepare("SELECT `value` FROM rzx_settings WHERE `key` = 'github_token'");
        $stmt->execute();
        $dbToken = $stmt->fetchColumn();
        $stmt->closeCursor();
        $diagPdo = null;
        $diag['db_token'] = $dbToken ? 'EXISTS (' . strlen($dbToken) . ' chars, enc:' . (str_starts_with($dbToken, 'enc:') ? 'yes' : 'no') . ')' : 'MISSING';

        // 3. APP_KEY
        $appKey = $_ENV['APP_KEY'] ?? $_SERVER['APP_KEY'] ?? getenv('APP_KEY') ?: '';
        $diag['app_key'] = $appKey ? 'EXISTS (' . strlen($appKey) . ' chars)' : 'MISSING';

        // 4. 토큰 복호화 테스트
        if ($dbToken) {
            try {
                $decrypted = \RzxLib\Core\Helpers\Encryption::decrypt($dbToken);
                $isSame = ($decrypted === $dbToken);
                $diag['token_decrypt'] = $isSame ? 'FAILED (returned as-is)' : 'OK (decrypted, ' . strlen($decrypted) . ' chars)';
            } catch (\Throwable $e) {
                $diag['token_decrypt'] = 'ERROR: ' . $e->getMessage();
            }
        }

        // 5. GitHub API 테스트 (no auth)
        $owner = $vi['github']['owner'] ?? '';
        $repo = $vi['github']['repo'] ?? '';
        $ch = curl_init("https://api.github.com/repos/{$owner}/{$repo}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true, CURLOPT_USERAGENT => 'RezlyX-Updater/1.0',
        ]);
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlErr = curl_error($ch);
        curl_close($ch);
        $diag['github_api_no_auth'] = $curlErr ? "cURL Error: {$curlErr}" : "HTTP {$httpCode}";

        // 6. GitHub API (with token)
        if ($dbToken) {
            $token = \RzxLib\Core\Helpers\Encryption::decrypt($dbToken);
            $ch = curl_init("https://api.github.com/repos/{$owner}/{$repo}/releases/latest");
            curl_setopt_array($ch, [
                CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
                CURLOPT_SSL_VERIFYPEER => true, CURLOPT_USERAGENT => 'RezlyX-Updater/1.0',
                CURLOPT_HTTPHEADER => ['Accept: application/vnd.github.v3+json', 'Authorization: Bearer ' . $token],
            ]);
            $resp2 = curl_exec($ch);
            $httpCode2 = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlErr2 = curl_error($ch);
            curl_close($ch);
            $body2 = json_decode($resp2, true);
            $msg2 = $curlErr2 ? "cURL Error: {$curlErr2}" : "HTTP {$httpCode2}";
            if (!$curlErr2 && $httpCode2 == 200) $msg2 .= ' (tag: ' . ($body2['tag_name'] ?? '?') . ')';
            elseif (!$curlErr2 && isset($body2['message'])) $msg2 .= ' - ' . $body2['message'];
            $diag['github_api_with_auth'] = $msg2;
        }

        // 7. curl info
        $diag['curl_version'] = curl_version()['version'] ?? 'unknown';
        $diag['ssl_version'] = curl_version()['ssl_version'] ?? 'unknown';

        echo json_encode(['success' => true, 'data' => $diag], JSON_PRETTY_PRINT);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// GitHub 토큰 저장 (현재 APP_KEY로 암호화)
if ($action === 'save_token') {
    try {
        $token = trim($_POST['token'] ?? '');
        if ($token === '') {
            echo json_encode(['success' => false, 'error' => 'Token is required']);
            exit;
        }

        // GitHub API로 토큰 유효성 검증
        $vi = file_exists(BASE_PATH . '/version.json') ? json_decode(file_get_contents(BASE_PATH . '/version.json'), true) : [];
        $owner = $vi['github']['owner'] ?? '';
        $repo = $vi['github']['repo'] ?? '';
        $ch = curl_init("https://api.github.com/repos/{$owner}/{$repo}");
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true, CURLOPT_USERAGENT => 'RezlyX-Updater/1.0',
            CURLOPT_HTTPHEADER => ['Accept: application/vnd.github.v3+json', 'Authorization: Bearer ' . $token],
        ]);
        $resp = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200) {
            $body = json_decode($resp, true);
            echo json_encode(['success' => false, 'error' => 'Invalid token - GitHub API HTTP ' . $httpCode . ': ' . ($body['message'] ?? 'Unknown error')]);
            exit;
        }

        // 암호화 후 DB 저장
        $encrypted = \RzxLib\Core\Helpers\Encryption::encrypt($token);
        $savePdo = createFreshPdo();
        $stmt = $savePdo->prepare("INSERT INTO rzx_settings (`key`, `value`) VALUES ('github_token', :val) ON DUPLICATE KEY UPDATE `value` = :val2");
        $stmt->execute([':val' => $encrypted, ':val2' => $encrypted]);
        $savePdo = null;

        echo json_encode(['success' => true, 'message' => 'Token saved and verified successfully']);
    } catch (Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

try {
    // 별도 PDO로 Updater 생성 (unbuffered 쿼리 충돌 방지)
    $updater = new Updater(createFreshPdo(), BASE_PATH);

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

        case 'patch':
            $version = $_POST['version'] ?? null;
            $result = $updater->performPatchUpdate($version);
            echo json_encode(['success' => $result['success'], 'data' => $result]);
            break;

        case 'compare':
            $version = $_POST['version'] ?? $_GET['version'] ?? null;
            $result = $updater->getChangedFiles($version);
            echo json_encode(['success' => $result['success'] ?? false, 'data' => $result]);
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
