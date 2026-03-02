<?php
/**
 * RezlyX Admin Settings - Initialization
 * Common setup for all settings pages (no HTML output)
 */

// Database connection
try {
    $pdo = new PDO(
        'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx'),
        $_ENV['DB_USERNAME'] ?? 'root',
        $_ENV['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('데이터베이스 연결 실패: ' . $e->getMessage());
}

// Load current settings
$settings = [];
try {
    $stmt = $pdo->query("SELECT `key`, `value` FROM rzx_settings");
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $settings[$row['key']] = $row['value'];
    }
} catch (PDOException $e) {
    // Ignore errors, use defaults
}

// Base URLs for navigation
$baseUrl = $config['app_url'] ?? '';
$adminPath = $config['admin_path'] ?? $_ENV['ADMIN_PATH'] ?? 'admin';
$adminUrl = $baseUrl . '/' . $adminPath;

// Message variables
$message = '';
$messageType = '';
