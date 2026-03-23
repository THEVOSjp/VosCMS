<?php
/**
 * RezlyX 키오스크 - 서비스 선택 화면
 * 카테고리별 서비스 목록, 복수 선택 가능
 */
include __DIR__ . '/_init.php';

// 하단 문구
$kioskFooterText = $ks['kiosk_footer_text'] ?? '';
$footerFromDb = kioskTranslation($pdo, $prefix, 'kiosk.footer_text', $currentLocale);
$footerText = $footerFromDb ?: ($kioskFooterText ?: 'Powered by ' . $siteName);

// 파라미터
$lang = $_GET['lang'] ?? $currentLocale;
$type = $_GET['type'] ?? 'designation';
$staffId = $_GET['staff'] ?? '';

// 통화 설정
$serviceCurrency = $siteSettings['service_currency'] ?? 'KRW';
$currencySymbols = [
    'KRW' => '₩', 'USD' => '$', 'JPY' => '¥', 'EUR' => '€',
    'CNY' => '¥', 'GBP' => '£', 'THB' => '฿', 'VND' => '₫',
    'MNT' => '₮', 'RUB' => '₽', 'TRY' => '₺', 'IDR' => 'Rp',
];
$currencySymbol = $currencySymbols[$serviceCurrency] ?? $serviceCurrency;
$currencyPosition = $siteSettings['service_currency_position'] ?? 'prefix';

function kioskPrice(float $amount, string $symbol, string $position): string {
    $formatted = number_format($amount);
    return $position === 'suffix' ? $formatted . $symbol : $symbol . $formatted;
}

// 카테고리 조회
$categories = $pdo->query("
    SELECT id, name FROM {$prefix}service_categories
    WHERE is_active = 1
    ORDER BY sort_order ASC
")->fetchAll(PDO::FETCH_ASSOC);

// 서비스 조회 (지명 모드: 해당 스태프 스킬에 매칭되는 서비스만)
if ($type === 'designation' && $staffId) {
    $stSvc = $pdo->prepare("
        SELECT s.id, s.name, s.description, s.price, s.duration, s.image, s.category_id
        FROM {$prefix}services s
        INNER JOIN {$prefix}staff_services ss ON s.id = ss.service_id AND ss.staff_id = ?
        WHERE s.is_active = 1
        ORDER BY s.sort_order ASC, s.name ASC
    ");
    $stSvc->execute([$staffId]);
    $services = $stSvc->fetchAll(PDO::FETCH_ASSOC);
} else {
    $services = $pdo->query("
        SELECT id, name, description, price, duration, image, category_id
        FROM {$prefix}services
        WHERE is_active = 1
        ORDER BY sort_order ASC, name ASC
    ")->fetchAll(PDO::FETCH_ASSOC);
}

// 카테고리별 그룹핑
$servicesByCategory = [];
foreach ($services as $svc) {
    $catId = $svc['category_id'] ?? 0;
    $servicesByCategory[$catId][] = $svc;
}

// 다국어 이름 (rzx_translations에서 가져오기)
$translationKeys = [];
foreach ($categories as $cat) {
    $translationKeys[] = 'service_category.' . $cat['id'] . '.name';
}
foreach ($services as $svc) {
    $translationKeys[] = 'service.' . $svc['id'] . '.name';
    $translationKeys[] = 'service.' . $svc['id'] . '.description';
}

$translations = [];
if (!empty($translationKeys)) {
    $placeholders = implode(',', array_fill(0, count($translationKeys), '?'));
    $stmtTr = $pdo->prepare("SELECT lang_key, content FROM {$prefix}translations WHERE lang_key IN ({$placeholders}) AND locale = ?");
    $stmtTr->execute([...$translationKeys, $currentLocale]);
    foreach ($stmtTr->fetchAll(PDO::FETCH_ASSOC) as $tr) {
        $translations[$tr['lang_key']] = $tr['content'];
    }
}

function kioskTr(array $translations, string $key, string $fallback): string {
    return !empty($translations[$key]) ? $translations[$key] : $fallback;
}

// 지명 스태프 정보
$staffName = '';
if ($type === 'designation' && $staffId) {
    $stStaff = $pdo->prepare("SELECT name, name_i18n FROM {$prefix}staff WHERE id = ?");
    $stStaff->execute([$staffId]);
    $staffRow = $stStaff->fetch(PDO::FETCH_ASSOC);
    if ($staffRow) {
        if ($staffRow['name_i18n']) {
            $i18n = json_decode($staffRow['name_i18n'], true);
            $staffName = $i18n[$currentLocale] ?? $staffRow['name'];
        } else {
            $staffName = $staffRow['name'];
        }
    }
}
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
        .svc-card { transition: all 0.2s ease; }
        .svc-card:hover { transform: translateY(-2px); box-shadow: 0 8px 30px rgba(0,0,0,0.12); }
        .svc-card.selected { border-color: #3b82f6 !important; <?= $isLight ? 'background: rgba(59,130,246,0.08);' : 'background: rgba(59,130,246,0.15);' ?> }
        .svc-card.selected .svc-check { display: flex; }
        .svc-check { display: none; }
        .cat-tab { transition: all 0.2s ease; }
        .cat-tab.active { <?= $isLight ? 'background: rgba(0,0,0,0.08); color: #18181b;' : 'background: rgba(255,255,255,0.15); color: #fff;' ?> }
        .svc-scroll::-webkit-scrollbar { width: 6px; }
        .svc-scroll::-webkit-scrollbar-track { background: transparent; }
        .svc-scroll::-webkit-scrollbar-thumb { background: <?= $isLight ? 'rgba(0,0,0,0.15)' : 'rgba(255,255,255,0.15)' ?>; border-radius: 3px; }
    </style>
</head>
<body class="<?= $kioskBgType === 'gradient' ? 'bg-animated' : '' ?> flex flex-col h-screen select-none">

<?php if ($kioskBgType === 'image' && $kioskBgImage): ?>
    <div class="bg-media-container"><img src="<?= htmlspecialchars($kioskBgImage) ?>" alt="background"></div>
    <div class="bg-overlay"></div>
<?php elseif ($kioskBgType === 'video' && $kioskBgVideo): ?>
    <div class="bg-media-container"><video autoplay muted loop playsinline><source src="<?= htmlspecialchars($kioskBgVideo) ?>" type="video/<?= pathinfo($kioskBgVideo, PATHINFO_EXTENSION) ?: 'mp4' ?>"></video></div>
    <div class="bg-overlay"></div>
<?php endif; ?>

    <div class="kiosk-content flex flex-col h-screen w-full">

        <!-- 상단 헤더 -->
        <div class="flex items-center justify-between px-8 pt-6 pb-2">
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
            <div class="w-20"></div>
        </div>

        <!-- 타이틀 -->
        <div class="text-center px-8 pb-4">
            <h2 class="text-2xl font-bold <?= $textColor ?>"><?= __('reservations.kiosk_select_service') ?></h2>
            <?php if ($staffName): ?>
                <p class="<?= $subTextColor ?> text-sm mt-1"><?= __('reservations.kiosk_staff_selected') ?>: <span class="<?= $textColor ?> font-semibold"><?= htmlspecialchars($staffName) ?></span></p>
            <?php endif; ?>
        </div>

        <!-- 카테고리 탭 -->
        <?php if (count($categories) > 1): ?>
        <div class="px-8 pb-4">
            <div class="flex gap-2 overflow-x-auto justify-center flex-wrap">
                <button type="button" onclick="filterCategory('all')"
                        class="cat-tab active px-5 py-2 rounded-full text-sm font-medium whitespace-nowrap backdrop-blur-sm border <?= $isLight ? 'border-black/10 text-zinc-600' : 'border-white/20 text-zinc-400' ?>"
                        data-cat="all">
                    <?= __('reservations.filter.all') ?>
                </button>
                <?php foreach ($categories as $cat): ?>
                <button type="button" onclick="filterCategory(<?= $cat['id'] ?>)"
                        class="cat-tab px-5 py-2 rounded-full text-sm font-medium whitespace-nowrap backdrop-blur-sm border <?= $isLight ? 'border-black/10 text-zinc-600' : 'border-white/20 text-zinc-400' ?>"
                        data-cat="<?= $cat['id'] ?>">
                    <?= htmlspecialchars(kioskTr($translations, 'service_category.' . $cat['id'] . '.name', $cat['name'])) ?>
                </button>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- 서비스 목록 -->
        <div class="flex-1 overflow-y-auto svc-scroll px-8 pb-32">
            <div class="grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 gap-4 max-w-6xl mx-auto" id="serviceGrid">
                <?php foreach ($services as $svc):
                    $svcName = kioskTr($translations, 'service.' . $svc['id'] . '.name', $svc['name']);
                    $svcDesc = kioskTr($translations, 'service.' . $svc['id'] . '.description', $svc['description'] ?? '');
                    $imgUrl = $svc['image'] ? ($baseUrl . '/' . $svc['image']) : '';
                ?>
                <button type="button" onclick="toggleService(this, '<?= $svc['id'] ?>')"
                        class="svc-card relative flex flex-col rounded-2xl backdrop-blur-sm border <?= $btnBg ?> overflow-hidden text-left"
                        data-id="<?= $svc['id'] ?>"
                        data-cat="<?= $svc['category_id'] ?>"
                        data-price="<?= $svc['price'] ?>"
                        data-duration="<?= $svc['duration'] ?>">
                    <!-- 체크 표시 -->
                    <div class="svc-check absolute top-3 right-3 w-7 h-7 rounded-full bg-blue-500 items-center justify-center z-10">
                        <svg class="w-4 h-4 text-white" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                        </svg>
                    </div>
                    <!-- 이미지 -->
                    <?php if ($imgUrl): ?>
                        <div class="w-full h-32 overflow-hidden">
                            <img src="<?= htmlspecialchars($imgUrl) ?>" alt="<?= htmlspecialchars($svcName) ?>" class="w-full h-full object-cover">
                        </div>
                    <?php else: ?>
                        <div class="w-full h-32 flex items-center justify-center <?= $isLight ? 'bg-zinc-100' : 'bg-white/5' ?>">
                            <svg class="w-10 h-10 <?= $isLight ? 'text-zinc-300' : 'text-white/20' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M4 16l4.586-4.586a2 2 0 012.828 0L16 16m-2-2l1.586-1.586a2 2 0 012.828 0L20 14m-6-6h.01M6 20h12a2 2 0 002-2V6a2 2 0 00-2-2H6a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                            </svg>
                        </div>
                    <?php endif; ?>
                    <!-- 정보 -->
                    <div class="p-4 flex-1 flex flex-col gap-1">
                        <span class="<?= $btnText ?> text-sm font-bold leading-tight"><?= htmlspecialchars($svcName) ?></span>
                        <div class="flex items-center gap-2 mt-auto">
                            <span class="<?= $isLight ? 'text-blue-600' : 'text-blue-400' ?> text-sm font-bold"><?= kioskPrice((float)$svc['price'], $currencySymbol, $currencyPosition) ?></span>
                            <span class="<?= $subTextColor ?> text-xs"><?= $svc['duration'] ?><?= __('reservations.pos_min') ?></span>
                        </div>
                    </div>
                </button>
                <?php endforeach; ?>
            </div>
        </div>

        <!-- 하단 선택 바 -->
        <div id="selectionBar" class="fixed bottom-0 left-0 right-0 z-30 transform translate-y-full transition-transform duration-300">
            <div class="backdrop-blur-xl <?= $isLight ? 'bg-white/90 border-t border-zinc-200' : 'bg-zinc-900/90 border-t border-zinc-700' ?> px-8 py-4">
                <div class="max-w-6xl mx-auto flex items-center justify-between">
                    <div>
                        <span class="<?= $textColor ?> text-sm font-medium">
                            <span id="selectedCount">0</span> <?= __('reservations.kiosk_selected') ?>
                        </span>
                        <span class="<?= $subTextColor ?> text-sm mx-2">·</span>
                        <span class="<?= $isLight ? 'text-blue-600' : 'text-blue-400' ?> text-lg font-bold" id="totalPrice"><?= kioskPrice(0, $currencySymbol, $currencyPosition) ?></span>
                        <span class="<?= $subTextColor ?> text-xs ml-2">(<span id="totalDuration">0</span><?= __('reservations.pos_min') ?>)</span>
                    </div>
                    <button type="button" onclick="confirmSelection()"
                            class="px-8 py-3 bg-blue-600 hover:bg-blue-700 text-white rounded-xl text-sm font-bold transition">
                        <?= __('reservations.kiosk_next') ?>
                        <svg class="w-4 h-4 inline-block ml-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M14 5l7 7m0 0l-7 7m7-7H3"/>
                        </svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- 하단 안내 (선택바 없을 때) -->
        <div id="footerText" class="fixed bottom-6 left-0 right-0 text-center z-20">
            <p class="<?= $footerColor ?> text-sm"><?= $footerText ?></p>
        </div>

    </div>

<script>
console.log('[Kiosk] Service selection page loaded');
console.log('[Kiosk] Type:', '<?= $type ?>', 'Staff:', '<?= $staffId ?>');

const selectedServices = new Map();
const currencySymbol = '<?= $currencySymbol ?>';
const currencyPosition = '<?= $currencyPosition ?>';

function formatPrice(amount) {
    const formatted = amount.toLocaleString();
    return currencyPosition === 'suffix' ? formatted + currencySymbol : currencySymbol + formatted;
}

function toggleService(el, id) {
    if (selectedServices.has(id)) {
        selectedServices.delete(id);
        el.classList.remove('selected');
        console.log('[Kiosk] Service deselected:', id);
    } else {
        selectedServices.set(id, {
            price: parseFloat(el.dataset.price),
            duration: parseInt(el.dataset.duration)
        });
        el.classList.add('selected');
        console.log('[Kiosk] Service selected:', id);
    }
    updateSelectionBar();
}

function updateSelectionBar() {
    const count = selectedServices.size;
    let totalPrice = 0, totalDuration = 0;
    selectedServices.forEach(s => { totalPrice += s.price; totalDuration += s.duration; });

    document.getElementById('selectedCount').textContent = count;
    document.getElementById('totalPrice').textContent = formatPrice(totalPrice);
    document.getElementById('totalDuration').textContent = totalDuration;

    const bar = document.getElementById('selectionBar');
    const footer = document.getElementById('footerText');
    if (count > 0) {
        bar.classList.remove('translate-y-full');
        footer.classList.add('hidden');
    } else {
        bar.classList.add('translate-y-full');
        footer.classList.remove('hidden');
    }
}

function filterCategory(catId) {
    document.querySelectorAll('.cat-tab').forEach(tab => {
        tab.classList.toggle('active', tab.dataset.cat == catId || (catId === 'all' && tab.dataset.cat === 'all'));
    });
    document.querySelectorAll('.svc-card').forEach(card => {
        card.style.display = (catId === 'all' || card.dataset.cat == catId) ? '' : 'none';
    });
    console.log('[Kiosk] Category filter:', catId);
}

function confirmSelection() {
    if (selectedServices.size === 0) return;
    const serviceIds = Array.from(selectedServices.keys()).join(',');
    console.log('[Kiosk] Confirming services:', serviceIds);
    const adminUrl = '<?= $adminUrl ?>';
    const lang = '<?= $lang ?>';
    const type = '<?= $type ?>';
    const staff = '<?= $staffId ?>';
    // TODO: 다음 단계 (예약 확인/접수)
    window.location.href = adminUrl + '/kiosk/run/confirm?lang=' + lang + '&type=' + type + '&staff=' + staff + '&services=' + serviceIds;
}

function goBack() {
    console.log('[Kiosk] Going back');
    const adminUrl = '<?= $adminUrl ?>';
    const lang = '<?= $lang ?>';
    <?php if ($type === 'designation'): ?>
        window.location.href = adminUrl + '/kiosk/run/staff?lang=' + lang;
    <?php else: ?>
        window.location.href = adminUrl + '/kiosk/run/choose?lang=' + lang;
    <?php endif; ?>
}

// 전체화면 (더블클릭)
document.addEventListener('dblclick', () => {
    if (!document.fullscreenElement) {
        document.documentElement.requestFullscreen().catch(() => {});
    } else {
        document.exitFullscreen();
    }
});

// 우클릭 방지
document.addEventListener('contextmenu', e => e.preventDefault());

// 유휴 타이머
let idleTimer;
function resetIdleTimer() {
    clearTimeout(idleTimer);
    idleTimer = setTimeout(() => {
        console.log('[Kiosk] Idle timeout');
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
