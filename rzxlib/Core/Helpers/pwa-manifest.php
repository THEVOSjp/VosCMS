<?php
/**
 * PWA Manifest 동적 생성 + 아이콘 리사이징 헬퍼
 *
 * - serve_pwa_manifest($scope): rzx_settings 의 pwa_{front|admin}_* 값과
 *   /storage/pwa/sizes/ 의 리사이즈된 아이콘으로 manifest JSON 응답.
 * - pwa_generate_icon_sizes($srcPath): 업로드된 1장에서 8개 표준 사이즈
 *   (72/96/128/144/152/192/384/512) PNG 를 GD 로 생성.
 *
 * 정적 manifest.json 파일은 제거됨 — nginx try_files 가 index.php 로 폴백,
 * index.php 가 'manifest.json' / '{ADMIN}/manifest.json' 패스를 이 헬퍼로 위임.
 */

if (!function_exists('pwa_serve_manifest')) {
    function pwa_serve_manifest(string $scope = 'front'): void
    {
        global $siteSettings, $config;

        $prefix = $scope === 'admin' ? 'pwa_admin_' : 'pwa_front_';
        $enabled = ($siteSettings[$prefix . 'enabled'] ?? '1') === '1';
        if (!$enabled) {
            http_response_code(404);
            exit;
        }

        $appName = $config['app_name'] ?? 'VosCMS';
        $name = $siteSettings[$prefix . 'name'] ?? $appName;
        $shortName = $siteSettings[$prefix . 'short_name'] ?? '';
        if ($shortName === '') $shortName = mb_substr($name, 0, 12);

        $description = $siteSettings[$prefix . 'description'] ?? '';
        $themeColor = $siteSettings[$prefix . 'theme_color'] ?? ($scope === 'admin' ? '#18181b' : '#3b82f6');
        $bgColor = $siteSettings[$prefix . 'bg_color'] ?? '#ffffff';
        $display = $siteSettings[$prefix . 'display'] ?? 'standalone';
        $iconPath = $siteSettings[$prefix . 'icon'] ?? '';

        // ⚠ trailing slash 주의 — RezlyX 원본 호환:
        //   admin scope='/admin' (no trailing slash) 형태가 Chrome 의
        //   PWA 분리 인식에 적합. trailing slash 가 붙으면 중첩 scope 처리에서
        //   front PWA 의 scope='/' 에 흡수되는 현상 발견.
        $adminPath = $config['admin_path'] ?? 'admin';
        $startUrl = $scope === 'admin' ? '/' . $adminPath : '/';
        $scopeUrl = $startUrl;

        // 8 표준 사이즈 — /storage/pwa/sizes/{base}-{size}.png 만 노출
        $sizes = [72, 96, 128, 144, 152, 192, 384, 512];
        $icons = [];

        if ($iconPath && str_starts_with($iconPath, '/storage/pwa/')) {
            $iconBase = pathinfo($iconPath, PATHINFO_FILENAME);
            $sizesDir = BASE_PATH . '/storage/pwa/sizes';

            // 사이즈가 한 개라도 없으면 lazy 생성 시도
            $needGen = false;
            foreach ($sizes as $size) {
                if (!is_file($sizesDir . '/' . $iconBase . '-' . $size . '.png')) {
                    $needGen = true;
                    break;
                }
            }
            if ($needGen) {
                $absSrc = BASE_PATH . $iconPath;
                if (is_file($absSrc)) {
                    pwa_generate_icon_sizes($absSrc, $sizesDir);
                }
            }

            // mtime 기반 cache-busting query — 아이콘 교체 시 Chrome/Cloudflare 캐시 무효화
            $cacheBust = '';
            $oneSized = $sizesDir . '/' . $iconBase . '-192.png';
            if (is_file($oneSized)) {
                $cacheBust = '?v=' . filemtime($oneSized);
            }

            // 원본 RezlyX 호환: 한 아이콘에 'maskable any' 결합 (분리하지 않음)
            // → Chrome 이 두 PWA 를 별도 앱으로 인식하는 데 영향이 없도록 단순화
            foreach ($sizes as $size) {
                $rel = '/storage/pwa/sizes/' . $iconBase . '-' . $size . '.png';
                if (is_file(BASE_PATH . $rel)) {
                    $icons[] = [
                        'src' => $rel . $cacheBust,
                        'sizes' => "{$size}x{$size}",
                        'type' => 'image/png',
                        'purpose' => 'maskable any',
                    ];
                }
            }
        }

        // 폴백: 사이즈 생성 실패 시 원본 1장이라도 노출
        if (empty($icons) && $iconPath) {
            $icons[] = [
                'src' => $iconPath,
                'sizes' => '192x192 512x512',
                'type' => 'image/png',
                'purpose' => 'maskable any',
            ];
        }

        // ⚠ id 필드는 의도적으로 누락 — Chrome 이 manifest URL 자체로 PWA 식별.
        // id 가 명시되면 scope 중첩 시 front/admin 분리가 깨지는 현상 발견.
        // (RezlyX 원본도 id 없이 두 PWA 가 별도로 설치됨)
        $manifest = [
            'name' => $name,
            'short_name' => $shortName,
            'description' => $description,
            'start_url' => $startUrl,
            'display' => $display,
            'background_color' => $bgColor,
            'theme_color' => $themeColor,
            'orientation' => $scope === 'admin' ? 'any' : 'portrait-primary',
            'scope' => $scopeUrl,
            'lang' => $config['locale'] ?? 'ko',
            'categories' => ['business', 'productivity'],
            'icons' => $icons,
        ];

        header('Content-Type: application/manifest+json; charset=utf-8');
        header('Cache-Control: public, max-age=3600');
        echo json_encode(
            $manifest,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
        );
    }
}

if (!function_exists('pwa_generate_icon_sizes')) {
    /**
     * 업로드된 PNG/WebP 1장에서 PWA 표준 8 사이즈를 생성.
     * 비율 유지 + 정사각형 캔버스 중앙 배치 + 투명 배경.
     *
     * @return string[] 생성된 파일 경로 배열
     */
    function pwa_generate_icon_sizes(string $srcPath, string $outputDir): array
    {
        if (!is_file($srcPath)) return [];
        if (!is_dir($outputDir)) {
            @mkdir($outputDir, 0755, true);
        }

        $info = @getimagesize($srcPath);
        if (!$info) return [];

        switch ($info[2]) {
            case IMAGETYPE_PNG:
                $src = @imagecreatefrompng($srcPath);
                break;
            case IMAGETYPE_WEBP:
                $src = function_exists('imagecreatefromwebp') ? @imagecreatefromwebp($srcPath) : null;
                break;
            case IMAGETYPE_JPEG:
                $src = @imagecreatefromjpeg($srcPath);
                break;
            default:
                return [];
        }
        if (!$src) return [];

        $srcW = imagesx($src);
        $srcH = imagesy($src);
        $baseName = pathinfo($srcPath, PATHINFO_FILENAME);
        $sizes = [72, 96, 128, 144, 152, 192, 384, 512];
        $generated = [];

        foreach ($sizes as $size) {
            $dst = imagecreatetruecolor($size, $size);
            imagealphablending($dst, false);
            imagesavealpha($dst, true);
            $transparent = imagecolorallocatealpha($dst, 0, 0, 0, 127);
            imagefilledrectangle($dst, 0, 0, $size, $size, $transparent);

            // 비율 유지하며 정사각 안에 fit
            $scale = min($size / $srcW, $size / $srcH);
            $newW = max(1, (int)round($srcW * $scale));
            $newH = max(1, (int)round($srcH * $scale));
            $offX = (int)round(($size - $newW) / 2);
            $offY = (int)round(($size - $newH) / 2);

            imagecopyresampled($dst, $src, $offX, $offY, 0, 0, $newW, $newH, $srcW, $srcH);

            $outPath = rtrim($outputDir, '/') . '/' . $baseName . '-' . $size . '.png';
            if (@imagepng($dst, $outPath, 9)) {
                @chmod($outPath, 0644);
                $generated[] = $outPath;
            }
            imagedestroy($dst);
        }
        imagedestroy($src);
        return $generated;
    }
}

if (!function_exists('pwa_cleanup_icon_sizes')) {
    /**
     * 옛 아이콘의 리사이즈 결과 8개 제거 (새 아이콘 업로드 시 호출).
     */
    function pwa_cleanup_icon_sizes(string $oldIconPath, string $sizesDir): void
    {
        if (!$oldIconPath) return;
        $base = pathinfo($oldIconPath, PATHINFO_FILENAME);
        if (!$base) return;
        foreach ([72, 96, 128, 144, 152, 192, 384, 512] as $size) {
            $f = rtrim($sizesDir, '/') . '/' . $base . '-' . $size . '.png';
            if (is_file($f)) @unlink($f);
        }
    }
}
