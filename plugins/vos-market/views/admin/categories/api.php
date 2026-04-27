<?php
header('Content-Type: application/json; charset=utf-8');
$token = $_POST['_token'] ?? '';
if (!isset($_SESSION['_csrf']) || !hash_equals($_SESSION['_csrf'], $token)) {
    http_response_code(403); echo json_encode(['ok'=>false,'msg'=>'CSRF error']); exit;
}
$action = $_POST['action'] ?? '';
try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
    );
    $pfx = $_ENV['DB_PREFIX'] ?? 'rzx_';

    if ($action === 'create') {
        $slug = trim($_POST['slug'] ?? '');
        if (!$slug || !preg_match('/^[a-z0-9\-]+$/', $slug)) { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'유효하지 않은 슬러그']); exit; }
        $chk = $pdo->prepare("SELECT id FROM {$pfx}mkt_categories WHERE slug=?"); $chk->execute([$slug]);
        if ($chk->fetchColumn()) { echo json_encode(['ok'=>false,'msg'=>'이미 사용 중인 슬러그입니다']); exit; }
        $pdo->prepare("INSERT INTO {$pfx}mkt_categories (slug,name,description,icon,sort_order,is_active) VALUES (?,?,?,?,?,?)")
            ->execute([$slug, $_POST['name']??'{}', $_POST['description']??'{}', $_POST['icon']??null, (int)($_POST['sort_order']??0), (int)($_POST['is_active']??1)]);
        echo json_encode(['ok'=>true,'msg'=>'카테고리 생성 완료','id'=>(int)$pdo->lastInsertId()]);

    } elseif ($action === 'update') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'id required']); exit; }
        $slug = trim($_POST['slug'] ?? '');
        if (!$slug || !preg_match('/^[a-z0-9\-]+$/', $slug)) { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'유효하지 않은 슬러그']); exit; }
        $chk = $pdo->prepare("SELECT id FROM {$pfx}mkt_categories WHERE slug=? AND id!=?"); $chk->execute([$slug,$id]);
        if ($chk->fetchColumn()) { echo json_encode(['ok'=>false,'msg'=>'이미 사용 중인 슬러그입니다']); exit; }
        $pdo->prepare("UPDATE {$pfx}mkt_categories SET slug=?,name=?,description=?,icon=?,sort_order=?,is_active=?,updated_at=NOW() WHERE id=?")
            ->execute([$slug, $_POST['name']??'{}', $_POST['description']??'{}', $_POST['icon']??null, (int)($_POST['sort_order']??0), (int)($_POST['is_active']??1), $id]);
        echo json_encode(['ok'=>true,'msg'=>'수정 완료']);

    } elseif ($action === 'delete') {
        $id = (int)($_POST['id'] ?? 0);
        if (!$id) { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'id required']); exit; }
        $pdo->prepare("UPDATE {$pfx}mkt_items SET category_id=NULL WHERE category_id=?")->execute([$id]);
        $pdo->prepare("DELETE FROM {$pfx}mkt_categories WHERE id=?")->execute([$id]);
        echo json_encode(['ok'=>true,'msg'=>'삭제 완료']);

    } else {
        http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'Unknown action']);
    }
} catch (Throwable $e) {
    http_response_code(500);
    echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
