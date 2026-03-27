<?php
/**
 * RezlyX Staff Detail Page - 스태프 상세 + 월간 캘린더 + 슬롯 조회
 */

require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
use RzxLib\Core\Auth\Auth;

$baseUrl = $config['app_url'] ?? '';
$staffId = $routeParams['id'] ?? $staffSlug ?? 0;
$currentLocale = $config['locale'] ?? 'ko';
$isLoggedIn = Auth::check();
$currentUser = $isLoggedIn ? Auth::user() : null;

if (empty($staffId)) {
    header('Location: ' . $baseUrl . '/staff');
    exit;
}

// 다국어 헬퍼
function getLocalizedVal($name, $nameI18n, $locale) {
    if (!empty($nameI18n)) {
        $i18n = is_string($nameI18n) ? json_decode($nameI18n, true) : $nameI18n;
        if (is_array($i18n) && !empty($i18n[$locale])) return $i18n[$locale];
    }
    return $name;
}

function getSubNameVal($nameI18n, $locale) {
    if (empty($nameI18n)) return '';
    $i18n = is_string($nameI18n) ? json_decode($nameI18n, true) : $nameI18n;
    if (!is_array($i18n)) return '';
    // 서브네임은 항상 영어 대문자로 표시
    if (!empty($i18n['en'])) return strtoupper($i18n['en']);
    return '';
}

$staff = null;
$staffServices = [];
$schedules = [];
$businessHours = [];

try {
    $pdo = new PDO(
        'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx') . ';charset=utf8mb4',
        $_ENV['DB_USERNAME'] ?? 'root',
        $_ENV['DB_PASSWORD'] ?? '',
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
    $prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

    $scheduleEnabled = ($siteSettings['staff_schedule_enabled'] ?? '0') === '1';
    $slotInterval = (int)($siteSettings['booking_slot_interval'] ?? 30);
    if (!in_array($slotInterval, [15, 30, 60])) $slotInterval = 30;

    // ========== AJAX 처리 ==========
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        include BASE_PATH . '/resources/views/customer/staff-detail-ajax.php';
    }

    // ========== 페이지 데이터 로드 ==========
    $stmt = $pdo->prepare("SELECT s.*, p.name as position_name, p.name_i18n as position_name_i18n
        FROM {$prefix}staff s
        LEFT JOIN {$prefix}staff_positions p ON s.position_id = p.id
        WHERE s.id = ? AND s.is_active = 1");
    $stmt->execute([$staffId]);
    $staff = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$staff) {
        header('Location: ' . $baseUrl . '/staff');
        exit;
    }

    // 담당 서비스
    $stmt = $pdo->prepare("SELECT sv.id, sv.name, sv.slug, sv.description, sv.price, sv.duration, sv.image,
            sc.name as category_name
        FROM {$prefix}staff_services ss
        JOIN {$prefix}services sv ON ss.service_id = sv.id
        LEFT JOIN {$prefix}service_categories sc ON sv.category_id = sc.id
        WHERE ss.staff_id = ? AND sv.is_active = 1
        ORDER BY sv.sort_order, sv.name");
    $stmt->execute([$staffId]);
    $staffServices = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // 해당 스태프에 할당된 활성 번들 조회
    $staffBundles = [];
    $bundleStmt = $pdo->prepare("
        SELECT b.*, GROUP_CONCAT(bi.service_id ORDER BY bi.sort_order) as service_id_list,
               COUNT(bi.id) as item_count,
               COALESCE(SUM(sv.price), 0) as original_total,
               COALESCE(SUM(sv.duration), 0) as total_duration
        FROM {$prefix}staff_bundles sb
        JOIN {$prefix}service_bundles b ON sb.bundle_id = b.id
        JOIN {$prefix}service_bundle_items bi ON b.id = bi.bundle_id
        JOIN {$prefix}services sv ON bi.service_id = sv.id
        WHERE sb.staff_id = ? AND b.is_active = 1
        GROUP BY b.id
        ORDER BY b.display_order, b.name
    ");
    $bundleStmt->execute([$staffId]);
    $staffBundles = $bundleStmt->fetchAll(PDO::FETCH_ASSOC);

    // 다국어 번역 헬퍼 (폴백: 현재 로케일 → en → 원본)
    $_trCache = [];
    $_trStmt = $pdo->prepare("SELECT locale, content FROM {$prefix}translations WHERE lang_key = ? AND locale IN (?, 'en')");
    function _tr($pdo, $prefix, $langKey, $default, $locale) {
        global $_trCache, $_trStmt;
        if (isset($_trCache[$langKey])) {
            $cached = $_trCache[$langKey];
        } else {
            $_trStmt->execute([$langKey, $locale]);
            $cached = [];
            while ($r = $_trStmt->fetch(PDO::FETCH_ASSOC)) $cached[$r['locale']] = $r['content'];
            $_trCache[$langKey] = $cached;
        }
        return $cached[$locale] ?? $cached['en'] ?? $default;
    }

    // 서비스 다국어 적용
    foreach ($staffServices as &$_sv) {
        $_sv['name'] = _tr($pdo, $prefix, 'service.' . $_sv['id'] . '.name', $_sv['name'], $currentLocale);
        if (!empty($_sv['category_name'])) {
            $_sv['category_name'] = _tr($pdo, $prefix, 'category.' . ($_sv['category_id'] ?? '') . '.name', $_sv['category_name'], $currentLocale);
        }
    }
    unset($_sv);

    // 번들 다국어 적용
    foreach ($staffBundles as &$_bdl) {
        $_bdl['name'] = _tr($pdo, $prefix, 'bundle.' . $_bdl['id'] . '.name', $_bdl['name'], $currentLocale);
        $_bdl['description'] = _tr($pdo, $prefix, 'bundle.' . $_bdl['id'] . '.description', $_bdl['description'] ?? '', $currentLocale);
    }
    unset($_bdl);

    // bundle_display_name 다국어
    $_bdnVal = _tr($pdo, $prefix, 'bundle_display_name', '', $currentLocale);
    if ($_bdnVal) $siteSettings['bundle_display_name'] = $_bdnVal;

    // 주간 스케줄 (프로필 표시용)
    $stmt = $pdo->prepare("SELECT * FROM {$prefix}staff_schedules WHERE staff_id = ? ORDER BY day_of_week");
    $stmt->execute([$staffId]);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        $schedules[$row['day_of_week']] = $row;
    }

} catch (PDOException $e) {
    if ($config['debug'] ?? false) {
        error_log('Staff detail DB error: ' . $e->getMessage());
    }
    header('Location: ' . $baseUrl . '/staff');
    exit;
}

$staffName = getLocalizedVal($staff['name'], $staff['name_i18n'], $currentLocale);
$subName = getSubNameVal($staff['name_i18n'], $currentLocale);
$positionLabel = getLocalizedVal($staff['position_name'] ?? '', $staff['position_name_i18n'] ?? null, $currentLocale);
$bio = getLocalizedVal($staff['bio'] ?? '', $staff['bio_i18n'] ?? null, $currentLocale);
$designationFee = (float)($staff['designation_fee'] ?? 0);
$_rawAvatar = $staff['avatar'] ?? '';
$avatarUrl = !empty($_rawAvatar) ? (str_starts_with($_rawAvatar, 'http') ? $_rawAvatar : $baseUrl . '/' . ltrim($_rawAvatar, '/')) : '';
$_rawBanner = $staff['banner'] ?? '';
$bannerUrl = !empty($_rawBanner) ? (str_starts_with($_rawBanner, 'http') ? $_rawBanner : $baseUrl . '/' . ltrim($_rawBanner, '/')) : '';

// 로그인 회원: 등급 할인/적립금 조회
$userGrade = null;
$userPointsBalance = 0;
$discountEnabled = ($siteSettings['service_discount_enabled'] ?? '0') === '1';
$pointsEnabled = ($siteSettings['service_points_enabled'] ?? '0') === '1';
$depositEnabled = ($siteSettings['service_deposit_enabled'] ?? '0') === '1';
$depositType = $siteSettings['service_deposit_type'] ?? 'fixed';
$depositAmount = (float)($siteSettings['service_deposit_amount'] ?? 0);
$depositPercent = (float)($siteSettings['service_deposit_percent'] ?? 0);
if ($isLoggedIn && $currentUser) {
    $ugStmt = $pdo->prepare("SELECT u.points_balance, g.id as grade_id, g.name as grade_name, g.discount_rate, g.point_rate, g.color as grade_color
        FROM {$prefix}users u LEFT JOIN {$prefix}member_grades g ON u.grade_id = g.id WHERE u.id = ?");
    $ugStmt->execute([$currentUser['id']]);
    $ugRow = $ugStmt->fetch(PDO::FETCH_ASSOC);
    if ($ugRow) {
        $userPointsBalance = (float)($ugRow['points_balance'] ?? 0);
        if (!empty($ugRow['grade_name'])) {
            $userGrade = [
                'id' => $ugRow['grade_id'],
                'name' => $ugRow['grade_name'],
                'discount_rate' => (float)($ugRow['discount_rate'] ?? 0),
                'point_rate' => (float)($ugRow['point_rate'] ?? 0),
                'color' => $ugRow['grade_color'] ?? '#6B7280',
            ];
        }
    }
}

$pageTitle = $staffName . ' - ' . ($config['app_name'] ?? 'RezlyX');

// 요일명
$dayLabels = [
    'ko' => ['일', '월', '화', '수', '목', '금', '토'],
    'ja' => ['日', '月', '火', '水', '木', '金', '土'],
    'en' => ['Sun', 'Mon', 'Tue', 'Wed', 'Thu', 'Fri', 'Sat'],
];
$days = $dayLabels[$currentLocale] ?? $dayLabels['en'];

?>

    <main class="max-w-4xl mx-auto px-4 py-6">
        <!-- Breadcrumb -->
        <nav class="text-sm text-gray-500 dark:text-zinc-400 mb-4">
            <a href="<?= $baseUrl ?>/staff" class="hover:text-blue-600 dark:hover:text-blue-400"><?= __('staff_page.back_to_list') ?></a>
            <span class="mx-2">&gt;</span>
            <span class="text-gray-900 dark:text-white"><?= htmlspecialchars($staffName) ?></span>
        </nav>

        <!-- Banner -->
        <?php if (!empty($bannerUrl)): ?>
        <div class="w-full h-48 md:h-56 rounded-xl overflow-hidden mb-6">
            <img src="<?= htmlspecialchars($bannerUrl) ?>" alt="" class="w-full h-full object-cover">
        </div>
        <?php endif; ?>

        <!-- Profile Header -->
        <div class="flex flex-col md:flex-row gap-6 mb-8">
            <!-- Avatar -->
            <div class="w-32 md:w-40 flex-shrink-0">
                <div class="aspect-square overflow-hidden bg-gray-100 dark:bg-zinc-800 rounded-xl <?= !empty($bannerUrl) ? '-mt-16 md:-mt-20 relative z-10 border-4 border-white dark:border-zinc-900' : '' ?>">
                    <?php if (!empty($avatarUrl)): ?>
                    <img src="<?= htmlspecialchars($avatarUrl) ?>" alt="<?= htmlspecialchars($staffName) ?>" class="w-full h-full object-cover">
                    <?php else: ?>
                    <div class="w-full h-full flex items-center justify-center bg-gradient-to-br from-gray-200 to-gray-300 dark:from-zinc-700 dark:to-zinc-800">
                        <span class="text-4xl font-bold text-gray-400 dark:text-zinc-500"><?= mb_substr($staffName, 0, 1) ?></span>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Info -->
            <div class="flex-1 <?= !empty($bannerUrl) ? 'pt-0 md:pt-2' : '' ?>">
                <h1 class="text-2xl font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($staffName) ?></h1>
                <?php if (!empty($subName)): ?>
                <p class="text-gray-500 dark:text-zinc-400 text-sm mt-0.5"><?= htmlspecialchars($subName) ?></p>
                <?php endif; ?>

                <?php if (!empty($positionLabel)): ?>
                <span class="inline-block mt-2 px-3 py-1 text-xs font-medium bg-blue-100 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400 rounded-full">
                    <?= htmlspecialchars($positionLabel) ?>
                </span>
                <?php endif; ?>

                <?php if ($designationFee > 0): ?>
                <p class="mt-2 text-red-600 dark:text-red-400 font-semibold text-sm">
                    <?= __('staff_page.designation_fee') ?> &yen;<?= number_format($designationFee) ?>
                </p>
                <?php endif; ?>

                <?php if (!empty($bio)): ?>
                <div class="mt-3 text-gray-700 dark:text-zinc-300 text-sm leading-relaxed">
                    <?= nl2br(htmlspecialchars($bio)) ?>
                </div>
                <?php endif; ?>

                <!-- Weekly Schedule Summary -->
                <?php if (!empty($schedules)): ?>
                <div class="mt-4">
                    <div class="flex flex-wrap gap-1.5">
                        <?php for ($d = 0; $d < 7; $d++):
                            $sch = $schedules[$d] ?? null;
                            $isWorking = $sch && $sch['is_working'];
                            $dayColor = $d === 0 ? 'text-red-500' : ($d === 6 ? 'text-blue-500' : 'text-gray-700 dark:text-zinc-300');
                        ?>
                        <div class="text-center px-2 py-1 rounded <?= $isWorking ? 'bg-gray-50 dark:bg-zinc-800' : 'bg-gray-100 dark:bg-zinc-900 opacity-50' ?>">
                            <div class="text-xs font-medium <?= $dayColor ?>"><?= $days[$d] ?></div>
                            <?php if ($isWorking): ?>
                            <div class="text-[10px] text-gray-500 dark:text-zinc-400 mt-0.5">
                                <?= substr($sch['start_time'], 0, 5) ?>-<?= substr($sch['end_time'], 0, 5) ?>
                            </div>
                            <?php else: ?>
                            <div class="text-[10px] text-gray-400 dark:text-zinc-500 mt-0.5"><?= __('staff_page.day_off') ?></div>
                            <?php endif; ?>
                        </div>
                        <?php endfor; ?>
                    </div>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- Bundle Packages -->
        <?php if (!empty($staffBundles)): ?>
        <div class="border-t border-gray-200 dark:border-zinc-700 pt-6 mb-6">
            <?php
            $_bundleName = $siteSettings['bundle_display_name'] ?? '';
            if (!$_bundleName) $_bundleName = __('bundles.default_name') ?? '세트 서비스';
            ?>
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4"><?= __('bundles.recommended') ?? '추천' ?> <?= htmlspecialchars($_bundleName) ?></h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-3" id="sdBundleList">
                <?php foreach ($staffBundles as $bdl):
                    $bdlPrice = (float)$bdl['bundle_price'];
                    $origPrice = (float)$bdl['original_total'];
                    $discPct = $origPrice > 0 && $bdlPrice < $origPrice ? round((1 - $bdlPrice / $origPrice) * 100) : 0;
                    $svcIdList = $bdl['service_id_list'] ?? '';
                ?>
                <?php $bdlImage = $bdl['image'] ?? ''; if ($bdlImage && !str_starts_with($bdlImage, 'http')) $bdlImage = $baseUrl . $bdlImage; ?>
                <div class="sd-bundle-card cursor-pointer" data-bundle-id="<?= htmlspecialchars($bdl['id']) ?>" data-services="<?= htmlspecialchars($svcIdList) ?>" data-price="<?= $bdlPrice ?>" data-duration="<?= (int)$bdl['total_duration'] ?>" data-name="<?= htmlspecialchars($bdl['name']) ?>">
                    <div class="sd-bundle-inner relative rounded-xl border-2 border-gray-200 dark:border-zinc-700 hover:border-blue-300 dark:hover:border-blue-600 overflow-hidden transition-all bg-white dark:bg-zinc-800 hover:shadow-md">
                        <?php if ($bdlImage): ?>
                        <!-- 배경 이미지 헤더 -->
                        <div class="relative h-28 overflow-hidden">
                            <img src="<?= htmlspecialchars($bdlImage) ?>" class="w-full h-full object-cover" alt="">
                            <div class="absolute inset-0 bg-gradient-to-t from-black/60 to-transparent"></div>
                            <!-- 배경 위 제목 -->
                            <div class="absolute bottom-2 left-3 right-3">
                                <h3 class="font-semibold text-white text-sm drop-shadow"><?= htmlspecialchars($bdl['name']) ?></h3>
                            </div>
                            <!-- 할인 뱃지 -->
                            <?php if ($discPct > 0): ?>
                            <div class="absolute top-2 right-2 px-2 py-0.5 bg-red-500 text-white text-xs font-bold rounded-full shadow">-<?= $discPct ?>%</div>
                            <?php endif; ?>
                            <!-- 선택 체크 -->
                            <div class="absolute top-2 left-2 w-5 h-5 rounded-full border-2 border-white/70 flex items-center justify-center sd-bundle-circle bg-black/20">
                                <svg class="w-3 h-3 text-white hidden sd-bundle-check-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                        </div>
                        <!-- 본문 -->
                        <div class="p-3">
                            <?php if (!empty($bdl['description'])): ?>
                            <p class="text-xs text-gray-500 dark:text-zinc-400 mb-2 line-clamp-2"><?= htmlspecialchars($bdl['description']) ?></p>
                            <?php endif; ?>
                            <div class="flex items-center justify-between">
                                <span class="text-xs text-gray-400 dark:text-zinc-500"><?= (int)$bdl['item_count'] ?><?= __('bundles.services_count') ?> · <?= (int)$bdl['total_duration'] ?><?= __('common.minutes') ?></span>
                                <div class="text-right">
                                    <span class="text-base font-bold text-blue-600 dark:text-blue-400">&yen;<?= number_format($bdlPrice) ?></span>
                                    <?php if ($discPct > 0): ?>
                                    <span class="text-xs text-gray-400 line-through ml-1">&yen;<?= number_format($origPrice) ?></span>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php else: ?>
                        <!-- 이미지 없는 기본 카드 -->
                        <div class="p-4">
                            <!-- 할인 뱃지 -->
                            <?php if ($discPct > 0): ?>
                            <div class="absolute -top-2 -right-2 px-2 py-0.5 bg-red-500 text-white text-xs font-bold rounded-full shadow">-<?= $discPct ?>%</div>
                            <?php endif; ?>
                            <!-- 선택 체크 -->
                            <div class="absolute top-3 left-3 w-5 h-5 rounded-full border-2 border-gray-300 dark:border-zinc-600 flex items-center justify-center sd-bundle-circle">
                                <svg class="w-3 h-3 text-white hidden sd-bundle-check-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                            </div>
                            <div class="pl-7">
                                <div class="flex items-center gap-2">
                                    <svg class="w-5 h-5 text-blue-500 flex-shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                                    <h3 class="font-semibold text-gray-900 dark:text-white text-sm"><?= htmlspecialchars($bdl['name']) ?></h3>
                                </div>
                                <?php if (!empty($bdl['description'])): ?>
                                <p class="text-xs text-gray-500 dark:text-zinc-400 mt-1 line-clamp-2"><?= htmlspecialchars($bdl['description']) ?></p>
                                <?php endif; ?>
                                <div class="flex items-center justify-between mt-2">
                                    <span class="text-xs text-gray-400 dark:text-zinc-500"><?= (int)$bdl['item_count'] ?><?= __('bundles.services_count') ?> · <?= (int)$bdl['total_duration'] ?><?= __('common.minutes') ?></span>
                                    <div class="text-right">
                                        <span class="text-base font-bold text-blue-600 dark:text-blue-400">&yen;<?= number_format($bdlPrice) ?></span>
                                        <?php if ($discPct > 0): ?>
                                        <span class="text-xs text-gray-400 line-through ml-1">&yen;<?= number_format($origPrice) ?></span>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            </div>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>

        <!-- Available Services -->
        <?php if (!empty($staffServices)): ?>
        <div class="border-t border-gray-200 dark:border-zinc-700 pt-6 mb-8">
            <div class="flex items-center justify-between mb-4">
                <h2 class="text-lg font-bold text-gray-900 dark:text-white"><?= __('staff_page.available_services') ?></h2>
            </div>

            <!-- 카테고리 필터 -->
            <?php
            $sdCategories = [];
            foreach ($staffServices as $s) {
                $cid = $s['category_id'] ?? '';
                $cname = $s['category_name'] ?? '';
                if ($cid && $cname && !isset($sdCategories[$cid])) {
                    $sdCategories[$cid] = $cname;
                }
            }
            ?>
            <?php if (!empty($sdCategories)): ?>
            <div id="sdCatFilter" class="flex flex-wrap gap-2 mb-3">
                <button type="button" class="sd-cat-btn px-3 py-1 text-xs font-medium rounded-full transition-all bg-blue-600 text-white" data-cat=""><?= __('common.all') ?></button>
                <?php foreach ($sdCategories as $cid => $cname): ?>
                <button type="button" class="sd-cat-btn px-3 py-1 text-xs font-medium rounded-full transition-all bg-gray-100 dark:bg-zinc-700 text-gray-600 dark:text-zinc-300 hover:bg-gray-200 dark:hover:bg-zinc-600" data-cat="<?= htmlspecialchars($cid) ?>"><?= htmlspecialchars($cname) ?></button>
                <?php endforeach; ?>
            </div>
            <?php endif; ?>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-3">
                <?php foreach ($staffServices as $svc):
                    $svcName = htmlspecialchars($svc['name']);
                    $svcPrice = (float)$svc['price'];
                    $svcDuration = (int)$svc['duration'];
                    $svcImage = $svc['image'] ?? '';
                    $hasImage = !empty($svcImage);
                    $svcCatId = $svc['category_id'] ?? '';
                    $svcCatName = $svc['category_name'] ?? '';
                ?>
                <div class="sd-svc-card cursor-pointer" data-cat="<?= htmlspecialchars($svcCatId) ?>">
                    <input type="checkbox" class="hidden sd-svc-check" value="<?= $svc['id'] ?>"
                           data-name="<?= $svcName ?>" data-price="<?= $svcPrice ?>" data-duration="<?= $svcDuration ?>">
                    <div class="sd-card-inner group relative rounded-xl border-2 border-gray-200 dark:border-zinc-700 hover:border-gray-300 dark:hover:border-zinc-600 hover:shadow-md cursor-pointer transition-all overflow-hidden"
                         style="min-height:150px;<?php if ($hasImage): ?>background-image:url('<?= htmlspecialchars($baseUrl . '/' . $svcImage) ?>');background-size:cover;background-position:center<?php endif; ?>">
                        <?php if (!$hasImage): ?>
                        <div class="absolute inset-0 bg-gradient-to-br from-gray-100 to-gray-200 dark:from-zinc-700 dark:to-zinc-800"></div>
                        <?php endif; ?>
                        <div class="absolute inset-0 bg-gradient-to-t from-black/70 via-black/20 to-transparent"></div>
                        <div class="absolute inset-0 bg-blue-500/20 hidden sd-overlay"></div>
                        <!-- 선택 체크 -->
                        <div class="absolute top-2 right-2 w-6 h-6 rounded-full border-2 border-white/70 bg-black/20 flex items-center justify-center transition-all shadow-sm z-10 sd-circle">
                            <svg class="w-3.5 h-3.5 text-white hidden sd-check-icon" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/>
                            </svg>
                        </div>
                        <?php if ($svcCatName): ?>
                        <div class="absolute top-2 left-2 z-10">
                            <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-black/40 text-white/90 backdrop-blur-sm"><?= htmlspecialchars($svcCatName) ?></span>
                        </div>
                        <?php endif; ?>
                        <div class="absolute bottom-0 left-0 right-0 p-3 z-10">
                            <p class="text-sm font-bold text-white truncate drop-shadow-sm"><?= $svcName ?></p>
                            <div class="flex items-center justify-between mt-1">
                                <span class="text-xs text-white/70 flex items-center gap-1">
                                    <svg class="w-3 h-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                    <?= $svcDuration ?><?= __('common.minutes') ?>
                                </span>
                                <span class="text-sm font-bold text-white drop-shadow-sm">&yen;<?= number_format($svcPrice) ?></span>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>

            <!-- 선택 개수 표시 -->
            <div id="sdSvcCount" class="hidden mt-3 text-sm text-blue-600 dark:text-blue-400 font-medium"></div>
        </div>
        <?php endif; ?>

        <!-- Monthly Calendar + Slots Section -->
        <div class="border-t border-gray-200 dark:border-zinc-700 pt-6 mb-8">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4"><?= __('staff_page.schedule_calendar') ?></h2>

            <!-- Calendar Navigation -->
            <div class="flex items-center justify-between mb-3">
                <button id="btnPrevMonth" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-800 text-gray-600 dark:text-zinc-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
                </button>
                <h3 id="calendarTitle" class="text-base font-semibold text-gray-900 dark:text-white"></h3>
                <button id="btnNextMonth" class="p-2 rounded-lg hover:bg-gray-100 dark:hover:bg-zinc-800 text-gray-600 dark:text-zinc-400">
                    <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 5l7 7-7 7"/></svg>
                </button>
            </div>

            <!-- Calendar Grid -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
                <!-- Day Headers -->
                <div class="grid grid-cols-7 text-center text-xs font-medium border-b border-gray-200 dark:border-zinc-700">
                    <?php for ($d = 0; $d < 7; $d++):
                        $hColor = $d === 0 ? 'text-red-500' : ($d === 6 ? 'text-blue-500' : 'text-gray-500 dark:text-zinc-400');
                    ?>
                    <div class="py-2 <?= $hColor ?>"><?= $days[$d] ?></div>
                    <?php endfor; ?>
                </div>
                <!-- Calendar Body -->
                <div id="calendarBody" class="grid grid-cols-7 text-center">
                    <!-- JS fills this -->
                </div>
            </div>

            <!-- Time Slots -->
            <div id="slotsSection" class="mt-4 hidden">
                <h3 id="slotsTitle" class="text-sm font-semibold text-gray-900 dark:text-white mb-3"></h3>
                <div id="slotsGrid" class="flex flex-wrap gap-2">
                    <!-- JS fills this -->
                </div>
                <div id="slotsEmpty" class="hidden text-sm text-gray-500 dark:text-zinc-400 py-4 text-center">
                    <?= __('staff_page.no_available_slots') ?>
                </div>
                <div id="slotsLoading" class="hidden text-sm text-gray-500 dark:text-zinc-400 py-4 text-center">
                    <svg class="animate-spin h-5 w-5 mx-auto mb-1 text-gray-400" xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24"><circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="4"></circle><path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 018-8V0C5.373 0 0 5.373 0 12h4z"></path></svg>
                </div>
            </div>
        </div>

        <!-- Booking Summary Panel -->
        <?php if (!empty($staffServices)): ?>
        <div id="sdBookingSummary" class="border-t border-gray-200 dark:border-zinc-700 pt-6 mb-8 hidden">
            <h2 class="text-lg font-bold text-gray-900 dark:text-white mb-4"><?= __('staff_page.booking_summary') ?></h2>
            <div class="bg-white dark:bg-zinc-800 rounded-xl border border-gray-200 dark:border-zinc-700 overflow-hidden">
                <!-- 선택 서비스 목록 -->
                <div id="sdSelectedList" class="divide-y divide-gray-100 dark:divide-zinc-700/50">
                    <!-- JS fills -->
                </div>

                <div class="p-4 space-y-2 border-t border-gray-200 dark:border-zinc-700">
                    <!-- 지명료 -->
                    <?php if ($designationFee > 0): ?>
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500 dark:text-zinc-400"><?= __('staff_page.designation_fee') ?></span>
                        <span class="text-gray-900 dark:text-white">&yen;<?= number_format($designationFee) ?></span>
                    </div>
                    <?php endif; ?>

                    <!-- 총 소요시간 -->
                    <div class="flex justify-between text-sm">
                        <span class="text-gray-500 dark:text-zinc-400"><?= __('booking.total_duration') ?></span>
                        <span id="sdSumDuration" class="text-gray-900 dark:text-white font-medium"></span>
                    </div>

                    <!-- 예약 일시 -->
                    <div id="sdDateTimeRow" class="hidden flex justify-between text-sm">
                        <span class="text-gray-500 dark:text-zinc-400"><?= __('staff_page.booking_datetime') ?></span>
                        <span id="sdDateTimeLabel" class="text-blue-600 dark:text-blue-400 font-medium"></span>
                    </div>

                    <!-- 회원 할인 -->
                    <?php if ($isLoggedIn && $discountEnabled && $userGrade && $userGrade['discount_rate'] > 0): ?>
                    <div id="sdDiscountRow" class="flex justify-between text-sm hidden">
                        <span class="text-red-500 dark:text-red-400">
                            <?= __('booking.member_discount') ?>
                            <span class="text-xs">(<?= htmlspecialchars(_tr($pdo, $prefix, 'grade.' . ($userGrade['id'] ?? '') . '.name', $userGrade['name'], $currentLocale)) ?> <?= $userGrade['discount_rate'] ?>%)</span>
                        </span>
                        <span id="sdDiscountAmount" class="text-red-500 dark:text-red-400 font-medium"></span>
                    </div>
                    <?php endif; ?>

                    <!-- 적립금 사용 -->
                    <?php if ($isLoggedIn && $pointsEnabled && $userPointsBalance > 0): ?>
                    <div id="sdPointsRow" class="hidden">
                        <div class="flex items-center justify-between text-sm mb-1">
                            <label for="sdPointsInput" class="text-gray-500 dark:text-zinc-400">
                                <?= get_points_name($currentLocale) ?> <?= __('reservations.used') ?>
                                <span class="text-xs text-blue-500">(<?= __('booking.points_balance') ?>: &yen;<?= number_format($userPointsBalance) ?>)</span>
                            </label>
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="text-sm text-gray-400">&yen;</span>
                            <input type="number" id="sdPointsInput" min="0" max="<?= (int)$userPointsBalance ?>" step="1" value="0"
                                   class="flex-1 px-3 py-1.5 text-sm border border-gray-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500">
                            <button type="button" id="sdPointsAllBtn" class="flex-shrink-0 px-3 py-1.5 text-xs font-medium text-blue-600 dark:text-blue-400 bg-blue-50 dark:bg-blue-900/20 hover:bg-blue-100 dark:hover:bg-blue-900/40 border border-blue-200 dark:border-blue-800 rounded-lg transition whitespace-nowrap">
                                <?= __('booking.use_all') ?>
                            </button>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- 합계 -->
                    <div class="border-t border-gray-200 dark:border-zinc-700 pt-3 mt-2">
                        <div class="flex justify-between items-center">
                            <span class="text-base font-bold text-gray-900 dark:text-white"><?= __('staff_page.total_amount') ?></span>
                            <span id="sdGrandTotal" class="text-xl font-bold text-blue-600 dark:text-blue-400"></span>
                        </div>
                    </div>

                    <!-- 예상 적립금 -->
                    <?php
                    $_pointRate = (float)($siteSettings['point_rate'] ?? 0);
                    $_pointEnabled = ($siteSettings['point_enabled'] ?? '') === 'true';
                    $_pointName = $siteSettings['point_name'] ?? __('booking.points_default_name') ?? '적립금';
                    ?>
                    <?php if ($_pointEnabled && $_pointRate > 0): ?>
                    <div id="sdEarnPointsRow" class="hidden mt-2 p-2.5 bg-green-50 dark:bg-green-900/20 rounded-lg border border-green-200 dark:border-green-800">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-green-700 dark:text-green-300 font-medium flex items-center gap-1">
                                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8c-1.657 0-3 .895-3 2s1.343 2 3 2 3 .895 3 2-1.343 2-3 2m0-8c1.11 0 2.08.402 2.599 1M12 8V7m0 1v8m0 0v1m0-1c-1.11 0-2.08-.402-2.599-1M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <?= __('booking.expected_points') ?? '예상 적립' ?> (<?= htmlspecialchars($_pointName) ?> <?= $_pointRate ?>%)
                            </span>
                            <span id="sdEarnPoints" class="text-sm font-bold text-green-600 dark:text-green-400"></span>
                        </div>
                    </div>
                    <?php endif; ?>

                    <!-- 예약금 안내 -->
                    <?php if ($depositEnabled): ?>
                    <div id="sdDepositRow" class="hidden mt-2 p-2.5 bg-violet-50 dark:bg-violet-900/20 rounded-lg border border-violet-200 dark:border-violet-800">
                        <div class="flex justify-between items-center">
                            <span class="text-sm text-violet-700 dark:text-violet-300 font-medium"><?= __('booking.deposit_pay_now') ?></span>
                            <span id="sdDepositAmount" class="text-base font-bold text-violet-600 dark:text-violet-400"></span>
                        </div>
                        <p class="text-xs text-violet-500 dark:text-violet-400/70 mt-0.5"><?= __('booking.deposit_remaining_later') ?></p>
                    </div>
                    <?php endif; ?>
                </div>

                <!-- 고객 정보 입력 -->
                <div id="sdCustomerForm" class="p-4 border-t border-gray-200 dark:border-zinc-700 space-y-3">
                    <h3 class="text-sm font-semibold text-gray-900 dark:text-white"><?= __('booking.enter_info') ?></h3>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div>
                            <label class="block text-xs text-gray-500 dark:text-zinc-400 mb-1"><?= __('booking.form.customer_name') ?> <span class="text-red-500">*</span></label>
                            <input id="sdCustName" type="text" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                   value="<?= htmlspecialchars($isLoggedIn && $currentUser ? ($currentUser['name'] ?? '') : '') ?>">
                        </div>
                        <div>
                            <?php
                            $_sdPhoneVal = $isLoggedIn && $currentUser ? ($currentUser['phone'] ?? '') : '';
                            $phoneInputConfig = [
                                'name' => 'customer_phone',
                                'id' => 'sdCustPhone',
                                'label' => __('booking.form.customer_phone'),
                                'value' => $_sdPhoneVal,
                                'required' => true,
                                'show_label' => true,
                            ];
                            include BASE_PATH . '/resources/views/components/phone-input.php';
                            ?>
                        </div>
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-zinc-400 mb-1"><?= __('booking.form.customer_email') ?></label>
                        <input id="sdCustEmail" type="email" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                               value="<?= htmlspecialchars($isLoggedIn && $currentUser ? ($currentUser['email'] ?? '') : '') ?>">
                    </div>
                    <div>
                        <label class="block text-xs text-gray-500 dark:text-zinc-400 mb-1"><?= __('booking.form.notes') ?></label>
                        <textarea id="sdCustNotes" rows="2" class="w-full px-3 py-2 text-sm border border-gray-300 dark:border-zinc-600 rounded-lg bg-white dark:bg-zinc-700 text-gray-900 dark:text-white focus:ring-2 focus:ring-blue-500 focus:border-blue-500" placeholder="<?= __('booking.form.notes_placeholder') ?>"></textarea>
                    </div>
                </div>

                <!-- 예약 버튼 -->
                <div class="p-4 border-t border-gray-200 dark:border-zinc-700">
                    <button id="sdBookBtn" type="button" disabled
                            class="w-full py-3 text-white font-semibold rounded-lg transition shadow-lg bg-gray-300 dark:bg-zinc-600 text-gray-500 dark:text-zinc-400 cursor-not-allowed">
                        <?= __('staff_page.book_selected') ?>
                    </button>
                    <p id="sdBookHint" class="text-xs text-center text-gray-400 dark:text-zinc-500 mt-2"><?= __('staff_page.select_all_hint') ?></p>
                </div>
            </div>

            <!-- 예약 완료 메시지 -->
            <div id="sdBookingSuccess" class="hidden mt-4 p-6 bg-green-50 dark:bg-green-900/20 rounded-xl text-center">
                <svg class="w-12 h-12 text-green-500 mx-auto mb-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <h3 class="text-lg font-bold text-green-700 dark:text-green-400 mb-1"><?= __('booking.success') ?></h3>
                <p class="text-sm text-green-600 dark:text-green-500"><?= __('booking.success_desc') ?></p>
                <p class="mt-2 text-lg font-mono font-bold text-green-700 dark:text-green-400" id="sdReservationNumber"></p>
            </div>
        </div>
        <?php endif; ?>

        <!-- Back to list -->
        <div class="mt-8 text-center">
            <a href="<?= $baseUrl ?>/staff" class="text-gray-500 dark:text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 text-sm">
                &larr; <?= __('staff_page.back_to_list') ?>
            </a>
        </div>
    </main>

<script src="<?= $baseUrl ?>/assets/js/phone-input.js"></script>
<script>document.addEventListener('DOMContentLoaded', function() { if (typeof PhoneInput !== 'undefined') PhoneInput.init(); });</script>
<?php include BASE_PATH . '/resources/views/customer/staff-detail-js.php'; ?>

<?php
?>
