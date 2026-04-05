<?php
// 테스트용: lookup 페이지에서 예약을 검색하고 상세보기 클릭 시뮬레이션

// 데이터베이스 연결
$db = new \PDO(
    'mysql:host=127.0.0.1;dbname=rezlyx_salon;charset=utf8mb4',
    'root',
    '',
    [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
);

// 첫 번째 예약 가져오기
$stmt = $db->query("SELECT id, reservation_number, customer_email, customer_phone, customer_name FROM rzx_reservations LIMIT 1");
$reservation = $stmt->fetch(\PDO::FETCH_ASSOC);

if (!$reservation) {
    die("No reservations found");
}

// 예약의 상세보기 URL로 리다이렉트
// lookup 페이지의 상세보기 버튼은 /lookup/detail?reservation_id={id} 형태일 것으로 예상
$detail_url = '/rezlyx_salon/lookup/detail?reservation_id=' . urlencode($reservation['id']);

echo "<!DOCTYPE html>";
echo "<html>";
echo "<head>";
echo "<title>Lookup Detail Test</title>";
echo "<meta charset='utf-8'>";
echo "<meta name='viewport' content='width=device-width, initial-scale=1'>";
echo "</head>";
echo "<body>";
echo "<h1>Reservation Lookup Detail Test</h1>";
echo "<p>Redirecting to detail page...</p>";
echo "<p>Reservation ID: " . htmlspecialchars($reservation['id']) . "</p>";
echo "<p>Reservation Number: " . htmlspecialchars($reservation['reservation_number']) . "</p>";
echo "<p>Customer Email: " . htmlspecialchars($reservation['customer_email']) . "</p>";
echo "<p><a href='" . htmlspecialchars($detail_url) . "'>Click here to view detail</a></p>";

// 자동 리다이렉트
echo "<script>";
echo "setTimeout(function() {";
echo "  window.location.href = '" . htmlspecialchars($detail_url) . "';";
echo "}, 1000);";
echo "</script>";
echo "</body>";
echo "</html>";
?>
