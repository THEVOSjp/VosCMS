<?php
/**
 * RezlyX 키오스크 - 지명/배정 선택 화면
 * 언어 선택 후 스태프 지명 or 자동 배정 분기
 */
include __DIR__ . '/_init.php';

// 하단 문구
$kioskFooterText = $ks['kiosk_footer_text'] ?? '';
$footerFromDb = kioskTranslation($pdo, $prefix, 'kiosk.footer_text', $currentLocale);
$footerText = $footerFromDb ?: ($kioskFooterText ?: 'Powered by ' . $siteName);
?>
<!DOCTYPE html>
<html lang="<?= $currentLocale ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, user-scalable=no">
    <title><?= htmlspecialchars($siteName) ?> - Kiosk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * { -webkit-tap-highlight-color: transparent; }
        html, body { height: 100%; overflow: hidden; cursor: default; }
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
        .choose-btn {
            transition: all 0.25s ease;
        }
        .choose-btn:hover {
            transform: translateY(-6px);
            box-shadow: 0 20px 60px rgba(0,0,0,0.2);
        }
        .choose-btn:active {
            transform: translateY(0);
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }
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

    <div class="kiosk-content flex flex-col items-center justify-center h-screen w-full px-8">

        <!-- 로고/사이트명 -->
        <div class="mb-12 text-center">
            <?php if ($kioskLogoSrc): ?>
                <img src="<?= htmlspecialchars($kioskLogoSrc) ?>"
                     alt="<?= htmlspecialchars($siteName) ?>"
                     class="h-16 mx-auto mb-4 object-contain">
            <?php else: ?>
                <h1 class="text-4xl font-bold <?= $textColor ?> tracking-tight"><?= htmlspecialchars($siteName) ?></h1>
            <?php endif; ?>
            <p class="<?= $subTextColor ?> text-xl mt-3"><?= __('reservations.kiosk_choose_title') ?></p>
        </div>

        <!-- 지명 / 배정 선택 -->
        <div class="flex flex-col sm:flex-row gap-6 max-w-3xl w-full">

            <!-- 지명 버튼 -->
            <button type="button" onclick="chooseType('designation')"
                    class="choose-btn flex-1 flex flex-col items-center justify-center gap-4 p-10 rounded-3xl backdrop-blur-sm border <?= $btnBg ?>">
                <div class="w-20 h-20 rounded-2xl flex items-center justify-center <?= $isLight ? 'bg-blue-100' : 'bg-blue-500/20' ?>">
                    <svg class="w-10 h-10 <?= $isLight ? 'text-blue-600' : 'text-blue-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                    </svg>
                </div>
                <span class="<?= $btnText ?> text-2xl font-bold"><?= __('reservations.kiosk_designation') ?></span>
                <span class="<?= $subTextColor ?> text-sm text-center leading-relaxed"><?= __('reservations.kiosk_designation_desc') ?></span>
            </button>

            <!-- 배정 버튼 -->
            <button type="button" onclick="chooseType('assignment')"
                    class="choose-btn flex-1 flex flex-col items-center justify-center gap-4 p-10 rounded-3xl backdrop-blur-sm border <?= $btnBg ?>">
                <div class="w-20 h-20 rounded-2xl flex items-center justify-center <?= $isLight ? 'bg-emerald-100' : 'bg-emerald-500/20' ?>">
                    <svg class="w-10 h-10 <?= $isLight ? 'text-emerald-600' : 'text-emerald-400' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 20h5v-2a3 3 0 00-5.356-1.857M17 20H7m10 0v-2c0-.656-.126-1.283-.356-1.857M7 20H2v-2a3 3 0 015.356-1.857M7 20v-2c0-.656.126-1.283.356-1.857m0 0a5.002 5.002 0 019.288 0M15 7a3 3 0 11-6 0 3 3 0 016 0z"/>
                    </svg>
                </div>
                <span class="<?= $btnText ?> text-2xl font-bold"><?= __('reservations.kiosk_assignment') ?></span>
                <span class="<?= $subTextColor ?> text-sm text-center leading-relaxed"><?= __('reservations.kiosk_assignment_desc') ?></span>
            </button>

        </div>

        <!-- 뒤로가기 -->
        <button type="button" onclick="goBack()"
                class="mt-8 flex items-center gap-2 px-6 py-3 rounded-xl <?= $subTextColor ?> hover:<?= $textColor ?> transition text-sm">
            <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
            </svg>
            <?= __('reservations.kiosk_back_lang') ?>
        </button>

        <!-- 하단 안내 -->
        <div class="absolute bottom-8 text-center">
            <p class="<?= $footerColor ?> text-sm"><?= $footerText ?></p>
        </div>

    </div>

<script>
console.log('[Kiosk] Choose page loaded (designation / assignment)');

function chooseType(type) {
    console.log('[Kiosk] Type chosen:', type);
    const adminUrl = '<?= $adminUrl ?>';
    const lang = new URLSearchParams(window.location.search).get('lang') || '<?= $currentLocale ?>';
    if (type === 'designation') {
        // 지명: 스태프 선택 → 서비스 선택
        window.location.href = adminUrl + '/kiosk/run/staff?lang=' + lang;
    } else {
        // 배정: 서비스 선택으로 바로 이동
        window.location.href = adminUrl + '/kiosk/run/service?lang=' + lang + '&type=assignment';
    }
}

function goBack() {
    console.log('[Kiosk] Going back to language selection');
    const adminUrl = '<?= $adminUrl ?>';
    window.location.href = adminUrl + '/kiosk/run';
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

// 유휴 타이머 — 지정 시간 후 언어 선택으로 복귀
let idleTimer;
function resetIdleTimer() {
    clearTimeout(idleTimer);
    idleTimer = setTimeout(() => {
        console.log('[Kiosk] Idle timeout, returning to language selection');
        window.location.href = '<?= $adminUrl ?>/kiosk/run';
    }, <?= $kioskIdleTimeout ?> * 1000);
}
['mousemove', 'touchstart', 'keydown', 'click'].forEach(evt => {
    document.addEventListener(evt, resetIdleTimer, { passive: true });
});
resetIdleTimer();
</script>

</body>
</html>
