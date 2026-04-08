<?php
namespace RzxLib\Core\Modules;

/**
 * OG(Open Graph) 메타데이터 추출 모듈
 *
 * 사용법:
 *   $og = OgFetcher::fetch('https://example.com');
 *   // ['title'=>'...', 'description'=>'...', 'image'=>'...', 'site_name'=>'...', 'url'=>'...']
 */
class OgFetcher
{
    private static int $timeout = 5;
    private static int $maxSize = 512000; // 500KB

    /**
     * URL에서 OG 메타데이터 추출
     */
    public static function fetch(string $url): array
    {
        $result = [
            'url' => $url,
            'title' => '',
            'description' => '',
            'image' => '',
            'site_name' => '',
            'type' => '',
            'favicon' => '',
        ];

        if (!filter_var($url, FILTER_VALIDATE_URL)) return $result;

        // URL 스키마 확인
        $scheme = parse_url($url, PHP_URL_SCHEME);
        if (!in_array($scheme, ['http', 'https'])) return $result;

        $html = self::fetchHtml($url);
        if (!$html) return $result;

        // 인코딩 처리
        $html = self::ensureUtf8($html);

        // OG 태그 추출
        $result['title'] = self::extractMeta($html, 'og:title')
            ?: self::extractMeta($html, 'twitter:title')
            ?: self::extractTitle($html);
        $result['description'] = self::extractMeta($html, 'og:description')
            ?: self::extractMeta($html, 'twitter:description')
            ?: self::extractMeta($html, 'description');
        $result['image'] = self::extractMeta($html, 'og:image')
            ?: self::extractMeta($html, 'twitter:image');
        $result['site_name'] = self::extractMeta($html, 'og:site_name');
        $result['type'] = self::extractMeta($html, 'og:type');
        $result['favicon'] = self::extractFavicon($html, $url);

        // 상대 URL → 절대 URL
        if ($result['image'] && !preg_match('#^https?://#', $result['image'])) {
            $base = parse_url($url);
            $result['image'] = ($base['scheme'] ?? 'https') . '://' . ($base['host'] ?? '') . '/' . ltrim($result['image'], '/');
        }

        // 텍스트 정리
        $result['title'] = html_entity_decode(trim($result['title']), ENT_QUOTES, 'UTF-8');
        $result['description'] = html_entity_decode(trim($result['description']), ENT_QUOTES, 'UTF-8');
        if (mb_strlen($result['description']) > 200) {
            $result['description'] = mb_substr($result['description'], 0, 200) . '...';
        }

        // 도메인
        $result['domain'] = parse_url($url, PHP_URL_HOST) ?: '';

        return $result;
    }

    private static function fetchHtml(string $url): string
    {
        $ctx = stream_context_create([
            'http' => [
                'timeout' => self::$timeout,
                'max_redirects' => 3,
                'header' => "User-Agent: RezlyX/1.0 (OG Fetcher)\r\nAccept: text/html\r\n",
                'ignore_errors' => true,
            ],
            'ssl' => ['verify_peer' => false, 'verify_peer_name' => false],
        ]);

        $html = @file_get_contents($url, false, $ctx, 0, self::$maxSize);
        return $html ?: '';
    }

    private static function ensureUtf8(string $html): string
    {
        // charset 감지
        if (preg_match('/charset=["\']?([a-zA-Z0-9\-]+)/i', $html, $m)) {
            $charset = strtolower($m[1]);
            if ($charset !== 'utf-8' && $charset !== 'utf8') {
                $converted = @iconv($charset, 'UTF-8//IGNORE', $html);
                if ($converted) return $converted;
            }
        }
        if (!mb_check_encoding($html, 'UTF-8')) {
            return mb_convert_encoding($html, 'UTF-8', 'auto');
        }
        return $html;
    }

    private static function extractMeta(string $html, string $property): string
    {
        // og: / twitter: → property 속성
        // description → name 속성
        $patterns = [
            '/<meta[^>]+property=["\']' . preg_quote($property, '/') . '["\'][^>]+content=["\']([^"\']*)["\'][^>]*>/si',
            '/<meta[^>]+content=["\']([^"\']*)["\'][^>]+property=["\']' . preg_quote($property, '/') . '["\'][^>]*>/si',
            '/<meta[^>]+name=["\']' . preg_quote($property, '/') . '["\'][^>]+content=["\']([^"\']*)["\'][^>]*>/si',
            '/<meta[^>]+content=["\']([^"\']*)["\'][^>]+name=["\']' . preg_quote($property, '/') . '["\'][^>]*>/si',
        ];
        foreach ($patterns as $p) {
            if (preg_match($p, $html, $m)) return $m[1];
        }
        return '';
    }

    private static function extractTitle(string $html): string
    {
        if (preg_match('/<title[^>]*>(.*?)<\/title>/si', $html, $m)) {
            return trim(strip_tags($m[1]));
        }
        return '';
    }

    private static function extractFavicon(string $html, string $url): string
    {
        if (preg_match('/<link[^>]+rel=["\'](?:shortcut )?icon["\'][^>]+href=["\']([^"\']+)["\'][^>]*>/si', $html, $m)) {
            $icon = $m[1];
            if (!preg_match('#^https?://#', $icon)) {
                $base = parse_url($url);
                $icon = ($base['scheme'] ?? 'https') . '://' . ($base['host'] ?? '') . '/' . ltrim($icon, '/');
            }
            return $icon;
        }
        $base = parse_url($url);
        return ($base['scheme'] ?? 'https') . '://' . ($base['host'] ?? '') . '/favicon.ico';
    }
}
