<?php
/**
 * POS/키오스크 테스트 예약 데이터 시드
 * 사용: php database/seed_reservations.php
 *
 * 생성 항목:
 * - 오늘 날짜 기준 10건의 예약 (다양한 상태/소스)
 * - 각 예약에 1~3개 서비스 연결
 * - POS, 키오스크, 온라인, 관리자 등 소스 다양
 */

$envFile = __DIR__ . '/../.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (str_contains($line, '=')) {
            [$k, $v] = explode('=', $line, 2);
            $_ENV[trim($k)] = trim(trim($v), '"');
        }
    }
}

$pdo = new PDO(
    "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
    $_ENV['DB_USERNAME'],
    $_ENV['DB_PASSWORD'],
    [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
);

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$today = date('Y-m-d');

echo "=== RezlyX 예약 샘플 데이터 시드 ===\n";
echo "날짜: {$today}\n\n";

// 서비스 목록 가져오기
$services = $pdo->query("SELECT id, name, price, duration FROM {$prefix}services WHERE is_active = 1 ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC);
if (empty($services)) {
    die("[오류] 서비스가 없습니다. seed_services.php를 먼저 실행하세요.\n");
}

// 스태프 목록 가져오기
$staffList = $pdo->query("SELECT id, name, designation_fee FROM {$prefix}staff WHERE is_active = 1 ORDER BY sort_order")->fetchAll(PDO::FETCH_ASSOC);
if (empty($staffList)) {
    die("[오류] 스태프가 없습니다. seed_staff_test.php를 먼저 실행하세요.\n");
}

// 샘플 고객 데이터
$customers = [
    ['name' => '김민수', 'phone' => '+821012345678'],
    ['name' => '田中 花子', 'phone' => '+819012345678'],
    ['name' => 'John Smith', 'phone' => '+12025551234'],
    ['name' => '이영희', 'phone' => '+821098765432'],
    ['name' => '佐藤 太郎', 'phone' => '+818023456789'],
    ['name' => '박서준', 'phone' => '+821055551234'],
    ['name' => 'Emma Wilson', 'phone' => '+447911123456'],
    ['name' => '워크인', 'phone' => ''],
    ['name' => '워크인', 'phone' => ''],
    ['name' => '鈴木 さくら', 'phone' => '+817012345678'],
];

// 예약 시나리오
$scenarios = [
    // [source, status, startHour, customer_idx, hasStaff]
    ['designation', 'completed', 9, 0, true],
    ['designation', 'completed', 10, 1, true],
    ['walk_in',     'completed', 10, 7, false],
    ['online',      'confirmed', 11, 2, true],
    ['designation', 'in_service', 12, 3, true],
    ['walk_in',     'in_service', 12, 8, false],
    ['kiosk',       'pending', 13, 4, true],
    ['kiosk',       'pending', 13, 5, false],
    ['admin',       'confirmed', 14, 6, true],
    ['online',      'confirmed', 15, 9, true],
];

echo "기존 오늘 예약 삭제 중...\n";
// 오늘 날짜 예약의 서비스 관계 먼저 삭제
$existingIds = $pdo->prepare("SELECT id FROM {$prefix}reservations WHERE reservation_date = ?");
$existingIds->execute([$today]);
$ids = $existingIds->fetchAll(PDO::FETCH_COLUMN);
if (!empty($ids)) {
    $ph = implode(',', array_fill(0, count($ids), '?'));
    $pdo->prepare("DELETE FROM {$prefix}reservation_services WHERE reservation_id IN ({$ph})")->execute($ids);
    $pdo->prepare("DELETE FROM {$prefix}reservations WHERE reservation_date = ?")->execute([$today]);
}

$rsStmt = $pdo->prepare("INSERT INTO {$prefix}reservation_services
    (reservation_id, service_id, service_name, price, duration, sort_order)
    VALUES (?, ?, ?, ?, ?, ?)");

$count = 0;
foreach ($scenarios as $i => $sc) {
    [$source, $status, $startHour, $custIdx, $hasStaff] = $sc;
    $cust = $customers[$custIdx];
    $staff = $hasStaff ? $staffList[$i % count($staffList)] : null;

    // 랜덤 1~3개 서비스 선택
    $svcCount = rand(1, min(3, count($services)));
    $shuffled = $services;
    shuffle($shuffled);
    $selectedSvcs = array_slice($shuffled, 0, $svcCount);

    $totalAmount = 0;
    $totalDuration = 0;
    foreach ($selectedSvcs as $s) {
        $totalAmount += (float)$s['price'];
        $totalDuration += (int)$s['duration'];
    }

    $designationFee = $staff ? (float)$staff['designation_fee'] : 0;
    $finalAmount = $totalAmount + $designationFee;

    $startMin = rand(0, 50);
    $startTime = sprintf('%02d:%02d:00', $startHour, $startMin);
    $endMinutes = $startHour * 60 + $startMin + $totalDuration;
    $endTime = sprintf('%02d:%02d:00', floor($endMinutes / 60) % 24, $endMinutes % 60);

    $id = bin2hex(random_bytes(18));
    $reservationNumber = 'RZX' . date('YmdHis') . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));

    // 결제 상태 (완료된 건은 paid)
    $paymentStatus = in_array($status, ['completed']) ? 'paid' : 'pending';
    $paymentMethod = $paymentStatus === 'paid' ? (['cash', 'card', 'transfer'][rand(0, 2)]) : null;

    $pdo->prepare("INSERT INTO {$prefix}reservations
        (id, reservation_number, user_id, staff_id, designation_fee,
         customer_name, customer_phone,
         reservation_date, start_time, end_time,
         total_amount, final_amount, discount_amount,
         status, source, payment_status, payment_method,
         created_at, updated_at)
        VALUES (?, ?, NULL, ?, ?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, ?, NOW(), NOW())")
        ->execute([
            $id, $reservationNumber,
            $staff ? $staff['id'] : null, $designationFee,
            $cust['name'], $cust['phone'],
            $today, $startTime, $endTime,
            $totalAmount, $finalAmount,
            $status, $source, $paymentStatus, $paymentMethod,
        ]);

    // 서비스 관계 저장
    $sortOrder = 0;
    foreach ($selectedSvcs as $s) {
        $rsStmt->execute([$id, $s['id'], $s['name'], $s['price'], $s['duration'], $sortOrder++]);
    }

    $staffLabel = $staff ? $staff['name'] : '미지정';
    echo "  [{$source}] {$cust['name']} → {$staffLabel} | {$startTime}~{$endTime} | {$status} | ¥" . number_format($finalAmount) . "\n";
    $count++;
}

echo "\n완료! {$count}건의 예약 데이터가 생성되었습니다.\n";
echo "POS: http://localhost/rezlyx/admin/reservations/pos 에서 확인\n";
echo "키오스크: http://localhost/rezlyx/admin/kiosk/run 에서 확인\n";
