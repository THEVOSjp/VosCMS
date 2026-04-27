<?php
/**
 * POST /api/market/item/purchase
 *
 * VosCMS 관리자 > 쉬운 설치에서 유료 아이템 구매 시 호출.
 * 무료 아이템은 install.php 사용. 이 엔드포인트는 유료/무료 모두 처리하되
 * 유료는 PAY.JP 결제 후 라이선스 발급, 무료는 즉시 발급.
 *
 * Request (JSON):
 *   {
 *     "vos_key"     : "RZX-XXXX-XXXX-XXXX",  // VosCMS 라이선스 키
 *     "domain"      : "customer-shop.com",     // 설치 도메인
 *     "item_slug"   : "vos-salon",             // 아이템 slug
 *     "payjp_token" : "tok_xxx",               // 유료 아이템만 필요
 *     "buyer_email" : "user@example.com"       // 영수증/시리얼 발송용
 *   }
 *
 * Response:
 *   { ok, license_key, serial_key, order_number, product_key, is_new }
 */
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'POST required']);
    exit;
}

// ── 부트스트랩 ──────────────────────────────────────────────────────
if (!defined('BASE_PATH')) define('BASE_PATH', dirname(__DIR__, 5));

$envFile = BASE_PATH . '/.env';
if (file_exists($envFile)) {
    foreach (file($envFile, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = trim($line);
        if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) continue;
        [$k, $v] = explode('=', $line, 2);
        $_ENV[trim($k)] = trim($v, " \t\n\r\0\x0B\"'");
    }
}

if (file_exists(BASE_PATH . '/vendor/autoload.php')) {
    require_once BASE_PATH . '/vendor/autoload.php';
}

// ── 입력 파싱 ──────────────────────────────────────────────────────
$body       = json_decode(file_get_contents('php://input'), true) ?: [];
$vosKey     = trim($body['vos_key']     ?? '');
$rawDomain  = trim($body['domain']      ?? '');
$itemSlug   = trim($body['item_slug']   ?? '');
$payjpToken = trim($body['payjp_token'] ?? '');
$buyerEmail = trim($body['buyer_email'] ?? '');
$installment = (int)($body['installment'] ?? 0);  // 0=일시불, 3/5/6/10/12=할부 회수

if (!$vosKey || !$rawDomain || !$itemSlug) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'vos_key, domain, item_slug 필수']);
    exit;
}

// ── 도메인 정규화 ──────────────────────────────────────────────────
$domain = strtolower(preg_replace('#^https?://#', '', rtrim($rawDomain, '/')));
$domain = preg_replace('#^www\.#', '', $domain);
$domain = explode('/', $domain)[0];
$domain = explode('?', $domain)[0];

if (!$domain) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => '유효하지 않은 도메인']);
    exit;
}

// ── DB 연결 ────────────────────────────────────────────────────────
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    $pfx = $_ENV['DB_PREFIX'] ?? 'rzx_';

    // ── 1. VosCMS 라이선스 키 검증 ─────────────────────────────────
    $stVos = $pdo->prepare("SELECT id, status FROM {$pfx}vos_licenses WHERE license_key = ? LIMIT 1");
    $stVos->execute([$vosKey]);
    $vosLic = $stVos->fetch();

    if (!$vosLic || $vosLic['status'] !== 'active') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'message' => '유효하지 않은 VosCMS 라이선스 키']);
        exit;
    }

    // ── 2. 아이템 조회 ─────────────────────────────────────────────
    $stItem = $pdo->prepare("SELECT id, product_key, slug, name, type, price, currency, status FROM {$pfx}mkt_items WHERE slug = ? LIMIT 1");
    $stItem->execute([$itemSlug]);
    $item = $stItem->fetch();

    if (!$item || $item['status'] !== 'active') {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => '존재하지 않거나 구매할 수 없는 아이템']);
        exit;
    }

    $isFree = ((float)$item['price'] === 0.0);

    // ── 3. 기존 활성 라이선스 확인 ────────────────────────────────
    $stExist = $pdo->prepare("
        SELECT l.license_key, o.serial_key
          FROM {$pfx}mkt_licenses l
          LEFT JOIN {$pfx}mkt_order_items oi ON oi.id = l.order_item_id
          LEFT JOIN {$pfx}mkt_orders o ON o.id = oi.order_id
         WHERE l.vos_license_key = ? AND l.item_id = ? AND l.domain = ? AND l.status = 'active'
         LIMIT 1
    ");
    $stExist->execute([$vosKey, $item['id'], $domain]);
    $existing = $stExist->fetch();

    if ($existing) {
        $pdo->prepare("
            UPDATE {$pfx}mkt_license_activations
               SET last_check_at = NOW()
             WHERE license_id = (SELECT id FROM {$pfx}mkt_licenses WHERE license_key = ? LIMIT 1)
               AND domain = ?
        ")->execute([$existing['license_key'], $domain]);

        // 주문번호 (혹시 연결된 주문이 있으면 가져옴)
        $stOrd = $pdo->prepare(
            "SELECT o.order_number FROM {$pfx}mkt_licenses l
              LEFT JOIN {$pfx}mkt_order_items oi ON oi.id = l.order_item_id
              LEFT JOIN {$pfx}mkt_orders o ON o.id = oi.order_id
              WHERE l.license_key = ? LIMIT 1"
        );
        $stOrd->execute([$existing['license_key']]);
        $existingOrder = $stOrd->fetchColumn();

        $nameJson = json_decode($item['name'] ?? '{}', true) ?: [];
        $resolvedName = $nameJson['ko'] ?? $nameJson['en'] ?? (reset($nameJson) ?: $item['slug']);

        echo json_encode([
            'ok'           => true,
            'license_key'  => $existing['license_key'],
            'serial_key'   => $existing['serial_key'],
            'order_number' => $existingOrder ?: null,
            'product_key'  => $item['product_key'],
            'item_slug'    => $item['slug'],
            'item_name'    => $resolvedName,
            'item_type'    => $item['type'] ?? null,
            'amount'       => (float)$item['price'],
            'currency'     => $item['currency'] ?? 'JPY',
            'type'         => $isFree ? 'free' : 'paid',
            'is_new'       => false,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── 4. 유료 아이템: PAY.JP 결제 ───────────────────────────────
    $chargeId = null;
    if (!$isFree) {
        if (!$payjpToken) {
            http_response_code(402);
            echo json_encode(['ok' => false, 'message' => '유료 아이템은 payjp_token 필수']);
            exit;
        }

        // 결제 금액 (JPY는 정수, 그 외 100배)
        $currency = strtoupper($item['currency'] ?? 'JPY');
        $amount   = in_array($currency, ['JPY', 'KRW'])
            ? (int)$item['price']
            : (int)round($item['price'] * 100);

        // PaymentManager로 PAY.JP 결제
        $pm      = new \RzxLib\Modules\Payment\PaymentManager($pdo, $pfx);
        $gateway = $pm->gateway('payjp');

        $orderUuid   = sprintf('%08x-%04x-4%03x-%04x-%012x',
            random_int(0, 0xffffffff), random_int(0, 0xffff),
            random_int(0, 0xfff), random_int(0x8000, 0xbfff),
            random_int(0, 0xffffffffffff));
        $itemName    = json_decode($item['name'] ?? '{}', true)['ko'] ?? $item['slug'];
        // 토큰 piped 문자열: token|amount|currency|orderId|description|installment
        $tokenPiped  = "{$payjpToken}|{$amount}|{$currency}|{$orderUuid}|{$itemName}|{$installment}";

        $result = $gateway->confirm($tokenPiped);
        if (!$result->success) {
            http_response_code(402);
            echo json_encode([
                'ok'      => false,
                'message' => '결제 실패: ' . ($result->failureMessage ?? '알 수 없는 오류'),
                'code'    => $result->failureCode ?? null,
            ], JSON_UNESCAPED_UNICODE);
            exit;
        }
        $chargeId = $result->paymentKey;
    }

    // ── 5. 주문 생성 ───────────────────────────────────────────────
    $serialKey   = generateSerialKey();
    $orderNumber = 'MKT-' . date('Ymd') . '-' . strtoupper(substr(md5(uniqid('', true)), 0, 6));
    $orderUuid   = $orderUuid ?? sprintf('%08x-%04x-4%03x-%04x-%012x',
        random_int(0, 0xffffffff), random_int(0, 0xffff),
        random_int(0, 0xfff), random_int(0x8000, 0xbfff),
        random_int(0, 0xffffffffffff));

    $pdo->prepare("
        INSERT INTO {$pfx}mkt_orders
            (uuid, order_number, buyer_site_url, buyer_email, payment_ref, serial_key, vos_license_key,
             subtotal, discount, total, currency, status, paid_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, 0, ?, ?, ?, NOW())
    ")->execute([
        $orderUuid,
        $orderNumber,
        $domain,
        $buyerEmail ?: null,
        $chargeId,
        $serialKey,
        $vosKey,
        $item['price'],
        $item['price'],
        $item['currency'] ?? 'JPY',
        $isFree ? 'paid' : 'paid',
    ]);
    $orderId = (int)$pdo->lastInsertId();

    // ── 6. 주문 아이템 생성 ────────────────────────────────────────
    $itemNameStr = json_decode($item['name'] ?? '{}', true)['ko'] ?? $item['slug'];
    $pdo->prepare("
        INSERT INTO {$pfx}mkt_order_items
            (order_id, item_id, item_name, item_type, item_slug, price)
        VALUES (?, ?, ?, ?, ?, ?)
    ")->execute([$orderId, $item['id'], $itemNameStr, $item['type'] ?? 'plugin', $item['slug'], $item['price']]);
    $orderItemId = (int)$pdo->lastInsertId();

    // ── 7. 라이선스 발급 ───────────────────────────────────────────
    $licenseKey = sprintf('%08x-%04x-4%03x-%04x-%012x',
        random_int(0, 0xffffffff), random_int(0, 0xffff),
        random_int(0, 0xfff), random_int(0x8000, 0xbfff),
        random_int(0, 0xffffffffffff));

    $pdo->prepare("
        INSERT INTO {$pfx}mkt_licenses
            (license_key, order_item_id, vos_license_key, item_id, domain,
             buyer_email, buyer_site_url, type, max_activations, status)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'active')
    ")->execute([
        $licenseKey,
        $orderItemId,
        $vosKey,
        $item['id'],
        $domain,
        $buyerEmail ?: null,
        $domain,
        $isFree ? 'unlimited' : 'single',
        $isFree ? null : 1,
    ]);
    $licenseId = (int)$pdo->lastInsertId();

    // ── 8. 활성화 레코드 등록 ──────────────────────────────────────
    $pdo->prepare("
        INSERT INTO {$pfx}mkt_license_activations
            (license_id, domain, ip_address, voscms_version, activated_at, last_check_at)
        VALUES (?, ?, ?, ?, NOW(), NOW())
    ")->execute([
        $licenseId,
        $domain,
        $_SERVER['REMOTE_ADDR'] ?? null,
        $body['cms_version'] ?? null,
    ]);

    // ── 9. 다운로드 카운트 증가 ────────────────────────────────────
    $pdo->prepare("UPDATE {$pfx}mkt_items SET download_count = download_count + 1 WHERE id = ?")
        ->execute([$item['id']]);

    // 아이템 이름 (구매 내역 표시용 — 한국어 우선, 폴백 en/슬러그)
    $nameJson  = json_decode($item['name'] ?? '{}', true) ?: [];
    $resolvedName = $nameJson['ko'] ?? $nameJson['en'] ?? (reset($nameJson) ?: $item['slug']);

    echo json_encode([
        'ok'           => true,
        'license_key'  => $licenseKey,
        'serial_key'   => $serialKey,
        'order_number' => $orderNumber,
        'product_key'  => $item['product_key'],
        'item_slug'    => $item['slug'],
        'item_name'    => $resolvedName,
        'item_type'    => $item['type'] ?? null,
        'amount'       => (float)$item['price'],
        'currency'     => $item['currency'] ?? 'JPY',
        'type'         => $isFree ? 'free' : 'paid',
        'is_new'       => true,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    error_log('[purchase] ' . $e->getMessage());
    echo json_encode(['ok' => false, 'message' => 'Server error']);
}

// ── 시리얼 키 생성 ─────────────────────────────────────────────────
function generateSerialKey(): string {
    $chars = 'ABCDEFGHJKLMNPQRSTUVWXYZ23456789';
    $segments = [];
    for ($i = 0; $i < 3; $i++) {
        $seg = '';
        for ($j = 0; $j < 4; $j++) {
            $seg .= $chars[random_int(0, strlen($chars) - 1)];
        }
        $segments[] = $seg;
    }
    return 'MKT-' . implode('-', $segments);
}
