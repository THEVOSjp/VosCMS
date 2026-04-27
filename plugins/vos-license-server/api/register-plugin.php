<?php
/**
 * VosCMS License API - 플러그인 구매 등록
 * POST /api/license/register-plugin
 *
 * 마켓플레이스에서 플러그인 구매 완료 시 호출.
 * 해당 도메인의 허용 플러그인 목록에 추가.
 *
 * Request:  { key, domain, plugin_id, order_id }
 * Response: { success, allowed_plugins[] }
 */

require_once __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['success' => false, 'error' => 'method_not_allowed'], 405);
}

$input = getInput();
$key      = trim($input['key'] ?? '');
$domain   = trim($input['domain'] ?? '');
$pluginId = trim($input['plugin_id'] ?? '');
$orderId  = trim($input['order_id'] ?? '');

if (!$key || !$domain || !$pluginId) {
    respond(['success' => false, 'error' => 'missing_params', 'message' => 'key, domain, plugin_id required'], 400);
}

$domain = normalizeDomain($domain);

// 라이선스 조회
$stmt = $pdo->prepare("SELECT * FROM vcs_licenses WHERE license_key = ? AND status = 'active'");
$stmt->execute([$key]);
$license = $stmt->fetch();

if (!$license) {
    respond(['success' => false, 'error' => 'invalid_key', 'message' => 'Active license not found'], 403);
}

// 도메인 확인
if ($license['domain'] !== $domain) {
    respond(['success' => false, 'error' => 'domain_mismatch', 'message' => 'Domain does not match'], 403);
}

// 이미 등록된 플러그인인지 확인
$existing = $pdo->prepare("SELECT id, status FROM vcs_license_plugins WHERE license_id = ? AND plugin_id = ?");
$existing->execute([$license['id'], $pluginId]);
$existingPlugin = $existing->fetch();

if ($existingPlugin) {
    if ($existingPlugin['status'] === 'active') {
        // 이미 활성 → 성공 응답 (멱등성)
        $allPlugins = $pdo->prepare("SELECT plugin_id FROM vcs_license_plugins WHERE license_id = ? AND status = 'active'");
        $allPlugins->execute([$license['id']]);

        respond([
            'success' => true,
            'message' => 'Plugin already registered',
            'allowed_plugins' => $allPlugins->fetchAll(PDO::FETCH_COLUMN),
        ]);
    } else {
        // 만료/취소 → 재활성화
        $pdo->prepare("UPDATE vcs_license_plugins SET status = 'active', order_id = ?, purchased_at = NOW() WHERE id = ?")
            ->execute([$orderId ?: null, $existingPlugin['id']]);
    }
} else {
    // 신규 등록
    $pdo->prepare(
        "INSERT INTO vcs_license_plugins (license_id, plugin_id, order_id, status, purchased_at)
         VALUES (?, ?, ?, 'active', NOW())"
    )->execute([$license['id'], $pluginId, $orderId ?: null]);
}

logAction($pdo, (int)$license['id'], 'plugin_purchase', $domain, [
    'plugin_id' => $pluginId,
    'order_id' => $orderId,
]);

// 전체 허용 플러그인 목록 반환
$allPlugins = $pdo->prepare("SELECT plugin_id FROM vcs_license_plugins WHERE license_id = ? AND status = 'active'");
$allPlugins->execute([$license['id']]);

respond([
    'success' => true,
    'message' => 'Plugin registered successfully',
    'allowed_plugins' => $allPlugins->fetchAll(PDO::FETCH_COLUMN),
]);
