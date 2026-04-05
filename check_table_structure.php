<?php
// .env 로드
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (strpos($line, '=') !== false && strpos($line, '#') !== 0) {
            list($key, $value) = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value, '\'"');
        }
    }
}

try {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        $_ENV['DB_HOST'] ?? 'localhost',
        $_ENV['DB_NAME'] ?? 'rezlyx_dev'
    );
    
    $pdo = new PDO($dsn, $_ENV['DB_USER'] ?? 'root', $_ENV['DB_PASS'] ?? '');
} catch (PDOException $e) {
    die('DB error: ' . $e->getMessage());
}

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

// 테이블 구조 확인
echo "Table Structure: {$prefix}reservations\n";
echo "=====================================\n";
$stmt = $pdo->query("DESCRIBE {$prefix}reservations");
$columns = $stmt->fetchAll(PDO::FETCH_ASSOC);
foreach ($columns as $col) {
    echo "  {$col['Field']}: {$col['Type']} ({$col['Null']}) [KEY: {$col['Key']}]\n";
}
