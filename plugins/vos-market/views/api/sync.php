<?php
/**
 * POST /api/market/sync
 *
 * VosCMS 사이트가 현재 설치된 아이템 목록을 마켓에 보고.
 * - product_key 있음: 직접 조회
 * - product_key 없고 slug만 있음: slug로 폴백 조회 후 product_key 반환
 *
 * Request (JSON):
 *   {
 *     "vos_key" : "RZX-XXXX-XXXX-XXXX",
 *     "domain"  : "customer.com",
 *     "items"   : [
 *       { "product_key": "uuid", "slug": "vos-shop",  "version": "2.0.0" },
 *       { "slug": "vos-salon",                        "version": "1.0.0" }
 *     ]
 *   }
 *
 * Response:
 *   {
 *     "ok": true,
 *     "results": [
 *       { "slug": "vos-shop",  "product_key": "uuid",    "status": "licensed"        },
 *       { "slug": "vos-salon", "product_key": "abc-123", "status": "unlicensed"      },
 *       { "slug": "unknown",   "product_key": null,      "status": "unknown_product" }
 *     ]
 *   }
 */
header('Content-Type: application/json; charset=utf-8');

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'message' => 'POST required']);
    exit;
}

// ── 입력 파싱 ──────────────────────────────────────────────────────
$body      = json_decode(file_get_contents('php://input'), true) ?: [];
$rawDomain = trim($body['domain']  ?? '');
$vosKey    = trim($body['vos_key'] ?? '');
$items     = $body['items'] ?? [];

if (!$rawDomain || !$vosKey || !is_array($items)) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => 'domain, vos_key, items 필수']);
    exit;
}

if (empty($items)) {
    echo json_encode(['ok' => true, 'results' => []]);
    exit;
}

if (count($items) > 200) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'message' => '한 번에 최대 200개까지 보고 가능']);
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

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
    $pfx = $_ENV['DB_PREFIX'] ?? 'rzx_';

    // ── 1. VosCMS 라이선스 키 UPSERT (무조건 기록) ─────────────────
    // 설치 추적은 수집이 목적이므로 vos_key 유효성은 검증하지 않음.
    // 마켓이 처음 보는 키면 로컬에 새 레코드로 기록하고 last_seen 갱신.
    $pdo->prepare(
        "INSERT INTO {$pfx}vos_licenses (license_key, domain, plan, status, last_seen_at)
         VALUES (?, ?, 'free', 'active', NOW())
         ON DUPLICATE KEY UPDATE last_seen_at=NOW()"
    )->execute([$vosKey, $domain]);

    // ── 2. 아이템 조회: product_key 목록 + slug 목록 분리 ──────────
    $byProductKey = []; // product_key => item row
    $bySlug       = []; // slug => item row

    $productKeys = [];
    $slugs       = [];

    foreach ($items as $it) {
        $pk   = trim($it['product_key'] ?? '');
        $slug = trim($it['slug']        ?? '');
        if ($pk)   $productKeys[] = $pk;
        if ($slug) $slugs[]       = $slug;
    }
    $productKeys = array_values(array_unique($productKeys));
    $slugs       = array_values(array_unique($slugs));

    // product_key로 일괄 조회
    if ($productKeys) {
        $phs = implode(',', array_fill(0, count($productKeys), '?'));
        $st  = $pdo->prepare("SELECT id, product_key, slug, status FROM {$pfx}mkt_items WHERE product_key IN ($phs)");
        $st->execute($productKeys);
        foreach ($st->fetchAll() as $row) {
            $byProductKey[$row['product_key']] = $row;
        }
    }

    // slug로 일괄 조회 (product_key 없는 항목 폴백)
    if ($slugs) {
        $phs = implode(',', array_fill(0, count($slugs), '?'));
        $st  = $pdo->prepare("SELECT id, product_key, slug, status FROM {$pfx}mkt_items WHERE slug IN ($phs)");
        $st->execute($slugs);
        foreach ($st->fetchAll() as $row) {
            $bySlug[$row['slug']] = $row;
        }
    }

    // ── 3. 라이선스 일괄 조회 ──────────────────────────────────────
    $allItemIds = array_unique(array_merge(
        array_column(array_values($byProductKey), 'id'),
        array_column(array_values($bySlug),       'id')
    ));

    $licensedItemIds = [];
    if ($allItemIds) {
        $phs = implode(',', array_fill(0, count($allItemIds), '?'));
        $st  = $pdo->prepare(
            "SELECT item_id FROM {$pfx}mkt_licenses
              WHERE vos_license_key = ? AND item_id IN ($phs) AND status = 'active'"
        );
        $st->execute(array_merge([$vosKey], $allItemIds));
        foreach ($st->fetchAll() as $row) {
            $licensedItemIds[$row['item_id']] = true;
        }
    }

    // ── 4. 각 아이템 처리 및 sync_reports UPSERT ──────────────────
    $stUpsert = $pdo->prepare("
        INSERT INTO {$pfx}mkt_sync_reports
            (vos_key, domain, item_id, product_key, slug, version, status, first_seen_at, last_seen_at)
        VALUES (?, ?, ?, ?, ?, ?, ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE
            domain       = VALUES(domain),
            item_id      = VALUES(item_id),
            slug         = VALUES(slug),
            version      = VALUES(version),
            status       = VALUES(status),
            last_seen_at = NOW()
    ");

    $results = [];

    foreach ($items as $it) {
        $pk      = trim($it['product_key'] ?? '');
        $slug    = trim($it['slug']        ?? '');
        $version = trim($it['version']     ?? '') ?: null;

        if (!$pk && !$slug) continue;

        // 아이템 행 결정: product_key 우선, 없으면 slug 폴백
        $itemRow = null;
        if ($pk && isset($byProductKey[$pk])) {
            $itemRow = $byProductKey[$pk];
        } elseif ($slug && isset($bySlug[$slug])) {
            $itemRow = $bySlug[$slug];
        }

        if (!$itemRow) {
            // 마켓에 없는 아이템 — product_key가 없으면 slug 기반 합성 키 사용
            $reportKey = $pk ?: ('unk-' . substr(sha1($slug), 0, 32));
            $results[] = [
                'slug'        => $slug ?: null,
                'product_key' => $pk   ?: null,
                'status'      => 'unknown_product',
            ];
            $stUpsert->execute([$vosKey, $domain, null, $reportKey, $slug ?: null, $version, 'unknown_product']);
            continue;
        }

        $resolvedProductKey = $itemRow['product_key'];
        $resolvedSlug       = $itemRow['slug'];
        $status = isset($licensedItemIds[$itemRow['id']]) ? 'licensed' : 'unlicensed';

        $stUpsert->execute([$vosKey, $domain, $itemRow['id'], $resolvedProductKey, $resolvedSlug, $version, $status]);

        $results[] = [
            'slug'        => $resolvedSlug,
            'product_key' => $resolvedProductKey,
            'status'      => $status,
        ];
    }

    echo json_encode(['ok' => true, 'results' => $results], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'message' => 'Server error']);
}
