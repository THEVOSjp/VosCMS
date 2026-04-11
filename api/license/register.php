<?php
/**
 * VosCMS License API - 신규 설치 등록
 * POST /api/license/register
 *
 * install.php Step 5에서 호출.
 * 라이선스 키 + 도메인을 등록하고 free 플랜으로 시작.
 *
 * Request:  { key, domain, version, php_version, server_ip }
 * Response: { success, plan, registered_at }
 */

require_once __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'error' => 'method_not_allowed'], 405);
}

$input = getInput();
$key    = trim($input['key'] ?? '');
$domain = trim($input['domain'] ?? '');
$version = trim($input['version'] ?? '');
$phpVersion = trim($input['php_version'] ?? '');
$serverIp = trim($input['server_ip'] ?? $_SERVER['REMOTE_ADDR'] ?? '');

// 필수 파라미터 검증
if (!$key || !$domain) {
    respond(['success' => false, 'error' => 'missing_params', 'message' => 'key and domain are required'], 400);
}

// 키 형식 검증
if (!isValidKeyFormat($key)) {
    respond(['success' => false, 'error' => 'invalid_key_format', 'message' => 'Invalid license key format'], 400);
}

$domain = normalizeDomain($domain);
$hash = domainHash($domain);

// 이미 등록된 키인지 확인
$stmt = $pdo->prepare("SELECT id, domain, status FROM vcs_licenses WHERE license_key = ?");
$stmt->execute([$key]);
$existing = $stmt->fetch();

if ($existing) {
    if ($existing['domain'] === $domain) {
        // 같은 도메인으로 재등록 (재설치) → 상태 업데이트
        $pdo->prepare(
            "UPDATE vcs_licenses SET voscms_version = ?, php_version = ?, server_ip = ?,
             last_verified_at = NOW(), updated_at = NOW() WHERE id = ?"
        )->execute([$version, $phpVersion, $serverIp, $existing['id']]);

        logAction($pdo, (int)$existing['id'], 'reinstall', $domain, [
            'version' => $version, 'php' => $phpVersion,
        ]);

        respond([
            'success' => true,
            'plan' => 'free',
            'registered_at' => date('c'),
            'message' => 'License re-registered (same domain)',
        ]);
    } else {
        // 다른 도메인 → 거부 (도메인 변경은 본사만)
        logAction($pdo, (int)$existing['id'], 'register_rejected', $domain, [
            'reason' => 'domain_mismatch',
            'registered_domain' => $existing['domain'],
        ]);
        respond([
            'success' => false,
            'error' => 'key_already_registered',
            'message' => 'This license key is already registered to another domain',
        ], 409);
    }
}

// 동일 도메인이 이미 다른 키로 등록되어 있는지 확인
$domainCheck = $pdo->prepare("SELECT id, license_key FROM vcs_licenses WHERE domain = ? AND status = 'active'");
$domainCheck->execute([$domain]);
$domainExisting = $domainCheck->fetch();

if ($domainExisting) {
    logAction($pdo, (int)$domainExisting['id'], 'register_rejected', $domain, [
        'reason' => 'domain_exists',
        'existing_key' => substr($domainExisting['license_key'], 0, 8) . '...',
    ]);
    respond([
        'success' => false,
        'error' => 'domain_exists',
        'message' => 'This domain is already registered with another license key',
    ], 409);
}

// 신규 등록
$stmt = $pdo->prepare(
    "INSERT INTO vcs_licenses (license_key, domain, domain_hash, plan, status, voscms_version, php_version, server_ip, last_verified_at, registered_at)
     VALUES (?, ?, ?, 'free', 'active', ?, ?, ?, NOW(), NOW())"
);
$stmt->execute([$key, $domain, $hash, $version, $phpVersion, $serverIp]);
$licenseId = (int) $pdo->lastInsertId();

logAction($pdo, $licenseId, 'register', $domain, [
    'version' => $version,
    'php' => $phpVersion,
    'ip' => $serverIp,
]);

respond([
    'success' => true,
    'plan' => 'free',
    'registered_at' => date('c'),
    'message' => 'License registered successfully',
]);
