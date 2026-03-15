<?php
/**
 * 예약 관리 공통 초기화
 * 변수: $pdo, $config, $siteSettings, $baseUrl, $adminUrl, $prefix, $csrfToken
 */

if (!function_exists('__')) {
    require_once BASE_PATH . '/rzxlib/Core/Helpers/lang.php';
}

$baseUrl = $config['app_url'] ?? '';
$adminUrl = $baseUrl . '/' . ($config['admin_path'] ?? 'admin');
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';

// CSRF 토큰
$csrfToken = $_SESSION['csrf_token'] ?? '';
if (empty($csrfToken)) {
    $csrfToken = bin2hex(random_bytes(32));
    $_SESSION['csrf_token'] = $csrfToken;
}

// 상태 배지 헬퍼
function statusBadge(string $status): string {
    $map = [
        'pending'   => ['bg-yellow-100 text-yellow-800 dark:bg-yellow-900/30 dark:text-yellow-400', __('reservations.filter.pending')],
        'confirmed' => ['bg-blue-100 text-blue-800 dark:bg-blue-900/30 dark:text-blue-400', __('reservations.filter.confirmed')],
        'completed' => ['bg-green-100 text-green-800 dark:bg-green-900/30 dark:text-green-400', __('reservations.actions.complete')],
        'cancelled' => ['bg-red-100 text-red-800 dark:bg-red-900/30 dark:text-red-400', __('reservations.actions.cancel')],
        'no_show'   => ['bg-zinc-100 text-zinc-800 dark:bg-zinc-700 dark:text-zinc-300', __('reservations.actions.no_show')],
    ];
    $info = $map[$status] ?? ['bg-zinc-100 text-zinc-800', $status];
    return '<span class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-medium ' . $info[0] . '">' . htmlspecialchars($info[1]) . '</span>';
}

// 서비스 목록 캐시
function getServices(\PDO $pdo, string $prefix): array {
    static $cache = null;
    if ($cache !== null) return $cache;
    $stmt = $pdo->query("SELECT s.*, c.name as category_name FROM {$prefix}services s LEFT JOIN {$prefix}service_categories c ON s.category_id = c.id WHERE s.is_active = 1 ORDER BY s.sort_order ASC, s.name ASC");
    $cache = $stmt->fetchAll(PDO::FETCH_ASSOC);
    return $cache;
}

// 통화 설정
$serviceCurrency = $siteSettings['service_currency'] ?? 'KRW';
$currencySymbols = [
    'KRW' => '₩', 'USD' => '$', 'JPY' => '¥', 'EUR' => '€',
    'CNY' => '¥', 'GBP' => '£', 'THB' => '฿', 'VND' => '₫',
    'MNT' => '₮', 'RUB' => '₽', 'TRY' => '₺', 'IDR' => 'Rp',
];
$currencySymbol = $currencySymbols[$serviceCurrency] ?? $serviceCurrency;
$currencyPosition = $siteSettings['service_currency_position'] ?? 'prefix'; // prefix or suffix

function formatPrice(float $amount): string {
    global $currencySymbol, $currencyPosition;
    $formatted = number_format($amount);
    return $currencyPosition === 'suffix'
        ? $formatted . $currencySymbol
        : $currencySymbol . $formatted;
}

// 서비스 이름 가져오기 (junction table 기반)
function getServiceName(\PDO $pdo, string $prefix, ?string $reservationId): string {
    if (!$reservationId) return '-';
    $stmt = $pdo->prepare("SELECT GROUP_CONCAT(service_name ORDER BY sort_order SEPARATOR ', ') FROM {$prefix}reservation_services WHERE reservation_id = ?");
    $stmt->execute([$reservationId]);
    $name = $stmt->fetchColumn();
    return $name ?: '-';
}

function getBundleName(\PDO $pdo, string $prefix, ?string $reservationId): ?string {
    if (!$reservationId) return null;
    $stmt = $pdo->prepare("SELECT DISTINCT b.name FROM {$prefix}reservation_services rs JOIN {$prefix}service_bundles b ON rs.bundle_id = b.id WHERE rs.reservation_id = ? AND rs.bundle_id IS NOT NULL LIMIT 1");
    $stmt->execute([$reservationId]);
    return $stmt->fetchColumn() ?: null;
}
