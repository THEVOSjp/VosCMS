<?php
/**
 * 코어 /api/* 의 라이선스/개발자 API용 plugin dispatch 헬퍼.
 *
 * 본사 voscms.com 전용 API(라이선스 서버, 개발자 포털)는 plugin으로 분리되어 있음.
 * 코어의 같은 path 파일은 단순 wrapper — plugin이 활성화된 환경에서만 dispatch.
 * 비활성/미설치 환경에서는 404 반환 → 외부 노출 자동 차단.
 */

function dispatchToPlugin(string $pluginId, string $apiFile): void
{
    $coreRoot = dirname(__DIR__);
    $pluginApi = $coreRoot . '/plugins/' . $pluginId . '/api/' . $apiFile;

    if (!file_exists($pluginApi)) {
        http_response_code(404);
        exit;
    }

    // .env 로드 (단순 파서)
    if (empty($_ENV['DB_HOST']) && file_exists($coreRoot . '/.env')) {
        foreach (file($coreRoot . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
            if (strpos(trim($line), '#') === 0 || strpos($line, '=') === false) continue;
            [$k, $v] = explode('=', $line, 2);
            $_ENV[trim($k)] = trim($v, "\"' ");
        }
    }

    // plugin 활성 여부 DB 조회
    try {
        $pdo = new PDO(
            "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
            $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
        );
        $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
        $stmt = $pdo->prepare("SELECT id FROM {$prefix}plugins WHERE plugin_id = ?");
        $stmt->execute([$pluginId]);
        if (!$stmt->fetch()) {
            http_response_code(404);
            exit;
        }
    } catch (\PDOException $e) {
        http_response_code(503);
        exit;
    }

    require $pluginApi;
    exit;
}
