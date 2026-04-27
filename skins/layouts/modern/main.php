<?php
/**
 * RezlyX Default Theme - Main Layout
 *
 * 기본 레이아웃: 헤더 + 콘텐츠 + 푸터
 */

// Translator 초기화
require_once __DIR__ . '/../../../rzxlib/Core/I18n/Translator.php';
use RzxLib\Core\I18n\Translator;

// 세션 시작 (아직 시작되지 않은 경우)
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// 언어 경로 설정
$langPath = __DIR__ . '/../../../resources/lang';
Translator::init($langPath);

// URL 파라미터로 언어 변경 처리
if (isset($_GET['lang'])) {
    $newLang = $_GET['lang'];
    Translator::setLocale($newLang);

    // 현재 URL에서 lang 파라미터 제거 후 리다이렉트
    $currentUrl = strtok($_SERVER['REQUEST_URI'], '?');
    $params = $_GET;
    unset($params['lang']);
    $queryString = http_build_query($params);
    $redirectUrl = $currentUrl . ($queryString ? '?' . $queryString : '');

    header('Location: ' . $redirectUrl);
    exit;
}

// 현재 로케일 가져오기
$locale = current_locale();

// 헬퍼 함수 로드 (아직 로드되지 않은 경우)
$helpersPath = __DIR__ . '/../../../rzxlib/Core/Helpers/functions.php';
if (file_exists($helpersPath) && !function_exists('get_site_tagline')) {
    require_once $helpersPath;
}

// 사이트 이름과 타이틀 생성 (다국어 지원)
$siteName = function_exists('get_site_name') ? get_site_name() : ($config['app_name'] ?? 'RezlyX');
$siteTagline = function_exists('get_site_tagline') ? get_site_tagline() : '';
if (!empty($siteTagline)) {
    $defaultTitle = $siteName . ' - ' . $siteTagline;
} else {
    $defaultTitle = $siteName . ' - ' . __('common.reservation_system');
}

$pageTitle = $pageTitle ?? $defaultTitle;
$metaDescription = $metaDescription ?? $siteTagline;
$bodyClass = $bodyClass ?? '';
$baseUrl = $baseUrl ?? $config['app_url'] ?? '';
?>
<!DOCTYPE html>
<html lang="<?php echo htmlspecialchars($locale); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="description" content="<?php echo htmlspecialchars($metaDescription); ?>">
    <meta name="robots" content="index, follow">

    <title><?php echo htmlspecialchars($pageTitle); ?></title>

    <!-- Favicon & PWA -->
    <?php
    $_pwaS = $siteSettings ?? [];
    $pwaFrontIcon = $_pwaS['pwa_front_icon'] ?? '';
    $pwaFrontTheme = $_pwaS['pwa_front_theme_color'] ?? '#3b82f6';
    ?>
    <link rel="icon" href="<?php echo !empty($siteSettings['favicon']) ? $baseUrl . htmlspecialchars($siteSettings['favicon']) : $baseUrl . '/assets/images/favicon.ico'; ?>">
    <?php if ($pwaFrontIcon): ?>
    <link rel="apple-touch-icon" href="<?php echo $baseUrl . htmlspecialchars($pwaFrontIcon); ?>">
    <?php endif; ?>
    <link rel="manifest" href="<?php echo $baseUrl; ?>/manifest.json">
    <meta name="theme-color" content="<?php echo htmlspecialchars($pwaFrontTheme); ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="default">
    <meta name="apple-mobile-web-app-title" content="<?php echo htmlspecialchars($siteName); ?>">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="application-name" content="<?php echo htmlspecialchars($siteName); ?>">

    <!-- Fonts -->
    <link rel="preconnect" href="https://cdn.jsdelivr.net">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/gh/orioncactus/pretendard/dist/web/static/pretendard.css">

    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            darkMode: 'class',
            theme: {
                extend: {
                    fontFamily: {
                        sans: ['Pretendard', '-apple-system', 'BlinkMacSystemFont', 'sans-serif'],
                    },
                }
            }
        }
    </script>

    <!-- Custom Styles -->
    <style>
        body { font-family: 'Pretendard', -apple-system, BlinkMacSystemFont, sans-serif; }
        ::-webkit-scrollbar { width: 8px; height: 8px; }
        ::-webkit-scrollbar-track { background: transparent; }
        ::-webkit-scrollbar-thumb { background: #a1a1aa; border-radius: 4px; }
        ::-webkit-scrollbar-thumb:hover { background: #71717a; }
        .dark ::-webkit-scrollbar-thumb { background: #52525b; }
        html { scroll-behavior: smooth; }
    </style>

    <!-- Dark Mode 초기화 -->
    <script>
        if (localStorage.getItem('darkMode') === 'true' ||
            (!localStorage.getItem('darkMode') && window.matchMedia('(prefers-color-scheme: dark)').matches)) {
            document.documentElement.classList.add('dark');
        }
    </script>

    <?php if (isset($headScripts)): ?>
        <?php echo $headScripts; ?>
    <?php endif; ?>
</head>
<body class="bg-zinc-50 dark:bg-zinc-900 text-zinc-900 dark:text-white min-h-screen flex flex-col transition-colors duration-200 <?php echo htmlspecialchars($bodyClass); ?>">
    <!-- Skip to main content (접근성) -->
    <a href="#main-content"
       class="sr-only focus:not-sr-only focus:absolute focus:top-4 focus:left-4
              bg-blue-600 text-white px-4 py-2 rounded-lg z-50 font-medium">
        메인 콘텐츠로 이동
    </a>

    <!-- Main Content -->
    <main id="main-content" class="flex-1">
        <?php echo $content ?? ''; ?>
    </main>

    <!-- Toast Container -->
    <div id="toast-container" class="fixed bottom-4 right-4 z-50 space-y-2"></div>

    <!-- Scripts -->
    <script src="https://unpkg.com/alpinejs@3.x.x/dist/cdn.min.js" defer></script>

    <script>
        function toggleDarkMode() {
            const isDark = document.documentElement.classList.toggle('dark');
            localStorage.setItem('darkMode', isDark);
            console.log('[Theme] Dark mode:', isDark);
        }

        function showToast(message, type = 'info', duration = 3000) {
            const container = document.getElementById('toast-container');
            const toast = document.createElement('div');
            const colors = { success: 'bg-green-500', error: 'bg-red-500', warning: 'bg-amber-500', info: 'bg-blue-500' };
            toast.className = `${colors[type]} text-white px-6 py-3 rounded-lg shadow-lg transform transition-all duration-300 translate-x-full`;
            toast.textContent = message;
            container.appendChild(toast);
            setTimeout(() => toast.classList.remove('translate-x-full'), 10);
            setTimeout(() => { toast.classList.add('translate-x-full'); setTimeout(() => toast.remove(), 300); }, duration);
        }
    </script>

    <?php if (isset($footerScripts)): ?>
        <?php echo $footerScripts; ?>
    <?php endif; ?>

    <!-- PWA Service Worker Registration -->
    <script>
        if ('serviceWorker' in navigator) {
            window.addEventListener('load', async () => {
                try {
                    const basePath = '<?php echo rtrim($baseUrl, "/"); ?>';
                    const registration = await navigator.serviceWorker.register(basePath + '/sw.js', { scope: basePath + '/' });
                    console.log('[PWA] Service Worker registered:', registration.scope);

                    // Check for updates
                    registration.addEventListener('updatefound', () => {
                        const newWorker = registration.installing;
                        console.log('[PWA] New service worker installing...');

                        newWorker.addEventListener('statechange', () => {
                            if (newWorker.state === 'installed' && navigator.serviceWorker.controller) {
                                console.log('[PWA] New version available');
                                showUpdateNotification();
                            }
                        });
                    });
                } catch (error) {
                    console.error('[PWA] Service Worker registration failed:', error);
                }
            });

            // Handle controller change (new SW activated)
            navigator.serviceWorker.addEventListener('controllerchange', () => {
                console.log('[PWA] Controller changed');
            });
        }

        // Show update notification
        function showUpdateNotification() {
            const updateBanner = document.createElement('div');
            updateBanner.id = 'pwa-update-banner';
            updateBanner.className = 'fixed bottom-20 left-4 right-4 md:left-auto md:right-4 md:w-80 bg-blue-600 text-white p-4 rounded-lg shadow-lg z-50';
            updateBanner.innerHTML = `
                <p class="text-sm font-medium mb-2"><?= __('common.pwa.update_available') ?? '새 버전이 있습니다' ?></p>
                <div class="flex gap-2">
                    <button onclick="updatePWA()" class="flex-1 bg-white text-blue-600 px-3 py-1.5 rounded text-sm font-medium hover:bg-blue-50">
                        <?= __('common.pwa.update_now') ?? '업데이트' ?>
                    </button>
                    <button onclick="this.parentElement.parentElement.remove()" class="px-3 py-1.5 text-sm hover:bg-blue-700 rounded">
                        <?= __('common.pwa.later') ?? '나중에' ?>
                    </button>
                </div>
            `;
            document.body.appendChild(updateBanner);
        }

        function updatePWA() {
            if (navigator.serviceWorker.controller) {
                navigator.serviceWorker.controller.postMessage({ type: 'SKIP_WAITING' });
            }
            window.location.reload();
        }

        // PWA Install Prompt
        let deferredPrompt;

        window.addEventListener('beforeinstallprompt', (e) => {
            e.preventDefault();
            deferredPrompt = e;
            console.log('[PWA] Install prompt ready');
            showInstallButton();
        });

        function showInstallButton() {
            // Show install button if element exists
            const installBtn = document.getElementById('pwa-install-btn');
            if (installBtn) {
                installBtn.classList.remove('hidden');
            }
        }

        async function installPWA() {
            if (!deferredPrompt) return;

            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            console.log('[PWA] Install outcome:', outcome);
            deferredPrompt = null;

            const installBtn = document.getElementById('pwa-install-btn');
            if (installBtn) {
                installBtn.classList.add('hidden');
            }
        }

        window.addEventListener('appinstalled', () => {
            console.log('[PWA] App installed');
            deferredPrompt = null;
        });
    </script>
</body>
</html>
