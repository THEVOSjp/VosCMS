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

// DB 연결
try {
    $dsn = sprintf(
        'mysql:host=%s;dbname=%s;charset=utf8mb4',
        $_ENV['DB_HOST'] ?? 'localhost',
        $_ENV['DB_NAME'] ?? 'rezlyx_dev'
    );
    
    $pdo = new PDO($dsn, $_ENV['DB_USER'] ?? 'root', $_ENV['DB_PASS'] ?? '');
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
} catch (PDOException $e) {
    http_response_code(503);
    die(json_encode(['error' => 'DB error: ' . $e->getMessage()]));
}

$reservationNumber = 'e54d600f-5899-adbb-c141-065db9b6aae4';
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

// 예약 정보 조회
$stmt = $pdo->prepare("SELECT * FROM {$prefix}reservations WHERE reservation_number = ? LIMIT 1");
$stmt->execute([$reservationNumber]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reservation) {
    die(json_encode(['error' => 'Reservation not found', 'query' => $reservationNumber]));
}

// 서비스 정보
$svcStmt = $pdo->prepare("
    SELECT rs.*, s.image, s.description
    FROM {$prefix}reservation_services rs
    LEFT JOIN {$prefix}services s ON rs.service_id = s.id
    WHERE rs.reservation_id = ?
    ORDER BY rs.sort_order
");
$svcStmt->execute([$reservation['id']]);
$services = $svcStmt->fetchAll(PDO::FETCH_ASSOC);

// 스태프 정보
$staff = null;
if ($reservation['staff_id']) {
    $staffStmt = $pdo->prepare("SELECT id, name FROM {$prefix}staff WHERE id = ? LIMIT 1");
    $staffStmt->execute([$reservation['staff_id']]);
    $staff = $staffStmt->fetch(PDO::FETCH_ASSOC);
}

$result = [
    'reservation' => [
        'number' => $reservation['reservation_number'],
        'customer_name' => $reservation['customer_name'],
        'reservation_date' => $reservation['reservation_date'],
        'start_time' => $reservation['start_time'],
        'end_time' => $reservation['end_time'],
        'status' => $reservation['status']
    ],
    'staff' => $staff,
    'services' => array_map(function($s) {
        return [
            'service_name' => $s['service_name'] ?? 'Unknown',
            'duration' => $s['duration'] ?? 0,
            'price' => $s['price'] ?? 0
        ];
    }, $services),
    'service_count' => count($services)
];

header('Content-Type: application/json; charset=utf-8');
echo json_encode($result, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT);
