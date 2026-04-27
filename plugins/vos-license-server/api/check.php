<?php
/**
 * VosCMS License API - 라이선스 상태 조회
 * GET /api/license/check?key=RZX-XXXX-XXXX-XXXX
 *
 * 관리자 설정 페이지에서 현재 라이선스 상태를 확인할 때 사용.
 *
 * Response: { valid, key, domain, plan, status, plugins[], registered_at, expires_at }
 */

require_once __DIR__ . '/_init.php';

$key = trim($_GET['key'] ?? '');

if (!$key) {
    respond(['success' => false, 'error' => 'missing_key'], 400);
}

// 라이선스 조회
$stmt = $pdo->prepare("SELECT * FROM vcs_licenses WHERE license_key = ?");
$stmt->execute([$key]);
$license = $stmt->fetch();

if (!$license) {
    respond(['valid' => false, 'error' => 'not_found', 'message' => 'License key not found'], 404);
}

// 허용 플러그인
$pluginStmt = $pdo->prepare(
    "SELECT plugin_id, status, purchased_at, expires_at FROM vcs_license_plugins WHERE license_id = ? ORDER BY purchased_at"
);
$pluginStmt->execute([$license['id']]);
$plugins = $pluginStmt->fetchAll();

// 남은 일수
$daysLeft = null;
if ($license['expires_at']) {
    $daysLeft = max(0, (int) ceil((strtotime($license['expires_at']) - time()) / 86400));
}

respond([
    'valid' => $license['status'] === 'active',
    'key' => $license['license_key'],
    'domain' => $license['domain'],
    'plan' => $license['plan'],
    'status' => $license['status'],
    'plugins' => $plugins,
    'days_left' => $daysLeft,
    'registered_at' => $license['registered_at'],
    'expires_at' => $license['expires_at'],
    'last_verified_at' => $license['last_verified_at'],
    'voscms_version' => $license['voscms_version'],
]);
