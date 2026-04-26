<?php
/**
 * 배포 빌드 후 처리: voscms.com 호스팅 서비스 주문 시스템 제거 패치
 *
 * 사용법: php8.3 strip-hosting-service.php /path/to/build/voscms-X.Y.Z
 */
declare(strict_types=1);

if ($argc < 2) {
    fwrite(STDERR, "Usage: php strip-hosting-service.php <target_dir>\n");
    exit(1);
}
$target = rtrim($argv[1], '/');
if (!is_dir($target)) {
    fwrite(STDERR, "Target not found: {$target}\n");
    exit(2);
}

$changes = 0;

// 1) AdminRouter.php — service-orders 라우트 블록 제거
$ar = $target . '/rzxlib/Core/Router/AdminRouter.php';
if (is_file($ar)) {
    $c = file_get_contents($ar);
    // if ($adminRoute === 'service-orders') { ... } elseif (preg_match('#^service-orders/.*?#', ...) { ... }
    $pattern = '#\s*if\s*\(\s*\$adminRoute\s*===\s*[\'"]service-orders[\'"].*?elseif\s*\(\s*preg_match\(\s*[\'"]\#\^service-orders.+?\}\s*(?=elseif|else\s|$|\})#s';
    $new = preg_replace($pattern, "\n    ", $c);
    if ($new !== null && $new !== $c) {
        file_put_contents($ar, $new);
        echo "  patched: AdminRouter.php (service-orders routes removed)\n";
        $changes++;
    } else {
        // 단일 if 블록만 있는 경우 폴백
        $pat2 = '#\s*if\s*\(\s*\$adminRoute\s*===\s*[\'"]service-orders[\'"]\s*\)\s*\{[^{}]*\}\s*(?=elseif|else|\})#s';
        $new = preg_replace($pat2, "\n    ", $c);
        if ($new !== null && $new !== $c) {
            file_put_contents($ar, $new);
            echo "  patched: AdminRouter.php (single block)\n";
            $changes++;
        }
    }
}

// 2) index.php — mypage/services 라우트 2개 elseif 블록 제거
$ix = $target . '/index.php';
if (is_file($ix)) {
    $c = file_get_contents($ix);
    $lines = explode("\n", $c);
    $out = [];
    $skip = 0;
    foreach ($lines as $ln) {
        if ($skip > 0) { $skip--; continue; }
        // 첫 번째 블록: } elseif ($path === 'mypage/services') {
        //               $__pageFile = BASE_PATH . '/resources/views/customer/mypage/services.php';
        if (preg_match("#\\}\\s*elseif\\s*\\(\\\$path\\s*===\\s*['\"]mypage/services['\"]\\)#", $ln)) {
            $skip = 1; // 다음 라인($__pageFile = ...) 스킵
            continue;
        }
        // 두 번째 블록: } elseif (preg_match('#^mypage/services/...
        //               $serviceOrderNumber = ...
        //               $__pageFile = BASE_PATH . '/resources/views/customer/mypage/service-detail.php';
        if (preg_match('#\}\s*elseif\s*\(preg_match\(\s*[\'"]\#\^mypage/services#', $ln)) {
            $skip = 2;
            continue;
        }
        $out[] = $ln;
    }
    $new = implode("\n", $out);
    if ($new !== $c) {
        file_put_contents($ix, $new);
        echo "  patched: index.php (mypage/services routes removed)\n";
        $changes++;
    } else {
        echo "  index.php: no mypage/services route to patch\n";
    }
}

// 3) config/system-pages.php — 'service/...' slug 항목 제거
$sp = $target . '/config/system-pages.php';
if (is_file($sp)) {
    $pages = include $sp;
    if (is_array($pages)) {
        $original = count($pages);
        $pages = array_values(array_filter($pages, function ($p) {
            $slug = $p['slug'] ?? '';
            return !str_starts_with($slug, 'service/');
        }));
        if (count($pages) !== $original) {
            file_put_contents($sp, "<?php\nreturn " . var_export($pages, true) . ";\n");
            echo "  patched: config/system-pages.php (" . ($original - count($pages)) . " service entries removed)\n";
            $changes++;
        }
    }
}

echo "  total patches: {$changes}\n";
