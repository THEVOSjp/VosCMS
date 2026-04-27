<?php
/**
 * Developer API - 로그인
 * POST /api/developer/login
 */
require_once __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') respond(['success' => false, 'error' => 'method_not_allowed'], 405);

$input = getInput();
$email = trim($input['email'] ?? '');
$password = $input['password'] ?? '';

if (!$email || !$password) {
    respond(['success' => false, 'error' => 'missing_fields'], 400);
}

$stmt = $pdo->prepare("SELECT * FROM vcs_developers WHERE email = ?");
$stmt->execute([$email]);
$dev = $stmt->fetch();

if (!$dev || !password_verify($password, $dev['password'])) {
    respond(['success' => false, 'error' => 'invalid_credentials', 'message' => 'Invalid email or password'], 401);
}

if ($dev['status'] === 'banned') {
    respond(['success' => false, 'error' => 'account_banned', 'message' => 'Your account has been banned'], 403);
}
if ($dev['status'] === 'suspended') {
    respond(['success' => false, 'error' => 'account_suspended', 'message' => 'Your account is suspended'], 403);
}

if (session_status() !== PHP_SESSION_ACTIVE) session_start();
session_regenerate_id(true);
$_SESSION['developer_id'] = (int) $dev['id'];
$_SESSION['developer_name'] = $dev['name'];
$_SESSION['developer_email'] = $dev['email'];
$_SESSION['developer_type'] = $dev['type'];

respond(['success' => true, 'developer' => [
    'id' => (int) $dev['id'],
    'name' => $dev['name'],
    'email' => $dev['email'],
    'type' => $dev['type'],
    'status' => $dev['status'],
]]);
