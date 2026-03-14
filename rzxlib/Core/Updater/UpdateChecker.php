<?php

declare(strict_types=1);

namespace RzxLib\Core\Updater;

/**
 * 경량 업데이트 확인 유틸리티
 * 캐시 기반으로 GitHub API 호출 최소화
 */
class UpdateChecker
{
    /** 캐시 TTL (초) — 1시간 */
    private const CACHE_TTL = 3600;

    /** 캐시 파일명 */
    private const CACHE_FILE = '/storage/.update_cache';

    /**
     * 업데이트 확인 (캐시 우선)
     *
     * @return array{has_update: bool, current: string, latest: string, name: string, url: string}|null
     */
    public static function check(\PDO $pdo, string $basePath): ?array
    {
        $cacheFile = rtrim($basePath, '/\\') . self::CACHE_FILE;

        // 1. 캐시 확인
        if (file_exists($cacheFile)) {
            $cache = json_decode(file_get_contents($cacheFile), true);
            if ($cache && isset($cache['checked_at']) && (time() - $cache['checked_at']) < self::CACHE_TTL) {
                return $cache['data'];
            }
        }

        // 2. 현재 버전
        $versionFile = rtrim($basePath, '/\\') . '/version.json';
        if (!file_exists($versionFile)) {
            return null;
        }
        $versionInfo = json_decode(file_get_contents($versionFile), true);
        if (!$versionInfo) {
            return null;
        }

        $currentVersion = $versionInfo['version'] ?? '0.0.0';
        $owner = $versionInfo['github']['owner'] ?? '';
        $repo = $versionInfo['github']['repo'] ?? '';

        if (empty($owner) || empty($repo)) {
            return null;
        }

        // 3. GitHub 토큰
        $token = null;
        try {
            $stmt = $pdo->prepare("SELECT `value` FROM rzx_settings WHERE `key` = 'github_token'");
            $stmt->execute();
            $encToken = $stmt->fetchColumn();
            if ($encToken) {
                $token = \RzxLib\Core\Helpers\Encryption::decrypt($encToken);
            }
        } catch (\Throwable $e) {
            // 토큰 없이 진행 (public repo만 가능)
        }

        // 4. GitHub API 호출
        $result = self::fetchLatestRelease($owner, $repo, $token);

        // 5. 결과 구성
        $data = null;
        if ($result !== null) {
            $latestVersion = ltrim($result['tag_name'] ?? '', 'v');
            $hasUpdate = version_compare($latestVersion, $currentVersion, '>');
            $data = [
                'has_update' => $hasUpdate,
                'current' => $currentVersion,
                'latest' => $latestVersion,
                'name' => $result['name'] ?? '',
                'url' => $result['html_url'] ?? '',
            ];
        } else {
            $data = [
                'has_update' => false,
                'current' => $currentVersion,
                'latest' => $currentVersion,
                'name' => '',
                'url' => '',
                'error' => true,
            ];
        }

        // 6. 캐시 저장
        $cacheDir = dirname($cacheFile);
        if (is_dir($cacheDir) && is_writable($cacheDir)) {
            file_put_contents($cacheFile, json_encode([
                'checked_at' => time(),
                'data' => $data,
            ], JSON_UNESCAPED_SLASHES));
        }

        return $data;
    }

    /**
     * 캐시 무효화
     */
    public static function clearCache(string $basePath): void
    {
        $cacheFile = rtrim($basePath, '/\\') . self::CACHE_FILE;
        if (file_exists($cacheFile)) {
            @unlink($cacheFile);
        }
    }

    /**
     * GitHub 최신 릴리스 조회
     */
    private static function fetchLatestRelease(string $owner, string $repo, ?string $token): ?array
    {
        $url = "https://api.github.com/repos/{$owner}/{$repo}/releases/latest";

        $headers = ['Accept: application/vnd.github.v3+json'];
        if ($token) {
            $headers[] = "Authorization: Bearer {$token}";
        }

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'RezlyX-UpdateChecker/1.0',
            CURLOPT_HTTPHEADER => $headers,
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            return null;
        }

        $data = json_decode($response, true);
        return (json_last_error() === JSON_ERROR_NONE) ? $data : null;
    }
}
