<?php
/**
 * RezlyX 키오스크 - 언어 선택 화면
 * DB 키오스크 설정(kiosk_*) 반영
 */
include __DIR__ . '/_init.php';

$langData = \RzxLib\Core\Modules\LanguageModule::getData($siteSettings ?? [], $currentLocale);
$allLanguages = $langData['languages'];

// 키오스크 언어 필터
$kioskLangCodes = json_decode($ks['kiosk_languages'] ?? '[]', true);
$languages = (!empty($kioskLangCodes))
    ? array_intersect_key($allLanguages, array_flip($kioskLangCodes))
    : $allLanguages;

// 다국어 번역 (환영 문구 / 하단 문구)
$kioskWelcomeText = $ks['kiosk_welcome_text'] ?? '';
$kioskFooterText  = $ks['kiosk_footer_text'] ?? '';
$welcomeFromDb = kioskTranslation($pdo, $prefix, 'kiosk.welcome_text', $currentLocale);
$footerFromDb  = kioskTranslation($pdo, $prefix, 'kiosk.footer_text', $currentLocale);
$welcomeText = $welcomeFromDb ?: ($kioskWelcomeText ?: 'Select your language');
$footerText  = $footerFromDb ?: ($kioskFooterText ?: 'Powered by ' . $siteName);

// 언어별 국기 이모지
$langFlags = [
    'ko' => '🇰🇷', 'en' => '🇺🇸', 'ja' => '🇯🇵',
    'zh_CN' => '🇨🇳', 'zh_TW' => '🇹🇼',
    'de' => '🇩🇪', 'es' => '🇪🇸', 'fr' => '🇫🇷',
    'id' => '🇮🇩', 'mn' => '🇲🇳',
    'ru' => '🇷🇺', 'tr' => '🇹🇷', 'vi' => '🇻🇳',
];
?>
<!DOCTYPE html>
<html lang="<?= $currentLocale ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no, maximum-scale=1.0">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="mobile-web-app-capable" content="yes">
    <meta name="theme-color" content="#000000">
    <link rel="manifest" href="<?= $baseUrl ?>/manifest-kiosk.json">
    <title><?= htmlspecialchars($siteName) ?> - Kiosk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * { -webkit-tap-highlight-color: transparent; }
        html, body { height: 100%; overflow: hidden; cursor: default; }
        .lang-flag { font-size: 2rem; line-height: 1; }
        .lang-btn { transition: all 0.2s ease; }
        .lang-btn:hover { transform: translateY(-4px); box-shadow: 0 12px 40px rgba(0,0,0,0.15); }
        .lang-btn:active { transform: translateY(0px); box-shadow: 0 4px 12px rgba(0,0,0,0.1); }
        @keyframes gradientShift {
            0% { background-position: 0% 50%; }
            50% { background-position: 100% 50%; }
            100% { background-position: 0% 50%; }
        }
        .bg-animated {
            background: linear-gradient(135deg, <?= $isLight ? '#e2e8f0 0%, #f1f5f9 25%, #e2e8f0 50%, #f1f5f9 75%, #e2e8f0 100%' : '#0f172a 0%, #1e293b 25%, #0f172a 50%, #1e293b 75%, #0f172a 100%' ?>);
            background-size: 400% 400%;
            animation: gradientShift 15s ease infinite;
        }
        .bg-media-container { position: fixed; inset: 0; z-index: 0; }
        .bg-media-container img, .bg-media-container video { width: 100%; height: 100%; object-fit: cover; }
        .bg-overlay { position: fixed; inset: 0; z-index: 1; background: <?= $isLight ? 'rgba(255,255,255,' : 'rgba(0,0,0,' ?><?= $kioskBgOverlay / 100 ?>); }
        .kiosk-content { position: relative; z-index: 2; }
    </style>
</head>
<body class="<?= $kioskBgType === 'gradient' ? 'bg-animated' : '' ?> flex flex-col items-center justify-center h-screen select-none">

<?php if ($kioskBgType === 'image' && $kioskBgImage): ?>
    <div class="bg-media-container">
        <img src="<?= htmlspecialchars($kioskBgImage) ?>" alt="background">
    </div>
    <div class="bg-overlay"></div>
<?php elseif ($kioskBgType === 'video' && $kioskBgVideo): ?>
    <div class="bg-media-container">
        <video autoplay muted loop playsinline>
            <source src="<?= htmlspecialchars($kioskBgVideo) ?>" type="video/<?= pathinfo($kioskBgVideo, PATHINFO_EXTENSION) ?: 'mp4' ?>">
        </video>
    </div>
    <div class="bg-overlay"></div>
<?php endif; ?>

    <div class="kiosk-content flex flex-col items-center justify-center h-screen w-full">

        <!-- 로고/사이트명 -->
        <div class="mb-12 text-center">
            <?php if ($kioskLogoSrc): ?>
                <img src="<?= htmlspecialchars($kioskLogoSrc) ?>"
                     alt="<?= htmlspecialchars($siteName) ?>"
                     class="h-16 mx-auto mb-4 object-contain">
            <?php else: ?>
                <h1 class="text-4xl font-bold <?= $textColor ?> tracking-tight"><?= htmlspecialchars($siteName) ?></h1>
            <?php endif; ?>
            <p class="<?= $subTextColor ?> text-lg mt-3"><?= htmlspecialchars($welcomeText) ?></p>
        </div>

        <!-- 언어 선택 버튼 그리드 -->
        <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 px-8 max-w-5xl">
            <?php foreach ($languages as $code => $nativeName): ?>
                <button type="button"
                        class="lang-btn flex flex-col items-center justify-center gap-3 px-8 py-6 rounded-2xl backdrop-blur-sm border <?= $btnBg ?> min-w-[160px]"
                        onclick="selectLanguage('<?= $code ?>')">
                    <span class="lang-flag"><?= $langFlags[$code] ?? '🌐' ?></span>
                    <span class="<?= $btnText ?> text-lg font-semibold"><?= htmlspecialchars($nativeName) ?></span>
                </button>
            <?php endforeach; ?>
        </div>

        <!-- 하단 안내 (HTML 허용) -->
        <div class="absolute bottom-8 text-center">
            <p class="<?= $footerColor ?> text-sm"><?= $footerText ?></p>
        </div>

    </div>

<script>
console.log('[Kiosk] Language selection page loaded');

function selectLanguage(code) {
    console.log('[Kiosk] Language selected:', code);
    document.cookie = 'locale=' + code + ';path=/;max-age=' + (365 * 24 * 60 * 60);

    const baseUrl = '<?= $baseUrl ?>';
    const adminUrl = '<?= $adminUrl ?>';
    fetch(baseUrl + '/lang/' + code, {
        method: 'GET',
        headers: { 'X-Requested-With': 'XMLHttpRequest' }
    }).then(() => {
        window.location.href = adminUrl + '/kiosk/run/choose?lang=' + code;
    }).catch(() => {
        window.location.href = adminUrl + '/kiosk/run/choose?lang=' + code;
    });
}

// 전체화면 모드 (더블클릭)
document.addEventListener('dblclick', () => {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(() => {});
        console.log('[Kiosk] Entering fullscreen');
    } else {
        document.exitFullscreen();
        console.log('[Kiosk] Exiting fullscreen');
    }
});

// 우클릭 방지
document.addEventListener('contextmenu', e => e.preventDefault());
</script>

</body>
</html>
