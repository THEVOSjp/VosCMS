<?php
/**
 * VosCMS License API - 주기적 인증 확인
 * POST /api/license/verify
 *
 * LicenseClient.php에서 24시간 주기로 호출.
 * 라이선스 유효성 + 허용 플러그인 목록 반환.
 *
 * Request:  { key, domain, version, installed_plugins[] }
 * Response: { valid, plan, allowed_plugins[], unauthorized_plugins[], latest_version, cache_ttl }
 */

require_once __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'error' => 'method_not_allowed'], 405);
}

$input = getInput();
$key    = trim($input['key'] ?? '');
$domain = trim($input['domain'] ?? '');
$version = trim($input['version'] ?? '');
$installedPlugins = $input['installed_plugins'] ?? [];

if (!$key || !$domain) {
    respond(['valid' => false, 'error' => 'missing_params'], 400);
}

$domain = normalizeDomain($domain);

// 라이선스 조회
$stmt = $pdo->prepare("SELECT * FROM vcs_licenses WHERE license_key = ?");
$stmt->execute([$key]);
$license = $stmt->fetch();

if (!$license) {
    respond(['valid' => false, 'error' => 'invalid_key', 'message' => 'License key not found'], 403);
}

// 도메인 일치 확인
if ($license['domain'] !== $domain) {
    logAction($pdo, (int)$license['id'], 'verify_rejected', $domain, [
        'reason' => 'domain_mismatch',
        'registered_domain' => $license['domain'],
    ]);
    respond([
        'valid' => false,
        'error' => 'domain_mismatch',
        'message' => 'License key is registered to a different domain',
    ], 403);
}

// 상태 확인
if ($license['status'] !== 'active') {
    logAction($pdo, (int)$license['id'], 'verify_rejected', $domain, [
        'reason' => 'status_' . $license['status'],
    ]);
    respond([
        'valid' => false,
        'error' => 'license_' . $license['status'],
        'message' => 'License is ' . $license['status'],
    ], 403);
}

// 만료 확인
if ($license['expires_at'] && strtotime($license['expires_at']) < time()) {
    $pdo->prepare("UPDATE vcs_licenses SET status = 'suspended' WHERE id = ?")->execute([$license['id']]);
    logAction($pdo, (int)$license['id'], 'expired', $domain);
    respond([
        'valid' => false,
        'error' => 'license_expired',
        'message' => 'License has expired',
        'expired_at' => $license['expires_at'],
    ], 403);
}

// 허용된 플러그인 목록 조회
$pluginStmt = $pdo->prepare(
    "SELECT plugin_id FROM vcs_license_plugins WHERE license_id = ? AND status = 'active'
     AND (expires_at IS NULL OR expires_at > NOW())"
);
$pluginStmt->execute([$license['id']]);
$allowedPlugins = $pluginStmt->fetchAll(PDO::FETCH_COLUMN);

// 미인증 플러그인 감지
$unauthorizedPlugins = [];
if (is_array($installedPlugins)) {
    foreach ($installedPlugins as $pluginId) {
        if (!in_array($pluginId, $allowedPlugins) && $pluginId !== 'vos-marketplace') {
            $unauthorizedPlugins[] = $pluginId;
        }
    }
}

// 버전 업데이트 + last_verified_at 갱신
$pdo->prepare(
    "UPDATE vcs_licenses SET voscms_version = ?, last_verified_at = NOW(), updated_at = NOW() WHERE id = ?"
)->execute([$version, $license['id']]);

logAction($pdo, (int)$license['id'], 'verify', $domain, [
    'version' => $version,
    'installed_plugins' => $installedPlugins,
    'unauthorized' => $unauthorizedPlugins,
]);

// 남은 일수 계산
$daysLeft = null;
if ($license['expires_at']) {
    $daysLeft = max(0, (int) ceil((strtotime($license['expires_at']) - time()) / 86400));
}

// 최신 버전 (향후 vcs_releases 테이블에서 가져올 수 있음)
$latestVersion = $version; // 현재는 동일 반환

respond([
    'valid' => true,
    'plan' => $license['plan'],
    'status' => $license['status'],
    'allowed_plugins' => $allowedPlugins,
    'unauthorized_plugins' => $unauthorizedPlugins,
    'days_left' => $daysLeft,
    'expires_at' => $license['expires_at'],
    'latest_version' => $latestVersion,
    'update_available' => version_compare($latestVersion, $version, '>'),
    'cache_ttl' => 604800, // 7일 (초)
]);
