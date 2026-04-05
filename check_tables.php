<?php
$db = new \PDO(
    'mysql:host=127.0.0.1;dbname=rezlyx_salon;charset=utf8mb4',
    'root',
    '',
    [\PDO::ATTR_ERRMODE => \PDO::ERRMODE_EXCEPTION, \PDO::ATTR_DEFAULT_FETCH_MODE => \PDO::FETCH_ASSOC]
);

echo "All tables in rezlyx_salon:\n";
$stmt = $db->query("SHOW TABLES");
$tables = $stmt->fetchAll(\PDO::FETCH_COLUMN);
echo "Total tables: " . count($tables) . "\n\n";

foreach ($tables as $table) {
    echo "- " . $table . "\n";
}

echo "\n\nSearching for booking-related tables:\n";
$bookingTables = array_filter($tables, function($t) {
    return stripos($t, 'book') !== false || stripos($t, 'reserv') !== false;
});

if (empty($bookingTables)) {
    echo "No booking/reservation tables found!\n";
} else {
    foreach ($bookingTables as $table) {
        echo "- " . $table . "\n";
        // Show structure
        $stmt = $db->query("DESCRIBE " . $table);
        $columns = $stmt->fetchAll();
        foreach ($columns as $col) {
            echo "  - " . $col['Field'] . " (" . $col['Type'] . ")\n";
        }
    }
}
?>
