<?php
/**
 * 예약 상세 페이지
 */
include __DIR__ . '/_init.php';

$id = $reservationId ?? $_GET['id'] ?? null;
if (!$id) { header("Location: {$adminUrl}/reservations"); exit; }

$stmt = $pdo->prepare("SELECT * FROM {$prefix}reservations WHERE id = ?");
$stmt->execute([$id]);
$r = $stmt->fetch(PDO::FETCH_ASSOC);
if (!$r) { http_response_code(404); echo '<p>' . __('reservations.show_not_found') . '</p>'; exit; }

$serviceName = getServiceName($pdo, $prefix, $r['id']);

// 서비스명/카테고리명 DB 번역 캐시 로드
$_trLocale = $config['locale'] ?? 'ko';
$_trDefLocale = $siteSettings['default_language'] ?? 'ko';
$_trLocaleChain = array_unique(array_filter([$_trLocale, 'en', $_trDefLocale]));
$_trCache = [];
try {
    $_ph = implode(',', array_fill(0, count($_trLocaleChain), '?'));
    $_trSt = $pdo->prepare("SELECT lang_key, locale, content FROM {$prefix}translations WHERE locale IN ({$_ph}) AND (lang_key LIKE 'service.%.name' OR lang_key LIKE 'category.%.name')");
    $_trSt->execute(array_values($_trLocaleChain));
    while ($_tr = $_trSt->fetch(PDO::FETCH_ASSOC)) { $_trCache[$_tr['lang_key']][$_tr['locale']] = $_tr['content']; }
} catch (PDOException $e) {}

function translateDbName(string $type, ?string $id, string $fallback, array $cache, array $chain): string {
    if (!$id) return $fallback;
    $key = "{$type}.{$id}.name";
    if (isset($cache[$key])) {
        foreach ($chain as $lc) {
            if (!empty($cache[$key][$lc])) return $cache[$key][$lc];
        }
    }
    return $fallback;
}

// 번들 정보 조회
$bundleInfo = null;
try {
    $bdlStmt = $pdo->prepare("SELECT DISTINCT b.name, b.bundle_price
        FROM {$prefix}reservation_services rs
        JOIN {$prefix}service_bundles b ON rs.bundle_id = b.id
        WHERE rs.reservation_id = ? AND rs.bundle_id IS NOT NULL LIMIT 1");
    $bdlStmt->execute([$r['id']]);
    $bundleInfo = $bdlStmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    // service_bundles 테이블 미존재 시 무시
}

// 예약 서비스 상세 조회
try {
    $rsSvcStmt = $pdo->prepare("
        SELECT rs.service_id, COALESCE(rs.service_name, s.name) as service_name, rs.price, rs.duration, rs.bundle_id,
               s.image as service_image, s.description as service_description,
               s.category_id, c.name as category_name
        FROM {$prefix}reservation_services rs
        LEFT JOIN {$prefix}services s ON rs.service_id = s.id
        LEFT JOIN {$prefix}service_categories c ON s.category_id = c.id
        WHERE rs.reservation_id = ?
        ORDER BY rs.sort_order ASC
    ");
    $rsSvcStmt->execute([$r['id']]);
    $reservationServices = $rsSvcStmt->fetchAll(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $rsSvcStmt = $pdo->prepare("
        SELECT rs.service_id, s.name as service_name, rs.price, rs.duration, NULL as bundle_id,
               s.image as service_image, s.description as service_description,
               s.category_id, c.name as category_name
        FROM {$prefix}reservation_services rs
        LEFT JOIN {$prefix}services s ON rs.service_id = s.id
        LEFT JOIN {$prefix}service_categories c ON s.category_id = c.id
        WHERE rs.reservation_id = ?
    ");
    $rsSvcStmt->execute([$r['id']]);
    $reservationServices = $rsSvcStmt->fetchAll(PDO::FETCH_ASSOC);
}

$totalDuration = 0;
$totalServicePrice = 0;
foreach ($reservationServices as $rs) {
    $totalDuration += (int)$rs['duration'];
    $totalServicePrice += (float)$rs['price'];
}

// 스태프 정보
$staffName = '-';
$staffAvatar = '';
if (!empty($r['staff_id'])) {
    $staffStmt = $pdo->prepare("SELECT name, avatar FROM {$prefix}staff WHERE id = ?");
    $staffStmt->execute([$r['staff_id']]);
    $staffRow = $staffStmt->fetch(PDO::FETCH_ASSOC);
    if ($staffRow) {
        $staffName = $staffRow['name'];
        $staffAvatar = $staffRow['avatar'] ?? '';
    }
}

// 회원 등급 및 적립금 조회
$userGrade = null;
$userPoints = 0;
$userDetail = null;
if (!empty($r['user_id'])) {
    $ugStmt = $pdo->prepare("SELECT u.points_balance, u.profile_image, u.birth_date, u.gender, u.company, u.blog, u.created_at as member_since, u.last_login_at,
            g.name as grade_name, g.discount_rate, g.point_rate, g.color as grade_color
        FROM {$prefix}users u
        LEFT JOIN {$prefix}member_grades g ON u.grade_id = g.id
        WHERE u.id = ?");
    $ugStmt->execute([$r['user_id']]);
    $ugRow = $ugStmt->fetch(PDO::FETCH_ASSOC);
    if ($ugRow) {
        $userPoints = (float)($ugRow['points_balance'] ?? 0);
        $userDetail = $ugRow;
        if (!empty($ugRow['grade_name'])) {
            $userGrade = [
                'name' => $ugRow['grade_name'],
                'discount_rate' => (float)($ugRow['discount_rate'] ?? 0),
                'point_rate' => (float)($ugRow['point_rate'] ?? 0),
                'color' => $ugRow['grade_color'] ?? '#6B7280',
            ];
        }
    }
    // 이 고객의 총 예약 횟수
    $visitStmt = $pdo->prepare("SELECT COUNT(*) as total, SUM(CASE WHEN status = 'completed' THEN 1 ELSE 0 END) as completed, SUM(CASE WHEN status = 'no_show' THEN 1 ELSE 0 END) as no_show FROM {$prefix}reservations WHERE user_id = ?");
    $visitStmt->execute([$r['user_id']]);
    $visitStats = $visitStmt->fetch(PDO::FETCH_ASSOC);
}

// 요일 계산
$weekdays = __('reservations.weekdays');
$dateTs = strtotime($r['reservation_date']);
$dayOfWeek = $weekdays[(int)date('w', $dateTs)];

$pageTitle = __('reservations.detail') . ' - ' . ($r['reservation_number'] ?? $id);

// 관리자 메모 목록 조회
$adminMemos = [];
if (!empty($r['user_id'])) {
    $memoStmt = $pdo->prepare("SELECT m.id, m.content, m.reservation_id, m.reservation_number, m.admin_id, m.created_at, a.name as admin_name
        FROM {$prefix}admin_memos m
        LEFT JOIN {$prefix}admins a ON m.admin_id = a.id
        WHERE m.user_id = ?
        ORDER BY m.created_at DESC
        LIMIT 20");
    $memoStmt->execute([$r['user_id']]);
    $adminMemos = $memoStmt->fetchAll(PDO::FETCH_ASSOC);
}

$appUrl = $baseUrl; // config['app_url'] — 이미지 URL 조합에 사용
include __DIR__ . '/_head.php';
?>

<!-- 상단 헤더 -->
<div class="flex items-center justify-between mb-6">
    <div class="flex items-center gap-3">
        <a href="<?= $adminUrl ?>/reservations" class="p-2 text-zinc-400 hover:text-zinc-600 dark:hover:text-zinc-200 hover:bg-zinc-200 dark:hover:bg-zinc-700 rounded-lg transition">
            <svg class="w-5 h-5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M15 19l-7-7 7-7"/></svg>
        </a>
        <div>
            <h2 class="text-2xl font-bold text-zinc-900 dark:text-white"><?= __('reservations.detail') ?></h2>
            <p class="text-sm text-zinc-500 font-mono"><?= htmlspecialchars($r['reservation_number'] ?? '') ?></p>
        </div>
    </div>
    <div class="flex gap-2">
        <?php if ($r['status'] === 'pending'): ?>
        <button onclick="changeStatus('<?= $id ?>', 'confirm')" class="px-4 py-2 bg-green-600 hover:bg-green-700 text-white rounded-lg text-sm transition">
            <?= __('reservations.actions.confirm') ?>
        </button>
        <button onclick="changeStatus('<?= $id ?>', 'no-show')" class="px-4 py-2 bg-zinc-600 hover:bg-zinc-700 text-white rounded-lg text-sm transition">
            <?= __('reservations.actions.no_show') ?>
        </button>
        <?php endif; ?>
        <?php if ($r['status'] === 'confirmed'): ?>
        <button onclick="changeStatus('<?= $id ?>', 'complete')" class="px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm transition">
            <?= __('reservations.actions.complete') ?>
        </button>
        <button onclick="changeStatus('<?= $id ?>', 'no-show')" class="px-4 py-2 bg-zinc-600 hover:bg-zinc-700 text-white rounded-lg text-sm transition">
            <?= __('reservations.actions.no_show') ?>
        </button>
        <?php endif; ?>
        <?php if (in_array($r['status'], ['pending', 'confirmed'])): ?>
        <button onclick="changeStatus('<?= $id ?>', 'cancel')" class="px-4 py-2 bg-red-600 hover:bg-red-700 text-white rounded-lg text-sm transition">
            <?= __('reservations.actions.cancel') ?>
        </button>
        <?php endif; ?>
    </div>
</div>

<!-- 상태 타임라인 -->
<div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-4 mb-6">
    <div class="flex items-center justify-between">
        <?php
        $steps = [
            ['pending', __('reservations.show_step_pending'), 'bg-yellow-400'],
            ['confirmed', __('reservations.show_step_confirmed'), 'bg-blue-400'],
            ['completed', __('reservations.show_step_completed'), 'bg-green-400'],
        ];
        $statusOrder = ['pending' => 0, 'confirmed' => 1, 'completed' => 2, 'cancelled' => -1, 'no_show' => -1];
        $currentStep = $statusOrder[$r['status']] ?? -1;
        $isCancelled = in_array($r['status'], ['cancelled', 'no_show']);

        foreach ($steps as $i => [$stepKey, $stepLabel, $stepColor]):
            $stepIdx = $statusOrder[$stepKey];
            $isActive = $stepIdx <= $currentStep && !$isCancelled;
            $isCurrent = $stepKey === $r['status'];
        ?>
        <div class="flex items-center <?= $i < count($steps) - 1 ? 'flex-1' : '' ?>">
            <div class="flex flex-col items-center">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold
                    <?= $isCurrent ? $stepColor . ' text-white' : ($isActive ? 'bg-zinc-300 dark:bg-zinc-600 text-white' : 'bg-zinc-100 dark:bg-zinc-700 text-zinc-400') ?>">
                    <?php if ($isActive && !$isCurrent): ?>
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="3" d="M5 13l4 4L19 7"/></svg>
                    <?php else: ?>
                        <?= $i + 1 ?>
                    <?php endif; ?>
                </div>
                <span class="text-[10px] mt-1 <?= $isCurrent ? 'font-bold text-zinc-900 dark:text-white' : 'text-zinc-400' ?>"><?= $stepLabel ?></span>
            </div>
            <?php if ($i < count($steps) - 1): ?>
            <div class="flex-1 h-0.5 mx-2 <?= $isActive && $stepIdx < $currentStep ? 'bg-zinc-300 dark:bg-zinc-600' : 'bg-zinc-100 dark:bg-zinc-700' ?>"></div>
            <?php endif; ?>
        </div>
        <?php endforeach; ?>

        <?php if ($isCancelled): ?>
        <div class="flex items-center">
            <div class="flex-1 h-0.5 mx-2 bg-zinc-100 dark:bg-zinc-700"></div>
            <div class="flex flex-col items-center">
                <div class="w-8 h-8 rounded-full flex items-center justify-center text-xs font-bold bg-red-500 text-white">
                    <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                </div>
                <span class="text-[10px] mt-1 font-bold text-red-500"><?= $r['status'] === 'cancelled' ? __('reservations.show_step_cancelled') : __('reservations.show_step_noshow') ?></span>
            </div>
        </div>
        <?php endif; ?>
    </div>
</div>

<div class="grid grid-cols-1 lg:grid-cols-3 gap-6">
    <!-- 좌측: 예약/고객 정보 -->
    <div class="lg:col-span-2 space-y-6">
        <!-- 예약 정보 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('reservations.show_reservation_info') ?> <span class="text-sm font-mono text-zinc-400 font-normal ml-2"><?= htmlspecialchars($r['reservation_number'] ?? '') ?></span></h3>
                <?php if (in_array($r['status'], ['pending', 'confirmed'])): ?>
                <button onclick="openDateTimeEditModal()" class="px-3 py-1.5 text-xs font-medium text-zinc-600 dark:text-zinc-300 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                    <?= __('reservations.show_edit_datetime') ?? '일시 수정' ?>
                </button>
                <?php endif; ?>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('reservations.show_status') ?></p>
                    <div><?= statusBadge($r['status']) ?></div>
                </div>
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('reservations.show_service') ?></p>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">
                        <?= htmlspecialchars($serviceName) ?>
                        <?php if ($bundleInfo): ?>
                        <span class="ml-1 inline-flex items-center px-2 py-0.5 rounded-full text-[10px] font-medium bg-purple-100 text-purple-700 dark:bg-purple-900/30 dark:text-purple-400">
                            <svg class="w-3 h-3 mr-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M20 7l-8-4-8 4m16 0l-8 4m8-4v10l-8 4m0-10L4 7m8 4v10M4 7v10l8 4"/></svg>
                            <?= htmlspecialchars($bundleInfo['name']) ?>
                        </span>
                        <?php endif; ?>
                    </p>
                </div>
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('reservations.show_source') ?></p>
                    <?php
                    $srcLabel = match($r['source'] ?? 'online') {
                        'walk_in' => [__('reservations.show_source_walkin'), 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400'],
                        'phone' => [__('reservations.show_source_phone'), 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400'],
                        'staff_page' => [__('reservations.show_source_staff_page'), 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400'],
                        default => [__('reservations.show_source_online'), 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'],
                    };
                    ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium <?= $srcLabel[1] ?>"><?= $srcLabel[0] ?></span>
                </div>
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('reservations.show_date') ?></p>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= $r['reservation_date'] ?> (<?= $dayOfWeek ?>)</p>
                </div>
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('reservations.show_time_duration') ?></p>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= substr($r['start_time'], 0, 5) ?> ~ <?= substr($r['end_time'] ?? '', 0, 5) ?> <span class="text-xs text-zinc-400">(<?= $totalDuration ?>분)</span></p>
                </div>
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('reservations.show_payment_status') ?></p>
                    <?php
                    $paidAmt = (float)($r['paid_amount'] ?? 0);
                    $finalAmt = (float)($r['final_amount'] ?? 0);
                    $pSt = $r['payment_status'] ?? 'unpaid';
                    $pLabel = match($pSt) {
                        'paid' => [__('reservations.show_pay_paid'), 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'],
                        'partial' => [__('reservations.show_pay_partial'), 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'],
                        default => [__('reservations.show_pay_unpaid'), 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400'],
                    };
                    ?>
                    <?php
                    // 예약금 설정 확인
                    $_admDepEnabled = ($settings['service_deposit_enabled'] ?? '0') === '1';
                    if ($pSt === 'partial' && $_admDepEnabled && $paidAmt > 0): ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400">
                        <?= __('booking.payment.deposit') ?? '예약금' ?> <?= formatPrice($paidAmt) ?> <?= __('booking.payment.paid') ?? '결제' ?>
                    </span>
                    <?php else: ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium <?= $pLabel[1] ?>"><?= $pLabel[0] ?></span>
                    <?php if ($pSt === 'partial'): ?>
                    <span class="text-xs text-zinc-400 ml-1"><?= formatPrice($paidAmt) ?> / <?= formatPrice($finalAmt) ?></span>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 담당 스태프 -->
            <?php
                $isDesignation = !empty($r['staff_id']) && (float)($r['designation_fee'] ?? 0) > 0;
                $hasStaff = !empty($r['staff_id']) && $staffName !== '-';
            ?>
            <?php $canChangeStaff = in_array($r['status'], ['pending', 'confirmed', 'completed']); ?>
            <div id="staffCard" class="mt-4 p-3 rounded-lg flex items-center gap-3 <?= $hasStaff ? ($isDesignation ? 'bg-violet-50 dark:bg-violet-900/10 border border-violet-200 dark:border-violet-800/30' : 'bg-emerald-50 dark:bg-emerald-900/10 border border-emerald-200 dark:border-emerald-800/30') : 'bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700' ?>">
                <?php if ($hasStaff): ?>
                <?php if (!empty($staffAvatar)):
                    $staffAvatarUrl = str_starts_with($staffAvatar, 'http') ? $staffAvatar : $appUrl . (str_starts_with($staffAvatar, '/') ? $staffAvatar : '/storage/' . $staffAvatar);
                ?>
                <img src="<?= htmlspecialchars($staffAvatarUrl) ?>" alt="<?= htmlspecialchars($staffName) ?>"
                     class="w-10 h-10 rounded-full object-cover flex-shrink-0 border-2 <?= $isDesignation ? 'border-violet-300 dark:border-violet-600' : 'border-emerald-300 dark:border-emerald-600' ?>">
                <?php else: ?>
                <div class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center border-2 <?= $isDesignation ? 'bg-violet-100 dark:bg-violet-900/30 border-violet-300 dark:border-violet-600' : 'bg-emerald-100 dark:bg-emerald-900/30 border-emerald-300 dark:border-emerald-600' ?>">
                    <svg class="w-5 h-5 <?= $isDesignation ? 'text-violet-500' : 'text-emerald-500' ?>" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <?php endif; ?>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <a href="<?= $adminUrl ?>/staff" class="text-sm font-semibold text-zinc-900 dark:text-white hover:text-violet-600 dark:hover:text-violet-400 hover:underline transition"><?= htmlspecialchars($staffName) ?></a>
                        <?php if ($isDesignation): ?>
                        <span class="px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400"><?= __('reservations.show_designation') ?></span>
                        <span class="text-xs text-violet-600 dark:text-violet-400 font-medium"><?= formatPrice((float)$r['designation_fee']) ?></span>
                        <?php else: ?>
                        <span class="px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400"><?= __('reservations.show_assignment') ?></span>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= __('reservations.show_staff_label') ?></p>
                </div>
                <?php if ($canChangeStaff): ?>
                <div class="flex gap-1.5 flex-shrink-0">
                    <button type="button" onclick="openStaffChangePanel('assign')" class="px-2.5 py-1.5 text-xs font-medium text-emerald-600 hover:bg-emerald-100 dark:hover:bg-emerald-900/20 rounded-lg border border-emerald-300 dark:border-emerald-700 transition"><?= __('reservations.show_staff_assign_btn') ?></button>
                    <button type="button" onclick="openStaffChangePanel('designate')" class="px-2.5 py-1.5 text-xs font-medium text-violet-600 hover:bg-violet-100 dark:hover:bg-violet-900/20 rounded-lg border border-violet-300 dark:border-violet-700 transition"><?= __('reservations.show_staff_designate_btn') ?></button>
                </div>
                <?php endif; ?>
                <?php else: ?>
                <div class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center border-2 bg-zinc-200 dark:bg-zinc-700 border-zinc-300 dark:border-zinc-600">
                    <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-zinc-400"><?= __('reservations.show_unassigned') ?></span>
                        <span class="px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400"><?= __('reservations.show_unassigned') ?></span>
                    </div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= __('reservations.show_staff_label') ?></p>
                </div>
                <?php if ($canChangeStaff): ?>
                <div class="flex gap-1.5 flex-shrink-0">
                    <button type="button" onclick="openStaffChangePanel('assign')" class="px-2.5 py-1.5 text-xs font-medium text-emerald-600 hover:bg-emerald-100 dark:hover:bg-emerald-900/20 rounded-lg border border-emerald-300 dark:border-emerald-700 transition"><?= __('reservations.show_staff_assign_btn') ?></button>
                    <button type="button" onclick="openStaffChangePanel('designate')" class="px-2.5 py-1.5 text-xs font-medium text-violet-600 hover:bg-violet-100 dark:hover:bg-violet-900/20 rounded-lg border border-violet-300 dark:border-violet-700 transition"><?= __('reservations.show_staff_designate_btn') ?></button>
                </div>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            <!-- 스태프 변경 패널 (토글) -->
            <?php if ($canChangeStaff): ?>
            <div id="staffChangePanel" class="hidden mt-2 p-3 bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                <div class="flex items-center justify-between mb-2">
                    <p id="staffPanelTitle" class="text-xs font-semibold text-zinc-700 dark:text-zinc-300"><?= __('reservations.show_staff_assign_title') ?></p>
                    <button type="button" onclick="closeStaffChangePanel()" class="p-1 text-zinc-400 hover:text-zinc-600 rounded transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div id="staffChangeList" class="space-y-1.5 max-h-48 overflow-y-auto">
                    <div class="text-center py-3 text-zinc-400 text-xs"><?= __('reservations.show_staff_loading') ?></div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($r['status'] === 'cancelled' && !empty($r['cancel_reason'])): ?>
            <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                <p class="text-xs text-red-500 mb-1"><?= __('reservations.show_cancel_reason') ?></p>
                <p class="text-sm text-red-800 dark:text-red-300"><?= htmlspecialchars($r['cancel_reason']) ?></p>
                <p class="text-xs text-red-400 mt-1"><?= $r['cancelled_at'] ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- 고객 정보 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('reservations.show_customer_info') ?></h3>
                <div class="flex items-center gap-2">
                    <?php if (!empty($r['user_id'])): ?>
                    <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400"><?= __('reservations.show_customer_member') ?></span>
                    <?php endif; ?>
                    <button onclick="openCustomerEditModal()" class="p-1.5 text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700" title="<?= __('reservations.show_edit_contact') ?? '연락처 수정' ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    </button>
                </div>
            </div>
            <?php
            // 국제전화 포맷 함수
            if (!function_exists('_admFmtPhone')) {
                function _admFmtPhone($phone) {
                    if (empty($phone)) return '';
                    $d = preg_replace('/\D/', '', $phone);
                    if (str_starts_with($d, '0')) { $l = substr($d, 1); if (preg_match('/^(10|11|16|17|18|19)(\d{4})(\d{4})$/', $l, $m)) return '+82 '.$m[1].'-'.$m[2].'-'.$m[3]; if (preg_match('/^(2)(\d{3,4})(\d{4})$/', $l, $m)) return '+82 '.$m[1].'-'.$m[2].'-'.$m[3]; if (preg_match('/^(\d{2})(\d{3,4})(\d{4})$/', $l, $m)) return '+82 '.$m[1].'-'.$m[2].'-'.$m[3]; return '+82 '.$l; }
                    if (str_starts_with($d, '82')) { $l = substr($d, 2); if (str_starts_with($l, '0')) $l = substr($l, 1); if (preg_match('/^(10|11|16|17|18|19)(\d{4})(\d{4})$/', $l, $m)) return '+82 '.$m[1].'-'.$m[2].'-'.$m[3]; return '+82 '.$l; }
                    if (str_starts_with($d, '81')) { $l = substr($d, 2); if (str_starts_with($l, '0')) $l = substr($l, 1); if (preg_match('/^(\d{2,3})(\d{4})(\d{4})$/', $l, $m)) return '+81 '.$m[1].'-'.$m[2].'-'.$m[3]; return '+81 '.$l; }
                    return '+'.$d;
                }
            }
            $_fmtPhone = _admFmtPhone($r['customer_phone']);
            ?>
            <!-- 프로필 헤더 -->
            <div class="flex items-start gap-4 mb-4">
                <!-- 아바타 -->
                <div class="shrink-0">
                    <?php if (!empty($r['user_id']) && $userDetail && !empty($userDetail['profile_image'])):
                        $pi = $userDetail['profile_image'];
                        $profUrl = str_starts_with($pi, 'http') ? $pi : $appUrl . (str_starts_with($pi, '/') ? $pi : '/storage/' . $pi);
                    ?>
                    <img src="<?= htmlspecialchars($profUrl) ?>" alt="" class="w-24 h-24 rounded-full object-cover border-4 border-white dark:border-zinc-700 shadow-lg">
                    <?php else: ?>
                    <div class="w-24 h-24 rounded-full bg-gradient-to-br from-blue-500 to-purple-500 flex items-center justify-center text-white font-bold text-3xl border-4 border-white dark:border-zinc-700 shadow-lg">
                        <?= mb_substr($r['customer_name'], 0, 1) ?>
                    </div>
                    <?php endif; ?>
                </div>
                <!-- 이름 / 전화 / 이메일 3열 -->
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2 mb-2">
                        <p class="text-lg font-bold text-zinc-900 dark:text-white"><?= htmlspecialchars($r['customer_name']) ?></p>
                        <?php if (!empty($r['user_id']) && $userDetail): ?>
                        <?php if ($userGrade): ?>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-semibold text-white" style="background-color: <?= htmlspecialchars($userGrade['color']) ?>"><?= htmlspecialchars($userGrade['name']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($visitStats)): ?>
                        <span class="text-xs text-zinc-500"><?= __('reservations.show_customer_visit_count', ['count' => (int)$visitStats['completed']]) ?></span>
                        <?php if ((int)$visitStats['no_show'] > 0): ?>
                        <span class="text-xs text-red-400"><?= __('reservations.show_customer_noshow_count', ['count' => (int)$visitStats['no_show']]) ?></span>
                        <?php endif; ?>
                        <?php endif; ?>
                        <?php else: ?>
                        <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400"><?= __('reservations.show_customer_guest') ?></span>
                        <?php endif; ?>
                    </div>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-2 text-sm">
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-zinc-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 5a2 2 0 012-2h3.28a1 1 0 01.948.684l1.498 4.493a1 1 0 01-.502 1.21l-2.257 1.13a11.042 11.042 0 005.516 5.516l1.13-2.257a1 1 0 011.21-.502l4.493 1.498a1 1 0 01.684.949V19a2 2 0 01-2 2h-1C9.716 21 3 14.284 3 6V5z"/></svg>
                            <a href="tel:<?= htmlspecialchars($r['customer_phone']) ?>" class="text-blue-600 dark:text-blue-400 hover:underline font-mono"><?= htmlspecialchars($_fmtPhone) ?></a>
                        </div>
                        <?php if (!empty($r['customer_email'])): ?>
                        <div class="flex items-center gap-2">
                            <svg class="w-4 h-4 text-zinc-400 shrink-0" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 8l7.89 5.26a2 2 0 002.22 0L21 8M5 19h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                            <a href="mailto:<?= htmlspecialchars($r['customer_email']) ?>" class="text-blue-600 dark:text-blue-400 hover:underline break-all"><?= htmlspecialchars($r['customer_email']) ?></a>
                        </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <?php if (!empty($userDetail['birth_date'])): ?>
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('reservations.show_customer_birth') ?></p>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= $userDetail['birth_date'] ?>
                        <?php $age = (int)date('Y') - (int)substr($userDetail['birth_date'], 0, 4); ?>
                        <span class="text-xs text-zinc-400">(<?= __('reservations.show_customer_age', ['age' => $age]) ?>)</span>
                    </p>
                </div>
                <?php endif; ?>
                <?php if (!empty($userDetail['gender'])): ?>
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('reservations.show_customer_gender') ?></p>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= match($userDetail['gender']) { 'male' => __('reservations.show_customer_gender_male'), 'female' => __('reservations.show_customer_gender_female'), default => __('reservations.show_customer_gender_other') } ?></p>
                </div>
                <?php endif; ?>
                <?php if ($userGrade): ?>
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('reservations.show_customer_grade') ?></p>
                    <p class="text-sm font-medium">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold text-white" style="background-color: <?= htmlspecialchars($userGrade['color']) ?>">
                            <?= htmlspecialchars($userGrade['name']) ?>
                        </span>
                        <?php if ($userGrade['discount_rate'] > 0): ?>
                        <span class="ml-1 text-xs text-zinc-500 dark:text-zinc-400"><?= __('reservations.show_customer_discount', ['rate' => $userGrade['discount_rate']]) ?></span>
                        <?php endif; ?>
                    </p>
                </div>
                <?php endif; ?>
                <?php if (!empty($r['user_id'])): ?>
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('reservations.pos_points_balance_label', ['name' => get_points_name()]) ?></p>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">
                        <?= formatPrice($userPoints) ?>
                        <?php if ($userGrade && $userGrade['point_rate'] > 0): ?>
                        <span class="ml-1 text-xs text-zinc-500 dark:text-zinc-400">(<?= __('reservations.show_customer_points_earn', ['rate' => $userGrade['point_rate']]) ?>)</span>
                        <?php endif; ?>
                    </p>
                </div>
                <?php endif; ?>
                <?php if (!empty($userDetail['member_since'])): ?>
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1"><?= __('reservations.show_customer_joined') ?></p>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= date('Y-m-d', strtotime($userDetail['member_since'])) ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- 고객 요구사항 -->
            <?php if (!empty($r['notes'])): ?>
            <div class="mt-4 p-3 bg-amber-50 dark:bg-amber-900/10 rounded-lg border border-amber-200 dark:border-amber-800/30">
                <p class="text-xs font-medium text-amber-600 dark:text-amber-400 mb-1 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                    <?= __('reservations.show_customer_request') ?>
                </p>
                <p class="text-sm text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap"><?= htmlspecialchars($r['notes']) ?></p>
            </div>
            <?php endif; ?>

            <!-- 관리자 메모 (예약건) -->
            <?php if (!empty($r['admin_notes'])): ?>
            <div class="mt-3 p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700">
                <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    <?= __('reservations.show_admin_notes') ?>
                </p>
                <p class="text-sm text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap"><?= htmlspecialchars($r['admin_notes']) ?></p>
            </div>
            <?php endif; ?>
        </div>
        <!-- 서비스 상세 정보 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('reservations.show_service_detail') ?></h3>
                <span id="showSvcSummary" class="text-sm text-zinc-500 dark:text-zinc-400"><?= __('reservations.show_service_count_duration', ['count' => count($reservationServices), 'min' => $totalDuration]) ?></span>
            </div>
            <?php if (empty($reservationServices)): ?>
            <p class="text-sm text-zinc-400 text-center py-4"><?= __('reservations.show_no_services') ?></p>
            <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($reservationServices as $idx => $rs):
                    $svcImg = $rs['service_image'] ?? '';
                    $hasImg = !empty($svcImg);
                    $isBundled = !empty($rs['bundle_id']);
                ?>
                <div class="flex items-start gap-3 p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg" data-svc-row data-svc-id="<?= htmlspecialchars($rs['service_id']) ?>" data-svc-price="<?= (float)$rs['price'] ?>" data-svc-duration="<?= (int)$rs['duration'] ?>">
                    <?php if ($hasImg): ?>
                    <img src="<?= htmlspecialchars($appUrl . '/' . $svcImg) ?>" alt=""
                         class="w-14 h-14 rounded-lg object-cover flex-shrink-0 border border-zinc-200 dark:border-zinc-700">
                    <?php else: ?>
                    <div class="w-14 h-14 rounded-lg bg-zinc-200 dark:bg-zinc-700 flex-shrink-0 flex items-center justify-center">
                        <svg class="w-6 h-6 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <?php endif; ?>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white truncate"><?= htmlspecialchars(translateDbName('service', $rs['service_id'] ?? null, $rs['service_name'], $_trCache, $_trLocaleChain)) ?></p>
                            <?php if ($rs['category_name']): ?>
                            <span class="px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400 flex-shrink-0"><?= htmlspecialchars(translateDbName('category', $rs['category_id'] ?? null, $rs['category_name'], $_trCache, $_trLocaleChain)) ?></span>
                            <?php endif; ?>
                            <?php if ($isBundled): ?>
                            <span class="px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 flex-shrink-0"><?= __('reservations.show_bundle') ?></span>
                            <?php endif; ?>
                        </div>
                        <?php if (!empty($rs['service_description'])): ?>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400 line-clamp-2 mb-1"><?= htmlspecialchars($rs['service_description']) ?></p>
                        <?php endif; ?>
                        <div class="flex items-center gap-3 text-xs text-zinc-500 dark:text-zinc-400">
                            <span class="flex items-center gap-1">
                                <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                                <?= (int)$rs['duration'] ?>분
                            </span>
                            <span class="font-semibold text-zinc-900 dark:text-white"><?= formatPrice((float)$rs['price']) ?></span>
                        </div>
                    </div>
                    <?php if (in_array($r['status'], ['pending', 'confirmed'])): ?>
                    <button type="button" onclick="removeShowService('<?= htmlspecialchars($r['id']) ?>', '<?= htmlspecialchars($rs['service_id']) ?>', this)"
                            class="flex-shrink-0 p-1.5 text-red-400 hover:text-red-600 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition" title="<?= __('reservations.pos_remove_service') ?>">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16"/></svg>
                    </button>
                    <?php endif; ?>
                </div>
                <?php endforeach; ?>
                <?php $designFee = (float)($r['designation_fee'] ?? 0); ?>
                <?php if ($designFee > 0): ?>
                <div class="flex items-start gap-3 p-3 bg-blue-50 dark:bg-blue-900/10 rounded-lg border border-blue-200 dark:border-blue-800/30">
                    <?php if (!empty($staffAvatar)): ?>
                    <?php $staffAvatarUrl = str_starts_with($staffAvatar, 'http') ? $staffAvatar : $appUrl . (str_starts_with($staffAvatar, '/') ? $staffAvatar : '/storage/' . $staffAvatar); ?>
                    <img src="<?= htmlspecialchars($staffAvatarUrl) ?>" alt="<?= htmlspecialchars($staffName) ?>"
                         class="w-14 h-14 rounded-full object-cover flex-shrink-0 border-2 border-blue-300 dark:border-blue-600">
                    <?php else: ?>
                    <div class="w-14 h-14 rounded-full bg-blue-100 dark:bg-blue-900/30 flex-shrink-0 flex items-center justify-center border-2 border-blue-300 dark:border-blue-600">
                        <svg class="w-6 h-6 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                    </div>
                    <?php endif; ?>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white"><?= __('reservations.show_designation_fee') ?></p>
                            <span class="px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 flex-shrink-0"><?= __('reservations.show_designation') ?></span>
                        </div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400"><?= __('reservations.show_designation_staff', ['name' => htmlspecialchars($staffName)]) ?></p>
                        <div class="flex items-center gap-3 text-xs mt-1">
                            <span class="font-semibold text-blue-600 dark:text-blue-400"><?= formatPrice($designFee) ?></span>
                        </div>
                    </div>
                </div>
                <?php endif; ?>
            </div>
            <!-- 서비스 합계 -->
            <div class="mt-3 pt-3 border-t border-zinc-200 dark:border-zinc-700 space-y-1.5 text-sm">
                <div class="flex items-center justify-between">
                    <span class="text-zinc-500 dark:text-zinc-400"><?= __('reservations.show_service_total_label', ['count' => count($reservationServices), 'min' => $totalDuration]) ?></span>
                    <span class="text-zinc-900 dark:text-white"><?= formatPrice($totalServicePrice) ?></span>
                </div>
                <?php if ($designFee > 0): ?>
                <div class="flex items-center justify-between">
                    <span class="text-zinc-500 dark:text-zinc-400"><?= __('reservations.show_designation_fee') ?></span>
                    <span class="text-zinc-900 dark:text-white"><?= formatPrice($designFee) ?></span>
                </div>
                <div class="flex items-center justify-between pt-1.5 border-t border-zinc-200 dark:border-zinc-700">
                    <span class="font-semibold text-zinc-900 dark:text-white"><?= __('reservations.show_total') ?></span>
                    <span class="font-bold text-zinc-900 dark:text-white"><?= formatPrice($totalServicePrice + $designFee) ?></span>
                </div>
                <?php else: ?>
                <div class="flex items-center justify-between">
                    <span class="font-semibold text-zinc-900 dark:text-white"><?= __('reservations.show_total') ?></span>
                    <span class="font-bold text-zinc-900 dark:text-white"><?= formatPrice($totalServicePrice) ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- 서비스 추가 버튼 + 영역 -->
            <?php if (in_array($r['status'], ['pending', 'confirmed'])): ?>
            <button type="button" id="showAddServiceToggleBtn" onclick="toggleShowAddService()" class="mt-3 w-full py-2 flex items-center justify-center gap-1.5 text-sm font-medium text-blue-600 hover:bg-blue-50 dark:hover:bg-blue-900/20 border border-dashed border-blue-300 dark:border-blue-700 rounded-lg transition">
                <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                <?= __('reservations.show_add_service') ?>
            </button>
            <div id="showAddServiceArea" class="hidden mt-3 p-4 bg-blue-50 dark:bg-blue-900/10 rounded-lg border border-blue-200 dark:border-blue-800/30">
                <div class="flex items-center justify-between mb-3">
                    <p class="text-sm font-semibold text-blue-700 dark:text-blue-400"><?= __('reservations.show_add_service_title') ?></p>
                    <button type="button" onclick="toggleShowAddService()" class="p-1 text-zinc-400 hover:text-zinc-600 rounded transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div id="showAddServiceList" class="space-y-1.5 max-h-48 overflow-y-auto mb-3">
                    <div class="text-center py-3 text-zinc-400 text-xs"><?= __('reservations.show_staff_loading') ?></div>
                </div>
                <button type="button" id="showAddServiceBtn" onclick="submitShowAddService()" disabled
                        class="w-full py-2 bg-blue-600 hover:bg-blue-700 text-white rounded-lg text-sm font-bold transition disabled:opacity-50"><?= __('reservations.show_add_service_btn') ?></button>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 우측 사이드바 -->
    <div class="space-y-6">
        <!-- 결제 정보 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <?php
            // 영수증 URL 조회
            $_admReceiptUrl = null;
            $_admPayStmt = $pdo->prepare("SELECT receipt_url, payment_key FROM {$prefix}payments WHERE reservation_id = ? AND status = 'paid' ORDER BY created_at DESC LIMIT 1");
            $_admPayStmt->execute([$r['id']]);
            $_admPayRow = $_admPayStmt->fetch(PDO::FETCH_ASSOC);
            $_admReceiptUrl = $_admPayRow['receipt_url'] ?? null;
            // 없으면 Stripe에서 실시간 조회
            if (!$_admReceiptUrl && !empty($_admPayRow['payment_key'])) {
                try {
                    require_once BASE_PATH . '/rzxlib/Modules/Payment/PaymentManager.php';
                    require_once BASE_PATH . '/rzxlib/Modules/Payment/Gateways/StripeGateway.php';
                    require_once BASE_PATH . '/rzxlib/Modules/Payment/Contracts/PaymentGatewayInterface.php';
                    require_once BASE_PATH . '/rzxlib/Modules/Payment/DTO/PaymentResult.php';
                    require_once BASE_PATH . '/rzxlib/Modules/Payment/DTO/RefundResult.php';
                    $__amgr = new \RzxLib\Modules\Payment\PaymentManager($pdo, $prefix);
                    if ($__amgr->isEnabled()) {
                        $__agw = $__amgr->gateway();
                        $__as = $__agw->getTransaction($_admPayRow['payment_key']);
                        $__api = $__as['payment_intent'] ?? null;
                        if ($__api) {
                            $__ap = $__agw->getTransaction($__api);
                            $__ac = $__ap['latest_charge'] ?? ($__ap['charges']['data'][0] ?? null);
                            if (is_string($__ac)) $__ac = $__agw->getTransaction($__ac);
                            $_admReceiptUrl = $__ac['receipt_url'] ?? null;
                            if ($_admReceiptUrl) $pdo->prepare("UPDATE {$prefix}payments SET receipt_url = ? WHERE payment_key = ?")->execute([$_admReceiptUrl, $_admPayRow['payment_key']]);
                        }
                    }
                } catch (\Throwable $e) {}
            }
            ?>
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white"><?= __('reservations.show_payment_info') ?></h3>
                <div class="flex items-center gap-1">
                    <?php if ($_admReceiptUrl): ?>
                    <a href="<?= htmlspecialchars($_admReceiptUrl) ?>" target="_blank" title="<?= __('booking.payment.receipt') ?? '영수증' ?>"
                       class="p-1.5 text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12h6m-6 4h6m2 5H7a2 2 0 01-2-2V5a2 2 0 012-2h5.586a1 1 0 01.707.293l5.414 5.414a1 1 0 01.293.707V19a2 2 0 01-2 2z"/></svg>
                    </a>
                    <?php endif; ?>
                    <button onclick="window.print()" title="<?= __('booking.payment.print') ?? '인쇄' ?>"
                            class="p-1.5 text-zinc-400 hover:text-blue-600 dark:hover:text-blue-400 transition rounded-lg hover:bg-zinc-100 dark:hover:bg-zinc-700">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M17 17h2a2 2 0 002-2v-4a2 2 0 00-2-2H5a2 2 0 00-2 2v4a2 2 0 002 2h2m2 4h6a2 2 0 002-2v-4a2 2 0 00-2-2H9a2 2 0 00-2 2v4a2 2 0 002 2zm8-12V5a2 2 0 00-2-2H9a2 2 0 00-2 2v4h10z"/></svg>
                    </button>
                </div>
            </div>
            <div class="space-y-3">
                <!-- 서비스 항목별 금액 -->
                <?php foreach ($reservationServices as $rs): ?>
                <div class="flex justify-between text-sm">
                    <span class="text-zinc-500 dark:text-zinc-400 truncate mr-2"><?= htmlspecialchars(translateDbName('service', $rs['service_id'] ?? null, $rs['service_name'], $_trCache, $_trLocaleChain)) ?></span>
                    <span class="text-zinc-900 dark:text-white flex-shrink-0"><?= formatPrice((float)$rs['price']) ?></span>
                </div>
                <?php endforeach; ?>
                <?php if ((float)($r['designation_fee'] ?? 0) > 0): ?>
                <div class="flex justify-between text-sm">
                    <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        <?= __('reservations.show_designation_fee') ?> (<?= htmlspecialchars($staffName) ?>)
                    </span>
                    <span class="text-blue-600 dark:text-blue-400 flex-shrink-0">+<?= formatPrice((float)$r['designation_fee']) ?></span>
                </div>
                <?php endif; ?>
                <div class="border-t border-zinc-200 dark:border-zinc-700 pt-2 flex justify-between text-sm">
                    <span class="text-zinc-500 dark:text-zinc-400"><?= __('reservations.show_subtotal') ?></span>
                    <span class="text-zinc-900 dark:text-white"><?= formatPrice((float)$r['total_amount'] + (float)($r['designation_fee'] ?? 0)) ?></span>
                </div>
                <?php if ((float)$r['discount_amount'] > 0): ?>
                <div class="flex justify-between text-sm">
                    <span class="text-zinc-500 dark:text-zinc-400">
                        <?= __('reservations.show_member_discount') ?>
                        <?php if ($userGrade && $userGrade['discount_rate'] > 0): ?>
                        <span class="text-xs text-red-400">(<?= htmlspecialchars($userGrade['name']) ?> <?= $userGrade['discount_rate'] ?>%)</span>
                        <?php endif; ?>
                    </span>
                    <span class="text-red-500 flex-shrink-0">-<?= formatPrice((float)$r['discount_amount']) ?></span>
                </div>
                <?php endif; ?>
                <?php if ((float)$r['points_used'] > 0): ?>
                <div class="flex justify-between text-sm">
                    <span class="text-zinc-500 dark:text-zinc-400"><?= get_points_name() ?> <?= __('reservations.used') ?></span>
                    <span class="text-red-500 flex-shrink-0">-<?= formatPrice((float)$r['points_used']) ?></span>
                </div>
                <?php endif; ?>
                <div class="border-t border-zinc-200 dark:border-zinc-700 pt-3 flex justify-between">
                    <span class="font-semibold text-zinc-900 dark:text-white"><?= __('reservations.show_final_amount') ?></span>
                    <span class="font-bold text-lg text-blue-600 dark:text-blue-400"><?= formatPrice((float)$r['final_amount']) ?></span>
                </div>
                <?php if ($userGrade && $userGrade['point_rate'] > 0 && $r['status'] === 'completed'): ?>
                <div class="mt-2 p-2 bg-green-50 dark:bg-green-900/10 rounded-lg flex justify-between text-xs">
                    <span class="text-green-600 dark:text-green-400"><?= __('reservations.estimated_points', ['name' => get_points_name()]) ?> (<?= $userGrade['point_rate'] ?>%)</span>
                    <span class="text-green-600 dark:text-green-400 font-semibold">+<?= formatPrice((float)$r['final_amount'] * $userGrade['point_rate'] / 100) ?></span>
                </div>
                <?php endif; ?>
            </div>
        </div>

        <!-- 관리자 메모 (레코드 기반) -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4"><?= __('reservations.show_admin_notes') ?></h3>

            <?php if (!empty($r['user_id'])): ?>
            <!-- 메모 입력 -->
            <form onsubmit="saveMemo(event)" class="mb-4">
                <textarea id="memoContent" rows="3"
                    class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100 resize-none"
                    placeholder="<?= __('reservations.show_memo_placeholder') ?>"></textarea>
                <button type="submit" id="saveMemoBtn" class="mt-2 px-4 py-2 bg-zinc-800 dark:bg-zinc-600 text-white rounded-lg text-sm hover:bg-zinc-700 w-full transition"><?= __('reservations.show_memo_save') ?></button>
            </form>

            <!-- 메모 목록 -->
            <div id="memoList" class="space-y-3 max-h-64 overflow-y-auto">
                <?php if (empty($adminMemos)): ?>
                <p class="text-xs text-zinc-400 dark:text-zinc-500 text-center py-2" id="noMemoMsg"><?= __('reservations.show_no_memo') ?></p>
                <?php else: ?>
                <?php foreach ($adminMemos as $memo): ?>
                <div class="border-l-2 <?= ($memo['reservation_id'] === $r['id']) ? 'border-blue-400' : 'border-zinc-300 dark:border-zinc-600' ?> pl-3 py-1">
                    <p class="text-sm text-zinc-800 dark:text-zinc-200 whitespace-pre-wrap"><?= htmlspecialchars($memo['content']) ?></p>
                    <div class="flex items-center gap-2 mt-1 text-xs text-zinc-400 dark:text-zinc-500">
                        <span><?= date('Y-m-d H:i', strtotime($memo['created_at'])) ?></span>
                        <span>&middot;</span>
                        <span><?= htmlspecialchars($memo['admin_name'] ?? 'Admin') ?></span>
                        <?php if (!empty($memo['reservation_number'])): ?>
                        <span>&middot;</span>
                        <span class="font-mono"><?= htmlspecialchars($memo['reservation_number']) ?></span>
                        <?php endif; ?>
                    </div>
                </div>
                <?php endforeach; ?>
                <?php endif; ?>
            </div>
            <?php else: ?>
            <p class="text-xs text-zinc-400 dark:text-zinc-500 text-center py-2"><?= __('reservations.show_guest_memo_notice') ?></p>
            <?php endif; ?>
        </div>

        <!-- 기타 정보 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h3 class="text-sm font-semibold text-zinc-500 dark:text-zinc-400 mb-3"><?= __('reservations.show_etc_info') ?></h3>
            <div class="space-y-2 text-xs text-zinc-500 dark:text-zinc-400">
                <div class="flex justify-between"><span><?= __('reservations.show_etc_source') ?></span><span class="text-zinc-700 dark:text-zinc-300"><?= match($r['source'] ?? 'online') { 'walk_in' => __('reservations.show_source_walkin'), 'phone' => __('reservations.show_source_phone'), 'staff_page' => __('reservations.show_source_staff_page'), default => __('reservations.show_source_online') } ?></span></div>
                <div class="flex justify-between"><span><?= __('reservations.show_etc_staff') ?></span><span class="text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($staffName) ?> <?= (float)($r['designation_fee'] ?? 0) > 0 ? '(' . __('reservations.show_designation') . ')' : (!empty($r['staff_id']) ? '(' . __('reservations.show_assignment') . ')' : '') ?></span></div>
                <div class="flex justify-between"><span><?= __('reservations.show_etc_created') ?></span><span class="text-zinc-700 dark:text-zinc-300"><?= $r['created_at'] ?? '-' ?></span></div>
                <div class="flex justify-between"><span><?= __('reservations.show_etc_updated') ?></span><span class="text-zinc-700 dark:text-zinc-300"><?= $r['updated_at'] ?? '-' ?></span></div>
                <?php if (!empty($r['user_id'])): ?>
                <div class="flex justify-between"><span><?= __('reservations.show_etc_user_id') ?></span><span class="font-mono text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars(substr($r['user_id'], 0, 8)) ?>...</span></div>
                <?php endif; ?>
                <div class="flex justify-between"><span><?= __('reservations.show_etc_reservation_id') ?></span><span class="font-mono text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars(substr($r['id'], 0, 8)) ?>...</span></div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/show-js.php'; ?>

<?php include __DIR__ . '/_foot.php'; ?>
