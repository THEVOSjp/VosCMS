<?php
/**
 * RezlyX - 메뉴 데이터 로더
 *
 * DB에서 사이트맵별 메뉴를 로드하고 현재 로케일 번역을 적용합니다.
 * header.php, footer.php 등에서 include하여 사용합니다.
 *
 * 제공 변수:
 *   $siteMenus['Main Menu']   = [ {id, title, url, children:[...], ...}, ... ]
 *   $siteMenus['Footer Menu'] = [ ... ]
 *   $siteMenus['Utility Menu'] = [ ... ]
 */

// 메뉴 URL 생성 헬퍼
if (!function_exists('rzxMenuUrl')) {
    function rzxMenuUrl($item, $baseUrl) {
        $url = $item['url'] ?? '';
        if (empty($url) || $url === '#') return '#';
        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://')) return $url;
        if (str_starts_with($url, '#menu_')) return '#';
        if (!str_starts_with($url, '/')) $url = '/' . $url;
        return $baseUrl . $url;
    }
}

// 현재 페이지 매칭
if (!function_exists('rzxIsActive')) {
    function rzxIsActive($item, $currentPath, $baseUrl) {
        $url = $item['url'] ?? '';
        if (empty($url) || $url === '#') return false;
        $menuPath = str_starts_with($url, '/') ? $url : '/' . $url;
        $fullPath = rtrim($baseUrl, '/') . $menuPath;
        return $currentPath === $fullPath || ($menuPath !== '/' && str_starts_with($currentPath, $fullPath));
    }
}

if (!isset($siteMenus)) {
    $siteMenus = [];

    try {
        // PDO 연결 (index.php에서 생성된 $pdo 사용, 없으면 새로 생성)
        if (!isset($pdo)) {
            $pdo = new PDO(
                'mysql:host=' . ($_ENV['DB_HOST'] ?? 'localhost') . ';dbname=' . ($_ENV['DB_DATABASE'] ?? 'rezlyx') . ';charset=utf8mb4',
                $_ENV['DB_USERNAME'] ?? 'root',
                $_ENV['DB_PASSWORD'] ?? '',
                [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION]
            );
        }

        // 사이트맵 목록
        $__sitemaps = $pdo->query("SELECT id, title, slug FROM rzx_sitemaps ORDER BY sort_order ASC")->fetchAll(PDO::FETCH_ASSOC);

        // 메뉴 항목 전체 로드
        $__menuItems = [];
        $__stmt = $pdo->query("SELECT * FROM rzx_menu_items WHERE is_active = 1 ORDER BY sort_order ASC");
        while ($__row = $__stmt->fetch(PDO::FETCH_ASSOC)) {
            $__menuItems[$__row['sitemap_id']][] = $__row;
        }

        // 다국어 표시 룰:
        // 1. 선택언어 존재 → 선택언어 표시
        // 2. 선택언어 없음 + 영어 존재 → 영어 표시
        // 3. 선택언어 없음 + 영어 없음 → 기본언어 표시
        // 4. 모두 없음 → DB 원본 title
        $__locale = $config['locale'] ?? (function_exists('current_locale') ? current_locale() : 'ko');
        $__defaultLocale = $config['default_language'] ?? 'ko';

        // 폴백 체인에서 중복 제거: [선택언어, en, 기본언어]
        $__localeChain = array_unique(array_filter([$__locale, 'en', $__defaultLocale]));

        // 한 번의 쿼리로 필요한 모든 로케일 번역 로드
        $__placeholders = implode(',', array_fill(0, count($__localeChain), '?'));
        $__trStmt = $pdo->prepare("SELECT lang_key, locale, content FROM rzx_translations WHERE locale IN ({$__placeholders}) AND lang_key LIKE 'menu_item.%'");
        $__trStmt->execute(array_values($__localeChain));

        $__allTranslations = []; // [lang_key][locale] = content
        while ($__tr = $__trStmt->fetch(PDO::FETCH_ASSOC)) {
            $__allTranslations[$__tr['lang_key']][$__tr['locale']] = $__tr['content'];
        }

        // 번역 적용 함수 (폴백 체인 순서대로 검색)
        $__getTranslation = function($itemId, $field, $default) use ($__allTranslations, $__localeChain) {
            $key = "menu_item.{$itemId}.{$field}";
            if (isset($__allTranslations[$key])) {
                foreach ($__localeChain as $loc) {
                    if (!empty($__allTranslations[$key][$loc])) {
                        return $__allTranslations[$key][$loc];
                    }
                }
            }
            return $default;
        };

        // 트리 구조 생성 (번역 적용)
        $__buildTree = function($items, $parentId = null) use (&$__buildTree, $__getTranslation) {
            $tree = [];
            foreach ($items as $item) {
                if ($item['parent_id'] == $parentId) {
                    $item['title'] = $__getTranslation($item['id'], 'title', $item['title']);
                    $item['description'] = $__getTranslation($item['id'], 'description', $item['description'] ?? '');
                    $item['children'] = $__buildTree($items, $item['id']);
                    $tree[] = $item;
                }
            }
            return $tree;
        };

        // 사이트맵별 트리 구성
        foreach ($__sitemaps as $__sm) {
            $__items = $__menuItems[$__sm['id']] ?? [];
            $siteMenus[$__sm['title']] = $__buildTree($__items);
        }

    } catch (PDOException $e) {
        error_log('[MenuLoader] DB Error: ' . $e->getMessage());
        $siteMenus = [];
    }
}
