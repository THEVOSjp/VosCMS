<?php
/**
 * POST /api/market/item/install
 *
 * VosCMS 서버에서 마켓플레이스 아이템 설치 시 호출.
 * 무료: 즉시 라이선스 발급
 * 유료: 결제 완료된 주문(order_id) 확인 후 발급
 *
 * Request (JSON):
 *   {
 *     "domain"    : "customer-shop.com",      // 설치 도메인
 *     "vos_key"   : "RZX-XXXX-XXXX-XXXX",    // VosCMS 라이선스 키
 *     "item_slug" : "vos-salon",              // 아이템 slug
 *     "order_id"  : "uuid"                   // 유료 아이템만 필요
 *   }
 *
 * Response:
 *   { ok, license_key, item_slug, product_key, type, is_new }
 */
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'POST required']);
    exit;
}

// ── 입력 파싱 ──────────────────────────────────────────────────────
$body     = json_decode(file_get_contents('php://input'), true) ?: [];
$rawDomain = trim($body['domain']    ?? '');
$vosKey    = trim($body['vos_key']   ?? '');
$itemSlug  = trim($body['item_slug'] ?? '');
$orderId   = trim($body['order_id']  ?? '');

if (!$rawDomain || !$vosKey || !$itemSlug) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'domain, vos_key, item_slug 필수']);
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

    if (!$vosLic) {
        http_response_code(403);
        echo json_encode(['ok' => false, 'message' => '유효하지 않은 VosCMS 라이선스 키']);
        exit;
    }
    if ($vosLic['status'] !== 'active') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'message' => 'VosCMS 라이선스가 활성 상태가 아닙니다', 'status' => $vosLic['status']]);
        exit;
    }

    // ── 2. 아이템 조회 ─────────────────────────────────────────────
    $stItem = $pdo->prepare("SELECT id, product_key, slug, name, price, currency, status FROM {$pfx}mkt_items WHERE slug = ? LIMIT 1");
    $stItem->execute([$itemSlug]);
    $item = $stItem->fetch();

    if (!$item) {
        http_response_code(404);
        echo json_encode(['ok' => false, 'message' => '존재하지 않는 아이템']);
        exit;
    }
    if ($item['status'] !== 'active') {
        http_response_code(403);
        echo json_encode(['ok' => false, 'message' => '설치할 수 없는 아이템 상태', 'status' => $item['status']]);
        exit;
    }

    $isFree = ((float)$item['price'] === 0.0);

    // ── 3. 기존 라이선스 확인 (domain + vos_key + item 세 조건 일치 → 기존 키 반환) ──
    $stExist = $pdo->prepare("
        SELECT l.license_key
          FROM {$pfx}mkt_licenses l
         WHERE l.vos_license_key = ? AND l.item_id = ? AND l.domain = ? AND l.status = 'active'
         LIMIT 1
    ");
    $stExist->execute([$vosKey, $item['id'], $domain]);
    $existing = $stExist->fetchColumn();

    if ($existing) {
        // 활성화 레코드 last_check_at 갱신
        $pdo->prepare("
            UPDATE {$pfx}mkt_license_activations
               SET last_check_at = NOW()
             WHERE license_id = (SELECT id FROM {$pfx}mkt_licenses WHERE license_key = ? LIMIT 1)
               AND domain = ?
        ")->execute([$existing, $domain]);

        echo json_encode([
            'ok'          => true,
            'license_key' => $existing,
            'item_slug'   => $item['slug'],
            'product_key' => $item['product_key'],
            'type'        => $isFree ? 'free' : 'paid',
            'is_new'      => false,
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }

    // ── 4. 유료 아이템: 결제 확인 ─────────────────────────────────
    if (!$isFree) {
        if (!$orderId) {
            http_response_code(402);
            echo json_encode(['ok' => false, 'message' => '유료 아이템은 order_id 필수']);
            exit;
        }

        $stOrder = $pdo->prepare("
            SELECT o.id
              FROM {$pfx}mkt_orders o
              JOIN {$pfx}mkt_order_items oi ON oi.order_id = o.id
             WHERE o.uuid = ?
               AND oi.item_id = ?
               AND o.status = 'paid'
             LIMIT 1
        ");
        $stOrder->execute([$orderId, $item['id']]);
        $orderDbId = $stOrder->fetchColumn();

        if (!$orderDbId) {
            http_response_code(402);
            echo json_encode(['ok' => false, 'message' => '결제가 완료된 주문을 찾을 수 없습니다']);
            exit;
        }
    }

    // ── 5. 라이선스 발급 (UUID v4) ────────────────────────────────
    $licenseKey = sprintf('%08x-%04x-4%03x-%04x-%012x',
        random_int(0, 0xffffffff),
        random_int(0, 0xffff),
        random_int(0, 0xfff),
        random_int(0x8000, 0xbfff),
        random_int(0, 0xffffffffffff)
    );

    $pdo->prepare("
        INSERT INTO {$pfx}mkt_licenses
            (license_key, item_id, vos_license_key, domain, type, max_activations, status)
        VALUES (?, ?, ?, ?, ?, ?, 'active')
    ")->execute([
        $licenseKey,
        $item['id'],
        $vosKey,
        $domain,
        $isFree ? 'unlimited' : 'single',
        $isFree ? null : 1,
    ]);

    $licenseId = (int)$pdo->lastInsertId();

    // ── 6. 활성화 레코드 등록 ──────────────────────────────────────
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

    // ── 7. 다운로드 카운트 증가 ────────────────────────────────────
    $pdo->prepare("UPDATE {$pfx}mkt_items SET download_count = download_count + 1 WHERE id = ?")
        ->execute([$item['id']]);

    echo json_encode([
        'ok'          => true,
        'license_key' => $licenseKey,
        'item_slug'   => $item['slug'],
        'product_key' => $item['product_key'],
        'type'        => $isFree ? 'free' : 'paid',
        'is_new'      => true,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Server error']);
}
