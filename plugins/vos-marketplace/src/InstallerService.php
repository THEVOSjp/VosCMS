<?php

declare(strict_types=1);

namespace VosMarketplace;

use RzxLib\Core\Plugin\PluginManager;
use VosMarketplace\Models\ItemVersion;
use VosMarketplace\Models\MarketplaceItem;

/**
 * 마켓플레이스 아이템 다운로드/추출/설치
 */
class InstallerService
{
    private string $basePath;
    private string $tmpDir;

    public function __construct(string $basePath)
    {
        $this->basePath = rtrim($basePath, '/');
        $this->tmpDir = $this->basePath . '/storage/tmp';
    }

    /**
     * 아이템 설치
     */
    public function install(int $itemId, ?int $versionId = null): array
    {
        $item = MarketplaceItem::find($itemId);
        if (!$item) {
            return ['success' => false, 'message' => 'Item not found'];
        }

        // 버전 확인
        $version = $versionId
            ? ItemVersion::find($versionId)
            : ItemVersion::latestForItem($itemId);

        if (!$version || !$version->download_url) {
            return ['success' => false, 'message' => 'No downloadable version found'];
        }

        // 다운로드
        $tmpId = uniqid('mp-install-', true);
        $tmpPath = $this->tmpDir . '/' . $tmpId;
        @mkdir($tmpPath, 0775, true);

        $zipPath = $tmpPath . '/package.zip';
        $downloaded = $this->download($version->download_url, $zipPath);
        if (!$downloaded) {
            $this->cleanup($tmpPath);
            return ['success' => false, 'message' => 'Download failed'];
        }

        // 해시 검증
        if ($version->file_hash) {
            $hash = hash_file('sha256', $zipPath);
            if ($hash !== $version->file_hash) {
                $this->cleanup($tmpPath);
                return ['success' => false, 'message' => 'File integrity check failed'];
            }
        }

        // 압축 해제
        $extractPath = $tmpPath . '/extracted';
        $extracted = $this->extract($zipPath, $extractPath);
        if (!$extracted) {
            $this->cleanup($tmpPath);
            return ['success' => false, 'message' => 'Extraction failed'];
        }

        // 매니페스트 검증 및 설치
        $result = $this->installByType($item, $extractPath);

        // 정리
        $this->cleanup($tmpPath);

        return $result;
    }

    /**
     * 타입별 설치 디렉토리에 복사
     */
    private function installByType(MarketplaceItem $item, string $extractPath): array
    {
        // 추출된 디렉토리 내 첫 번째 서브 디렉토리 찾기
        $dirs = glob($extractPath . '/*', GLOB_ONLYDIR);
        $sourcePath = !empty($dirs) ? $dirs[0] : $extractPath;

        $targetDir = match ($item->type) {
            'plugin' => $this->basePath . '/plugins/' . $item->slug,
            'theme' => $this->basePath . '/themes/' . $item->slug,
            'widget' => $this->basePath . '/widgets/' . $item->slug,
            'skin' => $this->basePath . '/skins/' . $item->slug,
            default => null,
        };

        if (!$targetDir) {
            return ['success' => false, 'message' => 'Unknown item type: ' . $item->type];
        }

        // 매니페스트 확인
        $manifestFile = match ($item->type) {
            'plugin' => 'plugin.json',
            'widget' => 'widget.json',
            default => null,
        };

        if ($manifestFile && !file_exists($sourcePath . '/' . $manifestFile)) {
            return ['success' => false, 'message' => "Missing {$manifestFile} in package"];
        }

        // 기존 디렉토리가 있으면 백업
        if (is_dir($targetDir)) {
            $backupDir = $targetDir . '.bak.' . date('YmdHis');
            rename($targetDir, $backupDir);
        }

        // 파일 복사
        if (!$this->copyDirectory($sourcePath, $targetDir)) {
            // 백업 복원
            if (isset($backupDir) && is_dir($backupDir)) {
                rename($backupDir, $targetDir);
            }
            return ['success' => false, 'message' => 'Failed to copy files'];
        }

        // 백업 삭제
        if (isset($backupDir) && is_dir($backupDir)) {
            $this->deleteDirectory($backupDir);
        }

        // 플러그인이면 PluginManager로 설치/활성화
        if ($item->type === 'plugin') {
            $pm = PluginManager::getInstance();
            if ($pm) {
                $pm->install($item->slug);
            }
        }

        return [
            'success' => true,
            'message' => 'Installed successfully',
            'path' => $targetDir,
        ];
    }

    /**
     * 파일 다운로드
     */
    private function download(string $url, string $destPath): bool
    {
        $ch = curl_init();
        $fp = fopen($destPath, 'wb');
        if (!$fp) return false;

        curl_setopt_array($ch, [
            CURLOPT_URL => $url,
            CURLOPT_FILE => $fp,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_MAXREDIRS => 3,
            CURLOPT_TIMEOUT => 120,
            CURLOPT_CONNECTTIMEOUT => 15,
            CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => [
                'User-Agent: VosCMS/' . ($_ENV['APP_VERSION'] ?? '2.0'),
            ],
        ]);

        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);
        fclose($fp);

        return $httpCode === 200 && filesize($destPath) > 0;
    }

    /**
     * ZIP 압축 해제
     */
    private function extract(string $zipPath, string $destPath): bool
    {
        if (!class_exists('ZipArchive')) {
            return false;
        }

        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) {
            return false;
        }

        @mkdir($destPath, 0775, true);
        $result = $zip->extractTo($destPath);
        $zip->close();

        return $result;
    }

    /**
     * 디렉토리 재귀 복사
     */
    private function copyDirectory(string $src, string $dst): bool
    {
        if (!is_dir($src)) return false;
        if (!is_dir($dst)) @mkdir($dst, 0775, true);

        $dir = opendir($src);
        while (($file = readdir($dir)) !== false) {
            if ($file === '.' || $file === '..') continue;

            $srcFile = $src . '/' . $file;
            $dstFile = $dst . '/' . $file;

            if (is_dir($srcFile)) {
                if (!$this->copyDirectory($srcFile, $dstFile)) return false;
            } else {
                if (!copy($srcFile, $dstFile)) return false;
            }
        }
        closedir($dir);

        return true;
    }

    /**
     * 디렉토리 재귀 삭제
     */
    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;

        $files = array_diff(scandir($dir), ['.', '..']);
        foreach ($files as $file) {
            $path = $dir . '/' . $file;
            is_dir($path) ? $this->deleteDirectory($path) : @unlink($path);
        }
        @rmdir($dir);
    }

    /**
     * 임시 디렉토리 정리
     */
    private function cleanup(string $tmpPath): void
    {
        $this->deleteDirectory($tmpPath);
    }
}
