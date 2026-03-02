<?php
/**
 * RezlyX Booking Lookup Page
 * 비회원 예약 조회 페이지
 */

require_once BASE_PATH . '/rzxlib/Reservation/Models/Reservation.php';
require_once BASE_PATH . '/rzxlib/Reservation/Models/Service.php';
require_once BASE_PATH . '/rzxlib/Core/Helpers/Encryption.php';

use RzxLib\Reservation\Models\Reservation;
use RzxLib\Reservation\Models\Service;
use RzxLib\Core\Helpers\Encryption;

$pageTitle = ($config['app_name'] ?? 'RezlyX') . ' - ' . __('booking.lookup.title');
$baseUrl = $config['app_url'] ?? '';

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
            // 예약 검색
            $query = Reservation::query();

            if (!empty($reservationNumber)) {
                $query->where('reservation_number', $reservationNumber);
            }

            if (!empty($email)) {
                // 이메일은 암호화되어 저장됨
                $encryptedEmail = Encryption::encrypt($email);
                $query->where('customer_email', $encryptedEmail);
            }

            if (!empty($phone)) {
                // 전화번호도 암호화되어 저장됨
                $encryptedPhone = Encryption::encrypt($phone);
                $query->where('customer_phone', $encryptedPhone);
            }

            $results = $query->orderBy('reservation_date', 'desc')->limit(20)->get();
            $reservations = array_map([Reservation::class, 'fromArray'], $results);

            if (empty($reservations)) {
                $error = __('booking.lookup.not_found');
            }
        } catch (\PDOException $e) {
            $error = __('booking.lookup.not_found');
            if ($config['debug'] ?? false) {
                error_log('Reservation lookup error: ' . $e->getMessage());
            }
        }
    }
}

// 상태별 색상 클래스
function getStatusBadgeClass($status) {
    $classes = [
        'pending' => 'bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400',
        'confirmed' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400',
        'cancelled' => 'bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400',
        'completed' => 'bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400',
        'no_show' => 'bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-400',
    ];
    return $classes[$status] ?? $classes['pending'];
}

// 헤더 포함
include BASE_PATH . '/resources/views/partials/header.php';
?>

    <main class="min-h-screen py-12">
        <div class="max-w-2xl mx-auto px-4 sm:px-6 lg:px-8">
            <!-- 페이지 제목 -->
            <div class="text-center mb-8">
                <h1 class="text-3xl font-bold text-gray-900 dark:text-white mb-2"><?php echo __('booking.lookup.title'); ?></h1>
                <p class="text-gray-600 dark:text-zinc-400"><?php echo __('booking.lookup.description'); ?></p>
            </div>

            <!-- 검색 폼 -->
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-6 mb-6">
                <form method="POST" class="space-y-5">
                    <!-- 예약번호 -->
                    <div>
                        <label for="booking_code" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                            <?php echo __('booking.lookup.booking_code'); ?>
                        </label>
                        <input type="text" name="booking_code" id="booking_code"
                               value="<?php echo htmlspecialchars($_POST['booking_code'] ?? ''); ?>"
                               placeholder="<?php echo __('booking.lookup.booking_code_placeholder'); ?>"
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

                    <!-- 이메일 또는 전화번호 -->
                    <div class="space-y-4">
                        <div>
                            <label for="email" class="block text-sm font-medium text-gray-700 dark:text-zinc-300 mb-1">
                                <?php echo __('booking.lookup.email'); ?>
                            </label>
                            <input type="email" name="email" id="email"
                                   value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>"
                                   placeholder="<?php echo __('booking.lookup.email_placeholder'); ?>"
                                   class="w-full px-4 py-3 border border-gray-300 dark:border-zinc-600 dark:bg-zinc-700 dark:text-white rounded-lg focus:ring-2 focus:ring-blue-500 focus:border-blue-500">
                        </div>
                        <div class="relative">
                            <div class="absolute inset-0 flex items-center">
                                <div class="w-full border-t border-gray-200 dark:border-zinc-700"></div>
                            </div>
                            <div class="relative flex justify-center text-sm">
                                <span class="px-3 bg-white dark:bg-zinc-800 text-gray-500 dark:text-zinc-400"><?php echo __('auth.social.or'); ?></span>
                            </div>
                        </div>
                        <div>
                            <?php
                            $phoneInputConfig = [
                                'name' => 'phone',
                                'id' => 'phone',
                                'label' => __('booking.lookup.phone'),
                                'value' => $_POST['phone'] ?? '',
                                'country_code' => $_POST['phone_country'] ?? '+82',
                                'phone_number' => $_POST['phone_number'] ?? '',
                                'required' => false,
                                'placeholder' => __('booking.lookup.phone_placeholder'),
                                'show_label' => true,
                            ];
                            include BASE_PATH . '/resources/views/components/phone-input.php';
                            ?>
                        </div>
                    </div>

                    <p class="text-xs text-gray-500 dark:text-zinc-400">
                        * <?php echo __('booking.lookup.hint'); ?>
                    </p>

                    <button type="submit" class="w-full px-6 py-3 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition shadow-lg shadow-blue-500/30">
                        <svg class="inline-block w-5 h-5 mr-2 -mt-0.5" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M21 21l-6-6m2-5a7 7 0 11-14 0 7 7 0 0114 0z"/>
                        </svg>
                        <?php echo __('booking.lookup.search'); ?>
                    </button>
                </form>
            </div>

            <!-- 에러 메시지 -->
            <?php if ($error): ?>
            <div class="mb-6 p-4 bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800 rounded-lg">
                <div class="flex items-center">
                    <svg class="w-5 h-5 text-red-400 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <span class="text-red-700 dark:text-red-300 text-sm"><?php echo htmlspecialchars($error); ?></span>
                </div>
            </div>
            <?php endif; ?>

            <!-- 검색 결과 -->
            <?php if (!empty($reservations)): ?>
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg overflow-hidden">
                <div class="px-6 py-4 border-b border-gray-200 dark:border-zinc-700">
                    <h2 class="text-lg font-semibold text-gray-900 dark:text-white">
                        <?php echo __('booking.lookup.result_title'); ?>
                    </h2>
                    <p class="text-sm text-gray-500 dark:text-zinc-400">
                        <?php echo __('booking.lookup.multiple_results', ['count' => count($reservations)]); ?>
                    </p>
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
                                        <?php echo htmlspecialchars($reservation->reservation_number); ?>
                                    </span>
                                    <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?php echo getStatusBadgeClass($reservation->status); ?>">
                                        <?php echo __('common.status.' . $reservation->status); ?>
                                    </span>
                                </div>
                                <h3 class="font-semibold text-gray-900 dark:text-white mb-1">
                                    <?php echo htmlspecialchars($serviceName); ?>
                                </h3>
                                <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-500 dark:text-zinc-400">
                                    <span class="flex items-center">
                                        <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                        </svg>
                                        <?php echo $reservation->getFormattedDateTime(); ?>
                                    </span>
                                    <span class="font-medium text-gray-900 dark:text-white">
                                        <?php echo $reservation->getFormattedPrice(); ?>
                                    </span>
                                </div>
                            </div>
                            <div class="flex items-center gap-2">
                                <a href="<?php echo $baseUrl; ?>/booking/detail/<?php echo urlencode($reservation->reservation_number); ?>"
                                   class="px-4 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition">
                                    <?php echo __('booking.detail.title'); ?>
                                </a>
                                <?php if ($reservation->isCancellable()): ?>
                                <a href="<?php echo $baseUrl; ?>/booking/cancel/<?php echo urlencode($reservation->reservation_number); ?>"
                                   class="px-4 py-2 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition">
                                    <?php echo __('booking.cancel.title'); ?>
                                </a>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>
            </div>
            <?php elseif ($searched && empty($error)): ?>
            <!-- 검색했지만 결과 없음 -->
            <div class="text-center py-12 bg-white dark:bg-zinc-800 rounded-2xl shadow-lg">
                <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-zinc-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                    <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M9.172 16.172a4 4 0 015.656 0M9 10h.01M15 10h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                </svg>
                <p class="text-gray-500 dark:text-zinc-400 mb-4"><?php echo __('booking.lookup.not_found'); ?></p>
                <a href="<?php echo $baseUrl; ?>/booking" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                    <?php echo __('common.nav.booking'); ?>
                </a>
            </div>
            <?php endif; ?>

            <!-- 도움말 링크 -->
            <div class="mt-8 text-center">
                <p class="text-sm text-gray-500 dark:text-zinc-400">
                    <?php echo __('booking.lookup.help_text'); ?>
                    <a href="<?php echo $baseUrl; ?>/contact" class="text-blue-600 dark:text-blue-400 hover:underline">
                        <?php echo __('booking.lookup.contact_support'); ?>
                    </a>
                </p>
            </div>
        </div>
    </main>

<!-- 전화번호 입력 컴포넌트 JS -->
<script src="<?php echo $baseUrl; ?>/assets/js/phone-input.js"></script>

<?php
// 푸터 포함
include BASE_PATH . '/resources/views/partials/footer.php';
?>
