<?php
/**
 * Test Lookup Form Submission
 * 예약 조회 기능이 제대로 작동하는지 테스트합니다.
 */

echo "========================================\n";
echo "Lookup Form Submission Test\n";
echo "========================================\n\n";

$baseUrl = 'http://localhost/rezlyx_salon/lookup';

// Test data
$testCases = [
    [
        'name' => 'Email Search',
        'data' => ['email' => 'maiaki081@gmail.com'],
    ],
    [
        'name' => 'Phone Search',
        'data' => ['phone' => '0312345678'],  // Digits only
    ],
    [
        'name' => 'Reservation Code Search',
        'data' => ['booking_code' => 'RZX2603167E920D'],
    ],
    [
        'name' => 'Non-existent Email',
        'data' => ['email' => 'nonexistent@test.com'],
    ],
    [
        'name' => 'Empty Search',
        'data' => ['booking_code' => '', 'email' => '', 'phone' => ''],
    ],
];

foreach ($testCases as $test) {
    echo "Test: " . $test['name'] . "\n";
    echo "Data: " . json_encode($test['data']) . "\n";

    // Build POST data
    $postData = http_build_query($test['data']);

    // Use cURL to simulate form submission
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $baseUrl);
    curl_setopt($ch, CURLOPT_POST, 1);
    curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, 1);
    curl_setopt($ch, CURLOPT_FOLLOWLOCATION, 1);
    curl_setopt($ch, CURLOPT_TIMEOUT, 10);

    $response = curl_exec($ch);
    $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    curl_close($ch);

    echo "HTTP Status: " . $httpCode . "\n";

    // Check response for key indicators
    if (strpos($response, 'not_found') !== false || strpos($response, '조회 결과가 없습니다') !== false) {
        echo "Result: No results found\n";
    } elseif (strpos($response, 'input_required') !== false || strpos($response, '필수입력') !== false) {
        echo "Result: Input required error\n";
    } elseif (strpos($response, 'reservation_number') !== false || preg_match('/RZX[A-Z0-9]+/', $response)) {
        echo "Result: Results found\n";
        // Extract and show reservation numbers
        if (preg_match_all('/RZX[A-Z0-9]+/', $response, $matches)) {
            echo "  Found " . count(array_unique($matches[0])) . " reservation(s)\n";
            foreach (array_unique($matches[0]) as $num) {
                echo "  - " . $num . "\n";
            }
        }
    } else {
        echo "Result: Unknown (form displayed)\n";
    }

    echo "\n";
}

echo "========================================\n";
echo "Test Complete\n";
echo "========================================\n";
?>
