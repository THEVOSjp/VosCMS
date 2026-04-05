<?php
/**
 * RezlyX Admin - 서비스 관리 데이터 로드
 * services.php에서 include
 *
 * 필요 변수: $pdo, $prefix, $config
 * 제공 변수: $categories, $services, $svcTranslatedMap, $totalServices, $activeServices, $totalCategories
 */

// 카테고리 목록
$categories = $pdo->query("SELECT * FROM {$prefix}service_categories ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);

// 다국어 표시 룰: 선택언어 → 영어 → 기본언어 → DB 원본
$currentLocale = $config['locale'] ?? 'ko';
$defaultLocale = $config['default_language'] ?? 'ko';
$catLocaleChain = array_unique(array_filter([$currentLocale, 'en', $defaultLocale]));

$catPlaceholders = implode(',', array_fill(0, count($catLocaleChain), '?'));
// 카테고리 + 서비스 번역 로드
$trStmt = $pdo->prepare("SELECT lang_key, locale, content FROM {$prefix}translations WHERE locale IN ({$catPlaceholders}) AND (lang_key LIKE 'category.%' OR lang_key LIKE 'service.%')");
$trStmt->execute(array_values($catLocaleChain));

$catAllTranslations = [];
$svcAllTranslations = [];
while ($tr = $trStmt->fetch(PDO::FETCH_ASSOC)) {
    if (str_starts_with($tr['lang_key'], 'category.')) {
        $catAllTranslations[$tr['lang_key']][$tr['locale']] = $tr['content'];
    } else {
        $svcAllTranslations[$tr['lang_key']][$tr['locale']] = $tr['content'];
    }
}

/**
 * 카테고리 항목의 번역된 텍스트 가져오기
 */
function getCategoryTranslated($catId, $field, $default) {
    global $catAllTranslations, $catLocaleChain;
    $key = "category.{$catId}.{$field}";
    if (isset($catAllTranslations[$key])) {
        foreach ($catLocaleChain as $loc) {
            if (!empty($catAllTranslations[$key][$loc])) {
                return $catAllTranslations[$key][$loc];
            }
        }
    }
    return $default;
}

/**
 * 서비스 항목의 번역된 텍스트 가져오기
 * 폴백: 선택언어 → 영어 → 기본언어 → DB 원본
 */
function getServiceTranslated($svcId, $field, $default) {
    global $svcAllTranslations, $catLocaleChain;
    $key = "service.{$svcId}.{$field}";
    if (isset($svcAllTranslations[$key])) {
        foreach ($catLocaleChain as $loc) {
            if (!empty($svcAllTranslations[$key][$loc])) {
                return $svcAllTranslations[$key][$loc];
            }
        }
    }
    return $default;
}

// 서비스 목록 (카테고리 조인)
$filterCategory = $_GET['category'] ?? '';
$filterStatus = $_GET['status'] ?? '';

$sql = "SELECT s.*, c.name as category_name FROM {$prefix}services s LEFT JOIN {$prefix}service_categories c ON s.category_id = c.id WHERE 1=1";
$params = [];

if (!empty($filterCategory)) {
    $sql .= " AND s.category_id = ?";
    $params[] = $filterCategory;
}
if ($filterStatus === 'active') {
    $sql .= " AND s.is_active = 1";
} elseif ($filterStatus === 'inactive') {
    $sql .= " AND s.is_active = 0";
}
$sql .= " ORDER BY s.sort_order ASC";

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$services = $stmt->fetchAll(PDO::FETCH_ASSOC);

// 서비스 번역 맵 (JS용)
$svcTranslatedMap = [];
foreach ($services as $svc) {
    $svcTranslatedMap[$svc['id']] = [
        'name' => getServiceTranslated($svc['id'], 'name', $svc['name']),
        'description' => getServiceTranslated($svc['id'], 'description', $svc['description'] ?? ''),
    ];
}

// 통계
$totalServices = count($services);
$activeServices = 0;
foreach ($services as $s) { if ($s['is_active']) $activeServices++; }
$totalCategories = count($categories);
