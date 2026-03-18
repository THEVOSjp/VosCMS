<?php
/**
 * PDO 버퍼링 테스트 - 문제 진단 후 삭제할 것
 */
header('Content-Type: application/json');

// .env 수동 파싱
$envPath = __DIR__ . '/.env';
$env = [];
if (file_exists($envPath)) {
    foreach (file($envPath, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (strpos($line, '=') !== false) {
            [$k, $v] = explode('=', $line, 2);
            $env[trim($k)] = trim($v, " \"'");
        }
    }
}

$results = [];

// 테스트 1: 생성자 옵션으로 buffered query
try {
    $pdo1 = new PDO(
        'mysql:host=' . ($env['DB_HOST'] ?? '127.0.0.1') . ';port=' . ($env['DB_PORT'] ?? '3306') . ';dbname=' . ($env['DB_DATABASE'] ?? 'rezlyx') . ';charset=utf8mb4',
        $env['DB_USERNAME'] ?? 'root',
        $env['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true]
    );

    // 확인: buffered 설정 값
    $results['buffered_attr'] = $pdo1->getAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY) ? 'ON' : 'OFF';

    // 쿼리 1: fetchAll
    $stmt1 = $pdo1->prepare("SELECT `key`, `value` FROM rzx_settings LIMIT 5");
    $stmt1->execute();
    $rows1 = $stmt1->fetchAll(PDO::FETCH_ASSOC);
    $stmt1->closeCursor();
    $results['test1_fetchAll'] = 'OK (' . count($rows1) . ' rows)';

    // 쿼리 2: 바로 이어서 다른 쿼리
    $stmt2 = $pdo1->prepare("SELECT COUNT(*) FROM rzx_settings");
    $stmt2->execute();
    $count = $stmt2->fetchAll(PDO::FETCH_COLUMN);
    $stmt2->closeCursor();
    $results['test2_second_query'] = 'OK (count=' . ($count[0] ?? '?') . ')';

    $pdo1 = null;
} catch (Throwable $e) {
    $results['test_constructor_error'] = $e->getMessage();
}

// 테스트 2: setAttribute로 buffered query
try {
    $pdo2 = new PDO(
        'mysql:host=' . ($env['DB_HOST'] ?? '127.0.0.1') . ';port=' . ($env['DB_PORT'] ?? '3306') . ';dbname=' . ($env['DB_DATABASE'] ?? 'rezlyx') . ';charset=utf8mb4',
        $env['DB_USERNAME'] ?? 'root',
        $env['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $pdo2->setAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY, true);
    $results['buffered_attr_setattr'] = $pdo2->getAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY) ? 'ON' : 'OFF';

    $s1 = $pdo2->prepare("SELECT `key`, `value` FROM rzx_settings LIMIT 5");
    $s1->execute();
    $r1 = $s1->fetchAll(PDO::FETCH_ASSOC);
    $s1->closeCursor();

    $s2 = $pdo2->prepare("SELECT COUNT(*) FROM rzx_settings");
    $s2->execute();
    $c2 = $s2->fetchAll(PDO::FETCH_COLUMN);
    $s2->closeCursor();
    $results['test3_setattr'] = 'OK';

    $pdo2 = null;
} catch (Throwable $e) {
    $results['test_setattr_error'] = $e->getMessage();
}

// 테스트 3: fetchColumn 없이 fetchAll만 사용 (unbuffered)
try {
    $pdo3 = new PDO(
        'mysql:host=' . ($env['DB_HOST'] ?? '127.0.0.1') . ';port=' . ($env['DB_PORT'] ?? '3306') . ';dbname=' . ($env['DB_DATABASE'] ?? 'rezlyx') . ';charset=utf8mb4',
        $env['DB_USERNAME'] ?? 'root',
        $env['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    // buffered 설정 안 함!
    $results['unbuffered_attr'] = $pdo3->getAttribute(PDO::MYSQL_ATTR_USE_BUFFERED_QUERY) ? 'ON' : 'OFF';

    $s1 = $pdo3->prepare("SELECT `key`, `value` FROM rzx_settings LIMIT 5");
    $s1->execute();
    $r1 = $s1->fetchAll(PDO::FETCH_ASSOC);
    $s1->closeCursor();

    $s2 = $pdo3->prepare("SELECT COUNT(*) FROM rzx_settings");
    $s2->execute();
    $c2 = $s2->fetchAll(PDO::FETCH_COLUMN);
    $s2->closeCursor();
    $results['test4_unbuffered_fetchall'] = 'OK';

    $pdo3 = null;
} catch (Throwable $e) {
    $results['test_unbuffered_error'] = $e->getMessage();
}

// PHP/MySQL 정보
$results['php_version'] = PHP_VERSION;
$results['pdo_drivers'] = implode(', ', PDO::getAvailableDrivers());

echo json_encode($results, JSON_PRETTY_PRINT);
