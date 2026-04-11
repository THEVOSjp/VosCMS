<?php
/**
 * Developer API - 개발자 등록
 * POST /api/developer/register
 */
require_once __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(['success' => false, 'error' => 'method_not_allowed'], 405);

$input = getInput();
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';
$name = trim($input['name'] ?? '');
$company = trim($input['company'] ?? '');
$website = trim($input['website'] ?? '');
$github = trim($input['github'] ?? '');

if (!$email || !$password || !$name) {
    respond(['success' => false, 'error' => 'missing_fields', 'message' => 'email, password, name are required'], 400);
}
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    respond(['success' => false, 'error' => 'invalid_email'], 400);
}
if (strlen($password) < 8) {
    respond(['success' => false, 'error' => 'password_too_short', 'message' => 'Password must be at least 8 characters'], 400);
}

// 중복 확인
$chk = $pdo->prepare("SELECT id FROM vcs_developers WHERE email = ?");
$chk->execute([$email]);
if ($chk->fetch()) {
    respond(['success' => false, 'error' => 'email_exists', 'message' => 'Email already registered'], 409);
}

$hashedPassword = password_hash($password, PASSWORD_BCRYPT);

$stmt = $pdo->prepare(
    "INSERT INTO vcs_developers (email, password, name, company, website, github, status, created_at)
     VALUES (?, ?, ?, ?, ?, ?, 'active', NOW())"
);
$stmt->execute([$email, $hashedPassword, $name, $company ?: null, $website ?: null, $github ?: null]);
$devId = (int) $pdo->lastInsertId();

// 세션 로그인
if (session_status() !== PHP_SESSION_ACTIVE) session_start();
$_SESSION['developer_id'] = $devId;
$_SESSION['developer_name'] = $name;
$_SESSION['developer_email'] = $email;

respond(['success' => true, 'developer_id' => $devId, 'message' => 'Registration successful']);
