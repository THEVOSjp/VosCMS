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

// 스태프 정보
$staffName = '-';
if (!empty($r['staff_id'])) {
    $staffStmt = $pdo->prepare("SELECT name FROM {$prefix}staff WHERE id = ?");
    $staffStmt->execute([$r['staff_id']]);
    $staffRow = $staffStmt->fetch(PDO::FETCH_ASSOC);
    if ($staffRow) $staffName = $staffRow['name'];
}

// 요일 계산
$weekdays = ['일', '월', '화', '수', '목', '금', '토'];
$dateTs = strtotime($r['reservation_date']);
$dayOfWeek = $weekdays[(int)date('w', $dateTs)];

$pageTitle = __('reservations.detail') . ' - ' . ($r['reservation_number'] ?? $id);

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
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">담당 스태프</p>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($staffName) ?></p>
                </div>
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">예약일</p>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= $r['reservation_date'] ?> (<?= $dayOfWeek ?>)</p>
                </div>
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">시간</p>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= substr($r['start_time'], 0, 5) ?> ~ <?= substr($r['end_time'] ?? '', 0, 5) ?></p>
                </div>
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">예약번호</p>
                    <p class="text-sm font-mono text-zinc-900 dark:text-white"><?= htmlspecialchars($r['reservation_number'] ?? '') ?></p>
                </div>
            </div>
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
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">고객 정보</h3>
            <div class="grid grid-cols-2 md:grid-cols-3 gap-4">
                <div>
                    <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">이름</p>
                    <p class="text-sm font-medium text-zinc-900 dark:text-white"><?= htmlspecialchars($r['customer_name']) ?></p>
                </div>
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
            </div>
            <?php if (!empty($r['notes'])): ?>
            <div class="mt-4">
                <p class="text-xs text-zinc-500 dark:text-zinc-400 mb-1">고객 메모</p>
                <p class="text-sm text-zinc-700 dark:text-zinc-300 bg-zinc-50 dark:bg-zinc-900 p-3 rounded-lg"><?= nl2br(htmlspecialchars($r['notes'])) ?></p>
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
                <div class="flex justify-between text-sm">
                    <span class="text-zinc-500 dark:text-zinc-400">서비스 금액</span>
                    <span class="text-zinc-900 dark:text-white"><?= formatPrice((float)$r['total_amount']) ?></span>
                </div>
                <?php if ((float)($r['designation_fee'] ?? 0) > 0): ?>
                <div class="flex justify-between text-sm">
                    <span class="text-zinc-500 dark:text-zinc-400">지명비</span>
                    <span class="text-zinc-900 dark:text-white">+<?= formatPrice((float)$r['designation_fee']) ?></span>
                </div>
                <?php endif; ?>
                <?php if ((float)$r['discount_amount'] > 0): ?>
                <div class="flex justify-between text-sm">
                    <span class="text-zinc-500 dark:text-zinc-400">할인</span>
                    <span class="text-red-500">-<?= formatPrice((float)$r['discount_amount']) ?></span>
                </div>
                <?php endif; ?>
                <?php if ((float)$r['points_used'] > 0): ?>
                <div class="flex justify-between text-sm">
                    <span class="text-zinc-500 dark:text-zinc-400">포인트 사용</span>
                    <span class="text-red-500">-<?= formatPrice((float)$r['points_used']) ?></span>
                </div>
                <?php endif; ?>
                <div class="border-t border-zinc-200 dark:border-zinc-700 pt-3 flex justify-between">
                    <span class="font-semibold text-zinc-900 dark:text-white">최종 금액</span>
                    <span class="font-bold text-lg text-blue-600 dark:text-blue-400"><?= formatPrice((float)$r['final_amount']) ?></span>
                </div>
            </div>
        </div>

        <!-- 관리자 메모 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h3 class="text-lg font-semibold text-zinc-900 dark:text-white mb-4">관리자 메모</h3>
            <form onsubmit="saveAdminNote(event)">
                <textarea id="adminNotes" rows="4"
                    class="w-full px-3 py-2 bg-zinc-50 dark:bg-zinc-900 border border-zinc-300 dark:border-zinc-600 rounded-lg text-sm text-zinc-900 dark:text-zinc-100 resize-none"
                    placeholder="관리자 메모 입력..."><?= htmlspecialchars($r['admin_notes'] ?? '') ?></textarea>
                <button type="submit" id="saveNoteBtn" class="mt-2 px-4 py-2 bg-zinc-800 dark:bg-zinc-600 text-white rounded-lg text-sm hover:bg-zinc-700 w-full transition">저장</button>
            </form>
        </div>

        <!-- 기타 정보 -->
        <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <h3 class="text-sm font-semibold text-zinc-500 dark:text-zinc-400 mb-3">기타 정보</h3>
            <div class="space-y-2 text-xs text-zinc-500 dark:text-zinc-400">
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
