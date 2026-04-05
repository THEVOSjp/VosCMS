<?php
/**
 * RezlyX 키오스크 - 스태프 지명 화면
 * 활성 스태프 목록을 카드형으로 표시, 선택 → 서비스 선택으로 이동
 */
include __DIR__ . '/_init.php';

// 하단 문구
$kioskFooterText = $ks['kiosk_footer_text'] ?? '';
$footerFromDb = kioskTranslation($pdo, $prefix, 'kiosk.footer_text', $currentLocale);
$footerText = $footerFromDb ?: ($kioskFooterText ?: 'Powered by ' . $siteName);

// 스태프 목록 조회
$staffList = $pdo->query("
    SELECT s.id, s.name, s.name_i18n, s.avatar, s.bio, s.bio_i18n, s.designation_fee, p.name as position_name, p.name_i18n as position_i18n
    FROM {$prefix}staff s
    LEFT JOIN {$prefix}staff_positions p ON s.position_id = p.id
    WHERE s.is_active = 1 AND (s.is_visible = 1 OR s.is_visible IS NULL)
    ORDER BY s.sort_order ASC, s.name ASC
")->fetchAll(PDO::FETCH_ASSOC);

// 현재 이용중인 스태프 조회 (오늘 날짜, 현재 시간 기준 진행중/확정 예약)
$now = new DateTime();
$today = $now->format('Y-m-d');
$currentTime = $now->format('H:i:s');
$busyStmt = $pdo->prepare("
    SELECT DISTINCT staff_id FROM {$prefix}reservations
    WHERE reservation_date = ? AND start_time <= ? AND end_time > ?
    AND status IN ('confirmed','in_service') AND staff_id IS NOT NULL
");
$busyStmt->execute([$today, $currentTime, $currentTime]);
$busyStaffIds = array_column($busyStmt->fetchAll(PDO::FETCH_ASSOC), 'staff_id');

// 다국어 이름 헬퍼
function kioskI18n(?string $i18nJson, string $fallback, string $locale): string {
    if ($i18nJson) {
        $data = json_decode($i18nJson, true);
        if ($data && !empty($data[$locale])) return $data[$locale];
    }
    return $fallback;
}

// 통화 설정
$serviceCurrency = $siteSettings['service_currency'] ?? 'KRW';
$currencySymbols = [
    'KRW' => '₩', 'USD' => '$', 'JPY' => '¥', 'EUR' => '€',
    'CNY' => '¥', 'GBP' => '£', 'THB' => '฿', 'VND' => '₫',
    'MNT' => '₮', 'RUB' => '₽', 'TRY' => '₺', 'IDR' => 'Rp',
];
$currencySymbol = $currencySymbols[$serviceCurrency] ?? $serviceCurrency;
$currencyPosition = $siteSettings['service_currency_position'] ?? 'prefix';

function kioskFormatPrice(float $amount, string $symbol, string $position): string {
    $formatted = number_format($amount);
    return $position === 'suffix' ? $formatted . $symbol : $symbol . $formatted;
}

$lang = $_GET['lang'] ?? $currentLocale;
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
    <link rel="manifest" href="/manifest-kiosk.json">
    <title><?= htmlspecialchars($siteName) ?> - Kiosk</title>
    <script src="https://cdn.tailwindcss.com"></script>
    <style>
        * { -webkit-tap-highlight-color: transparent; }
        html, body { height: 100%; cursor: default; }
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
        .staff-card {
            transition: all 0.25s ease;
        }
        .staff-card:hover {
            transform: translateY(-4px);
            box-shadow: 0 16px 48px rgba(0,0,0,0.15);
        }
        .staff-card:active {
            transform: translateY(0);
        }
        .staff-avatar {
            width: 100px;
            height: 100px;
            object-fit: cover;
            border-radius: 50%;
        }
        /* 스크롤바 스타일 */
        .staff-scroll::-webkit-scrollbar { width: 6px; }
        .staff-scroll::-webkit-scrollbar-track { background: transparent; }
        .staff-scroll::-webkit-scrollbar-thumb { background: <?= $isLight ? 'rgba(0,0,0,0.15)' : 'rgba(255,255,255,0.15)' ?>; border-radius: 3px; }
    </style>
</head>
<body class="<?= $kioskBgType === 'gradient' ? 'bg-animated' : '' ?> flex flex-col h-screen select-none">

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

    <div class="kiosk-content flex flex-col h-screen w-full">

        <!-- 상단 헤더 -->
        <div class="flex items-center justify-between px-8 pt-8 pb-4">
            <button type="button" onclick="goBack()"
                    class="flex items-center gap-2 px-4 py-2 rounded-xl <?= $subTextColor ?> hover:<?= $textColor ?> transition text-sm backdrop-blur-sm">
                <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M10 19l-7-7m0 0l7-7m-7 7h18"/>
                </svg>
                <?= __('reservations.kiosk_back') ?>
            </button>
            <div class="text-center">
                <?php if ($kioskLogoSrc): ?>
                    <img src="<?= htmlspecialchars($kioskLogoSrc) ?>" alt="<?= htmlspecialchars($siteName) ?>" class="h-10 mx-auto object-contain">
                <?php else: ?>
                    <span class="text-lg font-bold <?= $textColor ?>"><?= htmlspecialchars($siteName) ?></span>
                <?php endif; ?>
            </div>
            <div class="w-20"></div><!-- spacer -->
        </div>

        <!-- 타이틀 -->
        <div class="text-center px-8 pb-6">
            <h2 class="text-3xl font-bold <?= $textColor ?>"><?= __('reservations.kiosk_select_staff') ?></h2>
            <p class="<?= $subTextColor ?> text-sm mt-2"><?= __('reservations.kiosk_select_staff_desc') ?></p>
        </div>

        <!-- 스태프 카드 그리드 -->
        <div class="flex-1 overflow-y-auto staff-scroll px-8 pb-24">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-4 max-w-6xl mx-auto">
                <?php foreach ($staffList as $staff):
                    $staffName = kioskI18n($staff['name_i18n'], $staff['name'], $currentLocale);
                    $positionName = kioskI18n($staff['position_i18n'] ?? null, $staff['position_name'] ?? '', $currentLocale);
                    $staffBio = kioskI18n($staff['bio_i18n'], $staff['bio'] ?? '', $currentLocale);
                    $fee = (float)$staff['designation_fee'];
                    $hasAvatar = !empty($staff['avatar']);
                    $isBusy = in_array($staff['id'], $busyStaffIds);
                ?>
                <button type="button" onclick="selectStaff(<?= $staff['id'] ?>, '<?= htmlspecialchars(addslashes($staffName)) ?>')"
                        class="staff-card relative flex flex-col items-center gap-3 p-6 rounded-2xl backdrop-blur-sm border <?= $btnBg ?> text-center">
                    <!-- 상태 뱃지 -->
                    <?php if ($isBusy): ?>
                        <span class="absolute top-3 right-3 px-2 py-0.5 rounded-full text-[10px] font-bold bg-orange-500/20 text-orange-400 border border-orange-500/30"><?= __('reservations.pos_in_service') ?></span>
                    <?php else: ?>
                        <span class="absolute top-3 right-3 px-2 py-0.5 rounded-full text-[10px] font-bold <?= $isLight ? 'bg-green-100 text-green-700 border border-green-200' : 'bg-green-500/20 text-green-400 border border-green-500/30' ?>"><?= __('reservations.kiosk_available') ?></span>
                    <?php endif; ?>
                    <!-- 아바타 -->
                    <?php if ($hasAvatar): ?>
                        <img src="<?= htmlspecialchars($staff['avatar']) ?>" alt="<?= htmlspecialchars($staffName) ?>"
                             class="staff-avatar border-2 <?= $isBusy ? 'border-orange-400/50 opacity-60' : ($isLight ? 'border-zinc-200' : 'border-white/20') ?>">
                    <?php else: ?>
                        <div class="staff-avatar flex items-center justify-center <?= $isLight ? 'bg-zinc-200 text-zinc-500' : 'bg-white/10 text-white/50' ?> border-2 <?= $isBusy ? 'border-orange-400/50 opacity-60' : ($isLight ? 'border-zinc-200' : 'border-white/20') ?>" style="border-radius:50%">
                            <svg class="w-10 h-10" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/>
                            </svg>
                        </div>
                    <?php endif; ?>

                    <!-- 이름 -->
                    <span class="<?= $btnText ?> text-lg font-bold leading-tight"><?= htmlspecialchars($staffName) ?></span>

                    <!-- 직책 -->
                    <?php if ($positionName): ?>
                        <span class="<?= $subTextColor ?> text-xs"><?= htmlspecialchars($positionName) ?></span>
                    <?php endif; ?>

                    <!-- 지명비 -->
                    <?php if ($fee > 0): ?>
                        <span class="inline-flex items-center px-3 py-1 rounded-full text-xs font-medium <?= $isLight ? 'bg-blue-100 text-blue-700' : 'bg-blue-500/20 text-blue-300' ?>">
                            <?= __('reservations.kiosk_designation_fee') ?> <?= kioskFormatPrice($fee, $currencySymbol, $currencyPosition) ?>
                        </span>
                    <?php endif; ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 하단 안내 -->
        <div class="absolute bottom-6 left-0 right-0 text-center">
            <p class="<?= $footerColor ?> text-sm"><?= $footerText ?></p>
        </div>

    </div>

<script>
console.log('[Kiosk] Staff selection page loaded, staffCount:', <?= count($staffList) ?>);

function selectStaff(staffId, staffName) {
    console.log('[Kiosk] Staff selected:', staffId, staffName);
    const adminUrl = '<?= $adminUrl ?>';
    const lang = '<?= $lang ?>';
    // 다음 단계: 서비스 선택
    window.location.href = adminUrl + '/kiosk/run/service?lang=' + lang + '&type=designation&staff=' + staffId;
}

function goBack() {
    console.log('[Kiosk] Going back to choose page');
    const adminUrl = '<?= $adminUrl ?>';
    const lang = '<?= $lang ?>';
    window.location.href = adminUrl + '/kiosk/run/choose?lang=' + lang;
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

// 유휴 타이머
let idleTimer;
function resetIdleTimer() {
    clearTimeout(idleTimer);
    idleTimer = setTimeout(() => {
        console.log('[Kiosk] Idle timeout, returning to language selection');
        window.location.href = '<?= $adminUrl ?>/kiosk/run';
    }, <?= $kioskIdleTimeout ?> * 1000);
}
['mousemove', 'touchstart', 'keydown', 'click', 'scroll'].forEach(evt => {
    document.addEventListener(evt, resetIdleTimer, { passive: true });
});
resetIdleTimer();
</script>

</body>
</html>
