<?php
/**
 * Developer API - 매출/정산 조회
 * GET /api/developer/earnings
 */
require_once __DIR__ . '/_init.php';

$dev = getAuthDeveloper($pdo);
if (!$dev) respond(['success' => false, 'error' => 'unauthorized'], 401);

// 요약
$summary = [
    'total_earnings' => (float) $dev['total_earnings'],
    'total_paid' => (float) $dev['total_paid'],
    'pending_balance' => (float) $dev['pending_balance'],
];

// 최근 매출 내역
$stmt = $pdo->prepare(
    "SELECT item_name, buyer_domain, gross_amount, commission, net_amount, currency, status, created_at
     FROM vcs_developer_earnings WHERE developer_id = ? ORDER BY created_at DESC LIMIT 50"
);
$stmt->execute([$dev['id']]);
$earnings = $stmt->fetchAll();

// 지급 내역
$payStmt = $pdo->prepare(
    "SELECT amount, currency, method, reference, status, period_start, period_end, items_count, processed_at
     FROM vcs_developer_payouts WHERE developer_id = ? ORDER BY created_at DESC LIMIT 20"
);
$payStmt->execute([$dev['id']]);
$payouts = $payStmt->fetchAll();

respond([
    'success' => true,
    'summary' => $summary,
    'earnings' => $earnings,
    'payouts' => $payouts,
]);
