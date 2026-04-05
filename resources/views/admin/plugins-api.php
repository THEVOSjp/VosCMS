<?php
/**
 * VosCMS - 플러그인 관리 API
 * POST /admin/plugins/api
 */
header('Content-Type: application/json; charset=utf-8');

$input = json_decode(file_get_contents('php://input'), true);
if (!$input || empty($input['action'])) {
    echo json_encode(['success' => false, 'message' => 'Invalid request']);
    exit;
}

$pm = $pluginManager ?? \RzxLib\Core\Plugin\PluginManager::getInstance();
if (!$pm) {
    echo json_encode(['success' => false, 'message' => 'Plugin system not available']);
    exit;
}

$action = $input['action'];
$pluginId = $input['plugin_id'] ?? '';

if (!$pluginId) {
    echo json_encode(['success' => false, 'message' => 'Plugin ID required']);
    exit;
}

try {
    $result = match($action) {
        'install' => $pm->install($pluginId),
        'uninstall' => $pm->uninstall($pluginId),
        'activate' => $pm->activate($pluginId),
        'deactivate' => $pm->deactivate($pluginId),
        default => ['success' => false, 'message' => 'Unknown action: ' . $action],
    };
    echo json_encode($result);
} catch (\Exception $e) {
    echo json_encode(['success' => false, 'message' => $e->getMessage()]);
}
