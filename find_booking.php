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

// 정확히 검색
$stmt = $pdo->prepare("SELECT id, reservation_number, customer_name, staff_id FROM {$prefix}reservations WHERE reservation_number = ?");
$stmt->execute(['e54d600f-5899-adbb-c141-065db9b6aae4']);
$result = $stmt->fetch(PDO::FETCH_ASSOC);

if ($result) {
    echo "✓ Found booking:\n";
    echo "  ID: " . $result['id'] . "\n";
    echo "  Number: " . $result['reservation_number'] . "\n";
    echo "  Name: " . $result['customer_name'] . "\n";
    echo "  Staff ID: " . $result['staff_id'] . "\n";
} else {
    echo "✗ Not found with exact number\n";
    
    // Like 검색
    echo "\nSearching with LIKE:\n";
    $stmt = $pdo->prepare("SELECT id, reservation_number, customer_name FROM {$prefix}reservations WHERE reservation_number LIKE ?");
    $stmt->execute(['e54d600f%']);
    $results = $stmt->fetchAll(PDO::FETCH_ASSOC);
    if (count($results) > 0) {
        foreach ($results as $r) {
            echo "  - " . $r['reservation_number'] . " (" . $r['customer_name'] . ")\n";
        }
    } else {
        echo "  No results\n";
    }
}
