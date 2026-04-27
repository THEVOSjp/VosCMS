<?php
/**
 * VosCMS License Client
 *
 * 고객 사이트에서 라이선스 서버(voscms.com)와 통신.
 * 24시간 주기로 인증 확인, 7일 캐시, 도메인 해시 검증.
 *
 * ionCube 암호화 대상 파일.
 */

namespace RzxLib\Core\License;

class LicenseClient
{
    /** 도메인 해시 비밀키 (ionCube로 보호) */
    private const HASH_SECRET = 'VosCMS_2026_LicenseServer_!@#SecretKey';

    /** 캐시 파일 경로 */
    private string $cacheFile;

    /** 라이선스 서버 URL (라이선스 인증용) */
    private string $serverUrl;

    /** 마켓 서버 URL (아이템 sync, resolve-keys용) */
    private string $marketUrl;

    /** 라이선스 키 */
    private string $licenseKey;

    /** 현재 도메인 */
    private string $domain;

    /** 인증 확인 주기 (초) — 기본 24시간 */
    private int $checkInterval = 86400;

    /** 캐시 유효 기간 (초) — 기본 7일 */
    private int $cacheTtl = 604800;

    public function __construct()
    {
        $this->cacheFile = ($_ENV['BASE_PATH'] ?? (defined('BASE_PATH') ? BASE_PATH : __DIR__ . '/../../..')) . '/storage/.license_cache';
        $this->serverUrl = rtrim($_ENV['LICENSE_SERVER'] ?? 'https://voscms.com/api', '/');
        $this->marketUrl = rtrim($_ENV['MARKET_SERVER'] ?? 'https://market.21ces.com/api', '/');
        $this->licenseKey = $_ENV['LICENSE_KEY'] ?? '';
        $this->domain = $this->detectDomain();
    }

    /**
     * 라이선스 상태 확인 (메인 메서드)
     * 관리자 접속 시 호출.
     */
    public function check(): LicenseStatus
    {
        // 라이선스 키 미설정 → 자동 Free 라이선스 등록 시도
        if (empty($this->licenseKey)) {
            try {
                $result = $this->register($this->domain, $_ENV['APP_VERSION'] ?? '2.0.0', PHP_VERSION);
                if (!empty($result['success']) && !empty($result['key'])) {
                    $this->licenseKey = $result['key'];
                    $_ENV['LICENSE_KEY'] = $result['key'];
                    $this->saveKeyToEnv($result['key']);
                }
            } catch (\Throwable $e) {
                // silent fail
            }

            if (empty($this->licenseKey)) {
                return LicenseStatus::unregistered();
            }
        }

        // 1. 캐시 확인
        $cache = $this->loadCache();
        if ($cache && $this->isCacheValid($cache)) {
            // 24시간 이내 확인 → 캐시 사용
            if ($this->isWithinCheckInterval($cache)) {
                return LicenseStatus::fromCache($cache);
            }

            // 24시간 경과 → 서버 확인 시도
            $serverResult = $this->verifyWithServer();
            if ($serverResult !== null) {
                return $serverResult;
            }

            // 서버 연결 실패 → 캐시 기간 내면 OK (경고 포함)
            return LicenseStatus::fromCache($cache, 'server_unreachable');
        }

        // 2. 캐시 없음/만료 → 서버 확인 필수
        $serverResult = $this->verifyWithServer();
        if ($serverResult !== null) {
            return $serverResult;
        }

        // 3. 서버도 캐시도 불가 → 미인증
        return LicenseStatus::unverified();
    }

    /**
     * 라이선스 서버에 인증 확인
     */
    private function verifyWithServer(): ?LicenseStatus
    {
        $installedPlugins = $this->getInstalledPlugins();

        $response = $this->apiCall('/license/verify', [
            'key' => $this->licenseKey,
            'domain' => $this->domain,
            'version' => $_ENV['APP_VERSION'] ?? '2.0.0',
            'installed_plugins' => $installedPlugins,
        ]);

        if ($response === null) {
            return null; // 네트워크 실패
        }

        if (!empty($response['valid'])) {
            $this->saveCache($response);
            // sync 먼저: 마켓 서버에 라이선스 등록/캐시 → 그 후 resolve-keys 성공
            $this->syncInstalledItems();
            $this->resolveProductKeys();
            return LicenseStatus::active($response);
        }

        // 라이선스 무효
        $this->clearCache();
        return LicenseStatus::invalid($response['error'] ?? 'unknown', $response['message'] ?? '');
    }

    /**
     * 설치 시 라이선스 등록
     * 서버에 도메인 정보를 전송하면 서버가 키를 생성하여 반환
     */
    public function register(string $domain, string $version = '', string $phpVersion = ''): array
    {
        $response = $this->apiCall('/license/register', [
            'domain' => self::normalizeDomain($domain),
            'version' => $version ?: ($_ENV['APP_VERSION'] ?? '2.0.0'),
            'php_version' => $phpVersion ?: PHP_VERSION,
            'server_ip' => $_SERVER['SERVER_ADDR'] ?? '',
        ]);

        return $response ?? ['success' => false, 'error' => 'network_error', 'message' => 'Could not reach license server'];
    }

    /**
     * 플러그인 구매 등록
     */
    public function registerPlugin(string $pluginId, string $orderId = ''): array
    {
        $response = $this->apiCall('/license/register-plugin', [
            'key' => $this->licenseKey,
            'domain' => $this->domain,
            'plugin_id' => $pluginId,
            'order_id' => $orderId,
        ]);

        if ($response && !empty($response['success'])) {
            // 캐시 갱신 (allowed_plugins 업데이트)
            $cache = $this->loadCache();
            if ($cache) {
                $cache['allowed_plugins'] = $response['allowed_plugins'] ?? $cache['allowed_plugins'] ?? [];
                $this->saveCacheData($cache);
            }
        }

        return $response ?? ['success' => false, 'error' => 'network_error'];
    }

    // ─── .env 저장 ───

    private function saveKeyToEnv(string $key): void
    {
        $basePath = defined('BASE_PATH') ? BASE_PATH : ($_ENV['BASE_PATH'] ?? __DIR__ . '/../../..');
        $envFile = $basePath . '/.env';
        if (!file_exists($envFile) || !is_writable($envFile)) return;

        $envContent = file_get_contents($envFile);
        // 빈 값(LICENSE_KEY=)도 덮어쓰기, 이미 실제 키가 있으면 스킵
        if (preg_match('/^LICENSE_KEY=\S+/m', $envContent)) return;

        $domain = self::normalizeDomain($_ENV['APP_URL'] ?? $_SERVER['HTTP_HOST'] ?? $this->domain);
        $serverUrl = $_ENV['LICENSE_SERVER'] ?? 'https://voscms.com/api';
        // 빈 LICENSE_KEY= 라인이 있으면 교체, 없으면 추가
        if (preg_match('/^LICENSE_KEY=$/m', $envContent)) {
            $envContent = preg_replace('/^LICENSE_KEY=$/m', "LICENSE_KEY={$key}", $envContent);
        } else {
            $envContent .= "\n\nLICENSE_KEY={$key}\nLICENSE_DOMAIN={$domain}\nLICENSE_REGISTERED_AT=" . date('c') . "\nLICENSE_SERVER={$serverUrl}\n";
        }
        file_put_contents($envFile, $envContent, LOCK_EX);

        $_ENV['LICENSE_DOMAIN'] = $domain;
        $_ENV['LICENSE_REGISTERED_AT'] = date('c');
        $_ENV['LICENSE_SERVER'] = $serverUrl;
    }

    // ─── 캐시 관리 ───

    private function loadCache(): ?array
    {
        if (!file_exists($this->cacheFile)) return null;

        $raw = @file_get_contents($this->cacheFile);
        if (!$raw) return null;

        $data = json_decode($raw, true);
        if (!is_array($data)) return null;

        // 체크섬 검증
        $checksum = $data['_checksum'] ?? '';
        unset($data['_checksum']);
        if ($checksum !== $this->calculateChecksum($data)) {
            @unlink($this->cacheFile);
            return null;
        }

        // 도메인 해시 검증 (다른 도메인에서 캐시 복사 방지)
        if (($data['domain_hash'] ?? '') !== $this->domainHash($this->domain)) {
            @unlink($this->cacheFile);
            return null;
        }

        return $data;
    }

    private function saveCache(array $response): void
    {
        $data = [
            'license_key' => $this->licenseKey,
            'domain_hash' => $this->domainHash($this->domain),
            'plan' => $response['plan'] ?? 'free',
            'allowed_plugins' => $response['allowed_plugins'] ?? [],
            'unauthorized_plugins' => $response['unauthorized_plugins'] ?? [],
            'verified_at' => date('c'),
            'cache_expires_at' => date('c', time() + $this->cacheTtl),
            'latest_version' => $response['latest_version'] ?? '',
            'update_available' => $response['update_available'] ?? false,
            'expires_at' => $response['expires_at'] ?? null,
            'days_left' => $response['days_left'] ?? null,
        ];
        $this->saveCacheData($data);
    }

    private function saveCacheData(array $data): void
    {
        $data['_checksum'] = $this->calculateChecksum($data);
        @file_put_contents($this->cacheFile, json_encode($data, JSON_UNESCAPED_UNICODE), LOCK_EX);
    }

    private function clearCache(): void
    {
        @unlink($this->cacheFile);
    }

    private function isCacheValid(array $cache): bool
    {
        $expiresAt = $cache['cache_expires_at'] ?? null;
        if (!$expiresAt) return false;
        return strtotime($expiresAt) > time();
    }

    private function isWithinCheckInterval(array $cache): bool
    {
        $verifiedAt = $cache['verified_at'] ?? null;
        if (!$verifiedAt) return false;
        return (time() - strtotime($verifiedAt)) < $this->checkInterval;
    }

    private function calculateChecksum(array $data): string
    {
        unset($data['_checksum']);
        return hash('sha256', json_encode($data) . self::HASH_SECRET);
    }

    private function domainHash(string $domain): string
    {
        return hash('sha256', self::normalizeDomain($domain) . self::HASH_SECRET);
    }

    // ─── 유틸리티 ───

    private function detectDomain(): string
    {
        // .env의 LICENSE_DOMAIN 우선
        if (!empty($_ENV['LICENSE_DOMAIN'])) {
            return self::normalizeDomain($_ENV['LICENSE_DOMAIN']);
        }
        // APP_URL에서 추출
        if (!empty($_ENV['APP_URL'])) {
            return self::normalizeDomain($_ENV['APP_URL']);
        }
        // HTTP_HOST에서
        return self::normalizeDomain($_SERVER['HTTP_HOST'] ?? 'unknown');
    }

    public static function normalizeDomain(string $domain): string
    {
        $domain = strtolower(trim($domain));
        $domain = preg_replace('#^https?://#', '', $domain);
        $domain = preg_replace('#^www\.#', '', $domain);
        $domain = preg_replace('#:\d+$#', '', $domain);
        return rtrim($domain, '/');
    }

    private function getInstalledPlugins(): array
    {
        $pluginsDir = (defined('BASE_PATH') ? BASE_PATH : __DIR__ . '/../../..') . '/plugins';
        $plugins = [];
        if (is_dir($pluginsDir)) {
            foreach (glob($pluginsDir . '/*/plugin.json') as $jsonPath) {
                $data = json_decode(file_get_contents($jsonPath), true);
                if ($data && !empty($data['id'])) {
                    $plugins[] = $data['id'];
                }
            }
        }
        return $plugins;
    }

    /**
     * 설치된 모든 아이템 수집 (product_key 유무 관계없이)
     * product_key 없는 구형 아이템은 slug만 전송 → 마켓이 조회 후 반환
     * 반환 형식: [['slug'=>..., 'version'=>..., 'product_key'=>...(있을 때), 'manifest_path'=>...], ...]
     */
    private function collectInstalledItems(): array
    {
        $basePath = defined('BASE_PATH') ? BASE_PATH : __DIR__ . '/../../..';
        $items = [];

        // VosCMS 설치 범주: 플러그인, 위젯, 레이아웃(skins/layouts 하위)
        // purchases.php와 동일한 스캔 기준
        $scanTargets = [
            ['glob' => $basePath . '/plugins/*/plugin.json',        'type' => 'plugin'],
            ['glob' => $basePath . '/widgets/*/widget.json',        'type' => 'widget'],
            ['glob' => $basePath . '/skins/layouts/*/layout.json',  'type' => 'layout'],
        ];

        foreach ($scanTargets as $t) {
            foreach (glob($t['glob']) ?: [] as $jsonPath) {
                $data = @json_decode(@file_get_contents($jsonPath), true);
                if (!is_array($data)) continue;

                // id / slug / 디렉토리명 순으로 식별자 결정
                $slug = $data['id'] ?? $data['slug'] ?? basename(dirname($jsonPath));
                if (!$slug) continue;

                $item = [
                    'slug'          => $slug,
                    'type'          => $t['type'],
                    'version'       => $data['version'] ?? null,
                    'manifest_path' => $jsonPath,
                ];
                if (!empty($data['product_key'])) {
                    $item['product_key'] = $data['product_key'];
                }
                $items[] = $item;
            }
        }
        return $items;
    }

    /**
     * 마켓 서버에 설치된 아이템 목록 비동기 보고 (fire-and-forget)
     * 응답을 기다리지 않으므로 페이지 로딩에 영향 없음
     */
    public function syncInstalledItems(): void
    {
        if (empty($this->licenseKey)) return;

        $items = $this->collectInstalledItems();
        if (empty($items)) return;

        $payload = array_map(function ($item) {
            $entry = ['slug' => $item['slug']];
            if (!empty($item['type']))        $entry['type']        = $item['type'];
            if (!empty($item['product_key'])) $entry['product_key'] = $item['product_key'];
            if (!empty($item['version']))     $entry['version']     = $item['version'];
            return $entry;
        }, $items);

        // 비동기 전송 — 응답 대기 없음 (마켓 서버로)
        $this->apiCallAsync($this->marketUrl . '/market/sync', [
            'vos_key' => $this->licenseKey,
            'domain'  => $this->domain,
            'items'   => $payload,
        ], true);
    }

    /**
     * product_key 없는 플러그인의 키를 마켓에서 가져와 plugin.json에 저장
     * verifyWithServer() 성공 후 호출
     */
    public function resolveProductKeys(): void
    {
        if (empty($this->licenseKey)) return;

        $items   = $this->collectInstalledItems();
        $missing = array_filter($items, fn($i) => empty($i['product_key']));

        if (empty($missing)) return;

        $slugs = array_column($missing, 'slug');

        // 쿼리스트링으로 요청 (마켓 서버로)
        $query    = http_build_query(['vos_key' => $this->licenseKey, 'slugs' => $slugs]);
        $response = $this->apiCallGet($this->marketUrl . '/market/resolve-keys?' . $query, true);

        if (empty($response['ok']) || empty($response['keys'])) return;

        $keyMap = $response['keys']; // slug => product_key

        foreach ($missing as $item) {
            if (empty($keyMap[$item['slug']])) continue;
            $this->saveProductKeyToManifest($item['manifest_path'], $keyMap[$item['slug']]);
        }
    }

    /**
     * plugin.json / theme.json 에 product_key 필드 저장
     */
    private function saveProductKeyToManifest(string $manifestPath, string $productKey): void
    {
        if (!is_writable($manifestPath)) return;

        $raw  = @file_get_contents($manifestPath);
        $data = @json_decode($raw, true);
        if (!is_array($data)) return;

        $data['product_key'] = $productKey;

        $encoded = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
        if ($encoded === false) return;

        @file_put_contents($manifestPath, $encoded, LOCK_EX);
    }

    private function apiCall(string $endpoint, array $data): ?array
    {
        $url = $this->serverUrl . $endpoint;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => json_encode($data),
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER => [
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: VosCMS/' . ($_ENV['APP_VERSION'] ?? '2.0'),
            ],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$response || $httpCode === 0) {
            return null; // 네트워크 실패
        }

        return json_decode($response, true);
    }

    /**
     * 비동기 POST — 응답 대기 없이 즉시 반환 (fire-and-forget)
     * 응답 바디는 메모리에 캡처 후 폐기 (페이지 출력 오염 방지)
     *
     * @param string $endpoint /path 또는 절대 URL
     * @param bool   $absolute true면 $endpoint를 그대로 사용, false면 $this->serverUrl 접두
     */
    private function apiCallAsync(string $endpoint, array $data, bool $absolute = false): void
    {
        $url     = $absolute ? $endpoint : $this->serverUrl . $endpoint;
        $payload = json_encode($data);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => $payload,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT_MS     => 500,
            CURLOPT_CONNECTTIMEOUT => 2,
            CURLOPT_NOSIGNAL       => 1,
            CURLOPT_HTTPHEADER     => [
                'Content-Type: application/json',
                'Accept: application/json',
                'User-Agent: VosCMS/' . ($_ENV['APP_VERSION'] ?? '2.0'),
                'Content-Length: ' . strlen($payload),
            ],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        @curl_exec($ch);
        curl_close($ch);
    }

    /**
     * GET 요청 — 동기적으로 응답 반환
     *
     * @param string $endpointWithQuery /path?query 또는 절대 URL
     * @param bool   $absolute true면 $endpointWithQuery를 그대로 사용, false면 $this->serverUrl 접두
     */
    private function apiCallGet(string $endpointWithQuery, bool $absolute = false): ?array
    {
        $url = $absolute ? $endpointWithQuery : $this->serverUrl . $endpointWithQuery;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL            => $url,
            CURLOPT_HTTPGET        => true,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_CONNECTTIMEOUT => 5,
            CURLOPT_HTTPHEADER     => [
                'Accept: application/json',
                'User-Agent: VosCMS/' . ($_ENV['APP_VERSION'] ?? '2.0'),
            ],
            CURLOPT_SSL_VERIFYPEER => true,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if (!$response || $httpCode === 0) {
            return null;
        }

        return json_decode($response, true);
    }
}
