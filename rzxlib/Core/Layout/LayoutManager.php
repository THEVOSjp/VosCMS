<?php
namespace RzxLib\Core\Layout;

/**
 * RezlyX 레이아웃 매니저
 *
 * 고객 페이지에 레이아웃을 자동 적용합니다.
 *
 * 사용법 (index.php):
 *   LayoutManager::render($pageFile, $config, $siteSettings);
 *
 * 페이지에서 레이아웃 제어:
 *   $__layout = false;         // 레이아웃 미적용 (API, AJAX 등)
 *   $__layout = 'other';       // 다른 레이아웃 사용
 *   $pageTitle = '제목';       // 페이지 제목
 *   $metaDescription = '설명'; // SEO 설명
 */
class LayoutManager
{
    // 레이아웃 미적용 경로 패턴
    private static array $noLayoutPaths = [
        'board/api/',
        'api/',
        'logout',
        'manifest.json',
        'sw.js',
    ];

    // 자체 레이아웃 사용 경로 (로그인, 키오스크 등)
    private static array $selfLayoutPaths = [
        'login',
        'register',
        'forgot-password',
        'reset-password',
        'kiosk/',
    ];

    /**
     * 페이지를 레이아웃으로 감싸서 렌더링
     */
    public static function render(string $pageFile, array &$config, array &$siteSettings, array $extraVars = []): void
    {
        $path = trim(parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH), '/');
        $basePath = rtrim($config['app_url'] ?? '', '/');
        // basePath에서 앱 경로 제거
        $appPath = parse_url($basePath, PHP_URL_PATH) ?: '';
        $appPath = trim($appPath, '/');
        if ($appPath && str_starts_with($path, $appPath . '/')) {
            $path = substr($path, strlen($appPath) + 1);
        }

        // 레이아웃 미적용 경로 체크
        foreach (self::$noLayoutPaths as $pattern) {
            if (str_starts_with($path, $pattern) || $path === rtrim($pattern, '/')) {
                // 변수 주입 후 직접 include
                extract($extraVars);
                include $pageFile;
                return;
            }
        }

        // 자체 레이아웃 사용 경로 체크
        foreach (self::$selfLayoutPaths as $pattern) {
            if (str_starts_with($path, $pattern) || $path === rtrim($pattern, '/')) {
                extract($extraVars);
                include $pageFile;
                return;
            }
        }

        // 페이지 콘텐츠 캡처
        // 페이지에서 $__layout, $pageTitle 등을 설정할 수 있도록 변수 공유
        $__layout = 'default'; // 기본 레이아웃

        // 변수 주입
        extract($extraVars);

        ob_start();
        include $pageFile;
        $__content = ob_get_clean();

        // 페이지에서 $__layout = false 설정 시 레이아웃 미적용
        if ($__layout === false) {
            echo $__content;
            return;
        }

        // 레이아웃 렌더링
        $layoutHeader = BASE_PATH . '/resources/views/layouts/base-header.php';
        $layoutFooter = BASE_PATH . '/resources/views/layouts/base-footer.php';

        if (!file_exists($layoutHeader)) {
            // 레이아웃 파일 없으면 콘텐츠만 출력
            echo $__content;
            return;
        }

        include $layoutHeader;
        echo $__content;
        include $layoutFooter;
    }
}
