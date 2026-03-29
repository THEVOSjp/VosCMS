<?php
/**
 * RezlyX - 예약 상세 페이지 (고객용 - 예약번호로 조회)
 */
require_once BASE_PATH . '/rzxlib/Core/I18n/Translator.php';
\RzxLib\Core\I18n\Translator::init(BASE_PATH . '/resources/lang');
if (!isset($currentLocale)) $currentLocale = current_locale();
if (!isset($baseUrl)) $baseUrl = rtrim($config['app_url'] ?? '', '/');
if (!isset($siteSettings)) $siteSettings = [];
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

// 예약 조회 (예약번호)
if (empty($reservationNumber)) {
    http_response_code(404);
    echo '<div class="max-w-2xl mx-auto px-4 py-12 text-center"><p class="text-zinc-500">' . __('booking.lookup.not_found') . '</p></div>';
    return;
}

$stmt = $pdo->prepare("SELECT * FROM {$prefix}reservations WHERE reservation_number = ?");
$stmt->execute([$reservationNumber]);
$reservation = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$reservation) {
    http_response_code(404);
    echo '<div class="max-w-2xl mx-auto px-4 py-12 text-center"><p class="text-zinc-500">' . __('booking.lookup.not_found') . '</p></div>';
    return;
}

// 공통 데이터 가공
include BASE_PATH . '/resources/views/components/reservation-detail-data.php';

$seoContext = ['type' => 'sub', 'subpage_title' => $pageTitle];
?>

<div class="max-w-7xl mx-auto px-4 sm:px-6 py-8">
    <div class="flex flex-col lg:flex-row gap-6 lg:gap-8">
        <!-- 좌측: 메인 콘텐츠 -->
        <div class="flex-1">
            <?php
            $backUrl = $baseUrl . '/lookup';
            $backLabel = __('booking.detail.back_to_lookup');
            include BASE_PATH . '/resources/views/components/reservation-detail-ui.php';
            ?>
        </div>

        <!-- 우측: 결제 정보 사이드바 -->
        <div class="w-full lg:w-80 shrink-0">
            <?php include BASE_PATH . '/resources/views/components/reservation-detail-payment.php'; ?>
        </div>
    </div>
</div>
