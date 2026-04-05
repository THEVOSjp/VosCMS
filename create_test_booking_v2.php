<?php
error_reporting(E_ALL);
ini_set('display_errors', 1);

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

echo "DB_HOST: " . ($_ENV['DB_HOST'] ?? 'localhost') . "\n";
echo "DB_NAME: " . ($_ENV['DB_NAME'] ?? 'rezlyx_dev') . "\n";

try {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        $_ENV['DB_HOST'] ?? 'localhost',
        $_ENV['DB_NAME'] ?? 'rezlyx_dev'
    );
    
    $pdo = new PDO($dsn, $_ENV['DB_USER'] ?? 'root', $_ENV['DB_PASS'] ?? '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    echo "DB Connected OK\n\n";
} catch (PDOException $e) {
    die('DB error: ' . $e->getMessage());
}

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$reservationNumber = 'e54d600f-5899-adbb-c141-065db9b6aae4';

// 기존 데이터 확인
echo "Checking existing booking...\n";
$checkStmt = $pdo->prepare("SELECT id FROM {$prefix}reservations WHERE reservation_number = ?");
$checkStmt->execute([$reservationNumber]);
$existing = $checkStmt->fetch(PDO::FETCH_ASSOC);
if ($existing) {
    echo "  Already exists (ID: " . $existing['id'] . ")\n\n";
} else {
    echo "  Not found - creating new\n\n";
    
    // 스태프 조회
    $staffStmt = $pdo->query("SELECT id FROM {$prefix}staff WHERE id >= 1 LIMIT 1");
    $staffRow = $staffStmt->fetch(PDO::FETCH_ASSOC);
    $staffId = $staffRow['id'] ?? 1;
    echo "Using Staff ID: $staffId\n";
    
    // 서비스 조회
    $svcStmt = $pdo->query("SELECT id FROM {$prefix}services WHERE id >= 1 LIMIT 1");
    $svcRow = $svcStmt->fetch(PDO::FETCH_ASSOC);
    $serviceId = $svcRow['id'] ?? 1;
    echo "Using Service ID: $serviceId\n\n";
    
    // 예약 생성
    try {
        echo "Inserting reservation...\n";
        $stmt = $pdo->prepare("
            INSERT INTO {$prefix}reservations (
                reservation_number, customer_name, customer_email, customer_phone,
                reservation_date, start_time, end_time, staff_id,
                total_amount, status, payment_status, created_at
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())
        ");
        
        $stmt->execute([
            $reservationNumber,
            '안소영',
            'ansoyoung@example.com',
            '010-1234-5678',
            date('Y-m-d', strtotime('+1 day')),
            '10:00:00',
            '11:00:00',
            $staffId,
            50000,
            'confirmed',
            'unpaid'
        ]);
        
        $reservationId = $pdo->lastInsertId();
        echo "  ✓ Reservation created (ID: $reservationId)\n";
        
        // 예약 서비스 추가
        echo "Adding reservation service...\n";
        $svcStmt = $pdo->prepare("
            INSERT INTO {$prefix}reservation_services (
                reservation_id, service_id, service_name, duration, price, sort_order
            ) VALUES (?, ?, ?, ?, ?, ?)
        ");
        
        $svcStmt->execute([
            $reservationId,
            $serviceId,
            '헤어컷',
            60,
            50000,
            0
        ]);
        echo "  ✓ Service added\n";
        
    } catch (PDOException $e) {
        die('  ✗ Error: ' . $e->getMessage());
    }
}

// 최종 확인
echo "\nFinal verification:\n";
$verifyStmt = $pdo->prepare("
    SELECT r.*, s.name as staff_name 
    FROM {$prefix}reservations r
    LEFT JOIN {$prefix}staff s ON r.staff_id = s.id
    WHERE r.reservation_number = ?
");
$verifyStmt->execute([$reservationNumber]);
$result = $verifyStmt->fetch(PDO::FETCH_ASSOC);
if ($result) {
    echo "  ✓ Booking verified:\n";
    echo "    Number: " . $result['reservation_number'] . "\n";
    echo "    Name: " . $result['customer_name'] . "\n";
    echo "    Staff: " . ($result['staff_name'] ?: 'N/A') . "\n";
}
