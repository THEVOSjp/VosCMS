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
if (!$r) { http_response_code(404); echo '<p>예약을 찾을 수 없습니다.</p>'; exit; }

$serviceName = getServiceName($pdo, $prefix, $r['id']);

// 번들 정보 조회
$bundleInfo = null;
$bdlStmt = $pdo->prepare("SELECT DISTINCT b.name, b.bundle_price
    FROM {$prefix}reservation_services rs
    JOIN {$prefix}service_bundles b ON rs.bundle_id = b.id
    WHERE rs.reservation_id = ? AND rs.bundle_id IS NOT NULL LIMIT 1");
$bdlStmt->execute([$r['id']]);
$bundleInfo = $bdlStmt->fetch(PDO::FETCH_ASSOC);

// 예약 서비스 상세 조회
$rsSvcStmt = $pdo->prepare("
    SELECT rs.service_id, rs.service_name, rs.price, rs.duration, rs.bundle_id,
           s.image as service_image, s.description as service_description,
           c.name as category_name
    FROM {$prefix}reservation_services rs
    LEFT JOIN {$prefix}services s ON rs.service_id = s.id
    LEFT JOIN {$prefix}service_categories c ON s.category_id = c.id
    WHERE rs.reservation_id = ?
    ORDER BY rs.sort_order ASC
");
$rsSvcStmt->execute([$r['id']]);
$reservationServices = $rsSvcStmt->fetchAll(PDO::FETCH_ASSOC);

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
$weekdays = ['일', '월', '화', '수', '목', '금', '토'];
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
        <a href="<?= $adminUrl ?>/reservations/<?= $id ?>/edit" class="px-4 py-2 border border-zinc-300 dark:border-zinc-600 text-zinc-700 dark:text-zinc-300 rounded-lg text-sm hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">
            <?= __('reservations.actions.edit') ?>
        </a>
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
            ['pending', '대기중', 'bg-yellow-400'],
            ['confirmed', '확정', 'bg-blue-400'],
            ['completed', '완료', 'bg-green-400'],
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
                <span class="text-[10px] mt-1 font-bold text-red-500"><?= $r['status'] === 'cancelled' ? '취소' : '노쇼' ?></span>
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
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">예약 정보</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">상태</p>
                    <div><?= statusBadge($r['status']) ?></div>
                </div>
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">서비스</p>
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
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">예약 경로</p>
                    <?php
                    $srcLabel = match($r['source'] ?? 'online') {
                        'walk_in' => ['현장접수', 'bg-orange-100 text-orange-700 dark:bg-orange-900/30 dark:text-orange-400'],
                        'phone' => ['전화예약', 'bg-cyan-100 text-cyan-700 dark:bg-cyan-900/30 dark:text-cyan-400'],
                        'staff_page' => ['스태프 지명', 'bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400'],
                        default => ['온라인', 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'],
                    };
                    ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium <?= $srcLabel[1] ?>"><?= $srcLabel[0] ?></span>
                </div>
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">예약일</p>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= $r['reservation_date'] ?> (<?= $dayOfWeek ?>)</p>
                </div>
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">시간 / 소요</p>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= substr($r['start_time'], 0, 5) ?> ~ <?= substr($r['end_time'] ?? '', 0, 5) ?> <span class="text-xs text-zinc-400">(<?= $totalDuration ?>분)</span></p>
                </div>
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">결제 상태</p>
                    <?php
                    $paidAmt = (float)($r['paid_amount'] ?? 0);
                    $finalAmt = (float)($r['final_amount'] ?? 0);
                    $pSt = $r['payment_status'] ?? 'unpaid';
                    $pLabel = match($pSt) {
                        'paid' => ['결제완료', 'bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-400'],
                        'partial' => ['부분결제', 'bg-amber-100 text-amber-700 dark:bg-amber-900/30 dark:text-amber-400'],
                        default => ['미결제', 'bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400'],
                    };
                    ?>
                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-[11px] font-medium <?= $pLabel[1] ?>"><?= $pLabel[0] ?></span>
                    <?php if ($pSt === 'partial'): ?>
                    <span class="text-xs text-zinc-400 ml-1"><?= formatPrice($paidAmt) ?> / <?= formatPrice($finalAmt) ?></span>
                    <?php endif; ?>
                </div>
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">예약번호</p>
                    <p class="text-sm font-mono text-zinc-900 dark:text-white"><?= htmlspecialchars($r['reservation_number'] ?? '') ?></p>
                </div>
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">담당 스태프</p>
                    <?php if (!empty($r['staff_id']) && $staffName !== '-'):
                        $isDesig = (float)($r['designation_fee'] ?? 0) > 0;
                    ?>
                    <div class="flex items-center gap-2">
                        <?php if ($isDesig): ?>
                        <span class="px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400">지명</span>
                        <?php else: ?>
                        <span class="px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">배정</span>
                        <?php endif; ?>
                    </div>
                    <?php else: ?>
                    <span class="px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400">미배정</span>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 담당 스태프 -->
            <?php
                $isDesignation = !empty($r['staff_id']) && (float)($r['designation_fee'] ?? 0) > 0;
                $hasStaff = !empty($r['staff_id']) && $staffName !== '-';
            ?>
            <?php $canChangeStaff = !$isDesignation && in_array($r['status'], ['pending', 'confirmed']); ?>
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
                        <span class="px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-violet-100 text-violet-700 dark:bg-violet-900/30 dark:text-violet-400">지명</span>
                        <span class="text-xs text-violet-600 dark:text-violet-400 font-medium"><?= formatPrice((float)$r['designation_fee']) ?></span>
                        <?php else: ?>
                        <span class="px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-emerald-100 text-emerald-700 dark:bg-emerald-900/30 dark:text-emerald-400">배정</span>
                        <?php endif; ?>
                    </div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">담당 스태프</p>
                </div>
                <?php if ($canChangeStaff): ?>
                <button type="button" onclick="openStaffChangePanel()" class="px-2.5 py-1.5 text-xs font-medium text-emerald-600 hover:bg-emerald-100 dark:hover:bg-emerald-900/20 rounded-lg border border-emerald-300 dark:border-emerald-700 transition">변경</button>
                <?php endif; ?>
                <?php else: ?>
                <div class="w-10 h-10 rounded-full flex-shrink-0 flex items-center justify-center border-2 bg-zinc-200 dark:bg-zinc-700 border-zinc-300 dark:border-zinc-600">
                    <svg class="w-5 h-5 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex items-center gap-2">
                        <span class="text-sm text-zinc-400">미배정</span>
                        <span class="px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-zinc-100 text-zinc-500 dark:bg-zinc-700 dark:text-zinc-400">미배정</span>
                    </div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400">담당 스태프</p>
                </div>
                <?php if ($canChangeStaff): ?>
                <button type="button" onclick="openStaffChangePanel()" class="px-2.5 py-1.5 text-xs font-medium text-blue-600 hover:bg-blue-100 dark:hover:bg-blue-900/20 rounded-lg border border-blue-300 dark:border-blue-700 transition">배정</button>
                <?php endif; ?>
                <?php endif; ?>
            </div>
            <!-- 스태프 변경 패널 (토글) -->
            <?php if ($canChangeStaff): ?>
            <div id="staffChangePanel" class="hidden mt-2 p-3 bg-zinc-50 dark:bg-zinc-900 border border-zinc-200 dark:border-zinc-700 rounded-lg">
                <div class="flex items-center justify-between mb-2">
                    <p class="text-xs font-semibold text-zinc-700 dark:text-zinc-300">스태프 선택</p>
                    <button type="button" onclick="closeStaffChangePanel()" class="p-1 text-zinc-400 hover:text-zinc-600 rounded transition">
                        <svg class="w-4 h-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M6 18L18 6M6 6l12 12"/></svg>
                    </button>
                </div>
                <div id="staffChangeList" class="space-y-1.5 max-h-48 overflow-y-auto">
                    <div class="text-center py-3 text-zinc-400 text-xs">로딩 중...</div>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($r['status'] === 'cancelled' && !empty($r['cancel_reason'])): ?>
            <div class="mt-4 p-3 bg-red-50 dark:bg-red-900/20 rounded-lg">
                <p class="text-xs text-red-500 mb-1">취소 사유</p>
                <p class="text-sm text-red-800 dark:text-red-300"><?= htmlspecialchars($r['cancel_reason']) ?></p>
                <p class="text-xs text-red-400 mt-1"><?= $r['cancelled_at'] ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- 고객 정보 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">고객 정보</h3>
                <?php if (!empty($r['user_id'])): ?>
                <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-400">회원</span>
                <?php else: ?>
                <span class="px-2 py-0.5 text-[10px] font-medium rounded-full bg-zinc-100 text-zinc-600 dark:bg-zinc-700 dark:text-zinc-400">비회원</span>
                <?php endif; ?>
            </div>
            <!-- 프로필 헤더 -->
            <?php if (!empty($r['user_id']) && $userDetail): ?>
            <div class="flex items-center gap-3 mb-4 pb-4 border-b border-zinc-100 dark:border-zinc-700">
                <?php if (!empty($userDetail['profile_image'])):
                    $pi = $userDetail['profile_image'];
                    $profUrl = str_starts_with($pi, 'http') ? $pi : $appUrl . (str_starts_with($pi, '/') ? $pi : '/storage/' . $pi);
                ?>
                <img src="<?= htmlspecialchars($profUrl) ?>" alt="" class="w-12 h-12 rounded-full object-cover border-2 border-zinc-200 dark:border-zinc-600">
                <?php else: ?>
                <div class="w-12 h-12 rounded-full bg-zinc-200 dark:bg-zinc-700 flex items-center justify-center border-2 border-zinc-200 dark:border-zinc-600">
                    <svg class="w-6 h-6 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                </div>
                <?php endif; ?>
                <div class="flex-1 min-w-0">
                    <p class="text-sm font-semibold text-zinc-900 dark:text-white"><?= htmlspecialchars($r['customer_name']) ?></p>
                    <div class="flex items-center gap-2 text-xs text-zinc-500 dark:text-zinc-400">
                        <?php if ($userGrade): ?>
                        <span class="inline-flex items-center px-1.5 py-0.5 rounded-full text-[10px] font-semibold text-white" style="background-color: <?= htmlspecialchars($userGrade['color']) ?>"><?= htmlspecialchars($userGrade['name']) ?></span>
                        <?php endif; ?>
                        <?php if (!empty($visitStats)): ?>
                        <span>방문 <?= (int)$visitStats['completed'] ?>회</span>
                        <?php if ((int)$visitStats['no_show'] > 0): ?>
                        <span class="text-red-400">노쇼 <?= (int)$visitStats['no_show'] ?>회</span>
                        <?php endif; ?>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <?php if (empty($r['user_id']) || !$userDetail): ?>
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">이름</p>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($r['customer_name']) ?></p>
                </div>
                <?php endif; ?>
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">전화번호</p>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">
                        <a href="tel:<?= htmlspecialchars($r['customer_phone']) ?>" class="text-blue-600 dark:text-blue-400 hover:underline"><?= htmlspecialchars($r['customer_phone']) ?></a>
                    </p>
                </div>
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">이메일</p>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white">
                        <?php if (!empty($r['customer_email'])): ?>
                        <a href="mailto:<?= htmlspecialchars($r['customer_email']) ?>" class="text-blue-600 dark:text-blue-400 hover:underline"><?= htmlspecialchars($r['customer_email']) ?></a>
                        <?php else: ?>-<?php endif; ?>
                    </p>
                </div>
                <?php if (!empty($userDetail['birth_date'])): ?>
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">생년월일</p>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= $userDetail['birth_date'] ?>
                        <?php $age = (int)date('Y') - (int)substr($userDetail['birth_date'], 0, 4); ?>
                        <span class="text-xs text-zinc-400">(<?= $age ?>세)</span>
                    </p>
                </div>
                <?php endif; ?>
                <?php if (!empty($userDetail['gender'])): ?>
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">성별</p>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= match($userDetail['gender']) { 'male' => '남성', 'female' => '여성', default => '기타' } ?></p>
                </div>
                <?php endif; ?>
                <?php if ($userGrade): ?>
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">회원 등급</p>
                    <p class="text-sm font-medium">
                        <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-semibold text-white" style="background-color: <?= htmlspecialchars($userGrade['color']) ?>">
                            <?= htmlspecialchars($userGrade['name']) ?>
                        </span>
                        <?php if ($userGrade['discount_rate'] > 0): ?>
                        <span class="ml-1 text-xs text-zinc-500 dark:text-zinc-400">할인 <?= $userGrade['discount_rate'] ?>%</span>
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
                        <span class="ml-1 text-xs text-zinc-500 dark:text-zinc-400">(적립 <?= $userGrade['point_rate'] ?>%)</span>
                        <?php endif; ?>
                    </p>
                </div>
                <?php endif; ?>
                <?php if (!empty($userDetail['member_since'])): ?>
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">가입일</p>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= date('Y-m-d', strtotime($userDetail['member_since'])) ?></p>
                </div>
                <?php endif; ?>
            </div>

            <!-- 고객 요구사항 -->
            <?php if (!empty($r['notes'])): ?>
            <div class="mt-4 p-3 bg-amber-50 dark:bg-amber-900/10 rounded-lg border border-amber-200 dark:border-amber-800/30">
                <p class="text-xs font-medium text-amber-600 dark:text-amber-400 mb-1 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M7 8h10M7 12h4m1 8l-4-4H5a2 2 0 01-2-2V6a2 2 0 012-2h14a2 2 0 012 2v8a2 2 0 01-2 2h-3l-4 4z"/></svg>
                    고객 요구사항
                </p>
                <p class="text-sm text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap"><?= htmlspecialchars($r['notes']) ?></p>
            </div>
            <?php endif; ?>

            <!-- 관리자 메모 (예약건) -->
            <?php if (!empty($r['admin_notes'])): ?>
            <div class="mt-3 p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg border border-zinc-200 dark:border-zinc-700">
                <p class="text-xs font-medium text-zinc-500 dark:text-zinc-400 mb-1 flex items-center gap-1">
                    <svg class="w-3.5 h-3.5" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M11 5H6a2 2 0 00-2 2v11a2 2 0 002 2h11a2 2 0 002-2v-5m-1.414-9.414a2 2 0 112.828 2.828L11.828 15H9v-2.828l8.586-8.586z"/></svg>
                    관리자 메모
                </p>
                <p class="text-sm text-zinc-700 dark:text-zinc-300 whitespace-pre-wrap"><?= htmlspecialchars($r['admin_notes']) ?></p>
            </div>
            <?php endif; ?>
        </div>

        <!-- 서비스 상세 정보 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-lg font-semibold text-zinc-900 dark:text-white">서비스 상세</h3>
                <span class="text-sm text-zinc-500 dark:text-zinc-400"><?= count($reservationServices) ?>건 · <?= $totalDuration ?>분</span>
            </div>
            <?php if (empty($reservationServices)): ?>
            <p class="text-sm text-zinc-400 text-center py-4">등록된 서비스가 없습니다.</p>
            <?php else: ?>
            <div class="space-y-3">
                <?php foreach ($reservationServices as $idx => $rs):
                    $svcImg = $rs['service_image'] ?? '';
                    $hasImg = !empty($svcImg);
                    $isBundled = !empty($rs['bundle_id']);
                ?>
                <div class="flex items-start gap-3 p-3 bg-zinc-50 dark:bg-zinc-900 rounded-lg">
                    <?php if ($hasImg): ?>
                    <img src="<?= htmlspecialchars($appUrl . '/storage/' . $svcImg) ?>" alt=""
                         class="w-14 h-14 rounded-lg object-cover flex-shrink-0 border border-zinc-200 dark:border-zinc-700">
                    <?php else: ?>
                    <div class="w-14 h-14 rounded-lg bg-zinc-200 dark:bg-zinc-700 flex-shrink-0 flex items-center justify-center">
                        <svg class="w-6 h-6 text-zinc-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 13.255A23.931 23.931 0 0112 15c-3.183 0-6.22-.62-9-1.745M16 6V4a2 2 0 00-2-2h-4a2 2 0 00-2 2v2m4 6h.01M5 20h14a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v10a2 2 0 002 2z"/></svg>
                    </div>
                    <?php endif; ?>
                    <div class="flex-1 min-w-0">
                        <div class="flex items-center gap-2 mb-0.5">
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white truncate"><?= htmlspecialchars($rs['service_name']) ?></p>
                            <?php if ($rs['category_name']): ?>
                            <span class="px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-zinc-200 dark:bg-zinc-700 text-zinc-600 dark:text-zinc-400 flex-shrink-0"><?= htmlspecialchars($rs['category_name']) ?></span>
                            <?php endif; ?>
                            <?php if ($isBundled): ?>
                            <span class="px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-purple-100 dark:bg-purple-900/30 text-purple-700 dark:text-purple-400 flex-shrink-0">번들</span>
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
                            <p class="text-sm font-semibold text-zinc-900 dark:text-white">지명비</p>
                            <span class="px-1.5 py-0.5 text-[10px] font-medium rounded-full bg-blue-100 dark:bg-blue-900/30 text-blue-700 dark:text-blue-400 flex-shrink-0">지명</span>
                        </div>
                        <p class="text-xs text-zinc-500 dark:text-zinc-400">담당: <?= htmlspecialchars($staffName) ?></p>
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
                    <span class="text-zinc-500 dark:text-zinc-400">서비스 (<?= count($reservationServices) ?>건 · <?= $totalDuration ?>분)</span>
                    <span class="text-zinc-900 dark:text-white"><?= formatPrice($totalServicePrice) ?></span>
                </div>
                <?php if ($designFee > 0): ?>
                <div class="flex items-center justify-between">
                    <span class="text-zinc-500 dark:text-zinc-400">지명비</span>
                    <span class="text-zinc-900 dark:text-white"><?= formatPrice($designFee) ?></span>
                </div>
                <div class="flex items-center justify-between pt-1.5 border-t border-zinc-200 dark:border-zinc-700">
                    <span class="font-semibold text-zinc-900 dark:text-white">합계</span>
                    <span class="font-bold text-zinc-900 dark:text-white"><?= formatPrice($totalServicePrice + $designFee) ?></span>
                </div>
                <?php else: ?>
                <div class="flex items-center justify-between">
                    <span class="font-semibold text-zinc-900 dark:text-white">합계</span>
                    <span class="font-bold text-zinc-900 dark:text-white"><?= formatPrice($totalServicePrice) ?></span>
                </div>
                <?php endif; ?>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- 우측 사이드바 -->
    <div class="space-y-6">
        <!-- 결제 정보 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">결제 정보</h3>
            <div class="space-y-3">
                <!-- 서비스 항목별 금액 -->
                <?php foreach ($reservationServices as $rs): ?>
                <div class="flex justify-between text-sm">
                    <span class="text-zinc-500 dark:text-zinc-400 truncate mr-2"><?= htmlspecialchars($rs['service_name']) ?></span>
                    <span class="text-zinc-900 dark:text-white flex-shrink-0"><?= formatPrice((float)$rs['price']) ?></span>
                </div>
                <?php endforeach; ?>
                <?php if ((float)($r['designation_fee'] ?? 0) > 0): ?>
                <div class="flex justify-between text-sm">
                    <span class="text-zinc-500 dark:text-zinc-400 flex items-center gap-1">
                        <svg class="w-3.5 h-3.5 text-blue-500" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                        지명비 (<?= htmlspecialchars($staffName) ?>)
                    </span>
                    <span class="text-blue-600 dark:text-blue-400 flex-shrink-0">+<?= formatPrice((float)$r['designation_fee']) ?></span>
                </div>
                <?php endif; ?>
                <div class="border-t border-zinc-200 dark:border-zinc-700 pt-2 flex justify-between text-sm">
                    <span class="text-zinc-500 dark:text-zinc-400">소계</span>
                    <span class="text-zinc-900 dark:text-white"><?= formatPrice((float)$r['total_amount'] + (float)($r['designation_fee'] ?? 0)) ?></span>
                </div>
                <?php if ((float)$r['discount_amount'] > 0): ?>
                <div class="flex justify-between text-sm">
                    <span class="text-zinc-500 dark:text-zinc-400">
                        회원 할인
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
                    <span class="font-semibold text-zinc-900 dark:text-white">최종 금액</span>
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
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">관리자 메모</h3>

            <?php if (!empty($r['user_id'])): ?>
            <!-- 메모 입력 -->
            <form onsubmit="saveMemo(event)" class="mb-4">
                <textarea id="memoContent" rows="3"
                    class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100 resize-none"
                    placeholder="메모 입력..."></textarea>
                <button type="submit" id="saveMemoBtn" class="mt-2 px-4 py-2 bg-zinc-800 dark:bg-zinc-600 text-white rounded-lg text-sm hover:bg-zinc-700 w-full transition">저장</button>
            </form>

            <!-- 메모 목록 -->
            <div id="memoList" class="space-y-3 max-h-64 overflow-y-auto">
                <?php if (empty($adminMemos)): ?>
                <p class="text-xs text-zinc-400 dark:text-zinc-500 text-center py-2" id="noMemoMsg">메모가 없습니다.</p>
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
            <p class="text-xs text-zinc-400 dark:text-zinc-500 text-center py-2">비회원 예약 — 메모 기능을 사용하려면 회원 연결이 필요합니다.</p>
            <?php endif; ?>
        </div>

        <!-- 기타 정보 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h3 class="text-sm font-semibold text-zinc-500 dark:text-zinc-400 mb-3">기타 정보</h3>
            <div class="space-y-2 text-xs text-zinc-500 dark:text-zinc-400">
                <div class="flex justify-between"><span>예약 경로</span><span class="text-zinc-700 dark:text-zinc-300"><?= match($r['source'] ?? 'online') { 'walk_in' => '현장접수', 'phone' => '전화예약', 'staff_page' => '스태프 지명', default => '온라인' } ?></span></div>
                <div class="flex justify-between"><span>스태프</span><span class="text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars($staffName) ?> <?= (float)($r['designation_fee'] ?? 0) > 0 ? '(지명)' : (!empty($r['staff_id']) ? '(배정)' : '') ?></span></div>
                <div class="flex justify-between"><span>생성일</span><span class="text-zinc-700 dark:text-zinc-300"><?= $r['created_at'] ?? '-' ?></span></div>
                <div class="flex justify-between"><span>수정일</span><span class="text-zinc-700 dark:text-zinc-300"><?= $r['updated_at'] ?? '-' ?></span></div>
                <?php if (!empty($r['user_id'])): ?>
                <div class="flex justify-between"><span>회원 ID</span><span class="font-mono text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars(substr($r['user_id'], 0, 8)) ?>...</span></div>
                <?php endif; ?>
                <div class="flex justify-between"><span>예약 ID</span><span class="font-mono text-zinc-700 dark:text-zinc-300"><?= htmlspecialchars(substr($r['id'], 0, 8)) ?>...</span></div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/show-js.php'; ?>

<?php include __DIR__ . '/_foot.php'; ?>
