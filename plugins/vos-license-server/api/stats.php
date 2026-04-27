<?php
/**
 * VosCMS License API - 통계
 * GET /api/license/stats
 *
 * 공개 통계: 총 설치 수, 활성 설치 수 등
 */

require_once __DIR__ . '/_init.php';

$totalInstalls = (int) $pdo->query("SELECT COUNT(*) FROM vcs_licenses")->fetchColumn();
$activeInstalls = (int) $pdo->query("SELECT COUNT(*) FROM vcs_licenses WHERE status = 'active'")->fetchColumn();
$totalPluginPurchases = (int) $pdo->query("SELECT COUNT(*) FROM vcs_license_plugins WHERE status = 'active'")->fetchColumn();

// 플랜별 분포
$plans = $pdo->query("SELECT plan, COUNT(*) as cnt FROM vcs_licenses WHERE status = 'active' GROUP BY plan")->fetchAll();
$planStats = [];
foreach ($plans as $p) {
    $planStats[$p['plan']] = (int) $p['cnt'];
}

respond([
    'total_installs' => $totalInstalls,
    'active_installs' => $activeInstalls,
    'total_plugin_purchases' => $totalPluginPurchases,
    'plans' => $planStats,
]);
