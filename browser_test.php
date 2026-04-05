<?php
// 테스트: lookup 페이지 및 detail 페이지 검증

// 데이터베이스에서 예약 정보 가져오기
$db = new \PDO(
    'mysql:host=127.0.0.1;dbname=rezlyx_salon;charset=utf8mb4',
    'root',
    '',
    [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION]
);

$stmt = $db->query("SELECT id, reservation_number, customer_email, customer_phone FROM rzx_reservations LIMIT 1");
$reservation = $stmt->fetch(\PDO::FETCH_ASSOC);

if (!$reservation) {
    die("No reservations found");
}

?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="utf-8">
    <title>RezlyX Salon - Lookup & Detail Test</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
</head>
<body style="font-family: Arial, sans-serif; max-width: 900px; margin: 20px auto;">
    <h1>RezlyX Salon - Lookup & Detail Page Test</h1>
    
    <h2>시나리오</h2>
    <ol>
        <li>lookup 페이지로 이동</li>
        <li>예약 정보로 검색</li>
        <li>"상세보기" 버튼 클릭</li>
        <li>detail 페이지에서 2컬럼 레이아웃 검증</li>
    </ol>
    
    <h2>테스트 데이터</h2>
    <ul>
        <li>예약 ID: <?php echo htmlspecialchars($reservation['id']); ?></li>
        <li>예약번호: <?php echo htmlspecialchars($reservation['reservation_number']); ?></li>
        <li>고객 이메일: <?php echo htmlspecialchars($reservation['customer_email']); ?></li>
        <li>고객 전화: <?php echo htmlspecialchars($reservation['customer_phone']); ?></li>
    </ul>
    
    <h2>직접 링크</h2>
    <ul>
        <li><a href="/rezlyx_salon/lookup" target="_blank">lookup 페이지 열기</a></li>
        <li><a href="/rezlyx_salon/booking/detail/<?php echo urlencode($reservation['reservation_number']); ?>" target="_blank">detail 페이지 직접 열기</a></li>
    </ul>
    
    <h2>테스트 방법</h2>
    <p>1. lookup 페이지를 열어서 다음 정보로 검색:</p>
    <pre>
이메일: <?php echo htmlspecialchars($reservation['customer_email']); ?>
    </pre>
    
    <p>2. 검색 결과에서 "상세보기" (booking.detail.title) 버튼 클릭</p>
    
    <p>3. 또는 아래 직접 링크로 detail 페이지 열기:</p>
    <pre>
/rezlyx_salon/booking/detail/<?php echo urlencode($reservation['reservation_number']); ?>
    </pre>
    
    <h2>검증 사항</h2>
    <ul>
        <li>좌측: 예약 정보 + 고객 정보 (flex-1)</li>
        <li>우측: 결제 정보 사이드바 (lg:w-80, sticky)</li>
        <li>데스크톱에서는 2컬럼, 모바일에서는 1컬럼으로 변환</li>
        <li>헤더: 예약번호, 상태 배지</li>
        <li>날짜/시간, 서비스, 고객 정보 표시</li>
        <li>결제 금액 계산 정상 표시</li>
    </ul>
</body>
</html>
