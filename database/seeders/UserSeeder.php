<?php
/**
 * User Seeder - 테스트용 사용자 생성
 *
 * 실행: php database/seeders/UserSeeder.php
 */

// 환경 설정 로드
$basePath = dirname(dirname(__DIR__));
require $basePath . '/vendor/autoload.php';

$dotenv = Dotenv\Dotenv::createImmutable($basePath);
$dotenv->load();

// DB 접속
try {
    $pdo = new PDO(
        'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx') . ';charset=utf8mb4',
        $_ENV['DB_USERNAME'] ?? 'root',
        $_ENV['DB_PASSWORD'] ?? '',
        [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]
    );

    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
    $table = $prefix . 'users';

    echo "=== RezlyX User Seeder ===\n\n";

    // 테스트 사용자 목록
    $users = [
        [
            'name' => '테스트 사용자',
            'email' => 'test@example.com',
            'password' => 'password123',
            'phone' => '010-1234-5678',
        ],
        [
            'name' => 'Test User',
            'email' => 'user@rezlyx.com',
            'password' => 'test1234',
            'phone' => '010-9876-5432',
        ],
        [
            'name' => '管理テスト',
            'email' => 'demo@rezlyx.com',
            'password' => 'demo1234',
            'phone' => null,
        ],
    ];

    foreach ($users as $userData) {
        // 이미 존재하는지 확인
        $checkStmt = $pdo->prepare("SELECT id FROM {$table} WHERE email = ?");
        $checkStmt->execute([$userData['email']]);
        $exists = $checkStmt->fetch();

        if ($exists) {
            echo "[SKIP] {$userData['email']} - 이미 존재함\n";
            continue;
        }

        // UUID 생성
        $userId = sprintf(
            '%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
            mt_rand(0, 0xffff), mt_rand(0, 0xffff),
            mt_rand(0, 0xffff),
            mt_rand(0, 0x0fff) | 0x4000,
            mt_rand(0, 0x3fff) | 0x8000,
            mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
        );

        // 비밀번호 해시
        $hashedPassword = password_hash($userData['password'], PASSWORD_DEFAULT, ['cost' => 12]);

        // 사용자 생성
        $insertStmt = $pdo->prepare("
            INSERT INTO {$table} (id, name, email, password, phone, status, email_verified_at, created_at, updated_at)
            VALUES (?, ?, ?, ?, ?, 'active', NOW(), NOW(), NOW())
        ");
        $insertStmt->execute([
            $userId,
            $userData['name'],
            $userData['email'],
            $hashedPassword,
            $userData['phone'],
        ]);

        echo "[OK] {$userData['email']} 생성됨 (비밀번호: {$userData['password']})\n";
    }

    echo "\n=== 완료 ===\n";
    echo "\n테스트 계정으로 로그인해보세요:\n";
    echo "- Email: test@example.com\n";
    echo "- Password: password123\n";

} catch (PDOException $e) {
    echo "[ERROR] " . $e->getMessage() . "\n";
    exit(1);
}
