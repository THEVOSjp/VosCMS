<?php
/**
 * RezlyX 마이페이지 - 예약 상세
 */
require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
require_once BASE_PATH . '/rzxlib/Core/Helpers/Encryption.php';
use RzxLib\Core\Auth\Auth;
use RzxLib\Core\Helpers\Encryption;

if (!Auth::check()) {
    header('Location: ' . ($config['app_url'] ?? '') . '/login');
    exit;
}

$user = Auth::user();
$currentUser = $user;
$isLoggedIn = true;
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

// 예약 조회
$stmt = $pdo->prepare("SELECT * FROM {$prefix}reservations WHERE id = ? AND user_id = ?");
$stmt->execute([$reservationId, $user['id']]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reservation) {
    http_response_code(404);
    echo '<div class="max-w-2xl mx-auto px-4 py-12 text-center"><p class="text-zinc-500">' . __('booking.lookup.not_found') . '</p></div>';
    return;
}

$pageTitle = __('booking.detail.title') . ' - ' . $reservation['reservation_number'];

// 서비스 목록
$svcStmt = $pdo->prepare("SELECT * FROM {$prefix}reservation_services WHERE reservation_id = ? ORDER BY sort_order");
$svcStmt->execute([$reservation['id']]);
$services = $svcStmt->fetchAll(PDO::FETCH_ASSOC);

// 스태프 정보
$staff = null;
if ($reservation['staff_id']) {
    $staffStmt = $pdo->prepare("SELECT id, name, avatar, greeting_before, greeting_after, designation_fee FROM {$prefix}staff WHERE id = ?");
    $staffStmt->execute([$reservation['staff_id']]);
    $staff = $staffStmt->fetch(PDO::FETCH_ASSOC);
}

// 상태 배지
$statusColors = [
    'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
    'confirmed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
    'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
    'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
    'no_show' => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-400',
];
$statusClass = $statusColors[$reservation['status']] ?? $statusColors['pending'];
$statusLabel = __('common.status.' . $reservation['status']);

// 결제 상태
$paymentLabel = __('booking.payment.' . ($reservation['payment_status'] ?? 'unpaid'));

// 통화 포맷
$currency = $config['currency'] ?? 'KRW';
$sym = ['KRW' => '₩', 'JPY' => '¥', 'USD' => '$'][$currency] ?? $currency;
$fmt = function($a) use ($sym) { return $sym . number_format((float)$a); };

// 취소 가능 여부
$isCancellable = in_array($reservation['status'], ['pending', 'confirmed']);

// 인사말
$greeting = '';
if ($staff) {
    $greeting = $reservation['status'] === 'completed'
        ? ($staff['greeting_after'] ?? '')
        : ($staff['greeting_before'] ?? '');
}
?>

<div class="max-w-5xl mx-auto px-4 sm:px-6 py-8">
    <div class="flex flex-col lg:flex-row gap-6">

        <!-- 사이드바 -->
        <?php include __DIR__ . '/profile-sidebar.php'; ?>

        <!-- 메인 -->
        <div class="flex-1 space-y-6">

            <!-- 헤더 -->
            <div class="flex items-center justify-between">
                <div>
                    <h1 class="text-2xl font-bold text-zinc-800 dark:text-zinc-100"><?= __('booking.detail.title') ?></h1>
                    <p class="text-sm text-zinc-500 mt-1 font-mono"><?= htmlspecialchars($reservation['reservation_number']) ?></p>
                </div>
                <span class="px-3 py-1 text-sm font-medium rounded-full <?= $statusClass ?>"><?= $statusLabel ?></span>
            </div>

            <!-- 날짜/시간 + 스태프 -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                <div class="flex items-start justify-between gap-4">
                    <div class="flex items-center gap-4">
                        <div class="w-14 h-14 bg-blue-100 dark:bg-blue-900/30 rounded-xl flex flex-col items-center justify-center shrink-0">
                            <span class="text-xs text-blue-600 dark:text-blue-400 font-medium"><?= date('M', strtotime($reservation['reservation_date'])) ?></span>
                            <span class="text-lg font-bold text-blue-600 dark:text-blue-400 -mt-1"><?= date('d', strtotime($reservation['reservation_date'])) ?></span>
                        </div>
                        <div>
                            <p class="text-lg font-semibold text-zinc-800 dark:text-zinc-100"><?= date('Y년 m월 d일 (D)', strtotime($reservation['reservation_date'])) ?></p>
                            <p class="text-sm text-zinc-500"><?= date('H:i', strtotime($reservation['start_time'])) ?> ~ <?= date('H:i', strtotime($reservation['end_time'])) ?></p>
                        </div>
                    </div>

                    <?php if ($staff): ?>
                    <div class="flex items-start gap-3 shrink-0">
                        <?php if ($greeting): ?>
                        <div class="relative bg-blue-50 dark:bg-blue-900/20 border border-blue-200 dark:border-blue-800 rounded-xl px-3 py-2 max-w-[200px]">
                            <p class="text-xs text-blue-700 dark:text-blue-300"><?= htmlspecialchars($greeting) ?></p>
                            <div class="absolute top-4 -right-2 w-0 h-0 border-t-[6px] border-t-transparent border-b-[6px] border-b-transparent border-l-[8px] border-l-blue-200 dark:border-l-blue-800"></div>
                        </div>
                        <?php endif; ?>
                        <div class="text-center">
                            <?php if (!empty($staff['avatar'])): ?>
                            <img src="<?= htmlspecialchars($staff['avatar']) ?>" class="w-14 h-14 rounded-full object-cover border-2 border-white dark:border-zinc-700 shadow">
                            <?php else: ?>
                            <div class="w-14 h-14 rounded-full bg-purple-100 dark:bg-purple-900/30 flex items-center justify-center text-purple-600 font-bold text-lg border-2 border-white dark:border-zinc-700 shadow"><?= mb_substr($staff['name'], 0, 1) ?></div>
                            <?php endif; ?>
                            <p class="text-xs font-medium text-zinc-700 dark:text-zinc-300 mt-1"><?= htmlspecialchars($staff['name']) ?></p>
                            <?php if (($reservation['designation_fee'] ?? 0) > 0): ?>
                            <p class="text-[10px] text-amber-600 dark:text-amber-400"><?= __('booking.detail.designation_fee') ?> <?= $fmt($reservation['designation_fee']) ?></p>
                            <?php endif; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- 서비스 내역 -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                <h3 class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-3"><?= __('booking.detail.services') ?></h3>
                <div class="space-y-2">
                    <?php foreach ($services as $svc): ?>
                    <div class="flex items-center justify-between">
                        <div>
                            <span class="text-sm text-zinc-800 dark:text-zinc-200"><?= htmlspecialchars($svc['service_name'] ?: '-') ?></span>
                            <span class="text-xs text-zinc-400 ml-2"><?= (int)$svc['duration'] ?><?= __('common.minutes') ?? '분' ?></span>
                        </div>
                        <span class="text-sm font-medium text-zinc-700 dark:text-zinc-300"><?= $fmt($svc['price']) ?></span>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>

            <!-- 결제 정보 -->
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                <h3 class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-3"><?= __('booking.detail.payment') ?></h3>
                <div class="space-y-1.5 text-sm">
                    <div class="flex justify-between">
                        <span class="text-zinc-600 dark:text-zinc-400"><?= __('booking.detail.total') ?></span>
                        <span class="text-zinc-800 dark:text-zinc-200"><?= $fmt($reservation['total_amount']) ?></span>
                    </div>
                    <?php if (($reservation['discount_amount'] ?? 0) > 0): ?>
                    <div class="flex justify-between text-green-600 dark:text-green-400">
                        <span><?= __('booking.detail.discount') ?></span>
                        <span>-<?= $fmt($reservation['discount_amount']) ?></span>
                    </div>
                    <?php endif; ?>
                    <?php if (($reservation['points_used'] ?? 0) > 0): ?>
                    <div class="flex justify-between text-green-600 dark:text-green-400">
                        <span><?= __('booking.detail.points_used') ?></span>
                        <span>-<?= $fmt($reservation['points_used']) ?></span>
                    </div>
                    <?php endif; ?>
                    <div class="flex justify-between pt-2 border-t border-zinc-100 dark:border-zinc-700 font-semibold">
                        <span class="text-zinc-800 dark:text-zinc-200"><?= __('booking.detail.final_amount') ?></span>
                        <span class="text-blue-600 dark:text-blue-400 text-lg"><?= $fmt($reservation['final_amount']) ?></span>
                    </div>
                    <div class="flex justify-between pt-1">
                        <span class="text-zinc-500"><?= __('booking.detail.payment_status') ?></span>
                        <span class="text-zinc-700 dark:text-zinc-300"><?= $paymentLabel ?></span>
                    </div>
                </div>
            </div>

            <!-- 요청사항 -->
            <?php if (!empty($reservation['notes'])): ?>
            <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
                <h3 class="text-sm font-medium text-zinc-500 dark:text-zinc-400 mb-2"><?= __('booking.detail.notes') ?></h3>
                <p class="text-sm text-zinc-800 dark:text-zinc-200"><?= nl2br(htmlspecialchars($reservation['notes'])) ?></p>
            </div>
            <?php endif; ?>

            <!-- 취소 정보 -->
            <?php if ($reservation['status'] === 'cancelled'): ?>
            <div class="bg-red-50 dark:bg-red-900/20 rounded-xl border border-red-200 dark:border-red-800 p-6">
                <h3 class="text-sm font-medium text-red-700 dark:text-red-300 mb-2"><?= __('booking.detail.cancel_info') ?></h3>
                <?php if ($reservation['cancelled_at']): ?>
                <p class="text-sm text-red-600 dark:text-red-400"><?= __('booking.detail.cancelled_at') ?>: <?= date('Y-m-d H:i', strtotime($reservation['cancelled_at'])) ?></p>
                <?php endif; ?>
                <?php if ($reservation['cancel_reason']): ?>
                <p class="text-sm text-red-600 dark:text-red-400 mt-1"><?= __('booking.detail.cancel_reason') ?>: <?= htmlspecialchars($reservation['cancel_reason']) ?></p>
                <?php endif; ?>
            </div>
            <?php endif; ?>

            <!-- 버튼 -->
            <div class="flex items-center justify-between">
                <a href="<?= $baseUrl ?>/mypage/reservations" class="px-5 py-2.5 text-sm font-medium text-zinc-600 dark:text-zinc-300 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition">
                    <?= __('auth.reservations.title') ?>
                </a>
                <?php if ($isCancellable): ?>
                <a href="<?= $baseUrl ?>/booking/cancel/<?= urlencode($reservation['reservation_number']) ?>" class="px-5 py-2.5 text-sm font-medium text-red-600 dark:text-red-400 bg-white dark:bg-zinc-800 border border-red-300 dark:border-red-600 rounded-lg hover:bg-red-50 dark:hover:bg-red-900/20 transition">
                    <?= __('booking.cancel.title') ?>
                </a>
                <?php endif; ?>
            </div>

        </div>
    </div>
</div>
