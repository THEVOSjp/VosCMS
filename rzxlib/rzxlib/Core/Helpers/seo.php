<?php
/**
 * RezlyX SEO Helper
 *
 * SEO 설정 기반 메타 태그 생성
 *
 * 사용법 (base-header.php에서):
 *   $seoMeta = rzx_seo_meta($siteSettings, $baseUrl, $siteName, $seoContext ?? []);
 *   echo $seoMeta['title_tag'];   // <title> 내용
 *   echo $seoMeta['meta_tags'];   // OG, Twitter, author 등 메타 태그 HTML
 *
 * $seoContext 키:
 *   'type'           => 'main' | 'sub' | 'document'  (기본: 'sub')
 *   'subpage_title'  => 서브페이지 제목
 *   'document_title' => 문서 제목
 *   'category'       => 카테고리명
 *   'page'           => 페이지 번호
 *   'content'        => 본문 HTML (설명/이미지/해시태그 추출용)
 *   'image'          => 명시적 OG 이미지 URL
 *   'description'    => 명시적 설명
 *   'author'         => 작성자 이름
 *   'published_at'   => 작성일 (ISO 8601 or Y-m-d H:i:s)
 *   'modified_at'    => 수정일
 *   'url'            => 현재 페이지 전체 URL
 */

function rzx_seo_meta(array $ss, string $baseUrl, string $siteName, array $ctx = []): array
{
    $type = $ctx['type'] ?? 'sub';

    // 다국어 번역 헬퍼 (db_trans가 있으면 로케일별 번역 우선)
    $t = function(string $langKey, string $fallback) {
        if (function_exists('db_trans')) {
            $v = db_trans($langKey, null, '');
            if ($v !== '') return $v;
        }
        return $fallback;
    };

    // --- 1. 제목 패턴 ---
    $titlePatterns = [
        'main'     => $t('settings.seo_title_main', $ss['seo_title_main'] ?? '$SITE_TITLE - $SITE_SUBTITLE'),
        'sub'      => $t('settings.seo_title_sub', $ss['seo_title_sub'] ?? '$SITE_TITLE - $SUBPAGE_TITLE'),
        'document' => $t('settings.seo_title_document', $ss['seo_title_document'] ?? '$SITE_TITLE - $DOCUMENT_TITLE'),
    ];
    $pattern = $titlePatterns[$type] ?? $titlePatterns['sub'];

    $siteSubtitle = function_exists('get_site_tagline') ? get_site_tagline() : ($ss['site_tagline'] ?? '');

    $vars = [
        '$SITE_TITLE'     => $siteName,
        '$SITE_SUBTITLE'  => $siteSubtitle,
        '$SUBPAGE_TITLE'  => $ctx['subpage_title'] ?? '',
        '$DOCUMENT_TITLE' => $ctx['document_title'] ?? '',
        '$CATEGORY'       => $ctx['category'] ?? '',
        '$PAGE'           => !empty($ctx['page']) && $ctx['page'] > 1 ? $ctx['page'] : '',
    ];
    $title = str_replace(array_keys($vars), array_values($vars), $pattern);
    // 빈 변수 치환 후 정리 (연속 구분자 제거)
    $title = preg_replace('/\s*-\s*-\s*/', ' - ', $title);
    $title = preg_replace('/\s*-\s*$/', '', $title);
    $title = preg_replace('/^\s*-\s*/', '', $title);
    $title = trim($title);

    // --- 2. 설명 ---
    $description = $ctx['description'] ?? '';
    if (!$description && ($ss['seo_extract_desc'] ?? 'N') === 'Y' && !empty($ctx['content'])) {
        $description = _rzx_extract_description($ctx['content']);
    }
    if (!$description) {
        $description = $t('settings.seo_description', $ss['seo_description'] ?? '');
    }

    // --- 3. 이미지 ---
    $image = $ctx['image'] ?? '';
    if (!$image && ($ss['seo_extract_image'] ?? 'N') === 'Y' && !empty($ctx['content'])) {
        $image = _rzx_extract_first_image($ctx['content'], $baseUrl);
    }
    if (!$image && !empty($ss['og_image'])) {
        $image = $baseUrl . $ss['og_image'];
    }

    // --- 4. 해시태그 → 키워드 ---
    $extraKeywords = '';
    if (($ss['seo_extract_hashtag'] ?? 'N') === 'Y' && !empty($ctx['content'])) {
        $extraKeywords = _rzx_extract_hashtags($ctx['content']);
    }

    // --- 5. URL ---
    $url = $ctx['url'] ?? ($baseUrl . ($_SERVER['REQUEST_URI'] ?? '/'));

    // --- 6. 메타 태그 생성 ---
    $meta = '';

    // OG 태그
    if (($ss['seo_og_tag'] ?? 'N') === 'Y') {
        $meta .= _rzx_meta_og($title, $description, $image, $url, $siteName);
    }

    // Twitter 태그
    if (($ss['seo_twitter_tag'] ?? 'N') === 'Y') {
        $meta .= _rzx_meta_twitter($title, $description, $image);
    }

    // 작성자
    if (($ss['seo_show_author'] ?? 'N') === 'Y' && !empty($ctx['author'])) {
        $meta .= '    <meta name="author" content="' . htmlspecialchars($ctx['author']) . '">' . "\n";
    }

    // 작성/수정 시각
    if (($ss['seo_show_datetime'] ?? 'N') === 'Y') {
        if (!empty($ctx['published_at'])) {
            $meta .= '    <meta property="article:published_time" content="' . htmlspecialchars(_rzx_to_iso8601($ctx['published_at'])) . '">' . "\n";
        }
        if (!empty($ctx['modified_at'])) {
            $meta .= '    <meta property="article:modified_time" content="' . htmlspecialchars(_rzx_to_iso8601($ctx['modified_at'])) . '">' . "\n";
        }
    }

    // 해시태그 키워드 추가
    if ($extraKeywords) {
        $meta .= '    <meta name="article:tag" content="' . htmlspecialchars($extraKeywords) . '">' . "\n";
    }

    return [
        'title'      => $title,
        'title_tag'  => $title,
        'meta_tags'  => $meta,
        'description' => $description,
        'image'      => $image,
        'keywords_extra' => $extraKeywords,
    ];
}

// --- 내부 함수들 ---

function _rzx_extract_description(string $html, int $maxLen = 160): string
{
    $text = strip_tags($html);
    $text = preg_replace('/\s+/', ' ', $text);
    $text = trim($text);
    if (mb_strlen($text) > $maxLen) {
        $text = mb_substr($text, 0, $maxLen) . '...';
    }
    return $text;
}

function _rzx_extract_first_image(string $html, string $baseUrl): string
{
    if (preg_match('/<img[^>]+src=["\']([^"\']+)["\']/i', $html, $m)) {
        $src = $m[1];
        if (!preg_match('/^https?:\/\//', $src)) {
            $src = $baseUrl . '/' . ltrim($src, '/');
        }
        return $src;
    }
    return '';
}

function _rzx_extract_hashtags(string $html): string
{
    $text = strip_tags($html);
    if (preg_match_all('/#([\p{L}\p{N}_]+)/u', $text, $m)) {
        return implode(', ', array_unique(array_slice($m[1], 0, 10)));
    }
    return '';
}

function _rzx_to_iso8601(string $dt): string
{
    if (strpos($dt, 'T') !== false) return $dt; // 이미 ISO 형식
    try {
        return (new DateTime($dt))->format('c');
    } catch (Exception $e) {
        return $dt;
    }
}

function _rzx_meta_og(string $title, string $desc, string $image, string $url, string $siteName): string
{
    $out  = '    <meta property="og:type" content="website">' . "\n";
    $out .= '    <meta property="og:site_name" content="' . htmlspecialchars($siteName) . '">' . "\n";
    $out .= '    <meta property="og:title" content="' . htmlspecialchars($title) . '">' . "\n";
    if ($desc) {
        $out .= '    <meta property="og:description" content="' . htmlspecialchars($desc) . '">' . "\n";
    }
    $out .= '    <meta property="og:url" content="' . htmlspecialchars($url) . '">' . "\n";
    if ($image) {
        $out .= '    <meta property="og:image" content="' . htmlspecialchars($image) . '">' . "\n";
    }
    return $out;
}

function _rzx_meta_twitter(string $title, string $desc, string $image): string
{
    $cardType = $image ? 'summary_large_image' : 'summary';
    $out  = '    <meta name="twitter:card" content="' . $cardType . '">' . "\n";
    $out .= '    <meta name="twitter:title" content="' . htmlspecialchars($title) . '">' . "\n";
    if ($desc) {
        $out .= '    <meta name="twitter:description" content="' . htmlspecialchars($desc) . '">' . "\n";
    }
    if ($image) {
        $out .= '    <meta name="twitter:image" content="' . htmlspecialchars($image) . '">' . "\n";
    }
    return $out;
}
