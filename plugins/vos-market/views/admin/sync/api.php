<?php
/**
 * POST /admin/market/sync/api
 * 설치 추적 관리자 액션 (라이선스 수동 발급 등)
 */
ob_start();
include __DIR__ . '/../_head.php';
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');

// CSRF 검증
$csrf = $_POST['_csrf'] ?? '';
if (!$csrf || !isset($_SESSION['_csrf']) || !hash_equals($_SESSION['_csrf'], $csrf)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'CSRF 오류']);
    exit;
}

$action = $_POST['action'] ?? '';
$db  = mkt_pdo();
$pfx = $_mktPrefix;

// ── 미추적 설치에 라이선스 수동 발급 ──────────────────────────────
if ($action === 'issue_from_sync') {
    $reportId = (int)($_POST['report_id'] ?? 0);
    $domain   = trim($_POST['domain']   ?? '');
    $vosKey   = trim($_POST['vos_key']  ?? '');
    $itemId   = (int)($_POST['item_id'] ?? 0);

    if (!$reportId || !$domain || !$vosKey || !$itemId) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => '필수 파라미터 누락']);
        exit;
    }

    // sync_reports 레코드 확인
    $stReport = $db->prepare("SELECT id, status FROM {$pfx}mkt_sync_reports WHERE id = ? LIMIT 1");
    $stReport->execute([$reportId]);
    $report = $stReport->fetch();
    if (!$report) {
        echo json_encode(['ok' => false, 'message' => '레코드를 찾을 수 없습니다']);
        exit;
    }

    // 이미 라이선스 있는지 확인
    $stExist = $db->prepare(
        "SELECT license_key FROM {$pfx}mkt_licenses WHERE vos_license_key = ? AND item_id = ? AND status = 'active' LIMIT 1"
    );
    $stExist->execute([$vosKey, $itemId]);
    $existing = $stExist->fetchColumn();
    if ($existing) {
        // 기존 라이선스를 sync_report에 반영
        $db->prepare("UPDATE {$pfx}mkt_sync_reports SET status = 'licensed' WHERE id = ?")
           ->execute([$reportId]);
        echo json_encode(['ok' => true, 'license_key' => $existing, 'is_new' => false]);
        exit;
    }

    // UUID v4 라이선스 키 생성
    $licenseKey = sprintf('%08x-%04x-4%03x-%04x-%012x',
        random_int(0, 0xffffffff), random_int(0, 0xffff),
        random_int(0, 0xfff), random_int(0x8000, 0xbfff),
        random_int(0, 0xffffffffffff)
    );

    $db->prepare("
        INSERT INTO {$pfx}mkt_licenses
            (license_key, item_id, vos_license_key, domain, type, max_activations, status)
        VALUES (?, ?, ?, ?, 'single', 1, 'active')
    ")->execute([$licenseKey, $itemId, $vosKey, $domain]);

    $licenseId = (int)$db->lastInsertId();

    $db->prepare("
        INSERT INTO {$pfx}mkt_license_activations
            (license_id, domain, ip_address, activated_at, last_check_at)
        VALUES (?, ?, ?, NOW(), NOW())
    ")->execute([$licenseId, $domain, $_SERVER['REMOTE_ADDR'] ?? null]);

    // sync_report 상태 갱신
    $db->prepare("UPDATE {$pfx}mkt_sync_reports SET status = 'licensed' WHERE id = ?")
       ->execute([$reportId]);

    echo json_encode(['ok' => true, 'license_key' => $licenseKey, 'is_new' => true]);
    exit;
}

// ── 알 수 없는 제품을 마켓 카탈로그에 등록 (선택: 라이선스도 자동 발급) ──
if ($action === 'register_from_sync') {
    $reportId     = (int)($_POST['report_id'] ?? 0);
    $domain       = trim($_POST['domain']   ?? '');
    $vosKey       = trim($_POST['vos_key']  ?? '');
    $slug         = trim($_POST['slug']     ?? '');
    $type         = trim($_POST['type']     ?? 'plugin');
    $name         = trim($_POST['name']     ?? '');
    $issueLicense = ($_POST['issue_license'] ?? '0') === '1';

    if (!$reportId || !$domain || !$vosKey || !$slug) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => '필수 파라미터 누락']);
        exit;
    }

    // type 정규화: enum은 plugin/theme/widget/skin — layout은 skin으로 저장
    $typeMap = ['plugin' => 'plugin', 'widget' => 'widget', 'theme' => 'theme', 'skin' => 'skin', 'layout' => 'skin'];
    $dbType  = $typeMap[$type] ?? 'plugin';

    // 이미 카탈로그에 있는지 확인 (slug 기준)
    $stExist = $db->prepare("SELECT id, product_key FROM {$pfx}mkt_items WHERE slug = ? LIMIT 1");
    $stExist->execute([$slug]);
    $existing = $stExist->fetch();

    if ($existing) {
        $itemId     = (int)$existing['id'];
        $productKey = $existing['product_key'];
    } else {
        // 새 아이템 생성
        $productKey = sprintf('%08x-%04x-4%03x-%04x-%012x',
            random_int(0, 0xffffffff), random_int(0, 0xffff),
            random_int(0, 0xfff), random_int(0x8000, 0xbfff),
            random_int(0, 0xffffffffffff)
        );
        $nameJson = json_encode(['ko' => $name, 'en' => $name], JSON_UNESCAPED_UNICODE);
        // sync에서 등록하는 아이템은 ZIP 없으므로 'pending' 상태로 → 관리자가 ZIP 업로드 후 활성화
        $db->prepare("
            INSERT INTO {$pfx}mkt_items
                (product_key, slug, type, name, price, currency, status, created_at, updated_at)
            VALUES (?, ?, ?, ?, 0, 'JPY', 'pending', NOW(), NOW())
        ")->execute([$productKey, $slug, $dbType, $nameJson]);
        $itemId = (int)$db->lastInsertId();
    }

    $licenseKey = null;
    if ($issueLicense) {
        // 이미 발급된 라이선스 확인
        $stLic = $db->prepare("SELECT license_key FROM {$pfx}mkt_licenses WHERE vos_license_key = ? AND item_id = ? AND status = 'active' LIMIT 1");
        $stLic->execute([$vosKey, $itemId]);
        $licenseKey = $stLic->fetchColumn();

        if (!$licenseKey) {
            $licenseKey = sprintf('%08x-%04x-4%03x-%04x-%012x',
                random_int(0, 0xffffffff), random_int(0, 0xffff),
                random_int(0, 0xfff), random_int(0x8000, 0xbfff),
                random_int(0, 0xffffffffffff)
            );
            $db->prepare("
                INSERT INTO {$pfx}mkt_licenses
                    (license_key, item_id, vos_license_key, domain, type, max_activations, status)
                VALUES (?, ?, ?, ?, 'single', 1, 'active')
            ")->execute([$licenseKey, $itemId, $vosKey, $domain]);
            $licenseId = (int)$db->lastInsertId();
            $db->prepare("
                INSERT INTO {$pfx}mkt_license_activations
                    (license_id, domain, ip_address, activated_at, last_check_at)
                VALUES (?, ?, ?, NOW(), NOW())
            ")->execute([$licenseId, $domain, $_SERVER['REMOTE_ADDR'] ?? null]);
        }
    }

    // sync_report 업데이트: item_id 연결, 상태 갱신
    $newStatus = $licenseKey ? 'licensed' : 'unlicensed';
    $db->prepare("UPDATE {$pfx}mkt_sync_reports SET item_id = ?, product_key = ?, status = ? WHERE id = ?")
       ->execute([$itemId, $productKey, $newStatus, $reportId]);

    echo json_encode([
        'ok'          => true,
        'item_id'     => $itemId,
        'product_key' => $productKey,
        'license_key' => $licenseKey,
    ]);
    exit;
}

// ── 보고서 무시(삭제) ────────────────────────────────────────
if ($action === 'dismiss_report') {
    $reportId = (int)($_POST['report_id'] ?? 0);
    if (!$reportId) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'report_id 필수']);
        exit;
    }
    $db->prepare("DELETE FROM {$pfx}mkt_sync_reports WHERE id = ?")->execute([$reportId]);
    echo json_encode(['ok' => true]);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'message' => '알 수 없는 액션']);
