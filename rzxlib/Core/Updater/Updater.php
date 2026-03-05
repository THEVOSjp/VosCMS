<?php

declare(strict_types=1);

namespace RzxLib\Core\Updater;

use RzxLib\Core\Helpers\Encryption;

/**
 * 자동 업데이트 관리자
 */
class Updater
{
    private string $basePath;
    private GitHubClient $github;
    private Backup $backup;
    private \PDO $pdo;
    private array $versionInfo;

    /**
     * 업데이트 시 보존할 파일/디렉토리
     */
    private array $preserve = [
        '.env',
        '.env.local',
        'storage',
        'uploads',
        'version.json', // 업데이트 후 별도 처리
    ];

    public function __construct(\PDO $pdo, string $basePath)
    {
        $this->pdo = $pdo;
        $this->basePath = rtrim($basePath, '/\\');
        $this->backup = new Backup($basePath);

        // 버전 정보 로드
        $this->loadVersionInfo();

        // GitHub 클라이언트 초기화
        $token = $this->getGitHubToken();
        $this->github = new GitHubClient(
            $this->versionInfo['github']['owner'] ?? '',
            $this->versionInfo['github']['repo'] ?? '',
            $this->versionInfo['github']['branch'] ?? 'main',
            $token
        );
    }

    /**
     * 업데이트 확인
     */
    public function checkForUpdates(): array
    {
        $currentVersion = $this->versionInfo['version'] ?? '0.0.0';

        $latestRelease = $this->github->getLatestRelease();
        if ($latestRelease === null) {
            return [
                'has_update' => false,
                'error' => '릴리스 정보를 가져올 수 없습니다.',
                'current_version' => $currentVersion,
            ];
        }

        $latestVersion = $latestRelease['version'];
        $hasUpdate = version_compare($latestVersion, $currentVersion, '>');

        return [
            'has_update' => $hasUpdate,
            'current_version' => $currentVersion,
            'latest_version' => $latestVersion,
            'release_name' => $latestRelease['name'],
            'release_notes' => $latestRelease['body'],
            'published_at' => $latestRelease['published_at'],
            'download_url' => $latestRelease['zipball_url'],
        ];
    }

    /**
     * 업데이트 실행
     */
    public function performUpdate(string $targetVersion = null): array
    {
        $currentVersion = $this->versionInfo['version'] ?? '0.0.0';

        // 1. 최신 릴리스 정보 가져오기
        $latestRelease = $this->github->getLatestRelease();
        if ($latestRelease === null) {
            return ['success' => false, 'error' => '릴리스 정보를 가져올 수 없습니다.'];
        }

        $targetVersion = $targetVersion ?? $latestRelease['version'];

        // 2. 업데이트 필요 여부 확인
        if (version_compare($targetVersion, $currentVersion, '<=')) {
            return ['success' => false, 'error' => '이미 최신 버전입니다.'];
        }

        // 3. 메인터넌스 모드 활성화
        $this->setMaintenanceMode(true);

        try {
            // 4. 백업 생성
            $backupPath = $this->backup->createFull($currentVersion);
            if ($backupPath === null) {
                throw new \Exception('백업 생성에 실패했습니다.');
            }

            // 5. 업데이트 다운로드
            $tempDir = sys_get_temp_dir() . '/rezlyx_update_' . uniqid();
            mkdir($tempDir, 0755, true);
            $zipPath = $tempDir . '/update.zip';

            if (!$this->github->downloadRelease('v' . $targetVersion, $zipPath)) {
                throw new \Exception('업데이트 다운로드에 실패했습니다.');
            }

            // 6. ZIP 추출
            $extractDir = $tempDir . '/extracted';
            mkdir($extractDir, 0755, true);

            $zip = new \ZipArchive();
            if ($zip->open($zipPath) !== true) {
                throw new \Exception('ZIP 파일을 열 수 없습니다.');
            }

            $zip->extractTo($extractDir);
            $zip->close();

            // GitHub ZIP은 최상위에 디렉토리가 하나 있음
            $dirs = glob($extractDir . '/*', GLOB_ONLYDIR);
            $sourceDir = $dirs[0] ?? $extractDir;

            // 7. 파일 적용
            $this->applyUpdate($sourceDir);

            // 8. 버전 정보 업데이트
            $this->updateVersionInfo($targetVersion);

            // 9. 임시 파일 정리
            $this->deleteDirectory($tempDir);

            // 10. 오래된 백업 정리
            $this->backup->cleanOldBackups(5);

            // 11. 메인터넌스 모드 해제
            $this->setMaintenanceMode(false);

            // 12. 업데이트 로그 기록
            $this->logUpdate($currentVersion, $targetVersion, true);

            return [
                'success' => true,
                'message' => "v{$currentVersion} → v{$targetVersion} 업데이트가 완료되었습니다.",
                'from_version' => $currentVersion,
                'to_version' => $targetVersion,
                'backup_path' => $backupPath,
            ];

        } catch (\Exception $e) {
            // 롤백
            if (isset($backupPath) && $backupPath) {
                $this->backup->restore($backupPath);
            }

            $this->setMaintenanceMode(false);
            $this->logUpdate($currentVersion, $targetVersion ?? 'unknown', false, $e->getMessage());

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'rolled_back' => isset($backupPath),
            ];
        }
    }

    /**
     * 롤백 실행
     */
    public function rollback(string $backupPath = null): array
    {
        $backups = $this->backup->getBackupList();

        if (empty($backups)) {
            return ['success' => false, 'error' => '사용 가능한 백업이 없습니다.'];
        }

        // 지정된 백업 또는 가장 최근 백업 사용
        if ($backupPath === null) {
            $backupPath = $backups[0]['path'];
        }

        if (!file_exists($backupPath)) {
            return ['success' => false, 'error' => '백업 파일을 찾을 수 없습니다.'];
        }

        $this->setMaintenanceMode(true);

        try {
            $restored = $this->backup->restore($backupPath);

            if (!$restored) {
                throw new \Exception('복원에 실패했습니다.');
            }

            $this->setMaintenanceMode(false);
            $this->loadVersionInfo(); // 버전 정보 다시 로드

            return [
                'success' => true,
                'message' => '이전 버전으로 복원되었습니다.',
                'restored_version' => $this->versionInfo['version'] ?? 'unknown',
            ];

        } catch (\Exception $e) {
            $this->setMaintenanceMode(false);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * 파일 적용
     */
    private function applyUpdate(string $sourceDir): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($sourceDir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = substr($item->getPathname(), strlen($sourceDir) + 1);
            $relativePath = str_replace('\\', '/', $relativePath);
            $targetPath = $this->basePath . '/' . $relativePath;

            // 보존 파일 확인
            if ($this->shouldPreserve($relativePath)) {
                continue;
            }

            if ($item->isDir()) {
                if (!is_dir($targetPath)) {
                    mkdir($targetPath, 0755, true);
                }
            } else {
                $targetDir = dirname($targetPath);
                if (!is_dir($targetDir)) {
                    mkdir($targetDir, 0755, true);
                }
                copy($item->getPathname(), $targetPath);
            }
        }
    }

    /**
     * 보존 여부 확인
     */
    private function shouldPreserve(string $path): bool
    {
        foreach ($this->preserve as $preserve) {
            if ($path === $preserve || str_starts_with($path, $preserve . '/')) {
                return true;
            }
        }
        return false;
    }

    /**
     * 버전 정보 로드
     */
    private function loadVersionInfo(): void
    {
        $versionFile = $this->basePath . '/version.json';
        if (file_exists($versionFile)) {
            $this->versionInfo = json_decode(file_get_contents($versionFile), true) ?? [];
        } else {
            $this->versionInfo = [
                'version' => '0.0.0',
                'github' => ['owner' => '', 'repo' => '', 'branch' => 'main'],
            ];
        }
    }

    /**
     * 버전 정보 업데이트
     */
    private function updateVersionInfo(string $newVersion): void
    {
        $this->versionInfo['version'] = $newVersion;
        $this->versionInfo['release_date'] = date('Y-m-d');
        $this->versionInfo['updated_at'] = date('Y-m-d H:i:s');

        $versionFile = $this->basePath . '/version.json';
        file_put_contents($versionFile, json_encode($this->versionInfo, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
    }

    /**
     * GitHub 토큰 가져오기 (암호화된 상태)
     */
    private function getGitHubToken(): ?string
    {
        try {
            $stmt = $this->pdo->prepare("SELECT `value` FROM rzx_settings WHERE `key` = 'github_token'");
            $stmt->execute();
            return $stmt->fetchColumn() ?: null;
        } catch (\PDOException $e) {
            return null;
        }
    }

    /**
     * 메인터넌스 모드 설정
     */
    private function setMaintenanceMode(bool $enabled): void
    {
        $maintenanceFile = $this->basePath . '/storage/.maintenance';

        if ($enabled) {
            file_put_contents($maintenanceFile, json_encode([
                'time' => time(),
                'message' => '시스템 업데이트 중입니다.',
            ]));
        } else {
            if (file_exists($maintenanceFile)) {
                unlink($maintenanceFile);
            }
        }
    }

    /**
     * 업데이트 로그 기록
     */
    private function logUpdate(string $from, string $to, bool $success, string $error = null): void
    {
        $logFile = $this->basePath . '/storage/logs/updates.log';
        $logDir = dirname($logFile);

        if (!is_dir($logDir)) {
            mkdir($logDir, 0755, true);
        }

        $log = sprintf(
            "[%s] %s: %s → %s%s\n",
            date('Y-m-d H:i:s'),
            $success ? 'SUCCESS' : 'FAILED',
            $from,
            $to,
            $error ? " - Error: {$error}" : ''
        );

        file_put_contents($logFile, $log, FILE_APPEND);
    }

    /**
     * 디렉토리 삭제
     */
    private function deleteDirectory(string $dir): bool
    {
        if (!is_dir($dir)) {
            return false;
        }

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : unlink($path);
        }

        return rmdir($dir);
    }

    /**
     * 현재 버전 정보
     */
    public function getCurrentVersion(): array
    {
        return $this->versionInfo;
    }

    /**
     * 백업 목록
     */
    public function getBackups(): array
    {
        return $this->backup->getBackupList();
    }

    /**
     * 시스템 요구사항 확인
     */
    public function checkRequirements(): array
    {
        return [
            'curl' => extension_loaded('curl'),
            'json' => extension_loaded('json'),
            'zip' => extension_loaded('zip'),
            'openssl' => extension_loaded('openssl'),
            'allow_url_fopen' => (bool) ini_get('allow_url_fopen'),
            'writable_root' => is_writable($this->basePath),
            'writable_storage' => is_writable($this->basePath . '/storage'),
        ];
    }
}
