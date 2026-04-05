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
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    die('DB error: ' . $e->getMessage());
}

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

// UUID 생성 함수
function generateUUID() {
    return sprintf(
        '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
        mt_rand(0, 0xffff), mt_rand(0, 0xffff),
        mt_rand(0, 0xffff),
        mt_rand(0, 0x0fff) | 0x4000,
        mt_rand(0, 0x3fff) | 0x8000,
        mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
    );
}

$reservationId = generateUUID();
$reservationNumber = 'e54d600f-5899-adbb-c141-065db9b6aae4';

// 스태프 조회
$staffStmt = $pdo->query("SELECT id FROM {$prefix}staff WHERE id >= 1 LIMIT 1");
$staffRow = $staffStmt->fetch(PDO::FETCH_ASSOC);
$staffId = $staffRow['id'] ?? 1;

// 서비스 조회
$svcStmt = $pdo->query("SELECT id FROM {$prefix}services WHERE id IS NOT NULL LIMIT 1");
$svcRow = $svcStmt->fetch(PDO::FETCH_ASSOC);
$serviceId = $svcRow['id'] ?? null;

echo "Creating booking with UUID: $reservationId\n";
echo "Staff ID: $staffId, Service ID: $serviceId\n\n";

try {
    $stmt = $pdo->prepare("
        INSERT INTO {$prefix}reservations (
            id, reservation_number, customer_name, customer_email, customer_phone,
            reservation_date, start_time, end_time, staff_id,
            total_amount, final_amount, status, source, payment_status, paid_amount,
            created_at, updated_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
    ");
    
    $stmt->execute([
        $reservationId,
        $reservationNumber,
        '안소영',
        'ansoyoung@example.com',
        '010-1234-5678',
        date('Y-m-d', strtotime('+1 day')),
        '10:00:00',
        '11:00:00',
        $staffId,
        50000,
        50000,
        'confirmed',
        'web',
        'unpaid',
        0
    ]);
    
    echo "✓ Reservation created\n";
    
    // 예약 서비스 추가
    $svcId = generateUUID();
    $svcStmt = $pdo->prepare("
        INSERT INTO {$prefix}reservation_services (
            id, reservation_id, service_id, service_name, duration, price, sort_order
        ) VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    
    $svcStmt->execute([
        $svcId,
        $reservationId,
        $serviceId,
        '헤어컷',
        60,
        50000,
        0
    ]);
    echo "✓ Service added\n\n";
    
    // 확인
    $verifyStmt = $pdo->prepare("SELECT * FROM {$prefix}reservations WHERE reservation_number = ?");
    $verifyStmt->execute([$reservationNumber]);
    $verify = $verifyStmt->fetch(PDO::FETCH_ASSOC);
    
    if ($verify) {
        echo "✓ Verification successful:\n";
        echo "  Number: " . $verify['reservation_number'] . "\n";
        echo "  Name: " . $verify['customer_name'] . "\n";
        echo "  Date: " . $verify['reservation_date'] . "\n";
        echo "  Status: " . $verify['status'] . "\n";
    }
    
} catch (PDOException $e) {
    die('Error: ' . $e->getMessage());
}
