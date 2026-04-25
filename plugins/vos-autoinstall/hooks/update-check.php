<?php
/**
 * 마켓플레이스 업데이트 체크 훅
 * plugin.update.check 훅에서 설치된 아이템의 새 버전 확인
 */
return function () {
    $pm = \RzxLib\Core\Plugin\PluginManager::getInstance();
    if (!$pm) return;

    $autoCheck = $pm->getSetting('vos-autoinstall', 'auto_update_check', '1');
    if ($autoCheck !== '1') return;

    $interval = (int)$pm->getSetting('vos-autoinstall', 'update_check_interval', '86400');
    $lastCheck = (int)$pm->getSetting('vos-autoinstall', '_last_update_check', '0');

    if (time() - $lastCheck < $interval) return;

    // 업데이트 체크 시간 기록
    $pm->setSetting('vos-autoinstall', '_last_update_check', (string)time());

    // 설치��� 플러그인 버전 수집
    $installed = $pm->getInstalled();
    $items = [];
    foreach ($installed as $p) {
        $items[] = [
            'id' => $p['plugin_id'],
            'version' => $p['version'],
        ];
    }

    if (empty($items)) return;

    // CatalogClient로 업데이트 확인
    $apiUrl = $pm->getSetting('vos-autoinstall', 'market_api_url', 'https://market.21ces.com/api/market');

    try {
        require_once __DIR__ . '/../src/CatalogClient.php';
        $client = new \VosMarketplace\CatalogClient($apiUrl);
        $updates = $client->checkUpdates($items);

        if (!empty($updates)) {
            $pm->setSetting('vos-autoinstall', '_available_updates', json_encode($updates));
        }
    } catch (\Throwable $e) {
        // 업데이트 체크 실패는 무시
    }
};
