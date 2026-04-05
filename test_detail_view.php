<?php
// 첫 예약 정보 가져오기
$db = new \PDO(
    'mysql:host=127.0.0.1;dbname=rezlyx_salon;charset=utf8mb4',
    'root',
    '',
    [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
);

$stmt = $db->query("SELECT reservation_number FROM rzx_reservations LIMIT 1");
$reservation = $stmt->fetch(\PDO::FETCH_ASSOC);

if ($reservation) {
    echo "첫 번째 예약 번호: " . htmlspecialchars($reservation['reservation_number']) . "\n";
    echo "상세보기 URL: http://localhost/rezlyx_salon/booking/detail/" . urlencode($reservation['reservation_number']) . "\n";
    echo "\n자동 리다이렉트 중...\n";
    header('Location: /rezlyx_salon/booking/detail/' . urlencode($reservation['reservation_number']));
    exit;
} else {
    echo "예약이 없습니다.";
}
?>
