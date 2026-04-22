<?php

declare(strict_types=1);

namespace VosAutoinstall;

/**
 * market.21ces.com API 기반 아이템 설치 서비스
 *
 * 1. market API /item/install → license_key 획득
 * 2. market API /download     → ZIP 다운로드
 * 3. 로컬 압축 해제 + 타입별 디렉토리에 설치
 * 4. rzx_plugin_settings에 라이선스 저장
 */
class InstallerService
{
    private string $basePath;
    private string $tmpDir;
    private string $marketApiBase;

    public function __construct(string $basePath)
    {
        $this->basePath      = rtrim($basePath, '/');
        $this->tmpDir        = $this->basePath . '/storage/tmp';
        $this->marketApiBase = rtrim($_ENV['MARKET_API_URL'] ?? 'https://market.21ces.com/api/market', '/');
    }

    public function install(
        string $slug,
        string $vosKey,
        string $domain,
        \PDO   $pdo,
        string $prefix  = 'rzx_',
        string $orderId = ''
    ): array {
        // 1. 라이선스 발급
        $licResult = $this->callInstallApi($slug, $vosKey, $domain, $orderId);
        if (!($licResult['ok'] ?? false)) {
            return ['success' => false, 'message' => $licResult['message'] ?? '라이선스 발급 실패'];
        }
        $licenseKey = $licResult['license_key'] ?? '';
        $productKey = $licResult['product_key'] ?? '';
        $isFree     = ($licResult['type'] ?? 'paid') === 'free';

        // 2. 아이템 타입 조회
        $itemInfo = $this->fetchItemInfo($slug);
        if (!$itemInfo) {
            return ['success' => false, 'message' => '아이템 정보 조회 실패'];
        }

        // 3. ZIP 다운로드
        @mkdir($this->tmpDir, 0775, true);
        $tmpPath = $this->tmpDir . '/ai-' . uniqid('', true);
        @mkdir($tmpPath, 0775, true);
        $zipPath = $tmpPath . '/package.zip';

        if (!$this->downloadPackage($slug, $licenseKey, $zipPath)) {
            $this->cleanup($tmpPath);
            return ['success' => false, 'message' => '패키지 다운로드 실패'];
        }

        // 4. 압축 해제 + 설치
        $extractPath = $tmpPath . '/extracted';
        if (!$this->extract($zipPath, $extractPath)) {
            $this->cleanup($tmpPath);
            return ['success' => false, 'message' => '압축 해제 실패'];
        }

        $result = $this->installByType($slug, $itemInfo['type'], $extractPath);
        $this->cleanup($tmpPath);
        if (!$result['success']) return $result;

        // 5. 라이선스 저장
        $this->saveLicense($pdo, $prefix, $slug, $licenseKey, $productKey);

        return [
            'success'     => true,
            'message'     => '설치 완료',
            'slug'        => $slug,
            'type'        => $itemInfo['type'],
            'license_key' => $licenseKey,
            'product_key' => $productKey,
            'is_free'     => $isFree,
        ];
    }

    private function callInstallApi(string $slug, string $vosKey, string $domain, string $orderId): array
    {
        $payload = ['domain' => $domain, 'vos_key' => $vosKey, 'item_slug' => $slug];
        if ($orderId) $payload['order_id'] = $orderId;
        $ch = curl_init($this->marketApiBase . '/item/install');
        curl_setopt_array($ch, [
            CURLOPT_POST           => true,
            CURLOPT_POSTFIELDS     => json_encode($payload),
            CURLOPT_HTTPHEADER     => ['Content-Type: application/json', 'Accept: application/json'],
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT        => 15,
            CURLOPT_SSL_VERIFYPEER => true,
        ]);
        $resp = curl_exec($ch); curl_close($ch);
        return $resp ? (json_decode($resp, true) ?: []) : [];
    }

    private function fetchItemInfo(string $slug): ?array
    {
        $ch = curl_init($this->marketApiBase . '/item?slug=' . rawurlencode($slug));
        curl_setopt_array($ch, [CURLOPT_RETURNTRANSFER => true, CURLOPT_TIMEOUT => 10,
            CURLOPT_SSL_VERIFYPEER => true, CURLOPT_HTTPHEADER => ['Accept: application/json']]);
        $resp = curl_exec($ch); curl_close($ch);
        $data = $resp ? json_decode($resp, true) : null;
        return ($data['ok'] ?? false) ? ($data['data'] ?? null) : null;
    }

    private function downloadPackage(string $slug, string $licenseKey, string $destPath): bool
    {
        $url = $this->marketApiBase . '/download?slug=' . rawurlencode($slug)
             . ($licenseKey ? '&license_key=' . rawurlencode($licenseKey) : '');
        $fp  = fopen($destPath, 'wb');
        if (!$fp) return false;
        $ch  = curl_init($url);
        curl_setopt_array($ch, [CURLOPT_FILE => $fp, CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_TIMEOUT => 120, CURLOPT_SSL_VERIFYPEER => true,
            CURLOPT_HTTPHEADER => ['User-Agent: VosCMS-AutoInstall/' . ($_ENV['APP_VERSION'] ?? '2.0')]]);
        curl_exec($ch); $code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch); fclose($fp);
        return $code === 200 && filesize($destPath) > 100;
    }

    private function installByType(string $slug, string $type, string $extractPath): array
    {
        $dirs       = glob($extractPath . '/*', GLOB_ONLYDIR);
        $sourcePath = !empty($dirs) ? $dirs[0] : $extractPath;
        $targetDir  = match ($type) {
            'plugin' => $this->basePath . '/plugins/' . $slug,
            'widget' => $this->basePath . '/widgets/' . $slug,
            'theme'  => $this->basePath . '/themes/'  . $slug,
            'skin'   => $this->basePath . '/skins/'   . $slug,
            default  => null,
        };
        if (!$targetDir) return ['success' => false, 'message' => '알 수 없는 타입: ' . $type];

        $manifest = match ($type) { 'plugin' => 'plugin.json', 'widget' => 'widget.json', default => null };
        if ($manifest && !file_exists($sourcePath . '/' . $manifest))
            return ['success' => false, 'message' => "패키지에 {$manifest} 없음"];

        $backupDir = null;
        if (is_dir($targetDir)) { $backupDir = $targetDir . '.bak.' . date('YmdHis'); rename($targetDir, $backupDir); }
        if (!$this->copyDirectory($sourcePath, $targetDir)) {
            if ($backupDir) rename($backupDir, $targetDir);
            return ['success' => false, 'message' => '파일 복사 실패'];
        }
        if ($backupDir) $this->deleteDirectory($backupDir);
        return ['success' => true, 'path' => $targetDir];
    }

    private function saveLicense(\PDO $pdo, string $prefix, string $slug, string $licenseKey, string $productKey): void
    {
        $upsert = "INSERT INTO {$prefix}plugin_settings (plugin_id, setting_key, setting_value)
                        VALUES (?, ?, ?) ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value)";
        try {
            $pdo->prepare($upsert)->execute([$slug, 'market_license_key',    $licenseKey]);
            $pdo->prepare($upsert)->execute([$slug, 'market_product_key',    $productKey]);
            $pdo->prepare($upsert)->execute([$slug, 'market_license_status', 'valid']);
        } catch (\Throwable $e) { error_log('[AutoInstall] license save: ' . $e->getMessage()); }
    }

    private function extract(string $zipPath, string $destPath): bool
    {
        if (!class_exists('ZipArchive')) return false;
        $zip = new \ZipArchive();
        if ($zip->open($zipPath) !== true) return false;
        @mkdir($destPath, 0775, true);
        $r = $zip->extractTo($destPath); $zip->close(); return $r;
    }

    private function copyDirectory(string $src, string $dst): bool
    {
        if (!is_dir($src)) return false;
        if (!is_dir($dst)) @mkdir($dst, 0775, true);
        $d = opendir($src);
        while (($f = readdir($d)) !== false) {
            if ($f === '.' || $f === '..') continue;
            $s = "$src/$f"; $t = "$dst/$f";
            is_dir($s) ? $this->copyDirectory($s, $t) : copy($s, $t);
        }
        closedir($d); return true;
    }

    private function deleteDirectory(string $dir): void
    {
        if (!is_dir($dir)) return;
        foreach (array_diff(scandir($dir), ['.', '..']) as $f) {
            $p = "$dir/$f"; is_dir($p) ? $this->deleteDirectory($p) : @unlink($p);
        }
        @rmdir($dir);
    }

    private function cleanup(string $path): void { $this->deleteDirectory($path); }
}
