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

    /** 라이선스 서버 URL */
    private string $serverUrl;

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
        $this->serverUrl = rtrim($_ENV['LICENSE_SERVER'] ?? 'https://vos.21ces.com/api', '/');
        $this->licenseKey = $_ENV['LICENSE_KEY'] ?? '';
        $this->domain = $this->detectDomain();
    }

    /**
     * 라이선스 상태 확인 (메인 메서드)
     * 관리자 접속 시 호출.
     */
    public function check(): LicenseStatus
    {
        // 라이선스 키 미설정
        if (empty($this->licenseKey)) {
            return LicenseStatus::unregistered();
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
            return LicenseStatus::active($response);
        }

        // 라이선스 무효
        $this->clearCache();
        return LicenseStatus::invalid($response['error'] ?? 'unknown', $response['message'] ?? '');
    }

    /**
     * 설치 시 라이선스 등록
     */
    public function register(string $key, string $domain, string $version = '', string $phpVersion = ''): array
    {
        $response = $this->apiCall('/license/register', [
            'key' => $key,
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

    /**
     * 라이선스 키 생성 (install.php에서 사용)
     */
    public static function generateKey(): string
    {
        $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
        $segments = [];
        for ($i = 0; $i < 3; $i++) {
            $seg = '';
            for ($j = 0; $j < 4; $j++) {
                $seg .= $chars[random_int(0, strlen($chars) - 1)];
            }
            $segments[] = $seg;
        }
        return 'RZX-' . implode('-', $segments);
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
}
