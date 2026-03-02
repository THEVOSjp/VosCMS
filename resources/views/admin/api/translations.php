<?php
/**
 * RezlyX Admin - Translations API
 * 다국어 번역 데이터 저장/조회 API
 */

// 에러 리포팅 설정
error_reporting(E_ALL);
ini_set('display_errors', '0');

// JSON 응답 헤더
header('Content-Type: application/json; charset=utf-8');

// Bootstrap 로드
$basePath = dirname(__DIR__, 4);
if (!defined('BASE_PATH')) {
    define('BASE_PATH', $basePath);
}

// .env 로드
if (file_exists(BASE_PATH . '/.env')) {
    $envContent = file_get_contents(BASE_PATH . '/.env');
    $lines = explode("\n", $envContent);
    foreach ($lines as $line) {
        $line = trim($line);
        if (empty($line) || strpos($line, '#') === 0) continue;
        if (strpos($line, '=') !== false) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, "\"' ");
        }
    }
}

// DB 연결
try {
    $pdo = new PDO(
        'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx'),
        $_ENV['DB_USERNAME'] ?? 'root',
        $_ENV['DB_PASSWORD'] ?? '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
        ]
    );
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['success' => false, 'error' => 'Database connection failed']);
    exit;
}

// 테이블이 없으면 자동 생성
try {
    $tableCheck = $pdo->query("SHOW TABLES LIKE 'rzx_translations'");
    if ($tableCheck->rowCount() === 0) {
        $pdo->exec("
            CREATE TABLE IF NOT EXISTS `rzx_translations` (
                `id` INT AUTO_INCREMENT NOT NULL,
                `lang_key` VARCHAR(255) NOT NULL COMMENT '번역 키',
                `locale` VARCHAR(10) NOT NULL COMMENT '언어 코드 (ko, en, ja 등)',
                `content` TEXT NOT NULL COMMENT '번역된 내용',
                `created_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
                `updated_at` TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
                PRIMARY KEY (`id`),
                UNIQUE KEY `uk_key_locale` (`lang_key`, `locale`),
                KEY `idx_lang_key` (`lang_key`)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci COMMENT='다국어 번역 데이터'
        ");
    }
} catch (PDOException $e) {
    // 테이블 생성 실패해도 계속 진행 (이미 존재할 수 있음)
}

// 요청 메서드 확인
$method = $_SERVER['REQUEST_METHOD'];
$action = $_GET['action'] ?? '';

/**
 * 번역 조회
 */
function getTranslations(PDO $pdo, string $langKey): array
{
    $stmt = $pdo->prepare("SELECT locale, content FROM rzx_translations WHERE lang_key = ?");
    $stmt->execute([$langKey]);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $translations = [];
    foreach ($results as $row) {
        $translations[$row['locale']] = $row['content'];
    }

    return $translations;
}

/**
 * 번역 저장
 */
function saveTranslations(PDO $pdo, string $langKey, array $translations): bool
{
    try {
        $pdo->beginTransaction();

        $stmt = $pdo->prepare("
            INSERT INTO rzx_translations (lang_key, locale, content)
            VALUES (?, ?, ?)
            ON DUPLICATE KEY UPDATE content = VALUES(content), updated_at = CURRENT_TIMESTAMP
        ");

        foreach ($translations as $locale => $content) {
            if (!empty($content)) {
                $stmt->execute([$langKey, $locale, $content]);
            }
        }

        $pdo->commit();
        return true;
    } catch (Exception $e) {
        $pdo->rollBack();
        return false;
    }
}

/**
 * 특정 언어의 번역 조회
 */
function getTranslation(PDO $pdo, string $langKey, string $locale): ?string
{
    $stmt = $pdo->prepare("SELECT content FROM rzx_translations WHERE lang_key = ? AND locale = ?");
    $stmt->execute([$langKey, $locale]);
    $result = $stmt->fetch(PDO::FETCH_ASSOC);

    return $result ? $result['content'] : null;
}

// API 라우팅
switch ($method) {
    case 'GET':
        if ($action === 'get') {
            $langKey = $_GET['key'] ?? '';
            $locale = $_GET['locale'] ?? '';

            if (empty($langKey)) {
                echo json_encode(['success' => false, 'error' => 'Missing lang_key parameter']);
                exit;
            }

            if (!empty($locale)) {
                // 특정 언어 번역 조회
                $content = getTranslation($pdo, $langKey, $locale);
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'key' => $langKey,
                        'locale' => $locale,
                        'content' => $content
                    ]
                ]);
            } else {
                // 모든 언어 번역 조회
                $translations = getTranslations($pdo, $langKey);
                echo json_encode([
                    'success' => true,
                    'data' => [
                        'key' => $langKey,
                        'translations' => $translations
                    ]
                ]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
        }
        break;

    case 'POST':
        // POST 데이터 읽기
        $input = json_decode(file_get_contents('php://input'), true);

        if ($action === 'save') {
            $langKey = $input['key'] ?? '';
            $translations = $input['translations'] ?? [];

            if (empty($langKey)) {
                echo json_encode(['success' => false, 'error' => 'Missing lang_key parameter']);
                exit;
            }

            if (empty($translations)) {
                echo json_encode(['success' => false, 'error' => 'No translations provided']);
                exit;
            }

            // 번역 저장
            $success = saveTranslations($pdo, $langKey, $translations);

            if ($success) {
                echo json_encode([
                    'success' => true,
                    'message' => 'Translations saved successfully'
                ]);
            } else {
                http_response_code(500);
                echo json_encode([
                    'success' => false,
                    'error' => 'Failed to save translations'
                ]);
            }
        } else {
            echo json_encode(['success' => false, 'error' => 'Invalid action']);
        }
        break;

    default:
        http_response_code(405);
        echo json_encode(['success' => false, 'error' => 'Method not allowed']);
}
