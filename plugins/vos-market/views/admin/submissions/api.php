<?php
header('Content-Type: application/json; charset=utf-8');
// CSRF
$token = $_POST['_token'] ?? '';
if (!isset($_SESSION['_csrf']) || !hash_equals($_SESSION['_csrf'], $token)) {
    http_response_code(403); echo json_encode(['ok'=>false,'msg'=>'CSRF error']); exit;
}

$action = $_POST['action'] ?? '';
$id     = (int)($_POST['id'] ?? 0);
if (!$id) { http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'id required']); exit; }

try {
    $pdo = new PDO(
        "mysql:host={$_ENV['DB_HOST']};dbname={$_ENV['DB_DATABASE']};charset=utf8mb4",
        $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD'],
        [PDO::ATTR_ERRMODE=>PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE=>PDO::FETCH_ASSOC]
    );
    $pfx = $_ENV['DB_PREFIX'] ?? 'rzx_';
    $adminId = $_SESSION['user_id'] ?? null;

    $sub = $pdo->prepare("SELECT * FROM {$pfx}mkt_submissions WHERE id=?")->execute([$id]) ? null : null;
    $st = $pdo->prepare("SELECT * FROM {$pfx}mkt_submissions WHERE id=?"); $st->execute([$id]);
    $sub = $st->fetch();
    if (!$sub) { http_response_code(404); echo json_encode(['ok'=>false,'msg'=>'Not found']); exit; }

    if ($action === 'approve') {
        $pdo->beginTransaction();
        $data = json_decode($sub['submitted_data']??'{}', true) ?: [];

        if (!$sub['is_update'] && !$sub['item_id']) {
            // 신규 아이템
            $slug = $sub['submitted_slug'];
            $chk = $pdo->prepare("SELECT id FROM {$pfx}mkt_items WHERE slug=?"); $chk->execute([$slug]);
            if ($chk->fetchColumn()) $slug .= '-'.date('ymd');

            $pdo->prepare("INSERT INTO {$pfx}mkt_items
                (slug,type,name,description,short_description,author_name,repo_url,demo_url,
                 partner_id,tags,icon,banner_image,screenshots,price,currency,
                 license,min_voscms_version,min_php_version,latest_version,status)
                VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,'active')")
            ->execute([
                $slug, $sub['item_type'],
                json_encode($data['name']??[]),
                json_encode($data['description']??[]),
                json_encode($data['short_description']??[]),
                $data['author_name'] ?? null,
                $data['repo_url'] ?? null,
                $data['demo_url'] ?? null,
                $sub['partner_id'],
                json_encode($data['tags']??[]),
                $data['icon_url'] ?? null,
                $data['banner_url'] ?? null,
                json_encode($data['screenshot_urls']??[]),
                (float)($data['price']??0),
                $data['currency'] ?? 'JPY',
                $data['license'] ?? null,
                $data['min_voscms_version'] ?? null,
                $data['min_php_version'] ?? null,
                $sub['submitted_version'] ?? '1.0.0',
            ]);
            $itemId = (int)$pdo->lastInsertId();
            $pdo->prepare("UPDATE {$pfx}mkt_submissions SET item_id=? WHERE id=?")->execute([$itemId,$id]);
        } else {
            $itemId = (int)$sub['item_id'];
            $pdo->prepare("UPDATE {$pfx}mkt_items SET latest_version=?,updated_at=NOW() WHERE id=?")
                ->execute([$sub['submitted_version']??'1.0.0', $itemId]);
        }

        // 버전 추가
        $pdo->prepare("INSERT IGNORE INTO {$pfx}mkt_item_versions
            (item_id,version,changelog,file_path,file_hash,file_size,min_voscms_version,min_php_version,status)
            VALUES (?,?,?,?,?,?,?,?,'active')")
        ->execute([
            $itemId, $sub['submitted_version']??'1.0.0',
            $data['changelog']??null,
            $sub['package_path']??null, $sub['package_hash']??null, $sub['package_size']??null,
            $data['min_voscms_version']??null, $data['min_php_version']??null,
        ]);

        $pdo->prepare("UPDATE {$pfx}mkt_submissions SET status='approved',reviewer_id=?,reviewed_at=NOW() WHERE id=?")
            ->execute([$adminId,$id]);

        if ($sub['partner_id']) {
            $pdo->prepare("UPDATE {$pfx}mkt_partners SET item_count=item_count+1 WHERE id=? AND NOT EXISTS (SELECT 1 FROM {$pfx}mkt_items WHERE partner_id=? AND id=?)")
                ->execute([$sub['partner_id'],$sub['partner_id'],$itemId]);
        }

        $pdo->commit();
        echo json_encode(['ok'=>true,'msg'=>'승인 완료','item_id'=>$itemId]);

    } elseif ($action === 'reject') {
        $reason = trim($_POST['reason'] ?? '');
        $note   = trim($_POST['note'] ?? '');
        $pdo->prepare("UPDATE {$pfx}mkt_submissions SET status='rejected',rejection_reason=?,reviewer_note=?,reviewer_id=?,reviewed_at=NOW() WHERE id=?")
            ->execute([$reason,$note,$adminId,$id]);
        echo json_encode(['ok'=>true,'msg'=>'반려 완료']);
    } else {
        http_response_code(400); echo json_encode(['ok'=>false,'msg'=>'Unknown action']);
    }
} catch (Throwable $e) {
    if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
    http_response_code(500);
    echo json_encode(['ok'=>false,'msg'=>$e->getMessage()]);
}
