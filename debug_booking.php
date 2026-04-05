<?php
// .env 파일 로드
$env_file = '/e/xampp/htdocs/project/rezlyx_salon/.env';
if (!file_exists($env_file)) {
    echo "Error: .env file not found at $env_file\n";
    exit(1);
}

$env_content = file_get_contents($env_file);
preg_match('/DB_HOST=([^\n\r]+)/', $env_content, $m_host);
preg_match('/DB_USER=([^\n\r]+)/', $env_content, $m_user);
preg_match('/DB_PASS=([^\n\r]+)/', $env_content, $m_pass);
preg_match('/DB_NAME=([^\n\r]+)/', $env_content, $m_name);

$host = trim($m_host[1] ?? '');
$user = trim($m_user[1] ?? '');
$pass = trim($m_pass[1] ?? '');
$name = trim($m_name[1] ?? '');

if (!$host || !$user || !$name) {
    echo "Error: Missing DB config. host=$host, user=$user, name=$name\n";
    exit(1);
}

try {
    $pdo = new PDO("mysql:host=$host;dbname=$name;charset=utf8mb4", $user, $pass);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // 예약 정보 조회
    $stmt = $pdo->prepare("SELECT id, booking_code, staff_id, coupon_id, service_ids FROM rzx_bookings WHERE booking_code = ?");
    $stmt->execute(['RZX260328B7C5A2']);
    $booking = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$booking) {
        echo "Booking not found\n";
        exit(1);
    }
    
    echo "=== BOOKING INFO ===\n";
    echo "ID: " . $booking['id'] . "\n";
    echo "Code: " . $booking['booking_code'] . "\n";
    echo "Staff ID: " . $booking['staff_id'] . "\n";
    echo "Coupon ID: " . $booking['coupon_id'] . "\n";
    echo "Service IDs: " . $booking['service_ids'] . "\n";
    
    // 스태프 정보 조회
    if ($booking['staff_id']) {
        $stmt = $pdo->prepare("SELECT id, name, display_name FROM rzx_staff WHERE id = ?");
        $stmt->execute([$booking['staff_id']]);
        $staff = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "\n=== STAFF INFO ===\n";
        echo "Staff Name: " . ($staff['display_name'] ?: $staff['name']) . "\n";
    }
    
    // 쿠폰 정보 조회
    if ($booking['coupon_id']) {
        $stmt = $pdo->prepare("SELECT id, name FROM rzx_coupons WHERE id = ?");
        $stmt->execute([$booking['coupon_id']]);
        $coupon = $stmt->fetch(PDO::FETCH_ASSOC);
        echo "\n=== COUPON INFO ===\n";
        echo "Coupon Name: " . $coupon['name'] . "\n";
    }
    
    // 서비스 정보 조회
    if ($booking['service_ids']) {
        $service_ids = json_decode($booking['service_ids'], true);
        if (is_array($service_ids)) {
            echo "\n=== SERVICES ===\n";
            foreach ($service_ids as $svc_id) {
                $stmt = $pdo->prepare("SELECT id, name FROM rzx_services WHERE id = ?");
                $stmt->execute([$svc_id]);
                $service = $stmt->fetch(PDO::FETCH_ASSOC);
                if ($service) {
                    echo "- " . $service['name'] . "\n";
                }
            }
        }
    }
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
    exit(1);
}
