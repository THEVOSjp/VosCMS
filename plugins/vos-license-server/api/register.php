<?php
/**
 * VosCMS License API - 신규 설치 등록
 * POST /api/license/register
 *
 * install.php에서 도메인 정보만 전송하면,
 * 서버에서 라이선스 키를 생성하고 DB에 저장한 뒤 키를 반환.
 *
 * Request:  { domain, version, php_version, server_ip }
 * Response: { success, key, plan, registered_at }
 */

require_once __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'error' => 'method_not_allowed'], 405);
}

$input = getInput();
$domain = trim($input['domain'] ?? '');
$version = trim($input['version'] ?? '');
$phpVersion = trim($input['php_version'] ?? '');
$serverIp = trim($input['server_ip'] ?? $_SERVER['REMOTE_ADDR'] ?? '');

// 필수 파라미터 검증
if (!$domain) {
    respond(['success' => false, 'error' => 'missing_params', 'message' => 'domain is required'], 400);
}

$domain = normalizeDomain($domain);
$hash = domainHash($domain);

// 동일 도메인이 이미 등록되어 있는지 확인
$domainCheck = $pdo->prepare("SELECT id, license_key, status FROM vcs_licenses WHERE domain = ?");
$domainCheck->execute([$domain]);
$existing = $domainCheck->fetch();

if ($existing) {
    if ($existing['status'] === 'active') {
        // 같은 도메인으로 재설치 → 기존 키 반환 + 정보 업데이트
        $pdo->prepare(
            "UPDATE vcs_licenses SET voscms_version = ?, php_version = ?, server_ip = ?,
             last_verified_at = NOW(), updated_at = NOW() WHERE id = ?"
        )->execute([$version, $phpVersion, $serverIp, $existing['id']]);

        logAction($pdo, (int)$existing['id'], 'reinstall', $domain, [
            'version' => $version, 'php' => $phpVersion,
        ]);

        respond([
            'success' => true,
            'key' => $existing['license_key'],
            'plan' => 'free',
            'registered_at' => date('c'),
            'message' => 'License restored (same domain reinstall)',
        ]);
    } else {
        // 정지/취소된 도메인
        logAction($pdo, (int)$existing['id'], 'register_rejected', $domain, [
            'reason' => 'domain_' . $existing['status'],
        ]);
        respond([
            'success' => false,
            'error' => 'domain_' . $existing['status'],
            'message' => 'This domain is ' . $existing['status'] . '. Please contact support.',
        ], 403);
    }
}

// ─── 서버에서 라이선스 키 생성 (중복 방지) ───
$key = generateUniqueKey($pdo);

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
    'key' => $key,
    'plan' => 'free',
    'registered_at' => date('c'),
    'message' => 'License registered successfully',
]);

/**
 * 중복 없는 라이선스 키 생성
 * RZX-XXXX-XXXX-XXXX (혼동 문자 제외: 0,O,1,I)
 */
function generateUniqueKey(PDO $pdo, int $maxRetries = 10): string
{
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $len = strlen($chars);

    for ($attempt = 0; $attempt < $maxRetries; $attempt++) {
        $segments = [];
        for ($i = 0; $i < 3; $i++) {
            $seg = '';
            for ($j = 0; $j < 4; $j++) {
                $seg .= $chars[random_int(0, $len - 1)];
            }
            $segments[] = $seg;
        }
        $key = 'RZX-' . implode('-', $segments);

        // DB 중복 체크
        $chk = $pdo->prepare("SELECT id FROM vcs_licenses WHERE license_key = ?");
        $chk->execute([$key]);
        if (!$chk->fetch()) {
            return $key;
        }
    }

    // 극히 드문 경우: 타임스탬프 혼합으로 유니크 보장
    return 'RZX-' . strtoupper(substr(base_convert(time(), 10, 36), -4))
         . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4))
         . '-' . strtoupper(substr(bin2hex(random_bytes(2)), 0, 4));
}
