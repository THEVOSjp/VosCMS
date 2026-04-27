<?php
/**
 * Developer API - 내 아이템 목록
 * GET /api/developer/my-items
 */
require_once __DIR__ . '/_init.php';

$dev = getAuthDeveloper($pdo);
if (!$dev) respond(['success' => false, 'error' => 'unauthorized'], 401);

// 심사 큐 항목
$stmt = $pdo->prepare(
    "SELECT id, item_type, name, version, price, currency, status, rejection_reason,
            submitted_at, reviewed_at
     FROM vcs_review_queue WHERE developer_id = ? ORDER BY submitted_at DESC"
);
$stmt->execute([$dev['id']]);
$queueItems = $stmt->fetchAll();

foreach ($queueItems as &$qi) {
    $qi['name'] = json_decode($qi['name'], true);
}

// 공개된 아이템 (승인 후)
$prefix = $_ENV['DB_PREFIX'] ?? 'rzx_';
$pubStmt = $pdo->prepare(
    "SELECT id, slug, type, name, price, currency, latest_version, download_count, rating_avg, status
     FROM {$prefix}mp_items WHERE author_name = ? ORDER BY created_at DESC"
);
$pubStmt->execute([$dev['name']]);
$publishedItems = $pubStmt->fetchAll();

foreach ($publishedItems as &$pi) {
    $pi['name'] = json_decode($pi['name'], true);
}

respond([
    'success' => true,
    'queue' => $queueItems,
    'published' => $publishedItems,
]);
