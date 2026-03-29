<?php
/**
 * RezlyX - 예약 취소 페이지
 */
require_once BASE_PATH . '/rzxlib/Core/Helpers/Encryption.php';
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

if (empty($reservationNumber)) {
    http_response_code(404);
    echo '<div class="max-w-2xl mx-auto px-4 py-12 text-center"><p class="text-zinc-500">' . __('booking.lookup.not_found') . '</p></div>';
    return;
}

$stmt = $pdo->prepare("SELECT * FROM {$prefix}reservations WHERE reservation_number = ?");
$stmt->execute([$reservationNumber]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reservation || !in_array($reservation['status'], ['pending', 'confirmed'])) {
    echo '<div class="max-w-2xl mx-auto px-4 py-12 text-center"><p class="text-zinc-500">' . __('booking.cancel.cannot_cancel') . '</p></div>';
    return;
}

$pageTitle = __('booking.cancel.title') . ' - ' . $reservationNumber;
$success = false;
$error = '';

// 취소 처리
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $reason = trim($_POST['reason'] ?? '');
    $pdo->prepare("UPDATE {$prefix}reservations SET status = 'cancelled', cancel_reason = ?, cancelled_at = NOW() WHERE id = ?")
        ->execute([$reason, $reservation['id']]);
    $success = true;
}
?>

<div class="max-w-lg mx-auto px-4 py-12">
    <?php if ($success): ?>
    <!-- 취소 완료 -->
    <div class="text-center">
        <div class="w-16 h-16 mx-auto mb-4 bg-green-100 dark:bg-green-900/30 rounded-full flex items-center justify-center">
            <svg class="w-8 h-8 text-green-600 dark:text-green-400" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M5 13l4 4L19 7"/></svg>
        </div>
        <h1 class="text-2xl font-bold text-zinc-800 dark:text-zinc-100 mb-2"><?= __('booking.cancel.success') ?></h1>
        <p class="text-sm text-zinc-500 mb-6 font-mono"><?= htmlspecialchars($reservationNumber) ?></p>
        <a href="<?= $baseUrl ?>/lookup" class="px-6 py-2.5 text-sm font-medium text-white bg-blue-600 hover:bg-blue-700 rounded-lg transition"><?= __('booking.detail.back_to_lookup') ?></a>
    </div>
    <?php else: ?>
    <!-- 취소 확인 -->
    <div class="bg-white dark:bg-zinc-800 rounded-xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
        <h1 class="text-xl font-bold text-zinc-800 dark:text-zinc-100 mb-2"><?= __('booking.cancel.title') ?></h1>
        <p class="text-sm text-zinc-500 mb-1 font-mono"><?= htmlspecialchars($reservationNumber) ?></p>
        <p class="text-sm text-zinc-500 mb-6"><?= date('Y-m-d', strtotime($reservation['reservation_date'])) ?> <?= date('H:i', strtotime($reservation['start_time'])) ?></p>

        <div class="p-4 bg-red-50 dark:bg-red-900/20 border border-red-200 dark:border-red-800 rounded-lg mb-6">
            <p class="text-sm text-red-700 dark:text-red-300"><?= __('booking.cancel.confirm') ?></p>
        </div>

        <form method="POST" class="space-y-4">
            <div>
                <label class="block text-sm font-medium text-zinc-700 dark:text-zinc-300 mb-1"><?= __('booking.cancel.reason') ?></label>
                <textarea name="reason" rows="3" class="w-full px-3 py-2 text-sm bg-white dark:bg-zinc-700 border border-zinc-300 dark:border-zinc-600 rounded-lg text-zinc-800 dark:text-zinc-200" placeholder="<?= __('booking.cancel.reason_placeholder') ?>"></textarea>
            </div>
            <div class="flex gap-3">
                <a href="<?= $baseUrl ?>/booking/detail/<?= urlencode($reservationNumber) ?>" class="flex-1 px-4 py-2.5 text-sm font-medium text-zinc-600 dark:text-zinc-300 bg-white dark:bg-zinc-800 border border-zinc-300 dark:border-zinc-600 rounded-lg hover:bg-zinc-50 dark:hover:bg-zinc-700 transition text-center"><?= __('board.cancel') ?></a>
                <button type="submit" class="flex-1 px-4 py-2.5 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition"><?= __('booking.cancel.submit') ?></button>
            </div>
        </form>
    </div>
    <?php endif; ?>
</div>
