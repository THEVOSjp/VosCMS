<?php
header('Content-Type: application/json; charset=utf-8');
try {
    $pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $pfx = $_ENV['DB_PREFIX'] ?? 'rzx_';
    $slug = trim($_GET['slug'] ?? '');
    $locale = preg_replace('/[^a-zA-Z_]/', '', $_GET['locale'] ?? '');
    if (!$slug) { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'slug required']); exit; }
    $st = $pdo->prepare("SELECT i.*,c.slug cat_slug,c.name cat_name,p.display_name author FROM {$pfx}mkt_items i LEFT JOIN {$pfx}mkt_categories c ON c.id=i.category_id LEFT JOIN {$pfx}mkt_partners p ON p.id=i.partner_id WHERE i.slug=? AND i.status='active'");
    $st->execute([$slug]); $item = $st->fetch();
    if (!$item) { http_response_code(404); echo json_encode(['ok'=>false,'msg'=>'Not found']); exit; }
    $baseUrl = rtrim($_ENV['APP_URL'] ?? 'https://market.21ces.com', '/');

    // locale 지정 시 다국어 JSON 필드를 단일 문자열로 변환 (폴백: en → 첫 값)
    if ($locale) {
        $resolveLocale = function($val) use ($locale) {
            if (!is_string($val) || $val === '') return $val;
            $arr = json_decode($val, true);
            if (!is_array($arr)) return $val;
            return $arr[$locale] ?? $arr['en'] ?? reset($arr) ?: '';
        };
        foreach (['name','description','short_description','cat_name'] as $f) {
            if (isset($item[$f])) $item[$f] = $resolveLocale($item[$f]);
        }
    }
    $toAbs = function(?string $v) use ($baseUrl): string {
        if (!$v) return '';
        if (str_starts_with($v,'http://') || str_starts_with($v,'https://')) return $v;
        return str_starts_with($v,'/') ? $baseUrl.$v : '';
    };
    foreach (['icon','banner_image'] as $f) {
        $item[$f] = $toAbs($item[$f] ?? '');
    }
    // screenshots 배열 절대 URL 변환 + 존재하지 않는 파일 제외
    if (!empty($item['screenshots'])) {
        $arr = json_decode($item['screenshots'], true);
        if (is_array($arr)) {
            $valid = [];
            foreach ($arr as $p) {
                if (!$p) continue;
                if (str_starts_with($p, 'http://') || str_starts_with($p, 'https://')) {
                    $valid[] = $p;
                    continue;
                }
                $fs = (defined('BASE_PATH') ? BASE_PATH : dirname(__DIR__, 4)) . '/' . ltrim($p, '/');
                if (file_exists($fs)) $valid[] = $toAbs($p);
            }
            $item['screenshots'] = json_encode($valid, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
        }
    }
    echo json_encode(['ok'=>true,'data'=>$item], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500); echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
