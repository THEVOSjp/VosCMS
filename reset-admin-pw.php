<?php
/**
 * 관리자 비밀번호 리셋 스크립트
 * 사용 후 반드시 삭제하세요!
 */

// .env 로드
$envFile = __DIR__ . '/.env';
if (file_exists($envFile)) {
    $lines = file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    foreach ($lines as $line) {
        if (str_starts_with(trim($line), '#')) continue;
        if (strpos($line, '=') !== false) {
            [$key, $value] = explode('=', $line, 2);
            $_ENV[trim($key)] = trim($value);
        }
    }
}

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

try {
    $pdo = new PDO(
        'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx'),
        $_ENV['DB_USERNAME'] ?? 'root',
        $_ENV['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    die('DB 연결 실패: ' . $e->getMessage());
}

// 리셋 대상
$email = 'webmaster@rezlyx.com';
$newPassword = 'Gksaudtjr@1017';
$hashed = password_hash($newPassword, PASSWORD_DEFAULT);

// admin@rezlyx.local → webmaster@rezlyx.com 이메일 변경 + 비밀번호 리셋
$stmt = $pdo->prepare("UPDATE {$prefix}admins SET password = ?, email = ? WHERE email = 'admin@rezlyx.local'");
$stmt->execute([$hashed, $email]);
$adminRows = $stmt->rowCount();

// dongwhahn 계정도 비밀번호 리셋
$pdo->prepare("UPDATE {$prefix}admins SET password = ? WHERE email = 'dongwhahn@gmail.com'")->execute([$hashed]);

// users 테이블
$pdo->prepare("UPDATE {$prefix}users SET password = ?, email = ? WHERE email = 'admin@rezlyx.local'")->execute([$hashed, $email]);
$stmt2 = $pdo->prepare("UPDATE {$prefix}users SET password = ? WHERE email = ?");
$stmt2->execute([$hashed, $email]);
$userRows = $stmt2->rowCount();

echo "<h2>비밀번호 리셋 완료</h2>";
echo "<p>Email: {$email}</p>";
echo "<p>admins 업데이트: {$adminRows}건</p>";
echo "<p>users 업데이트: {$userRows}건</p>";
echo "<p>Hash: {$hashed}</p>";

// 디버그
echo "<hr><h3>admins 테이블:</h3>";
try {
    $all = $pdo->query("SELECT id, email, name, role, status FROM {$prefix}admins")->fetchAll(PDO::FETCH_ASSOC);
    foreach ($all as $a) echo "<p>{$a['id']} | {$a['email']} | {$a['name']} | {$a['role']} | {$a['status']}</p>";
    if (empty($all)) echo "<p>비어있음</p>";
} catch(Exception $e) { echo "<p>에러: " . $e->getMessage() . "</p>"; }

echo "<h3>SHOW TABLES:</h3>";
$tables = $pdo->query("SHOW TABLES LIKE '%admin%'")->fetchAll(PDO::FETCH_COLUMN);
echo "<p>" . implode(', ', $tables) . "</p>";

echo "<hr><p style='color:red;font-weight:bold'>이 파일을 반드시 삭제하세요!</p>";
?>
