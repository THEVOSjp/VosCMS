<?php
/**
 * Lookup Widget - render.php
 * 예약 조회 위젯 (직접 렌더링)
 */
require_once BASE_PATH . '/rzxlib/Reservation/Models/Reservation.php';
require_once BASE_PATH . '/rzxlib/Reservation/Models/Service.php';
use RzxLib\Reservation\Models\Reservation;
use RzxLib\Reservation\Models\Service;

$wTitle = $config['title'] ?? '';
$wSubtitle = $config['subtitle'] ?? '';
$baseUrl = $baseUrl ?? '';

// 검색 결과
$reservations = [];
$error = '';
$searched = false;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $searched = true;
    $reservationNumber = trim($_POST['booking_code'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $phone = trim($_POST['phone'] ?? '');

    if (empty($reservationNumber) && empty($email) && empty($phone)) {
        $error = __('booking.lookup.input_required');
    } else {
        try {
            $query = Reservation::query();
            if (!empty($reservationNumber)) {
                $query->where('reservation_number', $reservationNumber);
            }
            if (!empty($email)) {
                $query->where('customer_email', $email);
            }
            if (!empty($phone)) {
                $phoneDigits = preg_replace('/[^0-9]/', '', $phone);
                if (strlen($phoneDigits) >= 4) {
                    $query->where('customer_phone', 'LIKE', '%' . $phoneDigits . '%');
                }
            }
            $results = $query->orderBy('reservation_date', 'desc')->limit(20)->get();
            $reservations = array_map([Reservation::class, 'fromArray'], $results);
            if (empty($reservations)) {
                $error = __('booking.lookup.not_found');
            }
        } catch (\PDOException $e) {
            $error = __('booking.lookup.not_found');
        }
    }
}

ob_start();
?>
<section class="py-8">
    <div class="max-w-7xl mx-auto px-4">
        <?php if ($wTitle || $wSubtitle): ?>
        <div class="text-center mb-8">
            <?php if ($wTitle): ?><h2 class="text-3xl font-bold text-gray-900 dark:text-white"><?= htmlspecialchars($wTitle) ?></h2><?php endif; ?>
            <?php if ($wSubtitle): ?><p class="text-gray-600 dark:text-zinc-400 mt-2"><?= htmlspecialchars($wSubtitle) ?></p><?php endif; ?>
        </div>
        <?php endif; ?>

        <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-700 p-6">
            <form method="POST" class="space-y-5">
                <!-- 예약번호 -->
                <div>
                    <label for="booking_code" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                        <?= __('booking.lookup.booking_code') ?>
                    </label>
                    <input type="text" name="booking_code" id="booking_code"
                           value="<?= htmlspecialchars($_POST['booking_code'] ?? '') ?>"
                           placeholder="<?= __('booking.lookup.booking_code_placeholder') ?>"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500 font-mono uppercase">
                </div>

                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200 dark:border-zinc-700"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-3 bg-white dark:bg-zinc-800 text-gray-500 dark:text-zinc-400">+</span>
                    </div>
                </div>

                <!-- 이메일 -->
                <div>
                    <label for="email" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                        <?= __('booking.lookup.email') ?>
                    </label>
                    <input type="email" name="email" id="email"
                           value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                           placeholder="<?= __('booking.lookup.email_placeholder') ?>"
                           class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                </div>

                <div class="relative">
                    <div class="absolute inset-0 flex items-center">
                        <div class="w-full border-t border-gray-200 dark:border-zinc-700"></div>
                    </div>
                    <div class="relative flex justify-center text-sm">
                        <span class="px-3 bg-white dark:bg-zinc-800 text-gray-500 dark:text-zinc-400"><?= __('auth.social.or') ?></span>
                    </div>
                </div>

                <!-- 전화번호 -->
                <div>
                    <label for="phone" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                        <?= __('booking.lookup.phone') ?>
                    </label>
                    <div class="flex gap-2">
                        <input type="tel" name="phone" id="phone"
                               value="<?= htmlspecialchars($_POST['phone'] ?? '') ?>"
                               placeholder="<?= __('booking.lookup.phone_placeholder') ?>"
                               class="flex-1 px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                    </div>
                </div>

                <button type="submit" class="w-full px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition shadow-lg shadow-blue-500/30">
                    <svg class="inline-block w-5 h-5 mr-2 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                    </svg>
                    <?= __('booking.lookup.search') ?>
                </button>
            </form>
        </div>

        <?php if ($error): ?>
        <div class="mt-4 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg">
            <p class="text-red-700 dark:text-red-300 text-sm"><?= htmlspecialchars($error) ?></p>
        </div>
        <?php endif; ?>

        <!-- 검색 결과 -->
        <?php if (!empty($reservations)): ?>
        <div class="mt-6 bg-white dark:bg-zinc-800 rounded-2xl shadow-sm border border-zinc-200 dark:border-zinc-700 overflow-hidden">
            <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-700">
                <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                    <?= __('booking.lookup.result_title') ?>
                </h2>
            </div>

            <div class="divide-y divide-gray-200 dark:divide-zinc-700">
                <?php foreach ($reservations as $reservation): ?>
                <?php
                    $service = Service::find($reservation->service_id);
                    $serviceName = $service ? $service->name : '서비스';
                ?>
                <div class="p-6 hover:bg-gray-50 dark:hover:bg-zinc-700/50 transition">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                        <div class="flex-1">
                            <div class="flex items-center gap-2 mb-2">
                                <span class="font-mono text-sm font-medium text-gray-900 dark:text-white">
                                    <?= htmlspecialchars($reservation->reservation_number) ?>
                                </span>
                                <?php
                                    $statusClasses = [
                                        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
                                        'confirmed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
                                        'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
                                        'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
                                        'no_show' => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-400',
                                    ];
                                    $statusClass = $statusClasses[$reservation->status] ?? $statusClasses['pending'];
                                ?>
                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?= $statusClass ?>">
                                    <?= __('common.status.' . $reservation->status) ?>
                                </span>
                            </div>
                            <h3 class="font-semibold text-gray-900 dark:text-white mb-1">
                                <?= htmlspecialchars($serviceName) ?>
                            </h3>
                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-500 dark:text-zinc-400">
                                <span class="flex items-center">
                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                    </svg>
                                    <?= date('Y-m-d H:i', strtotime($reservation->reservation_date . ' ' . $reservation->start_time)) ?>
                                </span>
                                <span class="font-medium text-gray-900 dark:text-white">
                                    ¥<?= number_format($reservation->final_amount, 0) ?>
                                </span>
                            </div>
                        </div>
                        <div class="flex items-center gap-2">
                            <a href="<?= $baseUrl ?>/booking/detail/<?= urlencode($reservation->reservation_number) ?>"
                               class="px-4 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition">
                                <?= __('booking.detail.title') ?>
                            </a>
                            <?php if ($reservation->status === 'pending' || $reservation->status === 'confirmed'): ?>
                            <a href="<?= $baseUrl ?>/booking/cancel/<?= urlencode($reservation->reservation_number) ?>"
                               class="px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition">
                                <?= __('booking.cancel.title') ?>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
        <?php endif; ?>
    </div>
</section>
<?php return ob_get_clean();
