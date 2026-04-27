<?php
ob_start();
include __DIR__ . '/../_head.php';
ob_end_clean();

header('Content-Type: application/json; charset=utf-8');

$csrf = $_POST['_csrf'] ?? '';
if (!$csrf || !isset($_SESSION['_csrf']) || !hash_equals($_SESSION['_csrf'], $csrf)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'message' => 'CSRF 오류']);
    exit;
}

$action = $_POST['action'] ?? '';
$db  = mkt_pdo();
$pfx = $_mktPrefix;

if ($action === 'process_payout') {
    $partnerId = (int)($_POST['partner_id'] ?? 0);
    $method    = trim($_POST['method']     ?? '');
    $reference = trim($_POST['reference']  ?? '');

    if (!$partnerId || !$method) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'message' => 'partner_id, method 필수']);
        exit;
    }

    $stPartner = $db->prepare("SELECT id, pending_balance, currency FROM {$pfx}mkt_partners WHERE id = ? LIMIT 1");
    $stPartner->execute([$partnerId]);
    $partner = $stPartner->fetch();

    if (!$partner || (float)$partner['pending_balance'] <= 0) {
        echo json_encode(['ok' => false, 'message' => '미지급 잔액이 없습니다']);
        exit;
    }

    $amount   = (float)$partner['pending_balance'];
    $currency = $partner['currency'] ?: ($db->query("SELECT value FROM {$pfx}mkt_settings WHERE `key`='currency' LIMIT 1")->fetchColumn() ?: 'JPY');

    $db->prepare("
        INSERT INTO {$pfx}mkt_payouts
            (partner_id, amount, currency, method, reference, status, requested_at, processed_at)
        VALUES (?, ?, ?, ?, ?, 'completed', NOW(), NOW())
    ")->execute([$partnerId, $amount, $currency, $method, $reference]);

    $payoutId = (int)$db->lastInsertId();

    $db->prepare("
        UPDATE {$pfx}mkt_partners
           SET total_paid       = total_paid + ?,
               pending_balance  = 0,
               updated_at       = NOW()
         WHERE id = ?
    ")->execute([$amount, $partnerId]);

    $db->prepare("
        UPDATE {$pfx}mkt_partner_earnings
           SET status    = 'paid',
               payout_id = ?
         WHERE partner_id = ? AND status = 'confirmed'
    ")->execute([$payoutId, $partnerId]);

    echo json_encode(['ok' => true, 'payout_id' => $payoutId, 'amount' => $amount]);
    exit;
}

http_response_code(400);
echo json_encode(['ok' => false, 'message' => '알 수 없는 액션']);
