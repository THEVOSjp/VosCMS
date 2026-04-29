<?php
/**
 * 서비스 주문 API
 *
 * POST /api/service-order.php
 * Body (JSON): {
 *   payment_method: "card" | "bank",
 *   payment_token: "tok_xxx" (카드 결제 시),
 *   domain_option: "free" | "new" | "existing",
 *   domain: "example.com",
 *   hosting_plan: "1GB",
 *   contract_months: 12,
 *   items: [...],  // 프론트에서 계산한 항목 (서버에서 재계산)
 *   applicant: { name, email, phone, company, category },
 *   domains: { "example.com": 2850 },  // 선택된 도메인
 *   addons: ["설치 지원", ...],
 *   maintenance: "Basic",
 *   bizmail_count: 0,
 * }
 */
if (!headers_sent()) header('Content-Type: application/json; charset=utf-8');

if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__, 3));

// .env 로드
$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#') continue;
        if (strpos($line, '=') !== false) {
            [$k, $v] = explode('=', $line, 2);
            $_ENV[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
        }
    }
}

require_once BASE_PATH . '/vendor/autoload.php';

// POST만 허용
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    echo json_encode(['success' => false, 'message' => 'POST only']);
    exit;
}

// 세션 — 코어와 동일한 save_path 사용 (BASE_PATH/storage/sessions)
if (session_status() === PHP_SESSION_NONE) {
    $_sessionDir = BASE_PATH . '/storage/sessions';
    if (is_dir($_sessionDir)) ini_set('session.save_path', $_sessionDir);
    session_start();
}

// 입력 파싱
$input = json_decode(file_get_contents('php://input'), true);
if (!$input) {
    echo json_encode(['success' => false, 'message' => '요청 데이터가 없습니다.']);
    exit;
}

// DB 연결
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB 연결 실패']);
    exit;
}

// 사용자 확인
$userId = $_SESSION['user_id'] ?? null;
if (!$userId) {
    echo json_encode(['success' => false, 'message' => '로그인이 필요합니다.']);
    exit;
}

// 사용자 정보 DB에서 로드
$userStmt = $pdo->prepare("SELECT name, email, phone FROM {$prefix}users WHERE id = ?");
$userStmt->execute([$userId]);
$userData = $userStmt->fetch(PDO::FETCH_ASSOC) ?: [];

// ===== 서비스 설정 로드 =====
$svcSettings = [];
$sStmt = $pdo->prepare("SELECT `key`, `value` FROM {$prefix}settings WHERE `key` LIKE 'service_%'");
$sStmt->execute();
while ($r = $sStmt->fetch(PDO::FETCH_ASSOC)) $svcSettings[$r['key']] = $r['value'];

$hostingPlans = json_decode($svcSettings['service_hosting_plans'] ?? '[]', true) ?: [];
$hostingPeriods = json_decode($svcSettings['service_hosting_periods'] ?? '[]', true) ?: [];
$domainPricing = json_decode($svcSettings['service_domain_pricing'] ?? '[]', true) ?: [];
$addons = json_decode($svcSettings['service_addons'] ?? '[]', true) ?: [];
$maintenance = json_decode($svcSettings['service_maintenance'] ?? '[]', true) ?: [];
$currency = $svcSettings['service_currency'] ?? 'JPY';

// ===== Calendar 월말 마감 헬퍼 =====
// 첫 달 일할 = 월단가/30 × 가입달 잔여일수
function _proratedFirstAmount($monthlyPrice, $startDate) {
    $ts = strtotime($startDate);
    $daysInMonth = (int)date('t', $ts);
    $dayOfMonth = (int)date('j', $ts);
    $remainDays = max(1, $daysInMonth - $dayOfMonth + 1);
    return (int)round($monthlyPrice * $remainDays / 30);
}
// 정상 청구 시작일 = 다음달 1일 00:00:00
function _calendarBillingStart($startDate) {
    return date('Y-m-01 00:00:00', strtotime('first day of next month', strtotime($startDate)));
}
// Calendar 만료일 = billing_start + offset - 1일, 23:59:59
function _calendarExpires($billingStart, $offsetSpec) {
    return date('Y-m-d 23:59:59', strtotime($offsetSpec . ' -1 day', strtotime($billingStart)));
}

// ===== 서버에서 금액 재계산 =====
$items = [];
$subtotal = 0;

$paymentMethod = $input['payment_method'] ?? 'card';
$paymentToken = $input['payment_token'] ?? '';
$contractMonths = (int)($input['contract_months'] ?? 12);
$domainOption = $input['domain_option'] ?? 'free';
$domainName = $input['domain'] ?? '';
$selectedDomains = $input['domains'] ?? [];
$selectedAddons = $input['addons'] ?? [];
$selectedMaint = $input['maintenance'] ?? '';
$bizmailCount = (int)($input['bizmail_count'] ?? 0);
$bizmailAccounts = $input['bizmail_accounts'] ?? [];
$mailAccounts = $input['mail_accounts'] ?? [];
$applicant = $input['applicant'] ?? [];
// 프론트에서 안 보낸 필드는 DB 사용자 정보로 폴백
if (empty($applicant['email'])) $applicant['email'] = $userData['email'] ?? '';
if (empty($applicant['name'])) $applicant['name'] = $userData['name'] ?? '';
if (empty($applicant['phone'])) $applicant['phone'] = $userData['phone'] ?? '';

// 할인율 찾기
$discount = 0;
foreach ($hostingPeriods as $pd) {
    if ((int)$pd['months'] === $contractMonths) {
        $discount = (int)($pd['discount'] ?? 0);
        break;
    }
}

// 1. 도메인
$domainYears = max(1, ceil($contractMonths / 12));
if (!empty($selectedDomains)) {
    $domainTotal = 0;
    $domainPriceMap = [];
    foreach ($domainPricing as $dp) {
        $domainPriceMap[$dp['tld']] = $dp;
    }
    foreach ($selectedDomains as $fqdn => $frontPrice) {
        // TLD 추출하여 서버 가격으로 재계산
        $tld = '.' . implode('.', array_slice(explode('.', $fqdn), 1));
        $dp = $domainPriceMap[$tld] ?? null;
        $isLoggedIn = !empty($userId);
        $price = $dp ? (int)($isLoggedIn && !empty($dp['vip_price']) ? $dp['vip_price'] : ($dp['price'] ?? 0)) : 0;
        $domainTotal += $price;
    }
    $domainAmount = $domainTotal * $domainYears;
    $items[] = [
        'type' => 'domain',
        'label' => '도메인',
        'qty' => count($selectedDomains) . '개 × ' . $domainYears . '년',
        'unit_price' => $domainTotal,
        'amount' => $domainAmount,
        'detail' => array_keys($selectedDomains),
        'tax_excluded' => true,
    ];
    $subtotal += $domainAmount;
}

// 2. 호스팅
$hostingPlanName = $input['hosting_plan'] ?? '';
$hostingInfo = null;
foreach ($hostingPlans as $p) {
    $val = strtolower(str_replace(' ', '', $p['capacity'] ?? ''));
    if ($val === $hostingPlanName || $p['label'] === $hostingPlanName) {
        $hostingInfo = $p;
        break;
    }
}

if ($hostingInfo && (int)$hostingInfo['price'] > 0) {
    $monthlyPrice = (int)$hostingInfo['price'];
    $hostingTotal = $monthlyPrice * $contractMonths;
    $hostingDiscount = (int)floor($hostingTotal * $discount / 100);
    $hostingFinal = $hostingTotal - $hostingDiscount;

    $items[] = [
        'type' => 'hosting',
        'label' => '웹 호스팅 ' . ($hostingInfo['capacity'] ?? ''),
        'qty' => $contractMonths . '개월',
        'unit_price' => $monthlyPrice,
        'amount' => $hostingTotal,
    ];
    if ($hostingDiscount > 0) {
        $items[] = [
            'type' => 'hosting_discount',
            'label' => '장기 할인 (' . $discount . '%)',
            'amount' => -$hostingDiscount,
        ];
    }
    // 첫 달 일할 (가입일~말일) — 정상 N개월 외 추가 청구
    $_now = date('Y-m-d H:i:s');
    $proratedHosting = _proratedFirstAmount($monthlyPrice, $_now);
    if ($proratedHosting > 0) {
        $_remainDays = max(1, (int)date('t', strtotime($_now)) - (int)date('j', strtotime($_now)) + 1);
        $items[] = [
            'type' => 'hosting_prorated',
            'label' => '첫 달 일할 (' . $_remainDays . '일)',
            'qty' => $_remainDays . '일',
            'unit_price' => (int)round($monthlyPrice / 30),
            'amount' => $proratedHosting,
        ];
        $subtotal += $proratedHosting;
    }
    $subtotal += $hostingFinal;
} elseif ($hostingInfo) {
    $items[] = [
        'type' => 'hosting',
        'label' => '웹 호스팅 (무료 ' . ($hostingInfo['capacity'] ?? '') . ')',
        'qty' => '1개월',
        'unit_price' => 0,
        'amount' => 0,
    ];
}

// 3. 부가 서비스
foreach ($addons as $addon) {
    $addonLabel = $addon['label'] ?? '';
    if (!in_array($addonLabel, $selectedAddons)) continue;

    $price = (int)($addon['price'] ?? 0);
    $unit = $addon['unit'] ?? '';
    $isBizmail = stripos($addonLabel, '비즈니스 메일') !== false || stripos($addonLabel, 'ビジネスメール') !== false;
    $isMonthly = strpos($unit, '/월') !== false || strpos($unit, '/계정/월') !== false;
    $isYearly = strpos($unit, '/년') !== false;

    if ($isBizmail && $bizmailCount > 0) {
        $mailTotal = $price * $bizmailCount * $contractMonths;
        $mailDiscount = $discount > 0 ? (int)floor($mailTotal * $discount / 100) : 0;
        $items[] = ['type' => 'addon', 'label' => $addonLabel, 'qty' => $bizmailCount . '계정 × ' . $contractMonths . '개월', 'unit_price' => $price, 'amount' => $mailTotal];
        if ($mailDiscount > 0) {
            $items[] = ['type' => 'addon_discount', 'label' => '할인 (' . $discount . '%)', 'amount' => -$mailDiscount];
        }
        $subtotal += $mailTotal - $mailDiscount;
    } elseif ($price > 0 && $isMonthly) {
        $monthTotal = $price * $contractMonths;
        $monthDiscount = $discount > 0 ? (int)floor($monthTotal * $discount / 100) : 0;
        $items[] = ['type' => 'addon', 'label' => $addonLabel, 'qty' => $contractMonths . '개월', 'unit_price' => $price, 'amount' => $monthTotal];
        if ($monthDiscount > 0) {
            $items[] = ['type' => 'addon_discount', 'label' => '할인 (' . $discount . '%)', 'amount' => -$monthDiscount];
        }
        $subtotal += $monthTotal - $monthDiscount;
    } elseif ($price > 0 && $isYearly) {
        $years = max(1, ceil($contractMonths / 12));
        $yearTotal = $price * $years;
        $items[] = ['type' => 'addon', 'label' => $addonLabel, 'qty' => $years . '년', 'unit_price' => $price, 'amount' => $yearTotal];
        $subtotal += $yearTotal;
    } elseif ($price > 0) {
        $items[] = ['type' => 'addon', 'label' => $addonLabel, 'qty' => 1, 'unit_price' => $price, 'amount' => $price];
        $subtotal += $price;
    } else {
        $items[] = ['type' => 'addon', 'label' => $addonLabel, 'qty' => '', 'unit_price' => 0, 'amount' => 0];
    }
}

// 4. 유지보수
if ($selectedMaint) {
    foreach ($maintenance as $mt) {
        if ($mt['label'] === $selectedMaint && (int)$mt['price'] > 0) {
            $mp = (int)$mt['price'];
            $maintTotal = $mp * $contractMonths;
            $maintDiscount = $discount > 0 ? (int)floor($maintTotal * $discount / 100) : 0;
            $items[] = ['type' => 'maintenance', 'label' => '유지보수 ' . $mt['label'], 'qty' => $contractMonths . '개월', 'unit_price' => $mp, 'amount' => $maintTotal];
            if ($maintDiscount > 0) {
                $items[] = ['type' => 'maintenance_discount', 'label' => '할인 (' . $discount . '%)', 'amount' => -$maintDiscount];
            }
            $subtotal += $maintTotal - $maintDiscount;
            break;
        }
    }
}

// 5. 메일 계정 정보 (금액 없음, 설치 작업용 데이터)
require_once BASE_PATH . '/rzxlib/Core/Helpers/Encryption.php';
require_once BASE_PATH . '/rzxlib/Core/Helpers/functions.php';

$encMailAccounts = [];
$encBizmailAccounts = [];

if (!empty($mailAccounts)) {
    $encMailAccounts = array_map(function($m) {
        return ['address' => $m['address'] ?? '', 'password' => mail_password_hash($m['password'] ?? '')];
    }, $mailAccounts);
    $items[] = ['type' => 'mail_basic', 'label' => '기본 메일', 'qty' => count($mailAccounts), 'accounts' => $encMailAccounts];
}

if (!empty($bizmailAccounts)) {
    $encBizmailAccounts = array_map(function($m) {
        return ['address' => $m['address'] ?? '', 'password' => mail_password_hash($m['password'] ?? '')];
    }, $bizmailAccounts);
    $items[] = ['type' => 'mail_business', 'label' => '비즈니스 메일', 'qty' => count($bizmailAccounts), 'accounts' => $encBizmailAccounts];
}

// ===== 구독 데이터 사전 계산 (모든 서비스 — 유무료 무관) =====
$subscriptionData = [];

// (1) 호스팅
if ($hostingInfo) {
    $hPrice = (int)($hostingInfo['price'] ?? 0);
    $subscriptionData[] = [
        'type' => 'hosting',
        'service_class' => $hPrice > 0 ? 'recurring' : 'free',
        'label' => $hPrice > 0
            ? '웹 호스팅 ' . ($hostingInfo['capacity'] ?? '')
            : '웹 호스팅 (무료 ' . ($hostingInfo['capacity'] ?? '') . ')',
        'unit_price' => $hPrice,
        'quantity' => 1,
        'billing_amount' => $hPrice > 0 ? $hPrice * $contractMonths : 0,
        'billing_cycle' => $hPrice > 0 ? 'custom' : 'monthly',
        'billing_months' => $hPrice > 0 ? $contractMonths : 1,
        'expires_offset' => $hPrice > 0 ? "+{$contractMonths} months" : '+1 month',
        // mail_accounts 는 mail subscription 에만 저장 (single source of truth)
        'metadata' => ['capacity' => $hostingInfo['capacity'] ?? ''],
    ];
}

// (2) 도메인
if (!empty($selectedDomains)) {
    $subscriptionData[] = [
        'type' => 'domain',
        'service_class' => 'recurring',
        'label' => '도메인',
        'unit_price' => $domainTotal ?? 0,
        'quantity' => count($selectedDomains),
        'billing_amount' => $domainAmount ?? 0,
        'billing_cycle' => 'yearly',
        'billing_months' => 12,
        'expires_offset' => "+{$domainYears} years",
        'metadata' => ['domains' => array_keys($selectedDomains)],
    ];
} elseif ($domainOption === 'free' && !empty($domainName)) {
    // 서버 측 가용성 재검증 — DB(reserved_subdomains) 우선, Cloudflare API fallback
    $_parts = explode('.', strtolower($domainName), 2);
    $_sub = $_parts[0] ?? '';
    $_zone = $_parts[1] ?? '';
    if (!$_sub || !$_zone) {
        echo json_encode(['success' => false, 'message' => '잘못된 서브도메인 형식입니다.']); exit;
    }
    try {
        $_cf = new \RzxLib\Core\Dns\CloudflareDns($_ENV['CLOUDFLARE_API_TOKEN'] ?? '');
        $_chk = $_cf->checkSubdomainAvailability($_zone, $_sub, $pdo);
        if (!($_chk['available'] ?? false)) {
            echo json_encode(['success' => false, 'message' => "{$domainName} 은(는) 이미 사용 중입니다."]); exit;
        }
    } catch (\Throwable $_e) {
        error_log('[service-order] subdomain availability check failed: ' . $_e->getMessage());
        echo json_encode(['success' => false, 'message' => '서브도메인 가용성 확인 중 오류가 발생했습니다.']); exit;
    }

    $subscriptionData[] = [
        'type' => 'domain',
        'service_class' => 'free',
        'label' => '도메인 (무료)',
        'unit_price' => 0,
        'quantity' => 1,
        'billing_amount' => 0,
        'billing_cycle' => 'monthly',
        'billing_months' => 1,
        'expires_offset' => '+1 month',
        'metadata' => ['domains' => [$domainName], 'free_subdomain' => true],
    ];
} elseif ($domainOption === 'existing' && !empty($domainName)) {
    $subscriptionData[] = [
        'type' => 'domain',
        'service_class' => 'free',
        'label' => '도메인 (보유)',
        'unit_price' => 0,
        'quantity' => 1,
        'billing_amount' => 0,
        'billing_cycle' => 'custom',
        'billing_months' => $contractMonths,
        'expires_offset' => "+{$contractMonths} months",
        'metadata' => ['domains' => [$domainName], 'existing' => true],
    ];
}

// (3) 부가서비스 (비즈메일 제외)
foreach ($addons as $addon) {
    $addonLabel = $addon['label'] ?? '';
    if (!in_array($addonLabel, $selectedAddons)) continue;
    $isBizmail = stripos($addonLabel, '비즈니스 메일') !== false || stripos($addonLabel, 'ビジネスメール') !== false;
    if ($isBizmail) continue;

    $aPrice = (int)($addon['price'] ?? 0);
    $aUnit = $addon['unit'] ?? '';
    $aIsOneTime = !empty($addon['one_time']);
    $aIsMonthly = strpos($aUnit, '/월') !== false;
    $aIsYearly = strpos($aUnit, '/년') !== false;
    $aServiceClass = $aIsOneTime ? 'one_time' : ($aPrice > 0 ? 'recurring' : 'free');

    // 설치 지원: 관리자 정보를 metadata에 저장
    $aBaseMeta = null;
    if (($addon['_id'] ?? '') === 'install' && !empty($input['install_info'])) {
        $aBaseMeta = ['install_info' => $input['install_info']];
    }

    if ($aIsOneTime) {
        $subscriptionData[] = [
            'type' => 'addon', 'service_class' => 'one_time', 'label' => $addonLabel,
            'unit_price' => $aPrice, 'quantity' => 1,
            'billing_amount' => $aPrice,
            'billing_cycle' => 'once', 'billing_months' => 0,
            'expires_offset' => "+{$contractMonths} months",
            'metadata' => $aBaseMeta,
        ];
    } elseif ($aIsMonthly) {
        $subscriptionData[] = [
            'type' => 'addon', 'service_class' => $aServiceClass, 'label' => $addonLabel,
            'unit_price' => $aPrice, 'quantity' => 1,
            'billing_amount' => $aPrice * $contractMonths,
            'billing_cycle' => 'custom', 'billing_months' => $contractMonths,
            'expires_offset' => "+{$contractMonths} months",
            'metadata' => $aBaseMeta,
        ];
    } elseif ($aIsYearly) {
        $aYears = max(1, ceil($contractMonths / 12));
        $subscriptionData[] = [
            'type' => 'addon', 'service_class' => $aServiceClass, 'label' => $addonLabel,
            'unit_price' => $aPrice, 'quantity' => 1,
            'billing_amount' => $aPrice * $aYears,
            'billing_cycle' => 'yearly', 'billing_months' => 12,
            'expires_offset' => "+{$aYears} years",
            'metadata' => $aBaseMeta,
        ];
    } else {
        $aExtraMeta = $aBaseMeta ?? [];
        if ($aUnit === '별도 견적') $aExtraMeta['quote_required'] = true;
        $subscriptionData[] = [
            'type' => 'addon', 'service_class' => $aServiceClass, 'label' => $addonLabel,
            'unit_price' => $aPrice, 'quantity' => 1,
            'billing_amount' => $aPrice,
            'billing_cycle' => 'custom', 'billing_months' => $contractMonths,
            'expires_offset' => "+{$contractMonths} months",
            'metadata' => $aExtraMeta ?: null,
        ];
    }
}

// (4) 유지보수
if ($selectedMaint) {
    foreach ($maintenance as $mt) {
        if ($mt['label'] === $selectedMaint) {
            $mp = (int)($mt['price'] ?? 0);
            $subscriptionData[] = [
                'type' => 'maintenance',
                'service_class' => $mp > 0 ? 'recurring' : 'free',
                'label' => '유지보수 ' . $mt['label'],
                'unit_price' => $mp, 'quantity' => 1,
                'billing_amount' => $mp * $contractMonths,
                'billing_cycle' => 'custom', 'billing_months' => $contractMonths,
                'expires_offset' => "+{$contractMonths} months",
                'metadata' => null,
            ];
            break;
        }
    }
}

// (5) 기본 메일 (호스팅 포함 무료 메일 계정)
if (!empty($encMailAccounts)) {
    $subscriptionData[] = [
        'type' => 'mail',
        'service_class' => 'free',
        'label' => '기본 메일',
        'unit_price' => 0, 'quantity' => count($encMailAccounts),
        'billing_amount' => 0,
        'billing_cycle' => 'custom', 'billing_months' => $contractMonths,
        'expires_offset' => $hostingInfo && (int)($hostingInfo['price'] ?? 0) > 0
            ? "+{$contractMonths} months" : '+1 month',
        'metadata' => ['accounts' => count($encMailAccounts), 'mail_accounts' => $encMailAccounts],
    ];
}

// (6) 비즈니스 메일
if ($bizmailCount > 0) {
    foreach ($addons as $addon) {
        if (stripos($addon['label'], '비즈니스 메일') !== false || stripos($addon['label'], 'ビジネスメール') !== false) {
            $bPrice = (int)($addon['price'] ?? 0);
            $subscriptionData[] = [
                'type' => 'mail',
                'service_class' => $bPrice > 0 ? 'recurring' : 'free',
                'label' => $addon['label'],
                'unit_price' => $bPrice, 'quantity' => $bizmailCount,
                'billing_amount' => $bPrice * $bizmailCount * $contractMonths,
                'billing_cycle' => 'custom', 'billing_months' => $contractMonths,
                'expires_offset' => "+{$contractMonths} months",
                'metadata' => ['accounts' => $bizmailCount, 'mail_accounts' => $encBizmailAccounts],
            ];
            break;
        }
    }
}

/**
 * 구독 레코드 일괄 INSERT (모든 결제 경로에서 공통 사용)
 */
/**
 * 결제 완료 후 메일 도메인 자동 프로비저닝.
 * voscms.com 의 임시 서브도메인 (customer-XXXX.voscms.com) 자동 발급.
 * 외부 IO (Cloudflare API + SSH to mx1) 라 transaction 외부에서 호출.
 * 실패해도 주문 완료 자체는 영향 없음 (로그만 기록).
 */
/**
 * 호스팅 자동 프로비저닝 (결제 완료 후 호출).
 * - free subdomain: 즉시 셋업 (Cloudflare DNS 가 이미 등록되어 있어 SSL 발급 가능)
 * - new/existing 도메인: NS 변경 후 관리자 활성화 시점에 별도 호출
 *
 * 'install' addon 이 신청에 포함되어 있으면 metadata.install_info 로 voscms 자동 설치.
 *
 * 실패해도 주문 자체는 영향 없음 (로그만 기록, 관리자 후속 조치 가능).
 */
function _autoProvisionHosting($pdo, $prefix, $orderId, $orderNumber) {
    try {
        // 주문 + 호스팅 정보 로드
        $orderStmt = $pdo->prepare("SELECT * FROM {$prefix}orders WHERE id = ?");
        $orderStmt->execute([$orderId]);
        $order = $orderStmt->fetch(\PDO::FETCH_ASSOC);
        if (!$order) return;

        $domain = $order['domain'] ?? '';
        $capacity = $order['hosting_capacity'] ?? '';
        $domainOption = $order['domain_option'] ?? 'free';

        // 호스팅 신청이 없거나 도메인 미정인 경우 skip
        if (empty($domain) || empty($capacity)) {
            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type) VALUES (?, 'hosting_provision_skipped', ?, 'system')")
                ->execute([$orderId, json_encode(['reason' => 'no domain or capacity'])]);
            return;
        }

        // free 만 즉시 셋업 (new/existing 은 도메인 등록/NS 변경 후 별도)
        if ($domainOption !== 'free') {
            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type) VALUES (?, 'hosting_provision_deferred', ?, 'system')")
                ->execute([$orderId, json_encode(['domain_option' => $domainOption, 'note' => '도메인 활성화 후 관리자가 수동 트리거'])]);
            return;
        }

        // HostingProvisioner 호출
        $provisioner = new \RzxLib\Core\Hosting\HostingProvisioner($pdo);
        $result = $provisioner->provision($orderNumber, $domain, $capacity);

        // 'install' addon 의 install_info 가 있으면 voscms 자동 설치
        if ($result['success'] && !empty($result['db']['success'])) {
            $aSt = $pdo->prepare("SELECT id, metadata FROM {$prefix}subscriptions WHERE order_id = ? AND type = 'addon'");
            $aSt->execute([$orderId]);
            while ($aRow = $aSt->fetch(\PDO::FETCH_ASSOC)) {
                $aMeta = json_decode($aRow['metadata'] ?? '{}', true) ?: [];
                if (!empty($aMeta['install_info'])) {
                    $installResult = $provisioner->installVoscms($orderNumber, $domain, $result['db'], $aMeta['install_info']);
                    $result['install'] = $installResult;

                    // 설치 성공 시 addon metadata 에 완료 시점 기록 (탭 노출 트리거)
                    if (!empty($installResult['success'])) {
                        $aMeta['install_completed_at'] = $installResult['installed_at'] ?? date('c');
                        $aMeta['install_admin_url'] = $installResult['admin_url'] ?? null;
                        $upd = $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?");
                        $upd->execute([json_encode($aMeta, JSON_UNESCAPED_UNICODE), $aRow['id']]);
                    }
                    break;
                }
            }
        }

        $action = $result['success'] ? 'hosting_provisioned' : 'hosting_provision_failed';
        $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type) VALUES (?, ?, ?, 'system')")
            ->execute([$orderId, $action, json_encode($result, JSON_UNESCAPED_UNICODE)]);

        // 호스팅 subscription 의 metadata 에 server 정보 저장
        if ($result['success']) {
            $hostStmt = $pdo->prepare("SELECT id, metadata FROM {$prefix}subscriptions WHERE order_id = ? AND type = 'hosting' LIMIT 1");
            $hostStmt->execute([$orderId]);
            if ($hSub = $hostStmt->fetch(\PDO::FETCH_ASSOC)) {
                $hMeta = json_decode($hSub['metadata'] ?? '{}', true) ?: [];
                $hMeta['hosting_provisioned'] = true;
                $hMeta['hosting_provisioned_at'] = date('c');
                $hMeta['server'] = array_merge($hMeta['server'] ?? [], [
                    'ftp' => ['host' => $domain, 'user' => $result['username'], 'port' => 21,
                              'sftp_host' => 'ftp.voscms.com', 'sftp_port' => 2222],
                    'db' => $result['db'],
                    'env' => ['php' => '8.3'],
                ]);
                $pdo->prepare("UPDATE {$prefix}subscriptions SET metadata = ? WHERE id = ?")
                    ->execute([json_encode($hMeta, JSON_UNESCAPED_UNICODE), $hSub['id']]);
            }
        }
    } catch (\Throwable $e) {
        error_log("[hosting provisioner] order $orderNumber: " . $e->getMessage());
        try {
            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type) VALUES (?, 'hosting_provision_failed', ?, 'system')")
                ->execute([$orderId, json_encode(['error' => substr($e->getMessage(), 0, 500)], JSON_UNESCAPED_UNICODE)]);
        } catch (\Throwable $e2) { /* silent */ }
    }
}

function _autoProvisionMailDomain($pdo, $prefix, $orderId, $orderNumber) {
    try {
        $provisioner = new \RzxLib\Core\Mail\MailDomainProvisioner($pdo);
        $result = $provisioner->provisionForOrder((int)$orderId);
        $action = !empty($result['provisioned']) ? 'mail_provisioned' : 'mail_provision_skipped';
        $detail = ['result' => $result];
        $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type) VALUES (?, ?, ?, 'system')")
            ->execute([$orderId, $action, json_encode($detail, JSON_UNESCAPED_UNICODE)]);

        // 알림 발송 (mode 별)
        $mode = $result['mode'] ?? null;
        try {
            $notifier = new \RzxLib\Core\Mail\MailNotifier($pdo);
            if ($mode === 'new_pending' || $mode === 'existing_pending') {
                $info = $provisioner->getProvisionInfo((int)$orderId) ?? [];
                $notifier->notifyAdminProvisionRequired((int)$orderId, $mode, $info);
            } elseif ($mode === 'active') {
                // free 케이스는 즉시 active — 고객에게 사용 가능 알림
                $notifier->notifyCustomerMailReady((int)$orderId);
            }
        } catch (\Throwable $ne) {
            error_log("[mail notifier] order $orderNumber: " . $ne->getMessage());
        }
    } catch (\Throwable $e) {
        error_log("[mail provisioner] order $orderNumber: " . $e->getMessage());
        try {
            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type) VALUES (?, 'mail_provision_failed', ?, 'system')")
                ->execute([$orderId, json_encode(['error' => substr($e->getMessage(), 0, 500)], JSON_UNESCAPED_UNICODE)]);
        } catch (\Throwable $e2) { /* silent */ }
    }
}

function _insertSubscriptions($pdo, $prefix, $orderId, $userId, $currency, $subscriptionData, $status, $now, $customerId = null, $gwName = null) {
    $stmt = $pdo->prepare("INSERT INTO {$prefix}subscriptions
        (order_id, user_id, type, service_class, label, unit_price, quantity, billing_amount, billing_cycle, billing_months,
         currency, started_at, billing_start, expires_at, next_billing_at, auto_renew, payment_customer_id, payment_gateway, status, metadata)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
    // 정상 청구 시작 = 다음달 1일 (일할 첫 달 이후)
    $billingStart = _calendarBillingStart($now);
    foreach ($subscriptionData as $sub) {
        $serviceClass = $sub['service_class'] ?? 'recurring';
        $autoRenew = ($serviceClass === 'recurring') ? 1 : 0;
        // 도메인은 anniversary 유지 (1년 단위 등록), 그 외는 calendar 월말 마감
        if ($sub['type'] === 'domain') {
            $exp = date('Y-m-d H:i:s', strtotime($sub['expires_offset'] . ' -1 day', strtotime($now)));
            $bStart = $now;
        } else {
            $exp = _calendarExpires($billingStart, $sub['expires_offset']);
            $bStart = $billingStart;
        }
        $nextBilling = ($serviceClass === 'one_time') ? null : $exp;
        $subStatus = ($serviceClass === 'one_time') ? 'pending' : $status;
        $stmt->execute([
            $orderId, $userId, $sub['type'], $serviceClass, $sub['label'],
            $sub['unit_price'], $sub['quantity'], $sub['billing_amount'],
            $sub['billing_cycle'], $sub['billing_months'], $currency,
            $now, $bStart, $exp, $nextBilling, $autoRenew,
            $customerId, $gwName, $subStatus,
            $sub['metadata'] ? json_encode($sub['metadata'], JSON_UNESCAPED_UNICODE) : null,
        ]);
    }
}

// 부가세
$taxRate = 10;
$tax = (int)round($subtotal * $taxRate / 100);
$total = $subtotal + $tax;

// ===== 주문 생성 =====
$uuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
    mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
    mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff)
);
$orderNumber = 'SVC' . date('ymd') . strtoupper(substr(md5(uniqid()), 0, 6));

$pdo->beginTransaction();

try {
    // rzx_orders INSERT
    $stmt = $pdo->prepare("INSERT INTO {$prefix}orders
        (uuid, order_number, user_id, status, items, subtotal, tax_rate, tax, total, currency,
         contract_months, payment_method, domain, domain_option, hosting_plan, hosting_capacity,
         applicant_name, applicant_email, applicant_phone, applicant_company, applicant_category)
        VALUES (?, ?, ?, 'pending', ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?, ?,
                ?, ?, ?, ?, ?)");
    $stmt->execute([
        $uuid, $orderNumber, $userId,
        json_encode($items, JSON_UNESCAPED_UNICODE),
        $subtotal, $taxRate, $tax, $total, $currency,
        $contractMonths, $paymentMethod,
        $domainName, $domainOption,
        $hostingInfo['label'] ?? '', $hostingInfo['capacity'] ?? '',
        $applicant['name'] ?? '', $applicant['email'] ?? '',
        $applicant['phone'] ?? '', $applicant['company'] ?? '',
        $applicant['category'] ?? '',
    ]);
    $orderId = $pdo->lastInsertId();

    // 주문 로그
    $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type, actor_id) VALUES (?, 'created', ?, 'user', ?)")
        ->execute([$orderId, json_encode(['total' => $total, 'currency' => $currency]), $userId]);

    // ===== 결제 처리 =====
    // ===== 무료 주문 (총액 0원) — 결제 없이 바로 활성화 =====
    if ($total <= 0) {
        $now = date('Y-m-d H:i:s');
        $expiresAt = _calendarExpires(_calendarBillingStart($now), "+{$contractMonths} months");
        $pdo->prepare("UPDATE {$prefix}orders SET status='paid', payment_method='free', started_at=?, expires_at=? WHERE id=?")
            ->execute([$now, $expiresAt, $orderId]);

        // 모든 구독 레코드 생성
        _insertSubscriptions($pdo, $prefix, $orderId, $userId, $currency, $subscriptionData, 'active', $now);

        $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type) VALUES (?, 'paid', '무료 주문', 'system')")
            ->execute([$orderId]);

        $pdo->commit();

        // 자동 프로비저닝 (transaction 외부) — 호스팅 → 메일 순서
        _autoProvisionHosting($pdo, $prefix, $orderId, $orderNumber);
        _autoProvisionMailDomain($pdo, $prefix, $orderId, $orderNumber);

        echo json_encode([
            'success' => true,
            'order_number' => $orderNumber,
            'total' => 0,
            'currency' => $currency,
            'message' => '서비스가 활성화되었습니다.',
        ]);
        exit;
    }

    if ($paymentMethod === 'card' && $paymentToken && strlen($paymentToken) > 10 && strpos($paymentToken, 'tok_') === 0) {
        // PaymentManager로 PG사 결제
        $payMgr = new \RzxLib\Modules\Payment\PaymentManager($pdo, $prefix);
        $gateway = $payMgr->gateway();
        $gwName = $payMgr->getGatewayName();

        // 1) Customer 생성 (카드 저장 — 구독 갱신용)
        $customerId = null;
        if ($gwName === 'payjp' && method_exists($gateway, 'createCustomer')) {
            $custResult = $gateway->createCustomer($paymentToken, $applicant['email'] ?? '', ['order_id' => $orderNumber]);
            if ($custResult['success'] ?? false) {
                $customerId = $custResult['customer_id'];
            } else {
                $pdo->prepare("UPDATE {$prefix}orders SET status='failed' WHERE id=?")->execute([$orderId]);
                $pdo->commit();
                echo json_encode(['success' => false, 'message' => '카드 등록에 실패했습니다: ' . ($custResult['message'] ?? '')]);
                exit;
            }
        }

        // 2) Customer의 카드로 첫 결제 (Charge)
        $result = $gateway->chargeCustomer($customerId, $total, strtolower($currency), 'VosCMS Service Order: ' . $orderNumber);

        if ($result->isSuccessful()) {
            // 결제 성공
            $paymentStmt = $pdo->prepare("INSERT INTO {$prefix}payments
                (uuid, user_id, order_id, payment_key, gateway, method, method_detail, amount, status, paid_at, raw_response)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, 'paid', NOW(), ?)");
            $payUuid = sprintf('%04x%04x-%04x-%04x-%04x-%04x%04x%04x',
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff),
                mt_rand(0, 0x0fff) | 0x4000, mt_rand(0, 0x3fff) | 0x8000,
                mt_rand(0, 0xffff), mt_rand(0, 0xffff), mt_rand(0, 0xffff));
            $paymentStmt->execute([
                $payUuid, $userId, $orderNumber,
                $result->paymentKey, $gwName, $result->method,
                json_encode($result->methodDetail ?? []),
                $total, json_encode($result->raw ?? []),
            ]);
            $paymentId = $pdo->lastInsertId();

            // 주문 상태 업데이트
            $now = date('Y-m-d H:i:s');
            $expiresAt = _calendarExpires(_calendarBillingStart($now), "+{$contractMonths} months");
            $pdo->prepare("UPDATE {$prefix}orders SET status='paid', payment_id=?, payment_gateway=?, started_at=?, expires_at=? WHERE id=?")
                ->execute([$paymentId, $gwName, $now, $expiresAt, $orderId]);

            // 모든 구독 레코드 생성
            _insertSubscriptions($pdo, $prefix, $orderId, $userId, $currency, $subscriptionData, 'active', $now, $customerId, $gwName);

            // 주문 로그
            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type) VALUES (?, 'paid', ?, 'system')")
                ->execute([$orderId, json_encode(['payment_id' => $paymentId, 'payment_key' => $result->paymentKey])]);

            $pdo->commit();

            // 자동 프로비저닝 (transaction 외부) — 호스팅 → 메일 순서
            _autoProvisionHosting($pdo, $prefix, $orderId, $orderNumber);
            _autoProvisionMailDomain($pdo, $prefix, $orderId, $orderNumber);

            echo json_encode([
                'success' => true,
                'order_number' => $orderNumber,
                'total' => $total,
                'currency' => $currency,
                'message' => '결제가 완료되었습니다.',
            ]);
            exit;

        } else {
            // 결제 실패
            $pdo->prepare("UPDATE {$prefix}orders SET status='failed' WHERE id=?")->execute([$orderId]);
            $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type) VALUES (?, 'failed', ?, 'system')")
                ->execute([$orderId, json_encode(['code' => $result->failureCode, 'message' => $result->failureMessage])]);

            $pdo->commit();

            echo json_encode([
                'success' => false,
                'message' => '결제에 실패했습니다: ' . ($result->failureMessage ?? '카드 정보를 확인해주세요.'),
            ]);
            exit;
        }

    } elseif ($paymentMethod === 'card') {
        // 카드 결제인데 토큰이 없거나 유효하지 않음
        $pdo->prepare("UPDATE {$prefix}orders SET status='failed' WHERE id=?")->execute([$orderId]);
        $pdo->commit();
        echo json_encode(['success' => false, 'message' => '카드 정보가 유효하지 않습니다. 카드 번호, 유효기간, CVC를 확인해주세요.']);
        exit;

    } elseif ($paymentMethod === 'bank') {
        // 계좌이체: pending 상태로 구독 생성
        $now = date('Y-m-d H:i:s');
        $expiresAt = _calendarExpires(_calendarBillingStart($now), "+{$contractMonths} months");

        // 모든 구독 레코드 생성
        _insertSubscriptions($pdo, $prefix, $orderId, $userId, $currency, $subscriptionData, 'pending', $now);

        $pdo->prepare("INSERT INTO {$prefix}order_logs (order_id, action, detail, actor_type) VALUES (?, 'bank_pending', '계좌이체 대기', 'system')")
            ->execute([$orderId]);

        $pdo->commit();

        echo json_encode([
            'success' => true,
            'order_number' => $orderNumber,
            'total' => $total,
            'currency' => $currency,
            'payment_method' => 'bank',
            'message' => '주문이 접수되었습니다. 입금 안내가 이메일로 발송됩니다.',
        ]);
        exit;
    }

    $pdo->commit();
    echo json_encode(['success' => false, 'message' => '결제 방법이 선택되지 않았습니다.']);

} catch (\Throwable $e) {
    $pdo->rollBack();
    error_log("[ServiceOrder] Error: " . $e->getMessage() . " in " . $e->getFile() . ":" . $e->getLine());
    echo json_encode(['success' => false, 'message' => '주문 처리 중 오류가 발생했습니다.']);
}
