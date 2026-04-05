<?php
/**
 * Booking Lookup Test Helper
 * 데이터베이스의 예약 데이터를 확인하고 조회 기능을 테스트합니다.
 */

require_once __DIR__ . '/rzxlib/Core/Application.php';

use RzxLib\Core\Application;

// Initialize application
$app = new Application(__DIR__);
$config = $app->config();

// Database connection
$db = new \PDO(
    'mysql:host=' . $config['db_host'] . ';dbname=' . $config['db_database'] . ';charset=utf8mb4',
    $config['db_username'],
    $config['db_password'],
    [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    ]
);

echo "========================================\n";
echo "Booking Lookup Test Report\n";
echo "========================================\n\n";

// 1. Check bookings table existence and row count
echo "1. Checking database bookings table...\n";
try {
    $stmt = $db->query("SELECT COUNT(*) as cnt FROM " . $config['db_prefix'] . "bookings");
    $result = $stmt->fetch();
    $bookingCount = $result['cnt'] ?? 0;
    echo "   Total bookings: " . $bookingCount . "\n";

    if ($bookingCount > 0) {
        // Get sample booking
        $stmt = $db->query("SELECT * FROM " . $config['db_prefix'] . "bookings ORDER BY created_at DESC LIMIT 1");
        $sample = $stmt->fetch();
        echo "\n   Sample booking (latest):\n";
        echo "   - ID: " . $sample['id'] . "\n";
        echo "   - Number: " . $sample['reservation_number'] . "\n";
        echo "   - Email: " . $sample['customer_email'] . "\n";
        echo "   - Phone: " . $sample['customer_phone'] . "\n";
        echo "   - Status: " . $sample['status'] . "\n";
        echo "   - Date: " . $sample['reservation_date'] . "\n";
    } else {
        echo "   WARNING: No bookings in database\n";
    }
} catch (\Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

// 2. Get sample data for testing
echo "\n2. Gathering test data...\n";
try {
    $stmt = $db->query("SELECT DISTINCT customer_email FROM " . $config['db_prefix'] . "bookings WHERE customer_email IS NOT NULL AND customer_email != '' LIMIT 3");
    $emails = $stmt->fetchAll(\PDO::FETCH_COLUMN);
    echo "   Sample emails available: " . count($emails) . "\n";
    foreach ($emails as $email) {
        echo "   - " . htmlspecialchars($email) . "\n";
    }

    $stmt = $db->query("SELECT DISTINCT customer_phone FROM " . $config['db_prefix'] . "bookings WHERE customer_phone IS NOT NULL AND customer_phone != '' LIMIT 3");
    $phones = $stmt->fetchAll(\PDO::FETCH_COLUMN);
    echo "\n   Sample phones available: " . count($phones) . "\n";
    foreach ($phones as $phone) {
        echo "   - " . htmlspecialchars($phone) . "\n";
    }

    $stmt = $db->query("SELECT reservation_number FROM " . $config['db_prefix'] . "bookings LIMIT 3");
    $numbers = $stmt->fetchAll(\PDO::FETCH_COLUMN);
    echo "\n   Sample booking codes: " . count($numbers) . "\n";
    foreach ($numbers as $number) {
        echo "   - " . htmlspecialchars($number) . "\n";
    }
} catch (\Exception $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

echo "\n3. Testing lookup page form submission...\n";
echo "   Form inputs expected:\n";
echo "   - booking_code: text input (uppercase, format: RZ250301XXXXXX)\n";
echo "   - email: email input\n";
echo "   - phone: tel input (with country code support)\n";
echo "   - Method: POST\n";

echo "\n4. UI Components found:\n";
echo "   - Search form with 3 optional input fields\n";
echo "   - Search button with submit type\n";
echo "   - Error message display (red box)\n";
echo "   - Results section with cards\n";
echo "   - Status badge for each booking\n";
echo "   - Links to detail and cancel pages\n";

echo "\n5. Translation keys required:\n";
echo "   - booking.lookup.title\n";
echo "   - booking.lookup.description\n";
echo "   - booking.lookup.booking_code\n";
echo "   - booking.lookup.email\n";
echo "   - booking.lookup.phone\n";
echo "   - booking.lookup.search\n";
echo "   - booking.lookup.not_found\n";
echo "   - booking.lookup.input_required\n";
echo "   - booking.lookup.result_title\n";

echo "\n========================================\n";
echo "Test Complete\n";
echo "========================================\n";
