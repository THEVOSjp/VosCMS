<?php

declare(strict_types=1);

namespace RzxLib\Core\Updater;

/**
 * 업데이트 백업/롤백 관리
 */
class Backup
{
    private string $basePath;
    private string $backupDir;

    /**
     * 백업에서 제외할 디렉토리/파일
     */
    private array $excludes = [
        '.git',
        '.gitignore',
        '.env',
        '.env.local',
        'storage/logs',
        'storage/cache',
        'storage/sessions',
        'storage/framework/cache',
        'storage/framework/sessions',
        'storage/framework/views',
        'node_modules',
        'vendor',
        'backups',
    ];

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/\\');
        $this->backupDir = $this->basePath . '/storage/backups';

        if (!is_dir($this->backupDir)) {
            mkdir($this->backupDir, 0755, true);
        }
    }

    /**
     * 전체 백업 생성
     */
    public function createFull(string $version): ?string
    {
        $timestamp = date('Y-m-d_His');
        $backupName = "backup_{$version}_{$timestamp}.zip";
        $backupPath = $this->backupDir . '/' . $backupName;

        $zip = new \ZipArchive();
        if ($zip->open($backupPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            error_log("백업 생성 실패: ZIP 파일을 열 수 없습니다.");
            return null;
        }

        $this->addDirectoryToZip($zip, $this->basePath, '');
        $zip->close();

        if (!file_exists($backupPath)) {
            return null;
        }

        // 백업 메타데이터 저장
        $metaPath = $this->backupDir . '/' . pathinfo($backupName, PATHINFO_FILENAME) . '.json';
        file_put_contents($metaPath, json_encode([
            'version' => $version,
            'created_at' => date('Y-m-d H:i:s'),
            'file' => $backupName,
            'size' => filesize($backupPath),
        ], JSON_PRETTY_PRINT));

        return $backupPath;
    }

    /**
     * 특정 파일들만 백업
     */
    public function createPartial(array $files, string $version): ?string
    {
        $timestamp = date('Y-m-d_His');
        $backupName = "partial_{$version}_{$timestamp}.zip";
        $backupPath = $this->backupDir . '/' . $backupName;

        $zip = new \ZipArchive();
        if ($zip->open($backupPath, \ZipArchive::CREATE | \ZipArchive::OVERWRITE) !== true) {
            return null;
        }

        foreach ($files as $file) {
            $fullPath = $this->basePath . '/' . ltrim($file, '/\\');
            if (file_exists($fullPath)) {
                $zip->addFile($fullPath, $file);
            }
        }

        $zip->close();

        return file_exists($backupPath) ? $backupPath : null;
    }

    /**
     * 백업에서 복원
     */
    public function restore(string $backupPath): bool
    {
        if (!file_exists($backupPath)) {
            error_log("복원 실패: 백업 파일을 찾을 수 없습니다.");
            return false;
        }

        $zip = new \ZipArchive();
        if ($zip->open($backupPath) !== true) {
            error_log("복원 실패: ZIP 파일을 열 수 없습니다.");
            return false;
        }

        // 임시 디렉토리에 추출
        $tempDir = sys_get_temp_dir() . '/rezlyx_restore_' . uniqid();
        mkdir($tempDir, 0755, true);

        $zip->extractTo($tempDir);
        $zip->close();

        // 파일 복사
        $this->copyDirectory($tempDir, $this->basePath);

        // 임시 디렉토리 삭제
        $this->deleteDirectory($tempDir);

        return true;
    }

    /**
     * 백업 목록
     */
    public function getBackupList(): array
    {
        $backups = [];
        $files = glob($this->backupDir . '/*.json');

        foreach ($files as $metaFile) {
            $meta = json_decode(file_get_contents($metaFile), true);
            if ($meta) {
                $zipFile = $this->backupDir . '/' . $meta['file'];
                $backups[] = [
                    'version' => $meta['version'] ?? 'unknown',
                    'created_at' => $meta['created_at'] ?? '',
                    'file' => $meta['file'] ?? '',
                    'path' => $zipFile,
                    'size' => file_exists($zipFile) ? filesize($zipFile) : 0,
                    'exists' => file_exists($zipFile),
                ];
            }
        }

        // 최신순 정렬
        usort($backups, fn($a, $b) => strcmp($b['created_at'], $a['created_at']));

        return $backups;
    }

    /**
     * 개별 백업 삭제
     */
    public function deleteBackup(string $backupPath): bool
    {
        if (!file_exists($backupPath) || !str_starts_with(realpath($backupPath), realpath($this->backupDir))) {
            return false;
        }

        $deleted = false;
        if (unlink($backupPath)) {
            $deleted = true;
        }

        // 메타 JSON도 삭제
        $metaPath = $this->backupDir . '/' . pathinfo(basename($backupPath), PATHINFO_FILENAME) . '.json';
        if (file_exists($metaPath)) {
            unlink($metaPath);
        }

        return $deleted;
    }

    /**
     * 오래된 백업 정리
     */
    public function cleanOldBackups(int $keepCount = 5): int
    {
        $backups = $this->getBackupList();
        $deleted = 0;

        if (count($backups) <= $keepCount) {
            return 0;
        }

        $toDelete = array_slice($backups, $keepCount);

        foreach ($toDelete as $backup) {
            if (isset($backup['path']) && file_exists($backup['path'])) {
                unlink($backup['path']);
                $deleted++;
            }
            $metaPath = $this->backupDir . '/' . pathinfo($backup['file'], PATHINFO_FILENAME) . '.json';
            if (file_exists($metaPath)) {
                unlink($metaPath);
            }
        }

        return $deleted;
    }

    /**
     * 디렉토리를 ZIP에 추가
     */
    private function addDirectoryToZip(\ZipArchive $zip, string $dir, string $zipPath): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::SELF_FIRST
        );

        foreach ($iterator as $item) {
            $relativePath = $zipPath . substr($item->getPathname(), strlen($dir) + 1);
            $relativePath = str_replace('\\', '/', $relativePath);

            // 제외 항목 확인
            if ($this->shouldExclude($relativePath)) {
                continue;
            }

            if ($item->isDir()) {
                $zip->addEmptyDir($relativePath);
            } else {
                $zip->addFile($item->getPathname(), $relativePath);
            }
        }
    }

    /**
     * 제외 여부 확인
     */
    private function shouldExclude(string $path): bool
    {
        foreach ($this->excludes as $exclude) {
            if (str_starts_with($path, $exclude) || str_contains($path, '/' . $exclude)) {
                return true;
            }
        }
        return false;
    }

    /**
     * 디렉토리 복사
     */
    private function copyDirectory(string $src, string $dst): void
    {
        $dir = opendir($src);
        if (!is_dir($dst)) {
            mkdir($dst, 0755, true);
        }

        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') {
                continue;
            }

            $srcPath = $src . '/' . $file;
            $dstPath = $dst . '/' . $file;

            if (is_dir($srcPath)) {
                $this->copyDirectory($srcPath, $dstPath);
            } else {
                copy($srcPath, $dstPath);
            }
        }

        closedir($dir);
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
}
