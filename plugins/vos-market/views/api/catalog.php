<?php
header('Content-Type: application/json; charset=utf-8');
try {
    $pdo = new PDO("mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]);
    $pfx = $_ENV['DB_PREFIX'] ?? 'rzx_';

    $type     = $_GET['type'] ?? '';
    $cat      = $_GET['cat'] ?? '';
    $q        = trim($_GET['q'] ?? '');
    $sort     = $_GET['sort'] ?? 'newest';
    $free     = isset($_GET['free']) && $_GET['free'];
    $paid     = isset($_GET['paid']) && $_GET['paid'];
    $featured = isset($_GET['featured']) && $_GET['featured'];
    $page     = max(1, (int)($_GET['page'] ?? 1));
    $limit    = min(50, max(1, (int)($_GET['limit'] ?? 20)));
    $offset   = ($page - 1) * $limit;
    $locale   = preg_replace('/[^a-zA-Z_]/', '', $_GET['locale'] ?? '');

    $where = ["i.status='active'"]; $params = [];
    if ($type && preg_match('/^[a-z]+$/', $type))         { $where[] = 'i.type=?';   $params[] = $type; }
    if ($cat  && preg_match('/^[a-z0-9\-]+$/', $cat))     { $where[] = 'c.slug=?';   $params[] = $cat; }
    if ($free)                                             { $where[] = 'i.price=0'; }
    if ($paid)                                             { $where[] = 'i.price>0'; }
    if ($featured)                                         { $where[] = 'i.is_featured=1'; }
    if ($q) {
        $where[] = '(i.slug LIKE ? OR i.name LIKE ?)';
        $params[] = "%$q%"; $params[] = "%$q%";
    }
    $ws = 'WHERE ' . implode(' AND ', $where);

    $orderBy = match ($sort) {
        'popular'    => 'i.download_count DESC',
        'price_asc'  => 'i.price ASC',
        'price_desc' => 'i.price DESC',
        'rating'     => 'i.rating_avg DESC',
        default      => 'i.created_at DESC',
    };

    $stc = $pdo->prepare("SELECT COUNT(*) FROM {$pfx}mkt_items i LEFT JOIN {$pfx}mkt_categories c ON c.id=i.category_id $ws");
    $stc->execute($params);
    $total = (int)$stc->fetchColumn();

    $sti = $pdo->prepare(
        "SELECT i.id, i.slug, i.type, i.name, i.short_description, i.icon, i.banner_image,
                i.price, i.currency, i.sale_price, i.sale_ends_at,
                i.latest_version, i.download_count, i.rating_avg, i.rating_count,
                i.is_featured, i.is_verified, i.tags, i.author_name,
                i.demo_url,
                c.slug AS cat_slug
         FROM {$pfx}mkt_items i
         LEFT JOIN {$pfx}mkt_categories c ON c.id = i.category_id
         $ws ORDER BY $orderBy LIMIT $limit OFFSET $offset"
    );
    $sti->execute($params);
    $items = $sti->fetchAll();

    $baseUrl = rtrim($_ENV['APP_URL'] ?? 'https://market.21ces.com', '/');
    $toAbsUrl = function(?string $val) use ($baseUrl): string {
        if (!$val) return '';
        if (str_starts_with($val, 'http://') || str_starts_with($val, 'https://')) return $val;
        if (str_starts_with($val, '/')) return $baseUrl . $val;
        return ''; // 아이콘 슬러그 등 URL이 아닌 값은 빈 문자열
    };
    $resolveLocale = $locale ? function($val) use ($locale) {
        if (!is_string($val) || $val === '') return $val;
        $arr = json_decode($val, true);
        if (!is_array($arr)) return $val;
        return $arr[$locale] ?? $arr['en'] ?? reset($arr) ?: '';
    } : null;
    foreach ($items as &$item) {
        $item['icon']         = $toAbsUrl($item['icon']);
        $item['banner_image'] = $toAbsUrl($item['banner_image']);
        if ($resolveLocale) {
            foreach (['name','short_description'] as $f) {
                if (isset($item[$f])) $item[$f] = $resolveLocale($item[$f]);
            }
        }
    }
    unset($item);

    echo json_encode([
        'ok'   => true,
        'data' => $items,
        'meta' => ['total' => $total, 'page' => $page, 'limit' => $limit, 'pages' => (int)ceil($total / $limit)],
    ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'msg' => $e->getMessage()]);
}
