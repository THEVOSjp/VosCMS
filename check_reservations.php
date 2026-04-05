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

// 예약 테이블 확인
$countStmt = $pdo->query("SELECT COUNT(*) FROM {$prefix}reservations");
$count = $countStmt->fetchColumn();
echo "Total reservations: $count\n\n";

// 최근 5개 조회
$stmt = $pdo->query("SELECT id, reservation_number, customer_name, status FROM {$prefix}reservations ORDER BY created_at DESC LIMIT 5");
$rows = $stmt->fetchAll(PDO::FETCH_ASSOC);
echo "Recent 5 reservations:\n";
foreach ($rows as $row) {
    echo "  - {$row['reservation_number']}: {$row['customer_name']} ({$row['status']})\n";
}

// 안소영 예약 찾기
echo "\nSearching for customer '안소영':\n";
$stmt = $pdo->prepare("SELECT id, reservation_number, customer_name, status FROM {$prefix}reservations WHERE customer_name LIKE ? LIMIT 5");
$stmt->execute(['%안소영%']);
$results = $stmt->fetchAll(PDO::FETCH_ASSOC);
if (count($results) > 0) {
    foreach ($results as $row) {
        echo "  - {$row['reservation_number']}: {$row['customer_name']} ({$row['status']})\n";
    }
} else {
    echo "  No results found\n";
}
