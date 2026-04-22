<?php
/**
 * VosCMS Auto Install - 설치 핸들러
 *
 * POST /admin/autoinstall/install
 * Body: item_slug (아이템 slug), order_id (유료 아이템 선택)
 *
 * 흐름:
 *   1. vos_key + domain 로드
 *   2. InstallerService::install() 호출
 *      → market /item/install → license_key 획득
 *      → market /download     → ZIP 다운로드
 *      → 로컬 설치
 *   3. 결과 반환
 */
header('Content-Type: application/json; charset=utf-8');

$adminId = $_SESSION['admin_id'] ?? '';
if (!$adminId) {
    echo json_encode(['success' => false, 'message' => 'Unauthorized']);
    return;
}

$prefix   = $_ENV['DB_PREFIX'] ?? 'rzx_';
$itemSlug = trim($_POST['item_slug'] ?? '');
$orderId  = trim($_POST['order_id']  ?? '');

if (!$itemSlug) {
    echo json_encode(['success' => false, 'message' => 'item_slug 필수']);
    return;
}

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
    );
} catch (PDOException $e) {
    echo json_encode(['success' => false, 'message' => 'DB 연결 실패']);
    return;
}

// VosCMS 라이선스 키
$cacheFile = (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/storage/.license_cache';
$vosKey    = '';
if (file_exists($cacheFile)) {
    $cacheData = json_decode(file_get_contents($cacheFile), true);
    $vosKey    = $cacheData['license_key'] ?? '';
}
if (!$vosKey) {
    echo json_encode(['success' => false, 'message' => 'VosCMS 라이선스 키 없음. 라이선스 등록 후 다시 시도하세요.']);
    return;
}

// 사이트 도메인
$domain = '';
try {
    $st = $pdo->prepare("SELECT value FROM {$prefix}settings WHERE `key` = 'site_url' LIMIT 1");
    $st->execute();
    $siteUrl = $st->fetchColumn() ?: '';
    $domain  = strtolower(preg_replace('#^https?://#', '', rtrim($siteUrl, '/')));
    $domain  = preg_replace('#^www\.#', '', $domain);
    $domain  = explode('/', $domain)[0];
    $domain  = explode('?', $domain)[0];
} catch (Throwable $e) {}

if (!$domain) {
    echo json_encode(['success' => false, 'message' => '사이트 URL을 확인할 수 없습니다']);
    return;
}

// InstallerService 실행
require_once __DIR__ . '/../../src/InstallerService.php';

$installer = new \VosAutoinstall\InstallerService(defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4));
$result    = $installer->install($itemSlug, $vosKey, $domain, $pdo, $prefix, $orderId);

echo json_encode($result, JSON_UNESCAPED_UNICODE);
