<?php

declare(strict_types=1);

namespace RzxLib\Core\Updater;

use RzxLib\Core\Helpers\Encryption;

/**
 * GitHub API 클라이언트
 * Private 저장소 접근 및 릴리스 정보 조회
 */
class GitHubClient
{
    private string $owner;
    private string $repo;
    private string $branch;
    private ?string $token;
    private string $apiBase = 'https://api.github.com';

    public function __construct(string $owner, string $repo, string $branch = 'main', ?string $token = null)
    {
        $this->owner = $owner;
        $this->repo = $repo;
        $this->branch = $branch;
        $this->token = $token ? Encryption::decrypt($token) : null;
    }

    /**
     * 최신 버전 정보 가져오기 (Release 우선 → 태그 폴백)
     */
    public function getLatestRelease(): ?array
    {
        // 1차: Release API
        $url = "{$this->apiBase}/repos/{$this->owner}/{$this->repo}/releases/latest";
        $response = $this->request($url);

        if ($response !== null) {
            return [
                'version' => ltrim($response['tag_name'] ?? '', 'v'),
                'name' => $response['name'] ?? '',
                'body' => $response['body'] ?? '',
                'published_at' => $response['published_at'] ?? '',
                'html_url' => $response['html_url'] ?? '',
                'zipball_url' => $response['zipball_url'] ?? '',
                'tarball_url' => $response['tarball_url'] ?? '',
                'assets' => $response['assets'] ?? [],
            ];
        }

        // 2차: 태그 API 폴백
        return $this->getLatestFromTags();
    }

    /**
     * 태그 기반 최신 버전 조회
     */
    private function getLatestFromTags(): ?array
    {
        $url = "{$this->apiBase}/repos/{$this->owner}/{$this->repo}/tags?per_page=10";
        $tags = $this->request($url);

        if ($tags === null || empty($tags)) return null;

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

        $tagName = $latest['name'];
        return [
            'version' => ltrim($tagName, 'v'),
            'name' => $tagName,
            'body' => '',
            'published_at' => '',
            'html_url' => "https://github.com/{$this->owner}/{$this->repo}/releases/tag/{$tagName}",
            'zipball_url' => "{$this->apiBase}/repos/{$this->owner}/{$this->repo}/zipball/{$tagName}",
            'tarball_url' => "{$this->apiBase}/repos/{$this->owner}/{$this->repo}/tarball/{$tagName}",
            'assets' => [],
        ];
    }

    /**
     * 모든 릴리스 목록
     */
    public function getReleases(int $limit = 10): array
    {
        $url = "{$this->apiBase}/repos/{$this->owner}/{$this->repo}/releases?per_page={$limit}";
        $response = $this->request($url);

        if ($response === null) {
            return [];
        }

        return array_map(function ($release) {
            return [
                'version' => ltrim($release['tag_name'] ?? '', 'v'),
                'name' => $release['name'] ?? '',
                'body' => $release['body'] ?? '',
                'published_at' => $release['published_at'] ?? '',
                'prerelease' => $release['prerelease'] ?? false,
            ];
        }, $response);
    }

    /**
     * 두 커밋/태그 간의 변경 파일 목록
     */
    public function getCompare(string $base, string $head): ?array
    {
        $url = "{$this->apiBase}/repos/{$this->owner}/{$this->repo}/compare/{$base}...{$head}";
        $response = $this->request($url);

        if ($response === null) {
            return null;
        }

        $files = [];
        foreach ($response['files'] ?? [] as $file) {
            $files[] = [
                'filename' => $file['filename'],
                'status' => $file['status'], // added, removed, modified, renamed
                'additions' => $file['additions'] ?? 0,
                'deletions' => $file['deletions'] ?? 0,
                'sha' => $file['sha'] ?? '',
            ];
        }

        return [
            'ahead_by' => $response['ahead_by'] ?? 0,
            'behind_by' => $response['behind_by'] ?? 0,
            'total_commits' => $response['total_commits'] ?? 0,
            'files' => $files,
        ];
    }

    /**
     * 파일 내용 가져오기 (raw)
     */
    public function getFileContent(string $path, ?string $ref = null): ?string
    {
        $ref = $ref ?? $this->branch;
        $url = "https://raw.githubusercontent.com/{$this->owner}/{$this->repo}/{$ref}/{$path}";

        return $this->requestRaw($url);
    }

    /**
     * 릴리스 ZIP 다운로드 URL 생성
     */
    public function getDownloadUrl(string $tag): string
    {
        return "{$this->apiBase}/repos/{$this->owner}/{$this->repo}/zipball/{$tag}";
    }

    /**
     * ZIP 파일 다운로드
     */
    public function downloadRelease(string $tag, string $savePath): bool
    {
        $url = $this->getDownloadUrl($tag);

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 5,
            CURLOPT_TIMEOUT => 300,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'RezlyX-Updater/1.0',
            CURLOPT_HTTPHEADER => $this->getHeaders(),
        ]);

        $fp = fopen($savePath, 'wb');
        if ($fp === false) {
            curl_close($ch);
            return false;
        }

        curl_setopt($ch, CURLOPT_FILE, $fp);
        $success = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);

        fclose($fp);
        curl_close($ch);

        if ($success === false || $httpCode !== 200) {
            @unlink($savePath);
            return false;
        }

        return true;
    }

    /**
     * 마지막 에러 메시지
     */
    private ?string $lastError = null;

    /**
     * 마지막 에러 가져오기
     */
    public function getLastError(): ?string
    {
        return $this->lastError;
    }

    /**
     * API 요청
     */
    private function request(string $url): ?array
    {
        $this->lastError = null;

        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 30,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'RezlyX-Updater/1.0',
            CURLOPT_HTTPHEADER => $this->getHeaders(),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        $curlErrno = curl_errno($ch);
        curl_close($ch);

        // SSL 인증서 오류 시 SSL 검증 없이 재시도
        if ($response === false && in_array($curlErrno, [60, 77, 35])) {
            error_log("GitHub API SSL Error ({$curlErrno}): {$curlError} - Retrying without SSL verify");
            $ch = curl_init();
            curl_setopt_array($ch, [
                CURLOPT_URL => $url,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_FOLLOWLOCATION => true,
                CURLOPT_TIMEOUT => 30,
                CURLOPT_SSL_VERIFYPEER => false,
                CURLOPT_SSL_VERIFYHOST => 0,
                CURLOPT_USERAGENT => 'RezlyX-Updater/1.0',
                CURLOPT_HTTPHEADER => $this->getHeaders(),
            ]);
            $response = curl_exec($ch);
            $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
            $curlError = curl_error($ch);
            curl_close($ch);
        }

        if ($response === false) {
            $this->lastError = "cURL Error: {$curlError}";
            error_log("GitHub API cURL Error: {$curlError} URL: {$url}");
            return null;
        }

        if ($httpCode !== 200) {
            $body = json_decode($response, true);
            $msg = $body['message'] ?? "HTTP {$httpCode}";
            $this->lastError = "GitHub API: {$msg} (HTTP {$httpCode})";
            error_log("GitHub API Error: {$msg} (HTTP {$httpCode}) URL: {$url}");
            return null;
        }

        $data = json_decode($response, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            $this->lastError = "JSON Parse Error: " . json_last_error_msg();
            error_log("GitHub API JSON Error: " . json_last_error_msg());
            return null;
        }

        return $data;
    }

    /**
     * Raw 요청 (파일 다운로드용)
     */
    private function requestRaw(string $url): ?string
    {
        $ch = curl_init();
        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 60,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_USERAGENT => 'RezlyX-Updater/1.0',
            CURLOPT_HTTPHEADER => $this->getHeaders(),
        ]);

        $response = curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        if ($response === false || $httpCode !== 200) {
            return null;
        }

        return $response;
    }

    /**
     * HTTP 헤더 생성
     */
    private function getHeaders(): array
    {
        $headers = [
            'Accept: application/vnd.github.v3+json',
        ];

        if ($this->token) {
            $headers[] = "Authorization: Bearer {$this->token}";
        }

        return $headers;
    }

    /**
     * 연결 테스트
     */
    public function testConnection(): array
    {
        $url = "{$this->apiBase}/repos/{$this->owner}/{$this->repo}";
        $response = $this->request($url);

        if ($response === null) {
            return [
                'success' => false,
                'message' => '저장소에 접근할 수 없습니다. 설정을 확인해주세요.',
            ];
        }

        return [
            'success' => true,
            'message' => '연결 성공',
            'repo' => [
                'name' => $response['name'] ?? '',
                'private' => $response['private'] ?? false,
                'default_branch' => $response['default_branch'] ?? 'main',
            ],
        ];
    }
}
