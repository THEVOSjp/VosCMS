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
            $rows = $stmt->fetchAll(\PDO::FETCH_COLUMN);
            $stmt->closeCursor();
            $encToken = !empty($rows) ? $rows[0] : null;
            if ($encToken) {
                $token = \RzxLib\Core\Helpers\Encryption::decrypt($encToken);
            }
        } catch (\Throwable $e) {
            // 토큰 없이 진행 (public repo만 가능)
        }

        // 4. GitHub API 호출 (태그 기반)
        $result = self::fetchLatestTag($owner, $repo, $token);

        // 5. 결과 구성
        $data = null;
        if ($result !== null) {
            $latestVersion = ltrim($result['tag_name'], 'v');
            $hasUpdate = version_compare($latestVersion, $currentVersion, '>');
            $data = [
                'has_update' => $hasUpdate,
                'current' => $currentVersion,
                'latest' => $latestVersion,
                'name' => $result['tag_name'],
                'url' => "https://github.com/{$owner}/{$repo}/releases/tag/{$result['tag_name']}",
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
     * GitHub 최신 태그 조회 (semver 기준 최신)
     */
    private static function fetchLatestTag(string $owner, string $repo, ?string $token): ?array
    {
        $url = "https://api.github.com/repos/{$owner}/{$repo}/tags?per_page=50";

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

        $tags = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE || empty($tags)) {
            return null;
        }

        // semver 태그만 필터링하여 최신 찾기
        $latest = null;
        $latestVersion = '0.0.0';
        foreach ($tags as $tag) {
            $name = $tag['name'] ?? '';
            if (!preg_match('/^v?\d+\.\d+\.\d+/', $name)) continue;
            $ver = ltrim($name, 'v');
            if (version_compare($ver, $latestVersion, '>')) {
                $latestVersion = $ver;
                $latest = $tag;
            }
        }

        if (!$latest) return null;

        return [
            'tag_name' => $latest['name'],
            'sha' => $latest['commit']['sha'] ?? '',
        ];
    }
}
