<?php

declare(strict_types=1);

namespace VosMarketplace;

use VosMarketplace\Models\MarketplaceItem;
use VosMarketplace\Models\ItemVersion;
use VosMarketplace\Models\MarketplaceCategory;

/**
 * 원격 마켓플레이스 API 클라이언트
 * marketplace.voscms.com에서 카탈로그를 동기화
 */
class CatalogClient
{
    private string $apiUrl;
    private int $timeout;

    public function __construct(string $apiUrl = 'https://market.21ces.com/api/market', int $timeout = 30)
    {
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->timeout = $timeout;
    }

    /**
     * 원격 카탈로그 동기화
     */
    public function syncCatalog(): array
    {
        $response = $this->request('/catalog', ['limit' => 50]);
        if (!$response || empty($response['data'])) {
            return ['success' => false, 'message' => 'Failed to fetch catalog', 'synced' => 0];
        }

        $synced = 0;
        foreach ($response['data'] as $remoteItem) {
            if ($this->upsertItem($remoteItem)) {
                $synced++;
            }
        }

        // 카테고리 동기화
        if (!empty($response['categories'])) {
            foreach ($response['categories'] as $remoteCat) {
                $this->upsertCategory($remoteCat);
            }
        }

        return ['success' => true, 'synced' => $synced];
    }

    /**
     * 원격 아이템 단일 조회
     */
    public function fetchItem(string $slug): ?array
    {
        $response = $this->request('/item', ['slug' => $slug]);
        return $response['data'] ?? null;
    }

    /**
     * 업데이트 확인
     *   $installedItems: ['slug' => 'current_version', ...]
     */
    public function checkUpdates(array $installedItems): array
    {
        $parts = [];
        foreach ($installedItems as $slug => $ver) {
            $parts[] = $slug . ':' . $ver;
        }
        $response = $this->request('/updates/check', [
            'items' => implode(',', $parts),
        ]);

        return $response['updates'] ?? $response['data'] ?? [];
    }

    /**
     * 아이템 upsert (remote_id 기준)
     */
    private function upsertItem(array $data): bool
    {
        if (empty($data['id'])) return false;

        $existing = MarketplaceItem::findByRemoteId((string) $data['id']);

        $item = $existing ?: new MarketplaceItem();
        $item->remote_id = (string) $data['id'];
        $item->slug = $data['slug'] ?? $item->slug;
        $item->type = $data['type'] ?? $item->type;
        $item->name = $data['name'] ?? $item->name;
        $item->description = $data['description'] ?? $item->description;
        $item->short_description = $data['short_description'] ?? $item->short_description;
        $item->author_name = $data['author_name'] ?? $item->author_name;
        $item->author_url = $data['author_url'] ?? $item->author_url;
        $item->icon = $data['icon'] ?? $item->icon;
        $item->banner_image = $data['banner_image'] ?? $item->banner_image;
        $item->screenshots = $data['screenshots'] ?? $item->screenshots;
        $item->tags = $data['tags'] ?? $item->tags;
        $item->price = (float) ($data['price'] ?? $item->price);
        $item->currency = $data['currency'] ?? $item->currency;
        $item->sale_price = isset($data['sale_price']) ? (float) $data['sale_price'] : $item->sale_price;
        $item->sale_ends_at = $data['sale_ends_at'] ?? $item->sale_ends_at;
        $item->latest_version = $data['latest_version'] ?? $item->latest_version;
        $item->min_voscms_version = $data['min_voscms_version'] ?? $item->min_voscms_version;
        $item->min_php_version = $data['min_php_version'] ?? $item->min_php_version;
        $item->requires_plugins = $data['requires_plugins'] ?? $item->requires_plugins;
        $item->download_count = (int) ($data['download_count'] ?? $item->download_count);
        $item->rating_avg = (float) ($data['rating_avg'] ?? $item->rating_avg);
        $item->rating_count = (int) ($data['rating_count'] ?? $item->rating_count);
        $item->is_featured = (bool) ($data['is_featured'] ?? $item->is_featured);
        $item->is_verified = (bool) ($data['is_verified'] ?? $item->is_verified);
        $item->status = $data['status'] ?? $item->status;

        return $item->save();
    }

    /**
     * 카테고리 upsert
     */
    private function upsertCategory(array $data): bool
    {
        if (empty($data['slug'])) return false;

        $existing = MarketplaceCategory::findBySlug($data['slug']);
        if ($existing) return true;

        $pdo = \RzxLib\Core\Database\Connection::getInstance()->getPdo();
        $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

        $stmt = $pdo->prepare(
            "INSERT INTO {$prefix}mp_categories (slug, name, icon, sort_order)
             VALUES (?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE name = VALUES(name)"
        );

        return $stmt->execute([
            $data['slug'],
            json_encode($data['name'] ?? [], JSON_UNESCAPED_UNICODE),
            $data['icon'] ?? null,
            (int) ($data['sort_order'] ?? 0),
        ]);
    }

    /**
     * HTTP 요청
     */
    private function request(string $endpoint, array $params = []): ?array
    {
        $url = $this->apiUrl . $endpoint;
        if (!empty($params)) {
            $url .= '?' . http_build_query($params);
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => $this->timeout,
            CURLOPT_CONNECTTIMEOUT => 10,
            CURLOPT_HTTPHEADER => [
                'Accept: application/json',
                'User-Agent: VosCMS/' . ($_ENV['APP_VERSION'] ?? '2.0'),
            ],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($httpCode !== 200 || !$response) {
            return null;
        }

        $data = json_decode($response, true);
        return is_array($data) ? $data : null;
    }
}
