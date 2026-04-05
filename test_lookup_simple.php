<?php
/**
 * Simple Booking Lookup Test
 */

// Database connection
$db = new \PDO(
    'mysql:host=127.0.0.1;dbname=rezlyx_salon;charset=utf8mb4',
    'root',
    '',
    [
        \PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION,
        \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC,
    ]
);

echo "========================================\n";
echo "Booking Lookup Test Report\n";
echo "========================================\n\n";

// 1. Check bookings table
echo "1. Database table check:\n";
try {
    $stmt = $db->query("SELECT COUNT(*) as cnt FROM rzx_bookings");
    $result = $stmt->fetch();
    $bookingCount = $result['cnt'] ?? 0;
    echo "   Total bookings in database: " . $bookingCount . "\n";

    if ($bookingCount > 0) {
        // Get sample booking
        $stmt = $db->query("SELECT * FROM rzx_bookings ORDER BY created_at DESC LIMIT 1");
        $sample = $stmt->fetch();
        echo "\n   Sample booking (latest):\n";
        echo "   ID: " . $sample['id'] . "\n";
        echo "   Number: " . $sample['reservation_number'] . "\n";
        echo "   Email: " . $sample['customer_email'] . "\n";
        echo "   Phone: " . $sample['customer_phone'] . "\n";
        echo "   Status: " . $sample['status'] . "\n";
        echo "   Service ID: " . $sample['service_id'] . "\n";
        echo "   Created: " . $sample['created_at'] . "\n";
    } else {
        echo "   WARNING: No bookings in database!\n";
        echo "   Need to insert test data first.\n";
    }
} catch (\PDOException $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

// 2. Get all distinct emails
echo "\n2. Sample test data (first 5 of each):\n";
try {
    echo "\n   Emails:\n";
    $stmt = $db->query("SELECT DISTINCT customer_email FROM rzx_bookings WHERE customer_email IS NOT NULL AND customer_email != '' LIMIT 5");
    $emails = $stmt->fetchAll(\PDO::FETCH_COLUMN);
    foreach ($emails as $email) {
        echo "   - " . htmlspecialchars($email) . "\n";
    }
    if (count($emails) === 0) {
        echo "   (none)\n";
    }

    echo "\n   Phone numbers:\n";
    $stmt = $db->query("SELECT DISTINCT customer_phone FROM rzx_bookings WHERE customer_phone IS NOT NULL AND customer_phone != '' LIMIT 5");
    $phones = $stmt->fetchAll(\PDO::FETCH_COLUMN);
    foreach ($phones as $phone) {
        echo "   - " . htmlspecialchars($phone) . "\n";
    }
    if (count($phones) === 0) {
        echo "   (none)\n";
    }

    echo "\n   Reservation numbers:\n";
    $stmt = $db->query("SELECT reservation_number FROM rzx_bookings LIMIT 5");
    $numbers = $stmt->fetchAll(\PDO::FETCH_COLUMN);
    foreach ($numbers as $number) {
        echo "   - " . htmlspecialchars($number) . "\n";
    }
    if (count($numbers) === 0) {
        echo "   (none)\n";
    }
} catch (\PDOException $e) {
    echo "   ERROR: " . $e->getMessage() . "\n";
}

echo "\n========================================\n";
echo "End of Report\n";
echo "========================================\n";
?>
