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
                'error' => \__('updater.no_release_info'),
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
            return ['success' => false, 'error' => \__('updater.no_release_info')];
        }

        $targetVersion = $targetVersion ?? $latestRelease['version'];

        // 2. 업데이트 필요 여부 확인
        if (version_compare($targetVersion, $currentVersion, '<=')) {
            return ['success' => false, 'error' => \__('updater.already_latest')];
        }

        // 3. 메인터넌스 모드 활성화
        $this->setMaintenanceMode(true);

        try {
            // 4. 백업 생성
            $backupPath = $this->backup->createFull($currentVersion);
            if ($backupPath === null) {
                throw new \Exception(\__('updater.backup_failed'));
            }

            // 5. 업데이트 다운로드
            $tempDir = sys_get_temp_dir() . '/rezlyx_update_' . uniqid();
            mkdir($tempDir, 0755, true);
            $zipPath = $tempDir . '/update.zip';

            if (!$this->github->downloadRelease('v' . $targetVersion, $zipPath)) {
                throw new \Exception(\__('updater.download_failed'));
            }

            // 6. ZIP 추출
            $extractDir = $tempDir . '/extracted';
            mkdir($extractDir, 0755, true);

            $zip = new \ZipArchive();
            if ($zip->open($zipPath) !== true) {
                throw new \Exception(\__('updater.zip_open_failed'));
            }

            $zip->extractTo($extractDir);
            $zip->close();

            // GitHub ZIP은 최상위에 디렉토리가 하나 있음
            $dirs = glob($extractDir . '/*', GLOB_ONLYDIR);
            $sourceDir = $dirs[0] ?? $extractDir;

            // 7. 파일 적용
            $this->applyUpdate($sourceDir);

            // 7.5. DB 마이그레이션 실행
            $migrationResult = $this->runDatabaseMigration($targetVersion);
            if (!$migrationResult['success'] && !empty($migrationResult['errors'])) {
                throw new \Exception(\__('updater.db_migration_failed', ['errors' => implode(', ', $migrationResult['errors'])]));
            }

            // 8. 버전 정보 업데이트
            $this->updateVersionInfo($targetVersion);

            // 9. 임시 파일 정리
            $this->deleteDirectory($tempDir);

            // 10. 오래된 백업 정리
            $this->backup->cleanOldBackups(5);

            // 11. 메인터넌스 모드 해제
            $this->setMaintenanceMode(false);

            // 11.5. 업데이트 캐시 무효화
            UpdateChecker::clearCache($this->basePath);

            // 12. 업데이트 로그 기록
            $this->logUpdate($currentVersion, $targetVersion, true);

            return [
                'success' => true,
                'message' => \__('updater.update_complete', ['from' => $currentVersion, 'to' => $targetVersion]),
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
     * 변경분만 업데이트 (Patch 모드)
     * 현재 버전과 대상 버전 사이 변경된 파일만 개별 다운로드
     */
    public function performPatchUpdate(string $targetVersion = null): array
    {
        $currentVersion = $this->versionInfo['version'] ?? '0.0.0';

        // 1. 최신 릴리스 정보
        $latestRelease = $this->github->getLatestRelease();
        if ($latestRelease === null) {
            return ['success' => false, 'error' => \__('updater.no_release_info')];
        }

        $targetVersion = $targetVersion ?? $latestRelease['version'];

        if (version_compare($targetVersion, $currentVersion, '<=')) {
            return ['success' => false, 'error' => \__('updater.already_latest')];
        }

        // 2. 변경 파일 목록 조회
        $compare = $this->github->getCompare('v' . $currentVersion, 'v' . $targetVersion);
        if ($compare === null) {
            return ['success' => false, 'error' => \__('updater.compare_failed')];
        }

        $files = $compare['files'] ?? [];
        if (empty($files)) {
            return ['success' => false, 'error' => \__('updater.no_changes')];
        }

        // 3. 파일 수 초과 시 전체 업데이트로 폴백 권장
        $maxPatchFiles = 200;
        if (count($files) > $maxPatchFiles) {
            return [
                'success' => false,
                'error' => \__('updater.too_many_changes', ['count' => count($files), 'max' => $maxPatchFiles]),
                'suggest_full' => true,
            ];
        }

        // 4. 메인터넌스 모드
        $this->setMaintenanceMode(true);

        try {
            // 5. 백업 생성
            $backupPath = $this->backup->createFull($currentVersion);
            if ($backupPath === null) {
                throw new \Exception(\__('updater.backup_failed'));
            }

            $applied = 0;
            $deleted = 0;
            $errors = [];

            // 6. 파일별 처리
            foreach ($files as $file) {
                $filename = $file['filename'];

                // 보존 파일 스킵
                if ($this->shouldPreserve($filename)) {
                    continue;
                }

                $targetPath = $this->basePath . '/' . $filename;

                if ($file['status'] === 'removed') {
                    // 삭제된 파일
                    if (file_exists($targetPath)) {
                        @unlink($targetPath);
                        $deleted++;
                    }
                } else {
                    // 추가/수정된 파일 — GitHub에서 내용 다운로드
                    $content = $this->github->getFileContent($filename, 'v' . $targetVersion);
                    if ($content === null) {
                        $errors[] = $filename;
                        continue;
                    }

                    $dir = dirname($targetPath);
                    if (!is_dir($dir)) {
                        mkdir($dir, 0755, true);
                    }
                    file_put_contents($targetPath, $content);
                    $applied++;
                }
            }

            // 7. 다운로드 실패 파일이 전체의 20% 이상이면 롤백
            if (!empty($errors) && count($errors) > count($files) * 0.2) {
                throw new \Exception(\__('updater.patch_too_many_errors', ['count' => count($errors)]));
            }

            // 8. DB 마이그레이션
            $migrationResult = $this->runDatabaseMigration($targetVersion);
            if (!$migrationResult['success'] && !empty($migrationResult['errors'])) {
                throw new \Exception(\__('updater.db_migration_failed', ['errors' => implode(', ', $migrationResult['errors'])]));
            }

            // 9. 버전 정보 업데이트
            $this->updateVersionInfo($targetVersion);

            // 10. 백업 정리
            $this->backup->cleanOldBackups(5);

            // 11. 메인터넌스 해제
            $this->setMaintenanceMode(false);

            // 12. 업데이트 캐시 무효화
            UpdateChecker::clearCache($this->basePath);

            // 13. 로그
            $this->logUpdate($currentVersion, $targetVersion, true, "patch: {$applied} applied, {$deleted} deleted" . (!empty($errors) ? ', ' . count($errors) . ' errors' : ''));

            return [
                'success' => true,
                'message' => \__('updater.update_complete', ['from' => $currentVersion, 'to' => $targetVersion]),
                'from_version' => $currentVersion,
                'to_version' => $targetVersion,
                'applied' => $applied,
                'deleted' => $deleted,
                'errors' => $errors,
                'backup_path' => $backupPath,
            ];

        } catch (\Exception $e) {
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
     * 변경 파일 목록 조회 (미리보기용)
     */
    public function getChangedFiles(string $targetVersion = null): array
    {
        $currentVersion = $this->versionInfo['version'] ?? '0.0.0';

        if ($targetVersion === null) {
            $latestRelease = $this->github->getLatestRelease();
            $targetVersion = $latestRelease['version'] ?? $currentVersion;
        }

        $compare = $this->github->getCompare('v' . $currentVersion, 'v' . $targetVersion);
        if ($compare === null) {
            return ['success' => false, 'error' => \__('updater.compare_failed')];
        }

        // 카테고리별 분류
        $added = [];
        $modified = [];
        $removed = [];

        foreach ($compare['files'] ?? [] as $file) {
            $entry = [
                'filename' => $file['filename'],
                'additions' => $file['additions'],
                'deletions' => $file['deletions'],
            ];
            match ($file['status']) {
                'added' => $added[] = $entry,
                'removed' => $removed[] = $entry,
                default => $modified[] = $entry,
            };
        }

        return [
            'success' => true,
            'current_version' => $currentVersion,
            'target_version' => $targetVersion,
            'total_commits' => $compare['total_commits'],
            'total_files' => count($compare['files']),
            'added' => $added,
            'modified' => $modified,
            'removed' => $removed,
        ];
    }

    /**
     * 롤백 실행
     */
    public function rollback(string $backupPath = null): array
    {
        $backups = $this->backup->getBackupList();

        if (empty($backups)) {
            return ['success' => false, 'error' => \__('updater.no_backup_available')];
        }

        // 지정된 백업 또는 가장 최근 백업 사용
        if ($backupPath === null) {
            $backupPath = $backups[0]['path'];
        }

        if (!file_exists($backupPath)) {
            return ['success' => false, 'error' => \__('updater.backup_not_found')];
        }

        $this->setMaintenanceMode(true);

        try {
            $restored = $this->backup->restore($backupPath);

            if (!$restored) {
                throw new \Exception(\__('updater.restore_failed'));
            }

            $this->setMaintenanceMode(false);
            $this->loadVersionInfo(); // 버전 정보 다시 로드

            return [
                'success' => true,
                'message' => \__('updater.restore_complete'),
                'restored_version' => $this->versionInfo['version'] ?? 'unknown',
            ];

        } catch (\Exception $e) {
            $this->setMaintenanceMode(false);
            return ['success' => false, 'error' => $e->getMessage()];
        }
    }

    /**
     * DB 마이그레이션 실행
     */
    private function runDatabaseMigration(string $targetVersion): array
    {
        $migrator = new DatabaseMigrator($this->pdo, $this->basePath);
        return $migrator->migrate($targetVersion);
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
                'message' => \__('updater.maintenance_message'),
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
