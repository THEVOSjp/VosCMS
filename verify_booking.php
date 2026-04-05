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
$reservationNumber = 'e54d600f-5899-adbb-c141-065db9b6aae4';

// 예약 확인
$stmt = $pdo->prepare("SELECT * FROM {$prefix}reservations WHERE reservation_number = ? LIMIT 1");
$stmt->execute([$reservationNumber]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if ($reservation) {
    echo "✓ Booking found:\n";
    echo "  ID: " . $reservation['id'] . "\n";
    echo "  Number: " . $reservation['reservation_number'] . "\n";
    echo "  Name: " . $reservation['customer_name'] . "\n";
    echo "  Date: " . $reservation['reservation_date'] . "\n";
    echo "  Status: " . $reservation['status'] . "\n";
    
    // 서비스 확인
    $svcStmt = $pdo->prepare("SELECT * FROM {$prefix}reservation_services WHERE reservation_id = ?");
    $svcStmt->execute([$reservation['id']]);
    $services = $svcStmt->fetchAll(PDO::FETCH_ASSOC);
    echo "  Services: " . count($services) . "\n";
    foreach ($services as $svc) {
        echo "    - " . $svc['service_name'] . "\n";
    }
} else {
    echo "✗ Booking not found\n";
}

// 스태프 확인
$staffStmt = $pdo->query("SELECT id, name FROM {$prefix}staff LIMIT 1");
$staff = $staffStmt->fetch(PDO::FETCH_ASSOC);
if ($staff) {
    echo "\nStaff available: " . $staff['name'] . " (ID: " . $staff['id'] . ")\n";
}
