<?php
/**
 * RezlyX Reservations List Page
 */

require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
require_once BASE_PATH . '/rzxlib/Reservation/Models/Reservation.php';
require_once BASE_PATH . '/rzxlib/Reservation/Models/Service.php';

use RzxLib\Core\Auth\Auth;
use RzxLib\Reservation\Models\Reservation;
use RzxLib\Reservation\Models\Service;

if (!Auth::check()) {
    header('Location: ' . ($config['app_url'] ?? '') . '/login');
    exit;
}

$user = Auth::user();
$pageTitle = ($config['app_name'] ?? 'RezlyX') . ' - ' . __('auth.reservations.title');
$baseUrl = $config['app_url'] ?? '';

// 헤더에서 사용할 변수
$isLoggedIn = true;
$currentUser = $user;

// 필터 처리
$filter = $_GET['filter'] ?? 'all';
$page = max(1, (int)($_GET['page'] ?? 1));
$perPage = 10;

// 예약 목록 가져오기
try {
    $allReservations = Reservation::forUser((string)$user['id']);
} catch (\PDOException $e) {
    // 테이블이 없거나 DB 오류 시 빈 배열
    $allReservations = [];
    if ($config['debug'] ?? false) {
        error_log('Reservation query error: ' . $e->getMessage());
    }
}

// 필터 적용
$filteredReservations = array_filter($allReservations, function($reservation) use ($filter) {
    $today = date('Y-m-d');

    switch ($filter) {
        case 'upcoming':
            return $reservation->reservation_date >= $today &&
                   !in_array($reservation->status, [Reservation::STATUS_CANCELLED, Reservation::STATUS_COMPLETED, Reservation::STATUS_NO_SHOW]);
        case 'past':
            return $reservation->reservation_date < $today ||
                   in_array($reservation->status, [Reservation::STATUS_COMPLETED, Reservation::STATUS_NO_SHOW]);
        default:
            return true;
    }
});

// 페이지네이션
$totalReservations = count($filteredReservations);
$totalPages = max(1, ceil($totalReservations / $perPage));
$page = min($page, $totalPages);
$offset = ($page - 1) * $perPage;
$reservations = array_slice($filteredReservations, $offset, $perPage);

// 예약 취소 처리
$message = '';
$messageType = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'cancel') {
    $reservationId = $_POST['reservation_id'] ?? '';
    $reservation = Reservation::find($reservationId);

    if ($reservation && $reservation->user_id === (string)$user['id']) {
        if ($reservation->isCancellable()) {
            if ($reservation->cancel($_POST['reason'] ?? null)) {
                $message = __('auth.reservations.cancel_success');
                $messageType = 'success';
                // 목록 새로고침
                header('Location: ' . $baseUrl . '/mypage/reservations?message=cancelled');
                exit;
            } else {
                $message = __('auth.reservations.cancel_error');
                $messageType = 'error';
            }
        } else {
            $message = __('auth.reservations.cannot_cancel');
            $messageType = 'error';
        }
    }
}

// URL 메시지 처리
if (isset($_GET['message']) && $_GET['message'] === 'cancelled') {
    $message = __('auth.reservations.cancel_success');
    $messageType = 'success';
}

// 상태별 색상 클래스
function getStatusClass($status) {
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

    <main class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
        <div class="lg:flex lg:gap-8">
            <!-- 사이드바 -->
            <aside class="lg:w-64 mb-6 lg:mb-0">
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-6 sticky top-24">
                    <div class="text-center mb-6">
                        <div class="w-20 h-20 bg-gradient-to-br from-blue-500 to-purple-600 rounded-full flex items-center justify-center mx-auto mb-3">
                            <span class="text-2xl font-bold text-white"><?php echo mb_substr($user['name'] ?? 'U', 0, 1); ?></span>
                        </div>
                        <h2 class="text-lg font-bold text-gray-900 dark:text-white"><?php echo htmlspecialchars($user['name'] ?? ''); ?></h2>
                        <p class="text-sm text-gray-500 dark:text-zinc-400"><?php echo htmlspecialchars($user['email'] ?? ''); ?></p>
                    </div>
                    <nav class="space-y-1">
                        <a href="<?php echo $baseUrl; ?>/mypage" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-600 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-700">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M3 12l2-2m0 0l7-7 7 7M5 10v10a1 1 0 001 1h3m10-11l2 2m-2-2v10a1 1 0 01-1 1h-3m-6 0a1 1 0 001-1v-4a1 1 0 011-1h2a1 1 0 011 1v4a1 1 0 001 1m-6 0h6"/></svg>
                            <?php echo __('auth.mypage.menu.dashboard'); ?>
                        </a>
                        <a href="<?php echo $baseUrl; ?>/mypage/reservations" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg bg-blue-50 dark:bg-blue-900/30 text-blue-600 dark:text-blue-400">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                            <?php echo __('auth.mypage.menu.reservations'); ?>
                        </a>
                        <a href="<?php echo $baseUrl; ?>/mypage/profile" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-600 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-700">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M16 7a4 4 0 11-8 0 4 4 0 018 0zM12 14a7 7 0 00-7 7h14a7 7 0 00-7-7z"/></svg>
                            <?php echo __('auth.mypage.menu.profile'); ?>
                        </a>
                        <a href="<?php echo $baseUrl; ?>/mypage/password" class="flex items-center px-4 py-3 text-sm font-medium rounded-lg text-gray-600 dark:text-zinc-300 hover:bg-gray-100 dark:hover:bg-zinc-700">
                            <svg class="w-5 h-5 mr-3" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 15v2m-6 4h12a2 2 0 002-2v-6a2 2 0 00-2-2H6a2 2 0 00-2 2v6a2 2 0 002 2zm10-10V7a4 4 0 00-8 0v4h8z"/></svg>
                            <?php echo __('auth.mypage.menu.password'); ?>
                        </a>
                    </nav>
                </div>
            </aside>

            <!-- 메인 콘텐츠 -->
            <div class="flex-1">
                <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-lg p-6">
                    <!-- 제목 및 필터 -->
                    <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between mb-6">
                        <div>
                            <h1 class="text-2xl font-bold text-gray-900 dark:text-white mb-1"><?php echo __('auth.reservations.title'); ?></h1>
                            <p class="text-gray-500 dark:text-zinc-400"><?php echo __('auth.reservations.description'); ?></p>
                        </div>
                        <a href="<?php echo $baseUrl; ?>/booking" class="mt-4 sm:mt-0 inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                            <svg class="w-5 h-5 mr-2" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 4v16m8-8H4"/></svg>
                            <?php echo __('auth.reservations.make_reservation'); ?>
                        </a>
                    </div>

                    <!-- 메시지 -->
                    <?php if ($message): ?>
                    <div class="mb-6 p-4 rounded-lg <?php echo $messageType === 'success' ? 'bg-green-50 dark:bg-green-900/30 border border-green-200 dark:border-green-800' : 'bg-red-50 dark:bg-red-900/30 border border-red-200 dark:border-red-800'; ?>">
                        <span class="<?php echo $messageType === 'success' ? 'text-green-700 dark:text-green-300' : 'text-red-700 dark:text-red-300'; ?> text-sm"><?php echo htmlspecialchars($message); ?></span>
                    </div>
                    <?php endif; ?>

                    <!-- 필터 탭 -->
                    <div class="flex gap-2 mb-6 border-b border-gray-200 dark:border-zinc-700">
                        <a href="?filter=all" class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition <?php echo $filter === 'all' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-zinc-400 hover:text-gray-700 dark:hover:text-zinc-200'; ?>">
                            <?php echo __('auth.reservations.filter.all'); ?> (<?php echo count($allReservations); ?>)
                        </a>
                        <a href="?filter=upcoming" class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition <?php echo $filter === 'upcoming' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-zinc-400 hover:text-gray-700 dark:hover:text-zinc-200'; ?>">
                            <?php echo __('auth.reservations.filter.upcoming'); ?>
                        </a>
                        <a href="?filter=past" class="px-4 py-2 text-sm font-medium border-b-2 -mb-px transition <?php echo $filter === 'past' ? 'border-blue-500 text-blue-600 dark:text-blue-400' : 'border-transparent text-gray-500 dark:text-zinc-400 hover:text-gray-700 dark:hover:text-zinc-200'; ?>">
                            <?php echo __('auth.reservations.filter.past'); ?>
                        </a>
                    </div>

                    <!-- 예약 목록 -->
                    <?php if (empty($reservations)): ?>
                    <div class="text-center py-12">
                        <svg class="w-16 h-16 mx-auto text-gray-300 dark:text-zinc-600 mb-4" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="1.5" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                        </svg>
                        <p class="text-gray-500 dark:text-zinc-400 mb-4"><?php echo __('auth.reservations.no_reservations'); ?></p>
                        <a href="<?php echo $baseUrl; ?>/booking" class="inline-flex items-center px-4 py-2 bg-blue-600 hover:bg-blue-700 text-white font-semibold rounded-lg transition">
                            <?php echo __('auth.reservations.make_reservation'); ?>
                        </a>
                    </div>
                    <?php else: ?>
                    <div class="space-y-4">
                        <?php foreach ($reservations as $reservation): ?>
                        <?php
                            $service = Service::find($reservation->service_id);
                            $serviceName = $service ? $service->name : '서비스';
                        ?>
                        <div class="border border-gray-200 dark:border-zinc-700 rounded-xl p-4 hover:shadow-md transition">
                            <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4">
                                <!-- 예약 정보 -->
                                <div class="flex-1">
                                    <div class="flex items-start gap-4">
                                        <div class="hidden sm:flex w-14 h-14 bg-blue-100 dark:bg-blue-900/30 rounded-lg items-center justify-center flex-shrink-0">
                                            <svg class="w-7 h-7 text-blue-600 dark:text-blue-400" fill="none" stroke="currentColor" viewBox="0 0 24 24">
                                                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/>
                                            </svg>
                                        </div>
                                        <div class="flex-1 min-w-0">
                                            <div class="flex items-center gap-2 mb-1">
                                                <h3 class="font-semibold text-gray-900 dark:text-white truncate"><?php echo htmlspecialchars($serviceName); ?></h3>
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-full text-xs font-medium <?php echo getStatusClass($reservation->status); ?>">
                                                    <?php echo __('auth.reservations.status.' . $reservation->status); ?>
                                                </span>
                                            </div>
                                            <p class="text-sm text-gray-600 dark:text-zinc-400 mb-2">
                                                <?php echo __('auth.reservations.booking_code'); ?>: <span class="font-mono font-medium"><?php echo htmlspecialchars($reservation->reservation_number); ?></span>
                                            </p>
                                            <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-gray-500 dark:text-zinc-400">
                                                <span class="flex items-center">
                                                    <svg class="w-4 h-4 mr-1" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M8 7V3m8 4V3m-9 8h10M5 21h14a2 2 0 002-2V7a2 2 0 00-2-2H5a2 2 0 00-2 2v12a2 2 0 002 2z"/></svg>
                                                    <?php echo $reservation->getFormattedDateTime(); ?>
                                                </span>
                                                <span class="flex items-center font-medium text-gray-900 dark:text-white">
                                                    <?php echo $reservation->getFormattedPrice(); ?>
                                                </span>
                                            </div>
                                        </div>
                                    </div>
                                </div>

                                <!-- 액션 버튼 -->
                                <div class="flex items-center gap-2 lg:flex-shrink-0">
                                    <a href="<?php echo $baseUrl; ?>/mypage/reservations/<?php echo $reservation->id; ?>" class="px-3 py-2 text-sm font-medium text-blue-600 dark:text-blue-400 hover:bg-blue-50 dark:hover:bg-blue-900/20 rounded-lg transition">
                                        <?php echo __('auth.reservations.view_detail'); ?>
                                    </a>
                                    <?php if ($reservation->isCancellable()): ?>
                                    <button type="button" onclick="openCancelModal('<?php echo htmlspecialchars($reservation->id); ?>', '<?php echo htmlspecialchars($reservation->reservation_number); ?>')" class="px-3 py-2 text-sm font-medium text-red-600 dark:text-red-400 hover:bg-red-50 dark:hover:bg-red-900/20 rounded-lg transition">
                                        <?php echo __('auth.reservations.cancel'); ?>
                                    </button>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>

                    <!-- 페이지네이션 -->
                    <?php if ($totalPages > 1): ?>
                    <div class="flex items-center justify-center gap-2 mt-6">
                        <?php if ($page > 1): ?>
                        <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page - 1; ?>" class="px-3 py-2 text-sm font-medium text-gray-600 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-lg transition">
                            <?php echo __('common.pagination.previous'); ?>
                        </a>
                        <?php endif; ?>

                        <?php for ($i = max(1, $page - 2); $i <= min($totalPages, $page + 2); $i++): ?>
                        <a href="?filter=<?php echo $filter; ?>&page=<?php echo $i; ?>" class="px-3 py-2 text-sm font-medium rounded-lg transition <?php echo $i === $page ? 'bg-blue-600 text-white' : 'text-gray-600 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-700'; ?>">
                            <?php echo $i; ?>
                        </a>
                        <?php endfor; ?>

                        <?php if ($page < $totalPages): ?>
                        <a href="?filter=<?php echo $filter; ?>&page=<?php echo $page + 1; ?>" class="px-3 py-2 text-sm font-medium text-gray-600 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-lg transition">
                            <?php echo __('common.pagination.next'); ?>
                        </a>
                        <?php endif; ?>
                    </div>
                    <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </main>

    <!-- 취소 확인 모달 -->
    <div id="cancelModal" class="fixed inset-0 z-50 hidden">
        <div class="absolute inset-0 bg-black/50 backdrop-blur-sm" onclick="closeCancelModal()"></div>
        <div class="absolute inset-0 flex items-center justify-center p-4">
            <div class="bg-white dark:bg-zinc-800 rounded-2xl shadow-xl max-w-md w-full p-6">
                <h3 class="text-lg font-bold text-gray-900 dark:text-white mb-2"><?php echo __('auth.reservations.cancel'); ?></h3>
                <p class="text-gray-500 dark:text-zinc-400 mb-4"><?php echo __('auth.reservations.cancel_confirm'); ?></p>
                <p class="text-sm text-gray-600 dark:text-zinc-300 mb-4">
                    <?php echo __('auth.reservations.booking_code'); ?>: <span id="cancelBookingCode" class="font-mono font-medium"></span>
                </p>
                <form id="cancelForm" method="POST">
                    <input type="hidden" name="action" value="cancel">
                    <input type="hidden" name="reservation_id" id="cancelReservationId">
                    <div class="flex justify-end gap-3">
                        <button type="button" onclick="closeCancelModal()" class="px-4 py-2 text-sm font-medium text-gray-600 dark:text-zinc-400 hover:bg-gray-100 dark:hover:bg-zinc-700 rounded-lg transition">
                            <?php echo __('common.buttons.cancel'); ?>
                        </button>
                        <button type="submit" class="px-4 py-2 text-sm font-medium text-white bg-red-600 hover:bg-red-700 rounded-lg transition">
                            <?php echo __('auth.reservations.cancel'); ?>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        function openCancelModal(id, code) {
            document.getElementById('cancelReservationId').value = id;
            document.getElementById('cancelBookingCode').textContent = code;
            document.getElementById('cancelModal').classList.remove('hidden');
        }

        function closeCancelModal() {
            document.getElementById('cancelModal').classList.add('hidden');
        }

        // ESC 키로 모달 닫기
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeCancelModal();
            }
        });
    </script>

<?php
// 푸터 포함
include BASE_PATH . '/resources/views/partials/footer.php';
?>
