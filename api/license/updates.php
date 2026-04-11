<?php
/**
 * VosCMS License API - 플러그인 업데이트 확인
 * POST /api/license/updates
 *
 * 설치된 플러그인 목록과 버전을 보내면 업데이트 가능한 항목 반환.
 *
 * Request:  { items: [{id: "vos-salon", version: "1.0.0"}, ...] }
 * Response: { updates: [{slug, current, latest, changelog}] }
 */
require_once __DIR__ . '/_init.php';

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    respond(['updates' => []], 200);
}

$input = getInput();
$items = $input['items'] ?? [];

if (empty($items) || !is_array($items)) {
    respond(['updates' => []], 200);
}

$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$updates = [];

foreach ($items as $installed) {
    $pluginId = $installed['id'] ?? '';
    $currentVersion = $installed['version'] ?? '0.0.0';

    if (!$pluginId) continue;

    // slug로 마켓플레이스 아이템 찾기
    $stmt = $pdo->prepare("SELECT id, slug, name, latest_version FROM {$prefix}mp_items WHERE slug = ? AND status = 'active'");
    $stmt->execute([$pluginId]);
    $mpItem = $stmt->fetch();

    if (!$mpItem) continue;

    $latestVersion = $mpItem['latest_version'] ?? '0.0.0';

    if (version_compare($latestVersion, $currentVersion, '>')) {
        // 최신 버전 정보 가져오기
        $vStmt = $pdo->prepare(
            "SELECT version, changelog, file_size, released_at FROM {$prefix}mp_item_versions
             WHERE item_id = ? AND status = 'active' ORDER BY released_at DESC LIMIT 1"
        );
        $vStmt->execute([$mpItem['id']]);
        $vInfo = $vStmt->fetch();

        $name = json_decode($mpItem['name'] ?? '{}', true);

        $updates[] = [
            'slug' => $mpItem['slug'],
            'name' => $name,
            'current_version' => $currentVersion,
            'latest_version' => $latestVersion,
            'changelog' => $vInfo['changelog'] ?? null,
            'file_size' => $vInfo['file_size'] ?? null,
            'released_at' => $vInfo['released_at'] ?? null,
        ];
    }
}

respond(['updates' => $updates]);
