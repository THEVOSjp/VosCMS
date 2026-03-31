<?php
/**
 * RezlyX 마이페이지 - 예약 상세 (ID + user_id로 조회)
 */
require_once BASE_PATH . '/rzxlib/Core/Auth/Auth.php';
use RzxLib\Core\Auth\Auth;

if (!Auth::check()) {
    header('Location: ' . ($config['app_url'] ?? '') . '/login');
    exit;
}

$user = Auth::user();
$currentUser = $user;
$isLoggedIn = true;
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
if (!isset($currentLocale)) $currentLocale = $config['locale'] ?? (function_exists('current_locale') ? current_locale() : 'ko');

// 예약 조회 (ID + 본인 확인)
$stmt = $pdo->prepare("SELECT * FROM {$prefix}reservations WHERE id = ? AND user_id = ?");
$stmt->execute([$reservationId, $user['id']]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reservation) {
    http_response_code(404);
    echo '<div class="max-w-2xl mx-auto px-4 py-12 text-center"><p class="text-zinc-500">' . __('booking.lookup.not_found') . '</p></div>';
    return;
}

// 공통 데이터 가공
include BASE_PATH . '/resources/views/components/reservation-detail-data.php';
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8">
    <div class="flex flex-col lg:flex-row gap-6">
        <!-- 사이드바 -->
        <?php include __DIR__ . '/profile-sidebar.php'; ?>

        <!-- 메인 -->
        <div class="flex-1">
            <?php
            $backUrl = $baseUrl . '/mypage/reservations';
            $backLabel = __('auth.reservations.title');
            include BASE_PATH . '/resources/views/components/reservation-detail-ui.php';
            ?>
        </div>

        <!-- 결제 정보 사이드 패널 -->
        <div class="w-full lg:w-72 shrink-0">
            <?php include BASE_PATH . '/resources/views/components/reservation-detail-payment.php'; ?>
        </div>
    </div>
</div>
