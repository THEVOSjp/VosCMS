<?php
/**
 * RezlyX 업데이트 독립 API 엔드포인트
 * index.php를 우회하여 PDO unbuffered query 충돌을 완전 회피
 *
 * 직접 호출: /rezlyx/update-api.php?action=check
 */

header('Content-Type: application/json');

// PHP 에러를 HTML 대신 JSON으로 반환
set_error_handler(function ($severity, $message, $file, $line) {
    throw new \ErrorException($message, 0, $severity, $file, $line);
});
set_exception_handler(function ($e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    exit;
});
ini_set('display_errors', '0');

// 세션 시작
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 관리자 인증 확인 (세션 기반)
if (empty($_SESSION['admin_id'])) {
    http_response_code(401);
    echo json_encode(['success' => false, 'error' => 'Unauthorized']);
    exit;
}

// 기본 경로 설정
define('BASE_PATH', __DIR__);

// Autoloader
require BASE_PATH . '/vendor/autoload.php';

// .env 로드
$dotenv = Dotenv\Dotenv::createImmutable(BASE_PATH);
$dotenv->load();

// 번역 시스템 초기화
require_once BASE_PATH . '/rzxlib/Core/I18n/Translator.php';
\RzxLib\Core\I18n\Translator::init(BASE_PATH . '/resources/lang');

// 액션 파라미터
$action = $_GET['action'] ?? $_POST['action'] ?? '';

/**
 * 별도 PDO 연결 생성 (buffered query 보장)
 */
function createPdo(): \PDO
{
    $pdo = new \PDO(
        'mysql:host=' . ($_ENV['DB_HOST'] ?? '127.0.0.1')
        . ';port=' . ($_ENV['DB_PORT'] ?? '3306')
        . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx')
        . ';charset=utf8mb4',
        $_ENV['DB_USERNAME'] ?? 'root',
        $_ENV['DB_PASSWORD'] ?? '',
        [
            \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
            \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ]
    );
    // 일부 서버에서 생성자 옵션이 무시되므로 setAttribute로 이중 설정
    $pdo->setAttribute(\PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    return $pdo;
}

// ─── diagnose ───
if ($action === 'diagnose') {
    try {
        $diag = [];

        // 1. version.json
        $vf = BASE_PATH . '/version.json';
        $vi = file_exists($vf) ? json_decode(file_get_contents($vf), true) : null;
        $diag['version_json'] = $vi ? 'OK (v' . ($vi['version'] ?? '?') . ')' : 'MISSING';
        $diag['github_owner'] = $vi['github']['owner'] ?? 'NOT SET';
        $diag['github_repo'] = $vi['github']['repo'] ?? 'NOT SET';
        $diag['endpoint'] = 'standalone (update-api.php)';

        // 2. DB 토큰
        $dpdo = createPdo();
        $stmt = $dpdo->prepare("SELECT `value` FROM rzx_settings WHERE `key` = 'github_token'");
        $stmt->execute();
        $dbToken = $stmt->fetchColumn();
        $stmt->closeCursor();
        $dpdo = null;
        $diag['db_token'] = $dbToken
            ? 'EXISTS (' . strlen($dbToken) . ' chars, enc:' . (str_starts_with($dbToken, 'enc:') ? 'yes' : 'no') . ')'
            : 'MISSING';

        // 3. APP_KEY
        $appKey = $_ENV['APP_KEY'] ?? '';
        $diag['app_key'] = $appKey ? 'EXISTS (' . strlen($appKey) . ' chars)' : 'MISSING';

        // 4. 토큰 복호화 테스트
        if ($dbToken) {
            try {
                $decrypted = \RzxLib\Core\Helpers\Encryption::decrypt($dbToken);
                $isSame = ($decrypted === $dbToken);
                $diag['token_decrypt'] = $isSame
                    ? 'FAILED (returned as-is)'
                    : 'OK (decrypted, ' . strlen($decrypted) . ' chars)';
            } catch (\Throwable $e) {
                $diag['token_decrypt'] = 'ERROR: ' . $e->getMessage();
            }
        }

        // 5. GitHub API (no auth)
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
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ─── save_token ───
if ($action === 'save_token') {
    try {
        $token = trim($_POST['token'] ?? '');
        if ($token === '') {
            echo json_encode(['success' => false, 'error' => 'Token is required']);
            exit;
        }

        $vi = file_exists(BASE_PATH . '/version.json')
            ? json_decode(file_get_contents(BASE_PATH . '/version.json'), true) : [];
        $owner = $vi['github']['owner'] ?? '';
        $repo = $vi['github']['repo'] ?? '';

        // GitHub API 검증
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
            echo json_encode(['success' => false, 'error' => 'Invalid token - HTTP ' . $httpCode . ': ' . ($body['message'] ?? 'Unknown')]);
            exit;
        }

        $encrypted = \RzxLib\Core\Helpers\Encryption::encrypt($token);
        $spdo = createPdo();
        $stmt = $spdo->prepare("INSERT INTO rzx_settings (`key`, `value`) VALUES ('github_token', :val) ON DUPLICATE KEY UPDATE `value` = :val2");
        $stmt->execute([':val' => $encrypted, ':val2' => $encrypted]);
        $spdo = null;

        echo json_encode(['success' => true, 'message' => 'Token saved and verified successfully']);
    } catch (\Throwable $e) {
        echo json_encode(['success' => false, 'error' => $e->getMessage()]);
    }
    exit;
}

// ─── Updater 기반 액션 ───
// 중요: 업데이트 시 파일이 덮어써지기 전에 모든 클래스를 메모리에 미리 로드
require_once BASE_PATH . '/rzxlib/Core/Updater/Backup.php';
require_once BASE_PATH . '/rzxlib/Core/Updater/GitHubClient.php';
require_once BASE_PATH . '/rzxlib/Core/Updater/DatabaseMigrator.php';
require_once BASE_PATH . '/rzxlib/Core/Updater/UpdateChecker.php';
require_once BASE_PATH . '/rzxlib/Core/Updater/Updater.php';
require_once BASE_PATH . '/rzxlib/Core/Helpers/Encryption.php';

use RzxLib\Core\Updater\Updater;

/**
 * 태그 기반 최신 버전 확인 (Release가 없을 때 폴백)
 */
function checkLatestTag(\PDO $pdo): ?array
{
    $vf = BASE_PATH . '/version.json';
    $vi = file_exists($vf) ? json_decode(file_get_contents($vf), true) : null;
    if (!$vi) return null;

    $currentVersion = $vi['version'] ?? '0.0.0';
    $owner = $vi['github']['owner'] ?? '';
    $repo = $vi['github']['repo'] ?? '';
    if (!$owner || !$repo) return null;

    // 토큰
    $token = null;
    try {
        $stmt = $pdo->prepare("SELECT `value` FROM rzx_settings WHERE `key` = 'github_token'");
        $stmt->execute();
        $enc = $stmt->fetchColumn();
        $stmt->closeCursor();
        if ($enc) $token = \RzxLib\Core\Helpers\Encryption::decrypt($enc);
    } catch (\Throwable $e) {}

    $headers = ['Accept: application/vnd.github.v3+json'];
    if ($token) $headers[] = "Authorization: Bearer {$token}";

    $ch = curl_init("https://api.github.com/repos/{$owner}/{$repo}/tags?per_page=10");
    curl_setopt_array($ch, [
        CURLOPT_RETURNTRANSFER => true, CURLOPT_FOLLOWLOCATION => true,
        CURLOPT_TIMEOUT => 10, CURLOPT_SSL_VERIFYPEER => true,
        CURLOPT_USERAGENT => 'RezlyX-UpdateChecker/1.0', CURLOPT_HTTPHEADER => $headers,
    ]);
    $resp = curl_exec($ch);
    $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    if ($resp === false || $code !== 200) return null;
    $tags = json_decode($resp, true);
    if (!is_array($tags) || empty($tags)) return null;

    $latestTag = null;
    $latestVer = '0.0.0';
    foreach ($tags as $t) {
        $name = $t['name'] ?? '';
        if (!preg_match('/^v?\d+\.\d+\.\d+/', $name)) continue;
        $ver = ltrim($name, 'v');
        if (version_compare($ver, $latestVer, '>')) {
            $latestVer = $ver;
            $latestTag = $name;
        }
    }
    if (!$latestTag) return null;

    return [
        'has_update' => version_compare($latestVer, $currentVersion, '>'),
        'current_version' => $currentVersion,
        'latest_version' => $latestVer,
        'release_name' => $latestTag,
        'release_notes' => '',
        'published_at' => '',
        'download_url' => "https://api.github.com/repos/{$owner}/{$repo}/zipball/{$latestTag}",
    ];
}

// 파일 기반 디버그 로그
function dbg(string $msg): void {
    @file_put_contents(BASE_PATH . '/storage/logs/update-debug.log',
        '[' . date('H:i:s') . '] ' . $msg . "\n", FILE_APPEND);
}

try {
    dbg("=== START action={$action} ===");
    $pdo = createPdo();
    dbg("step1: PDO created, classes pre-loaded");

    // PDO 연결 상태 테스트
    $testStmt = $pdo->prepare("SELECT 1");
    $testStmt->execute();
    $testStmt->fetchAll();
    $testStmt->closeCursor();
    dbg("step2: PDO test OK");

    $updater = new Updater($pdo, BASE_PATH);
    dbg("step3: Updater created");

    switch ($action) {
        case 'check':
            $result = $updater->checkForUpdates();
            // 항상 태그도 확인하여 더 높은 버전이 있으면 사용
            $tagResult = checkLatestTag($pdo);
            if ($tagResult) {
                $releaseVer = $result['latest_version'] ?? '0.0.0';
                $tagVer = $tagResult['latest_version'] ?? '0.0.0';
                if (version_compare($tagVer, $releaseVer, '>')) {
                    $result = $tagResult;
                }
            }
            echo json_encode(['success' => true, 'data' => $result]);
            break;

        case 'perform':
            set_time_limit(300);
            $version = $_POST['version'] ?? null;
            $result = $updater->performUpdate($version);
            echo json_encode(['success' => $result['success'], 'data' => $result]);
            break;

        case 'patch':
            set_time_limit(300);
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

        case 'delete_backup':
            $backupPath = $_POST['backup_path'] ?? null;
            if (!$backupPath) {
                echo json_encode(['success' => false, 'error' => 'Backup path is required']);
                break;
            }
            $backupObj = new \RzxLib\Core\Updater\Backup(BASE_PATH);
            $deleted = $backupObj->deleteBackup($backupPath);
            echo json_encode(['success' => $deleted, 'message' => $deleted ? \__('system.updates.backup_deleted') : \__('system.updates.backup_delete_failed')]);
            break;

        case 'requirements':
            $requirements = $updater->checkRequirements();
            $allMet = !in_array(false, $requirements, true);
            echo json_encode([
                'success' => true,
                'data' => ['requirements' => $requirements, 'all_met' => $allMet],
            ]);
            break;

        case 'version':
            $versionInfo = $updater->getCurrentVersion();
            unset($versionInfo['github']);
            echo json_encode(['success' => true, 'data' => $versionInfo]);
            break;

        case 'run_migrations':
            // 업데이트 후 별도 요청으로 마이그레이션 실행
            // (업데이트 중에는 이전 코드가 메모리에 로드되어 새 마이그레이션 로직 미실행)
            dbg("run_migrations: start");
            try {
                $dsn = 'mysql:host=' . ($_ENV['DB_HOST'] ?? '127.0.0.1')
                     . ';port=' . ($_ENV['DB_PORT'] ?? '3306')
                     . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx')
                     . ';charset=utf8mb4';
                $migPdo = new \PDO($dsn, $_ENV['DB_USERNAME'] ?? 'root', $_ENV['DB_PASSWORD'] ?? '', [
                    \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
                    \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
                ]);
                $migrator = new \RzxLib\Core\Updater\DatabaseMigrator($migPdo, BASE_PATH);
                $result = $migrator->runMigrations();
                dbg("run_migrations: result=" . json_encode($result));
                $migPdo = null;
                echo json_encode(['success' => $result['success'] ?? true, 'data' => $result]);
            } catch (\Throwable $e) {
                dbg("run_migrations: ERROR " . $e->getMessage());
                echo json_encode(['success' => false, 'error' => $e->getMessage()]);
            }
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
    }

} catch (\Throwable $e) {
    http_response_code(500);
    dbg("ERROR: {$e->getMessage()} in " . basename($e->getFile()) . ":{$e->getLine()}");
    dbg("TRACE: " . $e->getTraceAsString());
    echo json_encode(['success' => false, 'error' => $e->getMessage(), 'file' => basename($e->getFile()), 'line' => $e->getLine()]);
}
